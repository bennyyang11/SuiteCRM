-- =====================================================
-- Order Pipeline Analytics Views
-- Manufacturing Order Tracking Dashboard Analytics
-- SuiteCRM Enterprise Legacy Modernization
-- =====================================================

-- =====================================================
-- 1. PIPELINE PERFORMANCE DASHBOARD VIEW
-- =====================================================

CREATE OR REPLACE VIEW mfg_pipeline_analytics AS
SELECT 
    DATE(p.date_entered) as pipeline_date,
    p.current_stage,
    p.assigned_user_id,
    u.user_name as assigned_user_name,
    u.first_name,
    u.last_name,
    
    -- Stage Counts
    COUNT(*) as stage_count,
    COUNT(CASE WHEN p.priority = 'urgent' THEN 1 END) as urgent_count,
    COUNT(CASE WHEN p.priority = 'high' THEN 1 END) as high_priority_count,
    COUNT(CASE WHEN p.status = 'on_hold' THEN 1 END) as on_hold_count,
    
    -- Financial Metrics
    AVG(p.total_value) as avg_value,
    SUM(p.total_value) as total_value,
    MIN(p.total_value) as min_value,
    MAX(p.total_value) as max_value,
    
    -- Time Metrics
    AVG(DATEDIFF(NOW(), p.stage_entered_date)) as avg_days_in_stage,
    MIN(DATEDIFF(NOW(), p.stage_entered_date)) as min_days_in_stage,
    MAX(DATEDIFF(NOW(), p.stage_entered_date)) as max_days_in_stage,
    AVG(DATEDIFF(NOW(), p.date_entered)) as avg_total_pipeline_days,
    
    -- Completion Metrics
    COUNT(CASE WHEN p.current_stage = 'invoiced_delivered' THEN 1 END) as completed_count,
    COUNT(CASE WHEN p.expected_completion_date < CURDATE() AND p.current_stage != 'invoiced_delivered' THEN 1 END) as overdue_count
    
FROM mfg_order_pipeline p
LEFT JOIN users u ON p.assigned_user_id = u.id
WHERE p.deleted = 0
GROUP BY DATE(p.date_entered), p.current_stage, p.assigned_user_id, u.user_name, u.first_name, u.last_name;

-- =====================================================
-- 2. CONVERSION FUNNEL ANALYTICS
-- =====================================================

CREATE OR REPLACE VIEW mfg_pipeline_funnel AS
SELECT 
    stage_info.stage_name,
    stage_info.stage_order,
    stage_info.stage_display_name,
    COALESCE(stage_counts.count, 0) as count,
    COALESCE(stage_counts.total_value, 0) as total_value,
    COALESCE(stage_counts.avg_value, 0) as avg_value,
    ROUND(
        (COALESCE(stage_counts.count, 0) * 100.0 / 
         NULLIF((SELECT COUNT(*) FROM mfg_order_pipeline WHERE deleted = 0), 0)
        ), 2
    ) as percentage_of_total,
    
    -- Conversion rates (percentage that reach this stage from previous)
    CASE 
        WHEN stage_info.stage_order = 1 THEN 100.00
        ELSE ROUND(
            (COALESCE(stage_counts.count, 0) * 100.0 / 
             NULLIF((
                SELECT COUNT(*) 
                FROM mfg_order_pipeline p2
                WHERE p2.deleted = 0 
                AND p2.current_stage IN (
                    SELECT s2.stage_name 
                    FROM (
                        SELECT 'quote_requested' as stage_name, 1 as stage_order
                        UNION SELECT 'quote_prepared', 2
                        UNION SELECT 'quote_sent', 3
                        UNION SELECT 'quote_approved', 4
                        UNION SELECT 'order_processing', 5
                        UNION SELECT 'ready_to_ship', 6
                        UNION SELECT 'invoiced_delivered', 7
                    ) s2 
                    WHERE s2.stage_order >= stage_info.stage_order
                )
             ), 0)
            ), 2
        )
    END as conversion_rate

FROM (
    SELECT 'quote_requested' as stage_name, 1 as stage_order, 'Quote Requested' as stage_display_name
    UNION SELECT 'quote_prepared', 2, 'Quote Prepared'
    UNION SELECT 'quote_sent', 3, 'Quote Sent'
    UNION SELECT 'quote_approved', 4, 'Quote Approved'
    UNION SELECT 'order_processing', 5, 'Order Processing'
    UNION SELECT 'ready_to_ship', 6, 'Ready to Ship'
    UNION SELECT 'invoiced_delivered', 7, 'Invoiced & Delivered'
) stage_info

LEFT JOIN (
    SELECT 
        p.current_stage,
        COUNT(*) as count,
        SUM(p.total_value) as total_value,
        AVG(p.total_value) as avg_value
    FROM mfg_order_pipeline p
    WHERE p.deleted = 0
    GROUP BY p.current_stage
) stage_counts ON stage_info.stage_name = stage_counts.current_stage

ORDER BY stage_info.stage_order;

-- =====================================================
-- 3. SALES REP PERFORMANCE VIEW
-- =====================================================

CREATE OR REPLACE VIEW mfg_sales_rep_performance AS
SELECT 
    u.id as user_id,
    u.user_name,
    CONCAT(u.first_name, ' ', u.last_name) as full_name,
    u.department,
    
    -- Pipeline Counts
    COUNT(p.id) as total_pipelines,
    COUNT(CASE WHEN p.current_stage = 'invoiced_delivered' THEN 1 END) as completed_sales,
    COUNT(CASE WHEN p.current_stage != 'invoiced_delivered' AND p.status = 'active' THEN 1 END) as active_pipelines,
    COUNT(CASE WHEN p.priority = 'urgent' THEN 1 END) as urgent_pipelines,
    
    -- Financial Performance
    SUM(CASE WHEN p.current_stage = 'invoiced_delivered' THEN p.total_value ELSE 0 END) as total_revenue,
    AVG(CASE WHEN p.current_stage = 'invoiced_delivered' THEN p.total_value END) as avg_deal_size,
    SUM(CASE WHEN p.current_stage != 'invoiced_delivered' AND p.status = 'active' THEN p.total_value ELSE 0 END) as pipeline_value,
    
    -- Performance Metrics
    ROUND(
        (COUNT(CASE WHEN p.current_stage = 'invoiced_delivered' THEN 1 END) * 100.0 / 
         NULLIF(COUNT(p.id), 0)
        ), 2
    ) as close_rate,
    
    -- Time Metrics
    AVG(CASE 
        WHEN p.current_stage = 'invoiced_delivered' THEN 
            DATEDIFF(
                COALESCE(p.actual_completion_date, 
                    (SELECT MAX(h.transition_date) 
                     FROM mfg_pipeline_stage_history h 
                     WHERE h.pipeline_id = p.id AND h.to_stage = 'invoiced_delivered')
                ),
                p.date_entered
            )
    END) as avg_completion_days,
    
    AVG(DATEDIFF(NOW(), p.stage_entered_date)) as avg_days_in_current_stage,
    
    -- Overdue Analysis
    COUNT(CASE 
        WHEN p.expected_completion_date < CURDATE() 
        AND p.current_stage != 'invoiced_delivered' 
        AND p.status = 'active' 
        THEN 1 
    END) as overdue_pipelines,
    
    -- Recent Activity
    COUNT(CASE WHEN p.date_entered >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as pipelines_last_30_days,
    COUNT(CASE WHEN p.date_modified >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as updated_last_7_days

FROM users u
LEFT JOIN mfg_order_pipeline p ON u.id = p.assigned_user_id AND p.deleted = 0
WHERE u.deleted = 0 AND u.status = 'Active'
GROUP BY u.id, u.user_name, u.first_name, u.last_name, u.department
HAVING total_pipelines > 0
ORDER BY total_revenue DESC, close_rate DESC;

-- =====================================================
-- 4. STAGE DURATION ANALYSIS VIEW
-- =====================================================

CREATE OR REPLACE VIEW mfg_stage_duration_analysis AS
SELECT 
    h.to_stage as stage_name,
    stage_display.display_name,
    
    -- Duration Statistics
    COUNT(*) as total_transitions,
    AVG(h.duration_in_previous_stage_hours) as avg_hours,
    MIN(h.duration_in_previous_stage_hours) as min_hours,
    MAX(h.duration_in_previous_stage_hours) as max_hours,
    
    -- Convert to days for readability
    ROUND(AVG(h.duration_in_previous_stage_hours) / 24, 1) as avg_days,
    ROUND(MIN(h.duration_in_previous_stage_hours) / 24, 1) as min_days,
    ROUND(MAX(h.duration_in_previous_stage_hours) / 24, 1) as max_days,
    
    -- Percentiles
    (SELECT duration_in_previous_stage_hours 
     FROM mfg_pipeline_stage_history h2 
     WHERE h2.to_stage = h.to_stage AND h2.duration_in_previous_stage_hours IS NOT NULL
     ORDER BY h2.duration_in_previous_stage_hours 
     LIMIT 1 OFFSET (FLOOR(0.50 * COUNT(*)))
    ) as median_hours,
    
    -- Performance Indicators
    SUM(CASE WHEN h.automated_transition = 1 THEN 1 ELSE 0 END) as automated_transitions,
    ROUND(
        (SUM(CASE WHEN h.automated_transition = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2
    ) as automation_percentage,
    
    -- Recent Performance (Last 30 days)
    COUNT(CASE WHEN h.transition_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_transitions,
    AVG(CASE 
        WHEN h.transition_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
        THEN h.duration_in_previous_stage_hours 
    END) as recent_avg_hours

FROM mfg_pipeline_stage_history h
LEFT JOIN (
    SELECT 'quote_requested' as stage_name, 'Quote Requested' as display_name
    UNION SELECT 'quote_prepared', 'Quote Prepared'
    UNION SELECT 'quote_sent', 'Quote Sent'
    UNION SELECT 'quote_approved', 'Quote Approved'
    UNION SELECT 'order_processing', 'Order Processing'
    UNION SELECT 'ready_to_ship', 'Ready to Ship'
    UNION SELECT 'invoiced_delivered', 'Invoiced & Delivered'
) stage_display ON h.to_stage = stage_display.stage_name

WHERE h.deleted = 0 
AND h.duration_in_previous_stage_hours IS NOT NULL
AND h.duration_in_previous_stage_hours > 0

GROUP BY h.to_stage, stage_display.display_name
ORDER BY avg_hours DESC;

-- =====================================================
-- 5. PIPELINE HEALTH DASHBOARD VIEW
-- =====================================================

CREATE OR REPLACE VIEW mfg_pipeline_health_dashboard AS
SELECT 
    'overall' as metric_category,
    
    -- Overall Pipeline Health
    COUNT(*) as total_active_pipelines,
    SUM(p.total_value) as total_pipeline_value,
    AVG(p.total_value) as avg_pipeline_value,
    
    -- Stage Distribution
    COUNT(CASE WHEN p.current_stage = 'quote_requested' THEN 1 END) as quote_requested_count,
    COUNT(CASE WHEN p.current_stage = 'quote_prepared' THEN 1 END) as quote_prepared_count,
    COUNT(CASE WHEN p.current_stage = 'quote_sent' THEN 1 END) as quote_sent_count,
    COUNT(CASE WHEN p.current_stage = 'quote_approved' THEN 1 END) as quote_approved_count,
    COUNT(CASE WHEN p.current_stage = 'order_processing' THEN 1 END) as order_processing_count,
    COUNT(CASE WHEN p.current_stage = 'ready_to_ship' THEN 1 END) as ready_to_ship_count,
    COUNT(CASE WHEN p.current_stage = 'invoiced_delivered' THEN 1 END) as invoiced_delivered_count,
    
    -- Priority Distribution
    COUNT(CASE WHEN p.priority = 'urgent' THEN 1 END) as urgent_count,
    COUNT(CASE WHEN p.priority = 'high' THEN 1 END) as high_count,
    COUNT(CASE WHEN p.priority = 'medium' THEN 1 END) as medium_count,
    COUNT(CASE WHEN p.priority = 'low' THEN 1 END) as low_count,
    
    -- Health Indicators
    COUNT(CASE 
        WHEN p.expected_completion_date < CURDATE() 
        AND p.current_stage != 'invoiced_delivered' 
        THEN 1 
    END) as overdue_count,
    
    COUNT(CASE 
        WHEN DATEDIFF(NOW(), p.stage_entered_date) > 7 
        AND p.current_stage IN ('quote_requested', 'quote_prepared') 
        THEN 1 
    END) as stalled_early_stage,
    
    COUNT(CASE 
        WHEN DATEDIFF(NOW(), p.stage_entered_date) > 14 
        AND p.current_stage IN ('quote_sent', 'quote_approved') 
        THEN 1 
    END) as stalled_mid_stage,
    
    COUNT(CASE 
        WHEN DATEDIFF(NOW(), p.stage_entered_date) > 21 
        AND p.current_stage IN ('order_processing', 'ready_to_ship') 
        THEN 1 
    END) as stalled_late_stage,
    
    -- Performance Metrics
    ROUND(
        (COUNT(CASE WHEN p.current_stage = 'invoiced_delivered' THEN 1 END) * 100.0 / 
         NULLIF(COUNT(*), 0)
        ), 2
    ) as completion_rate,
    
    AVG(DATEDIFF(NOW(), p.date_entered)) as avg_pipeline_age_days

FROM mfg_order_pipeline p
WHERE p.deleted = 0 AND p.status = 'active';

-- =====================================================
-- 6. MONTHLY PIPELINE TRENDS VIEW
-- =====================================================

CREATE OR REPLACE VIEW mfg_monthly_pipeline_trends AS
SELECT 
    YEAR(p.date_entered) as year,
    MONTH(p.date_entered) as month,
    DATE_FORMAT(p.date_entered, '%Y-%m') as year_month,
    MONTHNAME(p.date_entered) as month_name,
    
    -- New Pipelines
    COUNT(*) as new_pipelines,
    SUM(p.total_value) as new_pipeline_value,
    AVG(p.total_value) as avg_new_pipeline_value,
    
    -- Completed Pipelines (by completion date)
    (SELECT COUNT(*) 
     FROM mfg_order_pipeline p2 
     WHERE p2.deleted = 0 
     AND p2.current_stage = 'invoiced_delivered'
     AND YEAR(p2.actual_completion_date) = YEAR(p.date_entered)
     AND MONTH(p2.actual_completion_date) = MONTH(p.date_entered)
    ) as completed_pipelines,
    
    (SELECT SUM(p2.total_value) 
     FROM mfg_order_pipeline p2 
     WHERE p2.deleted = 0 
     AND p2.current_stage = 'invoiced_delivered'
     AND YEAR(p2.actual_completion_date) = YEAR(p.date_entered)
     AND MONTH(p2.actual_completion_date) = MONTH(p.date_entered)
    ) as completed_pipeline_value,
    
    -- Growth Metrics
    LAG(COUNT(*)) OVER (ORDER BY YEAR(p.date_entered), MONTH(p.date_entered)) as prev_month_new,
    ROUND(
        ((COUNT(*) - LAG(COUNT(*)) OVER (ORDER BY YEAR(p.date_entered), MONTH(p.date_entered))) * 100.0 /
         NULLIF(LAG(COUNT(*)) OVER (ORDER BY YEAR(p.date_entered), MONTH(p.date_entered)), 0)
        ), 2
    ) as growth_rate_percent

FROM mfg_order_pipeline p
WHERE p.deleted = 0
GROUP BY YEAR(p.date_entered), MONTH(p.date_entered)
ORDER BY YEAR(p.date_entered) DESC, MONTH(p.date_entered) DESC;

-- =====================================================
-- 7. BOTTLENECK ANALYSIS VIEW
-- =====================================================

CREATE OR REPLACE VIEW mfg_pipeline_bottleneck_analysis AS
SELECT 
    stage_info.stage_name,
    stage_info.display_name,
    stage_info.stage_order,
    
    -- Current Stage Statistics
    COALESCE(current_stage.current_count, 0) as current_count,
    COALESCE(current_stage.avg_days_in_stage, 0) as avg_days_in_stage,
    COALESCE(current_stage.total_value, 0) as total_value_in_stage,
    
    -- Historical Performance
    COALESCE(historical.avg_duration_days, 0) as historical_avg_duration_days,
    COALESCE(historical.total_transitions, 0) as total_historical_transitions,
    
    -- Bottleneck Score Calculation
    ROUND(
        (COALESCE(current_stage.current_count, 0) * 100.0 / 
         NULLIF((SELECT COUNT(*) FROM mfg_order_pipeline WHERE deleted = 0 AND status = 'active'), 0)
        ) + 
        (GREATEST(0, COALESCE(current_stage.avg_days_in_stage, 0) - COALESCE(historical.avg_duration_days, 0)) * 5),
        2
    ) as bottleneck_score,
    
    -- Recommendations
    CASE 
        WHEN COALESCE(current_stage.current_count, 0) > 
             (SELECT COUNT(*) * 0.3 FROM mfg_order_pipeline WHERE deleted = 0 AND status = 'active')
        THEN 'HIGH VOLUME - Consider additional resources'
        WHEN COALESCE(current_stage.avg_days_in_stage, 0) > 
             COALESCE(historical.avg_duration_days, 0) * 1.5
        THEN 'SLOW PROCESSING - Review stage efficiency'
        WHEN COALESCE(current_stage.current_count, 0) = 0
        THEN 'NO CURRENT ITEMS - Stage performing well'
        ELSE 'NORMAL - Stage performing within expected parameters'
    END as recommendation

FROM (
    SELECT 'quote_requested' as stage_name, 'Quote Requested' as display_name, 1 as stage_order
    UNION SELECT 'quote_prepared', 'Quote Prepared', 2
    UNION SELECT 'quote_sent', 'Quote Sent', 3
    UNION SELECT 'quote_approved', 'Quote Approved', 4
    UNION SELECT 'order_processing', 'Order Processing', 5
    UNION SELECT 'ready_to_ship', 'Ready to Ship', 6
    UNION SELECT 'invoiced_delivered', 'Invoiced & Delivered', 7
) stage_info

LEFT JOIN (
    SELECT 
        p.current_stage,
        COUNT(*) as current_count,
        AVG(DATEDIFF(NOW(), p.stage_entered_date)) as avg_days_in_stage,
        SUM(p.total_value) as total_value
    FROM mfg_order_pipeline p
    WHERE p.deleted = 0 AND p.status = 'active'
    GROUP BY p.current_stage
) current_stage ON stage_info.stage_name = current_stage.current_stage

LEFT JOIN (
    SELECT 
        h.to_stage,
        COUNT(*) as total_transitions,
        AVG(h.duration_in_previous_stage_hours / 24) as avg_duration_days
    FROM mfg_pipeline_stage_history h
    WHERE h.deleted = 0 
    AND h.duration_in_previous_stage_hours IS NOT NULL
    AND h.transition_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
    GROUP BY h.to_stage
) historical ON stage_info.stage_name = historical.to_stage

ORDER BY stage_info.stage_order;

-- =====================================================
-- GRANT PERMISSIONS (Adjust based on your user roles)
-- =====================================================

-- Grant SELECT permissions to appropriate roles
-- GRANT SELECT ON mfg_pipeline_analytics TO 'suitecrm_reporting'@'%';
-- GRANT SELECT ON mfg_pipeline_funnel TO 'suitecrm_reporting'@'%';
-- GRANT SELECT ON mfg_sales_rep_performance TO 'suitecrm_managers'@'%';

COMMIT;
