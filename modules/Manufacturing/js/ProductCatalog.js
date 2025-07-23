/**
 * Mobile-First Product Catalog Component
 * React component for manufacturing sales reps
 */

// Mobile Product Catalog Application
class ProductCatalogApp {
    constructor() {
        this.products = [];
        this.currentPage = 1;
        this.filters = {
            category: '',
            material: '',
            search: '',
            stockStatus: '',
            priceRange: [0, 999999]
        };
        this.selectedClient = null;
        this.cart = [];
        this.isLoading = false;
        
        this.init();
    }
    
    init() {
        this.createHTML();
        this.bindEvents();
        this.loadProducts();
        
        // Service Worker for offline capability
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/manufacturing/sw.js');
        }
    }
    
    createHTML() {
        const container = document.getElementById('product-catalog-container') || document.body;
        
        container.innerHTML = `
            <div class="manufacturing-catalog">
                <!-- Header -->
                <header class="catalog-header">
                    <div class="header-content">
                        <h1><i class="fas fa-industry"></i> Product Catalog</h1>
                        <div class="client-selector">
                            <select id="clientSelect" class="form-select">
                                <option value="">Select Client for Pricing</option>
                            </select>
                        </div>
                    </div>
                </header>

                <!-- Search and Filters -->
                <div class="search-filters">
                    <div class="search-bar">
                        <input type="text" id="searchInput" placeholder="Search products, SKU, or description..." 
                               class="form-control">
                        <button id="searchBtn" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    
                    <div class="filters-row">
                        <select id="categoryFilter" class="form-select">
                            <option value="">All Categories</option>
                        </select>
                        
                        <select id="materialFilter" class="form-select">
                            <option value="">All Materials</option>
                        </select>
                        
                        <select id="stockFilter" class="form-select">
                            <option value="">All Stock</option>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="price-range">
                        <label>Price Range: $<span id="priceRangeValue">0 - 999,999</span></label>
                        <input type="range" id="priceRangeSlider" min="0" max="10000" step="100" 
                               class="form-range">
                    </div>
                </div>

                <!-- Product Grid -->
                <div class="products-container">
                    <div id="productsGrid" class="products-grid">
                        <!-- Products will be loaded here -->
                    </div>
                    
                    <div id="loadingSpinner" class="loading-spinner" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Loading products...
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination-container">
                    <button id="prevPage" class="btn btn-outline-primary">Previous</button>
                    <span id="pageInfo" class="page-info">Page 1</span>
                    <button id="nextPage" class="btn btn-outline-primary">Next</button>
                </div>

                <!-- Cart Summary (Floating) -->
                <div id="cartSummary" class="cart-summary" style="display: none;">
                    <div class="cart-header">
                        <span>Quote Cart (<span id="cartCount">0</span>)</span>
                        <button id="toggleCart" class="btn btn-sm btn-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                    <div id="cartItems" class="cart-items" style="display: none;">
                        <!-- Cart items here -->
                    </div>
                    <div class="cart-actions" style="display: none;">
                        <button id="generateQuote" class="btn btn-success btn-block">
                            Generate Quote
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        this.loadCSS();
    }
    
    loadCSS() {
        if (document.getElementById('manufacturing-catalog-css')) return;
        
        const css = `
            <style id="manufacturing-catalog-css">
                .manufacturing-catalog {
                    max-width: 100%;
                    margin: 0 auto;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }
                
                .catalog-header {
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    padding: 1rem;
                    position: sticky;
                    top: 0;
                    z-index: 100;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .header-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                }
                
                .header-content h1 {
                    margin: 0;
                    font-size: 1.5rem;
                    font-weight: 600;
                }
                
                .client-selector {
                    min-width: 200px;
                }
                
                .search-filters {
                    padding: 1rem;
                    background: #f8f9fa;
                    border-bottom: 1px solid #dee2e6;
                }
                
                .search-bar {
                    display: flex;
                    gap: 0.5rem;
                    margin-bottom: 1rem;
                }
                
                .search-bar input {
                    flex: 1;
                    padding: 0.75rem;
                    border: 1px solid #ced4da;
                    border-radius: 0.375rem;
                    font-size: 1rem;
                }
                
                .filters-row {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                    gap: 0.5rem;
                    margin-bottom: 1rem;
                }
                
                .form-select {
                    padding: 0.5rem;
                    border: 1px solid #ced4da;
                    border-radius: 0.375rem;
                    background: white;
                }
                
                .price-range {
                    text-align: center;
                }
                
                .products-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 1rem;
                    padding: 1rem;
                }
                
                .product-card {
                    background: white;
                    border-radius: 0.5rem;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    overflow: hidden;
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                    position: relative;
                }
                
                .product-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
                }
                
                .product-image {
                    width: 100%;
                    height: 200px;
                    background: #f8f9fa;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    position: relative;
                }
                
                .product-image img {
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: cover;
                }
                
                .stock-badge {
                    position: absolute;
                    top: 0.5rem;
                    right: 0.5rem;
                    padding: 0.25rem 0.5rem;
                    border-radius: 0.25rem;
                    font-size: 0.75rem;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                
                .stock-badge.in_stock { background: #d4edda; color: #155724; }
                .stock-badge.low_stock { background: #fff3cd; color: #856404; }
                .stock-badge.out_of_stock { background: #f8d7da; color: #721c24; }
                
                .product-info {
                    padding: 1rem;
                }
                
                .product-name {
                    font-weight: 600;
                    font-size: 1.1rem;
                    margin-bottom: 0.5rem;
                    color: #2d3748;
                }
                
                .product-sku {
                    color: #6c757d;
                    font-size: 0.875rem;
                    margin-bottom: 0.5rem;
                }
                
                .product-description {
                    color: #495057;
                    font-size: 0.875rem;
                    line-height: 1.4;
                    margin-bottom: 1rem;
                    display: -webkit-box;
                    -webkit-line-clamp: 2;
                    -webkit-box-orient: vertical;
                    overflow: hidden;
                }
                
                .product-specs {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 0.5rem;
                    margin-bottom: 1rem;
                    font-size: 0.875rem;
                }
                
                .spec-item {
                    display: flex;
                    justify-content: space-between;
                }
                
                .pricing-info {
                    margin-bottom: 1rem;
                }
                
                .price-display {
                    font-size: 1.25rem;
                    font-weight: 600;
                    color: #667eea;
                }
                
                .list-price {
                    text-decoration: line-through;
                    color: #6c757d;
                    font-size: 0.875rem;
                }
                
                .savings {
                    color: #28a745;
                    font-size: 0.875rem;
                    font-weight: 500;
                }
                
                .product-actions {
                    display: flex;
                    gap: 0.5rem;
                }
                
                .btn {
                    padding: 0.5rem 1rem;
                    border: none;
                    border-radius: 0.375rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.25rem;
                }
                
                .btn-primary {
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                }
                
                .btn-outline-primary {
                    border: 1px solid #667eea;
                    color: #667eea;
                    background: white;
                }
                
                .btn:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                }
                
                .btn:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                }
                
                .cart-summary {
                    position: fixed;
                    bottom: 1rem;
                    right: 1rem;
                    background: white;
                    border-radius: 0.5rem;
                    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
                    z-index: 200;
                    min-width: 280px;
                    max-width: 400px;
                }
                
                .cart-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 1rem;
                    border-bottom: 1px solid #dee2e6;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    border-radius: 0.5rem 0.5rem 0 0;
                }
                
                .cart-items {
                    max-height: 300px;
                    overflow-y: auto;
                    padding: 1rem;
                }
                
                .cart-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 0.5rem 0;
                    border-bottom: 1px solid #f8f9fa;
                }
                
                .pagination-container {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    gap: 1rem;
                    padding: 2rem 1rem;
                }
                
                .loading-spinner {
                    text-align: center;
                    padding: 2rem;
                    color: #667eea;
                    font-size: 1.1rem;
                }
                
                /* Mobile Optimizations */
                @media (max-width: 768px) {
                    .header-content {
                        flex-direction: column;
                        gap: 1rem;
                    }
                    
                    .client-selector {
                        width: 100%;
                    }
                    
                    .products-grid {
                        grid-template-columns: 1fr;
                        padding: 0.5rem;
                    }
                    
                    .product-card {
                        margin-bottom: 1rem;
                    }
                    
                    .filters-row {
                        grid-template-columns: 1fr;
                    }
                    
                    .cart-summary {
                        bottom: 0;
                        right: 0;
                        left: 0;
                        border-radius: 0.5rem 0.5rem 0 0;
                        max-width: none;
                    }
                }
                
                @media (max-width: 480px) {
                    .product-specs {
                        grid-template-columns: 1fr;
                    }
                    
                    .product-actions {
                        flex-direction: column;
                    }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', css);
    }
    
    bindEvents() {
        // Search functionality
        document.getElementById('searchBtn').addEventListener('click', () => this.handleSearch());
        document.getElementById('searchInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.handleSearch();
        });
        
        // Filter changes
        document.getElementById('categoryFilter').addEventListener('change', () => this.applyFilters());
        document.getElementById('materialFilter').addEventListener('change', () => this.applyFilters());
        document.getElementById('stockFilter').addEventListener('change', () => this.applyFilters());
        
        // Client selection
        document.getElementById('clientSelect').addEventListener('change', (e) => {
            this.selectedClient = e.target.value;
            this.loadProducts();
        });
        
        // Pagination
        document.getElementById('prevPage').addEventListener('click', () => this.previousPage());
        document.getElementById('nextPage').addEventListener('click', () => this.nextPage());
        
        // Cart toggle
        document.getElementById('toggleCart').addEventListener('click', () => this.toggleCart());
    }
    
    async loadProducts() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading(true);
        
        try {
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: 20,
                ...this.filters
            });
            
            const endpoint = this.selectedClient 
                ? `/api/v1/products/client/${this.selectedClient}?${params}`
                : `/api/v1/products?${params}`;
            
            const response = await fetch(endpoint);
            const data = await response.json();
            
            if (data.success) {
                this.products = data.data.products;
                this.renderProducts();
                this.updatePagination(data.data.pagination);
            } else {
                this.showError('Failed to load products');
            }
        } catch (error) {
            console.error('Error loading products:', error);
            this.showError('Network error. Check your connection.');
        } finally {
            this.isLoading = false;
            this.showLoading(false);
        }
    }
    
    renderProducts() {
        const grid = document.getElementById('productsGrid');
        
        if (this.products.length === 0) {
            grid.innerHTML = `
                <div class="no-products">
                    <i class="fas fa-search" style="font-size: 3rem; color: #dee2e6;"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your filters or search terms.</p>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = this.products.map(product => this.renderProductCard(product)).join('');
        
        // Bind product-specific events
        this.bindProductEvents();
    }
    
    renderProductCard(product) {
        const stockClass = product.inventory.stock_status;
        const stockLabel = stockClass.replace('_', ' ').toUpperCase();
        const hasClientPricing = product.pricing && this.selectedClient;
        
        return `
            <div class="product-card" data-product-id="${product.id}">
                <div class="product-image">
                    ${product.images.thumbnail 
                        ? `<img src="${product.images.thumbnail}" alt="${product.name}" loading="lazy">`
                        : `<i class="fas fa-box" style="font-size: 3rem; color: #dee2e6;"></i>`
                    }
                    <div class="stock-badge ${stockClass}">${stockLabel}</div>
                </div>
                
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-sku">SKU: ${product.sku}</div>
                    <div class="product-description">${product.description || 'No description available'}</div>
                    
                    <div class="product-specs">
                        <div class="spec-item">
                            <span>Category:</span>
                            <span>${product.category || 'N/A'}</span>
                        </div>
                        <div class="spec-item">
                            <span>Material:</span>
                            <span>${product.material || 'N/A'}</span>
                        </div>
                        ${product.weight_lbs ? `
                            <div class="spec-item">
                                <span>Weight:</span>
                                <span>${product.weight_lbs} lbs</span>
                            </div>
                        ` : ''}
                        <div class="spec-item">
                            <span>Min Qty:</span>
                            <span>${product.minimum_order_qty}</span>
                        </div>
                    </div>
                    
                    <div class="pricing-info">
                        ${hasClientPricing ? `
                            <div class="price-display">$${product.pricing.client_price.toFixed(2)}</div>
                            ${product.pricing.list_price > product.pricing.client_price ? `
                                <div class="list-price">List: $${product.pricing.list_price.toFixed(2)}</div>
                                <div class="savings">
                                    Save ${product.pricing.discount_percentage.toFixed(1)}% 
                                    (${product.pricing.tier_name})
                                </div>
                            ` : ''}
                        ` : `
                            <div class="price-display">$${product.list_price.toFixed(2)}</div>
                        `}
                    </div>
                    
                    <div class="product-actions">
                        <button class="btn btn-primary add-to-cart" 
                                data-product-id="${product.id}"
                                ${stockClass === 'out_of_stock' ? 'disabled' : ''}>
                            <i class="fas fa-plus"></i>
                            Add to Quote
                        </button>
                        <button class="btn btn-outline-primary view-details" 
                                data-product-id="${product.id}">
                            <i class="fas fa-info-circle"></i>
                            Details
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    bindProductEvents() {
        // Add to cart buttons
        document.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;
                this.addToCart(productId);
            });
        });
        
        // View details buttons
        document.querySelectorAll('.view-details').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;
                this.viewProductDetails(productId);
            });
        });
    }
    
    addToCart(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product) return;
        
        const existingItem = this.cart.find(item => item.product.id === productId);
        
        if (existingItem) {
            existingItem.quantity++;
        } else {
            this.cart.push({
                product: product,
                quantity: product.minimum_order_qty || 1
            });
        }
        
        this.updateCartDisplay();
        this.showNotification(`Added ${product.name} to quote cart`);
    }
    
    updateCartDisplay() {
        const cartSummary = document.getElementById('cartSummary');
        const cartCount = document.getElementById('cartCount');
        
        if (this.cart.length > 0) {
            cartSummary.style.display = 'block';
            cartCount.textContent = this.cart.length;
        } else {
            cartSummary.style.display = 'none';
        }
    }
    
    toggleCart() {
        const cartItems = document.getElementById('cartItems');
        const cartActions = document.querySelector('.cart-actions');
        
        if (cartItems.style.display === 'none') {
            cartItems.style.display = 'block';
            cartActions.style.display = 'block';
            this.renderCartItems();
        } else {
            cartItems.style.display = 'none';
            cartActions.style.display = 'none';
        }
    }
    
    renderCartItems() {
        const cartItems = document.getElementById('cartItems');
        
        cartItems.innerHTML = this.cart.map(item => `
            <div class="cart-item">
                <div>
                    <div style="font-weight: 500;">${item.product.name}</div>
                    <div style="font-size: 0.875rem; color: #6c757d;">
                        SKU: ${item.product.sku} • Qty: ${item.quantity}
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 600;">
                        $${(item.product.list_price * item.quantity).toFixed(2)}
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    handleSearch() {
        const searchTerm = document.getElementById('searchInput').value.trim();
        if (searchTerm.length < 2) {
            this.showNotification('Please enter at least 2 characters to search');
            return;
        }
        
        this.filters.search = searchTerm;
        this.currentPage = 1;
        this.searchProducts();
    }
    
    async searchProducts() {
        this.showLoading(true);
        
        try {
            const params = new URLSearchParams({
                q: this.filters.search,
                category: this.filters.category,
                material: this.filters.material,
                stock_status: this.filters.stockStatus,
                limit: 20
            });
            
            const response = await fetch(`/api/v1/products/search?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.products = data.data.products;
                this.renderProducts();
            }
        } catch (error) {
            this.showError('Search failed. Please try again.');
        } finally {
            this.showLoading(false);
        }
    }
    
    applyFilters() {
        this.filters.category = document.getElementById('categoryFilter').value;
        this.filters.material = document.getElementById('materialFilter').value;
        this.filters.stockStatus = document.getElementById('stockFilter').value;
        this.currentPage = 1;
        this.loadProducts();
    }
    
    previousPage() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.loadProducts();
        }
    }
    
    nextPage() {
        this.currentPage++;
        this.loadProducts();
    }
    
    updatePagination(pagination) {
        if (!pagination) return;
        
        const pageInfo = document.getElementById('pageInfo');
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        
        pageInfo.textContent = `Page ${pagination.page} of ${pagination.total_pages}`;
        prevBtn.disabled = pagination.page <= 1;
        nextBtn.disabled = pagination.page >= pagination.total_pages;
    }
    
    showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        spinner.style.display = show ? 'block' : 'none';
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'success') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: ${type === 'error' ? '#dc3545' : '#28a745'};
            color: white;
            padding: 1rem;
            border-radius: 0.375rem;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
    
    viewProductDetails(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product) return;
        
        // Open product details modal or navigate to details page
        // This could be implemented as a modal or separate page
        window.open(`/products/view/${productId}`, '_blank');
    }
}

// Initialize the app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('product-catalog-container')) {
        window.productCatalogApp = new ProductCatalogApp();
    }
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductCatalogApp;
}
