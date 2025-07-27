<?php
/**
 * Dynamic Inventory Purchase System
 * Manufacturing Distribution Platform - Real-Time Purchase Interface
 * 
 * Allows clients to browse products, make purchases, and see real-time inventory updates
 */

require_once('config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛒 Inventory Purchase System - Manufacturing Distribution</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 20px; }
        .header-nav { display: flex; align-items: center; justify-content: space-between; max-width: 1400px; margin: 0 auto; }
        .header-center { text-align: center; flex: 1; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.2em; opacity: 0.9; }
        
        .back-link { color: white; text-decoration: none; padding: 10px 15px; border-radius: 6px; background: rgba(255,255,255,0.1); }
        .back-link:hover { background: rgba(255,255,255,0.2); text-decoration: none; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; display: grid; grid-template-columns: 1fr 350px; gap: 20px; }
        
        /* Main Content Area */
        .main-content { }
        
        /* Purchase Stats Bar */
        .stats-bar { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .stat-card { text-align: center; padding: 15px; background: linear-gradient(135deg, #27ae60, #229954); color: white; border-radius: 8px; }
        .stat-number { font-size: 1.8em; font-weight: bold; }
        .stat-label { font-size: 0.9em; opacity: 0.9; margin-top: 5px; }
        
        /* Product Grid */
        .product-section { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .product-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-controls { display: flex; gap: 10px; align-items: center; }
        .search-input { padding: 10px; border: 1px solid #ddd; border-radius: 6px; width: 250px; }
        .filter-select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .product-card { border: 1px solid #e9ecef; border-radius: 12px; padding: 20px; transition: all 0.3s ease; position: relative; }
        .product-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.1); transform: translateY(-2px); }
        
        .product-header-card { display: flex; justify-content: between; align-items: flex-start; margin-bottom: 15px; }
        .product-name { font-weight: 600; color: #2c3e50; font-size: 1.1em; margin-bottom: 5px; }
        .product-sku { color: #6c757d; font-size: 0.9em; margin-bottom: 10px; }
        .product-price { color: #27ae60; font-weight: bold; font-size: 1.2em; margin-bottom: 15px; }
        
        /* Stock indicators */
        .stock-indicator { position: absolute; top: 15px; right: 15px; padding: 4px 8px; border-radius: 12px; font-size: 0.8em; font-weight: 500; }
        .stock-high { background: #d4edda; color: #155724; }
        .stock-medium { background: #fff3cd; color: #856404; }
        .stock-low { background: #f8d7da; color: #721c24; }
        .stock-out { background: #f5c6cb; color: #721c24; }
        
        .stock-info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0; }
        .stock-detail { text-align: center; padding: 10px; background: #f8f9fa; border-radius: 6px; }
        .stock-number { font-weight: bold; font-size: 1.1em; }
        .stock-text { font-size: 0.8em; color: #6c757d; margin-top: 2px; }
        
        .purchase-controls { display: flex; gap: 10px; align-items: center; margin-top: 15px; }
        .quantity-input { width: 70px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; text-align: center; }
        .btn-add-cart { background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 6px; cursor: pointer; flex: 1; }
        .btn-add-cart:hover { background: #0056b3; }
        .btn-add-cart:disabled { background: #6c757d; cursor: not-allowed; }
        
        /* Cart Sidebar */
        .cart-sidebar { position: sticky; top: 20px; height: fit-content; }
        .cart-container { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .cart-header { display: flex; justify-content: between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e9ecef; }
        .cart-title { font-weight: 600; color: #2c3e50; }
        .cart-count { background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.8em; }
        
        .cart-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f3f4; }
        .cart-item:last-child { border-bottom: none; }
        .cart-item-info { flex: 1; }
        .cart-item-name { font-weight: 500; color: #2c3e50; font-size: 0.9em; }
        .cart-item-details { color: #6c757d; font-size: 0.8em; margin-top: 2px; }
        .cart-item-price { font-weight: bold; color: #27ae60; }
        .remove-item { background: #dc3545; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.8em; }
        
        .cart-total { margin-top: 15px; padding-top: 15px; border-top: 2px solid #e9ecef; }
        .total-row { display: flex; justify-content: space-between; margin: 5px 0; }
        .total-label { color: #6c757d; }
        .total-amount { font-weight: bold; }
        .grand-total { font-size: 1.2em; color: #2c3e50; }
        
        .btn-purchase { background: #28a745; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: 500; margin-top: 15px; }
        .btn-purchase:hover { background: #1e7e34; }
        .btn-purchase:disabled { background: #6c757d; cursor: not-allowed; }
        
        /* Transaction History */
        .transaction-history { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .transaction-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f1f3f4; }
        .transaction-info { flex: 1; }
        .transaction-name { font-weight: 500; color: #2c3e50; font-size: 0.9em; }
        .transaction-time { color: #6c757d; font-size: 0.8em; }
        .transaction-qty { background: #e3f2fd; color: #1976d2; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; }
        
        /* Real-time updates */
        .live-indicator { display: inline-flex; align-items: center; gap: 5px; color: #28a745; font-size: 0.9em; }
        .live-dot { width: 8px; height: 8px; background: #28a745; border-radius: 50%; animation: pulse 2s infinite; }
        
        /* Animations */
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        @keyframes slideIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .slide-in { animation: slideIn 0.3s ease-out; }
        
        /* Notifications */
        .notification { position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; transform: translateX(400px); transition: transform 0.3s ease; }
        .notification.show { transform: translateX(0); }
        .notification.error { background: #dc3545; }
        .notification.warning { background: #ffc107; color: #212529; }
        
        /* Loading states */
        .loading { opacity: 0.6; pointer-events: none; }
        .spinner { border: 2px solid #f3f3f3; border-top: 2px solid #007bff; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; display: inline-block; margin-right: 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; padding: 10px; }
            .cart-sidebar { position: relative; }
            .product-grid { grid-template-columns: 1fr; }
            .search-controls { flex-direction: column; align-items: stretch; gap: 10px; }
            .search-input { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="index.php" class="back-link">← Back to Dashboard</a>
            <div class="header-center">
                <h1>🛒 Dynamic Inventory Purchase System</h1>
                <p>Real-Time Purchasing with Live Inventory Updates</p>
            </div>
            <div class="live-indicator">
                <div class="live-dot"></div>
                <span>Live Updates</span>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Main Content -->
        <div class="main-content">
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number" id="totalProducts">0</div>
                        <div class="stat-label">Available Products</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <div class="stat-number" id="totalStock">0</div>
                        <div class="stat-label">Total Stock Units</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                        <div class="stat-number" id="todaysPurchases">0</div>
                        <div class="stat-label">Today's Purchases</div>
                    </div>
                    <div class="stat-card" style="background: linear-gradient(135deg, #e67e22, #d35400);">
                        <div class="stat-number" id="activeWarehouses">0</div>
                        <div class="stat-label">Warehouses Online</div>
                    </div>
                </div>
            </div>

            <!-- Product Catalog -->
            <div class="product-section">
                <div class="product-header">
                    <h2>🏭 Product Catalog</h2>
                    <div class="search-controls">
                        <input type="text" id="searchInput" class="search-input" placeholder="Search products by name or SKU...">
                        <select id="categoryFilter" class="filter-select">
                            <option value="">All Categories</option>
                        </select>
                        <select id="stockFilter" class="filter-select">
                            <option value="">All Stock Levels</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
                
                <div id="productGrid" class="product-grid">
                    <!-- Products will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Cart Sidebar -->
        <div class="cart-sidebar">
            <!-- Shopping Cart -->
            <div class="cart-container">
                <div class="cart-header">
                    <div class="cart-title">🛒 Shopping Cart</div>
                    <div class="cart-count" id="cartCount">0</div>
                </div>
                
                <div id="cartItems">
                    <div style="text-align: center; color: #6c757d; padding: 20px; font-style: italic;">
                        Cart is empty
                    </div>
                </div>
                
                <div class="cart-total" id="cartTotal" style="display: none;">
                    <div class="total-row">
                        <span class="total-label">Subtotal:</span>
                        <span class="total-amount" id="subtotal">$0.00</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Tax (8.5%):</span>
                        <span class="total-amount" id="tax">$0.00</span>
                    </div>
                    <div class="total-row grand-total">
                        <span class="total-label">Total:</span>
                        <span class="total-amount" id="grandTotal">$0.00</span>
                    </div>
                </div>
                
                <button id="purchaseBtn" class="btn-purchase" onclick="processPurchase()" disabled>
                    Complete Purchase
                </button>
            </div>

            <!-- Recent Transactions -->
            <div class="transaction-history">
                <h3 style="margin-bottom: 15px; color: #2c3e50;">📊 Recent Purchases</h3>
                <div id="recentTransactions">
                    <!-- Recent transactions will appear here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification" class="notification"></div>

    <script>
        // Global state
        let products = [];
        let cart = [];
        let transactions = [];
        let lastInventoryUpdate = Date.now();

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            loadInventoryData();
            loadProducts();
            setupEventListeners();
            startRealTimeUpdates();
        });

        // Event listeners
        function setupEventListeners() {
            document.getElementById('searchInput').addEventListener('input', filterProducts);
            document.getElementById('categoryFilter').addEventListener('change', filterProducts);
            document.getElementById('stockFilter').addEventListener('change', filterProducts);
        }

        // Load inventory summary data
        async function loadInventoryData() {
            try {
                const response = await fetch('/inventory_api_direct.php?action=get_summary');
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    document.getElementById('totalProducts').textContent = data.in_stock_items || 0;
                    document.getElementById('totalStock').textContent = formatNumber(data.total_available || 0);
                    document.getElementById('activeWarehouses').textContent = '8'; // From existing system
                }
            } catch (error) {
                console.error('Error loading inventory data:', error);
            }
        }

        // Load products with inventory information
        async function loadProducts() {
            try {
                showLoading(true);
                const response = await fetch('/inventory_purchase_api.php?action=get_products');
                const result = await response.json();
                
                if (result.success) {
                    products = result.data.products;
                    populateCategories();
                    renderProducts();
                } else {
                    showNotification('Error loading products: ' + result.error.message, 'error');
                }
            } catch (error) {
                console.error('Error loading products:', error);
                showNotification('Error loading products. Please try again.', 'error');
            } finally {
                showLoading(false);
            }
        }

        // Populate category filter
        function populateCategories() {
            const categories = [...new Set(products.map(p => p.category))].filter(Boolean);
            const categoryFilter = document.getElementById('categoryFilter');
            
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categoryFilter.appendChild(option);
            });
        }

        // Render products in grid
        function renderProducts(filteredProducts = products) {
            const grid = document.getElementById('productGrid');
            
            if (filteredProducts.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #6c757d;">
                        <h3>No products found</h3>
                        <p>Try adjusting your search or filter criteria.</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = filteredProducts.map(product => `
                <div class="product-card slide-in" data-product-id="${product.id}">
                    ${getStockIndicator(product)}
                    
                    <div class="product-name">${product.name}</div>
                    <div class="product-sku">SKU: ${product.sku}</div>
                    <div class="product-price">$${parseFloat(product.unit_price || 0).toFixed(2)}</div>
                    
                    <div class="stock-info">
                        <div class="stock-detail">
                            <div class="stock-number" id="stock-${product.id}">${product.total_stock || 0}</div>
                            <div class="stock-text">Available</div>
                        </div>
                        <div class="stock-detail">
                            <div class="stock-number">${product.warehouse_count || 0}</div>
                            <div class="stock-text">Locations</div>
                        </div>
                    </div>
                    
                    <div class="purchase-controls">
                        <input type="number" class="quantity-input" id="qty-${product.id}" 
                               min="1" max="${product.total_stock || 0}" value="1" 
                               ${(product.total_stock || 0) <= 0 ? 'disabled' : ''}>
                        <button class="btn-add-cart" onclick="addToCart('${product.id}')" 
                                ${(product.total_stock || 0) <= 0 ? 'disabled' : ''}>
                            ${(product.total_stock || 0) <= 0 ? '❌ Out of Stock' : '🛒 Add to Cart'}
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Get stock status indicator
        function getStockIndicator(product) {
            const stock = product.total_stock || 0;
            
            if (stock <= 0) {
                return '<div class="stock-indicator stock-out">❌ Out of Stock</div>';
            } else if (stock <= 20) {
                return '<div class="stock-indicator stock-low">⚠️ Low Stock</div>';
            } else if (stock <= 50) {
                return '<div class="stock-indicator stock-medium">📊 Medium Stock</div>';
            } else {
                return '<div class="stock-indicator stock-high">✅ In Stock</div>';
            }
        }

        // Filter products
        function filterProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const stockFilter = document.getElementById('stockFilter').value;
            
            let filtered = products.filter(product => {
                const matchesSearch = !searchTerm || 
                    product.name.toLowerCase().includes(searchTerm) ||
                    product.sku.toLowerCase().includes(searchTerm);
                
                const matchesCategory = !category || product.category === category;
                
                const matchesStock = !stockFilter || product.stock_status === stockFilter;
                
                return matchesSearch && matchesCategory && matchesStock;
            });
            
            renderProducts(filtered);
        }

        // Add product to cart
        function addToCart(productId) {
            const product = products.find(p => p.id === productId);
            const qtyInput = document.getElementById(`qty-${productId}`);
            const quantity = parseInt(qtyInput.value) || 1;
            
            if (!product || quantity <= 0) return;
            
            if (quantity > product.total_stock) {
                showNotification(`Only ${product.total_stock} units available`, 'warning');
                return;
            }
            
            // Check if item already in cart
            const existingItem = cart.find(item => item.id === productId);
            
            if (existingItem) {
                existingItem.quantity += quantity;
            } else {
                cart.push({
                    id: productId,
                    name: product.name,
                    sku: product.sku,
                    price: parseFloat(product.unit_price || 0),
                    quantity: quantity,
                    max_stock: product.total_stock
                });
            }
            
            updateCartDisplay();
            showNotification(`Added ${quantity}x ${product.name} to cart`, 'success');
            
            // Reset quantity input
            qtyInput.value = 1;
        }

        // Remove item from cart
        function removeFromCart(productId) {
            cart = cart.filter(item => item.id !== productId);
            updateCartDisplay();
            showNotification('Item removed from cart', 'success');
        }

        // Update cart display
        function updateCartDisplay() {
            const cartCount = document.getElementById('cartCount');
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            const purchaseBtn = document.getElementById('purchaseBtn');
            
            cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div style="text-align: center; color: #6c757d; padding: 20px; font-style: italic;">
                        Cart is empty
                    </div>
                `;
                cartTotal.style.display = 'none';
                purchaseBtn.disabled = true;
                return;
            }
            
            cartItems.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-details">${item.quantity}x $${item.price.toFixed(2)}</div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div class="cart-item-price">$${(item.quantity * item.price).toFixed(2)}</div>
                        <button class="remove-item" onclick="removeFromCart('${item.id}')">✕</button>
                    </div>
                </div>
            `).join('');
            
            // Calculate totals
            const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.price), 0);
            const tax = subtotal * 0.085; // 8.5% tax
            const total = subtotal + tax;
            
            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `$${tax.toFixed(2)}`;
            document.getElementById('grandTotal').textContent = `$${total.toFixed(2)}`;
            
            cartTotal.style.display = 'block';
            purchaseBtn.disabled = false;
        }

        // Process purchase
        async function processPurchase() {
            if (cart.length === 0) return;
            
            const purchaseBtn = document.getElementById('purchaseBtn');
            const originalText = purchaseBtn.textContent;
            
            try {
                purchaseBtn.innerHTML = '<div class="spinner"></div>Processing...';
                purchaseBtn.disabled = true;
                
                const response = await fetch('/inventory_purchase_api.php?action=process_purchase', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        items: cart,
                        customer_name: 'Walk-in Customer', // Could be dynamic
                        payment_method: 'cash'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Purchase completed successfully!', 'success');
                    
                    // Add to recent transactions
                    transactions.unshift({
                        id: result.data.transaction_id,
                        items: [...cart],
                        total: result.data.total_amount,
                        timestamp: new Date()
                    });
                    
                    // Clear cart
                    cart = [];
                    updateCartDisplay();
                    updateRecentTransactions();
                    
                    // Refresh inventory
                    setTimeout(() => {
                        loadProducts();
                        loadInventoryData();
                    }, 1000);
                    
                } else {
                    showNotification('Purchase failed: ' + result.error.message, 'error');
                }
                
            } catch (error) {
                console.error('Purchase error:', error);
                showNotification('Purchase failed. Please try again.', 'error');
            } finally {
                purchaseBtn.textContent = originalText;
                purchaseBtn.disabled = false;
            }
        }

        // Update recent transactions display
        function updateRecentTransactions() {
            const container = document.getElementById('recentTransactions');
            
            if (transactions.length === 0) {
                container.innerHTML = '<div style="text-align: center; color: #6c757d; font-style: italic;">No recent purchases</div>';
                return;
            }
            
            container.innerHTML = transactions.slice(0, 5).map(transaction => `
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="transaction-name">${transaction.items.length} items</div>
                        <div class="transaction-time">${formatTime(transaction.timestamp)}</div>
                    </div>
                    <div class="transaction-qty">$${transaction.total.toFixed(2)}</div>
                </div>
            `).join('');
            
            // Update today's purchases count
            const today = new Date().toDateString();
            const todaysCount = transactions.filter(t => t.timestamp.toDateString() === today).length;
            document.getElementById('todaysPurchases').textContent = todaysCount;
        }

        // Real-time inventory updates
        function startRealTimeUpdates() {
            setInterval(async () => {
                try {
                    const response = await fetch(`/inventory_purchase_api.php?action=get_updates&since=${lastInventoryUpdate}`);
                    const result = await response.json();
                    
                    if (result.success && result.data.updates.length > 0) {
                        result.data.updates.forEach(update => {
                            updateProductStock(update.product_id, update.new_stock);
                        });
                        
                        lastInventoryUpdate = Date.now();
                        showNotification(`${result.data.updates.length} inventory updates received`, 'success');
                    }
                } catch (error) {
                    console.error('Error checking for updates:', error);
                }
            }, 5000); // Check every 5 seconds
        }

        // Update product stock in UI
        function updateProductStock(productId, newStock) {
            const stockElement = document.getElementById(`stock-${productId}`);
            if (stockElement) {
                stockElement.textContent = newStock;
                stockElement.style.animation = 'pulse 0.5s ease-in-out';
            }
            
            // Update product data
            const product = products.find(p => p.id === productId);
            if (product) {
                product.total_stock = newStock;
            }
        }

        // Utility functions
        function formatNumber(number) {
            const num = Math.round(number);
            if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        }

        function formatTime(date) {
            return date.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                hour12: true 
            });
        }

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = `notification ${type}`;
            notification.classList.add('show');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }

        function showLoading(show) {
            const grid = document.getElementById('productGrid');
            if (show) {
                grid.classList.add('loading');
            } else {
                grid.classList.remove('loading');
            }
        }
    </script>
</body>
</html>
