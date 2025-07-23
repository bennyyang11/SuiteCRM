/**
 * Stock Meter Component
 * Advanced stock level visualization with progress bars and trends
 */

class StockMeter {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.getElementById(container) : container;
        this.options = {
            productId: options.productId || '',
            warehouseId: options.warehouseId || null,
            showTrend: options.showTrend !== false,
            showThresholds: options.showThresholds !== false,
            showActions: options.showActions !== false,
            height: options.height || 200,
            width: options.width || '100%',
            updateInterval: options.updateInterval || 30000,
            apiEndpoint: options.apiEndpoint || '/inventory_api_direct.php',
            ...options
        };
        
        this.stockData = null;
        this.historicalData = [];
        this.updateTimer = null;
        
        this.init();
    }
    
    init() {
        if (!this.container || !this.options.productId) {
            console.error('StockMeter: Invalid container or product ID');
            return;
        }
        
        this.render();
        this.fetchStockData();
        this.startAutoUpdate();
    }
    
    render() {
        this.container.innerHTML = `
            <div class="stock-meter-wrapper" data-product-id="${this.options.productId}">
                <div class="stock-meter-loading">
                    <div class="loading-spinner"></div>
                    <span>Loading stock meter...</span>
                </div>
            </div>
        `;
        
        // Add CSS if not already present
        if (!document.getElementById('stock-meter-styles')) {
            const styles = document.createElement('style');
            styles.id = 'stock-meter-styles';
            styles.textContent = this.getCSS();
            document.head.appendChild(styles);
        }
    }
    
    async fetchStockData() {
        try {
            const url = this.options.warehouseId 
                ? `${this.options.apiEndpoint}?action=get_product_stock&product_id=${this.options.productId}&warehouse_id=${this.options.warehouseId}`
                : `${this.options.apiEndpoint}?action=get_product_stock&product_id=${this.options.productId}`;
                
            const response = await fetch(url);
            const result = await response.json();
            
            if (result.success) {
                this.stockData = result.data;
                this.updateHistoricalData();
                this.renderStockMeter();
            } else {
                this.renderError('Failed to load stock data: ' + (result.error?.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Stock meter fetch error:', error);
            this.renderError('Network error loading stock data');
        }
    }
    
    updateHistoricalData() {
        // Add current data point to historical data
        const timestamp = new Date();
        const totalStock = this.stockData.total_stock || 0;
        
        this.historicalData.push({
            timestamp: timestamp,
            stock: totalStock,
            available: this.stockData.total_available || 0
        });
        
        // Keep only last 20 data points
        if (this.historicalData.length > 20) {
            this.historicalData.shift();
        }
    }
    
    renderStockMeter() {
        if (!this.stockData) return;
        
        const warehouses = this.options.warehouseId 
            ? this.stockData.warehouses.filter(wh => wh.warehouse_id === this.options.warehouseId)
            : this.stockData.warehouses;
            
        const meterContent = warehouses.map(wh => this.renderWarehouseMeter(wh)).join('');
        
        this.container.innerHTML = `
            <div class="stock-meter-wrapper" data-product-id="${this.options.productId}">
                <div class="stock-meter-header">
                    <h3 class="product-name">${this.stockData.product_name || 'Product'}</h3>
                    <div class="stock-totals">
                        <span class="total-stock">${this.formatNumber(this.stockData.total_stock)} Total</span>
                        <span class="available-stock">${this.formatNumber(this.stockData.total_available)} Available</span>
                    </div>
                </div>
                
                <div class="stock-meters">
                    ${meterContent}
                </div>
                
                ${this.options.showTrend ? this.renderTrendChart() : ''}
                ${this.options.showActions ? this.renderActions() : ''}
                
                <div class="stock-meter-footer">
                    Last updated: ${this.formatTimestamp(new Date())}
                </div>
            </div>
        `;
        
        // Initialize any interactive elements
        this.initializeInteractions();
    }
    
    renderWarehouseMeter(warehouse) {
        const currentStock = parseFloat(warehouse.current_stock || 0);
        const reservedStock = parseFloat(warehouse.reserved_stock || 0);
        const reorderPoint = parseFloat(warehouse.reorder_point || 0);
        const maxLevel = Math.max(reorderPoint * 3, currentStock * 1.5, 100);
        
        const currentPercent = (currentStock / maxLevel) * 100;
        const reservedPercent = (reservedStock / maxLevel) * 100;
        const reorderPercent = (reorderPoint / maxLevel) * 100;
        
        const status = this.getStockStatus(currentStock, reorderPoint);
        const statusInfo = this.getStatusInfo(status);
        
        return `
            <div class="warehouse-meter warehouse-${status}">
                <div class="warehouse-meter-header">
                    <div class="warehouse-info">
                        <span class="warehouse-name">${warehouse.warehouse_name}</span>
                        <span class="warehouse-code">(${warehouse.warehouse_code})</span>
                    </div>
                    <div class="status-badge status-${status}">
                        ${statusInfo.icon} ${statusInfo.text}
                    </div>
                </div>
                
                <div class="meter-container">
                    <div class="meter-bar" style="height: ${this.options.height}px;">
                        <div class="meter-background"></div>
                        
                        ${this.options.showThresholds && reorderPoint > 0 ? `
                            <div class="threshold-line reorder-line" 
                                 style="bottom: ${reorderPercent}%;"
                                 title="Reorder Point: ${this.formatNumber(reorderPoint)}">
                                <span class="threshold-label">Reorder</span>
                            </div>
                        ` : ''}
                        
                        <div class="stock-fill current-stock" 
                             style="height: ${Math.min(currentPercent, 100)}%;"
                             title="Current Stock: ${this.formatNumber(currentStock)}">
                        </div>
                        
                        ${reservedStock > 0 ? `
                            <div class="stock-fill reserved-stock" 
                                 style="height: ${Math.min(reservedPercent, 100)}%; bottom: ${Math.min(currentPercent, 100)}%;"
                                 title="Reserved: ${this.formatNumber(reservedStock)}">
                            </div>
                        ` : ''}
                        
                        <div class="meter-scale">
                            <div class="scale-mark scale-0">0</div>
                            <div class="scale-mark scale-25">${this.formatNumber(maxLevel * 0.25)}</div>
                            <div class="scale-mark scale-50">${this.formatNumber(maxLevel * 0.5)}</div>
                            <div class="scale-mark scale-75">${this.formatNumber(maxLevel * 0.75)}</div>
                            <div class="scale-mark scale-100">${this.formatNumber(maxLevel)}</div>
                        </div>
                    </div>
                    
                    <div class="meter-stats">
                        <div class="stat-item">
                            <label>Current:</label>
                            <span class="current-value">${this.formatNumber(currentStock)}</span>
                        </div>
                        <div class="stat-item">
                            <label>Available:</label>
                            <span class="available-value">${this.formatNumber(warehouse.available_stock)}</span>
                        </div>
                        ${reservedStock > 0 ? `
                            <div class="stat-item">
                                <label>Reserved:</label>
                                <span class="reserved-value">${this.formatNumber(reservedStock)}</span>
                            </div>
                        ` : ''}
                        ${reorderPoint > 0 ? `
                            <div class="stat-item">
                                <label>Reorder At:</label>
                                <span class="reorder-value">${this.formatNumber(reorderPoint)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    renderTrendChart() {
        if (this.historicalData.length < 2) {
            return `<div class="trend-chart-placeholder">Collecting trend data...</div>`;
        }
        
        const maxStock = Math.max(...this.historicalData.map(d => d.stock));
        const minStock = Math.min(...this.historicalData.map(d => d.stock));
        const range = maxStock - minStock || 1;
        
        const points = this.historicalData.map((d, i) => {
            const x = (i / (this.historicalData.length - 1)) * 100;
            const y = 100 - ((d.stock - minStock) / range) * 100;
            return `${x},${y}`;
        }).join(' ');
        
        const trend = this.calculateTrend();
        const trendInfo = trend > 0 ? { icon: '📈', text: 'Increasing', class: 'positive' } :
                         trend < 0 ? { icon: '📉', text: 'Decreasing', class: 'negative' } :
                         { icon: '➡️', text: 'Stable', class: 'neutral' };
        
        return `
            <div class="trend-chart-section">
                <div class="trend-header">
                    <h4>Stock Level Trend</h4>
                    <div class="trend-indicator trend-${trendInfo.class}">
                        ${trendInfo.icon} ${trendInfo.text}
                    </div>
                </div>
                <div class="trend-chart">
                    <svg width="100%" height="80" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <polyline points="${points}" 
                                  fill="none" 
                                  stroke="#007bff" 
                                  stroke-width="2" 
                                  vector-effect="non-scaling-stroke"/>
                        <circle cx="${points.split(' ').pop().split(',')[0]}" 
                                cy="${points.split(' ').pop().split(',')[1]}" 
                                r="3" 
                                fill="#007bff"/>
                    </svg>
                </div>
            </div>
        `;
    }
    
    renderActions() {
        return `
            <div class="stock-actions">
                <button class="action-btn reserve-btn" onclick="this.closest('.stock-meter-wrapper').stockMeter.showReserveModal()">
                    📦 Reserve Stock
                </button>
                <button class="action-btn adjust-btn" onclick="this.closest('.stock-meter-wrapper').stockMeter.showAdjustModal()">
                    ⚙️ Adjust Levels
                </button>
                <button class="action-btn refresh-btn" onclick="this.closest('.stock-meter-wrapper').stockMeter.fetchStockData()">
                    🔄 Refresh
                </button>
            </div>
        `;
    }
    
    renderError(message) {
        this.container.innerHTML = `
            <div class="stock-meter-wrapper error">
                <div class="stock-meter-error">
                    <span class="error-icon">⚠️</span>
                    <span class="error-message">${message}</span>
                    <button class="retry-btn" onclick="this.closest('.stock-meter-wrapper').stockMeter.fetchStockData()">
                        Retry
                    </button>
                </div>
            </div>
        `;
        
        this.container.querySelector('.stock-meter-wrapper').stockMeter = this;
    }
    
    getStockStatus(currentStock, reorderPoint) {
        if (currentStock <= 0) return 'out_of_stock';
        if (currentStock <= reorderPoint) return 'low_stock';
        return 'in_stock';
    }
    
    getStatusInfo(status) {
        const statusMap = {
            'in_stock': { icon: '✅', text: 'In Stock', color: '#28a745' },
            'low_stock': { icon: '⚠️', text: 'Low Stock', color: '#ffc107' },
            'out_of_stock': { icon: '❌', text: 'Out of Stock', color: '#dc3545' }
        };
        
        return statusMap[status] || statusMap.in_stock;
    }
    
    calculateTrend() {
        if (this.historicalData.length < 3) return 0;
        
        const recent = this.historicalData.slice(-3);
        const first = recent[0].stock;
        const last = recent[recent.length - 1].stock;
        
        return (last - first) / first;
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
    
    initializeInteractions() {
        this.container.querySelector('.stock-meter-wrapper').stockMeter = this;
    }
    
    showReserveModal() {
        // Implementation for stock reservation modal
        alert('Reserve Stock modal would open here');
    }
    
    showAdjustModal() {
        // Implementation for stock adjustment modal
        alert('Adjust Stock Levels modal would open here');
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
        .stock-meter-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            background: #fff;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stock-meter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .product-name {
            margin: 0;
            font-size: 1.25em;
            font-weight: 600;
            color: #212529;
        }
        
        .stock-totals {
            display: flex;
            gap: 15px;
        }
        
        .total-stock, .available-stock {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .total-stock {
            background-color: #e7f3ff;
            color: #0066cc;
        }
        
        .available-stock {
            background-color: #d4edda;
            color: #155724;
        }
        
        .stock-meters {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .warehouse-meter {
            flex: 1;
            min-width: 250px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .warehouse-meter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .warehouse-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .warehouse-name {
            font-weight: 600;
            color: #212529;
        }
        
        .warehouse-code {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-in_stock {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-low_stock {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-out_of_stock {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .meter-container {
            display: flex;
            gap: 15px;
        }
        
        .meter-bar {
            position: relative;
            width: 40px;
            background: #e9ecef;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .meter-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, #f1f3f4 0%, #e8eaed 100%);
        }
        
        .stock-fill {
            position: absolute;
            left: 0;
            width: 100%;
            transition: height 0.5s ease;
        }
        
        .current-stock {
            background: linear-gradient(to top, #4CAF50, #81C784);
            bottom: 0;
        }
        
        .reserved-stock {
            background: linear-gradient(to top, #FF9800, #FFB74D);
        }
        
        .threshold-line {
            position: absolute;
            left: -5px;
            right: -5px;
            height: 2px;
            background: #dc3545;
            z-index: 10;
        }
        
        .threshold-line::before {
            content: '';
            position: absolute;
            left: -3px;
            top: -3px;
            width: 8px;
            height: 8px;
            background: #dc3545;
            border-radius: 50%;
        }
        
        .threshold-label {
            position: absolute;
            right: 10px;
            top: -10px;
            font-size: 0.7em;
            color: #dc3545;
            font-weight: 500;
        }
        
        .meter-scale {
            position: absolute;
            right: -30px;
            top: 0;
            height: 100%;
            width: 25px;
        }
        
        .scale-mark {
            position: absolute;
            font-size: 0.7em;
            color: #6c757d;
            white-space: nowrap;
        }
        
        .scale-0 { bottom: 0; }
        .scale-25 { bottom: 25%; }
        .scale-50 { bottom: 50%; }
        .scale-75 { bottom: 75%; }
        .scale-100 { top: 0; }
        
        .meter-stats {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 150px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
        }
        
        .stat-item label {
            font-size: 0.85em;
            color: #6c757d;
            font-weight: 500;
        }
        
        .stat-item span {
            font-weight: 600;
            color: #212529;
        }
        
        .trend-chart-section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .trend-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .trend-header h4 {
            margin: 0;
            font-size: 1em;
            color: #495057;
        }
        
        .trend-indicator {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .trend-positive {
            background-color: #d4edda;
            color: #155724;
        }
        
        .trend-negative {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .trend-neutral {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .trend-chart {
            background: white;
            border-radius: 4px;
            padding: 10px;
        }
        
        .trend-chart-placeholder {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }
        
        .stock-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: 1px solid #007bff;
            background: white;
            color: #007bff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            background: #007bff;
            color: white;
        }
        
        .stock-meter-footer {
            font-size: 0.8em;
            color: #6c757d;
            text-align: right;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
        }
        
        .stock-meter-error {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            color: #721c24;
        }
        
        .retry-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .retry-btn:hover {
            background-color: #c82333;
        }
        
        @media (max-width: 768px) {
            .stock-meter-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
                text-align: left;
            }
            
            .stock-meters {
                flex-direction: column;
            }
            
            .warehouse-meter {
                min-width: auto;
            }
            
            .meter-container {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            
            .meter-bar {
                width: 100%;
                height: 30px !important;
            }
            
            .stock-fill {
                height: 100% !important;
                width: auto;
                left: 0;
                right: auto;
            }
            
            .current-stock {
                background: linear-gradient(to right, #4CAF50, #81C784);
            }
            
            .reserved-stock {
                background: linear-gradient(to right, #FF9800, #FFB74D);
                left: auto;
                right: 0;
            }
            
            .threshold-line {
                top: -5px;
                bottom: -5px;
                left: auto;
                right: auto;
                width: 2px;
                height: auto;
            }
            
            .meter-scale {
                position: static;
                display: flex;
                justify-content: space-between;
                margin-top: 5px;
            }
            
            .scale-mark {
                position: static;
            }
        }
        `;
    }
}

// Auto-initialize stock meters with data-stock-meter attribute
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-stock-meter]').forEach(element => {
        const productId = element.getAttribute('data-product-id');
        const warehouseId = element.getAttribute('data-warehouse-id');
        const options = {
            productId: productId,
            warehouseId: warehouseId,
            showTrend: element.getAttribute('data-show-trend') !== 'false',
            showThresholds: element.getAttribute('data-show-thresholds') !== 'false',
            showActions: element.getAttribute('data-show-actions') !== 'false',
            height: parseInt(element.getAttribute('data-height')) || 200,
            updateInterval: parseInt(element.getAttribute('data-update-interval')) || 30000
        };
        
        new StockMeter(element, options);
    });
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StockMeter;
}
