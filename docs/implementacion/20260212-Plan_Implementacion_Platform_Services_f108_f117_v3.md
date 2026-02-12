# Plan de Implementacion: Platform Services v3 (Especificaciones Tecnicas 108-117)

> **Tipo:** Plan de Implementacion
> **Version:** 3.0.0
> **Fecha:** 2026-02-12
> **Autor:** Jaraba Dev Team (Claude Code)
> **Estado:** En Implementacion
> **Alcance:** 10 modulos Drupal 11 dedicados (f-108 a f-117)
> **Modulos:** jaraba_agent_flows, jaraba_pwa, jaraba_onboarding, jaraba_usage_billing, jaraba_integrations, jaraba_customer_success, jaraba_tenant_knowledge, jaraba_security_compliance, jaraba_analytics, jaraba_whitelabel

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia)
3. [Cumplimiento de las 14 Directrices](#3-cumplimiento-directrices)
4. [Fase 1 - jaraba_agent_flows (f-108)](#4-fase-1-agent-flows)
5. [Fase 2 - jaraba_pwa (f-109)](#5-fase-2-pwa)
6. [Fase 3 - jaraba_onboarding (f-110)](#6-fase-3-onboarding)
7. [Fase 4 - jaraba_usage_billing (f-111)](#7-fase-4-usage-billing)
8. [Fase 5 - jaraba_integrations (f-112)](#8-fase-5-integrations)
9. [Fase 6 - jaraba_customer_success (f-113)](#9-fase-6-customer-success)
10. [Fase 7 - jaraba_tenant_knowledge (f-114)](#10-fase-7-knowledge-base)
11. [Fase 8 - jaraba_security_compliance (f-115)](#11-fase-8-security-compliance)
12. [Fase 9 - jaraba_analytics (f-116)](#12-fase-9-analytics-bi)
13. [Fase 10 - jaraba_whitelabel (f-117)](#13-fase-10-whitelabel)
14. [Inventario Consolidado de Entidades](#14-inventario-entidades)
15. [Inventario Consolidado de Services](#15-inventario-services)
16. [Inventario Consolidado de Endpoints REST API](#16-inventario-endpoints)
17. [Paleta de Colores y Design Tokens](#17-design-tokens)
18. [Patron de Iconos SVG](#18-iconos-svg)
19. [Orden de Implementacion Global y Dependencias](#19-orden-implementacion)
20. [Estimacion de Esfuerzo](#20-estimacion)
21. [Registro de Cambios](#21-registro-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Vision

Este plan v3 consolida la implementacion definitiva de los 10 servicios de plataforma definidos en las especificaciones tecnicas f-108 a f-117. La version v1 propuso crear 10 modulos dedicados nuevos; la implementacion real (v2) distribuyo funcionalidad en modulos existentes alcanzando ~72% de completitud. Esta version v3 completa el 100% creando los modulos dedicados pendientes y cerrando gaps de UI frontend.

### 1.2 Relacion con Infraestructura Existente

Los 10 modulos se integran con la infraestructura existente:
- **ecosistema_jaraba_core**: Modulo base con TenantManager, TenantContext, StylePreset, Design Tokens
- **jaraba_billing**: Ciclo completo Stripe Billing (13 servicios, 26 endpoints)
- **jaraba_ai_agents**: Sistema de agentes IA con WorkflowExecutor, ModelRouter, Tools
- **jaraba_theming**: TenantThemeConfig, ThemeTokenService, cascada 4 niveles
- **jaraba_tenant_knowledge**: Knowledge base con DocumentProcessor, FaqBot, KnowledgeIndexer

### 1.3 Patron Arquitectonico

Todos los modulos siguen el patron **Wrapper + Extension**:
1. Los servicios nuevos **envuelven** servicios existentes via DI (nunca duplican logica)
2. Las entidades nuevas **extienden** el modelo de datos (nunca modifican entidades existentes)
3. Los controllers **componen** datos de multiples servicios para las vistas
4. Los templates **incluyen** parciales del tema base via `{% include '@ecosistema_jaraba_theme/...' only %}`

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

| Spec | Titulo | Modulo | Estado v2 | Estado v3 |
|------|--------|--------|-----------|-----------|
| f-108 | Agent Flows | jaraba_agent_flows | 0% (en jaraba_ai_agents) | 100% |
| f-109 | PWA & Offline | jaraba_pwa | 30% (push en core) | 100% |
| f-110 | Onboarding | jaraba_onboarding | 40% (en core) | 100% |
| f-111 | Usage Billing | jaraba_usage_billing | 50% (en billing) | 100% |
| f-112 | Integrations Marketplace | jaraba_integrations | 80% (existe) | 100% |
| f-113 | Customer Success | jaraba_customer_success | 85% (existe) | 100% |
| f-114 | Knowledge Base | jaraba_tenant_knowledge | 75% (existe) | 100% |
| f-115 | Security & Compliance | jaraba_security_compliance | 60% (en core) | 100% |
| f-116 | Analytics BI | jaraba_analytics | 70% (existe) | 100% |
| f-117 | White-Label | jaraba_whitelabel | 40% (en core) | 100% |

---

## 3. Cumplimiento de las 14 Directrices

| # | Directriz | Implementacion |
|---|-----------|---------------|
| D1 | i18n obligatorio | PHP: `$this->t()`, Twig: `{% trans %}`, JS: `Drupal.t()` |
| D2 | SCSS con Design Tokens | Solo `var(--ej-*, $fallback)`, nunca `$ej-*` en modulos satelite |
| D3 | Dart Sass moderno | `@use 'sass:color'`, nunca `darken()`/`lighten()` |
| D4 | Frontend limpio | Templates sin `page.content`, layouts propios |
| D5 | Body classes | `hook_preprocess_html()` para body classes por ruta |
| D6 | Slide-panel CRUD | Operaciones CRUD en slide-panel, no salir de pagina |
| D7 | Field UI / Views | `field_ui_base_route` + `views_data` en toda entidad |
| D8 | No hardcodear | Constantes para allowed_values, config para umbrales |
| D9 | Parciales Twig | `{% include '@ecosistema_jaraba_theme/partials/...' only %}` |
| D10 | Seguridad | `accessCheck(TRUE)`, sanitizacion, CSRF tokens |
| D11 | Comentarios descriptivos | Docblocks completos en espanol |
| D12 | Iconos SVG duotone | `{name}.svg` + `{name}-duotone.svg` por categoria |
| D13 | AI via @ai.provider | Toda IA via servicio `@ai.provider`, nunca directo |
| D14 | Hooks Drupal 11 | `#[Hook]` attribute cuando aplica, `hook_` functions en .module |

---

## 4. Fase 1 - jaraba_agent_flows (f-108)

### 4.1 Justificacion
Modulo dedicado para flujos de agentes IA con UI visual. Envuelve `jaraba_ai_agents.workflow_executor` proporcionando una capa de gestion, templates predefinidos, metricas de ejecucion y dashboard visual.

### 4.2 Entidades

| Entidad | Tipo | Campos Clave | Tabla |
|---------|------|-------------|-------|
| AgentFlow | ContentEntity | name, description, flow_config (JSON), trigger_type, trigger_config, status, tenant_id | agent_flow |
| AgentFlowExecution | ContentEntity | flow_id (ref), status, started_at, completed_at, result (JSON), error_message, tenant_id | agent_flow_execution |
| AgentFlowStepLog | ContentEntity | execution_id (ref), step_name, step_type, input (JSON), output (JSON), duration_ms, status | agent_flow_step_log |

### 4.3 Services

| Servicio | Responsabilidad |
|----------|----------------|
| AgentFlowExecutionService | Ejecuta flujos delegando a workflow_executor |
| AgentFlowTriggerService | Gestiona triggers (cron, webhook, manual) |
| AgentFlowValidatorService | Valida definiciones de flujo antes de ejecutar |
| AgentFlowMetricsService | Metricas de ejecucion (duracion, exitos, fallos) |
| AgentFlowTemplateService | Templates predefinidos por vertical |

### 4.4 Controllers
- `AgentFlowDashboardController`: Dashboard visual con metricas y listado
- `AgentFlowApiController`: REST API para CRUD y ejecucion

### 4.5 Frontend
- Templates: dashboard, flow-detail, execution-log
- JS: agent-flow-dashboard.js, agent-flow-builder.js
- SCSS: _agent-flow-dashboard.scss, _agent-flow-builder.scss

---

## 5. Fase 2 - jaraba_pwa (f-109)

### 5.1 Justificacion
Modulo dedicado para Progressive Web App. Migra PushSubscription y PlatformPushService desde ecosistema_jaraba_core. Anade service worker avanzado, offline-first con sync, manifest dinamico.

### 5.2 Entidades

| Entidad | Tipo | Campos Clave | Tabla |
|---------|------|-------------|-------|
| PushSubscription | ContentEntity | endpoint, p256dh_key, auth_key, user_id, tenant_id, topics, status | push_subscription |
| PendingSyncAction | ContentEntity | action_type, entity_type, entity_id, payload (JSON), status, retry_count, tenant_id | pending_sync_action |

### 5.3 Services
- PlatformPushService (migrado), PwaSyncManagerService, PwaManifestService, PwaOfflineDataService, PwaCacheStrategyService

---

## 6. Fase 3 - jaraba_onboarding (f-110)

### 6.1 Justificacion
Modulo dedicado para onboarding guiado. Envuelve TenantOnboardingService, GuidedTourService, InAppMessagingService del core.

### 6.2 Entidades
- OnboardingTemplate: Plantillas de onboarding por vertical/plan
- UserOnboardingProgress: Progreso individual de cada usuario

### 6.3 Services
- OnboardingOrchestratorService, OnboardingGamificationService, OnboardingChecklistService, OnboardingContextualHelpService, OnboardingAnalyticsService

---

## 7. Fase 4 - jaraba_usage_billing (f-111)

### 7.1 Justificacion
Modulo dedicado para billing basado en uso. Extiende jaraba_billing con pipeline de ingestion, agregacion y sync con Stripe Metered Billing.

### 7.2 Entidades
- UsageEvent: Eventos individuales de uso
- UsageAggregate: Agregaciones horarias/diarias/mensuales
- PricingRule: Reglas de pricing por metrica

### 7.3 Services
- UsageIngestionService, UsageAggregatorService, UsagePricingService, UsageStripeSyncService, UsageAlertService

---

## 8. Fase 5 - jaraba_integrations (f-112)

### 8.1 Justificacion
Extension del modulo existente con marketplace publico, portal de desarrolladores, wizard de instalacion y rate limiting.

### 8.2 Nuevos Services
- RateLimiterService, AppApprovalService, ConnectorSdkService

### 8.3 Nuevos Controllers
- MarketplaceController, DeveloperPortalController

---

## 9. Fase 6 - jaraba_customer_success (f-113)

### 9.1 Justificacion
Extension con UI frontend completa: NPS survey, health detail, churn matrix, expansion pipeline.

### 9.2 Adiciones
- NpsSurveyController, NpsApiController
- NpsSurveySubmissionForm
- Templates: health-detail, churn-matrix, nps-survey, nps-results, expansion-pipeline, health-timeline

---

## 10. Fase 7 - jaraba_tenant_knowledge (f-114)

### 10.1 Justificacion
Extension con Knowledge Base publica: articulos, categorias, videos, busqueda semantica.

### 10.2 Nuevas Entidades
- KbArticle, KbCategory, KbVideo

### 10.3 Nuevos Services
- KbSemanticSearchService, KbArticleManagerService, KbAnalyticsService

---

## 11. Fase 8 - jaraba_security_compliance (f-115)

### 11.1 Justificacion
Modulo dedicado para seguridad y compliance. Migra AuditLog, ComplianceAssessment, SecurityPolicy desde ecosistema_jaraba_core.

### 11.2 Entidades Migradas
- AuditLog, ComplianceAssessment, SecurityPolicy

### 11.3 Nuevos Services
- PolicyEnforcerService, ComplianceTrackerService, DataRetentionService

---

## 12. Fase 9 - jaraba_analytics (f-116)

### 12.1 Justificacion
Extension con BI dashboard builder, scheduled reports y widgets configurables.

### 12.2 Nuevas Entidades
- AnalyticsDashboard, ScheduledReport, DashboardWidget

### 12.3 Nuevos Services
- DashboardManagerService, ReportSchedulerService, DataService

---

## 13. Fase 10 - jaraba_whitelabel (f-117)

### 13.1 Justificacion
Modulo dedicado para white-label completo. Migra Reseller, ResellerCommissionService, BrandedPdfService desde ecosistema_jaraba_core.

### 13.2 Entidades
- Reseller (migrada), WhitelabelConfig (nueva), CustomDomain (nueva), EmailTemplate (nueva)

### 13.3 Services
- ConfigResolverService, DomainManagerService, EmailRendererService, ResellerManagerService

---

## 14. Inventario Consolidado de Entidades

| # | Entidad | Modulo | Tipo | Estado |
|---|---------|--------|------|--------|
| 1 | AgentFlow | jaraba_agent_flows | ContentEntity | Nueva |
| 2 | AgentFlowExecution | jaraba_agent_flows | ContentEntity | Nueva |
| 3 | AgentFlowStepLog | jaraba_agent_flows | ContentEntity | Nueva |
| 4 | PushSubscription | jaraba_pwa | ContentEntity | Migrada |
| 5 | PendingSyncAction | jaraba_pwa | ContentEntity | Nueva |
| 6 | OnboardingTemplate | jaraba_onboarding | ContentEntity | Nueva |
| 7 | UserOnboardingProgress | jaraba_onboarding | ContentEntity | Nueva |
| 8 | UsageEvent | jaraba_usage_billing | ContentEntity | Nueva |
| 9 | UsageAggregate | jaraba_usage_billing | ContentEntity | Nueva |
| 10 | PricingRule | jaraba_usage_billing | ContentEntity | Nueva |
| 11 | NpsSurvey | jaraba_customer_success | ContentEntity | Nueva |
| 12 | KbArticle | jaraba_tenant_knowledge | ContentEntity | Nueva |
| 13 | KbCategory | jaraba_tenant_knowledge | ContentEntity | Nueva |
| 14 | KbVideo | jaraba_tenant_knowledge | ContentEntity | Nueva |
| 15 | AuditLog | jaraba_security_compliance | ContentEntity | Migrada |
| 16 | ComplianceAssessment | jaraba_security_compliance | ContentEntity | Migrada |
| 17 | SecurityPolicy | jaraba_security_compliance | ContentEntity | Migrada |
| 18 | AnalyticsDashboard | jaraba_analytics | ContentEntity | Nueva |
| 19 | ScheduledReport | jaraba_analytics | ContentEntity | Nueva |
| 20 | DashboardWidget | jaraba_analytics | ContentEntity | Nueva |
| 21 | Reseller | jaraba_whitelabel | ContentEntity | Migrada |
| 22 | WhitelabelConfig | jaraba_whitelabel | ContentEntity | Nueva |
| 23 | CustomDomain | jaraba_whitelabel | ContentEntity | Nueva |
| 24 | EmailTemplate | jaraba_whitelabel | ContentEntity | Nueva |

---

## 15. Inventario Consolidado de Services

| # | Service ID | Modulo | Clase |
|---|-----------|--------|-------|
| 1 | jaraba_agent_flows.execution | jaraba_agent_flows | AgentFlowExecutionService |
| 2 | jaraba_agent_flows.trigger | jaraba_agent_flows | AgentFlowTriggerService |
| 3 | jaraba_agent_flows.validator | jaraba_agent_flows | AgentFlowValidatorService |
| 4 | jaraba_agent_flows.metrics | jaraba_agent_flows | AgentFlowMetricsService |
| 5 | jaraba_agent_flows.template | jaraba_agent_flows | AgentFlowTemplateService |
| 6 | jaraba_pwa.push | jaraba_pwa | PlatformPushService |
| 7 | jaraba_pwa.sync_manager | jaraba_pwa | PwaSyncManagerService |
| 8 | jaraba_pwa.manifest | jaraba_pwa | PwaManifestService |
| 9 | jaraba_pwa.offline_data | jaraba_pwa | PwaOfflineDataService |
| 10 | jaraba_pwa.cache_strategy | jaraba_pwa | PwaCacheStrategyService |
| 11 | jaraba_onboarding.orchestrator | jaraba_onboarding | OnboardingOrchestratorService |
| 12 | jaraba_onboarding.gamification | jaraba_onboarding | OnboardingGamificationService |
| 13 | jaraba_onboarding.checklist | jaraba_onboarding | OnboardingChecklistService |
| 14 | jaraba_onboarding.contextual_help | jaraba_onboarding | OnboardingContextualHelpService |
| 15 | jaraba_onboarding.analytics | jaraba_onboarding | OnboardingAnalyticsService |
| 16 | jaraba_usage_billing.ingestion | jaraba_usage_billing | UsageIngestionService |
| 17 | jaraba_usage_billing.aggregator | jaraba_usage_billing | UsageAggregatorService |
| 18 | jaraba_usage_billing.pricing | jaraba_usage_billing | UsagePricingService |
| 19 | jaraba_usage_billing.stripe_sync | jaraba_usage_billing | UsageStripeSyncService |
| 20 | jaraba_usage_billing.alert | jaraba_usage_billing | UsageAlertService |
| 21 | jaraba_integrations.rate_limiter | jaraba_integrations | RateLimiterService |
| 22 | jaraba_integrations.app_approval | jaraba_integrations | AppApprovalService |
| 23 | jaraba_integrations.connector_sdk | jaraba_integrations | ConnectorSdkService |
| 24 | jaraba_customer_success.nps_survey | jaraba_customer_success | NpsSurveyService |
| 25 | jaraba_tenant_knowledge.kb_search | jaraba_tenant_knowledge | KbSemanticSearchService |
| 26 | jaraba_tenant_knowledge.kb_manager | jaraba_tenant_knowledge | KbArticleManagerService |
| 27 | jaraba_tenant_knowledge.kb_analytics | jaraba_tenant_knowledge | KbAnalyticsService |
| 28 | jaraba_security_compliance.policy_enforcer | jaraba_security_compliance | PolicyEnforcerService |
| 29 | jaraba_security_compliance.compliance_tracker | jaraba_security_compliance | ComplianceTrackerService |
| 30 | jaraba_security_compliance.data_retention | jaraba_security_compliance | DataRetentionService |
| 31 | jaraba_analytics.dashboard_manager | jaraba_analytics | DashboardManagerService |
| 32 | jaraba_analytics.report_scheduler | jaraba_analytics | ReportSchedulerService |
| 33 | jaraba_analytics.data_service | jaraba_analytics | DataService |
| 34 | jaraba_whitelabel.config_resolver | jaraba_whitelabel | ConfigResolverService |
| 35 | jaraba_whitelabel.domain_manager | jaraba_whitelabel | DomainManagerService |
| 36 | jaraba_whitelabel.email_renderer | jaraba_whitelabel | EmailRendererService |
| 37 | jaraba_whitelabel.reseller_manager | jaraba_whitelabel | ResellerManagerService |

---

## 16. Inventario Consolidado de Endpoints REST API

| Modulo | Metodo | Path | Descripcion |
|--------|--------|------|-------------|
| agent_flows | GET | /api/v1/agent-flows | Listar flujos |
| agent_flows | POST | /api/v1/agent-flows | Crear flujo |
| agent_flows | GET | /api/v1/agent-flows/{id} | Detalle flujo |
| agent_flows | POST | /api/v1/agent-flows/{id}/execute | Ejecutar flujo |
| agent_flows | GET | /api/v1/agent-flows/{id}/executions | Historial ejecuciones |
| agent_flows | GET | /api/v1/agent-flows/templates | Templates predefinidos |
| pwa | POST | /api/v1/pwa/push/subscribe | Suscribir push |
| pwa | DELETE | /api/v1/pwa/push/unsubscribe | Desuscribir push |
| pwa | GET | /api/v1/pwa/manifest | Manifest dinamico |
| pwa | POST | /api/v1/pwa/sync | Sincronizar offline |
| onboarding | GET | /api/v1/onboarding/progress | Progreso actual |
| onboarding | POST | /api/v1/onboarding/step/{step}/complete | Completar paso |
| onboarding | GET | /api/v1/onboarding/checklist | Checklist actual |
| usage_billing | POST | /api/v1/usage/events | Ingestar evento |
| usage_billing | GET | /api/v1/usage/aggregates | Agregados |
| usage_billing | GET | /api/v1/usage/dashboard | Dashboard datos |
| integrations | GET | /integraciones/marketplace | Marketplace publico |
| integrations | GET | /integraciones/developer | Portal desarrolladores |
| customer_success | GET | /api/v1/cs/nps/survey | Encuesta NPS activa |
| customer_success | POST | /api/v1/cs/nps/submit | Enviar respuesta NPS |
| customer_success | GET | /api/v1/cs/nps/results | Resultados NPS |
| knowledge | GET | /ayuda | Help center publico |
| knowledge | GET | /api/v1/kb/search | Busqueda semantica |
| security | GET | /api/v1/security/audit/export | Exportar audit log |
| security | GET | /api/v1/security/compliance/status | Estado compliance |
| analytics | GET | /api/v1/analytics/dashboards | Listar dashboards |
| analytics | POST | /api/v1/analytics/dashboards | Crear dashboard |
| analytics | GET | /api/v1/analytics/reports/scheduled | Reports programados |
| whitelabel | GET | /api/v1/whitelabel/config | Config white-label |
| whitelabel | GET | /api/v1/whitelabel/domains | Dominios custom |

---

## 17. Paleta de Colores y Design Tokens

### 7 Brand Colors
| Token | Valor | Uso |
|-------|-------|-----|
| --ej-primary | #2563eb | Acciones principales, CTAs |
| --ej-primary-light | #60a5fa | Hover states, backgrounds suaves |
| --ej-primary-dark | #1d4ed8 | Active states, enfasis |
| --ej-secondary | #059669 | Exito, confirmaciones, badges |
| --ej-accent | #d97706 | Alertas, destacados, warnings |
| --ej-neutral-50 | #f8fafc | Fondos claros |
| --ej-neutral-900 | #0f172a | Texto principal |

### Extended UI Tokens
| Token | Uso |
|-------|-----|
| --ej-border-radius | Border radius global (8px) |
| --ej-shadow-sm | Sombra sutil para cards |
| --ej-shadow-md | Sombra media para modals |
| --ej-font-sans | Tipografia sans-serif del tenant |
| --ej-transition | Transicion default (150ms ease) |
| --ej-spacing-unit | Unidad base de espaciado (4px) |

---

## 18. Patron de Iconos SVG

### Categorias
| Directorio | Contenido |
|-----------|-----------|
| ui/ | Iconos de interfaz (menu, close, search, filter) |
| ai/ | Iconos de IA (agent, flow, brain, sparkle) |
| business/ | Iconos de negocio (invoice, subscription, tenant) |
| analytics/ | Iconos de analytics (chart, funnel, cohort, dashboard) |
| actions/ | Iconos de accion (add, edit, delete, export) |
| verticals/ | Iconos por vertical (agro, servicios, empleo) |

### Convencion
- Siempre dual: `{name}.svg` (outline 1.5px) + `{name}-duotone.svg` (filled con opacidad)
- Viewbox: `0 0 24 24`
- Colores: `currentColor` para outline, `currentColor` + opacidad 0.2 para duotone
- NUNCA emojis en templates

---

## 19. Orden de Implementacion Global y Dependencias

```
Orden:  Modulo                    Depende de
------  ----------------------    --------------------------
  1     jaraba_integrations       ecosistema_jaraba_core (existe)
  2     jaraba_agent_flows        jaraba_ai_agents (existe)
  3     jaraba_onboarding         ecosistema_jaraba_core + jaraba_journey
  4     jaraba_usage_billing      jaraba_billing (existe)
  5     jaraba_pwa                ecosistema_jaraba_core
  6     jaraba_customer_success   ecosistema_jaraba_core (existe)
  7     jaraba_tenant_knowledge   jaraba_rag (existe)
  8     jaraba_security_compliance ecosistema_jaraba_core
  9     jaraba_analytics          ecosistema_jaraba_core (existe)
 10     jaraba_whitelabel         jaraba_theming + ecosistema_jaraba_core
```

---

## 20. Estimacion de Esfuerzo

| Fase | Modulo | Archivos Nuevos | Archivos Modificados | Tipo |
|------|--------|----------------|---------------------|------|
| 1 | jaraba_integrations | ~22 | ~5 | Extension |
| 2 | jaraba_agent_flows | ~43 | 0 | Nuevo |
| 3 | jaraba_onboarding | ~42 | 0 | Nuevo |
| 4 | jaraba_usage_billing | ~41 | 0 | Nuevo |
| 5 | jaraba_pwa | ~38 | ~3 | Nuevo + Migracion |
| 6 | jaraba_customer_success | ~20 | ~5 | Extension |
| 7 | jaraba_tenant_knowledge | ~32 | ~6 | Extension |
| 8 | jaraba_security_compliance | ~42 | ~3 | Nuevo + Migracion |
| 9 | jaraba_analytics | ~35 | ~6 | Extension |
| 10 | jaraba_whitelabel | ~55 | ~3 | Nuevo + Migracion |
| **Total** | | **~370** | **~31** | |

---

## 21. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-12 | 3.0.0 | Creacion del plan v3 con 10 modulos dedicados |
