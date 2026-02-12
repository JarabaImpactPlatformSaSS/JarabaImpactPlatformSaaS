# Plan de Implementacion: Catalogo Completo de TODOs Pendientes

> **Tipo:** Catalogo de Deuda Tecnica y Mejoras Diferidas
> **Version:** 1.0.0
> **Fecha:** 2026-02-12
> **Autor:** Equipo Tecnico JarabaImpactPlatformSaaS
> **Estado:** Consolidado
> **Alcance:** 61 modulos custom, 267 entidades, 443 servicios, 276 controladores

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Metodologia del Scan](#2-metodologia-del-scan)
3. [Metricas Globales](#3-metricas-globales)
4. [TODOs por Categoria](#4-todos-por-categoria)
   - 4.1 [DATA_INTEGRATION — Conexion con datos reales](#41-data_integration--conexion-con-datos-reales)
   - 4.2 [NOTIFICATIONS — Alertas y notificaciones](#42-notifications--alertas-y-notificaciones)
   - 4.3 [EXTERNAL_API — Integraciones externas](#43-external_api--integraciones-externas)
   - 4.4 [TENANT_FILTERING — Filtrado multi-tenant](#44-tenant_filtering--filtrado-multi-tenant)
   - 4.5 [FUTURE_PHASE — Diferidos a fases futuras](#45-future_phase--diferidos-a-fases-futuras)
   - 4.6 [UX_FRONTEND — Mejoras de interfaz](#46-ux_frontend--mejoras-de-interfaz)
   - 4.7 [CALCULATIONS — Logica de negocio y metricas](#47-calculations--logica-de-negocio-y-metricas)
   - 4.8 [INFRASTRUCTURE — Cron, colas, rendimiento](#48-infrastructure--cron-colas-rendimiento)
5. [TODOs por Modulo](#5-todos-por-modulo)
6. [Priorizacion y Roadmap](#6-priorizacion-y-roadmap)
7. [Directrices Arquitectonicas para Implementacion](#7-directrices-arquitectonicas-para-implementacion)
8. [Registro de Cambios](#8-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Estado de la Plataforma

La plataforma JarabaImpactPlatformSaaS se encuentra en **produccion (IONOS)** con:

| Metrica | Valor |
|---------|-------|
| Modulos custom | 61 |
| Content Entities | 267 |
| Services | 443 |
| Controllers | 276 |
| Unit Tests | 194 archivos / 1,466+ tests |
| Archivos JS | 203 |
| Archivos SCSS | 185 |
| Templates Twig | 462 |
| CI Status | ALL GREEN |

### 1.2 Resultados del Scan

| Tipo | Cantidad |
|------|----------|
| **TODOs accionables (PHP servicios/controladores)** | 75 |
| **TODOs accionables (JS)** | 7 |
| **TODOs accionables (Twig)** | 6 |
| **Total accionables** | **88** |
| Boilerplate Drupal entity (`@todo` en definiciones de campo) | ~1,039 |
| **Gran Total** | ~1,127 |

> Los TODOs de boilerplate son generados automaticamente por las definiciones de Content Entity de Drupal y no representan deuda tecnica real.

---

## 2. Metodologia del Scan

- **Patron**: `TODO`, `FIXME`, `@todo`, `STUB`, `HACK` (case insensitive)
- **Alcance**: `web/modules/custom/` + `web/themes/custom/`
- **Tipos de archivo**: PHP, JS, SCSS, Twig, YAML, MJML, JSON
- **Excluidos**: `vendor/`, `node_modules/`, `contrib/`, CSS compilados, archivos `.min.js`
- **Herramientas**: ripgrep via Grep tool, verificacion manual de falsos positivos
- **Fecha del scan**: 2026-02-12

---

## 3. Metricas Globales

### 3.1 Distribucion por Categoria

| Categoria | Cantidad | Porcentaje | Prioridad |
|-----------|----------|------------|-----------|
| DATA_INTEGRATION | 22 | 25.0% | Alta |
| NOTIFICATIONS | 12 | 13.6% | Media |
| EXTERNAL_API | 11 | 12.5% | Media |
| TENANT_FILTERING | 10 | 11.4% | Alta |
| FUTURE_PHASE | 12 | 13.6% | Baja |
| UX_FRONTEND | 10 | 11.4% | Media |
| CALCULATIONS | 6 | 6.8% | Media |
| INFRASTRUCTURE | 5 | 5.7% | Media |
| **Total** | **88** | **100%** | — |

### 3.2 Top 10 Modulos por TODOs

| # | Modulo | TODOs |
|---|--------|-------|
| 1 | `ecosistema_jaraba_core` | 17 |
| 2 | `jaraba_job_board` | 8 |
| 3 | `jaraba_page_builder` | 8 |
| 4 | `jaraba_candidate` | 6 |
| 5 | `jaraba_lms` | 6 |
| 6 | `jaraba_social` | 5 |
| 7 | `jaraba_rag` | 5 |
| 8 | `jaraba_billing` | 4 |
| 9 | `jaraba_foc` | 4 |
| 10 | `jaraba_tenant_knowledge` | 4 |

---

## 4. TODOs por Categoria

### 4.1 DATA_INTEGRATION — Conexion con datos reales

**22 TODOs** — Conectar stubs con entidades y queries reales.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/Controller/ApiController.php` | 157 | Obtener metricas reales del TenantMeteringService |
| 2 | `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` | 195 | Obtener lista de tenants activos |
| 3 | `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` | 273 | Obtener datos reales de revenue |
| 4 | `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` | 327 | Obtener de la base de datos |
| 5 | `ecosistema_jaraba_core/src/Service/MicroAutomationService.php` | 228 | Integrar con datos reales de analytics |
| 6 | `ecosistema_jaraba_core/src/Service/MicroAutomationService.php` | 313 | Iterar sobre tenants activos |
| 7 | `ecosistema_jaraba_core/src/Service/TenantContextService.php` | 240 | Calcular uso real mediante file_managed o directorio fisico |
| 8 | `jaraba_billing/src/Service/ExpansionRevenueService.php` | 195 | Obtener lista de tenants activos (duplicado) |
| 9 | `jaraba_billing/src/Service/ExpansionRevenueService.php` | 273 | Obtener datos reales de revenue (duplicado) |
| 10 | `jaraba_billing/src/Service/ExpansionRevenueService.php` | 327 | Obtener de la base de datos (duplicado) |
| 11 | `jaraba_candidate/src/Controller/DashboardController.php` | 198 | Check experience, education, skills |
| 12 | `jaraba_candidate/src/Controller/DashboardController.php` | 323 | Integrate with ProgressTrackingService |
| 13 | `jaraba_candidate/src/Controller/DashboardController.php` | 345 | Aggregate activities from applications, learning, profile |
| 14 | `jaraba_candidate/src/Service/CvBuilderService.php` | 372 | Load from candidate_language entity |
| 15 | `jaraba_candidate/src/Service/CandidateProfileService.php` | 94 | Implement skills retrieval from candidate_skill entity |
| 16 | `jaraba_job_board/src/Controller/JobSearchController.php` | 329 | Load from employer_profile entity |
| 17 | `jaraba_job_board/src/Service/MatchingService.php` | 353 | Integrate with candidate_profile entity |
| 18 | `jaraba_job_board/src/Service/MatchingService.php` | 362 | Integrate with candidate_profile entity |
| 19 | `jaraba_job_board/src/Service/MatchingService.php` | 371 | Integrate with candidate_profile entity |
| 20 | `jaraba_lms/src/Controller/CatalogController.php` | 244 | Load from lms_lesson table |
| 21 | `jaraba_matching/src/Service/MatchingService.php` | 566 | Anadir certifications y courses cuando esten implementados |
| 22 | `jaraba_diagnostic/src/Service/DiagnosticScoringService.php` | 266 | Implementar cuando exista la entidad digitalization_path |

### 4.2 NOTIFICATIONS — Alertas y notificaciones

**12 TODOs** — Envio de emails, alertas push, dispatch de eventos.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/Plugin/QueueWorker/UsageLimitsWorker.php` | 115 | Send email alert via mail manager |
| 2 | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | 381 | Implementar notificaciones por email |
| 3 | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | 463 | Enviar notificacion al administrador del tenant |
| 4 | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | 493 | Enviar notificacion de recordatorio |
| 5 | `jaraba_job_board/src/Service/ApplicationService.php` | 121 | Dispatch application event for ECA (notifications) |
| 6 | `jaraba_job_board/src/Service/ApplicationService.php` | 261 | Dispatch status change event for notifications |
| 7 | `jaraba_lms/src/Service/EnrollmentService.php` | 130 | Dispatch enrollment event for ECA |
| 8 | `jaraba_lms/src/Service/EnrollmentService.php` | 227 | Dispatch completion event for certificate issuance |
| 9 | `jaraba_lms/src/Service/EnrollmentService.php` | 294 | Queue reminder email |
| 10 | `jaraba_rag/src/Service/QueryAnalyticsService.php` | 196 | Notificar al admin del tenant via ECA/Brevo |
| 11 | `jaraba_foc/src/Service/AlertService.php` | 108 | Comparar MRR actual vs anterior para detectar caidas |
| 12 | `jaraba_foc/src/Controller/FocDashboardController.php` | 279 | Implementar logica de alertas reales |

### 4.3 EXTERNAL_API — Integraciones externas

**11 TODOs** — OAuth, APIs de terceros, servicios cloud.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/Service/VideoGeoService.php` | 123 | Integrar con servicio de transcripcion (Whisper API) |
| 2 | `jaraba_social/src/Service/SocialPostService.php` | 167 | Implementar clientes de API para cada plataforma |
| 3 | `jaraba_social/src/Service/SocialAccountService.php` | 149 | Implementar llamadas OAuth reales a cada plataforma |
| 4 | `jaraba_social/src/Service/MakeComIntegrationService.php` | 105 | Implementar envio HTTP real al webhook de Make.com |
| 5 | `jaraba_social/src/Service/MakeComIntegrationService.php` | 262 | Implementar verificacion real de conectividad con Make.com |
| 6 | `jaraba_funding/src/Service/Intelligence/FundingMatchingEngine.php` | 368 | Conectar con Qdrant para busqueda semantica de embeddings |
| 7 | `jaraba_matching/src/Controller/MatchingApiController.php` | 195 | Implementar con Qdrant semantic similarity en Fase 2 |
| 8 | `jaraba_matching/src/Controller/MatchingApiController.php` | 251 | Implementar con Qdrant en Fase 2 |
| 9 | `jaraba_job_board/src/Service/MatchingService.php` | 380 | Integrate with embedding service (OpenAI, Cohere, etc.) |
| 10 | `jaraba_pixels/src/Service/TokenVerificationService.php` | 166 | Hacer llamada de prueba a cada plataforma |
| 11 | `jaraba_training/src/Service/RoyaltyTracker.php` | 15 | INTEGRACION STRIPE CONNECT (Fase Futura) |

### 4.4 TENANT_FILTERING — Filtrado multi-tenant

**10 TODOs** — Segmentacion por tenant_id, Group module.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/TenantAccessControlHandler.php` | 84 | Implementar verificacion de membresia via Group module |
| 2 | `ecosistema_jaraba_core/src/Service/TenantContextService.php` | 275 | Filtrar por grupo cuando gnode este completamente configurado |
| 3 | `ecosistema_jaraba_core/src/Service/ImpactCreditService.php` | 215 | Filtrar por tenant cuando este integrado |
| 4 | `jaraba_billing/src/Service/ImpactCreditService.php` | 215 | Filtrar por tenant cuando este integrado (duplicado) |
| 5 | `jaraba_analytics/src/Controller/AnalyticsDashboardController.php` | 48 | Detectar tenant del contexto |
| 6 | `jaraba_crm/src/Controller/CrmDashboardController.php` | 52 | Obtener tenant_id del contexto actual |
| 7 | `jaraba_page_builder/src/PageContentListBuilder.php` | 93 | Filtrar por tenant del usuario actual |
| 8 | `jaraba_page_builder/src/Controller/CanvasEditorController.php` | 318 | Filtrar premium segun plan del tenant |
| 9 | `jaraba_integrations/src/Access/ConnectorInstallationAccessControlHandler.php` | 31 | Verificar tenant_id contra membresia Group del usuario |
| 10 | `jaraba_rag/src/Service/KbIndexerService.php` | 172 | Implementar reindexacion por tenant |

### 4.5 FUTURE_PHASE — Diferidos a fases futuras

**12 TODOs** — Explicitamente marcados para sprints o fases futuras.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 165 | Implementar en Sprint TK2 |
| 2 | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 179 | Implementar en Sprint TK2 |
| 3 | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 190 | Implementar en Sprint TK3 |
| 4 | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 204 | Implementar en Sprint TK4 |
| 5 | `jaraba_servicios_conecta/src/Controller/ServiceApiController.php` | 209 | Fase 2: Implementar logica completa de creacion de reserva |
| 6 | `jaraba_servicios_conecta/src/Controller/ServiceApiController.php` | 221 | Fase 2: Implementar actualizacion de estado de reserva |
| 7 | `jaraba_training/src/Entity/TrainingProduct.php` | 217 | Cambiar a 'course' y 'mentoring_package' cuando entities esten instaladas |
| 8 | `jaraba_training/src/Entity/CertificationProgram.php` | 148 | Cambiar a 'course' cuando jaraba_lms entity este instalada |
| 9 | `jaraba_training/src/Service/UpsellEngine.php` | 21 | INTEGRACION COMMERCE (Fase Futura) |
| 10 | `jaraba_training/src/Service/LadderService.php` | 91 | Integrar con sistema de compras/enrollments |
| 11 | `jaraba_pixels/src/Service/BatchProcessorService.php` | 188 | Implementar dispatch directo sin entidad en V2.1 |
| 12 | `jaraba_copilot_v2/src/Service/EntrepreneurContextService.php` | 180 | Implementar cuando exista entidad conversation_log |

### 4.6 UX_FRONTEND — Mejoras de interfaz

**10 TODOs** — Funcionalidades de UI, canvas, editores.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `jaraba_page_builder/js/canvas-editor.js` | 129 | Implementar guardado completo |
| 2 | `jaraba_page_builder/js/canvas-editor.js` | 140 | Implementar publicacion |
| 3 | `jaraba_page_builder/js/accessibility-validator.js` | 249 | Mostrar en slide-panel cuando se implemente |
| 4 | `jaraba_page_builder/templates/section-editor.html.twig` | 313 | Generar campos dinamicamente segun fields_schema del template |
| 5 | `jaraba_page_builder/templates/canvas-editor.html.twig` | 21 | Header SaaS - Temporalmente deshabilitado por conflicto de layout |
| 6 | `jaraba_page_builder/templates/canvas-editor.html.twig` | 177 | i18n-selector temporalmente removido por conflicto de estilos |
| 7 | `jaraba_page_builder/templates/blocks/pricing/pricing-table.html.twig` | 131 | Implementar tabla de comparacion |
| 8 | `jaraba_interactive/js/player.js` | 1483 | Implementar vista de revision |
| 9 | `jaraba_comercio_conecta/templates/comercio-product-detail.html.twig` | 223 | Fase 6: Widget de resenas con estrellas |
| 10 | `jaraba_comercio_conecta/templates/comercio-merchant-dashboard.html.twig` | 157 | Fase 2: Tabla de pedidos recientes |

### 4.7 CALCULATIONS — Logica de negocio y metricas

**6 TODOs** — Calculos, puntuaciones, analisis.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `jaraba_foc/src/Service/EtlService.php` | 245 | Calcular resto de metricas |
| 2 | `jaraba_foc/src/Service/MetricsCalculatorService.php` | 424 | Calcular CAC real desde transacciones de marketing |
| 3 | `jaraba_copilot_v2/src/Service/CustomerDiscoveryGamificationService.php` | 237 | Calcular week_streak basado en fechas |
| 4 | `jaraba_copilot_v2/src/Service/EntrepreneurContextService.php` | 195 | Implementar analisis de patrones con historial |
| 5 | `jaraba_rag/src/Service/JarabaRagService.php` | 167 | Obtener tokens_used del resultado LLM |
| 6 | `jaraba_social/src/Service/SocialCalendarService.php` | 214 | Implementar analisis real basado en historico de engagement |

### 4.8 INFRASTRUCTURE — Cron, colas, rendimiento

**5 TODOs** — Tests, arquitectura, infraestructura.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | 640 | Implementar entidad WebhookIntegration para procesamiento especifico |
| 2 | `ecosistema_jaraba_core/src/Service/AgentAutonomyService.php` | 364 | Re-ejecutar la accion con el executor guardado |
| 3 | `ecosistema_jaraba_core/src/Service/SandboxTenantService.php` | 264 | Crear cuenta real en Drupal |
| 4 | `ecosistema_jaraba_core/tests/src/Kernel/TenantProvisioningTest.php` | 50 | Migrar tests a Functional (BrowserTestBase) |
| 5 | `ecosistema_jaraba_core/js/pwa-register.js` | 268 | Get VAPID public key from server |

---

## 5. TODOs por Modulo

### 5.1 Tabla Completa

| # | Modulo | Accionables | Categorias principales |
|---|--------|-------------|----------------------|
| 1 | `ecosistema_jaraba_core` | 17 | DATA_INTEGRATION (7), NOTIFICATIONS (4), TENANT (2), INFRA (3), EXTERNAL_API (1) |
| 2 | `jaraba_page_builder` | 8 | UX_FRONTEND (6), TENANT (2) |
| 3 | `jaraba_job_board` | 8 | DATA_INTEGRATION (4), NOTIFICATIONS (2), EXTERNAL_API (1), UX (1) |
| 4 | `jaraba_candidate` | 6 | DATA_INTEGRATION (5), UX (1) |
| 5 | `jaraba_lms` | 6 | DATA_INTEGRATION (1), NOTIFICATIONS (3), FUTURE_PHASE (2) |
| 6 | `jaraba_social` | 5 | EXTERNAL_API (5) |
| 7 | `jaraba_rag` | 5 | CALCULATIONS (2), NOTIFICATIONS (1), TENANT (1), DATA_INTEGRATION (1) |
| 8 | `jaraba_billing` | 4 | DATA_INTEGRATION (3), TENANT (1) |
| 9 | `jaraba_foc` | 4 | CALCULATIONS (2), NOTIFICATIONS (2) |
| 10 | `jaraba_tenant_knowledge` | 4 | FUTURE_PHASE (4) |
| 11 | `jaraba_training` | 5 | FUTURE_PHASE (4), EXTERNAL_API (1) |
| 12 | `jaraba_copilot_v2` | 3 | CALCULATIONS (2), FUTURE_PHASE (1) |
| 13 | `jaraba_matching` | 3 | EXTERNAL_API (2), DATA_INTEGRATION (1) |
| 14 | `jaraba_diagnostic` | 3 | DATA_INTEGRATION (1), FUTURE_PHASE (1), INFRASTRUCTURE (1) |
| 15 | `jaraba_comercio_conecta` | 2 | UX_FRONTEND (2) |
| 16 | `jaraba_interactive` | 3 | UX_FRONTEND (1), DATA_INTEGRATION (2) |
| 17 | `jaraba_servicios_conecta` | 2 | FUTURE_PHASE (2) |
| 18 | `jaraba_integrations` | 2 | TENANT (1), INFRASTRUCTURE (1) |
| 19 | `jaraba_pixels` | 2 | EXTERNAL_API (1), FUTURE_PHASE (1) |
| 20 | `jaraba_legal_knowledge` | 1 | CALCULATIONS (1) |
| 21 | `jaraba_funding` | 1 | EXTERNAL_API (1) |
| 22 | `jaraba_analytics` | 1 | TENANT (1) |
| 23 | `jaraba_crm` | 1 | TENANT (1) |
| 24 | `jaraba_agroconecta_core` | 2 | DATA_INTEGRATION (2) |

---

## 6. Priorizacion y Roadmap

### 6.1 Sprint Inmediato (Prioridad Alta — 32 TODOs)

**Objetivo**: Conexion con datos reales y filtrado multi-tenant.

| Bloque | TODOs | Descripcion |
|--------|-------|-------------|
| Tenant Filtering | 10 | Activar filtrado por `tenant_id` en todos los controladores y list builders usando `TenantContextService::getCurrentTenantId()` |
| Data Integration — Empleabilidad | 9 | Conectar `jaraba_candidate`, `jaraba_job_board` con entidades reales (candidate_profile, employer_profile, candidate_skill) |
| Data Integration — Core | 7 | Conectar `ExpansionRevenueService`, `MicroAutomationService`, `TenantContextService` con queries BD reales |
| Data Integration — LMS | 3 | Conectar `CatalogController` con lms_lesson, implementar enrollment logic |
| Data Integration — Otros | 3 | `DiagnosticScoringService`, `MatchingService`, `AgroConecta SalesApi` |

### 6.2 Sprint Medio (Prioridad Media — 35 TODOs)

**Objetivo**: Notificaciones, APIs externas, metricas.

| Bloque | TODOs | Descripcion |
|--------|-------|-------------|
| Notificaciones email | 12 | Implementar `hook_mail()` + `MailManagerInterface::mail()` para alertas de webhook, enrollment, application status, MRR drops |
| External APIs — Social | 5 | Implementar clientes OAuth reales para Facebook, Instagram, LinkedIn, Make.com |
| External APIs — AI/Vector | 4 | Conectar Qdrant para semantic search en funding, matching, job board |
| External APIs — Otros | 2 | Whisper API transcripcion, Stripe Connect royalties |
| Calculations | 6 | CAC real, week_streak, tokens_used LLM, engagement historico, MRR comparativo, patrones entrepreneur |

### 6.3 Sprint Diferido (Prioridad Baja — 21 TODOs)

**Objetivo**: Fases futuras y mejoras de UX.

| Bloque | TODOs | Descripcion |
|--------|-------|-------------|
| Future Phase — Sprints TK | 4 | Knowledge Base Sprints TK2-TK4 |
| Future Phase — ServiciosConecta | 2 | Fase 2: reservas completas |
| Future Phase — Training | 4 | Entidades LMS, Commerce integration |
| Future Phase — Otros | 2 | BatchProcessor V2.1, conversation_log entity |
| UX Frontend | 10 | Canvas save/publish, accessibility panel, i18n selector, section editor fields, pricing table, reviews widget |
| Infrastructure | 5 | WebhookIntegration entity, agent re-execution, sandbox account creation, Kernel→Functional tests, VAPID key |

---

## 7. Directrices Arquitectonicas para Implementacion

### 7.1 SCSS — Modelo SASS con Variables Inyectables

- Compilacion con **Dart Sass moderno** (`@use` en lugar de `@import`)
- Variables inyectables configurables via **Drupal UI** (sin tocar codigo)
- Cada modulo: `scss/main.scss` como punto de entrada, parciales con `_prefijo.scss`
- Namespace de variables por modulo: `$foc-*`, `$agro-*`, `$ej-*`
- Funciones modernas: `color.adjust()` en lugar de `darken()`/`lighten()`
- BEM + CSS Custom Properties: `var(--ej-primary)`, `var(--ej-bg-body)`

### 7.2 Templates Twig — Limpias, Sin Regiones

- Templates **libres de regiones y bloques de Drupal**
- Templates parciales con `{% include %}` para elementos heredables
- Textos de interfaz siempre traducibles: `{% trans %}Texto{% endtrans %}` o `{{ 'texto'|t }}`
- Sin `|striptags|raw` (riesgo XSS)
- Zero Region page templates para frontales: `page--modulo.html.twig`

### 7.3 Frontend — Mobile-First, Modales, Sin Admin Theme

- **Mobile-first** layout con breakpoints en SCSS
- Todas las acciones crear/editar/ver abren en **modal**
- El tenant **no tiene acceso** al tema de administracion de Drupal
- Body classes via `hook_preprocess_html()` (NO `attributes.addClass()` en template)
- `Drupal.behaviors` + `once()` para JS
- Alpine.js para componentes interactivos

### 7.4 Entidades — Content Entities con Field UI

- Content entities con integracion **Field UI** y **Views**
- Navegacion en `/admin/structure` y `/admin/content`
- `EntityChangedTrait` para timestamps
- `declare(strict_types=1)` en todos los PHP
- Dependencias opcionales: `'@?service_name'` en `services.yml`

### 7.5 Ejecucion — Docker (Lando)

- Todos los comandos dentro del contenedor: `lando ssh -c "cd /app && ..."`
- URL desarrollo: `https://jaraba-saas.lndo.site/`
- Tests: `lando ssh -c "cd /app && vendor/bin/phpunit ..."`
- Cache rebuild: `lando drush cr`

---

## 8. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-12 | 1.0.0 | Scan completo inicial: 88 TODOs accionables identificados, clasificados y priorizados en 3 sprints |
