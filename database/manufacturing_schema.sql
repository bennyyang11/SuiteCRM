-- SuiteCRM Manufacturing Distribution Database Schema
-- Phase 1: Product Catalog, Pricing Tiers, Client Contracts

-- 1. Products Table
CREATE TABLE IF NOT EXISTS mfg_products (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    category VARCHAR(100),
    material VARCHAR(100),
    weight_lbs DECIMAL(10,2),
    dimensions VARCHAR(100),
    base_price DECIMAL(15,2) NOT NULL,
    cost_price DECIMAL(15,2),
    list_price DECIMAL(15,2),
    status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    minimum_order_qty INT DEFAULT 1,
    packaging_unit VARCHAR(50),
    manufacturer VARCHAR(255),
    supplier_sku VARCHAR(100),
    lead_time_days INT DEFAULT 0,
    image_url VARCHAR(500),
    thumbnail_url VARCHAR(500),
    specifications JSON,
    tags TEXT,
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    INDEX idx_sku (sku),
    INDEX idx_category (category),
    INDEX idx_material (material),
    INDEX idx_status (status),
    INDEX idx_deleted (deleted),
    FULLTEXT idx_search (name, description, sku, tags)
);

-- 2. Customer Pricing Tiers
CREATE TABLE IF NOT EXISTS mfg_pricing_tiers (
    id VARCHAR(36) PRIMARY KEY,
    tier_name VARCHAR(100) NOT NULL,
    tier_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    minimum_order_amount DECIMAL(15,2) DEFAULT 0.00,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    INDEX idx_tier_code (tier_code),
    INDEX idx_active (is_active),
    INDEX idx_deleted (deleted)
);

-- 3. Client Contracts
CREATE TABLE IF NOT EXISTS mfg_client_contracts (
    id VARCHAR(36) PRIMARY KEY,
    account_id VARCHAR(36) NOT NULL,
    contract_number VARCHAR(100) UNIQUE NOT NULL,
    pricing_tier_id VARCHAR(36),
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'expired', 'cancelled', 'pending') DEFAULT 'active',
    payment_terms VARCHAR(100),
    shipping_terms VARCHAR(100),
    special_pricing JSON,
    notes TEXT,
    assigned_rep_id VARCHAR(36),
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (pricing_tier_id) REFERENCES mfg_pricing_tiers(id),
    FOREIGN KEY (assigned_rep_id) REFERENCES users(id),
    
    INDEX idx_account_id (account_id),
    INDEX idx_contract_number (contract_number),
    INDEX idx_status (status),
    INDEX idx_rep_id (assigned_rep_id),
    INDEX idx_deleted (deleted)
);

-- 4. Product Pricing Rules
CREATE TABLE IF NOT EXISTS mfg_product_pricing (
    id VARCHAR(36) PRIMARY KEY,
    product_id VARCHAR(36) NOT NULL,
    pricing_tier_id VARCHAR(36),
    account_id VARCHAR(36),
    price_type ENUM('tier_price', 'contract_price', 'volume_price') DEFAULT 'tier_price',
    custom_price DECIMAL(15,2),
    min_quantity INT DEFAULT 1,
    max_quantity INT,
    effective_date DATE NOT NULL,
    expiry_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (product_id) REFERENCES mfg_products(id),
    FOREIGN KEY (pricing_tier_id) REFERENCES mfg_pricing_tiers(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    
    INDEX idx_product_id (product_id),
    INDEX idx_pricing_tier_id (pricing_tier_id),
    INDEX idx_account_id (account_id),
    INDEX idx_price_type (price_type),
    INDEX idx_active (is_active),
    INDEX idx_deleted (deleted)
);

-- 5. Inventory Levels
CREATE TABLE IF NOT EXISTS mfg_inventory (
    id VARCHAR(36) PRIMARY KEY,
    product_id VARCHAR(36) NOT NULL,
    warehouse_location VARCHAR(100) DEFAULT 'main',
    quantity_on_hand INT DEFAULT 0,
    quantity_reserved INT DEFAULT 0,
    quantity_available INT GENERATED ALWAYS AS (quantity_on_hand - quantity_reserved) STORED,
    reorder_point INT DEFAULT 0,
    max_stock_level INT DEFAULT 0,
    last_count_date DATE,
    last_sync_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    sync_status ENUM('synced', 'pending', 'error') DEFAULT 'synced',
    external_system_id VARCHAR(100),
    notes TEXT,
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (product_id) REFERENCES mfg_products(id),
    
    UNIQUE KEY unique_product_warehouse (product_id, warehouse_location),
    INDEX idx_product_id (product_id),
    INDEX idx_warehouse (warehouse_location),
    INDEX idx_quantity_available (quantity_available),
    INDEX idx_sync_status (sync_status),
    INDEX idx_deleted (deleted)
);

-- 6. Manufacturing User Roles
CREATE TABLE IF NOT EXISTS mfg_user_roles (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    role_type ENUM('sales_rep', 'sales_manager', 'client_user', 'admin') NOT NULL,
    territory VARCHAR(255),
    product_categories TEXT, -- JSON array of allowed categories
    client_accounts TEXT, -- JSON array of account IDs for client users
    permissions JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_role_type (role_type),
    INDEX idx_active (is_active),
    INDEX idx_deleted (deleted)
);

-- 7. Quote to Order Pipeline
CREATE TABLE IF NOT EXISTS mfg_order_pipeline (
    id VARCHAR(36) PRIMARY KEY,
    quote_id VARCHAR(36),
    opportunity_id VARCHAR(36),
    account_id VARCHAR(36) NOT NULL,
    pipeline_stage ENUM(
        'quote_created', 
        'quote_sent', 
        'quote_approved', 
        'order_placed', 
        'order_shipped', 
        'invoice_sent', 
        'payment_received'
    ) DEFAULT 'quote_created',
    stage_updated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    expected_close_date DATE,
    total_amount DECIMAL(15,2),
    probability_percent INT DEFAULT 50,
    assigned_rep_id VARCHAR(36),
    client_po_number VARCHAR(100),
    shipping_address TEXT,
    special_instructions TEXT,
    stage_history JSON,
    notifications_sent JSON,
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (assigned_rep_id) REFERENCES users(id),
    
    INDEX idx_quote_id (quote_id),
    INDEX idx_opportunity_id (opportunity_id),
    INDEX idx_account_id (account_id),
    INDEX idx_pipeline_stage (pipeline_stage),
    INDEX idx_rep_id (assigned_rep_id),
    INDEX idx_deleted (deleted)
);

-- Insert Default Pricing Tiers
INSERT INTO mfg_pricing_tiers (id, tier_name, tier_code, description, discount_percentage, minimum_order_amount) VALUES
(UUID(), 'Retail', 'RETAIL', 'Standard retail pricing', 0.00, 0.00),
(UUID(), 'Volume', 'VOLUME', 'Volume discount tier', 5.00, 1000.00),
(UUID(), 'Wholesale', 'WHOLESALE', 'Wholesale pricing tier', 15.00, 5000.00),
(UUID(), 'Distributor', 'DIST', 'Distributor pricing tier', 25.00, 10000.00),
(UUID(), 'OEM Partner', 'OEM', 'OEM partner pricing', 35.00, 25000.00);

-- Create Views for Common Queries

-- Product Catalog with Inventory
CREATE VIEW mfg_product_catalog AS
SELECT 
    p.id,
    p.name,
    p.sku,
    p.description,
    p.category,
    p.material,
    p.base_price,
    p.list_price,
    p.minimum_order_qty,
    p.image_url,
    p.thumbnail_url,
    p.specifications,
    i.quantity_available,
    CASE 
        WHEN i.quantity_available <= 0 THEN 'out_of_stock'
        WHEN i.quantity_available <= i.reorder_point THEN 'low_stock'
        ELSE 'in_stock'
    END as stock_status,
    p.status as product_status
FROM mfg_products p
LEFT JOIN mfg_inventory i ON p.id = i.product_id
WHERE p.deleted = 0 AND p.status = 'active';

-- Client Pricing View
CREATE VIEW mfg_client_pricing AS
SELECT 
    p.id as product_id,
    p.sku,
    p.name as product_name,
    p.base_price,
    p.list_price,
    cc.account_id,
    pt.tier_name,
    pt.discount_percentage,
    ROUND(p.list_price * (1 - pt.discount_percentage / 100), 2) as client_price,
    pp.custom_price,
    COALESCE(pp.custom_price, ROUND(p.list_price * (1 - pt.discount_percentage / 100), 2)) as final_price
FROM mfg_products p
CROSS JOIN mfg_client_contracts cc
LEFT JOIN mfg_pricing_tiers pt ON cc.pricing_tier_id = pt.id
LEFT JOIN mfg_product_pricing pp ON p.id = pp.product_id AND cc.account_id = pp.account_id
WHERE p.deleted = 0 AND p.status = 'active' 
AND cc.deleted = 0 AND cc.status = 'active'
AND (cc.end_date IS NULL OR cc.end_date >= CURDATE());
