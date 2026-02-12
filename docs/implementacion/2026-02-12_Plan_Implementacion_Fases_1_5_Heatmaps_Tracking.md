# Plan de Implementación — Fases 1-5: Heatmaps Nativos + Tracking Architecture

**Fecha de creación:** 2026-02-12 22:30
**Última actualización:** 2026-02-12 22:30
**Autor:** Claude Opus 4.6 — Arquitecto SaaS Senior
**Versión:** 1.0.0
**Roles:** Arquitecto SaaS, Ingeniero Drupal Senior, Ingeniero UX Senior, Desarrollador Frontend Senior, Diseñador de Theming Senior
**Precedente:** `2026-02-12_Plan_Cierre_Gaps_Specs_20260130_Heatmaps_Tracking_Preview.md` (Fase 0 completada)
**Especificaciones de referencia:**
- `20260130a` — 180_Platform_Native_Heatmaps_v1 (Fases 1, 2, 3)
- `20260130b` — 178_Platform_Native_Tracking_Architecture_v1 (Fases 4, 5)

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado tras Fase 0 — Punto de Partida](#2-estado-tras-fase-0--punto-de-partida)
3. [Directrices de Obligado Cumplimiento — Checklist Rápido](#3-directrices-de-obligado-cumplimiento--checklist-rápido)
4. [Fase 1 — Heatmap: QueueWorker + ScreenshotService](#4-fase-1--heatmap-queueworker--screenshotservice)
   - 4.1 [Objetivo y Alcance](#41-objetivo-y-alcance)
   - 4.2 [Tabla de Correspondencia con Spec 20260130a](#42-tabla-de-correspondencia-con-spec-20260130a)
   - 4.3 [Tarea 1.1 — HeatmapEventProcessor QueueWorker](#43-tarea-11--heatmapeventprocessor-queueworker)
   - 4.4 [Tarea 1.2 — Modificar HeatmapCollectorService para encolar](#44-tarea-12--modificar-heatmapcollectorservice-para-encolar)
   - 4.5 [Tarea 1.3 — HeatmapScreenshotService](#45-tarea-13--heatmapscreenshotservice)
   - 4.6 [Tarea 1.4 — Rutas REST para Screenshots](#46-tarea-14--rutas-rest-para-screenshots)
   - 4.7 [Tarea 1.5 — Actualizar services.yml](#47-tarea-15--actualizar-servicesyml)
   - 4.8 [Tarea 1.6 — package.json y SCSS base](#48-tarea-16--packagejson-y-scss-base)
   - 4.9 [Tarea 1.7 — Tests unitarios](#49-tarea-17--tests-unitarios)
   - 4.10 [Verificación y Validación Fase 1](#410-verificación-y-validación-fase-1)
5. [Fase 2 — Heatmap: Automatización con hook_cron](#5-fase-2--heatmap-automatización-con-hook_cron)
   - 5.1 [Objetivo y Alcance](#51-objetivo-y-alcance)
   - 5.2 [Tabla de Correspondencia con Spec 20260130a](#52-tabla-de-correspondencia-con-spec-20260130a)
   - 5.3 [Tarea 2.1 — Agregación Diaria](#53-tarea-21--agregación-diaria)
   - 5.4 [Tarea 2.2 — Cleanup Semanal](#54-tarea-22--cleanup-semanal)
   - 5.5 [Tarea 2.3 — Detección de Anomalías](#55-tarea-23--detección-de-anomalías)
   - 5.6 [Tarea 2.4 — Tests unitarios de cron](#56-tarea-24--tests-unitarios-de-cron)
6. [Fase 3 — Heatmap: Dashboard Frontend Drupal Nativo](#6-fase-3--heatmap-dashboard-frontend-drupal-nativo)
   - 6.1 [Objetivo y Alcance](#61-objetivo-y-alcance)
   - 6.2 [Arquitectura de 3 Capas (Zero Region)](#62-arquitectura-de-3-capas-zero-region)
   - 6.3 [Tarea 3.1 — HeatmapDashboardController](#63-tarea-31--heatmapdashboardcontroller)
   - 6.4 [Tarea 3.2 — Template Principal heatmap-analytics-dashboard.html.twig](#64-tarea-32--template-principal)
   - 6.5 [Tarea 3.3 — Parciales Twig Reutilizables](#65-tarea-33--parciales-twig-reutilizables)
   - 6.6 [Tarea 3.4 — JavaScript heatmap-dashboard.js](#66-tarea-34--javascript-heatmap-dashboardjs)
   - 6.7 [Tarea 3.5 — SCSS del Dashboard](#67-tarea-35--scss-del-dashboard)
   - 6.8 [Tarea 3.6 — Página Twig Limpia + hook_preprocess_html()](#68-tarea-36--página-twig-limpia--hook_preprocess_html)
   - 6.9 [Tarea 3.7 — Library, Ruta y Navegación Admin](#69-tarea-37--library-ruta-y-navegación-admin)
   - 6.10 [Tarea 3.8 — Compilación SCSS y Verificación Visual](#610-tarea-38--compilación-scss-y-verificación-visual)
7. [Fase 4 — Tracking: hook_cron para Analytics, Pixels y A/B Testing](#7-fase-4--tracking-hook_cron-para-analytics-pixels-y-ab-testing)
   - 7.1 [Objetivo y Alcance](#71-objetivo-y-alcance)
   - 7.2 [Tabla de Correspondencia con Spec 20260130b](#72-tabla-de-correspondencia-con-spec-20260130b)
   - 7.3 [Tarea 4.1 — jaraba_analytics hook_cron](#73-tarea-41--jaraba_analytics-hook_cron)
   - 7.4 [Tarea 4.2 — jaraba_pixels hook_cron](#74-tarea-42--jaraba_pixels-hook_cron)
   - 7.5 [Tarea 4.3 — jaraba_ab_testing hook_cron](#75-tarea-43--jaraba_ab_testing-hook_cron)
   - 7.6 [Tarea 4.4 — Tests unitarios de cron](#76-tarea-44--tests-unitarios-de-cron)
8. [Fase 5 — Tracking: PixelHealthCheckService + ExperimentOrchestratorService](#8-fase-5--tracking-pixelhealthcheckservice--experimentorchestratorservice)
   - 8.1 [Objetivo y Alcance](#81-objetivo-y-alcance)
   - 8.2 [Tabla de Correspondencia con Spec 20260130b](#82-tabla-de-correspondencia-con-spec-20260130b)
   - 8.3 [Tarea 5.1 — PixelHealthCheckService](#83-tarea-51--pixelhealthcheckservice)
   - 8.4 [Tarea 5.2 — ExperimentOrchestratorService](#84-tarea-52--experimentorchestratorservice)
   - 8.5 [Tarea 5.3 — Notificaciones por Email](#85-tarea-53--notificaciones-por-email)
   - 8.6 [Tarea 5.4 — Tests unitarios](#86-tarea-54--tests-unitarios)
9. [Tabla de Correspondencia Global — Specs 20260130a + 20260130b](#9-tabla-de-correspondencia-global--specs-20260130a--20260130b)
10. [Cumplimiento de Directrices por Fase](#10-cumplimiento-de-directrices-por-fase)
11. [Aprendizajes Críticos Aplicados](#11-aprendizajes-críticos-aplicados)
12. [Árbol de Dependencias entre Fases](#12-árbol-de-dependencias-entre-fases)
13. [Estimaciones y Roadmap](#13-estimaciones-y-roadmap)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este documento detalla el plan de implementación para las **Fases 1 a 5** del cierre de gaps de las especificaciones técnicas 20260130a (Native Heatmaps) y 20260130b (Native Tracking Architecture). La **Fase 0 (Premium Preview SCSS)** se completó exitosamente con la adición de la variante `--light-green` y 7 modificadores de color de icono + 3 alias legacy en `_features.scss`.

| Métrica | Valor |
|---------|-------|
| Fases cubiertas | 5 (Fase 1 a Fase 5) |
| Módulos afectados | 4 (`jaraba_heatmap`, `jaraba_analytics`, `jaraba_pixels`, `jaraba_ab_testing`) |
| Archivos nuevos estimados | ~25 |
| Archivos modificados estimados | ~15 |
| Horas estimadas totales | 45.5-63h |
| Specs de referencia | 20260130a (§4.1, §6.1, §7.2, §8-10, §11), 20260130b (§8.1, §8.3, §8.4) |

**Principios rectores que rigen TODA la implementación:**

> 1. **SCSS**: Solo CSS Custom Properties `var(--ej-*, $fallback)`. Nunca variables SCSS locales para tokens. Dart Sass moderno (`@use 'sass:color'`, `color.scale()`).
> 2. **Textos**: Todo traducible — `$this->t()`, `{% trans %}`, `Drupal.t()`. Cero cadenas hardcodeadas.
> 3. **Iconos**: `jaraba_icon('categoría', 'nombre', {opciones})`. No emojis, no FontAwesome.
> 4. **Frontend**: Páginas Zero Region con 3 capas (Controller → `hook_preprocess_html()` → Twig limpia con parciales).
> 5. **Body classes**: Siempre vía `hook_preprocess_html()`, **NUNCA** `attributes.addClass()` en templates.
> 6. **Modales**: Todo CRUD en frontend abre modal con `data-dialog-type="modal"` y `core/drupal.dialog.ajax`.
> 7. **Entidades**: Content Entities con Field UI + Views para datos de negocio editables. Tablas directas solo para append-only de alto volumen.
> 8. **Automatización**: `hook_cron` nativo, **NO** ECA YAML (aprendizaje ECA-001).
> 9. **PHP 8.4**: No redeclarar propiedades heredadas de ControllerBase (DRUPAL11-001). Usar `stdClass` para mocks.
> 10. **Docker**: Todos los comandos via `docker exec jarabasaas_appserver_1 bash -c "..."`.
> 11. **API naming**: `store()` para POST, no `create()` (API-NAMING-001).
> 12. **Accesibilidad**: WCAG 2.1 AA — `focus-visible`, `prefers-reduced-motion`, ARIA, contraste 4.5:1.
> 13. **Navegación admin**: `/admin/structure/{entity}` para configuración de campos, `/admin/content/{entity}` para colecciones.

---

## 2. Estado tras Fase 0 — Punto de Partida

### 2.1 Fase 0 Completada

| Tarea | Archivo | Estado |
|-------|---------|--------|
| Variante `--light-green` | `jaraba_page_builder/scss/blocks/_features.scss:141-164` | ✅ Completada |
| 7 modificadores icon color + 3 alias legacy | `jaraba_page_builder/scss/blocks/_features.scss:248-314` | ✅ Completada |
| Compilación SCSS | `jaraba_page_builder/css/jaraba-page-builder.css` | ✅ Sin errores |
| Cache clear + verificación | 11 clases CSS confirmadas, HTTP 200 | ✅ Verificada |
| Fix `development.services.yml` | `web/sites/development.services.yml` | ✅ Bonus resuelto |

### 2.2 Estado Actual de los Módulos Afectados

**`jaraba_heatmap`** (71% → objetivo 100%):
```
✅ 7 config files (.info.yml, .module, .routing.yml, .services.yml, .permissions.yml, .libraries.yml, .install)
✅ 2 controllers (HeatmapCollectorController, HeatmapApiController — 499 LOC)
✅ 2 services (HeatmapCollectorService 217 LOC, HeatmapAggregatorService 352 LOC)
✅ 1 form (HeatmapSettingsForm 195 LOC)
✅ 2 JS (heatmap-tracker.js 438 LOC, heatmap-viewer.js 383 LOC)
✅ 1 CSS (heatmap-viewer.css 119 LOC)
✅ 2 tests (HeatmapCollectorServiceTest 299 LOC, HeatmapAggregatorServiceTest 225 LOC)
✅ Config + Schema (settings.yml, schema.yml)
❌ HeatmapScreenshotService — NO EXISTE
❌ HeatmapEventProcessor QueueWorker — NO EXISTE
❌ hook_cron automatización — NO IMPLEMENTADO (módulo tiene hook_cron vacío)
❌ Dashboard frontend completo — NO EXISTE (solo viewer JS)
❌ package.json — NO EXISTE
❌ SCSS source — NO EXISTE (solo CSS compilado sin fuente)
```

**`jaraba_analytics`** (88% → objetivo 95%):
```
✅ 47 PHP files, 9 JS files, 8 templates, 13 tests
✅ 9 entidades (AnalyticsEvent, AnalyticsDaily, ConsentRecord + 6 avanzadas)
✅ 9 servicios, 10 controllers
❌ hook_cron para agregación diaria — FALTA disparador automático
```

**`jaraba_pixels`** (85% → objetivo 95%):
```
✅ 26 PHP files, 1 JS, 2 DB tables (pixel_credentials, pixel_event_log)
✅ 4 Platform Clients (Meta, Google, LinkedIn, TikTok)
✅ EventMapperService, PixelDispatcherService
❌ PixelHealthCheckService — NO EXISTE
❌ hook_cron para health check — FALTA
```

**`jaraba_ab_testing`** (85% → objetivo 95%):
```
✅ 28 PHP files, 3 JS
✅ 6 services (StatisticalEngine, VariantAssignment, ExperimentAggregator, OnboardingExperiment, ExposureTracking, ResultCalculation)
❌ ExperimentOrchestratorService — NO EXISTE
❌ hook_cron para auto-winner — FALTA
```

---

## 3. Directrices de Obligado Cumplimiento — Checklist Rápido

Cada componente nuevo DEBE verificarse contra esta lista **antes** de considerarse completo.

### 3.1 SCSS (Directriz §4.1)

```
□ Usa var(--ej-*, $fallback) — nunca $ej-* variables SCSS
□ Dart Sass con @use 'sass:color' — nunca darken()/lighten()
□ package.json con "sass": "^1.71.0"
□ Cabecera con instrucciones de compilación Docker
□ Registrado en {module}.libraries.yml
□ Compilado sin errores dentro del contenedor
□ Edita .scss, NUNCA .css directamente
```

### 3.2 Textos Traducibles (Directriz §4.2)

```
□ PHP: $this->t('...'), new TranslatableMarkup('...')
□ Twig: {% trans %}...{% endtrans %}, {{ 'texto'|t }}
□ JS: Drupal.t('...')
□ Anotaciones: @Translation("...")
□ Formularios: #title y #description con $this->t()
□ Cero cadenas hardcodeadas en la interfaz
```

### 3.3 Iconos (Directriz §4.3)

```
□ Usa jaraba_icon('categoría', 'nombre', {opciones})
□ Categorías: business, analytics, actions, ai, ui, commerce, education, social, verticals
□ Variantes: outline, outline-bold, filled, duotone
□ No emojis Unicode, no FontAwesome, no CDN externo
```

### 3.4 Frontend Zero Region (Directriz §4.6)

```
□ Controller devuelve #theme + #attached
□ hook_preprocess_html() añade body classes
□ page--{ruta}.html.twig limpia con {% include %} parciales
□ Sin page.sidebar_first, sin page.sidebar_second
□ Full-width layout, mobile-first
□ Header y footer del tema (partials/)
```

### 3.5 Entidades y Navegación (Directriz §4.5)

```
□ Content Entity con fieldable = TRUE si necesita Field UI
□ views_data handler para integración con Views
□ /admin/structure/{entity} para configuración de campos
□ /admin/content/{entity} para colecciones
□ AccessControlHandler con aislamiento tenant_id
□ links.menu.yml + links.task.yml + links.action.yml
```

### 3.6 PHP 8.4 / Drupal 11 (Directriz §4.10)

```
□ No redeclarar propiedades heredadas de ControllerBase
□ Constructor: asignación manual para propiedades del padre
□ Tests: stdClass para mock fields, no createMock()->value
□ installEntityType() en lugar de applyUpdates()
□ Logger channel registrado en services.yml
```

---

## 4. Fase 1 — Heatmap: QueueWorker + ScreenshotService

### 4.1 Objetivo y Alcance

Implementar el procesamiento asíncrono de eventos de heatmap mediante un QueueWorker plugin y crear el servicio de capturas de pantalla para el overlay del dashboard. Además, crear la infraestructura SCSS del módulo (package.json, SCSS base).

**Prioridad:** P1 (Alta)
**Estimación:** 12-15h
**Dependencias de entrada:** Fase 0 completada ✅
**Dependencias de salida:** Fase 2 depende de esta fase, Fase 3 depende parcialmente (screenshots)

### 4.2 Tabla de Correspondencia con Spec 20260130a

| Sección Spec | Componente | Archivo Destino | Horas |
|---|---|---|---|
| §4.1 | HeatmapEventProcessor QueueWorker | `src/Plugin/QueueWorker/HeatmapEventProcessor.php` | 2-3h |
| §4.1 | Modificar HeatmapCollectorService (encolar) | `src/Service/HeatmapCollectorService.php` | 1h |
| §7.2 | HeatmapScreenshotService | `src/Service/HeatmapScreenshotService.php` | 5-7h |
| §6.1 | Rutas REST screenshot GET/POST | `jaraba_heatmap.routing.yml` | 0.5h |
| — | services.yml actualizado | `jaraba_heatmap.services.yml` | 0.5h |
| §4.1 | package.json + SCSS base | `package.json`, `scss/main.scss` | 0.5h |
| §14 | Tests unitarios | `tests/src/Unit/` | 2-3h |

### 4.3 Tarea 1.1 — HeatmapEventProcessor QueueWorker

**Archivo:** `web/modules/custom/jaraba_heatmap/src/Plugin/QueueWorker/HeatmapEventProcessor.php`

**Arquitectura:**
- Plugin con anotación `@QueueWorker` y `cron.time = 30` (30s máximos por ejecución).
- Implementa `ContainerFactoryPluginInterface` para inyección de dependencias.
- Procesa un único evento por llamada a `processItem()`.
- No relanza excepciones — registra el error en watchdog y descarta el evento. Diseño deliberado para evitar colas infinitas.

**Patrón de referencia del proyecto:**
- Seguir el patrón exacto documentado en `2026-02-12_Plan_Cierre_Gaps_Specs_20260130_Heatmaps_Tracking_Preview.md` §6.1.
- Constructor con 5 parámetros (configuration, plugin_id, plugin_definition, database, logger).
- `create()` estático que obtiene servicios del contenedor.

**Campos insertados en `heatmap_events`:**
```
tenant_id (int), session_id (string), page_path (string), event_type (string),
x_percent (float|null), y_pixel (int|null), viewport_width (int),
viewport_height (int), scroll_depth (float|null), element_selector (string|null),
element_text (string|null), device_type (string), created_at (int)
```

**Compliance checklist:**
- ✅ Textos: `@Translation("Heatmap Event Processor")` en anotación
- ✅ PHP 8.4: No hereda de ControllerBase (no hay conflicto)
- ✅ Logger: Usa `@logger.factory` del contenedor
- ✅ Tipado estricto: `declare(strict_types=1)`

### 4.4 Tarea 1.2 — Modificar HeatmapCollectorService para encolar

**Archivo:** `web/modules/custom/jaraba_heatmap/src/Service/HeatmapCollectorService.php`

**Cambio:** El servicio actualmente inserta directamente en la BD. Se debe modificar para que encole los eventos en la cola `jaraba_heatmap_events` que luego procesará el QueueWorker. Para preservar compatibilidad, se añade un parámetro de configuración `use_queue` (habilitado por defecto).

**Patrón:**
```php
// Inyectar QueueFactory en constructor
// En processEvents():
if ($this->useQueue) {
    $queue = $this->queueFactory->get('jaraba_heatmap_events');
    foreach ($normalizedEvents as $event) {
        $queue->createItem($event);
    }
} else {
    // Inserción directa (fallback para depuración)
    $this->insertEvents($normalizedEvents);
}
```

**Dependencia nueva en services.yml:**
```yaml
jaraba_heatmap.collector:
  class: Drupal\jaraba_heatmap\Service\HeatmapCollectorService
  arguments:
    - '@database'
    - '@logger.factory'
    - '@state'
    - '@queue'         # NUEVO: QueueFactory para encolar eventos
    - '@config.factory' # NUEVO: Para leer configuración use_queue
```

### 4.5 Tarea 1.3 — HeatmapScreenshotService

**Archivo:** `web/modules/custom/jaraba_heatmap/src/Service/HeatmapScreenshotService.php`

**Arquitectura del servicio:**
1. Método público `getScreenshot($tenantId, $pagePath, $forceRecapture)` — punto de entrada.
2. Método protegido `getExistingScreenshot()` — consulta BD.
3. Método protegido `isScreenshotValid()` — verifica edad < 30 días.
4. Método protegido `captureScreenshot()` — ejecuta `wkhtmltoimage`.
5. Método protegido `saveScreenshotRecord()` — UPSERT en BD con `merge()`.
6. Método público `cleanupExpiredScreenshots($daysToKeep)` — purga archivos + registros BD.

**Decisión arquitectónica — wkhtmltoimage vs Puppeteer:**
La spec menciona Puppeteer (Node.js). Sin embargo, el entorno IONOS de producción puede no tener Node.js runtime. Se implementa `wkhtmltoimage` como backend por defecto (binario estático sin dependencias runtime). El servicio está diseñado para ser reemplazable por una interfaz `ScreenshotCaptureInterface` si en el futuro se decide migrar a Puppeteer o a una API de captura cloud.

**Almacenamiento:** `public://heatmaps/tenant_{id}/{filename}.png`

**Constantes del servicio:**
- `SCREENSHOT_MAX_AGE_DAYS = 30` (días antes de recapturar)
- `DEFAULT_VIEWPORT_WIDTH = 1280` (px estándar para desktop)

**Compliance checklist:**
- ✅ Textos: Mensajes de log traducibles con `@message` placeholders
- ✅ Multi-tenant: Directorio segregado por `tenant_id`
- ✅ Seguridad: `escapeshellarg()` para URL y filepath en exec()
- ✅ File system: Usa `FileSystemInterface::prepareDirectory()` + `CREATE_DIRECTORY`

### 4.6 Tarea 1.4 — Rutas REST para Screenshots

**Archivo:** `web/modules/custom/jaraba_heatmap/jaraba_heatmap.routing.yml` (añadir)

Dos nuevas rutas:

```yaml
jaraba_heatmap.screenshot.get:
  path: '/api/heatmap/pages/{page_path}/screenshot'
  defaults:
    _controller: '\Drupal\jaraba_heatmap\Controller\HeatmapApiController::getScreenshot'
  methods: [GET]
  requirements:
    _permission: 'access heatmap data'
    page_path: '.+'

jaraba_heatmap.screenshot.capture:
  path: '/api/heatmap/pages/{page_path}/screenshot'
  defaults:
    _controller: '\Drupal\jaraba_heatmap\Controller\HeatmapApiController::captureScreenshot'
  methods: [POST]
  requirements:
    _permission: 'administer heatmap settings'
    page_path: '.+'
```

**Métodos a añadir en `HeatmapApiController`:**
- `getScreenshot(string $page_path)` → JsonResponse con `screenshot_url`, `page_height`, `viewport_width`, `captured_at`
- `captureScreenshot(string $page_path)` → JsonResponse con resultado de la captura

### 4.7 Tarea 1.5 — Actualizar services.yml

**Archivo:** `web/modules/custom/jaraba_heatmap/jaraba_heatmap.services.yml`

Añadir:
```yaml
  # Servicio de capturas de página para overlay de heatmap
  # Ref: Spec 20260130a §7.2
  jaraba_heatmap.screenshot:
    class: Drupal\jaraba_heatmap\Service\HeatmapScreenshotService
    arguments:
      - '@database'
      - '@file_system'
      - '@logger.factory'
```

Modificar collector para añadir dependencias:
```yaml
  jaraba_heatmap.collector:
    class: Drupal\jaraba_heatmap\Service\HeatmapCollectorService
    arguments:
      - '@database'
      - '@logger.factory'
      - '@state'
      - '@queue'
      - '@config.factory'
```

### 4.8 Tarea 1.6 — package.json y SCSS base

**Archivo nuevo:** `web/modules/custom/jaraba_heatmap/package.json`

```json
{
    "name": "jaraba-heatmap",
    "version": "1.0.0",
    "description": "Estilos SCSS para el módulo Heatmap de Jaraba SaaS",
    "scripts": {
        "build": "sass scss/main.scss:css/jaraba-heatmap.css --style=compressed",
        "watch": "sass --watch scss:css --style=compressed"
    },
    "devDependencies": {
        "sass": "^1.71.0"
    }
}
```

**Archivo nuevo:** `web/modules/custom/jaraba_heatmap/scss/main.scss`

Archivo principal SCSS que importa los parciales del módulo. Inicialmente contendrá solo la importación del dashboard y del viewer (refactorizando el CSS existente a SCSS).

```scss
/**
 * @file
 * Main SCSS - Estilos del módulo Jaraba Heatmap.
 *
 * COMPILACIÓN (desde Docker):
 * docker exec jarabasaas_appserver_1 bash -c \
 *   "cd /app/web/modules/custom/jaraba_heatmap && npx sass scss/main.scss css/jaraba-heatmap.css --style=compressed"
 *
 * DIRECTRIZ: Usar variables CSS inyectables (var(--ej-*, $fallback))
 * Ver: /.agent/workflows/scss-estilos.md
 */

@use 'sass:color';

// Viewer de heatmap (refactorizado del CSS existente)
@use 'heatmap-viewer';

// Dashboard de analytics (nuevo en Fase 3)
// @use 'heatmap-dashboard';
```

**Archivo nuevo:** `web/modules/custom/jaraba_heatmap/scss/_heatmap-viewer.scss`

Refactorización del `css/heatmap-viewer.css` existente a SCSS. Convertir los hardcoded hex a `var(--ej-*, $fallback)`. Mantener los custom properties existentes `--ej-heatmap-*` que ya siguen el patrón correcto.

### 4.9 Tarea 1.7 — Tests unitarios

**Archivos nuevos:**
- `tests/src/Unit/Plugin/QueueWorker/HeatmapEventProcessorTest.php`
- `tests/src/Unit/Service/HeatmapScreenshotServiceTest.php`

**Tests para QueueWorker:**
1. `testProcessItemValidEvent()` — Evento válido se inserta en BD
2. `testProcessItemMissingFields()` — Campos faltantes usan defaults
3. `testProcessItemDatabaseException()` — Error de BD se registra sin relanzar
4. `testProcessItemNullValues()` — Valores null se manejan correctamente

**Tests para ScreenshotService:**
1. `testGetScreenshotReturnsExisting()` — Screenshot válido se devuelve sin recapturar
2. `testGetScreenshotExpiredRecaptures()` — Screenshot expirado dispara recaptura
3. `testGetScreenshotForceRecapture()` — Flag force ignora cache
4. `testCleanupExpiredScreenshots()` — Purga registros y archivos >30 días
5. `testSaveScreenshotRecordUpsert()` — Merge funciona para insert y update

**Patrón de mock para BD (aprendizaje BILLING-007):**
```php
// ✅ CORRECTO: stdClass para mock fields
$record = (object) [
    'screenshot_uri' => 'public://heatmaps/tenant_1/page.png',
    'captured_at' => time() - 3600,
    'page_height' => 5000,
    'viewport_width' => 1280,
];
```

### 4.10 Verificación y Validación Fase 1

**Comandos de verificación dentro de Docker:**

```bash
# 1. Instalar dependencias SCSS
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_heatmap && npm install"

# 2. Compilar SCSS
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_heatmap && npx sass scss/main.scss:css/jaraba-heatmap.css --style=compressed"

# 3. Limpiar caché
docker exec jarabasaas_appserver_1 bash -c "cd /app && drush cr"

# 4. Ejecutar tests unitarios
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app && php vendor/bin/phpunit web/modules/custom/jaraba_heatmap/tests/"

# 5. Verificar que la cola existe
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app && drush queue:list | grep heatmap"

# 6. Verificar rutas de screenshot
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app && drush route:list --path=/api/heatmap"
```

**Criterios de aceptación:**
- [ ] QueueWorker registrado y visible en `drush queue:list`
- [ ] HeatmapCollectorService encola eventos (verificar con POST a /api/heatmap/collect)
- [ ] HeatmapScreenshotService se instancia sin errores
- [ ] Rutas screenshot responden con status correcto (GET 200 o 404, POST 200 o 500)
- [ ] SCSS compila sin errores
- [ ] Todos los tests pasan

---

## 5. Fase 2 — Heatmap: Automatización con hook_cron

### 5.1 Objetivo y Alcance

Implementar los 3 flujos de automatización definidos en la spec 20260130a §11 mediante `hook_cron` nativo de Drupal. **No usar ECA YAML** (aprendizaje ECA-001).

**Prioridad:** P1 (Alta)
**Estimación:** 3-4h
**Dependencias:** Fase 1 completada (necesita HeatmapAggregatorService + HeatmapScreenshotService)

### 5.2 Tabla de Correspondencia con Spec 20260130a

| Sección Spec | Automatización | Frecuencia | Control |
|---|---|---|---|
| §11.1 | Agregación diaria | 1x/día (después medianoche) | `\Drupal::state()->get('jaraba_heatmap.last_aggregation')` |
| §11.2 | Cleanup semanal | 1x/semana (domingos) | `\Drupal::state()->get('jaraba_heatmap.last_cleanup')` |
| §11.3 | Detección anomalías | 1x/día (mañana) | `\Drupal::state()->get('jaraba_heatmap.last_anomaly_check')` |

### 5.3 Tarea 2.1 — Agregación Diaria

**Archivo:** `web/modules/custom/jaraba_heatmap/jaraba_heatmap.module`

**Lógica:** Ejecutar `HeatmapAggregatorService::aggregateDaily()` una vez al día, controlado por State API.

```php
function _jaraba_heatmap_cron_aggregation(int $time): void {
    $last_run = \Drupal::state()->get('jaraba_heatmap.last_aggregation', 0);
    $today = strtotime('today');

    if ($last_run < $today) {
        /** @var \Drupal\jaraba_heatmap\Service\HeatmapAggregatorService $aggregator */
        $aggregator = \Drupal::service('jaraba_heatmap.aggregator');
        $aggregator->aggregateDaily();
        \Drupal::state()->set('jaraba_heatmap.last_aggregation', $time);
        \Drupal::logger('jaraba_heatmap')->info('Daily aggregation completed.');
    }
}
```

### 5.4 Tarea 2.2 — Cleanup Semanal

**Lógica:** Purgar datos antiguos 1x/semana (domingo o cuando no se ha ejecutado en >7 días).

**Retención configurable:**
- Raw events: 7 días (por defecto, configurable en HeatmapSettingsForm)
- Aggregated data: 90 días
- Screenshots: 30 días (gestionado por HeatmapScreenshotService)

```php
function _jaraba_heatmap_cron_cleanup(int $time): void {
    $last_run = \Drupal::state()->get('jaraba_heatmap.last_cleanup', 0);
    $one_week = 604800;

    if (($time - $last_run) > $one_week) {
        $config = \Drupal::config('jaraba_heatmap.settings');
        $aggregator = \Drupal::service('jaraba_heatmap.aggregator');
        $screenshot = \Drupal::service('jaraba_heatmap.screenshot');

        $raw_days = (int) ($config->get('retention_raw_days') ?: 7);
        $agg_days = (int) ($config->get('retention_aggregated_days') ?: 90);

        $aggregator->purgeOldEvents($raw_days);
        $aggregator->purgeOldAggregated($agg_days);
        $screenshot->cleanupExpiredScreenshots(30);

        \Drupal::state()->set('jaraba_heatmap.last_cleanup', $time);
        \Drupal::logger('jaraba_heatmap')->info('Weekly cleanup completed.');
    }
}
```

### 5.5 Tarea 2.3 — Detección de Anomalías

**Lógica:** Comparar las métricas del día anterior con la media de los 7 días previos. Si hay una caída > 50% o un pico > 200%, registrar alerta.

**Servicio:** Se crea un método nuevo en `HeatmapAggregatorService::detectAnomalies()`.

**Umbrales configurables:**
- `threshold_drop`: 50% (caída mínima para alerta)
- `threshold_spike`: 200% (pico máximo para alerta)

```php
function _jaraba_heatmap_cron_anomaly_detection(int $time): void {
    $last_run = \Drupal::state()->get('jaraba_heatmap.last_anomaly_check', 0);
    $today = strtotime('today');

    if ($last_run < $today) {
        $aggregator = \Drupal::service('jaraba_heatmap.aggregator');
        $anomalies = $aggregator->detectAnomalies();

        if (!empty($anomalies)) {
            \Drupal::logger('jaraba_heatmap')->warning('Anomalies detected: @count pages with unusual activity.', [
                '@count' => count($anomalies),
            ]);
        }

        \Drupal::state()->set('jaraba_heatmap.last_anomaly_check', $time);
    }
}
```

### 5.6 Tarea 2.4 — Tests unitarios de cron

**Archivo:** `tests/src/Unit/CronTest.php`

**Tests:**
1. `testAggregationRunsOncePerDay()` — No ejecuta si ya corrió hoy
2. `testCleanupRunsOncePerWeek()` — No ejecuta antes de 7 días
3. `testAnomalyDetectionRunsDaily()` — Ejecuta una vez al día
4. `testAnomalyThresholds()` — Verifica umbrales drop/spike correctos

---

## 6. Fase 3 — Heatmap: Dashboard Frontend Drupal Nativo

### 6.1 Objetivo y Alcance

Crear el dashboard de heatmaps como página frontend limpia siguiendo el patrón Zero Region. Reemplaza los 3 componentes React de la spec por enfoque Drupal nativo coherente con la arquitectura del SaaS.

**Prioridad:** P2 (Media)
**Estimación:** 10-14h
**Dependencias:** Fase 1 (screenshots), Fase 2 (datos agregados)

### 6.2 Arquitectura de 3 Capas (Zero Region)

**Capa 1 — Controller (`HeatmapDashboardController.php`):**
```php
public function dashboard(): array {
    $tenantId = $this->tenantContext->getTenantId();
    $pages = $this->heatmapApi->getTrackedPages($tenantId);

    return [
        '#theme' => 'heatmap_analytics_dashboard',
        '#pages' => $pages,
        '#tenant_id' => $tenantId,
        '#attached' => [
            'library' => ['jaraba_heatmap/heatmap-dashboard'],
            'drupalSettings' => [
                'jarabaHeatmap' => [
                    'tenantId' => $tenantId,
                    'apiBase' => '/api/heatmap',
                ],
            ],
        ],
    ];
}
```

**Capa 2 — `hook_preprocess_html()` en ecosistema_jaraba_theme.theme:**
```php
// Añadir al array existente $dashboard_routes:
'jaraba_heatmap.analytics_dashboard' => 'page-heatmap-dashboard',
```

**Capa 3 — page--heatmap--analytics.html.twig:**
```twig
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}
<main class="main-content main-content--full" role="main">
    {{ page.content }}
</main>
{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

### 6.3 Tarea 3.1 — HeatmapDashboardController

**Archivo nuevo:** `web/modules/custom/jaraba_heatmap/src/Controller/HeatmapDashboardController.php`

**Patrón:** Extender `ControllerBase`, inyectar servicios via `create()`. No redeclarar `$entityTypeManager` (DRUPAL11-001).

**Métodos:**
- `dashboard()` — Renderiza página con datos iniciales
- `getTitle()` — Título traducible dinámico

### 6.4 Tarea 3.2 — Template Principal

**Archivo nuevo:** `web/modules/custom/jaraba_heatmap/templates/heatmap-analytics-dashboard.html.twig`

**Estructura:**
```
.heatmap-dashboard
├── .heatmap-dashboard__header (título + filtros)
├── .heatmap-dashboard__content
│   ├── .heatmap-dashboard__viewer (Canvas heatmap)
│   ├── .heatmap-dashboard__scroll-depth (barras de scroll)
│   └── .heatmap-dashboard__top-elements (tabla clicados)
└── .heatmap-dashboard__sidebar
    ├── _heatmap-page-selector.html.twig
    ├── _heatmap-metric-card.html.twig × 4
    └── .heatmap-dashboard__device-filter
```

**Textos:** TODOS con `{% trans %}...{% endtrans %}` o `{{ 'texto'|t }}`.

### 6.5 Tarea 3.3 — Parciales Twig Reutilizables

**Parciales nuevos en el módulo:**

1. **`_heatmap-metric-card.html.twig`** — Card de métrica reutilizable
   - Variables: `title`, `value`, `icon_category`, `icon_name`, `trend`, `trend_direction`
   - Usa `jaraba_icon()` para iconos
   - Muestra tendencia con color semántico (success/danger)

2. **`_heatmap-scroll-depth.html.twig`** — Visualización de scroll depth
   - Variables: `depth_data`, `avg_depth`, `fold_line`
   - Barras horizontales con porcentajes

3. **`_heatmap-page-selector.html.twig`** — Selector de página
   - Variables: `pages`, `selected_page`
   - Dropdown con búsqueda

**Convención de inclusión:**
```twig
{% include '@jaraba_heatmap/_heatmap-metric-card.html.twig' with {
    title: 'Total Clicks'|t,
    value: total_clicks,
    icon_category: 'analytics',
    icon_name: 'chart-bar',
    trend: click_trend,
    trend_direction: click_trend_direction,
} only %}
```

### 6.6 Tarea 3.4 — JavaScript heatmap-dashboard.js

**Archivo nuevo:** `web/modules/custom/jaraba_heatmap/js/heatmap-dashboard.js`

**Patrón:** `Drupal.behaviors` + `once()` (aprendizaje LIBRARY-001).

**Funcionalidades:**
- Carga AJAX de datos al seleccionar página
- Filtros por tipo de evento (clicks, scroll, movement)
- Filtros por dispositivo (desktop, tablet, mobile)
- Filtros por rango de fechas
- Integración con `heatmap-viewer.js` existente para Canvas
- Texto traducible con `Drupal.t()`

**Dependencias de library:**
```yaml
heatmap-dashboard:
  version: VERSION
  css:
    component:
      css/jaraba-heatmap.css: {}
  js:
    js/heatmap-dashboard.js: {}
    js/heatmap-viewer.js: {}
  dependencies:
    - core/drupal
    - core/once
    - core/drupal.dialog.ajax
    - core/drupalSettings
```

### 6.7 Tarea 3.5 — SCSS del Dashboard

**Archivo nuevo:** `web/modules/custom/jaraba_heatmap/scss/_heatmap-dashboard.scss`

**Tokens CSS inyectables usados:**
```scss
.heatmap-dashboard {
    background: var(--ej-bg-body, #F8FAFC);
    color: var(--ej-color-body, #334155);

    &__header {
        background: var(--ej-bg-surface, #FFFFFF);
        border-bottom: 1px solid var(--ej-border-color, #E5E7EB);
        padding: var(--ej-spacing-lg, 1.5rem);
    }

    &__title {
        font-family: var(--ej-font-headings, 'Outfit', sans-serif);
        color: var(--ej-color-headings, #1A1A2E);
    }
}
```

**Responsive (mobile-first):**
```scss
.heatmap-dashboard__content {
    display: grid;
    gap: var(--ej-spacing-lg, 1.5rem);

    // Mobile: single column
    grid-template-columns: 1fr;

    // Desktop: 2 columns
    @media (min-width: 992px) {
        grid-template-columns: 1fr 300px;
    }
}
```

**Accesibilidad:**
```scss
// WCAG 2.1 AA: focus-visible
.heatmap-dashboard :focus-visible {
    outline: 2px solid var(--ej-color-primary, #FF8C42);
    outline-offset: 2px;
}

// Reduced motion
@media (prefers-reduced-motion: reduce) {
    .heatmap-dashboard * {
        transition: none !important;
        animation: none !important;
    }
}
```

### 6.8 Tarea 3.6 — Página Twig Limpia + hook_preprocess_html()

**Archivo nuevo en tema:** `web/themes/custom/ecosistema_jaraba_theme/templates/page--heatmap--analytics.html.twig`

**Modificación:** `ecosistema_jaraba_theme.theme` — añadir ruta al array de dashboard routes en `hook_preprocess_html()`.

**⚠️ CRÍTICO:** Las clases se añaden en `hook_preprocess_html()`, NUNCA en el template con `attributes.addClass()`.

### 6.9 Tarea 3.7 — Library, Ruta y Navegación Admin

**Ruta:** Ya documentada en §6.2 (`/heatmap/analytics`).

**Navegación admin:**
```yaml
# jaraba_heatmap.links.menu.yml (crear)
jaraba_heatmap.analytics_dashboard:
  title: 'Heatmap Analytics'
  description: 'Dashboard de análisis de heatmaps del tenant.'
  route_name: jaraba_heatmap.analytics_dashboard
  parent: system.admin_content
  weight: 40

jaraba_heatmap.settings:
  title: 'Heatmap Settings'
  description: 'Configuración del sistema de heatmaps.'
  route_name: jaraba_heatmap.settings
  parent: system.admin_structure
  weight: 40
```

### 6.10 Tarea 3.8 — Compilación SCSS y Verificación Visual

```bash
# Compilar SCSS completo
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_heatmap && npx sass scss/main.scss:css/jaraba-heatmap.css --style=compressed"

# Limpiar caché
docker exec jarabasaas_appserver_1 bash -c "cd /app && drush cr"

# Verificar en navegador: https://jaraba-saas.lndo.site/heatmap/analytics
```

---

## 7. Fase 4 — Tracking: hook_cron para Analytics, Pixels y A/B Testing

### 7.1 Objetivo y Alcance

Implementar los hooks de cron faltantes para los 3 módulos de tracking, conectando los servicios existentes con disparadores automáticos.

**Prioridad:** P2 (Media)
**Estimación:** 10-15h
**Dependencias:** Independiente de Fases 1-3 (puede ejecutarse en paralelo)

### 7.2 Tabla de Correspondencia con Spec 20260130b

| Sección Spec | Módulo | Automatización | Servicio Existente |
|---|---|---|---|
| §8.1 | jaraba_analytics | Agregación diaria + cache invalidation | AnalyticsAggregatorService ✅ |
| §8.3 | jaraba_pixels | Health check de píxeles | PixelDispatcherService (parcial) |
| §8.4 | jaraba_ab_testing | Evaluación auto-winner | StatisticalEngineService + ResultCalculationService ✅ |

### 7.3 Tarea 4.1 — jaraba_analytics hook_cron

**Archivo:** `web/modules/custom/jaraba_analytics/jaraba_analytics.module`

**Automatizaciones:**
1. **Agregación diaria** — Disparar `AnalyticsAggregatorService::aggregateDailyMetrics()` 1x/día
2. **Invalidación de cache Redis** — Limpiar cache tags post-agregación

**Patrón idéntico al de jaraba_heatmap (State API + comparación diaria).**

### 7.4 Tarea 4.2 — jaraba_pixels hook_cron

**Archivo:** `web/modules/custom/jaraba_pixels/jaraba_pixels.module`

**Automatizaciones:**
1. **Health check** (cada 24h) — Verificar último evento exitoso por píxel
2. **Notificación** — Email a admin si píxel en error >48h

**Depende de:** PixelHealthCheckService (Fase 5). Si Fase 5 no está implementada aún, el cron preparará la estructura pero delegará a una función stub que se completará en Fase 5.

### 7.5 Tarea 4.3 — jaraba_ab_testing hook_cron

**Archivo:** `web/modules/custom/jaraba_ab_testing/jaraba_ab_testing.module`

**Automatizaciones:**
1. **Evaluación auto-winner** (cada 6h) — Para experimentos con `auto_complete = TRUE`

**Depende de:** ExperimentOrchestratorService (Fase 5). Misma estrategia de stub.

### 7.6 Tarea 4.4 — Tests unitarios de cron

Tests análogos a los de Fase 2 pero para los 3 módulos de tracking.

---

## 8. Fase 5 — Tracking: PixelHealthCheckService + ExperimentOrchestratorService

### 8.1 Objetivo y Alcance

Crear los 2 servicios especializados que faltan: monitorización proactiva de píxeles y gestión automática de experimentos ganadores.

**Prioridad:** P2 (Media)
**Estimación:** 10-15h
**Dependencias:** Fase 4 (los hooks de cron de Fase 4 llaman a estos servicios)

### 8.2 Tabla de Correspondencia con Spec 20260130b

| Sección | Servicio | Módulo | Horas |
|---|---|---|---|
| §8.3 | PixelHealthCheckService | jaraba_pixels | 4-5h |
| §8.4 | ExperimentOrchestratorService | jaraba_ab_testing | 3-5h |
| §8.3-8.4 | Notificaciones por email | Ambos | 1-2h |
| — | Tests unitarios | Ambos | 2-3h |

### 8.3 Tarea 5.1 — PixelHealthCheckService

**Archivo nuevo:** `web/modules/custom/jaraba_pixels/src/Service/PixelHealthCheckService.php`

**Arquitectura documentada en plan previo (§6.4).** Resumen:
- Umbral: 48h sin eventos exitosos
- Flujo: verificar → enviar test event → si falla → marcar error → notificar
- Dependencias: PixelDispatcherService, Connection, MailManagerInterface, Logger

**Compliance:**
- Textos de email traducibles con `$this->t()`
- Logger channel dedicado
- Multi-tenant: iterar solo píxeles del tenant actual

### 8.4 Tarea 5.2 — ExperimentOrchestratorService

**Archivo nuevo:** `web/modules/custom/jaraba_ab_testing/src/Service/ExperimentOrchestratorService.php`

**Arquitectura documentada en plan previo (§6.5).** Resumen:
- Evaluación cada 6h de experimentos con `auto_complete = TRUE`
- Verificar `minimum_sample_size` + `minimum_runtime_days`
- Llamar `StatisticalEngineService::calculateZScore()`
- Si significativo: declarar winner, completar experimento, notificar

**Compliance:**
- ResultCalculationService ya existe y tiene la lógica estadística
- Este servicio ORQUESTA la evaluación automática sobre él

### 8.5 Tarea 5.3 — Notificaciones por Email

**Templates de email:**
1. **Pixel Health Alert** — `jaraba_pixels_mail()` hook en `.module`
2. **Experiment Winner** — `jaraba_ab_testing_mail()` hook en `.module`

Ambos usan `hook_mail()` con `MailManagerInterface::mail()`.

### 8.6 Tarea 5.4 — Tests unitarios

Tests con mocks de servicios externos (dispatcher, statistical engine, mail manager).

---

## 9. Tabla de Correspondencia Global — Specs 20260130a + 20260130b

| # | Spec | Sección | Componente | Fase | Estado Pre-Plan | Estado Post-Plan |
|---|---|---|---|---|---|---|
| 1 | 20260130a | §4.1 | HeatmapEventProcessor QueueWorker | F1 | ❌ | ✅ |
| 2 | 20260130a | §7.2 | HeatmapScreenshotService | F1 | ❌ | ✅ |
| 3 | 20260130a | §6.1 | Rutas REST screenshot | F1 | ❌ | ✅ |
| 4 | 20260130a | §11.1 | Agregación diaria hook_cron | F2 | ❌ | ✅ |
| 5 | 20260130a | §11.2 | Cleanup semanal hook_cron | F2 | ❌ | ✅ |
| 6 | 20260130a | §11.3 | Detección anomalías hook_cron | F2 | ❌ | ✅ |
| 7 | 20260130a | §8 | HeatmapViewer dashboard | F3 | ⚠️ Parcial | ✅ |
| 8 | 20260130a | §9 | ScrollDepthChart | F3 | ❌ | ✅ |
| 9 | 20260130a | §10 | HeatmapDashboard | F3 | ❌ | ✅ |
| 10 | 20260130b | §8.1 | Analytics hook_cron agregación | F4 | ❌ | ✅ |
| 11 | 20260130b | §8.3 | Pixel hook_cron health check | F4 | ❌ | ✅ |
| 12 | 20260130b | §8.4 | A/B Testing hook_cron auto-winner | F4 | ❌ | ✅ |
| 13 | 20260130b | §8.3 | PixelHealthCheckService | F5 | ❌ | ✅ |
| 14 | 20260130b | §8.4 | ExperimentOrchestratorService | F5 | ❌ | ✅ |
| 15 | 20260130b | §8.3-8.4 | Notificaciones email | F5 | ❌ | ✅ |

**Progreso esperado:**
- Pre-plan: 71% heatmap, 88% tracking
- Post-plan: 100% heatmap (excepto Matomo), 95% tracking (excepto Matomo)

---

## 10. Cumplimiento de Directrices por Fase

| Directriz | F1 | F2 | F3 | F4 | F5 |
|---|---|---|---|---|---|
| SCSS var(--ej-*) | ✅ package.json + SCSS base | — | ✅ Dashboard SCSS | — | — |
| Textos traducibles | ✅ @Translation QueueWorker | ✅ Log messages | ✅ Template + JS | ✅ Log messages | ✅ Emails |
| Iconos jaraba_icon() | — | — | ✅ Metric cards | — | — |
| Zero Region | — | — | ✅ 3 capas completas | — | — |
| hook_preprocess_html() | — | — | ✅ Body classes | — | — |
| Modales CRUD | — | — | ✅ Formularios | — | — |
| Field UI + Views | — | — | — | — | — |
| hook_cron (no ECA) | — | ✅ 3 tareas cron | — | ✅ 3 módulos cron | — |
| PHP 8.4 compatible | ✅ | ✅ | ✅ | ✅ | ✅ |
| Docker execution | ✅ | ✅ | ✅ | ✅ | ✅ |
| WCAG 2.1 AA | — | — | ✅ Focus, motion, ARIA | — | — |
| Multi-tenant | ✅ tenant_id | ✅ tenant_id | ✅ tenant_id | ✅ tenant_id | ✅ tenant_id |

---

## 11. Aprendizajes Críticos Aplicados

| ID | Aprendizaje | Dónde se aplica |
|---|---|---|
| ECA-001 | hook_cron nativo, no ECA YAML | Fases 2 y 4 |
| DRUPAL11-001 | No redeclarar propiedades heredadas | F1 (ScreenshotService), F3 (DashboardController) |
| BILLING-007 | stdClass para mock fields en tests | F1, F2 tests |
| FRONTEND-001 | Zero Region 3 capas | F3 completa |
| LIBRARY-001 | Dependencies en libraries.yml (once, dialog.ajax) | F3 library |
| API-NAMING-001 | store() no create() para POST | F1 screenshot capture |
| SCSS-001 | Dart Sass @use scoping | F1, F3 SCSS |
| TRANSLATE-001 | Todo texto traducible | Todas las fases |
| CRED-005 | WCAG 2.1 AA focus-visible + reduced-motion | F3 SCSS |
| SERVICE-001 | Logger channel registrado en services.yml | F1, F5 |
| CONTROLLER-001 | Constructor pattern Drupal 11 | F3 controller |

---

## 12. Árbol de Dependencias entre Fases

```
Fase 0 ✅ (Premium Preview SCSS)
  │
  ├── Fase 1 (QueueWorker + Screenshot)
  │     │
  │     ├── Fase 2 (hook_cron heatmap)
  │     │     │
  │     │     └── Fase 3 (Dashboard Frontend)
  │     │
  │     └── Fase 3 (Dashboard Frontend) [parcial: screenshots]
  │
  ├── Fase 4 (hook_cron tracking) [INDEPENDIENTE de F1-F3]
  │     │
  │     └── Fase 5 (HealthCheck + AutoWinner)
  │
  └── Fase 6 (Matomo — P3, fuera de este plan)
```

**Nota:** Las Fases 1-3 (heatmap) y las Fases 4-5 (tracking) son **streams independientes** que pueden ejecutarse en paralelo. Sin embargo, dentro de cada stream, el orden es secuencial.

**Orden de ejecución recomendado:**
1. Fase 1 → Fase 2 → Fase 3
2. Fase 4 → Fase 5

---

## 13. Estimaciones y Roadmap

| Fase | Horas Min | Horas Max | Prioridad | Stream |
|---|---|---|---|---|
| Fase 1 — QueueWorker + Screenshot | 12h | 15h | P1 | Heatmap |
| Fase 2 — hook_cron heatmap | 3h | 4h | P1 | Heatmap |
| Fase 3 — Dashboard Frontend | 10h | 14h | P2 | Heatmap |
| Fase 4 — hook_cron tracking | 10h | 15h | P2 | Tracking |
| Fase 5 — HealthCheck + AutoWinner | 10h | 15h | P2 | Tracking |
| **TOTAL** | **45h** | **63h** | — | — |

---

## 14. Registro de Cambios

| Fecha | Versión | Cambio |
|---|---|---|
| 2026-02-12 | 1.0.0 | Creación del documento. Plan completo Fases 1-5. |
