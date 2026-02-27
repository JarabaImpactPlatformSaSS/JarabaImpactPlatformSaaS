# 🚀 Checklist Deploy a Producción — IONOS

## Pre-Deploy

- [ ] Todas las migraciones de DB aplicadas (`drush updb`)
- [ ] Config exportada (`drush cex`)
- [ ] Composer.lock actualizado (`composer install --no-dev`)
- [ ] SCSS compilado (zero errors)
- [ ] Tests pasan (`phpunit`, `cypress`)
- [ ] `settings.php` tiene `trusted_host_patterns` para producción
- [ ] `.env` producción configurado (DB, Redis, SendGrid, Stripe keys)

## IONOS — Requisitos Stack

| Componente | Versión | Nota |
|---|---|---|
| PHP | 8.4+ | Extensiones: mbstring, gd, pdo_mysql, opcache, redis |
| MariaDB | 10.11+ | `innodb_buffer_pool_size >= 512M` |
| Redis | 7+ | Cache backend |
| Composer | 2.x | Deploy via `composer install --no-dev --optimize-autoloader` |
| Node.js | 20.x | Solo para build SCSS (no requiere en runtime) |
| SSL | Let's Encrypt | Auto-renovación via certbot |

## DNS — 3 Meta-Sitios

| Dominio | Tipo | Valor | TTL |
|---|---|---|---|
| `plataformadeecosistemas.es` | A | `[IP_IONOS]` | 300 |
| `www.plataformadeecosistemas.es` | CNAME | `plataformadeecosistemas.es` | 300 |
| `pepejaraba.com` | A | `[IP_IONOS]` | 300 |
| `www.pepejaraba.com` | CNAME | `pepejaraba.com` | 300 |
| `jarabaimpact.com` | A | `[IP_IONOS]` | 300 |
| `www.jarabaimpact.com` | CNAME | `jarabaimpact.com` | 300 |

> [!WARNING]
> TTL a 300s durante migración. Subir a 3600s tras verificar.

## Deploy Steps

```bash
# 1. Subir código
rsync -avz --exclude='.git' --exclude='node_modules' ./ user@ionos:/var/www/jaraba/

# 2. Instalar dependencias
cd /var/www/jaraba && composer install --no-dev --optimize-autoloader

# 3. Compilar SCSS (si no se incluyó css/ compilado)
cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/main.css --style=compressed

# 4. Migraciones + config
drush updb -y && drush cim -y && drush cr

# 5. SSL
certbot --nginx -d plataformadeecosistemas.es -d www.plataformadeecosistemas.es \
  -d pepejaraba.com -d www.pepejaraba.com \
  -d jarabaimpact.com -d www.jarabaimpact.com
```

## Post-Deploy Verificación

- [ ] `curl -I https://plataformadeecosistemas.es` → 200 + HTTPS
- [ ] `curl -I https://pepejaraba.com` → 200 + HTTPS
- [ ] `curl -I https://jarabaimpact.com` → 200 + HTTPS
- [ ] Heatmap tracker cargando en los 3 dominios
- [ ] Funnel analytics cargando
- [ ] `POST /api/v1/public/contact` → `{"status":"ok"}`
- [ ] `POST /api/v1/analytics/event` → `{"status":"ok","count":1}`
- [ ] A/B tests activos (verificar cookie `ab_homepage_hero_cta`)
- [ ] SSL cert válido (no warnings)
- [ ] Cron configurado cada 15min (`drush cron`)
- [ ] Redis conectado (`drush status`)

## Monitorización

- [ ] UptimeRobot o similar para los 3 dominios
- [ ] `/health` endpoint respondiendo
- [ ] Log rotation configurado
- [ ] Backup diario DB + files

## Rollback

```bash
# Si algo falla:
cd /var/www/jaraba
git checkout HEAD~1
composer install --no-dev
drush cr
```
