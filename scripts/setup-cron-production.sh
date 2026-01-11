#!/bin/bash
# =============================================================================
# JARABA - Setup Cron Jobs (Production/IONOS)
# =============================================================================
# Instala los cron jobs necesarios para AIOps en producción.
#
# Uso:
#   ./scripts/setup-cron-production.sh install   # Instalar tareas
#   ./scripts/setup-cron-production.sh uninstall # Desinstalar tareas
#   ./scripts/setup-cron-production.sh status    # Ver tareas actuales
# =============================================================================

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
AIOPS_SCRIPT="$PROJECT_DIR/scripts/aiops-production.sh"
LOG_FILE="/var/log/jaraba-aiops.log"

# Marcador para identificar nuestras tareas
CRON_MARKER="# JARABA-AIOPS"

show_usage() {
    echo ""
    echo "Jaraba AIOps - Cron Setup for Production"
    echo ""
    echo "Usage: $0 [install|uninstall|status]"
    echo ""
    echo "Commands:"
    echo "  install    - Install AIOps cron jobs"
    echo "  uninstall  - Remove AIOps cron jobs"
    echo "  status     - Show current Jaraba cron jobs"
    echo ""
}

install_cron() {
    echo ""
    echo "=============================================="
    echo "  Installing Jaraba AIOps Cron Jobs"
    echo "=============================================="
    echo ""
    
    # Verificar que el script existe
    if [ ! -f "$AIOPS_SCRIPT" ]; then
        echo "[ERROR] AIOps script not found: $AIOPS_SCRIPT"
        exit 1
    fi
    
    # Hacer el script ejecutable
    chmod +x "$AIOPS_SCRIPT"
    
    # Crear directorio de logs si no existe
    sudo mkdir -p $(dirname "$LOG_FILE") 2>/dev/null || true
    sudo touch "$LOG_FILE" 2>/dev/null || true
    sudo chmod 666 "$LOG_FILE" 2>/dev/null || true
    
    # Backup del crontab actual
    crontab -l > /tmp/jaraba_crontab_backup 2>/dev/null || echo ""
    
    # Remover entradas existentes de Jaraba
    crontab -l 2>/dev/null | grep -v "$CRON_MARKER" > /tmp/jaraba_crontab_new || true
    
    # Añadir nuevas entradas
    cat >> /tmp/jaraba_crontab_new << EOF

$CRON_MARKER - AIOps Monitoring (every 5 min)
*/5 * * * * $AIOPS_SCRIPT >> $LOG_FILE 2>&1 $CRON_MARKER
EOF
    
    # Instalar nuevo crontab
    crontab /tmp/jaraba_crontab_new
    
    echo "[OK] Cron jobs installed:"
    echo ""
    echo "  - AIOps Monitoring: every 5 minutes"
    echo "  - Log file: $LOG_FILE"
    echo ""
    echo "=============================================="
    echo ""
}

uninstall_cron() {
    echo ""
    echo "=============================================="
    echo "  Removing Jaraba AIOps Cron Jobs"
    echo "=============================================="
    echo ""
    
    # Remover entradas de Jaraba
    crontab -l 2>/dev/null | grep -v "$CRON_MARKER" > /tmp/jaraba_crontab_new || true
    crontab /tmp/jaraba_crontab_new
    
    echo "[OK] Jaraba cron jobs removed"
    echo ""
}

show_status() {
    echo ""
    echo "=============================================="
    echo "  Jaraba AIOps Cron Jobs Status"
    echo "=============================================="
    echo ""
    
    local jobs=$(crontab -l 2>/dev/null | grep "$CRON_MARKER" | grep -v "^#")
    
    if [ -z "$jobs" ]; then
        echo "[INFO] No Jaraba cron jobs installed"
    else
        echo "[OK] Active Jaraba cron jobs:"
        echo ""
        echo "$jobs" | while read line; do
            echo "  $line"
        done
    fi
    
    echo ""
    echo "=============================================="
    echo ""
}

# Main
case "$1" in
    install)
        install_cron
        ;;
    uninstall)
        uninstall_cron
        ;;
    status)
        show_status
        ;;
    *)
        show_usage
        exit 1
        ;;
esac
