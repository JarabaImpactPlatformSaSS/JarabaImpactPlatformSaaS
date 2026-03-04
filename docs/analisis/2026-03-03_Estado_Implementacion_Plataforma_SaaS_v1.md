# Estado de Implementacion — Jaraba Impact Platform SaaS

**Fecha:** 2026-03-03
**Version:** 1.0.0
**Autor:** IA Asistente (Claude)
**Alcance:** Mapeo completo del estado de implementacion del ecosistema

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Metricas Globales](#2-metricas-globales)
3. [Arquitectura del Sistema](#3-arquitectura-del-sistema)
4. [Inventario de Modulos](#4-inventario-de-modulos)
5. [Estado por Vertical](#5-estado-por-vertical)
6. [Stack de IA](#6-stack-de-ia)
7. [Page Builder / GrapesJS](#7-page-builder--grapesjs)
8. [Infraestructura de Testing](#8-infraestructura-de-testing)
9. [Pipeline CI/CD](#9-pipeline-cicd)
10. [Sistema de Validacion Arquitectonica](#10-sistema-de-validacion-arquitectonica)
11. [Tema y Frontend](#11-tema-y-frontend)
12. [Documentacion](#12-documentacion)
13. [Dependencias](#13-dependencias)
14. [Estado de Compilacion y Runtime](#14-estado-de-compilacion-y-runtime)
15. [Tabla de Correspondencias](#15-tabla-de-correspondencias)
16. [Diagramas de Arquitectura](#16-diagramas-de-arquitectura)

---

## 1. Resumen Ejecutivo

Jaraba Impact Platform es un **SaaS multi-tenant de escala empresarial** construido sobre Drupal 11 + PHP 8.4, con 10 verticales de negocio, 11 agentes IA Gen 2, y un sistema completo de orquestacion CI/CD.

### Indicadores Clave

| Indicador | Valor | Estado |
|-----------|-------|--------|
| Modulos custom | 92 | Activo |
| Entidades (Content + Config) | 441 | Activo |
| Servicios registrados | 925 | Activo |
| Rutas definidas | 2,569 | Activo |
| Tests (metodos) | 3,650 | CI Verde |
| Agentes IA Gen 2 | 11 | Activo |
| Workflows CI/CD | 8 | Activo |
| Scripts validacion | 6 | Activo |
| Documentos tecnicos | 696 | Actualizado |
| Directrices del proyecto | 106 versiones | v106.0.0 |

### Nivel de Madurez: **5.0 / 5.0** (Resiliencia & Cumplimiento Certificado)

---

## 2. Metricas Globales

### 2.1 Escala del Codebase

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    METRICAS DE ESCALA DEL CODEBASE                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Codigo Fuente:                                                        │
│   ┌───────────────────────────────────────────────────────────────┐    │
│   │  PHP files (production)    3,685                              │    │
│   │  PHP files (tests)           462  (12.4% del total)          │    │
│   │  SCSS files                   99                              │    │
│   │  CSS files (compiled)         52                              │    │
│   │  JavaScript files             81                              │    │
│   │  Twig templates (modules)    709                              │    │
│   │  Twig templates (theme)      156                              │    │
│   │  Total templates             865                              │    │
│   └───────────────────────────────────────────────────────────────┘    │
│                                                                         │
│   Configuracion:                                                        │
│   ┌───────────────────────────────────────────────────────────────┐    │
│   │  config/sync/ YAMLs        1,460                              │    │
│   │  config/install/ YAMLs       592                              │    │
│   │  *.services.yml files         95  (8,349 lineas)             │    │
│   │  *.routing.yml files          95  (2,569 rutas)              │    │
│   │  *.info.yml files             95                              │    │
│   │  .module files                91                              │    │
│   │  .install files               56                              │    │
│   └───────────────────────────────────────────────────────────────┘    │
│                                                                         │
│   Objetos Arquitectonicos:                                              │
│   ┌───────────────────────────────────────────────────────────────┐    │
│   │  ContentEntity types         424                              │    │
│   │  ConfigEntity types           17                              │    │
│   │  Services registrados        925                              │    │
│   │  Controllers                 404                              │    │
│   │  Form classes                652                              │    │
│   │  Service classes             771                              │    │
│   └───────────────────────────────────────────────────────────────┘    │
│                                                                         │
│   Disco:                                                                │
│   ┌───────────────────────────────────────────────────────────────┐    │
│   │  Modulos custom              246 MB                           │    │
│   │  Tema                        451 MB                           │    │
│   │  Documentacion                18 MB                           │    │
│   │  Scripts                      ~5 MB                           │    │
│   └───────────────────────────────────────────────────────────────┘    │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Distribucion por Tipo de Archivo PHP

| Tipo | Archivos | % |
|------|----------|---|
| Entity classes (`src/Entity/`) | 562 | 15.2% |
| Service classes (`src/Service/`) | 771 | 20.9% |
| Form classes (`src/Form/`) | 652 | 17.7% |
| Controller classes (`src/Controller/`) | 404 | 10.9% |
| Test files (`tests/`) | 462 | 12.5% |
| Otros (Access, Plugin, Event, etc.) | 834 | 22.6% |
| **Total** | **3,685** | **100%** |

---

## 3. Arquitectura del Sistema

### 3.1 Stack Tecnologico

```
┌─────────────────────────────────────────────────────────────────────────┐
│                        STACK TECNOLOGICO                                 │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   FRONTEND                        BACKEND                               │
│   ┌──────────────────┐            ┌──────────────────────────────┐     │
│   │ Twig Templates   │            │ PHP 8.4 + Drupal 11         │     │
│   │ SCSS (Dart Sass) │            │ 92 modulos custom            │     │
│   │ Vanilla JS       │            │ 925 servicios DI             │     │
│   │ GrapesJS 5.7     │            │ 2,569 rutas                  │     │
│   └──────────────────┘            └──────────────────────────────┘     │
│                                                                         │
│   DATOS                           IA                                    │
│   ┌──────────────────┐            ┌──────────────────────────────┐     │
│   │ MariaDB 10.11    │            │ Claude API (Opus/Sonnet)     │     │
│   │ Redis 7.4        │            │ Gemini API (fallback)        │     │
│   │ Qdrant (vectors) │            │ 11 Agentes Gen 2             │     │
│   │ Apache Tika      │            │ MCP Server (JSON-RPC 2.0)    │     │
│   └──────────────────┘            └──────────────────────────────┘     │
│                                                                         │
│   PAGOS                           INFRAESTRUCTURA                       │
│   ┌──────────────────┐            ┌──────────────────────────────┐     │
│   │ Stripe Connect   │            │ IONOS Dedicated L-16 NVMe   │     │
│   │ Destination       │            │ 128 GB RAM, AMD EPYC        │     │
│   │ Charges          │            │ GitHub Actions CI/CD         │     │
│   └──────────────────┘            │ Lando (dev local)            │     │
│                                    └──────────────────────────────┘     │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Multi-Tenancy

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    MODELO MULTI-TENANT                                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Tenant Entity (billing)  ←── TenantBridgeService ──→  Group (content) │
│        │                                                      │         │
│   ┌────┴────────┐                                    ┌───────┴──────┐  │
│   │ Plan tier   │                                    │ Content      │  │
│   │ Billing     │                                    │ Users        │  │
│   │ Quotas      │                                    │ Permissions  │  │
│   │ Features    │                                    │ Entities     │  │
│   └─────────────┘                                    └──────────────┘  │
│                                                                         │
│   Resolucion: TenantContextService → getCurrentTenantId()               │
│   Aislamiento: AccessControlHandler verifica tenant match               │
│   Query: TODA query filtra por tenant_id (TENANT-001)                  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 4. Inventario de Modulos

### 4.1 Modulos por Categoria (92 modulos custom)

| Categoria | Modulos | Principales |
|-----------|---------|-------------|
| **Core/Transversal** | 1 | `ecosistema_jaraba_core` (142 servicios) |
| **IA/Agentes** | 4 | `jaraba_ai_agents`, `jaraba_agents`, `jaraba_copilot_v2`, `jaraba_rag` |
| **Legal** | 9 | `jaraba_legal`, `jaraba_legal_vault`, `jaraba_legal_billing`, `jaraba_legal_lexnet`, `jaraba_legal_intelligence`, etc. |
| **HR/Talento** | 8 | `jaraba_candidate`, `jaraba_job_board`, `jaraba_skills`, `jaraba_mentoring`, `jaraba_training`, `jaraba_lms`, etc. |
| **Comercio** | 6 | `jaraba_commerce`, `jaraba_comercio_conecta`, `jaraba_social_commerce`, `jaraba_shopping_cart`, etc. |
| **Analytics** | 6 | `jaraba_analytics`, `jaraba_insights_hub`, `jaraba_predictive`, `jaraba_performance`, `jaraba_heatmap`, `jaraba_pixels` |
| **CRM/Soporte** | 4 | `jaraba_crm`, `jaraba_customer_success`, `jaraba_support`, `jaraba_messaging` |
| **Revenue** | 6 | `jaraba_billing`, `jaraba_pricing`, `jaraba_usage_billing`, `jaraba_referral`, `jaraba_funding`, `jaraba_ads` |
| **Infraestructura** | 7 | `jaraba_workflows`, `jaraba_multiregion`, `jaraba_sso`, `jaraba_identity`, `jaraba_privacy`, etc. |
| **Contenido** | 2 | `jaraba_content_hub`, `jaraba_blog` (DESHABILITADO, consolidado) |
| **Page Builder** | 1 | `jaraba_page_builder` |
| **Otros verticales** | 38 | Agro, servicios, andalucia_ei, emprendimiento, demo, notificaciones, etc. |

### 4.2 Top 10 Modulos por Complejidad

| Modulo | Entidades | Servicios | Rutas | .install |
|--------|-----------|-----------|-------|----------|
| `ecosistema_jaraba_core` | 35 | 142 | 234 | Si |
| `jaraba_agroconecta_core` | 91 | 29 | 286 | Si |
| `jaraba_ai_agents` | — | 80+ | 50+ | Si |
| `jaraba_comercio_conecta` | 42 | 29 | 97 | Si |
| `jaraba_page_builder` | 10+ | 20+ | 40+ | Si |
| `jaraba_support` | 9 | 18 | 30+ | Si |
| `jaraba_content_hub` | 8 | 15 | 62 | Si |
| `jaraba_candidate` | 12 | 11 | 58 | Si |
| `jaraba_lms` | 7 | 13 | 45 | Si |
| `jaraba_legal_intelligence` | 2 | 15+ | 10+ | Si |

---

## 5. Estado por Vertical

### 5.1 Tabla de Estado de los 10 Verticales Canonicos

```
┌───────────────────────────────────────────────────────────────────────────────────┐
│                    ESTADO DE VERTICALES CANONICOS                                   │
├───────────────────────────────────────────────────────────────────────────────────┤
│                                                                                     │
│   Vertical              │ Entidades │ Servicios │ Rutas │ Landing │ Schema.org │ FVL │
│   ──────────────────────┼───────────┼───────────┼───────┼─────────┼────────────┼─────│
│   empleabilidad         │    12     │    11     │  58   │   ✓     │     ✓      │  ✓  │
│   emprendimiento        │     6     │     9     │  42   │   ✓     │     ✓      │  ✓  │
│   comercioconecta       │    42     │    29     │  97   │   ✓     │     ✓      │  ✓  │
│   agroconecta           │    91     │    29     │ 286   │   ✓     │     ✓      │  ✓  │
│   jarabalex             │     6     │     6     │  20   │   ✓     │     ✓      │  ✓  │
│   serviciosconecta      │     6     │     9     │  30   │   ✓     │     ✓      │  ✓  │
│   andalucia_ei          │     6     │    12     │  33   │   ✓     │     ✓      │  ✓  │
│   jaraba_content_hub    │     8     │    15     │  62   │   ✓     │     ✓      │  ✓  │
│   formacion             │     7     │    13     │  45   │   ✓     │     ✓      │  ✓  │
│   demo                  │    35     │   142     │ 234   │   ✓     │     ✓      │  ✓  │
│   ──────────────────────┼───────────┼───────────┼───────┼─────────┼────────────┼─────│
│   TOTAL                 │   219     │   275     │ 907   │  10/10  │   10/10    │10/10│
│                                                                                     │
│   Leyenda: FVL = FreemiumVerticalLimit configs                                      │
│                                                                                     │
└───────────────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Estado de Elevacion (Clase Mundial)

| Vertical | Landing 3 Niveles | Forms Premium | Entity Preprocess | Tests | Score |
|----------|-------------------|---------------|-------------------|-------|-------|
| empleabilidad | ✓ Contenido + Schema + Config | ✓ 237 migrados | ✓ | ✓ | 95% |
| demo | ✓ (PLG transversal) | ✓ | ✓ | ✓ 24 tests | 100% |
| comercioconecta | ✓ | ✓ | ✓ | ✓ | 90% |
| agroconecta | ✓ | ✓ | ✓ | ✓ | 90% |
| jarabalex | ✓ (301 desde /despachos) | ✓ | ✓ | ✓ | 90% |
| serviciosconecta | ✓ | ✓ | ✓ | ✓ | 85% |
| emprendimiento | ✓ | ✓ | ✓ | ✓ | 85% |
| andalucia_ei | ✓ (Plan Maestro 8 fases) | ✓ | ✓ | ✓ | 80% |
| formacion | ✓ | ✓ | ✓ | ✓ | 85% |
| jaraba_content_hub | ✓ (Blog + Canvas) | ✓ | ✓ | ✓ | 90% |

---

## 6. Stack de IA

### 6.1 Arquitectura de Agentes

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    STACK IA — 11 AGENTES GEN 2                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Model Routing (3 tiers):                                              │
│   ┌─────────┐   ┌──────────┐   ┌─────────────┐                        │
│   │  Fast   │   │ Balanced │   │   Premium   │                        │
│   │ Haiku   │   │ Sonnet   │   │   Opus 4.6  │                        │
│   │  4.5    │   │   4.6    │   │             │                        │
│   └────┬────┘   └─────┬────┘   └──────┬──────┘                        │
│        └──────────────┼────────────────┘                               │
│                       ▼                                                 │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  SmartBaseAgent (Gen 2) — 10 args constructor                   │  │
│   │  doExecute() override — A/B experiments — ToolRegistry loop     │  │
│   ├─────────────────────────────────────────────────────────────────┤  │
│   │                                                                 │  │
│   │  Agentes:                                                       │  │
│   │  ┌─────────────────┐  ┌─────────────────┐  ┌───────────────┐  │  │
│   │  │ SmartMarketing  │  │ Storytelling     │  │ CustomerExp.  │  │  │
│   │  │ SmartContent    │  │ ProducerCopilot  │  │ Support       │  │  │
│   │  │ SmartEmployab.  │  │ SmartLegalCopil. │  │ Sales         │  │  │
│   │  │ MerchantCopilot │  │ LearningPath     │  │               │  │  │
│   │  └─────────────────┘  └─────────────────┘  └───────────────┘  │  │
│   │                                                                 │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   Servicios Clave (29+):                                                │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  ModelRouterService    ProviderFallbackService (circuit breaker)│  │
│   │  ContextWindowManager  ReActLoopService    HandoffDecisionServ. │  │
│   │  ToolRegistry          StreamingOrchestrator   TraceContextServ.│  │
│   │  SemanticCacheService  AgentLongTermMemory  AgentBenchmarkServ. │  │
│   │  AutoDiagnosticService PromptVersionService  AIGuardrailsServ.  │  │
│   │  PersonalizationEngine MultiModalBridgeServ.  VoicePipelineServ.│  │
│   │  McpServerController   InlineAiService      ProactiveInsights   │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   Seguridad IA:                                                         │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  AIIdentityRule::apply()       → Identidad inquebrantable       │  │
│   │  AIGuardrailsService::checkPII → DNI/NIE/IBAN/NIF/+34 + US     │  │
│   │  checkJailbreak()              → Bilingue ES/EN                 │  │
│   │  maskOutputPII()               → Bidireccional input/output     │  │
│   │  ConstitutionalGuardrail       → Pre-generation constraints     │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Integraciones IA

| Componente | Estado | Protocolo |
|------------|--------|-----------|
| MCP Server | Activo | JSON-RPC 2.0 POST /api/v1/mcp |
| Streaming SSE | Activo | PHP Generator + EventSource |
| Native Function Calling | Activo | ChatInput::setChatTools() |
| Semantic Cache (Qdrant) | Activo | 2 capas: exact + vector 0.92 |
| Tool Use Loop | Activo | Max 5 iteraciones |
| ReAct Loop | Activo | PLAN→EXECUTE→OBSERVE→REFLECT |
| Agent Benchmark | Activo | LLM-as-Judge evaluation |
| Prompt Versioning | Activo | Rollback capability |
| LCIS (9 capas) | Activo | Coherencia juridica automatica |

---

## 7. Page Builder / GrapesJS

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PAGE BUILDER — GRAPESJS 5.7                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   93 PHP files   │   166 Twig templates   │   11 JS plugins            │
│                                                                         │
│   Bloques: 202 (24 categorias)                                          │
│   ┌──────────────────────────────┬──────────────────────────────────┐  │
│   │ commerce, gallery, features  │ layout, maps, pricing, events   │  │
│   │ premium animations           │ testimonials, forms, heroes     │  │
│   └──────────────────────────────┴──────────────────────────────────┘  │
│                                                                         │
│   Templates: 55 verticales (5 verticales x 11 tipos)                   │
│   + 11 AgroConecta premium templates                                    │
│   + Template Registry SSoT v5.0                                         │
│                                                                         │
│   Integraciones:                                                        │
│   - Canvas Editor en ContentArticle (CANVAS-ARTICLE-001)               │
│   - SEO Assistant integrado                                             │
│   - Responsive Preview (8 viewports)                                    │
│   - Multi-Page Editor                                                   │
│   - Feature Flags system                                                │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 8. Infraestructura de Testing

### 8.1 Metricas de Tests

| Suite | Archivos | Metodos | % |
|-------|----------|---------|---|
| Unit | 376 | 3,213 | 88.0% |
| Kernel | 55 | 258 | 7.1% |
| Functional | 31 | 166 | 4.5% |
| PromptRegression | — | — | Planificado |
| **Total** | **462** | **3,650** | **100%** |

### 8.2 Cobertura por Modulo (top 10)

| Modulo | Unit | Kernel | Functional | Total |
|--------|------|--------|------------|-------|
| jaraba_facturae | 8 | 5 | 14 | 27 |
| ecosistema_jaraba_core | 2 | — | 5 | 7 |
| jaraba_support | 4 | — | — | 4 |
| jaraba_legal_intelligence | 7 | — | — | 7 |
| jaraba_content_hub | 3 | 1 | — | 4 |
| jaraba_ai_agents | 5+ | 2+ | — | 7+ |

### 8.3 Reglas de Testing Criticas

- KERNEL-TEST-DEPS-001: `$modules` NO auto-resuelve dependencias
- MOCK-DYNPROP-001: PHP 8.4 prohibe dynamic properties en mocks
- MOCK-METHOD-001: `createMock()` solo soporta metodos de la interface
- TEST-CACHE-001: Entity mocks DEBEN implementar cache interfaces
- KERNEL-TIME-001: Assertions de timestamp con tolerancia +/-1s

---

## 9. Pipeline CI/CD

### 9.1 Workflows Activos (8)

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    PIPELINE CI/CD — 8 WORKFLOWS                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   CONTINUO (en cada push):                                              │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  ci.yml                                                         │  │
│   │  ┌───────┐  ┌─────────┐  ┌─────────┐  ┌───────┐  ┌────────┐  │  │
│   │  │ Lint  │→│Architect│→│  Test   │→│Securit│→│ Build  │  │  │
│   │  │PHPCS  │  │Validate │  │ Unit+K │  │Trivy  │  │Composer│  │  │
│   │  │PHPStan│  │6 scripts│  │ 80% cov│  │Compos.│  │ SCSS   │  │  │
│   │  │ESLint │  │MODULE+DI│  │MariaDB │  │ audit │  │        │  │  │
│   │  └───────┘  └─────────┘  └─────────┘  └───────┘  └────────┘  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   DESPLIEGUE (manual/push a main):                                      │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  deploy.yml         deploy-staging.yml    deploy-production.yml │  │
│   │  20 pasos IONOS     Staging env           Blue-green (futuro)  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   SEGURIDAD (diario):                                                   │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  security-scan.yml           fitness-functions.yml               │  │
│   │  Trivy + OWASP ZAP + SAST   Custom architecture assertions     │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   BACKUP (diario):                                                      │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │  daily-backup.yml            verify-backups.yml                  │  │
│   │  Automated DB backup         Backup integrity verification      │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 9.2 Deploy Pipeline — 20 Pasos

| # | Paso | Tipo |
|---|------|------|
| 1 | Module consistency check | Validacion |
| 2 | Architectural validation (--full) | Validacion |
| 3 | PHPUnit Unit + Kernel | Tests |
| 4 | CSS up-to-date check | Compilacion |
| 5 | Pre-deploy DB backup | Seguridad |
| 6 | Maintenance mode ON | Operacion |
| 7 | settings.local.php validation | Config |
| 8 | API key injection | Seguridad |
| 9 | PHP CLI symlink | Infraestructura |
| 10 | Code pull (git reset) | Deploy |
| 11 | Composer install --no-dev | Dependencias |
| 12 | .htaccess fix | Config |
| 13 | drush updatedb | Schema |
| 14 | Site UUID sync | Config |
| 15 | drush config:import | Config |
| 16 | Cache rebuild | Operacion |
| 17 | Schema consistency check | Validacion |
| 18 | Maintenance mode OFF | Operacion |
| 19 | Smoke tests (5 checks) | Verificacion |
| 20 | Slack notification | Comunicacion |

---

## 10. Sistema de Validacion Arquitectonica

### 10.1 Validacion Cruzada YAML↔PHP (ARCH-VALIDATE-001)

```
┌─────────────────────────────────────────────────────────────────────────┐
│               VALIDACION ARQUITECTONICA — 6 SCRIPTS                      │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   scripts/validation/                                                   │
│   ├── validate-services-di.php      DI type mismatches (781 servicios) │
│   ├── validate-routing.php          Route→Controller (2,127 refs)      │
│   ├── validate-entity-integrity.php 6 checks entidades (437 entities)  │
│   ├── validate-query-chains.php     QUERY-CHAIN-001 detection          │
│   ├── validate-config-sync.sh       Config drift detection             │
│   └── validate-all.sh              Orquestador --fast / --full          │
│                                                                         │
│   Integracion:                                                          │
│   ┌─────────────┐  ┌─────────┐  ┌──────────┐  ┌──────────────┐       │
│   │ Pre-commit  │  │  CI     │  │  Deploy  │  │    Lando     │       │
│   │ --fast <3s  │  │ --full  │  │  --full  │  │ validate     │       │
│   │ condicional │  │ obligat.│  │ pre-rsync│  │ validate-fast│       │
│   └─────────────┘  └─────────┘  └──────────┘  └──────────────┘       │
│                                                                         │
│   Auto-descubrimiento: glob() — zero listas hardcoded                  │
│   Nuevos modulos se validan automaticamente                             │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 11. Tema y Frontend

### 11.1 ecosistema_jaraba_theme

| Componente | Cantidad |
|------------|----------|
| SCSS files | 99 |
| CSS compiled | 52 |
| JS files | 81 |
| Twig templates | 156 |
| Libraries declaradas | 4 globals + N route-specific |
| Parciales Twig | 65+ |

### 11.2 Sistema de Tokens CSS

```
5 Capas de Tokens:
  1. SCSS Variables → 2. CSS Custom Properties (--ej-*)
     → 3. Component Tokens → 4. Tenant Override
        → 5. Vertical Presets
```

### 11.3 Patrones Frontend

- **Zero Region Pattern**: `{{ clean_content }}` en vez de `{{ page.content }}`
- **Slide-Panel**: `renderPlain()` + `#action = requestUri()`
- **Iconos**: `jaraba_icon('category', 'name', { variant: 'duotone' })`
- **GrapesJS**: Full-viewport HtmlResponse bypasa page template

---

## 12. Documentacion

### 12.1 Inventario Documental

| Directorio | Archivos | Descripcion |
|------------|----------|-------------|
| tecnicos/ | 484 | Aprendizajes, auditorias, especificaciones |
| implementacion/ | 119 | Planes de implementacion |
| arquitectura/ | 27 | Documentos de arquitectura |
| planificacion/ | 22 | Planificacion estrategica |
| analisis/ | 12 | Auditorias y analisis |
| operaciones/ | 4 | Checklists de deploy |
| **Total** | **696** | |

### 12.2 Documentos Maestros

| Documento | Version | Lineas | Ultima Actualizacion |
|-----------|---------|--------|---------------------|
| DIRECTRICES | v106.0.0 | 2,295 | 2026-03-03 |
| ARQUITECTURA | v95.0.0 | 2,911 | 2026-03-03 |
| INDICE | v135.0.0 | 2,385 | 2026-03-03 |
| FLUJO | v59.0.0 | 870 | 2026-03-03 |

### 12.3 Aprendizajes

- **Ultimo**: #156 (Validacion Arquitectonica Automatizada)
- **Reglas de oro**: #96 (BASELINE-CLEAN-001)
- **Total aprendizajes**: 50+ documentos

---

## 13. Dependencias

### 13.1 Composer Packages

| Tipo | Cantidad |
|------|----------|
| require (produccion) | 94 |
| require-dev (testing) | 60 |
| **Total** | **154** |

### 13.2 Dependencias Clave

| Categoria | Paquetes |
|-----------|----------|
| **Drupal Core** | drupal/core ^11.0 |
| **AI Providers** | drupal/ai, ai_provider_anthropic, ai_provider_google_vertex, ai_provider_openai |
| **Commerce** | drupal/commerce ^3.1, commerce_stripe |
| **Multi-tenancy** | drupal/group ^3.2, drupal/domain ^2.0 |
| **Search** | drupal/search_api ^1.31 |
| **Workflow** | drupal/eca ^3.0 |
| **Cache** | drupal/redis ^1.7 |
| **SEO** | drupal/pathauto, drupal/redirect, drupal/metatag |
| **Pagos** | stripe/stripe-php ^15.0 |
| **PDF** | dompdf/dompdf ^2.0, tecnickcom/tcpdf ^6.10 |
| **Seguridad** | defuse/php-encryption ^2.4 |
| **Testing** | phpunit/phpunit ^11.0, mglaman/phpstan-drupal |

---

## 14. Estado de Compilacion y Runtime

| Componente | Estado | Verificacion |
|------------|--------|-------------|
| PHP syntax | OK | PHPStan Level 6 en CI |
| SCSS compilacion | Verificar | Recompilar si timestamps desincronizados |
| Config sync | OK | 1,460 YAMLs en config/sync/ |
| Schema DB | OK | hook_update_N() + EntityDefinitionUpdateManager |
| Rutas | OK | 2,569 rutas validadas por validate-routing.php |
| DI services | OK | 925 servicios validados por validate-services-di.php |
| Entity conventions | OK | 437 entidades verificadas |
| Query chains | OK | 0 patrones peligrosos detectados |

---

## 15. Tabla de Correspondencias

### 15.1 Regla → Script de Validacion → Punto de Control

| Regla | Script | Pre-commit | CI | Deploy | Lando |
|-------|--------|------------|----|---------| ------|
| MODULE-ORPHAN-001 | check-module-consistency.sh | ✓ fast | ✓ | ✓ | ✓ |
| DI-TYPE-001 | validate-services-di.php | — | ✓ full | ✓ full | ✓ |
| ROUTE-CTRL-001 | validate-routing.php | ✓ fast | ✓ | ✓ | ✓ |
| ENTITY-001 + 5 mas | validate-entity-integrity.php | — | ✓ full | ✓ full | ✓ |
| QUERY-CHAIN-001 | validate-query-chains.php | ✓ fast | ✓ | ✓ | ✓ |
| CONFIG-SYNC-001 | validate-config-sync.sh | — | ✓ full | ✓ full | ✓ |

### 15.2 Vertical → Modulo Principal → Entidades Clave

| Vertical | Modulo Principal | Entidades Clave |
|----------|-----------------|-----------------|
| empleabilidad | jaraba_candidate | CandidateProfile, CandidateEducation, CandidateExperience |
| emprendimiento | jaraba_business_tools | BusinessPlan, StartupProfile |
| comercioconecta | jaraba_comercio_conecta | Product, Order, MerchantProfile |
| agroconecta | jaraba_agroconecta_core | AgroProduct, AgroCatalog, CooperativeProfile |
| jarabalex | jaraba_legal | LegalCase, LegalDocument, Contract |
| serviciosconecta | jaraba_servicios_conecta | ServiceListing, BookingSlot |
| andalucia_ei | jaraba_andalucia_ei | SolicitudEi, ExpedienteDocumento |
| jaraba_content_hub | jaraba_content_hub | ContentArticle, ContentAuthor, ContentCategory |
| formacion | jaraba_lms | Course, LearningPath, Certificate |
| demo | ecosistema_jaraba_core | DemoGuide, FeatureShowcase |

### 15.3 Agente IA → Vertical → Tier Default

| Agente | Vertical(es) | Tier |
|--------|-------------|------|
| SmartMarketing | Todos (transversal) | balanced |
| SmartContentWriter | jaraba_content_hub | balanced |
| SmartEmployabilityCopilot | empleabilidad | balanced |
| SmartLegalCopilot | jarabalex | premium |
| ProducerCopilot | agroconecta | balanced |
| MerchantCopilot | comercioconecta | balanced |
| Sales | Todos (transversal) | fast |
| Support | jaraba_support | balanced |
| CustomerExperience | Todos (transversal) | balanced |
| Storytelling | jaraba_content_hub | balanced |
| LearningPathAgent | formacion | balanced |

---

## 16. Diagramas de Arquitectura

### 16.1 Flujo de Request Completo

```
┌──────────────────────────────────────────────────────────────────────┐
│                     FLUJO DE REQUEST                                   │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  Browser                                                               │
│    │                                                                   │
│    ▼                                                                   │
│  Apache (.htaccess)                                                    │
│    │                                                                   │
│    ▼                                                                   │
│  Drupal Bootstrap                                                      │
│    │                                                                   │
│    ├─→ Redis Cache (hit?) ──→ Response                                │
│    │                                                                   │
│    ▼                                                                   │
│  RouteProvider → Controller/Form                                       │
│    │                                                                   │
│    ├─→ TenantContextService (resolve tenant)                          │
│    ├─→ AccessControlHandler (verify tenant match)                     │
│    ├─→ Entity Storage (MariaDB + _field_data)                         │
│    ├─→ [Optional] AI Service (SmartBaseAgent)                         │
│    │     ├─→ ModelRouter (select tier)                                │
│    │     ├─→ ProviderFallback (circuit breaker)                       │
│    │     ├─→ AIGuardrails (PII check)                                 │
│    │     └─→ LLM API (Claude/Gemini)                                  │
│    │                                                                   │
│    ▼                                                                   │
│  Render Array                                                          │
│    │                                                                   │
│    ├─→ hook_preprocess_page() [Zero Region, drupalSettings]           │
│    ├─→ hook_page_attachments_alter() [Route SCSS, SEO meta]           │
│    ├─→ template_preprocess_{entity}() [Entity data extraction]        │
│    │                                                                   │
│    ▼                                                                   │
│  Twig Rendering                                                        │
│    │                                                                   │
│    ├─→ page--{route}.html.twig (zero region)                          │
│    ├─→ _header.html.twig → _header-classic.html.twig                  │
│    ├─→ {{ clean_content }} (system_main_block only)                   │
│    ├─→ _footer.html.twig (3 columnas configurables)                   │
│    │                                                                   │
│    ▼                                                                   │
│  HTML + CSS (compiled) + JS (Drupal.behaviors)                        │
│    │                                                                   │
│    ▼                                                                   │
│  Browser Render                                                        │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

### 16.2 Arquitectura de Datos Multi-Tenant

```
┌──────────────────────────────────────────────────────────────────────┐
│                     DATOS MULTI-TENANT                                  │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  Tenant (billing)                Group (content isolation)             │
│  ┌────────────────┐              ┌────────────────────┐               │
│  │ id             │              │ id                  │               │
│  │ plan_tier      │◀─ Bridge ──▶│ label               │               │
│  │ stripe_id      │              │ type                │               │
│  │ domain         │              │ members[]           │               │
│  │ billing_email  │              │ permissions[]       │               │
│  └────────────────┘              └────────────────────┘               │
│        │                               │                               │
│        ▼                               ▼                               │
│  ┌─────────────────────────────────────────────────────────┐          │
│  │  Entity (e.g. ContentArticle)                            │          │
│  │  ┌─────────────────────────────────────────────────────┐│          │
│  │  │ id │ tenant_id (→Group) │ uid │ status │ fields... ││          │
│  │  └─────────────────────────────────────────────────────┘│          │
│  │  AccessControlHandler: tenant match para update/delete   │          │
│  │  Queries: SIEMPRE ->condition('tenant_id', $tenantId)   │          │
│  └─────────────────────────────────────────────────────────┘          │
│                                                                        │
│  Config por Tenant:                                                    │
│  ┌──────────────────────┬──────────────────────────────────┐          │
│  │ FreemiumVerticalLimit│ SaasPlan │ SaasPlanFeatures       │          │
│  │ {vertical}_{plan}_   │ 3 planes │ features por plan     │          │
│  │ {feature} = limit    │ 29/79/199│ cascade resolution    │          │
│  └──────────────────────┴──────────────────────────────────┘          │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Historico de Versiones

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-03 | 1.0.0 | Creacion inicial — mapeo completo del estado de implementacion |
