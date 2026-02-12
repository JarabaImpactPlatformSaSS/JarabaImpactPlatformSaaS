/**
 * k6 Multi-Tenant Load Test — Jaraba Impact Platform (F10 Doc 187)
 *
 * Tests multi-tenant performance: concurrent tenant API calls,
 * CRM pipeline operations, AI agent invocations, and tenant isolation.
 *
 * Usage:
 *   k6 run tests/performance/multi_tenant_load_test.js
 *   k6 run --env BASE_URL=https://staging.jaraba.io tests/performance/multi_tenant_load_test.js
 *   k6 run --env TENANTS=10 tests/performance/multi_tenant_load_test.js
 *
 * Scenarios:
 *   - Multi-tenant API concurrent access
 *   - CRM Pipeline operations (B2B flow)
 *   - Tenant isolation verification
 *   - Scaling breakpoint test
 */

import http from 'k6/http';
import { check, group, sleep, fail } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// =============================================================================
// CUSTOM METRICS
// =============================================================================

const errorRate = new Rate('errors');
const tenantIsolationFailures = new Counter('tenant_isolation_failures');
const crmApiTime = new Trend('crm_api_duration', true);
const pipelineTime = new Trend('pipeline_operation_duration', true);
const playBookTime = new Trend('playbook_api_duration', true);
const analyticsTime = new Trend('analytics_api_duration', true);
const socialApiTime = new Trend('social_api_duration', true);

// =============================================================================
// CONFIGURATION
// =============================================================================

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const TENANT_COUNT = parseInt(__ENV.TENANTS || '5');

// Simulated tenant sessions (in real test, these would be auth tokens)
const TENANTS = Array.from({ length: TENANT_COUNT }, (_, i) => ({
  id: i + 1,
  name: `Tenant_${i + 1}`,
  user: `admin_tenant_${i + 1}@jaraba.io`,
}));

// =============================================================================
// TEST SCENARIOS
// =============================================================================

export const options = {
  scenarios: {
    // Scenario 1: Multi-tenant concurrent API access
    multi_tenant_api: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '30s', target: TENANT_COUNT },       // 1 VU per tenant
        { duration: '2m',  target: TENANT_COUNT * 2 },   // 2 VUs per tenant
        { duration: '2m',  target: TENANT_COUNT * 2 },   // Sustained
        { duration: '30s', target: 0 },                  // Ramp down
      ],
      startTime: '0s',
      tags: { scenario: 'multi_tenant_api' },
    },

    // Scenario 2: CRM Pipeline heavy operations
    crm_pipeline: {
      executor: 'constant-vus',
      vus: Math.min(TENANT_COUNT, 10),
      duration: '3m',
      startTime: '5m30s',
      tags: { scenario: 'crm_pipeline' },
    },

    // Scenario 3: Tenant isolation verification
    tenant_isolation: {
      executor: 'per-vu-iterations',
      vus: Math.min(TENANT_COUNT, 5),
      iterations: 3,
      startTime: '9m',
      tags: { scenario: 'tenant_isolation' },
    },

    // Scenario 4: Scaling breakpoint (find the breaking point)
    scaling_breakpoint: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '1m', target: 20 },
        { duration: '1m', target: 50 },
        { duration: '1m', target: 100 },
        { duration: '1m', target: 100 },   // Sustained at peak
        { duration: '30s', target: 0 },
      ],
      startTime: '11m',
      tags: { scenario: 'scaling_breakpoint' },
    },
  },

  // Performance thresholds
  thresholds: {
    // Global
    http_req_duration: ['p(95)<800', 'p(99)<2000'],
    http_req_failed: ['rate<0.02'],           // < 2% error rate
    errors: ['rate<0.02'],

    // Multi-tenant specific
    crm_api_duration: ['p(95)<500'],          // CRM API < 500ms p95
    pipeline_operation_duration: ['p(95)<600'], // Pipeline ops < 600ms p95
    playbook_api_duration: ['p(95)<400'],     // Playbook < 400ms p95
    analytics_api_duration: ['p(95)<700'],    // Analytics < 700ms p95
    social_api_duration: ['p(95)<500'],       // Social API < 500ms p95

    // Isolation
    tenant_isolation_failures: ['count<1'],   // Zero isolation failures
  },
};

// =============================================================================
// HELPER: Get tenant for current VU
// =============================================================================

function getCurrentTenant() {
  return TENANTS[(__VU - 1) % TENANTS.length];
}

function getHeaders(tenant) {
  return {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-Tenant-Id': String(tenant.id),
  };
}

// =============================================================================
// DEFAULT FUNCTION — Multi-tenant API
// =============================================================================

export default function () {
  var tenant = getCurrentTenant();
  var headers = getHeaders(tenant);

  group('CRM API — ' + tenant.name, function () {
    testCrmCompanies(tenant, headers);
    testCrmOpportunities(tenant, headers);
    testCrmForecast(tenant, headers);
  });

  group('Pipeline API — ' + tenant.name, function () {
    testPipelineStages(tenant, headers);
    testPipelinePlaybook(tenant, headers);
  });

  group('Analytics API — ' + tenant.name, function () {
    testAnalyticsEndpoints(tenant, headers);
  });

  group('Social API — ' + tenant.name, function () {
    testSocialEndpoints(tenant, headers);
  });

  sleep(Math.random() * 2 + 0.5);
}

// =============================================================================
// CRM API TESTS
// =============================================================================

function testCrmCompanies(tenant, headers) {
  var res = http.get(BASE_URL + '/api/v1/crm/companies', {
    headers: headers,
    tags: { name: 'crm_companies_list', tenant: tenant.name },
  });

  var passed = check(res, {
    'companies list responds': function (r) {
      return r.status === 200 || r.status === 403;
    },
  });
  errorRate.add(!passed);
  crmApiTime.add(res.timings.duration);
}

function testCrmOpportunities(tenant, headers) {
  var res = http.get(BASE_URL + '/api/v1/crm/opportunities', {
    headers: headers,
    tags: { name: 'crm_opportunities_list', tenant: tenant.name },
  });

  var passed = check(res, {
    'opportunities list responds': function (r) {
      return r.status === 200 || r.status === 403;
    },
  });
  errorRate.add(!passed);
  crmApiTime.add(res.timings.duration);
}

function testCrmForecast(tenant, headers) {
  var res = http.get(BASE_URL + '/api/v1/crm/forecast', {
    headers: headers,
    tags: { name: 'crm_forecast', tenant: tenant.name },
  });

  var passed = check(res, {
    'forecast responds': function (r) {
      return r.status === 200 || r.status === 403;
    },
    'forecast within time': function (r) {
      return r.timings.duration < 1000;
    },
  });
  errorRate.add(!passed);
  crmApiTime.add(res.timings.duration);
}

// =============================================================================
// PIPELINE OPERATIONS
// =============================================================================

function testPipelineStages(tenant, headers) {
  var res = http.get(BASE_URL + '/api/v1/crm/pipeline-stages', {
    headers: headers,
    tags: { name: 'pipeline_stages', tenant: tenant.name },
  });

  var passed = check(res, {
    'pipeline stages responds': function (r) {
      return r.status === 200 || r.status === 403;
    },
  });
  errorRate.add(!passed);
  pipelineTime.add(res.timings.duration);
}

function testPipelinePlaybook(tenant, headers) {
  // Test playbook endpoint with opportunity ID 1 (may not exist, should return 404 or 200)
  var res = http.get(BASE_URL + '/api/v1/crm/opportunities/1/playbook', {
    headers: headers,
    tags: { name: 'playbook_recommendation', tenant: tenant.name },
  });

  var passed = check(res, {
    'playbook responds (200 or 404)': function (r) {
      return r.status === 200 || r.status === 404 || r.status === 403;
    },
  });
  errorRate.add(!passed);
  playBookTime.add(res.timings.duration);
}

// =============================================================================
// ANALYTICS API
// =============================================================================

function testAnalyticsEndpoints(tenant, headers) {
  var endpoints = [
    '/api/v1/analytics/metrics',
    '/api/v1/analytics/engagement',
  ];

  endpoints.forEach(function (endpoint) {
    var res = http.get(BASE_URL + endpoint, {
      headers: headers,
      tags: { name: 'analytics_' + endpoint.split('/').pop(), tenant: tenant.name },
    });

    var passed = check(res, {
      'analytics responds': function (r) {
        return r.status === 200 || r.status === 403 || r.status === 404;
      },
    });
    errorRate.add(!passed);
    analyticsTime.add(res.timings.duration);
  });
}

// =============================================================================
// SOCIAL API
// =============================================================================

function testSocialEndpoints(tenant, headers) {
  var res = http.get(BASE_URL + '/api/v1/social/accounts', {
    headers: headers,
    tags: { name: 'social_accounts', tenant: tenant.name },
  });

  var passed = check(res, {
    'social API responds': function (r) {
      return r.status === 200 || r.status === 403 || r.status === 404;
    },
  });
  errorRate.add(!passed);
  socialApiTime.add(res.timings.duration);
}

// =============================================================================
// TENANT ISOLATION VERIFICATION
// =============================================================================

/**
 * Verifies that tenant A cannot access tenant B's data.
 * Makes requests with Tenant A's context and checks for data leakage.
 */
export function tenantIsolation() {
  if (TENANTS.length < 2) {
    return;
  }

  var tenantA = TENANTS[(__VU - 1) % TENANTS.length];
  var tenantB = TENANTS[__VU % TENANTS.length];

  group('Isolation: ' + tenantA.name + ' vs ' + tenantB.name, function () {
    var headersA = getHeaders(tenantA);

    // Request companies as Tenant A
    var res = http.get(BASE_URL + '/api/v1/crm/companies', {
      headers: headersA,
      tags: { name: 'isolation_check', tenant: tenantA.name },
    });

    if (res.status === 200) {
      try {
        var body = JSON.parse(res.body);
        if (body.data && Array.isArray(body.data)) {
          body.data.forEach(function (item) {
            if (item.tenant_id && String(item.tenant_id) !== String(tenantA.id)) {
              tenantIsolationFailures.add(1);
              console.error(
                'ISOLATION FAILURE: Tenant ' + tenantA.id +
                ' received data from tenant ' + item.tenant_id
              );
            }
          });
        }
      }
      catch (e) {
        // Response might not be JSON, that's OK
      }
    }
  });

  sleep(1);
}

// =============================================================================
// SCALING BREAKPOINT — Heavy concurrent load
// =============================================================================

export function scalingBreakpoint() {
  var tenant = getCurrentTenant();
  var headers = getHeaders(tenant);

  // Rapid-fire API calls to find the breaking point
  var batchRequests = [
    ['GET', BASE_URL + '/api/v1/crm/companies', null, { headers: headers, tags: { name: 'bp_companies' } }],
    ['GET', BASE_URL + '/api/v1/crm/opportunities', null, { headers: headers, tags: { name: 'bp_opportunities' } }],
    ['GET', BASE_URL + '/api/v1/crm/pipeline-stages', null, { headers: headers, tags: { name: 'bp_stages' } }],
    ['GET', BASE_URL + '/api/v1/crm/forecast', null, { headers: headers, tags: { name: 'bp_forecast' } }],
  ];

  var responses = http.batch(batchRequests);

  responses.forEach(function (res, i) {
    var passed = check(res, {
      'breakpoint request ok': function (r) {
        return r.status === 200 || r.status === 403 || r.status === 404;
      },
    });
    errorRate.add(!passed);
  });

  sleep(0.2);
}

// =============================================================================
// LIFECYCLE
// =============================================================================

export function setup() {
  var res = http.get(BASE_URL);
  console.log('Multi-tenant load test targeting: ' + BASE_URL);
  console.log('Simulating ' + TENANT_COUNT + ' tenants');

  if (res.status !== 200) {
    console.warn('Base URL returned status ' + res.status);
  }

  return {
    baseUrl: BASE_URL,
    tenantCount: TENANT_COUNT,
  };
}

export function teardown(data) {
  console.log('Multi-tenant load test completed.');
  console.log('Tenants tested: ' + data.tenantCount);
}
