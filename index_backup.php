<?php
/**
 * SuiteCRM Manufacturing Distribution - Main Entry Point
 * Provides navigation to manufacturing features and SuiteCRM
 */

// Check if user is trying to access SuiteCRM directly
if (isset($_GET['module']) || isset($_GET['action']) || isset($_POST['module']) || isset($_POST['action'])) {
    // Pass through to actual SuiteCRM
    include 'index_original.php';
    exit;
}

// Otherwise show our manufacturing landing page
define('sugarEntry', true);
require_once('config.php');

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SuiteCRM Manufacturing Distribution</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; line-height: 1.6; }
        
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 40px; border-radius: 15px; text-align: center; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.2em; opacity: 0.9; }
        
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0; }
        
        .feature-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: transform 0.2s ease; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        
        .feature-icon { font-size: 3em; margin-bottom: 15px; text-align: center; }
        .feature-title { font-size: 1.3em; color: #2c3e50; margin-bottom: 10px; font-weight: 600; }
        .feature-desc { color: #7f8c8d; margin-bottom: 20px; }
        
        .btn { display: inline-block; background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 500; transition: all 0.3s ease; }
        .btn:hover { background: #2980b9; text-decoration: none; color: white; transform: translateY(-1px); }
        .btn.success { background: #27ae60; }
        .btn.success:hover { background: #229954; }
        .btn.warning { background: #f39c12; }
        .btn.warning:hover { background: #e67e22; }
        
        .status-banner { background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; padding: 20px; border-radius: 10px; border-left: 4px solid #28a745; margin: 20px 0; }
        .status-banner h3 { margin-bottom: 10px; }
        
        .nav-strip { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-links { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .nav-link { background: #ecf0f1; color: #2c3e50; padding: 8px 16px; text-decoration: none; border-radius: 5px; transition: all 0.3s ease; }
        .nav-link:hover { background: #3498db; color: white; text-decoration: none; }
        
        /* Features Navigation Menu */
        .features-nav { background: white; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .features-nav-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 15px 20px; font-weight: 600; font-size: 1.1em; }
        .features-nav-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0; }
        .feature-nav-item { border-right: 1px solid #ecf0f1; border-bottom: 1px solid #ecf0f1; }
        .feature-nav-item:hover { background: #f8f9fa; }
        .feature-nav-link { display: block; padding: 15px 20px; text-decoration: none; color: #2c3e50; transition: all 0.2s ease; }
        .feature-nav-link:hover { color: #3498db; text-decoration: none; }
        .feature-nav-icon { font-size: 1.2em; margin-right: 10px; }
        .feature-nav-title { font-weight: 500; margin-bottom: 3px; display: block; }
        .feature-nav-desc { font-size: 0.85em; color: #7f8c8d; }

        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { padding: 20px; }
            .header h1 { font-size: 2em; }
            .features-grid { grid-template-columns: 1fr; }
            .nav-links { flex-direction: column; }
            .features-nav-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏭 SuiteCRM Manufacturing Distribution</h1>
            <p>Enterprise Legacy Modernization Platform for Manufacturing Distributors</p>
        </div>

        <div class="nav-strip">
            <div class="nav-links">
                <a href="index.php?module=Users&action=Login" class="nav-link">🔐 Login to SuiteCRM</a>
                <a href="index.php?module=Home&action=index" class="nav-link">🏠 SuiteCRM Dashboard</a>
                <a href="index.php?module=Accounts&action=index" class="nav-link">👥 Accounts</a>
                <a href="index.php?module=Opportunities&action=index" class="nav-link">💰 Opportunities</a>
            </div>
        </div>

        <div class="status-banner">
            <h3>✅ System Status: All 6 Features Complete - Enterprise Ready!</h3>
            <p><strong>Features 1-6 Complete:</strong> Product Catalog, Order Pipeline, Real-Time Inventory Integration, Quote Builder with PDF Export, Advanced Search & Filtering, and User Role Management with JWT security are fully functional with mobile optimization and enterprise-grade security.</p>
        </div>

        <!-- Features Navigation Menu -->
        <div class="features-nav">
            <div class="features-nav-header">
                🎯 Quick Access to All 6 Manufacturing Features
            </div>
            <div class="features-nav-grid">
                <div class="feature-nav-item">
                    <a href="feature1_product_catalog.php" class="feature-nav-link">
                        <span class="feature-nav-icon">📱</span>
                        <span class="feature-nav-title">Feature 1: Product Catalog</span>
                        <div class="feature-nav-desc">Mobile-responsive catalog with client-specific pricing</div>
                    </a>
                </div>
                <div class="feature-nav-item">
                    <a href="feature2_order_pipeline.php" class="feature-nav-link">
                        <span class="feature-nav-icon">📊</span>
                        <span class="feature-nav-title">Feature 2: Order Pipeline</span>
                        <div class="feature-nav-desc">7-stage tracking from quote to delivery</div>
                    </a>
                </div>
                <div class="feature-nav-item">
                    <a href="feature3_inventory_integration.php" class="feature-nav-link">
                        <span class="feature-nav-icon">📦</span>
                        <span class="feature-nav-title">Feature 3: Inventory Integration</span>
                        <div class="feature-nav-desc">Real-time stock levels and AI suggestions</div>
                    </a>
                </div>
                <div class="feature-nav-item">
                    <a href="feature4_quote_builder.php" class="feature-nav-link">
                        <span class="feature-nav-icon">📋</span>
                        <span class="feature-nav-title">Feature 4: Quote Builder</span>
                        <div class="feature-nav-desc">Drag-and-drop quotes with PDF export</div>
                    </a>
                </div>
                <div class="feature-nav-item">
                    <a href="feature5_advanced_search.php" class="feature-nav-link">
                        <span class="feature-nav-icon">🔍</span>
                        <span class="feature-nav-title">Feature 5: Advanced Search</span>
                        <div class="feature-nav-desc">Google-like search with intelligent filtering</div>
                    </a>
                </div>
                <div class="feature-nav-item">
                    <a href="feature6_role_management.php" class="feature-nav-link">
                        <span class="feature-nav-icon">👥</span>
                        <span class="feature-nav-title">Feature 6: Role Management</span>
                        <div class="feature-nav-desc">RBAC system with JWT authentication</div>
                    </a>
                </div>
            </div>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Manufacturing Demo</h3>
                <p class="feature-desc">Complete showcase of all 6 features with mobile-responsive interface, product catalog, order pipeline, real-time inventory integration, quote builder with PDF export, advanced search with intelligent filtering, and comprehensive user role management system.</p>
                <a href="complete_manufacturing_demo.php" class="btn success">View Demo</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🧹</div>
                <h3 class="feature-title">Clean Demo</h3>
                <p class="feature-desc">Warning-free presentation mode perfect for client demos and technical reviews featuring all 6 completed features including role management without PHP deprecation noise.</p>
                <a href="clean_demo.php" class="btn">Clean Version</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3 class="feature-title">Role Management Demo</h3>
                <p class="feature-desc">Feature 6: Complete RBAC system with JWT authentication, role-specific dashboards, territory-based access control, and client self-service portal.</p>
                <a href="test_feature6_role_management_demo.php" class="btn success">View Roles</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🔧</div>
                <h3 class="feature-title">API Testing</h3>
                <p class="feature-desc">Technical validation suite for testing Product Catalog and Order Pipeline APIs with JSON responses and performance metrics.</p>
                <a href="test_manufacturing_apis.php" class="btn warning">Test APIs</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🏠</div>
                <h3 class="feature-title">SuiteCRM Access</h3>
                <p class="feature-desc">Access the full SuiteCRM interface with integrated manufacturing features and modernized dashboard.</p>
                <a href="index.php?module=Users&action=Login" class="btn">Login to CRM</a>
            </div>
        </div>

        <div style="background: white; padding: 25px; border-radius: 10px; margin-top: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <h3 style="color: #2c3e50; margin-bottom: 15px;">Implementation Progress</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: #27ae60;">✅ Completed</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 5px 0;">📱 Mobile Product Catalog</li>
                        <li style="padding: 5px 0;">📊 Order Pipeline Dashboard</li>
                        <li style="padding: 5px 0;">⚡ Performance Optimization</li>
                        <li style="padding: 5px 0;">🎨 UI Integration</li>
                    </ul>
                </div>
                <div>
                    <h4 style="color: #3498db;">🔄 Ready to Implement</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li style="padding: 5px 0;">📦 Real-Time Inventory</li>
                        <li style="padding: 5px 0;">📄 Quote Builder & PDF</li>
                        <li style="padding: 5px 0;">🔍 Advanced Search</li>
                        <li style="padding: 5px 0;">👥 User Role Management</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.feature-card');
            cards.forEach((card, index) => {
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
        });
    </script>
</body>
</html>
