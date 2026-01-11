#!/bin/bash
# =============================================================================
# JARABA AIOPS - Production Monitoring (Linux/IONOS)
# =============================================================================
# Script para monitoreo y alertas en producción.
# Diseñado para ejecutarse via cron en el servidor IONOS.
#
# Instalación:
#   crontab -e
#   */5 * * * * /path/to/scripts/aiops-production.sh >> /var/log/jaraba-aiops.log 2>&1
#
# Horarios de bajo impacto: 02:00-06:00 CET
# =============================================================================

# Configuración
SITE_URL="https://plataformadeecosistemas.com"
DRUSH_CMD="/usr/bin/php8.4-cli vendor/bin/drush.php"
LOG_FILE="/var/log/jaraba-aiops.log"
NOTIFY_EMAIL="contacto@pepejaraba.es"
PROJECT_DIR="$HOME/JarabaImpactPlatformSaaS"

# Umbrales
RESPONSE_TIME_WARNING=2000  # ms
RESPONSE_TIME_CRITICAL=5000 # ms

# Horario de bajo impacto
LOW_IMPACT_START=2   # 02:00
LOW_IMPACT_END=6     # 06:00

# =============================================================================
# Funciones
# =============================================================================

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$1] $2"
}

is_low_impact_hours() {
    local hour=$(date +%H)
    [[ $hour -ge $LOW_IMPACT_START && $hour -lt $LOW_IMPACT_END ]]
}

check_site_health() {
    log "INFO" "Checking site health..."
    
    local start_time=$(date +%s%N)
    local response=$(curl -s -o /dev/null -w "%{http_code}" \
        --connect-timeout 10 \
        --max-time 30 \
        "$SITE_URL")
    local end_time=$(date +%s%N)
    
    local response_time=$(( (end_time - start_time) / 1000000 ))
    
    log "INFO" "Site response: HTTP $response in ${response_time}ms"
    
    # Check HTTP status
    if [ "$response" != "200" ]; then
        log "ERROR" "Site returned HTTP $response - CRITICAL"
        send_alert "CRITICAL" "Site Down" "Site returned HTTP $response"
        return 1
    fi
    
    # Check response time
    if [ "$response_time" -gt "$RESPONSE_TIME_CRITICAL" ]; then
        log "ERROR" "Response time ${response_time}ms exceeds critical threshold"
        
        if is_low_impact_hours; then
            log "ACTION" "Executing cache rebuild (low-impact hours)..."
            cd "$PROJECT_DIR" && $DRUSH_CMD cr
        else
            log "WARN" "Cache rebuild deferred (outside low-impact hours 02:00-06:00)"
        fi
        
        send_alert "CRITICAL" "Site Slow" "Response time: ${response_time}ms"
        return 1
    elif [ "$response_time" -gt "$RESPONSE_TIME_WARNING" ]; then
        log "WARN" "Response time ${response_time}ms exceeds warning threshold"
        return 0
    fi
    
    log "OK" "Site healthy (${response_time}ms)"
    return 0
}

check_disk_space() {
    local usage=$(df -h "$PROJECT_DIR" | awk 'NR==2 {print $5}' | tr -d '%')
    
    if [ "$usage" -gt 90 ]; then
        log "ERROR" "Disk usage at ${usage}% - CRITICAL"
        send_alert "CRITICAL" "Disk Full" "Disk usage: ${usage}%"
        return 1
    elif [ "$usage" -gt 80 ]; then
        log "WARN" "Disk usage at ${usage}%"
        return 0
    fi
    
    log "OK" "Disk usage: ${usage}%"
    return 0
}

check_drupal_status() {
    cd "$PROJECT_DIR"
    
    # Check if Drupal can bootstrap
    local status=$($DRUSH_CMD status --field=bootstrap 2>/dev/null)
    
    if [ "$status" != "Successful" ]; then
        log "ERROR" "Drupal bootstrap failed"
        send_alert "CRITICAL" "Drupal Error" "Bootstrap failed: $status"
        return 1
    fi
    
    log "OK" "Drupal bootstrap: OK"
    return 0
}

send_alert() {
    local severity="$1"
    local subject="$2"
    local body="$3"
    
    log "ALERT" "Sending alert: [$severity] $subject"
    
    # Enviar email
    echo -e "Jaraba AIOps Alert\n\nSeverity: $severity\nSubject: $subject\n\nDetails:\n$body\n\nTimestamp: $(date)\nServer: $(hostname)" | \
        mail -s "[Jaraba AIOps] [$severity] $subject" "$NOTIFY_EMAIL" 2>/dev/null || \
        log "WARN" "Could not send email notification"
}

# =============================================================================
# Main
# =============================================================================

log "INFO" "=========================================="
log "INFO" "  JARABA AIOPS - PRODUCTION CHECK"
log "INFO" "=========================================="

errors=0

# 1. Site Health
check_site_health || errors=$((errors + 1))

# 2. Disk Space
check_disk_space || errors=$((errors + 1))

# 3. Drupal Status (solo cada 5 ejecuciones = cada 25 min)
CHECK_FILE="/tmp/jaraba-drupal-check-counter"
counter=0
[ -f "$CHECK_FILE" ] && counter=$(cat "$CHECK_FILE")
counter=$((counter + 1))
echo $counter > "$CHECK_FILE"

if [ $((counter % 5)) -eq 0 ]; then
    check_drupal_status || errors=$((errors + 1))
else
    log "INFO" "Drupal check skipped (interval: $counter/5)"
fi

# Summary
log "INFO" "=========================================="
if [ $errors -eq 0 ]; then
    log "OK" "  ALL CHECKS PASSED"
else
    log "WARN" "  $errors CHECK(S) FAILED"
fi
log "INFO" "=========================================="

exit $errors
