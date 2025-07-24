-- Performance Optimization Indexes for SuiteCRM Manufacturing
-- These indexes improve query performance for manufacturing-specific operations

-- ========================================
-- MANUFACTURING PRODUCTS INDEXES
-- ========================================

-- Primary search and filtering indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_sku ON manufacturing_products(sku);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_category ON manufacturing_products(category);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_manufacturer ON manufacturing_products(manufacturer);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_status ON manufacturing_products(status);

-- Inventory management indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_quantity ON manufacturing_products(quantity_available, quantity_reserved);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_inventory_sync ON manufacturing_products(inventory_last_sync);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_warehouse ON manufacturing_products(warehouse_location);

-- Pricing and cost indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_price ON manufacturing_products(unit_price);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_cost ON manufacturing_products(unit_cost);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_price_updated ON manufacturing_products(price_last_updated);

-- Date-based indexes for reporting
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_date_entered ON manufacturing_products(date_entered);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_date_modified ON manufacturing_products(date_modified);

-- Compound indexes for common queries
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_active_inventory ON manufacturing_products(deleted, status, quantity_available);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_category_price ON manufacturing_products(category, unit_price);
CREATE INDEX IF NOT EXISTS idx_manufacturing_products_search ON manufacturing_products(name, sku, manufacturer);

-- ========================================
-- MANUFACTURING QUOTES INDEXES
-- ========================================

-- Quote management indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_number ON manufacturing_quotes(quote_number);
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_status ON manufacturing_quotes(quote_status);
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_account ON manufacturing_quotes(account_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_contact ON manufacturing_quotes(contact_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_assigned ON manufacturing_quotes(assigned_user_id);

-- Date-based indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_date ON manufacturing_quotes(quote_date);
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_valid_until ON manufacturing_quotes(valid_until);
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_created ON manufacturing_quotes(date_entered);

-- Financial indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_total ON manufacturing_quotes(total_amount);
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_currency ON manufacturing_quotes(currency_id);

-- Pipeline tracking indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_pipeline ON manufacturing_quotes(quote_status, date_entered);
CREATE INDEX IF NOT EXISTS idx_manufacturing_quotes_user_status ON manufacturing_quotes(assigned_user_id, quote_status);

-- ========================================
-- MANUFACTURING ORDERS INDEXES
-- ========================================

-- Order management indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_number ON manufacturing_orders(order_number);
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_status ON manufacturing_orders(order_status);
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_account ON manufacturing_orders(account_id);
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_quote ON manufacturing_orders(quote_id);

-- Date-based indexes
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_date ON manufacturing_orders(order_date);
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_delivery ON manufacturing_orders(delivery_date);
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_shipped ON manufacturing_orders(shipped_date);

-- Financial tracking
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_total ON manufacturing_orders(total_amount);
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_payment ON manufacturing_orders(payment_status);

-- Pipeline and fulfillment
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_pipeline ON manufacturing_orders(order_status, order_date);
CREATE INDEX IF NOT EXISTS idx_manufacturing_orders_fulfillment ON manufacturing_orders(fulfillment_status, delivery_date);

-- ========================================
-- QUOTE LINE ITEMS INDEXES
-- ========================================

-- Quote line item relationships
CREATE INDEX IF NOT EXISTS idx_quote_line_items_quote ON manufacturing_quote_line_items(quote_id);
CREATE INDEX IF NOT EXISTS idx_quote_line_items_product ON manufacturing_quote_line_items(product_id);
CREATE INDEX IF NOT EXISTS idx_quote_line_items_line_number ON manufacturing_quote_line_items(quote_id, line_number);

-- Pricing and quantity analysis
CREATE INDEX IF NOT EXISTS idx_quote_line_items_pricing ON manufacturing_quote_line_items(unit_price, quantity);
CREATE INDEX IF NOT EXISTS idx_quote_line_items_total ON manufacturing_quote_line_items(line_total);

-- ========================================
-- ORDER LINE ITEMS INDEXES
-- ========================================

-- Order line item relationships
CREATE INDEX IF NOT EXISTS idx_order_line_items_order ON manufacturing_order_line_items(order_id);
CREATE INDEX IF NOT EXISTS idx_order_line_items_product ON manufacturing_order_line_items(product_id);
CREATE INDEX IF NOT EXISTS idx_order_line_items_line_number ON manufacturing_order_line_items(order_id, line_number);

-- Fulfillment tracking
CREATE INDEX IF NOT EXISTS idx_order_line_items_status ON manufacturing_order_line_items(fulfillment_status);
CREATE INDEX IF NOT EXISTS idx_order_line_items_shipped ON manufacturing_order_line_items(shipped_quantity);

-- ========================================
-- CLIENT PRICING INDEXES
-- ========================================

-- Client-specific pricing
CREATE INDEX IF NOT EXISTS idx_client_pricing_account ON manufacturing_client_pricing(account_id);
CREATE INDEX IF NOT EXISTS idx_client_pricing_product ON manufacturing_client_pricing(product_id);
CREATE INDEX IF NOT EXISTS idx_client_pricing_tier ON manufacturing_client_pricing(pricing_tier);
CREATE INDEX IF NOT EXISTS idx_client_pricing_active ON manufacturing_client_pricing(account_id, product_id, effective_date, expiry_date);

-- Pricing validity
CREATE INDEX IF NOT EXISTS idx_client_pricing_effective ON manufacturing_client_pricing(effective_date);
CREATE INDEX IF NOT EXISTS idx_client_pricing_expiry ON manufacturing_client_pricing(expiry_date);

-- ========================================
-- INVENTORY HISTORY INDEXES
-- ========================================

-- Inventory tracking
CREATE INDEX IF NOT EXISTS idx_inventory_history_product ON inventory_history(product_id);
CREATE INDEX IF NOT EXISTS idx_inventory_history_timestamp ON inventory_history(sync_timestamp);
CREATE INDEX IF NOT EXISTS idx_inventory_history_product_date ON inventory_history(product_id, sync_timestamp);

-- Quantity tracking
CREATE INDEX IF NOT EXISTS idx_inventory_history_quantity ON inventory_history(quantity_available);
CREATE INDEX IF NOT EXISTS idx_inventory_history_reserved ON inventory_history(quantity_reserved);

-- ========================================
-- BACKGROUND JOBS INDEXES
-- ========================================

-- Job queue processing (already defined in JobQueue.php but listed here for completeness)
-- These are critical for job queue performance
-- CREATE INDEX IF NOT EXISTS idx_background_jobs_status_priority ON background_jobs(status, priority);
-- CREATE INDEX IF NOT EXISTS idx_background_jobs_type ON background_jobs(job_type);
-- CREATE INDEX IF NOT EXISTS idx_background_jobs_scheduled ON background_jobs(scheduled_at);
-- CREATE INDEX IF NOT EXISTS idx_background_jobs_created_by ON background_jobs(created_by);

-- ========================================
-- USER ACTIVITY INDEXES
-- ========================================

-- User access patterns
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_employee_status ON users(employee_status);
CREATE INDEX IF NOT EXISTS idx_users_date_entered ON users(date_entered);

-- Authentication and sessions
CREATE INDEX IF NOT EXISTS idx_user_preferences_user ON user_preferences(assigned_user_id);
CREATE INDEX IF NOT EXISTS idx_user_preferences_category ON user_preferences(category);

-- ========================================
-- ACCOUNTS (CUSTOMERS) INDEXES
-- ========================================

-- Customer management
CREATE INDEX IF NOT EXISTS idx_accounts_name ON accounts(name);
CREATE INDEX IF NOT EXISTS idx_accounts_industry ON accounts(industry);
CREATE INDEX IF NOT EXISTS idx_accounts_type ON accounts(account_type);
CREATE INDEX IF NOT EXISTS idx_accounts_assigned ON accounts(assigned_user_id);

-- Geographic indexes
CREATE INDEX IF NOT EXISTS idx_accounts_city ON accounts(billing_address_city);
CREATE INDEX IF NOT EXISTS idx_accounts_state ON accounts(billing_address_state);
CREATE INDEX IF NOT EXISTS idx_accounts_country ON accounts(billing_address_country);

-- Date-based customer analysis
CREATE INDEX IF NOT EXISTS idx_accounts_date_entered ON accounts(date_entered);
CREATE INDEX IF NOT EXISTS idx_accounts_date_modified ON accounts(date_modified);

-- ========================================
-- CONTACTS INDEXES
-- ========================================

-- Contact management
CREATE INDEX IF NOT EXISTS idx_contacts_account ON contacts(account_id);
CREATE INDEX IF NOT EXISTS idx_contacts_email ON contacts(email1);
CREATE INDEX IF NOT EXISTS idx_contacts_phone ON contacts(phone_work);
CREATE INDEX IF NOT EXISTS idx_contacts_name ON contacts(last_name, first_name);

-- Lead management
CREATE INDEX IF NOT EXISTS idx_contacts_lead_source ON contacts(lead_source);
CREATE INDEX IF NOT EXISTS idx_contacts_assigned ON contacts(assigned_user_id);

-- ========================================
-- CACHE OPTIMIZATION INDEXES
-- ========================================

-- These indexes optimize cache invalidation queries
CREATE INDEX IF NOT EXISTS idx_products_cache_tags ON manufacturing_products(category, manufacturer, status);
CREATE INDEX IF NOT EXISTS idx_quotes_cache_tags ON manufacturing_quotes(quote_status, account_id);
CREATE INDEX IF NOT EXISTS idx_orders_cache_tags ON manufacturing_orders(order_status, account_id);

-- ========================================
-- REPORTING OPTIMIZATION INDEXES
-- ========================================

-- Sales performance reporting
CREATE INDEX IF NOT EXISTS idx_quotes_sales_report ON manufacturing_quotes(assigned_user_id, quote_date, total_amount);
CREATE INDEX IF NOT EXISTS idx_orders_sales_report ON manufacturing_orders(assigned_user_id, order_date, total_amount);

-- Product performance reporting
CREATE INDEX IF NOT EXISTS idx_products_performance ON manufacturing_products(category, unit_price, quantity_available);
CREATE INDEX IF NOT EXISTS idx_line_items_product_performance ON manufacturing_order_line_items(product_id, quantity, unit_price);

-- Customer analysis reporting
CREATE INDEX IF NOT EXISTS idx_accounts_customer_analysis ON accounts(industry, account_type, date_entered);
CREATE INDEX IF NOT EXISTS idx_orders_customer_analysis ON manufacturing_orders(account_id, order_date, total_amount);

-- Time-based reporting optimization
CREATE INDEX IF NOT EXISTS idx_quotes_monthly_report ON manufacturing_quotes(DATE(quote_date), quote_status);
CREATE INDEX IF NOT EXISTS idx_orders_monthly_report ON manufacturing_orders(DATE(order_date), order_status);

-- ========================================
-- FULL-TEXT SEARCH INDEXES
-- ========================================

-- Product search optimization
ALTER TABLE manufacturing_products ADD FULLTEXT(name, description, sku);
ALTER TABLE manufacturing_products ADD FULLTEXT(name, sku, manufacturer, category);

-- Customer search optimization
ALTER TABLE accounts ADD FULLTEXT(name, description);
ALTER TABLE contacts ADD FULLTEXT(first_name, last_name, description);

-- Quote and order search
ALTER TABLE manufacturing_quotes ADD FULLTEXT(name, description);
ALTER TABLE manufacturing_orders ADD FULLTEXT(name, description);

-- ========================================
-- PERFORMANCE MONITORING VIEWS
-- ========================================

-- Create views for performance monitoring
CREATE OR REPLACE VIEW v_slow_queries AS
SELECT 
    table_name,
    index_name,
    cardinality,
    (cardinality / (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE())) * 100 as selectivity_ratio
FROM information_schema.statistics 
WHERE table_schema = DATABASE()
ORDER BY selectivity_ratio DESC;

-- Create view for cache hit rates
CREATE OR REPLACE VIEW v_cache_performance AS
SELECT 
    'redis' as cache_type,
    COUNT(*) as total_operations,
    SUM(CASE WHEN operation = 'hit' THEN 1 ELSE 0 END) as hits,
    SUM(CASE WHEN operation = 'miss' THEN 1 ELSE 0 END) as misses,
    (SUM(CASE WHEN operation = 'hit' THEN 1 ELSE 0 END) / COUNT(*)) * 100 as hit_rate
FROM cache_statistics 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- ========================================
-- INDEX MAINTENANCE PROCEDURES
-- ========================================

-- Procedure to analyze table statistics
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS AnalyzeManufacturingTables()
BEGIN
    ANALYZE TABLE manufacturing_products;
    ANALYZE TABLE manufacturing_quotes;
    ANALYZE TABLE manufacturing_orders;
    ANALYZE TABLE manufacturing_quote_line_items;
    ANALYZE TABLE manufacturing_order_line_items;
    ANALYZE TABLE manufacturing_client_pricing;
    ANALYZE TABLE inventory_history;
    ANALYZE TABLE background_jobs;
    ANALYZE TABLE accounts;
    ANALYZE TABLE contacts;
END //
DELIMITER ;

-- Procedure to optimize tables
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS OptimizeManufacturingTables()
BEGIN
    OPTIMIZE TABLE manufacturing_products;
    OPTIMIZE TABLE manufacturing_quotes;
    OPTIMIZE TABLE manufacturing_orders;
    OPTIMIZE TABLE manufacturing_quote_line_items;
    OPTIMIZE TABLE manufacturing_order_line_items;
    OPTIMIZE TABLE manufacturing_client_pricing;
    OPTIMIZE TABLE inventory_history;
    OPTIMIZE TABLE background_jobs;
END //
DELIMITER ;

-- ========================================
-- QUERY PERFORMANCE MONITORING
-- ========================================

-- Enable query logging for performance analysis
-- SET GLOBAL general_log = 'ON';
-- SET GLOBAL slow_query_log = 'ON';
-- SET GLOBAL long_query_time = 2;
-- SET GLOBAL log_queries_not_using_indexes = 'ON';

-- ========================================
-- MAINTENANCE SCHEDULE
-- ========================================

-- These should be run via cron jobs:
-- Daily: CALL AnalyzeManufacturingTables();
-- Weekly: CALL OptimizeManufacturingTables();
-- Monthly: Full index rebuild if needed

-- Performance monitoring queries:
-- SELECT * FROM v_slow_queries LIMIT 10;
-- SELECT * FROM v_cache_performance;
-- SHOW PROCESSLIST; -- Check for long-running queries
