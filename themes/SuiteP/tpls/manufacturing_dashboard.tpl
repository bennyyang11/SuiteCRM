{*
 * Manufacturing Dashboard Home Page
 * Modern dashboard matching manufacturing demo design
 *}

<link rel="stylesheet" href="themes/SuiteP/css/manufacturing-dashboard.css">

<div class="dashboard-content">
    <div class="container-fluid" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
        
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h2>Welcome to Manufacturing Distribution CRM</h2>
            <p>Streamlined sales, modernized workflows, and intelligent insights for manufacturing distributors</p>
        </div>

        <!-- Quick Stats -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon">📊</div>
                <div class="dashboard-card-title">Business Performance</div>
            </div>
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="stat-number">$145K</div>
                    <div class="stat-label">Pipeline Value</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">12</div>
                    <div class="stat-label">Active Orders</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-number">85%</div>
                    <div class="stat-label">Conversion Rate</div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-number">247</div>
                    <div class="stat-label">Products</div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
            
            <!-- Manufacturing Features -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">🏭</div>
                    <div class="dashboard-card-title">Manufacturing Features</div>
                </div>
                
                <div class="modern-alert success">
                    <h4 style="margin-bottom: 10px;">✅ Features 1 & 2 Complete</h4>
                    <p>Product Catalog and Order Pipeline are fully operational with mobile optimization.</p>
                </div>
                
                <div class="quick-actions">
                    <a href="manufacturing_demo.php" class="quick-action">
                        <span class="quick-action-icon">📱</span>
                        <span class="quick-action-text">Mobile Demo</span>
                    </a>
                    <a href="index.php?module=Accounts&action=index" class="quick-action">
                        <span class="quick-action-icon">👥</span>
                        <span class="quick-action-text">Client Accounts</span>
                    </a>
                    <a href="index.php?module=Opportunities&action=index" class="quick-action">
                        <span class="quick-action-icon">💰</span>
                        <span class="quick-action-text">Sales Pipeline</span>
                    </a>
                    <a href="clean_demo.php" class="quick-action">
                        <span class="quick-action-icon">🧹</span>
                        <span class="quick-action-text">Clean Demo</span>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">🕒</div>
                    <div class="dashboard-card-title">Recent Activity</div>
                </div>
                
                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="activity-icon">🛒</div>
                        <div class="activity-content">
                            <div class="activity-title">New Order Created</div>
                            <div class="activity-meta">ORD-2024-008 • Manufacturing Corp</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon">✅</div>
                        <div class="activity-content">
                            <div class="activity-title">Quote Approved</div>
                            <div class="activity-meta">QUO-2024-015 • $22,500</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon">📱</div>
                        <div class="activity-content">
                            <div class="activity-title">Mobile Catalog Updated</div>
                            <div class="activity-meta">247 products synced</div>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="activity-icon">🚚</div>
                        <div class="activity-content">
                            <div class="activity-title">Order Shipped</div>
                            <div class="activity-meta">ORD-2024-006 • Heavy Industries</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Implementation Progress -->
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon">🎯</div>
                <div class="dashboard-card-title">Implementation Progress</div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: #27ae60; margin-bottom: 15px;">✅ Completed Features</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #ecf0f1;">
                            <strong>Feature 1:</strong> Mobile Product Catalog with Client-Specific Pricing
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #ecf0f1;">
                            <strong>Feature 2:</strong> Order Tracking Dashboard (Quote → Invoice Pipeline)
                        </li>
                        <li style="padding: 8px 0;">
                            <strong>Performance:</strong> Sub-second response times achieved
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #3498db; margin-bottom: 15px;">🔄 In Progress</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #ecf0f1;">
                            <strong>Feature 3:</strong> Real-Time Inventory Integration
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #ecf0f1;">
                            <strong>Feature 4:</strong> Quote Builder with PDF Export
                        </li>
                        <li style="padding: 8px 0;">
                            <strong>UI Integration:</strong> Modern dashboard styling
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: #f39c12; margin-bottom: 15px;">📋 Upcoming</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 8px 0; border-bottom: 1px solid #ecf0f1;">
                            <strong>Feature 5:</strong> Advanced Search & Filtering
                        </li>
                        <li style="padding: 8px 0; border-bottom: 1px solid #ecf0f1;">
                            <strong>Feature 6:</strong> User Role Management
                        </li>
                        <li style="padding: 8px 0;">
                            <strong>Testing:</strong> Comprehensive validation suite
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="modern-alert info">
            <h4 style="margin-bottom: 10px;">🚀 Ready for Feature 3 Implementation</h4>
            <p>
                The development environment is optimized and Features 1 & 2 are fully functional. 
                <a href="manufacturing_demo.php" style="color: #004085; font-weight: bold;">View the live demo</a> 
                or <a href="test_manufacturing_apis.php" style="color: #004085; font-weight: bold;">test the APIs</a> 
                to see the completed work.
            </p>
        </div>

    </div>
</div>

<script>
// Add smooth animations
document.addEventListener('DOMContentLoaded', function() {
    // Add manufacturing mode class
    document.body.classList.add('manufacturing-mode');
    
    // Animate stats cards on load
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
    
    // Add hover effects to activity items
    const activityItems = document.querySelectorAll('.activity-item');
    activityItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(5px)';
        });
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
});
</script>
