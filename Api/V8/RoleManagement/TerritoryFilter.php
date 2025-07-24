<?php

namespace Api\V8\RoleManagement;

use PDO;
use Exception;

class TerritoryFilter
{
    private PDO $db;
    private RoleDefinitions $roleManager;

    public function __construct(PDO $database, RoleDefinitions $roleManager)
    {
        $this->db = $database;
        $this->roleManager = $roleManager;
    }

    /**
     * Filter data based on user's territory access
     */
    public function filterByUserTerritories(string $userId, array $data, string $territoryField = 'territory_id'): array
    {
        $userRoles = $this->roleManager->getUserRoles($userId);
        $userTerritories = $this->roleManager->getUserTerritories($userId);
        
        // Admin and Manager roles see all data
        foreach ($userRoles['roles'] as $role) {
            if (in_array($role['role_name'], ['admin', 'manager'])) {
                return $data;
            }
        }
        
        // Get allowed territory IDs
        $allowedTerritoryIds = array_column($userTerritories, 'id');
        
        if (empty($allowedTerritoryIds)) {
            return [];
        }
        
        // Filter data by territory access
        return array_filter($data, function($item) use ($allowedTerritoryIds, $territoryField) {
            $itemTerritoryId = $item[$territoryField] ?? null;
            return in_array($itemTerritoryId, $allowedTerritoryIds);
        });
    }

    /**
     * Apply territory filtering to SQL query
     */
    public function applyTerritoryFilterToQuery(string $userId, string $baseQuery, string $territoryField = 'territory_id', string $tableAlias = ''): array
    {
        $userRoles = $this->roleManager->getUserRoles($userId);
        $userTerritories = $this->roleManager->getUserTerritories($userId);
        
        // Admin and Manager roles see all data - no filter needed
        foreach ($userRoles['roles'] as $role) {
            if (in_array($role['role_name'], ['admin', 'manager'])) {
                return ['query' => $baseQuery, 'params' => []];
            }
        }
        
        // Get allowed territory IDs
        $allowedTerritoryIds = array_column($userTerritories, 'id');
        
        if (empty($allowedTerritoryIds)) {
            // No territories assigned - return query that returns no results
            $whereClause = $tableAlias ? "{$tableAlias}.{$territoryField} IS NULL AND 1=0" : "{$territoryField} IS NULL AND 1=0";
            $filteredQuery = $this->addWhereClause($baseQuery, $whereClause);
            return ['query' => $filteredQuery, 'params' => []];
        }
        
        // Create WHERE clause for territory filtering
        $placeholders = str_repeat('?,', count($allowedTerritoryIds) - 1) . '?';
        $territoryFieldName = $tableAlias ? "{$tableAlias}.{$territoryField}" : $territoryField;
        $whereClause = "{$territoryFieldName} IN ({$placeholders})";
        
        $filteredQuery = $this->addWhereClause($baseQuery, $whereClause);
        
        return ['query' => $filteredQuery, 'params' => $allowedTerritoryIds];
    }

    /**
     * Filter products by territory access
     */
    public function filterProductsByTerritory(string $userId, array $additionalFilters = []): array
    {
        $baseQuery = "
            SELECT p.id, p.sku, p.name, p.category, p.base_price, p.territory_id,
                   i.stock_level, i.status as stock_status,
                   t.name as territory_name, t.region
            FROM mfg_products p
            LEFT JOIN mfg_inventory i ON p.id = i.product_id
            LEFT JOIN mfg_territories t ON p.territory_id = t.id
            WHERE p.is_active = 1 AND p.deleted = 0 AND t.deleted = 0
        ";
        
        // Apply additional filters
        foreach ($additionalFilters as $field => $value) {
            if (is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $baseQuery .= " AND p.{$field} IN ({$placeholders})";
            } else {
                $baseQuery .= " AND p.{$field} = ?";
            }
        }
        
        $baseQuery .= " ORDER BY p.category, p.name";
        
        $queryData = $this->applyTerritoryFilterToQuery($userId, $baseQuery, 'territory_id', 'p');
        
        // Combine additional filter params with territory params
        $allParams = [];
        foreach ($additionalFilters as $value) {
            if (is_array($value)) {
                $allParams = array_merge($allParams, $value);
            } else {
                $allParams[] = $value;
            }
        }
        $allParams = array_merge($allParams, $queryData['params']);
        
        $stmt = $this->db->prepare($queryData['query']);
        $stmt->execute($allParams);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filter quotes by territory access
     */
    public function filterQuotesByTerritory(string $userId, array $additionalFilters = []): array
    {
        $baseQuery = "
            SELECT q.id, q.quote_number, q.client_name, q.total_amount, q.status,
                   q.created_date, q.valid_until, a.name as account_name,
                   mcc.territory_id, t.name as territory_name
            FROM mfg_quotes q
            LEFT JOIN accounts a ON q.billing_account_id = a.id
            LEFT JOIN mfg_client_contracts mcc ON a.id = mcc.account_id
            LEFT JOIN mfg_territories t ON mcc.territory_id = t.id
            WHERE q.deleted = 0
        ";
        
        // Apply additional filters
        foreach ($additionalFilters as $field => $value) {
            if ($field === 'status' && is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $baseQuery .= " AND q.status IN ({$placeholders})";
            } elseif ($field === 'date_range') {
                $baseQuery .= " AND q.created_date BETWEEN ? AND ?";
            } else {
                $baseQuery .= " AND q.{$field} = ?";
            }
        }
        
        $baseQuery .= " ORDER BY q.created_date DESC";
        
        $queryData = $this->applyTerritoryFilterToQuery($userId, $baseQuery, 'territory_id', 'mcc');
        
        // Handle additional filter parameters
        $allParams = [];
        foreach ($additionalFilters as $key => $value) {
            if (is_array($value)) {
                $allParams = array_merge($allParams, $value);
            } else {
                $allParams[] = $value;
            }
        }
        $allParams = array_merge($allParams, $queryData['params']);
        
        $stmt = $this->db->prepare($queryData['query']);
        $stmt->execute($allParams);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filter orders by territory access
     */
    public function filterOrdersByTerritory(string $userId, array $additionalFilters = []): array
    {
        $baseQuery = "
            SELECT op.id, op.order_number, op.stage_name, op.total_amount,
                   op.created_date, op.expected_delivery_date,
                   a.name as account_name, mcc.territory_id, t.name as territory_name,
                   u.first_name as rep_first_name, u.last_name as rep_last_name
            FROM mfg_order_pipeline op
            LEFT JOIN accounts a ON op.billing_account_id = a.id
            LEFT JOIN mfg_client_contracts mcc ON a.id = mcc.account_id
            LEFT JOIN mfg_territories t ON mcc.territory_id = t.id
            LEFT JOIN users u ON op.assigned_user_id = u.id
            WHERE op.deleted = 0
        ";
        
        // Apply additional filters
        foreach ($additionalFilters as $field => $value) {
            if ($field === 'stage' && is_array($value)) {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $baseQuery .= " AND op.stage_name IN ({$placeholders})";
            } elseif ($field === 'assigned_user_id') {
                $baseQuery .= " AND op.assigned_user_id = ?";
            } else {
                $baseQuery .= " AND op.{$field} = ?";
            }
        }
        
        $baseQuery .= " ORDER BY op.created_date DESC";
        
        $queryData = $this->applyTerritoryFilterToQuery($userId, $baseQuery, 'territory_id', 'mcc');
        
        // Handle additional filter parameters
        $allParams = [];
        foreach ($additionalFilters as $value) {
            if (is_array($value)) {
                $allParams = array_merge($allParams, $value);
            } else {
                $allParams[] = $value;
            }
        }
        $allParams = array_merge($allParams, $queryData['params']);
        
        $stmt = $this->db->prepare($queryData['query']);
        $stmt->execute($allParams);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filter clients/accounts by territory access
     */
    public function filterClientsByTerritory(string $userId, array $additionalFilters = []): array
    {
        $baseQuery = "
            SELECT a.id, a.name, a.billing_address_city, a.billing_address_state,
                   a.phone_office, a.email1, mcc.pricing_tier, mcc.territory_id,
                   t.name as territory_name, t.region,
                   (SELECT COUNT(*) FROM mfg_quotes q WHERE q.billing_account_id = a.id) as quote_count
            FROM accounts a
            LEFT JOIN mfg_client_contracts mcc ON a.id = mcc.account_id
            LEFT JOIN mfg_territories t ON mcc.territory_id = t.id
            WHERE a.deleted = 0
        ";
        
        // Apply additional filters
        foreach ($additionalFilters as $field => $value) {
            if ($field === 'pricing_tier') {
                $baseQuery .= " AND mcc.pricing_tier = ?";
            } elseif ($field === 'region') {
                $baseQuery .= " AND t.region = ?";
            } else {
                $baseQuery .= " AND a.{$field} = ?";
            }
        }
        
        $baseQuery .= " ORDER BY a.name";
        
        $queryData = $this->applyTerritoryFilterToQuery($userId, $baseQuery, 'territory_id', 'mcc');
        
        // Handle additional filter parameters
        $allParams = [];
        foreach ($additionalFilters as $value) {
            $allParams[] = $value;
        }
        $allParams = array_merge($allParams, $queryData['params']);
        
        $stmt = $this->db->prepare($queryData['query']);
        $stmt->execute($allParams);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get territory-specific analytics for user
     */
    public function getTerritoryAnalytics(string $userId, string $dateRange = '30'): array
    {
        $userTerritories = $this->roleManager->getUserTerritories($userId);
        $userRoles = $this->roleManager->getUserRoles($userId);
        
        $analytics = [];
        
        // If admin or manager, get analytics for all territories
        $isAdminOrManager = false;
        foreach ($userRoles['roles'] as $role) {
            if (in_array($role['role_name'], ['admin', 'manager'])) {
                $isAdminOrManager = true;
                break;
            }
        }
        
        if ($isAdminOrManager) {
            $territoriesToAnalyze = $this->roleManager->getAllTerritories();
        } else {
            $territoriesToAnalyze = $userTerritories;
        }
        
        foreach ($territoriesToAnalyze as $territory) {
            $territoryAnalytics = $this->getTerritorySpecificAnalytics($territory['id'], $dateRange);
            $territoryAnalytics['territory_info'] = $territory;
            $analytics[] = $territoryAnalytics;
        }
        
        return $analytics;
    }

    /**
     * Get analytics for specific territory
     */
    private function getTerritorySpecificAnalytics(string $territoryId, string $dateRange): array
    {
        $dateCondition = "DATE_SUB(NOW(), INTERVAL {$dateRange} DAY)";
        
        // Quote analytics
        $quoteQuery = "
            SELECT COUNT(*) as total_quotes,
                   SUM(CASE WHEN q.status = 'accepted' THEN 1 ELSE 0 END) as accepted_quotes,
                   SUM(CASE WHEN q.status = 'accepted' THEN q.total_amount ELSE 0 END) as accepted_amount
            FROM mfg_quotes q
            JOIN mfg_client_contracts mcc ON q.billing_account_id = mcc.account_id
            WHERE mcc.territory_id = ?
            AND q.created_date >= {$dateCondition}
            AND q.deleted = 0
        ";
        
        $stmt = $this->db->prepare($quoteQuery);
        $stmt->execute([$territoryId]);
        $quoteMetrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Order analytics
        $orderQuery = "
            SELECT COUNT(*) as total_orders,
                   SUM(op.total_amount) as total_order_value,
                   COUNT(CASE WHEN op.stage_name IN ('delivered', 'completed') THEN 1 END) as completed_orders
            FROM mfg_order_pipeline op
            JOIN mfg_client_contracts mcc ON op.billing_account_id = mcc.account_id
            WHERE mcc.territory_id = ?
            AND op.created_date >= {$dateCondition}
            AND op.deleted = 0
        ";
        
        $stmt = $this->db->prepare($orderQuery);
        $stmt->execute([$territoryId]);
        $orderMetrics = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Client count
        $clientQuery = "
            SELECT COUNT(*) as total_clients
            FROM mfg_client_contracts mcc
            JOIN accounts a ON mcc.account_id = a.id
            WHERE mcc.territory_id = ?
            AND a.deleted = 0
        ";
        
        $stmt = $this->db->prepare($clientQuery);
        $stmt->execute([$territoryId]);
        $clientCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_clients'];
        
        // Active sales reps
        $repQuery = "
            SELECT COUNT(DISTINCT ut.user_id) as active_reps
            FROM mfg_user_territories ut
            JOIN mfg_user_roles ur ON ut.user_id = ur.user_id
            JOIN mfg_role_definitions rd ON ur.role_id = rd.id
            WHERE ut.territory_id = ?
            AND rd.role_name = 'sales_rep'
            AND ut.deleted = 0 AND ur.deleted = 0
        ";
        
        $stmt = $this->db->prepare($repQuery);
        $stmt->execute([$territoryId]);
        $activeReps = $stmt->fetch(PDO::FETCH_ASSOC)['active_reps'];
        
        // Calculate conversion rate
        $conversionRate = $quoteMetrics['total_quotes'] > 0 
            ? round(($quoteMetrics['accepted_quotes'] / $quoteMetrics['total_quotes']) * 100, 1) 
            : 0;
        
        return [
            'quotes' => [
                'total' => (int)$quoteMetrics['total_quotes'],
                'accepted' => (int)$quoteMetrics['accepted_quotes'],
                'conversion_rate' => $conversionRate,
                'accepted_value' => (float)$quoteMetrics['accepted_amount']
            ],
            'orders' => [
                'total' => (int)$orderMetrics['total_orders'],
                'completed' => (int)$orderMetrics['completed_orders'],
                'total_value' => (float)$orderMetrics['total_order_value']
            ],
            'clients' => [
                'total' => (int)$clientCount
            ],
            'team' => [
                'active_reps' => (int)$activeReps
            ]
        ];
    }

    /**
     * Check if user has access to specific territory
     */
    public function hasAccessToTerritory(string $userId, string $territoryId): bool
    {
        $userRoles = $this->roleManager->getUserRoles($userId);
        
        // Admin and Manager roles have access to all territories
        foreach ($userRoles['roles'] as $role) {
            if (in_array($role['role_name'], ['admin', 'manager'])) {
                return true;
            }
        }
        
        // Check user's assigned territories
        $userTerritories = $this->roleManager->getUserTerritories($userId);
        $territoryIds = array_column($userTerritories, 'id');
        
        return in_array($territoryId, $territoryIds);
    }

    /**
     * Get territory hierarchy for user (for nested territory access)
     */
    public function getTerritoryHierarchy(string $userId): array
    {
        $userTerritories = $this->roleManager->getUserTerritories($userId);
        $hierarchy = [];
        
        foreach ($userTerritories as $territory) {
            $hierarchy[$territory['region']][] = $territory;
        }
        
        return $hierarchy;
    }

    /**
     * Apply territory filter to search results
     */
    public function filterSearchResults(string $userId, array $searchResults, string $resultType = 'mixed'): array
    {
        $filteredResults = [];
        
        foreach ($searchResults as $result) {
            $hasAccess = false;
            
            // Determine territory access based on result type
            switch ($resultType) {
                case 'products':
                    $hasAccess = $this->hasAccessToTerritory($userId, $result['territory_id'] ?? '');
                    break;
                    
                case 'clients':
                    // Check through client contracts
                    if (isset($result['territory_id'])) {
                        $hasAccess = $this->hasAccessToTerritory($userId, $result['territory_id']);
                    }
                    break;
                    
                case 'orders':
                case 'quotes':
                    // Check through assigned user or territory
                    if (isset($result['assigned_user_id']) && $result['assigned_user_id'] === $userId) {
                        $hasAccess = true;
                    } elseif (isset($result['territory_id'])) {
                        $hasAccess = $this->hasAccessToTerritory($userId, $result['territory_id']);
                    }
                    break;
                    
                default:
                    // Mixed results - check all possible territory fields
                    $territoryFields = ['territory_id', 'client_territory_id', 'product_territory_id'];
                    foreach ($territoryFields as $field) {
                        if (isset($result[$field]) && $this->hasAccessToTerritory($userId, $result[$field])) {
                            $hasAccess = true;
                            break;
                        }
                    }
            }
            
            if ($hasAccess) {
                $filteredResults[] = $result;
            }
        }
        
        return $filteredResults;
    }

    /**
     * Helper method to add WHERE clause to existing query
     */
    private function addWhereClause(string $query, string $whereClause): string
    {
        $query = trim($query);
        
        // Check if query already has WHERE clause
        if (stripos($query, 'WHERE') !== false) {
            // Add AND condition
            return $query . " AND ({$whereClause})";
        } else {
            // Add WHERE clause
            // Find position after FROM clause and any JOINs
            $orderByPos = stripos($query, 'ORDER BY');
            $groupByPos = stripos($query, 'GROUP BY');
            $havingPos = stripos($query, 'HAVING');
            $limitPos = stripos($query, 'LIMIT');
            
            // Find the earliest position of these clauses
            $insertPos = strlen($query);
            if ($orderByPos !== false) $insertPos = min($insertPos, $orderByPos);
            if ($groupByPos !== false) $insertPos = min($insertPos, $groupByPos);
            if ($havingPos !== false) $insertPos = min($insertPos, $havingPos);
            if ($limitPos !== false) $insertPos = min($insertPos, $limitPos);
            
            if ($insertPos < strlen($query)) {
                return substr($query, 0, $insertPos) . " WHERE {$whereClause} " . substr($query, $insertPos);
            } else {
                return $query . " WHERE {$whereClause}";
            }
        }
    }
}
