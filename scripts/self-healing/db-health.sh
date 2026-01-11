#!/bin/bash
# =============================================================================
# JARABA SELF-HEALING: Database Health Check & Recovery
# =============================================================================
# Basado en hallazgos del Game Day #1 (2026-01-11)
# 
# Función: Detectar base de datos pausada/caída y auto-recuperar
# Trigger: Cron cada 1 minuto
# MTTR Objetivo: < 10 segundos
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/config.sh"

# =============================================================================
# Función principal
# =============================================================================

check_database() {
    log "INFO" "Checking Database health..."
    
    # Verificar si el contenedor está corriendo
    local status=$(check_container_status "$DATABASE_CONTAINER")
    
    if [ "$status" != "running" ]; then
        log "ERROR" "Database container is not running (status: $status)"
        return 1
    fi
    
    # CRÍTICO: Verificar si está pausado (hallazgo Game Day #1)
    local paused=$(check_container_paused "$DATABASE_CONTAINER")
    if [ "$paused" == "true" ]; then
        log "ERROR" "Database container is PAUSED - This causes site timeout!"
        return 2
    fi
    
    # Verificar conexión MySQL
    local mysql_check=$(docker exec "$DATABASE_CONTAINER" \
        mysqladmin ping -u root --silent 2>&1)
    
    if [[ "$mysql_check" != *"alive"* ]]; then
        log "ERROR" "Database is not responding to ping"
        return 3
    fi
    
    log "INFO" "Database is healthy"
    return 0
}

recover_database() {
    local error_code="$1"
    local attempt=1
    local recovered=false
    
    log "WARN" "Starting Database recovery (error code: $error_code)..."
    
    while [ $attempt -le $MAX_RETRIES ]; do
        log "INFO" "Recovery attempt $attempt of $MAX_RETRIES"
        
        case $error_code in
            1)
                # Contenedor no corriendo - intentar start
                log "INFO" "Starting Database container..."
                docker start "$DATABASE_CONTAINER" 2>&1 | tee -a "$LOG_FILE"
                ;;
            2)
                # Contenedor pausado - unpause (HALLAZGO GAME DAY #1)
                log "INFO" "Unpausing Database container (Game Day #1 runbook)..."
                docker unpause "$DATABASE_CONTAINER" 2>&1 | tee -a "$LOG_FILE"
                ;;
            3)
                # MySQL no responde - reiniciar contenedor
                log "INFO" "Restarting Database container..."
                docker restart "$DATABASE_CONTAINER" 2>&1 | tee -a "$LOG_FILE"
                ;;
        esac
        
        # Esperar y verificar
        sleep $RETRY_DELAY
        
        if check_database; then
            recovered=true
            break
        fi
        
        attempt=$((attempt + 1))
        sleep $((RETRY_DELAY * attempt))  # Backoff exponencial
    done
    
    if [ "$recovered" = true ]; then
        log "INFO" "Database recovered successfully after $attempt attempt(s)"
        notify_email "Database Auto-Recovery SUCCESS" \
            "La base de datos se recuperó automáticamente.\n\nDetalles:\n- Error original: $error_code\n- Intentos: $attempt\n- Timestamp: $(date)\n- Servidor: $(hostname)\n\nNota: Este problema fue identificado en Game Day #1 (contenedor pausado causa timeout indefinido)."
        return 0
    else
        log "ERROR" "Database recovery FAILED after $MAX_RETRIES attempts"
        notify_email "Database Auto-Recovery FAILED - CRITICAL" \
            "¡CRÍTICO! La base de datos NO se pudo recuperar automáticamente.\n\nDetalles:\n- Error: $error_code\n- Intentos: $MAX_RETRIES\n- Timestamp: $(date)\n- Servidor: $(hostname)\n\n¡SE REQUIERE INTERVENCIÓN MANUAL INMEDIATA!"
        return 1
    fi
}

# =============================================================================
# Main
# =============================================================================

main() {
    log "INFO" "=== Database Health Check Started ==="
    
    if check_database; then
        log "INFO" "=== Database Health Check Completed (OK) ==="
        exit 0
    else
        error_code=$?
        recover_database $error_code
        exit_code=$?
        log "INFO" "=== Database Health Check Completed (Recovery: $exit_code) ==="
        exit $exit_code
    fi
}

# Solo ejecutar si no es sourced
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
