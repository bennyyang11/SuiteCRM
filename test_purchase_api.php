<?php
/**
 * Test Purchase API Functionality
 */

echo "=== Testing Purchase API ===\n\n";

// Test 1: Get products
echo "1. Testing get_products endpoint...\n";
$products_response = file_get_contents('http://localhost:3000/inventory_purchase_api.php?action=get_products');

if ($products_response === false) {
    echo "❌ Failed to fetch products\n";
} else {
    $products_data = json_decode($products_response, true);
    if ($products_data && $products_data['success']) {
        echo "✅ Products loaded successfully\n";
        echo "   Found " . count($products_data['data']['products']) . " products\n";
        
        // Show first product
        if (!empty($products_data['data']['products'])) {
            $first_product = $products_data['data']['products'][0];
            echo "   Sample product: {$first_product['name']} (SKU: {$first_product['sku']}) - Stock: {$first_product['total_stock']}\n";
        }
    } else {
        echo "❌ Products API error: " . ($products_data['error']['message'] ?? 'Unknown error') . "\n";
    }
}

echo "\n";

// Test 2: Get inventory summary
echo "2. Testing inventory summary...\n";
$summary_response = file_get_contents('http://localhost:3000/inventory_api_direct.php?action=get_summary');

if ($summary_response === false) {
    echo "❌ Failed to fetch inventory summary\n";
} else {
    $summary_data = json_decode($summary_response, true);
    if ($summary_data && $summary_data['success']) {
        echo "✅ Inventory summary loaded successfully\n";
        echo "   Total items: " . ($summary_data['data']['total_items'] ?? 0) . "\n";
        echo "   Total stock: " . ($summary_data['data']['total_stock'] ?? 0) . "\n";
        echo "   Low stock items: " . ($summary_data['data']['low_stock_items'] ?? 0) . "\n";
    } else {
        echo "❌ Summary API error: " . ($summary_data['error']['message'] ?? 'Unknown error') . "\n";
    }
}

echo "\n";

// Test 3: Simulate a purchase
echo "3. Testing purchase transaction...\n";

if (!empty($products_data['data']['products'])) {
    $test_product = $products_data['data']['products'][0];
    
    $purchase_data = [
        'items' => [
            [
                'id' => $test_product['id'],
                'name' => $test_product['name'],
                'sku' => $test_product['sku'],
                'price' => $test_product['unit_price'],
                'quantity' => 1
            ]
        ],
        'customer_name' => 'Test Customer',
        'payment_method' => 'cash'
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($purchase_data)
        ]
    ]);
    
    $purchase_response = file_get_contents('http://localhost:3000/inventory_purchase_api.php?action=process_purchase', false, $context);
    
    if ($purchase_response === false) {
        echo "❌ Failed to process purchase\n";
    } else {
        $purchase_result = json_decode($purchase_response, true);
        if ($purchase_result && $purchase_result['success']) {
            echo "✅ Purchase processed successfully\n";
            echo "   Transaction ID: {$purchase_result['data']['transaction_id']}\n";
            echo "   Total amount: $" . number_format($purchase_result['data']['total_amount'], 2) . "\n";
            echo "   Items processed: " . count($purchase_result['data']['items_processed']) . "\n";
        } else {
            echo "❌ Purchase error: " . ($purchase_result['error']['message'] ?? 'Unknown error') . "\n";
        }
    }
} else {
    echo "❌ No products available for purchase test\n";
}

echo "\n";

// Test 4: Check purchase history
echo "4. Testing purchase history...\n";
$history_response = file_get_contents('http://localhost:3000/inventory_purchase_api.php?action=get_purchase_history&limit=5');

if ($history_response === false) {
    echo "❌ Failed to fetch purchase history\n";
} else {
    $history_data = json_decode($history_response, true);
    if ($history_data && $history_data['success']) {
        echo "✅ Purchase history loaded successfully\n";
        echo "   Recent purchases: " . count($history_data['data']['purchases']) . "\n";
        
        if (!empty($history_data['data']['purchases'])) {
            $recent = $history_data['data']['purchases'][0];
            echo "   Most recent: {$recent['customer_name']} - $" . number_format($recent['total_amount'], 2) . " ({$recent['item_count']} items)\n";
        }
    } else {
        echo "❌ History API error: " . ($history_data['error']['message'] ?? 'Unknown error') . "\n";
    }
}

echo "\n=== Test Complete ===\n";

// Test file browser access
echo "\n5. Testing purchase interface file access...\n";
if (file_exists('/Users/bennyyang/Projects/Enterprise V3/inventory_purchase_interface.php')) {
    echo "✅ Purchase interface file exists\n";
    echo "   Access URL: http://localhost:3000/inventory_purchase_interface.php\n";
} else {
    echo "❌ Purchase interface file not found\n";
}

if (file_exists('/Users/bennyyang/Projects/Enterprise V3/inventory_purchase_api.php')) {
    echo "✅ Purchase API file exists\n";
    echo "   API URL: http://localhost:3000/inventory_purchase_api.php\n";
} else {
    echo "❌ Purchase API file not found\n";
}

echo "\n";
?>
