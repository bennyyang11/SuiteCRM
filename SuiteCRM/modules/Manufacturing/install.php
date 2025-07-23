<?php
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('modules/Administration/QuickRepairAndRebuild.php');

class ManufacturingInstaller
{
    public function install()
    {
        global $db;
        
        // Create manufacturing_products table
        $products_sql = "
        CREATE TABLE IF NOT EXISTS manufacturing_products (
            id CHAR(36) NOT NULL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            sku VARCHAR(100) UNIQUE,
            category VARCHAR(100),
            base_price DECIMAL(10,2),
            description TEXT,
            specifications TEXT,
            date_entered DATETIME,
            date_modified DATETIME,
            modified_user_id CHAR(36),
            created_by CHAR(36),
            deleted TINYINT(1) DEFAULT 0
        )";
        
        // Create manufacturing_pipeline table
        $pipeline_sql = "
        CREATE TABLE IF NOT EXISTS manufacturing_pipeline (
            id CHAR(36) NOT NULL PRIMARY KEY,
            order_id VARCHAR(100),
            client_id CHAR(36),
            stage VARCHAR(50),
            priority VARCHAR(20),
            value DECIMAL(12,2),
            expected_close_date DATE,
            probability INT,
            description TEXT,
            date_entered DATETIME,
            date_modified DATETIME,
            modified_user_id CHAR(36),
            created_by CHAR(36),
            deleted TINYINT(1) DEFAULT 0
        )";
        
        // Create manufacturing_client_pricing table
        $pricing_sql = "
        CREATE TABLE IF NOT EXISTS manufacturing_client_pricing (
            id CHAR(36) NOT NULL PRIMARY KEY,
            client_id CHAR(36),
            product_id CHAR(36),
            price DECIMAL(10,2),
            discount_percentage DECIMAL(5,2),
            date_entered DATETIME,
            date_modified DATETIME,
            modified_user_id CHAR(36),
            created_by CHAR(36),
            deleted TINYINT(1) DEFAULT 0
        )";
        
        // Create manufacturing_pipeline_history table
        $history_sql = "
        CREATE TABLE IF NOT EXISTS manufacturing_pipeline_history (
            id CHAR(36) NOT NULL PRIMARY KEY,
            pipeline_id CHAR(36),
            old_stage VARCHAR(50),
            new_stage VARCHAR(50),
            changed_by CHAR(36),
            date_changed DATETIME
        )";
        
        try {
            $db->query($products_sql);
            $db->query($pipeline_sql);
            $db->query($pricing_sql);
            $db->query($history_sql);
            
            // Insert sample data
            $this->insertSampleData();
            
            // Register module
            $this->registerModule();
            
            // Quick Repair and Rebuild
            $repair = new RepairAndClear();
            $repair->repairAndClearAll(array('clearAll'), array('Manufacturing'), false, false);
            
            return true;
        } catch (Exception $e) {
            error_log('Manufacturing Module Installation Error: ' . $e->getMessage());
            return false;
        }
    }
    
    private function insertSampleData()
    {
        global $db;
        
        // Sample products
        $products = array(
            array(
                'id' => create_guid(),
                'name' => 'Industrial Motor 5HP',
                'sku' => 'MOT-5HP-001',
                'category' => 'electrical',
                'base_price' => 1299.99,
                'description' => 'High-efficiency 5HP industrial motor for manufacturing applications'
            ),
            array(
                'id' => create_guid(),
                'name' => 'Precision Ball Bearing Set',
                'sku' => 'BER-PRC-001',
                'category' => 'mechanical',
                'base_price' => 89.50,
                'description' => 'Premium precision ball bearings for high-speed applications'
            ),
            array(
                'id' => create_guid(),
                'name' => 'Pneumatic Wrench Kit',
                'sku' => 'TWL-PNE-001',
                'category' => 'tools',
                'base_price' => 445.00,
                'description' => 'Professional pneumatic wrench kit with multiple attachments'
            )
        );
        
        foreach ($products as $product) {
            $sql = "INSERT INTO manufacturing_products (id, name, sku, category, base_price, description, date_entered, date_modified, deleted) 
                   VALUES ('{$product['id']}', '{$product['name']}', '{$product['sku']}', '{$product['category']}', 
                          {$product['base_price']}, '{$product['description']}', NOW(), NOW(), 0)";
            $db->query($sql);
        }
        
        // Sample pipeline data
        $pipeline_data = array(
            array(
                'id' => create_guid(),
                'order_id' => 'ORD-2025-001',
                'client_id' => 'sample-client-1',
                'stage' => 'Lead',
                'priority' => 'High',
                'value' => 15000.00,
                'probability' => 25,
                'description' => 'Large order for industrial motors and bearings'
            ),
            array(
                'id' => create_guid(),
                'order_id' => 'ORD-2025-002',
                'client_id' => 'sample-client-2',
                'stage' => 'Qualified',
                'priority' => 'Medium',
                'value' => 8500.00,
                'probability' => 60,
                'description' => 'Tool kit order for maintenance department'
            )
        );
        
        foreach ($pipeline_data as $pipeline) {
            $sql = "INSERT INTO manufacturing_pipeline (id, order_id, client_id, stage, priority, value, probability, description, date_entered, date_modified, deleted) 
                   VALUES ('{$pipeline['id']}', '{$pipeline['order_id']}', '{$pipeline['client_id']}', '{$pipeline['stage']}', 
                          '{$pipeline['priority']}', {$pipeline['value']}, {$pipeline['probability']}, '{$pipeline['description']}', NOW(), NOW(), 0)";
            $db->query($sql);
        }
    }
    
    private function registerModule()
    {
        global $db;
        
        // Add module to modules table if it doesn't exist
        $check_sql = "SELECT * FROM modules WHERE module_name = 'Manufacturing'";
        $result = $db->query($check_sql);
        
        if ($db->getRowCount($result) == 0) {
            $insert_sql = "INSERT INTO modules (module_name, display_name, table_name, enabled) 
                          VALUES ('Manufacturing', 'Manufacturing', 'manufacturing', 1)";
            $db->query($insert_sql);
        }
    }
}

// Install the module
$installer = new ManufacturingInstaller();
if ($installer->install()) {
    echo "Manufacturing module installed successfully!\n";
} else {
    echo "Failed to install Manufacturing module.\n";
}
