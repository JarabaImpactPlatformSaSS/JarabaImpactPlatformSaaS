#!/usr/bin/env bash
# =============================================================================
# Jaraba Impact Platform - Redis 7.4 -> 8.0 Production Upgrade Script
# =============================================================================
# Target: IONOS Dedicated (AMD EPYC 12c/24t, 128GB DDR5, Ubuntu 24.04)
# IP: 82.223.204.169 | SSH: 2222 | User: jaraba
#
# Prerequisites:
#   1. REDIS_ADMIN_PASSWORD and REDIS_MONITOR_PASSWORD added to env
#   2. users.acl deployed to /etc/redis/users.acl
#   3. Updated redis.conf deployed to /etc/redis/redis.conf
#   4. This script run as root or with sudo
#
# Usage:
#   sudo bash scripts/upgrade-redis-8.sh [--dry-run] [--rollback]
#
# Rollback:
#   sudo bash scripts/upgrade-redis-8.sh --rollback
# =============================================================================

set -euo pipefail

# ---------------------------------------------------------------------------
# Configuration
# ---------------------------------------------------------------------------
REDIS_DATA_DIR="/var/lib/redis"
REDIS_CONF_DIR="/etc/redis"
REDIS_LOG="/var/log/redis/redis-server.log"
BACKUP_DIR="/var/backups/redis-pre-upgrade-$(date +%Y%m%d-%H%M%S)"
REDIS_CLI="redis-cli"
DRY_RUN=false
ROLLBACK=false

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info()  { echo -e "${BLUE}[INFO]${NC}  $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC}    $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC}  $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_step()  { echo -e "\n${GREEN}=== STEP $1 ===${NC}"; }

# ---------------------------------------------------------------------------
# Parse Arguments
# ---------------------------------------------------------------------------
for arg in "$@"; do
  case $arg in
    --dry-run)  DRY_RUN=true ;;
    --rollback) ROLLBACK=true ;;
    *)          log_error "Unknown argument: $arg"; exit 1 ;;
  esac
done

if $DRY_RUN; then
  log_warn "DRY RUN MODE — no changes will be made"
fi

# ---------------------------------------------------------------------------
# Rollback
# ---------------------------------------------------------------------------
if $ROLLBACK; then
  log_step "ROLLBACK Redis 8 -> 7.4"

  # Find most recent backup
  LATEST_BACKUP=$(ls -dt /var/backups/redis-pre-upgrade-* 2>/dev/null | head -1)
  if [ -z "$LATEST_BACKUP" ]; then
    log_error "No backup found in /var/backups/redis-pre-upgrade-*"
    exit 1
  fi

  log_info "Using backup: $LATEST_BACKUP"

  if ! $DRY_RUN; then
    systemctl stop redis-server || true

    # Restore config
    cp "$LATEST_BACKUP/redis.conf" "$REDIS_CONF_DIR/redis.conf"
    [ -f "$LATEST_BACKUP/users.acl" ] && cp "$LATEST_BACKUP/users.acl" "$REDIS_CONF_DIR/users.acl"

    # Restore data (RDB + AOF)
    cp "$LATEST_BACKUP/dump.rdb" "$REDIS_DATA_DIR/dump.rdb" 2>/dev/null || true
    [ -f "$LATEST_BACKUP/appendonly.aof" ] && cp "$LATEST_BACKUP/appendonly.aof" "$REDIS_DATA_DIR/appendonly.aof"

    # Reinstall Redis 7.x from Ubuntu repos (removes Redis 8)
    apt-get remove -y redis-server redis-tools 2>/dev/null || true
    rm -f /etc/apt/sources.list.d/redis.list
    apt-get update
    apt-get install -y redis-server

    systemctl start redis-server
  fi

  # Verify
  CURRENT_VERSION=$($REDIS_CLI INFO server 2>/dev/null | grep redis_version | cut -d: -f2 | tr -d '\r')
  log_info "Current Redis version: $CURRENT_VERSION"
  log_ok "Rollback complete"
  exit 0
fi

# ---------------------------------------------------------------------------
# Pre-Flight Checks
# ---------------------------------------------------------------------------
log_step "1/8: Pre-Flight Checks"

# Must be root
if [ "$EUID" -ne 0 ]; then
  log_error "This script must be run as root (sudo)"
  exit 1
fi

# Check current version
CURRENT_VERSION=$($REDIS_CLI INFO server 2>/dev/null | grep redis_version | cut -d: -f2 | tr -d '\r')
log_info "Current Redis version: $CURRENT_VERSION"

if [[ "$CURRENT_VERSION" == 8.* ]]; then
  log_warn "Redis is already version $CURRENT_VERSION — nothing to upgrade"
  exit 0
fi

if [[ "$CURRENT_VERSION" != 7.* ]]; then
  log_error "Expected Redis 7.x, got $CURRENT_VERSION. Aborting."
  exit 1
fi

# Check Redis is responding
if ! $REDIS_CLI PING > /dev/null 2>&1; then
  log_error "Redis is not responding to PING"
  exit 1
fi
log_ok "Redis $CURRENT_VERSION is running and responsive"

# Check disk space (need at least 2GB free for backup + new install)
FREE_SPACE_MB=$(df -m "$REDIS_DATA_DIR" | tail -1 | awk '{print $4}')
if [ "$FREE_SPACE_MB" -lt 2048 ]; then
  log_error "Insufficient disk space: ${FREE_SPACE_MB}MB free (need 2048MB)"
  exit 1
fi
log_ok "Disk space OK: ${FREE_SPACE_MB}MB free"

# Check env vars for ACL (REDIS-ACL-001)
if [ -z "${REDIS_PASSWORD:-}" ]; then
  log_error "REDIS_PASSWORD environment variable not set"
  log_info "Add to /etc/environment or settings.secrets.php before proceeding"
  exit 1
fi
log_ok "REDIS_PASSWORD is set"

if [ -z "${REDIS_ADMIN_PASSWORD:-}" ]; then
  log_error "REDIS_ADMIN_PASSWORD environment variable not set"
  log_info "Add to /etc/environment or settings.secrets.php before proceeding"
  exit 1
fi
log_ok "REDIS_ADMIN_PASSWORD is set"

if [ -z "${REDIS_SENTINEL_PASSWORD:-}" ]; then
  log_warn "REDIS_SENTINEL_PASSWORD not set (needed for HA Sentinel failover)"
fi

# Check new config files exist
if [ ! -f "$REDIS_CONF_DIR/redis.conf.redis8" ] && ! $DRY_RUN; then
  log_warn "Deploy new redis.conf to $REDIS_CONF_DIR/redis.conf.redis8 before running"
  log_warn "Deploy new users.acl to $REDIS_CONF_DIR/users.acl before running"
  log_info "Files from: infrastructure/ha/redis/{redis.conf,users.acl}"
  log_info "Remember to substitute \${REDIS_PASSWORD} etc. in users.acl"
fi

# ---------------------------------------------------------------------------
# Backup
# ---------------------------------------------------------------------------
log_step "2/8: Backup Current State"

if ! $DRY_RUN; then
  mkdir -p "$BACKUP_DIR"

  # Force RDB save
  $REDIS_CLI SAVE
  log_ok "RDB snapshot saved"

  # Backup data
  cp -r "$REDIS_DATA_DIR/dump.rdb" "$BACKUP_DIR/" 2>/dev/null || true
  cp -r "$REDIS_DATA_DIR/appendonly.aof" "$BACKUP_DIR/" 2>/dev/null || true

  # Backup config
  cp "$REDIS_CONF_DIR/redis.conf" "$BACKUP_DIR/"
  [ -f "$REDIS_CONF_DIR/users.acl" ] && cp "$REDIS_CONF_DIR/users.acl" "$BACKUP_DIR/"

  # Backup Redis version info
  $REDIS_CLI INFO server > "$BACKUP_DIR/redis-info-pre-upgrade.txt"

  log_ok "Backup saved to $BACKUP_DIR"
else
  log_info "[DRY RUN] Would backup to $BACKUP_DIR"
fi

# ---------------------------------------------------------------------------
# Record Pre-Upgrade Metrics
# ---------------------------------------------------------------------------
log_step "3/8: Record Pre-Upgrade Metrics"

PRE_MEMORY=$($REDIS_CLI INFO memory | grep used_memory_human | cut -d: -f2 | tr -d '\r')
PRE_KEYS=$($REDIS_CLI DBSIZE | tr -d '\r')
PRE_UPTIME=$($REDIS_CLI INFO server | grep uptime_in_days | cut -d: -f2 | tr -d '\r')
PRE_CONNECTED=$($REDIS_CLI INFO clients | grep connected_clients | cut -d: -f2 | tr -d '\r')

log_info "Memory: $PRE_MEMORY"
log_info "Keys: $PRE_KEYS"
log_info "Uptime: ${PRE_UPTIME} days"
log_info "Connected clients: $PRE_CONNECTED"

# ---------------------------------------------------------------------------
# Stop Redis 7
# ---------------------------------------------------------------------------
log_step "4/8: Stop Redis 7.4"

if ! $DRY_RUN; then
  # Graceful shutdown waits for RDB/AOF to finish
  systemctl stop redis-server
  sleep 2

  # Verify stopped
  if pgrep -x redis-server > /dev/null; then
    log_error "Redis did not stop cleanly. Sending SIGKILL..."
    pkill -9 redis-server
    sleep 1
  fi
  log_ok "Redis 7.4 stopped"
else
  log_info "[DRY RUN] Would stop redis-server"
fi

# ---------------------------------------------------------------------------
# Install Redis 8
# ---------------------------------------------------------------------------
log_step "5/8: Install Redis 8.0"

if ! $DRY_RUN; then
  # Add official Redis repository
  if [ ! -f /usr/share/keyrings/redis-archive-keyring.gpg ]; then
    curl -fsSL https://packages.redis.io/gpg | gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg
    log_ok "Redis GPG key installed"
  fi

  echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" > /etc/apt/sources.list.d/redis.list

  apt-get update -qq
  apt-get install -y redis-server redis-tools
  log_ok "Redis 8.0 packages installed"

  # Verify binary version
  NEW_BIN_VERSION=$(redis-server --version | grep -oP 'v=\K[0-9.]+')
  log_info "Installed binary version: $NEW_BIN_VERSION"

  if [[ ! "$NEW_BIN_VERSION" == 8.* ]]; then
    log_error "Expected Redis 8.x binary, got $NEW_BIN_VERSION"
    log_warn "Run --rollback to restore Redis 7.4"
    exit 1
  fi
else
  log_info "[DRY RUN] Would install Redis 8.0 from packages.redis.io"
fi

# ---------------------------------------------------------------------------
# Deploy New Configuration
# ---------------------------------------------------------------------------
log_step "6/8: Deploy Redis 8 Configuration"

if ! $DRY_RUN; then
  # Deploy new config (pre-staged as .redis8 files)
  if [ -f "$REDIS_CONF_DIR/redis.conf.redis8" ]; then
    cp "$REDIS_CONF_DIR/redis.conf.redis8" "$REDIS_CONF_DIR/redis.conf"
    log_ok "redis.conf deployed"
  else
    log_warn "No redis.conf.redis8 found — using existing config"
  fi

  if [ -f "$REDIS_CONF_DIR/users.acl.redis8" ]; then
    cp "$REDIS_CONF_DIR/users.acl.redis8" "$REDIS_CONF_DIR/users.acl"
    log_ok "users.acl deployed"
  else
    log_warn "No users.acl.redis8 found — ACL file may need manual deployment"
  fi

  # Substitute environment variables in config files.
  # Redis does NOT expand env vars in config files — must be done at deploy time.
  sed -i "s|\${REDIS_PASSWORD}|${REDIS_PASSWORD}|g" "$REDIS_CONF_DIR/redis.conf"
  sed -i "s|\${REDIS_PASSWORD}|${REDIS_PASSWORD}|g" "$REDIS_CONF_DIR/users.acl"
  sed -i "s|\${REDIS_ADMIN_PASSWORD}|${REDIS_ADMIN_PASSWORD}|g" "$REDIS_CONF_DIR/users.acl"
  if [ -n "${REDIS_SENTINEL_PASSWORD:-}" ]; then
    sed -i "s|\${REDIS_SENTINEL_PASSWORD}|${REDIS_SENTINEL_PASSWORD}|g" "$REDIS_CONF_DIR/users.acl"
  fi
  if [ -n "${REDIS_MONITOR_PASSWORD:-}" ]; then
    sed -i "s|\${REDIS_MONITOR_PASSWORD}|${REDIS_MONITOR_PASSWORD}|g" "$REDIS_CONF_DIR/users.acl"
  fi
  log_ok "Environment variables substituted in config files"

  # Ensure correct ownership
  chown redis:redis "$REDIS_CONF_DIR/redis.conf"
  chown redis:redis "$REDIS_CONF_DIR/users.acl" 2>/dev/null || true
  chown -R redis:redis "$REDIS_DATA_DIR"

  log_ok "Configuration deployed with correct ownership"
else
  log_info "[DRY RUN] Would deploy redis.conf and users.acl"
fi

# ---------------------------------------------------------------------------
# Start Redis 8
# ---------------------------------------------------------------------------
log_step "7/8: Start Redis 8.0"

if ! $DRY_RUN; then
  systemctl start redis-server
  sleep 3

  # Verify running
  if ! pgrep -x redis-server > /dev/null; then
    log_error "Redis 8 failed to start! Check: journalctl -u redis-server"
    log_warn "Run --rollback to restore Redis 7.4"
    exit 1
  fi
  log_ok "Redis 8.0 started"
else
  log_info "[DRY RUN] Would start redis-server"
fi

# ---------------------------------------------------------------------------
# Post-Upgrade Verification
# ---------------------------------------------------------------------------
log_step "8/8: Post-Upgrade Verification"

if ! $DRY_RUN; then
  CHECKS_PASSED=0
  CHECKS_TOTAL=8

  # Check 1: Version
  POST_VERSION=$($REDIS_CLI --user default --pass "${REDIS_PASSWORD:-}" INFO server 2>/dev/null | grep redis_version | cut -d: -f2 | tr -d '\r')
  if [[ "$POST_VERSION" == 8.* ]]; then
    log_ok "1/8 Version: Redis $POST_VERSION"
    ((CHECKS_PASSED++))
  else
    log_error "1/8 Version: Expected 8.x, got $POST_VERSION"
  fi

  # Check 2: PING
  if $REDIS_CLI --user default --pass "${REDIS_PASSWORD:-}" PING 2>/dev/null | grep -q PONG; then
    log_ok "2/8 PING: PONG received"
    ((CHECKS_PASSED++))
  else
    log_error "2/8 PING: No response"
  fi

  # Check 3: ACL active
  ACL_USERS=$($REDIS_CLI --user admin --pass "${REDIS_ADMIN_PASSWORD}" ACL LIST 2>/dev/null | wc -l)
  if [ "$ACL_USERS" -ge 2 ]; then
    log_ok "3/8 ACL: $ACL_USERS users configured"
    ((CHECKS_PASSED++))
  else
    log_error "3/8 ACL: Expected >= 2 users, got $ACL_USERS"
  fi

  # Check 4: Dangerous commands blocked for default user
  FLUSH_RESULT=$($REDIS_CLI --user default --pass "${REDIS_PASSWORD:-}" FLUSHDB 2>&1 || true)
  if echo "$FLUSH_RESULT" | grep -qi "NOPERM\|no permissions"; then
    log_ok "4/8 ACL Security: FLUSHDB correctly blocked for default user"
    ((CHECKS_PASSED++))
  else
    log_error "4/8 ACL Security: FLUSHDB NOT blocked! Result: $FLUSH_RESULT"
  fi

  # Check 5: I/O threads
  IO_THREADS=$($REDIS_CLI --user admin --pass "${REDIS_ADMIN_PASSWORD}" CONFIG GET io-threads 2>/dev/null | tail -1)
  if [ "$IO_THREADS" -ge 2 ] 2>/dev/null; then
    log_ok "5/8 I/O Threads: $IO_THREADS configured"
    ((CHECKS_PASSED++))
  else
    log_warn "5/8 I/O Threads: $IO_THREADS (expected >= 2)"
  fi

  # Check 6: Memory
  POST_MEMORY=$($REDIS_CLI --user default --pass "${REDIS_PASSWORD:-}" INFO memory 2>/dev/null | grep used_memory_human | cut -d: -f2 | tr -d '\r')
  log_ok "6/8 Memory: $POST_MEMORY (pre-upgrade: $PRE_MEMORY)"
  ((CHECKS_PASSED++))

  # Check 7: Set/Get test
  $REDIS_CLI --user default --pass "${REDIS_PASSWORD:-}" SET jaraba_upgrade_test "redis8_ok" EX 60 > /dev/null 2>&1
  TEST_VALUE=$($REDIS_CLI --user default --pass "${REDIS_PASSWORD:-}" GET jaraba_upgrade_test 2>/dev/null)
  if [ "$TEST_VALUE" = "redis8_ok" ]; then
    log_ok "7/8 SET/GET: Cache operations working"
    ((CHECKS_PASSED++))
  else
    log_error "7/8 SET/GET: Failed (got: $TEST_VALUE)"
  fi

  # Check 8: Lazy freeing enabled
  LAZY_EVICTION=$($REDIS_CLI --user admin --pass "${REDIS_ADMIN_PASSWORD}" CONFIG GET lazyfree-lazy-eviction 2>/dev/null | tail -1)
  if [ "$LAZY_EVICTION" = "yes" ]; then
    log_ok "8/8 Lazy Freeing: Enabled"
    ((CHECKS_PASSED++))
  else
    log_warn "8/8 Lazy Freeing: $LAZY_EVICTION"
  fi

  echo ""
  echo "============================================"
  if [ "$CHECKS_PASSED" -eq "$CHECKS_TOTAL" ]; then
    log_ok "ALL $CHECKS_TOTAL/$CHECKS_TOTAL CHECKS PASSED"
    echo ""
    log_info "Next steps:"
    log_info "  1. Clear Drupal caches: drush cr"
    log_info "  2. Verify site loads correctly"
    log_info "  3. Check AI cache bins: drush redis:info"
    log_info "  4. Monitor logs: tail -f $REDIS_LOG"
    log_info "  5. Run validator: php scripts/validation/validate-redis-config.php"
  else
    log_error "$CHECKS_PASSED/$CHECKS_TOTAL CHECKS PASSED"
    log_warn "Review failures above. Run --rollback if needed."
  fi
  echo "============================================"
else
  log_info "[DRY RUN] Would verify 8 post-upgrade checks"
  log_ok "Dry run complete. Review output and run without --dry-run to proceed."
fi
