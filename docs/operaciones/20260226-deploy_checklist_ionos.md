# Deploy a Produccion — IONOS Dedicated L-16 NVMe

Jaraba Impact Platform — Stack completo para 3 meta-sitios + SaaS multi-tenant.

## Arquitectura de Dominios

| Dominio | Funcion | Tipo |
|---------|---------|------|
| `plataformadeecosistemas.es` | Meta-sitio corporativo PED S.L. | Meta-sitio |
| `pepejaraba.com` | Marca personal Pepe Jaraba | Meta-sitio |
| `jarabaimpact.com` | Jaraba Impact B2B | Meta-sitio |
| `plataformadeecosistemas.com` | SaaS base domain | Plataforma |
| `*.plataformadeecosistemas.com` | Subdominios tenant | Multi-tenant |

Todos los dominios apuntan a la misma instalacion Drupal 11 en `/var/www/jaraba/`.
La resolucion multi-tenant la maneja `MetaSiteResolverService` + Domain module.

---

## 1. Requisitos del Stack

| Componente | Version | Notas |
|------------|---------|-------|
| PHP | 8.4+ | Extensiones: mbstring, gd, pdo_mysql, opcache, redis, intl, zip |
| MariaDB | 10.11+ | `innodb_buffer_pool_size >= 2G` (128GB RAM disponible) |
| Redis | 7.4+ | Cache backend (default + bins: render, discovery, dynamic_page_cache, page) |
| Nginx | 1.24+ | Reverse proxy a PHP-FPM |
| PHP-FPM | 8.4 | Socket: `/run/php/php8.4-fpm.sock` |
| Composer | 2.x | `composer install --no-dev --optimize-autoloader` |
| Node.js | 20.x | Solo build SCSS (no runtime) |
| Certbot | Latest | Let's Encrypt + DNS challenge para wildcard |

---

## 2. DNS — Todos los Dominios

### 2.1 Meta-Sitios (registradores individuales)

| Dominio | Tipo | Valor | TTL |
|---------|------|-------|-----|
| `plataformadeecosistemas.es` | A | `212.227.141.42` | 300 |
| `www.plataformadeecosistemas.es` | CNAME | `plataformadeecosistemas.es` | 300 |
| `pepejaraba.com` | A | `212.227.141.42` | 300 |
| `www.pepejaraba.com` | CNAME | `pepejaraba.com` | 300 |
| `jarabaimpact.com` | A | `212.227.141.42` | 300 |
| `www.jarabaimpact.com` | CNAME | `jarabaimpact.com` | 300 |

### 2.2 SaaS Base Domain (plataformadeecosistemas.com)

| Dominio | Tipo | Valor | TTL |
|---------|------|-------|-----|
| `plataformadeecosistemas.com` | A | `212.227.141.42` | 300 |
| `www.plataformadeecosistemas.com` | CNAME | `plataformadeecosistemas.com` | 300 |
| `*.plataformadeecosistemas.com` | A | `212.227.141.42` | 300 |

> **CRITICO**: El registro wildcard `*.plataformadeecosistemas.com` es OBLIGATORIO
> para que los subdominios tenant (ej: `academia-talento.plataformadeecosistemas.com`)
> resuelvan al servidor. Sin este registro, los tenants no funcionan.

> **NOTA**: TTL a 300s durante migracion. Subir a 3600s tras verificar estabilidad.

---

## 3. Nginx — Configuracion

Los archivos de configuracion estan versionados en `config/deploy/`:

```
config/deploy/
  nginx-metasites.conf      # Vhosts para los 4 grupos de dominio
  nginx-jaraba-common.conf  # Snippet compartido (security headers, gzip, PHP-FPM, etc.)
  settings.production.php   # Settings de Drupal para produccion
```

### 3.1 Instalar snippet compartido

```bash
cp config/deploy/nginx-jaraba-common.conf /etc/nginx/snippets/jaraba-common.conf
```

Incluye: security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy,
Permissions-Policy), gzip, cache de assets estaticos (1 anio), clean URLs, PHP-FPM,
bloqueo de .ht/.git/install.php/update.php, private files, health endpoint.

### 3.2 Instalar vhosts

```bash
cp config/deploy/nginx-metasites.conf /etc/nginx/sites-available/jaraba-metasites.conf
ln -s /etc/nginx/sites-available/jaraba-metasites.conf /etc/nginx/sites-enabled/
```

### 3.3 Verificar y recargar

```bash
nginx -t && systemctl reload nginx
```

### 3.4 Contenido del vhost (resumen)

El archivo define 4 grupos de server blocks:

1. **plataformadeecosistemas.es** — www->non-www redirect + HTTPS main block
2. **pepejaraba.com** — www->non-www redirect + HTTPS main block
3. **jarabaimpact.com** — www->non-www redirect + HTTPS main block
4. **plataformadeecosistemas.com + *.plataformadeecosistemas.com** — www->non-www + wildcard HTTPS

Cada main block: `root /var/www/jaraba/web`, `include /etc/nginx/snippets/jaraba-common.conf`.

---

## 4. SSL — Certificados Let's Encrypt

### 4.1 Meta-sitios (HTTP challenge, automatico)

```bash
certbot --nginx \
  -d plataformadeecosistemas.es -d www.plataformadeecosistemas.es \
  -d pepejaraba.com -d www.pepejaraba.com \
  -d jarabaimpact.com -d www.jarabaimpact.com \
  -d plataformadeecosistemas.com -d www.plataformadeecosistemas.com
```

### 4.2 Wildcard para subdominios tenant (DNS challenge, OBLIGATORIO)

Los subdominios tenant (`*.plataformadeecosistemas.com`) requieren certificado
wildcard, que SOLO se puede obtener via DNS challenge:

```bash
certbot certonly --manual --preferred-challenges dns \
  -d "*.plataformadeecosistemas.com" \
  -d "plataformadeecosistemas.com"
```

Certbot pedira crear un registro TXT `_acme-challenge.plataformadeecosistemas.com`.
Para renovacion automatica, considerar plugin DNS del registrador:

```bash
# Ejemplo con Cloudflare DNS plugin:
certbot certonly --dns-cloudflare \
  --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
  -d "*.plataformadeecosistemas.com" \
  -d "plataformadeecosistemas.com"
```

> **NOTA**: Si el registrador de plataformadeecosistemas.com tiene API DNS
> (Cloudflare, Route53, DigitalOcean, etc.), usar el plugin correspondiente
> para renovacion automatica. Si no, la renovacion wildcard es manual cada 90 dias.

### 4.3 Verificar auto-renovacion

```bash
certbot renew --dry-run
systemctl enable certbot.timer
```

---

## 5. Drupal — Settings de Produccion

### 5.1 settings.secrets.php (variables de entorno)

Verificar que `/var/www/jaraba/web/sites/default/settings.secrets.php` tiene
todas las variables via `getenv()` (SECRET-MGMT-001):

```
DRUPAL_DB_HOST, DRUPAL_DB_NAME, DRUPAL_DB_USER, DRUPAL_DB_PASSWORD
REDIS_HOST, REDIS_PORT
GOOGLE_OAUTH_CLIENT_ID, GOOGLE_OAUTH_CLIENT_SECRET
LINKEDIN_OAUTH_CLIENT_ID, LINKEDIN_OAUTH_CLIENT_SECRET
MICROSOFT_OAUTH_CLIENT_ID, MICROSOFT_OAUTH_CLIENT_SECRET
SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD
RECAPTCHA_V3_SITE_KEY, RECAPTCHA_V3_SECRET_KEY
STRIPE_SECRET_KEY, STRIPE_PUBLIC_KEY, STRIPE_WEBHOOK_SECRET
ANTHROPIC_API_KEY, GEMINI_API_KEY
QDRANT_HOST, QDRANT_API_KEY
```

### 5.2 settings.production.php (configuracion no secreta)

Incluir desde `settings.local.php` en el servidor:

```php
// En /var/www/jaraba/web/sites/default/settings.local.php:
include $app_root . '/../config/deploy/settings.production.php';
```

Este archivo configura:
- `jaraba_base_domain` = `plataformadeecosistemas.com` (CRITICO para CNAME targets)
- `trusted_host_patterns` con TODOS los dominios de produccion
- Redis como cache backend (default + bins especificos)
- Agregacion CSS/JS activada
- Error level: hide
- File paths: private en `/var/www/jaraba/private`, temp en `/tmp`

### 5.3 Crear directorio private files

```bash
mkdir -p /var/www/jaraba/private
chown www-data:www-data /var/www/jaraba/private
chmod 750 /var/www/jaraba/private
```

---

## 6. Domain Entities (DOMAIN-ROUTE-CACHE-001)

**CRITICO**: Cada hostname DEBE tener su propia Domain entity en Drupal.
Sin ella, `RouteProvider` cachea rutas con clave equivocada en Redis y
los path processors NO se ejecutan en cache HIT.

### 6.1 Verificar Domain entities existentes

```bash
drush eval "
  \$storage = \Drupal::entityTypeManager()->getStorage('domain');
  foreach (\$storage->loadMultiple() as \$domain) {
    echo \$domain->id() . ' | ' . \$domain->getHostname() . ' | ' .
         (\$domain->isDefault() ? 'DEFAULT' : '-') . PHP_EOL;
  }
"
```

### 6.2 Domain entities requeridas

| ID | Hostname | Default | Scheme |
|----|----------|---------|--------|
| `plataformadeecosistemas_com` | `plataformadeecosistemas.com` | SI | https |
| `plataformadeecosistemas_es` | `plataformadeecosistemas.es` | NO | https |
| `pepejaraba_com` | `pepejaraba.com` | NO | https |
| `jarabaimpact_com` | `jarabaimpact.com` | NO | https |

> Los subdominios tenant NO necesitan Domain entities individuales —
> `MetaSiteResolverService` los resuelve dinamicamente via `Tenant.domain`
> o extraccion de prefijo de subdominio.

### 6.3 Crear Domain entities faltantes

```bash
# Ejemplo: crear Domain entity para jarabaimpact.com
drush eval "
  \$domain = \Drupal::entityTypeManager()->getStorage('domain')->create([
    'id' => 'jarabaimpact_com',
    'hostname' => 'jarabaimpact.com',
    'name' => 'Jaraba Impact B2B',
    'scheme' => 'https',
    'status' => TRUE,
    'weight' => 0,
    'is_default' => FALSE,
  ]);
  \$domain->save();
  echo 'Domain entity creada: ' . \$domain->id() . PHP_EOL;
"
```

Repetir para cada hostname faltante. Luego exportar config:

```bash
drush cex -y
```

---

## 7. SiteConfig y TenantThemeConfig

Cada meta-sitio necesita entidades de contenido que definen su identidad visual
y estructural. Verificar:

```bash
# SiteConfig entities (configuracion estructural)
drush eval "
  \$storage = \Drupal::entityTypeManager()->getStorage('site_config');
  foreach (\$storage->loadMultiple() as \$sc) {
    echo \$sc->id() . ' | ' . \$sc->label() . ' | ' . (\$sc->get('hostname')->value ?? 'N/A') . PHP_EOL;
  }
"

# TenantThemeConfig entities (configuracion visual)
drush eval "
  \$storage = \Drupal::entityTypeManager()->getStorage('tenant_theme_config');
  foreach (\$storage->loadMultiple() as \$ttc) {
    echo \$ttc->id() . ' | ' . \$ttc->label() . ' | ' . (\$ttc->get('hostname')->value ?? 'N/A') . PHP_EOL;
  }
"
```

Cada meta-sitio debe tener ambas entidades con su `hostname` correcto.
El sistema de theming usa UnifiedThemeResolverService con cascade de 5 niveles:
Platform -> Vertical -> Plan -> TenantThemeConfig -> SiteConfig (fallback).

---

## 8. Deploy Steps

### 8.1 Pre-Deploy (local)

```bash
# Compilar SCSS
cd web/themes/custom/ecosistema_jaraba_theme && npm run build && cd -

# Verificar tests
./vendor/bin/phpunit --testsuite Unit,Kernel

# Exportar config
drush cex -y

# Verificar integridad
bash scripts/validation/validate-all.sh --fast
```

### 8.2 Deploy (servidor)

```bash
# 1. Subir codigo
rsync -avz --delete \
  --exclude='.git' \
  --exclude='node_modules' \
  --exclude='web/sites/default/settings.local.php' \
  --exclude='web/sites/default/settings.secrets.php' \
  --exclude='.env' \
  ./ user@ionos:/var/www/jaraba/

# 2. En el servidor:
ssh user@ionos

cd /var/www/jaraba

# 3. Instalar dependencias
composer install --no-dev --optimize-autoloader

# 4. Migraciones + config import + cache rebuild
drush updb -y && drush cim -y && drush cr

# 5. Verificar estado
drush status
drush watchdog:show --count=10
```

### 8.3 Primer Deploy (setup adicional)

Solo la primera vez:

```bash
# Crear settings.local.php
cat > web/sites/default/settings.local.php << 'EOF'
<?php
// Production settings — IONOS Dedicated L-16 NVMe.
include $app_root . '/../config/deploy/settings.production.php';
EOF

# Instalar Nginx configs (ver seccion 3)
# Obtener certificados SSL (ver seccion 4)
# Crear Domain entities (ver seccion 6)

# PHP-FPM pool tuning (128GB RAM)
# /etc/php/8.4/fpm/pool.d/www.conf:
#   pm = dynamic
#   pm.max_children = 100
#   pm.start_servers = 20
#   pm.min_spare_servers = 10
#   pm.max_spare_servers = 40

# Redis config
# /etc/redis/redis.conf:
#   maxmemory 4gb
#   maxmemory-policy allkeys-lru

# Cron
echo "*/15 * * * * www-data cd /var/www/jaraba && drush cron 2>&1 | logger -t drupal-cron" \
  > /etc/cron.d/drupal-jaraba
```

---

## 9. Post-Deploy Verificacion

### 9.1 HTTP/HTTPS de todos los dominios

```bash
# Meta-sitios
curl -sI https://plataformadeecosistemas.es | head -5
curl -sI https://pepejaraba.com | head -5
curl -sI https://jarabaimpact.com | head -5

# SaaS base
curl -sI https://plataformadeecosistemas.com | head -5

# Redirects www -> non-www
curl -sI https://www.plataformadeecosistemas.es 2>&1 | grep -i location
curl -sI https://www.pepejaraba.com 2>&1 | grep -i location
curl -sI https://www.jarabaimpact.com 2>&1 | grep -i location

# Redirect HTTP -> HTTPS
curl -sI http://pepejaraba.com 2>&1 | grep -i location
```

### 9.2 SSL valido

```bash
for domain in plataformadeecosistemas.es pepejaraba.com jarabaimpact.com plataformadeecosistemas.com; do
  echo "=== $domain ==="
  echo | openssl s_client -connect "$domain:443" -servername "$domain" 2>/dev/null | \
    openssl x509 -noout -dates -subject
done
```

### 9.3 Security headers

```bash
curl -sI https://plataformadeecosistemas.es | grep -iE 'x-frame|x-content|referrer|permissions|vary'
```

Esperado:
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: camera=(), microphone=(), geolocation=()
Vary: Host
```

### 9.4 Funcionalidad aplicacion

```bash
# Health endpoint
curl -s https://plataformadeecosistemas.com/health

# Contact API
curl -sX POST https://plataformadeecosistemas.es/api/v1/public/contact \
  -H 'Content-Type: application/json' \
  -d '{"test": true}'

# Analytics API
curl -sX POST https://plataformadeecosistemas.es/api/v1/analytics/event \
  -H 'Content-Type: application/json' \
  -d '{"event": "test"}'

# Redis conectado
drush eval "echo \Drupal::cache()->getBackendClass();"

# Drupal status
drush core:requirements --severity=2
```

### 9.5 Checklist final

- [ ] Los 3 meta-sitios responden HTTPS 200
- [ ] SaaS base domain responde HTTPS 200
- [ ] www -> non-www redirects funcionan (301)
- [ ] HTTP -> HTTPS redirects funcionan (301)
- [ ] SSL valido en todos los dominios (sin warnings)
- [ ] Wildcard SSL para `*.plataformadeecosistemas.com`
- [ ] Security headers presentes (X-Frame-Options, Vary: Host)
- [ ] Redis conectado como cache backend
- [ ] Domain entities existen para los 4 hostnames base
- [ ] SiteConfig + TenantThemeConfig para cada meta-sitio
- [ ] `jaraba_base_domain` = `plataformadeecosistemas.com` activo
- [ ] `trusted_host_patterns` incluye TODOS los dominios
- [ ] Cron ejecutando cada 15min
- [ ] Private files dir con permisos correctos
- [ ] `/health` endpoint respondiendo
- [ ] Backups DB + files configurados
- [ ] Log rotation configurado
- [ ] UptimeRobot o similar para los 4 dominios principales

---

## 10. Monitorizacion

```bash
# Verificar cron ejecuta
drush watchdog:show --type=cron --count=5

# Verificar Redis
redis-cli info memory | grep used_memory_human
redis-cli info keyspace

# Verificar PHP-FPM
systemctl status php8.4-fpm

# Disco
df -h /var/www/jaraba
du -sh /var/www/jaraba/web/sites/default/files/

# Logs
tail -f /var/log/nginx/error.log
journalctl -u php8.4-fpm -f
```

---

## 11. Rollback

```bash
# Si falla un deploy:
cd /var/www/jaraba
git log --oneline -5          # Identificar commit anterior
git checkout HEAD~1
composer install --no-dev --optimize-autoloader
drush cr

# Si falla una migracion de DB:
# Restaurar backup antes de drush updb
mysql -u root jaraba < /backups/jaraba_YYYYMMDD.sql
drush cr
```

---

## 12. Mantenimiento Periodico

| Tarea | Frecuencia | Comando |
|-------|------------|---------|
| Backup DB | Diario | `mysqldump jaraba > /backups/jaraba_$(date +%Y%m%d).sql` |
| Backup files | Semanal | `tar czf /backups/files_$(date +%Y%m%d).tar.gz web/sites/default/files/` |
| Renovar SSL | Auto (certbot timer) | `certbot renew` |
| Actualizar Drupal | Mensual | `composer update drupal/core-* --with-dependencies` |
| Limpiar cache Redis | Si problemas | `drush cr` (NO `redis-cli flushall`) |
| Rotar logs | Auto (logrotate) | Verificar `/etc/logrotate.d/nginx` |
| Verificar SCSS compilado | Cada deploy | `SCSS-COMPILE-VERIFY-001` |
