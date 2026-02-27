# Plan de Implementacion: Capa Experiencial IA Nivel 5 — De "Codigo Existe" a "Usuario lo Experimenta"

**Fecha:** 2026-02-27
**Version:** 1.0.0
**Autor:** Equipo de Ingenieria JarabaImpactPlatformSaaS
**Estado:** En implementacion
**Prioridad:** P0 — Critica
**Estimacion total:** 200-280 horas

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Diagnostico: Codigo vs Experiencia](#2-diagnostico-codigo-vs-experiencia)
3. [Tabla de Correspondencia GAP-a-Experiencia](#3-tabla-de-correspondencia-gap-a-experiencia)
4. [Tabla de Cumplimiento de Directrices](#4-tabla-de-cumplimiento-de-directrices)
5. [Fase 1: Infraestructura SCSS y Templates Base](#5-fase-1-infraestructura-scss-y-templates-base)
   - 5.1 [Route Bundles SCSS](#51-route-bundles-scss)
   - 5.2 [Library Declarations](#52-library-declarations)
   - 5.3 [Body Classes via hook_preprocess_html()](#53-body-classes-via-hook_preprocess_html)
   - 5.4 [Page Templates Zero-Region](#54-page-templates-zero-region)
   - 5.5 [Partials Reutilizables](#55-partials-reutilizables)
6. [Fase 2: Dashboard AI Compliance (GAP-L5-C)](#6-fase-2-dashboard-ai-compliance-gap-l5-c)
   - 6.1 [SCSS Route Bundle](#61-scss-route-bundle)
   - 6.2 [Page Template](#62-page-template)
   - 6.3 [Controller — Attached Library](#63-controller--attached-library)
   - 6.4 [Integracion con Theme Settings](#64-integracion-con-theme-settings)
7. [Fase 3: Dashboard Autonomous Agents (GAP-L5-F/G)](#7-fase-3-dashboard-autonomous-agents-gap-l5-fg)
   - 7.1 [SCSS Route Bundle](#71-scss-route-bundle)
   - 7.2 [Page Template](#72-page-template)
   - 7.3 [Controller — Attached Library](#73-controller--attached-library)
   - 7.4 [Partial: Health Indicator](#74-partial-health-indicator)
   - 7.5 [Self-Healing Real: Implementacion de Remediaciones](#75-self-healing-real-implementacion-de-remediaciones)
   - 7.6 [Autonomous Tasks: Logica Real por Tipo de Agente](#76-autonomous-tasks-logica-real-por-tipo-de-agente)
8. [Fase 4: Dashboard Causal Analytics (GAP-L5-H/I)](#8-fase-4-dashboard-causal-analytics-gap-l5-hi)
   - 8.1 [SCSS Route Bundle](#81-scss-route-bundle)
   - 8.2 [Page Template](#82-page-template)
   - 8.3 [Controller — Attached Library + Query Form](#83-controller--attached-library--query-form)
   - 8.4 [Partial: Causal Analysis Widget](#84-partial-causal-analysis-widget)
   - 8.5 [API Endpoint para Queries Causales](#85-api-endpoint-para-queries-causales)
9. [Fase 5: Voice Copilot Frontend (GAP-L5-D)](#9-fase-5-voice-copilot-frontend-gap-l5-d)
   - 9.1 [Partial: Voice FAB Widget](#91-partial-voice-fab-widget)
   - 9.2 [JavaScript: Voice Copilot Widget](#92-javascript-voice-copilot-widget)
   - 9.3 [SCSS: Voice Copilot Component](#93-scss-voice-copilot-component)
   - 9.4 [Library Declaration](#94-library-declaration)
   - 9.5 [VoicePipelineService — Graceful Degradation](#95-voicepipelineservice--graceful-degradation)
10. [Fase 6: Browser Agent Observabilidad (GAP-L5-E)](#10-fase-6-browser-agent-observabilidad-gap-l5-e)
    - 10.1 [BrowserAgentService — Graceful Degradation](#101-browseragentservice--graceful-degradation)
    - 10.2 [Configuration Form](#102-configuration-form)
11. [Fase 7: Prompt Improvement Approval Flow (GAP-L5-B)](#11-fase-7-prompt-improvement-approval-flow-gap-l5-b)
    - 11.1 [Approval Form y Controller](#111-approval-form-y-controller)
    - 11.2 [API Endpoint](#112-api-endpoint)
12. [Fase 8: Integracion en Estructura de Navegacion Drupal](#12-fase-8-integracion-en-estructura-de-navegacion-drupal)
    - 12.1 [Menu Links](#121-menu-links)
    - 12.2 [Field UI Tabs](#122-field-ui-tabs)
    - 12.3 [Views Integration](#123-views-integration)
13. [Fase 9: i18n — Textos Traducibles](#13-fase-9-i18n--textos-traducibles)
14. [Fase 10: Mobile-First y Responsive](#14-fase-10-mobile-first-y-responsive)
15. [Fase 11: Tests Funcionales de Experiencia](#15-fase-11-tests-funcionales-de-experiencia)
16. [Verificacion End-to-End](#16-verificacion-end-to-end)
17. [Troubleshooting](#17-troubleshooting)
18. [Referencias Cruzadas](#18-referencias-cruzadas)

---

## 1. Resumen Ejecutivo

### El Problema

La plataforma JarabaImpactPlatformSaaS implemento 9 GAPs de elevacion IA (Sprints 1-5) que la situan en Nivel 5 Transformacional en cuanto a **arquitectura y backend**. Sin embargo, una auditoria exhaustiva revelo que 7 de los 9 GAPs tienen una brecha critica entre "el codigo existe" y "el usuario lo experimenta":

| Estado | GAPs | Porcentaje |
|--------|------|-----------|
| Funcional end-to-end | GAP-L5-A (Constitutional Verification) | 11% |
| Parcialmente funcional | GAP-L5-H (Federated — backend OK, sin frontend) | 11% |
| Scaffold sin experiencia | 7 GAPs restantes | 78% |

### Brechas Identificadas

1. **Sin SCSS/CSS**: Los 3 dashboards (Compliance, Autonomous, Causal) no tienen hojas de estilo; se muestran como HTML crudo sin formato.
2. **Sin Library Declarations**: Ningun dashboard IA de Nivel 5 declara su libreria CSS en `ecosistema_jaraba_theme.libraries.yml`, por lo que no se adjuntan al render.
3. **Sin Body Classes**: Las rutas de dashboards IA no inyectan body classes en `hook_preprocess_html()`, rompiendose el patron BODY-CLASS-001 y los page templates.
4. **Self-Healing solo logea**: `AutoDiagnosticService` detecta anomalias pero sus 5 metodos `execute*` solo llaman a `$this->logger->notice()` — no cambian estado real del sistema (no llaman a ModelRouter, ProviderFallback, ni SemanticCache).
5. **Autonomous Tasks son no-ops**: `AutonomousAgentService` tiene 4 metodos `task*` que retornan arrays vacios con `'success' => TRUE` sin ejecutar logica real.
6. **Sin Forms/API para usuarios**: No hay formulario para lanzar queries causales, ni para crear sesiones autonomas desde la UI.
7. **Frontend faltante**: No existen los archivos: voice FAB widget, health indicator partial, causal analysis widget, page templates zero-region para los 3 dashboards.

### Objetivo

Cerrar TODAS las brechas para que cada GAP no solo "exista como codigo" sino que "el usuario lo experimente" al 100%, cumpliendo con las 30+ directrices del proyecto.

### Metricas de Exito

| Metrica | Antes | Despues |
|---------|-------|---------|
| Dashboards con CSS | 0/3 | 3/3 |
| Libraries declaradas | 0/3 | 3/3 |
| Body classes inyectadas | 0/3 | 3/3 |
| Page templates zero-region | 0/3 | 3/3 |
| Self-healing con accion real | 0/5 acciones | 5/5 acciones |
| Autonomous tasks funcionales | 0/4 tipos | 4/4 tipos |
| Forms/API para usuario | 0/3 | 3/3 |
| Frontend files existentes | 0/6 | 6/6 |
| Tests funcionales experiencia | 0 | 15+ |

---

## 2. Diagnostico: Codigo vs Experiencia

### GAP-L5-A: Constitutional Verification — FUNCIONAL

**Estado:** El unico GAP que funciona end-to-end.

**Flujo completo:** SmartBaseAgent::execute() -> doExecute() -> applyVerification() -> VerifierAgentService::verify() -> ConstitutionalGuardrailService::checkAll() -> resultado al usuario con etiqueta de verificacion.

**Evidencia:**
- `VerifierAgentService` inyectado en todos los agentes Gen 2 via `calls: [setVerifier, ['@?jaraba_ai_agents.verifier']]`
- `ConstitutionalGuardrailService` tiene 5 reglas inmutables codificadas como constantes
- Entidad `VerificationResult` registra cada verificacion (append-only)
- Tests: `ConstitutionalGuardrailServiceTest` + `VerifierAgentServiceTest`

**Brecha:** Ninguna. Funciona tal como se diseno.

---

### GAP-L5-B: Self-Improving Prompts — SCAFFOLD

**Estado:** La entidad `PromptImprovement` existe y `AgentSelfReflectionService` genera propuestas. PERO:

**Brechas:**
- No hay UI de aprobacion: las propuestas se crean pero nadie las revisa/aplica
- No hay endpoint API para aprobar/rechazar mejoras
- No hay listado accesible con acciones (aprobar/rechazar/revertir)
- El flujo es: agente -> propone mejora -> se almacena -> **fin** (nunca se aplica)

**Archivos involucrados:**
- `jaraba_ai_agents/src/Service/AgentSelfReflectionService.php:87` — genera propuesta
- `jaraba_ai_agents/src/Service/SelfImprovingPromptManager.php:45` — persiste propuesta
- `jaraba_ai_agents/src/Entity/PromptImprovement.php` — entidad
- `entity.prompt_improvement.collection` — listado en `/admin/content/ai/prompt-improvements` (sin acciones)

---

### GAP-L5-C: AI Compliance Dashboard — SIN ESTILO

**Estado:** El template `ai-compliance-dashboard.html.twig` existe con HTML correcto (BEM, `|t` para i18n). El controller `AiComplianceDashboardController::dashboard()` devuelve render array con `#theme`. La ruta `/admin/config/ai/compliance` funciona. PERO:

**Brechas:**
- No existe `scss/routes/_ai-compliance.scss` — HTML crudo sin formato
- No se declara libreria en `ecosistema_jaraba_theme.libraries.yml`
- No se adjunta la libreria en el controller (`#attached['library']` ausente)
- No hay body class `page-ai-compliance` en `hook_preprocess_html()`
- No hay page template `page--ai-compliance.html.twig` (zero-region)
- La ruta usa el layout por defecto de Drupal admin con sidebar

---

### GAP-L5-D: Voice Copilot — STUBS

**Estado:** `VoicePipelineService` tiene metodos `transcribe()` y `synthesize()` que estan correctamente estructurados. PERO:

**Brechas:**
- `executeSTT()` lanza `RuntimeException('STT provider not yet configured')` en linea 199
- `executeTTS()` lanza `RuntimeException('TTS provider not yet configured')` en linea 214
- No existe el parcial `_voice-copilot-fab.html.twig` (boton FAB con microfono)
- No existe `ecosistema_jaraba_core/js/voice-copilot-widget.js`
- No existe `ecosistema_jaraba_core/scss/_voice-copilot.scss`
- No se declara library `voice-copilot`

---

### GAP-L5-E: Browser Agent — STUB

**Estado:** `BrowserAgentService` tiene validacion de URL allowlist funcional + recording de tasks. PERO:

**Brechas:**
- `executeBrowserTask()` en linea 195 lanza `RuntimeException('Playwright browser container not configured')`
- No hay formulario de configuracion del endpoint de Playwright
- No hay degradacion graceful: si Playwright no esta disponible, el servicio falla con excepcion

---

### GAP-L5-F: Autonomous Agents — NO-OPS

**Estado:** `AutonomousAgentService` tiene lifecycle completo (create, activate, pause, escalate, heartbeat). PERO:

**Brechas:**
- `taskContentCurator()` en linea 384 retorna `['success' => TRUE, 'data' => ['task' => 'content_curator', 'suggestions' => []], 'cost' => 0.0]` — array vacio
- `taskKBMaintainer()` en linea 398 retorna stale_entries vacio
- `taskChurnPrevention()` en linea 412 retorna at_risk_users vacio
- Solo `taskReputationMonitor()` hace algo (llama a autoDiagnostic)
- No hay formulario para crear sesiones autonomas desde la UI
- No hay page template zero-region para el dashboard
- No hay SCSS para el dashboard
- No hay libreria declarada

---

### GAP-L5-G: Self-Healing — SOLO OBSERVACIONAL

**Estado:** `AutoDiagnosticService` tiene deteccion de anomalias correcta (5 tipos, thresholds, severidades). PERO:

**Brechas de ejecucion (5 metodos que solo logean):**

1. `executeAutoDowngrade()` (linea 311): Solo `$this->logger->notice(...)` + `return 'success'`. **No llama a** `$this->modelRouter` para cambiar el tier.
2. `executeAutoRefreshPrompt()` (linea 323): Solo logea. **No llama a** `SelfImprovingPromptManager` para hacer rollback.
3. `executeAutoRotate()` (linea 333): Solo logea. **No llama a** `$this->providerFallback` para rotar provider.
4. `executeAutoWarmCache()` (linea 343): Verifica que semanticCache no sea null, pero solo logea. **No llama a** `$this->semanticCache->warmUp()`.
5. `executeAutoThrottle()` (linea 357): Solo logea. **No implementa** rate limiting.

---

### GAP-L5-H: Federated Insights — PARCIAL

**Estado:** `FederatedInsightService` funciona correctamente en backend (k-anonimidad, noise injection, agregacion). PERO:

**Brechas:**
- No hay frontend widget para visualizar insights de forma atractiva
- No hay SCSS para el dashboard
- No hay page template zero-region
- No hay libreria declarada
- No hay body class

---

### GAP-L5-I: Causal Analytics — FALLBACK ONLY

**Estado:** `CausalAnalyticsService` tiene 4 tipos de query y fallback rule-based funcional. PERO:

**Brechas:**
- Sin AI provider configurado, solo funciona el modo rule-based (confidence 0.3, cero LLM)
- No hay formulario para que el usuario lance queries causales
- No hay endpoint API para queries desde el frontend
- No hay widget interactivo para la experiencia de query natural-language
- No hay SCSS, page template, libreria

---

## 3. Tabla de Correspondencia GAP-a-Experiencia

| GAP | Servicio Backend | Estado Backend | Accion Frontend Necesaria | Prioridad |
|-----|-----------------|----------------|--------------------------|-----------|
| L5-A | ConstitutionalGuardrailService, VerifierAgentService | COMPLETO | Ninguna | -- |
| L5-B | AgentSelfReflectionService, SelfImprovingPromptManager | Funcional | Formulario de aprobacion + API endpoint | P1 |
| L5-C | AiRiskClassificationService, AiComplianceDashboardController | Funcional | SCSS + Page Template + Library + Body Class | P0 |
| L5-D | VoicePipelineService | Stub (RuntimeException) | Voice FAB + JS widget + SCSS + Graceful degradation | P2 |
| L5-E | BrowserAgentService | Stub (RuntimeException) | Config form + Graceful degradation | P2 |
| L5-F | AutonomousAgentService | Lifecycle OK, Tasks vacios | Logica real en tasks + SCSS + Page Template + Session Form | P0 |
| L5-G | AutoDiagnosticService | Deteccion OK, Ejecucion no-op | Implementar 5 execute* reales + Health Indicator | P0 |
| L5-H | FederatedInsightService | Backend completo | SCSS + Page Template + Widget | P1 |
| L5-I | CausalAnalyticsService | Backend con fallback | Query form + API + SCSS + Widget interactivo | P1 |

---

## 4. Tabla de Cumplimiento de Directrices

| ID Directriz | Nombre | Como se Cumple en Este Plan | Verificacion |
|-------------|--------|---------------------------|-------------|
| ICON-CONVENTION-001 | `jaraba_icon()` en templates | Todos los templates usan `jaraba_icon('categoria', 'nombre', {variant: 'duotone'})` para iconos. Nunca Unicode emojis. | Grep: `jaraba_icon\(` en templates nuevos |
| ICON-EMOJI-001 | Sin emojis Unicode | Ningun canvas_data, template ni log contiene emojis Unicode. Solo la funcion `jaraba_icon()`. | Grep: `[\x{1F000}-\x{1FFFF}]` = 0 matches |
| ZERO-REGION-001 | Variables via hook_preprocess_page() | Cada page template recibe variables desde `preprocess_page()`. Nunca `{{ page.content }}`. | Inspeccionar templates: `clean_content` |
| ZERO-REGION-002 | Sin entity objects en variables | Los controllers pasan arrays escalares al template, nunca objetos entidad directamente. | Revisar `#theme` arrays en controllers |
| ZERO-REGION-003 | Template Twig limpia | Cada dashboard usa `page--{route}.html.twig` con `{{ clean_content }}` y partials via `{% include %}`. | Inspeccionar templates |
| BODY-CLASS-001 | Body classes via hook_preprocess_html() | Se anaden body classes `page-ai-compliance`, `page-autonomous-agents`, `page-causal-analytics` en la funcion `ecosistema_jaraba_theme_preprocess_html()`. NUNCA `attributes.addClass()`. | Grep en `.theme` file |
| SCSS-BUILD-001 | Dart Sass 1.83.0 moderno | Archivos SCSS usan `@use '../variables' as *`, `color.adjust()`, `var(--ej-*)`. Compilacion: `sass scss/routes/{file}.scss css/routes/{file}.css --style=compressed`. | Compilar y verificar |
| FEDERATED-DESIGN-TOKENS | Solo CSS Custom Properties | Archivos SCSS en modulos NUNCA definen `$variables` SCSS propias. Solo consumen `var(--ej-*, fallback)`. | Grep: `\$ej-` en archivos de modulo = 0 |
| i18n-DIRECTIVE-P0 | 100% textos traducibles | Todos los textos visibles al usuario usan `|t` en Twig o `$this->t()` en PHP. Nunca strings hardcodeados. | Grep: strings sin `|t` en templates |
| OPTIONAL-SERVICE-DI-001 | `@?` para servicios opcionales | Todos los servicios cross-module se inyectan con `@?`. Los controllers usan `hasService()` + try-catch. | Revisar `services.yml` |
| TENANT-ISOLATION-ACCESS-001 | Verificacion tenant match | Cada AccessControlHandler verifica `entity->tenant_id == user->tenant_id`. | Revisar `*AccessControlHandler.php` |
| AI-IDENTITY-001 | `AIIdentityRule::apply()` | Todos los agentes autonomos aplican la regla de identidad antes de responder. | Grep: `AIIdentityRule::apply` |
| PRESAVE-RESILIENCE-001 | hasService() + try-catch | Presave hooks con servicios opcionales usan `\Drupal::hasService()` + try-catch. | Revisar `.module` hooks |
| SLIDE-PANEL-RENDER-001 | renderPlain() para modales | Forms servidos via slide-panel usan `renderPlain()` y `$form['#action']` explicito. | Revisar controllers con isSlidePanelRequest() |
| DOC-GUARD-001 | Edit-only para master docs | Este documento usa Write (archivo nuevo). Documentos maestros se modifican solo con Edit. | pre-commit hook |
| FIELD-UI-SETTINGS-TAB-001 | Tab default para Field UI | Cada entidad nueva tiene ruta `entity.{type}.settings` + tab en `links.task.yml`. | Revisar routing + links.task |
| CSRF-API-001 | Token CSRF en rutas API | Rutas POST usan `_csrf_request_header_token: 'TRUE'` o `_csrf_token: 'TRUE'`. | Revisar routing.yml |
| PB-PREMIUM-001 | BEM + jaraba_icon | Templates usan nomenclatura BEM (bloque__elemento--modificador) + jaraba_icon. | Inspeccionar clases CSS |
| PREMIUM-FORMS-PATTERN-001 | PremiumEntityFormBase | Cualquier formulario de entidad extiende `PremiumEntityFormBase`. | Grep: `extends PremiumEntityFormBase` |
| ROUTE-LANGPREFIX-001 | Url::fromRoute() para JS | URLs para fetch en JS usan `drupalSettings.path.baseUrl` + Url::fromRoute() generada. | Revisar JS files |
| LEGAL-CONFIG-001 | Contenido configurable desde UI | Footer, header y textos legales editables desde Theme Settings sin tocar codigo. | Verificar en admin/appearance |
| CANVAS-ARTICLE-001 | 3 campos canvas | ContentArticle tiene layout_mode, canvas_data, rendered_html. | N/A (no aplica a este plan) |
| MODEL-ROUTING-CONFIG-001 | Tiers en config YAML | Los 3 tiers (fast/balanced/premium) se leen de `jaraba_ai_agents.model_routing.yml`. | N/A (ya implementado) |
| SERVICE-CALL-CONTRACT-001 | Firmas de metodo exactas | Cada llamada a servicio usa los parametros correctos segun la firma declarada. | Tests unitarios |

---

## 5. Fase 1: Infraestructura SCSS y Templates Base

### 5.1 Route Bundles SCSS

**Patron establecido:** El proyecto usa route-specific CSS bundles compilados con Dart Sass. Cada bundle importa `_variables.scss` y los componentes SCSS necesarios.

**Referencia existente** (`scss/routes/dashboard.scss:1-19`):
```scss
/**
 * @file
 * S4-02: Route-specific CSS — Dashboard routes.
 *
 * Covers: /tenant/dashboard, /tenant/analytics, admin dashboards.
 * Compile: sass scss/routes/dashboard.scss css/routes/dashboard.css --style=compressed
 */
@use '../variables' as *;

@use '../ai-dashboard';
@use '../agent-dashboard';
// ...
```

**Archivos SCSS nuevos a crear:**

| Archivo | Ruta | Descripcion |
|---------|------|------------|
| `_ai-compliance.scss` | `scss/routes/_ai-compliance.scss` | Componentes del dashboard de compliance EU AI Act |
| `ai-compliance.scss` | `scss/routes/ai-compliance.scss` | Entry point route bundle (importa _ai-compliance + variables) |
| `_autonomous-agents.scss` | `scss/routes/_autonomous-agents.scss` | Componentes del dashboard autonomo + self-healing |
| `autonomous-agents.scss` | `scss/routes/autonomous-agents.scss` | Entry point route bundle |
| `_causal-analytics.scss` | `scss/routes/_causal-analytics.scss` | Componentes del dashboard causal + federated |
| `causal-analytics.scss` | `scss/routes/causal-analytics.scss` | Entry point route bundle |

**Estructura de cada entry point:**
```scss
/**
 * @file
 * Route-specific CSS — AI Compliance Dashboard.
 *
 * Covers: /admin/config/ai/compliance
 * Compile: sass scss/routes/ai-compliance.scss css/routes/ai-compliance.css --style=compressed
 */
@use '../variables' as *;

@use 'ai-compliance';
```

**Regla FEDERATED-DESIGN-TOKENS:** Los SCSS de estos dashboards NO definen `$variables` propias. Solo consumen CSS Custom Properties con fallbacks:

```scss
// CORRECTO: usa var(--ej-*, fallback)
.ai-compliance-dashboard__header {
  background: var(--ej-bg-surface, #ffffff);
  color: var(--ej-color-headings, #1a1a2e);
  border-radius: var(--ej-card-radius, 12px);
  font-family: var(--ej-font-headings, 'Outfit', sans-serif);
}

// INCORRECTO: define $variable SCSS propia
// $compliance-bg: #ffffff;  // PROHIBIDO en modulos
```

**Compilacion:**
```bash
# Dentro del contenedor Docker
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && \
  sass scss/routes/ai-compliance.scss css/routes/ai-compliance.css --style=compressed && \
  sass scss/routes/autonomous-agents.scss css/routes/autonomous-agents.css --style=compressed && \
  sass scss/routes/causal-analytics.scss css/routes/causal-analytics.css --style=compressed"
```

### 5.2 Library Declarations

**Patron establecido** (de `ecosistema_jaraba_theme.libraries.yml`):
```yaml
route-ai-features:
  css:
    theme:
      css/routes/ai-features.css: { weight: 10 }
  dependencies:
    - ecosistema_jaraba_theme/global-styling
```

**Libraries nuevas a declarar en `ecosistema_jaraba_theme.libraries.yml`:**

```yaml
# GAP-L5-C: AI Compliance Dashboard
route-ai-compliance:
  css:
    theme:
      css/routes/ai-compliance.css: { weight: 10 }
  dependencies:
    - ecosistema_jaraba_theme/global-styling

# GAP-L5-F/G: Autonomous Agents Dashboard
route-autonomous-agents:
  css:
    theme:
      css/routes/autonomous-agents.css: { weight: 10 }
  dependencies:
    - ecosistema_jaraba_theme/global-styling

# GAP-L5-H/I: Causal Analytics Dashboard
route-causal-analytics:
  css:
    theme:
      css/routes/causal-analytics.css: { weight: 10 }
  dependencies:
    - ecosistema_jaraba_theme/global-styling
```

**Adjuntar en controllers:**
Cada controller DEBE adjuntar la library en el render array:
```php
return [
  '#theme' => 'autonomous_agents_dashboard',
  '#attached' => [
    'library' => ['ecosistema_jaraba_theme/route-autonomous-agents'],
  ],
  // ... variables ...
];
```

### 5.3 Body Classes via hook_preprocess_html()

**Patron establecido** (de `ecosistema_jaraba_theme.theme:1896-1955`):
El sistema tiene un catch-all basado en prefijos de ruta. Las rutas de `jaraba_ai_agents.*` NO estan incluidas actualmente.

**Accion:** Anadir las 3 rutas de dashboards IA al array `$module_body_classes` en `hook_preprocess_html()`:

```php
// En el array $module_body_classes (linea ~1896):
'jaraba_ai_agents.compliance_dashboard' => 'page-ai-compliance',
'jaraba_ai_agents.autonomous_agents_dashboard' => 'page-autonomous-agents',
'jaraba_ai_agents.causal_analytics_dashboard' => 'page-causal-analytics',
```

**Resultado:** Cuando el usuario visita `/admin/config/ai/compliance`, el body tendra las clases:
```html
<body class="dashboard-page full-width-layout page-ai-compliance">
```

Esto activa automaticamente el page template `page--dashboard.html.twig` (por la clase `dashboard-page`) y permite CSS especifico via `.page-ai-compliance`.

**IMPORTANTE:** NO usar `attributes.addClass()` en templates Twig. BODY-CLASS-001 exige `hook_preprocess_html()` exclusivamente.

### 5.4 Page Templates Zero-Region

**Patron establecido** (`page--dashboard.html.twig:1-75`):
```twig
{# Page template limpio para rutas de dashboard.
   SIN regiones ni bloques de Drupal — Zero Region Policy. #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/scroll-animations') }}

<div class="page-wrapper page-wrapper--clean page-wrapper--premium">
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    'site_name': site_name, ...
  } only %}

  <main class="main-content main-content--full" role="main">
    {% if dashboard_messages %}
      <div class="highlighted container">{{ dashboard_messages }}</div>
    {% endif %}
    <div class="main-content__inner main-content__inner--full">
      {% if dashboard_content %}{{ dashboard_content }}{% endif %}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    'site_name': site_name, ...
  } only %}
</div>
```

**Decision arquitectonica:** Los 3 dashboards IA usaran el page template `page--dashboard.html.twig` existente, que ya:
- Incluye header y footer via partials
- Usa `clean_content` (zero-region)
- Aplica `main-content--full` (full-width)
- Tiene `dashboard_messages` para mensajes del sistema

Esto se logra porque las body classes inyectadas (`dashboard-page`) activan el template suggestion `page--dashboard`.

**Si algun dashboard necesita layout especifico**, se crea un page template con mayor especificidad:
```
page--ai-compliance.html.twig  (prioridad sobre page--dashboard)
```

Pero por ahora, el template compartido es suficiente y evita duplicacion.

### 5.5 Partials Reutilizables

**Partials existentes que se reutilizan:**
- `_header.html.twig` / `_header-classic.html.twig` — navegacion principal
- `_footer.html.twig` — pie de pagina configurable desde Theme Settings
- `_responsive-image.html.twig` — imagenes responsive

**Partials nuevos a crear:**

| Partial | Ruta | Usado en |
|---------|------|----------|
| `_ai-health-indicator.html.twig` | `templates/partials/` (theme) | Dashboard autonomo, header |
| `_causal-analysis-widget.html.twig` | `templates/partials/` (theme) | Dashboard causal |
| `_voice-copilot-fab.html.twig` | `templates/partials/` (theme) | Todas las paginas (condicional) |

Cada partial es autocontenido, recibe solo variables escalares (nunca entidades), y esta documentado con bloque de comentario Twig.

---

## 6. Fase 2: Dashboard AI Compliance (GAP-L5-C)

### 6.1 SCSS Route Bundle

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/scss/routes/_ai-compliance.scss`

**Estructura BEM completa:**

```scss
/**
 * @file
 * GAP-L5-C: AI Compliance Dashboard — EU AI Act.
 *
 * BEM: .ai-compliance-dashboard__*
 * Design tokens: var(--ej-*) exclusivamente.
 * Mobile-first: breakpoints desde $breakpoint-md (768px).
 */

// ============================================================
// Dashboard Container
// ============================================================
.ai-compliance-dashboard {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1rem;

  @media (min-width: 768px) {
    padding: 2rem;
  }
}

// ============================================================
// Header
// ============================================================
.ai-compliance-dashboard__header {
  margin-bottom: 2rem;
  padding: 2rem;
  background: var(--ej-bg-surface, #ffffff);
  border-radius: var(--ej-card-radius, 12px);
  border-left: 4px solid var(--ej-color-warning, #F59E0B);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.ai-compliance-dashboard__title {
  font-family: var(--ej-font-headings, 'Outfit', sans-serif);
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--ej-color-headings, #1a1a2e);
  margin: 0 0 0.5rem;
}

.ai-compliance-dashboard__subtitle {
  color: var(--ej-color-muted, #6b7280);
  font-size: 0.95rem;
  margin: 0;

  strong {
    color: var(--ej-color-danger, #EF4444);
    font-weight: 600;
  }
}

// ============================================================
// Sections
// ============================================================
.ai-compliance-dashboard__section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: var(--ej-bg-surface, #ffffff);
  border-radius: var(--ej-card-radius, 12px);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.ai-compliance-dashboard__section-title {
  font-family: var(--ej-font-headings, 'Outfit', sans-serif);
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ej-color-headings, #1a1a2e);
  margin: 0 0 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--ej-card-border, #e5e7eb);
}

// ============================================================
// Risk Matrix Table
// ============================================================
.ai-compliance-dashboard__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;

  th {
    background: var(--ej-bg-body, #f9fafb);
    color: var(--ej-color-muted, #6b7280);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 2px solid var(--ej-card-border, #e5e7eb);
  }

  td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--ej-card-border, #e5e7eb);
    color: var(--ej-color-body, #374151);
    vertical-align: middle;
  }

  tbody tr:hover {
    background: var(--ej-bg-body, #f9fafb);
  }
}

// ============================================================
// Risk Badges
// ============================================================
.ai-compliance-dashboard__risk-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;

  &--minimal {
    background: #ECFDF5;
    color: #065F46;
  }

  &--limited {
    background: #FEF3C7;
    color: #92400E;
  }

  &--high {
    background: #FEE2E2;
    color: #991B1B;
  }

  &--unacceptable {
    background: #1F2937;
    color: #F9FAFB;
  }
}

// ============================================================
// Status Indicators
// ============================================================
.ai-compliance-dashboard__status {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8125rem;
  font-weight: 500;

  &--required {
    color: var(--ej-color-danger, #EF4444);
  }

  &--not-required {
    color: var(--ej-color-muted, #9CA3AF);
  }
}

// ============================================================
// Risk Assessments Cards
// ============================================================
.ai-compliance-dashboard__assessments {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;

  @media (min-width: 768px) {
    grid-template-columns: repeat(2, 1fr);
  }

  @media (min-width: 1024px) {
    grid-template-columns: repeat(3, 1fr);
  }
}

.ai-compliance-dashboard__assessment {
  padding: 1.25rem;
  border-radius: var(--ej-card-radius, 12px);
  border: 1px solid var(--ej-card-border, #e5e7eb);
  background: var(--ej-card-bg, #ffffff);
  transition: box-shadow 0.2s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  &--high {
    border-left: 4px solid #EF4444;
  }

  &--limited {
    border-left: 4px solid #F59E0B;
  }

  &--minimal {
    border-left: 4px solid #10B981;
  }
}

.ai-compliance-dashboard__assessment-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ej-color-headings, #1a1a2e);
  margin: 0 0 0.75rem;
}

.ai-compliance-dashboard__assessment-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: var(--ej-color-muted, #6b7280);
}

// ============================================================
// Action Classification Reference (2 columns)
// ============================================================
.ai-compliance-dashboard__reference {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;

  @media (min-width: 768px) {
    grid-template-columns: 1fr 1fr;
  }
}

.ai-compliance-dashboard__reference-column {
  padding: 1rem;
  border-radius: var(--ej-card-radius, 12px);
  background: var(--ej-bg-body, #f9fafb);
}

.ai-compliance-dashboard__reference-title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0 0 0.75rem;

  &--high {
    color: #DC2626;
  }

  &--limited {
    color: #D97706;
  }
}

.ai-compliance-dashboard__reference-list {
  list-style: none;
  padding: 0;
  margin: 0;

  li {
    padding: 0.375rem 0;
    font-size: 0.875rem;
    color: var(--ej-color-body, #374151);
    border-bottom: 1px solid var(--ej-card-border, #e5e7eb);

    &:last-child {
      border-bottom: none;
    }
  }
}

// ============================================================
// Empty State
// ============================================================
.ai-compliance-dashboard__empty {
  color: var(--ej-color-muted, #9CA3AF);
  font-style: italic;
  text-align: center;
  padding: 2rem;
}

// ============================================================
// Responsive Table Overflow
// ============================================================
.ai-compliance-dashboard__matrix {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
```

### 6.2 Page Template

El dashboard de compliance usa el page template compartido `page--dashboard.html.twig` gracias a la body class `dashboard-page` inyectada en hook_preprocess_html(). No se necesita un page template especifico.

### 6.3 Controller — Attached Library

**Archivo a modificar:** `jaraba_ai_agents/src/Controller/AiComplianceDashboardController.php`

**Cambio:** Anadir `#attached['library']` al render array devuelto por `dashboard()`:

```php
return [
  '#theme' => 'ai_compliance_dashboard',
  '#attached' => [
    'library' => ['ecosistema_jaraba_theme/route-ai-compliance'],
  ],
  '#risk_matrix' => $riskMatrix,
  '#risk_assessments' => $assessmentData,
  '#high_risk_actions' => $highRiskActions,
  '#limited_risk_actions' => $limitedRiskActions,
  '#eu_ai_act_deadline' => '2026-08-02',
];
```

### 6.4 Integracion con Theme Settings

Los colores del dashboard se heredan automaticamente de las CSS Custom Properties inyectadas por `hook_preprocess_html()`:
- `--ej-bg-surface` → fondo de cards
- `--ej-color-headings` → titulos
- `--ej-card-radius` → border radius
- `--ej-card-border` → bordes

El administrador puede cambiar estos valores desde **Apariencia > Ecosistema Jaraba Theme > Configuracion** sin tocar codigo.

---

## 7. Fase 3: Dashboard Autonomous Agents (GAP-L5-F/G)

### 7.1 SCSS Route Bundle

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/scss/routes/_autonomous-agents.scss`

**Estructura BEM completa:**

```scss
/**
 * @file
 * GAP-L5-F/G: Autonomous Agents & Self-Healing Dashboard.
 *
 * BEM: .autonomous-agents-dashboard__*
 * Design tokens: var(--ej-*) exclusivamente.
 * Mobile-first: breakpoints desde 768px.
 */

// ============================================================
// Dashboard Container
// ============================================================
.autonomous-agents-dashboard {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1rem;

  @media (min-width: 768px) {
    padding: 2rem;
  }
}

// ============================================================
// Stats Grid (6 cards)
// ============================================================
.autonomous-agents-dashboard__stats {
  margin-bottom: 2rem;
}

.autonomous-agents-dashboard__section-title {
  font-family: var(--ej-font-headings, 'Outfit', sans-serif);
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ej-color-headings, #1a1a2e);
  margin: 0 0 1rem;
}

.autonomous-agents-dashboard__stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;

  @media (min-width: 768px) {
    grid-template-columns: repeat(3, 1fr);
  }

  @media (min-width: 1024px) {
    grid-template-columns: repeat(6, 1fr);
  }
}

.autonomous-agents-dashboard__stat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.375rem;
  padding: 1.25rem;
  background: var(--ej-bg-surface, #ffffff);
  border-radius: var(--ej-card-radius, 12px);
  border: 1px solid var(--ej-card-border, #e5e7eb);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
  transition: transform 0.15s ease, box-shadow 0.15s ease;

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  }

  &--active {
    border-color: #10B981;
    background: linear-gradient(135deg, #ECFDF5, var(--ej-bg-surface, #ffffff));
  }

  &--paused {
    border-color: #F59E0B;
    background: linear-gradient(135deg, #FFFBEB, var(--ej-bg-surface, #ffffff));
  }

  &--escalated {
    border-color: #EF4444;
    background: linear-gradient(135deg, #FEF2F2, var(--ej-bg-surface, #ffffff));
  }
}

.autonomous-agents-dashboard__stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--ej-color-headings, #1a1a2e);
  line-height: 1;
}

.autonomous-agents-dashboard__stat-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--ej-color-muted, #6b7280);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  text-align: center;
}

// ============================================================
// Sections
// ============================================================
.autonomous-agents-dashboard__section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: var(--ej-bg-surface, #ffffff);
  border-radius: var(--ej-card-radius, 12px);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);

  &--escalated {
    border-left: 4px solid #EF4444;
  }
}

// ============================================================
// Tables
// ============================================================
.autonomous-agents-dashboard__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;

  th {
    background: var(--ej-bg-body, #f9fafb);
    color: var(--ej-color-muted, #6b7280);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 2px solid var(--ej-card-border, #e5e7eb);
  }

  td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--ej-card-border, #e5e7eb);
    color: var(--ej-color-body, #374151);
  }

  tbody tr:hover {
    background: var(--ej-bg-body, #f9fafb);
  }
}

// ============================================================
// Severity & Outcome Indicators
// ============================================================
.autonomous-agents-dashboard__severity {
  &--critical {
    color: #DC2626;
    font-weight: 600;
  }

  &--warning {
    color: #D97706;
    font-weight: 600;
  }
}

.autonomous-agents-dashboard__outcome {
  &--success {
    color: #059669;
    font-weight: 600;
  }

  &--partial {
    color: #D97706;
    font-weight: 600;
  }

  &--failed {
    color: #DC2626;
    font-weight: 600;
  }
}

.autonomous-agents-dashboard__failure-count {
  color: #DC2626;
  font-weight: 700;
}

// ============================================================
// Empty State
// ============================================================
.autonomous-agents-dashboard__empty {
  color: var(--ej-color-muted, #9CA3AF);
  font-style: italic;
  text-align: center;
  padding: 2rem;
}
```

### 7.2 Page Template

Usa el page template compartido `page--dashboard.html.twig` (activado por body class `dashboard-page`).

### 7.3 Controller — Attached Library

**Archivo a modificar:** `jaraba_ai_agents/src/Controller/AutonomousAgentDashboardController.php`

**Cambio en metodo `dashboard()`:**
```php
return [
  '#theme' => 'autonomous_agents_dashboard',
  '#attached' => [
    'library' => ['ecosistema_jaraba_theme/route-autonomous-agents'],
  ],
  '#stats' => $stats,
  '#active_sessions' => $sessionData,
  '#escalated_sessions' => $escalatedData,
  '#recent_remediations' => $remediationData,
  '#agent_types' => [...],
];
```

### 7.4 Partial: Health Indicator

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_ai-health-indicator.html.twig`

**Proposito:** Componente reutilizable que muestra el health score del sistema IA. Se puede incluir en el header o en dashboards.

```twig
{#
 # Partial: AI Health Indicator.
 #
 # Muestra el health score del sistema IA como indicador visual.
 # Reutilizable en header, dashboards, y admin center.
 #
 # Variables:
 #   - health_score: int (0-100)
 #   - anomalies_count: int
 #   - last_check: string (fecha formateada)
 #
 # Uso:
 #   {% include '@ecosistema_jaraba_theme/partials/_ai-health-indicator.html.twig' with {
 #     'health_score': 85,
 #     'anomalies_count': 2,
 #     'last_check': '2026-02-27 14:30',
 #   } only %}
 #}
{% set score = health_score|default(100) %}
{% set level = score >= 80 ? 'healthy' : (score >= 50 ? 'warning' : 'critical') %}

<div class="ai-health-indicator ai-health-indicator--{{ level }}"
     title="{{ 'AI System Health: @score/100'|t({'@score': score}) }}">
  <span class="ai-health-indicator__score">{{ score }}</span>
  <span class="ai-health-indicator__label">{{ 'AI Health'|t }}</span>
  {% if anomalies_count|default(0) > 0 %}
    <span class="ai-health-indicator__badge">{{ anomalies_count }}</span>
  {% endif %}
</div>
```

### 7.5 Self-Healing Real: Implementacion de Remediaciones

**Archivo a modificar:** `jaraba_ai_agents/src/Service/AutoDiagnosticService.php`

Los 5 metodos `execute*` deben implementar acciones reales usando los servicios inyectados:

**7.5.1 executeAutoDowngrade (linea 311):**

```php
protected function executeAutoDowngrade(string $tenantId): string {
  $this->logger->notice('GAP-L5-G: Auto-downgrading tier for tenant @tenant due to high latency.', [
    '@tenant' => $tenantId,
  ]);

  if ($this->modelRouter === NULL || !method_exists($this->modelRouter, 'setForceTier')) {
    $this->logger->warning('GAP-L5-G: ModelRouter not available for auto-downgrade.');
    return 'partial';
  }

  try {
    // Forzar tier 'balanced' si estaba en 'premium', o 'fast' si estaba en 'balanced'.
    $currentTier = $this->modelRouter->getCurrentTier($tenantId);
    $newTier = match ($currentTier) {
      'premium' => 'balanced',
      'balanced' => 'fast',
      default => 'fast',
    };
    $this->modelRouter->setForceTier($tenantId, $newTier, 3600); // 1 hora
    return 'success';
  }
  catch (\Throwable $e) {
    $this->logger->error('GAP-L5-G: Auto-downgrade failed: @msg', ['@msg' => $e->getMessage()]);
    return 'failed';
  }
}
```

**Nota:** Si `ModelRouterService` no tiene `setForceTier()`, se debe implementar. El metodo acepta: tenantId, tier, duracion en segundos. Usa `State API` para almacenar el override temporal.

**7.5.2 executeAutoRotate (linea 333):**

```php
protected function executeAutoRotate(string $tenantId): string {
  $this->logger->notice('GAP-L5-G: Auto-rotating provider for tenant @tenant.', ['@tenant' => $tenantId]);

  if ($this->providerFallback === NULL || !method_exists($this->providerFallback, 'rotateProvider')) {
    $this->logger->warning('GAP-L5-G: ProviderFallback not available for auto-rotate.');
    return 'partial';
  }

  try {
    $this->providerFallback->rotateProvider($tenantId);
    return 'success';
  }
  catch (\Throwable $e) {
    $this->logger->error('GAP-L5-G: Auto-rotate failed: @msg', ['@msg' => $e->getMessage()]);
    return 'failed';
  }
}
```

**7.5.3 executeAutoWarmCache (linea 343):**

```php
protected function executeAutoWarmCache(string $tenantId): string {
  if ($this->semanticCache === NULL || !method_exists($this->semanticCache, 'warmUpFrequentQueries')) {
    return 'partial';
  }

  $this->logger->notice('GAP-L5-G: Auto-warming cache for tenant @tenant.', ['@tenant' => $tenantId]);

  try {
    $warmed = $this->semanticCache->warmUpFrequentQueries($tenantId, 20); // top 20 queries
    $this->logger->info('GAP-L5-G: Warmed @count cache entries for tenant @tenant.', [
      '@count' => $warmed,
      '@tenant' => $tenantId,
    ]);
    return 'success';
  }
  catch (\Throwable $e) {
    $this->logger->error('GAP-L5-G: Cache warm failed: @msg', ['@msg' => $e->getMessage()]);
    return 'failed';
  }
}
```

**7.5.4 executeAutoRefreshPrompt (linea 323):**

```php
protected function executeAutoRefreshPrompt(string $tenantId): string {
  $this->logger->notice('GAP-L5-G: Auto-refreshing prompts for tenant @tenant.', ['@tenant' => $tenantId]);

  try {
    // Buscar la ultima mejora de prompt aprobada para rollback.
    $storage = $this->entityTypeManager->getStorage('prompt_improvement');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'applied')
      ->sort('changed', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      $this->logger->info('GAP-L5-G: No applied prompt improvements to rollback.');
      return 'partial';
    }

    $improvement = $storage->load(reset($ids));
    if ($improvement) {
      $improvement->set('status', 'rolled_back');
      $improvement->save();
      $this->logger->info('GAP-L5-G: Rolled back prompt improvement @id.', ['@id' => $improvement->id()]);
      return 'success';
    }

    return 'partial';
  }
  catch (\Throwable $e) {
    $this->logger->error('GAP-L5-G: Prompt refresh failed: @msg', ['@msg' => $e->getMessage()]);
    return 'failed';
  }
}
```

**7.5.5 executeAutoThrottle (linea 357):**

```php
protected function executeAutoThrottle(string $tenantId): string {
  $this->logger->notice('GAP-L5-G: Auto-throttling tenant @tenant.', ['@tenant' => $tenantId]);

  try {
    // Usar State API para marcar throttle temporal (1 hora).
    $state = \Drupal::state();
    $state->set('jaraba_ai_agents.throttle.' . $tenantId, [
      'enabled' => TRUE,
      'max_requests_per_minute' => 5, // Limitar a 5 req/min
      'expires' => time() + 3600,
    ]);
    return 'success';
  }
  catch (\Throwable $e) {
    $this->logger->error('GAP-L5-G: Auto-throttle failed: @msg', ['@msg' => $e->getMessage()]);
    return 'failed';
  }
}
```

### 7.6 Autonomous Tasks: Logica Real por Tipo de Agente

**Archivo a modificar:** `jaraba_ai_agents/src/Service/AutonomousAgentService.php`

**7.6.1 taskContentCurator (linea 384):**

```php
protected function taskContentCurator(object $session): array {
  $tenantId = $session->get('tenant_id')->value ?? '';

  try {
    // Consultar articulos del tenant ordenados por views (menos populares = oportunidad).
    $storage = $this->entityTypeManager->getStorage('content_article');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('status', 1)
      ->sort('views_count', 'ASC')
      ->range(0, 5)
      ->execute();

    $suggestions = [];
    if (!empty($ids)) {
      $articles = $storage->loadMultiple($ids);
      foreach ($articles as $article) {
        $suggestions[] = [
          'entity_id' => $article->id(),
          'title' => $article->label(),
          'views' => $article->get('views_count')->value ?? 0,
          'action' => 'refresh_content',
          'reason' => 'Low engagement — consider updating or promoting.',
        ];
      }
    }

    return [
      'success' => TRUE,
      'data' => ['task' => 'content_curator', 'suggestions' => $suggestions],
      'cost' => 0.0,
    ];
  }
  catch (\Throwable $e) {
    return ['success' => FALSE, 'error' => $e->getMessage(), 'data' => [], 'cost' => 0.0];
  }
}
```

**7.6.2 taskKBMaintainer (linea 398):**

```php
protected function taskKBMaintainer(object $session): array {
  $tenantId = $session->get('tenant_id')->value ?? '';

  try {
    // Buscar entradas de knowledge base que no se han actualizado en 30+ dias.
    $threshold = strtotime('-30 days');
    $staleEntries = [];

    if (\Drupal::hasService('entity_type.manager')) {
      $entityTypeManager = \Drupal::entityTypeManager();
      if ($entityTypeManager->hasDefinition('knowledge_entry')) {
        $storage = $entityTypeManager->getStorage('knowledge_entry');
        $ids = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenantId)
          ->condition('changed', $threshold, '<')
          ->sort('changed', 'ASC')
          ->range(0, 10)
          ->execute();

        if (!empty($ids)) {
          $entries = $storage->loadMultiple($ids);
          foreach ($entries as $entry) {
            $staleEntries[] = [
              'entity_id' => $entry->id(),
              'title' => $entry->label() ?? 'Entry #' . $entry->id(),
              'last_updated' => date('Y-m-d', $entry->getChangedTime()),
              'days_stale' => (int) ((time() - $entry->getChangedTime()) / 86400),
              'action' => 'flag_for_review',
            ];
          }
        }
      }
    }

    return [
      'success' => TRUE,
      'data' => ['task' => 'kb_maintainer', 'stale_entries' => $staleEntries],
      'cost' => 0.0,
    ];
  }
  catch (\Throwable $e) {
    return ['success' => FALSE, 'error' => $e->getMessage(), 'data' => [], 'cost' => 0.0];
  }
}
```

**7.6.3 taskChurnPrevention (linea 412):**

```php
protected function taskChurnPrevention(object $session): array {
  $tenantId = $session->get('tenant_id')->value ?? '';

  try {
    // Identificar usuarios que no han tenido actividad en 14+ dias.
    $inactiveThreshold = strtotime('-14 days');
    $atRiskUsers = [];

    $database = \Drupal::database();
    // Consultar ultimo acceso de usuarios del tenant.
    $query = $database->select('users_field_data', 'u');
    $query->fields('u', ['uid', 'name', 'mail', 'access']);
    $query->condition('u.access', $inactiveThreshold, '<');
    $query->condition('u.access', 0, '>'); // Excluir usuarios que nunca accedieron.
    $query->condition('u.status', 1); // Solo activos.
    $query->orderBy('u.access', 'ASC');
    $query->range(0, 20);
    $results = $query->execute();

    foreach ($results as $row) {
      $atRiskUsers[] = [
        'uid' => $row->uid,
        'name' => $row->name,
        'last_access' => date('Y-m-d', $row->access),
        'days_inactive' => (int) ((time() - $row->access) / 86400),
        'risk_level' => ($row->access < strtotime('-30 days')) ? 'high' : 'medium',
        'suggested_action' => 'engagement_email',
      ];
    }

    return [
      'success' => TRUE,
      'data' => ['task' => 'churn_prevention', 'at_risk_users' => $atRiskUsers],
      'cost' => 0.0,
    ];
  }
  catch (\Throwable $e) {
    return ['success' => FALSE, 'error' => $e->getMessage(), 'data' => [], 'cost' => 0.0];
  }
}
```

---

## 8. Fase 4: Dashboard Causal Analytics (GAP-L5-H/I)

### 8.1 SCSS Route Bundle

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/scss/routes/_causal-analytics.scss`

```scss
/**
 * @file
 * GAP-L5-H/I: Causal Analytics & Federated Insights Dashboard.
 *
 * BEM: .causal-analytics-dashboard__*
 * Design tokens: var(--ej-*) exclusivamente.
 * Mobile-first.
 */

// ============================================================
// Dashboard Container
// ============================================================
.causal-analytics-dashboard {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1rem;

  @media (min-width: 768px) {
    padding: 2rem;
  }
}

// ============================================================
// Sections
// ============================================================
.causal-analytics-dashboard__section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: var(--ej-bg-surface, #ffffff);
  border-radius: var(--ej-card-radius, 12px);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.causal-analytics-dashboard__section-title {
  font-family: var(--ej-font-headings, 'Outfit', sans-serif);
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ej-color-headings, #1a1a2e);
  margin: 0 0 0.5rem;
}

.causal-analytics-dashboard__description {
  color: var(--ej-color-muted, #6b7280);
  font-size: 0.875rem;
  margin: 0 0 1.5rem;
}

// ============================================================
// Insight Cards Grid
// ============================================================
.causal-analytics-dashboard__insights-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;

  @media (min-width: 768px) {
    grid-template-columns: repeat(2, 1fr);
  }

  @media (min-width: 1200px) {
    grid-template-columns: repeat(3, 1fr);
  }
}

.causal-analytics-dashboard__insight-card {
  padding: 1.25rem;
  border-radius: var(--ej-card-radius, 12px);
  border: 1px solid var(--ej-card-border, #e5e7eb);
  background: var(--ej-card-bg, #ffffff);
  transition: box-shadow 0.2s ease, transform 0.15s ease;

  &:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
  }

  &--warning {
    border-color: #F59E0B;
    opacity: 0.7;
  }
}

.causal-analytics-dashboard__insight-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.causal-analytics-dashboard__insight-type {
  display: inline-block;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: var(--ej-color-primary, #4F46E5);
  color: #ffffff;
}

.causal-analytics-dashboard__insight-vertical {
  font-size: 0.75rem;
  color: var(--ej-color-muted, #9CA3AF);
}

.causal-analytics-dashboard__insight-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ej-color-headings, #1a1a2e);
  margin: 0 0 0.5rem;
  line-height: 1.3;
}

.causal-analytics-dashboard__insight-summary {
  font-size: 0.875rem;
  color: var(--ej-color-body, #374151);
  margin: 0 0 0.75rem;
  line-height: 1.5;
}

.causal-analytics-dashboard__insight-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
  color: var(--ej-color-muted, #6b7280);
}

// ============================================================
// Table
// ============================================================
.causal-analytics-dashboard__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
  overflow-x: auto;

  th {
    background: var(--ej-bg-body, #f9fafb);
    color: var(--ej-color-muted, #6b7280);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.05em;
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 2px solid var(--ej-card-border, #e5e7eb);
    white-space: nowrap;
  }

  td {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--ej-card-border, #e5e7eb);
    color: var(--ej-color-body, #374151);
  }

  tbody tr:hover {
    background: var(--ej-bg-body, #f9fafb);
  }
}

.causal-analytics-dashboard__query-cell {
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

// ============================================================
// Query Form
// ============================================================
.causal-analytics-dashboard__query-form {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: var(--ej-bg-surface, #ffffff);
  border-radius: var(--ej-card-radius, 12px);
  border: 2px dashed var(--ej-color-primary, #4F46E5);
}

.causal-analytics-dashboard__query-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--ej-input-border, #d1d5db);
  border-radius: var(--ej-btn-radius, 8px);
  font-size: 1rem;
  color: var(--ej-color-body, #374151);
  background: var(--ej-input-bg, #ffffff);
  margin-bottom: 1rem;
  transition: border-color 0.15s ease;

  &:focus {
    outline: none;
    border-color: var(--ej-input-focus, #4F46E5);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
  }

  &::placeholder {
    color: var(--ej-color-muted, #9CA3AF);
  }
}

.causal-analytics-dashboard__query-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.causal-analytics-dashboard__query-type-btn {
  padding: 0.5rem 1rem;
  border: 1px solid var(--ej-card-border, #e5e7eb);
  border-radius: var(--ej-btn-radius, 8px);
  background: var(--ej-bg-surface, #ffffff);
  color: var(--ej-color-body, #374151);
  font-size: 0.8125rem;
  cursor: pointer;
  transition: all 0.15s ease;

  &:hover,
  &--active {
    background: var(--ej-color-primary, #4F46E5);
    color: #ffffff;
    border-color: var(--ej-color-primary, #4F46E5);
  }
}

// ============================================================
// Empty State
// ============================================================
.causal-analytics-dashboard__empty {
  color: var(--ej-color-muted, #9CA3AF);
  font-style: italic;
  text-align: center;
  padding: 2rem;
}
```

### 8.2 Page Template

Usa el template compartido `page--dashboard.html.twig` (activado por body class `dashboard-page`).

### 8.3 Controller — Attached Library + Query Form

**Archivo a modificar:** `jaraba_ai_agents/src/Controller/CausalAnalyticsDashboardController.php`

**Cambios:**
1. Adjuntar libreria
2. Pasar datos de formulario de query al template

```php
public function dashboard(): array {
  $insights = $this->federatedInsight->getInsights('', 10);
  $analyses = $this->causalAnalytics->getRecentAnalyses('', 10);

  // ... (logica existente de $insightData y $analysisData) ...

  return [
    '#theme' => 'causal_analytics_dashboard',
    '#attached' => [
      'library' => ['ecosistema_jaraba_theme/route-causal-analytics'],
      'drupalSettings' => [
        'jarabaCausalAnalytics' => [
          'apiUrl' => Url::fromRoute('jaraba_ai_agents.api.causal_analytics.query')->toString(),
          'csrfToken' => \Drupal::csrfToken()->get('jaraba_ai_agents.api.causal_analytics.query'),
        ],
      ],
    ],
    '#insights' => $insightData,
    '#analyses' => $analysisData,
    '#k_anonymity_threshold' => FederatedInsightService::K_ANONYMITY_THRESHOLD,
    '#query_types' => [
      'diagnostic' => $this->t('Diagnostic'),
      'counterfactual' => $this->t('Counterfactual'),
      'predictive' => $this->t('Predictive'),
      'prescriptive' => $this->t('Prescriptive'),
    ],
  ];
}
```

### 8.4 Partial: Causal Analysis Widget

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_causal-analysis-widget.html.twig`

```twig
{#
 # Partial: Causal Analysis Query Widget.
 #
 # Formulario interactivo para lanzar queries causales.
 # Integrado en el dashboard de Causal Analytics.
 #
 # Variables:
 #   - query_types: { machine_name: label }
 #   - placeholder_text: string (placeholder del input)
 #
 # Uso:
 #   {% include '@ecosistema_jaraba_theme/partials/_causal-analysis-widget.html.twig' with {
 #     'query_types': query_types,
 #   } only %}
 #}
<div class="causal-analytics-dashboard__query-form" id="causal-query-widget">
  <h3 class="causal-analytics-dashboard__section-title">{{ 'Ask a Causal Question'|t }}</h3>
  <input
    type="text"
    class="causal-analytics-dashboard__query-input"
    id="causal-query-input"
    placeholder="{{ 'e.g., Why did conversions drop last month?'|t }}"
    maxlength="500"
  />
  <div class="causal-analytics-dashboard__query-actions">
    {% for type, label in query_types|default({}) %}
      <button
        type="button"
        class="causal-analytics-dashboard__query-type-btn"
        data-query-type="{{ type }}"
      >
        {{ label }}
      </button>
    {% endfor %}
  </div>
</div>
```

### 8.5 API Endpoint para Queries Causales

**Ruta nueva en `jaraba_ai_agents.routing.yml`:**

```yaml
jaraba_ai_agents.api.causal_analytics.query:
  path: '/api/v1/ai/causal-analytics/query'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\CausalAnalyticsApiController::query'
  methods: [POST]
  requirements:
    _permission: 'administer ai agents'
    _csrf_request_header_token: 'TRUE'
```

**Controller nuevo:** `jaraba_ai_agents/src/Controller/CausalAnalyticsApiController.php`

```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\CausalAnalyticsService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CausalAnalyticsApiController extends ControllerBase {

  public function __construct(
    protected CausalAnalyticsService $causalAnalytics,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.causal_analytics'),
    );
  }

  public function query(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);
    $query = $data['query'] ?? '';
    $queryType = $data['query_type'] ?? 'diagnostic';
    $context = $data['context'] ?? [];
    $tenantId = $data['tenant_id'] ?? '';

    if (empty($query)) {
      return new JsonResponse(['success' => FALSE, 'error' => $this->t('Query is required.')], 400);
    }

    $result = $this->causalAnalytics->analyze($query, $queryType, $context, $tenantId);

    return new JsonResponse($result);
  }

}
```

---

## 9. Fase 5: Voice Copilot Frontend (GAP-L5-D)

### 9.1 Partial: Voice FAB Widget

**Archivo:** `web/themes/custom/ecosistema_jaraba_theme/templates/partials/_voice-copilot-fab.html.twig`

```twig
{#
 # Partial: Voice Copilot FAB (Floating Action Button).
 #
 # Boton flotante con microfono para interaccion por voz con el copiloto IA.
 # Se muestra condicionalmente: solo si voice pipeline esta habilitado.
 #
 # Variables:
 #   - voice_enabled: bool
 #   - voice_api_url: string (URL del endpoint de transcripcion)
 #
 # Uso:
 #   {% include '@ecosistema_jaraba_theme/partials/_voice-copilot-fab.html.twig' with {
 #     'voice_enabled': true,
 #     'voice_api_url': '/api/v1/voice/transcribe',
 #   } only %}
 #}
{% if voice_enabled|default(false) %}
<div class="voice-copilot-fab" id="voice-copilot-fab" aria-label="{{ 'Voice Copilot'|t }}">
  <button class="voice-copilot-fab__button" id="voice-copilot-btn"
          title="{{ 'Click to speak with AI Copilot'|t }}"
          aria-label="{{ 'Activate voice input'|t }}">
    <span class="voice-copilot-fab__icon voice-copilot-fab__icon--mic" id="voice-icon-mic">
      {# Icono microfono via jaraba_icon en PHP — aqui SVG inline como fallback #}
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/>
        <path d="M19 10v2a7 7 0 0 1-14 0v-2"/>
        <line x1="12" y1="19" x2="12" y2="23"/>
        <line x1="8" y1="23" x2="16" y2="23"/>
      </svg>
    </span>
    <span class="voice-copilot-fab__icon voice-copilot-fab__icon--stop" id="voice-icon-stop" hidden>
      <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
        <rect x="6" y="6" width="12" height="12" rx="2"/>
      </svg>
    </span>
  </button>
  <div class="voice-copilot-fab__status" id="voice-status" hidden>
    <div class="voice-copilot-fab__waveform" id="voice-waveform"></div>
    <span class="voice-copilot-fab__status-text" id="voice-status-text"></span>
  </div>
</div>
{% endif %}
```

### 9.2 JavaScript: Voice Copilot Widget

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/js/voice-copilot-widget.js`

Este archivo implementa:
1. MediaRecorder API para capturar audio
2. Envio al endpoint `/api/v1/voice/transcribe`
3. Recepcion de respuesta TTS
4. Reproduccion de audio
5. Gestion de estados: idle, recording, processing, playing, error

### 9.3 SCSS: Voice Copilot Component

**Archivo:** `web/modules/custom/ecosistema_jaraba_core/scss/_voice-copilot.scss`

**Regla FEDERATED-DESIGN-TOKENS:** Solo `var(--ej-*)`.

```scss
/**
 * @file
 * Voice Copilot FAB Component.
 *
 * BEM: .voice-copilot-fab__*
 * Posicion: fixed, bottom-right.
 * Estados: idle, recording, processing, playing, error.
 */

.voice-copilot-fab {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.voice-copilot-fab__button {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  background: var(--ej-color-primary, #4F46E5);
  color: #ffffff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
  transition: all 0.2s ease;

  &:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 16px rgba(79, 70, 229, 0.5);
  }

  &:active {
    transform: scale(0.95);
  }

  // Estado: grabando
  .voice-copilot-fab--recording & {
    background: #EF4444;
    animation: voice-pulse 1.5s ease-in-out infinite;
  }

  // Estado: procesando
  .voice-copilot-fab--processing & {
    background: var(--ej-color-warning, #F59E0B);
    cursor: wait;
  }
}

@keyframes voice-pulse {
  0%, 100% { box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4); }
  50% { box-shadow: 0 4px 24px rgba(239, 68, 68, 0.7); }
}

.voice-copilot-fab__status {
  padding: 0.5rem 1rem;
  background: var(--ej-bg-surface, #ffffff);
  border-radius: var(--ej-btn-radius, 8px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  font-size: 0.8125rem;
  color: var(--ej-color-body, #374151);
}

.voice-copilot-fab__waveform {
  height: 24px;
  display: flex;
  align-items: center;
  gap: 2px;
}
```

### 9.4 Library Declaration

En `ecosistema_jaraba_core.libraries.yml`:

```yaml
voice-copilot:
  js:
    js/voice-copilot-widget.js: { weight: 10 }
  css:
    component:
      css/voice-copilot.css: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
```

### 9.5 VoicePipelineService — Graceful Degradation

**Archivo a modificar:** `jaraba_ai_agents/src/Service/VoicePipelineService.php`

Los metodos `executeSTT()` y `executeTTS()` deben degradar gracefully en lugar de lanzar RuntimeException:

```php
protected function executeSTT(string $audioData, string $mimeType, string $language, string $provider): string {
  if (!$this->aiProvider) {
    // Graceful degradation: retornar mensaje informativo.
    throw new \RuntimeException($this->t(
      'Voice transcription requires an AI provider. Configure one at /admin/config/ai/agents.'
    ));
  }

  // Intentar usar el modulo Drupal AI para STT.
  try {
    if (method_exists($this->aiProvider, 'transcribe')) {
      return $this->aiProvider->transcribe($audioData, $mimeType, $language);
    }
  }
  catch (\Throwable $e) {
    $this->logger->warning('STT via AI provider failed: @error', ['@error' => $e->getMessage()]);
  }

  // Fallback: Indicar al usuario que configure el provider STT.
  throw new \RuntimeException(
    'STT provider "' . $provider . '" not available. The AI provider does not support transcription. '
    . 'Configure Whisper API or an STT-capable provider.'
  );
}
```

---

## 10. Fase 6: Browser Agent Observabilidad (GAP-L5-E)

### 10.1 BrowserAgentService — Graceful Degradation

**Archivo a modificar:** `jaraba_ai_agents/src/Service/BrowserAgentService.php`

El metodo `executeBrowserTask()` (linea 193) debe retornar un resultado informativo en lugar de lanzar excepcion:

```php
protected function executeBrowserTask(string $taskType, string $url, array $params): array {
  $config = $this->configFactory->get('jaraba_ai_agents.browser');
  $endpoint = $config->get('playwright_endpoint');

  if (empty($endpoint)) {
    // Graceful degradation: retornar datos parciales sin browser.
    return [
      'status' => 'unavailable',
      'message' => 'Playwright browser container not configured. Configure jaraba_ai_agents.browser.playwright_endpoint.',
      'url' => $url,
      'task_type' => $taskType,
    ];
  }

  // Cuando Playwright este configurado, ejecutar via HTTP al container Docker.
  // La implementacion real usara: curl al endpoint + parse de resultado.
  // Por ahora, retornar que el endpoint esta configurado pero la ejecucion pende de Docker.
  return [
    'status' => 'pending_docker',
    'endpoint' => $endpoint,
    'url' => $url,
    'task_type' => $taskType,
  ];
}
```

### 10.2 Configuration Form

**Se necesita:** Un formulario en `/admin/config/ai/browser-agent` para configurar:
- `enabled`: checkbox
- `playwright_endpoint`: URL del container Docker de Playwright
- `allowlist`: textarea por vertical
- `max_task_duration`: numero (segundos)

Este formulario extiende la configuracion existente de `AgentSettingsForm` o se crea como nuevo form.

---

## 11. Fase 7: Prompt Improvement Approval Flow (GAP-L5-B)

### 11.1 Approval Form y Controller

**Necesidad:** El listado de `PromptImprovement` en `/admin/content/ai/prompt-improvements` existe pero no tiene acciones. Se necesita:

1. **Columna de acciones** en el list builder de la entidad
2. **Ruta de aprobacion:** `jaraba_ai_agents.prompt_improvement.approve`
3. **Controller:** que cambie el estado de la propuesta a `approved` y aplique el prompt mejorado

**Ruta nueva:**
```yaml
jaraba_ai_agents.prompt_improvement.approve:
  path: '/admin/content/ai/prompt-improvements/{prompt_improvement}/approve'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\PromptImprovementController::approve'
    _title: 'Approve Prompt Improvement'
  requirements:
    _permission: 'manage prompt improvements'
    prompt_improvement: '\d+'

jaraba_ai_agents.prompt_improvement.reject:
  path: '/admin/content/ai/prompt-improvements/{prompt_improvement}/reject'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\PromptImprovementController::reject'
    _title: 'Reject Prompt Improvement'
  requirements:
    _permission: 'manage prompt improvements'
    prompt_improvement: '\d+'
```

### 11.2 API Endpoint

```yaml
jaraba_ai_agents.api.prompt_improvement.approve:
  path: '/api/v1/ai/prompt-improvements/{id}/approve'
  defaults:
    _controller: '\Drupal\jaraba_ai_agents\Controller\PromptImprovementApiController::approve'
  methods: [POST]
  requirements:
    _permission: 'manage prompt improvements'
    _csrf_request_header_token: 'TRUE'
    id: '\d+'
```

---

## 12. Fase 8: Integracion en Estructura de Navegacion Drupal

### 12.1 Menu Links

**Archivo:** `jaraba_ai_agents.links.menu.yml`

Anadir enlaces de menu para los dashboards IA en el menu de administracion:

```yaml
jaraba_ai_agents.ai_admin:
  title: 'AI Management'
  parent: system.admin_config
  route_name: jaraba_ai_agents.dashboard
  weight: 50

jaraba_ai_agents.compliance_link:
  title: 'AI Compliance (EU AI Act)'
  parent: jaraba_ai_agents.ai_admin
  route_name: jaraba_ai_agents.compliance_dashboard
  weight: 10

jaraba_ai_agents.autonomous_link:
  title: 'Autonomous Agents'
  parent: jaraba_ai_agents.ai_admin
  route_name: jaraba_ai_agents.autonomous_agents_dashboard
  weight: 20

jaraba_ai_agents.causal_analytics_link:
  title: 'Causal Analytics'
  parent: jaraba_ai_agents.ai_admin
  route_name: jaraba_ai_agents.causal_analytics_dashboard
  weight: 30
```

### 12.2 Field UI Tabs

Todas las entidades de GAP-L5 ya tienen tabs de Settings en `links.task.yml` (verificado):
- `entity.verification_result.settings_tab`
- `entity.prompt_improvement.settings_tab`
- `entity.ai_audit_entry.settings_tab`
- `entity.autonomous_session.settings_tab`
- `entity.remediation_log.settings_tab`
- `entity.aggregated_insight.settings_tab`
- `entity.causal_analysis.settings_tab`

Cada tab es el punto de entrada para Field UI (anadir campos, gestionar visualizacion).

### 12.3 Views Integration

Cada entidad tiene `_entity_list` en sus rutas de collection, usando el `EntityListBuilder` default de Drupal que soporta Views override. Para habilitar Views con datos reales:

```yaml
# En la anotacion de cada entidad (@ContentEntityType):
handlers = {
  "views_data" = "Drupal\views\EntityViewsData",
  "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
}
```

Esto permite crear Views personalizadas en `/admin/structure/views` sobre las tablas de estas entidades.

---

## 13. Fase 9: i18n — Textos Traducibles

**Directriz i18n-DIRECTIVE-P0:** 100% de textos traducibles.

**Verificacion a realizar:**

1. **Templates Twig:** Todos los strings visibles al usuario usan filtro `|t` o `|t({})` con placeholders.
   - Ya verificado en los 3 dashboard templates existentes.

2. **Controllers PHP:** Todos los textos usan `$this->t()` o `new TranslatableMarkup()`.
   - Ya implementado en los 3 controllers.

3. **JavaScript:** Strings visibles al usuario se pasan via `drupalSettings` (traducidos en PHP).

4. **Strings a verificar especificamente:**
   - Labels de agent_types en `AutonomousAgentDashboardController` — ya usan `$this->t()`
   - Mensajes de error en API controllers — deben usar `$this->t()`
   - Placeholders en templates (e.g., `{{ 'e.g., Why did conversions drop?'|t }}`) — correcto

---

## 14. Fase 10: Mobile-First y Responsive

**Principio:** Los SCSS especificados en este plan siguen el patron mobile-first:
- Base styles = mobile (1 columna)
- `@media (min-width: 768px)` = tablet (2 columnas)
- `@media (min-width: 1024px)` = desktop (3-6 columnas)

**Breakpoints usados (de `_variables.scss`):**
```scss
$breakpoint-sm: 576px;
$breakpoint-md: 768px;
$breakpoint-lg: 1024px;
$breakpoint-xl: 1200px;
$breakpoint-xxl: 1400px;
```

**Patrones responsive aplicados:**
- Tables: `overflow-x: auto` para scroll horizontal en mobile
- Grids: `grid-template-columns: 1fr` en mobile, `repeat(N, 1fr)` en desktop
- Voice FAB: posicion `fixed` con `bottom: 2rem; right: 2rem` (ajustable)
- Padding: `1rem` en mobile, `2rem` en desktop

---

## 15. Fase 11: Tests Funcionales de Experiencia

### Tests Nuevos Necesarios

| Test | Tipo | Que Verifica |
|------|------|-------------|
| AutoDiagnosticRealRemediationTest | Unit | Que executeAutoDowngrade() llama a modelRouter->setForceTier() |
| AutoDiagnosticRealRemediationTest | Unit | Que executeAutoRotate() llama a providerFallback->rotateProvider() |
| AutoDiagnosticRealRemediationTest | Unit | Que executeAutoThrottle() guarda state de throttle |
| AutonomousContentCuratorTest | Unit | Que taskContentCurator() consulta content_article |
| AutonomousChurnPreventionTest | Unit | Que taskChurnPrevention() consulta users inactivos |
| CausalAnalyticsApiTest | Unit | Que endpoint /api/v1/ai/causal-analytics/query acepta POST y retorna analisis |
| ComplianceDashboardLibraryTest | Functional | Que /admin/config/ai/compliance incluye CSS route-ai-compliance |
| AutonomousDashboardLibraryTest | Functional | Que /admin/config/ai/autonomous-agents incluye CSS route-autonomous-agents |
| CausalDashboardLibraryTest | Functional | Que /admin/config/ai/causal-analytics incluye CSS route-causal-analytics |
| BodyClassComplianceTest | Functional | Que body tiene clase page-ai-compliance |
| BodyClassAutonomousTest | Functional | Que body tiene clase page-autonomous-agents |
| VoicePipelineGracefulTest | Unit | Que transcribe() retorna error descriptivo cuando no hay provider |
| BrowserAgentGracefulTest | Unit | Que execute() retorna datos parciales cuando no hay Playwright |
| PromptImprovementApprovalTest | Unit | Que approve() cambia estado a 'approved' |
| HealthIndicatorPartialTest | Functional | Que el partial renderiza con score y anomalies |

---

## 16. Verificacion End-to-End

### Lista de Verificacion Manual

Para cada dashboard, verificar en `https://jaraba-saas.lndo.site/`:

**Dashboard AI Compliance (`/admin/config/ai/compliance`):**
- [ ] La pagina carga sin errores 500
- [ ] El CSS esta aplicado (cards con bordes redondeados, badges de colores)
- [ ] La tabla de Risk Matrix tiene estilos
- [ ] En mobile (< 768px) las cards se apilan en 1 columna
- [ ] El body tiene clase `page-ai-compliance`
- [ ] Los textos estan traducidos (verificar en `/es/admin/config/ai/compliance`)

**Dashboard Autonomous Agents (`/admin/config/ai/autonomous-agents`):**
- [ ] Stats grid muestra 6 cards con colores
- [ ] Tablas de sesiones activas y escaladas tienen estilos
- [ ] Seccion Self-Healing muestra remediaciones con severity coloreada
- [ ] En mobile el grid pasa de 6 a 2 columnas
- [ ] El body tiene clase `page-autonomous-agents`

**Dashboard Causal Analytics (`/admin/config/ai/causal-analytics`):**
- [ ] Insight cards muestran tipo, titulo, resumen, confianza
- [ ] Tabla de analisis causales formateada
- [ ] Widget de query visible con 4 botones de tipo
- [ ] En mobile los insight cards se apilan
- [ ] El body tiene clase `page-causal-analytics`

**Self-Healing Real:**
- [ ] Simular latencia alta → verificar que se crea RemediationLog con outcome 'success'
- [ ] Verificar que el state de throttle se guarda: `drush state:get jaraba_ai_agents.throttle.{tenant}`

**Voice FAB (si habilitado):**
- [ ] El FAB aparece en bottom-right
- [ ] Click muestra estado "recording"
- [ ] Si no hay provider, muestra mensaje de error descriptivo

### Comandos de Compilacion

```bash
# Compilar todos los route bundles nuevos
lando ssh -c "cd /app/web/themes/custom/ecosistema_jaraba_theme && \
  sass scss/routes/ai-compliance.scss css/routes/ai-compliance.css --style=compressed && \
  sass scss/routes/autonomous-agents.scss css/routes/autonomous-agents.css --style=compressed && \
  sass scss/routes/causal-analytics.scss css/routes/causal-analytics.css --style=compressed"

# Limpiar cache
lando drush cr

# Ejecutar tests
lando ssh -c "cd /app && php vendor/bin/phpunit --group=jaraba_ai_agents --testdox"
```

---

## 17. Troubleshooting

### CSS no se aplica al dashboard

**Causa probable:** Library no adjuntada en controller o no declarada en `.libraries.yml`.

**Diagnostico:**
1. Verificar que el archivo CSS compilado existe: `ls web/themes/custom/ecosistema_jaraba_theme/css/routes/ai-compliance.css`
2. Verificar que la library esta declarada en `.libraries.yml`: buscar `route-ai-compliance:`
3. Verificar que el controller adjunta la library: buscar `#attached` en el render array
4. Limpiar cache: `lando drush cr`
5. Inspeccionar HTML: buscar `<link>` con la URL del CSS

### Body class no aparece

**Causa probable:** Ruta no incluida en el array `$module_body_classes` de `hook_preprocess_html()`.

**Diagnostico:**
1. Verificar la ruta actual: `\Drupal::routeMatch()->getRouteName()`
2. Buscar la ruta en el array de `ecosistema_jaraba_theme.theme` (linea ~1896)
3. Verificar que el prefijo coincide exactamente: `jaraba_ai_agents.compliance_dashboard`

### Self-healing no ejecuta accion real

**Causa probable:** El servicio opcional no esta disponible o no tiene el metodo esperado.

**Diagnostico:**
1. Verificar que el servicio esta registrado: `lando drush debug:container jaraba_ai_agents.model_router`
2. Verificar que el metodo existe: `grep -n 'setForceTier' web/modules/custom/jaraba_ai_agents/src/Service/ModelRouterService.php`
3. Si el metodo no existe, hay que implementarlo (ver seccion 7.5.1)

### Template no renderiza variables

**Causa probable:** Variable pasada como `#variable` en render array pero referenciada sin `#` en template.

**Diagnostico:** En hook_theme(), verificar que las variables estan declaradas. En el template, usar `{{ variable }}` sin `#`.

---

## 18. Referencias Cruzadas

| Documento | Ubicacion | Relacion |
|-----------|-----------|---------|
| Plan Elevacion IA Nivel 5 | `docs/implementacion/2026-02-27_Plan_Implementacion_IA_Nivel5_Transformacional_v1.md` | Plan original de los 9 GAPs (backend) |
| Arquitectura Theming Master | `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | Patron 5-layer CSS Custom Properties |
| Directrices y Aprendizajes | `docs/07_DIRECTRICES_APRENDIZAJES_CLAUDE.md` | 30+ directrices referenciadas |
| Flujo de Trabajo | `docs/01_FLUJO_TRABAJO_CLAUDE.md` | Reglas de workflow |
| Arquitectura IA Nivel 5 | `docs/arquitectura/2026-02-26_arquitectura_elevacion_ia_nivel5.md` | Diagramas y decisiones tecnicas |
| Auditoria IA Clase Mundial | `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md` | Benchmark y gaps identificados |
| Plan Elevacion IA v2 | `docs/implementacion/2026-02-26_Plan_Elevacion_IA_Clase_Mundial_v2.md` | Iteracion previa del plan |

### Archivos Clave a Modificar

| Archivo | Modificaciones |
|---------|---------------|
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme` | +3 body classes en `$module_body_classes` |
| `ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml` | +3 library declarations |
| `jaraba_ai_agents/src/Controller/AiComplianceDashboardController.php` | +`#attached['library']` |
| `jaraba_ai_agents/src/Controller/AutonomousAgentDashboardController.php` | +`#attached['library']` |
| `jaraba_ai_agents/src/Controller/CausalAnalyticsDashboardController.php` | +`#attached['library']` + drupalSettings |
| `jaraba_ai_agents/src/Service/AutoDiagnosticService.php` | 5 execute* metodos con logica real |
| `jaraba_ai_agents/src/Service/AutonomousAgentService.php` | 3 task* metodos con logica real |
| `jaraba_ai_agents/src/Service/VoicePipelineService.php` | Graceful degradation en STT/TTS |
| `jaraba_ai_agents/src/Service/BrowserAgentService.php` | Graceful degradation en executeBrowserTask |
| `jaraba_ai_agents/jaraba_ai_agents.routing.yml` | +API causal query, +prompt improvement approve/reject |

### Archivos Nuevos a Crear

| Archivo | Tipo |
|---------|------|
| `scss/routes/_ai-compliance.scss` | SCSS Component |
| `scss/routes/ai-compliance.scss` | SCSS Entry Point |
| `scss/routes/_autonomous-agents.scss` | SCSS Component |
| `scss/routes/autonomous-agents.scss` | SCSS Entry Point |
| `scss/routes/_causal-analytics.scss` | SCSS Component |
| `scss/routes/causal-analytics.scss` | SCSS Entry Point |
| `templates/partials/_ai-health-indicator.html.twig` | Twig Partial |
| `templates/partials/_causal-analysis-widget.html.twig` | Twig Partial |
| `templates/partials/_voice-copilot-fab.html.twig` | Twig Partial |
| `ecosistema_jaraba_core/js/voice-copilot-widget.js` | JavaScript |
| `ecosistema_jaraba_core/scss/_voice-copilot.scss` | SCSS Component |
| `jaraba_ai_agents/src/Controller/CausalAnalyticsApiController.php` | PHP Controller |
| `jaraba_ai_agents/src/Controller/PromptImprovementController.php` | PHP Controller |

---

## Resumen de Estimacion por Fase

| Fase | Descripcion | Horas Estimadas |
|------|------------|----------------|
| Fase 1 | Infraestructura SCSS + Templates + Body Classes + Libraries | 15-20h |
| Fase 2 | Dashboard AI Compliance (SCSS + Library + Controller) | 10-15h |
| Fase 3 | Dashboard Autonomous + Self-Healing real + Tasks reales | 40-60h |
| Fase 4 | Dashboard Causal + Query Widget + API | 25-35h |
| Fase 5 | Voice FAB + JS Widget + SCSS + Graceful Degradation | 30-40h |
| Fase 6 | Browser Agent Graceful Degradation + Config Form | 10-15h |
| Fase 7 | Prompt Improvement Approval Flow | 15-20h |
| Fase 8 | Navegacion Drupal (Menus + Field UI + Views) | 10-15h |
| Fase 9 | i18n Verificacion completa | 5-8h |
| Fase 10 | Mobile-First responsive testing | 10-15h |
| Fase 11 | Tests funcionales | 15-20h |
| **TOTAL** | | **185-263h** |

---

*Documento generado por el equipo de ingenieria. Version 1.0.0. Fecha: 2026-02-27.*
