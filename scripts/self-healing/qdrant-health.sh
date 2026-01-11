#!/bin/bash
# =============================================================================
# JARABA SELF-HEALING: Qdrant Health Check & Recovery
# =============================================================================
# Basado en hallazgos del Game Day #1 (2026-01-11)
# 
# Función: Verificar conexión a Qdrant y auto-recuperar si falla
# Trigger: Cron cada 1 minuto
# MTTR Objetivo: < 30 segundos
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/config.sh"

# =============================================================================
# Función principal
# =============================================================================

check_qdrant() {
    log "INFO" "Checking Qdrant health..."
    
    # Verificar si el contenedor está corriendo
    local status=$(check_container_status "$QDRANT_CONTAINER")
    
    if [ "$status" != "running" ]; then
        log "ERROR" "Qdrant container is not running (status: $status)"
        return 1
    fi
    
    # Verificar si está pausado
    local paused=$(check_container_paused "$QDRANT_CONTAINER")
    if [ "$paused" == "true" ]; then
        log "ERROR" "Qdrant container is PAUSED"
        return 2
    fi
    
    # Verificar conexión HTTP
    local response=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout "$QDRANT_TIMEOUT" \
        --max-time "$QDRANT_TIMEOUT" \
        "${QDRANT_HOST}/")
    
    if [ "$response" != "200" ]; then
        log "ERROR" "Qdrant HTTP check failed (status: $response)"
        return 3
    fi
    
    log "INFO" "Qdrant is healthy"
    return 0
}

recover_qdrant() {
    local error_code="$1"
    local attempt=1
    local recovered=false
    
    log "WARN" "Starting Qdrant recovery (error code: $error_code)..."
    
    while [ $attempt -le $MAX_RETRIES ]; do
        log "INFO" "Recovery attempt $attempt of $MAX_RETRIES"
        
        case $error_code in
            1)
                # Contenedor no corriendo - intentar start
                log "INFO" "Starting Qdrant container..."
                docker start "$QDRANT_CONTAINER" 2>&1 | tee -a "$LOG_FILE"
                ;;
            2)
                # Contenedor pausado - unpause
                log "INFO" "Unpausing Qdrant container..."
                docker unpause "$QDRANT_CONTAINER" 2>&1 | tee -a "$LOG_FILE"
                ;;
            3)
                # HTTP falla - reiniciar contenedor
                log "INFO" "Restarting Qdrant container..."
                docker restart "$QDRANT_CONTAINER" 2>&1 | tee -a "$LOG_FILE"
                ;;
        esac
        
        # Esperar y verificar
        sleep $RETRY_DELAY
        
        if check_qdrant; then
            recovered=true
            break
        fi
        
        attempt=$((attempt + 1))
        sleep $((RETRY_DELAY * attempt))  # Backoff exponencial
    done
    
    if [ "$recovered" = true ]; then
        log "INFO" "Qdrant recovered successfully after $attempt attempt(s)"
        notify_email "Qdrant Auto-Recovery SUCCESS" \
            "Qdrant se recuperó automáticamente.\n\nDetalles:\n- Error original: $error_code\n- Intentos: $attempt\n- Timestamp: $(date)\n- Servidor: $(hostname)"
        return 0
    else
        log "ERROR" "Qdrant recovery FAILED after $MAX_RETRIES attempts"
        notify_email "Qdrant Auto-Recovery FAILED" \
            "¡ATENCIÓN! Qdrant NO se pudo recuperar automáticamente.\n\nDetalles:\n- Error: $error_code\n- Intentos: $MAX_RETRIES\n- Timestamp: $(date)\n- Servidor: $(hostname)\n\nSe requiere intervención manual."
        return 1
    fi
}

# =============================================================================
# Main
# =============================================================================

main() {
    log "INFO" "=== Qdrant Health Check Started ==="
    
    if check_qdrant; then
        log "INFO" "=== Qdrant Health Check Completed (OK) ==="
        exit 0
    else
        error_code=$?
        recover_qdrant $error_code
        exit_code=$?
        log "INFO" "=== Qdrant Health Check Completed (Recovery: $exit_code) ==="
        exit $exit_code
    fi
}

# Solo ejecutar si no es sourced
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
