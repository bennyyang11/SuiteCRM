<?php
// Simplified feature page - no SuiteCRM entry point to avoid session conflicts
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature 6: Role Management - Manufacturing Distribution</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 20px; }
        .header-nav { display: flex; align-items: center; justify-content: space-between; max-width: 1200px; margin: 0 auto; }
        .header-center { text-align: center; flex: 1; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.2em; opacity: 0.9; }
        .back-link { color: white; text-decoration: none; padding: 10px 15px; border-radius: 6px; background: rgba(255,255,255,0.1); }
        .back-link:hover { background: rgba(255,255,255,0.2); text-decoration: none; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .coming-soon { background: white; border-radius: 10px; padding: 50px; text-align: center; margin: 20px 0; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .btn { padding: 12px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px; }
        .btn:hover { background: #0056b3; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="index.php" class="back-link">← Back to Dashboard</a>
            <div class="header-center">
                <h1>👥 Feature 6: User Role Management & Permissions</h1>
                <p>RBAC System with JWT Authentication</p>
            </div>
            <div>
                <a href="complete_manufacturing_demo.php" class="back-link">View All Features</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="coming-soon">
            <h2 style="color: #2c3e50; margin-bottom: 20px;">🚧 Feature Page Under Construction</h2>
            <p style="color: #6c757d; margin-bottom: 30px; font-size: 1.1em;">This comprehensive feature page is being built. In the meantime, you can test the working Role Management functionality:</p>
            
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="test_feature6_role_management_demo.php" class="btn">👥 Role Demo</a>
                <a href="test_feature6_security_comprehensive.php" class="btn">🔒 Security Test</a>
                <a href="complete_auth_results.html" class="btn">📋 Auth Report</a>
                <a href="complete_manufacturing_demo.php" class="btn">📋 View in Main Demo</a>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 8px;">
                <h4 style="color: #1976d2; margin-bottom: 10px;">✅ Role Management Features Available:</h4>
                <div style="color: #1976d2;">
                    • Sales Rep: Product catalog, quotes, own clients<br>
                    • Manager: Team performance, all quotes, inventory<br>
                    • Client: Order tracking, reorder, invoice history<br>
                    • Admin: User management, system configuration<br>
                    • JWT authentication with role-based access control
                </div>
            </div>
        </div>
    </div>
</body>
</html>
