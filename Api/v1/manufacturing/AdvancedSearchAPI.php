<?php
/**
 * Advanced Search API for Manufacturing Products
 * Google-like search experience with faceted filtering and intelligent suggestions
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('include/MVC/Controller/SugarController.php');
require_once('include/utils.php');

class AdvancedSearchAPI extends SugarController
{
    private $db;
    private $cache_enabled = true;
    private $cache_prefix = 'mfg_search_';
    
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
     * GET /api/v1/search/instant
     * Google-like instant search with autocomplete
     */
    public function action_instant()
    {
        try {
            $query = $_GET['q'] ?? '';
            $limit = min(intval($_GET['limit'] ?? 10), 20);
            
            if (strlen($query) < 2) {
                return $this->successResponse([
                    'suggestions' => [],
                    'products' => [],
                    'facets' => []
                ]);
            }
            
            // Check cache first
            $cache_key = $this->cache_prefix . 'instant_' . md5($query . $limit);
            $cached = $this->getCache($cache_key);
            if ($cached) {
                return $this->successResponse($cached);
            }
            
            $results = [
                'suggestions' => $this->getSearchSuggestions($query, 5),
                'products' => $this->getInstantSearchResults($query, $limit),
                'facets' => $this->getSearchFacets($query)
            ];
            
            // Cache results for 5 minutes
            $this->setCache($cache_key, $results, 300);
            
            return $this->successResponse($results);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/search/advanced
     * Advanced search with comprehensive filtering
     */
    public function action_advanced()
    {
        try {
            $filters = $this->parseAdvancedFilters();
            $page = intval($_GET['page'] ?? 1);
            $limit = min(intval($_GET['limit'] ?? 20), 50);
            $sort = $_GET['sort'] ?? 'relevance';
            $order = $_GET['order'] ?? 'desc';
            
            $results = $this->performAdvancedSearch($filters, $page, $limit, $sort, $order);
            
            return $this->successResponse($results);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/search/autocomplete
     * Intelligent autocomplete suggestions
     */
    public function action_autocomplete()
    {
        try {
            $query = $_GET['q'] ?? '';
            $type = $_GET['type'] ?? 'all'; // all, product, sku, category, material
            $limit = min(intval($_GET['limit'] ?? 10), 20);
            
            if (strlen($query) < 2) {
                return $this->successResponse(['suggestions' => []]);
            }
            
            $suggestions = $this->getAutocompleteSuggestions($query, $type, $limit);
            
            return $this->successResponse(['suggestions' => $suggestions]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/search/facets
     * Get available filter facets for search refinement
     */
    public function action_facets()
    {
        try {
            $query = $_GET['q'] ?? '';
            $facets = $this->getAllFacets($query);
            
            return $this->successResponse(['facets' => $facets]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * POST /api/v1/search/saved
     * Save a search query for user
     */
    public function action_saved()
    {
        try {
            $method = $_SERVER['REQUEST_METHOD'];
            
            switch ($method) {
                case 'GET':
                    return $this->getSavedSearches();
                case 'POST':
                    return $this->saveSearch();
                case 'DELETE':
                    return $this->deleteSavedSearch();
                default:
                    return $this->errorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/v1/search/history
     * Get recent search history for user
     */
    public function action_history()
    {
        try {
            $user_id = $this->getCurrentUserId();
            $limit = min(intval($_GET['limit'] ?? 10), 20);
            
            $history = $this->getSearchHistory($user_id, $limit);
            
            return $this->successResponse(['history' => $history]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    
    private function getSearchSuggestions($query, $limit)
    {
        // Get suggestions from product names, SKUs, and popular searches
        $suggestions_query = "
            (SELECT DISTINCT name as suggestion, 'product' as type, COUNT(*) as weight
             FROM mfg_products 
             WHERE deleted = 0 AND status = 'active' 
             AND name LIKE ? 
             GROUP BY name
             LIMIT ?)
            UNION
            (SELECT DISTINCT sku as suggestion, 'sku' as type, COUNT(*) as weight
             FROM mfg_products 
             WHERE deleted = 0 AND status = 'active' 
             AND sku LIKE ? 
             GROUP BY sku
             LIMIT ?)
            UNION  
            (SELECT DISTINCT category as suggestion, 'category' as type, COUNT(*) as weight
             FROM mfg_products 
             WHERE deleted = 0 AND status = 'active' 
             AND category LIKE ? 
             GROUP BY category
             LIMIT ?)
            ORDER BY weight DESC, suggestion ASC
            LIMIT ?
        ";
        
        $like_query = '%' . $query . '%';
        $result = $this->db->pQuery($suggestions_query, [
            $like_query, $limit,
            $like_query, $limit,
            $like_query, $limit,
            $limit
        ]);
        
        $suggestions = [];
        while ($row = $this->db->fetchByAssoc($result)) {
            $suggestions[] = [
                'text' => $row['suggestion'],
                'type' => $row['type'],
                'weight' => intval($row['weight'])
            ];
        }
        
        return $suggestions;
    }
    
    private function getInstantSearchResults($query, $limit)
    {
        $search_query = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.description,
                p.category,
                p.material,
                p.list_price,
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
            WHERE p.deleted = 0 
            AND p.status = 'active'
            AND MATCH(p.name, p.description, p.sku, p.tags) AGAINST(? IN BOOLEAN MODE)
            ORDER BY relevance DESC, p.name ASC
            LIMIT ?
        ";
        
        $search_term = $query . '*';
        $result = $this->db->pQuery($search_query, [$search_term, $search_term, $limit]);
        
        $products = [];
        while ($row = $this->db->fetchByAssoc($result)) {
            $products[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'sku' => $row['sku'],
                'description' => substr($row['description'], 0, 150) . '...',
                'category' => $row['category'],
                'material' => $row['material'],
                'price' => floatval($row['list_price']),
                'thumbnail' => $row['thumbnail_url'],
                'stock_status' => $row['stock_status'],
                'relevance' => floatval($row['relevance'])
            ];
        }
        
        return $products;
    }
    
    private function getSearchFacets($query)
    {
        $facets = [];
        
        // Category facets
        $category_query = "
            SELECT p.category, COUNT(*) as count
            FROM mfg_products p
            WHERE p.deleted = 0 AND p.status = 'active'
            AND MATCH(p.name, p.description, p.sku, p.tags) AGAINST(? IN BOOLEAN MODE)
            GROUP BY p.category
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $search_term = $query . '*';
        $result = $this->db->pQuery($category_query, [$search_term]);
        
        $categories = [];
        while ($row = $this->db->fetchByAssoc($result)) {
            $categories[] = [
                'value' => $row['category'],
                'count' => intval($row['count']),
                'label' => ucwords(str_replace('_', ' ', $row['category']))
            ];
        }
        $facets['categories'] = $categories;
        
        // Material facets
        $material_query = "
            SELECT p.material, COUNT(*) as count
            FROM mfg_products p
            WHERE p.deleted = 0 AND p.status = 'active'
            AND MATCH(p.name, p.description, p.sku, p.tags) AGAINST(? IN BOOLEAN MODE)
            GROUP BY p.material
            ORDER BY count DESC
            LIMIT 10
        ";
        
        $result = $this->db->pQuery($material_query, [$search_term]);
        
        $materials = [];
        while ($row = $this->db->fetchByAssoc($result)) {
            $materials[] = [
                'value' => $row['material'],
                'count' => intval($row['count']),
                'label' => ucwords(str_replace('_', ' ', $row['material']))
            ];
        }
        $facets['materials'] = $materials;
        
        // Price ranges
        $facets['price_ranges'] = $this->getPriceRangeFacets($query);
        
        // Stock status
        $facets['stock_status'] = $this->getStockStatusFacets($query);
        
        return $facets;
    }
    
    private function parseAdvancedFilters()
    {
        return [
            'query' => $_GET['q'] ?? '',
            'category' => $_GET['category'] ?? '',
            'material' => $_GET['material'] ?? '',
            'price_min' => floatval($_GET['price_min'] ?? 0),
            'price_max' => floatval($_GET['price_max'] ?? 999999),
            'stock_status' => $_GET['stock_status'] ?? '',
            'manufacturer' => $_GET['manufacturer'] ?? '',
            'weight_min' => floatval($_GET['weight_min'] ?? 0),
            'weight_max' => floatval($_GET['weight_max'] ?? 999999),
            'lead_time_max' => intval($_GET['lead_time_max'] ?? 365),
            'specifications' => $_GET['specifications'] ?? [],
            'tags' => $_GET['tags'] ?? [],
            'client_id' => $_GET['client_id'] ?? ''
        ];
    }
    
    private function performAdvancedSearch($filters, $page, $limit, $sort, $order)
    {
        $offset = ($page - 1) * $limit;
        $where_conditions = ["p.deleted = 0", "p.status = 'active'"];
        $params = [];
        $joins = ["LEFT JOIN mfg_inventory i ON p.id = i.product_id AND i.deleted = 0"];
        
        // Text search
        if (!empty($filters['query'])) {
            $where_conditions[] = "MATCH(p.name, p.description, p.sku, p.tags) AGAINST(? IN BOOLEAN MODE)";
            $params[] = $filters['query'] . '*';
        }
        
        // Category filter
        if (!empty($filters['category'])) {
            $where_conditions[] = "p.category = ?";
            $params[] = $filters['category'];
        }
        
        // Material filter
        if (!empty($filters['material'])) {
            $where_conditions[] = "p.material = ?";
            $params[] = $filters['material'];
        }
        
        // Price range
        if ($filters['price_min'] > 0) {
            $where_conditions[] = "p.list_price >= ?";
            $params[] = $filters['price_min'];
        }
        if ($filters['price_max'] < 999999) {
            $where_conditions[] = "p.list_price <= ?";
            $params[] = $filters['price_max'];
        }
        
        // Weight range
        if ($filters['weight_min'] > 0) {
            $where_conditions[] = "p.weight_lbs >= ?";
            $params[] = $filters['weight_min'];
        }
        if ($filters['weight_max'] < 999999) {
            $where_conditions[] = "p.weight_lbs <= ?";
            $params[] = $filters['weight_max'];
        }
        
        // Lead time
        if ($filters['lead_time_max'] < 365) {
            $where_conditions[] = "p.lead_time_days <= ?";
            $params[] = $filters['lead_time_max'];
        }
        
        // Manufacturer
        if (!empty($filters['manufacturer'])) {
            $where_conditions[] = "p.manufacturer = ?";
            $params[] = $filters['manufacturer'];
        }
        
        // Client-specific pricing if client_id provided
        if (!empty($filters['client_id'])) {
            $joins[] = "LEFT JOIN mfg_client_contracts cc ON cc.account_id = '{$filters['client_id']}' AND cc.deleted = 0 AND cc.status = 'active'";
            $joins[] = "LEFT JOIN mfg_pricing_tiers pt ON cc.pricing_tier_id = pt.id AND pt.deleted = 0";
        }
        
        $having_conditions = [];
        
        // Stock status filter
        if (!empty($filters['stock_status'])) {
            $having_conditions[] = "stock_status = '{$filters['stock_status']}'";
        }
        
        // Build sorting
        $order_clause = $this->buildOrderClause($sort, $order, !empty($filters['query']));
        
        $where_clause = implode(' AND ', $where_conditions);
        $join_clause = implode(' ', $joins);
        $having_clause = !empty($having_conditions) ? 'HAVING ' . implode(' AND ', $having_conditions) : '';
        
        // Get total count
        $count_query = "
            SELECT COUNT(*) as total
            FROM (
                SELECT p.id,
                CASE 
                    WHEN i.quantity_available <= 0 THEN 'out_of_stock'
                    WHEN i.quantity_available <= i.reorder_point THEN 'low_stock'
                    ELSE 'in_stock'
                END as stock_status
                FROM mfg_products p
                {$join_clause}
                WHERE {$where_clause}
                {$having_clause}
            ) as counted
        ";
        
        $count_result = $this->db->pQuery($count_query, $params);
        $total = $this->db->fetchByAssoc($count_result)['total'];
        
        // Get products
        $select_fields = $this->getSelectFields(!empty($filters['query']), !empty($filters['client_id']));
        
        $search_query = "
            SELECT {$select_fields}
            FROM mfg_products p
            {$join_clause}
            WHERE {$where_clause}
            {$having_clause}
            {$order_clause}
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        $result = $this->db->pQuery($search_query, $params);
        $products = [];
        
        while ($row = $this->db->fetchByAssoc($result)) {
            $products[] = $this->formatSearchProduct($row);
        }
        
        return [
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => intval($total),
                'total_pages' => ceil($total / $limit)
            ],
            'filters' => $filters,
            'facets' => $this->getSearchFacets($filters['query'])
        ];
    }
    
    private function getSelectFields($has_text_search, $has_client_pricing)
    {
        $fields = [
            'p.id', 'p.name', 'p.sku', 'p.description', 'p.category', 'p.material',
            'p.weight_lbs', 'p.dimensions', 'p.base_price', 'p.list_price',
            'p.minimum_order_qty', 'p.packaging_unit', 'p.manufacturer',
            'p.lead_time_days', 'p.image_url', 'p.thumbnail_url',
            'p.specifications', 'p.tags', 'i.quantity_available',
            'CASE 
                WHEN i.quantity_available <= 0 THEN "out_of_stock"
                WHEN i.quantity_available <= i.reorder_point THEN "low_stock"
                ELSE "in_stock"
            END as stock_status'
        ];
        
        if ($has_text_search) {
            $fields[] = 'MATCH(p.name, p.description, p.sku, p.tags) AGAINST(? IN BOOLEAN MODE) as relevance';
        }
        
        if ($has_client_pricing) {
            $fields[] = 'pt.tier_name';
            $fields[] = 'pt.discount_percentage';
            $fields[] = 'ROUND(p.list_price * (1 - COALESCE(pt.discount_percentage, 0) / 100), 2) as client_price';
        }
        
        return implode(', ', $fields);
    }
    
    private function buildOrderClause($sort, $order, $has_text_search)
    {
        $valid_orders = ['asc', 'desc'];
        $order = in_array(strtolower($order), $valid_orders) ? strtolower($order) : 'desc';
        
        switch ($sort) {
            case 'relevance':
                return $has_text_search ? "ORDER BY relevance DESC, p.name ASC" : "ORDER BY p.name ASC";
            case 'name':
                return "ORDER BY p.name {$order}";
            case 'price':
                return "ORDER BY p.list_price {$order}";
            case 'stock':
                return "ORDER BY i.quantity_available {$order}";
            case 'category':
                return "ORDER BY p.category {$order}, p.name ASC";
            case 'material':
                return "ORDER BY p.material {$order}, p.name ASC";
            default:
                return $has_text_search ? "ORDER BY relevance DESC, p.name ASC" : "ORDER BY p.name ASC";
        }
    }
    
    private function formatSearchProduct($row)
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
        
        if (isset($row['relevance'])) {
            $product['relevance'] = floatval($row['relevance']);
        }
        
        if (isset($row['client_price'])) {
            $product['pricing'] = [
                'list_price' => floatval($row['list_price']),
                'client_price' => floatval($row['client_price']),
                'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                'tier_name' => $row['tier_name']
            ];
        }
        
        return $product;
    }
    
    // Cache management methods
    private function getCache($key)
    {
        if (!$this->cache_enabled) return null;
        
        // Simple file-based cache for demo
        $cache_file = sys_get_temp_dir() . '/' . $key . '.cache';
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 300) {
            return json_decode(file_get_contents($cache_file), true);
        }
        return null;
    }
    
    private function setCache($key, $data, $ttl = 300)
    {
        if (!$this->cache_enabled) return;
        
        $cache_file = sys_get_temp_dir() . '/' . $key . '.cache';
        file_put_contents($cache_file, json_encode($data));
    }
    
    private function getCurrentUserId()
    {
        global $current_user;
        return $current_user->id ?? 'anonymous';
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
