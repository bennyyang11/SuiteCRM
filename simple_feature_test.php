<?php
header('Content-Type: text/html; charset=UTF-8');
echo "<h1>SuiteCRM Manufacturing Features Test</h1>";

// Suppress deprecation warnings for testing
error_reporting(E_ERROR | E_WARNING);

echo "<h2>Feature 1: Product Catalog API Test</h2>";
$start = microtime(true);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/Api/v1/manufacturing/ProductCatalogAPI.php?action=search&query=steel&limit=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$time = microtime(true) - $start;
curl_close($ch);

echo "<p><strong>API Response Time:</strong> " . round($time, 3) . "s</p>";
echo "<p><strong>HTTP Status:</strong> $httpCode</p>";

if ($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['products'])) {
        echo "<p><strong>Products Found:</strong> " . count($data['products']) . "</p>";
        echo "<p><strong>Sample Product:</strong> " . $data['products'][0]['name'] . " - $" . $data['products'][0]['price'] . "</p>";
        echo "<p style='color: green;'>✅ Product Catalog API Working</p>";
    } else {
        echo "<p style='color: red;'>❌ Product Catalog API Failed</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ No response from Product Catalog API</p>";
}

echo "<h2>Feature 2: Order Pipeline API Test</h2>";
$start = microtime(true);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:3000/Api/v1/manufacturing/OrderPipelineAPI.php?action=get_dashboard');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$time = microtime(true) - $start;
curl_close($ch);

echo "<p><strong>API Response Time:</strong> " . round($time, 3) . "s</p>";
echo "<p><strong>HTTP Status:</strong> $httpCode</p>";

if ($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['pipeline_data'])) {
        echo "<p><strong>Pipeline Stages:</strong> " . count($data['pipeline_data']) . "</p>";
        echo "<p style='color: green;'>✅ Order Pipeline API Working</p>";
    } else {
        echo "<p style='color: red;'>❌ Order Pipeline API Failed</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ No response from Order Pipeline API</p>";
}

echo "<h2>Database Connection Test</h2>";
try {
    $pdo = new PDO('mysql:host=localhost;port=3307;dbname=suitecrm', 'root', 'root123');
    echo "<p style='color: green;'>✅ Database Connection Working</p>";
    
    // Test if our tables exist
    $tables = ['mfg_products', 'mfg_pricing_tiers', 'mfg_order_pipeline'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table $table exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table $table missing</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Connection Failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Mobile Interface Test</h2>";
if (file_exists('themes/SuiteP/tpls/mobile_product_catalog.tpl')) {
    echo "<p style='color: green;'>✅ Mobile Product Catalog Template Exists</p>";
} else {
    echo "<p style='color: red;'>❌ Mobile Product Catalog Template Missing</p>";
}

if (file_exists('themes/SuiteP/tpls/mobile_dashboard.tpl')) {
    echo "<p style='color: green;'>✅ Mobile Dashboard Template Exists</p>";
} else {
    echo "<p style='color: red;'>❌ Mobile Dashboard Template Missing</p>";
}

echo "<h2>Summary</h2>";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
