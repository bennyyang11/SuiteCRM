{* Mobile-Optimized Pipeline Dashboard Template *}
<div id="mobile-pipeline-dashboard" class="mobile-dashboard">
    <div class="mobile-header">
        <div class="header-controls">
            <button class="back-btn" onclick="history.back()">
                <i class="fa fa-arrow-left"></i>
            </button>
            <h1 class="page-title">Order Pipeline</h1>
            <button class="menu-btn" onclick="toggleMobileMenu()">
                <i class="fa fa-bars"></i>
            </button>
        </div>
        
        {* Pull-to-refresh indicator *}
        <div class="pull-refresh-indicator" id="pullRefreshIndicator">
            <div class="refresh-spinner"></div>
            <span>Pull to refresh</span>
        </div>
        
        {* Quick stats bar *}
        <div class="quick-stats">
            <div class="stat-item">
                <span class="stat-number" id="totalOrders">0</span>
                <span class="stat-label">Orders</span>
            </div>
            <div class="stat-item">
                <span class="stat-number" id="totalValue">$0</span>
                <span class="stat-label">Value</span>
            </div>
            <div class="stat-item urgent">
                <span class="stat-number" id="urgentCount">0</span>
                <span class="stat-label">Urgent</span>
            </div>
        </div>
    </div>

    {* Search and Filter Bar *}
    <div class="mobile-search-bar">
        <div class="search-input-container">
            <input type="text" id="mobileSearchInput" placeholder="Search orders..." 
                   class="search-input" autocomplete="off">
            <button class="search-btn" onclick="performMobileSearch()">
                <i class="fa fa-search"></i>
            </button>
        </div>
        <button class="filter-toggle" onclick="toggleMobileFilters()" id="filterToggleBtn">
            <i class="fa fa-filter"></i>
            <span class="filter-count" id="filterCount" style="display: none;">0</span>
        </button>
    </div>

    {* Mobile Filters Panel *}
    <div class="mobile-filters-panel" id="mobileFiltersPanel">
        <div class="filter-header">
            <h3>Filters</h3>
            <button class="close-filters" onclick="toggleMobileFilters()">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="filter-content">
            <div class="filter-group">
                <label>Priority</label>
                <div class="filter-options">
                    <label class="checkbox-label">
                        <input type="checkbox" value="urgent" name="priority">
                        <span>Urgent</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" value="high" name="priority">
                        <span>High</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" value="normal" name="priority">
                        <span>Normal</span>
                    </label>
                </div>
            </div>
            
            <div class="filter-group">
                <label>Assigned To</label>
                <select name="assignedUser" class="filter-select">
                    <option value="">All Users</option>
                    <option value="me">My Orders</option>
                    <option value="unassigned">Unassigned</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Date Range</label>
                <select name="dateRange" class="filter-select">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
            
            <div class="filter-actions">
                <button class="btn-clear" onclick="clearMobileFilters()">Clear All</button>
                <button class="btn-apply" onclick="applyMobileFilters()">Apply</button>
            </div>
        </div>
    </div>

    {* View Mode Toggle *}
    <div class="view-mode-toggle">
        <button class="view-btn active" data-view="compact" onclick="switchView('compact')">
            <i class="fa fa-th-list"></i>
        </button>
        <button class="view-btn" data-view="detailed" onclick="switchView('detailed')">
            <i class="fa fa-th-large"></i>
        </button>
        <button class="view-btn" data-view="list" onclick="switchView('list')">
            <i class="fa fa-list"></i>
        </button>
    </div>

    {* Pipeline Stages Container *}
    <div class="mobile-pipeline-container" id="mobilePipelineContainer">
        <div class="pipeline-loading" id="pipelineLoading">
            <div class="loading-spinner"></div>
            <span>Loading pipeline...</span>
        </div>
        
        {* Pipeline stages will be dynamically loaded here *}
        <div class="pipeline-stages" id="pipelineStages">
            {* Stages will be populated by JavaScript *}
        </div>
    </div>

    {* Floating Action Button *}
    <div class="mobile-fab" onclick="openQuickActions()">
        <i class="fa fa-plus"></i>
        <div class="fab-tooltip">Quick Actions</div>
    </div>

    {* Quick Actions Menu *}
    <div class="quick-actions-menu" id="quickActionsMenu">
        <div class="quick-action" onclick="createNewOrder()">
            <i class="fa fa-plus-circle"></i>
            <span>New Order</span>
        </div>
        <div class="quick-action" onclick="refreshPipeline()">
            <i class="fa fa-refresh"></i>
            <span>Refresh</span>
        </div>
        <div class="quick-action" onclick="openBulkActions()">
            <i class="fa fa-tasks"></i>
            <span>Bulk Actions</span>
        </div>
        <div class="quick-action" onclick="openSettings()">
            <i class="fa fa-cog"></i>
            <span>Settings</span>
        </div>
    </div>

    {* Order Details Modal *}
    <div class="order-modal" id="orderModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalOrderTitle">Order Details</h3>
                <button class="modal-close" onclick="closeOrderModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="modalOrderContent">
                {* Order details will be loaded here *}
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeOrderModal()">Close</button>
                <button class="btn-primary" onclick="editOrder()">Edit Order</button>
            </div>
        </div>
    </div>

    {* Bottom Tab Navigation *}
    <div class="mobile-bottom-nav">
        <div class="nav-item active" onclick="showPipelineView()">
            <i class="fa fa-tasks"></i>
            <span>Pipeline</span>
        </div>
        <div class="nav-item" onclick="showCalendarView()">
            <i class="fa fa-calendar"></i>
            <span>Calendar</span>
        </div>
        <div class="nav-item" onclick="showReportsView()">
            <i class="fa fa-chart-bar"></i>
            <span>Reports</span>
        </div>
        <div class="nav-item" onclick="showProfileView()">
            <i class="fa fa-user"></i>
            <span>Profile</span>
        </div>
    </div>
</div>

{* CSS Styles *}
<style>
.mobile-dashboard {
    min-height: 100vh;
    background: #f8f9fa;
    position: relative;
    padding-bottom: 80px; /* Space for bottom nav */
}

.mobile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 16px;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.header-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.header-controls button {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 12px;
    border-radius: 50%;
    font-size: 18px;
    min-width: 44px;
    min-height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.page-title {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
}

.pull-refresh-indicator {
    position: absolute;
    top: -60px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    color: white;
    opacity: 0;
    transition: all 0.3s ease;
}

.pull-refresh-indicator.active {
    opacity: 1;
    top: 10px;
}

.refresh-spinner {
    width: 24px;
    height: 24px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.quick-stats {
    display: flex;
    justify-content: space-around;
    margin-top: 16px;
}

.stat-item {
    text-align: center;
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 12px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-item.urgent .stat-number {
    color: #ff6b6b;
}

.mobile-search-bar {
    padding: 16px;
    background: white;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    gap: 12px;
    align-items: center;
    position: sticky;
    top: 120px;
    z-index: 999;
}

.search-input-container {
    flex: 1;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 25px;
    font-size: 16px;
    background: #f8f9fa;
}

.search-btn {
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    background: #667eea;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 20px;
    min-width: 44px;
    min-height: 36px;
}

.filter-toggle {
    background: #f8f9fa;
    border: 1px solid #ddd;
    padding: 12px;
    border-radius: 8px;
    position: relative;
    min-width: 44px;
    min-height: 44px;
}

.filter-count {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #ff6b6b;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.mobile-filters-panel {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: white;
    z-index: 2000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.mobile-filters-panel.active {
    transform: translateX(0);
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 16px;
    border-bottom: 1px solid #e9ecef;
    background: #667eea;
    color: white;
}

.filter-content {
    padding: 20px 16px;
}

.filter-group {
    margin-bottom: 24px;
}

.filter-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
    margin-bottom: 0;
}

.checkbox-label input {
    min-width: 20px;
    min-height: 20px;
}

.filter-select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 16px;
    background: white;
}

.filter-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
}

.btn-clear, .btn-apply {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
}

.btn-clear {
    background: #f8f9fa;
    color: #666;
    border: 1px solid #ddd;
}

.btn-apply {
    background: #667eea;
    color: white;
}

.view-mode-toggle {
    display: flex;
    justify-content: center;
    padding: 12px;
    background: white;
    border-bottom: 1px solid #e9ecef;
    gap: 4px;
}

.view-btn {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    border-radius: 6px;
    min-width: 44px;
    min-height: 36px;
}

.view-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.mobile-pipeline-container {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

.pipeline-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #666;
}

.loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #f3f3f3;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

.mobile-fab {
    position: fixed;
    bottom: 100px;
    right: 20px;
    width: 56px;
    height: 56px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    cursor: pointer;
    z-index: 1000;
    transition: all 0.3s ease;
}

.mobile-fab:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
}

.fab-tooltip {
    position: absolute;
    right: 70px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease;
}

.mobile-fab:hover .fab-tooltip {
    opacity: 1;
}

.quick-actions-menu {
    position: fixed;
    bottom: 100px;
    right: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    padding: 8px;
    transform: scale(0);
    transform-origin: bottom right;
    transition: all 0.3s ease;
    z-index: 1001;
    min-width: 160px;
}

.quick-actions-menu.active {
    transform: scale(1);
}

.quick-action {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.2s ease;
    min-height: 44px;
}

.quick-action:hover {
    background: #f8f9fa;
}

.quick-action i {
    width: 20px;
    color: #667eea;
    font-size: 16px;
}

.order-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 3000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
}

.order-modal.active {
    opacity: 1;
    pointer-events: all;
}

.modal-content {
    background: white;
    border-radius: 12px;
    margin: 20px;
    max-width: 100%;
    max-height: 80vh;
    overflow: hidden;
    transform: translateY(50px);
    transition: transform 0.3s ease;
}

.order-modal.active .modal-content {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #666;
    padding: 8px;
    min-width: 44px;
    min-height: 44px;
    border-radius: 50%;
}

.modal-body {
    padding: 20px;
    max-height: 50vh;
    overflow-y: auto;
}

.modal-actions {
    display: flex;
    gap: 12px;
    padding: 20px;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.btn-secondary, .btn-primary {
    flex: 1;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    min-height: 44px;
}

.btn-secondary {
    background: #f8f9fa;
    color: #666;
    border: 1px solid #ddd;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.mobile-bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #e9ecef;
    padding: 8px 0;
    display: flex;
    justify-content: space-around;
    z-index: 1000;
}

.nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 12px;
    color: #666;
    text-decoration: none;
    cursor: pointer;
    min-width: 60px;
    transition: color 0.2s ease;
}

.nav-item.active {
    color: #667eea;
}

.nav-item i {
    font-size: 20px;
    margin-bottom: 4px;
}

.nav-item span {
    font-size: 12px;
    font-weight: 500;
}

/* Responsive adjustments */
@media (max-width: 375px) {
    .mobile-header {
        padding: 16px 12px;
    }
    
    .page-title {
        font-size: 20px;
    }
    
    .quick-stats .stat-number {
        font-size: 20px;
    }
    
    .mobile-search-bar {
        padding: 12px;
    }
    
    .mobile-pipeline-container {
        padding: 12px;
    }
}

/* Touch gesture feedback */
.order-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.order-card.swiping {
    transform: translateX(var(--swipe-x, 0px));
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.order-card.swipe-right {
    background: linear-gradient(90deg, transparent 0%, rgba(40, 167, 69, 0.1) 100%);
}

.order-card.swipe-left {
    background: linear-gradient(90deg, rgba(220, 53, 69, 0.1) 0%, transparent 100%);
}

/* Haptic feedback simulation */
.haptic-feedback {
    animation: haptic-pulse 0.1s ease;
}

@keyframes haptic-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
}
</style>
