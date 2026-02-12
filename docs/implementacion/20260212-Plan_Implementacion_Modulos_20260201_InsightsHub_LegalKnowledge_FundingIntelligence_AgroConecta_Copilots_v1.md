# Plan de Implementacion: Modulos 20260201 — Insights Hub + Legal Knowledge + Funding Intelligence + AgroConecta Copilots

**Fecha:** 2026-02-12
**Autor:** Claude Opus 4.6 — Arquitecto SaaS Senior
**Version:** 1.0.0
**Especificaciones base:** 179a, 178, 178b, 179, 179b, 67, 68, Auditoria AI/BOE, Plan Mitigaciones
**Esfuerzo total estimado:** 1,500-1,900 horas (7 fases, ~36 semanas)

---

## Contexto

La auditoria de implementacion de los documentos tecnicos 20260201 revela que 3 modulos nuevos estan al 0% (Insights Hub, Legal Knowledge, Funding Intelligence), 2 copilots de AgroConecta al ~40-45%, y existen duplicidades criticas en el codebase que deben resolverse antes de avanzar. Este plan consolida todo en un roadmap coherente con fases secuenciales respetando dependencias.

---

## Tabla de Contenidos

1. [Fase 0: Consolidacion y Resolucion de Duplicidades](#fase-0)
2. [Fase 1: Insights Hub (jaraba_insights_hub)](#fase-1)
3. [Fase 2: Legal Knowledge Module (jaraba_legal_knowledge)](#fase-2)
4. [Fase 3: Funding Intelligence Module (jaraba_funding)](#fase-3)
5. [Fase 4: Producer Copilot — Completar (jaraba_agroconecta_core)](#fase-4)
6. [Fase 5: Sales Agent — Completar (jaraba_agroconecta_core + jaraba_ai_agents)](#fase-5)
7. [Fase 6: Integracion, QA y Go-Live](#fase-6)
8. [Tabla de Correspondencia Specs-Implementacion](#tabla-correspondencia)
9. [Checklist de Cumplimiento de Directrices](#checklist-directrices)
10. [Estimaciones y Dependencias](#estimaciones)

---

## Fase 0: Consolidacion y Resolucion de Duplicidades {#fase-0}

**Estimacion:** 25-35 horas | **Prioridad:** CRITICA | **Prerrequisito para todas las fases**

### 0.1 ConsentRecord — Consolidar entidad duplicada

**Problema:** `consent_record` entity_type_id existe en dos modulos con implementaciones distintas.
- `jaraba_analytics/src/Entity/ConsentRecord.php` — Version simple (4 campos boolean)
- `jaraba_pixels/src/Entity/ConsentRecord.php` — Version completa (handlers, CRUD, EntityChangedInterface, constantes)

**Solucion:**
1. Mantener la version de `jaraba_pixels` como canonica (mas completa)
2. En `jaraba_analytics`, eliminar `src/Entity/ConsentRecord.php`
3. En `jaraba_analytics/src/Service/ConsentService.php`, cambiar imports para usar `Drupal\jaraba_pixels\Entity\ConsentRecord`
4. Anadir dependencia en `jaraba_analytics.info.yml`: `- jaraba_pixels:jaraba_pixels`
5. Ejecutar `drush entity-updates` para verificar integridad de schema

**Archivos a modificar:**
- `web/modules/custom/jaraba_analytics/src/Entity/ConsentRecord.php` (ELIMINAR)
- `web/modules/custom/jaraba_analytics/src/Service/ConsentService.php` (cambiar import)
- `web/modules/custom/jaraba_analytics/jaraba_analytics.info.yml` (anadir dependencia)

### 0.2 AnalyticsService — Renombrar para evitar confusion

**Problema:** Dos clases `AnalyticsService` en modulos distintos con responsabilidades diferentes.
- `jaraba_analytics/src/Service/AnalyticsService.php` — Motor de analytics real (agregacion, reportes)
- `jaraba_page_builder/src/Service/AnalyticsService.php` — Helper de GA4/DataLayer (eventos de tracking)

**Solucion:**
1. Renombrar `jaraba_page_builder/src/Service/AnalyticsService.php` a `PageBuilderTrackingService.php`
2. Actualizar namespace y clase: `class PageBuilderTrackingService`
3. Actualizar `jaraba_page_builder.services.yml`: clase del servicio (mantener service ID `jaraba_page_builder.analytics` por BC)
4. Actualizar todas las inyecciones de tipo en controladores/servicios del page_builder

**Archivos a modificar:**
- `web/modules/custom/jaraba_page_builder/src/Service/AnalyticsService.php` (RENOMBRAR)
- `web/modules/custom/jaraba_page_builder/jaraba_page_builder.services.yml`
- Cualquier controlador que inyecte `AnalyticsService` en page_builder

### 0.3 Conversation Entity — Crear interfaz unificada

**Problema:** 3 pares de entidades conversacion/mensaje fragmentados sin base comun.

**Solucion pragmatica (sin migracion de datos):**
1. Crear interfaz `CopilotConversationInterface` en `ecosistema_jaraba_core/src/Interface/`
2. Crear trait `CopilotConversationTrait` con campos comunes (tenant_id, state, messages_count, last_activity, metadata)
3. Hacer que las 3 entidades de conversacion implementen la interfaz y usen el trait
4. Crear interfaz `CopilotMessageInterface` y trait `CopilotMessageTrait` equivalentes
5. Esto permite servicios que operan sobre cualquier tipo de conversacion sin acoplamiento

**Archivos a crear:**
- `web/modules/custom/ecosistema_jaraba_core/src/Interface/CopilotConversationInterface.php`
- `web/modules/custom/ecosistema_jaraba_core/src/Interface/CopilotMessageInterface.php`
- `web/modules/custom/ecosistema_jaraba_core/src/Trait/CopilotConversationTrait.php`
- `web/modules/custom/ecosistema_jaraba_core/src/Trait/CopilotMessageTrait.php`

**Archivos a modificar:**
- `jaraba_candidate/src/Entity/CopilotConversation.php` (implements + use trait)
- `jaraba_candidate/src/Entity/CopilotMessage.php` (implements + use trait)
- `jaraba_agroconecta_core/src/Entity/CopilotConversationAgro.php` (implements + use trait)
- `jaraba_agroconecta_core/src/Entity/CopilotMessageAgro.php` (implements + use trait)
- `jaraba_agroconecta_core/src/Entity/SalesConversationAgro.php` (implements + use trait)
- `jaraba_agroconecta_core/src/Entity/SalesMessageAgro.php` (implements + use trait)

### 0.4 SalesAgent — Crear en jaraba_ai_agents

**Problema:** ProducerCopilotAgent existe en jaraba_ai_agents pero SalesAgent no, creando inconsistencia arquitectonica.

**Solucion:**
1. Crear `SalesAgent.php` en `jaraba_ai_agents/src/Agent/` siguiendo patron de ProducerCopilotAgent
2. Extender `SmartBaseAgent` para heredar Model Routing (fast/balanced/premium)
3. Configurar agent YAML en `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.ai_agent.sales_agent.yml`
4. Registrar servicio en `jaraba_ai_agents.services.yml`
5. Refactorizar `SalesAgentService` en jaraba_agroconecta_core para delegar al nuevo agente

**Archivos a crear:**
- `web/modules/custom/jaraba_ai_agents/src/Agent/SalesAgent.php`
- `web/modules/custom/ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.ai_agent.sales_agent.yml`

**Archivos a modificar:**
- `web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.services.yml`
- `web/modules/custom/jaraba_agroconecta_core/src/Service/SalesAgentService.php`

---

## Fase 1: Insights Hub (jaraba_insights_hub) {#fase-1}

**Estimacion:** 105-140 horas | **Spec:** 179a | **Dependencias:** Fase 0, Google Cloud Project

### 1.1 Estructura del modulo

```
web/modules/custom/jaraba_insights_hub/
├── jaraba_insights_hub.info.yml
├── jaraba_insights_hub.module
├── jaraba_insights_hub.install
├── jaraba_insights_hub.routing.yml
├── jaraba_insights_hub.services.yml
├── jaraba_insights_hub.permissions.yml
├── jaraba_insights_hub.links.menu.yml
├── jaraba_insights_hub.links.task.yml
├── jaraba_insights_hub.links.action.yml
├── jaraba_insights_hub.libraries.yml
├── config/
│   ├── install/jaraba_insights_hub.settings.yml
│   └── schema/jaraba_insights_hub.schema.yml
├── src/
│   ├── Entity/
│   │   ├── SearchConsoleConnection.php
│   │   ├── SearchConsoleData.php
│   │   ├── WebVitalsMetric.php
│   │   ├── InsightsErrorLog.php
│   │   ├── UptimeCheck.php
│   │   └── UptimeIncident.php
│   ├── Service/
│   │   ├── SearchConsoleService.php
│   │   ├── WebVitalsCollectorService.php
│   │   ├── WebVitalsAggregatorService.php
│   │   ├── ErrorTrackingService.php
│   │   ├── UptimeMonitorService.php
│   │   └── InsightsAggregatorService.php
│   ├── Controller/
│   │   ├── InsightsDashboardController.php
│   │   ├── InsightsAdminController.php
│   │   ├── SearchConsoleApiController.php
│   │   ├── WebVitalsApiController.php
│   │   ├── ErrorTrackingApiController.php
│   │   └── UptimeApiController.php
│   ├── Form/
│   │   ├── InsightsSettingsForm.php
│   │   └── SearchConsoleConnectForm.php
│   ├── Access/
│   │   └── InsightsAccessControlHandler.php
│   └── ListBuilder/
│       ├── InsightsErrorLogListBuilder.php
│       ├── UptimeCheckListBuilder.php
│       └── UptimeIncidentListBuilder.php
├── js/
│   ├── web-vitals-tracker.js
│   ├── error-tracker.js
│   └── insights-dashboard.js
├── scss/
│   ├── _variables.scss
│   ├── _insights-dashboard.scss
│   └── main.scss
├── css/
│   └── jaraba-insights-hub.css
├── templates/
│   ├── page--insights.html.twig
│   └── partials/
│       ├── _insights-header.html.twig
│       ├── _insights-seo-panel.html.twig
│       ├── _insights-performance-panel.html.twig
│       ├── _insights-errors-panel.html.twig
│       └── _insights-uptime-panel.html.twig
└── tests/
    └── src/Unit/
        ├── WebVitalsAggregatorServiceTest.php
        ├── ErrorTrackingServiceTest.php
        └── UptimeMonitorServiceTest.php
```

### 1.2 Entidades clave

| Entidad | entity_type_id | Tabla | Campos clave |
|---------|---------------|-------|-------------|
| SearchConsoleConnection | `search_console_connection` | search_console_connection | tenant_id, site_url, access_token (encrypted), refresh_token (encrypted), status, last_sync_at |
| SearchConsoleData | `search_console_data` | search_console_data | tenant_id, date, query, page, clicks, impressions, ctr, position |
| WebVitalsMetric | `web_vitals_metric` | web_vitals_metric | tenant_id, page_url, metric_name (LCP/INP/CLS/FCP/TTFB), metric_value, metric_rating, device_type |
| InsightsErrorLog | `insights_error_log` | insights_error_log | tenant_id, error_hash (dedup), error_type (js/php), severity, message, stack_trace, occurrences, status |
| UptimeCheck | `uptime_check` | uptime_check | tenant_id, endpoint, status (up/down/degraded), response_time_ms, checked_at |
| UptimeIncident | `uptime_incident` | uptime_incident | tenant_id, endpoint, status (ongoing/resolved), started_at, resolved_at, failed_checks, alert_sent |

### 1.3 Servicios y dependencias

```yaml
services:
  jaraba_insights_hub.search_console:
    class: Drupal\jaraba_insights_hub\Service\SearchConsoleService
    arguments: ['@http_client', '@config.factory', '@entity_type.manager', '@logger.channel.jaraba_insights_hub']

  jaraba_insights_hub.web_vitals_collector:
    class: Drupal\jaraba_insights_hub\Service\WebVitalsCollectorService
    arguments: ['@entity_type.manager', '@ecosistema_jaraba_core.tenant_context', '@logger.channel.jaraba_insights_hub']

  jaraba_insights_hub.web_vitals_aggregator:
    class: Drupal\jaraba_insights_hub\Service\WebVitalsAggregatorService
    arguments: ['@entity_type.manager', '@database']

  jaraba_insights_hub.error_tracking:
    class: Drupal\jaraba_insights_hub\Service\ErrorTrackingService
    arguments: ['@entity_type.manager', '@ecosistema_jaraba_core.tenant_context', '@logger.channel.jaraba_insights_hub']

  jaraba_insights_hub.uptime_monitor:
    class: Drupal\jaraba_insights_hub\Service\UptimeMonitorService
    arguments: ['@http_client', '@entity_type.manager', '@state', '@logger.channel.jaraba_insights_hub']

  jaraba_insights_hub.aggregator:
    class: Drupal\jaraba_insights_hub\Service\InsightsAggregatorService
    arguments:
      - '@jaraba_insights_hub.search_console'
      - '@jaraba_insights_hub.web_vitals_aggregator'
      - '@jaraba_insights_hub.error_tracking'
      - '@jaraba_insights_hub.uptime_monitor'
      - '@jaraba_analytics.analytics_service'
      - '@jaraba_site_builder.seo_manager'
```

### 1.4 Rutas principales

| Tipo | Ruta | Controlador | Admin? |
|------|------|-------------|--------|
| Frontend dashboard | `/insights` | InsightsDashboardController::dashboard | NO |
| Admin dashboard | `/admin/content/insights` | InsightsAdminController::overview | SI |
| Settings | `/admin/config/services/insights-hub` | InsightsSettingsForm | SI |
| API: Web Vitals ingest | `POST /api/v1/insights/web-vitals` | WebVitalsApiController::collect | NO |
| API: Error ingest | `POST /api/v1/insights/errors` | ErrorTrackingApiController::collect | NO |
| API: Dashboard data | `GET /api/v1/insights/summary` | InsightsDashboardController::apiSummary | SI |

### 1.5 Frontend (Zero-Region)

**Pagina:** `templates/page--insights.html.twig`
- Incluye `_header.html.twig` del tema
- 4 tabs: SEO | Performance | Errors | Uptime
- Cada tab es un partial
- Selector de rango de fechas (7d, 30d, 90d)
- Metricas con KPI cards usando CSS custom properties

### 1.6 Hooks (NO ECA)

```php
function jaraba_insights_hub_cron(): void {
  _jaraba_insights_hub_uptime_check(\Drupal::time()->getRequestTime());
  _jaraba_insights_hub_search_console_sync(\Drupal::time()->getRequestTime());
  _jaraba_insights_hub_web_vitals_aggregate(\Drupal::time()->getRequestTime());
}

function jaraba_insights_hub_mail(string $key, array &$message, array $params): void {
  // Alertas de uptime, errores criticos, CWV degradados
}
```

---

## Fase 2: Legal Knowledge Module (jaraba_legal_knowledge) {#fase-2}

**Estimacion:** 400-520 horas | **Specs:** 178, 178b | **Dependencias:** Fase 0, Qdrant operativo

### 2.1 Estructura del modulo

```
web/modules/custom/jaraba_legal_knowledge/
├── jaraba_legal_knowledge.info.yml
├── jaraba_legal_knowledge.module
├── jaraba_legal_knowledge.install
├── jaraba_legal_knowledge.routing.yml
├── jaraba_legal_knowledge.services.yml
├── jaraba_legal_knowledge.permissions.yml
├── jaraba_legal_knowledge.links.menu.yml
├── jaraba_legal_knowledge.links.task.yml
├── jaraba_legal_knowledge.links.action.yml
├── jaraba_legal_knowledge.libraries.yml
├── config/
│   ├── install/jaraba_legal_knowledge.settings.yml
│   └── schema/jaraba_legal_knowledge.schema.yml
├── src/
│   ├── Entity/
│   │   ├── LegalNorm.php
│   │   ├── LegalChunk.php
│   │   ├── LegalQueryLog.php
│   │   └── NormChangeAlert.php
│   ├── Service/
│   │   ├── BoeApiClient.php
│   │   ├── LegalIngestionService.php
│   │   ├── LegalChunkingService.php
│   │   ├── LegalEmbeddingService.php
│   │   ├── LegalRagService.php
│   │   ├── LegalQueryService.php
│   │   ├── LegalCitationService.php
│   │   ├── LegalDisclaimerService.php
│   │   ├── LegalAlertService.php
│   │   └── TaxCalculatorService.php
│   ├── Controller/
│   │   ├── LegalQueryController.php
│   │   ├── LegalAdminController.php
│   │   └── TaxCalculatorController.php
│   ├── Form/
│   │   ├── LegalSettingsForm.php
│   │   └── LegalSyncForm.php
│   ├── Plugin/QueueWorker/
│   │   ├── LegalNormIngestionWorker.php
│   │   └── LegalAlertNotificationWorker.php
│   └── Access/
│       └── LegalNormAccessControlHandler.php
├── js/
│   └── legal-dashboard.js
├── scss/
│   ├── _variables.scss
│   ├── _legal-dashboard.scss
│   ├── _tax-calculator.scss
│   └── main.scss
├── css/
│   └── jaraba-legal-knowledge.css
├── templates/
│   ├── page--legal.html.twig
│   ├── legal-query-response.html.twig
│   ├── legal-citation.html.twig
│   └── partials/
│       ├── _legal-search.html.twig
│       ├── _legal-results.html.twig
│       ├── _legal-alerts-panel.html.twig
│       └── _tax-calculator.html.twig
└── tests/
    └── src/Unit/
        ├── BoeApiClientTest.php
        ├── LegalChunkingServiceTest.php
        └── TaxCalculatorServiceTest.php
```

### 2.2 Integracion con modulos existentes

**CRITICO:** El modulo `jaraba_copilot_v2` ya tiene `NormativeKnowledgeService` y `NormativeRAGService`.

**Estrategia:** Legal Knowledge es el PROVEEDOR de datos normativos. Los copilots CONSUMEN via servicio.
- `jaraba_legal_knowledge` posee las entidades y el pipeline de ingestion BOE
- `jaraba_legal_knowledge.rag_service` expone metodo `query(string $question, array $filters): LegalResponse`
- `jaraba_copilot_v2.NormativeKnowledgeService` se refactoriza para inyectar `jaraba_legal_knowledge.rag_service`
- Qdrant client reutilizado: `jaraba_rag.qdrant_client` (QdrantDirectClient existente)

### 2.3 Rutas principales

| Tipo | Ruta | Controlador | Admin? |
|------|------|-------------|--------|
| Frontend consultas | `/legal` | LegalQueryController::queryPage | NO |
| Frontend calculadoras | `/legal/calculadoras` | TaxCalculatorController::page | NO |
| Admin normas | `/admin/content/legal-norms` | EntityListBuilder | SI |
| Admin alertas | `/admin/content/legal-alerts` | EntityListBuilder | SI |
| Settings | `/admin/config/services/legal-knowledge` | LegalSettingsForm | SI |
| API: Consulta | `POST /api/v1/legal/query` | LegalQueryController::apiQuery | SI |
| API: IRPF | `POST /api/v1/legal/calculators/irpf` | TaxCalculatorController::irpf | SI |

---

## Fase 3: Funding Intelligence Module (jaraba_funding) {#fase-3}

**Estimacion:** 520-680 horas + 42h BD | **Specs:** 179, 179b | **Dependencias:** Fase 2 (Legal Knowledge)

### 3.1 Estructura del modulo

```
web/modules/custom/jaraba_funding/
├── jaraba_funding.info.yml
├── jaraba_funding.module
├── jaraba_funding.install
├── jaraba_funding.routing.yml
├── jaraba_funding.services.yml
├── jaraba_funding.permissions.yml
├── jaraba_funding.links.menu.yml
├── jaraba_funding.links.task.yml
├── jaraba_funding.links.action.yml
├── jaraba_funding.libraries.yml
├── config/
│   ├── install/
│   │   ├── jaraba_funding.settings.yml
│   │   └── jaraba_funding.sources.yml
│   └── schema/jaraba_funding.schema.yml
├── src/
│   ├── Entity/
│   │   ├── FundingCall.php
│   │   ├── FundingSubscription.php
│   │   ├── FundingMatch.php
│   │   └── FundingAlert.php
│   ├── Service/
│   │   ├── Api/
│   │   │   ├── BdnsApiClient.php
│   │   │   └── BojaApiClient.php
│   │   ├── Ingestion/
│   │   │   ├── FundingIngestionService.php
│   │   │   └── FundingNormalizerService.php
│   │   ├── Intelligence/
│   │   │   ├── FundingMatchingEngine.php
│   │   │   ├── FundingEligibilityCalculator.php
│   │   │   └── FundingCopilotService.php
│   │   ├── Alerts/
│   │   │   ├── FundingAlertService.php
│   │   │   └── FundingNotificationDispatcher.php
│   │   └── FundingCacheService.php
│   ├── Controller/
│   │   ├── FundingDashboardController.php
│   │   └── FundingCopilotController.php
│   ├── Plugin/QueueWorker/
│   │   ├── FundingIngestionWorker.php
│   │   └── FundingAlertWorker.php
│   └── Access/
│       └── FundingCallAccessControlHandler.php
├── js/
│   ├── funding-dashboard.js
│   ├── funding-calendar.js
│   └── funding-copilot.js
├── scss/
│   ├── _variables.scss
│   ├── _funding-dashboard.scss
│   ├── _funding-calendar.scss
│   └── main.scss
├── css/
│   └── jaraba-funding.css
├── templates/
│   ├── page--funding.html.twig
│   └── partials/
│       ├── _funding-search.html.twig
│       ├── _funding-match-card.html.twig
│       ├── _funding-calendar.html.twig
│       └── _funding-copilot-widget.html.twig
└── tests/
    └── src/Unit/
        ├── BdnsApiClientTest.php
        ├── BojaApiClientTest.php
        └── MatchingEngineTest.php
```

### 3.2 Motor de Matching IA

Algoritmo de scoring (0-100) con 5 criterios ponderados:

| Criterio | Peso | Logica |
|----------|------|--------|
| Region | 20% | 100 si nacional o match exacto, 0 si no aplica |
| Tipo Beneficiario | 25% | 100 match directo, 90 por inclusion, 20 bajo match |
| Sector | 20% | 70 sin restriccion, 60-100 por ratio de interseccion |
| Tamano | 15% | Score por empleados y facturacion vs requisitos |
| Semantico | 20% | Similaridad vectorial via Qdrant |

### 3.3 Rutas principales

| Tipo | Ruta | Controlador | Admin? |
|------|------|-------------|--------|
| Frontend dashboard | `/funding` | FundingDashboardController::dashboard | NO |
| Frontend copilot | `/funding/copilot` | FundingCopilotController::chat | NO |
| Admin convocatorias | `/admin/content/funding-calls` | EntityListBuilder | SI |
| Settings | `/admin/config/services/funding` | FundingSettingsForm | SI |
| API: Search | `GET /api/v1/funding/calls` | FundingDashboardController::apiSearch | SI |
| API: Matches | `GET /api/v1/funding/matches` | FundingDashboardController::apiMatches | SI |
| API: Copilot | `POST /api/v1/funding/copilot` | FundingCopilotController::apiChat | SI |

---

## Fase 4: Producer Copilot — Completar {#fase-4}

**Estimacion:** 150-200 horas | **Spec:** 67 | **Dependencias:** Fase 0.3 (interfaces), Qdrant

### 4.1 Componentes a completar

| Componente | Estado | Accion |
|-----------|--------|--------|
| ProducerCopilotService | Existe | Extender con Qdrant RAG pipeline |
| CopilotApiController | Existe (6 endpoints) | Anadir endpoint forecast/demand |
| CopilotGeneratedContentAgro | **NO EXISTE** | **CREAR** |
| DemandForecasterService | **NO EXISTE** | **CREAR** |
| Market Spy Integration | **NO EXISTE** | **CREAR** |
| ECA Workflows (4) | **NO EXISTE** | **CREAR** como hooks en .module |
| Tests | **NO EXISTE** | **CREAR** |

### 4.2 Entidad nueva: CopilotGeneratedContentAgro

```php
// entity_type_id: copilot_generated_content_agro
// Campos: message_id (FK), content_type, target_entity_type, target_entity_id,
//         content (TEXT), status (draft|published|rejected)
```

---

## Fase 5: Sales Agent — Completar {#fase-5}

**Estimacion:** 180-250 horas | **Spec:** 68 | **Dependencias:** Fase 0.4 (SalesAgent en ai_agents)

### 5.1 Componentes a completar

| Componente | Estado | Accion |
|-----------|--------|--------|
| SalesAgentService | Existe (TODOs) | Completar integracion |
| SalesAgent (jaraba_ai_agents) | **NO EXISTE** | **CREAR** (Fase 0.4) |
| CrossSellEngine | **NO EXISTE** | **CREAR** |
| CartRecoveryService | **NO EXISTE** | **CREAR** |
| WhatsApp Integration | **NO EXISTE** | **CREAR** |
| ECA Workflows (4) | **NO EXISTE** | **CREAR** como hooks |
| Tests | **NO EXISTE** | **CREAR** |

### 5.2 WhatsApp Business Integration

```
src/Service/WhatsApp/
├── WhatsAppClientService.php
├── WhatsAppWebhookHandler.php
└── WhatsAppTemplateService.php
```

---

## Fase 6: Integracion, QA y Go-Live {#fase-6}

**Estimacion:** 80-120 horas | **Dependencias:** Todas las fases anteriores

### 6.1 Integracion cross-module
- Insights Hub consume datos de jaraba_analytics y jaraba_site_builder
- Legal Knowledge alimenta Funding Intelligence y copilot_v2
- Funding Intelligence se conecta con Journey triggers
- Copilots AgroConecta usan RAG compartido via jaraba_rag.qdrant_client

### 6.2 Tests
- Unit tests por servicio (PHPUnit)
- Kernel tests para entidades y servicios con BD
- Tests de API endpoints (HTTP tests)
- Tests de integracion Qdrant (mock)

### 6.3 SCSS Compilation
```bash
cd web/modules/custom/jaraba_insights_hub && npx sass scss/main.scss:css/jaraba-insights-hub.css --style=compressed
cd web/modules/custom/jaraba_legal_knowledge && npx sass scss/main.scss:css/jaraba-legal-knowledge.css --style=compressed
cd web/modules/custom/jaraba_funding && npx sass scss/main.scss:css/jaraba-funding.css --style=compressed
```

---

## Tabla de Correspondencia Specs-Implementacion {#tabla-correspondencia}

| Spec | Seccion | Componente | Modulo Drupal | Archivo(s) | Estado |
|------|---------|-----------|--------------|-----------|--------|
| 179a §3 | Search Console | OAuth2 + API sync | jaraba_insights_hub | SearchConsoleService.php | Pendiente |
| 179a §4 | Core Web Vitals | RUM tracker + agregacion | jaraba_insights_hub | WebVitalsCollectorService.php | Pendiente |
| 179a §5 | Error Tracking | JS + PHP handlers | jaraba_insights_hub | ErrorTrackingService.php | Pendiente |
| 179a §6 | Uptime Monitor | Cron + health endpoints | jaraba_insights_hub | UptimeMonitorService.php | Pendiente |
| 178 §3 | Modelo datos legal | 4 entidades | jaraba_legal_knowledge | LegalNorm.php, LegalChunk.php | Pendiente |
| 178 §4 | API BOE | Cliente + ingestion | jaraba_legal_knowledge | BoeApiClient.php | Pendiente |
| 178 §5 | RAG Pipeline | Query → Qdrant → Claude → citas | jaraba_legal_knowledge | LegalRagService.php | Pendiente |
| 179 §3 | Entidades funding | 4 entidades | jaraba_funding | FundingCall.php, FundingMatch.php | Pendiente |
| 179 §4 | API Clients | BDNS + BOJA | jaraba_funding | BdnsApiClient.php, BojaApiClient.php | Pendiente |
| 179 §5 | Matching IA | Scoring 5 criterios | jaraba_funding | FundingMatchingEngine.php | Pendiente |
| 67 §3.3 | Generated content | Entidad auditable | jaraba_agroconecta_core | CopilotGeneratedContentAgro.php | Pendiente |
| 67 §4.4 | Demand Forecaster | Prediccion demanda | jaraba_agroconecta_core | DemandForecasterService.php | Pendiente |
| 68 §4.3 | Cross-Sell | Motor venta cruzada | jaraba_agroconecta_core | CrossSellEngine.php | Pendiente |
| 68 §4.4 | Cart Recovery | Secuencia recuperacion | jaraba_agroconecta_core | CartRecoveryService.php | Pendiente |
| 68 §5 | WhatsApp | Business API integration | jaraba_agroconecta_core | WhatsAppClientService.php | Pendiente |

---

## Checklist de Cumplimiento de Directrices {#checklist-directrices}

| # | Directriz | Cumplimiento |
|---|----------|-------------|
| 1 | Entidades con 4 YAML files | Cada modulo tiene .routing.yml, .links.menu.yml, .links.task.yml, .links.action.yml |
| 2 | Content entities en /admin/content | Todas las entidades bajo /admin/content/* |
| 3 | Config entities en /admin/structure | Settings forms bajo /admin/structure/* |
| 4 | Frontend zero-region | page--insights.html.twig, page--legal.html.twig, page--funding.html.twig |
| 5 | hook_preprocess_html() para body classes | Clases page-insights, page-legal, page-funding |
| 6 | SCSS con var(--ej-*, fallback) | CSS custom properties, NO SCSS vars para colores |
| 7 | Dart Sass @use | Cada partial declara sus propios @use imports |
| 8 | Compilar desde WSL | npx sass desde WSL |
| 9 | Hooks NO ECA | hook_cron(), hook_entity_insert(), hook_mail() |
| 10 | Textos traducibles | t(), {% trans %}, Drupal.t() |
| 11 | Paleta 7 colores | Todas las UI usan --ej-color-* |
| 12 | Modales slide-panel | data-slide-panel para crear/editar |
| 13 | QueueWorker pattern | ContainerFactoryPluginInterface |
| 14 | Multi-tenant | tenant_id en todas las entidades |
| 15 | Permisos own/any | Patron propio/cualquiera |
| 16 | API /api/v1/ | Todos los endpoints bajo /api/v1/module/ |
| 17 | BEM CSS | ej-module__element--modifier |

---

## Estimaciones y Dependencias {#estimaciones}

### Cronograma

| Fase | Horas | Semanas | Dependencias |
|------|-------|---------|-------------|
| **Fase 0**: Consolidacion | 25-35h | 1-2 | Ninguna |
| **Fase 1**: Insights Hub | 105-140h | 5-7 | Fase 0 |
| **Fase 2**: Legal Knowledge | 400-520h | 10-13 | Fase 0, Qdrant |
| **Fase 3**: Funding Intelligence | 562-722h | 14-18 | Fase 2 |
| **Fase 4**: Producer Copilot | 150-200h | 4-5 | Fase 0, Qdrant |
| **Fase 5**: Sales Agent | 180-250h | 5-6 | Fase 0, Fase 4 |
| **Fase 6**: Integracion/QA | 80-120h | 2-3 | Todas |
| **TOTAL** | **1,502-1,987h** | **~36 sem** | |

### Grafo de dependencias

```
Fase 0 (Consolidacion)
  ├── Fase 1 (Insights Hub)          [parallelizable con Fase 2]
  ├── Fase 2 (Legal Knowledge)       [parallelizable con Fase 1]
  │   └── Fase 3 (Funding Intelligence) [secuencial]
  ├── Fase 4 (Producer Copilot)      [parallelizable con Fases 1-3]
  │   └── Fase 5 (Sales Agent)       [secuencial]
  └── Fase 6 (Integracion/QA)       [final]
```

### Ruta critica
Fase 0 → Fase 2 → Fase 3 → Fase 6 = **1,067-1,397 horas** (~28 semanas)
