<?php
define('sugarEntry', true);
require_once('include/entryPoint.php');

global $db;

echo "<h2>Installing Manufacturing Database Schema and Sample Data...</h2>";

try {
    // Read and execute schema file
    $schema_sql = file_get_contents('database/manufacturing_database_schema.sql');
    $schema_statements = explode(';', $schema_sql);
    
    echo "<h3>Creating Database Tables:</h3><ul>";
    foreach ($schema_statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $db->query($statement);
            if (preg_match('/CREATE TABLE.*?(\w+)/', $statement, $matches)) {
                echo "<li>✓ Created table: {$matches[1]}</li>";
            }
        }
    }
    echo "</ul>";
    
    // Read and execute sample data file
    $data_sql = file_get_contents('database/manufacturing_sample_data.sql');
    $data_statements = explode(';', $data_sql);
    
    echo "<h3>Populating Sample Data:</h3><ul>";
    $insert_count = 0;
    foreach ($data_statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            if (stripos($statement, 'INSERT INTO') === 0) {
                $db->query($statement);
                $insert_count++;
                if (preg_match('/INSERT INTO (\w+)/', $statement, $matches)) {
                    echo "<li>✓ Inserted data into: {$matches[1]}</li>";
                }
            }
        }
    }
    echo "</ul>";
    
    // Verify data counts
    echo "<h3>Data Verification:</h3><ul>";
    
    $product_count = $db->getOne("SELECT COUNT(*) FROM mfg_products WHERE deleted = 0");
    echo "<li>✓ Products created: {$product_count}</li>";
    
    $tier_count = $db->getOne("SELECT COUNT(*) FROM mfg_pricing_tiers WHERE deleted = 0");
    echo "<li>✓ Pricing tiers created: {$tier_count}</li>";
    
    $contract_count = $db->getOne("SELECT COUNT(*) FROM mfg_client_contracts WHERE deleted = 0");
    echo "<li>✓ Client contracts created: {$contract_count}</li>";
    
    $pricing_count = $db->getOne("SELECT COUNT(*) FROM mfg_product_pricing WHERE deleted = 0");
    echo "<li>✓ Product pricing overrides: {$pricing_count}</li>";
    
    $transaction_count = $db->getOne("SELECT COUNT(*) FROM mfg_inventory_transactions WHERE deleted = 0");
    echo "<li>✓ Inventory transactions: {$transaction_count}</li>";
    
    echo "</ul>";
    
    // Show sample product data
    echo "<h3>Sample Product Categories:</h3><ul>";
    $categories = $db->query("SELECT category, COUNT(*) as count FROM mfg_products WHERE deleted = 0 GROUP BY category ORDER BY category");
    while ($row = $db->fetchByAssoc($categories)) {
        echo "<li>{$row['category']}: {$row['count']} products</li>";
    }
    echo "</ul>";
    
    echo "<h3>Sample Pricing Tiers:</h3><ul>";
    $tiers = $db->query("SELECT name, tier_code, discount_percentage FROM mfg_pricing_tiers WHERE deleted = 0 ORDER BY tier_order");
    while ($row = $db->fetchByAssoc($tiers)) {
        echo "<li>{$row['name']} ({$row['tier_code']}): {$row['discount_percentage']}% discount</li>";
    }
    echo "</ul>";
    
    echo "<div style='background:#d4edda; border:1px solid #c3e6cb; padding:15px; margin:20px 0; border-radius:5px;'>";
    echo "<h3 style='color:#155724; margin-top:0;'>✅ Installation Complete!</h3>";
    echo "<p><strong>Database Setup Summary:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Created mfg_products table with SKU, pricing, inventory fields</li>";
    echo "<li>✅ Created mfg_pricing_tiers table (Retail, Wholesale, OEM, etc.)</li>";
    echo "<li>✅ Created mfg_client_contracts table for negotiated pricing</li>";
    echo "<li>✅ Populated sample product data ({$product_count}+ products)</li>";
    echo "</ul>";
    echo "<p><strong>What's included:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Steel Products:</strong> 15 items (bars, sheets, tubes, beams, etc.)</li>";
    echo "<li><strong>Aluminum Products:</strong> 12 items (extrusions, sheets, plates, etc.)</li>";
    echo "<li><strong>Fasteners:</strong> 15 items (bolts, screws, nuts, washers, etc.)</li>";
    echo "<li><strong>Tools & Equipment:</strong> 10 items (precision tools, power tools, etc.)</li>";
    echo "<li><strong>Electrical Components:</strong> 8 items (conduit, wire, breakers, etc.)</li>";
    echo "</ul>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ul>";
    echo "<li>Access Product Catalog: <a href='index.php?module=Manufacturing&action=ProductCatalog' target='_blank'>Product Catalog</a></li>";
    echo "<li>View Order Dashboard: <a href='index.php?module=Manufacturing&action=OrderDashboard' target='_blank'>Order Dashboard</a></li>";
    echo "<li>Test client-specific pricing functionality</li>";
    echo "<li>Verify mobile responsiveness on different devices</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da; border:1px solid #f5c6cb; padding:15px; margin:20px 0; border-radius:5px;'>";
    echo "<h3 style='color:#721c24; margin-top:0;'>❌ Installation Error</h3>";
    echo "<p style='color:#721c24;'>Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}
