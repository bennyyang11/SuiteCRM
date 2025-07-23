-- ============================================================================
-- SuiteCRM Manufacturing Module - Database Schema
-- Enterprise Legacy Modernization Project
-- ============================================================================

-- 1. MFG_PRODUCTS TABLE - Core product information with SKU, pricing, inventory
DROP TABLE IF EXISTS mfg_products;
CREATE TABLE mfg_products (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    specifications TEXT,
    
    -- Product Classification
    category VARCHAR(100),
    subcategory VARCHAR(100),
    material VARCHAR(100),
    grade VARCHAR(50),
    finish VARCHAR(100),
    
    -- Pricing Fields
    base_price DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    cost DECIMAL(12,4) DEFAULT 0.0000,
    list_price DECIMAL(12,4) DEFAULT 0.0000,
    currency CHAR(3) DEFAULT 'USD',
    
    -- Inventory Fields
    stock_quantity INT DEFAULT 0,
    reserved_quantity INT DEFAULT 0,
    available_quantity INT GENERATED ALWAYS AS (stock_quantity - reserved_quantity) STORED,
    reorder_point INT DEFAULT 0,
    reorder_quantity INT DEFAULT 0,
    lead_time_days INT DEFAULT 0,
    
    -- Physical Specifications
    weight DECIMAL(10,4),
    weight_unit VARCHAR(10) DEFAULT 'lbs',
    length DECIMAL(10,4),
    width DECIMAL(10,4),
    height DECIMAL(10,4),
    dimension_unit VARCHAR(10) DEFAULT 'in',
    
    -- Product Images and Documentation
    primary_image_url VARCHAR(500),
    datasheet_url VARCHAR(500),
    cad_file_url VARCHAR(500),
    
    -- Product Status
    status VARCHAR(50) DEFAULT 'Active',
    is_discontinued TINYINT(1) DEFAULT 0,
    replacement_sku VARCHAR(100),
    
    -- Compliance and Certifications
    certifications TEXT,
    compliance_notes TEXT,
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    modified_user_id CHAR(36),
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    
    -- Indexes for Performance
    INDEX idx_sku (sku),
    INDEX idx_category (category),
    INDEX idx_material (material),
    INDEX idx_status (status, deleted),
    INDEX idx_availability (available_quantity, status),
    INDEX idx_date_modified (date_modified),
    
    FOREIGN KEY (modified_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 2. MFG_PRICING_TIERS TABLE - Customer pricing levels
DROP TABLE IF EXISTS mfg_pricing_tiers;
CREATE TABLE mfg_pricing_tiers (
    id CHAR(36) NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    tier_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    
    -- Tier Configuration
    tier_order INT DEFAULT 0,
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    markup_percentage DECIMAL(5,2) DEFAULT 0.00,
    price_multiplier DECIMAL(6,4) DEFAULT 1.0000,
    
    -- Minimum Requirements
    minimum_order_value DECIMAL(12,2) DEFAULT 0.00,
    minimum_order_quantity INT DEFAULT 1,
    minimum_annual_volume DECIMAL(12,2) DEFAULT 0.00,
    
    -- Tier Validity
    effective_date DATE,
    expiration_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    
    -- Geographic Restrictions
    allowed_countries TEXT,
    restricted_states TEXT,
    
    -- Terms and Conditions
    payment_terms VARCHAR(100),
    shipping_terms VARCHAR(100),
    special_conditions TEXT,
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    modified_user_id CHAR(36),
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    
    -- Indexes
    INDEX idx_tier_code (tier_code),
    INDEX idx_tier_order (tier_order),
    INDEX idx_active (is_active, deleted),
    INDEX idx_effective_date (effective_date, expiration_date),
    
    FOREIGN KEY (modified_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 3. MFG_CLIENT_CONTRACTS TABLE - Negotiated customer pricing
DROP TABLE IF EXISTS mfg_client_contracts;
CREATE TABLE mfg_client_contracts (
    id CHAR(36) NOT NULL PRIMARY KEY,
    contract_number VARCHAR(100) NOT NULL UNIQUE,
    client_id CHAR(36) NOT NULL,
    
    -- Contract Details
    contract_name VARCHAR(255),
    contract_type VARCHAR(50) DEFAULT 'Standard',
    status VARCHAR(50) DEFAULT 'Active',
    
    -- Pricing Configuration
    pricing_tier_id CHAR(36),
    custom_discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    volume_tier_1_qty INT DEFAULT 0,
    volume_tier_1_discount DECIMAL(5,2) DEFAULT 0.00,
    volume_tier_2_qty INT DEFAULT 0,
    volume_tier_2_discount DECIMAL(5,2) DEFAULT 0.00,
    volume_tier_3_qty INT DEFAULT 0,
    volume_tier_3_discount DECIMAL(5,2) DEFAULT 0.00,
    
    -- Contract Terms
    effective_date DATE NOT NULL,
    expiration_date DATE,
    auto_renewal TINYINT(1) DEFAULT 0,
    renewal_term_months INT DEFAULT 12,
    
    -- Order Requirements
    minimum_order_value DECIMAL(12,2) DEFAULT 0.00,
    maximum_credit_limit DECIMAL(12,2),
    payment_terms VARCHAR(100) DEFAULT 'Net 30',
    
    -- Shipping and Logistics
    shipping_terms VARCHAR(100),
    preferred_warehouse VARCHAR(100),
    delivery_instructions TEXT,
    
    -- Special Provisions
    exclusive_products TEXT,
    restricted_products TEXT,
    special_terms TEXT,
    rebate_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Contract Management
    primary_contact_id CHAR(36),
    sales_rep_id CHAR(36),
    approval_status VARCHAR(50) DEFAULT 'Pending',
    approved_by CHAR(36),
    approval_date DATETIME,
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    modified_user_id CHAR(36),
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    
    -- Indexes
    INDEX idx_contract_number (contract_number),
    INDEX idx_client_id (client_id),
    INDEX idx_status (status, deleted),
    INDEX idx_effective_dates (effective_date, expiration_date),
    INDEX idx_pricing_tier (pricing_tier_id),
    INDEX idx_sales_rep (sales_rep_id),
    
    FOREIGN KEY (client_id) REFERENCES accounts(id),
    FOREIGN KEY (pricing_tier_id) REFERENCES mfg_pricing_tiers(id),
    FOREIGN KEY (primary_contact_id) REFERENCES contacts(id),
    FOREIGN KEY (sales_rep_id) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (modified_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 4. MFG_PRODUCT_PRICING TABLE - Junction table for product-specific pricing
DROP TABLE IF EXISTS mfg_product_pricing;
CREATE TABLE mfg_product_pricing (
    id CHAR(36) NOT NULL PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    pricing_tier_id CHAR(36),
    client_contract_id CHAR(36),
    
    -- Pricing Details
    custom_price DECIMAL(12,4),
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    effective_date DATE NOT NULL,
    expiration_date DATE,
    
    -- Quantity Breaks
    min_quantity INT DEFAULT 1,
    max_quantity INT,
    
    -- Special Conditions
    notes TEXT,
    requires_approval TINYINT(1) DEFAULT 0,
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    modified_user_id CHAR(36),
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    
    -- Indexes
    INDEX idx_product_tier (product_id, pricing_tier_id),
    INDEX idx_product_contract (product_id, client_contract_id),
    INDEX idx_effective_dates (effective_date, expiration_date),
    
    FOREIGN KEY (product_id) REFERENCES mfg_products(id),
    FOREIGN KEY (pricing_tier_id) REFERENCES mfg_pricing_tiers(id),
    FOREIGN KEY (client_contract_id) REFERENCES mfg_client_contracts(id),
    FOREIGN KEY (modified_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 5. MFG_INVENTORY_TRANSACTIONS TABLE - Track inventory movements
DROP TABLE IF EXISTS mfg_inventory_transactions;
CREATE TABLE mfg_inventory_transactions (
    id CHAR(36) NOT NULL PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    
    -- Transaction Details
    transaction_type VARCHAR(50) NOT NULL, -- 'Receipt', 'Sale', 'Adjustment', 'Transfer', 'Reserved', 'Released'
    quantity_change INT NOT NULL,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    
    -- Reference Information
    reference_type VARCHAR(50), -- 'Purchase Order', 'Sales Order', 'Manual Adjustment', etc.
    reference_id CHAR(36),
    reference_number VARCHAR(100),
    
    -- Transaction Context
    reason VARCHAR(255),
    notes TEXT,
    warehouse_location VARCHAR(100),
    
    -- Cost Information
    unit_cost DECIMAL(12,4),
    total_cost DECIMAL(12,2),
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL,
    date_modified DATETIME NOT NULL,
    modified_user_id CHAR(36),
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    
    -- Indexes
    INDEX idx_product_id (product_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_date_entered (date_entered),
    INDEX idx_reference (reference_type, reference_id),
    
    FOREIGN KEY (product_id) REFERENCES mfg_products(id),
    FOREIGN KEY (modified_user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Add comments to tables for documentation
ALTER TABLE mfg_products COMMENT = 'Core manufacturing products with SKU, pricing, and inventory management';
ALTER TABLE mfg_pricing_tiers COMMENT = 'Customer pricing tiers (Retail, Wholesale, OEM, etc.)';
ALTER TABLE mfg_client_contracts COMMENT = 'Negotiated customer-specific pricing contracts';
ALTER TABLE mfg_product_pricing COMMENT = 'Product-specific pricing overrides and volume breaks';
ALTER TABLE mfg_inventory_transactions COMMENT = 'Inventory movement tracking and audit trail';
