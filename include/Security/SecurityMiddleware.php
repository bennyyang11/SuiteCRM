<?php
/**
 * SuiteCRM Manufacturing Security - Security Middleware Integration
 * OWASP Compliant Security Middleware for Request Processing
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'include/Security/SecurityHeaders.php';
require_once 'include/Security/CSRFProtection.php';
require_once 'include/Security/InputValidator.php';
require_once 'include/Authentication/PasswordManager.php';
require_once 'include/Audit/SecurityAudit.php';

class SecurityMiddleware
{
    private static $isInitialized = false;
    private static $config = [];
    
    /**
     * Initialize security middleware
     */
    public static function init($config = [])
    {
        if (self::$isInitialized) {
            return;
        }
        
        self::$config = array_merge(self::getDefaultConfig(), $config);
        
        // Initialize all security components
        SecurityAudit::init();
        SecurityHeaders::init();
        CSRFProtection::init();
        InputValidator::init();
        
        // Start security monitoring
        if (class_exists('SecurityMonitor')) {
            SecurityMonitor::startMonitoring();
        }
        
        self::$isInitialized = true;
        
        SecurityAudit::logEvent('SECURITY_MIDDLEWARE_INITIALIZED', 
            'Security middleware system initialized', 
            'LOW', 
            'SYSTEM_ACCESS'
        );
    }
    
    /**
     * Process request through security middleware
     */
    public static function processRequest()
    {
        if (!self::$isInitialized) {
            self::init();
        }
        
        try {
            // 1. Validate request headers
            if (!self::validateRequestHeaders()) {
                self::blockRequest('Invalid request headers', 400);
            }
            
            // 2. Check rate limiting
            if (!self::checkRateLimit()) {
                self::blockRequest('Rate limit exceeded', 429);
            }
            
            // 3. Validate CSRF token for state-changing requests
            if (!self::validateCSRF()) {
                self::blockRequest('CSRF token validation failed', 403);
            }
            
            // 4. Validate and sanitize input
            if (!self::validateInput()) {
                self::blockRequest('Input validation failed', 400);
            }
            
            // 5. Check authentication and session security
            if (!self::validateSession()) {
                self::blockRequest('Session validation failed', 401);
            }
            
            // 6. Check authorization
            if (!self::checkAuthorization()) {
                self::blockRequest('Authorization failed', 403);
            }
            
            // 7. Log successful request
            SecurityAudit::logAccess($_SERVER['REQUEST_URI'], true);
            
            return true;
            
        } catch (Exception $e) {
            SecurityAudit::logSecurityViolation('MIDDLEWARE_ERROR', 
                'Security middleware error: ' . $e->getMessage()
            );
            self::blockRequest('Security error', 500);
        }
    }
    
    /**
     * Validate request headers
     */
    private static function validateRequestHeaders()
    {
        $requiredHeaders = ['Host', 'User-Agent'];
        
        foreach ($requiredHeaders as $header) {
            $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
            if (empty($_SERVER[$headerKey])) {
                SecurityAudit::logSecurityViolation('MISSING_REQUIRED_HEADER', 
                    "Missing required header: {$header}"
                );
                return false;
            }
        }
        
        // Check for suspicious headers
        $suspiciousHeaders = [
            'HTTP_X_FORWARDED_FOR' => 'Potential proxy bypass attempt',
            'HTTP_X_REAL_IP' => 'IP spoofing attempt',
            'HTTP_X_CLUSTER_CLIENT_IP' => 'Load balancer bypass attempt'
        ];
        
        foreach ($suspiciousHeaders as $header => $threat) {
            if (!empty($_SERVER[$header]) && !self::$config['allow_proxy_headers']) {
                SecurityAudit::logSecurityViolation('SUSPICIOUS_HEADER', 
                    "{$threat}: {$header} = {$_SERVER[$header]}"
                );
            }
        }
        
        return true;
    }
    
    /**
     * Check rate limiting
     */
    private static function checkRateLimit()
    {
        $clientIP = self::getClientIP();
        $cacheKey = 'rate_limit_' . md5($clientIP);
        
        // Get current request count
        $requests = apcu_fetch($cacheKey, $success);
        if (!$success) {
            $requests = 0;
        }
        
        $requests++;
        
        // Check if limit exceeded
        if ($requests > self::$config['rate_limit']) {
            SecurityAudit::logSecurityViolation('RATE_LIMIT_EXCEEDED', 
                "Rate limit exceeded for IP: {$clientIP}, Requests: {$requests}"
            );
            return false;
        }
        
        // Store updated count
        apcu_store($cacheKey, $requests, self::$config['rate_limit_window']);
        
        return true;
    }
    
    /**
     * Validate CSRF token
     */
    private static function validateCSRF()
    {
        // Skip CSRF validation for GET requests and API endpoints with valid tokens
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'GET' || $method === 'HEAD' || $method === 'OPTIONS') {
            return true;
        }
        
        // Check for API authentication
        if (self::hasValidAPIAuthentication()) {
            return true;
        }
        
        return CSRFProtection::validateRequest();
    }
    
    /**
     * Validate and sanitize input
     */
    private static function validateInput()
    {
        $allInput = array_merge($_GET, $_POST, $_COOKIE);
        
        // Check for malicious patterns
        foreach ($allInput as $key => $value) {
            if (is_string($value)) {
                // Check for injection attempts
                $maliciousPatterns = [
                    '/union\s+select/i' => 'SQL Injection',
                    '/<script/i' => 'XSS Attempt',
                    '/javascript:/i' => 'JavaScript Injection',
                    '/\.\.\//' => 'Directory Traversal',
                    '/exec\s*\(/i' => 'Command Injection'
                ];
                
                foreach ($maliciousPatterns as $pattern => $threat) {
                    if (preg_match($pattern, $value)) {
                        SecurityAudit::logSecurityViolation('MALICIOUS_INPUT', 
                            "{$threat} detected in parameter {$key}: " . substr($value, 0, 100)
                        );
                        return false;
                    }
                }
            }
        }
        
        // Sanitize input
        $_GET = self::sanitizeArray($_GET);
        $_POST = self::sanitizeArray($_POST);
        $_COOKIE = self::sanitizeArray($_COOKIE);
        
        return true;
    }
    
    /**
     * Validate session
     */
    private static function validateSession()
    {
        // Skip session validation for public endpoints
        if (self::isPublicEndpoint()) {
            return true;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check session validity
        if (empty($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > self::$config['session_timeout']) {
            session_destroy();
            return false;
        }
        
        // Check session IP consistency
        if (isset($_SESSION['ip_address']) && 
            $_SESSION['ip_address'] !== self::getClientIP()) {
            SecurityAudit::logSecurityViolation('SESSION_HIJACK_ATTEMPT', 
                'Session IP mismatch: ' . $_SESSION['ip_address'] . ' vs ' . self::getClientIP()
            );
            session_destroy();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Check authorization
     */
    private static function checkAuthorization()
    {
        // Skip authorization for public endpoints
        if (self::isPublicEndpoint()) {
            return true;
        }
        
        global $current_user;
        
        // Check if user is loaded
        if (empty($current_user) || empty($current_user->id)) {
            return false;
        }
        
        // Check user status
        if ($current_user->status !== 'Active') {
            SecurityAudit::logSecurityViolation('INACTIVE_USER_ACCESS', 
                "Inactive user attempted access: {$current_user->user_name}"
            );
            return false;
        }
        
        // Check role-based access
        return self::checkRoleBasedAccess();
    }
    
    /**
     * Check role-based access control
     */
    private static function checkRoleBasedAccess()
    {
        global $current_user;
        
        $requestUri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Define access control rules
        $accessRules = [
            '/admin/' => ['Admin'],
            '/api/admin/' => ['Admin'],
            '/modules/Users/' => ['Admin', 'Manager'],
            '/modules/Roles/' => ['Admin'],
            '/api/reports/' => ['Admin', 'Manager'],
            '/api/data/delete' => ['Admin', 'Manager']
        ];
        
        foreach ($accessRules as $pattern => $allowedRoles) {
            if (strpos($requestUri, $pattern) !== false) {
                $userRoles = self::getUserRoles($current_user->id);
                
                $hasAccess = false;
                foreach ($allowedRoles as $role) {
                    if (in_array($role, $userRoles)) {
                        $hasAccess = true;
                        break;
                    }
                }
                
                if (!$hasAccess) {
                    SecurityAudit::logSecurityViolation('UNAUTHORIZED_ACCESS', 
                        "User {$current_user->user_name} attempted to access restricted resource: {$requestUri}"
                    );
                    return false;
                }
                
                break;
            }
        }
        
        return true;
    }
    
    /**
     * Get user roles
     */
    private static function getUserRoles($userId)
    {
        global $db;
        
        try {
            $query = "SELECT r.name FROM roles r 
                      INNER JOIN roles_users ru ON r.id = ru.role_id 
                      WHERE ru.user_id = ? AND r.deleted = 0";
            
            $stmt = $db->prepare($query);
            $stmt->bind_param('s', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $roles = [];
            while ($row = $result->fetch_assoc()) {
                $roles[] = $row['name'];
            }
            
            $stmt->close();
            return $roles;
            
        } catch (Exception $e) {
            SecurityAudit::logEvent('ROLE_CHECK_ERROR', $e->getMessage(), 'HIGH', 'SYSTEM_ACCESS');
            return [];
        }
    }
    
    /**
     * Check if endpoint is public
     */
    private static function isPublicEndpoint()
    {
        $publicEndpoints = [
            '/index.php',
            '/login.php',
            '/api/auth/login',
            '/api/health-check',
            '/robots.txt',
            '/favicon.ico'
        ];
        
        $requestUri = $_SERVER['REQUEST_URI'];
        
        foreach ($publicEndpoints as $endpoint) {
            if (strpos($requestUri, $endpoint) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for valid API authentication
     */
    private static function hasValidAPIAuthentication()
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            return false;
        }
        
        // Check for Bearer token
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            return self::validateAPIToken($token);
        }
        
        // Check for API key
        if (strpos($authHeader, 'ApiKey ') === 0) {
            $apiKey = substr($authHeader, 7);
            return self::validateAPIKey($apiKey);
        }
        
        return false;
    }
    
    /**
     * Validate API token
     */
    private static function validateAPIToken($token)
    {
        // Implementation would validate JWT token
        // For now, return false to enforce CSRF protection
        return false;
    }
    
    /**
     * Validate API key
     */
    private static function validateAPIKey($apiKey)
    {
        global $db;
        
        try {
            $query = "SELECT user_id FROM api_keys WHERE api_key = ? AND status = 'active' AND expires_at > NOW()";
            $stmt = $db->prepare($query);
            $stmt->bind_param('s', $apiKey);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $isValid = $result->num_rows > 0;
            $stmt->close();
            
            if ($isValid) {
                SecurityAudit::logEvent('API_KEY_AUTHENTICATION', 
                    'Valid API key authentication', 
                    'LOW', 
                    'AUTHENTICATION'
                );
            }
            
            return $isValid;
            
        } catch (Exception $e) {
            SecurityAudit::logEvent('API_KEY_VALIDATION_ERROR', $e->getMessage(), 'HIGH', 'AUTHENTICATION');
            return false;
        }
    }
    
    /**
     * Sanitize array recursively
     */
    private static function sanitizeArray($array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::sanitizeArray($value);
            } else {
                $array[$key] = InputValidator::sanitize($value);
            }
        }
        
        return $array;
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                                  FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Block request with security violation
     */
    private static function blockRequest($reason, $statusCode = 403)
    {
        SecurityAudit::logSecurityViolation('REQUEST_BLOCKED', 
            "Request blocked: {$reason}", 
            [
                'ip_address' => self::getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'status_code' => $statusCode
            ]
        );
        
        http_response_code($statusCode);
        
        if ($statusCode === 429) {
            header('Retry-After: ' . self::$config['rate_limit_window']);
        }
        
        // Return JSON for API requests, HTML for web requests
        if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Security violation',
                'message' => 'Request blocked for security reasons',
                'code' => $statusCode
            ]);
        } else {
            header('Content-Type: text/html');
            echo self::getSecurityBlockPage($reason, $statusCode);
        }
        
        exit();
    }
    
    /**
     * Get security block page HTML
     */
    private static function getSecurityBlockPage($reason, $statusCode)
    {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Security Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f8f9fa; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-code { font-size: 48px; color: #e74c3c; margin-bottom: 20px; }
        .error-message { color: #2c3e50; margin-bottom: 30px; }
        .details { background: #f8f9fa; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-code">🚫 ' . $statusCode . '</div>
        <h1>Security Error</h1>
        <div class="error-message">
            <p>Your request has been blocked for security reasons.</p>
        </div>
        <div class="details">
            <p><strong>Incident ID:</strong> ' . uniqid() . '</p>
            <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p>If you believe this is an error, please contact the system administrator.</p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Get default configuration
     */
    private static function getDefaultConfig()
    {
        return [
            'rate_limit' => 100, // requests per window
            'rate_limit_window' => 300, // 5 minutes
            'session_timeout' => 3600, // 1 hour
            'allow_proxy_headers' => false,
            'strict_mode' => true,
            'log_all_requests' => false,
            'block_suspicious_requests' => true
        ];
    }
    
    /**
     * Update configuration
     */
    public static function updateConfig($config)
    {
        self::$config = array_merge(self::$config, $config);
        
        SecurityAudit::logEvent('SECURITY_CONFIG_UPDATED', 
            'Security middleware configuration updated', 
            'MEDIUM', 
            'CONFIGURATION'
        );
    }
    
    /**
     * Get current configuration
     */
    public static function getConfig()
    {
        return self::$config;
    }
    
    /**
     * Get security metrics
     */
    public static function getSecurityMetrics()
    {
        return [
            'blocked_requests' => self::getBlockedRequestsCount(),
            'rate_limited_ips' => self::getRateLimitedIPs(),
            'failed_authentications' => self::getFailedAuthCount(),
            'security_violations' => self::getSecurityViolationsCount()
        ];
    }
    
    private static function getBlockedRequestsCount()
    {
        // Implementation would query audit log
        return 0;
    }
    
    private static function getRateLimitedIPs()
    {
        // Implementation would check rate limit cache
        return [];
    }
    
    private static function getFailedAuthCount()
    {
        // Implementation would query failed login attempts
        return 0;
    }
    
    private static function getSecurityViolationsCount()
    {
        // Implementation would query security violations
        return 0;
    }
}

/**
 * Security middleware initialization hook
 */
function initSecurityMiddleware()
{
    SecurityMiddleware::init();
    SecurityMiddleware::processRequest();
}

// Auto-initialize if not in CLI mode
if (php_sapi_name() !== 'cli') {
    register_shutdown_function(function() {
        if (!SecurityMiddleware::$isInitialized) {
            initSecurityMiddleware();
        }
    });
}
