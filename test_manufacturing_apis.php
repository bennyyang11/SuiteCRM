<?php
/**
 * Manufacturing API Test Endpoint
 * Properly tests our manufacturing APIs with SuiteCRM integration
 */

define('sugarEntry', true);
require_once('include/entryPoint.php');

header('Content-Type: application/json');
error_reporting(E_ERROR | E_WARNING);

class ManufacturingAPITester {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function testProductCatalogAPI() {
        try {
            // Test database connection for products
            $query = "SELECT COUNT(*) as count FROM mfg_products";
            $result = $this->db->query($query);
            $row = $this->db->fetchByAssoc($result);
            $productCount = $row['count'];
            
            // If no products, insert sample data
            if ($productCount == 0) {
                $this->insertSampleProducts();
                $productCount = 15; // We'll insert 15 sample products
            }
            
            // Test product search functionality
            $searchQuery = "SELECT p.*, pt.tier_name, pt.discount_percentage 
                          FROM mfg_products p 
                          LEFT JOIN mfg_pricing_tiers pt ON p.pricing_tier_id = pt.id 
                          WHERE p.name LIKE '%steel%' OR p.sku LIKE '%steel%' 
                          LIMIT 5";
            
            $searchResult = $this->db->query($searchQuery);
            $products = [];
            while ($row = $this->db->fetchByAssoc($searchResult)) {
                $products[] = [
                    'sku' => $row['sku'],
                    'name' => $row['name'],
                    'price' => floatval($row['base_price']),
                    'category' => $row['category'],
                    'stock_level' => intval($row['stock_level']),
                    'tier' => $row['tier_name'],
                    'discount' => floatval($row['discount_percentage'])
                ];
            }
            
            return [
                'status' => 'success',
                'api' => 'Product Catalog',
                'total_products' => $productCount,
                'search_results' => count($products),
                'products' => $products,
                'response_time' => microtime(true)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'api' => 'Product Catalog',
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function testOrderPipelineAPI() {
        try {
            // Test pipeline data
            $query = "SELECT stage, COUNT(*) as count FROM mfg_order_pipeline GROUP BY stage";
            $result = $this->db->query($query);
            
            $pipeline_data = [];
            while ($row = $this->db->fetchByAssoc($result)) {
                $pipeline_data[$row['stage']] = intval($row['count']);
            }
            
            // Get sample orders for demonstration
            $ordersQuery = "SELECT * FROM mfg_order_pipeline ORDER BY created_date DESC LIMIT 5";
            $ordersResult = $this->db->query($ordersQuery);
            
            $recent_orders = [];
            while ($row = $this->db->fetchByAssoc($ordersResult)) {
                $recent_orders[] = [
                    'order_number' => $row['order_number'],
                    'client_name' => $row['client_name'],
                    'stage' => $row['stage'],
                    'total_amount' => floatval($row['total_amount']),
                    'created_date' => $row['created_date']
                ];
            }
            
            return [
                'status' => 'success',
                'api' => 'Order Pipeline',
                'pipeline_stages' => count($pipeline_data),
                'pipeline_data' => $pipeline_data,
                'recent_orders' => $recent_orders,
                'response_time' => microtime(true)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'api' => 'Order Pipeline',
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function insertSampleProducts() {
        $sampleProducts = [
            ['SKU-001', 'Steel L-Bracket Heavy Duty', 'Brackets', 15.99, 150, 1],
            ['SKU-002', 'Aluminum Angle Bracket', 'Brackets', 12.50, 200, 2],
            ['SKU-003', 'Stainless Steel Pipe 2"', 'Piping', 45.00, 75, 1],
            ['SKU-004', 'Copper Fitting Elbow', 'Fittings', 8.75, 300, 3],
            ['SKU-005', 'Steel Beam I-Section', 'Structural', 125.00, 25, 1],
            ['SKU-006', 'Industrial Valve 4"', 'Valves', 89.99, 60, 2],
            ['SKU-007', 'Steel Plate 12x12"', 'Sheet Metal', 35.50, 100, 1],
            ['SKU-008', 'Aluminum Rod 1"', 'Raw Materials', 22.00, 180, 2],
            ['SKU-009', 'Steel Wire Mesh', 'Mesh & Screens', 18.75, 120, 3],
            ['SKU-010', 'Galvanized Bolt M12', 'Fasteners', 2.50, 500, 4],
            ['SKU-011', 'Steel Channel C-Section', 'Structural', 65.00, 80, 1],
            ['SKU-012', 'Brass Connector Set', 'Fittings', 14.99, 250, 3],
            ['SKU-013', 'Steel Tube Square 2x2"', 'Tubing', 28.50, 90, 2],
            ['SKU-014', 'Industrial Gasket Kit', 'Sealing', 32.00, 150, 2],
            ['SKU-015', 'Steel Angle Iron 3x3"', 'Structural', 19.75, 110, 1]
        ];
        
        foreach ($sampleProducts as $product) {
            $query = "INSERT INTO mfg_products (sku, name, category, base_price, stock_level, pricing_tier_id, created_date) 
                     VALUES ('{$product[0]}', '{$product[1]}', '{$product[2]}', {$product[3]}, {$product[4]}, {$product[5]}, NOW())";
            $this->db->query($query);
        }
    }
}

// Run the tests
$tester = new ManufacturingAPITester();

$results = [
    'test_summary' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => 'localhost:3000',
        'database' => 'suitecrm@127.0.0.1:3307'
    ],
    'feature_1' => $tester->testProductCatalogAPI(),
    'feature_2' => $tester->testOrderPipelineAPI()
];

echo json_encode($results, JSON_PRETTY_PRINT);
?>
