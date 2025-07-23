<?php
/**
 * Manufacturing Product Catalog API
 * RESTful endpoints for mobile product catalog with client-specific pricing
 */

require_once('include/MVC/Controller/SugarController.php');
require_once('include/utils.php');
require_once('modules/Accounts/Account.php');

class ProductCatalogAPI extends SugarController
{
    private $db;
    
    public function __construct()
    {
        global $db;
        $this->db = $db;
        
        // Set JSON headers
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * GET /api/v1/products
     * Get product catalog with optional filtering
     */
    public function action_products()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            
            switch ($method) {
                case 'GET':
                    return $this->getProducts();
                case 'POST':
                    return $this->createProduct();
                default:
                    return $this->errorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/products/client/{account_id}
     * Get products with client-specific pricing
     */
    public function action_client_products()
    {
        try {
            $account_id = $this->getPathParameter(3); // /api/v1/products/client/{account_id}
            
            if (!$account_id) {
                return $this->errorResponse('Account ID is required', 400);
            }
            
            return $this->getClientProducts($account_id);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/products/search
     * Advanced product search with filters
     */
    public function action_search()
    {
        try {
            return $this->searchProducts();
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/inventory/{product_id}
     * Get real-time inventory for a product
     */
    public function action_inventory()
    {
        try {
            $product_id = $this->getPathParameter(2); // /api/v1/inventory/{product_id}
            
            if (!$product_id) {
                return $this->errorResponse('Product ID is required', 400);
            }
            
            return $this->getInventory($product_id);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    private function getProducts()
    {
        $page = intval($_GET['page'] ?? 1);
        $limit = min(intval($_GET['limit'] ?? 50), 100); // Max 100 items per page
        $category = $_GET['category'] ?? '';
        $material = $_GET['material'] ?? '';
        $status = $_GET['status'] ?? 'active';
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["p.deleted = 0"];
        $params = [];
        
        if ($status) {
            $where_conditions[] = "p.status = ?";
            $params[] = $status;
        }
        
        if ($category) {
            $where_conditions[] = "p.category = ?";
            $params[] = $category;
        }
        
        if ($material) {
            $where_conditions[] = "p.material = ?";
            $params[] = $material;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_query = "SELECT COUNT(*) as total FROM mfg_products p WHERE {$where_clause}";
        $count_result = $this->db->pQuery($count_query, $params);
        $total = $this->db->fetchByAssoc($count_result)['total'];
        
        // Get products with inventory
        $query = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.description,
                p.category,
                p.material,
                p.weight_lbs,
                p.dimensions,
                p.base_price,
                p.list_price,
                p.minimum_order_qty,
                p.packaging_unit,
                p.manufacturer,
                p.lead_time_days,
                p.image_url,
                p.thumbnail_url,
                p.specifications,
                p.tags,
                i.quantity_available,
                CASE 
                    WHEN i.quantity_available <= 0 THEN 'out_of_stock'
                    WHEN i.quantity_available <= i.reorder_point THEN 'low_stock'
                    ELSE 'in_stock'
                END as stock_status,
                i.reorder_point
            FROM mfg_products p
            LEFT JOIN mfg_inventory i ON p.id = i.product_id AND i.deleted = 0
            WHERE {$where_clause}
            ORDER BY p.name ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $result = $this->db->pQuery($query, $params);
        $products = [];
        
        while ($row = $this->db->fetchByAssoc($result)) {
            $products[] = $this->formatProduct($row);
        }
        
        return $this->successResponse([
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => intval($total),
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    private function getClientProducts($account_id)
    {
        $page = intval($_GET['page'] ?? 1);
        $limit = min(intval($_GET['limit'] ?? 50), 100);
        $category = $_GET['category'] ?? '';
        $offset = ($page - 1) * $limit;
        
        $where_conditions = ["p.deleted = 0", "p.status = 'active'"];
        $params = [$account_id];
        
        if ($category) {
            $where_conditions[] = "p.category = ?";
            $params[] = $category;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.description,
                p.category,
                p.material,
                p.base_price,
                p.list_price,
                p.minimum_order_qty,
                p.image_url,
                p.thumbnail_url,
                p.specifications,
                i.quantity_available,
                CASE 
                    WHEN i.quantity_available <= 0 THEN 'out_of_stock'
                    WHEN i.quantity_available <= i.reorder_point THEN 'low_stock'
                    ELSE 'in_stock'
                END as stock_status,
                pt.tier_name,
                pt.discount_percentage,
                COALESCE(
                    pp.custom_price, 
                    ROUND(p.list_price * (1 - COALESCE(pt.discount_percentage, 0) / 100), 2)
                ) as client_price,
                cc.payment_terms,
                cc.contract_number
            FROM mfg_products p
            LEFT JOIN mfg_inventory i ON p.id = i.product_id AND i.deleted = 0
            LEFT JOIN mfg_client_contracts cc ON cc.account_id = ? 
                AND cc.deleted = 0 AND cc.status = 'active'
                AND (cc.end_date IS NULL OR cc.end_date >= CURDATE())
            LEFT JOIN mfg_pricing_tiers pt ON cc.pricing_tier_id = pt.id AND pt.deleted = 0
            LEFT JOIN mfg_product_pricing pp ON p.id = pp.product_id 
                AND cc.account_id = pp.account_id AND pp.deleted = 0 AND pp.is_active = 1
                AND (pp.expiry_date IS NULL OR pp.expiry_date >= CURDATE())
            WHERE {$where_clause}
            ORDER BY p.name ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $result = $this->db->pQuery($query, $params);
        $products = [];
        
        while ($row = $this->db->fetchByAssoc($result)) {
            $product = $this->formatProduct($row);
            $product['pricing'] = [
                'list_price' => floatval($row['list_price']),
                'client_price' => floatval($row['client_price']),
                'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                'tier_name' => $row['tier_name'],
                'payment_terms' => $row['payment_terms']
            ];
            $products[] = $product;
        }
        
        return $this->successResponse([
            'products' => $products,
            'account_id' => $account_id,
            'pagination' => [
                'page' => $page,
                'limit' => $limit
            ]
        ]);
    }
    
    private function searchProducts()
    {
        $query = $_GET['q'] ?? '';
        $category = $_GET['category'] ?? '';
        $material = $_GET['material'] ?? '';
        $price_min = floatval($_GET['price_min'] ?? 0);
        $price_max = floatval($_GET['price_max'] ?? 999999);
        $stock_status = $_GET['stock_status'] ?? '';
        $limit = min(intval($_GET['limit'] ?? 20), 50);
        
        if (strlen($query) < 2) {
            return $this->errorResponse('Search query must be at least 2 characters', 400);
        }
        
        $where_conditions = ["p.deleted = 0", "p.status = 'active'"];
        $params = [];
        
        // Full-text search
        $where_conditions[] = "MATCH(p.name, p.description, p.sku, p.tags) AGAINST(? IN BOOLEAN MODE)";
        $params[] = $query . '*';
        
        if ($category) {
            $where_conditions[] = "p.category = ?";
            $params[] = $category;
        }
        
        if ($material) {
            $where_conditions[] = "p.material = ?";
            $params[] = $material;
        }
        
        if ($price_min > 0) {
            $where_conditions[] = "p.list_price >= ?";
            $params[] = $price_min;
        }
        
        if ($price_max < 999999) {
            $where_conditions[] = "p.list_price <= ?";
            $params[] = $price_max;
        }
        
        $having_conditions = [];
        if ($stock_status) {
            $having_conditions[] = "stock_status = '{$stock_status}'";
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $having_clause = !empty($having_conditions) ? 'HAVING ' . implode(' AND ', $having_conditions) : '';
        
        $search_query = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.description,
                p.category,
                p.material,
                p.list_price,
                p.image_url,
                p.thumbnail_url,
                i.quantity_available,
                CASE 
                    WHEN i.quantity_available <= 0 THEN 'out_of_stock'
                    WHEN i.quantity_available <= i.reorder_point THEN 'low_stock'
                    ELSE 'in_stock'
                END as stock_status,
                MATCH(p.name, p.description, p.sku, p.tags) AGAINST(? IN BOOLEAN MODE) as relevance
            FROM mfg_products p
            LEFT JOIN mfg_inventory i ON p.id = i.product_id AND i.deleted = 0
            WHERE {$where_clause}
            {$having_clause}
            ORDER BY relevance DESC, p.name ASC
            LIMIT {$limit}
        ";
        
        $result = $this->db->pQuery($search_query, $params);
        $products = [];
        
        while ($row = $this->db->fetchByAssoc($result)) {
            $products[] = $this->formatProduct($row);
        }
        
        return $this->successResponse([
            'products' => $products,
            'search_query' => $query,
            'filters' => [
                'category' => $category,
                'material' => $material,
                'price_range' => [$price_min, $price_max],
                'stock_status' => $stock_status
            ]
        ]);
    }
    
    private function getInventory($product_id)
    {
        $query = "
            SELECT 
                p.name as product_name,
                p.sku,
                i.quantity_on_hand,
                i.quantity_reserved,
                i.quantity_available,
                i.reorder_point,
                i.max_stock_level,
                i.warehouse_location,
                i.last_sync_date,
                CASE 
                    WHEN i.quantity_available <= 0 THEN 'out_of_stock'
                    WHEN i.quantity_available <= i.reorder_point THEN 'low_stock'
                    ELSE 'in_stock'
                END as stock_status
            FROM mfg_products p
            LEFT JOIN mfg_inventory i ON p.id = i.product_id AND i.deleted = 0
            WHERE p.id = ? AND p.deleted = 0
        ";
        
        $result = $this->db->pQuery($query, [$product_id]);
        $inventory = $this->db->fetchByAssoc($result);
        
        if (!$inventory) {
            return $this->errorResponse('Product not found', 404);
        }
        
        return $this->successResponse([
            'product_id' => $product_id,
            'inventory' => [
                'product_name' => $inventory['product_name'],
                'sku' => $inventory['sku'],
                'quantity_on_hand' => intval($inventory['quantity_on_hand'] ?? 0),
                'quantity_reserved' => intval($inventory['quantity_reserved'] ?? 0),
                'quantity_available' => intval($inventory['quantity_available'] ?? 0),
                'reorder_point' => intval($inventory['reorder_point'] ?? 0),
                'stock_status' => $inventory['stock_status'],
                'warehouse_location' => $inventory['warehouse_location'],
                'last_updated' => $inventory['last_sync_date']
            ]
        ]);
    }
    
    private function formatProduct($row)
    {
        return [
            'id' => $row['id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'description' => $row['description'],
            'category' => $row['category'],
            'material' => $row['material'],
            'weight_lbs' => floatval($row['weight_lbs'] ?? 0),
            'dimensions' => $row['dimensions'],
            'base_price' => floatval($row['base_price']),
            'list_price' => floatval($row['list_price']),
            'minimum_order_qty' => intval($row['minimum_order_qty'] ?? 1),
            'packaging_unit' => $row['packaging_unit'],
            'manufacturer' => $row['manufacturer'],
            'lead_time_days' => intval($row['lead_time_days'] ?? 0),
            'images' => [
                'thumbnail' => $row['thumbnail_url'],
                'full_size' => $row['image_url']
            ],
            'specifications' => $row['specifications'] ? json_decode($row['specifications'], true) : null,
            'tags' => $row['tags'] ? explode(',', $row['tags']) : [],
            'inventory' => [
                'quantity_available' => intval($row['quantity_available'] ?? 0),
                'stock_status' => $row['stock_status'] ?? 'unknown'
            ]
        ];
    }
    
    private function getPathParameter($index)
    {
        $path = trim($_SERVER['REQUEST_URI'], '/');
        $segments = explode('/', $path);
        return $segments[$index] ?? null;
    }
    
    private function successResponse($data, $status_code = 200)
    {
        http_response_code($status_code);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    private function errorResponse($message, $status_code = 400)
    {
        http_response_code($status_code);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status_code
            ],
            'timestamp' => date('c')
        ]);
        exit;
    }
}
