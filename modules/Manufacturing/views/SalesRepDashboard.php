<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'Api/V8/Middleware/AuthMiddleware.php';
require_once 'include/database/DBManager.php';

use Api\V8\Middleware\AuthMiddleware;

class SalesRepDashboard
{
    private AuthMiddleware $authMiddleware;
    private DBManager $db;
    private array $currentUser;

    public function __construct()
    {
        $this->db = DBManager::getInstance();
        $this->authMiddleware = new AuthMiddleware($this->db->getConnection());
        
        // Authenticate and authorize
        $this->currentUser = $this->authMiddleware->protect('product_catalog', 'READ', '/sales-rep-dashboard');
        
        // Verify user has sales_rep role
        if ($this->currentUser['primary_role'] !== 'sales_rep') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: Sales Rep role required']);
            exit;
        }
    }

    /**
     * Main dashboard display
     */
    public function display()
    {
        $dashboardData = $this->getDashboardData();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $dashboardData,
            'user' => [
                'id' => $this->currentUser['user_id'],
                'username' => $this->currentUser['username'],
                'role' => $this->currentUser['primary_role'],
                'territories' => $this->currentUser['territories']
            ]
        ]);
    }

    /**
     * Get comprehensive dashboard data
     */
    private function getDashboardData(): array
    {
        return [
            'performance_metrics' => $this->getPerformanceMetrics(),
            'recent_quotes' => $this->getRecentQuotes(),
            'assigned_clients' => $this->getAssignedClients(),
            'territory_products' => $this->getTerritoryProducts(),
            'pending_tasks' => $this->getPendingTasks(),
            'notifications' => $this->getNotifications(),
            'quick_actions' => $this->getQuickActions()
        ];
    }

    /**
     * Get sales rep performance metrics
     */
    private function getPerformanceMetrics(): array
    {
        $userId = $this->currentUser['user_id'];
        $currentMonth = date('Y-m-01');
        $lastMonth = date('Y-m-01', strtotime('-1 month'));
        
        // Quotes this month
        $quotesQuery = "
            SELECT COUNT(*) as total_quotes,
                   SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted_quotes,
                   SUM(total_amount) as total_quoted,
                   SUM(CASE WHEN status = 'accepted' THEN total_amount ELSE 0 END) as accepted_amount
            FROM mfg_quotes 
            WHERE assigned_user_id = ? 
            AND created_date >= ? 
            AND deleted = 0
        ";
        
        $stmt = $this->db->getConnection()->prepare($quotesQuery);
        $stmt->execute([$userId, $currentMonth]);
        $currentMetrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Previous month for comparison
        $stmt->execute([$userId, $lastMonth]);
        $previousMetrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate conversion rate
        $conversionRate = $currentMetrics['total_quotes'] > 0 
            ? ($currentMetrics['accepted_quotes'] / $currentMetrics['total_quotes']) * 100 
            : 0;
        
        return [
            'quotes_this_month' => (int)$currentMetrics['total_quotes'],
            'accepted_quotes' => (int)$currentMetrics['accepted_quotes'],
            'conversion_rate' => round($conversionRate, 1),
            'total_quoted' => (float)$currentMetrics['total_quoted'],
            'accepted_amount' => (float)$currentMetrics['accepted_amount'],
            'previous_month_quotes' => (int)$previousMetrics['total_quotes'],
            'trend' => $this->calculateTrend($currentMetrics['total_quotes'], $previousMetrics['total_quotes'])
        ];
    }

    /**
     * Get recent quotes for the sales rep
     */
    private function getRecentQuotes(): array
    {
        $userId = $this->currentUser['user_id'];
        
        $query = "
            SELECT q.id, q.quote_number, q.client_name, q.total_amount, q.status,
                   q.created_date, q.valid_until, a.name as account_name
            FROM mfg_quotes q
            LEFT JOIN accounts a ON q.billing_account_id = a.id
            WHERE q.assigned_user_id = ? AND q.deleted = 0
            ORDER BY q.created_date DESC
            LIMIT 10
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add status badge and urgency
        foreach ($quotes as &$quote) {
            $quote['status_badge'] = $this->getStatusBadge($quote['status']);
            $quote['is_urgent'] = $this->isQuoteUrgent($quote['valid_until']);
            $quote['days_until_expiry'] = $this->getDaysUntilExpiry($quote['valid_until']);
        }
        
        return $quotes;
    }

    /**
     * Get clients assigned to this sales rep
     */
    private function getAssignedClients(): array
    {
        $userId = $this->currentUser['user_id'];
        
        // Filter by territory access
        $territoryIds = array_column($this->currentUser['territories'], 'id');
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        $query = "
            SELECT a.id, a.name, a.billing_address_city, a.billing_address_state,
                   a.phone_office, a.email1, mcc.pricing_tier, mcc.contract_value,
                   (SELECT COUNT(*) FROM mfg_quotes q WHERE q.billing_account_id = a.id AND q.assigned_user_id = ?) as total_quotes,
                   (SELECT MAX(created_date) FROM mfg_quotes q WHERE q.billing_account_id = a.id AND q.assigned_user_id = ?) as last_quote_date
            FROM accounts a
            LEFT JOIN mfg_client_contracts mcc ON a.id = mcc.account_id
            WHERE a.assigned_user_id = ? 
            AND a.deleted = 0
            AND (mcc.territory_id IN ({$placeholders}) OR mcc.territory_id IS NULL)
            ORDER BY mcc.contract_value DESC, a.name ASC
            LIMIT 20
        ";
        
        $params = array_merge([$userId, $userId, $userId], $territoryIds);
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get products available in sales rep's territories
     */
    private function getTerritoryProducts(): array
    {
        $territoryIds = array_column($this->currentUser['territories'], 'id');
        
        if (empty($territoryIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        $query = "
            SELECT p.id, p.sku, p.name, p.category, p.base_price,
                   i.stock_level, i.status as stock_status,
                   pt.tier_price, pt.tier_name
            FROM mfg_products p
            LEFT JOIN mfg_inventory i ON p.id = i.product_id
            LEFT JOIN mfg_pricing_tiers pt ON p.id = pt.product_id AND pt.tier_name = 'wholesale'
            WHERE p.territory_id IN ({$placeholders})
            AND p.is_active = 1 AND p.deleted = 0
            ORDER BY p.category, p.name
            LIMIT 50
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($territoryIds);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add product status indicators
        foreach ($products as &$product) {
            $product['stock_indicator'] = $this->getStockIndicator($product['stock_level']);
            $product['price_display'] = $product['tier_price'] ?: $product['base_price'];
        }
        
        return $products;
    }

    /**
     * Get pending tasks for sales rep
     */
    private function getPendingTasks(): array
    {
        $userId = $this->current $this->currentUser['user_id'];
        
        return [
            'follow_up_quotes' => $this->getFollowUpQuotes($userId),
            'expiring_quotes' => $this->getExpiringQuotes($userId),
            'new_leads' => $this->getNewLeads($userId),
            'overdue_activities' => $this->getOverdueActivities($userId)
        ];
    }

    /**
     * Get notifications for sales rep
     */
    private function getNotifications(): array
    {
        $userId = $this->currentUser['user_id'];
        
        $notifications = [];
        
        // Quote status changes
        $quoteNotifications = $this->getQuoteNotifications($userId);
        
        // Inventory alerts
        $inventoryNotifications = $this->getInventoryNotifications();
        
        // Client activity
        $clientNotifications = $this->getClientNotifications($userId);
        
        return array_merge($quoteNotifications, $inventoryNotifications, $clientNotifications);
    }

    /**
     * Get quick action items
     */
    private function getQuickActions(): array
    {
        return [
            'create_quote' => [
                'label' => 'Create New Quote',
                'icon' => 'plus-circle',
                'url' => '/quote-builder',
                'color' => 'primary'
            ],
            'product_catalog' => [
                'label' => 'Browse Products',
                'icon' => 'grid-3x3',
                'url' => '/product-catalog',
                'color' => 'info'
            ],
            'client_list' => [
                'label' => 'My Clients',
                'icon' => 'people',
                'url' => '/clients',
                'color' => 'success'
            ],
            'order_pipeline' => [
                'label' => 'Order Pipeline',
                'icon' => 'kanban',
                'url' => '/pipeline',
                'color' => 'warning'
            ]
        ];
    }

    /**
     * Calculate performance trend
     */
    private function calculateTrend(int $current, int $previous): array
    {
        if ($previous == 0) {
            return ['direction' => 'neutral', 'percentage' => 0];
        }
        
        $change = (($current - $previous) / $previous) * 100;
        
        return [
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
            'percentage' => round(abs($change), 1)
        ];
    }

    /**
     * Get status badge configuration
     */
    private function getStatusBadge(string $status): array
    {
        $badges = [
            'draft' => ['color' => 'secondary', 'text' => 'Draft'],
            'sent' => ['color' => 'info', 'text' => 'Sent'],
            'viewed' => ['color' => 'primary', 'text' => 'Viewed'],
            'accepted' => ['color' => 'success', 'text' => 'Accepted'],
            'rejected' => ['color' => 'danger', 'text' => 'Rejected'],
            'expired' => ['color' => 'dark', 'text' => 'Expired']
        ];
        
        return $badges[$status] ?? ['color' => 'secondary', 'text' => ucfirst($status)];
    }

    /**
     * Check if quote is urgent (expires within 3 days)
     */
    private function isQuoteUrgent(string $validUntil): bool
    {
        $expiry = strtotime($validUntil);
        $threeDaysFromNow = strtotime('+3 days');
        
        return $expiry <= $threeDaysFromNow && $expiry > time();
    }

    /**
     * Get days until quote expiry
     */
    private function getDaysUntilExpiry(string $validUntil): int
    {
        $expiry = strtotime($validUntil);
        $now = time();
        
        return max(0, floor(($expiry - $now) / 86400));
    }

    /**
     * Get stock level indicator
     */
    private function getStockIndicator(int $stockLevel): array
    {
        if ($stockLevel <= 0) {
            return ['status' => 'out_of_stock', 'color' => 'danger', 'text' => 'Out of Stock'];
        } elseif ($stockLevel <= 10) {
            return ['status' => 'low_stock', 'color' => 'warning', 'text' => 'Low Stock'];
        } else {
            return ['status' => 'in_stock', 'color' => 'success', 'text' => 'In Stock'];
        }
    }

    /**
     * Get quotes needing follow-up
     */
    private function getFollowUpQuotes(string $userId): array
    {
        $query = "
            SELECT id, quote_number, client_name, created_date, status
            FROM mfg_quotes 
            WHERE assigned_user_id = ? 
            AND status IN ('sent', 'viewed') 
            AND created_date < DATE_SUB(NOW(), INTERVAL 3 DAY)
            AND deleted = 0
            ORDER BY created_date ASC
            LIMIT 5
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get quotes expiring soon
     */
    private function getExpiringQuotes(string $userId): array
    {
        $query = "
            SELECT id, quote_number, client_name, valid_until, status
            FROM mfg_quotes 
            WHERE assigned_user_id = ? 
            AND status IN ('sent', 'viewed') 
            AND valid_until BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
            AND deleted = 0
            ORDER BY valid_until ASC
            LIMIT 5
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get new leads assigned to sales rep
     */
    private function getNewLeads(string $userId): array
    {
        $query = "
            SELECT id, name, phone_office, email1, lead_source, created_date
            FROM leads 
            WHERE assigned_user_id = ? 
            AND status = 'New'
            AND deleted = 0
            ORDER BY created_date DESC
            LIMIT 5
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get overdue activities
     */
    private function getOverdueActivities(string $userId): array
    {
        $query = "
            SELECT id, name, activity_type, due_date, related_type, related_id
            FROM activities 
            WHERE assigned_user_id = ? 
            AND status != 'completed'
            AND due_date < NOW()
            AND deleted = 0
            ORDER BY due_date ASC
            LIMIT 5
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get quote-related notifications
     */
    private function getQuoteNotifications(string $userId): array
    {
        // This would typically come from a notifications table
        // For now, return recent quote status changes
        return [];
    }

    /**
     * Get inventory notifications for products in user's territory
     */
    private function getInventoryNotifications(): array
    {
        $territoryIds = array_column($this->currentUser['territories'], 'id');
        
        if (empty($territoryIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        $query = "
            SELECT p.name, i.stock_level, i.reorder_point
            FROM mfg_products p
            JOIN mfg_inventory i ON p.id = i.product_id
            WHERE p.territory_id IN ({$placeholders})
            AND i.stock_level <= i.reorder_point
            AND p.is_active = 1 AND p.deleted = 0
            LIMIT 5
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($territoryIds);
        $lowStock = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $notifications = [];
        foreach ($lowStock as $product) {
            $notifications[] = [
                'type' => 'inventory_alert',
                'message' => "Low stock alert: {$product['name']} ({$product['stock_level']} remaining)",
                'priority' => 'medium',
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return $notifications;
    }

    /**
     * Get client activity notifications
     */
    private function getClientNotifications(string $userId): array
    {
        // This would track client portal activity, new orders, etc.
        // For now, return empty array
        return [];
    }
}

// Handle the request
if (isset($_GET['action']) && $_GET['action'] === 'dashboard_data') {
    $dashboard = new SalesRepDashboard();
    $dashboard->display();
} else {
    // Return HTML template for the dashboard
    include 'templates/sales_rep_dashboard.html';
}
