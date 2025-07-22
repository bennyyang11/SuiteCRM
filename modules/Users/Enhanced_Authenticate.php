<?php
/**
 * Enhanced Authentication Handler for SuiteCRM
 * 
 * Integrates modern authentication with existing SuiteCRM authentication flow
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/entryPoint.php';

use SuiteCRM\Authentication\ModernAuth\AuthenticationController;
use SuiteCRM\Authentication\Security\SecurityHeaders;

global $mod_strings, $app_strings, $sugar_config, $current_user;

// Start timing for performance measurement
$auth_start_time = microtime(true);

// Initialize security
$security = new SecurityHeaders();
$security->applyHeaders();

// Initialize authentication controller
$authController = new AuthenticationController();

try {
    // Get login credentials
    $username = $_POST['user_name'] ?? '';
    $password = $_POST['username_password'] ?? '';
    
    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required');
    }
    
    // Validate CSRF token
    if (!$security->validateRequest()) {
        throw new Exception('Security validation failed');
    }
    
    // Attempt authentication
    $authResult = $authController->authenticatePassword($username, $password);
    
    // Handle different authentication results
    switch ($authResult['status']) {
        case 'success':
            // Authentication successful
            $current_user = BeanFactory::getBean('Users', $authResult['user_id']);
            
            // Set session variables for SuiteCRM compatibility
            $_SESSION['authenticated_user_id'] = $authResult['user_id'];
            $_SESSION['unique_key'] = $sugar_config['unique_key'];
            $_SESSION['user_unique_key'] = $current_user->getPreference('unique_key');
            $_SESSION['authenticated_user_language'] = $current_user->getPreference('default_language');
            $_SESSION['authenticated_user_theme'] = $current_user->getPreference('user_theme');
            
            // Calculate authentication time
            $auth_time = microtime(true) - $auth_start_time;
            
            // Log successful authentication with timing
            error_log("Modern Auth: Successful login for user '{$username}' in " . round($auth_time * 1000, 2) . "ms");
            
            // Handle redirect
            $redirect_url = $_POST['return_module'] ?? 'Home';
            $redirect_action = $_POST['return_action'] ?? 'index';
            
            if (!empty($_POST['login_url'])) {
                $redirect_location = $_POST['login_url'];
            } else {
                $redirect_location = "index.php?module={$redirect_url}&action={$redirect_action}";
            }
            
            // Store success metrics in session for testing
            $_SESSION['last_auth_time'] = $auth_time;
            $_SESSION['last_auth_method'] = 'modern_password';
            $_SESSION['last_auth_success'] = true;
            
            header("Location: {$redirect_location}");
            exit();
            
        case '2fa_required':
            // Redirect to 2FA verification page
            $_SESSION['pending_2fa_user_id'] = $authResult['user_id'];
            header('Location: index.php?module=Users&action=TwoFactorAuth');
            exit();
            
        case 'password_expired':
            // Redirect to password change page
            $_SESSION['password_expired_user_id'] = $authResult['user_id'];
            header('Location: index.php?module=Users&action=ChangePassword&expired=1');
            exit();
            
        case 'password_change_required':
            // Redirect to forced password change
            $_SESSION['force_password_change_user_id'] = $authResult['user_id'];
            header('Location: index.php?module=Users&action=ChangePassword&forced=1');
            exit();
            
        default:
            throw new Exception($authResult['message'] ?? 'Authentication failed');
    }
    
} catch (Exception $e) {
    // Log authentication failure
    $auth_time = microtime(true) - $auth_start_time;
    error_log("Modern Auth: Failed login attempt for user '{$username}' in " . round($auth_time * 1000, 2) . "ms - " . $e->getMessage());
    
    // Store failure metrics
    $_SESSION['last_auth_time'] = $auth_time;
    $_SESSION['last_auth_method'] = 'modern_password';
    $_SESSION['last_auth_success'] = false;
    $_SESSION['last_auth_error'] = $e->getMessage();
    
    // Set error message for display
    $error_message = $e->getMessage();
    
    // For common errors, use user-friendly messages
    if (strpos($error_message, 'Invalid username or password') !== false) {
        $error_message = 'LBL_LOGIN_ERROR_MESSAGE';
    } elseif (strpos($error_message, 'Account is temporarily locked') !== false) {
        $error_message = 'LBL_LOGIN_ACCOUNT_LOCKED';
    } elseif (strpos($error_message, 'Too many login attempts') !== false) {
        $error_message = 'LBL_LOGIN_TOO_MANY_ATTEMPTS';
    }
    
    // Redirect back to login with error
    SugarApplication::setCookie('loginErrorMessage', $error_message, time() + 30);
    header('Location: index.php?action=Login&module=Users');
    exit();
}
