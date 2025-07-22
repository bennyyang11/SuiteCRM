<?php
/**
 * SuiteCRM OAuth 2.0 Callback Handler
 * 
 * Handles OAuth callbacks from Google, Microsoft, and GitHub
 */

if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

require_once 'include/entryPoint.php';

use SuiteCRM\Authentication\OAuth\OAuthProviders;
use SuiteCRM\Authentication\ModernAuth\JWTManager;
use SuiteCRM\Authentication\Security\SecurityHeaders;

global $current_user, $sugar_config, $db;

// Initialize security
$security = new SecurityHeaders();
$security->applyHeaders();

try {
    // Get provider and parameters
    $provider = $_GET['provider'] ?? '';
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $error = $_GET['error'] ?? '';
    
    if ($error) {
        throw new Exception('OAuth error: ' . $error);
    }
    
    if (empty($provider) || empty($code) || empty($state)) {
        throw new Exception('Missing required OAuth parameters');
    }
    
    $oauthProviders = new OAuthProviders();
    
    // Handle OAuth callback
    $userData = $oauthProviders->handleCallback($provider, $code, $state);
    
    if (!$userData) {
        throw new Exception('Failed to retrieve user data from OAuth provider');
    }
    
    // Check if user exists by email
    $userQuery = "SELECT id, user_name, status, employee_status 
                  FROM users 
                  WHERE email_address = ? AND deleted = 0";
    $userStmt = $db->getConnection()->prepare($userQuery);
    $userStmt->execute([$userData['email']]);
    $existingUser = $userStmt->fetch();
    
    if ($existingUser) {
        // User exists - link OAuth account
        $userId = $existingUser['id'];
        
        // Check if user is active
        if ($existingUser['status'] !== 'Active') {
            throw new Exception('User account is not active');
        }
        
        // Link OAuth account
        $linkQuery = "INSERT INTO user_oauth_accounts 
                      (user_id, provider, provider_user_id, email, name, picture, linked_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?) 
                      ON DUPLICATE KEY UPDATE 
                      email = VALUES(email),
                      name = VALUES(name),
                      picture = VALUES(picture),
                      linked_at = VALUES(linked_at)";
        
        $linkStmt = $db->getConnection()->prepare($linkQuery);
        $linkStmt->execute([
            $userId,
            $userData['provider'],
            $userData['id'],
            $userData['email'],
            $userData['name'],
            $userData['picture'],
            date('Y-m-d H:i:s')
        ]);
        
    } else {
        // Create new user account
        $userId = create_guid();
        $userName = generateUniqueUsername($userData['email']);
        
        $insertQuery = "INSERT INTO users 
                        (id, user_name, first_name, last_name, email_address, status, 
                         employee_status, is_admin, created_by, date_entered, date_modified) 
                        VALUES (?, ?, ?, ?, ?, 'Active', 'Active', 0, '1', ?, ?)";
        
        $now = date('Y-m-d H:i:s');
        $insertStmt = $db->getConnection()->prepare($insertQuery);
        $insertStmt->execute([
            $userId,
            $userName,
            $userData['first_name'],
            $userData['last_name'],
            $userData['email'],
            $now,
            $now
        ]);
        
        // Link OAuth account
        $linkQuery = "INSERT INTO user_oauth_accounts 
                      (user_id, provider, provider_user_id, email, name, picture, linked_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $linkStmt = $db->getConnection()->prepare($linkQuery);
        $linkStmt->execute([
            $userId,
            $userData['provider'],
            $userData['id'],
            $userData['email'],
            $userData['name'],
            $userData['picture'],
            date('Y-m-d H:i:s')
        ]);
        
        // Log new user creation
        $security->logSecurityEvent('user_created_via_oauth', [
            'user_id' => $userId,
            'provider' => $userData['provider'],
            'email' => $userData['email']
        ]);
    }
    
    // Load user object
    $current_user = BeanFactory::getBean('Users', $userId);
    
    if (!$current_user) {
        throw new Exception('Failed to load user account');
    }
    
    // Update last login
    $updateQuery = "UPDATE users SET last_login_at = ?, date_modified = ? WHERE id = ?";
    $updateStmt = $db->getConnection()->prepare($updateQuery);
    $updateStmt->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $userId]);
    
    // Generate JWT tokens
    $jwtManager = new JWTManager();
    $tokens = $jwtManager->generateTokens($userId, [
        'user_name' => $current_user->user_name,
        'email' => $current_user->email_address,
        'name' => $current_user->full_name,
        'auth_method' => 'oauth_' . $provider
    ]);
    
    // Set secure cookies
    $jwtManager->setTokenCookie($tokens['access_token'], $tokens['refresh_token']);
    
    // Set session variables for compatibility
    $_SESSION['authenticated_user_id'] = $userId;
    $_SESSION['unique_key'] = $sugar_config['unique_key'];
    $_SESSION['user_unique_key'] = $current_user->getPreference('unique_key');
    $_SESSION['authenticated_user_language'] = $current_user->getPreference('default_language');
    
    // Log successful OAuth login
    $auditQuery = "INSERT INTO auth_audit_log 
                   (user_id, username, event_type, auth_method, ip_address, user_agent, session_id, details, created_at) 
                   VALUES (?, ?, 'login_success', ?, ?, ?, ?, ?, ?)";
    
    $auditStmt = $db->getConnection()->prepare($auditQuery);
    $auditStmt->execute([
        $userId,
        $current_user->user_name,
        'oauth_' . $provider,
        $security->getClientIP(),
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        session_id(),
        json_encode(['provider' => $provider, 'oauth_user_id' => $userData['id']]),
        date('Y-m-d H:i:s')
    ]);
    
    // Redirect to main application
    $redirectUrl = $sugar_config['site_url'] . '/index.php';
    if (!empty($_SESSION['oauth_redirect_after_login'])) {
        $redirectUrl = $_SESSION['oauth_redirect_after_login'];
        unset($_SESSION['oauth_redirect_after_login']);
    }
    
    header('Location: ' . $redirectUrl);
    exit();
    
} catch (Exception $e) {
    // Log error
    error_log('OAuth Callback Error: ' . $e->getMessage());
    
    if (isset($security)) {
        $security->logSecurityEvent('oauth_login_failed', [
            'provider' => $provider ?? 'unknown',
            'error' => $e->getMessage(),
            'ip_address' => $security->getClientIP()
        ]);
    }
    
    // Redirect to login with error
    $errorMessage = urlencode('OAuth authentication failed. Please try again.');
    header('Location: index.php?action=Login&module=Users&loginErrorMessage=' . $errorMessage);
    exit();
}

/**
 * Generate unique username from email
 */
function generateUniqueUsername(string $email): string
{
    global $db;
    
    $baseUsername = strstr($email, '@', true);
    $baseUsername = preg_replace('/[^a-zA-Z0-9._-]/', '', $baseUsername);
    
    if (strlen($baseUsername) < 3) {
        $baseUsername = 'user_' . substr(md5($email), 0, 8);
    }
    
    $username = $baseUsername;
    $counter = 1;
    
    while (true) {
        $checkQuery = "SELECT id FROM users WHERE user_name = ? AND deleted = 0";
        $checkStmt = $db->getConnection()->prepare($checkQuery);
        $checkStmt->execute([$username]);
        
        if ($checkStmt->rowCount() === 0) {
            break;
        }
        
        $username = $baseUsername . '_' . $counter++;
        
        if ($counter > 1000) {
            // Fallback to random username
            $username = 'user_' . substr(md5($email . time()), 0, 12);
            break;
        }
    }
    
    return $username;
}
