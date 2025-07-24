<?php

namespace Api\V8\Authentication;

use PDO;
use Exception;

class SessionManager
{
    private PDO $db;
    private int $sessionTimeout;  // 24 hours in seconds
    private int $maxSessions;     // Maximum concurrent sessions per user

    public function __construct(PDO $database)
    {
        $this->db = $database;
        $this->sessionTimeout = 86400; // 24 hours
        $this->maxSessions = 5; // Max 5 concurrent sessions per user
    }

    /**
     * Create new session
     */
    public function createSession(string $userId, array $sessionData = []): string
    {
        $sessionId = $this->generateSessionId();
        $deviceInfo = $this->getDeviceInfo();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        try {
            $this->db->beginTransaction();
            
            // Clean up expired sessions first
            $this->cleanupExpiredSessions();
            
            // Check and enforce maximum sessions limit
            $this->enforceSessionLimit($userId);
            
            // Create new session record
            $insertQuery = "
                INSERT INTO user_sessions (
                    id, user_id, session_token, ip_address, user_agent, 
                    device_info, session_data, created_date, last_activity, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))
            ";
            
            $stmt = $this->db->prepare($insertQuery);
            $stmt->execute([
                $this->generateUUID(),
                $userId,
                $sessionId,
                $ipAddress,
                $userAgent,
                json_encode($deviceInfo),
                json_encode($sessionData),
                $this->sessionTimeout
            ]);
            
            $this->db->commit();
            
            return $sessionId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Failed to create session: ' . $e->getMessage());
        }
    }

    /**
     * Validate session and update last activity
     */
    public function validateSession(string $sessionToken): ?array
    {
        $query = "
            SELECT us.*, u.user_name, u.first_name, u.last_name, u.email1, u.status
            FROM user_sessions us
            JOIN users u ON us.user_id = u.id
            WHERE us.session_token = ?
            AND us.expires_at > NOW()
            AND us.is_active = 1
            AND u.deleted = 0
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$sessionToken]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            return null;
        }
        
        // Check if user account is still active
        if ($session['status'] !== 'Active') {
            $this->invalidateSession($sessionToken);
            return null;
        }
        
        // Update last activity
        $this->updateLastActivity($sessionToken);
        
        // Return session data
        return [
            'session_id' => $session['id'],
            'user_id' => $session['user_id'],
            'username' => $session['user_name'],
            'first_name' => $session['first_name'],
            'last_name' => $session['last_name'],
            'email' => $session['email1'],
            'session_data' => json_decode($session['session_data'], true) ?? [],
            'device_info' => json_decode($session['device_info'], true) ?? [],
            'ip_address' => $session['ip_address'],
            'created_date' => $session['created_date'],
            'last_activity' => $session['last_activity']
        ];
    }

    /**
     * Update session data
     */
    public function updateSessionData(string $sessionToken, array $data): bool
    {
        $query = "
            UPDATE user_sessions 
            SET session_data = ?, last_activity = NOW()
            WHERE session_token = ? AND is_active = 1
        ";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([json_encode($data), $sessionToken]);
    }

    /**
     * Invalidate specific session
     */
    public function invalidateSession(string $sessionToken): bool
    {
        $query = "
            UPDATE user_sessions 
            SET is_active = 0, invalidated_at = NOW()
            WHERE session_token = ?
        ";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$sessionToken]);
    }

    /**
     * Invalidate all sessions for a user
     */
    public function invalidateAllUserSessions(string $userId, ?string $excludeSession = null): bool
    {
        $query = "
            UPDATE user_sessions 
            SET is_active = 0, invalidated_at = NOW()
            WHERE user_id = ?
        ";
        
        $params = [$userId];
        
        if ($excludeSession) {
            $query .= " AND session_token != ?";
            $params[] = $excludeSession;
        }
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Get all active sessions for a user
     */
    public function getUserSessions(string $userId): array
    {
        $query = "
            SELECT id, session_token, ip_address, user_agent, device_info,
                   created_date, last_activity, expires_at
            FROM user_sessions
            WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
            ORDER BY last_activity DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$userId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse device info and add human-readable descriptions
        foreach ($sessions as &$session) {
            $deviceInfo = json_decode($session['device_info'], true) ?? [];
            $session['device_description'] = $this->getDeviceDescription($deviceInfo);
            $session['is_current'] = $this->isCurrentSession($session['session_token']);
            $session['time_since_activity'] = $this->getTimeSinceActivity($session['last_activity']);
        }
        
        return $sessions;
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        $query = "
            UPDATE user_sessions 
            SET is_active = 0, invalidated_at = NOW()
            WHERE expires_at <= NOW() AND is_active = 1
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->rowCount();
    }

    /**
     * Extend session expiry
     */
    public function extendSession(string $sessionToken, int $additionalSeconds = null): bool
    {
        $extensionTime = $additionalSeconds ?? $this->sessionTimeout;
        
        $query = "
            UPDATE user_sessions 
            SET expires_at = DATE_ADD(NOW(), INTERVAL ? SECOND),
                last_activity = NOW()
            WHERE session_token = ? AND is_active = 1
        ";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$extensionTime, $sessionToken]);
    }

    /**
     * Get session statistics
     */
    public function getSessionStatistics(): array
    {
        $stats = [];
        
        // Total active sessions
        $totalQuery = "SELECT COUNT(*) as total FROM user_sessions WHERE is_active = 1 AND expires_at > NOW()";
        $stmt = $this->db->prepare($totalQuery);
        $stmt->execute();
        $stats['total_active_sessions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Sessions by device type
        $deviceQuery = "
            SELECT 
                JSON_EXTRACT(device_info, '$.device_type') as device_type,
                COUNT(*) as session_count
            FROM user_sessions 
            WHERE is_active = 1 AND expires_at > NOW()
            GROUP BY JSON_EXTRACT(device_info, '$.device_type')
        ";
        $stmt = $this->db->prepare($deviceQuery);
        $stmt->execute();
        $stats['sessions_by_device'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent session activity
        $recentQuery = "
            SELECT COUNT(*) as recent_sessions
            FROM user_sessions 
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND is_active = 1
        ";
        $stmt = $this->db->prepare($recentQuery);
        $stmt->execute();
        $stats['recent_activity'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_sessions'];
        
        return $stats;
    }

    /**
     * Detect suspicious session activity
     */
    public function detectSuspiciousActivity(string $userId): array
    {
        $suspiciousActivities = [];
        
        // Multiple sessions from different IPs
        $ipQuery = "
            SELECT ip_address, COUNT(*) as session_count
            FROM user_sessions
            WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
            GROUP BY ip_address
            HAVING COUNT(*) > 1
        ";
        
        $stmt = $this->db->prepare($ipQuery);
        $stmt->execute([$userId]);
        $multipleIPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($multipleIPs) > 2) {
            $suspiciousActivities[] = [
                'type' => 'multiple_ips',
                'severity' => 'medium',
                'description' => 'User has active sessions from multiple IP addresses',
                'details' => $multipleIPs
            ];
        }
        
        // Rapid session creation
        $rapidQuery = "
            SELECT COUNT(*) as recent_sessions
            FROM user_sessions
            WHERE user_id = ? AND created_date >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ";
        
        $stmt = $this->db->prepare($rapidQuery);
        $stmt->execute([$userId]);
        $recentSessions = $stmt->fetch(PDO::FETCH_ASSOC)['recent_sessions'];
        
        if ($recentSessions > 3) {
            $suspiciousActivities[] = [
                'type' => 'rapid_sessions',
                'severity' => 'high',
                'description' => 'Multiple sessions created in short time period',
                'details' => ['session_count' => $recentSessions]
            ];
        }
        
        return $suspiciousActivities;
    }

    /**
     * Create session security report
     */
    public function generateSecurityReport(string $userId): array
    {
        $report = [];
        
        // Get user sessions
        $sessions = $this->getUserSessions($userId);
        $report['active_sessions'] = count($sessions);
        $report['sessions'] = $sessions;
        
        // Check for suspicious activity
        $report['suspicious_activity'] = $this->detectSuspiciousActivity($userId);
        
        // Recent login history
        $historyQuery = "
            SELECT ip_address, user_agent, created_date,
                   CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as status
            FROM user_sessions
            WHERE user_id = ?
            ORDER BY created_date DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->prepare($historyQuery);
        $stmt->execute([$userId]);
        $report['recent_logins'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $report;
    }

    /**
     * Private helper methods
     */
    
    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function getDeviceInfo(): array
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $deviceInfo = [
            'user_agent' => $userAgent,
            'device_type' => $this->detectDeviceType($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'os' => $this->detectOS($userAgent),
            'is_mobile' => $this->isMobile($userAgent),
            'screen_resolution' => null, // Would need JavaScript to capture
            'timezone' => null // Would need JavaScript to capture
        ];
        
        return $deviceInfo;
    }

    private function detectDeviceType(string $userAgent): string
    {
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }

    private function detectBrowser(string $userAgent): string
    {
        if (preg_match('/Chrome/i', $userAgent)) {
            return 'Chrome';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            return 'Firefox';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            return 'Safari';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            return 'Edge';
        } else {
            return 'Unknown';
        }
    }

    private function detectOS(string $userAgent): string
    {
        if (preg_match('/Windows/i', $userAgent)) {
            return 'Windows';
        } elseif (preg_match('/Mac/i', $userAgent)) {
            return 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            return 'Android';
        } elseif (preg_match('/iOS/i', $userAgent)) {
            return 'iOS';
        } else {
            return 'Unknown';
        }
    }

    private function isMobile(string $userAgent): bool
    {
        return preg_match('/mobile|android|iphone|phone/i', $userAgent) === 1;
    }

    private function updateLastActivity(string $sessionToken): void
    {
        $query = "UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$sessionToken]);
    }

    private function enforceSessionLimit(string $userId): void
    {
        // Count current active sessions
        $countQuery = "
            SELECT COUNT(*) as session_count
            FROM user_sessions
            WHERE user_id = ? AND is_active = 1 AND expires_at > NOW()
        ";
        
        $stmt = $this->db->prepare($countQuery);
        $stmt->execute([$userId]);
        $currentSessions = $stmt->fetch(PDO::FETCH_ASSOC)['session_count'];
        
        if ($currentSessions >= $this->maxSessions) {
            // Remove oldest sessions to make room
            $removeQuery = "
                UPDATE user_sessions 
                SET is_active = 0, invalidated_at = NOW()
                WHERE user_id = ? AND is_active = 1
                ORDER BY last_activity ASC
                LIMIT ?
            ";
            
            $sessionsToRemove = $currentSessions - $this->maxSessions + 1;
            $stmt = $this->db->prepare($removeQuery);
            $stmt->execute([$userId, $sessionsToRemove]);
        }
    }

    private function getDeviceDescription(array $deviceInfo): string
    {
        $browser = $deviceInfo['browser'] ?? 'Unknown Browser';
        $os = $deviceInfo['os'] ?? 'Unknown OS';
        $deviceType = $deviceInfo['device_type'] ?? 'Unknown Device';
        
        return "{$browser} on {$os} ({$deviceType})";
    }

    private function isCurrentSession(string $sessionToken): bool
    {
        // This would need to be set from the current request context
        // For now, return false as placeholder
        return false;
    }

    private function getTimeSinceActivity(string $lastActivity): string
    {
        $timestamp = strtotime($lastActivity);
        $now = time();
        $diff = $now - $timestamp;
        
        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "{$minutes} minute" . ($minutes !== 1 ? 's' : '') . " ago";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "{$hours} hour" . ($hours !== 1 ? 's' : '') . " ago";
        } else {
            $days = floor($diff / 86400);
            return "{$days} day" . ($days !== 1 ? 's' : '') . " ago";
        }
    }

    /**
     * Initialize session management (create table if needed)
     */
    public function initializeSessionManagement(): bool
    {
        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS user_sessions (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                session_token VARCHAR(64) NOT NULL UNIQUE,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                device_info JSON,
                session_data JSON,
                created_date DATETIME NOT NULL,
                last_activity DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                invalidated_at DATETIME NULL,
                INDEX idx_user_sessions_user_id (user_id),
                INDEX idx_user_sessions_token (session_token),
                INDEX idx_user_sessions_expires (expires_at),
                INDEX idx_user_sessions_active (is_active, expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        try {
            $this->db->exec($createTableSQL);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
