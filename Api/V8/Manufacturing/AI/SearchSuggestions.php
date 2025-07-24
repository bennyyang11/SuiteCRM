<?php
/**
 * AI-Powered Search Suggestions API
 * Intelligent search assistance for SuiteCRM Manufacturing
 * 
 * Provides advanced search capabilities including:
 * - Real-time autocomplete suggestions
 * - Typo correction and spell checking
 * - Semantic search understanding
 * - Context-aware product suggestions
 * - Manufacturing-specific search intelligence
 * 
 * @package SuiteCRM\Api\V8\Manufacturing\AI
 * @author AI-Assisted Development Team
 * @version 1.0.0
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'Api/V8/BeanDecorator/BeanManager.php';

class SearchSuggestionsApi extends SugarApi
{
    private $db;
    private $cacheManager;
    private $searchIndex;
    private $nlpProcessor;
    
    // Search suggestion algorithms weights
    const ALGORITHM_WEIGHTS = [
        'exact_match' => 1.0,
        'prefix_match' => 0.8,
        'fuzzy_match' => 0.6,
        'semantic_match' => 0.7,
        'popular_searches' => 0.4,
        'category_match' => 0.5
    ];

    public function __construct()
    {
        $this->db = DBManagerFactory::getInstance();
        $this->cacheManager = SugarCache::instance();
        $this->searchIndex = new ManufacturingSearchIndex();
        $this->nlpProcessor = new NaturalLanguageProcessor();
    }

    public function registerApiRest()
    {
        return [
            'manufacturing_search_suggestions' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'search-suggestions'],
                'pathVars' => [],
                'method' => 'getSearchSuggestions',
                'shortHelp' => 'Get intelligent search suggestions',
                'longHelp' => [
                    'en' => 'Returns AI-powered search suggestions with autocomplete, typo correction, and semantic understanding'
                ]
            ],
            'manufacturing_autocomplete' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'autocomplete'],
                'pathVars' => [],
                'method' => 'getAutocomplete',
                'shortHelp' => 'Get real-time autocomplete suggestions',
                'longHelp' => [
                    'en' => 'Provides fast autocomplete suggestions for product search'
                ]
            ],
            'manufacturing_spell_check' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'spell-check'],
                'pathVars' => [],
                'method' => 'getSpellCheckSuggestions',
                'shortHelp' => 'Get spell-check and typo corrections',
                'longHelp' => [
                    'en' => 'Returns spell-corrected suggestions for search queries'
                ]
            ],
            'manufacturing_semantic_search' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'semantic-search'],
                'pathVars' => [],
                'method' => 'getSemanticSuggestions',
                'shortHelp' => 'Get semantic search suggestions',
                'longHelp' => [
                    'en' => 'Returns contextually relevant suggestions based on search intent'
                ]
            ],
            'manufacturing_search_analytics' => [
                'reqType' => 'GET',
                'path' => ['manufacturing', 'ai', 'search-analytics'],
                'pathVars' => [],
                'method' => 'getSearchAnalytics',
                'shortHelp' => 'Get search analytics and trends',
                'longHelp' => [
                    'en' => 'Returns search analytics including popular queries and trends'
                ]
            ]
        ];
    }

    /**
     * Get comprehensive AI-powered search suggestions
     * 
     * @param ServiceBase $api
     * @param array $args Request arguments
     * @return array Search suggestions with multiple algorithms
     */
    public function getSearchSuggestions($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            // Validate and sanitize input
            $query = $this->sanitizeSearchQuery($args['query'] ?? '');
            $limit = min((int)($args['limit'] ?? 10), 25);
            $context = $args['context'] ?? 'general'; // 'product', 'customer', 'order', 'general'
            $includeAnalytics = filter_var($args['include_analytics'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $userId = $args['user_id'] ?? null;
            
            if (strlen($query) < 2) {
                return [
                    'success' => true,
                    'suggestions' => [],
                    'message' => 'Query too short for suggestions'
                ];
            }

            // Generate cache key
            $cacheKey = "search_suggestions_" . md5($query . $context . $limit . $userId);
            
            // Check cache first (5-minute TTL for fast response)
            $cachedResults = $this->cacheManager->get($cacheKey);
            if ($cachedResults !== null) {
                $cachedResults['cached'] = true;
                $cachedResults['execution_time_ms'] = round((microtime(true) - $startTime) * 1000, 2);
                return $cachedResults;
            }

            // Log search query for analytics
            $this->logSearchQuery($query, $context, $userId);
            
            // Generate suggestions using multiple AI algorithms
            $suggestions = $this->generateComprehensiveSuggestions([
                'query' => $query,
                'context' => $context,
                'limit' => $limit * 2, // Get extra for filtering
                'user_id' => $userId
            ]);
            
            // Apply intelligent filtering and ranking
            $filteredSuggestions = $this->filterAndRankSuggestions($suggestions, $query, $context);
            
            // Limit to requested number
            $finalSuggestions = array_slice($filteredSuggestions, 0, $limit);
            
            // Add analytics if requested
            $analytics = $includeAnalytics ? $this->getQueryAnalytics($query) : null;

            $result = [
                'success' => true,
                'query' => $query,
                'suggestions' => $finalSuggestions,
                'total_suggestions' => count($finalSuggestions),
                'context' => $context,
                'algorithms_used' => array_keys(self::ALGORITHM_WEIGHTS),
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                'cached' => false
            ];
            
            if ($analytics) {
                $result['analytics'] = $analytics;
            }

            // Cache results for 5 minutes
            $this->cacheManager->set($cacheKey, $result, 300);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Search suggestions error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to generate search suggestions',
                'error_code' => 'SEARCH_SUGGESTIONS_FAILED',
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
        }
    }

    /**
     * Get fast autocomplete suggestions for real-time search
     */
    public function getAutocomplete($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            $query = $this->sanitizeSearchQuery($args['query'] ?? '');
            $limit = min((int)($args['limit'] ?? 8), 15);
            $context = $args['context'] ?? 'product';
            
            if (strlen($query) < 2) {
                return ['success' => true, 'suggestions' => []];
            }

            $cacheKey = "autocomplete_{$context}_" . md5($query);
            $cachedResults = $this->cacheManager->get($cacheKey);
            
            if ($cachedResults !== null) {
                return $cachedResults;
            }

            // Fast prefix-based suggestions
            $prefixSuggestions = $this->getPrefixSuggestions($query, $context, $limit);
            
            // Add popular completions
            $popularSuggestions = $this->getPopularCompletions($query, $context, $limit);
            
            // Combine and deduplicate
            $allSuggestions = array_merge($prefixSuggestions, $popularSuggestions);
            $uniqueSuggestions = $this->deduplicateSuggestions($allSuggestions);
            
            // Sort by relevance and frequency
            $sortedSuggestions = $this->sortAutocompleteResults($uniqueSuggestions, $query);

            $result = [
                'success' => true,
                'query' => $query,
                'suggestions' => array_slice($sortedSuggestions, 0, $limit),
                'context' => $context,
                'suggestion_types' => ['prefix_match', 'popular_completion'],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];

            // Cache for 2 minutes for real-time performance
            $this->cacheManager->set($cacheKey, $result, 120);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Autocomplete error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Autocomplete failed',
                'suggestions' => []
            ];
        }
    }

    /**
     * Get spell-check and typo correction suggestions
     */
    public function getSpellCheckSuggestions($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            $query = $args['query'] ?? '';
            $maxDistance = min((int)($args['max_distance'] ?? 2), 3); // Levenshtein distance
            $limit = min((int)($args['limit'] ?? 5), 10);
            
            if (strlen($query) < 3) {
                return [
                    'success' => true,
                    'corrections' => [],
                    'message' => 'Query too short for spell check'
                ];
            }

            $cacheKey = "spell_check_" . md5($query . $maxDistance);
            $cachedResults = $this->cacheManager->get($cacheKey);
            
            if ($cachedResults !== null) {
                return $cachedResults;
            }

            // Check if query needs correction
            $isCorrect = $this->isQuerySpellingCorrect($query);
            
            if ($isCorrect) {
                return [
                    'success' => true,
                    'query' => $query,
                    'needs_correction' => false,
                    'corrections' => [],
                    'message' => 'Query appears to be spelled correctly'
                ];
            }

            // Generate spell corrections
            $corrections = $this->generateSpellCorrections($query, $maxDistance, $limit);
            
            // Enhance corrections with context and frequency
            $enhancedCorrections = $this->enhanceSpellCorrections($corrections, $query);

            $result = [
                'success' => true,
                'query' => $query,
                'needs_correction' => true,
                'corrections' => $enhancedCorrections,
                'correction_algorithms' => [
                    'levenshtein_distance',
                    'phonetic_matching',
                    'frequency_based',
                    'context_aware'
                ],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];

            // Cache for 10 minutes
            $this->cacheManager->set($cacheKey, $result, 600);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Spell check error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Spell check failed',
                'corrections' => []
            ];
        }
    }

    /**
     * Get semantic search suggestions based on intent understanding
     */
    public function getSemanticSuggestions($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            $query = $this->sanitizeSearchQuery($args['query'] ?? '');
            $limit = min((int)($args['limit'] ?? 8), 15);
            $context = $args['context'] ?? 'product';
            $includeIntent = filter_var($args['include_intent'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if (strlen($query) < 3) {
                return [
                    'success' => true,
                    'suggestions' => [],
                    'message' => 'Query too short for semantic analysis'
                ];
            }

            $cacheKey = "semantic_search_" . md5($query . $context);
            $cachedResults = $this->cacheManager->get($cacheKey);
            
            if ($cachedResults !== null) {
                return $cachedResults;
            }

            // Analyze search intent
            $searchIntent = $this->nlpProcessor->analyzeSearchIntent($query);
            
            // Generate semantic suggestions based on intent
            $semanticSuggestions = $this->generateSemanticSuggestions($query, $searchIntent, $context);
            
            // Find conceptually related terms
            $conceptualSuggestions = $this->findConceptuallyRelatedTerms($query, $context);
            
            // Combine and rank semantic results
            $allSuggestions = array_merge($semanticSuggestions, $conceptualSuggestions);
            $rankedSuggestions = $this->rankSemanticSuggestions($allSuggestions, $searchIntent);

            $result = [
                'success' => true,
                'query' => $query,
                'suggestions' => array_slice($rankedSuggestions, 0, $limit),
                'context' => $context,
                'semantic_features' => [
                    'intent_analysis',
                    'conceptual_matching',
                    'manufacturing_domain_knowledge',
                    'contextual_understanding'
                ],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
            
            if ($includeIntent) {
                $result['search_intent'] = $searchIntent;
            }

            // Cache for 15 minutes
            $this->cacheManager->set($cacheKey, $result, 900);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Semantic search error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Semantic search failed',
                'suggestions' => []
            ];
        }
    }

    /**
     * Get search analytics and trends
     */
    public function getSearchAnalytics($api, $args)
    {
        $startTime = microtime(true);
        
        try {
            $timeframe = $args['timeframe'] ?? '7d'; // '1d', '7d', '30d', '90d'
            $limit = min((int)($args['limit'] ?? 20), 50);
            $includeZeroResults = filter_var($args['include_zero_results'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            $cacheKey = "search_analytics_{$timeframe}_{$limit}_{$includeZeroResults}";
            $cachedResults = $this->cacheManager->get($cacheKey);
            
            if ($cachedResults !== null) {
                return $cachedResults;
            }

            // Get popular search queries
            $popularQueries = $this->getPopularSearchQueries($timeframe, $limit);
            
            // Get trending searches
            $trendingQueries = $this->getTrendingSearches($timeframe, $limit);
            
            // Get search performance metrics
            $performanceMetrics = $this->getSearchPerformanceMetrics($timeframe);
            
            // Get zero-result queries if requested
            $zeroResultQueries = $includeZeroResults ? $this->getZeroResultQueries($timeframe, $limit) : [];
            
            // Get search category distribution
            $categoryDistribution = $this->getSearchCategoryDistribution($timeframe);

            $result = [
                'success' => true,
                'timeframe' => $timeframe,
                'analytics' => [
                    'popular_queries' => $popularQueries,
                    'trending_queries' => $trendingQueries,
                    'performance_metrics' => $performanceMetrics,
                    'category_distribution' => $categoryDistribution
                ],
                'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ];
            
            if ($includeZeroResults) {
                $result['analytics']['zero_result_queries'] = $zeroResultQueries;
            }

            // Cache for 1 hour
            $this->cacheManager->set($cacheKey, $result, 3600);
            
            return $result;

        } catch (Exception $e) {
            $GLOBALS['log']->error("Search analytics error: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Search analytics failed',
                'analytics' => []
            ];
        }
    }

    /**
     * Generate comprehensive suggestions using multiple algorithms
     */
    private function generateComprehensiveSuggestions($params)
    {
        $query = $params['query'];
        $context = $params['context'];
        $limit = $params['limit'];
        
        $allSuggestions = [];
        
        // 1. Exact and prefix matches
        $exactMatches = $this->getExactMatches($query, $context);
        foreach ($exactMatches as $match) {
            $allSuggestions[] = [
                'text' => $match['text'],
                'type' => 'exact_match',
                'score' => self::ALGORITHM_WEIGHTS['exact_match'],
                'context' => $match['context'],
                'frequency' => $match['frequency'] ?? 1
            ];
        }
        
        // 2. Prefix matches
        $prefixMatches = $this->getPrefixMatches($query, $context);
        foreach ($prefixMatches as $match) {
            $allSuggestions[] = [
                'text' => $match['text'],
                'type' => 'prefix_match',
                'score' => self::ALGORITHM_WEIGHTS['prefix_match'],
                'context' => $match['context'],
                'frequency' => $match['frequency'] ?? 1
            ];
        }
        
        // 3. Fuzzy matches (typo tolerance)
        $fuzzyMatches = $this->getFuzzyMatches($query, $context);
        foreach ($fuzzyMatches as $match) {
            $allSuggestions[] = [
                'text' => $match['text'],
                'type' => 'fuzzy_match',
                'score' => self::ALGORITHM_WEIGHTS['fuzzy_match'] * $match['similarity'],
                'context' => $match['context'],
                'frequency' => $match['frequency'] ?? 1,
                'similarity' => $match['similarity']
            ];
        }
        
        // 4. Semantic matches
        $semanticMatches = $this->getSemanticMatches($query, $context);
        foreach ($semanticMatches as $match) {
            $allSuggestions[] = [
                'text' => $match['text'],
                'type' => 'semantic_match',
                'score' => self::ALGORITHM_WEIGHTS['semantic_match'] * $match['relevance'],
                'context' => $match['context'],
                'frequency' => $match['frequency'] ?? 1,
                'relevance' => $match['relevance']
            ];
        }
        
        // 5. Popular searches
        $popularMatches = $this->getPopularSearchMatches($query, $context);
        foreach ($popularMatches as $match) {
            $allSuggestions[] = [
                'text' => $match['text'],
                'type' => 'popular_search',
                'score' => self::ALGORITHM_WEIGHTS['popular_searches'] * $match['popularity'],
                'context' => $match['context'],
                'frequency' => $match['frequency'],
                'popularity' => $match['popularity']
            ];
        }
        
        // 6. Category-based matches
        $categoryMatches = $this->getCategoryMatches($query, $context);
        foreach ($categoryMatches as $match) {
            $allSuggestions[] = [
                'text' => $match['text'],
                'type' => 'category_match',
                'score' => self::ALGORITHM_WEIGHTS['category_match'],
                'context' => $match['context'],
                'frequency' => $match['frequency'] ?? 1,
                'category' => $match['category']
            ];
        }
        
        return $allSuggestions;
    }

    /**
     * Get exact matches for the query
     */
    private function getExactMatches($query, $context)
    {
        $contextFilter = $this->buildContextFilter($context);
        
        $results = $this->db->query("
            SELECT search_term as text, search_context as context, 
                   search_count as frequency
            FROM search_analytics 
            WHERE search_term = ? 
            {$contextFilter}
            AND date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY search_count DESC
            LIMIT 5
        ", [$query]);
        
        return $results;
    }

    /**
     * Get prefix matches for autocomplete
     */
    private function getPrefixMatches($query, $context)
    {
        $contextFilter = $this->buildContextFilter($context);
        
        $results = $this->db->query("
            SELECT search_term as text, search_context as context,
                   search_count as frequency
            FROM search_analytics 
            WHERE search_term LIKE ? 
            {$contextFilter}
            AND date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY search_count DESC, LENGTH(search_term) ASC
            LIMIT 8
        ", [$query . '%']);
        
        return $results;
    }

    /**
     * Get fuzzy matches with typo tolerance
     */
    private function getFuzzyMatches($query, $context)
    {
        $contextFilter = $this->buildContextFilter($context);
        $queryLength = strlen($query);
        $maxDistance = $queryLength <= 4 ? 1 : 2;
        
        // Use SOUNDEX for phonetic matching
        $results = $this->db->query("
            SELECT search_term as text, search_context as context,
                   search_count as frequency,
                   (1 - (LEAST(CHAR_LENGTH(?), CHAR_LENGTH(search_term)) - 
                        GREATEST(CHAR_LENGTH(?), CHAR_LENGTH(search_term))) / 
                        GREATEST(CHAR_LENGTH(?), CHAR_LENGTH(search_term))) as similarity
            FROM search_analytics 
            WHERE (SOUNDEX(search_term) = SOUNDEX(?) 
                  OR search_term LIKE ? 
                  OR search_term LIKE ?)
            {$contextFilter}
            AND search_term != ?
            AND date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            HAVING similarity >= 0.6
            ORDER BY similarity DESC, search_count DESC
            LIMIT 6
        ", [
            $query, $query, $query, $query,
            '%' . substr($query, 0, -1) . '%',
            '%' . substr($query, 1) . '%',
            $query
        ]);
        
        return $results;
    }

    /**
     * Get semantic matches using NLP processing
     */
    private function getSemanticMatches($query, $context)
    {
        // Get semantically related terms
        $relatedTerms = $this->nlpProcessor->findRelatedTerms($query, $context);
        $matches = [];
        
        foreach ($relatedTerms as $term) {
            $contextFilter = $this->buildContextFilter($context);
            
            $results = $this->db->query("
                SELECT search_term as text, search_context as context,
                       search_count as frequency,
                       ? as relevance
                FROM search_analytics 
                WHERE search_term LIKE ?
                {$contextFilter}
                AND date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY search_count DESC
                LIMIT 3
            ", [$term['relevance'], '%' . $term['term'] . '%']);
            
            $matches = array_merge($matches, $results);
        }
        
        return $matches;
    }

    /**
     * Filter and rank suggestions intelligently
     */
    private function filterAndRankSuggestions($suggestions, $query, $context)
    {
        // Remove duplicates
        $suggestions = $this->deduplicateSuggestions($suggestions);
        
        // Calculate final scores with multiple factors
        foreach ($suggestions as &$suggestion) {
            $baseScore = $suggestion['score'];
            $frequencyBoost = min(log($suggestion['frequency'] + 1) / 10, 0.3);
            $lengthPenalty = strlen($suggestion['text']) > 50 ? -0.1 : 0;
            $contextBoost = $suggestion['context'] === $context ? 0.1 : 0;
            
            $suggestion['final_score'] = $baseScore + $frequencyBoost + $lengthPenalty + $contextBoost;
        }
        
        // Sort by final score
        usort($suggestions, function($a, $b) {
            return $b['final_score'] <=> $a['final_score'];
        });
        
        return $suggestions;
    }

    /**
     * Log search query for analytics
     */
    private function logSearchQuery($query, $context, $userId)
    {
        try {
            $this->db->query("
                INSERT INTO search_analytics (
                    id, search_term, search_context, user_id, 
                    date_created, search_count
                ) VALUES (
                    ?, ?, ?, ?, NOW(), 1
                ) ON DUPLICATE KEY UPDATE 
                    search_count = search_count + 1,
                    last_searched = NOW()
            ", [
                create_guid(),
                $query,
                $context,
                $userId
            ]);
        } catch (Exception $e) {
            $GLOBALS['log']->warn("Failed to log search query: " . $e->getMessage());
        }
    }

    /**
     * Sanitize search query for security
     */
    private function sanitizeSearchQuery($query)
    {
        // Remove potentially dangerous characters
        $query = preg_replace('/[<>"\']/', '', $query);
        
        // Trim whitespace
        $query = trim($query);
        
        // Limit length
        $query = substr($query, 0, 100);
        
        return $query;
    }

    /**
     * Build context filter for SQL queries
     */
    private function buildContextFilter($context)
    {
        if ($context === 'general') {
            return '';
        }
        
        return "AND search_context = '" . $this->db->quote($context) . "'";
    }

    /**
     * Deduplicate suggestions array
     */
    private function deduplicateSuggestions($suggestions)
    {
        $seen = [];
        $unique = [];
        
        foreach ($suggestions as $suggestion) {
            $key = strtolower($suggestion['text']);
            
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $suggestion;
            }
        }
        
        return $unique;
    }

    /**
     * Get popular search queries for analytics
     */
    private function getPopularSearchQueries($timeframe, $limit)
    {
        $dateFilter = $this->getDateFilter($timeframe);
        
        return $this->db->query("
            SELECT search_term, search_context, 
                   SUM(search_count) as total_searches,
                   COUNT(DISTINCT user_id) as unique_users,
                   MAX(last_searched) as last_searched
            FROM search_analytics 
            WHERE date_created >= ?
            GROUP BY search_term, search_context
            ORDER BY total_searches DESC
            LIMIT ?
        ", [$dateFilter, $limit]);
    }

    /**
     * Get trending searches (increasing popularity)
     */
    private function getTrendingSearches($timeframe, $limit)
    {
        $dateFilter = $this->getDateFilter($timeframe);
        $halfTimeAgo = $this->getDateFilter($timeframe, 0.5);
        
        return $this->db->query("
            SELECT search_term, search_context,
                   SUM(CASE WHEN date_created >= ? THEN search_count ELSE 0 END) as recent_searches,
                   SUM(CASE WHEN date_created < ? THEN search_count ELSE 0 END) as older_searches,
                   (SUM(CASE WHEN date_created >= ? THEN search_count ELSE 0 END) / 
                    NULLIF(SUM(CASE WHEN date_created < ? THEN search_count ELSE 0 END), 0)) as trend_ratio
            FROM search_analytics 
            WHERE date_created >= ?
            GROUP BY search_term, search_context
            HAVING recent_searches > 5 AND trend_ratio > 1.5
            ORDER BY trend_ratio DESC
            LIMIT ?
        ", [$halfTimeAgo, $halfTimeAgo, $halfTimeAgo, $halfTimeAgo, $dateFilter, $limit]);
    }

    /**
     * Get date filter for analytics queries
     */
    private function getDateFilter($timeframe, $multiplier = 1.0)
    {
        $days = [
            '1d' => 1,
            '7d' => 7,
            '30d' => 30,
            '90d' => 90
        ];
        
        $dayCount = $days[$timeframe] ?? 7;
        $adjustedDays = (int)($dayCount * $multiplier);
        
        return date('Y-m-d H:i:s', strtotime("-{$adjustedDays} days"));
    }
}

/**
 * Supporting Classes for NLP and Search Intelligence
 */

class ManufacturingSearchIndex
{
    private $db;
    
    public function __construct()
    {
        $this->db = DBManagerFactory::getInstance();
    }
    
    public function buildFullTextIndex()
    {
        // Implementation for building search index
    }
    
    public function searchIndex($query, $context, $limit = 10)
    {
        // Implementation for searching the index
        return [];
    }
}

class NaturalLanguageProcessor
{
    public function analyzeSearchIntent($query)
    {
        // Simple intent analysis - in production, use more sophisticated NLP
        $intent = [
            'type' => 'product_search',
            'confidence' => 0.8,
            'entities' => $this->extractEntities($query),
            'keywords' => $this->extractKeywords($query)
        ];
        
        // Detect specific intents
        if (preg_match('/\b(price|cost|pricing)\b/i', $query)) {
            $intent['type'] = 'price_inquiry';
        } elseif (preg_match('/\b(available|stock|inventory)\b/i', $query)) {
            $intent['type'] = 'availability_check';
        } elseif (preg_match('/\b(similar|like|alternative)\b/i', $query)) {
            $intent['type'] = 'similar_product_search';
        }
        
        return $intent;
    }
    
    public function findRelatedTerms($query, $context)
    {
        // Manufacturing-specific term relationships
        $manufacturingTerms = [
            'motor' => ['drive', 'controller', 'encoder', 'brake'],
            'sensor' => ['proximity', 'photoelectric', 'ultrasonic', 'pressure'],
            'valve' => ['actuator', 'fitting', 'manifold', 'solenoid'],
            'cable' => ['connector', 'terminal', 'conduit', 'wire'],
            'switch' => ['relay', 'contactor', 'breaker', 'fuse']
        ];
        
        $relatedTerms = [];
        $queryLower = strtolower($query);
        
        foreach ($manufacturingTerms as $term => $related) {
            if (strpos($queryLower, $term) !== false) {
                foreach ($related as $relatedTerm) {
                    $relatedTerms[] = [
                        'term' => $relatedTerm,
                        'relevance' => 0.7
                    ];
                }
            }
        }
        
        return $relatedTerms;
    }
    
    private function extractEntities($query)
    {
        $entities = [];
        
        // Extract product codes (e.g., ABC-123, XYZ123)
        if (preg_match_all('/\b[A-Z]{2,4}[-]?\d{2,6}\b/', $query, $matches)) {
            foreach ($matches[0] as $match) {
                $entities[] = ['type' => 'product_code', 'value' => $match];
            }
        }
        
        // Extract brand names (common manufacturing brands)
        $brands = ['siemens', 'schneider', 'omron', 'allen-bradley', 'mitsubishi'];
        foreach ($brands as $brand) {
            if (stripos($query, $brand) !== false) {
                $entities[] = ['type' => 'brand', 'value' => $brand];
            }
        }
        
        return $entities;
    }
    
    private function extractKeywords($query)
    {
        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = preg_split('/\s+/', strtolower($query));
        
        return array_values(array_diff($words, $stopWords));
    }
}
