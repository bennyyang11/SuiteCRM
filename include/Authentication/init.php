<?php
/**
 * SuiteCRM Modern Authentication Initialization
 * 
 * Initializes modern authentication system and integrates with legacy SuiteCRM
 */

// Auto-load modern authentication classes
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

// Register entry points for OAuth
$entry_point_registry['oauth_login'] = [
    'file' => 'modules/Users/entrypoint_oauth_login.php',
    'auth' => false
];

// Initialize security headers on every request
use SuiteCRM\Authentication\Security\SecurityHeaders;

if (class_exists('SuiteCRM\Authentication\Security\SecurityHeaders')) {
    $security = new SecurityHeaders();
    $security->applyHeaders();
    
    // Validate CSRF for POST requests (temporarily disabled for legitimate AJAX requests)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
        !$security->validateRequest() && 
        !isset($_REQUEST['entryPoint'])) { // Allow SuiteCRM entry points to work
        // Log security violation
        $security->logSecurityEvent('csrf_violation', [
            'ip' => $security->getClientIP(),
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Redirect to login with error
        header('Location: index.php?action=Login&module=Users&loginErrorMessage=' . urlencode('Security validation failed'));
        exit();
    }
}

// Override default authentication for enhanced security
global $sugar_config;

// Set OAuth provider configuration
if (!isset($sugar_config['oauth_providers'])) {
    $sugar_config['oauth_providers'] = [
        'google' => [
            'client_id' => '',
            'client_secret' => '',
            'enabled' => false
        ],
        'microsoft' => [
            'client_id' => '',
            'client_secret' => '',
            'enabled' => false
        ],
        'github' => [
            'client_id' => '',
            'client_secret' => '',
            'enabled' => false
        ]
    ];
}

// Enhanced password policy configuration
if (!isset($sugar_config['password_policy'])) {
    $sugar_config['password_policy'] = [
        'min_length' => 12,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => true,
        'password_expiry_days' => 90,
        'account_lockout_attempts' => 5,
        'account_lockout_duration' => 30
    ];
}

// JWT configuration
if (!isset($sugar_config['jwt_secret_key'])) {
    $sugar_config['jwt_secret_key'] = base64_encode(random_bytes(64));
    
    // Save to config_override.php
    $configFile = 'config_override.php';
    $configContent = file_get_contents($configFile) ?? "<?php\n";
    $configContent .= "\n\$sugar_config['jwt_secret_key'] = '{$sugar_config['jwt_secret_key']}';\n";
    file_put_contents($configFile, $configContent);
}

/**
 * Enhanced authentication hook for SuiteCRM
 */
function modern_auth_hook($event, $arguments)
{
    if ($event === 'before_login') {
        // Apply rate limiting and security checks
        
        $security = new SecurityHeaders();
        $ip = $security->getClientIP();
        
        // Check rate limiting
        if (!$security->checkRateLimit('login_' . $ip, 10, 600)) {
            throw new Exception('Too many login attempts from this IP address');
        }
        
        // Check for malicious input
        $username = $arguments['username'] ?? '';
        $threats = $security->detectMaliciousInput($username);
        
        if (!empty($threats)) {
            $security->logSecurityEvent('malicious_input_detected', [
                'threats' => $threats,
                'input' => $username,
                'ip' => $ip
            ]);
            
            throw new Exception('Invalid input detected');
        }
    }
}

// Register hooks
if (function_exists('LogicHook')) {
    LogicHook::initialize();
    $GLOBALS['logic_hook']->call_custom_logic('Users', 'before_login');
}

/**
 * JWT Token validation for API requests
 */
function validate_jwt_token()
{
    use SuiteCRM\Authentication\ModernAuth\JWTManager;
    
    // Check for JWT token in Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        // Check for token in cookies
        $token = $_COOKIE['suitecrm_access_token'] ?? '';
    } else {
        // Extract token from Bearer header
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            return false;
        }
    }
    
    if (empty($token)) {
        return false;
    }
    
    $jwtManager = new JWTManager();
    $payload = $jwtManager->validateToken($token);
    
    if ($payload) {
        // Set current user from token
        global $current_user;
        $current_user = BeanFactory::getBean('Users', $payload['sub']);
        $_SESSION['authenticated_user_id'] = $payload['sub'];
        
        return true;
    }
    
    return false;
}

// Clean up expired tokens periodically
function cleanup_expired_tokens()
{
    global $db;
    
    // Run cleanup only 1% of the time to avoid performance impact
    if (rand(1, 100) === 1) {
        try {
            // Clean expired refresh tokens
            $query = "DELETE FROM user_refresh_tokens WHERE expires_at < NOW()";
            $db->query($query);
            
            // Clean old audit logs (keep 90 days)
            $query = "DELETE FROM auth_audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
            $db->query($query);
            
            // Clean expired lockouts
            $query = "UPDATE users SET account_locked_until = NULL WHERE account_locked_until < NOW()";
            $db->query($query);
            
        } catch (Exception $e) {
            error_log('Token cleanup error: ' . $e->getMessage());
        }
    }
}

// Run cleanup
cleanup_expired_tokens();
