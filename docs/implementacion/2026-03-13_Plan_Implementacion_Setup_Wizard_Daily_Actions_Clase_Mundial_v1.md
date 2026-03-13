# Plan de Implementación — Patrón Setup Wizard + Daily Actions — Clase Mundial SaaS

> **Versión:** 1.0.0 | **Fecha:** 2026-03-13 | **Sprint:** 19-20 | **Autor:** Claude Code (Opus 4.6)
>
> **Módulo transversal:** `ecosistema_jaraba_core` + `ecosistema_jaraba_theme`
>
> **Módulo vertical (primer caso):** `jaraba_andalucia_ei` — Coordinador Dashboard
>
> **Regla nueva:** SETUP-WIZARD-DAILY-001 (P1)
>
> **Filosofía:** "Sin Humo" — componente transversal reutilizable, no implementación ad-hoc por vertical

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Análisis del Problema](#2-análisis-del-problema)
3. [Arquitectura del Patrón Transversal](#3-arquitectura-del-patrón-transversal)
4. [Sprint 19 — Infraestructura Transversal (ecosistema_jaraba_core + theme)](#4-sprint-19--infraestructura-transversal)
   - 4.1 [SetupWizardStepInterface](#41-setupwizardstepinterface)
   - 4.2 [SetupWizardRegistry (tagged services)](#42-setupwizardregistry-tagged-services)
   - 4.3 [SetupWizardCompilerPass](#43-setupwizardcompilerpass)
   - 4.4 [Parcial Twig _setup-wizard.html.twig](#44-parcial-twig-_setup-wizardhtmltwig)
   - 4.5 [Parcial Twig _daily-actions.html.twig](#45-parcial-twig-_daily-actionshtmltwig)
   - 4.6 [SCSS _setup-wizard.scss](#46-scss-_setup-wizardscss)
   - 4.7 [JavaScript setup-wizard.js](#47-javascript-setup-wizardjs)
   - 4.8 [Library Registration](#48-library-registration)
   - 4.9 [Preprocess Integration](#49-preprocess-integration)
5. [Sprint 20 — Caso de Uso: Coordinador Andalucía +ei](#5-sprint-20--caso-de-uso-coordinador-andalucía-ei)
   - 5.1 [CoordinadorSetupWizardStep (4 pasos)](#51-coordinadorsetupwizardstep-4-pasos)
   - 5.2 [Template coordinador-dashboard.html.twig (modificaciones)](#52-template-coordinador-dashboardhtmltwig-modificaciones)
   - 5.3 [CoordinadorDashboardController (variables nuevas)](#53-coordinadordashboardcontroller-variables-nuevas)
   - 5.4 [Slide-Panel para Plan Formativo](#54-slide-panel-para-plan-formativo)
   - 5.5 [Reestructuración de Daily Actions](#55-reestructuración-de-daily-actions)
   - 5.6 [Preprocess page__andalucia_ei (drupalSettings)](#56-preprocess-page__andalucia_ei-drupalsettings)
6. [Mapa de Aplicación Transversal (10 Verticales + Perfiles)](#6-mapa-de-aplicación-transversal-10-verticales--perfiles)
7. [Tabla de Correspondencia con Especificaciones Técnicas](#7-tabla-de-correspondencia-con-especificaciones-técnicas)
8. [Tabla de Cumplimiento de Directrices](#8-tabla-de-cumplimiento-de-directrices)
9. [Plan de Testing](#9-plan-de-testing)
10. [RUNTIME-VERIFY-001 — Verificación Post-Implementación](#10-runtime-verify-001--verificación-post-implementación)
11. [Gestión de Riesgos](#11-gestión-de-riesgos)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### Problema

El Coordinador Hub de Andalucía +ei mezcla en las "Acciones Rápidas" del header dos tipos de acciones fundamentalmente distintos:

- **Configuración del programa** (se hace una vez al inicio): crear Plan Formativo, crear Acciones Formativas, programar Sesiones
- **Operación diaria** (se repite continuamente): gestionar solicitudes, registrar asistencia, exportar STO, gestionar leads

Esta mezcla genera confusión cognitiva: un coordinador que ya tiene todo configurado ve "Nueva Acción Formativa" cada día como si fuera una tarea pendiente. Un coordinador nuevo no sabe por dónde empezar porque las cards no indican secuencia.

### Solución

Implementar el patrón **SETUP-WIZARD-DAILY-001** como estándar transversal del SaaS:

1. **Setup Wizard** — Stepper horizontal con pasos secuenciales numerados. Cada paso tiene estado visual (completado/activo/pendiente). El wizard se auto-oculta cuando todos los pasos están completados, pero permanece accesible vía enlace discreto "Configuración del Programa". Se colapsa progresivamente (mostrar solo pasos incompletos) para no ocupar espacio innecesario.

2. **Daily Actions** — Cards de acción rápida enfocadas EXCLUSIVAMENTE en las tareas operativas recurrentes del día a día. Contextualizadas por rol, estado del programa y alertas activas.

### Impacto

- **10 verticales** × ~2-3 roles por vertical = **20-30 wizards potenciales** en el ecosistema
- Primer caso de uso: Coordinador Andalucía +ei (Sprint 20)
- Arquitectura extensible via tagged services (mismo patrón que `TenantSettingsRegistry`)
- Componente transversal en `ecosistema_jaraba_core` + `ecosistema_jaraba_theme`

### Estimación

| Sprint | Contenido | Horas estimadas |
|--------|-----------|-----------------|
| 19 | Infraestructura transversal (service, parciales, SCSS, JS) | 16-20h |
| 20 | Caso Andalucía +ei + slide-panel Plan Formativo + daily actions | 12-16h |
| **Total** | | **28-36h** |

---

## 2. Análisis del Problema

### 2.1 Estado Actual del Coordinador Dashboard

El header del `coordinador-dashboard.html.twig` contiene actualmente 5 action cards:

| Card | Tipo real | Route | Slide-panel |
|------|-----------|-------|-------------|
| **Nuevo participante** | Operación diaria | `entity.programa_participante_ei.add_form` | ✅ `data-slide-panel` |
| **Nueva Acción Formativa** | Setup (1 vez) | `jaraba_andalucia_ei.hub.accion_formativa.add` | ✅ `data-slide-panel="large"` |
| **Programar Sesión** | Mixto (setup + recurrente) | `jaraba_andalucia_ei.hub.sesion_programada.add` | ✅ `data-slide-panel="large"` |
| **Exportar STO** | Operación diaria | `jaraba_andalucia_ei.sto_export` | ❌ Navegación |
| **Leads** | Operación diaria | `jaraba_andalucia_ei.leads_guia` | ❌ Navegación |

**Problemas identificados:**

1. No hay guía secuencial: ¿qué debe hacer primero el coordinador?
2. No hay feedback de completitud: ¿ya tengo suficientes acciones formativas?
3. "Nueva Acción Formativa" es una tarea de setup que se muestra como acción diaria
4. Falta el paso 0 más importante: **crear el Plan Formativo** (actualmente solo accesible desde la pestaña "Planes" vía route admin `entity.plan_formativo_ei.add_form`, sin slide-panel)
5. "Programar Sesión" es dual: es setup cuando se configuran las primeras sesiones, y operación cuando se reprograman
6. No hay enlace directo a gestión de solicitudes, que es la tarea #1 del día a día

### 2.2 Secuencia Lógica Normativa PIIL CV 2025

Basándose en el modelo normativo PIIL y la estructura de entities del módulo:

```
FASE 0 — CONFIGURACIÓN DEL PROGRAMA (prerequisitos normativos)
════════════════════════════════════════════════════════════════

 ┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
 │  PASO 1           │     │  PASO 2           │     │  PASO 3           │     │  PASO 4           │
 │  Plan Formativo   │────▶│  Acciones         │────▶│  Programar        │────▶│  ¡Listo!          │
 │                   │     │  Formativas       │     │  Sesiones         │     │                   │
 │  PlanFormativoEi  │     │  AccionFormativaEi│     │  SesionProgramada │     │  Validación       │
 │  - Carril         │     │  - VoBo SAE       │     │  - Fecha/hora     │     │  normativa        │
 │  - Horas          │     │  - Horas          │     │  - Facilitador    │     │  - ≥50h formación │
 │  - Fechas         │     │  - Tipo           │     │  - Plazas         │     │  - ≥10h orient.   │
 └──────────────────┘     └──────────────────┘     └──────────────────┘     │  - VoBo pendiente │
                                                                             └──────────────────┘

FASE 1+ — OPERACIÓN DIARIA (recurrente)
════════════════════════════════════════

 ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐
 │ Solicitudes│  │ Asistencia │  │ Exportar   │  │ Alertas    │  │ Leads      │
 │ pendientes │  │ sesiones   │  │ STO        │  │ normativas │  │ captación  │
 └────────────┘  └────────────┘  └────────────┘  └────────────┘  └────────────┘
```

### 2.3 Datos Disponibles para el Wizard

El `CoordinadorDashboardController` ya inyecta `formacion_stats` con:

```php
[
  'total_acciones'          => int,  // ← Paso 2 completado si > 0
  'en_ejecucion'            => int,
  'vobo_pendiente'          => int,  // ← Paso 4 advertencia si > 0
  'sesiones_programadas'    => int,  // ← Paso 3 completado si > 0
  'planes_activos'          => int,  // ← Paso 1 completado si > 0
  'horas_formacion_previstas' => float,
]
```

Estos datos son suficientes para computar el estado del wizard sin queries adicionales. El Paso 4 (validación normativa) puede usar `AccionFormativaService::validarRequisitosPlan()` que ya existe.

### 2.4 Benchmark Clase Mundial

| SaaS | Patrón Setup | Patrón Daily |
|------|-------------|--------------|
| **Stripe Dashboard** | Stepper 4 pasos (Activate → Products → Prices → Go Live) con progress bar | Cards: View payments, Create invoice, View disputes |
| **HubSpot** | "Getting Started" widget con checklist colapsable, auto-dismiss al 100% | Quick Actions dropdown + command bar |
| **Salesforce** | Setup Assistant con pasos obligatorios/opcionales, persistent sidebar | Home page con "Today's Events", "Recent Records" |
| **Linear** | Onboarding checklist (Create team → Import → Integrate → Invite) | "My Issues", "Create Issue", "Quick Filters" |
| **Notion** | Template gallery + "Getting Started" page auto-generada | Recent pages, Quick links, Favorites |
| **Monday.com** | "Start from Template" wizard con preview visual | "My Week" dashboard cards |

**Patrón ganador:** Stepper horizontal con estados visuales (Stripe) + auto-collapse cuando completado (HubSpot) + persistencia del estado (Salesforce).

---

## 3. Arquitectura del Patrón Transversal

### 3.1 Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    ECOSISTEMA_JARABA_CORE                                │
│                                                                         │
│  ┌─────────────────────────────────┐  ┌──────────────────────────────┐ │
│  │ SetupWizardStepInterface        │  │ SetupWizardRegistry          │ │
│  │ ─────────────────────────────── │  │ ──────────────────────────── │ │
│  │ + getId(): string               │  │ - steps: SetupWizardStep[][] │ │
│  │ + getWizardId(): string         │  │ + addStep(step)              │ │
│  │ + getLabel(): TranslatableMarkup│  │ + getStepsForWizard(id)      │ │
│  │ + getDescription(): string      │  │ + getWizardStatus(id)        │ │
│  │ + getWeight(): int              │  │ + isWizardComplete(id)       │ │
│  │ + getIcon(): array              │  │ + getCompletionPercentage(id)│ │
│  │ + getRoute(): string            │  │                              │ │
│  │ + isComplete(): bool            │  │ (tagged service collector)   │ │
│  │ + getCompletionData(): array    │  └──────────────────────────────┘ │
│  │ + isOptional(): bool            │                                   │
│  └─────────────────────────────────┘  ┌──────────────────────────────┐ │
│                                       │ SetupWizardCompilerPass      │ │
│  ┌─────────────────────────────────┐  │ ──────────────────────────── │ │
│  │ Tag:                            │  │ Collects services tagged     │ │
│  │ ecosistema_jaraba_core          │  │ with setup_wizard_step       │ │
│  │   .setup_wizard_step            │  │ Injects into Registry        │ │
│  └─────────────────────────────────┘  └──────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
        │                    │                    │
        ▼                    ▼                    ▼
┌──────────────┐  ┌──────────────────┐  ┌──────────────────────────┐
│ jaraba_      │  │ jaraba_          │  │ jaraba_comercio_conecta  │
│ andalucia_ei │  │ candidate        │  │                          │
│              │  │                  │  │ ComercianteSetupStep     │
│ Coordinador  │  │ Candidato        │  │  (alta negocio, catálogo │
│ SetupStep    │  │ SetupStep        │  │   envíos, pagos)         │
│ (4 pasos)    │  │ (perfil, CV,     │  │                          │
│              │  │  preferencias)   │  │                          │
└──────────────┘  └──────────────────┘  └──────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│                    ECOSISTEMA_JARABA_THEME                               │
│                                                                         │
│  ┌──────────────────────────┐  ┌──────────────────────────┐            │
│  │ _setup-wizard.html.twig  │  │ _daily-actions.html.twig │            │
│  │ (parcial reutilizable)   │  │ (parcial reutilizable)   │            │
│  │ Stepper horizontal       │  │ Grid de action cards     │            │
│  │ + auto-collapse          │  │ + badges dinámicos       │            │
│  └──────────────────────────┘  └──────────────────────────┘            │
│                                                                         │
│  ┌──────────────────────────┐  ┌──────────────────────────┐            │
│  │ scss/components/         │  │ js/setup-wizard.js       │            │
│  │   _setup-wizard.scss     │  │ Drupal.behaviors.        │            │
│  │                          │  │   setupWizard            │            │
│  └──────────────────────────┘  └──────────────────────────┘            │
│                                                                         │
│  ┌──────────────────────────┐                                          │
│  │ Library:                 │                                          │
│  │   setup-wizard           │                                          │
│  │   (CSS + JS)             │                                          │
│  └──────────────────────────┘                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Flujo de Datos

```
1. Controller llama SetupWizardRegistry::getStepsForWizard('coordinador_ei')
2. Registry itera los tagged services filtrando por wizardId
3. Cada step ejecuta isComplete() contra los datos del tenant
4. Registry devuelve array ordenado por weight con estado por paso
5. Controller pasa $variables['setup_wizard'] al template
6. Template renderiza parcial _setup-wizard.html.twig con { wizard: setup_wizard } only
7. JS behavior gestiona interacciones: collapse, expand, slide-panel trigger, localStorage dismiss
8. Al completar un paso (save en slide-panel), JS recarga la sección wizard vía fetch API
```

### 3.3 Principios de Diseño

1. **Inversión de control:** Los verticales definen sus pasos, el core los orquesta. Añadir un wizard nuevo requiere SOLO registrar tagged services — cero cambios en core.

2. **Datos desde el controller, no desde el parcial:** El parcial Twig recibe datos pre-computados. NUNCA consultas en el template (ZERO-REGION-001).

3. **Auto-dismiss inteligente:** Cuando `isWizardComplete() === TRUE`, el wizard se colapsa a una barra mínima ("✓ Programa configurado") en vez de desaparecer completamente. El coordinador puede re-abrirlo para verificar o modificar la configuración.

4. **Persistencia dual:** El estado de completitud viene del backend (datos reales de entities). El estado de UI (colapsado/expandido, dismiss temporal) se persiste en localStorage del navegador.

5. **Mobile-first:** El stepper horizontal se convierte en stepper vertical en breakpoints < 768px. Los steps se apilan como cards compactas.

---

## 4. Sprint 19 — Infraestructura Transversal

> **Estado:** PLANIFICADO | **Estimación:** 16-20h | **Archivos nuevos:** 8 | **Archivos modificados:** 5

### 4.1 SetupWizardStepInterface

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/SetupWizardStepInterface.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for a single step in a Setup Wizard.
 *
 * Each step represents a configuration task that must be completed
 * before the system is fully operational. Steps are collected via
 * tagged services (tag: ecosistema_jaraba_core.setup_wizard_step)
 * and organized by wizardId.
 *
 * @see \Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry
 */
interface SetupWizardStepInterface {

  /**
   * Returns the unique step ID within its wizard.
   *
   * Convention: '{wizard_id}.{step_name}' (e.g., 'coordinador_ei.plan_formativo').
   */
  public function getId(): string;

  /**
   * Returns the wizard this step belongs to.
   *
   * Multiple steps with the same wizardId are grouped together.
   * Convention: module_name + role (e.g., 'coordinador_ei', 'candidato_empleo').
   */
  public function getWizardId(): string;

  /**
   * Human-readable label for the step.
   *
   * MUST use TranslatableMarkup for i18n ({% trans %} in templates).
   */
  public function getLabel(): TranslatableMarkup;

  /**
   * Short description explaining what this step achieves.
   *
   * Displayed below the step title in the wizard UI.
   * MUST use TranslatableMarkup.
   */
  public function getDescription(): TranslatableMarkup;

  /**
   * Ordering weight. Lower values appear first.
   *
   * Steps within a wizard are sorted ascending by weight.
   * Convention: use multiples of 10 (10, 20, 30...) to allow insertion.
   */
  public function getWeight(): int;

  /**
   * Icon configuration for jaraba_icon() Twig function.
   *
   * Returns: ['category' => string, 'name' => string, 'variant' => 'duotone']
   * ICON-DUOTONE-001: default variant MUST be 'duotone'.
   * ICON-COLOR-001: color MUST be from Jaraba palette.
   */
  public function getIcon(): array;

  /**
   * Route name for the action button (creates/edits the step's entity).
   *
   * ROUTE-LANGPREFIX-001: MUST be a Drupal route name, never a hardcoded path.
   * The wizard UI will generate the URL via Url::fromRoute().
   * If the step opens a slide-panel, this route should support AJAX rendering.
   */
  public function getRoute(): string;

  /**
   * Route parameters for the action button.
   *
   * Returns: ['param_name' => value] or empty array.
   */
  public function getRouteParameters(): array;

  /**
   * Whether this step should open in a slide-panel.
   *
   * If TRUE, the wizard button will include data-slide-panel attributes.
   * SLIDE-PANEL-RENDER-001: the target controller MUST support renderPlain().
   */
  public function useSlidePanel(): bool;

  /**
   * Slide-panel size when useSlidePanel() returns TRUE.
   *
   * Valid values: 'small', 'medium', 'large', 'full'.
   */
  public function getSlidePanelSize(): string;

  /**
   * Determines whether this step is complete for the given tenant.
   *
   * This method is called on every dashboard load — it MUST be fast.
   * Prefer using pre-aggregated stats over individual entity queries.
   *
   * TENANT-001: MUST filter by tenant_id internally.
   *
   * @param int $tenantId
   *   The tenant ID to check completeness for.
   *
   * @return bool
   *   TRUE if the step is complete, FALSE otherwise.
   */
  public function isComplete(int $tenantId): bool;

  /**
   * Returns completion data for display in the wizard UI.
   *
   * Example: ['count' => 3, 'label' => '3 acciones creadas', 'progress' => 60]
   * - 'count': numeric value for badges
   * - 'label': human-readable status (TranslatableMarkup)
   * - 'progress': 0-100 percentage (optional, for partial completion)
   * - 'warning': string message if there's a blocker (e.g., VoBo pending)
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array
   *   Associative array with completion details.
   */
  public function getCompletionData(int $tenantId): array;

  /**
   * Whether this step is optional.
   *
   * Optional steps don't block the wizard from showing "complete" status
   * but are displayed with a different visual treatment (dashed border).
   */
  public function isOptional(): bool;

}
```

**Decisiones de diseño:**

- `isComplete()` recibe `$tenantId` como parámetro explícito en vez de depender del `TenantContextService`. Esto permite: (a) testear con tenant IDs arbitrarios, (b) el controller resuelve el tenant UNA vez y lo pasa a todos los steps.
- `getCompletionData()` devuelve un array flexible con `count`, `label`, `progress` y `warning` opcionales. Esto permite desde un simple checkmark hasta barras de progreso parcial.
- `useSlidePanel()` y `getSlidePanelSize()` se declaran en la interfaz porque el parcial Twig necesita saber cómo renderizar el botón de acción del step (con o sin `data-slide-panel`).

### 4.2 SetupWizardRegistry (tagged services)

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/SetupWizardRegistry.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

/**
 * Collects and organizes SetupWizardStep tagged services.
 *
 * Pattern: identical to TenantSettingsRegistry (TENANT-SETTINGS-HUB-001).
 * Steps are collected via SetupWizardCompilerPass and grouped by wizardId.
 *
 * Usage in controllers:
 *   $steps = $this->wizardRegistry->getStepsForWizard('coordinador_ei', $tenantId);
 *
 * Usage in Twig (via preprocess):
 *   {% include '.../_setup-wizard.html.twig' with { wizard: setup_wizard } only %}
 */
class SetupWizardRegistry {

  /**
   * Collected wizard steps, grouped by wizard ID.
   *
   * Structure: ['wizard_id' => [SetupWizardStepInterface, ...]]
   *
   * @var \Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface[][]
   */
  protected array $steps = [];

  /**
   * Registers a step into the registry.
   *
   * Called by SetupWizardCompilerPass for each tagged service.
   */
  public function addStep(SetupWizardStepInterface $step): void {
    $this->steps[$step->getWizardId()][] = $step;
  }

  /**
   * Returns all steps for a wizard, sorted by weight, with completion data.
   *
   * @param string $wizardId
   *   The wizard identifier (e.g., 'coordinador_ei').
   * @param int $tenantId
   *   The tenant ID for computing isComplete() and getCompletionData().
   *
   * @return array
   *   Structured array ready for Twig rendering:
   *   [
   *     'wizard_id' => string,
   *     'is_complete' => bool,
   *     'completion_percentage' => int (0-100),
   *     'steps' => [
   *       [
   *         'id' => string,
   *         'label' => TranslatableMarkup,
   *         'description' => TranslatableMarkup,
   *         'icon' => ['category' => ..., 'name' => ..., 'variant' => ...],
   *         'route' => string,
   *         'route_params' => array,
   *         'use_slide_panel' => bool,
   *         'slide_panel_size' => string,
   *         'is_complete' => bool,
   *         'is_optional' => bool,
   *         'is_active' => bool,  // first incomplete non-optional step
   *         'completion_data' => array,
   *         'step_number' => int, // 1-based
   *       ],
   *       ...
   *     ],
   *   ]
   */
  public function getStepsForWizard(string $wizardId, int $tenantId): array {
    $wizardSteps = $this->steps[$wizardId] ?? [];

    // Sort by weight ascending.
    usort($wizardSteps, fn($a, $b) => $a->getWeight() <=> $b->getWeight());

    $steps = [];
    $completedCount = 0;
    $requiredCount = 0;
    $firstIncomplete = TRUE;

    foreach ($wizardSteps as $index => $step) {
      $isComplete = $step->isComplete($tenantId);
      $isOptional = $step->isOptional();

      if (!$isOptional) {
        $requiredCount++;
        if ($isComplete) {
          $completedCount++;
        }
      }

      // The "active" step is the first non-optional incomplete step.
      $isActive = FALSE;
      if (!$isComplete && !$isOptional && $firstIncomplete) {
        $isActive = TRUE;
        $firstIncomplete = FALSE;
      }

      $steps[] = [
        'id' => $step->getId(),
        'label' => $step->getLabel(),
        'description' => $step->getDescription(),
        'icon' => $step->getIcon(),
        'route' => $step->getRoute(),
        'route_params' => $step->getRouteParameters(),
        'use_slide_panel' => $step->useSlidePanel(),
        'slide_panel_size' => $step->getSlidePanelSize(),
        'is_complete' => $isComplete,
        'is_optional' => $isOptional,
        'is_active' => $isActive,
        'completion_data' => $step->getCompletionData($tenantId),
        'step_number' => $index + 1,
      ];
    }

    $percentage = $requiredCount > 0
      ? (int) round(($completedCount / $requiredCount) * 100)
      : 100;

    return [
      'wizard_id' => $wizardId,
      'is_complete' => $percentage === 100,
      'completion_percentage' => $percentage,
      'steps' => $steps,
    ];
  }

  /**
   * Quick check if a wizard has any registered steps.
   */
  public function hasWizard(string $wizardId): bool {
    return !empty($this->steps[$wizardId]);
  }

}
```

**Decisiones de diseño:**

- La salida de `getStepsForWizard()` es un array plano listo para Twig (no objetos). Esto cumple ZERO-REGION-002 (nunca pasar entity objects como non-# keys) y simplifica el template.
- `is_active` se computa automáticamente: es el primer paso no-opcional incompleto. Esto permite al CSS/JS resaltar el "siguiente paso a dar".
- `completion_percentage` se calcula solo sobre pasos no-opcionales.

### 4.3 SetupWizardCompilerPass

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/DependencyInjection/SetupWizardCompilerPass.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass that collects setup wizard step tagged services.
 *
 * Pattern: identical to TenantSettingsSectionPass.
 * Collects services tagged 'ecosistema_jaraba_core.setup_wizard_step'
 * and injects them into SetupWizardRegistry via addStep().
 */
class SetupWizardCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    if (!$container->hasDefinition('ecosistema_jaraba_core.setup_wizard_registry')) {
      return;
    }

    $registry = $container->getDefinition('ecosistema_jaraba_core.setup_wizard_registry');

    foreach ($container->findTaggedServiceIds('ecosistema_jaraba_core.setup_wizard_step') as $id => $tags) {
      $registry->addMethodCall('addStep', [new Reference($id)]);
    }
  }

}
```

**Registro del CompilerPass** en `EcosistemaJarabaCoreServiceProvider.php`:

```php
// En el método register():
$container->addCompilerPass(new SetupWizardCompilerPass());
```

**Registro del servicio en `ecosistema_jaraba_core.services.yml`:**

```yaml
  ecosistema_jaraba_core.setup_wizard_registry:
    class: Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry
```

### 4.4 Parcial Twig _setup-wizard.html.twig

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_setup-wizard.html.twig`

Este parcial se incluye en los page templates con:

```twig
{% include '@ecosistema_jaraba_theme/partials/_setup-wizard.html.twig' with {
  wizard: setup_wizard,
  wizard_title: 'Configuración del Programa'|t,
  wizard_subtitle: 'Completa estos pasos para poner en marcha el programa'|t,
  collapsed_label: 'Programa configurado'|t,
} only %}
```

**Estructura del parcial:**

```twig
{#
/**
 * @file
 * Parcial reutilizable: Setup Wizard — Patrón SETUP-WIZARD-DAILY-001.
 *
 * Stepper horizontal responsive con estados visuales por paso.
 * Auto-collapse cuando wizard.is_complete = TRUE.
 * Mobile-first: stepper vertical en < 768px.
 *
 * Variables:
 *   - wizard: Array from SetupWizardRegistry::getStepsForWizard()
 *     - wizard_id: string
 *     - is_complete: bool
 *     - completion_percentage: int (0-100)
 *     - steps: array of step objects
 *   - wizard_title: string (TranslatableMarkup)
 *   - wizard_subtitle: string (TranslatableMarkup)
 *   - collapsed_label: string (TranslatableMarkup)
 *
 * Directrices aplicadas:
 *   - SETUP-WIZARD-DAILY-001: Patrón transversal Setup Wizard
 *   - CSS-VAR-ALL-COLORS-001: Todos los colores via var(--ej-*)
 *   - ICON-CONVENTION-001, ICON-DUOTONE-001, ICON-COLOR-001
 *   - TWIG-INCLUDE-ONLY-001: Se usa con `only`
 *   - ORTOGRAFIA-TRANS-001: Textos {% trans %} con tildes correctas
 *   - ROUTE-LANGPREFIX-001: URLs via path() / url()
 *   - WCAG 2.1 AA: aria-labels, role, focus-visible
 */
#}

{% if wizard and wizard.steps|length > 0 %}
<section class="setup-wizard"
         data-setup-wizard="{{ wizard.wizard_id }}"
         data-wizard-complete="{{ wizard.is_complete ? 'true' : 'false' }}"
         aria-label="{{ wizard_title }}">

  {# === COLLAPSED STATE (visible when wizard is complete) === #}
  <div class="setup-wizard__collapsed {% if not wizard.is_complete %}setup-wizard__collapsed--hidden{% endif %}"
       data-wizard-collapsed>
    <div class="setup-wizard__collapsed-content">
      <span class="setup-wizard__collapsed-icon" aria-hidden="true">
        {{ jaraba_icon('compliance', 'check-circle', { variant: 'duotone', color: 'verde-innovacion', size: '20px' }) }}
      </span>
      <span class="setup-wizard__collapsed-label">{{ collapsed_label }}</span>
      <span class="setup-wizard__collapsed-percentage">{{ wizard.completion_percentage }}%</span>
    </div>
    <button class="setup-wizard__expand-btn"
            type="button"
            aria-expanded="false"
            aria-controls="wizard-panel-{{ wizard.wizard_id }}"
            data-wizard-toggle>
      {% trans %}Revisar configuración{% endtrans %}
      <span aria-hidden="true">&darr;</span>
    </button>
  </div>

  {# === EXPANDED STATE (visible when wizard is NOT complete or user expands) === #}
  <div class="setup-wizard__panel {% if wizard.is_complete %}setup-wizard__panel--hidden{% endif %}"
       id="wizard-panel-{{ wizard.wizard_id }}"
       data-wizard-panel>

    {# --- Header with title, subtitle, and progress --- #}
    <div class="setup-wizard__header">
      <div class="setup-wizard__header-text">
        <h2 class="setup-wizard__title">{{ wizard_title }}</h2>
        {% if wizard_subtitle %}
          <p class="setup-wizard__subtitle">{{ wizard_subtitle }}</p>
        {% endif %}
      </div>
      <div class="setup-wizard__progress" role="progressbar"
           aria-valuenow="{{ wizard.completion_percentage }}"
           aria-valuemin="0" aria-valuemax="100"
           aria-label="{% trans %}Progreso de configuración{% endtrans %}">
        <div class="setup-wizard__progress-track">
          <div class="setup-wizard__progress-fill"
               style="width: {{ wizard.completion_percentage }}%;">
          </div>
        </div>
        <span class="setup-wizard__progress-text">
          {{ wizard.completion_percentage }}% {% trans %}completado{% endtrans %}
        </span>
      </div>
      {% if wizard.is_complete %}
        <button class="setup-wizard__collapse-btn"
                type="button"
                aria-expanded="true"
                aria-controls="wizard-panel-{{ wizard.wizard_id }}"
                data-wizard-toggle>
          {{ jaraba_icon('ui', 'chevron-up', { variant: 'outline', color: 'azul-corporativo', size: '16px' }) }}
          <span class="visually-hidden">{% trans %}Colapsar{% endtrans %}</span>
        </button>
      {% endif %}
    </div>

    {# --- Stepper with steps --- #}
    <div class="setup-wizard__stepper" role="list">
      {% for step in wizard.steps %}
        <div class="setup-wizard__step
                    {% if step.is_complete %}setup-wizard__step--complete{% endif %}
                    {% if step.is_active %}setup-wizard__step--active{% endif %}
                    {% if step.is_optional %}setup-wizard__step--optional{% endif %}"
             role="listitem"
             data-step-id="{{ step.id }}">

          {# --- Step number indicator --- #}
          <div class="setup-wizard__step-indicator">
            {% if step.is_complete %}
              <span class="setup-wizard__step-check" aria-hidden="true">
                {{ jaraba_icon('compliance', 'check-circle', { variant: 'duotone', color: 'verde-innovacion', size: '24px' }) }}
              </span>
            {% else %}
              <span class="setup-wizard__step-number">{{ step.step_number }}</span>
            {% endif %}
          </div>

          {# --- Connector line (between steps, not on last) --- #}
          {% if not loop.last %}
            <div class="setup-wizard__step-connector
                        {% if step.is_complete %}setup-wizard__step-connector--complete{% endif %}"
                 aria-hidden="true">
            </div>
          {% endif %}

          {# --- Step content card --- #}
          <div class="setup-wizard__step-card">
            <div class="setup-wizard__step-icon" aria-hidden="true">
              {{ jaraba_icon(step.icon.category, step.icon.name, {
                variant: step.icon.variant|default('duotone'),
                color: step.is_complete ? 'verde-innovacion' : (step.is_active ? 'naranja-impulso' : 'azul-corporativo'),
                size: '32px'
              }) }}
            </div>
            <div class="setup-wizard__step-text">
              <h3 class="setup-wizard__step-label">{{ step.label }}</h3>
              <p class="setup-wizard__step-desc">{{ step.description }}</p>

              {# --- Completion data (badges, warnings) --- #}
              {% if step.completion_data.label is defined %}
                <span class="setup-wizard__step-status
                             {% if step.is_complete %}setup-wizard__step-status--success{% endif %}
                             {% if step.completion_data.warning is defined %}setup-wizard__step-status--warning{% endif %}">
                  {{ step.completion_data.label }}
                </span>
              {% endif %}
              {% if step.completion_data.warning is defined and not step.is_complete %}
                <span class="setup-wizard__step-warning">
                  {{ jaraba_icon('compliance', 'alert-triangle', { variant: 'outline', color: 'naranja-impulso', size: '14px' }) }}
                  {{ step.completion_data.warning }}
                </span>
              {% endif %}
            </div>

            {# --- Action button --- #}
            {% if not step.is_complete or step.is_active %}
              <a href="{{ path(step.route, step.route_params) }}"
                 class="setup-wizard__step-action"
                 {% if step.use_slide_panel %}
                   data-slide-panel="{{ step.slide_panel_size }}"
                   data-slide-panel-title="{{ step.label }}"
                 {% endif %}>
                {% if step.is_complete %}
                  {% trans %}Editar{% endtrans %}
                {% else %}
                  {% trans %}Configurar{% endtrans %}
                {% endif %}
                <span aria-hidden="true">&rarr;</span>
              </a>
            {% elseif step.is_complete %}
              <a href="{{ path(step.route, step.route_params) }}"
                 class="setup-wizard__step-action setup-wizard__step-action--edit"
                 {% if step.use_slide_panel %}
                   data-slide-panel="{{ step.slide_panel_size }}"
                   data-slide-panel-title="{{ step.label }}"
                 {% endif %}>
                {{ jaraba_icon('actions', 'edit', { variant: 'outline', color: 'azul-corporativo', size: '14px' }) }}
                <span class="visually-hidden">{% trans %}Editar{% endtrans %} {{ step.label }}</span>
              </a>
            {% endif %}
          </div>
        </div>
      {% endfor %}
    </div>

  </div>
</section>
{% endif %}
```

**Decisiones clave del parcial:**

- Se recibe con `only` para cumplir TWIG-INCLUDE-ONLY-001 — no se filtran variables del padre.
- Todos los textos usan `{% trans %}` (ORTOGRAFIA-TRANS-001).
- El color del icono cambia según estado: verde (completo), naranja (activo), azul (pendiente) — coherente con la paleta Jaraba.
- Los links usan `path()` (ROUTE-LANGPREFIX-001).
- `data-slide-panel` se incluye condicionalmente según `step.use_slide_panel`.
- WCAG: `role="list"` / `role="listitem"` en el stepper, `role="progressbar"` con `aria-valuenow`, `aria-expanded` en botones toggle, `visually-hidden` para screen readers.
- La sección completa se renderiza condicionalmente: si no hay wizard o no hay steps, no se genera HTML.

### 4.5 Parcial Twig _daily-actions.html.twig

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_daily-actions.html.twig`

```twig
{#
/**
 * @file
 * Parcial reutilizable: Daily Actions — Patrón SETUP-WIZARD-DAILY-001.
 *
 * Grid responsive de action cards para operaciones diarias recurrentes.
 * Cada card tiene: icono, título, descripción, badge opcional, enlace.
 *
 * Variables:
 *   - daily_actions: array of action objects
 *     - id: string
 *     - label: TranslatableMarkup
 *     - description: TranslatableMarkup
 *     - icon: ['category' => ..., 'name' => ..., 'variant' => ...]
 *     - color: string (azul-corporativo | naranja-impulso | verde-innovacion)
 *     - route: string (Drupal route name)
 *     - route_params: array
 *     - use_slide_panel: bool
 *     - slide_panel_size: string
 *     - badge: int|null (counter badge, e.g., pending items)
 *     - badge_type: string (info | warning | critical)
 *     - is_primary: bool (primary action gets larger card)
 *   - actions_title: string (optional section title)
 */
#}

{% if daily_actions is not empty %}
<div class="daily-actions" aria-label="{{ actions_title|default('Acciones rápidas'|t) }}">
  {% if actions_title %}
    <h2 class="daily-actions__title">{{ actions_title }}</h2>
  {% endif %}

  <div class="daily-actions__grid">
    {% for action in daily_actions %}
      <a href="{{ path(action.route, action.route_params|default({})) }}"
         class="daily-actions__card
                {% if action.is_primary|default(false) %}daily-actions__card--primary{% endif %}
                daily-actions__card--{{ action.color|default('azul-corporativo') }}"
         {% if action.use_slide_panel|default(false) %}
           data-slide-panel="{{ action.slide_panel_size|default('medium') }}"
           data-slide-panel-title="{{ action.label }}"
         {% endif %}>

        <span class="daily-actions__icon daily-actions__icon--{{ action.color|default('azul-corporativo') }}">
          {{ jaraba_icon(
            action.icon.category,
            action.icon.name,
            { variant: action.icon.variant|default('duotone'), size: '24px' }
          ) }}
        </span>

        <span class="daily-actions__content">
          <span class="daily-actions__label">{{ action.label }}</span>
          <span class="daily-actions__desc">{{ action.description }}</span>
        </span>

        {% if action.badge is defined and action.badge is not null and action.badge > 0 %}
          <span class="daily-actions__badge daily-actions__badge--{{ action.badge_type|default('info') }}"
                aria-label="{{ action.badge }} {% trans %}pendientes{% endtrans %}">
            {{ action.badge }}
          </span>
        {% endif %}

        <span class="daily-actions__arrow" aria-hidden="true">&rarr;</span>
      </a>
    {% endfor %}
  </div>
</div>
{% endif %}
```

**Diferencias clave con las action cards actuales:**

- El parcial es genérico — no tiene HTML hardcoded para cada card. Recibe un array de acciones.
- Incluye `badge` con contador y tipo (info/warning/critical) para resaltar items pendientes.
- `is_primary` permite que la primera card (normalmente "Gestionar solicitudes") sea más grande.
- Los colores se aplican por CSS class, no por inline style.
- El `aria-label` del badge incluye la palabra "pendientes" traducible.

### 4.6 SCSS _setup-wizard.scss

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/scss/components/_setup-wizard.scss`

Este SCSS sigue las directrices CSS-VAR-ALL-COLORS-001, SCSS-001, SCSS-COLORMIX-001.

```scss
// =============================================================================
// Setup Wizard + Daily Actions — SETUP-WIZARD-DAILY-001
//
// Directrices:
//   CSS-VAR-ALL-COLORS-001 — All colors via var(--ej-*, fallback)
//   SCSS-001 — @use isolated scope
//   SCSS-COLORMIX-001 — color-mix() for runtime alpha, not rgba()
//   WCAG 2.1 AA — Focus visible, contrast ratios
// =============================================================================

@use '../variables' as *;

// -- Setup Wizard (stepper horizontal) ----------------------------------------

.setup-wizard {
  margin-bottom: var(--ej-spacing-lg, 1.5rem);
}

// Collapsed state — minimal bar when wizard is complete
.setup-wizard__collapsed {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--ej-spacing-sm, 0.5rem) var(--ej-spacing-md, 1rem);
  background: color-mix(in srgb, var(--ej-color-verde-innovacion, #00A9A5) 6%, var(--ej-color-surface, #ffffff));
  border: 1px solid color-mix(in srgb, var(--ej-color-verde-innovacion, #00A9A5) 20%, transparent);
  border-radius: var(--ej-radius-md, 8px);

  &--hidden { display: none; }
}

.setup-wizard__collapsed-content {
  display: flex;
  align-items: center;
  gap: var(--ej-spacing-xs, 0.375rem);
}

.setup-wizard__collapsed-label {
  font-weight: 600;
  color: var(--ej-color-headings, #1A1A2E);
  font-size: 0.875rem;
}

.setup-wizard__collapsed-percentage {
  font-size: 0.75rem;
  color: var(--ej-color-muted, #64748B);
}

.setup-wizard__expand-btn,
.setup-wizard__collapse-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--ej-color-azul-corporativo, #233D63);
  display: flex;
  align-items: center;
  gap: var(--ej-spacing-xs, 0.375rem);
  padding: var(--ej-spacing-xs, 0.375rem) var(--ej-spacing-sm, 0.5rem);
  border-radius: var(--ej-radius-sm, 4px);
  transition: background 0.15s ease;

  &:hover {
    background: color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 8%, transparent);
  }

  &:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 40%, transparent);
    outline-offset: 2px;
  }
}

// Expanded panel
.setup-wizard__panel {
  background: var(--ej-color-surface, #ffffff);
  border: 1px solid var(--ej-color-border-light, #E5E7EB);
  border-radius: var(--ej-radius-lg, 12px);
  padding: var(--ej-spacing-lg, 1.5rem);
  box-shadow: 0 1px 3px color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 6%, transparent);

  &--hidden { display: none; }
}

.setup-wizard__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--ej-spacing-md, 1rem);
  margin-bottom: var(--ej-spacing-lg, 1.5rem);
  flex-wrap: wrap;
}

.setup-wizard__title {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--ej-color-headings, #1A1A2E);
  margin: 0;
}

.setup-wizard__subtitle {
  font-size: 0.8125rem;
  color: var(--ej-color-muted, #64748B);
  margin: var(--ej-spacing-xs, 0.375rem) 0 0;
}

// Progress bar
.setup-wizard__progress {
  display: flex;
  align-items: center;
  gap: var(--ej-spacing-sm, 0.5rem);
  min-width: 160px;
}

.setup-wizard__progress-track {
  flex: 1;
  height: 6px;
  background: var(--ej-color-border-light, #E5E7EB);
  border-radius: 3px;
  overflow: hidden;
}

.setup-wizard__progress-fill {
  height: 100%;
  background: linear-gradient(
    90deg,
    var(--ej-color-verde-innovacion, #00A9A5),
    color-mix(in srgb, var(--ej-color-verde-innovacion, #00A9A5) 80%, var(--ej-color-azul-corporativo, #233D63))
  );
  border-radius: 3px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.setup-wizard__progress-text {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ej-color-muted, #64748B);
  white-space: nowrap;
}

// Stepper
.setup-wizard__stepper {
  display: flex;
  gap: 0;
  position: relative;
}

.setup-wizard__step {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  min-width: 0; // Allow flex shrink
}

// Step indicator (number or check)
.setup-wizard__step-indicator {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
  z-index: 1;
  transition: all 0.3s ease;

  .setup-wizard__step--active & {
    background: var(--ej-color-naranja-impulso, #FF8C42);
    color: var(--ej-color-surface, #ffffff);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 20%, transparent);
  }

  .setup-wizard__step--complete & {
    background: color-mix(in srgb, var(--ej-color-verde-innovacion, #00A9A5) 10%, var(--ej-color-surface, #ffffff));
  }

  .setup-wizard__step:not(.setup-wizard__step--active):not(.setup-wizard__step--complete) & {
    background: var(--ej-color-border-light, #E5E7EB);
    color: var(--ej-color-muted, #64748B);
  }
}

.setup-wizard__step-number {
  line-height: 1;
}

// Connector line between steps
.setup-wizard__step-connector {
  position: absolute;
  top: 18px; // Center of indicator
  left: calc(50% + 18px); // After indicator
  right: calc(-50% + 18px); // Before next indicator
  height: 2px;
  background: var(--ej-color-border-light, #E5E7EB);
  z-index: 0;

  &--complete {
    background: var(--ej-color-verde-innovacion, #00A9A5);
  }
}

// Step card content
.setup-wizard__step-card {
  text-align: center;
  margin-top: var(--ej-spacing-sm, 0.5rem);
  padding: var(--ej-spacing-sm, 0.5rem);
  border-radius: var(--ej-radius-md, 8px);
  transition: background 0.15s ease;
  width: 100%;

  .setup-wizard__step--active & {
    background: color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 4%, var(--ej-color-surface, #ffffff));
  }
}

.setup-wizard__step-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--ej-color-headings, #1A1A2E);
  margin: var(--ej-spacing-xs, 0.375rem) 0 0;
}

.setup-wizard__step-desc {
  font-size: 0.75rem;
  color: var(--ej-color-muted, #64748B);
  margin: 2px 0 0;
  line-height: 1.4;
}

.setup-wizard__step-status {
  display: inline-block;
  font-size: 0.6875rem;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: 10px;
  margin-top: var(--ej-spacing-xs, 0.375rem);

  &--success {
    background: color-mix(in srgb, var(--ej-color-verde-innovacion, #00A9A5) 10%, var(--ej-color-surface, #ffffff));
    color: color-mix(in srgb, var(--ej-color-verde-innovacion, #00A9A5) 65%, black);
  }

  &--warning {
    background: color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 10%, var(--ej-color-surface, #ffffff));
    color: color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 65%, black);
  }
}

.setup-wizard__step-warning {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.6875rem;
  color: color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 65%, black);
  margin-top: 4px;
  justify-content: center;
}

.setup-wizard__step-action {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--ej-color-azul-corporativo, #233D63);
  text-decoration: none;
  padding: 4px 12px;
  border-radius: var(--ej-radius-sm, 4px);
  margin-top: var(--ej-spacing-xs, 0.375rem);
  transition: background 0.15s ease;

  &:hover {
    background: color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 8%, transparent);
  }

  &:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 40%, transparent);
    outline-offset: 2px;
  }

  .setup-wizard__step--active & {
    background: var(--ej-color-naranja-impulso, #FF8C42);
    color: var(--ej-color-surface, #ffffff);
    padding: 6px 16px;

    &:hover {
      background: color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 85%, black);
    }
  }

  &--edit {
    padding: 4px;
    opacity: 0.6;

    &:hover { opacity: 1; }
  }
}

// -- Daily Actions (quick action cards grid) ----------------------------------

.daily-actions {
  margin-bottom: var(--ej-spacing-lg, 1.5rem);
}

.daily-actions__title {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--ej-color-muted, #64748B);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin: 0 0 var(--ej-spacing-sm, 0.5rem);
}

.daily-actions__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: var(--ej-spacing-sm, 0.5rem);
}

.daily-actions__card {
  display: flex;
  align-items: center;
  gap: var(--ej-spacing-sm, 0.5rem);
  padding: var(--ej-spacing-sm, 0.5rem) var(--ej-spacing-md, 1rem);
  background: var(--ej-color-surface, #ffffff);
  border: 1px solid var(--ej-color-border-light, #E5E7EB);
  border-radius: var(--ej-radius-md, 8px);
  text-decoration: none;
  color: inherit;
  transition: all 0.15s ease;
  position: relative;

  &:hover {
    border-color: color-mix(in srgb, var(--card-accent, var(--ej-color-azul-corporativo, #233D63)) 30%, transparent);
    box-shadow: 0 2px 8px color-mix(in srgb, var(--card-accent, var(--ej-color-azul-corporativo, #233D63)) 10%, transparent);
    transform: translateY(-1px);
  }

  &:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--card-accent, var(--ej-color-azul-corporativo, #233D63)) 40%, transparent);
    outline-offset: 2px;
  }

  &--primary {
    grid-column: span 2;
    background: linear-gradient(
      135deg,
      color-mix(in srgb, var(--card-accent) 5%, var(--ej-color-surface, #ffffff)),
      var(--ej-color-surface, #ffffff) 60%
    );
    border-left: 3px solid color-mix(in srgb, var(--card-accent) 50%, transparent);
  }

  &--azul-corporativo { --card-accent: var(--ej-color-azul-corporativo, #233D63); }
  &--naranja-impulso { --card-accent: var(--ej-color-naranja-impulso, #FF8C42); }
  &--verde-innovacion { --card-accent: var(--ej-color-verde-innovacion, #00A9A5); }
}

.daily-actions__icon {
  width: 36px;
  height: 36px;
  border-radius: var(--ej-radius-sm, 4px);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;

  &--azul-corporativo {
    background: color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 8%, var(--ej-color-surface, #ffffff));
  }
  &--naranja-impulso {
    background: color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 8%, var(--ej-color-surface, #ffffff));
  }
  &--verde-innovacion {
    background: color-mix(in srgb, var(--ej-color-verde-innovacion, #00A9A5) 8%, var(--ej-color-surface, #ffffff));
  }
}

.daily-actions__content {
  flex: 1;
  min-width: 0;
}

.daily-actions__label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--ej-color-headings, #1A1A2E);
}

.daily-actions__desc {
  display: block;
  font-size: 0.6875rem;
  color: var(--ej-color-muted, #64748B);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.daily-actions__badge {
  min-width: 20px;
  height: 20px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.6875rem;
  font-weight: 700;
  flex-shrink: 0;
  padding: 0 6px;

  &--info {
    background: color-mix(in srgb, var(--ej-color-azul-corporativo, #233D63) 12%, var(--ej-color-surface, #ffffff));
    color: var(--ej-color-azul-corporativo, #233D63);
  }

  &--warning {
    background: color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 15%, var(--ej-color-surface, #ffffff));
    color: color-mix(in srgb, var(--ej-color-naranja-impulso, #FF8C42) 65%, black);
  }

  &--critical {
    background: color-mix(in srgb, #DC2626 12%, var(--ej-color-surface, #ffffff));
    color: #DC2626;
  }
}

.daily-actions__arrow {
  color: var(--ej-color-muted, #64748B);
  font-size: 1rem;
  flex-shrink: 0;
  transition: transform 0.15s ease;

  .daily-actions__card:hover & {
    transform: translateX(2px);
  }
}

// -- Responsive: Mobile-first vertical stepper --------------------------------

@media (max-width: 768px) {
  .setup-wizard__stepper {
    flex-direction: column;
    gap: 0;
  }

  .setup-wizard__step {
    flex-direction: row;
    align-items: flex-start;
    gap: var(--ej-spacing-sm, 0.5rem);
  }

  .setup-wizard__step-connector {
    position: absolute;
    top: 36px;
    left: 18px;
    right: auto;
    width: 2px;
    height: calc(100% - 18px);
  }

  .setup-wizard__step-card {
    text-align: left;
  }

  .setup-wizard__step-warning {
    justify-content: flex-start;
  }

  .daily-actions__grid {
    grid-template-columns: 1fr;
  }

  .daily-actions__card--primary {
    grid-column: span 1;
  }
}

// -- Reduced motion -----------------------------------------------------------

@media (prefers-reduced-motion: reduce) {
  .setup-wizard__progress-fill,
  .setup-wizard__step-indicator,
  .daily-actions__card,
  .daily-actions__arrow {
    transition: none;
  }
}
```

### 4.7 JavaScript setup-wizard.js

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/js/setup-wizard.js`

El JS gestiona: toggle expand/collapse, persistencia del estado dismiss en localStorage, y re-carga del wizard tras save en slide-panel.

```javascript
/**
 * @file
 * Setup Wizard behavior — SETUP-WIZARD-DAILY-001.
 *
 * Manages expand/collapse state, localStorage persistence,
 * and refresh after slide-panel form save.
 *
 * Directives:
 *   - Vanilla JS + Drupal.behaviors (no frameworks)
 *   - Drupal.t() for translations
 *   - No innerHTML without Drupal.checkPlain()
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.setupWizard = {
    attach: function (context) {
      once('setup-wizard', '[data-setup-wizard]', context).forEach(function (wizard) {
        var wizardId = wizard.getAttribute('data-setup-wizard');
        var isComplete = wizard.getAttribute('data-wizard-complete') === 'true';
        var storageKey = 'jaraba_setup_wizard_' + wizardId + '_dismissed';

        var collapsedEl = wizard.querySelector('[data-wizard-collapsed]');
        var panelEl = wizard.querySelector('[data-wizard-panel]');
        var toggleBtns = wizard.querySelectorAll('[data-wizard-toggle]');

        // Restore dismiss state from localStorage.
        if (isComplete && localStorage.getItem(storageKey) === 'true') {
          showCollapsed();
        }

        // Toggle handlers.
        toggleBtns.forEach(function (btn) {
          btn.addEventListener('click', function () {
            if (panelEl.classList.contains('setup-wizard__panel--hidden')) {
              showExpanded();
              localStorage.removeItem(storageKey);
            }
            else {
              showCollapsed();
              if (isComplete) {
                localStorage.setItem(storageKey, 'true');
              }
            }
          });
        });

        function showCollapsed() {
          if (collapsedEl) {
            collapsedEl.classList.remove('setup-wizard__collapsed--hidden');
          }
          if (panelEl) {
            panelEl.classList.add('setup-wizard__panel--hidden');
          }
          toggleBtns.forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
          });
        }

        function showExpanded() {
          if (collapsedEl) {
            collapsedEl.classList.add('setup-wizard__collapsed--hidden');
          }
          if (panelEl) {
            panelEl.classList.remove('setup-wizard__panel--hidden');
          }
          toggleBtns.forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'true');
          });
        }

        // Listen for slide-panel close events to refresh wizard state.
        document.addEventListener('jaraba:slide-panel:closed', function () {
          refreshWizardState(wizard, wizardId);
        });
      });
    },

    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        // Clean up event listeners if needed.
      }
    }
  };

  /**
   * Refreshes wizard completion state via API call.
   *
   * After a slide-panel form is saved (e.g., new AccionFormativaEi),
   * this fetches the updated wizard status and updates the UI.
   */
  function refreshWizardState(wizardEl, wizardId) {
    var apiUrl = Drupal.url('api/v1/setup-wizard/' + wizardId + '/status');

    fetch(apiUrl, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
    .then(function (response) {
      if (!response.ok) { return; }
      return response.json();
    })
    .then(function (data) {
      if (!data) { return; }

      // Update progress bar.
      var progressFill = wizardEl.querySelector('.setup-wizard__progress-fill');
      if (progressFill) {
        progressFill.style.width = data.completion_percentage + '%';
      }
      var progressText = wizardEl.querySelector('.setup-wizard__progress-text');
      if (progressText) {
        progressText.textContent = data.completion_percentage + '% ' + Drupal.t('completado');
      }

      // Update step states.
      if (data.steps) {
        data.steps.forEach(function (step) {
          var stepEl = wizardEl.querySelector('[data-step-id="' + step.id + '"]');
          if (!stepEl) { return; }

          stepEl.classList.toggle('setup-wizard__step--complete', step.is_complete);
          stepEl.classList.toggle('setup-wizard__step--active', step.is_active);

          // Update status label.
          var statusEl = stepEl.querySelector('.setup-wizard__step-status');
          if (statusEl && step.completion_data && step.completion_data.label) {
            statusEl.textContent = Drupal.checkPlain(step.completion_data.label);
            statusEl.classList.toggle('setup-wizard__step-status--success', step.is_complete);
          }
        });
      }

      // Update complete state.
      wizardEl.setAttribute('data-wizard-complete', data.is_complete ? 'true' : 'false');
    })
    .catch(function () {
      // Fail silently — wizard state will refresh on next page load.
    });
  }

})(Drupal, once);
```

**Nota sobre `once`:** Se usa `core/once` (Drupal 11). Si se detectan problemas CSP en subdominios multi-tenant, se puede migrar a JS-STANDALONE-MULTITENANT-001 (IIFE sin Drupal.behaviors).

### 4.8 Library Registration

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml`

```yaml
setup-wizard:
  version: 1.0
  css:
    component:
      css/components/setup-wizard.css: {}
  js:
    js/setup-wizard.js: { attributes: { defer: true } }
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
    - ecosistema_jaraba_theme/global-styling
    - ecosistema_jaraba_theme/slide-panel
```

**Compilación SCSS** — añadir al `package.json` en `build:css` o como `@use` en main.scss:

```scss
// En main.scss:
@use 'components/setup-wizard';
```

### 4.9 Preprocess Integration

El wizard se integra en el flujo preprocess existente. Cada vertical que use el wizard lo inyecta en su `preprocess_page__*` función. El endpoint API para refresh se registra en routing.yml del core.

**Ruta API** (en `ecosistema_jaraba_core.routing.yml`):

```yaml
ecosistema_jaraba_core.setup_wizard_status:
  path: '/api/v1/setup-wizard/{wizard_id}/status'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\SetupWizardApiController::getStatus'
  requirements:
    _permission: 'access content'
    _csrf_request_header_token: 'TRUE'
  methods: [GET]
```

**SetupWizardApiController:**

```php
public function getStatus(string $wizard_id): JsonResponse {
  $tenantId = $this->tenantContext->getCurrentTenantId();
  if (!$tenantId || !$this->wizardRegistry->hasWizard($wizard_id)) {
    return new JsonResponse(['error' => 'Not found'], 404);
  }
  $data = $this->wizardRegistry->getStepsForWizard($wizard_id, $tenantId);
  // Serialize TranslatableMarkup to strings.
  foreach ($data['steps'] as &$step) {
    $step['label'] = (string) $step['label'];
    $step['description'] = (string) $step['description'];
    if (isset($step['completion_data']['label'])) {
      $step['completion_data']['label'] = (string) $step['completion_data']['label'];
    }
    if (isset($step['completion_data']['warning'])) {
      $step['completion_data']['warning'] = (string) $step['completion_data']['warning'];
    }
  }
  return new JsonResponse($data);
}
```

---

## 5. Sprint 20 — Caso de Uso: Coordinador Andalucía +ei

> **Estado:** PLANIFICADO | **Estimación:** 12-16h | **Archivos nuevos:** 2 | **Archivos modificados:** 4

### 5.1 CoordinadorSetupWizardStep (4 pasos)

**Archivo:** `web/modules/custom/jaraba_andalucia_ei/src/SetupWizard/CoordinadorPlanFormativoStep.php`
**Archivo:** `web/modules/custom/jaraba_andalucia_ei/src/SetupWizard/CoordinadorAccionesFormativasStep.php`
**Archivo:** `web/modules/custom/jaraba_andalucia_ei/src/SetupWizard/CoordinadorSesionesStep.php`
**Archivo:** `web/modules/custom/jaraba_andalucia_ei/src/SetupWizard/CoordinadorValidacionStep.php`

**Registro en `jaraba_andalucia_ei.services.yml`:**

```yaml
  jaraba_andalucia_ei.setup_wizard.plan_formativo:
    class: Drupal\jaraba_andalucia_ei\SetupWizard\CoordinadorPlanFormativoStep
    arguments:
      - '@entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }

  jaraba_andalucia_ei.setup_wizard.acciones_formativas:
    class: Drupal\jaraba_andalucia_ei\SetupWizard\CoordinadorAccionesFormativasStep
    arguments:
      - '@entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }

  jaraba_andalucia_ei.setup_wizard.sesiones:
    class: Drupal\jaraba_andalucia_ei\SetupWizard\CoordinadorSesionesStep
    arguments:
      - '@entity_type.manager'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }

  jaraba_andalucia_ei.setup_wizard.validacion:
    class: Drupal\jaraba_andalucia_ei\SetupWizard\CoordinadorValidacionStep
    arguments:
      - '@?jaraba_andalucia_ei.accion_formativa_service'
      - '@?ecosistema_jaraba_core.tenant_context'
    tags:
      - { name: ecosistema_jaraba_core.setup_wizard_step }
```

**Nota:** Las dependencias cross-módulo usan `@?` (OPTIONAL-CROSSMODULE-001). `@entity_type.manager` es core y no requiere `@?`.

**Detalle de cada paso:**

| # | Step ID | Label | isComplete() | getCompletionData() |
|---|---------|-------|-------------|---------------------|
| 1 | `coordinador_ei.plan_formativo` | Plan Formativo | `planes_activos > 0` | `{count: N, label: 'N plan(es) creado(s)'}` |
| 2 | `coordinador_ei.acciones_formativas` | Acciones Formativas | `total_acciones > 0` | `{count: N, label: 'N acción(es)', warning: 'M sin VoBo' si vobo_pendiente > 0}` |
| 3 | `coordinador_ei.sesiones` | Programar Sesiones | `sesiones_programadas > 0` | `{count: N, label: 'N sesión(es)'}` |
| 4 | `coordinador_ei.validacion` | Validación Normativa | `validarRequisitosPlan().valido && vobo_pendiente == 0` | `{label: '50h form + 10h orient ✓' o errores[], warning: 'VoBo SAE pendiente'}` |

**Paso 4 (Validación)** es especial: no tiene route de acción propia (es un checkpoint). Su `getRoute()` devuelve la ruta del plan formativo (para que el coordinador pueda revisar/ajustar). Su `isComplete()` consulta `AccionFormativaService::validarRequisitosPlan()` y verifica que `vobo_pendiente == 0`.

**Ejemplo implementación Paso 1:**

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Create a Plan Formativo (PlanFormativoEi entity).
 *
 * A plan is the top-level container that groups acciones formativas
 * for a specific carril (impulso_digital, acelera_pro, or hibrido).
 * The plan stores cumulative hours and validates normative minimums.
 *
 * The step is considered complete when at least one PlanFormativoEi
 * with estado != 'borrador' exists for the tenant.
 */
class CoordinadorPlanFormativoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  public function getId(): string {
    return 'coordinador_ei.plan_formativo';
  }

  public function getWizardId(): string {
    return 'coordinador_ei';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Plan Formativo');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Definir el marco general de horas, contenidos y objetivos del programa.');
  }

  public function getWeight(): int {
    return 10;
  }

  public function getIcon(): array {
    return [
      'category' => 'education',
      'name' => 'book-open',
      'variant' => 'duotone',
    ];
  }

  public function getRoute(): string {
    return 'jaraba_andalucia_ei.hub.plan_formativo.add';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function useSlidePanel(): bool {
    return TRUE;
  }

  public function getSlidePanelSize(): string {
    return 'large';
  }

  public function isComplete(int $tenantId): bool {
    return $this->getActiveCount($tenantId) > 0;
  }

  public function getCompletionData(int $tenantId): array {
    $count = $this->getActiveCount($tenantId);
    return [
      'count' => $count,
      'label' => (string) $this->formatPlural(
        $count,
        '1 plan creado',
        '@count planes creados',
      ),
    ];
  }

  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Count non-draft PlanFormativoEi entities for the tenant.
   */
  protected function getActiveCount(int $tenantId): int {
    try {
      return (int) $this->entityTypeManager
        ->getStorage('plan_formativo_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', 'borrador', '<>')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
```

### 5.2 Template coordinador-dashboard.html.twig (modificaciones)

Reemplazar la sección actual de action cards (líneas 48-115) por:

```twig
    {# === SETUP WIZARD — SETUP-WIZARD-DAILY-001 === #}
    {% if setup_wizard and setup_wizard.steps|length > 0 %}
      {% include '@ecosistema_jaraba_theme/partials/_setup-wizard.html.twig' with {
        wizard: setup_wizard,
        wizard_title: 'Configuración del Programa'|t,
        wizard_subtitle: 'Completa estos pasos para poner en marcha el programa PIIL CV 2025'|t,
        collapsed_label: 'Programa configurado'|t,
      } only %}
    {% endif %}

    {# === DAILY ACTIONS — Operación diaria === #}
    {% include '@ecosistema_jaraba_theme/partials/_daily-actions.html.twig' with {
      daily_actions: daily_actions,
      actions_title: 'Acciones del día'|t,
    } only %}
```

### 5.3 CoordinadorDashboardController (variables nuevas)

En el método `dashboard()`, añadir:

```php
// Setup Wizard (SETUP-WIZARD-DAILY-001).
$wizardData = [];
if ($this->wizardRegistry) {
  $wizardData = $this->wizardRegistry->getStepsForWizard('coordinador_ei', $tenantId);
}

// Daily Actions — operación recurrente.
$dailyActions = $this->buildDailyActions($tenantId, $stats, $hubKpis);

// Pasar al template:
'setup_wizard' => $wizardData,
'daily_actions' => $dailyActions,
```

**Método `buildDailyActions()`:**

```php
/**
 * Builds the daily action cards for the coordinador dashboard.
 *
 * These are operational tasks that recur daily once the program
 * is running, NOT setup/configuration tasks.
 *
 * @return array
 *   Array of action objects ready for _daily-actions.html.twig.
 */
protected function buildDailyActions(int $tenantId, array $stats, array $hubKpis): array {
  $actions = [];

  // 1. Gestionar solicitudes (PRIMARY — most common daily task).
  $pendingSolicitudes = (int) ($hubKpis['pending_solicitudes'] ?? 0);
  $actions[] = [
    'id' => 'solicitudes',
    'label' => $this->t('Gestionar solicitudes'),
    'description' => $this->t('Revisar y aprobar candidatos'),
    'icon' => ['category' => 'actions', 'name' => 'inbox', 'variant' => 'duotone'],
    'color' => 'azul-corporativo',
    'route' => 'jaraba_andalucia_ei.coordinador_dashboard',
    'route_params' => [],
    'badge' => $pendingSolicitudes > 0 ? $pendingSolicitudes : NULL,
    'badge_type' => $pendingSolicitudes > 5 ? 'warning' : 'info',
    'is_primary' => TRUE,
  ];

  // 2. Nuevo participante.
  $actions[] = [
    'id' => 'nuevo_participante',
    'label' => $this->t('Nuevo participante'),
    'description' => $this->t('Registrar alta en programa'),
    'icon' => ['category' => 'actions', 'name' => 'plus', 'variant' => 'outline'],
    'color' => 'azul-corporativo',
    'route' => 'entity.programa_participante_ei.add_form',
    'route_params' => [],
    'use_slide_panel' => TRUE,
    'slide_panel_size' => 'large',
  ];

  // 3. Programar sesión (also operational — recurring scheduling).
  $actions[] = [
    'id' => 'programar_sesion',
    'label' => $this->t('Programar sesión'),
    'description' => $this->t('Orientación, formación o tutoría'),
    'icon' => ['category' => 'ui', 'name' => 'calendar', 'variant' => 'duotone'],
    'color' => 'verde-innovacion',
    'route' => 'jaraba_andalucia_ei.hub.sesion_programada.add',
    'route_params' => [],
    'use_slide_panel' => TRUE,
    'slide_panel_size' => 'large',
  ];

  // 4. Exportar STO.
  $actions[] = [
    'id' => 'exportar_sto',
    'label' => $this->t('Exportar STO'),
    'description' => $this->t('Descarga seguimiento técnico'),
    'icon' => ['category' => 'actions', 'name' => 'export', 'variant' => 'duotone'],
    'color' => 'azul-corporativo',
    'route' => 'jaraba_andalucia_ei.sto_export',
    'route_params' => [],
  ];

  // 5. Leads / captación.
  $actions[] = [
    'id' => 'leads',
    'label' => $this->t('Leads'),
    'description' => $this->t('Gestión de candidatos'),
    'icon' => ['category' => 'business', 'name' => 'talent-search', 'variant' => 'duotone'],
    'color' => 'verde-innovacion',
    'route' => 'jaraba_andalucia_ei.leads_guia',
    'route_params' => [],
  ];

  return $actions;
}
```

### 5.4 Slide-Panel para Plan Formativo

Actualmente NO existe ruta slide-panel para `PlanFormativoEi`. Es necesario añadir:

**Ruta nueva** en `jaraba_andalucia_ei.routing.yml`:

```yaml
jaraba_andalucia_ei.hub.plan_formativo.add:
  path: '/api/v1/andalucia-ei/hub/plan-formativo/form'
  defaults:
    _controller: '\Drupal\jaraba_andalucia_ei\Controller\CoordinadorFormController::addPlanFormativo'
    _title: 'Nuevo Plan Formativo'
  requirements:
    _permission: 'create plan formativo ei'
  methods: [GET, POST]
```

**Método en CoordinadorFormController:**

```php
public function addPlanFormativo(Request $request): Response|array {
  return $this->handleEntityForm('plan_formativo_ei', NULL, $request);
}
```

El `handleEntityForm()` genérico existente (línea 95) ya soporta cualquier entity type — solo necesita la ruta y el método delegado.

### 5.5 Reestructuración de Daily Actions

Las 5 cards actuales se reestructuran:

| Antes (actual) | Tipo | Después | Ubicación |
|----------------|------|---------|-----------|
| Nuevo participante | Diario ✅ | **Mantener** en Daily Actions | Card operativa |
| Nueva Acción Formativa | Setup ❌ | **Mover** al Wizard paso 2 | Setup Wizard step |
| Programar Sesión | Dual | **Mantener** en Daily Actions (también en Wizard paso 3) | Ambos |
| Exportar STO | Diario ✅ | **Mantener** en Daily Actions | Card operativa |
| Leads | Diario ✅ | **Mantener** en Daily Actions | Card operativa |
| — | — | **NUEVO: Gestionar solicitudes** (primary, badge) | Card operativa |

### 5.6 Preprocess page__andalucia_ei (drupalSettings)

En `ecosistema_jaraba_theme_preprocess_page__andalucia_ei()`, añadir la library:

```php
$variables['#attached']['library'][] = 'ecosistema_jaraba_theme/setup-wizard';
```

La variable `setup_wizard` ya viene del controller (no del preprocess) porque es específica del coordinador dashboard, no de todas las rutas andalucia_ei.

---

## 6. Mapa de Aplicación Transversal (10 Verticales + Perfiles)

### 6.1 Verticales Canónicos

| Vertical | Wizard ID | Rol | Pasos Setup | Prioridad |
|----------|-----------|-----|-------------|-----------|
| **andalucia_ei** | `coordinador_ei` | Coordinador | Plan → Acciones → Sesiones → Validación | P0 (Sprint 20) |
| **andalucia_ei** | `orientador_ei` | Orientador | Perfil → Disponibilidad → Vincular participantes | P1 |
| **empleabilidad** | `candidato_empleo` | Candidato | Perfil → CV → Preferencias → Alertas | P1 |
| **empleabilidad** | `recruiter_empleo` | Recruiter | Empresa → Puestos tipo → Pipeline ATS | P2 |
| **comercioconecta** | `comerciante` | Comerciante | Alta negocio → Catálogo → Envíos → Pagos | P2 |
| **agroconecta** | `productor` | Productor | Explotación → Catálogo → Certificaciones → Logística | P2 |
| **jarabalex** | `despacho` | Despacho | Despacho → Áreas → Tarifas → LexNET | P2 |
| **serviciosconecta** | `profesional` | Profesional | Perfil → Servicios → Disponibilidad → Portfolio | P2 |
| **emprendimiento** | `emprendedor` | Emprendedor | Idea → Plan negocio → Viabilidad → Equipo | P3 |
| **formacion** | `instructor_lms` | Instructor | Curso → Módulos → Materiales → Evaluación | P3 |

### 6.2 Servicios Transversales

| Contexto | Wizard ID | Rol | Pasos Setup | Prioridad |
|----------|-----------|-----|-------------|-----------|
| **Tenant Admin** | `tenant_admin` | Admin | Tenant → Branding → Usuarios → Plan/Billing | P1 |
| **Avatar/Perfil** | `user_profile` | Todo usuario | Perfil → Verificar email → Foto → Preferencias | P1 |
| **Page Builder** | `page_builder` | Editor | Primera página → Elegir template → Publicar | P3 |

### 6.3 Capacidad de Escala

Con 13+ wizards potenciales, el patrón tagged services garantiza:
- **Cero acoplamiento:** Cada módulo registra sus pasos independientemente
- **Auto-descubrimiento:** El CompilerPass recolecta todo automáticamente
- **Testing aislado:** Cada step es un servicio unitario testeable

---

## 7. Tabla de Correspondencia con Especificaciones Técnicas

| # | Especificación | Requisito | Implementación | Estado |
|---|---------------|-----------|----------------|--------|
| 1 | PIIL CV 2025 Art. 6 — Diseño programa | Plan formativo obligatorio | Wizard paso 1: PlanFormativoEi | 🔲 Sprint 20 |
| 2 | PIIL CV 2025 Art. 7 — Acciones formativas | Acciones con VoBo SAE | Wizard paso 2: AccionFormativaEi + VoboSaeWorkflowService | 🔲 Sprint 20 |
| 3 | PIIL CV 2025 Art. 8 — Sesiones programadas | Sesiones con tipos PIIL | Wizard paso 3: SesionProgramadaEi + 6 tipos | 🔲 Sprint 20 |
| 4 | PIIL CV 2025 Art. 10 — Requisitos mínimos | ≥50h formación + ≥10h orientación | Wizard paso 4: validarRequisitosPlan() | 🔲 Sprint 20 |
| 5 | WCAG 2.1 AA — 1.3.1 | Estructura semántica | role="list", role="progressbar", aria-labels | 🔲 Sprint 19 |
| 6 | WCAG 2.1 AA — 2.4.7 | Focus visible | focus-visible con outline 3px | 🔲 Sprint 19 |
| 7 | WCAG 2.1 AA — 2.5.5 | Touch targets | Botones ≥44px | 🔲 Sprint 19 |
| 8 | WCAG 2.1 AA — 4.1.2 | Name/Role/Value | aria-expanded, aria-controls | 🔲 Sprint 19 |
| 9 | Drupal 11 coding standards | PHP 8.4 strict types | declare(strict_types=1) en todos los archivos | 🔲 Sprint 19 |
| 10 | i18n — traducibilidad | Todos los textos traducibles | {% trans %}, $this->t(), TranslatableMarkup | 🔲 Sprint 19 |

---

## 8. Tabla de Cumplimiento de Directrices

| Directriz | Estado | Evidencia |
|-----------|--------|-----------|
| **SETUP-WIZARD-DAILY-001** (nueva) | 🔲 IMPL | Este plan define el patrón completo |
| **TENANT-001** | ✅ DISEÑADO | `isComplete($tenantId)` en cada step; `buildDailyActions($tenantId)` |
| **PREMIUM-FORMS-PATTERN-001** | ✅ COMPATBLE | Los forms del wizard (PlanFormativoEi, etc.) ya extienden PremiumEntityFormBase |
| **CSS-VAR-ALL-COLORS-001** | ✅ DISEÑADO | _setup-wizard.scss usa exclusivamente `var(--ej-*, fallback)` |
| **SCSS-001** | ✅ DISEÑADO | `@use '../variables' as *;` como primera línea |
| **SCSS-COLORMIX-001** | ✅ DISEÑADO | `color-mix(in srgb, ...)` para transparencias, 0 `rgba()` |
| **ICON-CONVENTION-001** | ✅ DISEÑADO | `jaraba_icon()` con category/name/variant |
| **ICON-DUOTONE-001** | ✅ DISEÑADO | Default variant `duotone` en todos los pasos |
| **ICON-COLOR-001** | ✅ DISEÑADO | Solo paleta Jaraba: azul-corporativo, naranja-impulso, verde-innovacion |
| **ZERO-REGION-001** | ✅ DISEÑADO | Variables desde controller, no desde preprocess |
| **ZERO-REGION-002** | ✅ DISEÑADO | Arrays planos para Twig, no entity objects |
| **TWIG-INCLUDE-ONLY-001** | ✅ DISEÑADO | `{% include ... only %}` en ambos parciales |
| **ORTOGRAFIA-TRANS-001** | ✅ DISEÑADO | Todos los `{% trans %}` con tildes correctas: Configuración, Formación, Orientación, Sesión, Validación |
| **ROUTE-LANGPREFIX-001** | ✅ DISEÑADO | `path()` en Twig, `Url::fromRoute()` en PHP |
| **SLIDE-PANEL-RENDER-001** | ✅ DISEÑADO | `renderPlain()` en CoordinadorFormController::handleEntityForm() |
| **OPTIONAL-CROSSMODULE-001** | ✅ DISEÑADO | `@?` para cross-module deps en services.yml |
| **CONTROLLER-READONLY-001** | ✅ DISEÑADO | No `protected readonly` en propiedades heredadas |
| **INNERHTML-XSS-001** | ✅ DISEÑADO | `Drupal.checkPlain()` en JS refresh function |
| **CSRF-API-001** | ✅ DISEÑADO | `_csrf_request_header_token: 'TRUE'` en ruta API |
| **WCAG-CONTRAST-TOKEN-001** | ✅ DISEÑADO | `color-mix(..., black)` para texto naranja/verde sobre fondo claro |
| **ENTITY-001** | N/A | No se crean nuevas entities |
| **UPDATE-HOOK-REQUIRED-001** | N/A | No se modifican baseFieldDefinitions |
| **DOC-GUARD-001** | ✅ | Este plan en docs/implementacion/, NO en master docs |

---

## 9. Plan de Testing

### 9.1 Unit Tests

```bash
# Ejecutar dentro del contenedor Docker:
lando ssh -c "cd /app && ./vendor/bin/phpunit \
  --filter 'CoordinadorPlanFormativoStepTest|CoordinadorAccionesFormativasStepTest|CoordinadorSesionesStepTest|CoordinadorValidacionStepTest|SetupWizardRegistryTest'"
```

**Tests necesarios:**

| Test Class | # Tests | Qué verifica |
|------------|---------|-------------|
| `SetupWizardRegistryTest` | 6 | addStep, getStepsForWizard ordering, isWizardComplete, completionPercentage, hasWizard, empty wizard |
| `CoordinadorPlanFormativoStepTest` | 4 | getId, isComplete TRUE/FALSE, getCompletionData count, getIcon returns duotone |
| `CoordinadorAccionesFormativasStepTest` | 5 | isComplete, completionData with VoBo warning, weight ordering, route slide-panel |
| `CoordinadorSesionesStepTest` | 3 | isComplete, completionData count, route |
| `CoordinadorValidacionStepTest` | 4 | isComplete when all valid, isComplete FALSE with errors, warning message, not optional |

**Total: ~22 unit tests.**

### 9.2 Kernel Tests

```bash
lando ssh -c "cd /app && ./vendor/bin/phpunit \
  --filter 'SetupWizardRegistryIntegrationTest'"
```

| Test Class | # Tests | Qué verifica |
|------------|---------|-------------|
| `SetupWizardRegistryIntegrationTest` | 3 | CompilerPass collects tagged services, steps sorted by weight, tenant isolation |

### 9.3 Visual Verification

```bash
# Compilar SCSS:
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && npm run build"

# Verificar CSS compilado:
lando ssh -c "ls -la /app/web/themes/custom/ecosistema_jaraba_theme/css/components/setup-wizard.css"

# Limpiar cache Drupal:
lando drush cr
```

Verificar en el navegador en `https://jaraba-saas.lndo.site/es/coordinador`:
1. Wizard visible con 4 pasos
2. Estado correcto (si no hay datos, todos los pasos en pendiente)
3. Click en "Configurar" abre slide-panel
4. Tras guardar en slide-panel, wizard se actualiza
5. Responsive: stepper vertical en móvil
6. Auto-collapse cuando todos los pasos completados
7. Re-expand desde barra colapsada

---

## 10. RUNTIME-VERIFY-001 — Verificación Post-Implementación

| # | Capa | Verificación | Comando/Acción |
|---|------|-------------|----------------|
| 1 | CSS | Compilado (timestamp CSS > SCSS) | `stat css/components/setup-wizard.css` |
| 2 | Library | Registrada en libraries.yml | `drush libraries-list \| grep setup-wizard` |
| 3 | Library attached | setup-wizard en head del HTML | Inspeccionar `<link>` en `/es/coordinador` |
| 4 | Ruta API | `/api/v1/setup-wizard/coordinador_ei/status` accesible | `curl -H "X-CSRF-Token: ..." URL` |
| 5 | Tagged services | 4 steps registrados | `drush devel:services \| grep setup_wizard` |
| 6 | drupalSettings | `setup_wizard` presente en JSON | Inspeccionar `<script>drupalSettings` |
| 7 | Slide-panel | Plan Formativo abre en panel | Click en "Configurar" del paso 1 |
| 8 | Twig parciales | Sin errores 500 | Navegar a `/es/coordinador` |
| 9 | Responsive | Stepper vertical en < 768px | Device toolbar Chrome |
| 10 | Accesibilidad | Tab navigation funciona | Tab key cycle en wizard |
| 11 | localStorage | Dismiss persistido | Completar wizard → recargar → barra colapsada |
| 12 | Refresh API | Wizard actualiza tras save | Guardar form en slide-panel |

---

## 11. Gestión de Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| CompilerPass no recoge steps en cierto orden de boot | Baja | Media | `usort()` por weight en Registry garantiza orden |
| `isComplete()` lento con muchas entities | Baja | Media | Usar `->count()->execute()` (sin cargar entities) |
| Colisión tag name con otros módulos | Muy baja | Alta | Tag namespace completo: `ecosistema_jaraba_core.setup_wizard_step` |
| CSS conflicto con estilos existentes de action-card | Media | Baja | Clases BEM separadas: `setup-wizard__*` y `daily-actions__*` |
| JS `once` falla en subdominios (CSP) | Baja | Media | Fallback: migrar a IIFE si necesario (JS-STANDALONE-MULTITENANT-001) |
| Plan Formativo slide-panel no redirige tras save | Media | Media | `$form['#action'] = $request->getRequestUri()` + redirect override en form |
| Wizard visible para orientadores (no coordinadores) | Media | Baja | Verificar permisos en controller antes de pasar `setup_wizard` |

---

## 12. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-03-13 | 1.0.0 | Versión inicial del plan. Patrón SETUP-WIZARD-DAILY-001 transversal. Sprint 19 (infra) + Sprint 20 (Andalucía +ei). |

---

> **Siguiente paso:** Implementar Sprint 19 (infraestructura transversal en `ecosistema_jaraba_core` + `ecosistema_jaraba_theme`), luego Sprint 20 (caso Andalucía +ei coordinador).
