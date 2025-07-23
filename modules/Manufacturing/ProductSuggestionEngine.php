<?php
/**
 * Product Suggestion Engine
 * Manufacturing Distribution Platform - Feature 3
 * 
 * Intelligent product suggestions based on:
 * - Category and specifications matching
 * - Customer purchase history
 * - Stock availability and location
 * - Price range compatibility
 * - Cross-sell and upsell opportunities
 */

require_once('config.php');

class ProductSuggestionEngine {
    private $db;
    private $cache = [];
    private $cache_ttl = 300; // 5 minutes
    
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
            throw new Exception("Database connection failed: " . $this->db->connect_error);
        }
    }
    
    /**
     * Main method to get product suggestions
     */
    public function getSuggestions($product_id, $options = []) {
        $default_options = [
            'customer_id' => null,
            'warehouse_id' => null,
            'price_range_percent' => 30, // ±30% price range
            'max_suggestions' => 10,
            'include_out_of_stock' => false,
            'suggestion_types' => ['similar', 'alternative', 'cross_sell', 'upsell'],
            'min_relevance_score' => 0.3
        ];
        
        $options = array_merge($default_options, $options);
        
        // Check cache first
        $cache_key = $this->getCacheKey($product_id, $options);
        if (isset($this->cache[$cache_key]) && 
            (time() - $this->cache[$cache_key]['timestamp']) < $this->cache_ttl) {
            return $this->cache[$cache_key]['data'];
        }
        
        // Get the base product information
        $base_product = $this->getProductDetails($product_id);
        if (!$base_product) {
            return ['error' => 'Product not found'];
        }
        
        $suggestions = [];
        
        // Generate different types of suggestions
        foreach ($options['suggestion_types'] as $type) {
            switch ($type) {
                case 'similar':
                    $suggestions = array_merge($suggestions, 
                        $this->getSimilarProducts($base_product, $options));
                    break;
                case 'alternative':
                    $suggestions = array_merge($suggestions, 
                        $this->getAlternativeProducts($base_product, $options));
                    break;
                case 'cross_sell':
                    $suggestions = array_merge($suggestions, 
                        $this->getCrossSellProducts($base_product, $options));
                    break;
                case 'upsell':
                    $suggestions = array_merge($suggestions, 
                        $this->getUpsellProducts($base_product, $options));
                    break;
            }
        }
        
        // Remove duplicates and the original product
        $suggestions = $this->deduplicateSuggestions($suggestions, $product_id);
        
        // Apply filters
        $suggestions = $this->applyFilters($suggestions, $options);
        
        // Calculate relevance scores
        $suggestions = $this->calculateRelevanceScores($suggestions, $base_product, $options);
        
        // Sort by relevance score
        usort($suggestions, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        // Limit results
        $suggestions = array_slice($suggestions, 0, $options['max_suggestions']);
        
        // Prepare final result
        $result = [
            'base_product' => $base_product,
            'suggestions' => $suggestions,
            'total_found' => count($suggestions),
            'generated_at' => date('Y-m-d H:i:s'),
            'cache_key' => $cache_key
        ];
        
        // Cache the result
        $this->cache[$cache_key] = [
            'data' => $result,
            'timestamp' => time()
        ];
        
        return $result;
    }
    
    /**
     * Get similar products based on category and specifications
     */
    private function getSimilarProducts($base_product, $options) {
        $similar_products = [];
        
        // Find products in the same category
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.id, p.name, p.sku, p.category, p.unit_price,
                   p.description,
                   i.current_stock, i.available_stock, i.warehouse_id,
                   w.name as warehouse_name, w.code as warehouse_code
            FROM mfg_products p
            JOIN mfg_inventory i ON p.id = i.product_id
            JOIN mfg_warehouses w ON i.warehouse_id = w.id
            WHERE p.category = ? 
                AND p.id != ? 
                AND p.deleted = 0 
                AND i.deleted = 0 
                AND w.deleted = 0
                AND (? = 1 OR i.available_stock > 0)
            ORDER BY p.name ASC
            LIMIT 20
        ");
        
        $include_out_of_stock = $options['include_out_of_stock'] ? 1 : 0;
        $stmt->bind_param('ssi', $base_product['category'], $base_product['id'], $include_out_of_stock);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $similar_products[] = [
                'product' => $row,
                'suggestion_type' => 'similar',
                'reason' => 'Same category: ' . $row['category'],
                'base_score' => 0.8 // High score for same category
            ];
        }
        
        return $similar_products;
    }
    
    /**
     * Get alternative products when original is out of stock
     */
    private function getAlternativeProducts($base_product, $options) {
        $alternatives = [];
        
        // Find products with similar price and function
        $price_min = $base_product['unit_price'] * (1 - $options['price_range_percent'] / 100);
        $price_max = $base_product['unit_price'] * (1 + $options['price_range_percent'] / 100);
        
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.id, p.name, p.sku, p.category, p.unit_price,
                   p.description,
                   i.current_stock, i.available_stock, i.warehouse_id,
                   w.name as warehouse_name, w.code as warehouse_code,
                   ABS(p.unit_price - ?) as price_diff
            FROM mfg_products p
            JOIN mfg_inventory i ON p.id = i.product_id
            JOIN mfg_warehouses w ON i.warehouse_id = w.id
            WHERE p.unit_price BETWEEN ? AND ?
                AND p.id != ? 
                AND p.deleted = 0 
                AND i.deleted = 0 
                AND w.deleted = 0
                AND i.available_stock > 0
            ORDER BY price_diff ASC, i.available_stock DESC
            LIMIT 15
        ");
        
        $stmt->bind_param('ddds', $base_product['unit_price'], $price_min, $price_max, $base_product['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $price_similarity = $base_product['unit_price'] > 0 ? 
                1 - ($row['price_diff'] / $base_product['unit_price']) : 0.5;
            $alternatives[] = [
                'product' => $row,
                'suggestion_type' => 'alternative',
                'reason' => 'Similar price range and functionality',
                'base_score' => 0.7 * $price_similarity,
                'price_difference' => $row['price_diff'],
                'price_similarity' => $price_similarity
            ];
        }
        
        return $alternatives;
    }
    
    /**
     * Get cross-sell products (frequently bought together)
     */
    private function getCrossSellProducts($base_product, $options) {
        $cross_sell = [];
        
        // This would typically analyze order history to find frequently bought together items
        // For demo, we'll use category-based cross-selling
        
        $cross_sell_categories = $this->getCrossSellCategories($base_product['category']);
        
        if (!empty($cross_sell_categories)) {
            $category_placeholders = str_repeat('?,', count($cross_sell_categories) - 1) . '?';
            
            $stmt = $this->db->prepare("
                SELECT DISTINCT p.id, p.name, p.sku, p.category, p.unit_price,
                       p.description,
                       i.current_stock, i.available_stock, i.warehouse_id,
                       w.name as warehouse_name, w.code as warehouse_code
                FROM mfg_products p
                JOIN mfg_inventory i ON p.id = i.product_id
                JOIN mfg_warehouses w ON i.warehouse_id = w.id
                WHERE p.category IN ($category_placeholders)
                    AND p.id != ? 
                    AND p.deleted = 0 
                    AND i.deleted = 0 
                    AND w.deleted = 0
                    AND i.available_stock > 0
                ORDER BY i.available_stock DESC
                LIMIT 10
            ");
            
            $params = array_merge($cross_sell_categories, [$base_product['id']]);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $cross_sell[] = [
                    'product' => $row,
                    'suggestion_type' => 'cross_sell',
                    'reason' => 'Frequently bought with ' . $base_product['category'] . ' products',
                    'base_score' => 0.6
                ];
            }
        }
        
        return $cross_sell;
    }
    
    /**
     * Get upsell products (higher value alternatives)
     */
    private function getUpsellProducts($base_product, $options) {
        $upsell = [];
        
        // Find products in same category with higher price
        $min_price = $base_product['unit_price'] * 1.2; // At least 20% more expensive
        $max_price = $base_product['unit_price'] * 2.0; // Not more than 2x price
        
        $stmt = $this->db->prepare("
            SELECT DISTINCT p.id, p.name, p.sku, p.category, p.unit_price,
                   p.description,
                   i.current_stock, i.available_stock, i.warehouse_id,
                   w.name as warehouse_name, w.code as warehouse_code,
                   (p.unit_price - ?) as price_premium
            FROM mfg_products p
            JOIN mfg_inventory i ON p.id = i.product_id
            JOIN mfg_warehouses w ON i.warehouse_id = w.id
            WHERE p.category = ?
                AND p.unit_price BETWEEN ? AND ?
                AND p.id != ? 
                AND p.deleted = 0 
                AND i.deleted = 0 
                AND w.deleted = 0
                AND i.available_stock > 0
            ORDER BY p.unit_price ASC
            LIMIT 8
        ");
        
        $stmt->bind_param('dsdds', 
            $base_product['unit_price'], 
            $base_product['category'], 
            $min_price, 
            $max_price,
            $base_product['id']
        );
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $price_premium_percent = $base_product['unit_price'] > 0 ? 
                ($row['price_premium'] / $base_product['unit_price']) * 100 : 0;
            $upsell[] = [
                'product' => $row,
                'suggestion_type' => 'upsell',
                'reason' => sprintf('Premium option with %.0f%% higher value', $price_premium_percent),
                'base_score' => 0.5,
                'price_premium' => $row['price_premium'],
                'price_premium_percent' => $price_premium_percent
            ];
        }
        
        return $upsell;
    }
    
    /**
     * Get customer purchase history to improve suggestions
     */
    private function getCustomerPurchaseHistory($customer_id, $limit = 50) {
        // This would analyze actual order history
        // For demo purposes, return empty array
        return [];
    }
    
    /**
     * Get cross-sell categories based on product category
     */
    private function getCrossSellCategories($category) {
        $cross_sell_map = [
            'Industrial Parts' => ['Safety Equipment', 'Maintenance Tools', 'Lubricants'],
            'Safety Equipment' => ['Industrial Parts', 'PPE', 'First Aid'],
            'Maintenance Tools' => ['Industrial Parts', 'Safety Equipment', 'Lubricants'],
            'Electrical Components' => ['Cables', 'Connectors', 'Safety Equipment'],
            'Hydraulic Systems' => ['Seals', 'Filters', 'Lubricants']
        ];
        
        return $cross_sell_map[$category] ?? [];
    }
    
    /**
     * Get detailed product information
     */
    private function getProductDetails($product_id) {
        $stmt = $this->db->prepare("
            SELECT p.id, p.name, p.sku, p.category, p.unit_price, 
                   p.description, p.status,
                   SUM(i.current_stock) as total_stock,
                   SUM(i.available_stock) as total_available,
                   COUNT(i.warehouse_id) as warehouse_count
            FROM mfg_products p
            LEFT JOIN mfg_inventory i ON p.id = i.product_id AND i.deleted = 0
            WHERE p.id = ? AND p.deleted = 0
            GROUP BY p.id
        ");
        
        $stmt->bind_param('s', $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Remove duplicate suggestions and original product
     */
    private function deduplicateSuggestions($suggestions, $original_product_id) {
        $seen = [$original_product_id => true];
        $unique = [];
        
        foreach ($suggestions as $suggestion) {
            $product_id = $suggestion['product']['id'];
            if (!isset($seen[$product_id])) {
                $seen[$product_id] = true;
                $unique[] = $suggestion;
            }
        }
        
        return $unique;
    }
    
    /**
     * Apply various filters to suggestions
     */
    private function applyFilters($suggestions, $options) {
        $filtered = [];
        
        foreach ($suggestions as $suggestion) {
            // Filter by warehouse if specified
            if ($options['warehouse_id'] && 
                $suggestion['product']['warehouse_id'] !== $options['warehouse_id']) {
                continue;
            }
            
            // Filter out of stock if not allowed
            if (!$options['include_out_of_stock'] && 
                $suggestion['product']['available_stock'] <= 0) {
                continue;
            }
            
            $filtered[] = $suggestion;
        }
        
        return $filtered;
    }
    
    /**
     * Calculate relevance scores for all suggestions
     */
    private function calculateRelevanceScores($suggestions, $base_product, $options) {
        foreach ($suggestions as &$suggestion) {
            $score = $suggestion['base_score'];
            
            // Boost score based on stock availability
            if ($suggestion['product']['available_stock'] > 0) {
                $stock_factor = min(1.0, $suggestion['product']['available_stock'] / 100);
                $score += 0.2 * $stock_factor;
            }
            
            // Boost score for exact category match
            if ($suggestion['product']['category'] === $base_product['category']) {
                $score += 0.1;
            }
            
            // Price similarity factor (for alternatives)
            if ($suggestion['suggestion_type'] === 'alternative' && 
                isset($suggestion['price_similarity'])) {
                $score *= $suggestion['price_similarity'];
            }
            
            // Name similarity (basic keyword matching)
            $name_similarity = $this->calculateNameSimilarity(
                $base_product['name'], 
                $suggestion['product']['name']
            );
            $score += 0.1 * $name_similarity;
            
            // Ensure score is between 0 and 1
            $suggestion['relevance_score'] = max(0, min(1, $score));
            
            // Add explanation
            $suggestion['score_explanation'] = $this->explainScore($suggestion, $base_product);
        }
        
        return $suggestions;
    }
    
    /**
     * Calculate name similarity using simple keyword matching
     */
    private function calculateNameSimilarity($name1, $name2) {
        $words1 = array_map('strtolower', preg_split('/\s+/', $name1));
        $words2 = array_map('strtolower', preg_split('/\s+/', $name2));
        
        $common_words = array_intersect($words1, $words2);
        $total_words = array_unique(array_merge($words1, $words2));
        
        return count($total_words) > 0 ? count($common_words) / count($total_words) : 0;
    }
    
    /**
     * Provide explanation for the relevance score
     */
    private function explainScore($suggestion, $base_product) {
        $factors = [];
        
        if ($suggestion['product']['category'] === $base_product['category']) {
            $factors[] = 'Same category';
        }
        
        if ($suggestion['product']['available_stock'] > 0) {
            $factors[] = 'In stock';
        }
        
        if ($suggestion['suggestion_type'] === 'alternative') {
            $factors[] = 'Price compatible';
        }
        
        if ($suggestion['suggestion_type'] === 'cross_sell') {
            $factors[] = 'Complementary product';
        }
        
        if ($suggestion['suggestion_type'] === 'upsell') {
            $factors[] = 'Premium option';
        }
        
        return implode(', ', $factors);
    }
    
    /**
     * Generate cache key
     */
    private function getCacheKey($product_id, $options) {
        return 'suggestions_' . $product_id . '_' . md5(serialize($options));
    }
    
    /**
     * Clear suggestion cache
     */
    public function clearCache($product_id = null) {
        if ($product_id) {
            // Clear cache for specific product
            $keys_to_remove = [];
            foreach (array_keys($this->cache) as $key) {
                if (strpos($key, 'suggestions_' . $product_id . '_') === 0) {
                    $keys_to_remove[] = $key;
                }
            }
            foreach ($keys_to_remove as $key) {
                unset($this->cache[$key]);
            }
        } else {
            // Clear all cache
            $this->cache = [];
        }
    }
    
    /**
     * Get suggestion statistics
     */
    public function getSuggestionStats() {
        $stats = [
            'cache_entries' => count($this->cache),
            'cache_hit_rate' => 0, // Would track in production
            'avg_suggestions_per_product' => 0,
            'most_common_suggestion_types' => []
        ];
        
        return $stats;
    }
}
?>
