# Plan de Implementacion Tenant Export + Backups Automatizados v1.0

> **Fecha:** 2026-02-16
> **Ultima actualizacion:** 2026-02-16
> **Autor:** Claude Opus 4.6
> **Version:** 1.0.0
> **Estado:** Implementacion completada
> **Modulo principal:** `jaraba_tenant_export`

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Vision y Proposito](#11-vision-y-proposito)
  - [1.2 Relacion con la infraestructura existente](#12-relacion-con-la-infraestructura-existente)
  - [1.3 Patron arquitectonico de referencia](#13-patron-arquitectonico-de-referencia)
- [2. Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-tecnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
  - [3.1 Directriz: i18n](#31-directriz-i18n)
  - [3.2 Directriz: SCSS Federated Design Tokens](#32-directriz-scss-federated-design-tokens)
  - [3.3 Directriz: Dart Sass moderno](#33-directriz-dart-sass-moderno)
  - [3.4 Directriz: Frontend limpio sin regiones Drupal](#34-directriz-frontend-limpio-sin-regiones-drupal)
  - [3.5 Directriz: Body classes via hook_preprocess_html](#35-directriz-body-classes-via-hook_preprocess_html)
  - [3.7 Directriz: Entidades con Field UI y Views](#37-directriz-entidades-con-field-ui-y-views)
  - [3.8 Directriz: No hardcodear configuracion](#38-directriz-no-hardcodear-configuracion)
  - [3.9 Directriz: Parciales Twig reutilizables](#39-directriz-parciales-twig-reutilizables)
  - [3.10 Directriz: Seguridad](#310-directriz-seguridad)
  - [3.11 Directriz: Comentarios de codigo](#311-directriz-comentarios-de-codigo)
  - [3.12 Directriz: Iconos SVG duotone](#312-directriz-iconos-svg-duotone)
  - [3.14 Directriz: Automaciones via hooks Drupal](#314-directriz-automaciones-via-hooks-drupal)
- [4. Arquitectura del Modulo](#4-arquitectura-del-modulo)
  - [4.1 Nombre y ubicacion](#41-nombre-y-ubicacion)
  - [4.2 Dependencias](#42-dependencias)
  - [4.3 Estructura de directorios](#43-estructura-de-directorios)
  - [4.4 Compilacion SCSS](#44-compilacion-scss)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. FASE 0: Scaffolding + Entidad TenantExportRecord](#6-fase-0-scaffolding--entidad-tenantexportrecord)
- [7. FASE 1: TenantDataCollectorService](#7-fase-1-tenantdatacollectorservice)
- [8. FASE 2: TenantExportService + QueueWorkers](#8-fase-2-tenantexportservice--queueworkers)
- [9. FASE 3: API Controllers + Pagina Frontend](#9-fase-3-api-controllers--pagina-frontend)
- [10. FASE 4: SCSS + JavaScript + Iconos SVG](#10-fase-4-scss--javascript--iconos-svg)
- [11. FASE 5: Sistema de Backup Automatizado](#11-fase-5-sistema-de-backup-automatizado)
- [12. FASE 6: Testing + Documentacion](#12-fase-6-testing--documentacion)
- [13. Entidad TenantExportRecord — Detalle](#13-entidad-tenantexportrecord--detalle)
- [14. Estructura del ZIP Generado](#14-estructura-del-zip-generado)
- [15. API REST — Endpoints](#15-api-rest--endpoints)
- [16. Paleta de Colores y Design Tokens](#16-paleta-de-colores-y-design-tokens)
- [17. Patron de Iconos SVG](#17-patron-de-iconos-svg)
- [18. Orden de Implementacion Global](#18-orden-de-implementacion-global)
- [19. Edge Cases y Manejo de Errores](#19-edge-cases-y-manejo-de-errores)
- [20. Registro de Cambios](#20-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Vision y Proposito

El modulo `jaraba_tenant_export` aborda dos necesidades criticas:

**A) Per-Tenant Data Export** — Mecanismo self-service para que cada tenant pueda exportar la totalidad de sus datos (contenido, archivos, facturacion, analytics) como paquete ZIP descargable. Cumple GDPR Art. 20 (derecho a la portabilidad).

**B) Backup Automatizado Diario** — GitHub Actions workflow con cron diario, independiente del deploy, con rotacion inteligente, verificacion de integridad y alertas Slack.

### 1.2 Relacion con la infraestructura existente

| Componente existente | Relacion |
|---|---|
| `GdprCommands.php` | Patron de coleccion de datos por secciones con try/catch |
| `AnalyticsExportController.php` | CSV streaming con generators, 50k limit |
| `BillingInvoice.php` | ContentEntity con todos los handlers |
| `VeriFactuRemisionQueueWorker.php` | QueueWorker con ContainerFactoryPluginInterface |
| `RateLimiterService.php` | Rate limiting cache-backed |
| `AuditLogService.php` | Registro de eventos de auditoria |
| `TenantContextService.php` | Resolucion de tenant + storage metrics |
| `deploy.yml` | SSH setup con webfactory/ssh-agent |
| `verify-backups.yml` | Verificacion + Slack alerts |

### 1.3 Patron arquitectonico de referencia

- **Entidad:** Patron `BillingInvoice` — ContentEntity + AdminHtmlRouteProvider + AccessControlHandler + ListBuilder
- **Servicios:** DI via services.yml, logger channels dedicados
- **Queue:** Patron `VeriFactuRemisionQueueWorker` — ContainerFactoryPluginInterface
- **Frontend:** Patron `jaraba_funding` — Template zero-region + partials + hook_theme + hook_preprocess_html

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

| Especificacion | Seccion del Plan |
|---|---|
| GDPR Art. 20 (Portabilidad) | Fases 0-4 |
| AUDIT-CONS-003 (Envelope API) | Fase 3, API Controller |
| AUDIT-CONS-005 (tenant_id) | Fase 0, Entity fields |
| AUDIT-PERF-N06 (50k limit) | Fase 1, collectAnalyticsData |
| Directriz 3.1-3.14 | Seccion 3 |

---

## 3. Cumplimiento de Directrices del Proyecto

### 3.1 Directriz: i18n
Todos los textos en templates usan `{% trans %}`. JS usa `Drupal.t()`. Entity labels usan `@Translation()`.

### 3.2 Directriz: SCSS Federated Design Tokens
Variables locales `$export-*` como fallback para `var(--ej-*)`. Font: `var(--ej-font-family, 'Outfit', sans-serif)`. No `$ej-*` en modulo satelite.

### 3.3 Directriz: Dart Sass moderno
`@use` en lugar de `@import`. `color.adjust()` en vez de `darken()`/`lighten()`. `color-mix(in srgb, ...)` en vez de `rgba()`.

### 3.4 Directriz: Frontend limpio sin regiones Drupal
Template `page--tenant-export.html.twig` extiende `@claro/layout/page.html.twig`. Layout full-width, mobile-first.

### 3.5 Directriz: Body classes via hook_preprocess_html
`page--tenant-export` y `full-width-layout` via `jaraba_tenant_export_preprocess_html()`.

### 3.7 Directriz: Entidades con Field UI y Views
`TenantExportRecord` con `views_data`, `field_ui_base_route`, `AdminHtmlRouteProvider`.

### 3.8 Directriz: No hardcodear configuracion
Settings form en `/admin/structure/tenant-export-record/settings`. Config schema validado. 7 parametros editables.

### 3.9 Directriz: Parciales Twig reutilizables
6 parciales: `_export-header`, `_export-request-card`, `_export-history-list`, `_export-progress-bar`, `_export-download-card`, `_export-empty-state`.

### 3.10 Directriz: Seguridad
6 permisos granulares. Rate limiting por tenant. Download tokens UUID. Access control handler.

### 3.11 Directriz: Comentarios de codigo
PHPDoc en servicios y metodos publicos. Referencia a directrices y specs en annotations.

### 3.12 Directriz: Iconos SVG duotone
Iconos `export`, `archive`, `schedule` con variantes `-duotone`. 24x24 viewBox, 2 capas, `currentColor`.

### 3.14 Directriz: Automaciones via hooks Drupal
`hook_cron()` para cleanup con State API guard (6h). `hook_theme()` para templates. `hook_preprocess_html()` para body classes.

---

## 4. Arquitectura del Modulo

### 4.1 Nombre y ubicacion
`web/modules/custom/jaraba_tenant_export/`

### 4.2 Dependencias
- `ecosistema_jaraba_core` (RateLimiter, AuditLog, TenantContext)
- `drupal:views`

### 4.3 Estructura de directorios

```
jaraba_tenant_export/
├── jaraba_tenant_export.info.yml
├── jaraba_tenant_export.module
├── jaraba_tenant_export.install
├── jaraba_tenant_export.services.yml
├── jaraba_tenant_export.routing.yml
├── jaraba_tenant_export.permissions.yml
├── jaraba_tenant_export.libraries.yml
├── jaraba_tenant_export.links.menu.yml
├── jaraba_tenant_export.links.task.yml
├── jaraba_tenant_export.links.action.yml
├── config/install/jaraba_tenant_export.settings.yml
├── config/schema/jaraba_tenant_export.schema.yml
├── package.json
├── css/tenant-export.css
├── scss/
│   ├── main.scss
│   ├── _variables-export.scss
│   ├── _export-page.scss
│   ├── _export-card.scss
│   ├── _export-progress.scss
│   └── _export-history.scss
├── js/tenant-export-dashboard.js
├── src/
│   ├── Entity/
│   │   ├── TenantExportRecord.php
│   │   └── TenantExportRecordInterface.php
│   ├── Access/TenantExportRecordAccessControlHandler.php
│   ├── ListBuilder/TenantExportRecordListBuilder.php
│   ├── Form/
│   │   ├── TenantExportRecordForm.php
│   │   └── TenantExportSettingsForm.php
│   ├── Service/
│   │   ├── TenantDataCollectorService.php
│   │   └── TenantExportService.php
│   ├── Controller/
│   │   ├── TenantExportPageController.php
│   │   └── TenantExportApiController.php
│   ├── Plugin/QueueWorker/
│   │   ├── TenantExportWorker.php
│   │   └── TenantExportCleanupWorker.php
│   └── Commands/TenantExportCommands.php
├── templates/
│   ├── page--tenant-export.html.twig
│   └── partials/
│       ├── _export-header.html.twig
│       ├── _export-request-card.html.twig
│       ├── _export-history-list.html.twig
│       ├── _export-progress-bar.html.twig
│       ├── _export-download-card.html.twig
│       └── _export-empty-state.html.twig
└── tests/src/
    ├── Unit/
    │   ├── TenantDataCollectorServiceTest.php
    │   ├── TenantExportServiceTest.php
    │   └── TenantBackupServiceTest.php
    ├── Kernel/
    │   ├── TenantExportRecordEntityTest.php
    │   ├── TenantExportWorkerTest.php
    │   └── TenantExportCleanupWorkerTest.php
    └── Functional/
        ├── TenantExportPageTest.php
        └── TenantExportApiTest.php
```

### 4.4 Compilacion SCSS
```bash
lando ssh -c "cd /app/web/modules/custom/jaraba_tenant_export && \
  export NVM_DIR=\"\$HOME/.nvm\" && [ -s \"\$NVM_DIR/nvm.sh\" ] && . \"\$NVM_DIR/nvm.sh\" && \
  nvm use --lts && npm run build"
```

---

## 5. Estado por Fases

| Fase | Nombre | Estado |
|---|---|---|
| 0 | Scaffolding + Entidad | Completada |
| 1 | TenantDataCollectorService | Completada |
| 2 | TenantExportService + QueueWorkers | Completada |
| 3 | API Controllers + Frontend | Completada |
| 4 | SCSS + JS + SVG | Completada |
| 5 | Backup Automatizado | Completada |
| 6 | Testing + Documentacion | Completada |

---

## 6. FASE 0: Scaffolding + Entidad TenantExportRecord

Entidad ContentEntity con 17 campos, 6 permisos, Settings form con 7 parametros editables, AdminHtmlRouteProvider, AccessControlHandler, ListBuilder.

---

## 7. FASE 1: TenantDataCollectorService

Servicio que recopila datos en 6 grupos (Core, Analytics, Knowledge, Operational, Vertical, Files) con graceful degradation por entity type. Patron `GdprCommands.php`.

---

## 8. FASE 2: TenantExportService + QueueWorkers

Servicio orquestador: requestExport, processExport, buildZipPackage, getDownloadResponse, cleanupExpiredExports. TenantExportWorker (55s cron, 3 reintentos). TenantExportCleanupWorker (30s cron, cada 6h via State API).

---

## 9. FASE 3: API Controllers + Pagina Frontend

6 endpoints REST bajo `/api/v1/tenant-export/`. Pagina self-service en `/tenant/export`. Template zero-region con 6 parciales Twig.

---

## 10. FASE 4: SCSS + JavaScript + Iconos SVG

5 parciales SCSS, variables locales `$export-*`, BEM naming, mobile-first. JS con Drupal.behaviors, once(), polling de progreso. 6 iconos SVG (export, archive, schedule + duotone).

---

## 11. FASE 5: Sistema de Backup Automatizado

GitHub Actions workflow `daily-backup.yml`: cron 03:00 UTC, SSH backup, gzip integrity check, rotacion inteligente (diarios 30d, semanales 84d), alertas Slack. Actualizado `verify-backups.yml` para detectar `db_daily_*`. 3 Drush commands: `tenant-export:backup`, `tenant-export:cleanup`, `tenant-export:status`.

---

## 12. FASE 6: Testing + Documentacion

3 Unit tests, 3 Kernel tests, 2 Functional tests. Documentacion de implementacion.

---

## 13. Entidad TenantExportRecord — Detalle

| Campo | Tipo | Descripcion |
|---|---|---|
| tenant_id | entity_reference (group) | AUDIT-CONS-005 |
| tenant_entity_id | entity_reference (tenant) | Entidad tenant |
| requested_by | entity_reference (user) | Solicitante |
| export_type | list_string | full/partial/gdpr_portability |
| status | list_string | queued/collecting/packaging/completed/failed/expired/cancelled |
| progress | integer (0-100) | Porcentaje |
| current_phase | string | Fase actual |
| requested_sections | string_long (JSON) | Secciones |
| file_path | string | Ruta ZIP |
| file_size | integer | Bytes |
| file_hash | string | SHA-256 |
| section_counts | string_long (JSON) | Conteo por seccion |
| error_message | string_long | Error |
| expires_at | timestamp | Expiracion |
| download_token | string (UUID) | Token descarga |
| download_count | integer | Descargas |
| created/changed/completed_at | timestamp | Fechas |

---

## 14. Estructura del ZIP Generado

```
tenant_export_{nombre}_{fecha}.zip
├── manifest.json
├── README.txt
├── core/
│   ├── tenant.json
│   ├── billing_invoices.json
│   ├── whitelabel_config.json
│   └── verifactu_records.json
├── analytics/
│   ├── events.csv
│   └── dashboards.json
├── knowledge/
│   ├── tenant_documents.json
│   └── kb_articles.json
├── operational/
│   ├── audit_log.json
│   └── email_campaigns.json
├── vertical/
│   └── product_agro.json
└── files/
    ├── index.json
    └── (archivos originales)
```

---

## 15. API REST — Endpoints

| Metodo | Ruta | Descripcion |
|---|---|---|
| POST | `/api/v1/tenant-export/request` | Solicitar exportacion |
| GET | `/api/v1/tenant-export/{id}/status` | Estado y progreso |
| GET | `/api/v1/tenant-export/{token}/download` | Descargar ZIP |
| POST | `/api/v1/tenant-export/{id}/cancel` | Cancelar |
| GET | `/api/v1/tenant-export/history` | Historico |
| GET | `/api/v1/tenant-export/sections` | Secciones disponibles |

---

## 16. Paleta de Colores y Design Tokens

Heredada del Design System de Ecosistema Jaraba via CSS Custom Properties:
- `--ej-color-primary`: #2563eb
- `--ej-color-success`: #10b981
- `--ej-color-error`: #ef4444
- `--ej-color-warning`: #f59e0b
- `--ej-font-family`: 'Outfit', sans-serif

---

## 17. Patron de Iconos SVG

Nuevos iconos en `ecosistema_jaraba_core/images/icons/actions/`:
- `export.svg` + `export-duotone.svg`
- `archive.svg` + `archive-duotone.svg`
- `schedule.svg` + `schedule-duotone.svg`

Formato: 24x24 viewBox, 2 capas (background opacity 0.3 + main stroke), `currentColor`.

---

## 18. Orden de Implementacion Global

1. Fase 0: Module scaffolding + entity
2. Fase 1: Data collector service
3. Fase 2: Export service + queue workers
4. Fase 3: API controllers + templates
5. Fase 4: SCSS + JS + SVG
6. Fase 5: Daily backup workflow
7. Fase 6: Tests + documentation

---

## 19. Edge Cases y Manejo de Errores

| Escenario | Manejo |
|---|---|
| Tenant sin datos | ZIP con manifest vacio + README |
| >50k analytics_event | CSV chunked con limit en query |
| Requests concurrentes | Rate limiter bloquea (3/dia) |
| Fallo mid-export | QueueWorker reintenta max 3 veces, luego marca `failed` |
| Disco lleno | Exception capturada, status `failed` |
| Export expirado | Cleanup worker marca `expired` y borra ZIP |
| Modulo no instalado | try/catch graceful degradation en collector |

---

## 20. Registro de Cambios

| Fecha | Version | Descripcion |
|---|---|---|
| 2026-02-16 | 1.0.0 | Implementacion completa de las 7 fases |
