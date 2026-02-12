#!/bin/bash
# =============================================================================
# RESTORE TENANT — Per-Tenant Backup/Restore (F10 Doc 187)
# =============================================================================
# Ubicacion: scripts/restore_tenant.sh
#
# Backup y restauracion de datos de un solo tenant (Group entity).
# Auto-descubre todas las tablas con columna tenant_id.
#
# Uso:
#   ./scripts/restore_tenant.sh backup  <tenant_id> [--output=FILE]
#   ./scripts/restore_tenant.sh restore <tenant_id> <backup_file> [--force]
#   ./scripts/restore_tenant.sh list
#   ./scripts/restore_tenant.sh tables
#
# Entornos:
#   - Dev (Lando):  ENVIRONMENT=lando (default)
#   - Prod (IONOS): ENVIRONMENT=ionos
#
# Ejemplo:
#   ./scripts/restore_tenant.sh backup 5
#   ./scripts/restore_tenant.sh backup 5 --output=/backups/tenant_5_manual.sql.gz
#   ./scripts/restore_tenant.sh restore 5 /backups/tenant_5_20260212.sql.gz
#   ./scripts/restore_tenant.sh restore 5 /backups/tenant_5_20260212.sql.gz --force
# =============================================================================

set -euo pipefail

# =============================================================================
# CONFIGURACION
# =============================================================================

ENVIRONMENT="${ENVIRONMENT:-lando}"
BACKUP_DIR="${BACKUP_DIR:-/tmp/tenant_backups}"
PROJECT_DIR="${PROJECT_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"

# Database connection (overridable via env vars)
DB_HOST="${DB_HOST:-database}"
DB_PORT="${DB_PORT:-3306}"
DB_NAME="${DB_NAME:-drupal11}"
DB_USER="${DB_USER:-drupal11}"
DB_PASS="${DB_PASS:-drupal11}"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# =============================================================================
# FUNCIONES DE UTILIDAD
# =============================================================================

timestamp() {
    date '+%Y%m%d_%H%M%S'
}

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

log_step() {
    echo -e "\n${CYAN}${BOLD}[$1]${NC} ${BOLD}$2${NC}"
}

# Ejecutar MySQL segun entorno
run_mysql() {
    if [ "$ENVIRONMENT" = "lando" ]; then
        lando mysql --skip-column-names -e "$1" 2>/dev/null
    else
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
            "$DB_NAME" --skip-column-names -e "$1" 2>/dev/null
    fi
}

# Ejecutar mysqldump segun entorno
run_mysqldump() {
    if [ "$ENVIRONMENT" = "lando" ]; then
        lando mysqldump "$@" 2>/dev/null
    else
        mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
            "$DB_NAME" "$@" 2>/dev/null
    fi
}

# Ejecutar drush segun entorno
run_drush() {
    if [ "$ENVIRONMENT" = "lando" ]; then
        lando drush "$@" 2>/dev/null
    else
        cd "$PROJECT_DIR" && vendor/bin/drush "$@" 2>/dev/null
    fi
}

# =============================================================================
# AUTO-DESCUBRIMIENTO DE TABLAS
# =============================================================================

# Obtiene todas las tablas que tienen columna tenant_id
discover_tenant_tables() {
    run_mysql "
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME = 'tenant_id'
          AND TABLE_SCHEMA = DATABASE()
        ORDER BY TABLE_NAME;
    "
}

# Obtiene tablas del Group module (grupo principal del tenant)
get_group_tables() {
    run_mysql "
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND (TABLE_NAME LIKE 'groups%'
               OR TABLE_NAME LIKE 'group_%')
        ORDER BY TABLE_NAME;
    "
}

# =============================================================================
# COMANDO: LIST — Listar tenants activos
# =============================================================================

cmd_list() {
    log_step "1/1" "Listando tenants activos"

    echo ""
    echo -e "${BOLD}ID   | Label                          | Type         | Created${NC}"
    echo "-----+--------------------------------+--------------+---------------------"

    run_mysql "
        SELECT CONCAT(
            LPAD(id, 4, ' '), ' | ',
            RPAD(IFNULL(label, '(sin nombre)'), 30, ' '), ' | ',
            RPAD(IFNULL(type, 'default'), 12, ' '), ' | ',
            FROM_UNIXTIME(created)
        )
        FROM groups_field_data
        ORDER BY id;
    " || log_warn "No se pudo consultar la tabla groups_field_data"

    echo ""
}

# =============================================================================
# COMANDO: TABLES — Mostrar tablas con tenant_id
# =============================================================================

cmd_tables() {
    log_step "1/2" "Auto-descubriendo tablas con columna tenant_id"

    local tables
    tables=$(discover_tenant_tables)
    local count
    count=$(echo "$tables" | grep -c '.' || true)

    echo ""
    echo -e "${BOLD}Tablas con tenant_id: $count${NC}"
    echo "-------------------------------------------"
    echo "$tables" | while read -r table; do
        local rows
        rows=$(run_mysql "SELECT COUNT(*) FROM \`$table\`;" || echo "?")
        printf "  %-50s %s filas\n" "$table" "$rows"
    done

    log_step "2/2" "Tablas del Group module"
    local group_tables
    group_tables=$(get_group_tables)
    echo "$group_tables" | while read -r table; do
        local rows
        rows=$(run_mysql "SELECT COUNT(*) FROM \`$table\`;" || echo "?")
        printf "  %-50s %s filas\n" "$table" "$rows"
    done

    echo ""
}

# =============================================================================
# COMANDO: BACKUP — Exportar datos de un tenant
# =============================================================================

cmd_backup() {
    local tenant_id="$1"
    local output_file="${2:-}"

    # Validar que el tenant existe
    local tenant_label
    tenant_label=$(run_mysql "SELECT label FROM groups_field_data WHERE id = $tenant_id LIMIT 1;")
    if [ -z "$tenant_label" ]; then
        log_error "Tenant ID $tenant_id no encontrado."
        exit 1
    fi

    log_info "Tenant: #$tenant_id — $tenant_label"

    # Crear directorio de backup
    mkdir -p "$BACKUP_DIR"

    # Nombre de archivo
    if [ -z "$output_file" ]; then
        output_file="$BACKUP_DIR/tenant_${tenant_id}_$(timestamp).sql.gz"
    fi

    local sql_file="${output_file%.gz}"
    if [[ "$output_file" == *.gz ]]; then
        sql_file="/tmp/tenant_${tenant_id}_$(timestamp).sql"
    else
        sql_file="$output_file"
    fi

    # Cabecera del backup
    log_step "1/4" "Generando cabecera del backup"
    cat > "$sql_file" <<HEADER
-- =============================================================================
-- TENANT BACKUP — Jaraba Impact Platform SaaS
-- =============================================================================
-- Tenant ID:    $tenant_id
-- Tenant Label: $tenant_label
-- Timestamp:    $(date '+%Y-%m-%d %H:%M:%S')
-- Environment:  $ENVIRONMENT
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

HEADER

    # Backup de la entidad Group
    log_step "2/4" "Exportando Group entity #$tenant_id"
    local group_tables
    group_tables=$(get_group_tables)

    for table in $group_tables; do
        local has_id
        has_id=$(run_mysql "
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '$table'
              AND COLUMN_NAME = 'id';
        ")
        if [ "$has_id" -gt 0 ]; then
            local row_count
            row_count=$(run_mysql "SELECT COUNT(*) FROM \`$table\` WHERE id = $tenant_id;")
            if [ "$row_count" -gt 0 ]; then
                echo "-- Group table: $table ($row_count rows)" >> "$sql_file"
                run_mysqldump --no-create-info --complete-insert \
                    --where="id = $tenant_id" "$table" >> "$sql_file" 2>/dev/null || true
                echo "" >> "$sql_file"
                log_success "  $table: $row_count filas"
            fi
        fi
    done

    # Backup de todas las tablas con tenant_id
    log_step "3/4" "Exportando entidades del tenant"
    local tenant_tables
    tenant_tables=$(discover_tenant_tables)
    local total_rows=0

    for table in $tenant_tables; do
        local row_count
        row_count=$(run_mysql "SELECT COUNT(*) FROM \`$table\` WHERE tenant_id = $tenant_id;")
        if [ "$row_count" -gt 0 ]; then
            echo "-- Tenant table: $table ($row_count rows)" >> "$sql_file"
            run_mysqldump --no-create-info --complete-insert \
                --where="tenant_id = $tenant_id" "$table" >> "$sql_file" 2>/dev/null || true
            echo "" >> "$sql_file"
            total_rows=$((total_rows + row_count))
            log_success "  $table: $row_count filas"
        fi
    done

    # Footer
    cat >> "$sql_file" <<FOOTER

SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = 1;

-- Total rows exported: $total_rows
-- Backup complete.
FOOTER

    # Comprimir si es .gz
    log_step "4/4" "Finalizando backup"
    if [[ "$output_file" == *.gz ]]; then
        gzip -c "$sql_file" > "$output_file"
        rm -f "$sql_file"
        local size
        size=$(du -h "$output_file" | cut -f1)
        log_success "Backup comprimido: $output_file ($size)"
    else
        local size
        size=$(du -h "$sql_file" | cut -f1)
        log_success "Backup: $sql_file ($size)"
    fi

    echo ""
    echo -e "${GREEN}${BOLD}Backup completado${NC}"
    echo -e "  Tenant:     #$tenant_id ($tenant_label)"
    echo -e "  Filas:      $total_rows"
    echo -e "  Archivo:    $output_file"
    echo ""
}

# =============================================================================
# COMANDO: RESTORE — Restaurar datos de un tenant
# =============================================================================

cmd_restore() {
    local tenant_id="$1"
    local backup_file="$2"
    local force="${3:-false}"

    # Validar archivo
    if [ ! -f "$backup_file" ]; then
        log_error "Archivo de backup no encontrado: $backup_file"
        exit 1
    fi

    # Verificar que el backup corresponde al tenant
    local file_content
    if [[ "$backup_file" == *.gz ]]; then
        file_content=$(gunzip -c "$backup_file" | head -20)
    else
        file_content=$(head -20 "$backup_file")
    fi

    local backup_tenant_id
    backup_tenant_id=$(echo "$file_content" | grep "Tenant ID:" | awk '{print $NF}')
    if [ -n "$backup_tenant_id" ] && [ "$backup_tenant_id" != "$tenant_id" ]; then
        log_error "El backup es del tenant #$backup_tenant_id, pero se intenta restaurar en #$tenant_id"
        if [ "$force" != "true" ]; then
            log_error "Use --force para forzar la restauracion."
            exit 1
        fi
        log_warn "Forzando restauracion (--force)."
    fi

    log_info "Restaurando tenant #$tenant_id desde $backup_file"

    # Paso 1: Safety backup del estado actual
    log_step "1/5" "Creando safety backup del estado actual"
    local safety_file="$BACKUP_DIR/tenant_${tenant_id}_pre_restore_$(timestamp).sql.gz"
    mkdir -p "$BACKUP_DIR"
    cmd_backup "$tenant_id" "$safety_file" 2>/dev/null || log_warn "No se pudo crear safety backup (tenant podria no existir)."
    log_success "Safety backup: $safety_file"

    # Paso 2: Activar modo mantenimiento
    log_step "2/5" "Activando modo mantenimiento"
    run_drush state:set system.maintenance_mode 1 --input-format=integer || true
    run_drush cr || true
    log_success "Modo mantenimiento activado"

    # Paso 3: Borrar datos actuales del tenant
    log_step "3/5" "Limpiando datos actuales del tenant #$tenant_id"
    local tenant_tables
    tenant_tables=$(discover_tenant_tables)

    for table in $tenant_tables; do
        local deleted
        deleted=$(run_mysql "DELETE FROM \`$table\` WHERE tenant_id = $tenant_id; SELECT ROW_COUNT();" || echo "0")
        if [ "$deleted" -gt 0 ] 2>/dev/null; then
            log_info "  $table: $deleted filas eliminadas"
        fi
    done

    # Paso 4: Importar backup
    log_step "4/5" "Importando datos desde backup"
    if [[ "$backup_file" == *.gz ]]; then
        if [ "$ENVIRONMENT" = "lando" ]; then
            gunzip -c "$backup_file" | lando mysql 2>/dev/null
        else
            gunzip -c "$backup_file" | mysql -h "$DB_HOST" -P "$DB_PORT" \
                -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null
        fi
    else
        if [ "$ENVIRONMENT" = "lando" ]; then
            lando mysql < "$backup_file" 2>/dev/null
        else
            mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
                "$DB_NAME" < "$backup_file" 2>/dev/null
        fi
    fi
    log_success "Datos importados"

    # Paso 5: Reconstruir caches y desactivar mantenimiento
    log_step "5/5" "Finalizando restauracion"
    run_drush cr || true
    run_drush state:set system.maintenance_mode 0 --input-format=integer || true
    run_drush cr || true
    log_success "Modo mantenimiento desactivado"

    # Verificacion rapida
    local row_count
    row_count=0
    for table in $tenant_tables; do
        local count
        count=$(run_mysql "SELECT COUNT(*) FROM \`$table\` WHERE tenant_id = $tenant_id;" || echo "0")
        row_count=$((row_count + count))
    done

    echo ""
    echo -e "${GREEN}${BOLD}Restauracion completada${NC}"
    echo -e "  Tenant:        #$tenant_id"
    echo -e "  Filas totales: $row_count"
    echo -e "  Safety backup: $safety_file"
    echo ""
}

# =============================================================================
# AYUDA
# =============================================================================

show_help() {
    echo ""
    echo -e "${BOLD}restore_tenant.sh${NC} — Per-Tenant Backup/Restore (F10 Doc 187)"
    echo ""
    echo -e "${BOLD}Uso:${NC}"
    echo "  $0 backup  <tenant_id> [--output=FILE]"
    echo "  $0 restore <tenant_id> <backup_file> [--force]"
    echo "  $0 list"
    echo "  $0 tables"
    echo ""
    echo -e "${BOLD}Comandos:${NC}"
    echo "  backup   Exporta todos los datos de un tenant a archivo SQL"
    echo "  restore  Restaura datos de un tenant desde backup"
    echo "  list     Lista todos los tenants activos"
    echo "  tables   Muestra tablas con columna tenant_id"
    echo ""
    echo -e "${BOLD}Variables de entorno:${NC}"
    echo "  ENVIRONMENT  lando (default) o ionos"
    echo "  BACKUP_DIR   Directorio de backups (default: /tmp/tenant_backups)"
    echo "  DB_HOST      Host de BD (para ionos)"
    echo "  DB_NAME      Nombre de BD"
    echo "  DB_USER      Usuario de BD"
    echo "  DB_PASS      Password de BD"
    echo ""
    echo -e "${BOLD}Ejemplos:${NC}"
    echo "  $0 list"
    echo "  $0 backup 5"
    echo "  $0 backup 5 --output=/backups/tenant_5.sql.gz"
    echo "  $0 restore 5 /tmp/tenant_backups/tenant_5_20260212.sql.gz"
    echo "  ENVIRONMENT=ionos $0 backup 12"
    echo ""
}

# =============================================================================
# MAIN
# =============================================================================

main() {
    local command="${1:-help}"
    shift || true

    case "$command" in
        backup)
            local tenant_id="${1:-}"
            local output_file=""
            shift || true

            if [ -z "$tenant_id" ]; then
                log_error "Se requiere tenant_id. Uso: $0 backup <tenant_id>"
                exit 1
            fi

            # Parse optional args
            for arg in "$@"; do
                case "$arg" in
                    --output=*)
                        output_file="${arg#--output=}"
                        ;;
                esac
            done

            cmd_backup "$tenant_id" "$output_file"
            ;;
        restore)
            local tenant_id="${1:-}"
            local backup_file="${2:-}"
            local force="false"
            shift 2 || true

            if [ -z "$tenant_id" ] || [ -z "$backup_file" ]; then
                log_error "Se requiere tenant_id y backup_file. Uso: $0 restore <tenant_id> <backup_file>"
                exit 1
            fi

            for arg in "$@"; do
                case "$arg" in
                    --force) force="true" ;;
                esac
            done

            cmd_restore "$tenant_id" "$backup_file" "$force"
            ;;
        list)
            cmd_list
            ;;
        tables)
            cmd_tables
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            log_error "Comando desconocido: $command"
            show_help
            exit 1
            ;;
    esac
}

main "$@"
