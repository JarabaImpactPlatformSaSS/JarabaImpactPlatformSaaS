# Aprendizaje #208: Migración a Servidor Dedicado IONOS AE12-128

**Fecha**: 2026-03-20
**Contexto**: Migración de shared hosting a servidor dedicado
**Severidad**: P0 — Infraestructura core

---

## Lecciones aprendidas

### 1. NGINX-CSS-AGGREGATION-001: CSS/JS agregados requieren `try_files` + `@drupal` fallback
**Problema**: Nginx con `try_files $uri /index.php?$query_string` para `/sites/default/files/` causaba 400 "host not valid" al generar CSS agregados, porque `fastcgi_param SERVER_NAME` no se pasaba correctamente.

**Solución**: Usar patrón `try_files $uri @drupal` con `location @drupal { rewrite ^ /index.php last; }` + `fastcgi_param SERVER_NAME $host` en el bloque PHP.

**Golden Rule #146**: En Nginx con Drupal, SIEMPRE incluir `fastcgi_param SERVER_NAME $host` — sin esto, trusted_host_patterns rechaza peticiones internas de CSS/JS aggregation.

### 2. SETTINGS-PRODUCTION-INCLUDE-001: settings.production.php no se incluye automáticamente
**Problema**: `settings.php` incluye `settings.env.php`, `settings.secrets.php`, `settings.ai-queues.php` y `settings.local.php`. Pero `settings.production.php` NO está en la cadena de includes — requiere inclusión explícita desde `settings.local.php`.

**Solución**: En `settings.local.php` del servidor dedicado:
```php
$_prod = dirname(__DIR__, 3) . '/config/deploy/settings.production.php';
if (file_exists($_prod)) { include $_prod; }
```

**Golden Rule #147**: La variable `$app_root` no está disponible en `settings.local.php`. Usar `dirname(__DIR__, 3)` para navegar al root del proyecto.

### 3. IONOS-FIREWALL-TWO-LAYER-001: IONOS tiene firewall de infraestructura + UFW
**Problema**: Configuramos SSH en puerto 2222 con UFW, pero IONOS CloudPanel tiene su propio firewall que solo permitía 22/80/443. Nos bloqueamos.

**Solución**: Añadir puerto 2222 en IONOS CloudPanel → Red → Políticas de firewall → política "Linux". Reiniciar servidor para aplicar.

**Golden Rule #148**: En IONOS dedicados, SIEMPRE abrir puertos primero en CloudPanel firewall ANTES de cambiar la configuración de SSH/UFW interna.

### 4. CSS-AGGREGATION-STALE-HASH-001: Hashes de CSS importados de otro servidor no coinciden
**Problema**: Tras importar BD, Drupal generaba URLs con hashes del servidor viejo que no existían en disco del nuevo.

**Solución**: Limpiar `key_value` + `rm -rf files/css/* files/js/* files/php/` + `redis FLUSHALL` + `drush cr`.

### 5. DEPLOY-KEY-VS-SSH-KEY-001: Deploy key GitHub es diferente de la clave SSH del servidor
**Problema**: La clave SSH para acceder al servidor (`jaraba-dedicated`) no tiene acceso a GitHub. Se necesita una deploy key separada (`github-deploy`).

**Solución**: Generar clave ed25519 específica en el servidor, añadirla como deploy key read-only en GitHub, configurar `~/.ssh/config` para usarla con github.com.

---

## Decisiones de diseño

- **Stack nativo** (no Docker): Nginx + PHP-FPM + MariaDB + Redis directos en Ubuntu. Simplicidad operativa, máximo rendimiento NVMe.
- **Rama separada** para workflows: `infra/dedicated-migration` se mergea a main solo en el cutover. Protege al otro agente.
- **OPcache validate_timestamps=0**: Requiere `systemctl reload php8.4-fpm` en cada deploy. Máximo rendimiento pero sin hot-reload.
- **Redis sessions (database 1)**: Sesiones PHP en Redis, no en disco. Elimina problemas de sesiones perdidas entre workers.
