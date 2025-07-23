<?php
/**
 * Manufacturing API Endpoint Handler
 * Enterprise Legacy Modernization Project
 * 
 * Main entry point for Manufacturing Product Catalog API
 */

define('sugarEntry', true);
require_once('include/entryPoint.php');
require_once('Api/v1/manufacturing/ProductCatalogAPI.php');

// Set headers for API response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Initialize API
    $api = new ProductCatalogAPI();
    
    // Get request method and path
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['PATH_INFO'] ?? $_GET['endpoint'] ?? '';
    
    // Remove leading slash
    $path = ltrim($path, '/');
    
    // Get request parameters
    $params = [];
    
    switch ($method) {
        case 'GET':
            $params = $_GET;
            break;
        case 'POST':
        case 'PUT':
            $input = file_get_contents('php://input');
            $params = json_decode($input, true) ?? [];
            // Merge with GET params for hybrid requests
            $params = array_merge($_GET, $params);
            break;
    }
    
    // Remove endpoint from params
    unset($params['endpoint']);
    
    // Route to appropriate endpoint
    if (empty($path)) {
        // Default: API info/health check
        $response = $api->getApiInfo();
        http_response_code(200);
    } else {
        // Handle API request
        $response = $api->handleRequest($method, $path, $params);
        
        // Set appropriate HTTP status code
        if ($response['status'] === 'error') {
            http_response_code($response['error']['code'] ?? 400);
        } else {
            http_response_code(200);
        }
    }
    
    // Output JSON response
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Handle unexpected errors
    error_log('Manufacturing API Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => [
            'code' => 500,
            'message' => 'Internal server error',
            'details' => $e->getMessage()
        ],
        'meta' => [
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);
}
