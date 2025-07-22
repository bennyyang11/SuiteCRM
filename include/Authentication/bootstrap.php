<?php
/**
 * SuiteCRM Modern Authentication Bootstrap
 * 
 * This file is loaded early in the SuiteCRM bootstrap process to initialize
 * modern authentication features and security headers
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

// Load modern authentication classes
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// Initialize security headers immediately
use SuiteCRM\Authentication\Security\SecurityHeaders;

if (class_exists('SuiteCRM\Authentication\Security\SecurityHeaders')) {
    try {
        $security = new SecurityHeaders();
        
        // Apply security headers to all responses
        $security->applyHeaders();
        
        // Add CSRF token to session if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $security->generateCSRFToken();
        }
        
        // Validate CSRF for POST requests (except login and OAuth)
        // Skip CSRF validation for GET requests as they should be safe
        // Only validate CSRF for actual form submissions, not AJAX calls
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
            !isExcludedFromCSRF() && 
            !isAjaxRequest() && 
            isFormSubmission()) {
            if (!$security->validateRequest()) {
                // Log security violation (only if database is available)
                try {
                    $security->logSecurityEvent('csrf_violation', [
                        'ip' => $security->getClientIP(),
                        'uri' => $_SERVER['REQUEST_URI'] ?? '',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        'referer' => $_SERVER['HTTP_REFERER'] ?? ''
                    ]);
                } catch (Exception $logException) {
                    // Logging failed, but continue with security response
                    error_log('Security event logging failed: ' . $logException->getMessage());
                }
                
                // For AJAX requests, return JSON error
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'CSRF token validation failed']);
                    exit();
                }
                
                // For regular requests, redirect to login
                header('Location: index.php?action=Login&module=Users&loginErrorMessage=' . 
                       urlencode('Security validation failed. Please try again.'));
                exit();
            }
        }
        
    } catch (Exception $e) {
        // Log error but don't break the application
        error_log('Modern Auth Bootstrap Error: ' . $e->getMessage());
    }
}

/**
 * Check if current request is an AJAX request
 */
function isAjaxRequest()
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check if current request is a form submission
 */
function isFormSubmission()
{
    // Form submissions typically have these characteristics
    return isset($_POST['action']) || 
           isset($_POST['module']) ||
           !empty($_POST);
}

/**
 * Check if current request should be excluded from CSRF validation
 */
function isExcludedFromCSRF()
{
    $excludedActions = [
        'Login',
        'Authenticate', 
        'oauth_callback',
        'GeneratePassword',
        'index',
        'DetailView',
        'ListView',
        'EditView',
        'Save',
        'Delete',
        'CallBackHandler',
        'DynamicAction',
        'ajaxui'
    ];
    
    $excludedEntryPoints = [
        'oauth_login',
        'download',
        'image',
        'export',
        'getYUILoader',
        'GetModuleLanguage',
        'Logout'
    ];
    
    $excludedModules = [
        'Home',
        'Dashboard',
        'Charts',
        'Reports'
    ];
    
    // Check action
    $action = $_REQUEST['action'] ?? '';
    if (in_array($action, $excludedActions)) {
        return true;
    }
    
    // Check entry point
    $entryPoint = $_REQUEST['entryPoint'] ?? '';
    if (in_array($entryPoint, $excludedEntryPoints)) {
        return true;
    }
    
    // Check module
    $module = $_REQUEST['module'] ?? '';
    if (in_array($module, $excludedModules)) {
        return true;
    }
    
    // AJAX requests are handled separately by isAjaxRequest() function
    
    // API requests are handled separately
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/Api/') !== false) {
        return true;
    }
    
    // Exclude common SuiteCRM AJAX endpoints
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($uri, 'index.php?to_pdf=1') !== false ||
        strpos($uri, 'index.php?sugar_body_only=1') !== false ||
        strpos($uri, 'index.php?entryPoint=') !== false ||
        strpos($uri, 'sugar_body_only=1') !== false ||
        strpos($uri, 'to_pdf=1') !== false) {
        return true;
    }
    
    // Exclude dashlet loading and other dashboard-specific requests
    if (isset($_REQUEST['dashletId']) || 
        isset($_REQUEST['isAjaxCall']) ||
        isset($_REQUEST['refresh']) ||
        strpos($uri, 'Dashlets') !== false) {
        return true;
    }
    
    return false;
}

/**
 * Initialize JWT token validation for authenticated requests
 */
function initializeJWTValidation(): void
{
    
    // Skip for login pages and public endpoints
    if (isPublicEndpoint()) {
        return;
    }
    
    try {
        $jwtManager = new \SuiteCRM\Authentication\ModernAuth\JWTManager();
        
        // Check for JWT token in cookies or headers
        $token = $_COOKIE['suitecrm_access_token'] ?? '';
        
        if (empty($token)) {
            // Check Authorization header
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
            
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if (!empty($token)) {
            $payload = $jwtManager->validateToken($token);
            
            if ($payload) {
                // Set current user from token
                global $current_user;
                $current_user = BeanFactory::getBean('Users', $payload['sub']);
                $_SESSION['authenticated_user_id'] = $payload['sub'];
                
                // Update last activity
                $_SESSION['last_jwt_activity'] = time();
            } else {
                // Token invalid, clear cookies
                $jwtManager->clearTokenCookies();
            }
        }
        
    } catch (Exception $e) {
        error_log('JWT Validation Error: ' . $e->getMessage());
    }
}

/**
 * Check if current endpoint is public (doesn't require authentication)
 */
function isPublicEndpoint()
{
    $publicActions = ['Login', 'Authenticate', 'Logout'];
    $publicEntryPoints = ['oauth_login', 'oauth_callback', 'GeneratePassword'];
    
    $action = $_REQUEST['action'] ?? '';
    $entryPoint = $_REQUEST['entryPoint'] ?? '';
    
    return in_array($action, $publicActions) || in_array($entryPoint, $publicEntryPoints);
}

// Initialize JWT validation
initializeJWTValidation();
