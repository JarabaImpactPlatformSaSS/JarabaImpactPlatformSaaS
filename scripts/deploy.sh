#!/bin/bash
# =============================================================================
# SCRIPT DE DEPLOY SEGURO - Jaraba Impact Platform v2.0
# =============================================================================
# Ubicación: ~/JarabaImpactPlatformSaaS/scripts/deploy.sh
# Uso: ./scripts/deploy.sh
#
# DISEÑADO PARA: IONOS Shared Hosting
# PROBADO: 2026-01-10
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Variables
PROJECT_DIR="$HOME/JarabaImpactPlatformSaaS"
WEB_DIR="$PROJECT_DIR/web"
SETTINGS_DIR="$WEB_DIR/sites/default"
SETTINGS_LOCAL="$SETTINGS_DIR/settings.local.php"
HTACCESS="$WEB_DIR/.htaccess"
BACKUP_DIR="$HOME/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Comandos IONOS
PHP_CLI="/usr/bin/php8.4-cli"
COMPOSER="$PHP_CLI $HOME/bin/composer.phar"
DRUSH="$PHP_CLI $PROJECT_DIR/vendor/bin/drush.php"

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# =============================================================================
# PRE-FLIGHT CHECKS
# =============================================================================
log_info "=========================================="
log_info "DEPLOY SEGURO IONOS - $TIMESTAMP"
log_info "=========================================="

cd "$PROJECT_DIR"

# Verificar que estamos en el directorio correcto
if [ ! -f "composer.json" ]; then
    log_error "No estamos en el directorio del proyecto. Abortando."
    exit 1
fi

# Verificar que settings.local.php existe
if [ ! -f "$SETTINGS_LOCAL" ]; then
    log_error "settings.local.php NO EXISTE. Las credenciales de IONOS se perderían."
    log_error "Ejecute el procedimiento de recreación en el runbook."
    exit 1
fi

# Verificar conexión BD antes de continuar
log_info "Verificando conexión a base de datos..."
if ! $DRUSH sql:query "SELECT 1" > /dev/null 2>&1; then
    log_error "No hay conexión a la base de datos. Verificar settings.local.php"
    exit 1
fi
log_info "Conexión BD OK"

# =============================================================================
# BACKUP
# =============================================================================
mkdir -p "$BACKUP_DIR"

log_info "Creando backup de BD..."
$DRUSH sql-dump --gzip > "$BACKUP_DIR/db_pre_deploy_$TIMESTAMP.sql.gz"
log_info "Backup: $BACKUP_DIR/db_pre_deploy_$TIMESTAMP.sql.gz"

log_info "Backup de settings.local.php..."
cp "$SETTINGS_LOCAL" "$BACKUP_DIR/settings.local_$TIMESTAMP.php"

CURRENT_COMMIT=$(git rev-parse HEAD)
log_info "Commit actual: $CURRENT_COMMIT"

# =============================================================================
# DEPLOY
# =============================================================================
log_info "Desbloqueando permisos..."
chmod 755 "$SETTINGS_DIR"
chmod 644 "$SETTINGS_DIR/settings.php"

log_info "Actualizando código..."
git fetch origin
git pull origin main

log_info "Restaurando permisos seguros..."
chmod 555 "$SETTINGS_DIR"
chmod 444 "$SETTINGS_DIR/settings.php"

# Verificar que settings.local.php no fue sobrescrito
if [ ! -f "$SETTINGS_LOCAL" ]; then
    log_warn "settings.local.php fue eliminado. Restaurando backup..."
    cp "$BACKUP_DIR/settings.local_$TIMESTAMP.php" "$SETTINGS_LOCAL"
fi

# Habilitar RewriteBase (necesario para IONOS)
if grep -q "# RewriteBase /" "$HTACCESS"; then
    log_info "Habilitando RewriteBase..."
    sed -i 's/# RewriteBase \//RewriteBase \//' "$HTACCESS"
fi

# =============================================================================
# COMPOSER & DRUSH
# =============================================================================
log_info "Ejecutando composer install..."
$COMPOSER install --no-dev --optimize-autoloader

log_info "Ejecutando database updates..."
$DRUSH updatedb -y || log_warn "updatedb tuvo warnings (puede ser normal)"

log_info "Limpiando caché..."
$DRUSH cr

# =============================================================================
# VERIFICACIÓN
# =============================================================================
log_info "Verificando sitio..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "https://plataformadeecosistemas.com/" 2>/dev/null || echo "000")
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    log_info "Sitio respondiendo correctamente (HTTP $HTTP_CODE)"
else
    log_warn "Sitio devuelve HTTP $HTTP_CODE - verificar manualmente"
fi

# =============================================================================
# RESUMEN
# =============================================================================
NEW_COMMIT=$(git rev-parse HEAD)
log_info "=========================================="
log_info "DEPLOY COMPLETADO"
log_info "Commit anterior: $CURRENT_COMMIT"
log_info "Commit nuevo:    $NEW_COMMIT"
log_info "Backup BD:       $BACKUP_DIR/db_pre_deploy_$TIMESTAMP.sql.gz"
log_info "=========================================="
