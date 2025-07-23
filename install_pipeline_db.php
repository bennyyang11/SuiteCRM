<?php
/**
 * Install Order Pipeline Database Schema
 * Simple installation script for the manufacturing order tracking system
 */

// Include SuiteCRM bootstrap
if (!defined('sugarEntry')) define('sugarEntry', true);
require_once 'config.php';
require_once 'include/database/DBManagerFactory.php';

$db = DBManagerFactory::getInstance();

echo "<h1>📊 Order Pipeline Database Installation</h1>\n";
echo "<pre>\n";

// Test database connection
if (!$db) {
    echo "❌ Failed to connect to database\n";
    exit(1);
}

echo "✅ Database connection successful\n";
echo "🗄️  Database: " . $sugar_config['dbconfig']['db_name'] . "\n\n";

// Create main pipeline table
$pipelineTableSQL = "
CREATE TABLE IF NOT EXISTS mfg_order_pipeline (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    pipeline_number VARCHAR(50) NOT NULL UNIQUE,
    opportunity_id VARCHAR(36) NULL,
    account_id VARCHAR(36) NOT NULL,
    assigned_user_id VARCHAR(36) NULL,
    current_stage ENUM(
        'quote_requested',
        'quote_prepared', 
        'quote_sent',
        'quote_approved',
        'order_processing',
        'ready_to_ship',
        'invoiced_delivered'
    ) NOT NULL DEFAULT 'quote_requested',
    stage_entered_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_completion_date DATE NULL,
    actual_completion_date DATE NULL,
    total_value DECIMAL(18,2) DEFAULT 0.00,
    currency_id VARCHAR(36) DEFAULT 'USD',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('active', 'on_hold', 'cancelled', 'completed') DEFAULT 'active',
    client_po_number VARCHAR(100) NULL,
    client_contact_id VARCHAR(36) NULL,
    notes TEXT NULL,
    internal_notes TEXT NULL,
    shipping_address TEXT NULL,
    shipping_method VARCHAR(100) NULL,
    tracking_number VARCHAR(100) NULL,
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    modified_user_id VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create stage history table
$historyTableSQL = "
CREATE TABLE IF NOT EXISTS mfg_pipeline_stage_history (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    pipeline_id VARCHAR(36) NOT NULL,
    from_stage VARCHAR(50) NULL,
    to_stage VARCHAR(50) NOT NULL,
    transition_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    transition_user_id VARCHAR(36) NULL,
    duration_in_previous_stage_hours INT NULL,
    expected_duration_hours INT NULL,
    transition_reason TEXT NULL,
    notes TEXT NULL,
    automated_transition TINYINT(1) DEFAULT 0,
    workflow_rule_id VARCHAR(36) NULL,
    approval_required TINYINT(1) DEFAULT 0,
    approved_by VARCHAR(36) NULL,
    approval_date DATETIME NULL,
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create notification preferences table
$notificationTableSQL = "
CREATE TABLE IF NOT EXISTS mfg_notification_preferences (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    notification_type ENUM(
        'stage_change',
        'overdue_alert',
        'daily_summary',
        'weekly_report',
        'client_update',
        'urgent_priority',
        'completion_milestone',
        'bottleneck_alert',
        'approval_required',
        'quote_expiring'
    ) NOT NULL,
    delivery_method ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    schedule_time TIME NULL,
    schedule_day ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NULL,
    threshold_hours INT NULL,
    threshold_amount DECIMAL(18,2) NULL,
    email_template_id VARCHAR(36) NULL,
    message_format ENUM('full', 'summary', 'minimal') DEFAULT 'summary',
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    modified_user_id VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY unique_user_notification (user_id, notification_type, delivery_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create notification queue table
$queueTableSQL = "
CREATE TABLE IF NOT EXISTS mfg_notification_queue (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    pipeline_id VARCHAR(36) NULL,
    recipient_user_id VARCHAR(36) NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    delivery_method ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    message_html TEXT NULL,
    action_url VARCHAR(500) NULL,
    status ENUM('pending', 'sent', 'failed', 'cancelled', 'read') DEFAULT 'pending',
    scheduled_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_time DATETIME NULL,
    read_time DATETIME NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT NULL,
    external_id VARCHAR(255) NULL,
    batch_id VARCHAR(36) NULL,
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Execute table creation
$tables = [
    'mfg_order_pipeline' => $pipelineTableSQL,
    'mfg_pipeline_stage_history' => $historyTableSQL,
    'mfg_notification_preferences' => $notificationTableSQL,
    'mfg_notification_queue' => $queueTableSQL
];

foreach ($tables as $tableName => $sql) {
    try {
        $db->query($sql);
        echo "✅ Created table: $tableName\n";
    } catch (Exception $e) {
        echo "❌ Error creating table $tableName: " . $e->getMessage() . "\n";
    }
}

// Create indexes
$indexes = [
    "CREATE INDEX idx_pipeline_stage ON mfg_order_pipeline(current_stage, deleted)",
    "CREATE INDEX idx_pipeline_assigned ON mfg_order_pipeline(assigned_user_id, deleted)",
    "CREATE INDEX idx_pipeline_account ON mfg_order_pipeline(account_id, deleted)",
    "CREATE INDEX idx_history_pipeline ON mfg_pipeline_stage_history(pipeline_id, transition_date)",
    "CREATE INDEX idx_notif_user ON mfg_notification_preferences(user_id, enabled)",
    "CREATE INDEX idx_queue_status ON mfg_notification_queue(status, scheduled_time)"
];

echo "\n📊 Creating indexes...\n";
foreach ($indexes as $indexSQL) {
    try {
        $db->query($indexSQL);
        echo "✅ Index created\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            echo "⚠️  Index creation warning: " . $e->getMessage() . "\n";
        }
    }
}

// Insert sample data
echo "\n📥 Installing sample data...\n";

// Get existing users and accounts for foreign keys
$userResult = $db->query("SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1");
$userRow = $db->fetchByAssoc($userResult);
$userId = $userRow ? $userRow['id'] : null;

$accountResult = $db->query("SELECT id FROM accounts WHERE deleted = 0 LIMIT 1");
$accountRow = $db->fetchByAssoc($accountResult);
$accountId = $accountRow ? $accountRow['id'] : null;

if ($userId && $accountId) {
    // Generate UUID function for older MySQL versions
    $uuid1 = uniqid() . '-' . uniqid();
    $uuid2 = uniqid() . '-' . uniqid();
    $uuid3 = uniqid() . '-' . uniqid();
    
    $sampleData = "
    INSERT IGNORE INTO mfg_order_pipeline (
        id, pipeline_number, account_id, assigned_user_id, current_stage, 
        stage_entered_date, expected_completion_date, total_value, priority, 
        client_po_number, notes, date_entered, created_by
    ) VALUES 
    ('$uuid1', 'PIPE-2025-DEMO-001', '$accountId', '$userId', 'quote_requested', NOW(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 15750.00, 'high', 'PO-DEMO-001', 'Demo pipeline for testing Order Tracking Dashboard', NOW(), '$userId'),
    ('$uuid2', 'PIPE-2025-DEMO-002', '$accountId', '$userId', 'quote_prepared', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 22150.75, 'medium', 'PO-DEMO-002', 'Demo pipeline in quote preparation stage', DATE_SUB(NOW(), INTERVAL 2 DAY), '$userId'),
    ('$uuid3', 'PIPE-2025-DEMO-003', '$accountId', '$userId', 'order_processing', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 33750.50, 'high', 'PO-DEMO-003', 'Demo pipeline in manufacturing stage', DATE_SUB(NOW(), INTERVAL 12 DAY), '$userId')
    ";
    
    try {
        $db->query($sampleData);
        echo "✅ Sample pipeline data installed\n";
        
        // Add sample notification preferences
        $notifUuid = uniqid() . '-' . uniqid();
        $notifSQL = "
        INSERT IGNORE INTO mfg_notification_preferences (
            id, user_id, notification_type, delivery_method, enabled, date_entered, created_by
        ) VALUES
        ('$notifUuid', '$userId', 'stage_change', 'email', 1, NOW(), '$userId')
        ";
        
        $db->query($notifSQL);
        echo "✅ Sample notification preferences installed\n";
        
    } catch (Exception $e) {
        echo "⚠️  Sample data installation warning: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  No active users or accounts found for sample data\n";
}

// Verify installation
echo "\n🔍 Verifying installation...\n";
$tables = ['mfg_order_pipeline', 'mfg_pipeline_stage_history', 'mfg_notification_preferences', 'mfg_notification_queue'];

foreach ($tables as $table) {
    try {
        $result = $db->query("SELECT COUNT(*) as count FROM $table");
        $row = $db->fetchByAssoc($result);
        echo "✅ Table $table: " . $row['count'] . " records\n";
    } catch (Exception $e) {
        echo "❌ Table $table verification failed\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "🎉 Order Pipeline Database Installation Complete!\n";
echo "\n📊 Features installed:\n";
echo "  ✅ 7-stage pipeline tracking (Quote → Invoice)\n";
echo "  ✅ Complete stage transition history\n";
echo "  ✅ User notification preferences system\n";
echo "  ✅ Notification queue and delivery tracking\n";
echo "  ✅ Performance indexes for fast queries\n";
echo "  ✅ Sample data for testing and demos\n";
echo "\n🚀 Ready for Order Tracking Dashboard implementation!\n";
echo "</pre>\n";
?>
