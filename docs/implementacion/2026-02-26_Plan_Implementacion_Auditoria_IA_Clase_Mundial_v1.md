# Plan de Implementacion: Auditoria IA Clase Mundial — 25 Gaps hacia Paridad con Lideres del Mercado

**Fecha de creacion:** 2026-02-26 18:00
**Ultima actualizacion:** 2026-02-26 18:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Implementacion
**Modulo:** Multi-modulo (`jaraba_ai_agents`, `jaraba_copilot_v2`, `jaraba_content_hub`, `ecosistema_jaraba_core`, `jaraba_candidate`, `jaraba_lms`, `jaraba_comercio_conecta`, `jaraba_servicios_conecta`, `jaraba_onboarding`)
**Documentos fuente:** Auditoria de Mercado IA v1, Plan Elevacion IA Nivel5 v2, Plan Remediacion Integral IA v1

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que](#12-por-que)
   - 1.3 [Alcance](#13-alcance)
   - 1.4 [Filosofia](#14-filosofia)
   - 1.5 [Estimacion](#15-estimacion)
2. [Tabla de Correspondencia Tecnica](#2-tabla-de-correspondencia-tecnica)
   - 2.1 [Gaps con codigo existente (7 refinamiento)](#21-gaps-con-codigo-existente-7-refinamiento)
   - 2.2 [True gaps nuevos (16)](#22-true-gaps-nuevos-16)
   - 2.3 [Infrastructure gaps (2)](#23-infrastructure-gaps-2)
3. [Tabla de Cumplimiento de Directrices](#3-tabla-de-cumplimiento-de-directrices)
4. [Requisitos Previos](#4-requisitos-previos)
5. [Entorno de Desarrollo](#5-entorno-de-desarrollo)
6. [Refinamiento de Componentes Existentes (GAP-AUD-001 a GAP-AUD-007)](#6-refinamiento-de-componentes-existentes-gap-aud-001-a-gap-aud-007)
   - 6.1 [GAP-AUD-001: Onboarding Wizard AI](#61-gap-aud-001-onboarding-wizard-ai)
   - 6.2 [GAP-AUD-002: Pricing AI Metering](#62-gap-aud-002-pricing-ai-metering)
   - 6.3 [GAP-AUD-003: Demo/Playground Publico](#63-gap-aud-003-demoplayground-publico)
   - 6.4 [GAP-AUD-004: AI Dashboard GEO Enhancement](#64-gap-aud-004-ai-dashboard-geo-enhancement)
   - 6.5 [GAP-AUD-005: llms.txt MCP Discovery](#65-gap-aud-005-llmstxt-mcp-discovery)
   - 6.6 [GAP-AUD-006: Schema.org GEO Enhancement](#66-gap-aud-006-schemaorg-geo-enhancement)
   - 6.7 [GAP-AUD-007: Dark Mode AI Components](#67-gap-aud-007-dark-mode-ai-components)
7. [Command Bar / Spotlight (Cmd+K) — GAP-AUD-008](#7-command-bar--spotlight-cmdk--gap-aud-008)
   - 7.1 [Decision arquitectonica](#71-decision-arquitectonica)
   - 7.2 [CommandRegistryService](#72-commandregistryservice)
   - 7.3 [CommandBarController](#73-commandbarcontroller)
   - 7.4 [Frontend command-bar.js](#74-frontend-command-barjs)
   - 7.5 [SCSS _command-bar.scss](#75-scss-_command-barscss)
   - 7.6 [Twig _command-bar.html.twig](#76-twig-_command-barhtmltwig)
   - 7.7 [Seguridad](#77-seguridad)
   - 7.8 [i18n](#78-i18n)
   - 7.9 [Theme Settings](#79-theme-settings)
8. [Inline AI — GAP-AUD-009](#8-inline-ai--gap-aud-009)
   - 8.1 [Arquitectura](#81-arquitectura)
   - 8.2 [InlineAiService](#82-inlineaiservice)
   - 8.3 [InlineAiController](#83-inlineaicontroller)
   - 8.4 [Frontend inline-ai-trigger.js](#84-frontend-inline-ai-triggerjs)
   - 8.5 [Integracion PremiumEntityFormBase](#85-integracion-premiumentityformbase)
   - 8.6 [SCSS _inline-ai.scss](#86-scss-_inline-aiscss)
   - 8.7 [Integracion con GrapesJS](#87-integracion-con-grapesjs)
9. [Proactive Intelligence — GAP-AUD-010](#9-proactive-intelligence--gap-aud-010)
   - 9.1 [Arquitectura](#91-arquitectura)
   - 9.2 [ProactiveInsight entity](#92-proactiveinsight-entity)
   - 9.3 [ProactiveInsightEngine service](#93-proactiveinsightengine-service)
   - 9.4 [Frontend](#94-frontend)
   - 9.5 [Admin integration](#95-admin-integration)
10. [Voice AI Interface — GAP-AUD-011](#10-voice-ai-interface--gap-aud-011)
    - 10.1 [Arquitectura](#101-arquitectura)
    - 10.2 [MultiModalBridgeService implementacion](#102-multimodalbridgeservice-implementacion)
    - 10.3 [voice-input.js](#103-voice-inputjs)
    - 10.4 [Feature gate por plan](#104-feature-gate-por-plan)
11. [A2A Protocol — GAP-AUD-012](#11-a2a-protocol--gap-aud-012)
    - 11.1 [Extension del patron MCP](#111-extension-del-patron-mcp)
    - 11.2 [AgentCardController](#112-agentcardcontroller)
    - 11.3 [A2ATaskController](#113-a2ataskcontroller)
    - 11.4 [Seguridad](#114-seguridad)
12. [Vision/Multimodal — GAP-AUD-013](#12-visionmultimodal--gap-aud-013)
    - 12.1 [MultiModalBridgeService::analyzeImage()](#121-multimodalbridgeserviceanalyzeimage)
    - 12.2 [CopilotStreamController multipart](#122-copilotstreamcontroller-multipart)
    - 12.3 [copilot-chat-widget.js upload](#123-copilot-chat-widgetjs-upload)
13. [AI Test Coverage + CI/CD Prompt Regression — GAP-AUD-014 + GAP-AUD-015](#13-ai-test-coverage--cicd-prompt-regression--gap-aud-014--gap-aud-015)
    - 13.1 [Estado actual](#131-estado-actual)
    - 13.2 [Tests nuevos](#132-tests-nuevos)
    - 13.3 [PromptRegressionTestBase](#133-promptregressiontestbase)
    - 13.4 [phpunit.xml suite](#134-phpunitxml-suite)
14. [Blog Slugs + Content Hub tenant_id — GAP-AUD-016 + GAP-AUD-017](#14-blog-slugs--content-hub-tenant_id--gap-aud-016--gap-aud-017)
    - 14.1 [Slug field y route parameter converter](#141-slug-field-y-route-parameter-converter)
    - 14.2 [tenant_id field y access handler](#142-tenant_id-field-y-access-handler)
15. [Vertical AI Features — GAP-AUD-018 a GAP-AUD-022](#15-vertical-ai-features--gap-aud-018-a-gap-aud-022)
    - 15.1 [Skill Inference (Empleabilidad) — GAP-AUD-018](#151-skill-inference-empleabilidad--gap-aud-018)
    - 15.2 [Adaptive Learning (LMS) — GAP-AUD-019](#152-adaptive-learning-lms--gap-aud-019)
    - 15.3 [Demand Forecasting (ComercioConecta) — GAP-AUD-020](#153-demand-forecasting-comercioconecta--gap-aud-020)
    - 15.4 [AI Writing in GrapesJS — GAP-AUD-021](#154-ai-writing-in-grapesjs--gap-aud-021)
    - 15.5 [Service Matching (ServiciosConecta) — GAP-AUD-022](#155-service-matching-serviciosconecta--gap-aud-022)
16. [Design System Documentation — GAP-AUD-023](#16-design-system-documentation--gap-aud-023)
17. [Infrastructure Gaps — GAP-AUD-024 + GAP-AUD-025](#17-infrastructure-gaps--gap-aud-024--gap-aud-025)
18. [Internacionalizacion (i18n)](#18-internacionalizacion-i18n)
19. [Seguridad](#19-seguridad)
20. [Fases de Implementacion](#20-fases-de-implementacion)
21. [Estrategia de Testing](#21-estrategia-de-testing)
22. [Verificacion y Despliegue](#22-verificacion-y-despliegue)
23. [Troubleshooting](#23-troubleshooting)
24. [Referencias Cruzadas](#24-referencias-cruzadas)
25. [Registro de Cambios](#25-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Se implementan **25 gaps** identificados en una auditoria exhaustiva que comparo el nivel IA del SaaS Jaraba Impact Platform contra lideres del mercado (Salesforce Agentforce, HubSpot Breeze, Shopify Sidekick, Intercom Fin, Notion AI). Una segunda auditoria profunda del codigo **corrigio varios hallazgos**: 7 gaps que se reportaron como "ausentes" ya existen como codigo funcional y solo necesitan refinamiento.

**Desglose:**
- **7 gaps de refinamiento** (GAP-AUD-001 a GAP-AUD-007): Componentes existentes que necesitan mejoras puntuales para alcanzar paridad
- **16 true gaps nuevos** (GAP-AUD-008 a GAP-AUD-023): Funcionalidades que no existen y deben crearse
- **2 gaps de infraestructura** (GAP-AUD-024 a GAP-AUD-025): Capacidades operacionales de escala

### 1.2 Por que

El stack IA actual ha alcanzado un nivel **4.2/5 en backend** tras las elevaciones previas (23 FIX + 10 GAP), pero se queda en **2-3/5** en dimensiones criticas:

| Dimension | Nivel actual | Nivel objetivo | Gap |
|-----------|-------------|----------------|-----|
| Backend IA (agentes, routing, guardrails) | 4.2/5 | 4.5/5 | Bajo |
| UX IA (Command Bar, Inline AI, Voice) | 1.5/5 | 4.0/5 | **Critico** |
| Monetizacion IA (metering, pricing) | 2.0/5 | 3.5/5 | Alto |
| SEO/GEO (schema, llms.txt) | 2.5/5 | 4.0/5 | Alto |
| Testing IA (coverage, regression) | 1.0/5 | 3.5/5 | **Critico** |
| Interoperabilidad (A2A, multimodal) | 1.5/5 | 3.0/5 | Alto |
| Verticales IA (skill, adaptive, forecast) | 2.0/5 | 3.5/5 | Alto |
| Design System documentation | 0.5/5 | 3.0/5 | **Critico** |

**Competidores de referencia:**

| Plataforma | Command Bar | Inline AI | Voice | A2A | Test Coverage |
|------------|-----------|-----------|-------|-----|--------------|
| Salesforce Agentforce | Si (Einstein Bar) | Si | Si (Voice AI) | Si (A2A) | >80% |
| HubSpot Breeze | Si (Cmd+K) | Si (Breeze Copilot) | No | Parcial | >70% |
| Shopify Sidekick | Si (Search Bar) | Si (Magic) | No | No | >75% |
| Intercom Fin | Si (Quick Search) | Si (AI Compose) | Si | Si (webhooks) | >80% |
| **Jaraba (actual)** | **No** | **No** | **No** | **Parcial (MCP)** | **<5%** |

### 1.3 Alcance

Los 25 gaps con identificadores GAP-AUD-001 a GAP-AUD-025:

| ID | Nombre | Tipo | Sprint |
|----|--------|------|--------|
| GAP-AUD-001 | Onboarding Wizard AI | Refinamiento | 1 |
| GAP-AUD-002 | Pricing AI Metering | Refinamiento | 1 |
| GAP-AUD-003 | Demo/Playground Publico | Refinamiento | 1 |
| GAP-AUD-004 | AI Dashboard GEO | Refinamiento | 2 |
| GAP-AUD-005 | llms.txt MCP Discovery | Refinamiento | 1 |
| GAP-AUD-006 | Schema.org GEO | Refinamiento | 2 |
| GAP-AUD-007 | Dark Mode AI Components | Refinamiento | 2 |
| GAP-AUD-008 | Command Bar (Cmd+K) | Nuevo | 1 |
| GAP-AUD-009 | Inline AI | Nuevo | 2 |
| GAP-AUD-010 | Proactive Intelligence | Nuevo | 2 |
| GAP-AUD-011 | Voice AI Interface | Nuevo | 3 |
| GAP-AUD-012 | A2A Protocol | Nuevo | 3 |
| GAP-AUD-013 | Vision/Multimodal | Nuevo | 3 |
| GAP-AUD-014 | AI Test Coverage | Nuevo | 1 |
| GAP-AUD-015 | CI/CD Prompt Regression | Nuevo | 2 |
| GAP-AUD-016 | Blog Slugs | Nuevo | 1 |
| GAP-AUD-017 | Content Hub tenant_id | Nuevo | 2 |
| GAP-AUD-018 | Skill Inference (Empleabilidad) | Nuevo | 3 |
| GAP-AUD-019 | Adaptive Learning (LMS) | Nuevo | 3 |
| GAP-AUD-020 | Demand Forecasting (ComercioConecta) | Nuevo | 4 |
| GAP-AUD-021 | AI Writing in GrapesJS | Nuevo | 3 |
| GAP-AUD-022 | Service Matching (ServiciosConecta) | Nuevo | 3 |
| GAP-AUD-023 | Design System Documentation | Nuevo | 4 |
| GAP-AUD-024 | Cost Attribution per Tenant | Infraestructura | 4 |
| GAP-AUD-025 | Horizontal Scaling AI | Infraestructura | 4 |

### 1.4 Filosofia

1. **Conectar antes de crear:** Antes de escribir codigo nuevo, verificar que no existe ya un componente que cubre el 60-80% del gap. 7 de los 25 gaps son refinamientos de codigo existente.
2. **Reutilizar SmartBaseAgent:** Toda nueva capacidad IA DEBE conectarse al pipeline existente de SmartBaseAgent (897 lineas, 10 constructor args, model routing, guardrails, observability, quality evaluation).
3. **Mobile-first:** Todos los componentes UI nuevos (Command Bar, Inline AI, Voice, alertas proactivas) DEBEN funcionar en pantallas de 320px+.
4. **Test-driven:** Cada gap DEBE incluir al menos 1 unit test y 1 kernel test antes de considerarse completado. Target total: 40+ unit tests, 15+ kernel tests.
5. **Directrices primero:** Todo codigo nuevo cumple las 73+ reglas del proyecto (CSRF, XSS, tenant isolation, PII, i18n, SCSS Dart Sass, Twig zero-region, Premium Forms, etc.).
6. **Internacionalizacion nativa:** Cada string de interfaz usa `|t` en Twig, `$this->t()` en PHP, `Drupal.t()` en JS.
7. **Progressive enhancement:** Voice AI y Vision son opt-in por plan, con graceful degradation si el browser no soporta Web Speech API.

### 1.5 Estimacion

| Sprint | Gaps | Horas min | Horas max |
|--------|------|-----------|-----------|
| Sprint 1 (P0 — Foundations) | GAP-AUD-001, 002, 003, 005, 008, 014, 016 | 80 | 110 |
| Sprint 2 (P1 — Core AI UX) | GAP-AUD-004, 006, 007, 009, 010, 015, 017 | 90 | 120 |
| Sprint 3 (P2 — Advanced) | GAP-AUD-011, 012, 013, 018, 019, 021, 022 | 100 | 140 |
| Sprint 4 (P3 — Scale) | GAP-AUD-020, 023, 024, 025 | 50 | 70 |
| **TOTAL** | **25 gaps** | **320** | **440** |

---

## 2. Tabla de Correspondencia Tecnica

### 2.1 Gaps con codigo existente (7 refinamiento)

| GAP ID | Nombre | Componente existente | Archivo | Lineas | Que falta |
|--------|--------|---------------------|---------|--------|-----------|
| GAP-AUD-001 | Onboarding Wizard AI | `TenantOnboardingWizardService` + `OnboardingOrchestratorService` | `jaraba_onboarding/src/Service/TenantOnboardingWizardService.php` (409 ln), `jaraba_onboarding/src/Service/OnboardingOrchestratorService.php` (217 ln) | 626 | AI recommendation layer via SmartBaseAgent |
| GAP-AUD-002 | Pricing AI Metering | `PricingController` + `MetaSitePricingService` | `ecosistema_jaraba_core/src/Controller/PricingController.php` (356 ln), `ecosistema_jaraba_core/src/Service/MetaSitePricingService.php` (197 ln) | 553 | Metering de tokens AI por plan, visualizacion de consumo |
| GAP-AUD-003 | Demo/Playground | `DemoController` + `PublicCopilotController` | `ecosistema_jaraba_core/src/Controller/DemoController.php` (246 ln), `jaraba_copilot_v2/src/Controller/PublicCopilotController.php` (547 ln) | 793 | Pagina interactiva con copilot embebido sin registro |
| GAP-AUD-004 | AI Dashboard GEO | `_ai-dashboard.scss` + template | `ecosistema_jaraba_theme/scss/_ai-dashboard.scss` (389 ln) | 389 | Geovisualizacion con Chart.js, export CSV |
| GAP-AUD-005 | llms.txt MCP Discovery | `LlmsTxtController` + `web/llms.txt` | `ecosistema_jaraba_core/src/Controller/LlmsTxtController.php` (255 ln), `web/llms.txt` (60 ln) | 315 | Seccion MCP server discovery, 7 Gen 2 agents, tools listing |
| GAP-AUD-006 | Schema.org GEO | `SchemaGeneratorService` + `SchemaOrgService` | `jaraba_site_builder/src/Service/SchemaGeneratorService.php` (471 ln), `jaraba_page_builder/src/Service/SchemaOrgService.php` (529 ln) | 1000 | BlogPosting speakable, FAQPage, HowTo, GEO attributes |
| GAP-AUD-007 | Dark Mode AI | `_dark-mode.scss` | `ecosistema_jaraba_theme/scss/features/_dark-mode.scss` (76 ln) | 76 | Variables dark mode para copilot, dashboard IA, Command Bar |

### 2.2 True gaps nuevos (16)

| GAP ID | Nombre | Patron a reutilizar | Modulo destino |
|--------|--------|---------------------|----------------|
| GAP-AUD-008 | Command Bar (Cmd+K) | `CommandRegistryService` con tagged services `jaraba.command_provider` | `ecosistema_jaraba_core` + `ecosistema_jaraba_theme` |
| GAP-AUD-009 | Inline AI | `SmartBaseAgent::callAiApi()` fast tier + slide-panel (SLIDE-PANEL-RENDER-001) | `jaraba_ai_agents` + `ecosistema_jaraba_core` |
| GAP-AUD-010 | Proactive Intelligence | ContentEntity + QueueWorker cron + `SmartBaseAgent` balanced tier | `jaraba_ai_agents` |
| GAP-AUD-011 | Voice AI | Web Speech API + `StreamingOrchestratorService` pipeline | `jaraba_copilot_v2` |
| GAP-AUD-012 | A2A Protocol | `McpServerController` JSON-RPC 2.0 extension | `jaraba_ai_agents` |
| GAP-AUD-013 | Vision/Multimodal | `MultiModalBridgeService` stub + Claude Vision API | `jaraba_ai_agents` + `jaraba_copilot_v2` |
| GAP-AUD-014 | AI Test Coverage | PHPUnit suites Unit + Kernel (CI-KERNEL-001) | Multi-modulo |
| GAP-AUD-015 | Prompt Regression | Golden fixtures + `PromptRegressionTestBase` | `jaraba_ai_agents` |
| GAP-AUD-016 | Blog Slugs | `ContentArticle::baseFieldDefinitions()` + `ParamConverter` | `jaraba_content_hub` |
| GAP-AUD-017 | Content Hub tenant_id | TENANT-ISOLATION-ACCESS-001 patron | `jaraba_content_hub` |
| GAP-AUD-018 | Skill Inference | `SkillsService` (335 ln) + `SmartBaseAgent` balanced | `jaraba_candidate` |
| GAP-AUD-019 | Adaptive Learning | `AdaptiveLearningService` (237 ln, unused `$aiAgent`) + SmartBaseAgent | `jaraba_lms` |
| GAP-AUD-020 | Demand Forecasting | `ComercioAnalyticsService` SQL + SmartBaseAgent premium | `jaraba_comercio_conecta` |
| GAP-AUD-021 | AI Writing in GrapesJS | CANVAS-ARTICLE-001 + `InlineAiService` | `jaraba_content_hub` |
| GAP-AUD-022 | Service Matching | `ServiceMatchingService` (241 ln, Qdrant wired) | `jaraba_servicios_conecta` |
| GAP-AUD-023 | Design System Docs | `DesignTokenConfig` entity + theme SCSS | `ecosistema_jaraba_core` |

### 2.3 Infrastructure gaps (2)

| GAP ID | Nombre | Componentes relacionados | Enfoque |
|--------|--------|--------------------------|---------|
| GAP-AUD-024 | Cost Attribution per Tenant | `AIObservabilityService`, `CostAlertService`, `TenantMeteringService` | Conectar observability logs con billing metering |
| GAP-AUD-025 | Horizontal Scaling AI | Redis queues, `ScheduledAgentWorker`, `StreamingOrchestratorService` | Worker pool aislado para AI workloads |

---

## 3. Tabla de Cumplimiento de Directrices

| Directriz | Prioridad | Estado | Donde se aplica |
|-----------|-----------|--------|-----------------|
| ZERO-REGION-POLICY | P0 | Cumple | Command Bar overlay, todas las paginas nuevas usan partials `{% include %}` |
| PREMIUM-FORMS-PATTERN-001 | P1 | Cumple | ProactiveInsight y SkillProfile forms extienden `PremiumEntityFormBase` con `getSectionDefinitions()` y `getFormIcon()` |
| ICON-CONVENTION-001 | P0 | Cumple | `jaraba_icon('category', 'name', ...)` en Command Bar, Inline AI sparkles, alertas proactivas, voice button |
| ICON-DUOTONE-001 | P1 | Cumple | `variant: 'duotone'` en features premium (Command Bar, Inline AI, Proactive Insights) |
| ICON-COLOR-001 | P1 | Cumple | Solo `azul-corporativo` (#233D63), `naranja-impulso` (#FF8C42), `verde-innovacion` (#00A9A5) en iconos coloreados |
| CSRF-API-001 | P0 | Cumple | Todas las rutas API nuevas (Command Bar search, Inline AI suggest, A2A task, voice transcribe) usan `_csrf_request_header_token: 'TRUE'` |
| TWIG-XSS-001 | P0 | Cumple | `\|safe_html` para contenido IA renderizado en DOM, NUNCA `\|raw` |
| TENANT-ISOLATION-ACCESS-001 | P0 | Cumple | ContentArticle tenant_id (GAP-AUD-017), ProactiveInsight tenant_id, SkillProfile tenant_id — access handler verifica `isSameTenant()` |
| ENTITY-PREPROCESS-001 | P1 | Cumple | `template_preprocess_proactive_insight()` y `template_preprocess_skill_profile()` extraen datos para Twig |
| ROUTE-LANGPREFIX-001 | P0 | Cumple | `Drupal.url()` en todos los JS nuevos (command-bar.js, inline-ai-trigger.js, voice-input.js) — NUNCA paths hardcodeados |
| PRESAVE-RESILIENCE-001 | P1 | Cumple | `\Drupal::hasService()` + try-catch en presave hooks de ProactiveInsight y SkillProfile |
| AI-IDENTITY-001 | P0 | Cumple | `AIIdentityRule::apply()` en todos los prompts nuevos (Inline AI, Proactive Engine, Skill Inference, Adaptive Learning, Demand Forecasting) |
| AI-COMPETITOR-001 | P0 | Cumple | Ningun prompt nuevo menciona ni recomienda plataformas competidoras |
| CSS-STICKY-001 | P0 | Cumple | Command Bar overlay usa `position: fixed` con `z-index` superior al header sticky |
| DART-SASS-MODERN | P1 | Cumple | `@use` (no `@import`), `color.adjust()` (no `darken()`/`lighten()`), `var(--ej-*)` con fallback |
| MOBILE-FIRST | P1 | Cumple | Todos los componentes UI nuevos diseñados para 320px+ primero, breakpoints `$ej-bp-*` |
| i18n | P0 | Cumple | `{% trans %}` en Twig, `$this->t()` en PHP, `Drupal.t()` en JS — TODOS los strings de interfaz |
| SLIDE-PANEL-RENDER-001 | P0 | Cumple | Inline AI sugerencias como slide-panels via `renderPlain()` con `$form['#action']` explicito |
| INNERHTML-XSS-001 | P0 | Cumple | `Drupal.checkPlain()` para respuestas IA insertadas en DOM via JS |
| CSRF-JS-CACHE-001 | P1 | Cumple | Promise cacheada para CSRF token en command-bar.js e inline-ai-trigger.js |
| STREAMING-REAL-001 | P1 | Cumple | Voice AI usa `StreamingOrchestratorService` existente para respuestas streaming |
| MCP-SERVER-001 | P1 | Cumple | A2A Protocol extiende patron de `McpServerController` JSON-RPC 2.0 |
| TRACE-CONTEXT-001 | P1 | Cumple | Command Bar, Inline AI, Proactive Engine — todos propagan trace_id via `TraceContextService` |
| LEGAL-CONFIG-001 | P1 | Cumple | Contenido configurable desde Theme Settings UI en `/admin/appearance/settings/ecosistema_jaraba_theme` |
| CANVAS-ARTICLE-001 | P1 | Cumple | AI Writing Assistant en GrapesJS usa library dependency `jaraba_page_builder/grapesjs-canvas` sin duplicar |
| ACCESS-STRICT-001 | P0 | Cumple | `(int) === (int)` en todos los access handlers nuevos |
| SERVICE-CALL-CONTRACT-001 | P0 | Cumple | Toda llamada a servicio inyectado verificada contra firma exacta (args, orden, tipos) |
| SMART-AGENT-DI-001 | P0 | Cumple | Nuevos agentes respetan constructor de 10 args con 3 opcionales `@?` |
| MODEL-ROUTING-CONFIG-001 | P1 | Cumple | Tiers (fast/balanced/premium) desde YAML config, no hardcodeados |
| AI-GUARDRAILS-PII-001 | P0 | Cumple | PII español (DNI, NIE, IBAN ES, NIF/CIF, +34) detectado en inputs y masked en outputs |

---

## 4. Requisitos Previos

### Software

| Componente | Version requerida | Notas |
|-----------|-------------------|-------|
| PHP | 8.4+ | Con extensiones: curl, gd, json, mbstring, openssl, pdo_mysql, redis, sodium |
| Drupal | 11.x | Core estable |
| MariaDB | 10.11+ | Para Kernel tests y produccion |
| Redis | 7.4+ | Cache, queues, flood |
| Node.js | 18+ | Para compilacion SCSS (sass) |
| Sass (Dart) | 1.70+ | `sass scss/main.scss:css/main.css --style=compressed` |
| Composer | 2.x | Dependencias PHP |

### Modulos custom requeridos

| Modulo | Proposito |
|--------|-----------|
| `ecosistema_jaraba_core` | Nucleo: TenantBridge, Guardrails, AIIdentityRule, PremiumEntityFormBase, CommandBar |
| `jaraba_ai_agents` | Stack IA: SmartBaseAgent, ModelRouter, ToolRegistry, McpServer, TraceContext |
| `jaraba_copilot_v2` | Copilot: Orchestrator, Streaming, PublicCopilot |
| `jaraba_content_hub` | Blog: ContentArticle, Canvas Editor, Blog, Categories |
| `jaraba_candidate` | Empleabilidad: SkillsService, CopilotInsights, ProfileSections |
| `jaraba_lms` | Formacion: AdaptiveLearningService, Courses, Enrollments |
| `jaraba_comercio_conecta` | Comercio: MerchantCopilot, Analytics, Products |
| `jaraba_servicios_conecta` | Servicios: ServiceMatchingService, Providers, Bookings |
| `jaraba_onboarding` | Onboarding: WizardService, OrchestratorService, Progress |
| `jaraba_page_builder` | Page Builder: GrapesJS Canvas, Templates, SchemaOrg |
| `jaraba_site_builder` | Site Builder: SchemaGenerator, SeoConfig, SitePageTree |
| `jaraba_usage_billing` | Billing: UsageAggregator, UsagePricing, Alerts |
| `jaraba_rag` | RAG: JarabaRagService, LlmReRanker, Embeddings |
| `jaraba_agents` | Agentes autonomos: ScheduledWorker, LongTermMemory, SharedMemory |
| `ecosistema_jaraba_theme` | Tema: SCSS, templates, partials, JS |

### Servicios externos

| Servicio | Uso | Requerido para |
|----------|-----|----------------|
| OpenAI API / Claude API | LLM calls via Drupal AI module | Todos los gaps IA |
| Qdrant | Vector search (semantic cache, memory, matching) | GAP-AUD-018, 019, 022 |
| Web Speech API (browser) | Speech-to-text client-side | GAP-AUD-011 |
| Stripe | Payment metering | GAP-AUD-002 |

### Documentos de referencia

| Documento | Ubicacion |
|-----------|-----------|
| Plan Elevacion IA Nivel5 v2 | `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Clase_Mundial_v2.md` |
| Arquitectura IA Nivel5 | `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md` |
| Plan Elevacion IA v1 | `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md` |
| Directrices v81 | `docs/00_DIRECTRICES_PROYECTO.md` |
| Flujo trabajo v36 | `docs/00_FLUJO_TRABAJO_CLAUDE.md` |
| Theming architecture | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` |

---

## 5. Entorno de Desarrollo

### Comandos habituales

```bash
# Arrancar entorno
lando start

# Cache
lando drush cr

# SCSS compilacion
cd web/themes/custom/ecosistema_jaraba_theme
sass scss/main.scss:css/main.css --style=compressed

# Tests unitarios
lando php vendor/bin/phpunit --testsuite=Unit

# Tests kernel
lando php vendor/bin/phpunit --testsuite=Kernel

# Lint PHP
lando php -l web/modules/custom/jaraba_ai_agents/src/Service/InlineAiService.php

# Lint YAML
lando php vendor/bin/yaml-lint web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.services.yml

# Update hooks
lando drush updb -y

# Export config
lando drush cex -y
```

### URLs de desarrollo

| URL | Proposito |
|-----|-----------|
| `https://jarabaimpact.lndo.site` | Meta-sitio principal |
| `https://jarabaimpact.lndo.site/admin` | Admin Drupal |
| `https://jarabaimpact.lndo.site/blog` | Blog publico |
| `https://jarabaimpact.lndo.site/demo` | Demo interactivo |
| `https://jarabaimpact.lndo.site/planes` | Pricing page |
| `https://jarabaimpact.lndo.site/api/v1/mcp` | MCP server |

### Variables de entorno relevantes

```
AI_PROVIDER_API_KEY=sk-...       # OpenAI / Anthropic API key
QDRANT_URL=http://qdrant:6333    # Qdrant vector DB
REDIS_HOST=redis                  # Redis for queues/cache
```

---

## 6. Refinamiento de Componentes Existentes (GAP-AUD-001 a GAP-AUD-007)

### 6.1 GAP-AUD-001: Onboarding Wizard AI

**Estado actual:**

El onboarding wizard es un flujo completo de 7 pasos con codigo funcional en 2 servicios + 1 controlador:

- `TenantOnboardingWizardService` (409 lineas): 7 pasos (Welcome, Identity, Fiscal, Payments, Team, Content, Launch), validacion NIF/CIF/NIE, vertical-aware step skipping (commerce vs non-commerce), progress tracking
- `OnboardingOrchestratorService` (217 lineas): CRUD orchestrator con template-based workflows
- `TenantOnboardingWizardController` (499 lineas): 7 step routes + 3 API endpoints + Stripe callback, DriverJS guided tours, logo color extraction

**Que falta:**

Recomendaciones AI personalizadas durante el onboarding. Actualmente los pasos son estaticos — no hay inteligencia que sugiera configuraciones basadas en la vertical, tamano del negocio o objetivos declarados.

**Arquitectura de la mejora:**

```
┌─────────────────────────────────────────────────────────────┐
│                   ONBOARDING AI LAYER                       │
│                                                             │
│  TenantOnboardingWizardController                          │
│       │                                                     │
│       ▼                                                     │
│  OnboardingAiRecommendationService (NUEVO)                 │
│       │                                                     │
│       ├─── SmartBaseAgent::callAiApi() [balanced tier]     │
│       │         │                                           │
│       │         ├── System prompt: vertical + step context  │
│       │         ├── AIIdentityRule::apply()                 │
│       │         └── Guardrails PII check                    │
│       │                                                     │
│       └─── Recommendations cache (Redis, 24h TTL)          │
│                                                             │
│  Output: { suggestions: [], confidence: float }            │
└─────────────────────────────────────────────────────────────┘
```

**Archivos a crear:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `jaraba_onboarding/src/Service/OnboardingAiRecommendationService.php` | NUEVO | Servicio que usa SmartBaseAgent balanced tier para generar recomendaciones contextuales por step |

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_onboarding/jaraba_onboarding.services.yml` | Registrar `OnboardingAiRecommendationService` con `@?jaraba_ai_agents.smart_marketing_agent` |
| `jaraba_onboarding/src/Controller/TenantOnboardingWizardController.php` | Inyectar servicio opcional, mostrar recomendaciones en cada step |

**Patron de codigo:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;

/**
 * AI-powered recommendations for onboarding wizard steps.
 */
class OnboardingAiRecommendationService {

  public function __construct(
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?object $aiAgent = NULL,
  ) {}

  /**
   * Generate AI recommendations for a given onboarding step.
   *
   * @param string $vertical
   *   The canonical vertical name.
   * @param int $step
   *   Current step number (1-7).
   * @param array $previousAnswers
   *   Data from completed steps.
   *
   * @return array
   *   Array with 'suggestions' and 'confidence' keys.
   */
  public function getRecommendations(string $vertical, int $step, array $previousAnswers): array {
    if ($this->aiAgent === NULL) {
      return ['suggestions' => [], 'confidence' => 0.0];
    }

    try {
      $prompt = $this->buildPrompt($vertical, $step, $previousAnswers);
      $systemPrompt = AIIdentityRule::apply(
        'Eres un asistente de configuracion inicial para la vertical ' . $vertical . '.'
      );

      $result = $this->aiAgent->execute('onboarding_recommendation', [
        'prompt' => $prompt,
        'system_prompt' => $systemPrompt,
        'tier' => 'balanced',
      ]);

      return $result['data'] ?? ['suggestions' => [], 'confidence' => 0.0];
    }
    catch (\Throwable $e) {
      $this->logger->warning('AI onboarding recommendation failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['suggestions' => [], 'confidence' => 0.0];
    }
  }

}
```

**Integracion frontend:**

En cada step template, un bloque condicional muestra las recomendaciones IA:

```twig
{% if ai_recommendations and ai_recommendations.suggestions is not empty %}
  <div class="onboarding-ai-suggestions glass-card">
    <div class="ai-suggestions__header">
      {{ jaraba_icon('ui', 'sparkles', { variant: 'duotone', color: 'naranja-impulso' }) }}
      <h4>{{ 'Recomendaciones personalizadas'|t }}</h4>
    </div>
    <ul class="ai-suggestions__list">
      {% for suggestion in ai_recommendations.suggestions %}
        <li>{{ suggestion|safe_html }}</li>
      {% endfor %}
    </ul>
  </div>
{% endif %}
```

---

### 6.2 GAP-AUD-002: Pricing AI Metering

**Estado actual:**

- `PricingController` (356 lineas): `/planes` page + `/api/v1/pricing/{vertical}` API con SaasPlanTier config entities, FAQ, yearly savings calculation, feature mapping
- `MetaSitePricingService` (197 lineas): Config-driven cascade resolution `{vertical}_{tier}` → `_default_{tier}` → fallback
- `UsageDashboardController` (frontend, 194 lineas): `/mi-cuenta/uso` con 8 metrics + Chart.js + budget alerts

**Que falta:**

Metering de tokens AI por plan. Los planes definen `ai_queries` como limite, pero no hay:
1. Visualizacion del consumo de tokens AI en la pagina de pricing (para que el usuario vea el valor)
2. Barra de progreso de uso AI en el dashboard del tenant
3. Indicador de tokens restantes en el copilot widget

**Arquitectura de la mejora:**

```
┌─────────────────────────────────────────────────────────────┐
│                   AI METERING LAYER                         │
│                                                             │
│  PricingController                                         │
│       │                                                     │
│       ▼                                                     │
│  Parcial _pricing-ai-usage.html.twig (NUEVO)              │
│       │                                                     │
│       └── Muestra: tokens incluidos, tokens premium,       │
│           comparativa por plan, estimacion de uso          │
│                                                             │
│  UsageDashboardController                                  │
│       │                                                     │
│       ▼                                                     │
│  AI Usage Widget (en dashboard existente)                  │
│       │                                                     │
│       └── Barra: usados / limite, proyeccion mensual,     │
│           upgrade CTA si >80%                              │
│                                                             │
│  CopilotStreamController / copilot-chat-widget.js          │
│       │                                                     │
│       ▼                                                     │
│  Token counter badge (en widget existente)                 │
│       └── "N consultas restantes este mes"                 │
└─────────────────────────────────────────────────────────────┘
```

**Archivos a crear:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `ecosistema_jaraba_theme/templates/partials/_pricing-ai-usage.html.twig` | NUEVO | Parcial con tabla comparativa de tokens AI por plan |

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_core/src/Controller/PricingController.php` | Añadir datos de AI metering al render array de `pricingPage()` |
| `ecosistema_jaraba_core/src/Controller/UsageDashboardController.php` | Añadir seccion AI tokens con barra de progreso |
| `jaraba_copilot_v2/js/copilot-chat-widget.js` | Mostrar badge de consultas restantes desde metadata SSE `done` event |

**Template parcial:**

```twig
{# _pricing-ai-usage.html.twig #}
<div class="pricing-ai-usage">
  <h3 class="pricing-ai-usage__title">{{ 'Capacidades de IA incluidas'|t }}</h3>
  <div class="pricing-ai-usage__grid">
    {% for tier in tiers %}
      <div class="pricing-ai-usage__tier {{ tier.is_recommended ? 'is-recommended' }}">
        <h4>{{ tier.label|t }}</h4>
        <div class="pricing-ai-usage__metric">
          {{ jaraba_icon('ui', 'sparkles', { variant: 'duotone', color: 'naranja-impulso', size: '20px' }) }}
          <span class="metric-value">{{ tier.ai_queries|number_format }}</span>
          <span class="metric-label">{{ 'consultas IA/mes'|t }}</span>
        </div>
        <ul class="pricing-ai-usage__features">
          {% for feature in tier.ai_features %}
            <li>{{ feature|t }}</li>
          {% endfor %}
        </ul>
      </div>
    {% endfor %}
  </div>
</div>
```

---

### 6.3 GAP-AUD-003: Demo/Playground Publico

**Estado actual:**

- `DemoController` (246 lineas): Demo flow con profile selection, session management, TTFV tracking, DriverJS tours, Chart.js metrics, AI storytelling demo, demo-to-real conversion
- `PublicCopilotController` (547 lineas): `POST /api/v1/copilot/public/chat` con rate limiting (10 req/min), RAG, sales prompt, keyword fallback, feedback collection

**Que falta:**

Pagina interactiva de "playground" donde un visitante no registrado puede probar el copilot IA en tiempo real con datos de demo, sin necesidad de crear cuenta. Actualmente `/demo` requiere seleccionar un perfil y tiene un flow guiado — no es un sandbox libre.

**Arquitectura de la mejora:**

```
┌─────────────────────────────────────────────────────────────┐
│                   AI PLAYGROUND                             │
│                                                             │
│  Nueva ruta: GET /demo/ai-playground                       │
│       │                                                     │
│       ▼                                                     │
│  DemoController::aiPlayground() (NUEVO metodo)             │
│       │                                                     │
│       ├── Template: demo-ai-playground.html.twig           │
│       │     ├── Copilot widget (PublicCopilotController)   │
│       │     ├── Ejemplo prompts sugeridos                  │
│       │     ├── Indicador rate limit (10/min)              │
│       │     └── CTA registro para acceso completo          │
│       │                                                     │
│       └── Cache: 1h, public, vary on nothing              │
│                                                             │
│  JS: demo-ai-playground.js                                 │
│       ├── Prompt chips clickeables                         │
│       ├── Typing animation                                 │
│       └── Conversion tracking                              │
└─────────────────────────────────────────────────────────────┘
```

**Archivos a crear:**

| Archivo | Tipo |
|---------|------|
| `ecosistema_jaraba_theme/templates/demo-ai-playground.html.twig` | NUEVO |
| `ecosistema_jaraba_theme/js/demo-ai-playground.js` | NUEVO |

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_core/src/Controller/DemoController.php` | Nuevo metodo `aiPlayground()` |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | Nueva ruta `ecosistema_jaraba_core.demo.ai_playground` |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.libraries.yml` | Nueva library `demo-ai-playground` |

---

### 6.4 GAP-AUD-004: AI Dashboard GEO Enhancement

**Estado actual:**

El AI dashboard tiene 389 lineas de SCSS (`_ai-dashboard.scss`) y un template de 176 lineas (`jaraba-ai-dashboard.html.twig`) con metricas de observabilidad: tokens consumidos, latencias, error rates, costes por tier.

**Que falta:**

1. Geovisualizacion: mapa de uso por region/pais con Chart.js (no requiere Google Maps — usar chart tipo bar horizontal o doughnut)
2. Export CSV de logs de observabilidad
3. Filtros por fecha, agente, tier, vertical
4. Tendencias con sparklines

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_ai_agents/src/Service/AIObservabilityService.php` | Nuevo metodo `getUsageByRegion()` y `exportCsv()` |
| Template del dashboard | Añadir seccion GEO con Chart.js doughnut |
| `_ai-dashboard.scss` | Estilos para GEO chart, sparklines, filtros |

**Patron de Chart.js para GEO:**

```javascript
// En el JS del dashboard
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.aiDashboardGeo = {
    attach: function (context) {
      once('ai-dashboard-geo', '[data-ai-geo-chart]', context).forEach(function (element) {
        var data = JSON.parse(element.dataset.aiGeoData);
        new Chart(element, {
          type: 'doughnut',
          data: {
            labels: data.labels,
            datasets: [{
              data: data.values,
              backgroundColor: [
                'var(--ej-color-corporate, #233D63)',
                'var(--ej-color-impulse, #FF8C42)',
                'var(--ej-color-innovation, #00A9A5)',
                'var(--ej-color-agro, #556B2F)'
              ]
            }]
          },
          options: {
            plugins: {
              title: {
                display: true,
                text: Drupal.t('Uso IA por region')
              }
            }
          }
        });
      });
    }
  };
})(Drupal, once);
```

---

### 6.5 GAP-AUD-005: llms.txt MCP Discovery

**Estado actual:**

- `LlmsTxtController` (255 lineas): Genera llms.txt dinamicamente con site name, slogan, product/node counts. Sigue standard llmstxt.org. Cache 1 dia.
- `web/llms.txt` (60 lineas): Version estatica listando 4 agentes, 2 endpoints, compliance GDPR

**Que falta:**

1. Listar los 7 Gen 2 smart agents (no solo 4)
2. Seccion MCP server discovery: `POST /api/v1/mcp` con capabilities
3. Listado de tools disponibles desde ToolRegistry
4. Endpoints streaming: `POST /api/v1/copilot/stream`
5. Schema.org references
6. Per-vertical differentiation

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_core/src/Controller/LlmsTxtController.php` | Inyectar `ToolRegistry` (optional), expandir `buildLlmsTxtContent()` con MCP section, tools, streaming |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` | Añadir `@?jaraba_ai_agents.tool_registry` al constructor |
| `web/llms.txt` | Actualizar version estatica como fallback |

**Contenido a añadir en llms.txt:**

```
## MCP Server (Model Context Protocol)
- Endpoint: POST /api/v1/mcp
- Protocol: JSON-RPC 2.0
- Version: 2025-11-25
- Authentication: Bearer token + CSRF header
- Capabilities: tools (list, call)

## AI Agents (Gen 2 — SmartBaseAgent)
- SmartMarketing Agent: brand storytelling, social posts, email campaigns
- Storytelling Agent: narrative generation, brand stories
- CustomerExperience Agent: satisfaction analysis, feedback response
- Support Agent: ticket resolution, FAQ generation
- ProducerCopilot Agent: agricultural guidance, market analysis
- Sales Agent: lead qualification, proposal generation
- MerchantCopilot Agent: product descriptions, pricing suggestions

## Available Tools
[Dynamic from ToolRegistry — SendEmail, CreateEntity, SearchKnowledge, etc.]

## Streaming
- Endpoint: POST /api/v1/copilot/stream
- Protocol: Server-Sent Events (SSE)
- Events: chunk, cached, done, error, thinking, mode
```

---

### 6.6 GAP-AUD-006: Schema.org GEO Enhancement

**Estado actual:**

Dos servicios complementarios:
- `SchemaGeneratorService` (471 lineas): 9 Schema.org types (WebPage, Article, BlogPosting, FAQPage, Product, LocalBusiness, Organization, WebSite, BreadcrumbList), tenant-aware
- `SchemaOrgService` (529 lineas): 6 types verticales (FAQPage, BreadcrumbList, JobPosting, Course, LocalBusiness, Product), Google Rich Results compliant

**Que falta para GEO (Generative Engine Optimization):**

1. `BlogPosting` con `speakable` property (para asistentes de voz)
2. `FAQPage` con preguntas reales del copilot (mas frecuentes)
3. `HowTo` schema para tutoriales del LMS
4. `LocalBusiness` GEO attributes: `areaServed`, `geo` con coordenadas reales
5. `SoftwareApplication` schema para el SaaS mismo
6. `AIProduct` custom property para los agentes (schema.org extension)

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_site_builder/src/Service/SchemaGeneratorService.php` | Añadir `generateSoftwareApplication()`, `speakable` en `generateArticle()` |
| `jaraba_page_builder/src/Service/SchemaOrgService.php` | Añadir `generateHowTo()`, GEO attributes en `generateLocalBusinessSchema()` |
| `jaraba_content_hub/jaraba_content_hub.module` | Añadir `speakable` y `answer_capsule` en preprocess |

**Patron para speakable en BlogPosting:**

```php
// En SchemaGeneratorService::generateArticle()
$schema = [
  '@context' => 'https://schema.org',
  '@type' => 'BlogPosting',
  'headline' => $title,
  'speakable' => [
    '@type' => 'SpeakableSpecification',
    'cssSelector' => ['.article-body__content h2', '.article-body__content p:first-of-type'],
  ],
];

// Si tiene answer_capsule (GEO optimization)
if (!empty($answerCapsule)) {
  $schema['description'] = $answerCapsule;
}
```

---

### 6.7 GAP-AUD-007: Dark Mode AI Components

**Estado actual:**

`_dark-mode.scss` (76 lineas) cubre 14 CSS custom properties para body, surface, text, cards, inputs y toggle button. Tiene companion JS para toggle.

**Que falta:**

Variables dark mode para:
1. Copilot chat widget (burbujas, input, header)
2. AI Dashboard (charts, sparklines, badges)
3. Command Bar (overlay, resultados, highlight)
4. Inline AI (sparkle icon, sugerencias panel)
5. Proactive insights (notification bell, cards)
6. `prefers-color-scheme: dark` media query para auto-deteccion

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_theme/scss/features/_dark-mode.scss` | Ampliar de 14 a ~40 CSS custom properties |

**SCSS a añadir:**

```scss
@use '../variables' as *;
@use 'sass:color';

.dark-mode {
  // Existing variables preserved...

  // Copilot widget
  --ej-copilot-bg: #{$ej-color-bg-dark};
  --ej-copilot-bubble-user: #{color.adjust($ej-color-impulse, $lightness: -15%)};
  --ej-copilot-bubble-ai: #2D2D42;
  --ej-copilot-input-bg: #1F1F35;
  --ej-copilot-input-border: #4A4A6A;

  // AI Dashboard
  --ej-dashboard-chart-bg: #252538;
  --ej-dashboard-sparkline: #{$ej-color-innovation};
  --ej-dashboard-badge-bg: #2D2D42;

  // Command Bar
  --ej-command-bar-overlay: rgba(0, 0, 0, 0.75);
  --ej-command-bar-bg: #1F1F35;
  --ej-command-bar-result-hover: #2D2D42;
  --ej-command-bar-highlight: #{$ej-color-impulse};

  // Inline AI
  --ej-inline-ai-sparkle: #{$ej-color-impulse};
  --ej-inline-ai-panel-bg: #252538;

  // Proactive insights
  --ej-insight-card-bg: #252538;
  --ej-insight-severity-high: #{color.adjust($ej-color-ui-danger, $lightness: -10%)};
  --ej-insight-severity-medium: #{color.adjust($ej-color-ui-warning, $lightness: -10%)};
  --ej-insight-severity-low: #{color.adjust($ej-color-ui-success, $lightness: -10%)};

  // Charts
  --ej-chart-grid: #374151;
  --ej-chart-text: #94A3B8;
}

// Auto-detect system preference
@media (prefers-color-scheme: dark) {
  body:not(.light-mode-forced) {
    @extend .dark-mode;
  }
}
```

---

## 7. Command Bar / Spotlight (Cmd+K) — GAP-AUD-008

Este es el gap mas grande en UX IA. Todas las plataformas competidoras (Salesforce, HubSpot, Notion, Linear) ofrecen un Command Bar / Spotlight accesible via `Cmd+K` (Mac) / `Ctrl+K` (Win/Linux).

### 7.1 Decision arquitectonica

```
┌─────────────────────────────────────────────────────────────────────┐
│                    COMMAND BAR ARCHITECTURE                         │
│                                                                     │
│  User types Cmd+K                                                  │
│       │                                                             │
│       ▼                                                             │
│  command-bar.js (Drupal.behaviors)                                 │
│       │                                                             │
│       ├── Overlay + input field                                    │
│       ├── Debounced input (300ms)                                  │
│       │                                                             │
│       ▼                                                             │
│  GET /api/v1/command-bar/search?q={query}                          │
│       │     (CSRF header, tenant_id from session)                  │
│       │                                                             │
│       ▼                                                             │
│  CommandBarController                                              │
│       │                                                             │
│       ▼                                                             │
│  CommandRegistryService                                            │
│       │                                                             │
│       ├── NavigationCommandProvider (tagged)                       │
│       │     └── Rutas admin, content, config                       │
│       │                                                             │
│       ├── EntitySearchCommandProvider (tagged)                     │
│       │     └── ContentArticle, PageContent, Product, etc.        │
│       │                                                             │
│       ├── ActionCommandProvider (tagged)                           │
│       │     └── Create article, Clear cache, Export, etc.         │
│       │                                                             │
│       └── AiSearchCommandProvider (tagged)                         │
│             └── SmartBaseAgent fast tier (solo si >3 chars y       │
│                 otros providers retornan <3 resultados)            │
│                                                                     │
│  Results: [{ type, label, description, url, icon, shortcut }]     │
│                                                                     │
│  Fuzzy matching: Fuse.js client-side para navegacion estatica     │
│  Server search: API call para entidades y AI                      │
└─────────────────────────────────────────────────────────────────────┘
```

### 7.2 CommandRegistryService

Tagged service collector que agrega resultados de multiples providers.

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Session\AccountProxyInterface;

/**
 * Registry for command bar providers.
 *
 * Collects tagged services implementing CommandProviderInterface.
 */
class CommandRegistryService {

  /**
   * @var \Drupal\ecosistema_jaraba_core\CommandProviderInterface[]
   */
  protected array $providers = [];

  public function __construct(
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Add a command provider (called by service collector).
   */
  public function addProvider(CommandProviderInterface $provider): void {
    $this->providers[] = $provider;
  }

  /**
   * Search across all providers.
   *
   * @param string $query
   *   The search query (min 1 char).
   * @param int $limit
   *   Max results per provider.
   *
   * @return array
   *   Merged and sorted results.
   */
  public function search(string $query, int $limit = 10): array {
    $results = [];
    foreach ($this->providers as $provider) {
      if ($provider->isAccessible($this->currentUser)) {
        $providerResults = $provider->search($query, $limit);
        $results = array_merge($results, $providerResults);
      }
    }

    // Sort by relevance score descending.
    usort($results, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

    return array_slice($results, 0, $limit);
  }

}
```

**Service tag registration en services.yml:**

```yaml
services:
  ecosistema_jaraba_core.command_registry:
    class: Drupal\ecosistema_jaraba_core\Service\CommandRegistryService
    arguments: ['@current_user']
    tags:
      - { name: service_collector, tag: jaraba.command_provider, call: addProvider }

  ecosistema_jaraba_core.command_provider.navigation:
    class: Drupal\ecosistema_jaraba_core\CommandProvider\NavigationCommandProvider
    arguments: ['@router.route_provider', '@current_user']
    tags:
      - { name: jaraba.command_provider }

  ecosistema_jaraba_core.command_provider.entity_search:
    class: Drupal\ecosistema_jaraba_core\CommandProvider\EntitySearchCommandProvider
    arguments: ['@entity_type.manager', '@current_user']
    tags:
      - { name: jaraba.command_provider }

  ecosistema_jaraba_core.command_provider.actions:
    class: Drupal\ecosistema_jaraba_core\CommandProvider\ActionCommandProvider
    arguments: ['@current_user']
    tags:
      - { name: jaraba.command_provider }
```

### 7.3 CommandBarController

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for the Command Bar search.
 */
class CommandBarController extends ControllerBase {

  public function search(Request $request): CacheableJsonResponse {
    $query = trim((string) $request->query->get('q', ''));

    if (mb_strlen($query) < 1) {
      return new CacheableJsonResponse(['results' => []], 200);
    }

    $registry = \Drupal::service('ecosistema_jaraba_core.command_registry');
    $results = $registry->search($query, 10);

    $response = new CacheableJsonResponse(['results' => $results]);
    $cacheMetadata = new CacheableMetadata();
    $cacheMetadata->setCacheMaxAge(0); // No cache — results depend on user permissions
    $cacheMetadata->addCacheContexts(['user.permissions', 'url.query_args:q']);
    $response->addCacheableDependency($cacheMetadata);

    return $response;
  }

}
```

**Ruta:**

```yaml
ecosistema_jaraba_core.command_bar.search:
  path: '/api/v1/command-bar/search'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\CommandBarController::search'
  requirements:
    _permission: 'access content'
    _csrf_request_header_token: 'TRUE'
  options:
    no_cache: TRUE
```

### 7.4 Frontend command-bar.js

```javascript
/**
 * @file
 * Command Bar (Cmd+K / Ctrl+K) behavior.
 */
(function (Drupal, once) {
  'use strict';

  var csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then(function (r) { return r.text(); });
    }
    return csrfTokenPromise;
  }

  Drupal.behaviors.commandBar = {
    attach: function (context) {
      once('command-bar', 'body', context).forEach(function () {
        var overlay = document.querySelector('[data-command-bar-overlay]');
        var input = document.querySelector('[data-command-bar-input]');
        var results = document.querySelector('[data-command-bar-results]');
        var debounceTimer = null;
        var selectedIndex = -1;

        if (!overlay || !input || !results) {
          return;
        }

        // Cmd+K / Ctrl+K to open
        document.addEventListener('keydown', function (e) {
          if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            toggleOverlay();
          }
          if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
            closeOverlay();
          }
        });

        // Debounced search
        input.addEventListener('input', function () {
          clearTimeout(debounceTimer);
          var query = input.value.trim();
          if (query.length < 1) {
            results.innerHTML = '';
            return;
          }
          debounceTimer = setTimeout(function () {
            performSearch(query);
          }, 300);
        });

        // Keyboard navigation
        input.addEventListener('keydown', function (e) {
          var items = results.querySelectorAll('[data-command-result]');
          if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
          }
          if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            updateSelection(items);
          }
          if (e.key === 'Enter' && selectedIndex >= 0 && items[selectedIndex]) {
            e.preventDefault();
            var url = items[selectedIndex].dataset.commandUrl;
            if (url) {
              window.location.href = url;
            }
          }
        });

        function toggleOverlay() {
          overlay.classList.toggle('is-open');
          if (overlay.classList.contains('is-open')) {
            input.value = '';
            results.innerHTML = '';
            selectedIndex = -1;
            input.focus();
          }
        }

        function closeOverlay() {
          overlay.classList.remove('is-open');
        }

        function performSearch(query) {
          getCsrfToken().then(function (token) {
            return fetch(Drupal.url('api/v1/command-bar/search') + '?q=' + encodeURIComponent(query), {
              headers: {
                'X-CSRF-Token': token,
                'Content-Type': 'application/json'
              }
            });
          })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            renderResults(data.results || []);
          })
          .catch(function () {
            results.innerHTML = '<div class="command-bar__empty">' +
              Drupal.t('Error al buscar') + '</div>';
          });
        }

        function renderResults(items) {
          selectedIndex = -1;
          if (items.length === 0) {
            results.innerHTML = '<div class="command-bar__empty">' +
              Drupal.t('Sin resultados') + '</div>';
            return;
          }
          var html = '';
          items.forEach(function (item, idx) {
            html += '<div class="command-bar__result" data-command-result data-command-url="' +
              Drupal.checkPlain(item.url) + '" tabindex="-1">' +
              '<span class="command-bar__result-label">' + Drupal.checkPlain(item.label) + '</span>' +
              '<span class="command-bar__result-desc">' + Drupal.checkPlain(item.description || '') + '</span>' +
              '</div>';
          });
          results.innerHTML = html;
        }

        function updateSelection(items) {
          items.forEach(function (item, idx) {
            item.classList.toggle('is-selected', idx === selectedIndex);
          });
          if (items[selectedIndex]) {
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
          }
        }

        // Close on overlay background click
        overlay.addEventListener('click', function (e) {
          if (e.target === overlay) {
            closeOverlay();
          }
        });
      });
    }
  };
})(Drupal, once);
```

### 7.5 SCSS _command-bar.scss

```scss
@use '../variables' as *;
@use 'sass:color';

// Command Bar Overlay
.command-bar-overlay {
  position: fixed;
  inset: 0;
  z-index: 10000;
  display: none;
  align-items: flex-start;
  justify-content: center;
  padding-top: 20vh;
  background: var(--ej-command-bar-overlay, rgba(0, 0, 0, 0.5));
  backdrop-filter: blur(4px);

  &.is-open {
    display: flex;
  }
}

// Command Bar Modal
.command-bar {
  width: 100%;
  max-width: 640px;
  margin: 0 $ej-spacing-md;
  background: var(--ej-command-bar-bg, var(--ej-bg-surface, #fff));
  border-radius: $ej-border-radius;
  box-shadow: $ej-shadow-lg;
  overflow: hidden;
}

// Input
.command-bar__input {
  width: 100%;
  padding: $ej-spacing-md $ej-spacing-lg;
  border: none;
  border-bottom: 1px solid var(--ej-border-color, $ej-color-border);
  font-size: 1.125rem;
  font-family: $ej-font-body;
  background: transparent;
  color: var(--ej-color-headings, $ej-color-text-headings);
  outline: none;

  &::placeholder {
    color: var(--ej-color-muted, $ej-color-text-muted);
  }
}

// Results
.command-bar__results {
  max-height: 400px;
  overflow-y: auto;
}

.command-bar__result {
  display: flex;
  flex-direction: column;
  padding: $ej-spacing-sm $ej-spacing-lg;
  cursor: pointer;
  transition: background-color $ej-transition-fast;

  &:hover,
  &.is-selected {
    background: var(--ej-command-bar-result-hover, #{color.adjust($ej-color-bg-body, $lightness: -3%)});
  }

  &-label {
    font-weight: 600;
    color: var(--ej-color-headings, $ej-color-text-headings);
  }

  &-desc {
    font-size: 0.875rem;
    color: var(--ej-color-muted, $ej-color-text-muted);
  }
}

.command-bar__empty {
  padding: $ej-spacing-lg;
  text-align: center;
  color: var(--ej-color-muted, $ej-color-text-muted);
}

// Mobile: full-width
@media (max-width: $ej-bp-sm) {
  .command-bar-overlay {
    padding-top: 0;
    align-items: stretch;
  }

  .command-bar {
    max-width: 100%;
    margin: 0;
    border-radius: 0;
    height: 100vh;
  }

  .command-bar__results {
    max-height: calc(100vh - 60px);
  }
}
```

### 7.6 Twig _command-bar.html.twig

```twig
{# Parcial: _command-bar.html.twig #}
{# Incluido en _header.html.twig: {% include '@ecosistema_jaraba_theme/partials/_command-bar.html.twig' %} #}
{% if command_bar_enabled %}
<div class="command-bar-overlay" data-command-bar-overlay>
  <div class="command-bar" role="dialog" aria-label="{{ 'Busqueda rapida'|t }}">
    <input
      type="text"
      class="command-bar__input"
      data-command-bar-input
      placeholder="{{ 'Buscar paginas, contenido, acciones...'|t }}"
      aria-label="{{ 'Buscar'|t }}"
      autocomplete="off"
    />
    <div class="command-bar__results" data-command-bar-results role="listbox" aria-label="{{ 'Resultados de busqueda'|t }}">
    </div>
    <div class="command-bar__footer">
      <kbd>↑↓</kbd> {{ 'navegar'|t }}
      <kbd>↩</kbd> {{ 'abrir'|t }}
      <kbd>esc</kbd> {{ 'cerrar'|t }}
    </div>
  </div>
</div>
{% endif %}
```

### 7.7 Seguridad

| Aspecto | Implementacion |
|---------|----------------|
| CSRF | Ruta API con `_csrf_request_header_token: 'TRUE'`, JS usa `getCsrfToken()` cacheada |
| XSS | `Drupal.checkPlain()` para todos los resultados insertados en DOM |
| Tenant isolation | `CommandRegistryService` filtra resultados por tenant del usuario |
| Permission | `_permission: 'access content'` en ruta — solo usuarios autenticados con acceso |
| Rate limiting | No requerido (ya filtrado por Drupal flood + CSRF) |

### 7.8 i18n

Strings traducibles en Command Bar:

**Twig:**
- `'Busqueda rapida'|t`
- `'Buscar paginas, contenido, acciones...'|t`
- `'Buscar'|t`
- `'Resultados de busqueda'|t`
- `'navegar'|t`
- `'abrir'|t`
- `'cerrar'|t`

**JS:**
- `Drupal.t('Error al buscar')`
- `Drupal.t('Sin resultados')`

**PHP:**
- `$this->t('Pages')`
- `$this->t('Content')`
- `$this->t('Actions')`
- `$this->t('AI Search')`

### 7.9 Theme Settings

Toggle de Command Bar en Theme Settings:

```php
// En ecosistema_jaraba_theme.theme, dentro de ecosistema_jaraba_theme_form_system_theme_settings_alter()
$form['command_bar'] = [
  '#type' => 'details',
  '#title' => t('Command Bar (Cmd+K)'),
  '#group' => 'ecosistema_jaraba_theme_settings',
];
$form['command_bar']['command_bar_enabled'] = [
  '#type' => 'checkbox',
  '#title' => t('Enable Command Bar'),
  '#default_value' => theme_get_setting('command_bar_enabled') ?? TRUE,
  '#description' => t('Show the Cmd+K / Ctrl+K quick search overlay.'),
];
```

Variable en preprocess:

```php
$variables['command_bar_enabled'] = theme_get_setting('command_bar_enabled') ?? TRUE;
```

**Archivos nuevos completos para Command Bar:**

| Archivo | Tipo |
|---------|------|
| `ecosistema_jaraba_core/src/Service/CommandRegistryService.php` | NUEVO |
| `ecosistema_jaraba_core/src/CommandProvider/CommandProviderInterface.php` | NUEVO |
| `ecosistema_jaraba_core/src/CommandProvider/NavigationCommandProvider.php` | NUEVO |
| `ecosistema_jaraba_core/src/CommandProvider/EntitySearchCommandProvider.php` | NUEVO |
| `ecosistema_jaraba_core/src/CommandProvider/ActionCommandProvider.php` | NUEVO |
| `ecosistema_jaraba_core/src/Controller/CommandBarController.php` | NUEVO |
| `ecosistema_jaraba_theme/js/command-bar.js` | NUEVO |
| `ecosistema_jaraba_theme/scss/components/_command-bar.scss` | NUEVO |
| `ecosistema_jaraba_theme/templates/partials/_command-bar.html.twig` | NUEVO |

---

## 8. Inline AI — GAP-AUD-009

### 8.1 Arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                    INLINE AI ARCHITECTURE                           │
│                                                                     │
│  PremiumEntityFormBase                                             │
│       │                                                             │
│       ├── getInlineAiFields() returns ['title', 'body', ...]     │
│       │                                                             │
│       ▼                                                             │
│  inline-ai-trigger.js                                              │
│       │                                                             │
│       ├── Sparkle icon (✦) next to configured fields              │
│       ├── Click sparkle → POST /api/v1/inline-ai/suggest          │
│       │     Body: { field, value, context, entity_type }          │
│       │     Header: X-CSRF-Token                                   │
│       │                                                             │
│       ▼                                                             │
│  InlineAiController                                                │
│       │                                                             │
│       ▼                                                             │
│  InlineAiService                                                   │
│       │                                                             │
│       ├── SmartBaseAgent::callAiApi() [fast tier]                 │
│       │     ├── System: AIIdentityRule + field context             │
│       │     ├── Guardrails: PII check                              │
│       │     └── Model: Haiku 4.5 (fast, low cost)                 │
│       │                                                             │
│       └── Returns: { suggestions: [string, string, string] }     │
│                                                                     │
│  JS renders suggestions as slide-panel chips                       │
│  User clicks chip → value inserted into field                     │
└─────────────────────────────────────────────────────────────────────┘
```

### 8.2 InlineAiService

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;

/**
 * Service for inline AI suggestions on form fields.
 */
class InlineAiService {

  public function __construct(
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?object $aiAgent = NULL,
  ) {}

  /**
   * Generate suggestions for a form field value.
   *
   * @param string $fieldName
   *   The field machine name.
   * @param string $currentValue
   *   Current field value.
   * @param string $entityType
   *   The entity type ID.
   * @param array $context
   *   Additional context (entity label, other field values).
   *
   * @return array
   *   Array with 'suggestions' key containing 1-3 string suggestions.
   */
  public function suggest(string $fieldName, string $currentValue, string $entityType, array $context = []): array {
    if ($this->aiAgent === NULL) {
      return ['suggestions' => []];
    }

    try {
      $prompt = $this->buildSuggestionPrompt($fieldName, $currentValue, $entityType, $context);
      $result = $this->aiAgent->execute('inline_suggestion', [
        'prompt' => $prompt,
        'tier' => 'fast',
      ]);

      return [
        'suggestions' => array_slice($result['data']['suggestions'] ?? [], 0, 3),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Inline AI suggestion failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['suggestions' => []];
    }
  }

  protected function buildSuggestionPrompt(string $fieldName, string $currentValue, string $entityType, array $context): string {
    $systemPrompt = AIIdentityRule::apply(
      'Eres un asistente de redaccion. Genera 3 sugerencias concisas y relevantes para el campo indicado.', TRUE
    );

    $prompt = "Campo: {$fieldName}\n";
    $prompt .= "Tipo de entidad: {$entityType}\n";
    if (!empty($currentValue)) {
      $prompt .= "Valor actual: {$currentValue}\n";
    }
    if (!empty($context['entity_label'])) {
      $prompt .= "Contexto: {$context['entity_label']}\n";
    }
    $prompt .= "\nGenera exactamente 3 sugerencias en formato JSON: {\"suggestions\": [\"sugerencia1\", \"sugerencia2\", \"sugerencia3\"]}";

    return $prompt;
  }

}
```

### 8.3 InlineAiController

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for inline AI suggestions.
 */
class InlineAiController extends ControllerBase {

  public function suggest(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['field']) || empty($data['entity_type'])) {
      return new JsonResponse(['error' => 'Missing required fields'], 400);
    }

    $field = (string) $data['field'];
    $value = (string) ($data['value'] ?? '');
    $entityType = (string) $data['entity_type'];
    $context = (array) ($data['context'] ?? []);

    /** @var \Drupal\jaraba_ai_agents\Service\InlineAiService $service */
    $service = \Drupal::service('jaraba_ai_agents.inline_ai');
    $result = $service->suggest($field, $value, $entityType, $context);

    return new JsonResponse($result);
  }

}
```

**Ruta:**

```yaml
jaraba_ai_agents.inline_ai.suggest:
  path: '/api/v1/inline-ai/suggest'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\InlineAiController::suggest'
  requirements:
    _permission: 'use ai agents'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]
```

### 8.4 Frontend inline-ai-trigger.js

El script detecta campos configurados via `drupalSettings.inlineAiFields` y añade un boton sparkle (✦) junto a cada uno.

```javascript
(function (Drupal, drupalSettings, once) {
  'use strict';

  var csrfTokenPromise = null;

  function getCsrfToken() {
    if (!csrfTokenPromise) {
      csrfTokenPromise = fetch(Drupal.url('session/token'))
        .then(function (r) { return r.text(); });
    }
    return csrfTokenPromise;
  }

  Drupal.behaviors.inlineAiTrigger = {
    attach: function (context) {
      var fields = drupalSettings.inlineAiFields || [];
      if (fields.length === 0) {
        return;
      }

      fields.forEach(function (fieldName) {
        var selector = '[name="' + fieldName + '[0][value]"]';
        once('inline-ai', selector, context).forEach(function (fieldEl) {
          var trigger = document.createElement('button');
          trigger.type = 'button';
          trigger.className = 'inline-ai-trigger';
          trigger.setAttribute('aria-label', Drupal.t('Sugerencias IA'));
          trigger.innerHTML = '<span class="inline-ai-trigger__icon">✦</span>';

          trigger.addEventListener('click', function () {
            requestSuggestions(fieldEl, fieldName, trigger);
          });

          fieldEl.parentNode.insertBefore(trigger, fieldEl.nextSibling);
        });
      });

      function requestSuggestions(fieldEl, fieldName, trigger) {
        trigger.classList.add('is-loading');
        var entityType = drupalSettings.inlineAiEntityType || '';

        getCsrfToken().then(function (token) {
          return fetch(Drupal.url('api/v1/inline-ai/suggest'), {
            method: 'POST',
            headers: {
              'X-CSRF-Token': token,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              field: fieldName,
              value: fieldEl.value,
              entity_type: entityType,
              context: {}
            })
          });
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          trigger.classList.remove('is-loading');
          renderSuggestions(fieldEl, data.suggestions || []);
        })
        .catch(function () {
          trigger.classList.remove('is-loading');
        });
      }

      function renderSuggestions(fieldEl, suggestions) {
        // Remove existing panel
        var existing = fieldEl.parentNode.querySelector('.inline-ai-panel');
        if (existing) {
          existing.remove();
        }

        if (suggestions.length === 0) {
          return;
        }

        var panel = document.createElement('div');
        panel.className = 'inline-ai-panel';

        suggestions.forEach(function (suggestion) {
          var chip = document.createElement('button');
          chip.type = 'button';
          chip.className = 'inline-ai-chip';
          chip.textContent = suggestion;
          chip.addEventListener('click', function () {
            fieldEl.value = suggestion;
            fieldEl.dispatchEvent(new Event('change'));
            panel.remove();
          });
          panel.appendChild(chip);
        });

        fieldEl.parentNode.appendChild(panel);
      }
    }
  };
})(Drupal, drupalSettings, once);
```

### 8.5 Integracion PremiumEntityFormBase

Nuevo metodo opcional en `PremiumEntityFormBase`:

```php
/**
 * Returns fields that support inline AI suggestions.
 *
 * Override in subclasses to enable AI sparkle buttons on specific fields.
 *
 * @return string[]
 *   Array of field machine names.
 */
protected function getInlineAiFields(): array {
  return [];
}
```

En `buildForm()`, pasar a drupalSettings:

```php
$inlineAiFields = $this->getInlineAiFields();
if (!empty($inlineAiFields) && \Drupal::hasService('jaraba_ai_agents.inline_ai')) {
  $form['#attached']['library'][] = 'jaraba_ai_agents/inline-ai-trigger';
  $form['#attached']['drupalSettings']['inlineAiFields'] = $inlineAiFields;
  $form['#attached']['drupalSettings']['inlineAiEntityType'] = $this->entity->getEntityTypeId();
}
```

### 8.6 SCSS _inline-ai.scss

```scss
@use '../variables' as *;

.inline-ai-trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: var(--ej-inline-ai-sparkle, #{$ej-color-impulse});
  color: #fff;
  cursor: pointer;
  transition: transform $ej-transition-fast, opacity $ej-transition-fast;
  vertical-align: middle;
  margin-inline-start: $ej-spacing-xs;

  &:hover {
    transform: scale(1.1);
    opacity: $ej-hover-opacity;
  }

  &.is-loading {
    animation: inline-ai-pulse 1s ease-in-out infinite;
  }

  &__icon {
    font-size: 1rem;
  }
}

.inline-ai-panel {
  display: flex;
  flex-wrap: wrap;
  gap: $ej-spacing-xs;
  margin-top: $ej-spacing-xs;
  padding: $ej-spacing-sm;
  background: var(--ej-inline-ai-panel-bg, var(--ej-bg-surface, #fff));
  border: 1px solid var(--ej-border-color, $ej-color-border);
  border-radius: $ej-btn-border-radius;
}

.inline-ai-chip {
  padding: $ej-spacing-xs $ej-spacing-sm;
  border: 1px solid var(--ej-border-color, $ej-color-border);
  border-radius: 20px;
  background: transparent;
  color: var(--ej-color-body, $ej-color-text-body);
  font-size: 0.875rem;
  cursor: pointer;
  transition: background-color $ej-transition-fast, border-color $ej-transition-fast;

  &:hover {
    background: var(--ej-color-impulse, #{$ej-color-impulse});
    border-color: var(--ej-color-impulse, #{$ej-color-impulse});
    color: #fff;
  }
}

@keyframes inline-ai-pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}
```

### 8.7 Integracion con GrapesJS

Plugin toolbar button "AI" en el Canvas Editor que usa `InlineAiService` en modo redactor:

```javascript
// grapesjs-jaraba-ai.js (plugin GrapesJS)
grapesjs.plugins.add('jaraba-ai-writer', function (editor, opts) {
  editor.RichTextEditor.add('ai-suggest', {
    icon: '✦',
    attributes: { title: Drupal.t('Sugerencia IA') },
    result: function (rte) {
      var selected = rte.selection().toString();
      if (!selected) {
        return;
      }

      fetch(Drupal.url('api/v1/inline-ai/suggest'), {
        method: 'POST',
        headers: {
          'X-CSRF-Token': drupalSettings.csrfToken,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          field: 'body',
          value: selected,
          entity_type: 'content_article',
          context: { mode: 'rewrite' }
        })
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.suggestions && data.suggestions[0]) {
          rte.exec('insertHTML', data.suggestions[0]);
        }
      });
    }
  });
});
```

**Archivos nuevos para Inline AI:**

| Archivo | Tipo |
|---------|------|
| `jaraba_ai_agents/src/Service/InlineAiService.php` | NUEVO |
| `jaraba_ai_agents/src/Controller/InlineAiController.php` | NUEVO |
| `ecosistema_jaraba_theme/js/inline-ai-trigger.js` | NUEVO |
| `ecosistema_jaraba_theme/scss/components/_inline-ai.scss` | NUEVO |
| `jaraba_content_hub/js/grapesjs-jaraba-ai.js` | NUEVO |

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_core/src/Form/PremiumEntityFormBase.php` | Nuevo metodo `getInlineAiFields()` + attach library en `buildForm()` |
| `jaraba_ai_agents/jaraba_ai_agents.services.yml` | Registrar `InlineAiService` |
| `jaraba_ai_agents/jaraba_ai_agents.routing.yml` | Ruta inline AI suggest |

---

## 9. Proactive Intelligence — GAP-AUD-010

### 9.1 Arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                 PROACTIVE INTELLIGENCE                              │
│                                                                     │
│  hook_cron()                                                       │
│       │                                                             │
│       ▼                                                             │
│  ProactiveInsightEngine (QueueWorker)                              │
│       │                                                             │
│       ├── Per tenant: analyze usage patterns                       │
│       │     ├── SmartBaseAgent [balanced tier]                     │
│       │     │     ├── System: AIIdentityRule + tenant context      │
│       │     │     └── Prompt: "Analiza metricas y genera insights" │
│       │     │                                                       │
│       │     └── Creates ProactiveInsight entities                  │
│       │           ├── insight_type: optimization|alert|opportunity │
│       │           ├── severity: high|medium|low                    │
│       │           ├── target_user: uid                             │
│       │           ├── tenant_id: int                               │
│       │           └── action_url: /path/to/relevant/page          │
│       │                                                             │
│  Frontend:                                                          │
│       │                                                             │
│       ├── Bell icon in header (notification count)                 │
│       ├── Slide-panel with list of insights                       │
│       └── Mark as read / dismiss                                   │
│                                                                     │
│  Admin:                                                             │
│       ├── /admin/structure/proactive-insight                       │
│       ├── /admin/content/proactive-insight                         │
│       ├── Field UI                                                 │
│       └── Views                                                    │
└─────────────────────────────────────────────────────────────────────┘
```

### 9.2 ProactiveInsight entity

ContentEntity con los siguientes campos:

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| `id` | integer (serial) | Auto | ID unico |
| `uuid` | uuid | Auto | UUID |
| `insight_type` | list_string | Si | `optimization`, `alert`, `opportunity` |
| `title` | string(255) | Si | Titulo del insight |
| `body` | text_long | Si | Descripcion completa generada por IA |
| `severity` | list_string | Si | `high`, `medium`, `low` |
| `target_user` | entity_reference (user) | Si | Usuario destinatario |
| `tenant_id` | integer | Si | Tenant isolation |
| `read_status` | boolean | No | Default FALSE |
| `action_url` | string(512) | No | URL de accion sugerida |
| `ai_model` | string(64) | No | Modelo que genero el insight |
| `ai_confidence` | decimal(3,2) | No | Score de confianza 0.00-1.00 |
| `created` | created | Auto | Timestamp de creacion |
| `changed` | changed | Auto | Timestamp de modificacion |

**Entity keys:**
```php
"id" = "id",
"uuid" = "uuid",
"label" = "title",
"owner" = "target_user",
```

**Links:**
```php
"canonical" = "/admin/content/proactive-insight/{proactive_insight}",
"add-form" = "/admin/content/proactive-insight/add",
"edit-form" = "/admin/content/proactive-insight/{proactive_insight}/edit",
"delete-form" = "/admin/content/proactive-insight/{proactive_insight}/delete",
"collection" = "/admin/content/proactive-insight",
```

**Form:** `ProactiveInsightForm extends PremiumEntityFormBase` con:

```php
protected function getSectionDefinitions(): array {
  return [
    'insight_info' => [
      'label' => $this->t('Insight Information'),
      'icon' => ['category' => 'ui', 'name' => 'sparkles'],
      'description' => $this->t('AI-generated insight details.'),
      'fields' => ['title', 'insight_type', 'severity', 'body'],
    ],
    'targeting' => [
      'label' => $this->t('Targeting'),
      'icon' => ['category' => 'users', 'name' => 'user'],
      'description' => $this->t('Who should see this insight.'),
      'fields' => ['target_user', 'tenant_id', 'action_url'],
    ],
    'metadata' => [
      'label' => $this->t('AI Metadata'),
      'icon' => ['category' => 'status', 'name' => 'info'],
      'description' => $this->t('AI model and confidence.'),
      'fields' => ['ai_model', 'ai_confidence', 'read_status'],
    ],
  ];
}

protected function getFormIcon(): array {
  return ['category' => 'ui', 'name' => 'sparkles'];
}
```

**Access handler:** Implementa `TENANT-ISOLATION-ACCESS-001`:

```php
protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
  if ($operation === 'view') {
    // User can view own insights.
    return AccessResult::allowedIf(
      (int) $entity->get('target_user')->target_id === (int) $account->id()
    );
  }

  // Update/delete: admin or same tenant.
  if ($account->hasPermission('administer proactive insights')) {
    return AccessResult::allowed();
  }

  return AccessResult::forbidden();
}
```

### 9.3 ProactiveInsightEngine service

QueueWorker que se ejecuta via `hook_cron` para generar insights automaticos:

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;

/**
 * Queue worker for generating proactive insights.
 *
 * @QueueWorker(
 *   id = "proactive_insight_engine",
 *   title = @Translation("Proactive Insight Engine"),
 *   cron = {"time" = 120}
 * )
 */
class ProactiveInsightEngineWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function processItem($data): void {
    $tenantId = (int) $data['tenant_id'];
    $userId = (int) $data['user_id'];

    // Use SmartBaseAgent balanced tier to analyze patterns.
    // Create ProactiveInsight entities from results.
    // Limit: max 3 insights per user per day.
  }

}
```

### 9.4 Frontend

**Bell notification in header** (`_header.html.twig`):

```twig
{% if proactive_insights_count > 0 %}
<button class="proactive-insights-bell" data-proactive-insights-trigger
  aria-label="{{ 'N notificaciones de IA'|t({ 'N': proactive_insights_count }) }}">
  {{ jaraba_icon('ui', 'bell', { variant: 'duotone', color: 'naranja-impulso', size: '24px' }) }}
  <span class="proactive-insights-badge">{{ proactive_insights_count }}</span>
</button>
{% endif %}
```

**Slide-panel JS** (`proactive-insights.js`):

Clicking the bell opens a slide-panel via AJAX that loads the list of unread insights for the current user, rendered as cards with severity color-coding.

### 9.5 Admin integration

| Ruta | Controlador | Proposito |
|------|------------|-----------|
| `/admin/structure/proactive-insight` | Entity type settings | Field UI, form/display config |
| `/admin/content/proactive-insight` | ListBuilder | Admin list with filters |

**Archivos nuevos para Proactive Intelligence:**

| Archivo | Tipo |
|---------|------|
| `jaraba_ai_agents/src/Entity/ProactiveInsight.php` | NUEVO |
| `jaraba_ai_agents/src/Entity/ProactiveInsightInterface.php` | NUEVO |
| `jaraba_ai_agents/src/Form/ProactiveInsightForm.php` | NUEVO |
| `jaraba_ai_agents/src/ProactiveInsightAccessControlHandler.php` | NUEVO |
| `jaraba_ai_agents/src/ProactiveInsightListBuilder.php` | NUEVO |
| `jaraba_ai_agents/src/Plugin/QueueWorker/ProactiveInsightEngineWorker.php` | NUEVO |
| `ecosistema_jaraba_theme/scss/components/_proactive-insights.scss` | NUEVO |
| `ecosistema_jaraba_theme/js/proactive-insights.js` | NUEVO |
| `ecosistema_jaraba_theme/templates/partials/_proactive-insights-bell.html.twig` | NUEVO |

---

## 10. Voice AI Interface — GAP-AUD-011

### 10.1 Arquitectura

```
┌─────────────────────────────────────────────────────────────────────┐
│                    VOICE AI INTERFACE                                │
│                                                                     │
│  Browser (Web Speech API)                                          │
│       │                                                             │
│       ├── navigator.mediaDevices.getUserMedia()                    │
│       ├── SpeechRecognition (client-side STT)                     │
│       │                                                             │
│       ▼                                                             │
│  Transcript (text)                                                 │
│       │                                                             │
│       ▼                                                             │
│  copilot-chat-widget.js (existing pipeline)                        │
│       │                                                             │
│       ├── POST /api/v1/copilot/stream (existing streaming)        │
│       ├── SSE response (chunk, done, etc.)                         │
│       │                                                             │
│       ▼                                                             │
│  Text response (rendered in chat)                                  │
│       │                                                             │
│       ▼ (optional, future)                                         │
│  Web Speech API SpeechSynthesis (client-side TTS)                  │
│       └── Read response aloud                                      │
│                                                                     │
│  Fallback: MultiModalBridgeService::transcribeAudio()             │
│       └── OpenAI Whisper API (server-side, if Web Speech fails)   │
└─────────────────────────────────────────────────────────────────────┘
```

**Decision de diseno:** Voice se implementa **client-side primero** con Web Speech API porque:
1. No requiere API keys adicionales para transcripcion
2. Baja latencia (no hay round-trip al servidor para STT)
3. Privacy-first (el audio no sale del navegador)
4. Fallback server-side via `MultiModalBridgeService` para browsers sin soporte

### 10.2 MultiModalBridgeService implementacion

El stub existente (87 lineas) se amplia con implementacion real del metodo `transcribeAudio()`:

```php
public function transcribeAudio(string $audioUri, string $language = 'es', array $options = []): array {
  if (!\Drupal::hasService('ai_provider_openai.provider')) {
    throw new MultiModalNotAvailableException('audio_transcription');
  }

  try {
    $provider = \Drupal::service('ai_provider_openai.provider');
    // Use Whisper API via provider.
    $result = $provider->transcribe($audioUri, [
      'language' => $language,
      'model' => 'whisper-1',
    ]);

    return [
      'text' => $result['text'] ?? '',
      'language' => $language,
      'confidence' => $result['confidence'] ?? 0.0,
      'provider' => 'openai_whisper',
    ];
  }
  catch (\Throwable $e) {
    $this->logger->warning('Audio transcription failed: @error', [
      '@error' => $e->getMessage(),
    ]);
    throw new MultiModalNotAvailableException('audio_transcription');
  }
}
```

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_ai_agents/src/Service/MultiModalBridgeService.php` | Implementar `transcribeAudio()` |
| `jaraba_ai_agents/src/Service/MultiModalInputInterface.php` | Actualizar `getInputCapabilities()` return |

### 10.3 voice-input.js

```javascript
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.voiceInput = {
    attach: function (context) {
      once('voice-input', '[data-copilot-input]', context).forEach(function (inputEl) {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
          return; // Browser not supported — graceful degradation
        }

        var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        var recognition = new SpeechRecognition();
        recognition.lang = document.documentElement.lang || 'es';
        recognition.interimResults = true;
        recognition.continuous = false;

        // Create mic button
        var micBtn = document.createElement('button');
        micBtn.type = 'button';
        micBtn.className = 'voice-input-btn';
        micBtn.setAttribute('aria-label', Drupal.t('Entrada de voz'));
        micBtn.innerHTML = '<svg class="voice-input-icon" viewBox="0 0 24 24" width="20" height="20">' +
          '<path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z" fill="currentColor"/>' +
          '<path d="M19 10v2a7 7 0 0 1-14 0v-2" stroke="currentColor" fill="none" stroke-width="2"/>' +
          '<line x1="12" y1="19" x2="12" y2="23" stroke="currentColor" stroke-width="2"/>' +
          '</svg>';
        inputEl.parentNode.insertBefore(micBtn, inputEl.nextSibling);

        var isRecording = false;

        micBtn.addEventListener('click', function () {
          if (isRecording) {
            recognition.stop();
          } else {
            recognition.start();
          }
        });

        recognition.onstart = function () {
          isRecording = true;
          micBtn.classList.add('is-recording');
        };

        recognition.onend = function () {
          isRecording = false;
          micBtn.classList.remove('is-recording');
        };

        recognition.onresult = function (event) {
          var transcript = '';
          for (var i = event.resultIndex; i < event.results.length; i++) {
            transcript += event.results[i][0].transcript;
          }
          if (event.results[event.resultIndex].isFinal) {
            inputEl.value = transcript;
            inputEl.dispatchEvent(new Event('input'));
            // Auto-submit after final transcript
            var submitBtn = inputEl.closest('form')?.querySelector('[data-copilot-submit]');
            if (submitBtn) {
              submitBtn.click();
            }
          }
        };

        recognition.onerror = function (event) {
          isRecording = false;
          micBtn.classList.remove('is-recording');
          if (event.error !== 'no-speech') {
            console.warn('Voice recognition error:', event.error);
          }
        };
      });
    }
  };
})(Drupal, once);
```

### 10.4 Feature gate por plan

Voice AI solo disponible en planes Professional y Enterprise (no Starter/Free):

```php
// En hook_preprocess para copilot widget
$voiceEnabled = FALSE;
if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
  $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenant();
  if ($tenant) {
    $plan = $tenant->get('plan_tier')->value ?? 'starter';
    $voiceEnabled = in_array($plan, ['professional', 'enterprise'], TRUE);
  }
}
$variables['voice_enabled'] = $voiceEnabled;
```

**Archivos nuevos:**

| Archivo | Tipo |
|---------|------|
| `jaraba_copilot_v2/js/voice-input.js` | NUEVO |
| `ecosistema_jaraba_theme/scss/components/_voice-input.scss` | NUEVO |

---

## 11. A2A Protocol — GAP-AUD-012

### 11.1 Extension del patron MCP

El A2A (Agent-to-Agent) Protocol extiende el patron ya implementado en `McpServerController` (367 lineas) con:
1. **Agent Card** discovery endpoint (`.well-known/agent.json`)
2. **Task lifecycle** management (submitted → working → completed/failed)
3. **Inter-agent communication** via JSON-RPC extended methods

```
┌─────────────────────────────────────────────────────────────────────┐
│                    A2A PROTOCOL                                     │
│                                                                     │
│  External Agent                                                    │
│       │                                                             │
│       ├── GET /.well-known/agent.json                              │
│       │     └── Agent Card: name, capabilities, auth, endpoints   │
│       │                                                             │
│       ├── POST /api/v1/a2a/task                                    │
│       │     └── Submit task: { action, params, callback_url }     │
│       │                                                             │
│       ├── GET /api/v1/a2a/task/{task_id}                           │
│       │     └── Check status: submitted|working|completed|failed  │
│       │                                                             │
│       └── POST /api/v1/a2a/task/{task_id}/cancel                  │
│             └── Cancel running task                                │
│                                                                     │
│  Internal: A2ATaskController                                       │
│       │                                                             │
│       ├── Maps action → SmartBaseAgent subclass                   │
│       ├── Creates A2ATask entity (status tracking)                 │
│       ├── Executes via QueueWorker (async)                         │
│       └── Calls callback_url on completion                         │
│                                                                     │
│  Security:                                                          │
│       ├── Bearer token (API key per external agent)               │
│       ├── HMAC signature verification                              │
│       └── Rate limiting: 100 tasks/hour per agent                 │
└─────────────────────────────────────────────────────────────────────┘
```

### 11.2 AgentCardController

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Serves the A2A Agent Card at /.well-known/agent.json.
 */
class AgentCardController extends ControllerBase {

  public function agentCard(): JsonResponse {
    $card = [
      'name' => 'Jaraba Impact Platform AI',
      'description' => $this->t('Multi-vertical SaaS AI agents for digital transformation.'),
      'version' => '1.0.0',
      'protocol' => 'a2a/1.0',
      'capabilities' => [
        'tasks' => TRUE,
        'streaming' => TRUE,
        'tools' => TRUE,
      ],
      'endpoints' => [
        'task_submit' => '/api/v1/a2a/task',
        'task_status' => '/api/v1/a2a/task/{task_id}',
        'task_cancel' => '/api/v1/a2a/task/{task_id}/cancel',
        'mcp' => '/api/v1/mcp',
      ],
      'authentication' => [
        'type' => 'bearer',
        'header' => 'Authorization',
      ],
      'supported_actions' => [
        'content_generation',
        'data_analysis',
        'email_generation',
        'search_knowledge',
      ],
    ];

    return new JsonResponse($card, 200, [
      'Content-Type' => 'application/json',
      'Cache-Control' => 'public, max-age=3600',
    ]);
  }

}
```

### 11.3 A2ATaskController

Handles task submission, status check, and cancellation. Tasks are persisted as entities and processed asynchronously via QueueWorker.

**Ruta:**

```yaml
jaraba_ai_agents.a2a.agent_card:
  path: '/.well-known/agent.json'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\AgentCardController::agentCard'
  requirements:
    _access: 'TRUE'

jaraba_ai_agents.a2a.task_submit:
  path: '/api/v1/a2a/task'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\A2ATaskController::submit'
  requirements:
    _permission: 'use ai agents'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]

jaraba_ai_agents.a2a.task_status:
  path: '/api/v1/a2a/task/{task_id}'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\A2ATaskController::status'
  requirements:
    _permission: 'use ai agents'
    task_id: '\d+'

jaraba_ai_agents.a2a.task_cancel:
  path: '/api/v1/a2a/task/{task_id}/cancel'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\A2ATaskController::cancel'
  requirements:
    _permission: 'use ai agents'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]
```

### 11.4 Seguridad

| Aspecto | Implementacion |
|---------|----------------|
| Auth | Bearer token en header `Authorization` — API keys stored as config |
| HMAC | Optional `X-Signature` header con HMAC-SHA256 del body |
| Rate limit | 100 tasks/hour per agent identity (Drupal Flood) |
| Tenant isolation | Tasks inherit tenant_id from agent configuration |
| PII | All task results pass through `AIGuardrailsService::maskOutputPII()` |

**Archivos nuevos:**

| Archivo | Tipo |
|---------|------|
| `jaraba_ai_agents/src/Controller/AgentCardController.php` | NUEVO |
| `jaraba_ai_agents/src/Controller/A2ATaskController.php` | NUEVO |
| `jaraba_ai_agents/src/Entity/A2ATask.php` | NUEVO |
| `jaraba_ai_agents/src/Plugin/QueueWorker/A2ATaskWorker.php` | NUEVO |

---

## 12. Vision/Multimodal — GAP-AUD-013

### 12.1 MultiModalBridgeService::analyzeImage()

Implementar el stub existente con soporte para Claude Vision o GPT-4o:

```php
public function analyzeImage(string $imageUri, string $prompt = '', array $options = []): array {
  // Validate image: max 10MB, allowed types: jpg, png, webp, gif
  $fileInfo = $this->validateImage($imageUri);

  if (!\Drupal::hasService('ai_provider_openai.provider')) {
    throw new MultiModalNotAvailableException('image_analysis');
  }

  try {
    $provider = \Drupal::service('ai_provider_openai.provider');
    $imageData = base64_encode(file_get_contents($imageUri));
    $mimeType = $fileInfo['mime_type'];

    $analysisPrompt = AIIdentityRule::apply(
      $prompt ?: 'Describe esta imagen en detalle. Incluye objetos, colores, texto visible y contexto.', TRUE
    );

    $result = $provider->chat([
      'model' => 'gpt-4o',
      'messages' => [
        [
          'role' => 'user',
          'content' => [
            ['type' => 'text', 'text' => $analysisPrompt],
            [
              'type' => 'image_url',
              'image_url' => [
                'url' => "data:{$mimeType};base64,{$imageData}",
              ],
            ],
          ],
        ],
      ],
      'max_tokens' => 1000,
    ]);

    $responseText = $result['choices'][0]['message']['content'] ?? '';

    // Mask any PII in the analysis result.
    if (\Drupal::hasService('ecosistema_jaraba_core.ai_guardrails')) {
      $responseText = \Drupal::service('ecosistema_jaraba_core.ai_guardrails')
        ->maskOutputPII($responseText);
    }

    return [
      'analysis' => $responseText,
      'provider' => 'openai_gpt4o',
      'tokens_used' => $result['usage']['total_tokens'] ?? 0,
    ];
  }
  catch (\Throwable $e) {
    $this->logger->warning('Image analysis failed: @error', [
      '@error' => $e->getMessage(),
    ]);
    throw new MultiModalNotAvailableException('image_analysis');
  }
}
```

### 12.2 CopilotStreamController multipart

Ampliar el controller existente para aceptar `multipart/form-data` con imagen adjunta:

```php
// En CopilotStreamController, nuevo metodo o extension de streamChat()
if ($request->files->has('image')) {
  $uploadedFile = $request->files->get('image');
  // Validate and save temporarily.
  $tempUri = 'temporary://' . $uploadedFile->getClientOriginalName();
  move_uploaded_file($uploadedFile->getRealPath(), $tempUri);

  // Analyze image first.
  if (\Drupal::hasService('jaraba_ai_agents.multimodal_bridge')) {
    $analysis = \Drupal::service('jaraba_ai_agents.multimodal_bridge')
      ->analyzeImage($tempUri, $message);
    $message .= "\n\n[Analisis de imagen: " . $analysis['analysis'] . "]";
  }
}
```

### 12.3 copilot-chat-widget.js upload

Añadir boton de upload de imagen al widget del copilot:

```javascript
// Image upload button in copilot widget
var imageBtn = document.createElement('button');
imageBtn.type = 'button';
imageBtn.className = 'copilot-image-btn';
imageBtn.setAttribute('aria-label', Drupal.t('Adjuntar imagen'));
imageBtn.innerHTML = '📎';

var fileInput = document.createElement('input');
fileInput.type = 'file';
fileInput.accept = 'image/jpeg,image/png,image/webp,image/gif';
fileInput.style.display = 'none';

imageBtn.addEventListener('click', function () {
  fileInput.click();
});

fileInput.addEventListener('change', function () {
  if (fileInput.files[0]) {
    // Show preview thumbnail
    var reader = new FileReader();
    reader.onload = function (e) {
      // Display thumbnail in chat
    };
    reader.readAsDataURL(fileInput.files[0]);

    // Attach to next message submission as FormData
  }
});
```

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_ai_agents/src/Service/MultiModalBridgeService.php` | Implementar `analyzeImage()` |
| `jaraba_copilot_v2/src/Controller/CopilotStreamController.php` | Aceptar multipart form data |
| `jaraba_copilot_v2/js/copilot-chat-widget.js` | UI upload imagen |

---

## 13. AI Test Coverage + CI/CD Prompt Regression — GAP-AUD-014 + GAP-AUD-015

### 13.1 Estado actual

El stack IA tiene ~7,500 lineas de codigo en los servicios principales con una cobertura de tests extremadamente baja:

| Suite | Tests existentes | Lineas cubiertas |
|-------|-----------------|------------------|
| Unit | 2 | ~150 |
| Kernel | 4 | ~300 |
| Prompt Regression | 0 | 0 |
| **Total** | **6** | **~450 / ~7,500 (~6%)** |

Competidores de referencia tienen >70% de coverage en sus stacks IA.

### 13.2 Tests nuevos

**Unit Tests (40+):**

| Test Class | Cubre | Metodos testados |
|------------|-------|-----------------|
| `SmartBaseAgentTest` | SmartBaseAgent | `callAiApi()`, `parseToolCall()`, `sanitizeToolOutput()`, `getRoutingConfig()` |
| `ModelRouterServiceTest` | ModelRouterService | `route()`, `assessComplexity()`, heuristics |
| `ToolRegistryTest` | ToolRegistry | `register()`, `execute()`, `generateNativeToolsInput()`, `generateToolsDocumentation()` |
| `AIGuardrailsServiceTest` | AIGuardrailsService | `validate()`, `checkPII()`, `checkJailbreak()`, `sanitizeRagContent()`, `sanitizeToolOutput()`, `maskOutputPII()` |
| `AIIdentityRuleTest` | AIIdentityRule | `apply()` con short/long |
| `TraceContextServiceTest` | TraceContextService | `startTrace()`, `startSpan()`, `endSpan()`, `getSpanContext()`, auto-parenting |
| `InlineAiServiceTest` | InlineAiService | `suggest()` con/sin agent, error handling |
| `CommandRegistryServiceTest` | CommandRegistryService | `search()`, provider access filtering |
| `ProactiveInsightEngineTest` | ProactiveInsightEngine | `processItem()`, max insights per day |
| `StreamingOrchestratorServiceTest` | StreamingOrchestratorService | `shouldFlushBuffer()`, `maskBufferPII()` |
| `McpServerControllerTest` | McpServerController | JSON-RPC dispatch, error codes, PII sanitization |
| `OnboardingAiRecommendationServiceTest` | OnboardingAiRecommendation | `getRecommendations()` with mock agent |
| `AgentCardControllerTest` | AgentCardController | `agentCard()` response structure |
| `A2ATaskControllerTest` | A2ATaskController | `submit()`, `status()`, `cancel()` |
| `SkillInferenceServiceTest` | SkillInferenceService | `infer()` with mock agent |
| `AdaptiveLearningServiceTest` | AdaptiveLearningService | AI-enhanced `getNextLessons()` |

**Kernel Tests (15+):**

| Test Class | Cubre | Que verifica |
|------------|-------|-------------|
| `ProactiveInsightEntityTest` | ProactiveInsight entity | CRUD, field validation, access control, tenant isolation |
| `ContentArticleSlugTest` | ContentArticle slug | Slug generation, uniqueness, route resolution |
| `ContentArticleTenantTest` | ContentArticle tenant_id | Tenant isolation in access handler |
| `CommandRegistryIntegrationTest` | Command Bar full stack | Tagged service collection, search across providers |
| `InlineAiIntegrationTest` | Inline AI full stack | Service → Controller → Response |
| `A2ATaskEntityTest` | A2ATask entity | Lifecycle: submitted → working → completed |
| `DesignTokenDocumentationTest` | Design System docs | Token extraction from SCSS |

### 13.3 PromptRegressionTestBase

Clase base para tests de regresion de prompts IA:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Base class for prompt regression tests.
 *
 * Compares generated prompts against golden fixtures to detect
 * unintended changes in AI behavior.
 */
abstract class PromptRegressionTestBase extends TestCase {

  /**
   * Path to golden fixtures directory.
   */
  protected function getFixturesPath(): string {
    return __DIR__ . '/../../fixtures/prompts';
  }

  /**
   * Assert prompt matches golden fixture.
   *
   * @param string $fixtureName
   *   Name of the fixture file (without extension).
   * @param string $actualPrompt
   *   The generated prompt to compare.
   */
  protected function assertPromptMatchesGolden(string $fixtureName, string $actualPrompt): void {
    $fixturePath = $this->getFixturesPath() . '/' . $fixtureName . '.txt';

    if (!file_exists($fixturePath)) {
      // First run: create the golden fixture.
      file_put_contents($fixturePath, $actualPrompt);
      $this->markTestSkipped('Golden fixture created. Re-run to validate.');
      return;
    }

    $expected = file_get_contents($fixturePath);
    $this->assertSame(
      $this->normalizePrompt($expected),
      $this->normalizePrompt($actualPrompt),
      "Prompt regression detected for fixture: {$fixtureName}"
    );
  }

  /**
   * Normalize prompt for comparison (trim, normalize whitespace).
   */
  protected function normalizePrompt(string $prompt): string {
    $prompt = trim($prompt);
    $prompt = preg_replace('/\s+/', ' ', $prompt);
    return $prompt;
  }

}
```

### 13.4 phpunit.xml suite

Añadir suite `PromptRegression` al `phpunit.xml` existente:

```xml
<testsuite name="PromptRegression">
  <directory>web/modules/custom/jaraba_ai_agents/tests/src/Unit/PromptRegression</directory>
</testsuite>
```

**Archivos nuevos:**

| Archivo | Tipo |
|---------|------|
| `jaraba_ai_agents/tests/src/Unit/PromptRegression/PromptRegressionTestBase.php` | NUEVO |
| `jaraba_ai_agents/tests/src/Unit/PromptRegression/IdentityPromptRegressionTest.php` | NUEVO |
| `jaraba_ai_agents/tests/src/Unit/PromptRegression/GuardrailsPromptRegressionTest.php` | NUEVO |
| `jaraba_ai_agents/tests/fixtures/prompts/` | NUEVO (directorio) |
| 40+ test files across multiple modules | NUEVO |

---

## 14. Blog Slugs + Content Hub tenant_id — GAP-AUD-016 + GAP-AUD-017

### 14.1 Slug field y route parameter converter

**Estado actual de ContentArticle (511 lineas):**
- Tiene campo `slug` (string 255, translatable) pero la ruta canonica usa `{content_article}` (entity ID)
- Canonical: `/blog/{content_article}` → ID numerico

**Que implementar:**

1. **Route parameter converter** que resuelva slug → entity ID
2. **Presave hook** que auto-genere slug desde titulo (transliterate + slugify)
3. **Uniqueness check** en presave

**Update hook:**

```php
/**
 * Populate slug field for existing articles.
 */
function jaraba_content_hub_update_10002(): string {
  $storage = \Drupal::entityTypeManager()->getStorage('content_article');
  $ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->notExists('slug')
    ->execute();

  $count = 0;
  foreach ($storage->loadMultiple($ids) as $article) {
    $title = $article->getTitle();
    $slug = \Drupal::service('pathauto.alias_cleaner')->cleanString($title);
    $article->set('slug', $slug);
    $article->save();
    $count++;
  }

  return "Updated slug for {$count} articles.";
}
```

**Ruta modificada:**

```yaml
jaraba_content_hub.blog.article:
  path: '/blog/{slug}'
  defaults:
    _controller: '\Drupal\jaraba_content_hub\Controller\BlogController::article'
  requirements:
    _access: 'TRUE'
    slug: '[a-z0-9\-]+'
  options:
    parameters:
      slug:
        type: 'jaraba_content_hub:content_article_slug'
```

**ParamConverter:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

class ContentArticleSlugConverter implements ParamConverterInterface {

  public function convert($value, $definition, $name, array $defaults) {
    $storage = \Drupal::entityTypeManager()->getStorage('content_article');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('slug', $value)
      ->condition('status', 'published')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  public function applies($definition, $name, Route $route): bool {
    return !empty($definition['type']) && $definition['type'] === 'jaraba_content_hub:content_article_slug';
  }

}
```

### 14.2 tenant_id field y access handler

**Que implementar:**

1. Campo `tenant_id` (integer) en `ContentArticle::baseFieldDefinitions()`
2. Access handler con `TENANT-ISOLATION-ACCESS-001`
3. Update hook para migrar articulos existentes

**Campo nuevo:**

```php
$fields['tenant_id'] = BaseFieldDefinition::create('integer')
  ->setLabel(t('Tenant ID'))
  ->setDescription(t('The tenant that owns this article.'))
  ->setDefaultValue(0)
  ->setDisplayOptions('form', [
    'type' => 'number',
    'weight' => 90,
  ])
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

**Update hook:**

```php
/**
 * Add tenant_id field to content_article.
 */
function jaraba_content_hub_update_10003(): void {
  $entityDefinitionManager = \Drupal::entityDefinitionUpdateManager();
  $fieldDefinition = BaseFieldDefinition::create('integer')
    ->setLabel('Tenant ID')
    ->setDefaultValue(0);
  $entityDefinitionManager->installFieldStorageDefinition(
    'tenant_id', 'content_article', 'jaraba_content_hub', $fieldDefinition
  );
}
```

**Archivos nuevos:**

| Archivo | Tipo |
|---------|------|
| `jaraba_content_hub/src/ParamConverter/ContentArticleSlugConverter.php` | NUEVO |

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_content_hub/src/Entity/ContentArticle.php` | Añadir `tenant_id` field, update slug accessor |
| `jaraba_content_hub/jaraba_content_hub.install` | Update hooks 10002, 10003 |
| `jaraba_content_hub/jaraba_content_hub.services.yml` | Registrar ParamConverter |
| `jaraba_content_hub/jaraba_content_hub.routing.yml` | Cambiar ruta blog a slug |
| `jaraba_content_hub/src/ContentArticleAccessControlHandler.php` | Tenant isolation check |

---

## 15. Vertical AI Features — GAP-AUD-018 a GAP-AUD-022

### 15.1 Skill Inference (Empleabilidad) — GAP-AUD-018

**Estado actual:**

`SkillsService` (335 lineas) en `jaraba_candidate` usa scoring rule-based con `SKILL_CATEGORIES` hardcodeadas. `assessSkills()` scores 1-5, `getSkillGaps()` compara aritmeticamente. No hay AI/LLM.

`CopilotInsightsService` (566 lineas) detecta intents con `str_contains()` contra keyword lists. No ML.

**Que implementar:**

`SkillInferenceService` que usa SmartBaseAgent balanced tier para:
1. Inferir skills desde texto libre (CV, descripcion de experiencia)
2. Semantic matching contra job requirements (via Qdrant)
3. Gap analysis inteligente con recomendaciones de formacion personalizadas

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;

/**
 * AI-powered skill inference from unstructured text.
 */
class SkillInferenceService {

  public function __construct(
    protected readonly SkillsService $skillsService,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?object $aiAgent = NULL,
    protected readonly ?object $embeddingService = NULL,
  ) {}

  /**
   * Infer skills from free-text input (CV, experience description).
   */
  public function inferFromText(string $text, string $language = 'es'): array {
    if ($this->aiAgent === NULL) {
      return ['skills' => [], 'confidence' => 0.0];
    }

    try {
      $prompt = AIIdentityRule::apply(
        "Analiza el siguiente texto y extrae las habilidades profesionales. " .
        "Clasifica cada habilidad en: technical, soft, digital, languages. " .
        "Asigna un nivel estimado 1-5.\n\n" .
        "Texto:\n{$text}\n\n" .
        "Responde en JSON: {\"skills\": [{\"name\": \"\", \"category\": \"\", \"level\": N}]}", TRUE
      );

      $result = $this->aiAgent->execute('skill_inference', [
        'prompt' => $prompt,
        'tier' => 'balanced',
      ]);

      return $result['data'] ?? ['skills' => [], 'confidence' => 0.0];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Skill inference failed: @error', ['@error' => $e->getMessage()]);
      return ['skills' => [], 'confidence' => 0.0];
    }
  }

}
```

**Archivos nuevos:**

| Archivo | Tipo |
|---------|------|
| `jaraba_candidate/src/Service/SkillInferenceService.php` | NUEVO |

### 15.2 Adaptive Learning (LMS) — GAP-AUD-019

**Estado actual:**

`AdaptiveLearningService` (237 lineas) acepta un `$aiAgent` parameter pero NUNCA lo usa. Scoring puramente rule-based con 3 factores hardcodeados: difficulty alignment (0.4), prerequisite satisfaction (0.3), sequential order (0.3).

**Que implementar:**

Activar el `$aiAgent` para:
1. Personalizar recomendaciones basadas en learning style (visual, kinesthetic, etc.)
2. Generar explicaciones adaptadas al nivel del alumno
3. Predecir likelihood de completion basado en patron de engagement

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_lms/src/Service/AdaptiveLearningService.php` | Activar `$aiAgent` en `getNextLessons()` y `getRecommendationReason()` |
| `jaraba_lms/jaraba_lms.services.yml` | Conectar `@?jaraba_ai_agents.smart_marketing_agent` |

### 15.3 Demand Forecasting (ComercioConecta) — GAP-AUD-020

**Estado actual:**

No existe `DemandForecastingService`. `ComercioAnalyticsService` provee SQL aggregation de KPIs (GMV, orders, payments) pero no predicciones.

**Que implementar:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;

/**
 * AI-powered demand forecasting for merchants.
 */
class DemandForecastingService {

  public function __construct(
    protected readonly ComercioAnalyticsService $analytics,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?object $aiAgent = NULL,
  ) {}

  /**
   * Forecast demand for a merchant's products.
   */
  public function forecast(int $merchantId, int $daysAhead = 30): array {
    if ($this->aiAgent === NULL) {
      return ['forecast' => [], 'confidence' => 0.0];
    }

    // Get historical data.
    $historicalData = $this->analytics->getMerchantKpis($merchantId, 90);

    try {
      $prompt = AIIdentityRule::apply(
        "Eres un analista de demanda para comercios. Analiza los datos historicos " .
        "y genera una prediccion de demanda para los proximos {$daysAhead} dias.", TRUE
      );

      $result = $this->aiAgent->execute('demand_forecast', [
        'prompt' => $prompt . "\n\nDatos historicos:\n" . json_encode($historicalData),
        'tier' => 'premium',
      ]);

      return $result['data'] ?? ['forecast' => [], 'confidence' => 0.0];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Demand forecasting failed: @error', ['@error' => $e->getMessage()]);
      return ['forecast' => [], 'confidence' => 0.0];
    }
  }

}
```

**Archivos nuevos:**

| Archivo | Tipo |
|---------|------|
| `jaraba_comercio_conecta/src/Service/DemandForecastingService.php` | NUEVO |

### 15.4 AI Writing in GrapesJS — GAP-AUD-021

Usa el plugin `grapesjs-jaraba-ai.js` descrito en [8.7](#87-integracion-con-grapesjs) para añadir un boton "AI Writer" en la toolbar del Rich Text Editor de GrapesJS. El plugin inyecta sugerencias contextuales usando `InlineAiService` con el texto seleccionado como contexto.

**Archivos:** Ver seccion 8.7.

### 15.5 Service Matching (ServiciosConecta) — GAP-AUD-022

**Estado actual:**

`ServiceMatchingService` (241 lineas) tiene arquitectura hibrida con Qdrant wired opcionalmente. `embeddingService` y `qdrantClient` son `?object`. Scoring con 6 factores ponderados. Availability hardcodeada a 0.8. Location simplificada a same-city check.

**Que implementar:**

1. Conectar `embeddingService` realmente (wiring via services.yml)
2. Mejorar location matching con coordenadas (lat/lng distance)
3. Availability real desde BookingService
4. AI-enhanced description matching (re-rank con SmartBaseAgent fast)

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_servicios_conecta/src/Service/ServiceMatchingService.php` | Real Qdrant wiring, availability from BookingService, location distance |
| `jaraba_servicios_conecta/jaraba_servicios_conecta.services.yml` | Connect `@?jaraba_rag.embedding_service` and `@?jaraba_rag.qdrant_client` |

---

## 16. Design System Documentation — GAP-AUD-023

**Estado actual:**

Design token infrastructure exists (`DesignTokenConfig` entity, `StylePresetService`, config instances per vertical) but there is NO public-facing documentation controller or component styleguide.

**Que implementar:**

`ComponentDocumentationController` que:
1. Scannea partials en `ecosistema_jaraba_theme/templates/partials/` y genera vista de componentes
2. Extrae tokens de `_variables.scss` y los presenta como documentacion viva
3. Muestra mock data para cada componente

```
┌─────────────────────────────────────────────────────────────────────┐
│                 DESIGN SYSTEM DOCS                                  │
│                                                                     │
│  Route: /admin/design-system                                       │
│       │                                                             │
│       ▼                                                             │
│  ComponentDocumentationController                                   │
│       │                                                             │
│       ├── scanPartials() → list of _*.html.twig                   │
│       ├── extractTokens() → parse _variables.scss                  │
│       ├── renderComponent($partial, $mockData) → HTML preview     │
│       │                                                             │
│       ▼                                                             │
│  Template: design-system-page.html.twig                            │
│       ├── Color palette swatches                                   │
│       ├── Typography specimens                                     │
│       ├── Spacing scale visualization                              │
│       ├── Icon gallery (all categories)                            │
│       └── Component previews with code snippets                   │
└─────────────────────────────────────────────────────────────────────┘
```

**Archivos nuevos:**

| Archivo | Tipo |
|---------|------|
| `ecosistema_jaraba_core/src/Controller/ComponentDocumentationController.php` | NUEVO |
| `ecosistema_jaraba_theme/templates/design-system-page.html.twig` | NUEVO |
| `ecosistema_jaraba_theme/scss/components/_design-system.scss` | NUEVO |

---

## 17. Infrastructure Gaps — GAP-AUD-024 + GAP-AUD-025

### GAP-AUD-024: Cost Attribution per Tenant

**Problema:** `AIObservabilityService` logs AI usage but doesn't connect to `TenantMeteringService` for billing. Tokens consumed per tenant are not attributed for cost allocation.

**Implementacion:**

1. En `AIObservabilityService::log()`, despues de registrar el log, llamar a `TenantMeteringService::recordUsage()` con metric `ai_tokens` y quantity = input_tokens + output_tokens
2. En `CostAlertService`, configurar thresholds por tenant_id (no solo globales)
3. Dashboard en `/mi-cuenta/uso` ya existe — solo necesita conectar los datos de AI tokens

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `jaraba_ai_agents/src/Service/AIObservabilityService.php` | Inyectar `@?ecosistema_jaraba_core.tenant_metering` y llamar `recordUsage()` |
| `ecosistema_jaraba_core/src/Service/CostAlertService.php` | Per-tenant thresholds |

### GAP-AUD-025: Horizontal Scaling AI

**Problema:** AI workloads (agent execution, streaming, batch insight generation) run in the same PHP process pool as web requests. High AI load can degrade web response times.

**Implementacion:**

1. Redis-backed dedicated queue `jaraba_ai_workloads` para tareas AI pesadas
2. Configuracion para worker pool aislado en `settings.php`:

```php
$settings['queue_service_jaraba_ai_workloads'] = 'queue.redis';
```

3. Supervisor config para workers dedicados:

```ini
[program:jaraba-ai-workers]
command=drush queue:run jaraba_ai_workloads --time-limit=300
numprocs=2
autostart=true
autorestart=true
```

4. `ProactiveInsightEngineWorker` y `A2ATaskWorker` usan esta queue dedicada
5. `ScheduledAgentWorker` migrado a la queue dedicada

**Archivos a crear:**

| Archivo | Tipo |
|---------|------|
| `config/deploy/supervisor-ai-workers.conf` | NUEVO (template) |

---

## 18. Internacionalizacion (i18n)

Listado completo de strings traducibles por gap:

### Command Bar (GAP-AUD-008)
- `'Busqueda rapida'` — Twig
- `'Buscar paginas, contenido, acciones...'` — Twig
- `'Buscar'` — Twig
- `'Resultados de busqueda'` — Twig
- `'navegar'`, `'abrir'`, `'cerrar'` — Twig
- `'Error al buscar'` — JS
- `'Sin resultados'` — JS
- `'Pages'`, `'Content'`, `'Actions'`, `'AI Search'` — PHP

### Inline AI (GAP-AUD-009)
- `'Sugerencias IA'` — JS
- `'Genera exactamente 3 sugerencias'` — PHP (system prompt)

### Proactive Intelligence (GAP-AUD-010)
- `'N notificaciones de IA'` — Twig
- `'Insight Information'`, `'Targeting'`, `'AI Metadata'` — PHP
- `'AI-generated insight details.'` — PHP
- `'Who should see this insight.'` — PHP
- `'AI model and confidence.'` — PHP

### Voice AI (GAP-AUD-011)
- `'Entrada de voz'` — JS

### A2A (GAP-AUD-012)
- `'Multi-vertical SaaS AI agents for digital transformation.'` — PHP

### Demo Playground (GAP-AUD-003)
- `'Prueba nuestro asistente IA'` — Twig
- `'Escribe un mensaje o elige una sugerencia'` — Twig
- `'consultas restantes'` — JS

### Pricing (GAP-AUD-002)
- `'Capacidades de IA incluidas'` — Twig
- `'consultas IA/mes'` — Twig

### Onboarding (GAP-AUD-001)
- `'Recomendaciones personalizadas'` — Twig

---

## 19. Seguridad

### CSRF Protection

Todas las rutas API POST nuevas incluyen `_csrf_request_header_token: 'TRUE'`:

| Ruta | Metodo | CSRF |
|------|--------|------|
| `/api/v1/command-bar/search` | GET | Si (via CSRF header) |
| `/api/v1/inline-ai/suggest` | POST | Si |
| `/api/v1/a2a/task` | POST | Si |
| `/api/v1/a2a/task/{id}/cancel` | POST | Si |

### XSS Prevention

| Contexto | Proteccion |
|----------|-----------|
| Respuestas IA en DOM (JS) | `Drupal.checkPlain()` |
| Contenido IA en Twig | `\|safe_html` (NUNCA `\|raw`) |
| Command Bar resultados | `Drupal.checkPlain()` en label y description |
| Inline AI sugerencias | `textContent` (no `innerHTML`) |

### Tenant Isolation

| Entidad nueva | Campo tenant_id | Access handler |
|---------------|----------------|----------------|
| ProactiveInsight | Si | `ProactiveInsightAccessControlHandler` |
| A2ATask | Si (via agent config) | `A2ATaskAccessControlHandler` |
| ContentArticle (GAP-AUD-017) | Si (nuevo) | `ContentArticleAccessControlHandler` (modificado) |

### PII Protection

| Capa | Componentes |
|------|------------|
| Input | `AIGuardrailsService::validate()` en todos los nuevos prompts |
| Intermediate | `sanitizeRagContent()`, `sanitizeToolOutput()` en herramientas nuevas |
| Output | `maskOutputPII()` en todas las respuestas IA hacia el usuario |
| Streaming | `maskBufferPII()` en buffer acumulado (cross-chunk detection) |

### Voice Data Privacy

- Transcripcion client-side via Web Speech API (audio no sale del browser)
- Fallback server-side (Whisper): audio procesado y descartado inmediatamente, no almacenado
- No logging de audio en `AIObservabilityService`

### A2A Authentication

- Bearer token obligatorio para todas las rutas A2A
- HMAC-SHA256 signature opcional en header `X-Signature`
- Rate limiting: 100 tasks/hour por agent identity via Drupal Flood
- Task results sanitizados via `AIGuardrailsService::maskOutputPII()`

---

## 20. Fases de Implementacion

### Sprint 1: P0 — Foundations (80-110h)

| Orden | Gap | Descripcion | Horas | Dependencias |
|-------|-----|-------------|-------|-------------|
| 1 | GAP-AUD-014 | AI Test Coverage — tests base | 20-25 | Ninguna |
| 2 | GAP-AUD-016 | Blog Slugs | 8-10 | Ninguna |
| 3 | GAP-AUD-008 | Command Bar (Cmd+K) | 25-35 | Ninguna |
| 4 | GAP-AUD-005 | llms.txt MCP Discovery | 5-8 | Ninguna |
| 5 | GAP-AUD-001 | Onboarding Wizard AI | 8-12 | GAP-AUD-014 (test) |
| 6 | GAP-AUD-002 | Pricing AI Metering | 8-10 | Ninguna |
| 7 | GAP-AUD-003 | Demo/Playground Publico | 6-10 | Ninguna |

**Criterio de completitud Sprint 1:**
- Command Bar funcional con Cmd+K, keyboard nav, 3+ providers
- 20+ tests unitarios nuevos
- Blog slugs en rutas publicas
- llms.txt con MCP discovery y 7 Gen 2 agents

### Sprint 2: P1 — Core AI UX (90-120h)

| Orden | Gap | Descripcion | Horas | Dependencias |
|-------|-----|-------------|-------|-------------|
| 1 | GAP-AUD-009 | Inline AI | 20-25 | GAP-AUD-008 (patron JS) |
| 2 | GAP-AUD-010 | Proactive Intelligence | 25-30 | Ninguna |
| 3 | GAP-AUD-015 | CI/CD Prompt Regression | 10-15 | GAP-AUD-014 (test base) |
| 4 | GAP-AUD-017 | Content Hub tenant_id | 8-12 | GAP-AUD-016 (slugs) |
| 5 | GAP-AUD-004 | AI Dashboard GEO | 10-15 | Ninguna |
| 6 | GAP-AUD-006 | Schema.org GEO | 10-12 | Ninguna |
| 7 | GAP-AUD-007 | Dark Mode AI | 7-11 | GAP-AUD-008, 009 (componentes) |

**Criterio de completitud Sprint 2:**
- Inline AI sparkles en 5+ entity forms
- ProactiveInsight entity con bell notifications
- Prompt regression suite con 5+ golden fixtures
- ContentArticle con tenant_id y access handler

### Sprint 3: P2 — Advanced (100-140h)

| Orden | Gap | Descripcion | Horas | Dependencias |
|-------|-----|-------------|-------|-------------|
| 1 | GAP-AUD-011 | Voice AI Interface | 15-20 | GAP-AUD-009 (copilot widget) |
| 2 | GAP-AUD-012 | A2A Protocol | 20-25 | GAP-AUD-014 (tests) |
| 3 | GAP-AUD-013 | Vision/Multimodal | 15-20 | GAP-AUD-012 (A2A pattern) |
| 4 | GAP-AUD-018 | Skill Inference | 12-18 | Ninguna |
| 5 | GAP-AUD-019 | Adaptive Learning | 10-15 | Ninguna |
| 6 | GAP-AUD-021 | AI Writing in GrapesJS | 12-18 | GAP-AUD-009 (InlineAi) |
| 7 | GAP-AUD-022 | Service Matching | 16-24 | Ninguna |

**Criterio de completitud Sprint 3:**
- Voice AI funcional en copilot (Chrome, Edge)
- A2A Agent Card en `/.well-known/agent.json`
- 5 verticales con AI features nuevos
- MultiModal bridge implementado (audio + vision)

### Sprint 4: P3 — Scale (50-70h)

| Orden | Gap | Descripcion | Horas | Dependencias |
|-------|-----|-------------|-------|-------------|
| 1 | GAP-AUD-020 | Demand Forecasting | 15-20 | GAP-AUD-018 (patron vertical) |
| 2 | GAP-AUD-023 | Design System Docs | 15-20 | GAP-AUD-007 (dark mode tokens) |
| 3 | GAP-AUD-024 | Cost Attribution | 10-15 | GAP-AUD-002 (metering) |
| 4 | GAP-AUD-025 | Horizontal Scaling | 10-15 | Ninguna |

**Criterio de completitud Sprint 4:**
- Demand forecasting para merchants
- Design system page en `/admin/design-system`
- AI costs attributed per tenant en billing
- AI worker pool aislado configurado

### Resumen de horas por sprint

| Sprint | Horas min | Horas max |
|--------|-----------|-----------|
| Sprint 1 (P0) | 80 | 110 |
| Sprint 2 (P1) | 90 | 120 |
| Sprint 3 (P2) | 100 | 140 |
| Sprint 4 (P3) | 50 | 70 |
| **TOTAL** | **320** | **440** |

---

## 21. Estrategia de Testing

### Unit Tests (40+)

| # | Test | Modulo | Metodos cubiertos |
|---|------|--------|-------------------|
| 1 | `SmartBaseAgentCallAiTest` | jaraba_ai_agents | `callAiApi()` routing, guardrails, observability |
| 2 | `SmartBaseAgentToolUseTest` | jaraba_ai_agents | `callAiApiWithTools()`, `parseToolCall()` |
| 3 | `SmartBaseAgentNativeToolsTest` | jaraba_ai_agents | `callAiApiWithNativeTools()`, fallback |
| 4 | `ModelRouterServiceTest` | jaraba_ai_agents | `route()`, `assessComplexity()` |
| 5 | `ModelRouterHeuristicsTest` | jaraba_ai_agents | Prompt length, keywords, force_tier |
| 6 | `ToolRegistryTest` | jaraba_ai_agents | `register()`, `execute()`, `has()`, `filter()` |
| 7 | `ToolRegistryNativeInputTest` | jaraba_ai_agents | `generateNativeToolsInput()` |
| 8 | `AIGuardrailsValidateTest` | ecosistema_jaraba_core | `validate()` full pipeline |
| 9 | `AIGuardrailsPiiTest` | ecosistema_jaraba_core | `checkPII()` — US + Spanish patterns |
| 10 | `AIGuardrailsJailbreakTest` | ecosistema_jaraba_core | `checkJailbreak()` — 24 bilingual patterns |
| 11 | `AIGuardrailsRagSanitizeTest` | ecosistema_jaraba_core | `sanitizeRagContent()` — indirect injection |
| 12 | `AIGuardrailsOutputMaskTest` | ecosistema_jaraba_core | `maskOutputPII()` — 9 pattern types |
| 13 | `AIIdentityRuleTest` | ecosistema_jaraba_core | `apply()` — short and long variants |
| 14 | `TraceContextServiceTest` | jaraba_ai_agents | `startTrace()`, `startSpan()`, auto-parenting |
| 15 | `TraceContextSpanStackTest` | jaraba_ai_agents | Nested spans, `endSpan()` restores parent |
| 16 | `CommandRegistryServiceTest` | ecosistema_jaraba_core | `search()`, `addProvider()`, access filtering |
| 17 | `NavigationCommandProviderTest` | ecosistema_jaraba_core | Route-based search |
| 18 | `EntitySearchCommandProviderTest` | ecosistema_jaraba_core | Entity label search |
| 19 | `ActionCommandProviderTest` | ecosistema_jaraba_core | Action availability by permission |
| 20 | `InlineAiServiceTest` | jaraba_ai_agents | `suggest()` — with/without agent |
| 21 | `InlineAiServiceErrorTest` | jaraba_ai_agents | Agent throws, graceful degradation |
| 22 | `OnboardingAiRecommendationTest` | jaraba_onboarding | `getRecommendations()` — mock agent |
| 23 | `StreamingBufferTest` | jaraba_copilot_v2 | `shouldFlushBuffer()` — boundary conditions |
| 24 | `StreamingPiiMaskTest` | jaraba_copilot_v2 | `maskBufferPII()` — cross-chunk PII |
| 25 | `McpServerDispatchTest` | jaraba_ai_agents | JSON-RPC method dispatch, error codes |
| 26 | `McpServerToolCallTest` | jaraba_ai_agents | `tools/call` — execution + PII sanitization |
| 27 | `AgentCardControllerTest` | jaraba_ai_agents | Response structure, capabilities |
| 28 | `A2ATaskSubmitTest` | jaraba_ai_agents | Task creation, validation |
| 29 | `A2ATaskLifecycleTest` | jaraba_ai_agents | submitted → working → completed |
| 30 | `SkillInferenceServiceTest` | jaraba_candidate | `inferFromText()` — with/without agent |
| 31 | `AdaptiveLearningAiTest` | jaraba_lms | AI-enhanced recommendations |
| 32 | `DemandForecastingServiceTest` | jaraba_comercio_conecta | `forecast()` — with/without agent |
| 33 | `ServiceMatchingScoreTest` | jaraba_servicios_conecta | `calculateScore()` all 6 factors |
| 34 | `ContentArticleSlugTest` | jaraba_content_hub | Slug generation, transliteration |
| 35 | `ContentArticleSlugConverterTest` | jaraba_content_hub | `convert()` — found/not found |
| 36 | `ProactiveInsightFormTest` | jaraba_ai_agents | `getSectionDefinitions()`, `getFormIcon()` |
| 37 | `MultiModalTranscribeTest` | jaraba_ai_agents | `transcribeAudio()` — with/without provider |
| 38 | `MultiModalImageTest` | jaraba_ai_agents | `analyzeImage()` — validation, PII masking |
| 39 | `CostAlertPerTenantTest` | ecosistema_jaraba_core | Per-tenant thresholds |
| 40 | `LlmsTxtMcpSectionTest` | ecosistema_jaraba_core | MCP discovery content in llms.txt |

### Kernel Tests (15+)

| # | Test | Que verifica |
|---|------|-------------|
| 1 | `ProactiveInsightEntityKernelTest` | Entity CRUD, field validation, schema |
| 2 | `ProactiveInsightAccessKernelTest` | Tenant isolation, owner-only view |
| 3 | `ContentArticleSlugKernelTest` | Slug uniqueness, presave hook, route resolution |
| 4 | `ContentArticleTenantKernelTest` | tenant_id field install, access handler |
| 5 | `A2ATaskEntityKernelTest` | Task lifecycle persistence |
| 6 | `CommandRegistryIntegrationKernelTest` | Tagged service collection, full search |
| 7 | `InlineAiIntegrationKernelTest` | Service DI, controller response |
| 8 | `ToolRegistryCollectorKernelTest` | Service tag collection |
| 9 | `DesignTokenDocKernelTest` | Token extraction from SCSS |
| 10 | `SchemaOrgGeoKernelTest` | GEO schema generation |
| 11 | `ModelRouterConfigKernelTest` | YAML config loading, tier defaults |
| 12 | `OnboardingAiKernelTest` | Service injection, recommendation flow |
| 13 | `SkillInferenceKernelTest` | Service DI, SkillsService integration |
| 14 | `ServiceMatchingQdrantKernelTest` | Qdrant client injection, fallback |
| 15 | `CostAttributionKernelTest` | Observability → Metering pipeline |

### Prompt Regression Tests

| # | Fixture | Que verifica |
|---|---------|-------------|
| 1 | `identity_rule_long` | AIIdentityRule IDENTITY_PROMPT unchanged |
| 2 | `identity_rule_short` | AIIdentityRule IDENTITY_PROMPT_SHORT unchanged |
| 3 | `inline_ai_suggestion` | InlineAiService system prompt structure |
| 4 | `proactive_insight` | ProactiveInsightEngine analysis prompt |
| 5 | `skill_inference` | SkillInferenceService extraction prompt |
| 6 | `demand_forecast` | DemandForecastingService analysis prompt |
| 7 | `onboarding_recommendation` | OnboardingAiRecommendation prompt |

### Functional Tests (Manual)

| # | Test | Pasos |
|---|------|-------|
| 1 | Command Bar | Cmd+K → type "blog" → navigate with arrows → Enter opens page |
| 2 | Inline AI | Open article form → click sparkle on title → accept suggestion |
| 3 | Voice Input | Open copilot → click mic → speak → verify transcript submits |
| 4 | Proactive Bell | Login → verify bell count → click → see insights panel |
| 5 | Blog Slug | Create article → verify `/blog/{slug}` URL works |
| 6 | Dark Mode | Toggle dark mode → verify copilot, command bar, dashboard styled |

---

## 22. Verificacion y Despliegue

### Checklist pre-deploy

- [ ] `lando php -l` en todos los ficheros PHP nuevos/modificados — 0 errores
- [ ] `php vendor/bin/yaml-lint` en todos los YAML modificados — 0 errores
- [ ] `sass scss/main.scss:css/main.css --style=compressed` — compila sin errores
- [ ] `lando php vendor/bin/phpunit --testsuite=Unit` — 40+ tests pass
- [ ] `lando php vendor/bin/phpunit --testsuite=Kernel` — 15+ tests pass
- [ ] `lando php vendor/bin/phpunit --testsuite=PromptRegression` — 7 fixtures match
- [ ] Verificar `lando drush updb -y` ejecuta update hooks 10002, 10003 sin errores
- [ ] Verificar CSRF token requerido en todas las rutas API POST
- [ ] Verificar `Drupal.checkPlain()` en todos los `innerHTML` assignments
- [ ] Verificar `|safe_html` en todos los renders de contenido IA en Twig
- [ ] Verificar tenant_id check en access handlers de ProactiveInsight y ContentArticle
- [ ] Verificar `AIIdentityRule::apply()` en todos los prompts nuevos
- [ ] Verificar `prefers-color-scheme: dark` media query funciona
- [ ] Verificar Command Bar en mobile (320px viewport)
- [ ] Verificar Voice AI graceful degradation en Firefox (sin Web Speech API)
- [ ] Verificar `/.well-known/agent.json` responde correctamente
- [ ] Verificar llms.txt incluye MCP discovery y 7 Gen 2 agents

### Comandos de verificacion

```bash
# PHP lint
find web/modules/custom -name '*.php' -newer web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.info.yml | xargs -I{} lando php -l {}

# YAML lint
lando php vendor/bin/yaml-lint web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.services.yml
lando php vendor/bin/yaml-lint web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.routing.yml

# SCSS compile
cd web/themes/custom/ecosistema_jaraba_theme && sass scss/main.scss:css/main.css --style=compressed

# Tests
lando php vendor/bin/phpunit --testsuite=Unit --colors=always
lando php vendor/bin/phpunit --testsuite=Kernel --colors=always
lando php vendor/bin/phpunit --testsuite=PromptRegression --colors=always

# Update hooks
lando drush updb -y

# Cache clear
lando drush cr

# Config export (si aplica)
lando drush cex -y
```

### Rollback procedure

```bash
# Si algo falla post-deploy:

# 1. Revertir update hooks (entity field definitions)
lando drush php-eval "\Drupal::entityDefinitionUpdateManager()->applyUpdates();"

# 2. Clear cache
lando drush cr

# 3. Si persisten problemas, revertir al commit anterior
git revert HEAD --no-edit
lando drush updb -y
lando drush cr
```

---

## 23. Troubleshooting

| Problema | Causa | Solucion |
|----------|-------|----------|
| Command Bar no abre con Cmd+K | JS no cargado o conflicto con otro shortcut | Verificar que library `ecosistema_jaraba_core/command-bar` esta attached. Verificar consola browser. |
| Inline AI sparkle no aparece | `getInlineAiFields()` retorna array vacio | Override en subclase del form. Verificar `drupalSettings.inlineAiFields` en page source. |
| Voice mic no funciona | Browser sin Web Speech API (Firefox) | Graceful degradation por diseno. Solo Chrome, Edge, Safari soportan. |
| Proactive insights bell no muestra | Cron no ha ejecutado `ProactiveInsightEngine` | Ejecutar `lando drush cron`. Verificar queue `proactive_insight_engine`. |
| Blog slug 404 | ParamConverter no registrado | Verificar `jaraba_content_hub.services.yml` tiene `ContentArticleSlugConverter` registrado como `paramconverter`. |
| A2A task stuck en "submitted" | QueueWorker no ejecutado | Verificar `lando drush queue:run jaraba_ai_workloads`. Verificar Redis queue config. |
| Dark mode no afecta copilot | CSS custom properties no override | Verificar `_dark-mode.scss` tiene variables `--ej-copilot-*`. Recompilar SCSS. |
| MCP discovery faltante en llms.txt | `ToolRegistry` no inyectado | Verificar `ecosistema_jaraba_core.services.yml` tiene `@?jaraba_ai_agents.tool_registry` en LlmsTxtController. |
| Test kernel falla con "table not found" | MariaDB service container no disponible | Verificar `lando info` muestra MariaDB 10.11+. Verificar `phpunit.xml` database config. |
| Schema.org speakable no aparece | Preprocess no ejecutado | Verificar `template_preprocess_content_article()` incluye `speakable` en schema. Clear cache. |
| `MultiModalNotAvailableException` | Provider no configurado | Configurar `ai_provider_openai.settings` → `api_key`. Verificar `\Drupal::hasService()`. |
| ProactiveInsight access denied | Tenant mismatch | Verificar `tenant_id` del insight coincide con tenant del usuario. |
| Command Bar search lento | Sin indice en entity tables | Verificar indices en `content_article.title`, `page_content.title`. |

---

## 24. Referencias Cruzadas

### Archivos del proyecto referenciados

| Referencia | Ubicacion |
|-----------|-----------|
| SmartBaseAgent | `web/modules/custom/jaraba_ai_agents/src/Agent/SmartBaseAgent.php` |
| McpServerController | `web/modules/custom/jaraba_ai_agents/src/Controller/McpServerController.php` |
| MultiModalBridgeService | `web/modules/custom/jaraba_ai_agents/src/Service/MultiModalBridgeService.php` |
| ModelRouterService | `web/modules/custom/jaraba_ai_agents/src/Service/ModelRouterService.php` |
| ToolRegistry | `web/modules/custom/jaraba_ai_agents/src/Tool/ToolRegistry.php` |
| TraceContextService | `web/modules/custom/jaraba_ai_agents/src/Service/TraceContextService.php` |
| AIObservabilityService | `web/modules/custom/jaraba_ai_agents/src/Service/AIObservabilityService.php` |
| StreamingOrchestratorService | `web/modules/custom/jaraba_copilot_v2/src/Service/StreamingOrchestratorService.php` |
| CopilotStreamController | `web/modules/custom/jaraba_copilot_v2/src/Controller/CopilotStreamController.php` |
| PublicCopilotController | `web/modules/custom/jaraba_copilot_v2/src/Controller/PublicCopilotController.php` |
| AIGuardrailsService | `web/modules/custom/ecosistema_jaraba_core/src/Service/AIGuardrailsService.php` |
| AIIdentityRule | `web/modules/custom/ecosistema_jaraba_core/src/AI/AIIdentityRule.php` |
| PremiumEntityFormBase | `web/modules/custom/ecosistema_jaraba_core/src/Form/PremiumEntityFormBase.php` |
| ContentArticle | `web/modules/custom/jaraba_content_hub/src/Entity/ContentArticle.php` |
| SchemaGeneratorService | `web/modules/custom/jaraba_site_builder/src/Service/SchemaGeneratorService.php` |
| SchemaOrgService | `web/modules/custom/jaraba_page_builder/src/Service/SchemaOrgService.php` |
| TenantOnboardingWizardService | `web/modules/custom/jaraba_onboarding/src/Service/TenantOnboardingWizardService.php` |
| OnboardingOrchestratorService | `web/modules/custom/jaraba_onboarding/src/Service/OnboardingOrchestratorService.php` |
| PricingController | `web/modules/custom/ecosistema_jaraba_core/src/Controller/PricingController.php` |
| MetaSitePricingService | `web/modules/custom/ecosistema_jaraba_core/src/Service/MetaSitePricingService.php` |
| DemoController | `web/modules/custom/ecosistema_jaraba_core/src/Controller/DemoController.php` |
| UsageDashboardController | `web/modules/custom/ecosistema_jaraba_core/src/Controller/UsageDashboardController.php` |
| LlmsTxtController | `web/modules/custom/ecosistema_jaraba_core/src/Controller/LlmsTxtController.php` |
| SkillsService | `web/modules/custom/jaraba_candidate/src/Service/SkillsService.php` |
| AdaptiveLearningService | `web/modules/custom/jaraba_lms/src/Service/AdaptiveLearningService.php` |
| ServiceMatchingService | `web/modules/custom/jaraba_servicios_conecta/src/Service/ServiceMatchingService.php` |
| CostAlertService | `web/modules/custom/ecosistema_jaraba_core/src/Service/CostAlertService.php` |
| DesignTokenConfig | `web/modules/custom/ecosistema_jaraba_core/src/Entity/DesignTokenConfig.php` |
| SCSS Variables | `web/themes/custom/ecosistema_jaraba_theme/scss/_variables.scss` |
| Dark Mode SCSS | `web/themes/custom/ecosistema_jaraba_theme/scss/features/_dark-mode.scss` |
| llms.txt static | `web/llms.txt` |
| copilot-chat-widget.js | `web/modules/custom/jaraba_copilot_v2/js/copilot-chat-widget.js` |

### Documentos referenciados

| Documento | Ubicacion |
|-----------|-----------|
| Plan Elevacion IA Nivel5 v2 | `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Clase_Mundial_v2.md` |
| Plan Elevacion IA Nivel5 v1 | `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md` |
| Arquitectura IA Nivel5 | `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md` |
| Directrices v81 | `docs/00_DIRECTRICES_PROYECTO.md` |
| Flujo de trabajo v36 | `docs/00_FLUJO_TRABAJO_CLAUDE.md` |
| Indice General v106 | `docs/00_INDICE_GENERAL.md` |
| Documento Maestro v76 | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` |
| Arquitectura Theming | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` |
| Plan GrapesJS Content Hub | `docs/implementacion/2026-02-26_Plan_Implementacion_GrapesJS_Content_Hub_v1.md` |
| Plan Blog Clase Mundial | `docs/implementacion/2026-02-26_Blog_Clase_Mundial_Plan_Implementacion.md` |
| Aprendizaje #133 | `docs/tecnicos/aprendizajes/2026-02-26_elevacion_ia_10_gaps_streaming_mcp_native_tools.md` |
| Aprendizaje #129 | Elevacion IA 23 FIX items |
| Aprendizaje #130 | Auditoria post-implementacion IA |

### Especificaciones externas

| Spec | URL |
|------|-----|
| MCP Protocol 2025-11-25 | https://modelcontextprotocol.io/specification |
| llms.txt standard | https://llmstxt.org/ |
| Schema.org SpeakableSpecification | https://schema.org/SpeakableSpecification |
| Web Speech API | https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API |
| JSON-RPC 2.0 | https://www.jsonrpc.org/specification |
| A2A Protocol | https://google.github.io/A2A/ |

---

## 25. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-02-26 | IA Asistente (Claude Opus 4.6) | Creacion inicial del plan con 25 gaps, 25 secciones, 4 sprints, 320-440h estimadas |
