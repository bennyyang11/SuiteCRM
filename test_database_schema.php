<?php
/**
 * Test Database Schema Installation
 * Order Pipeline Database Setup
 */

require_once 'include/entryPoint.php';

// Database connection
$config = $GLOBALS['sugar_config']['dbconfig'];
$host = $config['db_host_name'] . ':' . $config['db_port'];
$username = $config['db_user_name'];
$password = $config['db_password'];
$database = $config['db_name'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "📊 Order Pipeline Database Schema Installation\n";
    echo "=" . str_repeat("=", 50) . "\n\n";
    
    // Read and execute schema file
    $schemaSQL = file_get_contents('database/order_pipeline_schema.sql');
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schemaSQL)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            
            // Check if it's a CREATE TABLE statement
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches);
                if ($matches) {
                    echo "✅ Created table: " . $matches[1] . "\n";
                }
            } elseif (stripos($statement, 'CREATE VIEW') !== false) {
                preg_match('/CREATE.*VIEW\s+`?(\w+)`?/i', $statement, $matches);
                if ($matches) {
                    echo "✅ Created view: " . $matches[1] . "\n";
                }
            } elseif (stripos($statement, 'CREATE TRIGGER') !== false) {
                preg_match('/CREATE TRIGGER\s+`?(\w+)`?/i', $statement, $matches);
                if ($matches) {
                    echo "✅ Created trigger: " . $matches[1] . "\n";
                }
            } elseif (stripos($statement, 'CREATE INDEX') !== false) {
                echo "✅ Created index\n";
            }
            
            $successCount++;
            
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'already exists') === false) {
                echo "❌ Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "📊 Schema Installation Summary:\n";
    echo "✅ Successful operations: $successCount\n";
    echo "❌ Errors: $errorCount\n\n";
    
    // Verify tables were created
    echo "🔍 Verifying table creation:\n";
    $tables = [
        'mfg_order_pipeline',
        'mfg_pipeline_stage_history',
        'mfg_pipeline_stage_metrics',
        'mfg_notification_preferences',
        'mfg_notification_queue',
        'mfg_pipeline_items'
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table exists: $table\n";
            
            // Get column count
            $stmt = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '$database'");
            $columnCount = $stmt->fetchColumn();
            echo "   └─ Columns: $columnCount\n";
        } else {
            echo "❌ Table missing: $table\n";
        }
    }
    
    echo "\n🔍 Verifying views:\n";
    $views = [
        'mfg_pipeline_analytics',
        'mfg_pipeline_funnel',
        'mfg_sales_rep_performance',
        'mfg_stage_duration_analysis',
        'mfg_pipeline_health_dashboard',
        'mfg_monthly_pipeline_trends',
        'mfg_pipeline_bottleneck_analysis'
    ];
    
    foreach ($views as $view) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$view'");
        if ($stmt->rowCount() > 0) {
            echo "✅ View exists: $view\n";
        } else {
            echo "❌ View missing: $view\n";
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 Database schema installation completed!\n\n";
    
    // Test sample data insertion
    echo "📥 Installing sample data...\n";
    
    // Check if we have existing accounts and users for foreign keys
    $stmt = $pdo->query("SELECT COUNT(*) FROM accounts WHERE deleted = 0");
    $accountCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE deleted = 0 AND status = 'Active'");
    $userCount = $stmt->fetchColumn();
    
    echo "👥 Found $userCount active users\n";
    echo "🏢 Found $accountCount accounts\n";
    
    if ($accountCount > 0 && $userCount > 0) {
        echo "✅ Prerequisites met for sample data installation\n";
        
        // Install basic sample data (without the complex cross joins that might fail)
        $sampleData = "
        INSERT IGNORE INTO mfg_order_pipeline (
            id, pipeline_number, account_id, assigned_user_id, current_stage, 
            stage_entered_date, expected_completion_date, total_value, priority, 
            client_po_number, notes, date_entered, created_by
        ) VALUES 
        (UUID(), 'PIPE-2025-DEMO-001', (SELECT id FROM accounts WHERE deleted = 0 LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1), 'quote_requested', NOW(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 15750.00, 'high', 'PO-DEMO-001', 'Demo pipeline for testing', NOW(), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1)),
        (UUID(), 'PIPE-2025-DEMO-002', (SELECT id FROM accounts WHERE deleted = 0 LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1), 'quote_prepared', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 22150.75, 'medium', 'PO-DEMO-002', 'Demo pipeline in preparation', DATE_SUB(NOW(), INTERVAL 2 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1)),
        (UUID(), 'PIPE-2025-DEMO-003', (SELECT id FROM accounts WHERE deleted = 0 LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1), 'order_processing', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 33750.50, 'high', 'PO-DEMO-003', 'Demo pipeline in manufacturing', DATE_SUB(NOW(), INTERVAL 12 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1));
        ";
        
        try {
            $pdo->exec($sampleData);
            echo "✅ Sample pipeline data installed\n";
            
            // Verify sample data
            $stmt = $pdo->query("SELECT COUNT(*) FROM mfg_order_pipeline WHERE deleted = 0");
            $pipelineCount = $stmt->fetchColumn();
            echo "📊 Total pipelines created: $pipelineCount\n";
            
        } catch (PDOException $e) {
            echo "⚠️  Sample data installation failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️  Insufficient base data for sample installation\n";
    }
    
    echo "\n🎉 Database setup completed successfully!\n";
    echo "Ready for Order Tracking Dashboard implementation.\n\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
