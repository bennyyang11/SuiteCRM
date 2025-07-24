<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'Api/V8/Middleware/AuthMiddleware.php';
require_once 'Api/V8/RoleManagement/RoleDefinitions.php';
require_once 'include/database/DBManager.php';

use Api\V8\Middleware\AuthMiddleware;
use Api\V8\RoleManagement\RoleDefinitions;

class AdminPanel
{
    private AuthMiddleware $authMiddleware;
    private RoleDefinitions $roleManager;
    private DBManager $db;
    private array $currentUser;

    public function __construct()
    {
        $this->db = DBManager::getInstance();
        $this->authMiddleware = new AuthMiddleware($this->db->getConnection());
        $this->roleManager = new RoleDefinitions($this->db->getConnection());
        
        // Authenticate and authorize
        $this->currentUser = $this->authMiddleware->protect('system_config', 'ADMIN', '/admin-panel');
        
        // Verify user has admin role
        if ($this->currentUser['primary_role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: Admin role required']);
            exit;
        }
    }

    /**
     * Main admin panel display
     */
    public function display()
    {
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'dashboard':
                $this->displayDashboard();
                break;
            case 'users':
                $this->manageUsers();
                break;
            case 'roles':
                $this->manageRoles();
                break;
            case 'territories':
                $this->manageTerritories();
                break;
            case 'permissions':
                $this->managePermissions();
                break;
            case 'system_config':
                $this->manageSystemConfig();
                break;
            case 'audit_logs':
                $this->viewAuditLogs();
                break;
            case 'security':
                $this->manageSecuritySettings();
                break;
            case 'analytics':
                $this->systemAnalytics();
                break;
            default:
                $this->displayDashboard();
        }
    }

    /**
     * Display admin dashboard
     */
    private function displayDashboard()
    {
        $dashboardData = [
            'system_overview' => $this->getSystemOverview(),
            'user_statistics' => $this->getUserStatistics(),
            'recent_activities' => $this->getRecentActivities(),
            'security_alerts' => $this->getSecurityAlerts(),
            'system_health' => $this->getSystemHealth(),
            'quick_actions' => $this->getQuickActions()
        ];
        
        $this->sendJsonResponse(['success' => true, 'data' => $dashboardData]);
    }

    /**
     * User management interface
     */
    private function manageUsers()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getUsers();
                break;
            case 'POST':
                $this->createUser();
                break;
            case 'PUT':
                $this->updateUser();
                break;
            case 'DELETE':
                $this->deleteUser();
                break;
        }
    }

    /**
     * Role management interface
     */
    private function manageRoles()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getRoles();
                break;
            case 'POST':
                $this->createRole();
                break;
            case 'PUT':
                $this->updateRole();
                break;
            case 'DELETE':
                $this->deleteRole();
                break;
        }
    }

    /**
     * Territory management interface
     */
    private function manageTerritories()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getTerritories();
                break;
            case 'POST':
                $this->createTerritory();
                break;
            case 'PUT':
                $this->updateTerritory();
                break;
            case 'DELETE':
                $this->deleteTerritory();
                break;
        }
    }

    /**
     * Permission management interface
     */
    private function managePermissions()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getPermissions();
                break;
            case 'POST':
                $this->assignPermissions();
                break;
        }
    }

    /**
     * System configuration management
     */
    private function manageSystemConfig()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getSystemConfig();
                break;
            case 'POST':
            case 'PUT':
                $this->updateSystemConfig();
                break;
        }
    }

    /**
     * View audit logs
     */
    private function viewAuditLogs()
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $filter = $_GET['filter'] ?? '';
        
        $logs = $this->getAuditLogs($page, $limit, $filter);
        
        $this->sendJsonResponse(['success' => true, 'data' => $logs]);
    }

    /**
     * Security settings management
     */
    private function manageSecuritySettings()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($method) {
            case 'GET':
                $this->getSecuritySettings();
                break;
            case 'POST':
            case 'PUT':
                $this->updateSecuritySettings();
                break;
        }
    }

    /**
     * System analytics
     */
    private function systemAnalytics()
    {
        $analyticsData = [
            'usage_statistics' => $this->getUsageStatistics(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'user_activity' => $this->getUserActivityStats(),
            'feature_usage' => $this->getFeatureUsageStats()
        ];
        
        $this->sendJsonResponse(['success' => true, 'data' => $analyticsData]);
    }

    /**
     * Get system overview
     */
    private function getSystemOverview(): array
    {
        $overview = [];
        
        // User counts by role
        $userCountQuery = "
            SELECT rd.role_name, COUNT(DISTINCT ur.user_id) as user_count
            FROM mfg_role_definitions rd
            LEFT JOIN mfg_user_roles ur ON rd.id = ur.role_id AND ur.deleted = 0
            WHERE rd.deleted = 0 AND rd.is_active = 1
            GROUP BY rd.id, rd.role_name
        ";
        
        $stmt = $this->db->getConnection()->prepare($userCountQuery);
        $stmt->execute();
        $userCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $overview['user_counts'] = $userCounts;
        
        // Territory counts
        $territoryQuery = "SELECT COUNT(*) as total FROM mfg_territories WHERE deleted = 0";
        $stmt = $this->db->getConnection()->prepare($territoryQuery);
        $stmt->execute();
        $overview['territory_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active sessions
        $overview['active_sessions'] = $this->getActiveSessionCount();
        
        // System status
        $overview['system_status'] = $this->getSystemStatus();
        
        return $overview;
    }

    /**
     * Get user statistics
     */
    private function getUserStatistics(): array
    {
        $stats = [];
        
        // Total users
        $totalQuery = "SELECT COUNT(*) as total FROM users WHERE deleted = 0";
        $stmt = $this->db->getConnection()->prepare($totalQuery);
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Active users (logged in within 30 days)
        $activeQuery = "
            SELECT COUNT(DISTINCT user_id) as active_users
            FROM user_sessions 
            WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        $stmt = $this->db->getConnection()->prepare($activeQuery);
        $stmt->execute();
        $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'] ?? 0;
        
        // New users this month
        $newQuery = "
            SELECT COUNT(*) as new_users
            FROM users 
            WHERE date_entered >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND deleted = 0
        ";
        $stmt = $this->db->getConnection()->prepare($newQuery);
        $stmt->execute();
        $stats['new_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['new_users'];
        
        return $stats;
    }

    /**
     * Get recent system activities
     */
    private function getRecentActivities(): array
    {
        $query = "
            SELECT activity_type, user_id, username, details, created_date
            FROM system_audit_log
            WHERE created_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_date DESC
            LIMIT 20
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get security alerts
     */
    private function getSecurityAlerts(): array
    {
        $alerts = [];
        
        // Failed login attempts
        $failedLogins = $this->getFailedLoginAttempts();
        if ($failedLogins > 10) {
            $alerts[] = [
                'type' => 'security',
                'severity' => 'high',
                'message' => "High number of failed login attempts: {$failedLogins} in the last hour",
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        // Suspicious IP addresses
        $suspiciousIPs = $this->getSuspiciousIPs();
        foreach ($suspiciousIPs as $ip) {
            $alerts[] = [
                'type' => 'security',
                'severity' => 'medium',
                'message' => "Suspicious activity from IP: {$ip['ip_address']} ({$ip['attempt_count']} attempts)",
                'timestamp' => $ip['last_attempt']
            ];
        }
        
        return $alerts;
    }

    /**
     * Get system health metrics
     */
    private function getSystemHealth(): array
    {
        return [
            'database_status' => $this->checkDatabaseHealth(),
            'storage_usage' => $this->getStorageUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'response_time' => $this->getAverageResponseTime(),
            'error_rate' => $this->getErrorRate(),
            'uptime' => $this->getSystemUptime()
        ];
    }

    /**
     * Get quick actions for admin
     */
    private function getQuickActions(): array
    {
        return [
            'create_user' => [
                'label' => 'Create New User',
                'icon' => 'user-plus',
                'url' => '/admin/users/create',
                'color' => 'primary'
            ],
            'manage_roles' => [
                'label' => 'Manage Roles',
                'icon' => 'shield-check',
                'url' => '/admin/roles',
                'color' => 'info'
            ],
            'system_backup' => [
                'label' => 'Create Backup',
                'icon' => 'database',
                'url' => '/admin/backup',
                'color' => 'warning'
            ],
            'view_logs' => [
                'label' => 'View Audit Logs',
                'icon' => 'file-text',
                'url' => '/admin/logs',
                'color' => 'secondary'
            ]
        ];
    }

    /**
     * User management methods
     */
    private function getUsers()
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 25);
        $search = $_GET['search'] ?? '';
        $roleFilter = $_GET['role'] ?? '';
        
        $offset = ($page - 1) * $limit;
        $whereConditions = ['u.deleted = 0'];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(u.user_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email1 LIKE ?)";
            $searchTerm = "%{$search}%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        if (!empty($roleFilter)) {
            $whereConditions[] = "rd.role_name = ?";
            $params[] = $roleFilter;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $query = "
            SELECT u.id, u.user_name, u.first_name, u.last_name, u.email1,
                   u.status, u.date_entered, u.last_login,
                   rd.role_name as primary_role,
                   GROUP_CONCAT(t.name) as territories
            FROM users u
            LEFT JOIN mfg_user_roles ur ON u.id = ur.user_id AND ur.is_primary = 1 AND ur.deleted = 0
            LEFT JOIN mfg_role_definitions rd ON ur.role_id = rd.id
            LEFT JOIN mfg_user_territories ut ON u.id = ut.user_id AND ut.deleted = 0
            LEFT JOIN mfg_territories t ON ut.territory_id = t.id
            WHERE {$whereClause}
            GROUP BY u.id
            ORDER BY u.last_name, u.first_name
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(DISTINCT u.id) as total FROM users u WHERE u.deleted = 0";
        if (!empty($search) || !empty($roleFilter)) {
            // Apply same filters to count query
            $countQuery = "
                SELECT COUNT(DISTINCT u.id) as total
                FROM users u
                LEFT JOIN mfg_user_roles ur ON u.id = ur.user_id AND ur.is_primary = 1 AND ur.deleted = 0
                LEFT JOIN mfg_role_definitions rd ON ur.role_id = rd.id
                WHERE {$whereClause}
            ";
        }
        
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $stmt = $this->db->getConnection()->prepare($countQuery);
        $stmt->execute($countParams);
        $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $this->sendJsonResponse([
            'success' => true,
            'data' => [
                'users' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalUsers / $limit),
                    'total_users' => (int)$totalUsers,
                    'per_page' => $limit
                ]
            ]
        ]);
    }

    /**
     * Create new user
     */
    private function createUser()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $requiredFields = ['username', 'first_name', 'last_name', 'email', 'password', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '{$field}' is required", 400);
                return;
            }
        }
        
        try {
            $this->db->getConnection()->beginTransaction();
            
            // Create user
            $userId = $this->generateUUID();
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            
            $userQuery = "
                INSERT INTO users (id, user_name, first_name, last_name, email1, user_hash, status, date_entered)
                VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW())
            ";
            
            $stmt = $this->db->getConnection()->prepare($userQuery);
            $stmt->execute([
                $userId,
                $input['username'],
                $input['first_name'],
                $input['last_name'],
                $input['email'],
                $hashedPassword
            ]);
            
            // Assign role
            $role = $this->roleManager->getRoleByName($input['role']);
            if ($role) {
                $this->roleManager->assignUserRole($userId, $role['id'], true);
            }
            
            // Assign territories if provided
            if (!empty($input['territories'])) {
                foreach ($input['territories'] as $territoryId) {
                    $this->roleManager->assignUserTerritory($userId, $territoryId, 'write');
                }
            }
            
            $this->db->getConnection()->commit();
            
            $this->logAdminAction('user_created', "Created user: {$input['username']}");
            
            $this->sendJsonResponse([
                'success' => true,
                'data' => ['user_id' => $userId, 'message' => 'User created successfully']
            ]);
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            $this->sendError('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get roles
     */
    private function getRoles()
    {
        $roles = $this->roleManager->getAllRoles();
        
        // Add user counts for each role
        foreach ($roles as &$role) {
            $countQuery = "
                SELECT COUNT(*) as user_count
                FROM mfg_user_roles
                WHERE role_id = ? AND deleted = 0
            ";
            
            $stmt = $this->db->getConnection()->prepare($countQuery);
            $stmt->execute([$role['id']]);
            $role['user_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['user_count'];
        }
        
        $this->sendJsonResponse(['success' => true, 'data' => $roles]);
    }

    /**
     * Get territories
     */
    private function getTerritories()
    {
        $territories = $this->roleManager->getAllTerritories();
        
        // Add user counts and manager info for each territory
        foreach ($territories as &$territory) {
            $userCountQuery = "
                SELECT COUNT(*) as user_count
                FROM mfg_user_territories
                WHERE territory_id = ? AND deleted = 0
            ";
            
            $stmt = $this->db->getConnection()->prepare($userCountQuery);
            $stmt->execute([$territory['id']]);
            $territory['user_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['user_count'];
            
            // Get manager name
            if ($territory['manager_id']) {
                $managerQuery = "
                    SELECT CONCAT(first_name, ' ', last_name) as manager_name
                    FROM users
                    WHERE id = ? AND deleted = 0
                ";
                
                $stmt = $this->db->getConnection()->prepare($managerQuery);
                $stmt->execute([$territory['manager_id']]);
                $manager = $stmt->fetch(PDO::FETCH_ASSOC);
                $territory['manager_name'] = $manager['manager_name'] ?? 'Unassigned';
            } else {
                $territory['manager_name'] = 'Unassigned';
            }
        }
        
        $this->sendJsonResponse(['success' => true, 'data' => $territories]);
    }

    /**
     * Get audit logs
     */
    private function getAuditLogs(int $page, int $limit, string $filter): array
    {
        $offset = ($page - 1) * $limit;
        $whereCondition = '';
        $params = [];
        
        if (!empty($filter)) {
            $whereCondition = "WHERE activity_type LIKE ? OR username LIKE ? OR details LIKE ?";
            $filterTerm = "%{$filter}%";
            $params = [$filterTerm, $filterTerm, $filterTerm];
        }
        
        $query = "
            SELECT activity_type, user_id, username, details, ip_address, user_agent, created_date
            FROM system_audit_log
            {$whereCondition}
            ORDER BY created_date DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM system_audit_log" . ($whereCondition ? " {$whereCondition}" : "");
        $countParams = !empty($filter) ? [$filterTerm, $filterTerm, $filterTerm] : [];
        
        $stmt = $this->db->getConnection()->prepare($countQuery);
        $stmt->execute($countParams);
        $totalLogs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalLogs / $limit),
                'total_logs' => (int)$totalLogs,
                'per_page' => $limit
            ]
        ];
    }

    /**
     * Helper methods
     */
    
    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function logAdminAction(string $action, string $details): void
    {
        $query = "
            INSERT INTO system_audit_log (activity_type, user_id, username, details, ip_address, user_agent, created_date)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([
            $action,
            $this->currentUser['user_id'],
            $this->currentUser['username'],
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }

    private function sendJsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function sendError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['error' => $message, 'code' => $code]);
    }

    // Placeholder methods for system health checks
    private function getActiveSessionCount(): int { return 0; }
    private function getSystemStatus(): string { return 'operational'; }
    private function getFailedLoginAttempts(): int { return 0; }
    private function getSuspiciousIPs(): array { return []; }
    private function checkDatabaseHealth(): string { return 'healthy'; }
    private function getStorageUsage(): array { return ['used' => 0, 'total' => 100]; }
    private function getMemoryUsage(): array { return ['used' => 0, 'total' => 100]; }
    private function getAverageResponseTime(): float { return 0.25; }
    private function getErrorRate(): float { return 0.1; }
    private function getSystemUptime(): string { return '99.9%'; }
    private function getUsageStatistics(): array { return []; }
    private function getPerformanceMetrics(): array { return []; }
    private function getUserActivityStats(): array { return []; }
    private function getFeatureUsageStats(): array { return []; }
    private function getSystemConfig(): array { return []; }
    private function updateSystemConfig(): void {}
    private function getSecuritySettings(): array { return []; }
    private function updateSecuritySettings(): void {}
    private function getPermissions(): void {}
    private function assignPermissions(): void {}
    private function updateUser(): void {}
    private function deleteUser(): void {}
    private function createRole(): void {}
    private function updateRole(): void {}
    private function deleteRole(): void {}
    private function createTerritory(): void {}
    private function updateTerritory(): void {}
    private function deleteTerritory(): void {}
}

// Handle the request
$admin = new AdminPanel();
$admin->display();
