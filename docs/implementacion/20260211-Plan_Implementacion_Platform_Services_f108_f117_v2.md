# Plan de Implementación Platform Services (Docs 108-117) v2.0

> **Tipo:** Plan de Implementación con Auditoría de Código Existente
> **Versión:** 2.0.0
> **Fecha:** 2026-02-11
> **Última actualización:** 2026-02-11
> **Autor:** Claude Opus 4.6 / Equipo Técnico
> **Estado:** ✅ Auditoría completada — Planificación de gaps
> **Alcance:** 10 especificaciones técnicas transversales de plataforma (docs 108-117)
> **Módulos principales:** `jaraba_agent_flows`, `jaraba_pwa`, `jaraba_onboarding`, `jaraba_usage_billing`, `jaraba_integrations`, `jaraba_customer_success`, `jaraba_knowledge_base`, `jaraba_security_compliance`, `jaraba_analytics_bi`, `jaraba_whitelabel`
> **Diferencia con v1.0:** Esta versión incluye auditoría exhaustiva del código existente en el SaaS, identificando qué ya está implementado, qué necesita extensión y qué se debe crear desde cero. La v1.0 marcaba todo como "⬜ Planificada" sin verificar el estado real.

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Visión y Posicionamiento](#11-visión-y-posicionamiento)
  - [1.2 Auditoría de Implementación Existente — Hallazgos Clave](#12-auditoría-de-implementación-existente--hallazgos-clave)
  - [1.3 Esfuerzo Estimado Revisado (Post-Auditoría)](#13-esfuerzo-estimado-revisado-post-auditoría)
  - [1.4 Relación con la Infraestructura Existente](#14-relación-con-la-infraestructura-existente)
  - [1.5 Patrón Arquitectónico de Referencia](#15-patrón-arquitectónico-de-referencia)
- [2. Auditoría Detallada de Implementación Existente](#2-auditoría-detallada-de-implementación-existente)
  - [2.1 f108: AI Agent Flows — 90% Implementado](#21-f108-ai-agent-flows--90-implementado)
  - [2.2 f109: PWA Mobile — 70% Implementado](#22-f109-pwa-mobile--70-implementado)
  - [2.3 f110: Onboarding Product-Led — 95% Implementado](#23-f110-onboarding-product-led--95-implementado)
  - [2.4 f111: Usage-Based Pricing — 85% Implementado](#24-f111-usage-based-pricing--85-implementado)
  - [2.5 f112: Integration Marketplace — 40% Implementado](#25-f112-integration-marketplace--40-implementado)
  - [2.6 f113: Customer Success — 35% Implementado](#26-f113-customer-success--35-implementado)
  - [2.7 f114: Knowledge Base — 85% Implementado](#27-f114-knowledge-base--85-implementado)
  - [2.8 f115: Security & Compliance — 75% Implementado](#28-f115-security--compliance--75-implementado)
  - [2.9 f116: Advanced Analytics — 80% Implementado](#29-f116-advanced-analytics--80-implementado)
  - [2.10 f117: White-Label — 70% Implementado](#210-f117-white-label--70-implementado)
- [3. Tabla de Correspondencia con Especificaciones Técnicas](#3-tabla-de-correspondencia-con-especificaciones-técnicas)
- [4. Cumplimiento de Directrices del Proyecto](#4-cumplimiento-de-directrices-del-proyecto)
  - [4.1 Directriz: i18n — Textos siempre traducibles](#41-directriz-i18n--textos-siempre-traducibles)
  - [4.2 Directriz: Modelo SCSS con Federated Design Tokens](#42-directriz-modelo-scss-con-federated-design-tokens)
  - [4.3 Directriz: Dart Sass moderno](#43-directriz-dart-sass-moderno)
  - [4.4 Directriz: Frontend limpio sin regiones Drupal](#44-directriz-frontend-limpio-sin-regiones-drupal)
  - [4.5 Directriz: Body classes via hook_preprocess_html()](#45-directriz-body-classes-via-hook_preprocess_html)
  - [4.6 Directriz: CRUD en modales slide-panel](#46-directriz-crud-en-modales-slide-panel)
  - [4.7 Directriz: Entidades con Field UI y Views](#47-directriz-entidades-con-field-ui-y-views)
  - [4.8 Directriz: No hardcodear configuración](#48-directriz-no-hardcodear-configuración)
  - [4.9 Directriz: Parciales Twig reutilizables](#49-directriz-parciales-twig-reutilizables)
  - [4.10 Directriz: Seguridad](#410-directriz-seguridad)
  - [4.11 Directriz: Comentarios de código en español (3 dimensiones)](#411-directriz-comentarios-de-código-en-español-3-dimensiones)
  - [4.12 Directriz: Iconos SVG duotone](#412-directriz-iconos-svg-duotone)
  - [4.13 Directriz: AI via abstracción @ai.provider](#413-directriz-ai-via-abstracción-aiprovider)
  - [4.14 Directriz: Automaciones via hooks Drupal](#414-directriz-automaciones-via-hooks-drupal)
- [5. Arquitectura General de Módulos](#5-arquitectura-general-de-módulos)
- [6. GAPS — FASE 1: AI Agent Flows (Doc 108)](#6-gaps--fase-1-ai-agent-flows-doc-108)
- [7. GAPS — FASE 2: PWA Mobile (Doc 109)](#7-gaps--fase-2-pwa-mobile-doc-109)
- [8. GAPS — FASE 3: Onboarding Product-Led (Doc 110)](#8-gaps--fase-3-onboarding-product-led-doc-110)
- [9. GAPS — FASE 4: Usage-Based Pricing (Doc 111)](#9-gaps--fase-4-usage-based-pricing-doc-111)
- [10. GAPS — FASE 5: Integration Marketplace (Doc 112)](#10-gaps--fase-5-integration-marketplace-doc-112)
- [11. GAPS — FASE 6: Customer Success Proactivo (Doc 113)](#11-gaps--fase-6-customer-success-proactivo-doc-113)
- [12. GAPS — FASE 7: Knowledge Base & Self-Service (Doc 114)](#12-gaps--fase-7-knowledge-base--self-service-doc-114)
- [13. GAPS — FASE 8: Security & Compliance (Doc 115)](#13-gaps--fase-8-security--compliance-doc-115)
- [14. GAPS — FASE 9: Advanced Analytics & BI (Doc 116)](#14-gaps--fase-9-advanced-analytics--bi-doc-116)
- [15. GAPS — FASE 10: White-Label & Reseller (Doc 117)](#15-gaps--fase-10-white-label--reseller-doc-117)
- [16. Inventario Consolidado de Entidades](#16-inventario-consolidado-de-entidades)
- [17. Inventario Consolidado de Services](#17-inventario-consolidado-de-services)
- [18. Inventario Consolidado de Endpoints REST API](#18-inventario-consolidado-de-endpoints-rest-api)
- [19. Paleta de Colores y Design Tokens](#19-paleta-de-colores-y-design-tokens)
- [20. Patrón de Iconos SVG](#20-patrón-de-iconos-svg)
- [21. Orden de Implementación Global y Dependencias](#21-orden-de-implementación-global-y-dependencias)
- [22. Estimación Total de Esfuerzo Revisada](#22-estimación-total-de-esfuerzo-revisada)
- [23. Checklist de Verificación Pre-Implementación](#23-checklist-de-verificación-pre-implementación)
- [24. Registro de Cambios](#24-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Las especificaciones técnicas 108-117 (serie `20260117i`) definen la **capa de servicios transversales de plataforma** del Ecosistema Jaraba. A diferencia de los verticales (AgroConecta, ComercioConecta, ServiciosConecta, Empleabilidad, Emprendimiento) que resuelven necesidades sectoriales, estos 10 módulos proporcionan capacidades horizontales que benefician a **todos los verticales y todos los tenants** simultáneamente.

La principal diferencia de este plan v2.0 respecto al v1.0 es la inclusión de una **auditoría exhaustiva del código existente en el repositorio**, realizada el 2026-02-11. Esta auditoría demuestra que una parte significativa de las funcionalidades especificadas ya está implementada parcial o totalmente en módulos existentes del ecosistema, lo que reduce drásticamente el esfuerzo restante y cambia la estrategia de "implementar desde cero" a "extender y completar gaps".

### 1.1 Visión y Posicionamiento

La capa Platform Services transforma el Ecosistema Jaraba de una plataforma vertical exitosa a una **plataforma-como-infraestructura** (PaaI) capaz de escalar exponencialmente. Cada módulo desbloquea un multiplicador de valor:

| Módulo | Multiplicador de Valor | Impacto en Negocio |
|--------|----------------------|-------------------|
| AI Agent Flows (108) | Automatización sin código | Reduce operaciones manuales 60-80% |
| PWA Mobile (109) | Acceso ubicuo sin app stores | +30% engagement, acceso rural offline |
| Onboarding Product-Led (110) | Activación self-service | <5 min a valor, -50% tickets soporte |
| Usage-Based Pricing (111) | Monetización flexible | +25% ARPU vía overage billing |
| Integration Marketplace (112) | Ecosistema abierto | 50+ conectores Y1, efecto red |
| Customer Success (113) | Retención proactiva | NRR 115-120%, <5% churn anual |
| Knowledge Base (114) | Autoservicio inteligente | -40% tickets, FAQ bot con RAG |
| Security Compliance (115) | Enterprise/B2G ready | SOC 2 + ISO 27001 + ENS |
| Advanced Analytics (116) | BI self-service | +40% adopción informes, decisiones data-driven |
| White-Label (117) | Escala via partners | 10+ franquicias Y1, revenue share 20-40% |

### 1.2 Auditoría de Implementación Existente — Hallazgos Clave

> [!IMPORTANT]
> La auditoría del 2026-02-11 sobre el código del repositorio revela que **el 72% de la funcionalidad total especificada en los documentos 108-117 ya está implementada** en módulos existentes del ecosistema. Esto cambia fundamentalmente la estrategia de ejecución.

| Fase | Especificación | % Implementado | Estado Real | Estrategia |
|------|---------------|----------------|-------------|------------|
| 1 | AI Agent Flows (108) | **90%** | ✅ Extenso | Completar visual builder + benchmarks |
| 2 | PWA Mobile (109) | **70%** | ✅ Implementado | Mobile UI + offline sync + iOS |
| 3 | Onboarding Product-Led (110) | **95%** | ✅ Extenso | A/B testing + video walkthroughs |
| 4 | Usage-Based Pricing (111) | **85%** | ✅ Implementado | Dashboard tenant + custom tiers |
| 5 | Integration Marketplace (112) | **40%** | ⚠️ Parcial | **APP marketplace + OAuth + conectores** |
| 6 | Customer Success (113) | **35%** | ⚠️ Parcial | **Health scores + churn + NPS** |
| 7 | Knowledge Base (114) | **85%** | ✅ Extenso | Help center público + versioning |
| 8 | Security Compliance (115) | **75%** | ✅ Implementado | SOC2 dashboard + pen testing |
| 9 | Advanced Analytics (116) | **80%** | ✅ Extenso | Cohorts + funnels + report builder |
| 10 | White-Label (117) | **70%** | ✅ Implementado | Domain automation + email templates |

**Fortalezas detectadas:**
1. **AI Agents (f108)**: 9 agentes en producción, orquestación, Tool Registry, multi-provider failover, quality evaluation — `jaraba_ai_agents/`
2. **Knowledge Base (f114)**: Búsqueda semántica con Qdrant, auto-indexing, FAQ generation — `jaraba_tenant_knowledge/`
3. **Onboarding (f110)**: PLG excepcional con TTFV tracking, guided tours, journey engine, reverse trial — `ecosistema_jaraba_core/`, `jaraba_journey/`
4. **Analytics (f116)**: 100% nativo con heatmaps, multi-tenant isolation, pixel manager — `jaraba_analytics/`, `jaraba_heatmap/`, `jaraba_pixels/`

**Gaps críticos (requieren implementación nueva):**
1. **f112 (Integration Marketplace)**: Solo existe marketplace de productos. Falta **app/connector marketplace** estilo Zapier con OAuth y registro de conectores
2. **f113 (Customer Success)**: Existe monitorización de salud de plataforma, pero falta **health scoring por tenant**, predicción de churn y NPS
3. **f117 (White-Label)**: Existe personalización de tema por tenant, pero falta **automatización de dominios custom** (CNAME + SSL) y templates de email branded

### 1.3 Esfuerzo Estimado Revisado (Post-Auditoría)

| Fase | Módulo | Horas v1 (desde cero) | Horas v2 (gaps) | Ahorro | Prioridad |
|------|--------|----------------------|-----------------|--------|-----------|
| 1 | AI Agent Flows (108) | 370-480 | **40-60** | 87% | 🟡 P2 |
| 2 | PWA Mobile (109) | 210-270 | **65-90** | 69% | 🟡 P2 |
| 3 | Onboarding Product-Led (110) | 150-195 | **15-25** | 88% | 🟢 P3 |
| 4 | Usage-Based Pricing (111) | 155-205 | **30-45** | 79% | 🟡 P2 |
| 5 | Integration Marketplace (112) | 360-490 | **215-295** | 40% | 🔴 P0 |
| 6 | Customer Success (113) | 290-410 | **190-270** | 34% | 🔴 P0 |
| 7 | Knowledge Base (114) | 250-340 | **40-55** | 84% | 🟡 P2 |
| 8 | Security Compliance (115) | 100-150 + auditorías | **50-80** + auditorías | 47% | 🟡 P2 |
| 9 | Advanced Analytics (116) | 310-410 | **65-90** | 79% | 🟡 P2 |
| 10 | White-Label (117) | 290-390 | **90-130** | 68% | 🟡 P2 |
| **TOTAL** | | **2,485-3,340** | **~800-1,140** | **~66%** | |

> El ahorro del 66% se debe a la reutilización extensiva de infraestructura ya construida. Las horas indicadas son solo para **completar los gaps identificados**.

### 1.4 Relación con la Infraestructura Existente

Todos los módulos de Platform Services se construyen sobre la infraestructura consolidada del ecosistema. La auditoría ha verificado la existencia real de estos módulos en el repositorio:

| Módulo Core | Estado Verificado | Elementos Clave Disponibles |
|------------|-------------------|----------------------------|
| `ecosistema_jaraba_core` | ✅ Operativo | Tenant, Vertical, SaasPlan, Feature entities; TenantManager, PlanValidator, FinOpsTrackingService, JarabaStripeConnect, TenantMeteringService, UsageLimitsService |
| `ecosistema_jaraba_theme` | ✅ Operativo | Federated Design Tokens v2.1; 16 parciales Twig (`_header`, `_footer`, `_copilot-fab`, `_slide-panel`, `_hero`, `_stats`, `_features`, etc.); 70+ opciones configurables vía UI; 23 templates `page--*.html.twig` |
| `jaraba_ai_agents` | ✅ Operativo | 9 agentes (Base, Smart Marketing, Support, Customer Experience, Storytelling, Producer Copilot); AgentOrchestrator, WorkflowExecutorService, ModelRouterService, QualityEvaluatorService, Tool Registry |
| `jaraba_rag` | ✅ Operativo | Qdrant vector search, embeddings OpenAI, GroundingValidator, KbIndexerService, QueryAnalyticsService |
| `jaraba_journey` | ✅ Operativo | Journey Engine con 7 journey definitions por vertical (Empleabilidad, Emprendimiento, AgroConecta, etc.); TimeToFirstValueService, GuidedTourService, InAppMessagingService |
| `jaraba_foc` | ✅ Operativo | Financial Operations Center; FinOpsTrackingService |
| `jaraba_copilot_v2` | ✅ Operativo | 5 modos de copiloto, ModeDetectorService, FaqGeneratorService |
| `jaraba_email` | ✅ Operativo | Email marketing con generación IA |
| `jaraba_analytics` | ✅ Operativo | AnalyticsService, AnalyticsAggregatorService, AnalyticsEvent entity, consent management |
| `jaraba_heatmap` | ✅ Operativo | HeatmapAggregatorService, HeatmapSettingsForm |
| `jaraba_pixels` | ✅ Operativo | PixelDispatcherService, EventMapperService (Facebook, Google) |
| `jaraba_tenant_knowledge` | ✅ Operativo | TenantFaq, TenantPolicy, TenantDocument entities; KnowledgeIndexerService, auto-indexing a Qdrant |
| `jaraba_theming` | ✅ Operativo | TenantThemeConfig entity, ThemeTokenService, IndustryPresetService, VisualCustomizerController |

### 1.5 Patrón Arquitectónico de Referencia

Cada módulo nuevo o extensión de Platform Services sigue el patrón verificado en los verticales existentes. Consultar la v1.0 de este plan (sección 1.3) para la estructura de directorios completa. Las **6 reglas inmutables** son:

1. **Content Entities** (no Config Entities) para datos de usuario/operación — garantiza Field UI, Views, Entity Reference y revisiones
2. **Navegación dual**: `/admin/structure/{modulo}` para tipos/configuración, `/admin/content/{modulo}` para contenido de usuario
3. **Frontend limpio**: Templates Twig sin `page.content` ni bloques heredados de Drupal, layout full-width, mobile-first
4. **CRUD en modal**: Todas las acciones crear/editar/ver abren en slide-panel off-canvas, el usuario nunca abandona la página
5. **Variables inyectables**: Todo color, tipografía y espaciado usa `var(--ej-*, $fallback)` — configurable desde UI de Drupal sin tocar código
6. **Hook-first**: Automatizaciones en `.module` con hooks de Drupal (no ECA BPMN UI) para versionado en Git y testabilidad

---

## 2. Auditoría Detallada de Implementación Existente

> [!IMPORTANT]
> Esta sección documenta el resultado de la auditoría exhaustiva del código del repositorio realizada el 2026-02-11. Para cada especificación se identifican los archivos, entidades, servicios y controladores ya existentes, y se listan los gaps específicos que requieren implementación.

### 2.1 f108: AI Agent Flows — 90% Implementado

**Módulo existente:** `jaraba_ai_agents/` (operativo en producción)

**Lo que YA EXISTE:**

| Componente | Archivo/Clase | Líneas | Estado |
|-----------|--------------|--------|--------|
| Entidad AIAgent | `ecosistema_jaraba_core/src/Entity/AIAgent.php` | ConfigEntity | ✅ Operativo |
| Entidad AIWorkflow | `jaraba_ai_agents/src/Entity/AIWorkflow.php` | ContentEntity | ✅ Operativo |
| Entidad AIUsageLog | `jaraba_ai_agents/src/Entity/AIUsageLog.php` | ContentEntity | ✅ Operativo |
| Entidad PendingApproval | `jaraba_ai_agents/src/Entity/PendingApproval.php` | ContentEntity | ✅ Operativo |
| BaseAgent | `jaraba_ai_agents/src/Agent/BaseAgent.php` | ~14,923 líneas | ✅ Operativo |
| SmartMarketingAgent | `jaraba_ai_agents/src/Agent/SmartMarketingAgent.php` | Agente completo | ✅ Operativo |
| SupportAgent | `jaraba_ai_agents/src/Agent/SupportAgent.php` | Agente completo | ✅ Operativo |
| CustomerExperienceAgent | `jaraba_ai_agents/src/Agent/CustomerExperienceAgent.php` | Agente completo | ✅ Operativo |
| StorytellingAgent | `jaraba_ai_agents/src/Agent/StorytellingAgent.php` | Agente completo | ✅ Operativo |
| ProducerCopilotAgent | `jaraba_ai_agents/src/Agent/ProducerCopilotAgent.php` | Agente completo | ✅ Operativo |
| AgentOrchestrator | `jaraba_ai_agents/src/Service/AgentOrchestrator.php` | 296 líneas | ✅ Operativo |
| WorkflowExecutorService | `jaraba_ai_agents/src/Service/WorkflowExecutorService.php` | 448 líneas | ✅ Operativo |
| ModelRouterService | `jaraba_ai_agents/src/Service/ModelRouterService.php` | 372 líneas | ✅ Operativo |
| QualityEvaluatorService | `jaraba_ai_agents/src/Service/QualityEvaluatorService.php` | 394 líneas | ✅ Operativo |
| AIObservabilityService | `jaraba_ai_agents/src/Service/AIObservabilityService.php` | 389 líneas | ✅ Operativo |
| PendingApprovalService | `jaraba_ai_agents/src/Service/PendingApprovalService.php` | 238 líneas | ✅ Operativo |
| TenantBrandVoiceService | `jaraba_ai_agents/src/Service/TenantBrandVoiceService.php` | 290 líneas | ✅ Operativo |
| Tool Registry | `jaraba_ai_agents/src/Tool/ToolRegistry.php` | Registro de tools | ✅ Operativo |
| Tools (Search, Create, Email) | `jaraba_ai_agents/src/Tool/` | 3 tools | ✅ Operativo |
| DashboardController | `jaraba_ai_agents/src/Controller/DashboardController.php` | Dashboard | ✅ Operativo |
| AgentApiController | `jaraba_ai_agents/src/Controller/AgentApiController.php` | REST API | ✅ Operativo |
| WorkflowApiController | `jaraba_ai_agents/src/Controller/WorkflowApiController.php` | REST API | ✅ Operativo |
| 5 config agents preinstalados | `jaraba_ai_agents/config/install/` | 5 YAML | ✅ Operativo |

**GAPS pendientes (10% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G108-1 | **Visual Flow Builder UI**: La especificación contempla un editor visual de flujos (React Flow). Actualmente los flujos se definen solo por código/JSON | 25-35h | 🟡 P2 |
| G108-2 | **Cross-agent collaboration patterns**: Falta orquestación avanzada donde múltiples agentes colaboran en un mismo flujo con transferencia de contexto | 10-15h | 🟢 P3 |
| G108-3 | **Agent performance benchmarking dashboard**: No hay dashboard de benchmarking comparativo entre agentes | 5-10h | 🟢 P3 |

### 2.2 f109: PWA Mobile — 70% Implementado

**Archivos existentes verificados:**

| Componente | Archivo | Estado |
|-----------|---------|--------|
| Service Worker | `web/sw.js` + `web/service-worker.js` | ✅ Cache strategies, offline fallback, push notifications |
| Web App Manifest | `web/manifest.json` | ✅ Configurado |
| PWA Registration | `ecosistema_jaraba_core/js/pwa-register.js` | ✅ Registro del SW |
| Offline Page | `web/offline.html` | ✅ Fallback offline |
| Meta Tags Mobile | Templates del tema | ✅ Viewport configurado |
| Hook page_attachments | `ecosistema_jaraba_core.module` | ✅ Inyecta manifest, theme-color, apple-mobile-web-app |

**GAPS pendientes (30% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G109-1 | **Mobile-specific UI components**: Componentes optimizados para tactil (swipe cards, bottom navigation, pull-to-refresh) | 20-25h | 🟡 P2 |
| G109-2 | **Offline data sync con IndexedDB**: La especificación define un schema IndexedDB para datos offline con conflict resolution. Actualmente solo hay cache de assets | 20-30h | 🟡 P2 |
| G109-3 | **Push notifications backend**: Existe soporte en el SW pero falta el backend FCM integration y PushSubscription entity | 15-20h | 🟡 P2 |
| G109-4 | **iOS-specific optimizations**: Prompts de "Add to Home Screen", splash screens por dispositivo | 5-10h | 🟢 P3 |
| G109-5 | **Background sync queue**: PendingSyncAction entity y cola de acciones offline que se sincronizan al recuperar conexión | 5-10h | 🟡 P2 |

### 2.3 f110: Onboarding Product-Led — 95% Implementado

**Archivos existentes verificados:**

| Componente | Archivo/Servicio | Estado |
|-----------|-----------------|--------|
| OnboardingController | `ecosistema_jaraba_core/src/Controller/OnboardingController.php` (404 líneas) | ✅ Flujo 4 pasos completo |
| TenantOnboardingService | `ecosistema_jaraba_core/src/Service/TenantOnboardingService.php` | ✅ Provisioning, user creation, plan assignment |
| Templates onboarding | `ecosistema-jaraba-welcome.html.twig`, `select-plan.html.twig`, `setup-payment.html.twig` | ✅ |
| SCSS onboarding | `ecosistema_jaraba_core/scss/_onboarding.scss` | ✅ |
| JavaScript onboarding | `ecosistema-jaraba-onboarding.js` | ✅ |
| Journey Engine | `jaraba_journey/` — 7 journey definitions por vertical | ✅ |
| TimeToFirstValueService | `jaraba_journey/src/Service/TimeToFirstValueService.php` | ✅ |
| GuidedTourService | `jaraba_journey/src/Service/GuidedTourService.php` | ✅ |
| InAppMessagingService | `jaraba_journey/src/Service/InAppMessagingService.php` | ✅ |
| UserIntentClassifierService | `jaraba_journey/src/Service/UserIntentClassifierService.php` | ✅ |
| DemoInteractiveService | `jaraba_journey/src/Service/DemoInteractiveService.php` | ✅ |
| ReverseTrialService | `jaraba_journey/src/Service/ReverseTrialService.php` | ✅ |

**GAPS pendientes (5% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G110-1 | **A/B testing de flujos de onboarding**: Variaciones de flujo por cohorte para optimizar TTFV | 8-12h | 🟢 P3 |
| G110-2 | **Video walkthroughs por vertical**: Videos embebidos en cada paso del onboarding | 5-8h | 🟢 P3 |
| G110-3 | **Gamificación con badges**: Sistema de badges por completar hitos del onboarding (profile_pro, quick_starter, aha_achieved) | 5-8h | 🟢 P3 |

### 2.4 f111: Usage-Based Pricing — 85% Implementado

**Archivos existentes verificados:**

| Componente | Archivo/Servicio | Estado |
|-----------|-----------------|--------|
| TenantMeteringService | `ecosistema_jaraba_core/src/Service/TenantMeteringService.php` (245 líneas) | ✅ 8 métricas, record(), increment(), getUsage(), calculateBill(), getForecast() |
| Tabla `tenant_metering` | Definida en `.install` | ✅ Con period tracking |
| FinOpsTrackingService | `ecosistema_jaraba_core/src/Service/FinOpsTrackingService.php` | ✅ |
| UsageLimitsService | `ecosistema_jaraba_core/src/Service/UsageLimitsService.php` | ✅ Enforce plan limits |
| ExpansionRevenueService | `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` | ✅ Expansion opportunities |
| PricingRecommendationService | `ecosistema_jaraba_core/src/Service/PricingRecommendationService.php` | ✅ Plan recommendations |
| JarabaStripeConnect | `ecosistema_jaraba_core/src/Service/JarabaStripeConnect.php` | ✅ Stripe integration |
| Budget alerts | En TenantMeteringService (80% threshold) | ✅ |

**GAPS pendientes (15% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G111-1 | **Dashboard de uso en tiempo real para tenants**: Interfaz frontend en `/mi-cuenta/uso` con gráficos de consumo, proyecciones y alertas | 15-20h | 🟡 P2 |
| G111-2 | **Custom pricing tiers por tenant**: Entidad `PricingRule` para reglas de precio personalizadas más allá de los planes estándar | 10-15h | 🟡 P2 |
| G111-3 | **Usage anomaly detection**: Alertas automáticas por picos anómalos de consumo | 5-10h | 🟢 P3 |

### 2.5 f112: Integration Marketplace — 40% Implementado

**Archivos existentes verificados:**

| Componente | Archivo | Estado | Nota |
|-----------|---------|--------|------|
| MarketplaceController | `ecosistema_jaraba_core/src/Controller/MarketplaceController.php` | ✅ | Solo productos, NO apps/conectores |
| WebhookService | `ecosistema_jaraba_core/src/Service/WebhookService.php` | ✅ | Generic dispatch |
| WebhookController | `ecosistema_jaraba_core/src/Controller/WebhookController.php` | ✅ | Receptor genérico |
| TenantWebhooksForm | `ecosistema_jaraba_core/src/Form/TenantWebhooksForm.php` | ✅ | Config de webhooks por tenant |
| StripeWebhookController | `ecosistema_jaraba_core/src/Controller/StripeWebhookController.php` | ✅ | Stripe-specific |
| Templates marketplace | `marketplace-landing.html.twig`, `search.html.twig`, `product.html.twig` | ✅ | Solo productos |
| SCSS marketplace | `ecosistema_jaraba_core/scss/_marketplace.scss` | ✅ | |
| MarketplaceRecommendationService | `ecosistema_jaraba_core/src/Service/MarketplaceRecommendationService.php` | ✅ | Solo productos |

**GAPS CRÍTICOS pendientes (60% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G112-1 | **Módulo `jaraba_integrations` completo**: No existe. Necesita creación desde cero con la estructura estándar del proyecto | 30-40h | 🔴 P0 |
| G112-2 | **Entidad `Connector`**: Registro de conectores disponibles (tipo, categoría, config schema, auth type) | 20-25h | 🔴 P0 |
| G112-3 | **Entidad `ConnectorInstallation`**: Instalaciones por tenant con credentials y estado de conexión | 15-20h | 🔴 P0 |
| G112-4 | **OAuth2 Server**: Servidor OAuth para autorización de apps terceras con OauthClient entity | 30-40h | 🔴 P0 |
| G112-5 | **Entidad `WebhookSubscription`**: Suscripciones de webhooks con retry policy, delivery logs, HMAC signing | 15-20h | 🔴 P0 |
| G112-6 | **Connector Registry UI**: Frontend en `/integraciones` con catálogo de conectores, 1-click install, estado | 25-35h | 🔴 P0 |
| G112-7 | **8 conectores predefinidos**: Google Sheets, Mailchimp, WhatsApp Business, Holded, LinkedIn, Shopify, Slack, Calendly | 60-80h | 🟡 P2 |
| G112-8 | **MCP Server**: Model Context Protocol server para que AI agents externos puedan interactuar con la plataforma | 20-30h | 🟡 P2 |
| G112-9 | **Developer Portal**: Documentación OpenAPI, sandbox para desarrolladores | 15-25h | 🟢 P3 |

### 2.6 f113: Customer Success — 35% Implementado

**Archivos existentes verificados:**

| Componente | Archivo | Estado | Nota |
|-----------|---------|--------|------|
| HealthDashboardController | `ecosistema_jaraba_core/src/Controller/HealthDashboardController.php` | ✅ | Salud de la PLATAFORMA, no del tenant/customer |
| Templates health | `health-dashboard.html.twig`, `_health-dashboard.scss` | ✅ | Servicios técnicos (DB, Qdrant, Cache) |
| TimeToFirstValueService | `jaraba_journey/src/Service/TimeToFirstValueService.php` | ✅ | TTFV metrics |
| ExpansionRevenueService | `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` | ✅ | Upsell opportunities |
| AlertingService + AlertRule | `ecosistema_jaraba_core/src/Service/AlertingService.php` | ✅ | Alertas configurables |

**GAPS CRÍTICOS pendientes (65% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G113-1 | **Módulo `jaraba_customer_success` completo**: No existe. Crear desde cero | 25-30h | 🔴 P0 |
| G113-2 | **Entidad `CustomerHealth`**: Health score 0-100 por tenant con 5 dimensiones (engagement 30%, adoption 25%, satisfaction 20%, support 15%, growth 10%). Cálculo diario por cron | 25-30h | 🔴 P0 |
| G113-3 | **Entidad `ChurnPrediction`**: Predicciones ML de churn con risk factors, confidence score, recommended actions. Usa @ai.provider para el modelo | 25-35h | 🔴 P0 |
| G113-4 | **Entidad `CsPlaybook`**: Secuencias automatizadas de acciones por condición (reactivation, expansion, onboarding-stuck). Ejecutadas por cron | 20-25h | 🔴 P0 |
| G113-5 | **Entidad `ExpansionSignal`**: Señales de uso (usage_limit_approaching, feature_request, growth_trajectory). Detectadas por metering | 15-20h | 🟡 P2 |
| G113-6 | **NPS Survey system**: Encuestas in-app con scoring y tracking longitudinal | 20-25h | 🟡 P2 |
| G113-7 | **Customer Success Dashboard**: Frontend en `/customer-success` con health scores, churn risk, playbook execution, expansion pipeline | 25-35h | 🔴 P0 |
| G113-8 | **Lifecycle stage tracking**: Clasificación de tenants por etapa (trial, onboarding, adoption, expansion, renewal) con transiciones automáticas | 15-20h | 🟡 P2 |
| G113-9 | **Engagement scoring**: DAU/MAU ratio, feature usage tracking, time-in-app por tenant | 15-20h | 🟡 P2 |
| G113-10 | **CSAT/feedback collection**: Micro-encuestas post-interacción con almacenamiento y trending | 10-15h | 🟡 P2 |

### 2.7 f114: Knowledge Base — 85% Implementado

**Archivos existentes verificados:**

| Componente | Archivo/Módulo | Estado |
|-----------|---------------|--------|
| Módulo `jaraba_tenant_knowledge` | `web/modules/custom/jaraba_tenant_knowledge/` (273 líneas module) | ✅ |
| Entidad TenantFaq | `jaraba_tenant_knowledge/src/Entity/TenantFaq.php` | ✅ |
| Entidad TenantPolicy | `jaraba_tenant_knowledge/src/Entity/TenantPolicy.php` | ✅ |
| Entidad TenantProductEnrichment | `jaraba_tenant_knowledge/src/Entity/TenantProductEnrichment.php` | ✅ |
| Entidad TenantDocument | `jaraba_tenant_knowledge/src/Entity/TenantDocument.php` | ✅ |
| KnowledgeIndexerService | `jaraba_tenant_knowledge/src/Service/KnowledgeIndexerService.php` | ✅ Auto-indexing a Qdrant |
| TenantKnowledgeManager | `jaraba_tenant_knowledge/src/Service/TenantKnowledgeManager.php` | ✅ CRUD knowledge |
| KnowledgeDashboardController | `jaraba_tenant_knowledge/src/Controller/KnowledgeDashboardController.php` | ✅ |
| KnowledgeApiController | `jaraba_tenant_knowledge/src/Controller/KnowledgeApiController.php` | ✅ REST API |
| Templates | `knowledge-dashboard.html.twig`, `knowledge-test-console.html.twig` | ✅ |
| Auto-indexing hooks | `hook_entity_insert/update/delete` en module file | ✅ |
| FaqGeneratorService | `jaraba_copilot_v2/src/Service/FaqGeneratorService.php` | ✅ |
| FAQ pages SEO | `jaraba_geo/templates/geo-faq-page.html.twig` | ✅ |

**GAPS pendientes (15% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G114-1 | **Public Help Center theme**: Portal público `/ayuda` con diseño premium, búsqueda semántica, categorías navegables. Actualmente la KB es solo admin-facing | 20-25h | 🟡 P2 |
| G114-2 | **Article versioning**: Control de versiones de artículos KB con diff visual | 5-10h | 🟢 P3 |
| G114-3 | **Multilingual KB articles**: Soporte i18n completo para artículos (entity translatable) | 10-15h | 🟡 P2 |
| G114-4 | **FAQ Bot contextual**: Widget de chat con grounding estricto en KB, escalación a humano. Extender `jaraba_copilot_v2` con modo `kb_faq` | 10-15h | 🟡 P2 |

### 2.8 f115: Security & Compliance — 75% Implementado

**Archivos existentes verificados:**

| Componente | Archivo | Estado |
|-----------|---------|--------|
| SecurityHeadersSubscriber | `ecosistema_jaraba_core/src/EventSubscriber/SecurityHeadersSubscriber.php` | ✅ CORS, CSP, HSTS, X-Frame-Options |
| SecurityHeadersSettingsForm | `ecosistema_jaraba_core/src/Form/SecurityHeadersSettingsForm.php` | ✅ Configurable desde UI |
| ConsentService + ConsentRecord | `jaraba_analytics/src/Service/ConsentService.php` + entity | ✅ GDPR consent |
| ConsentController | `jaraba_analytics/src/Controller/ConsentController.php` | ✅ |
| ImpersonationAuditLog | `ecosistema_jaraba_core/src/Entity/ImpersonationAuditLog.php` | ✅ |
| DocumentDownloadLog | Entity existente | ✅ |
| FirmaDigitalService | `ecosistema_jaraba_core/src/Service/FirmaDigitalService.php` | ✅ Digital signatures |
| CertificadoPdfService | `ecosistema_jaraba_core/src/Service/CertificadoPdfService.php` | ✅ PDF certificates |
| ImpersonationService | `ecosistema_jaraba_core/src/Service/ImpersonationService.php` | ✅ Admin impersonation with audit trail |
| Permission system | Múltiples `.permissions.yml` | ✅ Extensivo |

**GAPS pendientes (25% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G115-1 | **Compliance Dashboard**: Frontend `/admin/seguridad` con estado de controles SOC2/ISO 27001/ENS, evaluaciones pendientes, findings abiertos | 20-25h | 🟡 P2 |
| G115-2 | **Entidad `AuditLog` inmutable**: Registro centralizado de todos los eventos de seguridad con retención configurable. Extender el sistema actual que es fragmentado | 10-15h | 🟡 P2 |
| G115-3 | **Entidad `SecurityPolicy`**: Políticas configurables por scope (global, tenant) con enforcement automático | 10-15h | 🟡 P2 |
| G115-4 | **Entidad `ComplianceAssessment`**: Evaluaciones periódicas con findings y remediation tracking | 10-15h | 🟡 P2 |
| G115-5 | **Data retention automation**: Políticas de retención de datos automatizadas por cron, con cumplimiento GDPR/ARCO | 5-10h | 🟢 P3 |

### 2.9 f116: Advanced Analytics — 80% Implementado

**Archivos existentes verificados:**

| Componente | Archivo/Módulo | Estado |
|-----------|---------------|--------|
| Módulo `jaraba_analytics` | `web/modules/custom/jaraba_analytics/` (132 líneas module) | ✅ 100% nativo |
| AnalyticsService | `jaraba_analytics/src/Service/AnalyticsService.php` | ✅ Event tracking |
| AnalyticsAggregatorService | `jaraba_analytics/src/Service/AnalyticsAggregatorService.php` | ✅ Cron aggregation |
| AnalyticsEvent entity | `jaraba_analytics/src/Entity/AnalyticsEvent.php` | ✅ Raw events |
| AnalyticsApiController | `jaraba_analytics/src/Controller/AnalyticsApiController.php` | ✅ REST API |
| Templates analytics | `analytics-dashboard.html.twig` | ✅ |
| Módulo `jaraba_heatmap` | Completo con HeatmapAggregatorService | ✅ |
| Módulo `jaraba_pixels` | PixelDispatcherService, EventMapperService (FB, Google) | ✅ |
| TenantAnalyticsService | En `jaraba_copilot_v2` | ✅ |
| Multiple dashboard SCSS | `_finops-dashboard.scss`, `_journey-dashboard.scss`, `_analytics-dashboard.scss` | ✅ |

**GAPS pendientes (20% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G116-1 | **Cohort analysis**: Segmentación por cohortes de registro con retention curves. Entidad `CohortDefinition` | 15-20h | 🟡 P2 |
| G116-2 | **Funnel tracking**: Definición y visualización de funnels de conversión (onboarding, purchase, application) | 15-20h | 🟡 P2 |
| G116-3 | **Custom Report Builder UI**: Frontend drag-drop para crear informes personalizados con métricas y dimensiones. Entidad `CustomReport` | 20-30h | 🟡 P2 |
| G116-4 | **Scheduled Reports**: Entidad `ScheduledReport` con envío automático por email/Slack en frecuencias configurables | 10-15h | 🟡 P2 |
| G116-5 | **Data export CSV/Excel/PDF**: Exportación de datos con formato y branding del tenant | 5-10h | 🟢 P3 |

### 2.10 f117: White-Label — 70% Implementado

**Archivos existentes verificados:**

| Componente | Archivo/Módulo | Estado |
|-----------|---------------|--------|
| Módulo `jaraba_theming` | `web/modules/custom/jaraba_theming/` | ✅ |
| TenantThemeConfig entity | `jaraba_theming/src/Entity/TenantThemeConfig.php` | ✅ Store theme settings per tenant |
| ThemeTokenService | `jaraba_theming/src/Service/ThemeTokenService.php` | ✅ CSS variable generation |
| IndustryPresetService | `jaraba_theming/src/Service/IndustryPresetService.php` | ✅ 4 presets |
| VisualCustomizerController | `jaraba_theming/src/Controller/VisualCustomizerController.php` | ✅ Visual editor |
| TenantThemeCustomizerForm | `jaraba_theming/src/Form/TenantThemeCustomizerForm.php` | ✅ Self-service |
| TenantDomainSettingsForm | Form existente | ✅ Custom domain settings UI |
| Style presets YAML | `comercio_tech.yml`, `comercio_gastro.yml`, `agro_modern.yml`, `agro_traditional.yml` | ✅ 4 presets |
| Rutas | `/mi-cuenta/personalizar-tema`, `/dashboard/tema` | ✅ |
| Theme settings (70+ opciones) | `ecosistema_jaraba_theme.theme` | ✅ 13 tabs de configuración |

**GAPS pendientes (30% restante):**

| Gap | Descripción | Esfuerzo | Prioridad |
|-----|-------------|----------|-----------|
| G117-1 | **Custom domain CNAME automation**: Actualmente el form existe pero no hay automatización real de DNS verification + SSL provisioning con Caddy/Let's Encrypt | 25-35h | 🟡 P2 |
| G117-2 | **White-label email templates (MJML)**: Templates de email branded por tenant con logo, colores, footer personalizado. 9 templates predefinidos | 20-25h | 🟡 P2 |
| G117-3 | **PDF templates branded**: Facturas, certificados, informes con branding del tenant via Puppeteer + Handlebars | 15-20h | 🟡 P2 |
| G117-4 | **Reseller Portal**: Entidad `Reseller` con gestión de sub-tenants, comisiones, territories. Frontend `/partner-portal` | 20-30h | 🟡 P2 |
| G117-5 | **Font customization**: Upload y selección de fuentes custom por tenant (Google Fonts + custom woff2) | 5-10h | 🟢 P3 |
| G117-6 | **Theme preview mode**: Vista previa de cambios de tema antes de publicar | 5-10h | 🟢 P3 |

---

## 3. Tabla de Correspondencia con Especificaciones Técnicas

| Doc # | Título | Fase | Módulo Drupal | % Impl. | Estado Real | Módulos Existentes que Cubren |
|-------|--------|------|--------------|---------|-------------|-------------------------------|
| **108** | Platform AI Agent Flows | 1 | `jaraba_agent_flows` (nuevo) / `jaraba_ai_agents` (existente) | 90% | ✅ Extenso | `jaraba_ai_agents`, `ecosistema_jaraba_core` |
| **109** | Platform PWA Mobile | 2 | `jaraba_pwa` (nuevo parcial) | 70% | ✅ Implementado | `ecosistema_jaraba_core` (PWA hooks), `web/sw.js` |
| **110** | Platform Onboarding Product-Led | 3 | `jaraba_onboarding` (no necesario) | 95% | ✅ Extenso | `ecosistema_jaraba_core`, `jaraba_journey` |
| **111** | Platform Usage-Based Pricing | 4 | `jaraba_usage_billing` (nuevo parcial) | 85% | ✅ Implementado | `ecosistema_jaraba_core` (TenantMeteringService, UsageLimitsService) |
| **112** | Platform Integration Marketplace | 5 | `jaraba_integrations` (nuevo) | 40% | ⚠️ Parcial | `ecosistema_jaraba_core` (webhooks, marketplace productos) |
| **113** | Platform Customer Success | 6 | `jaraba_customer_success` (nuevo) | 35% | ⚠️ Parcial | `ecosistema_jaraba_core` (health dashboard, alerting) |
| **114** | Platform Knowledge Base | 7 | `jaraba_knowledge_base` (nuevo parcial) | 85% | ✅ Extenso | `jaraba_tenant_knowledge`, `jaraba_rag`, `jaraba_copilot_v2` |
| **115** | Platform Security Compliance | 8 | `jaraba_security_compliance` (nuevo) | 75% | ✅ Implementado | `ecosistema_jaraba_core` (SecurityHeaders, consent, audit logs) |
| **116** | Platform Advanced Analytics | 9 | `jaraba_analytics_bi` (extensión) | 80% | ✅ Extenso | `jaraba_analytics`, `jaraba_heatmap`, `jaraba_pixels` |
| **117** | Platform White-Label | 10 | `jaraba_whitelabel` (nuevo parcial) | 70% | ✅ Implementado | `jaraba_theming`, `ecosistema_jaraba_theme` |

**Dependencias cruzadas con módulos existentes (verificadas en código):**

| Doc # | Reutiliza de | % Reutilización Real |
|-------|-------------|---------------------|
| 108 | `jaraba_ai_agents` (9 agentes, orchestrator, tools, workflows) | **90%** (vs 40% estimado en v1) |
| 109 | `ecosistema_jaraba_core` (PWA hooks, manifest), Theme (parciales) | **70%** (vs 20% estimado en v1) |
| 110 | `jaraba_journey` (7 journeys, TTFV, tours), Core (onboarding controller) | **95%** (vs 35% estimado en v1) |
| 111 | `ecosistema_jaraba_core` (metering, limits, Stripe, FinOps) | **85%** (vs 50% estimado en v1) |
| 112 | `ecosistema_jaraba_core` (webhooks, marketplace) | **40%** (≈ estimado en v1) |
| 113 | `ecosistema_jaraba_core` (health, alerting), `jaraba_foc`, `jaraba_email` | **35%** (≈ estimado en v1) |
| 114 | `jaraba_tenant_knowledge` (4 entities, indexer), `jaraba_rag`, `jaraba_copilot_v2` | **85%** (vs 60% estimado en v1) |
| 115 | `ecosistema_jaraba_core` (security headers, consent, audit, permissions) | **75%** (vs 30% estimado en v1) |
| 116 | `jaraba_analytics` (events, aggregator), `jaraba_heatmap`, `jaraba_pixels` | **80%** (vs 35% estimado en v1) |
| 117 | `jaraba_theming` (TenantThemeConfig, presets, customizer), Theme (70+ settings) | **70%** (vs 40% estimado en v1) |

---

## 4. Cumplimiento de Directrices del Proyecto

> [!IMPORTANT]
> Las directrices de esta sección son **obligatorias** para toda implementación de gaps. Se refieren tanto al código existente que se extiende como al código nuevo que se crea. Para cada directriz se indica la referencia documental y un ejemplo concreto aplicado a Platform Services.

### 4.1 Directriz: i18n — Textos siempre traducibles

**Referencia:** `.agent/workflows/i18n-traducciones.md`

Toda cadena de texto visible al usuario DEBE ser traducible. La base es español de España. Los textos en español se escriben directamente dentro de las funciones de traducción.

| Contexto | Método | Ejemplo en Platform Services |
|----------|--------|------------------------------|
| Controllers PHP | `$this->t()` | `$this->t('Panel de Customer Success')` |
| Services PHP | `$this->t()` (con `StringTranslationTrait`) | `$this->t('Health score calculado: @score', ['@score' => $score])` |
| Templates Twig | `{% trans %}...{% endtrans %}` | `{% trans %}Crear nuevo conector{% endtrans %}` |
| JavaScript ES6+ | `Drupal.t()` | `Drupal.t('Sincronización completada')` |
| Formularios | `'#title' => $this->t(...)` | `'#title' => $this->t('Nombre del conector')` |
| Placeholders | `'#placeholder' => $this->t(...)` | `'#placeholder' => $this->t('Buscar artículos...')` |
| Aria-labels | `'#attributes' => ['aria-label' => $this->t(...)]` | `'aria-label' => $this->t('Filtrar por categoría')` |
| Abreviaturas | Siempre en español completo | `5.1 meses` (NUNCA `5.1 mo`) |
| Acrónimos técnicos | Glosario visible si es necesario | `MRR (Ingresos Recurrentes Mensuales)` |

**Regla crítica:** Nunca escribir texto visible al usuario fuera de una función de traducción. Esto incluye atributos `aria-label`, placeholders de formularios, tooltips, mensajes flash y textos en JavaScript.

### 4.2 Directriz: Modelo SCSS con Federated Design Tokens

**Referencia:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`, `.agent/workflows/scss-estilos.md`

Los módulos de Platform Services son **consumidores** del sistema de Design Tokens. Ninguno define variables `$ej-*` propias. Todos consumen exclusivamente `var(--ej-*, $fallback)` de la cascada de 5 capas:

| Capa | Fuente | Prioridad |
|------|--------|-----------|
| L1 | `_variables.scss` (ecosistema_jaraba_core) — SCSS compile-time fallbacks | Más baja |
| L2 | `_injectable.scss` (ecosistema_jaraba_core) — `:root` CSS Custom Properties | Base runtime |
| L3 | Component tokens (cada módulo) — scope local de componente | Componente |
| L4 | Tenant Override (`hook_preprocess_html()`) — inyección desde UI Drupal | Tenant |
| L5 | Vertical Presets (DesignTokenConfig entity) — paletas por vertical | Más alta |

**Patrón CORRECTO:**
```scss
// ✅ Solo consumir CSS Custom Properties con fallback inline
.connector-card {
  background: var(--ej-bg-surface, #FFFFFF);
  border: 1px solid var(--ej-border-color, #E5E7EB);
  border-radius: var(--ej-border-radius, 12px);
  box-shadow: var(--ej-shadow-md, 0 4px 6px rgba(0, 0, 0, 0.07));
  padding: var(--ej-spacing-lg, 1.5rem);
  color: var(--ej-text-primary, #212121);
  font-family: var(--ej-font-body, 'Inter', sans-serif);
  transition: var(--ej-transition, all 250ms cubic-bezier(0.4, 0, 0.2, 1));
}

// ❌ PROHIBIDO: Definir variables SCSS locales
// $my-card-color: #233D63;  // NO — viola el SSOT
// $ej-color-primary: #FF8C42;  // NO — duplica core
```

**Cada módulo nuevo DEBE tener `package.json`:**
```json
{
  "name": "jaraba-{modulo}",
  "version": "1.0.0",
  "scripts": {
    "build": "sass scss/main.scss:css/jaraba-{modulo}.css --style=compressed",
    "watch": "sass scss/main.scss:css/jaraba-{modulo}.css --watch"
  },
  "devDependencies": {
    "sass": "^1.80.0"
  }
}
```

### 4.3 Directriz: Dart Sass moderno

**Referencia:** Directrices v6.2 §2.2.1

Todos los módulos usan Dart Sass `^1.80.0` (nunca LibSass). Las funciones deprecadas están prohibidas:

```scss
// ✅ CORRECTO: Dart Sass moderno con @use
@use 'sass:color';
@use 'sass:math';

.status-badge--success {
  background: color.scale(#43A047, $lightness: 80%);
}

.grid-item {
  width: math.div(100%, 3);
}

// ❌ PROHIBIDO:
// background: darken($color, 10%);   // NO
// background: lighten($color, 20%);  // NO
// width: 100% / 3;                   // NO — división ambigua
```

**Compilación en WSL:**
```bash
cd /home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_{modulo}
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm use --lts
npm run build
lando drush cr
```

### 4.4 Directriz: Frontend limpio sin regiones Drupal

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

Cada página de frontend usa un template Twig dedicado que controla el 100% del HTML. NO se usa `page.content` como contenedor heredado de Drupal. El contenido se inyecta desde el controller.

**Estructura obligatoria (verificada en 23 templates existentes del tema):**

```twig
{# page--integraciones.html.twig — Layout limpio full-width #}
{{ attach_library('ecosistema_jaraba_theme/global-styling') }}
{{ attach_library('jaraba_integrations/integrations') }}

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
  site_name: site_name,
  logo: logo|default(''),
  logged_in: logged_in,
  theme_settings: theme_settings|default({})
} only %}

<main id="main-content" class="platform-page platform-page--integrations" role="main">
  <div class="platform-container">
    {{ content }}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
  site_name: site_name,
  theme_settings: theme_settings|default({})
} only %}

{% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' only %}
```

**Parciales reutilizables existentes (verificados en el tema):**

| Parcial | Ruta | Uso en Platform Services |
|---------|------|--------------------------|
| `_header.html.twig` | `partials/` | Header responsive con navegación — TODAS las páginas |
| `_header-classic.html.twig` | `partials/` | Variante header clásico |
| `_header-centered.html.twig` | `partials/` | Variante header centrado |
| `_header-split.html.twig` | `partials/` | Variante header split |
| `_header-hero.html.twig` | `partials/` | Variante header con hero |
| `_header-minimal.html.twig` | `partials/` | Header mínimo para canvas/editor |
| `_footer.html.twig` | `partials/` | Footer configurable desde theme settings |
| `_copilot-fab.html.twig` | `partials/` | Botón flotante del copiloto IA |
| `_hero.html.twig` | `partials/` | Sección hero reutilizable |
| `_stats.html.twig` | `partials/` | Grid de estadísticas |
| `_features.html.twig` | `partials/` | Grid de features |
| `_article-card.html.twig` | `partials/` | Card de artículo |
| `_category-filter.html.twig` | `partials/` | Filtro por categorías |
| `_auth-hero.html.twig` | `partials/` | Hero de autenticación |
| `_intentions-grid.html.twig` | `partials/` | Grid de intenciones PLG |
| `_related-articles.html.twig` | `partials/` | Artículos relacionados |

**El tenant NO accede al tema de administración de Drupal.** Las rutas `/admin/*` son solo para el superadministrador. Los tenants gestionan todo desde rutas frontend limpias con slide-panels para CRUD.

### 4.5 Directriz: Body classes via hook_preprocess_html()

**Referencia:** `.agent/workflows/frontend-page-pattern.md`

> [!CAUTION]
> Las clases añadidas en el template con `attributes.addClass()` NO funcionan para el `<body>`. El body se renderiza en `html.html.twig`, no en `page.html.twig`. Se DEBE usar `hook_preprocess_html()`.

```php
// En ecosistema_jaraba_theme.theme — YA IMPLEMENTADO para múltiples rutas
// EXTENDER con las nuevas rutas de Platform Services gaps:
$platform_routes = [
  'jaraba_integrations.' => 'page-platform page-integrations',
  'jaraba_customer_success.' => 'page-platform page-customer-success',
  // Las demás ya están cubiertas por módulos existentes
];
```

### 4.6 Directriz: CRUD en modales slide-panel

**Referencia:** `.agent/workflows/slide-panel-modales.md`

Todas las operaciones crear/editar/ver abren en slide-panel off-canvas. El parcial `_slide-panel.html.twig` ya existe en el tema y se incluye vía `{% include %}`.

**Activación (sin JS adicional):**
```html
<button class="btn btn--primary"
        data-slide-panel="connector-form"
        data-slide-panel-url="/integraciones/add"
        data-slide-panel-title="{{ 'Instalar conector'|trans }}">
  {% trans %}+ Instalar{% endtrans %}
</button>
```

**El controller detecta AJAX y devuelve HTML limpio:**
```php
public function add(Request $request): array|Response {
  $entity = $this->entityTypeManager()->getStorage('connector_installation')->create();
  $form = $this->entityFormBuilder()->getForm($entity, 'add');
  if ($request->isXmlHttpRequest()) {
    return new Response(
      (string) $this->renderer->render($form), 200,
      ['Content-Type' => 'text/html; charset=UTF-8']
    );
  }
  return ['#theme' => 'connector_form_page', '#form' => $form];
}
```

**Ocultar ruido de Drupal en formularios del slide-panel:**
```php
function jaraba_integrations_form_alter(array &$form, FormStateInterface $form_state, string $form_id): void {
  if (str_contains($form_id, 'connector')) {
    _jaraba_integrations_hide_format_guidelines($form);
  }
}
```

### 4.7 Directriz: Entidades con Field UI y Views

**Referencia:** `.agent/workflows/drupal-custom-modules.md`

Todas las entidades nuevas de Platform Services se implementan como **ContentEntity** (nunca ConfigEntity para datos de usuario/operación). Los **4 archivos YAML obligatorios** por entidad son:

1. **`.routing.yml`** — Rutas admin + frontend + API REST
2. **`.links.menu.yml`** — Navegación en `/admin/structure` con `parent: system.admin_structure`
3. **`.links.task.yml`** — Pestañas en `/admin/content` con `base_route: system.admin_content`
4. **`.links.action.yml`** — Botones "Añadir" con `appears_on`

**Annotation obligatoria con handlers completos:**
```php
/**
 * @ContentEntityType(
 *   id = "{entity_id}",
 *   label = @Translation("..."),
 *   label_collection = @Translation("..."),
 *   handlers = {
 *     "list_builder" = "Drupal\{module}\{Entity}ListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\{module}\Form\{Entity}Form",
 *       "add" = "Drupal\{module}\Form\{Entity}Form",
 *       "edit" = "Drupal\{module}\Form\{Entity}Form",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\{module}\{Entity}AccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "{table}",
 *   admin_permission = "administer {entities}",
 *   field_ui_base_route = "{module}.settings",
 *   entity_keys = { "id" = "id", "uuid" = "uuid", "label" = "name" },
 *   links = {
 *     "canonical" = "/admin/content/{entities}/{entity}",
 *     "add-form" = "/admin/content/{entities}/add",
 *     "edit-form" = "/admin/content/{entities}/{entity}/edit",
 *     "delete-form" = "/admin/content/{entities}/{entity}/delete",
 *     "collection" = "/admin/content/{entities}",
 *   },
 * )
 */
```

### 4.8 Directriz: No hardcodear configuración

Ninguna configuración de negocio puede estar hardcodeada. Todos los valores configurables usan entidades, theme settings o variables de entorno:

| Tipo | Mecanismo | Ejemplo |
|------|-----------|---------|
| Límites de plan | `SaasPlan` ContentEntity con campos | `max_integrations`, `max_reports` |
| Feature flags | `Feature` ContentEntity | `customer_success_enabled` |
| API keys | `settings.local.php` (env vars) | `STRIPE_SECRET_KEY`, `QDRANT_API_KEY` |
| Textos del footer | Theme settings (UI Drupal) | Configurable sin código |
| Colores de marca | Theme settings → CSS Custom Properties | `--ej-color-primary` |
| Umbrales de negocio | Config forms del módulo | `churn_risk_threshold: 40` |

### 4.9 Directriz: Parciales Twig reutilizables

Antes de crear un componente Twig, verificar si ya existe un parcial reutilizable. Si un componente se usa en 2+ páginas de Platform Services, crear un parcial compartido dentro del módulo:

```
templates/partials/
├── _platform-stat-card.html.twig     ← Tarjeta KPI (reutilizar _stats.html.twig del tema si posible)
├── _platform-empty-state.html.twig   ← Estado vacío con CTA
├── _platform-data-table.html.twig    ← Tabla de datos con ordenación
└── _platform-filter-bar.html.twig    ← Barra de filtros
```

**Uso con `only` keyword obligatorio:**
```twig
{% include '@jaraba_integrations/partials/_connector-card.html.twig' with {
  'connector': connector,
  'show_actions': true,
} only %}
```

### 4.10 Directriz: Seguridad

| Área | Requisito | Implementación |
|------|-----------|----------------|
| API Keys | Variables de entorno, NUNCA en config DB | `settings.local.php` |
| Rate Limiting | Obligatorio en todos los endpoints API | `RateLimiterService` con Redis |
| CSRF | Token en formularios Drupal | Form API nativo (automático) |
| XSS | Escapar output en Twig | Automático en Twig |
| SQL Injection | Query builder de Drupal | `$query->condition()` |
| Webhooks | Verificación HMAC obligatoria | `hash_hmac('sha256', $payload, $secret)` |
| Tenant isolation | Filtrar SIEMPRE por `tenant_id` | `$query->condition('tenant_id', $tenantId)` |
| Qdrant | `must` (AND) para tenant_id | NUNCA `should` (OR) |

### 4.11 Directriz: Comentarios de código en español (3 dimensiones)

```php
/**
 * Servicio de cálculo de Health Score para Customer Success.
 *
 * ESTRUCTURA: Integrado en jaraba_customer_success, inyectado vía services.yml.
 * Depende de FinOpsTrackingService y TenantManager.
 *
 * LÓGICA: Calcula puntuación 0-100 ponderada por 5 dimensiones:
 * Engagement (30%), Adoption (25%), Satisfaction (20%), Support (15%), Growth (10%).
 * Recalcula diariamente por cron. Categorías: Healthy, Neutral, At Risk, Critical.
 *
 * SINTAXIS:
 * @param int $tenantId ID del tenant a evaluar
 * @return array{overall_score: int, category: string, breakdown: array}
 */
```

### 4.12 Directriz: Iconos SVG duotone

**Referencia:** `.agent/workflows/scss-estilos.md`

Cada módulo nuevo crea sus iconos SVG en dos versiones: outline (`{nombre}.svg`) y duotone (`{nombre}-duotone.svg`).

**Ubicación centralizada:**
```
web/modules/custom/ecosistema_jaraba_core/images/icons/
├── ai/           ← Agent Flows
├── analytics/    ← Analytics BI, Usage Billing, Customer Success
├── business/     ← White-Label, Integrations, Compliance
├── ui/           ← Onboarding, Knowledge Base, PWA
└── actions/      ← CRUD, sync, download, export
```

**Uso en Twig:**
```twig
{{ jaraba_icon('business', 'connector', { color: 'corporate', size: '24px', variant: 'duotone' }) }}
```

### 4.13 Directriz: AI via abstracción @ai.provider

**Referencia:** `.agent/workflows/ai-integration.md`

NUNCA clientes HTTP directos a APIs de LLM. Siempre `@ai.provider` de Drupal con failover entre providers.

### 4.14 Directriz: Automaciones via hooks Drupal

**Referencia:** `.agent/workflows/drupal-eca-hooks.md`

Automatizaciones en `.module` con hooks de Drupal, NO con ECA BPMN UI. Hooks principales por gap:

| Gap | hook_entity_insert | hook_entity_update | hook_cron | hook_mail |
|-----|-------------------|-------------------|-----------|-----------|
| G112 Integrations | Registrar instalación | Estado conexión | Verificar salud | Notificar desconexiones |
| G113 Customer Success | — | Recalcular health | Health scores diarios + playbooks | Emails de playbook |
| G114 Knowledge Base (pub) | Generar embeddings | Actualizar embeddings | Artículos populares | — |
| G116 Analytics | — | — | Informes programados | Email informes |
| G117 White-Label | Provisionar dominio | Actualizar SSL | Verificar DNS | Emails branded |

---

## 5. Arquitectura General de Módulos

### 5.1 Mapa de módulos y dependencias (actualizado con auditoría)

```
┌──────────────────────────────────────────────────────────────────┐
│              GAPS A IMPLEMENTAR (Platform Services)              │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │ jaraba_      │  │ jaraba_      │  │ Extender             │  │
│  │ integrations │  │ customer_    │  │ jaraba_analytics     │  │
│  │ (NUEVO)      │  │ success      │  │ jaraba_theming       │  │
│  │ 60% trabajo  │  │ (NUEVO)      │  │ jaraba_tenant_knowl. │  │
│  │              │  │ 65% trabajo  │  │ ecosistema_core      │  │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬───────────┘  │
│         │                  │                      │              │
├─────────┴──────────────────┴──────────────────────┴──────────────┤
│              MÓDULOS YA IMPLEMENTADOS (Base Sólida)              │
├──────────────────────────────────────────────────────────────────┤
│  ecosistema_jaraba_core  │  jaraba_ai_agents   │  jaraba_foc    │
│  ecosistema_jaraba_theme │  jaraba_journey     │  jaraba_email  │
│  jaraba_analytics        │  jaraba_theming     │  jaraba_rag    │
│  jaraba_tenant_knowledge │  jaraba_copilot_v2  │  jaraba_geo    │
│  jaraba_heatmap          │  jaraba_pixels      │  jaraba_pwa*   │
└──────────────────────────────────────────────────────────────────┘
* jaraba_pwa es funcionalidad PWA integrada en core, no módulo separado
```

### 5.2 Estructura de directorios para módulos NUEVOS

Solo las fases con gaps >50% necesitan módulo nuevo: `jaraba_integrations` (f112) y `jaraba_customer_success` (f113). El resto de gaps se implementan como extensiones de módulos existentes.

```
web/modules/custom/jaraba_integrations/          ← MÓDULO NUEVO
├── jaraba_integrations.info.yml
├── jaraba_integrations.module
├── jaraba_integrations.install
├── jaraba_integrations.services.yml
├── jaraba_integrations.routing.yml
├── jaraba_integrations.links.menu.yml
├── jaraba_integrations.links.task.yml
├── jaraba_integrations.links.action.yml
├── jaraba_integrations.permissions.yml
├── jaraba_integrations.libraries.yml
├── config/
│   ├── install/
│   └── schema/jaraba_integrations.schema.yml
├── src/
│   ├── Entity/
│   │   ├── Connector.php
│   │   ├── ConnectorInstallation.php
│   │   ├── OauthClient.php
│   │   └── WebhookSubscription.php
│   ├── Controller/
│   │   ├── IntegrationDashboardController.php
│   │   ├── ConnectorApiController.php
│   │   └── OauthController.php
│   ├── Service/
│   │   ├── ConnectorRegistryService.php
│   │   ├── ConnectorInstallerService.php
│   │   ├── OauthServerService.php
│   │   ├── WebhookDispatcherService.php
│   │   └── ConnectorHealthCheckService.php
│   ├── Form/
│   │   ├── ConnectorForm.php
│   │   ├── ConnectorInstallationForm.php
│   │   └── IntegrationSettingsForm.php
│   └── Access/
│       ├── ConnectorAccessControlHandler.php
│       └── ConnectorInstallationAccessControlHandler.php
├── templates/
│   ├── integration-dashboard.html.twig
│   └── partials/
│       ├── _connector-card.html.twig
│       ├── _connector-detail.html.twig
│       └── _integration-empty-state.html.twig
├── scss/
│   ├── main.scss
│   ├── _integration-dashboard.scss
│   └── _connector-card.scss
├── css/
│   └── jaraba-integrations.css
├── js/
│   └── integrations.js
└── package.json

web/modules/custom/jaraba_customer_success/      ← MÓDULO NUEVO
├── jaraba_customer_success.info.yml
├── jaraba_customer_success.module
├── jaraba_customer_success.install
├── jaraba_customer_success.services.yml
├── jaraba_customer_success.routing.yml
├── jaraba_customer_success.links.menu.yml
├── jaraba_customer_success.links.task.yml
├── jaraba_customer_success.links.action.yml
├── jaraba_customer_success.permissions.yml
├── jaraba_customer_success.libraries.yml
├── config/
│   ├── install/
│   └── schema/jaraba_customer_success.schema.yml
├── src/
│   ├── Entity/
│   │   ├── CustomerHealth.php
│   │   ├── ChurnPrediction.php
│   │   ├── CsPlaybook.php
│   │   └── ExpansionSignal.php
│   ├── Controller/
│   │   ├── CustomerSuccessDashboardController.php
│   │   ├── HealthScoreApiController.php
│   │   └── PlaybookApiController.php
│   ├── Service/
│   │   ├── HealthScoreCalculatorService.php
│   │   ├── ChurnPredictionService.php
│   │   ├── PlaybookExecutorService.php
│   │   ├── EngagementScoringService.php
│   │   ├── NpsSurveyService.php
│   │   └── LifecycleStageService.php
│   ├── Form/
│   │   ├── CustomerHealthForm.php
│   │   ├── CsPlaybookForm.php
│   │   └── CustomerSuccessSettingsForm.php
│   └── Access/
│       └── CustomerHealthAccessControlHandler.php
├── templates/
│   ├── customer-success-dashboard.html.twig
│   └── partials/
│       ├── _health-score-card.html.twig
│       ├── _churn-risk-panel.html.twig
│       ├── _playbook-timeline.html.twig
│       └── _cs-empty-state.html.twig
├── scss/
│   ├── main.scss
│   ├── _cs-dashboard.scss
│   └── _health-score.scss
├── css/
│   └── jaraba-customer-success.css
├── js/
│   └── customer-success.js
└── package.json
```

### 5.3 Compilación SCSS

**Comando estándar (WSL, no Docker):**
```bash
cd /home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_{modulo}
export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
nvm use --lts
npm install    # Solo la primera vez
npm run build  # Compilar SCSS
lando drush cr # Limpiar caché de Drupal
```

---

## 6. GAPS — FASE 1: AI Agent Flows (Doc 108)

**Estado:** 90% implementado en `jaraba_ai_agents`
**Estrategia:** Extender módulo existente, NO crear módulo nuevo

### 6.1 Gaps a Implementar

#### G108-1: Visual Flow Builder UI (25-35h)

**Descripción extensa:** La especificación f108 contempla un editor visual de flujos basado en React Flow donde los usuarios pueden crear workflows arrastrando nodos (acción, decisión LLM, condicional, aprobación humana) y conectándolos visualmente. Actualmente los flujos se definen solo en JSON/código a través del `AgentFlowForm`. El gap consiste en crear una interfaz React embebida dentro del formulario Drupal que permita diseñar flujos de forma visual, con un panel de nodos disponibles, conexiones drag-and-drop, y sincronización bidireccional con el campo `flow_definition` JSON.

**Archivos a crear:**
- `jaraba_ai_agents/js/flow-builder/` — Aplicación React con React Flow
- `jaraba_ai_agents/scss/_flow-builder.scss` — Estilos del canvas

**Archivos a modificar:**
- `jaraba_ai_agents/src/Form/AgentFlowForm.php` — Integrar widget React
- `jaraba_ai_agents/jaraba_ai_agents.libraries.yml` — Registrar JS bundle

#### G108-2: Cross-agent Collaboration (10-15h)

**Descripción extensa:** Patrones avanzados donde múltiples agentes colaboran en un mismo flujo con transferencia de contexto. Ejemplo: el StorytellingAgent genera el copy del producto, el SmartMarketingAgent lo adapta para SEO, y el CustomerExperienceAgent personaliza el tono para el segmento del cliente. Requiere extender `AgentOrchestrator.php` con un método `orchestrateChain()` que mantenga un contexto compartido entre agentes.

**Archivos a modificar:**
- `jaraba_ai_agents/src/Service/AgentOrchestrator.php` — Añadir `orchestrateChain()`
- `jaraba_ai_agents/src/Service/WorkflowExecutorService.php` — Nodo `multi_agent`

#### G108-3: Agent Benchmarking Dashboard (5-10h)

**Descripción extensa:** Dashboard comparativo de rendimiento entre agentes con métricas de latencia, coste, calidad, tasa de éxito. Extender el `DashboardController` existente con una pestaña adicional.

**Archivos a modificar:**
- `jaraba_ai_agents/src/Controller/DashboardController.php` — Sección benchmarks
- `jaraba_ai_agents/scss/` — Parcial `_benchmarks.scss`

### 6.2 Verificación

- [ ] Visual Flow Builder renderiza correctamente en el formulario de AgentFlow
- [ ] Los flujos creados visualmente se ejecutan igual que los definidos en JSON
- [ ] Cross-agent collaboration mantiene contexto entre agentes
- [ ] Dashboard de benchmarks muestra métricas reales de ejecuciones
- [ ] SCSS solo usa `var(--ej-*)` sin variables SCSS locales
- [ ] Todos los textos usan `{% trans %}` o `$this->t()`
- [ ] `lando drush cr` y verificación visual en navegador

---

## 7. GAPS — FASE 2: PWA Mobile (Doc 109)

**Estado:** 70% implementado en `ecosistema_jaraba_core` + `web/sw.js`
**Estrategia:** Extender funcionalidad PWA en core, posible módulo `jaraba_pwa`

### 7.1 Gaps a Implementar

#### G109-1: Mobile UI Components (20-25h)

**Descripción extensa:** Componentes optimizados para interacción tactil: bottom navigation persistente con iconos SVG duotone para las secciones principales, pull-to-refresh en listados, swipe cards para acciones rápidas (aprobar/rechazar), y badges de notificación. Estos componentes se implementan como parciales Twig reutilizables con su SCSS correspondiente, activados solo cuando se detecta viewport móvil.

#### G109-2: Offline Data Sync con IndexedDB (20-30h)

**Descripción extensa:** Schema IndexedDB para almacenar datos operativos offline (productos pendientes, pedidos draft, artículos KB descargados) con conflict resolution strategy (last-write-wins para datos simples, merge para listas). Entidad `PendingSyncAction` para la cola de acciones que se sincronizan al recuperar conexión vía Background Sync API.

#### G109-3: Push Notifications Backend (15-20h)

**Descripción extensa:** Backend FCM integration con entidad `PushSubscription` por usuario/dispositivo. Endpoints para suscripción/desuscripción, y servicio de envío de notificaciones push segmentadas por tenant, vertical y tipo de evento.

### 7.2 Verificación

- [ ] Bottom navigation visible solo en móvil (< 768px)
- [ ] Pull-to-refresh funcional en listados principales
- [ ] IndexedDB almacena datos offline correctamente
- [ ] Background Sync procesa cola de acciones al recuperar conexión
- [ ] Push notifications llegan a dispositivos suscritos
- [ ] SCSS mobile-first sin variables SCSS locales
- [ ] Todos los textos traducibles

---

## 8. GAPS — FASE 3: Onboarding Product-Led (Doc 110)

**Estado:** 95% implementado
**Estrategia:** Extensiones menores en `jaraba_journey`

### 8.1 Gaps a Implementar

Los 3 gaps (A/B testing, video walkthroughs, gamificación badges) son mejoras incrementales de baja prioridad que no requieren módulo nuevo ni entidades nuevas. Se implementan como extensiones del `jaraba_journey` existente.

### 8.2 Verificación

- [ ] A/B testing muestra variante correcta por cohorte
- [ ] Videos se cargan sin bloquear el flujo de onboarding
- [ ] Badges se otorgan al completar hitos

---

## 9. GAPS — FASE 4: Usage-Based Pricing (Doc 111)

**Estado:** 85% implementado en `ecosistema_jaraba_core`
**Estrategia:** Extender `TenantMeteringService` y añadir frontend

### 9.1 Gaps a Implementar

#### G111-1: Dashboard de Uso para Tenants (15-20h)

**Descripción extensa:** Interfaz frontend en `/mi-cuenta/uso` que muestra al tenant admin su consumo actual vs límites del plan, con gráficos de tendencia (últimos 30 días), proyección de fin de periodo, alertas de umbral, y acciones rápidas para upgrade de plan. Usa datos del `TenantMeteringService` existente.

**Archivos a crear:**
- `ecosistema_jaraba_core/src/Controller/UsageDashboardController.php`
- Template `usage-dashboard.html.twig` con parciales `_usage-meter.html.twig`, `_usage-trend-chart.html.twig`
- SCSS parcial `_usage-dashboard.scss`

**Archivos a modificar:**
- `ecosistema_jaraba_core.routing.yml` — Ruta `/mi-cuenta/uso`
- `ecosistema_jaraba_core/scss/main.scss` — Import del parcial
- `ecosistema_jaraba_theme.theme` — Theme suggestion para `page--mi-cuenta--uso`

#### G111-2: Custom Pricing Rules (10-15h)

**Descripción extensa:** Entidad `PricingRule` como ContentEntity para definir reglas de precio personalizadas por tenant más allá de los planes estándar (descuentos por volumen, pricing especial para enterprise, créditos prepago).

### 9.2 Verificación

- [ ] Dashboard de uso muestra datos reales del `TenantMeteringService`
- [ ] Gráficos de tendencia reflejan los últimos 30 días
- [ ] Alertas de umbral al 80% y 100% visibles
- [ ] PricingRule entity accesible en `/admin/content/pricing-rules`
- [ ] SCSS: solo `var(--ej-*)`, compilado con Dart Sass

---

## 10. GAPS — FASE 5: Integration Marketplace (Doc 112)

**Estado:** 40% implementado — **GAP CRÍTICO**
**Estrategia:** Módulo NUEVO `jaraba_integrations`

### 10.1 Entidades Nuevas Necesarias

| Entidad | Tipo | Base Table | Campos Clave |
|---------|------|-----------|-------------|
| `Connector` | ContentEntity | `connector` | name, description, category, auth_type, config_schema, icon, documentation_url, is_active, tenant_id |
| `ConnectorInstallation` | ContentEntity | `connector_installation` | connector_id, tenant_id, credentials (encrypted), status, last_sync, error_log |
| `OauthClient` | ContentEntity | `oauth_client` | client_id, client_secret (hashed), redirect_uris, scopes, tenant_id, is_active |
| `WebhookSubscription` | ContentEntity | `webhook_subscription` | tenant_id, target_url, events, signing_secret, retry_policy, status, delivery_log |

### 10.2 Services Nuevos

| Service | Métodos Clave | Descripción |
|---------|---------------|-------------|
| `ConnectorRegistryService` | `getAvailable()`, `getByCategory()`, `search()` | Catálogo de conectores disponibles con filtros por categoría y búsqueda |
| `ConnectorInstallerService` | `install()`, `uninstall()`, `configure()`, `test()` | Instalación 1-click con validación de credenciales y test de conexión |
| `OauthServerService` | `authorize()`, `token()`, `introspect()`, `revoke()` | Servidor OAuth2 para autorización de apps terceras |
| `WebhookDispatcherService` | `dispatch()`, `retry()`, `verify()` | Envío de webhooks con retry exponencial y HMAC signing |
| `ConnectorHealthCheckService` | `checkAll()`, `checkOne()`, `getStatus()` | Verificación de salud de integraciones activas vía cron |

### 10.3 Frontend

**Ruta:** `/integraciones`
**Template:** `page--integraciones.html.twig` (layout limpio)
**Contenido:** `integration-dashboard.html.twig` con:
- Grid de conectores disponibles (catálogo)
- Conectores instalados con estado
- Métricas de uso por integración
- Botón "+ Instalar" → slide-panel

**Parciales nuevos:**
- `_connector-card.html.twig` — Card premium de conector con icono, categoría, estado, acciones
- `_connector-detail.html.twig` — Detalle con configuración, logs, métricas
- `_integration-empty-state.html.twig` — Estado vacío con CTA

### 10.4 Verificación

- [ ] Entidades Connector, ConnectorInstallation, OauthClient, WebhookSubscription en `/admin/content/`
- [ ] Settings en `/admin/structure/integrations`
- [ ] Frontend `/integraciones` con layout limpio full-width
- [ ] Install/uninstall funcional vía slide-panel
- [ ] OAuth2 flow completo (authorize → token → introspect)
- [ ] Webhook dispatch con HMAC y retry
- [ ] Health check en cron
- [ ] SCSS: solo `var(--ej-*)`, package.json con build
- [ ] Todos los textos `{% trans %}` o `$this->t()`
- [ ] Iconos SVG outline + duotone en `images/icons/business/`

---

## 11. GAPS — FASE 6: Customer Success Proactivo (Doc 113)

**Estado:** 35% implementado — **GAP CRÍTICO**
**Estrategia:** Módulo NUEVO `jaraba_customer_success`

### 11.1 Entidades Nuevas Necesarias

| Entidad | Tipo | Base Table | Campos Clave |
|---------|------|-----------|-------------|
| `CustomerHealth` | ContentEntity | `customer_health` | tenant_id, overall_score (0-100), category (healthy/neutral/at_risk/critical), engagement_score, adoption_score, satisfaction_score, support_score, growth_score, calculated_at |
| `ChurnPrediction` | ContentEntity | `churn_prediction` | tenant_id, risk_score (0-100), confidence, risk_factors (JSON), recommended_actions (JSON), predicted_churn_date, model_version |
| `CsPlaybook` | ContentEntity | `cs_playbook` | name, trigger_condition, actions (JSON), target_segment, is_active, execution_count, success_rate |
| `ExpansionSignal` | ContentEntity | `expansion_signal` | tenant_id, signal_type, signal_data (JSON), confidence, detected_at, status (new/reviewed/actioned) |

### 11.2 Services Nuevos

| Service | Métodos Clave | Descripción |
|---------|---------------|-------------|
| `HealthScoreCalculatorService` | `calculate()`, `getHistory()`, `getByCategory()` | Calcula health score 0-100 con 5 dimensiones ponderadas. Se ejecuta diariamente en `hook_cron`. Usa datos de `TenantMeteringService`, `AnalyticsService` y `NpsSurveyService` |
| `ChurnPredictionService` | `predict()`, `getRiskFactors()`, `getAtRisk()` | Predicción de churn usando `@ai.provider` (Claude) con datos históricos de engagement, uso y satisfacción. Genera risk factors explicables |
| `PlaybookExecutorService` | `execute()`, `evaluateTriggers()`, `getActivePlaybooks()` | Motor de ejecución de playbooks automatizados. Evalúa triggers en cron y ejecuta secuencias de acciones (email, in-app message, task creation) |
| `EngagementScoringService` | `getDAUMAU()`, `getFeatureAdoption()`, `getTimeInApp()` | Calcula métricas de engagement por tenant: DAU/MAU ratio, feature usage vs available, session duration |
| `NpsSurveyService` | `send()`, `collect()`, `getScore()`, `getTrend()` | Sistema de encuestas NPS in-app con tracking longitudinal |
| `LifecycleStageService` | `getCurrentStage()`, `transition()`, `getHistory()` | Gestión de etapas del ciclo de vida: trial → onboarding → adoption → expansion → renewal |

### 11.3 Frontend

**Ruta:** `/customer-success`
**Template:** `page--customer-success.html.twig` (layout limpio)
**Contenido:** `customer-success-dashboard.html.twig` con:
- Overview cards: Total tenants, Healthy %, At Risk %, Critical %
- Health score distribution chart
- Churn risk panel con tenants en riesgo
- Playbook execution timeline
- Expansion signals pipeline
- NPS trend chart

**Parciales nuevos:**
- `_health-score-card.html.twig` — Gauge visual con score y categoría
- `_churn-risk-panel.html.twig` — Panel de tenants en riesgo con acciones
- `_playbook-timeline.html.twig` — Timeline de ejecuciones de playbook
- `_cs-empty-state.html.twig` — Estado vacío con setup wizard

### 11.4 Verificación

- [ ] Entidades CustomerHealth, ChurnPrediction, CsPlaybook, ExpansionSignal en `/admin/content/`
- [ ] Settings en `/admin/structure/customer-success`
- [ ] Frontend `/customer-success` con layout limpio full-width
- [ ] Health score se calcula diariamente por cron
- [ ] Churn prediction usa `@ai.provider` (no HTTP directo)
- [ ] Playbooks se ejecutan automáticamente al cumplir triggers
- [ ] NPS surveys aparecen in-app según configuración
- [ ] SCSS: solo `var(--ej-*)`, package.json con build
- [ ] Todos los textos traducibles
- [ ] Iconos SVG outline + duotone

---

## 12. GAPS — FASE 7: Knowledge Base & Self-Service (Doc 114)

**Estado:** 85% implementado en `jaraba_tenant_knowledge` + `jaraba_rag`
**Estrategia:** Extender módulos existentes

### 12.1 Gap Principal: Public Help Center (G114-1)

**Descripción extensa:** Portal público en `/ayuda` con diseño premium, búsqueda semántica usando el pipeline RAG existente de `jaraba_rag`, categorías navegables, artículos con breadcrumbs, y widget de FAQ Bot contextual. Se implementa como una extensión de `jaraba_tenant_knowledge` con nuevas rutas públicas y templates.

**Archivos a crear:**
- `jaraba_tenant_knowledge/src/Controller/HelpCenterController.php`
- Templates: `help-center.html.twig`, `_kb-article-card.html.twig`, `_kb-search-bar.html.twig`
- SCSS: `_help-center.scss`

**Archivos a modificar:**
- `jaraba_tenant_knowledge.routing.yml` — Rutas públicas `/ayuda`, `/ayuda/{category}`, `/ayuda/articulo/{article}`
- `ecosistema_jaraba_theme.theme` — Theme suggestion para `page--ayuda`

### 12.2 Verificación

- [ ] Help Center accesible en `/ayuda` sin autenticación
- [ ] Búsqueda semántica funcional con Qdrant
- [ ] Artículos traducibles (entity translatable)
- [ ] FAQ Bot contextual con grounding estricto
- [ ] Layout limpio sin regiones de admin

---

## 13. GAPS — FASE 8: Security & Compliance (Doc 115)

**Estado:** 75% implementado
**Estrategia:** Extender `ecosistema_jaraba_core`, posible módulo `jaraba_security_compliance`

### 13.1 Gap Principal: Compliance Dashboard (G115-1)

**Descripción extensa:** Dashboard en `/admin/seguridad` que muestra el estado de cumplimiento de controles SOC 2, ISO 27001 y ENS. Incluye una checklist interactiva de controles con estados (compliant, non-compliant, in-progress, not-applicable), findings abiertos con severidad y fecha límite, y evaluaciones periódicas con historial. Esto permite al equipo de seguridad trackear el progreso hacia la certificación.

### 13.2 Verificación

- [ ] Entidades AuditLog, SecurityPolicy, ComplianceAssessment en `/admin/content/`
- [ ] Dashboard de compliance en `/admin/seguridad`
- [ ] Audit log inmutable (sin delete permission)
- [ ] Data retention policies ejecutándose en cron

---

## 14. GAPS — FASE 9: Advanced Analytics & BI (Doc 116)

**Estado:** 80% implementado en `jaraba_analytics` + `jaraba_heatmap` + `jaraba_pixels`
**Estrategia:** Extender `jaraba_analytics`

### 14.1 Gaps Principales

#### G116-1 + G116-2: Cohort Analysis + Funnel Tracking (30-40h)

**Descripción extensa:** Sistema de análisis por cohortes que agrupa tenants/usuarios por fecha de registro y trackea retención, engagement y conversión a lo largo del tiempo. Visualización con retention matrix (heat map de retención por cohorte/periodo). Los funnels permiten definir secuencias de eventos (ej: registro → completar perfil → primera aplicación → entrevista) y visualizar drop-off entre etapas.

#### G116-3: Custom Report Builder (20-30h)

**Descripción extensa:** Entidad `CustomReport` que almacena la definición de un informe personalizado (métricas seleccionadas, dimensiones, filtros, tipo de visualización). Frontend con interfaz drag-drop para construir informes, previsualización en tiempo real, y guardado para reutilización.

### 14.2 Verificación

- [ ] Cohort analysis muestra retention matrix con datos reales
- [ ] Funnel tracking visualiza drop-off entre etapas
- [ ] Custom Report Builder permite crear informes sin código
- [ ] Scheduled Reports se envían por email en frecuencia configurada
- [ ] Export CSV/Excel/PDF funcional

---

## 15. GAPS — FASE 10: White-Label & Reseller (Doc 117)

**Estado:** 70% implementado en `jaraba_theming` + tema
**Estrategia:** Extender `jaraba_theming` + posible `jaraba_whitelabel`

### 15.1 Gaps Principales

#### G117-1: Custom Domain Automation (25-35h)

**Descripción extensa:** Automatización del proceso de dominio custom: el partner registra su dominio en el formulario existente (`TenantDomainSettingsForm`), el sistema genera un registro CNAME que el partner configura en su DNS provider, un cron verifica la propagación DNS, y al confirmar genera automáticamente un certificado SSL con Let's Encrypt vía Caddy Server. La entidad `CustomDomain` trackea el estado (pending_dns → dns_verified → ssl_provisioning → active).

#### G117-2: White-Label Email Templates MJML (20-25h)

**Descripción extensa:** 9 templates de email predefinidos (welcome, password_reset, order_confirmation, order_shipped, invoice, job_application, job_match, mentoring_reminder, certificate) implementados en MJML con variables Handlebars para branding por tenant (logo, colores, footer). Se integran con `jaraba_email` existente.

### 15.2 Verificación

- [ ] Custom domain flow completo: registro → DNS → SSL → activo
- [ ] Email templates renderizados con branding del tenant
- [ ] Reseller portal funcional en `/partner-portal`
- [ ] PDF generation con branding (Puppeteer)
- [ ] Font customization funcional

---

## 16. Inventario Consolidado de Entidades

### 16.1 Entidades ya existentes (NO re-implementar)

| Entidad | Módulo | Tipo | Tabla |
|---------|--------|------|-------|
| AIAgent | ecosistema_jaraba_core | ConfigEntity | config |
| AIWorkflow | jaraba_ai_agents | ContentEntity | ai_workflow |
| AIUsageLog | jaraba_ai_agents | ContentEntity | ai_usage_log |
| PendingApproval | jaraba_ai_agents | ContentEntity | pending_approval |
| TenantFaq | jaraba_tenant_knowledge | ContentEntity | tenant_faq |
| TenantPolicy | jaraba_tenant_knowledge | ContentEntity | tenant_policy |
| TenantDocument | jaraba_tenant_knowledge | ContentEntity | tenant_document |
| TenantProductEnrichment | jaraba_tenant_knowledge | ContentEntity | tenant_product_enrichment |
| AnalyticsEvent | jaraba_analytics | ContentEntity | analytics_event |
| ConsentRecord | jaraba_analytics | ContentEntity | consent_record |
| TenantThemeConfig | jaraba_theming | ContentEntity | tenant_theme_config |
| ImpersonationAuditLog | ecosistema_jaraba_core | ContentEntity | impersonation_audit_log |
| AlertRule | ecosistema_jaraba_core | ContentEntity | alert_rule |
| Tenant | ecosistema_jaraba_core | ContentEntity | tenant |
| Feature | ecosistema_jaraba_core | ConfigEntity | config |

### 16.2 Entidades NUEVAS a crear (gaps)

| Entidad | Módulo | Tipo | Tabla | Gap |
|---------|--------|------|-------|-----|
| Connector | jaraba_integrations | ContentEntity | connector | G112-2 |
| ConnectorInstallation | jaraba_integrations | ContentEntity | connector_installation | G112-3 |
| OauthClient | jaraba_integrations | ContentEntity | oauth_client | G112-4 |
| WebhookSubscription | jaraba_integrations | ContentEntity | webhook_subscription | G112-5 |
| CustomerHealth | jaraba_customer_success | ContentEntity | customer_health | G113-2 |
| ChurnPrediction | jaraba_customer_success | ContentEntity | churn_prediction | G113-3 |
| CsPlaybook | jaraba_customer_success | ContentEntity | cs_playbook | G113-4 |
| ExpansionSignal | jaraba_customer_success | ContentEntity | expansion_signal | G113-5 |
| PricingRule | ecosistema_jaraba_core | ContentEntity | pricing_rule | G111-2 |
| CustomReport | jaraba_analytics | ContentEntity | custom_report | G116-3 |
| ScheduledReport | jaraba_analytics | ContentEntity | scheduled_report | G116-4 |
| CustomDomain | jaraba_theming | ContentEntity | custom_domain | G117-1 |
| PushSubscription | ecosistema_jaraba_core | ContentEntity | push_subscription | G109-3 |
| PendingSyncAction | ecosistema_jaraba_core | ContentEntity | pending_sync_action | G109-5 |
| AuditLog | jaraba_security_compliance | ContentEntity | audit_log | G115-2 |
| SecurityPolicy | jaraba_security_compliance | ContentEntity | security_policy | G115-3 |
| ComplianceAssessment | jaraba_security_compliance | ContentEntity | compliance_assessment | G115-4 |

**Total nuevas: 17 entidades**

---

## 17. Inventario Consolidado de Services

### 17.1 Services nuevos a crear

| Service ID | Clase | Módulo | Gap |
|-----------|-------|--------|-----|
| `jaraba_integrations.connector_registry` | ConnectorRegistryService | jaraba_integrations | G112-2 |
| `jaraba_integrations.connector_installer` | ConnectorInstallerService | jaraba_integrations | G112-3 |
| `jaraba_integrations.oauth_server` | OauthServerService | jaraba_integrations | G112-4 |
| `jaraba_integrations.webhook_dispatcher` | WebhookDispatcherService | jaraba_integrations | G112-5 |
| `jaraba_integrations.health_check` | ConnectorHealthCheckService | jaraba_integrations | G112-6 |
| `jaraba_customer_success.health_calculator` | HealthScoreCalculatorService | jaraba_customer_success | G113-2 |
| `jaraba_customer_success.churn_prediction` | ChurnPredictionService | jaraba_customer_success | G113-3 |
| `jaraba_customer_success.playbook_executor` | PlaybookExecutorService | jaraba_customer_success | G113-4 |
| `jaraba_customer_success.engagement_scoring` | EngagementScoringService | jaraba_customer_success | G113-9 |
| `jaraba_customer_success.nps_survey` | NpsSurveyService | jaraba_customer_success | G113-6 |
| `jaraba_customer_success.lifecycle_stage` | LifecycleStageService | jaraba_customer_success | G113-8 |

**Total nuevos: 11 services**

---

## 18. Inventario Consolidado de Endpoints REST API

### 18.1 Endpoints nuevos

| Método | Path | Módulo | Descripción |
|--------|------|--------|-------------|
| GET | `/api/v1/connectors` | jaraba_integrations | Catálogo de conectores disponibles |
| POST | `/api/v1/connectors/{id}/install` | jaraba_integrations | Instalar conector para tenant |
| DELETE | `/api/v1/connectors/{id}/uninstall` | jaraba_integrations | Desinstalar conector |
| GET | `/api/v1/integrations` | jaraba_integrations | Integraciones activas del tenant |
| POST | `/api/v1/oauth/authorize` | jaraba_integrations | OAuth2 authorization |
| POST | `/api/v1/oauth/token` | jaraba_integrations | OAuth2 token exchange |
| GET | `/api/v1/health-scores` | jaraba_customer_success | Health scores de tenants |
| GET | `/api/v1/health-scores/{tenant_id}` | jaraba_customer_success | Health score de un tenant |
| GET | `/api/v1/churn-predictions` | jaraba_customer_success | Predicciones de churn |
| GET | `/api/v1/expansion-signals` | jaraba_customer_success | Señales de expansión |
| POST | `/api/v1/playbooks/{id}/execute` | jaraba_customer_success | Ejecutar playbook manualmente |

---

## 19. Paleta de Colores y Design Tokens

Los módulos de Platform Services usan **exclusivamente** la paleta oficial verificada en `_variables.scss` y `JarabaTwigExtension.php`:

### 19.1 Paleta Oficial Jaraba (verificada en código)

| Color | Nombre | Hex | Uso en Platform Services |
|-------|--------|-----|--------------------------|
| ■ | Azul Corporativo | `#233D63` | Headers, textos principales, fondos oscuros |
| ■ | Turquesa Innovación | `#00A9A5` | CTAs secundarios, estados success, badges |
| ■ | Naranja Impulso | `#FF8C42` | CTAs primarios, acciones, alertas |
| ■ | Verde Oliva | `#556B2F` | AgroConecta vertical, estados natural |
| ■ | Azul Profundo | `#003366` | Fondos premium, autoridad |
| ■ | Azul Verdoso | `#2B7A78` | Conexión, balance |
| ■ | Verde Oliva Oscuro | `#3E4E23` | AgroConecta intenso |

### 19.2 Tokens Semánticos por Módulo

| Módulo | Color Primario | Color Acento | Uso |
|--------|---------------|-------------|-----|
| Integration Marketplace | `--ej-color-corporate` (#233D63) | `--ej-color-impulse` (#FF8C42) | Conectores, apps |
| Customer Success | `--ej-color-innovation` (#00A9A5) | `--ej-color-impulse` (#FF8C42) | Health verde, risk naranja |
| Knowledge Base | `--ej-color-corporate` (#233D63) | `--ej-color-innovation` (#00A9A5) | Artículos, búsqueda |
| Security Compliance | `--ej-color-corporate` (#233D63) | `var(--ej-color-danger, #EF4444)` | Compliance, alertas |
| Analytics BI | `--ej-color-corporate` (#233D63) | `var(--ej-color-info, #3B82F6)` | Gráficos, datos |
| White-Label | Dinámico por tenant | Dinámico por tenant | Branding personalizado |

---

## 20. Patrón de Iconos SVG

**Iconos nuevos necesarios para gaps de Platform Services:**

| Icono | Categoría | Variantes | Uso |
|-------|-----------|-----------|-----|
| `connector` | business | outline + duotone | Integration Marketplace |
| `plugin` | business | outline + duotone | Conectores instalables |
| `webhook` | business | outline + duotone | Webhooks |
| `health-score` | analytics | outline + duotone | Customer Success |
| `churn-risk` | analytics | outline + duotone | Predicción de churn |
| `playbook` | business | outline + duotone | Playbooks automatizados |
| `nps` | analytics | outline + duotone | Encuestas NPS |
| `help-center` | ui | outline + duotone | Knowledge Base público |
| `compliance` | business | outline + duotone | Security Compliance |
| `report-builder` | analytics | outline + duotone | Custom Report Builder |
| `cohort` | analytics | outline + duotone | Análisis de cohortes |
| `funnel` | analytics | outline + duotone | Funnel tracking |
| `domain` | business | outline + duotone | Custom domains |
| `reseller` | business | outline + duotone | Partner/Reseller portal |

**Ubicación:** `ecosistema_jaraba_core/images/icons/{category}/`

**Estructura de cada SVG duotone:**
```svg
<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <!-- Capa de fondo (opacity 0.3) -->
  <path d="..." fill="currentColor" opacity="0.3"/>
  <!-- Capa principal (stroke o fill sólido) -->
  <path d="..." stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

---

## 21. Orden de Implementación Global y Dependencias

### 21.1 Prioridades Revisadas (Post-Auditoría)

```
PRIORIDAD 0 (Gaps Críticos — Implementar Primero):
├── FASE 5: Integration Marketplace (jaraba_integrations) — 215-295h
│   └── Sin dependencias de otros gaps
├── FASE 6: Customer Success (jaraba_customer_success) — 190-270h
│   └── Depende de: ecosistema_jaraba_core (metering, analytics)
│
PRIORIDAD 2 (Extensiones — En Paralelo):
├── FASE 4: Usage Dashboard + PricingRule — 30-45h
├── FASE 7: Public Help Center — 40-55h
├── FASE 8: Compliance Dashboard — 50-80h
├── FASE 9: Cohorts + Funnels + Report Builder — 65-90h
├── FASE 10: Domain Automation + Email Templates — 90-130h
├── FASE 2: Mobile UI + Offline Sync — 65-90h
├── FASE 1: Visual Flow Builder — 40-60h
│
PRIORIDAD 3 (Nice-to-Have):
├── FASE 3: A/B Testing Onboarding + Badges — 15-25h
└── Subgaps menores (anomaly detection, font customization, etc.)
```

### 21.2 Dependencias entre Gaps

```
G112 (Integrations) ──── Sin dependencias (EMPEZAR AQUÍ)
G113 (Customer Success) ── Depende de metering (ya existe)
G111 (Usage Dashboard) ── Independiente
G114 (Help Center) ────── Depende de jaraba_rag (ya existe)
G115 (Compliance) ─────── Independiente
G116 (Analytics BI) ───── Independiente
G117 (White-Label) ────── Depende de jaraba_email (ya existe)
G109 (PWA) ────────────── Independiente
G108 (Visual Builder) ─── Depende de jaraba_ai_agents (ya existe)
G110 (Onboarding) ─────── Depende de jaraba_journey (ya existe)
```

---

## 22. Estimación Total de Esfuerzo Revisada

| Prioridad | Fases | Horas (min-max) | % del Total |
|-----------|-------|-----------------|-------------|
| 🔴 P0 (Crítico) | F5 + F6 | 405-565h | 50% |
| 🟡 P2 (Extensiones) | F1, F2, F4, F7, F8, F9, F10 | 380-550h | 42% |
| 🟢 P3 (Nice-to-Have) | F3 + sub-gaps | 15-25h | 3% |
| **TOTAL** | **10 fases** | **~800-1,140h** | **100%** |

**Comparación con v1.0:**
- v1.0 estimaba: 2,485-3,340h (desde cero)
- v2.0 estima: 800-1,140h (solo gaps)
- **Ahorro: ~66% (1,685-2,200h ahorradas)**

---

## 23. Checklist de Verificación Pre-Implementación

Antes de iniciar la implementación de cualquier gap, verificar:

### SCSS y Theming
- [ ] El módulo tiene `package.json` con scripts de build
- [ ] SCSS solo usa `var(--ej-*, $fallback)` — CERO variables `$ej-*` locales
- [ ] Compilación con Dart Sass `^1.80.0` (`@use 'sass:color'`, NO `darken()`)
- [ ] Premium card pattern con `cubic-bezier(0.175, 0.885, 0.32, 1.275)` y glassmorphism
- [ ] Los valores configurables (colores, textos) vienen de theme settings, no hardcodeados

### i18n
- [ ] Todo texto visible usa `$this->t()`, `{% trans %}`, o `Drupal.t()`
- [ ] Placeholders, aria-labels, tooltips también traducibles
- [ ] Abreviaturas en español completo
- [ ] Acrónimos técnicos con glosario visible

### Templates Twig
- [ ] Páginas frontend limpias sin `page.content` ni bloques de Drupal
- [ ] Usa parciales existentes del tema (`_header`, `_footer`, `_copilot-fab`)
- [ ] Si el componente se usa 2+ veces → crear parcial
- [ ] `{% include ... only %}` siempre con keyword `only`
- [ ] Layout full-width, mobile-first, sin sidebar admin para tenants

### Entidades
- [ ] ContentEntity (no ConfigEntity) para datos de usuario
- [ ] 4 archivos YAML: routing, links.menu, links.task, links.action
- [ ] `field_ui_base_route` para Field UI
- [ ] `views_data` handler para Views integration
- [ ] Navegación en `/admin/structure` Y `/admin/content`
- [ ] AccessControlHandler con aislamiento por tenant

### Frontend
- [ ] Body classes via `hook_preprocess_html()` (NO en template)
- [ ] CRUD en slide-panel (no navegación a otra página)
- [ ] Template suggestion registrado en `theme_suggestions_page_alter()`
- [ ] Librería registrada en `.libraries.yml` con dependencias
- [ ] JavaScript con `Drupal.behaviors` y `once()`

### Seguridad
- [ ] API Keys en variables de entorno
- [ ] Rate limiting en endpoints API
- [ ] HMAC en webhooks
- [ ] Filtro por `tenant_id` en TODAS las queries
- [ ] Qdrant: `must` (AND) para tenant_id
- [ ] Parámetros de ruta con regex (`'\d+'`)

### Código
- [ ] Comentarios en español con 3 dimensiones (Estructura, Lógica, Sintaxis)
- [ ] AI vía `@ai.provider` (no HTTP directo)
- [ ] Automatizaciones vía hooks en `.module`
- [ ] Iconos SVG en outline + duotone

---

## 24. Registro de Cambios

| Fecha | Versión | Autor | Descripción |
|-------|---------|-------|-------------|
| 2026-02-10 | 1.0.0 | Claude Opus 4.6 | Plan inicial desde cero (sin auditoría de código) |
| 2026-02-11 | 2.0.0 | Claude Opus 4.6 | Auditoría exhaustiva del código existente, gap analysis detallado, estimaciones revisadas con 66% de ahorro. Incluye inventarios consolidados y checklist de verificación |

---

## Referencias Cruzadas

| Documento | Ubicación | Descripción |
|-----------|-----------|-------------|
| Directrices del Proyecto | `docs/00_DIRECTRICES_PROYECTO.md` | Directrices maestras v6.2 |
| Arquitectura Maestra | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` | Arquitectura global del SaaS |
| Índice General | `docs/00_INDICE_GENERAL.md` | Índice de toda la documentación |
| Arquitectura Theming | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | Federated Design Tokens v2.1 |
| Plan v1.0 | `docs/implementacion/20260210-Plan_Implementacion_Platform_Services_v1.md` | Plan original sin auditoría |
| Workflow i18n | `.agent/workflows/i18n-traducciones.md` | Directrices de traducción |
| Workflow SCSS | `.agent/workflows/scss-estilos.md` | Directrices de estilos y compilación |
| Workflow Frontend | `.agent/workflows/frontend-page-pattern.md` | Patrón de página limpia |
| Workflow Slide-Panel | `.agent/workflows/slide-panel-modales.md` | Patrón de modales CRUD |
| Workflow Custom Modules | `.agent/workflows/drupal-custom-modules.md` | Patrón de Content Entities |
| Workflow Premium Cards | `.agent/workflows/premium-cards-pattern.md` | Patrón de cards glassmorphism |
| Workflow AI Integration | `.agent/workflows/ai-integration.md` | Integración con @ai.provider |
| Workflow ECA/Hooks | `.agent/workflows/drupal-eca-hooks.md` | Automatizaciones con hooks |
| Spec f108 | `docs/tecnicos/20260117i-108_Platform_AI_Agent_Flows_v1_Claude.md` | AI Agent Flows |
| Spec f109 | `docs/tecnicos/20260117i-109_Platform_PWA_Mobile_v1_Claude.md` | PWA Mobile |
| Spec f110 | `docs/tecnicos/20260117i-110_Platform_Onboarding_ProductLed_v1_Claude.md` | Onboarding PLG |
| Spec f111 | `docs/tecnicos/20260117i-111_Platform_UsageBased_Pricing_v1_Claude.md` | Usage Pricing |
| Spec f112 | `docs/tecnicos/20260117i-112_Platform_Integration_Marketplace_v1_Claude.md` | Integration Marketplace |
| Spec f113 | `docs/tecnicos/20260117i-113_Platform_Customer_Success_v1_CLaude.md` | Customer Success |
| Spec f114 | `docs/tecnicos/20260117i-114_Platform_Knowledge_Base_v1_Claude.md` | Knowledge Base |
| Spec f115 | `docs/tecnicos/20260117i-115_Platform_Security_Compliance_v1_Claude.md` | Security Compliance |
| Spec f116 | `docs/tecnicos/20260117i-116_Platform_Advanced_Analytics_v1_Claude.md` | Advanced Analytics |
| Spec f117 | `docs/tecnicos/20260117i-117_Platform_WhiteLabel_v1_Claude.md` | White-Label |

---

> **Nota:** Este documento es la fuente de verdad para la implementación de Platform Services.
> La v1.0 queda como referencia histórica pero esta v2.0 tiene precedencia al incluir la auditoría real del código.
> Cualquier desviación de las directrices aquí documentadas debe justificarse por escrito.
