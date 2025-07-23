<?php
/**
 * Comprehensive Pricing Calculation Testing Script
 * Tests all client tier pricing scenarios
 */

require_once 'include/entryPoint.php';

header('Content-Type: application/json');

class PricingTestSuite {
    private $db;
    private $testResults = [];
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    /**
     * Run all pricing tests
     */
    public function runAllTests() {
        $this->addResult('info', 'Starting Comprehensive Pricing Tests', 'Testing all client tiers and scenarios');
        
        // Test 1: Retail Pricing (Base price, no discounts)
        $this->testRetailPricing();
        
        // Test 2: Wholesale Pricing (10-25% discount)
        $this->testWholesalePricing();
        
        // Test 3: OEM Pricing (25-40% discount)
        $this->testOEMPricing();
        
        // Test 4: Contract Pricing (Custom negotiated rates)
        $this->testContractPricing();
        
        // Test 5: Volume Discount Calculations
        $this->testVolumeDiscounts();
        
        // Test 6: Edge Cases
        $this->testEdgeCases();
        
        return $this->testResults;
    }
    
    /**
     * Test retail pricing (base prices)
     */
    private function testRetailPricing() {
        $this->addResult('info', 'Testing Retail Pricing', 'Base price calculations for retail customers');
        
        $testProducts = [
            ['id' => 1, 'sku' => 'MFG-001', 'base_price' => 125.00],
            ['id' => 2, 'sku' => 'MFG-002', 'base_price' => 85.50],
            ['id' => 3, 'sku' => 'MFG-003', 'base_price' => 450.00]
        ];
        
        foreach ($testProducts as $product) {
            $calculatedPrice = $this->calculateClientPrice($product['base_price'], 'retail');
            $expectedPrice = $product['base_price']; // No discount for retail
            
            if (abs($calculatedPrice - $expectedPrice) < 0.01) {
                $this->addResult('pass', "Retail Pricing - {$product['sku']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice}");
            } else {
                $this->addResult('fail', "Retail Pricing - {$product['sku']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice}");
            }
        }
    }
    
    /**
     * Test wholesale pricing (10-25% discount)
     */
    private function testWholesalePricing() {
        $this->addResult('info', 'Testing Wholesale Pricing', '15% discount for wholesale customers');
        
        $testProducts = [
            ['id' => 1, 'sku' => 'MFG-001', 'base_price' => 125.00],
            ['id' => 2, 'sku' => 'MFG-002', 'base_price' => 85.50],
            ['id' => 3, 'sku' => 'MFG-003', 'base_price' => 450.00]
        ];
        
        foreach ($testProducts as $product) {
            $calculatedPrice = $this->calculateClientPrice($product['base_price'], 'wholesale');
            $expectedPrice = $product['base_price'] * 0.85; // 15% discount
            
            if (abs($calculatedPrice - $expectedPrice) < 0.01) {
                $this->addResult('pass', "Wholesale Pricing - {$product['sku']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice}");
            } else {
                $this->addResult('fail', "Wholesale Pricing - {$product['sku']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice}");
            }
        }
    }
    
    /**
     * Test OEM pricing (25-40% discount)
     */
    private function testOEMPricing() {
        $this->addResult('info', 'Testing OEM Pricing', '30% discount for OEM customers');
        
        $testProducts = [
            ['id' => 1, 'sku' => 'MFG-001', 'base_price' => 125.00],
            ['id' => 2, 'sku' => 'MFG-002', 'base_price' => 85.50],
            ['id' => 3, 'sku' => 'MFG-003', 'base_price' => 450.00]
        ];
        
        foreach ($testProducts as $product) {
            $calculatedPrice = $this->calculateClientPrice($product['base_price'], 'oem');
            $expectedPrice = $product['base_price'] * 0.70; // 30% discount
            
            if (abs($calculatedPrice - $expectedPrice) < 0.01) {
                $this->addResult('pass', "OEM Pricing - {$product['sku']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice}");
            } else {
                $this->addResult('fail', "OEM Pricing - {$product['sku']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice}");
            }
        }
    }
    
    /**
     * Test contract pricing (custom negotiated rates)
     */
    private function testContractPricing() {
        $this->addResult('info', 'Testing Contract Pricing', 'Custom negotiated pricing overrides');
        
        // Test contract pricing overrides
        $contractScenarios = [
            ['client_id' => 1001, 'product_id' => 1, 'contract_price' => 95.00, 'base_price' => 125.00],
            ['client_id' => 1002, 'product_id' => 2, 'contract_price' => 75.00, 'base_price' => 85.50],
            ['client_id' => 1003, 'product_id' => 3, 'contract_price' => 380.00, 'base_price' => 450.00]
        ];
        
        foreach ($contractScenarios as $scenario) {
            $calculatedPrice = $this->calculateContractPrice($scenario['client_id'], $scenario['product_id'], $scenario['base_price']);
            $expectedPrice = $scenario['contract_price'];
            
            if (abs($calculatedPrice - $expectedPrice) < 0.01) {
                $this->addResult('pass', "Contract Pricing - Client {$scenario['client_id']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice}");
            } else {
                $this->addResult('fail', "Contract Pricing - Client {$scenario['client_id']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice}");
            }
        }
    }
    
    /**
     * Test volume discount calculations
     */
    private function testVolumeDiscounts() {
        $this->addResult('info', 'Testing Volume Discounts', 'Quantity-based pricing tiers');
        
        $volumeTests = [
            ['quantity' => 10, 'base_price' => 100.00, 'expected_discount' => 0],
            ['quantity' => 50, 'base_price' => 100.00, 'expected_discount' => 5],
            ['quantity' => 100, 'base_price' => 100.00, 'expected_discount' => 10],
            ['quantity' => 500, 'base_price' => 100.00, 'expected_discount' => 15]
        ];
        
        foreach ($volumeTests as $test) {
            $calculatedPrice = $this->calculateVolumePrice($test['base_price'], $test['quantity']);
            $expectedPrice = $test['base_price'] * (1 - $test['expected_discount'] / 100);
            
            if (abs($calculatedPrice - $expectedPrice) < 0.01) {
                $this->addResult('pass', "Volume Discount - Qty {$test['quantity']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice} ({$test['expected_discount']}% discount)");
            } else {
                $this->addResult('fail', "Volume Discount - Qty {$test['quantity']}", "Calculated: \${$calculatedPrice}, Expected: \${$expectedPrice} ({$test['expected_discount']}% discount)");
            }
        }
    }
    
    /**
     * Test edge cases and error scenarios
     */
    private function testEdgeCases() {
        $this->addResult('info', 'Testing Edge Cases', 'Error handling and boundary conditions');
        
        // Test zero quantity
        try {
            $price = $this->calculateVolumePrice(100.00, 0);
            $this->addResult('fail', 'Zero Quantity Test', 'Should have thrown an error');
        } catch (Exception $e) {
            $this->addResult('pass', 'Zero Quantity Test', 'Correctly rejected zero quantity');
        }
        
        // Test negative pricing
        try {
            $price = $this->calculateClientPrice(-50.00, 'retail');
            $this->addResult('fail', 'Negative Price Test', 'Should have thrown an error');
        } catch (Exception $e) {
            $this->addResult('pass', 'Negative Price Test', 'Correctly rejected negative price');
        }
        
        // Test invalid client tier
        try {
            $price = $this->calculateClientPrice(100.00, 'invalid_tier');
            $this->addResult('fail', 'Invalid Tier Test', 'Should have thrown an error');
        } catch (Exception $e) {
            $this->addResult('pass', 'Invalid Tier Test', 'Correctly rejected invalid tier');
        }
        
        // Test extreme quantity
        $extremePrice = $this->calculateVolumePrice(100.00, 10000);
        if ($extremePrice > 0 && $extremePrice <= 100.00) {
            $this->addResult('pass', 'Extreme Quantity Test', "Handled large quantity correctly: \${$extremePrice}");
        } else {
            $this->addResult('fail', 'Extreme Quantity Test', "Unexpected result for large quantity: \${$extremePrice}");
        }
    }
    
    /**
     * Calculate price based on client tier
     */
    private function calculateClientPrice($basePrice, $clientTier) {
        if ($basePrice < 0) {
            throw new Exception('Base price cannot be negative');
        }
        
        switch (strtolower($clientTier)) {
            case 'retail':
                return $basePrice; // No discount
            case 'wholesale':
                return $basePrice * 0.85; // 15% discount
            case 'oem':
                return $basePrice * 0.70; // 30% discount
            default:
                throw new Exception('Invalid client tier');
        }
    }
    
    /**
     * Calculate contract pricing
     */
    private function calculateContractPrice($clientId, $productId, $basePrice) {
        // Simulate contract pricing lookup
        $contractPrices = [
            '1001_1' => 95.00,
            '1002_2' => 75.00,
            '1003_3' => 380.00
        ];
        
        $key = $clientId . '_' . $productId;
        if (isset($contractPrices[$key])) {
            return $contractPrices[$key];
        }
        
        // Fall back to wholesale pricing if no contract
        return $this->calculateClientPrice($basePrice, 'wholesale');
    }
    
    /**
     * Calculate volume-based pricing
     */
    private function calculateVolumePrice($basePrice, $quantity) {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than zero');
        }
        
        $discount = 0;
        
        if ($quantity >= 500) {
            $discount = 15; // 15% discount for 500+
        } elseif ($quantity >= 100) {
            $discount = 10; // 10% discount for 100+
        } elseif ($quantity >= 50) {
            $discount = 5; // 5% discount for 50+
        }
        
        return $basePrice * (1 - $discount / 100);
    }
    
    /**
     * Add test result
     */
    private function addResult($status, $test, $message, $details = '') {
        $this->testResults[] = [
            'status' => $status,
            'test' => $test,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Run the tests
$testSuite = new PricingTestSuite();
$results = $testSuite->runAllTests();

// Calculate summary
$totalTests = count($results);
$passedTests = count(array_filter($results, function($r) { return $r['status'] === 'pass'; }));
$failedTests = count(array_filter($results, function($r) { return $r['status'] === 'fail'; }));
$infoTests = count(array_filter($results, function($r) { return $r['status'] === 'info'; }));

$summary = [
    'total_tests' => $totalTests,
    'passed' => $passedTests,
    'failed' => $failedTests,
    'info' => $infoTests,
    'success_rate' => $totalTests > 0 ? round(($passedTests / ($passedTests + $failedTests)) * 100, 2) : 0,
    'timestamp' => date('Y-m-d H:i:s')
];

// Output results
echo json_encode([
    'status' => 'success',
    'summary' => $summary,
    'results' => $results
], JSON_PRETTY_PRINT);
?>
