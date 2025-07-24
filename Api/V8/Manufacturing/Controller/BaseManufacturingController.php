<?php
namespace Api\V8\Manufacturing\Controller;

use Api\V8\Controller\BaseController;
use Api\V8\JsonApi\Response\ErrorResponse;
use Slim\Http\Request;
use Slim\Http\Response as HttpResponse;
use LoggerManager;

/**
 * Base Manufacturing Controller
 * 
 * Provides common functionality for all manufacturing API controllers
 * including error handling, logging, validation, and response formatting.
 * 
 * @author AI Assistant
 * @version 1.0.0
 */
abstract class BaseManufacturingController extends BaseController
{
    /**
     * @var \LoggerManager
     */
    protected $logger;

    /**
     * @var array Common HTTP status codes
     */
    protected const HTTP_STATUS = [
        'OK' => 200,
        'CREATED' => 201,
        'NO_CONTENT' => 204,
        'BAD_REQUEST' => 400,
        'UNAUTHORIZED' => 401,
        'FORBIDDEN' => 403,
        'NOT_FOUND' => 404,
        'METHOD_NOT_ALLOWED' => 405,
        'CONFLICT' => 409,
        'UNPROCESSABLE_ENTITY' => 422,
        'TOO_MANY_REQUESTS' => 429,
        'INTERNAL_SERVER_ERROR' => 500,
        'SERVICE_UNAVAILABLE' => 503
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = LoggerManager::getLogger('manufacturing-api');
    }

    /**
     * Generate success response with proper headers and logging
     *
     * @param HttpResponse $httpResponse
     * @param mixed $data
     * @param int $status
     * @param array $meta
     * @param array $links
     * @return HttpResponse
     */
    protected function generateSuccessResponse(
        HttpResponse $httpResponse,
        $data,
        int $status = self::HTTP_STATUS['OK'],
        array $meta = [],
        array $links = []
    ): HttpResponse {
        $response = [
            'data' => $data
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        if (!empty($links)) {
            $response['links'] = $links;
        }

        // Add API versioning headers
        $httpResponse = $httpResponse->withHeader('API-Version', '8.0.0');
        $httpResponse = $httpResponse->withHeader('X-Manufacturing-API', 'SuiteCRM-Manufacturing');

        // Log successful response
        $this->logger->info("Manufacturing API Response", [
            'status' => $status,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'response_size' => strlen(json_encode($response))
        ]);

        return $this->generateResponse($httpResponse, $response, $status);
    }

    /**
     * Generate error response with consistent format and logging
     *
     * @param HttpResponse $httpResponse
     * @param string $title
     * @param string $detail
     * @param int $status
     * @param string $code
     * @param array $source
     * @return HttpResponse
     */
    protected function generateManufacturingErrorResponse(
        HttpResponse $httpResponse,
        string $title,
        string $detail,
        int $status = self::HTTP_STATUS['INTERNAL_SERVER_ERROR'],
        string $code = null,
        array $source = []
    ): HttpResponse {
        $error = [
            'id' => uniqid('err_'),
            'status' => (string)$status,
            'title' => $title,
            'detail' => $detail
        ];

        if ($code) {
            $error['code'] = $code;
        }

        if (!empty($source)) {
            $error['source'] = $source;
        }

        $response = [
            'errors' => [$error]
        ];

        // Log error with context
        $this->logger->error("Manufacturing API Error", [
            'error_id' => $error['id'],
            'status' => $status,
            'title' => $title,
            'detail' => $detail,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        return $this->generateResponse($httpResponse, $response, $status);
    }

    /**
     * Generate validation error response
     *
     * @param HttpResponse $httpResponse
     * @param array $validationErrors
     * @return HttpResponse
     */
    protected function generateValidationErrorResponse(
        HttpResponse $httpResponse,
        array $validationErrors
    ): HttpResponse {
        $errors = [];
        
        foreach ($validationErrors as $field => $messages) {
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $errors[] = [
                        'id' => uniqid('val_'),
                        'status' => (string)self::HTTP_STATUS['UNPROCESSABLE_ENTITY'],
                        'code' => 'validation_error',
                        'title' => 'Validation Error',
                        'detail' => $message,
                        'source' => [
                            'pointer' => "/data/attributes/{$field}"
                        ]
                    ];
                }
            } else {
                $errors[] = [
                    'id' => uniqid('val_'),
                    'status' => (string)self::HTTP_STATUS['UNPROCESSABLE_ENTITY'],
                    'code' => 'validation_error',
                    'title' => 'Validation Error',
                    'detail' => $messages,
                    'source' => [
                        'pointer' => "/data/attributes/{$field}"
                    ]
                ];
            }
        }

        $response = ['errors' => $errors];

        // Log validation errors
        $this->logger->warning("Manufacturing API Validation Error", [
            'validation_errors' => $validationErrors,
            'endpoint' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]);

        return $this->generateResponse($httpResponse, $response, self::HTTP_STATUS['UNPROCESSABLE_ENTITY']);
    }

    /**
     * Validate required fields in request data
     *
     * @param array $data
     * @param array $requiredFields
     * @return array Validation errors (empty if valid)
     */
    protected function validateRequiredFields(array $data, array $requiredFields): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[$field] = "The {$field} field is required.";
            }
        }

        return $errors;
    }

    /**
     * Sanitize input data
     *
     * @param array $data
     * @return array
     */
    protected function sanitizeInputData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove potentially harmful characters
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInputData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Generate pagination metadata
     *
     * @param int $total
     * @param int $currentPage
     * @param int $perPage
     * @return array
     */
    protected function generatePaginationMeta(int $total, int $currentPage, int $perPage): array
    {
        $totalPages = ceil($total / $perPage);
        
        return [
            'total' => $total,
            'count' => min($perPage, $total - (($currentPage - 1) * $perPage)),
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => $totalPages
        ];
    }

    /**
     * Generate pagination links
     *
     * @param string $baseUrl
     * @param int $currentPage
     * @param int $totalPages
     * @param array $queryParams
     * @return array
     */
    protected function generatePaginationLinks(
        string $baseUrl,
        int $currentPage,
        int $totalPages,
        array $queryParams = []
    ): array {
        $links = [
            'self' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $currentPage])),
            'first' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => 1])),
            'last' => $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $totalPages]))
        ];

        if ($currentPage > 1) {
            $links['prev'] = $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $currentPage - 1]));
        }

        if ($currentPage < $totalPages) {
            $links['next'] = $this->buildUrl($baseUrl, array_merge($queryParams, ['page' => $currentPage + 1]));
        }

        return $links;
    }

    /**
     * Build URL with query parameters
     *
     * @param string $baseUrl
     * @param array $params
     * @return string
     */
    private function buildUrl(string $baseUrl, array $params): string
    {
        if (empty($params)) {
            return $baseUrl;
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    /**
     * Rate limiting check
     *
     * @param string $userId
     * @param int $limit
     * @param int $window
     * @return bool
     */
    protected function checkRateLimit(string $userId, int $limit = 1000, int $window = 3600): bool
    {
        // Implementation would depend on caching system (Redis, etc.)
        // For now, return true (no limiting)
        return true;
    }

    /**
     * Log API access for monitoring
     *
     * @param Request $request
     * @param string $action
     * @param array $metadata
     */
    protected function logApiAccess(Request $request, string $action, array $metadata = []): void
    {
        $this->logger->info("Manufacturing API Access", [
            'action' => $action,
            'method' => $request->getMethod(),
            'uri' => $request->getUri()->getPath(),
            'user_agent' => $request->getHeaderLine('User-Agent'),
            'ip_address' => $request->getAttribute('ip_address'),
            'metadata' => $metadata,
            'timestamp' => date('c')
        ]);
    }

    /**
     * Handle exceptions and convert to appropriate API response
     *
     * @param HttpResponse $httpResponse
     * @param \Exception $exception
     * @return HttpResponse
     */
    protected function handleException(HttpResponse $httpResponse, \Exception $exception): HttpResponse
    {
        // Log the exception
        $this->logger->error("Manufacturing API Exception", [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Determine appropriate HTTP status based on exception type
        $status = self::HTTP_STATUS['INTERNAL_SERVER_ERROR'];
        $title = 'Internal Server Error';
        $detail = 'An unexpected error occurred while processing your request.';

        if ($exception instanceof \InvalidArgumentException) {
            $status = self::HTTP_STATUS['BAD_REQUEST'];
            $title = 'Bad Request';
            $detail = $exception->getMessage();
        } elseif ($exception instanceof \UnexpectedValueException) {
            $status = self::HTTP_STATUS['UNPROCESSABLE_ENTITY'];
            $title = 'Unprocessable Entity';
            $detail = $exception->getMessage();
        }

        return $this->generateManufacturingErrorResponse(
            $httpResponse,
            $title,
            $detail,
            $status
        );
    }
}
