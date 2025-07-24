<?php
/**
 * Automated Data Validation with AI-powered Anomaly Detection
 * Enterprise-grade data quality assurance for manufacturing distribution
 * 
 * @package SuiteCRM.Include.AI
 * @author Enterprise AI Development Team  
 * @version 1.0.0
 */

namespace SuiteCRM\AI;

use SuiteCRM\Exception\ApiException;
use SuiteCRM\Utility\SuiteValidator;

class DataValidation 
{
    private $db;
    private $logger;
    private $cacheManager;
    private $validationRules;
    private $mlModel;
    
    public function __construct() {
        global $db, $log;
        $this->db = $db;
        $this->logger = $log;
        $this->cacheManager = new \SuiteCRM\Cache\CacheManager();
        $this->loadValidationRules();
        $this->initializeMLModel();
    }

    /**
     * Comprehensive data validation with AI anomaly detection
     * 
     * @param string $dataType Type of data being validated (product, client, order, etc.)
     * @param array $data Data to validate
     * @param array $context Additional context for validation
     * @return array Validation results with recommendations
     * @throws ApiException
     */
    public function validateData($dataType, $data, $context = []) {
        try {
            $this->logger->info("DataValidation: Starting validation for {$dataType}");
            
            $validationResult = [
                'data_type' => $dataType,
                'validation_id' => $this->generateValidationId(),
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'pending',
                'errors' => [],
                'warnings' => [],
                'suggestions' => [],
                'anomalies' => [],
                'quality_score' => 0,
                'confidence_level' => 0,
                'ai_insights' => []
            ];

            // Step 1: Schema validation
            $schemaValidation = $this->validateSchema($dataType, $data);
            $validationResult['errors'] = array_merge($validationResult['errors'], $schemaValidation['errors']);
            $validationResult['warnings'] = array_merge($validationResult['warnings'], $schemaValidation['warnings']);

            // Step 2: Business rule validation
            $businessValidation = $this->validateBusinessRules($dataType, $data, $context);
            $validationResult['errors'] = array_merge($validationResult['errors'], $businessValidation['errors']);
            $validationResult['warnings'] = array_merge($validationResult['warnings'], $businessValidation['warnings']);

            // Step 3: AI-powered anomaly detection
            $anomalyDetection = $this->detectAnomalies($dataType, $data, $context);
            $validationResult['anomalies'] = $anomalyDetection['anomalies'];
            $validationResult['ai_insights'] = $anomalyDetection['insights'];

            // Step 4: Data quality assessment
            $qualityAssessment = $this->assessDataQuality($dataType, $data, $validationResult);
            $validationResult['quality_score'] = $qualityAssessment['score'];
            $validationResult['confidence_level'] = $qualityAssessment['confidence'];

            // Step 5: Generate intelligent suggestions
            $suggestions = $this->generateIntelligentSuggestions($dataType, $data, $validationResult);
            $validationResult['suggestions'] = $suggestions;

            // Step 6: Determine final status
            $validationResult['status'] = $this->determineValidationStatus($validationResult);

            // Log validation results
            $this->logValidationResults($validationResult);

            // Cache for performance optimization
            $this->cacheValidationResults($validationResult);

            $this->logger->info("DataValidation: Completed with status {$validationResult['status']}, quality score {$validationResult['quality_score']}");
            
            return $validationResult;

        } catch (Exception $e) {
            $this->logger->error("DataValidation Error: " . $e->getMessage());
            throw new ApiException("Data validation failed: " . $e->getMessage(), 500);
        }
    }

    /**
     * Validate data against schema definitions
     */
    private function validateSchema($dataType, $data) {
        $errors = [];
        $warnings = [];
        
        $schema = $this->getSchemaDefinition($dataType);
        
        if (!$schema) {
            $errors[] = [
                'type' => 'schema_error',
                'message' => "No schema definition found for data type: {$dataType}",
                'severity' => 'critical'
            ];
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        // Required field validation
        foreach ($schema['required_fields'] as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $errors[] = [
                    'type' => 'required_field',
                    'field' => $field,
                    'message' => "Required field '{$field}' is missing or empty",
                    'severity' => 'critical'
                ];
            }
        }

        // Data type validation
        foreach ($schema['field_types'] as $field => $expectedType) {
            if (isset($data[$field]) && !$this->validateFieldType($data[$field], $expectedType)) {
                $errors[] = [
                    'type' => 'data_type',
                    'field' => $field,
                    'message' => "Field '{$field}' should be of type {$expectedType}",
                    'current_type' => gettype($data[$field]),
                    'severity' => 'high'
                ];
            }
        }

        // Field constraint validation
        foreach ($schema['field_constraints'] as $field => $constraints) {
            if (isset($data[$field])) {
                $constraintValidation = $this->validateFieldConstraints($field, $data[$field], $constraints);
                $errors = array_merge($errors, $constraintValidation['errors']);
                $warnings = array_merge($warnings, $constraintValidation['warnings']);
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate business rules specific to manufacturing distribution
     */
    private function validateBusinessRules($dataType, $data, $context) {
        $errors = [];
        $warnings = [];

        switch ($dataType) {
            case 'product':
                $validation = $this->validateProductBusinessRules($data, $context);
                break;
            case 'client':
                $validation = $this->validateClientBusinessRules($data, $context);
                break;
            case 'order':
                $validation = $this->validateOrderBusinessRules($data, $context);
                break;
            case 'inventory':
                $validation = $this->validateInventoryBusinessRules($data, $context);
                break;
            case 'pricing':
                $validation = $this->validatePricingBusinessRules($data, $context);
                break;
            default:
                $validation = ['errors' => [], 'warnings' => []];
        }

        return $validation;
    }

    /**
     * AI-powered anomaly detection using machine learning
     */
    private function detectAnomalies($dataType, $data, $context) {
        $anomalies = [];
        $insights = [];

        // Statistical anomaly detection
        $statisticalAnomalies = $this->detectStatisticalAnomalies($dataType, $data);
        $anomalies = array_merge($anomalies, $statisticalAnomalies);

        // Pattern-based anomaly detection
        $patternAnomalies = $this->detectPatternAnomalies($dataType, $data, $context);
        $anomalies = array_merge($anomalies, $patternAnomalies);

        // Time-series anomaly detection (for historical data)
        if (isset($context['historical_data'])) {
            $timeSeriesAnomalies = $this->detectTimeSeriesAnomalies($dataType, $data, $context['historical_data']);
            $anomalies = array_merge($anomalies, $timeSeriesAnomalies);
        }

        // Generate AI insights
        $insights = $this->generateAnomalyInsights($anomalies, $dataType, $data);

        return [
            'anomalies' => $anomalies,
            'insights' => $insights
        ];
    }

    /**
     * Product-specific business rule validation
     */
    private function validateProductBusinessRules($data, $context) {
        $errors = [];
        $warnings = [];

        // SKU uniqueness check
        if (isset($data['sku'])) {
            if ($this->isSkuDuplicate($data['sku'], $data['id'] ?? null)) {
                $errors[] = [
                    'type' => 'business_rule',
                    'rule' => 'sku_uniqueness',
                    'message' => "SKU '{$data['sku']}' already exists in the system",
                    'severity' => 'critical'
                ];
            }
        }

        // Price validation
        if (isset($data['price'])) {
            if ($data['price'] <= 0) {
                $errors[] = [
                    'type' => 'business_rule',
                    'rule' => 'positive_price',
                    'message' => "Product price must be greater than zero",
                    'severity' => 'high'
                ];
            }

            // Check for unusual price changes
            if (isset($context['previous_price'])) {
                $priceChangePercent = abs(($data['price'] - $context['previous_price']) / $context['previous_price']) * 100;
                if ($priceChangePercent > 50) {
                    $warnings[] = [
                        'type' => 'business_rule',
                        'rule' => 'price_change_threshold',
                        'message' => "Price change of {$priceChangePercent}% exceeds typical threshold",
                        'severity' => 'medium'
                    ];
                }
            }
        }

        // Category validation
        if (isset($data['category'])) {
            $validCategories = $this->getValidProductCategories();
            if (!in_array($data['category'], $validCategories)) {
                $errors[] = [
                    'type' => 'business_rule',
                    'rule' => 'valid_category',
                    'message' => "Invalid product category: {$data['category']}",
                    'severity' => 'high',
                    'suggestions' => $this->suggestSimilarCategories($data['category'])
                ];
            }
        }

        // Weight and dimensions validation
        if (isset($data['weight']) && isset($data['dimensions'])) {
            $dimensionValidation = $this->validateProductDimensions($data);
            $errors = array_merge($errors, $dimensionValidation['errors']);
            $warnings = array_merge($warnings, $dimensionValidation['warnings']);
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Order-specific business rule validation
     */
    private function validateOrderBusinessRules($data, $context) {
        $errors = [];
        $warnings = [];

        // Client credit limit check
        if (isset($data['client_id']) && isset($data['total_amount'])) {
            $creditCheck = $this->validateClientCreditLimit($data['client_id'], $data['total_amount']);
            if (!$creditCheck['approved']) {
                if ($creditCheck['severity'] === 'critical') {
                    $errors[] = [
                        'type' => 'business_rule',
                        'rule' => 'credit_limit',
                        'message' => $creditCheck['message'],
                        'severity' => 'critical'
                    ];
                } else {
                    $warnings[] = [
                        'type' => 'business_rule',
                        'rule' => 'credit_limit',
                        'message' => $creditCheck['message'],
                        'severity' => 'medium'
                    ];
                }
            }
        }

        // Inventory availability check
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $inventoryCheck = $this->validateInventoryAvailability($item['product_id'], $item['quantity']);
                if (!$inventoryCheck['available']) {
                    $errors[] = [
                        'type' => 'business_rule',
                        'rule' => 'inventory_availability',
                        'message' => "Insufficient inventory for product {$item['product_id']}",
                        'severity' => 'high',
                        'available_quantity' => $inventoryCheck['available_quantity'],
                        'requested_quantity' => $item['quantity']
                    ];
                }
            }
        }

        // Minimum order value check
        if (isset($data['total_amount'])) {
            $minimumOrder = $this->getMinimumOrderValue($data['client_id'] ?? null);
            if ($data['total_amount'] < $minimumOrder) {
                $warnings[] = [
                    'type' => 'business_rule',
                    'rule' => 'minimum_order_value',
                    'message' => "Order value ${$data['total_amount']} is below minimum order value ${$minimumOrder}",
                    'severity' => 'medium'
                ];
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Statistical anomaly detection using Z-score and IQR methods
     */
    private function detectStatisticalAnomalies($dataType, $data) {
        $anomalies = [];
        
        // Get historical data for comparison
        $historicalData = $this->getHistoricalData($dataType);
        
        if (empty($historicalData)) {
            return $anomalies; // Not enough data for statistical analysis
        }

        foreach ($data as $field => $value) {
            if (is_numeric($value)) {
                // Extract historical values for this field
                $historicalValues = array_column($historicalData, $field);
                $historicalValues = array_filter($historicalValues, 'is_numeric');
                
                if (count($historicalValues) < 10) {
                    continue; // Need at least 10 data points
                }

                // Z-score anomaly detection
                $mean = array_sum($historicalValues) / count($historicalValues);
                $variance = array_sum(array_map(function($x) use ($mean) { return pow($x - $mean, 2); }, $historicalValues)) / count($historicalValues);
                $stdDev = sqrt($variance);
                
                if ($stdDev > 0) {
                    $zScore = abs(($value - $mean) / $stdDev);
                    
                    if ($zScore > 3) { // 3 sigma rule
                        $anomalies[] = [
                            'type' => 'statistical_anomaly',
                            'method' => 'z_score',
                            'field' => $field,
                            'value' => $value,
                            'z_score' => $zScore,
                            'mean' => $mean,
                            'std_dev' => $stdDev,
                            'severity' => $zScore > 4 ? 'critical' : 'high',
                            'message' => "Value {$value} for field '{$field}' is {$zScore} standard deviations from the mean"
                        ];
                    }
                }

                // IQR anomaly detection
                sort($historicalValues);
                $count = count($historicalValues);
                $q1 = $historicalValues[intval($count * 0.25)];
                $q3 = $historicalValues[intval($count * 0.75)];
                $iqr = $q3 - $q1;
                
                $lowerBound = $q1 - 1.5 * $iqr;
                $upperBound = $q3 + 1.5 * $iqr;
                
                if ($value < $lowerBound || $value > $upperBound) {
                    $anomalies[] = [
                        'type' => 'statistical_anomaly',
                        'method' => 'iqr',
                        'field' => $field,
                        'value' => $value,
                        'q1' => $q1,
                        'q3' => $q3,
                        'iqr' => $iqr,
                        'lower_bound' => $lowerBound,
                        'upper_bound' => $upperBound,
                        'severity' => 'medium',
                        'message' => "Value {$value} for field '{$field}' is outside the IQR bounds [{$lowerBound}, {$upperBound}]"
                    ];
                }
            }
        }

        return $anomalies;
    }

    /**
     * Generate intelligent suggestions based on validation results
     */
    private function generateIntelligentSuggestions($dataType, $data, $validationResult) {
        $suggestions = [];

        // Error-based suggestions
        foreach ($validationResult['errors'] as $error) {
            $suggestion = $this->generateErrorSuggestion($error, $data, $dataType);
            if ($suggestion) {
                $suggestions[] = $suggestion;
            }
        }

        // Anomaly-based suggestions
        foreach ($validationResult['anomalies'] as $anomaly) {
            $suggestion = $this->generateAnomalySuggestion($anomaly, $data, $dataType);
            if ($suggestion) {
                $suggestions[] = $suggestion;
            }
        }

        // Quality improvement suggestions
        $qualitySuggestions = $this->generateQualityImprovementSuggestions($data, $dataType);
        $suggestions = array_merge($suggestions, $qualitySuggestions);

        // AI-powered optimization suggestions
        $optimizationSuggestions = $this->generateOptimizationSuggestions($data, $dataType);
        $suggestions = array_merge($suggestions, $optimizationSuggestions);

        return $suggestions;
    }

    /**
     * Assess overall data quality score
     */
    private function assessDataQuality($dataType, $data, $validationResult) {
        $baseScore = 100;
        $confidence = 100;

        // Deduct points for errors
        foreach ($validationResult['errors'] as $error) {
            switch ($error['severity']) {
                case 'critical':
                    $baseScore -= 25;
                    $confidence -= 10;
                    break;
                case 'high':
                    $baseScore -= 15;
                    $confidence -= 5;
                    break;
                case 'medium':
                    $baseScore -= 5;
                    $confidence -= 2;
                    break;
            }
        }

        // Deduct points for warnings
        foreach ($validationResult['warnings'] as $warning) {
            $baseScore -= ($warning['severity'] === 'high') ? 10 : 5;
            $confidence -= 1;
        }

        // Deduct points for anomalies
        foreach ($validationResult['anomalies'] as $anomaly) {
            switch ($anomaly['severity']) {
                case 'critical':
                    $baseScore -= 20;
                    $confidence -= 8;
                    break;
                case 'high':
                    $baseScore -= 10;
                    $confidence -= 4;
                    break;
                case 'medium':
                    $baseScore -= 5;
                    $confidence -= 2;
                    break;
            }
        }

        // Add bonus for completeness
        $completenessBonus = $this->calculateCompletenessBonus($dataType, $data);
        $baseScore += $completenessBonus;

        // Ensure scores are within bounds
        $finalScore = max(0, min(100, $baseScore));
        $finalConfidence = max(0, min(100, $confidence));

        return [
            'score' => round($finalScore, 1),
            'confidence' => round($finalConfidence, 1),
            'grade' => $this->getQualityGrade($finalScore)
        ];
    }

    /**
     * Load validation rules from configuration
     */
    private function loadValidationRules() {
        // In production, this would load from database or configuration files
        $this->validationRules = [
            'product' => [
                'required_fields' => ['sku', 'name', 'category', 'price'],
                'field_types' => [
                    'sku' => 'string',
                    'name' => 'string',
                    'price' => 'numeric',
                    'weight' => 'numeric',
                    'active' => 'boolean'
                ],
                'field_constraints' => [
                    'sku' => ['max_length' => 50, 'pattern' => '/^[A-Z0-9-]+$/'],
                    'price' => ['min_value' => 0.01, 'max_value' => 999999.99],
                    'weight' => ['min_value' => 0, 'max_value' => 10000]
                ]
            ],
            'client' => [
                'required_fields' => ['name', 'email', 'type'],
                'field_types' => [
                    'name' => 'string',
                    'email' => 'string',
                    'credit_limit' => 'numeric',
                    'active' => 'boolean'
                ],
                'field_constraints' => [
                    'email' => ['pattern' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/'],
                    'credit_limit' => ['min_value' => 0, 'max_value' => 10000000]
                ]
            ]
            // Additional data types...
        ];
    }

    /**
     * Initialize machine learning model for anomaly detection
     */
    private function initializeMLModel() {
        // Placeholder for ML model initialization
        // In production, this would load trained models
        $this->mlModel = new \stdClass();
    }

    /**
     * Get validation analytics and performance metrics
     */
    public function getValidationAnalytics($dateRange = null) {
        $sql = "
            SELECT 
                data_type,
                COUNT(*) as total_validations,
                AVG(quality_score) as avg_quality_score,
                SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_validations,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_validations,
                AVG(confidence_level) as avg_confidence
            FROM data_validation_logs
        ";
        
        if ($dateRange) {
            $sql .= " WHERE timestamp BETWEEN ? AND ?";
            $params = [$dateRange['start'], $dateRange['end']];
        } else {
            $params = [];
        }
        
        $sql .= " GROUP BY data_type ORDER BY total_validations DESC";
        
        $result = $this->db->query($sql, $params);
        $analytics = [];
        
        while ($row = $this->db->fetchByAssoc($result)) {
            $analytics[] = $row;
        }
        
        return $analytics;
    }

    /**
     * Bulk data validation for batch processing
     */
    public function validateBatch($dataType, $dataArray, $options = []) {
        $results = [];
        $batchId = uniqid('batch_');
        
        $this->logger->info("DataValidation: Starting batch validation for {$dataType}, batch size: " . count($dataArray));
        
        foreach ($dataArray as $index => $data) {
            try {
                $context = array_merge($options, ['batch_id' => $batchId, 'batch_index' => $index]);
                $validationResult = $this->validateData($dataType, $data, $context);
                $results[] = $validationResult;
            } catch (Exception $e) {
                $results[] = [
                    'batch_index' => $index,
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                    'data' => $data
                ];
            }
        }
        
        // Generate batch summary
        $batchSummary = $this->generateBatchSummary($results, $batchId);
        
        return [
            'batch_id' => $batchId,
            'summary' => $batchSummary,
            'results' => $results
        ];
    }

    // Additional helper methods...
    
    private function generateValidationId() {
        return 'VAL-' . date('Ymd-His') . '-' . strtoupper(substr(uniqid(), -4));
    }

    private function logValidationResults($validationResult) {
        // Log to database for analytics and monitoring
        $sql = "
            INSERT INTO data_validation_logs 
            (validation_id, data_type, status, quality_score, confidence_level, errors_count, warnings_count, anomalies_count, timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $this->db->query($sql, [
            $validationResult['validation_id'],
            $validationResult['data_type'],
            $validationResult['status'],
            $validationResult['quality_score'],
            $validationResult['confidence_level'],
            count($validationResult['errors']),
            count($validationResult['warnings']),
            count($validationResult['anomalies']),
            $validationResult['timestamp']
        ]);
    }
}
