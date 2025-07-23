<?php
/**
 * Manufacturing Product Catalog Module
 * SuiteCRM integration for manufacturing distributors
 */

require_once('modules/DynamicFields/templates/Fields/TemplateText.php');
require_once('include/SugarObjects/templates/basic/Basic.php');

class ProductCatalog extends Basic
{
    public $new_schema = true;
    public $module_dir = 'Manufacturing';
    public $object_name = 'ProductCatalog';
    public $table_name = 'mfg_products';
    public $tracker_visibility = false;
    public $importable = true;
    public $disable_row_level_security = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function bean_implements($interface)
    {
        switch($interface) {
            case 'ACL':
                return true;
        }
        return false;
    }

    /**
     * Get products for mobile catalog with client-specific pricing
     */
    public function getMobileProducts($account_id = null, $filters = array(), $page = 1, $limit = 20)
    {
        global $db;
        
        $offset = ($page - 1) * $limit;
        $where_conditions = ["p.deleted = 0", "p.status = 'active'"];
        $params = [];
        
        // Add filters
        if (!empty($filters['category'])) {
            $where_conditions[] = "p.category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['material'])) {
            $where_conditions[] = "p.material = ?";
            $params[] = $filters['material'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = "MATCH(p.name, p.description, p.sku, p.tags) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $filters['search'] . '*';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        if ($account_id) {
            // Get products with client-specific pricing
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
                    pt.tier_name,
                    pt.discount_percentage,
                    COALESCE(
                        pp.custom_price, 
                        ROUND(p.list_price * (1 - COALESCE(pt.discount_percentage, 0) / 100), 2)
                    ) as client_price,
                    cc.payment_terms
                FROM mfg_products p
                LEFT JOIN mfg_inventory i ON p.id = i.product_id AND i.deleted = 0
                LEFT JOIN mfg_client_contracts cc ON cc.account_id = ? 
                    AND cc.deleted = 0 AND cc.status = 'active'
                    AND (cc.end_date IS NULL OR cc.end_date >= CURDATE())
                LEFT JOIN mfg_pricing_tiers pt ON cc.pricing_tier_id = pt.id AND pt.deleted = 0
                LEFT JOIN mfg_product_pricing pp ON p.id = pp.product_id 
                    AND cc.account_id = pp.account_id AND pp.deleted = 0 AND pp.is_active = 1
                WHERE {$where_clause}
                ORDER BY p.name ASC
                LIMIT {$limit} OFFSET {$offset}
            ";
            
            array_unshift($params, $account_id);
        } else {
            // Get standard product catalog
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
                    END as stock_status
                FROM mfg_products p
                LEFT JOIN mfg_inventory i ON p.id = i.product_id AND i.deleted = 0
                WHERE {$where_clause}
                ORDER BY p.name ASC
                LIMIT {$limit} OFFSET {$offset}
            ";
        }
        
        $result = $db->pQuery($query, $params);
        $products = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $products[] = $this->formatProductForMobile($row, $account_id);
        }
        
        return $products;
    }
    
    /**
     * Format product data for mobile app consumption
     */
    private function formatProductForMobile($row, $account_id = null)
    {
        $product = [
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
        
        // Add client-specific pricing if available
        if ($account_id && isset($row['client_price'])) {
            $product['pricing'] = [
                'list_price' => floatval($row['list_price']),
                'client_price' => floatval($row['client_price']),
                'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                'tier_name' => $row['tier_name'],
                'payment_terms' => $row['payment_terms']
            ];
        }
        
        return $product;
    }
    
    /**
     * Get available categories for filtering
     */
    public function getCategories()
    {
        global $db;
        
        $query = "
            SELECT DISTINCT category 
            FROM mfg_products 
            WHERE deleted = 0 AND status = 'active' AND category IS NOT NULL AND category != ''
            ORDER BY category
        ";
        
        $result = $db->query($query);
        $categories = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    }
    
    /**
     * Get available materials for filtering
     */
    public function getMaterials()
    {
        global $db;
        
        $query = "
            SELECT DISTINCT material 
            FROM mfg_products 
            WHERE deleted = 0 AND status = 'active' AND material IS NOT NULL AND material != ''
            ORDER BY material
        ";
        
        $result = $db->query($query);
        $materials = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $materials[] = $row['material'];
        }
        
        return $materials;
    }
    
    /**
     * Update inventory levels (called by sync jobs)
     */
    public function updateInventory($product_id, $quantity_on_hand, $warehouse_location = 'main')
    {
        global $db;
        
        $query = "
            INSERT INTO mfg_inventory (
                id, product_id, warehouse_location, quantity_on_hand, 
                last_sync_date, sync_status, date_entered, date_modified
            ) VALUES (
                ?, ?, ?, ?, NOW(), 'synced', NOW(), NOW()
            ) ON DUPLICATE KEY UPDATE
                quantity_on_hand = VALUES(quantity_on_hand),
                last_sync_date = NOW(),
                sync_status = 'synced',
                date_modified = NOW()
        ";
        
        $inventory_id = create_guid();
        $params = [$inventory_id, $product_id, $warehouse_location, $quantity_on_hand];
        
        return $db->pQuery($query, $params);
    }
    
    /**
     * Reserve inventory for quotes
     */
    public function reserveInventory($product_id, $quantity, $warehouse_location = 'main')
    {
        global $db;
        
        // Check available quantity
        $check_query = "
            SELECT quantity_available 
            FROM mfg_inventory 
            WHERE product_id = ? AND warehouse_location = ? AND deleted = 0
        ";
        
        $result = $db->pQuery($check_query, [$product_id, $warehouse_location]);
        $inventory = $db->fetchByAssoc($result);
        
        if (!$inventory || $inventory['quantity_available'] < $quantity) {
            return false; // Insufficient inventory
        }
        
        // Reserve the quantity
        $update_query = "
            UPDATE mfg_inventory 
            SET quantity_reserved = quantity_reserved + ?,
                date_modified = NOW()
            WHERE product_id = ? AND warehouse_location = ? AND deleted = 0
        ";
        
        return $db->pQuery($update_query, [$quantity, $product_id, $warehouse_location]);
    }
    
    /**
     * Release reserved inventory
     */
    public function releaseInventory($product_id, $quantity, $warehouse_location = 'main')
    {
        global $db;
        
        $update_query = "
            UPDATE mfg_inventory 
            SET quantity_reserved = GREATEST(0, quantity_reserved - ?),
                date_modified = NOW()
            WHERE product_id = ? AND warehouse_location = ? AND deleted = 0
        ";
        
        return $db->pQuery($update_query, [$quantity, $product_id, $warehouse_location]);
    }
    
    /**
     * Get client pricing for a specific account
     */
    public function getClientPricing($account_id, $product_ids = [])
    {
        global $db;
        
        $product_filter = '';
        $params = [$account_id];
        
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $product_filter = "AND p.id IN ({$placeholders})";
            $params = array_merge($params, $product_ids);
        }
        
        $query = "
            SELECT 
                p.id as product_id,
                p.sku,
                p.name as product_name,
                p.list_price,
                pt.tier_name,
                pt.discount_percentage,
                COALESCE(
                    pp.custom_price, 
                    ROUND(p.list_price * (1 - COALESCE(pt.discount_percentage, 0) / 100), 2)
                ) as client_price,
                cc.payment_terms,
                cc.contract_number
            FROM mfg_products p
            LEFT JOIN mfg_client_contracts cc ON cc.account_id = ? 
                AND cc.deleted = 0 AND cc.status = 'active'
                AND (cc.end_date IS NULL OR cc.end_date >= CURDATE())
            LEFT JOIN mfg_pricing_tiers pt ON cc.pricing_tier_id = pt.id AND pt.deleted = 0
            LEFT JOIN mfg_product_pricing pp ON p.id = pp.product_id 
                AND cc.account_id = pp.account_id AND pp.deleted = 0 AND pp.is_active = 1
            WHERE p.deleted = 0 AND p.status = 'active' {$product_filter}
            ORDER BY p.name ASC
        ";
        
        $result = $db->pQuery($query, $params);
        $pricing = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $pricing[$row['product_id']] = [
                'list_price' => floatval($row['list_price']),
                'client_price' => floatval($row['client_price']),
                'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                'tier_name' => $row['tier_name'],
                'payment_terms' => $row['payment_terms'],
                'contract_number' => $row['contract_number']
            ];
        }
        
        return $pricing;
    }
}
