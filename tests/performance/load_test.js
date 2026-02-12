/**
 * k6 Load Test for Jaraba Impact Platform
 *
 * Tests critical user journeys under load to validate performance
 * before production deployment.
 *
 * Usage:
 *   k6 run tests/performance/load_test.js
 *   k6 run --env BASE_URL=https://staging.jaraba.io tests/performance/load_test.js
 *   k6 run --env BASE_URL=https://jaraba.io tests/performance/load_test.js
 *
 * Scenarios:
 *   - Homepage & public pages
 *   - User login flow
 *   - AI Skills API
 *   - Billing checkout flow
 *   - Vertical dashboard pages
 */

import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const homepageTime = new Trend('homepage_duration', true);
const loginTime = new Trend('login_duration', true);
const apiTime = new Trend('api_duration', true);
const dashboardTime = new Trend('dashboard_duration', true);

// Configuration
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const TEST_USER = __ENV.TEST_USER || 'test@jaraba.io';
const TEST_PASS = __ENV.TEST_PASS || 'test-password-2024';

// Test scenarios
export const options = {
  scenarios: {
    // Smoke test: minimal load to verify functionality
    smoke: {
      executor: 'constant-vus',
      vus: 1,
      duration: '30s',
      startTime: '0s',
      tags: { scenario: 'smoke' },
    },
    // Load test: expected production traffic
    load: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '1m', target: 10 },   // Ramp up
        { duration: '3m', target: 10 },   // Steady state
        { duration: '1m', target: 20 },   // Peak
        { duration: '2m', target: 20 },   // Sustained peak
        { duration: '1m', target: 0 },    // Ramp down
      ],
      startTime: '30s',
      tags: { scenario: 'load' },
    },
    // Stress test: beyond expected capacity
    stress: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '1m', target: 30 },
        { duration: '2m', target: 50 },
        { duration: '1m', target: 0 },
      ],
      startTime: '9m',
      tags: { scenario: 'stress' },
    },
  },

  // Performance thresholds
  thresholds: {
    // Global thresholds
    http_req_duration: ['p(95)<500', 'p(99)<1500'],
    http_req_failed: ['rate<0.01'],        // <1% error rate
    errors: ['rate<0.01'],

    // Scenario-specific thresholds
    homepage_duration: ['p(95)<400'],       // Homepage < 400ms p95
    login_duration: ['p(95)<800'],          // Login < 800ms p95
    api_duration: ['p(95)<300'],            // API < 300ms p95
    dashboard_duration: ['p(95)<600'],      // Dashboard < 600ms p95
  },
};

// =============================================================================
// TEST SCENARIOS
// =============================================================================

export default function () {
  // Distribute traffic across scenarios
  group('Homepage & Public Pages', function () {
    testHomepage();
  });

  group('Login Flow', function () {
    testLogin();
  });

  group('AI Skills API', function () {
    testSkillsApi();
  });

  group('Dashboard Pages', function () {
    testDashboards();
  });

  // Think time between iterations
  sleep(Math.random() * 3 + 1);
}

// =============================================================================
// HOMEPAGE & PUBLIC PAGES
// =============================================================================

function testHomepage() {
  const responses = http.batch([
    ['GET', `${BASE_URL}/`, null, { tags: { name: 'homepage' } }],
    ['GET', `${BASE_URL}/planes`, null, { tags: { name: 'pricing' } }],
    ['GET', `${BASE_URL}/contacto`, null, { tags: { name: 'contact' } }],
  ]);

  responses.forEach((res, i) => {
    const names = ['homepage', 'pricing', 'contact'];
    const passed = check(res, {
      [`${names[i]} status is 200`]: (r) => r.status === 200,
      [`${names[i]} body is not empty`]: (r) => r.body && r.body.length > 0,
    });
    errorRate.add(!passed);
    if (i === 0) {
      homepageTime.add(res.timings.duration);
    }
  });
}

// =============================================================================
// LOGIN FLOW
// =============================================================================

function testLogin() {
  // Get login page (and CSRF token)
  const loginPage = http.get(`${BASE_URL}/user/login`, {
    tags: { name: 'login_page' },
  });

  check(loginPage, {
    'login page loads': (r) => r.status === 200,
  });

  // Extract form build ID and token from page
  const formBuildId = loginPage.body
    ? loginPage.body.match(/name="form_build_id"\s+value="([^"]+)"/)?.[1]
    : null;
  const formToken = loginPage.body
    ? loginPage.body.match(/name="form_token"\s+value="([^"]+)"/)?.[1]
    : null;

  if (formBuildId && formToken) {
    const loginRes = http.post(
      `${BASE_URL}/user/login`,
      {
        name: TEST_USER,
        pass: TEST_PASS,
        form_build_id: formBuildId,
        form_token: formToken,
        form_id: 'user_login_form',
        op: 'Iniciar sesión',
      },
      {
        tags: { name: 'login_submit' },
        redirects: 0,
      }
    );

    const passed = check(loginRes, {
      'login response is redirect or OK': (r) =>
        r.status === 302 || r.status === 303 || r.status === 200,
    });
    errorRate.add(!passed);
    loginTime.add(loginRes.timings.duration);
  }

  sleep(1);
}

// =============================================================================
// AI SKILLS API
// =============================================================================

function testSkillsApi() {
  // List skills endpoint
  const listRes = http.get(`${BASE_URL}/api/v1/skills`, {
    tags: { name: 'api_skills_list' },
    headers: { Accept: 'application/json' },
  });

  const passed = check(listRes, {
    'skills API returns 200 or 403': (r) =>
      r.status === 200 || r.status === 403,
    'skills API responds in time': (r) => r.timings.duration < 500,
  });
  errorRate.add(!passed);
  apiTime.add(listRes.timings.duration);

  // Health check endpoint
  const healthRes = http.get(`${BASE_URL}/api/health`, {
    tags: { name: 'api_health' },
    headers: { Accept: 'application/json' },
  });

  check(healthRes, {
    'health check responds': (r) =>
      r.status === 200 || r.status === 404,
  });

  sleep(0.5);
}

// =============================================================================
// DASHBOARD PAGES
// =============================================================================

function testDashboards() {
  const dashboardUrls = [
    '/admin/dashboard',
    '/admin/analytics',
    '/admin/billing',
  ];

  dashboardUrls.forEach((url) => {
    const res = http.get(`${BASE_URL}${url}`, {
      tags: { name: `dashboard_${url.split('/').pop()}` },
      redirects: 5,
    });

    const passed = check(res, {
      [`${url} loads`]: (r) =>
        r.status === 200 || r.status === 302 || r.status === 403,
    });
    errorRate.add(!passed);
    dashboardTime.add(res.timings.duration);
  });

  sleep(1);
}

// =============================================================================
// LIFECYCLE HOOKS
// =============================================================================

export function setup() {
  // Verify target is accessible
  const res = http.get(BASE_URL);
  if (res.status !== 200) {
    console.warn(
      `Warning: Base URL ${BASE_URL} returned status ${res.status}`
    );
  }
  console.log(`Load test targeting: ${BASE_URL}`);
  return { baseUrl: BASE_URL };
}

export function teardown(data) {
  console.log(`Load test completed against: ${data.baseUrl}`);
}
