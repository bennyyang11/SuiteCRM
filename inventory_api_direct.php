<?php
/**
 * Direct Inventory API Access
 * Manufacturing Distribution Platform - Feature 3
 * 
 * Simple API endpoint for inventory operations
 */

require_once('config.php');

// Simple API router
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

// Database connection
global $sugar_config;
$host = str_replace(':3307', '', $sugar_config['dbconfig']['db_host_name']);
$db = new mysqli(
    $host,
    $sugar_config['dbconfig']['db_user_name'],
    $sugar_config['dbconfig']['db_password'],
    $sugar_config['dbconfig']['db_name'],
    3307
);

if ($db->connect_error) {
    sendError(500, "Database connection failed");
}

try {
    switch ($action) {
        case 'get_product_stock':
            getProductStock($db, $_GET['product_id'] ?? '');
            break;
        case 'get_low_stock':
            getLowStockItems($db);
            break;
        case 'reserve_stock':
            reserveStock($db, json_decode(file_get_contents('php://input'), true));
            break;
        case 'release_stock':
            releaseStock($db, json_decode(file_get_contents('php://input'), true));
            break;
        case 'sync_inventory':
            syncInventory($db, json_decode(file_get_contents('php://input'), true));
            break;
        case 'get_warehouses':
            getWarehouses($db);
            break;
        case 'get_summary':
            getInventorySummary($db);
            break;
        default:
            sendError(404, "Action not found. Available actions: get_product_stock, get_low_stock, reserve_stock, release_stock, sync_inventory, get_warehouses, get_summary");
    }
} catch (Exception $e) {
    sendError(500, "Internal error: " . $e->getMessage());
}

/**
 * Get product stock across all warehouses
 */
function getProductStock($db, $product_id) {
    if (empty($product_id)) {
        sendError(400, "product_id parameter required");
    }
    
    $stmt = $db->prepare("
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
            i.stock_status,
            i.unit_cost
        FROM mfg_inventory i
        JOIN mfg_warehouses w ON i.warehouse_id = w.id
        LEFT JOIN mfg_products p ON i.product_id = p.id
        WHERE i.product_id = ? AND i.deleted = 0 AND w.deleted = 0
        ORDER BY w.priority ASC
    ");
    
    $stmt->bind_param('s', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $warehouses = [];
    $total_stock = 0;
    $total_available = 0;
    
    while ($row = $result->fetch_assoc()) {
        $total_stock += $row['current_stock'];
        $total_available += $row['available_stock'];
        $warehouses[] = $row;
    }
    
    if (empty($warehouses)) {
        sendError(404, "Product not found in inventory");
    }
    
    sendSuccess([
        'product_id' => $product_id,
        'product_name' => $warehouses[0]['product_name'],
        'total_stock' => $total_stock,
        'total_available' => $total_available,
        'warehouse_count' => count($warehouses),
        'warehouses' => $warehouses
    ]);
}

/**
 * Get low stock items
 */
function getLowStockItems($db) {
    $limit = $_GET['limit'] ?? 20;
    
    $stmt = $db->prepare("
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
            AND i.deleted = 0 AND w.deleted = 0
        ORDER BY (i.reorder_point - i.current_stock) DESC
        LIMIT ?
    ");
    
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    sendSuccess([
        'low_stock_items' => $items,
        'count' => count($items)
    ]);
}

/**
 * Reserve stock for quotes
 */
function reserveStock($db, $data) {
    if (!$data || !isset($data['product_id']) || !isset($data['warehouse_id']) || !isset($data['quantity'])) {
        sendError(400, "product_id, warehouse_id, and quantity required");
    }
    
    $product_id = $data['product_id'];
    $warehouse_id = $data['warehouse_id'];
    $quantity = $data['quantity'];
    $quote_id = $data['quote_id'] ?? null;
    $expiration_hours = $data['expiration_hours'] ?? 24;
    
    $db->begin_transaction();
    
    try {
        // Check available stock
        $check_stmt = $db->prepare("
            SELECT current_stock, reserved_stock, available_stock 
            FROM mfg_inventory 
            WHERE product_id = ? AND warehouse_id = ? AND deleted = 0
        ");
        $check_stmt->bind_param('ss', $product_id, $warehouse_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            sendError(404, "Product not found in warehouse");
        }
        
        $inventory = $result->fetch_assoc();
        if ($inventory['available_stock'] < $quantity) {
            sendError(400, "Insufficient stock. Available: {$inventory['available_stock']}, Requested: $quantity");
        }
        
        // Create reservation
        $reservation_id = generateUUID();
        $expiration_date = date('Y-m-d H:i:s', strtotime("+$expiration_hours hours"));
        
        $reserve_stmt = $db->prepare("
            INSERT INTO mfg_stock_reservations 
            (id, product_id, warehouse_id, quote_id, reserved_quantity, 
             reserved_by, expiration_date, created_by)
            VALUES (?, ?, ?, ?, ?, '1', ?, '1')
        ");
        
        $reserve_stmt->bind_param('ssssds',
            $reservation_id, $product_id, $warehouse_id, $quote_id,
            $quantity, $expiration_date
        );
        
        $reserve_stmt->execute();
        
        // Update inventory
        $update_stmt = $db->prepare("
            UPDATE mfg_inventory 
            SET reserved_stock = reserved_stock + ?
            WHERE product_id = ? AND warehouse_id = ?
        ");
        $update_stmt->bind_param('dss', $quantity, $product_id, $warehouse_id);
        $update_stmt->execute();
        
        $db->commit();
        
        sendSuccess([
            'reservation_id' => $reservation_id,
            'reserved_quantity' => $quantity,
            'expiration_date' => $expiration_date,
            'status' => 'active'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
}

/**
 * Get warehouses
 */
function getWarehouses($db) {
    $stmt = $db->prepare("
        SELECT id, name, code, city, state, contact_name, contact_phone
        FROM mfg_warehouses 
        WHERE deleted = 0 AND is_active = 1
        ORDER BY priority ASC, name ASC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $warehouses = [];
    while ($row = $result->fetch_assoc()) {
        $warehouses[] = $row;
    }
    
    sendSuccess([
        'warehouses' => $warehouses,
        'count' => count($warehouses)
    ]);
}

/**
 * Get inventory summary
 */
function getInventorySummary($db) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_items,
            SUM(current_stock) as total_stock,
            SUM(reserved_stock) as total_reserved,
            SUM(available_stock) as total_available,
            COUNT(CASE WHEN stock_status = 'low_stock' THEN 1 END) as low_stock_items,
            COUNT(CASE WHEN stock_status = 'out_of_stock' THEN 1 END) as out_of_stock_items,
            COUNT(CASE WHEN stock_status = 'in_stock' THEN 1 END) as in_stock_items
        FROM mfg_inventory 
        WHERE deleted = 0
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
    
    sendSuccess($summary);
}

/**
 * Utility functions
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function sendSuccess($data) {
    echo json_encode([
        'success' => true,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}

function sendError($code, $message) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    exit;
}
?>
