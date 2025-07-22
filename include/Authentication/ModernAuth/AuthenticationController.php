<?php
/**
 * SuiteCRM Modern Authentication Controller
 * 
 * Main controller for handling all authentication methods
 */

namespace SuiteCRM\Authentication\ModernAuth;

use SuiteCRM\Authentication\OAuth\OAuthProviders;
use SuiteCRM\Authentication\TwoFactor\TwoFactorAuth;
use SuiteCRM\Authentication\Security\PasswordPolicy;
use SuiteCRM\Authentication\Security\SecurityHeaders;
use Exception;

class AuthenticationController
{
    private JWTManager $jwtManager;
    private OAuthProviders $oauthProviders;
    private TwoFactorAuth $twoFactorAuth;
    private PasswordPolicy $passwordPolicy;
    private SecurityHeaders $security;
    
    public function __construct()
    {
        $this->jwtManager = new JWTManager();
        $this->oauthProviders = new OAuthProviders();
        $this->twoFactorAuth = new TwoFactorAuth();
        $this->passwordPolicy = new PasswordPolicy();
        $this->security = new SecurityHeaders();
    }
    
    /**
     * Authenticate user with username/password
     */
    public function authenticatePassword(string $username, string $password): array
    {
        global $db;
        
        // Rate limiting check
        if (!$this->security->checkRateLimit('login_' . $username, 5, 900)) {
            throw new Exception('Too many login attempts. Please try again later.');
        }
        
        // Check account lockout
        if ($this->isAccountLocked($username)) {
            throw new Exception('Account is temporarily locked due to multiple failed attempts.');
        }
        
        // Find user
        $user = $this->findUserByUsername($username);
        if (!$user) {
            $this->recordFailedAttempt($username);
            throw new Exception('Invalid username or password.');
        }
        
        // Verify password
        if (!password_verify($password, $user['user_hash'])) {
            $this->recordFailedAttempt($username, $user['id']);
            throw new Exception('Invalid username or password.');
        }
        
        // Check user status
        if ($user['status'] !== 'Active') {
            throw new Exception('User account is not active.');
        }
        
        // Check password expiry
        if ($this->passwordPolicy->isPasswordExpired($user['id'])) {
            return [
                'status' => 'password_expired',
                'user_id' => $user['id'],
                'message' => 'Your password has expired. Please change it.'
            ];
        }
        
        // Check if user needs to change password
        if ($user['force_password_change']) {
            return [
                'status' => 'password_change_required',
                'user_id' => $user['id'],
                'message' => 'You must change your password before continuing.'
            ];
        }
        
        // Check 2FA requirement
        if ($this->twoFactorAuth->isTwoFactorEnabled($user['id'])) {
            // Store user ID in session for 2FA verification
            $_SESSION['pending_2fa_user_id'] = $user['id'];
            $_SESSION['pending_2fa_auth_method'] = 'password';
            
            return [
                'status' => '2fa_required',
                'user_id' => $user['id'],
                'message' => 'Please enter your 2FA code.'
            ];
        }
        
        // Successful authentication
        return $this->completeAuthentication($user['id'], 'password');
    }
    
    /**
     * Verify 2FA code and complete authentication
     */
    public function verify2FA(string $code): array
    {
        if (!isset($_SESSION['pending_2fa_user_id'])) {
            throw new Exception('No pending 2FA verification.');
        }
        
        $userId = $_SESSION['pending_2fa_user_id'];
        $authMethod = $_SESSION['pending_2fa_auth_method'] ?? 'password';
        
        // Verify 2FA code
        if (!$this->twoFactorAuth->verifyUserCode($userId, $code)) {
            $this->recordFailedAttempt('', $userId);
            throw new Exception('Invalid 2FA code.');
        }
        
        // Clear 2FA session data
        unset($_SESSION['pending_2fa_user_id']);
        unset($_SESSION['pending_2fa_auth_method']);
        
        // Complete authentication
        return $this->completeAuthentication($userId, $authMethod);
    }
    
    /**
     * Initiate OAuth authentication
     */
    public function initiateOAuth(string $provider): string
    {
        if (!$this->oauthProviders->isProviderConfigured($provider)) {
            throw new Exception('OAuth provider not configured.');
        }
        
        // Store redirect URL if provided
        if (!empty($_GET['redirect_after_login'])) {
            $_SESSION['oauth_redirect_after_login'] = $_GET['redirect_after_login'];
        }
        
        return $this->oauthProviders->getAuthorizationUrl($provider);
    }
    
    /**
     * Change user password
     */
    public function changePassword(string $userId, string $currentPassword, string $newPassword): array
    {
        global $db;
        
        // Get current user
        $user = BeanFactory::getBean('Users', $userId);
        if (!$user) {
            throw new Exception('User not found.');
        }
        
        // Verify current password (unless force change)
        if (!$user->force_password_change && !password_verify($currentPassword, $user->user_hash)) {
            throw new Exception('Current password is incorrect.');
        }
        
        // Validate new password
        $validation = $this->passwordPolicy->validatePassword($newPassword, [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'user_name' => $user->user_name,
            'email' => $user->email_address
        ]);
        
        if (!$validation['valid']) {
            throw new Exception('Password validation failed: ' . implode(', ', $validation['errors']));
        }
        
        // Check password history
        if (!$this->passwordPolicy->checkPasswordHistory($userId, $newPassword)) {
            throw new Exception('Password has been used recently. Please choose a different password.');
        }
        
        // Update password
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE users SET 
                        user_hash = ?, 
                        password_changed_at = ?, 
                        force_password_change = 0,
                        failed_login_attempts = 0,
                        account_locked_until = NULL,
                        date_modified = ?
                        WHERE id = ?";
        
        $now = date('Y-m-d H:i:s');
        $updateQuery = str_replace(['?', '?', '?'], 
                                  [$db->quoted($newHash), $db->quoted($now), $db->quoted($userId)], 
                                  $updateQuery);
        $db->query($updateQuery);
        
        // Store in password history
        $this->passwordPolicy->storePasswordHistory($userId, $newHash);
        
        // Log password change
        $this->logAuthEvent($userId, 'password_changed', 'password');
        
        return [
            'status' => 'success',
            'message' => 'Password changed successfully.'
        ];
    }
    
    /**
     * Enable 2FA for user
     */
    public function enable2FA(string $userId, string $code): array
    {
        $user = BeanFactory::getBean('Users', $userId);
        if (!$user) {
            throw new Exception('User not found.');
        }
        
        // Get secret key from session
        if (!isset($_SESSION['2fa_setup_secret'])) {
            throw new Exception('No 2FA setup in progress.');
        }
        
        $secretKey = $_SESSION['2fa_setup_secret'];
        
        // Verify setup code
        if (!$this->twoFactorAuth->verifyCode($secretKey, $code)) {
            throw new Exception('Invalid verification code.');
        }
        
        // Enable 2FA
        if (!$this->twoFactorAuth->enableTwoFactor($userId, $secretKey)) {
            throw new Exception('Failed to enable 2FA.');
        }
        
        // Update user record
        global $db;
        $now = date('Y-m-d H:i:s');
        $query = "UPDATE users SET two_factor_enabled = 1, date_modified = " . $db->quoted($now) . " WHERE id = " . $db->quoted($userId);
        $db->query($query);
        
        // Clear session
        unset($_SESSION['2fa_setup_secret']);
        
        // Get backup codes
        $backupCodes = $_SESSION['new_backup_codes'] ?? [];
        unset($_SESSION['new_backup_codes']);
        
        // Log 2FA enablement
        $this->logAuthEvent($userId, '2fa_enabled', 'password');
        
        return [
            'status' => 'success',
            'message' => '2FA enabled successfully.',
            'backup_codes' => $backupCodes
        ];
    }
    
    /**
     * Setup 2FA for user
     */
    public function setup2FA(string $userId): array
    {
        $user = BeanFactory::getBean('Users', $userId);
        if (!$user) {
            throw new Exception('User not found.');
        }
        
        // Generate secret key
        $secretKey = $this->twoFactorAuth->generateSecretKey();
        
        // Store in session temporarily
        $_SESSION['2fa_setup_secret'] = $secretKey;
        
        // Generate QR code
        $qrCode = $this->twoFactorAuth->generateQRCode($user->email_address, $secretKey);
        
        return [
            'secret_key' => $secretKey,
            'qr_code' => $qrCode,
            'manual_entry_key' => chunk_split($secretKey, 4, ' ')
        ];
    }
    
    /**
     * Logout user
     */
    public function logout(string $userId = null): void
    {
        global $current_user;
        
        $userId = $userId ?? $current_user->id ?? null;
        
        if ($userId) {
            // Revoke refresh tokens
            $this->jwtManager->revokeRefreshToken($userId);
            
            // Log logout
            $this->logAuthEvent($userId, 'logout', 'password');
        }
        
        // Clear JWT cookies
        $this->jwtManager->clearTokenCookies();
        
        // Clear session
        session_destroy();
    }
    
    /**
     * Complete authentication process
     */
    private function completeAuthentication(string $userId, string $authMethod): array
    {
        global $db, $current_user;
        
        // Load user
        $current_user = BeanFactory::getBean('Users', $userId);
        
        // Reset failed attempts
        $this->resetFailedAttempts($userId);
        
        // Update last login
        $updateQuery = "UPDATE users SET 
                        last_login_at = ?, 
                        failed_login_attempts = 0,
                        account_locked_until = NULL,
                        date_modified = ?
                        WHERE id = ?";
        
        $now = date('Y-m-d H:i:s');
        $updateQuery = str_replace('?', $db->quoted($now), $updateQuery);
        $updateQuery = str_replace('?', $db->quoted($userId), $updateQuery);
        $db->query($updateQuery);
        
        // Generate JWT tokens
        $tokens = $this->jwtManager->generateTokens($userId, [
            'user_name' => $current_user->user_name,
            'email' => $current_user->email_address,
            'name' => $current_user->full_name,
            'auth_method' => $authMethod
        ]);
        
        // Set secure cookies
        $this->jwtManager->setTokenCookie($tokens['access_token'], $tokens['refresh_token']);
        
        // Set session variables for compatibility
        $_SESSION['authenticated_user_id'] = $userId;
        $_SESSION['unique_key'] = $GLOBALS['sugar_config']['unique_key'];
        $_SESSION['user_unique_key'] = $current_user->getPreference('unique_key');
        
        // Log successful login
        $this->logAuthEvent($userId, 'login_success', $authMethod);
        
        return [
            'status' => 'success',
            'user_id' => $userId,
            'tokens' => $tokens,
            'user' => [
                'id' => $current_user->id,
                'user_name' => $current_user->user_name,
                'full_name' => $current_user->full_name,
                'email' => $current_user->email_address
            ]
        ];
    }
    
    /**
     * Find user by username
     */
    private function findUserByUsername(string $username): ?array
    {
        global $db;
        
        $query = "SELECT id, user_name, user_hash, status, employee_status, 
                         force_password_change, failed_login_attempts, account_locked_until
                  FROM users 
                  WHERE (user_name = ? OR email_address = ?) AND deleted = 0";
        
        $query = str_replace('?', $db->quoted($username), $query);
        $query = str_replace('?', $db->quoted($username), $query);
        $result = $db->query($query);
        
        return $result ? $db->fetchByAssoc($result) : null;
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked(string $username): bool
    {
        global $db;
        
        $query = "SELECT account_locked_until FROM users 
                  WHERE (user_name = " . $db->quoted($username) . " OR email_address = " . $db->quoted($username) . ") AND deleted = 0";
        
        $result = $db->query($query);
        $row = $result ? $db->fetchByAssoc($result) : null;
        
        if ($row && $row['account_locked_until']) {
            return strtotime($row['account_locked_until']) > time();
        }
        
        return false;
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedAttempt(string $username, string $userId = null): void
    {
        global $db;
        
        if ($userId) {
            // Update user's failed attempts
            $query = "UPDATE users SET 
                      failed_login_attempts = failed_login_attempts + 1,
                      date_modified = ?
                      WHERE id = ?";
            
            $now = date('Y-m-d H:i:s');
            $query = str_replace('?', $db->quoted($now), $query);
            $query = str_replace('?', $db->quoted($userId), $query);
            $db->query($query);
            
            // Check if we need to lock the account
            $checkQuery = "SELECT failed_login_attempts FROM users WHERE id = ?";
            $checkQuery = str_replace('?', $db->quoted($userId), $checkQuery);
            $checkResult = $db->query($checkQuery);
            $result = $checkResult ? $db->fetchByAssoc($checkResult) : null;
            
            $maxAttempts = $this->passwordPolicy->getConfiguration()['account_lockout_attempts'];
            if ($result && $result['failed_login_attempts'] >= $maxAttempts) {
                $lockoutDuration = $this->passwordPolicy->getConfiguration()['account_lockout_duration'];
                $lockUntil = date('Y-m-d H:i:s', time() + ($lockoutDuration * 60));
                
                $lockQuery = "UPDATE users SET account_locked_until = ? WHERE id = ?";
                $lockQuery = str_replace('?', $db->quoted($lockUntil), $lockQuery);
                $lockQuery = str_replace('?', $db->quoted($userId), $lockQuery);
                $db->query($lockQuery);
                
                // Log account lockout
                $this->logAuthEvent($userId, 'account_locked', 'password');
            }
        }
        
        // Log failed attempt
        $this->logAuthEvent($userId, 'login_failed', 'password', ['username' => $username]);
    }
    
    /**
     * Reset failed login attempts
     */
    private function resetFailedAttempts(string $userId): void
    {
        global $db;
        
        $query = "UPDATE users SET 
                  failed_login_attempts = 0,
                  account_locked_until = NULL,
                  date_modified = ?
                  WHERE id = ?";
        
        $now = date('Y-m-d H:i:s');
        $resetQuery = str_replace('?', $db->quoted($now), $resetQuery);
        $resetQuery = str_replace('?', $db->quoted($userId), $resetQuery);
        $db->query($resetQuery);
    }
    
    /**
     * Log authentication event
     */
    private function logAuthEvent(string $userId = null, string $eventType, string $authMethod, array $details = []): void
    {
        global $db;
        
        $username = '';
        if ($userId) {
            $user = BeanFactory::getBean('Users', $userId);
            $username = $user->user_name ?? '';
        }
        
        $query = "INSERT INTO auth_audit_log 
                  (user_id, username, event_type, auth_method, ip_address, user_agent, session_id, details, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $query = str_replace(
            ['?', '?', '?', '?', '?', '?', '?', '?', '?'],
            [
                $db->quoted($userId),
                $db->quoted($username),
                $db->quoted($eventType),
                $db->quoted($authMethod),
                $db->quoted($this->security->getClientIP()),
                $db->quoted($_SERVER['HTTP_USER_AGENT'] ?? ''),
                $db->quoted(session_id()),
                $db->quoted(json_encode($details)),
                $db->quoted(date('Y-m-d H:i:s'))
            ],
            $query
        );
        $db->query($query);
    }
}
