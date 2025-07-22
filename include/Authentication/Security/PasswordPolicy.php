<?php
/**
 * SuiteCRM Modern Authentication - Enhanced Password Policy
 * 
 * Implements stronger password requirements and validation
 */

namespace SuiteCRM\Authentication\Security;

class PasswordPolicy
{
    private array $config;
    
    public function __construct()
    {
        global $sugar_config;
        
        // Default password policy configuration
        $this->config = array_merge([
            'min_length' => 12,
            'max_length' => 128,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_special_chars' => true,
            'min_special_chars' => 1,
            'min_numbers' => 1,
            'min_uppercase' => 1,
            'min_lowercase' => 1,
            'prevent_common_passwords' => true,
            'prevent_personal_info' => true,
            'prevent_dictionary_words' => true,
            'password_history_count' => 5,
            'password_expiry_days' => 90,
            'force_change_on_first_login' => true,
            'account_lockout_attempts' => 5,
            'account_lockout_duration' => 30, // minutes
            'allowed_special_chars' => '!@#$%^&*()_+-=[]{}|;:,.<>?'
        ], $sugar_config['password_policy'] ?? []);
    }
    
    /**
     * Validate password against policy
     */
    public function validatePassword(string $password, array $userInfo = []): array
    {
        $errors = [];
        
        // Length validation
        if (strlen($password) < $this->config['min_length']) {
            $errors[] = "Password must be at least {$this->config['min_length']} characters long";
        }
        
        if (strlen($password) > $this->config['max_length']) {
            $errors[] = "Password cannot exceed {$this->config['max_length']} characters";
        }
        
        // Character requirement validation
        if ($this->config['require_uppercase']) {
            $uppercaseCount = preg_match_all('/[A-Z]/', $password);
            if ($uppercaseCount < $this->config['min_uppercase']) {
                $errors[] = "Password must contain at least {$this->config['min_uppercase']} uppercase letter(s)";
            }
        }
        
        if ($this->config['require_lowercase']) {
            $lowercaseCount = preg_match_all('/[a-z]/', $password);
            if ($lowercaseCount < $this->config['min_lowercase']) {
                $errors[] = "Password must contain at least {$this->config['min_lowercase']} lowercase letter(s)";
            }
        }
        
        if ($this->config['require_numbers']) {
            $numberCount = preg_match_all('/[0-9]/', $password);
            if ($numberCount < $this->config['min_numbers']) {
                $errors[] = "Password must contain at least {$this->config['min_numbers']} number(s)";
            }
        }
        
        if ($this->config['require_special_chars']) {
            $specialChars = preg_quote($this->config['allowed_special_chars'], '/');
            $specialCount = preg_match_all('/[' . $specialChars . ']/', $password);
            if ($specialCount < $this->config['min_special_chars']) {
                $errors[] = "Password must contain at least {$this->config['min_special_chars']} special character(s)";
            }
        }
        
        // Common password validation
        if ($this->config['prevent_common_passwords'] && $this->isCommonPassword($password)) {
            $errors[] = "Password is too common. Please choose a more unique password";
        }
        
        // Personal information validation
        if ($this->config['prevent_personal_info'] && !empty($userInfo)) {
            if ($this->containsPersonalInfo($password, $userInfo)) {
                $errors[] = "Password cannot contain personal information";
            }
        }
        
        // Dictionary word validation
        if ($this->config['prevent_dictionary_words'] && $this->isDictionaryWord($password)) {
            $errors[] = "Password cannot be a common dictionary word";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'strength' => $this->calculatePasswordStrength($password)
        ];
    }
    
    /**
     * Check password against history
     */
    public function checkPasswordHistory(string $userId, string $newPassword): bool
    {
        global $db;
        
        $query = "SELECT password_hash FROM user_password_history 
                  WHERE user_id = ? 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId, $this->config['password_history_count']]);
        
        while ($row = $stmt->fetch()) {
            if (password_verify($newPassword, $row['password_hash'])) {
                return false; // Password found in history
            }
        }
        
        return true;
    }
    
    /**
     * Store password in history
     */
    public function storePasswordHistory(string $userId, string $passwordHash): void
    {
        global $db;
        
        // Insert new password hash
        $insertQuery = "INSERT INTO user_password_history (user_id, password_hash, created_at) 
                        VALUES (?, ?, ?)";
        $insertStmt = $db->getConnection()->prepare($insertQuery);
        $insertStmt->execute([$userId, $passwordHash, date('Y-m-d H:i:s')]);
        
        // Clean up old history entries
        $cleanupQuery = "DELETE FROM user_password_history 
                        WHERE user_id = ? 
                        AND id NOT IN (
                            SELECT id FROM (
                                SELECT id FROM user_password_history 
                                WHERE user_id = ? 
                                ORDER BY created_at DESC 
                                LIMIT ?
                            ) AS recent
                        )";
        $cleanupStmt = $db->getConnection()->prepare($cleanupQuery);
        $cleanupStmt->execute([$userId, $userId, $this->config['password_history_count']]);
    }
    
    /**
     * Check if password needs to be changed due to expiry
     */
    public function isPasswordExpired(string $userId): bool
    {
        global $db;
        
        if ($this->config['password_expiry_days'] === 0) {
            return false; // Password expiry disabled
        }
        
        $query = "SELECT password_changed_at FROM users WHERE id = ?";
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        $result = $stmt->fetch();
        if (!$result || !$result['password_changed_at']) {
            return true; // No password change date, force change
        }
        
        $passwordAge = (time() - strtotime($result['password_changed_at'])) / (24 * 60 * 60);
        return $passwordAge > $this->config['password_expiry_days'];
    }
    
    /**
     * Generate secure password suggestion
     */
    public function generateSecurePassword(int $length = null): string
    {
        $length = $length ?? max($this->config['min_length'], 16);
        
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = $this->config['allowed_special_chars'];
        
        $password = '';
        
        // Ensure minimum character requirements
        if ($this->config['require_uppercase']) {
            $password .= $this->getRandomChars($uppercase, $this->config['min_uppercase']);
        }
        if ($this->config['require_lowercase']) {
            $password .= $this->getRandomChars($lowercase, $this->config['min_lowercase']);
        }
        if ($this->config['require_numbers']) {
            $password .= $this->getRandomChars($numbers, $this->config['min_numbers']);
        }
        if ($this->config['require_special_chars']) {
            $password .= $this->getRandomChars($special, $this->config['min_special_chars']);
        }
        
        // Fill remaining length with random characters
        $allChars = $uppercase . $lowercase . $numbers . $special;
        $remainingLength = $length - strlen($password);
        $password .= $this->getRandomChars($allChars, $remainingLength);
        
        // Shuffle the password
        return str_shuffle($password);
    }
    
    /**
     * Calculate password strength score (0-100)
     */
    public function calculatePasswordStrength(string $password): int
    {
        $score = 0;
        $length = strlen($password);
        
        // Length score (max 25 points)
        $score += min(25, $length * 2);
        
        // Character variety score (max 40 points)
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 10;
        
        // Pattern analysis (max 35 points)
        $patterns = [
            '/(.)\1{2,}/' => -10, // Repeated characters
            '/(?:abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz)/i' => -5, // Sequential letters
            '/(?:123|234|345|456|567|678|789|890)/' => -5, // Sequential numbers
            '/(?:qwerty|asdf|zxcv|password|123456|admin)/i' => -15, // Common patterns
        ];
        
        foreach ($patterns as $pattern => $penalty) {
            if (preg_match($pattern, $password)) {
                $score += $penalty;
            }
        }
        
        // Bonus for length
        if ($length >= 16) $score += 10;
        if ($length >= 20) $score += 5;
        
        return max(0, min(100, $score));
    }
    
    /**
     * Get password policy configuration
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }
    
    /**
     * Check if password is in common passwords list
     */
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123', 
            'password123', '111111', '123123', 'admin', 'welcome',
            'letmein', 'monkey', 'dragon', 'master', 'sunshine',
            'princess', 'football', 'baseball', 'superman', 'batman'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    /**
     * Check if password contains personal information
     */
    private function containsPersonalInfo(string $password, array $userInfo): bool
    {
        $password = strtolower($password);
        
        $personalFields = ['first_name', 'last_name', 'user_name', 'email'];
        
        foreach ($personalFields as $field) {
            if (!empty($userInfo[$field])) {
                $value = strtolower($userInfo[$field]);
                if (strlen($value) >= 3 && strpos($password, $value) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if password is a dictionary word
     */
    private function isDictionaryWord(string $password): bool
    {
        // Simple dictionary check - in production, use a proper dictionary file
        $commonWords = [
            'computer', 'internet', 'security', 'system', 'network',
            'website', 'company', 'business', 'service', 'product',
            'account', 'profile', 'settings', 'manager', 'support'
        ];
        
        return in_array(strtolower($password), $commonWords);
    }
    
    /**
     * Get random characters from charset
     */
    private function getRandomChars(string $charset, int $count): string
    {
        $result = '';
        $max = strlen($charset) - 1;
        
        for ($i = 0; $i < $count; $i++) {
            $result .= $charset[random_int(0, $max)];
        }
        
        return $result;
    }
}
