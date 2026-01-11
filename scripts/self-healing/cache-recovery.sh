#!/bin/bash
# =============================================================================
# JARABA SELF-HEALING: Cache Health Check & Recovery
# =============================================================================
# Basado en hallazgos del Game Day #1 (2026-01-11)
# 
# Función: Monitorear health de cache Drupal y reconstruir si necesario
# Trigger: Cron cada 5 minutos
# MTTR Objetivo: < 1 minuto
# =============================================================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/config.sh"

# Configuración específica de cache
CACHE_HIT_THRESHOLD=30  # Porcentaje mínimo de hit rate aceptable
SITE_URL="https://jaraba-saas.lndo.site"
RESPONSE_TIME_THRESHOLD=3000  # ms - umbral para considerar sitio lento

# =============================================================================
# Funciones
# =============================================================================

check_site_response() {
    log "INFO" "Checking site response time..."
    
    local start_time=$(date +%s%N)
    local response=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout 10 \
        --max-time 30 \
        "$SITE_URL")
    local end_time=$(date +%s%N)
    
    local response_time=$(( (end_time - start_time) / 1000000 ))  # Convert to ms
    
    log "INFO" "Site response: HTTP $response in ${response_time}ms"
    
    if [ "$response" != "200" ]; then
        log "ERROR" "Site returned HTTP $response"
        return 1
    fi
    
    if [ "$response_time" -gt "$RESPONSE_TIME_THRESHOLD" ]; then
        log "WARN" "Site is slow: ${response_time}ms > ${RESPONSE_TIME_THRESHOLD}ms threshold"
        return 2
    fi
    
    return 0
}

check_drupal_bootstrap() {
    log "INFO" "Checking Drupal bootstrap..."
    
    local bootstrap=$(docker exec "$APPSERVER_CONTAINER" \
        drush status --field=bootstrap 2>/dev/null)
    
    if [ "$bootstrap" != "Successful" ]; then
        log "ERROR" "Drupal bootstrap failed: $bootstrap"
        return 1
    fi
    
    log "INFO" "Drupal bootstrap: OK"
    return 0
}

rebuild_cache() {
    log "WARN" "Rebuilding Drupal cache..."
    
    local start_time=$(date +%s)
    
    # Ejecutar cache rebuild
    docker exec "$APPSERVER_CONTAINER" drush cr 2>&1 | tee -a "$LOG_FILE"
    local exit_code=$?
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    if [ $exit_code -eq 0 ]; then
        log "INFO" "Cache rebuild completed in ${duration}s"
        return 0
    else
        log "ERROR" "Cache rebuild failed (exit code: $exit_code)"
        return 1
    fi
}

warm_critical_pages() {
    log "INFO" "Warming critical pages..."
    
    local pages=(
        "/"
        "/es"
        "/user/login"
        "/admin"
    )
    
    for page in "${pages[@]}"; do
        curl -s -o /dev/null "$SITE_URL$page" &
    done
    
    wait
    log "INFO" "Critical pages warmed"
}

# =============================================================================
# Main
# =============================================================================

main() {
    log "INFO" "=== Cache Health Check Started ==="
    
    # Verificar bootstrap de Drupal primero
    if ! check_drupal_bootstrap; then
        log "ERROR" "Drupal not bootstrapped, cannot check cache"
        notify_email "Cache Check FAILED - Drupal Not Running" \
            "No se pudo verificar el cache porque Drupal no está corriendo.\n\nTimestamp: $(date)\nServidor: $(hostname)"
        exit 1
    fi
    
    # Verificar respuesta del sitio
    check_site_response
    site_status=$?
    
    if [ $site_status -eq 0 ]; then
        log "INFO" "=== Cache Health Check Completed (OK) ==="
        exit 0
    fi
    
    # Sitio lento o con error - reconstruir cache
    log "WARN" "Site performance issue detected (status: $site_status)"
    
    if rebuild_cache; then
        # Calentar páginas críticas
        warm_critical_pages
        
        # Verificar mejora
        sleep 2
        if check_site_response; then
            notify_email "Cache Auto-Recovery SUCCESS" \
                "El cache se reconstruyó automáticamente y el sitio mejoró.\n\nDetalles:\n- Problema: Sitio lento/error\n- Acción: drush cr + warm pages\n- Timestamp: $(date)\n- Servidor: $(hostname)"
            log "INFO" "=== Cache Health Check Completed (Recovery OK) ==="
            exit 0
        fi
    fi
    
    # Recovery falló
    notify_email "Cache Recovery - Sitio sigue lento" \
        "Se reconstruyó el cache pero el sitio sigue con problemas.\n\nTimestamp: $(date)\nServidor: $(hostname)\n\nPuede requerir investigación adicional."
    log "WARN" "=== Cache Health Check Completed (Recovery partial) ==="
    exit 1
}

# Solo ejecutar si no es sourced
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi
