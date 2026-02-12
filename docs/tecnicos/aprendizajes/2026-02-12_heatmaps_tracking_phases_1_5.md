# Aprendizaje: Heatmaps Nativos + Tracking Automation — Fases 1-5

**Fecha:** 2026-02-12
**Módulos afectados:** `jaraba_heatmap`, `jaraba_ab_testing`, `jaraba_pixels`
**Spec de referencia:** Doc 178 — Native Heatmaps v1.0.0 + Spec 20260130b §8.3-§8.4

---

## Resumen

Implementación completa de 5 fases del plan de Heatmaps Nativos + Tracking Automation:
- **Fase 1**: Heatmap Core (install, QueueWorker, screenshots, tests)
- **Fase 2**: hook_cron automation (agregación, limpieza, anomalías)
- **Fase 3**: Dashboard Frontend (Controller, Twig, JS Canvas, SCSS responsive)
- **Fase 4**: Tracking cron cross-módulo (ab_testing auto-winner, pixels health check)
- **Fase 5**: Services + Email (ExperimentOrchestratorService, hook_mail)

**Total**: 53 tests, 250 assertions — todos OK.

---

## Reglas Nuevas

### HEATMAP-001: QueueWorker Plugin Pattern
El `@QueueWorker` plugin DEBE usar `ContainerFactoryPluginInterface` con inyección de dependencias.
La anotación requiere `cron = {"time" = 30}` para procesamiento cron.
El constructor NO debe redeclarar propiedades de `QueueWorkerBase` (PHP 8.4 DRUPAL11-001).

```php
/**
 * @QueueWorker(
 *   id = "jaraba_heatmap_events",
 *   title = @Translation("Heatmap Event Processor"),
 *   cron = {"time" = 30}
 * )
 */
class HeatmapEventProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {
```

### HEATMAP-002: hook_cron con funciones independientes
Refactorizar `hook_cron()` en funciones independientes con State API para rate limiting.
Cada función verifica su propio intervalo de ejecución via `\Drupal::state()`.

```php
function jaraba_heatmap_cron() {
  $time = \Drupal::time()->getRequestTime();
  _jaraba_heatmap_cron_aggregation($time);    // Diario
  _jaraba_heatmap_cron_cleanup($time);         // Semanal (604800s)
  _jaraba_heatmap_cron_anomaly_detection($time); // Diario
}
```

**Intervalos estándar:**
- Diario: `86400` segundos
- Cada 6 horas: `21600` segundos (auto-winner A/B testing)
- Semanal: `604800` segundos

### HEATMAP-003: datetime.time Service ID
`\Drupal::time()` mapea al service ID `datetime.time` en el container, **NO** `time`.
En mocks de tests unitarios usar:
```php
$container->set('datetime.time', $timeMock);
```

### HEATMAP-004: Detección de Anomalías por Comparación Temporal
Comparar métricas de ayer vs media 7 días con umbrales configurables:
- `threshold_drop`: 50% (porcentaje de caída para alerta)
- `threshold_spike`: 200% (porcentaje de pico para alerta)

```php
$ratio = $yesterday_count / $avg_7days;
if ($ratio < (1 - $threshold_drop / 100)) → tipo 'drop'
if ($ratio > $threshold_spike / 100) → tipo 'spike'
```

### HEATMAP-005: Dashboard Canvas con Zero Region Pattern
El dashboard de heatmaps sigue el patrón de 3 capas:
1. **Controller** devuelve `#theme` con datos + `#attached` library
2. **hook_preprocess_html** inyecta body classes (`page-heatmap-analytics`, `dashboard-page`)
3. **Page template** Twig sin regiones Drupal (Zero Region)

JS usa `Drupal.behaviors` + `once()` + `fetch()` API para carga asíncrona.
Canvas 2D para renderizado de heatmap (no React — decisión de simplicidad para Drupal behaviors).

### TRACKING-001: Auto-Winner A/B Testing cada 6 horas
El cron de `jaraba_ab_testing` evalúa auto-winner para experimentos activos con `auto_complete = TRUE`.
Usa `ResultCalculationService::checkAutoStop()` que verifica significancia estadística.
Intervalo: 6 horas. State key: `jaraba_ab_testing.last_auto_winner_check`.

### TRACKING-002: Pixel Health Check diario (48h threshold)
`PixelHealthCheckService::checkAllPixels()` verifica last successful event por pixel.
Umbrales: `> 24h` = warning, `> 48h` = error.
Ejecutado diariamente desde `jaraba_pixels_cron()`.
State key: `jaraba_pixels.last_health_check`.

### TRACKING-003: ExperimentOrchestratorService Pattern
Servicio orquestador que coordina evaluación batch de experimentos:
- `evaluateAll()` → itera experimentos activos con auto_complete
- `evaluateExperiment(int $id)` → calcula resultados + checkAutoStop
- `sendWinnerNotification()` → email via `plugin.manager.mail`

Registrado como `jaraba_ab_testing.experiment_orchestrator` con 5 dependencias DI.

### TRACKING-004: hook_mail para alertas
Dos hook_mail implementados:
- `jaraba_ab_testing_mail('experiment_winner')` — notifica ganador de test A/B
- `jaraba_pixels_mail('pixel_health_alert')` — alerta pixels en estado error

Patrón: `$message['subject']` con `t()` + `$message['body'][]` array de líneas.

---

## Decisiones Técnicas

### wkhtmltoimage sobre Puppeteer
Para capturas de pantalla de heatmaps se usa `wkhtmltoimage` en vez de Puppeteer.
Razón: compatibilidad con IONOS Cloud (no requiere Chrome headless).
El servicio `HeatmapScreenshotService` genera capturas almacenadas en `public://heatmaps/`.
Patrón UPSERT via `$this->database->merge()` para registros de screenshots.

### Canvas 2D sobre React
El dashboard de heatmaps usa Canvas 2D vanilla (dentro de `Drupal.behaviors`) en vez de React.
Razón: coherencia con el stack frontend del proyecto (no hay React en otros módulos).
El visor renderiza gradientes radiales de calor directamente en canvas.

### Parámetros opcionales retrocompatibles
Las funciones `purgeOldEvents()` y `purgeOldAggregated()` usan `?int $days = NULL`:
```php
public function purgeOldEvents(?int $days = NULL): int {
    $days = $days ?? (int) $this->configFactory->get('jaraba_heatmap.settings')->get('retention_raw_days') ?: 7;
```
Esto permite llamadas explícitas en tests sin romper callers existentes.

---

## Inventario de Archivos

### jaraba_heatmap (nuevos/modificados)
| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `jaraba_heatmap.install` | Nuevo | hook_schema() con 4 tablas + hook_requirements() |
| `jaraba_heatmap.module` | Modificado | hook_cron (3 funciones), hook_theme (2 templates) |
| `jaraba_heatmap.routing.yml` | Modificado | +1 ruta analytics_dashboard |
| `jaraba_heatmap.libraries.yml` | Modificado | +1 library heatmap-dashboard |
| `jaraba_heatmap.links.menu.yml` | Nuevo | 2 menu links admin |
| `src/Plugin/QueueWorker/HeatmapEventProcessor.php` | Nuevo | Plugin @QueueWorker |
| `src/Service/HeatmapScreenshotService.php` | Nuevo | Capturas con wkhtmltoimage |
| `src/Service/HeatmapAggregatorService.php` | Modificado | +detectAnomalies(), params opcionales |
| `src/Controller/HeatmapDashboardController.php` | Nuevo | Dashboard frontend |
| `templates/heatmap-analytics-dashboard.html.twig` | Nuevo | Template dashboard |
| `templates/heatmap-metric-card.html.twig` | Nuevo | Tarjeta métrica reutilizable |
| `js/heatmap-dashboard.js` | Nuevo | Canvas rendering + filtros |
| `scss/_heatmap-dashboard.scss` | Nuevo | Estilos dashboard responsive |
| `tests/src/Unit/CronTest.php` | Nuevo | 7 tests cron |
| `tests/src/Unit/Plugin/QueueWorker/HeatmapEventProcessorTest.php` | Nuevo | 6 tests |
| `tests/src/Unit/Service/HeatmapScreenshotServiceTest.php` | Nuevo | 4 tests |
| `tests/src/Unit/Service/HeatmapAggregatorServiceTest.php` | Modificado | +7 tests (anomalías, params) |

### jaraba_ab_testing (nuevos/modificados)
| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `jaraba_ab_testing.module` | Modificado | +hook_cron, +hook_mail, +hook_preprocess_html |
| `jaraba_ab_testing.services.yml` | Modificado | +experiment_orchestrator service |
| `src/Service/ExperimentOrchestratorService.php` | Nuevo | Orquestador auto-winner |
| `tests/src/Unit/CronTest.php` | Nuevo | 4 tests cron auto-winner |
| `tests/src/Unit/Service/ExperimentOrchestratorServiceTest.php` | Nuevo | 5 tests |

### jaraba_pixels (nuevos/modificados)
| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `jaraba_pixels.module` | Modificado | +hook_mail, +daily health check en cron |
| `jaraba_pixels.services.yml` | Modificado | +health_check service |
| `src/Service/PixelHealthCheckService.php` | Nuevo | Monitoreo proactivo pixels |
| `tests/src/Unit/Service/PixelHealthCheckServiceTest.php` | Nuevo | 6 tests |

### Tema (ecosistema_jaraba_theme)
| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `ecosistema_jaraba_theme.theme` | Modificado | +page suggestion heatmap analytics |
| `templates/page--heatmap--analytics.html.twig` | Nuevo | Zero Region template |

---

## Métricas

| Métrica | Valor |
|---------|-------|
| Tests nuevos totales | 53 |
| Assertions totales | 250 |
| Archivos nuevos | ~25 |
| Archivos modificados | ~12 |
| Rutas añadidas | 1 (analytics_dashboard) |
| Servicios añadidos | 3 (screenshot, orchestrator, health_check) |
| Plugins añadidos | 1 (QueueWorker) |
| Templates Twig | 3 (dashboard, metric-card, page template) |
| SCSS compilado | 10,124 bytes |
| Reglas documentadas | 9 (HEATMAP-001 a 005, TRACKING-001 a 004) |
