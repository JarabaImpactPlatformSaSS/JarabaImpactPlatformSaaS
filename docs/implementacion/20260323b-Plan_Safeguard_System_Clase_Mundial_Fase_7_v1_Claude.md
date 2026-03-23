# Plan de Implementacion: Safeguard System Fase 7 — Clase Mundial
# Jaraba Impact Platform SaaS

**Fecha:** 2026-03-23
**Version:** 1.0.0
**Autor:** Claude (Opus 4.6)
**Estado:** Propuesta
**Prioridad:** P0-P2 (escalonada)
**Estimacion:** 3 sprints (P0: Sprint 1, P1: Sprint 2, P2: Sprint 3)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual del Sistema de Salvaguarda](#2-estado-actual-del-sistema-de-salvaguarda)
3. [Auditoria Integral — Hallazgos por Area](#3-auditoria-integral--hallazgos-por-area)
   - 3.1 [Theming y SCSS](#31-theming-y-scss)
   - 3.2 [GrapesJS / Page Builder](#32-grapesjs--page-builder)
   - 3.3 [Setup Wizard + Daily Actions](#33-setup-wizard--daily-actions)
   - 3.4 [Conversion y Frontend](#34-conversion-y-frontend)
   - 3.5 [Entities e Integracion](#35-entities-e-integracion)
   - 3.6 [Seguridad Multi-Tenant](#36-seguridad-multi-tenant)
   - 3.7 [Documentacion](#37-documentacion)
4. [Gap Analysis — 18 Medidas de Salvaguarda Propuestas](#4-gap-analysis--18-medidas-de-salvaguarda-propuestas)
   - 4.1 [Prioridad P0 — Criticos (Sprint 1)](#41-prioridad-p0--criticos-sprint-1)
   - 4.2 [Prioridad P1 — Importantes (Sprint 2)](#42-prioridad-p1--importantes-sprint-2)
   - 4.3 [Prioridad P2 — Mejoras (Sprint 3)](#43-prioridad-p2--mejoras-sprint-3)
5. [Tabla de Correspondencia Tecnica](#5-tabla-de-correspondencia-tecnica)
6. [Directrices de Aplicacion](#6-directrices-de-aplicacion)
7. [Plan de Ejecucion Detallado](#7-plan-de-ejecucion-detallado)
   - 7.1 [Sprint 1 — P0 Criticos](#71-sprint-1--p0-criticos)
   - 7.2 [Sprint 2 — P1 Importantes](#72-sprint-2--p1-importantes)
   - 7.3 [Sprint 3 — P2 Mejoras](#73-sprint-3--p2-mejoras)
8. [Metricas de Exito](#8-metricas-de-exito)
9. [Riesgos y Mitigaciones](#9-riesgos-y-mitigaciones)
10. [Glosario](#10-glosario)

---

## 1. Resumen Ejecutivo

### Contexto

El Sistema de Salvaguarda de Jaraba Impact Platform ha alcanzado una madurez del **87%** con 104 validators (88 run + 16 warn), 6 capas de defensa, y 0 validators huerfanos. La auditoria exhaustiva realizada el 2026-03-23 — abarcando theming, GrapesJS, Setup Wizard, conversion, entities y seguridad multi-tenant — ha identificado **18 medidas de salvaguarda adicionales** necesarias para alcanzar el objetivo del **95% de madurez** (clase mundial).

### Resultado de la Auditoria

| Area Auditada | Score Actual | Target | Estado |
|---------------|-------------|--------|--------|
| Theming y SCSS | 95% | 98% | 6 route SCSS stale |
| GrapesJS / Page Builder | 90% | 95% | Sanitizador duplicado |
| Setup Wizard + Daily Actions | 100% | 100% | Completo |
| Conversion (Homepage) | 15/15 | 15/15 | Completo |
| Conversion (Case Study) | 15/15 | 15/15 | Completo |
| Entities e Integracion | 95% | 98% | 130 handlers sin return type |
| Seguridad Multi-Tenant | 90% | 95% | Views isolation gap |
| Documentacion | 92% | 95% | Version drift en tabla raiz |
| Runtime Self-Checks | 88% | 95% | 11 modulos sin hook_requirements |
| Safeguard Scripts | 100% | 100% | 0 orphans |

### Impacto Esperado

- **Seguridad**: Cierre de vector de fuga de datos cross-tenant via Views
- **Calidad**: 130 AccessControlHandlers con return type correcto (PHPStan compliant)
- **Fiabilidad**: 11 modulos con self-checks en produccion (de 83 a 94)
- **Automatizacion**: 6 nuevos validators + 2 CI gates + 3 hook_requirements batches

---

## 2. Estado Actual del Sistema de Salvaguarda

### 6 Capas de Defensa (v2026-03-23)

| Capa | Mecanismo | Cobertura | Madurez |
|------|-----------|-----------|---------|
| 1 | 104 scripts validacion (88 run + 16 warn) | On demand + CI | 100% |
| 2 | Pre-commit Husky + lint-staged (9 hooks) | PHP/SCSS/MD/Twig/JS/services.yml/routing.yml | 90% |
| 3 | CI Pipeline Gates (ci.yml + fitness-functions.yml) | PHPStan L6, tests, security, 26 arch checks | 95% |
| 4 | Runtime Self-Checks (hook_requirements) | 83/94 modulos (88%) | 75% |
| 5 | IMPLEMENTATION-CHECKLIST-001 | Al completar features | 85% |
| 6 | PIPELINE-E2E-001 (4 capas L1-L4) | Dashboards con UI | 90% |

### Metricas Clave

- **Total validators**: 104 PHP scripts en `scripts/validation/`
- **Orphaned validators**: 0 (VALIDATOR-COVERAGE-001)
- **Pre-commit hooks**: 9 patterns file-triggered
- **CI blocking checks**: 88
- **CI non-blocking warns**: 16
- **Modulos con hook_requirements**: 83 de 94 (88.3%)
- **Entity integrity**: 463/463 PASS
- **Premium forms migradas**: 271/271 (100%)
- **AccessControlHandlers**: 346 (77.5% explicitos, 22.5% default)
- **Twig includes con 'only'**: 314/473 (66.4%)

---

## 3. Auditoria Integral — Hallazgos por Area

### 3.1 Theming y SCSS

**Score: 95%** — Arquitectura clase mundial con gaps menores.

**Fortalezas verificadas:**
- 107 parciales SCSS organizados en components/routes/bundles
- 290+ CSS custom properties con prefijo `--ej-*`
- 16 route SCSS + 8 bundles para code splitting
- 972 iconos SVG en 28 categorias con duotone/outline + fallback cascade
- 70+ opciones configurables desde UI via TenantThemeConfig
- Cascada 5 niveles: SCSS Tokens → CSS Custom Properties → Component Tokens → Tenant Override → Vertical Presets
- Dart Sass moderno con @use (NO @import)
- color-mix() para runtime alpha (SCSS-COLORMIX-001)

**Gaps identificados:**
1. **6 route SCSS stale** (formacion, legal-pages, emprendimiento, landing, tenant-settings, tenant-dashboard) — compilados entre 2026-03-04 y 2026-03-10, potencialmente sin cambios de `_variables.scss` recientes
2. **config/install/ecosistema_jaraba_theme.settings.yml no existe** — defaults hardcoded en hook_form_alter
3. **Bundle vs Route overlap no documentado** — logica de prioridad de attachment no explicita

**Recomendacion:** Recompilar routes stale con `npm run build:routes` y verificar freshness con SCSS-COMPILE-FRESHNESS-001.

### 3.2 GrapesJS / Page Builder

**Score: 90%** — Arquitectura hibrida madura con 14 plugins custom.

**Fortalezas verificadas:**
- GrapesJS 5.6 embebido (vendor local, NO CDN)
- 14 plugins custom (canvas, blocks, icons, AI, SEO, reviews, legal, marketplace, multipage, command-palette, thumbnails, onboarding, assets, partials)
- SAFEGUARD-CANVAS-001 operativo (4 capas: presave monitoring, backup/restore CLI, translation reload, validation)
- CANVAS-ARTICLE-001: Content Hub reutiliza engine via library dependency (zero duplicacion)
- Dual architecture: GrapesJS script functions (editor) + Drupal behaviors (frontend publicado)
- Sanitizacion HTML completa: elimina scripts, event handlers, javascript: URIs, data-gjs-*, gjs-* classes

**Gaps identificados:**
1. **Sanitizador duplicado** — `CanvasApiController::sanitizePageBuilderHtml()` duplicado en `ArticleCanvasApiController`. Deberia extraerse a `CanvasSanitizationService` o trait compartido
2. **Plugin registry sin validacion** — No hay check de que todos los plugins declarados en libraries.yml realmente cargan. Fallo silencioso si un JS falla
3. **Canvas revision history no automatico** — Presave hook loguea pero no crea revision. Backup requiere invocacion manual
4. **AI content validation ausente** — Plugin AI genera bloques pero no valida JSON schema post-generacion

**Recomendacion:** Extraer sanitizador a servicio compartido (P1). Plugin validation como nuevo safeguard (P2).

### 3.3 Setup Wizard + Daily Actions

**Score: 100%** — Patron clase mundial completamente implementado.

**Fortalezas verificadas:**
- 9/9 verticales canonicos con wizard + daily actions (100%)
- 19 IDs totales (9 canonical + 10 features/roles/billing)
- 52 wizard steps + 39 daily actions
- ZEIGARNIK-PRELOAD-001 operativo: 2 global auto-complete steps (weight -20, -10)
- PIPELINE-E2E-001: 16 controllers, 13 hook_theme declarations, 17 templates
- Templates reutilizables: `_setup-wizard.html.twig` (203 lineas) + `_daily-actions.html.twig` (88 lineas)
- TWIG-INCLUDE-ONLY-001 en ambos parciales
- WCAG 2.1 AA: aria-labels, focus-visible, 44px touch targets
- Animaciones con prefers-reduced-motion

**Sin gaps.** Pattern es production-ready y extensible.

### 3.4 Conversion y Frontend

**Score: 97%** — Clase mundial con gaps cosmeticos.

**Fortalezas verificadas:**
- Homepage: 15/15 LANDING-CONVERSION-SCORE-001
- Case Study: 15/15 LANDING-CONVERSION-SCORE-001 (9/9 verticales)
- Vertical Landings: 9/9 con 11+ secciones cada una
- CTA tracking: 122/122 data-track-cta (100%)
- Zero-region: 53/61 templates (87%, 8 excepciones intencionales)
- Slide-panel: 180+ ficheros con isSlidePanelRequest()
- Textos traducibles: 158/969 templates con {% trans %} (coverage apropiada)
- Body classes via hook_preprocess_html() (NUNCA attributes.addClass())
- Video hero A11y: 6/6 checks PASS
- 4 variantes homepage por metasitio (variant-aware)

**Gaps identificados:**
1. **159 Twig includes sin 'only' keyword** — TWIG-INCLUDE-ONLY-001 compliance al 66.4% (314/473). Archivos afectados: page--mi-cuenta, page--messaging, tree-node recursivo, onboarding progress forms
2. **Upgrade messages vacios** — 195 FreemiumVerticalLimit sin upgrade_message personalizado (cosmetico, no funcional)

**Recomendacion:** Batch fix de 'only' keyword en includes (P1). Upgrade messages como mejora iterativa (P2).

### 3.5 Entities e Integracion

**Score: 95%** — Excepcional con un gap tecnico pendiente.

**Fortalezas verificadas:**
- 463 entity definitions escaneadas, 100% conventions satisfechas
- 271 PremiumEntityForms (100% de entity forms migradas, zero ContentEntityForm)
- 346 AccessControlHandlers (77.5% explicitos)
- 95+ entities con EntityViewsData
- 208 entities implementando EntityOwnerInterface
- 170+ con EntityOwnerTrait + EntityChangedTrait
- Hook update coverage: 463/463 (100%)
- Field UI en 20+ entities donde es necesario
- Admin navigation: /admin/content y /admin/structure correctamente separados
- Tenant isolation en 20+ access handlers verificado

**Gaps identificados:**
1. **130 AccessControlHandlers sin `: AccessResultInterface` return type** — ACCESS-RETURN-TYPE-001 compliance al 62.4% (216/346). PHPStan L6 puede flaggear incompatibilidad con parent class
2. **Hook theme completeness limitada** — Solo 1 priority hook encontrado por el validator. Posible gap en modulos con templates custom no auditados

**Recomendacion:** Bulk update return types (P0, afecta PHPStan baseline). Extender hook_theme validator (P1).

### 3.6 Seguridad Multi-Tenant

**Score: 90%** — Solida con un vector de riesgo no cubierto.

**Fortalezas verificadas:**
- TENANT-001: Todas las queries filtran por tenant_id
- TENANT-ISOLATION-ACCESS-001: checkAccess() verifica tenant match para update/delete
- ACCESS-STRICT-001: Comparaciones ownership con (int)===(int)
- TenantBridgeService: Mapping Tenant↔Group correcto
- SECRET-MGMT-001: Secrets via getenv(), nunca en config/sync/
- CSRF-API-001: Tokens en API routes
- AUDIT-SEC-001: Webhooks con HMAC + hash_equals()

**Gaps identificados:**
1. **Views UI sin validacion de tenant isolation** — Un admin puede crear una View con un entity type que tiene tenant_id pero sin filtro de tenant. Vector de fuga de datos cross-tenant
2. **Cache keys custom sin verificacion tenant_id** — DOMAIN-ROUTE-CACHE-001 cubre rutas, pero cache custom de servicios podria no incluir tenant scope
3. **AccessControlHandler implementations** — ROUTE-PERMISSION-AUDIT-001 verifica routing.yml pero NO que el handler realmente implementa la logica de verificacion tenant

**Recomendacion:** TENANT-VIEWS-ISOLATION-001 como nuevo validator P0. CACHE-KEY-TENANT-001 como P2.

### 3.7 Documentacion

**Score: 92%** — Comprensiva con drift corregido.

**Estado post-auditoria:**
- DIRECTRICES: v162.0.0 (actualizada)
- ARQUITECTURA: v147.0.0 (actualizada)
- INDICE: v191.0.0 (actualizada)
- FLUJO: v112.0.0 (sin cambios necesarios)
- CLAUDE.md: 34.4k chars (< 40k limite, optimizado -21.5%)
- validators-reference.md: SSOT con 104 scripts (creado y sincronizado)

**Gap corregido durante auditoria:**
- Tabla "Documentos Raiz" en INDICE mostraba versiones de febrero (v88, v81, v113) — actualizada a versiones reales (v162, v147, v191)
- ARQUITECTURA seccion 10.8.1 decia "19 scripts" — actualizado a "104 scripts"
- CLAUDE.md decia "53 validators" — actualizado a "104 (88 run + 16 warn)"

---

## 4. Gap Analysis — 18 Medidas de Salvaguarda Propuestas

### 4.1 Prioridad P0 — Criticos (Sprint 1)

Estos gaps impactan seguridad, integridad de datos o compliance PHPStan.

#### SAF-01: HOOK-REQUIREMENTS-GAP-CLOSE-001
**Descripcion:** 11 modulos sin hook_requirements() en produccion. Problemas de configuracion, dependencias faltantes o estados corruptos pasan desapercibidos en /admin/reports/status.

**Modulos afectados:**
1. `ai_provider_google_gemini`
2. `jaraba_ambient_ux`
3. `jaraba_commerce`
4. `jaraba_connector_sdk`
5. `jaraba_geo`
6. `jaraba_heatmap`
7. `jaraba_i18n`
8. `jaraba_performance`
9. `jaraba_rag`
10. `jaraba_social_commerce`
11. `jaraba_zkp`

**Implementacion:** Para cada modulo, crear `{module}.install` (si no existe) o agregar `hook_requirements()` con checks minimos:
- Version PHP compatible
- Dependencias de servicios disponibles
- Config entities creadas
- Tablas DB existentes

**Criterio de exito:** HOOK-REQUIREMENTS-GAP-001 validator pasa de 88% a 100%.

**Directrices aplicables:** IMPLEMENTATION-CHECKLIST-001 (Integridad), UPDATE-HOOK-REQUIRED-001

---

#### SAF-02: TENANT-VIEWS-ISOLATION-001
**Descripcion:** Nuevo validator que verifica que TODAS las Views configuradas que usan entity types con campo tenant_id tienen filtro de tenant_id obligatorio. Sin este filtro, un admin puede exponer datos de otros tenants via Views UI.

**Logica del validator:**
1. Escanear `config/sync/views.view.*.yml`
2. Para cada view, obtener el base_table/base_field
3. Verificar si el entity type del base_table tiene campo `tenant_id` en baseFieldDefinitions()
4. Si tiene tenant_id, verificar que la view tiene un filtro o contextual filter de tenant_id
5. FAIL si view usa entity con tenant_id pero no filtra por el

**Archivo:** `scripts/validation/validate-views-tenant-isolation.php`
**Registro:** run_check en validate-all.sh
**Directrices aplicables:** TENANT-001, TENANT-ISOLATION-ACCESS-001, API-WHITELIST-001

---

#### SAF-03: ACCESS-RETURN-TYPE-BULK-001
**Descripcion:** Corregir 130 AccessControlHandlers que no declaran `: AccessResultInterface` como return type de checkAccess(). Esto genera warnings en PHPStan L6 y viola ACCESS-RETURN-TYPE-001.

**Implementacion:**
1. Script PHP que parsee todos los ficheros `*AccessControlHandler.php`
2. Detecte `protected function checkAccess(` sin `: AccessResultInterface`
3. Agregue el return type automaticamente
4. Verifique que el `use` statement para `AccessResultInterface` existe

**Archivo:** `scripts/migration/fix-access-return-types.php`
**Verificacion:** PHPStan baseline se reduce en ~130 entradas
**Directrices aplicables:** ACCESS-RETURN-TYPE-001, DRUPAL11-001

---

#### SAF-04: DOC-VERSION-INDEX-SYNC-001
**Descripcion:** Extender el validator DOC-VERSION-DRIFT-001 para verificar tambien la tabla "Documentos Raiz" en el INDICE GENERAL. Durante la auditoria se encontro que esta tabla mostraba versiones de febrero (v88, v81) cuando los docs reales estaban en v161, v147.

**Implementacion:** Agregar check en `validate-doc-version-drift.php`:
1. Parsear la tabla markdown de "Documentos Raiz" en INDICE
2. Extraer versiones mencionadas
3. Comparar con las versiones reales en el header de cada master doc
4. FAIL si la diferencia es > 5 versiones (tolerancia para commits intermedios)

**Directrices aplicables:** DOC-GUARD-001, DOC-VERSION-DRIFT-001

---

#### SAF-05: SCSS-ROUTE-FRESHNESS-001
**Descripcion:** 6 route SCSS compilados stale (2026-03-04 a 2026-03-10). Cambios en _variables.scss no reflejados. Recompilar y agregar check en CI.

**Implementacion:**
1. Recompilar: `cd web/themes/custom/ecosistema_jaraba_theme && npm run build`
2. Agregar step en CI: `npm run build && git diff --exit-code css/` (falla si hay CSS no committeado)
3. Extender SCSS-COMPILE-FRESHNESS-001 para incluir routes y bundles, no solo main.scss

**Directrices aplicables:** SCSS-COMPILE-VERIFY-001, SCSS-COMPILE-FRESHNESS-001, RUNTIME-VERIFY-001

---

### 4.2 Prioridad P1 — Importantes (Sprint 2)

Estos gaps mejoran calidad, mantenibilidad y robustez.

#### SAF-06: CANVAS-SANITIZER-EXTRACT-001
**Descripcion:** Extraer `sanitizePageBuilderHtml()` de CanvasApiController y ArticleCanvasApiController a un servicio compartido `CanvasSanitizationService`. Actualmente el codigo esta duplicado — un fix de seguridad en uno puede no aplicarse al otro.

**Implementacion:**
1. Crear `ecosistema_jaraba_core/src/Service/CanvasSanitizationService.php`
2. Mover logica de sanitizacion (eliminar scripts, event handlers, javascript: URIs, data-gjs-*, gjs-*)
3. Inyectar servicio en ambos controllers via DI
4. Test unitario que verifique paridad de sanitizacion

**Directrices aplicables:** OPTIONAL-CROSSMODULE-001, SERVICE-ORPHAN-001

---

#### SAF-07: TWIG-INCLUDE-ONLY-BATCH-001
**Descripcion:** 159 Twig includes sin keyword 'only' violan TWIG-INCLUDE-ONLY-001. El riesgo es contaminacion de variables: el parcial recibe TODAS las variables del padre, incluyendo render arrays que pueden colisionar.

**Implementacion:**
1. Script PHP que parsee templates Twig
2. Detecte `{% include ... %}` sin `only`
3. Agregue `only` keyword y pase las variables necesarias explicitamente
4. Verificar con TWIG-SYNTAX-LINT-001 post-fix

**Archivos principales afectados:**
- page--mi-cuenta.html.twig (2 includes)
- page--messaging.html.twig (3 includes)
- tree-node.html.twig (recursivo, 24 includes)
- Onboarding progress forms (3+ includes)

**Directrices aplicables:** TWIG-INCLUDE-ONLY-001, TWIG-SYNTAX-LINT-001

---

#### SAF-08: E2E-CI-GATE-001
**Descripcion:** tests/e2e/ con Cypress existe pero NO se ejecuta en CI. Agregar job non-blocking.

**Implementacion:**
1. Agregar job `e2e-tests` en `.github/workflows/ci.yml`
2. `continue-on-error: true` inicialmente
3. Lando start + Cypress run con screenshots en artifacts
4. Migrar a blocking cuando cobertura sea estable

**Directrices aplicables:** IMPLEMENTATION-CHECKLIST-001 (Testing)

---

#### SAF-09: API-CONTRACT-001
**Descripcion:** Endpoints API con ALLOWED_FIELDS se validan via API-WHITELIST-001, pero no hay check de que la respuesta JSON tiene la estructura esperada. Un cambio en un controller puede romper clientes sin que CI lo detecte.

**Implementacion:**
1. Nuevo validator: `validate-api-contract.php`
2. Parsear rutas con `_controller:` que devuelvan JsonResponse
3. Verificar que los controllers declaran constantes o docblocks con la estructura de respuesta
4. Opcional: JSON Schema validation contra fixtures

**Directrices aplicables:** API-WHITELIST-001, ROUTE-CTRL-001

---

#### SAF-10: ACCESS-HANDLER-IMPL-001
**Descripcion:** ROUTE-PERMISSION-AUDIT-001 verifica que routing.yml tiene access declarations, pero NO que el AccessControlHandler realmente implementa verificacion de tenant_id. Un handler vacio pasa la validacion de ruta pero no protege datos.

**Implementacion:**
1. Nuevo validator: `validate-access-handler-impl.php`
2. Para handlers de entities con tenant_id:
   - Verificar que checkAccess() contiene referencia a `tenant` o `getCurrentTenant`
   - Verificar patron `(int)..===(int)` para ownership
3. FAIL si handler de entity con tenant_id no tiene logica de tenant

**Directrices aplicables:** TENANT-ISOLATION-ACCESS-001, ACCESS-STRICT-001

---

#### SAF-11: SCSS-BUILD-CI-001
**Descripcion:** Verificar compilacion SCSS en CI. SCSS-COMPILE-FRESHNESS-001 es warn porque git checkout iguala timestamps. La solucion real es compilar en CI y verificar que no hay diff.

**Implementacion:**
1. Agregar step en CI despues de checkout:
   ```yaml
   - name: Verify SCSS compilation freshness
     run: |
       cd web/themes/custom/ecosistema_jaraba_theme
       npm ci
       npm run build
       git diff --exit-code css/
   ```
2. Si hay diff, el CSS committeado esta desactualizado

**Directrices aplicables:** SCSS-COMPILE-VERIFY-001, SCSS-COMPONENT-BUILD-001

---

#### SAF-12: PLUGIN-REGISTRY-VALIDATION-001
**Descripcion:** Page Builder tiene 14 plugins JS declarados en libraries.yml pero no hay validacion de que todos cargan correctamente. Un plugin con error de syntax falla silenciosamente.

**Implementacion:**
1. Nuevo validator: `validate-plugin-registry.php`
2. Parsear libraries.yml del page builder
3. Verificar que cada archivo JS declarado existe y tiene syntax valida (via Node.js `--check`)
4. Verificar que cada plugin se registra en el patron `editor.Plugins.add()`

**Directrices aplicables:** JS-SYNTAX-LINT-001, SAFEGUARD-CANVAS-001

---

### 4.3 Prioridad P2 — Mejoras (Sprint 3)

Mejoras incrementales para llevar el sistema al 95%+.

#### SAF-13: A11Y-HEADING-HIERARCHY-001
**Descripcion:** Solo se valida video a11y. Verificar jerarquia de headings (h1→h2→h3 sin saltos) en templates Twig.

**Implementacion:** Nuevo validator que parsee templates buscando `<h[1-6]` y verifique que no hay saltos (ej: h1 seguido de h3 sin h2).

---

#### SAF-14: PERF-N1-QUERY-001
**Descripcion:** Detectar patrones N+1 en loops PHP. Escanear `::load()` dentro de `foreach` sin `::loadMultiple()` previo.

**Implementacion:** Validator estatico PHP que busque el patron `foreach.*{.*::load(` sin `loadMultiple` en scope previo.

---

#### SAF-15: CACHE-KEY-TENANT-001
**Descripcion:** Verificar que cache keys custom en servicios incluyen tenant_id. DOMAIN-ROUTE-CACHE-001 cubre rutas pero no cache de servicios.

**Implementacion:** Grep en servicios que usan `cache()->set()` o `\Drupal::cache()` y verificar que el key incluye referencia a tenant.

---

#### SAF-16: EMAIL-TEMPLATE-RENDER-001
**Descripcion:** EMAIL-SENDER-001 valida config pero no rendering. Verificar que templates de email no tienen errores de sintaxis Twig.

**Implementacion:** Extender TWIG-SYNTAX-LINT-001 para cubrir templates en `templates/email/` de cada modulo.

---

#### SAF-17: DEPRECATED-API-SCAN-001
**Descripcion:** PHPStan con `phpstan-deprecation-rules` existe pero no bloquea. Monitorear baseline de deprecations.

**Implementacion:** Agregar step en fitness-functions.yml que cuente deprecations en baseline y alerte si crece.

---

#### SAF-18: CANVAS-REVISION-AUTO-001
**Descripcion:** Automatizar backup de canvas_data cuando presave detecta shrinkage >50%. Actualmente solo loguea.

**Implementacion:** En presave hook, si shrinkage >50%, crear snapshot automatico en `canvas-snapshots/` antes de permitir save.

---

## 5. Tabla de Correspondencia Tecnica

| # | Medida | Rule ID | Tipo | Archivo Nuevo | Registro |
|---|--------|---------|------|---------------|----------|
| SAF-01 | hook_requirements 11 modulos | HOOK-REQUIREMENTS-GAP-CLOSE-001 | 11 .install files | {module}.install | N/A |
| SAF-02 | Views tenant isolation | TENANT-VIEWS-ISOLATION-001 | Nuevo validator | validate-views-tenant-isolation.php | run_check |
| SAF-03 | AccessControlHandler return types | ACCESS-RETURN-TYPE-BULK-001 | Migration script | fix-access-return-types.php | N/A |
| SAF-04 | Doc version index sync | DOC-VERSION-INDEX-SYNC-001 | Extender validator | validate-doc-version-drift.php | run_check |
| SAF-05 | SCSS route freshness | SCSS-ROUTE-FRESHNESS-001 | CI step + recompile | ci.yml | CI gate |
| SAF-06 | Canvas sanitizer extract | CANVAS-SANITIZER-EXTRACT-001 | Nuevo servicio | CanvasSanitizationService.php | N/A |
| SAF-07 | Twig include only batch | TWIG-INCLUDE-ONLY-BATCH-001 | Fix script | fix-twig-include-only.php | N/A |
| SAF-08 | E2E CI gate | E2E-CI-GATE-001 | CI job | ci.yml | CI gate |
| SAF-09 | API contract validation | API-CONTRACT-001 | Nuevo validator | validate-api-contract.php | run_check |
| SAF-10 | Access handler impl | ACCESS-HANDLER-IMPL-001 | Nuevo validator | validate-access-handler-impl.php | run_check |
| SAF-11 | SCSS build CI | SCSS-BUILD-CI-001 | CI step | ci.yml | CI gate |
| SAF-12 | Plugin registry validation | PLUGIN-REGISTRY-VALIDATION-001 | Nuevo validator | validate-plugin-registry.php | warn_check |
| SAF-13 | A11y heading hierarchy | A11Y-HEADING-HIERARCHY-001 | Nuevo validator | validate-heading-hierarchy.php | warn_check |
| SAF-14 | N+1 query detection | PERF-N1-QUERY-001 | Nuevo validator | validate-n1-queries.php | warn_check |
| SAF-15 | Cache key tenant | CACHE-KEY-TENANT-001 | Nuevo validator | validate-cache-key-tenant.php | warn_check |
| SAF-16 | Email template render | EMAIL-TEMPLATE-RENDER-001 | Extender validator | validate-twig-syntax.php | run_check |
| SAF-17 | Deprecated API scan | DEPRECATED-API-SCAN-001 | CI step | fitness-functions.yml | CI warn |
| SAF-18 | Canvas revision auto | CANVAS-REVISION-AUTO-001 | Hook PHP | jaraba_page_builder.module | N/A |

---

## 6. Directrices de Aplicacion

Cada medida DEBE cumplir con las siguientes directrices transversales:

### Codigo
- **PHP 8.4** con `declare(strict_types=1)` en archivos nuevos
- **PHPStan Level 6** — zero nuevos errores introducidos
- **Drupal Coding Standards** — verificado por PHPCS en pre-commit
- **CONTROLLER-READONLY-001** — subclases NO declaran `protected readonly` en propiedades heredadas
- **OPTIONAL-CROSSMODULE-001** — toda referencia cross-modulo en services.yml usa `@?`

### Templates
- **{% trans %}** para todo texto visible al usuario
- **{% include ... only %}** en todos los includes de parciales
- **TWIG-SYNTAX-LINT-001** — zero errores de sintaxis
- **ZERO-REGION-001/002/003** — controllers devuelven markup minimo, logica en preprocess

### SCSS/CSS
- **CSS-VAR-ALL-COLORS-001** — colores via `var(--ej-*, fallback)`
- **SCSS-001** — `@use '../variables' as *;` en cada parcial
- **SCSS-COMPILE-VERIFY-001** — recompilar tras edicion SCSS

### Seguridad
- **TENANT-001** — toda query filtra por tenant_id
- **SECRET-MGMT-001** — nunca secrets en config/sync/
- **CSRF-API-001** — tokens en API routes
- **ACCESS-STRICT-001** — comparaciones ownership con `(int)===(int)`

### Testing
- **KERNEL-TEST-DEPS-001** — $modules lista TODOS los requeridos
- **MOCK-DYNPROP-001** — PHP 8.4 prohibe dynamic properties en mocks
- **TEST-CACHE-001** — mocks implementan getCacheContexts/Tags/MaxAge

### Documentacion
- **DOC-GUARD-001** — NUNCA sobreescribir master docs con Write, siempre Edit incremental
- **COMMIT-SCOPE-001** — master docs en commit separado con prefijo `docs:`

---

## 7. Plan de Ejecucion Detallado

### 7.1 Sprint 1 — P0 Criticos

| Paso | Tarea | Salida | Verificacion |
|------|-------|--------|-------------|
| 1.1 | Generar hook_requirements para 11 modulos | 11 .install actualizados | HOOK-REQUIREMENTS-GAP-001 al 100% |
| 1.2 | Crear validate-views-tenant-isolation.php | Nuevo validator registrado | run_check PASS |
| 1.3 | Script fix-access-return-types.php | 130 handlers corregidos | PHPStan baseline -130 |
| 1.4 | Extender validate-doc-version-drift.php | Check tabla raiz | run_check PASS |
| 1.5 | Recompilar SCSS routes + CI step | CSS fresh + CI gate | git diff --exit-code css/ |
| 1.6 | Actualizar validate-all.sh + CLAUDE.md | Nuevos validators registrados | VALIDATOR-COVERAGE-001 PASS |

### 7.2 Sprint 2 — P1 Importantes

| Paso | Tarea | Salida | Verificacion |
|------|-------|--------|-------------|
| 2.1 | Extraer CanvasSanitizationService | 1 servicio + 2 controllers refactorizados | Unit test PASS |
| 2.2 | Batch fix Twig includes 'only' | 159 templates corregidos | TWIG-INCLUDE-ONLY-001 al 95%+ |
| 2.3 | E2E tests en CI (non-blocking) | Cypress job en ci.yml | Job ejecuta sin bloquear |
| 2.4 | Crear validate-api-contract.php | Nuevo validator | run_check PASS |
| 2.5 | Crear validate-access-handler-impl.php | Nuevo validator | run_check PASS |
| 2.6 | SCSS build en CI | Step en ci.yml | npm run build + git diff |
| 2.7 | Crear validate-plugin-registry.php | Nuevo validator | warn_check PASS |

### 7.3 Sprint 3 — P2 Mejoras

| Paso | Tarea | Salida | Verificacion |
|------|-------|--------|-------------|
| 3.1 | Heading hierarchy validator | Nuevo validator | warn_check |
| 3.2 | N+1 query detector | Nuevo validator | warn_check |
| 3.3 | Cache key tenant checker | Nuevo validator | warn_check |
| 3.4 | Email template render check | Extender Twig lint | run_check |
| 3.5 | Deprecated API baseline monitor | CI step | fitness-functions.yml |
| 3.6 | Canvas auto-revision en presave | Hook PHP modificado | Snapshot auto en shrinkage >50% |

---

## 8. Metricas de Exito

| Metrica | Actual | Post-Sprint 1 | Post-Sprint 2 | Post-Sprint 3 |
|---------|--------|---------------|---------------|---------------|
| Madurez global | 87% | 92% | 94% | 96% |
| hook_requirements | 88% (83/94) | 100% (94/94) | 100% | 100% |
| Validators total | 104 | 109 | 116 | 122 |
| run_check | 88 | 93 | 98 | 99 |
| warn_check | 16 | 16 | 18 | 23 |
| AccessControlHandler return types | 62% | 100% | 100% | 100% |
| Twig includes 'only' | 66% | 66% | 95%+ | 95%+ |
| PHPStan baseline | 41K+ | 41K-130 | 41K-130 | 41K-130 |
| CI gates | 88 | 90 | 93 | 94 |

---

## 9. Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| fix-access-return-types rompe herencia | Baja | Alta | Script con --dry-run + PHPStan pre/post |
| Views tenant validator false positivos en views de admin | Media | Baja | Lista de exclusion para views /admin/* |
| Twig 'only' fix rompe variables en parciales | Media | Media | Test manual en Lando pre-commit |
| SCSS recompile cambia CSS hash (cache bust) | Baja | Baja | OPcache invalidation en deploy |
| E2E tests flaky en CI | Alta | Baja | Non-blocking con continue-on-error |

---

## 10. Glosario

| Sigla | Significado |
|-------|-------------|
| SAF | Safeguard (medida de salvaguarda) |
| CI | Continuous Integration |
| SCSS | Sassy CSS (preprocesador) |
| CSRF | Cross-Site Request Forgery |
| HMAC | Hash-based Message Authentication Code |
| DI | Dependency Injection |
| E2E | End-to-End (tests) |
| WCAG | Web Content Accessibility Guidelines |
| PHPStan | PHP Static Analysis Tool |
| CTA | Call to Action |
| PLG | Product-Led Growth |
| SSOT | Single Source of Truth |
| RPO | Recovery Point Objective |
| FPM | FastCGI Process Manager |
| NVMe | Non-Volatile Memory Express |
| CDN | Content Delivery Network |
| CSP | Content Security Policy |
| PIIL | Programa de Insercion e Inclusion Laboral |
| BANT | Budget, Authority, Need, Timeline |
| MQL | Marketing Qualified Lead |
| SVG | Scalable Vector Graphics |
| UUID | Universally Unique Identifier |
| ECA | Events, Conditions, Actions |
| SLA | Service Level Agreement |
| A11Y | Accessibility |
| RTL | Right-to-Left |
