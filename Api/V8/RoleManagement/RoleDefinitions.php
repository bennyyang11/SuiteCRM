<?php

namespace Api\V8\RoleManagement;

use PDO;
use Exception;

class RoleDefinitions
{
    private PDO $db;

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    /**
     * Get all role definitions
     */
    public function getAllRoles(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, role_name, role_description, permissions, hierarchy_level, is_active 
             FROM mfg_role_definitions 
             WHERE deleted = 0 AND is_active = 1 
             ORDER BY hierarchy_level DESC"
        );
        
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON permissions
        foreach ($roles as &$role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?? [];
        }
        
        return $roles;
    }

    /**
     * Get role by ID
     */
    public function getRoleById(string $roleId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, role_name, role_description, permissions, hierarchy_level, is_active 
             FROM mfg_role_definitions 
             WHERE id = ? AND deleted = 0"
        );
        
        $stmt->execute([$roleId]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?? [];
        }
        
        return $role ?: null;
    }

    /**
     * Get role by name
     */
    public function getRoleByName(string $roleName): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, role_name, role_description, permissions, hierarchy_level, is_active 
             FROM mfg_role_definitions 
             WHERE role_name = ? AND deleted = 0"
        );
        
        $stmt->execute([$roleName]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($role) {
            $role['permissions'] = json_decode($role['permissions'], true) ?? [];
        }
        
        return $role ?: null;
    }

    /**
     * Get user roles and permissions
     */
    public function getUserRoles(string $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT rd.id, rd.role_name, rd.role_description, rd.permissions, rd.hierarchy_level,
                    ur.is_primary, ur.effective_date, ur.expiry_date
             FROM mfg_user_roles ur
             JOIN mfg_role_definitions rd ON ur.role_id = rd.id
             WHERE ur.user_id = ? AND ur.deleted = 0 AND rd.deleted = 0 AND rd.is_active = 1
             AND (ur.expiry_date IS NULL OR ur.expiry_date > NOW())
             ORDER BY rd.hierarchy_level DESC, ur.is_primary DESC"
        );
        
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON permissions and merge
        $mergedPermissions = [];
        $primaryRole = null;
        
        foreach ($roles as &$role) {
            $permissions = json_decode($role['permissions'], true) ?? [];
            $role['permissions'] = $permissions;
            
            // Merge permissions (higher hierarchy takes precedence)
            foreach ($permissions as $module => $actions) {
                if (!isset($mergedPermissions[$module])) {
                    $mergedPermissions[$module] = [];
                }
                $mergedPermissions[$module] = array_unique(array_merge($mergedPermissions[$module], $actions));
            }
            
            // Set primary role
            if ($role['is_primary'] == 1) {
                $primaryRole = $role['role_name'];
            }
        }
        
        return [
            'roles' => $roles,
            'primary_role' => $primaryRole,
            'merged_permissions' => $mergedPermissions
        ];
    }

    /**
     * Assign role to user
     */
    public function assignUserRole(string $userId, string $roleId, bool $isPrimary = false, ?string $expiryDate = null): bool
    {
        try {
            $this->db->beginTransaction();
            
            // If setting as primary, update existing primary roles
            if ($isPrimary) {
                $updateStmt = $this->db->prepare(
                    "UPDATE mfg_user_roles SET is_primary = 0 WHERE user_id = ? AND deleted = 0"
                );
                $updateStmt->execute([$userId]);
            }
            
            // Insert new role assignment
            $insertStmt = $this->db->prepare(
                "INSERT INTO mfg_user_roles (id, user_id, role_id, is_primary, effective_date, expiry_date) 
                 VALUES (UUID(), ?, ?, ?, NOW(), ?)"
            );
            
            $result = $insertStmt->execute([
                $userId,
                $roleId,
                $isPrimary ? 1 : 0,
                $expiryDate
            ]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Remove role from user
     */
    public function removeUserRole(string $userId, string $roleId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE mfg_user_roles SET deleted = 1 WHERE user_id = ? AND role_id = ?"
        );
        
        return $stmt->execute([$userId, $roleId]);
    }

    /**
     * Get user territories
     */
    public function getUserTerritories(string $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT t.id, t.name, t.region, ut.access_level
             FROM mfg_user_territories ut
             JOIN mfg_territories t ON ut.territory_id = t.id
             WHERE ut.user_id = ? AND ut.deleted = 0 AND t.deleted = 0
             ORDER BY t.region, t.name"
        );
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Assign territory to user
     */
    public function assignUserTerritory(string $userId, string $territoryId, string $accessLevel = 'read'): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO mfg_user_territories (id, user_id, territory_id, access_level) 
             VALUES (UUID(), ?, ?, ?)
             ON DUPLICATE KEY UPDATE access_level = VALUES(access_level), deleted = 0"
        );
        
        return $stmt->execute([$userId, $territoryId, $accessLevel]);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $userId, string $module, string $action): bool
    {
        $userRoles = $this->getUserRoles($userId);
        $permissions = $userRoles['merged_permissions'];
        
        if (empty($permissions)) {
            return false;
        }
        
        // Check if user has admin role (full access)
        foreach ($userRoles['roles'] as $role) {
            if ($role['role_name'] === 'admin') {
                return true;
            }
        }
        
        // Check specific module permission
        if (isset($permissions[$module])) {
            return in_array(strtoupper($action), $permissions[$module]);
        }
        
        return false;
    }

    /**
     * Get permission matrix for role
     */
    public function getRolePermissionMatrix(string $roleName): array
    {
        $role = $this->getRoleByName($roleName);
        if (!$role) {
            return [];
        }
        
        return $role['permissions'];
    }

    /**
     * Update role permissions
     */
    public function updateRolePermissions(string $roleId, array $permissions): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE mfg_role_definitions SET permissions = ?, modified_date = NOW() WHERE id = ?"
        );
        
        return $stmt->execute([json_encode($permissions), $roleId]);
    }

    /**
     * Create new role definition
     */
    public function createRole(string $roleName, string $description, array $permissions, int $hierarchyLevel = 1): string
    {
        $roleId = $this->generateUUID();
        
        $stmt = $this->db->prepare(
            "INSERT INTO mfg_role_definitions (id, role_name, role_description, permissions, hierarchy_level) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $roleId,
            $roleName,
            $description,
            json_encode($permissions),
            $hierarchyLevel
        ]);
        
        return $roleId;
    }

    /**
     * Get all territories
     */
    public function getAllTerritories(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, name, region, manager_id FROM mfg_territories WHERE deleted = 0 ORDER BY region, name"
        );
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create territory
     */
    public function createTerritory(string $name, string $region, ?string $managerId = null): string
    {
        $territoryId = $this->generateUUID();
        
        $stmt = $this->db->prepare(
            "INSERT INTO mfg_territories (id, name, region, manager_id) VALUES (?, ?, ?, ?)"
        );
        
        $stmt->execute([$territoryId, $name, $region, $managerId]);
        
        return $territoryId;
    }

    /**
     * Generate UUID
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

    /**
     * Filter data by user territories (for territory-based access control)
     */
    public function filterByUserTerritories(string $userId, array $data, string $territoryField = 'territory_id'): array
    {
        $territories = $this->getUserTerritories($userId);
        $allowedTerritoryIds = array_column($territories, 'id');
        
        // Admin and Manager roles see all data
        $userRoles = $this->getUserRoles($userId);
        foreach ($userRoles['roles'] as $role) {
            if (in_array($role['role_name'], ['admin', 'manager'])) {
                return $data;
            }
        }
        
        // Filter data by allowed territories
        return array_filter($data, function($item) use ($allowedTerritoryIds, $territoryField) {
            return in_array($item[$territoryField] ?? null, $allowedTerritoryIds);
        });
    }
}
