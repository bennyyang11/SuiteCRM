/**
 * Mobile Pipeline Dashboard JavaScript
 * Handles touch gestures, swipe interactions, and mobile-optimized UI
 */

class MobilePipeline {
    constructor() {
        this.currentView = 'compact';
        this.filters = {
            priority: [],
            assignedUser: '',
            dateRange: 'all',
            searchText: ''
        };
        this.orders = [];
        this.stages = [];
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchCurrentX = 0;
        this.touchCurrentY = 0;
        this.isSwiping = false;
        this.swipeThreshold = 100;
        this.pullRefreshDistance = 80;
        this.isPullRefreshing = false;
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadPipelineData();
        this.setupPullToRefresh();
        this.setupServiceWorker();
        
        // Initialize view
        this.renderPipeline();
    }

    bindEvents() {
        // Search functionality
        document.getElementById('mobileSearchInput').addEventListener('input', 
            this.debounce(this.handleSearch.bind(this), 300));
        
        // Filter events
        document.querySelectorAll('input[name="priority"]').forEach(input => {
            input.addEventListener('change', this.updateFilters.bind(this));
        });
        
        document.querySelector('select[name="assignedUser"]').addEventListener('change', 
            this.updateFilters.bind(this));
        
        document.querySelector('select[name="dateRange"]').addEventListener('change', 
            this.updateFilters.bind(this));

        // Touch events for swipe gestures
        document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
        
        // Prevent context menu on long press for better UX
        document.addEventListener('contextmenu', (e) => {
            if (e.target.closest('.order-card')) {
                e.preventDefault();
            }
        });

        // Back button handling
        window.addEventListener('popstate', this.handleBackNavigation.bind(this));
        
        // Online/offline handling
        window.addEventListener('online', this.handleOnline.bind(this));
        window.addEventListener('offline', this.handleOffline.bind(this));
    }

    setupPullToRefresh() {
        let startY = 0;
        let currentY = 0;
        let isPulling = false;
        
        const container = document.getElementById('mobilePipelineContainer');
        const indicator = document.getElementById('pullRefreshIndicator');
        
        container.addEventListener('touchstart', (e) => {
            if (container.scrollTop === 0) {
                startY = e.touches[0].clientY;
                isPulling = true;
            }
        }, { passive: true });
        
        container.addEventListener('touchmove', (e) => {
            if (!isPulling) return;
            
            currentY = e.touches[0].clientY;
            const pullDistance = currentY - startY;
            
            if (pullDistance > 0 && container.scrollTop === 0) {
                e.preventDefault();
                const progress = Math.min(pullDistance / this.pullRefreshDistance, 1);
                
                indicator.style.opacity = progress;
                indicator.style.transform = `translateX(-50%) translateY(${pullDistance * 0.5}px)`;
                
                if (pullDistance >= this.pullRefreshDistance) {
                    indicator.classList.add('active');
                    this.triggerHapticFeedback();
                }
            }
        }, { passive: false });
        
        container.addEventListener('touchend', () => {
            if (isPulling) {
                const pullDistance = currentY - startY;
                
                if (pullDistance >= this.pullRefreshDistance && !this.isPullRefreshing) {
                    this.refreshPipeline();
                } else {
                    indicator.style.opacity = '0';
                    indicator.style.transform = 'translateX(-50%) translateY(-60px)';
                    indicator.classList.remove('active');
                }
                
                isPulling = false;
                startY = 0;
                currentY = 0;
            }
        });
    }

    async setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                await navigator.serviceWorker.register('/service-worker.js');
                console.log('Service Worker registered successfully');
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
    }

    handleTouchStart(e) {
        const orderCard = e.target.closest('.order-card');
        if (!orderCard) return;
        
        this.touchStartX = e.touches[0].clientX;
        this.touchStartY = e.touches[0].clientY;
        this.touchCurrentX = this.touchStartX;
        this.touchCurrentY = this.touchStartY;
        this.isSwiping = false;
        
        orderCard.style.transition = 'none';
    }

    handleTouchMove(e) {
        const orderCard = e.target.closest('.order-card');
        if (!orderCard) return;
        
        this.touchCurrentX = e.touches[0].clientX;
        this.touchCurrentY = e.touches[0].clientY;
        
        const deltaX = this.touchCurrentX - this.touchStartX;
        const deltaY = this.touchCurrentY - this.touchStartY;
        
        // Determine if this is a horizontal swipe
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
            e.preventDefault();
            this.isSwiping = true;
            
            // Visual feedback during swipe
            orderCard.classList.add('swiping');
            orderCard.style.setProperty('--swipe-x', `${deltaX * 0.3}px`);
            
            // Add direction-specific styling
            if (deltaX > 50) {
                orderCard.classList.add('swipe-right');
                orderCard.classList.remove('swipe-left');
            } else if (deltaX < -50) {
                orderCard.classList.add('swipe-left');
                orderCard.classList.remove('swipe-right');
            }
        }
    }

    handleTouchEnd(e) {
        const orderCard = e.target.closest('.order-card');
        if (!orderCard) return;
        
        const deltaX = this.touchCurrentX - this.touchStartX;
        const deltaY = this.touchCurrentY - this.touchStartY;
        
        // Reset visual state
        orderCard.style.transition = '';
        orderCard.classList.remove('swiping', 'swipe-right', 'swipe-left');
        orderCard.style.removeProperty('--swipe-x');
        
        if (this.isSwiping && Math.abs(deltaX) > this.swipeThreshold) {
            const orderId = orderCard.dataset.orderId;
            
            if (deltaX > 0) {
                // Swipe right - advance stage
                this.handleSwipeRight(orderId);
            } else {
                // Swipe left - previous stage
                this.handleSwipeLeft(orderId);
            }
        } else if (!this.isSwiping && Math.abs(deltaX) < 10 && Math.abs(deltaY) < 10) {
            // Tap - open order details
            const orderId = orderCard.dataset.orderId;
            this.openOrderDetails(orderId);
        }
        
        this.isSwiping = false;
    }

    handleSwipeRight(orderId) {
        this.triggerHapticFeedback();
        this.advanceOrderStage(orderId);
    }

    handleSwipeLeft(orderId) {
        this.triggerHapticFeedback();
        this.revertOrderStage(orderId);
    }

    triggerHapticFeedback() {
        // Trigger haptic feedback on supported devices
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
        
        // Visual feedback
        document.body.classList.add('haptic-feedback');
        setTimeout(() => {
            document.body.classList.remove('haptic-feedback');
        }, 100);
    }

    async advanceOrderStage(orderId) {
        try {
            const order = this.orders.find(o => o.id === orderId);
            if (!order) return;
            
            const currentStageIndex = this.stages.findIndex(s => s.id === order.stage_id);
            const nextStage = this.stages[currentStageIndex + 1];
            
            if (!nextStage) {
                this.showToast('Order is already in the final stage', 'warning');
                return;
            }
            
            // Optimistic update
            order.stage_id = nextStage.id;
            this.renderPipeline();
            
            // Send to server
            const response = await fetch('/Api/v1/manufacturing/OrderPipelineAPI.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    action: 'updateStage',
                    orderId: orderId,
                    newStageId: nextStage.id,
                    note: `Advanced to ${nextStage.name} via mobile swipe`
                })
            });
            
            if (!response.ok) {
                throw new Error('Failed to update order stage');
            }
            
            this.showToast(`Order moved to ${nextStage.name}`, 'success');
            this.sendPushNotification(orderId, 'stage_change', {
                fromStage: this.stages[currentStageIndex].name,
                toStage: nextStage.name
            });
            
        } catch (error) {
            console.error('Error advancing order stage:', error);
            this.showToast('Failed to update order. Please try again.', 'error');
            // Revert optimistic update
            this.loadPipelineData();
        }
    }

    async revertOrderStage(orderId) {
        try {
            const order = this.orders.find(o => o.id === orderId);
            if (!order) return;
            
            const currentStageIndex = this.stages.findIndex(s => s.id === order.stage_id);
            const previousStage = this.stages[currentStageIndex - 1];
            
            if (!previousStage) {
                this.showToast('Order is already in the first stage', 'warning');
                return;
            }
            
            // Check if revert is allowed
            if (!this.canRevertStage(order, previousStage)) {
                this.showToast('Cannot revert this order to previous stage', 'warning');
                return;
            }
            
            // Optimistic update
            order.stage_id = previousStage.id;
            this.renderPipeline();
            
            // Send to server
            const response = await fetch('/Api/v1/manufacturing/OrderPipelineAPI.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.getCSRFToken()
                },
                body: JSON.stringify({
                    action: 'updateStage',
                    orderId: orderId,
                    newStageId: previousStage.id,
                    note: `Reverted to ${previousStage.name} via mobile swipe`
                })
            });
            
            if (!response.ok) {
                throw new Error('Failed to revert order stage');
            }
            
            this.showToast(`Order moved back to ${previousStage.name}`, 'success');
            
        } catch (error) {
            console.error('Error reverting order stage:', error);
            this.showToast('Failed to update order. Please try again.', 'error');
            // Revert optimistic update
            this.loadPipelineData();
        }
    }

    canRevertStage(order, targetStage) {
        // Business logic to determine if an order can be reverted
        // This would be customized based on business rules
        
        const restrictedReverts = ['shipped', 'delivered', 'cancelled'];
        return !restrictedReverts.includes(targetStage.key);
    }

    async loadPipelineData() {
        try {
            this.showLoading(true);
            
            const response = await fetch('/Api/v1/manufacturing/OrderPipelineAPI.php?action=getMobilePipeline', {
                headers: {
                    'X-CSRF-Token': this.getCSRFToken()
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load pipeline data');
            }
            
            const data = await response.json();
            this.orders = data.orders || [];
            this.stages = data.stages || [];
            
            this.updateQuickStats();
            this.renderPipeline();
            
        } catch (error) {
            console.error('Error loading pipeline data:', error);
            this.showToast('Failed to load pipeline data', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    renderPipeline() {
        const container = document.getElementById('pipelineStages');
        if (!container) return;
        
        const filteredOrders = this.getFilteredOrders();
        const stagesWithOrders = this.groupOrdersByStage(filteredOrders);
        
        let html = '';
        
        stagesWithOrders.forEach(stage => {
            html += this.renderStage(stage);
        });
        
        container.innerHTML = html;
        
        // Add intersection observer for lazy loading
        this.setupLazyLoading();
    }

    renderStage(stage) {
        const isExpanded = localStorage.getItem(`stage_${stage.id}_expanded`) !== 'false';
        
        return `
            <div class="mobile-stage" data-stage-id="${stage.id}">
                <div class="stage-header" onclick="toggleStage('${stage.id}')">
                    <div class="stage-info">
                        <h3 class="stage-title">${stage.name}</h3>
                        <div class="stage-meta">
                            <span class="order-count">${stage.orders.length} orders</span>
                            <span class="stage-value">$${this.formatCurrency(stage.totalValue)}</span>
                        </div>
                    </div>
                    <div class="stage-toggle">
                        <i class="fa fa-chevron-${isExpanded ? 'up' : 'down'}"></i>
                    </div>
                </div>
                
                <div class="stage-content ${isExpanded ? 'expanded' : 'collapsed'}">
                    ${stage.orders.map(order => this.renderOrderCard(order)).join('')}
                    ${stage.orders.length === 0 ? '<div class="empty-stage">No orders in this stage</div>' : ''}
                </div>
            </div>
        `;
    }

    renderOrderCard(order) {
        const priorityClass = this.getPriorityClass(order.priority);
        const isOverdue = this.isOrderOverdue(order);
        
        return `
            <div class="order-card ${priorityClass} ${isOverdue ? 'overdue' : ''}" 
                 data-order-id="${order.id}" 
                 data-view="${this.currentView}">
                
                <div class="swipe-hint left">
                    <i class="fa fa-arrow-left"></i>
                    <span>Previous</span>
                </div>
                
                <div class="card-content">
                    <div class="order-header">
                        <div class="order-number">#${order.order_number}</div>
                        <div class="order-priority ${priorityClass}">
                            ${this.getPriorityIcon(order.priority)}
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="client-name">${order.account_name}</div>
                        <div class="order-value">$${this.formatCurrency(order.total_value)}</div>
                        <div class="order-date">${this.formatDate(order.date_created)}</div>
                        ${isOverdue ? '<div class="overdue-badge">OVERDUE</div>' : ''}
                    </div>
                    
                    ${this.currentView === 'detailed' ? this.renderOrderDetails(order) : ''}
                </div>
                
                <div class="swipe-hint right">
                    <i class="fa fa-arrow-right"></i>
                    <span>Next</span>
                </div>
                
                <div class="card-actions">
                    <button class="action-btn" onclick="callClient('${order.id}')" 
                            title="Call Client">
                        <i class="fa fa-phone"></i>
                    </button>
                    <button class="action-btn" onclick="emailClient('${order.id}')" 
                            title="Email Client">
                        <i class="fa fa-envelope"></i>
                    </button>
                    <button class="action-btn" onclick="addNote('${order.id}')" 
                            title="Add Note">
                        <i class="fa fa-comment"></i>
                    </button>
                </div>
            </div>
        `;
    }

    renderOrderDetails(order) {
        return `
            <div class="order-extended-details">
                <div class="detail-row">
                    <span class="label">Sales Rep:</span>
                    <span class="value">${order.assigned_user_name || 'Unassigned'}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Products:</span>
                    <span class="value">${order.product_count} items</span>
                </div>
                <div class="detail-row">
                    <span class="label">Expected Close:</span>
                    <span class="value">${this.formatDate(order.expected_close_date)}</span>
                </div>
                ${order.last_note ? `
                    <div class="detail-row">
                        <span class="label">Last Note:</span>
                        <span class="value note-preview">${order.last_note}</span>
                    </div>
                ` : ''}
            </div>
        `;
    }

    groupOrdersByStage(orders) {
        const stagesWithOrders = this.stages.map(stage => ({
            ...stage,
            orders: orders.filter(order => order.stage_id === stage.id),
            totalValue: 0
        }));
        
        // Calculate total values
        stagesWithOrders.forEach(stage => {
            stage.totalValue = stage.orders.reduce((sum, order) => 
                sum + parseFloat(order.total_value || 0), 0);
        });
        
        return stagesWithOrders;
    }

    getFilteredOrders() {
        let filtered = [...this.orders];
        
        // Apply search filter
        if (this.filters.searchText) {
            const searchLower = this.filters.searchText.toLowerCase();
            filtered = filtered.filter(order => 
                order.order_number.toLowerCase().includes(searchLower) ||
                order.account_name.toLowerCase().includes(searchLower) ||
                (order.assigned_user_name && order.assigned_user_name.toLowerCase().includes(searchLower))
            );
        }
        
        // Apply priority filter
        if (this.filters.priority.length > 0) {
            filtered = filtered.filter(order => 
                this.filters.priority.includes(order.priority));
        }
        
        // Apply assigned user filter
        if (this.filters.assignedUser) {
            if (this.filters.assignedUser === 'me') {
                filtered = filtered.filter(order => order.assigned_user_id === this.getCurrentUserId());
            } else if (this.filters.assignedUser === 'unassigned') {
                filtered = filtered.filter(order => !order.assigned_user_id);
            }
        }
        
        // Apply date range filter
        if (this.filters.dateRange !== 'all') {
            const now = new Date();
            const filterDate = new Date();
            
            switch (this.filters.dateRange) {
                case 'today':
                    filterDate.setHours(0, 0, 0, 0);
                    break;
                case 'week':
                    filterDate.setDate(now.getDate() - 7);
                    break;
                case 'month':
                    filterDate.setMonth(now.getMonth() - 1);
                    break;
            }
            
            filtered = filtered.filter(order => 
                new Date(order.date_created) >= filterDate);
        }
        
        return filtered;
    }

    updateQuickStats() {
        const totalOrders = this.orders.length;
        const totalValue = this.orders.reduce((sum, order) => 
            sum + parseFloat(order.total_value || 0), 0);
        const urgentCount = this.orders.filter(order => 
            order.priority === 'urgent').length;
        
        document.getElementById('totalOrders').textContent = totalOrders;
        document.getElementById('totalValue').textContent = '$' + this.formatCurrency(totalValue);
        document.getElementById('urgentCount').textContent = urgentCount;
    }

    handleSearch(event) {
        this.filters.searchText = event.target.value;
        this.renderPipeline();
    }

    updateFilters() {
        // Update priority filters
        this.filters.priority = Array.from(document.querySelectorAll('input[name="priority"]:checked'))
            .map(input => input.value);
        
        // Update other filters
        this.filters.assignedUser = document.querySelector('select[name="assignedUser"]').value;
        this.filters.dateRange = document.querySelector('select[name="dateRange"]').value;
        
        this.updateFilterCount();
        this.renderPipeline();
    }

    updateFilterCount() {
        const activeFilters = 
            this.filters.priority.length +
            (this.filters.assignedUser ? 1 : 0) +
            (this.filters.dateRange !== 'all' ? 1 : 0) +
            (this.filters.searchText ? 1 : 0);
        
        const filterCountEl = document.getElementById('filterCount');
        if (activeFilters > 0) {
            filterCountEl.textContent = activeFilters;
            filterCountEl.style.display = 'flex';
        } else {
            filterCountEl.style.display = 'none';
        }
    }

    async refreshPipeline() {
        if (this.isPullRefreshing) return;
        
        this.isPullRefreshing = true;
        const indicator = document.getElementById('pullRefreshIndicator');
        
        try {
            indicator.classList.add('active');
            await this.loadPipelineData();
            this.showToast('Pipeline refreshed', 'success');
        } catch (error) {
            this.showToast('Failed to refresh pipeline', 'error');
        } finally {
            setTimeout(() => {
                indicator.style.opacity = '0';
                indicator.style.transform = 'translateX(-50%) translateY(-60px)';
                indicator.classList.remove('active');
                this.isPullRefreshing = false;
            }, 500);
        }
    }

    showLoading(show) {
        const loadingEl = document.getElementById('pipelineLoading');
        const stagesEl = document.getElementById('pipelineStages');
        
        if (show) {
            loadingEl.style.display = 'flex';
            stagesEl.style.display = 'none';
        } else {
            loadingEl.style.display = 'none';
            stagesEl.style.display = 'block';
        }
    }

    showToast(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fa fa-${this.getToastIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.add('show'), 100);
        
        // Remove after delay
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }

    getToastIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }

    // Utility functions
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    getPriorityClass(priority) {
        const classes = {
            urgent: 'priority-urgent',
            high: 'priority-high',
            normal: 'priority-normal',
            low: 'priority-low'
        };
        return classes[priority] || 'priority-normal';
    }

    getPriorityIcon(priority) {
        const icons = {
            urgent: '<i class="fa fa-exclamation-triangle"></i>',
            high: '<i class="fa fa-chevron-up"></i>',
            normal: '<i class="fa fa-minus"></i>',
            low: '<i class="fa fa-chevron-down"></i>'
        };
        return icons[priority] || icons.normal;
    }

    isOrderOverdue(order) {
        if (!order.expected_close_date) return false;
        return new Date(order.expected_close_date) < new Date();
    }

    getCSRFToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    getCurrentUserId() {
        return window.currentUserId || '';
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Offline handling
    handleOnline() {
        this.showToast('Connection restored', 'success');
        this.loadPipelineData();
    }

    handleOffline() {
        this.showToast('Working offline', 'warning');
    }

    setupLazyLoading() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                }
            });
        });

        document.querySelectorAll('img[data-src]').forEach(img => {
            observer.observe(img);
        });
    }
}

// Global functions for template interaction
function toggleMobileFilters() {
    const panel = document.getElementById('mobileFiltersPanel');
    panel.classList.toggle('active');
}

function clearMobileFilters() {
    document.querySelectorAll('input[name="priority"]').forEach(input => input.checked = false);
    document.querySelector('select[name="assignedUser"]').value = '';
    document.querySelector('select[name="dateRange"]').value = 'all';
    mobilePipeline.updateFilters();
    toggleMobileFilters();
}

function applyMobileFilters() {
    mobilePipeline.updateFilters();
    toggleMobileFilters();
}

function switchView(viewMode) {
    document.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelector(`[data-view="${viewMode}"]`).classList.add('active');
    
    mobilePipeline.currentView = viewMode;
    mobilePipeline.renderPipeline();
}

function toggleStage(stageId) {
    const stage = document.querySelector(`[data-stage-id="${stageId}"]`);
    const content = stage.querySelector('.stage-content');
    const icon = stage.querySelector('.stage-toggle i');
    
    const isExpanded = content.classList.contains('expanded');
    
    if (isExpanded) {
        content.classList.remove('expanded');
        content.classList.add('collapsed');
        icon.className = 'fa fa-chevron-down';
        localStorage.setItem(`stage_${stageId}_expanded`, 'false');
    } else {
        content.classList.remove('collapsed');
        content.classList.add('expanded');
        icon.className = 'fa fa-chevron-up';
        localStorage.setItem(`stage_${stageId}_expanded`, 'true');
    }
}

function openQuickActions() {
    const menu = document.getElementById('quickActionsMenu');
    menu.classList.toggle('active');
}

function createNewOrder() {
    // Navigate to new order creation
    window.location.href = '/index.php?module=Orders&action=EditView';
}

function refreshPipeline() {
    mobilePipeline.refreshPipeline();
    openQuickActions(); // Close menu
}

function openBulkActions() {
    // Implement bulk actions functionality
    mobilePipeline.showToast('Bulk actions coming soon', 'info');
    openQuickActions();
}

function openSettings() {
    // Navigate to settings
    window.location.href = '/index.php?module=Administration&action=index';
}

function openOrderDetails(orderId) {
    mobilePipeline.openOrderDetails(orderId);
}

function closeOrderModal() {
    const modal = document.getElementById('orderModal');
    modal.classList.remove('active');
}

function editOrder() {
    const orderId = document.getElementById('orderModal').dataset.orderId;
    window.location.href = `/index.php?module=Orders&action=EditView&record=${orderId}`;
}

// Navigation functions
function showPipelineView() {
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    event.target.closest('.nav-item').classList.add('active');
    // Current view - no action needed
}

function showCalendarView() {
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    event.target.closest('.nav-item').classList.add('active');
    window.location.href = '/index.php?module=Calendar&action=index';
}

function showReportsView() {
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    event.target.closest('.nav-item').classList.add('active');
    window.location.href = '/index.php?module=Reports&action=index';
}

function showProfileView() {
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    event.target.closest('.nav-item').classList.add('active');
    window.location.href = '/index.php?module=Users&action=DetailView&record=' + mobilePipeline.getCurrentUserId();
}

// Initialize mobile pipeline on page load
let mobilePipeline;
document.addEventListener('DOMContentLoaded', () => {
    mobilePipeline = new MobilePipeline();
});
