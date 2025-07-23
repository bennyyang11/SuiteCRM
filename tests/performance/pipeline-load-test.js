/**
 * K6 Performance Testing Script for Pipeline Dashboard
 * Tests dashboard with 100+ concurrent orders and validates response times
 */

import { check, sleep } from 'k6';
import http from 'k6/http';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
const dashboardLoadTime = new Trend('dashboard_load_time');
const stageUpdateTime = new Trend('stage_update_time');
const searchResponseTime = new Trend('search_response_time');
const apiResponseTime = new Trend('api_response_time');
const errorRate = new Rate('error_rate');
const successfulRequests = new Counter('successful_requests');

// Test configuration
export let options = {
  stages: [
    { duration: '2m', target: 20 },   // Ramp up to 20 users
    { duration: '5m', target: 50 },   // Stay at 50 users for 5 minutes
    { duration: '2m', target: 100 },  // Ramp up to 100 users
    { duration: '10m', target: 100 }, // Stay at 100 users for 10 minutes
    { duration: '3m', target: 150 },  // Peak load test
    { duration: '2m', target: 100 },  // Back to 100 users
    { duration: '2m', target: 0 },    // Ramp down
  ],
  
  thresholds: {
    // Dashboard should load within 2 seconds for 95% of requests
    'dashboard_load_time': ['p(95)<2000'],
    
    // Stage updates should complete within 500ms for 95% of requests
    'stage_update_time': ['p(95)<500'],
    
    // Search should respond within 300ms for 95% of requests
    'search_response_time': ['p(95)<300'],
    
    // API endpoints should respond within 200ms for 95% of requests
    'api_response_time': ['p(95)<200'],
    
    // Error rate should be less than 1%
    'error_rate': ['rate<0.01'],
    
    // HTTP request duration should be reasonable
    'http_req_duration': ['p(95)<2000'],
    
    // HTTP request failures should be minimal
    'http_req_failed': ['rate<0.01'],
  },
};

// Test data and configuration
const BASE_URL = __ENV.BASE_URL || 'http://localhost:3000';
const API_BASE = `${BASE_URL}/Api/v1/manufacturing`;

// Authentication token (would be obtained via login in real test)
const AUTH_TOKEN = __ENV.AUTH_TOKEN || 'test-auth-token';

// Headers for authenticated requests
const authHeaders = {
  'Authorization': `Bearer ${AUTH_TOKEN}`,
  'Content-Type': 'application/json',
  'X-CSRF-Token': 'test-csrf-token'
};

// Test data generators
function generateTestOrder() {
  return {
    id: `test_order_${Math.random().toString(36).substr(2, 9)}`,
    order_number: `ORD-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
    account_name: `Test Client ${Math.floor(Math.random() * 100)}`,
    total_value: Math.floor(Math.random() * 100000) + 1000,
    stage_id: ['quote_requested', 'quote_prepared', 'quote_sent', 'quote_approved', 'order_processing'][Math.floor(Math.random() * 5)],
    priority: ['normal', 'high', 'urgent'][Math.floor(Math.random() * 3)],
    assigned_user_id: `user_${Math.floor(Math.random() * 10)}`,
    expected_close_date: new Date(Date.now() + Math.random() * 30 * 24 * 60 * 60 * 1000).toISOString()
  };
}

function generateSearchQuery() {
  const queries = [
    'steel',
    'brackets',
    'ORD-2024',
    'urgent',
    'processing',
    'shipped',
    'Test Client',
    '10000'
  ];
  return queries[Math.floor(Math.random() * queries.length)];
}

// Setup function - runs once before test execution
export function setup() {
  console.log('Setting up performance test environment...');
  
  // Create test data set with 100+ orders
  const setupStart = Date.now();
  
  // Generate test orders
  const testOrders = [];
  for (let i = 0; i < 150; i++) {
    testOrders.push(generateTestOrder());
  }
  
  // Bulk create test orders via API
  const createOrdersResponse = http.post(
    `${API_BASE}/OrderPipelineAPI.php`,
    JSON.stringify({
      action: 'bulkCreate',
      orders: testOrders
    }),
    { headers: authHeaders }
  );
  
  const setupDuration = Date.now() - setupStart;
  console.log(`Setup completed in ${setupDuration}ms. Created ${testOrders.length} test orders.`);
  
  if (createOrdersResponse.status !== 200) {
    console.error('Failed to create test orders:', createOrdersResponse.body);
  }
  
  return {
    testOrders: testOrders,
    setupTime: setupDuration
  };
}

// Main test function
export default function(data) {
  const testScenarios = [
    { name: 'Dashboard Load', weight: 30, func: testDashboardLoad },
    { name: 'Stage Updates', weight: 25, func: testStageUpdates },
    { name: 'Search Operations', weight: 20, func: testSearchOperations },
    { name: 'API Endpoints', weight: 15, func: testAPIEndpoints },
    { name: 'Mobile Dashboard', weight: 10, func: testMobileDashboard }
  ];
  
  // Select test scenario based on weight
  const random = Math.random() * 100;
  let cumulativeWeight = 0;
  let selectedScenario = testScenarios[0];
  
  for (const scenario of testScenarios) {
    cumulativeWeight += scenario.weight;
    if (random <= cumulativeWeight) {
      selectedScenario = scenario;
      break;
    }
  }
  
  // Execute selected test scenario
  selectedScenario.func(data);
  
  // Think time between requests (1-3 seconds)
  sleep(Math.random() * 2 + 1);
}

// Test scenario: Dashboard loading with 100+ orders
function testDashboardLoad(data) {
  const dashboardStart = Date.now();
  
  const response = http.get(`${BASE_URL}/index.php?module=Manufacturing&action=Dashboard`, {
    headers: authHeaders
  });
  
  const duration = Date.now() - dashboardStart;
  dashboardLoadTime.add(duration);
  
  const success = check(response, {
    'dashboard loads successfully': (r) => r.status === 200,
    'dashboard loads within 2 seconds': (r) => duration < 2000,
    'dashboard contains pipeline data': (r) => r.body.includes('pipeline') || r.body.includes('order'),
    'dashboard is not empty': (r) => r.body.length > 1000,
  });
  
  if (success) {
    successfulRequests.add(1);
  } else {
    errorRate.add(1);
  }
  
  // Test pipeline data loading
  const pipelineDataStart = Date.now();
  const pipelineResponse = http.get(`${API_BASE}/OrderPipelineAPI.php?action=getMobilePipeline`, {
    headers: authHeaders
  });
  
  const pipelineDataDuration = Date.now() - pipelineDataStart;
  apiResponseTime.add(pipelineDataDuration);
  
  check(pipelineResponse, {
    'pipeline data loads successfully': (r) => r.status === 200,
    'pipeline data loads quickly': (r) => pipelineDataDuration < 500,
    'pipeline data contains orders': (r) => {
      try {
        const data = JSON.parse(r.body);
        return data.orders && data.orders.length > 0;
      } catch (e) {
        return false;
      }
    }
  });
}

// Test scenario: Stage update operations
function testStageUpdates(data) {
  if (!data.testOrders || data.testOrders.length === 0) {
    return;
  }
  
  const randomOrder = data.testOrders[Math.floor(Math.random() * data.testOrders.length)];
  const newStages = ['quote_prepared', 'quote_sent', 'quote_approved', 'order_processing', 'shipped'];
  const newStage = newStages[Math.floor(Math.random() * newStages.length)];
  
  const stageUpdateStart = Date.now();
  
  const updateResponse = http.post(
    `${API_BASE}/OrderPipelineAPI.php`,
    JSON.stringify({
      action: 'updateStage',
      orderId: randomOrder.id,
      newStageId: newStage,
      note: `Performance test stage update at ${new Date().toISOString()}`
    }),
    { headers: authHeaders }
  );
  
  const duration = Date.now() - stageUpdateStart;
  stageUpdateTime.add(duration);
  
  check(updateResponse, {
    'stage update successful': (r) => r.status === 200,
    'stage update completes quickly': (r) => duration < 500,
    'stage update returns valid response': (r) => {
      try {
        const data = JSON.parse(r.body);
        return data.success === true;
      } catch (e) {
        return false;
      }
    }
  });
  
  // Test notification generation for stage update
  const notificationResponse = http.get(`${API_BASE}/NotificationAPI.php?action=getRecent&orderId=${randomOrder.id}`, {
    headers: authHeaders
  });
  
  check(notificationResponse, {
    'notification generated for stage update': (r) => r.status === 200
  });
}

// Test scenario: Search operations
function testSearchOperations(data) {
  const searchQuery = generateSearchQuery();
  const searchStart = Date.now();
  
  const searchResponse = http.get(
    `${API_BASE}/OrderPipelineAPI.php?action=search&query=${encodeURIComponent(searchQuery)}&limit=50`,
    { headers: authHeaders }
  );
  
  const duration = Date.now() - searchStart;
  searchResponseTime.add(duration);
  
  check(searchResponse, {
    'search returns results': (r) => r.status === 200,
    'search responds quickly': (r) => duration < 300,
    'search returns valid JSON': (r) => {
      try {
        JSON.parse(r.body);
        return true;
      } catch (e) {
        return false;
      }
    }
  });
  
  // Test advanced search with filters
  const advancedSearchStart = Date.now();
  const advancedSearchResponse = http.post(
    `${API_BASE}/OrderPipelineAPI.php`,
    JSON.stringify({
      action: 'advancedSearch',
      filters: {
        priority: ['high', 'urgent'],
        dateRange: '30days',
        assignedUser: 'current'
      },
      limit: 25
    }),
    { headers: authHeaders }
  );
  
  const advancedDuration = Date.now() - advancedSearchStart;
  searchResponseTime.add(advancedDuration);
  
  check(advancedSearchResponse, {
    'advanced search works': (r) => r.status === 200,
    'advanced search is fast': (r) => advancedDuration < 400
  });
}

// Test scenario: API endpoint performance
function testAPIEndpoints(data) {
  const apiTests = [
    {
      name: 'Get KPI Data',
      url: `${API_BASE}/ManagerDashboardAPI.php?action=getKPIData`,
      method: 'GET'
    },
    {
      name: 'Get Team Performance',
      url: `${API_BASE}/ManagerDashboardAPI.php?action=getTeamPerformance&timeRange=week`,
      method: 'GET'
    },
    {
      name: 'Get Integration Status',
      url: `${API_BASE}/PipelineOpportunityIntegration.php?action=getIntegrationStatus`,
      method: 'GET'
    },
    {
      name: 'Get Push Preferences',
      url: `${API_BASE}/PushNotificationAPI.php?action=getPushPreferences`,
      method: 'GET'
    }
  ];
  
  const randomTest = apiTests[Math.floor(Math.random() * apiTests.length)];
  const apiStart = Date.now();
  
  let response;
  if (randomTest.method === 'GET') {
    response = http.get(randomTest.url, { headers: authHeaders });
  } else {
    response = http.post(randomTest.url, '{}', { headers: authHeaders });
  }
  
  const duration = Date.now() - apiStart;
  apiResponseTime.add(duration);
  
  check(response, {
    [`${randomTest.name} API responds`]: (r) => r.status === 200,
    [`${randomTest.name} API is fast`]: (r) => duration < 200,
    [`${randomTest.name} API returns JSON`]: (r) => {
      try {
        JSON.parse(r.body);
        return true;
      } catch (e) {
        return false;
      }
    }
  });
}

// Test scenario: Mobile dashboard performance
function testMobileDashboard(data) {
  const mobileStart = Date.now();
  
  // Test mobile dashboard page load
  const mobileResponse = http.get(`${BASE_URL}/index.php?module=Manufacturing&action=MobileDashboard`, {
    headers: {
      ...authHeaders,
      'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15'
    }
  });
  
  const duration = Date.now() - mobileStart;
  dashboardLoadTime.add(duration);
  
  check(mobileResponse, {
    'mobile dashboard loads': (r) => r.status === 200,
    'mobile dashboard is optimized': (r) => duration < 3000, // Slightly higher threshold for mobile
    'mobile dashboard has responsive CSS': (r) => r.body.includes('mobile') || r.body.includes('responsive')
  });
  
  // Test mobile API endpoints
  const mobilePipelineStart = Date.now();
  const mobilePipelineResponse = http.get(`${API_BASE}/OrderPipelineAPI.php?action=getMobilePipeline&compact=true`, {
    headers: authHeaders
  });
  
  const mobilePipelineDuration = Date.now() - mobilePipelineStart;
  apiResponseTime.add(mobilePipelineDuration);
  
  check(mobilePipelineResponse, {
    'mobile pipeline API works': (r) => r.status === 200,
    'mobile pipeline API is fast': (r) => mobilePipelineDuration < 300
  });
}

// Teardown function - runs once after test execution
export function teardown(data) {
  console.log('Cleaning up performance test environment...');
  
  if (data.testOrders && data.testOrders.length > 0) {
    // Clean up test orders
    const cleanupResponse = http.post(
      `${API_BASE}/OrderPipelineAPI.php`,
      JSON.stringify({
        action: 'bulkDelete',
        orderIds: data.testOrders.map(order => order.id)
      }),
      { headers: authHeaders }
    );
    
    if (cleanupResponse.status === 200) {
      console.log(`Cleaned up ${data.testOrders.length} test orders`);
    } else {
      console.error('Failed to clean up test orders:', cleanupResponse.body);
    }
  }
  
  console.log('Performance test teardown completed');
}

// Helper function to handle check results
export function handleSummary(data) {
  const summary = {
    testRun: {
      timestamp: new Date().toISOString(),
      duration: data.state.testRunDurationMs,
      iterations: data.metrics.iterations.values.count,
      vus: data.metrics.vus.values.value,
      vusMax: data.metrics.vus_max.values.value
    },
    performance: {
      dashboardLoadTime: {
        avg: data.metrics.dashboard_load_time?.values?.avg || 0,
        p95: data.metrics.dashboard_load_time?.values?.p95 || 0,
        max: data.metrics.dashboard_load_time?.values?.max || 0
      },
      stageUpdateTime: {
        avg: data.metrics.stage_update_time?.values?.avg || 0,
        p95: data.metrics.stage_update_time?.values?.p95 || 0,
        max: data.metrics.stage_update_time?.values?.max || 0
      },
      searchResponseTime: {
        avg: data.metrics.search_response_time?.values?.avg || 0,
        p95: data.metrics.search_response_time?.values?.p95 || 0,
        max: data.metrics.search_response_time?.values?.max || 0
      },
      apiResponseTime: {
        avg: data.metrics.api_response_time?.values?.avg || 0,
        p95: data.metrics.api_response_time?.values?.p95 || 0,
        max: data.metrics.api_response_time?.values?.max || 0
      }
    },
    reliability: {
      errorRate: data.metrics.error_rate?.values?.rate || 0,
      successfulRequests: data.metrics.successful_requests?.values?.count || 0,
      httpReqFailed: data.metrics.http_req_failed?.values?.rate || 0
    },
    thresholds: data.thresholds || {}
  };
  
  // Save detailed results to file
  return {
    'performance-test-results.json': JSON.stringify(summary, null, 2),
    'stdout': generateTextSummary(summary)
  };
}

function generateTextSummary(summary) {
  return `
=== PIPELINE DASHBOARD PERFORMANCE TEST RESULTS ===

Test Run Information:
- Duration: ${Math.round(summary.testRun.duration / 1000)}s
- Total Iterations: ${summary.testRun.iterations}
- Peak Virtual Users: ${summary.testRun.vusMax}

Performance Metrics:
- Dashboard Load Time: ${Math.round(summary.performance.dashboardLoadTime.avg)}ms avg, ${Math.round(summary.performance.dashboardLoadTime.p95)}ms p95
- Stage Update Time: ${Math.round(summary.performance.stageUpdateTime.avg)}ms avg, ${Math.round(summary.performance.stageUpdateTime.p95)}ms p95
- Search Response Time: ${Math.round(summary.performance.searchResponseTime.avg)}ms avg, ${Math.round(summary.performance.searchResponseTime.p95)}ms p95
- API Response Time: ${Math.round(summary.performance.apiResponseTime.avg)}ms avg, ${Math.round(summary.performance.apiResponseTime.p95)}ms p95

Reliability Metrics:
- Error Rate: ${(summary.reliability.errorRate * 100).toFixed(2)}%
- Successful Requests: ${summary.reliability.successfulRequests}
- HTTP Request Failures: ${(summary.reliability.httpReqFailed * 100).toFixed(2)}%

Overall Status: ${summary.reliability.errorRate < 0.01 && summary.performance.dashboardLoadTime.p95 < 2000 ? 'PASSED' : 'FAILED'}

====================================================
`;
}
