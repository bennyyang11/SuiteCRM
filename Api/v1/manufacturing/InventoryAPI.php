<?php
/**
 * Real-Time Inventory Integration API
 * Manufacturing Distribution Platform - Feature 3
 * 
 * Provides comprehensive inventory management endpoints:
 * - Stock level queries across warehouses
 * - Bulk inventory synchronization
 * - Stock reservations for quotes
 * - Low stock alerts and reporting
 * - Movement history tracking
 */

require_once('config.php');
require_once('include/utils.php');

class InventoryAPI {
    private $db;
    private $response_format = 'json';
    
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
            $this->sendError(500, "Database connection failed: " . $this->db->connect_error);
        }
        
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }
    
    /**
     * Main request router
     */
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path_parts = explode('/', trim($path, '/'));
        
        // Handle OPTIONS for CORS
        if ($method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        try {
            // Route to appropriate handler
            if (count($path_parts) >= 4 && $path_parts[3] === 'inventory') {
                $action = $path_parts[4] ?? '';
                
                switch ($method) {
                    case 'GET':
                        $this->handleGet($action, $path_parts);
                        break;
                    case 'POST':
                        $this->handlePost($action, $path_parts);
                        break;
                    case 'PUT':
                        $this->handlePut($action, $path_parts);
                        break;
                    default:
                        $this->sendError(405, "Method not allowed");
                }
            } else {
                $this->sendError(404, "Endpoint not found");
            }
        } catch (Exception $e) {
            $this->sendError(500, "Internal server error: " . $e->getMessage());
        }
    }
    
    /**
     * Handle GET requests
     */
    private function handleGet($action, $path_parts) {
        switch ($action) {
            case 'stock':
                if (isset($path_parts[5])) {
                    $this->getProductStock($path_parts[5]);
                } else {
                    $this->sendError(400, "Product ID required");
                }
                break;
            case 'low-stock':
                $this->getLowStockItems();
                break;
            case 'movements':
                if (isset($path_parts[5])) {
                    $this->getStockMovements($path_parts[5]);
                } else {
                    $this->sendError(400, "Product ID required");
                }
                break;
            case 'warehouses':
                $this->getWarehouses();
                break;
            case 'summary':
                $this->getInventorySummary();
                break;
            default:
                $this->sendError(404, "Action not found");
        }
    }
    
    /**
     * Handle POST requests
     */
    private function handlePost($action, $path_parts) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'sync':
                $this->bulkSyncInventory($input);
                break;
            case 'reserve':
                $this->reserveStock($input);
                break;
            case 'release':
                $this->releaseStock($input);
                break;
            case 'movement':
                $this->recordStockMovement($input);
                break;
            default:
                $this->sendError(404, "Action not found");
        }
    }
    
    /**
     * Handle PUT requests
     */
    private function handlePut($action, $path_parts) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if ($action && isset($path_parts[5]) && $path_parts[6] === 'warehouse' && isset($path_parts[7])) {
            $this->updateWarehouseStock($path_parts[5], $path_parts[7], $input);
        } else {
            $this->sendError(400, "Invalid PUT request");
        }
    }
    
    /**
     * GET /inventory/stock/{product_id} - Get current stock across all warehouses
     */
    private function getProductStock($product_id) {
        $stmt = $this->db->prepare("
            SELECT 
                i.id,
                i.product_id,
                p.name as product_name,
                p.sku,
                i.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                i.current_stock,
                i.reserved_stock,
                i.available_stock,
                i.reorder_point,
                i.reorder_quantity,
                i.max_stock_level,
                i.unit_cost,
                i.stock_status,
                i.location_bin,
                i.next_delivery_date,
                i.expected_quantity,
                i.supplier_lead_time,
                i.last_count_date,
                CASE 
                    WHEN i.current_stock <= 0 THEN 'out_of_stock'
                    WHEN i.current_stock <= i.reorder_point THEN 'low_stock'
                    WHEN i.current_stock >= i.max_stock_level THEN 'overstock'
                    ELSE 'in_stock'
                END as calculated_status
            FROM mfg_inventory i
            JOIN mfg_warehouses w ON i.warehouse_id = w.id
            LEFT JOIN mfg_products p ON i.product_id = p.id
            WHERE i.product_id = ? AND i.deleted = 0 AND w.deleted = 0
            ORDER BY w.priority ASC, w.name ASC
        ");
        
        $stmt->bind_param('s', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $inventory_data = [];
        $total_stock = 0;
        $total_available = 0;
        $total_reserved = 0;
        
        while ($row = $result->fetch_assoc()) {
            $total_stock += $row['current_stock'];
            $total_available += $row['available_stock'];
            $total_reserved += $row['reserved_stock'];
            $inventory_data[] = $row;
        }
        
        if (empty($inventory_data)) {
            $this->sendError(404, "Product not found in inventory");
        }
        
        $response = [
            'product_id' => $product_id,
            'product_name' => $inventory_data[0]['product_name'] ?? '',
            'sku' => $inventory_data[0]['sku'] ?? '',
            'total_stock' => $total_stock,
            'total_available' => $total_available,
            'total_reserved' => $total_reserved,
            'warehouse_count' => count($inventory_data),
            'overall_status' => $this->calculateOverallStatus($inventory_data),
            'warehouses' => $inventory_data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->sendSuccess($response);
    }
    
    /**
     * POST /inventory/sync - Bulk sync inventory data from external systems
     */
    private function bulkSyncInventory($data) {
        if (!isset($data['items']) || !is_array($data['items'])) {
            $this->sendError(400, "Items array required");
        }
        
        $sync_id = $this->generateUUID();
        $external_system = $data['external_system'] ?? 'unknown';
        $sync_type = $data['sync_type'] ?? 'incremental';
        
        // Start sync log
        $this->recordSyncStart($sync_id, $sync_type, $external_system, count($data['items']));
        
        $processed = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        
        $this->db->begin_transaction();
        
        try {
            foreach ($data['items'] as $item) {
                $processed++;
                
                if (!isset($item['product_id']) || !isset($item['warehouse_id'])) {
                    $failed++;
                    $errors[] = "Missing product_id or warehouse_id for item $processed";
                    continue;
                }
                
                // Update inventory record
                $update_stmt = $this->db->prepare("
                    INSERT INTO mfg_inventory 
                    (id, product_id, warehouse_id, current_stock, reserved_stock, 
                     reorder_point, reorder_quantity, unit_cost, stock_status, 
                     supplier_lead_time, next_delivery_date, expected_quantity,
                     date_modified, modified_user_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), '1')
                    ON DUPLICATE KEY UPDATE
                    current_stock = VALUES(current_stock),
                    reserved_stock = VALUES(reserved_stock),
                    reorder_point = VALUES(reorder_point),
                    reorder_quantity = VALUES(reorder_quantity),
                    unit_cost = VALUES(unit_cost),
                    stock_status = VALUES(stock_status),
                    supplier_lead_time = VALUES(supplier_lead_time),
                    next_delivery_date = VALUES(next_delivery_date),
                    expected_quantity = VALUES(expected_quantity),
                    date_modified = NOW(),
                    modified_user_id = '1'
                ");
                
                $inventory_id = $this->generateUUID();
                $current_stock = $item['current_stock'] ?? 0;
                $reserved_stock = $item['reserved_stock'] ?? 0;
                $reorder_point = $item['reorder_point'] ?? 0;
                $reorder_quantity = $item['reorder_quantity'] ?? 0;
                $unit_cost = $item['unit_cost'] ?? 0;
                $stock_status = $item['stock_status'] ?? 'in_stock';
                $supplier_lead_time = $item['supplier_lead_time'] ?? 0;
                $next_delivery_date = $item['next_delivery_date'] ?? null;
                $expected_quantity = $item['expected_quantity'] ?? 0;
                
                $update_stmt->bind_param('sssdddddsissd',
                    $inventory_id, $item['product_id'], $item['warehouse_id'],
                    $current_stock, $reserved_stock, $reorder_point, $reorder_quantity,
                    $unit_cost, $stock_status, $supplier_lead_time, $next_delivery_date,
                    $expected_quantity
                );
                
                if ($update_stmt->execute()) {
                    $updated++;
                    
                    // Record stock movement
                    $this->recordMovement(
                        $item['product_id'],
                        $item['warehouse_id'],
                        'adjustment',
                        $current_stock,
                        $unit_cost,
                        'api_sync',
                        $sync_id,
                        'Bulk sync from ' . $external_system
                    );
                } else {
                    $failed++;
                    $errors[] = "Failed to update product {$item['product_id']}: " . $update_stmt->error;
                }
            }
            
            $this->db->commit();
            
            // Complete sync log
            $this->recordSyncComplete($sync_id, 'completed', $processed, $updated, $failed, $errors);
            
            $response = [
                'sync_id' => $sync_id,
                'status' => 'completed',
                'processed' => $processed,
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->sendSuccess($response);
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->recordSyncComplete($sync_id, 'failed', $processed, $updated, $failed, [$e->getMessage()]);
            throw $e;
        }
    }
    
    /**
     * POST /inventory/reserve - Reserve stock for quotes
     */
    private function reserveStock($data) {
        if (!isset($data['product_id']) || !isset($data['warehouse_id']) || !isset($data['quantity'])) {
            $this->sendError(400, "product_id, warehouse_id, and quantity required");
        }
        
        $product_id = $data['product_id'];
        $warehouse_id = $data['warehouse_id'];
        $quantity = $data['quantity'];
        $quote_id = $data['quote_id'] ?? null;
        $reserved_by = $data['reserved_by'] ?? '1';
        $expiration_hours = $data['expiration_hours'] ?? 24;
        $notes = $data['notes'] ?? '';
        
        $this->db->begin_transaction();
        
        try {
            // Check available stock
            $check_stmt = $this->db->prepare("
                SELECT current_stock, reserved_stock, available_stock 
                FROM mfg_inventory 
                WHERE product_id = ? AND warehouse_id = ? AND deleted = 0
            ");
            $check_stmt->bind_param('ss', $product_id, $warehouse_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->sendError(404, "Product not found in warehouse");
            }
            
            $inventory = $result->fetch_assoc();
            if ($inventory['available_stock'] < $quantity) {
                $this->sendError(400, "Insufficient stock. Available: {$inventory['available_stock']}, Requested: $quantity");
            }
            
            // Create reservation
            $reservation_id = $this->generateUUID();
            $expiration_date = date('Y-m-d H:i:s', strtotime("+$expiration_hours hours"));
            
            $reserve_stmt = $this->db->prepare("
                INSERT INTO mfg_stock_reservations 
                (id, product_id, warehouse_id, quote_id, reserved_quantity, 
                 reserved_by, expiration_date, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $reserve_stmt->bind_param('ssssdssss',
                $reservation_id, $product_id, $warehouse_id, $quote_id,
                $quantity, $reserved_by, $expiration_date, $notes, $reserved_by
            );
            
            if (!$reserve_stmt->execute()) {
                throw new Exception("Failed to create reservation: " . $reserve_stmt->error);
            }
            
            // Update inventory reserved stock
            $update_stmt = $this->db->prepare("
                UPDATE mfg_inventory 
                SET reserved_stock = reserved_stock + ?, date_modified = NOW()
                WHERE product_id = ? AND warehouse_id = ? AND deleted = 0
            ");
            $update_stmt->bind_param('dss', $quantity, $product_id, $warehouse_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update reserved stock: " . $update_stmt->error);
            }
            
            // Record movement
            $this->recordMovement(
                $product_id, $warehouse_id, 'reservation', $quantity, 0,
                'quote', $quote_id, "Stock reserved for quote: $notes"
            );
            
            $this->db->commit();
            
            $response = [
                'reservation_id' => $reservation_id,
                'product_id' => $product_id,
                'warehouse_id' => $warehouse_id,
                'reserved_quantity' => $quantity,
                'expiration_date' => $expiration_date,
                'status' => 'active',
                'remaining_stock' => $inventory['available_stock'] - $quantity,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->sendSuccess($response);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * POST /inventory/release - Release reserved stock
     */
    private function releaseStock($data) {
        if (!isset($data['reservation_id'])) {
            $this->sendError(400, "reservation_id required");
        }
        
        $reservation_id = $data['reservation_id'];
        $reason = $data['reason'] ?? 'manual_release';
        
        $this->db->begin_transaction();
        
        try {
            // Get reservation details
            $get_stmt = $this->db->prepare("
                SELECT product_id, warehouse_id, reserved_quantity, status
                FROM mfg_stock_reservations 
                WHERE id = ? AND deleted = 0
            ");
            $get_stmt->bind_param('s', $reservation_id);
            $get_stmt->execute();
            $result = $get_stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->sendError(404, "Reservation not found");
            }
            
            $reservation = $result->fetch_assoc();
            if ($reservation['status'] !== 'active') {
                $this->sendError(400, "Reservation is not active");
            }
            
            // Update reservation status
            $update_res_stmt = $this->db->prepare("
                UPDATE mfg_stock_reservations 
                SET status = 'cancelled', date_modified = NOW()
                WHERE id = ?
            ");
            $update_res_stmt->bind_param('s', $reservation_id);
            $update_res_stmt->execute();
            
            // Update inventory reserved stock
            $update_inv_stmt = $this->db->prepare("
                UPDATE mfg_inventory 
                SET reserved_stock = reserved_stock - ?, date_modified = NOW()
                WHERE product_id = ? AND warehouse_id = ? AND deleted = 0
            ");
            $update_inv_stmt->bind_param('dss', 
                $reservation['reserved_quantity'],
                $reservation['product_id'],
                $reservation['warehouse_id']
            );
            $update_inv_stmt->execute();
            
            // Record movement
            $this->recordMovement(
                $reservation['product_id'],
                $reservation['warehouse_id'],
                'release',
                $reservation['reserved_quantity'],
                0,
                'manual',
                $reservation_id,
                "Stock released: $reason"
            );
            
            $this->db->commit();
            
            $response = [
                'reservation_id' => $reservation_id,
                'released_quantity' => $reservation['reserved_quantity'],
                'reason' => $reason,
                'status' => 'released',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $this->sendSuccess($response);
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * GET /inventory/low-stock - Get products below reorder point
     */
    private function getLowStockItems() {
        $limit = $_GET['limit'] ?? 100;
        $warehouse_id = $_GET['warehouse_id'] ?? null;
        
        $where_clause = "WHERE i.current_stock <= i.reorder_point AND i.deleted = 0 AND w.deleted = 0";
        if ($warehouse_id) {
            $where_clause .= " AND i.warehouse_id = '$warehouse_id'";
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                i.id,
                i.product_id,
                p.name as product_name,
                p.sku,
                i.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                i.current_stock,
                i.reorder_point,
                i.reorder_quantity,
                i.max_stock_level,
                i.stock_status,
                (i.reorder_point - i.current_stock) as shortage_quantity,
                i.next_delivery_date,
                i.expected_quantity,
                i.supplier_lead_time
            FROM mfg_inventory i
            JOIN mfg_warehouses w ON i.warehouse_id = w.id
            LEFT JOIN mfg_products p ON i.product_id = p.id
            $where_clause
            ORDER BY (i.reorder_point - i.current_stock) DESC, w.priority ASC
            LIMIT ?
        ");
        
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $low_stock_items = [];
        while ($row = $result->fetch_assoc()) {
            $low_stock_items[] = $row;
        }
        
        $response = [
            'low_stock_items' => $low_stock_items,
            'total_count' => count($low_stock_items),
            'warehouse_filter' => $warehouse_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->sendSuccess($response);
    }
    
    /**
     * GET /inventory/movements/{product_id} - Get stock movement history
     */
    private function getStockMovements($product_id) {
        $limit = $_GET['limit'] ?? 50;
        $warehouse_id = $_GET['warehouse_id'] ?? null;
        
        $where_clause = "WHERE m.product_id = ? AND m.deleted = 0";
        $params = [$product_id];
        $types = 's';
        
        if ($warehouse_id) {
            $where_clause .= " AND m.warehouse_id = ?";
            $params[] = $warehouse_id;
            $types .= 's';
        }
        
        $stmt = $this->db->prepare("
            SELECT 
                m.id,
                m.product_id,
                p.name as product_name,
                p.sku,
                m.warehouse_id,
                w.name as warehouse_name,
                w.code as warehouse_code,
                m.movement_type,
                m.quantity,
                m.unit_cost,
                m.reference_type,
                m.reference_id,
                m.notes,
                m.movement_date,
                m.batch_number,
                m.lot_number,
                u.user_name as performed_by_name
            FROM mfg_stock_movements m
            JOIN mfg_warehouses w ON m.warehouse_id = w.id
            LEFT JOIN mfg_products p ON m.product_id = p.id
            LEFT JOIN users u ON m.performed_by = u.id
            $where_clause
            ORDER BY m.movement_date DESC
            LIMIT ?
        ");
        
        $params[] = $limit;
        $types .= 'i';
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $movements = [];
        while ($row = $result->fetch_assoc()) {
            $movements[] = $row;
        }
        
        $response = [
            'product_id' => $product_id,
            'movements' => $movements,
            'total_count' => count($movements),
            'warehouse_filter' => $warehouse_id,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->sendSuccess($response);
    }
    
    /**
     * Helper method to record stock movements
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
     * Helper methods for sync logging
     */
    private function recordSyncStart($sync_id, $sync_type, $external_system, $products_count) {
        $stmt = $this->db->prepare("
            INSERT INTO mfg_inventory_sync_log 
            (id, sync_type, external_system, products_processed, sync_status, performed_by)
            VALUES (?, ?, ?, ?, 'running', '1')
        ");
        $stmt->bind_param('sssi', $sync_id, $sync_type, $external_system, $products_count);
        $stmt->execute();
    }
    
    private function recordSyncComplete($sync_id, $status, $processed, $updated, $failed, $errors) {
        $error_message = empty($errors) ? null : json_encode($errors);
        
        $stmt = $this->db->prepare("
            UPDATE mfg_inventory_sync_log 
            SET sync_status = ?, products_processed = ?, products_updated = ?, 
                products_failed = ?, error_message = ?, end_time = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('siiiss', $status, $processed, $updated, $failed, $error_message, $sync_id);
        $stmt->execute();
    }
    
    /**
     * Additional helper methods
     */
    private function calculateOverallStatus($inventory_data) {
        $has_stock = false;
        $has_low_stock = false;
        
        foreach ($inventory_data as $item) {
            if ($item['current_stock'] > 0) {
                $has_stock = true;
            }
            if ($item['current_stock'] <= $item['reorder_point']) {
                $has_low_stock = true;
            }
        }
        
        if (!$has_stock) return 'out_of_stock';
        if ($has_low_stock) return 'low_stock';
        return 'in_stock';
    }
    
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    private function sendSuccess($data, $code = 200) {
        http_response_code($code);
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    private function sendError($code, $message) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

// Handle the request
$api = new InventoryAPI();
$api->handleRequest();
?>
