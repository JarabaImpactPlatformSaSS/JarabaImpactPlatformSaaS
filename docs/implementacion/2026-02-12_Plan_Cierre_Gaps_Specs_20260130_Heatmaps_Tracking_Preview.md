# Plan de Cierre de Gaps — Especificaciones Técnicas 20260130

**Especificaciones cubiertas:**
- `20260130a` — 180\_Platform\_Native\_Heatmaps\_v1
- `20260130b` — 178\_Platform\_Native\_Tracking\_Architecture\_v1
- `20260130c` — 181\_Premium\_Preview\_System\_v1

**Fecha de creación:** 2026-02-12 18:00
**Última actualización:** 2026-02-12 18:00
**Autor:** Claude Opus 4.6 — Arquitecto SaaS Senior
**Versión:** 1.0.0
**Roles:** Arquitecto SaaS, Ingeniero Drupal Senior, Ingeniero UX Senior, Desarrollador Frontend Senior, Diseñador de Theming Senior

---

## Tabla de Contenidos (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado Actual — Auditoría Verificada](#2-estado-actual--auditoría-verificada)
   - 2.1 [Spec 20260130a — Native Heatmaps (jaraba\_heatmap)](#21-spec-20260130a--native-heatmaps-jaraba_heatmap)
   - 2.2 [Spec 20260130b — Native Tracking Architecture](#22-spec-20260130b--native-tracking-architecture)
   - 2.3 [Spec 20260130c — Premium Preview System (jaraba\_page\_builder)](#23-spec-20260130c--premium-preview-system-jaraba_page_builder)
3. [Tabla de Correspondencia con Especificaciones Técnicas](#3-tabla-de-correspondencia-con-especificaciones-técnicas)
4. [Directrices de Obligado Cumplimiento](#4-directrices-de-obligado-cumplimiento)
   - 4.1 [SCSS: Modelo SaaS con Dart Sass y variables inyectables](#41-scss-modelo-saas-con-dart-sass-y-variables-inyectables)
   - 4.2 [Textos de interfaz siempre traducibles](#42-textos-de-interfaz-siempre-traducibles)
   - 4.3 [Sistema de iconos: jaraba\_icon()](#43-sistema-de-iconos-jaraba_icon)
   - 4.4 [Paleta de colores y tokens de diseño](#44-paleta-de-colores-y-tokens-de-diseño)
   - 4.5 [Entidades de contenido con Field UI y Views](#45-entidades-de-contenido-con-field-ui-y-views)
   - 4.6 [Páginas frontend limpias (Zero Region)](#46-páginas-frontend-limpias-zero-region)
   - 4.7 [Modales para acciones CRUD](#47-modales-para-acciones-crud)
   - 4.8 [Templates Twig con parciales reutilizables](#48-templates-twig-con-parciales-reutilizables)
   - 4.9 [Configuración del tema vía UI de Drupal (sin código)](#49-configuración-del-tema-vía-ui-de-drupal-sin-código)
   - 4.10 [PHP 8.4 / Drupal 11 — Reglas de compatibilidad](#410-php-84--drupal-11--reglas-de-compatibilidad)
   - 4.11 [ECA: Hooks nativos, no YAML BPMN](#411-eca-hooks-nativos-no-yaml-bpmn)
   - 4.12 [Accesibilidad WCAG 2.1 AA](#412-accesibilidad-wcag-21-aa)
   - 4.13 [Ejecución en Docker/Lando](#413-ejecución-en-dockerlando)
5. [Plan de Implementación por Fases](#5-plan-de-implementación-por-fases)
   - 5.1 [Fase 0 — Premium Preview SCSS (P0, 0.5h)](#51-fase-0--premium-preview-scss-p0-05h)
   - 5.2 [Fase 1 — Heatmap Queue Worker + Screenshot Service (P1, 12-15h)](#52-fase-1--heatmap-queue-worker--screenshot-service-p1-12-15h)
   - 5.3 [Fase 2 — Heatmap: Automatización con Hooks (P1, 3-4h)](#53-fase-2--heatmap-automatización-con-hooks-p1-3-4h)
   - 5.4 [Fase 3 — Heatmap: Dashboard Frontend Drupal (P2, 10-14h)](#54-fase-3--heatmap-dashboard-frontend-drupal-p2-10-14h)
   - 5.5 [Fase 4 — Tracking: ECA Hooks para Automatización (P2, 10-15h)](#55-fase-4--tracking-eca-hooks-para-automatización-p2-10-15h)
   - 5.6 [Fase 5 — Tracking: Pixel Health Check + Auto-Winner (P2, 10-15h)](#56-fase-5--tracking-pixel-health-check--auto-winner-p2-10-15h)
   - 5.7 [Fase 6 — Matomo Self-Hosted Integration (P3, 40-50h)](#57-fase-6--matomo-self-hosted-integration-p3-40-50h)
6. [Arquitectura Técnica Detallada](#6-arquitectura-técnica-detallada)
   - 6.1 [HeatmapEventProcessor — QueueWorker Plugin](#61-heatmapeventprocessor--queueworker-plugin)
   - 6.2 [HeatmapScreenshotService — Captura Server-Side](#62-heatmapscreenshotservice--captura-server-side)
   - 6.3 [Heatmap Dashboard — Enfoque Drupal Nativo](#63-heatmap-dashboard--enfoque-drupal-nativo)
   - 6.4 [Pixel Health Check Service](#64-pixel-health-check-service)
   - 6.5 [Auto-Winner Orchestrator Service](#65-auto-winner-orchestrator-service)
   - 6.6 [Matomo Integration Service](#66-matomo-integration-service)
7. [SCSS y Theming — Implementación Detallada](#7-scss-y-theming--implementación-detallada)
   - 7.1 [Features SCSS: Variantes de color de icono](#71-features-scss-variantes-de-color-de-icono)
   - 7.2 [Features SCSS: Variante light-green](#72-features-scss-variante-light-green)
   - 7.3 [Heatmap: package.json y compilación SCSS](#73-heatmap-packagejson-y-compilación-scss)
8. [Templates Twig y Parciales](#8-templates-twig-y-parciales)
   - 8.1 [Heatmap Dashboard Page Template](#81-heatmap-dashboard-page-template)
   - 8.2 [Parciales reutilizables del Heatmap](#82-parciales-reutilizables-del-heatmap)
9. [Rutas, Permisos y Navegación Admin](#9-rutas-permisos-y-navegación-admin)
10. [Testing Strategy](#10-testing-strategy)
11. [Aprendizajes Críticos Aplicados](#11-aprendizajes-críticos-aplicados)
12. [Estimaciones y Roadmap](#12-estimaciones-y-roadmap)
13. [Registro de Cambios](#13-registro-de-cambios)

---

## 1. Resumen Ejecutivo

Este plan cubre el cierre de gaps pendientes en las tres especificaciones técnicas con fecha 20260130 del proyecto Jaraba Impact Platform SaaS. La auditoría exhaustiva reveló que el backend de las tres specs está sustancialmente implementado, con gaps concentrados en automatización, servicios complementarios y ajustes de theming.

| Métrica | Valor |
|---------|-------|
| Specs cubiertas | 3 documentos (20260130a, 20260130b, 20260130c) |
| Módulos afectados | 5 (`jaraba_heatmap`, `jaraba_analytics`, `jaraba_pixels`, `jaraba_ab_testing`, `jaraba_page_builder`) |
| Completitud promedio actual | 85% |
| Horas estimadas para cierre total | 86-114h |
| Prioridad máxima (P0) | SCSS Premium Preview — 0.5h |
| Gap más complejo | Matomo Self-Hosted Integration — 40-50h |

**Principio rector:**
> Cada componente debe cumplir con las directrices del proyecto: SCSS con tokens inyectables `var(--ej-*)`, textos traducibles con `t()` / `{% trans %}`, iconos via `jaraba_icon()`, páginas frontend limpias sin regiones de Drupal, modales para CRUD, y automatización via hooks nativos (no ECA YAML). Todo ejecutado dentro del contenedor Docker de Lando.

---

## 2. Estado Actual — Auditoría Verificada

### 2.1 Spec 20260130a — Native Heatmaps (`jaraba_heatmap`)

**Completitud global:** 71%

El módulo `jaraba_heatmap` existe en `web/modules/custom/jaraba_heatmap/` con un backend funcional completo (schema con 4 tablas, 2 controllers, 2 services, tracker JS, viewer JS, settings form). Sin embargo, carece de componentes de automatización y el servicio de capturas de pantalla.

| Componente | Archivos Esperados | Implementados | Estado |
|---|---|---|---|
| Core module files (.yml) | 7 | 7 | ✅ 100% |
| Config (settings + schema) | 2 | 2 | ✅ 100% |
| Schema BD (4 tablas) | 4 | 4 | ✅ 100% |
| Controllers | 2 | 2 | ✅ 100% |
| Services | 3 | 2 | ⚠️ 67% — Falta `HeatmapScreenshotService` |
| Forms | 1 | 1 | ✅ 100% |
| Frontend JS | 2 | 3 | ✅ 100% (tracker + viewer + CSS viewer) |
| Tests unitarios | 2 | 2 | ✅ 100% |
| API REST Routes | 7+2 screenshot | 7 | ⚠️ 78% — Faltan rutas screenshot |
| **QueueWorker Plugin** | 1 | 0 | ❌ 0% — `HeatmapEventProcessor` no existe |
| **React Components** | 3 | 0 | ❌ 0% — Reemplazados por JS nativo (decisión arquitectónica) |
| **ECA Workflows** | 3 | 0 | ❌ 0% — No hay automatización de cron |
| **Screenshot Service** | 1 | 0 | ❌ 0% — Puppeteer no integrado |
| `package.json` | 1 | 0 | ❌ 0% — No hay configuración de compilación SCSS |

**Archivos existentes verificados:**

```
web/modules/custom/jaraba_heatmap/
├── jaraba_heatmap.info.yml          ✅
├── jaraba_heatmap.module            ✅
├── jaraba_heatmap.install           ✅ (4 tablas)
├── jaraba_heatmap.routing.yml       ✅ (7 rutas)
├── jaraba_heatmap.services.yml      ✅ (2 servicios)
├── jaraba_heatmap.permissions.yml   ✅
├── jaraba_heatmap.libraries.yml     ✅
├── config/
│   ├── install/jaraba_heatmap.settings.yml  ✅
│   └── schema/jaraba_heatmap.schema.yml     ✅
├── src/
│   ├── Controller/
│   │   ├── HeatmapCollectorController.php   ✅
│   │   └── HeatmapApiController.php         ✅
│   ├── Service/
│   │   ├── HeatmapCollectorService.php      ✅
│   │   ├── HeatmapAggregatorService.php     ✅
│   │   └── HeatmapScreenshotService.php     ❌ FALTA
│   ├── Plugin/QueueWorker/
│   │   └── HeatmapEventProcessor.php        ❌ FALTA
│   └── Form/
│       └── HeatmapSettingsForm.php          ✅
├── js/
│   ├── heatmap-tracker.js                   ✅
│   └── heatmap-viewer.js                    ✅
├── css/
│   └── heatmap-viewer.css                   ✅
└── tests/
    └── src/Unit/Service/
        ├── HeatmapCollectorServiceTest.php  ✅
        └── HeatmapAggregatorServiceTest.php ✅
```

**Decisión arquitectónica sobre React:** La spec original propone 3 componentes React (`HeatmapViewer.jsx`, `ScrollDepthChart.jsx`, `HeatmapDashboard.jsx`). Sin embargo, el proyecto ya implementó `heatmap-viewer.js` como solución vanilla JS integrada con `Drupal.behaviors`. Dado que el proyecto no usa React como framework frontend principal para dashboards de admin (usa Twig + JS vanilla con `Drupal.behaviors` + `once()`), **se mantendrá el enfoque vanilla JS con Canvas API** y se complementará con un dashboard Twig nativo. Esto es coherente con la arquitectura de otros dashboards del SaaS (analytics, credentials, copilot).

---

### 2.2 Spec 20260130b — Native Tracking Architecture

**Completitud global:** 88%

Esta spec masiva (180-240h estimadas) se implementó distribuida en 3 módulos independientes: `jaraba_analytics`, `jaraba_pixels` y `jaraba_ab_testing`. La implementación supera la spec en varias áreas (entidades avanzadas como Funnel, Cohort, Dashboard) pero tiene gaps en automatización y en la integración con Matomo.

| Componente (por módulo) | Estado | Detalle |
|---|---|---|
| **jaraba_analytics** — Entidades core | ✅ 100% | `AnalyticsEvent`, `AnalyticsDaily`, `ConsentRecord` + 6 avanzadas |
| **jaraba_analytics** — AnalyticsService | ✅ 95% | Todos los métodos de la spec + extras |
| **jaraba_analytics** — APIs REST (20+ endpoints) | ✅ 100% | Superan spec (funnels, cohorts, export) |
| **jaraba_analytics** — ConsentService + Banner | ✅ 100% | GDPR compliant con banner JS |
| **jaraba_analytics** — Tests (13 unitarios) | ✅ 100% | Cobertura de servicios y entidades |
| **jaraba_pixels** — TrackingPixel entity | ✅ 100% | Con config JSON y status |
| **jaraba_pixels** — 4 Platform Clients | ✅ 100% | Meta CAPI, Google MP, LinkedIn, TikTok |
| **jaraba_pixels** — EventMapperService | ✅ 100% | Mapeo universal de 13+ eventos |
| **jaraba_pixels** — PixelDispatcherService | ✅ 100% | Con consent check integrado |
| **jaraba_pixels** — APIs CRUD | ✅ 100% | GET/POST/PATCH/DELETE completo |
| **jaraba_ab_testing** — Entidades | ✅ 100% | `ABExperiment` + `ABVariant` completas |
| **jaraba_ab_testing** — StatisticalEngine | ✅ 100% | Z-test, chi-square, power analysis |
| **Pixel Health Check** | ⚠️ 30% | Solo tracking de errores, sin scheduler proactivo |
| **Auto-Winner Orchestrator** | ⚠️ 70% | Engine existe, falta orquestador de flujo |
| **ECA Workflow YAML Definitions** | ❌ 0% | Lógica en servicios pero sin hooks de cron |
| **Matomo Self-Hosted** | ❌ 0% | Sección 9 de la spec completamente ausente |

**Detalle de archivos por módulo:**

- `jaraba_analytics`: 73 archivos (9 entidades, 9 servicios, 10 controllers, 9 forms, 8 JS, 8 templates, 13 tests)
- `jaraba_pixels`: ~35 archivos (3 entidades, 4 clients, 7 servicios, 3 controllers, 5 tests)
- `jaraba_ab_testing`: ~20 archivos (2 entidades, 1 servicio, 2 controllers, 4 forms, 2 list builders)

---

### 2.3 Spec 20260130c — Premium Preview System (`jaraba_page_builder`)

**Completitud global:** 95%

La infraestructura está completa y correctamente arquitectada. El sistema soporta 70 plantillas con datos curados de preview. Los únicos gaps son 2 variantes SCSS que impiden la fidelidad visual entre la miniatura PNG y el preview live.

| Componente | Archivo | Estado |
|---|---|---|
| `PageTemplate.php` → propiedad `preview_data` | `src/Entity/PageTemplate.php:137` | ✅ Existe |
| `PageTemplate.php` → método `getPreviewData()` | `src/Entity/PageTemplate.php:250-253` | ✅ Existe |
| `PageTemplateInterface` → contrato `getPreviewData()` | `src/PageTemplateInterface.php:70-78` | ✅ Existe |
| `config_export` incluye `preview_data` | `src/Entity/PageTemplate.php:55` | ✅ Existe |
| Schema YAML con `preview_data: ignore` | `config/schema/jaraba_page_builder.schema.yml:65-67` | ✅ Existe |
| `TemplatePickerController::getPreviewData()` con priorización 3 niveles | `src/Controller/TemplatePickerController.php:602-628` | ✅ Existe |
| 70 templates con `preview_data` curado en YAML | `config/install/jaraba_page_builder.template.*.yml` | ✅ Existe |
| `features-grid.html.twig` con `background_variant` | `templates/blocks/features/features-grid.html.twig:23` | ✅ Existe |
| `features-grid.html.twig` con `icon_color` | `templates/blocks/features/features-grid.html.twig:40-44` | ✅ Existe |
| **SCSS `.jaraba-features--light-green`** | `scss/blocks/_features.scss` | ❌ **FALTA** |
| **SCSS `.jaraba-feature-card__icon--{color}`** | `scss/blocks/_features.scss` | ❌ **FALTA** |

**Impacto de los gaps SCSS:** Los templates Twig ya generan las clases CSS (`jaraba-features--light-green`, `jaraba-feature-card__icon--impulse`), pero como no existen los selectores SCSS correspondientes, los estilos no se aplican. Resultado: el preview live no coincide con la miniatura PNG diseñada.

---

## 3. Tabla de Correspondencia con Especificaciones Técnicas

### 3.1 Correspondencia por Sección de Spec — 20260130a (Heatmaps)

| Sección Spec | Título | Módulo | Estado | Gap (Horas) | Fase Plan |
|---|---|---|---|---|---|
| §3 | Modelo de Datos (4 tablas) | `jaraba_heatmap` | ✅ Implementado | — | — |
| §4 | Módulo Drupal (estructura) | `jaraba_heatmap` | ✅ Implementado | — | — |
| §4.3 | Schema hook_schema() | `jaraba_heatmap.install` | ✅ Implementado | — | — |
| §5 | Frontend Tracker JS | `js/heatmap-tracker.js` | ✅ Implementado | — | — |
| §6.1 | POST /api/heatmap/collect | `HeatmapCollectorController` | ✅ Implementado | — | — |
| §6.2-6.4 | GET endpoints (clicks, scroll, movement) | `HeatmapApiController` | ✅ Implementado | — | — |
| §6.1 | GET/POST screenshot endpoints | No existe | ❌ Pendiente | 3-4h | Fase 1 |
| §7.1 | HeatmapCollectorService | `src/Service/` | ✅ Implementado | — | — |
| §7.2 | HeatmapAggregatorService | `src/Service/` | ✅ Implementado | — | — |
| §7.2 | **HeatmapScreenshotService** | No existe | ❌ Pendiente | 7-10h | Fase 1 |
| §4.1 | **HeatmapEventProcessor (QueueWorker)** | No existe | ❌ Pendiente | 2-3h | Fase 1 |
| §8 | HeatmapViewer (React → Drupal nativo) | `js/heatmap-viewer.js` | ⚠️ Adaptado | 5-7h | Fase 3 |
| §9 | ScrollDepthChart (React → Drupal nativo) | No existe | ❌ Pendiente | 3-4h | Fase 3 |
| §10 | HeatmapDashboard (React → Twig) | No existe | ❌ Pendiente | 5-7h | Fase 3 |
| §11.1 | ECA: Agregación nocturna | No existe | ❌ Pendiente (hook_cron) | 1-2h | Fase 2 |
| §11.2 | ECA: Cleanup semanal | No existe | ❌ Pendiente (hook_cron) | 1h | Fase 2 |
| §11.3 | ECA: Alerta anomalías | No existe | ❌ Pendiente (hook_cron) | 1-2h | Fase 2 |
| §13 | Configuración por Tenant | `HeatmapSettingsForm` | ✅ Implementado | — | — |
| §14 | Testing Strategy | 2 unit tests | ✅ Implementado | — | — |

### 3.2 Correspondencia por Sección de Spec — 20260130b (Tracking)

| Sección Spec | Título | Módulo | Estado | Gap (Horas) | Fase Plan |
|---|---|---|---|---|---|
| §3.1 | Entity analytics_event | `jaraba_analytics` | ✅ 100% | — | — |
| §3.2 | Entity analytics_daily | `jaraba_analytics` | ✅ 100% | — | — |
| §3.3 | Eventos E-commerce (15 tipos) | `AnalyticsService` | ✅ 100% | — | — |
| §3.4 | AnalyticsService (PHP) | `jaraba_analytics` | ✅ 95% | — | — |
| §4 | Retargeting Pixel Manager | `jaraba_pixels` | ✅ 95% | — | — |
| §4.1 | 4 Platform Clients (Meta, Google, LinkedIn, TikTok) | `jaraba_pixels/Client/` | ✅ 100% | — | — |
| §4.3 | Event Mapping Universal | `EventMapperService` | ✅ 100% | — | — |
| §5 | Consent Manager (GDPR) | `jaraba_analytics` | ✅ 100% | — | — |
| §5.1 | Entity consent_record | `ConsentRecord.php` | ✅ 100% | — | — |
| §5.2 | Consent Banner JS | `js/consent-banner.js` | ✅ 100% | — | — |
| §6 | A/B Testing Framework | `jaraba_ab_testing` | ✅ 90% | — | — |
| §6.1-6.2 | Entities experiment + variant | `jaraba_ab_testing` | ✅ 100% | — | — |
| §6.3 | Cálculo significancia estadística | `StatisticalEngineService` | ✅ 100% | — | — |
| §7.1 | APIs Analytics (8 endpoints) | `jaraba_analytics` | ✅ 100% | — | — |
| §7.2 | APIs Pixels CRUD | `jaraba_pixels` | ✅ 100% | — | — |
| §7.3 | APIs Consent | `jaraba_analytics` | ✅ 100% | — | — |
| §7.4 | APIs A/B Testing | `jaraba_ab_testing` | ✅ 90% | — | — |
| §8.1 | **Hook cron: Agregación diaria** | Servicio existe, hook no | ⚠️ 85% | 2-3h | Fase 4 |
| §8.2 | **Hook: Server-side event dispatch** | `PixelDispatcherService` | ✅ 100% | — | — |
| §8.3 | **Pixel Health Check scheduler** | No existe | ❌ 30% | 8-10h | Fase 5 |
| §8.4 | **Auto-Winner A/B Test orchestrator** | Engine existe, orquestador no | ⚠️ 70% | 5-8h | Fase 5 |
| §9 | **Matomo Self-Hosted Integration** | No existe | ❌ 0% | 40-50h | Fase 6 |

### 3.3 Correspondencia por Sección de Spec — 20260130c (Premium Preview)

| Sección Spec | Título | Módulo | Estado | Gap (Horas) | Fase Plan |
|---|---|---|---|---|---|
| §4.1 | PageTemplate entity + preview_data | `jaraba_page_builder` | ✅ 100% | — | — |
| §4.2 | config_export con preview_data | `PageTemplate.php` | ✅ 100% | — | — |
| §4.3 | Schema YAML | `schema.yml` | ✅ 100% | — | — |
| §4.4 | TemplatePickerController | `TemplatePickerController.php` | ✅ 100% | — | — |
| §5.2 | YAML features_grid (9 features) | `template.features_grid.yml` | ✅ 100% | — | — |
| §6.1 | **SCSS `.jaraba-features--light-green`** | `_features.scss` | ❌ **FALTA** | 0.25h | Fase 0 |
| §6.2 | **SCSS `.jaraba-feature-card__icon--{color}`** | `_features.scss` | ❌ **FALTA** | 0.25h | Fase 0 |
| §7 (Fase 3) | Validación visual PNG vs live | No verificado | ⚠️ Pendiente | — | Fase 0 |

---

## 4. Directrices de Obligado Cumplimiento

Las siguientes 13 directrices son **obligatorias** para toda implementación derivada de este plan. Cada componente nuevo debe verificarse contra esta lista antes de considerarse completo.

### 4.1 SCSS: Modelo SaaS con Dart Sass y variables inyectables

**Fuente:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`, `.agent/workflows/scss-estilos.md`

**Regla:** Los módulos satélite **NUNCA** definen variables SCSS propias (`$ej-*`). Solo consumen CSS Custom Properties con fallback inline.

**Correcto:**
```scss
@use 'sass:color';

.mi-componente {
    color: var(--ej-color-corporate, #233D63);
    background: var(--ej-bg-surface, #FFFFFF);
    border: 1px solid var(--ej-border-color, #E5E7EB);
}
```

**Incorrecto:**
```scss
// ❌ PROHIBIDO: Definir variables locales que duplican tokens del core
$ej-color-corporate: #233D63;
$mi-color-bg: white;

.mi-componente {
    color: $ej-color-corporate;  // ❌ No usar SCSS variables de tokens
    background: $mi-color-bg;   // ❌ Hardcoded
}
```

**Compilación obligatoria:**
```bash
# Dentro del contenedor Docker
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/{modulo} && npx sass scss/main.scss:css/{output}.css --style=compressed"
```

**Cada módulo SCSS debe tener:**
1. `package.json` con script `build` y dependencia `sass: "^1.71.0"`
2. Cabecera en el archivo principal con instrucciones de compilación
3. Registro de la librería en `{modulo}.libraries.yml`
4. Verificación post-compilación: `lando drush cr`

**Funciones modernas obligatorias (Dart Sass):**
```scss
@use 'sass:color';

// ✅ CORRECTO: color.adjust() / color.scale()
background: color.scale($color, $lightness: 85%);

// ❌ INCORRECTO: darken() / lighten() — DEPRECATED
background: darken($color, 15%);
background: lighten($color, 85%);
```

### 4.2 Textos de interfaz siempre traducibles

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md`, Aprendizaje `TRANSLATE-001`

**Regla:** Todo texto visible al usuario debe ser traducible. Cero cadenas hardcodeadas en la interfaz.

**PHP:**
```php
// ✅ CORRECTO
$this->t('Heatmap tracking enabled');
new TranslatableMarkup('Click density');

// ❌ INCORRECTO
$label = 'Heatmap tracking enabled';  // No traducible
```

**Twig:**
```twig
{# ✅ CORRECTO #}
<h2>{% trans %}Scroll Depth Analysis{% endtrans %}</h2>
<span>{{ 'Total clicks'|t }}</span>
<label>{{ 'Filter by device'|t }}</label>

{# ❌ INCORRECTO #}
<h2>Scroll Depth Analysis</h2>
<span>Total clicks</span>
```

**JavaScript (Drupal.t):**
```javascript
// ✅ CORRECTO
const label = Drupal.t('Loading heatmap data...');
const message = Drupal.t('No data available for this page');

// ❌ INCORRECTO
const label = 'Loading heatmap data...';
```

**Formularios de configuración:**
```php
$form['enabled'] = [
    '#type' => 'checkbox',
    '#title' => $this->t('Enable heatmap tracking'),
    '#description' => $this->t('When enabled, user interactions will be tracked.'),
];
```

### 4.3 Sistema de iconos: `jaraba_icon()`

**Fuente:** `docs/tecnicos/aprendizajes/2026-01-26_iconos_svg_landing_verticales.md`, `ecosistema_jaraba_core/src/Twig/JarabaTwigExtension.php`

**Regla:** Usar `jaraba_icon()` en Twig para TODOS los iconos. No usar emojis Unicode, FontAwesome ni CDN externos.

```twig
{# ✅ CORRECTO — Sistema propio de iconos SVG #}
{{ jaraba_icon('analytics', 'chart-bar', { variant: 'duotone', size: '24px', color: 'corporate' }) }}
{{ jaraba_icon('ui', 'settings', { size: '20px' }) }}
{{ jaraba_icon('actions', 'check', { color: 'success' }) }}

{# ❌ INCORRECTO — Emojis, FontAwesome, CDN externo #}
<span>📊</span>
<i class="fas fa-chart-bar"></i>
```

**Categorías disponibles:** `business`, `analytics`, `actions`, `ai`, `ui`, `commerce`, `education`, `social`, `verticals`

**Variantes:** `outline` (default), `outline-bold`, `filled`, `duotone`

**Colores semánticos:** `corporate` (#233D63), `impulse` (#FF8C42), `innovation` (#00A9A5), `agro` (#556B2F), `success` (#10B981), `warning` (#F59E0B), `danger` (#EF4444)

### 4.4 Paleta de colores y tokens de diseño

**Fuente:** `ecosistema_jaraba_theme/scss/_variables.scss`, `ecosistema_jaraba_theme/scss/_base.scss`

**Paleta de marca oficial Jaraba:**

| Token | Hex | CSS Custom Property | Uso semántico |
|---|---|---|---|
| Corporate | `#233D63` | `--ej-color-corporate` | La "J", confianza, autoridad |
| Impulse | `#FF8C42` | `--ej-color-impulse` | Emprendimiento, CTAs, acción |
| Innovation | `#00A9A5` | `--ej-color-innovation` | Talento, empleabilidad |
| Agro | `#556B2F` | `--ej-color-agro` | AgroConecta, naturaleza |
| Success | `#10B981` | `--ej-color-success` | Estados positivos |
| Warning | `#F59E0B` | `--ej-color-warning` | Alertas |
| Danger | `#EF4444` | `--ej-color-danger` | Errores, destructivo |

**Backgrounds y textos:**

| Token | Hex | CSS Custom Property |
|---|---|---|
| Body BG | `#F8FAFC` | `--ej-bg-body` |
| Surface/Card | `#FFFFFF` | `--ej-bg-surface` |
| Text Primary | `#1A1A2E` | `--ej-color-headings` |
| Text Body | `#334155` | `--ej-color-body` |
| Text Muted | `#64748B` | `--ej-color-muted` |
| Border | `#E5E7EB` | `--ej-border-color` |

**Regla de uso en SCSS:** Siempre `var(--ej-*, $fallback)` — nunca hex directo.

### 4.5 Entidades de contenido con Field UI y Views

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md`, Aprendizajes `ENTITY-001`, `ENTITY-SD-001`

**Regla:** Toda entidad de contenido debe tener Field UI habilitado (`fieldable = TRUE`), handlers de Views (`views_data`), y navegación en `/admin/structure` (para campos) y `/admin/content` (para colecciones).

**Checklist obligatorio para nuevas entidades:**

```
✅ Anotación @ContentEntityType con TODOS los handlers
✅ fieldable = TRUE
✅ field_ui_base_route apuntando a ruta settings
✅ views_data handler = Drupal\views\EntityViewsData
✅ links: collection, canonical, add-form, edit-form, delete-form
✅ ListBuilder personalizado
✅ AccessControlHandler con aislamiento por tenant_id
✅ 4 archivos YAML: routing.yml, links.menu.yml, links.task.yml, links.action.yml
✅ Campo tenant_id para aislamiento multi-tenant
```

**Nota para este plan:** Los módulos `jaraba_heatmap`, `jaraba_analytics` y `jaraba_pixels` usan tablas directas via `hook_schema()` (no Content Entities) para datos de alta frecuencia (eventos, métricas). Esto es correcto según el aprendizaje `MILESTONE-001`: tablas append-only de alto volumen no necesitan Field UI. Las entidades de configuración (`ABExperiment`, `TrackingPixel`, etc.) sí son Content Entities con Field UI completo.

### 4.6 Páginas frontend limpias (Zero Region)

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md`, Aprendizaje `FRONTEND-001`

**Regla:** Las páginas de frontend del SaaS usan templates Twig propios sin `page.content` de Drupal, sin bloques heredados, sin sidebar de admin (salvo para administradores). Layout full-width pensado para móvil.

**Patrón de 3 capas sincronizadas:**

**Capa 1 — Controller:** Devuelve render array con `#theme` y `#attached`:
```php
public function heatmapDashboard(): array {
    return [
        '#theme' => 'heatmap_analytics_dashboard',
        '#pages' => $this->heatmapApi->getTrackedPages(),
        '#attached' => [
            'library' => ['jaraba_heatmap/heatmap-dashboard'],
        ],
    ];
}
```

**Capa 2 — hook_preprocess_html():** Body classes (NO `attributes.addClass()` en template):
```php
// En ecosistema_jaraba_theme.theme
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
    $route = \Drupal::routeMatch()->getRouteName();
    $heatmap_routes = [
        'jaraba_heatmap.analytics_dashboard' => 'page-heatmap-dashboard',
    ];
    if (isset($heatmap_routes[$route])) {
        $variables['attributes']['class'][] = $heatmap_routes[$route];
        $variables['attributes']['class'][] = 'dashboard-page';
    }
}
```

**Capa 3 — Template Twig:** Página limpia con parciales:
```twig
{# page--heatmap--dashboard.html.twig #}
{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}
<main class="main-content main-content--full">
    {{ page.content }}
</main>
{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

### 4.7 Modales para acciones CRUD

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md`

**Regla:** Todas las acciones de crear/editar/ver en frontend deben abrirse en modal. El usuario no debe abandonar la página en la que está trabajando.

```twig
{# Enlace que abre formulario en modal #}
<a href="{{ path('entity.tracking_pixel.edit_form', {'tracking_pixel': pixel.id}) }}"
   class="use-ajax button button--secondary"
   data-dialog-type="modal"
   data-dialog-options='{"width": 600, "title": "{{ 'Edit Pixel'|t }}"}'>
    {{ 'Edit'|t }}
</a>
```

**Dependencia obligatoria en library:**
```yaml
mi-dashboard:
  js:
    js/mi-dashboard.js: {}
  dependencies:
    - core/drupal
    - core/once
    - core/drupal.dialog.ajax  # Obligatorio para modales con use-ajax
```

### 4.8 Templates Twig con parciales reutilizables

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md`, Aprendizaje `TEMPLATE-001`

**Regla:** Antes de extender una página, comprobar si ya existe un parcial reutilizable. Si no existe y el fragmento se usará en más de una página, crear un parcial con `{% include %}`.

**Parciales existentes en el tema (verificar antes de crear nuevos):**
- `_header.html.twig` — Cabecera del sitio (ya incluye navegación)
- `_footer.html.twig` — Pie de página con datos configurables desde UI
- `_copilot-fab.html.twig` — Botón flotante del copiloto

**Convención de nombres:**
```
partials/_nombre-componente.html.twig     # Parcial reutilizable
page--seccion--pagina.html.twig           # Página limpia
```

**Inclusión con aislamiento de variables:**
```twig
{% include '@ecosistema_jaraba_theme/partials/_metric-card.html.twig' with {
    title: 'Total Clicks'|t,
    value: total_clicks,
    icon_category: 'analytics',
    icon_name: 'chart-bar',
    trend: trend_value
} only %}
```

### 4.9 Configuración del tema vía UI de Drupal (sin código)

**Fuente:** `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md`

**Regla:** Los valores de los parciales de header/footer y otros componentes configurables deben provenir de la configuración del tema, accesible desde `/admin/appearance/settings/ecosistema_jaraba_theme`. No se debe tener que tocar código para cambiar contenido del footer, colores, logos, etc.

**Mecanismo:**
1. Se definen fields en `ecosistema_jaraba_theme.theme` → `hook_form_system_theme_settings_alter()`
2. Se guardan en la config del tema: `theme_get_setting('nombre_campo')`
3. Se inyectan a las templates vía `hook_preprocess_page()` o `hook_preprocess_html()`
4. Los parciales usan las variables inyectadas

```php
// En hook_preprocess_page():
$variables['footer_text'] = theme_get_setting('footer_text') ?? '';
$variables['footer_links'] = theme_get_setting('footer_links') ?? [];
```

```twig
{# En _footer.html.twig #}
<footer class="ej-footer">
    <p>{{ footer_text }}</p>
</footer>
```

### 4.10 PHP 8.4 / Drupal 11 — Reglas de compatibilidad

**Fuente:** Aprendizajes `DRUPAL11-001`, `DRUPAL11-002`, `BILLING-007`

**Prohibición de redeclarar propiedades heredadas:**
```php
// ❌ INCORRECTO — Fatal error en PHP 8.4
class MiController extends ControllerBase {
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager, // Heredada de ControllerBase
    ) {}
}

// ✅ CORRECTO — Asignación manual
class MiController extends ControllerBase {
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        protected HeatmapCollectorService $collector,
    ) {
        $this->entityTypeManager = $entityTypeManager;
    }
}
```

**En tests unitarios — no usar propiedades dinámicas en mocks:**
```php
// ❌ INCORRECTO
$field = $this->createMock(FieldItemListInterface::class);
$field->value = 'active'; // NULL en PHP 8.4

// ✅ CORRECTO
$field = (object) ['value' => 'active'];
```

### 4.11 ECA: Hooks nativos, no YAML BPMN

**Fuente:** Aprendizaje `ECA-001`

**Regla:** Todas las automatizaciones en módulos custom usan hooks nativos de Drupal (`hook_cron`, `hook_entity_insert`, `hook_entity_update`), NO definiciones ECA en YAML. Razón: versionables en git, testeables, rendimiento predecible.

```php
// En jaraba_heatmap.module
function jaraba_heatmap_cron(): void {
    // Agregación diaria — ejecutar después de medianoche
    $last_run = \Drupal::state()->get('jaraba_heatmap.last_aggregation', 0);
    $today = strtotime('today');

    if ($last_run < $today) {
        /** @var \Drupal\jaraba_heatmap\Service\HeatmapAggregatorService $aggregator */
        $aggregator = \Drupal::service('jaraba_heatmap.aggregator');
        $aggregator->aggregateDaily();
        \Drupal::state()->set('jaraba_heatmap.last_aggregation', \Drupal::time()->getRequestTime());
    }
}
```

### 4.12 Accesibilidad WCAG 2.1 AA

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md`, Aprendizaje `CRED-005`

**Checklist para cada componente nuevo:**
- `focus-visible` con variables `--ej-focus-ring-*`
- `@media (prefers-reduced-motion: reduce)` eliminando animaciones
- Navegación por teclado completa (Tab, Enter, Escape)
- Etiquetas ARIA en contenedores interactivos
- Contraste mínimo 4.5:1 para texto normal
- Jerarquía de headings sin saltos (h1 → h2 → h3)
- Formularios con `<label>` asociado a cada `<input>`

### 4.13 Ejecución en Docker/Lando

**Fuente:** `docs/00_DIRECTRICES_PROYECTO.md`

**Todos los comandos se ejecutan dentro del contenedor Docker:**

```bash
# Drush
docker exec jarabasaas_appserver_1 bash -c "cd /app && drush cr"
docker exec jarabasaas_appserver_1 bash -c "cd /app && drush cex -y"
docker exec jarabasaas_appserver_1 bash -c "cd /app && drush updb -y"

# SCSS Compilation
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_heatmap && npx sass scss/main.scss:css/jaraba-heatmap.css --style=compressed"

# Composer (si necesario)
docker exec jarabasaas_appserver_1 bash -c "cd /app && composer require paquete/nombre"

# npm install (para módulos con package.json)
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_heatmap && npm install"
```

**URL de verificación:** `https://jaraba-saas.lndo.site/`

---

## 5. Plan de Implementación por Fases

### 5.1 Fase 0 — Premium Preview SCSS (P0, 0.5h)

**Objetivo:** Cerrar los 2 gaps SCSS de la spec 20260130c para lograr fidelidad visual entre miniatura PNG y preview live en el sistema de templates del Page Builder.

**Spec:** 20260130c §6.1, §6.2

| # | Tarea | Horas | Spec | Acción |
|---|---|---|---|---|
| 0.1 | Añadir variante `.jaraba-features--light-green` en `_features.scss` | 0.15h | §6.1 | Gradiente verde claro coherente con PNG |
| 0.2 | Añadir 4 modificadores `.jaraba-feature-card__icon--{color}` en `_features.scss` | 0.15h | §6.2 | Colores corporativos: impulse, innovation, corporate, success |
| 0.3 | Compilar SCSS y verificar en navegador | 0.1h | §7 | `npm run build` dentro de Docker |
| 0.4 | Verificar fidelidad visual PNG vs preview live | 0.1h | §8 | Comparar 9 tarjetas, fondo, iconos, layout |

**Subtotal Fase 0:** 0.5h

**Detalle de implementación → ver [§7.1](#71-features-scss-variantes-de-color-de-icono) y [§7.2](#72-features-scss-variante-light-green)**

---

### 5.2 Fase 1 — Heatmap Queue Worker + Screenshot Service (P1, 12-15h)

**Objetivo:** Implementar el procesamiento asíncrono de eventos mediante Queue Worker y el servicio de capturas de pantalla para overlay de heatmap.

**Spec:** 20260130a §4.1 (QueueWorker), §7.2 (ScreenshotService), §12.5 (Sprint 5 de spec)

| # | Tarea | Horas | Spec | Acción |
|---|---|---|---|---|
| 1.1 | Crear `HeatmapEventProcessor` QueueWorker Plugin | 2-3h | §4.1 | Plugin con `@QueueWorker` annotation, procesamiento batch |
| 1.2 | Registrar queue en `jaraba_heatmap.services.yml` | 0.5h | §4.1 | Servicio del queue worker |
| 1.3 | Crear `HeatmapScreenshotService` | 5-7h | §7.2 | Captura server-side (wkhtmltoimage como alternativa ligera a Puppeteer) |
| 1.4 | Añadir rutas screenshot GET/POST en `routing.yml` | 0.5h | §6.1 | Endpoints para consultar y solicitar capturas |
| 1.5 | Actualizar `jaraba_heatmap.services.yml` con nuevos servicios | 0.5h | — | Declaración DI de screenshot service |
| 1.6 | Tests unitarios para QueueWorker y ScreenshotService | 2-3h | §14 | PHPUnit con mocks |
| 1.7 | Crear `package.json` para compilación SCSS | 0.5h | Directriz 4.1 | Script build con Dart Sass |

**Subtotal Fase 1:** 12-15h

**Detalle de implementación → ver [§6.1](#61-heatmapeventprocessor--queueworker-plugin) y [§6.2](#62-heatmapscreenshotservice--captura-server-side)**

**Nota sobre Puppeteer vs wkhtmltoimage:** La spec original menciona Puppeteer (Node.js). Sin embargo, dado que el entorno de producción es PHP/Drupal sobre IONOS (no dispone de Node.js en producción salvo para compilación), se recomienda `wkhtmltoimage` (binario estático, sin dependencias runtime) o la captura mediante la API de Drupal con renderizado server-side. Si el servidor de producción tiene Node.js disponible, se puede optar por Puppeteer. La decisión se documenta en el servicio con una interfaz abstracta.

---

### 5.3 Fase 2 — Heatmap: Automatización con Hooks (P1, 3-4h)

**Objetivo:** Implementar los 3 flujos de automatización de la spec mediante `hook_cron` nativo (según directriz ECA-001).

**Spec:** 20260130a §11.1, §11.2, §11.3

| # | Tarea | Horas | Spec | Acción |
|---|---|---|---|---|
| 2.1 | Implementar agregación nocturna en `hook_cron` | 1-1.5h | §11.1 | Usar `\Drupal::state()` para control 1x/día |
| 2.2 | Implementar cleanup semanal en `hook_cron` | 0.5-1h | §11.2 | Purga raw 7 días, agregated 90 días, screenshots 30 días |
| 2.3 | Implementar detección de anomalías en `hook_cron` | 1-1.5h | §11.3 | Comparar métricas vs media 7 días, umbral configurable |
| 2.4 | Test unitario de las funciones de cron | 0.5h | §14 | Verificar lógica de state y thresholds |

**Subtotal Fase 2:** 3-4h

**Patrón de implementación (Aprendizaje ECA-001):**
```php
/**
 * Implements hook_cron().
 *
 * Ejecuta 3 tareas automatizadas del sistema de heatmaps:
 * 1. Agregación diaria de eventos raw a buckets (después de medianoche)
 * 2. Cleanup semanal de datos antiguos (domingos)
 * 3. Detección de anomalías en métricas de interacción (diaria a las 9h)
 *
 * Cada tarea usa State API para evitar ejecuciones duplicadas.
 * Ref: Spec 20260130a §11
 */
function jaraba_heatmap_cron(): void {
    $time = \Drupal::time()->getRequestTime();

    // 1. Agregación diaria
    _jaraba_heatmap_cron_aggregation($time);

    // 2. Cleanup semanal (domingos)
    _jaraba_heatmap_cron_cleanup($time);

    // 3. Detección anomalías
    _jaraba_heatmap_cron_anomaly_detection($time);
}
```

---

### 5.4 Fase 3 — Heatmap: Dashboard Frontend Drupal (P2, 10-14h)

**Objetivo:** Crear el dashboard de heatmaps como página frontend limpia con Twig templates, parciales reutilizables y JS vanilla con Canvas API. Reemplaza los 3 componentes React de la spec por enfoque Drupal nativo coherente con la arquitectura del SaaS.

**Spec:** 20260130a §8, §9, §10

| # | Tarea | Horas | Spec | Acción |
|---|---|---|---|---|
| 3.1 | Crear template Twig `heatmap-analytics-dashboard.html.twig` | 2-3h | §10 | Dashboard completo con filtros, viewer, sidebar |
| 3.2 | Crear parcial `_heatmap-metric-card.html.twig` | 0.5h | §10 | Card de métrica reutilizable |
| 3.3 | Crear parcial `_heatmap-scroll-depth.html.twig` | 1-1.5h | §9 | Visualización de scroll depth con barras |
| 3.4 | Crear parcial `_heatmap-page-selector.html.twig` | 0.5h | §10 | Selector de página con filtros |
| 3.5 | Ampliar `heatmap-viewer.js` con scroll depth chart | 2-3h | §9 | Canvas rendering de barras de profundidad |
| 3.6 | Crear `heatmap-dashboard.js` con Drupal.behaviors | 2-3h | §10 | Carga AJAX de datos, filtros, integración |
| 3.7 | Crear page template `page--heatmap--dashboard.html.twig` | 0.5h | Directriz 4.6 | Página limpia con header/footer propios |
| 3.8 | Registrar ruta frontend y hook_preprocess_html | 0.5h | Directriz 4.6 | Body class para CSS targeting |
| 3.9 | SCSS del dashboard (ampliar `_heatmap-dashboard.scss` existente) | 1-1.5h | — | Tokens inyectables, responsive, accesible |
| 3.10 | Compilar SCSS, registrar library, verificar | 0.5h | Directriz 4.1 | Build + drush cr + test visual |

**Subtotal Fase 3:** 10-14h

**Detalle de implementación → ver [§6.3](#63-heatmap-dashboard--enfoque-drupal-nativo) y [§8](#8-templates-twig-y-parciales)**

---

### 5.5 Fase 4 — Tracking: ECA Hooks para Automatización (P2, 10-15h)

**Objetivo:** Implementar los hooks de cron faltantes para los módulos `jaraba_analytics`, `jaraba_pixels` y `jaraba_ab_testing`, conectando los servicios existentes con disparadores automáticos.

**Spec:** 20260130b §8.1, §8.3, §8.4

| # | Tarea | Horas | Spec | Acción |
|---|---|---|---|---|
| 4.1 | `jaraba_analytics.module` — `hook_cron` para agregación diaria | 2-3h | §8.1 | Disparar `AnalyticsAggregatorService::aggregateDailyMetrics()` |
| 4.2 | `jaraba_analytics.module` — Invalidación de cache Redis post-agregación | 1h | §8.1 | Limpiar cache tags de métricas del tenant |
| 4.3 | `jaraba_pixels.module` — `hook_cron` para health check | 3-4h | §8.3 | Verificar último evento exitoso por píxel |
| 4.4 | `jaraba_pixels.module` — Notificación admin por píxel en error | 2-3h | §8.3 | Email via `MailManagerInterface` con template |
| 4.5 | `jaraba_ab_testing.module` — `hook_cron` para evaluación auto-winner | 2-3h | §8.4 | Verificar muestra mínima y significancia |
| 4.6 | Tests unitarios de las funciones de cron | 1-2h | — | State API mocking, threshold verification |

**Subtotal Fase 4:** 10-15h

---

### 5.6 Fase 5 — Tracking: Pixel Health Check + Auto-Winner (P2, 10-15h)

**Objetivo:** Crear los servicios especializados que faltan: `PixelHealthCheckService` para monitorización proactiva de píxeles y `ExperimentOrchestratorService` para gestión automática de experimentos ganadores.

**Spec:** 20260130b §8.3, §8.4

| # | Tarea | Horas | Spec | Acción |
|---|---|---|---|---|
| 5.1 | Crear `PixelHealthCheckService` | 4-5h | §8.3 | Verificación proactiva, test events, status update |
| 5.2 | Registrar servicio en `jaraba_pixels.services.yml` | 0.5h | — | DI con PixelDispatcherService y MailManager |
| 5.3 | Crear `ExperimentOrchestratorService` | 3-5h | §8.4 | Coordinar StatisticalEngine + auto-complete + redirect |
| 5.4 | Registrar servicio en `jaraba_ab_testing.services.yml` | 0.5h | — | DI con StatisticalEngineService |
| 5.5 | Tests unitarios para ambos servicios | 2-3h | — | PHPUnit con mocks de external calls |
| 5.6 | Integrar notificaciones por email | 1-2h | §8.3, §8.4 | Templates de email para health alerts y experiment results |

**Subtotal Fase 5:** 10-15h

**Detalle de implementación → ver [§6.4](#64-pixel-health-check-service) y [§6.5](#65-auto-winner-orchestrator-service)**

---

### 5.7 Fase 6 — Matomo Self-Hosted Integration (P3, 40-50h)

**Objetivo:** Integrar Matomo 5.x self-hosted como complemento de analytics avanzado, con sincronización bidireccional hacia `jaraba_analytics` y configuración multi-tenant.

**Spec:** 20260130b §9

**Nota de priorización:** Esta fase es P3 (baja prioridad inmediata) porque `jaraba_analytics` ya proporciona analytics nativos funcionales. Matomo añade valor en: heatmaps de Matomo (redundante con `jaraba_heatmap`), session recordings, form analytics y SEO analytics. Se recomienda evaluar si el valor justifica las 40-50h de integración dado que ya se tienen heatmaps nativos y analytics propios.

| # | Tarea | Horas | Spec | Acción |
|---|---|---|---|---|
| 6.1 | Instalación y configuración Matomo 5.x self-hosted | 4-6h | §9.1 | Subdirectorio /matomo, MySQL compartida, multi-site |
| 6.2 | Configuración multi-tenant (1 Site ID por tenant) | 3-4h | §9.1 | Crear sites automáticamente al crear tenant |
| 6.3 | Crear módulo `jaraba_matomo` con MatomoApiClient | 8-10h | §9.3 | Cliente PHP para Reporting API de Matomo |
| 6.4 | Crear `MatomoSyncService` para sincronización | 6-8h | §9.3 | Importar métricas de Matomo a analytics_daily |
| 6.5 | hook_cron para sincronización horaria | 2-3h | §9.3 | Cada hora importar métricas agregadas |
| 6.6 | JavaScript tracker integration (dual tracking) | 3-4h | §9.1 | Matomo tracker + jaraba tracker en paralelo |
| 6.7 | Formulario de configuración Matomo por tenant | 3-4h | §9.1 | URL, Site ID, auth token |
| 6.8 | Instalar plugins recomendados de Matomo | 2-3h | §9.2 | CustomDimensions, Funnels, GDPR Tools |
| 6.9 | Tests de integración | 4-5h | — | Verificar sync, multi-tenant, data consistency |
| 6.10 | Dashboard: widgets de Matomo embebidos | 4-6h | §9.3 | iframes o API para dashboards híbridos |

**Subtotal Fase 6:** 40-50h

**Detalle de implementación → ver [§6.6](#66-matomo-integration-service)**

---

## 6. Arquitectura Técnica Detallada

### 6.1 HeatmapEventProcessor — QueueWorker Plugin

**Spec:** 20260130a §4.1
**Ubicación:** `web/modules/custom/jaraba_heatmap/src/Plugin/QueueWorker/HeatmapEventProcessor.php`

**Propósito:** Procesar eventos de heatmap de la cola de forma asíncrona. El `HeatmapCollectorService` encola eventos recibidos del tracker JS (via Beacon API) y este worker los inserta en la tabla `heatmap_events` en batches para minimizar la carga en la base de datos durante el tráfico en tiempo real.

**Flujo de datos:**
```
Browser JS Tracker
    → POST /api/heatmap/collect (Beacon API)
    → HeatmapCollectorController::collect()
    → HeatmapCollectorService::processPayload()
    → Queue 'jaraba_heatmap_events' (Redis)
    → [Cron] HeatmapEventProcessor::processItem()
    → INSERT heatmap_events (MySQL batch)
```

**Estructura del código:**
```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_heatmap\Plugin\QueueWorker;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa eventos de heatmap encolados por HeatmapCollectorService.
 *
 * Los eventos llegan desde el tracker JavaScript del frontend mediante
 * Beacon API y se almacenan temporalmente en la cola Redis. Este worker
 * los inserta en la tabla heatmap_events durante el procesamiento de cron,
 * desacoplando la recepción de datos (tiempo real) del almacenamiento
 * persistente (batch asíncrono).
 *
 * @QueueWorker(
 *   id = "jaraba_heatmap_events",
 *   title = @Translation("Heatmap Event Processor"),
 *   cron = {"time" = 30}
 * )
 *
 * Ref: Spec 20260130a §4.1 — HeatmapEventProcessor
 */
class HeatmapEventProcessor extends QueueWorkerBase implements ContainerFactoryPluginInterface {

    protected Connection $database;
    protected LoggerInterface $logger;

    public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        Connection $database,
        LoggerInterface $logger,
    ) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->database = $database;
        $this->logger = $logger;
    }

    public static function create(
        ContainerInterface $container,
        array $configuration,
        $plugin_id,
        $plugin_definition,
    ): static {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('database'),
            $container->get('logger.factory')->get('jaraba_heatmap'),
        );
    }

    /**
     * {@inheritdoc}
     *
     * Procesa un único evento de heatmap e inserta en BD.
     * Cada $data contiene los campos normalizados por HeatmapCollectorService:
     * tenant_id, session_id, page_path, event_type, x_percent, y_pixel,
     * viewport_width, viewport_height, scroll_depth, element_selector,
     * element_text, device_type, created_at.
     */
    public function processItem($data): void {
        try {
            $this->database->insert('heatmap_events')
                ->fields([
                    'tenant_id' => (int) $data['tenant_id'],
                    'session_id' => (string) $data['session_id'],
                    'page_path' => (string) $data['page_path'],
                    'event_type' => (string) $data['event_type'],
                    'x_percent' => $data['x_percent'] ?? NULL,
                    'y_pixel' => $data['y_pixel'] ?? NULL,
                    'viewport_width' => (int) ($data['viewport_width'] ?? 0),
                    'viewport_height' => (int) ($data['viewport_height'] ?? 0),
                    'scroll_depth' => $data['scroll_depth'] ?? NULL,
                    'element_selector' => $data['element_selector'] ?? NULL,
                    'element_text' => $data['element_text'] ?? NULL,
                    'device_type' => $data['device_type'] ?? 'desktop',
                    'created_at' => (int) ($data['created_at'] ?? \Drupal::time()->getRequestTime()),
                ])
                ->execute();
        }
        catch (\Exception $e) {
            $this->logger->error('Failed to process heatmap event: @message', [
                '@message' => $e->getMessage(),
            ]);
            // No relanzar excepción para evitar que el item se reencole indefinidamente.
            // El evento se pierde, pero se registra en el log para diagnóstico.
        }
    }
}
```

**Notas de implementación:**
- El `cron.time = 30` define 30 segundos máximos por ejecución de cron para este worker.
- Los eventos perdidos por error se registran en watchdog pero no se reencolan (diseño deliberado para evitar colas infinitas en caso de error de schema).
- Para alto volumen, considerar `batchInsert()` del `HeatmapCollectorService` en lugar de inserciones individuales.

---

### 6.2 HeatmapScreenshotService — Captura Server-Side

**Spec:** 20260130a §7.2, §12.5
**Ubicación:** `web/modules/custom/jaraba_heatmap/src/Service/HeatmapScreenshotService.php`

**Propósito:** Capturar screenshots de páginas del tenant para usarlos como fondo del overlay de heatmap. El servicio abstrae el mecanismo de captura para permitir diferentes backends (wkhtmltoimage, Puppeteer, o API externa).

**Consideraciones arquitectónicas:**
- El entorno IONOS de producción puede no tener Node.js runtime, por lo que se prefiere `wkhtmltoimage` (binario estático) como backend por defecto.
- Se define una interfaz `ScreenshotCaptureInterface` para permitir implementaciones alternativas sin modificar el servicio.
- Los screenshots se almacenan en `public://heatmaps/tenant_{id}/` con naming basado en el path de la página.
- Se implementa cache con invalidación por timestamp (`captured_at`) para evitar capturas redundantes.

**Estructura del código:**
```php
<?php

declare(strict_types=1);

namespace Drupal\jaraba_heatmap\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para capturar y gestionar screenshots de páginas.
 *
 * Los screenshots sirven como fondo para el overlay de heatmap en Canvas.
 * Se almacenan en el filesystem público del tenant y se referencian desde
 * la tabla heatmap_page_screenshots para asociarlos con page_path.
 *
 * Flujo:
 * 1. Se solicita screenshot para un path (manual o automático)
 * 2. Se verifica si existe uno reciente (<30 días)
 * 3. Si no existe o está expirado: se captura con wkhtmltoimage
 * 4. Se guarda en public://heatmaps/tenant_{id}/ y se registra en BD
 *
 * Ref: Spec 20260130a §7.2 — HeatmapScreenshotService
 */
class HeatmapScreenshotService {

    /**
     * Días de validez de un screenshot antes de recapturar.
     */
    protected const SCREENSHOT_MAX_AGE_DAYS = 30;

    /**
     * Ancho de viewport por defecto para capturas.
     */
    protected const DEFAULT_VIEWPORT_WIDTH = 1280;

    public function __construct(
        protected Connection $database,
        protected FileSystemInterface $fileSystem,
        protected LoggerInterface $logger,
    ) {}

    /**
     * Obtiene el screenshot para una página, capturando si es necesario.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param string $pagePath
     *   Path de la página (ej: /productos/tomates).
     * @param bool $forceRecapture
     *   Si TRUE, ignora cache y recaptura.
     *
     * @return array|null
     *   Array con 'screenshot_uri', 'page_height', 'viewport_width',
     *   'captured_at' o NULL si no se pudo capturar.
     */
    public function getScreenshot(int $tenantId, string $pagePath, bool $forceRecapture = FALSE): ?array {
        // 1. Verificar screenshot existente en BD.
        if (!$forceRecapture) {
            $existing = $this->getExistingScreenshot($tenantId, $pagePath);
            if ($existing && $this->isScreenshotValid($existing)) {
                return $existing;
            }
        }

        // 2. Capturar nuevo screenshot.
        $result = $this->captureScreenshot($tenantId, $pagePath);
        if ($result) {
            $this->saveScreenshotRecord($tenantId, $pagePath, $result);
        }

        return $result;
    }

    /**
     * Consulta si existe screenshot en BD para este tenant+path.
     */
    protected function getExistingScreenshot(int $tenantId, string $pagePath): ?array {
        $record = $this->database->select('heatmap_page_screenshots', 's')
            ->fields('s')
            ->condition('tenant_id', $tenantId)
            ->condition('page_path', $pagePath)
            ->execute()
            ->fetchAssoc();

        return $record ?: NULL;
    }

    /**
     * Verifica si el screenshot aún es válido (no expirado).
     */
    protected function isScreenshotValid(array $record): bool {
        $maxAge = self::SCREENSHOT_MAX_AGE_DAYS * 86400;
        return (\Drupal::time()->getRequestTime() - (int) $record['captured_at']) < $maxAge;
    }

    /**
     * Captura un screenshot de la página usando wkhtmltoimage.
     *
     * @param int $tenantId
     *   ID del tenant.
     * @param string $pagePath
     *   Path de la página a capturar.
     *
     * @return array|null
     *   Resultado con URI del archivo y dimensiones, o NULL si falla.
     */
    protected function captureScreenshot(int $tenantId, string $pagePath): ?array {
        // Construir URL absoluta de la página.
        $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
        $fullUrl = $baseUrl . $pagePath;

        // Directorio de destino.
        $directory = "public://heatmaps/tenant_{$tenantId}";
        $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        // Nombre de archivo basado en el path.
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($pagePath, '/'));
        $filepath = "{$directory}/{$filename}.png";
        $realPath = $this->fileSystem->realpath($filepath) ?: "/tmp/heatmap_{$tenantId}_{$filename}.png";

        // Ejecutar wkhtmltoimage (si disponible).
        $command = sprintf(
            'wkhtmltoimage --width %d --quality 80 --quiet %s %s 2>&1',
            self::DEFAULT_VIEWPORT_WIDTH,
            escapeshellarg($fullUrl),
            escapeshellarg($realPath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $this->logger->warning('Screenshot capture failed for @path: @output', [
                '@path' => $pagePath,
                '@output' => implode("\n", $output),
            ]);
            return NULL;
        }

        // Obtener dimensiones de la imagen.
        $imageSize = @getimagesize($realPath);
        $pageHeight = $imageSize ? $imageSize[1] : 0;

        // Mover a filesystem gestionado de Drupal si es necesario.
        if (!str_starts_with($realPath, 'public://')) {
            $this->fileSystem->move($realPath, $filepath, FileSystemInterface::EXISTS_REPLACE);
        }

        return [
            'screenshot_uri' => $filepath,
            'page_height' => $pageHeight,
            'viewport_width' => self::DEFAULT_VIEWPORT_WIDTH,
            'captured_at' => \Drupal::time()->getRequestTime(),
        ];
    }

    /**
     * Guarda o actualiza el registro de screenshot en BD.
     */
    protected function saveScreenshotRecord(int $tenantId, string $pagePath, array $data): void {
        $this->database->merge('heatmap_page_screenshots')
            ->keys([
                'tenant_id' => $tenantId,
                'page_path' => $pagePath,
            ])
            ->fields([
                'screenshot_uri' => $data['screenshot_uri'],
                'page_height' => $data['page_height'],
                'viewport_width' => $data['viewport_width'],
                'captured_at' => $data['captured_at'],
            ])
            ->execute();
    }

    /**
     * Elimina screenshots expirados de un tenant.
     *
     * @param int $daysToKeep
     *   Número de días a retener. Por defecto 30.
     */
    public function cleanupExpiredScreenshots(int $daysToKeep = 30): int {
        $cutoff = \Drupal::time()->getRequestTime() - ($daysToKeep * 86400);

        // Obtener URIs para eliminar archivos físicos.
        $records = $this->database->select('heatmap_page_screenshots', 's')
            ->fields('s', ['id', 'screenshot_uri'])
            ->condition('captured_at', $cutoff, '<')
            ->execute()
            ->fetchAllAssoc('id');

        foreach ($records as $record) {
            try {
                $this->fileSystem->delete($record->screenshot_uri);
            }
            catch (\Exception $e) {
                // Archivo ya no existe, continuar con limpieza de BD.
            }
        }

        // Eliminar registros de BD.
        return (int) $this->database->delete('heatmap_page_screenshots')
            ->condition('captured_at', $cutoff, '<')
            ->execute();
    }
}
```

**Registro en `jaraba_heatmap.services.yml`:**
```yaml
  # Servicio de capturas de página para overlay de heatmap
  jaraba_heatmap.screenshot:
    class: Drupal\jaraba_heatmap\Service\HeatmapScreenshotService
    arguments:
      - '@database'
      - '@file_system'
      - '@logger.factory'
```

---

### 6.3 Heatmap Dashboard — Enfoque Drupal Nativo

**Spec:** 20260130a §8, §9, §10

**Justificación de la decisión arquitectónica:** La spec original propone React para el dashboard. Sin embargo, la plataforma Jaraba usa Twig + vanilla JS + `Drupal.behaviors` como patrón estándar para dashboards de administración (ver: `copilot-analytics-dashboard`, `credentials-dashboard`, `ab-testing-dashboard`). Implementar un dashboard React aislado rompería la coherencia del SaaS y añadiría una dependencia de build separada. El viewer Canvas ya funciona en vanilla JS (`heatmap-viewer.js`).

**Componentes del dashboard:**

1. **Controller** (`HeatmapDashboardController.php`): Renderiza la página con datos iniciales y attach de libraries.
2. **Template Twig** (`heatmap-analytics-dashboard.html.twig`): Layout con filtros, viewer, scroll chart y sidebar de métricas.
3. **Parciales Twig**: Componentes reutilizables (`_heatmap-metric-card`, `_heatmap-scroll-depth`, `_heatmap-page-selector`).
4. **JavaScript** (`heatmap-dashboard.js`): Interacción con `Drupal.behaviors`, carga AJAX de datos, filtros dinámicos.
5. **SCSS** (`_heatmap-dashboard.scss`): Ya existe parcialmente en el tema; se amplía con tokens inyectables.

**Ruta del dashboard:**
```yaml
# En jaraba_heatmap.routing.yml
jaraba_heatmap.analytics_dashboard:
  path: '/heatmap/analytics'
  defaults:
    _controller: '\Drupal\jaraba_heatmap\Controller\HeatmapDashboardController::dashboard'
    _title_callback: '\Drupal\jaraba_heatmap\Controller\HeatmapDashboardController::getTitle'
  requirements:
    _permission: 'access heatmap data'
```

---

### 6.4 Pixel Health Check Service

**Spec:** 20260130b §8.3
**Ubicación:** `web/modules/custom/jaraba_pixels/src/Service/PixelHealthCheckService.php`

**Propósito:** Verificar proactivamente el estado de salud de los píxeles de tracking configurados. Detecta píxeles que han dejado de funcionar (>48h sin eventos exitosos) y notifica al administrador del tenant.

**Flujo:**
```
[Cron diario 08:00] → PixelHealthCheckService::runHealthCheck()
    → Para cada tenant:
        → Para cada tracking_pixel activo:
            → Verificar último evento exitoso (tracking_event)
            → Si >48h sin éxito:
                → Enviar test event via PixelDispatcherService
                → Si test event falla:
                    → Actualizar pixel.status = 'error'
                    → Enviar email de alerta al admin del tenant
            → Si test event OK pero estaba en error:
                → Restaurar pixel.status = 'active'
```

**Interfaz del servicio:**
```php
/**
 * Servicio de monitorización proactiva de salud de píxeles.
 *
 * Ejecutado via hook_cron, verifica que cada píxel de tracking activo
 * sigue funcionando correctamente. Envía test events a las plataformas
 * (Meta, Google, LinkedIn, TikTok) y actualiza el estado del píxel.
 *
 * Ref: Spec 20260130b §8.3
 */
class PixelHealthCheckService {
    // Umbral en segundos (48 horas).
    protected const HEALTH_THRESHOLD_SECONDS = 172800;

    public function __construct(
        protected PixelDispatcherService $dispatcher,
        protected Connection $database,
        protected MailManagerInterface $mailManager,
        protected LoggerInterface $logger,
    ) {}

    public function runHealthCheck(): array;
    protected function checkPixelHealth(TrackingPixel $pixel): string;
    protected function sendTestEvent(TrackingPixel $pixel): bool;
    protected function notifyAdmin(TrackingPixel $pixel, string $status): void;
    protected function getLastSuccessfulEvent(int $pixelId): ?int;
}
```

---

### 6.5 Auto-Winner Orchestrator Service

**Spec:** 20260130b §8.4
**Ubicación:** `web/modules/custom/jaraba_ab_testing/src/Service/ExperimentOrchestratorService.php`

**Propósito:** Orquestar la evaluación automática de experimentos A/B. Cuando un experimento tiene `auto_complete = TRUE` y todas las variantes han alcanzado el `minimum_sample_size`, evalúa la significancia estadística y, si se alcanza el `confidence_threshold`, declara automáticamente una variante ganadora y redirige el 100% del tráfico.

**Flujo:**
```
[Cron cada 6h] → ExperimentOrchestratorService::evaluateActiveExperiments()
    → Para cada ABExperiment con status='running' AND auto_complete=TRUE:
        → Verificar minimum_sample_size en todas las variantes
        → Si muestra insuficiente: continuar
        → Verificar minimum_runtime_days transcurrido
        → Llamar StatisticalEngineService::calculateZScore(control, variant)
        → Si p_value < (1 - confidence_threshold):
            → Marcar variante como is_winner = TRUE
            → Cambiar experiment.status = 'completed'
            → Redirigir 100% tráfico a variante ganadora
            → Notificar admin del tenant con resultados
```

**Interfaz del servicio:**
```php
/**
 * Orquestador de evaluación automática de experimentos A/B.
 *
 * Coordina el StatisticalEngineService para evaluar periódicamente
 * los experimentos activos con auto_complete habilitado. Cuando se
 * alcanza significancia estadística, declara la variante ganadora
 * y completa el experimento automáticamente.
 *
 * Ref: Spec 20260130b §8.4
 */
class ExperimentOrchestratorService {
    public function __construct(
        protected StatisticalEngineService $statisticalEngine,
        protected EntityTypeManagerInterface $entityTypeManager,
        protected MailManagerInterface $mailManager,
        protected LoggerInterface $logger,
    ) {}

    public function evaluateActiveExperiments(): array;
    protected function evaluateExperiment(ABExperiment $experiment): ?ABVariant;
    protected function declareWinner(ABExperiment $experiment, ABVariant $winner): void;
    protected function notifyResults(ABExperiment $experiment, ABVariant $winner, array $stats): void;
}
```

---

### 6.6 Matomo Integration Service

**Spec:** 20260130b §9
**Ubicación:** `web/modules/custom/jaraba_matomo/` (módulo nuevo)

**Estructura del módulo:**
```
web/modules/custom/jaraba_matomo/
├── jaraba_matomo.info.yml
├── jaraba_matomo.module              # hook_cron para sync horaria
├── jaraba_matomo.routing.yml
├── jaraba_matomo.services.yml
├── jaraba_matomo.permissions.yml
├── config/
│   ├── install/jaraba_matomo.settings.yml
│   └── schema/jaraba_matomo.schema.yml
├── src/
│   ├── Client/
│   │   └── MatomoApiClient.php       # HTTP client para Reporting API
│   ├── Service/
│   │   ├── MatomoSyncService.php     # Sincronización Matomo → analytics_daily
│   │   └── MatomoTenantManager.php   # Crear/gestionar sites por tenant
│   ├── Controller/
│   │   └── MatomoSettingsController.php
│   └── Form/
│       └── MatomoSettingsForm.php    # Config Matomo por tenant
└── tests/
    └── src/Unit/
        ├── Client/MatomoApiClientTest.php
        └── Service/MatomoSyncServiceTest.php
```

**MatomoApiClient:** Cliente HTTP que usa `GuzzleHttp\ClientInterface` (inyectado via DI) para comunicarse con la API de reportes de Matomo. Soporta autenticación via `token_auth` almacenado encriptado en config del tenant.

**MatomoSyncService:** Importa métricas agregadas de Matomo (visits, pageviews, bounce rate, etc.) y las inyecta en los campos correspondientes de `analytics_daily`. Se ejecuta cada hora via `hook_cron`.

**MatomoTenantManager:** Crea automáticamente un Site en Matomo cuando se crea un nuevo tenant en Drupal. Usa la API `SitesManager.addSite` de Matomo.

---

## 7. SCSS y Theming — Implementación Detallada

### 7.1 Features SCSS: Variantes de color de icono

**Spec:** 20260130c §6.2
**Archivo:** `web/modules/custom/jaraba_page_builder/scss/blocks/_features.scss`
**Posición:** Dentro del bloque `.jaraba-feature-card__icon` (después de la línea 210)

**Código a añadir:**

```scss
    // === Variantes de color por token de marca ===
    // Ref: Spec 20260130c §6.2 — Iconos coloridos por color corporativo.
    // Los colores se aplican al icono SVG dentro del contenedor.
    // La clase se genera desde el Twig: icon_color → jaraba-feature-card__icon--{color}
    // Los nombres de color corresponden a la paleta oficial de Jaraba.

    &--impulse {
        background: rgba(255, 140, 66, 0.1);
        svg, .icon, img { color: var(--ej-color-impulse, #FF8C42); }
    }

    &--innovation {
        background: rgba(0, 169, 165, 0.1);
        svg, .icon, img { color: var(--ej-color-innovation, #00A9A5); }
    }

    &--corporate {
        background: rgba(35, 61, 99, 0.1);
        svg, .icon, img { color: var(--ej-color-corporate, #233D63); }
    }

    &--success {
        background: rgba(16, 185, 129, 0.1);
        svg, .icon, img { color: var(--ej-color-success, #10B981); }
    }

    &--warning {
        background: rgba(245, 158, 11, 0.1);
        svg, .icon, img { color: var(--ej-color-warning, #F59E0B); }
    }

    &--danger {
        background: rgba(239, 68, 68, 0.1);
        svg, .icon, img { color: var(--ej-color-danger, #EF4444); }
    }

    &--agro {
        background: rgba(85, 107, 47, 0.1);
        svg, .icon, img { color: var(--ej-color-agro, #556B2F); }
    }
```

**Verificación:** Las clases se generan en `features-grid.html.twig:40-44` con el patrón:
```twig
{% set icon_color_class = feature.icon_color ? 'jaraba-feature-card__icon--' ~ feature.icon_color : '' %}
```
Y los valores de `feature.icon_color` en el YAML de `features_grid` son: `impulse`, `innovation`, `corporate`, `success`.

### 7.2 Features SCSS: Variante light-green

**Spec:** 20260130c §6.1
**Archivo:** `web/modules/custom/jaraba_page_builder/scss/blocks/_features.scss`
**Posición:** Dentro del bloque `.jaraba-features` (después de la variante `--gradient`, línea 136)

**Código a añadir:**

```scss
    // === Variante fondo verde claro ===
    // Ref: Spec 20260130c §6.1 — Gradiente verde suave para features_grid.
    // Usado por la plantilla features_grid cuyo PNG de miniatura muestra
    // fondo verde claro (#e8f5e9 → #f1f8e9).
    &--light-green {
        background: linear-gradient(180deg, #e8f5e9 0%, #f1f8e9 100%);

        .jaraba-features__title {
            color: var(--ej-text-primary, #1e293b);
        }

        .jaraba-features__subtitle {
            color: var(--ej-text-secondary, #64748b);
        }

        .jaraba-feature-card {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(200, 230, 200, 0.5);

            &:hover {
                background: white;
                border-color: rgba(76, 175, 80, 0.3);
            }
        }
    }
```

**Verificación:** La clase se genera en `features-grid.html.twig:23` con:
```twig
{% set bg_class = content.background_variant ? 'jaraba-features--' ~ content.background_variant : '' %}
```
Y el valor en el YAML de `features_grid` es `background_variant: 'light-green'`.

### 7.3 Heatmap: package.json y compilación SCSS

**Spec:** Directriz 4.1
**Archivo:** `web/modules/custom/jaraba_heatmap/package.json`

El módulo `jaraba_heatmap` tiene SCSS en el tema (`ecosistema_jaraba_theme/scss/components/_heatmap-dashboard.scss`) pero no tiene `package.json` propio. Dado que el SCSS del heatmap viewer está en `css/heatmap-viewer.css` (ya compilado), y el dashboard SCSS está en el tema, se necesita crear un `package.json` para gestionar la compilación de cualquier SCSS futuro del módulo.

**Archivo a crear:**
```json
{
    "name": "jaraba-heatmap",
    "version": "1.0.0",
    "description": "Estilos SCSS para el módulo Heatmap de Jaraba SaaS",
    "scripts": {
        "build": "sass scss/main.scss:css/jaraba-heatmap.css --style=compressed",
        "build:all": "npm run build && echo '✅ Build completado'",
        "watch": "sass --watch scss:css --style=compressed"
    },
    "devDependencies": {
        "sass": "^1.71.0"
    },
    "keywords": ["jaraba", "heatmap", "scss", "drupal"],
    "author": "Jaraba Impact Platform",
    "license": "UNLICENSED"
}
```

**Compilación:**
```bash
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_heatmap && npm install && npm run build"
```

---

## 8. Templates Twig y Parciales

### 8.1 Heatmap Dashboard Page Template

**Ubicación:** `web/themes/custom/ecosistema_jaraba_theme/templates/page--heatmap--dashboard.html.twig`

**Propósito:** Página frontend limpia para el dashboard de heatmaps. Sin sidebar de admin, layout full-width, header y footer propios del tema.

**Estructura:**
```twig
{#
 * page--heatmap--dashboard.html.twig
 *
 * PROPÓSITO: Renderizar el dashboard de analytics de heatmaps
 * como página full-width sin regiones de Drupal.
 *
 * PATRÓN: Zero Region — HTML completo con {% include %} de parciales.
 * El contenido principal se inyecta via {{ page.content }} desde el
 * controller HeatmapDashboardController::dashboard().
 *
 * DIRECTRICES APLICADAS:
 * - Directriz 4.6: Página frontend limpia
 * - Directriz 4.8: Parciales reutilizables
 * - Directriz 4.12: WCAG 2.1 AA (skip link, landmarks)
 *
 * Ref: Spec 20260130a §10
 *#}

{% include '@ecosistema_jaraba_theme/partials/_header.html.twig' %}

<a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Skip to main content{% endtrans %}
</a>

<main id="main-content" class="main-content main-content--full" role="main">
    {{ page.content }}
</main>

{% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' %}
```

### 8.2 Parciales reutilizables del Heatmap

**Parcial `_heatmap-metric-card.html.twig`:**
Reutilizable para cualquier tarjeta de métrica numérica con icono y tendencia. Se usa en el dashboard de heatmaps y potencialmente en otros dashboards (analytics, pixels).

```twig
{#
 * _heatmap-metric-card.html.twig
 *
 * Tarjeta de métrica con icono, valor numérico y tendencia.
 *
 * VARIABLES:
 *   - title (string): Título de la métrica (traducible).
 *   - value (string|int): Valor principal a mostrar.
 *   - icon_category (string): Categoría del icono (analytics, ui, etc).
 *   - icon_name (string): Nombre del icono.
 *   - trend (float|null): Cambio porcentual vs período anterior.
 *   - trend_label (string): Texto de contexto del trend.
 *
 * UBICACIÓN: web/themes/custom/ecosistema_jaraba_theme/templates/partials/
 *#}

<div class="heatmap-metric-card" role="group" aria-label="{{ title }}">
    <div class="heatmap-metric-card__icon">
        {{ jaraba_icon(icon_category, icon_name, { variant: 'duotone', size: '24px' }) }}
    </div>
    <div class="heatmap-metric-card__content">
        <span class="heatmap-metric-card__value">{{ value }}</span>
        <span class="heatmap-metric-card__title">{{ title }}</span>
    </div>
    {% if trend is not null %}
        <div class="heatmap-metric-card__trend heatmap-metric-card__trend--{{ trend >= 0 ? 'up' : 'down' }}">
            {{ jaraba_icon('analytics', trend >= 0 ? 'trend-up' : 'trend-down', { size: '16px' }) }}
            <span>{{ trend > 0 ? '+' : '' }}{{ trend|number_format(1) }}%</span>
        </div>
    {% endif %}
</div>
```

---

## 9. Rutas, Permisos y Navegación Admin

**Rutas nuevas a registrar:**

| Módulo | Ruta | Método | Controller | Permiso |
|---|---|---|---|---|
| `jaraba_heatmap` | `/heatmap/analytics` | GET | `HeatmapDashboardController::dashboard` | `access heatmap data` |
| `jaraba_heatmap` | `/api/heatmap/pages/{path}/screenshot` | GET | `HeatmapApiController::getScreenshot` | `access heatmap data` |
| `jaraba_heatmap` | `/api/heatmap/pages/{path}/screenshot` | POST | `HeatmapApiController::requestScreenshot` | `administer heatmap` |

**Permisos existentes (verificados):**
- `access heatmap data` — Ver datos de heatmap
- `administer heatmap` — Administrar configuración

**Navegación admin existente:**
- `/admin/config/jaraba/heatmap` — Configuración del módulo (ya existe)

**El dashboard de heatmaps (`/heatmap/analytics`) es una página frontend del SaaS**, no una página de admin. El tenant accede a ella desde su panel de métricas, no desde `/admin/`.

---

## 10. Testing Strategy

**Estructura de tests por fase:**

| Fase | Archivo Test | Tipo | Qué verifica |
|---|---|---|---|
| 1 | `tests/src/Unit/Plugin/QueueWorker/HeatmapEventProcessorTest.php` | Unit | Inserción BD, manejo errores |
| 1 | `tests/src/Unit/Service/HeatmapScreenshotServiceTest.php` | Unit | Lógica de cache, validez, cleanup |
| 2 | `tests/src/Unit/HeatmapCronTest.php` | Unit | State API, thresholds, rate limiting |
| 5 | `tests/src/Unit/Service/PixelHealthCheckServiceTest.php` | Unit | Detección inactivos, test events |
| 5 | `tests/src/Unit/Service/ExperimentOrchestratorServiceTest.php` | Unit | Auto-winner logic, sample size check |

**Patrón de mocking (PHP 8.4 compatible):**
```php
// ✅ CORRECTO — stdClass para campos
$field = (object) ['value' => 'active'];

// ✅ CORRECTO — willReturnMap para múltiples campos
$entity->method('get')->willReturnMap([
    ['status', (object) ['value' => 'active']],
    ['tenant_id', (object) ['target_id' => 123]],
]);
```

---

## 11. Aprendizajes Críticos Aplicados

Estos aprendizajes provienen de errores reales documentados en el proyecto y se aplican obligatoriamente en cada fase de este plan.

| Fecha | Código | Aprendizaje | Fases Afectadas | Consecuencia si se ignora |
|---|---|---|---|---|
| 2026-01-15 | DRUPAL11-001 | No redeclarar propiedades heredadas en Controllers | 1, 3, 5, 6 | Fatal error PHP 8.4 |
| 2026-01-15 | DRUPAL11-002 | Usar `installEntityType()` en vez de `applyUpdates()` | 6 | Error Drupal 11 en updates |
| 2026-01-19 | ENTITY-001 | 4 archivos YAML obligatorios para navegación de entidades | 6 | Entidad sin acceso en admin |
| 2026-01-24 | API-NAMING-001 | No usar `create()` como nombre de endpoint — usar `store()` | 5, 6 | Conflicto con ContainerInjectionInterface |
| 2026-01-26 | SCSS-001 | Cada parcial SCSS debe declarar `@use` propio | 0, 3 | Variables indefinidas en compilación |
| 2026-01-26 | SCSS-019 | Usar `color.scale()` en vez de `darken()`/`lighten()` | 0, 3 | Warning Dart Sass deprecated |
| 2026-02-02 | FRONTEND-001 | Sincronizar 3 capas: controller + preprocess_html + twig | 3 | Body class faltante, layout roto |
| 2026-02-02 | ECA-001 | Usar hooks nativos, no ECA YAML | 2, 4, 5 | Automatización no versionable |
| 2026-02-05 | BILLING-007 | stdClass para mocking en PHP 8.4, no propiedades dinámicas | 1, 2, 5 | Tests fallan silenciosamente |
| 2026-02-07 | CRED-005 | WCAG 2.1 AA obligatorio: focus-visible, prefers-reduced-motion | 0, 3 | Accesibilidad no cumplida |
| 2026-02-09 | LIBRARY-001 | Incluir `core/drupal`, `core/once`, `core/drupal.dialog.ajax` | 3 | Behaviors no ejecutan, modales rotos |

---

## 12. Estimaciones y Roadmap

### Resumen por Fase

| Fase | Período | Horas Min | Horas Max | Prioridad | Spec | Notas |
|---|---|---|---|---|---|---|
| Fase 0 | Inmediato | 0.5h | 0.5h | P0 | 20260130c | 2 variantes SCSS, verificación visual |
| Fase 1 | Semana 1 | 12h | 15h | P1 | 20260130a | QueueWorker + ScreenshotService + package.json |
| Fase 2 | Semana 1 | 3h | 4h | P1 | 20260130a | hook_cron: agregación, cleanup, anomalías |
| Fase 3 | Semana 2 | 10h | 14h | P2 | 20260130a | Dashboard Twig + JS + SCSS |
| Fase 4 | Semana 2-3 | 10h | 15h | P2 | 20260130b | hook_cron: analytics, pixels, ab_testing |
| Fase 5 | Semana 3 | 10h | 15h | P2 | 20260130b | HealthCheck + AutoWinner services |
| Fase 6 | Semana 4-6 | 40h | 50h | P3 | 20260130b | Matomo self-hosted (evaluar necesidad) |
| **TOTAL** | **6 semanas** | **85.5h** | **113.5h** | — | — | — |

### Dependencias Críticas

```
Fase 0 (Premium Preview SCSS) — Sin dependencias, ejecutar inmediatamente
  │
  ├── Fase 1 (QueueWorker + Screenshot)
  │     └── Fase 2 (Hooks cron heatmap) ← Depende de: servicios de Fase 1
  │           └── Fase 3 (Dashboard heatmap) ← Depende de: datos de Fase 2
  │
  ├── Fase 4 (Hooks cron tracking) — Puede ejecutarse en paralelo con Fase 1-3
  │     └── Fase 5 (HealthCheck + AutoWinner) ← Depende de: hooks de Fase 4
  │
  └── Fase 6 (Matomo) — Independiente, P3, puede ejecutarse en cualquier momento
```

### Criterios de Aceptación Global

Cada fase se considera completada cuando:

- [ ] Código PHP con `declare(strict_types=1)` y sin redeclaración de propiedades
- [ ] SCSS compilado con Dart Sass sin warnings
- [ ] Solo usa CSS Custom Properties `var(--ej-*, $fallback)` — cero hex hardcoded
- [ ] Todos los textos de UI traducibles con `t()` / `{% trans %}` / `Drupal.t()`
- [ ] Iconos via `jaraba_icon()` — cero emojis Unicode
- [ ] Tests unitarios pasando (`phpunit`)
- [ ] Cache limpia (`drush cr`) sin errores
- [ ] Verificación visual en `https://jaraba-saas.lndo.site/`
- [ ] WCAG 2.1 AA: focus-visible, contraste, keyboard nav
- [ ] Responsive: mobile-first, funcional en 320px+

---

## 13. Registro de Cambios

| Fecha | Versión | Descripción |
|---|---|---|
| 2026-02-12 | 1.0.0 | **Creación inicial:** Auditoría exhaustiva de 3 specs (20260130a/b/c), identificación de gaps, plan de 7 fases con estimación 85.5-113.5h. Decisión arquitectónica: React → Drupal nativo para dashboard de heatmaps. Inclusión de 13 directrices de obligado cumplimiento y 11 aprendizajes críticos aplicados. |

---

*— Fin del Documento —*

*Jaraba Impact Platform | Plan de Cierre de Gaps Specs 20260130 v1.0.0 | Febrero 2026*
