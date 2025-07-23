<?php
/**
 * Comprehensive Feature Testing Suite
 * Tests Features 1 & 2 with performance metrics and visual verification
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

class FeatureTestSuite {
    private $testResults = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    public function testFeature1ProductCatalog() {
        echo "Testing Feature 1: Product Catalog...\n";
        
        // Test 1: Product API Response Time
        $start = microtime(true);
        $response = $this->makeRequest('/Api/v1/manufacturing/ProductCatalogAPI.php', [
            'action' => 'search',
            'query' => 'steel',
            'limit' => 20
        ]);
        $apiTime = microtime(true) - $start;
        
        $this->testResults['feature1']['api_response_time'] = $apiTime;
        $this->testResults['feature1']['api_status'] = !empty($response['products']) ? 'PASS' : 'FAIL';
        
        // Test 2: Product Count and Data Quality
        if (!empty($response['products'])) {
            $productCount = count($response['products']);
            $this->testResults['feature1']['product_count'] = $productCount;
            
            // Check first product has required fields
            $firstProduct = $response['products'][0];
            $requiredFields = ['sku', 'name', 'price', 'category', 'stock_level'];
            $hasAllFields = true;
            foreach ($requiredFields as $field) {
                if (!isset($firstProduct[$field])) {
                    $hasAllFields = false;
                    break;
                }
            }
            $this->testResults['feature1']['data_quality'] = $hasAllFields ? 'PASS' : 'FAIL';
        }
        
        // Test 3: Client-Specific Pricing
        $pricingResponse = $this->makeRequest('/Api/v1/manufacturing/ProductCatalogAPI.php', [
            'action' => 'search',
            'client_tier' => 'wholesale',
            'query' => 'bracket'
        ]);
        
        $this->testResults['feature1']['pricing_calculation'] = 
            (!empty($pricingResponse['products']) && 
             isset($pricingResponse['products'][0]['price'])) ? 'PASS' : 'FAIL';
        
        return $this->testResults['feature1'];
    }
    
    public function testFeature2OrderPipeline() {
        echo "Testing Feature 2: Order Pipeline...\n";
        
        // Test 1: Pipeline API Response
        $start = microtime(true);
        $response = $this->makeRequest('/Api/v1/manufacturing/OrderPipelineAPI.php', [
            'action' => 'get_dashboard'
        ]);
        $apiTime = microtime(true) - $start;
        
        $this->testResults['feature2']['api_response_time'] = $apiTime;
        $this->testResults['feature2']['api_status'] = !empty($response['pipeline_data']) ? 'PASS' : 'FAIL';
        
        // Test 2: Pipeline Stages
        if (!empty($response['pipeline_data'])) {
            $expectedStages = ['quote', 'approved', 'ordered', 'production', 'shipped', 'delivered', 'invoiced'];
            $stagesPresent = 0;
            
            foreach ($expectedStages as $stage) {
                if (isset($response['pipeline_data'][$stage])) {
                    $stagesPresent++;
                }
            }
            
            $this->testResults['feature2']['pipeline_stages'] = $stagesPresent . '/' . count($expectedStages);
            $this->testResults['feature2']['stages_complete'] = ($stagesPresent === count($expectedStages)) ? 'PASS' : 'FAIL';
        }
        
        // Test 3: Mobile Dashboard
        $mobileResponse = $this->makeRequest('/themes/SuiteP/tpls/mobile_dashboard.tpl');
        $this->testResults['feature2']['mobile_dashboard'] = 
            (strpos($mobileResponse, 'mobile-pipeline') !== false) ? 'PASS' : 'FAIL';
        
        return $this->testResults['feature2'];
    }
    
    public function testPerformanceMetrics() {
        echo "Testing Performance Metrics...\n";
        
        // Test homepage load time
        $start = microtime(true);
        $response = file_get_contents('http://localhost:3000/');
        $homepageTime = microtime(true) - $start;
        
        $this->testResults['performance']['homepage_load_time'] = $homepageTime;
        $this->testResults['performance']['homepage_status'] = (strlen($response) > 1000) ? 'PASS' : 'FAIL';
        
        // Test mobile catalog page
        $start = microtime(true);
        $response = file_get_contents('http://localhost:3000/index.php?module=Home&action=index&mobile=1');
        $mobileTime = microtime(true) - $start;
        
        $this->testResults['performance']['mobile_load_time'] = $mobileTime;
        $this->testResults['performance']['mobile_status'] = (strlen($response) > 500) ? 'PASS' : 'FAIL';
        
        // Overall performance score
        $avgTime = ($homepageTime + $mobileTime) / 2;
        $this->testResults['performance']['overall_score'] = $avgTime < 2.0 ? 'EXCELLENT' : ($avgTime < 5.0 ? 'GOOD' : 'NEEDS_IMPROVEMENT');
        
        return $this->testResults['performance'];
    }
    
    private function makeRequest($endpoint, $params = []) {
        $url = 'http://localhost:3000' . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        return json_decode($response, true) ?: $response;
    }
    
    public function generateReport() {
        $totalTime = microtime(true) - $this->startTime;
        
        return [
            'test_summary' => [
                'total_test_time' => round($totalTime, 3),
                'timestamp' => date('Y-m-d H:i:s'),
                'features_tested' => ['Product Catalog', 'Order Pipeline'],
                'performance_tested' => true
            ],
            'results' => $this->testResults,
            'recommendations' => $this->generateRecommendations()
        ];
    }
    
    private function generateRecommendations() {
        $recommendations = [];
        
        // Check API response times
        if (isset($this->testResults['feature1']['api_response_time']) && 
            $this->testResults['feature1']['api_response_time'] > 1.0) {
            $recommendations[] = "Feature 1 API response time is " . 
                round($this->testResults['feature1']['api_response_time'], 2) . "s - consider caching optimization";
        }
        
        if (isset($this->testResults['performance']['overall_score']) && 
            $this->testResults['performance']['overall_score'] === 'NEEDS_IMPROVEMENT') {
            $recommendations[] = "Overall performance needs improvement - check server configuration";
        }
        
        return $recommendations;
    }
}

// Run the test suite
$testSuite = new FeatureTestSuite();

echo "=== ENTERPRISE MANUFACTURING CRM - FEATURE TEST SUITE ===\n\n";

// Test each feature
$feature1Results = $testSuite->testFeature1ProductCatalog();
echo "Feature 1 Results: " . json_encode($feature1Results, JSON_PRETTY_PRINT) . "\n\n";

$feature2Results = $testSuite->testFeature2OrderPipeline();
echo "Feature 2 Results: " . json_encode($feature2Results, JSON_PRETTY_PRINT) . "\n\n";

$performanceResults = $testSuite->testPerformanceMetrics();
echo "Performance Results: " . json_encode($performanceResults, JSON_PRETTY_PRINT) . "\n\n";

// Generate final report
$finalReport = $testSuite->generateReport();
echo "=== FINAL TEST REPORT ===\n";
echo json_encode($finalReport, JSON_PRETTY_PRINT);

?>
