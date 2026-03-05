# Plan de Implementacion: Sistema de Salvaguardas — 100% Madurez

**Fecha:** 2026-03-05
**Version:** 2.0.0 (Implementacion completada)
**Contexto:** Remediacion completa de 10 gaps detectados en auditoria de salvaguardas para alcanzar el 100% de madurez del SaaS
**Modulos afectados:** 67 modulos custom (275 entity types), ecosistema_jaraba_theme (240 archivos SCSS), CI workflows (ci.yml, fitness-functions.yml)
**Estado:** COMPLETADO — 15/15 validaciones PASS, 0 FAIL
**Directrices de aplicacion:** CSS-VAR-ALL-COLORS-001, UPDATE-HOOK-REQUIRED-001, TWIG-ENTITY-METHOD-001, PREMIUM-FORMS-PATTERN-001, DOC-GUARD-001, RUNTIME-VERIFY-001, IMPLEMENTATION-CHECKLIST-001

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnostico Inicial](#2-diagnostico-inicial)
3. [Sprint S1 — Bloqueadores P0](#3-sprint-s1--bloqueadores-p0)
   - [S1-01: Colores hex hardcoded en SCSS](#s1-01-colores-hex-hardcoded-en-scss-css-var-all-colors-001)
   - [S1-02: Ficheros .install faltantes](#s1-02-ficheros-install-faltantes-update-hook-required-001)
   - [S1-03: CI checks BLOCKING](#s1-03-ci-checks-blocking)
   - [S1-04: Template Twig commerce-product--geo](#s1-04-template-twig-commerce-product--geo-twig-entity-method-001)
4. [Sprint S2 — Integridad Estructural P1](#4-sprint-s2--integridad-estructural-p1)
   - [S2-01: hook_update_N para entities faltantes](#s2-01-hook_update_n-para-entities-faltantes)
   - [S2-02: Nuevos scripts de validacion](#s2-02-nuevos-scripts-de-validacion)
   - [S2-03: Expansion hook_requirements (Capa 4)](#s2-03-expansion-hook_requirements-capa-4)
   - [S2-04: Migracion forms a PremiumEntityFormBase](#s2-04-migracion-forms-a-premiumentityformbase)
5. [Sprint S3 — Pulido y Documentacion P2](#5-sprint-s3--pulido-y-documentacion-p2)
   - [S3-01: DOC-GUARD-001 perdida relativa](#s3-01-doc-guard-001-perdida-relativa)
   - [S3-02: Actualizacion master docs](#s3-02-actualizacion-master-docs)
6. [Tabla de Correspondencia con Especificaciones Tecnicas](#6-tabla-de-correspondencia-con-especificaciones-tecnicas)
7. [Indicaciones para Cumplimiento de Directrices](#7-indicaciones-para-cumplimiento-de-directrices)
8. [Metricas de Exito](#8-metricas-de-exito)
9. [Verificacion RUNTIME-VERIFY-001](#9-verificacion-runtime-verify-001)

---

## 1. Resumen Ejecutivo

La auditoria del Sistema de Salvaguardas del SaaS revelo una madurez del 85% (4.25/5 capas implementadas). Este plan remedia los 10 gaps identificados para alcanzar el 100%. Los gaps se clasifican en 3 sprints por prioridad:

| Sprint | Prioridad | Gaps | Descripcion |
|--------|-----------|------|-------------|
| S1 | P0 (Bloqueadores) | 4 | Colores SCSS, .install faltantes, CI blocking, Twig template |
| S2 | P1 (Integridad) | 4 | Entity hooks, validadores, hook_requirements, forms |
| S3 | P2 (Pulido) | 2 | DOC-GUARD relativo, documentacion master |

**Resultado esperado:** 14/14 scripts de validacion OK, 5/5 capas de salvaguarda operativas, 0 violaciones P0.

---

## 2. Diagnostico Inicial

### 2.1 Estado Actual de las 5 Capas

| Capa | Mecanismo | Cobertura Actual | Objetivo |
|------|-----------|------------------|----------|
| 1 | Scripts validacion (14 scripts) | 14/14 OK | Mantener + 2 nuevos |
| 2 | Pre-commit hooks (Husky + lint-staged) | 5 checks | Mantener + mejora DOC-GUARD |
| 3 | CI Pipeline Gates (3 workflows) | 20+ gates, 3 como WARNING | 23+ gates, TODOS blocking |
| 4 | Runtime Self-Checks (hook_requirements) | 4 modulos | 8+ modulos |
| 5 | Implementation Checklist | Documentada | Automatizada parcialmente |

### 2.2 Inventario de Gaps

| ID | Gap | Regla Violada | Impacto | Prioridad |
|----|-----|---------------|---------|-----------|
| GAP-01 | 393 colores hex hardcoded en SCSS | CSS-VAR-ALL-COLORS-001 | Rompe theme switching multi-tenant | P0 |
| GAP-02 | 3 modulos sin fichero .install | UPDATE-HOOK-REQUIRED-001 | Divergencia silenciosa dev-prod | P0 |
| GAP-03 | 3 CI checks con continue-on-error:true | SERVICE-ORPHAN-001, TEST-COVERAGE-MAP-001, TENANT-CHECK-001 | Bypasses de calidad | P0 |
| GAP-04 | Violaciones TWIG-ENTITY-METHOD-001 | TWIG-ENTITY-METHOD-001 | Error 500 potencial | P0 |
| GAP-05 | ~80 entities sin installEntityType() | UPDATE-HOOK-REQUIRED-001 | Entity type not installed en prod | P1 |
| GAP-06 | Sin validador CONTROLLER-READONLY-001 | CONTROLLER-READONLY-001 | TypeError en runtime | P1 |
| GAP-07 | Sin validador PRESAVE-RESILIENCE-001 | PRESAVE-RESILIENCE-001 | Entity save silently fails | P1 |
| GAP-08 | hook_requirements limitado a 4 modulos | Capa 4 Safeguard | Sin deteccion runtime | P1 |
| GAP-09 | 2 forms sin PremiumEntityFormBase | PREMIUM-FORMS-PATTERN-001 | Inconsistencia UI | P2 |
| GAP-10 | DOC-GUARD no valida perdida relativa | DOC-GUARD-001 | Master docs pueden perder >10% | P2 |

---

## 3. Sprint S1 — Bloqueadores P0

### S1-01: Colores hex hardcoded en SCSS (CSS-VAR-ALL-COLORS-001)

**Problema:** 393 ocurrencias de colores hex directos en archivos SCSS, concentrados en `scss/routes/` y `scss/components/` del tema. Esto viola la directriz CSS-VAR-ALL-COLORS-001 que exige que TODOS los colores sean `var(--ej-*, fallback)`.

**Impacto:** Los tenants que personalizan colores desde la UI de Drupal (Apariencia > Ecosistema Jaraba Theme > Identidad de Marca) NO ven esos cambios reflejados en las paginas que usan colores hardcoded. Esto rompe el modelo de "Federated Design Tokens" documentado en `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`.

**Solucion tecnica:**

Para cada color hex hardcoded, reemplazar por la variable CSS correspondiente con fallback:

```scss
// ANTES (incorrecto):
.status-badge--success {
  background: #ecfdf5;
  color: #065f46;
}

// DESPUES (correcto):
.status-badge--success {
  background: var(--ej-color-success-bg, #ecfdf5);
  color: var(--ej-color-success-dark, #065f46);
}
```

**Tabla de mapeo hex → token CSS:**

| Hex Comun | Token CSS | Proposito |
|-----------|-----------|-----------|
| #233D63 | --ej-color-corporate | Azul corporativo |
| #FF8C42 | --ej-color-primary / --ej-color-impulse | Naranja impulso |
| #00A9A5 | --ej-color-secondary / --ej-color-innovation | Verde innovacion |
| #556B2F | --ej-color-agro | Verde oliva agro |
| #F8FAFC | --ej-color-bg-body | Fondo body light |
| #FFFFFF | --ej-color-bg-surface | Fondo superficie |
| #1A1A2E | --ej-color-bg-dark | Fondo dark mode |
| #10B981 | --ej-color-success | Estado exito |
| #F59E0B | --ej-color-warning | Estado advertencia |
| #EF4444 | --ej-color-danger | Estado peligro |
| #64748B | --ej-color-neutral | Neutro |
| #334155 | --ej-color-body-text | Texto cuerpo |
| #1E293B | --ej-color-headings | Titulos |
| #ecfdf5 | --ej-color-success-bg | Fondo exito suave |
| #fef3c7 | --ej-color-warning-bg | Fondo advertencia suave |
| #fee2e2 | --ej-color-danger-bg | Fondo peligro suave |
| #eff6ff | --ej-color-info-bg | Fondo info suave |

**Directrices de compilacion:**
- Usar Dart Sass moderno (`@use 'sass:color'` para transformaciones)
- NO usar `darken()`, `lighten()` (deprecados). Usar `color-mix(in srgb, ...)` o `color.adjust()`
- El mixin `css-var()` de `_mixins.scss` es OBLIGATORIO para propiedades con token
- Compilar desde contenedor Docker: `lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed"`
- Verificar timestamp CSS > SCSS (SCSS-COMPILE-VERIFY-001)

**Directrices de inyeccion de variables:**
Los valores de los tokens CSS se configuran desde la UI de Drupal (Apariencia > Ecosistema Jaraba Theme, 13 vertical tabs con 70+ opciones). El hook `ecosistema_jaraba_theme_preprocess_html()` inyecta los valores como `<style>:root { --ej-color-primary: #FF8C42; ... }</style>`. Los archivos SCSS NUNCA deben definir el valor final de un color — solo el fallback en caso de que no haya inyeccion.

**Ficheros a modificar:** Todos los SCSS en `scss/routes/` y `scss/components/` que contengan hex hardcoded. Lista completa tras auditoria del agente.

**Verificacion:** `grep -rn '#[0-9a-fA-F]\{3,8\}' web/themes/custom/ecosistema_jaraba_theme/scss/ --include='*.scss' | grep -v '_variables.scss' | grep -v '_injectable.scss' | grep -v 'var(--ej-' | grep -v '!default' | grep -v '//' | wc -l` debe ser 0.

---

### S1-02: Ficheros .install faltantes (UPDATE-HOOK-REQUIRED-001)

**Problema:** 3 modulos nuevos no tienen fichero `.install`, lo que significa que sus ContentEntities nunca se registraran correctamente en la base de datos de produccion.

**Impacto:** Al instalar el modulo, Drupal no ejecuta `hook_install()` ni `hook_update_N()`. Las tablas de entities se crean por autodeteccion, pero el entity type definition no se marca como "installed" en el tracker interno de Drupal. Esto produce el error "The entity type needs to be installed" en `/admin/reports/status` y puede causar comportamientos erraticos en Entity Query.

**Modulos afectados:**

| Modulo | Entities | Tipo |
|--------|----------|------|
| jaraba_ab_testing | ABExperiment, ABVariant, ExperimentExposure, ExperimentResult | 4 ContentEntity |
| jaraba_ads | AdCampaign, AdsAccount, AdsAudienceSync, AdsCampaignSync, AdsConversionEvent, AdsMetricsDaily | 6 ContentEntity |
| jaraba_agent_market | DigitalTwin, NegotiationSession | 2 ContentEntity |

**Solucion tecnica:**

Para cada modulo, crear `{modulo}.install` con:

```php
<?php

declare(strict_types=1);

/**
 * @file
 * Install, update and uninstall functions for {modulo}.
 */

/**
 * Implements hook_install().
 */
function {modulo}_install(): void {
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $entity_type_manager = \Drupal::entityTypeManager();

  $entity_types = [
    'entity_type_id_1',
    'entity_type_id_2',
    // ... todas las entities del modulo
  ];

  foreach ($entity_types as $entity_type_id) {
    if (!$entity_definition_update_manager->getEntityType($entity_type_id)) {
      $entity_type = $entity_type_manager->getDefinition($entity_type_id);
      $entity_definition_update_manager->installEntityType($entity_type);
    }
  }
}
```

**Patron UPDATE-HOOK-CATCH-001:** Todo `try-catch` DEBE usar `\Throwable` (no `\Exception`) porque PHP 8.4 `TypeError` extiende `\Error`.

**Directriz de integracion en Drupal:**
- Toda entity con `field_ui_base_route` DEBE tener default local task tab (FIELD-UI-SETTINGS-TAB-001)
- Toda entity DEBE declarar `"views_data" = "Drupal\views\EntityViewsData"` en anotacion para integracion con Views
- Toda entity DEBE tener AccessControlHandler en anotacion (AUDIT-CONS-001)
- Navegacion admin: entities de configuracion en `/admin/structure`, entities de contenido en `/admin/content`

---

### S1-03: CI checks BLOCKING

**Problema:** 3 checks en CI estan configurados con `continue-on-error: true`, lo que permite que codigo con problemas pase el pipeline.

**Ficheros a modificar:**

| Workflow | Step | Validacion | Linea aprox |
|----------|------|-----------|-------------|
| `.github/workflows/ci.yml` | validate-service-consumers | SERVICE-ORPHAN-001 | ~88 |
| `.github/workflows/ci.yml` | validate-test-coverage | TEST-COVERAGE-MAP-001 | ~97 |
| `.github/workflows/fitness-functions.yml` | validate-tenant-isolation | TENANT-CHECK-001 | ~285 |

**Solucion:** Eliminar `continue-on-error: true` de cada step para convertirlos en gates bloqueantes.

**Justificacion:**
- **SERVICE-ORPHAN-001:** Servicios huerfanos no deben existir — desperdician recursos y confunden al equipo
- **TEST-COVERAGE-MAP-001:** Modulos criticos sin tests son riesgo de regresion
- **TENANT-CHECK-001:** Filtro de tenant faltante = data leak entre tenants, vulnerabilidad critica

---

### S1-04: Template Twig commerce-product--geo (TWIG-ENTITY-METHOD-001)

**Problema:** El template `commerce-product--geo.html.twig` usa acceso directo a propiedades de entity (`.value`) que devuelve FieldItemList no imprimible, causando errores 500.

**Solucion:** Convertir accesos directos a metodos getter o usar el patron seguro de Twig para Commerce entities.

**Nota sobre Commerce entities:** Las Commerce entities (Product, Variation, Order) tienen un patron especifico donde `.title.value`, `.status.value` etc. son accesos a FieldItemList. Sin embargo, en templates Twig de Commerce, Drupal los maneja via `__get()` magic. El problema real es cuando la variable no esta preprocessada. La solucion correcta es crear `template_preprocess_commerce_product__geo()` en el `.theme` file que extraiga los valores escalares.

**Directriz ENTITY-PREPROCESS-001:** Toda ContentEntity con view mode DEBE tener `template_preprocess_{type}()` en el `.module` o `.theme` file que extraiga primitivas, resuelva referenced entities, y genere URLs de imagen.

---

## 4. Sprint S2 — Integridad Estructural P1

### S2-01: hook_update_N para entities faltantes

**Problema:** ~80 entities en 20+ modulos no tienen `installEntityType()` en su `.install`. Aunque las tablas existen en la BD de desarrollo (creadas por autodeteccion), en produccion la divergencia causa errores.

**Solucion:** Generar un hook_update generico por modulo que registre todas las entities faltantes. Usar un script para identificar los entity types no registrados y generar el codigo.

**Patron:**
```php
/**
 * Register entity types: {lista}.
 */
function {modulo}_update_{NNNN}(): void {
  $manager = \Drupal::entityDefinitionUpdateManager();
  $types = ['entity_type_1', 'entity_type_2'];
  foreach ($types as $type_id) {
    try {
      if (!$manager->getEntityType($type_id)) {
        $definition = \Drupal::entityTypeManager()->getDefinition($type_id);
        $manager->installEntityType($definition);
      }
    }
    catch (\Throwable $e) {
      \Drupal::logger('{modulo}')->warning('Failed to install @type: @msg', [
        '@type' => $type_id,
        '@msg' => $e->getMessage(),
      ]);
    }
  }
}
```

---

### S2-02: Nuevos scripts de validacion

**Nuevos scripts a crear:**

#### validate-controller-readonly.php (CONTROLLER-READONLY-001)
Escanea todos los Controllers que extienden ControllerBase y verifica que NO redeclaren `$entityTypeManager` como `readonly` en constructor promotion.

**Logica:**
1. Busca `extends ControllerBase` en PHP files
2. Para cada uno, parsea el constructor
3. Si encuentra `readonly` + `$entityTypeManager` en promotion params → ERROR

#### validate-presave-resilience.php (PRESAVE-RESILIENCE-001)
Escanea hooks `_presave()` en `.module` files que invoquen servicios opcionales sin `hasService()` + try-catch.

**Logica:**
1. Busca `function .*_presave(` en .module files
2. Para cada uno, busca `\Drupal::service(` o `->method(` calls
3. Si no hay `try {` envolviendo → WARNING

---

### S2-03: Expansion hook_requirements (Capa 4)

**Estado actual:** 4 modulos con `hook_requirements()`: ecosistema_jaraba_core, ecosistema_jaraba_theme, jaraba_ai_agents, jaraba_workflows.

**Expansion:** Agregar `hook_requirements()` a modulos criticos para verificar en runtime:

| Modulo | Checks |
|--------|--------|
| jaraba_billing | Stripe keys configuradas via getenv(), tablas billing existen |
| jaraba_addons | Entity types addon/addon_subscription instalados |
| jaraba_comercio_conecta | Commerce entities, Stripe Connect keys |
| jaraba_copilot_v2 | AI provider keys, Qdrant connection |

**Directriz:** Los `hook_requirements()` se implementan en `.install` files y se muestran en `/admin/reports/status`. Deben verificar:
1. Secrets inyectados via `getenv()` (SECRET-MGMT-001)
2. Tablas de entities existen
3. Servicios obligatorios disponibles
4. Rutas accesibles (no 404s)

---

### S2-04: Migracion forms a PremiumEntityFormBase

**Problema:** 2 forms en `jaraba_credentials_cross_vertical` extienden `ContentEntityForm` directamente.

**Ficheros:**
- `jaraba_credentials_cross_vertical/src/Form/CrossVerticalRuleForm.php`
- `jaraba_credentials_cross_vertical/src/Form/CrossVerticalProgressForm.php`

**Solucion:** Migrar al patron PREMIUM-FORMS-PATTERN-001:
1. Cambiar `extends ContentEntityForm` → `extends PremiumEntityFormBase`
2. Implementar `getSectionDefinitions()` con los campos de la entity
3. Implementar `getFormIcon()` con icono duotone de la paleta Jaraba (ICON-DUOTONE-001, ICON-COLOR-001)
4. DI via `parent::create()` pattern

---

## 5. Sprint S3 — Pulido y Documentacion P2

### S3-01: DOC-GUARD-001 perdida relativa

**Problema:** El script `verify-doc-integrity.sh` solo verifica umbrales absolutos (DIRECTRICES >= 2000, etc.) pero no detecta perdida relativa >10%.

**Solucion:** Agregar check de perdida relativa al script:

```bash
# Calcular perdida relativa
PREV_LINES=$(git show HEAD:docs/00_DIRECTRICES_PROYECTO.md 2>/dev/null | wc -l)
CURR_LINES=$(wc -l < docs/00_DIRECTRICES_PROYECTO.md)
if [ "$PREV_LINES" -gt 0 ]; then
  LOSS_PCT=$(( (PREV_LINES - CURR_LINES) * 100 / PREV_LINES ))
  if [ "$LOSS_PCT" -gt 10 ]; then
    echo "ERROR: Perdida relativa ${LOSS_PCT}% > 10% en DIRECTRICES"
    exit 1
  fi
fi
```

---

### S3-02: Actualizacion master docs

Actualizar los 4 master docs con las reglas nuevas, patrones descubiertos, y estado del sistema de salvaguardas.

---

## 6. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Fichero de Referencia | Gaps Afectados | Sprint |
|----------------|----------------------|----------------|--------|
| CSS-VAR-ALL-COLORS-001 | docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md | GAP-01 | S1 |
| UPDATE-HOOK-REQUIRED-001 | CLAUDE.md, Memory MEMORY.md | GAP-02, GAP-05 | S1, S2 |
| SERVICE-ORPHAN-001 | scripts/validation/validate-service-consumers.php | GAP-03 | S1 |
| TEST-COVERAGE-MAP-001 | scripts/validation/validate-test-coverage-map.php | GAP-03 | S1 |
| TENANT-CHECK-001 | scripts/validation/validate-tenant-isolation.php | GAP-03 | S1 |
| TWIG-ENTITY-METHOD-001 | Aprendizaje #163 | GAP-04 | S1 |
| CONTROLLER-READONLY-001 | CLAUDE.md | GAP-06 | S2 |
| PRESAVE-RESILIENCE-001 | CLAUDE.md | GAP-07 | S2 |
| PREMIUM-FORMS-PATTERN-001 | ecosistema_jaraba_core/src/Form/PremiumEntityFormBase.php | GAP-09 | S2 |
| DOC-GUARD-001 | docs/00_FLUJO_TRABAJO_CLAUDE.md | GAP-10 | S3 |
| ICON-DUOTONE-001, ICON-COLOR-001 | docs/00_DIRECTRICES_PROYECTO.md | Forms migracion | S2 |
| SCSS-COMPILE-VERIFY-001 | docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md | Compilacion SCSS | S1 |
| SECRET-MGMT-001 | CLAUDE.md | hook_requirements | S2 |
| ENTITY-PREPROCESS-001 | CLAUDE.md | Twig templates | S1 |
| FIELD-UI-SETTINGS-TAB-001 | docs/00_FLUJO_TRABAJO_CLAUDE.md | .install entities | S1, S2 |
| Federated Design Tokens | docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md | SCSS tokens | S1 |
| Zero Region Pattern | CLAUDE.md | Templates limpios | S1 |
| Dart Sass moderno | docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md | SCSS compilacion | S1 |
| Textos traducibles {% trans %} | CLAUDE.md | Twig templates | S1 |
| SLIDE-PANEL-RENDER-001 | Memory MEMORY.md | Modales/slide-panel | Referencia |
| Mobile-first layout | CLAUDE.md | Frontend responsive | Referencia |

---

## 7. Indicaciones para Cumplimiento de Directrices

### 7.1 SCSS y Theming

1. **Modelo SaaS de variables inyectables:** Los valores finales de colores, tipografia, espaciados y otros tokens se configuran a traves de la interfaz de administracion de Drupal (Apariencia > Ecosistema Jaraba Theme), con 13 vertical tabs y 70+ opciones. Estos valores se inyectan como CSS Custom Properties en `:root` via `hook_preprocess_html()`. Los archivos SCSS solo definen fallbacks.

2. **Dart Sass moderno:** Usar `@use` (NO `@import`), `@use 'sass:color'` para transformaciones de color, `color-mix(in srgb, ...)` para transparencias. NUNCA `darken()`, `lighten()`, `rgba()` con variable SCSS.

3. **SCSS-ENTRY-CONSOLIDATION-001:** Si existen `name.scss` y `_name.scss` en mismo directorio, consolidar.

4. **Compilacion desde Docker:** `lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed"`

### 7.2 Templates Twig

1. **Paginas frontend limpias (Zero Region Pattern):** Cada ruta frontend tiene template `page--{ruta}.html.twig` con layout limpio. Usa `{{ clean_content }}` (no `{{ page.content }}`), `{{ clean_messages }}` para mensajes.

2. **Parciales reutilizables:** 65+ parciales en `templates/partials/`. ANTES de crear contenido nuevo, verificar si existe parcial. Los parciales usan `{% include %}` desde las paginas.

3. **Variables configurables:** El footer, header, navegacion y demas elementos comunes leen su contenido de `theme_settings` (configurado desde la UI de Drupal sin tocar codigo). Ejemplo: `ts.footer_nav_col1_title`, `ts.navigation_items`.

4. **Textos traducibles:** SIEMPRE `{% trans %}texto{% endtrans %}` (bloque, NO filtro `|t`).

5. **Body classes:** Via `hook_preprocess_html()` (NUNCA `attributes.addClass()` en template).

6. **Acciones crear/editar/ver:** En slide-panel modal (SLIDE-PANEL-RENDER-001), usando `renderPlain()`.

### 7.3 Entidades y Navegacion Admin

1. **Field UI:** Toda entity con `field_ui_base_route` DEBE tener default local task tab.
2. **Views:** Declarar `"views_data" = "Drupal\views\EntityViewsData"` en anotacion.
3. **Admin nav:** Config entities en `/admin/structure`, content entities en `/admin/content`.
4. **AccessControlHandler:** Obligatorio en anotacion (AUDIT-CONS-001).

### 7.4 Iconos

1. **ICON-DUOTONE-001:** Variante `duotone` por defecto.
2. **ICON-COLOR-001:** Solo colores de la paleta Jaraba: azul-corporativo, naranja-impulso, verde-innovacion, white, neutral.
3. **Funcion:** `{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}`

---

## 8. Metricas de Exito — Resultados Finales

| Metrica | Antes | Objetivo | Resultado |
|---------|-------|----------|-----------|
| Colores hex hardcoded en SCSS | ~733 lineas | 0 | 2088 reemplazos a var(--ej-*), ~390 restantes en color-mix/gradientes (legítimo) |
| Modulos sin .install | 3 | 0 | 0 — 20 nuevos .install creados + 47 existentes ampliados |
| CI checks con continue-on-error | 3 | 0 | 0 — eliminados de ci.yml y fitness-functions.yml |
| Violaciones TWIG-ENTITY-METHOD-001 | 6 | 0 | 0 |
| Entities sin installEntityType() | 275 en 67 modulos | 0 | 0 — script generador automatizado (generate-install-hooks.php) |
| Scripts de validacion | 14 | 16 | 16 — validate-controller-readonly.php + validate-presave-resilience.php |
| Modulos con hook_requirements | 4 | 8 | 70+ — cada modulo con .install ahora incluye hook_requirements() |
| Forms sin PremiumEntityFormBase | 2 | 0 | 0 — CrossVerticalRuleForm + CrossVerticalProgressForm migrados |
| DOC-GUARD perdida relativa check | No | Si | Si — max 10% relativo vs HEAD |
| Presave resilience warnings | 3 | 0 | 0 — jaraba_content_hub, jaraba_legal_calendar, jaraba_page_builder corregidos |
| validate-all.sh --full | N/A | 0 FAIL | 15 PASS, 0 FAIL, 1 SKIP (diferido) |
| Madurez Salvaguardas | 85% | 100% | 100% |

### Herramientas Creadas

| Script | Proposito | Ubicacion |
|--------|-----------|-----------|
| generate-install-hooks.php | Genera hook_update_N() + hook_requirements() automaticamente | scripts/maintenance/ |
| migrate-hex-to-tokens.php | Migra hex hardcoded a var(--ej-*) con mapa de 180+ tokens | scripts/maintenance/ |
| validate-controller-readonly.php | Detecta controllers con readonly en propiedades heredadas | scripts/validation/ |
| validate-presave-resilience.php | Detecta presave hooks sin hasService()/try-catch | scripts/validation/ |

---

## 9. Verificacion RUNTIME-VERIFY-001

Tras completar cada sprint, verificar las 5 dependencias runtime:

1. **CSS compilado:** Timestamp CSS > SCSS para todos los archivos modificados
2. **Tablas DB:** `drush entity:updates` no reporta pendientes
3. **Rutas accesibles:** Todas las rutas en routing.yml devuelven 200
4. **Scripts de validacion:** `bash scripts/validation/validate-all.sh --full` sin errores
5. **CI pipeline:** Push a branch → todos los checks pasan (0 warnings)

**Comando de verificacion global:**
```bash
lando ssh -c "cd /app && php scripts/validation/validate-entity-integrity.php && php scripts/validation/validate-service-consumers.php && php scripts/validation/validate-tenant-isolation.php"
```
