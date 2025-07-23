<?php
// Simple SuiteCRM status check
define('sugarEntry', true);

// Basic SuiteCRM check
if (file_exists('config.php')) {
    require_once('config.php');
    echo "<!DOCTYPE html>";
    echo "<html><head><title>SuiteCRM Manufacturing Distribution</title>";
    echo "<meta charset='UTF-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    echo "<style>";
    echo "body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }";
    echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }";
    echo ".header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 20px; border-radius: 8px; text-align: center; margin-bottom: 20px; }";
    echo ".nav-links { display: flex; gap: 15px; justify-content: center; margin: 20px 0; }";
    echo ".nav-link { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; transition: all 0.3s ease; }";
    echo ".nav-link:hover { background: #2980b9; text-decoration: none; color: white; }";
    echo ".status { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0; }";
    echo "</style>";
    echo "</head><body>";
    
    echo "<div class='container'>";
    echo "<div class='header'>";
    echo "<h1>🏭 SuiteCRM Manufacturing Distribution</h1>";
    echo "<p>Enterprise Legacy Modernization Platform</p>";
    echo "</div>";
    
    echo "<div class='status'>";
    echo "<h3>✅ System Status: Operational</h3>";
    echo "<p>SuiteCRM configuration loaded successfully</p>";
    echo "</div>";
    
    echo "<div class='nav-links'>";
    echo "<a href='complete_manufacturing_demo.php' class='nav-link'>📱 Manufacturing Demo</a>";
    echo "<a href='clean_demo.php' class='nav-link'>🧹 Clean Demo</a>";
    echo "<a href='test_manufacturing_apis.php' class='nav-link'>🔧 API Test</a>";
    echo "<a href='index.php?module=Users&action=Login' class='nav-link'>🔐 Login to SuiteCRM</a>";
    echo "</div>";
    
    // Quick feature status
    echo "<h3>Implementation Status</h3>";
    echo "<ul>";
    echo "<li><strong>✅ Feature 1:</strong> Mobile Product Catalog with Client-Specific Pricing</li>";
    echo "<li><strong>✅ Feature 2:</strong> Order Tracking Dashboard (Quote → Invoice Pipeline)</li>";
    echo "<li><strong>🔄 Feature 3:</strong> Real-Time Inventory Integration (Ready to implement)</li>";
    echo "<li><strong>📋 Features 4-6:</strong> Planned for implementation</li>";
    echo "</ul>";
    
    echo "<h3>Access Points</h3>";
    echo "<ul>";
    echo "<li><strong>Manufacturing Demo:</strong> <a href='complete_manufacturing_demo.php'>Complete feature showcase</a></li>";
    echo "<li><strong>Clean Demo:</strong> <a href='clean_demo.php'>Warning-free presentation mode</a></li>";
    echo "<li><strong>API Testing:</strong> <a href='test_manufacturing_apis.php'>Technical validation</a></li>";
    echo "<li><strong>SuiteCRM Login:</strong> <a href='index.php?module=Users&action=Login'>Admin interface</a></li>";
    echo "</ul>";
    
    echo "</div>";
    echo "</body></html>";
} else {
    echo "SuiteCRM Configuration Error";
}
?>
