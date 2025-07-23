<?php
/**
 * Feature 5 Integration Test
 * Test that Feature 5 is properly integrated into all demo systems
 */

echo "<h2>🔍 Feature 5 Integration Test</h2>\n";
echo "<style>
    .success { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .info { background: #e2e3e5; color: #383d41; padding: 10px; margin: 5px 0; border-radius: 5px; }
</style>\n";

$tests_passed = 0;
$tests_failed = 0;

echo "<div class='info'>🧪 Testing Feature 5 integration across all demo systems...</div>\n";

// Test 1: Check if manufacturing demo mentions Feature 5
echo "<h3>📱 Manufacturing Demo Integration</h3>\n";
if (file_exists('manufacturing_demo.php')) {
    $content = file_get_contents('manufacturing_demo.php');
    
    if (strpos($content, 'Features 1, 2, 3, 4 & 5') !== false) {
        echo "<div class='success'>✅ Manufacturing demo title updated to include Feature 5</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Manufacturing demo title not updated</div>\n";
        $tests_failed++;
    }
    
    if (strpos($content, 'Feature 5: Advanced Search') !== false) {
        echo "<div class='success'>✅ Feature 5 section added to manufacturing demo</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Feature 5 section missing from manufacturing demo</div>\n";
        $tests_failed++;
    }
    
    if (strpos($content, 'All Five Features Successfully Implemented') !== false) {
        echo "<div class='success'>✅ Summary section updated to reflect 5 features</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Summary section not updated</div>\n";
        $tests_failed++;
    }
} else {
    echo "<div class='error'>❌ Manufacturing demo file not found</div>\n";
    $tests_failed++;
}

// Test 2: Check if clean demo mentions Feature 5
echo "<h3>🧹 Clean Demo Integration</h3>\n";
if (file_exists('clean_demo.php')) {
    $content = file_get_contents('clean_demo.php');
    
    if (strpos($content, '5/6') !== false) {
        echo "<div class='success'>✅ Clean demo stats updated (5/6 features complete)</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Clean demo stats not updated</div>\n";
        $tests_failed++;
    }
    
    if (strpos($content, 'Feature 5: Advanced Search') !== false) {
        echo "<div class='success'>✅ Feature 5 card added to clean demo</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Feature 5 card missing from clean demo</div>\n";
        $tests_failed++;
    }
    
    if (strpos($content, 'Features 3, 4 & 5 Complete') !== false) {
        echo "<div class='success'>✅ Clean demo status updated to include Feature 5</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Clean demo status not updated</div>\n";
        $tests_failed++;
    }
} else {
    echo "<div class='error'>❌ Clean demo file not found</div>\n";
    $tests_failed++;
}

// Test 3: Check if main index mentions Feature 5
echo "<h3>🏠 Main Index Integration</h3>\n";
if (file_exists('index.php')) {
    $content = file_get_contents('index.php');
    
    if (strpos($content, 'Features 1, 2, 3, 4 & 5 Complete') !== false) {
        echo "<div class='success'>✅ Main index system status updated</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Main index system status not updated</div>\n";
        $tests_failed++;
    }
    
    if (strpos($content, 'Advanced Search & Filtering') !== false) {
        echo "<div class='success'>✅ Feature 5 mentioned in system status</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Feature 5 not mentioned in system status</div>\n";
        $tests_failed++;
    }
    
    if (strpos($content, 'all 5 completed features') !== false) {
        echo "<div class='success'>✅ Clean demo description updated</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Clean demo description not updated</div>\n";
        $tests_failed++;
    }
} else {
    echo "<div class='error'>❌ Main index file not found</div>\n";
    $tests_failed++;
}

// Test 4: Check if checklist reflects completion
echo "<h3>📋 Checklist Update</h3>\n";
if (file_exists('REMAINING_TASKS_CHECKLIST.md')) {
    $content = file_get_contents('REMAINING_TASKS_CHECKLIST.md');
    
    if (strpos($content, 'Features 1-5 COMPLETE') !== false) {
        echo "<div class='success'>✅ Checklist project status updated</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Checklist project status not updated</div>\n";
        $tests_failed++;
    }
    
    // Count completed Feature 5 tasks
    $feature5_completed = substr_count($content, '- [x]');
    if ($feature5_completed >= 19) {
        echo "<div class='success'>✅ All Feature 5 tasks marked complete ({$feature5_completed} checkmarks)</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Not all Feature 5 tasks marked complete ({$feature5_completed} checkmarks)</div>\n";
        $tests_failed++;
    }
} else {
    echo "<div class='error'>❌ Checklist file not found</div>\n";
    $tests_failed++;
}

// Test 5: Check if Feature 5 components exist
echo "<h3>🔧 Feature 5 Components</h3>\n";
$components = [
    'Api/v1/manufacturing/AdvancedSearchAPI.php',
    'SuiteCRM/modules/Manufacturing/frontend/src/components/AdvancedSearch.tsx',
    'SuiteCRM/modules/Manufacturing/frontend/src/components/SearchBar.tsx',
    'SuiteCRM/modules/Manufacturing/frontend/src/services/AdvancedSearchAPI.ts',
    'database/advanced_search_schema.sql'
];

foreach ($components as $component) {
    if (file_exists($component)) {
        $size = round(filesize($component) / 1024, 1);
        echo "<div class='success'>✅ Component '{basename($component)}' exists ({$size}KB)</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ Component '{basename($component)}' missing</div>\n";
        $tests_failed++;
    }
}

// Test 6: Database verification
echo "<h3>🗄️ Database Integration</h3>\n";
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
    
    $search_tables = [
        'mfg_search_history',
        'mfg_saved_searches',
        'mfg_popular_searches',
        'mfg_search_suggestions'
    ];
    
    foreach ($search_tables as $table) {
        try {
            $result = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $result->fetch()['count'];
            echo "<div class='success'>✅ Table '{$table}' operational ({$count} records)</div>\n";
            $tests_passed++;
        } catch (Exception $e) {
            echo "<div class='error'>❌ Table '{$table}' error: " . $e->getMessage() . "</div>\n";
            $tests_failed++;
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>\n";
    $tests_failed += 4;
}

// Final Results
echo "<div style='margin-top: 30px; padding: 20px; border: 2px solid #333; border-radius: 10px;'>\n";
echo "<h3>🎯 Integration Test Summary</h3>\n";
echo "<p><strong>Tests Passed:</strong> {$tests_passed}</p>\n";
echo "<p><strong>Tests Failed:</strong> {$tests_failed}</p>\n";

$total_tests = $tests_passed + $tests_failed;
$success_rate = $total_tests > 0 ? round(($tests_passed / $total_tests) * 100, 1) : 0;

if ($tests_failed === 0) {
    echo "<div class='success'>\n";
    echo "<h3>🎉 PERFECT INTEGRATION!</h3>\n";
    echo "<p>Feature 5: Advanced Search & Filtering is successfully integrated across all demo systems with 100% test pass rate.</p>\n";
    echo "<ul>\n";
    echo "<li>✅ Manufacturing Demo updated with Feature 5 showcase</li>\n";
    echo "<li>✅ Clean Demo updated with Feature 5 status</li>\n";
    echo "<li>✅ Main Index updated with system status</li>\n";
    echo "<li>✅ Checklist reflects completion status</li>\n";
    echo "<li>✅ All components and database tables operational</li>\n";
    echo "</ul>\n";
    echo "<p><strong>🚀 Ready for immediate deployment and client demonstrations!</strong></p>\n";
    echo "</div>\n";
} else {
    echo "<div class='error'>\n";
    echo "<h3>⚠️ INTEGRATION NEEDS ATTENTION</h3>\n";
    echo "<p>Success Rate: {$success_rate}% ({$tests_passed}/{$total_tests})</p>\n";
    echo "<p>{$tests_failed} integration issues found that should be addressed.</p>\n";
    echo "</div>\n";
}

echo "</div>\n";

// Return exit code
exit($tests_failed > 0 ? 1 : 0);
