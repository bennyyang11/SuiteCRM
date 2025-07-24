<?php
// Quick test to verify all feature pages are working
$feature_pages = [
    'feature1_product_catalog.php',
    'feature2_order_pipeline.php', 
    'feature3_inventory_integration.php',
    'feature4_quote_builder.php',
    'feature5_advanced_search.php',
    'feature6_role_management.php'
];

echo "<!DOCTYPE html><html><head><title>Feature Pages Test</title></head><body>";
echo "<h1>Feature Pages Loading Test</h1>";
echo "<ul>";

foreach ($feature_pages as $page) {
    $url = "http://localhost:3000/$page";
    $headers = @get_headers($url);
    $status = $headers ? $headers[0] : 'Failed to connect';
    $working = (strpos($status, '200') !== false) ? '✅' : '❌';
    
    echo "<li>$working <a href='$url' target='_blank'>$page</a> - $status</li>";
}

echo "</ul>";
echo "<p><strong>All pages should show ✅ and be clickable</strong></p>";
echo "</body></html>";
?>
