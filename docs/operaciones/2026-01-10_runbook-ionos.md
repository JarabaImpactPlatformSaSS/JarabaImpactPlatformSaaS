# Runbook de Operaciones - IONOS Production

> **Última actualización**: 2026-01-10 22:00  
> **Servidor**: IONOS Shared Hosting  
> **Dominio**: plataformadeecosistemas.com  
> **Versión desplegada**: AI-First Commerce v2.0

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

## Rutas Importantes

| Ruta | Descripción |
|------|-------------|
| `~/JarabaImpactPlatformSaaS` | Raíz del proyecto |
| `~/JarabaImpactPlatformSaaS/web` | DocumentRoot (symlink desde plataformadeecosistemas.com) |
| `~/bin/composer.phar` | Composer |
| `/usr/bin/php8.4-cli` | PHP CLI |

---

## Deployment (Actualizar código)

> [!CAUTION]
> **CRÍTICO**: Siempre manejar permisos de sites/default y verificar settings.local.php

### Procedimiento Completo

```bash
cd ~/JarabaImpactPlatformSaaS

# 1. Backup antes de deploy
/usr/bin/php8.4-cli ./vendor/bin/drush.php sql-dump --gzip > ~/backups/db_pre_deploy_$(date +%Y%m%d_%H%M%S).sql.gz

# 2. Desbloquear permisos para git
chmod 755 web/sites/default
chmod 644 web/sites/default/settings.php

# 3. Actualizar código
git fetch origin
git pull origin main

# 4. Restaurar permisos seguros
chmod 555 web/sites/default
chmod 444 web/sites/default/settings.php

# 5. Habilitar RewriteBase (necesario en IONOS)
sed -i 's/# RewriteBase \//RewriteBase \//' web/.htaccess

# 6. Instalar dependencias (si composer.json cambió)
/usr/bin/php8.4-cli ~/bin/composer.phar install --no-dev --optimize-autoloader

# 7. Ejecutar updates de BD
/usr/bin/php8.4-cli ./vendor/bin/drush.php updatedb -y

# 8. Importar configuración (si hay cambios)
/usr/bin/php8.4-cli ./vendor/bin/drush.php config:import -y

# 9. Limpiar caché
/usr/bin/php8.4-cli ./vendor/bin/drush.php cr

# 10. Verificar sitio
curl -I https://plataformadeecosistemas.com/
```

### Deploy Rápido (sin cambios de composer)

```bash
cd ~/JarabaImpactPlatformSaaS
chmod 755 web/sites/default && chmod 644 web/sites/default/settings.php
git pull origin main
chmod 555 web/sites/default && chmod 444 web/sites/default/settings.php
sed -i 's/# RewriteBase \//RewriteBase \//' web/.htaccess
/usr/bin/php8.4-cli ./vendor/bin/drush.php cr
```

---

## Comandos Drush Frecuentes

```bash
cd ~/JarabaImpactPlatformSaaS

# Estado del sitio
/usr/bin/php8.4-cli ./vendor/bin/drush.php status

# Limpiar caché
/usr/bin/php8.4-cli ./vendor/bin/drush.php cr

# Modo mantenimiento ON
/usr/bin/php8.4-cli ./vendor/bin/drush.php state:set system.maintenance_mode 1

# Modo mantenimiento OFF
/usr/bin/php8.4-cli ./vendor/bin/drush.php state:set system.maintenance_mode 0

# Ver logs de errores
/usr/bin/php8.4-cli ./vendor/bin/drush.php watchdog:show --count=20

# Listar módulos habilitados
/usr/bin/php8.4-cli ./vendor/bin/drush.php pm:list --status=enabled

# Habilitar módulo
/usr/bin/php8.4-cli ./vendor/bin/drush.php en nombre_modulo -y

# Deshabilitar módulo
/usr/bin/php8.4-cli ./vendor/bin/drush.php pm:uninstall nombre_modulo -y
```

---

## Backup y Restauración

### Backup

```bash
cd ~/JarabaImpactPlatformSaaS
mkdir -p ~/backups

# Backup de base de datos
/usr/bin/php8.4-cli ./vendor/bin/drush.php sql-dump --gzip > ~/backups/db_$(date +%Y%m%d_%H%M%S).sql.gz

# Backup de archivos
tar -czf ~/backups/files_$(date +%Y%m%d).tar.gz web/sites/default/files

# Backup de settings.local.php (CRÍTICO)
cp web/sites/default/settings.local.php ~/backups/settings.local_$(date +%Y%m%d).php
```

### Restaurar

```bash
# Restaurar BD
gunzip -c ~/backups/db_YYYYMMDD_HHMMSS.sql.gz | /usr/bin/php8.4-cli ./vendor/bin/drush.php sql-cli

# Restaurar archivos
tar -xzf ~/backups/files_YYYYMMDD.tar.gz -C ~/JarabaImpactPlatformSaaS/

# Limpiar caché tras restaurar
/usr/bin/php8.4-cli ./vendor/bin/drush.php cr
```

---

## Troubleshooting

### Error 500 - Verificaciones Rápidas

```bash
cd ~/JarabaImpactPlatformSaaS

# 1. Verificar RewriteBase
grep "RewriteBase /" web/.htaccess
# Debe mostrar "RewriteBase /" (sin #)

# 2. Verificar settings.local.php existe
cat web/sites/default/settings.local.php | grep host
# Debe mostrar: 'host' => 'db5018953276.hosting-data.io'

# 3. Verificar include de settings.local.php
tail -10 web/sites/default/settings.php
# El if(file_exists...settings.local.php) debe estar SIN # al inicio

# 4. Verificar conexión BD
/usr/bin/php8.4-cli ./vendor/bin/drush.php status
```

### Error "host 'database' not found"

**Causa**: settings.php tiene credenciales de Lando en vez de IONOS.

**Solución**:
1. Editar `web/sites/default/settings.php`
2. Eliminar el bloque `$databases['default']['default']` con `'host' => 'database'`
3. Asegurar que el include de `settings.local.php` esté descomentado

### Error de permisos en git pull

```bash
chmod 755 web/sites/default
chmod 644 web/sites/default/settings.php
git pull origin main
chmod 555 web/sites/default
chmod 444 web/sites/default/settings.php
```

### Recrear settings.local.php

```bash
chmod 755 web/sites/default
cat > web/sites/default/settings.local.php << 'EOF'
<?php
$databases['default']['default'] = [
  'database' => 'dbs14934629',
  'username' => 'dbu360732',
  'password' => 'Pe@06Ja#11Mu$2025_ped_[v%Tf9!zK$4#pL&j*]',
  'host' => 'db5018953276.hosting-data.io',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
];
$settings['hash_salt'] = 'jaraba_ionos_2026_saas_secure_salt';
$settings['trusted_host_patterns'] = [
  '^plataformadeecosistemas\.com$',
  '^.+\.plataformadeecosistemas\.com$',
];
$settings['config_sync_directory'] = 'sites/default/files/config_gLIUe7q5hu-HnOYZsSfpYjIFI5rG5PMudO2lUcIHSj_h0te-8Kp5sn7Ke7L4lUZkPUpkx6i1KQ/sync';
EOF
chmod 555 web/sites/default
```

---

## Credenciales y Accesos

| Servicio | Ubicación |
|----------|-----------|
| SSH | Panel IONOS → Servidor → Acceso |
| MariaDB | Panel IONOS → Bases de datos |
| DNS | Panel IONOS → Dominios |
| Drupal Admin | `/user/login` |

---

## Checklist Pre-Deploy

- [ ] Backup de BD creado
- [ ] Backup de settings.local.php
- [ ] Comunicar ventana de mantenimiento (si aplica)

## Checklist Post-Deploy

- [ ] Sitio carga sin errores
- [ ] /user/login funciona
- [ ] Panel admin accesible
- [ ] robots.txt muestra reglas de AI crawlers

---

## Contactos de Emergencia

| Rol | Contacto |
|-----|----------|
| Soporte IONOS | https://my.ionos.es/support |
| Desarrollador | [Definir] |
