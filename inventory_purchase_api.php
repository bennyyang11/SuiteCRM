<?php
/**
 * Inventory Purchase API - Docker Version
 * Manufacturing Distribution Platform - Dynamic Purchase System
 * 
 * Handles product browsing, cart management, and purchase transactions
 * Configured for Docker container environment
 */

require_once('config.php');

// Enable CORS and set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection - Docker environment
global $sugar_config;

// Use Docker service name for database connection
$host = 'suitecrm-mysql';
$port = 3306;
$db = new mysqli(
    $host,
    $sugar_config['dbconfig']['db_user_name'],
    $sugar_config['dbconfig']['db_password'],
    $sugar_config['dbconfig']['db_name'],
    $port
);

if ($db->connect_error) {
    sendError(500, "Database connection failed: " . $db->connect_error);
}

// Main API router
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'get_products':
            getProductsWithInventory($db);
            break;
        case 'process_purchase':
            processPurchaseTransaction($db, json_decode(file_get_contents('php://input'), true));
            break;
        case 'get_updates':
            getInventoryUpdates($db, $_GET['since'] ?? 0);
            break;
        case 'validate_cart':
            validateCartItems($db, json_decode(file_get_contents('php://input'), true));
            break;
        case 'get_purchase_history':
            getPurchaseHistory($db, $_GET['limit'] ?? 20);
            break;
        default:
            sendError(404, "Action not found. Available: get_products, process_purchase, get_updates, validate_cart, get_purchase_history");
    }
} catch (Exception $e) {
    sendError(500, "Internal error: " . $e->getMessage());
}

/**
 * Get products with inventory information across all warehouses
 */
function getProductsWithInventory($db) {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $stock_filter = $_GET['stock_filter'] ?? '';
    $limit = intval($_GET['limit'] ?? 50);
    $offset = intval($_GET['offset'] ?? 0);
    
    $whereConditions = ["p.deleted = 0"];
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $whereConditions[] = "(p.name LIKE ? OR p.sku LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    if (!empty($category)) {
        $whereConditions[] = "p.category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $whereClause = implode(" AND ", $whereConditions);
    
    // Main query to get products with aggregated inventory data
    $sql = "
        SELECT 
            p.id,
            p.name,
            p.sku,
            p.category,
            p.description,
            p.unit_price,
            p.status,
            COALESCE(inv_summary.total_stock, 0) as total_stock,
            COALESCE(inv_summary.total_available, 0) as total_available,
            COALESCE(inv_summary.warehouse_count, 0) as warehouse_count,
            CASE 
                WHEN COALESCE(inv_summary.total_available, 0) <= 0 THEN 'out_of_stock'
                WHEN COALESCE(inv_summary.total_available, 0) <= 20 THEN 'low_stock'
                ELSE 'in_stock'
            END as stock_status
        FROM mfg_products p
        LEFT JOIN (
            SELECT 
                i.product_id,
                SUM(i.current_stock) as total_stock,
                SUM(GREATEST(i.current_stock - COALESCE(i.reserved_stock, 0), 0)) as total_available,
                COUNT(DISTINCT i.warehouse_id) as warehouse_count
            FROM mfg_inventory i
            JOIN mfg_warehouses w ON i.warehouse_id = w.id
            WHERE i.deleted = 0 AND w.deleted = 0 AND w.is_active = 1
            GROUP BY i.product_id
        ) inv_summary ON p.id = inv_summary.product_id
        WHERE $whereClause
        ORDER BY p.name ASC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Apply stock filter after query if needed
        if (!empty($stock_filter) && $row['stock_status'] !== $stock_filter) {
            continue;
        }
        
        $products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'category' => $row['category'],
            'description' => $row['description'],
            'unit_price' => floatval($row['unit_price']),
            'status' => $row['status'],
            'total_stock' => intval($row['total_stock']),
            'total_available' => intval($row['total_available']),
            'warehouse_count' => intval($row['warehouse_count']),
            'stock_status' => $row['stock_status']
        ];
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM mfg_products p WHERE $whereClause";
    $countStmt = $db->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
    
    sendSuccess([
        'products' => $products,
        'pagination' => [
            'total' => intval($totalCount),
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ]
    ]);
}

/**
 * Process purchase transaction
 */
function processPurchaseTransaction($db, $data) {
    if (!$data || !isset($data['items']) || empty($data['items'])) {
        sendError(400, "Purchase items required");
    }
    
    $items = $data['items'];
    $customer_name = $data['customer_name'] ?? 'Walk-in Customer';
    $payment_method = $data['payment_method'] ?? 'cash';
    
    $db->begin_transaction();
    
    try {
        // Generate transaction ID
        $transaction_id = generateUUID();
        $total_amount = 0;
        $processed_items = [];
        
        // Validate and process each item
        foreach ($items as $item) {
            $product_id = $item['id'];
            $quantity = intval($item['quantity']);
            $unit_price = floatval($item['price']);
            
            if ($quantity <= 0) {
                throw new Exception("Invalid quantity for product {$item['name']}");
            }
            
            // Find best warehouse with available stock
            $warehouse_result = $db->prepare("
                SELECT 
                    i.warehouse_id,
                    w.name as warehouse_name,
                    i.current_stock,
                    COALESCE(i.reserved_stock, 0) as reserved_stock,
                    GREATEST(i.current_stock - COALESCE(i.reserved_stock, 0), 0) as available_stock
                FROM mfg_inventory i
                JOIN mfg_warehouses w ON i.warehouse_id = w.id
                WHERE i.product_id = ? AND i.deleted = 0 AND w.deleted = 0 AND w.is_active = 1
                    AND GREATEST(i.current_stock - COALESCE(i.reserved_stock, 0), 0) >= ?
                ORDER BY GREATEST(i.current_stock - COALESCE(i.reserved_stock, 0), 0) DESC, w.priority ASC
                LIMIT 1
            ");
            
            $warehouse_result->bind_param('si', $product_id, $quantity);
            $warehouse_result->execute();
            $warehouse_data = $warehouse_result->get_result()->fetch_assoc();
            
            if (!$warehouse_data) {
                throw new Exception("Insufficient stock for {$item['name']}. Requested: $quantity");
            }
            
            $warehouse_id = $warehouse_data['warehouse_id'];
            $item_total = $quantity * $unit_price;
            $total_amount += $item_total;
            
            // Create purchase transaction record
            $transaction_item_id = generateUUID();
            $insert_transaction = $db->prepare("
                INSERT INTO mfg_purchase_transactions 
                (id, transaction_id, product_id, warehouse_id, quantity_purchased, 
                 unit_price, line_total, customer_name, transaction_date, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'completed', '1')
            ");
            
            $insert_transaction->bind_param('ssssddds', 
                $transaction_item_id, $transaction_id, $product_id, $warehouse_id,
                $quantity, $unit_price, $item_total, $customer_name
            );
            
            $insert_transaction->execute();
            
            // Update inventory - reduce stock
            $update_inventory = $db->prepare("
                UPDATE mfg_inventory 
                SET current_stock = current_stock - ?,
                    last_updated = NOW()
                WHERE product_id = ? AND warehouse_id = ?
            ");
            
            $update_inventory->bind_param('iss', $quantity, $product_id, $warehouse_id);
            $update_inventory->execute();
            
            // Create stock movement record
            $movement_id = generateUUID();
            $insert_movement = $db->prepare("
                INSERT INTO mfg_stock_movements 
                (id, product_id, warehouse_id, movement_type, quantity, unit_cost,
                 reference_type, reference_id, notes, performed_by, date_entered)
                VALUES (?, ?, ?, 'outbound', ?, ?, 'purchase', ?, 'Customer purchase', '1', NOW())
            ");
            
            $insert_movement->bind_param('sssdds', 
                $movement_id, $product_id, $warehouse_id, $quantity, 
                $unit_price, $transaction_id
            );
            
            $insert_movement->execute();
            
            // Update stock status if needed
            $check_stock = $db->prepare("
                SELECT current_stock, reorder_point
                FROM mfg_inventory 
                WHERE product_id = ? AND warehouse_id = ?
            ");
            $check_stock->bind_param('ss', $product_id, $warehouse_id);
            $check_stock->execute();
            $stock_info = $check_stock->get_result()->fetch_assoc();
            
            if ($stock_info) {
                $new_status = 'in_stock';
                if ($stock_info['current_stock'] <= 0) {
                    $new_status = 'out_of_stock';
                } elseif ($stock_info['current_stock'] <= $stock_info['reorder_point']) {
                    $new_status = 'low_stock';
                }
                
                $update_status = $db->prepare("
                    UPDATE mfg_inventory 
                    SET stock_status = ?
                    WHERE product_id = ? AND warehouse_id = ?
                ");
                $update_status->bind_param('sss', $new_status, $product_id, $warehouse_id);
                $update_status->execute();
            }
            
            $processed_items[] = [
                'product_id' => $product_id,
                'name' => $item['name'],
                'sku' => $item['sku'],
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'line_total' => $item_total,
                'warehouse' => $warehouse_data['warehouse_name']
            ];
        }
        
        // Create main transaction summary
        $summary_id = generateUUID();
        $insert_summary = $db->prepare("
            INSERT INTO mfg_purchase_summary 
            (id, transaction_id, customer_name, total_amount, item_count, 
             payment_method, transaction_date, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 'completed', '1')
        ");
        
        $item_count = count($processed_items);
        $insert_summary->bind_param('sssdis', 
            $summary_id, $transaction_id, $customer_name, $total_amount, 
            $item_count, $payment_method
        );
        
        $insert_summary->execute();
        
        $db->commit();
        
        sendSuccess([
            'transaction_id' => $transaction_id,
            'total_amount' => $total_amount,
            'items_processed' => $processed_items,
            'customer_name' => $customer_name,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'completed'
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        sendError(400, $e->getMessage());
    }
}

/**
 * Get recent inventory updates for real-time sync
 */
function getInventoryUpdates($db, $since_timestamp) {
    $since_date = date('Y-m-d H:i:s', intval($since_timestamp) / 1000);
    
    $stmt = $db->prepare("
        SELECT 
            i.product_id,
            p.name as product_name,
            p.sku,
            SUM(i.current_stock) as new_stock,
            MAX(i.last_updated) as last_updated
        FROM mfg_inventory i
        JOIN mfg_products p ON i.product_id = p.id
        WHERE i.last_updated > ? AND i.deleted = 0
        GROUP BY i.product_id, p.name, p.sku
        ORDER BY MAX(i.last_updated) DESC
        LIMIT 20
    ");
    
    $stmt->bind_param('s', $since_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $updates = [];
    while ($row = $result->fetch_assoc()) {
        $updates[] = [
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'sku' => $row['sku'],
            'new_stock' => intval($row['new_stock']),
            'last_updated' => $row['last_updated']
        ];
    }
    
    sendSuccess([
        'updates' => $updates,
        'count' => count($updates),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Get purchase history
 */
function getPurchaseHistory($db, $limit = 20) {
    $stmt = $db->prepare("
        SELECT 
            ps.transaction_id,
            ps.customer_name,
            ps.total_amount,
            ps.item_count,
            ps.payment_method,
            ps.transaction_date
        FROM mfg_purchase_summary ps
        WHERE ps.status = 'completed'
        ORDER BY ps.transaction_date DESC
        LIMIT ?
    ");
    
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = [
            'transaction_id' => $row['transaction_id'],
            'customer_name' => $row['customer_name'],
            'total_amount' => floatval($row['total_amount']),
            'item_count' => intval($row['item_count']),
            'payment_method' => $row['payment_method'],
            'transaction_date' => $row['transaction_date']
        ];
    }
    
    sendSuccess([
        'purchases' => $history,
        'count' => count($history)
    ]);
}

/**
 * Validate cart items against current inventory
 */
function validateCartItems($db, $data) {
    if (!$data || !isset($data['items'])) {
        sendError(400, "Cart items required");
    }
    
    $items = $data['items'];
    $validation_results = [];
    $all_valid = true;
    
    foreach ($items as $item) {
        $product_id = $item['id'];
        $requested_qty = intval($item['quantity']);
        
        // Check current availability
        $check_stmt = $db->prepare("
            SELECT 
                p.name,
                p.sku,
                SUM(GREATEST(i.current_stock - COALESCE(i.reserved_stock, 0), 0)) as available_stock
            FROM mfg_products p
            LEFT JOIN mfg_inventory i ON p.id = i.product_id
            LEFT JOIN mfg_warehouses w ON i.warehouse_id = w.id
            WHERE p.id = ? AND p.deleted = 0 
                AND (i.deleted = 0 OR i.deleted IS NULL)
                AND (w.deleted = 0 OR w.deleted IS NULL)
                AND (w.is_active = 1 OR w.is_active IS NULL)
            GROUP BY p.id, p.name, p.sku
        ");
        
        $check_stmt->bind_param('s', $product_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            $validation_results[] = [
                'product_id' => $product_id,
                'valid' => false,
                'error' => 'Product not found',
                'available_stock' => 0
            ];
            $all_valid = false;
            continue;
        }
        
        $available = intval($result['available_stock']);
        $is_valid = $available >= $requested_qty;
        
        if (!$is_valid) {
            $all_valid = false;
        }
        
        $validation_results[] = [
            'product_id' => $product_id,
            'name' => $result['name'],
            'sku' => $result['sku'],
            'requested_quantity' => $requested_qty,
            'available_stock' => $available,
            'valid' => $is_valid,
            'error' => $is_valid ? null : "Only $available units available"
        ];
    }
    
    sendSuccess([
        'all_valid' => $all_valid,
        'items' => $validation_results
    ]);
}

/**
 * Utility Functions
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
