<?php
/**
 * Manufacturing Product Catalog API
 * Enterprise Legacy Modernization Project
 * 
 * RESTful API for mobile-responsive product catalog with client-specific pricing
 * 
 * @package Manufacturing
 * @version 1.0.0
 * @author Enterprise Modernization Team
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once('data/SugarBean.php');
require_once('modules/Manufacturing/Product.php');
require_once('modules/Manufacturing/Pipeline.php');

class ProductCatalogAPI
{
    private $db;
    private $current_user;
    private $cache_enabled = false;
    private $redis = null;
    
    // Cache TTL settings (in seconds)
    private $cache_ttl = [
        'products' => 3600,    // 1 hour
        'pricing' => 1800,     // 30 minutes
        'search' => 900,       // 15 minutes
        'inventory' => 300     // 5 minutes
    ];
    
    public function __construct()
    {
        global $db, $current_user;
        $this->db = $db;
        $this->current_user = $current_user;
        
        // Initialize Redis cache if available
        $this->initializeCache();
    }
    
    /**
     * Initialize Redis cache connection
     */
    private function initializeCache()
    {
        if (class_exists('Redis')) {
            try {
                $this->redis = new Redis();
                $this->redis->connect('127.0.0.1', 6379);
                $this->cache_enabled = true;
                error_log('Manufacturing API: Redis cache initialized');
            } catch (Exception $e) {
                error_log('Manufacturing API: Redis connection failed - ' . $e->getMessage());
                $this->cache_enabled = false;
            }
        }
    }
    
    /**
     * Get cached data or execute callback if cache miss
     */
    private function getFromCache($key, $callback, $ttl = 3600)
    {
        if (!$this->cache_enabled) {
            return $callback();
        }
        
        try {
            $cached = $this->redis->get($key);
            if ($cached !== false) {
                return json_decode($cached, true);
            }
            
            $data = $callback();
            $this->redis->setex($key, $ttl, json_encode($data));
            return $data;
            
        } catch (Exception $e) {
            error_log('Manufacturing API: Cache error - ' . $e->getMessage());
            return $callback();
        }
    }
    
    /**
     * Clear cache for specific pattern
     */
    private function clearCache($pattern)
    {
        if (!$this->cache_enabled) return;
        
        try {
            $keys = $this->redis->keys($pattern);
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        } catch (Exception $e) {
            error_log('Manufacturing API: Cache clear error - ' . $e->getMessage());
        }
    }
    
    /**
     * Main API endpoint router
     * 
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return array API response
     */
    public function handleRequest($method, $endpoint, $params = [])
    {
        // Start timing for performance monitoring
        $start_time = microtime(true);
        
        try {
            // Input validation and sanitization
            $params = $this->sanitizeInput($params);
            
            // Route to appropriate method
            switch ($endpoint) {
                case 'products/search':
                    $response = $this->searchProducts($params);
                    break;
                    
                case 'products/categories':
                    $response = $this->getCategories($params);
                    break;
                    
                case 'pricing/calculate':
                    $response = $this->calculatePricing($params);
                    break;
                    
                case 'inventory/check':
                    $response = $this->checkInventory($params);
                    break;
                    
                case 'products/recommendations':
                    $response = $this->getProductRecommendations($params);
                    break;
                    
                default:
                    $response = $this->errorResponse('Endpoint not found', 404);
            }
            
            // Add performance metadata
            $response['meta']['response_time'] = round((microtime(true) - $start_time) * 1000, 2) . 'ms';
            $response['meta']['cache_hit'] = isset($response['meta']['cache_hit']) ? $response['meta']['cache_hit'] : false;
            
            return $response;
            
        } catch (Exception $e) {
            error_log('Manufacturing API Error: ' . $e->getMessage());
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }
    
    /**
     * Search products with advanced filtering
     * 
     * GET /Api/v1/manufacturing/products/search
     * 
     * @param array $params Query parameters
     * @return array Search results
     */
    public function searchProducts($params)
    {
        // Extract and validate parameters
        $query = $params['q'] ?? '';
        $category = $params['category'] ?? '';
        $material = $params['material'] ?? '';
        $price_min = floatval($params['price_min'] ?? 0);
        $price_max = floatval($params['price_max'] ?? 999999);
        $sku = $params['sku'] ?? '';
        $in_stock = filter_var($params['in_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $page = max(1, intval($params['page'] ?? 1));
        $limit = min(100, max(1, intval($params['limit'] ?? 20)));
        $sort_by = $params['sort_by'] ?? 'name';
        $sort_order = strtoupper($params['sort_order'] ?? 'ASC');
        
        // Validate sort parameters
        $allowed_sort_fields = ['name', 'sku', 'category', 'base_price', 'stock_quantity', 'date_modified'];
        if (!in_array($sort_by, $allowed_sort_fields)) {
            $sort_by = 'name';
        }
        if (!in_array($sort_order, ['ASC', 'DESC'])) {
            $sort_order = 'ASC';
        }
        
        // Build cache key
        $cache_key = 'mfg_search:' . md5(serialize([
            'query' => $query,
            'category' => $category,
            'material' => $material,
            'price_min' => $price_min,
            'price_max' => $price_max,
            'sku' => $sku,
            'in_stock' => $in_stock,
            'page' => $page,
            'limit' => $limit,
            'sort_by' => $sort_by,
            'sort_order' => $sort_order
        ]));
        
        return $this->getFromCache($cache_key, function() use (
            $query, $category, $material, $price_min, $price_max, 
            $sku, $in_stock, $page, $limit, $sort_by, $sort_order
        ) {
            // Build SQL query
            $sql = "SELECT p.*, 
                           CASE 
                               WHEN p.stock_quantity > p.reorder_point THEN 'In Stock'
                               WHEN p.stock_quantity > 0 THEN 'Low Stock'
                               ELSE 'Out of Stock'
                           END as stock_status,
                           p.available_quantity
                    FROM mfg_products p 
                    WHERE p.deleted = 0";
            
            $where_conditions = [];
            $params = [];
            
            // Full-text search
            if (!empty($query)) {
                $search_term = '%' . $this->db->quote($query) . '%';
                $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? OR p.specifications LIKE ?)";
                $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
            }
            
            // Category filter
            if (!empty($category)) {
                $where_conditions[] = "p.category = ?";
                $params[] = $category;
            }
            
            // Material filter
            if (!empty($material)) {
                $where_conditions[] = "p.material = ?";
                $params[] = $material;
            }
            
            // Price range filter
            if ($price_min > 0) {
                $where_conditions[] = "p.base_price >= ?";
                $params[] = $price_min;
            }
            if ($price_max < 999999) {
                $where_conditions[] = "p.base_price <= ?";
                $params[] = $price_max;
            }
            
            // SKU filter
            if (!empty($sku)) {
                $where_conditions[] = "p.sku LIKE ?";
                $params[] = '%' . $sku . '%';
            }
            
            // Stock filter
            if ($in_stock) {
                $where_conditions[] = "p.stock_quantity > 0";
            }
            
            // Add WHERE conditions
            if (!empty($where_conditions)) {
                $sql .= " AND " . implode(" AND ", $where_conditions);
            }
            
            // Add sorting
            $sql .= " ORDER BY p.{$sort_by} {$sort_order}";
            
            // Get total count for pagination
            $count_sql = str_replace("SELECT p.*, CASE WHEN p.stock_quantity > p.reorder_point THEN 'In Stock' WHEN p.stock_quantity > 0 THEN 'Low Stock' ELSE 'Out of Stock' END as stock_status, p.available_quantity", "SELECT COUNT(*)", $sql);
            $count_sql = preg_replace('/ORDER BY.*$/', '', $count_sql);
            
            $stmt = $this->db->prepare($count_sql);
            $stmt->execute($params);
            $total_count = $stmt->fetchColumn();
            
            // Add pagination
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
            
            // Execute main query
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format products for API response
            $formatted_products = array_map([$this, 'formatProductForAPI'], $products);
            
            return [
                'status' => 'success',
                'data' => [
                    'products' => $formatted_products,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total_count' => $total_count,
                        'total_pages' => ceil($total_count / $limit),
                        'has_next' => $page < ceil($total_count / $limit),
                        'has_prev' => $page > 1
                    ]
                ],
                'meta' => [
                    'query_time' => microtime(true),
                    'cache_hit' => false
                ]
            ];
            
        }, $this->cache_ttl['search']);
    }
    
    /**
     * Get product categories with counts
     * 
     * @param array $params Query parameters
     * @return array Categories data
     */
    public function getCategories($params)
    {
        $cache_key = 'mfg_categories:all';
        
        return $this->getFromCache($cache_key, function() {
            $sql = "SELECT category, 
                           COUNT(*) as product_count,
                           AVG(base_price) as avg_price,
                           MIN(base_price) as min_price,
                           MAX(base_price) as max_price
                    FROM mfg_products 
                    WHERE deleted = 0 AND status = 'Active'
                    GROUP BY category 
                    ORDER BY category";
            
            $result = $this->db->query($sql);
            $categories = [];
            
            while ($row = $this->db->fetchByAssoc($result)) {
                $categories[] = [
                    'name' => $row['category'],
                    'product_count' => intval($row['product_count']),
                    'price_range' => [
                        'min' => floatval($row['min_price']),
                        'max' => floatval($row['max_price']),
                        'avg' => floatval($row['avg_price'])
                    ]
                ];
            }
            
            return [
                'status' => 'success',
                'data' => [
                    'categories' => $categories
                ],
                'meta' => [
                    'cache_hit' => false
                ]
            ];
            
        }, $this->cache_ttl['products']);
    }
    
    /**
     * Calculate client-specific pricing
     * 
     * @param array $params Pricing parameters
     * @return array Pricing calculations
     */
    public function calculatePricing($params)
    {
        $product_id = $params['product_id'] ?? '';
        $client_id = $params['client_id'] ?? '';
        $quantity = max(1, intval($params['quantity'] ?? 1));
        
        if (empty($product_id)) {
            return $this->errorResponse('Product ID is required', 400);
        }
        
        $cache_key = "mfg_pricing:{$product_id}:{$client_id}:{$quantity}";
        
        return $this->getFromCache($cache_key, function() use ($product_id, $client_id, $quantity) {
            
            // Get product base price
            $product_sql = "SELECT * FROM mfg_products WHERE id = ? AND deleted = 0";
            $product_result = $this->db->query($product_sql, [$product_id]);
            $product = $this->db->fetchByAssoc($product_result);
            
            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }
            
            $base_price = floatval($product['base_price']);
            $calculated_price = $base_price;
            $discounts_applied = [];
            
            // Step 1: Check for client-specific contract pricing
            if (!empty($client_id)) {
                $contract_sql = "SELECT cc.*, pt.discount_percentage as tier_discount 
                               FROM mfg_client_contracts cc
                               LEFT JOIN mfg_pricing_tiers pt ON cc.pricing_tier_id = pt.id
                               WHERE cc.client_id = ? 
                               AND cc.status = 'Active' 
                               AND cc.effective_date <= CURDATE()
                               AND (cc.expiration_date IS NULL OR cc.expiration_date >= CURDATE())
                               AND cc.deleted = 0
                               ORDER BY cc.date_entered DESC
                               LIMIT 1";
                
                $contract_result = $this->db->query($contract_sql, [$client_id]);
                $contract = $this->db->fetchByAssoc($contract_result);
                
                if ($contract) {
                    // Apply contract discount
                    if ($contract['custom_discount_percentage'] > 0) {
                        $discount = $contract['custom_discount_percentage'] / 100;
                        $calculated_price = $base_price * (1 - $discount);
                        $discounts_applied[] = [
                            'type' => 'contract_discount',
                            'description' => 'Contract Discount',
                            'percentage' => $contract['custom_discount_percentage'],
                            'amount' => $base_price - $calculated_price
                        ];
                    }
                    
                    // Apply volume tier discounts
                    $volume_discount = 0;
                    if ($quantity >= $contract['volume_tier_3_qty'] && $contract['volume_tier_3_discount'] > 0) {
                        $volume_discount = $contract['volume_tier_3_discount'];
                    } elseif ($quantity >= $contract['volume_tier_2_qty'] && $contract['volume_tier_2_discount'] > 0) {
                        $volume_discount = $contract['volume_tier_2_discount'];
                    } elseif ($quantity >= $contract['volume_tier_1_qty'] && $contract['volume_tier_1_discount'] > 0) {
                        $volume_discount = $contract['volume_tier_1_discount'];
                    }
                    
                    if ($volume_discount > 0) {
                        $discount_amount = $calculated_price * ($volume_discount / 100);
                        $calculated_price -= $discount_amount;
                        $discounts_applied[] = [
                            'type' => 'volume_discount',
                            'description' => "Volume Discount ({$quantity} units)",
                            'percentage' => $volume_discount,
                            'amount' => $discount_amount
                        ];
                    }
                    
                    // Apply tier discount if no contract discount
                    if ($contract['custom_discount_percentage'] == 0 && $contract['tier_discount'] > 0) {
                        $discount = $contract['tier_discount'] / 100;
                        $tier_discount_amount = $calculated_price * $discount;
                        $calculated_price -= $tier_discount_amount;
                        $discounts_applied[] = [
                            'type' => 'tier_discount',
                            'description' => 'Pricing Tier Discount',
                            'percentage' => $contract['tier_discount'],
                            'amount' => $tier_discount_amount
                        ];
                    }
                }
            }
            
            // Step 2: Check for product-specific pricing overrides
            $override_sql = "SELECT * FROM mfg_product_pricing 
                           WHERE product_id = ? 
                           AND (pricing_tier_id IS NOT NULL OR client_contract_id IS NOT NULL)
                           AND effective_date <= CURDATE()
                           AND (expiration_date IS NULL OR expiration_date >= CURDATE())
                           AND min_quantity <= ?
                           AND deleted = 0
                           ORDER BY custom_price ASC
                           LIMIT 1";
            
            $override_result = $this->db->query($override_sql, [$product_id, $quantity]);
            $override = $this->db->fetchByAssoc($override_result);
            
            if ($override && $override['custom_price'] > 0) {
                $calculated_price = floatval($override['custom_price']);
                $discounts_applied[] = [
                    'type' => 'custom_pricing',
                    'description' => 'Special Product Pricing',
                    'amount' => $base_price - $calculated_price,
                    'notes' => $override['notes']
                ];
            }
            
            // Calculate totals
            $unit_price = $calculated_price;
            $total_price = $unit_price * $quantity;
            $total_savings = ($base_price - $unit_price) * $quantity;
            
            return [
                'status' => 'success',
                'data' => [
                    'product_id' => $product_id,
                    'client_id' => $client_id,
                    'quantity' => $quantity,
                    'pricing' => [
                        'base_price' => $base_price,
                        'unit_price' => round($unit_price, 4),
                        'total_price' => round($total_price, 2),
                        'total_savings' => round($total_savings, 2),
                        'savings_percentage' => $base_price > 0 ? round(($total_savings / ($base_price * $quantity)) * 100, 2) : 0
                    ],
                    'discounts_applied' => $discounts_applied,
                    'currency' => $product['currency'] ?? 'USD'
                ],
                'meta' => [
                    'calculation_time' => microtime(true),
                    'cache_hit' => false
                ]
            ];
            
        }, $this->cache_ttl['pricing']);
    }
    
    /**
     * Check product inventory and availability
     * 
     * @param array $params Inventory parameters
     * @return array Inventory status
     */
    public function checkInventory($params)
    {
        $product_ids = $params['product_ids'] ?? [];
        $warehouse_id = $params['warehouse_id'] ?? '';
        
        if (empty($product_ids)) {
            return $this->errorResponse('Product IDs are required', 400);
        }
        
        if (!is_array($product_ids)) {
            $product_ids = [$product_ids];
        }
        
        $cache_key = 'mfg_inventory:' . md5(serialize($product_ids) . $warehouse_id);
        
        return $this->getFromCache($cache_key, function() use ($product_ids, $warehouse_id) {
            
            $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
            $sql = "SELECT id, name, sku, 
                           stock_quantity,
                           reserved_quantity,
                           available_quantity,
                           reorder_point,
                           reorder_quantity,
                           lead_time_days,
                           CASE 
                               WHEN available_quantity > reorder_point THEN 'In Stock'
                               WHEN available_quantity > 0 THEN 'Low Stock'
                               WHEN stock_quantity > reserved_quantity THEN 'Limited Stock'
                               ELSE 'Out of Stock'
                           END as stock_status,
                           CASE 
                               WHEN available_quantity <= 0 AND lead_time_days > 0 
                               THEN DATE_ADD(CURDATE(), INTERVAL lead_time_days DAY)
                               ELSE NULL
                           END as estimated_restock_date
                    FROM mfg_products 
                    WHERE id IN ({$placeholders}) 
                    AND deleted = 0";
            
            $result = $this->db->query($sql, $product_ids);
            $inventory_data = [];
            
            while ($row = $this->db->fetchByAssoc($result)) {
                $inventory_data[] = [
                    'product_id' => $row['id'],
                    'sku' => $row['sku'],
                    'name' => $row['name'],
                    'stock_status' => $row['stock_status'],
                    'quantities' => [
                        'in_stock' => intval($row['stock_quantity']),
                        'reserved' => intval($row['reserved_quantity']),
                        'available' => intval($row['available_quantity']),
                        'reorder_point' => intval($row['reorder_point'])
                    ],
                    'availability' => [
                        'is_available' => $row['available_quantity'] > 0,
                        'max_quantity' => intval($row['available_quantity']),
                        'estimated_restock_date' => $row['estimated_restock_date'],
                        'lead_time_days' => intval($row['lead_time_days'])
                    ]
                ];
            }
            
            return [
                'status' => 'success',
                'data' => [
                    'inventory' => $inventory_data
                ],
                'meta' => [
                    'warehouse_id' => $warehouse_id,
                    'check_time' => date('Y-m-d H:i:s'),
                    'cache_hit' => false
                ]
            ];
            
        }, $this->cache_ttl['inventory']);
    }
    
    /**
     * Get product recommendations based on various criteria
     * 
     * @param array $params Recommendation parameters
     * @return array Product recommendations
     */
    public function getProductRecommendations($params)
    {
        $product_id = $params['product_id'] ?? '';
        $client_id = $params['client_id'] ?? '';
        $category = $params['category'] ?? '';
        $limit = min(20, max(1, intval($params['limit'] ?? 5)));
        
        $cache_key = "mfg_recommendations:{$product_id}:{$client_id}:{$category}:{$limit}";
        
        return $this->getFromCache($cache_key, function() use ($product_id, $client_id, $category, $limit) {
            
            $recommendations = [];
            
            // If product_id provided, find similar products
            if (!empty($product_id)) {
                $base_product_sql = "SELECT category, material, subcategory FROM mfg_products WHERE id = ? AND deleted = 0";
                $base_result = $this->db->query($base_product_sql, [$product_id]);
                $base_product = $this->db->fetchByAssoc($base_result);
                
                if ($base_product) {
                    $similar_sql = "SELECT *, 
                                          CASE 
                                              WHEN category = ? AND material = ? THEN 3
                                              WHEN category = ? THEN 2
                                              WHEN material = ? THEN 1
                                              ELSE 0
                                          END as relevance_score
                                   FROM mfg_products 
                                   WHERE id != ? 
                                   AND deleted = 0 
                                   AND status = 'Active'
                                   AND (category = ? OR material = ? OR subcategory = ?)
                                   ORDER BY relevance_score DESC, stock_quantity DESC
                                   LIMIT ?";
                    
                    $params = [
                        $base_product['category'], $base_product['material'],
                        $base_product['category'],
                        $base_product['material'],
                        $product_id,
                        $base_product['category'], $base_product['material'], $base_product['subcategory'],
                        $limit
                    ];
                    
                    $result = $this->db->query($similar_sql, $params);
                    while ($row = $this->db->fetchByAssoc($result)) {
                        $recommendations[] = [
                            'type' => 'similar_product',
                            'reason' => 'Similar to current product',
                            'product' => $this->formatProductForAPI($row),
                            'relevance_score' => intval($row['relevance_score'])
                        ];
                    }
                }
            }
            
            // Category-based recommendations
            if (!empty($category) && count($recommendations) < $limit) {
                $remaining_limit = $limit - count($recommendations);
                $category_sql = "SELECT * FROM mfg_products 
                               WHERE category = ? 
                               AND deleted = 0 
                               AND status = 'Active'
                               AND stock_quantity > 0
                               ORDER BY stock_quantity DESC, base_price ASC
                               LIMIT ?";
                
                $result = $this->db->query($category_sql, [$category, $remaining_limit]);
                while ($row = $this->db->fetchByAssoc($result)) {
                    $recommendations[] = [
                        'type' => 'category_match',
                        'reason' => "Popular in {$category} category",
                        'product' => $this->formatProductForAPI($row),
                        'relevance_score' => 2
                    ];
                }
            }
            
            // Popular products fallback
            if (count($recommendations) < $limit) {
                $remaining_limit = $limit - count($recommendations);
                $popular_sql = "SELECT p.*, COUNT(t.id) as transaction_count
                              FROM mfg_products p
                              LEFT JOIN mfg_inventory_transactions t ON p.id = t.product_id 
                                  AND t.transaction_type = 'Sale' 
                                  AND t.date_entered >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                              WHERE p.deleted = 0 
                              AND p.status = 'Active'
                              AND p.stock_quantity > 0
                              GROUP BY p.id
                              ORDER BY transaction_count DESC, p.stock_quantity DESC
                              LIMIT ?";
                
                $result = $this->db->query($popular_sql, [$remaining_limit]);
                while ($row = $this->db->fetchByAssoc($result)) {
                    $recommendations[] = [
                        'type' => 'popular_product',
                        'reason' => 'Popular choice',
                        'product' => $this->formatProductForAPI($row),
                        'relevance_score' => 1
                    ];
                }
            }
            
            return [
                'status' => 'success',
                'data' => [
                    'recommendations' => array_slice($recommendations, 0, $limit)
                ],
                'meta' => [
                    'total_found' => count($recommendations),
                    'cache_hit' => false
                ]
            ];
            
        }, $this->cache_ttl['products']);
    }
    
    /**
     * Format product data for API response
     * 
     * @param array $product Raw product data
     * @return array Formatted product data
     */
    private function formatProductForAPI($product)
    {
        return [
            'id' => $product['id'],
            'name' => $product['name'],
            'sku' => $product['sku'],
            'description' => $product['description'],
            'category' => $product['category'],
            'subcategory' => $product['subcategory'],
            'material' => $product['material'],
            'grade' => $product['grade'],
            'finish' => $product['finish'],
            'pricing' => [
                'base_price' => floatval($product['base_price']),
                'list_price' => floatval($product['list_price']),
                'currency' => $product['currency'] ?? 'USD'
            ],
            'specifications' => [
                'weight' => floatval($product['weight']),
                'weight_unit' => $product['weight_unit'],
                'dimensions' => [
                    'length' => floatval($product['length']),
                    'width' => floatval($product['width']),
                    'height' => floatval($product['height']),
                    'unit' => $product['dimension_unit']
                ]
            ],
            'inventory' => [
                'stock_quantity' => intval($product['stock_quantity']),
                'available_quantity' => intval($product['available_quantity'] ?? $product['stock_quantity']),
                'stock_status' => $product['stock_status'] ?? 'Unknown',
                'reorder_point' => intval($product['reorder_point'])
            ],
            'media' => [
                'primary_image' => $product['primary_image_url'],
                'datasheet' => $product['datasheet_url'],
                'cad_file' => $product['cad_file_url']
            ],
            'compliance' => [
                'certifications' => $product['certifications'],
                'compliance_notes' => $product['compliance_notes']
            ],
            'status' => $product['status'],
            'last_updated' => $product['date_modified']
        ];
    }
    
    /**
     * Sanitize input parameters
     * 
     * @param array $params Input parameters
     * @return array Sanitized parameters
     */
    private function sanitizeInput($params)
    {
        $sanitized = [];
        
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                // Remove potential SQL injection attempts
                $value = strip_tags($value);
                $value = trim($value);
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Generate error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param string $details Additional error details
     * @return array Error response
     */
    private function errorResponse($message, $code = 400, $details = '')
    {
        return [
            'status' => 'error',
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details
            ],
            'meta' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'api_version' => '1.0.0'
            ]
        ];
    }
    
    /**
     * Get API documentation/health check
     * 
     * @return array API info
     */
    public function getApiInfo()
    {
        return [
            'status' => 'success',
            'data' => [
                'api_name' => 'Manufacturing Product Catalog API',
                'version' => '1.0.0',
                'endpoints' => [
                    'GET /products/search' => 'Search products with filtering',
                    'GET /products/categories' => 'Get product categories',
                    'POST /pricing/calculate' => 'Calculate client-specific pricing',
                    'POST /inventory/check' => 'Check product availability',
                    'GET /products/recommendations' => 'Get product recommendations'
                ],
                'cache_enabled' => $this->cache_enabled,
                'performance' => [
                    'target_response_time' => '<500ms',
                    'cache_ttl' => $this->cache_ttl
                ]
            ],
            'meta' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'server_time' => microtime(true)
            ]
        ];
    }
}
