-- User Role Management & Permissions Database Schema
-- Feature 6: RBAC System for Manufacturing Distributors

-- Drop existing tables if they exist
DROP TABLE IF EXISTS mfg_user_role_permissions;
DROP TABLE IF EXISTS mfg_user_roles;
DROP TABLE IF EXISTS mfg_role_definitions;
DROP TABLE IF EXISTS mfg_user_territories;
DROP TABLE IF EXISTS mfg_territories;

-- Create territories table for geographical/client segmentation
CREATE TABLE mfg_territories (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    region VARCHAR(50) NOT NULL,
    manager_id VARCHAR(36),
    created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_territory_region (region),
    INDEX idx_territory_manager (manager_id)
);

-- Create role definitions table
CREATE TABLE mfg_role_definitions (
    id VARCHAR(36) PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    permissions JSON,
    hierarchy_level INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_role_name (role_name),
    INDEX idx_hierarchy (hierarchy_level)
);

-- Create user roles table (links users to roles)
CREATE TABLE mfg_user_roles (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    role_id VARCHAR(36) NOT NULL,
    is_primary TINYINT(1) DEFAULT 1,
    effective_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATETIME NULL,
    created_by VARCHAR(36),
    created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (role_id) REFERENCES mfg_role_definitions(id),
    INDEX idx_user_role (user_id, role_id),
    INDEX idx_user_primary (user_id, is_primary),
    INDEX idx_effective_date (effective_date)
);

-- Create user territories table (assigns users to territories)
CREATE TABLE mfg_user_territories (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    territory_id VARCHAR(36) NOT NULL,
    access_level ENUM('READ', 'WRITE', 'ADMIN') DEFAULT 'READ',
    created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (territory_id) REFERENCES mfg_territories(id),
    INDEX idx_user_territory (user_id, territory_id),
    INDEX idx_territory_access (territory_id, access_level)
);

-- Create user role permissions table for granular permissions
CREATE TABLE mfg_user_role_permissions (
    id VARCHAR(36) PRIMARY KEY,
    user_role_id VARCHAR(36) NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    permission_type ENUM('CREATE', 'READ', 'UPDATE', 'DELETE', 'EXPORT', 'ADMIN') NOT NULL,
    is_granted TINYINT(1) DEFAULT 1,
    created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modified_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_role_id) REFERENCES mfg_user_roles(id),
    UNIQUE KEY unique_permission (user_role_id, module_name, permission_type),
    INDEX idx_module_permission (module_name, permission_type)
);

-- Insert default role definitions
INSERT INTO mfg_role_definitions (id, role_name, role_description, permissions, hierarchy_level) VALUES
(
    UUID(),
    'sales_rep',
    'Sales Representative - Field sales with territory access',
    JSON_OBJECT(
        'product_catalog', JSON_ARRAY('READ'),
        'quotes', JSON_ARRAY('CREATE', 'READ', 'UPDATE'),
        'clients', JSON_ARRAY('READ', 'UPDATE'),
        'orders', JSON_ARRAY('READ'),
        'inventory', JSON_ARRAY('READ'),
        'territory_data', JSON_ARRAY('READ')
    ),
    1
),
(
    UUID(),
    'manager',
    'Sales Manager - Team oversight and territory management',
    JSON_OBJECT(
        'product_catalog', JSON_ARRAY('READ', 'UPDATE'),
        'quotes', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE'),
        'clients', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE'),
        'orders', JSON_ARRAY('CREATE', 'READ', 'UPDATE'),
        'inventory', JSON_ARRAY('READ', 'UPDATE'),
        'territory_data', JSON_ARRAY('READ', 'UPDATE'),
        'team_analytics', JSON_ARRAY('READ'),
        'user_management', JSON_ARRAY('READ', 'UPDATE')
    ),
    2
),
(
    UUID(),
    'client',
    'Client User - Self-service portal access',
    JSON_OBJECT(
        'orders', JSON_ARRAY('READ'),
        'invoices', JSON_ARRAY('READ', 'EXPORT'),
        'reorder', JSON_ARRAY('CREATE'),
        'account_info', JSON_ARRAY('READ', 'UPDATE'),
        'support', JSON_ARRAY('CREATE', 'READ')
    ),
    0
),
(
    UUID(),
    'admin',
    'System Administrator - Full system access',
    JSON_OBJECT(
        'product_catalog', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE', 'ADMIN'),
        'quotes', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE', 'ADMIN'),
        'clients', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE', 'ADMIN'),
        'orders', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE', 'ADMIN'),
        'inventory', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE', 'ADMIN'),
        'territory_data', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE', 'ADMIN'),
        'team_analytics', JSON_ARRAY('READ', 'ADMIN'),
        'user_management', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE', 'ADMIN'),
        'system_config', JSON_ARRAY('CREATE', 'READ', 'UPDATE', 'DELETE', 'ADMIN')
    ),
    3
);

-- Insert default territories
INSERT INTO mfg_territories (id, name, region) VALUES
(UUID(), 'North East Manufacturing', 'Northeast'),
(UUID(), 'Southeast Industrial', 'Southeast'),
(UUID(), 'Midwest Production', 'Midwest'),
(UUID(), 'West Coast Distribution', 'West'),
(UUID(), 'National Accounts', 'National');

-- Create indexes for performance
CREATE INDEX idx_user_roles_composite ON mfg_user_roles (user_id, role_id, is_primary, deleted);
CREATE INDEX idx_role_permissions_module ON mfg_user_role_permissions (module_name, permission_type, is_granted);
CREATE INDEX idx_territories_active ON mfg_territories (deleted, region);
CREATE INDEX idx_user_territory_access ON mfg_user_territories (user_id, access_level, deleted);
