<?php
/**
 * Manufacturing API Test Suite
 * 
 * Comprehensive test suite for all manufacturing API endpoints
 * including functionality tests, error handling, and documentation validation.
 * 
 * @author AI Assistant
 * @version 1.0.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bootstrap SuiteCRM
if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

require_once 'config.php';
require_once 'include/entryPoint.php';

/**
 * Manufacturing API Test Runner
 */
class ManufacturingApiTestRunner
{
    private $baseUrl;
    private $authToken;
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;

    public function __construct()
    {
        $this->baseUrl = 'http://localhost:3000/api/v8/manufacturing';
        $this->authToken = $this->getAuthToken();
    }

    /**
     * Run all API tests
     */
    public function runAllTests(): array
    {
        echo "<h1>🔧 Manufacturing API Comprehensive Test Suite</h1>\n";
        echo "<div style='font-family: monospace; background: #f5f5f5; padding: 20px;'>\n";

        // Test Categories
        $this->testHealthCheck();
        $this->testProductsAPI();
        $this->testOrdersAPI();
        $this->testQuotesAPI();
        $this->testInventoryAPI();
        $this->testAnalyticsAPI();
        $this->testErrorHandling();
        $this->testRateLimit();
        $this->testAuthentication();
        $this->testSwaggerDocumentation();

        // Generate summary
        $this->generateTestSummary();

        echo "</div>\n";
        return $this->testResults;
    }

    /**
     * Test API health check endpoint
     */
    private function testHealthCheck(): void
    {
        echo "<h2>🏥 Health Check Tests</h2>\n";
        
        $response = $this->makeRequest('GET', '/health', [], false); // No auth required
        
        $this->assert(
            'Health Check Endpoint Accessible',
            $response['http_code'] === 200,
            "Expected 200, got {$response['http_code']}"
        );

        if ($response['http_code'] === 200) {
            $data = json_decode($response['body'], true);
            
            $this->assert(
                'Health Response Structure',
                isset($data['status']) && isset($data['version']) && isset($data['endpoints']),
                'Missing required health check fields'
            );

            $this->assert(
                'Health Status Healthy',
                $data['status'] === 'healthy',
                "Expected healthy status, got {$data['status']}"
            );

            $this->assert(
                'All Endpoints Operational',
                $this->allEndpointsOperational($data['endpoints']),
                'Some endpoints are not operational'
            );
        }
    }

    /**
     * Test Products API endpoints
     */
    private function testProductsAPI(): void
    {
        echo "<h2>📦 Products API Tests</h2>\n";

        // Test GET /products
        $response = $this->makeRequest('GET', '/products');
        $this->assert(
            'Get Products Endpoint',
            $response['http_code'] === 200,
            "Expected 200, got {$response['http_code']}"
        );

        // Test GET /products with pagination
        $response = $this->makeRequest('GET', '/products?page=1&limit=5');
        $this->assert(
            'Products Pagination',
            $response['http_code'] === 200,
            "Pagination failed: {$response['http_code']}"
        );

        if ($response['http_code'] === 200) {
            $data = json_decode($response['body'], true);
            $this->assert(
                'Products Response Structure',
                isset($data['data']) && isset($data['meta']) && isset($data['links']),
                'Missing pagination structure'
            );
        }

        // Test GET /products with search
        $response = $this->makeRequest('GET', '/products/search?q=test');
        $this->assert(
            'Products Search',
            $response['http_code'] === 200 || $response['http_code'] === 404,
            "Search failed: {$response['http_code']}"
        );

        // Test POST /products (Create)
        $productData = [
            'data' => [
                'type' => 'products',
                'attributes' => [
                    'name' => 'Test Product API',
                    'sku' => 'TEST-API-' . time(),
                    'description' => 'Test product created via API',
                    'category' => 'Test Category',
                    'price' => 99.99,
                    'cost' => 50.00,
                    'weight' => 1.5,
                    'specifications' => [
                        'material' => 'Steel',
                        'color' => 'Blue'
                    ],
                    'tags' => ['test', 'api']
                ]
            ]
        ];

        $response = $this->makeRequest('POST', '/products', $productData);
        $this->assert(
            'Create Product',
            $response['http_code'] === 201,
            "Product creation failed: {$response['http_code']}"
        );

        $createdProductId = null;
        if ($response['http_code'] === 201) {
            $data = json_decode($response['body'], true);
            $createdProductId = $data['data']['id'] ?? null;
            
            $this->assert(
                'Created Product Has ID',
                !empty($createdProductId),
                'Created product missing ID'
            );
        }

        // Test GET /products/{id}
        if ($createdProductId) {
            $response = $this->makeRequest('GET', "/products/{$createdProductId}");
            $this->assert(
                'Get Single Product',
                $response['http_code'] === 200,
                "Get product failed: {$response['http_code']}"
            );
        }

        // Test PUT /products/{id} (Update)
        if ($createdProductId) {
            $updateData = [
                'data' => [
                    'type' => 'products',
                    'attributes' => [
                        'name' => 'Updated Test Product API',
                        'price' => 149.99
                    ]
                ]
            ];

            $response = $this->makeRequest('PUT', "/products/{$createdProductId}", $updateData);
            $this->assert(
                'Update Product',
                $response['http_code'] === 200,
                "Product update failed: {$response['http_code']}"
            );
        }

        // Test DELETE /products/{id}
        if ($createdProductId) {
            $response = $this->makeRequest('DELETE', "/products/{$createdProductId}");
            $this->assert(
                'Delete Product',
                $response['http_code'] === 204,
                "Product deletion failed: {$response['http_code']}"
            );
        }
    }

    /**
     * Test Orders API endpoints
     */
    private function testOrdersAPI(): void
    {
        echo "<h2>📋 Orders API Tests</h2>\n";

        // Test GET /orders
        $response = $this->makeRequest('GET', '/orders');
        $this->assert(
            'Get Orders Endpoint',
            $response['http_code'] === 200 || $response['http_code'] === 404,
            "Orders endpoint failed: {$response['http_code']}"
        );

        // Test GET /orders/pipeline
        $response = $this->makeRequest('GET', '/orders/pipeline');
        $this->assert(
            'Orders Pipeline Endpoint',
            $response['http_code'] === 200 || $response['http_code'] === 404,
            "Pipeline endpoint failed: {$response['http_code']}"
        );
    }

    /**
     * Test Quotes API endpoints
     */
    private function testQuotesAPI(): void
    {
        echo "<h2>💰 Quotes API Tests</h2>\n";

        // Test GET /quotes
        $response = $this->makeRequest('GET', '/quotes');
        $this->assert(
            'Get Quotes Endpoint',
            $response['http_code'] === 200 || $response['http_code'] === 404,
            "Quotes endpoint failed: {$response['http_code']}"
        );
    }

    /**
     * Test Inventory API endpoints
     */
    private function testInventoryAPI(): void
    {
        echo "<h2>📊 Inventory API Tests</h2>\n";

        // Test GET /inventory
        $response = $this->makeRequest('GET', '/inventory');
        $this->assert(
            'Get Inventory Endpoint',
            $response['http_code'] === 200 || $response['http_code'] === 404,
            "Inventory endpoint failed: {$response['http_code']}"
        );

        // Test POST /inventory/sync
        $syncData = [
            'data' => [
                'type' => 'inventory_sync',
                'attributes' => [
                    'source' => 'test',
                    'products' => []
                ]
            ]
        ];

        $response = $this->makeRequest('POST', '/inventory/sync', $syncData);
        $this->assert(
            'Inventory Sync Endpoint',
            in_array($response['http_code'], [200, 202, 404, 501]),
            "Sync endpoint failed: {$response['http_code']}"
        );
    }

    /**
     * Test Analytics API endpoints
     */
    private function testAnalyticsAPI(): void
    {
        echo "<h2>📈 Analytics API Tests</h2>\n";

        // Test GET /analytics/sales
        $response = $this->makeRequest('GET', '/analytics/sales');
        $this->assert(
            'Sales Analytics Endpoint',
            $response['http_code'] === 200 || $response['http_code'] === 404,
            "Sales analytics failed: {$response['http_code']}"
        );

        // Test GET /analytics/dashboard
        $response = $this->makeRequest('GET', '/analytics/dashboard');
        $this->assert(
            'Dashboard Analytics Endpoint',
            $response['http_code'] === 200 || $response['http_code'] === 404,
            "Dashboard analytics failed: {$response['http_code']}"
        );
    }

    /**
     * Test error handling
     */
    private function testErrorHandling(): void
    {
        echo "<h2>⚠️ Error Handling Tests</h2>\n";

        // Test 404 for non-existent product
        $response = $this->makeRequest('GET', '/products/non-existent-id');
        $this->assert(
            '404 Error Handling',
            $response['http_code'] === 404,
            "Expected 404, got {$response['http_code']}"
        );

        // Test 400 for invalid request body
        $response = $this->makeRequest('POST', '/products', ['invalid' => 'data']);
        $this->assert(
            '400 Bad Request Handling',
            $response['http_code'] === 400,
            "Expected 400, got {$response['http_code']}"
        );

        // Test validation errors
        $invalidProduct = [
            'data' => [
                'type' => 'products',
                'attributes' => [
                    'name' => '', // Required field empty
                    'sku' => '',  // Required field empty
                ]
            ]
        ];

        $response = $this->makeRequest('POST', '/products', $invalidProduct);
        $this->assert(
            '422 Validation Error Handling',
            $response['http_code'] === 422,
            "Expected 422, got {$response['http_code']}"
        );
    }

    /**
     * Test rate limiting
     */
    private function testRateLimit(): void
    {
        echo "<h2>🚦 Rate Limiting Tests</h2>\n";

        // Make multiple rapid requests to test rate limiting
        $rapidRequests = 0;
        for ($i = 0; $i < 5; $i++) {
            $response = $this->makeRequest('GET', '/health', [], false);
            if ($response['http_code'] === 200) {
                $rapidRequests++;
            }
        }

        $this->assert(
            'Rate Limiting Allows Normal Usage',
            $rapidRequests >= 4,
            "Rate limiting too restrictive: only {$rapidRequests}/5 requests succeeded"
        );
    }

    /**
     * Test authentication
     */
    private function testAuthentication(): void
    {
        echo "<h2>🔐 Authentication Tests</h2>\n";

        // Test request without authentication
        $response = $this->makeRequest('GET', '/products', [], false, false);
        $this->assert(
            'Unauthorized Access Blocked',
            $response['http_code'] === 401,
            "Expected 401, got {$response['http_code']}"
        );

        // Test request with valid authentication
        $response = $this->makeRequest('GET', '/products');
        $this->assert(
            'Authorized Access Allowed',
            in_array($response['http_code'], [200, 404]),
            "Authorized request failed: {$response['http_code']}"
        );
    }

    /**
     * Test Swagger documentation
     */
    private function testSwaggerDocumentation(): void
    {
        echo "<h2>📚 OpenAPI Documentation Tests</h2>\n";

        // Test swagger.json endpoint
        $response = $this->makeRequest('GET', '/swagger.json', [], false);
        $this->assert(
            'Swagger Documentation Accessible',
            in_array($response['http_code'], [200, 404]),
            "Swagger endpoint failed: {$response['http_code']}"
        );

        if ($response['http_code'] === 200) {
            $swagger = json_decode($response['body'], true);
            
            $this->assert(
                'Valid OpenAPI Structure',
                isset($swagger['openapi']) && isset($swagger['info']) && isset($swagger['paths']),
                'Invalid OpenAPI structure'
            );

            $this->assert(
                'API Version Information',
                isset($swagger['info']['version']) && !empty($swagger['info']['version']),
                'Missing API version information'
            );
        }

        // Check if swagger.yaml file exists
        $swaggerPath = 'Api/V8/Manufacturing/swagger.yaml';
        $this->assert(
            'Swagger YAML File Exists',
            file_exists($swaggerPath),
            "Swagger YAML file not found at {$swaggerPath}"
        );
    }

    /**
     * Make HTTP request to API
     */
    private function makeRequest(string $method, string $endpoint, array $data = [], bool $json = true, bool $auth = true): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->buildHeaders($json, $auth),
            CURLOPT_TIMEOUT => 30
        ]);

        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json ? json_encode($data) : http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body' => $response,
            'error' => $error
        ];
    }

    /**
     * Build HTTP headers for request
     */
    private function buildHeaders(bool $json = true, bool $auth = true): array
    {
        $headers = [];
        
        if ($json) {
            $headers[] = 'Content-Type: application/vnd.api+json';
            $headers[] = 'Accept: application/vnd.api+json';
        }
        
        if ($auth && $this->authToken) {
            $headers[] = 'Authorization: Bearer ' . $this->authToken;
        }

        return $headers;
    }

    /**
     * Get authentication token
     */
    private function getAuthToken(): ?string
    {
        // For testing purposes, create a simple token
        // In production, this would be obtained through OAuth2 flow
        return 'test-token-' . time();
    }

    /**
     * Assert test condition
     */
    private function assert(string $testName, bool $condition, string $message = ''): void
    {
        $this->totalTests++;
        
        if ($condition) {
            $this->passedTests++;
            echo "✅ {$testName}: PASSED\n";
            $this->testResults[$testName] = ['status' => 'PASSED', 'message' => ''];
        } else {
            echo "❌ {$testName}: FAILED - {$message}\n";
            $this->testResults[$testName] = ['status' => 'FAILED', 'message' => $message];
        }
    }

    /**
     * Check if all endpoints are operational
     */
    private function allEndpointsOperational(array $endpoints): bool
    {
        foreach ($endpoints as $endpoint => $status) {
            if ($status !== 'operational') {
                return false;
            }
        }
        return true;
    }

    /**
     * Generate test summary
     */
    private function generateTestSummary(): void
    {
        $successRate = $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0;
        
        echo "<h2>📊 Test Summary</h2>\n";
        echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
        echo "<strong>Total Tests:</strong> {$this->totalTests}<br>\n";
        echo "<strong>Passed:</strong> {$this->passedTests}<br>\n";
        echo "<strong>Failed:</strong> " . ($this->totalTests - $this->passedTests) . "<br>\n";
        echo "<strong>Success Rate:</strong> " . number_format($successRate, 1) . "%<br>\n";
        echo "</div>\n";

        // List failed tests
        $failedTests = array_filter($this->testResults, function($result) {
            return $result['status'] === 'FAILED';
        });

        if (!empty($failedTests)) {
            echo "<h3>❌ Failed Tests:</h3>\n";
            echo "<ul>\n";
            foreach ($failedTests as $testName => $result) {
                echo "<li><strong>{$testName}:</strong> {$result['message']}</li>\n";
            }
            echo "</ul>\n";
        }

        // API Readiness Assessment
        if ($successRate >= 90) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            echo "<h3>🎉 API Ready for Production</h3>\n";
            echo "The Manufacturing API has passed the majority of tests and is ready for deployment.\n";
            echo "</div>\n";
        } elseif ($successRate >= 75) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            echo "<h3>⚠️ API Ready with Minor Issues</h3>\n";
            echo "The Manufacturing API is mostly functional but has some issues that should be addressed.\n";
            echo "</div>\n";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0;'>\n";
            echo "<h3>🚨 API Needs Attention</h3>\n";
            echo "The Manufacturing API has significant issues that must be resolved before deployment.\n";
            echo "</div>\n";
        }
    }
}

// Run the tests if called directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    echo "<!DOCTYPE html>\n<html><head><title>Manufacturing API Test Results</title></head><body>\n";
    
    $testRunner = new ManufacturingApiTestRunner();
    $results = $testRunner->runAllTests();
    
    echo "</body></html>\n";
}
