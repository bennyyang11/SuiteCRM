<?php
/**
 * API Router for Manufacturing Module
 * Routes API requests to appropriate handlers
 */

define('sugarEntry', true);
require_once('config.php');
require_once('include/entryPoint.php');

// Get request URI and method
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Parse the API route
$route = parse_url($requestUri, PHP_URL_PATH);
$route = str_replace('/api/v8/', '', $route);
$routeParts = explode('/', trim($route, '/'));

// Initialize response handler
require_once('Api/V8/Common/ResponseHandler.php');
use Api\V8\Common\ResponseHandler;

$logger = new SugarLogger();
$responseHandler = new ResponseHandler($logger);

try {
    // Route to manufacturing endpoints
    if (!empty($routeParts[0]) && $routeParts[0] === 'manufacturing') {
        
        // Remove 'manufacturing' from route parts
        array_shift($routeParts);
        
        $resource = $routeParts[0] ?? '';
        $id = $routeParts[1] ?? null;
        $action = $routeParts[2] ?? null;
        
        switch ($resource) {
            case 'products':
                require_once('api_handlers/products.php');
                handleProductsAPI($method, $id, $action, $responseHandler);
                break;
                
            case 'orders':
                require_once('api_handlers/orders.php');
                handleOrdersAPI($method, $id, $action, $responseHandler);
                break;
                
            case 'quotes':
                require_once('api_handlers/quotes.php');
                handleQuotesAPI($method, $id, $action, $responseHandler);
                break;
                
            case 'inventory':
                require_once('api_handlers/inventory.php');
                handleInventoryAPI($method, $id, $action, $responseHandler);
                break;
                
            case 'pricing':
                require_once('api_handlers/pricing.php');
                handlePricingAPI($method, $id, $action, $responseHandler);
                break;
                
            case 'analytics':
                require_once('api_handlers/analytics.php');
                handleAnalyticsAPI($method, $id, $action, $responseHandler);
                break;
                
            default:
                $responseHandler->notFound('API endpoint');
        }
        
    } else {
        // Handle other API routes or pass to legacy SuiteCRM API
        $responseHandler->notFound('API endpoint');
    }
    
} catch (Exception $e) {
    $responseHandler->serverError('API request failed', $e);
}

// Helper function to handle products API (basic implementation)
function handleProductsAPI($method, $id, $action, $responseHandler) {
    switch ($method) {
        case 'GET':
            if ($id) {
                // Get single product
                $responseHandler->success([
                    'id' => $id,
                    'name' => 'Sample Product',
                    'sku' => 'PROD-001',
                    'price' => 299.99,
                    'stock' => 50
                ]);
            } else {
                // Get products list
                $responseHandler->success([
                    [
                        'id' => '1',
                        'name' => 'Industrial Bearing Set',
                        'sku' => 'IBS-001',
                        'price' => 299.99,
                        'stock' => 50
                    ],
                    [
                        'id' => '2', 
                        'name' => 'Hydraulic Pump',
                        'sku' => 'HP-002',
                        'price' => 1299.99,
                        'stock' => 25
                    ]
                ]);
            }
            break;
            
        default:
            $responseHandler->methodNotAllowed(['GET']);
    }
}
