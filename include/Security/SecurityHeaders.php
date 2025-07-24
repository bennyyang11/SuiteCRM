<?php
/**
 * SuiteCRM Manufacturing Security - Security Headers Configuration
 * OWASP Compliant Security Headers Implementation
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class SecurityHeaders
{
    private static $headers = [];
    private static $isInitialized = false;
    
    /**
     * Initialize and set all security headers
     */
    public static function init()
    {
        if (self::$isInitialized) {
            return;
        }
        
        self::defineSecurityHeaders();
        self::setSecurityHeaders();
        self::configureSecureCookies();
        self::$isInitialized = true;
        
        self::logSecurityEvent('SECURITY_HEADERS_INITIALIZED', 'All security headers configured');
    }
    
    /**
     * Define all security headers
     */
    private static function defineSecurityHeaders()
    {
        self::$headers = [
            // Strict Transport Security (HSTS)
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            
            // X-Frame-Options (Clickjacking protection)
            'X-Frame-Options' => 'DENY',
            
            // X-Content-Type-Options (MIME sniffing protection)
            'X-Content-Type-Options' => 'nosniff',
            
            // X-XSS-Protection (Built-in XSS protection)
            'X-XSS-Protection' => '1; mode=block',
            
            // Referrer Policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions Policy (Feature Policy)
            'Permissions-Policy' => self::getPermissionsPolicy(),
            
            // Content Security Policy
            'Content-Security-Policy' => self::getContentSecurityPolicy(),
            
            // Remove server information
            'Server' => 'SuiteCRM-Manufacturing',
            
            // Cache control for sensitive pages
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => 'Thu, 01 Jan 1970 00:00:00 GMT',
            
            // Cross-Origin policies
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin'
        ];
    }
    
    /**
     * Set security headers
     */
    private static function setSecurityHeaders()
    {
        // Don't set headers if already sent
        if (headers_sent()) {
            return;
        }
        
        foreach (self::$headers as $header => $value) {
            header("{$header}: {$value}");
        }
        
        // Remove potentially dangerous headers
        self::removeDangerousHeaders();
    }
    
    /**
     * Get Content Security Policy
     */
    private static function getContentSecurityPolicy()
    {
        $isProduction = self::isProduction();
        
        $csp = [
            "default-src 'self'",
            "script-src 'self'" . ($isProduction ? "" : " 'unsafe-inline' 'unsafe-eval'") . " https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net",
            "connect-src 'self'",
            "media-src 'self'",
            "object-src 'none'",
            "child-src 'none'",
            "frame-src 'none'",
            "worker-src 'self'",
            "manifest-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "upgrade-insecure-requests"
        ];
        
        // Add report-uri in production
        if ($isProduction) {
            $csp[] = "report-uri /api/security/csp-report";
        }
        
        return implode('; ', $csp);
    }
    
    /**
     * Get Permissions Policy
     */
    private static function getPermissionsPolicy()
    {
        $policies = [
            'accelerometer' => '()',
            'ambient-light-sensor' => '()',
            'autoplay' => '()',
            'battery' => '()',
            'camera' => '()',
            'cross-origin-isolated' => '()',
            'display-capture' => '()',
            'document-domain' => '()',
            'encrypted-media' => '()',
            'execution-while-not-rendered' => '()',
            'execution-while-out-of-viewport' => '()',
            'fullscreen' => '(self)',
            'geolocation' => '()',
            'gyroscope' => '()',
            'magnetometer' => '()',
            'microphone' => '()',
            'midi' => '()',
            'navigation-override' => '()',
            'payment' => '()',
            'picture-in-picture' => '()',
            'publickey-credentials-get' => '()',
            'screen-wake-lock' => '()',
            'sync-xhr' => '()',
            'usb' => '()',
            'web-share' => '()',
            'xr-spatial-tracking' => '()'
        ];
        
        $policyString = [];
        foreach ($policies as $directive => $allowlist) {
            $policyString[] = "{$directive}={$allowlist}";
        }
        
        return implode(', ', $policyString);
    }
    
    /**
     * Configure secure cookies
     */
    private static function configureSecureCookies()
    {
        $isHttps = self::isHttps();
        $domain = self::getDomain();
        
        // Configure session cookie settings
        ini_set('session.cookie_secure', $isHttps ? '1' : '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        
        if ($domain) {
            ini_set('session.cookie_domain', $domain);
        }
        
        // Set secure cookie defaults for application
        self::setSecureCookieDefaults();
    }
    
    /**
     * Set secure cookie defaults
     */
    private static function setSecureCookieDefaults()
    {
        $isHttps = self::isHttps();
        $domain = self::getDomain();
        
        // Override setcookie function behavior
        if (!function_exists('secure_setcookie')) {
            function secure_setcookie($name, $value = '', $expires = 0, $path = '/', $domain = '', $secure = null, $httponly = true, $samesite = 'Strict') {
                if ($secure === null) {
                    $secure = SecurityHeaders::isHttps();
                }
                
                if (empty($domain)) {
                    $domain = SecurityHeaders::getDomain();
                }
                
                $options = [
                    'expires' => $expires,
                    'path' => $path,
                    'domain' => $domain,
                    'secure' => $secure,
                    'httponly' => $httponly,
                    'samesite' => $samesite
                ];
                
                return setcookie($name, $value, $options);
            }
        }
    }
    
    /**
     * Remove dangerous headers
     */
    private static function removeDangerousHeaders()
    {
        $dangerousHeaders = [
            'X-Powered-By',
            'Server',
            'X-AspNet-Version',
            'X-AspNetMvc-Version'
        ];
        
        foreach ($dangerousHeaders as $header) {
            if (function_exists('header_remove')) {
                header_remove($header);
            }
        }
    }
    
    /**
     * Handle CSP violation reports
     */
    public static function handleCSPReport()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return;
        }
        
        $input = file_get_contents('php://input');
        $report = json_decode($input, true);
        
        if (!$report || !isset($report['csp-report'])) {
            http_response_code(400);
            return;
        }
        
        $violation = $report['csp-report'];
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'violated_directive' => $violation['violated-directive'] ?? 'unknown',
            'blocked_uri' => $violation['blocked-uri'] ?? 'unknown',
            'document_uri' => $violation['document-uri'] ?? 'unknown',
            'source_file' => $violation['source-file'] ?? 'unknown',
            'line_number' => $violation['line-number'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        self::logSecurityEvent('CSP_VIOLATION', json_encode($logData));
        
        // Send alert for repeated violations
        self::checkCSPViolationThreshold($violation['violated-directive'] ?? 'unknown');
        
        http_response_code(204);
    }
    
    /**
     * Check CSP violation threshold
     */
    private static function checkCSPViolationThreshold($directive)
    {
        $cacheKey = 'csp_violations_' . md5($directive . $_SERVER['REMOTE_ADDR']);
        $violations = apcu_fetch($cacheKey, $success);
        
        if (!$success) {
            $violations = 0;
        }
        
        $violations++;
        apcu_store($cacheKey, $violations, 3600); // Store for 1 hour
        
        // Alert if threshold exceeded
        if ($violations >= 5) {
            self::sendSecurityAlert('CSP Violation Threshold Exceeded', [
                'directive' => $directive,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'violations' => $violations
            ]);
        }
    }
    
    /**
     * Set custom security header
     */
    public static function setCustomHeader($name, $value)
    {
        if (!headers_sent()) {
            header("{$name}: {$value}");
        }
        
        self::$headers[$name] = $value;
        self::logSecurityEvent('CUSTOM_HEADER_SET', "Header: {$name}, Value: {$value}");
    }
    
    /**
     * Check if request is over HTTPS
     */
    public static function isHttps()
    {
        return (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            $_SERVER['SERVER_PORT'] == 443 ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
        );
    }
    
    /**
     * Get domain for cookie configuration
     */
    public static function getDomain()
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Remove port if present
        $host = preg_replace('/:\d+$/', '', $host);
        
        // Validate domain
        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return $host;
        }
        
        return null;
    }
    
    /**
     * Check if running in production environment
     */
    private static function isProduction()
    {
        return (
            (defined('SUGAR_ENV') && SUGAR_ENV === 'production') ||
            (!defined('SUGAR_ENV') && !ini_get('display_errors'))
        );
    }
    
    /**
     * Send security alert
     */
    private static function sendSecurityAlert($subject, $data)
    {
        $logMessage = "SECURITY ALERT: {$subject} - " . json_encode($data);
        error_log($logMessage, 3, 'logs/security_alerts.log');
        
        // In production, this would send email notifications
        if (self::isProduction()) {
            // Implementation would send email to security team
        }
    }
    
    /**
     * Log security events
     */
    private static function logSecurityEvent($eventType, $details)
    {
        global $current_user;
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $eventType,
            'user_id' => $current_user->id ?? 'anonymous',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        error_log('[SECURITY_HEADERS] ' . json_encode($logData), 3, 'logs/security.log');
    }
    
    /**
     * Validate request security headers
     */
    public static function validateRequestHeaders()
    {
        $requiredHeaders = ['Host', 'User-Agent'];
        $suspiciousHeaders = ['X-Forwarded-For', 'X-Real-IP', 'X-Originating-IP'];
        
        foreach ($requiredHeaders as $header) {
            if (empty($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))])) {
                self::logSecurityEvent('MISSING_REQUIRED_HEADER', "Header: {$header}");
                return false;
            }
        }
        
        // Check for suspicious headers that might indicate proxy/load balancer bypass
        foreach ($suspiciousHeaders as $header) {
            $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
            if (!empty($_SERVER[$headerKey])) {
                self::logSecurityEvent('SUSPICIOUS_HEADER_DETECTED', "Header: {$header}, Value: {$_SERVER[$headerKey]}");
            }
        }
        
        return true;
    }
    
    /**
     * Get security headers for testing
     */
    public static function getSecurityHeaders()
    {
        return self::$headers;
    }
    
    /**
     * Force HTTPS redirect
     */
    public static function forceHttps()
    {
        if (!self::isHttps() && self::isProduction()) {
            $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirectUrl, true, 301);
            exit();
        }
    }
    
    /**
     * Implement security middleware
     */
    public static function securityMiddleware()
    {
        // Force HTTPS in production
        self::forceHttps();
        
        // Initialize security headers
        self::init();
        
        // Validate request headers
        if (!self::validateRequestHeaders()) {
            http_response_code(400);
            die('Invalid request headers');
        }
        
        // Rate limiting check (if implemented)
        if (class_exists('RateLimiter')) {
            if (!RateLimiter::checkLimit()) {
                http_response_code(429);
                die('Rate limit exceeded');
            }
        }
        
        self::logSecurityEvent('SECURITY_MIDDLEWARE_EXECUTED', 'Request passed security checks');
    }
}

/**
 * Security Headers Middleware for Apache/Nginx
 */
class SecurityHeadersConfig
{
    /**
     * Generate .htaccess security rules
     */
    public static function generateHtaccess()
    {
        $htaccess = "
# SuiteCRM Manufacturing Security Headers
<IfModule mod_headers.c>
    # HSTS
    Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\"
    
    # Clickjacking protection
    Header always set X-Frame-Options \"DENY\"
    
    # MIME sniffing protection
    Header always set X-Content-Type-Options \"nosniff\"
    
    # XSS protection
    Header always set X-XSS-Protection \"1; mode=block\"
    
    # Referrer policy
    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"
    
    # Remove server information
    Header unset Server
    Header unset X-Powered-By
    
    # Cache control for sensitive pages
    <FilesMatch \"\.(php|html)$\">
        Header always set Cache-Control \"no-store, no-cache, must-revalidate, max-age=0\"
        Header always set Pragma \"no-cache\"
        Header always set Expires \"Thu, 01 Jan 1970 00:00:00 GMT\"
    </FilesMatch>
</IfModule>

# Security file access restrictions
<Files ~ \"^\\.(htaccess|htpasswd|ini|log|sh|inc|bak)$\">
    Order allow,deny
    Deny from all
</Files>

# Prevent access to sensitive directories
RedirectMatch 404 /\\.git
RedirectMatch 404 /logs
RedirectMatch 404 /cache/.*\\.log$
";
        
        return $htaccess;
    }
    
    /**
     * Generate Nginx security configuration
     */
    public static function generateNginxConfig()
    {
        $nginx = "
# SuiteCRM Manufacturing Security Headers
add_header Strict-Transport-Security \"max-age=31536000; includeSubDomains; preload\" always;
add_header X-Frame-Options \"DENY\" always;
add_header X-Content-Type-Options \"nosniff\" always;
add_header X-XSS-Protection \"1; mode=block\" always;
add_header Referrer-Policy \"strict-origin-when-cross-origin\" always;

# Hide server information
server_tokens off;
more_clear_headers Server;
more_clear_headers X-Powered-By;

# Cache control for sensitive files
location ~* \\.(php|html)$ {
    add_header Cache-Control \"no-store, no-cache, must-revalidate, max-age=0\" always;
    add_header Pragma \"no-cache\" always;
    add_header Expires \"Thu, 01 Jan 1970 00:00:00 GMT\" always;
}

# Block access to sensitive files
location ~ /\\.(ht|git|ini|log|sh|inc|bak) {
    deny all;
    return 404;
}

# Block access to sensitive directories
location ~ ^/(logs|cache.*\\.log)/ {
    deny all;
    return 404;
}
";
        
        return $nginx;
    }
}
