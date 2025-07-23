{* Manager Overview Dashboard Template *}
<div id="manager-dashboard" class="manager-dashboard">
    <div class="dashboard-header">
        <div class="header-controls">
            <button class="back-btn" onclick="history.back()">
                <i class="fa fa-arrow-left"></i>
            </button>
            <h1 class="dashboard-title">Team Overview</h1>
            <div class="header-actions">
                <button class="refresh-btn" onclick="refreshDashboard()" title="Refresh">
                    <i class="fa fa-refresh"></i>
                </button>
                <button class="settings-btn" onclick="openDashboardSettings()" title="Settings">
                    <i class="fa fa-cog"></i>
                </button>
            </div>
        </div>
        
        {* Real-time status indicator *}
        <div class="status-indicator">
            <div class="status-dot online" id="statusDot"></div>
            <span class="status-text" id="statusText">Live</span>
            <span class="last-updated" id="lastUpdated">Updated just now</span>
        </div>
    </div>

    {* Key Performance Indicators *}
    <div class="kpi-grid">
        <div class="kpi-card primary" onclick="drillDownKPI('totalOrders')">
            <div class="kpi-icon">
                <i class="fa fa-tasks"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" id="kpiTotalOrders">0</div>
                <div class="kpi-label">Active Orders</div>
                <div class="kpi-trend" id="kpiTotalOrdersTrend">
                    <i class="fa fa-arrow-up"></i>
                    <span>+12%</span>
                </div>
            </div>
        </div>
        
        <div class="kpi-card success" onclick="drillDownKPI('pipelineValue')">
            <div class="kpi-icon">
                <i class="fa fa-dollar-sign"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" id="kpiPipelineValue">$0</div>
                <div class="kpi-label">Pipeline Value</div>
                <div class="kpi-trend" id="kpiPipelineValueTrend">
                    <i class="fa fa-arrow-up"></i>
                    <span>+8%</span>
                </div>
            </div>
        </div>
        
        <div class="kpi-card warning" onclick="drillDownKPI('conversionRate')">
            <div class="kpi-icon">
                <i class="fa fa-chart-line"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" id="kpiConversionRate">0%</div>
                <div class="kpi-label">Conversion Rate</div>
                <div class="kpi-trend" id="kpiConversionRateTrend">
                    <i class="fa fa-arrow-down"></i>
                    <span>-2%</span>
                </div>
            </div>
        </div>
        
        <div class="kpi-card danger" onclick="drillDownKPI('overdueCount')">
            <div class="kpi-icon">
                <i class="fa fa-exclamation-triangle"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" id="kpiOverdueCount">0</div>
                <div class="kpi-label">Overdue Orders</div>
                <div class="kpi-trend" id="kpiOverdueCountTrend">
                    <i class="fa fa-arrow-down"></i>
                    <span>-15%</span>
                </div>
            </div>
        </div>
    </div>

    {* Alert Dashboard *}
    <div class="alerts-section" id="alertsSection">
        <div class="section-header">
            <h2>Priority Alerts</h2>
            <button class="view-all-btn" onclick="viewAllAlerts()">View All</button>
        </div>
        <div class="alerts-container" id="alertsContainer">
            {* Alerts will be populated by JavaScript *}
        </div>
    </div>

    {* Team Performance Overview *}
    <div class="team-performance-section">
        <div class="section-header">
            <h2>Team Performance</h2>
            <div class="time-range-selector">
                <select id="teamTimeRange" onchange="updateTeamPerformance()">
                    <option value="today">Today</option>
                    <option value="week" selected>This Week</option>
                    <option value="month">This Month</option>
                    <option value="quarter">This Quarter</option>
                </select>
            </div>
        </div>
        
        <div class="team-grid" id="teamGrid">
            {* Team member cards will be populated by JavaScript *}
        </div>
    </div>

    {* Pipeline Velocity Chart *}
    <div class="velocity-section">
        <div class="section-header">
            <h2>Pipeline Velocity</h2>
            <div class="chart-controls">
                <button class="chart-type-btn active" data-type="velocity" onclick="switchChart('velocity')">
                    Velocity
                </button>
                <button class="chart-type-btn" data-type="conversion" onclick="switchChart('conversion')">
                    Conversion
                </button>
                <button class="chart-type-btn" data-type="distribution" onclick="switchChart('distribution')">
                    Distribution
                </button>
            </div>
        </div>
        
        <div class="chart-container">
            <canvas id="velocityChart" width="400" height="200"></canvas>
        </div>
    </div>

    {* Bottleneck Analysis *}
    <div class="bottleneck-section">
        <div class="section-header">
            <h2>Bottleneck Analysis</h2>
            <button class="analyze-btn" onclick="runBottleneckAnalysis()">
                <i class="fa fa-search"></i>
                Analyze
            </button>
        </div>
        
        <div class="bottleneck-grid" id="bottleneckGrid">
            {* Bottleneck cards will be populated by JavaScript *}
        </div>
    </div>

    {* Quick Actions Panel *}
    <div class="quick-actions-panel">
        <div class="section-header">
            <h2>Quick Actions</h2>
        </div>
        
        <div class="actions-grid">
            <button class="action-card" onclick="bulkAssignOrders()">
                <i class="fa fa-users"></i>
                <span>Bulk Assign</span>
            </button>
            
            <button class="action-card" onclick="generateReport()">
                <i class="fa fa-file-alt"></i>
                <span>Generate Report</span>
            </button>
            
            <button class="action-card" onclick="scheduleTeamMeeting()">
                <i class="fa fa-calendar"></i>
                <span>Team Meeting</span>
            </button>
            
            <button class="action-card" onclick="exportData()">
                <i class="fa fa-download"></i>
                <span>Export Data</span>
            </button>
            
            <button class="action-card" onclick="manageUsers()">
                <i class="fa fa-user-cog"></i>
                <span>Manage Users</span>
            </button>
            
            <button class="action-card" onclick="configureWorkflows()">
                <i class="fa fa-sitemap"></i>
                <span>Workflows</span>
            </button>
        </div>
    </div>

    {* Recent Activity Feed *}
    <div class="activity-section">
        <div class="section-header">
            <h2>Recent Activity</h2>
            <button class="activity-filter-btn" onclick="toggleActivityFilters()">
                <i class="fa fa-filter"></i>
            </button>
        </div>
        
        <div class="activity-filters" id="activityFilters" style="display: none;">
            <div class="filter-chips">
                <button class="filter-chip active" data-type="all">All</button>
                <button class="filter-chip" data-type="stage_changes">Stage Changes</button>
                <button class="filter-chip" data-type="assignments">Assignments</button>
                <button class="filter-chip" data-type="notes">Notes</button>
                <button class="filter-chip" data-type="calls">Calls</button>
            </div>
        </div>
        
        <div class="activity-feed" id="activityFeed">
            {* Activity items will be populated by JavaScript *}
        </div>
    </div>

    {* Drill-down Modal *}
    <div class="drill-down-modal" id="drillDownModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="drillDownTitle">Detailed View</h3>
                <button class="modal-close" onclick="closeDrillDown()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="drillDownContent">
                {* Drill-down content will be loaded here *}
            </div>
        </div>
    </div>

    {* Settings Modal *}
    <div class="settings-modal" id="settingsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Dashboard Settings</h3>
                <button class="modal-close" onclick="closeSettings()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="settings-section">
                    <h4>Refresh Settings</h4>
                    <div class="setting-item">
                        <label>Auto Refresh</label>
                        <input type="checkbox" id="autoRefresh" checked onchange="updateAutoRefresh()">
                    </div>
                    <div class="setting-item">
                        <label>Refresh Interval (seconds)</label>
                        <input type="number" id="refreshInterval" value="30" min="10" max="300" onchange="updateRefreshInterval()">
                    </div>
                </div>
                
                <div class="settings-section">
                    <h4>Notification Settings</h4>
                    <div class="setting-item">
                        <label>Desktop Notifications</label>
                        <input type="checkbox" id="desktopNotifications" checked onchange="updateNotificationSettings()">
                    </div>
                    <div class="setting-item">
                        <label>Alert Sound</label>
                        <input type="checkbox" id="alertSound" checked onchange="updateNotificationSettings()">
                    </div>
                </div>
                
                <div class="settings-section">
                    <h4>Display Settings</h4>
                    <div class="setting-item">
                        <label>Compact View</label>
                        <input type="checkbox" id="compactView" onchange="updateDisplaySettings()">
                    </div>
                    <div class="setting-item">
                        <label>Show Trends</label>
                        <input type="checkbox" id="showTrends" checked onchange="updateDisplaySettings()">
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeSettings()">Cancel</button>
                <button class="btn-primary" onclick="saveSettings()">Save Settings</button>
            </div>
        </div>
    </div>

    {* Loading Overlay *}
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Loading dashboard data...</div>
    </div>
</div>

{* CSS Styles *}
<style>
.manager-dashboard {
    background: #f5f7fa;
    min-height: 100vh;
    padding: 20px;
}

.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
}

.header-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.dashboard-title {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
}

.header-actions button {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    padding: 12px;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.header-actions button:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.1);
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    opacity: 0.9;
}

.status-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #28a745;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.status-dot.offline {
    background: #dc3545;
    animation: none;
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.kpi-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
}

.kpi-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

.kpi-card.primary {
    border-left-color: #667eea;
}

.kpi-card.success {
    border-left-color: #28a745;
}

.kpi-card.warning {
    border-left-color: #ffc107;
}

.kpi-card.danger {
    border-left-color: #dc3545;
}

.kpi-card {
    display: flex;
    align-items: center;
    gap: 16px;
}

.kpi-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.kpi-card.primary .kpi-icon {
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.kpi-card.success .kpi-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.kpi-card.warning .kpi-icon {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.kpi-card.danger .kpi-icon {
    background: linear-gradient(135deg, #dc3545, #e83e8c);
}

.kpi-content {
    flex: 1;
}

.kpi-value {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    line-height: 1;
    margin-bottom: 4px;
}

.kpi-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 8px;
}

.kpi-trend {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
}

.kpi-trend .fa-arrow-up {
    color: #28a745;
}

.kpi-trend .fa-arrow-down {
    color: #dc3545;
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.alerts-section {
    margin-bottom: 32px;
}

.alerts-container {
    display: grid;
    gap: 12px;
}

.alert-item {
    background: white;
    border-radius: 8px;
    padding: 16px;
    border-left: 4px solid #dc3545;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.2s ease;
}

.alert-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.alert-item.warning {
    border-left-color: #ffc107;
}

.alert-item.info {
    border-left-color: #17a2b8;
}

.team-performance-section {
    margin-bottom: 32px;
}

.time-range-selector select {
    padding: 8px 16px;
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
    font-size: 14px;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.team-member-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.team-member-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.velocity-section {
    margin-bottom: 32px;
}

.chart-controls {
    display: flex;
    gap: 8px;
}

.chart-type-btn {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.chart-type-btn.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.chart-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-top: 16px;
}

.bottleneck-section {
    margin-bottom: 32px;
}

.analyze-btn {
    background: #667eea;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s ease;
}

.analyze-btn:hover {
    background: #5a6fd8;
    transform: translateY(-1px);
}

.bottleneck-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}

.quick-actions-panel {
    margin-bottom: 32px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
}

.action-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.action-card:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
}

.action-card i {
    font-size: 24px;
}

.action-card span {
    font-size: 14px;
    font-weight: 500;
}

.activity-section {
    margin-bottom: 32px;
}

.activity-filters {
    margin-bottom: 16px;
    padding: 16px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.filter-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.filter-chip {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-chip.active {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.activity-feed {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    padding: 16px;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: start;
    gap: 12px;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    color: white;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.activity-description {
    font-size: 14px;
    color: #666;
    margin-bottom: 4px;
}

.activity-time {
    font-size: 12px;
    color: #999;
}

/* Modal styles */
.drill-down-modal,
.settings-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
}

.drill-down-modal.active,
.settings-modal.active {
    opacity: 1;
    pointer-events: all;
}

.modal-content {
    background: white;
    border-radius: 12px;
    margin: 20px;
    max-width: 800px;
    width: 100%;
    max-height: 80vh;
    overflow: hidden;
    transform: translateY(50px);
    transition: transform 0.3s ease;
}

.drill-down-modal.active .modal-content,
.settings-modal.active .modal-content {
    transform: translateY(0);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #333;
}

.modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: #666;
    padding: 8px;
    cursor: pointer;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #f8f9fa;
    color: #333;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-actions {
    display: flex;
    gap: 12px;
    padding: 20px;
    border-top: 1px solid #e9ecef;
    justify-content: flex-end;
}

.btn-secondary,
.btn-primary {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
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

.btn-secondary:hover {
    background: #e9ecef;
}

.btn-primary:hover {
    background: #5a6fd8;
}

/* Settings styles */
.settings-section {
    margin-bottom: 24px;
}

.settings-section h4 {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
}

.setting-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-item label {
    font-size: 14px;
    color: #666;
    margin: 0;
}

.setting-item input[type="checkbox"] {
    width: 20px;
    height: 20px;
}

.setting-item input[type="number"] {
    width: 80px;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

/* Loading overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.9);
    z-index: 2000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s ease;
}

.loading-overlay.active {
    opacity: 1;
    pointer-events: all;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 16px;
}

.loading-text {
    font-size: 16px;
    color: #666;
}

/* Responsive design */
@media (max-width: 768px) {
    .manager-dashboard {
        padding: 12px;
    }
    
    .dashboard-header {
        padding: 16px;
    }
    
    .dashboard-title {
        font-size: 24px;
    }
    
    .kpi-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .kpi-card {
        padding: 16px;
    }
    
    .kpi-value {
        font-size: 24px;
    }
    
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .actions-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
    
    .chart-controls {
        flex-wrap: wrap;
    }
}

@media (max-width: 480px) {
    .header-controls {
        flex-direction: column;
        gap: 12px;
    }
    
    .header-actions {
        align-self: flex-end;
    }
    
    .kpi-card {
        flex-direction: column;
        text-align: center;
    }
    
    .kpi-icon {
        width: 50px;
        height: 50px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .modal-content {
        margin: 10px;
        max-height: 90vh;
    }
}
</style>
