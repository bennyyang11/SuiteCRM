<?php

/**
 * CSRF Protection Implementation
 * Provides token-based CSRF protection for all forms and AJAX requests
 */
class CSRFProtection
{
    private const TOKEN_LENGTH = 32;
    private const TOKEN_LIFETIME = 3600; // 1 hour
    private const MAX_TOKENS_PER_SESSION = 10;
    
    private static $instance = null;
    private $sessionKey = 'csrf_tokens';

    /**
     * Singleton pattern
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->initializeSession();
    }

    /**
     * Generate a new CSRF token
     */
    public function generateToken(string $action = 'default'): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $tokenData = [
            'token' => $token,
            'action' => $action,
            'created_at' => time(),
            'used' => false
        ];

        // Store token in session
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }

        // Limit number of tokens per session
        if (count($_SESSION[$this->sessionKey]) >= self::MAX_TOKENS_PER_SESSION) {
            // Remove oldest token
            array_shift($_SESSION[$this->sessionKey]);
        }

        $_SESSION[$this->sessionKey][$token] = $tokenData;

        // Clean up expired tokens
        $this->cleanupExpiredTokens();

        return $token;
    }

    /**
     * Validate CSRF token
     */
    public function validateToken(string $token, string $action = 'default', bool $singleUse = true): bool
    {
        if (empty($token)) {
            $this->logSecurityEvent('csrf_validation_failed', 'Empty token provided');
            return false;
        }

        if (!isset($_SESSION[$this->sessionKey][$token])) {
            $this->logSecurityEvent('csrf_validation_failed', 'Token not found in session');
            return false;
        }

        $tokenData = $_SESSION[$this->sessionKey][$token];

        // Check if token is expired
        if (time() - $tokenData['created_at'] > self::TOKEN_LIFETIME) {
            unset($_SESSION[$this->sessionKey][$token]);
            $this->logSecurityEvent('csrf_validation_failed', 'Token expired');
            return false;
        }

        // Check if token was already used (for single-use tokens)
        if ($singleUse && $tokenData['used']) {
            $this->logSecurityEvent('csrf_validation_failed', 'Token already used');
            return false;
        }

        // Check if action matches
        if ($tokenData['action'] !== $action) {
            $this->logSecurityEvent('csrf_validation_failed', 'Action mismatch');
            return false;
        }

        // Mark token as used if single-use
        if ($singleUse) {
            $_SESSION[$this->sessionKey][$token]['used'] = true;
        }

        $this->logSecurityEvent('csrf_validation_success', "Token validated for action: {$action}");
        return true;
    }

    /**
     * Get CSRF token for forms
     */
    public function getTokenField(string $action = 'default'): string
    {
        $token = $this->generateToken($action);
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Get CSRF token for AJAX requests
     */
    public function getTokenForAjax(string $action = 'default'): array
    {
        return [
            'csrf_token' => $this->generateToken($action),
            'csrf_action' => $action
        ];
    }

    /**
     * Validate request token
     */
    public function validateRequest(string $action = 'default'): bool
    {
        $token = $this->getTokenFromRequest();
        
        if (!$token) {
            return false;
        }

        return $this->validateToken($token, $action);
    }

    /**
     * Middleware to protect requests
     */
    public function protectRequest(string $action = 'default'): void
    {
        // Skip CSRF protection for GET requests (should be idempotent)
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return;
        }

        // Skip for API requests with valid bearer token
        if ($this->isApiRequest() && $this->hasValidApiToken()) {
            return;
        }

        if (!$this->validateRequest($action)) {
            $this->handleCSRFFailure();
        }
    }

    /**
     * Generate and set CSRF cookie for JavaScript access
     */
    public function setCsrfCookie(): void
    {
        $token = $this->generateToken('ajax');
        
        setcookie(
            'csrf_token', 
            $token, 
            [
                'expires' => time() + self::TOKEN_LIFETIME,
                'path' => '/',
                'domain' => '',
                'secure' => $this->isSecureConnection(),
                'httponly' => false, // Allow JavaScript access
                'samesite' => 'Strict'
            ]
        );
    }

    /**
     * Get CSRF meta tags for HTML head
     */
    public function getMetaTags(): string
    {
        $token = $this->generateToken('ajax');
        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Clean up expired tokens
     */
    private function cleanupExpiredTokens(): void
    {
        if (!isset($_SESSION[$this->sessionKey])) {
            return;
        }

        $currentTime = time();
        foreach ($_SESSION[$this->sessionKey] as $token => $data) {
            if ($currentTime - $data['created_at'] > self::TOKEN_LIFETIME) {
                unset($_SESSION[$this->sessionKey][$token]);
            }
        }
    }

    /**
     * Get token from request (POST, headers, or cookies)
     */
    private function getTokenFromRequest(): ?string
    {
        // Check POST data
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        // Check headers (for AJAX requests)
        $headers = getallheaders();
        if (isset($headers['X-CSRF-Token'])) {
            return $headers['X-CSRF-Token'];
        }
        if (isset($headers['X-Requested-With']) && isset($_COOKIE['csrf_token'])) {
            return $_COOKIE['csrf_token'];
        }

        return null;
    }

    /**
     * Check if request is an API request
     */
    private function isApiRequest(): bool
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($requestUri, '/api/') !== false;
    }

    /**
     * Check if request has valid API token
     */
    private function hasValidApiToken(): bool
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        return strpos($authHeader, 'Bearer ') === 0;
    }

    /**
     * Check if connection is secure
     */
    private function isSecureConnection(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Handle CSRF validation failure
     */
    private function handleCSRFFailure(): void
    {
        $this->logSecurityEvent('csrf_attack_detected', 'CSRF token validation failed');
        
        // Return 403 Forbidden
        http_response_code(403);
        
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'CSRF token validation failed',
                'code' => 403,
                'message' => 'Please refresh the page and try again'
            ]);
        } else {
            // Regular form submission
            header('Content-Type: text/html; charset=UTF-8');
            echo $this->getCSRFErrorPage();
        }
        
        exit;
    }

    /**
     * Get CSRF error page HTML
     */
    private function getCSRFErrorPage(): string
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-icon { color: #e74c3c; font-size: 48px; text-align: center; margin-bottom: 20px; }
        h1 { color: #2c3e50; text-align: center; }
        p { color: #666; line-height: 1.6; }
        .btn { display: inline-block; background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">⚠️</div>
        <h1>Security Error</h1>
        <p>Your request could not be processed due to a security validation failure. This could happen if:</p>
        <ul>
            <li>Your session has expired</li>
            <li>You have multiple browser tabs open</li>
            <li>You clicked the back button after submitting a form</li>
        </ul>
        <p>For your security, please refresh the page and try again.</p>
        <div style="text-align: center;">
            <a href="javascript:history.back()" class="btn">Go Back</a>
            <a href="javascript:location.reload()" class="btn">Refresh Page</a>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Initialize session if not already started
     */
    private function initializeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure secure session settings
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', $this->isSecureConnection() ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            
            session_start();
        }
    }

    /**
     * Log security events
     */
    private function logSecurityEvent(string $event, string $details): void
    {
        $logData = [
            'event' => $event,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'timestamp' => date('c')
        ];

        if (isset($GLOBALS['log'])) {
            $GLOBALS['log']->security("CSRF Security Event: {$event} - {$details}", $logData);
        }

        // Also log to security audit file if available
        if (class_exists('SecurityAudit')) {
            SecurityAudit::logEvent('csrf', $event, $logData);
        }
    }

    /**
     * Get token statistics for monitoring
     */
    public function getTokenStats(): array
    {
        $tokens = $_SESSION[$this->sessionKey] ?? [];
        $currentTime = time();
        
        $stats = [
            'total_tokens' => count($tokens),
            'expired_tokens' => 0,
            'used_tokens' => 0,
            'active_tokens' => 0
        ];

        foreach ($tokens as $tokenData) {
            if ($currentTime - $tokenData['created_at'] > self::TOKEN_LIFETIME) {
                $stats['expired_tokens']++;
            } elseif ($tokenData['used']) {
                $stats['used_tokens']++;
            } else {
                $stats['active_tokens']++;
            }
        }

        return $stats;
    }
}

/**
 * CSRF Protection Middleware Function
 * Call this at the beginning of protected pages
 */
function csrf_protect(string $action = 'default'): void
{
    CSRFProtection::getInstance()->protectRequest($action);
}

/**
 * Generate CSRF token field for forms
 */
function csrf_field(string $action = 'default'): string
{
    return CSRFProtection::getInstance()->getTokenField($action);
}

/**
 * Generate CSRF meta tag for AJAX
 */
function csrf_meta(): string
{
    return CSRFProtection::getInstance()->getMetaTags();
}
