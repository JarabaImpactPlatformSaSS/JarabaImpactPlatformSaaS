# Plan de Implementacion: Google Indexing Automation — Clase Mundial

**Version:** 1.0.0
**Fecha:** 2026-03-26
**Autor:** Claude Opus 4.6
**Roles:** Arquitecto SaaS Senior, Ingeniero SEO/GEO Senior, Ingeniero Drupal Senior, Ingeniero Software Senior
**Estado:** PENDIENTE DE IMPLEMENTACION
**Prerrequisito:** `20260326b-Auditoria_SEO_Google_Search_Console_7_Fixes_v1_Claude.md`
**Modulo principal:** `jaraba_insights_hub`
**Modulos afectados:** `jaraba_insights_hub`, `jaraba_page_builder`, `ecosistema_jaraba_core`

---

## Indice de Navegacion (TOC)

1. [Objetivos y Alcance](#1-objetivos-y-alcance)
2. [Principios Arquitectonicos](#2-principios-arquitectonicos)
3. [Pre-Implementacion: Checklist de Directrices](#3-pre-implementacion-checklist-de-directrices)
4. [Infraestructura Existente](#4-infraestructura-existente)
5. [Sprint A — P0: Post-Deploy Sitemap Submission](#5-sprint-a--p0-post-deploy-sitemap-submission)
   - 5.1 [OAuth Scope Upgrade](#51-oauth-scope-upgrade)
   - 5.2 [GoogleSeoNotificationService](#52-googleseonotificationservice)
   - 5.3 [Drush Command jaraba:seo:notify-google](#53-drush-command-jarabaseonotify-google)
   - 5.4 [Deploy Pipeline Integration](#54-deploy-pipeline-integration)
   - 5.5 [Configuracion y Schema](#55-configuracion-y-schema)
6. [Sprint B — P1: Content Change URL Notifications](#6-sprint-b--p1-content-change-url-notifications)
   - 6.1 [Google Indexing API Credentials](#61-google-indexing-api-credentials)
   - 6.2 [SeoNotificationLog Entity](#62-seonotificationlog-entity)
   - 6.3 [Queue Worker SeoUrlNotificationWorker](#63-queue-worker-seourlnotificationworker)
   - 6.4 [Entity Hooks para Content Changes](#64-entity-hooks-para-content-changes)
   - 6.5 [URL Resolution Multi-Domain](#65-url-resolution-multi-domain)
7. [Sprint C — P2: Dashboard Monitoring](#7-sprint-c--p2-dashboard-monitoring)
   - 7.1 [API Endpoint Status](#71-api-endpoint-status)
   - 7.2 [Dashboard Integration en Insights Hub](#72-dashboard-integration-en-insights-hub)
   - 7.3 [Alerting por Email](#73-alerting-por-email)
8. [Medidas de Salvaguarda](#8-medidas-de-salvaguarda)
9. [Tabla de Correspondencia: Specs a Archivos](#9-tabla-de-correspondencia-specs-a-archivos)
10. [Tabla de Cumplimiento de Directrices](#10-tabla-de-cumplimiento-de-directrices)
11. [Verificacion Post-Implementacion (RUNTIME-VERIFY-001)](#11-verificacion-post-implementacion-runtime-verify-001)
12. [Testing Strategy](#12-testing-strategy)
13. [Variables de Entorno y Secrets](#13-variables-de-entorno-y-secrets)
14. [Glosario](#14-glosario)

---

## 1. Objetivos y Alcance

### Objetivo

Automatizar la notificacion a Google Search Console tras cada deploy y tras cada publicacion de contenido, eliminando la dependencia manual del administrador para acelerar la indexacion. Un SaaS de clase mundial con IA nativa y proactiva no depende de intervenciones humanas para tareas de SEO operativo.

### Alcance

| Dimension | Incluido | Excluido |
|-----------|----------|----------|
| APIs Google | Search Console Sitemaps API, Indexing API, URL Inspection API | Google Analytics Data API, Business Profile API |
| Dominios | 4 produccion | Dominios dev, subdominios tenant |
| Triggers | Post-deploy automatico, publicacion contenido, comando manual | Eliminacion masiva, migraciones |
| Entities | page_content, content_article | Todas las demas entities |
| Dashboard | Panel SEO en Insights Hub | Dashboard separado |

### Dependencias

| Componente | Estado | Ubicacion |
|------------|:------:|-----------|
| SearchConsoleService | Existente | `jaraba_insights_hub/src/Service/SearchConsoleService.php` |
| SearchConsoleConnection | Existente | `jaraba_insights_hub/src/Entity/SearchConsoleConnection.php` |
| OAuth2 Token Refresh | Existente | `SearchConsoleService::getAccessToken()` (protegido → hacer publico) |
| SitemapController | Existente | `jaraba_page_builder/src/Controller/SitemapController.php` |
| Deploy Pipeline | Existente | `.github/workflows/deploy.yml` |
| Queue System | Existente | Drupal Core Queue API |
| Drush Commands | Existente | `jaraba_page_builder/drush.services.yml` (patron) |

---

## 2. Principios Arquitectonicos

| # | Principio | Aplicacion |
|:-:|-----------|------------|
| 1 | **OPTIONAL-CROSSMODULE-001** | `GoogleSeoNotificationService` usa `@?jaraba_insights_hub.search_console` si cross-modulo |
| 2 | **SECRET-MGMT-001** | Credenciales Google via `getenv()` en `settings.secrets.php` |
| 3 | **PHANTOM-ARG-001** | Args services.yml coinciden exactamente con constructores |
| 4 | **UPDATE-HOOK-CATCH-001** | Todo `catch` usa `\Throwable`, nunca `\Exception` |
| 5 | **PRESAVE-RESILIENCE-001** | Entity hooks con `hasService()` + try-catch |
| 6 | **SUPERVISOR-SLEEP-001** | Queue worker con `cron.time = 30` (no hot-loop) |
| 7 | **TENANT-001** | Notificaciones filtradas por tenant/dominio |
| 8 | **SEO-DEPLOY-NOTIFY-001** | Nuevo: notificacion Google obligatoria post-deploy |

---

## 3. Pre-Implementacion: Checklist de Directrices

| Directriz | Verificado | Notas |
|-----------|:----------:|-------|
| `declare(strict_types=1)` en archivos nuevos | Pendiente | 8 archivos nuevos |
| PHPStan Level 6 | Pendiente | Analisis pre-commit |
| PHPCS Drupal + DrupalPractice | Pendiente | Pre-commit hook |
| No secrets en config/sync | Pendiente | Google OAuth via env vars |
| Services.yml args match constructor | Pendiente | PHANTOM-ARG-001 |
| Entity con AccessControlHandler | Pendiente | SeoNotificationLog |
| hook_update_N() si nueva entity | Pendiente | UPDATE-HOOK-REQUIRED-001 |
| Tests unitarios | Pendiente | 2 test classes |

---

## 4. Infraestructura Existente

### 4.1 SearchConsoleService (Lectura)

El servicio `jaraba_insights_hub.search_console` ya implementa:

- **OAuth2 completo**: Authorization URL, code exchange, token refresh.
- **Token storage**: Tokens en `SearchConsoleConnection` entity, multi-tenant.
- **API calls**: `POST /sites/{siteUrl}/searchAnalytics/query` para metricas.
- **Cron sync**: Diario 03:00-05:00 UTC via `hook_cron()`.
- **Scope actual**: `webmasters.readonly` (SOLO LECTURA).

### 4.2 Limitacion Actual

El scope `webmasters.readonly` NO permite:
- Enviar sitemaps (`PUT /sites/{siteUrl}/sitemaps/{feedpath}`).
- Solicitar indexacion via URL Inspection API (requiere `webmasters` read-write).

### 4.3 Google Indexing API

La **Google Indexing API** (`indexing.googleapis.com`) es un servicio separado:
- Endpoint: `POST /v3/urlNotifications:publish`.
- Body: `{"url": "https://...", "type": "URL_UPDATED" | "URL_DELETED"}`.
- Scope: `https://www.googleapis.com/auth/indexing`.
- Rate limit: 200 peticiones/dia/propiedad.
- Autenticacion: Oficialmente requiere **service account**, pero funciona con OAuth user tokens si el usuario es propietario verificado de la propiedad en Search Console.

---

## 5. Sprint A — P0: Post-Deploy Sitemap Submission

### 5.1 OAuth Scope Upgrade

**Archivo a modificar**: `web/modules/custom/jaraba_insights_hub/src/Form/SearchConsoleConnectForm.php`

Cambiar el scope de `webmasters.readonly` a `webmasters` + `indexing`:

```php
// ANTES:
'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',

// DESPUES (incluir indexing scope para Sprint B):
'scope' => implode(' ', [
    'https://www.googleapis.com/auth/webmasters',
    'https://www.googleapis.com/auth/indexing',
]),
```

**Accion post-deploy**: Re-autorizar las 4 conexiones de produccion en `/admin/config/services/insights-hub/connect`. Los refresh tokens existentes NO adquieren scopes nuevos automaticamente.

**Archivo a modificar**: `web/modules/custom/jaraba_insights_hub/src/Service/SearchConsoleService.php`

Cambiar `getAccessToken()` de `protected` a `public` para reutilizarlo en `GoogleSeoNotificationService` sin duplicar logica de refresh.

### 5.2 GoogleSeoNotificationService

**Archivo a crear**: `web/modules/custom/jaraba_insights_hub/src/Service/GoogleSeoNotificationService.php`

Servicio principal que gestiona:

1. **Sitemap submission** para los 4 dominios de produccion.
2. **URL notification** individual via Indexing API (Sprint B).
3. **Resolucion de conexion** por dominio.

**Constantes clave**:
- `PRODUCTION_DOMAINS`: Mapa dominio → group_id para los 4 dominios.
- `GSC_API_BASE`: `https://www.googleapis.com/webmasters/v3`.
- `INDEXING_API_URL`: `https://indexing.googleapis.com/v3/urlNotifications:publish`.
- `SITEMAP_PATHS`: `['sitemap.xml', 'sitemap-pages.xml', 'sitemap-articles.xml', 'sitemap-static.xml']`.

**Metodos publicos**:

| Metodo | Descripcion | Retorno |
|--------|-------------|---------|
| `submitAllSitemaps(array $domainFilter = [])` | Envia sitemaps de todos los dominios (o filtrados) | `['submitted' => int, 'errors' => int, 'details' => [...]]` |
| `submitSitemapsForDomain(string $domain, SearchConsoleConnection $connection)` | Envia los 4 sitemaps de un dominio | `['success' => [...], 'failed' => [...]]` |
| `notifyUrlChange(string $url, string $type = 'URL_UPDATED')` | Envia notificacion URL via Indexing API | `['status' => 'success'\|'failed', 'code' => int]` |

**Flujo de `submitAllSitemaps()`**:

```
1. Leer config seo_notification_enabled → si FALSE, return []
2. Leer config seo_notification_domains → lista de dominios
3. Para cada dominio:
   a. Buscar SearchConsoleConnection por site_url
   b. Obtener access token (via SearchConsoleService::getAccessToken)
   c. Para cada sitemap path:
      - PUT https://www.googleapis.com/webmasters/v3/sites/{encoded_url}/sitemaps/{sitemap_url}
      - Header: Authorization: Bearer {token}
   d. Registrar resultado (log)
4. Return summary
```

**Servicio registrado en `jaraba_insights_hub.services.yml`**:

```yaml
jaraba_insights_hub.seo_notification:
  class: Drupal\jaraba_insights_hub\Service\GoogleSeoNotificationService
  arguments:
    - '@http_client'
    - '@config.factory'
    - '@entity_type.manager'
    - '@logger.channel.jaraba_insights_hub'
    - '@jaraba_insights_hub.search_console'
```

Constructor: 5 parametros (PHANTOM-ARG-001 compliant).

### 5.3 Drush Command jaraba:seo:notify-google

**Archivos a crear**:
- `web/modules/custom/jaraba_insights_hub/src/Commands/SeoNotificationCommands.php`
- `web/modules/custom/jaraba_insights_hub/drush.services.yml`

**drush.services.yml**:
```yaml
services:
  jaraba_insights_hub.commands.seo_notification:
    class: Drupal\jaraba_insights_hub\Commands\SeoNotificationCommands
    arguments:
      - '@jaraba_insights_hub.seo_notification'
    tags:
      - { name: drush.command }
```

**Comando**:

| Aspecto | Detalle |
|---------|---------|
| Nombre | `jaraba:seo:notify-google` |
| Alias | `seo:notify` |
| Opciones | `--domain=nombre.com` (filtrar), `--dry-run` (simular) |
| Output | Tabla con dominio, sitemaps enviados, status |
| Exit code | 0 si >= 1 exito, 1 si todos fallan |

**Ejemplo de uso**:
```bash
$ drush jaraba:seo:notify-google
[OK] Sitemaps submitted for 4 domains:
 plataformadeecosistemas.com: 4/4 sitemaps ✓
 jarabaimpact.com:            4/4 sitemaps ✓
 pepejaraba.com:              4/4 sitemaps ✓
 plataformadeecosistemas.es:  4/4 sitemaps ✓

$ drush seo:notify --domain=jarabaimpact.com --dry-run
[DRY RUN] Would submit 4 sitemaps for jarabaimpact.com
```

### 5.4 Deploy Pipeline Integration

**Archivo a modificar**: `.github/workflows/deploy.yml`

Nuevo step despues de "Disable maintenance mode" y antes de "Infrastructure health check":

```yaml
- name: Notify Google Search Console (SEO-DEPLOY-NOTIFY-001)
  continue-on-error: true
  run: |
    ssh -p ${{ env.DEPLOY_PORT }} ${{ env.DEPLOY_USER }}@${{ secrets.DEPLOY_HOST }} << 'EOF'
      cd /var/www/jaraba
      echo "=== SEO: Submitting sitemaps to Google ==="
      vendor/bin/drush jaraba:seo:notify-google 2>&1 || echo "SEO notification failed (non-blocking)"
    EOF
```

**Clave**: `continue-on-error: true` asegura que un fallo de notificacion NUNCA bloquea un deploy.

### 5.5 Configuracion y Schema

**Archivo a modificar**: `web/modules/custom/jaraba_insights_hub/config/install/jaraba_insights_hub.settings.yml`

Anadir:
```yaml
seo_notification_enabled: true
seo_notification_domains:
  - plataformadeecosistemas.com
  - jarabaimpact.com
  - pepejaraba.com
  - plataformadeecosistemas.es
seo_notification_daily_limit: 180
```

**Archivo a modificar**: `web/modules/custom/jaraba_insights_hub/config/schema/jaraba_insights_hub.schema.yml`

Anadir al mapping existente:
```yaml
seo_notification_enabled:
  type: boolean
  label: 'Enable SEO notifications on deploy'
seo_notification_domains:
  type: sequence
  label: 'Production domains for SEO notifications'
  sequence:
    type: string
    label: 'Domain hostname'
seo_notification_daily_limit:
  type: integer
  label: 'Daily URL notification limit per domain'
```

---

## 6. Sprint B — P1: Content Change URL Notifications

### 6.1 Google Indexing API Credentials

**Estrategia dual**:

| Opcion | Mecanismo | Cuando Usar |
|:------:|-----------|-------------|
| A (preferida) | OAuth user tokens de SearchConsoleConnection con scope `indexing` | Si el usuario es propietario verificado de la propiedad |
| B (fallback) | Service account JSON file | Si Google rechaza OAuth para Indexing API |

**Opcion A**: No requiere configuracion adicional — reutiliza tokens existentes con el scope ampliado en Sprint A.

**Opcion B** (si necesario):
- Nueva variable de entorno: `GOOGLE_INDEXING_SERVICE_ACCOUNT_JSON` con path al JSON.
- Inyectar en `settings.secrets.php`: `$config['jaraba_insights_hub.settings']['indexing_service_account_path'] = getenv('GOOGLE_INDEXING_SERVICE_ACCOUNT_JSON');`
- Secret en GitHub Actions: `GOOGLE_INDEXING_SA_JSON` (base64 encoded).

### 6.2 SeoNotificationLog Entity

**Archivo a crear**: `web/modules/custom/jaraba_insights_hub/src/Entity/SeoNotificationLog.php`

Entity ContentEntity para tracking de notificaciones enviadas a Google.

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `id` | serial | Primary key |
| `uuid` | uuid | UUID unico |
| `domain` | string (255) | Dominio destino (ej: jarabaimpact.com) |
| `notification_type` | list_string | `sitemap_submit`, `url_updated`, `url_deleted` |
| `target_url` | string_long | URL del sitemap o contenido |
| `status` | list_string | `queued`, `success`, `failed` |
| `response_code` | integer | HTTP response code |
| `error_message` | string_long | Detalle del error (si aplica) |
| `entity_type_id` | string (64) | Tipo de entidad fuente (page_content, etc.) |
| `entity_id` | integer | ID de la entidad fuente |
| `created` | created | Timestamp de creacion |

**Usos**:
- Rate limiting: contar notificaciones por dominio/dia.
- Retry: encontrar notificaciones fallidas para reprocesar.
- Dashboard: mostrar estado en Insights Hub.

**Requiere**: `hook_update_N()` con `EntityDefinitionUpdateManager::installEntityType()` (UPDATE-HOOK-REQUIRED-001).

### 6.3 Queue Worker SeoUrlNotificationWorker

**Archivo a crear**: `web/modules/custom/jaraba_insights_hub/src/Plugin/QueueWorker/SeoUrlNotificationWorker.php`

```
@QueueWorker(
  id = "seo_url_notification",
  title = "SEO URL Notification Worker",
  cron = {"time" = 30}
)
```

**Flujo por item**:
```
1. Extraer datos: url, type (URL_UPDATED/URL_DELETED), entity_type, entity_id
2. Determinar dominio del URL
3. Consultar SeoNotificationLog: contar notificaciones hoy para ese dominio
4. SI >= daily_limit (180):
   - throw SuspendQueueException (reencolar para proximo cron)
5. Llamar GoogleSeoNotificationService::notifyUrlChange(url, type)
6. Crear SeoNotificationLog con resultado
7. SI fallo HTTP 429 (rate limit):
   - throw RequeueException (reintentar mas tarde)
```

**Rate limiting**: 180 peticiones/dia/propiedad (safety margin del 10% bajo el limite de 200 de Google).

### 6.4 Entity Hooks para Content Changes

**Archivo a modificar**: `web/modules/custom/jaraba_insights_hub/jaraba_insights_hub.module`

Nuevos hooks:

```php
function jaraba_insights_hub_entity_insert(EntityInterface $entity): void
function jaraba_insights_hub_entity_update(EntityInterface $entity): void
function jaraba_insights_hub_entity_delete(EntityInterface $entity): void
```

Cada hook llama a `_jaraba_insights_hub_queue_url_notification($entity, $type)` que:

1. Verifica que el entity type es elegible (`page_content`, `content_article`).
2. Verifica que el contenido esta publicado (`status = 1`).
3. Lee config `seo_notification_enabled`.
4. Resuelve la URL absoluta del contenido (ver 6.5).
5. Encola en `seo_url_notification` queue.

**PRESAVE-RESILIENCE-001**: Todo el bloque envuelto en try-catch con `\Throwable`.

### 6.5 URL Resolution Multi-Domain

**Problema**: Cuando contenido se guarda desde el admin, `$entity->toUrl('canonical', ['absolute' => TRUE])` resuelve al dominio del request actual (SaaS hub), NO al dominio del tenant.

**Solucion**: Resolver el dominio correcto desde el `tenant_id`/`group_id` de la entidad:

```
1. Obtener tenant_id de la entidad
2. Buscar en seo_notification_domains config el dominio del tenant
3. Construir URL: https://{domain}/{langcode}/{path_alias}
4. Fallback: usar toUrl() si no se puede resolver dominio
```

Esto se implementa como metodo privado `resolveEntityUrl()` en el helper del `.module`.

---

## 7. Sprint C — P2: Dashboard Monitoring

### 7.1 API Endpoint Status

**Archivo a crear**: `web/modules/custom/jaraba_insights_hub/src/Controller/SeoNotificationApiController.php`

**Ruta**: `GET /api/v1/insights/seo-notifications/status`

**Response JSON**:
```json
{
  "domains": {
    "plataformadeecosistemas.com": {
      "last_sitemap_submit": "2026-03-26T14:30:00Z",
      "last_sitemap_status": "success",
      "url_notifications_today": 12,
      "url_notifications_quota": 180,
      "recent_failures": 0
    }
  },
  "global": {
    "total_notifications_24h": 45,
    "success_rate": 97.8,
    "next_cron_window": "2026-03-27T03:00:00Z"
  }
}
```

### 7.2 Dashboard Integration en Insights Hub

Panel nuevo en el dashboard de `/insights` con:

- Semaforo por dominio (verde/amarillo/rojo segun ultimo estado).
- Barra de progreso de cuota diaria (notificaciones usadas / 180).
- Tabla de ultimas 10 notificaciones con status.
- Boton "Notify Now" para trigger manual (llama al drush command via AJAX).

### 7.3 Alerting por Email

Extension del `hook_mail()` existente en `jaraba_insights_hub.module` con nuevo tipo `seo_notification_alert`:

- Trigger: >= 5 fallos consecutivos para un dominio.
- Destinatario: admin email del sitio.
- Contenido: dominio afectado, ultimo error, accion sugerida.

---

## 8. Medidas de Salvaguarda

| # | Riesgo | Mitigacion | Regla |
|:-:|--------|-----------|-------|
| 1 | Re-autorizacion OAuth falla | Documentar proceso, admin UI existente | — |
| 2 | Google rechaza OAuth para Indexing API | Implementar fallback service account (6.1 Opcion B) | — |
| 3 | Rate limit excedido (200/dia) | Safety margin 180/dia + SuspendQueueException | — |
| 4 | Deploy falla por notificacion SEO | `continue-on-error: true` en deploy.yml | SEO-DEPLOY-NOTIFY-001 |
| 5 | URL resolucion incorrecta en admin | Resolver dominio desde tenant_id, no request | TENANT-001 |
| 6 | Token expirado sin refresh | Marcar conexion `expired`, alertar admin | — |
| 7 | Notificaciones duplicadas | Deduplicar por URL+tipo en cola (24h window) | — |
| 8 | SeoNotificationLog crece sin limite | Cleanup cron: eliminar registros > 90 dias | — |
| 9 | Validador de regresion | Nuevo check en validate-seo-multi-domain.php (CHECK 18+) | — |

---

## 9. Tabla de Correspondencia: Specs a Archivos

### Archivos a Crear (8)

| Spec | Archivo | Sprint |
|------|---------|:------:|
| SP-01 | `jaraba_insights_hub/src/Service/GoogleSeoNotificationService.php` | P0 |
| SP-02 | `jaraba_insights_hub/src/Commands/SeoNotificationCommands.php` | P0 |
| SP-03 | `jaraba_insights_hub/drush.services.yml` | P0 |
| SP-04 | `jaraba_insights_hub/src/Entity/SeoNotificationLog.php` | P1 |
| SP-05 | `jaraba_insights_hub/src/Plugin/QueueWorker/SeoUrlNotificationWorker.php` | P1 |
| SP-06 | `jaraba_insights_hub/src/Controller/SeoNotificationApiController.php` | P2 |
| SP-07 | `jaraba_insights_hub/tests/src/Unit/Service/GoogleSeoNotificationServiceTest.php` | P0 |
| SP-08 | `jaraba_insights_hub/tests/src/Unit/Commands/SeoNotificationCommandsTest.php` | P0 |

### Archivos a Modificar (8)

| Spec | Archivo | Cambio | Sprint |
|------|---------|--------|:------:|
| SM-01 | `jaraba_insights_hub/src/Service/SearchConsoleService.php` | `getAccessToken()` protected → public | P0 |
| SM-02 | `jaraba_insights_hub/src/Form/SearchConsoleConnectForm.php` | OAuth scope upgrade | P0 |
| SM-03 | `jaraba_insights_hub/jaraba_insights_hub.services.yml` | Registrar seo_notification service | P0 |
| SM-04 | `jaraba_insights_hub/config/schema/jaraba_insights_hub.schema.yml` | Nuevos campos config | P0 |
| SM-05 | `jaraba_insights_hub/config/install/jaraba_insights_hub.settings.yml` | Valores default | P0 |
| SM-06 | `.github/workflows/deploy.yml` | Step post-deploy notification | P0 |
| SM-07 | `jaraba_insights_hub/jaraba_insights_hub.module` | Entity hooks + mail type | P1/P2 |
| SM-08 | `jaraba_insights_hub/jaraba_insights_hub.routing.yml` | Ruta API status | P2 |

---

## 10. Tabla de Cumplimiento de Directrices

| Directriz | Descripcion | Cumplimiento Previsto |
|-----------|-------------|----------------------|
| PHANTOM-ARG-001 | Args services.yml = constructor | GoogleSeoNotificationService: 5 args, SeoNotificationCommands: 1 arg |
| OPTIONAL-CROSSMODULE-001 | Cross-modulo con `@?` | N/A (todo dentro de jaraba_insights_hub) |
| SECRET-MGMT-001 | Secrets via getenv() | OAuth tokens en entity, service account path via env var |
| UPDATE-HOOK-CATCH-001 | catch(\Throwable) | En todos los catch blocks |
| PRESAVE-RESILIENCE-001 | Entity hooks con try-catch | En _queue_url_notification() |
| UPDATE-HOOK-REQUIRED-001 | hook_update_N() para nueva entity | SeoNotificationLog requiere installEntityType() |
| AUDIT-CONS-001 | Entity con AccessControlHandler | SeoNotificationLog con ACH |
| ENTITY-001 | EntityOwnerTrait si aplica | SeoNotificationLog no tiene owner (log de sistema) |
| SUPERVISOR-SLEEP-001 | Workers sin hot-loop | QueueWorker cron.time = 30 |
| TENANT-001 | Queries filtradas por tenant | Logs por dominio, no por tenant directamente |
| CSS-VAR-ALL-COLORS-001 | Colores via var(--ej-*) | En template del dashboard (P2) |
| ROUTE-LANGPREFIX-001 | URLs via Url::fromRoute() | Para URL resolution de entities |
| ENTITY-PREPROCESS-001 | Preprocess hook si view mode | N/A (SeoNotificationLog sin view mode) |

---

## 11. Verificacion Post-Implementacion (RUNTIME-VERIFY-001)

### Sprint A (P0)

| # | Verificacion | Comando/Metodo |
|:-:|-------------|----------------|
| 1 | Drush command existe | `drush list --filter=jaraba:seo` |
| 2 | Dry-run funciona | `drush seo:notify --dry-run` |
| 3 | Sitemap submission exitoso | `drush seo:notify --domain=jarabaimpact.com` (con conexion activa) |
| 4 | Deploy incluye step | `grep 'notify-google' .github/workflows/deploy.yml` |
| 5 | Config schema valido | `drush config:validate` |
| 6 | PHPStan L6 | `phpstan analyse` archivos nuevos |
| 7 | Tests pasan | `phpunit --filter GoogleSeoNotification` |

### Sprint B (P1)

| # | Verificacion | Comando/Metodo |
|:-:|-------------|----------------|
| 8 | Entity instalada | `drush entity:updates` (sin pending) |
| 9 | Queue worker registrado | `drush queue:list \| grep seo_url_notification` |
| 10 | Publicar contenido → item en cola | Crear page_content, verificar queue |
| 11 | Rate limiting funciona | Enviar 181 notificaciones, verificar suspension |

### Sprint C (P2)

| # | Verificacion | Comando/Metodo |
|:-:|-------------|----------------|
| 12 | API endpoint responde | `curl /api/v1/insights/seo-notifications/status` |
| 13 | Dashboard muestra datos | Visitar /insights, verificar panel SEO |
| 14 | Alerting dispara | Simular 5 fallos consecutivos |

---

## 12. Testing Strategy

### Unit Tests (Sprint A)

**GoogleSeoNotificationServiceTest.php**:

| Test | Descripcion |
|------|-------------|
| `testSubmitAllSitemaps_Success` | 4 dominios, todos responden 200 |
| `testSubmitAllSitemaps_PartialFailure` | 1 dominio falla, 3 exito |
| `testSubmitAllSitemaps_NoConnections` | Sin conexiones activas, return 0 |
| `testSubmitAllSitemaps_ExpiredToken` | Token refresh automatico |
| `testSubmitAllSitemaps_DomainFilter` | Solo dominio filtrado |
| `testNotifyUrlChange_Success` | URL notification 200 |
| `testNotifyUrlChange_RateLimit` | Respeta limite diario |
| `testGetConnectionForDomain_NotFound` | Return null, log warning |

**SeoNotificationCommandsTest.php**:

| Test | Descripcion |
|------|-------------|
| `testNotifyGoogle_AllDomains` | Llama servicio sin filtro |
| `testNotifyGoogle_SingleDomain` | Llama servicio con filtro |
| `testNotifyGoogle_DryRun` | No llama servicio |

### Patron de Tests

- `PHPUnit\Framework\TestCase` (no Kernel, no Functional).
- Mocks de `ClientInterface`, `ConfigFactoryInterface`, `EntityTypeManagerInterface`, `LoggerInterface`, `SearchConsoleService`.
- Namespace: `Drupal\Tests\jaraba_insights_hub\Unit\Service`.
- Group: `jaraba_insights_hub`.

---

## 13. Variables de Entorno y Secrets

### Variables Existentes (sin cambios)

| Variable | Proposito | Ubicacion |
|----------|-----------|-----------|
| `SOCIAL_AUTH_GOOGLE_CLIENT_ID` | OAuth client ID | settings.secrets.php |
| `SOCIAL_AUTH_GOOGLE_CLIENT_SECRET` | OAuth client secret | settings.secrets.php |

### Variables Nuevas (si Opcion B en 6.1)

| Variable | Proposito | Sprint | Ubicacion |
|----------|-----------|:------:|-----------|
| `GOOGLE_INDEXING_SERVICE_ACCOUNT_JSON` | Path a JSON de service account | P1 | settings.secrets.php |

### GitHub Secrets Nuevos (si Opcion B)

| Secret | Proposito | Sprint |
|--------|-----------|:------:|
| `GOOGLE_INDEXING_SA_JSON` | Service account JSON (base64) | P1 |

---

## 14. Glosario

| Termino | Definicion | Contexto |
|---------|-----------|----------|
| **GSC** | Google Search Console | Plataforma de Google para monitorizar indexacion y rendimiento de busqueda |
| **Indexing API** | Google Indexing API v3 | API para notificar a Google sobre cambios en URLs (publish/delete) |
| **URL Inspection API** | API para inspeccionar estado de indexacion | Solo lectura, no permite solicitar indexacion |
| **Sitemap Submission** | Envio de sitemap via Search Console API | `PUT /sites/{siteUrl}/sitemaps/{feedpath}` |
| **Service Account** | Cuenta de servicio Google Cloud | Autenticacion maquina-a-maquina sin intervencion humana |
| **OAuth2** | Protocolo de autorizacion | Flujo: auth URL → code → token → refresh |
| **Rate Limit** | Limite de peticiones por periodo | Google Indexing API: 200/dia/propiedad |
| **Queue Worker** | Plugin Drupal para procesar colas | `@QueueWorker` con cron.time para ejecucion periodica |
| **SearchConsoleConnection** | Entity custom Drupal | Almacena tokens OAuth2 por tenant/dominio |
| **SeoNotificationLog** | Entity custom Drupal (nueva) | Tracking de notificaciones enviadas a Google |
| **PHANTOM-ARG-001** | Regla del proyecto | Args en services.yml deben coincidir con constructor PHP |
| **PRESAVE-RESILIENCE-001** | Regla del proyecto | Hooks con servicios opcionales deben usar hasService() + try-catch |
| **drush.services.yml** | Archivo Drupal | Registra comandos Drush como servicios tagged |
| **continue-on-error** | Directiva GitHub Actions | Step que no bloquea el pipeline si falla |
| **RequeueException** | Excepcion Drupal Queue | Devuelve item a la cola para reintento posterior |
| **SuspendQueueException** | Excepcion Drupal Queue | Suspende procesamiento de toda la cola hasta proximo cron |
| **safety margin** | Margen de seguridad | 180 vs 200 limite (10% margen) |
| **MetaSite** | Dominio de produccion con identidad propia | 4 dominios en MetaSiteResolverService::VARIANT_MAP |

---

## Registro de Cambios

| Version | Fecha | Autor | Cambios |
|---------|-------|-------|---------|
| 1.0.0 | 2026-03-26 | Claude Opus 4.6 | Documento inicial completo |

---

*Prerrequisito: Auditoria SEO (20260326b). Siguiente paso: implementar Sprint A (P0).*
