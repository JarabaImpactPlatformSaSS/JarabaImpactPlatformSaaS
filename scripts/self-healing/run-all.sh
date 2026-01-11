#!/bin/bash
# =============================================================================
# JARABA SELF-HEALING: Master Health Check
# =============================================================================
# Script principal que ejecuta todos los health checks
# Diseñado para ejecutarse via cron cada minuto
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/config.sh"

# =============================================================================
# Main
# =============================================================================

log "INFO" "=========================================="
log "INFO" "  JARABA SELF-HEALING - FULL CHECK"
log "INFO" "=========================================="

# Contador de errores
errors=0

# 1. Database Health (CRÍTICO - prioritario)
log "INFO" "[1/3] Checking Database..."
"${SCRIPT_DIR}/db-health.sh"
if [ $? -ne 0 ]; then
    errors=$((errors + 1))
fi

# 2. Qdrant Health (ALTO - para RAG)
log "INFO" "[2/3] Checking Qdrant..."
"${SCRIPT_DIR}/qdrant-health.sh"
if [ $? -ne 0 ]; then
    errors=$((errors + 1))
fi

# 3. Cache Health (MEDIO - solo cada 5 ejecuciones)
CACHE_CHECK_INTERVAL=5
CACHE_CHECK_FILE="/tmp/jaraba-cache-check-counter"

if [ -f "$CACHE_CHECK_FILE" ]; then
    counter=$(cat "$CACHE_CHECK_FILE")
else
    counter=0
fi

counter=$((counter + 1))
echo $counter > "$CACHE_CHECK_FILE"

if [ $((counter % CACHE_CHECK_INTERVAL)) -eq 0 ]; then
    log "INFO" "[3/3] Checking Cache (periodic)..."
    "${SCRIPT_DIR}/cache-recovery.sh"
    if [ $? -ne 0 ]; then
        errors=$((errors + 1))
    fi
else
    log "INFO" "[3/3] Skipping Cache check (interval: $counter/$CACHE_CHECK_INTERVAL)"
fi

# Resumen
log "INFO" "=========================================="
if [ $errors -eq 0 ]; then
    log "INFO" "  ALL CHECKS PASSED"
else
    log "WARN" "  CHECKS COMPLETED WITH $errors ERROR(S)"
fi
log "INFO" "=========================================="

exit $errors
