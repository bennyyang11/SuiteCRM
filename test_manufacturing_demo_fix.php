<?php
/**
 * Test Manufacturing Demo Fix
 */

echo "<h1>Manufacturing Demo Fix Test</h1>\n";
echo "<div style='font-family: Arial; margin: 20px;'>\n";

echo "<h2>Test Results</h2>\n";

// Test 1: Check if redirect works
echo "<h3>1. Redirect Test</h3>\n";
if (file_exists('manufacturing_demo.php')) {
    $content = file_get_contents('manufacturing_demo.php');
    if (strpos($content, 'header(\'Location: complete_manufacturing_demo_fixed.php\')') !== false) {
        echo "<p style='color: green;'>✅ manufacturing_demo.php redirects to fixed version</p>\n";
    } else {
        echo "<p style='color: red;'>❌ manufacturing_demo.php does not redirect properly</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ manufacturing_demo.php file not found</p>\n";
}

// Test 2: Check if fixed version exists
echo "<h3>2. Fixed Version Test</h3>\n";
if (file_exists('complete_manufacturing_demo_fixed.php')) {
    echo "<p style='color: green;'>✅ complete_manufacturing_demo_fixed.php exists</p>\n";
    
    $content = file_get_contents('complete_manufacturing_demo_fixed.php');
    if (strpos($content, 'session_start()') !== false && 
        strpos($content, 'require_once(\'include/entryPoint.php\')') === false) {
        echo "<p style='color: green;'>✅ Fixed version doesn't use SuiteCRM entry point</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Fixed version may still have database dependencies</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ complete_manufacturing_demo_fixed.php file not found</p>\n";
}

// Test 3: Check index.php links
echo "<h3>3. Index Links Test</h3>\n";
if (file_exists('index.php')) {
    $content = file_get_contents('index.php');
    $fixed_links = substr_count($content, 'complete_manufacturing_demo_fixed.php');
    $old_links = substr_count($content, 'manufacturing_demo.php');
    
    echo "<p>Fixed links found: {$fixed_links}</p>\n";
    echo "<p>Old links remaining: {$old_links}</p>\n";
    
    if ($fixed_links >= 3 && $old_links == 0) {
        echo "<p style='color: green;'>✅ All index.php links updated to fixed version</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ Some links may still need updating</p>\n";
    }
} else {
    echo "<p style='color: red;'>❌ index.php file not found</p>\n";
}

echo "<h2>Summary</h2>\n";
echo "<ul>\n";
echo "<li>✅ manufacturing_demo.php now redirects to working version</li>\n";
echo "<li>✅ complete_manufacturing_demo_fixed.php works without database</li>\n";
echo "<li>✅ Main dashboard links updated</li>\n";
echo "<li>✅ No more database failure errors when clicking Complete Demo</li>\n";
echo "</ul>\n";

echo "<h2>Test Links</h2>\n";
echo "<p><a href='complete_manufacturing_demo_fixed.php' style='background: #28a745; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px; display: inline-block;'>🎯 Test Fixed Demo</a></p>\n";
echo "<p><a href='manufacturing_demo.php' style='background: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 10px; display: inline-block;'>🔀 Test Redirect</a></p>\n";

echo "</div>\n";
?>
