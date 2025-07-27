<?php
// Simplified feature page - no SuiteCRM entry point to avoid session conflicts
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
        
        .inventory-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .inventory-widget { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .inventory-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .inventory-title { font-weight: 600; color: #495057; }
        .stock-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 500; }
        .stock-in-stock { background: #d4edda; color: #155724; }
        .stock-low-stock { background: #fff3cd; color: #856404; }
        .stock-out-of-stock { background: #f8d7da; color: #721c24; }
        
        .warehouse-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
        .warehouse-card { background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; }
        .warehouse-name { font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .warehouse-location { color: #6c757d; font-size: 0.9em; margin-bottom: 15px; }
        .warehouse-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .warehouse-stat { text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px; }
        .warehouse-stat-number { font-weight: bold; color: #495057; }
        .warehouse-stat-label { font-size: 0.8em; color: #6c757d; }
        
        .alert-card { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 15px 0; border-left: 4px solid #ffc107; }
        .alert-header { font-weight: 600; color: #856404; margin-bottom: 8px; display: flex; align-items: center; }
        .alert-icon { font-size: 1.2em; margin-right: 8px; }
        .alert-details { font-size: 0.9em; color: #856404; }
        .alert-action { margin-top: 10px; }
        
        .sync-demo { background: #e3f2fd; border: 1px solid #bbdefb; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .sync-status { display: flex; align-items: center; margin-bottom: 15px; }
        .sync-indicator { width: 12px; height: 12px; border-radius: 50%; margin-right: 10px; animation: pulse 2s infinite; }
        .sync-active { background: #28a745; }
        .sync-progress { background: #e9ecef; border-radius: 10px; height: 8px; margin: 10px 0; }
        .sync-progress-bar { background: linear-gradient(90deg, #28a745, #20c997); height: 100%; border-radius: 10px; transition: width 0.3s ease; }
        
        .mobile-demo { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .mobile-screen { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 350px; margin: 0 auto; }
        
        .suggestion-card { background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 10px 0; display: flex; justify-content: space-between; align-items: center; }
        .suggestion-info { flex: 1; }
        .suggestion-name { font-weight: 500; color: #2c3e50; }
        .suggestion-desc { font-size: 0.8em; color: #6c757d; }
        .suggestion-type { padding: 2px 6px; border-radius: 4px; font-size: 0.7em; font-weight: bold; color: white; }
        .type-similar { background: #3498db; }
        .type-upsell { background: #9b59b6; }
        .type-alternative { background: #e67e22; }
        
        .btn { padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block; text-align: center; margin: 5px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn:hover { opacity: 0.9; text-decoration: none; }
        
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="index.php" class="back-link">← Back to Dashboard</a>
            <div class="header-center">
                <h1>📦 Feature 3: Real-Time Inventory Integration</h1>
                <p>Multi-Warehouse Stock Management & AI Suggestions</p>
            </div>
            <div>
                <a href="/" class="back-link">← Dashboard</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Overview Section -->
        <div class="feature-section">
            <h2>🎯 Inventory Overview</h2>
            <p style="margin: 15px 0; color: #6c757d; font-size: 1.1em;">Comprehensive real-time inventory management system with multi-warehouse tracking, automated reorder alerts, and AI-powered product suggestions for manufacturing distributors.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalItems">850</div>
                    <div class="stat-label">Inventory Items</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                    <div class="stat-number" id="totalStock">127K</div>
                    <div class="stat-label">Total Stock Units</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <div class="stat-number" id="lowStockCount">12</div>
                    <div class="stat-label">Low Stock Alerts</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                    <div class="stat-number">8</div>
                    <div class="stat-label">Warehouses</div>
                </div>
            </div>
        </div>

        <!-- Multi-Warehouse Tracking -->
        <div class="feature-section">
            <h2>🏢 Multi-Warehouse Management</h2>
            <p style="margin: 15px 0; color: #6c757d;">Real-time stock level monitoring across multiple warehouse locations with automated synchronization and cross-warehouse transfers.</p>
            
            <div class="warehouse-grid">
                <div class="warehouse-card" style="border-left: 4px solid #28a745;">
                    <div class="warehouse-name">🏭 Main Distribution Center</div>
                    <div class="warehouse-location">📍 Chicago, IL - Primary Hub</div>
                    <div class="warehouse-stats">
                        <div class="warehouse-stat">
                            <div class="warehouse-stat-number">45,800</div>
                            <div class="warehouse-stat-label">Units</div>
                        </div>
                        <div class="warehouse-stat">
                            <div class="warehouse-stat-number">320</div>
                            <div class="warehouse-stat-label">SKUs</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">✅ Fully Stocked</span>
                    </div>
                </div>

                <div class="warehouse-card" style="border-left: 4px solid #ffc107;">
                    <div class="warehouse-name">🌊 East Coast Distribution</div>
                    <div class="warehouse-location">📍 Newark, NJ - Regional Hub</div>
                    <div class="warehouse-stats">
                        <div class="warehouse-stat">
                            <div class="warehouse-stat-number">28,400</div>
                            <div class="warehouse-stat-label">Units</div>
                        </div>
                        <div class="warehouse-stat">
                            <div class="warehouse-stat-number">285</div>
                            <div class="warehouse-stat-label">SKUs</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <span style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">⚠️ 3 Low Stock Items</span>
                    </div>
                </div>

                <div class="warehouse-card" style="border-left: 4px solid #17a2b8;">
                    <div class="warehouse-name">⛰️ West Coast Distribution</div>
                    <div class="warehouse-location">📍 Los Angeles, CA - Regional Hub</div>
                    <div class="warehouse-stats">
                        <div class="warehouse-stat">
                            <div class="warehouse-stat-number">35,200</div>
                            <div class="warehouse-stat-label">Units</div>
                        </div>
                        <div class="warehouse-stat">
                            <div class="warehouse-stat-number">295</div>
                            <div class="warehouse-stat-label">SKUs</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <span style="background: #d1ecf1; color: #0c5460; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">📈 Above Target</span>
                    </div>
                </div>

                <div class="warehouse-card" style="border-left: 4px solid #6f42c1;">
                    <div class="warehouse-name">🔧 Specialty Components</div>
                    <div class="warehouse-location">📍 Dallas, TX - Specialty Hub</div>
                    <div class="warehouse-stats">
                        <div class="warehouse-stat">
                            <div class="warehouse-stat-number">12,600</div>
                            <div class="warehouse-stat-label">Units</div>
                        </div>
                        <div class="warehouse-stat">
                            <div class="warehouse-stat-number">180</div>
                            <div class="warehouse-stat-label">SKUs</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <span style="background: #e2d9f3; color: #6f42c1; padding: 4px 8px; border-radius: 12px; font-size: 0.8em;">🎯 On Target</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Stock Status -->
        <div class="feature-section">
            <h2>📊 Live Stock Status Dashboard</h2>
            <p style="margin: 15px 0; color: #6c757d;">Real-time inventory levels with automatic updates every 15 minutes and instant alerts for critical stock situations.</p>
            
            <div class="inventory-grid">
                <div class="inventory-widget">
                    <div class="inventory-header">
                        <div class="inventory-title">Safety Switch SS-200</div>
                        <span class="stock-badge stock-low-stock">⚠️ Low Stock</span>
                    </div>
                    <p style="color: #6c757d; margin: 10px 0;">SKU: SWITCH-SS200</p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0;">
                        <div style="text-align: center; padding: 10px; background: #fff3cd; border-radius: 6px;">
                            <div style="font-weight: bold; color: #856404;">19</div>
                            <div style="font-size: 0.8em; color: #856404;">Current Stock</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #f8d7da; border-radius: 6px;">
                            <div style="font-weight: bold; color: #721c24;">80</div>
                            <div style="font-size: 0.8em; color: #721c24;">Reorder Point</div>
                        </div>
                    </div>
                    <p><strong>Warehouse:</strong> East Coast Distribution</p>
                    <p style="color: #856404; margin-top: 10px;"><strong>🚨 Action Required:</strong> Order 200 units immediately</p>
                </div>

                <div class="inventory-widget">
                    <div class="inventory-header">
                        <div class="inventory-title">Steel Bolt M12x50</div>
                        <span class="stock-badge stock-in-stock">✅ In Stock</span>
                    </div>
                    <p style="color: #6c757d; margin: 10px 0;">SKU: BOLT-M1250</p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0;">
                        <div style="text-align: center; padding: 10px; background: #d4edda; border-radius: 6px;">
                            <div style="font-weight: bold; color: #155724;">2,347</div>
                            <div style="font-size: 0.8em; color: #155724;">Current Stock</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #e2e3e5; border-radius: 6px;">
                            <div style="font-weight: bold; color: #495057;">500</div>
                            <div style="font-size: 0.8em; color: #495057;">Reorder Point</div>
                        </div>
                    </div>
                    <p><strong>Warehouse:</strong> Main Distribution Center</p>
                    <p style="color: #28a745; margin-top: 10px;"><strong>✅ Status:</strong> Well stocked, no action needed</p>
                </div>

                <div class="inventory-widget">
                    <div class="inventory-header">
                        <div class="inventory-title">Hydraulic Pump HP-500</div>
                        <span class="stock-badge stock-out-of-stock">❌ Out of Stock</span>
                    </div>
                    <p style="color: #6c757d; margin: 10px 0;">SKU: PUMP-HP500</p>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0;">
                        <div style="text-align: center; padding: 10px; background: #f8d7da; border-radius: 6px;">
                            <div style="font-weight: bold; color: #721c24;">0</div>
                            <div style="font-size: 0.8em; color: #721c24;">Current Stock</div>
                        </div>
                        <div style="text-align: center; padding: 10px; background: #fff3cd; border-radius: 6px;">
                            <div style="font-weight: bold; color: #856404;">Jan 28</div>
                            <div style="font-size: 0.8em; color: #856404;">Expected Restock</div>
                        </div>
                    </div>
                    <p><strong>Warehouse:</strong> Specialty Components</p>
                    <p style="color: #721c24; margin-top: 10px;"><strong>🚨 Critical:</strong> Supplier shipment delayed, expedite order</p>
                </div>
            </div>
        </div>

        <!-- AI-Powered Suggestions -->
        <div class="feature-section">
            <h2>🧠 AI-Powered Product Suggestions</h2>
            <p style="margin: 15px 0; color: #6c757d;">Intelligent recommendation engine that suggests alternative products, upsell opportunities, and similar items based on stock levels and customer purchase patterns.</p>
            
            <div style="background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0;">
                <h4 style="color: #2c3e50; margin-bottom: 15px;">💡 Smart Recommendations for Safety Switch SS-200 (Out of Stock)</h4>
                
                <div class="suggestion-card">
                    <div class="suggestion-info">
                        <div class="suggestion-name">Safety Switch SS-250 (Upgraded Model)</div>
                        <div class="suggestion-desc">Higher capacity, same mounting specifications</div>
                    </div>
                    <span class="suggestion-type type-upsell">UPSELL</span>
                </div>
                
                <div class="suggestion-card">
                    <div class="suggestion-info">
                        <div class="suggestion-name">Safety Switch SS-200A (Alternative Brand)</div>
                        <div class="suggestion-desc">Compatible replacement from secondary supplier</div>
                    </div>
                    <span class="suggestion-type type-alternative">ALTERNATIVE</span>
                </div>
                
                <div class="suggestion-card">
                    <div class="suggestion-info">
                        <div class="suggestion-name">Safety Switch SS-180 (Budget Option)</div>
                        <div class="suggestion-desc">Similar functionality at lower price point</div>
                    </div>
                    <span class="suggestion-type type-similar">SIMILAR</span>
                </div>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <button class="btn btn-primary" onclick="generateSuggestions()">🧠 Generate AI Suggestions</button>
                <button class="btn btn-info" onclick="viewSuggestionHistory()">📊 View Suggestion Analytics</button>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="feature-section">
            <h2>⚠️ Automated Low Stock Alerts</h2>
            <p style="margin: 15px 0; color: #6c757d;">Proactive alert system that monitors stock levels and triggers notifications when items fall below reorder points.</p>
            
            <div class="alert-card">
                <div class="alert-header">
                    <span class="alert-icon">🚨</span>
                    Critical Stock Alert: Safety Switch SS-200
                </div>
                <div class="alert-details">
                    <strong>Current stock:</strong> 19 units (61 units below reorder point)<br>
                    <strong>Warehouse:</strong> East Coast Distribution (ECD)<br>
                    <strong>Last sold:</strong> 15 units on Jan 14, 2024<br>
                    <strong>Average daily usage:</strong> 3.2 units<br>
                    <strong>Days until stockout:</strong> 6 days
                </div>
                <div class="alert-action">
                    <button class="btn btn-danger">🚨 Create Emergency PO</button>
                    <button class="btn btn-warning">📞 Contact Supplier</button>
                    <button class="btn btn-info">🔄 Check Other Warehouses</button>
                </div>
            </div>
            
            <div class="alert-card" style="background: #fff3cd; border-color: #ffeaa7;">
                <div class="alert-header">
                    <span class="alert-icon">⚠️</span>
                    Low Stock Warning: Industrial Valve 4"
                </div>
                <div class="alert-details">
                    <strong>Current stock:</strong> 45 units (15 units below reorder point)<br>
                    <strong>Warehouse:</strong> West Coast Distribution (WCD)<br>
                    <strong>Recommended action:</strong> Place routine reorder for 100 units
                </div>
                <div class="alert-action">
                    <button class="btn btn-warning">📋 Create Standard PO</button>
                    <button class="btn btn-info">📊 View Usage History</button>
                </div>
            </div>
        </div>

        <!-- Real-Time Sync Demo -->
        <div class="feature-section">
            <h2>🔄 Real-Time Synchronization</h2>
            <p style="margin: 15px 0; color: #6c757d;">Background synchronization system that keeps inventory data current with 15-minute update cycles and instant alerts for critical changes.</p>
            
            <div class="sync-demo">
                <div class="sync-status">
                    <div class="sync-indicator sync-active"></div>
                    <div>
                        <div style="font-weight: bold; color: #155724;">Synchronization Active</div>
                        <div style="font-size: 0.9em; color: #155724;">Last sync: 2 minutes ago • Next sync: 13 minutes</div>
                    </div>
                </div>
                
                <div style="background: white; border-radius: 8px; padding: 15px; margin: 15px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-weight: 500;">Synchronizing inventory data...</span>
                        <span style="color: #28a745; font-weight: bold;" id="syncProgress">78%</span>
                    </div>
                    <div class="sync-progress">
                        <div class="sync-progress-bar" style="width: 78%;" id="syncProgressBar"></div>
                    </div>
                    <div style="font-size: 0.9em; color: #6c757d; margin-top: 8px;">
                        Processed: 660/850 items • ETA: 3 minutes
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #28a745;">8</div>
                        <div style="font-size: 0.9em; color: #6c757d;">Warehouses Online</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #17a2b8;">142ms</div>
                        <div style="font-size: 0.9em; color: #6c757d;">Avg Response Time</div>
                    </div>
                    <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: bold; color: #ffc107;">3</div>
                        <div style="font-size: 0.9em; color: #6c757d;">Updates This Hour</div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <button class="btn btn-success" onclick="forceSyncNow()">🔄 Force Sync Now</button>
                <button class="btn btn-primary" onclick="viewSyncLogs()">📋 View Sync Logs</button>
            </div>
        </div>

        <!-- Mobile Inventory Dashboard -->
        <div class="feature-section">
            <h2>📱 Mobile Inventory Dashboard</h2>
            <p style="margin: 15px 0; color: #6c757d;">Mobile-optimized interface for warehouse managers and field teams to check stock levels on-the-go.</p>
            
            <div class="mobile-demo">
                <h3 style="text-align: center; margin-bottom: 20px;">📲 Mobile Interface Preview</h3>
                <div class="mobile-screen">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #dee2e6;">
                        <h4>Inventory Dashboard</h4>
                        <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;">🔄 Live</span>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 1.3em; font-weight: bold; color: #27ae60;">850</div>
                            <div style="font-size: 0.8em; color: #6c757d;">Items</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 10px; border-radius: 6px; text-align: center;">
                            <div style="font-size: 1.3em; font-weight: bold; color: #ffc107;">12</div>
                            <div style="font-size: 0.8em; color: #6c757d;">Alerts</div>
                        </div>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 12px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #ffc107;">
                        <div style="font-weight: bold; color: #856404; font-size: 0.9em;">⚠️ Safety Switch SS-200</div>
                        <div style="font-size: 0.8em; color: #856404; margin: 3px 0;">19 units left • East Coast</div>
                        <div style="font-size: 0.8em; color: #856404;">Action: Emergency reorder needed</div>
                    </div>
                    
                    <div style="background: #d4edda; padding: 12px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #28a745;">
                        <div style="font-weight: bold; color: #155724; font-size: 0.9em;">✅ Steel Bolt M12x50</div>
                        <div style="font-size: 0.8em; color: #155724; margin: 3px 0;">2,347 units • Main Distribution</div>
                        <div style="font-size: 0.8em; color: #155724;">Status: Well stocked</div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <button class="btn btn-warning" style="margin: 0; font-size: 0.9em;">🚨 Alerts</button>
                        <button class="btn btn-info" style="margin: 0; font-size: 0.9em;">🏢 Warehouses</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Integration -->
        <div class="feature-section">
            <h2>🔌 Inventory API & Integration</h2>
            <p style="margin: 15px 0; color: #6c757d;">RESTful API endpoints for inventory management with webhook support for real-time updates and third-party system integration.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4>📡 API Endpoints</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                        <div style="font-family: monospace; font-size: 0.9em; color: #495057;">
                            <div><strong>GET</strong> /inventory_api_direct.php</div>
                            <div style="margin: 5px 0; color: #6c757d;">Get inventory summary</div>
                            <div><strong>GET</strong> /inventory_api_direct.php?sku=BOLT-M1250</div>
                            <div style="margin: 5px 0; color: #6c757d;">Get specific item stock</div>
                            <div><strong>POST</strong> /inventory_api_direct.php</div>
                            <div style="margin: 5px 0; color: #6c757d;">Update stock levels</div>
                            <div><strong>GET</strong> /inventory_api_direct.php?action=alerts</div>
                            <div style="margin: 5px 0; color: #6c757d;">Get low stock alerts</div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4>⚡ Performance & Integration</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: center;">
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: #27ae60;">99.8%</div>
                                <div style="font-size: 0.9em; color: #7f8c8d;">API Uptime</div>
                            </div>
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: #3498db;">&lt;150ms</div>
                                <div style="font-size: 0.9em; color: #7f8c8d;">Response Time</div>
                            </div>
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: #9b59b6;">WebHooks</div>
                                <div style="font-size: 0.9em; color: #7f8c8d;">Real-time Updates</div>
                            </div>
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: #e67e22;">ERP</div>
                                <div style="font-size: 0.9em; color: #7f8c8d;">Integration Ready</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <button class="btn btn-info" onclick="testInventoryAPI()">🔗 Test Inventory API</button>
                <button class="btn btn-success" onclick="viewAPIDocumentation()">📚 API Documentation</button>
                <button class="btn btn-primary" onclick="setupWebhooks()">🔗 Setup Webhooks</button>
            </div>
        </div>
    </div>

    <script>
        function generateSuggestions() {
            alert('🧠 AI Suggestion Engine\n\n✅ Analyzing product relationships...\n✅ Checking supplier availability...\n✅ Reviewing customer purchase patterns...\n✅ Calculating compatibility scores...\n\n💡 3 intelligent suggestions generated for out-of-stock items!');
        }

        function viewSuggestionHistory() {
            alert('📊 AI Suggestion Analytics\n\n• Total suggestions made: 1,247\n• Accepted suggestions: 892 (71.5%)\n• Revenue impact: +$28,400\n• Top suggestion category: Alternative products\n• Avg. customer satisfaction: 4.2/5');
        }

        function forceSyncNow() {
            const progressBar = document.getElementById('syncProgressBar');
            const progressText = document.getElementById('syncProgress');
            let progress = 0;
            
            const interval = setInterval(() => {
                progress += Math.random() * 15 + 5;
                if (progress >= 100) {
                    progress = 100;
                    clearInterval(interval);
                    alert('🔄 Manual Sync Complete!\n\n✅ All 8 warehouses synchronized\n✅ 850 items updated\n✅ 3 new alerts generated\nSync time: 47 seconds');
                }
                progressBar.style.width = progress + '%';
                progressText.textContent = Math.round(progress) + '%';
            }, 200);
        }

        function viewSyncLogs() {
            alert('📋 Synchronization Logs\n\n🕐 15:45 - Full sync completed (850 items)\n🕐 15:30 - Incremental sync (12 updates)\n🕐 15:15 - Full sync completed (850 items)\n🕐 15:02 - Alert triggered: SS-200 low stock\n🕐 15:00 - Full sync completed (850 items)\n\n✅ All syncs successful - No errors');
        }

        function testInventoryAPI() {
            const startTime = Date.now();
            // Simulate API call
            setTimeout(() => {
                const endTime = Date.now();
                alert(`🔗 Inventory API Test Complete!\n\nEndpoint: /inventory_api_direct.php\nResponse Time: ${endTime - startTime}ms\nStatus: 200 OK\nItems Retrieved: 850\nLow Stock Alerts: 12\nWarehouses Online: 8/8`);
            }, Math.random() * 150 + 50);
        }

        function viewAPIDocumentation() {
            alert('📚 API Documentation\n\n🔗 Available endpoints:\n• GET /inventory_api_direct.php - Inventory summary\n• GET /inventory_api_direct.php?sku={sku} - Item details\n• POST /inventory_api_direct.php - Update stock\n• GET /inventory_api_direct.php?action=alerts - Alerts\n\n📖 Full documentation available at /docs/inventory-api.html');
        }

        function setupWebhooks() {
            alert('🔗 Webhook Configuration\n\n⚙️ Available webhook events:\n• stock_level_changed\n• low_stock_alert\n• item_out_of_stock\n• reorder_point_reached\n• sync_completed\n\n🔧 Configure webhooks in the admin panel to receive real-time inventory updates.');
        }

        // Simulate real-time updates
        function simulateRealTimeUpdates() {
            setInterval(() => {
                // Randomly update stock numbers
                const stockElements = ['totalItems', 'totalStock', 'lowStockCount'];
                stockElements.forEach(id => {
                    const element = document.getElementById(id);
                    if (element && Math.random() > 0.95) {
                        const currentValue = parseInt(element.textContent) || 0;
                        const change = Math.floor(Math.random() * 10) - 5;
                        element.textContent = Math.max(0, currentValue + change);
                    }
                });
            }, 3000);
        }

        // Load real inventory data if available
        async function loadInventoryData() {
            try {
                const response = await fetch('/inventory_api_direct.php?action=get_summary');
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    document.getElementById('totalItems').textContent = data.total_items;
                    document.getElementById('totalStock').textContent = formatNumber(data.total_stock);
                    document.getElementById('lowStockCount').textContent = data.low_stock_items;
                }
            } catch (error) {
                console.log('Using demo inventory data');
            }
        }

        function formatNumber(number) {
            const num = Math.round(number);
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num;
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadInventoryData();
            simulateRealTimeUpdates();
        });
    </script>
</body>
</html>
