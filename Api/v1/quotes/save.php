<?php
/**
 * Quote Save API
 * Saves quote drafts and manages versioning
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

define('sugarEntry', true);
require_once('../../../config.php');
require_once('../../../include/database/DBManagerFactory.php');

// Initialize database connection
$db = DBManagerFactory::getInstance();

// Handle GET requests (load quote)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $quote_id = $_GET['quote_id'] ?? null;
    
    if (!$quote_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Quote ID required']);
        exit;
    }
    
    try {
        // Get quote data
        $quote_id_escaped = $db->quote($quote_id);
        $query = "SELECT * FROM mfg_quotes WHERE id = '$quote_id_escaped' AND deleted = 0";
    $result = $db->query($query);
        
        if ($db->num_rows($result) === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Quote not found']);
            exit;
        }
        
        $quote = $db->fetchByAssoc($result);
        
        // Get quote items
        $itemsQuery = "SELECT * FROM mfg_quote_items WHERE quote_id = '$quote_id_escaped' ORDER BY line_number";
        $itemsResult = $db->query($itemsQuery);
        
        $items = [];
        while ($row = $db->fetchByAssoc($itemsResult)) {
            $items[] = [
                'id' => $row['product_id'],
                'name' => $row['product_name'],
                'sku' => $row['product_sku'],
                'price' => floatval($row['unit_price']),
                'quantity' => intval($row['quantity']),
                'discount' => floatval($row['discount_percent']),
                'image' => $row['product_image'] ?? '📦'
            ];
        }
        
        $quote['items'] = $items;
        
        echo json_encode(['success' => true, 'quote' => $quote]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit;
}

// Handle POST requests (save quote)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get quote data
$input = file_get_contents('php://input');
$quoteData = json_decode($input, true);

if (!$quoteData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid quote data']);
    exit;
}

// Validate required fields
$required = ['quote_number', 'client_name', 'valid_until', 'items'];
foreach ($required as $field) {
    if (!isset($quoteData[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Create quotes table if it doesn't exist
    createQuoteTables($db);
    
    // Generate quote ID if not provided
    $quote_id = $quoteData['quote_id'] ?? generateUUID();
    
    // Calculate totals
    $totals = calculateQuoteTotals($quoteData['items']);
    
    // Check if quote exists (for updates)
    $existingQuote = null;
    $checkQuery = "SELECT id FROM mfg_quotes WHERE quote_number = ? AND deleted = 0";
    $checkResult = $db->pquery($checkQuery, [$quoteData['quote_number']]);
    
    if ($db->num_rows($checkResult) > 0) {
        $existingQuote = $db->fetchByAssoc($checkResult);
        $quote_id = $existingQuote['id'];
    }
    
    // Start transaction
    $db->startTransaction();
    
    if ($existingQuote) {
        // Update existing quote
        $updateQuery = "UPDATE mfg_quotes SET 
            client_name = ?, 
            valid_until = ?, 
            client_tier = ?,
            subtotal = ?,
            discount_total = ?,
            tax_total = ?,
            shipping_total = ?,
            grand_total = ?,
            status = 'draft',
            date_modified = NOW()
            WHERE id = ?";
        
        $db->pquery($updateQuery, [
            $quoteData['client_name'],
            $quoteData['valid_until'],
            $quoteData['client_tier'] ?? 'retail',
            $totals['subtotal'],
            $totals['discount'],
            $totals['tax'],
            $totals['shipping'],
            $totals['total'],
            $quote_id
        ]);
        
        // Delete existing items
        $db->pquery("DELETE FROM mfg_quote_items WHERE quote_id = ?", [$quote_id]);
        
    } else {
        // Create new quote
        $insertQuery = "INSERT INTO mfg_quotes (
            id, quote_number, client_name, valid_until, client_tier,
            subtotal, discount_total, tax_total, shipping_total, grand_total,
            status, created_by, date_entered, date_modified, deleted
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', 'system', NOW(), NOW(), 0)";
        
        $db->pquery($insertQuery, [
            $quote_id,
            $quoteData['quote_number'],
            $quoteData['client_name'],
            $quoteData['valid_until'],
            $quoteData['client_tier'] ?? 'retail',
            $totals['subtotal'],
            $totals['discount'],
            $totals['tax'],
            $totals['shipping'],
            $totals['total']
        ]);
    }
    
    // Insert quote items
    $lineNumber = 1;
    foreach ($quoteData['items'] as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $discountAmount = $itemTotal * ($item['discount'] ?? 0) / 100;
        $finalTotal = $itemTotal - $discountAmount;
        
        $itemQuery = "INSERT INTO mfg_quote_items (
            id, quote_id, product_id, product_name, product_sku, product_image,
            quantity, unit_price, discount_percent, discount_amount, line_total,
            line_number, date_entered, deleted
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)";
        
        $db->pquery($itemQuery, [
            generateUUID(),
            $quote_id,
            $item['id'],
            $item['name'],
            $item['sku'],
            $item['image'] ?? '📦',
            $item['quantity'],
            $item['price'],
            $item['discount'] ?? 0,
            $discountAmount,
            $finalTotal,
            $lineNumber++
        ]);
    }
    
    // Commit transaction
    $db->commitTransaction();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Quote saved successfully',
        'quote_id' => $quote_id,
        'quote_number' => $quoteData['quote_number']
    ]);
    
} catch (Exception $e) {
    $db->rollbackTransaction();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Create quote tables if they don't exist
 */
function createQuoteTables($db) {
    // Create quotes table
    $quotesTable = "CREATE TABLE IF NOT EXISTS mfg_quotes (
        id VARCHAR(36) PRIMARY KEY,
        quote_number VARCHAR(50) UNIQUE NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        client_id VARCHAR(36),
        valid_until DATE NOT NULL,
        client_tier VARCHAR(50) DEFAULT 'retail',
        subtotal DECIMAL(15,2) DEFAULT 0.00,
        discount_total DECIMAL(15,2) DEFAULT 0.00,
        tax_total DECIMAL(15,2) DEFAULT 0.00,
        shipping_total DECIMAL(15,2) DEFAULT 0.00,
        grand_total DECIMAL(15,2) DEFAULT 0.00,
        status ENUM('draft', 'sent', 'viewed', 'approved', 'rejected', 'expired') DEFAULT 'draft',
        notes TEXT,
        terms_conditions TEXT,
        created_by VARCHAR(36),
        date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
        date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted TINYINT(1) DEFAULT 0,
        
        INDEX idx_quote_number (quote_number),
        INDEX idx_client_name (client_name),
        INDEX idx_status (status),
        INDEX idx_valid_until (valid_until),
        INDEX idx_deleted (deleted)
    )";
    
    $db->query($quotesTable);
    
    // Create quote items table
    $itemsTable = "CREATE TABLE IF NOT EXISTS mfg_quote_items (
        id VARCHAR(36) PRIMARY KEY,
        quote_id VARCHAR(36) NOT NULL,
        product_id VARCHAR(36) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_sku VARCHAR(100) NOT NULL,
        product_image VARCHAR(10),
        quantity INT NOT NULL DEFAULT 1,
        unit_price DECIMAL(15,2) NOT NULL,
        discount_percent DECIMAL(5,2) DEFAULT 0.00,
        discount_amount DECIMAL(15,2) DEFAULT 0.00,
        line_total DECIMAL(15,2) NOT NULL,
        line_number INT NOT NULL,
        date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
        deleted TINYINT(1) DEFAULT 0,
        
        FOREIGN KEY (quote_id) REFERENCES mfg_quotes(id) ON DELETE CASCADE,
        INDEX idx_quote_id (quote_id),
        INDEX idx_product_id (product_id),
        INDEX idx_line_number (line_number),
        INDEX idx_deleted (deleted)
    )";
    
    $db->query($itemsTable);
    
    // Create quote versions table for tracking changes
    $versionsTable = "CREATE TABLE IF NOT EXISTS mfg_quote_versions (
        id VARCHAR(36) PRIMARY KEY,
        quote_id VARCHAR(36) NOT NULL,
        version_number INT NOT NULL,
        quote_data JSON,
        created_by VARCHAR(36),
        date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (quote_id) REFERENCES mfg_quotes(id) ON DELETE CASCADE,
        INDEX idx_quote_id (quote_id),
        INDEX idx_version (version_number)
    )";
    
    $db->query($versionsTable);
}

/**
 * Calculate quote totals
 */
function calculateQuoteTotals($items) {
    $subtotal = 0;
    $discountTotal = 0;
    
    foreach ($items as $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $itemDiscount = $itemTotal * ($item['discount'] ?? 0) / 100;
        $subtotal += $itemTotal - $itemDiscount;
        $discountTotal += $itemDiscount;
    }
    
    $taxRate = 0.085; // 8.5% tax
    $tax = $subtotal * $taxRate;
    $shipping = $subtotal > 500 ? 0 : 75;
    $total = $subtotal + $tax + $shipping;
    
    return [
        'subtotal' => $subtotal,
        'discount' => $discountTotal,
        'tax' => $tax,
        'shipping' => $shipping,
        'total' => $total
    ];
}

/**
 * Generate UUID
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
?>
