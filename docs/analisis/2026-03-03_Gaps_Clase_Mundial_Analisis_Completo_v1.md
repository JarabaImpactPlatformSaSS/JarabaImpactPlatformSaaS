# Analisis de Gaps para Clase Mundial 100% — Jaraba Impact Platform SaaS

**Fecha:** 2026-03-03
**Version:** 1.0.0
**Metodologia:** Exploracion exhaustiva del codebase real (4 auditorias paralelas)
**Alcance:** 92 modulos custom, 10 verticales, flujo transversal completo

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Mapa de Madurez Global](#2-mapa-de-madurez-global)
3. [Gaps Transversales (Flujo Core)](#3-gaps-transversales-flujo-core)
4. [Gaps por Vertical](#4-gaps-por-vertical)
5. [Gaps de Testing](#5-gaps-de-testing)
6. [Gaps de Seguridad](#6-gaps-de-seguridad)
7. [Gaps Operacionales SaaS](#7-gaps-operacionales-saas)
8. [Matriz de Prioridades](#8-matriz-de-prioridades)
9. [Roadmap hacia el 100%](#9-roadmap-hacia-el-100)
10. [Tabla de Correspondencias Gap→Accion](#10-tabla-de-correspondencias-gapaccion)

---

## 1. Resumen Ejecutivo

La plataforma tiene una **base arquitectonica solida** (925 servicios, 441 entidades, 2,569 rutas, 8 workflows CI/CD, 6 scripts de validacion). Sin embargo, la auditoria profunda del codebase real revela que **los scores de elevacion del documento de estado anterior (80-95%) estaban sobreestimados** porque no consideraban:

- Testing funcional (solo 7/91 modulos cubiertos = 7.7%)
- REST APIs publicas (0/10 verticales con endpoints REST)
- Accesibilidad (50% WCAG 2.1 AA)
- Compliance legal publico (paginas legales no publicadas)
- CDN/Performance (40%)
- FreemiumVerticalLimit en 2 verticales criticos (Content Hub + LMS = 0 configs)

### Score Real Corregido

```
┌─────────────────────────────────────────────────────────────────────────┐
│               MADUREZ REAL vs SCORE ANTERIOR                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Dimension              │ Score Anterior │ Score Real │ Delta          │
│   ───────────────────────┼────────────────┼────────────┼──────────────  │
│   Pagos/Billing          │      95%       │    90%     │   -5%         │
│   Onboarding             │      90%       │    70%     │  -20%         │
│   Admin Dashboard        │      90%       │    75%     │  -15%         │
│   Notificaciones         │      85%       │    75%     │  -10%         │
│   Search                 │      90%       │    65%     │  -25%         │
│   Internacionalizacion   │      80%       │    60%     │  -20%         │
│   API/Integraciones      │      90%       │    80%     │  -10%         │
│   Accesibilidad          │      N/A       │    50%     │  NUEVO        │
│   Testing                │      85%       │    55%     │  -30%         │
│   Seguridad              │      90%       │    80%     │  -10%         │
│   Operacional SaaS       │      N/A       │    65%     │  NUEVO        │
│   ───────────────────────┼────────────────┼────────────┼──────────────  │
│   MEDIA PONDERADA        │      88%       │    69%     │  -19%         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Mapa de Madurez Global

```
┌─────────────────────────────────────────────────────────────────────────┐
│            RADAR DE MADUREZ — 11 DIMENSIONES                             │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│                         Pagos (90%)                                     │
│                            ████████░░                                   │
│                           /          \                                  │
│            API (80%) ████████░░  ████████░░ Admin (75%)                │
│                     /                        \                          │
│      Seguridad (80%) ████████░░     ███████░░░ Notificaciones (75%)   │
│                     |                        |                          │
│      Onboarding (70%) ███████░░░    ██████░░░░ Operacional (65%)     │
│                     \                        /                          │
│          Search (65%) ██████░░░░  ██████░░░░ i18n (60%)              │
│                        \                  /                             │
│            Testing (55%) █████░░░░░  █████░░░░░ Accesibilidad (50%) │
│                                                                         │
│   ████ = implementado     ░░░░ = gap                                  │
│                                                                         │
│   BLOQUEANTES PARA PRODUCCION:                                         │
│   ✗ Paginas legales no publicadas (compliance 30%)                    │
│   ✗ CDN no configurado (performance 40%)                              │
│   ✗ Queue monitoring inexistente (10%)                                │
│   ✗ 2 verticales sin FreemiumVerticalLimit                            │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Gaps Transversales (Flujo Core)

### 3.1 Pagos/Billing — 90%

**EXISTE (Clase Mundial):**
- `BillingWebhookController` maneja 10 event types de Stripe
- `StripeSubscriptionService` con create, cancel, update, pause, resume
- `DunningService` para reintentos de pago fallido
- `TenantMeteringService` + `UsageBillingApiController` para facturacion por uso
- `StripeInvoiceService` sincroniza facturas con PDF URLs de Stripe

**FALTA para 100%:**

| Gap | Impacto | Esfuerzo |
|-----|---------|----------|
| Servicio de prorrateo (upgrade/downgrade mid-cycle) | Alto — usuarios pierden credito | Medio |
| Generacion PDF local de facturas (no depender de Stripe) | Medio — vendor lock-in | Medio |
| Dashboard de metricas de pago (retry rates, decline reasons) | Medio — operacional | Bajo |
| UI self-service para pausar/reanudar suscripcion | Alto — UX | Bajo |
| Calculadora de impuestos multi-jurisdiccion (IVA UE, etc.) | Alto — expansion internacional | Alto |
| Dashboard de revenue (MRR/ARR/churn) | Medio — toma de decisiones | Medio |

### 3.2 Onboarding — 70%

**EXISTE:**
- `TenantOnboardingWizardService` + Controller con flujo multi-paso
- `OnboardingTemplate` ConfigEntity personalizable por vertical
- `OnboardingChecklistService` con gamificacion
- `OnboardingAnalyticsService` para funnels de conversion
- Auto-provisioning desde Stripe checkout

**FALTA para 100%:**

| Gap | Impacto | Esfuerzo |
|-----|---------|----------|
| Verificacion de email (loop de confirmacion) | CRITICO — seguridad basica | Bajo |
| Secuencias de email de bienvenida (drip campaign) | Alto — activacion | Medio |
| Flujos diferenciados por rol (Owner/Admin/Colaborador) | Medio — UX | Medio |
| Tours interactivos dentro de features (Shepherd.js) | Alto — activacion | Medio |
| Optimizacion mobile del flujo de onboarding | Medio — responsive | Bajo |
| Opcion "saltar" para power users | Bajo — UX | Bajo |

### 3.3 Admin Dashboard — 75%

**EXISTE:**
- `AdminCenterController` en `/admin/jaraba/center` con KPIs
- `AdminCenterAggregatorService` con metricas cross-modulo
- `HealthDashboardController` en `/admin/health`
- `FinancialDashboardController` en `/admin/jaraba/financial`
- `ComplianceDashboardController` para audit logs
- `UsageDashboardController` para metricas por tenant
- Command palette (Cmd+K)

**FALTA para 100%:**

| Gap | Impacto | Esfuerzo |
|-----|---------|----------|
| UI de gestion de usuarios bulk (invitar, desactivar, exportar) | Alto | Medio |
| Analiticas cross-tenant (comparativa de cohortes) | Medio | Alto |
| Metricas de performance en tiempo real (DB/Redis/PHP) | Medio | Medio |
| Activity stream (feed de cambios recientes) | Bajo | Medio |
| Tenant health scores (metrica compuesta) | Medio | Medio |
| UI de visor de audit log | Bajo | Bajo |

### 3.4 Notificaciones — 75%

**EXISTE:**
- `NotificationService` + `Notification` entity (system|social|workflow|ai)
- `jaraba_email` con SendGrid, MJML compiler, campanas, secuencias
- Web Push via VAPID en `jaraba_job_board`
- `NotificationApiController` con CRUD

**FALTA para 100%:**

| Gap | Impacto | Esfuerzo |
|-----|---------|----------|
| Centro de preferencias de notificaciones (UI usuario) | CRITICO — UX | Medio |
| SMS (Twilio/AWS SNS) | Medio — multi-canal | Medio |
| Dashboard de analiticas de entrega/apertura/clicks | Medio — operacional | Medio |
| Programacion de envio futuro (scheduling) | Bajo | Bajo |
| Templates de email por vertical | Medio — personalizacion | Medio |
| Gestion de unsubscribe en emails | Alto — compliance | Bajo |

### 3.5 Search — 65%

**EXISTE:**
- RAG completo via Qdrant (`jaraba_rag`) con semantic search
- `KbIndexerService` para indexacion
- `LlmReRankerService` para re-ranking
- `RagTenantFilterService` para aislamiento
- `QueryAnalyticsService` para tracking de busquedas

**FALTA para 100%:**

| Gap | Impacto | Esfuerzo |
|-----|---------|----------|
| Busqueda facetada (filtros por categoria, fecha, tipo, autor) | CRITICO — UX | Alto |
| Busqueda cross-vertical (unificada entre silos) | CRITICO — experiencia | Alto |
| Full-text search index (Elasticsearch/Solr) complementando Qdrant | Alto — cobertura | Alto |
| Autocompletado (typeahead) mientras escribe el usuario | Alto — UX | Medio |
| Busqueda avanzada (AND, OR, NOT, comillas) | Medio | Medio |
| Ordenacion de resultados (fecha, popularidad, relevancia) | Medio | Bajo |
| Dashboard de estado de indexacion | Bajo | Bajo |

### 3.6 Internacionalizacion — 60%

**EXISTE:**
- `AITranslationService` para traduccion batch
- `TranslationManagerService` para operaciones bulk
- `_hreflang-meta.html.twig` para SEO internacional
- `MultilingualGeoService` con mapeo de locales
- Language switcher en footer
- `TranslationDashboardController`

**FALTA para 100%:**

| Gap | Impacto | Esfuerzo |
|-----|---------|----------|
| Soporte RTL (arabe, hebreo) — dir="rtl" dinamico | Alto — mercados globales | Alto |
| Moneda configurable por tenant (hardcoded EUR) | CRITICO — expansion | Medio |
| Timezone por tenant (campo en Tenant entity) | Alto — UX global | Medio |
| Workflow de aprobacion de traducciones | Medio — calidad | Medio |
| Variantes regionales de contenido | Medio | Alto |
| Templates de email por idioma | Medio | Medio |
| Meta tags SEO traducidos por idioma | Medio | Bajo |

### 3.7 API/Integraciones — 80%

**EXISTE:**
- OpenAPI 3.0.3 spec en `ecosistema_jaraba_core/openapi/`
- API key auth via `X-API-Key` por tenant
- `RateLimiterService` con sliding window configurable
- `WebhookService` con HMAC-SHA256
- Stripe Connect, SendGrid, Claude API, Gemini API
- MCP Server JSON-RPC 2.0

**FALTA para 100%:**

| Gap | Impacto | Esfuerzo |
|-----|---------|----------|
| UI self-service para generar/rotar/revocar API keys | Alto — DX | Medio |
| Dashboard de uso de API (calls, latencia, errores por key) | Medio | Medio |
| Conectores pre-construidos (HubSpot, Salesforce, Mailchimp) | Alto — ecosistema | Alto |
| GraphQL endpoint (complementando REST) | Medio | Alto |
| SDK libraries publicadas (Python, JS/TS) | Medio — DX | Alto |
| UI de webhook delivery retry + historial | Medio | Medio |

### 3.8 Accesibilidad — 50% (NUEVA DIMENSION)

**EXISTE:**
- Skip links en `page.html.twig`
- ARIA roles basicos (main, complementary)
- Semantica HTML (main, aside, article)
- CSS variables para theming consistente

**FALTA para 100%:**

| Gap | Impacto | Esfuerzo |
|-----|---------|----------|
| aria-label en botones de icono (FAB, nav icons) | CRITICO — WCAG | Bajo |
| Focus visible styles (focus rings en botones/links) | CRITICO — WCAG | Bajo |
| Jerarquia de headings h1→h2→h3 consistente | Alto — WCAG | Medio |
| aria-describedby en mensajes de error de formularios | Alto — WCAG | Medio |
| Modal/dialog accesibilidad (focus trap, role="dialog") | Alto — WCAG | Medio |
| aria-live="polite" para actualizaciones dinamicas | Medio — WCAG | Bajo |
| alt text obligatorio en jaraba_icon() e imagenes | Alto — WCAG | Medio |
| Touch targets minimos 48x48px en mobile | Alto — WCAG | Medio |
| Testing con NVDA/JAWS en CI | Medio | Alto |

---

## 4. Gaps por Vertical

### 4.1 Tabla Comparativa — 14 Checks de Clase Mundial

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                    14 CHECKS DE CLASE MUNDIAL POR VERTICAL                                 │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                            │
│   Check                  │ EMP │ ENT │ COM │ AGR │ LEX │ SVC │ AND │ HUB │ LMS │ DEM │   │
│   ───────────────────────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼   │
│   1. FeatureGateService  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✗  │  ✗  │  ✓  │   │
│   2. UpgradeTriggerServ. │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✗  │  ✗  │  ✓  │   │
│   3. FreemiumVertLimits  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✗  │  ✗  │  —  │   │
│   4. CopilotBridgeServ.  │  ✗  │  ✗  │  ✓  │  ✓  │  ✓  │  ✓  │  ✗  │  ✗  │  ✗  │  —  │   │
│   5. Kernel Tests        │  ✗  │  ✓  │  ✓  │  ✓  │  ✗  │  ✓  │  ✓  │  ✗  │  ✗  │  ✓  │   │
│   6. Functional Tests    │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✓  │   │
│   7. Schema.org/JsonLD   │  ~  │  ~  │  ~  │  ~  │  ✗  │  ~  │  ~  │  ✓  │  ✗  │  —  │   │
│   8. Entity Preprocess   │  ~  │  ~  │  ~  │  ~  │  ✗  │  ~  │  ~  │  ✓  │  ✗  │  —  │   │
│   9. SCSS Dedicado       │  ✓  │  ✗  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✗  │  ✗  │  —  │   │
│  10. REST API Endpoints  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │  ✗  │   │
│  11. PremiumEntityForms  │  ✓  │  ~  │  ✓  │  ✓  │  ✓  │  ✓  │  ~  │  ✓  │  ~  │  ✓  │   │
│  12. AccessControlHndlr  │  ✓  │  ~  │  ✓  │  ✓  │  ✓  │  ✓  │  ~  │  ✓  │  ~  │  ✓  │   │
│  13. Cron/Queue Workers  │  ✗  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │   │
│  14. Views Integration   │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │  ✓  │   │
│   ───────────────────────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼   │
│   Score                  │ 65% │ 60% │ 85% │ 85% │ 70% │ 75% │ 75% │ 55% │ 50% │ 80% │   │
│                                                                                            │
│   ✓ = completo   ~ = parcial   ✗ = ausente   — = no aplica                               │
│   EMP=Empleabilidad ENT=Emprendimiento COM=ComercioConecta AGR=AgroConecta               │
│   LEX=JarabaLex SVC=ServiciosConecta AND=AndaluciaEI HUB=ContentHub LMS=Formacion        │
│   DEM=Demo                                                                                 │
│                                                                                            │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Gaps Criticos por Frecuencia

| Gap | Verticales Afectados | Count |
|-----|---------------------|-------|
| **REST API Endpoints** | TODOS | 10/10 |
| **Functional Tests** | Todos excepto demo | 9/10 |
| **Schema.org/JsonLD completo** | lex, lms + 6 parciales | 8/10 |
| **CopilotBridgeService** | empleabilidad, emprendimiento, andalucia_ei, content_hub, lms | 5/10 |
| **Kernel Tests** | empleabilidad, jarabalex, content_hub, lms | 4/10 |
| **Entity Preprocess completo** | jarabalex, lms + 6 parciales | 8/10 |
| **SCSS Dedicado** | emprendimiento, content_hub, lms | 3/10 |
| **FeatureGateService** | content_hub, lms | 2/10 |
| **FreemiumVerticalLimit** | content_hub, lms | 2/10 |
| **Cron/Queue Workers** | empleabilidad | 1/10 |

### 4.3 Detalle por Vertical

#### Formacion/LMS — 50% (MAS CRITICO)

```
Estado: Servicios de negocio implementados (10 servicios) pero NO productizado
```

**FALTA:**
1. `FormacionFeatureGateService` — sin feature gating = no monetizable
2. FreemiumVerticalLimit configs — 0 YAML files = sin limites por plan
3. `CopilotBridgeService` — sin contexto IA vertical
4. SCSS dedicado — sin estilos propios para UI de cursos
5. Kernel Tests — 0 (4 entities sin test)
6. Functional Tests — 0
7. Entity Preprocess hooks — 0 (4 entities que renderizan sin preprocess)
8. Schema.org — 0 (falta Course/EducationEvent/CourseInstance)
9. REST API — 0 endpoints
10. PremiumEntityFormBase — solo 2 de 4 entities

**Archivos clave existentes:**
- `jaraba_lms/src/Service/LearningPathService.php`
- `jaraba_lms/src/Service/CourseService.php`
- `jaraba_lms/src/Service/EnrollmentService.php`

#### Content Hub — 55%

```
Estado: Excelente SEO (12 refs Schema.org) pero sin modelo freemium
```

**FALTA:**
1. `ContentHubFeatureGateService` — sin feature gating
2. FreemiumVerticalLimit configs — 0 YAML files
3. `CopilotBridgeService` — sin bridge para SmartContentWriter
4. SCSS dedicado — solo en tema, no en modulo
5. Kernel Tests — 0
6. Functional Tests — 0
7. REST API — 0 endpoints

**Fortalezas:** SeoService excelente, ArticleService completo, RSS, preprocess hooks

#### Empleabilidad — 65%

**FALTA:**
1. `CopilotBridgeService` para SmartEmployabilityCopilot
2. Kernel Tests — 0 (9 entities sin tests kernel)
3. Functional Tests — 0
4. Cron/Queue Workers para indexacion de perfiles
5. REST API — 0 endpoints
6. Entity Preprocess hooks — solo 1 basico (9 entities)
7. Schema.org — solo 1 referencia (falta Person, JobPosting, EducationalOccupation)

#### Emprendimiento — 60%

**FALTA:**
1. SCSS dedicado — sin estilos propios
2. `CopilotBridgeService`
3. Functional Tests — 0
4. REST API — 0 endpoints
5. Entity Preprocess hooks — solo 1 basico
6. Schema.org — solo 1 referencia
7. PremiumEntityFormBase — solo 3 de 8+ entities

#### JarabaLex — 70%

**FALTA:**
1. Kernel Tests — 0 (CRITICO para dominio legal/compliance)
2. Functional Tests — 0
3. Entity Preprocess hooks — 0 (CRITICO para cases/documents)
4. Schema.org — 0 (falta LegalService, Document)
5. REST API — 0 endpoints

**Fortaleza:** CopilotBridgeService y FeatureGateService ya existen

#### ServiciosConecta — 75%

**FALTA:**
1. Functional Tests — 0
2. REST API — 0 endpoints
3. Schema.org completo (falta LocalBusiness/Service/Review)

#### Andalucia EI — 75%

**FALTA:**
1. `CopilotBridgeService` — sin contexto IA vertical
2. Functional Tests — 0
3. REST API — 0 endpoints
4. Schema.org completo (falta EducationEvent/Course)

#### ComercioConecta — 85%

**FALTA:**
1. Functional Tests — 0
2. REST API — 0 (a pesar de 42 entity types)
3. Schema.org completo (falta Product/Offer/Organization para ecommerce)

**Fortaleza:** 25 PremiumEntityFormBase, 42 AccessControlHandlers, CopilotBridge

#### AgroConecta — 85%

**FALTA:**
1. Functional Tests — 0
2. REST API — 0
3. Schema.org completo (falta Product/AggregateOffer)

**Fortaleza:** 36 FreemiumVerticalLimit configs (mas completo), CopilotBridge, 37 views_data

#### Demo — 80%

**FALTA:** Minimo — 8 Kernel tests + 5 Functional tests ya existen
Es el vertical de referencia para calidad de testing.

---

## 5. Gaps de Testing

### 5.1 Panorama General

```
┌─────────────────────────────────────────────────────────────────────────┐
│               COBERTURA DE TESTING — ESTADO REAL                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Modulos con ZERO tests:              16 de 91 (17.6%)               │
│   Modulos sin Kernel tests:            77 de 91 (84.6%)               │
│   Modulos sin Functional tests:        84 de 91 (92.3%)               │
│   AccessControlHandler tests:           3 de 328 (0.9%)               │
│   PromptRegression tests:               4 archivos (11 agentes sin)   │
│                                                                         │
│   Modulos SIN NINGUN TEST:                                             │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │ jaraba_addons        jaraba_agent_market  jaraba_ambient_ux    │  │
│   │ jaraba_blog (*)      jaraba_candidate     jaraba_groups        │  │
│   │ jaraba_identity      jaraba_lms           jaraba_notifications │  │
│   │ jaraba_paths         jaraba_resources     jaraba_site_builder  │  │
│   │ jaraba_skills        jaraba_social_commerce                    │  │
│   │ jaraba_success_cases jaraba_zkp                                │  │
│   │                                      (*) = deshabilitado       │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   Modulos CRITICOS sin Kernel tests:                                   │
│   ┌─────────────────────────────────────────────────────────────────┐  │
│   │ jaraba_ai_agents (orquestacion IA)                              │  │
│   │ jaraba_copilot_v2 (streaming/copilot)                          │  │
│   │ jaraba_rag (busqueda semantica)                                │  │
│   │ jaraba_legal_vault (boveda documental segura)                  │  │
│   │ jaraba_support (tickets de soporte)                            │  │
│   │ jaraba_billing (pagos/facturacion)                             │  │
│   │ jaraba_candidate (perfiles candidatos)                         │  │
│   │ jaraba_lms (cursos/formacion)                                  │  │
│   └─────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│   Tests de aislamiento multi-tenant:     0 tests                      │
│   Tests de flujo de pago Stripe:         0 funcionales (5 unit)       │
│   Tests de ejecucion de agentes IA:      0 funcionales                │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 5.2 Gaps Especificos de Testing

| Categoria | Estado | Gap | Prioridad |
|-----------|--------|-----|-----------|
| Unit Tests | 376 archivos, 3,213 metodos | Aceptable | — |
| Kernel Tests | 55 archivos, 258 metodos (14 modulos) | 77 modulos sin coverage | P0 |
| Functional Tests | 31 archivos, 166 metodos (7 modulos) | 84 modulos sin coverage | P0 |
| AccessControl Tests | 3 archivos (328 handlers) | 0.9% cobertura | P0 |
| PromptRegression | 4 archivos (base + 3 tests) | 11 agentes sin regression | P1 |
| Multi-tenant isolation | 0 tests | Sin verificacion de leaks | P0 |
| Stripe payment flow | 0 functional | Sin E2E de ciclo de pago | P1 |
| AI agent execution | 0 functional | Sin E2E de ejecucion agente | P2 |

---

## 6. Gaps de Seguridad

### 6.1 Hallazgos de la Auditoria

```
┌─────────────────────────────────────────────────────────────────────────┐
│               AUDITORIA DE SEGURIDAD — HALLAZGOS                         │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   ✅ CORRECTO:                                                         │
│   • SECRET-MGMT-001: settings.secrets.php + getenv() OK               │
│   • CSP/CORS: SecurityHeadersSubscriber implementado                  │
│   • Rate limiting: RateLimiterService con sliding window              │
│   • SQL Injection: QueryDatabaseTool con 6 capas de defensa           │
│   • HMAC webhooks: WebhookService con hash_equals()                   │
│                                                                         │
│   ⚠️ HALLAZGOS CRITICOS:                                              │
│                                                                         │
│   1. CSRF en rutas DELETE (jaraba_legal_vault)                        │
│      Archivo: jaraba_legal_vault.routing.yml                          │
│      Rutas afectadas:                                                 │
│      • /api/v1/vault/documents/{uuid} [DELETE]                        │
│      • /api/v1/vault/access/{id} [DELETE]                             │
│      Falta: _csrf_token: 'TRUE'                                      │
│                                                                         │
│   2. XSS potencial en soporte (|raw sin sanitizar)                    │
│      Archivo: _support-message-bubble.html.twig:78                    │
│      Codigo: {{ message.body|raw }}                                   │
│      Problema: Controller usa body (raw) en vez de body_html          │
│      (sanitized). Si Markdown→HTML acepta <script>, es XSS.          │
│                                                                         │
│   3. AccessControl sin tests (0.9% cobertura)                         │
│      328 handlers, solo 3 tests                                       │
│      Riesgo: Cross-tenant data leak no detectado                      │
│                                                                         │
│   4. |raw en Twig — 34 usos                                          │
│      Mayoría verificados seguros (Schema.org server-generated)        │
│      2 usos en jaraba_support necesitan auditoria                    │
│                                                                         │
│   5. innerHTML en JS — 20+ usos                                      │
│      Mayoría usan Drupal.checkPlain() correctamente                  │
│      2 usos en onboarding-wizard.js necesitan verificacion           │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Acciones de Seguridad Requeridas

| # | Hallazgo | Archivo | Accion | Prioridad |
|---|----------|---------|--------|-----------|
| 1 | CSRF DELETE routes | `jaraba_legal_vault.routing.yml` | Agregar `_csrf_token: 'TRUE'` a 3 rutas DELETE | P0 |
| 2 | XSS support body | `_support-message-bubble.html.twig:78` | Usar `body_html` en vez de `body` en TicketDetailController | P0 |
| 3 | AccessControl tests | 328 handlers | Crear functional tests de aislamiento tenant | P0 |
| 4 | Auditar |raw restantes | 34 usos en Twig | Verificar 2 usos en soporte | P1 |
| 5 | Auditar innerHTML | 20+ usos en JS | Verificar 2 usos en onboarding | P1 |
| 6 | API ALLOWED_FIELDS | Controllers /api/v1/* | Auditar filtrado de input en endpoints dinamicos | P2 |

---

## 7. Gaps Operacionales SaaS

### 7.1 Tabla de Madurez Operacional

```
┌─────────────────────────────────────────────────────────────────────────┐
│               MADUREZ OPERACIONAL SaaS                                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Componente               │ Score │ Estado                            │
│   ─────────────────────────┼───────┼────────────────────────────────── │
│   Tenant Lifecycle         │  95%  │ Wizard 7 pasos + Stripe          │
│   Email System             │  90%  │ SendGrid + MJML + secuencias     │
│   Cron/Queues              │  90%  │ 46 cron hooks + 38 QueueWorkers  │
│   Monitoring               │  85%  │ Prometheus+Grafana+Loki config   │
│   Documentacion            │  90%  │ 697 docs + help center + portal  │
│   Backup                   │  70%  │ SHA-256 verify pero sin RTO/RPO  │
│   Accesibilidad            │  50%  │ Basico pero sin WCAG completo    │
│   CDN/Performance          │  40%  │ PWA si, CDN no, AVIF/WebP no     │
│   Compliance Legal         │  30%  │ Entities existen, paginas NO     │
│   Queue Monitoring         │  10%  │ 38 workers sin dashboard         │
│                                                                         │
│   BLOQUEANTES PARA LANZAMIENTO:                                        │
│   ✗ Paginas legales publicas (Privacy, Terms, Cookie, DPA)            │
│   ✗ CDN para assets estaticos                                         │
│   ✗ Queue monitoring dashboard                                        │
│   ✗ Documentacion de RTO/RPO y procedimiento de restore              │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 7.2 Gaps Operacionales Detallados

#### COMPLIANCE LEGAL — 30% (BLOQUEANTE)

**EXISTE:** PrivacyPolicy, DpaAgreement, CookieConsent, DataRightsRequest entities + GDPR export via drush + `TenantExportService` para portabilidad.

**FALTA (CRITICO):**
- Pagina publica de Privacy Policy (existe como entity admin pero no publicada)
- Pagina publica de Terms of Service
- Pagina publica de Cookie Policy
- Pagina publica de Data Processing Agreement
- Link de unsubscribe funcional en emails

#### CDN/PERFORMANCE — 40% (ALTO)

**EXISTE:** PWA (Service Worker + manifest por tenant), responsive images con ImageStyle.

**FALTA:**
- CDN configurado (imagenes servidas desde origin)
- Optimizacion WebP/AVIF
- Lazy loading sistematico
- Critical CSS extraction
- Image optimization pipeline automatico

#### QUEUE MONITORING — 10% (ALTO)

**EXISTE:** 38+ QueueWorkers operando con Redis.

**FALTA:**
- Dashboard de estado de colas (longitud, throughput, errores)
- Dead-letter queue handling
- Alertas de cola atascada
- Metricas de procesamiento por worker

---

## 8. Matriz de Prioridades

### 8.1 Impacto vs Esfuerzo

```
┌─────────────────────────────────────────────────────────────────────────┐
│            MATRIZ IMPACTO vs ESFUERZO                                    │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   IMPACTO                                                              │
│   ALTO   │ Paginas legales      │ REST APIs verticales │ Faceted      │
│          │ CSRF fix vault       │ Kernel tests 8 mod   │   search     │
│          │ XSS fix soporte      │ FreemiumVL hub+lms   │ CDN setup    │
│          │ Email verification   │ Schema.org 8 vert    │ Elasticsearch│
│          │ a11y aria-label      │ Functional tests     │ RTL support  │
│          │ Focus visible CSS    │ Notification prefs   │              │
│          │─────────────────────┼──────────────────────┼──────────────│
│   MEDIO  │ Unsubscribe emails  │ CopilotBridge 5 vert │ GraphQL      │
│          │ Webhook UI           │ SCSS hub+lms+empren  │ SDK libs     │
│          │ Queue monitoring     │ Entity preprocess    │ CRM connec.  │
│          │ Tenant timezone      │ Currency per tenant  │ Revenue ML   │
│          │─────────────────────┼──────────────────────┼──────────────│
│   BAJO   │ Skip onboarding     │ Translation workflow │ Video tuts   │
│          │ Audit log UI        │ Activity stream      │ APM integr.  │
│          │                      │ Scheduling notifs    │              │
│          └──────────────────────┴──────────────────────┴──────────────│
│                BAJO                    MEDIO                  ALTO     │
│                                     ESFUERZO                           │
│                                                                         │
│   ■ Cuadrante superior-izquierdo = QUICK WINS (hacer primero)         │
│   ■ Cuadrante superior-derecho = PROYECTOS ESTRATEGICOS               │
│   ■ Cuadrante inferior = DIFERIR                                       │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Roadmap hacia el 100%

### Fase 0 — Bloqueantes de Lanzamiento (Semana 1)

| # | Accion | Tipo | Esfuerzo |
|---|--------|------|----------|
| 0.1 | Publicar paginas legales (Privacy, Terms, Cookie, DPA) | Compliance | 2d |
| 0.2 | Fix CSRF en rutas DELETE de legal_vault | Seguridad | 2h |
| 0.3 | Fix XSS body→body_html en soporte | Seguridad | 2h |
| 0.4 | Email verification en onboarding | Seguridad | 1d |
| 0.5 | Unsubscribe links en emails | Compliance | 1d |

### Fase 1 — Monetizacion + Quick Wins (Semana 2)

| # | Accion | Tipo | Esfuerzo |
|---|--------|------|----------|
| 1.1 | `FormacionFeatureGateService` + FreemiumVL (20 YAMLs) | Revenue | 1d |
| 1.2 | `ContentHubFeatureGateService` + FreemiumVL (12 YAMLs) | Revenue | 1d |
| 1.3 | aria-label en botones de icono + focus visible CSS | a11y | 1d |
| 1.4 | Heading hierarchy h1→h2→h3 en templates | a11y | 1d |
| 1.5 | Centro de preferencias de notificaciones (UI) | UX | 2d |

### Fase 2 — Testing Critico (Semanas 3-4)

| # | Accion | Tipo | Esfuerzo |
|---|--------|------|----------|
| 2.1 | Functional tests aislamiento multi-tenant (10 tests) | Seguridad | 3d |
| 2.2 | Kernel tests para 8 modulos criticos (30+ tests) | Calidad | 5d |
| 2.3 | Functional tests para 5 verticales top (25 tests) | Calidad | 5d |
| 2.4 | PromptRegression tests para 11 agentes Gen 2 | IA | 3d |
| 2.5 | AccessControl tests para entidades con tenant_id | Seguridad | 3d |

### Fase 3 — Elevacion Vertical (Semanas 5-6)

| # | Accion | Tipo | Esfuerzo |
|---|--------|------|----------|
| 3.1 | CopilotBridgeService x5 (emp, ent, and, hub, lms) | IA | 3d |
| 3.2 | Schema.org completo x8 verticales | SEO | 3d |
| 3.3 | Entity Preprocess hooks completos x4 (lex, lms, emp, ent) | Frontend | 3d |
| 3.4 | SCSS dedicado x3 (emprendimiento, content_hub, lms) | Frontend | 2d |
| 3.5 | REST API endpoints minimos x10 verticales (5 endpoints/vert) | API | 5d |

### Fase 4 — Infraestructura (Semanas 7-8)

| # | Accion | Tipo | Esfuerzo |
|---|--------|------|----------|
| 4.1 | CDN setup (Cloudflare/CloudFront) | Performance | 2d |
| 4.2 | WebP/AVIF optimization pipeline | Performance | 2d |
| 4.3 | Queue monitoring dashboard | Operacional | 3d |
| 4.4 | Faceted search UI | UX | 5d |
| 4.5 | Cross-vertical search | UX | 3d |

### Fase 5 — Clase Mundial (Semanas 9-10)

| # | Accion | Tipo | Esfuerzo |
|---|--------|------|----------|
| 5.1 | Moneda configurable por tenant | i18n | 3d |
| 5.2 | Timezone por tenant | i18n | 2d |
| 5.3 | Servicio de prorrateo billing | Pagos | 3d |
| 5.4 | UI self-service API keys | DX | 3d |
| 5.5 | Modal/dialog accessibility (focus trap, role="dialog") | a11y | 2d |
| 5.6 | aria-describedby en formularios | a11y | 2d |
| 5.7 | aria-live para actualizaciones dinamicas | a11y | 1d |

### Fase 6 — Excelencia (Semanas 11-12)

| # | Accion | Tipo | Esfuerzo |
|---|--------|------|----------|
| 6.1 | Conectores CRM pre-construidos (HubSpot, Salesforce) | Ecosistema | 5d |
| 6.2 | Dashboard de revenue (MRR/ARR/churn) | Admin | 3d |
| 6.3 | Webhook delivery retry UI + historial | DX | 2d |
| 6.4 | RTL support basico | i18n | 5d |
| 6.5 | SDK libraries (Python, JS/TS) | DX | 5d |

---

## 10. Tabla de Correspondencias Gap→Accion

| Gap ID | Dimension | Score Actual | Score Target | Accion Principal | Fase |
|--------|-----------|-------------|-------------|-----------------|------|
| GAP-LEGAL-001 | Compliance | 30% | 90% | Publicar paginas legales | 0 |
| GAP-CSRF-001 | Seguridad | 95% | 100% | Fix CSRF legal_vault | 0 |
| GAP-XSS-001 | Seguridad | 95% | 100% | Fix body→body_html soporte | 0 |
| GAP-EMAIL-VERIFY | Onboarding | 70% | 80% | Email verification loop | 0 |
| GAP-FVL-LMS | Revenue | 0% | 100% | FormacionFeatureGateService + configs | 1 |
| GAP-FVL-HUB | Revenue | 0% | 100% | ContentHubFeatureGateService + configs | 1 |
| GAP-A11Y-ARIA | Accesibilidad | 50% | 70% | aria-label + focus visible | 1 |
| GAP-NOTIF-PREFS | UX | 75% | 85% | Notification preferences UI | 1 |
| GAP-TEST-TENANT | Testing | 0% | 80% | Functional tests multi-tenant | 2 |
| GAP-TEST-KERNEL | Testing | 15% | 50% | Kernel tests 8 modulos criticos | 2 |
| GAP-TEST-FUNC | Testing | 7.7% | 40% | Functional tests 5 verticales | 2 |
| GAP-TEST-PROMPT | Testing | 20% | 80% | PromptRegression 11 agentes | 2 |
| GAP-COPILOT-5 | IA | 50% | 100% | CopilotBridgeService x5 | 3 |
| GAP-SCHEMA-8 | SEO | 20% | 80% | Schema.org x8 verticales | 3 |
| GAP-PREPROCESS | Frontend | 40% | 90% | Entity Preprocess x4 | 3 |
| GAP-REST-API | API | 0% | 80% | REST endpoints x10 | 3 |
| GAP-CDN | Performance | 0% | 90% | CDN + WebP/AVIF | 4 |
| GAP-QUEUE-MON | Operacional | 10% | 80% | Queue monitoring dashboard | 4 |
| GAP-SEARCH-FACET | UX | 0% | 80% | Faceted + cross-vertical search | 4 |
| GAP-CURRENCY | i18n | 0% | 80% | Moneda configurable por tenant | 5 |
| GAP-TIMEZONE | i18n | 0% | 80% | Timezone por tenant | 5 |
| GAP-PRORATION | Pagos | 0% | 90% | Servicio de prorrateo | 5 |
| GAP-A11Y-MODAL | Accesibilidad | 0% | 80% | Focus trap + role="dialog" | 5 |
| GAP-CRM | Ecosistema | 0% | 60% | Conectores HubSpot/Salesforce | 6 |
| GAP-RTL | i18n | 0% | 50% | RTL basico | 6 |
| GAP-SDK | DX | 0% | 60% | SDK Python + JS | 6 |

---

## Historico de Versiones

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-03 | 1.0.0 | Creacion — auditoria exhaustiva del codebase real con 4 agentes paralelos |
