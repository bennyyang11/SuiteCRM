<?php
/**
 * Direct test of ProductSuggestionEngine
 */

require_once('modules/Manufacturing/ProductSuggestionEngine.php');

echo "=== Direct ProductSuggestionEngine Test ===\n";

try {
    $engine = new ProductSuggestionEngine();
    echo "✓ Engine initialized successfully\n";
    
    // Get a sample product
    require_once('config.php');
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
    
    if ($product) {
        echo "✓ Found test product: " . $product['name'] . "\n";
        
        echo "Getting suggestions...\n";
        $suggestions = $engine->getSuggestions($product['id'], [
            'max_suggestions' => 3
        ]);
        
        if (isset($suggestions['error'])) {
            echo "✗ Error: " . $suggestions['error'] . "\n";
        } else {
            echo "✓ Got suggestions successfully\n";
            echo "Base product: " . $suggestions['base_product']['name'] . "\n";
            echo "Suggestions found: " . count($suggestions['suggestions']) . "\n";
            
            foreach ($suggestions['suggestions'] as $i => $suggestion) {
                echo sprintf("  %d. %s (Type: %s, Score: %.2f)\n", 
                    $i + 1,
                    $suggestion['product']['name'],
                    $suggestion['suggestion_type'],
                    $suggestion['relevance_score']
                );
            }
        }
    } else {
        echo "✗ No products found in database\n";
    }
    
    $db->close();
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
