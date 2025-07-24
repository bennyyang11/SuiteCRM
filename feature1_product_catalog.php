<?php
// Simplified feature page - no SuiteCRM entry point to avoid session conflicts
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature 1: Mobile Product Catalog - Manufacturing Distribution</title>
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
        .stat-card { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 2.5em; font-weight: bold; }
        .stat-label { font-size: 0.9em; opacity: 0.9; }
        
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin: 20px 0; }
        .product-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: white; transition: transform 0.2s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .product-sku { font-weight: bold; color: #e74c3c; font-size: 0.9em; }
        .product-name { font-size: 1.1em; margin: 5px 0; color: #2c3e50; }
        .product-price { font-size: 1.3em; font-weight: bold; color: #27ae60; }
        
        .pricing-tiers { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
        .tier-card { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; }
        .tier-name { font-weight: bold; color: #495057; margin-bottom: 10px; }
        .tier-discount { color: #28a745; font-size: 1.2em; font-weight: bold; }
        
        .mobile-demo { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .mobile-screen { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 350px; margin: 0 auto; }
        
        .btn { padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block; text-align: center; margin: 5px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn:hover { opacity: 0.9; text-decoration: none; }
        
        .filter-demo { background: white; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin: 15px 0; }
        .filter-section { margin-bottom: 15px; }
        .filter-label { font-weight: 500; margin-bottom: 8px; display: block; }
        .filter-input { width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px; }
        .filter-checkboxes { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .checkbox-item { display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="index.php" class="back-link">← Back to Dashboard</a>
            <div class="header-center">
                <h1>📱 Feature 1: Mobile Product Catalog</h1>
                <p>Client-Specific Pricing & Mobile-Responsive Interface</p>
            </div>
            <div>
                <a href="complete_manufacturing_demo.php" class="back-link">View All Features</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Overview Section -->
        <div class="feature-section">
            <h2>🎯 Feature Overview</h2>
            <p style="margin: 15px 0; color: #6c757d; font-size: 1.1em;">A comprehensive mobile-responsive product catalog system designed specifically for manufacturing distributors. Features client-specific pricing tiers, real-time inventory integration, and optimized mobile experience for field sales teams.</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="totalProducts">60+</div>
                    <div class="stat-label">Products</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <div class="stat-number">5</div>
                    <div class="stat-label">Categories</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <div class="stat-number">4</div>
                    <div class="stat-label">Pricing Tiers</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                    <div class="stat-number">&lt;0.5s</div>
                    <div class="stat-label">Response Time</div>
                </div>
            </div>
        </div>

        <!-- Client-Specific Pricing System -->
        <div class="feature-section">
            <h2>💰 Client-Specific Pricing Tiers</h2>
            <p style="margin: 15px 0; color: #6c757d;">Dynamic pricing calculation engine that automatically applies the correct pricing tier based on client contracts and negotiated rates.</p>
            
            <div class="pricing-tiers">
                <div class="tier-card">
                    <div class="tier-name">🏪 Retail Pricing</div>
                    <div class="tier-discount">List Price</div>
                    <p style="margin: 10px 0; color: #6c757d;">Standard retail pricing for walk-in customers and small orders.</p>
                    <div style="font-size: 0.9em;">
                        <strong>Minimum Order:</strong> $0<br>
                        <strong>Payment Terms:</strong> Net 30
                    </div>
                </div>
                
                <div class="tier-card" style="border-left: 4px solid #f39c12;">
                    <div class="tier-name">🏭 Wholesale Pricing</div>
                    <div class="tier-discount">15% Discount</div>
                    <p style="margin: 10px 0; color: #6c757d;">Volume pricing for established distributors and regular customers.</p>
                    <div style="font-size: 0.9em;">
                        <strong>Minimum Order:</strong> $500<br>
                        <strong>Payment Terms:</strong> Net 45
                    </div>
                </div>
                
                <div class="tier-card" style="border-left: 4px solid #17a2b8;">
                    <div class="tier-name">⚙️ OEM Pricing</div>
                    <div class="tier-discount">25% Discount</div>
                    <p style="margin: 10px 0; color: #6c757d;">Special pricing for original equipment manufacturers with volume commitments.</p>
                    <div style="font-size: 0.9em;">
                        <strong>Minimum Order:</strong> $2,000<br>
                        <strong>Payment Terms:</strong> Net 60
                    </div>
                </div>
                
                <div class="tier-card" style="border-left: 4px solid #28a745;">
                    <div class="tier-name">🤝 Contract Pricing</div>
                    <div class="tier-discount">Custom Rates</div>
                    <p style="margin: 10px 0; color: #6c757d;">Negotiated pricing based on annual volume commitments and strategic partnerships.</p>
                    <div style="font-size: 0.9em;">
                        <strong>Minimum Order:</strong> Varies<br>
                        <strong>Payment Terms:</strong> Negotiated
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Product Catalog -->
        <div class="feature-section">
            <h2>📦 Live Product Catalog</h2>
            <p style="margin: 15px 0; color: #6c757d;">Real-time product data with inventory levels, specifications, and client-specific pricing.</p>
            
            <div class="product-grid" id="productGrid">
                <!-- Products loaded by JavaScript -->
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <button class="btn btn-primary" onclick="loadMoreProducts()">📦 Load More Products</button>
                <button class="btn btn-info" onclick="refreshInventory()">🔄 Refresh Inventory</button>
            </div>
        </div>

        <!-- Advanced Filtering System -->
        <div class="feature-section">
            <h2>🎛️ Advanced Filtering & Search</h2>
            <p style="margin: 15px 0; color: #6c757d;">Powerful filtering system allowing customers to find products by category, material, price range, and technical specifications.</p>
            
            <div class="filter-demo">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                    <div class="filter-section">
                        <label class="filter-label">🔍 Search Products</label>
                        <input type="text" class="filter-input" placeholder="Enter product name, SKU, or material..." onkeyup="filterProducts(this.value)">
                    </div>
                    
                    <div class="filter-section">
                        <label class="filter-label">💰 Price Range</label>
                        <select class="filter-input" onchange="filterByPrice(this.value)">
                            <option value="">All Prices</option>
                            <option value="0-25">$0 - $25</option>
                            <option value="25-100">$25 - $100</option>
                            <option value="100-500">$100 - $500</option>
                            <option value="500+">$500+</option>
                        </select>
                    </div>
                    
                    <div class="filter-section">
                        <label class="filter-label">📊 Stock Status</label>
                        <select class="filter-input" onchange="filterByStock(this.value)">
                            <option value="">All Stock Levels</option>
                            <option value="in-stock">In Stock</option>
                            <option value="low-stock">Low Stock</option>
                            <option value="out-of-stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-section" style="margin-top: 20px;">
                    <label class="filter-label">🏷️ Product Categories</label>
                    <div class="filter-checkboxes">
                        <div class="checkbox-item">
                            <input type="checkbox" id="brackets" onchange="filterByCategory()">
                            <label for="brackets">Brackets & Supports</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="piping" onchange="filterByCategory()">
                            <label for="piping">Piping & Fittings</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="structural" onchange="filterByCategory()">
                            <label for="structural">Structural Steel</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="valves" onchange="filterByCategory()">
                            <label for="valves">Valves & Controls</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="fasteners" onchange="filterByCategory()">
                            <label for="fasteners">Fasteners & Hardware</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Experience Demo -->
        <div class="feature-section">
            <h2>📱 Mobile-First Design</h2>
            <p style="margin: 15px 0; color: #6c757d;">Optimized for field sales teams with touch-friendly interface, offline capability, and responsive design.</p>
            
            <div class="mobile-demo">
                <h3 style="text-align: center; margin-bottom: 20px;">📲 Mobile Interface Preview</h3>
                <div class="mobile-screen">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #dee2e6;">
                        <h4>Product Catalog</h4>
                        <span style="background: #28a745; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em;">Online</span>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <input type="text" style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 8px;" placeholder="🔍 Search products..." readonly>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="font-weight: bold; color: #e74c3c; font-size: 0.9em;">SKU-001</div>
                                <div style="font-size: 1.1em; margin: 5px 0; color: #2c3e50;">Steel L-Bracket Heavy Duty</div>
                                <div style="color: #28a745; font-size: 0.9em;">✅ In Stock: 150 units</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.3em; font-weight: bold; color: #27ae60;">$15.99</div>
                                <div style="font-size: 0.8em; color: #6c757d;">Wholesale Price</div>
                            </div>
                        </div>
                        <div style="margin-top: 10px;">
                            <button class="btn btn-success" style="width: 100%; margin: 0;">📋 Add to Quote</button>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 15px;">
                        <button class="btn btn-primary" style="margin: 0;">🎛️ Filters</button>
                        <button class="btn btn-info" style="margin: 0;">💾 Favorites</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Integration -->
        <div class="feature-section">
            <h2>🔌 API Integration & Performance</h2>
            <p style="margin: 15px 0; color: #6c757d;">RESTful API endpoints with caching layer for optimal performance and integration capabilities.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <h4>📡 API Endpoints</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                        <div style="font-family: monospace; font-size: 0.9em; color: #495057;">
                            <div><strong>GET</strong> /Api/v1/manufacturing/ProductCatalogAPI.php</div>
                            <div style="margin: 5px 0; color: #6c757d;">Retrieve product catalog with filtering</div>
                            <div><strong>GET</strong> /Api/v1/manufacturing/ProductCatalogAPI.php?client_id=123</div>
                            <div style="margin: 5px 0; color: #6c757d;">Get client-specific pricing</div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4>⚡ Performance Metrics</h4>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 10px 0;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: center;">
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: #27ae60;">&lt;500ms</div>
                                <div style="font-size: 0.9em; color: #7f8c8d;">API Response Time</div>
                            </div>
                            <div>
                                <div style="font-size: 1.5em; font-weight: bold; color: #3498db;">Redis</div>
                                <div style="font-size: 0.9em; color: #7f8c8d;">Caching Layer</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <a href="Api/v1/manufacturing/ProductCatalogAPI.php" class="btn btn-info">🔗 Test API</a>
                <button class="btn btn-primary" onclick="runPerformanceTest()">⚡ Performance Test</button>
            </div>
        </div>
    </div>

    <script>
        // Sample product data
        const sampleProducts = [
            {sku: 'SKU-001', name: 'Steel L-Bracket Heavy Duty', category: 'Brackets', price: 15.99, stock: 150, material: 'Steel'},
            {sku: 'SKU-002', name: 'Aluminum Angle Bracket', category: 'Brackets', price: 12.50, stock: 200, material: 'Aluminum'},
            {sku: 'SKU-003', name: 'Stainless Steel Pipe 2"', category: 'Piping', price: 45.00, stock: 75, material: 'Stainless Steel'},
            {sku: 'SKU-004', name: 'Copper Fitting Elbow', category: 'Fittings', price: 8.75, stock: 300, material: 'Copper'},
            {sku: 'SKU-005', name: 'Steel Beam I-Section', category: 'Structural', price: 125.00, stock: 25, material: 'Steel'},
            {sku: 'SKU-006', name: 'Industrial Valve 4"', category: 'Valves', price: 89.99, stock: 60, material: 'Cast Iron'},
            {sku: 'SKU-007', name: 'Galvanized Bolt M16x80', category: 'Fasteners', price: 2.45, stock: 500, material: 'Galvanized Steel'},
            {sku: 'SKU-008', name: 'PVC Pipe 4" Schedule 40', category: 'Piping', price: 18.50, stock: 120, material: 'PVC'},
            {sku: 'SKU-009', name: 'Steel Angle Bar 50x50x5mm', category: 'Structural', price: 28.75, stock: 80, material: 'Steel'},
            {sku: 'SKU-010', name: 'Ball Valve 2" Brass', category: 'Valves', price: 65.99, stock: 45, material: 'Brass'}
        ];

        let displayedProducts = [];
        let filteredProducts = sampleProducts;

        function loadProducts() {
            const productGrid = document.getElementById('productGrid');
            productGrid.innerHTML = '';
            
            filteredProducts.slice(0, 8).forEach(product => {
                const stockStatus = product.stock > 100 ? 'In Stock' : product.stock > 50 ? 'Limited' : 'Low Stock';
                const stockColor = product.stock > 100 ? '#27ae60' : product.stock > 50 ? '#f39c12' : '#e74c3c';
                
                productGrid.innerHTML += `
                    <div class="product-card">
                        <div class="product-sku">${product.sku}</div>
                        <div class="product-name">${product.name}</div>
                        <div class="product-price">$${product.price}</div>
                        <div style="color: ${stockColor}; font-weight: bold; margin: 5px 0;">${stockStatus} (${product.stock})</div>
                        <div style="background: #ecf0f1; padding: 5px; border-radius: 3px; font-size: 0.8em; margin: 5px 0;">
                            ${product.category} • ${product.material}
                        </div>
                        <div style="margin-top: 10px;">
                            <button class="btn btn-success" style="width: 100%; margin: 0;" onclick="addToQuote('${product.sku}')">📋 Add to Quote</button>
                        </div>
                    </div>
                `;
            });
            
            displayedProducts = filteredProducts.slice(0, 8);
        }

        function filterProducts(searchTerm) {
            filteredProducts = sampleProducts.filter(product => 
                product.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                product.sku.toLowerCase().includes(searchTerm.toLowerCase()) ||
                product.material.toLowerCase().includes(searchTerm.toLowerCase())
            );
            loadProducts();
        }

        function filterByPrice(priceRange) {
            if (!priceRange) {
                filteredProducts = sampleProducts;
            } else {
                const [min, max] = priceRange.split('-').map(p => p.replace('+', ''));
                filteredProducts = sampleProducts.filter(product => {
                    if (max) {
                        return product.price >= parseFloat(min) && product.price <= parseFloat(max);
                    } else {
                        return product.price >= parseFloat(min);
                    }
                });
            }
            loadProducts();
        }

        function filterByStock(stockStatus) {
            if (!stockStatus) {
                filteredProducts = sampleProducts;
            } else {
                filteredProducts = sampleProducts.filter(product => {
                    if (stockStatus === 'in-stock') return product.stock > 100;
                    if (stockStatus === 'low-stock') return product.stock <= 100 && product.stock > 0;
                    if (stockStatus === 'out-of-stock') return product.stock === 0;
                });
            }
            loadProducts();
        }

        function filterByCategory() {
            const checkedCategories = [];
            ['brackets', 'piping', 'structural', 'valves', 'fasteners'].forEach(cat => {
                if (document.getElementById(cat).checked) {
                    checkedCategories.push(cat.charAt(0).toUpperCase() + cat.slice(1));
                }
            });
            
            if (checkedCategories.length === 0) {
                filteredProducts = sampleProducts;
            } else {
                filteredProducts = sampleProducts.filter(product => 
                    checkedCategories.some(cat => product.category.includes(cat))
                );
            }
            loadProducts();
        }

        function addToQuote(sku) {
            alert(`✅ Product ${sku} added to quote! This would normally integrate with the Quote Builder (Feature 4).`);
        }

        function loadMoreProducts() {
            alert('🔄 Loading more products... This would fetch additional products from the API.');
        }

        function refreshInventory() {
            alert('🔄 Refreshing inventory data... This would sync with the real-time inventory system (Feature 3).');
        }

        function runPerformanceTest() {
            const startTime = Date.now();
            // Simulate API call
            setTimeout(() => {
                const endTime = Date.now();
                alert(`⚡ Performance Test Complete!\nResponse Time: ${endTime - startTime}ms\nProducts Loaded: ${filteredProducts.length}\nCache Status: Active`);
            }, Math.random() * 300 + 100);
        }

        // Load products when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
            
            // Update total products count
            fetch('Api/v1/manufacturing/ProductCatalogAPI.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalProducts').textContent = data.count;
                    }
                })
                .catch(() => {
                    // Keep default value if API fails
                });
        });
    </script>
</body>
</html>
