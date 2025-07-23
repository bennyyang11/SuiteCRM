<?php
/**
 * Initialize Product Search Index
 * Updates the search index with current product data
 */

// Database configuration
$db_config = [
    'host' => '127.0.0.1',
    'port' => '3307',
    'database' => 'suitecrm',
    'username' => 'suitecrm', 
    'password' => 'suitecrm'
];

try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h2>Initializing Product Search Index...</h2>\n";
    echo "<pre>\n";
    
    // First check what columns exist in mfg_products
    echo "Checking mfg_products table structure...\n";
    $columns_query = "SHOW COLUMNS FROM mfg_products";
    $columns_result = $pdo->query($columns_query);
    $columns = $columns_result->fetchAll();
    
    $available_columns = array_column($columns, 'Field');
    echo "Available columns: " . implode(', ', $available_columns) . "\n\n";
    
    // Build the initialization query based on available columns
    $select_fields = ['id', 'name', 'sku'];
    $search_text_parts = ['name', 'sku'];
    $search_token_parts = ['name', 'sku'];
    
    if (in_array('description', $available_columns)) {
        $search_text_parts[] = 'COALESCE(description, "")';
        $search_token_parts[] = 'COALESCE(description, "")';
    }
    
    $category_field = 'NULL';
    if (in_array('category', $available_columns)) {
        $category_field = 'category';
    }
    
    $material_field = 'NULL';
    if (in_array('material', $available_columns)) {
        $material_field = 'material';
    }
    
    $specifications_field = 'NULL';
    if (in_array('specifications', $available_columns)) {
        $specifications_field = 'COALESCE(specifications, "")';
    }
    
    $tags_field = 'NULL';
    if (in_array('tags', $available_columns)) {
        $tags_field = 'COALESCE(tags, "")';
        $search_token_parts[] = 'COALESCE(tags, "")';
    }
    
    // Clear existing index
    echo "Clearing existing search index...\n";
    $pdo->exec("DELETE FROM mfg_product_search_index");
    echo "✅ Search index cleared\n\n";
    
    // Build and execute the initialization query
    $search_text = 'CONCAT(' . implode(', " ", ', $search_text_parts) . ')';
    $search_tokens = 'CONCAT(' . implode(', " ", ', $search_token_parts) . ')';
    
    $init_query = "
        INSERT INTO mfg_product_search_index (
            product_id, search_text, search_tokens, category_tokens, 
            material_tokens, specification_tokens, tag_tokens, popularity_score
        )
        SELECT 
            id,
            {$search_text},
            {$search_tokens},
            {$category_field},
            {$material_field},
            {$specifications_field},
            {$tags_field},
            0
        FROM mfg_products 
        WHERE deleted = 0 AND status = 'active'
    ";
    
    echo "Executing search index initialization...\n";
    echo "Query: " . preg_replace('/\s+/', ' ', $init_query) . "\n\n";
    
    $result = $pdo->exec($init_query);
    echo "✅ Product search index initialized with {$result} products\n\n";
    
    // Verify the results
    echo "Verifying search index...\n";
    $verify_query = "SELECT COUNT(*) as total, COUNT(DISTINCT product_id) as unique_products FROM mfg_product_search_index";
    $verify_result = $pdo->query($verify_query)->fetch();
    
    echo "Total index records: {$verify_result['total']}\n";
    echo "Unique products indexed: {$verify_result['unique_products']}\n\n";
    
    // Show sample indexed data
    echo "Sample indexed products:\n";
    $sample_query = "
        SELECT 
            psi.product_id,
            p.name,
            p.sku,
            LEFT(psi.search_text, 100) as search_text_preview
        FROM mfg_product_search_index psi
        JOIN mfg_products p ON psi.product_id = p.id
        LIMIT 5
    ";
    
    $sample_result = $pdo->query($sample_query);
    while ($row = $sample_result->fetch()) {
        echo "- {$row['name']} ({$row['sku']}): {$row['search_text_preview']}...\n";
    }
    
    echo "\n🎉 Search index initialization completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
