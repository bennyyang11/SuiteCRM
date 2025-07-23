/**
 * Order Pipeline Dashboard - Kanban Style
 * Mobile-first dashboard for manufacturing sales pipeline
 */

class OrderPipelineDashboard {
    constructor() {
        this.orders = [];
        this.kanbanData = {};
        this.summary = {};
        this.selectedOrder = null;
        this.filters = {
            rep_id: '',
            priority: '',
            days: 30
        };
        this.isLoading = false;
        this.draggedOrder = null;
        
        this.stages = {
            'quote_created': { label: 'Quote Created', color: '#e3f2fd', icon: 'fas fa-file-alt' },
            'quote_sent': { label: 'Quote Sent', color: '#f3e5f5', icon: 'fas fa-paper-plane' },
            'quote_approved': { label: 'Quote Approved', color: '#e8f5e8', icon: 'fas fa-check-circle' },
            'order_placed': { label: 'Order Placed', color: '#fff3e0', icon: 'fas fa-shopping-cart' },
            'order_shipped': { label: 'Order Shipped', color: '#e0f2f1', icon: 'fas fa-truck' },
            'invoice_sent': { label: 'Invoice Sent', color: '#fce4ec', icon: 'fas fa-file-invoice' },
            'payment_received': { label: 'Payment Received', color: '#e8f5e8', icon: 'fas fa-dollar-sign' }
        };
        
        this.init();
    }
    
    init() {
        this.createHTML();
        this.bindEvents();
        this.loadPipelineData();
        
        // Auto-refresh every 2 minutes
        setInterval(() => this.loadPipelineData(), 120000);
    }
    
    createHTML() {
        const container = document.getElementById('pipeline-dashboard-container') || document.body;
        
        container.innerHTML = `
            <div class="pipeline-dashboard">
                <!-- Dashboard Header -->
                <header class="dashboard-header">
                    <div class="header-content">
                        <h1><i class="fas fa-chart-line"></i> Order Pipeline Dashboard</h1>
                        <div class="header-stats" id="headerStats">
                            <div class="stat-item">
                                <span class="stat-value" id="totalOrders">0</span>
                                <span class="stat-label">Active Orders</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="totalValue">$0</span>
                                <span class="stat-label">Pipeline Value</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value" id="urgentOrders">0</span>
                                <span class="stat-label">Urgent</span>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Filters and Controls -->
                <div class="dashboard-controls">
                    <div class="controls-row">
                        <select id="repFilter" class="form-select">
                            <option value="">All Sales Reps</option>
                        </select>
                        
                        <select id="priorityFilter" class="form-select">
                            <option value="">All Priorities</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                        
                        <select id="daysFilter" class="form-select">
                            <option value="7">Last 7 Days</option>
                            <option value="30" selected>Last 30 Days</option>
                            <option value="90">Last 90 Days</option>
                            <option value="365">Last Year</option>
                        </select>
                        
                        <button id="refreshBtn" class="btn btn-primary">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Kanban Board -->
                <div class="kanban-container">
                    <div id="kanbanBoard" class="kanban-board">
                        <!-- Kanban columns will be generated here -->
                    </div>
                </div>

                <!-- Loading Overlay -->
                <div id="loadingOverlay" class="loading-overlay" style="display: none;">
                    <div class="loading-content">
                        <i class="fas fa-spinner fa-spin"></i>
                        <div>Loading pipeline data...</div>
                    </div>
                </div>

                <!-- Order Details Modal -->
                <div id="orderModal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 id="modalTitle">Order Details</h3>
                            <button class="modal-close" id="closeModal">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="modal-body" id="modalBody">
                            <!-- Order details will be loaded here -->
                        </div>
                        <div class="modal-footer">
                            <button id="updateStageBtn" class="btn btn-primary">
                                Update Stage
                            </button>
                            <button id="viewHistoryBtn" class="btn btn-outline-primary">
                                View History
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.loadCSS();
    }
    
    loadCSS() {
        if (document.getElementById('pipeline-dashboard-css')) return;
        
        const css = `
            <style id="pipeline-dashboard-css">
                .pipeline-dashboard {
                    max-width: 100%;
                    margin: 0 auto;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f8f9fa;
                    min-height: 100vh;
                }
                
                .dashboard-header {
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    padding: 1.5rem;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .header-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 1rem;
                }
                
                .header-content h1 {
                    margin: 0;
                    font-size: 1.75rem;
                    font-weight: 600;
                }
                
                .header-stats {
                    display: flex;
                    gap: 2rem;
                }
                
                .stat-item {
                    text-align: center;
                }
                
                .stat-value {
                    display: block;
                    font-size: 1.5rem;
                    font-weight: bold;
                    color: #fff;
                }
                
                .stat-label {
                    display: block;
                    font-size: 0.875rem;
                    opacity: 0.9;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .dashboard-controls {
                    background: white;
                    padding: 1rem;
                    border-bottom: 1px solid #dee2e6;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                
                .controls-row {
                    display: flex;
                    gap: 1rem;
                    align-items: center;
                    flex-wrap: wrap;
                }
                
                .form-select {
                    padding: 0.5rem;
                    border: 1px solid #ced4da;
                    border-radius: 0.375rem;
                    background: white;
                    min-width: 150px;
                }
                
                .btn {
                    padding: 0.5rem 1rem;
                    border: none;
                    border-radius: 0.375rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    display: inline-flex;
                    align-items: center;
                    gap: 0.5rem;
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
                
                .kanban-container {
                    padding: 1rem;
                    overflow-x: auto;
                }
                
                .kanban-board {
                    display: flex;
                    gap: 1rem;
                    min-width: max-content;
                    padding-bottom: 1rem;
                }
                
                .kanban-column {
                    background: white;
                    border-radius: 0.5rem;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    min-width: 320px;
                    max-width: 320px;
                    max-height: calc(100vh - 300px);
                    display: flex;
                    flex-direction: column;
                }
                
                .column-header {
                    padding: 1rem;
                    border-radius: 0.5rem 0.5rem 0 0;
                    color: white;
                    font-weight: 600;
                    font-size: 1rem;
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                
                .column-count {
                    background: rgba(255,255,255,0.2);
                    padding: 0.25rem 0.5rem;
                    border-radius: 1rem;
                    font-size: 0.75rem;
                    margin-left: auto;
                }
                
                .column-body {
                    padding: 1rem;
                    flex: 1;
                    overflow-y: auto;
                    min-height: 200px;
                }
                
                .order-card {
                    background: white;
                    border: 1px solid #e9ecef;
                    border-radius: 0.375rem;
                    padding: 1rem;
                    margin-bottom: 1rem;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    draggable: true;
                }
                
                .order-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    border-color: #667eea;
                }
                
                .order-card.dragging {
                    opacity: 0.5;
                    transform: rotate(5deg);
                }
                
                .order-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    margin-bottom: 0.75rem;
                }
                
                .order-number {
                    font-weight: 600;
                    color: #2d3748;
                    font-size: 1rem;
                }
                
                .priority-badge {
                    padding: 0.25rem 0.5rem;
                    border-radius: 0.25rem;
                    font-size: 0.75rem;
                    font-weight: 600;
                    text-transform: uppercase;
                }
                
                .priority-urgent {
                    background: #fed7d7;
                    color: #c53030;
                }
                
                .priority-high {
                    background: #fed7a8;
                    color: #dd6b20;
                }
                
                .priority-medium {
                    background: #bee3f8;
                    color: #3182ce;
                }
                
                .priority-low {
                    background: #c6f6d5;
                    color: #38a169;
                }
                
                .order-client {
                    color: #6c757d;
                    font-size: 0.875rem;
                    margin-bottom: 0.5rem;
                }
                
                .order-amount {
                    font-size: 1.25rem;
                    font-weight: 600;
                    color: #667eea;
                    margin-bottom: 0.5rem;
                }
                
                .order-meta {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 0.5rem;
                    font-size: 0.75rem;
                    color: #6c757d;
                }
                
                .order-timeline {
                    margin-top: 0.75rem;
                    padding-top: 0.75rem;
                    border-top: 1px solid #f1f3f4;
                }
                
                .timeline-item {
                    display: flex;
                    justify-content: space-between;
                    font-size: 0.75rem;
                }
                
                .timeline-status {
                    padding: 0.125rem 0.375rem;
                    border-radius: 0.25rem;
                    font-weight: 500;
                }
                
                .timeline-on_track {
                    background: #c6f6d5;
                    color: #38a169;
                }
                
                .timeline-due_soon {
                    background: #fed7a8;
                    color: #dd6b20;
                }
                
                .timeline-overdue {
                    background: #fed7d7;
                    color: #c53030;
                }
                
                .loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255,255,255,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                }
                
                .loading-content {
                    text-align: center;
                    color: #667eea;
                    font-size: 1.1rem;
                }
                
                .modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0,0,0,0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    padding: 1rem;
                }
                
                .modal-content {
                    background: white;
                    border-radius: 0.5rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
                    max-width: 600px;
                    width: 100%;
                    max-height: 90vh;
                    overflow-y: auto;
                }
                
                .modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 1.5rem;
                    border-bottom: 1px solid #dee2e6;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    border-radius: 0.5rem 0.5rem 0 0;
                }
                
                .modal-header h3 {
                    margin: 0;
                    font-size: 1.25rem;
                }
                
                .modal-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.25rem;
                    cursor: pointer;
                    padding: 0.25rem;
                }
                
                .modal-body {
                    padding: 1.5rem;
                }
                
                .modal-footer {
                    padding: 1rem 1.5rem;
                    border-top: 1px solid #dee2e6;
                    display: flex;
                    gap: 1rem;
                    justify-content: flex-end;
                }
                
                .drop-zone {
                    border: 2px dashed #667eea;
                    background: rgba(102, 126, 234, 0.1);
                    border-radius: 0.375rem;
                    padding: 1rem;
                    text-align: center;
                    color: #667eea;
                    margin: 0.5rem 0;
                }
                
                /* Mobile Optimizations */
                @media (max-width: 768px) {
                    .header-content {
                        flex-direction: column;
                        text-align: center;
                    }
                    
                    .header-stats {
                        justify-content: center;
                        gap: 1rem;
                    }
                    
                    .controls-row {
                        flex-direction: column;
                        align-items: stretch;
                    }
                    
                    .form-select {
                        min-width: 100%;
                    }
                    
                    .kanban-board {
                        flex-direction: column;
                        gap: 1rem;
                    }
                    
                    .kanban-column {
                        min-width: 100%;
                        max-width: 100%;
                        max-height: 400px;
                    }
                    
                    .modal-content {
                        margin: 0.5rem;
                        width: calc(100% - 1rem);
                    }
                }
                
                @media (max-width: 480px) {
                    .dashboard-header {
                        padding: 1rem;
                    }
                    
                    .header-content h1 {
                        font-size: 1.5rem;
                    }
                    
                    .stat-value {
                        font-size: 1.25rem;
                    }
                    
                    .order-meta {
                        grid-template-columns: 1fr;
                    }
                }
            </style>
        `;
        
        document.head.insertAdjacentHTML('beforeend', css);
    }
    
    bindEvents() {
        // Filter changes
        document.getElementById('repFilter').addEventListener('change', () => this.applyFilters());
        document.getElementById('priorityFilter').addEventListener('change', () => this.applyFilters());
        document.getElementById('daysFilter').addEventListener('change', () => this.applyFilters());
        
        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', () => this.loadPipelineData());
        
        // Modal controls
        document.getElementById('closeModal').addEventListener('click', () => this.closeModal());
        document.getElementById('orderModal').addEventListener('click', (e) => {
            if (e.target.id === 'orderModal') this.closeModal();
        });
        
        // Escape key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.closeModal();
        });
    }
    
    async loadPipelineData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading(true);
        
        try {
            const params = new URLSearchParams(this.filters);
            const response = await fetch(`/api/v1/pipeline?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.orders = data.data.orders;
                this.kanbanData = data.data.kanban;
                this.summary = data.data.summary;
                
                this.renderKanbanBoard();
                this.updateHeaderStats();
            } else {
                this.showError('Failed to load pipeline data');
            }
        } catch (error) {
            console.error('Error loading pipeline data:', error);
            this.showError('Network error. Check your connection.');
        } finally {
            this.isLoading = false;
            this.showLoading(false);
        }
    }
    
    renderKanbanBoard() {
        const board = document.getElementById('kanbanBoard');
        
        board.innerHTML = Object.entries(this.stages).map(([stageKey, stageData]) => {
            const orders = this.kanbanData[stageKey]?.orders || [];
            
            return `
                <div class="kanban-column" data-stage="${stageKey}">
                    <div class="column-header" style="background: linear-gradient(135deg, ${stageData.color}, ${this.darkenColor(stageData.color, 20)});">
                        <i class="${stageData.icon}"></i>
                        ${stageData.label}
                        <span class="column-count">${orders.length}</span>
                    </div>
                    <div class="column-body" ondrop="app.handleDrop(event)" ondragover="app.handleDragOver(event)">
                        ${orders.map(order => this.renderOrderCard(order)).join('')}
                        ${orders.length === 0 ? '<div class="drop-zone">Drop orders here</div>' : ''}
                    </div>
                </div>
            `;
        }).join('');
        
        // Bind drag and drop events
        this.bindDragEvents();
    }
    
    renderOrderCard(order) {
        const timelineClass = `timeline-${order.stage.timeline_status}`;
        const priorityClass = `priority-${order.details.priority}`;
        
        return `
            <div class="order-card" 
                 data-order-id="${order.id}"
                 draggable="true"
                 onclick="app.openOrderDetails('${order.id}')">
                <div class="order-header">
                    <div class="order-number">${order.pipeline_number}</div>
                    <div class="priority-badge ${priorityClass}">${order.details.priority}</div>
                </div>
                
                <div class="order-client">
                    <i class="fas fa-building"></i> ${order.account.name}
                </div>
                
                <div class="order-amount">
                    $${order.financial.total_amount.toLocaleString('en-US', {minimumFractionDigits: 2})}
                </div>
                
                <div class="order-meta">
                    <div><i class="fas fa-user"></i> ${order.assigned_rep.name}</div>
                    <div><i class="fas fa-percentage"></i> ${order.financial.probability_percent}%</div>
                    <div><i class="fas fa-calendar"></i> ${order.timeline.quote_date}</div>
                    <div><i class="fas fa-clock"></i> ${order.timeline.days_to_close} days</div>
                </div>
                
                <div class="order-timeline">
                    <div class="timeline-item">
                        <span>Expected Close:</span>
                        <span class="timeline-status ${timelineClass}">
                            ${order.timeline.expected_close_date}
                        </span>
                    </div>
                    ${order.details.client_po_number ? `
                        <div class="timeline-item">
                            <span>PO Number:</span>
                            <span>${order.details.client_po_number}</span>
                        </div>
                    ` : ''}
                </div>
                
                ${order.details.is_rush_order ? `
                    <div style="margin-top: 0.5rem;">
                        <span style="background: #fed7d7; color: #c53030; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; font-weight: 600;">
                            <i class="fas fa-exclamation-triangle"></i> RUSH ORDER
                        </span>
                    </div>
                ` : ''}
            </div>
        `;
    }
    
    bindDragEvents() {
        document.querySelectorAll('.order-card').forEach(card => {
            card.addEventListener('dragstart', (e) => {
                this.draggedOrder = e.target.dataset.orderId;
                e.target.classList.add('dragging');
            });
            
            card.addEventListener('dragend', (e) => {
                e.target.classList.remove('dragging');
            });
        });
    }
    
    handleDragOver(event) {
        event.preventDefault();
    }
    
    async handleDrop(event) {
        event.preventDefault();
        
        if (!this.draggedOrder) return;
        
        const column = event.target.closest('.kanban-column');
        if (!column) return;
        
        const newStage = column.dataset.stage;
        const orderId = this.draggedOrder;
        
        // Reset dragged order
        this.draggedOrder = null;
        
        // Update stage via API
        await this.updateOrderStage(orderId, newStage);
    }
    
    async updateOrderStage(orderId, newStage, notes = '') {
        try {
            const response = await fetch(`/api/v1/pipeline/${orderId}/stage`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    stage: newStage,
                    notes: notes
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(`Order moved to ${newStage.replace('_', ' ')}`, 'success');
                // Reload data to reflect changes
                await this.loadPipelineData();
            } else {
                this.showError('Failed to update order stage');
            }
        } catch (error) {
            console.error('Error updating stage:', error);
            this.showError('Failed to update order stage');
        }
    }
    
    openOrderDetails(orderId) {
        const order = this.orders.find(o => o.id === orderId);
        if (!order) return;
        
        this.selectedOrder = order;
        
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        modalTitle.textContent = `Order ${order.pipeline_number} Details`;
        
        modalBody.innerHTML = `
            <div class="order-details">
                <div class="detail-section">
                    <h4>Order Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Pipeline Number:</label>
                            <span>${order.pipeline_number}</span>
                        </div>
                        <div class="detail-item">
                            <label>Client:</label>
                            <span>${order.account.name}</span>
                        </div>
                        <div class="detail-item">
                            <label>Sales Rep:</label>
                            <span>${order.assigned_rep.name}</span>
                        </div>
                        <div class="detail-item">
                            <label>Current Stage:</label>
                            <span>${order.stage.current.replace('_', ' ')}</span>
                        </div>
                        <div class="detail-item">
                            <label>Priority:</label>
                            <span class="priority-badge priority-${order.details.priority}">${order.details.priority}</span>
                        </div>
                        <div class="detail-item">
                            <label>PO Number:</label>
                            <span>${order.details.client_po_number || 'N/A'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Financial Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Total Amount:</label>
                            <span>$${order.financial.total_amount.toLocaleString()}</span>
                        </div>
                        <div class="detail-item">
                            <label>Probability:</label>
                            <span>${order.financial.probability_percent}%</span>
                        </div>
                        <div class="detail-item">
                            <label>Expected Value:</label>
                            <span>$${(order.financial.total_amount * order.financial.probability_percent / 100).toLocaleString()}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Timeline</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Quote Date:</label>
                            <span>${order.timeline.quote_date}</span>
                        </div>
                        <div class="detail-item">
                            <label>Expected Close:</label>
                            <span>${order.timeline.expected_close_date}</span>
                        </div>
                        <div class="detail-item">
                            <label>Days to Close:</label>
                            <span>${order.timeline.days_to_close} days</span>
                        </div>
                        <div class="detail-item">
                            <label>Last Updated:</label>
                            <span>${new Date(order.stage.updated_date).toLocaleDateString()}</span>
                        </div>
                    </div>
                </div>
                
                ${order.details.special_instructions ? `
                    <div class="detail-section">
                        <h4>Special Instructions</h4>
                        <p>${order.details.special_instructions}</p>
                    </div>
                ` : ''}
                
                <div class="detail-section">
                    <h4>Update Stage</h4>
                    <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                        <select id="newStageSelect" class="form-select">
                            ${Object.entries(this.stages).map(([key, stage]) => `
                                <option value="${key}" ${key === order.stage.current ? 'selected' : ''}>
                                    ${stage.label}
                                </option>
                            `).join('')}
                        </select>
                        <button onclick="app.updateStageFromModal()" class="btn btn-primary">
                            Update Stage
                        </button>
                    </div>
                    <textarea id="stageNotes" placeholder="Optional notes..." 
                              style="width: 100%; margin-top: 0.5rem; padding: 0.5rem; border: 1px solid #ced4da; border-radius: 0.375rem;"></textarea>
                </div>
            </div>
            
            <style>
                .detail-section {
                    margin-bottom: 1.5rem;
                }
                
                .detail-section h4 {
                    margin: 0 0 1rem 0;
                    color: #2d3748;
                    font-size: 1.1rem;
                    border-bottom: 1px solid #e2e8f0;
                    padding-bottom: 0.5rem;
                }
                
                .detail-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 1rem;
                }
                
                .detail-item {
                    display: flex;
                    flex-direction: column;
                    gap: 0.25rem;
                }
                
                .detail-item label {
                    font-weight: 500;
                    color: #6c757d;
                    font-size: 0.875rem;
                }
                
                .detail-item span {
                    color: #2d3748;
                    font-size: 0.95rem;
                }
            </style>
        `;
        
        document.getElementById('orderModal').style.display = 'flex';
    }
    
    async updateStageFromModal() {
        const newStage = document.getElementById('newStageSelect').value;
        const notes = document.getElementById('stageNotes').value;
        
        if (this.selectedOrder && newStage !== this.selectedOrder.stage.current) {
            await this.updateOrderStage(this.selectedOrder.id, newStage, notes);
            this.closeModal();
        }
    }
    
    closeModal() {
        document.getElementById('orderModal').style.display = 'none';
        this.selectedOrder = null;
    }
    
    updateHeaderStats() {
        document.getElementById('totalOrders').textContent = this.summary.total_orders || 0;
        document.getElementById('totalValue').textContent = 
            '$' + (this.summary.total_value || 0).toLocaleString('en-US', {minimumFractionDigits: 0});
        document.getElementById('urgentOrders').textContent = 
            this.summary.priority_counts?.urgent || 0;
    }
    
    applyFilters() {
        this.filters.rep_id = document.getElementById('repFilter').value;
        this.filters.priority = document.getElementById('priorityFilter').value;
        this.filters.days = document.getElementById('daysFilter').value;
        
        this.loadPipelineData();
    }
    
    showLoading(show) {
        document.getElementById('loadingOverlay').style.display = show ? 'flex' : 'none';
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'success') {
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
    
    darkenColor(color, percent) {
        // Simple color darkening function
        const num = parseInt(color.replace("#",""), 16);
        const amt = Math.round(2.55 * percent);
        const R = (num >> 16) - amt;
        const G = (num >> 8 & 0x00FF) - amt;
        const B = (num & 0x0000FF) - amt;
        return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 + 
                      (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 + 
                      (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
    }
}

// Global app instance for event handling
let app;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('pipeline-dashboard-container')) {
        app = new OrderPipelineDashboard();
        window.pipelineDashboard = app;
    }
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OrderPipelineDashboard;
}
