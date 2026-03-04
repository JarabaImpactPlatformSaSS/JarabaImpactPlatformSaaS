/**
 * Jaraba Impact Platform — Official JavaScript SDK type declarations.
 */

export interface JarabaClientOptions {
  /** Tenant API key (required). */
  apiKey: string;
  /** Base URL of the API. Defaults to production. */
  baseUrl?: string;
  /** Request timeout in milliseconds. Defaults to 30000. */
  timeout?: number;
}

export interface PaginationMeta {
  total: number;
  limit: number;
  offset: number;
}

export interface ApiResponse<T = Record<string, unknown>> {
  data?: T;
  meta?: PaginationMeta;
  [key: string]: unknown;
}

export class JarabaError extends Error {
  statusCode?: number;
  body?: Record<string, unknown>;
}

export class AuthenticationError extends JarabaError {}
export class NotFoundError extends JarabaError {}
export class RateLimitError extends JarabaError {
  retryAfter?: number;
}
export class ValidationError extends JarabaError {}

export class TenantsResource {
  list(params?: Record<string, unknown>): Promise<ApiResponse>;
  get(tenantId: number): Promise<ApiResponse>;
  usage(tenantId: number): Promise<ApiResponse>;
}

export class AnalyticsResource {
  track(eventName: string, options?: {
    properties?: Record<string, unknown>;
    userId?: number;
  }): Promise<ApiResponse>;
  dashboard(params?: Record<string, unknown>): Promise<ApiResponse>;
  funnel(steps: string[], options?: {
    startDate?: string;
    endDate?: string;
  }): Promise<ApiResponse>;
}

export class ContentResource {
  listArticles(params?: Record<string, unknown>): Promise<ApiResponse>;
  createArticle(data: {
    title: string;
    body: string;
    categoryId?: number;
    status?: string;
  }): Promise<ApiResponse>;
  generateArticle(options: {
    topic: string;
    tone?: string;
    length?: string;
  }): Promise<ApiResponse>;
}

export class CopilotResource {
  chat(message: string, options?: {
    conversationId?: string;
    mode?: string;
  }): Promise<ApiResponse>;
  modes(): Promise<ApiResponse>;
}

export class AgentsResource {
  list(): Promise<ApiResponse>;
  get(agentId: string): Promise<ApiResponse>;
  execute(agentId: string, options?: {
    input?: string;
    parameters?: Record<string, unknown>;
  }): Promise<ApiResponse>;
}

export class BillingResource {
  listPlans(options?: { vertical?: string }): Promise<ApiResponse>;
  getSubscription(): Promise<ApiResponse>;
  listInvoices(): Promise<ApiResponse>;
  listPaymentMethods(): Promise<ApiResponse>;
  prorationPreview(newPriceId: string): Promise<ApiResponse>;
}

export class WebhooksResource {
  list(): Promise<ApiResponse>;
  create(data: {
    url: string;
    events: string[];
    secret?: string;
  }): Promise<ApiResponse>;
  delete(hookId: number): Promise<ApiResponse>;
}

export class CrmResource {
  listContacts(params?: Record<string, unknown>): Promise<ApiResponse>;
  createContact(data: Record<string, unknown>): Promise<ApiResponse>;
  listCompanies(params?: Record<string, unknown>): Promise<ApiResponse>;
  createCompany(data: Record<string, unknown>): Promise<ApiResponse>;
  listOpportunities(params?: Record<string, unknown>): Promise<ApiResponse>;
  createOpportunity(data: {
    name: string;
    value: number;
    stage?: string;
  }): Promise<ApiResponse>;
  pipelineStages(): Promise<ApiResponse>;
}

export class JarabaClient {
  constructor(options: JarabaClientOptions);

  tenants: TenantsResource;
  analytics: AnalyticsResource;
  content: ContentResource;
  copilot: CopilotResource;
  agents: AgentsResource;
  billing: BillingResource;
  webhooks: WebhooksResource;
  crm: CrmResource;
}
