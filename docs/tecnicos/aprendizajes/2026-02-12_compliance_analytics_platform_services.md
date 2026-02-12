# Aprendizaje #61: Compliance Dashboard + Advanced Analytics + Platform Services

**Fecha:** 2026-02-12
**Contexto:** Implementacion de G115-1 Security & Compliance Dashboard, Advanced Analytics (Cohort + Funnel), Integrations Dashboard UI, Customer Success SCSS, Tenant Knowledge config schema

---

## Que se hizo

### Fase 1 — Security & Compliance Dashboard (G115-1) en ecosistema_jaraba_core

- **AuditLog Entity** inmutable: event_type, actor_id, tenant_id, target_type, target_id, ip_address, details (JSON), severity (info/warning/critical), created
- **AuditLogService**: Logging centralizado de eventos de seguridad. Auto-captura usuario actual, IP, contexto tenant. Nunca rompe flujo de aplicacion (catches exceptions)
- **AuditLogAccessControlHandler**: View requiere 'view audit logs'. Create/Update: FORBIDDEN (inmutabilidad). Delete: requiere 'administer site configuration'
- **AuditLogListBuilder**: Lista en `/admin/seguridad/audit-log` con badges de severidad color-coded
- **ComplianceDashboardController** en `/admin/seguridad`:
  - 25+ controles evaluados en tiempo real
  - 4 frameworks: SOC 2 Type II, ISO 27001:2022, ENS (RD 311/2022), GDPR/RGPD
  - Verificacion security headers
  - Ultimos 20 eventos de auditoria
  - Estadisticas agregadas
- **Frontend**: compliance-dashboard.css (BEM, responsive 768px/480px), compliance-dashboard.js (collapsible sections, filtro severidad, auto-refresh 30s), compliance-dashboard.html.twig

### Fase 2 — Advanced Analytics (Cohort + Funnel) en jaraba_analytics

- **CohortDefinition Entity**: Tipos registration_date, first_purchase, vertical, custom. Campos: name, tenant_id, cohort_type, date_range, filters (JSON)
- **FunnelDefinition Entity**: Pasos JSON configurables [{event_type, label, filters}]. conversion_window_hours (default 72h)
- **CohortAnalysisService**: `buildRetentionCurve()` semana a semana (0-12 weeks), `compareCohorts()` side-by-side, `getCohortMembers()` multi-tenant
- **FunnelTrackingService**: `calculateFunnel()` matching secuencial dentro de ventana de conversion, `getFunnelSummary()` metricas globales
- **CohortApiController**: GET /api/v1/analytics/cohorts, GET .../cohorts/{id}/retention, POST .../cohorts
- **FunnelApiController**: GET /api/v1/analytics/funnels, GET .../funnels/{id}/calculate, POST .../funnels
- **CohortDefinitionForm** + **FunnelDefinitionForm**: Gestion dinamica de pasos via AJAX
- **CohortDefinitionListBuilder** + **FunnelDefinitionListBuilder**: Badges color-coded por tipo
- **Access handlers**: Requieren 'administer jaraba analytics'
- **Frontend**: cohort-analysis.html.twig (heatmap retencion, export CSV), funnel-analysis.html.twig (barras proporcionales, drop-off indicators)
- **JavaScript**: cohort-analysis.js (carga dinamica, tooltip), funnel-analysis.js (visualizacion, animacion)
- **Unit Tests**: Controllers + Services

### Fase 3 — Platform Services

- **jaraba_integrations**: Dashboard UI con CSS, JS y SCSS (layout grid responsive, stat cards, sidebar nav, search/filters)
- **jaraba_customer_success**: Install hook + SCSS architecture (_variables.scss tokens + main.scss dashboard styles con estados healthy/warning/critical)
- **jaraba_tenant_knowledge**: Config schema YML con qdrant_collection_prefix, embedding_model (text-embedding-3-small), chunk_size (512), chunk_overlap (50), max_search_results (5), feature toggles (faq_bot, help_center)

---

## Lecciones aprendidas

### 1. Entidades de auditoria deben ser inmutables desde la capa de acceso
Similar al patron append-only de BILLING-001, las entidades de auditoria de seguridad (AuditLog) no solo deben carecer de forms de edicion: el AccessControlHandler debe retornar `AccessResult::forbidden()` explicitamente para operaciones `create` y `update`. Esto garantiza inmutabilidad incluso si se intenta via API.

### 2. Compliance multi-framework como evaluacion en tiempo real
El ComplianceDashboardController no almacena resultados de compliance: los evalua en cada request. Esto evita datos stale pero requiere que las comprobaciones sean ligeras (checks de config, queries a BD, verificacion de headers HTTP). Patron: computar en runtime, cachear con tags para invalidar al cambiar config.

### 3. Cohort analysis requiere tabla de eventos analytics
La CohortAnalysisService depende de una tabla `analytics_event` con campos (user_id, event_type, tenant_id, created). Sin esta tabla pre-poblada, las curvas de retencion estaran vacias. Asegurar que el tracking de eventos esta activo antes de crear cohortes.

### 4. Funnel tracking es session-based, no user-based
FunnelTrackingService rastrea conversion por session_id, no por user_id. Esto permite tracking de usuarios anonimos pero requiere un mecanismo de session_id consistente (cookie o similar). La ventana de conversion (conversion_window_hours) limita el tiempo maximo entre el primer y ultimo paso.

### 5. Config schema como contrato de modulo
El schema YML de jaraba_tenant_knowledge define un contrato claro para la configuracion del modulo. Esto permite que `drush config:export` valide los valores y que otros modulos lean la configuracion con confianza de tipos. Patron recomendado para todos los modulos con configuracion compleja.

---

## Reglas derivadas

### COMPLIANCE-001: Entidades de auditoria inmutables desde access handler
Las entidades que registran eventos de seguridad/auditoria DEBEN retornar `AccessResult::forbidden()` para create y update en su AccessControlHandler. Solo delete requiere permiso admin para purgado programado.

### COMPLIANCE-002: Compliance checks computados en runtime
Los dashboards de compliance DEBEN evaluar controles en cada request, no almacenar resultados. Usar cache con tags de invalidacion para optimizar performance sin sacrificar frescura.

### COMPLIANCE-003: Analytics entities requieren tabla de eventos subyacente
Las entidades de analytics (CohortDefinition, FunnelDefinition) son definiciones, no datos. Los datos provienen de tablas de eventos (analytics_event). Verificar que el tracking esta activo antes de crear definiciones.

---

## Ficheros creados/modificados

### Nuevos — ecosistema_jaraba_core (7 ficheros)
- `src/Entity/AuditLog.php`
- `src/Service/AuditLogService.php`
- `src/Controller/ComplianceDashboardController.php`
- `src/Access/AuditLogAccessControlHandler.php`
- `src/AuditLogListBuilder.php`
- `css/compliance-dashboard.css`
- `js/compliance-dashboard.js`
- `templates/compliance-dashboard.html.twig`

### Nuevos — jaraba_analytics (14+ ficheros)
- `src/Entity/CohortDefinition.php`
- `src/Entity/FunnelDefinition.php`
- `src/Service/CohortAnalysisService.php`
- `src/Service/FunnelTrackingService.php`
- `src/Controller/CohortApiController.php`
- `src/Controller/FunnelApiController.php`
- `src/Form/CohortDefinitionForm.php`
- `src/Form/FunnelDefinitionForm.php`
- `src/CohortDefinitionListBuilder.php`
- `src/FunnelDefinitionListBuilder.php`
- `src/Access/CohortDefinitionAccessControlHandler.php`
- `src/Access/FunnelDefinitionAccessControlHandler.php`
- `templates/cohort-analysis.html.twig`
- `templates/funnel-analysis.html.twig`
- `js/cohort-analysis.js`
- `js/funnel-analysis.js`
- `css/` (directorio con estilos)
- `tests/src/Unit/Controller/` (tests)

### Nuevos — jaraba_integrations (3 ficheros)
- `css/integrations-dashboard.css`
- `js/integrations-dashboard.js`
- `scss/main.scss`

### Nuevos — jaraba_customer_success (3 ficheros)
- `jaraba_customer_success.install`
- `scss/_variables.scss`
- `scss/main.scss`

### Nuevos — jaraba_tenant_knowledge (2 ficheros)
- `config/install/jaraba_tenant_knowledge.settings.yml`
- `config/schema/jaraba_tenant_knowledge.schema.yml`

### Modificados
- `ecosistema_jaraba_core.libraries.yml` — +compliance-dashboard library
- `ecosistema_jaraba_core.module` — hooks para AuditLog
- `ecosistema_jaraba_core.permissions.yml` — +view audit logs
- `ecosistema_jaraba_core.routing.yml` — +/admin/seguridad routes
- `jaraba_analytics.routing.yml` — +cohort/funnel routes
- `jaraba_analytics.services.yml` — +2 servicios analytics
- `jaraba_customer_success/css/customer-success-dashboard.css` — actualizacion
- `jaraba_integrations.libraries.yml` — +integrations-dashboard library

---

## Patrones reutilizados

| Patron | Origen | Reutilizado en |
|--------|--------|----------------|
| Append-only entity | jaraba_foc FinancialTransaction | AuditLog entity |
| AccessResult::forbidden() | BillingUsageRecord | AuditLogAccessControlHandler |
| BEM + var(--ej-*) | ecosistema_jaraba_core | compliance-dashboard.css, analytics CSS |
| REST API Controller | jaraba_billing | CohortApiController, FunnelApiController |
| Config schema YML | jaraba_rag | jaraba_tenant_knowledge |
| SCSS architecture | ecosistema_jaraba_core | customer_success, integrations |

---

## Impacto en metricas

| Metrica | Antes | Despues |
|---------|-------|---------|
| Compliance frameworks | 0 | 4 (SOC 2, ISO 27001, ENS, GDPR) |
| Controles evaluados | 0 | 25+ |
| Analytics entities | 0 | 2 (CohortDefinition, FunnelDefinition) |
| API endpoints analytics | 0 | 6 |
| Modulos con SCSS | 14 | 16 (+ customer_success, integrations) |
| Aprendizajes | 60 | 61 |
