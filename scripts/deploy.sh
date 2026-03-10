#!/bin/bash
# =============================================================================
# SCRIPT DE DEPLOY SEGURO - Jaraba Impact Platform v3.0
# =============================================================================
# Servidor: IONOS Dedicated L-16 NVMe
# Uso:
#   sudo bash /var/www/jaraba/scripts/deploy.sh
#
# El script: backup BD -> pull codigo -> composer -> drush updb/cim/cr -> verificar
# =============================================================================

set -euo pipefail

# Colores.
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BOLD='\033[1m'
NC='\033[0m'

# Variables.
PROJECT_DIR="/var/www/jaraba"
WEB_DIR="$PROJECT_DIR/web"
SETTINGS_DIR="$WEB_DIR/sites/default"
BACKUP_DIR="/var/www/jaraba-backups"
DRUSH="$PROJECT_DIR/vendor/bin/drush"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# =============================================================================
# PRE-FLIGHT CHECKS
# =============================================================================
echo ""
log_info "=========================================="
log_info "DEPLOY — IONOS Dedicated — $TIMESTAMP"
log_info "=========================================="

# Verificar root.
if [ "$(id -u)" -ne 0 ]; then
  log_error "Ejecutar como root: sudo bash scripts/deploy.sh"
  exit 1
fi

cd "$PROJECT_DIR"

# Verificar que estamos en el directorio correcto.
if [ ! -f "composer.json" ]; then
  log_error "No se encuentra composer.json en $PROJECT_DIR"
  exit 1
fi

# Verificar settings.local.php.
if [ ! -f "$SETTINGS_DIR/settings.local.php" ]; then
  log_error "settings.local.php NO EXISTE."
  log_error "Ejecutar primero: sudo bash scripts/setup-ionos-dedicated.sh"
  exit 1
fi

# Verificar conexion BD.
log_info "Verificando conexion a base de datos..."
if ! sudo -u www-data "$DRUSH" sql:query "SELECT 1" --root="$WEB_DIR" 2>/dev/null; then
  log_error "No hay conexion a la base de datos."
  exit 1
fi
log_info "Conexion BD OK"

# =============================================================================
# BACKUP
# =============================================================================
mkdir -p "$BACKUP_DIR"

log_info "Creando backup de BD..."
sudo -u www-data "$DRUSH" sql-dump --root="$WEB_DIR" --gzip > "$BACKUP_DIR/db_pre_deploy_$TIMESTAMP.sql.gz" 2>/dev/null
log_info "Backup: $BACKUP_DIR/db_pre_deploy_$TIMESTAMP.sql.gz"

CURRENT_COMMIT=$(git rev-parse HEAD)
log_info "Commit actual: ${CURRENT_COMMIT:0:12}"

# =============================================================================
# DEPLOY
# =============================================================================

# Desbloquear permisos para git pull.
log_info "Desbloqueando permisos..."
chmod 755 "$SETTINGS_DIR"
chmod 644 "$SETTINGS_DIR/settings.php"

log_info "Actualizando codigo..."
git fetch origin
git pull origin main

log_info "Restaurando permisos seguros..."
chmod 555 "$SETTINGS_DIR"
chmod 444 "$SETTINGS_DIR/settings.php"
[ -f "$SETTINGS_DIR/settings.local.php" ] && chmod 444 "$SETTINGS_DIR/settings.local.php"

# Verificar que settings.local.php no fue sobrescrito.
if [ ! -f "$SETTINGS_DIR/settings.local.php" ]; then
  log_warn "settings.local.php fue eliminado por git pull. Recreando..."
  cat > "$SETTINGS_DIR/settings.local.php" << 'EOF'
<?php
include $app_root . '/../config/deploy/settings.production.php';
EOF
  chmod 444 "$SETTINGS_DIR/settings.local.php"
fi

# =============================================================================
# COMPOSER & DRUSH
# =============================================================================
log_info "Ejecutando composer install..."
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3

log_info "Ejecutando database updates..."
sudo -u www-data "$DRUSH" updatedb -y --root="$WEB_DIR" 2>&1 || log_warn "updatedb tuvo warnings"

log_info "Importando configuracion..."
sudo -u www-data "$DRUSH" config:import -y --root="$WEB_DIR" 2>&1 || log_warn "config:import tuvo warnings"

log_info "Reconstruyendo cache..."
sudo -u www-data "$DRUSH" cache:rebuild --root="$WEB_DIR" 2>/dev/null

# =============================================================================
# PERMISOS
# =============================================================================
log_info "Ajustando ownership..."
chown -R www-data:www-data "$WEB_DIR/sites/default/files" 2>/dev/null
chown -R www-data:www-data "$PROJECT_DIR/private" 2>/dev/null

# =============================================================================
# NGINX CONFIGS (actualizar si cambiaron)
# =============================================================================
DEPLOY_DIR="$PROJECT_DIR/config/deploy"
NGINX_CHANGED=0

if [ -f "$DEPLOY_DIR/nginx-jaraba-common.conf" ]; then
  if ! diff -q "$DEPLOY_DIR/nginx-jaraba-common.conf" /etc/nginx/snippets/jaraba-common.conf &>/dev/null; then
    cp "$DEPLOY_DIR/nginx-jaraba-common.conf" /etc/nginx/snippets/jaraba-common.conf
    log_info "Nginx snippet actualizado"
    NGINX_CHANGED=1
  fi
fi

if [ -f "$DEPLOY_DIR/nginx-metasites.conf" ]; then
  if ! diff -q "$DEPLOY_DIR/nginx-metasites.conf" /etc/nginx/sites-available/jaraba-metasites.conf &>/dev/null; then
    cp "$DEPLOY_DIR/nginx-metasites.conf" /etc/nginx/sites-available/jaraba-metasites.conf
    log_info "Nginx vhosts actualizado"
    NGINX_CHANGED=1
  fi
fi

if [ "$NGINX_CHANGED" -eq 1 ]; then
  if nginx -t 2>/dev/null; then
    systemctl reload nginx
    log_info "Nginx recargado"
  else
    log_error "Nginx config invalida — NO recargado"
  fi
fi

# =============================================================================
# VERIFICACION
# =============================================================================
log_info "Verificando sitios..."

for domain in plataformadeecosistemas.es pepejaraba.com jarabaimpact.com plataformadeecosistemas.com; do
  HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "https://$domain/" 2>/dev/null || echo "000")
  if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    log_info "https://$domain -> HTTP $HTTP_CODE OK"
  else
    log_warn "https://$domain -> HTTP $HTTP_CODE"
  fi
done

# =============================================================================
# RESUMEN
# =============================================================================
NEW_COMMIT=$(git rev-parse HEAD)
echo ""
log_info "=========================================="
log_info "DEPLOY COMPLETADO"
log_info "Commit anterior: ${CURRENT_COMMIT:0:12}"
log_info "Commit nuevo:    ${NEW_COMMIT:0:12}"
log_info "Backup BD:       $BACKUP_DIR/db_pre_deploy_$TIMESTAMP.sql.gz"
log_info "=========================================="
echo ""

# Limpiar backups antiguos (mantener ultimos 10).
ls -t "$BACKUP_DIR"/db_pre_deploy_*.sql.gz 2>/dev/null | tail -n +11 | xargs rm -f 2>/dev/null
