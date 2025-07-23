/**
 * Stock Indicator Component
 * Real-time inventory display for Manufacturing Distribution Platform
 */

class StockIndicator {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.getElementById(container) : container;
        this.options = {
            productId: options.productId || '',
            showDetails: options.showDetails !== false,
            updateInterval: options.updateInterval || 30000, // 30 seconds
            showWarehouse: options.showWarehouse !== false,
            apiEndpoint: options.apiEndpoint || '/inventory_api_direct.php',
            ...options
        };
        
        this.stockData = null;
        this.updateTimer = null;
        
        this.init();
    }
    
    init() {
        if (!this.container || !this.options.productId) {
            console.error('StockIndicator: Invalid container or product ID');
            return;
        }
        
        this.render();
        this.fetchStockData();
        this.startAutoUpdate();
    }
    
    render() {
        this.container.innerHTML = `
            <div class="stock-indicator-wrapper" data-product-id="${this.options.productId}">
                <div class="stock-indicator-loading">
                    <div class="loading-spinner"></div>
                    <span>Loading stock data...</span>
                </div>
            </div>
        `;
        
        // Add CSS if not already present
        if (!document.getElementById('stock-indicator-styles')) {
            const styles = document.createElement('style');
            styles.id = 'stock-indicator-styles';
            styles.textContent = this.getCSS();
            document.head.appendChild(styles);
        }
    }
    
    async fetchStockData() {
        try {
            const response = await fetch(`${this.options.apiEndpoint}?action=get_product_stock&product_id=${this.options.productId}`);
            const result = await response.json();
            
            if (result.success) {
                this.stockData = result.data;
                this.renderStockData();
            } else {
                this.renderError('Failed to load stock data: ' + (result.error?.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Stock fetch error:', error);
            this.renderError('Network error loading stock data');
        }
    }
    
    renderStockData() {
        if (!this.stockData) return;
        
        const overallStatus = this.calculateOverallStatus();
        const statusInfo = this.getStatusInfo(overallStatus);
        
        const warehouseList = this.options.showWarehouse ? 
            this.stockData.warehouses.map(wh => this.renderWarehouse(wh)).join('') : '';
        
        this.container.innerHTML = `
            <div class="stock-indicator-wrapper" data-product-id="${this.options.productId}">
                <div class="stock-indicator-main">
                    <div class="stock-badge stock-badge-${overallStatus}">
                        <span class="stock-icon">${statusInfo.icon}</span>
                        <span class="stock-text">${statusInfo.text}</span>
                        <span class="stock-count">${this.formatNumber(this.stockData.total_available)} Available</span>
                    </div>
                    
                    ${this.options.showDetails ? `
                        <div class="stock-details">
                            <div class="stock-summary">
                                <div class="stock-stat">
                                    <label>Total Stock:</label>
                                    <span>${this.formatNumber(this.stockData.total_stock)}</span>
                                </div>
                                <div class="stock-stat">
                                    <label>Reserved:</label>
                                    <span>${this.formatNumber(this.stockData.total_stock - this.stockData.total_available)}</span>
                                </div>
                                <div class="stock-stat">
                                    <label>Warehouses:</label>
                                    <span>${this.stockData.warehouse_count}</span>
                                </div>
                            </div>
                            
                            ${warehouseList ? `
                                <div class="warehouse-list">
                                    <h4>Warehouse Stock Levels</h4>
                                    ${warehouseList}
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>
                
                <div class="stock-last-updated">
                    Last updated: ${this.formatTimestamp(new Date())}
                </div>
            </div>
        `;
    }
    
    renderWarehouse(warehouse) {
        const whStatus = this.getWarehouseStatus(warehouse);
        const statusInfo = this.getStatusInfo(whStatus);
        
        return `
            <div class="warehouse-item warehouse-${whStatus}">
                <div class="warehouse-header">
                    <span class="warehouse-name">${warehouse.warehouse_name}</span>
                    <span class="warehouse-code">(${warehouse.warehouse_code})</span>
                    <span class="warehouse-status-badge">${statusInfo.icon}</span>
                </div>
                <div class="warehouse-stock">
                    <div class="stock-bar">
                        <div class="stock-bar-fill stock-bar-${whStatus}" 
                             style="width: ${this.calculateStockPercentage(warehouse)}%"></div>
                    </div>
                    <div class="stock-numbers">
                        <span class="available">${this.formatNumber(warehouse.available_stock)} available</span>
                        ${warehouse.reserved_stock > 0 ? 
                            `<span class="reserved">${this.formatNumber(warehouse.reserved_stock)} reserved</span>` : ''
                        }
                    </div>
                </div>
                ${warehouse.reorder_point > 0 ? `
                    <div class="reorder-info">
                        Reorder at: ${this.formatNumber(warehouse.reorder_point)}
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    renderError(message) {
        this.container.innerHTML = `
            <div class="stock-indicator-wrapper error">
                <div class="stock-indicator-error">
                    <span class="error-icon">⚠️</span>
                    <span class="error-message">${message}</span>
                    <button class="retry-button" onclick="this.closest('.stock-indicator-wrapper').stockIndicator.fetchStockData()">
                        Retry
                    </button>
                </div>
            </div>
        `;
        
        // Store reference for retry button
        this.container.querySelector('.stock-indicator-wrapper').stockIndicator = this;
    }
    
    calculateOverallStatus() {
        if (!this.stockData || !this.stockData.warehouses) return 'unknown';
        
        let hasStock = false;
        let hasLowStock = false;
        
        for (const warehouse of this.stockData.warehouses) {
            if (warehouse.current_stock > 0) hasStock = true;
            if (warehouse.current_stock <= warehouse.reorder_point) hasLowStock = true;
        }
        
        if (!hasStock) return 'out_of_stock';
        if (hasLowStock) return 'low_stock';
        return 'in_stock';
    }
    
    getWarehouseStatus(warehouse) {
        if (warehouse.current_stock <= 0) return 'out_of_stock';
        if (warehouse.current_stock <= warehouse.reorder_point) return 'low_stock';
        return 'in_stock';
    }
    
    getStatusInfo(status) {
        const statusMap = {
            'in_stock': { icon: '✅', text: 'In Stock', color: '#28a745' },
            'low_stock': { icon: '⚠️', text: 'Low Stock', color: '#ffc107' },
            'out_of_stock': { icon: '❌', text: 'Out of Stock', color: '#dc3545' },
            'unknown': { icon: '❓', text: 'Unknown', color: '#6c757d' }
        };
        
        return statusMap[status] || statusMap.unknown;
    }
    
    calculateStockPercentage(warehouse) {
        if (!warehouse.reorder_point || warehouse.reorder_point <= 0) {
            return warehouse.current_stock > 0 ? 100 : 0;
        }
        
        const maxLevel = Math.max(warehouse.reorder_point * 2, warehouse.current_stock);
        return Math.min(100, (warehouse.current_stock / maxLevel) * 100);
    }
    
    formatNumber(number) {
        return new Intl.NumberFormat().format(Math.round(number));
    }
    
    formatTimestamp(date) {
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    }
    
    startAutoUpdate() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        
        this.updateTimer = setInterval(() => {
            this.fetchStockData();
        }, this.options.updateInterval);
    }
    
    destroy() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
        }
        
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
    
    getCSS() {
        return `
        .stock-indicator-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
            margin: 10px 0;
        }
        
        .stock-indicator-loading {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #6c757d;
            padding: 20px;
            justify-content: center;
        }
        
        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .stock-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            border-radius: 6px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .stock-badge-in_stock {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .stock-badge-low_stock {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .stock-badge-out_of_stock {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .stock-details {
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
        }
        
        .stock-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .stock-stat {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .stock-stat label {
            font-size: 0.85em;
            color: #6c757d;
            font-weight: 500;
        }
        
        .stock-stat span {
            font-size: 1.1em;
            font-weight: 600;
            color: #212529;
        }
        
        .warehouse-list h4 {
            margin: 0 0 10px 0;
            font-size: 1em;
            color: #495057;
        }
        
        .warehouse-item {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 8px;
        }
        
        .warehouse-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .warehouse-name {
            font-weight: 600;
            color: #212529;
        }
        
        .warehouse-code {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .warehouse-status-badge {
            margin-left: auto;
        }
        
        .stock-bar {
            width: 100%;
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .stock-bar-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .stock-bar-in_stock {
            background-color: #28a745;
        }
        
        .stock-bar-low_stock {
            background-color: #ffc107;
        }
        
        .stock-bar-out_of_stock {
            background-color: #dc3545;
        }
        
        .stock-numbers {
            display: flex;
            justify-content: space-between;
            font-size: 0.9em;
        }
        
        .stock-numbers .available {
            color: #28a745;
            font-weight: 500;
        }
        
        .stock-numbers .reserved {
            color: #ffc107;
            font-weight: 500;
        }
        
        .reorder-info {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .stock-last-updated {
            font-size: 0.8em;
            color: #6c757d;
            text-align: right;
            margin-top: 10px;
            border-top: 1px solid #e9ecef;
            padding-top: 8px;
        }
        
        .stock-indicator-error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 6px;
            color: #721c24;
        }
        
        .retry-button {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
        }
        
        .retry-button:hover {
            background-color: #c82333;
        }
        
        @media (max-width: 768px) {
            .stock-summary {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .warehouse-header {
                flex-wrap: wrap;
            }
        }
        `;
    }
}

// Auto-initialize stock indicators with data-stock-indicator attribute
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-stock-indicator]').forEach(element => {
        const productId = element.getAttribute('data-product-id');
        const options = {
            productId: productId,
            showDetails: element.getAttribute('data-show-details') !== 'false',
            showWarehouse: element.getAttribute('data-show-warehouse') !== 'false',
            updateInterval: parseInt(element.getAttribute('data-update-interval')) || 30000
        };
        
        new StockIndicator(element, options);
    });
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StockIndicator;
}
`;
        
