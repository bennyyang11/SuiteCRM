<?php
/**
 * Manufacturing API Test Suite
 * Enterprise Legacy Modernization Project
 */

define('sugarEntry', true);
require_once('include/entryPoint.php');

echo "<h1>Manufacturing API Test Suite</h1>";
echo "<style>
.test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
.success { background-color: #d4edda; border-color: #c3e6cb; }
.error { background-color: #f8d7da; border-color: #f5c6cb; }
.info { background-color: #d1ecf1; border-color: #bee5eb; }
pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
.endpoint { font-family: monospace; background: #e9ecef; padding: 2px 5px; border-radius: 3px; }
</style>";

// Test endpoints
$base_url = 'http://localhost:3000/api_manufacturing.php';
$test_results = [];

/**
 * Make API request
 */
function makeApiRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $http_code,
        'error' => $error
    ];
}

/**
 * Run test and display results
 */
function runTest($name, $url, $method = 'GET', $data = null, $expected_status = 200) {
    global $test_results;
    
    echo "<div class='test-section'>";
    echo "<h3>🧪 Test: $name</h3>";
    echo "<p><strong>Endpoint:</strong> <span class='endpoint'>$method $url</span></p>";
    
    if ($data) {
        echo "<p><strong>Request Data:</strong></p>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
    }
    
    $start_time = microtime(true);
    $result = makeApiRequest($url, $method, $data);
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    if ($result['error']) {
        echo "<div class='error'>";
        echo "<p><strong>❌ CURL Error:</strong> " . $result['error'] . "</p>";
        echo "</div>";
        $test_results[$name] = 'FAILED';
    } else {
        $response_data = json_decode($result['response'], true);
        
        echo "<p><strong>Response Time:</strong> {$response_time}ms</p>";
        echo "<p><strong>HTTP Status:</strong> {$result['http_code']}</p>";
        
        if ($result['http_code'] == $expected_status) {
            echo "<div class='success'>";
            echo "<p><strong>✅ Test Passed</strong></p>";
            $test_results[$name] = 'PASSED';
        } else {
            echo "<div class='error'>";
            echo "<p><strong>❌ Test Failed</strong> - Expected status $expected_status, got {$result['http_code']}</p>";
            $test_results[$name] = 'FAILED';
        }
        
        echo "<p><strong>Response:</strong></p>";
        echo "<pre>" . json_encode($response_data, JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
}

// Run Tests
echo "<h2>🚀 Starting API Tests...</h2>";

// Test 1: API Health Check
runTest(
    'API Health Check',
    $base_url
);

// Test 2: Product Search - Basic
runTest(
    'Product Search - Basic',
    $base_url . '?endpoint=products/search&limit=5'
);

// Test 3: Product Search - With Query
runTest(
    'Product Search - Steel Products',
    $base_url . '?endpoint=products/search&q=steel&limit=10'
);

// Test 4: Product Search - Category Filter
runTest(
    'Product Search - Category Filter',
    $base_url . '?endpoint=products/search&category=Fasteners&limit=8'
);

// Test 5: Product Search - Price Range
runTest(
    'Product Search - Price Range',
    $base_url . '?endpoint=products/search&price_min=10&price_max=100&limit=10'
);

// Test 6: Product Categories
runTest(
    'Product Categories',
    $base_url . '?endpoint=products/categories'
);

// Test 7: Pricing Calculation
runTest(
    'Pricing Calculation',
    $base_url . '?endpoint=pricing/calculate',
    'POST',
    [
        'product_id' => 'prod-steel-001',
        'client_id' => 'client-abc-mfg',
        'quantity' => 25
    ]
);

// Test 8: Inventory Check
runTest(
    'Inventory Check',
    $base_url . '?endpoint=inventory/check',
    'POST',
    [
        'product_ids' => ['prod-steel-001', 'prod-alum-001', 'prod-fast-001']
    ]
);

// Test 9: Product Recommendations
runTest(
    'Product Recommendations',
    $base_url . '?endpoint=products/recommendations&product_id=prod-steel-001&limit=5'
);

// Test 10: Search Performance Test
runTest(
    'Search Performance Test',
    $base_url . '?endpoint=products/search&q=aluminum&category=Aluminum&limit=20&sort_by=base_price&sort_order=ASC'
);

// Test 11: Invalid Endpoint
runTest(
    'Invalid Endpoint Test',
    $base_url . '?endpoint=invalid/endpoint',
    'GET',
    null,
    404
);

// Summary
echo "<div class='test-section info'>";
echo "<h2>📊 Test Summary</h2>";

$passed = array_count_values($test_results)['PASSED'] ?? 0;
$failed = array_count_values($test_results)['FAILED'] ?? 0;
$total = $passed + $failed;

echo "<p><strong>Total Tests:</strong> $total</p>";
echo "<p><strong>Passed:</strong> $passed</p>";
echo "<p><strong>Failed:</strong> $failed</p>";
echo "<p><strong>Success Rate:</strong> " . round(($passed / $total) * 100, 1) . "%</p>";

if ($failed === 0) {
    echo "<p style='color: green; font-weight: bold;'>🎉 All tests passed!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some tests failed. Check the results above.</p>";
}

echo "<h3>Manual Testing URLs:</h3>";
echo "<ul>";
echo "<li><a href='{$base_url}' target='_blank'>API Health Check</a></li>";
echo "<li><a href='{$base_url}?endpoint=products/search&q=steel&limit=5' target='_blank'>Search Steel Products</a></li>";
echo "<li><a href='{$base_url}?endpoint=products/categories' target='_blank'>Product Categories</a></li>";
echo "</ul>";

echo "<h3>cURL Examples:</h3>";
echo "<pre>";
echo "# Basic Product Search\n";
echo "curl '{$base_url}?endpoint=products/search&q=aluminum&limit=10'\n\n";

echo "# Pricing Calculation\n";
echo "curl -X POST '{$base_url}?endpoint=pricing/calculate' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"product_id\":\"prod-steel-001\",\"client_id\":\"client-abc-mfg\",\"quantity\":50}'\n\n";

echo "# Inventory Check\n";
echo "curl -X POST '{$base_url}?endpoint=inventory/check' \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -d '{\"product_ids\":[\"prod-steel-001\",\"prod-alum-001\"]}'\n";
echo "</pre>";

echo "</div>";

echo "<div class='test-section success'>";
echo "<h2>✅ Next Steps</h2>";
echo "<p>If all tests passed, your Manufacturing API is ready for:</p>";
echo "<ul>";
echo "<li>Frontend integration with React/Vue components</li>";
echo "<li>Mobile app development</li>";
echo "<li>Performance optimization with Redis caching</li>";
echo "<li>Production deployment</li>";
echo "</ul>";
echo "</div>";
