<?php
/**
 * Real-Time Inventory Integration with Live Database Data
 * Manufacturing Distribution Platform - Feature 3 Enhanced
 */

require_once('config.php');

// Database connection
global $sugar_config;
$host = str_replace(':3307', '', $sugar_config['dbconfig']['db_host_name']);
$db = new mysqli(
    $host,
    $sugar_config['dbconfig']['db_user_name'],
    $sugar_config['dbconfig']['db_password'],
    $sugar_config['dbconfig']['db_name'],
    3307
);

// Get real inventory data
$inventory_data = [];
$summary_stats = [
    'total_items' => 0,
    'total_stock' => 0,
    'low_stock_items' => 0,
    'out_of_stock_items' => 0
];

if (!$db->connect_error) {
    // Get inventory summary
    $summary_result = $db->query("
        SELECT 
            COUNT(*) as total_items,
            SUM(current_stock) as total_stock,
            COUNT(CASE WHEN stock_status = 'low_stock' THEN 1 END) as low_stock_items,
            COUNT(CASE WHEN stock_status = 'out_of_stock' THEN 1 END) as out_of_stock_items
        FROM mfg_inventory 
        WHERE deleted = 0
    ");
    
    if ($summary_result) {
        $summary_stats = $summary_result->fetch_assoc();
    }
    
    // Get detailed inventory items
    $inventory_result = $db->query("
        SELECT 
            p.name as product_name,
            p.sku,
            p.unit_price,
            i.current_stock,
            i.reorder_point,
            i.stock_status,
            w.name as warehouse_name,
            w.code as warehouse_code
        FROM mfg_inventory i
        JOIN mfg_products p ON i.product_id = p.id
        JOIN mfg_warehouses w ON i.warehouse_id = w.id
        WHERE i.deleted = 0 AND p.deleted = 0 AND w.deleted = 0
        ORDER BY 
            CASE i.stock_status 
                WHEN 'out_of_stock' THEN 1 
                WHEN 'low_stock' THEN 2 
                ELSE 3 
            END,
            i.current_stock ASC
        LIMIT 20
    ");
    
    if ($inventory_result) {
        while ($row = $inventory_result->fetch_assoc()) {
            $inventory_data[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature 3: Real-Time Inventory Integration - Manufacturing Distribution</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 20px; }
        .header-nav { display: flex; align-items: center; justify-content: space-between; max-width: 1200px; margin: 0 auto; }
        .header-center { text-align: center; flex: 1; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.2em; opacity: 0.9; }
        
        .back-link { color: white; text-decoration: none; padding: 10px 15px; border-radius: 6px; background: rgba(255,255,255,0.1); }
        .back-link:hover { background: rgba(255,255,255,0.2); text-decoration: none; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .feature-section { background: white; border-radius: 10px; margin: 20px 0; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: linear-gradient(135deg, #27ae60, #229954); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 2.5em; font-weight: bold; }
        .stat-label { font-size: 0.9em; opacity: 0.9; }
        
        .inventory-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin: 20px 0; }
        .inventory-widget { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .inventory-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .inventory-title { font-weight: 600; color: #495057; }
        .stock-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 500; }
        .stock-in-stock { background: #d4edda; color: #155724; }
        .stock-low-stock { background: #fff3cd; color: #856404; }
        .stock-out-of-stock { background: #f8d7da; color: #721c24; }
        
        .stock-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0; }
        .stock-detail { text-align: center; padding: 10px; border-radius: 6px; }
        .stock-number { font-weight: bold; font-size: 1.1em; }
        .stock-text { font-size: 0.8em; margin-top: 2px; }
        
        .controls { display: flex; gap: 10px; margin: 20px 0; align-items: center; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn:hover { opacity: 0.9; }
        
        .search-box { width: 250px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; }
        .filter-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; }
        
        .no-data { text-align: center; color: #6c757d; padding: 40px; font-style: italic; }
        .error-message { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; margin: 20px 0; }
        
        .live-indicator { display: inline-flex; align-items: center; gap: 5px; color: #28a745; font-size: 0.9em; }
        .live-dot { width: 8px; height: 8px; background: #28a745; border-radius: 50%; animation: pulse 2s infinite; }
        
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="index.php" class="back-link">← Back to Dashboard</a>
            <div class="header-center">
                <h1>📦 Feature 3: Real-Time Inventory Integration</h1>
                <p>Live Database Inventory with Real Stock Levels</p>
            </div>
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span>Live Data</span>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Overview Section -->
        <div class="feature-section">
            <h2>🎯 Live Inventory Overview</h2>
            <p style="margin: 15px 0; color: #6c757d; font-size: 1.1em;">Real-time inventory data pulled directly from the database with actual stock levels and warehouse information.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $summary_stats['total_items'] ?? 0; ?></div>
                    <div class="stat-label">Inventory Items</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <div class="stat-number"><?php echo number_format($summary_stats['total_stock'] ?? 0); ?></div>
                    <div class="stat-label">Total Stock Units</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <div class="stat-number"><?php echo $summary_stats['low_stock_items'] ?? 0; ?></div>
                    <div class="stat-label">Low Stock Alerts</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <div class="stat-number"><?php echo $summary_stats['out_of_stock_items'] ?? 0; ?></div>
                    <div class="stat-label">Out of Stock</div>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="feature-section">
            <div class="controls">
                <input type="text" id="searchBox" class="search-box" placeholder="Search products...">
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
                <button class="btn btn-primary" onclick="refreshInventory()">🔄 Refresh</button>
                <a href="/inventory_purchase_interface.php" class="btn btn-success">🛒 Purchase System</a>
                <a href="/feature3_inventory_integration.php" class="btn btn-info">📊 Demo Version</a>
            </div>
        </div>

        <!-- Live Stock Status -->
        <div class="feature-section">
            <h2>📊 Live Stock Status Dashboard</h2>
            <p style="margin: 15px 0; color: #6c757d;">Real-time inventory levels pulled from the database. Showing items sorted by urgency (out of stock, low stock, then in stock).</p>
            
            <?php if ($db->connect_error): ?>
                <div class="error-message">
                    <strong>Database Connection Error:</strong> Unable to connect to inventory database. 
                    Using Docker environment - some features may be limited.
                </div>
            <?php elseif (empty($inventory_data)): ?>
                <div class="no-data">
                    No inventory data found. Please ensure the database schema has been installed.
                </div>
            <?php else: ?>
                <div class="inventory-grid" id="inventoryGrid">
                    <?php foreach ($inventory_data as $item): ?>
                        <div class="inventory-widget" data-status="<?php echo $item['stock_status']; ?>" data-name="<?php echo strtolower($item['product_name']); ?>">
                            <div class="inventory-header">
                                <div class="inventory-title"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <span class="stock-badge stock-<?php echo $item['stock_status']; ?>">
                                    <?php 
                                    switch($item['stock_status']) {
                                        case 'out_of_stock': echo '❌ Out of Stock'; break;
                                        case 'low_stock': echo '⚠️ Low Stock'; break;
                                        default: echo '✅ In Stock'; break;
                                    }
                                    ?>
                                </span>
                            </div>
                            <p style="color: #6c757d; margin: 10px 0;">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                            <p style="color: #6c757d; margin: 10px 0;">Price: $<?php echo number_format($item['unit_price'], 2); ?></p>
                            
                            <div class="stock-info">
                                <div class="stock-detail" style="background: <?php 
                                    echo $item['stock_status'] === 'out_of_stock' ? '#f8d7da' : 
                                        ($item['stock_status'] === 'low_stock' ? '#fff3cd' : '#d4edda'); 
                                ?>;">
                                    <div class="stock-number" style="color: <?php 
                                        echo $item['stock_status'] === 'out_of_stock' ? '#721c24' : 
                                            ($item['stock_status'] === 'low_stock' ? '#856404' : '#155724'); 
                                    ?>;"><?php echo number_format($item['current_stock']); ?></div>
                                    <div class="stock-text" style="color: <?php 
                                        echo $item['stock_status'] === 'out_of_stock' ? '#721c24' : 
                                            ($item['stock_status'] === 'low_stock' ? '#856404' : '#155724'); 
                                    ?>;">Current Stock</div>
                                </div>
                                <div class="stock-detail" style="background: #e2e3e5;">
                                    <div class="stock-number" style="color: #495057;"><?php echo number_format($item['reorder_point']); ?></div>
                                    <div class="stock-text" style="color: #495057;">Reorder Point</div>
                                </div>
                            </div>
                            
                            <p><strong>Warehouse:</strong> <?php echo htmlspecialchars($item['warehouse_name']); ?> (<?php echo htmlspecialchars($item['warehouse_code']); ?>)</p>
                            
                            <p style="margin-top: 10px; font-weight: 500; color: <?php 
                                echo $item['stock_status'] === 'out_of_stock' ? '#721c24' : 
                                    ($item['stock_status'] === 'low_stock' ? '#856404' : '#28a745'); 
                            ?>;">
                                <strong>
                                    <?php if ($item['stock_status'] === 'out_of_stock'): ?>
                                        🚨 Critical: Immediate restock required
                                    <?php elseif ($item['stock_status'] === 'low_stock'): ?>
                                        ⚠️ Action Required: Order <?php echo max(100, $item['reorder_point'] * 2); ?> units
                                    <?php else: ?>
                                        ✅ Status: Well stocked, no action needed
                                    <?php endif; ?>
                                </strong>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Integration Status -->
        <div class="feature-section">
            <h2>🔌 System Integration Status</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
                    <h4>✅ Database Connection</h4>
                    <p><?php echo $db->connect_error ? 'Error: ' . $db->connect_error : 'Connected successfully'; ?></p>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff;">
                    <h4>📊 Data Source</h4>
                    <p>Live database queries (mfg_inventory, mfg_products, mfg_warehouses)</p>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8;">
                    <h4>🔄 Real-time Updates</h4>
                    <p>Inventory changes reflect immediately after purchases</p>
                </div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #6f42c1;">
                    <h4>🛒 Purchase Integration</h4>
                    <p>Connected to purchase system for live transaction processing</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        document.getElementById('searchBox').addEventListener('input', filterInventory);
        document.getElementById('statusFilter').addEventListener('change', filterInventory);

        function filterInventory() {
            const searchTerm = document.getElementById('searchBox').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const widgets = document.querySelectorAll('.inventory-widget');

            widgets.forEach(widget => {
                const name = widget.getAttribute('data-name');
                const status = widget.getAttribute('data-status');
                
                const matchesSearch = !searchTerm || name.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                
                widget.style.display = (matchesSearch && matchesStatus) ? 'block' : 'none';
            });
        }

        function refreshInventory() {
            window.location.reload();
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshInventory, 30000);
    </script>
</body>
</html>
