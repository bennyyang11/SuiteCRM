<?php
/**
 * Product Suggestions API
 * Manufacturing Distribution Platform - Feature 3
 * 
 * API endpoint for intelligent product suggestions
 */

require_once('modules/Manufacturing/ProductSuggestionEngine.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $engine = new ProductSuggestionEngine();
    
    switch ($action) {
        case 'get_suggestions':
            getSuggestions($engine);
            break;
        case 'get_stats':
            getSuggestionStats($engine);
            break;
        case 'clear_cache':
            clearSuggestionCache($engine);
            break;
        default:
            sendError(404, "Action not found. Available actions: get_suggestions, get_stats, clear_cache");
    }

} catch (Exception $e) {
    sendError(500, "Internal error: " . $e->getMessage() . " | " . $e->getTraceAsString());
}

/**
 * Get product suggestions
 */
function getSuggestions($engine) {
    $product_id = $_GET['product_id'] ?? '';
    
    if (empty($product_id)) {
        sendError(400, "product_id parameter required");
    }
    
    $options = [
        'customer_id' => $_GET['customer_id'] ?? null,
        'warehouse_id' => $_GET['warehouse_id'] ?? null,
        'price_range_percent' => intval($_GET['price_range_percent'] ?? 30),
        'max_suggestions' => intval($_GET['max_suggestions'] ?? 10),
        'include_out_of_stock' => ($_GET['include_out_of_stock'] ?? 'false') === 'true',
        'suggestion_types' => explode(',', $_GET['suggestion_types'] ?? 'similar,alternative,cross_sell,upsell'),
        'min_relevance_score' => floatval($_GET['min_relevance_score'] ?? 0.3)
    ];
    
    $suggestions = $engine->getSuggestions($product_id, $options);
    
    if (isset($suggestions['error'])) {
        sendError(404, $suggestions['error']);
    }
    
    sendSuccess($suggestions);
}

/**
 * Get suggestion engine statistics
 */
function getSuggestionStats($engine) {
    $stats = $engine->getSuggestionStats();
    sendSuccess($stats);
}

/**
 * Clear suggestion cache
 */
function clearSuggestionCache($engine) {
    $product_id = $_GET['product_id'] ?? null;
    $engine->clearCache($product_id);
    
    sendSuccess([
        'message' => $product_id ? "Cache cleared for product $product_id" : "All cache cleared",
        'cleared_at' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Utility functions
 */
function sendSuccess($data) {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}

function sendError($code, $message) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}
?>
