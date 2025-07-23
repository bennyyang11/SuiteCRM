<?php
/**
 * Test Product Suggestions API
 */

require_once('config.php');

// Get a sample product ID
global $sugar_config;
$host = str_replace(':3307', '', $sugar_config['dbconfig']['db_host_name']);
$db = new mysqli(
    $host,
    $sugar_config['dbconfig']['db_user_name'],
    $sugar_config['dbconfig']['db_password'],
    $sugar_config['dbconfig']['db_name'],
    3307
);

$result = $db->query("SELECT id, name FROM mfg_products WHERE deleted = 0 LIMIT 1");
$product = $result->fetch_assoc();

if (!$product) {
    echo "No products found for testing.\n";
    exit(1);
}

$product_id = $product['id'];
$product_name = $product['name'];

echo "=== Product Suggestions API Test ===\n";
echo "Testing with Product: $product_name (ID: $product_id)\n\n";

// Test the suggestions API
$url = "http://localhost:3000/product_suggestions_api.php?action=get_suggestions&product_id=$product_id&max_suggestions=5";

echo "Testing URL: $url\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "CURL Error: $error\n";
    exit(1);
}

echo "HTTP Response Code: $http_code\n";
echo "Response:\n";
echo $response . "\n";

// Parse and display formatted results
$data = json_decode($response, true);

if ($data && $data['success'] && isset($data['data']['suggestions'])) {
    $suggestions = $data['data']['suggestions'];
    
    echo "\n=== Formatted Results ===\n";
    echo "Base Product: " . $data['data']['base_product']['name'] . "\n";
    echo "Total Suggestions Found: " . count($suggestions) . "\n\n";
    
    foreach ($suggestions as $i => $suggestion) {
        echo sprintf("Suggestion #%d:\n", $i + 1);
        echo sprintf("  Product: %s (%s)\n", 
            $suggestion['product']['name'], 
            $suggestion['product']['sku']
        );
        echo sprintf("  Type: %s\n", $suggestion['suggestion_type']);
        echo sprintf("  Reason: %s\n", $suggestion['reason']);
        echo sprintf("  Relevance Score: %.2f\n", $suggestion['relevance_score']);
        echo sprintf("  Price: $%.2f\n", $suggestion['product']['unit_price']);
        echo sprintf("  Available Stock: %s\n", number_format($suggestion['product']['available_stock']));
        echo sprintf("  Warehouse: %s (%s)\n", 
            $suggestion['product']['warehouse_name'], 
            $suggestion['product']['warehouse_code']
        );
        echo "\n";
    }
} else {
    echo "No suggestions found or API error.\n";
}

$db->close();
?>
