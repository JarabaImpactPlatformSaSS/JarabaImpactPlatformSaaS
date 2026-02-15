# Elevacion Emprendimiento: 6 Fases Implementadas

**Fecha:** 2026-02-15
**Vertical:** Emprendimiento
**Modulos:** jaraba_copilot_v2, ecosistema_jaraba_core, ecosistema_jaraba_theme, jaraba_journey

## Resumen

Implementacion completa del Plan de Elevacion a Clase Mundial del vertical Emprendimiento en 6 fases. Cierra los 6 hallazgos criticos/altos identificados en la auditoria y el gap G10 (A/B Testing) diferido de la sesion anterior.

## Fases Implementadas

### Fase 1: Parent Template + Copilot FAB + Preprocess Hook

**Problema:** Las 3 templates de emprendimiento (bmc, hipotesis, experimentos-gestion) eran zero-region pero no tenian parent template unificado ni Copilot FAB.

**Solucion:**
- Creado `page--emprendimiento.html.twig` replicando patron exacto de `page--empleabilidad.html.twig`
- Creado `hook_preprocess_page__emprendimiento()` con inyeccion de copilot_context, theme_settings, site_name
- Actualizado template suggestions: copilot_v2 + mentoring routes → `page__emprendimiento`
- Eliminados 3 templates hijos redundantes (Opcion A del plan)

**Directrices:** Nuclear #14 (Zero Region), Nuclear #11 (Full-width), P4-AI-001 (FAB), INCLUDE-001 (only)

### Fase 2: Body Classes Unificadas

**Problema:** Faltaban clases `page-emprendimiento` y `vertical-emprendimiento` en body. Solo existian clases individuales por ruta.

**Solucion:**
- Agregadas 2 clases unificadas al bloque copilot_v2_routes en `hook_preprocess_html()`
- Nuevo bloque para rutas mentoring (4 rutas) con mismas clases

**Directrices:** BODY-001 (clases via hook, nunca en template)

### Fase 3: Correccion SCSS y package.json

**Problema:** 3 violaciones directas de rgba() en SCSS + falta package.json.

**Solucion:**
- `_copilot-chat-widget.scss:385`: `rgba(255, 140, 66, 0.15)` → `color-mix(in srgb, var(--ej-color-accent, #FF8C42) 15%, transparent)`
- `_hypothesis-manager.scss:156`: `rgba(59, 130, 246, 0.1)` → `color-mix(in srgb, var(--ej-color-primary, #3B82F6) 10%, transparent)`
- `_hypothesis-manager.scss:163`: idem anterior
- Creado `package.json` con scripts build:css y watch:css (Dart Sass)

**Nota:** Los rgba() dentro de fallbacks var() se mantienen (aceptables).

**Directrices:** P4-COLOR-001 (color-mix), SCSS-PKG-001 (package.json), SCSS-DART-001 (Dart Sass)

### Fase 4: EmprendimientoFeatureGateService

**Problema:** 18 FreemiumVerticalLimit configs existian pero no habia servicio que las enforce.

**Solucion:**
- Creado `EmprendimientoFeatureGateService` replicando patron exacto de `EmployabilityFeatureGateService`
- 6 features gestionadas: hypotheses_active, experiments_monthly, copilot_sessions_daily, mentoring_sessions_monthly, bmc_drafts, calculadora_uses
- Tabla `emprendimiento_feature_usage` con ensureTable() auto-creacion + update_9016
- Registrado en services.yml con mismos argumentos

**Directrices:** F2/Doc 183 (Freemium), DRUPAL11-001 (DI)

### Fase 5: i18n Compliance en JourneyDefinition

**Problema:** 30+ strings hardcoded en espanol sin TranslatableMarkup en constantes PHP.

**Solucion:**
- Constantes `const` convertidas a metodos estaticos: `getEmprendedorJourney()`, `getMentorJourney()`, `getGestorProgramaJourney()`, `getEmpleabilidadFallback()`, `getEmprendimientoOnramp()`
- 30+ strings envueltos en `new TranslatableMarkup()`
- `getJourneyDefinition()` actualizado para llamar metodos
- `evaluateEmpleabilidadFallback()` usa `getEmpleabilidadFallback()` en lugar de constante
- `EmprendimientoCrossSellService` actualizado: `EMPRENDEDOR_JOURNEY['cross_sell']` → `getEmprendedorJourney()['cross_sell']`

**Decision tecnica:** Opcion A (metodos estaticos) elegida sobre Opcion B (keys + traductor separado) por limpieza y compatibilidad con patron existente.

**Directrices:** I18N-001 (TranslatableMarkup)

### Fase 6: A/B Testing Framework (G10)

**Problema:** Gap G10 diferido — no existia framework de experimentacion para emprendimiento.

**Solucion:**
- Creado `EmprendimientoExperimentService` en ecosistema_jaraba_core
- Integrado con jaraba_ab_testing existente (ABExperiment, ABVariant, VariantAssignmentService)
- Resolucion en runtime de VariantAssignmentService (sin hard dependency)
- 10 eventos de conversion validos (idea_registered → plan_upgraded)
- Soporte para scope filtering (onboarding_flow, bmc_ux, copilot_engagement, upgrade_funnel)
- Metricas por variante con conversion rate

**Decision tecnica:** Se usa el modulo jaraba_ab_testing existente en lugar de crear framework nuevo. Pattern replicado de OnboardingExperimentService.

**Directrices:** ConfigEntity pattern, MILESTONE-001 (append-only)

## Archivos Afectados

| Archivo | Accion | Fase |
|---------|--------|------|
| `page--emprendimiento.html.twig` | Creado | 1 |
| `ecosistema_jaraba_theme.theme` | Modificado (preprocess + body classes + suggestions) | 1, 2 |
| `page--emprendimiento--bmc.html.twig` | Eliminado | 1 |
| `page--emprendimiento--hipotesis.html.twig` | Eliminado | 1 |
| `page--emprendimiento--experimentos-gestion.html.twig` | Eliminado | 1 |
| `_copilot-chat-widget.scss` | Modificado | 3 |
| `_hypothesis-manager.scss` | Modificado | 3 |
| `jaraba_copilot_v2/package.json` | Creado | 3 |
| `EmprendimientoFeatureGateService.php` | Creado | 4 |
| `ecosistema_jaraba_core.services.yml` | Modificado | 4, 6 |
| `ecosistema_jaraba_core.install` | Modificado | 4 |
| `EmprendimientoJourneyDefinition.php` | Modificado | 5 |
| `EmprendimientoCrossSellService.php` | Modificado | 5 |
| `EmprendimientoExperimentService.php` | Creado | 6 |
| `00_INDICE_GENERAL.md` | Modificado | - |

## Resultado

| Hallazgo | Antes | Despues |
|----------|-------|---------|
| H1 Parent template | No existia | Creado + activo |
| H2 Copilot FAB | Ausente en 3 templates | Presente via parent |
| H3 Preprocess hook | No existia | Creado |
| H4 FeatureGateService | No existia | Creado + registrado |
| H5 Body classes | Solo individuales | Unificadas + individuales |
| H6 rgba() violaciones | 3 directas | 0 (corregidas con color-mix) |
| H7 package.json | No existia | Creado |
| H8 i18n | 0 llamadas t() | 30+ TranslatableMarkup |
| H9 A/B Testing G10 | No existia | EmprendimientoExperimentService |
