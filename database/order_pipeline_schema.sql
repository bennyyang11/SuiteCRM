-- =====================================================
-- Order Pipeline Database Schema
-- Manufacturing Order Tracking Dashboard
-- SuiteCRM Enterprise Legacy Modernization
-- =====================================================

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 1. CORE PIPELINE TABLE (7-Stage Order Tracking)
-- =====================================================

CREATE TABLE IF NOT EXISTS mfg_order_pipeline (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    pipeline_number VARCHAR(50) NOT NULL UNIQUE,
    opportunity_id VARCHAR(36) NULL,                    -- Link to SuiteCRM Opportunities
    account_id VARCHAR(36) NOT NULL,                    -- Link to SuiteCRM Accounts
    assigned_user_id VARCHAR(36) NULL,                  -- Sales rep responsible
    
    -- Pipeline Stage Management
    current_stage ENUM(
        'quote_requested',      -- 1. Initial quote request from client
        'quote_prepared',       -- 2. Sales team preparing detailed quote
        'quote_sent',           -- 3. Quote delivered to client for review
        'quote_approved',       -- 4. Client approves quote terms
        'order_processing',     -- 5. Manufacturing/procurement begins
        'ready_to_ship',        -- 6. Order completed, awaiting shipment
        'invoiced_delivered'    -- 7. Final delivery and billing complete
    ) NOT NULL DEFAULT 'quote_requested',
    
    -- Timeline Management
    stage_entered_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_completion_date DATE NULL,
    actual_completion_date DATE NULL,
    
    -- Financial Information
    total_value DECIMAL(18,2) DEFAULT 0.00,
    currency_id VARCHAR(36) DEFAULT 'USD',
    
    -- Priority and Status
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('active', 'on_hold', 'cancelled', 'completed') DEFAULT 'active',
    
    -- Client Information
    client_po_number VARCHAR(100) NULL,
    client_contact_id VARCHAR(36) NULL,                 -- Primary client contact
    
    -- Additional Details
    notes TEXT NULL,
    internal_notes TEXT NULL,                           -- Private sales team notes
    
    -- Shipping Information
    shipping_address TEXT NULL,
    shipping_method VARCHAR(100) NULL,
    tracking_number VARCHAR(100) NULL,
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    modified_user_id VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Indexes for Performance
    INDEX idx_pipeline_stage (current_stage, deleted),
    INDEX idx_pipeline_assigned (assigned_user_id, deleted),
    INDEX idx_pipeline_account (account_id, deleted),
    INDEX idx_pipeline_opportunity (opportunity_id),
    INDEX idx_pipeline_status (status, deleted),
    INDEX idx_pipeline_priority (priority, deleted),
    INDEX idx_pipeline_date (date_entered, deleted),
    INDEX idx_pipeline_number (pipeline_number),
    
    -- Foreign Key Constraints
    CONSTRAINT fk_pipeline_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
    CONSTRAINT fk_pipeline_opportunity FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) ON DELETE SET NULL,
    CONSTRAINT fk_pipeline_assigned_user FOREIGN KEY (assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_pipeline_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_pipeline_modified_by FOREIGN KEY (modified_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. STAGE TRANSITION HISTORY TRACKING
-- =====================================================

CREATE TABLE IF NOT EXISTS mfg_pipeline_stage_history (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    pipeline_id VARCHAR(36) NOT NULL,
    
    -- Stage Transition Details
    from_stage VARCHAR(50) NULL,                        -- NULL for initial stage
    to_stage VARCHAR(50) NOT NULL,
    transition_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    transition_user_id VARCHAR(36) NULL,                -- User who made the transition
    
    -- Duration Tracking
    duration_in_previous_stage_hours INT NULL,          -- Hours spent in previous stage
    expected_duration_hours INT NULL,                   -- Expected duration for this stage
    
    -- Transition Context
    transition_reason TEXT NULL,                        -- Why the transition occurred
    notes TEXT NULL,                                    -- Additional transition notes
    automated_transition TINYINT(1) DEFAULT 0,         -- System vs manual transition
    
    -- Workflow Information
    workflow_rule_id VARCHAR(36) NULL,                 -- If triggered by workflow
    approval_required TINYINT(1) DEFAULT 0,            -- If approval was required
    approved_by VARCHAR(36) NULL,                      -- Who approved the transition
    approval_date DATETIME NULL,
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Indexes for Performance
    INDEX idx_history_pipeline (pipeline_id, transition_date),
    INDEX idx_history_stage (to_stage, transition_date),
    INDEX idx_history_user (transition_user_id, transition_date),
    INDEX idx_history_duration (duration_in_previous_stage_hours),
    
    -- Foreign Key Constraints
    CONSTRAINT fk_history_pipeline FOREIGN KEY (pipeline_id) REFERENCES mfg_order_pipeline(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_transition_user FOREIGN KEY (transition_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_history_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_history_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. STAGE PERFORMANCE METRICS
-- =====================================================

CREATE TABLE IF NOT EXISTS mfg_pipeline_stage_metrics (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    stage_name VARCHAR(50) NOT NULL,
    
    -- Performance Metrics
    avg_duration_hours DECIMAL(8,2) DEFAULT 0.00,
    min_duration_hours INT DEFAULT 0,
    max_duration_hours INT DEFAULT 0,
    median_duration_hours DECIMAL(8,2) DEFAULT 0.00,
    
    -- Success Metrics
    success_rate DECIMAL(5,2) DEFAULT 0.00,            -- % that progress to next stage
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,         -- % that complete entire pipeline
    bottleneck_score DECIMAL(5,2) DEFAULT 0.00,        -- Performance indicator (0-100)
    
    -- Volume Metrics
    total_entries INT DEFAULT 0,
    current_count INT DEFAULT 0,                        -- Currently in this stage
    
    -- Calculation Details
    last_calculated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    calculation_period ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
    data_range_start DATE NOT NULL,
    data_range_end DATE NOT NULL,
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Indexes
    INDEX idx_metrics_stage (stage_name, calculation_period),
    INDEX idx_metrics_calculated (last_calculated),
    UNIQUE KEY unique_stage_period (stage_name, calculation_period, data_range_start, data_range_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. NOTIFICATION PREFERENCES
-- =====================================================

CREATE TABLE IF NOT EXISTS mfg_notification_preferences (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    
    -- Notification Types
    notification_type ENUM(
        'stage_change',         -- When pipeline stage changes
        'overdue_alert',        -- When pipeline is overdue
        'daily_summary',        -- Daily pipeline summary
        'weekly_report',        -- Weekly performance report
        'client_update',        -- Client-facing updates
        'urgent_priority',      -- Urgent priority items
        'completion_milestone', -- Major milestone completions
        'bottleneck_alert',     -- Performance bottleneck alerts
        'approval_required',    -- When approval is needed
        'quote_expiring'        -- When quotes are about to expire
    ) NOT NULL,
    
    -- Delivery Methods
    delivery_method ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    
    -- Scheduling
    schedule_time TIME NULL,                            -- For daily summaries
    schedule_day ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NULL,
    
    -- Thresholds
    threshold_hours INT NULL,                           -- For overdue alerts
    threshold_amount DECIMAL(18,2) NULL,               -- For high-value alerts
    
    -- Template and Formatting
    email_template_id VARCHAR(36) NULL,
    message_format ENUM('full', 'summary', 'minimal') DEFAULT 'summary',
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    modified_user_id VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Indexes
    INDEX idx_notif_user (user_id, enabled),
    INDEX idx_notif_type (notification_type, enabled),
    INDEX idx_notif_method (delivery_method, enabled),
    
    -- Unique Constraint
    UNIQUE KEY unique_user_notification (user_id, notification_type, delivery_method),
    
    -- Foreign Key Constraints
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. NOTIFICATION QUEUE AND DELIVERY
-- =====================================================

CREATE TABLE IF NOT EXISTS mfg_notification_queue (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    pipeline_id VARCHAR(36) NULL,                      -- Related pipeline (optional)
    recipient_user_id VARCHAR(36) NOT NULL,
    
    -- Notification Details
    notification_type VARCHAR(50) NOT NULL,
    delivery_method ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    
    -- Message Content
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    message_html TEXT NULL,                             -- HTML version for emails
    action_url VARCHAR(500) NULL,                       -- Click-through URL
    
    -- Scheduling and Delivery
    status ENUM('pending', 'sent', 'failed', 'cancelled', 'read') DEFAULT 'pending',
    scheduled_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_time DATETIME NULL,
    read_time DATETIME NULL,                            -- For in-app notifications
    
    -- Delivery Tracking
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT NULL,
    external_id VARCHAR(255) NULL,                     -- SMS/Push service ID
    
    -- Batch Processing
    batch_id VARCHAR(36) NULL,                          -- For bulk notifications
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Indexes
    INDEX idx_queue_status (status, scheduled_time),
    INDEX idx_queue_recipient (recipient_user_id, status),
    INDEX idx_queue_pipeline (pipeline_id, notification_type),
    INDEX idx_queue_batch (batch_id),
    INDEX idx_queue_type (notification_type, status),
    
    -- Foreign Key Constraints
    CONSTRAINT fk_queue_pipeline FOREIGN KEY (pipeline_id) REFERENCES mfg_order_pipeline(id) ON DELETE CASCADE,
    CONSTRAINT fk_queue_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. PIPELINE ITEMS (Individual Products/Services)
-- =====================================================

CREATE TABLE IF NOT EXISTS mfg_pipeline_items (
    id VARCHAR(36) NOT NULL PRIMARY KEY,
    pipeline_id VARCHAR(36) NOT NULL,
    
    -- Product Information
    product_id VARCHAR(36) NULL,                        -- Link to products table
    product_sku VARCHAR(100) NULL,
    product_name VARCHAR(255) NOT NULL,
    product_description TEXT NULL,
    
    -- Quantities and Pricing
    quantity DECIMAL(18,4) NOT NULL DEFAULT 1.0000,
    unit_price DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    discount_percent DECIMAL(5,2) DEFAULT 0.00,
    discount_amount DECIMAL(18,2) DEFAULT 0.00,
    line_total DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    
    -- Manufacturing Details
    lead_time_days INT NULL,
    manufacturing_notes TEXT NULL,
    special_requirements TEXT NULL,
    
    -- Status Tracking
    item_status ENUM('pending', 'approved', 'in_production', 'ready', 'shipped', 'delivered') DEFAULT 'pending',
    
    -- SuiteCRM Standard Fields
    date_entered DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) NULL,
    modified_user_id VARCHAR(36) NULL,
    deleted TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Indexes
    INDEX idx_items_pipeline (pipeline_id, deleted),
    INDEX idx_items_product (product_id),
    INDEX idx_items_status (item_status),
    
    -- Foreign Key Constraints
    CONSTRAINT fk_items_pipeline FOREIGN KEY (pipeline_id) REFERENCES mfg_order_pipeline(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS FOR AUTOMATED STAGE TRACKING
-- =====================================================

DELIMITER $$

-- Trigger to automatically create stage history when pipeline stage changes
CREATE TRIGGER tr_pipeline_stage_change 
AFTER UPDATE ON mfg_order_pipeline
FOR EACH ROW
BEGIN
    DECLARE previous_stage_duration INT DEFAULT 0;
    
    -- Only process if stage actually changed
    IF OLD.current_stage != NEW.current_stage THEN
        
        -- Calculate duration in previous stage
        SELECT TIMESTAMPDIFF(HOUR, OLD.stage_entered_date, NOW()) INTO previous_stage_duration;
        
        -- Insert stage history record
        INSERT INTO mfg_pipeline_stage_history (
            id,
            pipeline_id,
            from_stage,
            to_stage,
            transition_date,
            transition_user_id,
            duration_in_previous_stage_hours,
            automated_transition,
            date_entered,
            created_by
        ) VALUES (
            UUID(),
            NEW.id,
            OLD.current_stage,
            NEW.current_stage,
            NOW(),
            NEW.modified_user_id,
            previous_stage_duration,
            0, -- Assume manual transition unless specified otherwise
            NOW(),
            NEW.modified_user_id
        );
        
        -- Update stage entered date for new stage
        UPDATE mfg_order_pipeline 
        SET stage_entered_date = NOW() 
        WHERE id = NEW.id;
        
    END IF;
END$$

-- Trigger to update line totals when pipeline items change
CREATE TRIGGER tr_pipeline_item_update
AFTER UPDATE ON mfg_pipeline_items
FOR EACH ROW
BEGIN
    DECLARE pipeline_total DECIMAL(18,2) DEFAULT 0.00;
    
    -- Calculate new line total
    UPDATE mfg_pipeline_items 
    SET line_total = (quantity * unit_price) - discount_amount - ((quantity * unit_price) * discount_percent / 100)
    WHERE id = NEW.id;
    
    -- Update pipeline total
    SELECT SUM(line_total) INTO pipeline_total
    FROM mfg_pipeline_items 
    WHERE pipeline_id = NEW.pipeline_id AND deleted = 0;
    
    UPDATE mfg_order_pipeline 
    SET total_value = pipeline_total
    WHERE id = NEW.pipeline_id;
END$$

DELIMITER ;

-- =====================================================
-- INITIAL DATA SETUP
-- =====================================================

-- Insert default notification preferences for system admin
INSERT IGNORE INTO mfg_notification_preferences (id, user_id, notification_type, delivery_method, enabled) 
SELECT 
    UUID() as id,
    u.id as user_id,
    'stage_change' as notification_type,
    'email' as delivery_method,
    1 as enabled
FROM users u 
WHERE u.deleted = 0 AND u.status = 'Active'
LIMIT 10;

-- Initialize stage metrics table with default stages
INSERT IGNORE INTO mfg_pipeline_stage_metrics (id, stage_name, calculation_period, data_range_start, data_range_end) VALUES
(UUID(), 'quote_requested', 'daily', CURDATE() - INTERVAL 30 DAY, CURDATE()),
(UUID(), 'quote_prepared', 'daily', CURDATE() - INTERVAL 30 DAY, CURDATE()),
(UUID(), 'quote_sent', 'daily', CURDATE() - INTERVAL 30 DAY, CURDATE()),
(UUID(), 'quote_approved', 'daily', CURDATE() - INTERVAL 30 DAY, CURDATE()),
(UUID(), 'order_processing', 'daily', CURDATE() - INTERVAL 30 DAY, CURDATE()),
(UUID(), 'ready_to_ship', 'daily', CURDATE() - INTERVAL 30 DAY, CURDATE()),
(UUID(), 'invoiced_delivered', 'daily', CURDATE() - INTERVAL 30 DAY, CURDATE());

-- =====================================================
-- PERFORMANCE OPTIMIZATION
-- =====================================================

-- Additional composite indexes for common queries
CREATE INDEX idx_pipeline_stage_date ON mfg_order_pipeline(current_stage, date_entered, deleted);
CREATE INDEX idx_pipeline_user_stage ON mfg_order_pipeline(assigned_user_id, current_stage, deleted);
CREATE INDEX idx_history_pipeline_date ON mfg_pipeline_stage_history(pipeline_id, transition_date DESC);

-- Optimize for dashboard queries
CREATE INDEX idx_pipeline_dashboard ON mfg_order_pipeline(assigned_user_id, current_stage, priority, deleted);

COMMIT;
