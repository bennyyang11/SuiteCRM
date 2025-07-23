-- ============================================================================
-- SuiteCRM Opportunity-Pipeline Integration Database Schema
-- Connects the manufacturing pipeline system with existing SuiteCRM opportunities
-- ============================================================================

-- Add pipeline-specific fields to opportunities custom table
ALTER TABLE opportunities_cstm 
ADD COLUMN IF NOT EXISTS pipeline_stage_c VARCHAR(50) DEFAULT NULL COMMENT 'Current pipeline stage',
ADD COLUMN IF NOT EXISTS pipeline_id_c VARCHAR(36) DEFAULT NULL COMMENT 'Related pipeline record ID',
ADD COLUMN IF NOT EXISTS expected_ship_date_c DATE DEFAULT NULL COMMENT 'Expected shipping date',
ADD COLUMN IF NOT EXISTS manufacturing_priority_c VARCHAR(20) DEFAULT 'normal' COMMENT 'Manufacturing priority level',
ADD COLUMN IF NOT EXISTS production_notes_c TEXT DEFAULT NULL COMMENT 'Manufacturing production notes',
ADD COLUMN IF NOT EXISTS quality_requirements_c TEXT DEFAULT NULL COMMENT 'Quality and specifications',
ADD COLUMN IF NOT EXISTS delivery_method_c VARCHAR(50) DEFAULT NULL COMMENT 'Preferred delivery method',
ADD COLUMN IF NOT EXISTS special_instructions_c TEXT DEFAULT NULL COMMENT 'Special handling instructions';

-- Create index for pipeline lookups
CREATE INDEX IF NOT EXISTS idx_opportunities_cstm_pipeline 
ON opportunities_cstm (pipeline_id_c);

-- Add foreign key constraint to link pipeline to opportunities
ALTER TABLE mfg_order_pipeline 
ADD COLUMN IF NOT EXISTS opportunity_id VARCHAR(36) DEFAULT NULL COMMENT 'Related opportunity ID',
ADD CONSTRAINT IF NOT EXISTS fk_pipeline_opportunity 
FOREIGN KEY (opportunity_id) REFERENCES opportunities(id) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Create index for opportunity lookups
CREATE INDEX IF NOT EXISTS idx_pipeline_opportunity 
ON mfg_order_pipeline (opportunity_id);

-- Create pipeline stage mapping table
CREATE TABLE IF NOT EXISTS mfg_pipeline_stage_mapping (
    id VARCHAR(36) PRIMARY KEY,
    pipeline_stage VARCHAR(50) NOT NULL COMMENT 'Pipeline stage key',
    opportunity_stage VARCHAR(50) NOT NULL COMMENT 'Opportunity sales stage',
    stage_order INT NOT NULL COMMENT 'Stage sequence order',
    auto_advance BOOLEAN DEFAULT FALSE COMMENT 'Auto-advance opportunity when pipeline reaches this stage',
    requires_approval BOOLEAN DEFAULT FALSE COMMENT 'Stage requires manager approval',
    notification_template VARCHAR(100) DEFAULT NULL COMMENT 'Email template for this stage',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) DEFAULT NULL,
    modified_user_id VARCHAR(36) DEFAULT NULL,
    deleted BOOLEAN DEFAULT FALSE
);

-- Insert default stage mappings
INSERT IGNORE INTO mfg_pipeline_stage_mapping 
(id, pipeline_stage, opportunity_stage, stage_order, auto_advance, requires_approval, notification_template) VALUES
('map_001', 'quote_requested', 'Prospecting', 1, TRUE, FALSE, 'quote_requested_notification'),
('map_002', 'quote_prepared', 'Proposal/Price Quote', 2, TRUE, FALSE, 'quote_prepared_notification'),
('map_003', 'quote_sent', 'Proposal/Price Quote', 3, FALSE, FALSE, 'quote_sent_notification'),
('map_004', 'quote_approved', 'Negotiation/Review', 4, TRUE, TRUE, 'quote_approved_notification'),
('map_005', 'order_processing', 'Closed Won', 5, TRUE, TRUE, 'order_processing_notification'),
('map_006', 'shipped', 'Closed Won', 6, FALSE, FALSE, 'order_shipped_notification'),
('map_007', 'delivered', 'Closed Won', 7, FALSE, FALSE, 'order_delivered_notification');

-- Create opportunity-pipeline sync log table
CREATE TABLE IF NOT EXISTS mfg_opportunity_pipeline_sync_log (
    id VARCHAR(36) PRIMARY KEY,
    opportunity_id VARCHAR(36) NOT NULL,
    pipeline_id VARCHAR(36) DEFAULT NULL,
    sync_type ENUM('create', 'update', 'delete', 'stage_sync') NOT NULL,
    old_values JSON DEFAULT NULL COMMENT 'Previous values before sync',
    new_values JSON DEFAULT NULL COMMENT 'New values after sync',
    sync_status ENUM('pending', 'success', 'failed', 'retry') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    retry_count INT DEFAULT 0,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(36) DEFAULT NULL,
    processed_at DATETIME DEFAULT NULL,
    
    INDEX idx_sync_log_opportunity (opportunity_id),
    INDEX idx_sync_log_pipeline (pipeline_id),
    INDEX idx_sync_log_status (sync_status),
    INDEX idx_sync_log_date (date_created)
);

-- Create trigger for opportunity changes that should sync to pipeline
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_opportunity_pipeline_sync
AFTER UPDATE ON opportunities
FOR EACH ROW
BEGIN
    DECLARE pipeline_id_val VARCHAR(36);
    DECLARE should_sync BOOLEAN DEFAULT FALSE;
    
    -- Get pipeline ID from custom fields
    SELECT pipeline_id_c INTO pipeline_id_val 
    FROM opportunities_cstm 
    WHERE id_c = NEW.id;
    
    -- Check if relevant fields changed
    IF (OLD.sales_stage != NEW.sales_stage OR 
        OLD.amount != NEW.amount OR 
        OLD.date_closed != NEW.date_closed OR
        OLD.assigned_user_id != NEW.assigned_user_id) THEN
        SET should_sync = TRUE;
    END IF;
    
    -- Log sync request if pipeline exists and sync needed
    IF pipeline_id_val IS NOT NULL AND should_sync THEN
        INSERT INTO mfg_opportunity_pipeline_sync_log 
        (id, opportunity_id, pipeline_id, sync_type, old_values, new_values, created_by)
        VALUES (
            UUID(),
            NEW.id,
            pipeline_id_val,
            'stage_sync',
            JSON_OBJECT(
                'sales_stage', OLD.sales_stage,
                'amount', OLD.amount,
                'date_closed', OLD.date_closed,
                'assigned_user_id', OLD.assigned_user_id
            ),
            JSON_OBJECT(
                'sales_stage', NEW.sales_stage,
                'amount', NEW.amount,
                'date_closed', NEW.date_closed,
                'assigned_user_id', NEW.assigned_user_id
            ),
            NEW.modified_user_id
        );
    END IF;
END//
DELIMITER ;

-- Create pipeline integration configuration table
CREATE TABLE IF NOT EXISTS mfg_pipeline_integration_config (
    id VARCHAR(36) PRIMARY KEY,
    config_key VARCHAR(100) NOT NULL UNIQUE,
    config_value TEXT NOT NULL,
    config_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by VARCHAR(36) DEFAULT NULL,
    modified_user_id VARCHAR(36) DEFAULT NULL
);

-- Insert default integration configuration
INSERT IGNORE INTO mfg_pipeline_integration_config 
(id, config_key, config_value, config_type, description) VALUES
('cfg_001', 'auto_create_pipeline', 'true', 'boolean', 'Automatically create pipeline when opportunity reaches quote stage'),
('cfg_002', 'sync_opportunity_values', 'true', 'boolean', 'Keep opportunity amounts in sync with pipeline values'),
('cfg_003', 'require_po_number', 'true', 'boolean', 'Require PO number before advancing to processing stage'),
('cfg_004', 'auto_close_opportunity', 'true', 'boolean', 'Automatically close opportunity when order is delivered'),
('cfg_005', 'notification_delay_minutes', '5', 'number', 'Delay before sending stage change notifications'),
('cfg_006', 'max_sync_retries', '3', 'number', 'Maximum number of sync retry attempts'),
('cfg_007', 'required_opportunity_fields', '["account_id", "amount", "sales_stage"]', 'json', 'Required opportunity fields for pipeline creation'),
('cfg_008', 'pipeline_prefix', 'ORD-', 'string', 'Prefix for pipeline order numbers'),
('cfg_009', 'enable_revenue_recognition', 'true', 'boolean', 'Enable automatic revenue recognition based on pipeline stages'),
('cfg_010', 'territory_sync_enabled', 'true', 'boolean', 'Sync territory assignments between opportunities and pipeline');

-- Create view for integrated opportunity-pipeline data
CREATE VIEW IF NOT EXISTS v_opportunity_pipeline_integrated AS
SELECT 
    o.id as opportunity_id,
    o.name as opportunity_name,
    o.sales_stage as opportunity_stage,
    o.amount as opportunity_amount,
    o.date_closed as opportunity_close_date,
    o.assigned_user_id as opportunity_owner,
    u1.user_name as opportunity_owner_name,
    
    p.id as pipeline_id,
    p.order_number as pipeline_order_number,
    p.stage_id as pipeline_stage_id,
    ps.name as pipeline_stage_name,
    p.total_value as pipeline_value,
    p.expected_close_date as pipeline_close_date,
    p.assigned_user_id as pipeline_owner,
    u2.user_name as pipeline_owner_name,
    p.priority,
    p.is_overdue,
    
    oc.pipeline_stage_c as custom_pipeline_stage,
    oc.expected_ship_date_c as expected_ship_date,
    oc.manufacturing_priority_c as manufacturing_priority,
    oc.delivery_method_c as delivery_method,
    
    a.name as account_name,
    a.billing_address_city,
    a.billing_address_state,
    a.billing_address_country,
    
    -- Calculated fields
    CASE 
        WHEN p.total_value IS NOT NULL AND o.amount IS NOT NULL THEN
            ABS(p.total_value - o.amount)
        ELSE 0
    END as value_variance,
    
    CASE
        WHEN p.expected_close_date IS NOT NULL AND o.date_closed IS NOT NULL THEN
            DATEDIFF(p.expected_close_date, o.date_closed)
        ELSE 0
    END as date_variance,
    
    CASE
        WHEN p.id IS NOT NULL THEN 'Linked'
        WHEN o.sales_stage IN ('Proposal/Price Quote', 'Negotiation/Review') THEN 'Eligible'
        ELSE 'Not Applicable'
    END as integration_status

FROM opportunities o
LEFT JOIN opportunities_cstm oc ON o.id = oc.id_c
LEFT JOIN mfg_order_pipeline p ON oc.pipeline_id_c = p.id
LEFT JOIN mfg_pipeline_stages ps ON p.stage_id = ps.id
LEFT JOIN accounts a ON o.account_id = a.id
LEFT JOIN users u1 ON o.assigned_user_id = u1.id
LEFT JOIN users u2 ON p.assigned_user_id = u2.id
WHERE o.deleted = 0 
  AND (p.deleted = 0 OR p.deleted IS NULL);

-- Create indexes for the view performance
CREATE INDEX IF NOT EXISTS idx_opportunities_sales_stage ON opportunities (sales_stage);
CREATE INDEX IF NOT EXISTS idx_opportunities_assigned_user ON opportunities (assigned_user_id);
CREATE INDEX IF NOT EXISTS idx_opportunities_account ON opportunities (account_id);

-- Grant permissions for integration user
-- GRANT SELECT, INSERT, UPDATE ON mfg_pipeline_stage_mapping TO 'suitecrm_user'@'%';
-- GRANT SELECT, INSERT, UPDATE ON mfg_opportunity_pipeline_sync_log TO 'suitecrm_user'@'%';
-- GRANT SELECT, INSERT, UPDATE ON mfg_pipeline_integration_config TO 'suitecrm_user'@'%';
-- GRANT SELECT ON v_opportunity_pipeline_integrated TO 'suitecrm_user'@'%';

-- ============================================================================
-- Data Migration Script for Existing Opportunities
-- Run this to link existing opportunities with potential pipeline records
-- ============================================================================

-- Create temporary procedure for data migration
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS MigratePipelineOpportunityLinks()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE opp_id VARCHAR(36);
    DECLARE opp_amount DECIMAL(26,6);
    DECLARE opp_name VARCHAR(255);
    DECLARE account_id VARCHAR(36);
    
    DECLARE opp_cursor CURSOR FOR 
        SELECT o.id, o.amount, o.name, o.account_id
        FROM opportunities o
        LEFT JOIN opportunities_cstm oc ON o.id = oc.id_c
        WHERE o.sales_stage IN ('Proposal/Price Quote', 'Negotiation/Review', 'Closed Won')
          AND o.deleted = 0
          AND (oc.pipeline_id_c IS NULL OR oc.pipeline_id_c = '');
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN opp_cursor;
    
    migration_loop: LOOP
        FETCH opp_cursor INTO opp_id, opp_amount, opp_name, account_id;
        IF done THEN
            LEAVE migration_loop;
        END IF;
        
        -- Create pipeline record for eligible opportunities
        CALL CreatePipelineFromOpportunity(opp_id);
        
    END LOOP;
    
    CLOSE opp_cursor;
END//
DELIMITER ;

-- Create procedure to create pipeline from opportunity
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS CreatePipelineFromOpportunity(IN opp_id VARCHAR(36))
BEGIN
    DECLARE pipeline_id VARCHAR(36);
    DECLARE order_number VARCHAR(50);
    DECLARE stage_id VARCHAR(36);
    
    -- Generate new pipeline ID and order number
    SET pipeline_id = UUID();
    SET order_number = CONCAT('ORD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', SUBSTRING(pipeline_id, 1, 8));
    
    -- Get initial stage ID
    SELECT id INTO stage_id FROM mfg_pipeline_stages WHERE stage_key = 'quote_prepared' LIMIT 1;
    
    -- Insert pipeline record
    INSERT INTO mfg_order_pipeline (
        id, order_number, stage_id, opportunity_id, 
        account_id, assigned_user_id, total_value, priority,
        date_created, created_by, deleted
    )
    SELECT 
        pipeline_id,
        order_number,
        stage_id,
        o.id,
        o.account_id,
        o.assigned_user_id,
        COALESCE(o.amount, 0),
        'normal',
        NOW(),
        o.assigned_user_id,
        0
    FROM opportunities o 
    WHERE o.id = opp_id;
    
    -- Update opportunity custom fields
    INSERT INTO opportunities_cstm (id_c, pipeline_id_c, pipeline_stage_c, manufacturing_priority_c)
    VALUES (opp_id, pipeline_id, 'quote_prepared', 'normal')
    ON DUPLICATE KEY UPDATE 
        pipeline_id_c = pipeline_id,
        pipeline_stage_c = 'quote_prepared',
        manufacturing_priority_c = COALESCE(manufacturing_priority_c, 'normal');
        
    -- Log the creation
    INSERT INTO mfg_opportunity_pipeline_sync_log 
    (id, opportunity_id, pipeline_id, sync_type, new_values, sync_status, created_by, processed_at)
    VALUES (
        UUID(), opp_id, pipeline_id, 'create',
        JSON_OBJECT('pipeline_id', pipeline_id, 'order_number', order_number),
        'success', 'system', NOW()
    );
END//
DELIMITER ;

-- Performance optimization queries
ANALYZE TABLE opportunities;
ANALYZE TABLE opportunities_cstm;
ANALYZE TABLE mfg_order_pipeline;
ANALYZE TABLE mfg_pipeline_stages;

-- Cleanup old sync logs (keep last 30 days)
-- DELETE FROM mfg_opportunity_pipeline_sync_log 
-- WHERE date_created < DATE_SUB(NOW(), INTERVAL 30 DAY);

SELECT 'Pipeline-Opportunity integration schema created successfully' as result;
