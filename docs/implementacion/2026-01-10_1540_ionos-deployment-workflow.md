# Phase 10: IONOS Production Deployment

> **Servidor**: IONOS Dedicado L-16 NVMe (16 cores, 128GB RAM)  
> **Stack**: PHP 8.4, MariaDB 10.11  
> **Dominio**: plataformadeecosistemas.com  
> **Estado**: Planificación

---

## Flujo de Desarrollo → Producción

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLUJO DE DEPLOYMENT                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   LOCAL (WSL/Lando)              IONOS (Producción)            │
│   ─────────────────              ──────────────────            │
│                                                                 │
│   1. Desarrollar cambios    ──→  (no tocar)                    │
│   2. Validar en local       ──→  (no tocar)                    │
│   3. git commit + push      ──→  GitHub/GitLab                 │
│   4. (opcional) PR review   ──→  (automático o manual)         │
│   5. ssh → git pull         ──→  Descargar cambios             │
│   6. drush updb + cim + cr  ──→  Aplicar updates               │
│                                                                 │
│   Base de Datos:                                                │
│   - LOCAL: drupal_jaraba (datos de prueba)                     │
│   - IONOS: drupal_production (datos reales, nunca sobrescribir)│
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Paso 1: Configurar Repositorio Git

### 1.1 Crear repositorio en GitHub/GitLab

```bash
# En tu máquina local (WSL)
cd /home/PED/JarabaImpactPlatformSaaS

# Inicializar git (si no existe)
git init
git branch -M main

# Crear .gitignore apropiado para Drupal
cat >> .gitignore << 'EOF'
# Drupal
web/sites/*/settings.local.php
web/sites/*/files/*
web/sites/*/private/*
vendor/
node_modules/

# Environment
.env
.env.local

# IDE
.idea/
.vscode/

# OS
.DS_Store
Thumbs.db

# Lando (no necesario en producción)
.lando.local.yml
EOF

# Añadir remote
git remote add origin git@github.com:TU_USUARIO/JarabaImpactPlatformSaaS.git

# Primer push
git add -A
git commit -m "Initial commit: Jaraba SaaS Platform"
git push -u origin main
```

### 1.2 Generar SSH key para GitHub

```bash
# Generar nueva key
ssh-keygen -t ed25519 -C "tu-email@ejemplo.com"

# Mostrar la clave pública (copiar a GitHub)
cat ~/.ssh/id_ed25519.pub
```

Añadir en: GitHub → Settings → SSH Keys

---

## Paso 2: Configurar IONOS

### 2.1 Acceso SSH a IONOS

**Opción A: Desde Panel IONOS**
1. Acceder a https://my.ionos.es
2. Servidor > Acceso > Credenciales SSH
3. Copiar: IP, usuario, contraseña/key

**Opción B: Compartir conmigo (seguro)**
- NO pegar credenciales en el chat
- Usar un gestor de secretos o:
  1. Crear usuario SSH limitado para deploy
  2. Compartir solo la public key

### 2.2 Estructura en IONOS

```bash
# Conectar al servidor
ssh usuario@IP_IONOS

# Crear estructura
sudo mkdir -p /var/www/plataformadeecosistemas.com
sudo chown -R www-data:www-data /var/www/plataformadeecosistemas.com

# Clonar repositorio
cd /var/www/plataformadeecosistemas.com
git clone git@github.com:TU_USUARIO/JarabaImpactPlatformSaaS.git .

# Instalar dependencias
composer install --no-dev --optimize-autoloader

# Crear settings.local.php para producción
cp web/sites/default/settings.php web/sites/default/settings.local.php
```

### 2.3 settings.local.php (Producción)

```php
<?php
// web/sites/default/settings.local.php

$databases['default']['default'] = [
  'database' => 'drupal_production',
  'username' => 'drupal_user',
  'password' => 'CONTRASEÑA_SEGURA',
  'host' => 'localhost',
  'driver' => 'mysql',
  'prefix' => '',
];

$settings['hash_salt'] = 'HASH_UNICO_GENERADO';
$settings['trusted_host_patterns'] = [
  '^plataformadeecosistema\.es$',
  '^.+\.plataformadeecosistema\.es$',
];

// Stripe LIVE keys
$config['ecosistema_jaraba_core.stripe']['mode'] = 'live';
$config['ecosistema_jaraba_core.stripe']['public_key'] = 'pk_live_...';
$config['ecosistema_jaraba_core.stripe']['secret_key'] = 'sk_live_...';
```

---

## Paso 3: Script de Deployment (Zero-Downtime)

### 3.1 Script en IONOS: /var/www/scripts/deploy.sh

```bash
#!/bin/bash
set -e

SITE_DIR="/var/www/plataformadeecosistemas.com"
BACKUP_DIR="/var/backups/drupal"
DATE=$(date +%Y%m%d_%H%M%S)

echo "🚀 Iniciando deployment..."

# 1. Backup de base de datos ANTES de cambios
echo "📦 Backup de base de datos..."
mkdir -p $BACKUP_DIR
cd $SITE_DIR
drush sql-dump --gzip > "$BACKUP_DIR/db_pre_deploy_$DATE.sql.gz"

# 2. Activar modo mantenimiento
echo "🔧 Activando modo mantenimiento..."
drush state:set system.maintenance_mode 1

# 3. Pull de cambios
echo "📥 Descargando cambios de Git..."
git fetch origin
git reset --hard origin/main

# 4. Instalar dependencias
echo "📦 Instalando dependencias..."
composer install --no-dev --optimize-autoloader

# 5. Aplicar updates de base de datos
echo "🗄️ Aplicando updates de base de datos..."
drush updatedb -y

# 6. Importar configuración
echo "⚙️ Importando configuración..."
drush config:import -y

# 7. Limpiar caché
echo "🧹 Limpiando caché..."
drush cache:rebuild

# 8. Desactivar modo mantenimiento
echo "✅ Desactivando modo mantenimiento..."
drush state:set system.maintenance_mode 0

echo "🎉 Deployment completado!"
echo "📝 Backup guardado en: $BACKUP_DIR/db_pre_deploy_$DATE.sql.gz"
```

### 3.2 Uso

```bash
# Desde IONOS
sudo /var/www/scripts/deploy.sh

# O remotamente desde local
ssh usuario@IP_IONOS "sudo /var/www/scripts/deploy.sh"
```

---

## Paso 4: Rollback (Si algo falla)

```bash
#!/bin/bash
# /var/www/scripts/rollback.sh

BACKUP_FILE=$1  # Ej: /var/backups/drupal/db_pre_deploy_20260110.sql.gz

if [ -z "$BACKUP_FILE" ]; then
    echo "Uso: ./rollback.sh /path/to/backup.sql.gz"
    exit 1
fi

echo "⚠️ Restaurando backup: $BACKUP_FILE"
drush state:set system.maintenance_mode 1
gunzip -c $BACKUP_FILE | drush sql-cli
drush cache:rebuild
drush state:set system.maintenance_mode 0
echo "✅ Rollback completado"
```

---

## Resumen del Flujo

| Paso | Local | IONOS | Notas |
|------|-------|-------|-------|
| 1 | Desarrollar | - | Lando local |
| 2 | `git commit -m "..."` | - | Guardar cambios |
| 3 | `git push origin main` | - | Subir a GitHub |
| 4 | - | `./deploy.sh` | Ejecutar script |
| 5 | - | Validar en browser | Verificar cambios |

---

## Sobre Acceso SSH

**No necesito acceso SSH directo**. Lo que puedo hacer:
1. Generar los scripts y configuraciones
2. Documentar cada paso
3. Ayudarte en tiempo real si hay errores

Si prefieres que configure algo directamente, podrías:
- Crear un usuario SSH de deploy con permisos limitados
- Usar una sesión compartida de terminal (ej: tmate)

---

## Checklist Siguiente

- [ ] Crear repositorio Git (GitHub/GitLab)
- [ ] Push inicial del código
- [ ] Configurar SSH key en IONOS para pull de Git
- [ ] Crear base de datos `drupal_production` en IONOS
- [x] Clonar repo en IONOS
- [x] Crear `settings.local.php` de producción
- [x] Instalar entidades custom (vertical, tenant, saas_plan, feature, ai_agent):
```bash
/usr/bin/php8.4-cli vendor/bin/drush.php php:script install_entities.php
```
- [ ] Configurar Nginx/Apache vhost
- [x] Clean URLs con RewriteBase /
- [ ] SSL con Let's Encrypt
- [x] Primer deploy completado
