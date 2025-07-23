<?php
/**
 * Inventory Synchronization Background Job
 * Manufacturing Distribution Platform - Feature 3
 * 
 * Handles periodic inventory synchronization with external systems
 * Runs every 15 minutes to keep inventory data current
 */

require_once('config.php');

class InventorySyncJob {
    private $db;
    private $job_id;
    private $log_file;
    
    public function __construct() {
        global $sugar_config;
        
        $host = str_replace(':3307', '', $sugar_config['dbconfig']['db_host_name']);
        $this->db = new mysqli(
            $host,
            $sugar_config['dbconfig']['db_user_name'],
            $sugar_config['dbconfig']['db_password'],
            $sugar_config['dbconfig']['db_name'],
            3307
        );
        
        if ($this->db->connect_error) {
            die("Database connection failed: " . $this->db->connect_error);
        }
        
        $this->job_id = $this->generateUUID();
        $this->log_file = "cache/inventory_sync_" . date('Y-m-d') . ".log";
        
        $this->log("InventorySyncJob initialized with ID: " . $this->job_id);
    }
    
    /**
     * Main execution method - runs the complete sync process
     */
    public function execute() {
        $this->log("=== Starting Inventory Sync Job ===");
        $start_time = microtime(true);
        
        try {
            // Step 1: Clean up expired reservations
            $this->cleanupExpiredReservations();
            
            // Step 2: Sync with external inventory systems
            $this->syncExternalSystems();
            
            // Step 3: Update stock status based on current levels
            $this->updateStockStatus();
            
            // Step 4: Generate low stock alerts
            $this->generateLowStockAlerts();
            
            // Step 5: Send notifications
            $this->sendNotifications();
            
            $execution_time = round(microtime(true) - $start_time, 2);
            $this->log("=== Inventory Sync Job Completed Successfully in {$execution_time}s ===");
            
            return [
                'success' => true,
                'job_id' => $this->job_id,
                'execution_time' => $execution_time
            ];
            
        } catch (Exception $e) {
            $execution_time = round(microtime(true) - $start_time, 2);
            $this->log("ERROR: Inventory Sync Job Failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'job_id' => $this->job_id,
                'error' => $e->getMessage(),
                'execution_time' => $execution_time
            ];
        }
    }
    
    /**
     * Clean up expired stock reservations
     */
    private function cleanupExpiredReservations() {
        $this->log("Cleaning up expired reservations...");
        
        // Get expired reservations
        $stmt = $this->db->prepare("
            SELECT id, product_id, warehouse_id, reserved_quantity
            FROM mfg_stock_reservations 
            WHERE status = 'active' AND expiration_date < NOW() AND deleted = 0
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $expired_count = 0;
        
        while ($reservation = $result->fetch_assoc()) {
            // Update reservation status
            $update_res = $this->db->prepare("
                UPDATE mfg_stock_reservations 
                SET status = 'expired', date_modified = NOW()
                WHERE id = ?
            ");
            $update_res->bind_param('s', $reservation['id']);
            $update_res->execute();
            
            // Release reserved stock
            $update_inv = $this->db->prepare("
                UPDATE mfg_inventory 
                SET reserved_stock = reserved_stock - ?, date_modified = NOW()
                WHERE product_id = ? AND warehouse_id = ? AND deleted = 0
            ");
            $update_inv->bind_param('dss', 
                $reservation['reserved_quantity'],
                $reservation['product_id'],
                $reservation['warehouse_id']
            );
            $update_inv->execute();
            
            // Record movement
            $this->recordMovement(
                $reservation['product_id'],
                $reservation['warehouse_id'],
                'release',
                $reservation['reserved_quantity'],
                0,
                'system',
                $reservation['id'],
                'Automatic release of expired reservation'
            );
            
            $expired_count++;
        }
        
        $this->log("Cleaned up $expired_count expired reservations");
    }
    
    /**
     * Sync inventory data with external systems
     */
    private function syncExternalSystems() {
        $this->log("Syncing with external inventory systems...");
        
        // Get list of external systems to sync with
        $external_systems = [
            'ERP_SYSTEM_1' => 'http://localhost:3000/mock_inventory_api.php',
            'WAREHOUSE_MANAGEMENT' => 'http://localhost:3000/mock_warehouse_api.php',
            'SUPPLIER_PORTAL' => 'http://localhost:3000/mock_supplier_api.php'
        ];
        
        $total_synced = 0;
        
        foreach ($external_systems as $system_name => $endpoint) {
            try {
                $this->log("Syncing with $system_name...");
                
                // Simulate API call to external system
                $sync_data = $this->fetchExternalInventoryData($endpoint);
                
                if ($sync_data && isset($sync_data['items'])) {
                    $synced = $this->processExternalData($system_name, $sync_data['items']);
                    $total_synced += $synced;
                    $this->log("Synced $synced items from $system_name");
                } else {
                    $this->log("No data received from $system_name");
                }
                
            } catch (Exception $e) {
                $this->log("ERROR syncing with $system_name: " . $e->getMessage());
            }
        }
        
        $this->log("Total items synced: $total_synced");
    }
    
    /**
     * Fetch inventory data from external system
     */
    private function fetchExternalInventoryData($endpoint) {
        // For demo purposes, we'll simulate API responses
        // In production, this would make actual HTTP requests
        
        $mock_data = [
            'items' => []
        ];
        
        // Get some random products to simulate updates
        $products_stmt = $this->db->prepare("
            SELECT i.product_id, i.warehouse_id, i.current_stock 
            FROM mfg_inventory i 
            WHERE i.deleted = 0 
            ORDER BY RAND() 
            LIMIT 10
        ");
        $products_stmt->execute();
        $result = $products_stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Simulate stock changes
            $current_stock = $row['current_stock'];
            $change = rand(-50, 100); // Random stock change
            $new_stock = max(0, $current_stock + $change);
            
            $mock_data['items'][] = [
                'product_id' => $row['product_id'],
                'warehouse_id' => $row['warehouse_id'],
                'current_stock' => $new_stock,
                'unit_cost' => round(rand(100, 5000) / 100, 2),
                'last_updated' => date('Y-m-d H:i:s')
            ];
        }
        
        return $mock_data;
    }
    
    /**
     * Process external inventory data
     */
    private function processExternalData($system_name, $items) {
        $processed = 0;
        
        foreach ($items as $item) {
            if (!isset($item['product_id']) || !isset($item['warehouse_id'])) {
                continue;
            }
            
            // Update inventory record
            $stmt = $this->db->prepare("
                UPDATE mfg_inventory 
                SET current_stock = ?, 
                    unit_cost = ?, 
                    date_modified = NOW(),
                    modified_user_id = '1'
                WHERE product_id = ? AND warehouse_id = ? AND deleted = 0
            ");
            
            $current_stock = $item['current_stock'] ?? 0;
            $unit_cost = $item['unit_cost'] ?? 0;
            
            $stmt->bind_param('ddss', 
                $current_stock, $unit_cost, 
                $item['product_id'], $item['warehouse_id']
            );
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // Record the movement
                $this->recordMovement(
                    $item['product_id'],
                    $item['warehouse_id'],
                    'adjustment',
                    $current_stock,
                    $unit_cost,
                    'api_sync',
                    $system_name,
                    "Automatic sync from $system_name"
                );
                
                $processed++;
            }
        }
        
        return $processed;
    }
    
    /**
     * Update stock status based on current inventory levels
     */
    private function updateStockStatus() {
        $this->log("Updating stock status...");
        
        $stmt = $this->db->prepare("
            UPDATE mfg_inventory 
            SET stock_status = CASE
                WHEN current_stock <= 0 THEN 'out_of_stock'
                WHEN current_stock <= reorder_point THEN 'low_stock'
                WHEN current_stock >= max_stock_level AND max_stock_level > 0 THEN 'overstock'
                ELSE 'in_stock'
            END,
            date_modified = NOW()
            WHERE deleted = 0
        ");
        
        $stmt->execute();
        $updated_count = $stmt->affected_rows;
        
        $this->log("Updated stock status for $updated_count inventory records");
    }
    
    /**
     * Generate low stock alerts
     */
    private function generateLowStockAlerts() {
        $this->log("Generating low stock alerts...");
        
        // Get items that need reordering
        $stmt = $this->db->prepare("
            SELECT 
                i.product_id,
                p.name as product_name,
                p.sku,
                i.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                i.current_stock,
                i.reorder_point,
                i.reorder_quantity,
                (i.reorder_point - i.current_stock) as shortage_quantity
            FROM mfg_inventory i
            JOIN mfg_warehouses w ON i.warehouse_id = w.id
            LEFT JOIN mfg_products p ON i.product_id = p.id
            WHERE i.current_stock <= i.reorder_point 
                AND i.stock_status = 'low_stock'
                AND i.deleted = 0 
                AND w.deleted = 0
            ORDER BY (i.reorder_point - i.current_stock) DESC
        ");
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $low_stock_items = [];
        while ($row = $result->fetch_assoc()) {
            $low_stock_items[] = $row;
        }
        
        if (!empty($low_stock_items)) {
            // Save alerts to a file that can be picked up by notification system
            $alert_file = "cache/low_stock_alerts_" . date('Y-m-d_H-i-s') . ".json";
            file_put_contents($alert_file, json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'job_id' => $this->job_id,
                'alert_type' => 'low_stock',
                'items' => $low_stock_items
            ], JSON_PRETTY_PRINT));
            
            $this->log("Generated " . count($low_stock_items) . " low stock alerts: $alert_file");
        } else {
            $this->log("No low stock items found");
        }
    }
    
    /**
     * Send notifications (webhooks, emails, etc.)
     */
    private function sendNotifications() {
        $this->log("Processing notifications...");
        
        // Get active webhooks
        $webhook_stmt = $this->db->prepare("
            SELECT webhook_name, endpoint_url, event_types, secret_key
            FROM mfg_inventory_webhooks 
            WHERE is_active = 1 AND deleted = 0
        ");
        $webhook_stmt->execute();
        $webhooks = $webhook_stmt->get_result();
        
        $notification_count = 0;
        
        while ($webhook = $webhooks->fetch_assoc()) {
            $event_types = json_decode($webhook['event_types'], true) ?? [];
            
            if (in_array('inventory_sync', $event_types)) {
                // Send webhook notification
                $payload = [
                    'event' => 'inventory_sync_completed',
                    'job_id' => $this->job_id,
                    'timestamp' => date('Y-m-d H:i:s'),
                    'webhook_name' => $webhook['webhook_name']
                ];
                
                // In production, this would make actual HTTP POST requests
                $this->log("Would send webhook to: " . $webhook['endpoint_url']);
                $notification_count++;
            }
        }
        
        $this->log("Processed $notification_count webhook notifications");
    }
    
    /**
     * Record stock movement
     */
    private function recordMovement($product_id, $warehouse_id, $movement_type, $quantity, $unit_cost, $reference_type, $reference_id, $notes) {
        $movement_id = $this->generateUUID();
        
        $stmt = $this->db->prepare("
            INSERT INTO mfg_stock_movements 
            (id, product_id, warehouse_id, movement_type, quantity, unit_cost,
             reference_type, reference_id, notes, performed_by, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '1', '1')
        ");
        
        $stmt->bind_param('ssssdssss',
            $movement_id, $product_id, $warehouse_id, $movement_type,
            $quantity, $unit_cost, $reference_type, $reference_id, $notes
        );
        
        return $stmt->execute();
    }
    
    /**
     * Utility methods
     */
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message\n";
        
        echo $log_entry; // Output to console
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Command line execution
if (php_sapi_name() === 'cli') {
    $job = new InventorySyncJob();
    $result = $job->execute();
    
    if ($result['success']) {
        echo "Inventory sync job completed successfully\n";
        exit(0);
    } else {
        echo "Inventory sync job failed: " . $result['error'] . "\n";
        exit(1);
    }
} else {
    // Web interface for manual triggering
    if (isset($_GET['action']) && $_GET['action'] === 'run') {
        $job = new InventorySyncJob();
        $result = $job->execute();
        
        header('Content-Type: application/json');
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        echo "<h2>Inventory Sync Job</h2>";
        echo "<p><a href='?action=run'>Run Sync Job Manually</a></p>";
        echo "<p>This job runs automatically every 15 minutes via cron job.</p>";
    }
}
?>
