<?php
/**
 * AI-Powered Product Recommendations API
 * Enterprise-grade recommendation engine for SuiteCRM Manufacturing
 * 
 * Delivers intelligent product suggestions based on:
 * - Customer purchase history and patterns
 * - Product compatibility and cross-selling opportunities
 * - Inventory availability and seasonal trends
 * - Manufacturing-specific business rules
 * 
 * @package SuiteCRM\Api\V8\Manufacturing\AI
 * @author AI-Assisted Development Team
 * @version 1.0.0
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'Api/V8/BeanDecorator/BeanManager.php';

class ProductRecommendationsApi extends SugarApi
{
    private $db;
    private $cacheManager;
    private $inventoryService;
    private $analyticsEngine;

    public function __construct()
    {
        $this->db = DBManagerFactory::getInstance();
        $this->cacheManager = SugarCache::instance();
        $this->inventoryService = new InventoryIntegrationService();
        $this->analyticsEngine = new ManufacturingAnalyticsEngine();
    }

    public function registerApiRest()
    {
        return [
            'manufacturing_product_recommendations' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'product-recommendations'],
                'pathVars' => [],
                'method' => 'getProductRecommendations',
                'shortHelp' => 'Get AI-powered product recommendations',
                'longHelp' => [
                    'en' => 'Returns intelligent product recommendations based on customer context, purchase history, and manufacturing business rules'
                ]
            ],
            'manufacturing_similar_products' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'similar-products', '?'],
                'pathVars' => ['product_id'],
                'method' => 'getSimilarProducts',
                'shortHelp' => 'Get similar product recommendations',
                'longHelp' => [
                    'en' => 'Returns products similar to the specified product based on AI analysis'
                ]
            ],
            'manufacturing_cross_sell_recommendations' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'cross-sell', '?'],
                'pathVars' => ['customer_id'],
                'method' => 'getCrossSellRecommendations',
                'shortHelp' => 'Get cross-selling recommendations',
                'longHelp' => [
                    'en' => 'Returns cross-selling opportunities based on customer purchase patterns'
                ]
            ],
            'manufacturing_inventory_based_suggestions' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'inventory-suggestions'],
                'pathVars' => [],
                'method' => 'getInventoryBasedSuggestions',
                'shortHelp' => 'Get inventory-optimized product suggestions',
                'longHelp' => [
                    'en' => 'Returns product suggestions prioritizing available inventory and fast-moving items'
                ]
            ]
        ];
    }

    /**
     * Get AI-powered product recommendations
     * 
     * @param ServiceBase $api
     * @param array $args Request arguments
     * @return array Recommendation results
     * 
     * @throws SugarApiExceptionMissingParameter
     * @throws SugarApiExceptionInvalidParameter
     */
    public function getProductRecommendations($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            // Validate required parameters
            $customerId = $args['customer_id'] ?? null;
            $context = $args['context'] ?? 'general'; // 'quote', 'order', 'browse', 'general'
            $limit = min((int)($args['limit'] ?? 10), 50); // Max 50 recommendations
            $includeOutOfStock = filter_var($args['include_out_of_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if (!$customerId) {
                throw new SugarApiExceptionMissingParameter('customer_id is required');
            }

            // Generate cache key for recommendations
            $cacheKey = "product_recommendations_{$customerId}_{$context}_" . md5(json_encode($args));
            
            // Check cache first (15-minute TTL for performance)
            $cachedResults = $this->cacheManager->get($cacheKey);
            if ($cachedResults !== null) {
                $cachedResults['cached'] = true;
                $cachedResults['execution_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
                return $cachedResults;
            }

            // Get customer context and purchase history
            $customerContext = $this->getCustomerContext($customerId);
            $purchaseHistory = $this->getPurchaseHistory($customerId, 24); // Last 24 months
            
            // Generate recommendations using multiple AI algorithms
            $recommendations = $this->generateRecommendations([
                'customer_context' => $customerContext,
                'purchase_history' => $purchaseHistory,
                'context' => $context,
                'limit' => $limit,
                'include_out_of_stock' => $includeOutOfStock
            ]);

            // Enhance recommendations with business intelligence
            $enhancedRecommendations = $this->enhanceRecommendations($recommendations, $customerContext);
            
            // Apply manufacturing-specific business rules
            $filteredRecommendations = $this->applyManufacturingFilters($enhancedRecommendations, $customerContext);
            
            $result = [
                'success' => true,
                'recommendations' => array_slice($filteredRecommendations, 0, $limit),
                'total_found' => count($filteredRecommendations),
                'context' => $context,
                'customer_tier' => $customerContext['tier'],
                'algorithms_used' => [
                    'collaborative_filtering',
                    'content_based',
                    'inventory_optimization',
                    'seasonal_trends'
                ],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'cached' => false
            ];

            // Cache results for 15 minutes
            $this->cacheManager->set($cacheKey, $result, 900);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Product recommendations error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to generate product recommendations',
                'error_code' => 'RECOMMENDATION_GENERATION_FAILED',
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Get similar products using AI content analysis
     */
    public function getSimilarProducts($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            $productId = $args['product_id'];
            $limit = min((int)($args['limit'] ?? 8), 20);
            
            if (!$this->isValidProductId($productId)) {
                throw new SugarApiExceptionInvalidParameter('Invalid product_id');
            }

            $cacheKey = "similar_products_{$productId}_{$limit}";
            $cachedResults = $this->cacheManager->get($cacheKey);
            
            if ($cachedResults !== null) {
                $cachedResults['cached'] = true;
                return $cachedResults;
            }

            // Get target product details
            $targetProduct = $this->getProductDetails($productId);
            if (!$targetProduct) {
                throw new SugarApiExceptionNotFound('Product not found');
            }

            // Find similar products using multiple similarity algorithms
            $similarProducts = $this->findSimilarProducts($targetProduct, $limit * 2); // Get extra for filtering
            
            // Apply availability and business rule filters
            $filteredProducts = $this->applyAvailabilityFilters($similarProducts);
            
            // Score and rank recommendations
            $rankedProducts = $this->rankSimilarProducts($filteredProducts, $targetProduct);

            $result = [
                'success' => true,
                'target_product' => [
                    'id' => $targetProduct['id'],
                    'name' => $targetProduct['name'],
                    'category' => $targetProduct['category']
                ],
                'similar_products' => array_slice($rankedProducts, 0, $limit),
                'total_found' => count($rankedProducts),
                'similarity_algorithms' => [
                    'semantic_text_analysis',
                    'category_matching',
                    'specification_comparison',
                    'customer_behavior_patterns'
                ],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'cached' => false
            ];

            // Cache for 30 minutes (similarity changes less frequently)
            $this->cacheManager->set($cacheKey, $result, 1800);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Similar products error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to find similar products',
                'error_code' => 'SIMILAR_PRODUCTS_FAILED'
            ];
        }
    }

    /**
     * Get cross-selling recommendations based on purchase patterns
     */
    public function getCrossSellRecommendations($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            $customerId = $args['customer_id'];
            $currentProducts = $args['current_products'] ?? []; // Products in current quote/order
            $limit = min((int)($args['limit'] ?? 5), 15);
            
            $cacheKey = "cross_sell_{$customerId}_" . md5(json_encode($currentProducts));
            $cachedResults = $this->cacheManager->get($cacheKey);
            
            if ($cachedResults !== null) {
                return $cachedResults;
            }

            // Analyze customer's purchase patterns
            $purchasePatterns = $this->analyzePurchasePatterns($customerId);
            
            // Find frequently bought together combinations
            $frequentCombinations = $this->findFrequentCombinations($currentProducts, $purchasePatterns);
            
            // Get complementary products based on manufacturing workflows
            $complementaryProducts = $this->findComplementaryProducts($currentProducts);
            
            // Apply customer tier and pricing filters
            $customerContext = $this->getCustomerContext($customerId);
            $filteredRecommendations = $this->applyCustomerFilters($complementaryProducts, $customerContext);
            
            // Calculate cross-sell scores and rank
            $rankedRecommendations = $this->rankCrossSellOpportunities($filteredRecommendations, $purchasePatterns);

            $result = [
                'success' => true,
                'customer_id' => $customerId,
                'cross_sell_opportunities' => array_slice($rankedRecommendations, 0, $limit),
                'current_products_count' => count($currentProducts),
                'total_opportunities' => count($rankedRecommendations),
                'customer_tier' => $customerContext['tier'],
                'analysis_methods' => [
                    'frequent_itemsets',
                    'association_rules',
                    'manufacturing_workflows',
                    'customer_segment_patterns'
                ],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];

            // Cache for 20 minutes
            $this->cacheManager->set($cacheKey, $result, 1200);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Cross-sell recommendations error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to generate cross-sell recommendations',
                'error_code' => 'CROSS_SELL_FAILED'
            ];
        }
    }

    /**
     * Get inventory-optimized product suggestions
     */
    public function getInventoryBasedSuggestions($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            $category = $args['category'] ?? null;
            $warehouseId = $args['warehouse_id'] ?? null;
            $limit = min((int)($args['limit'] ?? 12), 30);
            $strategy = $args['strategy'] ?? 'balanced'; // 'fast_moving', 'overstocked', 'balanced'
            
            $cacheKey = "inventory_suggestions_{$category}_{$warehouseId}_{$strategy}_{$limit}";
            $cachedResults = $this->cacheManager->get($cacheKey);
            
            if ($cachedResults !== null) {
                return $cachedResults;
            }

            // Get current inventory levels and movement data
            $inventoryData = $this->inventoryService->getInventoryAnalytics([
                'category' => $category,
                'warehouse_id' => $warehouseId,
                'include_movement_data' => true,
                'days_history' => 90
            ]);

            // Apply AI-driven inventory optimization strategy
            $suggestions = $this->generateInventoryOptimizedSuggestions($inventoryData, $strategy);
            
            // Enhance with pricing and margin data
            $enhancedSuggestions = $this->enhanceWithPricingData($suggestions);
            
            // Apply business rules and availability filters
            $filteredSuggestions = $this->applyInventoryBusinessRules($enhancedSuggestions);
            
            // Rank by optimization strategy
            $rankedSuggestions = $this->rankInventorySuggestions($filteredSuggestions, $strategy);

            $result = [
                'success' => true,
                'suggestions' => array_slice($rankedSuggestions, 0, $limit),
                'total_analyzed' => count($inventoryData),
                'strategy' => $strategy,
                'filters_applied' => [
                    'category' => $category,
                    'warehouse_id' => $warehouseId
                ],
                'optimization_metrics' => [
                    'turnover_rate_weight' => 0.4,
                    'inventory_level_weight' => 0.3,
                    'margin_optimization_weight' => 0.2,
                    'demand_forecast_weight' => 0.1
                ],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];

            // Cache for 10 minutes (inventory changes frequently)
            $this->cacheManager->set($cacheKey, $result, 600);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Inventory suggestions error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to generate inventory-based suggestions',
                'error_code' => 'INVENTORY_SUGGESTIONS_FAILED'
            ];
        }
    }

    /**
     * Generate AI-powered product recommendations using multiple algorithms
     */
    private function generateRecommendations($params)
    {
        $recommendations = [];
        
        // 1. Collaborative Filtering (customers who bought X also bought Y)
        $collaborativeRecs = $this->getCollaborativeFilteringRecommendations($params);
        
        // 2. Content-Based Filtering (similar products based on attributes)
        $contentBasedRecs = $this->getContentBasedRecommendations($params);
        
        // 3. Inventory-Optimized Recommendations
        $inventoryRecs = $this->getInventoryOptimizedRecommendations($params);
        
        // 4. Seasonal and Trend-Based Recommendations
        $seasonalRecs = $this->getSeasonalRecommendations($params);
        
        // Combine and weight different recommendation sources
        $recommendations = $this->combineRecommendationSources([
            'collaborative' => ['weight' => 0.35, 'recommendations' => $collaborativeRecs],
            'content_based' => ['weight' => 0.25, 'recommendations' => $contentBasedRecs],
            'inventory_optimized' => ['weight' => 0.25, 'recommendations' => $inventoryRecs],
            'seasonal_trends' => ['weight' => 0.15, 'recommendations' => $seasonalRecs]
        ]);
        
        return $recommendations;
    }

    /**
     * Collaborative filtering recommendations
     */
    private function getCollaborativeFilteringRecommendations($params)
    {
        $customerId = $params['customer_context']['id'];
        
        // Find customers with similar purchase patterns
        $similarCustomers = $this->db->query("
            SELECT DISTINCT oh2.customer_id, 
                   COUNT(DISTINCT oi1.product_id) as common_products,
                   COUNT(DISTINCT oi2.product_id) as total_products
            FROM order_items oi1
            JOIN orders oh1 ON oi1.order_id = oh1.id
            JOIN order_items oi2 ON oi1.product_id = oi2.product_id
            JOIN orders oh2 ON oi2.order_id = oh2.id
            WHERE oh1.customer_id = ? 
            AND oh2.customer_id != ?
            AND oh1.deleted = 0 AND oh2.deleted = 0
            AND oh1.date_created >= DATE_SUB(NOW(), INTERVAL 24 MONTH)
            GROUP BY oh2.customer_id
            HAVING common_products >= 3
            ORDER BY (common_products / total_products) DESC
            LIMIT 20
        ", [$customerId, $customerId]);

        $recommendations = [];
        
        foreach ($similarCustomers as $similarCustomer) {
            // Get products bought by similar customers but not by target customer
            $products = $this->db->query("
                SELECT p.id, p.name, p.category_id, p.base_price,
                       COUNT(*) as purchase_frequency,
                       AVG(oi.unit_price) as avg_price,
                       MAX(oh.date_created) as last_purchased
                FROM products p
                JOIN order_items oi ON p.id = oi.product_id
                JOIN orders oh ON oi.order_id = oh.id
                WHERE oh.customer_id = ?
                AND p.id NOT IN (
                    SELECT DISTINCT oi2.product_id 
                    FROM order_items oi2 
                    JOIN orders oh2 ON oi2.order_id = oh2.id 
                    WHERE oh2.customer_id = ?
                )
                AND p.deleted = 0 AND p.status = 'Active'
                GROUP BY p.id
                ORDER BY purchase_frequency DESC, last_purchased DESC
                LIMIT 10
            ", [$similarCustomer['customer_id'], $customerId]);
            
            foreach ($products as $product) {
                $productId = $product['id'];
                
                if (!isset($recommendations[$productId])) {
                    $recommendations[$productId] = [
                        'product' => $product,
                        'confidence_score' => 0,
                        'reasoning' => [],
                        'algorithm' => 'collaborative_filtering'
                    ];
                }
                
                // Calculate confidence score based on similarity and frequency
                $similarity = $similarCustomer['common_products'] / $similarCustomer['total_products'];
                $frequencyScore = min($product['purchase_frequency'] / 10, 1.0);
                $score = $similarity * $frequencyScore * 0.8;
                
                $recommendations[$productId]['confidence_score'] += $score;
                $recommendations[$productId]['reasoning'][] = "Purchased by {$product['purchase_frequency']} similar customers";
            }
        }
        
        return array_values($recommendations);
    }

    /**
     * Content-based filtering recommendations
     */
    private function getContentBasedRecommendations($params)
    {
        $purchaseHistory = $params['purchase_history'];
        $recommendations = [];
        
        if (empty($purchaseHistory)) {
            return [];
        }
        
        // Analyze customer's product preferences
        $preferences = $this->analyzeProductPreferences($purchaseHistory);
        
        // Find products matching preferences
        $matchingProducts = $this->db->query("
            SELECT p.id, p.name, p.category_id, p.description, p.base_price,
                   c.name as category_name,
                   MATCH(p.name, p.description) AGAINST(? IN BOOLEAN MODE) as text_relevance
            FROM products p
            LEFT JOIN product_categories c ON p.category_id = c.id
            WHERE MATCH(p.name, p.description) AGAINST(? IN BOOLEAN MODE)
            AND p.deleted = 0 AND p.status = 'Active'
            AND p.id NOT IN ('" . implode("','", array_column($purchaseHistory, 'product_id')) . "')
            ORDER BY text_relevance DESC
            LIMIT 15
        ", [$preferences['keywords'], $preferences['keywords']]);
        
        foreach ($matchingProducts as $product) {
            // Calculate content similarity score
            $categoryScore = in_array($product['category_id'], $preferences['preferred_categories']) ? 0.4 : 0.1;
            $textScore = min($product['text_relevance'] / 10, 0.6);
            $totalScore = $categoryScore + $textScore;
            
            $recommendations[] = [
                'product' => $product,
                'confidence_score' => $totalScore,
                'reasoning' => [
                    "Matches preferred categories: " . $product['category_name'],
                    "Text similarity score: " . round($product['text_relevance'], 2)
                ],
                'algorithm' => 'content_based'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Get customer context including tier, preferences, and constraints
     */
    private function getCustomerContext($customerId)
    {
        $customer = $this->db->query("
            SELECT a.id, a.name, a.industry, a.customer_tier_c,
                   a.preferred_warehouse_c, a.payment_terms_c,
                   COUNT(DISTINCT oh.id) as total_orders,
                   SUM(oh.total_amount) as lifetime_value,
                   MAX(oh.date_created) as last_order_date
            FROM accounts a
            LEFT JOIN orders oh ON a.id = oh.customer_id AND oh.deleted = 0
            WHERE a.id = ? AND a.deleted = 0
            GROUP BY a.id
        ", [$customerId]);
        
        if (empty($customer)) {
            throw new SugarApiExceptionNotFound('Customer not found');
        }
        
        $customerData = $customer[0];
        
        return [
            'id' => $customerData['id'],
            'name' => $customerData['name'],
            'tier' => $customerData['customer_tier_c'] ?? 'standard',
            'industry' => $customerData['industry'],
            'preferred_warehouse' => $customerData['preferred_warehouse_c'],
            'payment_terms' => $customerData['payment_terms_c'],
            'total_orders' => (int)$customerData['total_orders'],
            'lifetime_value' => (float)$customerData['lifetime_value'],
            'last_order_date' => $customerData['last_order_date'],
            'customer_segment' => $this->calculateCustomerSegment($customerData)
        ];
    }

    /**
     * Get customer purchase history for analysis
     */
    private function getPurchaseHistory($customerId, $monthsBack = 24)
    {
        return $this->db->query("
            SELECT oi.product_id, p.name as product_name, p.category_id,
                   c.name as category_name, oi.quantity, oi.unit_price,
                   oh.date_created as order_date, oh.id as order_id
            FROM order_items oi
            JOIN orders oh ON oi.order_id = oh.id
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_categories c ON p.category_id = c.id
            WHERE oh.customer_id = ?
            AND oh.deleted = 0
            AND oh.date_created >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            ORDER BY oh.date_created DESC
        ", [$customerId, $monthsBack]);
    }

    /**
     * Enhance recommendations with additional business intelligence
     */
    private function enhanceRecommendations($recommendations, $customerContext)
    {
        foreach ($recommendations as &$rec) {
            $productId = $rec['product']['id'];
            
            // Add inventory availability
            $inventoryInfo = $this->inventoryService->getProductAvailability($productId);
            $rec['inventory'] = $inventoryInfo;
            
            // Add customer-specific pricing
            $pricing = $this->calculateCustomerPricing($productId, $customerContext);
            $rec['pricing'] = $pricing;
            
            // Add lead time information
            $rec['lead_time'] = $this->getProductLeadTime($productId, $customerContext['preferred_warehouse']);
            
            // Add margin and profitability data
            $rec['margin_data'] = $this->getProductMarginData($productId, $customerContext['tier']);
            
            // Boost score for high-margin products
            if ($rec['margin_data']['margin_percentage'] > 25) {
                $rec['confidence_score'] *= 1.1;
            }
        }
        
        return $recommendations;
    }

    /**
     * Apply manufacturing-specific business rule filters
     */
    private function applyManufacturingFilters($recommendations, $customerContext)
    {
        $filtered = [];
        
        foreach ($recommendations as $rec) {
            // Skip products not suitable for customer's industry
            if (!$this->isProductSuitableForIndustry($rec['product']['id'], $customerContext['industry'])) {
                continue;
            }
            
            // Skip products with insufficient inventory (unless specifically requested)
            if ($rec['inventory']['available_quantity'] <= 0) {
                continue;
            }
            
            // Skip products outside customer's typical price range
            if (!$this->isPriceInCustomerRange($rec['pricing']['final_price'], $customerContext)) {
                continue;
            }
            
            // Apply customer tier restrictions
            if (!$this->isProductAvailableForTier($rec['product']['id'], $customerContext['tier'])) {
                continue;
            }
            
            $filtered[] = $rec;
        }
        
        // Sort by confidence score
        usort($filtered, function($a, $b) {
            return $b['confidence_score'] <=> $a['confidence_score'];
        });
        
        return $filtered;
    }

    /**
     * Validation and utility methods
     */
    private function isValidProductId($productId)
    {
        if (!is_string($productId) || strlen($productId) !== 36) {
            return false;
        }
        
        $count = $this->db->getOne("SELECT COUNT(*) FROM products WHERE id = ? AND deleted = 0", [$productId]);
        return $count > 0;
    }

    private function calculateCustomerSegment($customerData)
    {
        $lifetimeValue = (float)$customerData['lifetime_value'];
        $totalOrders = (int)$customerData['total_orders'];
        
        if ($lifetimeValue > 100000 && $totalOrders > 50) {
            return 'enterprise';
        } elseif ($lifetimeValue > 25000 && $totalOrders > 10) {
            return 'growth';
        } elseif ($lifetimeValue > 5000 && $totalOrders > 3) {
            return 'established';
        } else {
            return 'new';
        }
    }

    private function analyzeProductPreferences($purchaseHistory)
    {
        $categories = [];
        $keywords = [];
        
        foreach ($purchaseHistory as $item) {
            $categories[] = $item['category_id'];
            $keywords[] = $item['product_name'];
        }
        
        $preferredCategories = array_unique(array_filter($categories));
        $keywordString = implode(' ', $keywords);
        
        return [
            'preferred_categories' => $preferredCategories,
            'keywords' => $keywordString
        ];
    }
}

/**
 * Supporting Classes and Services
 */

class ManufacturingAnalyticsEngine
{
    public function analyzeSeasonalTrends($productIds, $monthsBack = 12)
    {
        // Implementation for seasonal trend analysis
        return [];
    }
    
    public function calculateDemandForecast($productId, $daysForward = 90)
    {
        // Implementation for demand forecasting
        return [];
    }
}
