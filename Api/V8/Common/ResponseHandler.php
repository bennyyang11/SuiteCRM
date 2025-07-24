<?php

namespace Api\V8\Common;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Standardized API response handler with proper HTTP status codes and error handling
 */
class ResponseHandler
{
    private LoggerInterface $logger;
    private array $supportedFormats = ['json', 'xml'];
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Send successful response
     */
    public function success($data = null, int $statusCode = 200, array $meta = []): void
    {
        $this->sendResponse([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
            'timestamp' => date('c')
        ], $statusCode);
    }

    /**
     * Send error response
     */
    public function error(string $message, int $statusCode = 400, string $errorType = 'BadRequest', $details = null): void
    {
        $errorData = [
            'success' => false,
            'error' => $errorType,
            'message' => $message,
            'code' => $statusCode,
            'timestamp' => date('c')
        ];

        if ($details !== null) {
            $errorData['details'] = $details;
        }

        // Log error for monitoring
        $this->logger->error("API Error: {$errorType}", [
            'message' => $message,
            'code' => $statusCode,
            'details' => $details,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip_address' => $this->getClientIpAddress()
        ]);

        $this->sendResponse($errorData, $statusCode);
    }

    /**
     * Handle validation errors
     */
    public function validationError(array $errors, string $message = 'Validation failed'): void
    {
        $this->error($message, 422, 'ValidationError', $errors);
    }

    /**
     * Handle authentication errors
     */
    public function authenticationError(string $message = 'Authentication required'): void
    {
        $this->error($message, 401, 'AuthenticationError');
    }

    /**
     * Handle authorization errors
     */
    public function authorizationError(string $message = 'Insufficient permissions'): void
    {
        $this->error($message, 403, 'AuthorizationError');
    }

    /**
     * Handle not found errors
     */
    public function notFound(string $resource = 'Resource'): void
    {
        $this->error("{$resource} not found", 404, 'NotFound');
    }

    /**
     * Handle method not allowed errors
     */
    public function methodNotAllowed(array $allowedMethods = []): void
    {
        $details = empty($allowedMethods) ? null : ['allowed_methods' => $allowedMethods];
        $this->error('Method not allowed', 405, 'MethodNotAllowed', $details);
        
        if (!empty($allowedMethods)) {
            header('Allow: ' . implode(', ', $allowedMethods));
        }
    }

    /**
     * Handle rate limiting
     */
    public function rateLimitExceeded(int $retryAfter = 3600): void
    {
        header("Retry-After: {$retryAfter}");
        $this->error('Rate limit exceeded', 429, 'RateLimitExceeded', [
            'retry_after' => $retryAfter
        ]);
    }

    /**
     * Handle server errors
     */
    public function serverError(string $message = 'Internal server error', Exception $exception = null): void
    {
        // Log detailed error information
        if ($exception) {
            $this->logger->critical('Server Error', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]);
        }

        // Don't expose internal errors in production
        if (getenv('ENVIRONMENT') === 'production') {
            $message = 'An internal error occurred';
        }

        $this->error($message, 500, 'ServerError');
    }

    /**
     * Handle service unavailable
     */
    public function serviceUnavailable(string $message = 'Service temporarily unavailable'): void
    {
        $this->error($message, 503, 'ServiceUnavailable');
    }

    /**
     * Send paginated response
     */
    public function paginated(array $data, int $total, int $limit, int $offset): void
    {
        $meta = [
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($data)
            ]
        ];

        $this->success($data, 200, $meta);
    }

    /**
     * Send created response
     */
    public function created($data = null, string $location = null): void
    {
        if ($location) {
            header("Location: {$location}");
        }
        
        $this->success($data, 201);
    }

    /**
     * Send no content response
     */
    public function noContent(): void
    {
        $this->sendResponse(null, 204);
    }

    /**
     * Send accepted response (for async operations)
     */
    public function accepted($data = null): void
    {
        $this->success($data, 202);
    }

    /**
     * Send response with proper headers and content negotiation
     */
    private function sendResponse($data, int $statusCode): void
    {
        // Set status code
        http_response_code($statusCode);

        // Set security headers
        $this->setSecurityHeaders();

        // Content negotiation
        $format = $this->negotiateContentType();
        
        switch ($format) {
            case 'xml':
                header('Content-Type: application/xml; charset=utf-8');
                echo $this->toXml($data);
                break;
            case 'json':
            default:
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Ensure response is sent immediately
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        
        exit;
    }

    /**
     * Set security headers
     */
    private function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // CORS headers (configure based on your needs)
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $allowedOrigins = $this->getAllowedOrigins();
            if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            }
        }
    }

    /**
     * Content type negotiation
     */
    private function negotiateContentType(): string
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? 'application/json';
        
        if (strpos($accept, 'application/xml') !== false || strpos($accept, 'text/xml') !== false) {
            return 'xml';
        }
        
        return 'json';
    }

    /**
     * Convert data to XML format
     */
    private function toXml($data): string
    {
        $xml = new \SimpleXMLElement('<response/>');
        $this->arrayToXml($data, $xml);
        return $xml->asXML();
    }

    /**
     * Helper method to convert array to XML
     */
    private function arrayToXml($data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item';
                }
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                $xml->addChild($key, htmlspecialchars($value));
            }
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIpAddress(): string
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return 'unknown';
    }

    /**
     * Get allowed origins for CORS
     */
    private function getAllowedOrigins(): array
    {
        // Configure based on your environment
        return [
            'http://localhost:3000',
            'http://localhost:3001',
            'https://your-production-domain.com'
        ];
    }

    /**
     * Rate limiting check
     */
    public function checkRateLimit(string $identifier, int $maxRequests = 1000, int $windowSeconds = 3600): bool
    {
        // Implementation would use Redis or database to track requests
        // This is a simplified version
        $key = "rate_limit:{$identifier}";
        
        // In a real implementation, you would:
        // 1. Get current count from Redis
        // 2. Check if within limits
        // 3. Increment counter
        // 4. Set expiration if new key
        
        return true; // Placeholder
    }

    /**
     * Validate API version
     */
    public function validateApiVersion(string $requestedVersion, array $supportedVersions): bool
    {
        return in_array($requestedVersion, $supportedVersions);
    }

    /**
     * Track API metrics
     */
    public function trackMetrics(string $endpoint, string $method, int $responseTime, int $statusCode): void
    {
        $this->logger->info('API Metrics', [
            'endpoint' => $endpoint,
            'method' => $method,
            'response_time' => $responseTime,
            'status_code' => $statusCode,
            'timestamp' => time()
        ]);
    }
}
