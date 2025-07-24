<?php

/**
 * Secure Password Management with bcrypt hashing
 * Implements modern password security practices
 */
class PasswordManager
{
    // Password policy configuration
    private const MIN_LENGTH = 12;
    private const REQUIRE_UPPERCASE = true;
    private const REQUIRE_LOWERCASE = true;
    private const REQUIRE_NUMBERS = true;
    private const REQUIRE_SPECIAL_CHARS = true;
    private const MAX_PASSWORD_AGE_DAYS = 90;
    
    // bcrypt configuration
    private const BCRYPT_COST = 12; // Adjust based on server performance
    
    // Password history settings
    private const PASSWORD_HISTORY_COUNT = 5;
    
    private static $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Hash password using bcrypt
     */
    public function hashPassword(string $password): string
    {
        $options = [
            'cost' => self::BCRYPT_COST,
        ];
        
        $hash = password_hash($password, PASSWORD_BCRYPT, $options);
        
        if ($hash === false) {
            throw new Exception('Password hashing failed');
        }
        
        return $hash;
    }

    /**
     * Verify password against hash
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehashing (due to cost changes)
     */
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);
    }

    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];

        // Check minimum length
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = "Password must be at least " . self::MIN_LENGTH . " characters long";
        }

        // Check for uppercase letters
        if (self::REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        // Check for lowercase letters
        if (self::REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        // Check for numbers
        if (self::REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        // Check for special characters
        if (self::REQUIRE_SPECIAL_CHARS && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        // Check for common patterns
        $commonPatterns = $this->getCommonPatterns();
        foreach ($commonPatterns as $pattern => $message) {
            if (preg_match($pattern, $password)) {
                $errors[] = $message;
            }
        }

        // Check against common passwords
        if ($this->isCommonPassword($password)) {
            $errors[] = "Password is too common. Please choose a more unique password";
        }

        // Calculate strength score
        $score = $this->calculatePasswordScore($password);

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'score' => $score,
            'strength' => $this->getStrengthLabel($score)
        ];
    }

    /**
     * Generate secure random password
     */
    public function generateSecurePassword(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        
        // Ensure at least one character from each required set
        if (self::REQUIRE_UPPERCASE) {
            $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        }
        if (self::REQUIRE_LOWERCASE) {
            $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        }
        if (self::REQUIRE_NUMBERS) {
            $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        }
        if (self::REQUIRE_SPECIAL_CHARS) {
            $password .= $special[random_int(0, strlen($special) - 1)];
        }
        
        // Fill the rest with random characters from all sets
        $allChars = $uppercase . $lowercase . $numbers . $special;
        $remainingLength = $length - strlen($password);
        
        for ($i = 0; $i < $remainingLength; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password to avoid predictable patterns
        return str_shuffle($password);
    }

    /**
     * Update user password with history tracking
     */
    public function updateUserPassword(string $userId, string $newPassword): bool
    {
        // Validate new password
        $validation = $this->validatePasswordStrength($newPassword);
        if (!$validation['is_valid']) {
            throw new InvalidArgumentException('Password does not meet security requirements: ' . implode(', ', $validation['errors']));
        }

        // Check password history
        if ($this->isPasswordInHistory($userId, $newPassword)) {
            throw new InvalidArgumentException("Cannot reuse one of your last " . self::PASSWORD_HISTORY_COUNT . " passwords");
        }

        // Hash the new password
        $hashedPassword = $this->hashPassword($newPassword);

        try {
            // Begin transaction
            $db = DBManagerFactory::getInstance();
            $db->transactionStart();

            // Update user password
            $query = "UPDATE users SET user_hash = ?, pwd_last_changed = NOW() WHERE id = ?";
            $stmt = $db->getConnection()->prepare($query);
            $stmt->execute([$hashedPassword, $userId]);

            // Add to password history
            $this->addToPasswordHistory($userId, $hashedPassword);

            // Clear password reset tokens
            $this->clearPasswordResetTokens($userId);

            // Log the password change
            SecurityAudit::logEvent('password_changed', 'User password changed', [
                'user_id' => $userId,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            $db->transactionCommit();
            return true;

        } catch (Exception $e) {
            $db->transactionRollback();
            $GLOBALS['log']->error("Password update failed for user {$userId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if password is expired
     */
    public function isPasswordExpired(string $userId): bool
    {
        $query = "SELECT pwd_last_changed FROM users WHERE id = ?";
        $result = $GLOBALS['db']->getConnection()->prepare($query);
        $result->execute([$userId]);
        
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (!$row || !$row['pwd_last_changed']) {
            return true; // Force change if no date recorded
        }

        $lastChanged = strtotime($row['pwd_last_changed']);
        $expirationDate = $lastChanged + (self::MAX_PASSWORD_AGE_DAYS * 24 * 60 * 60);
        
        return time() > $expirationDate;
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken(string $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour expiration

        // Store token in database
        $query = "INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())
                  ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = NOW()";
        
        $stmt = $GLOBALS['db']->getConnection()->prepare($query);
        $stmt->execute([$userId, hash('sha256', $token), $expiresAt]);

        // Log token generation
        SecurityAudit::logEvent('password_reset_token_generated', 'Password reset token generated', [
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        return $token;
    }

    /**
     * Validate password reset token
     */
    public function validatePasswordResetToken(string $token): ?string
    {
        $hashedToken = hash('sha256', $token);
        
        $query = "SELECT user_id FROM password_reset_tokens 
                  WHERE token = ? AND expires_at > NOW() AND used_at IS NULL";
        
        $stmt = $GLOBALS['db']->getConnection()->prepare($query);
        $stmt->execute([$hashedToken]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Mark token as used
            $updateQuery = "UPDATE password_reset_tokens SET used_at = NOW() WHERE token = ?";
            $updateStmt = $GLOBALS['db']->getConnection()->prepare($updateQuery);
            $updateStmt->execute([$hashedToken]);
            
            return $result['user_id'];
        }
        
        return null;
    }

    /**
     * Calculate password strength score (0-100)
     */
    private function calculatePasswordScore(string $password): int
    {
        $score = 0;
        $length = strlen($password);
        
        // Length scoring
        if ($length >= 8) $score += 10;
        if ($length >= 12) $score += 15;
        if ($length >= 16) $score += 10;
        if ($length >= 20) $score += 5;
        
        // Character variety scoring
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 15;
        
        // Bonus for character variety
        $charTypes = 0;
        if (preg_match('/[a-z]/', $password)) $charTypes++;
        if (preg_match('/[A-Z]/', $password)) $charTypes++;
        if (preg_match('/[0-9]/', $password)) $charTypes++;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $charTypes++;
        
        if ($charTypes >= 3) $score += 10;
        if ($charTypes >= 4) $score += 10;
        
        // Penalty for common patterns
        if (preg_match('/(.)\1{2,}/', $password)) $score -= 10; // Repeated characters
        if (preg_match('/123|abc|qwe/i', $password)) $score -= 15; // Sequential characters
        
        return max(0, min(100, $score));
    }

    /**
     * Get strength label based on score
     */
    private function getStrengthLabel(int $score): string
    {
        if ($score < 30) return 'Very Weak';
        if ($score < 50) return 'Weak';
        if ($score < 70) return 'Fair';
        if ($score < 85) return 'Good';
        return 'Strong';
    }

    /**
     * Get common password patterns to avoid
     */
    private function getCommonPatterns(): array
    {
        return [
            '/(.)\1{3,}/' => 'Password cannot contain more than 3 repeated characters',
            '/^(password|123456|qwerty)/i' => 'Password cannot start with common sequences',
            '/^\d+$/' => 'Password cannot be only numbers',
            '/^[a-zA-Z]+$/' => 'Password cannot be only letters'
        ];
    }

    /**
     * Check if password is in common passwords list
     */
    private function isCommonPassword(string $password): bool
    {
        // In production, this would check against a comprehensive list
        $commonPasswords = [
            'password', '123456', 'password123', 'admin', 'letmein',
            'welcome', 'monkey', '1234567890', 'qwerty', 'abc123'
        ];
        
        return in_array(strtolower($password), array_map('strtolower', $commonPasswords));
    }

    /**
     * Check if password exists in user's history
     */
    private function isPasswordInHistory(string $userId, string $password): bool
    {
        $query = "SELECT password_hash FROM password_history 
                  WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        
        $stmt = $GLOBALS['db']->getConnection()->prepare($query);
        $stmt->execute([$userId, self::PASSWORD_HISTORY_COUNT]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($this->verifyPassword($password, $row['password_hash'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Add password to history
     */
    private function addToPasswordHistory(string $userId, string $hashedPassword): void
    {
        // Add new password to history
        $insertQuery = "INSERT INTO password_history (user_id, password_hash, created_at) VALUES (?, ?, NOW())";
        $stmt = $GLOBALS['db']->getConnection()->prepare($insertQuery);
        $stmt->execute([$userId, $hashedPassword]);

        // Clean up old history entries
        $cleanupQuery = "DELETE FROM password_history WHERE user_id = ? 
                        AND id NOT IN (
                            SELECT id FROM (
                                SELECT id FROM password_history 
                                WHERE user_id = ? 
                                ORDER BY created_at DESC 
                                LIMIT ?
                            ) AS recent
                        )";
        
        $stmt = $GLOBALS['db']->getConnection()->prepare($cleanupQuery);
        $stmt->execute([$userId, $userId, self::PASSWORD_HISTORY_COUNT]);
    }

    /**
     * Clear password reset tokens for user
     */
    private function clearPasswordResetTokens(string $userId): void
    {
        $query = "DELETE FROM password_reset_tokens WHERE user_id = ?";
        $stmt = $GLOBALS['db']->getConnection()->prepare($query);
        $stmt->execute([$userId]);
    }

    /**
     * Get password policy information
     */
    public function getPasswordPolicy(): array
    {
        return [
            'min_length' => self::MIN_LENGTH,
            'require_uppercase' => self::REQUIRE_UPPERCASE,
            'require_lowercase' => self::REQUIRE_LOWERCASE,
            'require_numbers' => self::REQUIRE_NUMBERS,
            'require_special_chars' => self::REQUIRE_SPECIAL_CHARS,
            'max_age_days' => self::MAX_PASSWORD_AGE_DAYS,
            'history_count' => self::PASSWORD_HISTORY_COUNT
        ];
    }
}
