-- Phase 2: Order Pipeline Tracking Schema
-- Extends the manufacturing schema with detailed pipeline management

-- 1. Enhanced Order Pipeline Table
DROP TABLE IF EXISTS mfg_order_pipeline;
CREATE TABLE mfg_order_pipeline (
    id VARCHAR(36) PRIMARY KEY,
    pipeline_number VARCHAR(100) UNIQUE NOT NULL,
    quote_id VARCHAR(36),
    opportunity_id VARCHAR(36),
    account_id VARCHAR(36) NOT NULL,
    account_name VARCHAR(255),
    assigned_rep_id VARCHAR(36),
    assigned_rep_name VARCHAR(255),
    
    -- Pipeline Stage Management
    current_stage ENUM(
        'quote_created', 
        'quote_sent', 
        'quote_approved', 
        'order_placed', 
        'order_shipped', 
        'invoice_sent', 
        'payment_received'
    ) DEFAULT 'quote_created',
    
    stage_updated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    stage_updated_by VARCHAR(36),
    next_action VARCHAR(500),
    
    -- Financial Information
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    shipping_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    final_amount DECIMAL(15,2) DEFAULT 0.00,
    
    -- Timeline & Probability
    quote_date DATE,
    expected_close_date DATE,
    actual_close_date DATE,
    probability_percent INT DEFAULT 50,
    
    -- Order Details
    client_po_number VARCHAR(100),
    internal_order_number VARCHAR(100),
    payment_terms VARCHAR(100),
    shipping_method VARCHAR(100),
    shipping_address TEXT,
    billing_address TEXT,
    
    -- Communication
    special_instructions TEXT,
    internal_notes TEXT,
    client_notes TEXT,
    
    -- Status & Priority
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    is_rush_order TINYINT(1) DEFAULT 0,
    
    -- Metadata
    stage_history JSON,
    notifications_sent JSON,
    attachments JSON,
    
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (assigned_rep_id) REFERENCES users(id),
    
    INDEX idx_pipeline_number (pipeline_number),
    INDEX idx_account_id (account_id),
    INDEX idx_current_stage (current_stage),
    INDEX idx_assigned_rep (assigned_rep_id),
    INDEX idx_quote_date (quote_date),
    INDEX idx_expected_close (expected_close_date),
    INDEX idx_priority (priority),
    INDEX idx_deleted (deleted)
);

-- 2. Pipeline Line Items (Products in each order)
CREATE TABLE mfg_pipeline_items (
    id VARCHAR(36) PRIMARY KEY,
    pipeline_id VARCHAR(36) NOT NULL,
    product_id VARCHAR(36) NOT NULL,
    product_sku VARCHAR(100),
    product_name VARCHAR(255),
    
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    list_price DECIMAL(15,2),
    discount_percentage DECIMAL(5,2) DEFAULT 0.00,
    line_total DECIMAL(15,2) NOT NULL,
    
    special_pricing_notes TEXT,
    delivery_date DATE,
    
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (pipeline_id) REFERENCES mfg_order_pipeline(id),
    FOREIGN KEY (product_id) REFERENCES mfg_products(id),
    
    INDEX idx_pipeline_id (pipeline_id),
    INDEX idx_product_id (product_id),
    INDEX idx_deleted (deleted)
);

-- 3. Pipeline Stage History
CREATE TABLE mfg_pipeline_stage_history (
    id VARCHAR(36) PRIMARY KEY,
    pipeline_id VARCHAR(36) NOT NULL,
    from_stage VARCHAR(50),
    to_stage VARCHAR(50) NOT NULL,
    stage_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    changed_by VARCHAR(36),
    changed_by_name VARCHAR(255),
    notes TEXT,
    duration_hours INT,
    
    -- Email/Notification tracking
    email_sent TINYINT(1) DEFAULT 0,
    email_sent_date DATETIME,
    client_notified TINYINT(1) DEFAULT 0,
    
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (pipeline_id) REFERENCES mfg_order_pipeline(id),
    FOREIGN KEY (changed_by) REFERENCES users(id),
    
    INDEX idx_pipeline_id (pipeline_id),
    INDEX idx_stage_date (stage_date),
    INDEX idx_to_stage (to_stage),
    INDEX idx_deleted (deleted)
);

-- 4. Pipeline Notifications Queue
CREATE TABLE mfg_pipeline_notifications (
    id VARCHAR(36) PRIMARY KEY,
    pipeline_id VARCHAR(36) NOT NULL,
    notification_type ENUM('email', 'sms', 'internal', 'webhook') DEFAULT 'email',
    recipient_type ENUM('client', 'rep', 'manager', 'team') DEFAULT 'client',
    recipient_email VARCHAR(255),
    recipient_phone VARCHAR(50),
    
    subject VARCHAR(500),
    message TEXT,
    template_used VARCHAR(100),
    
    trigger_stage VARCHAR(50),
    send_date DATETIME,
    sent_date DATETIME,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    
    retry_count INT DEFAULT 0,
    error_message TEXT,
    
    created_by VARCHAR(36),
    date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted TINYINT(1) DEFAULT 0,
    
    FOREIGN KEY (pipeline_id) REFERENCES mfg_order_pipeline(id),
    
    INDEX idx_pipeline_id (pipeline_id),
    INDEX idx_status (status),
    INDEX idx_send_date (send_date),
    INDEX idx_deleted (deleted)
);

-- 5. Sample Data for Testing
INSERT INTO mfg_order_pipeline (
    id, pipeline_number, account_id, account_name, assigned_rep_id, assigned_rep_name,
    current_stage, total_amount, probability_percent, quote_date, expected_close_date,
    client_po_number, priority, special_instructions
) VALUES 
(
    UUID(), 'ORD-2025-001', 
    (SELECT id FROM accounts WHERE deleted = 0 LIMIT 1), 
    'ABC Manufacturing Inc.',
    (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1),
    'John Sales Rep',
    'quote_sent', 15750.00, 75, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 14 DAY),
    'PO-ABC-2025-001', 'high', 'Rush order for production line upgrade'
),
(
    UUID(), 'ORD-2025-002',
    (SELECT id FROM accounts WHERE deleted = 0 LIMIT 1 OFFSET 1), 
    'XYZ Industrial Corp.',
    (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1),
    'John Sales Rep',
    'quote_approved', 28900.00, 90, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY),
    'PO-XYZ-2025-002', 'medium', 'Standard delivery terms'
),
(
    UUID(), 'ORD-2025-003',
    (SELECT id FROM accounts WHERE deleted = 0 LIMIT 1), 
    'Global Parts Solutions',
    (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1),
    'John Sales Rep',
    'order_shipped', 8450.00, 95, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY),
    'PO-GPS-2025-003', 'low', 'Regular maintenance parts order'
),
(
    UUID(), 'ORD-2025-004',
    (SELECT id FROM accounts WHERE deleted = 0 LIMIT 1), 
    'TechFlow Manufacturing',
    (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1),
    'John Sales Rep',
    'quote_created', 42300.00, 60, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 21 DAY),
    NULL, 'urgent', 'Emergency replacement parts needed'
),
(
    UUID(), 'ORD-2025-005',
    (SELECT id FROM accounts WHERE deleted = 0 LIMIT 1), 
    'Industrial Systems Ltd.',
    (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1),
    'John Sales Rep',
    'invoice_sent', 19850.00, 100, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY),
    'PO-ISL-2025-005', 'medium', 'Net 30 payment terms'
);

-- 6. Useful Views for Dashboard

-- Pipeline Summary by Stage
CREATE VIEW mfg_pipeline_summary AS
SELECT 
    current_stage,
    COUNT(*) as order_count,
    SUM(total_amount) as total_value,
    AVG(probability_percent) as avg_probability,
    AVG(DATEDIFF(expected_close_date, quote_date)) as avg_days_to_close
FROM mfg_order_pipeline 
WHERE deleted = 0 
GROUP BY current_stage;

-- Rep Performance View
CREATE VIEW mfg_rep_performance AS
SELECT 
    assigned_rep_id,
    assigned_rep_name,
    COUNT(*) as total_orders,
    SUM(CASE WHEN current_stage IN ('payment_received') THEN total_amount ELSE 0 END) as closed_revenue,
    SUM(total_amount) as pipeline_value,
    AVG(probability_percent) as avg_probability,
    COUNT(CASE WHEN current_stage = 'payment_received' THEN 1 END) as closed_orders,
    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_orders
FROM mfg_order_pipeline 
WHERE deleted = 0 
GROUP BY assigned_rep_id, assigned_rep_name;

-- Daily Pipeline Activity
CREATE VIEW mfg_daily_activity AS
SELECT 
    DATE(stage_updated_date) as activity_date,
    current_stage,
    COUNT(*) as stage_changes,
    SUM(total_amount) as value_moved
FROM mfg_order_pipeline 
WHERE deleted = 0 AND stage_updated_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(stage_updated_date), current_stage
ORDER BY activity_date DESC, current_stage;
