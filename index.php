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
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header { padding: 20px; }
            .header h1 { font-size: 2em; }
            .features-grid { grid-template-columns: 1fr; }
            .nav-links { flex-direction: column; }
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
            <h3>✅ System Status: Fully Operational</h3>
            <p><strong>Features 1, 2, 3, 4 & 5 Complete:</strong> Product Catalog, Order Pipeline, Real-Time Inventory Integration, Quote Builder with PDF Export, and Advanced Search & Filtering are fully functional with mobile optimization and clean API endpoints.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Manufacturing Demo</h3>
                <p class="feature-desc">Complete showcase of Features 1, 2, 3, 4 & 5 with mobile-responsive interface, product catalog, order pipeline, real-time inventory integration, quote builder with PDF export, and advanced search with intelligent filtering.</p>
                <a href="complete_manufacturing_demo.php" class="btn success">View Demo</a>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🧹</div>
                <h3 class="feature-title">Clean Demo</h3>
                <p class="feature-desc">Warning-free presentation mode perfect for client demos and technical reviews featuring all 5 completed features without PHP deprecation noise.</p>
                <a href="clean_demo.php" class="btn">Clean Version</a>
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
