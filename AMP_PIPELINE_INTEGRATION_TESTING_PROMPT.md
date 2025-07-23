# AMP Code Prompt: Integration & Testing for Order Tracking Pipeline

## TASK OVERVIEW
You are responsible for integration and comprehensive testing of the SuiteCRM Enterprise Legacy Modernization project's Order Tracking Pipeline system. Your focus is ensuring seamless integration with existing SuiteCRM modules, validating email notification delivery, testing business rule enforcement, and verifying performance with enterprise-scale data loads.

## PRIMARY OBJECTIVES
1. **SuiteCRM Integration**: Connect pipeline system with existing CRM modules
2. **Notification Testing**: Validate email/SMS delivery and reliability
3. **Business Logic Validation**: Test stage transition rules and workflow enforcement
4. **Performance Testing**: Verify system performance with 100+ concurrent orders
5. **Checklist Updates**: Mark each completed task with an ❌ in the REMAINING_TASKS_CHECKLIST.md file

## SPECIFIC TASKS TO COMPLETE

### 1. **Connect to existing SuiteCRM opportunities module**
- **Database Integration**:
```sql
-- Create relationship between pipeline and opportunities
ALTER TABLE mfg_order_pipeline 
ADD CONSTRAINT fk_pipeline_opportunity 
FOREIGN KEY (opportunity_id) REFERENCES opportunities(id);

-- Create pipeline-specific fields in opportunities
ALTER TABLE opportunities_cstm ADD COLUMN pipeline_stage_c VARCHAR(50);
ALTER TABLE opportunities_cstm ADD COLUMN pipeline_id_c VARCHAR(36);
ALTER TABLE opportunities_cstm ADD COLUMN expected_ship_date_c DATE;
ALTER TABLE opportunities_cstm ADD COLUMN manufacturing_priority_c VARCHAR(20);
```

- **Module Integration Points**:
  - **Opportunity Creation**: Auto-generate pipeline record when opportunity reaches "Proposal/Quote" stage
  - **Stage Synchronization**: Sync pipeline stages with opportunity sales stages
  - **Value Updates**: Keep opportunity amount in sync with pipeline total value
  - **Activity Tracking**: Log pipeline stage changes as opportunity activities
  - **Document Attachment**: Link quotes, contracts, and invoices to both records

- **API Integration Layer**:
```php
class PipelineOpportunityIntegration {
    public function syncOpportunityToPipeline(string $opportunityId): void {
        // Get opportunity data
        $opportunity = $this->getOpportunity($opportunityId);
        
        // Create or update pipeline record
        if ($opportunity->pipeline_id_c) {
            $this->updatePipelineFromOpportunity($opportunity);
        } else {
            $this->createPipelineFromOpportunity($opportunity);
        }
        
        // Update opportunity with pipeline reference
        $this->updateOpportunityPipelineFields($opportunity);
    }
    
    public function syncPipelineToOpportunity(string $pipelineId): void {
        // Update opportunity stages based on pipeline progression
        // Sync values, dates, and status information
        // Log activities and stage changes
    }
}
```

- **Workflow Integration**:
  - **Auto-progression**: Opportunities auto-advance when pipeline reaches certain stages
  - **Required Fields**: Validate opportunity required fields before pipeline advancement
  - **Team Assignment**: Sync assigned users between opportunities and pipeline
  - **Territory Management**: Respect territory assignments and permissions
  - **Revenue Recognition**: Update revenue forecasts based on pipeline progression

### 2. **Test email notification delivery**
- **Email Testing Framework**:
```php
class PipelineNotificationTester {
    public function testStageChangeNotification(): array {
        $results = [];
        
        // Test internal notifications
        $results['internal'] = $this->testInternalNotifications();
        
        // Test client notifications
        $results['client'] = $this->testClientNotifications();
        
        // Test manager digest emails
        $results['manager_digest'] = $this->testManagerDigestEmails();
        
        // Test overdue alerts
        $results['overdue_alerts'] = $this->testOverdueAlerts();
        
        return $results;
    }
    
    private function testInternalNotifications(): bool {
        // Create test pipeline record
        // Trigger stage change
        // Verify email delivery within 2 minutes
        // Check email content accuracy
        // Validate recipient list
    }
}
```

- **Notification Test Scenarios**:
  - **Stage Transitions**: Test all 6 possible stage transition notifications
  - **Priority Escalations**: Verify urgent order notifications are delivered immediately
  - **Overdue Alerts**: Test daily overdue order notifications
  - **Manager Summaries**: Validate weekly team performance digest emails
  - **Client Updates**: Test external client notification delivery
  - **Bulk Operations**: Test notification delivery for batch stage updates

- **Email Content Validation**:
  - **Template Rendering**: Verify all variables populate correctly
  - **HTML Formatting**: Test email display across different email clients
  - **Mobile Compatibility**: Ensure emails display properly on mobile devices
  - **Link Functionality**: Verify all email links work and lead to correct pages
  - **Unsubscribe Logic**: Test opt-out functionality for different notification types
  - **Personalization**: Confirm user-specific content and preferences

- **Delivery Testing**:
  - **SMTP Configuration**: Verify email server settings and authentication
  - **Spam Filter Testing**: Test delivery to major email providers (Gmail, Outlook, Yahoo)
  - **Bounce Handling**: Test invalid email address handling
  - **Rate Limiting**: Verify email sending doesn't exceed server limits
  - **Queue Processing**: Test email queue processing under load
  - **Retry Logic**: Verify failed email delivery retry mechanisms

### 3. **Validate stage transition business rules**
- **Business Rule Test Matrix**:
```typescript
const stageTransitionTests = [
  {
    from: 'quote_requested',
    to: 'quote_prepared',
    requiredFields: ['account_id', 'assigned_user_id'],
    permissions: ['access_quotes'],
    shouldPass: true
  },
  {
    from: 'quote_sent',
    to: 'order_processing',
    requiredFields: ['client_approval_date', 'po_number'],
    permissions: ['process_orders'],
    shouldPass: false, // Should go through quote_approved first
    expectedError: 'Invalid stage transition'
  },
  {
    from: 'order_processing',
    to: 'quote_requested',
    shouldPass: false, // Cannot go backwards from processing
    expectedError: 'Cannot revert from processing stage'
  }
];
```

- **Validation Test Cases**:
  - **Sequential Progression**: Test that stages must be completed in order
  - **Permission Checks**: Verify users can only transition stages they have permission for
  - **Required Field Validation**: Test that required fields must be completed before advancement
  - **Inventory Validation**: Verify stock availability before moving to processing
  - **Client Approval**: Test that client approval is required for processing stage
  - **Date Validation**: Verify expected dates are realistic and business-logical

- **Edge Case Testing**:
  - **Concurrent Updates**: Test multiple users updating same order simultaneously
  - **Network Failures**: Test stage updates during network interruptions
  - **Partial Updates**: Test recovery from failed stage transition attempts
  - **Data Corruption**: Test handling of corrupted or invalid pipeline data
  - **User Permissions**: Test stage transitions when user permissions change mid-process
  - **System Maintenance**: Test behavior during database maintenance windows

- **Rollback Testing**:
  - **Transaction Integrity**: Verify database transactions roll back on failure
  - **History Preservation**: Test that failed transitions are logged appropriately
  - **State Consistency**: Verify system state remains consistent after failures
  - **User Notification**: Test that users are informed of failed transitions
  - **Retry Mechanisms**: Test automatic retry logic for transient failures

### 4. **Performance test dashboard with 100+ orders**
- **Load Testing Scenarios**:
```javascript
// K6 load testing script
import { check } from 'k6';
import http from 'k6/http';

export let options = {
  stages: [
    { duration: '2m', target: 20 }, // Ramp up
    { duration: '5m', target: 50 }, // Stay at 50 users
    { duration: '2m', target: 100 }, // Ramp up to 100
    { duration: '10m', target: 100 }, // Stay at 100 users
    { duration: '2m', target: 0 }, // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% under 2s
    http_req_failed: ['rate<0.01'], // Less than 1% errors
  },
};

export default function() {
  // Test dashboard loading
  let response = http.get('${BASE_URL}/pipeline/dashboard');
  check(response, {
    'dashboard loads in <2s': (r) => r.timings.duration < 2000,
    'status is 200': (r) => r.status === 200,
  });
  
  // Test stage updates
  let updateResponse = http.post('${BASE_URL}/pipeline/update-stage', {
    pipelineId: 'test-order-123',
    newStage: 'quote_sent'
  });
  check(updateResponse, {
    'stage update successful': (r) => r.status === 200,
    'update completes <500ms': (r) => r.timings.duration < 500,
  });
}
```

- **Performance Metrics**:
  - **Dashboard Load Time**: <2 seconds for 100+ orders
  - **Stage Update Response**: <500ms for stage transitions
  - **Search Performance**: <300ms for pipeline search queries
  - **Notification Delivery**: <30 seconds for email notifications
  - **Database Query Time**: <100ms for individual pipeline queries
  - **API Response Time**: <200ms for REST API endpoints
  - **Real-time Updates**: <1 second for WebSocket updates
  - **Memory Usage**: <512MB for dashboard with 500+ orders

- **Scalability Testing**:
  - **User Concurrency**: Test 100 simultaneous users on dashboard
  - **Data Volume**: Test with 1000+ pipeline records
  - **Database Performance**: Monitor query performance under load
  - **Cache Effectiveness**: Verify Redis cache hit rates >80%
  - **Network Bandwidth**: Test performance on 3G/4G connections
  - **Mobile Performance**: Test on lower-end mobile devices

- **Stress Testing**:
  - **Peak Load**: Test system behavior at 150% normal capacity
  - **Resource Exhaustion**: Test behavior when memory/CPU limits reached
  - **Database Connections**: Test connection pool exhaustion scenarios
  - **API Rate Limiting**: Test behavior when API limits are exceeded
  - **Cache Failure**: Test system performance when Redis is unavailable
  - **Recovery Testing**: Test system recovery after failures

## TESTING TOOLS AND AUTOMATION

### Testing Stack:
```bash
# Performance testing
npm install -g k6
k6 run --vus 100 --duration 10m pipeline-load-test.js

# Email testing
npm install mailhog-client
npm install email-templates-tester

# Database testing
npm install mysql-stress-test
npm install redis-benchmark

# Integration testing
npm install newman  # Postman CLI
newman run pipeline-integration-tests.json
```

### Automated Test Suite:
- **Unit Tests**: Test individual pipeline functions and methods
- **Integration Tests**: Test module interactions and API endpoints
- **End-to-End Tests**: Test complete user workflows
- **Performance Tests**: Automated load testing with CI/CD integration
- **Security Tests**: SQL injection and XSS vulnerability testing
- **Accessibility Tests**: Automated a11y testing with axe-core

## COMPLETION PROCESS

After completing each task, you MUST:

1. **Update the checklist** - Replace `- [ ]` with `- [x]` for each completed item in REMAINING_TASKS_CHECKLIST.md under:
   ```
   - [ ] **Integration & Testing**
     - [ ] Connect to existing SuiteCRM opportunities module
     - [ ] Test email notification delivery
     - [ ] Validate stage transition business rules
     - [ ] Performance test dashboard with 100+ orders
   ```

2. **Document test results**:
   - Create comprehensive test execution reports
   - Record performance benchmarks and metrics
   - Document any bugs or issues discovered
   - Provide recommendations for optimization

3. **Verify production readiness**:
   - All tests passing with acceptable performance
   - No critical bugs or security vulnerabilities
   - Proper error handling and user feedback
   - Monitoring and alerting configured

## SUCCESS CRITERIA
- ✅ All 4 integration & testing tasks marked complete with ❌ in checklist
- ✅ SuiteCRM opportunities module integration working seamlessly
- ✅ Email notifications delivering reliably with proper content
- ✅ Stage transition business rules enforced correctly
- ✅ Dashboard performance meeting targets with 100+ concurrent orders
- ✅ All automated tests passing in CI/CD pipeline
- ✅ System ready for production deployment
- ✅ Comprehensive test documentation completed
- ✅ Performance baselines established for monitoring

## CONTEXT AWARENESS
- **Enterprise Environment**: Testing must reflect real-world enterprise conditions
- **Manufacturing Workflow**: Tests must validate manufacturing-specific business processes
- **Multi-user Environment**: Testing must account for concurrent user scenarios
- **Data Integrity**: Critical to maintain data consistency across integrations
- **Production Readiness**: System must be fully validated before go-live

Begin with SuiteCRM module integration, validate email notification systems, test business rule enforcement, perform comprehensive performance testing, document all results, and finally update the checklist with ❌ marks for completed tasks. 