<?php
/**
 * Debug Database Connection Issues
 */

echo "<h1>Database Connection Debug</h1>\n";
echo "<div style='font-family: Arial; margin: 20px;'>\n";

// Test 1: Direct MySQL Connection
echo "<h2>Test 1: Direct MySQL Connection</h2>\n";
try {
    $host = '127.0.0.1';
    $port = 3307;
    $user = 'suitecrm_user';
    $password = 'suitecrm_password';
    $database = 'suitecrm';
    
    $connection = new mysqli($host, $user, $password, $database, $port);
    
    if ($connection->connect_error) {
        echo "<p style='color: red;'>❌ Direct connection failed: " . $connection->connect_error . "</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Direct MySQL connection successful</p>\n";
        
        // Test a simple query
        $result = $connection->query("SELECT COUNT(*) as count FROM config");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p style='color: green;'>✅ Database query successful - config table has " . $row['count'] . " records</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Database query failed: " . $connection->error . "</p>\n";
        }
        
        $connection->close();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Direct connection error: " . $e->getMessage() . "</p>\n";
}

// Test 2: Check Config File
echo "<h2>Test 2: Config File Check</h2>\n";
try {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        
        if (isset($sugar_config['dbconfig'])) {
            $db_config = $sugar_config['dbconfig'];
            echo "<p>Host: " . htmlspecialchars($db_config['db_host_name']) . "</p>\n";
            echo "<p>User: " . htmlspecialchars($db_config['db_user_name']) . "</p>\n";
            echo "<p>Database: " . htmlspecialchars($db_config['db_name']) . "</p>\n";
            echo "<p style='color: green;'>✅ Config file loaded successfully</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Database config not found in config file</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ Config file not found</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Config file error: " . $e->getMessage() . "</p>\n";
}

// Test 3: Check Session Configuration
echo "<h2>Test 3: Session Configuration</h2>\n";
try {
    echo "<p>Session Save Path: " . session_save_path() . "</p>\n";
    echo "<p>Session Status: " . session_status() . "</p>\n";
    
    // Create session directory if needed
    $session_dir = '/tmp/php_sessions';
    if (!is_dir($session_dir)) {
        if (mkdir($session_dir, 0755, true)) {
            echo "<p style='color: green;'>✅ Created session directory: {$session_dir}</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Failed to create session directory: {$session_dir}</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✅ Session directory exists and is accessible</p>\n";
    }
    
    // Set custom session save path
    ini_set('session.save_path', $session_dir);
    echo "<p style='color: green;'>✅ Session save path set to: " . session_save_path() . "</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Session error: " . $e->getMessage() . "</p>\n";
}

// Test 4: Try SuiteCRM Entry Point (simplified)
echo "<h2>Test 4: SuiteCRM Entry Point Test</h2>\n";
try {
    // Backup original error reporting
    $original_error = error_reporting();
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    
    echo "<p>Testing SuiteCRM initialization...</p>\n";
    
    // Check if we can define sugarEntry
    if (!defined('sugarEntry')) {
        define('sugarEntry', true);
        echo "<p style='color: green;'>✅ sugarEntry defined</p>\n";
    }
    
    // Check critical SuiteCRM files
    $critical_files = [
        'include/utils.php',
        'include/database/DBManagerFactory.php',
        'modules/Administration/Administration.php'
    ];
    
    foreach ($critical_files as $file) {
        $full_path = __DIR__ . '/' . $file;
        if (file_exists($full_path)) {
            echo "<p style='color: green;'>✅ Found: {$file}</p>\n";
        } else {
            echo "<p style='color: red;'>❌ Missing: {$file}</p>\n";
        }
    }
    
    // Restore error reporting
    error_reporting($original_error);
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ SuiteCRM test error: " . $e->getMessage() . "</p>\n";
}

echo "<h2>Recommendations</h2>\n";
echo "<ul>\n";
echo "<li>Use a simplified demo page that doesn't require full SuiteCRM initialization</li>\n";
echo "<li>Initialize sessions properly before using SuiteCRM entry point</li>\n";
echo "<li>Check SuiteCRM logs for more detailed error information</li>\n";
echo "</ul>\n";

echo "</div>\n";
?>
