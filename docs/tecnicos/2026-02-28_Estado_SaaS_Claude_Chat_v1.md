# Estado del SaaS — Jaraba Impact Platform
# Documento de Contexto para Claude Chat (Project Knowledge)
# Ultima actualizacion: 2026-02-28 | Version: 1.0.0

> **Proposito:** Este documento proporciona a Claude Chat el estado REAL y verificado de
> la plataforma para que sus analisis y propuestas de especificaciones de implementacion
> sean precisas, alineadas con la arquitectura existente, y cumplan con las 140+ directrices
> vigentes del proyecto. Todo dato ha sido verificado contra el codigo fuente.

---

## INDICE

1. [Identidad y Filosofia](#1-identidad-y-filosofia)
2. [Stack Tecnologico](#2-stack-tecnologico)
3. [Metricas del Codebase](#3-metricas-del-codebase)
4. [Mapa de Verticales (10)](#4-mapa-de-verticales)
5. [Modulos Cross-Cutting](#5-modulos-cross-cutting)
6. [Inventario de Modulos (95)](#6-inventario-de-modulos)
7. [Arquitectura Multi-Tenant](#7-arquitectura-multi-tenant)
8. [Sistema de IA](#8-sistema-de-ia)
9. [Frontend y UX](#9-frontend-y-ux)
10. [Theming y Design Tokens](#10-theming-y-design-tokens)
11. [SEO y Datos Estructurados](#11-seo-y-datos-estructurados)
12. [CI/CD e Infraestructura](#12-cicd-e-infraestructura)
13. [Testing](#13-testing)
14. [Seguridad](#14-seguridad)
15. [Directrices Criticas (Top 50)](#15-directrices-criticas)
16. [Lo Que Existe vs Lo Que Falta](#16-lo-que-existe-vs-lo-que-falta)
17. [Reglas Para Propuestas](#17-reglas-para-propuestas)

---

## 1. Identidad y Filosofia

**Nombre:** Jaraba Impact Platform (Ecosistema Jaraba)
**Filosofia:** "Sin Humo" — codigo limpio, practico, sin complejidad innecesaria. Cada linea de codigo debe aportar valor real al usuario final. No se acepta over-engineering ni abstraccion prematura.

**Mision:** Plataforma SaaS multi-vertical que digitaliza el impacto social y economico de comunidades, abarcando empleo, emprendimiento, comercio local, agricultura, servicios profesionales, formacion y asistencia legal.

**Principio RUNTIME-VERIFY-001:** La diferencia entre "el codigo existe" y "el usuario lo experimenta" requiere verificacion en CADA capa de la cadena: PHP -> Twig -> SCSS -> CSS compilado -> JS -> drupalSettings -> DOM final. Un gap en cualquier eslabon rompe la experiencia completa.

---

## 2. Stack Tecnologico

| Componente | Version | Notas |
|-----------|---------|-------|
| **CMS** | Drupal 11.x | core-recommended ^11.0 |
| **PHP** | 8.4 | declare(strict_types=1), 93% cobertura |
| **Base de Datos** | MariaDB 10.11 | NO MySQL, NO 11.2 |
| **Cache** | Redis 7.4 | Modulo redis ^1.7 |
| **Vector DB** | Qdrant | RAG, semantic cache, long-term memory |
| **Document Processing** | Apache Tika | Extraccion de texto de documentos |
| **IA Principal** | Claude API (Anthropic) | 3 tiers: Haiku 4.5, Sonnet 4.6, Opus 4.6 |
| **IA Secundaria** | Google Gemini + Vertex AI | Fallback provider |
| **IA Terciaria** | OpenAI | Provider adicional |
| **Pagos** | Stripe Connect | Destination charges, webhooks HMAC |
| **E-Commerce** | Drupal Commerce 3.1 | Solo para ComercioConecta y AgroConecta |
| **Servidor Produccion** | IONOS Dedicated L-16 NVMe | 128GB RAM, AMD EPYC |
| **Dev Local** | Lando (.lando.yml) | NO docker-compose.yml |
| **URL Dev** | https://jaraba-saas.lndo.site/ | Multi-subdomain tenants |
| **CI/CD** | GitHub Actions | 8 workflows, 41+ jobs |
| **JavaScript** | Vanilla JS + Drupal.behaviors | NO React, NO Vue, NO Angular, NO frameworks |
| **SCSS** | Dart Sass moderno | @use (NO @import), color-mix() |
| **Page Builder** | GrapesJS 5.7 | 11 plugins custom, embebido en jaraba_page_builder |
| **Mapas** | Leaflet + Geofield + Geocoder | Georreferenciacion de negocios |
| **Auth Social** | Google, LinkedIn, Microsoft | OAuth2 + patched OpenID |
| **Email** | SMTP IONOS | MailHog en desarrollo |
| **Formularios** | PremiumEntityFormBase | Todos los entity forms extienden esta clase abstracta |

### Datos que se confunden frecuentemente

- **NO es PHP 8.3** — es PHP 8.4 (restricciones de propiedades heredadas, no dynamic props en mocks)
- **NO es MariaDB 11.2** — es MariaDB 10.11
- **NO tiene React** — todo es Vanilla JS + Drupal.behaviors
- **NO tiene 5 verticales** — tiene 10 verticales canonicos
- **NO usa --jaraba-* como prefijo CSS** — usa --ej-* (Ecosistema Jaraba)
- **NO tiene tema "jaraba_theme"** — el tema se llama ecosistema_jaraba_theme
- **NO usa Key module para secretos** — usa getenv() via settings.secrets.php
- **NO usa docker-compose** — usa Lando (.lando.yml)
- **NO tiene module de blog separado** — jaraba_blog esta DESINSTALADO, consolidado en jaraba_content_hub

---

## 3. Metricas del Codebase

| Metrica | Valor |
|---------|-------|
| Modulos custom (.info.yml) | 95 |
| Modulos custom habilitados | 57 |
| Modulos contrib habilitados | ~115 total (73 contrib + 42 custom) |
| Archivos PHP | 3,691 |
| Lineas PHP | ~712,000 |
| Archivos SCSS | 471 |
| Lineas SCSS | ~142,000 |
| Archivos JS (sin vendor) | 341 |
| Lineas JS | ~79,000 |
| Templates Twig | 864 |
| ContentEntity types | 426 |
| ConfigEntity types | 17 |
| Servicios registrados | ~2,800 |
| Rutas definidas | 2,563 |
| Controllers | 403 |
| Forms | 648 |
| Tests totales | 453 |
| Tests Unit | 367 |
| Tests Kernel | 55 |
| Tests Functional | 31 |
| Parciales Twig (tema) | 66 |
| Variables CSS --ej-* | 46+ inyectadas desde UI |
| Paquetes contrib (composer) | 59 drupal/* |
| Workflows CI | 8 (ci, security-scan, deploy, deploy-staging, deploy-production, daily-backup, fitness-functions, verify-backups) |
| Docs tecnicos | 306 especificaciones |
| Planes de implementacion | 118 documentos |
| Docs de arquitectura | 27 documentos |
| Aprendizajes documentados | 159 (#1 a #152 + extras) |
| Golden Rules | 89 reglas de oro |
| Scripts de mantenimiento | 42 scripts (seed, audit, deploy, self-healing) |

---

## 4. Mapa de Verticales

### 4.1 Empleabilidad
**Modulos:** jaraba_candidate, jaraba_job_board, jaraba_lms, jaraba_skills, jaraba_matching, jaraba_mentoring, jaraba_self_discovery
**Estado:** Implementado (vertical mas maduro)
**Entidades:** CandidateProfile, JobPosting, JobApplication, EmployerProfile, SavedJob, JobAlert, CandidateSkill, CandidateEducation, CandidateLanguage, CourseReview, CopilotConversation, CopilotMessage (~28 entities)
**Funcionalidades:**
- Perfil candidato con 8 secciones en slide-panel
- CV Builder con templates (PDF/DOCX export)
- Importacion LinkedIn
- Dashboard empleador con applicant tracking
- Job Board con busqueda avanzada y filtros
- LMS con cursos, lecciones, matriculaciones
- Skills matching con IA
- Sistema de mentoring (8 entities: MentorProfile, MentorshipRequest, Session, Review, Goal, Message, Resource, Availability)
- Copiloto de Empleabilidad (Gen 2 SmartEmployabilityCopilot)
- Sistema de credenciales (badges, certificaciones)

### 4.2 Emprendimiento
**Modulos:** ecosistema_jaraba_core (features emprendimiento), jaraba_business_tools, jaraba_copilot_v2, jaraba_foc
**Estado:** Implementado
**Funcionalidades:**
- Copilot v2 adaptativo con 5 modos (learn, build, coach, mentor, market)
- 44 experimentos progresivos Osterwalder (desbloqueo semanal)
- RAG sobre knowledge base de negocios
- Streaming SSE via PHP Generator
- FOC (Formacion Online Continua)
- Journey progression con dificultad adaptativa
- Personalization Engine (6 fuentes)

### 4.3 ComercioConecta
**Modulos:** jaraba_comercio_conecta (42 entities), jaraba_commerce, jaraba_social_commerce
**Estado:** Implementado (vertical mas complejo por entidades)
**Entidades:** ProductRetail, OrderRetail, OrderItemRetail, CartItem, Cart, MerchantProfile, FlashOffer, FlashOfferClaim, CouponRetail, ShipmentRetail, SuborderRetail, LocalBusinessProfile, PosConnection, ReviewRetail, WishlistItem, AbandonedCart, SearchLog, SearchIndex, PayoutRecord... (42 entities)
**Funcionalidades:**
- Marketplace multi-vendor de proximidad
- POS omnicanal (componentes Aceternity UI)
- Flash offers con gamificacion
- Inventario tiempo real (pickup + delivery)
- QR-based local lead capture
- Cart abandonment recovery
- Dynamic pricing por zona
- Local SEO (15 presets de industria)
- Merchant Copilot (Gen 2)

### 4.4 AgroConecta
**Modulos:** jaraba_agroconecta_core (37 entities)
**Estado:** Implementado
**Entidades:** AgroBatch, AgroCollection, AgroCategory, AgroCertification, OrderAgro, OrderItemAgro, ProductDocumentAgro, IntegrityProofAgro, QrCodeAgro, QrScanEvent, AgroShipment, TraceabilityEvent, ReviewAgro, PromotionAgro, CouponAgro... (37 entities)
**Funcionalidades:**
- Marketplace B2B agricola
- Trazabilidad completa (granja a minorista)
- Certificaciones digitales con proofs de integridad
- Gestion de lotes con IoT tracking
- QR scanning para productos
- WhatsApp API integration
- Producer Copilot (Gen 2)
- Email sequences para retencion

### 4.5 JarabaLex
**Modulos:** jaraba_legal (6), jaraba_legal_cases (4), jaraba_legal_billing (7), jaraba_legal_calendar (5), jaraba_legal_knowledge (4), jaraba_legal_templates (2), jaraba_legal_vault (5), jaraba_legal_intelligence (5), jaraba_legal_lexnet (2)
**Estado:** Implementado (9 sub-modulos, ~40 entities)
**Entidades:** ClientCase, ClientInquiry, CaseActivity, InquiryTriage, CourtHearing, LegalDeadline, CalendarConnection, LegalTemplate, PrecedentCase, TemplateVariable, LegalInvoice, TimeEntry, TrustAccount...
**Funcionalidades:**
- Gestion de casos con triage IA
- Documentacion automatizada con variables
- Calendario judicial sincronizado (Google, Outlook)
- Alertas de plazos con SLA
- Vault de documentos encriptados
- Integracion LexNet (protocolo oficial espanol)
- Investigacion legal con LLM
- Facturacion por tarea/hora con retainers
- Numeracion de expedientes (EXP-YYYY-NNNN)
- Legal Copilot (Gen 2 SmartLegalCopilot)

### 4.6 ServiciosConecta
**Modulos:** jaraba_servicios_conecta (6 entities)
**Estado:** Implementado
**Entidades:** ServiceOffering, ServicePackage, AvailabilitySlot, Booking, ProviderProfile, ReviewServicios
**Funcionalidades:**
- Marketplace de servicios profesionales (fontaneros, electricistas, consultores)
- Motor de reservas con slots de disponibilidad
- Comunicacion encriptada (trustbox)
- Firma digital PAdES (AutoFirma)
- Auto-presupuestado con IA
- Scoring de reputacion

### 4.7 Andalucia +ei
**Modulos:** jaraba_andalucia_ei
**Estado:** Implementado
**Entidades:** ExpedienteDocumento, ProgramParticipant, StoFicha, ProgressRecord
**Funcionalidades:**
- Programa publico de emprendimiento (4 fases PIIL)
- AI mentoring con tracking de horas
- Dificultad adaptativa basada en progreso
- Revision de documentos via LLM
- Exportacion STO (Seguimiento Trabajador Ocupado)
- Cumplimiento SEPE teleformacion

### 4.8 Content Hub
**Modulos:** jaraba_content_hub (5 entities)
**Estado:** Implementado (jaraba_blog DESINSTALADO, consolidado aqui)
**Entidades:** ContentArticle, ContentCategory, ContentAuthor, ContentComment, AiGenerationLog
**Funcionalidades:**
- Blog engine completo con categorias y autores
- Programacion y auto-publicacion de articulos
- Generacion de contenido con IA + analisis de sentimiento
- SEO completo: JSON-LD, OG, Twitter Cards, canonical
- RSS 2.0 feed (DOMDocument)
- Canvas editor GrapesJS (3 campos canvas)
- Comentarios con moderacion
- Articulos relacionados, adyacentes, populares
- View tracking y analytics

### 4.9 Formacion
**Modulos:** jaraba_training (3 entities), jaraba_sepe_teleformacion (3 entities)
**Estado:** Implementado
**Funcionalidades:**
- Sistema de cursos y lecciones (hereda de LMS)
- Learning paths personalizados
- Certificaciones y credenciales
- Integracion H5P (video, quiz, lectura)
- xAPI learning data reporting
- Compliance SEPE para teleformacion
- LearningPathAgent (Gen 2)

### 4.10 Demo
**Modulos:** Configuracion del vertical "demo" en BaseAgent::VERTICALS
**Estado:** Funcional (usa frameworks heredados)
**Proposito:** POC para demostraciones, pilotos de features, sales enablement

---

## 5. Modulos Cross-Cutting

### 5.1 jaraba_support — Sistema de Soporte
**Entidades (7 CE + 2 Config):** SupportTicket, TicketMessage, TicketAttachment, TicketEventLog, SlaPolicy, ResponseTemplate, AgentSavedView, TicketWatcher, BusinessHoursSchedule
**Maquina de estados (10 estados):** new -> ai_handling -> open -> pending_customer / pending_internal -> escalated -> resolved -> closed + reopened + merged
**Features:** Clasificacion IA automatica (Gen 2 Support Agent), SLA con creditos, watchers y menciones, templates de respuesta, adjuntos, horarios de negocio, deflexion de tickets via Knowledge Base

### 5.2 jaraba_billing — Facturacion SaaS
**Entidades (8 CE):** SaasPlan, SaasPlanTier, SaasPlanFeatures, PricingRule, FreemiumVerticalLimit + usage_billing (3)
**Features:** Multi-tier pricing por vertical, feature access per plan, wallet/creditos, usage metering, activacion trial, validacion de plan

### 5.3 jaraba_page_builder — Constructor de Paginas
**Entidades (8 CE):** Page, PageContent, PageTemplate, AnalyticsDaily, TemplateRegistry...
**Features:** GrapesJS 5.7 embebido con 11 plugins custom, 50+ bloques premium (Aceternity/Magic UI), quota limits por vertical/plan, analytics por pagina, template library, rendering slide-panel, vendor JS local (sin CDN)

### 5.4 jaraba_ai_agents — Framework de Agentes IA
**Entidades (16 CE):** PromptTemplate, BrandVoiceProfile, AIUsageLog, ProactiveInsight, AgentBenchmarkResult, AutonomousSession, AIWorkflow, A2ATask, VerificationResult, AiFeedback, CausalAnalysis, RemediationLog, AiRiskAssessment, AiAuditEntry
**11 Agentes Gen 2:** SmartMarketing, Storytelling, CustomerExperience, Support, ProducerCopilot, Sales, MerchantCopilot, SmartEmployabilityCopilot, SmartLegalCopilot, SmartContentWriter, LearningPathAgent
**Features:** Model routing 3-tier, tool-use loop iterativo (5 max), semantic cache (Qdrant), distributed tracing, MCP server (JSON-RPC 2.0), self-healing via State API, prompt versioning + rollback, benchmark LLM-as-Judge, PII guardrails bidireccional (US + ES)

### 5.5 jaraba_copilot_v2 — Copiloto Streaming
**Entidades (5 CE):** CopilotConversation, CopilotMessage, ...
**Features:** SSE streaming via PHP Generator, 5 modos adaptativos, buffered fallback, eventos SSE: chunk, cached, done, error, thinking, mode

### 5.6 ecosistema_jaraba_core — Nucleo Transversal
**Alcance:** Servicios compartidos, traits, access handlers, AI identity rule, guardrails, tenant services, preprocess hooks, parciales Twig, configuracion de tema
**Servicios clave:** TenantContextService, TenantBridgeService, AIIdentityRule, AIGuardrailsService, PersonalizationEngineService, CostAlertService, DefaultEntityAccessControlHandler, PremiumEntityFormBase

### 5.7 ecosistema_jaraba_theme — Tema Premium
**Tipo:** Tema custom sin base theme (base theme: false)
**Arquitectura:** 5 capas de design tokens, 46+ variables CSS --ej-*, 66 parciales Twig, 15 presets de industria
**SCSS:** Dart Sass moderno, 471 archivos, ~142K LOC, route/bundle pattern
**Features:** Design tokens per tenant, responsive mobile-first, CKEditor 5 styling, CWV tracking, Aceternity/Magic UI components

---

## 6. Inventario de Modulos (95)

### Habilitados (57 custom + ecosistema_jaraba_theme)

| Dominio | Modulos |
|---------|---------|
| **Core** | ecosistema_jaraba_core |
| **IA/Agentes** | jaraba_ai_agents, jaraba_copilot_v2, jaraba_rag, ai_provider_anthropic, ai_provider_google_gemini, ai_provider_google_vertex, ai_provider_openai |
| **Empleabilidad** | jaraba_candidate, jaraba_job_board, jaraba_lms, jaraba_skills, jaraba_matching, jaraba_mentoring, jaraba_self_discovery |
| **Emprendimiento** | jaraba_business_tools, jaraba_foc |
| **Comercio** | jaraba_comercio_conecta, jaraba_commerce |
| **Agro** | jaraba_agroconecta_core |
| **Legal** | jaraba_legal_cases, jaraba_legal_intelligence |
| **Servicios** | jaraba_servicios_conecta |
| **Gobierno** | jaraba_andalucia_ei, jaraba_sepe_teleformacion |
| **Contenido** | jaraba_content_hub |
| **Formacion** | jaraba_training |
| **Billing** | jaraba_billing, jaraba_addons |
| **Soporte** | jaraba_tenant_knowledge |
| **Infraestructura** | jaraba_page_builder, jaraba_site_builder, jaraba_theming, jaraba_paths |
| **Engagement** | jaraba_onboarding, jaraba_journey, jaraba_customer_success, jaraba_referral |
| **Comunicacion** | jaraba_email, jaraba_interactive |
| **Analytics** | jaraba_analytics, jaraba_heatmap, jaraba_pixels, jaraba_performance |
| **i18n** | jaraba_i18n |
| **Multi-tenant** | jaraba_groups, jaraba_tenant_export |
| **Geo** | jaraba_geo |
| **CRM** | jaraba_crm |
| **Credentials** | jaraba_credentials |
| **AB Testing** | jaraba_ab_testing |
| **Ads** | jaraba_ads |
| **Diagnostico** | jaraba_diagnostic |
| **Eventos** | jaraba_events |
| **Integraciones** | jaraba_integrations |
| **Recursos** | jaraba_resources |

### No habilitados (38 modulos inactivos, existentes pero no en core.extension)

| Modulo | Razon |
|--------|-------|
| jaraba_blog | DESINSTALADO — consolidado en jaraba_content_hub |
| jaraba_agents | Bridge autonomo->smart (standalone) |
| jaraba_support | Implementado reciente, pendiente habilitacion produccion |
| jaraba_sla | Pendiente habilitacion con jaraba_support |
| jaraba_legal | Base legal (compliance ToS/SLA/AUP) |
| jaraba_legal_billing | Facturacion legal |
| jaraba_legal_calendar | Calendario judicial |
| jaraba_legal_knowledge | Knowledge base legal |
| jaraba_legal_templates | Templates documentos legales |
| jaraba_legal_vault | Vault encriptado |
| jaraba_legal_lexnet | Integracion LexNet |
| jaraba_social_commerce | Social commerce (pendiente) |
| jaraba_connector_sdk | SDK de conectores |
| jaraba_credentials_cross_vertical | Credenciales cross-vertical |
| jaraba_credentials_emprendimiento | Credenciales emprendimiento |
| jaraba_dr | Disaster recovery |
| jaraba_einvoice_b2b | Factura electronica B2B |
| jaraba_facturae | FacturaE espanola |
| jaraba_funding | Financiacion/subvenciones |
| jaraba_governance | Gobernanza |
| jaraba_identity | Identidad digital |
| jaraba_insights_hub | Hub de insights |
| jaraba_institutional | Paginas institucionales |
| jaraba_agent_flows | Flujos de agentes |
| jaraba_agent_market | Marketplace de agentes |
| jaraba_ambient_ux | UX ambiental |
| jaraba_messaging | Mensajeria |
| jaraba_mobile | App movil |
| jaraba_multiregion | Multi-region |
| jaraba_notifications | Notificaciones |
| jaraba_predictive | Prediccion (analytics) |
| jaraba_privacy | Privacidad/GDPR |
| jaraba_pwa | Progressive Web App |
| jaraba_security_compliance | Compliance de seguridad |
| jaraba_social | Social features |
| jaraba_sso | Single Sign-On |
| jaraba_success_cases | Casos de exito |
| jaraba_usage_billing | Usage-based billing |
| jaraba_verifactu | VeriFACTU (obligacion fiscal espanola) |
| jaraba_whitelabel | White-label theming |
| jaraba_workflows | Workflows ECA |
| jaraba_zkp | Zero-knowledge proofs |

---

## 7. Arquitectura Multi-Tenant

### Modelo de Aislamiento
- **Group Module** (drupal/group ^3.2) para aislamiento de contenido (soft isolation)
- **Tenant entity** posee facturacion y configuracion
- **TenantBridgeService** resuelve bidireccionalmenente entre Tenant <-> Group
- **TenantContextService** resuelve tenant actual via admin_user + group membership
- **TenantResolverService::getCurrentTenant()** devuelve GroupInterface (NO TenantInterface)
- **Domain module** (drupal/domain ^2.0) para subdominios por tenant

### Reglas de Aislamiento
- TODA query de datos DEBE filtrar por tenant_id (TENANT-001)
- Usar ecosistema_jaraba_core.tenant_context para obtener tenant (TENANT-002)
- AccessControlHandler DEBE verificar tenant match para update/delete (TENANT-ISOLATION-ACCESS-001)
- Published pages (view) son publicas (acceso anonimo)
- tenant_id en entity_reference almacena NULL (no 0) para contenido platform-wide

### Tenants de Desarrollo
Configurados en .lando.yml como subdominios:
- aceitesdelsur.jaraba-saas.lndo.site
- pepejaraba.jaraba-saas.lndo.site
- jarabaimpact.jaraba-saas.lndo.site
- plataformadeecosistemas.jaraba-saas.lndo.site

---

## 8. Sistema de IA

### Arquitectura General
- **0 agentes Gen 1** restantes — todos migrados a Gen 2
- **11 agentes Gen 2** que extienden SmartBaseAgent
- **3 tiers de modelo** configurados en YAML (NO hardcoded):
  - fast: Haiku 4.5 (queries simples, clasificacion)
  - balanced: Sonnet 4.6 (conversacion, analisis)
  - premium: Opus 4.6 (razonamiento complejo, generacion larga)

### Componentes del Stack IA
| Componente | Servicio | Funcion |
|-----------|---------|---------|
| Model Router | ModelRouterService | Seleccion de tier por complejidad |
| Provider Fallback | ProviderFallbackService | Circuit breaker entre providers |
| Context Window | ContextWindowManager | Estimacion de tokens |
| Tool Use | ToolRegistry + callAiApiWithTools() | Loop iterativo (max 5 iteraciones) |
| Native Tools | callAiApiWithNativeTools() | API-level function calling |
| MCP Server | McpServerController | JSON-RPC 2.0 en POST /api/v1/mcp |
| Streaming | StreamingOrchestratorService | SSE via PHP Generator |
| Tracing | TraceContextService | trace_id, span_id distribuidos |
| Cache Semantico | SemanticCacheService | Qdrant fuzzy matching |
| Memoria | AgentLongTermMemoryService | Qdrant per-tenant memory |
| ReAct | ReActLoopService | Multi-step reasoning |
| Handoff | HandoffDecisionService | Routing entre agentes |
| Auto-diagnostico | AutoDiagnosticService | Self-healing via State API |
| Prompt Mgmt | PromptVersionService | Versionado + rollback |
| Benchmark | AgentBenchmarkService | LLM-as-Judge evaluacion |
| Guardrails | AIGuardrailsService | PII detection (US + ES), jailbreak check |
| Identidad | AIIdentityRule::apply() | Centralizado (NUNCA duplicar) |
| A/B Testing | Built into execute() | Experimento antes de doExecute() |

### RAG (jaraba_rag)
- Qdrant como vector store
- Embeddings via AI module providers
- LlmReRankerService para re-ranking con tier fast
- Knowledge bases por vertical + platform-wide
- TicketDeflectionService busca en KB antes de crear ticket

---

## 9. Frontend y UX

### Zero Region Pattern
Cada ruta frontend tiene un template page--{ruta}.html.twig con layout limpio:
- `{{ clean_content }}` en vez de `{{ page.content }}` — extrae solo system_main_block
- `{{ clean_messages }}` para mensajes
- Body classes via `hook_preprocess_html()` (NUNCA attributes.addClass() en template)
- Template suggestions via `hook_theme_suggestions_page_alter()` con array $ecosistema_prefixes
- Layout full-width, pensado para movil

### Parciales Twig (66 archivos en templates/partials/)
Templates reutilizables incluidos con `{% include %}`:
- `_header.html.twig` — dispatcher: classic/minimal/transparent
- `_footer.html.twig` — layouts: mega/standard/split, 3 columnas de nav configurables desde UI
- `_copilot-fab.html.twig`, `_command-bar.html.twig`
- `_avatar-nav.html.twig`, `_bottom-nav.html.twig`
- `_skeleton.html.twig`, `_empty-state.html.twig`
- `_review-card.html.twig`, `_article-card.html.twig`
- Footer content (links, copyright, social) configurado desde Theme Settings UI, NO hardcoded

### Modales y Slide-Panel
- TODA accion crear/editar/ver en frontend DEBE abrirse en slide-panel
- Usa `renderPlain()` (NO render()) para evitar BigPipe placeholders sin resolver
- Deteccion: `isSlidePanelRequest()` = `isXmlHttpRequest()` && `!_wrapper_format`
- Form action: `$form['#action'] = $request->getRequestUri()`
- NUNCA `$form_state->setCached(TRUE)` incondicional (LogicException en GET/HEAD)

### JavaScript
- 100% Vanilla JS + Drupal.behaviors (NO frameworks SPA)
- Traducciones: `Drupal.t('string')`
- URLs API via drupalSettings, NUNCA hardcoded (ROUTE-LANGPREFIX-001)
- XSS: `Drupal.checkPlain()` para datos de API en innerHTML
- CSRF: Token de `/session/token` cacheado en variable del modulo

---

## 10. Theming y Design Tokens

### Tema Unico: ecosistema_jaraba_theme
- Base theme: false (no hereda de ningun tema)
- 13 vertical tabs en configuracion (Apariencia > Ecosistema Jaraba Theme)
- 70+ opciones configurables sin codigo
- 15 presets de industria pre-configurados

### 5 Capas de Design Tokens
1. **SCSS Tokens** — variables base ($ej-primary, etc.)
2. **CSS Custom Properties** — :root { --ej-* } (46+ variables)
3. **Component Tokens** — variables especificas por componente
4. **Tenant Override** — TenantThemeConfig entity (ThemeTokenService cascade)
5. **Vertical Presets** — configuracion pre-definida por vertical

### Reglas CSS/SCSS
- Prefijo: `--ej-*` (NUNCA --jaraba-*, NUNCA hex hardcoded) — CSS-VAR-ALL-COLORS-001 (P0)
- Colores de marca: azul-corporativo #233D63, naranja-impulso #FF8C42, verde-innovacion #00A9A5
- Variables inyectadas via hook_preprocess_html() -> `<style>:root { --ej-* }</style>`
- Dart Sass: `@use` (NO @import), `@use 'sass:color'`, `color-mix()`
- Migracion: rgba() -> color-mix(in srgb, {token} {pct}%, transparent) — SCSS-COLORMIX-001
- Cada parcial SCSS DEBE incluir `@use '../variables' as *;` — SCSS-001
- Tras CADA edicion .scss, recompilar y verificar timestamp CSS > SCSS — SCSS-COMPILE-VERIFY-001 (P0)
- Compilacion: `npm run build` desde web/themes/custom/ecosistema_jaraba_theme/
- Huerfanos: `scripts/check-scss-orphans.js` detecta parciales sin @use en main.scss

### Iconos
- Funcion Twig: `{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}`
- Variante default: duotone (ICON-DUOTONE-001)
- Colores solo de paleta Jaraba: azul-corporativo, naranja-impulso, verde-innovacion, white, neutral
- SVG en canvas_data: hex explicito en stroke/fill, NUNCA currentColor
- NO emojis Unicode como iconos visuales en Page Builder

---

## 11. SEO y Datos Estructurados

### Implementado
- **JSON-LD:** FAQPage, QAPage, BreadcrumbList, Article, Organization, LocalBusiness, Service, Product, AggregateRating
- **Meta tags:** OG (title, description, image, type, url), Twitter Cards (summary_large_image)
- **Canonical URLs:** via Url::fromRoute() con language prefix
- **sitemap.xml:** simple_sitemap module
- **Pathauto:** URLs amigables por entity type
- **Redirects:** redirect module
- **RSS 2.0:** DOMDocument con Atom self-link (Content Hub)
- **Hreflang:** jaraba_i18n module
- **Language prefix:** /es/ (sitio en espanol, ROUTE-LANGPREFIX-001)

### SeoService (Content Hub)
Servicio dedicado que genera: meta/OG/Twitter/JSON-LD/BreadcrumbList para articulos del blog.
Patron replicable para otros verticales.

---

## 12. CI/CD e Infraestructura

### GitHub Actions — 8 Workflows

| Workflow | Trigger | Jobs |
|----------|---------|------|
| **ci.yml** | push develop/feature/hotfix, PR | Lint (PHPCS, PHPStan L6, ESLint, Stylelint), Unit Tests (80% coverage), Kernel Tests (MariaDB 10.11), Security Scan, Build Check |
| **security-scan.yml** | daily 02:00 UTC, push main/develop | Composer audit, npm audit, Trivy FS (CRITICAL/HIGH block), PHPStan SAST, OWASP ZAP baseline (staging) |
| **deploy.yml** | manual | Deploy a ambiente seleccionado |
| **deploy-staging.yml** | push develop | Deploy automatico a staging |
| **deploy-production.yml** | push main | Deploy a produccion |
| **daily-backup.yml** | daily | Backup DB + files |
| **fitness-functions.yml** | weekly/PR | Architecture fitness functions |
| **verify-backups.yml** | weekly | Verificacion integridad backups |

### Lando Dev Environment (7 servicios)
- PHP 8.4 (Apache), MariaDB 10.11, Redis 7.x, Qdrant, Apache Tika, MailHog, phpMyAdmin
- Tooling: composer, drush, php, redis-cli, qdrant-status, ai-health, self-healing scripts
- Multi-subdomain para tenants de desarrollo

### Scripts de Mantenimiento (42)
| Categoria | Ejemplos |
|-----------|----------|
| Self-healing | run-all.sh, db-health.sh, qdrant-health.sh, cache-recovery.sh |
| Go-live | preflight_checks.sh, validation_suite.sh, rollback.sh |
| Deploy | deploy.sh, aiops-production.sh, setup-cron-production.sh |
| Data seed | seed_core_skills.php, seed_pepejaraba.php, create_blog.php |
| Audit | audit-jarabaimpact.php, verify-elevation.php, validate_*.php |
| Integridad docs | verify-doc-integrity.sh (DOC-GUARD-001) |

---

## 13. Testing

### Configuracion PHPUnit
- **phpunit.xml** en raiz del proyecto
- **4 suites:** Unit, Kernel, Functional, PromptRegression
- **Coverage:** 80% minimo en modulos jaraba_*
- **DB tests:** mysql://drupal:drupal@127.0.0.1:3306/drupal_jaraba_test

### Distribucion de Tests (453 total)
| Suite | Tests | Notas |
|-------|-------|-------|
| Unit | 367 | Mock-based, sin DB |
| Kernel | 55 | MariaDB service container requerido |
| Functional | 31 | Browser tests |
| PromptRegression | (subset Unit) | Fixtures para validar prompts |

### Reglas de Testing
- KERNEL-TEST-DEPS-001: $modules NO auto-resuelve dependencias. Listar TODOS
- KERNEL-TEST-001: KernelTestBase SOLO cuando test necesita DB/entities
- MOCK-DYNPROP-001: PHP 8.4 prohibe dynamic properties en mocks
- MOCK-METHOD-001: createMock() solo soporta metodos de la interface dada
- TEST-CACHE-001: Entity mocks DEBEN implementar getCacheContexts, getCacheTags, getCacheMaxAge
- KERNEL-TIME-001: Assertions de timestamp con tolerancia +/-1 segundo

### Static Analysis
- **PHPStan Level 6** con baseline (phpstan.neon + phpstan-security.neon + phpstan-baseline.neon)
- **PHPCS:** Drupal + DrupalPractice coding standards
- **ESLint + Stylelint:** Para JS y SCSS

---

## 14. Seguridad

### Gestion de Secretos (SECRET-MGMT-001)
- NUNCA secrets en config/sync/. Valores vacios en YAML
- Secretos via `getenv()` en `config/deploy/settings.secrets.php`
- NO usar Key module de Drupal
- Aplica a: OAuth Google/LinkedIn/Microsoft, SMTP IONOS, reCAPTCHA v3, Stripe

### Reglas de Seguridad
| Regla | Descripcion |
|-------|-------------|
| AUDIT-SEC-001 | Webhooks con HMAC + hash_equals(). Query string token insuficiente |
| AUDIT-SEC-002 | Rutas con datos tenant DEBEN usar _permission (no solo _user_is_logged_in) |
| AUDIT-SEC-003 | NUNCA \|raw sin sanitizacion previa servidor |
| API-WHITELIST-001 | Endpoints con campos dinamicos DEBEN definir ALLOWED_FIELDS |
| CSRF-API-001 | API routes via fetch() usan _csrf_request_header_token: 'TRUE' |
| ACCESS-STRICT-001 | Comparaciones ownership con (int)..===(int), NUNCA == |
| INNERHTML-XSS-001 | Drupal.checkPlain() para datos API en innerHTML |
| CSRF-JS-CACHE-001 | Token /session/token cacheado en variable del modulo |
| AI-GUARDRAILS-PII-001 | Deteccion PII bidireccional (DNI, NIE, IBAN ES, NIF/CIF, SSN, +34) |

### CI Security
- Composer audit (prod dependencies)
- npm audit (high/critical)
- Trivy filesystem scan (CRITICAL/HIGH bloquean)
- PHPStan SAST (Level 6 + security rules)
- OWASP ZAP baseline (staging, manual/cron)

---

## 15. Directrices Criticas (Top 50)

### Prioridad P0 — Incumplimiento causa runtime errors o UX rota

| ID | Regla |
|----|-------|
| TENANT-001 | TODA query DEBE filtrar por tenant |
| TENANT-002 | Usar tenant_context service, NUNCA queries ad-hoc |
| TENANT-BRIDGE-001 | SIEMPRE TenantBridgeService para Tenant<->Group |
| TENANT-ISOLATION-ACCESS-001 | AccessControlHandler DEBE verificar tenant match |
| CSS-VAR-ALL-COLORS-001 | TODOS colores via var(--ej-*, fallback) |
| SCSS-COMPILE-VERIFY-001 | Recompilar tras CADA edicion SCSS |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute(), sitio usa /es/ |
| PREMIUM-FORMS-PATTERN-001 | Entity forms DEBEN extender PremiumEntityFormBase |
| RUNTIME-VERIFY-001 | Verificar 5 dependencias runtime post-implementacion |
| SECRET-MGMT-001 | Secretos via getenv(), NUNCA en config/sync/ |
| DOC-GUARD-001 | Master docs via Edit, NUNCA Write. Pre-commit hook protege |

### Prioridad P1 — Incumplimiento causa bugs o deuda tecnica

| ID | Regla |
|----|-------|
| TRANSLATABLE-FIELDDATA-001 | Entities translatable: SQL usa _field_data |
| QUERY-CHAIN-001 | addExpression()/join() devuelven string, no $this |
| DATETIME-ARITHMETIC-001 | datetime=VARCHAR, created=INT — no mezclar |
| ENTITY-PREPROCESS-001 | Entity con view mode DEBE tener preprocess en .module |
| PRESAVE-RESILIENCE-001 | Presave hooks: hasService() + try-catch |
| SLIDE-PANEL-RENDER-001 | renderPlain() para slide-panel, no render() |
| ZERO-REGION-001 | Variables via hook_preprocess_page(), no controller |
| ZERO-REGION-003 | #attached del controller NO se procesa |
| SCSS-ENTRY-CONSOLIDATION-001 | No name.scss + _name.scss en mismo directorio |
| AGENT-GEN2-PATTERN-001 | Gen 2 agents: SmartBaseAgent + doExecute() |
| MODEL-ROUTING-CONFIG-001 | Tiers en YAML, no hardcoded |
| SERVICE-CALL-CONTRACT-001 | Firmas de metodo DEBEN coincidir exactamente |
| ICON-CONVENTION-001 | jaraba_icon() con variante duotone default |
| ICON-COLOR-001 | Solo colores de paleta Jaraba |
| CONTROLLER-READONLY-001 | NO readonly en props heredadas de ControllerBase |
| DRUPAL11-001 | Hijos NO redeclaran typed properties del padre |
| ENTITY-FK-001 | FKs cross-modulo = integer, mismo modulo = entity_reference |
| AUDIT-CONS-001 | TODA ContentEntity DEBE tener AccessControlHandler |

### Prioridad P2 — Mejores practicas y consistencia

| ID | Regla |
|----|-------|
| SCSS-COLORMIX-001 | rgba() -> color-mix() |
| SCSS-001 | @use '../variables' as * en cada parcial |
| ENTITY-001 | EntityOwnerTrait + interfaces |
| LABEL-NULLSAFE-001 | $entity->label() puede ser NULL |
| FIELD-UI-SETTINGS-TAB-001 | field_ui_base_route necesita default local task |
| FORM-CACHE-001 | No setCached(TRUE) incondicional |
| COMMIT-SCOPE-001 | Docs separados de codigo |
| ICON-DUOTONE-001 | Variante default duotone |
| ICON-CANVAS-INLINE-001 | hex explicito en canvas SVG |
| ICON-EMOJI-001 | No emojis en Page Builder |
| KERNEL-TEST-DEPS-001 | Listar TODOS los modulos en $modules |
| MOCK-DYNPROP-001 | No dynamic props en mocks (PHP 8.4) |
| AI-IDENTITY-RULE | Identidad IA centralizada, no duplicar |
| VERTICAL-CANONICAL-001 | 10 verticales canonicos en BaseAgent::VERTICALS |

---

## 16. Lo Que Existe vs Lo Que Falta

### Ya Implementado (NO proponer de nuevo)
- Multi-tenancy con Group Module + TenantBridgeService
- 11 agentes IA Gen 2 (0 Gen 1 restantes)
- MCP Server (JSON-RPC 2.0 en /api/v1/mcp)
- Streaming SSE via PHP Generator
- PremiumEntityFormBase (237 forms migrados)
- Zero Region pattern (66 parciales, clean_content)
- Page Builder GrapesJS con 11 plugins
- CI completo (lint, unit, kernel, security, SAST, DAST baseline)
- PHPStan Level 6 con baseline
- Pre-commit hook para proteccion de docs (DOC-GUARD-001)
- Sistema de soporte con state machine 10 estados
- AI guardrails bidireccional (PII US + ES)
- Design tokens 5 capas + 46 CSS variables
- Theme Settings UI con 70+ opciones
- SEO completo (JSON-LD, OG, Twitter, canonical, sitemap, RSS)
- Self-healing scripts (DB, cache, Qdrant)
- Slide-panel para acciones CRUD
- Tool-use loop iterativo (5 iteraciones max)
- Semantic cache (Qdrant)
- Distributed tracing (trace_id, span_id)
- Prompt versioning + rollback
- Agent benchmark (LLM-as-Judge)
- Auto-diagnostic self-healing via State API
- Content Hub con canvas editor
- RAG con re-ranking

### Parcialmente Implementado (puede mejorarse)
- **Reviews/Comments:** 4 entities heterogeneas en 4 modulos. Plan aprobado para ReviewableEntityTrait + 5 servicios transversales + 2 nuevas entities (v2.0.0 plan completado en docs)
- **Centro de Ayuda:** Infraestructura completa pero contenido seed pendiente de deploy (update_10003)
- **Legal:** Solo 2 de 9 sub-modulos habilitados (jaraba_legal_cases, jaraba_legal_intelligence)
- **Formacion SEPE:** Compliance basico implementado, certificados avanzados pendientes
- **Notificaciones:** Email via jaraba_email, pero push/in-app via jaraba_notifications NO habilitado
- **Mobile/PWA:** jaraba_pwa y jaraba_mobile existen pero NO habilitados
- **Social features:** jaraba_social existe pero NO habilitado

### No Existe (puede proponerse)
- Dashboard unificado cross-vertical para administradores de plataforma
- Marketplace de plugins/addons para tenants
- API publica documentada (OpenAPI/Swagger)
- Webhooks configurables por tenant (outbound)
- Multi-idioma completo (solo /es/ activo actualmente)
- App nativa movil
- Integracion calendario nativa (mas alla de legal)
- Sistema de gamificacion transversal
- Reporting avanzado con data warehouse

---

## 17. Reglas Para Propuestas

### Al proponer nuevas especificaciones, SIEMPRE:

1. **Verificar stack real:** PHP 8.4, MariaDB 10.11, Drupal 11, 10 verticales, Vanilla JS, --ej-*, ecosistema_jaraba_theme
2. **No proponer lo que ya existe:** Revisar seccion 16 antes de proponer
3. **Respetar 140+ directrices:** Especialmente P0 (TENANT, CSS-VAR, SCSS-COMPILE, ROUTE-LANG, PREMIUM-FORMS, SECRET-MGMT)
4. **Usar patrones existentes:** PremiumEntityFormBase, SmartBaseAgent, Zero Region, design tokens, parciales Twig
5. **Entity forms:** SIEMPRE PremiumEntityFormBase con getSectionDefinitions() y getFormIcon()
6. **JavaScript:** Vanilla JS + Drupal.behaviors, URLs via drupalSettings, Drupal.t() para traducciones
7. **CSS:** var(--ej-*, fallback) para TODOS los colores, Dart Sass @use, compilar y verificar
8. **Templates:** {% trans %} para textos, parciales con {% include %}, clean_content en paginas
9. **Seguridad:** Secrets via getenv(), HMAC en webhooks, PII detection, CSRF tokens
10. **Testing:** Tests unitarios para logica, kernel para DB, 80% coverage minimo
11. **Documentacion:** Aprendizaje para bugs/patrones descubiertos, golden rule para reglas de oro
12. **No over-engineer:** "Sin Humo" — solo lo necesario, simple, practico

### Formato de Propuesta Esperado
Cada propuesta de implementacion debe incluir:
- Archivos a crear/modificar (con rutas exactas)
- Dependencias de otros modulos
- Entidades nuevas (con campos, anotaciones, access handler)
- Servicios nuevos (con inyeccion de dependencias)
- Rutas (con permisos)
- Templates (con variables y parciales reutilizables)
- SCSS (con variables --ej-* y compilacion)
- JS (con Drupal.behaviors y drupalSettings)
- Tests (unit + kernel si aplica)
- Cumplimiento de directrices (tabla)
- Verificacion RUNTIME-VERIFY-001

### Errores Frecuentes a Evitar
| Error | Correccion |
|-------|-----------|
| Proponer PHP 8.3 | Es PHP 8.4 |
| Proponer MariaDB 11.2 | Es MariaDB 10.11 |
| Proponer 5 verticales | Son 10 verticales |
| Usar --jaraba-* como prefijo CSS | Es --ej-* |
| Referirse a "jaraba_theme" | Es ecosistema_jaraba_theme |
| Proponer React/Vue/Angular | Es Vanilla JS + Drupal.behaviors |
| Proponer Key module para secretos | Es getenv() via settings.secrets.php |
| Proponer docker-compose | Es Lando (.lando.yml) |
| Usar @import en SCSS | Es @use (Dart Sass moderno) |
| Proponer nuevo modulo de blog | Consolidado en jaraba_content_hub |
| Proponer agentes Gen 1 (BaseAgent) | Todos son Gen 2 (SmartBaseAgent) |
| Hardcodear URLs (/es/ayuda) | Url::fromRoute() siempre |
| Crear entity form sin PremiumEntityFormBase | PROHIBIDO |
| Usar hex hardcoded en SCSS | var(--ej-*, #fallback) |
| Proponer CDN para GrapesJS | Vendor JS local |

---

*Documento generado automaticamente desde el codigo fuente del repositorio.*
*Verificado contra: 95 modulos, 3,691 archivos PHP, 426 ContentEntities, 2,563 rutas, 453 tests.*
*Ultima verificacion: 2026-02-28.*
