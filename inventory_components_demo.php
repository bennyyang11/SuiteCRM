<?php
/**
 * Real-Time Inventory Components Demo
 * Manufacturing Distribution Platform - Feature 3
 * 
 * Demonstrates stock indicators, meters, and real-time updates
 */

require_once('config.php');

// Get sample products for demo
global $sugar_config;
$host = str_replace(':3307', '', $sugar_config['dbconfig']['db_host_name']);
$db = new mysqli(
    $host,
    $sugar_config['dbconfig']['db_user_name'],
    $sugar_config['dbconfig']['db_password'],
    $sugar_config['dbconfig']['db_name'],
    3307
);

$products_result = $db->query("
    SELECT DISTINCT p.id, p.name, p.sku 
    FROM mfg_products p
    JOIN mfg_inventory i ON p.id = i.product_id
    WHERE p.deleted = 0 AND i.deleted = 0 
    LIMIT 3
");

$products = [];
while ($row = $products_result->fetch_assoc()) {
    $products[] = $row;
}

$warehouses_result = $db->query("
    SELECT id, name, code 
    FROM mfg_warehouses 
    WHERE deleted = 0 AND is_active = 1 
    LIMIT 2
");

$warehouses = [];
while ($row = $warehouses_result->fetch_assoc()) {
    $warehouses[] = $row;
}

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏭 Real-Time Inventory Components Demo</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #6c757d;
            font-size: 1.1em;
        }
        
        .demo-section {
            margin-bottom: 50px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .section-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #495057;
        }
        
        .section-description {
            color: #6c757d;
            font-size: 0.95em;
            max-width: 60%;
        }
        
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .demo-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
        }
        
        .card-title {
            font-size: 1.1em;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .controls {
            background: #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 30px;
        }
        
        .controls h3 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .control-group {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .control-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .control-item label {
            font-weight: 500;
            color: #495057;
        }
        
        .control-item select, .control-item input {
            padding: 5px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9em;
        }
        
        .update-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .update-btn:hover {
            background: #0056b3;
        }
        
        .stats-bar {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .demo-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .section-description {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏭 Real-Time Inventory Components</h1>
            <p>Live demonstration of manufacturing distribution inventory management</p>
        </div>
        
        <!-- Stats Bar -->
        <div class="stats-bar" id="statsBar">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-value" id="totalItems">-</span>
                    <span class="stat-label">Total Items</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="totalStock">-</span>
                    <span class="stat-label">Total Stock</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="lowStockItems">-</span>
                    <span class="stat-label">Low Stock</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="warehouses"><?php echo count($warehouses); ?></span>
                    <span class="stat-label">Warehouses</span>
                </div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="controls">
            <h3>📊 Demo Controls</h3>
            <div class="control-group">
                <div class="control-item">
                    <label>Product:</label>
                    <select id="productSelect">
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> (<?php echo $product['sku']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="control-item">
                    <label>Warehouse:</label>
                    <select id="warehouseSelect">
                        <option value="">All Warehouses</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo $warehouse['id']; ?>">
                                <?php echo htmlspecialchars($warehouse['name']); ?> (<?php echo $warehouse['code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="control-item">
                    <label>Update Interval:</label>
                    <select id="updateInterval">
                        <option value="10000">10 seconds</option>
                        <option value="30000" selected>30 seconds</option>
                        <option value="60000">1 minute</option>
                        <option value="300000">5 minutes</option>
                    </select>
                </div>
                <button class="update-btn" onclick="updateComponents()">🔄 Update Now</button>
            </div>
        </div>
        
        <!-- Stock Indicator Demo -->
        <div class="demo-section">
            <div class="section-header">
                <div class="section-title">📊 Stock Indicators</div>
                <div class="section-description">
                    Real-time stock level badges with warehouse breakdown and availability status
                </div>
            </div>
            
            <div class="demo-grid">
                <div class="demo-card">
                    <div class="card-title">🎯 Compact Stock Indicator</div>
                    <div id="stockIndicator1" 
                         data-stock-indicator 
                         data-product-id="<?php echo $products[0]['id'] ?? ''; ?>"
                         data-show-details="false"
                         data-show-warehouse="false">
                    </div>
                </div>
                
                <div class="demo-card">
                    <div class="card-title">📋 Detailed Stock Indicator</div>
                    <div id="stockIndicator2" 
                         data-stock-indicator 
                         data-product-id="<?php echo $products[0]['id'] ?? ''; ?>"
                         data-show-details="true"
                         data-show-warehouse="true">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stock Meter Demo -->
        <div class="demo-section">
            <div class="section-header">
                <div class="section-title">📈 Stock Meters</div>
                <div class="section-description">
                    Advanced visualization with progress bars, trends, and interactive controls
                </div>
            </div>
            
            <div class="demo-grid">
                <div class="demo-card" style="grid-column: 1 / -1;">
                    <div class="card-title">⚡ Full-Featured Stock Meter</div>
                    <div id="stockMeter1" 
                         data-stock-meter 
                         data-product-id="<?php echo $products[0]['id'] ?? ''; ?>"
                         data-show-trend="true"
                         data-show-thresholds="true"
                         data-show-actions="true"
                         data-height="250">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Multiple Products Demo -->
        <div class="demo-section">
            <div class="section-header">
                <div class="section-title">🏪 Multiple Products View</div>
                <div class="section-description">
                    Compare stock levels across different products simultaneously
                </div>
            </div>
            
            <div class="demo-grid">
                <?php foreach (array_slice($products, 0, 3) as $index => $product): ?>
                    <div class="demo-card">
                        <div class="card-title">
                            📦 <?php echo htmlspecialchars($product['name']); ?>
                            <small style="color: #6c757d;">(<?php echo $product['sku']; ?>)</small>
                        </div>
                        <div id="multiStockIndicator<?php echo $index; ?>" 
                             data-stock-indicator 
                             data-product-id="<?php echo $product['id']; ?>"
                             data-show-details="true"
                             data-show-warehouse="false">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Low Stock Alert Demo -->
        <div class="demo-section">
            <div class="section-header">
                <div class="section-title">⚠️ Low Stock Alerts</div>
                <div class="section-description">
                    Real-time monitoring of products requiring attention
                </div>
            </div>
            
            <div id="lowStockAlerts" style="min-height: 200px;">
                <div style="text-align: center; padding: 50px; color: #6c757d;">
                    Loading low stock alerts...
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div>Updating inventory data...</div>
        </div>
    </div>
    
    <!-- Include Components -->
    <script src="components/StockIndicator.js"></script>
    <script src="components/StockMeter.js"></script>
    
    <script>
        // Demo JavaScript
        let components = [];
        
        // Load initial stats
        async function loadStats() {
            try {
                const response = await fetch('/inventory_api_direct.php?action=get_summary');
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    document.getElementById('totalItems').textContent = formatNumber(data.total_items);
                    document.getElementById('totalStock').textContent = formatNumber(data.total_stock);
                    document.getElementById('lowStockItems').textContent = formatNumber(data.low_stock_items);
                }
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        }
        
        // Load low stock alerts
        async function loadLowStockAlerts() {
            try {
                const response = await fetch('/inventory_api_direct.php?action=get_low_stock&limit=5');
                const result = await response.json();
                
                if (result.success && result.data.low_stock_items.length > 0) {
                    const alertsHtml = result.data.low_stock_items.map(item => `
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-bottom: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>${item.product_name}</strong> 
                                    <span style="color: #6c757d;">(${item.sku})</span>
                                    <br>
                                    <small>${item.warehouse_name} (${item.warehouse_code})</small>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: #856404; font-weight: 600;">
                                        ${formatNumber(item.current_stock)} in stock
                                    </div>
                                    <div style="color: #dc3545; font-size: 0.9em;">
                                        Need ${formatNumber(item.shortage_quantity)} more
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    
                    document.getElementById('lowStockAlerts').innerHTML = alertsHtml;
                } else {
                    document.getElementById('lowStockAlerts').innerHTML = `
                        <div style="text-align: center; padding: 50px; color: #28a745;">
                            ✅ All products are adequately stocked
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Failed to load low stock alerts:', error);
                document.getElementById('lowStockAlerts').innerHTML = `
                    <div style="text-align: center; padding: 50px; color: #dc3545;">
                        ❌ Failed to load alerts
                    </div>
                `;
            }
        }
        
        // Update components based on controls
        function updateComponents() {
            const productId = document.getElementById('productSelect').value;
            const warehouseId = document.getElementById('warehouseSelect').value;
            const updateInterval = parseInt(document.getElementById('updateInterval').value);
            
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            // Update existing components
            setTimeout(() => {
                // Update product IDs for dynamic components
                const indicators = document.querySelectorAll('[data-stock-indicator]');
                indicators.forEach((indicator, index) => {
                    if (index < 2) { // First two are dynamic
                        indicator.setAttribute('data-product-id', productId);
                        indicator.innerHTML = ''; // Clear and reinitialize
                        new StockIndicator(indicator, {
                            productId: productId,
                            showDetails: indicator.getAttribute('data-show-details') === 'true',
                            showWarehouse: indicator.getAttribute('data-show-warehouse') === 'true',
                            updateInterval: updateInterval
                        });
                    }
                });
                
                const meters = document.querySelectorAll('[data-stock-meter]');
                meters.forEach(meter => {
                    meter.setAttribute('data-product-id', productId);
                    if (warehouseId) {
                        meter.setAttribute('data-warehouse-id', warehouseId);
                    } else {
                        meter.removeAttribute('data-warehouse-id');
                    }
                    meter.innerHTML = ''; // Clear and reinitialize
                    new StockMeter(meter, {
                        productId: productId,
                        warehouseId: warehouseId || null,
                        showTrend: true,
                        showThresholds: true,
                        showActions: true,
                        height: 250,
                        updateInterval: updateInterval
                    });
                });
                
                // Reload stats and alerts
                loadStats();
                loadLowStockAlerts();
                
                document.getElementById('loadingOverlay').style.display = 'none';
            }, 500);
        }
        
        function formatNumber(number) {
            return new Intl.NumberFormat().format(Math.round(number));
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadLowStockAlerts();
            
            // Auto-refresh stats every minute
            setInterval(() => {
                loadStats();
                loadLowStockAlerts();
            }, 60000);
        });
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                updateComponents();
            }
        });
    </script>
</body>
</html>
