# Plan de Implementacion — Cierre de Gaps hacia Clase Mundial 100%

**Fecha:** 2026-03-03
**Version:** 1.0.0
**Autor:** IA Asistente (Claude)
**Estado:** Pendiente de aprobacion
**Fuente:** Auditoria exhaustiva del codebase real (2026-03-03, 4 agentes paralelos)
**Alcance:** 26 gaps identificados en 11 dimensiones — de 69% a 100%

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnostico: De 69% a 100%](#2-diagnostico-de-69-a-100)
3. [Matriz de Correspondencias](#3-matriz-de-correspondencias-gap--directiva--accion--archivo--verificacion)
4. [FASE 0: Bloqueantes de Lanzamiento (Semana 1)](#4-fase-0-bloqueantes-de-lanzamiento-semana-1)
   - 4.1 [GAP-LEGAL-001: Paginas legales publicas](#41-gap-legal-001-paginas-legales-publicas)
   - 4.2 [GAP-CSRF-001: Fix CSRF legal_vault](#42-gap-csrf-001-fix-csrf-legal_vault)
   - 4.3 [GAP-XSS-001: Fix XSS soporte](#43-gap-xss-001-fix-xss-soporte)
   - 4.4 [GAP-EMAIL-VERIFY: Verificacion email onboarding](#44-gap-email-verify-verificacion-email-onboarding)
   - 4.5 [GAP-UNSUBSCRIBE: Links de baja en emails](#45-gap-unsubscribe-links-de-baja-en-emails)
5. [FASE 1: Monetizacion + Quick Wins (Semana 2)](#5-fase-1-monetizacion--quick-wins-semana-2)
   - 5.1 [GAP-FVL-LMS: FeatureGate + FreemiumVL Formacion](#51-gap-fvl-lms-featuregate--freemiumvl-formacion)
   - 5.2 [GAP-FVL-HUB: FeatureGate + FreemiumVL Content Hub](#52-gap-fvl-hub-featuregate--freemiumvl-content-hub)
   - 5.3 [GAP-A11Y-ARIA: Accesibilidad aria-label + focus visible](#53-gap-a11y-aria-accesibilidad-aria-label--focus-visible)
   - 5.4 [GAP-NOTIF-PREFS: Centro preferencias notificaciones](#54-gap-notif-prefs-centro-preferencias-notificaciones)
6. [FASE 2: Testing Critico (Semanas 3-4)](#6-fase-2-testing-critico-semanas-3-4)
   - 6.1 [GAP-TEST-TENANT: Tests aislamiento multi-tenant](#61-gap-test-tenant-tests-aislamiento-multi-tenant)
   - 6.2 [GAP-TEST-KERNEL: Kernel tests 8 modulos criticos](#62-gap-test-kernel-kernel-tests-8-modulos-criticos)
   - 6.3 [GAP-TEST-FUNC: Functional tests 5 verticales](#63-gap-test-func-functional-tests-5-verticales)
   - 6.4 [GAP-TEST-PROMPT: PromptRegression 11 agentes](#64-gap-test-prompt-promptregression-11-agentes)
   - 6.5 [GAP-TEST-ACCESS: AccessControl tests](#65-gap-test-access-accesscontrol-tests)
7. [FASE 3: Elevacion Vertical (Semanas 5-6)](#7-fase-3-elevacion-vertical-semanas-5-6)
   - 7.1 [GAP-COPILOT-5: CopilotBridgeService x5 verticales](#71-gap-copilot-5-copilotbridgeservice-x5-verticales)
   - 7.2 [GAP-SCHEMA-8: Schema.org x8 verticales](#72-gap-schema-8-schemaorg-x8-verticales)
   - 7.3 [GAP-PREPROCESS: Entity Preprocess x4 verticales](#73-gap-preprocess-entity-preprocess-x4-verticales)
   - 7.4 [GAP-SCSS-3: SCSS dedicado x3 verticales](#74-gap-scss-3-scss-dedicado-x3-verticales)
   - 7.5 [GAP-REST-API: REST API endpoints x10 verticales](#75-gap-rest-api-rest-api-endpoints-x10-verticales)
8. [FASE 4: Infraestructura (Semanas 7-8)](#8-fase-4-infraestructura-semanas-7-8)
   - 8.1 [GAP-CDN: CDN + WebP/AVIF](#81-gap-cdn-cdn--webpavif)
   - 8.2 [GAP-QUEUE-MON: Queue monitoring dashboard](#82-gap-queue-mon-queue-monitoring-dashboard)
   - 8.3 [GAP-SEARCH-FACET: Busqueda facetada + cross-vertical](#83-gap-search-facet-busqueda-facetada--cross-vertical)
9. [FASE 5: Clase Mundial (Semanas 9-10)](#9-fase-5-clase-mundial-semanas-9-10)
   - 9.1 [GAP-CURRENCY: Moneda configurable por tenant](#91-gap-currency-moneda-configurable-por-tenant)
   - 9.2 [GAP-TIMEZONE: Timezone por tenant](#92-gap-timezone-timezone-por-tenant)
   - 9.3 [GAP-PRORATION: Prorrateo billing](#93-gap-proration-prorrateo-billing)
   - 9.4 [GAP-A11Y-MODAL: Accesibilidad modales](#94-gap-a11y-modal-accesibilidad-modales)
   - 9.5 [GAP-API-KEYS: UI self-service API keys](#95-gap-api-keys-ui-self-service-api-keys)
10. [FASE 6: Excelencia (Semanas 11-12)](#10-fase-6-excelencia-semanas-11-12)
    - 10.1 [GAP-CRM: Conectores HubSpot/Salesforce](#101-gap-crm-conectores-hubspotsalesforce)
    - 10.2 [GAP-RTL: Soporte RTL basico](#102-gap-rtl-soporte-rtl-basico)
    - 10.3 [GAP-SDK: SDK Python + JS](#103-gap-sdk-sdk-python--js)
    - 10.4 [GAP-REVENUE-DASH: Dashboard MRR/ARR/churn](#104-gap-revenue-dash-dashboard-mrrarrchurn)
11. [Directrices de Aplicacion Transversal](#11-directrices-de-aplicacion-transversal)
    - 11.1 [Zero Region Pattern](#111-zero-region-pattern)
    - 11.2 [SCSS con Dart Sass + variables inyectables desde UI](#112-scss-con-dart-sass--variables-inyectables-desde-ui)
    - 11.3 [Textos siempre traducibles](#113-textos-siempre-traducibles)
    - 11.4 [PremiumEntityFormBase](#114-premiumentityformbase)
    - 11.5 [Slide-Panel para CRUD](#115-slide-panel-para-crud)
    - 11.6 [Iconos ICON-CONVENTION-001 + paleta Jaraba](#116-iconos-icon-convention-001--paleta-jaraba)
    - 11.7 [Body classes via hook_preprocess_html()](#117-body-classes-via-hook_preprocess_html)
    - 11.8 [Field UI + Views integration](#118-field-ui--views-integration)
    - 11.9 [Navegacion admin: /admin/structure + /admin/content](#119-navegacion-admin-adminstructure--admincontent)
    - 11.10 [RUNTIME-VERIFY-001: 12 checks post-implementacion](#1110-runtime-verify-001-12-checks-post-implementacion)
12. [Verificacion End-to-End](#12-verificacion-end-to-end)
    - 12.1 [Checklist por fase](#121-checklist-por-fase)
    - 12.2 [Comandos de verificacion (lando)](#122-comandos-de-verificacion-lando)
    - 12.3 [Criterios de aceptacion](#123-criterios-de-aceptacion)
13. [Historico de Versiones](#13-historico-de-versiones)

---

## 1. Resumen Ejecutivo

La auditoria exhaustiva del codebase real (2026-03-03) revelo que la madurez real de la plataforma es **69%** (vs 88% estimado previamente), con **26 gaps identificados** en 11 dimensiones. Este documento define el plan de implementacion tecnico completo para cerrar todos los gaps en 12 semanas (6 fases), llevando la plataforma de su estado actual a **clase mundial 100%**.

### Principios Rectores

1. **Seguridad primero**: Los gaps de seguridad (CSRF, XSS) y compliance legal se resuelven en la Fase 0 antes de cualquier otra cosa.
2. **Revenue habilitado**: La Fase 1 desbloquea monetizacion en 2 verticales criticos (Formacion y Content Hub) que actualmente operan sin limites por plan.
3. **Testing como red de seguridad**: La Fase 2 establece la cobertura de testing necesaria antes de las fases de expansion.
4. **Elevacion uniforme**: Las Fases 3-6 elevan sistematicamente cada vertical y cada dimension operacional.
5. **Sin Humo**: Cada gap tiene archivos concretos, patron de referencia existente, y verificacion post-implementacion.

### Metricas de Impacto

```
┌─────────────────────────────────────────────────────────────────────────┐
│               IMPACTO POR FASE                                           │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Fase │ Semanas │ Gaps │ Score 69%→ │ Tipo                            │
│   ─────┼─────────┼──────┼────────────┼───────────────────────────────  │
│     0  │    1    │   5  │  69→74%    │ Bloqueantes (seguridad+legal)   │
│     1  │    2    │   4  │  74→79%    │ Monetizacion + Quick Wins       │
│     2  │   3-4   │   5  │  79→85%    │ Testing critico                 │
│     3  │   5-6   │   5  │  85→91%    │ Elevacion vertical              │
│     4  │   7-8   │   3  │  91→94%    │ Infraestructura                 │
│     5  │   9-10  │   5  │  94→97%    │ Clase mundial                   │
│     6  │  11-12  │   4  │  97→100%   │ Excelencia                      │
│   ─────┼─────────┼──────┼────────────┼───────────────────────────────  │
│  TOTAL │   12    │  31  │  69→100%   │ 26 gaps unicos + 5 sub-items   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Diagnostico: De 69% a 100%

### 2.1 Score Real por Dimension

```
┌─────────────────────────────────────────────────────────────────────────┐
│               MADUREZ REAL vs OBJETIVO 100%                              │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│   Dimension              │ Score Real │ Objetivo │ Delta │ Fase(s)     │
│   ───────────────────────┼────────────┼──────────┼───────┼──────────── │
│   Pagos/Billing          │    90%     │   100%   │ +10%  │ 5           │
│   Onboarding             │    70%     │   100%   │ +30%  │ 0, 1        │
│   Admin Dashboard        │    75%     │   100%   │ +25%  │ 4, 6        │
│   Notificaciones         │    75%     │   100%   │ +25%  │ 0, 1        │
│   Search                 │    65%     │   100%   │ +35%  │ 4           │
│   Internacionalizacion   │    60%     │   100%   │ +40%  │ 5, 6        │
│   API/Integraciones      │    80%     │   100%   │ +20%  │ 3, 5, 6     │
│   Accesibilidad          │    50%     │   100%   │ +50%  │ 1, 5        │
│   Testing                │    55%     │   100%   │ +45%  │ 2           │
│   Seguridad              │    80%     │   100%   │ +20%  │ 0, 2        │
│   Operacional SaaS       │    65%     │   100%   │ +35%  │ 0, 4        │
│   ───────────────────────┼────────────┼──────────┼───────┼──────────── │
│   MEDIA PONDERADA        │    69%     │   100%   │ +31%  │ 0-6         │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Score Real por Vertical (14 Checks)

```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                    14 CHECKS DE CLASE MUNDIAL POR VERTICAL                                 │
├──────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                            │
│   Check                  │ EMP │ ENT │ COM │ AGR │ LEX │ SVC │ AND │ HUB │ LMS │ DEM │   │
│   ───────────────────────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┤   │
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
│   ───────────────────────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┼─────┤   │
│   Score                  │ 65% │ 60% │ 85% │ 85% │ 70% │ 75% │ 75% │ 55% │ 50% │ 80% │   │
│                                                                                            │
│   ✓ = completo   ~ = parcial   ✗ = ausente   — = no aplica                               │
│   EMP=Empleabilidad ENT=Emprendimiento COM=ComercioConecta AGR=AgroConecta               │
│   LEX=JarabaLex SVC=ServiciosConecta AND=AndaluciaEI HUB=ContentHub LMS=Formacion        │
│   DEM=Demo                                                                                 │
│                                                                                            │
└──────────────────────────────────────────────────────────────────────────────────────────┘
```

### 2.3 Bloqueantes Criticos para Lanzamiento

| # | Bloqueante | Tipo | Riesgo si no se resuelve |
|---|-----------|------|--------------------------|
| 1 | Paginas legales no publicadas | Compliance | Violacion RGPD — sancion economica |
| 2 | CSRF en rutas DELETE de legal_vault | Seguridad | Eliminacion no autorizada de documentos |
| 3 | XSS en burbujas de soporte | Seguridad | Ejecucion de script malicioso |
| 4 | Sin verificacion de email | Seguridad | Cuentas falsas, spam |
| 5 | Sin link de unsubscribe en emails | Compliance | Violacion CAN-SPAM/RGPD, deliverability |

---

## 3. Matriz de Correspondencias (Gap → Directiva → Accion → Archivo → Verificacion)

| Gap ID | Dimension | Directiva(s) Aplicable(s) | Accion Principal | Archivo(s) a Crear/Modificar | Verificacion |
|--------|-----------|---------------------------|------------------|------------------------------|-------------|
| GAP-LEGAL-001 | Compliance | ZERO-REGION-001, ROUTE-LANGPREFIX-001 | 4 rutas publicas /legal/* | `ecosistema_jaraba_core`: Controller, routing, templates | `lando drush router:debug \| grep legal` |
| GAP-CSRF-001 | Seguridad | CSRF-API-001 | `_csrf_request_header_token: 'TRUE'` en 3 rutas DELETE | `jaraba_legal_vault.routing.yml` | `grep csrf jaraba_legal_vault.routing.yml` |
| GAP-XSS-001 | Seguridad | AUDIT-SEC-003 | Usar `body_html` sanitizado en lugar de `body\|raw` | `_support-message-bubble.html.twig`, `TicketDetailController` | Inspeccion manual del template |
| GAP-EMAIL-VERIFY | Onboarding | SECRET-MGMT-001 | Loop de confirmacion de email | `ecosistema_jaraba_core`: Service, Controller, template email | `lando drush user:create test --mail=test@test.com` |
| GAP-UNSUBSCRIBE | Compliance | ROUTE-LANGPREFIX-001 | Token de unsub en headers y footer de emails | `jaraba_email`: Service, templates MJML | Inspeccionar header `List-Unsubscribe` en email |
| GAP-FVL-LMS | Revenue | PREMIUM-FORMS-PATTERN-001 | FeatureGateService + 20 YAMLs FreemiumVL | `jaraba_lms/src/Service/`, `config/install/` | `lando drush config:get jaraba_lms.freemium_vertical_limit.formacion_free_courses_limit` |
| GAP-FVL-HUB | Revenue | PREMIUM-FORMS-PATTERN-001 | FeatureGateService + 12 YAMLs FreemiumVL | `jaraba_content_hub/src/Service/`, `config/install/` | `lando drush config:get jaraba_content_hub.freemium_vertical_limit.content_hub_free_articles_limit` |
| GAP-A11Y-ARIA | Accesibilidad | ICON-CONVENTION-001 | aria-label en botones de icono + focus visible CSS | Templates parciales, `_variables.scss` | Lighthouse Accessibility audit > 90 |
| GAP-NOTIF-PREFS | UX | SLIDE-PANEL-RENDER-001, PREMIUM-FORMS-PATTERN-001 | UI de preferencias de notificaciones | `jaraba_notifications/src/Form/`, Controller, template | Navegar a /mi-cuenta/notificaciones |
| GAP-TEST-TENANT | Testing | TENANT-001, TENANT-ISOLATION-ACCESS-001 | 10 functional tests de aislamiento | `tests/src/Functional/TenantIsolation*Test.php` | `lando phpunit --group=tenant-isolation` |
| GAP-TEST-KERNEL | Testing | KERNEL-TEST-DEPS-001, CI-KERNEL-001 | 30+ kernel tests para 8 modulos | `tests/src/Kernel/` en cada modulo | `lando phpunit --testsuite=Kernel` |
| GAP-TEST-FUNC | Testing | MOCK-DYNPROP-001 | 25 functional tests para 5 verticales | `tests/src/Functional/` en cada modulo | `lando phpunit --testsuite=Functional` |
| GAP-TEST-PROMPT | Testing | AGENT-GEN2-PATTERN-001 | PromptRegression para 11 agentes Gen 2 | `jaraba_ai_agents/tests/src/Unit/PromptRegression/` | `lando phpunit --group=prompt-regression` |
| GAP-TEST-ACCESS | Testing | AUDIT-CONS-001, ACCESS-STRICT-001 | Tests de AccessControlHandler | `tests/src/Kernel/Access/` en modulos criticos | `lando phpunit --group=access-control` |
| GAP-COPILOT-5 | IA | SMART-AGENT-CONSTRUCTOR-001 | CopilotBridgeService x5 verticales | 5 archivos Service + 5 services.yml | `lando drush service:list \| grep copilot_bridge` |
| GAP-SCHEMA-8 | SEO | ENTITY-PREPROCESS-001 | Schema.org JSON-LD x8 verticales | 8 SeoService + hook_page_attachments_alter | JSON-LD Validator (Google) |
| GAP-PREPROCESS | Frontend | ENTITY-PREPROCESS-001 | Entity preprocess hooks x4 verticales | 4 archivos .module | Verificar que templates renderizan datos |
| GAP-SCSS-3 | Frontend | SCSS-001, CSS-VAR-ALL-COLORS-001 | SCSS dedicado x3 verticales | `scss/routes/*.scss`, `*.libraries.yml` | `npm run build` + timestamp CSS > SCSS |
| GAP-REST-API | API | API-WHITELIST-001, CSRF-API-001, TENANT-001 | REST endpoints x10 verticales | 10 RestResource plugins + routing | `lando drush router:debug --format=json \| jq '.[] \| select(.path \| startswith("/api/"))'` |
| GAP-CDN | Performance | CSS-VAR-ALL-COLORS-001 | CDN setup + WebP/AVIF pipeline | `.htaccess`, `settings.php`, image styles | WebPageTest.org performance |
| GAP-QUEUE-MON | Operacional | — | Dashboard de estado de colas | Controller + template + cron | `/admin/jaraba/queues` accesible |
| GAP-SEARCH-FACET | UX | ZERO-REGION-001 | Busqueda facetada + cross-vertical | `jaraba_rag`: Service, Controller, template, JS | `/buscar?q=test&facet[vertical]=empleo` |
| GAP-CURRENCY | i18n | TENANT-BRIDGE-001 | Campo currency en Tenant entity | Tenant entity + migration + services | `lando drush entity:updates` sin errores |
| GAP-TIMEZONE | i18n | TENANT-BRIDGE-001 | Campo timezone en Tenant entity | Tenant entity + migration + services | Verificar timestamp en UI tras cambio timezone |
| GAP-PRORATION | Pagos | SECRET-MGMT-001 | Servicio de prorrateo via Stripe proration | `jaraba_billing/src/Service/ProrationService.php` | Test unitario de calculo de prorrateo |
| GAP-A11Y-MODAL | Accesibilidad | SLIDE-PANEL-RENDER-001 | Focus trap + role="dialog" + aria-modal | `js/slide-panel.js`, templates modales | Tab key no escapa del modal abierto |
| GAP-API-KEYS | DX | CSRF-API-001, TENANT-001 | UI self-service para API keys | Controller + Form + template + routing | `/mi-cuenta/api-keys` accesible |
| GAP-CRM | Ecosistema | API-WHITELIST-001 | Conectores HubSpot + Salesforce | `jaraba_crm/src/Service/Connector/` | Sincronizar contacto de test |
| GAP-RTL | i18n | CSS-VAR-ALL-COLORS-001 | dir="rtl" dinamico + SCSS logical properties | `html.html.twig`, `_variables.scss`, reglas SCSS | Cambiar idioma a arabe y verificar layout |
| GAP-SDK | DX | — | SDK publicado (Python + JS/TS) | Repo separado + paquetes NPM/PyPI | `pip install jaraba-sdk && python -c "import jaraba"` |
| GAP-REVENUE-DASH | Admin | ZERO-REGION-001 | Dashboard MRR/ARR/churn | Controller + Service + template + SCSS | `/admin/jaraba/revenue` con datos reales |

---

## 4. FASE 0: Bloqueantes de Lanzamiento (Semana 1)

> **CRITICO**: Estos 5 gaps DEBEN resolverse ANTES de cualquier lanzamiento publico. Incluyen vulnerabilidades de seguridad activas y violaciones de compliance legal.

### 4.1 GAP-LEGAL-001: Paginas legales publicas

**Estado actual:** Las entidades `PrivacyPolicy`, `DpaAgreement`, `CookieConsent` existen en admin pero NO tienen paginas publicas accesibles. `TenantExportService` funciona para GDPR pero sin UI publica de politicas.

**Compliance:** 30% → 90%

**Directivas aplicables:**
- ZERO-REGION-001: Template limpio con `{{ clean_content }}`
- ROUTE-LANGPREFIX-001: Rutas via `Url::fromRoute()`, prefijo `/es/` automatico
- Body classes via `hook_preprocess_html()` (NUNCA `attributes.addClass()` en template)
- Textos traducibles: `{% trans %}` en headings fijos
- SCSS: Variables inyectables `var(--ej-*, fallback)`

**Archivos a crear:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `ecosistema_jaraba_core/src/Controller/LegalPagesController.php` | Controller | Renderiza paginas legales desde entities existentes |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` (editar) | Routing | 4 rutas: `/legal/privacidad`, `/legal/terminos`, `/legal/cookies`, `/legal/dpa` |
| `ecosistema_jaraba_theme/templates/page--legal.html.twig` | Template | Zero Region: header + `{{ clean_content }}` + footer |
| `ecosistema_jaraba_theme/templates/partials/_legal-page.html.twig` | Parcial | Contenido reutilizable para las 4 paginas legales |
| `ecosistema_jaraba_theme/scss/routes/legal.scss` | SCSS | Estilos dedicados para paginas legales |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml` (editar) | Library | Declarar `route-legal` |

**Patron de referencia:** Las rutas existentes del blog en `jaraba_content_hub.routing.yml` (rutas publicas con template Zero Region y preprocess hooks).

**Controller — Estructura:**

```php
// LegalPagesController.php
declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
// Inyectar EntityTypeManagerInterface via create() — CONTROLLER-READONLY-001
// NO usar protected readonly para $entityTypeManager (propiedad heredada)

class LegalPagesController extends ControllerBase {

  public function privacy(): array {
    return $this->renderLegalPage('privacy_policy', 'Politica de Privacidad');
  }

  public function terms(): array {
    return $this->renderLegalPage('terms_of_service', 'Terminos de Servicio');
  }

  public function cookies(): array {
    return $this->renderLegalPage('cookie_policy', 'Politica de Cookies');
  }

  public function dpa(): array {
    return $this->renderLegalPage('dpa_agreement', 'Acuerdo de Procesamiento de Datos');
  }

  // ZERO-REGION-003: Controller devuelve solo render array basico.
  // Variables y drupalSettings se inyectan via hook_preprocess_page().
  protected function renderLegalPage(string $type, string $title): array {
    return [
      '#type' => 'markup',
      '#markup' => '',
      // Los datos reales se pasan via hook_preprocess_page()
    ];
  }
}
```

**Template — Estructura:**

```twig
{# page--legal.html.twig — Zero Region Pattern #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<main id="main-content" class="legal-page" role="main">
  <div class="legal-page__container">
    {% include '@ecosistema_jaraba_theme/partials/_legal-page.html.twig' %}
  </div>
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

**Parcial _legal-page.html.twig:**

```twig
{# _legal-page.html.twig — Parcial reutilizable para paginas legales #}
<article class="legal-content">
  <header class="legal-content__header">
    <h1 class="legal-content__title">{{ legal_title }}</h1>
    <p class="legal-content__date">
      {% trans %}Ultima actualizacion:{% endtrans %} {{ legal_updated }}
    </p>
  </header>

  <div class="legal-content__body">
    {{ legal_body }}
  </div>
</article>
```

**SCSS — `scss/routes/legal.scss`:**

```scss
@use 'sass:color';
@use '../variables' as *;

.legal-page {
  padding: var(--ej-spacing-xl, 3rem) var(--ej-spacing-md, 1.5rem);
  background: var(--ej-bg-surface, #{$bg-surface});
  min-height: 60vh;

  &__container {
    max-width: 800px;
    margin: 0 auto;
  }
}

.legal-content {
  &__header {
    margin-bottom: var(--ej-spacing-lg, 2rem);
    border-bottom: 1px solid var(--ej-border-color, #{$border-color});
    padding-bottom: var(--ej-spacing-md, 1.5rem);
  }

  &__title {
    font-size: var(--ej-font-size-2xl, 2rem);
    color: var(--ej-text-heading, #{$text-heading});
    font-weight: 700;
  }

  &__date {
    color: var(--ej-text-muted, #{$text-muted});
    font-size: var(--ej-font-size-sm, 0.875rem);
  }

  &__body {
    color: var(--ej-text-color, #{$text-color});
    line-height: 1.7;

    h2 { font-size: var(--ej-font-size-xl, 1.5rem); margin-top: var(--ej-spacing-lg, 2rem); }
    h3 { font-size: var(--ej-font-size-lg, 1.25rem); margin-top: var(--ej-spacing-md, 1.5rem); }
    ul, ol { padding-left: var(--ej-spacing-md, 1.5rem); }
    a { color: var(--ej-link-color, #{$link-color}); }
  }
}
```

**Routing (agregar a `ecosistema_jaraba_core.routing.yml`):**

```yaml
ecosistema_jaraba_core.legal.privacy:
  path: '/legal/privacidad'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\LegalPagesController::privacy'
    _title: 'Politica de Privacidad'
  requirements:
    _access: 'TRUE'  # Pagina publica

ecosistema_jaraba_core.legal.terms:
  path: '/legal/terminos'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\LegalPagesController::terms'
    _title: 'Terminos de Servicio'
  requirements:
    _access: 'TRUE'

ecosistema_jaraba_core.legal.cookies:
  path: '/legal/cookies'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\LegalPagesController::cookies'
    _title: 'Politica de Cookies'
  requirements:
    _access: 'TRUE'

ecosistema_jaraba_core.legal.dpa:
  path: '/legal/dpa'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\LegalPagesController::dpa'
    _title: 'Acuerdo de Procesamiento de Datos'
  requirements:
    _access: 'TRUE'
```

**Preprocess hook (agregar a `ecosistema_jaraba_core.module`):**

```php
// En ecosistema_jaraba_core.module (o via OOP Hook class)
// Inyectar contenido legal y body class via hook_preprocess_page()
// ZERO-REGION-002: NUNCA pasar entity objects como non-# keys
// Solo primitivos: $variables['legal_title'], $variables['legal_body'], $variables['legal_updated']
```

**Verificacion post-implementacion:**

- [ ] `lando drush router:debug | grep legal` muestra 4 rutas
- [ ] Acceder a `https://jaraba-saas.lndo.site/es/legal/privacidad` devuelve 200
- [ ] Template usa `{{ clean_content }}` (Zero Region)
- [ ] Body class `page-legal` presente en `<body>`
- [ ] Textos fijos usan `{% trans %}`
- [ ] SCSS compilado: timestamp CSS > SCSS (`SCSS-COMPILE-VERIFY-001`)
- [ ] Links en footer del sitio apuntan a las 4 paginas legales

---

### 4.2 GAP-CSRF-001: Fix CSRF legal_vault

**Estado actual:** 3 rutas DELETE en `jaraba_legal_vault.routing.yml` carecen de proteccion CSRF. Las rutas aceptan DELETE via fetch() desde el frontend JS sin verificar token CSRF.

**Seguridad:** 95% → 100% (parcial, junto con GAP-XSS-001)

**Directiva aplicable:** CSRF-API-001 — API routes via `fetch()` usan `_csrf_request_header_token: 'TRUE'` (NO `_csrf_token` que es para formularios Drupal clasicos).

**Archivo a modificar:** `web/modules/custom/jaraba_legal_vault/jaraba_legal_vault.routing.yml`

**Cambio requerido — Agregar a las 3 rutas DELETE:**

```yaml
# ANTES (inseguro):
jaraba_legal_vault.api.document.delete:
  path: '/api/v1/vault/documents/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_legal_vault\Controller\VaultApiController::deleteDocument'
  methods: [DELETE]
  requirements:
    _permission: 'manage legal vault'

# DESPUES (seguro — CSRF-API-001):
jaraba_legal_vault.api.document.delete:
  path: '/api/v1/vault/documents/{uuid}'
  defaults:
    _controller: '\Drupal\jaraba_legal_vault\Controller\VaultApiController::deleteDocument'
  methods: [DELETE]
  requirements:
    _permission: 'manage legal vault'
    _csrf_request_header_token: 'TRUE'
```

**Rutas afectadas (las 3):**
1. `/api/v1/vault/documents/{uuid}` [DELETE]
2. `/api/v1/vault/access/{id}` [DELETE]
3. Cualquier otra ruta DELETE del modulo (verificar con `grep -n "methods:.*DELETE" jaraba_legal_vault.routing.yml`)

**Verificacion JS — El fetch() existente DEBE enviar el header:**

```javascript
// CORRECTO — CSRF-JS-CACHE-001
// El token debe obtenerse de /session/token y cachearse
const csrfToken = await Drupal.behaviors.jarabaLegalVault.getCsrfToken();
fetch(url, {
  method: 'DELETE',
  headers: {
    'X-CSRF-Token': csrfToken,
    'Content-Type': 'application/json',
  },
});
```

**Verificacion post-implementacion:**

- [ ] Las 3 rutas DELETE tienen `_csrf_request_header_token: 'TRUE'`
- [ ] `lando drush router:debug` muestra las rutas sin errores
- [ ] `fetch()` sin header X-CSRF-Token devuelve 403
- [ ] `fetch()` con header correcto devuelve 200/204
- [ ] JS del frontend obtiene token de `/session/token` (CSRF-JS-CACHE-001)

---

### 4.3 GAP-XSS-001: Fix XSS soporte

**Estado actual:** En `_support-message-bubble.html.twig:78` se usa `{{ message.body|raw }}` que renderiza HTML sin sanitizar. Si el contenido del mensaje proviene de input del usuario sin filtrar, permite ejecucion de scripts maliciosos.

**Seguridad:** 95% → 100% (parcial, junto con GAP-CSRF-001)

**Directiva aplicable:** AUDIT-SEC-003 — NUNCA `|raw` sin sanitizacion previa en servidor.

**Archivos a modificar:**

| Archivo | Cambio |
|---------|--------|
| `ecosistema_jaraba_theme/templates/partials/_support-message-bubble.html.twig` | Reemplazar `{{ message.body\|raw }}` por `{{ message.body_html }}` |
| `jaraba_support/src/Controller/TicketDetailController.php` | Verificar que `body_html` se genera con `Xss::filterAdmin()` |

**Cambio en template (linea 78):**

```twig
{# ANTES — INSEGURO (AUDIT-SEC-003 violacion): #}
{{ message.body|raw }}

{# DESPUES — SEGURO: #}
{{ message.body_html }}
```

**Verificacion en Controller:**

```php
// En TicketDetailController.php — verificar que body_html usa sanitizacion
use Drupal\Component\Utility\Xss;

// El controller DEBE preparar body_html asi:
$message['body_html'] = Xss::filterAdmin($rawBody);
// O con un filtro mas restrictivo:
$message['body_html'] = Xss::filter($rawBody, ['a', 'em', 'strong', 'p', 'br', 'ul', 'ol', 'li', 'code', 'pre', 'blockquote']);
```

**Adicionalmente — Auditar los 2 usos restantes de `|raw` en `jaraba_support`:**

```bash
# Buscar todos los usos de |raw en templates de soporte
grep -rn '|raw' web/modules/custom/jaraba_support/templates/
grep -rn '|raw' web/themes/custom/ecosistema_jaraba_theme/templates/partials/_support*
```

**Verificacion post-implementacion:**

- [ ] `grep '|raw' _support-message-bubble.html.twig` no devuelve resultados
- [ ] Inyectar `<script>alert('xss')</script>` en mensaje de soporte NO ejecuta el script
- [ ] El contenido HTML legitimo (negritas, enlaces) sigue renderizandose correctamente
- [ ] Los 2 usos restantes de `|raw` en soporte estan auditados

---

### 4.4 GAP-EMAIL-VERIFY: Verificacion email onboarding

**Estado actual:** El flujo de onboarding (`TenantOnboardingWizardService`) crea cuentas sin verificacion de email. Cualquiera puede registrar una cuenta con un email ajeno.

**Onboarding:** 70% → 80%

**Directivas aplicables:**
- SECRET-MGMT-001: Tokens via `getenv()`, NUNCA en config/sync/
- ROUTE-LANGPREFIX-001: Links de verificacion con `Url::fromRoute()` (incluye `/es/` automaticamente)
- PRESAVE-RESILIENCE-001: El envio de email DEBE ser resiliente — si falla, el registro continua

**Archivos a crear/modificar:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `ecosistema_jaraba_core/src/Service/EmailVerificationService.php` | Servicio nuevo | Genera token, envia email, verifica token |
| `ecosistema_jaraba_core/src/Controller/EmailVerificationController.php` | Controller nuevo | Endpoint `/verificar-email/{token}` |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` (editar) | Routing | Ruta publica de verificacion |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml` (editar) | DI | Registrar el nuevo servicio |
| `jaraba_email/templates/email-verification.html.twig` | Template MJML | Email de verificacion con link |

**Patron:**
- Generar token HMAC con `hash_hmac('sha256', $email . $uid . $timestamp, getenv('HASH_SALT'))`
- Token valido por 24 horas
- Link de verificacion: `Url::fromRoute('ecosistema_jaraba_core.verify_email', ['token' => $token])->setAbsolute()->toString()`
- Al verificar: marcar campo `email_verified` en User entity (usar `hook_entity_base_field_info()` para agregar el campo)
- Reenvio: boton en /mi-cuenta/configuracion, max 3 reenvios por hora

**Verificacion post-implementacion:**

- [ ] Al crear cuenta nueva, se envia email de verificacion
- [ ] Click en link verifica el email (campo `email_verified` = TRUE)
- [ ] Token expirado devuelve mensaje de error amigable
- [ ] Token invalido devuelve 403
- [ ] Link de reenvio funciona desde /mi-cuenta/configuracion

---

### 4.5 GAP-UNSUBSCRIBE: Links de baja en emails

**Estado actual:** El sistema de email (`jaraba_email` + SendGrid) envia campanas y secuencias pero sin header `List-Unsubscribe` ni link visible de baja en el footer del email.

**Compliance:** Violacion CAN-SPAM Act y RGPD Art. 7(3)

**Directivas aplicables:**
- ROUTE-LANGPREFIX-001: URL de unsub via `Url::fromRoute()`
- SECRET-MGMT-001: Token de unsub firmado con HMAC

**Archivos a crear/modificar:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `jaraba_email/src/Service/UnsubscribeService.php` | Servicio nuevo | Genera token, procesa baja |
| `jaraba_email/src/Controller/UnsubscribeController.php` | Controller nuevo | GET `/email/unsubscribe/{token}` muestra confirmacion, POST procesa |
| `jaraba_email/jaraba_email.routing.yml` (editar) | Routing | Ruta publica |
| Templates MJML de jaraba_email (editar) | Templates | Agregar link de unsub en footer |

**Headers de email requeridos (RFC 8058):**

```
List-Unsubscribe: <https://jaraba-saas.lndo.site/es/email/unsubscribe/{token}>
List-Unsubscribe-Post: List-Unsubscribe=One-Click
```

**Verificacion post-implementacion:**

- [ ] Emails enviados incluyen header `List-Unsubscribe`
- [ ] Link de baja visible en footer del email
- [ ] Click en link muestra pagina de confirmacion (no baja directa)
- [ ] Al confirmar, el usuario deja de recibir ese tipo de emails
- [ ] Los emails transaccionales (verificacion, reset password) NO incluyen link de baja

---

## 5. FASE 1: Monetizacion + Quick Wins (Semana 2)

> **OBJETIVO**: Desbloquear revenue en 2 verticales criticos y resolver quick wins de accesibilidad y UX.

### 5.1 GAP-FVL-LMS: FeatureGate + FreemiumVL Formacion

**Estado actual:** El vertical Formacion/LMS tiene 7 entidades, 13 servicios y 45 rutas funcionando pero opera sin ningun control de limites por plan. Cualquier usuario free puede crear cursos ilimitados.

**Revenue:** 0% → 100% (feature gating del vertical)

**Directivas aplicables:**
- PREMIUM-FORMS-PATTERN-001: Forms de entidades LMS con PremiumEntityFormBase
- ICON-CONVENTION-001: `jaraba_icon('education', 'course', { variant: 'duotone' })` para forms

**Patron de referencia:** `EmployabilityFeatureGateService` en `ecosistema_jaraba_core/src/Service/EmployabilityFeatureGateService.php` — Estructura identica, cambiando `VERTICAL = 'empleabilidad'` a `VERTICAL = 'formacion'` y la tabla de tracking.

**Archivos a crear:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `jaraba_lms/src/Service/FormacionFeatureGateService.php` | Servicio | Feature gating para formacion |
| `jaraba_lms/jaraba_lms.services.yml` (editar) | DI | Registrar servicio con DI: UpgradeTrigger, DB, currentUser, logger, TenantContext |
| `jaraba_lms/jaraba_lms.install` (editar) | Install | Schema para tabla `formacion_feature_usage` |
| 20 archivos YAML en `jaraba_lms/config/install/` | Config | FreemiumVerticalLimit por plan x feature |

**Features gestionadas:**

| Feature Key | Descripcion | Free | Starter | Profesional | Business |
|-------------|-------------|------|---------|-------------|----------|
| `courses_limit` | Cursos creados | 3 | 15 | 50 | -1 |
| `learning_paths_limit` | Rutas de aprendizaje | 1 | 5 | 20 | -1 |
| `certificates_limit` | Certificados emitidos/mes | 10 | 50 | 200 | -1 |
| `enrollments_limit` | Matriculaciones activas | 20 | 100 | 500 | -1 |
| `copilot_uses_per_month` | Consultas al copiloto IA | 5 | 30 | 100 | -1 |

**Estructura YAML (ejemplo `jaraba_lms.freemium_vertical_limit.formacion_free_courses_limit.yml`):**

```yaml
langcode: es
status: true
dependencies: {  }
id: formacion_free_courses_limit
label: 'Limite de cursos - Plan Free'
vertical: formacion
plan: free
feature_key: courses_limit
limit_value: 3
upgrade_message: 'Has alcanzado el limite de cursos en tu plan gratuito. Actualiza a Starter para crear hasta 15 cursos.'
```

**20 YAMLs necesarios:** 4 planes (free, starter, profesional, business) x 5 features = 20 archivos.

**Verificacion post-implementacion:**

- [ ] `lando drush config:get jaraba_lms.freemium_vertical_limit.formacion_free_courses_limit` devuelve `limit_value: 3`
- [ ] Servicio registrado: `lando drush debug:container --tag=jaraba_lms | grep feature_gate`
- [ ] `FormacionFeatureGateService::check()` devuelve `FeatureGateResult::denied()` cuando se excede el limite
- [ ] Test unitario verifica los 5 escenarios (permitido, limite alcanzado, ilimitado, feature no incluida, plan desconocido)
- [ ] Forms LMS usan `PremiumEntityFormBase` con `getFormIcon()` correcto

---

### 5.2 GAP-FVL-HUB: FeatureGate + FreemiumVL Content Hub

**Estado actual:** Content Hub tiene excelente SEO (SeoService) y ArticleService completo, pero opera sin limites por plan para la creacion de contenido. Sin FeatureGateService ni FreemiumVerticalLimit configs.

**Revenue:** 0% → 100% (feature gating del vertical)

**Patron de referencia:** Mismo que GAP-FVL-LMS → `EmployabilityFeatureGateService`

**Archivos a crear:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `jaraba_content_hub/src/Service/ContentHubFeatureGateService.php` | Servicio | Feature gating para content hub |
| `jaraba_content_hub/jaraba_content_hub.services.yml` (editar) | DI | Registrar servicio |
| `jaraba_content_hub/jaraba_content_hub.install` (editar) | Install | Tabla `content_hub_feature_usage` |
| 12 archivos YAML en `jaraba_content_hub/config/install/` | Config | FreemiumVerticalLimit |

**Features gestionadas:**

| Feature Key | Descripcion | Free | Starter | Profesional | Business |
|-------------|-------------|------|---------|-------------|----------|
| `articles_limit` | Articulos publicados | 10 | 50 | 200 | -1 |
| `categories_limit` | Categorias | 3 | 10 | 30 | -1 |
| `authors_limit` | Autores editoriales | 1 | 5 | 20 | -1 |

**12 YAMLs necesarios:** 4 planes x 3 features = 12 archivos.

**Verificacion post-implementacion:**

- [ ] 12 configs importadas: `lando drush config:list | grep content_hub.freemium`
- [ ] Crear articulo #11 con plan free devuelve modal de upgrade
- [ ] Plan business permite creacion ilimitada

---

### 5.3 GAP-A11Y-ARIA: Accesibilidad aria-label + focus visible

**Estado actual:** Botones de icono (FAB copilot, nav icons, slide-panel close) carecen de `aria-label`. Focus visible no tiene estilos dedicados (`:focus-visible` sin ring visible).

**Accesibilidad:** 50% → 70%

**Directivas aplicables:**
- ICON-CONVENTION-001: `jaraba_icon()` incluye parametro para `aria-label`
- ICON-DUOTONE-001: Variante default duotone
- CSS-VAR-ALL-COLORS-001: Focus ring con `var(--ej-focus-ring, ...)`

**Archivos a modificar — 67 parciales en `templates/partials/`:**

Los parciales criticos con botones de icono sin aria-label:

| Parcial | Elemento | aria-label requerido |
|---------|----------|---------------------|
| `_copilot-fab.html.twig` | Boton FAB | `{% trans %}Abrir asistente{% endtrans %}` |
| `_command-bar.html.twig` | Boton cerrar | `{% trans %}Cerrar barra de comandos{% endtrans %}` |
| `_avatar-nav.html.twig` | Boton avatar | `{% trans %}Menu de usuario{% endtrans %}` |
| `_bottom-nav.html.twig` | Links de nav | `{% trans %}Inicio{% endtrans %}`, etc. |
| `_header-classic.html.twig` | Hamburger menu | `{% trans %}Abrir menu{% endtrans %}` |
| `_category-filter.html.twig` | Filtros | `{% trans %}Filtrar por categoria{% endtrans %}` |

**SCSS para focus visible — Agregar a `_variables.scss` o crear `_a11y.scss`:**

```scss
// Focus visible — WCAG 2.1 AA 2.4.7
// Aplicar globalmente a interactivos
:focus-visible {
  outline: 3px solid var(--ej-focus-ring, #{$azul-corporativo});
  outline-offset: 2px;
  border-radius: var(--ej-radius-sm, 4px);
}

// Quitar outline en click (solo mouse)
:focus:not(:focus-visible) {
  outline: none;
}

// Focus ring para botones con icono (sin texto visible)
.icon-button:focus-visible,
[aria-label]:focus-visible {
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--ej-focus-ring, #{$azul-corporativo}) 40%, transparent);
}
```

**Verificacion post-implementacion:**

- [ ] Lighthouse Accessibility score > 90
- [ ] Tab key muestra ring azul visible en cada interactivo
- [ ] Screen reader (NVDA/JAWS) anuncia cada boton de icono por su aria-label
- [ ] SCSS compilado: `SCSS-COMPILE-VERIFY-001`

---

### 5.4 GAP-NOTIF-PREFS: Centro preferencias notificaciones

**Estado actual:** `NotificationService` + `Notification` entity funcionan con 4 tipos (system, social, workflow, ai) pero NO existe UI para que el usuario configure sus preferencias de recepcion.

**UX:** 75% → 85%

**Directivas aplicables:**
- SLIDE-PANEL-RENDER-001: Form en slide-panel via `renderPlain()`
- PREMIUM-FORMS-PATTERN-001: Extender `PremiumEntityFormBase`
- ZERO-REGION-001: Pagina con `{{ clean_content }}`

**Archivos a crear:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `jaraba_notifications/src/Entity/NotificationPreference.php` | ContentEntity | Preferencias por usuario x tipo x canal |
| `jaraba_notifications/src/Form/NotificationPreferenceForm.php` | Form | Extends PremiumEntityFormBase |
| `jaraba_notifications/src/Controller/NotificationPreferenceController.php` | Controller | Renderiza en slide-panel |
| `jaraba_notifications/src/NotificationPreferenceAccessControlHandler.php` | Access | Solo el propio usuario puede editar |
| `ecosistema_jaraba_theme/templates/partials/_notification-prefs.html.twig` | Parcial | UI de toggles por tipo |
| `ecosistema_jaraba_theme/scss/routes/notification-prefs.scss` | SCSS | Estilos dedicados |

**Estructura de preferencias:**

```
┌─────────────────────────────────────────────────────────┐
│  Tipo de Notificacion  │  Email  │  Push  │  In-App   │
│  ──────────────────────┼─────────┼────────┼────────── │
│  Sistema               │   ✓     │   ✓    │   ✓      │
│  Social                │   ✓     │   ✗    │   ✓      │
│  Workflow              │   ✓     │   ✓    │   ✓      │
│  IA (copilot)          │   ✗     │   ✗    │   ✓      │
│  Marketing             │   ✓     │   ✗    │   ✗      │
└─────────────────────────────────────────────────────────┘
```

**Verificacion post-implementacion:**

- [ ] Ruta `/mi-cuenta/notificaciones` accesible
- [ ] Form usa PremiumEntityFormBase con secciones y pills
- [ ] Cambiar preferencia persiste en DB (entity_query por user_id)
- [ ] NotificationService respeta las preferencias antes de enviar
- [ ] Slide-panel funciona: `renderPlain()` + `#action`

---

## 6. FASE 2: Testing Critico (Semanas 3-4)

> **OBJETIVO**: Establecer red de seguridad de testing antes de expandir funcionalidad. Priorizar aislamiento multi-tenant y modulos criticos.

### 6.1 GAP-TEST-TENANT: Tests aislamiento multi-tenant

**Estado actual:** 0 tests de aislamiento multi-tenant. 328 AccessControlHandlers con solo 3 tests (0.9% cobertura). Riesgo critico de data leak cross-tenant.

**Testing:** 0% → 80% (aislamiento)

**Directivas aplicables:**
- TENANT-001: TODA query DEBE filtrar por tenant
- TENANT-ISOLATION-ACCESS-001: AccessControlHandler verifica tenant match
- ACCESS-STRICT-001: Comparaciones con `(int)..===(int)`

**Archivos a crear (10 tests):**

| Archivo | Scope | Que verifica |
|---------|-------|-------------|
| `ecosistema_jaraba_core/tests/src/Functional/TenantIsolationBaseTest.php` | Base | Setup comun: 2 tenants, 2 users |
| `ecosistema_jaraba_core/tests/src/Functional/TenantIsolationEntityAccessTest.php` | Entities | User de tenant A NO puede ver/editar entities de tenant B |
| `ecosistema_jaraba_core/tests/src/Functional/TenantIsolationQueryTest.php` | Queries | EntityQuery con tenant filter devuelve solo datos propios |
| `jaraba_billing/tests/src/Functional/TenantBillingIsolationTest.php` | Billing | Facturas de tenant A invisibles para tenant B |
| `jaraba_legal_vault/tests/src/Functional/TenantVaultIsolationTest.php` | Legal vault | Documentos legales aislados |
| `jaraba_content_hub/tests/src/Functional/TenantContentIsolationTest.php` | Content | Articulos aislados por tenant |
| `jaraba_lms/tests/src/Functional/TenantLmsIsolationTest.php` | LMS | Cursos aislados por tenant |
| `jaraba_candidate/tests/src/Functional/TenantCandidateIsolationTest.php` | Empleabilidad | Perfiles de candidato aislados |
| `jaraba_support/tests/src/Functional/TenantSupportIsolationTest.php` | Soporte | Tickets aislados por tenant |
| `jaraba_page_builder/tests/src/Functional/TenantPageIsolationTest.php` | Pages | Paginas del page builder aisladas |

**Patron de test base:**

```php
// TenantIsolationBaseTest.php
abstract class TenantIsolationBaseTest extends BrowserTestBase {

  // KERNEL-TEST-DEPS-001: Listar TODOS los modulos requeridos
  protected static $modules = [
    'ecosistema_jaraba_core',
    'group',
    // ... dependencias completas
  ];

  protected function setUp(): void {
    parent::setUp();
    // Crear Tenant A + Group A + User A
    // Crear Tenant B + Group B + User B
    // Crear entidad de prueba en Tenant A
    // Crear entidad de prueba en Tenant B
  }

  // Test: User A accede a entity de Tenant A → 200
  // Test: User A accede a entity de Tenant B → 403
  // Test: EntityQuery de User A solo devuelve Tenant A
}
```

**Verificacion post-implementacion:**

- [ ] `lando phpunit --group=tenant-isolation` ejecuta 10+ tests
- [ ] TODOS los tests pasan en verde
- [ ] CI pipeline ejecuta estos tests en el job de Functional

---

### 6.2 GAP-TEST-KERNEL: Kernel tests 8 modulos criticos

**Estado actual:** 77 de 91 modulos sin Kernel tests (84.6%). Los 8 modulos mas criticos sin coverage kernel son: `jaraba_ai_agents` (orquestacion IA), `jaraba_copilot_v2` (streaming), `jaraba_rag` (busqueda semantica), `jaraba_legal_vault`, `jaraba_support`, `jaraba_billing`, `jaraba_candidate`, `jaraba_lms`.

**Testing:** 15% → 50% (kernel)

**Directivas aplicables:**
- KERNEL-TEST-DEPS-001: `$modules` NO auto-resuelve dependencias
- KERNEL-SYNTH-001: Dependencias de modulos no cargados = synthetic
- KERNEL-TIME-001: Timestamps con tolerancia +/-1 segundo
- MOCK-DYNPROP-001: PHP 8.4 prohibe dynamic properties
- TEST-CACHE-001: Entity mocks DEBEN implementar cache interfaces

**Tests a crear por modulo (30+ total):**

| Modulo | Tests | Que cubren |
|--------|-------|-----------|
| `jaraba_ai_agents` | 5 | ModelRouterService, ToolRegistry, AIGuardrailsService, ReActLoopService, AgentBenchmarkService |
| `jaraba_copilot_v2` | 4 | SemanticCacheService, StreamingOrchestratorService, CopilotOrchestratorService |
| `jaraba_rag` | 3 | KbIndexerService, LlmReRankerService, RagTenantFilterService |
| `jaraba_legal_vault` | 4 | VaultService (CRUD), EncryptionService, AccessLogService |
| `jaraba_support` | 3 | TicketService, SLAService, EscalationService |
| `jaraba_billing` | 4 | StripeSubscriptionService, DunningService, InvoiceService, MeteringService |
| `jaraba_candidate` | 4 | CandidateProfileService, SkillMatchService, CVGeneratorService |
| `jaraba_lms` | 3 | CourseService, EnrollmentService, CertificateService |

**Verificacion post-implementacion:**

- [ ] `lando phpunit --testsuite=Kernel` ejecuta 30+ tests nuevos
- [ ] Todos pasan en verde con MariaDB 10.11
- [ ] CI-KERNEL-001: Pipeline CI incluye estos tests

---

### 6.3 GAP-TEST-FUNC: Functional tests 5 verticales

**Estado actual:** 84 de 91 modulos sin Functional tests (92.3%). Solo `demo` tiene functional tests significativos (24 tests).

**Testing:** 7.7% → 40% (functional)

**5 verticales prioritarios:**

| Vertical | Tests | Flujos |
|----------|-------|--------|
| empleabilidad | 5 | Crear perfil candidato, buscar ofertas, aplicar, generar CV, copilot |
| comercioconecta | 5 | Crear producto, listar marketplace, orden, pago, review |
| jarabalex | 5 | Crear caso legal, subir documento, generar contrato, copilot, buscar jurisprudencia |
| formacion | 5 | Crear curso, matricularse, completar leccion, certificado, copilot |
| content_hub | 5 | Crear articulo, publicar, RSS, buscar, copilot |

**Verificacion post-implementacion:**

- [ ] `lando phpunit --testsuite=Functional` ejecuta 25+ tests nuevos
- [ ] Todos pasan en verde

---

### 6.4 GAP-TEST-PROMPT: PromptRegression 11 agentes

**Estado actual:** 4 archivos de PromptRegression (base + 3 tests). 11 agentes Gen 2 sin regression tests.

**Testing:** 20% → 80% (IA)

**Directiva:** AGENT-GEN2-PATTERN-001 — Todos extienden SmartBaseAgent con `doExecute()`.

**Archivos a crear (11 tests):**

| Agente | Test | Que verifica |
|--------|------|-------------|
| SmartMarketing | `SmartMarketingPromptRegressionTest.php` | Output incluye CTA, tono de marca, no PII |
| Storytelling | `StorytellingPromptRegressionTest.php` | Narrativa coherente, sin alucinaciones |
| CustomerExperience | `CustomerExperiencePromptRegressionTest.php` | Empatia, resolucion, escalacion correcta |
| Support | `SupportPromptRegressionTest.php` | Categorizacion, SLA awareness, escalacion |
| ProducerCopilot | `ProducerCopilotPromptRegressionTest.php` | Contexto agro, precios, estacionalidad |
| Sales | `SalesPromptRegressionTest.php` | Deteccion de intent, upgrade path correcto |
| MerchantCopilot | `MerchantCopilotPromptRegressionTest.php` | Contexto comercio, inventario, analytics |
| SmartEmployabilityCopilot | `SmartEmployabilityCopilotPromptRegressionTest.php` | CV tips, job matching, skills gap |
| SmartLegalCopilot | `SmartLegalCopilotPromptRegressionTest.php` | No da consejo legal directo, cita fuentes |
| SmartContentWriter | `SmartContentWriterPromptRegressionTest.php` | SEO, tono de marca, estructura |
| LearningPathAgent | `LearningPathAgentPromptRegressionTest.php` | Secuencia logica, prerequisitos, nivel |

**Verificacion post-implementacion:**

- [ ] `lando phpunit --group=prompt-regression` ejecuta 11+ tests
- [ ] AI-IDENTITY-RULE: Todos verifican que la identidad IA se mantiene
- [ ] AI-GUARDRAILS-PII-001: Tests verifican que PII no aparece en output

---

### 6.5 GAP-TEST-ACCESS: AccessControl tests

**Estado actual:** 328 AccessControlHandlers con solo 3 tests (0.9%).

**Testing:** 0.9% → 20% (access control)

**Prioridad:** Entidades con `tenant_id` + entidades con datos sensibles.

**Tests a crear (minimo 15):**

| Modulo | Entity | Test verifica |
|--------|--------|---------------|
| ecosistema_jaraba_core | Tenant | Solo admin puede crear/editar |
| jaraba_billing | Subscription | Solo tenant owner puede cancelar |
| jaraba_billing | Invoice | Solo tenant members pueden ver |
| jaraba_legal_vault | VaultDocument | Solo tenant con permiso accede |
| jaraba_support | Ticket | Solo creador y agentes del tenant |
| jaraba_candidate | CandidateProfile | Solo owner edita, empleadores ven |
| jaraba_lms | Course | Solo tenant creator edita |
| jaraba_content_hub | ContentArticle | Solo autores del tenant editan |
| jaraba_page_builder | PageContent | Solo tenant con permiso edita |
| jaraba_comercio_conecta | ProductRetail | Solo merchant owner edita |

**Verificacion post-implementacion:**

- [ ] `lando phpunit --group=access-control` ejecuta 15+ tests
- [ ] Cada test verifica: create, view, update, delete con usuarios de distinto tenant

---

## 7. FASE 3: Elevacion Vertical (Semanas 5-6)

> **OBJETIVO**: Elevar uniformemente todos los verticales con CopilotBridge, Schema.org, preprocess hooks, SCSS y REST API.

### 7.1 GAP-COPILOT-5: CopilotBridgeService x5 verticales

**Estado actual:** Solo 4 verticales tienen CopilotBridgeService (ComercioConecta, AgroConecta, JarabaLex, ServiciosConecta). Faltan 5: Empleabilidad, Emprendimiento, AndaluciaEI, ContentHub, LMS.

**IA:** 50% → 100% (copilot context)

**Patron de referencia:** `ComercioConectaCopilotBridgeService` en `jaraba_comercio_conecta/src/Service/ComercioConectaCopilotBridgeService.php`.

Cada servicio DEBE implementar 3 metodos:
1. `getRelevantContext(int $userId): array` — Contexto del vertical para el copilot
2. `getSoftSuggestion(int $userId): ?array` — Sugerencia de upgrade si aplica
3. `getMarketInsights(int $userId): array` — Metricas del vertical

**5 servicios a crear:**

| Servicio | Modulo | Contexto especifico |
|----------|--------|-------------------|
| `EmpleabilidadCopilotBridgeService` | `jaraba_candidate` | Perfil completado %, candidaturas activas, skills match |
| `EmprendimientoCopilotBridgeService` | `jaraba_business_tools` | Plan de negocio %, financiacion solicitada, mentores asignados |
| `AndaluciaEiCopilotBridgeService` | `jaraba_andalucia_ei` | Solicitudes activas, estado expediente, documentos pendientes |
| `ContentHubCopilotBridgeService` | `jaraba_content_hub` | Articulos publicados, views/mes, SEO score medio |
| `FormacionCopilotBridgeService` | `jaraba_lms` | Cursos creados, alumnos matriculados, tasa completacion |

**Registro en services.yml (patron para cada modulo):**

```yaml
jaraba_candidate.copilot_bridge:
  class: Drupal\jaraba_candidate\Service\EmpleabilidadCopilotBridgeService
  arguments:
    - '@entity_type.manager'
    - '@current_user'
    - '@logger.channel.jaraba_candidate'
  tags:
    - { name: 'jaraba_ai_agents.copilot_bridge', vertical: 'empleabilidad' }
```

**Verificacion post-implementacion:**

- [ ] 5 servicios registrados: `lando drush debug:container | grep copilot_bridge`
- [ ] Cada servicio devuelve contexto valido con `getRelevantContext()`
- [ ] Los 5 estan taggeados con `jaraba_ai_agents.copilot_bridge`
- [ ] El copilot inyecta el contexto del vertical correspondiente en las respuestas

---

### 7.2 GAP-SCHEMA-8: Schema.org x8 verticales

**Estado actual:** Solo Content Hub tiene SeoService completo con JSON-LD. 8 verticales necesitan Schema.org: empleabilidad, emprendimiento, comercioconecta, agroconecta, jarabalex, serviciosconecta, andalucia_ei, formacion.

**SEO:** 20% → 80%

**Patron de referencia:** `SeoService` en `jaraba_content_hub/src/Service/SeoService.php` — Genera meta tags, Open Graph, Twitter Cards, y JSON-LD via `hook_page_attachments_alter()`.

**Tipos Schema.org por vertical:**

| Vertical | Schema.org Type(s) | Pagina(s) |
|----------|-------------------|-----------|
| empleabilidad | `Person`, `JobPosting`, `EducationalOccupation` | Perfil candidato, ofertas de empleo |
| emprendimiento | `Organization`, `LocalBusiness`, `BusinessEvent` | Perfil emprendedor, eventos |
| comercioconecta | `Product`, `Offer`, `Organization`, `AggregateRating` | Productos, tiendas |
| agroconecta | `Product`, `AggregateOffer`, `FarmerMarket` | Catalogo agro, cooperativas |
| jarabalex | `LegalService`, `Attorney`, `Document` | Despachos, casos |
| serviciosconecta | `Service`, `LocalBusiness`, `Review`, `AggregateRating` | Listados, reservas |
| andalucia_ei | `EducationEvent`, `Course`, `GovernmentService` | Programas, convocatorias |
| formacion | `Course`, `CourseInstance`, `EducationalOrganization` | Cursos, rutas de aprendizaje |

**Inyeccion via `hook_page_attachments_alter()`:**

```php
// En cada modulo o centralizado en ecosistema_jaraba_core
// Detectar la ruta activa y generar JSON-LD correspondiente
$attachments['#attached']['html_head'][] = [
  [
    '#type' => 'html_tag',
    '#tag' => 'script',
    '#attributes' => ['type' => 'application/ld+json'],
    '#value' => json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
  ],
  'schema_org_' . $entityType,
];
```

**Verificacion post-implementacion:**

- [ ] 8 verticales con JSON-LD valido: [Google Rich Results Test](https://search.google.com/test/rich-results)
- [ ] Cada JSON-LD incluye `@context`, `@type`, y propiedades requeridas
- [ ] No hay JSON-LD duplicado en la misma pagina
- [ ] Performance: JSON-LD no bloquea renderizado (es `<script>`, no render-blocking)

---

### 7.3 GAP-PREPROCESS: Entity Preprocess x4 verticales

**Estado actual:** Solo Content Hub tiene entity preprocess hooks completos. 4 verticales necesitan preprocess: JarabaLex, LMS, Empleabilidad, Emprendimiento.

**Frontend:** 40% → 90%

**Directiva:** ENTITY-PREPROCESS-001 — TODA ContentEntity renderizada en view mode DEBE tener `template_preprocess_{entity_type}()` en el `.module`. La entidad esta en `$variables['elements']['#{entity_type}']`. Extraer primitivos, resolver referencias, generar URLs de imagenes.

**Archivos a modificar:**

| Modulo | .module file | Entities |
|--------|-------------|----------|
| `jaraba_legal` | `jaraba_legal.module` | LegalCase, LegalDocument, Contract |
| `jaraba_lms` | `jaraba_lms.module` | Course, LearningPath, Certificate, Enrollment |
| `jaraba_candidate` | `jaraba_candidate.module` | CandidateProfile, CandidateEducation, CandidateExperience |
| `jaraba_business_tools` | `jaraba_business_tools.module` | BusinessPlan, StartupProfile |

**Patron (ejemplo para Course):**

```php
/**
 * Implements template_preprocess_course().
 */
function jaraba_lms_preprocess_course(array &$variables): void {
  /** @var \Drupal\jaraba_lms\Entity\CourseInterface $course */
  $course = $variables['elements']['#course'];

  // Extraer primitivos — NUNCA pasar entity objects al template (ZERO-REGION-002)
  $variables['title'] = $course->label() ?? '';
  $variables['description'] = $course->get('description')->value ?? '';
  $variables['duration_hours'] = (int) ($course->get('duration_hours')->value ?? 0);
  $variables['level'] = $course->get('level')->value ?? 'beginner';
  $variables['status'] = $course->get('status')->value;
  $variables['created'] = $course->getCreatedTime();

  // Resolver categoria referenciada
  $category = $course->get('category')->entity;
  $variables['category_name'] = $category ? ($category->label() ?? '') : '';

  // Generar URL de imagen responsive
  $image = $course->get('image')->entity;
  if ($image) {
    $imageStyle = \Drupal\image\Entity\ImageStyle::load('medium');
    if ($imageStyle) {
      $variables['image_url'] = $imageStyle->buildUrl($image->getFileUri());
    }
  }

  // Owner/instructor
  $owner = $course->getOwner();
  $variables['instructor_name'] = $owner ? $owner->getDisplayName() : '';
}
```

**Verificacion post-implementacion:**

- [ ] Templates de entities renderizan datos correctamente
- [ ] No hay `$entity->get()` en templates Twig (solo variables preprocess)
- [ ] Imagenes usan ImageStyle (no URL raw)

---

### 7.4 GAP-SCSS-3: SCSS dedicado x3 verticales

**Estado actual:** Emprendimiento, Content Hub y LMS carecen de SCSS dedicado en el modulo. Los estilos estan parcialmente en el tema global pero sin especificidad vertical.

**Frontend:** SCSS parcial → 100%

**Directivas aplicables:**
- SCSS-001: `@use '../variables' as *;` en cada parcial
- CSS-VAR-ALL-COLORS-001: CADA color con `var(--ej-*, fallback)`, NUNCA hex hardcoded
- SCSS-ENTRY-CONSOLIDATION-001: No crear `name.scss` y `_name.scss` en mismo directorio
- SCSS-COMPILE-VERIFY-001: Recompilar y verificar timestamp CSS > SCSS

**Archivos a crear:**

| Vertical | SCSS Entry | CSS Output | Library |
|----------|-----------|------------|---------|
| Emprendimiento | `scss/routes/emprendimiento.scss` | `css/routes/emprendimiento.css` | `route-emprendimiento` |
| Content Hub | `scss/routes/content-hub.scss` | `css/routes/content-hub.css` | `route-content-hub` |
| LMS/Formacion | `scss/routes/formacion.scss` | `css/routes/formacion.css` | `route-formacion` |

**Estructura SCSS (ejemplo `scss/routes/formacion.scss`):**

```scss
@use 'sass:color';
@use '../variables' as *;

// Formacion/LMS — SCSS dedicado
// Tokens: SIEMPRE var(--ej-*, fallback) — CSS-VAR-ALL-COLORS-001

.page-formacion {
  // Course cards
  .course-card {
    background: var(--ej-bg-card, #{$bg-card});
    border-radius: var(--ej-radius-lg, 12px);
    border: 1px solid var(--ej-border-color, #{$border-color});
    transition: transform 0.2s ease, box-shadow 0.2s ease;

    &:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px color-mix(in srgb, var(--ej-shadow-color, #{$shadow-color}) 15%, transparent);
    }

    &__image {
      border-radius: var(--ej-radius-lg, 12px) var(--ej-radius-lg, 12px) 0 0;
      aspect-ratio: 16/9;
      object-fit: cover;
    }

    &__title {
      color: var(--ej-text-heading, #{$text-heading});
      font-weight: 600;
    }

    &__level {
      font-size: var(--ej-font-size-xs, 0.75rem);
      color: var(--ej-text-muted, #{$text-muted});
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    &__duration {
      color: var(--ej-text-secondary, #{$text-secondary});
    }
  }

  // Progress bar
  .learning-progress {
    height: 6px;
    border-radius: 3px;
    background: var(--ej-bg-muted, #{$bg-muted});

    &__fill {
      background: var(--ej-color-success, #{$verde-innovacion});
      border-radius: 3px;
      transition: width 0.3s ease;
    }
  }
}
```

**Registro en library y hook:**

```yaml
# ecosistema_jaraba_theme.libraries.yml
route-formacion:
  css:
    theme:
      css/routes/formacion.css: {}
  dependencies:
    - ecosistema_jaraba_theme/global
```

```php
// hook_page_attachments_alter() — ruta exacta ANTES de catch-all
// SCSS-ENTRY-CONSOLIDATION-001
if ($route_name === 'jaraba_lms.courses' || str_starts_with($route_name, 'jaraba_lms.')) {
  $attachments['#attached']['library'][] = 'ecosistema_jaraba_theme/route-formacion';
}
```

**Verificacion post-implementacion:**

- [ ] `npm run build` sin errores desde `web/themes/custom/ecosistema_jaraba_theme/`
- [ ] `SCSS-COMPILE-VERIFY-001`: Timestamp de `css/routes/formacion.css` > `scss/routes/formacion.scss`
- [ ] Inspector: estilos cargados en la pagina del vertical
- [ ] Ningun color hex hardcoded en los 3 archivos SCSS nuevos

---

### 7.5 GAP-REST-API: REST API endpoints x10 verticales

**Estado actual:** 0 de 10 verticales con REST API endpoints. OpenAPI spec existe en `ecosistema_jaraba_core/openapi/` pero sin implementacion. `RateLimiterService` y `ApiKeyAuthenticationProvider` ya existen.

**API:** 0% → 80%

**Directivas aplicables:**
- API-WHITELIST-001: Endpoints con campos dinamicos DEBEN definir ALLOWED_FIELDS
- CSRF-API-001: `_csrf_request_header_token: 'TRUE'` en rutas mutantes
- TENANT-001: TODA query filtra por tenant
- ROUTE-LANGPREFIX-001: URLs via `Url::fromRoute()`

**5 endpoints minimos por vertical (CRUD + list):**

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| GET | `/api/v1/{vertical}/{type}` | Listar (paginado, filtros) |
| GET | `/api/v1/{vertical}/{type}/{id}` | Obtener uno |
| POST | `/api/v1/{vertical}/{type}` | Crear |
| PATCH | `/api/v1/{vertical}/{type}/{id}` | Actualizar |
| DELETE | `/api/v1/{vertical}/{type}/{id}` | Eliminar |

**10 verticales x 5 endpoints = 50 endpoints REST.**

**Patron de implementacion — REST Resource Plugin:**

```php
/**
 * @RestResource(
 *   id = "jaraba_lms_course",
 *   label = @Translation("LMS Course"),
 *   uri_paths = {
 *     "canonical" = "/api/v1/formacion/courses/{id}",
 *     "create" = "/api/v1/formacion/courses"
 *   }
 * )
 */
class CourseRestResource extends ResourceBase {

  // API-WHITELIST-001: Solo campos permitidos
  private const ALLOWED_FIELDS = ['title', 'description', 'category', 'level', 'duration_hours', 'status'];

  public function get($id): ResourceResponse {
    // TENANT-001: Filtrar por tenant
    $entity = $this->entityTypeManager->getStorage('course')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('id', $id)
      ->condition('tenant_id', $this->tenantContext->getCurrentTenantId())
      ->execute();
    // ...
  }
}
```

**Autenticacion y rate limiting:**

```yaml
# En routing.yml de cada modulo
jaraba_lms.api.courses.list:
  path: '/api/v1/formacion/courses'
  defaults:
    _controller: '\Drupal\jaraba_lms\Controller\CourseApiController::list'
  methods: [GET]
  requirements:
    _permission: 'access api'
    _csrf_request_header_token: 'TRUE'
  options:
    _auth: ['api_key']
```

**Verificacion post-implementacion:**

- [ ] `lando drush router:debug --format=json | jq 'map(select(.path | startswith("/api/v1/")))' | jq length` = 50+
- [ ] Peticion sin API key devuelve 401
- [ ] Peticion con API key de otro tenant devuelve 403
- [ ] Rate limiting activo: 429 despues de N peticiones
- [ ] Response en formato JSON:API

---

## 8. FASE 4: Infraestructura (Semanas 7-8)

> **OBJETIVO**: Resolver gaps de infraestructura: CDN, monitoring de colas, y busqueda facetada.

### 8.1 GAP-CDN: CDN + WebP/AVIF

**Estado actual:** PWA implementado (Service Worker + manifest), pero imagenes servidas desde origin sin CDN. Sin optimizacion WebP/AVIF.

**Performance:** 40% → 90%

**Componentes:**

1. **CDN Setup (Cloudflare):**
   - Configurar Cloudflare como proxy para assets estaticos (`/sites/default/files/styles/`, `/themes/`, `/modules/*/css/`, `/modules/*/js/`)
   - Cache headers: `Cache-Control: public, max-age=31536000, immutable` para assets con hash
   - Purge automatico en deploy via Cloudflare API

2. **WebP/AVIF Pipeline:**
   - Image styles de Drupal con conversion automatica
   - `<picture>` con `<source type="image/avif">` y `<source type="image/webp">` fallback
   - Modulo `imageapi_optimize` + `imagemagick` para conversion

3. **Critical CSS:**
   - Extraer CSS above-the-fold para las 10 landings principales
   - Inline en `<style>` tag, defer el resto

**Verificacion post-implementacion:**

- [ ] WebPageTest.org: First Contentful Paint < 1.5s
- [ ] Headers de CDN presentes: `cf-cache-status: HIT`
- [ ] Imagenes servidas como WebP en Chrome, AVIF donde soportado
- [ ] Lighthouse Performance score > 90

---

### 8.2 GAP-QUEUE-MON: Queue monitoring dashboard

**Estado actual:** 38+ QueueWorkers operando con Redis pero sin dashboard de estado. No hay visibilidad de colas atascadas, errores, o throughput.

**Operacional:** 10% → 80%

**Archivos a crear:**

| Archivo | Tipo | Descripcion |
|---------|------|-------------|
| `ecosistema_jaraba_core/src/Controller/QueueDashboardController.php` | Controller | Dashboard en `/admin/jaraba/queues` |
| `ecosistema_jaraba_core/src/Service/QueueMonitorService.php` | Servicio | Recopila metricas de 38+ workers |
| `ecosistema_jaraba_theme/templates/page--admin--jaraba--queues.html.twig` | Template | Zero Region para admin |
| `ecosistema_jaraba_theme/scss/routes/admin-queues.scss` | SCSS | Estilos del dashboard |

**Metricas a mostrar por cola:**

```
┌─────────────────────────────────────────────────────────────────┐
│  Cola                  │ Pendientes │ Procesados/h │ Errores │  │
│  ──────────────────────┼────────────┼──────────────┼─────────┤  │
│  ai_agent_execution    │     12     │     340      │    2    │  │
│  email_send            │      3     │     520      │    0    │  │
│  content_indexation    │    156     │     890      │    5    │  │
│  stripe_webhook        │      0     │     120      │    0    │  │
│  ...                   │    ...     │     ...      │   ...   │  │
└─────────────────────────────────────────────────────────────────┘
```

**Alertas automaticas:**
- Cola con > 1000 items pendientes → alerta Slack
- Cola sin procesamiento en > 30 minutos → alerta critica
- Error rate > 5% → alerta warning

**Verificacion post-implementacion:**

- [ ] `/admin/jaraba/queues` muestra 38+ colas
- [ ] Metricas actualizadas en tiempo real (o refresh cada 30s)
- [ ] ZERO-REGION-001 en template

---

### 8.3 GAP-SEARCH-FACET: Busqueda facetada + cross-vertical

**Estado actual:** RAG completo via Qdrant con busqueda semantica, pero sin UI publica de busqueda con facetas. Sin busqueda cross-vertical.

**Search:** 65% → 100%

**Componentes:**

1. **Busqueda facetada:**
   - Filtros: vertical, categoria, tipo de contenido, fecha, autor
   - UI: Slide-panel lateral con filtros + resultados principales
   - Backend: Entity Query + Qdrant para hybrid search (texto + semantica)

2. **Cross-vertical:**
   - Un unico endpoint de busqueda que consulta multiples entity types
   - Resultados unificados con score de relevancia
   - Agrupacion visual por vertical

**Archivos a crear:**

| Archivo | Tipo |
|---------|------|
| `jaraba_rag/src/Controller/SearchController.php` | Controller publico |
| `jaraba_rag/src/Service/FacetedSearchService.php` | Logica de busqueda |
| `ecosistema_jaraba_theme/templates/page--buscar.html.twig` | Template Zero Region |
| `ecosistema_jaraba_theme/templates/partials/_search-results.html.twig` | Parcial resultados |
| `ecosistema_jaraba_theme/templates/partials/_search-facets.html.twig` | Parcial facetas |
| `ecosistema_jaraba_theme/scss/routes/search.scss` | SCSS |
| `ecosistema_jaraba_theme/js/search.js` | JS: autocompletado + filtros dinamicos |

**Verificacion post-implementacion:**

- [ ] `/buscar?q=test` devuelve resultados
- [ ] Facetas filtran correctamente
- [ ] Resultados cross-vertical agrupados por vertical
- [ ] TENANT-001: Solo resultados del tenant activo
- [ ] Autocompletado funciona (Drupal.behaviors)

---

## 9. FASE 5: Clase Mundial (Semanas 9-10)

> **OBJETIVO**: Features avanzadas de internacionalizacion, pagos, accesibilidad y DX que definen una plataforma de clase mundial.

### 9.1 GAP-CURRENCY: Moneda configurable por tenant

**Estado actual:** La moneda esta hardcoded a EUR en multiples servicios de billing y pricing.

**i18n:** 0% → 80% (moneda)

**Cambios requeridos:**
1. Agregar campo `currency` (varchar 3, ISO 4217) a la entidad Tenant
2. `hook_update_N()` con `EntityDefinitionUpdateManager` (UPDATE-HOOK-REQUIRED-001)
3. Crear `CurrencyService` que resuelve la moneda del tenant actual
4. Actualizar `StripeSubscriptionService`, `InvoiceService`, `PricingService` para usar moneda dinamica
5. Twig filter `{{ price|currency }}` que formata segun moneda del tenant

**Directiva:** TENANT-BRIDGE-001 — Acceder a la moneda via `TenantBridgeService`.

**Verificacion post-implementacion:**

- [ ] `lando drush entity:updates` sin errores
- [ ] Cambiar moneda de tenant a USD muestra precios en dolares
- [ ] Stripe usa la moneda correcta al crear suscripcion
- [ ] Facturas muestran simbolo correcto

---

### 9.2 GAP-TIMEZONE: Timezone por tenant

**Estado actual:** Timezone no configurable por tenant. Todas las fechas se muestran en timezone del servidor.

**i18n:** 0% → 80% (timezone)

**Cambios requeridos:**
1. Agregar campo `timezone` (varchar 64, IANA timezone) a entidad Tenant — default: `Europe/Madrid`
2. `hook_update_N()` (UPDATE-HOOK-REQUIRED-001, UPDATE-HOOK-CATCH-001: usar `\Throwable`)
3. Crear `TenantTimezoneService` que aplica timezone del tenant en renderizado
4. Middleware que establece `date_default_timezone_set()` por request

**Directiva:** DATETIME-ARITHMETIC-001 — datetime = VARCHAR 'Y-m-d\TH:i:s', created/changed = INT Unix.

**Verificacion post-implementacion:**

- [ ] Cambiar timezone de tenant cambia las fechas mostradas
- [ ] Cron jobs mantienen timestamps en UTC internamente
- [ ] Logs muestran timezone del tenant

---

### 9.3 GAP-PRORATION: Prorrateo billing

**Estado actual:** `StripeSubscriptionService` soporta create, cancel, update, pause, resume pero sin calculo de prorrateo para upgrade/downgrade mid-cycle.

**Pagos:** 90% → 100%

**Componentes:**
1. `ProrationService` que calcula credito restante y diferencia
2. Integracion con Stripe Proration API (`proration_behavior: 'create_prorations'`)
3. UI que muestra al usuario el calculo antes de confirmar
4. Webhook handler para `invoice.updated` con proration items

**Verificacion post-implementacion:**

- [ ] Upgrade mid-cycle muestra calculo correcto antes de confirmar
- [ ] Stripe invoice incluye proration line items
- [ ] Downgrade genera credito proporcional
- [ ] Test unitario con 3 escenarios: upgrade, downgrade, cancel mid-cycle

---

### 9.4 GAP-A11Y-MODAL: Accesibilidad modales

**Estado actual:** Slide-panel y modales funcionan pero sin focus trap, sin `role="dialog"`, sin `aria-modal="true"`.

**Accesibilidad:** 70% → 90%

**Directiva:** SLIDE-PANEL-RENDER-001

**Cambios en `js/slide-panel.js`:**

```javascript
// Focus trap: Tab no sale del modal abierto
// Al abrir: guardar ultimo elemento enfocado, enfocar primer interactivo
// Al cerrar: restaurar foco al elemento original
// Escape: cierra el modal
// aria-modal="true" y role="dialog" en el contenedor
```

**Verificacion post-implementacion:**

- [ ] Tab key NO escapa del slide-panel abierto
- [ ] Escape cierra el panel
- [ ] Screen reader anuncia "Dialogo" al abrir
- [ ] Foco vuelve al boton que abrio el panel al cerrar

---

### 9.5 GAP-API-KEYS: UI self-service API keys

**Estado actual:** API keys se gestionan via admin. No hay UI self-service para que tenants generen/roten/revoquen sus propias keys.

**DX:** 0% → 80%

**Archivos a crear:**

| Archivo | Tipo |
|---------|------|
| `ecosistema_jaraba_core/src/Controller/ApiKeyManagementController.php` | Controller |
| `ecosistema_jaraba_core/src/Form/ApiKeyForm.php` | Form (PremiumEntityFormBase) |
| `ecosistema_jaraba_theme/templates/page--mi-cuenta--api-keys.html.twig` | Template |
| `ecosistema_jaraba_theme/templates/partials/_api-key-card.html.twig` | Parcial |
| `ecosistema_jaraba_theme/scss/routes/api-keys.scss` | SCSS |

**Funcionalidades:**
- Generar nueva API key (mostrar una sola vez)
- Listar keys activas (hash parcial visible)
- Revocar key existente
- Rotar key (genera nueva, invalida antigua)
- Labels personalizables por key
- Permisos por key (read-only, read-write, full)

**Verificacion post-implementacion:**

- [ ] `/mi-cuenta/api-keys` accesible para usuarios autenticados
- [ ] Generar key muestra el valor completo una sola vez
- [ ] Revocar key devuelve 401 en peticiones posteriores
- [ ] TENANT-001: Solo keys del propio tenant visibles

---

## 10. FASE 6: Excelencia (Semanas 11-12)

> **OBJETIVO**: Features que elevan la plataforma al nivel de excelencia: integraciones CRM, RTL, SDK, y dashboard de revenue.

### 10.1 GAP-CRM: Conectores HubSpot/Salesforce

**Estado actual:** No existen conectores pre-construidos para CRM externos. Los datos de leads y customers se quedan en la plataforma.

**Ecosistema:** 0% → 60%

**Patron:** Plugin system con interface `CrmConnectorInterface`:

```php
interface CrmConnectorInterface {
  public function syncContact(int $userId): bool;
  public function syncDeal(int $subscriptionId): bool;
  public function getStatus(): array;
}
```

**Implementaciones:**
- `HubSpotConnector` — via HubSpot API v3 (contacts, deals, companies)
- `SalesforceConnector` — via Salesforce REST API (Account, Contact, Opportunity)

**Directivas:**
- SECRET-MGMT-001: API keys/tokens en `getenv()`, NUNCA en config/sync/
- API-WHITELIST-001: Solo campos definidos se sincronizan
- TENANT-001: Cada tenant configura sus propias credenciales CRM

---

### 10.2 GAP-RTL: Soporte RTL basico

**Estado actual:** Sin soporte para idiomas RTL (arabe, hebreo). Todo el CSS asume LTR.

**i18n:** 0% → 50%

**Cambios requeridos:**
1. `dir="rtl"` dinamico en `html.html.twig` basado en idioma activo
2. CSS logical properties: `margin-inline-start` en vez de `margin-left`
3. Variables SCSS para RTL: `--ej-dir-start: left` / `right`
4. Auditoria de 99 archivos SCSS para reemplazar propiedades fisicas por logicas

**Verificacion:**

- [ ] Cambiar idioma a arabe invierte el layout correctamente
- [ ] Textos alineados a la derecha
- [ ] Iconos de navegacion invertidos

---

### 10.3 GAP-SDK: SDK Python + JS

**Estado actual:** API REST existe (sera implementada en Fase 3) pero sin SDK client libraries.

**DX:** 0% → 60%

**Repositorios separados:**
- `jaraba-sdk-python` — PyPI package
- `jaraba-sdk-js` — NPM package

**Estructura del SDK Python:**

```python
from jaraba import JarabaClient

client = JarabaClient(api_key="jrb_...", base_url="https://api.jaraba.es")

# CRUD
courses = client.lms.courses.list(limit=10)
course = client.lms.courses.create(title="Nuevo curso", level="beginner")

# AI
response = client.copilot.chat("Sugiere temas para mi curso")
```

**Verificacion:**

- [ ] `pip install jaraba-sdk` instala sin errores
- [ ] `npm install @jaraba/sdk` instala sin errores
- [ ] Tests de integracion pasan contra API real

---

### 10.4 GAP-REVENUE-DASH: Dashboard MRR/ARR/churn

**Estado actual:** `FinancialDashboardController` existe en `/admin/jaraba/financial` pero no calcula MRR/ARR/churn en tiempo real.

**Admin:** 75% → 100%

**Metricas a implementar:**

| Metrica | Calculo | Fuente |
|---------|---------|--------|
| MRR | Suma de suscripciones activas x precio mensual | Subscription entity |
| ARR | MRR x 12 | Calculado |
| Churn Rate | Cancelaciones/mes / Activos inicio de mes | Subscription + AuditLog |
| LTV | ARPU / Churn Rate | Calculado |
| Net Revenue Retention | (MRR + expansion - churn - contraction) / MRR inicio | Calculado |
| ARPU | MRR / Active Subscribers | Calculado |

**Directivas:**
- ZERO-REGION-001: Template limpio
- SCSS con tokens inyectables
- Charts via vanilla JS (NO React/Vue — CLAUDE.md)

**Verificacion:**

- [ ] `/admin/jaraba/revenue` muestra 6 metricas
- [ ] Datos se calculan desde entities reales, no datos dummy
- [ ] Graficos de tendencia (ultimos 12 meses)
- [ ] Export CSV funcional

---

## 11. Directrices de Aplicacion Transversal

> **IMPORTANTE**: TODAS las implementaciones de este plan DEBEN cumplir las directrices de esta seccion. Cada directriz incluye: nombre, regla, ejemplo correcto/incorrecto, archivo patron de referencia, y checklist.

### 11.1 Zero Region Pattern

**Regla:** Las paginas frontend NUNCA usan `{{ page.content }}` ni `{{ page.sidebar }}`. En su lugar, usan `{{ clean_content }}` (extrae solo `system_main_block`) y `{{ clean_messages }}` (extrae `system_messages_block`).

**Directivas:** ZERO-REGION-001, ZERO-REGION-002, ZERO-REGION-003

```twig
{# ✅ CORRECTO — Zero Region #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}
<main id="main-content" role="main">
  {{ clean_messages }}
  {{ clean_content }}
</main>
{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}

{# ❌ INCORRECTO — Drupal clasico #}
{{ page.header }}
{{ page.content }}
{{ page.sidebar_first }}
{{ page.footer }}
```

**Archivo patron de referencia:** `web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig`

**Reglas asociadas:**
- ZERO-REGION-001: Variables via `hook_preprocess_page()`, NUNCA desde controller
- ZERO-REGION-002: NUNCA pasar entity objects como non-`#` keys en render arrays
- ZERO-REGION-003: `#attached` del controller NO se procesa; usar `$variables['#attached']` en preprocess

**Checklist:**
- [ ] Template usa `{{ clean_content }}` (no `{{ page.content }}`)
- [ ] Controller devuelve `['#type' => 'markup', '#markup' => '']`
- [ ] Variables inyectadas via `hook_preprocess_page()`
- [ ] drupalSettings via preprocess, no controller
- [ ] Solo primitivos pasados al template (strings, ints, arrays), no entity objects

---

### 11.2 SCSS con Dart Sass + variables inyectables desde UI

**Regla:** Todo color DEBE usar `var(--ej-*, fallback)`. Imports con `@use` (NUNCA `@import`). Color-mix en vez de rgba() para transparencias.

**Directivas:** SCSS-001, CSS-VAR-ALL-COLORS-001, SCSS-COLORMIX-001, SCSS-ENTRY-CONSOLIDATION-001, SCSS-COMPILE-VERIFY-001

```scss
// ✅ CORRECTO — Dart Sass moderno + tokens inyectables
@use 'sass:color';
@use '../variables' as *;

.component {
  color: var(--ej-text-color, #{$text-color});
  background: var(--ej-bg-surface, #{$bg-surface});
  border: 1px solid var(--ej-border-color, #{$border-color});
  box-shadow: 0 4px 12px color-mix(in srgb, var(--ej-shadow-color, #{$shadow-color}) 10%, transparent);
}

// ❌ INCORRECTO
@import 'variables';  // Deprecated, usar @use
.component {
  color: #333333;  // Hex hardcoded, usar var(--ej-*)
  background: rgba(0, 0, 0, 0.1);  // Usar color-mix()
}
```

**Archivo patron de referencia:** `web/themes/custom/ecosistema_jaraba_theme/scss/_variables.scss`

**Variables CSS Custom Properties inyectadas:**
- `--ej-*` (35+ variables configurables desde Apariencia > Ecosistema Jaraba Theme)
- Colores de marca: `--ej-azul-corporativo: #233D63`, `--ej-naranja-impulso: #FF8C42`, `--ej-verde-innovacion: #00A9A5`
- Los tenants pueden sobreescribir via `TenantThemeConfig` entity

**Checklist:**
- [ ] `@use '../variables' as *;` al inicio de cada parcial
- [ ] CERO hex hardcoded — todo `var(--ej-*, fallback)`
- [ ] `color-mix()` en vez de `rgba()` para transparencias
- [ ] No existe `name.scss` y `_name.scss` en mismo directorio
- [ ] `npm run build` sin errores
- [ ] Timestamp CSS > SCSS despues de compilar

---

### 11.3 Textos siempre traducibles

**Regla:** TODOS los textos visibles al usuario DEBEN ser traducibles en las 3 capas:

```
┌──────────────────────────────────────────────────────────────────────┐
│  CAPA      │ PATRON CORRECTO           │ PATRON INCORRECTO          │
│  ──────────┼───────────────────────────┼──────────────────────────── │
│  Twig      │ {% trans %}Texto{% endtrans %} │ Texto (literal)        │
│            │                           │ {{ 'Texto'|t }} (filtro)   │
│  JS        │ Drupal.t('Texto')         │ 'Texto' (literal)         │
│  PHP       │ $this->t('Texto')         │ 'Texto' (literal)         │
│            │ t('Texto') en .module     │ new TranslatableMarkup()   │
└──────────────────────────────────────────────────────────────────────┘
```

**Notas criticas:**
- En Twig: usar bloque `{% trans %}...{% endtrans %}` (NO filtro `|t`)
- En JS: `Drupal.t('Texto')` — requiere libreria `core/drupal`
- En PHP: `$this->t()` en clases con `StringTranslationTrait`, `t()` en `.module` files
- Variables dentro de textos: `{% trans %}Hola {{ name }}{% endtrans %}` (Twig), `$this->t('Hola @name', ['@name' => $name])` (PHP)

**Checklist:**
- [ ] `grep -rn ">\w\+<" templates/` — buscar textos literales sin {% trans %}
- [ ] `grep -rn "'[A-Z][a-z]" *.js` — buscar strings literales en JS sin Drupal.t()
- [ ] Revision manual de nuevos templates y JS

---

### 11.4 PremiumEntityFormBase

**Regla:** TODA entity form DEBE extender `PremiumEntityFormBase` (NUNCA `ContentEntityForm` directamente). DEBE implementar `getSectionDefinitions()` y `getFormIcon()`.

**Directiva:** PREMIUM-FORMS-PATTERN-001

```php
// ✅ CORRECTO
class CourseForm extends PremiumEntityFormBase {

  protected function getSectionDefinitions(): array {
    return [
      'content' => [
        'label' => $this->t('Contenido'),
        'icon' => ['category' => 'education', 'name' => 'course'],
        'description' => $this->t('Informacion principal del curso'),
        'fields' => ['title', 'description', 'body', 'image'],
      ],
      'settings' => [
        'label' => $this->t('Configuracion'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Nivel, duracion y requisitos'),
        'fields' => ['level', 'duration_hours', 'prerequisites', 'category'],
      ],
    ];
  }

  protected function getFormIcon(): array {
    return ['category' => 'education', 'name' => 'course'];
  }
}

// ❌ INCORRECTO — NUNCA hacer esto
class CourseForm extends ContentEntityForm {
  // ...
}
```

**Archivo patron de referencia:** `web/modules/custom/ecosistema_jaraba_core/src/Form/PremiumEntityFormBase.php`

**DI via `parent::create()` — 4 patrones de migracion:**
- Patron A (Simple): Form sin DI adicional
- Patron B (Computed): Form con campos calculados `#disabled = TRUE`
- Patron C (DI): Form con servicios inyectados via `create()`
- Patron D (Custom Logic): Form con logica personalizada en `save()`

**Restricciones:**
- Fieldsets / `<details>` groups PROHIBIDOS (PremiumEntityFormBase usa glass-cards con nav pills)
- Campos computados: `#disabled = TRUE` (no editable pero visible)

**Checklist:**
- [ ] Form extiende `PremiumEntityFormBase`
- [ ] Implementa `getSectionDefinitions()` con todas las secciones
- [ ] Implementa `getFormIcon()` con icono valido
- [ ] No usa fieldsets ni details
- [ ] DI via `parent::create()`

---

### 11.5 Slide-Panel para CRUD

**Regla:** TODA accion de crear/editar/ver en frontend DEBE abrirse en slide-panel, no navegar fuera de la pagina actual.

**Directivas:** SLIDE-PANEL-RENDER-001, FORM-CACHE-001

```php
// ✅ CORRECTO — Slide-panel rendering
class MyController extends ControllerBase {

  public function editForm(Request $request): Response {
    $form = $this->entityFormBuilder()->getForm($entity, 'edit');
    // SLIDE-PANEL-RENDER-001: Usar renderPlain() (NO render())
    $form['#action'] = $request->getRequestUri();
    $html = $this->renderer->renderPlain($form);
    return new Response($html);
  }

  // Deteccion de slide-panel request
  protected function isSlidePanelRequest(Request $request): bool {
    return $request->isXmlHttpRequest() && !$request->query->has('_wrapper_format');
  }
}

// ❌ INCORRECTO
$html = $this->renderer->render($form); // BigPipe placeholders no resueltos
$form_state->setCached(TRUE); // LogicException en GET/HEAD
```

**Archivo patron de referencia:** `js/slide-panel.js` (en el tema)

**Checklist:**
- [ ] Controller usa `renderPlain()` (no `render()`)
- [ ] `$form['#action']` establecido explicitamente
- [ ] No hay `setCached(TRUE)` incondicional
- [ ] Deteccion correcta: `isXmlHttpRequest() && !_wrapper_format`
- [ ] JS usa `Drupal.behaviors` para inicializar slide-panel

---

### 11.6 Iconos ICON-CONVENTION-001 + paleta Jaraba

**Regla:** Todos los iconos DEBEN usar la funcion `jaraba_icon()` con variante duotone por defecto y colores exclusivamente de la paleta Jaraba.

**Directivas:** ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001, ICON-CANVAS-INLINE-001, ICON-EMOJI-001

```twig
{# ✅ CORRECTO — Funcion jaraba_icon con paleta Jaraba #}
{{ jaraba_icon('education', 'course', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
{{ jaraba_icon('commerce', 'cart', { variant: 'duotone', color: 'naranja-impulso' }) }}
{{ jaraba_icon('ui', 'settings', { variant: 'outline', color: 'neutral' }) }}

{# ❌ INCORRECTO #}
<i class="fa fa-book"></i>  {# Font Awesome — NO usar #}
📚  {# Emoji Unicode — ICON-EMOJI-001: NO en canvas/page builder #}
{{ jaraba_icon('education', 'course', { color: '#FF0000' }) }}  {# Color fuera de paleta #}
```

**Colores permitidos (ICON-COLOR-001):**
- `azul-corporativo` (#233D63)
- `naranja-impulso` (#FF8C42)
- `verde-innovacion` (#00A9A5)
- `white` (#FFFFFF)
- `neutral` (gris contextual)

**Variantes:**
- `duotone` (default — ICON-DUOTONE-001)
- `outline` (solo contextos minimalistas)

**SVG en canvas (ICON-CANVAS-INLINE-001):** Hex explicito en `stroke`/`fill`, NUNCA `currentColor`.

**Checklist:**
- [ ] Todos los iconos nuevos usan `jaraba_icon()`
- [ ] Variante default: duotone
- [ ] Colores solo de la paleta permitida
- [ ] No hay emojis Unicode como iconos en Page Builder

---

### 11.7 Body classes via hook_preprocess_html()

**Regla:** Las body classes se agregan SIEMPRE via `hook_preprocess_html()` en PHP. NUNCA usar `attributes.addClass()` en templates Twig.

```php
// ✅ CORRECTO — en .module o Hook class
function mymodule_preprocess_html(array &$variables): void {
  $route = \Drupal::routeMatch()->getRouteName();
  if (str_starts_with($route, 'ecosistema_jaraba_core.legal.')) {
    $variables['attributes']['class'][] = 'page-legal';
  }
}

// ❌ INCORRECTO — en template Twig
{# NUNCA hacer esto: #}
{% set body_classes = body_classes|merge(['page-legal']) %}
```

**Archivo patron de referencia:** `ecosistema_jaraba_theme.theme` (funcion `ecosistema_jaraba_theme_preprocess_html()`)

**Checklist:**
- [ ] Body classes solo en `hook_preprocess_html()`
- [ ] Templates Twig NO contienen `addClass()` ni manipulacion de `body_classes`

---

### 11.8 Field UI + Views integration

**Regla:** TODA entity con `field_ui_base_route` DEBE tener default local task tab. TODA entity DEBE declarar `"views_data"` en anotacion.

**Directivas:** FIELD-UI-SETTINGS-TAB-001, Views integration

```php
// ✅ CORRECTO — Anotacion de entity
/**
 * @ContentEntityType(
 *   ...
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\mymodule\Form\MyEntityForm",
 *     },
 *   },
 *   field_ui_base_route = "entity.my_entity.settings",
 *   ...
 * )
 */
```

```yaml
# ✅ CORRECTO — Default local task
entity.my_entity.settings:
  path: '/admin/structure/my-entity/settings'
  defaults:
    _form: '\Drupal\mymodule\Form\MyEntitySettingsForm'
    _title: 'Settings'
  requirements:
    _permission: 'administer my_entity'

entity.my_entity.settings_tab:
  route_name: entity.my_entity.settings
  title: 'Settings'
  base_route: entity.my_entity.settings
  weight: -10
```

**Checklist:**
- [ ] Entity anotacion incluye `"views_data" = "Drupal\views\EntityViewsData"`
- [ ] Entity con `field_ui_base_route` tiene default local task
- [ ] Views data accesible en `/admin/structure/views`

---

### 11.9 Navegacion admin: /admin/structure + /admin/content

**Regla:** Las entidades de configuracion van en `/admin/structure/`, las entidades de contenido van en `/admin/content/`.

```yaml
# ✅ CORRECTO
# Entity de configuracion → /admin/structure/
entity.my_config_entity.collection:
  path: '/admin/structure/my-config-entities'

# Entity de contenido → /admin/content/
entity.my_content_entity.collection:
  path: '/admin/content/my-content-entities'

# ❌ INCORRECTO
entity.my_content_entity.collection:
  path: '/admin/my-content-entities'  # Sin namespace adecuado
```

**Checklist:**
- [ ] Entidades de configuracion accesibles desde `/admin/structure/`
- [ ] Entidades de contenido accesibles desde `/admin/content/`
- [ ] Links de menu en el admin toolbar correspondiente

---

### 11.10 RUNTIME-VERIFY-001: 12 checks post-implementacion

**Regla:** Tras completar CUALQUIER feature de este plan, ejecutar los 12 checks de verificacion runtime. La diferencia entre "el codigo existe" y "el usuario lo experimenta" REQUIERE verificacion en CADA capa.

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                    12 CHECKS DE VERIFICACION RUNTIME                           │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                                │
│   #  │ Check                              │ Capa       │ Comando              │
│   ───┼────────────────────────────────────┼────────────┼───────────────────── │
│   1  │ CSS compilado (timestamp > SCSS)    │ Frontend   │ ls -la css/ scss/    │
│   2  │ Tablas DB creadas (si aplica)       │ DB         │ lando drush sqlq     │
│   3  │ Rutas accesibles en routing.yml     │ Routing    │ lando drush rr       │
│   4  │ data-* selectores JS ↔ HTML        │ Frontend   │ grep -rn data-       │
│   5  │ drupalSettings inyectado            │ Frontend   │ DOM inspector        │
│   6  │ Entity schema actualizado           │ DB         │ lando drush entup    │
│   7  │ Config importada                    │ Config     │ lando drush cim -y   │
│   8  │ Servicios DI resuelven              │ PHP        │ lando drush cr       │
│   9  │ Permisos de archivos correctos      │ Server     │ ls -la               │
│  10  │ Cache rebuildeada                   │ PHP        │ lando drush cr       │
│  11  │ Templates renderizados sin error    │ Twig       │ Navegacion manual    │
│  12  │ JS sin errores en consola           │ JS         │ DevTools Console     │
│                                                                                │
│   > La diferencia entre "el codigo existe" y "el usuario lo experimenta"       │
│   > requiere verificacion en CADA capa:                                        │
│   > PHP → Twig → SCSS → CSS compilado → JS → drupalSettings → DOM final      │
│                                                                                │
└──────────────────────────────────────────────────────────────────────────────┘
```

**Checklist rapida por implementacion:**

```bash
# 1. Compilar SCSS y verificar timestamp
cd web/themes/custom/ecosistema_jaraba_theme && npm run build
ls -la css/routes/ scss/routes/  # CSS mas reciente que SCSS

# 2. Verificar DB
lando drush entity:updates  # Debe decir "No changes"

# 3. Verificar rutas
lando drush router:debug | grep <nombre_ruta>

# 4. Verificar config
lando drush config:import --diff  # Sin cambios pendientes

# 5. Rebuild cache
lando drush cr

# 6. Verificar en navegador
# - Abrir DevTools > Console: 0 errores
# - Verificar que drupalSettings contiene los datos esperados
# - Verificar que el template renderiza correctamente
# - Verificar responsiveness en 3 breakpoints
```

---

## 12. Verificacion End-to-End

### 12.1 Checklist por fase

#### Fase 0 — Bloqueantes

- [ ] 4 paginas legales accesibles publicamente (200 OK)
- [ ] CSRF fix en 3 rutas DELETE de legal_vault
- [ ] XSS fix en _support-message-bubble.html.twig
- [ ] Email de verificacion se envia al registrar
- [ ] Link de unsubscribe en footer de emails + header List-Unsubscribe
- [ ] Zero Region en todos los templates nuevos
- [ ] SCSS compilado sin errores

#### Fase 1 — Monetizacion

- [ ] FormacionFeatureGateService registrado y funcional
- [ ] 20 YAMLs FreemiumVL de formacion importados
- [ ] ContentHubFeatureGateService registrado y funcional
- [ ] 12 YAMLs FreemiumVL de content hub importados
- [ ] aria-label en 67 parciales de templates/partials/
- [ ] Focus visible CSS activo globalmente
- [ ] Centro de preferencias de notificaciones accesible

#### Fase 2 — Testing

- [ ] 10 tests de aislamiento multi-tenant pasando
- [ ] 30+ kernel tests nuevos pasando
- [ ] 25+ functional tests nuevos pasando
- [ ] 11 PromptRegression tests pasando
- [ ] 15+ AccessControl tests pasando
- [ ] CI pipeline ejecuta todos los tests nuevos

#### Fase 3 — Elevacion

- [ ] 5 CopilotBridgeService registrados y taggeados
- [ ] 8 verticales con Schema.org JSON-LD valido
- [ ] 4 verticales con entity preprocess hooks completos
- [ ] 3 SCSS de verticales compilados
- [ ] 50+ REST API endpoints accesibles
- [ ] Rate limiting activo en API

#### Fase 4 — Infraestructura

- [ ] CDN configurado: headers cf-cache-status presentes
- [ ] WebP/AVIF sirviendo imagenes optimizadas
- [ ] Queue dashboard en /admin/jaraba/queues
- [ ] Busqueda facetada en /buscar funcional
- [ ] Cross-vertical search funcional

#### Fase 5 — Clase Mundial

- [ ] Moneda configurable por tenant (EUR, USD, GBP minimo)
- [ ] Timezone por tenant funcional
- [ ] Prorrateo billing calculado correctamente
- [ ] Modales con focus trap + role="dialog"
- [ ] UI de API keys en /mi-cuenta/api-keys

#### Fase 6 — Excelencia

- [ ] Conector HubSpot sincroniza contacto de test
- [ ] Conector Salesforce sincroniza contacto de test
- [ ] RTL basico funcional con idioma arabe
- [ ] SDK Python instalable + test integracion
- [ ] SDK JS instalable + test integracion
- [ ] Dashboard revenue en /admin/jaraba/revenue con datos reales

---

### 12.2 Comandos de verificacion (lando)

```bash
# === COMPILACION ===
# Recompilar SCSS
cd web/themes/custom/ecosistema_jaraba_theme && npm run build

# Verificar timestamp CSS > SCSS
find css/ -name "*.css" -newer scss/main.scss

# === DB / SCHEMA ===
# Verificar schema de entidades
lando drush entity:updates

# Ejecutar hooks de actualizacion
lando drush updatedb --no-post-updates
lando drush updatedb

# === CONFIG ===
# Importar config
lando drush config:import -y

# Verificar config especifica
lando drush config:get jaraba_lms.freemium_vertical_limit.formacion_free_courses_limit

# Listar FreemiumVL configs
lando drush config:list | grep freemium_vertical_limit

# === ROUTING ===
# Verificar rutas
lando drush router:debug | grep legal
lando drush router:debug | grep api/v1

# === SERVICIOS ===
# Verificar servicios registrados
lando drush debug:container | grep feature_gate
lando drush debug:container | grep copilot_bridge

# Cache rebuild
lando drush cr

# === TESTING ===
# Todos los tests
lando phpunit

# Por suite
lando phpunit --testsuite=Unit
lando phpunit --testsuite=Kernel
lando phpunit --testsuite=Functional

# Por grupo
lando phpunit --group=tenant-isolation
lando phpunit --group=access-control
lando phpunit --group=prompt-regression

# === VALIDACION ARQUITECTONICA ===
# Rapida (pre-commit)
lando validate-fast

# Completa (CI/deploy)
lando validate

# === SEGURIDAD ===
# Verificar CSRF en routing
grep -rn '_csrf_request_header_token' web/modules/custom/jaraba_legal_vault/

# Verificar |raw en templates
grep -rn '|raw' web/themes/custom/ecosistema_jaraba_theme/templates/partials/_support*

# === FRONTEND ===
# Verificar accesibilidad
# Lighthouse CLI (si disponible)
lighthouse https://jaraba-saas.lndo.site/es/ --only-categories=accessibility --output=json

# Verificar aria-labels
grep -rn 'aria-label' web/themes/custom/ecosistema_jaraba_theme/templates/partials/
```

---

### 12.3 Criterios de aceptacion

| Criterio | Metrica | Objetivo |
|----------|---------|----------|
| Seguridad | 0 vulnerabilidades criticas | Fase 0 completada |
| Compliance | 4 paginas legales publicas | Fase 0 completada |
| Revenue | FeatureGate en 10/10 verticales | Fase 1 completada |
| Testing Kernel | 50%+ modulos con kernel tests | Fase 2 completada |
| Testing Functional | 40%+ modulos con functional tests | Fase 2 completada |
| Testing Tenant | 100% entities con tenant_id testeadas | Fase 2 completada |
| API REST | 50 endpoints accesibles | Fase 3 completada |
| Schema.org | 10/10 verticales con JSON-LD | Fase 3 completada |
| Performance | Lighthouse > 90 | Fase 4 completada |
| Accesibilidad | Lighthouse a11y > 90 | Fase 5 completada |
| i18n | Moneda + timezone por tenant | Fase 5 completada |
| DX | SDK publicado (Python + JS) | Fase 6 completada |
| Score Global | 100% en las 11 dimensiones | Todas las fases |

---

## 13. Historico de Versiones

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-03-03 | 1.0.0 | Creacion — Plan completo de implementacion para 26 gaps en 6 fases (12 semanas) |

---

> **Nota final**: Este documento es la guia tecnica completa para el equipo de desarrollo. Cada gap incluye archivos concretos, patrones de referencia existentes en el codebase, y verificacion post-implementacion. La filosofia "Sin Humo" del proyecto se refleja en que NADA se asume implementado hasta que pasa RUNTIME-VERIFY-001.
