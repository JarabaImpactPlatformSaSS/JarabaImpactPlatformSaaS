# Plan de Implementacion: Catalogo Completo de TODOs Pendientes

> **Tipo:** Catalogo de Deuda Tecnica y Mejoras Diferidas
> **Version:** 1.2.0
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
| **TODOs accionables (PHP servicios/controladores/entities)** | 103 |
| **TODOs accionables (JS)** | 7 |
| **TODOs accionables (Twig)** | 6 |
| **Total bruto** | **116** |
| Duplicados cross-module (billing/core) | -4 |
| **Total unicos accionables** | **112** |
| Boilerplate Drupal entity (no accionable) | 0 |

> **Nota**: No se encontraron boilerplate `@todo` en definiciones de entidad. Todas las Content Entities tienen sus campos y handlers completamente implementados. Se encontraron 4 duplicados entre `jaraba_billing` y `ecosistema_jaraba_core` (ImpactCreditService y ExpansionRevenueService existentes en ambos modulos).

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
| DATA_INTEGRATION | 39 | 33.6% | Alta |
| NOTIFICATIONS | 13 | 11.2% | Media |
| EXTERNAL_API | 11 | 9.5% | Media |
| TENANT_FILTERING | 13 | 11.2% | Alta |
| FUTURE_PHASE | 15 | 12.9% | Baja |
| UX_FRONTEND | 8 | 6.9% | Media |
| CALCULATIONS | 13 | 11.2% | Media |
| INFRASTRUCTURE | 4 | 3.4% | Media |
| **Total (bruto)** | **116** | **100%** | — |
| **Total unico (sin duplicados)** | **112** | — | — |

### 3.2 Top 10 Modulos por TODOs

| # | Modulo | TODOs |
|---|--------|-------|
| 1 | `ecosistema_jaraba_core` | 20 |
| 2 | `jaraba_job_board` | 12 |
| 3 | `jaraba_page_builder` | 10 |
| 4 | `jaraba_foc` | 7 |
| 5 | `jaraba_candidate` | 6 |
| 6 | `jaraba_lms` | 6 |
| 7 | `jaraba_social` | 5 |
| 8 | `jaraba_rag` | 5 |
| 9 | `jaraba_training` | 5 |
| 10 | `jaraba_billing` | 4 |

---

## 4. TODOs por Categoria

### 4.1 DATA_INTEGRATION — Conexion con datos reales

**39 TODOs** — Conectar stubs con entidades y queries reales.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/Controller/ApiController.php` | 157 | Obtener metricas reales del TenantMeteringService |
| 2 | `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` | 195 | Obtener lista de tenants activos |
| 3 | `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` | 273 | Obtener datos reales de revenue |
| 4 | `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` | 327 | Obtener de la base de datos |
| 5 | `ecosistema_jaraba_core/src/Service/MicroAutomationService.php` | 228 | Integrar con datos reales de analytics |
| 6 | `ecosistema_jaraba_core/src/Service/SandboxTenantService.php` | 264 | Crear cuenta real en Drupal |
| 7 | `jaraba_billing/src/Service/ExpansionRevenueService.php` | 195 | Obtener lista de tenants activos (duplicado core) |
| 8 | `jaraba_billing/src/Service/ExpansionRevenueService.php` | 273 | Obtener datos reales de revenue (duplicado core) |
| 9 | `jaraba_billing/src/Service/ExpansionRevenueService.php` | 327 | Obtener de la base de datos (duplicado core) |
| 10 | `jaraba_candidate/src/Controller/DashboardController.php` | 198 | Check experience, education, skills |
| 11 | `jaraba_candidate/src/Controller/DashboardController.php` | 323 | Integrate with ProgressTrackingService |
| 12 | `jaraba_candidate/src/Controller/DashboardController.php` | 336 | Implement profile view tracking |
| 13 | `jaraba_candidate/src/Controller/DashboardController.php` | 345 | Aggregate activities from applications, learning, profile |
| 14 | `jaraba_candidate/src/Service/CvBuilderService.php` | 372 | Load from candidate_language entity |
| 15 | `jaraba_candidate/src/Service/CandidateProfileService.php` | 94 | Implement skills retrieval from candidate_skill entity |
| 16 | `jaraba_job_board/src/Controller/JobSearchController.php` | 329 | Load from employer_profile entity |
| 17 | `jaraba_job_board/src/Controller/JobBoardApiController.php` | 141 | Implement employer applications retrieval |
| 18 | `jaraba_job_board/src/Service/MatchingService.php` | 353 | Integrate with candidate_profile entity |
| 19 | `jaraba_job_board/src/Service/MatchingService.php` | 362 | Integrate with candidate_profile entity |
| 20 | `jaraba_job_board/src/Service/MatchingService.php` | 371 | Integrate with candidate_profile entity |
| 21 | `jaraba_job_board/js/agent-fab.js` | 365 | Send rating to backend |
| 22 | `jaraba_lms/src/Controller/CatalogController.php` | 244 | Load from lms_lesson table |
| 23 | `jaraba_lms/src/Controller/LmsApiController.php` | 64 | Implement enrollment logic |
| 24 | `jaraba_matching/src/Service/MatchingService.php` | 566 | Anadir certifications y courses cuando esten implementados |
| 25 | `jaraba_diagnostic/src/Controller/DiagnosticApiController.php` | 236 | Implementar cuando se guarden scores por seccion |
| 26 | `jaraba_diagnostic/src/Service/DiagnosticScoringService.php` | 266 | Implementar cuando exista la entidad digitalization_path |
| 27 | `jaraba_copilot_v2/src/Service/EntrepreneurContextService.php` | 180 | Implementar cuando exista entidad conversation_log |
| 28 | `jaraba_agroconecta_core/src/Controller/SalesApiController.php` | 165 | Integrar con CartService real |
| 29 | `jaraba_agroconecta_core/src/Controller/SalesApiController.php` | 187 | Integrar con CouponService real |
| 30 | `jaraba_training/src/Entity/TrainingProduct.php` | 217 | Cambiar a 'course' y 'mentoring_package' cuando entities esten instaladas |
| 31 | `jaraba_training/src/Entity/CertificationProgram.php` | 148 | Cambiar a 'course' cuando jaraba_lms entity este instalada |
| 32 | `jaraba_training/src/Service/LadderService.php` | 91 | Integrar con sistema de compras/enrollments |
| 33 | `jaraba_foc/src/Controller/StripeWebhookController.php` | 263 | Actualizar entidad de vendedor en Drupal |
| 34 | `jaraba_foc/src/Controller/StripeWebhookController.php` | 329 | Mapear transaction_type a termino de taxonomia |
| 35 | `jaraba_interactive/src/Controller/ApiController.php` | 103 | Store xAPI statement in InteractiveResult entity |
| 36 | `jaraba_page_builder/src/Controller/TemplatePickerController.php` | 195 | Integrar analytics real (actualmente usa rand()) |
| 37 | `jaraba_rag/src/Service/JarabaRagService.php` | 167 | Obtener tokens_used del resultado LLM |
| 38 | `jaraba_mentoring/src/Service/MentorMatchingService.php` | 205 | Check actual availability slots |
| 39 | `jaraba_business_tools/js/entrepreneur-agent-fab.js` | 413 | Send rating to backend for AI learning |

### 4.2 NOTIFICATIONS — Alertas y notificaciones

**13 TODOs** — Envio de emails, alertas push, dispatch de eventos.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/Plugin/QueueWorker/UsageLimitsWorker.php` | 115 | Send email alert via mail manager |
| 2 | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | 381 | Implementar notificaciones por email |
| 3 | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | 463 | Enviar notificacion al administrador del tenant |
| 4 | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | 493 | Enviar notificacion de recordatorio |
| 5 | `ecosistema_jaraba_core/js/pwa-register.js` | 268 | Get VAPID public key from server |
| 6 | `jaraba_job_board/src/Service/ApplicationService.php` | 121 | Dispatch application event for ECA (notifications) |
| 7 | `jaraba_job_board/src/Service/ApplicationService.php` | 261 | Dispatch status change event for notifications |
| 8 | `jaraba_job_board/src/Service/JobAlertService.php` | 66 | Implement alert processing |
| 9 | `jaraba_lms/src/Service/EnrollmentService.php` | 130 | Dispatch enrollment event for ECA |
| 10 | `jaraba_lms/src/Service/EnrollmentService.php` | 227 | Dispatch completion event for certificate issuance |
| 11 | `jaraba_lms/src/Service/EnrollmentService.php` | 294 | Queue reminder email |
| 12 | `jaraba_rag/src/Service/QueryAnalyticsService.php` | 196 | Notificar al admin del tenant via ECA/Brevo |
| 13 | `jaraba_foc/src/Controller/FocDashboardController.php` | 279 | Implementar logica de alertas reales |

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
| 7 | `jaraba_job_board/src/Service/MatchingService.php` | 380 | Integrate with embedding service (OpenAI, Cohere, etc.) |
| 8 | `jaraba_diagnostic/src/Service/DiagnosticReportService.php` | 77 | Implementar con DomPDF o Puppeteer cuando este disponible |
| 9 | `jaraba_rag/src/Service/GroundingValidator.php` | 315 | Implementar llamada LLM cuando se necesite mayor precision |
| 10 | `jaraba_rag/src/Service/GroundingValidator.php` | 341 | Llamar al LLM y parsear respuesta |
| 11 | `jaraba_business_tools/src/Controller/CanvasApiController.php` | 751 | Implement PDF export with Puppeteer |

### 4.4 TENANT_FILTERING — Filtrado multi-tenant

**13 TODOs** — Segmentacion por tenant_id, Group module.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/TenantAccessControlHandler.php` | 84 | Implementar verificacion de membresia via Group module |
| 2 | `ecosistema_jaraba_core/src/Service/TenantContextService.php` | 275 | Filtrar por grupo cuando gnode este completamente configurado |
| 3 | `ecosistema_jaraba_core/src/Service/ImpactCreditService.php` | 215 | Filtrar por tenant cuando este integrado |
| 4 | `ecosistema_jaraba_core/src/Service/MicroAutomationService.php` | 313 | Iterar sobre tenants activos |
| 5 | `jaraba_billing/src/Service/ImpactCreditService.php` | 215 | Filtrar por tenant cuando este integrado (duplicado core) |
| 6 | `jaraba_analytics/src/Controller/AnalyticsDashboardController.php` | 48 | Detectar tenant del contexto |
| 7 | `jaraba_crm/src/Controller/CrmDashboardController.php` | 52 | Obtener tenant_id del contexto actual |
| 8 | `jaraba_page_builder/src/PageContentListBuilder.php` | 93 | Filtrar por tenant del usuario actual |
| 9 | `jaraba_page_builder/src/Controller/CanvasEditorController.php` | 318 | Filtrar premium segun plan del tenant |
| 10 | `jaraba_integrations/src/Access/ConnectorInstallationAccessControlHandler.php` | 31 | Verificar tenant_id contra membresia Group del usuario |
| 11 | `jaraba_rag/src/Service/KbIndexerService.php` | 172 | Implementar reindexacion por tenant |
| 12 | `jaraba_comercio_conecta/src/Controller/MarketplaceController.php` | 65 | Obtener del TenantContextService cuando este disponible |
| 13 | `jaraba_content_hub/src/Controller/ContentHubDashboardController.php` | 321 | Create dedicated AI writing assistant route |

### 4.5 FUTURE_PHASE — Diferidos a fases futuras

**15 TODOs** — Explicitamente marcados para sprints o fases futuras.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 165 | Implementar en Sprint TK2 |
| 2 | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 179 | Implementar en Sprint TK2 |
| 3 | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 190 | Implementar en Sprint TK3 |
| 4 | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | 204 | Implementar en Sprint TK4 |
| 5 | `jaraba_servicios_conecta/src/Controller/ServiceApiController.php` | 209 | Fase 2: Implementar logica completa de creacion de reserva |
| 6 | `jaraba_servicios_conecta/src/Controller/ServiceApiController.php` | 221 | Fase 2: Implementar actualizacion de estado de reserva |
| 7 | `jaraba_training/src/Service/RoyaltyTracker.php` | 15 | INTEGRACION STRIPE CONNECT (Fase Futura) |
| 8 | `jaraba_training/src/Service/UpsellEngine.php` | 21 | INTEGRACION COMMERCE (Fase Futura) |
| 9 | `jaraba_pixels/src/Service/BatchProcessorService.php` | 188 | Implementar dispatch directo sin entidad en V2.1 |
| 10 | `jaraba_pixels/src/Service/TokenVerificationService.php` | 166 | En V2.1, hacer llamada de prueba a cada plataforma |
| 11 | `jaraba_matching/src/Controller/MatchingApiController.php` | 195 | Implementar con Qdrant semantic similarity en Fase 2 |
| 12 | `jaraba_matching/src/Controller/MatchingApiController.php` | 251 | Implementar con Qdrant en Fase 2 |
| 13 | `jaraba_comercio_conecta/src/Service/MerchantDashboardService.php` | 110 | Fase 2: ventas_hoy, ventas_mes, pedidos_pendientes |
| 14 | `jaraba_comercio_conecta/templates/comercio-merchant-dashboard.html.twig` | 157 | Fase 2: Tabla de pedidos recientes |
| 15 | `jaraba_comercio_conecta/templates/comercio-product-detail.html.twig` | 223 | Fase 6: Widget de resenas con estrellas |

### 4.6 UX_FRONTEND — Mejoras de interfaz

**8 TODOs** — Funcionalidades de UI, canvas, editores.

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

### 4.7 CALCULATIONS — Logica de negocio y metricas

**13 TODOs** — Calculos, puntuaciones, analisis.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/Service/TenantContextService.php` | 240 | Calcular uso real mediante file_managed o directorio fisico |
| 2 | `jaraba_foc/src/Service/EtlService.php` | 245 | Calcular resto de metricas |
| 3 | `jaraba_foc/src/Service/MetricsCalculatorService.php` | 424 | Calcular CAC real desde transacciones de marketing |
| 4 | `jaraba_foc/src/Service/AlertService.php` | 108 | Comparar MRR actual vs anterior para detectar caidas |
| 5 | `jaraba_foc/src/Controller/StripeWebhookController.php` | 289 | Actualizar metricas de churn |
| 6 | `jaraba_copilot_v2/src/Service/CustomerDiscoveryGamificationService.php` | 237 | Calcular week_streak basado en fechas |
| 7 | `jaraba_copilot_v2/src/Service/EntrepreneurContextService.php` | 195 | Implementar analisis de patrones con historial |
| 8 | `jaraba_social/src/Service/SocialCalendarService.php` | 214 | Implementar analisis real basado en historico de engagement |
| 9 | `jaraba_job_board/src/Controller/JobSearchController.php` | 345 | Implement similar jobs logic |
| 10 | `jaraba_lms/src/Controller/CatalogController.php` | 253 | Implement recommendation logic |
| 11 | `jaraba_interactive/src/Controller/ApiController.php` | 104 | Process learning analytics |
| 12 | `jaraba_legal_knowledge/src/Service/TaxCalculatorService.php` | 195 | Implementar escalas autonomicas para cada comunidad |
| 13 | `jaraba_job_board/src/Service/JobPostingService.php` | 96 | Implement job expiration logic |

### 4.8 INFRASTRUCTURE — Cron, colas, rendimiento

**4 TODOs** — Tests, arquitectura, infraestructura.

| # | Archivo | Linea | Descripcion |
|---|---------|-------|-------------|
| 1 | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | 640 | Implementar entidad WebhookIntegration para procesamiento especifico |
| 2 | `ecosistema_jaraba_core/src/Service/AgentAutonomyService.php` | 364 | Re-ejecutar la accion con el executor guardado |
| 3 | `ecosistema_jaraba_core/tests/src/Kernel/TenantProvisioningTest.php` | 50 | Migrar tests a Functional (BrowserTestBase) |
| 4 | `jaraba_integrations/src/Controller/WebhookReceiverController.php` | 51 | Implementar dispatch interno segun tipo de webhook |

---

## 5. TODOs por Modulo

### 5.1 Tabla Completa

| # | Modulo | Accionables | Categorias principales |
|---|--------|-------------|----------------------|
| 1 | `ecosistema_jaraba_core` | 20 | DATA_INTEGRATION (6), NOTIFICATIONS (5), TENANT (4), INFRA (3), EXTERNAL_API (1), CALC (1) |
| 2 | `jaraba_job_board` | 12 | DATA_INTEGRATION (6), NOTIFICATIONS (3), EXTERNAL_API (1), CALC (2) |
| 3 | `jaraba_page_builder` | 10 | UX_FRONTEND (7), TENANT (2), DATA_INTEGRATION (1) |
| 4 | `jaraba_foc` | 7 | CALCULATIONS (4), DATA_INTEGRATION (2), NOTIFICATIONS (1) |
| 5 | `jaraba_candidate` | 6 | DATA_INTEGRATION (6) |
| 6 | `jaraba_lms` | 6 | NOTIFICATIONS (3), DATA_INTEGRATION (2), CALCULATIONS (1) |
| 7 | `jaraba_social` | 5 | EXTERNAL_API (4), CALCULATIONS (1) |
| 8 | `jaraba_rag` | 5 | EXTERNAL_API (2), NOTIFICATIONS (1), TENANT (1), DATA_INTEGRATION (1) |
| 9 | `jaraba_training` | 5 | DATA_INTEGRATION (3), FUTURE_PHASE (2) |
| 10 | `jaraba_billing` | 4 | DATA_INTEGRATION (3), TENANT (1) |
| 11 | `jaraba_tenant_knowledge` | 4 | FUTURE_PHASE (4) |
| 12 | `jaraba_comercio_conecta` | 4 | FUTURE_PHASE (3), TENANT (1) |
| 13 | `jaraba_matching` | 3 | FUTURE_PHASE (2), DATA_INTEGRATION (1) |
| 14 | `jaraba_copilot_v2` | 3 | CALCULATIONS (2), DATA_INTEGRATION (1) |
| 15 | `jaraba_interactive` | 3 | DATA_INTEGRATION (1), CALCULATIONS (1), UX_FRONTEND (1) |
| 16 | `jaraba_diagnostic` | 3 | DATA_INTEGRATION (2), EXTERNAL_API (1) |
| 17 | `jaraba_integrations` | 2 | TENANT (1), INFRASTRUCTURE (1) |
| 18 | `jaraba_agroconecta_core` | 2 | DATA_INTEGRATION (2) |
| 19 | `jaraba_pixels` | 2 | FUTURE_PHASE (2) |
| 20 | `jaraba_servicios_conecta` | 2 | FUTURE_PHASE (2) |
| 21 | `jaraba_business_tools` | 2 | EXTERNAL_API (1), DATA_INTEGRATION (1) |
| 22 | `jaraba_analytics` | 1 | TENANT (1) |
| 23 | `jaraba_crm` | 1 | TENANT (1) |
| 24 | `jaraba_content_hub` | 1 | TENANT (1) |
| 25 | `jaraba_legal_knowledge` | 1 | CALCULATIONS (1) |
| 26 | `jaraba_funding` | 1 | EXTERNAL_API (1) |
| 27 | `jaraba_mentoring` | 1 | DATA_INTEGRATION (1) |

---

## 6. Priorizacion y Roadmap

### 6.1 Sprint Inmediato (Prioridad Alta — 48 TODOs unicos)

**Objetivo**: Conexion con datos reales y filtrado multi-tenant.

| Bloque | TODOs | Descripcion |
|--------|-------|-------------|
| Tenant Filtering | 13 | Activar filtrado por `tenant_id` en todos los controladores y list builders usando `TenantContextService::getCurrentTenantId()` |
| Data Integration — Empleabilidad | 12 | Conectar `jaraba_candidate`, `jaraba_job_board` con entidades reales (candidate_profile, employer_profile, candidate_skill, agent rating) |
| Data Integration — Core | 6 | Conectar `ExpansionRevenueService`, `MicroAutomationService`, `SandboxTenantService`, `ApiController` con queries BD reales |
| Data Integration — LMS/Training | 5 | Conectar `CatalogController` con lms_lesson, enrollment logic, LadderService, TrainingProduct/CertificationProgram entities |
| Data Integration — Otros | 12 | FOC Stripe webhooks, diagnostico, matching, agroconecta sales, copilot, RAG, interactive xAPI, mentoring |

> **Nota**: De los 52 TODOs brutos de DATA_INTEGRATION (39) + TENANT_FILTERING (13), se restan 4 duplicados cross-module (billing/core) para obtener 48 unicos.

### 6.2 Sprint Medio (Prioridad Media — 37 TODOs)

**Objetivo**: Notificaciones, APIs externas, metricas.

| Bloque | TODOs | Descripcion |
|--------|-------|-------------|
| Notificaciones | 13 | Implementar `hook_mail()` + `MailManagerInterface::mail()` para alertas de webhook, enrollment, application status, MRR drops, VAPID push |
| External APIs — Social | 4 | Implementar clientes OAuth reales para Facebook, Instagram, LinkedIn, Make.com |
| External APIs — AI/Vector | 3 | Conectar Qdrant para semantic search en funding, job board embeddings, LLM grounding |
| External APIs — Otros | 4 | Whisper API, DomPDF/Puppeteer (x2), plataforma ad pixels verification |
| Calculations | 13 | CAC real, week_streak, tokens_used LLM, engagement historico, MRR comparativo, churn metrics, job expiration, similar jobs, recommendation logic, escalas autonomicas |

### 6.3 Sprint Diferido (Prioridad Baja — 27 TODOs)

**Objetivo**: Fases futuras, mejoras de UX e infraestructura.

| Bloque | TODOs | Descripcion |
|--------|-------|-------------|
| Future Phase — Sprints TK | 4 | Knowledge Base Sprints TK2-TK4 |
| Future Phase — ServiciosConecta | 2 | Fase 2: reservas completas |
| Future Phase — ComercioConecta | 3 | Fase 2: pedidos/ventas, Fase 6: resenas |
| Future Phase — Matching/Pixels | 4 | Qdrant Fase 2, BatchProcessor V2.1, TokenVerification V2.1 |
| Future Phase — Training | 2 | Stripe Connect royalties, Commerce integration |
| UX Frontend | 8 | Canvas save/publish, accessibility panel, i18n selector, section editor fields, pricing table, interactive review |
| Infrastructure | 4 | WebhookIntegration entity, agent re-execution, Kernel→Functional tests, webhook dispatch |

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
| 2026-02-12 | 1.1.0 | Scan exhaustivo: 116 bruto (112 unicos). Anadidos 4 TODOs faltantes (DiagnosticReportService, WebhookReceiverController, JobPostingService, entrepreneur-agent-fab). Eliminadas 5 duplicaciones cross-categoria. Actualizada tabla modulos (27 modulos) y roadmap (48/37/27 por sprint) |
