<?php

/**
 * Security Audit Logging System
 * Comprehensive logging of security events for monitoring and compliance
 */
class SecurityAudit
{
    private const LOG_FILE = 'security_audit.log';
    private const MAX_LOG_SIZE = 10485760; // 10MB
    private const LOG_RETENTION_DAYS = 365;
    
    // Event severity levels
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    // Event categories
    const CATEGORY_AUTH = 'authentication';
    const CATEGORY_ACCESS = 'access_control';
    const CATEGORY_DATA = 'data_access';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_NETWORK = 'network';
    
    private static $instance = null;
    private $logPath;
    private $dbLogging = true;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->logPath = $this->getLogPath();
        $this->ensureLogDirectory();
        $this->checkLogRotation();
    }

    /**
     * Log security event
     */
    public static function logEvent(
        string $category, 
        string $event, 
        array $details = [], 
        string $severity = self::SEVERITY_MEDIUM,
        string $userId = null
    ): void {
        $instance = self::getInstance();
        $instance->writeEvent($category, $event, $details, $severity, $userId);
    }

    /**
     * Log authentication events
     */
    public static function logAuthEvent(string $event, array $details = [], string $severity = self::SEVERITY_MEDIUM): void
    {
        self::logEvent(self::CATEGORY_AUTH, $event, $details, $severity);
    }

    /**
     * Log access control events
     */
    public static function logAccessEvent(string $event, array $details = [], string $severity = self::SEVERITY_MEDIUM): void
    {
        self::logEvent(self::CATEGORY_ACCESS, $event, $details, $severity);
    }

    /**
     * Log data access events
     */
    public static function logDataEvent(string $event, array $details = [], string $severity = self::SEVERITY_MEDIUM): void
    {
        self::logEvent(self::CATEGORY_DATA, $event, $details, $severity);
    }

    /**
     * Write event to log
     */
    private function writeEvent(
        string $category, 
        string $event, 
        array $details, 
        string $severity, 
        string $userId = null
    ): void {
        $logEntry = $this->createLogEntry($category, $event, $details, $severity, $userId);
        
        // Write to file
        $this->writeToFile($logEntry);
        
        // Write to database if enabled
        if ($this->dbLogging) {
            $this->writeToDatabase($logEntry);
        }
        
        // Send alerts for critical events
        if ($severity === self::SEVERITY_CRITICAL) {
            $this->sendCriticalAlert($logEntry);
        }
        
        // Check for suspicious patterns
        $this->checkSuspiciousPatterns($category, $event, $details);
    }

    /**
     * Create structured log entry
     */
    private function createLogEntry(
        string $category, 
        string $event, 
        array $details, 
        string $severity, 
        string $userId = null
    ): array {
        return [
            'timestamp' => date('c'),
            'event_id' => $this->generateEventId(),
            'category' => $category,
            'event' => $event,
            'severity' => $severity,
            'user_id' => $userId ?? $this->getCurrentUserId(),
            'session_id' => session_id(),
            'ip_address' => $this->getClientIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? null,
            'details' => $details,
            'server_info' => [
                'hostname' => gethostname(),
                'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown'
            ]
        ];
    }

    /**
     * Write log entry to file
     */
    private function writeToFile(array $logEntry): void
    {
        $logLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        $success = file_put_contents($this->logPath, $logLine, FILE_APPEND | LOCK_EX);
        
        if ($success === false) {
            error_log("Security audit log write failed: " . $this->logPath);
        }
    }

    /**
     * Write log entry to database
     */
    private function writeToDatabase(array $logEntry): void
    {
        try {
            $query = "INSERT INTO security_audit_log (
                        event_id, timestamp, category, event, severity, user_id, 
                        session_id, ip_address, user_agent, request_uri, request_method, 
                        referer, details, server_info
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $GLOBALS['db']->getConnection()->prepare($query);
            $stmt->execute([
                $logEntry['event_id'],
                $logEntry['timestamp'],
                $logEntry['category'],
                $logEntry['event'],
                $logEntry['severity'],
                $logEntry['user_id'],
                $logEntry['session_id'],
                $logEntry['ip_address'],
                $logEntry['user_agent'],
                $logEntry['request_uri'],
                $logEntry['request_method'],
                $logEntry['referer'],
                json_encode($logEntry['details']),
                json_encode($logEntry['server_info'])
            ]);
            
        } catch (Exception $e) {
            error_log("Security audit database log failed: " . $e->getMessage());
        }
    }

    /**
     * Send critical event alerts
     */
    private function sendCriticalAlert(array $logEntry): void
    {
        // Get alert configuration
        $alertConfig = $this->getAlertConfiguration();
        
        if (!$alertConfig['enabled']) {
            return;
        }
        
        $subject = "Critical Security Event: {$logEntry['event']}";
        $message = $this->formatAlertMessage($logEntry);
        
        // Send email alerts
        if (!empty($alertConfig['email_recipients'])) {
            foreach ($alertConfig['email_recipients'] as $recipient) {
                $this->sendEmailAlert($recipient, $subject, $message);
            }
        }
        
        // Webhook alerts
        if (!empty($alertConfig['webhook_url'])) {
            $this->sendWebhookAlert($alertConfig['webhook_url'], $logEntry);
        }
        
        // SMS alerts for extremely critical events
        if ($this->isExtremelyCritical($logEntry) && !empty($alertConfig['sms_recipients'])) {
            foreach ($alertConfig['sms_recipients'] as $phoneNumber) {
                $this->sendSmsAlert($phoneNumber, $subject);
            }
        }
    }

    /**
     * Check for suspicious patterns
     */
    private function checkSuspiciousPatterns(string $category, string $event, array $details): void
    {
        $ipAddress = $this->getClientIpAddress();
        $timeWindow = 300; // 5 minutes
        
        // Check for brute force attempts
        if ($category === self::CATEGORY_AUTH && $event === 'login_failed') {
            $failedAttempts = $this->getRecentEventCount($category, $event, $timeWindow, ['ip_address' => $ipAddress]);
            
            if ($failedAttempts >= 5) {
                self::logEvent(
                    self::CATEGORY_SYSTEM, 
                    'possible_brute_force_attack', 
                    ['ip_address' => $ipAddress, 'failed_attempts' => $failedAttempts],
                    self::SEVERITY_HIGH
                );
            }
        }
        
        // Check for privilege escalation attempts
        if ($category === self::CATEGORY_ACCESS && strpos($event, 'permission_denied') !== false) {
            $deniedAttempts = $this->getRecentEventCount($category, $event, $timeWindow);
            
            if ($deniedAttempts >= 10) {
                self::logEvent(
                    self::CATEGORY_SYSTEM, 
                    'possible_privilege_escalation', 
                    ['denied_attempts' => $deniedAttempts],
                    self::SEVERITY_HIGH
                );
            }
        }
        
        // Check for data exfiltration patterns
        if ($category === self::CATEGORY_DATA && $event === 'bulk_data_access') {
            $bulkAccess = $this->getRecentEventCount($category, $event, 3600); // 1 hour window
            
            if ($bulkAccess >= 3) {
                self::logEvent(
                    self::CATEGORY_SYSTEM, 
                    'possible_data_exfiltration', 
                    ['bulk_access_count' => $bulkAccess],
                    self::SEVERITY_CRITICAL
                );
            }
        }
    }

    /**
     * Get recent event count for pattern detection
     */
    private function getRecentEventCount(
        string $category, 
        string $event, 
        int $timeWindowSeconds, 
        array $additionalFilters = []
    ): int {
        if (!$this->dbLogging) {
            return 0; // Can't check patterns without database logging
        }
        
        try {
            $conditions = ['category = ?', 'event = ?', 'timestamp >= ?'];
            $params = [$category, $event, date('Y-m-d H:i:s', time() - $timeWindowSeconds)];
            
            foreach ($additionalFilters as $field => $value) {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
            
            $query = "SELECT COUNT(*) as count FROM security_audit_log WHERE " . implode(' AND ', $conditions);
            $stmt = $GLOBALS['db']->getConnection()->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['count'];
            
        } catch (Exception $e) {
            error_log("Pattern detection query failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate unique event ID
     */
    private function generateEventId(): string
    {
        return uniqid('audit_', true);
    }

    /**
     * Get current user ID
     */
    private function getCurrentUserId(): ?string
    {
        global $current_user;
        return $current_user->id ?? null;
    }

    /**
     * Get client IP address
     */
    private function getClientIpAddress(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Direct connection
        ];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get log file path
     */
    private function getLogPath(): string
    {
        $logDir = rtrim($GLOBALS['sugar_config']['log_dir'] ?? 'logs', '/');
        return $logDir . '/' . self::LOG_FILE;
    }

    /**
     * Ensure log directory exists
     */
    private function ensureLogDirectory(): void
    {
        $logDir = dirname($this->logPath);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true);
        }
    }

    /**
     * Check if log rotation is needed
     */
    private function checkLogRotation(): void
    {
        if (file_exists($this->logPath) && filesize($this->logPath) > self::MAX_LOG_SIZE) {
            $this->rotateLog();
        }
        
        $this->cleanupOldLogs();
    }

    /**
     * Rotate log file
     */
    private function rotateLog(): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $archivePath = str_replace('.log', "_{$timestamp}.log", $this->logPath);
        
        if (rename($this->logPath, $archivePath)) {
            // Compress archived log
            if (function_exists('gzencode')) {
                $content = file_get_contents($archivePath);
                file_put_contents($archivePath . '.gz', gzencode($content));
                unlink($archivePath);
            }
        }
    }

    /**
     * Clean up old log files
     */
    private function cleanupOldLogs(): void
    {
        $logDir = dirname($this->logPath);
        $cutoffTime = time() - (self::LOG_RETENTION_DAYS * 24 * 60 * 60);
        
        $files = glob($logDir . '/' . str_replace('.log', '_*.log*', self::LOG_FILE));
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }

    /**
     * Get alert configuration
     */
    private function getAlertConfiguration(): array
    {
        global $sugar_config;
        
        return $sugar_config['security_alerts'] ?? [
            'enabled' => false,
            'email_recipients' => [],
            'webhook_url' => '',
            'sms_recipients' => []
        ];
    }

    /**
     * Format alert message
     */
    private function formatAlertMessage(array $logEntry): string
    {
        return "Critical Security Event Detected\n\n" .
               "Event: {$logEntry['event']}\n" .
               "Category: {$logEntry['category']}\n" .
               "Severity: {$logEntry['severity']}\n" .
               "Time: {$logEntry['timestamp']}\n" .
               "User ID: {$logEntry['user_id']}\n" .
               "IP Address: {$logEntry['ip_address']}\n" .
               "Request: {$logEntry['request_method']} {$logEntry['request_uri']}\n" .
               "Details: " . json_encode($logEntry['details'], JSON_PRETTY_PRINT);
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(string $recipient, string $subject, string $message): void
    {
        // Use SuiteCRM's email system or direct mail
        try {
            mail($recipient, $subject, $message, "From: security@" . $_SERVER['HTTP_HOST']);
        } catch (Exception $e) {
            error_log("Failed to send security alert email: " . $e->getMessage());
        }
    }

    /**
     * Send webhook alert
     */
    private function sendWebhookAlert(string $webhookUrl, array $logEntry): void
    {
        $payload = json_encode($logEntry);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $payload
            ]
        ]);
        
        file_get_contents($webhookUrl, false, $context);
    }

    /**
     * Send SMS alert (placeholder - implement with your SMS provider)
     */
    private function sendSmsAlert(string $phoneNumber, string $message): void
    {
        // Implement SMS sending logic using your preferred provider
        // (Twilio, AWS SNS, etc.)
    }

    /**
     * Check if event is extremely critical
     */
    private function isExtremelyCritical(array $logEntry): bool
    {
        $extremelyCriticalEvents = [
            'system_compromised',
            'mass_data_breach',
            'privilege_escalation_successful',
            'malware_detected'
        ];
        
        return in_array($logEntry['event'], $extremelyCriticalEvents);
    }

    /**
     * Get audit report
     */
    public function getAuditReport(array $filters = []): array
    {
        if (!$this->dbLogging) {
            return ['error' => 'Database logging not enabled'];
        }
        
        $whereConditions = [];
        $params = [];
        
        if (!empty($filters['start_date'])) {
            $whereConditions[] = 'timestamp >= ?';
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $whereConditions[] = 'timestamp <= ?';
            $params[] = $filters['end_date'];
        }
        
        if (!empty($filters['category'])) {
            $whereConditions[] = 'category = ?';
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['severity'])) {
            $whereConditions[] = 'severity = ?';
            $params[] = $filters['severity'];
        }
        
        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
        
        try {
            // Get summary statistics
            $summaryQuery = "SELECT 
                                category, 
                severity, 
                COUNT(*) as count 
                             FROM security_audit_log 
                             {$whereClause} 
                             GROUP BY category, severity";
            
            $stmt = $GLOBALS['db']->getConnection()->prepare($summaryQuery);
            $stmt->execute($params);
            $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get recent events
            $eventsQuery = "SELECT * FROM security_audit_log 
                           {$whereClause} 
                           ORDER BY timestamp DESC 
                           LIMIT 100";
            
            $stmt = $GLOBALS['db']->getConnection()->prepare($eventsQuery);
            $stmt->execute($params);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'summary' => $summary,
                'recent_events' => $events,
                'generated_at' => date('c')
            ];
            
        } catch (Exception $e) {
            return ['error' => 'Failed to generate audit report: ' . $e->getMessage()];
        }
    }
}
