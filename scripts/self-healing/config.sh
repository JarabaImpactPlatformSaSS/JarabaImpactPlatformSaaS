#!/bin/bash
# =============================================================================
# JARABA SELF-HEALING: Configuración común
# =============================================================================
# Incluir en otros scripts: source ./config.sh
# =============================================================================

# Configuración de notificaciones
NOTIFY_EMAIL="contacto@pepejaraba.es"
NOTIFY_FROM="selfhealing@jaraba-saas.lndo.site"
NOTIFY_SUBJECT_PREFIX="[Jaraba Self-Healing]"

# Configuración de servicios
QDRANT_HOST="http://qdrant:6333"
QDRANT_CONTAINER="jarabasaas_qdrant_1"
DATABASE_CONTAINER="jarabasaas_database_1"
APPSERVER_CONTAINER="jarabasaas_appserver_1"

# Configuración de timeouts
QDRANT_TIMEOUT=3
DB_TIMEOUT=5

# Configuración de reintentos
MAX_RETRIES=3
RETRY_DELAY=2

# Directorio de logs
LOG_DIR="/var/log/jaraba-self-healing"
LOG_FILE="${LOG_DIR}/self-healing.log"

# =============================================================================
# Funciones comunes
# =============================================================================

log() {
    local level="$1"
    local message="$2"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [$level] $message" | tee -a "$LOG_FILE"
}

notify_email() {
    local subject="$1"
    local body="$2"
    
    # Usar mail o sendmail si está disponible
    if command -v mail &> /dev/null; then
        echo -e "$body" | mail -s "${NOTIFY_SUBJECT_PREFIX} ${subject}" -r "${NOTIFY_FROM}" "${NOTIFY_EMAIL}"
        log "INFO" "Email notification sent: ${subject}"
    elif command -v drush &> /dev/null; then
        # Fallback: usar Drupal para enviar email via drush
        drush php:eval "\\Drupal::service('plugin.manager.mail')->mail('system', 'mail', '${NOTIFY_EMAIL}', 'en', ['subject' => '${NOTIFY_SUBJECT_PREFIX} ${subject}', 'body' => '${body}']);"
        log "INFO" "Email notification sent via Drush: ${subject}"
    else
        log "WARN" "No mail system available, notification skipped"
    fi
}

check_container_status() {
    local container="$1"
    docker inspect -f '{{.State.Status}}' "$container" 2>/dev/null
}

check_container_paused() {
    local container="$1"
    docker inspect -f '{{.State.Paused}}' "$container" 2>/dev/null
}

# Crear directorio de logs si no existe
mkdir -p "$LOG_DIR" 2>/dev/null || true
