<?php
define('sugarEntry', true);
require_once('include/entryPoint.php');

global $db;

// Create manufacturing_products table
$products_sql = "
CREATE TABLE IF NOT EXISTS manufacturing_products (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) UNIQUE,
    category VARCHAR(100),
    base_price DECIMAL(10,2),
    description TEXT,
    specifications TEXT,
    date_entered DATETIME,
    date_modified DATETIME,
    modified_user_id CHAR(36),
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0
)";

// Create manufacturing_pipeline table
$pipeline_sql = "
CREATE TABLE IF NOT EXISTS manufacturing_pipeline (
    id CHAR(36) NOT NULL PRIMARY KEY,
    order_id VARCHAR(100),
    client_id CHAR(36),
    stage VARCHAR(50),
    priority VARCHAR(20),
    value DECIMAL(12,2),
    expected_close_date DATE,
    probability INT,
    description TEXT,
    date_entered DATETIME,
    date_modified DATETIME,
    modified_user_id CHAR(36),
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0
)";

// Create manufacturing_client_pricing table
$pricing_sql = "
CREATE TABLE IF NOT EXISTS manufacturing_client_pricing (
    id CHAR(36) NOT NULL PRIMARY KEY,
    client_id CHAR(36),
    product_id CHAR(36),
    price DECIMAL(10,2),
    discount_percentage DECIMAL(5,2),
    date_entered DATETIME,
    date_modified DATETIME,
    modified_user_id CHAR(36),
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0
)";

try {
    echo "<h2>Installing Manufacturing Module...</h2>";
    
    // Execute database creation
    $db->query($products_sql);
    echo "✓ Created manufacturing_products table<br>";
    
    $db->query($pipeline_sql);
    echo "✓ Created manufacturing_pipeline table<br>";
    
    $db->query($pricing_sql);
    echo "✓ Created manufacturing_client_pricing table<br>";
    
    // Insert sample products
    $products = array(
        array(
            'id' => create_guid(),
            'name' => 'Industrial Motor 5HP',
            'sku' => 'MOT-5HP-001',
            'category' => 'electrical',
            'base_price' => 1299.99,
            'description' => 'High-efficiency 5HP industrial motor for manufacturing applications'
        ),
        array(
            'id' => create_guid(),
            'name' => 'Precision Ball Bearing Set',
            'sku' => 'BER-PRC-001',
            'category' => 'mechanical',
            'base_price' => 89.50,
            'description' => 'Premium precision ball bearings for high-speed applications'
        ),
        array(
            'id' => create_guid(),
            'name' => 'Pneumatic Wrench Kit',
            'sku' => 'TWL-PNE-001',
            'category' => 'tools',
            'base_price' => 445.00,
            'description' => 'Professional pneumatic wrench kit with multiple attachments'
        )
    );
    
    foreach ($products as $product) {
        $sql = "INSERT INTO manufacturing_products (id, name, sku, category, base_price, description, date_entered, date_modified, deleted) 
               VALUES ('{$product['id']}', '" . $db->quote($product['name']) . "', '{$product['sku']}', '{$product['category']}', 
                      {$product['base_price']}, '" . $db->quote($product['description']) . "', NOW(), NOW(), 0)";
        $db->query($sql);
    }
    echo "✓ Inserted sample products<br>";
    
    // Sample pipeline data
    $pipeline_data = array(
        array(
            'id' => create_guid(),
            'order_id' => 'ORD-2025-001',
            'client_id' => 'sample-client-1',
            'stage' => 'Lead',
            'priority' => 'High',
            'value' => 15000.00,
            'probability' => 25,
            'description' => 'Large order for industrial motors and bearings'
        ),
        array(
            'id' => create_guid(),
            'order_id' => 'ORD-2025-002',
            'client_id' => 'sample-client-2',
            'stage' => 'Qualified',
            'priority' => 'Medium',
            'value' => 8500.00,
            'probability' => 60,
            'description' => 'Tool kit order for maintenance department'
        )
    );
    
    foreach ($pipeline_data as $pipeline) {
        $sql = "INSERT INTO manufacturing_pipeline (id, order_id, client_id, stage, priority, value, probability, description, date_entered, date_modified, deleted) 
               VALUES ('{$pipeline['id']}', '{$pipeline['order_id']}', '{$pipeline['client_id']}', '{$pipeline['stage']}', 
                      '{$pipeline['priority']}', {$pipeline['value']}, {$pipeline['probability']}, '" . $db->quote($pipeline['description']) . "', NOW(), NOW(), 0)";
        $db->query($sql);
    }
    echo "✓ Inserted sample pipeline data<br>";
    
    echo "<br><h3>Installation Complete!</h3>";
    echo "<p>You can now access:</p>";
    echo "<ul>";
    echo "<li><a href='index.php?module=Manufacturing&action=ProductCatalog' target='_blank'>Product Catalog</a></li>";
    echo "<li><a href='index.php?module=Manufacturing&action=OrderDashboard' target='_blank'>Order Dashboard</a></li>";
    echo "</ul>";
    
    echo "<p><strong>Note:</strong> If you still get 'No action by that name' errors, try clearing your browser cache or accessing the URLs directly.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
