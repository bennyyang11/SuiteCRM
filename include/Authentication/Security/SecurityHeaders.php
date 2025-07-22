<?php
/**
 * SuiteCRM Modern Authentication - Security Headers Manager
 * 
 * Handles CSRF, XSS, and CSP security headers
 */

namespace SuiteCRM\Authentication\Security;

class SecurityHeaders
{
    private array $headers = [];
    private bool $enabled = true;
    
    public function __construct()
    {
        $this->loadHeadersFromDatabase();
    }
    
    /**
     * Apply all security headers
     */
    public function applyHeaders(): void
    {
        if (!$this->enabled) {
            return;
        }
        
        foreach ($this->headers as $name => $config) {
            if ($config['enabled']) {
                header("{$name}: {$config['value']}");
            }
        }
        
        // Additional dynamic headers
        $this->applyCSRFHeaders();
        $this->applyCORSHeaders();
    }
    
    /**
     * Generate and validate CSRF tokens
     */
    public function generateCSRFToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token for forms
     */
    public function getCSRFTokenField(): string
    {
        $token = $this->generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Validate request for CSRF
     */
    public function validateRequest(): bool
    {
        // Skip CSRF validation for GET requests and API calls
        if ($_SERVER['REQUEST_METHOD'] === 'GET' || $this->isAPIRequest()) {
            return true;
        }
        
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token)) {
            return false;
        }
        
        return $this->validateCSRFToken($token);
    }
    
    /**
     * Content Security Policy builder
     */
    public function buildCSP(array $directives = []): string
    {
        $defaultDirectives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://www.google.com', 'https://apis.google.com'],
            'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],
            'img-src' => ["'self'", 'data:', 'https:', 'blob:'],
            'font-src' => ["'self'", 'https://fonts.gstatic.com'],
            'connect-src' => ["'self'", 'https://api.github.com', 'https://graph.microsoft.com'],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"]
        ];
        
        $directives = array_merge($defaultDirectives, $directives);
        $cspString = '';
        
        foreach ($directives as $directive => $sources) {
            $cspString .= $directive . ' ' . implode(' ', $sources) . '; ';
        }
        
        return trim($cspString);
    }
    
    /**
     * XSS Protection utilities
     */
    public function sanitizeOutput(string $data, string $context = 'html'): string
    {
        switch ($context) {
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            case 'attr':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            case 'js':
                return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            case 'css':
                return preg_replace('/[^a-zA-Z0-9\-_]/', '', $data);
            case 'url':
                return urlencode($data);
            default:
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Validate and sanitize input data
     */
    public function sanitizeInput(array $data): array
    {
        array_walk_recursive($data, function(&$value) {
            if (is_string($value)) {
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // Basic XSS prevention
                $value = strip_tags($value, '<p><br><b><i><u><strong><em>');
                
                // Trim whitespace
                $value = trim($value);
            }
        });
        
        return $data;
    }
    
    /**
     * Check for suspicious patterns in input
     */
    public function detectMaliciousInput(string $input): array
    {
        $patterns = [
            'xss' => [
                '/<script[^>]*>.*?<\/script>/is',
                '/javascript:/i',
                '/on\w+\s*=/i',
                '/<iframe[^>]*>.*?<\/iframe>/is'
            ],
            'sql_injection' => [
                '/(\bUNION\b|\bSELECT\b|\bINSERT\b|\bDELETE\b|\bUPDATE\b|\bDROP\b)/i',
                '/(\bOR\b|\bAND\b)\s+[\w\'"]+\s*=\s*[\w\'"]+/i',
                '/[\'";](\s*(OR|AND)\s+)?[\w\'"]+\s*=\s*[\w\'"]+/i'
            ],
            'path_traversal' => [
                '/\.\.[\/\\\\]/',
                '/\.(\.+)[\/\\\\]/',
                '/(\/|\\\\)\.\.(\/|\\\\)/',
            ],
            'command_injection' => [
                '/[;&|`$(){}]/',
                '/\b(cat|ls|pwd|whoami|id|uname|nc|wget|curl)\b/i'
            ]
        ];
        
        $threats = [];
        
        foreach ($patterns as $type => $patternList) {
            foreach ($patternList as $pattern) {
                if (preg_match($pattern, $input)) {
                    $threats[] = $type;
                    break;
                }
            }
        }
        
        return array_unique($threats);
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $details = []): void
    {
        global $db;
        
        // Only log if database is available
        if (!$db) {
            error_log("SecurityHeaders: Database not available for logging event: $event");
            return;
        }
        
        try {
            $query = "INSERT INTO auth_audit_log 
                      (event_type, ip_address, user_agent, details, created_at) 
                      VALUES (" . $db->quoted($event) . ", " . 
                      $db->quoted($this->getClientIP()) . ", " . 
                      $db->quoted($_SERVER['HTTP_USER_AGENT'] ?? '') . ", " . 
                      $db->quoted(json_encode($details)) . ", " . 
                      $db->quoted(date('Y-m-d H:i:s')) . ")";
            
            $db->query($query);
        } catch (Exception $e) {
            error_log("SecurityHeaders: Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Rate limiting check
     */
    public function checkRateLimit(string $action, int $maxAttempts = 10, int $timeWindow = 3600): bool
    {
        $ip = $this->getClientIP();
        $key = "rate_limit_{$action}_{$ip}";
        
        // Simple file-based rate limiting (in production, use Redis or Memcached)
        $cacheFile = sys_get_temp_dir() . '/' . md5($key) . '.cache';
        
        $data = ['count' => 0, 'timestamp' => time()];
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true) ?? $data;
        }
        
        // Reset counter if time window has passed
        if (time() - $data['timestamp'] > $timeWindow) {
            $data = ['count' => 0, 'timestamp' => time()];
        }
        
        $data['count']++;
        file_put_contents($cacheFile, json_encode($data));
        
        return $data['count'] <= $maxAttempts;
    }
    
    /**
     * Get client IP address
     */
    public function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',           // Proxy
            'REMOTE_ADDR'               // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Apply CSRF headers
     */
    private function applyCSRFHeaders(): void
    {
        // Add CSRF token to response headers for AJAX requests
        if ($this->isAjaxRequest()) {
            header('X-CSRF-Token: ' . $this->generateCSRFToken());
        }
    }
    
    /**
     * Apply CORS headers
     */
    private function applyCORSHeaders(): void
    {
        global $sugar_config;
        
        $allowedOrigins = $sugar_config['cors_allowed_origins'] ?? ['*'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }
    }
    
    /**
     * Load headers from database
     */
    private function loadHeadersFromDatabase(): void
    {
        global $db;
        
        try {
            if (!$db) {
                $this->setDefaultHeaders();
                return;
            }
            
            $query = "SELECT header_name, header_value, enabled FROM security_headers_config";
            $result = $db->query($query);
            
            if ($result) {
                while ($row = $db->fetchByAssoc($result)) {
                    $this->headers[$row['header_name']] = [
                        'value' => $row['header_value'],
                        'enabled' => (bool) $row['enabled']
                    ];
                }
            } else {
                $this->setDefaultHeaders();
            }
        } catch (Exception $e) {
            // If database is not available, use default headers
            $this->setDefaultHeaders();
        }
    }
    
    /**
     * Set default security headers
     */
    private function setDefaultHeaders(): void
    {
        $this->headers = [
            'X-Content-Type-Options' => ['value' => 'nosniff', 'enabled' => true],
            'X-Frame-Options' => ['value' => 'DENY', 'enabled' => true],
            'X-XSS-Protection' => ['value' => '1; mode=block', 'enabled' => true],
            'Strict-Transport-Security' => ['value' => 'max-age=31536000; includeSubDomains', 'enabled' => true],
            'Referrer-Policy' => ['value' => 'strict-origin-when-cross-origin', 'enabled' => true],
            'Content-Security-Policy' => ['value' => $this->buildCSP(), 'enabled' => true]
        ];
    }
    
    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Check if request is API call
     */
    private function isAPIRequest(): bool
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/Api/') !== false ||
               strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
    }
}
