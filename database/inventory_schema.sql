-- Real-Time Inventory Integration Database Schema
-- Manufacturing Distribution Platform

-- Warehouses table
CREATE TABLE mfg_warehouses (
    id CHAR(36) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    zip_code VARCHAR(20),
    country VARCHAR(50) DEFAULT 'USA',
    contact_name VARCHAR(100),
    contact_phone VARCHAR(50),
    contact_email VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    priority INT DEFAULT 0,
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by CHAR(36),
    modified_user_id CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_warehouse_code (code),
    INDEX idx_warehouse_active (is_active, deleted)
);

-- Main inventory table
CREATE TABLE mfg_inventory (
    id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    warehouse_id CHAR(36) NOT NULL,
    current_stock DECIMAL(15,4) DEFAULT 0,
    reserved_stock DECIMAL(15,4) DEFAULT 0,
    available_stock DECIMAL(15,4) GENERATED ALWAYS AS (current_stock - reserved_stock) STORED,
    reorder_point DECIMAL(15,4) DEFAULT 0,
    reorder_quantity DECIMAL(15,4) DEFAULT 0,
    max_stock_level DECIMAL(15,4) DEFAULT 0,
    unit_cost DECIMAL(15,4) DEFAULT 0,
    last_count_date DATETIME,
    next_delivery_date DATETIME,
    expected_quantity DECIMAL(15,4) DEFAULT 0,
    supplier_lead_time INT DEFAULT 0, -- days
    stock_status ENUM('in_stock', 'low_stock', 'out_of_stock', 'on_order', 'discontinued') DEFAULT 'in_stock',
    location_bin VARCHAR(50),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by CHAR(36),
    modified_user_id CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (warehouse_id) REFERENCES mfg_warehouses(id),
    UNIQUE KEY unique_product_warehouse (product_id, warehouse_id),
    INDEX idx_inventory_product (product_id),
    INDEX idx_inventory_warehouse (warehouse_id),
    INDEX idx_inventory_status (stock_status),
    INDEX idx_inventory_reorder (reorder_point, current_stock),
    INDEX idx_inventory_available (available_stock),
    INDEX idx_inventory_modified (date_modified)
);

-- Stock reservations table
CREATE TABLE mfg_stock_reservations (
    id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    warehouse_id CHAR(36) NOT NULL,
    quote_id CHAR(36),
    order_id CHAR(36),
    reserved_quantity DECIMAL(15,4) NOT NULL,
    reserved_by CHAR(36) NOT NULL,
    reservation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    expiration_date DATETIME NOT NULL,
    status ENUM('active', 'expired', 'fulfilled', 'cancelled') DEFAULT 'active',
    notes TEXT,
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by CHAR(36),
    modified_user_id CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (warehouse_id) REFERENCES mfg_warehouses(id),
    INDEX idx_reservation_product (product_id),
    INDEX idx_reservation_warehouse (warehouse_id),
    INDEX idx_reservation_quote (quote_id),
    INDEX idx_reservation_order (order_id),
    INDEX idx_reservation_expiration (expiration_date, status),
    INDEX idx_reservation_status (status),
    INDEX idx_reservation_user (reserved_by)
);

-- Stock movements/audit table
CREATE TABLE mfg_stock_movements (
    id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    warehouse_id CHAR(36) NOT NULL,
    movement_type ENUM('inbound', 'outbound', 'transfer', 'adjustment', 'reservation', 'release') NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    unit_cost DECIMAL(15,4) DEFAULT 0,
    reference_type ENUM('purchase_order', 'sales_order', 'quote', 'adjustment', 'transfer', 'api_sync', 'manual') NOT NULL,
    reference_id CHAR(36),
    from_warehouse_id CHAR(36), -- for transfers
    to_warehouse_id CHAR(36), -- for transfers
    notes TEXT,
    performed_by CHAR(36) NOT NULL,
    movement_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    batch_number VARCHAR(50),
    lot_number VARCHAR(50),
    expiry_date DATE,
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (warehouse_id) REFERENCES mfg_warehouses(id),
    INDEX idx_movement_product (product_id),
    INDEX idx_movement_warehouse (warehouse_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_movement_date (movement_date),
    INDEX idx_movement_reference (reference_type, reference_id),
    INDEX idx_movement_user (performed_by),
    INDEX idx_movement_batch (batch_number)
);

-- Supplier information for inventory
CREATE TABLE mfg_inventory_suppliers (
    id CHAR(36) PRIMARY KEY,
    product_id CHAR(36) NOT NULL,
    supplier_name VARCHAR(100) NOT NULL,
    supplier_code VARCHAR(50),
    supplier_product_code VARCHAR(100),
    lead_time_days INT DEFAULT 0,
    minimum_order_qty DECIMAL(15,4) DEFAULT 0,
    unit_cost DECIMAL(15,4) DEFAULT 0,
    is_preferred TINYINT(1) DEFAULT 0,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(50),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by CHAR(36),
    modified_user_id CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_supplier_product (product_id),
    INDEX idx_supplier_preferred (is_preferred),
    INDEX idx_supplier_name (supplier_name)
);

-- Webhook configuration table
CREATE TABLE mfg_inventory_webhooks (
    id CHAR(36) PRIMARY KEY,
    webhook_name VARCHAR(100) NOT NULL,
    endpoint_url VARCHAR(500) NOT NULL,
    secret_key VARCHAR(255),
    event_types JSON, -- ['stock_update', 'low_stock', 'reorder']
    is_active TINYINT(1) DEFAULT 1,
    retry_count INT DEFAULT 3,
    timeout_seconds INT DEFAULT 30,
    last_triggered DATETIME,
    success_count INT DEFAULT 0,
    failure_count INT DEFAULT 0,
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by CHAR(36),
    modified_user_id CHAR(36),
    deleted TINYINT(1) DEFAULT 0,
    INDEX idx_webhook_active (is_active),
    INDEX idx_webhook_last_triggered (last_triggered)
);

-- API sync log table
CREATE TABLE mfg_inventory_sync_log (
    id CHAR(36) PRIMARY KEY,
    sync_type ENUM('full', 'incremental', 'product', 'warehouse') NOT NULL,
    external_system VARCHAR(100),
    products_processed INT DEFAULT 0,
    products_updated INT DEFAULT 0,
    products_failed INT DEFAULT 0,
    sync_status ENUM('running', 'completed', 'failed', 'cancelled') DEFAULT 'running',
    start_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    end_time DATETIME,
    error_message TEXT,
    sync_data JSON, -- metadata about the sync
    performed_by CHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sync_status (sync_status),
    INDEX idx_sync_start_time (start_time),
    INDEX idx_sync_type (sync_type)
);

-- Sample data for warehouses
INSERT INTO mfg_warehouses (id, name, code, address, city, state, zip_code, contact_name, contact_phone, contact_email, priority) VALUES
(UUID(), 'Main Distribution Center', 'MDC', '1234 Manufacturing Blvd', 'Detroit', 'MI', '48201', 'John Smith', '313-555-0101', 'john.smith@company.com', 1),
(UUID(), 'West Coast Facility', 'WCF', '5678 Industrial Way', 'Los Angeles', 'CA', '90210', 'Maria Garcia', '213-555-0102', 'maria.garcia@company.com', 2),
(UUID(), 'East Coast Distribution', 'ECD', '9012 Logistics Ave', 'Atlanta', 'GA', '30309', 'Robert Johnson', '404-555-0103', 'robert.johnson@company.com', 3),
(UUID(), 'Texas Regional Hub', 'TRH', '3456 Supply Chain St', 'Dallas', 'TX', '75201', 'Sarah Williams', '214-555-0104', 'sarah.williams@company.com', 4),
(UUID(), 'Chicago Processing Center', 'CPC', '7890 Fulfillment Dr', 'Chicago', 'IL', '60601', 'Michael Brown', '312-555-0105', 'michael.brown@company.com', 5);
