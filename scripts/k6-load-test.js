/**
 * Jaraba Impact Platform SaaS - k6 Load Test Configuration
 * Phase 5 Go-Live Preparation
 *
 * This script tests the platform under realistic load conditions covering:
 *  - Homepage (anonymous)
 *  - API health endpoint
 *  - Login flow with CSRF token
 *  - Authenticated Content Hub dashboard
 *  - Authenticated RAG API query
 *
 * Usage:
 *   k6 run scripts/k6-load-test.js
 *   k6 run --env BASE_URL=https://staging.jaraba.com scripts/k6-load-test.js
 *   k6 run --env BASE_URL=https://staging.jaraba.com --env USERNAME=test@jaraba.com --env PASSWORD=testpass scripts/k6-load-test.js
 *
 * Requirements:
 *   k6 >= 0.45.0 (https://k6.io)
 */

import http from 'k6/http';
import { check, group, sleep, fail } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// ---------------------------------------------------------------------------
// Custom Metrics
// ---------------------------------------------------------------------------
const errorRate = new Rate('errors');
const homepageResponseTime = new Trend('homepage_response_time', true);
const apiHealthResponseTime = new Trend('api_health_response_time', true);
const loginResponseTime = new Trend('login_response_time', true);
const contentHubResponseTime = new Trend('content_hub_response_time', true);
const ragQueryResponseTime = new Trend('rag_query_response_time', true);
const successfulLogins = new Counter('successful_logins');

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------
const BASE_URL = __ENV.BASE_URL || 'https://platform.jaraba.com';
const USERNAME = __ENV.USERNAME || 'loadtest@jaraba.com';
const PASSWORD = __ENV.PASSWORD || 'LoadTest2026!';

export const options = {
  // Stages: ramp-up -> sustain -> ramp-down
  stages: [
    { duration: '1m', target: 50 },   // Ramp-up: 0 -> 50 VUs over 1 minute
    { duration: '3m', target: 50 },   // Sustain: hold 50 VUs for 3 minutes
    { duration: '1m', target: 0 },    // Ramp-down: 50 -> 0 VUs over 1 minute
  ],

  // Performance thresholds
  thresholds: {
    // Global thresholds
    'http_req_duration': ['p(95)<2000'],   // 95th percentile < 2s
    'errors': ['rate<0.05'],                // Error rate < 5%

    // Per-endpoint thresholds
    'homepage_response_time': ['p(95)<1500'],
    'api_health_response_time': ['p(95)<500'],
    'login_response_time': ['p(95)<2000'],
    'content_hub_response_time': ['p(95)<2500'],
    'rag_query_response_time': ['p(95)<3000'],
  },

  // HTTP-level settings
  insecureSkipTLSVerify: false,
  userAgent: 'JarabaLoadTest/1.0 (k6)',

  // Tags for result grouping
  tags: {
    project: 'jaraba-impact-platform',
    phase: 'go-live',
  },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Extract a CSRF token from the login page HTML.
 * Drupal renders the token inside a hidden input field.
 */
function extractCsrfToken(html) {
  // Pattern for Drupal form_build_id
  const formBuildIdMatch = html.match(
    /name="form_build_id"\s+value="([^"]+)"/
  );
  // Pattern for form_token
  const formTokenMatch = html.match(
    /name="form_token"\s+value="([^"]+)"/
  );

  return {
    formBuildId: formBuildIdMatch ? formBuildIdMatch[1] : '',
    formToken: formTokenMatch ? formTokenMatch[1] : '',
  };
}

/**
 * Extract the session cookie name and value from response cookies.
 */
function getSessionCookie(response) {
  const cookies = response.cookies;
  for (const name in cookies) {
    if (name.startsWith('SSESS') || name.startsWith('SESS')) {
      return { name, value: cookies[name][0].value };
    }
  }
  return null;
}

/**
 * Standard headers for API requests.
 */
function apiHeaders(sessionCookie, csrfToken) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };
  if (csrfToken) {
    headers['X-CSRF-Token'] = csrfToken;
  }
  return headers;
}

// ---------------------------------------------------------------------------
// Test Scenarios
// ---------------------------------------------------------------------------

export default function () {
  let sessionCookie = null;
  let csrfToken = '';

  // -----------------------------------------------------------------------
  // Scenario 1: Homepage (Anonymous GET)
  // -----------------------------------------------------------------------
  group('01 - Homepage', () => {
    const res = http.get(`${BASE_URL}/`, {
      tags: { endpoint: 'homepage' },
      headers: {
        'Accept': 'text/html,application/xhtml+xml',
        'Accept-Language': 'es-ES,es;q=0.9,en;q=0.8',
      },
    });

    homepageResponseTime.add(res.timings.duration);

    const passed = check(res, {
      'homepage: status is 200': (r) => r.status === 200,
      'homepage: contains expected HTML': (r) =>
        r.body.includes('</html>'),
      'homepage: response time < 2s': (r) => r.timings.duration < 2000,
      'homepage: no server error': (r) => r.status < 500,
    });

    errorRate.add(!passed);
  });

  sleep(1);

  // -----------------------------------------------------------------------
  // Scenario 2: API Health Endpoint
  // -----------------------------------------------------------------------
  group('02 - API Health', () => {
    const res = http.get(`${BASE_URL}/api/v1/health`, {
      tags: { endpoint: 'api_health' },
      headers: {
        'Accept': 'application/json',
      },
    });

    apiHealthResponseTime.add(res.timings.duration);

    const passed = check(res, {
      'health: status is 200': (r) => r.status === 200,
      'health: returns JSON': (r) => {
        try {
          const body = JSON.parse(r.body);
          return body.status === 'ok' || body.status === 'healthy';
        } catch (e) {
          return false;
        }
      },
      'health: response time < 500ms': (r) => r.timings.duration < 500,
    });

    errorRate.add(!passed);
  });

  sleep(1);

  // -----------------------------------------------------------------------
  // Scenario 3: Login Flow (POST with CSRF)
  // -----------------------------------------------------------------------
  group('03 - Login Flow', () => {
    // Step 1: GET login page to obtain CSRF tokens
    const loginPage = http.get(`${BASE_URL}/user/login`, {
      tags: { endpoint: 'login_page' },
      headers: {
        'Accept': 'text/html,application/xhtml+xml',
      },
    });

    check(loginPage, {
      'login page: status is 200': (r) => r.status === 200,
    });

    const tokens = extractCsrfToken(loginPage.body || '');

    // Step 2: POST login credentials
    const loginPayload = {
      name: USERNAME,
      pass: PASSWORD,
      form_build_id: tokens.formBuildId,
      form_token: tokens.formToken,
      form_id: 'user_login_form',
      op: 'Log in',
    };

    const loginRes = http.post(`${BASE_URL}/user/login`, loginPayload, {
      tags: { endpoint: 'login_submit' },
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': 'text/html,application/xhtml+xml',
      },
      redirects: 5,
    });

    loginResponseTime.add(loginRes.timings.duration);

    const loginPassed = check(loginRes, {
      'login: redirects or returns 200': (r) =>
        r.status === 200 || r.status === 303 || r.status === 302,
      'login: no error message in body': (r) =>
        !r.body.includes('Unrecognized username or password'),
      'login: response time < 2s': (r) => r.timings.duration < 2000,
    });

    if (loginPassed) {
      successfulLogins.add(1);
    }

    errorRate.add(!loginPassed);

    // Extract session cookie for authenticated requests
    sessionCookie = getSessionCookie(loginRes);

    // Get CSRF token for REST API calls
    if (sessionCookie) {
      const tokenRes = http.get(`${BASE_URL}/session/token`, {
        tags: { endpoint: 'session_token' },
      });
      if (tokenRes.status === 200) {
        csrfToken = tokenRes.body;
      }
    }
  });

  sleep(1);

  // -----------------------------------------------------------------------
  // Scenario 4: Content Hub Dashboard (Authenticated)
  // -----------------------------------------------------------------------
  group('04 - Content Hub Dashboard', () => {
    const headers = {
      'Accept': 'text/html,application/xhtml+xml',
      'Accept-Language': 'es-ES,es;q=0.9,en;q=0.8',
    };

    const res = http.get(`${BASE_URL}/content-hub`, {
      tags: { endpoint: 'content_hub' },
      headers,
    });

    contentHubResponseTime.add(res.timings.duration);

    const passed = check(res, {
      'content-hub: status is 200 or 403': (r) =>
        r.status === 200 || r.status === 403,
      'content-hub: page renders': (r) =>
        r.body.includes('</html>'),
      'content-hub: response time < 2.5s': (r) =>
        r.timings.duration < 2500,
      'content-hub: no 500 error': (r) => r.status < 500,
    });

    errorRate.add(!passed);
  });

  sleep(1);

  // -----------------------------------------------------------------------
  // Scenario 5: RAG API Query (Authenticated POST)
  // -----------------------------------------------------------------------
  group('05 - RAG API Query', () => {
    const payload = JSON.stringify({
      query: 'What training courses are available for digital skills?',
      context: 'empleabilidad',
      max_results: 5,
    });

    const headers = apiHeaders(sessionCookie, csrfToken);

    const res = http.post(`${BASE_URL}/api/v1/jaraba-rag/query`, payload, {
      tags: { endpoint: 'rag_query' },
      headers,
    });

    ragQueryResponseTime.add(res.timings.duration);

    const passed = check(res, {
      'rag: status is 200 or 401': (r) =>
        r.status === 200 || r.status === 401 || r.status === 403,
      'rag: returns JSON': (r) => {
        try {
          JSON.parse(r.body);
          return true;
        } catch (e) {
          // 401/403 might not return JSON
          return r.status === 401 || r.status === 403;
        }
      },
      'rag: response time < 3s': (r) => r.timings.duration < 3000,
      'rag: no 500 error': (r) => r.status < 500,
    });

    errorRate.add(!passed);
  });

  // Variable think-time between iterations (1-3 seconds)
  sleep(Math.random() * 2 + 1);
}

// ---------------------------------------------------------------------------
// Setup & Teardown
// ---------------------------------------------------------------------------

/**
 * Runs once before the test starts. Use to verify the target is reachable.
 */
export function setup() {
  const res = http.get(`${BASE_URL}/api/v1/health`);

  const isHealthy = check(res, {
    'setup: target is reachable': (r) => r.status === 200,
  });

  if (!isHealthy) {
    console.warn(
      `WARNING: Health endpoint returned status ${res.status}. ` +
      `The target ${BASE_URL} may not be fully available.`
    );
  }

  return {
    baseUrl: BASE_URL,
    startTime: new Date().toISOString(),
  };
}

/**
 * Runs once after the test completes.
 */
export function teardown(data) {
  console.log(`\n===== Jaraba Load Test Complete =====`);
  console.log(`Target: ${data.baseUrl}`);
  console.log(`Started: ${data.startTime}`);
  console.log(`Finished: ${new Date().toISOString()}`);
  console.log(`=====================================\n`);
}

// ---------------------------------------------------------------------------
// Custom Summary (Optional: export to JSON)
// ---------------------------------------------------------------------------
export function handleSummary(data) {
  const summary = {
    timestamp: new Date().toISOString(),
    target: BASE_URL,
    scenarios: {
      homepage: {
        p95: data.metrics.homepage_response_time
          ? data.metrics.homepage_response_time.values['p(95)']
          : null,
      },
      api_health: {
        p95: data.metrics.api_health_response_time
          ? data.metrics.api_health_response_time.values['p(95)']
          : null,
      },
      login: {
        p95: data.metrics.login_response_time
          ? data.metrics.login_response_time.values['p(95)']
          : null,
        successful: data.metrics.successful_logins
          ? data.metrics.successful_logins.values.count
          : 0,
      },
      content_hub: {
        p95: data.metrics.content_hub_response_time
          ? data.metrics.content_hub_response_time.values['p(95)']
          : null,
      },
      rag_query: {
        p95: data.metrics.rag_query_response_time
          ? data.metrics.rag_query_response_time.values['p(95)']
          : null,
      },
    },
    thresholds_passed: !Object.values(data.metrics).some(
      (m) => m.thresholds && Object.values(m.thresholds).some((t) => !t.ok)
    ),
    error_rate: data.metrics.errors
      ? data.metrics.errors.values.rate
      : 0,
  };

  return {
    'stdout': textSummary(data, { indent: ' ', enableColors: true }),
    'load-test-results.json': JSON.stringify(summary, null, 2),
  };
}

/**
 * Minimal text summary fallback.
 */
function textSummary(data, opts) {
  // k6 provides a built-in text summary; this is a fallback
  const lines = [
    '',
    '========== JARABA LOAD TEST RESULTS ==========',
    `Target: ${BASE_URL}`,
    `VUs max: ${data.metrics.vus_max ? data.metrics.vus_max.values.max : 'N/A'}`,
    `Total requests: ${data.metrics.http_reqs ? data.metrics.http_reqs.values.count : 'N/A'}`,
    `Error rate: ${data.metrics.errors ? (data.metrics.errors.values.rate * 100).toFixed(2) + '%' : 'N/A'}`,
    '',
    'Response times (p95):',
    `  Homepage:     ${data.metrics.homepage_response_time ? Math.round(data.metrics.homepage_response_time.values['p(95)']) + 'ms' : 'N/A'}`,
    `  API Health:   ${data.metrics.api_health_response_time ? Math.round(data.metrics.api_health_response_time.values['p(95)']) + 'ms' : 'N/A'}`,
    `  Login:        ${data.metrics.login_response_time ? Math.round(data.metrics.login_response_time.values['p(95)']) + 'ms' : 'N/A'}`,
    `  Content Hub:  ${data.metrics.content_hub_response_time ? Math.round(data.metrics.content_hub_response_time.values['p(95)']) + 'ms' : 'N/A'}`,
    `  RAG Query:    ${data.metrics.rag_query_response_time ? Math.round(data.metrics.rag_query_response_time.values['p(95)']) + 'ms' : 'N/A'}`,
    '',
    '================================================',
    '',
  ];
  return lines.join('\n');
}
