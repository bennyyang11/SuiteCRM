<?php

namespace Api\V8\Middleware;

use Api\V8\Authentication\JWTManager;
use Api\V8\RoleManagement\RoleDefinitions;
use Exception;
use PDO;

class AuthMiddleware
{
    private JWTManager $jwtManager;
    private RoleDefinitions $roleManager;
    private array $rateLimits;

    public function __construct(PDO $database)
    {
        $this->jwtManager = new JWTManager();
        $this->roleManager = new RoleDefinitions($database);
        
        // Rate limits by role (requests per minute)
        $this->rateLimits = [
            'admin' => 200,
            'manager' => 150,
            'sales_rep' => 100,
            'client' => 50,
            'anonymous' => 20
        ];
    }

    /**
     * Main authentication middleware
     */
    public function authenticate(): array
    {
        try {
            $token = $this->jwtManager->extractTokenFromHeader();
            
            if (!$token) {
                throw new Exception('Authorization token required', 401);
            }
            
            $tokenData = $this->jwtManager->validateToken($token);
            
            // Check if token is blacklisted
            if (isset($tokenData['jti']) && $this->jwtManager->isTokenBlacklisted($tokenData['jti'])) {
                throw new Exception('Token has been invalidated', 401);
            }
            
            // Get fresh user permissions
            $userRoles = $this->roleManager->getUserRoles($tokenData['user_id']);
            $userTerritories = $this->roleManager->getUserTerritories($tokenData['user_id']);
            
            // Update token data with fresh permissions
            $tokenData['permissions'] = $userRoles['merged_permissions'];
            $tokenData['territories'] = $userTerritories;
            $tokenData['primary_role'] = $userRoles['primary_role'];
            
            // Apply rate limiting
            $this->checkRateLimit($tokenData);
            
            return $tokenData;
            
        } catch (Exception $e) {
            http_response_code($e->getCode() ?: 401);
            echo json_encode([
                'error' => 'Authentication failed',
                'message' => $e->getMessage(),
                'code' => $e->getCode() ?: 401
            ]);
            exit;
        }
    }

    /**
     * Authorize user for specific action on module
     */
    public function authorize(array $tokenData, string $module, string $action): bool
    {
        try {
            // Check if user has required permission
            if (!$this->jwtManager->hasPermission($tokenData, $module, $action)) {
                throw new Exception("Insufficient permissions for {$action} on {$module}", 403);
            }
            
            return true;
            
        } catch (Exception $e) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Authorization failed',
                'message' => $e->getMessage(),
                'code' => 403
            ]);
            exit;
        }
    }

    /**
     * Check territory access for data filtering
     */
    public function checkTerritoryAccess(array $tokenData, string $territoryId): bool
    {
        return $this->jwtManager->hasTerritoryAccess($tokenData, $territoryId);
    }

    /**
     * Rate limiting by user role
     */
    private function checkRateLimit(array $tokenData): void
    {
        $userId = $tokenData['user_id'];
        $role = $tokenData['primary_role'] ?? 'anonymous';
        $limit = $this->rateLimits[$role] ?? $this->rateLimits['anonymous'];
        
        $rateLimitKey = "rate_limit_{$userId}";
        $currentTime = time();
        $windowStart = $currentTime - 60; // 1 minute window
        
        // Get current request count from storage
        $requestCount = $this->getRateLimitCount($rateLimitKey, $windowStart);
        
        if ($requestCount >= $limit) {
            http_response_code(429);
            echo json_encode([
                'error' => 'Rate limit exceeded',
                'message' => "Maximum {$limit} requests per minute allowed for {$role} role",
                'code' => 429,
                'retry_after' => 60
            ]);
            exit;
        }
        
        // Increment request count
        $this->incrementRateLimitCount($rateLimitKey, $currentTime);
    }

    /**
     * Get rate limit count from storage
     */
    private function getRateLimitCount(string $key, int $windowStart): int
    {
        $rateLimitFile = sys_get_temp_dir() . '/rate_limits.json';
        
        if (!file_exists($rateLimitFile)) {
            return 0;
        }
        
        $rateLimits = json_decode(file_get_contents($rateLimitFile), true) ?? [];
        
        if (!isset($rateLimits[$key])) {
            return 0;
        }
        
        // Count requests within the time window
        $requests = array_filter($rateLimits[$key], function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });
        
        return count($requests);
    }

    /**
     * Increment rate limit count
     */
    private function incrementRateLimitCount(string $key, int $timestamp): void
    {
        $rateLimitFile = sys_get_temp_dir() . '/rate_limits.json';
        
        $rateLimits = [];
        if (file_exists($rateLimitFile)) {
            $rateLimits = json_decode(file_get_contents($rateLimitFile), true) ?? [];
        }
        
        if (!isset($rateLimits[$key])) {
            $rateLimits[$key] = [];
        }
        
        $rateLimits[$key][] = $timestamp;
        
        // Clean up old entries (older than 5 minutes)
        $cutoff = $timestamp - 300;
        foreach ($rateLimits as $userId => $timestamps) {
            $rateLimits[$userId] = array_filter($timestamps, function($ts) use ($cutoff) {
                return $ts > $cutoff;
            });
            
            // Remove empty entries
            if (empty($rateLimits[$userId])) {
                unset($rateLimits[$userId]);
            }
        }
        
        file_put_contents($rateLimitFile, json_encode($rateLimits));
    }

    /**
     * Log API access for audit trail
     */
    public function logAccess(array $tokenData, string $endpoint, string $method, ?array $requestData = null): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $tokenData['user_id'],
            'username' => $tokenData['username'],
            'role' => $tokenData['primary_role'],
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_size' => $requestData ? strlen(json_encode($requestData)) : 0
        ];
        
        $logFile = sys_get_temp_dir() . '/api_access.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    }

    /**
     * Apply CORS headers for cross-origin requests
     */
    public function applyCorsHeaders(): void
    {
        // Allow specific origins in production
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Apply security headers
     */
    public function applySecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        header('Content-Security-Policy: default-src \'self\'');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    /**
     * Validate CSRF token for state-changing operations
     */
    public function validateCSRF(): bool
    {
        if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? null;
            $sessionToken = $_SESSION['csrf_token'] ?? null;
            
            if (!$csrfToken || !$sessionToken || !hash_equals($sessionToken, $csrfToken)) {
                http_response_code(403);
                echo json_encode([
                    'error' => 'CSRF validation failed',
                    'message' => 'Invalid or missing CSRF token',
                    'code' => 403
                ]);
                exit;
            }
        }
        
        return true;
    }

    /**
     * Sanitize input data
     */
    public function sanitizeInput(array $data): array
    {
        return array_map(function($value) {
            if (is_string($value)) {
                // Remove potentially dangerous characters
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                $value = strip_tags($value);
                return trim($value);
            }
            return $value;
        }, $data);
    }

    /**
     * Check if endpoint requires authentication
     */
    public function isProtectedEndpoint(string $endpoint): bool
    {
        $publicEndpoints = [
            '/api/v8/auth/login',
            '/api/v8/auth/register',
            '/api/v8/auth/forgot-password',
            '/api/v8/health'
        ];
        
        return !in_array($endpoint, $publicEndpoints);
    }

    /**
     * Complete middleware chain for protected endpoints
     */
    public function protect(string $module, string $action, string $endpoint): array
    {
        // Apply security headers
        $this->applySecurityHeaders();
        $this->applyCorsHeaders();
        
        // Skip authentication for public endpoints
        if (!$this->isProtectedEndpoint($endpoint)) {
            return ['role' => 'anonymous'];
        }
        
        // Validate CSRF for state-changing operations
        $this->validateCSRF();
        
        // Authenticate user
        $tokenData = $this->authenticate();
        
        // Authorize user for specific action
        $this->authorize($tokenData, $module, $action);
        
        // Log access
        $this->logAccess($tokenData, $endpoint, $_SERVER['REQUEST_METHOD']);
        
        return $tokenData;
    }

    /**
     * Generate CSRF token for forms
     */
    public function generateCSRFToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        
        return $token;
    }

    /**
     * Filter data based on user's territory access
     */
    public function filterByTerritoryAccess(array $tokenData, array $data, string $territoryField = 'territory_id'): array
    {
        return $this->roleManager->filterByUserTerritories($tokenData['user_id'], $data, $territoryField);
    }
}
