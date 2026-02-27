# Plan de Implementacion: Elevacion IA Clase Mundial — Ecosistema SaaS Multi-Tenant Multi-Vertical

**Fecha de creacion:** 2026-02-27 10:00
**Ultima actualizacion:** 2026-02-27 23:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 3.0.0
**Categoria:** Implementacion
**Modulos afectados:** `jaraba_ai_agents`, `jaraba_agents`, `jaraba_copilot_v2`, `jaraba_rag`, `ecosistema_jaraba_core`, `jaraba_content_hub`, `jaraba_page_builder`, `jaraba_candidate`, `jaraba_legal_intelligence`, `jaraba_business_tools`, `jaraba_job_board`, `jaraba_lms`, `jaraba_billing`, `ecosistema_jaraba_theme`, `jaraba_customer_success`, `jaraba_workflows`, `jaraba_comercio_conecta`, `jaraba_geo`
**Spec:** `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md` (v2.0.0 — 30 hallazgos)
**Impacto:** 30 hallazgos (25 RESUELTOS, 3 PARCIALES, 2 PENDIENTES), score actual **93/100**, target **100/100**

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Alcance](#13-alcance)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
   - 1.5 [Estimacion](#15-estimacion)
   - 1.6 [Riesgos y mitigacion](#16-riesgos-y-mitigacion)
2. [Requisitos Previos](#2-requisitos-previos)
3. [Tabla de Correspondencia: Hallazgos -> Acciones](#3-tabla-de-correspondencia-hallazgos---acciones)
4. [Tabla de Cumplimiento de Directrices](#4-tabla-de-cumplimiento-de-directrices)
5. [Sprint 1 — Seguridad e Integridad (COMPLETADO)](#5-sprint-1--seguridad-e-integridad)
   - 5.1 [S1-01: Guardrails en ToolRegistry.execute()](#51-s1-01-guardrails-en-toolregistryexecute)
   - 5.2 [S1-02: Approval gating en herramientas](#52-s1-02-approval-gating-en-herramientas)
   - 5.3 [S1-03: Sandbox GrapesJS canvas (CSP)](#53-s1-03-sandbox-grapesjs-canvas-csp)
   - 5.4 [S1-04: Audit tenant_id en handlers](#54-s1-04-audit-tenant_id-en-handlers)
   - 5.5 [S1-05: Datos reales en RecruiterAssistant](#55-s1-05-datos-reales-en-recruiterassistant)
   - 5.6 [S1-06: Guardrails mandatory en paths criticos](#56-s1-06-guardrails-mandatory-en-paths-criticos)
6. [Sprint 2 — Revenue y Operaciones](#6-sprint-2--revenue-y-operaciones)
   - 6.1 [S2-01: Usage metering real](#61-s2-01-usage-metering-real)
   - 6.2 [S2-02: Feature flag service](#62-s2-02-feature-flag-service)
   - 6.3 [S2-03: Provisioning automatizado](#63-s2-03-provisioning-automatizado)
   - 6.4 [S2-04: Per-tenant analytics dashboard](#64-s2-04-per-tenant-analytics-dashboard)
7. [Sprint 3 — IA de Clase Mundial](#7-sprint-3--ia-de-clase-mundial)
   - 7.1 [S3-01: AgentLongTermMemory con Qdrant](#71-s3-01-agentlongtermemory-con-qdrant)
   - 7.2 [S3-02: ReActLoop con parsing completo](#72-s3-02-reactloop-con-parsing-completo)
   - 7.3 [S3-03: IA proactiva](#73-s3-03-ia-proactiva)
   - 7.4 [S3-04: LearningPathAgent](#74-s3-04-learningpathagent)
   - 7.5 [S3-05: ModelRouter activation en Gen 2](#75-s3-05-modelrouter-activation-en-gen-2)
   - 7.6 [S3-06: A/B testing de prompts](#76-s3-06-ab-testing-de-prompts)
8. [Sprint 4 — Performance y UX](#8-sprint-4--performance-y-ux)
   - 8.1 [S4-01: Core Web Vitals](#81-s4-01-core-web-vitals)
   - 8.2 [S4-02: CSS code splitting](#82-s4-02-css-code-splitting)
   - 8.3 [S4-03: GEO/Local SEO](#83-s4-03-geocal-seo)
   - 8.4 [S4-04: Workflow automation engine](#84-s4-04-workflow-automation-engine)
   - 8.5 [S4-05: Plan de testing progresivo](#85-s4-05-plan-de-testing-progresivo)
9. [Sprint 5 — 100% Clase Mundial (NUEVO v2.0)](#9-sprint-5--100-clase-mundial)
   - 9.1 [S5-01: Migracion Gen 1 a Gen 2](#91-s5-01-migracion-gen-1-a-gen-2)
   - 9.2 [S5-02: Agent evaluation framework](#92-s5-02-agent-evaluation-framework)
   - 9.3 [S5-03: Prompt versioning y management](#93-s5-03-prompt-versioning-y-management)
   - 9.4 [S5-04: SemanticCacheService real](#94-s5-04-semanticcacheservice-real)
   - 9.5 [S5-05: ContextWindowManager progressive trimming](#95-s5-05-contextwindowmanager-progressive-trimming)
   - 9.6 [S5-06: Brand voice per-tenant completo](#96-s5-06-brand-voice-per-tenant-completo)
   - 9.7 [S5-07: Agent collaboration patterns](#97-s5-07-agent-collaboration-patterns)
   - 9.8 [S5-08: Schema.org completo](#98-s5-08-schemaorg-completo)
   - 9.9 [S5-09: PersonalizationEngine con ML](#99-s5-09-personalizationengine-con-ml)
   - 9.10 [S5-10: Residuales de Sprints 1-4](#910-s5-10-residuales-de-sprints-1-4)
10. [Arquitectura Frontend: Directrices de Cumplimiento](#10-arquitectura-frontend-directrices-de-cumplimiento)
    - 10.1-10.12 [Subsecciones existentes sin cambio]
11. [Especificaciones Tecnicas de Aplicacion](#11-especificaciones-tecnicas-de-aplicacion)
12. [Configuracion](#12-configuracion)
13. [Estrategia de Testing](#13-estrategia-de-testing)
14. [Verificacion y Despliegue](#14-verificacion-y-despliegue)
15. [Troubleshooting](#15-troubleshooting)
16. [Referencias Cruzadas](#16-referencias-cruzadas)
17. [Registro de Cambios](#17-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

> **ACTUALIZACION v2.0.0:** Re-evaluacion completa. 11/20 hallazgos originales RESUELTOS. Target elevado de 85/100 a **100/100**. +10 hallazgos nuevos (HAL-AI-21..30). Sprint 5 anadido. Secciones obligatorias faltantes incorporadas (Requisitos Previos, Configuracion, Troubleshooting).

Se implementa un plan de elevacion integral de la plataforma SaaS desde su estado actual (**82/100**, post-resoluciones) hasta el nivel **100/100 clase mundial** en 7 dimensiones: agentes IA, servicios transversales, modulos verticales, multi-tenancy, SEO, page builder y UX. El plan cubre 30 hallazgos (20 originales + 10 nuevos) identificados en la auditoria `2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md` (v2.0.0).

### 1.2 Por que se implementa

La auditoria v2.0 revela que la plataforma ha cerrado los gaps criticos de seguridad (guardrails + approval gating), memoria (Qdrant), razonamiento (ReActLoop), y IA proactiva (ProactiveInsights + ChurnPrediction). De 18 capacidades obligatorias, ahora cumple 14/18 (era 9/18). Los gaps residuales para 100% incluyen: CSS monolitico (736KB), agentes Gen 1 sin migracion a Gen 2, evaluacion de agentes inexistente, prompt versioning ausente, cache semantico no integrado, collaboration patterns incompletos (solo sequential y plan-first de 5 patrones), y brand voice per-tenant incompleto. El benchmark de mercado exige que las 18 capacidades esten al 100%.

### 1.3 Alcance

**En scope:**
- Seguridad de la capa IA (guardrails, approval, sandbox)
- Metering y billing real
- Feature flags runtime
- Memoria de largo plazo para agentes (Qdrant)
- Razonamiento multi-step (ReAct)
- IA proactiva (insights, anomaly detection)
- Agentes faltantes (LearningPath, DataAnalyst)
- Core Web Vitals optimization
- CSS code splitting
- GEO/Local SEO
- Testing coverage

**Fuera de scope:**
- Multimodal (voice + vision) — requiere API de terceros no contratadas
- Agent marketplace — requiere infraestructura de publicacion
- Personalizacion adaptativa completa — requiere ML pipeline no existente

### 1.4 Filosofia de implementacion

1. **Seguridad primero:** Ninguna feature nueva se despliega hasta que los gaps de seguridad estan cerrados
2. **Clase mundial sin atajos:** Cada implementacion cumple TODAS las directrices del proyecto
3. **Incremental y verificable:** Cada sprint produce artefactos testeable en produccion
4. **Zero-region policy:** Todas las paginas frontend usan templates Twig limpias sin regiones de Drupal
5. **Mobile-first:** Todo el CSS se escribe para movil primero, expandiendo con `@media (min-width: ...)`
6. **Dart Sass moderno:** `@use` en lugar de `@import`, `color.adjust()` en lugar de `darken()`/`lighten()`
7. **Variables inyectables:** Toda personalizacion visual via CSS Custom Properties desde theme settings de Drupal
8. **Textos siempre traducibles:** `$this->t()` en PHP, `{{ 'texto'|t }}` en Twig, `Drupal.t()` en JS
9. **Iconos duotone:** `jaraba_icon('category', 'name', { variant: 'duotone' })` — nunca emojis ni SVG directo
10. **Modales para CRUD:** Todas las acciones crear/editar/ver abren en slide-panel o modal

### 1.5 Estimacion

| Sprint | Duracion | Horas Estimadas | Prioridad | Estado v2.0 |
|--------|----------|----------------|-----------|-------------|
| Sprint 1: Seguridad | 1-2 semanas | 60-80h | CRITICA | **COMPLETADO** (4/6 resueltos, 2 parciales) |
| Sprint 2: Revenue | 2-4 semanas | 80-120h | ALTA | **MAYORMENTE COMPLETADO** (2/4 resueltos, 2 pendientes) |
| Sprint 3: IA Clase Mundial | 4-8 semanas | 120-180h | ALTA | **MAYORMENTE COMPLETADO** (4/6 resueltos, 2 parciales) |
| Sprint 4: Performance + UX | 2-4 semanas | 60-100h | MEDIA | **PARCIAL** (2/5 resueltos, 3 abiertos/parciales) |
| Sprint 5: 100% Clase Mundial | 3-5 meses | 160-200h | ALTA | **NUEVO** — 10 items + residuales |
| **Total** | **13-23 semanas** | **480-680h** | — | — |

### 1.6 Riesgos y mitigacion

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Qdrant no disponible en produccion | Media | Alto | Fallback a Redis para memoria, implementar `hasService()` pattern |
| Breaking changes en handlers existentes | Alta | Medio | Feature flag para activar/desactivar tenant verification progresivamente |
| CSS splitting rompe estilos | Media | Medio | Build pipeline con visual regression tests |
| Metering real impacta performance | Baja | Alto | Async logging via queue, no synchronous en request |
| IA proactiva genera ruido | Media | Medio | Umbrales configurables, quiet hours por tenant |

---

## 2. Requisitos Previos

### 2.1 Software Requerido

| Software | Version Minima | Proposito |
|----------|----------------|-----------|
| PHP | 8.4 | Runtime de Drupal 11 |
| Drupal | 11.x | CMS framework |
| MariaDB | 10.11+ | Base de datos relacional |
| Redis | 7.4 | Cache y session storage |
| Qdrant | 1.7+ | Vector database para memoria semantica, RAG, cache semantico |
| Composer | 2.x | Gestion de dependencias PHP |
| Node.js | 20+ | Build tools (Dart Sass, GrapesJS) |
| Dart Sass | 1.70+ | Compilacion SCSS |
| Lando | 3.x | Entorno de desarrollo local |

### 2.2 Conocimientos Previos

- Arquitectura Drupal 11 (entities, services, plugins, events)
- PHP 8.4 (readonly properties, enums, attributes, fibers)
- Patron PremiumEntityFormBase del proyecto (237 forms migrados)
- SmartBaseAgent Gen 2 pattern (doExecute, tool use, model routing)
- GrapesJS API (blocks, components, StorageManager, plugins)
- Qdrant API (collections, points, vector search, filtering)
- Drupal AI module (AiProviderPluginManager, ChatInput, embeddings)

### 2.3 Accesos Necesarios

- [ ] Repositorio Git con permisos de push
- [ ] Entorno Lando local funcional (`lando start`)
- [ ] API key de proveedor IA (Anthropic o OpenAI) en `config/deploy/settings.secrets.php`
- [ ] Qdrant server accesible (local o cloud) para Sprints 3 y 5
- [ ] Stripe API keys (test mode) para Sprint 2 (provisioning)
- [ ] Acceso a Google Search Console (Sprint 4, SEO)

---

## 3. Tabla de Correspondencia: Hallazgos -> Acciones

| HAL | Titulo | Sprint | Accion | Archivos Afectados | Estimacion |
|-----|--------|--------|--------|-------------------|------------|
| HAL-AI-01 | Tool execution sin guardrails | S1-01 | Anadir `sanitizeToolOutput()` en `executeTool()` | `jaraba_ai_agents/src/Service/AgentToolRegistry.php` | 4-6h |
| HAL-AI-02 | Usage metering simulado | S2-01 | Implementar queries reales + async metering | `ecosistema_jaraba_core/src/Service/UsageLimitsService.php`, nuevo `TenantMeteringService` | 20-30h |
| HAL-AI-03 | Tenant isolation inconsistente | S1-04 | Audit + implementacion sistematica | ~80 `*AccessControlHandler.php` | 16-24h |
| HAL-AI-04 | AgentLongTermMemory vacio | S3-01 | Implementar remember/recall con Qdrant | `jaraba_agents/src/Service/AgentLongTermMemoryService.php` | 20-30h |
| HAL-AI-05 | ReActLoop sin parsing | S3-02 | Implementar THOUGHT/ACTION/OBSERVATION parsing | `jaraba_ai_agents/src/Service/ReActLoopService.php` | 16-24h |
| HAL-AI-06 | IA proactiva inexistente | S3-03 | Cron-based insights + anomaly detection | Nuevo `ProactiveInsightsService`, `ChurnPredictionService` | 30-40h |
| HAL-AI-07 | LearningTutor skeleton | S3-04 | Migrar a SmartBaseAgent Gen 2 | `jaraba_lms/src/Agent/LearningTutorAgent.php` -> `LearningPathAgent` | 12-16h |
| HAL-AI-08 | Datos mock RecruiterAssistant | S1-05 | Reemplazar con queries reales | `jaraba_job_board/src/Agent/RecruiterAssistantAgent.php` | 6-8h |
| HAL-AI-09 | Canvas no sandboxed | S1-03 | CSP headers en iframe | `jaraba_page_builder/js/grapesjs-jaraba-canvas.js`, controllers | 8-12h |
| HAL-AI-10 | Core Web Vitals | S4-01 | fetchpriority, srcset, WebP, aspect-ratio | Templates Twig, SCSS, image pipeline | 20-30h |
| HAL-AI-11 | Feature flags hardcoded | S2-02 | Feature flag service runtime | Nuevo `FeatureFlagService`, `FeatureFlag` entity | 16-24h |
| HAL-AI-12 | CSS monolitico | S4-02 | Code splitting por ruta | `ecosistema_jaraba_theme/scss/`, `package.json` | 12-16h |
| HAL-AI-13 | Sin srcset/WebP/AVIF | S4-01 | Image pipeline + `<picture>` | Templates, image styles | Incluido en S4-01 |
| HAL-AI-14 | GEO/Local SEO ausente | S4-03 | LocalBusiness schema + geo meta | `SeoService.php`, nuevos hooks | 8-12h |
| HAL-AI-15 | Test coverage 34% | S4-05 | Plan testing progresivo | Nuevos archivos de test | Continuo |
| HAL-AI-16 | ModelRouter sin evidencia uso | S3-05 | Verificar + activar en Gen 2 | 8 agentes SmartBaseAgent | 4-6h |
| HAL-AI-17 | Guardrails opcionales | S1-06 | Hacer mandatory en paths criticos | `services.yml` de 3-4 modulos | 2-3h |
| HAL-AI-18 | Approval no enforced | S1-02 | Check `requiresApproval` antes de execute | `AgentToolRegistry.php` | 4-6h |
| HAL-AI-19 | Workflow automation | S4-04 | WorkflowAutomationAgent + engine | Nuevo modulo `jaraba_workflows` | 40-60h |
| HAL-AI-20 | Personalizacion adaptativa | Fuera de scope | Largo plazo | — | — |

---

## 3. Tabla de Cumplimiento de Directrices

Esta tabla garantiza que TODA implementacion cumple con las directrices vigentes del proyecto. Cada accion del plan DEBE verificar estas directrices antes de marcarse como completada.

| Directriz | Regla | Aplicacion en este Plan | Verificacion |
|-----------|-------|------------------------|--------------|
| **PREMIUM-FORMS-PATTERN-001** | Todo form extiende PremiumEntityFormBase | Nuevos forms (FeatureFlag, WorkflowRule) usan PremiumEntityFormBase con `getSectionDefinitions()` y `getFormIcon()`. Fieldsets PROHIBIDOS. | `grep -rl "extends ContentEntityForm" \| grep -v PremiumEntityFormBase \| grep -v SettingsForm` = 0 |
| **TENANT-ISOLATION-ACCESS-001** | Todo handler con tenant_id verifica tenant | S1-04 aplica checkTenantIsolation() sistematicamente. Published = public, update/delete = tenant match. | `grep -rn "checkAccess" \| grep -v "checkTenantIsolation"` en handlers con tenant_id = 0 |
| **TENANT-BRIDGE-001** | Usar TenantBridgeService para Tenant<->Group | Todo servicio nuevo que necesite tenant context usa `@ecosistema_jaraba_core.tenant_bridge`. NUNCA loadStorage('group') con Tenant IDs. | Review de DI en services.yml |
| **ENTITY-PREPROCESS-001** | template_preprocess_{entity_type}() en .module | Nuevas entidades (FeatureFlag, WorkflowRule, ProactiveInsight) tienen preprocess en su `.module`. Entity en `$variables['elements']['#{entity_type}']`. | `grep -rn "template_preprocess_"` para cada nueva entidad |
| **PRESAVE-RESILIENCE-001** | hasService() + try-catch en presave | Todo presave que invoque servicios opcionales usa el patron. Entity saves NUNCA fallan por servicios opcionales. | Code review de hooks presave |
| **SLIDE-PANEL-RENDER-001** | renderPlain() para slide-panel, no render() | Nuevos controllers de CRUD (FeatureFlag, Workflow) usan `renderPlain()` + `$form['#action'] = $request->getRequestUri()`. | Verificar en controllers con `isSlidePanelRequest()` |
| **FORM-CACHE-001** | NUNCA setCached(TRUE) incondicionalmente | Ningun form nuevo llama setCached(TRUE). Drupal rebuild desde route parameters. | `grep -rn "setCached(TRUE)"` = 0 en nuevos forms |
| **ROUTE-LANGPREFIX-001** | Url::fromRoute() para JS fetch, NUNCA hardcoded | Todas las URLs en JS usan `Drupal.url()` o `drupalSettings`. Nunca paths hardcoded con `/es/`. | `grep -rn "fetch('/" \| grep -v "Drupal.url"` = 0 |
| **ICON-CONVENTION-001** | jaraba_icon('category', 'name', { variant: 'duotone' }) | Todos los templates nuevos usan `jaraba_icon()`. NUNCA emojis como iconos visuales. NUNCA SVG directo en templates. | `grep -rn "emoji\|&#x" *.twig` = 0 en nuevos templates |
| **ICON-DUOTONE-001** | Variante duotone por defecto en premium UI | Todos los iconos en premium forms, dashboards y slide-panels usan variant 'duotone'. Solo 'outline' en breadcrumbs. | Visual review |
| **SAFE-HTML-001** | User content: \|safe_html. NUNCA \|raw | Templates nuevos usan `\|safe_html` para contenido de usuario. Solo `\|raw` para JSON-LD auto-generado. | `grep -rn "\|raw" *.twig` = solo JSON-LD |
| **CSS-STICKY-001** | position: sticky por defecto, fixed solo en landing | Headers usan `position: sticky`. Override solo en `body.landing-page`. | SCSS review |
| **BRAND-COLOR-001** | Paleta oficial Jaraba | Todos los SCSS nuevos usan `var(--ej-color-*)`. NUNCA hex directo de Tailwind (#2563eb, #374151, etc). | `grep -rn "#2563eb\|#374151\|#6b7280"` = 0 |
| **ZERO-REGION-001** | Variables SOLO via hook_preprocess_page() | Nuevas paginas frontend reciben datos via preprocess, NUNCA via controller render array directo. | Code review de controllers |
| **FORM-MSG-001** | Inyectar messages antes del form | Todo template de form inyecta `{{ messages }}` ANTES de `{{ form }}` via preprocess. | Template review |
| **LEGAL-ROUTE-001** | Rutas legales canonicas en espanol | No se crean nuevas rutas legales. | N/A |
| **SECRET-MGMT-001** | NUNCA secrets en config/sync | Ningun secret en YAML. Todo via env vars en settings.secrets.php. | `grep -rn "api_key\|secret_key\|password" config/sync/ \| grep -v '""' \| grep -v "'''"` = 0 |
| **SERVICE-CALL-CONTRACT-001** | Signatures exactas en calls entre servicios | Todo servicio nuevo verifica firma con `grep -rn 'service->method'`. | `grep` antes de merge |
| **ACCESS-STRICT-001** | Igualdad estricta `===` en handlers | Todo handler nuevo usa `(int)$entity->getOwnerId() === (int)$account->id()`. | `grep -rn "== \$account->id()"` = solo `===` |
| **CSRF-API-001** | `_csrf_request_header_token: 'TRUE'` en routes API | Todas las rutas PATCH/POST nuevas incluyen CSRF. JS usa `getCsrfToken()` cacheado. | Route yml review |
| **FIELD-UI-SETTINGS-TAB-001** | Tab default en links.task.yml | Toda entidad con `field_ui_base_route` tiene tab 'Configuracion' como base_route. | links.task.yml review |
| **OPTIONAL-SERVICE-DI-001** | @? para servicios opcionales | Servicios de modulos potencialmente no instalados usan `@?`. Constructor con `?ServiceInterface $service = NULL`. | services.yml review |
| **SMART-AGENT-CONSTRUCTOR-001** | 10 args en SmartBaseAgent | Nuevos agentes Gen 2 siguen el patron: 6 core + 4 opcionales con @?. | Constructor review |
| **AI-IDENTITY-001** | AIIdentityRule::apply() en todos los agentes | Nuevos agentes/copilots prepend la regla de identidad. NUNCA revelan ser Claude/GPT. | System prompt review |
| **JAILBREAK-DETECT-001** | checkJailbreak() bilingue | Guardrails validate() incluye jailbreak detection ES/EN. | Test con prompts de ataque |
| **MODEL-ROUTING-CONFIG-001** | Tiers en config YAML, no hardcoded | Nuevos agentes usan `ModelRouterService::route()`, no hardcoded tier. | Code review de `doExecute()` |
| **VERTICAL-CANONICAL-001** | 10 nombres canonicos de vertical | Nuevos servicios usan nombres de `BaseAgent::VERTICALS`. | Constant reference |
| **Dart Sass moderno** | @use, color.adjust(), sin @import ni darken() | Todos los SCSS nuevos usan `@use 'variables' as *`. NUNCA `@import`. NUNCA `darken()`/`lighten()`. | `grep -rn "@import\|darken(\|lighten(" scss/` = 0 en nuevos archivos |
| **Textos traducibles** | t() en PHP, \|t en Twig, Drupal.t() en JS | TODAS las cadenas visibles al usuario estan envueltas en funciones de traduccion. | `grep` de strings sin t() en nuevos archivos |

---

## 4. Sprint 1 — Seguridad e Integridad

**Duracion:** 1-2 semanas
**Prioridad:** CRITICA — Bloquea produccion segura
**Objetivo:** Cerrar todos los gaps de seguridad identificados

### 4.1 S1-01: Guardrails en ToolRegistry.execute()

**Hallazgo:** HAL-AI-01 — Las herramientas se ejecutan sin sanitizacion de output ni verificacion de guardrails.

**Estado actual:**
El archivo `jaraba_ai_agents/src/Service/AgentToolRegistry.php` tiene 89 lineas. El metodo `executeTool()` hace directamente `call_user_func_array()` sin pasar el resultado por ninguna capa de seguridad. El `AIGuardrailsService` esta inyectado como dependencia opcional (`@?`) pero nunca se utiliza en el flujo de ejecucion de herramientas.

**Que se debe implementar:**

1. **Sanitizacion del output de herramientas:** Despues de cada ejecucion de herramienta, el resultado debe pasar por `AIGuardrailsService::maskOutputPII()` para eliminar PII que la herramienta pueda haber expuesto (por ejemplo, un `QueryDatabaseTool` que retorne emails o DNIs de la base de datos).

2. **Validacion del output contra jailbreak indirecto:** El resultado de una herramienta podria contener instrucciones de prompt injection (indirect prompt injection). El output debe pasar por `AIGuardrailsService::sanitizeRagContent()` antes de ser reenviado al LLM en la siguiente iteracion del tool use loop.

3. **Validacion de parametros de entrada:** Antes de ejecutar la herramienta, verificar que los parametros proporcionados por el LLM coinciden con el schema esperado de la herramienta. Actualmente no hay validacion.

**Logica de implementacion:**

```php
// En AgentToolRegistry::executeTool()
public function executeTool(string $toolId, array $params): string {
    $tool = $this->tools[$toolId] ?? NULL;
    if (!$tool) {
        return json_encode(['error' => 'Tool not found: ' . $toolId]);
    }

    // 1. Validar parametros contra schema
    if (!$this->validateParams($toolId, $params)) {
        return json_encode(['error' => 'Invalid parameters for tool: ' . $toolId]);
    }

    // 2. Check approval requirement
    if ($this->requiresApproval($toolId) && !$this->isApproved($toolId, $params)) {
        return json_encode(['error' => 'Tool requires approval', 'pending' => TRUE]);
    }

    // 3. Ejecutar herramienta
    try {
        $result = call_user_func_array($tool['callable'], [$params]);
    } catch (\Exception $e) {
        $this->logger->error('Tool execution failed: @tool - @error', [
            '@tool' => $toolId,
            '@error' => $e->getMessage(),
        ]);
        return json_encode(['error' => 'Tool execution failed']);
    }

    // 4. Sanitizar output — PII masking
    if ($this->guardrails) {
        $result = $this->guardrails->maskOutputPII($result);
        $result = $this->guardrails->sanitizeRagContent($result);
    }

    return $result;
}
```

**Archivos afectados:**
- `web/modules/custom/jaraba_ai_agents/src/Service/AgentToolRegistry.php` — Modificar `executeTool()`
- `web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.services.yml` — Cambiar `@?` a `@` para guardrails en este servicio

**Directrices aplicables:**
- PRESAVE-RESILIENCE-001: try-catch alrededor de la ejecucion
- SERVICE-CALL-CONTRACT-001: Verificar firma de maskOutputPII() y sanitizeRagContent()
- AI-GUARDRAILS-PII-001: PII masking bidireccional (input + output)

**Estimacion:** 4-6 horas
**Verificacion:** Test unitario que ejecuta tool con output conteniendo PII (DNI, email) y verifica que se enmascara.

### 4.2 S1-02: Approval gating en herramientas

**Hallazgo:** HAL-AI-18 — PendingApprovalService inyectado pero nunca consultado.

**Estado actual:**
`UpdateEntityTool` tiene `requiresApproval: true` en su metadata, pero `AgentToolRegistry::executeTool()` no consulta `PendingApprovalService` antes de ejecutar. Las herramientas que deberian requerir aprobacion humana se ejecutan automaticamente.

**Que se debe implementar:**

1. **Pre-check de approval:** Antes de ejecutar cualquier herramienta marcada con `requiresApproval`, consultar `PendingApprovalService` para verificar si existe aprobacion pendiente o si ya fue aprobada.

2. **Creacion de solicitud de aprobacion:** Si la herramienta requiere aprobacion y no existe solicitud, crear una via `PendingApprovalService::createRequest()` con los parametros de la herramienta como contexto.

3. **Response pattern para herramientas pendientes:** Retornar un JSON especial `{"pending_approval": true, "request_id": "uuid"}` que el tool use loop interprete como "esperar aprobacion" en lugar de reintentar.

**Archivos afectados:**
- `web/modules/custom/jaraba_ai_agents/src/Service/AgentToolRegistry.php`
- `web/modules/custom/jaraba_agents/src/Service/ApprovalManagerService.php` (verificar interface)

**Estimacion:** 4-6 horas

### 4.3 S1-03: Sandbox GrapesJS canvas (CSP)

**Hallazgo:** HAL-AI-09 — Canvas iframe no tiene Content-Security-Policy.

**Estado actual:**
El editor GrapesJS renderiza HTML de usuario en un iframe sin restricciones de seguridad. Si un usuario (o un atacante con acceso a la cuenta) inserta `<script>alert('xss')</script>` en el canvas_data, el script se ejecutara en el contexto del iframe. La sanitizacion actual usa regex custom (`sanitizePageBuilderHtml()`), pero regex no es fiable para sanitizacion HTML compleja.

**Que se debe implementar:**

1. **CSP headers en el iframe:** Anadir `sandbox="allow-same-origin allow-forms"` al iframe del canvas. Esto previene la ejecucion de scripts mientras permite la edicion de formularios y el acceso a cookies de sesion.

2. **CSP meta tag en la respuesta del editor:** Los controllers `CanvasEditorController` y `ArticleCanvasEditorController` deben anadir:
   ```php
   $response->headers->set('Content-Security-Policy',
       "script-src 'self' 'unsafe-inline'; object-src 'none'; base-uri 'self';"
   );
   ```

3. **DOMPurify en el cliente:** Antes de insertar HTML en el canvas via `editor.setComponents()`, pasar por DOMPurify (biblioteca JS de 16KB que cubre edge cases que regex no puede).

**Directrices aplicables:**
- CANVAS-SANITIZATION-001: Las 3 capas de sanitizacion existentes se mantienen
- TWIG-XSS-001: Output HTML con `|safe_html` en templates publicos
- El editor GrapesJS es desktop-only (min-width: 1024px) segun las directrices

**Archivos afectados:**
- `web/modules/custom/jaraba_page_builder/js/grapesjs-jaraba-canvas.js` — Anadir DOMPurify al init
- `web/modules/custom/jaraba_page_builder/src/Controller/CanvasEditorController.php` — CSP headers
- `web/modules/custom/jaraba_content_hub/src/Controller/ArticleCanvasEditorController.php` — CSP headers
- `web/modules/custom/jaraba_page_builder/jaraba_page_builder.libraries.yml` — Anadir DOMPurify como dependencia

**Estimacion:** 8-12 horas

### 4.4 S1-04: Audit tenant_id en handlers

**Hallazgo:** HAL-AI-03 — Solo ~5-10% de handlers verifican tenant_id.

**Estado actual:**
El patron `TENANT-ISOLATION-ACCESS-001` define que todo AccessControlHandler para entidades con campo `tenant_id` DEBE verificar que el tenant de la entidad coincide con el del usuario en operaciones `update` y `delete`. Solo `ContentAuthorAccessControlHandler` lo implementa correctamente. Cientos de otros handlers no lo hacen.

**Que se debe implementar:**

1. **Script de auditoria:** Crear un script que:
   - Encuentre todas las entidades con campo `tenant_id` (via `baseFieldDefinitions()`)
   - Identifique sus AccessControlHandlers
   - Verifique si implementan `checkTenantIsolation()`

2. **Implementacion sistematica:** Para cada handler que no cumpla, anadir el metodo `checkTenantIsolation()` siguiendo el patron de `ContentAuthorAccessControlHandler`:

```php
protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
    if (!$entity->hasField('tenant_id')) {
        return NULL;
    }
    $entityTenantId = (int) ($entity->get('tenant_id')->target_id ?? 0);
    if ($entityTenantId === 0) {
        return NULL;
    }
    try {
        $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        $currentTenant = $tenantContext->getCurrentTenant();
        $userTenantId = (int) $currentTenant->id();
        if ($entityTenantId !== $userTenantId) {
            return AccessResult::forbidden('Tenant mismatch');
        }
    } catch (\Exception $e) {
        // Log but don't block — PRESAVE-RESILIENCE-001
    }
    return NULL;
}
```

3. **DefaultEntityAccessControlHandler mejorado:** Considerar mover `checkTenantIsolation()` al fallback handler default para cubrir automaticamente todas las entidades con tenant_id.

**Directrices aplicables:**
- TENANT-ISOLATION-ACCESS-001: view = public, update/delete = tenant match
- ACCESS-STRICT-001: (int) casting estricto
- OPTIONAL-SERVICE-DI-001: TenantContextService puede no estar disponible en tests

**Archivos afectados:**
- `web/modules/custom/ecosistema_jaraba_core/src/Access/DefaultEntityAccessControlHandler.php` — Anadir checkTenantIsolation()
- ~20 AccessControlHandlers en modulos verticales que tengan entidades con tenant_id

**Estimacion:** 16-24 horas

### 4.5 S1-05: Datos reales en RecruiterAssistant

**Hallazgo:** HAL-AI-08 — Datos hardcoded en lugar de queries reales.

**Estado actual:**
`RecruiterAssistantAgent::screenCandidates()` retorna siempre "12 cumplen todos los requisitos, 8 requieren revision, 5 no cumplen" independientemente de los datos reales. `rankApplicants()` retorna scores fijos "95, 88, 82". `getProcessAnalytics()` retorna metricas inventadas: "23 dias", "78%", "850 EUR".

**Que se debe implementar:**

1. **Inyectar servicios reales:** `ApplicationService` y `JobPostingService` deben inyectarse via DI. Actualmente `RecruiterAssistantAgent` NO implementa AgentInterface — debe migrarse.

2. **Queries reales para screening:**
```php
public function screenCandidates(int $jobPostingId): array {
    $applications = $this->applicationService->getApplicationsForJob($jobPostingId);
    $screened = ['qualified' => 0, 'review' => 0, 'unqualified' => 0];
    foreach ($applications as $app) {
        $score = $this->calculateMatchScore($app, $jobPosting);
        if ($score >= 80) $screened['qualified']++;
        elseif ($score >= 50) $screened['review']++;
        else $screened['unqualified']++;
    }
    return $screened;
}
```

3. **Renombrar a RecruiterAdvisorService:** Esta clase NO es un agente (no implementa AgentInterface). Debe renombrarse a `RecruiterAdvisorService` para evitar confusion contractual.

**Directrices aplicables:**
- SERVICE-CALL-CONTRACT-001: Verificar signatures de ApplicationService y JobPostingService
- TENANT-BRIDGE-001: Filtrar applications por tenant del recruiter

**Archivos afectados:**
- `web/modules/custom/jaraba_job_board/src/Agent/RecruiterAssistantAgent.php` -> Renombrar a `src/Service/RecruiterAdvisorService.php`
- `web/modules/custom/jaraba_job_board/jaraba_job_board.services.yml` — Actualizar service ID

**Estimacion:** 6-8 horas

### 4.6 S1-06: Guardrails mandatory en paths criticos

**Hallazgo:** HAL-AI-17 — AIGuardrailsService inyectado como @? (opcional) en todo el stack.

**Estado actual:**
En `jaraba_rag.services.yml`, `jaraba_copilot_v2.services.yml` y otros, el AIGuardrailsService se inyecta como `@?ecosistema_jaraba_core.ai_guardrails` (opcional). Esto significa que si por error se desinstala `ecosistema_jaraba_core` o se renombra el servicio, los guardrails desaparecen silenciosamente.

**Que se debe implementar:**

1. **Hacer mandatory en paths criticos:** En los siguientes servicios, cambiar `@?` a `@`:
   - `StreamingOrchestratorService` (copilot publico)
   - `AgentToolRegistry` (ejecucion de herramientas)
   - `JarabaRagService` (RAG pipeline)

2. **Mantener @? en agentes individuales:** Los agentes que hereden de SmartBaseAgent pueden mantener `@?` porque el framework base ya pasa por el copilot/RAG que tiene guardrails mandatory.

**Archivos afectados:**
- `web/modules/custom/jaraba_copilot_v2/jaraba_copilot_v2.services.yml`
- `web/modules/custom/jaraba_ai_agents/jaraba_ai_agents.services.yml`
- `web/modules/custom/jaraba_rag/jaraba_rag.services.yml`

**Estimacion:** 2-3 horas

---

## 5. Sprint 2 — Revenue y Operaciones

**Duracion:** 2-4 semanas
**Prioridad:** ALTA — Bloquea modelo de negocio
**Objetivo:** Habilitar billing real y operaciones tenant-level

### 5.1 S2-01: Usage metering real

**Hallazgo:** HAL-AI-02 — `getCurrentUsage()` retorna datos aleatorios.

**Estado actual:**
`UsageLimitsService` en `ecosistema_jaraba_core` tiene constante `PLAN_LIMITS` con limites por tier (starter: 25 productos, 100 orders/mes, 500MB storage, 1000 API calls/dia; enterprise: ilimitado). Sin embargo, `getCurrentUsage()` NO consulta la base de datos — retorna valores simulados.

**Que se debe implementar:**

1. **TenantMeteringService nuevo:** Servicio dedicado que consolida metricas reales por tenant. Debe ser **asynchronous** para no impactar performance del request.

2. **Metricas a trackear:**

| Metrica | Fuente | Query |
|---------|--------|-------|
| Productos activos | Entity count | `SELECT COUNT(*) FROM {product_agro\|product_retail} WHERE tenant_id = :tid AND status = 1` |
| Orders este mes | Entity count | `SELECT COUNT(*) FROM {order_*} WHERE tenant_id = :tid AND created >= :month_start` |
| Storage usado | File system | `SELECT SUM(filesize) FROM {file_managed} fm JOIN {file_usage} fu ON fm.fid = fu.fid WHERE fu.module LIKE 'jaraba_%' AND ... tenant filter` |
| API calls hoy | Log count | `SELECT COUNT(*) FROM {ai_usage_log} WHERE tenant_id = :tid AND created >= :today` |
| Team members | User count | `SELECT COUNT(*) FROM {group_relationship} WHERE gid = :group_id AND type LIKE '%membership%'` |

3. **Async metering via queue:** Cada API call registra un item en una queue de Drupal. Un cron worker consume la queue cada 5 minutos y actualiza contadores agregados en una tabla `tenant_usage_snapshot`.

4. **Tabla de snapshot:**
```sql
CREATE TABLE tenant_usage_snapshot (
    tenant_id INT UNSIGNED NOT NULL,
    metric_key VARCHAR(64) NOT NULL,
    metric_value BIGINT NOT NULL DEFAULT 0,
    period VARCHAR(10) NOT NULL, -- 'daily', 'monthly'
    period_start DATE NOT NULL,
    updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (tenant_id, metric_key, period, period_start),
    INDEX idx_tenant_period (tenant_id, period)
);
```

5. **Integration con UsageLimitsService:** Reemplazar datos fake con queries a `tenant_usage_snapshot`.

**Directrices aplicables:**
- TENANT-BRIDGE-001: Obtener tenant_id via TenantBridgeService
- PRESAVE-RESILIENCE-001: Queue worker con try-catch
- ZERO-REGION-001: Dashboard de metricas como pagina frontend limpia

**Archivos nuevos:**
- `web/modules/custom/ecosistema_jaraba_core/src/Service/TenantMeteringService.php`
- `web/modules/custom/ecosistema_jaraba_core/src/Plugin/QueueWorker/MeteringAggregatorWorker.php`

**Archivos modificados:**
- `web/modules/custom/ecosistema_jaraba_core/src/Service/UsageLimitsService.php` — Usar TenantMeteringService
- `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.install` — Schema para tabla snapshot

**Estimacion:** 20-30 horas

### 5.2 S2-02: Feature flag service

**Hallazgo:** HAL-AI-11 — Features determinados por plan tier hardcoded.

**Que se debe implementar:**

1. **FeatureFlag ConfigEntity:** Almacena flags runtime que se pueden toggle sin deploy.

```php
/**
 * @ConfigEntityType(
 *   id = "feature_flag",
 *   label = @Translation("Feature Flag"),
 *   config_prefix = "flag",
 *   entity_keys = { "id" = "id", "label" = "label" },
 *   admin_permission = "administer feature flags",
 * )
 */
class FeatureFlag extends ConfigEntityBase {
    // id, label, enabled (bool), scope (global|vertical|tenant|plan),
    // conditions (JSON: plan tiers, tenant IDs, percentage rollout)
}
```

2. **FeatureFlagService:** Central broker que resuelve si un flag esta activo para el contexto actual.

```php
public function isEnabled(string $flagId, ?int $tenantId = NULL): bool {
    $flag = $this->loadFlag($flagId);
    if (!$flag || !$flag->get('enabled')) return FALSE;

    $scope = $flag->get('scope');
    if ($scope === 'global') return TRUE;
    if ($scope === 'plan') return $this->checkPlanCondition($flag, $tenantId);
    if ($scope === 'tenant') return $this->checkTenantCondition($flag, $tenantId);
    if ($scope === 'percentage') return $this->checkPercentageRollout($flag, $tenantId);
    return FALSE;
}
```

3. **Twig extension:** `{% if feature_flag('ai_proactive_insights') %}...{% endif %}` para condicionar UI.

4. **Admin UI:** Lista + formulario en `/admin/config/system/feature-flags` con PremiumEntityFormBase.

**Directrices aplicables:**
- PREMIUM-FORMS-PATTERN-001: Form extiende PremiumEntityFormBase
- CONFIG-SCHEMA-001: Schema YAML para ConfigEntity con dynamic keys via `type: sequence`
- Learning #5: AdminHtmlRouteProvider para ConfigEntities

**Estimacion:** 16-24 horas

### 5.3 S2-03: Provisioning automatizado

**Que se debe implementar:**

1. **Webhook endpoint para Stripe:** `POST /api/v1/webhooks/stripe` que procese eventos `checkout.session.completed` y `customer.subscription.created`.

2. **AutoProvisioningService:** Al recibir webhook, ejecuta la misma secuencia que `TenantOnboardingService` pero sin intervencion manual:
   - Crear admin user
   - Crear Tenant en ACTIVE (ya pago)
   - Crear Group
   - Crear Domain
   - Enviar welcome email
   - Asignar plan tier basado en price_id de Stripe

3. **Idempotencia:** Verificar que el tenant no exista ya antes de crear (por si Stripe envia el webhook dos veces).

**Estimacion:** 16-20 horas

### 5.4 S2-04: Per-tenant analytics dashboard

**Que se debe implementar:**

Pagina frontend limpia en `/tenant/{tenant}/analytics` (zero-region policy) que muestre:
- MRR por tenant
- Usage vs limits (barra de progreso)
- AI tokens consumidos este mes
- Top acciones por tipo
- Growth trend (7/30/90 dias)

**Directrices aplicables:**
- ZERO-REGION-001: Template `page--tenant-analytics.html.twig` sin regiones
- Parciales: `{% include '@ecosistema_jaraba_theme/partials/_metric-card.html.twig' %}`
- Mobile-first: Cards en stack vertical en movil, grid en desktop
- hook_preprocess_html(): Anadir `body.page-tenant-analytics`
- Textos traducibles: Todos los labels con `|t`
- Iconos: `jaraba_icon('analytics', 'chart', { variant: 'duotone' })`

**Estimacion:** 12-16 horas

---

## 6. Sprint 3 — IA de Clase Mundial

**Duracion:** 4-8 semanas
**Prioridad:** ALTA — Core de diferenciacion competitiva
**Objetivo:** Completar capacidades IA que faltan para benchmark 85+

### 6.1 S3-01: AgentLongTermMemory con Qdrant

**Hallazgo:** HAL-AI-04 — Solo 80 lineas de constructor, 0 metodos implementados.

**Estado actual:**
`AgentLongTermMemoryService` en `jaraba_agents/src/Service/` tiene un constructor con 5 inyecciones opcionales pero ninguno de los 3 metodos prometidos (`remember()`, `recall()`, `buildMemoryPrompt()`) esta implementado.

**Que se debe implementar:**

1. **remember(string $agentId, string $content, array $metadata): void**
   - Genera embedding del `$content` via AI provider
   - Almacena en Qdrant collection `agent_memory` con payload: `{agent_id, content, metadata, timestamp, tenant_id}`
   - Si la collection no existe, crearla con dimension 1536 (OpenAI embeddings) o 1024 (Claude embeddings)
   - Deduplicacion: Buscar similitud > 0.95 antes de insertar; si existe, actualizar timestamp

2. **recall(string $agentId, string $query, int $limit = 5): array**
   - Genera embedding de `$query`
   - Busca en Qdrant con filtro `agent_id` y `tenant_id`
   - Aplica temporal decay: memorias mas antiguas tienen score reducido (half-life 30 dias)
   - Retorna array de `{content, score, timestamp, metadata}`

3. **buildMemoryPrompt(string $agentId, string $currentContext): string**
   - Llama a `recall()` con el contexto actual
   - Formatea las memorias relevantes como seccion de system prompt:
   ```
   ## Memorias Relevantes
   - [2026-02-25] El usuario prefiere comunicacion formal
   - [2026-02-20] El tenant trabaja en sector agroalimentario
   ```
   - Respeta `ContextWindowManager::estimateTokens()` para no exceder limite

4. **Integracion en SmartBaseAgent.execute():**
   - Antes de llamar al LLM, invocar `buildMemoryPrompt()` y prepend al system prompt
   - Despues de recibir respuesta, invocar `remember()` con un resumen del intercambio

**Directrices aplicables:**
- OPTIONAL-SERVICE-DI-001: Qdrant client como @? con fallback graceful
- PRESAVE-RESILIENCE-001: try-catch alrededor de todas las operaciones Qdrant
- TENANT-BRIDGE-001: Filtrar memorias por tenant_id
- TRACE-CONTEXT-001: Registrar spans para operaciones de memoria

**Archivos afectados:**
- `web/modules/custom/jaraba_agents/src/Service/AgentLongTermMemoryService.php` — Implementar 3 metodos
- `web/modules/custom/jaraba_ai_agents/src/Agent/SmartBaseAgent.php` — Integrar memory en execute()

**Estimacion:** 20-30 horas

### 6.2 S3-02: ReActLoop con parsing completo

**Hallazgo:** HAL-AI-05 — `buildStepPrompt()` y `parseStepResponse()` son stubs.

**Que se debe implementar:**

1. **buildStepPrompt():** Construir prompt que instrye al LLM a responder en formato estructurado:
```
Responde SIEMPRE en este formato exacto:

THOUGHT: [Tu razonamiento sobre el paso actual]
ACTION: [El nombre de la herramienta a usar, o FINISH si ya tienes la respuesta]
ACTION_INPUT: [JSON con los parametros de la herramienta]

O si ya tienes la respuesta final:

THOUGHT: [Tu razonamiento final]
ACTION: FINISH
ACTION_INPUT: {"answer": "La respuesta final"}
```

2. **parseStepResponse():** Parser robusto con regex + JSON fallback:
```php
private function parseStepResponse(string $response): array {
    $thought = '';
    $action = '';
    $actionInput = [];

    if (preg_match('/THOUGHT:\s*(.+?)(?=ACTION:)/s', $response, $m)) {
        $thought = trim($m[1]);
    }
    if (preg_match('/ACTION:\s*(.+?)(?=ACTION_INPUT:)/s', $response, $m)) {
        $action = trim($m[1]);
    }
    if (preg_match('/ACTION_INPUT:\s*(.+)/s', $response, $m)) {
        $jsonStr = trim($m[1]);
        $actionInput = json_decode($jsonStr, TRUE) ?? [];
    }

    return [
        'thought' => $thought,
        'action' => $action,
        'action_input' => $actionInput,
        'is_finish' => strtoupper($action) === 'FINISH',
    ];
}
```

3. **Step validation:** Verificar que cada paso produce un avance real. Si el agente repite la misma accion 3 veces, forzar FINISH con mensaje de timeout.

4. **Integracion con ToolRegistry:** Cuando `action` no es FINISH, ejecutar `ToolRegistry::executeTool($action, $actionInput)` y anadir el resultado como `OBSERVATION:` en el contexto.

5. **Logging por paso:** Cada paso registrado via `AIObservabilityService::log()` con `operation_name: 'react_step_{n}'` y `span_id` via `TraceContextService`.

**Estimacion:** 16-24 horas

### 6.3 S3-03: IA proactiva

**Hallazgo:** HAL-AI-06 — Solo IA reactiva (chatbot), sin capacidades proactivas.

**Estado actual:**
La plataforma solo genera respuestas IA cuando el usuario lo solicita explicitamente (via copilot, herramientas de formulario, o generacion de contenido). No hay ningun sistema que genere insights, alertas o recomendaciones de forma autonoma.

**Que se debe implementar:**

1. **ProactiveInsightsService:** Servicio que se ejecuta via cron cada 6 horas y genera insights por tenant.

2. **Tipos de insight:**

| Tipo | Descripcion | Fuente de Datos | Vertical |
|------|-------------|----------------|----------|
| `usage_anomaly` | Uso anormal (subida o bajada repentina) | tenant_usage_snapshot | Todas |
| `churn_risk` | Tenant sin login en 14+ dias | user login timestamps | Todas |
| `content_gap` | Categorias sin articulos recientes | content_article timestamps | Content Hub |
| `seo_opportunity` | Paginas sin meta description | SeoService::analyzeArticleSeo() | Content Hub |
| `quota_warning` | Uso > 80% del plan | UsageLimitsService | Todas |
| `review_sentiment` | Tendencia negativa en reviews | ReviewService aggregate | Agro, Comercio |

3. **ProactiveInsight entity:** ContentEntity para almacenar insights generados.

```php
// Campos: id, tenant_id, insight_type, title, description, severity (info/warning/critical),
// action_url, is_read (boolean), is_dismissed (boolean), created, expires_at
```

4. **Notificacion al usuario:** Widget en el dashboard del tenant que muestra insights no leidos. Badge en header con contador.

5. **Frontend:** Pagina `/tenant/insights` con template zero-region, parciales para cada tipo de insight card.

**Directrices aplicables:**
- ENTITY-PREPROCESS-001: `template_preprocess_proactive_insight()` en `.module`
- PREMIUM-FORMS-PATTERN-001: Form para dismissed/acknowledge
- ZERO-REGION-001: Pagina frontend limpia
- Textos traducibles: Todos los mensajes de insight con `t()`
- Iconos duotone: `jaraba_icon('status', 'alert', { variant: 'duotone' })`
- Mobile-first: Cards en stack vertical en movil

**Estimacion:** 30-40 horas

### 6.4 S3-04: LearningPathAgent

**Hallazgo:** HAL-AI-07 — LearningTutorAgent no tiene AI provider inyectado.

**Que se debe implementar:**

1. **Reescribir como SmartBaseAgent Gen 2:** `LearningPathAgent extends SmartBaseAgent` con los 10 args del constructor (SMART-AGENT-CONSTRUCTOR-001).

2. **5 acciones reales:**
   - `ask_question`: Responde dudas del estudiante usando RAG sobre contenido del curso
   - `explain_concept`: Explica un concepto con nivel adaptado al progreso del estudiante
   - `suggest_path`: Sugiere proximos cursos basado en completados + skills declarados
   - `study_tips`: Genera tecnicas de estudio personalizadas
   - `progress_review`: Analiza progreso y genera resumen con recomendaciones

3. **Integracion con EnrollmentService:** Obtener cursos completados, progreso actual, scores de evaluaciones.

4. **Model routing:** `ask_question` y `study_tips` → fast tier (Haiku). `explain_concept` y `progress_review` → balanced (Sonnet). `suggest_path` → balanced.

**Directrices aplicables:**
- SMART-AGENT-CONSTRUCTOR-001: 6 core + 4 optional args
- AI-IDENTITY-001: Prepend AIIdentityRule::apply()
- MODEL-ROUTING-CONFIG-001: Usar ModelRouterService, no hardcoded
- VERTICAL-CANONICAL-001: vertical = 'formacion'

**Archivos afectados:**
- `web/modules/custom/jaraba_lms/src/Agent/LearningTutorAgent.php` -> Eliminar
- `web/modules/custom/jaraba_lms/src/Agent/LearningPathAgent.php` -> Crear
- `web/modules/custom/jaraba_lms/jaraba_lms.services.yml` -> Actualizar service

**Estimacion:** 12-16 horas

### 6.5 S3-05: ModelRouter activation en Gen 2

**Hallazgo:** HAL-AI-16 — 8 agentes inyectan ModelRouterService pero sin evidencia de llamada a route().

**Que se debe implementar:**

1. **Verificar cada doExecute():** Revisar los 7 agentes Gen 2 y confirmar que llaman `$this->modelRouter->route($taskType, $context)` para obtener el tier adecuado.

2. **Patron correcto:**
```php
protected function doExecute(string $action, array $context): array {
    $routing = $this->modelRouter->route($action, $context);
    $tier = $routing['tier']; // 'fast', 'balanced', 'premium'
    $config = $routing['config']; // model_id, temperature, etc.

    $response = $this->callAiApi($tier, $systemPrompt, $userPrompt, $config);
    // ...
}
```

3. **Si no llaman route():** Anadir la llamada y usar el tier retornado en lugar de hardcoded.

**Estimacion:** 4-6 horas

### 6.6 S3-06: A/B testing de prompts

**Hallazgo:** PromptExperimentService ~30% implementado.

**Que se debe implementar:**

1. **Completar PromptExperimentService:**
   - `createExperiment(string $name, array $variants, array $metrics): Experiment`
   - `assignVariant(string $experimentId, string $userId): string`
   - `recordOutcome(string $experimentId, string $variantId, array $metrics): void`
   - `getResults(string $experimentId): array` (con t-test para significancia)

2. **Integracion en SmartBaseAgent.execute():** Ya existe `execute()` con experiment selection antes de `doExecute()`. Completar la logica.

3. **Admin dashboard:** `/admin/config/ai/experiments` con resultados por variante, confidence intervals, y boton "promote winner".

**Estimacion:** 16-24 horas

---

## 7. Sprint 4 — Performance y UX

**Duracion:** 2-4 semanas
**Prioridad:** MEDIA — Calidad de experiencia
**Objetivo:** Core Web Vitals, CSS performance, SEO avanzado

### 7.1 S4-01: Core Web Vitals

**Hallazgo:** HAL-AI-10 y HAL-AI-13 — Sin fetchpriority, srcset, WebP, aspect-ratio.

**Que se debe implementar:**

1. **Image pipeline con responsive images:**
   - Crear helper function `jaraba_responsive_image($uri, $alt, $sizes)` que genera `<picture>` con WebP y fallback JPEG
   - Usar image styles de Drupal para generar variantes: 400w, 800w, 1200w, 1600w
   - Anadir `fetchpriority="high"` al hero image (LCP)
   - Anadir `loading="lazy"` a todas las imagenes below-the-fold
   - Anadir `width` y `height` atributos (o CSS `aspect-ratio`) para prevenir CLS

2. **Template pattern:**
```twig
{# Parcial: partials/_responsive-image.html.twig #}
<picture>
    <source srcset="{{ image_webp_400 }} 400w, {{ image_webp_800 }} 800w, {{ image_webp_1200 }} 1200w"
            sizes="{{ sizes|default('(max-width: 768px) 100vw, 50vw') }}"
            type="image/webp">
    <img src="{{ image_fallback }}"
         alt="{{ alt }}"
         width="{{ width }}"
         height="{{ height }}"
         loading="{{ loading|default('lazy') }}"
         {% if fetchpriority %}fetchpriority="{{ fetchpriority }}"{% endif %}
         class="{{ class|default('') }}">
</picture>
```

3. **Aspect-ratio containers para prevenir CLS:**
```scss
.article-hero {
    aspect-ratio: 16 / 9;
    overflow: hidden;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}
```

**Directrices aplicables:**
- Dart Sass moderno: `aspect-ratio` nativo CSS, sin hacks
- Mobile-first: Sizes attribute refleja breakpoints movil primero
- SCSS variables: `var(--ej-spacing-*)` para padding/margin

**Estimacion:** 20-30 horas

### 7.2 S4-02: CSS code splitting

**Hallazgo:** HAL-AI-12 — 751KB en un solo archivo CSS.

**Que se debe implementar:**

1. **Estructura de SCSS particionado:**
```
scss/
  main.scss          -> css/ecosistema-jaraba-theme.css (core: ~200KB)
  dashboard.scss     -> css/dashboard.css (~80KB)
  content-hub.scss   -> css/content-hub.css (~60KB)
  marketplace.scss   -> css/marketplace.css (~60KB)
  page-builder.scss  -> css/page-builder.css (~80KB)
  auth.scss          -> css/auth.css (~30KB)
  legal.scss         -> css/legal.css (~20KB)
```

2. **Build script actualizado (package.json):**
```json
"build:css": "sass scss/main.scss css/ecosistema-jaraba-theme.css --style=compressed && sass scss/dashboard.scss css/dashboard.css --style=compressed && ..."
```

3. **Drupal libraries por ruta:** Cada pagina carga solo el CSS que necesita via `attach_library()` en el template:
```twig
{# page--content-hub.html.twig #}
{{ attach_library('ecosistema_jaraba_theme/content-hub') }}
```

4. **Critical CSS inlining:** Inline los estilos above-the-fold via `<style>` en el `<head>` usando el script `generate-critical.js` existente.

**Directrices aplicables:**
- Dart Sass moderno: Cada archivo parcial usa `@use 'variables' as *`
- Variables inyectables: Todos los splits mantienen acceso a `var(--ej-*)`
- El main.scss se reduce a core (reset, variables, header, footer, typography, utilities)

**Estimacion:** 12-16 horas

### 7.3 S4-03: GEO/Local SEO

**Hallazgo:** HAL-AI-14 — Sin geo meta tags ni LocalBusiness schema.

**Que se debe implementar:**

1. **LocalBusiness schema para Comercio Conecta:**
```json
{
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "Tenant Name",
    "address": {
        "@type": "PostalAddress",
        "streetAddress": "...",
        "addressLocality": "...",
        "postalCode": "...",
        "addressCountry": "ES"
    },
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": "...",
        "longitude": "..."
    },
    "openingHoursSpecification": [...],
    "aggregateRating": { "@type": "AggregateRating", ... }
}
```

2. **Geo meta tags:**
```html
<meta name="geo.region" content="ES-AN">
<meta name="geo.placename" content="Granada">
<meta name="geo.position" content="37.1773;-3.5986">
<meta name="ICBM" content="37.1773, -3.5986">
```

3. **Campos opcionales en Tenant:** `latitude`, `longitude`, `opening_hours` para tenants de Comercio Conecta.

4. **SeoService extension:** Metodo `generateLocalBusinessSchema($tenant)` que genera JSON-LD si el tenant tiene datos GEO.

**Estimacion:** 8-12 horas

### 7.4 S4-04: Workflow automation engine

**Hallazgo:** HAL-AI-19 — No hay motor de workflows IA.

**Que se debe implementar:**

1. **Nuevo modulo `jaraba_workflows`** con:
   - `WorkflowRule` ConfigEntity: trigger + conditions + actions
   - `WorkflowExecutionService`: Evalua triggers, verifica conditions, ejecuta actions
   - Integracion con ECA (Events-Conditions-Actions) de Drupal

2. **Triggers predefinidos:**
   - `entity_created`: Cuando se crea una entidad
   - `entity_updated`: Cuando se actualiza
   - `cron_schedule`: Programado (diario, semanal)
   - `threshold_reached`: Cuando un metric supera umbral
   - `ai_insight`: Cuando ProactiveInsightsService genera insight critico

3. **Actions con IA:**
   - `send_email`: Genera contenido con SmartMarketingAgent
   - `create_task`: Crea tarea en el sistema
   - `notify_admin`: Notificacion push al admin del tenant
   - `generate_report`: Genera reporte con DataAnalystAgent

4. **Admin UI:** `/admin/config/ai/workflows` con PremiumEntityFormBase.

**Directrices aplicables:**
- Learning #2: ECA Plugins by Code (Events, Conditions, Actions)
- PREMIUM-FORMS-PATTERN-001: Form con secciones
- FIELD-UI-SETTINGS-TAB-001: Tab para Field UI
- ZERO-REGION-001: Dashboard frontend limpio

**Estimacion:** 40-60 horas

### 7.5 S4-05: Plan de testing progresivo

**Hallazgo:** HAL-AI-15 — 59/89 modulos sin tests.

**Que se debe implementar:**

1. **Prioridad de testing:**

| Prioridad | Modulos | Tipo de Test |
|-----------|---------|-------------|
| P0 | jaraba_ai_agents, ecosistema_jaraba_core | Unit + Kernel |
| P1 | jaraba_agents, jaraba_copilot_v2, jaraba_rag | Unit + Kernel |
| P2 | jaraba_content_hub, jaraba_page_builder | Unit |
| P3 | Verticales (agro, comercio, empleabilidad) | Unit |

2. **Tests criticos a crear:**
   - `AgentToolRegistryTest`: Verifica guardrails en output, approval gating
   - `TenantIsolationTest`: Verifica que handlers bloquean cross-tenant access
   - `UsageMeteringTest`: Verifica queries reales vs datos fake
   - `ReActLoopTest`: Verifica parsing THOUGHT/ACTION/OBSERVATION
   - `ProactiveInsightsTest`: Verifica generacion de insights por tipo

3. **CI integration:** Tests ejecutados en pipeline CI existente (CI-KERNEL-001: MariaDB 10.11).

**Estimacion:** Continuo (8-12h/sprint)

---

## 9. Sprint 5 — 100% Clase Mundial (NUEVO v2.0)

**Duracion:** 3-5 meses
**Prioridad:** ALTA — Cierra la brecha de 82 a 100/100
**Objetivo:** Completar las 18 capacidades obligatorias al 100%. Resolver todos los residuales de Sprints 1-4.

### 9.1 S5-01: Migracion Gen 1 a Gen 2

**Hallazgo:** HAL-AI-21 — EmployabilityCopilot (6 modos), LegalCopilot (8 modos), ContentWriter (5 acciones) son Gen 1 sin tool use ni model routing.

**Que se debe implementar:**

1. **EmployabilityCopilotAgent → SmartEmployabilityCopilotAgent:**
   - Extender `SmartBaseAgent` en lugar de `BaseAgent`
   - Convertir los 6 modos (cv_analysis, interview_prep, job_search, career_path, skill_gap, motivation) a tools via `ToolRegistry`
   - Habilitar model routing: `cv_analysis` = balanced, `motivation` = fast
   - Preservar la logica de dominio de cada modo
   - Anadir observabilidad via `AIObservabilityService`

2. **LegalCopilotAgent → SmartLegalCopilotAgent:**
   - 8 modos → 8 tools (contract_analysis, dispute_resolution, legal_research, compliance_check, document_draft, case_summary, deadline_tracker, legal_qa)
   - Integrar RAG legal existente como tool adicional
   - Model routing: `contract_analysis` = premium, `legal_qa` = fast

3. **ContentWriterAgent → SmartContentWriterAgent:**
   - 5 acciones → tools (generate_article, seo_optimize, headline_variants, meta_description, content_calendar)
   - Integrar Canvas editor API como tool

**Directrices aplicables:**
- AGENT-GEN2-PATTERN-001: Override `doExecute()`, no `execute()`
- SMART-AGENT-CONSTRUCTOR-001: 10 args con 3 opcionales
- TOOL-USE-LOOP-001: Iterativo hasta 5 ciclos
- PRESAVE-RESILIENCE-001: try-catch en todas las integraciones

**Archivos afectados:**
- `jaraba_candidate/src/Agent/EmployabilityCopilotAgent.php` → nuevo `SmartEmployabilityCopilotAgent.php`
- `jaraba_legal_intelligence/src/Agent/LegalCopilotAgent.php` → nuevo `SmartLegalCopilotAgent.php`
- `jaraba_content_hub/src/Agent/ContentWriterAgent.php` → nuevo `SmartContentWriterAgent.php`
- Nuevos tools en `jaraba_ai_agents/src/Tool/` para cada modo migrado
- services.yml de cada modulo

**Estimacion:** 40-60 horas

### 9.2 S5-02: Agent evaluation framework

**Hallazgo:** HAL-AI-22 — Sin metricas de calidad, sin benchmarks, sin regression testing de prompts.

**Que se debe implementar:**

1. **AgentEvalService:**
   - `runEval(string $agentType, array $goldenDataset): EvalResult`
   - `compareVersions(string $agentType, string $versionA, string $versionB): ComparisonResult`
   - Metricas: relevance (0-1), coherence (0-1), helpfulness (0-1), safety (pass/fail), latency_ms
   - Evaluacion via LLM judge (fast tier) con rubric estandarizado

2. **GoldenDataset entity:**
   - `EvalDataset` ConfigEntity con: agent_type, items (array de {input, expected_output, context})
   - Admin UI: `/admin/config/ai/evals` con PremiumEntityFormBase

3. **CI integration:**
   - Drush command: `drush jaraba:eval-agents --agent=smart_marketing --threshold=0.8`
   - Fallo de CI si score < threshold

4. **Dashboard de resultados:**
   - Historico de scores por agente y version
   - Trend charts (mejora/degradacion)

**Directrices aplicables:**
- PREMIUM-FORMS-PATTERN-001: AdminForm
- MODEL-ROUTING-CONFIG-001: Eval LLM usa fast tier para coste minimo
- TRACE-CONTEXT-001: Cada eval run genera trace

**Archivos nuevos:**
- `jaraba_ai_agents/src/Service/AgentEvalService.php`
- `jaraba_ai_agents/src/Entity/EvalDataset.php`
- `jaraba_ai_agents/src/Form/EvalDatasetForm.php`
- `jaraba_ai_agents/src/Commands/AgentEvalCommands.php`

**Estimacion:** 20-30 horas

### 9.3 S5-03: Prompt versioning y management

**Hallazgo:** HAL-AI-23 — Prompts inline en PHP sin versionado ni rollback.

**Que se debe implementar:**

1. **PromptTemplate ConfigEntity:**
   - Campos: `id`, `label`, `agent_type`, `prompt_type` (system/user), `content` (text_long), `version`, `variables` (array), `is_active`
   - Versionado semantico automatico al guardar
   - Rollback: boton "Revert to version X" en admin UI

2. **PromptTemplateService:**
   - `getPrompt(string $agentType, string $promptType, array $variables = []): string`
   - Variable interpolation: `{{variable_name}}` → valor
   - Fallback a prompt inline si template no existe (migracion gradual)
   - Cache per-template con invalidacion al guardar

3. **Migracion gradual:**
   - Fase 1: Crear templates para los 7 Gen 2 agents existentes
   - Fase 2: Migrar Gen 1 conforme se convierten a Gen 2 (S5-01)

**Directrices aplicables:**
- CONFIG-SCHEMA-001: Schema YAML
- PREMIUM-FORMS-PATTERN-001: Admin form
- PRESAVE-RESILIENCE-001: fallback a inline si service no disponible

**Archivos nuevos:**
- `jaraba_ai_agents/src/Entity/PromptTemplate.php`
- `jaraba_ai_agents/src/Form/PromptTemplateForm.php`
- `jaraba_ai_agents/src/Service/PromptTemplateService.php`

**Estimacion:** 16-24 horas

### 9.4 S5-04: SemanticCacheService real

**Hallazgo:** HAL-AI-25 — Declarado en services.yml pero no integrado en ningun call path.

**Que se debe implementar:**

1. **Completar SemanticCacheService:**
   - `get(string $query, float $threshold = 0.92): ?array` — genera embedding de query, busca en Qdrant collection `semantic_cache` con similarity >= threshold, retorna {response, metadata, hit_count}
   - `set(string $query, string $response, array $metadata): void` — genera embedding, almacena en Qdrant con TTL configurable (default 24h)
   - `invalidate(string $pattern): int` — invalida entradas por patron (regex en metadata)
   - Deduplicacion: si query tiene similarity > 0.98 con entrada existente, actualizar en lugar de insertar

2. **Integracion en CopilotOrchestratorService:**
   - Antes de llamar al LLM, verificar cache: `$cached = $this->semanticCache->get($userMessage)`
   - Si hit, retornar `{type: 'cached', response: $cached['response']}` (SSE event type `cached`)
   - Despues de LLM response, almacenar: `$this->semanticCache->set($userMessage, $response, ['tenant_id' => ..., 'mode' => ...])`

3. **Per-tenant isolation:** Filtro `tenant_id` en todas las queries Qdrant

**Directrices aplicables:**
- OPTIONAL-SERVICE-DI-001: Qdrant client como @? con fallback
- TENANT-BRIDGE-001: Filtrar por tenant_id
- TRACE-CONTEXT-001: Span para cache lookup

**Archivos afectados:**
- `jaraba_copilot_v2/src/Service/SemanticCacheService.php` (completar)
- `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php` (integracion)
- `jaraba_copilot_v2/src/Service/StreamingOrchestratorService.php` (integracion)

**Estimacion:** 16-20 horas

### 9.5 S5-05: ContextWindowManager progressive trimming

**Hallazgo:** HAL-AI-26 — Solo token math, sin progressive trimming.

**Que se debe implementar:**

1. **fitContext(array $sections, int $maxTokens): array**
   - Secciones con prioridad: `system_prompt` (P0, nunca recortar), `tools` (P1), `rag_context` (P2), `memory` (P3), `conversation_history` (P4)
   - Algoritmo: Si total > maxTokens, recortar P4 (conversation) primero, luego P3, luego P2
   - Para cada seccion recortada: truncar desde el inicio (mantener mensajes recientes)
   - Retornar array de secciones ajustadas + metadata de cuanto se recorto

2. **Integracion en SmartBaseAgent.execute():**
   - Antes de `callAiApi()`, invocar `contextWindowManager->fitContext()` con system prompt + rag + memory + history
   - Log de warning si se recorta > 30% del contexto original

**Archivos afectados:**
- `jaraba_ai_agents/src/Service/ContextWindowManager.php` (completar)
- `jaraba_ai_agents/src/Agent/SmartBaseAgent.php` (integracion)

**Estimacion:** 8-12 horas

### 9.6 S5-06: Brand voice per-tenant completo

**Hallazgo:** HAL-AI-30 — BrandVoiceService existe pero no persiste configuracion por tenant.

**Que se debe implementar:**

1. **BrandVoiceProfile entity (content entity, no config):**
   - Campos: `tenant_id` (entity_reference), `tone` (list_string: formal/casual/tecnico/emprendedor), `vocabulary_preferred` (string_long, JSON array), `vocabulary_forbidden` (string_long, JSON array), `personality_traits` (string_long, JSON array), `style_guide` (text_long), `sample_text` (text_long)
   - Un perfil por tenant (unique constraint)

2. **BrandVoiceService::buildPromptSection(int $tenantId): string**
   - Carga BrandVoiceProfile del tenant
   - Genera seccion de system prompt:
   ```
   ## Brand Voice
   - Tone: [formal]
   - Preferred terms: [ecosystem, impact, transformation]
   - Forbidden terms: [free, cheap, discount]
   - Style: [style_guide text]
   ```
   - Cache per-tenant con invalidacion al guardar perfil

3. **Integracion en SmartBaseAgent:**
   - En `execute()`, inyectar brand voice section en system prompt via `buildPromptSection()`
   - Feature flag: `brand_voice_enabled` para activacion gradual

**Directrices aplicables:**
- PREMIUM-FORMS-PATTERN-001: Form de configuracion
- TENANT-BRIDGE-001: Resolucion de tenant
- ENTITY-PREPROCESS-001: Si se muestra en view mode

**Archivos afectados:**
- Nuevo `BrandVoiceProfile` entity + form + handler
- `BrandVoiceService` (completar)
- `SmartBaseAgent.php` (integracion)

**Estimacion:** 16-20 horas

### 9.7 S5-07: Agent collaboration patterns

**Hallazgo:** HAL-AI-29 — Solo sequential y plan-first de 5 patrones del estado del arte.

**Que se debe implementar:**

1. **AgentOrchestratorService:**
   - `executeSequential(array $agentIds, string $input): array` — ya existe via AgentExecutionBridge
   - `executeConcurrent(array $agentIds, string $input): array` — ejecutar N agentes en paralelo, merge resultados
   - `executeHandoff(string $startAgentId, string $input): array` — patron de delegacion dinamica: agent A decide pasar a agent B via tool call `handoff_to(agentId, reason)`, el runner cambia `active_agent` y mantiene historial compartido
   - `executeGroupChat(array $agentIds, string $topic, int $maxRounds = 3): array` — agentes discuten en turnos hasta consenso o maxRounds

2. **Handoff pattern (prioridad maxima):**
   - Nuevo tool `HandoffTool` con `execute(string $targetAgentId, string $reason, array $context)`
   - `HandoffDecisionService` (existente) evalua si handoff es necesario
   - El orchestrator mantiene `$activeAgent` y `$sharedHistory` (array de messages)

**Directrices aplicables:**
- TOOL-USE-LOOP-001: HandoffTool como tool registrado
- TRACE-CONTEXT-001: Span por agente en la cadena
- PRESAVE-RESILIENCE-001: Fallback si target agent no disponible

**Archivos nuevos:**
- `jaraba_ai_agents/src/Service/AgentOrchestratorService.php`
- `jaraba_ai_agents/src/Tool/HandoffTool.php`

**Archivos afectados:**
- `jaraba_ai_agents/src/Service/HandoffDecisionService.php` (completar)
- `jaraba_ai_agents/src/Service/AgentToolRegistry.php` (registrar HandoffTool)

**Estimacion:** 24-32 horas

### 9.8 S5-08: Schema.org completo

**Hallazgo:** HAL-AI-28 — Faltan 7 tipos de Schema.org para clase mundial.

**Que se debe implementar:**

1. **Extender SchemaOrgService con builders:**
   - `buildFAQPageSchema(array $faqs): array` — para secciones FAQ de verticales
   - `buildVideoObjectSchema(array $video): array` — para testimonios con video
   - `buildEventSchema(array $event): array` — para formaciones y webinars
   - `buildProductSchema(array $product): array` — para marketplace
   - `buildCourseSchema(array $course): array` — para LMS
   - `buildHowToSchema(array $steps): array` — para guias paso a paso
   - `buildReviewSchema(array $review): array` — para reviews system

2. **Integracion en preprocess de cada vertical:**
   - LMS: `template_preprocess_course()` → CourseSchema
   - Marketplace: `template_preprocess_product_listing()` → ProductSchema
   - Reviews: `template_preprocess_*_review()` → ReviewSchema + AggregateRating

**Directrices aplicables:**
- ENTITY-PREPROCESS-001: Inyectar schema en variables
- SEO best practices: JSON-LD en `<script type="application/ld+json">`

**Archivos afectados:**
- `jaraba_page_builder/src/Service/SchemaOrgService.php` (extender)
- Preprocess hooks en 5+ modulos

**Estimacion:** 12-16 horas

### 9.9 S5-09: PersonalizationEngine con ML

**Hallazgo:** HAL-AI-20 residual — Solo rule-based, sin ML recommendations.

**Que se debe implementar:**

1. **PersonalizationService:**
   - `getRecommendations(int $userId, string $entityType, int $limit = 5): array`
   - Algoritmo hibrido:
     a. Collaborative filtering basico: "usuarios que vieron X tambien vieron Y" via tabla `user_interactions`
     b. Content-based: embedding de perfil de usuario vs embeddings de contenido (Qdrant)
     c. Popularity fallback: contenido mas popular si no hay datos suficientes
   - Per-tenant isolation: recomendaciones solo del contenido del tenant del usuario

2. **UserInteraction tracking:**
   - Entidad `UserInteraction`: user_id, entity_type, entity_id, action (view/click/complete), timestamp, tenant_id
   - Tracking via EventSubscriber en entity view
   - Batch processing via cron para calcular similarity matrices

3. **Widget de recomendaciones:**
   - Twig partial `_recommendations.html.twig` incluible en cualquier template
   - Endpoint API: `GET /api/v1/recommendations?type=content_article&limit=5`

**Directrices aplicables:**
- TENANT-BRIDGE-001: Recomendaciones aisladas por tenant
- OPTIONAL-SERVICE-DI-001: Qdrant como @?
- ZERO-REGION-001: Widget limpio

**Estimacion:** 30-40 horas

### 9.10 S5-10: Residuales de Sprints 1-4

**Items pendientes de sprints anteriores que deben cerrarse para 100%:**

| Item | Sprint orig. | Que falta | Estimacion |
|------|-------------|-----------|------------|
| HAL-AI-02 residual | S2 | Implementar deteccion real de IPs multiples, sesiones concurrentes, credenciales compartidas | 8-12h |
| HAL-AI-08 residual | S1 | Migrar RecruiterAssistant a Gen 2 + LLM real para optimizeJobDescription, suggestInterviewQuestions, draftCandidateResponse | 12-16h |
| HAL-AI-10+13 residual | S4 | fetchpriority en hero images globales, srcset en todos los templates, AVIF, CWV monitoring (web-vitals.js) | 16-20h |
| HAL-AI-12 | S4 | CSS code splitting: dividir main.scss en bundles por ruta, critical CSS inline, libraries-override por ruta | 20-30h |
| HAL-AI-15 residual | S4 | Kernel tests para servicios IA criticos, Functional tests E2E, coverage report en CI | 16-24h |
| HAL-AI-17 residual | S1 | Guardrails mandatory en jaraba_tenant_knowledge, revisar 5 constructores nullable | 2-4h |
| S2-03 | S2 | Provisioning automatizado: webhook Stripe → AutoProvisioningService | 16-20h |
| S2-04 | S2 | Per-tenant analytics dashboard en `/tenant/{tenant}/analytics` | 12-16h |
| HAL-AI-24 (multi-modal) | S5 | Vision: OCR documentos (JarabaLex), analisis imagenes producto (Comercio) | 20-30h |
| HAL-AI-27 (locking) | S5 | Optimistic locking con version field en Page Builder canvas | 8-12h |

**Estimacion total residuales:** 130-184 horas

---

## 10. Arquitectura Frontend: Directrices de Cumplimiento

Esta seccion consolida TODAS las directrices frontend que DEBEN cumplirse en cada implementacion del plan. Es una guia de referencia para el equipo tecnico.

### 8.1 Modelo SCSS con variables inyectables

**Regla fundamental:** Los modulos NUNCA definen variables SCSS (`$variable`). Solo el tema define variables en `_variables.scss`. Los modulos SOLO consumen CSS Custom Properties con fallback inline.

**Correcto:**
```scss
// En un modulo
.mi-componente {
    color: var(--ej-color-corporate, #233D63);
    padding: var(--ej-spacing-md, 1rem);
    border-radius: var(--ej-border-radius, 12px);
}
```

**Incorrecto:**
```scss
// NUNCA en un modulo
$mi-color: #233D63;
.mi-componente { color: $mi-color; }
```

**Inyeccion desde Drupal UI:**
Las variables CSS se inyectan via `hook_preprocess_html()` en el tema. Cuando un administrador cambia un color en `/admin/appearance/settings/ecosistema_jaraba_theme`, el valor se almacena en la config del tema y se inyecta como `:root { --ej-color-primary: #nuevo-valor; }` en el `<head>` de cada pagina.

**46 variables inyectables:** Ver seccion 4 del tema (colors, fonts, spacing, borders, shadows, z-index).

**Compilacion:**
```bash
# Dentro del contenedor Docker
lando ssh -c "cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss css/ecosistema-jaraba-theme.css --style=compressed"
```

### 8.2 Templates Twig limpias (Zero-Region Policy)

**Regla:** Paginas frontend NUNCA usan `{{ page.content }}`, `{{ page.sidebar_first }}`, ni ninguna region de Drupal. Reciben datos via `hook_preprocess_page()` como variables planas.

**Template de pagina limpia:**
```twig
{# page--mi-seccion.html.twig #}
{{ attach_library('ecosistema_jaraba_theme/mi-seccion') }}

<a href="#main-content" class="visually-hidden focusable skip-link">
    {{ 'Skip to main content'|t }}
</a>

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo_url: logo_url,
    navigation_items: theme_settings.navigation_items,
    logged_in: logged_in,
} only %}

<main id="main-content" class="dashboard-main">
    {% if clean_messages %}
        {{ clean_messages }}
    {% endif %}

    {{ clean_content }}
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    ts: theme_settings,
} only %}
```

**Variables disponibles via preprocess:**
- `clean_content`: Render array sin markup de region
- `clean_messages`: Status/error messages
- `site_name`, `logo_url`: Branding
- `theme_settings`: Array con TODA la configuracion del tema (70+ keys)
- `logged_in`, `current_user`: Contexto de usuario
- `vertical_key`: Vertical actual

### 8.3 Parciales Twig con include

**Regla:** Elementos reutilizables (header, footer, cards, metric-cards, breadcrumbs) se implementan como parciales en `templates/partials/` y se incluyen via `{% include %}`.

**Parciales existentes (20+):**
- `_header.html.twig` — Despacha a header variant (classic, centered, mega, minimal, sidebar, transparent)
- `_footer.html.twig` — 4 layouts (minimal, standard, mega, split) con social links y copyright
- `_breadcrumbs.html.twig` — 5 estilos (arrows, chevrons, slashes, dots, pills)
- `_avatar-nav.html.twig` — Navegacion contextual por rol
- `_review-card.html.twig` — Tarjeta de review
- `_seo-schema.html.twig` — JSON-LD schema
- `_hreflang-meta.html.twig` — Alternate language links
- `_gtm-analytics.html.twig` — Google Tag Manager
- `_lead-magnet.html.twig` — Lead capture form
- `_star-rating-display.html.twig` — Estrellas de calificacion

**Antes de crear un nuevo parcial,** verificar si ya existe uno que cubra la necesidad. Si el elemento se usara en 2+ paginas, crear parcial. Si es especifico de 1 pagina, inline en el template de pagina.

**Patron de inclusion con variables explicitas:**
```twig
{% include '@ecosistema_jaraba_theme/partials/_metric-card.html.twig' with {
    icon: jaraba_icon('analytics', 'chart', { variant: 'duotone' }),
    value: metric.value,
    label: metric.label|t,
    trend: metric.trend,
} only %}
```

### 8.4 Theme settings configurables desde UI

**Regla:** Todo contenido que pueda cambiar (textos de footer, links de navegacion, copyright, colores, fuentes) DEBE ser configurable desde la UI de Drupal en `/admin/appearance/settings/ecosistema_jaraba_theme`, sin tocar codigo.

**15 tabs de configuracion:**
1. Identidad de Marca (8 colors + logo)
2. Tipografia (font family + size + colors)
3. Encabezado (5 layouts + sticky + CTA + navigation links)
4. Hero (5 layouts + overlay)
5. Tarjetas (4 styles + radius)
6. Footer (4 layouts + 3 nav columns + 4 widgets + social links + copyright)
7. Sidebar (5 colors)
8. Componentes (buttons + forms)
9. Productos (4 card layouts + colors)
10. Avanzadas (back-to-top, dark mode, preloader, command bar, custom CSS)
11. Banner Promo (text + URL + colors)
12. Industry Presets (15 presets)
13. Breadcrumbs (5 styles + icons + schema)
14. Paginas Legales (privacy, terms, cookies, about, contact — HTML editable)
15. Meta-Sitio (hero content + stats)

**Acceso a settings en templates:**
```twig
{# theme_settings viene de preprocess_page() #}
{{ theme_settings.footer_copyright|replace({'[year]': 'now'|date('Y')}) }}
```

### 8.5 Layout full-width mobile-first

**Regla:** Todo CSS se escribe para movil primero. Las expansiones a pantallas mas grandes usan `@media (min-width: ...)`.

**Breakpoints (mixin `respond-to()`):**
- xs: max-width 479px (movil pequeno)
- sm: min-width 640px (tablet pequena)
- md: min-width 768px (tablet)
- lg: min-width 992px (desktop)
- xl: min-width 1200px (desktop ancho)
- 2xl: min-width 1440px (pantalla grande)

**Patron mobile-first:**
```scss
.dashboard-grid {
    // Movil: 1 columna
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--ej-spacing-md);
    padding: var(--ej-spacing-md);

    @include respond-to('md') {
        // Tablet: 2 columnas
        grid-template-columns: repeat(2, 1fr);
        gap: var(--ej-spacing-lg);
    }

    @include respond-to('xl') {
        // Desktop: 3 columnas
        grid-template-columns: repeat(3, 1fr);
        gap: var(--ej-spacing-xl);
    }
}
```

**Full-width:** Paginas frontend NO tienen `max-width` fijo en el container principal. El contenido fluye al 100% del viewport con padding lateral que crece en pantallas grandes.

### 8.6 Modales y slide-panels para CRUD

**Regla:** TODAS las acciones de crear/editar/ver en paginas frontend DEBEN abrir en slide-panel o modal. El usuario NUNCA abandona la pagina en la que esta trabajando.

**Patron en template:**
```twig
<a href="{{ add_url }}"
   class="btn btn--primary"
   data-slide-panel="large"
   data-slide-panel-title="{{ 'New Article'|t }}">
    {{ jaraba_icon('actions', 'plus', { variant: 'duotone' }) }}
    {{ 'Create'|t }}
</a>
```

**Patron en controller (PremiumFormAjaxTrait):**
```php
public function add(Request $request): array|Response {
    $entity = $this->entityTypeManager()->getStorage('my_entity')->create();
    $form = $this->entityFormBuilder()->getForm($entity, 'add');

    // Slide-panel detection
    if ($ajax = $this->renderFormForAjax($form, $request)) {
        return $ajax;
    }

    // Fallback: full page
    return ['#theme' => 'premium_form_wrapper', '#form' => $form];
}
```

**Reglas de slide-panel:**
- Tamanos: small (400px), medium (600px), large (800px), full (100%)
- Header con gradiente premium (naranja a azul corporativo)
- Smooth transition (transform 0.3s ease)
- z-index: 1000
- Mobile: max-width 100%
- Cierre via boton X, click en overlay, o Escape

### 8.7 hook_preprocess_html() para body classes

**Regla:** Las clases del `<body>` se DEBEN anadir via `hook_preprocess_html()`, NUNCA via `attributes.addClass()` en el template (no funciona para body).

**Patron:**
```php
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
    // Route-based classes
    $route_name = \Drupal::routeMatch()->getRouteName();

    if (str_starts_with($route_name, 'jaraba_content_hub.')) {
        $variables['attributes']['class'][] = 'page-content-hub';
    }

    // Feature classes
    if (theme_get_setting('command_bar_enabled')) {
        $variables['attributes']['class'][] = 'has-command-bar';
    }

    // Meta-site tenant classes
    if ($metaSite) {
        $variables['attributes']['class'][] = 'meta-site';
        $variables['attributes']['class'][] = 'meta-site-tenant-' . $metaSite['group_id'];
    }

    // Layout classes from settings
    $variables['attributes']['class'][] = 'header-layout-' . theme_get_setting('header_layout');
    $variables['attributes']['class'][] = 'footer-layout-' . theme_get_setting('footer_layout');
}
```

### 8.8 Iconos duotone con jaraba_icon()

**Regla:** TODOS los iconos en templates DEBEN usar `jaraba_icon('category', 'name', { options })`. NUNCA emojis como iconos visuales. NUNCA SVG directo en templates.

**Firma correcta:** `jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' })`

**Variante duotone:** Por defecto en premium UI. Background layers con `opacity: 0.2` + `fill: currentColor`.

**Colores permitidos:** `azul-corporativo`, `naranja-impulso`, `verde-innovacion`, `white`, `neutral`.

**Categories disponibles:** achievement, finance, general, legal, navigation, status, tools, media, users, ui, actions, analytics.

**En canvas_data (GrapesJS):** Usar inline SVGs con hex explicito (`#233D63`, `#FF8C42`, `#00A9A5`), NUNCA `currentColor` (canvas no hereda CSS del tema).

### 8.9 Textos traducibles (i18n)

**Regla:** TODAS las cadenas visibles al usuario DEBEN estar envueltas en funciones de traduccion.

| Contexto | Funcion | Ejemplo |
|----------|---------|---------|
| PHP | `$this->t('String')` o `t('String')` | `$this->t('Save article')` |
| PHP con variables | `$this->t('Hello @name', ['@name' => $name])` | Escapado automatico |
| Twig | `{{ 'String'\|t }}` | `{{ 'Dashboard'\|t }}` |
| Twig con variables | `{{ 'Welcome @name'\|t({'@name': user_name}) }}` | |
| JavaScript | `Drupal.t('String')` | `Drupal.t('Loading...')` |
| Render arrays | `(string) $this->t('Label')` | Cast obligatorio |

**Sitio usa prefijo `/es/`:** ROUTE-LANGPREFIX-001. Todas las URLs en JS via `Drupal.url()` o `Url::fromRoute()`.

### 8.10 Dart Sass moderno

**Regla:** Usar sintaxis moderna de Dart Sass.

| Correcto | Incorrecto | Razon |
|----------|-----------|-------|
| `@use 'variables' as *` | `@import 'variables'` | @import deprecated |
| `color.adjust($color, $lightness: -10%)` | `darken($color, 10%)` | darken() deprecated |
| `color.adjust($color, $lightness: 10%)` | `lighten($color, 10%)` | lighten() deprecated |
| `math.div($a, $b)` | `$a / $b` | Division con / deprecated |
| `@use 'sass:color'` | (ninguno) | Namespace modules |
| `@use 'sass:math'` | (ninguno) | Math operations |

**Compilacion:**
```bash
npx sass scss/main.scss css/ecosistema-jaraba-theme.css --style=compressed
```

### 8.11 Integracion de entidades con Field UI y Views

**Regla:** Toda ContentEntity DEBE tener:

1. **En `@ContentEntityType` annotation:**
   - `field_ui_base_route = "entity.{entity_type}.settings"` — Habilita Field UI
   - `handlers.list_builder` — Para la pagina de coleccion en admin
   - `handlers.views_data` = `EntityViewsData` — Para integracion con Views
   - `links.canonical`, `links.add-form`, `links.collection` — URLs

2. **En `.links.menu.yml`:**
   - Entrada bajo `parent: system.admin_content` — Aparece en `/admin/content`
   - Entrada bajo `parent: system.admin_structure` — Para Field UI en `/admin/structure`

3. **En `.links.task.yml`:**
   - Tab `entity.{entity_type}.settings` como `base_route` — FIELD-UI-SETTINGS-TAB-001

4. **En `.links.action.yml`:**
   - Boton "Add" que `appears_on: entity.{entity_type}.collection`

5. **En `.routing.yml`:**
   - Ruta admin: `/admin/content/{entities}` con `_entity_list`
   - Ruta frontend: `/{seccion}` con `_admin_route: FALSE`

### 8.12 Tenant sin acceso a tema admin

**Regla:** Los usuarios tenant NUNCA ven el tema de administracion de Drupal. Todas las rutas de tenant usan `_admin_route: FALSE` en el routing, lo que fuerza el uso del tema frontend.

**Patron en routing.yml:**
```yaml
mi_modulo.frontend.list:
    path: '/mi-seccion'
    defaults:
        _controller: '\Drupal\mi_modulo\Controller\FrontendController::list'
    requirements:
        _permission: 'access mi_modulo overview'
    options:
        _admin_route: FALSE
```

---

## 9. Especificaciones Tecnicas de Aplicacion

| Tecnologia | Especificacion | Version | Uso |
|-----------|---------------|---------|-----|
| PHP | 8.4 | Actual | Backend |
| Drupal | 11 | Actual | Framework |
| MariaDB | 10.11+ | Actual | Base de datos |
| Redis | 7.4 | Actual | Cache |
| Dart Sass | 1.83 | Actual | Compilacion SCSS |
| GrapesJS | 0.21+ | Actual | Canvas editor |
| Qdrant | 1.7+ | Requerido | Vector DB para memoria y RAG |
| DOMPurify | 3.x | Nuevo | Sanitizacion HTML en cliente |
| Critical | 7.1 | Actual | Critical CSS extraction |
| Terser | 5.37 | Actual | JS minification |
| Sharp | 0.33 | Actual | Image optimization |
| Claude API | Opus 4.6, Sonnet 4.6, Haiku 4.5 | Actual | LLM primary |
| OpenAI API | GPT-4o, GPT-4o-mini | Actual | LLM fallback |
| Stripe | API v2024-06 | Actual | Billing/payments |
| PHPUnit | 10.x | Actual | Testing |
| Docker/Lando | Latest | Actual | Development environment |

| Directriz | ID | Verificacion |
|-----------|-----|-------------|
| Premium Forms | PREMIUM-FORMS-PATTERN-001 | `grep -rl "extends ContentEntityForm"` = 0 |
| Tenant Isolation | TENANT-ISOLATION-ACCESS-001 | Audit script de handlers |
| Tenant Bridge | TENANT-BRIDGE-001 | Review DI services.yml |
| Entity Preprocess | ENTITY-PREPROCESS-001 | `grep "template_preprocess_"` por entidad |
| Presave Resilience | PRESAVE-RESILIENCE-001 | hasService() + try-catch |
| Slide-Panel Render | SLIDE-PANEL-RENDER-001 | renderPlain() en controllers |
| Route Language | ROUTE-LANGPREFIX-001 | Url::fromRoute() everywhere |
| Icon Convention | ICON-CONVENTION-001 | jaraba_icon() en templates |
| Safe HTML | SAFE-HTML-001 | \|safe_html, nunca \|raw |
| Secret Management | SECRET-MGMT-001 | grep secrets en config/sync = 0 |
| Dart Sass | — | @use, no @import |
| Zero-Region | ZERO-REGION-001 | Templates sin page.content |
| Texts i18n | — | Todos los strings con t() |
| Mobile-First | — | CSS movil default, min-width media queries |
| Body Classes | — | hook_preprocess_html(), no attributes.addClass() |
| Field UI | FIELD-UI-SETTINGS-TAB-001 | Tab default en links.task.yml |
| CSRF API | CSRF-API-001 | _csrf_request_header_token en routes |
| AI Identity | AI-IDENTITY-001 | AIIdentityRule::apply() en prompts |
| Model Routing | MODEL-ROUTING-CONFIG-001 | Config YAML, no hardcoded |

---

## 10. Estrategia de Testing

| Sprint | Tests a Crear | Tipo | Comando |
|--------|--------------|------|---------|
| S1 | AgentToolRegistryTest | Unit | `lando ssh -c "cd /app && vendor/bin/phpunit --filter=AgentToolRegistryTest"` |
| S1 | TenantIsolationAuditTest | Kernel | `lando ssh -c "cd /app && vendor/bin/phpunit --filter=TenantIsolationAuditTest"` |
| S2 | UsageMeteringServiceTest | Kernel | `lando ssh -c "cd /app && vendor/bin/phpunit --filter=UsageMeteringServiceTest"` |
| S2 | FeatureFlagServiceTest | Unit | `lando ssh -c "cd /app && vendor/bin/phpunit --filter=FeatureFlagServiceTest"` |
| S3 | AgentLongTermMemoryTest | Unit | `lando ssh -c "cd /app && vendor/bin/phpunit --filter=AgentLongTermMemoryTest"` |
| S3 | ReActLoopParsingTest | Unit | `lando ssh -c "cd /app && vendor/bin/phpunit --filter=ReActLoopParsingTest"` |
| S3 | ProactiveInsightsTest | Kernel | `lando ssh -c "cd /app && vendor/bin/phpunit --filter=ProactiveInsightsTest"` |
| S4 | CssCodeSplitTest | Manual | Verificar carga de CSS por ruta en browser |

**Todos los tests se ejecutan dentro del contenedor Docker via `lando ssh`.**

---

## 11. Verificacion y Despliegue

### Checklist de verificacion por sprint

**Sprint 1:**
- [ ] `lando ssh -c "cd /app && vendor/bin/phpunit --testsuite=Unit,Kernel"` pasa
- [ ] `grep -rn "call_user_func_array" web/modules/custom/jaraba_ai_agents/src/Service/AgentToolRegistry.php` muestra guardrails antes de call
- [ ] Navegar a https://jaraba-saas.lndo.site/ y verificar que el editor GrapesJS carga sin errores de consola
- [ ] Intentar XSS en canvas y verificar que CSP lo bloquea

**Sprint 2:**
- [ ] `/admin/reports/usage` muestra datos reales (no aleatorios)
- [ ] Feature flags toggleables desde `/admin/config/system/feature-flags`
- [ ] Webhook de prueba Stripe crea tenant automaticamente

**Sprint 3:**
- [ ] Agente con memoria recuerda contexto de sesion anterior
- [ ] ReAct loop ejecuta 3+ pasos con herramientas reales
- [ ] Insights proactivos aparecen en dashboard de tenant

**Sprint 4:**
- [ ] Lighthouse score > 90 en Performance
- [ ] CSS por ruta: verificar que `/content-hub` NO carga `marketplace.css`
- [ ] LocalBusiness schema visible en Google Rich Results Test

### Comando de deploy

```bash
# Dentro del contenedor Docker
lando ssh -c "cd /app && drush cr && drush updb -y && drush cim -y"
```

### URL de verificacion

- **SaaS principal:** https://jaraba-saas.lndo.site/
- **Admin:** https://jaraba-saas.lndo.site/admin
- **Content Hub:** https://jaraba-saas.lndo.site/content-hub
- **Canvas Editor:** https://jaraba-saas.lndo.site/pages/{id}/editor

---

## 12. Configuracion

### 12.1 Configuracion de Qdrant (Sprints 3 y 5)

```yaml
# config/deploy/settings.secrets.php
$config['jaraba_ai_agents.qdrant'] = [
  'host' => getenv('QDRANT_HOST') ?: 'localhost',
  'port' => (int) (getenv('QDRANT_PORT') ?: 6333),
  'api_key' => getenv('QDRANT_API_KEY') ?: '',
];
```

**Collections requeridas:**
| Collection | Dimension | Uso |
|------------|-----------|-----|
| `agent_memory` | 1536 | AgentLongTermMemoryService (S3-01) |
| `semantic_cache` | 1536 | SemanticCacheService (S5-04) |
| `content_embeddings` | 1536 | PersonalizationService (S5-09) |

### 12.2 Configuracion de Feature Flags

```yaml
# Via admin UI: /admin/config/system/feature-flags
# O via drush:
drush config:set feature_flag.brand_voice_enabled status 1
drush config:set feature_flag.proactive_insights scope 'percentage' percentage 25
```

### 12.3 Parametros Configurables del Sprint 5

| Parametro | Tipo | Default | Descripcion |
|-----------|------|---------|-------------|
| `semantic_cache.threshold` | float | 0.92 | Umbral de similitud para cache hit |
| `semantic_cache.ttl` | integer | 86400 | TTL en segundos (24h) |
| `context_window.max_tokens` | integer | 200000 | Limite de tokens por modelo |
| `agent_eval.min_score` | float | 0.8 | Score minimo para pass en CI |
| `brand_voice.enabled` | boolean | false | Activar brand voice per-tenant |
| `personalization.min_interactions` | integer | 10 | Interacciones minimas para collaborative filtering |
| `handoff.max_chain_length` | integer | 5 | Maximo de handoffs en cadena |

---

## 13. Estrategia de Testing

(Seccion existente actualizada — se mantiene contenido original + adiciones Sprint 5)

### 13.1 Tests Sprint 5

| Test | Tipo | Comando |
|------|------|---------|
| SmartEmployabilityCopilotTest | Unit | `phpunit --filter SmartEmployabilityCopilotTest` |
| SmartLegalCopilotTest | Unit | `phpunit --filter SmartLegalCopilotTest` |
| AgentEvalServiceTest | Kernel | `phpunit --filter AgentEvalServiceTest` |
| PromptTemplateServiceTest | Unit | `phpunit --filter PromptTemplateServiceTest` |
| SemanticCacheServiceTest | Kernel | `phpunit --filter SemanticCacheServiceTest` |
| BrandVoiceServiceTest | Unit | `phpunit --filter BrandVoiceServiceTest` |
| AgentOrchestratorServiceTest | Kernel | `phpunit --filter AgentOrchestratorServiceTest` |
| PersonalizationServiceTest | Kernel | `phpunit --filter PersonalizationServiceTest` |

---

## 15. Troubleshooting

### Problema 1: Qdrant connection refused

**Sintomas:**
- `ConnectionException: Failed to connect to Qdrant`
- AgentLongTermMemory, SemanticCache, Personalization fallan silenciosamente

**Causa:** Qdrant server no accesible o API key incorrecta.

**Solucion:**
```bash
# Verificar que Qdrant esta corriendo
curl -s http://localhost:6333/healthz
# Si usa Lando, verificar el servicio
lando info | grep qdrant
# Verificar config
drush config:get jaraba_ai_agents.qdrant
```

### Problema 2: CSS monolitico no se divide tras code splitting

**Sintomas:**
- `ecosistema-jaraba-theme.css` sigue siendo 700KB+ tras implementar S4-02

**Causa:** `libraries-override` no configurado correctamente o `global-styling` sigue cargando el bundle completo.

**Solucion:**
```bash
# Verificar que las libraries override estan activas
drush theme:info ecosistema_jaraba_theme | grep libraries
# Rebuild cache
drush cr
# Verificar CSS cargado en ruta especifica
curl -s https://jaraba-saas.lndo.site/content-hub | grep -o 'ecosistema.*\.css' | sort -u
```

### Problema 3: Agent eval score por debajo del threshold en CI

**Sintomas:**
- CI falla con `Agent eval score 0.65 < threshold 0.80`

**Causa:** Cambio en prompt o modelo que degrada calidad.

**Solucion:**
```bash
# Ejecutar eval localmente con verbose
drush jaraba:eval-agents --agent=smart_marketing --verbose
# Revisar golden dataset
drush config:get eval_dataset.smart_marketing
# Si el cambio es intencional, actualizar golden dataset
drush jaraba:eval-update-golden --agent=smart_marketing
```

### Problema 4: Brand voice no se aplica en respuestas del copilot

**Sintomas:**
- Respuestas del copilot ignoran el tone y vocabulary del tenant

**Causa:** Feature flag desactivado o BrandVoiceProfile no creado para el tenant.

**Solucion:**
```bash
# Verificar feature flag
drush eval "echo \Drupal::service('ecosistema_jaraba_core.feature_flag')->isEnabled('brand_voice_enabled') ? 'ON' : 'OFF';"
# Verificar perfil del tenant
drush eval "echo \Drupal::entityTypeManager()->getStorage('brand_voice_profile')->loadByProperties(['tenant_id' => TENANT_ID]) ? 'EXISTS' : 'MISSING';"
```

---

## 16. Referencias Cruzadas

| Documento | Version | Relacion |
|-----------|---------|----------|
| `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md` | v2.0.0 | Auditoria fuente de este plan (30 hallazgos) |
| `docs/00_DIRECTRICES_PROYECTO.md` | v91.0.0 | Directrices de cumplimiento |
| `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | v84.0.0 | Arquitectura de referencia |
| `docs/00_FLUJO_TRABAJO_CLAUDE.md` | v45.0.0 | Flujo de trabajo y aprendizajes |
| `docs/00_INDICE_GENERAL.md` | v119.0.0 | Indice de documentacion |
| `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | v2.1 | Federated Design Tokens |
| `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md` | v2.0.0 | Arquitectura IA nivel 5 |
| `docs/arquitectura/2026-02-05_especificacion_grapesjs_saas.md` | v1.0 | Especificacion GrapesJS |
| `docs/implementacion/2026-02-26_Plan_Implementacion_GrapesJS_Content_Hub_v1.md` | v1.0.0 | Plan GrapesJS Content Hub |
| `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Nivel5_Clase_Mundial_v1.md` | v1.0.0 | Plan IA Nivel 5 previo |
| `docs/implementacion/2026-02-26_Plan_Implementacion_Reviews_Comentarios_Clase_Mundial_v1.md` | v2.0.0 | Plan Reviews y Comentarios |
| `docs/analisis/2026-02-27_Auditoria_Demo_Vertical_Clase_Mundial_v2.md` | v2.0.0 | Auditoria Demo Vertical |

---

## 17. Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-02-27 | Claude Opus 4.6 | Creacion inicial: plan de implementacion con 20 acciones en 4 sprints, tabla de correspondencia hallazgos-acciones, tabla de cumplimiento de 35 directrices, especificaciones tecnicas completas, 12 secciones de arquitectura frontend |
| 2.0.0 | 2026-02-27 | Claude Opus 4.6 | Re-evaluacion completa. Target elevado de 85→100/100. Sprints 1-3 marcados como COMPLETADOS/MAYORMENTE COMPLETADOS. Sprint 5 anadido con 10 items nuevos (S5-01..S5-10) + tabla de residuales. Secciones obligatorias anadidas: Requisitos Previos (sec. 2), Configuracion (sec. 12), Troubleshooting (sec. 15). Metadata actualizada a formato plantilla (Fecha creacion/actualizacion, Autor, Version, Categoria). Versiones de referencia actualizadas (DIRECTRICES v91, FLUJO v45, ARQUITECTURA v84, INDICE v119). 30 hallazgos totales. Estimacion: 480-680h en 5 sprints. |

> **Nota**: Recuerda actualizar el indice general (`00_INDICE_GENERAL.md`) despues de modificar este documento.
