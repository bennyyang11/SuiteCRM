# AMP Code Prompt: Order Pipeline Database Schema for Order Tracking Dashboard

## TASK OVERVIEW
You are developing the database foundation for a SuiteCRM Enterprise Legacy Modernization project's Order Tracking Dashboard. Your focus is creating a robust pipeline system that tracks quotes through their entire lifecycle from initial quote to final invoice, with comprehensive audit trails and analytics capabilities.

## PRIMARY OBJECTIVES
1. **Pipeline Database Design**: Create tables for 7-stage order pipeline tracking
2. **History & Audit System**: Implement comprehensive stage transition tracking
3. **Notification Infrastructure**: Build notification preferences and delivery system
4. **Analytics Foundation**: Create views and tables for pipeline performance analytics
5. **Checklist Updates**: Mark each completed task with an ❌ in the REMAINING_TASKS_CHECKLIST.md file

## SPECIFIC TASKS TO COMPLETE

### 1. **Create `mfg_order_pipeline` table with 7 stages**
- **Pipeline Stages**:
  1. **Quote Requested** - Initial quote request from client
  2. **Quote Prepared** - Sales team preparing detailed quote
  3. **Quote Sent** - Quote delivered to client for review
  4. **Quote Approved** - Client approves quote terms
  5. **Order Processing** - Manufacturing/procurement begins
  6. **Ready to Ship** - Order completed, awaiting shipment
  7. **Invoiced & Delivered** - Final delivery and billing complete

- **Table Structure**:
```sql
CREATE TABLE mfg_order_pipeline (
    id VARCHAR(36) PRIMARY KEY,
    pipeline_number VARCHAR(50) UNIQUE NOT NULL,
    opportunity_id VARCHAR(36), -- Link to SuiteCRM Opportunities
    account_id VARCHAR(36) NOT NULL, -- Link to SuiteCRM Accounts
    assigned_user_id VARCHAR(36), -- Sales rep responsible
    current_stage ENUM('quote_requested', 'quote_prepared', 'quote_sent', 
                      'quote_approved', 'order_processing', 'ready_to_ship', 
                      'invoiced_delivered') DEFAULT 'quote_requested',
    stage_entered_date DATETIME,
    expected_completion_date DATE,
    total_value DECIMAL(18,2),
    currency_id VARCHAR(36),
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    client_po_number VARCHAR(100),
    notes TEXT,
    date_entered DATETIME,
    date_modified DATETIME,
    created_by VARCHAR(36),
    modified_user_id VARCHAR(36),
    deleted TINYINT(1) DEFAULT 0
);
```

### 2. **Design stage transition history tracking**
- **Historical Audit Trail**:
```sql
CREATE TABLE mfg_pipeline_stage_history (
    id VARCHAR(36) PRIMARY KEY,
    pipeline_id VARCHAR(36) NOT NULL,
    from_stage VARCHAR(50),
    to_stage VARCHAR(50) NOT NULL,
    transition_date DATETIME NOT NULL,
    transition_user_id VARCHAR(36),
    duration_in_stage INT, -- Hours spent in previous stage
    notes TEXT,
    automated_transition TINYINT(1) DEFAULT 0, -- System vs manual transition
    date_entered DATETIME,
    created_by VARCHAR(36),
    FOREIGN KEY (pipeline_id) REFERENCES mfg_order_pipeline(id)
);
```

- **Stage Performance Metrics**:
```sql
CREATE TABLE mfg_pipeline_stage_metrics (
    id VARCHAR(36) PRIMARY KEY,
    stage_name VARCHAR(50) NOT NULL,
    avg_duration_hours DECIMAL(8,2),
    min_duration_hours INT,
    max_duration_hours INT,
    success_rate DECIMAL(5,2), -- % that progress to next stage
    bottleneck_score DECIMAL(5,2), -- Performance indicator
    last_calculated DATETIME,
    calculation_period ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily'
);
```

### 3. **Add notification preferences table**
- **User Notification Settings**:
```sql
CREATE TABLE mfg_notification_preferences (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    notification_type ENUM('stage_change', 'overdue_alert', 'daily_summary', 
                          'client_update', 'urgent_priority') NOT NULL,
    delivery_method ENUM('email', 'sms', 'push', 'in_app') NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    schedule_time TIME, -- For daily summaries
    threshold_hours INT, -- For overdue alerts
    date_entered DATETIME,
    date_modified DATETIME,
    UNIQUE KEY unique_user_notification (user_id, notification_type, delivery_method)
);
```

- **Notification Queue & Delivery**:
```sql
CREATE TABLE mfg_notification_queue (
    id VARCHAR(36) PRIMARY KEY,
    pipeline_id VARCHAR(36),
    recipient_user_id VARCHAR(36),
    notification_type VARCHAR(50),
    delivery_method ENUM('email', 'sms', 'push', 'in_app'),
    subject VARCHAR(255),
    message TEXT,
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    scheduled_time DATETIME,
    sent_time DATETIME,
    attempts INT DEFAULT 0,
    error_message TEXT,
    date_entered DATETIME
);
```

### 4. **Create pipeline analytics views**
- **Pipeline Performance Dashboard View**:
```sql
CREATE VIEW mfg_pipeline_analytics AS
SELECT 
    DATE(p.date_entered) as pipeline_date,
    p.current_stage,
    p.assigned_user_id,
    COUNT(*) as stage_count,
    AVG(p.total_value) as avg_value,
    SUM(p.total_value) as total_value,
    AVG(DATEDIFF(NOW(), p.stage_entered_date)) as avg_days_in_stage,
    COUNT(CASE WHEN p.priority = 'urgent' THEN 1 END) as urgent_count
FROM mfg_order_pipeline p
WHERE p.deleted = 0
GROUP BY DATE(p.date_entered), p.current_stage, p.assigned_user_id;
```

- **Conversion Funnel Analytics**:
```sql
CREATE VIEW mfg_pipeline_funnel AS
SELECT 
    'quote_requested' as stage, COUNT(*) as count,
    (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM mfg_order_pipeline WHERE deleted = 0)) as percentage
FROM mfg_order_pipeline WHERE current_stage = 'quote_requested' AND deleted = 0
UNION ALL
SELECT 'quote_prepared', COUNT(*), 
    (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM mfg_order_pipeline WHERE deleted = 0))
FROM mfg_order_pipeline WHERE current_stage IN ('quote_prepared', 'quote_sent', 'quote_approved', 'order_processing', 'ready_to_ship', 'invoiced_delivered') AND deleted = 0
-- Continue for each stage...
```

- **Sales Rep Performance View**:
```sql
CREATE VIEW mfg_sales_rep_performance AS
SELECT 
    u.user_name,
    u.id as user_id,
    COUNT(p.id) as total_pipelines,
    COUNT(CASE WHEN p.current_stage = 'invoiced_delivered' THEN 1 END) as completed_sales,
    SUM(CASE WHEN p.current_stage = 'invoiced_delivered' THEN p.total_value ELSE 0 END) as total_revenue,
    AVG(CASE WHEN p.current_stage = 'invoiced_delivered' THEN 
        DATEDIFF(
            (SELECT MAX(transition_date) FROM mfg_pipeline_stage_history h WHERE h.pipeline_id = p.id),
            p.date_entered
        ) END) as avg_completion_days
FROM users u
LEFT JOIN mfg_order_pipeline p ON u.id = p.assigned_user_id AND p.deleted = 0
WHERE u.deleted = 0
GROUP BY u.id, u.user_name;
```

## TECHNICAL REQUIREMENTS

### Database Standards:
- **SuiteCRM Compatibility**: Follow SuiteCRM naming conventions and field standards
- **Indexes**: Create proper indexes for performance on frequently queried fields
- **Foreign Keys**: Establish relationships with existing SuiteCRM tables (users, accounts, opportunities)
- **Audit Fields**: Include standard SuiteCRM audit fields (date_entered, created_by, etc.)
- **Soft Deletes**: Implement soft delete pattern for data integrity

### Performance Considerations:
- **Index Strategy**:
```sql
-- Core performance indexes
CREATE INDEX idx_pipeline_stage ON mfg_order_pipeline(current_stage, deleted);
CREATE INDEX idx_pipeline_assigned ON mfg_order_pipeline(assigned_user_id, deleted);
CREATE INDEX idx_pipeline_account ON mfg_order_pipeline(account_id, deleted);
CREATE INDEX idx_history_pipeline ON mfg_pipeline_stage_history(pipeline_id, transition_date);
CREATE INDEX idx_notifications_user ON mfg_notification_preferences(user_id, enabled);
```

- **Partitioning Strategy**: Consider partitioning history table by date for large datasets
- **Archive Strategy**: Plan for archiving completed pipelines older than 2 years

### Data Integrity:
- **Validation Rules**: Ensure stage transitions follow business logic
- **Constraints**: Prevent invalid stage combinations
- **Triggers**: Auto-update stage metrics when transitions occur
- **Backup Strategy**: Regular backups with point-in-time recovery

## INTEGRATION POINTS

### SuiteCRM Module Integration:
- **Opportunities**: Link pipeline records to existing opportunity records
- **Accounts**: Connect to customer account records
- **Users**: Leverage existing user management and permissions
- **Activities**: Integration with calls, meetings, and tasks
- **Email Templates**: Connect to email template system for notifications

### Sample Data Requirements:
- **50+ Pipeline Records**: Diverse stages, values, and timeframes
- **Historical Data**: 6 months of stage transition history
- **User Preferences**: Notification settings for different user types
- **Performance Metrics**: Calculated metrics for dashboard display

## COMPLETION PROCESS

After completing each task, you MUST:

1. **Update the checklist** - Replace `- [ ]` with `- [x]` for each completed item in REMAINING_TASKS_CHECKLIST.md under:
   ```
   ### **📊 Feature 2: Order Tracking Dashboard (Quote → Invoice Pipeline) (10 Points)**
   - [ ] **Database Schema**
     - [ ] Create `mfg_order_pipeline` table with 7 stages
     - [ ] Design stage transition history tracking
     - [ ] Add notification preferences table
     - [ ] Create pipeline analytics views
   ```

2. **Validate database structure**:
   - Test all table creation scripts
   - Verify foreign key relationships
   - Confirm index performance
   - Validate view query performance

3. **Populate test data**:
   - Create sample pipeline records across all stages
   - Generate historical transition data
   - Set up notification preferences for test users
   - Verify analytics views return meaningful data

## SUCCESS CRITERIA
- ✅ All 4 database schema tasks marked complete with ❌ in checklist
- ✅ `mfg_order_pipeline` table created with 7-stage workflow support
- ✅ Complete stage transition history tracking implemented
- ✅ Notification preferences system ready for user configuration
- ✅ Analytics views providing actionable pipeline insights
- ✅ Performance optimized with proper indexing strategy
- ✅ Full integration with existing SuiteCRM database structure
- ✅ Sample data populated for testing and demonstration

## CONTEXT AWARENESS
- **Manufacturing Focus**: Pipeline designed for manufacturing quote-to-delivery process
- **Enterprise Scale**: Handle hundreds of concurrent pipeline records
- **Mobile Optimization**: Database queries optimized for mobile dashboard performance
- **Analytics Ready**: Structure supports real-time reporting and KPI dashboards
- **Audit Compliance**: Complete audit trail for business process compliance

Begin with the core pipeline table creation, implement stage history tracking, add notification infrastructure, create analytics views, populate with realistic test data, and finally update the checklist with ❌ marks for completed tasks. 