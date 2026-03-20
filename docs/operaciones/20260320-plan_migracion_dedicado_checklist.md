# Checklist Migración a Dedicado IONOS AE16-128

**Fecha**: 2026-03-20
**Doc técnico completo**: `docs/tecnicos/20260320a-131b_Plan_Migracion_Dedicado_IONOS_AE16_v1_Claude.md`
**Runbook**: `docs/tecnicos/20260311c-131b_Server_Implementation_Runbook_AMD_v3_Claude.md`

---

## Requisitos previos

- [ ] IP pública del servidor dedicado obtenida
- [ ] Credenciales root iniciales de IONOS
- [ ] Ubuntu 24.04 confirmado en el servidor
- [ ] Clave SSH ed25519 generada (`~/.ssh/jaraba-dedicated`)
- [ ] Control DNS confirmado (5 dominios)
- [ ] API token Cloudflare (o proveedor DNS identificado)

## Fase A — Aprovisionamiento

- [ ] Usuario `jaraba` creado + grupo `www-data`
- [ ] SSH puerto 2222 + root login deshabilitado
- [ ] UFW: 2222/80/443
- [ ] fail2ban configurado
- [ ] MariaDB 10.11 instalado + `99-jaraba.cnf`
- [ ] PHP 8.4 FPM + `99-jaraba-prod.ini` + pool `jaraba.conf`
- [ ] Redis 7.4 instalado + configurado
- [ ] Nginx instalado + `nginx-metasites.conf` + `nginx-jaraba-common.conf`
- [ ] Supervisor instalado + `jaraba-ai-workers.conf`
- [ ] Composer 2 instalado globalmente
- [ ] Database `jaraba` + usuario `drupal@localhost`
- [ ] Tika Docker container en :9998
- [ ] Sudoers configurado (php-fpm reload, supervisorctl)
- [ ] Directorios creados (/var/www/jaraba, /var/log/jaraba, /opt/jaraba)

## Fase B — SSL

- [ ] Certbot wildcard: `*.plataformadeecosistemas.com`
- [ ] Certbot wildcard: `*.plataformadeecosistemas.es`
- [ ] Certbot wildcard: `*.jaraba.es`
- [ ] Certbot individual: `pepejaraba.com` + `www`
- [ ] Certbot individual: `jarabaimpact.com` + `www`
- [ ] Stack completo verificado (PHP, MariaDB, Redis, Nginx, Supervisor, Tika)

## Fase C — Código + Datos

- [ ] Repo clonado en `/var/www/jaraba`
- [ ] `composer install --no-dev` ejecutado
- [ ] `settings.local.php` creado
- [ ] `settings.env.php` generado con API keys
- [ ] DB importada desde servidor actual
- [ ] Files rsync completado
- [ ] `drush updatedb + config:import + cache:rebuild`
- [ ] curl localhost con Host header → 200

## Fase D — CI/CD

- [ ] `deploy.yml` actualizado para dedicado
- [ ] `daily-backup.yml` actualizado
- [ ] `verify-backups.yml` actualizado
- [ ] GitHub Secret: `IONOS_SSH_PRIVATE_KEY` actualizado
- [ ] GitHub Secret: `DEPLOY_HOST` creado
- [ ] GitHub Secrets DB actualizados

## Fase E — Cutover DNS

- [ ] TTL reducido a 300s (horas antes)
- [ ] Maintenance mode ON en servidor actual
- [ ] Dump final DB → importado en nuevo
- [ ] Rsync final files
- [ ] Cache rebuild en nuevo
- [ ] **DNS cambiado** (7 registros A)
- [ ] Propagación DNS verificada
- [ ] Smoke tests pasados
- [ ] Maintenance mode OFF

## Fase F — Post-cutover

- [ ] Homepage 200
- [ ] /api/v1/platform/status → ok
- [ ] SSL wildcard tenant OK
- [ ] Login admin sin CSRF
- [ ] Redis conexiones activas
- [ ] Supervisor 5 workers RUNNING
- [ ] Deploy automático (push → pipeline verde)
- [ ] Backup cron funcional
- [ ] Docs actualizados (CLAUDE.md, master docs)

## Fase G — Limpieza (7-14 días después)

- [ ] Workarounds shared hosting eliminados del código
- [ ] Servidor compartido cancelado
- [ ] Monitorización 7 días completada sin incidencias
