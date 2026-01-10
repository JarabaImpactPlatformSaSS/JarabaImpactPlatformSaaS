# Runbook de Operaciones - IONOS Production

> **Última actualización**: 2026-01-10  
> **Servidor**: IONOS L-16 NVMe  
> **Dominio**: plataformadeecosistemas.com

---

## Conexión SSH

```bash
# Desde Windows/WSL
ssh -i ~/.ssh/ionos_deploy u101456434@access834313033.webspace-data.io

# Alias recomendado en ~/.ssh/config
Host ionos-jaraba
  HostName access834313033.webspace-data.io
  User u101456434
  IdentityFile ~/.ssh/ionos_deploy
```

---

## Comandos Frecuentes

### Deployment (Actualizar código)

```bash
cd ~/JarabaImpactPlatformSaaS
git pull origin main
/usr/bin/php8.4-cli ~/bin/composer.phar install --no-dev
/usr/bin/php8.4-cli vendor/bin/drush.php updb -y
/usr/bin/php8.4-cli vendor/bin/drush.php cim -y
/usr/bin/php8.4-cli vendor/bin/drush.php cr
```

### Mantenimiento

```bash
# Activar modo mantenimiento
/usr/bin/php8.4-cli vendor/bin/drush.php state:set system.maintenance_mode 1

# Desactivar modo mantenimiento
/usr/bin/php8.4-cli vendor/bin/drush.php state:set system.maintenance_mode 0

# Limpiar caché
/usr/bin/php8.4-cli vendor/bin/drush.php cr

# Ver logs de errores
/usr/bin/php8.4-cli vendor/bin/drush.php watchdog:show --count=20
```

### Backup

```bash
# Backup de base de datos
/usr/bin/php8.4-cli vendor/bin/drush.php sql-dump --gzip > ~/backups/db_$(date +%Y%m%d_%H%M%S).sql.gz

# Backup de archivos
tar -czf ~/backups/files_$(date +%Y%m%d).tar.gz web/sites/default/files
```

### Restaurar Backup

```bash
# Restaurar BD
gunzip -c ~/backups/db_YYYYMMDD_HHMMSS.sql.gz | /usr/bin/php8.4-cli vendor/bin/drush.php sql-cli

# Restaurar archivos
tar -xzf ~/backups/files_YYYYMMDD.tar.gz -C /
```

---

## Troubleshooting

### Error 500 en páginas específicas

1. Verificar logs:
```bash
/usr/bin/php8.4-cli vendor/bin/drush.php watchdog:show --count=50
```

2. Verificar permisos:
```bash
chmod -R 755 web/sites/default/files
chmod 644 web/sites/default/settings.php
```

### Clean URLs no funcionan

1. Verificar .htaccess:
```bash
grep RewriteBase web/.htaccess
```

2. Debe mostrar: `RewriteBase /`

### Módulo no se habilita

1. Verificar entidades:
```bash
/usr/bin/php8.4-cli vendor/bin/drush.php php:script install_entities.php
```

### Base de datos corrupta

1. Restaurar último backup:
```bash
/usr/bin/php8.4-cli vendor/bin/drush.php state:set system.maintenance_mode 1
gunzip -c ~/backups/ULTIMO_BACKUP.sql.gz | /usr/bin/php8.4-cli vendor/bin/drush.php sql-cli
/usr/bin/php8.4-cli vendor/bin/drush.php cr
/usr/bin/php8.4-cli vendor/bin/drush.php state:set system.maintenance_mode 0
```

---

## Rutas Importantes

| Ruta | Descripción |
|------|-------------|
| `~/JarabaImpactPlatformSaaS` | Raíz del proyecto |
| `~/JarabaImpactPlatformSaaS/web` | DocumentRoot |
| `~/JarabaImpactPlatformSaaS/web/sites/default/files` | Archivos subidos |
| `~/JarabaImpactPlatformSaaS/web/sites/default/settings.local.php` | Config BD producción |
| `~/backups` | Directorio de backups |
| `~/bin/composer.phar` | Composer instalado |

---

## Credenciales y Accesos

| Servicio | Ubicación |
|----------|-----------|
| SSH | Panel IONOS → Servidor → Acceso |
| MariaDB | Panel IONOS → Bases de datos |
| DNS | Panel IONOS → Dominios |
| Drupal Admin | `/user/login` (admin/admin123) |

> ⚠️ **IMPORTANTE**: Cambiar contraseña admin en producción

---

## Contactos de Emergencia

| Rol | Contacto |
|-----|----------|
| Soporte IONOS | https://my.ionos.es/support |
| Desarrollador | [Definir] |

---

## Checklist Diario

- [ ] Verificar que el sitio carga
- [ ] Revisar logs de errores
- [ ] Verificar espacio en disco
- [ ] Confirmar último backup exitoso

## Checklist Semanal

- [ ] Ejecutar backup completo
- [ ] Revisar actualizaciones de seguridad Drupal
- [ ] Verificar certificado SSL
- [ ] Revisar métricas de rendimiento
