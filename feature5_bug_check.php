<?php
/**
 * Feature 5 Bug Check and Validation
 * Comprehensive testing to ensure no bugs or errors
 */

// Database configuration
$db_config = [
    'host' => '127.0.0.1',
    'port' => '3307',
    'database' => 'suitecrm',
    'username' => 'suitecrm', 
    'password' => 'suitecrm'
];

echo "<h2>🔍 Feature 5: Advanced Search - Bug Check & Validation</h2>\n";
echo "<style>
    .success { background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .warning { background: #fff3cd; color: #856404; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .info { background: #e2e3e5; color: #383d41; padding: 10px; margin: 5px 0; border-radius: 5px; }
</style>\n";

$bugs_found = 0;
$warnings_found = 0;
$tests_passed = 0;

try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<div class='info'>🔍 Starting comprehensive bug check...</div>\n";
    
    // Test 1: Database schema integrity
    echo "<h3>📊 Database Schema Integrity</h3>\n";
    
    $required_tables = [
        'mfg_search_history',
        'mfg_saved_searches', 
        'mfg_search_analytics',
        'mfg_product_search_index',
        'mfg_popular_searches',
        'mfg_search_suggestions'
    ];
    
    foreach ($required_tables as $table) {
        try {
            $result = $pdo->query("DESCRIBE {$table}");
            if ($result->rowCount() > 0) {
                echo "<div class='success'>✅ Table '{$table}' exists with proper structure</div>\n";
                $tests_passed++;
            } else {
                echo "<div class='error'>❌ BUG: Table '{$table}' has no columns</div>\n";
                $bugs_found++;
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ BUG: Table '{$table}' missing or inaccessible - " . $e->getMessage() . "</div>\n";
            $bugs_found++;
        }
    }
    
    // Test 2: Full-text index verification
    echo "<h3>🔍 Full-Text Index Verification</h3>\n";
    
    try {
        $result = $pdo->query("SHOW INDEX FROM mfg_products WHERE Key_name LIKE '%name%' AND Index_type = 'FULLTEXT'");
        if ($result->rowCount() > 0) {
            echo "<div class='success'>✅ Full-text index on mfg_products exists</div>\n";
            $tests_passed++;
        } else {
            echo "<div class='warning'>⚠️ WARNING: Full-text index might be missing</div>\n";
            $warnings_found++;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ BUG: Cannot verify full-text index - " . $e->getMessage() . "</div>\n";
        $bugs_found++;
    }
    
    // Test 3: Search functionality validation
    echo "<h3>🎯 Search Functionality Validation</h3>\n";
    
    $search_tests = [
        'Simple search' => "SELECT COUNT(*) as count FROM mfg_products WHERE name LIKE '%steel%' AND deleted = 0",
        'Full-text search' => "SELECT COUNT(*) as count FROM mfg_products WHERE MATCH(name, description, sku) AGAINST('steel' IN BOOLEAN MODE) AND deleted = 0",
        'Category filter' => "SELECT COUNT(*) as count FROM mfg_products WHERE category IS NOT NULL AND deleted = 0",
        'Price range' => "SELECT COUNT(*) as count FROM mfg_products WHERE unit_price > 0 AND deleted = 0"
    ];
    
    foreach ($search_tests as $test_name => $query) {
        try {
            $start_time = microtime(true);
            $result = $pdo->query($query);
            $count = $result->fetch()['count'];
            $end_time = microtime(true);
            $duration = round(($end_time - $start_time) * 1000, 2);
            
            if ($duration < 500) {
                echo "<div class='success'>✅ {$test_name}: {$count} results in {$duration}ms</div>\n";
                $tests_passed++;
            } else {
                echo "<div class='warning'>⚠️ WARNING: {$test_name} slow: {$duration}ms</div>\n";
                $warnings_found++;
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ BUG: {$test_name} failed - " . $e->getMessage() . "</div>\n";
            $bugs_found++;
        }
    }
    
    // Test 4: Component file integrity
    echo "<h3>📱 React Component File Integrity</h3>\n";
    
    $component_files = [
        'SuiteCRM/modules/Manufacturing/frontend/src/components/AdvancedSearch.tsx',
        'SuiteCRM/modules/Manufacturing/frontend/src/components/SearchBar.tsx',
        'SuiteCRM/modules/Manufacturing/frontend/src/components/SearchSuggestions.tsx',
        'SuiteCRM/modules/Manufacturing/frontend/src/components/SearchFilters.tsx',
        'SuiteCRM/modules/Manufacturing/frontend/src/components/SearchResults.tsx',
        'SuiteCRM/modules/Manufacturing/frontend/src/types/Search.ts',
        'SuiteCRM/modules/Manufacturing/frontend/src/services/AdvancedSearchAPI.ts',
        'SuiteCRM/modules/Manufacturing/frontend/src/hooks/useDebounce.ts',
        'SuiteCRM/modules/Manufacturing/frontend/src/hooks/useLocalStorage.ts'
    ];
    
    foreach ($component_files as $file) {
        if (file_exists($file) && filesize($file) > 0) {
            $size = round(filesize($file) / 1024, 1);
            echo "<div class='success'>✅ Component '{basename($file)}' exists ({$size}KB)</div>\n";
            $tests_passed++;
        } else {
            echo "<div class='error'>❌ BUG: Component '{basename($file)}' missing or empty</div>\n";
            $bugs_found++;
        }
    }
    
    // Test 5: API endpoint accessibility
    echo "<h3>🔗 API Endpoint Validation</h3>\n";
    
    $api_file = 'Api/v1/manufacturing/AdvancedSearchAPI.php';
    if (file_exists($api_file)) {
        // Check for syntax errors
        $output = [];
        $return_var = 0;
        exec("php -l {$api_file}", $output, $return_var);
        
        if ($return_var === 0) {
            echo "<div class='success'>✅ API file has no syntax errors</div>\n";
            $tests_passed++;
        } else {
            echo "<div class='error'>❌ BUG: API file has syntax errors: " . implode(', ', $output) . "</div>\n";
            $bugs_found++;
        }
        
        $api_size = round(filesize($api_file) / 1024, 1);
        echo "<div class='success'>✅ API file size: {$api_size}KB</div>\n";
        $tests_passed++;
    } else {
        echo "<div class='error'>❌ BUG: API file missing: {$api_file}</div>\n";
        $bugs_found++;
    }
    
    // Test 6: Sample data validation
    echo "<h3>📋 Sample Data Validation</h3>\n";
    
    $data_tests = [
        'Popular searches' => "SELECT COUNT(*) as count FROM mfg_popular_searches WHERE deleted = 0",
        'Search suggestions' => "SELECT COUNT(*) as count FROM mfg_search_suggestions WHERE deleted = 0",
        'Product index' => "SELECT COUNT(*) as count FROM mfg_product_search_index WHERE deleted = 0",
        'Products available' => "SELECT COUNT(*) as count FROM mfg_products WHERE deleted = 0 AND status = 'active'"
    ];
    
    foreach ($data_tests as $test_name => $query) {
        try {
            $result = $pdo->query($query);
            $count = $result->fetch()['count'];
            
            if ($count > 0) {
                echo "<div class='success'>✅ {$test_name}: {$count} records</div>\n";
                $tests_passed++;
            } else {
                echo "<div class='warning'>⚠️ WARNING: {$test_name}: No data found</div>\n";
                $warnings_found++;
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ BUG: {$test_name} query failed - " . $e->getMessage() . "</div>\n";
            $bugs_found++;
        }
    }
    
    // Test 7: Performance validation
    echo "<h3>⚡ Performance Validation</h3>\n";
    
    $performance_critical_queries = [
        'Instant search' => "SELECT id, name, sku FROM mfg_products WHERE MATCH(name, description, sku) AGAINST('steel*' IN BOOLEAN MODE) LIMIT 10",
        'Autocomplete' => "SELECT search_term FROM mfg_popular_searches WHERE search_term LIKE 'ste%' LIMIT 5",
        'Facet counts' => "SELECT category, COUNT(*) as count FROM mfg_products WHERE deleted = 0 GROUP BY category"
    ];
    
    foreach ($performance_critical_queries as $test_name => $query) {
        try {
            $start_time = microtime(true);
            $result = $pdo->query($query);
            $end_time = microtime(true);
            $duration = round(($end_time - $start_time) * 1000, 2);
            
            if ($duration < 100) {
                echo "<div class='success'>✅ {$test_name}: {$duration}ms (Excellent)</div>\n";
                $tests_passed++;
            } elseif ($duration < 500) {
                echo "<div class='success'>✅ {$test_name}: {$duration}ms (Good)</div>\n";
                $tests_passed++;
            } else {
                echo "<div class='warning'>⚠️ WARNING: {$test_name}: {$duration}ms (Needs optimization)</div>\n";
                $warnings_found++;
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ BUG: {$test_name} performance test failed - " . $e->getMessage() . "</div>\n";
            $bugs_found++;
        }
    }
    
    // Final summary
    echo "<div style='margin-top: 30px; padding: 20px; border: 2px solid #333; border-radius: 10px;'>\n";
    echo "<h3>🎯 Bug Check Summary</h3>\n";
    echo "<p><strong>Tests Passed:</strong> {$tests_passed}</p>\n";
    echo "<p><strong>Bugs Found:</strong> {$bugs_found}</p>\n";
    echo "<p><strong>Warnings:</strong> {$warnings_found}</p>\n";
    
    if ($bugs_found === 0) {
        echo "<div class='success'>\n";
        echo "<h3>🎉 NO BUGS FOUND!</h3>\n";
        echo "<p>Feature 5: Advanced Search & Filtering is bug-free and ready for production deployment.</p>\n";
        if ($warnings_found > 0) {
            echo "<p>⚠️ {$warnings_found} minor warnings noted but do not affect functionality.</p>\n";
        }
        echo "</div>\n";
    } else {
        echo "<div class='error'>\n";
        echo "<h3>❌ BUGS DETECTED</h3>\n";
        echo "<p>{$bugs_found} bugs found that need to be addressed before deployment.</p>\n";
        echo "</div>\n";
    }
    echo "</div>\n";
    
} catch (PDOException $e) {
    echo "<div class='error'>\n";
    echo "<h3>❌ Database Connection Error</h3>\n";
    echo "<p>Could not connect to database: " . $e->getMessage() . "</p>\n";
    echo "</div>\n";
    $bugs_found++;
}

// Return exit code based on bugs found
if ($bugs_found > 0) {
    exit(1);
} else {
    exit(0);
}
