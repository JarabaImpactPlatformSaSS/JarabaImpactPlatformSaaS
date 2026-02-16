# Aprendizajes: Tenant Export + Backup Automatizado Diario

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-16 |

---

## Patron Principal

La sesion implemento el modulo `jaraba_tenant_export` completo (7 fases) cubriendo dos necesidades criticas: (A) exportacion self-service de datos por tenant para cumplir GDPR Art. 20 (portabilidad), y (B) backup automatizado diario via GitHub Actions independiente de deploys. El patron principal es la recoleccion de datos multi-seccion con graceful degradation per entity type, combinado con procesamiento asincrono via Queue API de Drupal.

---

## Aprendizajes Clave

### 1. Graceful degradation per entity type es esencial en plataformas modulares

**Situacion:** El TenantDataCollectorService debe recopilar datos de 6 grupos (core, analytics, knowledge, operational, vertical, files), pero no todos los entity types estan siempre instalados (ej. vertical-specific entities como `product_agro`).

**Aprendizaje:** Usar `try/catch` por cada entity type con `$this->entityTypeManager->hasDefinition()` antes de intentar cargar storage. Si un modulo no esta instalado, el collector simplemente omite esa seccion sin romper el flujo. Patron copiado de `GdprCommands.php`.

**Regla:** EXPORT-001: Todo collector de datos multi-entidad DEBE usar graceful degradation — nunca fallar por ausencia de un entity type opcional.

### 2. Queue API con re-enqueue para exports largos dentro de timeout IONOS

**Situacion:** IONOS impone un timeout de ~60 segundos en cron. Un export completo de tenant con analytics (50k rows), archivos, y multiples secciones puede tardar varios minutos.

**Aprendizaje:** El TenantExportWorker procesa UNA seccion por ejecucion de cron (respeta timeout 55s). Si quedan secciones, re-encola el item con `next_section` para continuar en la siguiente ejecucion. Max 3 reintentos antes de marcar `failed`. Este patron "section-by-section" es mas robusto que intentar todo de golpe.

**Regla:** EXPORT-002: QueueWorkers en IONOS DEBEN respetar el timeout de 60s — usar patron section-by-section con re-enqueue.

### 3. Rate limiting reutilizando el cache-backed RateLimiterService existente

**Situacion:** Los tenants pueden solicitar exportaciones repetidamente, sobrecargando el servidor.

**Aprendizaje:** Reutilizar `ecosistema_jaraba_core.rate_limiter` con tipo `'export'` e identificador `"tenant:{$tenantId}"`. El rate limit configurable (default 3/dia) se gestiona desde la UI de settings del modulo (`/admin/structure/tenant-export-record/settings`). Devolver `retry_after_formatted` en la respuesta API para UX.

**Regla:** EXPORT-003: Rate limiting SIEMPRE debe reutilizar RateLimiterService del core — nunca implementar contadores ad-hoc.

### 4. StreamedResponse para descargas ZIP grandes

**Situacion:** Los paquetes ZIP de export pueden ser de hasta 500MB. Cargar todo en memoria seria inviable.

**Aprendizaje:** Usar `StreamedResponse` de Symfony con `readfile()` dentro del callback. El `download_token` (UUID) es diferente del entity ID para evitar enumeracion. Validar que el token no este expirado y que el archivo fisico exista antes de iniciar el stream.

**Regla:** EXPORT-004: Descargas de archivos grandes SIEMPRE via StreamedResponse — nunca cargar en memoria. Tokens de descarga separados del entity ID.

### 5. daily-backup.yml independiente de deploy es critico para DR

**Situacion:** Antes, los backups solo se creaban antes de cada deploy (pre-deploy). Si no habia deploys durante dias, no existian copias recientes.

**Aprendizaje:** Un workflow cron independiente (03:00 UTC diario) con `drush sql-dump --gzip` + fallback `mysqldump` garantiza backups diarios sin depender de actividad de desarrollo. La rotacion inteligente (diarios <30d, semanales 30-84d, eliminar >84d) mantiene un historico razonable sin consumir excesivo espacio.

**Regla:** BACKUP-001: Backups NUNCA deben depender exclusivamente de deploys — implementar cron diario independiente.

### 6. ZIP manifest.json como contrato de datos para portabilidad

**Situacion:** El paquete ZIP debe ser auto-descriptivo para cumplir GDPR Art. 20 (portabilidad a otra plataforma).

**Aprendizaje:** Incluir un `manifest.json` en la raiz del ZIP con metadata (export_date, tenant_name, tenant_id, sections exportadas, total_files, total_records_per_section, format_version). Ademas un `README.txt` explicando la estructura. Cada seccion tiene su directorio (`core/`, `analytics/`, etc.) con JSON + CSV para datos tabulares grandes.

**Regla:** EXPORT-005: Todo paquete de export DEBE incluir manifest.json y README.txt — el paquete debe ser auto-descriptivo.

### 7. verify-backups.yml debe detectar ambos patrones de naming

**Situacion:** Al anadir daily backups con naming `db_daily_*`, el workflow verify-backups.yml existente solo buscaba `db_pre_deploy_*`.

**Aprendizaje:** Actualizar los patrones de busqueda en verify-backups.yml para detectar AMBOS tipos: `db_pre_deploy_*` y `db_daily_*`. El conteo total y la deteccion de ultimo backup deben considerar ambas fuentes.

**Regla:** BACKUP-002: Workflows de verificacion DEBEN detectar todos los patrones de naming de backups — no asumir una unica fuente.

### 8. ConfigFormBase vs FormBase para settings editables

**Situacion:** El modulo necesita settings configurables desde UI (expiration hours, rate limit, max size, etc.).

**Aprendizaje:** Usar `ConfigFormBase` con `getEditableConfigNames()` en lugar de `FormBase` simple. Esto permite que los settings sean editables via UI Drupal y se exporten con `config:export`. Los billing settings forms existentes usan `FormBase` minimalista (solo markup), pero tenant export necesita inputs reales — por tanto `ConfigFormBase` es el patron correcto aqui.

**Regla:** EXPORT-006: Settings con inputs reales DEBEN usar ConfigFormBase — reservar FormBase solo para Field UI anchor routes.

---

## Metricas de la Sesion

| Metrica | Valor |
|---------|-------|
| Fases implementadas | 7 (0-6) |
| Archivos creados | ~56 |
| Entity types nuevos | 1 (TenantExportRecord) |
| Services nuevos | 2 (DataCollector, ExportService) |
| QueueWorkers nuevos | 2 (Export, Cleanup) |
| Controllers nuevos | 2 (Page, API) |
| Templates Twig | 7 (1 page + 6 partials) |
| SCSS parciales | 6 |
| JS behaviors | 1 |
| SVG icons | 6 |
| API endpoints | 6 |
| Drush commands | 3 |
| GitHub Actions workflows | 1 nuevo + 1 actualizado |
| PHPUnit test suites | 8 (3 Unit + 3 Kernel + 2 Functional) |
| Reglas nuevas | EXPORT-001 a 006, BACKUP-001 a 002 |

---

## Patrones Reutilizables

| Patron | Origen | Reutilizado en |
|--------|--------|---------------|
| Graceful degradation per entity | GdprCommands.php | TenantDataCollectorService |
| QueueWorker + ContainerFactoryPluginInterface | VeriFactuRemisionQueueWorker | TenantExportWorker |
| Rate limiting cache-backed | RateLimiterService | TenantExportService.canRequestExport() |
| Audit log eventos | AuditLogService | TenantExportService.requestExport() |
| CSV generator 50k limit | AnalyticsExportController | TenantDataCollectorService.collectAnalyticsData() |
| Zero-region Twig + BEM | page--funding.html.twig | page--tenant-export.html.twig |
| hook_cron + State API guard | jaraba_funding.module | jaraba_tenant_export.module |
| SSH backup via webfactory | deploy.yml | daily-backup.yml |
| Slack alerts on failure | verify-backups.yml | daily-backup.yml |
