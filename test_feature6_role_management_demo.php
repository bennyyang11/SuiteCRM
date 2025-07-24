<?php
/**
 * Feature 6: User Role Management & Permissions - Complete Demo
 * Demonstrates all role-based interfaces and security features
 */

define('sugarEntry', true);
require_once('include/entryPoint.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature 6: User Role Management & Permissions Demo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .header { background: linear-gradient(135deg, #6f42c1, #563d7c); color: white; padding: 20px; }
        .header-nav { display: flex; align-items: center; justify-content: space-between; max-width: 1200px; margin: 0 auto; }
        .header-center { text-align: center; flex: 1; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.2em; opacity: 0.9; }
        
        .back-link { color: white; text-decoration: none; padding: 10px 15px; border-radius: 6px; background: rgba(255,255,255,0.1); }
        .back-link:hover { background: rgba(255,255,255,0.2); text-decoration: none; }
        
        .header-actions { display: flex; gap: 10px; }
        .header-btn { padding: 8px 16px; background: rgba(255, 255, 255, 0.2); color: white; text-decoration: none; border-radius: 5px; }
        .header-btn:hover { background: rgba(255, 255, 255, 0.3); text-decoration: none; }
        
        .demo-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .feature-section { background: white; border-radius: 10px; margin: 20px 0; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .feature-header { display: flex; align-items: center; margin-bottom: 20px; }
        .feature-icon { font-size: 2em; margin-right: 15px; }
        .feature-title { font-size: 1.8em; color: #2c3e50; }
        
        .role-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .role-card { border-radius: 10px; padding: 20px; color: white; position: relative; overflow: hidden; }
        .role-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.1); z-index: 1; }
        .role-content { position: relative; z-index: 2; }
        .role-title { font-size: 1.4em; margin-bottom: 15px; }
        .role-features { list-style: none; padding: 0; }
        .role-features li { margin: 8px 0; padding-left: 20px; position: relative; }
        .role-features li::before { content: '✓'; position: absolute; left: 0; color: rgba(255,255,255,0.8); }
        
        .sales-rep { background: linear-gradient(135deg, #6f42c1, #563d7c); }
        .manager { background: linear-gradient(135deg, #17a2b8, #138496); }
        .client { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .admin { background: linear-gradient(135deg, #dc3545, #c82333); }
        
        .demo-interface { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .interface-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .interface-title { font-size: 1.2em; font-weight: 600; color: #495057; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8em; font-weight: 500; }
        .badge-secure { background: #d4edda; color: #155724; }
        .badge-active { background: #cce7ff; color: #004085; }
        
        .mock-screen { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 10px 0; }
        .mock-header { display: flex; justify-content: between; align-items: center; padding-bottom: 10px; border-bottom: 1px solid #e9ecef; margin-bottom: 15px; }
        .mock-user { font-weight: 600; color: #495057; }
        .mock-role { font-size: 0.9em; color: #6c757d; }
        
        .btn { padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-weight: 500; text-decoration: none; display: inline-block; text-align: center; margin: 5px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .security-features { background: linear-gradient(135deg, #e8f4fd, #d1ecf1); border: 1px solid #bee5eb; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .security-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .security-item { background: white; padding: 15px; border-radius: 6px; border-left: 4px solid #17a2b8; }
        .security-title { font-weight: 600; color: #0c5460; margin-bottom: 8px; }
        .security-desc { font-size: 0.9em; color: #495057; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: linear-gradient(135deg, #6f42c1, #563d7c); color: white; padding: 20px; border-radius: 8px; text-align: center; }
        .stat-number { font-size: 2.5em; font-weight: bold; }
        .stat-label { font-size: 0.9em; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="manufacturing_demo.php" class="back-link">← Back to Main Demo</a>
            <div class="header-center">
                <h1>👥 Feature 6: User Role Management & Permissions</h1>
                <p>Complete RBAC System with JWT Security & Territory-Based Access Control</p>
            </div>
            <div class="header-actions">
                <a href="test_feature6_security_comprehensive.php" class="header-btn">Security Test</a>
                <a href="manufacturing_demo.php" class="header-btn">All Features</a>
            </div>
        </div>
    </div>

    <div class="demo-container">
        <!-- Overview Statistics -->
        <div class="feature-section">
            <div class="feature-header">
                <div class="feature-icon">📊</div>
                <div class="feature-title">Role Management System Overview</div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number">4</div>
                    <div class="stat-label">User Roles</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <div class="stat-number">JWT</div>
                    <div class="stat-label">Token Security</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                    <div class="stat-number">5</div>
                    <div class="stat-label">Territories</div>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                    <div class="stat-number">✅</div>
                    <div class="stat-label">Enterprise Ready</div>
                </div>
            </div>
        </div>

        <!-- Role-Based Access Control -->
        <div class="feature-section">
            <div class="feature-header">
                <div class="feature-icon">🔐</div>
                <div class="feature-title">Role-Based Access Control (RBAC)</div>
            </div>
            
            <div class="role-grid">
                <div class="role-card sales-rep">
                    <div class="role-content">
                        <div class="role-title">👤 Sales Representative</div>
                        <ul class="role-features">
                            <li>Mobile product catalog access</li>
                            <li>Territory-specific client data</li>
                            <li>Personal performance metrics</li>
                            <li>Quote creation & management</li>
                            <li>Order pipeline tracking</li>
                            <li>Inventory status viewing</li>
                        </ul>
                        <a href="modules/Manufacturing/views/SalesRepDashboard.php" class="btn btn-light" style="margin-top: 15px;">View Dashboard</a>
                    </div>
                </div>
                
                <div class="role-card manager">
                    <div class="role-content">
                        <div class="role-title">👨‍💼 Sales Manager</div>
                        <ul class="role-features">
                            <li>Team performance analytics</li>
                            <li>Pipeline oversight & management</li>
                            <li>Territory assignment control</li>
                            <li>Inventory alerts & reports</li>
                            <li>User role management</li>
                            <li>Sales forecasting tools</li>
                        </ul>
                        <a href="modules/Manufacturing/views/ManagerDashboard.php" class="btn btn-light" style="margin-top: 15px;">View Dashboard</a>
                    </div>
                </div>
                
                <div class="role-card client">
                    <div class="role-content">
                        <div class="role-title">🏢 Client User</div>
                        <ul class="role-features">
                            <li>Order history & tracking</li>
                            <li>One-click reorder functionality</li>
                            <li>Invoice downloads & management</li>
                            <li>Account information updates</li>
                            <li>Support ticket creation</li>
                            <li>Pricing tier visibility</li>
                        </ul>
                        <a href="modules/Manufacturing/views/ClientPortal.php" class="btn btn-light" style="margin-top: 15px;">View Portal</a>
                    </div>
                </div>
                
                <div class="role-card admin">
                    <div class="role-content">
                        <div class="role-title">⚙️ System Administrator</div>
                        <ul class="role-features">
                            <li>User & role management</li>
                            <li>System configuration control</li>
                            <li>Security audit logs</li>
                            <li>Permission matrix management</li>
                            <li>Territory administration</li>
                            <li>System health monitoring</li>
                        </ul>
                        <a href="modules/Manufacturing/views/AdminPanel.php" class="btn btn-light" style="margin-top: 15px;">View Admin Panel</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Features -->
        <div class="feature-section">
            <div class="feature-header">
                <div class="feature-icon">🛡️</div>
                <div class="feature-title">Enterprise Security Features</div>
            </div>
            
            <div class="security-features">
                <div class="security-grid">
                    <div class="security-item">
                        <div class="security-title">🔐 JWT Authentication</div>
                        <div class="security-desc">RS256 secure algorithm with 15-minute access tokens and 7-day refresh tokens for maximum security</div>
                    </div>
                    <div class="security-item">
                        <div class="security-title">🛡️ API Security</div>
                        <div class="security-desc">Rate limiting by role, CSRF protection, input sanitization, and comprehensive security headers</div>
                    </div>
                    <div class="security-item">
                        <div class="security-title">🏢 Territory Access</div>
                        <div class="security-desc">Geographic data filtering with role-based territories and secure data isolation</div>
                    </div>
                    <div class="security-item">
                        <div class="security-title">📊 Session Management</div>
                        <div class="security-desc">Multi-device session support with 24-hour timeout and comprehensive device tracking</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Demo Interfaces -->
        <div class="feature-section">
            <div class="feature-header">
                <div class="feature-icon">📱</div>
                <div class="feature-title">Role-Specific Interface Demos</div>
            </div>
            
            <!-- Sales Rep Interface Demo -->
            <div class="demo-interface">
                <div class="interface-header">
                    <div class="interface-title">👤 Sales Rep Dashboard</div>
                    <span class="status-badge badge-secure">🔒 Territory Access</span>
                </div>
                <div class="mock-screen">
                    <div class="mock-header">
                        <div>
                            <div class="mock-user">John Smith - Sales Representative</div>
                            <div class="mock-role">Northeast Territory | Performance: 95%</div>
                        </div>
                        <span class="status-badge badge-active">🟢 Online</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center;">
                            <div style="font-size: 2em; color: #28a745; font-weight: bold;">24</div>
                            <div style="color: #6c757d;">Quotes This Month</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center;">
                            <div style="font-size: 2em; color: #007bff; font-weight: bold;">18</div>
                            <div style="color: #6c757d;">Active Clients</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center;">
                            <div style="font-size: 2em; color: #6f42c1; font-weight: bold;">87%</div>
                            <div style="color: #6c757d;">Conversion Rate</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button class="btn btn-primary">📱 Product Catalog</button>
                        <button class="btn btn-success">👥 My Clients</button>
                        <button class="btn btn-warning">📋 Create Quote</button>
                        <button class="btn btn-secondary">📊 Pipeline</button>
                    </div>
                </div>
            </div>

            <!-- Manager Interface Demo -->
            <div class="demo-interface">
                <div class="interface-header">
                    <div class="interface-title">👨‍💼 Manager Dashboard</div>
                    <span class="status-badge badge-secure">🔒 Team Access</span>
                </div>
                <div class="mock-screen">
                    <div class="mock-header">
                        <div>
                            <div class="mock-user">Sarah Johnson - Sales Manager</div>
                            <div class="mock-role">Regional Manager | Team: 8 Reps</div>
                        </div>
                        <span class="status-badge badge-active">🟢 Managing</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center;">
                            <div style="font-size: 2em; color: #28a745; font-weight: bold;">$127K</div>
                            <div style="color: #6c757d;">Team Revenue</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center;">
                            <div style="font-size: 2em; color: #17a2b8; font-weight: bold;">142</div>
                            <div style="color: #6c757d;">Active Orders</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center;">
                            <div style="font-size: 2em; color: #ffc107; color: black; font-weight: bold;">4</div>
                            <div style="color: #6c757d;">Inventory Alerts</div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <button class="btn btn-primary">📈 Team Analytics</button>
                        <button class="btn btn-success">🎯 Pipeline Management</button>
                        <button class="btn btn-warning">📦 Inventory Reports</button>
                        <button class="btn btn-danger">👥 User Management</button>
                    </div>
                </div>
            </div>

            <!-- Client Portal Demo -->
            <div class="demo-interface">
                <div class="interface-header">
                    <div class="interface-title">🏢 Client Self-Service Portal</div>
                    <span class="status-badge badge-secure">🔒 Secure Portal</span>
                </div>
                <div class="mock-screen">
                    <div class="mock-header">
                        <div>
                            <div class="mock-user">Manufacturing Corp - Client Portal</div>
                            <div class="mock-role">Premium Tier | Account: MFC-2024</div>
                        </div>
                        <span class="status-badge badge-active">🟢 Active Account</span>
                    </div>
                    <div style="background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 10px 0;">
                        <strong>Recent Order:</strong> ORD-2024-0156 - $23,450.00<br>
                        <span style="color: #0c5460;">Status: In Production | Expected Delivery: Jan 15, 2024</span>
                    </div>
                    <div style="margin-top: 15px;">
                        <button class="btn btn-primary">📋 Order History</button>
                        <button class="btn btn-success">🔄 Quick Reorder</button>
                        <button class="btn btn-warning">📄 Download Invoices</button>
                        <button class="btn btn-secondary">💬 Support</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Technical Implementation -->
        <div class="feature-section">
            <div class="feature-header">
                <div class="feature-icon">⚙️</div>
                <div class="feature-title">Technical Implementation Details</div>
            </div>
            
            <div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 10px 0;">
                <h4 style="color: #495057; margin-bottom: 15px;">🏗️ Architecture Components</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div>
                        <h5 style="color: #6f42c1; margin-bottom: 10px;">Database Schema</h5>
                        <ul style="list-style: none; padding: 0; color: #495057;">
                            <li>• mfg_role_definitions</li>
                            <li>• mfg_user_roles</li>
                            <li>• mfg_territories</li>
                            <li>• mfg_user_territories</li>
                            <li>• mfg_user_role_permissions</li>
                        </ul>
                    </div>
                    <div>
                        <h5 style="color: #6f42c1; margin-bottom: 10px;">API Classes</h5>
                        <ul style="list-style: none; padding: 0; color: #495057;">
                            <li>• JWTManager.php</li>
                            <li>• AuthMiddleware.php</li>
                            <li>• RoleDefinitions.php</li>
                            <li>• TerritoryFilter.php</li>
                            <li>• SessionManager.php</li>
                        </ul>
                    </div>
                    <div>
                        <h5 style="color: #6f42c1; margin-bottom: 10px;">Role Interfaces</h5>
                        <ul style="list-style: none; padding: 0; color: #495057;">
                            <li>• SalesRepDashboard.php</li>
                            <li>• ManagerDashboard.php</li>
                            <li>• ClientPortal.php</li>
                            <li>• AdminPanel.php</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; margin: 20px 0;">
                <h4 style="color: #155724; margin-bottom: 15px;">✅ Feature 6 Implementation Complete</h4>
                <p style="color: #155724; margin: 0; font-size: 1.1em;">
                    <strong>Enterprise-Grade User Role Management:</strong> Complete RBAC system with JWT authentication, 
                    territory-based access control, role-specific dashboards, and client self-service portal. 
                    All security features tested and validated. Ready for production deployment.
                </p>
                <div style="margin-top: 15px;">
                    <a href="test_feature6_security_comprehensive.php" class="btn btn-success">View Security Report</a>
                    <a href="manufacturing_demo.php" class="btn btn-primary">All 6 Features Demo</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add interactive elements and role switching demo
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔐 Feature 6: User Role Management Demo Loaded');
            
            // Simulate live status updates
            setInterval(function() {
                const badges = document.querySelectorAll('.badge-active');
                badges.forEach(badge => {
                    badge.style.opacity = badge.style.opacity === '0.7' ? '1' : '0.7';
                });
            }, 2000);
            
            // Add hover effects for role cards
            const roleCards = document.querySelectorAll('.role-card');
            roleCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.transition = 'transform 0.3s ease';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>
