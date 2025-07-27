<?php
/**
 * Feature 6: Complete Role Management & Permissions System
 * Manufacturing Distribution CRM
 */

// Initialize session properly
require_once 'session_init.php';

// Demo user data with different roles
$demo_users = [
    'sales_rep' => [
        'id' => 'user_001',
        'name' => 'Sarah Martinez',
        'email' => 'sarah@company.com',
        'role' => 'Sales Rep',
        'permissions' => ['view_catalog', 'create_quotes', 'view_own_clients', 'mobile_access'],
        'territory' => 'West Coast',
        'clients_count' => 47,
        'active_quotes' => 12
    ],
    'manager' => [
        'id' => 'user_002', 
        'name' => 'Mike Johnson',
        'email' => 'mike@company.com',
        'role' => 'Sales Manager',
        'permissions' => ['view_all_data', 'manage_team', 'approve_quotes', 'view_reports', 'inventory_access'],
        'team_size' => 8,
        'total_pipeline' => '$2.4M',
        'approval_pending' => 5
    ],
    'client' => [
        'id' => 'client_001',
        'name' => 'Industrial Solutions Ltd',
        'email' => 'orders@industrialsolutions.com', 
        'role' => 'Client',
        'permissions' => ['view_orders', 'reorder', 'download_invoices', 'track_shipments'],
        'tier' => 'Premium',
        'credit_limit' => '$50,000',
        'open_orders' => 3
    ],
    'admin' => [
        'id' => 'admin_001',
        'name' => 'Alex Thompson',
        'email' => 'admin@company.com',
        'role' => 'System Admin',
        'permissions' => ['full_access', 'user_management', 'system_config', 'security_settings'],
        'last_login' => '2025-07-25 09:30:00',
        'system_alerts' => 2
    ]
];

// Get current demo role
$current_role = $_GET['role'] ?? 'sales_rep';
$current_user = $demo_users[$current_role] ?? $demo_users['sales_rep'];

// Role switching functionality
if (isset($_POST['switch_role'])) {
    $current_role = $_POST['switch_role'];
    $current_user = $demo_users[$current_role];
    header("Location: feature6_role_management.php?role=" . $current_role);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature 6: Role Management & Permissions - Manufacturing CRM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        .header { background: linear-gradient(135deg, #8e44ad, #9b59b6); color: white; padding: 20px; }
        .header-nav { display: flex; align-items: center; justify-content: space-between; max-width: 1200px; margin: 0 auto; }
        .header-center { text-align: center; flex: 1; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { font-size: 1.2em; opacity: 0.9; }
        
        .back-link { 
            color: white; text-decoration: none; padding: 12px 20px; border-radius: 8px; 
            background: rgba(255,255,255,0.2); font-weight: 600; transition: all 0.3s ease;
        }
        .back-link:hover { background: rgba(255,255,255,0.3); text-decoration: none; transform: translateY(-2px); }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        
        .feature-intro {
            background: white; border-radius: 15px; padding: 30px; margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); border-left: 5px solid #8e44ad;
        }
        
        .role-switcher {
            background: linear-gradient(135deg, #3498db, #2980b9); color: white; 
            border-radius: 15px; padding: 25px; margin-bottom: 30px; text-align: center;
        }
        
        .role-buttons { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 20px; }
        .role-btn {
            padding: 12px 24px; border: 2px solid rgba(255,255,255,0.3); border-radius: 8px;
            background: rgba(255,255,255,0.1); color: white; text-decoration: none;
            font-weight: 600; transition: all 0.3s ease; cursor: pointer;
        }
        .role-btn:hover, .role-btn.active { 
            background: rgba(255,255,255,0.2); transform: translateY(-2px); text-decoration: none; color: white;
        }
        
        .current-user {
            background: white; border-radius: 15px; padding: 25px; margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px;
        }
        
        .user-avatar {
            width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #8e44ad, #9b59b6);
            display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold;
        }
        
        .user-info h3 { color: #2c3e50; margin-bottom: 5px; }
        .user-info p { color: #7f8c8d; margin-bottom: 5px; }
        .role-badge {
            display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600;
            background: #e8f5e8; color: #2d8f2d; margin-top: 5px;
        }
        
        .permissions-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 25px; margin-bottom: 30px; 
        }
        
        .permission-card {
            background: white; border-radius: 15px; padding: 25px; 
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); transition: all 0.3s ease;
        }
        .permission-card:hover { transform: translateY(-3px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
        
        .permission-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .permission-icon { 
            width: 50px; height: 50px; border-radius: 12px; display: flex; 
            align-items: center; justify-content: center; font-size: 24px; color: white;
        }
        
        .dashboard-section {
            background: white; border-radius: 15px; padding: 25px; margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .stat-item {
            text-align: center; padding: 20px; border-radius: 10px; background: #f8f9fa;
            border-left: 4px solid #8e44ad;
        }
        .stat-number { font-size: 2em; font-weight: bold; color: #8e44ad; }
        .stat-label { color: #7f8c8d; margin-top: 5px; }
        
        .action-buttons { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px; }
        .action-btn {
            padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600;
            transition: all 0.3s ease; border: none; cursor: pointer;
        }
        .btn-primary { background: #8e44ad; color: white; }
        .btn-primary:hover { background: #7d3c98; text-decoration: none; color: white; transform: translateY(-2px); }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; text-decoration: none; color: white; }
        
        .security-features {
            background: linear-gradient(135deg, #e74c3c, #c0392b); color: white;
            border-radius: 15px; padding: 25px; margin-top: 30px;
        }
        
        .feature-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 20px; }
        .feature-item {
            background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px;
            border-left: 3px solid rgba(255,255,255,0.3);
        }
        
        .permission-list { list-style: none; margin-top: 15px; }
        .permission-list li {
            padding: 8px 0; border-bottom: 1px solid #ecf0f1; display: flex;
            align-items: center; gap: 10px;
        }
        .permission-list li:last-child { border-bottom: none; }
        .permission-check { color: #27ae60; font-weight: bold; }
        .permission-cross { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-nav">
            <a href="/" class="back-link">← Back to Dashboard</a>
            <div class="header-center">
                <h1>👥 Feature 6: Role Management & Permissions</h1>
                <p>Comprehensive RBAC System with JWT Authentication</p>
            </div>
            <a href="test_feature6_security_comprehensive.php" class="back-link">Security Test →</a>
        </div>
    </div>

    <div class="container">
        <div class="feature-intro">
            <h2 style="color: #2c3e50; margin-bottom: 15px;">🔐 Advanced Role-Based Access Control</h2>
            <p style="color: #7f8c8d; line-height: 1.6;">
                Our comprehensive role management system provides secure, granular permissions for different user types in manufacturing distribution. 
                Each role has carefully designed access levels to ensure data security while maximizing productivity.
            </p>
        </div>

        <div class="role-switcher">
            <h3>🎭 Experience Different User Roles</h3>
            <p style="margin: 10px 0; opacity: 0.9;">Switch between roles to see how permissions and interfaces change</p>
            
            <form method="post" style="display: inline;">
                <div class="role-buttons">
                    <button type="submit" name="switch_role" value="sales_rep" 
                            class="role-btn <?= $current_role === 'sales_rep' ? 'active' : '' ?>">
                        📱 Sales Rep
                    </button>
                    <button type="submit" name="switch_role" value="manager" 
                            class="role-btn <?= $current_role === 'manager' ? 'active' : '' ?>">
                        📊 Manager
                    </button>
                    <button type="submit" name="switch_role" value="client" 
                            class="role-btn <?= $current_role === 'client' ? 'active' : '' ?>">
                        🏢 Client
                    </button>
                    <button type="submit" name="switch_role" value="admin" 
                            class="role-btn <?= $current_role === 'admin' ? 'active' : '' ?>">
                        ⚙️ Admin
                    </button>
                </div>
            </form>
        </div>

        <div class="current-user">
            <div class="user-avatar">
                <?= strtoupper(substr($current_user['name'], 0, 1)) ?>
            </div>
            <div class="user-info">
                <h3><?= htmlspecialchars($current_user['name']) ?></h3>
                <p><?= htmlspecialchars($current_user['email']) ?></p>
                <span class="role-badge"><?= htmlspecialchars($current_user['role']) ?></span>
            </div>
        </div>

        <?php if ($current_role === 'sales_rep'): ?>
        <div class="dashboard-section">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">📱 Sales Rep Dashboard</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['clients_count'] ?></div>
                    <div class="stat-label">Active Clients</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['active_quotes'] ?></div>
                    <div class="stat-label">Active Quotes</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['territory'] ?></div>
                    <div class="stat-label">Territory</div>
                </div>
            </div>
            <div class="action-buttons">
                <a href="feature1_product_catalog.php" class="action-btn btn-primary">View Product Catalog</a>
                <a href="feature4_quote_builder.php" class="action-btn btn-primary">Create Quote</a>
                <a href="feature2_order_pipeline.php" class="action-btn btn-secondary">View Pipeline</a>
            </div>
        </div>
        <?php elseif ($current_role === 'manager'): ?>
        <div class="dashboard-section">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">📊 Manager Dashboard</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['team_size'] ?></div>
                    <div class="stat-label">Team Members</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['total_pipeline'] ?></div>
                    <div class="stat-label">Pipeline Value</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['approval_pending'] ?></div>
                    <div class="stat-label">Pending Approvals</div>
                </div>
            </div>
            <div class="action-buttons">
                <a href="feature2_order_pipeline.php" class="action-btn btn-primary">Team Performance</a>
                <a href="feature3_inventory_integration.php" class="action-btn btn-primary">Inventory Management</a>
                <a href="#" class="action-btn btn-secondary">Approve Quotes</a>
            </div>
        </div>
        <?php elseif ($current_role === 'client'): ?>
        <div class="dashboard-section">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">🏢 Client Portal</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['tier'] ?></div>
                    <div class="stat-label">Account Tier</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['credit_limit'] ?></div>
                    <div class="stat-label">Credit Limit</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['open_orders'] ?></div>
                    <div class="stat-label">Open Orders</div>
                </div>
            </div>
            <div class="action-buttons">
                <a href="feature2_order_pipeline.php" class="action-btn btn-primary">Track Orders</a>
                <a href="feature1_product_catalog.php" class="action-btn btn-primary">Browse Catalog</a>
                <a href="#" class="action-btn btn-secondary">Download Invoices</a>
            </div>
        </div>
        <?php elseif ($current_role === 'admin'): ?>
        <div class="dashboard-section">
            <h3 style="color: #2c3e50; margin-bottom: 20px;">⚙️ Admin Control Panel</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">Full</div>
                    <div class="stat-label">System Access</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $current_user['system_alerts'] ?></div>
                    <div class="stat-label">System Alerts</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">Active</div>
                    <div class="stat-label">Security Status</div>
                </div>
            </div>
            <div class="action-buttons">
                <a href="#" class="action-btn btn-primary">User Management</a>
                <a href="#" class="action-btn btn-primary">System Config</a>
                <a href="test_feature6_security_comprehensive.php" class="action-btn btn-secondary">Security Settings</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="permissions-grid">
            <div class="permission-card">
                <div class="permission-header">
                    <div class="permission-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        🔍
                    </div>
                    <h4 style="color: #2c3e50;">Data Access Permissions</h4>
                </div>
                <ul class="permission-list">
                    <?php
                    $data_permissions = [
                        'sales_rep' => ['view_catalog' => true, 'view_own_clients' => true, 'view_all_clients' => false, 'view_inventory' => true],
                        'manager' => ['view_catalog' => true, 'view_own_clients' => true, 'view_all_clients' => true, 'view_inventory' => true],
                        'client' => ['view_catalog' => true, 'view_own_clients' => false, 'view_all_clients' => false, 'view_inventory' => false],
                        'admin' => ['view_catalog' => true, 'view_own_clients' => true, 'view_all_clients' => true, 'view_inventory' => true]
                    ];
                    $permissions = $data_permissions[$current_role];
                    ?>
                    <li>
                        <span class="<?= $permissions['view_catalog'] ? 'permission-check' : 'permission-cross' ?>">
                            <?= $permissions['view_catalog'] ? '✓' : '✗' ?>
                        </span>
                        View Product Catalog
                    </li>
                    <li>
                        <span class="<?= $permissions['view_own_clients'] ? 'permission-check' : 'permission-cross' ?>">
                            <?= $permissions['view_own_clients'] ? '✓' : '✗' ?>
                        </span>
                        View Own Clients
                    </li>
                    <li>
                        <span class="<?= $permissions['view_all_clients'] ? 'permission-check' : 'permission-cross' ?>">
                            <?= $permissions['view_all_clients'] ? '✓' : '✗' ?>
                        </span>
                        View All Clients
                    </li>
                    <li>
                        <span class="<?= $permissions['view_inventory'] ? 'permission-check' : 'permission-cross' ?>">
                            <?= $permissions['view_inventory'] ? '✓' : '✗' ?>
                        </span>
                        Access Inventory Data
                    </li>
                </ul>
            </div>

            <div class="permission-card">
                <div class="permission-header">
                    <div class="permission-icon" style="background: linear-gradient(135deg, #27ae60, #2ecc71);">
                        ✏️
                    </div>
                    <h4 style="color: #2c3e50;">Action Permissions</h4>
                </div>
                <ul class="permission-list">
                    <?php
                    $action_permissions = [
                        'sales_rep' => ['create_quotes' => true, 'modify_quotes' => true, 'approve_quotes' => false, 'manage_users' => false],
                        'manager' => ['create_quotes' => true, 'modify_quotes' => true, 'approve_quotes' => true, 'manage_users' => false],
                        'client' => ['create_quotes' => false, 'modify_quotes' => false, 'approve_quotes' => false, 'manage_users' => false],
                        'admin' => ['create_quotes' => true, 'modify_quotes' => true, 'approve_quotes' => true, 'manage_users' => true]
                    ];
                    $permissions = $action_permissions[$current_role];
                    ?>
                    <li>
                        <span class="<?= $permissions['create_quotes'] ? 'permission-check' : 'permission-cross' ?>">
                            <?= $permissions['create_quotes'] ? '✓' : '✗' ?>
                        </span>
                        Create Quotes
                    </li>
                    <li>
                        <span class="<?= $permissions['modify_quotes'] ? 'permission-check' : 'permission-cross' ?>">
                            <?= $permissions['modify_quotes'] ? '✓' : '✗' ?>
                        </span>
                        Modify Quotes
                    </li>
                    <li>
                        <span class="<?= $permissions['approve_quotes'] ? 'permission-check' : 'permission-cross' ?>">
                            <?= $permissions['approve_quotes'] ? '✓' : '✗' ?>
                        </span>
                        Approve Quotes
                    </li>
                    <li>
                        <span class="<?= $permissions['manage_users'] ? 'permission-check' : 'permission-cross' ?>">
                            <?= $permissions['manage_users'] ? '✓' : '✗' ?>
                        </span>
                        Manage Users
                    </li>
                </ul>
            </div>

            <div class="permission-card">
                <div class="permission-header">
                    <div class="permission-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        🔒
                    </div>
                    <h4 style="color: #2c3e50;">Security Features</h4>
                </div>
                <ul class="permission-list">
                    <li><span class="permission-check">✓</span> JWT Token Authentication</li>
                    <li><span class="permission-check">✓</span> Role-based Route Guards</li>
                    <li><span class="permission-check">✓</span> Session Management</li>
                    <li><span class="permission-check">✓</span> API Access Control</li>
                </ul>
            </div>
        </div>

        <div class="security-features">
            <h3 style="margin-bottom: 20px;">🛡️ Advanced Security Implementation</h3>
            <div class="feature-list">
                <div class="feature-item">
                    <h4>JWT Authentication</h4>
                    <p>Secure token-based authentication with automatic expiration and refresh capabilities.</p>
                </div>
                <div class="feature-item">
                    <h4>Granular Permissions</h4>
                    <p>Fine-grained access control at the feature, data, and API endpoint level.</p>
                </div>
                <div class="feature-item">
                    <h4>Client Portal Security</h4>
                    <p>Isolated client access with data segregation and secure document sharing.</p>
                </div>
                <div class="feature-item">
                    <h4>Audit Trail</h4>
                    <p>Comprehensive logging of user actions and system access for compliance.</p>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="test_feature6_security_comprehensive.php" class="action-btn btn-secondary" style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3);">
                    🔍 Run Comprehensive Security Test
                </a>
            </div>
        </div>
    </div>

    <script>
        // Add interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate permission cards
            const cards = document.querySelectorAll('.permission-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = `all 0.6s ease ${index * 0.2}s`;
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });

            // Add hover effects to stats
            const statItems = document.querySelectorAll('.stat-item');
            statItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                    this.style.transition = 'transform 0.3s ease';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>
