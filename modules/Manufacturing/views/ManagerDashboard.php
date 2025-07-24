<?php

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

require_once 'Api/V8/Middleware/AuthMiddleware.php';
require_once 'include/database/DBManager.php';

use Api\V8\Middleware\AuthMiddleware;

class ManagerDashboard
{
    private AuthMiddleware $authMiddleware;
    private DBManager $db;
    private array $currentUser;

    public function __construct()
    {
        $this->db = DBManager::getInstance();
        $this->authMiddleware = new AuthMiddleware($this->db->getConnection());
        
        // Authenticate and authorize
        $this->currentUser = $this->authMiddleware->protect('team_analytics', 'READ', '/manager-dashboard');
        
        // Verify user has manager role
        if ($this->currentUser['primary_role'] !== 'manager') {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied: Manager role required']);
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
     * Get comprehensive manager dashboard data
     */
    private function getDashboardData(): array
    {
        return [
            'team_performance' => $this->getTeamPerformance(),
            'sales_analytics' => $this->getSalesAnalytics(),
            'pipeline_overview' => $this->getPipelineOverview(),
            'inventory_alerts' => $this->getInventoryAlerts(),
            'territory_analysis' => $this->getTerritoryAnalysis(),
            'team_activities' => $this->getTeamActivities(),
            'performance_trends' => $this->getPerformanceTrends(),
            'action_items' => $this->getManagerActionItems()
        ];
    }

    /**
     * Get team performance metrics
     */
    private function getTeamPerformance(): array
    {
        $managedTerritories = $this->getManagedTerritories();
        $territoryIds = array_column($managedTerritories, 'id');
        
        if (empty($territoryIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        $currentMonth = date('Y-m-01');
        
        // Get team members in managed territories
        $teamQuery = "
            SELECT u.id, u.user_name, u.first_name, u.last_name,
                   COUNT(DISTINCT q.id) as total_quotes,
                   SUM(CASE WHEN q.status = 'accepted' THEN 1 ELSE 0 END) as accepted_quotes,
                   SUM(q.total_amount) as total_quoted,
                   SUM(CASE WHEN q.status = 'accepted' THEN q.total_amount ELSE 0 END) as accepted_amount,
                   ut.territory_id
            FROM users u
            JOIN mfg_user_territories ut ON u.id = ut.user_id
            JOIN mfg_user_roles ur ON u.id = ur.user_id
            JOIN mfg_role_definitions rd ON ur.role_id = rd.id
            LEFT JOIN mfg_quotes q ON u.id = q.assigned_user_id AND q.created_date >= ? AND q.deleted = 0
            WHERE ut.territory_id IN ({$placeholders})
            AND rd.role_name = 'sales_rep'
            AND ut.deleted = 0 AND ur.deleted = 0
            GROUP BY u.id, ut.territory_id
            ORDER BY accepted_amount DESC
        ";
        
        $params = array_merge([$currentMonth], $territoryIds);
        $stmt = $this->db->getConnection()->prepare($teamQuery);
        $stmt->execute($params);
        $teamMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate team totals and averages
        $totalQuotes = array_sum(array_column($teamMembers, 'total_quotes'));
        $totalAccepted = array_sum(array_column($teamMembers, 'accepted_quotes'));
        $totalAmount = array_sum(array_column($teamMembers, 'accepted_amount'));
        $teamSize = count($teamMembers);
        
        foreach ($teamMembers as &$member) {
            $member['conversion_rate'] = $member['total_quotes'] > 0 
                ? round(($member['accepted_quotes'] / $member['total_quotes']) * 100, 1) 
                : 0;
            $member['performance_score'] = $this->calculatePerformanceScore($member);
        }
        
        return [
            'team_members' => $teamMembers,
            'team_totals' => [
                'total_quotes' => $totalQuotes,
                'accepted_quotes' => $totalAccepted,
                'team_conversion_rate' => $totalQuotes > 0 ? round(($totalAccepted / $totalQuotes) * 100, 1) : 0,
                'total_revenue' => $totalAmount,
                'average_deal_size' => $totalAccepted > 0 ? round($totalAmount / $totalAccepted, 2) : 0,
                'team_size' => $teamSize
            ]
        ];
    }

    /**
     * Get sales analytics data
     */
    private function getSalesAnalytics(): array
    {
        $managedTerritories = $this->getManagedTerritories();
        $territoryIds = array_column($managedTerritories, 'id');
        
        if (empty($territoryIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        // Monthly sales trend (last 12 months)
        $trendQuery = "
            SELECT DATE_FORMAT(q.created_date, '%Y-%m') as month,
                   COUNT(*) as quote_count,
                   SUM(CASE WHEN q.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
                   SUM(CASE WHEN q.status = 'accepted' THEN q.total_amount ELSE 0 END) as revenue
            FROM mfg_quotes q
            JOIN mfg_user_territories ut ON q.assigned_user_id = ut.user_id
            WHERE ut.territory_id IN ({$placeholders})
            AND q.created_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            AND q.deleted = 0 AND ut.deleted = 0
            GROUP BY DATE_FORMAT(q.created_date, '%Y-%m')
            ORDER BY month ASC
        ";
        
        $stmt = $this->db->getConnection()->prepare($trendQuery);
        $stmt->execute($territoryIds);
        $monthlyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Product category performance
        $categoryQuery = "
            SELECT p.category,
                   COUNT(DISTINCT qi.quote_id) as quotes_with_category,
                   SUM(qi.quantity * qi.unit_price) as category_revenue,
                   AVG(qi.unit_price) as avg_unit_price
            FROM mfg_quote_items qi
            JOIN mfg_products p ON qi.product_id = p.id
            JOIN mfg_quotes q ON qi.quote_id = q.id
            JOIN mfg_user_territories ut ON q.assigned_user_id = ut.user_id
            WHERE ut.territory_id IN ({$placeholders})
            AND q.created_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            AND q.deleted = 0 AND qi.deleted = 0
            GROUP BY p.category
            ORDER BY category_revenue DESC
        ";
        
        $stmt = $this->db->getConnection()->prepare($categoryQuery);
        $stmt->execute($territoryIds);
        $categoryAnalysis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'monthly_trend' => $monthlyTrend,
            'category_analysis' => $categoryAnalysis,
            'forecasting' => $this->generateSalesForecast($monthlyTrend)
        ];
    }

    /**
     * Get pipeline overview for managed territories
     */
    private function getPipelineOverview(): array
    {
        $managedTerritories = $this->getManagedTerritories();
        $territoryIds = array_column($managedTerritories, 'id');
        
        if (empty($territoryIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        // Pipeline stages analysis
        $pipelineQuery = "
            SELECT op.stage_name,
                   COUNT(*) as order_count,
                   SUM(op.total_amount) as stage_value,
                   AVG(DATEDIFF(NOW(), op.created_date)) as avg_days_in_stage
            FROM mfg_order_pipeline op
            JOIN mfg_user_territories ut ON op.assigned_user_id = ut.user_id
            WHERE ut.territory_id IN ({$placeholders})
            AND op.deleted = 0 AND ut.deleted = 0
            GROUP BY op.stage_name
            ORDER BY 
                CASE op.stage_name
                    WHEN 'quote_sent' THEN 1
                    WHEN 'quote_accepted' THEN 2
                    WHEN 'order_confirmed' THEN 3
                    WHEN 'in_production' THEN 4
                    WHEN 'ready_to_ship' THEN 5
                    WHEN 'shipped' THEN 6
                    WHEN 'delivered' THEN 7
                    ELSE 8
                END
        ";
        
        $stmt = $this->db->getConnection()->prepare($pipelineQuery);
        $stmt->execute($territoryIds);
        $pipelineStages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Bottleneck analysis
        $bottlenecks = $this->identifyBottlenecks($pipelineStages);
        
        // Recent pipeline activities
        $recentActivities = $this->getRecentPipelineActivities($territoryIds);
        
        return [
            'pipeline_stages' => $pipelineStages,
            'bottlenecks' => $bottlenecks,
            'recent_activities' => $recentActivities,
            'total_pipeline_value' => array_sum(array_column($pipelineStages, 'stage_value'))
        ];
    }

    /**
     * Get inventory alerts for managed territories
     */
    private function getInventoryAlerts(): array
    {
        $managedTerritories = $this->getManagedTerritories();
        $territoryIds = array_column($managedTerritories, 'id');
        
        if (empty($territoryIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        // Low stock alerts
        $lowStockQuery = "
            SELECT p.name, p.sku, p.category, i.stock_level, i.reorder_point,
                   i.last_updated, t.name as territory_name
            FROM mfg_products p
            JOIN mfg_inventory i ON p.id = i.product_id
            JOIN mfg_territories t ON p.territory_id = t.id
            WHERE p.territory_id IN ({$placeholders})
            AND i.stock_level <= i.reorder_point
            AND p.is_active = 1 AND p.deleted = 0
            ORDER BY (i.stock_level / i.reorder_point) ASC, p.category
        ";
        
        $stmt = $this->db->getConnection()->prepare($lowStockQuery);
        $stmt->execute($territoryIds);
        $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Overstock items
        $overstockQuery = "
            SELECT p.name, p.sku, p.category, i.stock_level, i.max_stock_level,
                   i.last_updated, t.name as territory_name
            FROM mfg_products p
            JOIN mfg_inventory i ON p.id = i.product_id
            JOIN mfg_territories t ON p.territory_id = t.id
            WHERE p.territory_id IN ({$placeholders})
            AND i.max_stock_level > 0 
            AND i.stock_level > i.max_stock_level
            AND p.is_active = 1 AND p.deleted = 0
            ORDER BY (i.stock_level / i.max_stock_level) DESC
        ";
        
        $stmt = $this->db->getConnection()->prepare($overstockQuery);
        $stmt->execute($territoryIds);
        $overstockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'low_stock' => $lowStockItems,
            'overstock' => $overstockItems,
            'total_alerts' => count($lowStockItems) + count($overstockItems)
        ];
    }

    /**
     * Get territory analysis
     */
    private function getTerritoryAnalysis(): array
    {
        $managedTerritories = $this->getManagedTerritories();
        $territoryAnalysis = [];
        
        foreach ($managedTerritories as $territory) {
            $analysis = $this->analyzeTerritoryPerformance($territory['id']);
            $territoryAnalysis[] = array_merge($territory, $analysis);
        }
        
        return $territoryAnalysis;
    }

    /**
     * Get team activities and tasks
     */
    private function getTeamActivities(): array
    {
        $managedTerritories = $this->getManagedTerritories();
        $territoryIds = array_column($managedTerritories, 'id');
        
        if (empty($territoryIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        // Recent team activities
        $activitiesQuery = "
            SELECT a.id, a.name, a.activity_type, a.due_date, a.status,
                   u.first_name, u.last_name, u.user_name,
                   a.created_date, a.priority
            FROM activities a
            JOIN users u ON a.assigned_user_id = u.id
            JOIN mfg_user_territories ut ON u.id = ut.user_id
            WHERE ut.territory_id IN ({$placeholders})
            AND a.deleted = 0 AND ut.deleted = 0
            ORDER BY a.created_date DESC
            LIMIT 20
        ";
        
        $stmt = $this->db->getConnection()->prepare($activitiesQuery);
        $stmt->execute($territoryIds);
        $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Overdue tasks
        $overdueQuery = "
            SELECT a.id, a.name, a.activity_type, a.due_date,
                   u.first_name, u.last_name, u.user_name,
                   DATEDIFF(NOW(), a.due_date) as days_overdue
            FROM activities a
            JOIN users u ON a.assigned_user_id = u.id
            JOIN mfg_user_territories ut ON u.id = ut.user_id
            WHERE ut.territory_id IN ({$placeholders})
            AND a.status != 'completed'
            AND a.due_date < NOW()
            AND a.deleted = 0 AND ut.deleted = 0
            ORDER BY a.due_date ASC
        ";
        
        $stmt = $this->db->getConnection()->prepare($overdueQuery);
        $stmt->execute($territoryIds);
        $overdueTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'recent_activities' => $recentActivities,
            'overdue_tasks' => $overdueTasks,
            'task_summary' => $this->getTaskSummary($territoryIds)
        ];
    }

    /**
     * Get performance trends analysis
     */
    private function getPerformanceTrends(): array
    {
        $managedTerritories = $this->getManagedTerritories();
        $territoryIds = array_column($managedTerritories, 'id');
        
        if (empty($territoryIds)) {
            return [];
        }
        
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        // Weekly performance trend
        $weeklyQuery = "
            SELECT YEARWEEK(q.created_date, 1) as year_week,
                   COUNT(*) as quote_count,
                   SUM(CASE WHEN q.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
                   SUM(CASE WHEN q.status = 'accepted' THEN q.total_amount ELSE 0 END) as revenue
            FROM mfg_quotes q
            JOIN mfg_user_territories ut ON q.assigned_user_id = ut.user_id
            WHERE ut.territory_id IN ({$placeholders})
            AND q.created_date >= DATE_SUB(NOW(), INTERVAL 12 WEEK)
            AND q.deleted = 0 AND ut.deleted = 0
            GROUP BY YEARWEEK(q.created_date, 1)
            ORDER BY year_week ASC
        ";
        
        $stmt = $this->db->getConnection()->prepare($weeklyQuery);
        $stmt->execute($territoryIds);
        $weeklyTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'weekly_trend' => $weeklyTrend,
            'trend_analysis' => $this->analyzeTrends($weeklyTrend)
        ];
    }

    /**
     * Get manager action items
     */
    private function getManagerActionItems(): array
    {
        return [
            'team_reviews_due' => $this->getTeamReviewsDue(),
            'approval_requests' => $this->getApprovalRequests(),
            'territory_assignments' => $this->getPendingTerritoryAssignments(),
            'performance_alerts' => $this->getPerformanceAlerts()
        ];
    }

    /**
     * Helper methods
     */
    
    private function getManagedTerritories(): array
    {
        $userId = $this->currentUser['user_id'];
        
        $query = "
            SELECT t.id, t.name, t.region
            FROM mfg_territories t
            WHERE t.manager_id = ? OR ? IN (
                SELECT ur.user_id 
                FROM mfg_user_roles ur 
                JOIN mfg_role_definitions rd ON ur.role_id = rd.id 
                WHERE rd.role_name = 'admin'
            )
            AND t.deleted = 0
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$userId, $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function calculatePerformanceScore(array $member): int
    {
        $quoteWeight = 0.3;
        $conversionWeight = 0.4;
        $revenueWeight = 0.3;
        
        $quoteScore = min(100, ($member['total_quotes'] / 10) * 100);
        $conversionScore = $member['conversion_rate'];
        $revenueScore = min(100, ($member['accepted_amount'] / 50000) * 100);
        
        return round(
            ($quoteScore * $quoteWeight) + 
            ($conversionScore * $conversionWeight) + 
            ($revenueScore * $revenueWeight)
        );
    }

    private function generateSalesForecast(array $monthlyTrend): array
    {
        if (count($monthlyTrend) < 3) {
            return ['forecast' => 'Insufficient data'];
        }
        
        $revenues = array_column($monthlyTrend, 'revenue');
        $recentRevenues = array_slice($revenues, -3);
        $avgGrowth = 0;
        
        for ($i = 1; $i < count($recentRevenues); $i++) {
            if ($recentRevenues[$i-1] > 0) {
                $growth = (($recentRevenues[$i] - $recentRevenues[$i-1]) / $recentRevenues[$i-1]) * 100;
                $avgGrowth += $growth;
            }
        }
        
        $avgGrowth = $avgGrowth / (count($recentRevenues) - 1);
        $lastRevenue = end($revenues);
        $forecastRevenue = $lastRevenue * (1 + ($avgGrowth / 100));
        
        return [
            'next_month_forecast' => round($forecastRevenue, 2),
            'growth_rate' => round($avgGrowth, 1),
            'confidence' => 'Medium'
        ];
    }

    private function identifyBottlenecks(array $pipelineStages): array
    {
        $bottlenecks = [];
        
        foreach ($pipelineStages as $stage) {
            if ($stage['avg_days_in_stage'] > 7) {
                $bottlenecks[] = [
                    'stage' => $stage['stage_name'],
                    'avg_days' => round($stage['avg_days_in_stage'], 1),
                    'severity' => $stage['avg_days_in_stage'] > 14 ? 'high' : 'medium'
                ];
            }
        }
        
        return $bottlenecks;
    }

    private function getRecentPipelineActivities(array $territoryIds): array
    {
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        $query = "
            SELECT op.id, op.order_number, op.stage_name, op.previous_stage,
                   op.created_date, u.first_name, u.last_name
            FROM mfg_order_pipeline op
            JOIN mfg_user_territories ut ON op.assigned_user_id = ut.user_id
            JOIN users u ON op.assigned_user_id = u.id
            WHERE ut.territory_id IN ({$placeholders})
            AND op.created_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND op.deleted = 0
            ORDER BY op.created_date DESC
            LIMIT 15
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($territoryIds);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function analyzeTerritoryPerformance(string $territoryId): array
    {
        $currentMonth = date('Y-m-01');
        
        $query = "
            SELECT COUNT(DISTINCT q.assigned_user_id) as active_reps,
                   COUNT(*) as total_quotes,
                   SUM(CASE WHEN q.status = 'accepted' THEN 1 ELSE 0 END) as accepted_quotes,
                   SUM(CASE WHEN q.status = 'accepted' THEN q.total_amount ELSE 0 END) as revenue
            FROM mfg_quotes q
            JOIN mfg_user_territories ut ON q.assigned_user_id = ut.user_id
            WHERE ut.territory_id = ?
            AND q.created_date >= ?
            AND q.deleted = 0 AND ut.deleted = 0
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute([$territoryId, $currentMonth]);
        $performance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $conversionRate = $performance['total_quotes'] > 0 
            ? round(($performance['accepted_quotes'] / $performance['total_quotes']) * 100, 1) 
            : 0;
        
        return [
            'active_reps' => (int)$performance['active_reps'],
            'total_quotes' => (int)$performance['total_quotes'],
            'conversion_rate' => $conversionRate,
            'revenue' => (float)$performance['revenue'],
            'performance_grade' => $this->calculateTerritoryGrade($conversionRate, $performance['revenue'])
        ];
    }

    private function calculateTerritoryGrade(float $conversionRate, float $revenue): string
    {
        $score = ($conversionRate * 0.6) + (min(100, $revenue / 1000) * 0.4);
        
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }

    private function getTaskSummary(array $territoryIds): array
    {
        $placeholders = str_repeat('?,', count($territoryIds) - 1) . '?';
        
        $query = "
            SELECT a.status, COUNT(*) as count
            FROM activities a
            JOIN mfg_user_territories ut ON a.assigned_user_id = ut.user_id
            WHERE ut.territory_id IN ({$placeholders})
            AND a.deleted = 0 AND ut.deleted = 0
            GROUP BY a.status
        ";
        
        $stmt = $this->db->getConnection()->prepare($query);
        $stmt->execute($territoryIds);
        $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $summary = [];
        foreach ($statusCounts as $status) {
            $summary[$status['status']] = (int)$status['count'];
        }
        
        return $summary;
    }

    private function analyzeTrends(array $weeklyTrend): array
    {
        if (count($weeklyTrend) < 4) {
            return ['trend' => 'insufficient_data'];
        }
        
        $revenues = array_column($weeklyTrend, 'revenue');
        $recentAvg = array_sum(array_slice($revenues, -4)) / 4;
        $previousAvg = array_sum(array_slice($revenues, -8, 4)) / 4;
        
        $trendDirection = $recentAvg > $previousAvg ? 'up' : ($recentAvg < $previousAvg ? 'down' : 'stable');
        $trendStrength = $previousAvg > 0 ? abs(($recentAvg - $previousAvg) / $previousAvg * 100) : 0;
        
        return [
            'direction' => $trendDirection,
            'strength' => round($trendStrength, 1),
            'recent_avg' => round($recentAvg, 2),
            'previous_avg' => round($previousAvg, 2)
        ];
    }

    private function getTeamReviewsDue(): array
    {
        // Return placeholder data for team reviews
        return [];
    }

    private function getApprovalRequests(): array
    {
        // Return placeholder data for approval requests
        return [];
    }

    private function getPendingTerritoryAssignments(): array
    {
        // Return placeholder data for territory assignments
        return [];
    }

    private function getPerformanceAlerts(): array
    {
        // Return placeholder data for performance alerts
        return [];
    }
}

// Handle the request
if (isset($_GET['action']) && $_GET['action'] === 'dashboard_data') {
    $dashboard = new ManagerDashboard();
    $dashboard->display();
} else {
    // Return HTML template for the dashboard
    include 'templates/manager_dashboard.html';
}
