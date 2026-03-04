/**
 * @file Main client for Jaraba Impact Platform API.
 */

import {
  JarabaError,
  AuthenticationError,
  ForbiddenError,
  NotFoundError,
  RateLimitError,
  ValidationError,
} from './errors.js';

const DEFAULT_BASE_URL = 'https://plataformadeecosistemas.es/api/v1';
const DEFAULT_TIMEOUT = 30000;

/**
 * Jaraba Impact Platform API client.
 *
 * @example
 * import { JarabaClient } from '@jaraba/sdk';
 *
 * const client = new JarabaClient({ apiKey: 'jrb_...' });
 *
 * // List tenants
 * const tenants = await client.tenants.list();
 *
 * // Track analytics event
 * await client.analytics.track('page_view', { properties: { path: '/home' } });
 *
 * // Chat with AI copilot
 * const response = await client.copilot.chat('How can I improve my business?');
 */
export class JarabaClient {
  /**
   * @param {Object} options
   * @param {string} options.apiKey - Tenant API key.
   * @param {string} [options.baseUrl] - API base URL.
   * @param {number} [options.timeout] - Request timeout in ms.
   */
  constructor({ apiKey, baseUrl, timeout } = {}) {
    if (!apiKey) {
      throw new AuthenticationError('API key is required');
    }

    this._apiKey = apiKey;
    this._baseUrl = (baseUrl || DEFAULT_BASE_URL).replace(/\/+$/, '');
    this._timeout = timeout || DEFAULT_TIMEOUT;

    // Resource namespaces.
    this.tenants = new TenantsResource(this);
    this.analytics = new AnalyticsResource(this);
    this.content = new ContentResource(this);
    this.copilot = new CopilotResource(this);
    this.agents = new AgentsResource(this);
    this.billing = new BillingResource(this);
    this.webhooks = new WebhooksResource(this);
    this.crm = new CrmResource(this);
  }

  /**
   * Execute an HTTP request.
   * @param {string} method
   * @param {string} path
   * @param {Object} [options]
   * @param {Object} [options.json]
   * @param {Object} [options.params]
   * @returns {Promise<Object>}
   */
  async _request(method, path, { json, params } = {}) {
    let url = `${this._baseUrl}${path}`;

    if (params) {
      const qs = new URLSearchParams();
      for (const [k, v] of Object.entries(params)) {
        if (v !== undefined && v !== null) {
          qs.set(k, String(v));
        }
      }
      const qsStr = qs.toString();
      if (qsStr) {
        url += `?${qsStr}`;
      }
    }

    const headers = {
      'X-API-Key': this._apiKey,
      'Accept': 'application/json',
      'User-Agent': '@jaraba/sdk-js/1.0.0',
    };

    const init = { method, headers, signal: AbortSignal.timeout(this._timeout) };

    if (json) {
      headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(json);
    }

    const response = await fetch(url, init);
    return this._handleResponse(response);
  }

  /**
   * Parse response and raise appropriate exceptions.
   * @param {Response} response
   * @returns {Promise<Object>}
   */
  async _handleResponse(response) {
    if (response.status === 204) {
      return {};
    }

    let body;
    try {
      body = await response.json();
    }
    catch {
      body = {};
    }

    if (response.ok) {
      return body;
    }

    let errorMsg = '';
    if (body?.error) {
      errorMsg = typeof body.error === 'object'
        ? body.error.message || ''
        : String(body.error);
    }
    errorMsg = errorMsg || `HTTP ${response.status}`;

    switch (response.status) {
      case 400:
        throw new ValidationError(errorMsg, body);
      case 401:
        throw new AuthenticationError(errorMsg, body);
      case 403:
        throw new ForbiddenError(errorMsg, body);
      case 404:
        throw new NotFoundError(errorMsg, body);
      case 429: {
        const retryAfter = response.headers.get('Retry-After');
        throw new RateLimitError(
          errorMsg,
          retryAfter ? parseInt(retryAfter, 10) : null,
          body,
        );
      }
      default:
        throw new JarabaError(errorMsg, response.status, body);
    }
  }

  /** @param {string} path */
  async get(path, params) {
    return this._request('GET', path, { params });
  }

  /** @param {string} path */
  async post(path, data) {
    return this._request('POST', path, { json: data });
  }

  /** @param {string} path */
  async patch(path, data) {
    return this._request('PATCH', path, { json: data });
  }

  /** @param {string} path */
  async delete(path) {
    return this._request('DELETE', path);
  }
}

// === Resource Classes ======================================================

class TenantsResource {
  constructor(client) { this._c = client; }
  list(params) { return this._c.get('/tenants', params); }
  get(tenantId) { return this._c.get(`/tenants/${tenantId}`); }
  usage(tenantId) { return this._c.get(`/tenants/${tenantId}/usage`); }
}

class AnalyticsResource {
  constructor(client) { this._c = client; }
  track(eventName, { properties, userId } = {}) {
    const payload = { event: eventName };
    if (properties) payload.properties = properties;
    if (userId != null) payload.user_id = userId;
    return this._c.post('/analytics/event', payload);
  }
  dashboard(params) { return this._c.get('/analytics/dashboard', params); }
  funnel(steps, { startDate, endDate } = {}) {
    const payload = { steps };
    if (startDate) payload.start_date = startDate;
    if (endDate) payload.end_date = endDate;
    return this._c.post('/analytics/funnel', payload);
  }
}

class ContentResource {
  constructor(client) { this._c = client; }
  listArticles(params) { return this._c.get('/content/articles', params); }
  createArticle({ title, body, categoryId, status = 'draft' }) {
    const payload = { title, body, status };
    if (categoryId != null) payload.category_id = categoryId;
    return this._c.post('/content/articles', payload);
  }
  generateArticle({ topic, tone = 'professional', length = 'medium' }) {
    return this._c.post('/content/ai/full-article', { topic, tone, length });
  }
}

class CopilotResource {
  constructor(client) { this._c = client; }
  chat(message, { conversationId, mode } = {}) {
    const payload = { message };
    if (conversationId) payload.conversation_id = conversationId;
    if (mode) payload.mode = mode;
    return this._c.post('/copilot/chat', payload);
  }
  modes() { return this._c.get('/copilot/modes'); }
}

class AgentsResource {
  constructor(client) { this._c = client; }
  list() { return this._c.get('/agents'); }
  get(agentId) { return this._c.get(`/agents/${agentId}`); }
  execute(agentId, { input = '', parameters } = {}) {
    const payload = { input };
    if (parameters) payload.parameters = parameters;
    return this._c.post(`/agents/${agentId}/execute`, payload);
  }
}

class BillingResource {
  constructor(client) { this._c = client; }
  listPlans({ vertical } = {}) {
    const params = {};
    if (vertical) params.vertical = vertical;
    return this._c.get('/plans', params);
  }
  getSubscription() { return this._c.get('/billing/subscription'); }
  listInvoices() { return this._c.get('/billing/invoices'); }
  listPaymentMethods() { return this._c.get('/billing/payment-methods'); }
  prorationPreview(newPriceId) {
    return this._c.get('/billing/proration-preview', { new_price_id: newPriceId });
  }
}

class WebhooksResource {
  constructor(client) { this._c = client; }
  list() { return this._c.get('/webhooks'); }
  create({ url, events, secret }) {
    const payload = { url, events };
    if (secret) payload.secret = secret;
    return this._c.post('/webhooks', payload);
  }
  delete(hookId) { return this._c.delete(`/webhooks/${hookId}`); }
}

class CrmResource {
  constructor(client) { this._c = client; }
  listContacts(params) { return this._c.get('/crm/contacts', params); }
  createContact(data) { return this._c.post('/crm/contacts', data); }
  listCompanies(params) { return this._c.get('/crm/companies', params); }
  createCompany(data) { return this._c.post('/crm/companies', data); }
  listOpportunities(params) { return this._c.get('/crm/opportunities', params); }
  createOpportunity({ name, value, stage = 'lead', ...extra }) {
    return this._c.post('/crm/opportunities', { name, value, stage, ...extra });
  }
  pipelineStages() { return this._c.get('/crm/pipeline-stages'); }
}
