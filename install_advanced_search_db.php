<?php
/**
 * Install Advanced Search Database Schema
 * Sets up tables and indexes for comprehensive search functionality
 */

// Set as valid entry point for installation
define('sugarEntry', true);

require_once('include/database/DBManagerFactory.php');

// Get database connection
$db = DBManagerFactory::getInstance();

// Read the schema file
$schema_file = __DIR__ . '/database/advanced_search_schema.sql';
if (!file_exists($schema_file)) {
    die("Schema file not found: {$schema_file}");
}

$schema_sql = file_get_contents($schema_file);

// Split into individual statements
$statements = array_filter(array_map('trim', explode(';', $schema_sql)));

echo "<h2>Installing Advanced Search Database Schema...</h2>\n";
echo "<pre>\n";

$success_count = 0;
$error_count = 0;

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    // Skip delimiter statements
    if (strpos($statement, 'DELIMITER') !== false) {
        continue;
    }
    
    try {
        echo "Executing: " . substr($statement, 0, 100) . "...\n";
        $result = $db->query($statement);
        
        if ($result) {
            echo "✅ SUCCESS\n\n";
            $success_count++;
        } else {
            echo "❌ FAILED: " . $db->lastError() . "\n\n";
            $error_count++;
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n\n";
        $error_count++;
    }
}

echo "</pre>\n";

// Verify installation by checking if tables exist
echo "<h3>Verifying Installation...</h3>\n";
echo "<pre>\n";

$tables_to_check = [
    'mfg_search_history',
    'mfg_saved_searches', 
    'mfg_search_analytics',
    'mfg_product_search_index',
    'mfg_popular_searches',
    'mfg_search_suggestions'
];

$existing_tables = 0;
foreach ($tables_to_check as $table) {
    $check_query = "SHOW TABLES LIKE '{$table}'";
    $result = $db->query($check_query);
    
    if ($db->getRowCount($result) > 0) {
        echo "✅ Table '{$table}' exists\n";
        $existing_tables++;
    } else {
        echo "❌ Table '{$table}' NOT found\n";
    }
}

echo "\n";
echo "Installation Summary:\n";
echo "- Statements executed: " . ($success_count + $error_count) . "\n";
echo "- Successful: {$success_count}\n";
echo "- Errors: {$error_count}\n";
echo "- Tables verified: {$existing_tables}/{" . count($tables_to_check) . "}\n";

if ($existing_tables === count($tables_to_check) && $error_count === 0) {
    echo "\n🎉 Advanced Search Database Schema installed successfully!\n";
    
    // Initialize the product search index
    echo "\nInitializing product search index...\n";
    try {
        $db->query("CALL RebuildProductSearchIndex()");
        echo "✅ Product search index initialized\n";
    } catch (Exception $e) {
        echo "⚠️ Warning: Could not initialize search index: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n❌ Installation completed with errors. Please check the logs above.\n";
}

echo "</pre>\n";

// Test the search functionality
echo "<h3>Testing Search Functionality...</h3>\n";
echo "<pre>\n";

try {
    // Test popular searches query
    $test_query = "SELECT search_term, search_type, search_count FROM mfg_popular_searches WHERE deleted = 0 LIMIT 5";
    $result = $db->query($test_query);
    
    echo "Popular searches sample:\n";
    while ($row = $db->fetchByAssoc($result)) {
        echo "- {$row['search_term']} ({$row['search_type']}) - {$row['search_count']} searches\n";
    }
    
    // Test search suggestions query
    $test_query2 = "SELECT search_term, suggested_term, suggestion_type FROM mfg_search_suggestions WHERE deleted = 0 LIMIT 5";
    $result2 = $db->query($test_query2);
    
    echo "\nSearch suggestions sample:\n";
    while ($row = $db->fetchByAssoc($result2)) {
        echo "- '{$row['search_term']}' -> '{$row['suggested_term']}' ({$row['suggestion_type']})\n";
    }
    
    echo "\n✅ Search functionality tests passed\n";
    
} catch (Exception $e) {
    echo "❌ Search functionality test failed: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
