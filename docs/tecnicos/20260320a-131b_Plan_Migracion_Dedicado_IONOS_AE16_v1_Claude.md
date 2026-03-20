# Plan de Migración a Servidor Dedicado IONOS AE16-128 NVMe XL

**Documento**: 131b (complemento del Runbook v3)
**Fecha**: 2026-03-20
**Versión**: 1.0.0
**Autor**: Claude (asistido)
**Estado**: Pendiente — requisitos previos no completados
**Runbook asociado**: `20260311c-131b_Server_Implementation_Runbook_AMD_v3_Claude.md`

---

## 1. Contexto

El SaaS Jaraba Impact Platform corre actualmente en IONOS shared hosting (L-16 NVMe Managed) con limitaciones críticas:

| Limitación | Shared Hosting | Dedicado AE16-128 |
|-----------|---------------|-------------------|
| Base de datos | 5GB máximo | Sin límite (1TB NVMe) |
| Acceso root | No | Sí |
| Redis nativo | No (módulo PHP) | Redis 7.4 server |
| Supervisor | No disponible | 5 AI workers |
| my.cnf tuning | No modificable | InnoDB 16GB buffer pool |
| PHP-FPM control | No | 24 workers, JIT, OPcache |
| SSL wildcard | Manual/limitado | Certbot automático |
| Nginx | No (Apache) | Nginx optimizado |

**Hardware nuevo**: AMD EPYC 4465P Zen 5 (12c/24t), 128GB DDR5 ECC, 2×1TB NVMe RAID 1, Ubuntu 24.04 LTS.

**Objetivo**: Migración con <60min downtime, deploy automatizado desde día 1, intervención mínima del usuario.

---

## 2. Requisitos previos (Acciones del usuario)

### Acción 1 — Acceso SSH al servidor nuevo
- **IP pública** del servidor dedicado
- **Credenciales root** iniciales (proporcionadas por IONOS)
- Confirmar que tiene **Ubuntu 24.04** instalado

### Acción 2 — Generar clave SSH para deploy
```bash
ssh-keygen -t ed25519 -C "jaraba-deploy" -f ~/.ssh/jaraba-dedicated
cat ~/.ssh/jaraba-dedicated.pub
```
La clave pública se instala en el servidor. La privada se sube a GitHub Secrets.

### Acción 3 — Confirmar control DNS
Confirmar acceso al panel DNS de:
- plataformadeecosistemas.com
- plataformadeecosistemas.es
- pepejaraba.com
- jarabaimpact.com
- jaraba.es

### Acción 4 — Cloudflare API Token (si aplica)
Para certificados wildcard (`*.plataformadeecosistemas.com`) se necesita DNS challenge.
- Si dominios en Cloudflare: API token con permisos `Zone:DNS:Edit`
- Si NO están en Cloudflare: identificar proveedor DNS

---

## 3. Fases de implementación

### FASE A: Aprovisionamiento del servidor [CLAUDE vía SSH]
**Intervención usuario**: Solo dar acceso SSH (Acción 1)

1. **Hardening**:
   - Crear usuario `jaraba` (grupo `www-data`)
   - SSH en puerto 2222, deshabilitar root login
   - UFW: permitir 2222/tcp, 80/tcp, 443/tcp
   - fail2ban configurado para SSH

2. **Stack nativo**:
   - MariaDB 10.11 → `config/deploy/mariadb/my.cnf` → `/etc/mysql/mariadb.conf.d/99-jaraba.cnf`
   - PHP 8.4 FPM → `config/deploy/php/99-jaraba-prod.ini` + pool jaraba.conf
   - Redis 7.4 → localhost only, 5GB maxmemory, AOF
   - Nginx → `config/deploy/nginx-metasites.conf` + `nginx-jaraba-common.conf`
   - Supervisor → `config/deploy/supervisor-ai-workers.conf`
   - Composer 2 global

3. **Estructura de directorios**:
   ```
   /var/www/jaraba/          ← Document root
   /var/www/jaraba/private/  ← Private files (fuera de webroot)
   /var/log/jaraba/          ← Logs aplicación
   /opt/jaraba/scripts/      ← Scripts de deploy
   /opt/jaraba/backups/      ← Backups DB
   ```

4. **Base de datos**:
   - Database: `jaraba`
   - Usuario: `drupal@localhost` (sin acceso remoto)

5. **Tika**: Docker container en puerto 9998 (stateless)

6. **Sudoers** (sin password):
   - `jaraba ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php8.4-fpm`
   - `jaraba ALL=(ALL) NOPASSWD: /usr/bin/supervisorctl restart jaraba-ai:*`

### FASE B: SSL + Verificación stack [CLAUDE vía SSH + USER DNS token]
**Intervención usuario**: API token de Cloudflare (Acción 4)

1. **Certbot wildcard** (DNS challenge):
   - `*.plataformadeecosistemas.com` + `plataformadeecosistemas.com`
   - `*.plataformadeecosistemas.es` + `plataformadeecosistemas.es`
   - `*.jaraba.es` + `jaraba.es`

2. **Certbot individual** (HTTP o DNS challenge):
   - `pepejaraba.com` + `www.pepejaraba.com`
   - `jarabaimpact.com` + `www.jarabaimpact.com`

3. **Verificación**: PHP, MariaDB, Redis, Nginx, Supervisor, Tika — todo respondiendo

### FASE C: Código + Datos [CLAUDE autónomo]
**Intervención usuario**: Ninguna

1. Clonar repo en `/var/www/jaraba` + `composer install --no-dev`
2. Crear `settings.local.php` con DB credentials locales
3. Generar `settings.env.php` con API keys (mismos valores que GitHub Secrets)
4. Dump DB del servidor actual → importar en nuevo
5. Rsync `files/` (excluyendo css/js/styles regenerables)
6. `drush updatedb + config:import + cache:rebuild`
7. Verificar: `curl -H "Host: plataformadeecosistemas.com" http://localhost/` → 200

### FASE D: Actualizar pipeline CI/CD [CLAUDE — commits en repo]
**Intervención usuario**: Actualizar GitHub Secrets (Acción 5)

Archivos a modificar:

| Archivo | Cambio |
|---------|--------|
| `.github/workflows/deploy.yml` | Reescribir para dedicado (SSH jaraba@IP:2222, path /var/www/jaraba) |
| `.github/workflows/daily-backup.yml` | Host/user/port/paths |
| `.github/workflows/verify-backups.yml` | Host/user/port/paths |

Cambios clave en deploy.yml:
- SSH: `jaraba@<IP>` puerto 2222 (no `u101456434@access834313033`)
- Path: `/var/www/jaraba` (no `~/JarabaImpactPlatformSaaS`)
- Eliminar workarounds: .htaccess fix, .user.ini, ~/bin_cli/php symlink, vendor wipe
- Añadir: `sudo systemctl reload php8.4-fpm` + `sudo supervisorctl restart jaraba-ai:*`
- Composer: `composer` directo (no `~/bin/composer.phar`)

### Acción 5 — Actualizar GitHub Secrets (POST fase C)

| Secret | Cambiar a |
|--------|-----------|
| `IONOS_SSH_PRIVATE_KEY` | Contenido de `~/.ssh/jaraba-dedicated` (privada) |
| `DEPLOY_HOST` | IP del servidor dedicado (secret NUEVO) |
| `IONOS_DB_HOST` | `localhost` |
| `IONOS_DB_NAME` | `jaraba` |
| `IONOS_DB_USER` | `drupal` |
| `IONOS_DB_PASS` | (password generado al crear la DB) |

**NO cambian**: PRODUCTION_URL, OPENAI_API_KEY, ANTHROPIC_API_KEY, GOOGLE_GEMINI_API_KEY, STRIPE_*, RECAPTCHA_*, SMTP_*, SOCIAL_AUTH_*

### FASE E: Cutover DNS [USER — 30-60 min downtime]
**Intervención usuario**: Cambiar registros DNS

**Horas ANTES**: Reducir TTL de todos los dominios a 300s (5 minutos).

**Ventana de mantenimiento**:
1. [CLAUDE] Maintenance mode ON en servidor actual
2. [CLAUDE] Dump final DB → importar en nuevo (delta)
3. [CLAUDE] Rsync final files (solo cambios)
4. [CLAUDE] Cache rebuild en servidor nuevo
5. **[USUARIO] Cambiar DNS**:

| Registro | Tipo | Valor |
|----------|------|-------|
| plataformadeecosistemas.com | A | `<IP_NUEVO>` |
| *.plataformadeecosistemas.com | A | `<IP_NUEVO>` |
| plataformadeecosistemas.es | A | `<IP_NUEVO>` |
| pepejaraba.com | A | `<IP_NUEVO>` |
| jarabaimpact.com | A | `<IP_NUEVO>` |
| jaraba.es | A | `<IP_NUEVO>` |
| *.jaraba.es | A | `<IP_NUEVO>` |

6. [CLAUDE] Verificar propagación DNS + smoke tests + desactivar maintenance

### FASE F: Post-cutover [CLAUDE autónomo]
**Intervención usuario**: Ninguna

1. Smoke tests: Homepage, /api/v1/platform/status, login, meta-sites, SSL wildcard
2. Primer deploy automático: push a main → deploy.yml verde
3. Backup automatizado: rclone → R2 cada 6h + cron Drupal
4. Actualizar docs: CLAUDE.md, master docs, memory
5. Monitorización: 7 días vigilancia logs/errores/rendimiento

### FASE G: Limpieza (7-14 días después) [CLAUDE + USER]
**Intervención usuario**: Cancelar hosting compartido

1. [CLAUDE] Limpiar workarounds de shared hosting del código
2. [USUARIO] Cancelar contrato servidor compartido IONOS

---

## 4. Resumen de intervención del usuario

| Acción | Cuándo | Tiempo |
|--------|--------|--------|
| Dar IP + root SSH | Antes de empezar | 2 min |
| Generar clave SSH ed25519 | Antes de empezar | 1 min |
| Confirmar control DNS (5 dominios) | Antes de empezar | 1 min |
| API token Cloudflare (si aplica) | Fase B | 5 min |
| Actualizar GitHub Secrets | Fase D | 5 min |
| Reducir TTL DNS | Horas antes del cutover | 10 min |
| Cambiar registros DNS (7 registros A) | Fase E (cutover) | 15 min |
| Cancelar servidor viejo | 7-14 días después | 5 min |

**Total intervención: ~45 minutos distribuidos en varios días.**

---

## 5. Archivos del repo afectados

| Archivo | Cambio |
|---------|--------|
| `.github/workflows/deploy.yml` | Reescribir para dedicado |
| `.github/workflows/daily-backup.yml` | Host/user/port/paths |
| `.github/workflows/verify-backups.yml` | Host/user/port/paths |
| `CLAUDE.md` | Actualizar specs servidor |
| `docs/00_ARQUITECTURA_TECNICA.md` | Sección infraestructura |

**Sin cambios** (ya preparados): `config/deploy/*`, `settings.production.php`, `settings.php`

---

## 6. Verificación end-to-end (post-migración)

1. `curl -sI https://plataformadeecosistemas.com` → HTTP/2 200
2. `curl -s https://plataformadeecosistemas.com/api/v1/platform/status` → `{"status":"ok"}`
3. `curl -sI https://demo.plataformadeecosistemas.com` → SSL válido (wildcard)
4. `curl -sI https://pepejaraba.com` → 200
5. Login admin funcional (sin error CSRF)
6. `redis-cli -a <pass> info clients` → conexiones activas
7. `supervisorctl status` → 5 workers RUNNING
8. `gh workflow run deploy.yml` → pipeline verde
9. Backup cron ejecuta sin errores

---

## 7. Rollback

Si la migración falla:
1. Revertir DNS a IP del servidor compartido (TTL bajo = propagación rápida)
2. Desactivar maintenance mode en servidor compartido
3. Los datos del servidor nuevo se preservan para debugging

**Punto de no retorno**: Cuando se cancela el contrato del servidor compartido (Fase G).

---

## 8. Referencias

- Runbook v3: `docs/tecnicos/20260311c-131b_Server_Implementation_Runbook_AMD_v3_Claude.md`
- Análisis Runbook v3: `docs/tecnicos/20260311d_Analisis_Runbook_AMD_v3_Claude.md`
- Deploy checklist actual: `docs/operaciones/20260226-deploy_checklist_ionos.md`
- Infraestructura existente: `docs/tecnicos/20260118k-131_Platform_Infrastructure_Deployment_v1_Claude.md`
- Configs en repo: `config/deploy/` (nginx, mariadb, supervisor, php, settings)
