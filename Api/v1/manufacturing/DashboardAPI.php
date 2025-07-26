<?php
/**
 * Executive Dashboard API - Real-time Manufacturing KPIs
 * Provides consolidated data for the executive dashboard
 */

// Database connection - Use SuiteCRM config
require_once(dirname(__FILE__) . '/../../../config.php');

$db_host_full = $sugar_config['dbconfig']['db_host_name'] ?? 'localhost';
$db_user = $sugar_config['dbconfig']['db_user_name'] ?? 'root';
$db_pass = $sugar_config['dbconfig']['db_password'] ?? '';
$db_name = $sugar_config['dbconfig']['db_name'] ?? 'suitecrm';

// Parse host and port
if (strpos($db_host_full, ':') !== false) {
    list($db_host, $db_port) = explode(':', $db_host_full);
    $db_port = (int)$db_port;
} else {
    $db_host = $db_host_full;
    $db_port = 3306;
}

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'details' => $mysqli->connect_error]);
    exit;
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$endpoint = $_GET['endpoint'] ?? '';

try {
    switch ($endpoint) {
        case 'kpis':
            echo json_encode(getKPIs($mysqli));
            break;
            
        case 'top-products':
            echo json_encode(getTopProducts($mysqli));
            break;
            
        case 'pipeline-summary':
            echo json_encode(getPipelineSummary($mysqli));
            break;
            
        case 'inventory-alerts':
            echo json_encode(getInventoryAlerts($mysqli));
            break;
            
        case 'recent-quotes':
            echo json_encode(getRecentQuotes($mysqli));
            break;
            
        case 'team-activity':
            echo json_encode(getTeamActivity($mysqli));
            break;
            
        default:
            echo json_encode(getAllDashboardData($mysqli));
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'API Error', 'message' => $e->getMessage()]);
}

function getKPIs($mysqli) {
    // Use mock data for demonstration (in production, would query real tables)
    $revenue = 127350 + rand(-5000, 15000); // Simulate fluctuation
    $activeOrders = 24 + rand(-2, 5);
    $lowStock = 7 + rand(-2, 3);
    $pendingQuotes = 12 + rand(-3, 5);
    
    return [
        'monthly_revenue' => number_format($revenue, 0),
        'active_orders' => $activeOrders,
        'low_stock_alerts' => $lowStock,
        'pending_quotes' => $pendingQuotes,
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

function getTopProducts($mysqli) {
    // Mock data for top products
    return [
        [
            'name' => 'Steel Pipe 2"',
            'price' => '$24.99',
            'stock' => 152,
            'stock_status' => 'in-stock'
        ],
        [
            'name' => 'Copper Fittings',
            'price' => '$12.50',
            'stock' => 8,
            'stock_status' => 'low-stock'
        ],
        [
            'name' => 'Brass Valves',
            'price' => '$89.99',
            'stock' => 45,
            'stock_status' => 'in-stock'
        ]
    ];
}

function getPipelineSummary($mysqli) {
    // Mock pipeline data
    return [
        [
            'stage' => 'Quote Sent',
            'count' => 5 + rand(0, 3)
        ],
        [
            'stage' => 'Processing',
            'count' => 8 + rand(-2, 4)
        ],
        [
            'stage' => 'Ready to Ship',
            'count' => 3 + rand(0, 2)
        ]
    ];
}

function getInventoryAlerts($mysqli) {
    // Mock inventory data
    return [
        'in_stock' => 152,
        'low_stock' => 12,
        'out_of_stock' => 3,
        'urgent_items' => [
            [
                'name' => 'Steel Pipe 2"',
                'stock' => 5
            ],
            [
                'name' => 'Copper Fittings',
                'stock' => 0
            ]
        ]
    ];
}

function getRecentQuotes($mysqli) {
    // Mock quotes data
    return [
        [
            'number' => '#Q-2024-001',
            'client' => 'ABC Manufacturing',
            'amount' => '$5,240',
            'status' => 'pending'
        ],
        [
            'number' => '#Q-2024-002',
            'client' => 'XYZ Corp',
            'amount' => '$3,180',
            'status' => 'approved'
        ]
    ];
}

function getTeamActivity($mysqli) {
    // Simulate team activity (in real implementation, track user actions)
    return [
        [
            'user' => 'John Smith',
            'action' => 'created quote #Q-001',
            'time' => '2 min ago'
        ],
        [
            'user' => 'Mary Johnson', 
            'action' => 'updated inventory',
            'time' => '15 min ago'
        ]
    ];
}

function getAllDashboardData($mysqli) {
    return [
        'kpis' => getKPIs($mysqli),
        'top_products' => getTopProducts($mysqli),
        'pipeline_summary' => getPipelineSummary($mysqli),
        'inventory_alerts' => getInventoryAlerts($mysqli),
        'recent_quotes' => getRecentQuotes($mysqli),
        'team_activity' => getTeamActivity($mysqli)
    ];
}

$mysqli->close();
?>
