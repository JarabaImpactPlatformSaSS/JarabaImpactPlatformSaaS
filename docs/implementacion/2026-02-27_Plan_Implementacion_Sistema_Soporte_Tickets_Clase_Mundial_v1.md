# Sistema de Soporte y Tickets con IA Proactiva — Plan de Implementacion Integral

**Fecha**: 2026-02-27
**Modulos afectados**: `jaraba_support` (nuevo), `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`, `jaraba_ai_agents`, `jaraba_content_hub`, `jaraba_billing`
**Spec**: 178_Platform_Tenant_Support_Tickets_v1_Claude.md
**Analisis**: 2026-02-27_Analisis_Sistema_Soporte_Tickets_Clase_Mundial_v1.md
**Impacto**: Modulo completo de gestion de incidencias multi-tenant con clasificacion IA, resolucion autonoma, SLA engine, portal frontend premium, panel de agente, analytics, y elevacion a 100% clase mundial
**Estado global**: PENDIENTE — Plan aprobado, implementacion no iniciada

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Alcance](#13-alcance)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
   - 1.5 [Estimacion](#15-estimacion)
   - 1.6 [Riesgos y mitigacion](#16-riesgos-y-mitigacion)
2. [Inventario de Requisitos del Analisis](#2-inventario-de-requisitos-del-analisis)
   - 2.1 [Requisitos de la spec 178](#21-requisitos-de-la-spec-178)
   - 2.2 [Gaps de clase mundial identificados (GAP-SUP-01 a GAP-SUP-13)](#22-gaps-de-clase-mundial-identificados-gap-sup-01-a-gap-sup-13)
   - 2.3 [Diferenciadores competitivos (D1 a D5)](#23-diferenciadores-competitivos-d1-a-d5)
3. [Tabla de Correspondencia con Especificaciones Tecnicas](#3-tabla-de-correspondencia-con-especificaciones-tecnicas)
   - 3.1 [Mapping de entidades](#31-mapping-de-entidades)
   - 3.2 [Mapping de servicios](#32-mapping-de-servicios)
   - 3.3 [Mapping de controladores y rutas](#33-mapping-de-controladores-y-rutas)
   - 3.4 [Mapping de APIs REST](#34-mapping-de-apis-rest)
   - 3.5 [Mapping de automatizaciones ECA](#35-mapping-de-automatizaciones-eca)
   - 3.6 [Funcionalidades nuevas para clase mundial](#36-funcionalidades-nuevas-para-clase-mundial)
4. [Tabla de Cumplimiento de Directrices del Proyecto](#4-tabla-de-cumplimiento-de-directrices-del-proyecto)
5. [Requisitos Previos](#5-requisitos-previos)
   - 5.1 [Stack tecnologico](#51-stack-tecnologico)
   - 5.2 [Modulos Drupal requeridos](#52-modulos-drupal-requeridos)
   - 5.3 [Infraestructura](#53-infraestructura)
   - 5.4 [Documentos de referencia](#54-documentos-de-referencia)
6. [Entorno de Desarrollo](#6-entorno-de-desarrollo)
   - 6.1 [Comandos Docker/Lando](#61-comandos-dockerlando)
   - 6.2 [URLs de verificacion](#62-urls-de-verificacion)
   - 6.3 [Compilacion SCSS](#63-compilacion-scss)
7. [Arquitectura — Modelo de Datos Completo](#7-arquitectura--modelo-de-datos-completo)
   - 7.1 [Diagrama de entidades](#71-diagrama-de-entidades)
   - 7.2 [Entidad support_ticket](#72-entidad-support_ticket)
   - 7.3 [Entidad ticket_message](#73-entidad-ticket_message)
   - 7.4 [Entidad ticket_attachment](#74-entidad-ticket_attachment)
   - 7.5 [Entidad sla_policy (ConfigEntity)](#75-entidad-sla_policy-configentity)
   - 7.6 [Entidad business_hours_schedule (ConfigEntity)](#76-entidad-business_hours_schedule-configentity)
   - 7.7 [Entidad ticket_event_log](#77-entidad-ticket_event_log)
   - 7.8 [Entidad response_template](#78-entidad-response_template)
   - 7.9 [Entidad agent_saved_view](#79-entidad-agent_saved_view)
   - 7.10 [Access control handlers con tenant isolation](#710-access-control-handlers-con-tenant-isolation)
   - 7.11 [Permisos unificados (RBAC)](#711-permisos-unificados-rbac)
   - 7.12 [Estructura de archivos del modulo](#712-estructura-de-archivos-del-modulo)
8. [Servicios — Diseno Detallado](#8-servicios--diseno-detallado)
   - 8.1 [TicketService — CRUD y lifecycle](#81-ticketservice--crud-y-lifecycle)
   - 8.2 [TicketAiClassificationService — Pipeline IA](#82-ticketaiclassificationservice--pipeline-ia)
   - 8.3 [TicketAiResolutionService — Resolucion autonoma](#83-ticketairesolutionservice--resolucion-autonoma)
   - 8.4 [SlaEngineService — Gestion de SLAs](#84-slaengineservice--gestion-de-slas)
   - 8.5 [BusinessHoursService — Calendario laboral](#85-businesshoursservice--calendario-laboral)
   - 8.6 [TicketRoutingService — Enrutamiento inteligente](#86-ticketroutingservice--enrutamiento-inteligente)
   - 8.7 [AttachmentService — Gestion de adjuntos](#87-attachmentservice--gestion-de-adjuntos)
   - 8.8 [AttachmentScanService — Virus scanning](#88-attachmentscanservice--virus-scanning)
   - 8.9 [AttachmentUrlService — URLs pre-firmadas](#89-attachmenturlservice--urls-pre-firmadas)
   - 8.10 [TicketNotificationService — Notificaciones multicanal](#810-ticketnotificationservice--notificaciones-multicanal)
   - 8.11 [TicketSummarizationService — Resumenes IA](#811-ticketsummarizationservice--resumenes-ia)
   - 8.12 [TicketMergeService — Fusion de tickets](#812-ticketmergeservice--fusion-de-tickets)
   - 8.13 [SupportAnalyticsService — Metricas y KPIs](#813-supportanalyticsservice--metricas-y-kpis)
   - 8.14 [SupportHealthScoreService — Integracion CS](#814-supporthealthscoreservice--integracion-cs)
   - 8.15 [CsatSurveyService — Encuestas de satisfaccion](#815-csatsurveyservice--encuestas-de-satisfaccion)
   - 8.16 [TicketStreamService — SSE real-time](#816-ticketstreamservice--sse-real-time)
   - 8.17 [SupportAgentSmartAgent — Agente IA Gen 2](#817-supportagentsmartagent--agente-ia-gen-2)
   - 8.18 [services.yml completo](#818-servicesyml-completo)
9. [Controladores y Rutas](#9-controladores-y-rutas)
   - 9.1 [SupportPortalController — Portal frontend tenant](#91-supportportalcontroller--portal-frontend-tenant)
   - 9.2 [TicketDetailController — Detalle de ticket](#92-ticketdetailcontroller--detalle-de-ticket)
   - 9.3 [AgentDashboardController — Panel de agente](#93-agentdashboardcontroller--panel-de-agente)
   - 9.4 [SupportApiController — API REST completa](#94-supportapicontroller--api-rest-completa)
   - 9.5 [SupportStreamController — SSE endpoint](#95-supportstreamcontroller--sse-endpoint)
   - 9.6 [SupportSettingsController — Configuracion admin](#96-supportSettingscontroller--configuracion-admin)
   - 9.7 [Tabla de rutas completa](#97-tabla-de-rutas-completa)
10. [Arquitectura Frontend](#10-arquitectura-frontend)
    - 10.1 [Zero Region Policy — Paginas limpias](#101-zero-region-policy--paginas-limpias)
    - 10.2 [Templates Twig de pagina](#102-templates-twig-de-pagina)
    - 10.3 [Templates Twig parciales](#103-templates-twig-parciales)
    - 10.4 [Body classes via hook_preprocess_html()](#104-body-classes-via-hook_preprocess_html)
    - 10.5 [hook_preprocess_page() para variables de theme](#105-hook_preprocess_page-para-variables-de-theme)
    - 10.6 [SCSS: Federated Design Tokens](#106-scss-federated-design-tokens)
    - 10.7 [JavaScript: Support Portal interactivity](#107-javascript-support-portal-interactivity)
    - 10.8 [Iconos: jaraba_icon()](#108-iconos-jaraba_icon)
    - 10.9 [Mobile-first responsive](#109-mobile-first-responsive)
    - 10.10 [Modales slide-panel para CRUD](#1010-modales-slide-panel-para-crud)
    - 10.11 [Theme Settings: configuracion administrable](#1011-theme-settings-configuracion-administrable)
    - 10.12 [GrapesJS: Support Widget Block](#1012-grapesjs-support-widget-block)
11. [Internacionalizacion (i18n)](#11-internacionalizacion-i18n)
12. [Seguridad](#12-seguridad)
13. [Integracion con Ecosistema](#13-integracion-con-ecosistema)
    - 13.1 [TenantContextService / TenantBridgeService](#131-tenantcontextservice--tenantbridgeservice)
    - 13.2 [Customer Success health score](#132-customer-success-health-score)
    - 13.3 [Knowledge Base y FAQ Bot](#133-knowledge-base-y-faq-bot)
    - 13.4 [AI Skills y SmartBaseAgent](#134-ai-skills-y-smartbaseagent)
    - 13.5 [Billing / Stripe](#135-billing--stripe)
    - 13.6 [PlanResolverService (feature gates)](#136-planresolverservice-feature-gates)
14. [Navegacion Drupal Admin](#14-navegacion-drupal-admin)
    - 14.1 [/admin/structure: Field UI + Settings](#141-adminstructure-field-ui--settings)
    - 14.2 [/admin/content: Listados de entidades](#142-admincontent-listados-de-entidades)
    - 14.3 [Entity links: CRUD completo](#143-entity-links-crud-completo)
15. [Fases de Implementacion](#15-fases-de-implementacion)
    - 15.1 [Fase 1: Fundacion — Modelo de datos y CRUD basico](#151-fase-1-fundacion--modelo-de-datos-y-crud-basico)
    - 15.2 [Fase 2: Portal Tenant — Frontend premium](#152-fase-2-portal-tenant--frontend-premium)
    - 15.3 [Fase 3: Motor IA — Clasificacion y resolucion](#153-fase-3-motor-ia--clasificacion-y-resolucion)
    - 15.4 [Fase 4: SLA Engine y Business Hours](#154-fase-4-sla-engine-y-business-hours)
    - 15.5 [Fase 5: Panel de Agente](#155-fase-5-panel-de-agente)
    - 15.6 [Fase 6: Adjuntos, Seguridad y Virus Scanning](#156-fase-6-adjuntos-seguridad-y-virus-scanning)
    - 15.7 [Fase 7: Clase Mundial — SSE, Merge, Summarization](#157-fase-7-clase-mundial--sse-merge-summarization)
    - 15.8 [Fase 8: Analytics, CSAT y Health Score](#158-fase-8-analytics-csat-y-health-score)
    - 15.9 [Fase 9: Integracion Email y WhatsApp](#159-fase-9-integracion-email-y-whatsapp)
    - 15.10 [Fase 10: Testing integral y Go-Live](#1510-fase-10-testing-integral-y-go-live)
16. [Estrategia de Testing](#16-estrategia-de-testing)
17. [Verificacion y Despliegue](#17-verificacion-y-despliegue)
    - 17.1 [Checklist manual](#171-checklist-manual)
    - 17.2 [Comandos de verificacion](#172-comandos-de-verificacion)
    - 17.3 [Browser verification URLs](#173-browser-verification-urls)
    - 17.4 [Checklist de directrices cumplidas](#174-checklist-de-directrices-cumplidas)
18. [Troubleshooting](#18-troubleshooting)
19. [Referencias Cruzadas](#19-referencias-cruzadas)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

El modulo `jaraba_support` es un sistema completo de gestion de incidencias y soporte tecnico para la Jaraba Impact Platform. Incluye:

- **9 entidades** (7 ContentEntity + 2 ConfigEntity): support_ticket, ticket_message, ticket_attachment, ticket_event_log, response_template, agent_saved_view, ticket_watcher (ContentEntity) + sla_policy, business_hours_schedule (ConfigEntity)
- **18 servicios PHP**: TicketService, TicketAiClassificationService, TicketAiResolutionService, SlaEngineService, BusinessHoursService, TicketRoutingService, AttachmentService, AttachmentScanService, AttachmentUrlService, TicketNotificationService, TicketSummarizationService, TicketMergeService, SupportAnalyticsService, SupportHealthScoreService, CsatSurveyService, TicketStreamService, TicketDeflectionService, SupportCronService
- **1 agente IA Gen 2**: SupportAgentSmartAgent (extiende SmartBaseAgent)
- **6 controladores**: SupportPortalController, TicketDetailController, AgentDashboardController, SupportApiController, SupportStreamController, SupportSettingsController
- **~35 rutas**: 8 frontend, 16 API REST, 6 admin, 5 SSE/stream
- **Portal frontend premium**: Paginas limpias zero-region con header, navegacion y footer propios del tema
- **Panel de agente**: Dashboard con cola inteligente, collision detection, metricas personales
- **Motor IA completo**: Clasificacion, resolucion autonoma, summarization, deflection
- **SLA engine**: Business hours calendar, pausa en pending, warnings escalonados
- **Seguridad**: Virus scanning (ClamAV), pre-signed URLs, tenant isolation, CSRF, rate limiting

### 1.2 Por que se implementa

- **Gap critico**: La auditoria de mercado SaaS (Gaps #6 y #7) identifico la ausencia de un sistema formal de soporte como brecha critica para competitividad
- **Baseline obligatorio 2026**: Los sistemas de tickets con IA son considerados requisito minimo por clientes B2B e institucionales
- **Diferenciador**: IA de soporte incluida sin coste adicional, con contexto vertical profundo
- **Integracion ecosistema**: Conecta con Customer Success, Knowledge Base, AI Skills, Billing

### 1.3 Alcance

**Incluido:**
- Modulo `jaraba_support` completo
- Portal frontend para tenants (crear, seguir, gestionar tickets)
- Panel de agente con dashboard y cola inteligente
- Motor IA para clasificacion, resolucion y deflection
- SLA engine con business hours y warnings
- Gestion de adjuntos con virus scanning
- SSE para actualizaciones en tiempo real
- Analytics y metricas
- Integracion con Customer Success health score
- CSAT + CES surveys
- Email inbound parsing
- Cierre de los 13 gaps identificados en el analisis

**Excluido:**
- WhatsApp Business integration (Fase futura, requiere API Twilio)
- Integracion Slack/Teams para agentes internos (Fase futura)
- Chat en vivo bidireccional WebSocket (el FAB widget del FAQ Bot cubre este caso)

### 1.4 Filosofia de implementacion

1. **Drupal-native**: ContentEntity con Field UI, Views integration, y acceso completo a /admin/structure y /admin/content
2. **Federated Design Tokens**: SCSS consume `var(--ej-*)`, nunca define variables `$ej-*` propias
3. **Zero-region Twig**: Paginas frontend limpias con `{% include %}` de parciales del tema
4. **Premium Forms**: Todas las forms extienden `PremiumEntityFormBase` con getSectionDefinitions() y getFormIcon()
5. **Tenant isolation**: TenantContextService + AccessControlHandler con verificacion de tenant
6. **i18n first**: Todos los textos via `$this->t()`, `{% trans %}`, `t()` en baseFieldDefinitions
7. **SCSS compilado**: Archivos parciales con prefijo `_`, compilacion Dart Sass, variables inyectables via Drupal UI
8. **Slide-panel para CRUD**: Crear/editar tickets en modal sin abandonar la pagina
9. **Mobile-first**: Layout responsive con breakpoints del tema (`$ej-breakpoint-*`)
10. **Seguridad defense-in-depth**: CSRF, virus scanning, pre-signed URLs, rate limiting, XSS prevention

### 1.5 Estimacion

| Fase | Descripcion | Horas Estimadas |
|------|-------------|-----------------|
| Fase 1 | Fundacion — Modelo de datos y CRUD basico | 60-80h |
| Fase 2 | Portal Tenant — Frontend premium | 80-100h |
| Fase 3 | Motor IA — Clasificacion y resolucion | 80-100h |
| Fase 4 | SLA Engine y Business Hours | 40-50h |
| Fase 5 | Panel de Agente | 60-80h |
| Fase 6 | Adjuntos, Seguridad y Virus Scanning | 40-50h |
| Fase 7 | Clase Mundial — SSE, Merge, Summarization | 50-60h |
| Fase 8 | Analytics, CSAT y Health Score | 40-50h |
| Fase 9 | Integracion Email y WhatsApp | 30-40h |
| Fase 10 | Testing integral y Go-Live | 40-50h |
| **TOTAL** | | **520-660h** |

### 1.6 Riesgos y mitigacion

| Riesgo | Prob. | Impacto | Mitigacion |
|--------|-------|---------|------------|
| Sobrecarga API IA en pico | Media | Alto | ProviderFallbackService (circuit breaker) + ModelRouterService (Haiku para simples) |
| SLA impreciso por timezone | Alta | Medio | BusinessHoursService con DateTimeImmutable + timezone explicito |
| Adjuntos maliciosos | Baja | Critico | ClamAV + cuarentena automatica + pre-signed URLs |
| Complejidad de integracion | Media | Alto | Sprints incrementales, cada fase produce entregable funcional |
| Coste API Claude | Media | Medio | Model routing: Haiku (tickets simples), Sonnet (complejos), Opus (criticos) |

---

## 2. Inventario de Requisitos del Analisis

### 2.1 Requisitos de la spec 178

| ID | Requisito | Seccion Spec | Estado Plan |
|----|-----------|-------------|-------------|
| R-01 | Entidad support_ticket con 30+ campos | 3.1 | Fase 1 — extendido con campos de clase mundial |
| R-02 | Entidad ticket_message con hilo conversacional | 3.2 | Fase 1 |
| R-03 | Entidad ticket_attachment con analisis IA | 3.3 | Fase 6 |
| R-04 | Entidad sla_policy por plan/prioridad | 3.4 | Fase 4 (ConfigEntity) |
| R-05 | Entidad ticket_event_log inmutable | 3.5 | Fase 1 |
| R-06 | Motor IA proactivo pre/durante/post | 4.1-4.3 | Fases 3, 7, 8 |
| R-07 | Pipeline 9 pasos clasificacion+respuesta | 4.2 | Fase 3 |
| R-08 | Analisis IA de adjuntos (vision, OCR) | 4.3 | Fase 6 |
| R-09 | SLA matrix 4 plans x 4 prioridades | 5.1 | Fase 4 |
| R-10 | Canales por plan (starter→institutional) | 5.2 | Fase 4 (ConfigEntity) |
| R-11 | Portal tenant: flujo 3 pasos con deflection | 6.1 | Fase 2 |
| R-12 | Vista "Mis Tickets" con dashboard | 6.2 | Fase 2 |
| R-13 | Vista detalle ticket conversacional | 6.3 | Fase 2 |
| R-14 | Integracion multicanal (in-app, email, WhatsApp) | 7.1-7.3 | Fases 2, 9 |
| R-15 | Widget soporte in-app | 7.2 | Fase 2 (reutiliza FAQ Bot FAB) |
| R-16 | Integracion Customer Success health score | 7.3 | Fase 8 |
| R-17 | 12 flujos ECA automatizados | 8 | Fases 3-8 (distribuidos) |
| R-18 | 16 endpoints API REST | 9 | Fase 1 (basico), Fases 3-8 (completo) |
| R-19 | Panel de agente con cola inteligente | 10.1-10.2 | Fase 5 |
| R-20 | Analytics de soporte con KPIs | 11 | Fase 8 |
| R-21 | RBAC con 5 roles | 12 | Fase 1 |

### 2.2 Gaps de clase mundial identificados (GAP-SUP-01 a GAP-SUP-13)

| Gap ID | Descripcion | Fase Plan | Prioridad |
|--------|-------------|-----------|-----------|
| GAP-SUP-01 | Ticket merging/splitting | Fase 7 | P1 |
| GAP-SUP-02 | Business hours calendar configurable | Fase 4 | P0 |
| GAP-SUP-03 | Collision detection para agentes | Fase 7 | P1 |
| GAP-SUP-04 | Macros/templates con variables | Fase 5 | P1 |
| GAP-SUP-05 | Parent/child tickets | Fase 7 | P1 |
| GAP-SUP-06 | CC/BCC en tickets (watchers) | Fase 7 | P2 |
| GAP-SUP-07 | Auto-cierre configurable por tenant | Fase 4 | P2 |
| GAP-SUP-08 | Virus scanning en adjuntos | Fase 6 | P0 |
| GAP-SUP-09 | Pre-signed URLs para adjuntos | Fase 6 | P0 |
| GAP-SUP-10 | Customer Effort Score (CES) | Fase 8 | P2 |
| GAP-SUP-11 | Thread summarization por IA | Fase 7 | P1 |
| GAP-SUP-12 | Saved views/filtros personalizados | Fase 5 | P1 |
| GAP-SUP-13 | SSE/real-time para updates | Fase 7 | P1 |

### 2.3 Diferenciadores competitivos (D1 a D5)

| ID | Diferenciador | Fase Plan |
|----|---------------|-----------|
| D1 | Contexto vertical profundo en IA | Fase 3 (vertical detection en pipeline) |
| D2 | Grounding estricto + citacion de fuentes | Fase 3 (ai_sources en respuesta) |
| D3 | IA incluida sin coste extra | Transversal (arquitectura de modelo routing) |
| D4 | Ciclo KB ↔ Tickets (auto-FAQ generation) | Fase 8 |
| D5 | Health score alimentado por soporte | Fase 8 |

---

## 3. Tabla de Correspondencia con Especificaciones Tecnicas

### 3.1 Mapping de entidades

| Entidad Spec 178 | Implementacion Drupal | Tipo | Modulo |
|-------------------|-----------------------|------|--------|
| support_ticket | `SupportTicket` ContentEntity | Content | jaraba_support |
| ticket_message | `TicketMessage` ContentEntity | Content | jaraba_support |
| ticket_attachment | `TicketAttachment` ContentEntity | Content | jaraba_support |
| sla_policy | `SlaPolicy` ConfigEntity | Config | jaraba_support |
| ticket_event_log | `TicketEventLog` ContentEntity (append-only) | Content | jaraba_support |
| — (nuevo) | `BusinessHoursSchedule` ConfigEntity | Config | jaraba_support |
| — (nuevo) | `ResponseTemplate` ContentEntity | Content | jaraba_support |
| — (nuevo) | `AgentSavedView` ContentEntity | Content | jaraba_support |
| — (nuevo) | `TicketWatcher` ContentEntity | Content | jaraba_support |

### 3.2 Mapping de servicios

| Funcionalidad Spec | Servicio PHP | ID Servicio |
|--------------------|-------------|-------------|
| CRUD tickets + lifecycle | `TicketService` | `jaraba_support.ticket` |
| Pipeline clasificacion IA | `TicketAiClassificationService` | `jaraba_support.ai_classification` |
| Resolucion autonoma IA | `TicketAiResolutionService` | `jaraba_support.ai_resolution` |
| SLA deadlines + warnings + breach | `SlaEngineService` | `jaraba_support.sla_engine` |
| Business hours calculation | `BusinessHoursService` | `jaraba_support.business_hours` |
| Enrutamiento inteligente a agentes | `TicketRoutingService` | `jaraba_support.routing` |
| Upload/download adjuntos | `AttachmentService` | `jaraba_support.attachment` |
| Virus scanning ClamAV | `AttachmentScanService` | `jaraba_support.attachment_scan` |
| URLs pre-firmadas con HMAC | `AttachmentUrlService` | `jaraba_support.attachment_url` |
| Notificaciones multicanal | `TicketNotificationService` | `jaraba_support.notification` |
| Resumenes IA de hilos | `TicketSummarizationService` | `jaraba_support.summarization` |
| Fusion de tickets | `TicketMergeService` | `jaraba_support.merge` |
| KPIs y metricas | `SupportAnalyticsService` | `jaraba_support.analytics` |
| Health score feed | `SupportHealthScoreService` | `jaraba_support.health_score` |
| Encuestas CSAT + CES | `CsatSurveyService` | `jaraba_support.csat` |
| SSE real-time stream | `TicketStreamService` | `jaraba_support.stream` |
| KB deflection pre-ticket | `TicketDeflectionService` | `jaraba_support.deflection` |
| Cron: SLA check, auto-close, reminders | `SupportCronService` | `jaraba_support.cron` |
| Agente IA Gen 2 | `SupportAgentSmartAgent` | `jaraba_support.smart_support_agent` |

### 3.3 Mapping de controladores y rutas

| Funcionalidad Spec | Controlador | Tipo Ruta |
|--------------------|-------------|-----------|
| Portal tenant (lista + crear) | `SupportPortalController` | Frontend (_admin_route: FALSE) |
| Detalle ticket conversacional | `TicketDetailController` | Frontend |
| Panel agente + dashboard | `AgentDashboardController` | Frontend |
| 16+ endpoints REST | `SupportApiController` | API (JSON, CSRF) |
| SSE stream para real-time | `SupportStreamController` | SSE (text/event-stream) |
| Configuracion admin SLA + BH | `SupportSettingsController` | Admin |

### 3.4 Mapping de APIs REST

| Spec Endpoint | Ruta Drupal | Metodo | Controlador::metodo |
|---------------|------------|--------|---------------------|
| POST /tickets | `/api/v1/support/tickets` | POST | `SupportApiController::createTicket` |
| GET /tickets | `/api/v1/support/tickets` | GET | `SupportApiController::listTickets` |
| GET /tickets/{id} | `/api/v1/support/tickets/{support_ticket}` | GET | `SupportApiController::getTicket` |
| PATCH /tickets/{id} | `/api/v1/support/tickets/{support_ticket}` | PATCH | `SupportApiController::updateTicket` |
| POST /tickets/{id}/messages | `/api/v1/support/tickets/{support_ticket}/messages` | POST | `SupportApiController::addMessage` |
| POST /tickets/{id}/attachments | `/api/v1/support/tickets/{support_ticket}/attachments` | POST | `SupportApiController::uploadAttachment` |
| GET /tickets/{id}/attachments | `/api/v1/support/tickets/{support_ticket}/attachments` | GET | `SupportApiController::listAttachments` |
| POST /tickets/{id}/resolve | `/api/v1/support/tickets/{support_ticket}/resolve` | POST | `SupportApiController::resolveTicket` |
| POST /tickets/{id}/reopen | `/api/v1/support/tickets/{support_ticket}/reopen` | POST | `SupportApiController::reopenTicket` |
| POST /tickets/{id}/escalate | `/api/v1/support/tickets/{support_ticket}/escalate` | POST | `SupportApiController::escalateTicket` |
| POST /tickets/{id}/satisfaction | `/api/v1/support/tickets/{support_ticket}/satisfaction` | POST | `SupportApiController::submitSatisfaction` |
| POST /tickets/{id}/merge | `/api/v1/support/tickets/{support_ticket}/merge` | POST | `SupportApiController::mergeTicket` |
| GET /support/stats | `/api/v1/support/stats` | GET | `SupportApiController::getStats` |
| GET /support/sla-policies | `/api/v1/support/sla-policies` | GET | `SupportApiController::listSlaPolicies` |
| POST /support/ai/classify | `/api/v1/support/ai/classify` | POST | `SupportApiController::classifyText` |
| POST /support/ai/suggest | `/api/v1/support/ai/suggest` | POST | `SupportApiController::suggestSolution` |
| GET /support/search | `/api/v1/support/search` | GET | `SupportApiController::searchTickets` |
| POST /support/inbound/email | `/api/v1/support/inbound/email` | POST | `SupportApiController::inboundEmail` |
| GET /support/stream | `/api/v1/support/stream` | GET | `SupportStreamController::stream` |
| POST /support/deflection | `/api/v1/support/deflection` | POST | `SupportApiController::deflectionSearch` |

### 3.5 Mapping de automatizaciones ECA

| Codigo Spec | Evento | Fase Impl. | Servicio Responsable |
|-------------|--------|------------|---------------------|
| SUP-001 | ticket.created | Fase 3 | TicketAiClassificationService + SlaEngineService |
| SUP-002 | ticket.ai_resolved | Fase 3 | TicketAiResolutionService |
| SUP-003 | ticket.ai_rejected | Fase 3 | TicketRoutingService |
| SUP-004 | ticket.assigned | Fase 5 | TicketNotificationService |
| SUP-005 | sla.warning | Fase 4 | SlaEngineService (cron) |
| SUP-006 | sla.breached | Fase 4 | SlaEngineService + SupportHealthScoreService |
| SUP-007 | ticket.idle_48h | Fase 4 | SupportCronService |
| SUP-008 | ticket.resolved | Fase 8 | CsatSurveyService |
| SUP-009 | ticket.pattern_detected | Fase 8 | SupportAnalyticsService |
| SUP-010 | ticket.billing_category | Fase 8 | SupportHealthScoreService |
| SUP-011 | attachment.uploaded | Fase 6 | AttachmentScanService + TicketAiClassificationService |
| SUP-012 | ticket.satisfaction_low | Fase 8 | CsatSurveyService + TicketNotificationService |

### 3.6 Funcionalidades nuevas para clase mundial

| ID | Funcionalidad | Entidad/Servicio | Fase |
|----|---------------|-----------------|------|
| WC-01 | Ticket merging con persistencia | `SupportTicket.merged_into_id` + `TicketMergeService` | Fase 7 |
| WC-02 | Business hours calendar | `BusinessHoursSchedule` ConfigEntity + `BusinessHoursService` | Fase 4 |
| WC-03 | Collision detection | `TicketStreamService` SSE events | Fase 7 |
| WC-04 | Response templates con variables | `ResponseTemplate` ContentEntity | Fase 5 |
| WC-05 | Parent/child tickets | `SupportTicket.parent_ticket_id` | Fase 7 |
| WC-06 | Ticket watchers (CC) | `TicketWatcher` ContentEntity | Fase 7 |
| WC-07 | Auto-cierre configurable | `SlaPolicy.auto_close_hours` | Fase 4 |
| WC-08 | Virus scanning | `AttachmentScanService` (ClamAV) | Fase 6 |
| WC-09 | Pre-signed URLs | `AttachmentUrlService` (HMAC) | Fase 6 |
| WC-10 | CES junto a CSAT | `SupportTicket.effort_score` | Fase 8 |
| WC-11 | Thread summarization | `TicketSummarizationService` | Fase 7 |
| WC-12 | Saved views para agentes | `AgentSavedView` ContentEntity | Fase 5 |
| WC-13 | SSE real-time updates | `TicketStreamService` + `SupportStreamController` | Fase 7 |
| WC-14 | Bulk actions en lista | `SupportApiController::bulkAction` | Fase 5 |
| WC-15 | SLA pause on pending | `SlaEngineService.pauseSla()` | Fase 4 |

---

## 4. Tabla de Cumplimiento de Directrices del Proyecto

| Directriz | Referencia | Cumplimiento | Detalle |
|-----------|-----------|--------------|---------|
| **Federated Design Tokens** | Theming Master 2.1 | ✅ | SCSS en `jaraba_support/scss/` consume `var(--ej-*)`. No define `$ej-*` propias. Bundle en tema: `bundle-support`. |
| **SCSS compilado, nunca CSS directo** | DIRECTRICES 2.2.1 | ✅ | `npx sass scss/main.scss:css/jaraba-support.css --style=compressed`. Parciales con `_`. Dart Sass con `color.adjust()`. |
| **Variables inyectables via UI** | Theming Master 2.1 | ✅ | `_injectable.scss` genera CSS custom properties. Theme settings configuran colores de estado de ticket, icono del portal, textos. |
| **Twig limpias sin regiones** | DIRECTRICES 2.2.2 | ✅ | `page--support.html.twig` con `{% include %}` de parciales. Sin `page.content`, sin bloques heredados. |
| **Parciales Twig reutilizables** | DIRECTRICES 2.2.2 | ✅ | `_support-ticket-card.html.twig`, `_support-ticket-timeline.html.twig`, `_support-sidebar-context.html.twig`, etc. |
| **Body classes via hook_preprocess_html()** | DIRECTRICES 2.2.2 | ✅ | `jaraba_support_preprocess_html()` anade `page-support`, `page-support-portal`, `page-agent-dashboard`. |
| **Textos siempre traducibles** | i18n | ✅ | Todos los textos via `$this->t()`, `{% trans %}`, `t()` en baseFieldDefinitions. Zero strings hardcoded. |
| **Modelo SaaS con SCSS + variables inyectables** | DIRECTRICES 2.2.1 | ✅ | Theme settings para: colores de estado, icono del widget, textos del portal, CTA labels. Configurables desde UI sin codigo. |
| **Dart Sass moderno** | DIRECTRICES 2.2.1 | ✅ | `color.adjust()` en lugar de `darken()`/`lighten()`. `@use` en lugar de `@import`. |
| **Iconos via jaraba_icon()** | ICON-CONVENTION-001 | ✅ | Todos los iconos via `{{ jaraba_icon('ui', 'ticket', ...) }}`. Categoria `support` creada con iconos especificos. |
| **Premium Forms (PremiumEntityFormBase)** | PREMIUM-FORMS-PATTERN-001 | ✅ | `SupportTicketForm extends PremiumEntityFormBase`. Secciones: content, details, assignment, sla. getFormIcon() definido. |
| **Tenant isolation** | TENANT-ISOLATION-ACCESS-001 | ✅ | `SupportTicketAccessControlHandler` verifica `isSameTenant()` para update/delete. Published tickets visibles para viewer del tenant. |
| **TenantBridgeService** | TENANT-BRIDGE-001 | ✅ | `tenant_id` es `entity_reference` a `group`. Nunca `getStorage('group')` con Tenant IDs. |
| **Slide-panel para CRUD** | SLIDE-PANEL-RENDER-001 | ✅ | Crear ticket, responder, editar — todo en slide-panel. `renderPlain()` + `$form['#action']` explicito. |
| **_admin_route: FALSE** | DIRECTRICES 2.2.2 | ✅ | Todas las rutas frontend de soporte con `_admin_route: FALSE`. Tenant no accede al tema admin. |
| **CSRF en APIs** | FLUJO 3 CSRF | ✅ | `_csrf_request_header_token: TRUE` en rutas API. JS obtiene token de `Drupal.url('session/token')`. |
| **XSS prevention** | FLUJO 3 XSS | ✅ | Markdown renderizado server-side. `Drupal.checkPlain()` en JS. `|safe_html` en Twig (nunca `|raw`). |
| **API Whitelist** | API-WHITELIST-001 | ✅ | `SupportApiController::ALLOWED_FIELDS` constante. `$request` filtrado antes de `$entity->set()`. |
| **Entity en /admin/structure y /admin/content** | FIELD-UI-SETTINGS-TAB-001 | ✅ | Field UI habilitado. Settings tab con default local task. Collection en /admin/content/support-tickets. |
| **Optional DI con @?** | OPTIONAL-SERVICE-DI-001 | ✅ | Servicios de IA, Customer Success, Billing inyectados con `@?`. Nullable constructors con fallback. |
| **Presave resilience** | PRESAVE-RESILIENCE-001 | ✅ | `jaraba_support_support_ticket_presave()` con `hasService()` + try-catch para IA y analytics. |
| **hook_theme() + template_preprocess** | ENTITY-PREPROCESS-001 | ✅ | `template_preprocess_support_ticket()` extrae primitivos. Imagen, categoria, SLA resueltos. |
| **Route langprefix** | ROUTE-LANGPREFIX-001 | ✅ | URLs en JS via `Drupal.url()`. Rutas via `Url::fromRoute()`. Nunca paths hardcoded. |
| **Full-width layout mobile-first** | DIRECTRICES 2.2.2 | ✅ | Layout single-column en mobile. Breakpoints del tema. Touch targets 44x44px. |
| **Config Entities para datos parametricos** | CONFIG-SCHEMA-001 | ✅ | `SlaPolicy` y `BusinessHoursSchedule` son ConfigEntity con schema YAML. |
| **Append-only para logs** | Entidades Append-Only | ✅ | `TicketEventLog` sin form handlers edit/delete. AccessHandler deniega update/delete. |
| **GrapesJS block** | CANVAS-ARTICLE-001 | ✅ | Bloque GrapesJS "Support Portal" registrado en `grapesjs-jaraba-support-blocks.js`. |
| **DOC-GUARD-001** | FLUJO 1b | ✅ | Este documento es nuevo, no modifica maestros. Cambios a INDICE via Edit. |

---

## 5. Requisitos Previos

### 5.1 Stack tecnologico

| Componente | Version | Proposito |
|-----------|---------|-----------|
| PHP | 8.4+ | Backend |
| Drupal | 11.x | CMS, Entity API, Form API |
| MariaDB | 10.11+ | Base de datos |
| Redis | 7.4 | Cache (render, support_analytics) |
| Qdrant | 1.7+ | Busqueda semantica en tickets y KB |
| Claude API | Haiku/Sonnet/Opus | Clasificacion, resolucion, summarization |
| ClamAV | 1.0+ | Virus scanning de adjuntos |
| Dart Sass | Latest | Compilacion SCSS |
| NVM/Node | 20+ | Entorno JS para compilacion |

### 5.2 Modulos Drupal requeridos

| Modulo | Proposito | Tipo |
|--------|-----------|------|
| `ecosistema_jaraba_core` | TenantContext, TenantBridge, PremiumFormBase, jaraba_icon | Dependencia directa |
| `jaraba_ai_agents` | SmartBaseAgent, ModelRouter, ToolRegistry, Observability | Dependencia directa |
| `jaraba_rag` | Qdrant search, embeddings | Dependencia directa |
| `jaraba_billing` | PlanResolver, feature gates, suscripciones | Dependencia opcional (@?) |
| `jaraba_tenant_knowledge` | FAQ Bot, KB del tenant | Dependencia opcional (@?) |
| `datetime` | Campos datetime en SLA | Core |
| `options` | Campos list_string (status, priority) | Core |
| `text` | Campos text_long (body, description) | Core |
| `file` | Gestion de archivos adjuntos | Core |
| `image` | Thumbnails de adjuntos | Core |

### 5.3 Infraestructura

- **ClamAV**: Necesita servicio ClamAV accesible via socket o TCP. En Lando: container adicional `clamav`. En produccion: `clamav-daemon` en el servidor.
- **Qdrant**: Coleccion `jaraba_support_tickets` para embeddings de tickets. Reutiliza la infraestructura existente.
- **Storage**: Directorio `private://support_attachments/{tenant_id}/{ticket_id}/` para adjuntos con acceso controlado.

### 5.4 Documentos de referencia

| Documento | Proposito |
|-----------|-----------|
| 178_Platform_Tenant_Support_Tickets_v1_Claude.md | Especificacion original |
| 2026-02-27_Analisis_Sistema_Soporte_Tickets_Clase_Mundial_v1.md | Analisis de mercado y gaps |
| 00_DIRECTRICES_PROYECTO.md v93.0.0 | Directrices de desarrollo |
| 00_FLUJO_TRABAJO_CLAUDE.md v46.0.0 | Flujo de trabajo y aprendizajes |
| 2026-02-05_arquitectura_theming_saas_master.md v2.1 | Arquitectura SCSS |
| 2026-02-26_Plan_Implementacion_Reviews_Comentarios_Clase_Mundial_v1.md | Referencia de formato de plan |

---

## 6. Entorno de Desarrollo

### 6.1 Comandos Docker/Lando

```bash
# Habilitar modulo
lando drush en jaraba_support -y

# Instalar schemas de entidades
lando drush entity:updates

# Limpiar cache
lando drush cr

# Importar configuracion
lando drush config:import -y

# Ejecutar tests unitarios
lando php vendor/bin/phpunit --testsuite Unit --filter Support

# Ejecutar tests kernel
lando php vendor/bin/phpunit --testsuite Kernel --filter Support

# Verificar permisos
lando drush ev "print_r(array_keys(\Drupal::service('user.permissions')->getPermissions()));" | grep support
```

### 6.2 URLs de verificacion

| URL | Proposito |
|-----|-----------|
| `https://jaraba-saas.lndo.site/es/soporte` | Portal tenant frontend |
| `https://jaraba-saas.lndo.site/es/soporte/crear` | Crear ticket (slide-panel) |
| `https://jaraba-saas.lndo.site/es/soporte/ticket/{id}` | Detalle de ticket |
| `https://jaraba-saas.lndo.site/es/soporte/agente` | Panel de agente |
| `https://jaraba-saas.lndo.site/es/admin/content/support-tickets` | Admin: lista tickets |
| `https://jaraba-saas.lndo.site/es/admin/structure/support-ticket/settings` | Admin: Field UI |
| `https://jaraba-saas.lndo.site/es/admin/config/support/settings` | Admin: configuracion SLA |
| `https://jaraba-saas.lndo.site/es/api/v1/support/tickets?_format=json` | API: listar tickets |

### 6.3 Compilacion SCSS

```bash
# Modulo jaraba_support (desde raiz del modulo)
lando ssh -c "cd web/modules/custom/jaraba_support && npx sass scss/main.scss:css/jaraba-support.css --style=compressed"

# Tema — bundle de soporte
lando ssh -c "cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/bundles/support.scss:css/bundles/support.css --style=compressed"

# Watch mode (desarrollo)
lando ssh -c "cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/bundles/support.scss:css/bundles/support.css --watch"
```

---

## 7. Arquitectura — Modelo de Datos Completo

### 7.1 Diagrama de entidades

```
┌──────────────────────┐     ┌──────────────────────┐
│   SlaPolicy (Config) │     │ BusinessHoursSchedule │
│                      │────▶│     (Config)          │
│ plan_tier + priority │     │ timezone, schedule,   │
│ first_response_hours │     │ holidays              │
│ resolution_hours     │     └──────────────────────┘
│ auto_close_hours     │
└──────────┬───────────┘
           │
           │ FK
           ▼
┌─────────────────────────────────────────┐
│          SupportTicket                   │
│                                          │
│ ticket_number, tenant_id (→group),       │
│ reporter_uid, assignee_uid, vertical,    │
│ category, subject, description,          │
│ status, priority, severity, channel,     │
│ ai_classification, sla_policy_id,        │
│ merged_into_id (→self), parent_ticket_id │
│ (→self), satisfaction_rating,            │
│ effort_score, first_responded_at,        │
│ sla_paused_duration                      │
├──────────────────────────────────────────┤
│ 1:N → TicketMessage                      │
│ 1:N → TicketAttachment                   │
│ 1:N → TicketEventLog                     │
│ 1:N → TicketWatcher                      │
└──────────────────────────────────────────┘
     │           │           │
     ▼           ▼           ▼
┌──────────┐ ┌───────────┐ ┌───────────────┐
│ Ticket   │ │ Ticket    │ │ TicketEvent   │
│ Message  │ │Attachment │ │ Log           │
│          │ │           │ │ (append-only) │
│ body,    │ │ file_id,  │ │               │
│ author,  │ │ scan_stat,│ │ event_type,   │
│ is_note  │ │ storage   │ │ actor, old/   │
│ ai_src   │ │ path      │ │ new value,    │
└──────────┘ └───────────┘ │ ip, ua        │
                           └───────────────┘

┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│ ResponseTemplate │  │ AgentSavedView   │  │ TicketWatcher    │
│                  │  │                  │  │                  │
│ name, body,      │  │ name, filters    │  │ ticket_id,       │
│ category,        │  │ (JSON), owner,   │  │ user_id, type    │
│ vertical, scope  │  │ scope, sort      │  │ (cc/mention)     │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

### 7.2 Entidad support_ticket

**Archivo**: `web/modules/custom/jaraba_support/src/Entity/SupportTicket.php`

```php
/**
 * @ContentEntityType(
 *   id = "support_ticket",
 *   label = @Translation("Support Ticket"),
 *   label_collection = @Translation("Support Tickets"),
 *   label_singular = @Translation("support ticket"),
 *   label_plural = @Translation("support tickets"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_support\SupportTicketListBuilder",
 *     "access" = "Drupal\jaraba_support\SupportTicketAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\jaraba_support\Form\SupportTicketForm",
 *       "add" = "Drupal\jaraba_support\Form\SupportTicketForm",
 *       "edit" = "Drupal\jaraba_support\Form\SupportTicketForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "support_ticket",
 *   data_table = "support_ticket_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer support system",
 *   field_ui_base_route = "entity.support_ticket.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "langcode" = "langcode",
 *     "owner" = "reporter_uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/support-tickets/{support_ticket}",
 *     "add-form" = "/admin/content/support-tickets/add",
 *     "edit-form" = "/admin/content/support-tickets/{support_ticket}/edit",
 *     "delete-form" = "/admin/content/support-tickets/{support_ticket}/delete",
 *     "collection" = "/admin/content/support-tickets",
 *   },
 * )
 */
```

**Campos de baseFieldDefinitions():**

| Campo | Tipo Drupal | Requerido | Descripcion |
|-------|------------|-----------|-------------|
| `id` | `integer` (auto) | Si | ID numerico autoincremental |
| `uuid` | `uuid` | Si | UUID generado automaticamente |
| `ticket_number` | `string` (20) | Si | Formato: JRB-YYYYMM-NNNN. Generado en preSave(). |
| `tenant_id` | `entity_reference` → `group` | Si | TENANT-BRIDGE-001: FK al grupo del tenant |
| `reporter_uid` | EntityOwnerTrait (`uid`) | Si | Usuario que abre el ticket |
| `assignee_uid` | `entity_reference` → `user` | No | Agente asignado |
| `vertical` | `list_string` | Si | empleabilidad\|emprendimiento\|agro\|comercio\|servicios\|platform\|billing. Opciones via `t()`. |
| `category` | `string` (64) | Si | Categoria principal (configurable por vertical) |
| `subcategory` | `string` (64) | No | Subcategoria especifica |
| `subject` | `string` (255) | Si | Asunto del ticket. `entity_keys.label`. |
| `description` | `text_long` | Si | Descripcion detallada en Markdown |
| `status` | `list_string` | Si | new\|ai_handling\|open\|pending_customer\|pending_internal\|escalated\|resolved\|closed\|reopened\|merged. Default: `new`. |
| `priority` | `list_string` | Si | critical\|high\|medium\|low. Default: `medium`. |
| `severity` | `list_string` | No | blocker\|degraded\|minor\|cosmetic |
| `channel` | `list_string` | Si | portal\|email\|chat\|whatsapp\|phone\|api. Default: `portal`. |
| `ai_classification` | `string_long` | No | JSON: `{category, confidence, sentiment, urgency}`. Getter: `getAiClassification(): array`. |
| `ai_resolution_attempted` | `boolean` | Si | Default FALSE |
| `ai_resolution_accepted` | `boolean` | No | Si el usuario acepto la solucion IA |
| `ai_suggested_solution` | `text_long` | No | Solucion propuesta por la IA |
| `sla_policy_id` | `string` (128) | No | ID de ConfigEntity SlaPolicy |
| `sla_first_response_due` | `datetime` | No | Deadline primera respuesta |
| `sla_resolution_due` | `datetime` | No | Deadline resolucion |
| `sla_breached` | `boolean` | Si | Default FALSE |
| `sla_paused_at` | `datetime` | No | Timestamp de cuando se pauso el SLA |
| `sla_paused_duration` | `integer` | Si | Minutos acumulados de pausa. Default 0. |
| `first_responded_at` | `datetime` | No | Timestamp de primera respuesta (humana o IA) |
| `satisfaction_rating` | `integer` | No | Rating CSAT 1-5 |
| `satisfaction_comment` | `text_long` | No | Comentario CSAT |
| `effort_score` | `integer` | No | CES 1-5 (GAP-SUP-10) |
| `resolution_notes` | `text_long` | No | Notas de resolucion del agente |
| `tags` | `string_long` | No | JSON array de tags. Getter: `getTags(): array`. |
| `related_entity_type` | `string` (32) | No | order\|product\|user\|booking\|course |
| `related_entity_id` | `string` (128) | No | ID de la entidad relacionada |
| `merged_into_id` | `entity_reference` → `support_ticket` | No | FK ticket canonico (GAP-SUP-01). Self-reference. |
| `merged_from_ids` | `string_long` | No | JSON array de IDs merged into this (GAP-SUP-01) |
| `parent_ticket_id` | `entity_reference` → `support_ticket` | No | FK ticket padre (GAP-SUP-05). Self-reference. |
| `child_count` | `integer` | Si | Conteo de tickets hijos. Default 0. Computed in presave. |
| `cc_uids` | `string_long` | No | JSON array de user IDs watchers (GAP-SUP-06) |
| `created` | `created` | Si | Timestamp de creacion |
| `changed` | `changed` | Si | Timestamp de modificacion |
| `resolved_at` | `datetime` | No | Momento de resolucion |
| `closed_at` | `datetime` | No | Momento de cierre |
| `langcode` | `language` | Si | Idioma del ticket |

**Metodos clave de la entidad:**

```php
// Generacion de ticket_number en preSave()
public function preSave(EntityStorageInterface $storage): void {
  parent::preSave($storage);
  if ($this->isNew() && empty($this->get('ticket_number')->value)) {
    $this->set('ticket_number', $this->generateTicketNumber());
  }
}

private function generateTicketNumber(): string {
  $prefix = 'JRB';
  $month = date('Ym');
  // Query para obtener el siguiente secuencial del mes
  $query = \Drupal::database()->select('support_ticket', 'st')
    ->condition('ticket_number', $prefix . '-' . $month . '-%', 'LIKE');
  $query->addExpression('COUNT(*)', 'count');
  $count = (int) $query->execute()->fetchField();
  return sprintf('%s-%s-%04d', $prefix, $month, $count + 1);
}

// Getters para campos JSON
public function getAiClassification(): array {
  $value = $this->get('ai_classification')->value;
  return $value ? json_decode($value, TRUE) : [];
}

public function getTags(): array {
  $value = $this->get('tags')->value;
  return $value ? json_decode($value, TRUE) : [];
}
```

### 7.3 Entidad ticket_message

**Archivo**: `web/modules/custom/jaraba_support/src/Entity/TicketMessage.php`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | `integer` (auto) | Si | ID |
| `uuid` | `uuid` | Si | UUID |
| `ticket_id` | `entity_reference` → `support_ticket` | Si | FK al ticket padre |
| `author_uid` | `entity_reference` → `user` | No | NULL = sistema/IA |
| `author_type` | `list_string` | Si | customer\|agent\|ai\|system |
| `body` | `text_long` | Si | Contenido Markdown |
| `body_html` | `string_long` | No | HTML renderizado (computed en presave) |
| `body_plain` | `string_long` | No | Plaintext para busqueda (computed en presave) |
| `is_internal_note` | `boolean` | Si | Default FALSE. Nota interna no visible al tenant. |
| `is_ai_generated` | `boolean` | Si | Default FALSE |
| `ai_confidence` | `float` | No | Confianza 0.00-1.00 |
| `ai_sources` | `string_long` | No | JSON array de fuentes KB |
| `edited_at` | `datetime` | No | Si el mensaje fue editado |
| `created` | `created` | Si | Timestamp |

**Logica critica**: En presave, el body Markdown se renderiza a HTML via una funcion de sanitizacion (no Parsedown puro — usa `Html::escape()` + allowlist de tags seguros). El `body_plain` se genera con `strip_tags()` para indexacion de busqueda.

### 7.4 Entidad ticket_attachment

**Archivo**: `web/modules/custom/jaraba_support/src/Entity/TicketAttachment.php`

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | `integer` (auto) | Si | ID |
| `uuid` | `uuid` | Si | UUID |
| `ticket_id` | `entity_reference` → `support_ticket` | Si | FK al ticket |
| `message_id` | `entity_reference` → `ticket_message` | No | FK al mensaje (si va ligado) |
| `file_id` | `entity_reference` → `file` | Si | FK file_managed.fid |
| `filename` | `string` (255) | Si | Nombre original |
| `mime_type` | `string` (128) | Si | Tipo MIME validado |
| `file_size` | `integer` | Si | Tamano en bytes |
| `storage_path` | `string` (500) | Si | Path: `private://support_attachments/{tenant_id}/{ticket_id}/{uuid}-{filename}` |
| `scan_status` | `list_string` | Si | pending\|clean\|infected\|error. Default: `pending`. (GAP-SUP-08) |
| `ai_analysis` | `string_long` | No | JSON resultado analisis IA |
| `is_screenshot` | `boolean` | Si | Default FALSE |
| `uploaded_by` | `entity_reference` → `user` | Si | Usuario que subio |
| `created` | `created` | Si | Timestamp |

### 7.5 Entidad sla_policy (ConfigEntity)

**Archivo**: `web/modules/custom/jaraba_support/src/Entity/SlaPolicy.php`

```php
/**
 * @ConfigEntityType(
 *   id = "sla_policy",
 *   label = @Translation("SLA Policy"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_support\SlaPolicyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_support\Form\SlaPolicyForm",
 *       "edit" = "Drupal\jaraba_support\Form\SlaPolicyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "sla_policy",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id", "label", "plan_tier", "priority",
 *     "first_response_hours", "resolution_hours",
 *     "business_hours_only", "business_hours_schedule_id",
 *     "escalation_after_hours", "includes_phone",
 *     "includes_priority_queue", "idle_reminder_hours",
 *     "auto_close_hours", "pause_on_pending", "active",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/support/sla-policies/add",
 *     "edit-form" = "/admin/config/support/sla-policies/{sla_policy}",
 *     "delete-form" = "/admin/config/support/sla-policies/{sla_policy}/delete",
 *     "collection" = "/admin/config/support/sla-policies",
 *   },
 * )
 */
```

**ID Pattern**: `{plan_tier}_{priority}` (ej: `professional_high`, `enterprise_critical`). Defaults: `_default_{priority}`. Cascade via `PlanResolverService` pattern.

**Propiedades:**

| Propiedad | Tipo | Descripcion |
|-----------|------|-------------|
| `id` | string | `{plan_tier}_{priority}` |
| `label` | string | Nombre visible: "Professional - High Priority" |
| `plan_tier` | string | starter\|professional\|enterprise\|institutional |
| `priority` | string | critical\|high\|medium\|low |
| `first_response_hours` | integer | Horas para primera respuesta |
| `resolution_hours` | integer | Horas para resolucion |
| `business_hours_only` | boolean | Solo cuenta horas laborables |
| `business_hours_schedule_id` | string | ID de BusinessHoursSchedule |
| `escalation_after_hours` | integer | Horas sin actividad para auto-escalacion |
| `includes_phone` | boolean | Soporte telefonico incluido |
| `includes_priority_queue` | boolean | Cola prioritaria |
| `idle_reminder_hours` | integer | Horas idle para enviar reminder (GAP-SUP-07) |
| `auto_close_hours` | integer | Horas idle post-reminder para auto-cierre (GAP-SUP-07) |
| `pause_on_pending` | boolean | Pausar SLA en pending_customer (GAP-SUP-15) |
| `active` | boolean | Politica activa |

### 7.6 Entidad business_hours_schedule (ConfigEntity)

**Archivo**: `web/modules/custom/jaraba_support/src/Entity/BusinessHoursSchedule.php`

**Cierra GAP-SUP-02.**

```php
/**
 * @ConfigEntityType(
 *   id = "business_hours_schedule",
 *   label = @Translation("Business Hours Schedule"),
 *   config_prefix = "business_hours",
 *   admin_permission = "administer support system",
 *   ...
 * )
 */
```

| Propiedad | Tipo | Descripcion |
|-----------|------|-------------|
| `id` | string | ej: `spain_standard`, `institutional_24_7` |
| `label` | string | "Horario Laboral Espana" |
| `timezone` | string | "Europe/Madrid" |
| `schedule` | mapping | `{monday: {start: "09:00", end: "18:00"}, ...}` |
| `holidays` | sequence | `[{date: "2026-01-01", name: "Ano Nuevo"}, ...]` |
| `active` | boolean | Activo |

**Schema YAML** (`jaraba_support.schema.yml`):

```yaml
jaraba_support.business_hours.*:
  type: config_entity
  label: 'Business Hours Schedule'
  mapping:
    id:
      type: string
    label:
      type: label
    timezone:
      type: string
    schedule:
      type: mapping
      mapping:
        monday:
          type: mapping
          mapping:
            start: { type: string }
            end: { type: string }
        tuesday:
          type: mapping
          mapping:
            start: { type: string }
            end: { type: string }
        wednesday:
          type: mapping
          mapping:
            start: { type: string }
            end: { type: string }
        thursday:
          type: mapping
          mapping:
            start: { type: string }
            end: { type: string }
        friday:
          type: mapping
          mapping:
            start: { type: string }
            end: { type: string }
        saturday:
          type: mapping
          mapping:
            start: { type: string }
            end: { type: string }
        sunday:
          type: mapping
          mapping:
            start: { type: string }
            end: { type: string }
    holidays:
      type: sequence
      sequence:
        type: mapping
        mapping:
          date: { type: string }
          name: { type: string }
    active:
      type: boolean
```

### 7.7 Entidad ticket_event_log

**Archivo**: `web/modules/custom/jaraba_support/src/Entity/TicketEventLog.php`

**Append-only**: Sin form handlers edit/delete. AccessHandler deniega update/delete.

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | `integer` (auto) | Si | ID |
| `uuid` | `uuid` | Si | UUID |
| `ticket_id` | `entity_reference` → `support_ticket` | Si | FK al ticket |
| `event_type` | `list_string` | Si | created\|assigned\|status_changed\|priority_changed\|escalated\|sla_warning\|sla_breached\|ai_classified\|ai_responded\|resolved\|closed\|reopened\|merged\|tagged\|watcher_added\|parent_linked |
| `actor_uid` | `entity_reference` → `user` | No | NULL = sistema |
| `actor_type` | `list_string` | Si | customer\|agent\|ai\|system\|eca |
| `old_value` | `string` (255) | No | Valor anterior |
| `new_value` | `string` (255) | No | Valor nuevo |
| `metadata` | `string_long` | No | JSON datos adicionales |
| `ip_address` | `string` (45) | No | IP del actor (GAP-SUP audit) |
| `user_agent` | `string` (255) | No | User-Agent del actor |
| `created` | `created` | Si | Timestamp inmutable |

### 7.8 Entidad response_template

**Archivo**: `web/modules/custom/jaraba_support/src/Entity/ResponseTemplate.php`

**Cierra GAP-SUP-04.**

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | `integer` (auto) | Si | ID |
| `uuid` | `uuid` | Si | UUID |
| `name` | `string` (128) | Si | Nombre visible del template |
| `body` | `text_long` | Si | Cuerpo con placeholders: `{{customer_name}}`, `{{ticket_number}}`, `{{agent_name}}`, `{{tenant_name}}` |
| `category` | `string` (64) | No | Categoria para filtrado |
| `vertical` | `list_string` | No | Vertical especifica o NULL para global |
| `scope` | `list_string` | Si | global\|team\|personal. Default: `personal`. |
| `author_uid` | EntityOwnerTrait | Si | Creador del template |
| `usage_count` | `integer` | Si | Veces usado. Default 0. Incrementa automaticamente. |
| `created` | `created` | Si | Timestamp |
| `changed` | `changed` | Si | Timestamp |

### 7.9 Entidad agent_saved_view

**Archivo**: `web/modules/custom/jaraba_support/src/Entity/AgentSavedView.php`

**Cierra GAP-SUP-12.**

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | `integer` (auto) | Si | ID |
| `uuid` | `uuid` | Si | UUID |
| `name` | `string` (128) | Si | Nombre del view: "Mis tickets urgentes" |
| `filters` | `string_long` | Si | JSON: `{status: ["open"], priority: ["critical", "high"], assignee: "me"}` |
| `sort_field` | `string` (64) | No | Campo de ordenacion |
| `sort_direction` | `list_string` | No | asc\|desc |
| `owner_uid` | EntityOwnerTrait | Si | Agente propietario |
| `scope` | `list_string` | Si | personal\|team\|global |
| `is_default` | `boolean` | Si | Default FALSE. Si TRUE, se carga al abrir el dashboard. |
| `created` | `created` | Si | Timestamp |

### 7.10 Access control handlers con tenant isolation

**Archivo**: `web/modules/custom/jaraba_support/src/SupportTicketAccessControlHandler.php`

Implementa `EntityHandlerInterface` para DI (patron canonico del proyecto):

```php
class SupportTicketAccessControlHandler extends EntityAccessControlHandler
  implements EntityHandlerInterface {

  public function __construct(
    EntityTypeInterface $entity_type,
    private readonly ?TenantContextService $tenantContext = NULL,
  ) {
    parent::__construct($entity_type);
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->has('ecosistema_jaraba_core.tenant_context')
        ? $container->get('ecosistema_jaraba_core.tenant_context')
        : NULL,
    );
  }

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\jaraba_support\Entity\SupportTicket $entity */
    $admin = AccessResult::allowedIfHasPermission($account, 'administer support system');
    if ($admin->isAllowed()) {
      return $admin;
    }

    switch ($operation) {
      case 'view':
        // Tenant users can view their own tickets
        // Tenant admins can view all tickets of their tenant
        // Agents can view assigned tickets
        // Support leads and platform admins can view all
        if ($account->hasPermission('view all support tickets')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('view tenant support tickets') && $this->isSameTenant($entity, $account)) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
        }
        if ($entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowed()->cachePerUser();
        }
        return AccessResult::forbidden();

      case 'update':
        // TENANT-ISOLATION-ACCESS-001: Verify tenant match FIRST
        if (!$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Tenant mismatch');
        }
        if ($account->hasPermission('edit any support ticket')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('edit own support ticket') && $entity->getOwnerId() === (int) $account->id()) {
          return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
        }
        return AccessResult::forbidden();

      case 'delete':
        if (!$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Tenant mismatch');
        }
        return AccessResult::allowedIfHasPermission($account, 'delete support tickets');
    }

    return AccessResult::neutral();
  }

  private function isSameTenant(EntityInterface $entity, AccountInterface $account): bool {
    if (!$this->tenantContext) {
      return TRUE; // Degrade gracefully if service unavailable
    }
    try {
      $userTenant = $this->tenantContext->getCurrentTenant();
      $entityTenantId = $entity->get('tenant_id')->target_id;
      return $userTenant && ((string) $userTenant->id() === (string) $entityTenantId);
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }
}
```

### 7.11 Permisos unificados (RBAC)

**Archivo**: `web/modules/custom/jaraba_support/jaraba_support.permissions.yml`

```yaml
'administer support system':
  title: 'Administer Support System'
  description: 'Full administrative access to support configuration, SLA policies, and all tickets.'
  restrict access: true

'create support ticket':
  title: 'Create support tickets'
  description: 'Create new support tickets.'

'view own support tickets':
  title: 'View own support tickets'
  description: 'View tickets created by the user.'

'view tenant support tickets':
  title: 'View tenant support tickets'
  description: 'View all tickets belonging to the same tenant.'

'view all support tickets':
  title: 'View all support tickets'
  description: 'View tickets from all tenants. For platform admins and support leads.'
  restrict access: true

'edit own support ticket':
  title: 'Edit own support tickets'
  description: 'Edit tickets created by the user (add messages, close).'

'edit any support ticket':
  title: 'Edit any support ticket'
  description: 'Edit any ticket (change status, priority, assignment).'

'delete support tickets':
  title: 'Delete support tickets'
  description: 'Permanently delete support tickets.'
  restrict access: true

'add internal notes':
  title: 'Add internal notes'
  description: 'Add internal notes visible only to agents.'

'change ticket priority':
  title: 'Change ticket priority'
  description: 'Modify the priority level of tickets.'

'change ticket assignment':
  title: 'Change ticket assignment'
  description: 'Reassign tickets to other agents.'

'escalate tickets':
  title: 'Escalate tickets'
  description: 'Escalate tickets to higher support tiers.'

'merge tickets':
  title: 'Merge tickets'
  description: 'Merge duplicate tickets.'

'view support analytics':
  title: 'View support analytics'
  description: 'Access support analytics dashboards and reports.'

'manage response templates':
  title: 'Manage response templates'
  description: 'Create and edit response templates for quick replies.'

'use support agent dashboard':
  title: 'Use support agent dashboard'
  description: 'Access the support agent dashboard and ticket queue.'

'access support overview':
  title: 'Access support overview'
  description: 'View the support tickets admin listing.'
```

### 7.12 Estructura de archivos del modulo

```
web/modules/custom/jaraba_support/
├── jaraba_support.info.yml
├── jaraba_support.module
├── jaraba_support.permissions.yml
├── jaraba_support.routing.yml
├── jaraba_support.services.yml
├── jaraba_support.libraries.yml
├── jaraba_support.links.task.yml
├── jaraba_support.links.menu.yml
├── jaraba_support.links.action.yml
├── jaraba_support.install
│
├── config/
│   ├── install/
│   │   ├── jaraba_support.sla_policy.starter_critical.yml
│   │   ├── jaraba_support.sla_policy.starter_high.yml
│   │   ├── jaraba_support.sla_policy.starter_medium.yml
│   │   ├── jaraba_support.sla_policy.starter_low.yml
│   │   ├── jaraba_support.sla_policy.professional_critical.yml
│   │   ├── ... (16 SLA policies: 4 plans x 4 priorities)
│   │   ├── jaraba_support.business_hours.spain_standard.yml
│   │   ├── jaraba_support.business_hours.extended.yml
│   │   ├── jaraba_support.business_hours.twentyfour_seven.yml
│   │   └── jaraba_support.settings.yml
│   └── schema/
│       └── jaraba_support.schema.yml
│
├── src/
│   ├── Entity/
│   │   ├── SupportTicket.php
│   │   ├── SupportTicketInterface.php
│   │   ├── TicketMessage.php
│   │   ├── TicketMessageInterface.php
│   │   ├── TicketAttachment.php
│   │   ├── TicketEventLog.php
│   │   ├── SlaPolicy.php
│   │   ├── SlaPolicyInterface.php
│   │   ├── BusinessHoursSchedule.php
│   │   ├── ResponseTemplate.php
│   │   ├── AgentSavedView.php
│   │   └── TicketWatcher.php
│   │
│   ├── Service/
│   │   ├── TicketService.php
│   │   ├── TicketAiClassificationService.php
│   │   ├── TicketAiResolutionService.php
│   │   ├── SlaEngineService.php
│   │   ├── BusinessHoursService.php
│   │   ├── TicketRoutingService.php
│   │   ├── AttachmentService.php
│   │   ├── AttachmentScanService.php
│   │   ├── AttachmentUrlService.php
│   │   ├── TicketNotificationService.php
│   │   ├── TicketSummarizationService.php
│   │   ├── TicketMergeService.php
│   │   ├── SupportAnalyticsService.php
│   │   ├── SupportHealthScoreService.php
│   │   ├── CsatSurveyService.php
│   │   ├── TicketStreamService.php
│   │   ├── TicketDeflectionService.php
│   │   └── SupportCronService.php
│   │
│   ├── Agent/
│   │   └── SupportAgentSmartAgent.php
│   │
│   ├── Controller/
│   │   ├── SupportPortalController.php
│   │   ├── TicketDetailController.php
│   │   ├── AgentDashboardController.php
│   │   ├── SupportApiController.php
│   │   ├── SupportStreamController.php
│   │   └── SupportSettingsController.php
│   │
│   ├── Form/
│   │   ├── SupportTicketForm.php          (extends PremiumEntityFormBase)
│   │   ├── TicketMessageForm.php          (extends PremiumEntityFormBase)
│   │   ├── ResponseTemplateForm.php       (extends PremiumEntityFormBase)
│   │   ├── SlaPolicyForm.php              (ConfigEntity form)
│   │   ├── BusinessHoursScheduleForm.php  (ConfigEntity form)
│   │   └── SupportSettingsForm.php        (ConfigFormBase)
│   │
│   ├── Access/
│   │   └── SupportTicketAccessControlHandler.php
│   │
│   ├── SupportTicketListBuilder.php
│   ├── SlaPolicyListBuilder.php
│   └── BusinessHoursScheduleListBuilder.php
│
├── scss/
│   ├── _variables.scss          # Solo aliases a CSS vars del core
│   ├── _portal.scss             # Portal tenant (lista, crear, detalle)
│   ├── _agent-dashboard.scss    # Panel de agente
│   ├── _ticket-detail.scss      # Vista detalle ticket
│   ├── _timeline.scss           # Hilo conversacional
│   ├── _sidebar-context.scss    # Sidebar de contexto
│   ├── _sla-indicators.scss     # Indicadores SLA
│   ├── _status-badges.scss      # Badges de estado y prioridad
│   ├── _attachment-preview.scss # Preview de adjuntos
│   └── main.scss                # @use de todos los parciales
│
├── css/
│   └── jaraba-support.css       # Compilado (no editar)
│
├── js/
│   ├── support-portal.js        # Portal tenant interactivity
│   ├── ticket-detail.js         # Detalle ticket + SSE
│   ├── agent-dashboard.js       # Dashboard agente + SSE
│   ├── ticket-create.js         # Formulario creacion con deflection
│   ├── attachment-upload.js     # Drag-and-drop upload
│   └── grapesjs-jaraba-support-blocks.js  # Bloque GrapesJS
│
├── templates/
│   ├── support-portal.html.twig
│   ├── support-ticket-detail.html.twig
│   ├── support-agent-dashboard.html.twig
│   ├── support-ticket-create-modal.html.twig
│   └── partials/
│       ├── _support-ticket-card.html.twig
│       ├── _support-ticket-timeline.html.twig
│       ├── _support-message-bubble.html.twig
│       ├── _support-sidebar-context.html.twig
│       ├── _support-sla-indicator.html.twig
│       ├── _support-status-badge.html.twig
│       ├── _support-priority-badge.html.twig
│       ├── _support-csat-survey.html.twig
│       ├── _support-deflection-results.html.twig
│       ├── _support-agent-queue-item.html.twig
│       └── _support-analytics-kpi.html.twig
│
├── images/
│   └── icons/
│       └── support/
│           ├── ticket.svg
│           ├── sla-clock.svg
│           ├── agent-headset.svg
│           ├── merge.svg
│           ├── escalate.svg
│           └── satisfaction.svg
│
├── tests/
│   └── src/
│       └── Unit/
│           ├── Service/
│           │   ├── TicketServiceTest.php
│           │   ├── SlaEngineServiceTest.php
│           │   ├── BusinessHoursServiceTest.php
│           │   ├── TicketMergeServiceTest.php
│           │   └── AttachmentUrlServiceTest.php
│           └── Entity/
│               └── SupportTicketTest.php
│
└── package.json                 # Dart Sass compilation config
```

---

## 8. Servicios — Diseno Detallado

### 8.1 TicketService — CRUD y lifecycle

**Archivo**: `src/Service/TicketService.php`

Responsabilidades:
- Crear ticket con validacion de campos
- Transiciones de estado con validacion de state machine
- Asignacion de agentes
- Calculo de ticket_number
- Emision de eventos para ECA y notificaciones

```php
class TicketService {
  // Status transitions state machine
  private const VALID_TRANSITIONS = [
    'new' => ['ai_handling', 'open'],
    'ai_handling' => ['resolved', 'open'],
    'open' => ['pending_customer', 'pending_internal', 'escalated', 'resolved', 'merged'],
    'pending_customer' => ['open', 'closed'],
    'pending_internal' => ['open', 'escalated'],
    'escalated' => ['open', 'resolved'],
    'resolved' => ['closed', 'reopened'],
    'closed' => ['reopened'],
    'reopened' => ['open', 'pending_customer', 'escalated', 'resolved'],
  ];

  public function createTicket(array $data, AccountInterface $reporter): SupportTicketInterface;
  public function transitionStatus(SupportTicketInterface $ticket, string $newStatus, ?int $actorUid = NULL): void;
  public function assignAgent(SupportTicketInterface $ticket, int $agentUid): void;
  public function resolveTicket(SupportTicketInterface $ticket, string $notes, ?int $actorUid = NULL): void;
  public function reopenTicket(SupportTicketInterface $ticket, ?int $actorUid = NULL): void;
  public function addMessage(SupportTicketInterface $ticket, string $body, string $authorType, array $options = []): TicketMessage;
  public function getTicketsForTenant(int $tenantId, array $filters = [], int $limit = 50, int $offset = 0): array;
  public function getTicketsForAgent(int $agentUid, array $filters = []): array;
}
```

### 8.2 TicketAiClassificationService — Pipeline IA

**Archivo**: `src/Service/TicketAiClassificationService.php`

Implementa el pipeline de 9 pasos de la spec 178. Usa `ModelRouterService` para seleccionar modelo por complejidad.

```php
class TicketAiClassificationService {
  public function classify(SupportTicketInterface $ticket): array {
    // Step 1: Embedding del texto
    $embedding = $this->getEmbedding($ticket->get('subject')->value . ' ' . $ticket->get('description')->value);

    // Step 2: Busqueda semantica en KB
    $kbResults = $this->searchKnowledgeBase($embedding, $ticket->get('tenant_id')->target_id);

    // Step 3: Busqueda de tickets similares
    $similarTickets = $this->searchSimilarTickets($embedding, $ticket->get('tenant_id')->target_id);

    // Step 4: Clasificacion multi-label via Claude API
    $classification = $this->callClassificationModel($ticket, $kbResults, $similarTickets);
    // Returns: {category, subcategory, priority, sentiment, urgency, confidence, vertical}

    // Step 5: Guardar resultado
    $ticket->set('ai_classification', json_encode($classification));
    $ticket->set('priority', $classification['priority']);
    if (empty($ticket->get('vertical')->value)) {
      $ticket->set('vertical', $classification['vertical']);
    }

    return $classification;
  }

  // Model routing: Haiku for simple tickets, Sonnet for complex
  private function getModelTier(string $text): string {
    return strlen($text) < 200 ? 'fast' : 'balanced';
  }
}
```

### 8.3 TicketAiResolutionService — Resolucion autonoma

**Archivo**: `src/Service/TicketAiResolutionService.php`

```php
class TicketAiResolutionService {
  private const CONFIDENCE_THRESHOLD_AUTO = 0.85;
  private const CONFIDENCE_THRESHOLD_SUGGEST = 0.65;

  public function attemptResolution(SupportTicketInterface $ticket, array $kbResults, array $similarTickets): array {
    // Generate grounded response with citations
    $response = $this->generateGroundedResponse($ticket, $kbResults, $similarTickets);

    $ticket->set('ai_resolution_attempted', TRUE);
    $ticket->set('ai_suggested_solution', $response['solution']);

    if ($response['confidence'] >= self::CONFIDENCE_THRESHOLD_AUTO) {
      // High confidence — offer auto-resolution
      return ['action' => 'auto_resolve', 'solution' => $response['solution'], 'sources' => $response['sources'], 'confidence' => $response['confidence']];
    }
    if ($response['confidence'] >= self::CONFIDENCE_THRESHOLD_SUGGEST) {
      // Medium confidence — suggest but create ticket
      return ['action' => 'suggest', 'solution' => $response['solution'], 'sources' => $response['sources'], 'confidence' => $response['confidence']];
    }
    // Low confidence — route to human
    return ['action' => 'route_human', 'solution' => NULL, 'confidence' => $response['confidence']];
  }

  // CRITICAL: If sentiment is angry/frustrated, ALWAYS route to human
  // regardless of confidence
  private function shouldForceHumanRouting(array $classification): bool {
    return in_array($classification['sentiment'] ?? '', ['angry', 'frustrated']);
  }
}
```

### 8.4 SlaEngineService — Gestion de SLAs

**Archivo**: `src/Service/SlaEngineService.php`

```php
class SlaEngineService {
  public function calculateDeadlines(SupportTicketInterface $ticket): void;
  public function checkSlaStatus(SupportTicketInterface $ticket): string; // 'ok'|'warning'|'breached'
  public function pauseSla(SupportTicketInterface $ticket): void;         // GAP-SUP-15: pause on pending
  public function resumeSla(SupportTicketInterface $ticket): void;
  public function processSlaCron(): void;                                  // Check all active tickets

  // Uses BusinessHoursService for accurate time calculation
  private function calculateBusinessHoursDeadline(\DateTimeInterface $from, int $hours, string $scheduleId): \DateTimeImmutable;
}
```

### 8.5 BusinessHoursService — Calendario laboral

**Archivo**: `src/Service/BusinessHoursService.php`

**Cierra GAP-SUP-02.**

```php
class BusinessHoursService {
  public function isWithinBusinessHours(string $scheduleId, ?\DateTimeInterface $at = NULL): bool;
  public function getNextBusinessStart(string $scheduleId, \DateTimeInterface $from): \DateTimeImmutable;
  public function calculateBusinessMinutes(string $scheduleId, \DateTimeInterface $start, \DateTimeInterface $end): int;
  public function addBusinessHours(string $scheduleId, \DateTimeInterface $from, int $hours): \DateTimeImmutable;
  public function isHoliday(string $scheduleId, \DateTimeInterface $date): bool;
}
```

### 8.6 TicketRoutingService — Enrutamiento inteligente

**Archivo**: `src/Service/TicketRoutingService.php`

```php
class TicketRoutingService {
  public function routeTicket(SupportTicketInterface $ticket): ?int {
    // 1. Check vertical match
    // 2. Check agent availability and current load
    // 3. Check agent skills/specialization
    // 4. Round-robin within qualifying agents
    // Returns: agent UID or NULL if no agent available
  }

  public function getNextUrgentTicket(int $agentUid): ?SupportTicketInterface {
    // "Take Next" button — most urgent compatible ticket
    // Priority: SLA breach → SLA warning → Priority critical → Queue order
  }
}
```

### 8.7 AttachmentService — Gestion de adjuntos

**Archivo**: `src/Service/AttachmentService.php`

```php
class AttachmentService {
  // MIME type whitelist (never rely on extension alone)
  private const ALLOWED_MIMES = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif',
    'application/pdf',
    'text/plain', 'text/csv',
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/zip',
    'video/mp4', 'video/webm',
  ];

  // Max sizes by plan
  private const MAX_TOTAL_STARTER = 25 * 1024 * 1024;      // 25 MB
  private const MAX_TOTAL_ENTERPRISE = 100 * 1024 * 1024;   // 100 MB
  private const MAX_FILES_PER_TICKET = 10;

  public function uploadAttachment(SupportTicketInterface $ticket, UploadedFile $file, AccountInterface $uploader): TicketAttachment;
  public function validateFile(UploadedFile $file, string $planTier): array; // Returns validation errors
  public function getStoragePath(SupportTicketInterface $ticket): string;     // private://support_attachments/{tenant_id}/{ticket_id}/
}
```

### 8.8 AttachmentScanService — Virus scanning

**Archivo**: `src/Service/AttachmentScanService.php`

**Cierra GAP-SUP-08.**

```php
class AttachmentScanService {
  public function scanAttachment(TicketAttachment $attachment): string {
    // Returns: 'clean'|'infected'|'error'
    // Uses ClamAV socket or TCP connection
    // If ClamAV unavailable, marks as 'error' (never serves unscanned files)
  }

  public function processPendingScans(): void {
    // Cron: scan all attachments with scan_status = 'pending'
  }
}
```

### 8.9 AttachmentUrlService — URLs pre-firmadas

**Archivo**: `src/Service/AttachmentUrlService.php`

**Cierra GAP-SUP-09.**

```php
class AttachmentUrlService {
  private const URL_EXPIRY_SECONDS = 3600; // 1 hour

  public function generateSignedUrl(TicketAttachment $attachment, AccountInterface $requester): string {
    // HMAC-SHA256 signature with: attachment_id + tenant_id + requester_uid + expiry timestamp
    // Only serves files with scan_status = 'clean'
    // Pattern: /support/attachments/{token}
    $payload = $attachment->id() . ':' . $tenantId . ':' . $requester->id() . ':' . $expiry;
    $signature = hash_hmac('sha256', $payload, $this->getSecretKey());
    return Url::fromRoute('jaraba_support.attachment.download', [
      'token' => base64_encode($payload . ':' . $signature),
    ])->toString();
  }

  public function validateAndServe(string $token, AccountInterface $requester): ?BinaryFileResponse;
}
```

### 8.10 TicketNotificationService — Notificaciones multicanal

**Archivo**: `src/Service/TicketNotificationService.php`

```php
class TicketNotificationService {
  public function notifyTicketCreated(SupportTicketInterface $ticket): void;
  public function notifyTicketAssigned(SupportTicketInterface $ticket, int $agentUid): void;
  public function notifyNewMessage(SupportTicketInterface $ticket, TicketMessage $message): void;
  public function notifySlaWarning(SupportTicketInterface $ticket, int $minutesRemaining): void;
  public function notifySlaBreached(SupportTicketInterface $ticket): void;
  public function notifyTicketResolved(SupportTicketInterface $ticket): void;

  // Uses existing notification infrastructure (email + push + in-app)
  // Channel selection based on user preferences
}
```

### 8.11 TicketSummarizationService — Resumenes IA

**Archivo**: `src/Service/TicketSummarizationService.php`

**Cierra GAP-SUP-11.**

```php
class TicketSummarizationService {
  // Triggered when:
  // a) Ticket is reassigned
  // b) Ticket has > 5 messages
  // c) Ticket is escalated
  public function summarize(SupportTicketInterface $ticket): string {
    $messages = $this->loadMessages($ticket);
    if (count($messages) < 3) {
      return ''; // Not enough content to summarize
    }

    // Uses Haiku model (fast tier) for cost efficiency
    $summary = $this->callSummarizationModel($messages);

    // Save as internal note
    $this->ticketService->addMessage($ticket, $summary, 'ai', [
      'is_internal_note' => TRUE,
      'is_ai_generated' => TRUE,
    ]);

    return $summary;
  }
}
```

### 8.12 TicketMergeService — Fusion de tickets

**Archivo**: `src/Service/TicketMergeService.php`

**Cierra GAP-SUP-01.**

```php
class TicketMergeService {
  public function merge(SupportTicketInterface $canonical, SupportTicketInterface $duplicate): void {
    // 1. Move all messages from duplicate to canonical (update ticket_id FK)
    // 2. Move all attachments from duplicate to canonical
    // 3. Set duplicate.merged_into_id = canonical.id
    // 4. Set duplicate.status = 'merged'
    // 5. Add canonical.merged_from_ids += [duplicate.id]
    // 6. Add internal note to canonical: "Ticket #X merged"
    // 7. Log event on both tickets
    // 8. Notify duplicate's reporter
  }
}
```

### 8.13 SupportAnalyticsService — Metricas y KPIs

**Archivo**: `src/Service/SupportAnalyticsService.php`

```php
class SupportAnalyticsService {
  public function getOverviewStats(array $filters = []): array;
  public function getVolumeByPeriod(string $period, \DateTimeInterface $from, \DateTimeInterface $to): array;
  public function getAiResolutionRate(array $filters = []): float;
  public function getMttr(array $filters = []): float;        // Mean Time To Resolution
  public function getFirstResponseTime(array $filters = []): float;
  public function getSlaComplianceRate(array $filters = []): float;
  public function getCsatScore(array $filters = []): float;
  public function getCesScore(array $filters = []): float;     // GAP-SUP-10
  public function getTopCategories(int $limit = 10): array;
  public function getAgentPerformance(int $agentUid): array;
  public function getDeflectionRate(array $filters = []): float;
  public function getTrendData(string $metric, string $period, int $periods = 12): array;
}
```

### 8.14 SupportHealthScoreService — Integracion CS

**Archivo**: `src/Service/SupportHealthScoreService.php`

```php
class SupportHealthScoreService {
  // Calculates the support component of the Customer Success health score
  // Weights from spec 178 Section 7.3:
  // - 0 tickets in 30d: +5
  // - 4+ tickets in 30d: -10
  // - Critical unresolved > 24h: -15
  // - SLA breach: -20
  // - CSAT < 3.0 last 3 tickets: -10
  // - Billing/cancellation ticket: trigger churn alert

  public function calculateSupportScore(int $tenantId): int;
  public function triggerChurnAlertIfNeeded(SupportTicketInterface $ticket): void;
}
```

### 8.15 CsatSurveyService — Encuestas de satisfaccion

**Archivo**: `src/Service/CsatSurveyService.php`

```php
class CsatSurveyService {
  public function scheduleSurvey(SupportTicketInterface $ticket): void;      // Schedule for 24h after resolution
  public function submitSatisfaction(SupportTicketInterface $ticket, int $csatRating, ?int $cesScore = NULL, ?string $comment = NULL): void;
  public function processLowSatisfaction(SupportTicketInterface $ticket): void; // SUP-012: notify CS manager
  public function getSurveyPendingTickets(): array;
}
```

### 8.16 TicketStreamService — SSE real-time

**Archivo**: `src/Service/TicketStreamService.php`

**Cierra GAP-SUP-13.**

```php
class TicketStreamService {
  // Manages SSE event generation for real-time dashboard updates
  // Events: ticket_created, ticket_status_changed, ticket_assigned,
  //         sla_warning, new_message, agent_collision

  public function getEventsForAgent(int $agentUid, ?string $lastEventId = NULL): \Generator;
  public function getEventsForTenant(int $tenantId, int $userId, ?string $lastEventId = NULL): \Generator;
  public function publishEvent(string $eventType, array $data): void;

  // Collision detection (GAP-SUP-03):
  public function registerAgentViewing(int $ticketId, int $agentUid): void;
  public function unregisterAgentViewing(int $ticketId, int $agentUid): void;
  public function getAgentsViewingTicket(int $ticketId): array;
}
```

### 8.17 SupportAgentSmartAgent — Agente IA Gen 2

**Archivo**: `src/Agent/SupportAgentSmartAgent.php`

Extiende `SmartBaseAgent` (AGENT-GEN2-PATTERN-001):

```php
class SupportAgentSmartAgent extends SmartBaseAgent {
  // SMART-AGENT-CONSTRUCTOR-001: 10 constructor args
  public function __construct(
    $aiProvider, $configFactory, $logger,
    $brandVoice, $observability, $modelRouter,
    $promptBuilder = NULL, $toolRegistry = NULL,
    $providerFallback = NULL, $contextWindowManager = NULL,
  ) {
    parent::__construct(...);
    $this->setModelRouter($modelRouter);
    // Conditional setters for optional services
  }

  protected function doExecute(string $input, array $context = []): array {
    // 1. Classify the support request
    // 2. Search KB with grounding
    // 3. Generate response with strict grounding + citations
    // 4. Return with confidence and sources
  }

  public function getAgentId(): string { return 'support_agent'; }
  public function getVertical(): string { return 'platform'; }
}
```

### 8.18 services.yml completo

**Archivo**: `web/modules/custom/jaraba_support/jaraba_support.services.yml`

```yaml
services:
  # Logger
  logger.channel.jaraba_support:
    parent: logger.channel_base
    arguments: ['jaraba_support']

  # Core services
  jaraba_support.ticket:
    class: Drupal\jaraba_support\Service\TicketService
    arguments:
      - '@entity_type.manager'
      - '@current_user'
      - '@logger.channel.jaraba_support'
      - '@database'
      - '@?ecosistema_jaraba_core.tenant_context'
      - '@event_dispatcher'

  jaraba_support.ai_classification:
    class: Drupal\jaraba_support\Service\TicketAiClassificationService
    arguments:
      - '@ai.provider'
      - '@logger.channel.jaraba_support'
      - '@?jaraba_rag.qdrant_client'
      - '@?jaraba_ai_agents.model_router'
      - '@config.factory'

  jaraba_support.ai_resolution:
    class: Drupal\jaraba_support\Service\TicketAiResolutionService
    arguments:
      - '@ai.provider'
      - '@logger.channel.jaraba_support'
      - '@?jaraba_rag.qdrant_client'
      - '@?jaraba_ai_agents.model_router'
      - '@?jaraba_ai_agents.observability'
      - '@config.factory'

  jaraba_support.sla_engine:
    class: Drupal\jaraba_support\Service\SlaEngineService
    arguments:
      - '@entity_type.manager'
      - '@logger.channel.jaraba_support'
      - '@jaraba_support.business_hours'
      - '@jaraba_support.notification'
      - '@?jaraba_support.health_score'

  jaraba_support.business_hours:
    class: Drupal\jaraba_support\Service\BusinessHoursService
    arguments:
      - '@entity_type.manager'
      - '@logger.channel.jaraba_support'

  jaraba_support.routing:
    class: Drupal\jaraba_support\Service\TicketRoutingService
    arguments:
      - '@entity_type.manager'
      - '@current_user'
      - '@logger.channel.jaraba_support'
      - '@?ecosistema_jaraba_core.tenant_context'

  jaraba_support.attachment:
    class: Drupal\jaraba_support\Service\AttachmentService
    arguments:
      - '@entity_type.manager'
      - '@file_system'
      - '@logger.channel.jaraba_support'
      - '@?jaraba_billing.plan_resolver'

  jaraba_support.attachment_scan:
    class: Drupal\jaraba_support\Service\AttachmentScanService
    arguments:
      - '@entity_type.manager'
      - '@logger.channel.jaraba_support'
      - '@config.factory'

  jaraba_support.attachment_url:
    class: Drupal\jaraba_support\Service\AttachmentUrlService
    arguments:
      - '@entity_type.manager'
      - '@logger.channel.jaraba_support'
      - '@config.factory'

  jaraba_support.notification:
    class: Drupal\jaraba_support\Service\TicketNotificationService
    arguments:
      - '@logger.channel.jaraba_support'
      - '@plugin.manager.mail'
      - '@?ecosistema_jaraba_core.notification'

  jaraba_support.summarization:
    class: Drupal\jaraba_support\Service\TicketSummarizationService
    arguments:
      - '@ai.provider'
      - '@logger.channel.jaraba_support'
      - '@jaraba_support.ticket'
      - '@?jaraba_ai_agents.model_router'

  jaraba_support.merge:
    class: Drupal\jaraba_support\Service\TicketMergeService
    arguments:
      - '@entity_type.manager'
      - '@logger.channel.jaraba_support'
      - '@jaraba_support.ticket'

  jaraba_support.analytics:
    class: Drupal\jaraba_support\Service\SupportAnalyticsService
    arguments:
      - '@database'
      - '@logger.channel.jaraba_support'

  jaraba_support.health_score:
    class: Drupal\jaraba_support\Service\SupportHealthScoreService
    arguments:
      - '@database'
      - '@logger.channel.jaraba_support'
      - '@?ecosistema_jaraba_core.customer_success'

  jaraba_support.csat:
    class: Drupal\jaraba_support\Service\CsatSurveyService
    arguments:
      - '@entity_type.manager'
      - '@logger.channel.jaraba_support'
      - '@jaraba_support.notification'

  jaraba_support.stream:
    class: Drupal\jaraba_support\Service\TicketStreamService
    arguments:
      - '@database'
      - '@logger.channel.jaraba_support'

  jaraba_support.deflection:
    class: Drupal\jaraba_support\Service\TicketDeflectionService
    arguments:
      - '@?jaraba_rag.qdrant_client'
      - '@logger.channel.jaraba_support'
      - '@?ecosistema_jaraba_core.tenant_context'

  jaraba_support.cron:
    class: Drupal\jaraba_support\Service\SupportCronService
    arguments:
      - '@jaraba_support.sla_engine'
      - '@jaraba_support.ticket'
      - '@jaraba_support.notification'
      - '@jaraba_support.attachment_scan'
      - '@logger.channel.jaraba_support'

  # Gen 2 Smart Agent
  jaraba_support.smart_support_agent:
    class: Drupal\jaraba_support\Agent\SupportAgentSmartAgent
    arguments:
      - '@ai.provider'
      - '@config.factory'
      - '@logger.channel.jaraba_support'
      - '@?jaraba_ai_agents.tenant_brand_voice'
      - '@?jaraba_ai_agents.observability'
      - '@?jaraba_ai_agents.model_router'
      - '@?ecosistema_jaraba_core.unified_prompt_builder'
      - '@?jaraba_ai_agents.tool_registry'
      - '@?jaraba_ai_agents.provider_fallback'
      - '@?jaraba_ai_agents.context_window_manager'
```

---

## 9. Controladores y Rutas

### 9.1 SupportPortalController — Portal frontend tenant

**Archivo**: `src/Controller/SupportPortalController.php`

```php
class SupportPortalController extends ControllerBase {
  // DI: PHP 8.1 promoted properties
  public function __construct(
    protected TicketService $ticketService,
    protected TicketDeflectionService $deflectionService,
    protected SlaEngineService $slaEngine,
    protected RequestStack $requestStack,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_support.ticket'),
      $container->get('jaraba_support.deflection'),
      $container->get('jaraba_support.sla_engine'),
      $container->get('request_stack'),
      $container->has('ecosistema_jaraba_core.tenant_context')
        ? $container->get('ecosistema_jaraba_core.tenant_context')
        : NULL,
    );
  }

  // GET /soporte — My Tickets list
  public function index(): array {
    $tenant = $this->tenantContext?->getCurrentTenant();
    $userId = $this->currentUser()->id();
    $tickets = $this->ticketService->getTicketsForTenant(
      $tenant?->id(), ['reporter_uid' => $userId]
    );

    return [
      '#theme' => 'support_portal',
      '#tickets' => $this->formatTicketsForTemplate($tickets),
      '#stats' => $this->getQuickStats($userId),
      '#attached' => [
        'library' => [
          'jaraba_support/support-portal',
          'ecosistema_jaraba_theme/bundle-support',
        ],
      ],
      '#cache' => [
        'tags' => ['support_ticket_list'],
        'contexts' => ['user', 'languages'],
        'max-age' => 60,
      ],
    ];
  }

  // GET /soporte/crear — Create ticket (renders in slide-panel if XHR)
  public function createForm(Request $request): Response|array {
    // SLIDE-PANEL-RENDER-001: Check if slide-panel request
    if ($this->isSlidePanelRequest($request)) {
      $form = $this->formBuilder()->getForm(SupportTicketForm::class);
      $form['#action'] = $request->getRequestUri();
      $html = (string) \Drupal::service('renderer')->renderPlain($form);
      return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
    // Full page render
    return $this->formBuilder()->getForm(SupportTicketForm::class);
  }
}
```

### 9.7 Tabla de rutas completa

**Archivo**: `web/modules/custom/jaraba_support/jaraba_support.routing.yml`

```yaml
# ═══════════════════════════════════════
# FRONTEND ROUTES (_admin_route: FALSE)
# ═══════════════════════════════════════

jaraba_support.portal:
  path: '/soporte'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\SupportPortalController::index'
    _title: 'Support'
  requirements:
    _permission: 'view own support tickets'
  options:
    _admin_route: FALSE

jaraba_support.portal.create:
  path: '/soporte/crear'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\SupportPortalController::createForm'
    _title: 'Create Ticket'
  requirements:
    _permission: 'create support ticket'
  options:
    _admin_route: FALSE

jaraba_support.ticket.detail:
  path: '/soporte/ticket/{support_ticket}'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\TicketDetailController::view'
    _title_callback: '\Drupal\jaraba_support\Controller\TicketDetailController::title'
  requirements:
    _entity_access: 'support_ticket.view'
  options:
    _admin_route: FALSE
    parameters:
      support_ticket:
        type: entity:support_ticket

jaraba_support.agent.dashboard:
  path: '/soporte/agente'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\AgentDashboardController::dashboard'
    _title: 'Agent Dashboard'
  requirements:
    _permission: 'use support agent dashboard'
  options:
    _admin_route: FALSE

jaraba_support.agent.ticket:
  path: '/soporte/agente/ticket/{support_ticket}'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\AgentDashboardController::ticketView'
    _title_callback: '\Drupal\jaraba_support\Controller\AgentDashboardController::ticketTitle'
  requirements:
    _permission: 'use support agent dashboard'
  options:
    _admin_route: FALSE
    parameters:
      support_ticket:
        type: entity:support_ticket

# ═══════════════════════════════════════
# ADMIN ROUTES
# ═══════════════════════════════════════

entity.support_ticket.collection:
  path: '/admin/content/support-tickets'
  defaults:
    _entity_list: 'support_ticket'
    _title: 'Support Tickets'
  requirements:
    _permission: 'access support overview'

entity.support_ticket.settings:
  path: '/admin/structure/support-ticket/settings'
  defaults:
    _form: '\Drupal\jaraba_support\Form\SupportSettingsForm'
    _title: 'Support Ticket Settings'
  requirements:
    _permission: 'administer support system'

jaraba_support.admin.sla_policies:
  path: '/admin/config/support/sla-policies'
  defaults:
    _entity_list: 'sla_policy'
    _title: 'SLA Policies'
  requirements:
    _permission: 'administer support system'

jaraba_support.admin.business_hours:
  path: '/admin/config/support/business-hours'
  defaults:
    _entity_list: 'business_hours_schedule'
    _title: 'Business Hours'
  requirements:
    _permission: 'administer support system'

# ═══════════════════════════════════════
# API ROUTES (JSON + CSRF)
# ═══════════════════════════════════════

jaraba_support.api.tickets.create:
  path: '/api/v1/support/tickets'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\SupportApiController::createTicket'
  methods: [POST]
  requirements:
    _permission: 'create support ticket'
    _csrf_request_header_token: 'TRUE'

jaraba_support.api.tickets.list:
  path: '/api/v1/support/tickets'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\SupportApiController::listTickets'
  methods: [GET]
  requirements:
    _permission: 'view own support tickets'
    _format: 'json'

# ... (remaining 16 API routes following same pattern)

# ═══════════════════════════════════════
# SSE STREAM ROUTE
# ═══════════════════════════════════════

jaraba_support.stream:
  path: '/api/v1/support/stream'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\SupportStreamController::stream'
  methods: [GET]
  requirements:
    _permission: 'view own support tickets'

# ═══════════════════════════════════════
# ATTACHMENT DOWNLOAD (signed URL)
# ═══════════════════════════════════════

jaraba_support.attachment.download:
  path: '/support/attachments/{token}'
  defaults:
    _controller: '\Drupal\jaraba_support\Controller\SupportApiController::downloadAttachment'
  requirements:
    _access: 'TRUE'  # Validated via HMAC token, not Drupal permissions
```

---

## 10. Arquitectura Frontend

### 10.1 Zero Region Policy — Paginas limpias

Todas las paginas frontend del modulo de soporte siguen el patron zero-region:

- **Sin `page.content` ni bloques heredados**
- **Header, navegacion y footer propios del tema** via `{% include %}` de parciales
- **Layout full-width pensado para movil**
- **Body classes via `hook_preprocess_html()`** (no `attributes.addClass()`)
- **Theme settings configurables desde UI de Drupal** sin tocar codigo

### 10.2 Templates Twig de pagina

**`page--support.html.twig`** — Template compartido para todas las rutas `/soporte/*`:

```twig
{#
 * page--support.html.twig
 *
 * PROPOSITO: Pagina frontend limpia para el portal de soporte.
 * Sin sidebar de admin, sin regiones Drupal, full-width responsive.
 * PATRON: Zero-region con {% include %} de parciales del tema.
 #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('jaraba_support/support-portal') }}
{{ attach_library('ecosistema_jaraba_theme/bundle-support') }}

<!DOCTYPE html>
<html{{ html_attributes }}>
<head>
  <head-placeholder token="{{ placeholder_token }}">
  <title>{{ head_title|safe_join(' | ') }}</title>
  <css-placeholder token="{{ placeholder_token }}">
  <js-placeholder token="{{ placeholder_token }}">
</head>

<body{{ attributes }}>
  <a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Skip to main content{% endtrans %}
  </a>

  {# HEADER — Partial reutilizable del tema #}
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    logged_in: logged_in,
    theme_settings: theme_settings|default({})
  } %}

  {# MAIN — Full-width #}
  <main id="main-content" class="support-main">
    <div class="support-wrapper">
      {{ page.content }}
    </div>
  </main>

  {# FOOTER — Partial reutilizable del tema #}
  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    theme_settings: theme_settings|default({})
  } %}

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

### 10.3 Templates Twig parciales

**`_support-ticket-card.html.twig`** — Tarjeta de ticket para listas:

```twig
{# _support-ticket-card.html.twig
 * Tarjeta de ticket para portal de soporte.
 * Variables: ticket (associative array), show_sla (boolean)
 #}
{% set t = ticket|default({}) %}
{% set status = t.status|default('new') %}
{% set priority = t.priority|default('medium') %}

<article class="support-ticket-card support-ticket-card--{{ status }} support-ticket-card--{{ priority }}"
         data-ticket-id="{{ t.id }}"
         role="article"
         aria-label="{% trans %}Ticket{% endtrans %} {{ t.ticket_number }}">

  <div class="support-ticket-card__priority-strip"></div>

  <div class="support-ticket-card__content">
    <div class="support-ticket-card__header">
      <span class="support-ticket-card__number">{{ t.ticket_number }}</span>
      {% include '@jaraba_support/partials/_support-priority-badge.html.twig' with { priority: priority } only %}
      {% include '@jaraba_support/partials/_support-status-badge.html.twig' with { status: status } only %}
    </div>

    <h3 class="support-ticket-card__subject">
      <a href="{{ t.url }}">{{ t.subject }}</a>
    </h3>

    <div class="support-ticket-card__meta">
      <span class="support-ticket-card__date">
        {{ jaraba_icon('ui', 'clock', { size: '14px' }) }}
        {{ t.updated_at|default(t.created_at) }}
      </span>

      {% if t.assignee_name %}
        <span class="support-ticket-card__assignee">
          {{ jaraba_icon('support', 'agent-headset', { size: '14px' }) }}
          {{ t.assignee_name }}
        </span>
      {% endif %}

      {% if t.has_new_response %}
        <span class="support-ticket-card__new-badge">
          {% trans %}New response{% endtrans %}
        </span>
      {% endif %}
    </div>

    {% if show_sla|default(false) and t.sla_status %}
      {% include '@jaraba_support/partials/_support-sla-indicator.html.twig' with {
        sla_status: t.sla_status,
        sla_remaining: t.sla_remaining,
      } only %}
    {% endif %}
  </div>

</article>
```

**`_support-status-badge.html.twig`**:

```twig
{# Status badge — color-coded #}
{% set status_labels = {
  'new': 'New'|t,
  'ai_handling': 'AI Processing'|t,
  'open': 'Open'|t,
  'pending_customer': 'Pending'|t,
  'pending_internal': 'Internal Review'|t,
  'escalated': 'Escalated'|t,
  'resolved': 'Resolved'|t,
  'closed': 'Closed'|t,
  'reopened': 'Reopened'|t,
  'merged': 'Merged'|t,
} %}
<span class="support-status-badge support-status-badge--{{ status }}">
  {{ status_labels[status]|default(status) }}
</span>
```

**`_support-sla-indicator.html.twig`**:

```twig
{# SLA countdown indicator #}
{% set sla_class = 'ok' %}
{% if sla_remaining < 25 %}
  {% set sla_class = 'danger' %}
{% elseif sla_remaining < 50 %}
  {% set sla_class = 'warning' %}
{% endif %}

<div class="support-sla-indicator support-sla-indicator--{{ sla_class }}"
     aria-label="{% trans %}SLA status{% endtrans %}">
  {{ jaraba_icon('support', 'sla-clock', { size: '14px' }) }}
  <div class="support-sla-indicator__bar">
    <div class="support-sla-indicator__fill" style="width: {{ sla_remaining }}%"></div>
  </div>
  <span class="support-sla-indicator__text">{{ sla_remaining }}%</span>
</div>
```

### 10.4 Body classes via hook_preprocess_html()

```php
// In jaraba_support.module
function jaraba_support_preprocess_html(array &$variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName() ?? '';

  if (!str_starts_with($route_name, 'jaraba_support.')) {
    return;
  }

  $variables['attributes']['class'][] = 'page-support';
  $variables['attributes']['class'][] = 'page--clean-layout';

  if ($route_name === 'jaraba_support.portal') {
    $variables['attributes']['class'][] = 'page-support-portal';
  }
  if ($route_name === 'jaraba_support.ticket.detail') {
    $variables['attributes']['class'][] = 'page-support-ticket-detail';
  }
  if ($route_name === 'jaraba_support.agent.dashboard') {
    $variables['attributes']['class'][] = 'page-agent-dashboard';
  }
}
```

### 10.5 hook_preprocess_page() para variables de theme

```php
function jaraba_support_preprocess_page(array &$variables): void {
  $route_name = \Drupal::routeMatch()->getRouteName() ?? '';

  if (!str_starts_with($route_name, 'jaraba_support.')) {
    return;
  }

  // Replicate theme_settings for partials (standard pattern)
  $themeHandler = \Drupal::service('theme_handler');
  $activeTheme = $themeHandler->getDefault();
  $themeConfig = \Drupal::config($activeTheme . '.settings');
  $variables['theme_settings'] = $themeConfig->get() ?: [];
  $variables['site_name'] = \Drupal::config('system.site')->get('name') ?: 'Jaraba Impact Platform';
  $variables['logged_in'] = \Drupal::currentUser()->isAuthenticated();
}
```

### 10.6 SCSS: Federated Design Tokens

**`jaraba_support/scss/_variables.scss`** — SOLO aliases, nunca define `$ej-*`:

```scss
// jaraba_support/scss/_variables.scss
// SOLO consume CSS custom properties del core.
// NUNCA define variables $ej-* propias.

// Status colors (consumen --ej-*)
$support-status-new:              var(--ej-color-secondary);     // turquesa
$support-status-ai-handling:      var(--ej-color-secondary);     // turquesa
$support-status-open:             var(--ej-color-success);       // verde
$support-status-pending:          var(--ej-color-warning);       // amarillo
$support-status-escalated:        var(--ej-color-danger);        // rojo
$support-status-resolved:         var(--ej-color-neutral);       // gris
$support-status-closed:           var(--ej-bg-dark);             // oscuro

// Priority colors
$support-priority-critical:       var(--ej-color-danger);
$support-priority-high:           var(--ej-color-primary);       // naranja impulso
$support-priority-medium:         var(--ej-color-secondary);
$support-priority-low:            var(--ej-color-neutral);
```

**`jaraba_support/scss/main.scss`**:

```scss
@use 'variables';
@use 'portal';
@use 'ticket-detail';
@use 'timeline';
@use 'sidebar-context';
@use 'agent-dashboard';
@use 'sla-indicators';
@use 'status-badges';
@use 'attachment-preview';
```

**`ecosistema_jaraba_theme/scss/bundles/support.scss`** — Bundle del tema:

```scss
// Bundle: Support Portal
// Attached via library 'ecosistema_jaraba_theme/bundle-support'
@use '../variables' as *;

// Import module CSS via CSS custom property consumption
// The module compiles its own CSS; this bundle adds theme-level overrides

.page-support {
  .support-main {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--ej-spacing-lg);
  }
}

.page-agent-dashboard {
  .support-main {
    max-width: 100%;
    padding: 0;
  }
}
```

### 10.7 JavaScript: Support Portal interactivity

**`js/support-portal.js`**:

```javascript
// support-portal.js
// Handles: ticket list filtering, slide-panel creation, SSE updates

(function (Drupal, once) {
  'use strict';

  // CSRF token cache (CSRF-JS-CACHE-001)
  let _csrfTokenPromise = null;
  function getCsrfToken() {
    if (!_csrfTokenPromise) {
      _csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then(r => r.text());
    }
    return _csrfTokenPromise;
  }

  Drupal.behaviors.jarabaSupportPortal = {
    attach: function (context) {
      // Create ticket button → slide-panel
      once('support-create', '[data-support-create]', context).forEach(function (el) {
        el.addEventListener('click', function (e) {
          e.preventDefault();
          // ROUTE-LANGPREFIX-001: Use Drupal.url() for paths
          fetch(Drupal.url('soporte/crear'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          })
            .then(r => r.text())
            .then(html => {
              // Open slide-panel with form
              Drupal.jaraba.slidePanel.open(html, { size: 'large' });
            });
        });
      });

      // SSE for real-time ticket updates
      once('support-sse', '.support-portal', context).forEach(function () {
        const evtSource = new EventSource(Drupal.url('api/v1/support/stream'));
        evtSource.addEventListener('ticket_status_changed', function (e) {
          const data = JSON.parse(e.data);
          const card = document.querySelector('[data-ticket-id="' + Drupal.checkPlain(String(data.ticket_id)) + '"]');
          if (card) {
            // Update status badge (XSS-safe via Drupal.checkPlain)
            const badge = card.querySelector('.support-status-badge');
            if (badge) {
              badge.className = 'support-status-badge support-status-badge--' + Drupal.checkPlain(data.new_status);
              badge.textContent = Drupal.checkPlain(data.new_status_label);
            }
          }
        });
      });
    }
  };
})(Drupal, once);
```

### 10.8 Iconos: jaraba_icon()

Nuevos iconos SVG para el modulo de soporte en `ecosistema_jaraba_core/images/icons/support/`:

| Icono | Archivo | Uso |
|-------|---------|-----|
| `ticket` | `ticket.svg` | Icono principal de tickets |
| `sla-clock` | `sla-clock.svg` | Indicador SLA countdown |
| `agent-headset` | `agent-headset.svg` | Avatar de agente |
| `merge` | `merge.svg` | Accion de merge |
| `escalate` | `escalate.svg` | Accion de escalacion |
| `satisfaction` | `satisfaction.svg` | Encuesta CSAT |
| `internal-note` | `internal-note.svg` | Nota interna (candado) |
| `ai-sparkle` | Reutiliza `ai/sparkle.svg` | Respuesta IA |

Uso en Twig:
```twig
{{ jaraba_icon('support', 'ticket', { variant: 'duotone', size: '24px', color: 'corporate' }) }}
{{ jaraba_icon('support', 'sla-clock', { size: '16px' }) }}
```

### 10.9 Mobile-first responsive

```scss
// _portal.scss
.support-ticket-list {
  display: grid;
  gap: var(--ej-spacing-md);

  // Mobile: single column, full-width cards
  grid-template-columns: 1fr;

  @include respond-to(md) {
    // Tablet+: still single column but wider cards
    grid-template-columns: 1fr;
  }

  @include respond-to(lg) {
    // Desktop: optional 2-column if configured
    grid-template-columns: 1fr;
  }
}

// Ticket detail: mobile-first two-column layout
.support-ticket-detail {
  display: flex;
  flex-direction: column;
  gap: var(--ej-spacing-lg);

  @include respond-to(lg) {
    flex-direction: row;

    .support-ticket-detail__thread {
      flex: 1 1 65%;
      min-width: 0;
    }
    .support-ticket-detail__sidebar {
      flex: 0 0 35%;
      max-width: 400px;
    }
  }
}

// Touch targets: minimum 44x44px
.support-ticket-card,
.support-action-btn {
  min-height: 44px;
  min-width: 44px;
}
```

### 10.10 Modales slide-panel para CRUD

Todas las acciones de crear/editar/responder usan el slide-panel existente:

| Accion | Tamano Panel | Patron |
|--------|-------------|--------|
| Crear ticket | `large` (600px) | Formulario completo con drag-drop adjuntos |
| Responder a ticket | `medium` (480px) | Editor de respuesta + adjuntos |
| Editar ticket (agente) | `medium` | Status, priority, assignee |
| Ver adjunto | `large` | Preview de imagen/PDF |
| Encuesta CSAT | `small` (360px) | 2 preguntas: rating + comentario |

### 10.11 Theme Settings: configuracion administrable

El tema debe exponer settings configurables desde `/admin/appearance/settings/ecosistema_jaraba_theme` para que los textos y opciones del portal de soporte sean editables sin tocar codigo:

| Setting Key | Tipo | Default | Descripcion |
|-------------|------|---------|-------------|
| `support_portal_title` | string | "Centro de Soporte" | Titulo del portal |
| `support_portal_subtitle` | string | "¿En que podemos ayudarte?" | Subtitulo |
| `support_create_cta_text` | string | "Abrir ticket" | Texto del boton crear |
| `support_deflection_title` | string | "Antes de abrir un ticket..." | Titulo de deflection |
| `support_ai_processing_text` | string | "Analizando tu consulta..." | Texto de diagnostico IA |
| `support_csat_title` | string | "¿Como fue tu experiencia?" | Titulo encuesta CSAT |
| `support_enable_widget` | boolean | TRUE | Mostrar widget de soporte in-app |
| `support_widget_position` | select | "bottom-right" | Posicion del widget |

Estos settings se acceden en los templates via `{% set ts = theme_settings|default({}) %}` y luego `ts.support_portal_title|default('Centro de Soporte'|t)`.

### 10.12 GrapesJS: Support Widget Block

Bloque GrapesJS registrado para que los tenants puedan insertar un portal de soporte en sus paginas del site builder:

**`js/grapesjs-jaraba-support-blocks.js`**:

```javascript
// Register Support Portal block in GrapesJS
export default function (editor) {
  editor.BlockManager.add('support-portal-widget', {
    label: 'Support Portal',
    category: 'Interactive',
    content: {
      type: 'support-portal',
      tagName: 'div',
      attributes: {
        'data-jaraba-support-portal': '',
        class: 'jaraba-support-portal-embed',
      },
    },
    media: jarabaIcons.get('ticket'),
  });
}
```

---

## 11. Internacionalizacion (i18n)

**Regla absoluta**: TODO texto visible al usuario debe pasar por el sistema de traduccion:

| Contexto | Patron | Ejemplo |
|----------|--------|---------|
| **PHP Controller/Service** | `$this->t('text')` via `StringTranslationTrait` | `$this->t('Ticket created successfully')` |
| **PHP Entity fields** | `t('text')` en `baseFieldDefinitions()` | `->setLabel(t('Subject'))` |
| **PHP Entity allowed_values** | `t('text')` | `'new' => t('New')` |
| **Twig templates** | `{% trans %}text{% endtrans %}` | `{% trans %}Open ticket{% endtrans %}` |
| **Twig with variables** | `{% trans %}text {{ var }}{% endtrans %}` | `{% trans %}Ticket {{ number }}{% endtrans %}` |
| **JavaScript** | `Drupal.t('text')` | `Drupal.t('Loading...')` |
| **aria-label** | `{% trans %}` | `aria-label="{% trans %}Close panel{% endtrans %}"` |

**Zero strings hardcoded**: Incluso textos como "JRB" en el ticket_number son configurables via settings.

---

## 12. Seguridad

| Amenaza | Mitigacion | Implementacion |
|---------|-----------|---------------|
| **XSS en mensajes** | Markdown→HTML server-side con sanitizacion. `Drupal.checkPlain()` en JS. | `TicketMessage::presave()` renderiza body→body_html con allowlist de tags |
| **CSRF en APIs** | `_csrf_request_header_token: TRUE` en routing | JS obtiene token de `Drupal.url('session/token')`, envia como `X-CSRF-Token` |
| **Adjuntos maliciosos** | ClamAV scan + cuarentena | `AttachmentScanService` marca `infected`, nunca sirve |
| **Acceso cross-tenant** | `AccessControlHandler` + `isSameTenant()` | Verifica tenant match ANTES de cualquier operacion |
| **URL guessing adjuntos** | Pre-signed URLs con HMAC | `AttachmentUrlService` genera URL firmada con expiracion 1h |
| **Rate limiting** | Flood API | 5 tickets/hora/usuario, 10 mensajes/hora, 20 adjuntos/hora |
| **SQL injection** | Drupal Entity API queries | Nunca SQL raw, siempre `$storage->getQuery()` |
| **Internal notes leak** | API filtra por `is_internal_note` segun rol | `SupportApiController::getTicket()` excluye notas internas para tenants |
| **Audit trail tamper** | Append-only entity | `TicketEventLogAccessControlHandler` deniega update/delete |
| **API field injection** | `ALLOWED_FIELDS` whitelist | `SupportApiController::ALLOWED_FIELDS` constante, filtro antes de `set()` |

---

## 13. Integracion con Ecosistema

### 13.1 TenantContextService / TenantBridgeService

```php
// TENANT-BRIDGE-001: tenant_id es entity_reference → group
$fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
  ->setLabel(t('Tenant'))
  ->setSetting('target_type', 'group')
  ->setRequired(TRUE);

// En controladores/servicios:
$tenant = $this->tenantContext->getCurrentTenant(); // Returns GroupInterface
$tenantId = $tenant->id();
```

### 13.2 Customer Success health score

```php
// SupportHealthScoreService calcula el componente de soporte
// del health score de Customer Success.
// Se invoca desde:
// 1. hook_support_ticket_presave() — al cambiar status
// 2. SupportCronService — recalculo periodico
// 3. SLA breach event — impacto inmediato

// Integracion:
if (\Drupal::hasService('ecosistema_jaraba_core.customer_success')) {
  try {
    $cs = \Drupal::service('ecosistema_jaraba_core.customer_success');
    $cs->updateComponentScore($tenantId, 'support', $this->calculateSupportScore($tenantId));
  } catch (\Exception $e) {
    // PRESAVE-RESILIENCE-001: never fail ticket save due to optional service
  }
}
```

### 13.3 Knowledge Base y FAQ Bot

- `TicketDeflectionService` usa `jaraba_rag.qdrant_client` para buscar articulos KB relevantes
- Cuando el FAQ Bot escala (similarity <0.55), ofrece crear ticket pre-rellenado
- Cuando 3+ tickets similares se resuelven en 30 dias, `SupportAnalyticsService` propone auto-generacion de FAQ

### 13.4 AI Skills y SmartBaseAgent

- `SupportAgentSmartAgent` extiende `SmartBaseAgent` (AGENT-GEN2-PATTERN-001)
- Usa `ModelRouterService` para seleccionar tier: Haiku (simple), Sonnet (complex), Opus (critical)
- Respeta `AIIdentityRule::apply()` para identidad IA
- Respeta `AIGuardrailsService::checkPII()` para no exponer PII en respuestas

### 13.5 Billing / Stripe

- Tickets con `category=billing` se vinculan a suscripciones Stripe via `related_entity_type=subscription`
- Estado de pago visible en sidebar de contexto del ticket
- `SupportHealthScoreService::triggerChurnAlertIfNeeded()` detecta tickets de billing/cancelacion

### 13.6 PlanResolverService (feature gates)

```php
// SLA policy resolution using cascade pattern
$capabilities = $planResolver->getPlanCapabilities($vertical, $tier);
$maxSimultaneousTickets = $capabilities['max_support_tickets'] ?? 3;
$includesPhone = $capabilities['support_phone'] ?? FALSE;
$includesPriorityQueue = $capabilities['support_priority_queue'] ?? FALSE;
```

---

## 14. Navegacion Drupal Admin

### 14.1 /admin/structure: Field UI + Settings

**`jaraba_support.links.task.yml`**:

```yaml
entity.support_ticket.settings_tab:
  title: 'Settings'
  route_name: entity.support_ticket.settings
  base_route: entity.support_ticket.settings
```

Esto habilita:
- `/admin/structure/support-ticket/settings` — Configuracion basica
- `/admin/structure/support-ticket/settings/fields` — Field UI: Manage Fields
- `/admin/structure/support-ticket/settings/form-display` — Manage Form Display
- `/admin/structure/support-ticket/settings/display` — Manage Display

### 14.2 /admin/content: Listados de entidades

**`jaraba_support.links.menu.yml`**:

```yaml
jaraba_support.admin.tickets:
  title: 'Support Tickets'
  parent: system.admin_content
  route_name: entity.support_ticket.collection
  weight: 40
  description: 'Manage support tickets from all tenants.'

jaraba_support.admin.sla:
  title: 'SLA Policies'
  parent: system.admin_config
  route_name: jaraba_support.admin.sla_policies
  weight: 50
  description: 'Configure SLA policies by plan and priority.'

jaraba_support.admin.business_hours:
  title: 'Business Hours'
  parent: system.admin_config
  route_name: jaraba_support.admin.business_hours
  weight: 51
  description: 'Configure business hours schedules.'
```

### 14.3 Entity links: CRUD completo

Cada entidad tiene links completos en su anotacion `@ContentEntityType` para que Drupal genere las rutas CRUD automaticamente via `AdminHtmlRouteProvider`.

---

## 15. Fases de Implementacion

### 15.1 Fase 1: Fundacion — Modelo de datos y CRUD basico

**Duracion estimada**: 60-80h

**Entregables**:
- Modulo `jaraba_support` con `info.yml`, `module`, `install`, `permissions.yml`
- 7 ContentEntities: SupportTicket, TicketMessage, TicketAttachment, TicketEventLog, ResponseTemplate, AgentSavedView, TicketWatcher
- 2 ConfigEntities: SlaPolicy, BusinessHoursSchedule
- Access control handlers con tenant isolation
- Premium forms (PremiumEntityFormBase) para SupportTicket y TicketMessage
- ListBuilders para admin
- Rutas admin: collection, add, edit, delete para todas las entidades
- Permisos RBAC (18 permisos)
- Config install: 16 SLA policies + 3 business hours schedules
- Config schema YAML
- Field UI habilitado con settings tab
- TicketService basico (CRUD + state machine)
- 16 configs YAMLs de SLA policies en `config/install/`
- Unit tests para SupportTicket entity y state machine

**Verificacion**: `lando drush en jaraba_support -y && lando drush entity:updates && lando drush cr`

### 15.2 Fase 2: Portal Tenant — Frontend premium

**Duracion estimada**: 80-100h

**Entregables**:
- `SupportPortalController` con index() y createForm()
- `TicketDetailController` con view() y addMessage()
- Template `page--support.html.twig` (zero-region)
- Templates parciales: ticket-card, status-badge, priority-badge, sla-indicator
- Template de portal con lista de tickets, filtros, paginacion
- Template de detalle con timeline conversacional
- Template de formulario de creacion con deflection
- SCSS: `_portal.scss`, `_ticket-detail.scss`, `_timeline.scss`, `_status-badges.scss`, `_sla-indicators.scss`
- Bundle del tema: `bundles/support.scss` → `css/bundles/support.css`
- `package.json` para compilacion Dart Sass
- JS: `support-portal.js` con slide-panel, filtros, paginacion
- JS: `ticket-create.js` con deflection (busqueda KB en tiempo real)
- JS: `ticket-detail.js` con timeline y respuesta
- JS: `attachment-upload.js` con drag-and-drop
- `hook_preprocess_html()` para body classes
- `hook_preprocess_page()` para theme_settings
- `hook_theme_suggestions_page_alter()` para zero-region
- `hook_theme()` con templates registrados
- `template_preprocess_support_ticket()` (ENTITY-PREPROCESS-001)
- Theme settings para textos configurables del portal
- Iconos SVG en `ecosistema_jaraba_core/images/icons/support/`
- Mobile-first responsive (breakpoints del tema)
- Slide-panel para crear ticket y responder (SLIDE-PANEL-RENDER-001)

### 15.3 Fase 3: Motor IA — Clasificacion y resolucion

**Duracion estimada**: 80-100h

**Entregables**:
- `TicketAiClassificationService`: Pipeline 9 pasos, embedding, Qdrant search, Claude API
- `TicketAiResolutionService`: Generacion grounded, threshold 0.85, sentiment check
- `TicketDeflectionService`: Busqueda KB pre-ticket con debounce
- `SupportAgentSmartAgent`: Gen 2 agent extiende SmartBaseAgent
- `TicketRoutingService`: Routing por vertical, skills, carga
- Integration con `ModelRouterService` (Haiku/Sonnet/Opus)
- Integration con `AIGuardrailsService` (PII check)
- Integration con `AIIdentityRule` (identidad IA)
- Flujos ECA: SUP-001, SUP-002, SUP-003 implementados en presave hooks
- API endpoints: classify, suggest, deflection
- JS update para diagnostico IA instantaneo (<15s) con animacion

### 15.4 Fase 4: SLA Engine y Business Hours

**Duracion estimada**: 40-50h

**Entregables**:
- `SlaEngineService`: Calculo deadlines, warnings escalonados (50%, 75%, 90%, 100%), breach detection
- `BusinessHoursService`: Calculo preciso de horas laborables, festivos, timezone
- Pausa SLA en pending_customer (GAP-SUP-15)
- Auto-cierre configurable (GAP-SUP-07)
- `SupportCronService`: Check SLA cada 5 min, auto-close, reminders
- Flujos ECA: SUP-005 (warning), SUP-006 (breach), SUP-007 (idle)
- Admin forms para SlaPolicy y BusinessHoursSchedule
- Unit tests para BusinessHoursService (timezone, holidays, edge cases)

### 15.5 Fase 5: Panel de Agente

**Duracion estimada**: 60-80h

**Entregables**:
- `AgentDashboardController`: Dashboard con cola, metricas personales, ticket view
- Template `support-agent-dashboard.html.twig` con zero-region
- Cola inteligente: ordenacion por urgencia real (prioridad + SLA remaining)
- Boton "Tomar siguiente" (`getNextUrgentTicket()`)
- Vista de ticket para agente: timeline + sidebar contexto (tenant info, health score, tickets previos)
- Notas internas (background amarillo, icono candado)
- Response templates con variables dinamicas (GAP-SUP-04)
- Saved views/filtros personalizados (GAP-SUP-12)
- Bulk actions en lista (GAP-SUP-14)
- SCSS: `_agent-dashboard.scss`, `_sidebar-context.scss`
- JS: `agent-dashboard.js`

### 15.6 Fase 6: Adjuntos, Seguridad y Virus Scanning

**Duracion estimada**: 40-50h

**Entregables**:
- `AttachmentService`: Upload con validacion MIME, tamano por plan, storage path tenant-isolated
- `AttachmentScanService`: ClamAV integration (socket/TCP), scan asincrono (GAP-SUP-08)
- `AttachmentUrlService`: Pre-signed URLs con HMAC (GAP-SUP-09)
- Analisis IA de adjuntos: screenshots via Claude Vision, PDFs via OCR
- SCSS: `_attachment-preview.scss`
- Rate limiting: Flood API para uploads
- Cron: scan pending attachments

### 15.7 Fase 7: Clase Mundial — SSE, Merge, Summarization

**Duracion estimada**: 50-60h

**Entregables**:
- `TicketStreamService`: SSE events para real-time (GAP-SUP-13)
- `SupportStreamController`: Endpoint SSE con `text/event-stream`
- Collision detection via SSE events (GAP-SUP-03)
- `TicketMergeService`: Fusion con persistencia completa (GAP-SUP-01)
- `TicketSummarizationService`: Resumenes IA de hilos (GAP-SUP-11)
- Parent/child tickets: campos + logica (GAP-SUP-05)
- Ticket watchers (CC): entidad + notificaciones (GAP-SUP-06)
- JS updates para SSE en portal y dashboard de agente

### 15.8 Fase 8: Analytics, CSAT y Health Score

**Duracion estimada**: 40-50h

**Entregables**:
- `SupportAnalyticsService`: Todos los KPIs (volume, MTTR, FRT, SLA, CSAT, CES, deflection)
- `CsatSurveyService`: Encuestas post-resolucion (CSAT + CES) (GAP-SUP-10)
- `SupportHealthScoreService`: Integracion bidireccional con Customer Success
- Template analytics dashboard (admin)
- Flujos ECA: SUP-008, SUP-009, SUP-010, SUP-012
- Auto-generacion de FAQs desde patrones de tickets
- Churn alert para tickets de billing

### 15.9 Fase 9: Integracion Email y WhatsApp

**Duracion estimada**: 30-40h

**Entregables**:
- Email inbound parsing (SendGrid Parse webhook)
- Email threading (In-Reply-To, References headers)
- Email outbound para notificaciones de ticket
- Integracion con infraestructura de notificaciones existente
- WhatsApp stub (preparacion para integracion futura Twilio)

### 15.10 Fase 10: Testing integral y Go-Live

**Duracion estimada**: 40-50h

**Entregables**:
- Unit tests: SupportTicket, SlaEngine, BusinessHours, TicketMerge, AttachmentUrl
- Kernel tests: Entity schema installation, access control, query filters
- Browser verification en `https://jaraba-saas.lndo.site/`
- Revision de seguridad: XSS, CSRF, tenant isolation, rate limiting
- Revision de i18n: zero hardcoded strings
- Revision de accesibilidad: WCAG 2.1 AA
- Documentacion final en INDICE_GENERAL

---

## 16. Estrategia de Testing

| Tipo | Suite | Descripcion | Archivos |
|------|-------|-------------|----------|
| **Unit** | `Unit` | Logica de negocio pura sin Drupal | `TicketServiceTest`, `SlaEngineServiceTest`, `BusinessHoursServiceTest`, `TicketMergeServiceTest`, `AttachmentUrlServiceTest`, `SupportTicketTest` |
| **Kernel** | `Kernel` | Entity schema, access control, queries | `SupportTicketAccessTest`, `SlaEngineKernelTest` |

**Mocking patterns (PHPUnit 11)**:
- Entidades: `ContentEntityInterface` mock con callback `get()->willReturnCallback()`
- Servicios opcionales: `@?` en services.yml, nullable constructor, `hasService()` check
- AI provider: mock que retorna respuesta predefinida

---

## 17. Verificacion y Despliegue

### 17.1 Checklist manual

- [ ] Modulo se habilita sin errores: `lando drush en jaraba_support -y`
- [ ] Entity schemas se instalan: `lando drush entity:updates`
- [ ] SLA policies se importan: `lando drush config:import -y`
- [ ] Portal frontend accesible: `/es/soporte`
- [ ] Crear ticket funciona en slide-panel
- [ ] Detalle de ticket muestra timeline
- [ ] Panel de agente muestra cola
- [ ] Admin listing funciona: `/admin/content/support-tickets`
- [ ] Field UI accesible: `/admin/structure/support-ticket/settings`
- [ ] SCSS compilado sin errores
- [ ] Todos los textos traducibles
- [ ] Mobile responsive verificado

### 17.2 Comandos de verificacion

```bash
# Verificar entidades
lando drush ev "print_r(array_keys(\Drupal::entityTypeManager()->getDefinitions()));" | grep support

# Verificar permisos
lando drush ev "print_r(array_keys(\Drupal::service('user.permissions')->getPermissions()));" | grep support

# Verificar rutas
lando drush ev "\$r = \Drupal::service('router.route_provider'); foreach (\$r->getAllRoutes() as \$name => \$route) { if (str_contains(\$name, 'support')) echo \$name . PHP_EOL; }"

# Compilar SCSS
lando ssh -c "cd web/modules/custom/jaraba_support && npx sass scss/main.scss:css/jaraba-support.css --style=compressed"
lando ssh -c "cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/bundles/support.scss:css/bundles/support.css --style=compressed"

# Tests
lando php vendor/bin/phpunit --testsuite Unit --filter Support
```

### 17.3 Browser verification URLs

| URL | Esperado |
|-----|----------|
| `/es/soporte` | Portal con lista de tickets del usuario |
| `/es/soporte/crear` | Formulario en slide-panel con deflection |
| `/es/soporte/ticket/1` | Detalle con timeline conversacional |
| `/es/soporte/agente` | Dashboard de agente con cola |
| `/es/admin/content/support-tickets` | Lista admin de todos los tickets |
| `/es/admin/structure/support-ticket/settings` | Field UI accesible |
| `/es/admin/config/support/sla-policies` | Lista de SLA policies |
| `/es/admin/config/support/business-hours` | Lista de business hours |
| `/es/api/v1/support/tickets?_format=json` | JSON con lista de tickets |

### 17.4 Checklist de directrices cumplidas

| # | Directriz | Verificacion |
|---|-----------|-------------|
| 1 | Federated Design Tokens | `grep -r '\$ej-' jaraba_support/scss/` → 0 resultados (solo consume `var(--ej-*)`) |
| 2 | SCSS compilado | `ls jaraba_support/css/jaraba-support.css` existe |
| 3 | Variables inyectables | Theme settings editables en `/admin/appearance/settings/` |
| 4 | Twig sin regiones | `page--support.html.twig` no contiene `page.sidebar` ni bloques |
| 5 | Body classes via hook | `grep 'preprocess_html' jaraba_support.module` → implementado |
| 6 | Textos traducibles | `grep -rn "hardcoded" templates/` → 0 resultados |
| 7 | Dart Sass moderno | `grep 'darken\|lighten' scss/` → 0 resultados |
| 8 | jaraba_icon() | `grep 'jaraba_icon' templates/` → usado en todos los templates |
| 9 | PremiumEntityFormBase | `grep 'extends PremiumEntityFormBase' src/Form/` → todas las forms |
| 10 | Tenant isolation | AccessControlHandler verifica tenant en update/delete |
| 11 | Slide-panel CRUD | `renderPlain()` usado en `SupportPortalController::createForm()` |
| 12 | CSRF en APIs | `_csrf_request_header_token` en todas las rutas POST/PATCH |
| 13 | Entity en admin | `/admin/content/support-tickets` y `/admin/structure/support-ticket/settings` accesibles |
| 14 | Append-only logs | `TicketEventLogAccessControlHandler` deniega update/delete |
| 15 | Optional DI @? | `services.yml` usa `@?` para IA, billing, customer_success |
| 16 | Presave resilience | `hasService()` + try-catch en hooks con servicios opcionales |
| 17 | Mobile-first | Min-width media queries, touch targets 44px |
| 18 | DOC-GUARD-001 | No modifica documentos maestros |

---

## 18. Troubleshooting

| Problema | Causa | Solucion |
|----------|-------|----------|
| Entity schema no se instala | Dependencia circular o campo mal definido | Verificar `baseFieldDefinitions()` y `$modules` en tests |
| SLA deadlines incorrectos | Timezone no configurado en BusinessHoursSchedule | Verificar `timezone` en config, usar `DateTimeImmutable` con timezone explicito |
| Adjuntos no se sirven | ClamAV no disponible → scan_status = 'error' | Verificar conexion ClamAV, configurar fallback |
| SSE se desconecta | Timeout de proxy/load balancer | Configurar `proxy_read_timeout 300s` en Nginx |
| IA no clasifica | API key no configurada o servicio no disponible | Verificar `ai_provider_openai.settings`, usar `@?` para degradacion |
| Slide-panel muestra BigPipe placeholders | Usando `render()` en lugar de `renderPlain()` | Cambiar a `renderPlain()` (SLIDE-PANEL-RENDER-001) |
| Body classes no se aplican | Usando `attributes.addClass()` en page template | Mover a `hook_preprocess_html()` |

---

## 19. Referencias Cruzadas

| Documento | Relacion |
|-----------|----------|
| 178_Platform_Tenant_Support_Tickets_v1_Claude.md | Especificacion original |
| 2026-02-27_Analisis_Sistema_Soporte_Tickets_Clase_Mundial_v1.md | Analisis de mercado y gaps |
| 00_DIRECTRICES_PROYECTO.md | Directrices de desarrollo |
| 00_FLUJO_TRABAJO_CLAUDE.md | Flujo de trabajo y aprendizajes |
| 00_DOCUMENTO_MAESTRO_ARQUITECTURA.md | Arquitectura general |
| 2026-02-05_arquitectura_theming_saas_master.md | Arquitectura SCSS |
| 2026-02-05_especificacion_grapesjs_saas.md | GrapesJS integration |
| 2026-02-26_Plan_Implementacion_Reviews_Comentarios_Clase_Mundial_v1.md | Referencia de formato |

---

**Fin del Plan de Implementacion**

*Documento generado el 2026-02-27 por Claude Opus 4.6. Plan de implementacion integral para el modulo jaraba_support con elevacion a 100% clase mundial.*
