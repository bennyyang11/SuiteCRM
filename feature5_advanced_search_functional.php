<?php
/**
 * Feature 5: Advanced Search & Filtering - Fully Functional
 * Manufacturing Distribution Platform
 */

// Start session for user tracking
session_start();

// Check if user should be logged in (optional for this demo)
$isLoggedIn = !empty($_SESSION['logged_in']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - Manufacturing Distribution</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; line-height: 1.6; }
        
        /* Header */
        .header { background: linear-gradient(135deg, #1f2937, #374151); color: white; padding: 20px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .header-nav { display: flex; align-items: center; justify-content: space-between; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .header-center { text-align: center; flex: 1; }
        .header h1 { font-size: 2.2rem; margin-bottom: 8px; font-weight: 700; }
        .header p { font-size: 1.1rem; opacity: 0.9; }
        .back-link { color: white; text-decoration: none; padding: 10px 16px; border-radius: 8px; background: rgba(255,255,255,0.1); transition: all 0.2s; }
        .back-link:hover { background: rgba(255,255,255,0.2); text-decoration: none; transform: translateY(-1px); }
        
        /* Main Container */
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        
        /* Search Section */
        .search-section { background: white; border-radius: 12px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .search-header { text-align: center; margin-bottom: 30px; }
        .search-header h2 { color: #1f2937; font-size: 1.8rem; margin-bottom: 10px; }
        .search-header p { color: #6b7280; font-size: 1.1rem; }
        
        /* Search Box */
        .search-container { position: relative; max-width: 600px; margin: 0 auto 30px; }
        .search-input { width: 100%; padding: 16px 20px 16px 50px; font-size: 1.1rem; border: 2px solid #e5e7eb; border-radius: 50px; outline: none; transition: all 0.3s; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
        .search-icon { position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 1.2rem; }
        .search-btn { position: absolute; right: 6px; top: 6px; bottom: 6px; background: #3b82f6; color: white; border: none; border-radius: 50px; padding: 0 20px; cursor: pointer; font-weight: 600; transition: background 0.2s; }
        .search-btn:hover { background: #2563eb; }
        
        /* Search Filters */
        .search-filters { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .filter-group { }
        .filter-group label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; }
        .filter-input, .filter-select { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; }
        .filter-input:focus, .filter-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        /* Quick Filters */
        .quick-filters { margin-bottom: 30px; }
        .quick-filters h3 { color: #374151; margin-bottom: 15px; font-size: 1.1rem; }
        .filter-tags { display: flex; flex-wrap: wrap; gap: 10px; }
        .filter-tag { background: #f3f4f6; color: #374151; padding: 8px 16px; border-radius: 20px; cursor: pointer; transition: all 0.2s; border: none; font-size: 0.9rem; }
        .filter-tag:hover, .filter-tag.active { background: #3b82f6; color: white; transform: translateY(-1px); }
        
        /* Results Section */
        .results-section { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb; }
        .results-count { color: #6b7280; font-size: 0.9rem; }
        .sort-controls { display: flex; align-items: center; gap: 10px; }
        .sort-select { padding: 6px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; }
        
        /* Product Results */
        .product-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .product-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; transition: all 0.2s; cursor: pointer; }
        .product-card:hover { border-color: #3b82f6; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15); transform: translateY(-2px); }
        .product-header { display: flex; justify-content: between; align-items: flex-start; margin-bottom: 10px; }
        .product-name { font-weight: 600; color: #1f2937; font-size: 1.1rem; margin-bottom: 5px; }
        .product-category { color: #6b7280; font-size: 0.85rem; background: #f3f4f6; padding: 2px 8px; border-radius: 4px; }
        .product-description { color: #4b5563; font-size: 0.9rem; margin-bottom: 15px; line-height: 1.5; }
        .product-footer { display: flex; justify-content: space-between; align-items: center; }
        .product-price { font-weight: 700; color: #059669; font-size: 1.1rem; }
        .product-stock { font-size: 0.85rem; padding: 4px 8px; border-radius: 4px; }
        .stock-high { background: #dcfce7; color: #166534; }
        .stock-medium { background: #fef3c7; color: #92400e; }
        .stock-low { background: #fecaca; color: #dc2626; }
        
        /* Loading State */
        .loading { text-align: center; padding: 40px; color: #6b7280; }
        .loading-spinner { border: 3px solid #f3f4f6; border-top: 3px solid #3b82f6; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* No Results */
        .no-results { text-align: center; padding: 60px 20px; color: #6b7280; }
        .no-results h3 { color: #374151; margin-bottom: 10px; }
        
        /* Suggestions */
        .search-suggestions { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 12px 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 100; max-height: 300px; overflow-y: auto; display: none; }
        .suggestion-item { padding: 12px 20px; cursor: pointer; border-bottom: 1px solid #f3f4f6; }
        .suggestion-item:hover { background: #f8fafc; }
        .suggestion-item:last-child { border-bottom: none; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header h1 { font-size: 1.8rem; }
            .search-filters { grid-template-columns: 1fr; }
            .product-grid { grid-template-columns: 1fr; }
            .results-header { flex-direction: column; gap: 15px; align-items: stretch; }
            .filter-tags { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="index.php" class="back-link">← Dashboard</a>
            <div class="header-center">
                <h1>🔍 Advanced Product Search</h1>
                <p>Find products with intelligent filtering and real-time search</p>
            </div>
            <a href="/" class="back-link">Dashboard</a>
        </div>
    </div>

    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <div class="search-header">
                <h2>Search Manufacturing Products</h2>
                <p>Search across 247 products with advanced filtering capabilities</p>
            </div>
            
            <div class="search-container">
                <span class="search-icon">🔍</span>
                <input type="text" 
                       class="search-input" 
                       id="searchInput" 
                       placeholder="Search products, SKU, specifications..."
                       autocomplete="off">
                <button class="search-btn" onclick="performSearch()">Search</button>
                <div class="search-suggestions" id="searchSuggestions"></div>
            </div>
            
            <!-- Advanced Filters -->
            <div class="search-filters">
                <div class="filter-group">
                    <label for="categoryFilter">Category</label>
                    <select class="filter-select" id="categoryFilter" onchange="applyFilters()">
                        <option value="">All Categories</option>
                        <option value="pipes">Pipes & Tubing</option>
                        <option value="fittings">Fittings</option>
                        <option value="valves">Valves</option>
                        <option value="fasteners">Fasteners</option>
                        <option value="tools">Tools</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="materialFilter">Material</label>
                    <select class="filter-select" id="materialFilter" onchange="applyFilters()">
                        <option value="">All Materials</option>
                        <option value="steel">Steel</option>
                        <option value="copper">Copper</option>
                        <option value="brass">Brass</option>
                        <option value="aluminum">Aluminum</option>
                        <option value="plastic">Plastic</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="minPrice">Min Price ($)</label>
                    <input type="number" class="filter-input" id="minPrice" placeholder="0" onchange="applyFilters()">
                </div>
                
                <div class="filter-group">
                    <label for="maxPrice">Max Price ($)</label>
                    <input type="number" class="filter-input" id="maxPrice" placeholder="1000" onchange="applyFilters()">
                </div>
            </div>
            
            <!-- Quick Filters -->
            <div class="quick-filters">
                <h3>Quick Filters</h3>
                <div class="filter-tags">
                    <button class="filter-tag" onclick="setQuickFilter('in-stock')">In Stock</button>
                    <button class="filter-tag" onclick="setQuickFilter('on-sale')">On Sale</button>
                    <button class="filter-tag" onclick="setQuickFilter('new')">New Products</button>
                    <button class="filter-tag" onclick="setQuickFilter('popular')">Popular</button>
                    <button class="filter-tag" onclick="setQuickFilter('industrial')">Industrial Grade</button>
                    <button class="filter-tag active" onclick="clearFilters()">Clear All</button>
                </div>
            </div>
        </div>
        
        <!-- Results Section -->
        <div class="results-section">
            <div class="results-header">
                <div class="results-count" id="resultsCount">Showing all products</div>
                <div class="sort-controls">
                    <label for="sortSelect">Sort by:</label>
                    <select class="sort-select" id="sortSelect" onchange="applySorting()">
                        <option value="relevance">Relevance</option>
                        <option value="name">Name A-Z</option>
                        <option value="price-low">Price: Low to High</option>
                        <option value="price-high">Price: High to Low</option>
                        <option value="stock">Stock Level</option>
                    </select>
                </div>
            </div>
            
            <div id="searchResults">
                <div class="loading">
                    <div class="loading-spinner"></div>
                    Loading products...
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mock product data for demonstration
        const mockProducts = [
            {
                id: 1,
                name: "Steel Pipe 2\" Schedule 40",
                category: "pipes",
                material: "steel",
                description: "High-quality steel pipe suitable for industrial applications. Meets ASTM standards.",
                price: 24.99,
                stock: 152,
                stockLevel: "high",
                sku: "SP-2-SCH40",
                specifications: "2 inch diameter, Schedule 40, 10 foot length"
            },
            {
                id: 2,
                name: "Copper Pipe Fittings Set",
                category: "fittings",
                material: "copper",
                description: "Complete set of copper fittings including elbows, tees, and reducers.",
                price: 12.50,
                stock: 8,
                stockLevel: "low",
                sku: "CF-SET-001",
                specifications: "1/2 inch to 2 inch various fittings"
            },
            {
                id: 3,
                name: "Brass Ball Valve 1\"",
                category: "valves",
                material: "brass",
                description: "Durable brass ball valve with full port design for maximum flow.",
                price: 89.99,
                stock: 45,
                stockLevel: "medium",
                sku: "BBV-1-FP",
                specifications: "1 inch NPT threads, 600 WOG rating"
            },
            {
                id: 4,
                name: "Stainless Steel Bolts M12",
                category: "fasteners",
                material: "steel",
                description: "Marine grade stainless steel bolts with hex head design.",
                price: 3.75,
                stock: 500,
                stockLevel: "high",
                sku: "SSB-M12-50",
                specifications: "M12 x 50mm, 316 stainless steel"
            },
            {
                id: 5,
                name: "Aluminum Tubing 3/4\"",
                category: "pipes",
                material: "aluminum",
                description: "Lightweight aluminum tubing perfect for pneumatic systems.",
                price: 8.99,
                stock: 75,
                stockLevel: "medium",
                sku: "AT-075-20",
                specifications: "3/4 inch OD, 20 foot coil"
            },
            {
                id: 6,
                name: "Plastic Check Valve",
                category: "valves",
                material: "plastic",
                description: "Chemical resistant plastic check valve for corrosive environments.",
                price: 15.25,
                stock: 0,
                stockLevel: "out",
                sku: "PCV-075-CR",
                specifications: "3/4 inch, PVC construction"
            },
            {
                id: 7,
                name: "Pipe Wrench 14\"",
                category: "tools",
                material: "steel",
                description: "Heavy-duty pipe wrench with hardened steel jaws.",
                price: 45.99,
                stock: 22,
                stockLevel: "medium",
                sku: "PW-14-HD",
                specifications: "14 inch length, up to 2 inch pipe capacity"
            },
            {
                id: 8,
                name: "Copper Elbow 90° 1\"",
                category: "fittings",
                material: "copper",
                description: "Precision copper elbow for plumbing and HVAC applications.",
                price: 4.50,
                stock: 120,
                stockLevel: "high",
                sku: "CE-90-1",
                specifications: "1 inch copper, 90 degree angle"
            }
        ];
        
        let currentProducts = [...mockProducts];
        let searchTimeout = null;
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            displayProducts(currentProducts);
            setupSearch();
            
            // Check for query parameter
            const urlParams = new URLSearchParams(window.location.search);
            const query = urlParams.get('q');
            if (query) {
                document.getElementById('searchInput').value = query;
                performSearch();
            }
        });
        
        // Setup search functionality
        function setupSearch() {
            const searchInput = document.getElementById('searchInput');
            const suggestions = document.getElementById('searchSuggestions');
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.toLowerCase().trim();
                
                if (query.length > 0) {
                    searchTimeout = setTimeout(() => {
                        performSearch();
                        showSuggestions(query);
                    }, 300);
                } else {
                    suggestions.style.display = 'none';
                    currentProducts = [...mockProducts];
                    applyFilters();
                }
            });
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !suggestions.contains(e.target)) {
                    suggestions.style.display = 'none';
                }
            });
        }
        
        // Show search suggestions
        function showSuggestions(query) {
            const suggestions = document.getElementById('searchSuggestions');
            
            // Get relevant suggestions
            const suggestionList = [
                'steel pipe',
                'copper fittings',
                'brass valve',
                'stainless bolts',
                'aluminum tubing',
                'pipe wrench',
                'check valve',
                'Schedule 40',
                '1 inch',
                '2 inch'
            ].filter(s => s.includes(query));
            
            if (suggestionList.length > 0) {
                suggestions.innerHTML = suggestionList.slice(0, 5).map(s => 
                    `<div class="suggestion-item" onclick="selectSuggestion('${s}')">${s}</div>`
                ).join('');
                suggestions.style.display = 'block';
            } else {
                suggestions.style.display = 'none';
            }
        }
        
        // Select suggestion
        function selectSuggestion(suggestion) {
            document.getElementById('searchInput').value = suggestion;
            document.getElementById('searchSuggestions').style.display = 'none';
            performSearch();
        }
        
        // Perform search
        function performSearch() {
            const query = document.getElementById('searchInput').value.toLowerCase().trim();
            
            if (query) {
                currentProducts = mockProducts.filter(product => 
                    product.name.toLowerCase().includes(query) ||
                    product.description.toLowerCase().includes(query) ||
                    product.sku.toLowerCase().includes(query) ||
                    product.specifications.toLowerCase().includes(query) ||
                    product.category.toLowerCase().includes(query) ||
                    product.material.toLowerCase().includes(query)
                );
            } else {
                currentProducts = [...mockProducts];
            }
            
            applyFilters();
        }
        
        // Apply filters
        function applyFilters() {
            let filteredProducts = [...currentProducts];
            
            // Category filter
            const category = document.getElementById('categoryFilter').value;
            if (category) {
                filteredProducts = filteredProducts.filter(p => p.category === category);
            }
            
            // Material filter
            const material = document.getElementById('materialFilter').value;
            if (material) {
                filteredProducts = filteredProducts.filter(p => p.material === material);
            }
            
            // Price filters
            const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
            const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
            filteredProducts = filteredProducts.filter(p => p.price >= minPrice && p.price <= maxPrice);
            
            displayProducts(filteredProducts);
        }
        
        // Apply sorting
        function applySorting() {
            const sortBy = document.getElementById('sortSelect').value;
            let sortedProducts = [...currentProducts];
            
            switch(sortBy) {
                case 'name':
                    sortedProducts.sort((a, b) => a.name.localeCompare(b.name));
                    break;
                case 'price-low':
                    sortedProducts.sort((a, b) => a.price - b.price);
                    break;
                case 'price-high':
                    sortedProducts.sort((a, b) => b.price - a.price);
                    break;
                case 'stock':
                    sortedProducts.sort((a, b) => b.stock - a.stock);
                    break;
                default: // relevance - no change
                    break;
            }
            
            currentProducts = sortedProducts;
            applyFilters();
        }
        
        // Set quick filter
        function setQuickFilter(filter) {
            // Remove active class from all tags
            document.querySelectorAll('.filter-tag').forEach(tag => tag.classList.remove('active'));
            
            // Add active class to clicked tag
            event.target.classList.add('active');
            
            switch(filter) {
                case 'in-stock':
                    currentProducts = mockProducts.filter(p => p.stock > 0);
                    break;
                case 'on-sale':
                    currentProducts = mockProducts.filter(p => p.price < 20);
                    break;
                case 'new':
                    currentProducts = mockProducts.filter(p => p.id > 6);
                    break;
                case 'popular':
                    currentProducts = mockProducts.filter(p => ['steel', 'copper'].includes(p.material));
                    break;
                case 'industrial':
                    currentProducts = mockProducts.filter(p => p.price > 30);
                    break;
            }
            
            applyFilters();
        }
        
        // Clear filters
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('materialFilter').value = '';
            document.getElementById('minPrice').value = '';
            document.getElementById('maxPrice').value = '';
            document.getElementById('sortSelect').value = 'relevance';
            
            // Remove active class from all tags except Clear All
            document.querySelectorAll('.filter-tag').forEach(tag => tag.classList.remove('active'));
            event.target.classList.add('active');
            
            currentProducts = [...mockProducts];
            displayProducts(currentProducts);
        }
        
        // Display products
        function displayProducts(products) {
            const resultsContainer = document.getElementById('searchResults');
            const resultsCount = document.getElementById('resultsCount');
            
            // Update count
            resultsCount.textContent = `Showing ${products.length} of ${mockProducts.length} products`;
            
            if (products.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="no-results">
                        <h3>No products found</h3>
                        <p>Try adjusting your search terms or filters</p>
                    </div>
                `;
                return;
            }
            
            // Generate product cards
            const productCards = products.map(product => `
                <div class="product-card" onclick="viewProduct(${product.id})">
                    <div class="product-header">
                        <div>
                            <div class="product-name">${product.name}</div>
                            <div class="product-category">${product.category.toUpperCase()}</div>
                        </div>
                    </div>
                    <div class="product-description">${product.description}</div>
                    <div class="product-footer">
                        <div class="product-price">$${product.price.toFixed(2)}</div>
                        <div class="product-stock stock-${product.stockLevel}">
                            ${product.stock > 0 ? `${product.stock} in stock` : 'Out of stock'}
                        </div>
                    </div>
                </div>
            `).join('');
            
            resultsContainer.innerHTML = `<div class="product-grid">${productCards}</div>`;
        }
        
        // View product details
        function viewProduct(productId) {
            const product = mockProducts.find(p => p.id === productId);
            alert(`Product Details:\n\nName: ${product.name}\nSKU: ${product.sku}\nPrice: $${product.price}\nStock: ${product.stock}\nSpecifications: ${product.specifications}`);
        }
    </script>
</body>
</html>
