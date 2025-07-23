<?php
define('sugarEntry', true);
require_once('include/entryPoint.php');

global $db;

echo "<h2>Database Test Results</h2>";

// Test database connection
echo "<h3>Database Connection Test:</h3>";
try {
    $result = $db->query("SELECT 1");
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test table existence
echo "<h3>Table Existence Test:</h3>";
$tables = ['mfg_products', 'mfg_pricing_tiers', 'mfg_client_contracts', 'mfg_product_pricing', 'mfg_inventory_transactions'];

foreach ($tables as $table) {
    try {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if ($db->getRowCount($result) > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error checking table '$table': " . $e->getMessage() . "<br>";
    }
}

// Test sample data
echo "<h3>Sample Data Test:</h3>";
try {
    $product_count = $db->getOne("SELECT COUNT(*) FROM mfg_products WHERE deleted = 0");
    echo "✅ Products in database: $product_count<br>";
    
    $tier_count = $db->getOne("SELECT COUNT(*) FROM mfg_pricing_tiers WHERE deleted = 0");
    echo "✅ Pricing tiers: $tier_count<br>";
    
    $contract_count = $db->getOne("SELECT COUNT(*) FROM mfg_client_contracts WHERE deleted = 0");
    echo "✅ Client contracts: $contract_count<br>";
    
} catch (Exception $e) {
    echo "❌ Error checking data: " . $e->getMessage() . "<br>";
}

// Test product queries
echo "<h3>Product Query Test:</h3>";
try {
    $steel_products = $db->getOne("SELECT COUNT(*) FROM mfg_products WHERE category = 'Steel' AND deleted = 0");
    echo "✅ Steel products: $steel_products<br>";
    
    $aluminum_products = $db->getOne("SELECT COUNT(*) FROM mfg_products WHERE category = 'Aluminum' AND deleted = 0");
    echo "✅ Aluminum products: $aluminum_products<br>";
    
    $fasteners = $db->getOne("SELECT COUNT(*) FROM mfg_products WHERE category = 'Fasteners' AND deleted = 0");
    echo "✅ Fasteners: $fasteners<br>";
    
} catch (Exception $e) {
    echo "❌ Error in product queries: " . $e->getMessage() . "<br>";
}

// Test pricing tier functionality
echo "<h3>Pricing Tier Test:</h3>";
try {
    $tiers = $db->query("SELECT name, tier_code, discount_percentage FROM mfg_pricing_tiers WHERE deleted = 0 ORDER BY tier_order");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Tier Name</th><th>Code</th><th>Discount %</th></tr>";
    while ($row = $db->fetchByAssoc($tiers)) {
        echo "<tr><td>{$row['name']}</td><td>{$row['tier_code']}</td><td>{$row['discount_percentage']}%</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error in pricing tier test: " . $e->getMessage() . "<br>";
}

// Test sample product display
echo "<h3>Sample Products Test:</h3>";
try {
    $products = $db->query("SELECT name, sku, category, base_price FROM mfg_products WHERE deleted = 0 LIMIT 10");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Product Name</th><th>SKU</th><th>Category</th><th>Base Price</th></tr>";
    while ($row = $db->fetchByAssoc($products)) {
        echo "<tr><td>{$row['name']}</td><td>{$row['sku']}</td><td>{$row['category']}</td><td>$" . number_format($row['base_price'], 2) . "</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error in sample products test: " . $e->getMessage() . "<br>";
}

echo "<br><h3>Quick Access Links:</h3>";
echo "<ul>";
echo "<li><a href='index.php?module=Manufacturing&action=ProductCatalog' target='_blank'>🛒 Product Catalog</a></li>";
echo "<li><a href='index.php?module=Manufacturing&action=OrderDashboard' target='_blank'>📊 Order Dashboard</a></li>";
echo "<li><a href='install_manufacturing_database.php' target='_blank'>🔄 Re-run Installation</a></li>";
echo "</ul>";
