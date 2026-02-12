#!/bin/bash
# =============================================================================
# GO-LIVE ROLLBACK SCRIPT - Jaraba Impact Platform SaaS
# =============================================================================
# Ubicacion: scripts/golive/03_rollback.sh
# Uso: ./scripts/golive/03_rollback.sh <backup_timestamp> [--commit=HASH] [--force]
#
# Procedimiento automatizado de rollback:
# 1. Habilita modo mantenimiento
# 2. Restaura base de datos desde backup
# 3. Revierte codigo a commit/tag anterior
# 4. Ejecuta drush cr, updb, cim
# 5. Deshabilita modo mantenimiento
# 6. Ejecuta health check post-rollback
# 7. Notifica via Slack webhook
#
# DISENADO PARA: IONOS Shared Hosting
# PLATAFORMA: Drupal 11.x + MariaDB
#
# Ejemplo:
#   ./03_rollback.sh 20260212_143000
#   ./03_rollback.sh 20260212_143000 --commit=abc123f
#   ./03_rollback.sh 20260212_143000 --commit=v1.2.0 --force
# =============================================================================

set -uo pipefail

# =============================================================================
# CONFIGURACION
# =============================================================================

PROJECT_DIR="${PROJECT_DIR:-$HOME/JarabaImpactPlatformSaaS}"
WEB_DIR="$PROJECT_DIR/web"
SETTINGS_DIR="$WEB_DIR/sites/default"
BACKUP_DIR="${BACKUP_DIR:-$HOME/backups}"

# Comandos IONOS
PHP_CLI="${PHP_CLI:-/usr/bin/php8.4-cli}"
DRUSH="${DRUSH:-$PHP_CLI $PROJECT_DIR/vendor/bin/drush.php}"
COMPOSER="${COMPOSER:-$PHP_CLI $HOME/bin/composer.phar}"

# URLs
SITE_URL="${SITE_URL:-https://plataformadeecosistemas.com}"

# Slack webhook (configurar con variable de entorno)
SLACK_WEBHOOK_URL="${SLACK_WEBHOOK_URL:-}"

# Notificacion por email (fallback)
NOTIFY_EMAIL="${NOTIFY_EMAIL:-contacto@pepejaraba.es}"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Estado
ROLLBACK_SUCCESS=false
MAINTENANCE_ENABLED=false
ORIGINAL_COMMIT=""
ROLLBACK_LOG=""

# =============================================================================
# FUNCIONES DE UTILIDAD
# =============================================================================

timestamp() {
    date '+%Y-%m-%d %H:%M:%S'
}

log_step() {
    local step="$1"
    local message="$2"
    echo ""
    echo -e "${CYAN}${BOLD}[$step]${NC} ${BOLD}$message${NC}"
    ROLLBACK_LOG="${ROLLBACK_LOG}\n[$step] $message"
}

log_info() {
    echo -e "  ${BLUE}[INFO]${NC} $1"
    ROLLBACK_LOG="${ROLLBACK_LOG}\n  [INFO] $1"
}

log_ok() {
    echo -e "  ${GREEN}[  OK]${NC} $1"
    ROLLBACK_LOG="${ROLLBACK_LOG}\n  [OK] $1"
}

log_error() {
    echo -e "  ${RED}[ERROR]${NC} $1"
    ROLLBACK_LOG="${ROLLBACK_LOG}\n  [ERROR] $1"
}

log_warn() {
    echo -e "  ${YELLOW}[WARN]${NC} $1"
    ROLLBACK_LOG="${ROLLBACK_LOG}\n  [WARN] $1"
}

# Limpieza en caso de error fatal
cleanup_on_error() {
    echo ""
    log_error "=========================================="
    log_error "ROLLBACK INTERRUMPIDO POR ERROR"
    log_error "=========================================="

    # Si el modo mantenimiento esta habilitado, intentar deshabilitarlo
    if [ "$MAINTENANCE_ENABLED" = true ]; then
        log_warn "Intentando deshabilitar modo mantenimiento..."
        cd "$PROJECT_DIR" && $DRUSH state:set system.maintenance_mode 0 2>/dev/null || true
        cd "$PROJECT_DIR" && $DRUSH cr 2>/dev/null || true
        log_info "Modo mantenimiento deshabilitado (intento de recuperacion)"
    fi

    # Notificar del fallo
    send_notification "ROLLBACK FALLIDO" \
        "El rollback fue interrumpido por un error.\nRevision manual requerida.\nCommit actual: $(cd "$PROJECT_DIR" && git rev-parse HEAD 2>/dev/null || echo 'unknown')\nTimestamp: $(timestamp)"

    exit 1
}

trap cleanup_on_error ERR

# =============================================================================
# NOTIFICACIONES
# =============================================================================

send_slack_notification() {
    local title="$1"
    local message="$2"
    local color="${3:-danger}"

    if [ -z "$SLACK_WEBHOOK_URL" ]; then
        return 1
    fi

    local payload
    payload=$(cat <<EOJSON
{
    "attachments": [
        {
            "color": "$color",
            "title": "Jaraba Impact Platform - $title",
            "text": "$message",
            "fields": [
                {"title": "Entorno", "value": "Produccion (IONOS)", "short": true},
                {"title": "Timestamp", "value": "$(timestamp)", "short": true},
                {"title": "URL", "value": "$SITE_URL", "short": true},
                {"title": "Servidor", "value": "$(hostname)", "short": true}
            ],
            "footer": "Jaraba Go-Live Rollback Script",
            "ts": $(date +%s)
        }
    ]
}
EOJSON
)

    curl -s -X POST \
        -H "Content-Type: application/json" \
        -d "$payload" \
        "$SLACK_WEBHOOK_URL" > /dev/null 2>&1 || true
}

send_email_notification() {
    local subject="$1"
    local body="$2"

    if command -v mail &> /dev/null; then
        echo -e "$body" | mail -s "[Jaraba Rollback] $subject" "$NOTIFY_EMAIL" 2>/dev/null || true
    fi
}

send_notification() {
    local title="$1"
    local message="$2"
    local color="${3:-danger}"

    # Intentar Slack primero
    if [ -n "$SLACK_WEBHOOK_URL" ]; then
        send_slack_notification "$title" "$message" "$color"
        log_info "Notificacion Slack enviada"
    fi

    # Email como fallback o complemento
    send_email_notification "$title" "$message"
    log_info "Notificacion email enviada a $NOTIFY_EMAIL"
}

# =============================================================================
# ARGUMENTOS
# =============================================================================

BACKUP_TIMESTAMP="${1:-}"
TARGET_COMMIT=""
FORCE_MODE=false

# Parsear argumentos opcionales
shift 1 2>/dev/null || true
for arg in "$@"; do
    case $arg in
        --commit=*)
            TARGET_COMMIT="${arg#*=}"
            ;;
        --force)
            FORCE_MODE=true
            ;;
        --help|-h)
            echo "Uso: $0 <backup_timestamp> [--commit=HASH|TAG] [--force]"
            echo ""
            echo "Parametros:"
            echo "  backup_timestamp     Timestamp del backup (ej: 20260212_143000)"
            echo "  --commit=HASH        Commit o tag al que revertir (por defecto: HEAD~1)"
            echo "  --force              Omitir confirmacion interactiva"
            echo "  --help, -h           Mostrar esta ayuda"
            echo ""
            echo "Ejemplos:"
            echo "  $0 20260212_143000"
            echo "  $0 20260212_143000 --commit=abc123f"
            echo "  $0 20260212_143000 --commit=v1.2.0 --force"
            echo ""
            echo "Variables de entorno:"
            echo "  SLACK_WEBHOOK_URL    URL del webhook de Slack para notificaciones"
            echo "  SITE_URL             URL del sitio (default: https://plataformadeecosistemas.com)"
            echo "  BACKUP_DIR           Directorio de backups (default: ~/backups)"
            exit 0
            ;;
    esac
done

# =============================================================================
# VALIDACIONES PREVIAS
# =============================================================================

validate_inputs() {
    log_step "0/7" "VALIDACIONES PREVIAS"

    # Verificar timestamp proporcionado
    if [ -z "$BACKUP_TIMESTAMP" ]; then
        log_error "Debe proporcionar un backup_timestamp"
        echo ""
        echo "  Uso: $0 <backup_timestamp> [--commit=HASH] [--force]"
        echo ""
        echo "  Backups disponibles:"
        ls -la "$BACKUP_DIR"/db_pre_deploy_*.sql.gz 2>/dev/null | tail -10 || echo "    (ninguno encontrado)"
        echo ""
        exit 1
    fi

    # Verificar que el backup existe
    local backup_file="$BACKUP_DIR/db_pre_deploy_${BACKUP_TIMESTAMP}.sql.gz"
    if [ ! -f "$backup_file" ]; then
        log_error "Backup no encontrado: $backup_file"
        echo ""
        echo "  Backups disponibles:"
        ls -la "$BACKUP_DIR"/db_pre_deploy_*.sql.gz 2>/dev/null | tail -10 || echo "    (ninguno encontrado)"
        echo ""
        exit 1
    fi
    log_ok "Backup encontrado: $backup_file ($(du -h "$backup_file" | cut -f1))"

    # Verificar directorio del proyecto
    if [ ! -f "$PROJECT_DIR/composer.json" ]; then
        log_error "Directorio del proyecto no valido: $PROJECT_DIR"
        exit 1
    fi
    log_ok "Directorio del proyecto: $PROJECT_DIR"

    # Verificar drush
    if ! cd "$PROJECT_DIR" && $DRUSH status --field=bootstrap > /dev/null 2>&1; then
        log_error "Drush no funciona o Drupal no puede bootstrapear"
        exit 1
    fi
    log_ok "Drush operativo"

    # Guardar commit actual
    ORIGINAL_COMMIT=$(cd "$PROJECT_DIR" && git rev-parse HEAD)
    log_info "Commit actual: $ORIGINAL_COMMIT"

    # Determinar commit objetivo
    if [ -z "$TARGET_COMMIT" ]; then
        TARGET_COMMIT=$(cd "$PROJECT_DIR" && git rev-parse HEAD~1 2>/dev/null) || {
            log_error "No se puede determinar commit anterior"
            exit 1
        }
        log_info "Commit objetivo (HEAD~1): $TARGET_COMMIT"
    else
        # Verificar que el commit/tag existe
        if ! cd "$PROJECT_DIR" && git rev-parse "$TARGET_COMMIT" > /dev/null 2>&1; then
            log_error "Commit/tag no encontrado: $TARGET_COMMIT"
            exit 1
        fi
        local resolved_commit
        resolved_commit=$(cd "$PROJECT_DIR" && git rev-parse "$TARGET_COMMIT")
        log_info "Commit objetivo ($TARGET_COMMIT): $resolved_commit"
        TARGET_COMMIT="$resolved_commit"
    fi

    # Verificar que settings.local.php existe (no se pierde en rollback)
    if [ -f "$SETTINGS_DIR/settings.local.php" ]; then
        log_ok "settings.local.php presente"
    else
        log_warn "settings.local.php NO encontrado - verificar credenciales post-rollback"
    fi

    # Confirmacion interactiva
    if [ "$FORCE_MODE" = false ]; then
        echo ""
        echo -e "${RED}${BOLD}  ATENCION: Este script ejecutara un rollback completo.${NC}"
        echo ""
        echo "  Base de datos:  Se restaurara desde $BACKUP_TIMESTAMP"
        echo "  Codigo:         Se revertira a $TARGET_COMMIT"
        echo "  Downtime:       El sitio estara en mantenimiento durante el proceso"
        echo ""
        read -p "  Confirmar rollback? (escriba 'ROLLBACK' para continuar): " confirmation
        if [ "$confirmation" != "ROLLBACK" ]; then
            log_info "Rollback cancelado por el usuario"
            exit 0
        fi
    fi
}

# =============================================================================
# PASO 1: MODO MANTENIMIENTO
# =============================================================================

enable_maintenance_mode() {
    log_step "1/7" "HABILITANDO MODO MANTENIMIENTO"

    cd "$PROJECT_DIR"
    $DRUSH state:set system.maintenance_mode 1 2>&1 || {
        log_error "No se pudo habilitar modo mantenimiento"
        return 1
    }
    $DRUSH cr 2>/dev/null || true

    MAINTENANCE_ENABLED=true
    log_ok "Modo mantenimiento habilitado"

    # Verificar que el sitio muestra pagina de mantenimiento
    local http_code
    http_code=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout 10 --max-time 20 \
        "$SITE_URL/" 2>/dev/null) || true

    log_info "Sitio respondiendo con HTTP $http_code (503 esperado)"

    # Notificar inicio de rollback
    send_notification "ROLLBACK INICIADO" \
        "Se ha iniciado el rollback de la plataforma.\nBackup: $BACKUP_TIMESTAMP\nCommit objetivo: $TARGET_COMMIT\nSitio en mantenimiento." \
        "warning"
}

# =============================================================================
# PASO 2: BACKUP DE SEGURIDAD
# =============================================================================

create_safety_backup() {
    log_step "2/7" "BACKUP DE SEGURIDAD (ESTADO ACTUAL)"

    local safety_timestamp
    safety_timestamp=$(date +%Y%m%d_%H%M%S)

    cd "$PROJECT_DIR"

    # Backup de la base de datos actual (antes de restaurar)
    local safety_db="$BACKUP_DIR/db_pre_rollback_${safety_timestamp}.sql.gz"
    $DRUSH sql-dump --gzip > "$safety_db" 2>/dev/null || {
        log_warn "No se pudo crear backup de seguridad de BD"
    }
    log_ok "Backup de seguridad BD: $safety_db"

    # Backup de settings.local.php
    if [ -f "$SETTINGS_DIR/settings.local.php" ]; then
        cp "$SETTINGS_DIR/settings.local.php" "$BACKUP_DIR/settings.local_pre_rollback_${safety_timestamp}.php"
        log_ok "Backup de settings.local.php"
    fi

    # Guardar referencia del commit actual
    echo "$ORIGINAL_COMMIT" > "$BACKUP_DIR/commit_pre_rollback_${safety_timestamp}.txt"
    log_ok "Commit pre-rollback guardado: $ORIGINAL_COMMIT"
}

# =============================================================================
# PASO 3: RESTAURAR BASE DE DATOS
# =============================================================================

restore_database() {
    log_step "3/7" "RESTAURANDO BASE DE DATOS"

    local backup_file="$BACKUP_DIR/db_pre_deploy_${BACKUP_TIMESTAMP}.sql.gz"

    cd "$PROJECT_DIR"

    log_info "Restaurando desde: $backup_file"
    log_info "Tamano del backup: $(du -h "$backup_file" | cut -f1)"

    # Restaurar la base de datos
    local restore_start
    restore_start=$(date +%s)

    zcat "$backup_file" | $DRUSH sql:cli 2>&1 || {
        log_error "Error restaurando la base de datos"
        return 1
    }

    local restore_end
    restore_end=$(date +%s)
    local restore_duration=$((restore_end - restore_start))

    log_ok "Base de datos restaurada en ${restore_duration}s"

    # Verificar integridad basica
    local table_count
    table_count=$(cd "$PROJECT_DIR" && $DRUSH sql:query "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();" 2>/dev/null | head -1)
    log_info "Tablas en BD: ${table_count:-unknown}"
}

# =============================================================================
# PASO 4: REVERTIR CODIGO
# =============================================================================

revert_code() {
    log_step "4/7" "REVIRTIENDO CODIGO"

    cd "$PROJECT_DIR"

    # Desbloquear permisos para git checkout
    log_info "Desbloqueando permisos para git..."
    chmod 755 "$SETTINGS_DIR" 2>/dev/null || true
    chmod 644 "$SETTINGS_DIR/settings.php" 2>/dev/null || true

    # Guardar settings.local.php antes del checkout
    local settings_local_backup=""
    if [ -f "$SETTINGS_DIR/settings.local.php" ]; then
        settings_local_backup=$(mktemp)
        cp "$SETTINGS_DIR/settings.local.php" "$settings_local_backup"
        log_info "settings.local.php protegido temporalmente"
    fi

    # Git fetch y checkout
    log_info "Ejecutando git fetch..."
    git fetch origin 2>&1 || log_warn "git fetch fallo (puede continuar offline)"

    log_info "Checkout a $TARGET_COMMIT..."
    git checkout "$TARGET_COMMIT" 2>&1 || {
        log_error "git checkout fallo"
        # Restaurar settings.local.php si se perdio
        if [ -n "$settings_local_backup" ] && [ ! -f "$SETTINGS_DIR/settings.local.php" ]; then
            cp "$settings_local_backup" "$SETTINGS_DIR/settings.local.php"
        fi
        rm -f "$settings_local_backup"
        return 1
    }

    # Restaurar settings.local.php si fue eliminado por el checkout
    if [ -n "$settings_local_backup" ]; then
        if [ ! -f "$SETTINGS_DIR/settings.local.php" ]; then
            cp "$settings_local_backup" "$SETTINGS_DIR/settings.local.php"
            log_info "settings.local.php restaurado"
        fi
        rm -f "$settings_local_backup"
    fi

    # Restaurar permisos seguros
    chmod 555 "$SETTINGS_DIR" 2>/dev/null || true
    chmod 444 "$SETTINGS_DIR/settings.php" 2>/dev/null || true

    # Habilitar RewriteBase (necesario para IONOS)
    if [ -f "$WEB_DIR/.htaccess" ] && grep -q "# RewriteBase /" "$WEB_DIR/.htaccess"; then
        sed -i 's/# RewriteBase \//RewriteBase \//' "$WEB_DIR/.htaccess"
        log_info "RewriteBase habilitado en .htaccess"
    fi

    local new_commit
    new_commit=$(git rev-parse HEAD)
    log_ok "Codigo revertido a: $new_commit"

    # Reinstalar dependencias de composer
    log_info "Ejecutando composer install..."
    $COMPOSER install --no-dev --optimize-autoloader 2>&1 || {
        log_error "composer install fallo"
        return 1
    }
    log_ok "Dependencias de composer instaladas"
}

# =============================================================================
# PASO 5: RECONSTRUIR DRUPAL
# =============================================================================

rebuild_drupal() {
    log_step "5/7" "RECONSTRUYENDO DRUPAL"

    cd "$PROJECT_DIR"

    # Cache rebuild
    log_info "Ejecutando drush cr..."
    $DRUSH cr 2>&1 || {
        log_warn "drush cr devolvio error (puede ser normal post-rollback)"
    }
    log_ok "Cache reconstruido"

    # Database updates
    log_info "Ejecutando drush updb..."
    $DRUSH updatedb -y 2>&1 || {
        log_warn "drush updb devolvio warnings"
    }
    log_ok "Database updates aplicados"

    # Config import (si hay config exportada)
    if [ -d "$PROJECT_DIR/config/sync" ] && [ "$(ls -A "$PROJECT_DIR/config/sync" 2>/dev/null)" ]; then
        log_info "Ejecutando drush cim..."
        $DRUSH config:import -y 2>&1 || {
            log_warn "drush cim devolvio warnings (verificar manualmente)"
        }
        log_ok "Configuracion importada"
    else
        log_info "Sin directorio config/sync - omitiendo cim"
    fi

    # Cache rebuild final
    $DRUSH cr 2>/dev/null || true
    log_ok "Cache rebuild final completado"
}

# =============================================================================
# PASO 6: DESHABILITAR MODO MANTENIMIENTO
# =============================================================================

disable_maintenance_mode() {
    log_step "6/7" "DESHABILITANDO MODO MANTENIMIENTO"

    cd "$PROJECT_DIR"

    $DRUSH state:set system.maintenance_mode 0 2>&1 || {
        log_error "No se pudo deshabilitar modo mantenimiento"
        log_warn "Ejecutar manualmente: drush state:set system.maintenance_mode 0"
        return 1
    }
    $DRUSH cr 2>/dev/null || true

    MAINTENANCE_ENABLED=false
    log_ok "Modo mantenimiento deshabilitado"
}

# =============================================================================
# PASO 7: HEALTH CHECK POST-ROLLBACK
# =============================================================================

post_rollback_health_check() {
    log_step "7/7" "HEALTH CHECK POST-ROLLBACK"

    local checks_passed=0
    local checks_failed=0

    # Test 1: Drupal bootstrap
    local bootstrap
    bootstrap=$(cd "$PROJECT_DIR" && $DRUSH status --field=bootstrap 2>/dev/null) || true
    if [ "$bootstrap" = "Successful" ]; then
        log_ok "Drupal bootstrap: OK"
        checks_passed=$((checks_passed + 1))
    else
        log_error "Drupal bootstrap: FALLO ($bootstrap)"
        checks_failed=$((checks_failed + 1))
    fi

    # Test 2: Homepage responds
    local http_code
    http_code=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout 10 --max-time 30 \
        "$SITE_URL/" 2>/dev/null) || http_code="000"

    if [ "$http_code" = "200" ] || [ "$http_code" = "302" ]; then
        log_ok "Homepage: HTTP $http_code"
        checks_passed=$((checks_passed + 1))
    else
        log_error "Homepage: HTTP $http_code"
        checks_failed=$((checks_failed + 1))
    fi

    # Test 3: Database query
    local db_test
    db_test=$(cd "$PROJECT_DIR" && $DRUSH sql:query "SELECT 1" 2>/dev/null) || true
    if echo "$db_test" | grep -q "1"; then
        log_ok "Base de datos: Conectividad OK"
        checks_passed=$((checks_passed + 1))
    else
        log_error "Base de datos: Sin conectividad"
        checks_failed=$((checks_failed + 1))
    fi

    # Test 4: Login page
    local login_code
    login_code=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout 10 --max-time 20 \
        "$SITE_URL/user/login" 2>/dev/null) || login_code="000"

    if [ "$login_code" = "200" ]; then
        log_ok "Login page: HTTP $login_code"
        checks_passed=$((checks_passed + 1))
    else
        log_error "Login page: HTTP $login_code"
        checks_failed=$((checks_failed + 1))
    fi

    # Test 5: Modo mantenimiento desactivado
    local maint_mode
    maint_mode=$(cd "$PROJECT_DIR" && $DRUSH php:eval "echo \Drupal::state()->get('system.maintenance_mode', 0);" 2>/dev/null) || true
    if [ "${maint_mode:-1}" = "0" ]; then
        log_ok "Modo mantenimiento: Desactivado"
        checks_passed=$((checks_passed + 1))
    else
        log_error "Modo mantenimiento: AUN ACTIVO"
        checks_failed=$((checks_failed + 1))
    fi

    # Test 6: Vertical homepage (empleo como canary)
    local vertical_code
    vertical_code=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout 10 --max-time 20 \
        "$SITE_URL/empleo" 2>/dev/null) || vertical_code="000"

    if [ "$vertical_code" = "200" ] || [ "$vertical_code" = "302" ]; then
        log_ok "Vertical canary (/empleo): HTTP $vertical_code"
        checks_passed=$((checks_passed + 1))
    else
        log_warn "Vertical canary (/empleo): HTTP $vertical_code"
    fi

    echo ""
    log_info "Health check: $checks_passed OK, $checks_failed FAIL"

    if [ "$checks_failed" -gt 0 ]; then
        ROLLBACK_SUCCESS=false
        return 1
    fi

    ROLLBACK_SUCCESS=true
    return 0
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    local start_time
    start_time=$(date +%s)

    echo ""
    echo -e "${RED}${BOLD}================================================================${NC}"
    echo -e "${RED}${BOLD}  JARABA IMPACT PLATFORM - ROLLBACK PROCEDURE${NC}"
    echo -e "${RED}${BOLD}  $(timestamp)${NC}"
    echo -e "${RED}${BOLD}================================================================${NC}"

    # Validar inputs y confirmar
    validate_inputs

    # Ejecutar los 7 pasos del rollback
    enable_maintenance_mode
    create_safety_backup
    restore_database
    revert_code
    rebuild_drupal
    disable_maintenance_mode

    # Health check
    local health_ok=true
    post_rollback_health_check || health_ok=false

    # =============================================================================
    # RESUMEN FINAL
    # =============================================================================
    local end_time
    end_time=$(date +%s)
    local duration=$((end_time - start_time))

    echo ""
    echo -e "${BOLD}================================================================${NC}"
    echo -e "${BOLD}  RESUMEN DEL ROLLBACK${NC}"
    echo -e "${BOLD}================================================================${NC}"
    echo ""
    echo -e "  Backup restaurado:    $BACKUP_TIMESTAMP"
    echo -e "  Commit anterior:      $ORIGINAL_COMMIT"
    echo -e "  Commit actual:        $(cd "$PROJECT_DIR" && git rev-parse HEAD 2>/dev/null)"
    echo -e "  Duracion:             ${duration}s"
    echo ""

    if [ "$health_ok" = true ]; then
        echo -e "  ${GREEN}${BOLD}RESULTADO: ROLLBACK COMPLETADO CON EXITO${NC}"
        echo -e "  ${GREEN}La plataforma ha sido revertida y esta operativa.${NC}"
        echo ""

        send_notification "ROLLBACK EXITOSO" \
            "Rollback completado con exito.\nBackup: $BACKUP_TIMESTAMP\nCommit: $(cd "$PROJECT_DIR" && git rev-parse HEAD 2>/dev/null)\nDuracion: ${duration}s\nHealth check: PASS" \
            "good"
        return 0
    else
        echo -e "  ${RED}${BOLD}RESULTADO: ROLLBACK CON PROBLEMAS${NC}"
        echo -e "  ${RED}El rollback se ejecuto pero el health check fallo.${NC}"
        echo -e "  ${RED}Se requiere revision manual inmediata.${NC}"
        echo ""

        send_notification "ROLLBACK CON PROBLEMAS" \
            "Rollback ejecutado pero health check fallo.\nBackup: $BACKUP_TIMESTAMP\nCommit: $(cd "$PROJECT_DIR" && git rev-parse HEAD 2>/dev/null)\nDuracion: ${duration}s\nHealth check: FAIL\nACCION REQUERIDA: Revision manual inmediata." \
            "danger"
        return 1
    fi
}

# Solo ejecutar si no es sourced
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
