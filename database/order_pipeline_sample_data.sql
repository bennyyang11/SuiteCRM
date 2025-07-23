-- =====================================================
-- Order Pipeline Sample Data
-- Manufacturing Order Tracking Dashboard Test Data
-- SuiteCRM Enterprise Legacy Modernization
-- =====================================================

-- =====================================================
-- SAMPLE PIPELINE RECORDS (50+ Records)
-- =====================================================

-- Generate diverse pipeline records across all stages and timeframes
INSERT INTO mfg_order_pipeline (
    id, pipeline_number, opportunity_id, account_id, assigned_user_id,
    current_stage, stage_entered_date, expected_completion_date, total_value,
    priority, client_po_number, notes, date_entered, created_by
) VALUES

-- Quote Requested Stage (Recent Requests)
(UUID(), 'PIPE-2025-001', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_requested', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 15750.00, 'high', 'PO-2025-ABC123', 'Urgent manufacturing order for Q1 production', DATE_SUB(NOW(), INTERVAL 2 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

(UUID(), 'PIPE-2025-002', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_requested', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 45 DAY), 8420.50, 'medium', 'PO-2025-DEF456', 'Standard bracket order for warehouse expansion', DATE_SUB(NOW(), INTERVAL 1 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

(UUID(), 'PIPE-2025-003', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_requested', NOW(), DATE_ADD(CURDATE(), INTERVAL 21 DAY), 42300.00, 'urgent', 'PO-URGENT-789', 'Emergency replacement parts needed ASAP', NOW(), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

-- Quote Prepared Stage
(UUID(), 'PIPE-2025-004', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_prepared', DATE_SUB(NOW(), INTERVAL 3 HOUR), DATE_ADD(CURDATE(), INTERVAL 35 DAY), 22150.75, 'high', 'PO-2025-GHI789', 'Custom fabrication for automotive client', DATE_SUB(NOW(), INTERVAL 2 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

(UUID(), 'PIPE-2025-005', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_prepared', DATE_SUB(NOW(), INTERVAL 6 HOUR), DATE_ADD(CURDATE(), INTERVAL 28 DAY), 7890.25, 'medium', 'PO-STD-456', 'Quarterly maintenance parts order', DATE_SUB(NOW(), INTERVAL 3 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

-- Quote Sent Stage
(UUID(), 'PIPE-2025-006', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_sent', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 18650.00, 'medium', 'PO-SENT-123', 'Quote sent for approval, waiting client response', DATE_SUB(NOW(), INTERVAL 4 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

(UUID(), 'PIPE-2025-007', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_sent', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 35200.50, 'high', 'PO-LARGE-789', 'Large volume order pending approval', DATE_SUB(NOW(), INTERVAL 7 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

-- Quote Approved Stage
(UUID(), 'PIPE-2025-008', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_approved', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 18 DAY), 12750.25, 'medium', 'PO-APPROVED-456', 'Approved, ready to begin manufacturing', DATE_SUB(NOW(), INTERVAL 8 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

(UUID(), 'PIPE-2025-009', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'quote_approved', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 28900.00, 'high', 'PO-PRIORITY-789', 'High priority order, expedited processing', DATE_SUB(NOW(), INTERVAL 10 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

-- Order Processing Stage
(UUID(), 'PIPE-2025-010', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'order_processing', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), 19450.75, 'medium', 'PO-PROCESS-123', 'Currently in manufacturing phase', DATE_SUB(NOW(), INTERVAL 12 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

(UUID(), 'PIPE-2025-011', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'order_processing', DATE_SUB(NOW(), INTERVAL 8 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), 33750.50, 'high', 'PO-MFG-456', 'Manufacturing in progress, 60% complete', DATE_SUB(NOW(), INTERVAL 15 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

-- Ready to Ship Stage
(UUID(), 'PIPE-2025-012', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'ready_to_ship', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 16200.00, 'medium', 'PO-SHIP-789', 'Completed, awaiting shipping arrangement', DATE_SUB(NOW(), INTERVAL 18 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

(UUID(), 'PIPE-2025-013', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'ready_to_ship', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 24800.25, 'high', 'PO-READY-123', 'Quality control passed, ready for shipment', DATE_SUB(NOW(), INTERVAL 20 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

-- Invoiced & Delivered Stage (Recent Completions)
(UUID(), 'PIPE-2025-014', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'invoiced_delivered', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 21350.00, 'medium', 'PO-COMPLETE-456', 'Successfully delivered and invoiced', DATE_SUB(NOW(), INTERVAL 25 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)),

(UUID(), 'PIPE-2025-015', NULL, (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1), 'invoiced_delivered', DATE_SUB(NOW(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 5 DAY), 38900.75, 'high', 'PO-DELIVERED-789', 'Large order completed successfully', DATE_SUB(NOW(), INTERVAL 30 DAY), (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1));

-- Add additional sample records for historical data (30 more records)
INSERT INTO mfg_order_pipeline (
    id, pipeline_number, opportunity_id, account_id, assigned_user_id,
    current_stage, stage_entered_date, expected_completion_date, total_value,
    priority, client_po_number, notes, date_entered, created_by
)
SELECT 
    UUID(),
    CONCAT('PIPE-2024-', LPAD(seq.seq_num + 100, 3, '0')),
    NULL,
    (SELECT id FROM accounts WHERE deleted = 0 ORDER BY RAND() LIMIT 1),
    (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1),
    
    -- Distribute stages realistically
    CASE 
        WHEN seq.seq_num % 7 = 0 THEN 'quote_requested'
        WHEN seq.seq_num % 7 = 1 THEN 'quote_prepared'
        WHEN seq.seq_num % 7 = 2 THEN 'quote_sent'
        WHEN seq.seq_num % 7 = 3 THEN 'quote_approved'
        WHEN seq.seq_num % 7 = 4 THEN 'order_processing'
        WHEN seq.seq_num % 7 = 5 THEN 'ready_to_ship'
        ELSE 'invoiced_delivered'
    END,
    
    DATE_SUB(NOW(), INTERVAL (seq.seq_num * 2) DAY),
    DATE_ADD(CURDATE(), INTERVAL (30 - seq.seq_num) DAY),
    
    -- Realistic value distribution
    ROUND(5000 + (RAND() * 45000), 2),
    
    -- Priority distribution
    CASE 
        WHEN seq.seq_num % 10 < 2 THEN 'urgent'
        WHEN seq.seq_num % 10 < 5 THEN 'high'
        WHEN seq.seq_num % 10 < 8 THEN 'medium'
        ELSE 'low'
    END,
    
    CONCAT('PO-HIST-', LPAD(seq.seq_num + 100, 3, '0')),
    CONCAT('Historical pipeline record #', seq.seq_num + 100),
    DATE_SUB(NOW(), INTERVAL (seq.seq_num * 3) DAY),
    (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' ORDER BY RAND() LIMIT 1)

FROM (
    SELECT @row_number := @row_number + 1 AS seq_num
    FROM information_schema.tables t1, information_schema.tables t2, (SELECT @row_number := 0) r
    LIMIT 30
) seq;

-- =====================================================
-- SAMPLE STAGE HISTORY DATA
-- =====================================================

-- Create realistic stage transition history for existing pipelines
INSERT INTO mfg_pipeline_stage_history (
    id, pipeline_id, from_stage, to_stage, transition_date, 
    transition_user_id, duration_in_previous_stage_hours, notes, date_entered, created_by
)
SELECT 
    UUID(),
    p.id,
    
    -- Determine previous stage based on current stage
    CASE p.current_stage
        WHEN 'quote_prepared' THEN 'quote_requested'
        WHEN 'quote_sent' THEN 'quote_prepared'
        WHEN 'quote_approved' THEN 'quote_sent'
        WHEN 'order_processing' THEN 'quote_approved'
        WHEN 'ready_to_ship' THEN 'order_processing'
        WHEN 'invoiced_delivered' THEN 'ready_to_ship'
        ELSE NULL
    END,
    
    p.current_stage,
    p.stage_entered_date,
    p.assigned_user_id,
    
    -- Realistic duration in previous stage (varies by stage type)
    CASE p.current_stage
        WHEN 'quote_prepared' THEN FLOOR(12 + (RAND() * 48))    -- 12-60 hours
        WHEN 'quote_sent' THEN FLOOR(24 + (RAND() * 72))       -- 24-96 hours
        WHEN 'quote_approved' THEN FLOOR(48 + (RAND() * 120))  -- 48-168 hours
        WHEN 'order_processing' THEN FLOOR(8 + (RAND() * 24))  -- 8-32 hours
        WHEN 'ready_to_ship' THEN FLOOR(120 + (RAND() * 240))  -- 120-360 hours (5-15 days)
        WHEN 'invoiced_delivered' THEN FLOOR(24 + (RAND() * 72)) -- 24-96 hours
        ELSE NULL
    END,
    
    CONCAT('Transitioned to ', p.current_stage, ' stage'),
    p.stage_entered_date,
    p.created_by

FROM mfg_order_pipeline p
WHERE p.deleted = 0 
AND p.current_stage != 'quote_requested'; -- Don't create history for initial stage

-- Create additional historical transitions for completed pipelines
INSERT INTO mfg_pipeline_stage_history (
    id, pipeline_id, from_stage, to_stage, transition_date, 
    transition_user_id, duration_in_previous_stage_hours, notes, date_entered, created_by
)
SELECT 
    UUID(),
    p.id,
    stage_transitions.from_stage,
    stage_transitions.to_stage,
    DATE_SUB(p.stage_entered_date, INTERVAL stage_transitions.days_ago DAY),
    p.assigned_user_id,
    stage_transitions.duration_hours,
    CONCAT('Historical transition: ', stage_transitions.from_stage, ' → ', stage_transitions.to_stage),
    DATE_SUB(p.stage_entered_date, INTERVAL stage_transitions.days_ago DAY),
    p.created_by

FROM mfg_order_pipeline p
CROSS JOIN (
    SELECT 'quote_requested' as from_stage, 'quote_prepared' as to_stage, 5 as days_ago, 36 as duration_hours
    UNION SELECT 'quote_prepared', 'quote_sent', 4, 48
    UNION SELECT 'quote_sent', 'quote_approved', 3, 72
    UNION SELECT 'quote_approved', 'order_processing', 2, 24
    UNION SELECT 'order_processing', 'ready_to_ship', 1, 168
) stage_transitions

WHERE p.deleted = 0 
AND p.current_stage = 'invoiced_delivered'
LIMIT 50; -- Limit to prevent too much data

-- =====================================================
-- SAMPLE NOTIFICATION PREFERENCES
-- =====================================================

-- Set up notification preferences for all active users
INSERT INTO mfg_notification_preferences (
    id, user_id, notification_type, delivery_method, enabled, threshold_hours, date_entered, created_by
)
SELECT 
    UUID(),
    u.id,
    notif_types.notification_type,
    notif_types.delivery_method,
    notif_types.enabled,
    notif_types.threshold_hours,
    NOW(),
    u.id
FROM users u
CROSS JOIN (
    SELECT 'stage_change' as notification_type, 'email' as delivery_method, 1 as enabled, NULL as threshold_hours
    UNION SELECT 'stage_change', 'in_app', 1, NULL
    UNION SELECT 'overdue_alert', 'email', 1, 48
    UNION SELECT 'overdue_alert', 'in_app', 1, 48
    UNION SELECT 'daily_summary', 'email', 1, NULL
    UNION SELECT 'urgent_priority', 'email', 1, NULL
    UNION SELECT 'urgent_priority', 'sms', 0, NULL  -- Disabled by default
    UNION SELECT 'completion_milestone', 'email', 1, NULL
    UNION SELECT 'quote_expiring', 'email', 1, 72
) notif_types
WHERE u.deleted = 0 AND u.status = 'Active'
LIMIT 200; -- Reasonable limit for demo

-- =====================================================
-- SAMPLE NOTIFICATION QUEUE ENTRIES
-- =====================================================

-- Create some sample pending and sent notifications
INSERT INTO mfg_notification_queue (
    id, pipeline_id, recipient_user_id, notification_type, delivery_method,
    subject, message, status, scheduled_time, date_entered, created_by
)
SELECT 
    UUID(),
    p.id,
    p.assigned_user_id,
    'stage_change',
    'email',
    CONCAT('Pipeline ', p.pipeline_number, ' moved to ', p.current_stage),
    CONCAT('Your pipeline ', p.pipeline_number, ' has been moved to the ', REPLACE(p.current_stage, '_', ' '), ' stage.'),
    CASE 
        WHEN RAND() > 0.7 THEN 'sent'
        WHEN RAND() > 0.3 THEN 'pending'
        ELSE 'failed'
    END,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 24) HOUR),
    NOW(),
    p.created_by
FROM mfg_order_pipeline p
WHERE p.deleted = 0 AND p.assigned_user_id IS NOT NULL
LIMIT 25;

-- =====================================================
-- SAMPLE PIPELINE ITEMS (Product Line Items)
-- =====================================================

-- Add realistic line items to pipelines
INSERT INTO mfg_pipeline_items (
    id, pipeline_id, product_sku, product_name, product_description,
    quantity, unit_price, line_total, lead_time_days, item_status,
    date_entered, created_by
)
SELECT 
    UUID(),
    p.id,
    item_data.sku,
    item_data.name,
    item_data.description,
    item_data.quantity,
    item_data.unit_price,
    item_data.quantity * item_data.unit_price,
    item_data.lead_time,
    CASE p.current_stage
        WHEN 'quote_requested' THEN 'pending'
        WHEN 'quote_prepared' THEN 'pending'
        WHEN 'quote_sent' THEN 'pending'
        WHEN 'quote_approved' THEN 'approved'
        WHEN 'order_processing' THEN 'in_production'
        WHEN 'ready_to_ship' THEN 'ready'
        WHEN 'invoiced_delivered' THEN 'delivered'
    END,
    p.date_entered,
    p.created_by
FROM mfg_order_pipeline p
CROSS JOIN (
    SELECT 'MFG-BRACKET-001' as sku, 'Steel Mounting Bracket' as name, 'Heavy-duty steel bracket for industrial mounting' as description, 25 as quantity, 12.50 as unit_price, 7 as lead_time
    UNION SELECT 'MFG-PLATE-002', 'Aluminum Base Plate', 'Lightweight aluminum base plate with precision holes', 50, 8.75, 5
    UNION SELECT 'MFG-BOLT-003', 'Stainless Steel Bolts', 'Grade 8 stainless steel bolts with nuts and washers', 100, 2.25, 1
    UNION SELECT 'MFG-GASKET-004', 'Rubber Gasket Seal', 'Industrial grade rubber gasket for weatherproofing', 75, 3.50, 3
    UNION SELECT 'MFG-PIPE-005', 'Carbon Steel Pipe', '2-inch diameter carbon steel pipe, custom length', 12, 45.00, 14
) item_data
WHERE p.deleted = 0
ORDER BY RAND()
LIMIT 150; -- Multiple items per pipeline

-- Update pipeline totals based on line items
UPDATE mfg_order_pipeline p
SET total_value = (
    SELECT SUM(line_total)
    FROM mfg_pipeline_items i
    WHERE i.pipeline_id = p.id AND i.deleted = 0
)
WHERE p.deleted = 0;

-- =====================================================
-- UPDATE STAGE METRICS
-- =====================================================

-- Calculate and populate stage metrics
INSERT INTO mfg_pipeline_stage_metrics (
    id, stage_name, avg_duration_hours, min_duration_hours, max_duration_hours,
    success_rate, total_entries, current_count, last_calculated,
    calculation_period, data_range_start, data_range_end, created_by
)
SELECT 
    UUID(),
    stage_stats.stage_name,
    stage_stats.avg_duration,
    stage_stats.min_duration,
    stage_stats.max_duration,
    stage_stats.success_rate,
    stage_stats.total_entries,
    stage_stats.current_count,
    NOW(),
    'daily',
    DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    CURDATE(),
    (SELECT id FROM users WHERE deleted = 0 AND status = 'Active' LIMIT 1)
FROM (
    SELECT 
        h.to_stage as stage_name,
        AVG(h.duration_in_previous_stage_hours) as avg_duration,
        MIN(h.duration_in_previous_stage_hours) as min_duration,
        MAX(h.duration_in_previous_stage_hours) as max_duration,
        85.5 as success_rate, -- Simulated success rate
        COUNT(*) as total_entries,
        (SELECT COUNT(*) FROM mfg_order_pipeline p2 WHERE p2.current_stage = h.to_stage AND p2.deleted = 0) as current_count
    FROM mfg_pipeline_stage_history h
    WHERE h.deleted = 0 
    AND h.duration_in_previous_stage_hours IS NOT NULL
    GROUP BY h.to_stage
) stage_stats
ON DUPLICATE KEY UPDATE
    avg_duration_hours = stage_stats.avg_duration,
    min_duration_hours = stage_stats.min_duration,
    max_duration_hours = stage_stats.max_duration,
    last_calculated = NOW();

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Verify data was inserted correctly
SELECT 
    'Pipeline Records' as data_type,
    COUNT(*) as record_count,
    COUNT(DISTINCT current_stage) as distinct_stages,
    COUNT(DISTINCT assigned_user_id) as distinct_users,
    SUM(total_value) as total_pipeline_value
FROM mfg_order_pipeline 
WHERE deleted = 0

UNION ALL

SELECT 
    'Stage History Records',
    COUNT(*),
    COUNT(DISTINCT to_stage),
    COUNT(DISTINCT transition_user_id),
    AVG(duration_in_previous_stage_hours)
FROM mfg_pipeline_stage_history 
WHERE deleted = 0

UNION ALL

SELECT 
    'Notification Preferences',
    COUNT(*),
    COUNT(DISTINCT notification_type),
    COUNT(DISTINCT user_id),
    NULL
FROM mfg_notification_preferences 
WHERE deleted = 0

UNION ALL

SELECT 
    'Pipeline Items',
    COUNT(*),
    COUNT(DISTINCT product_sku),
    COUNT(DISTINCT pipeline_id),
    SUM(line_total)
FROM mfg_pipeline_items 
WHERE deleted = 0;

COMMIT;
