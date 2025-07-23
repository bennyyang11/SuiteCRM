/**
 * Manager Dashboard JavaScript
 * Handles real-time data updates, KPI displays, team performance, and analytics
 */

class ManagerDashboard {
    constructor() {
        this.autoRefresh = true;
        this.refreshInterval = 30000; // 30 seconds
        this.refreshTimer = null;
        this.socket = null;
        this.charts = {};
        this.currentChart = 'velocity';
        this.settings = this.loadSettings();
        this.isVisible = true;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeWebSocket();
        this.loadDashboardData();
        this.startAutoRefresh();
        this.setupVisibilityDetection();
        this.initializeCharts();
        
        // Apply saved settings
        this.applySettings();
    }

    bindEvents() {
        // Visibility change detection
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
        
        // KPI drill-down events
        document.querySelectorAll('.kpi-card').forEach(card => {
            card.addEventListener('click', this.handleKPIDrillDown.bind(this));
        });
        
        // Chart type switching
        document.querySelectorAll('.chart-type-btn').forEach(btn => {
            btn.addEventListener('click', this.handleChartSwitch.bind(this));
        });
        
        // Activity filter events
        document.querySelectorAll('.filter-chip').forEach(chip => {
            chip.addEventListener('click', this.handleActivityFilter.bind(this));
        });
        
        // Settings events
        document.getElementById('autoRefresh').addEventListener('change', this.updateAutoRefresh.bind(this));
        document.getElementById('refreshInterval').addEventListener('change', this.updateRefreshInterval.bind(this));
        
        // Window focus/blur for performance optimization
        window.addEventListener('focus', this.handleWindowFocus.bind(this));
        window.addEventListener('blur', this.handleWindowBlur.bind(this));
    }

    initializeWebSocket() {
        if (!window.WebSocket) {
            console.warn('WebSocket not supported');
            return;
        }

        try {
            // In production, this would be a proper WebSocket server
            // For demo purposes, we'll simulate real-time updates
            this.simulateRealTimeUpdates();
        } catch (error) {
            console.error('Failed to initialize WebSocket:', error);
        }
    }

    simulateRealTimeUpdates() {
        // Simulate real-time updates every 10 seconds
        setInterval(() => {
            if (this.isVisible) {
                this.updateRealTimeData();
            }
        }, 10000);
    }

    async loadDashboardData() {
        try {
            this.showLoading(true);
            
            // Load all dashboard data in parallel
            const [kpiData, teamData, chartData, alertsData, activityData] = await Promise.all([
                this.loadKPIData(),
                this.loadTeamPerformanceData(),
                this.loadChartData(),
                this.loadAlertsData(),
                this.loadActivityData()
            ]);
            
            this.updateKPIDisplay(kpiData);
            this.updateTeamPerformance(teamData);
            this.updateCharts(chartData);
            this.updateAlerts(alertsData);
            this.updateActivity(activityData);
            
            this.updateLastRefreshTime();
            
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            this.showError('Failed to load dashboard data');
        } finally {
            this.showLoading(false);
        }
    }

    async loadKPIData() {
        try {
            const response = await fetch('/Api/v1/manufacturing/ManagerDashboardAPI.php?action=getKPIData', {
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load KPI data');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error loading KPI data:', error);
            return this.getFallbackKPIData();
        }
    }

    async loadTeamPerformanceData() {
        try {
            const timeRange = document.getElementById('teamTimeRange').value;
            const response = await fetch(`/Api/v1/manufacturing/ManagerDashboardAPI.php?action=getTeamPerformance&timeRange=${timeRange}`, {
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load team performance data');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error loading team performance data:', error);
            return this.getFallbackTeamData();
        }
    }

    async loadChartData() {
        try {
            const response = await fetch('/Api/v1/manufacturing/ManagerDashboardAPI.php?action=getChartData', {
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load chart data');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error loading chart data:', error);
            return this.getFallbackChartData();
        }
    }

    async loadAlertsData() {
        try {
            const response = await fetch('/Api/v1/manufacturing/ManagerDashboardAPI.php?action=getAlerts', {
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load alerts data');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error loading alerts data:', error);
            return this.getFallbackAlertsData();
        }
    }

    async loadActivityData() {
        try {
            const response = await fetch('/Api/v1/manufacturing/ManagerDashboardAPI.php?action=getActivity', {
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load activity data');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error loading activity data:', error);
            return this.getFallbackActivityData();
        }
    }

    updateKPIDisplay(data) {
        // Update total orders
        document.getElementById('kpiTotalOrders').textContent = data.totalOrders || 0;
        this.updateTrend('kpiTotalOrdersTrend', data.totalOrdersTrend);
        
        // Update pipeline value
        document.getElementById('kpiPipelineValue').textContent = '$' + this.formatCurrency(data.pipelineValue || 0);
        this.updateTrend('kpiPipelineValueTrend', data.pipelineValueTrend);
        
        // Update conversion rate
        document.getElementById('kpiConversionRate').textContent = (data.conversionRate || 0) + '%';
        this.updateTrend('kpiConversionRateTrend', data.conversionRateTrend);
        
        // Update overdue count
        document.getElementById('kpiOverdueCount').textContent = data.overdueCount || 0;
        this.updateTrend('kpiOverdueCountTrend', data.overdueCountTrend);
        
        // Animate KPI updates
        this.animateKPIUpdate();
    }

    updateTrend(elementId, trendData) {
        const trendElement = document.getElementById(elementId);
        if (!trendElement || !trendData) return;
        
        const icon = trendElement.querySelector('i');
        const text = trendElement.querySelector('span');
        
        if (trendData.direction === 'up') {
            icon.className = 'fa fa-arrow-up';
            icon.style.color = trendData.isGood ? '#28a745' : '#dc3545';
        } else {
            icon.className = 'fa fa-arrow-down';
            icon.style.color = trendData.isGood ? '#28a745' : '#dc3545';
        }
        
        text.textContent = trendData.percentage;
    }

    animateKPIUpdate() {
        document.querySelectorAll('.kpi-value').forEach(element => {
            element.style.transform = 'scale(1.05)';
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 200);
        });
    }

    updateTeamPerformance(data) {
        const teamGrid = document.getElementById('teamGrid');
        if (!teamGrid || !data.teamMembers) return;
        
        let html = '';
        
        data.teamMembers.forEach(member => {
            html += this.renderTeamMemberCard(member);
        });
        
        teamGrid.innerHTML = html;
    }

    renderTeamMemberCard(member) {
        const performanceClass = this.getPerformanceClass(member.performance);
        
        return `
            <div class="team-member-card" onclick="viewTeamMemberDetails('${member.id}')">
                <div class="member-header">
                    <div class="member-avatar">
                        <img src="${member.avatar || '/themes/SuiteP/images/default-avatar.png'}" 
                             alt="${member.name}" class="avatar-img">
                    </div>
                    <div class="member-info">
                        <h4 class="member-name">${member.name}</h4>
                        <span class="member-role">${member.role}</span>
                    </div>
                    <div class="member-status ${member.status}">
                        <i class="fa fa-circle"></i>
                    </div>
                </div>
                
                <div class="member-metrics">
                    <div class="metric-item">
                        <span class="metric-label">Active Orders</span>
                        <span class="metric-value">${member.activeOrders}</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Completed</span>
                        <span class="metric-value">${member.completedOrders}</span>
                    </div>
                    <div class="metric-item">
                        <span class="metric-label">Performance</span>
                        <span class="metric-value ${performanceClass}">${member.performance}%</span>
                    </div>
                </div>
                
                <div class="member-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${member.performance}%"></div>
                    </div>
                </div>
                
                ${member.alerts > 0 ? `
                    <div class="member-alerts">
                        <i class="fa fa-exclamation-triangle"></i>
                        <span>${member.alerts} alert${member.alerts > 1 ? 's' : ''}</span>
                    </div>
                ` : ''}
            </div>
        `;
    }

    getPerformanceClass(performance) {
        if (performance >= 90) return 'excellent';
        if (performance >= 80) return 'good';
        if (performance >= 70) return 'average';
        return 'needs-improvement';
    }

    initializeCharts() {
        const ctx = document.getElementById('velocityChart');
        if (!ctx) return;
        
        // Initialize Chart.js if available
        if (typeof Chart !== 'undefined') {
            this.charts.velocity = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        } else {
            // Fallback to simple canvas drawing
            this.drawSimpleChart(ctx);
        }
    }

    updateCharts(data) {
        if (!data.charts) return;
        
        const chartData = data.charts[this.currentChart];
        if (!chartData) return;
        
        if (this.charts.velocity) {
            this.charts.velocity.data = chartData;
            this.charts.velocity.update();
        } else {
            this.drawSimpleChart(document.getElementById('velocityChart'), chartData);
        }
    }

    drawSimpleChart(canvas, data) {
        if (!canvas || !canvas.getContext) return;
        
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;
        
        // Clear canvas
        ctx.clearRect(0, 0, width, height);
        
        // Draw placeholder chart
        ctx.strokeStyle = '#667eea';
        ctx.lineWidth = 2;
        ctx.beginPath();
        
        const points = 10;
        for (let i = 0; i < points; i++) {
            const x = (i / (points - 1)) * width;
            const y = height - (Math.random() * height * 0.6 + height * 0.2);
            
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        
        ctx.stroke();
        
        // Add chart title
        ctx.fillStyle = '#333';
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('Pipeline Velocity Trend', width / 2, 20);
    }

    updateAlerts(data) {
        const alertsContainer = document.getElementById('alertsContainer');
        if (!alertsContainer || !data.alerts) return;
        
        if (data.alerts.length === 0) {
            alertsContainer.innerHTML = `
                <div class="no-alerts">
                    <i class="fa fa-check-circle"></i>
                    <span>No active alerts</span>
                </div>
            `;
            return;
        }
        
        let html = '';
        data.alerts.slice(0, 5).forEach(alert => {
            html += this.renderAlertItem(alert);
        });
        
        alertsContainer.innerHTML = html;
    }

    renderAlertItem(alert) {
        return `
            <div class="alert-item ${alert.type}" onclick="viewAlertDetails('${alert.id}')">
                <div class="alert-icon">
                    <i class="fa fa-${this.getAlertIcon(alert.type)}"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title">${alert.title}</div>
                    <div class="alert-description">${alert.description}</div>
                    <div class="alert-time">${this.formatTime(alert.timestamp)}</div>
                </div>
                <div class="alert-action">
                    <i class="fa fa-chevron-right"></i>
                </div>
            </div>
        `;
    }

    getAlertIcon(type) {
        const icons = {
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle',
            success: 'check-circle'
        };
        return icons[type] || 'bell';
    }

    updateActivity(data) {
        const activityFeed = document.getElementById('activityFeed');
        if (!activityFeed || !data.activities) return;
        
        let html = '';
        data.activities.forEach(activity => {
            html += this.renderActivityItem(activity);
        });
        
        activityFeed.innerHTML = html;
    }

    renderActivityItem(activity) {
        return `
            <div class="activity-item" onclick="viewActivityDetails('${activity.id}')">
                <div class="activity-icon" style="background: ${this.getActivityColor(activity.type)}">
                    <i class="fa fa-${this.getActivityIcon(activity.type)}"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-title">${activity.title}</div>
                    <div class="activity-description">${activity.description}</div>
                    <div class="activity-time">${this.formatTime(activity.timestamp)}</div>
                </div>
            </div>
        `;
    }

    getActivityIcon(type) {
        const icons = {
            stage_change: 'arrow-right',
            assignment: 'user',
            note: 'comment',
            call: 'phone',
            email: 'envelope',
            meeting: 'calendar'
        };
        return icons[type] || 'bell';
    }

    getActivityColor(type) {
        const colors = {
            stage_change: '#667eea',
            assignment: '#28a745',
            note: '#ffc107',
            call: '#17a2b8',
            email: '#fd7e14',
            meeting: '#6f42c1'
        };
        return colors[type] || '#6c757d';
    }

    updateRealTimeData() {
        // Simulate real-time updates
        const updates = this.generateRandomUpdates();
        
        updates.forEach(update => {
            this.applyRealTimeUpdate(update);
        });
        
        this.updateLastRefreshTime();
    }

    generateRandomUpdates() {
        const updates = [];
        
        // Random KPI updates
        if (Math.random() < 0.3) {
            updates.push({
                type: 'kpi',
                metric: 'totalOrders',
                value: Math.floor(Math.random() * 50) + 100
            });
        }
        
        if (Math.random() < 0.2) {
            updates.push({
                type: 'alert',
                alert: {
                    id: 'alert_' + Date.now(),
                    type: 'warning',
                    title: 'New Overdue Order',
                    description: 'Order #12345 is now 2 days overdue',
                    timestamp: new Date().toISOString()
                }
            });
        }
        
        return updates;
    }

    applyRealTimeUpdate(update) {
        switch (update.type) {
            case 'kpi':
                const element = document.getElementById(`kpi${this.capitalize(update.metric)}`);
                if (element) {
                    element.textContent = update.value;
                    this.highlightUpdate(element);
                }
                break;
                
            case 'alert':
                this.addNewAlert(update.alert);
                break;
                
            case 'activity':
                this.addNewActivity(update.activity);
                break;
        }
    }

    highlightUpdate(element) {
        element.style.background = '#fff3cd';
        element.style.borderRadius = '4px';
        element.style.padding = '2px 4px';
        
        setTimeout(() => {
            element.style.background = '';
            element.style.borderRadius = '';
            element.style.padding = '';
        }, 2000);
    }

    addNewAlert(alert) {
        const alertsContainer = document.getElementById('alertsContainer');
        if (!alertsContainer) return;
        
        const alertHtml = this.renderAlertItem(alert);
        alertsContainer.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Remove old alerts if too many
        const alerts = alertsContainer.querySelectorAll('.alert-item');
        if (alerts.length > 5) {
            alerts[alerts.length - 1].remove();
        }
    }

    startAutoRefresh() {
        if (!this.autoRefresh) return;
        
        this.refreshTimer = setInterval(() => {
            if (this.isVisible) {
                this.loadDashboardData();
            }
        }, this.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    setupVisibilityDetection() {
        // Page Visibility API
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
            
            if (this.isVisible) {
                this.setOnlineStatus();
                this.loadDashboardData();
            }
        });
    }

    handleVisibilityChange() {
        this.isVisible = !document.hidden;
        
        if (this.isVisible) {
            this.loadDashboardData();
        }
    }

    handleWindowFocus() {
        this.isVisible = true;
        this.loadDashboardData();
    }

    handleWindowBlur() {
        this.isVisible = false;
    }

    handleKPIDrillDown(event) {
        const kpiType = event.currentTarget.dataset.kpiType || 
                       event.currentTarget.onclick?.toString().match(/drillDownKPI\('(.+?)'\)/)?.[1];
        
        if (kpiType) {
            this.openDrillDown(kpiType);
        }
    }

    handleChartSwitch(event) {
        const chartType = event.currentTarget.dataset.type;
        if (chartType) {
            this.switchChart(chartType);
        }
    }

    handleActivityFilter(event) {
        const filterType = event.currentTarget.dataset.type;
        if (filterType) {
            this.filterActivity(filterType);
        }
    }

    openDrillDown(kpiType) {
        const modal = document.getElementById('drillDownModal');
        const title = document.getElementById('drillDownTitle');
        const content = document.getElementById('drillDownContent');
        
        title.textContent = this.getDrillDownTitle(kpiType);
        content.innerHTML = '<div class="loading">Loading detailed data...</div>';
        
        modal.classList.add('active');
        
        // Load drill-down data
        this.loadDrillDownData(kpiType).then(data => {
            content.innerHTML = this.renderDrillDownContent(kpiType, data);
        });
    }

    async loadDrillDownData(kpiType) {
        try {
            const response = await fetch(`/Api/v1/manufacturing/ManagerDashboardAPI.php?action=getDrillDown&type=${kpiType}`, {
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load drill-down data');
            }
            
            return await response.json();
        } catch (error) {
            console.error('Error loading drill-down data:', error);
            return { error: 'Failed to load data' };
        }
    }

    getDrillDownTitle(kpiType) {
        const titles = {
            totalOrders: 'Active Orders Details',
            pipelineValue: 'Pipeline Value Breakdown',
            conversionRate: 'Conversion Rate Analysis',
            overdueCount: 'Overdue Orders'
        };
        return titles[kpiType] || 'Details';
    }

    renderDrillDownContent(kpiType, data) {
        if (data.error) {
            return `<div class="error">Error: ${data.error}</div>`;
        }
        
        // Render specific content based on KPI type
        switch (kpiType) {
            case 'totalOrders':
                return this.renderOrdersList(data.orders);
            case 'pipelineValue':
                return this.renderValueBreakdown(data.breakdown);
            case 'conversionRate':
                return this.renderConversionAnalysis(data.analysis);
            case 'overdueCount':
                return this.renderOverdueOrders(data.overdueOrders);
            default:
                return '<div>No data available</div>';
        }
    }

    switchChart(chartType) {
        // Update active button
        document.querySelectorAll('.chart-type-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-type="${chartType}"]`).classList.add('active');
        
        this.currentChart = chartType;
        
        // Load new chart data
        this.loadChartData().then(data => {
            this.updateCharts(data);
        });
    }

    updateLastRefreshTime() {
        const statusText = document.getElementById('statusText');
        const lastUpdated = document.getElementById('lastUpdated');
        
        if (statusText) statusText.textContent = 'Live';
        if (lastUpdated) lastUpdated.textContent = 'Updated just now';
        
        this.setOnlineStatus();
    }

    setOnlineStatus() {
        const statusDot = document.getElementById('statusDot');
        if (statusDot) {
            statusDot.className = 'status-dot online';
        }
    }

    setOfflineStatus() {
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');
        
        if (statusDot) statusDot.className = 'status-dot offline';
        if (statusText) statusText.textContent = 'Offline';
    }

    showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('active', show);
        }
    }

    showError(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'toast error';
        toast.innerHTML = `
            <i class="fa fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 5000);
    }

    updateAutoRefresh() {
        const checkbox = document.getElementById('autoRefresh');
        this.autoRefresh = checkbox.checked;
        
        if (this.autoRefresh) {
            this.startAutoRefresh();
        } else {
            this.stopAutoRefresh();
        }
        
        this.saveSettings();
    }

    updateRefreshInterval() {
        const input = document.getElementById('refreshInterval');
        this.refreshInterval = parseInt(input.value) * 1000;
        
        if (this.autoRefresh) {
            this.stopAutoRefresh();
            this.startAutoRefresh();
        }
        
        this.saveSettings();
    }

    loadSettings() {
        try {
            const saved = localStorage.getItem('managerDashboardSettings');
            return saved ? JSON.parse(saved) : this.getDefaultSettings();
        } catch (error) {
            return this.getDefaultSettings();
        }
    }

    getDefaultSettings() {
        return {
            autoRefresh: true,
            refreshInterval: 30,
            desktopNotifications: true,
            alertSound: true,
            compactView: false,
            showTrends: true
        };
    }

    saveSettings() {
        const settings = {
            autoRefresh: this.autoRefresh,
            refreshInterval: this.refreshInterval / 1000,
            desktopNotifications: document.getElementById('desktopNotifications').checked,
            alertSound: document.getElementById('alertSound').checked,
            compactView: document.getElementById('compactView').checked,
            showTrends: document.getElementById('showTrends').checked
        };
        
        localStorage.setItem('managerDashboardSettings', JSON.stringify(settings));
        this.settings = settings;
    }

    applySettings() {
        document.getElementById('autoRefresh').checked = this.settings.autoRefresh;
        document.getElementById('refreshInterval').value = this.settings.refreshInterval;
        document.getElementById('desktopNotifications').checked = this.settings.desktopNotifications;
        document.getElementById('alertSound').checked = this.settings.alertSound;
        document.getElementById('compactView').checked = this.settings.compactView;
        document.getElementById('showTrends').checked = this.settings.showTrends;
        
        this.autoRefresh = this.settings.autoRefresh;
        this.refreshInterval = this.settings.refreshInterval * 1000;
    }

    // Utility methods
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) {
            return 'Just now';
        } else if (diff < 3600000) {
            return Math.floor(diff / 60000) + ' minutes ago';
        } else if (diff < 86400000) {
            return Math.floor(diff / 3600000) + ' hours ago';
        } else {
            return date.toLocaleDateString();
        }
    }

    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    // Fallback data for offline scenarios
    getFallbackKPIData() {
        return {
            totalOrders: 127,
            totalOrdersTrend: { direction: 'up', percentage: '+12%', isGood: true },
            pipelineValue: 1850000,
            pipelineValueTrend: { direction: 'up', percentage: '+8%', isGood: true },
            conversionRate: 68,
            conversionRateTrend: { direction: 'down', percentage: '-2%', isGood: false },
            overdueCount: 8,
            overdueCountTrend: { direction: 'down', percentage: '-15%', isGood: true }
        };
    }

    getFallbackTeamData() {
        return {
            teamMembers: [
                {
                    id: '1',
                    name: 'John Smith',
                    role: 'Senior Sales Rep',
                    status: 'online',
                    activeOrders: 15,
                    completedOrders: 42,
                    performance: 92,
                    alerts: 0,
                    avatar: '/themes/SuiteP/images/avatar1.png'
                },
                {
                    id: '2',
                    name: 'Sarah Johnson',
                    role: 'Sales Rep',
                    status: 'away',
                    activeOrders: 12,
                    completedOrders: 38,
                    performance: 86,
                    alerts: 2,
                    avatar: '/themes/SuiteP/images/avatar2.png'
                }
            ]
        };
    }

    getFallbackChartData() {
        return {
            charts: {
                velocity: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    datasets: [{
                        label: 'Average Stage Time (days)',
                        data: [3.2, 2.8, 3.1, 2.5, 2.9],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)'
                    }]
                }
            }
        };
    }

    getFallbackAlertsData() {
        return {
            alerts: [
                {
                    id: '1',
                    type: 'error',
                    title: 'System Integration Error',
                    description: 'Inventory sync failed for Product SKU #12345',
                    timestamp: new Date(Date.now() - 300000).toISOString()
                },
                {
                    id: '2',
                    type: 'warning',
                    title: 'Overdue Order Alert',
                    description: 'Order #ORD-2024-001 is 3 days overdue',
                    timestamp: new Date(Date.now() - 600000).toISOString()
                }
            ]
        };
    }

    getFallbackActivityData() {
        return {
            activities: [
                {
                    id: '1',
                    type: 'stage_change',
                    title: 'Order Stage Updated',
                    description: 'Order #ORD-2024-002 moved to Order Processing',
                    timestamp: new Date(Date.now() - 180000).toISOString()
                },
                {
                    id: '2',
                    type: 'assignment',
                    title: 'Order Assigned',
                    description: 'Order #ORD-2024-003 assigned to John Smith',
                    timestamp: new Date(Date.now() - 360000).toISOString()
                }
            ]
        };
    }
}

// Global functions for template interaction
function refreshDashboard() {
    if (managerDashboard) {
        managerDashboard.loadDashboardData();
    }
}

function openDashboardSettings() {
    const modal = document.getElementById('settingsModal');
    if (modal) {
        modal.classList.add('active');
    }
}

function closeSettings() {
    const modal = document.getElementById('settingsModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function saveSettings() {
    if (managerDashboard) {
        managerDashboard.saveSettings();
    }
    closeSettings();
}

function drillDownKPI(kpiType) {
    if (managerDashboard) {
        managerDashboard.openDrillDown(kpiType);
    }
}

function closeDrillDown() {
    const modal = document.getElementById('drillDownModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function switchChart(chartType) {
    if (managerDashboard) {
        managerDashboard.switchChart(chartType);
    }
}

function updateTeamPerformance() {
    if (managerDashboard) {
        managerDashboard.loadTeamPerformanceData().then(data => {
            managerDashboard.updateTeamPerformance(data);
        });
    }
}

function runBottleneckAnalysis() {
    // Show loading and run analysis
    const button = event.target;
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Analyzing...';
    button.disabled = true;
    
    setTimeout(() => {
        // Simulate analysis completion
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Show results
        const grid = document.getElementById('bottleneckGrid');
        grid.innerHTML = `
            <div class="bottleneck-result">
                <h4>Quote Preparation</h4>
                <p>Average delay: 2.3 days</p>
                <span class="severity high">High Impact</span>
            </div>
            <div class="bottleneck-result">
                <h4>Inventory Check</h4>
                <p>Average delay: 1.8 days</p>
                <span class="severity medium">Medium Impact</span>
            </div>
        `;
    }, 2000);
}

function toggleActivityFilters() {
    const filters = document.getElementById('activityFilters');
    if (filters) {
        filters.style.display = filters.style.display === 'none' ? 'block' : 'none';
    }
}

// Action functions
function bulkAssignOrders() {
    window.location.href = '/index.php?module=Orders&action=BulkAssign';
}

function generateReport() {
    window.location.href = '/index.php?module=Reports&action=Generate&type=pipeline';
}

function scheduleTeamMeeting() {
    window.location.href = '/index.php?module=Meetings&action=EditView&prefill=team_meeting';
}

function exportData() {
    window.location.href = '/index.php?module=Administration&action=Export&type=pipeline_data';
}

function manageUsers() {
    window.location.href = '/index.php?module=Users&action=index';
}

function configureWorkflows() {
    window.location.href = '/index.php?module=WorkFlow&action=index';
}

// Initialize manager dashboard on page load
let managerDashboard;
document.addEventListener('DOMContentLoaded', () => {
    managerDashboard = new ManagerDashboard();
});
