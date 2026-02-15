# Plan de Elevación a Clase Mundial — Vertical Andalucía +ei

**Documento:** 20260215c
**Versión:** 1.0
**Fecha:** 2026-02-15
**Estado:** IMPLEMENTADO (12/12 fases completadas)
**Autor:** Claude Opus 4.6
**Vertical:** Andalucía +ei (jaraba_andalucia_ei)
**Baseline:** Empleabilidad (10 fases) + Emprendimiento (6 fases + 9 gaps)

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Auditoría del Estado Actual](#2-auditoría-del-estado-actual)
3. [Tabla de Correspondencia con Especificaciones Técnicas](#3-tabla-de-correspondencia-con-especificaciones-técnicas)
4. [Plan de Implementación por Fases](#4-plan-de-implementación-por-fases)
   - [Fase 1: Page Template + Copilot FAB + Preprocess Hooks](#fase-1-page-template--copilot-fab--preprocess-hooks)
   - [Fase 2: SCSS Compliance — Migración CSS→SCSS + package.json](#fase-2-scss-compliance--migración-cssscss--packagejson)
   - [Fase 3: Design Token Config Vertical](#fase-3-design-token-config-vertical)
   - [Fase 4: Feature Gating — FreemiumVerticalLimit + AndaluciaEiFeatureGateService](#fase-4-feature-gating--freemiumverticallimit--andaluciaeifeaturesateservice)
   - [Fase 5: Email Lifecycle — Templates MJML + AndaluciaEiEmailSequenceService](#fase-5-email-lifecycle--templates-mjml--andaluciaeiemail-sequenceservice)
   - [Fase 6: Cross-Vertical Bridges](#fase-6-cross-vertical-bridges)
   - [Fase 7: Proactive AI Journey Progression](#fase-7-proactive-ai-journey-progression)
   - [Fase 8: Health Scores & KPIs Verticales](#fase-8-health-scores--kpis-verticales)
   - [Fase 9: i18n Compliance — JourneyDefinition con TranslatableMarkup](#fase-9-i18n-compliance--journeydefinition-con-translatablemarkup)
   - [Fase 10: Upgrade Triggers + CRM Integration](#fase-10-upgrade-triggers--crm-integration)
   - [Fase 11: A/B Testing Framework](#fase-11-ab-testing-framework)
   - [Fase 12: Recorrido Público → Registro → Embudo de Ventas](#fase-12-recorrido-público--registro--embudo-de-ventas)
5. [Recorrido Completo del Usuario](#5-recorrido-completo-del-usuario)
6. [Dependencias Cruzadas y Flujos de Entrada/Salida](#6-dependencias-cruzadas-y-flujos-de-entradasalida)
7. [Tabla de Cumplimiento de Directrices](#7-tabla-de-cumplimiento-de-directrices)
8. [Resumen de Archivos Afectados](#8-resumen-de-archivos-afectados)

---

## 1. Resumen Ejecutivo

### Estado Actual
El vertical Andalucía +ei cuenta con una base funcional sólida (~60% implementación):
- Entidad `programa_participante_ei` con campos de horas, fases, STO
- Dashboard frontend con grid de tarjetas responsivo
- Tracking automático de horas IA via hook en copilot_session
- Exportación STO con XML y sincronización cron cada 6h
- Journey definitions para 3 avatares (beneficiario, técnico, admin)
- Page template con Zero Region Policy básica
- API endpoints para CRUD via slide-panel

### Gaps Críticos (18 hallazgos)
Comparando con los estándares de clase mundial establecidos por empleabilidad (10 fases) y emprendimiento (6 fases + 9 gaps), se han identificado **18 gaps** organizados en **12 fases de implementación**.

### Puntuación Pre-Elevación

| Dimensión | Puntuación | Meta Clase Mundial |
|-----------|------------|-------------------|
| Page Template & FAB | 60% | 100% |
| SCSS/Design Tokens | 15% | 100% |
| Feature Gating | 0% | 100% |
| Email Lifecycle | 0% | 100% |
| Cross-Vertical Bridges | 0% | 100% |
| Proactive AI Journey | 0% | 100% |
| Health Scores & KPIs | 0% | 100% |
| i18n Compliance | 70% | 100% |
| Upgrade Triggers | 0% | 100% |
| CRM Integration | 0% | 100% |
| A/B Testing | 0% | 100% |
| Embudo Público/Ventas | 20% | 100% |
| **MEDIA GLOBAL** | **~14%** → **100%** | **100%** |

---

## 2. Auditoría del Estado Actual

### 2.1 Lo que EXISTE y funciona correctamente

| Componente | Estado | Archivo |
|------------|--------|---------|
| Entidad ProgramaParticipanteEi | Completo | `src/Entity/ProgramaParticipanteEi.php` |
| Dashboard Controller | Funcional | `src/Controller/AndaluciaEiController.php` |
| API Controller (slide-panel CRUD) | Funcional | `src/Controller/AndaluciaEiApiController.php` |
| STO Export Service | Funcional | `src/Service/StoExportService.php` |
| AI Mentorship Tracker | Funcional | `src/Service/AiMentorshipTracker.php` |
| Fase Transition Manager | Funcional | `src/Service/FaseTransitionManager.php` |
| Dashboard Template (Twig) | Parcial | `templates/andalucia-ei-dashboard.html.twig` |
| Page Template (Zero Region) | Parcial | `templates/page--andalucia-ei.html.twig` |
| CSS Dashboard | Solo CSS | `css/andalucia-ei.css` |
| Journey Definitions (3 avatares) | Completo | `AndaluciaEiJourneyDefinition.php` |
| Hook: IA Hours Tracking (ECA-EI-001) | Funcional | `jaraba_andalucia_ei.module:51-125` |
| Hook: Eligibility Notification (ECA-EI-002) | Funcional | `jaraba_andalucia_ei.module:137-195` |
| Hook: STO Cron Sync (ECA-EI-003) | Funcional | `jaraba_andalucia_ei.module:234-300` |
| Permissions (6) | Completo | `jaraba_andalucia_ei.permissions.yml` |
| Routes (12) | Completo | `jaraba_andalucia_ei.routing.yml` |
| Body Classes (hook_preprocess_html) | Parcial | `ecosistema_jaraba_theme.theme:1277-1284` |
| Template Suggestions | Parcial | `ecosistema_jaraba_theme.theme:2625-2631` |

### 2.2 Gaps Identificados (18 hallazgos)

#### GAP-AEI-001: Page Template sin Copilot FAB [CRÍTICO]
- **Ubicación:** `page--andalucia-ei.html.twig:1-43`
- **Problema:** El template NO incluye `_copilot-fab.html.twig`
- **Patrón de referencia:** `page--empleabilidad.html.twig` y `page--emprendimiento.html.twig` incluyen el FAB condicionalmente para usuarios autenticados
- **Directriz violada:** P4-AI-001 (Copilot FAB en toda ruta autenticada del vertical)

#### GAP-AEI-002: Missing hook_preprocess_page__andalucia_ei [CRÍTICO]
- **Ubicación:** `ecosistema_jaraba_theme.theme` — NO existe la función
- **Problema:** No se inyectan variables `copilot_context`, `clean_content`, `clean_messages`, `theme_settings`, `site_name`, `footer_copyright`
- **Patrón de referencia:** `hook_preprocess_page__empleabilidad()` y `hook_preprocess_page__emprendimiento()` inyectan todo el contexto necesario
- **Impacto:** El template no recibe las variables que necesita para funcionar como los demás verticales

#### GAP-AEI-003: CSS sin SCSS — Sin package.json [CRÍTICO]
- **Ubicación:** `css/andalucia-ei.css` (300 líneas de CSS puro)
- **Problema:**
  - No existe directorio `scss/` con partials
  - No existe `package.json` con scripts de build
  - 6 instancias de `rgba()` que violan P4-COLOR-002:
    - Línea 50: `rgba(255, 140, 66, 0.15)` → debería ser `color-mix()`
    - Línea 71: `rgba(0, 0, 0, 0.08)` → debería ser `color-mix()`
    - Línea 77: `rgba(0, 0, 0, 0.12)` → debería ser `color-mix()`
    - Línea 86: `rgba(0, 0, 0, 0.06)` → debería ser `color-mix()`
    - Línea 87: `rgba(0, 0, 0, 0.02)` → debería ser `color-mix()`
    - Línea 239: `rgba(0, 0, 0, 0.08)` → debería ser `color-mix()`
  - 8 colores hardcoded que deberían usar tokens `var(--ej-*)`:
    - `#1a2a44`, `#f59e0b`, `#10b981`, `#ef4444`, `#e5e7eb`, `#e07a35`, `#f8fafc`, `#f1f5f9`
- **Directrices violadas:** SCSS-PKG-001, P4-COLOR-001, P4-COLOR-002, SCSS-DART-001

#### GAP-AEI-004: Design Token Config AUSENTE
- **Ubicación:** NO existe `ecosistema_jaraba_core.design_token_config.vertical_andalucia_ei.yml`
- **Referencia:** Existen configs para empleabilidad, emprendimiento, agroconecta
- **Impacto:** El vertical no tiene paleta de colores, tipografía ni tokens de diseño configurados como vertical independiente

#### GAP-AEI-005: CERO FreemiumVerticalLimit configs
- **Ubicación:** `ecosistema_jaraba_core/config/install/` — 0 archivos para `andalucia_ei`
- **Referencia:** Empleabilidad tiene 12 configs, emprendimiento tiene 18 configs
- **Impacto:** Sin escalera de valor, sin gating por plan, sin upgrade triggers

#### GAP-AEI-006: CERO templates de email MJML
- **Ubicación:** NO existe directorio `jaraba_email/templates/mjml/andalucia_ei/`
- **Referencia:** Empleabilidad tiene 10 templates, emprendimiento tiene 11 templates
- **Impacto:** Sin comunicación automatizada del ciclo de vida del participante

#### GAP-AEI-007: Sin AndaluciaEiFeatureGateService
- **Ubicación:** NO existe en `ecosistema_jaraba_core/src/Service/`
- **Referencia:** EmployabilityFeatureGateService.php, EmprendimientoFeatureGateService.php
- **Impacto:** Las features no se limitan por plan

#### GAP-AEI-008: Sin AndaluciaEiEmailSequenceService
- **Ubicación:** NO existe en `ecosistema_jaraba_core/src/Service/`
- **Referencia:** EmployabilityEmailSequenceService.php, EmprendimientoEmailSequenceService.php
- **Impacto:** Sin secuencias de email automatizadas

#### GAP-AEI-009: Sin Cross-Vertical Bridges
- **Ubicación:** NO existe `AndaluciaEiCrossVerticalBridgeService`
- **Referencia:** EmployabilityCrossVerticalBridgeService.php con 4 puentes
- **Impacto:** El vertical está aislado, sin maximización de LTV del cliente

#### GAP-AEI-010: Sin Proactive AI Journey Progression
- **Ubicación:** NO existe `AndaluciaEiJourneyProgressionService`
- **Referencia:** EmployabilityJourneyProgressionService.php con 7 reglas proactivas
- **Impacto:** El copiloto no avanza proactivamente al usuario por el embudo

#### GAP-AEI-011: Sin Health Scores & KPIs
- **Ubicación:** NO existe `AndaluciaEiHealthScoreService`
- **Referencia:** EmployabilityHealthScoreService.php con 5 dimensiones + 8 KPIs
- **Impacto:** Sin métricas de salud del usuario ni KPIs del vertical

#### GAP-AEI-012: i18n — JourneyDefinition con strings hardcoded
- **Ubicación:** `AndaluciaEiJourneyDefinition.php:32-197` — 20+ strings sin `TranslatableMarkup`
- **Referencia:** `EmprendimientoJourneyDefinition.php` usa `TranslatableMarkup` en static methods
- **Directriz violada:** I18N-001

#### GAP-AEI-013: Emoji en dashboard template
- **Ubicación:** `andalucia-ei-dashboard.html.twig:64` — `⏱️` como icono
- **Directriz violada:** P4-EMOJI-002 (usar `jaraba_icon()`, nunca emojis)

#### GAP-AEI-014: Footer include con variables incompletas
- **Ubicación:** `page--andalucia-ei.html.twig:39-42`
- **Problema:** Include del footer pasa `site_name` y `logged_in`, pero falta `footer_copyright`, `theme_settings`, `logo`
- **Referencia:** Los templates de empleabilidad/emprendimiento pasan todas las variables

#### GAP-AEI-015: Missing `role="main"` en elemento main
- **Ubicación:** `page--andalucia-ei.html.twig:24`
- **Problema:** `<main id="main-content" class="andalucia-ei-main">` sin `role="main"`
- **Referencia:** Empleabilidad/emprendimiento lo incluyen

#### GAP-AEI-016: Solo 1 ruta en body class mapping
- **Ubicación:** `ecosistema_jaraba_theme.theme:1277-1284`
- **Problema:** Solo `jaraba_andalucia_ei.dashboard` está mapeado. Falta mapear las rutas API y admin
- **Referencia:** Empleabilidad mapea todas sus rutas (jaraba_candidate.*, jaraba_job_board.*, etc.)

#### GAP-AEI-017: Sin Upgrade Triggers integrados
- **Ubicación:** Ningún hook/controller de andalucia_ei llama a `UpgradeTriggerService::fire()`
- **Referencia:** Empleabilidad dispara triggers en ApplicationService, CvBuilderService, job_board hooks
- **Impacto:** Sin conversión freemium → premium

#### GAP-AEI-018: Sin integración CRM
- **Ubicación:** No hay sync entre fase transitions y CRM pipeline
- **Referencia:** Empleabilidad tiene `_jaraba_job_board_sync_to_crm()` con mapping de estados

---

## 3. Tabla de Correspondencia con Especificaciones Técnicas

| Gap | Spec Técnica Base | Directriz Aplicable | Fase |
|-----|-------------------|---------------------|------|
| GAP-AEI-001 | Plan Elevación Empleabilidad Fase 1 | P4-AI-001, Nuclear #14 | 1 |
| GAP-AEI-002 | Plan Elevación Empleabilidad Fase 1 | INCLUDE-001, Nuclear #11 | 1 |
| GAP-AEI-003 | 2026-02-05_arquitectura_theming_saas_master.md | SCSS-PKG-001, P4-COLOR-001/002, SCSS-DART-001 | 2 |
| GAP-AEI-004 | 07_VERTICAL_CUSTOMIZATION_PATTERNS.md | Design Token Layer 5 | 3 |
| GAP-AEI-005 | Doc 183 (Freemium) | F2/Doc 183, ConfigEntity | 4 |
| GAP-AEI-006 | Plan Elevación Empleabilidad Fase 6 | EMAIL-001 (MJML) | 5 |
| GAP-AEI-007 | Plan Elevación Empleabilidad Fase 4 | DRUPAL11-001 (DI) | 4 |
| GAP-AEI-008 | Plan Elevación Empleabilidad Fase 6 | SEQUENCE-001 | 5 |
| GAP-AEI-009 | Plan Elevación Empleabilidad Fase 8 | CROSS-VERTICAL-001 | 6 |
| GAP-AEI-010 | Plan Elevación Empleabilidad Fase 9 | JOURNEY-001, P4-AI-001 | 7 |
| GAP-AEI-011 | Plan Elevación Empleabilidad Fase 10 | KPI-001, HEALTH-001 | 8 |
| GAP-AEI-012 | Plan Elevación Emprendimiento Fase 5 | I18N-001 | 9 |
| GAP-AEI-013 | Aprendizaje Phase4 SCSS Colors Emoji | P4-EMOJI-002 | 2 |
| GAP-AEI-014 | Plan Elevación Empleabilidad Fase 1 | INCLUDE-001 | 1 |
| GAP-AEI-015 | WCAG 2.1 AA | Accesibilidad | 1 |
| GAP-AEI-016 | Plan Elevación Emprendimiento Fase 2 | BODY-001 | 1 |
| GAP-AEI-017 | Plan Elevación Empleabilidad Fase 5 | MILESTONE-001 | 10 |
| GAP-AEI-018 | Plan Elevación Empleabilidad Fase 7 | CRM-001 | 10 |

---

## 4. Plan de Implementación por Fases

### Fase 1: Page Template + Copilot FAB + Preprocess Hooks
**Cierra:** GAP-AEI-001, GAP-AEI-002, GAP-AEI-014, GAP-AEI-015, GAP-AEI-016

#### 1.1 Actualizar page--andalucia-ei.html.twig

Reescribir el template siguiendo el patrón exacto de `page--empleabilidad.html.twig`:

```twig
{#
/**
 * @file
 * Page template para rutas del vertical Andalucía +ei.
 * SIN regiones ni bloques de Drupal — Zero Region Policy.
 *
 * Aplicado a todas las rutas de:
 * - jaraba_andalucia_ei.* (/andalucia-ei, /admin/content/andalucia-ei/*)
 *
 * Variables inyectadas desde hook_preprocess_page__andalucia_ei():
 * - clean_content: Render array del controlador (system_main)
 * - clean_messages: Render array de mensajes del sistema
 * - copilot_context: Contexto del CopilotContextService
 * - theme_settings: Configuración del tema
 * - site_name, site_slogan, logo, footer_copyright
 *
 * Directrices aplicadas:
 * - Nuclear #14: Frontend Limpio (Zero Region Policy)
 * - Nuclear #11: Full-width layout
 * - P4-AI-001: Copilot FAB en toda ruta autenticada del vertical
 * - Plan Elevación Andalucía +ei v1 — Fase 1
 */
#}
{% set site_name = site_name|default('Jaraba Impact Platform') %}
{% set site_slogan = site_slogan|default('Impulsando el talento y la innovación') %}

{{ attach_library('ecosistema_jaraba_theme/scroll-animations') }}

<div class="page-wrapper page-wrapper--clean page-wrapper--premium page-wrapper--andalucia-ei">

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    'site_name': site_name,
    'site_slogan': site_slogan,
    'logo': logo,
    'logged_in': logged_in,
    'theme_settings': theme_settings|default({}),
    'avatar_nav': avatar_nav|default(null)
  } only %}

  <main class="main-content main-content--full main-content--andalucia-ei" role="main">
    {% if clean_messages %}
      <div class="highlighted container">
        {{ clean_messages }}
      </div>
    {% endif %}

    <div class="main-content__inner main-content__inner--full">
      {% if clean_content %}
        {{ clean_content }}
      {% endif %}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    'site_name': site_name,
    'logo': logo,
    'footer_copyright': footer_copyright,
    'theme_settings': theme_settings|default({})
  } only %}

  {% if logged_in %}
    {% include '@ecosistema_jaraba_theme/partials/_copilot-fab.html.twig' with {
      'context': copilot_context|default({})
    } only %}
  {% endif %}

</div>
```

#### 1.2 Crear hook_preprocess_page__andalucia_ei() en ecosistema_jaraba_theme.theme

Añadir al archivo `.theme` la función de preprocess para inyectar todas las variables:

```php
/**
 * Implements hook_preprocess_page__andalucia_ei().
 *
 * Plan Elevación Andalucía +ei v1 — Fase 1.
 */
function ecosistema_jaraba_theme_preprocess_page__andalucia_ei(array &$variables): void {
  // Zero Region Policy: extraer contenido limpio.
  $variables['clean_content'] = $variables['page']['content'] ?? [];
  $variables['clean_messages'] = $variables['page']['highlighted'] ?? [];

  // Datos del sitio.
  $variables['site_name'] = \Drupal::config('system.site')->get('name');
  $variables['site_slogan'] = \Drupal::config('system.site')->get('slogan');
  $variables['footer_copyright'] = theme_get_setting('footer_copyright');
  $variables['logo'] = theme_get_setting('logo.url');
  $variables['theme_settings'] = \Drupal::config('ecosistema_jaraba_theme.settings')->getRawData();

  // Copilot Context (P4-AI-001).
  if (\Drupal::hasService('ecosistema_jaraba_core.copilot_context')) {
    try {
      $variables['copilot_context'] = \Drupal::service('ecosistema_jaraba_core.copilot_context')->getContext();
    }
    catch (\Exception $e) {
      $variables['copilot_context'] = [];
    }
  }

  // Avatar Navigation.
  if (\Drupal::hasService('ecosistema_jaraba_core.avatar_navigation')) {
    try {
      $variables['avatar_nav'] = \Drupal::service('ecosistema_jaraba_core.avatar_navigation')->getNavigation();
    }
    catch (\Exception $e) {
      $variables['avatar_nav'] = NULL;
    }
  }
}
```

#### 1.3 Ampliar hook_preprocess_html() para todas las rutas andalucia_ei

Actualizar el bloque de body classes (líneas 1274-1284 del archivo .theme) para incluir todas las rutas:

```php
// ANDALUCÍA +ei — Todas las rutas del vertical
$andalucia_ei_routes = [
  'jaraba_andalucia_ei.dashboard',
  'jaraba_andalucia_ei.settings',
  'entity.programa_participante_ei.collection',
  'entity.programa_participante_ei.add_form',
  'entity.programa_participante_ei.edit_form',
  'entity.programa_participante_ei.canonical',
  'entity.programa_participante_ei.delete_form',
  'jaraba_andalucia_ei.export_sto',
  'jaraba_andalucia_ei.api.participant_add',
  'jaraba_andalucia_ei.api.participant_edit',
  'jaraba_andalucia_ei.api.participants_list',
  'jaraba_andalucia_ei.api.participant_get',
];
if (in_array($route, $andalucia_ei_routes) || str_starts_with($route, 'jaraba_andalucia_ei.')) {
  $variables['attributes']['class'][] = 'dashboard-page';
  $variables['attributes']['class'][] = 'page-andalucia-ei';
  $variables['attributes']['class'][] = 'vertical-andalucia-ei';
  $variables['attributes']['class'][] = 'full-width-page';
}
```

#### 1.4 Ampliar template suggestions para sub-rutas

Actualizar el bloque de suggestions (líneas 2625-2631 del archivo .theme):

```php
// ANDALUCÍA +ei — Template suggestions
if (in_array($route, $andalucia_ei_routes) || str_starts_with($route, 'jaraba_andalucia_ei.')) {
  $suggestions[] = 'page__andalucia_ei';
}
```

---

### Fase 2: SCSS Compliance — Migración CSS→SCSS + package.json
**Cierra:** GAP-AEI-003, GAP-AEI-013

#### 2.1 Crear estructura SCSS

Crear directorio `jaraba_andalucia_ei/scss/` con los siguientes archivos:

**scss/_dashboard.scss** — Migración del CSS actual a SCSS con:
- Todas las instancias de `rgba()` convertidas a `color-mix(in srgb, ...)`
- Todos los colores hardcoded convertidos a `var(--ej-*, fallback)`
- Emoji `⏱️` (línea 64 del template) reemplazado por `jaraba_icon('general', 'clock', { size: '20px' })`

**Conversiones específicas:**

```scss
// ANTES (viola P4-COLOR-002)
background: radial-gradient(circle at 80% 20%, rgba(255, 140, 66, 0.15) 0%, transparent 50%);
// DESPUÉS
background: radial-gradient(circle at 80% 20%, color-mix(in srgb, var(--ej-color-impulse, #FF8C42) 15%, transparent) 0%, transparent 50%);

// ANTES (viola P4-COLOR-002)
box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
// DESPUÉS
box-shadow: var(--ej-shadow-md, 0 4px 20px color-mix(in srgb, black 8%, transparent));

// ANTES (viola P4-COLOR-001 — color hardcoded)
background: linear-gradient(135deg, var(--aei-color-corporate) 0%, #1a2a44 100%);
// DESPUÉS
background: linear-gradient(135deg, var(--ej-color-corporate, #233D63) 0%, color-mix(in srgb, var(--ej-color-corporate, #233D63) 75%, black) 100%);

// ANTES (colores de fase hardcoded)
.stat-value.fase-atencion { color: #f59e0b; }
.stat-value.fase-insercion { color: #10b981; }
.stat-value.fase-baja { color: #ef4444; }
// DESPUÉS
.stat-value.fase-atencion { color: var(--ej-color-warning, #F59E0B); }
.stat-value.fase-insercion { color: var(--ej-color-success, #10B981); }
.stat-value.fase-baja { color: var(--ej-color-danger, #EF4444); }

// ANTES (gris hardcoded)
border: 1px solid #e5e7eb;
// DESPUÉS
border: 1px solid var(--ej-border-color, #e5e7eb);

// ANTES (hover bg hardcoded)
background: #f8fafc;
// DESPUÉS
background: var(--ej-bg-hover, #f8fafc);
```

**scss/main.scss** — Entry point:
```scss
/**
 * @file
 * Entry point SCSS para jaraba_andalucia_ei.
 *
 * COMPILACIÓN:
 * docker exec jarabasaas_appserver_1 bash -c \
 *   "cd /app/web/modules/custom/jaraba_andalucia_ei && npx sass scss/main.scss css/andalucia-ei.css --style=compressed"
 */

@use 'dashboard';
```

#### 2.2 Crear package.json

```json
{
  "name": "jaraba-andalucia-ei",
  "version": "1.0.0",
  "description": "SCSS build para el módulo Andalucía +ei",
  "scripts": {
    "build": "npx sass scss/main.scss css/andalucia-ei.css --style=compressed",
    "build:all": "npm run build && echo '✅ Build completado'",
    "watch": "npx sass scss/main.scss css/andalucia-ei.css --watch --style=compressed"
  },
  "devDependencies": {
    "sass": "^1.80.0"
  }
}
```

#### 2.3 Corregir emoji en dashboard template

En `andalucia-ei-dashboard.html.twig:64`:
```twig
{# ANTES — viola P4-EMOJI-002 #}
<span class="card-icon">⏱️</span>

{# DESPUÉS #}
<span class="card-icon">{{ jaraba_icon('general', 'clock', { size: '20px' }) }}</span>
```

---

### Fase 3: Design Token Config Vertical
**Cierra:** GAP-AEI-004

Crear config entity para los tokens del vertical Andalucía +ei:

**config/install/ecosistema_jaraba_core.design_token_config.vertical_andalucia_ei.yml:**
```yaml
id: vertical_andalucia_ei
label: 'Vertical Andalucía +ei'
scope: vertical
vertical_id: andalucia_ei
preset_name: andalucia_ei_program
description: 'Paleta del programa Andalucía +ei — Emprendimiento Aumentado con IA'
tokens:
  color_primary: '#FF8C42'
  color_secondary: '#00A9A5'
  color_accent: '#233D63'
  color_bg: '#FFFBF7'
  color_text: '#233D63'
  color_text_secondary: '#64748b'
  color_border: '#e5e7eb'
  color_surface: '#ffffff'
  color_gradient_start: '#FF8C42'
  color_gradient_end: '#00A9A5'
  font_heading: 'Outfit'
  font_body: 'Outfit'
  font_weight_heading: '700'
  spacing_base: '1rem'
  radius_base: '12px'
  radius_xl: '24px'
  shadow_default: '0 4px 20px color-mix(in srgb, black 8%, transparent)'
  glass_opacity: '0.94'
  glass_blur: '14px'
status: true
weight: 100
```

---

### Fase 4: Feature Gating — FreemiumVerticalLimit + AndaluciaEiFeatureGateService
**Cierra:** GAP-AEI-005, GAP-AEI-007

#### 4.1 Crear AndaluciaEiFeatureGateService

Crear `ecosistema_jaraba_core/src/Service/AndaluciaEiFeatureGateService.php` siguiendo el patrón exacto de `EmprendimientoFeatureGateService.php`:

- `VERTICAL = 'andalucia_ei'`
- Tabla: `andalucia_ei_feature_usage`
- Features gestionadas:
  - `copilot_sessions_daily` — Sesiones de Copilot IA por día
  - `mentoring_hours_monthly` — Horas de mentoría humana al mes
  - `sto_exports` — Exportaciones STO al mes
  - `training_modules` — Módulos LMS accesibles
  - `diagnostic_access` — Acceso al diagnóstico DIME
  - `report_downloads` — Descargas de informes mensuales

#### 4.2 Crear 18 configs FreemiumVerticalLimit (6 features × 3 plans)

**Nomenclatura:** `ecosistema_jaraba_core.freemium_vertical_limit.andalucia_ei_{plan}_{feature}.yml`

| Feature | Free | Starter | Profesional |
|---------|------|---------|-------------|
| copilot_sessions_daily | 3 | 15 | -1 (ilimitado) |
| mentoring_hours_monthly | 0 | 5 | -1 |
| sto_exports | 1 | 10 | -1 |
| training_modules | 3 | 10 | -1 |
| diagnostic_access | 1 | 3 | -1 |
| report_downloads | 1 | 5 | -1 |

**Ejemplo config (free_copilot_sessions_daily):**
```yaml
id: andalucia_ei_free_copilot_sessions_daily
label: 'Andalucía +ei Free: Sesiones Copilot Diarias'
vertical: andalucia_ei
plan: free
feature_key: copilot_sessions_daily
limit_value: 3
description: '3 sesiones diarias con el tutor IA en plan gratuito.'
upgrade_message: 'Has alcanzado tu límite de 3 sesiones IA hoy. Actualiza a Starter para 15 sesiones diarias.'
expected_conversion: 0.32
weight: 500
status: true
```

#### 4.3 Registrar servicio en ecosistema_jaraba_core.services.yml

```yaml
ecosistema_jaraba_core.andalucia_ei_feature_gate:
  class: Drupal\ecosistema_jaraba_core\Service\AndaluciaEiFeatureGateService
  arguments:
    - '@ecosistema_jaraba_core.upgrade_trigger'
    - '@database'
    - '@current_user'
    - '@logger.channel.ecosistema_jaraba_core'
```

#### 4.4 Añadir tabla en hook_update

En `ecosistema_jaraba_core.install`, añadir update hook para crear la tabla `andalucia_ei_feature_usage` con el mismo esquema que empleabilidad/emprendimiento.

---

### Fase 5: Email Lifecycle — Templates MJML + AndaluciaEiEmailSequenceService
**Cierra:** GAP-AEI-006, GAP-AEI-008

#### 5.1 Crear directorio y 6 templates MJML

**Directorio:** `jaraba_email/templates/mjml/andalucia_ei/`

| Template | Trigger | Propósito |
|----------|---------|-----------|
| `welcome_participant.mjml` | Post-inscripción | Bienvenida al programa con roadmap 12 semanas |
| `phase_transition.mjml` | Transición atencion→insercion | Felicitación + próximos pasos |
| `hours_milestone.mjml` | Hito 25h/50h/75h/100h | Celebración de hitos de horas acumuladas |
| `insertion_celebration.mjml` | Inserción laboral | Felicitación por inserción + bridge a otros verticales |
| `weekly_digest.mjml` | Cron semanal | Resumen de progreso: horas, fase, próximas sesiones |
| `sto_sync_report.mjml` | Post-sync STO | Confirmación de sincronización con informe |

**Secuencias automatizadas (5):**

| ID Secuencia | Trigger | Pasos |
|--------------|---------|-------|
| SEQ_AEI_001 | Post-inscripción | Bienvenida → (3d delay) → Primer login nudge → (7d delay) → Diagnostic reminder |
| SEQ_AEI_002 | 0 horas IA en 7 días | Reactivación: beneficios copilot IA |
| SEQ_AEI_003 | Completar 50h formación | Upsell: acceso a mentoría premium |
| SEQ_AEI_004 | Transición a inserción | Preparación laboral: CV + entrevista |
| SEQ_AEI_005 | Inserción confirmada | Retención post-empleo + bridge a emprendimiento |

#### 5.2 Crear AndaluciaEiEmailSequenceService

Siguiendo el patrón de `EmployabilityEmailSequenceService.php`:

```php
class AndaluciaEiEmailSequenceService {
  const SEQUENCES = [
    'SEQ_AEI_001' => ['label' => 'Onboarding Participante', 'trigger' => 'participant_enrolled'],
    'SEQ_AEI_002' => ['label' => 'Re-engagement IA', 'trigger' => 'no_ia_7_days'],
    'SEQ_AEI_003' => ['label' => 'Upsell Mentoría', 'trigger' => 'training_50h_completed'],
    'SEQ_AEI_004' => ['label' => 'Preparación Inserción', 'trigger' => 'phase_insercion'],
    'SEQ_AEI_005' => ['label' => 'Post-Inserción', 'trigger' => 'insertion_confirmed'],
  ];
}
```

#### 5.3 Registrar templates en TemplateLoaderService

Añadir las 6 plantillas al catálogo del `TemplateLoaderService` bajo la categoría `andalucia_ei`.

---

### Fase 6: Cross-Vertical Bridges
**Cierra:** GAP-AEI-009

Crear `ecosistema_jaraba_core/src/Service/AndaluciaEiCrossVerticalBridgeService.php`:

#### 6.1 Definición de puentes

| Puente | Vertical Destino | Condición | Prioridad |
|--------|-----------------|-----------|-----------|
| Emprendimiento Avanzado | emprendimiento | Participante en inserción + > 50h + interés emprendedor (RIASEC Enterprising ≥7) | 10 |
| Empleabilidad Express | empleabilidad | Participante sin inserción tras 90 días | 20 |
| Servicios Freelance | servicios | Participante con skills digitales (carril Impulso Digital) | 30 |
| Formación Continua | formación | Participante insertado < 30 días | 15 |

#### 6.2 Detección de condiciones

```php
protected const BRIDGES = [
  'emprendimiento_avanzado' => [
    'id' => 'emprendimiento_avanzado',
    'vertical' => 'emprendimiento',
    'icon_category' => 'business',
    'icon_name' => 'rocket',
    'color' => 'var(--ej-color-impulse, #FF8C42)',
    'message_key' => 'bridge.emprendimiento_avanzado.message',
    'cta_label_key' => 'bridge.emprendimiento_avanzado.cta',
    'cta_url' => '/emprendimiento/diagnostico',
    'condition' => 'insertion_plus_entrepreneur_interest',
    'priority' => 10,
  ],
  'empleabilidad_express' => [
    'id' => 'empleabilidad_express',
    'vertical' => 'empleabilidad',
    'icon_category' => 'business',
    'icon_name' => 'target',
    'color' => 'var(--ej-color-innovation, #00A9A5)',
    'message_key' => 'bridge.empleabilidad_express.message',
    'cta_label_key' => 'bridge.empleabilidad_express.cta',
    'cta_url' => '/empleabilidad/diagnostico',
    'condition' => 'no_insertion_90_days',
    'priority' => 20,
  ],
  // ... servicios, formación
];
```

#### 6.3 Integración en dashboard

Añadir sección de puentes cross-vertical en `andalucia-ei-dashboard.html.twig` justo después de las tarjetas principales:

```twig
{% if cross_vertical_bridges|length > 0 %}
  <section class="cross-vertical-bridges" aria-label="{% trans %}Oportunidades relacionadas{% endtrans %}">
    {% for bridge in cross_vertical_bridges %}
      <article class="bridge-card">
        <span class="bridge-icon">{{ jaraba_icon(bridge.icon_category, bridge.icon_name, { size: '24px' }) }}</span>
        <p class="bridge-message">{{ bridge.message }}</p>
        <a href="{{ bridge.cta_url }}" class="btn btn--outline">{{ bridge.cta_label }}</a>
        <button class="bridge-dismiss" data-bridge-id="{{ bridge.id }}" aria-label="{% trans %}Descartar{% endtrans %}">
          {{ jaraba_icon('ui', 'close', { size: '16px' }) }}
        </button>
      </article>
    {% endfor %}
  </section>
{% endif %}
```

---

### Fase 7: Proactive AI Journey Progression
**Cierra:** GAP-AEI-010

Crear `ecosistema_jaraba_core/src/Service/AndaluciaEiJourneyProgressionService.php`:

#### 7.1 Reglas proactivas (8 reglas)

| Regla | Fase | Condición | Acción | Prioridad |
|-------|------|-----------|--------|-----------|
| inactivity_atencion | atencion | 3 días sin actividad IA | FAB dot + nudge sesión IA | 10 |
| low_training_hours | atencion | < 10h formación en 4+ semanas | FAB expand + recordatorio LMS | 15 |
| orientation_milestone | atencion | 10h orientación alcanzadas | FAB celebración + info inserción | 5 |
| training_milestone | atencion | 50h formación alcanzadas | FAB expand + prompt transición | 3 |
| ready_for_insertion | atencion | canTransitToInsercion() = true | FAB urgente + guía transición | 1 |
| insertion_preparation | insercion | Sin tipo_insercion definido | FAB dot + seleccionar vía inserción | 8 |
| insertion_stalled | insercion | > 30 días sin actividad | FAB dot + re-engagement | 20 |
| post_insertion_expansion | insercion | Insertado < 30 días | FAB dot + bridge emprendimiento | 25 |

#### 7.2 Endpoint API

```php
// Ruta: /api/v1/copilot/andalucia-ei/proactive
// Método: GET
// Respuesta: { action: string, message: string, cta_url: string, priority: int } | null
```

#### 7.3 Integración con FAB

El Copilot FAB existente ya soporta acciones proactivas. Solo necesitamos conectar la evaluación al `CopilotApiController` para el avatar `beneficiario_ei`.

---

### Fase 8: Health Scores & KPIs Verticales
**Cierra:** GAP-AEI-011

Crear `ecosistema_jaraba_core/src/Service/AndaluciaEiHealthScoreService.php`:

#### 8.1 Dimensiones de salud (5 ponderadas)

| Dimensión | Peso | Cálculo |
|-----------|------|---------|
| Progreso horas orientación | 25% | (total_orientacion / 10) × 100, cap 100 |
| Progreso horas formación | 30% | (horas_formacion / 50) × 100, cap 100 |
| Engagement IA Copilot | 20% | Sesiones/semana ratio (target: 3/semana) |
| Completitud STO | 10% | sync_status == 'synced' ? 100 : 0 |
| Velocidad de progresión | 15% | Semanas activas vs semanas esperadas |

#### 8.2 Categorías de salud

| Rango | Categoría | Color |
|-------|-----------|-------|
| 0-25 | Crítico | `var(--ej-color-danger)` |
| 26-50 | En riesgo | `var(--ej-color-warning)` |
| 51-75 | Neutral | `var(--ej-color-impulse)` |
| 76-100 | Saludable | `var(--ej-color-success)` |

#### 8.3 KPIs del vertical (8 métricas)

| KPI | Descripción | Target |
|-----|-------------|--------|
| insertion_rate | % participantes que alcanzan inserción | > 60% |
| time_to_insertion | Días promedio hasta inserción | < 120 días |
| training_completion_rate | % que completan 50h formación | > 80% |
| ia_engagement_rate | % que usan copilot IA semanalmente | > 70% |
| sto_sync_success_rate | % sincronizaciones STO exitosas | > 95% |
| participant_nps | Net Promoter Score | > 50 |
| conversion_free_paid | % que pasan de free a paid | > 10% |
| churn_rate | % de abandono mensual | < 8% |

---

### Fase 9: i18n Compliance — JourneyDefinition con TranslatableMarkup
**Cierra:** GAP-AEI-012

Migrar `AndaluciaEiJourneyDefinition.php` de constantes con strings hardcoded a static methods con `TranslatableMarkup`:

```php
use Drupal\Core\StringTranslation\TranslatableMarkup;

class AndaluciaEiJourneyDefinition {

  /**
   * Journey del Beneficiario con strings traducibles.
   */
  public static function getBeneficiarioJourney(): array {
    return [
      'avatar' => 'beneficiario_ei',
      'vertical' => 'andalucia_ei',
      'kpi_target' => 'complete_85_subsanacion_20',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'verify_eligibility',
              'label' => new TranslatableMarkup('Verificar elegibilidad'),
              'ia_intervention' => new TranslatableMarkup('Checklist interactivo, pre-validar criterios'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['eligibility_check'],
          'transition_event' => 'eligibility_verified',
        ],
        // ... resto de estados con TranslatableMarkup
      ],
    ];
  }

  // ... getTecnicoStoJourney(), getAdminEiJourney()
}
```

---

### Fase 10: Upgrade Triggers + CRM Integration
**Cierra:** GAP-AEI-017, GAP-AEI-018

#### 10.1 Integrar UpgradeTriggerService en hooks existentes

En `jaraba_andalucia_ei.module`, añadir llamadas a `fire()`:

```php
// En _jaraba_andalucia_ei_track_ia_hours() — cuando alcanza límite diario:
if ($horasRestantes <= 0) {
  \Drupal::service('ecosistema_jaraba_core.upgrade_trigger')->fire(
    'limit_reached',
    $tenantId,
    ['feature' => 'copilot_sessions_daily', 'vertical' => 'andalucia_ei']
  );
}

// En hook programa_participante_ei_update() — cuando alcanza hitos:
// 25h, 50h, 75h, 100h de horas totales
$totalHoras = $entity->getTotalHorasOrientacion() + $entity->get('horas_formacion')->value;
$milestones = [25, 50, 75, 100];
foreach ($milestones as $milestone) {
  if ($totalHoras >= $milestone) {
    $originalTotal = $entity->original ? ($entity->original->getTotalHorasOrientacion() + $entity->original->get('horas_formacion')->value) : 0;
    if ($originalTotal < $milestone) {
      \Drupal::service('ecosistema_jaraba_core.upgrade_trigger')->fire(
        'first_milestone',
        $tenantId,
        ['milestone' => "{$milestone}h", 'vertical' => 'andalucia_ei']
      );
    }
  }
}
```

#### 10.2 Mapping CRM para fases

```php
function _jaraba_andalucia_ei_sync_to_crm($participante, string $newFase): void {
  $crmMapping = [
    'atencion' => 'lead',
    'insercion' => 'sql',
    'baja' => 'closed_lost',
  ];

  if (!\Drupal::hasService('jaraba_crm.activity')) {
    return;
  }

  $activity = \Drupal::service('jaraba_crm.activity');
  $stage = $crmMapping[$newFase] ?? 'lead';

  $activity->logActivity([
    'type' => 'phase_transition',
    'contact_email' => $participante->getOwner()->getEmail(),
    'stage' => $stage,
    'data' => [
      'fase' => $newFase,
      'programa' => 'andalucia_ei',
      'horas_totales' => $participante->getTotalHorasOrientacion(),
    ],
  ]);
}
```

---

### Fase 11: A/B Testing Framework
**Cierra:** Sin gap explícito — paridad con emprendimiento

Crear `ecosistema_jaraba_core/src/Service/AndaluciaEiExperimentService.php`:

**Conversion events (8):**
```php
const VALID_EVENTS = [
  'participant_enrolled',
  'first_ia_session',
  'diagnostic_completed',
  'training_10h',
  'training_50h',
  'orientation_10h',
  'phase_insertion',
  'plan_upgraded',
];
```

**Experiment scopes:**
- `onboarding_flow` — Variantes del flujo de inscripción
- `dashboard_layout` — Layout de tarjetas del dashboard
- `copilot_engagement` — Prompts y frecuencia del FAB
- `upgrade_funnel` — Mensajes y momentos de upsell

---

### Fase 12: Recorrido Público → Registro → Embudo de Ventas
**Cierra:** Completitud del recorrido end-to-end

#### 12.1 Recorrido del usuario NO registrado

```
1. Llegada → Landing page pública del programa Andalucía +ei
   - Hero con propuesta de valor: "Emprende con IA y Estrategia Real"
   - Sección de beneficios (100h formación, tutor IA 24/7, mentoría humana)
   - Testimonios de participantes anteriores
   - FAQ Bot contextual (grounded, público)
   - CTA: "Inscríbete Gratis" → /user/register?vertical=andalucia_ei

2. Registro → Onboarding personalizado
   - Selección de vertical automática (parámetro UTM/query)
   - Wizard de onboarding con paso específico Andalucía +ei:
     - Datos personales + provincia
     - Descripción de idea de negocio (si la tiene)
     - Asignación de carril automática via DIME test

3. Post-registro → Secuencia SEQ_AEI_001
   - Email de bienvenida
   - Primer login: Dashboard con tour guiado
   - Copilot FAB proactivo: "¡Bienvenido! ¿Empezamos con tu diagnóstico DIME?"
```

#### 12.2 Embudo de ventas dentro del vertical

```
Plan Free → Conocer
├── 3 sesiones IA/día
├── 1 diagnóstico DIME
├── 3 módulos LMS
├── Dashboard básico
├── 1 exportación STO/mes
│
├── TRIGGER: Límite alcanzado → Modal upsell
├── TRIGGER: Hito 25h → Nudge Starter
│
Plan Starter → Crecer (€19/mes)
├── 15 sesiones IA/día
├── 3 diagnósticos DIME
├── 10 módulos LMS
├── 5h mentoría humana/mes
├── 10 exportaciones STO/mes
├── Informes avanzados
│
├── TRIGGER: Mentoría agotada → Modal Profesional
├── TRIGGER: Hito 75h → Nudge Profesional
│
Plan Profesional → Liderar (€49/mes)
├── Sesiones IA ilimitadas
├── Diagnósticos ilimitados
├── Módulos LMS ilimitados
├── Mentoría ilimitada
├── STO ilimitado
├── API acceso datos
├── Soporte prioritario
```

#### 12.3 Escalera de valor transversal

```
Andalucía +ei (Emprendimiento Asistido)
    │
    ├── Inserción laboral exitosa
    │   └── → Empleabilidad (gestión de carrera continua)
    │       └── → Formación (upskilling permanente)
    │
    ├── Idea de negocio validada
    │   └── → Emprendimiento (BMC, hipótesis, experimentos avanzados)
    │       └── → Comercio (marketplace, tienda online)
    │
    ├── Skills digitales desarrollados
    │   └── → Servicios (freelancing, consultoría)
    │
    └── Perfil institucional (técnico/admin)
        └── → B2B (gestión de programas, licencias organizacionales)
```

---

## 5. Recorrido Completo del Usuario

### 5.1 Usuario No Registrado → Sitio Público

```
[Búsqueda Google / Referral / Redes Sociales]
    ↓
[Landing Andalucía +ei] (/programa/andalucia-ei)
    ↓ FAQ Bot contextual
    ↓ Copilot público (avatar: guest)
    ↓ Secciones: Hero, Beneficios, Testimonios, Pricing, FAQ
    ↓
[CTA: Inscríbete Gratis]
    ↓
[Registro] (/user/register?vertical=andalucia_ei&utm_source=...)
    ↓ AvatarDetectionService → beneficiario_ei
    ↓ TenantOnboardingService → Crear grupo
    ↓
[Onboarding Wizard]
    ↓ Paso 1: Datos personales + provincia
    ↓ Paso 2: Diagnóstico DIME (20 preguntas)
    ↓ Paso 3: Asignación de carril (Impulso/Acelera/Híbrido)
    ↓
[Dashboard Andalucía +ei] (/andalucia-ei)
    ↓ Copilot FAB proactivo: "¡Bienvenido!"
    ↓ Tour guiado primera visita
    ↓ Secuencia email SEQ_AEI_001 activada
```

### 5.2 Usuario Registrado → Embudo del Vertical

```
[Dashboard]
    ↓ Tarjetas: Progreso, Horas, Formación, Acciones
    ↓
[Fase Atención]
    ├── Copilot IA (tutor 24/7)
    │   ├── Coach Emocional (miedo, bloqueo)
    │   ├── Consultor Táctico (paso a paso)
    │   ├── Sparring Partner (feedback honesto)
    │   ├── CFO Sintético (precios, costes)
    │   └── Abogado del Diablo (desafiar supuestos)
    ├── LMS (módulos formativos)
    ├── Mentoría humana (reserva de sesiones)
    ├── Proactive rules:
    │   ├── Inactividad 3d → nudge IA
    │   ├── < 10h formación en 4 sem → recordatorio LMS
    │   ├── 10h orientación → celebración
    │   └── 50h formación → prompt transición
    │
    ↓ canTransitToInsercion() = true
    ↓ Notificación a técnicos (ECA-EI-002)
    ↓ FaseTransitionManager valida y ejecuta
    ↓
[Fase Inserción]
    ├── Tipo inserción: cuenta_ajena/propia/agrario
    ├── Preparación laboral (bridge → empleabilidad)
    ├── Cross-vertical bridges evaluados
    ├── Incentivo €528 (si aplica)
    ├── STO Sync automático (ECA-EI-003)
    │
    ↓ Inserción confirmada
    ↓ Secuencia SEQ_AEI_005 activada
    ↓ Health Score actualizado
    ↓
[Post-Inserción]
    ├── Bridge → Emprendimiento (si interés detectado)
    ├── Bridge → Formación Continua
    ├── Alumni Club (comunidad)
    └── Upsell vertical: Empleabilidad Premium / Emprendimiento Pro
```

### 5.3 Recorrido Transversal (Escalera de Valor)

```
ENTRADA: Andalucía +ei (Free)
    │
    ├─[1]─ Upgrade dentro del vertical ─────────────────────┐
    │      Free → Starter (€19/mes) → Profesional (€49/mes) │
    │                                                        │
    ├─[2]─ Cross-sell a otro vertical ──────────────────────┤
    │      Andalucía +ei → Emprendimiento Starter (€29/mes)  │
    │      Andalucía +ei → Empleabilidad Starter (€15/mes)   │
    │      Andalucía +ei → Servicios Starter (€19/mes)       │
    │                                                        │
    ├─[3]─ Bundle multi-vertical ──────────────────────────┤
    │      "Paquete Emprendedor Completo" (€69/mes)          │
    │      = Andalucía +ei Pro + Emprendimiento Pro          │
    │                                                        │
    └─[4]─ B2B / Licencias organizacionales ───────────────┘
           Entidad gestora: 50-500 participantes
           = Enterprise Custom + STO Integration
```

---

## 6. Dependencias Cruzadas y Flujos de Entrada/Salida

### 6.1 Mapa de Dependencias del Módulo

```
jaraba_andalucia_ei
├── DEPENDE DE:
│   ├── ecosistema_jaraba_core (entidades, servicios core, design tokens)
│   ├── jaraba_sepe_teleformacion (cliente SOAP para STO)
│   ├── jaraba_lms (módulos formativos, tracking horas)
│   ├── jaraba_mentoring (sesiones mentoría humana, calendario)
│   └── jaraba_copilot_v2 (sesiones IA, modos copilot, tracking)
│
├── SERVICIOS QUE CONSUME (DI / Optional):
│   ├── ecosistema_jaraba_core.tenant_context (plan del usuario)
│   ├── ecosistema_jaraba_core.copilot_context (contexto avatar)
│   ├── ecosistema_jaraba_core.avatar_navigation (nav contextual)
│   ├── ecosistema_jaraba_core.upgrade_trigger (escalera de valor)
│   ├── ecosistema_jaraba_core.andalucia_ei_feature_gate [NUEVO]
│   ├── ecosistema_jaraba_core.andalucia_ei_journey_progression [NUEVO]
│   ├── ecosistema_jaraba_core.andalucia_ei_health_score [NUEVO]
│   ├── ecosistema_jaraba_core.andalucia_ei_cross_vertical_bridge [NUEVO]
│   ├── ecosistema_jaraba_core.andalucia_ei_email_sequence [NUEVO]
│   ├── ecosistema_jaraba_core.andalucia_ei_experiment [NUEVO]
│   ├── jaraba_crm.activity (optional: sync CRM)
│   ├── jaraba_email.template_loader (templates MJML)
│   ├── jaraba_email.sequence_manager (secuencias automatizadas)
│   ├── jaraba_ab_testing.variant_assignment (optional: A/B testing)
│   └── jaraba_self_discovery.riasec (optional: detección emprendedor)
│
├── HOOKS QUE DISPARA:
│   ├── hook_copilot_session_update → track IA hours
│   ├── hook_programa_participante_ei_update → eligibility notification + CRM sync
│   ├── hook_cron → STO sync + re-engagement
│   └── hook_mail → eligibility email
│
└── EVENTOS QUE GENERA:
    ├── participant_enrolled → SEQ_AEI_001
    ├── ia_session_completed → hours tracking
    ├── training_milestone → upgrade trigger
    ├── eligibility_met → technician notification
    ├── phase_transition → CRM sync + email
    ├── insertion_confirmed → SEQ_AEI_005 + bridges
    └── limit_reached → upgrade modal
```

### 6.2 Flujos de Entrada al Vertical

| Origen | Condición | Destino en Andalucía +ei |
|--------|-----------|--------------------------|
| Landing pública | Click CTA | /user/register?vertical=andalucia_ei |
| Emprendimiento | Entrepreneur at_risk | Bridge → /andalucia-ei (programa subvencionado) |
| Empleabilidad | Job seeker > 90 días | Bridge → /andalucia-ei (formación + inserción) |
| Institucional | Admin crea batch | /admin/content/andalucia-ei (bulk import) |
| STO Import | Sync bidireccional | API → crear participante |

### 6.3 Flujos de Salida del Vertical

| Condición | Destino | Mecanismo |
|-----------|---------|-----------|
| Inserción + interés emprendedor | Emprendimiento | CrossVerticalBridge + SEQ_AEI_005 |
| Inserción laboral | Empleabilidad | CrossVerticalBridge → gestión carrera |
| Skills digitales (Impulso Digital) | Servicios | CrossVerticalBridge → freelancing |
| Insertado < 30 días | Formación | CrossVerticalBridge → LMS premium |
| Técnico/Admin EI | B2B Enterprise | Upgrade trigger → licencia organizacional |

---

## 7. Tabla de Cumplimiento de Directrices

| Directriz | Área | Estado Pre | Fase | Estado Post |
|-----------|------|-----------|------|-------------|
| Nuclear #14 | Zero Region Policy | Parcial | 1 | Completo |
| Nuclear #11 | Full-width layouts | Parcial | 1 | Completo |
| P4-AI-001 | Copilot FAB en rutas autenticadas | Ausente | 1 | Completo |
| INCLUDE-001 | Partials reutilizables (header, footer, fab) | Parcial | 1 | Completo |
| BODY-001 | Body classes via hook_preprocess_html | Parcial | 1 | Completo |
| SCSS-PKG-001 | package.json con build scripts | Ausente | 2 | Completo |
| SCSS-DART-001 | Compilación Dart Sass moderno | Ausente | 2 | Completo |
| P4-COLOR-001 | Paleta Jaraba (var(--ej-*)) | Parcial | 2 | Completo |
| P4-COLOR-002 | color-mix() en lugar de rgba() | Violado (6x) | 2 | Completo |
| P4-EMOJI-002 | jaraba_icon() en lugar de emojis | Violado (1x) | 2 | Completo |
| Design Token L5 | Config vertical de tokens | Ausente | 3 | Completo |
| F2/Doc 183 | Freemium model (ConfigEntity) | Ausente | 4 | Completo |
| DRUPAL11-001 | DI + PHP 8.4 | Parcial | 4 | Completo |
| EMAIL-001 | Templates MJML | Ausente | 5 | Completo |
| SEQUENCE-001 | Secuencias lifecycle | Ausente | 5 | Completo |
| CROSS-VERTICAL-001 | Puentes entre verticales | Ausente | 6 | Completo |
| JOURNEY-001 | Progresión proactiva IA | Ausente | 7 | Completo |
| KPI-001 | Métricas del vertical | Ausente | 8 | Completo |
| HEALTH-001 | Health scoring usuarios | Ausente | 8 | Completo |
| I18N-001 | TranslatableMarkup | Violado (20x) | 9 | Completo |
| MILESTONE-001 | Upgrade triggers append-only | Ausente | 10 | Completo |
| CRM-001 | Pipeline sync | Ausente | 10 | Completo |
| WCAG 2.1 AA | Accesibilidad | Parcial | 1,2 | Completo |
| TENANT-001 | Filtro tenant en queries | Presente | — | Mantenido |
| SCSS-FONT-001 | Outfit font family | Verificar | 3 | Completo |
| Textos traducibles (UI) | {% trans %} en Twig | 90% | 9 | 100% |
| Variables SCSS inyectables | No hardcoded, configurable desde UI | Ausente | 2,3 | Completo |
| Templates Twig limpias | Sin page.content ni bloques | Presente | 1 | Reforzado |
| Modales para CRUD | Acciones en modal/slide-panel | Presente | — | Mantenido |
| Entidades con Field UI + Views | Content Entity pattern | Presente | — | Mantenido |

---

## 8. Resumen de Archivos Afectados

### Archivos NUEVOS a crear

| Archivo | Fase | Descripción |
|---------|------|-------------|
| `jaraba_andalucia_ei/scss/_dashboard.scss` | 2 | SCSS migrado desde CSS |
| `jaraba_andalucia_ei/scss/main.scss` | 2 | Entry point SCSS |
| `jaraba_andalucia_ei/package.json` | 2 | Build scripts Dart Sass |
| `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.design_token_config.vertical_andalucia_ei.yml` | 3 | Design tokens |
| `ecosistema_jaraba_core/src/Service/AndaluciaEiFeatureGateService.php` | 4 | Feature gating |
| 18× `ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.freemium_vertical_limit.andalucia_ei_*.yml` | 4 | Límites freemium |
| `ecosistema_jaraba_core/src/Service/AndaluciaEiEmailSequenceService.php` | 5 | Secuencias email |
| 6× `jaraba_email/templates/mjml/andalucia_ei/*.mjml` | 5 | Templates MJML |
| `ecosistema_jaraba_core/src/Service/AndaluciaEiCrossVerticalBridgeService.php` | 6 | Puentes cross-vertical |
| `ecosistema_jaraba_core/src/Service/AndaluciaEiJourneyProgressionService.php` | 7 | Progresión proactiva IA |
| `ecosistema_jaraba_core/src/Service/AndaluciaEiHealthScoreService.php` | 8 | Health scores y KPIs |
| `ecosistema_jaraba_core/src/Service/AndaluciaEiExperimentService.php` | 11 | A/B testing |

### Archivos EXISTENTES a modificar

| Archivo | Fase | Cambios |
|---------|------|---------|
| `page--andalucia-ei.html.twig` (theme) | 1 | Reescribir: añadir Copilot FAB, variables, role="main" |
| `ecosistema_jaraba_theme.theme` | 1 | Añadir hook_preprocess_page__andalucia_ei(), ampliar body classes y suggestions |
| `andalucia-ei-dashboard.html.twig` | 2,6 | Corregir emoji, añadir sección bridges |
| `css/andalucia-ei.css` | 2 | Regenerar desde SCSS compilado |
| `jaraba_andalucia_ei.module` | 10 | Añadir upgrade triggers, CRM sync |
| `ecosistema_jaraba_core.services.yml` | 4,5,6,7,8,11 | Registrar 6 nuevos servicios |
| `AndaluciaEiJourneyDefinition.php` | 9 | Migrar a static methods + TranslatableMarkup |
| `TemplateLoaderService.php` | 5 | Registrar 6 templates andalucia_ei |
| `ecosistema_jaraba_core.install` | 4 | Update hook para tabla andalucia_ei_feature_usage |

### Estimación de esfuerzo

| Fase | Complejidad | Archivos | Estimación |
|------|-------------|----------|------------|
| 1 | Media | 3 | ~2h |
| 2 | Media-Alta | 4 | ~3h |
| 3 | Baja | 1 | ~30min |
| 4 | Alta | 20 | ~4h |
| 5 | Alta | 8 | ~4h |
| 6 | Media | 3 | ~2h |
| 7 | Media-Alta | 2 | ~3h |
| 8 | Media | 2 | ~2h |
| 9 | Baja | 1 | ~1h |
| 10 | Media | 2 | ~2h |
| 11 | Media | 2 | ~2h |
| 12 | Alta | 5+ | ~4h |
| **TOTAL** | | **~53** | **~29.5h** |

---

*Documento generado como parte de la auditoría de clase mundial del SaaS JarabaImpactPlatform.*
*Sigue las directrices de documentación del proyecto (plantilla_implementacion.md).*
