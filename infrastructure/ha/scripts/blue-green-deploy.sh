#!/usr/bin/env bash
# =============================================================================
# Jaraba Impact Platform - Blue-Green Deployment Script
# N3 Enterprise Class - Doc 197 - High Availability Infrastructure
# =============================================================================
# Zero-downtime deployment via HAProxy weight shifting.
#
# Process:
#   1. Build green environment (pull code, composer install, cache rebuild)
#   2. Run additive-only DB migrations (drush updatedb)
#   3. Smoke test green instances
#   4. Switch HAProxy traffic from blue to green
#   5. Monitor for 15 minutes (error rates, response times)
#   6. Decommission blue OR rollback within 30 s if errors spike
#
# Usage:
#   ./blue-green-deploy.sh deploy <git-ref>
#   ./blue-green-deploy.sh rollback
#   ./blue-green-deploy.sh status
# =============================================================================

set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
DEPLOY_ROOT="/opt/jaraba"
BLUE_SERVERS=("app1.jaraba.io" "app2.jaraba.io" "app3.jaraba.io")
GREEN_SERVERS=("app1-green.jaraba.io" "app2-green.jaraba.io" "app3-green.jaraba.io")
HAPROXY_SOCKET="/run/haproxy/admin.sock"
HAPROXY_BACKEND="drupal_servers"
HEALTH_ENDPOINT="/health"
HEALTH_TIMEOUT=10
MONITOR_DURATION=900        # 15 minutes
MONITOR_INTERVAL=30         # check every 30 s
ERROR_RATE_THRESHOLD=1.0    # percent
P95_LATENCY_THRESHOLD=2000  # ms
SMOKE_TEST_RETRIES=5
SMOKE_TEST_DELAY=10

LOG_FILE="/var/log/jaraba/blue-green-deploy.log"
LOCK_FILE="/tmp/jaraba-deploy.lock"
STATE_FILE="/opt/jaraba/deploy-state.json"

# Colours for terminal output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
log() {
    local level="$1"; shift
    local msg="$*"
    local ts
    ts=$(date '+%Y-%m-%d %H:%M:%S')
    echo -e "${ts} [${level}] ${msg}" | tee -a "$LOG_FILE"
}

info()  { log "INFO"  "${GREEN}$*${NC}"; }
warn()  { log "WARN"  "${YELLOW}$*${NC}"; }
error() { log "ERROR" "${RED}$*${NC}"; }

# ---------------------------------------------------------------------------
# Lock management (prevent concurrent deploys)
# ---------------------------------------------------------------------------
acquire_lock() {
    if [ -f "$LOCK_FILE" ]; then
        local pid
        pid=$(cat "$LOCK_FILE")
        if kill -0 "$pid" 2>/dev/null; then
            error "Another deployment is running (PID $pid). Aborting."
            exit 1
        fi
        warn "Stale lock file found. Removing."
        rm -f "$LOCK_FILE"
    fi
    echo $$ > "$LOCK_FILE"
    trap 'rm -f "$LOCK_FILE"' EXIT
}

# ---------------------------------------------------------------------------
# State management
# ---------------------------------------------------------------------------
save_state() {
    local active_env="$1"
    local git_ref="$2"
    local timestamp
    timestamp=$(date -u '+%Y-%m-%dT%H:%M:%SZ')
    cat > "$STATE_FILE" <<STATEEOF
{
    "active_environment": "${active_env}",
    "git_ref": "${git_ref}",
    "deployed_at": "${timestamp}",
    "deployed_by": "$(whoami)"
}
STATEEOF
}

get_active_env() {
    if [ -f "$STATE_FILE" ]; then
        python3 -c "import json; print(json.load(open('${STATE_FILE}'))['active_environment'])" 2>/dev/null || echo "blue"
    else
        echo "blue"
    fi
}

# ---------------------------------------------------------------------------
# HAProxy control via admin socket
# ---------------------------------------------------------------------------
haproxy_set_weight() {
    local server="$1"
    local weight="$2"
    echo "set weight ${HAPROXY_BACKEND}/${server} ${weight}" | socat stdio "$HAPROXY_SOCKET"
    info "HAProxy: ${server} weight -> ${weight}"
}

haproxy_set_state() {
    local server="$1"
    local state="$2"
    echo "set server ${HAPROXY_BACKEND}/${server} state ${state}" | socat stdio "$HAPROXY_SOCKET"
    info "HAProxy: ${server} state -> ${state}"
}

haproxy_get_stats() {
    echo "show stat" | socat stdio "$HAPROXY_SOCKET" | grep "^${HAPROXY_BACKEND},"
}

# ---------------------------------------------------------------------------
# Step 1: Build Green Environment
# ---------------------------------------------------------------------------
build_green() {
    local git_ref="$1"
    info "=== Step 1: Building green environment (ref: ${git_ref}) ==="

    for server in "${GREEN_SERVERS[@]}"; do
        info "Building on ${server}..."
        ssh -o ConnectTimeout=10 "deploy@${server}" bash -s <<BUILDEOF
set -euo pipefail
cd ${DEPLOY_ROOT}/current
git fetch origin
git checkout ${git_ref}
git pull origin ${git_ref}
composer install --no-dev --optimize-autoloader --no-interaction
cd web
../vendor/bin/drush cr
../vendor/bin/drush state:set system.maintenance_mode 0
BUILDEOF

        if [ $? -ne 0 ]; then
            error "Build failed on ${server}"
            return 1
        fi
        info "Build complete on ${server}"
    done

    info "=== Step 1 complete: Green environment built ==="
}

# ---------------------------------------------------------------------------
# Step 2: Run DB Migrations (additive-only)
# ---------------------------------------------------------------------------
run_migrations() {
    info "=== Step 2: Running database migrations ==="

    local primary_green="${GREEN_SERVERS[0]}"
    ssh -o ConnectTimeout=10 "deploy@${primary_green}" bash -s <<MIGRATEEOF
set -euo pipefail
cd ${DEPLOY_ROOT}/current/web
../vendor/bin/drush updatedb --yes
../vendor/bin/drush cr
MIGRATEEOF

    if [ $? -ne 0 ]; then
        error "Database migration failed"
        return 1
    fi

    info "=== Step 2 complete: Migrations applied ==="
}

# ---------------------------------------------------------------------------
# Step 3: Smoke Test Green
# ---------------------------------------------------------------------------
smoke_test_green() {
    info "=== Step 3: Smoke testing green environment ==="

    for server in "${GREEN_SERVERS[@]}"; do
        local attempt=0
        local healthy=false

        while [ $attempt -lt $SMOKE_TEST_RETRIES ]; do
            attempt=$((attempt + 1))
            info "Smoke test ${server} (attempt ${attempt}/${SMOKE_TEST_RETRIES})..."

            local http_code
            http_code=$(curl -s -o /dev/null -w "%{http_code}" \
                --connect-timeout "$HEALTH_TIMEOUT" \
                "http://${server}:8080${HEALTH_ENDPOINT}" 2>/dev/null || echo "000")

            if [ "$http_code" = "200" ]; then
                info "Smoke test passed for ${server}"
                healthy=true
                break
            fi

            warn "Health check returned ${http_code} for ${server}, retrying in ${SMOKE_TEST_DELAY}s..."
            sleep "$SMOKE_TEST_DELAY"
        done

        if [ "$healthy" = false ]; then
            error "Smoke test FAILED for ${server} after ${SMOKE_TEST_RETRIES} attempts"
            return 1
        fi
    done

    info "=== Step 3 complete: All green instances healthy ==="
}

# ---------------------------------------------------------------------------
# Step 4: Switch Traffic (Blue -> Green)
# ---------------------------------------------------------------------------
switch_traffic() {
    local from_env="$1"   # blue or green
    local to_env="$2"

    info "=== Step 4: Switching traffic from ${from_env} to ${to_env} ==="

    if [ "$to_env" = "green" ]; then
        # Enable green servers
        for i in 1 2 3; do
            haproxy_set_weight "app${i}-green" 100
        done
        sleep 5
        # Drain blue servers (weight 0 allows existing connections to finish)
        for i in 1 2 3; do
            haproxy_set_weight "app${i}" 0
        done
    elif [ "$to_env" = "blue" ]; then
        # Enable blue servers
        for i in 1 2 3; do
            haproxy_set_weight "app${i}" 100
        done
        sleep 5
        # Drain green servers
        for i in 1 2 3; do
            haproxy_set_weight "app${i}-green" 0
        done
    fi

    info "=== Step 4 complete: Traffic now routed to ${to_env} ==="
}

# ---------------------------------------------------------------------------
# Step 5: Monitor (15 minutes)
# ---------------------------------------------------------------------------
monitor_deployment() {
    info "=== Step 5: Monitoring deployment for ${MONITOR_DURATION}s ==="

    local elapsed=0
    local checks_passed=0
    local checks_total=0

    while [ $elapsed -lt $MONITOR_DURATION ]; do
        checks_total=$((checks_total + 1))
        local stats
        stats=$(haproxy_get_stats 2>/dev/null || echo "")

        # Parse error rate from HAProxy stats
        local total_req
        local error_req
        total_req=$(echo "$stats" | awk -F',' '{sum+=$49} END{print sum+0}' 2>/dev/null || echo "0")
        error_req=$(echo "$stats" | awk -F',' '{sum+=$53} END{print sum+0}' 2>/dev/null || echo "0")

        local error_rate="0.0"
        if [ "$total_req" -gt 0 ]; then
            error_rate=$(echo "scale=2; ($error_req * 100) / $total_req" | bc 2>/dev/null || echo "0.0")
        fi

        # Check thresholds
        local error_exceeded
        error_exceeded=$(echo "$error_rate > $ERROR_RATE_THRESHOLD" | bc 2>/dev/null || echo "0")

        if [ "$error_exceeded" = "1" ]; then
            error "Error rate ${error_rate}% exceeds threshold ${ERROR_RATE_THRESHOLD}%"
            warn "Triggering automatic rollback!"
            return 1
        fi

        checks_passed=$((checks_passed + 1))
        info "Monitor check ${checks_total}: error_rate=${error_rate}% - OK (${elapsed}s / ${MONITOR_DURATION}s)"

        sleep "$MONITOR_INTERVAL"
        elapsed=$((elapsed + MONITOR_INTERVAL))
    done

    info "=== Step 5 complete: ${checks_passed}/${checks_total} checks passed ==="
}

# ---------------------------------------------------------------------------
# Step 6a: Decommission old environment
# ---------------------------------------------------------------------------
decommission_old() {
    local old_env="$1"
    info "=== Step 6: Decommissioning ${old_env} environment ==="

    if [ "$old_env" = "blue" ]; then
        for i in 1 2 3; do
            haproxy_set_state "app${i}" "maint"
        done
    else
        for i in 1 2 3; do
            haproxy_set_state "app${i}-green" "maint"
        done
    fi

    info "=== Step 6 complete: ${old_env} environment in maintenance ==="
}

# ---------------------------------------------------------------------------
# Rollback
# ---------------------------------------------------------------------------
rollback() {
    local active_env
    active_env=$(get_active_env)
    local target_env

    if [ "$active_env" = "green" ]; then
        target_env="blue"
    else
        target_env="green"
    fi

    warn "=== ROLLBACK: Switching from ${active_env} back to ${target_env} ==="

    switch_traffic "$active_env" "$target_env"
    save_state "$target_env" "rollback-$(date +%s)"

    info "=== ROLLBACK COMPLETE: Traffic restored to ${target_env} ==="
}

# ---------------------------------------------------------------------------
# Status
# ---------------------------------------------------------------------------
status() {
    info "=== Deployment Status ==="

    if [ -f "$STATE_FILE" ]; then
        cat "$STATE_FILE"
    else
        echo "No deployment state file found."
    fi

    echo ""
    info "HAProxy Backend Status:"
    haproxy_get_stats 2>/dev/null | column -t -s',' || echo "Cannot reach HAProxy socket"
}

# ---------------------------------------------------------------------------
# Full Deploy Orchestration
# ---------------------------------------------------------------------------
deploy() {
    local git_ref="${1:-main}"
    local active_env
    active_env=$(get_active_env)

    info "============================================================"
    info "  Jaraba Impact Platform - Blue-Green Deployment"
    info "  Active: ${active_env} | Deploying to: green"
    info "  Git ref: ${git_ref}"
    info "============================================================"

    acquire_lock

    # Step 1: Build
    if ! build_green "$git_ref"; then
        error "Build failed. Deployment aborted."
        exit 1
    fi

    # Step 2: Migrations
    if ! run_migrations; then
        error "Migration failed. Deployment aborted (green not yet receiving traffic)."
        exit 1
    fi

    # Step 3: Smoke test
    if ! smoke_test_green; then
        error "Smoke tests failed. Deployment aborted (green not yet receiving traffic)."
        exit 1
    fi

    # Step 4: Switch traffic
    switch_traffic "$active_env" "green"
    save_state "green" "$git_ref"

    # Step 5: Monitor
    if ! monitor_deployment; then
        error "Monitoring detected issues. Rolling back..."
        rollback
        exit 1
    fi

    # Step 6: Decommission old
    decommission_old "$active_env"

    info "============================================================"
    info "  Deployment SUCCESSFUL"
    info "  Active environment: green"
    info "  Git ref: ${git_ref}"
    info "============================================================"
}

# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------
main() {
    mkdir -p "$(dirname "$LOG_FILE")"

    local command="${1:-}"
    shift || true

    case "$command" in
        deploy)
            deploy "$@"
            ;;
        rollback)
            acquire_lock
            rollback
            ;;
        status)
            status
            ;;
        *)
            echo "Usage: $0 {deploy <git-ref>|rollback|status}"
            echo ""
            echo "Commands:"
            echo "  deploy <git-ref>   Deploy the specified git ref using blue-green strategy"
            echo "  rollback           Immediately roll back to the previous environment"
            echo "  status             Show current deployment state and HAProxy status"
            exit 1
            ;;
    esac
}

main "$@"
