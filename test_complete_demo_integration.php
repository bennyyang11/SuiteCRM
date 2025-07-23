<?php
/**
 * Test Complete Manufacturing Demo Integration
 * Verify that Feature 5 is properly displayed in the View Demo
 */

echo "<h2>🔍 Complete Manufacturing Demo Integration Test</h2>\n";
echo "<style>
    .success { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .info { background: #e2e3e5; color: #383d41; padding: 10px; margin: 5px 0; border-radius: 5px; }
</style>\n";

$tests_passed = 0;
$tests_failed = 0;

echo "<div class='info'>🧪 Testing that View Demo shows all 5 features...</div>\n";

// Test: Check if complete_manufacturing_demo.php includes Feature 5
if (file_exists('complete_manufacturing_demo.php')) {
    $content = file_get_contents('complete_manufacturing_demo.php');
    
    // Test 1: Title includes all 5 features
    if (strpos($content, 'Features 1, 2, 3, 4 & 5') !== false) {
        echo "<div class='success'>✅ Title updated to include all 5 features</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Title not updated to include Feature 5</div>\n";
        $tests_failed++;
    }
    
    // Test 2: Header description includes all features
    if (strpos($content, 'Product Catalog + Order Pipeline + Inventory + Quote Builder + Advanced Search') !== false) {
        echo "<div class='success'>✅ Header description includes all features</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Header description not updated</div>\n";
        $tests_failed++;
    }
    
    // Test 3: Feature 5 section exists
    if (strpos($content, 'Feature 5: Advanced Search & Filtering') !== false) {
        echo "<div class='success'>✅ Feature 5 section exists in the demo</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Feature 5 section missing from demo</div>\n";
        $tests_failed++;
    }
    
    // Test 4: Google-like search engine mentioned
    if (strpos($content, 'Google-like Search Engine') !== false) {
        echo "<div class='success'>✅ Google-like search engine highlighted</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Google-like search engine not mentioned</div>\n";
        $tests_failed++;
    }
    
    // Test 5: Performance metrics shown
    if (strpos($content, '1.96ms') !== false) {
        echo "<div class='success'>✅ Performance metrics displayed (1.96ms search time)</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Performance metrics not displayed</div>\n";
        $tests_failed++;
    }
    
    // Test 6: Summary section includes Feature 5
    if (strpos($content, 'All Five Features Successfully Implemented') !== false) {
        echo "<div class='success'>✅ Summary updated to include all 5 features</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Summary not updated to include Feature 5</div>\n";
        $tests_failed++;
    }
    
    // Test 7: Feature 5 in summary list
    if (strpos($content, 'Feature 5: Advanced Search') !== false) {
        echo "<div class='success'>✅ Feature 5 added to summary feature list</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Feature 5 not in summary feature list</div>\n";
        $tests_failed++;
    }
    
    // Test 8: Demo links functional
    if (strpos($content, 'feature5_advanced_search_demo.php') !== false) {
        echo "<div class='success'>✅ Feature 5 demo links included</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Feature 5 demo links missing</div>\n";
        $tests_failed++;
    }
    
} else {
    echo "<div class='error'>❌ complete_manufacturing_demo.php file not found</div>\n";
    $tests_failed += 8;
}

// Test: Verify the View Demo link points to the right file
echo "<h3>🔗 View Demo Link Verification</h3>\n";
if (file_exists('index.php')) {
    $index_content = file_get_contents('index.php');
    
    if (strpos($index_content, 'href="complete_manufacturing_demo.php"') !== false) {
        echo "<div class='success'>✅ View Demo button links to complete_manufacturing_demo.php</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ View Demo button link incorrect</div>\n";
        $tests_failed++;
    }
} else {
    echo "<div class='error'>❌ index.php file not found</div>\n";
    $tests_failed++;
}

// Final Results
echo "<div style='margin-top: 30px; padding: 20px; border: 2px solid #333; border-radius: 10px;'>\n";
echo "<h3>🎯 Complete Demo Integration Summary</h3>\n";
echo "<p><strong>Tests Passed:</strong> {$tests_passed}</p>\n";
echo "<p><strong>Tests Failed:</strong> {$tests_failed}</p>\n";

if ($tests_failed === 0) {
    echo "<div class='success'>\n";
    echo "<h3>🎉 PERFECT INTEGRATION!</h3>\n";
    echo "<p>The View Demo button now shows all 5 features including Feature 5: Advanced Search & Filtering!</p>\n";
    echo "<ul>\n";
    echo "<li>✅ Title updated to show Features 1, 2, 3, 4 & 5</li>\n";
    echo "<li>✅ Header description includes all features</li>\n";
    echo "<li>✅ Feature 5 section fully displayed with Google-like search</li>\n";
    echo "<li>✅ Performance metrics and demo links included</li>\n";
    echo "<li>✅ Summary section shows all 5 features</li>\n";
    echo "</ul>\n";
    echo "<p><strong>🚀 Users can now see the complete Feature 5 showcase when clicking View Demo!</strong></p>\n";
    echo "</div>\n";
} else {
    echo "<div class='error'>\n";
    echo "<h3>⚠️ INTEGRATION ISSUES FOUND</h3>\n";
    echo "<p>{$tests_failed} issues found that need to be addressed.</p>\n";
    echo "</div>\n";
}

echo "</div>\n";

// Return exit code
exit($tests_failed > 0 ? 1 : 0);
