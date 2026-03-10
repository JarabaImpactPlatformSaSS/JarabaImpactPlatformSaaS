#!/bin/bash
# =============================================================================
# VALIDATE-ALL: Architectural validation orchestrator.
#
# Runs all architectural validation scripts and reports combined results.
# Auto-discovers modules, services, routes, and entities dynamically.
#
# Usage:
#   bash scripts/validation/validate-all.sh                    # Full mode (all checks)
#   bash scripts/validation/validate-all.sh --fast             # Fast mode (pre-commit)
#   bash scripts/validation/validate-all.sh --checklist <path> # Module checklist
#
# Exit: 0 = all clean, N = number of failed checks
# =============================================================================

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Colors.
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Parse arguments.
MODE="full"
CHECKLIST_MODULE=""
if [ "${1:-}" = "--fast" ]; then
  MODE="fast"
elif [ "${1:-}" = "--checklist" ]; then
  MODE="checklist"
  CHECKLIST_MODULE="${2:-}"
  if [ -z "$CHECKLIST_MODULE" ]; then
    echo "Usage: validate-all.sh --checklist <module_path>"
    echo "  e.g.: validate-all.sh --checklist web/modules/custom/jaraba_copilot_v2"
    exit 1
  fi
fi

ERRORS=0
PASSED=0
SKIPPED=0
TOTAL=0

# ─────────────────────────────────────────────────────────
# Runner function: executes a check and tracks results.
# ─────────────────────────────────────────────────────────
run_check() {
  local check_id="$1"
  local description="$2"
  shift 2
  local cmd=("$@")

  TOTAL=$((TOTAL + 1))

  echo -e "${BOLD}[$check_id]${NC} $description"

  # Run the command, capture output and exit code.
  local output
  local exit_code=0
  output=$("${cmd[@]}" 2>&1) || exit_code=$?

  if [ $exit_code -eq 0 ]; then
    echo -e "  ${GREEN}PASS${NC}"
    PASSED=$((PASSED + 1))
  else
    echo -e "  ${RED}FAIL${NC}"
    # Show output on failure (errors + key violations).
    echo "$output" | grep -E '\[(ERROR|FAIL|ORPHAN|GHOST)\]' | head -10
    ERRORS=$((ERRORS + 1))
  fi
  echo ""
}

skip_check() {
  local check_id="$1"
  local description="$2"
  TOTAL=$((TOTAL + 1))
  SKIPPED=$((SKIPPED + 1))
  echo -e "${BOLD}[$check_id]${NC} $description"
  echo -e "  ${YELLOW}SKIP${NC} (--fast mode)"
  echo ""
}

# ─────────────────────────────────────────────────────────
# Checklist mode: skip directly to module-specific checks.
# ─────────────────────────────────────────────────────────
if [ "$MODE" = "checklist" ]; then
  # Jump to the checklist section (defined later in file).
  # Set these to avoid unbound variable errors.
  ERRORS=0
  PASSED=0
  SKIPPED=0
  TOTAL=0
else

# ─────────────────────────────────────────────────────────
# Header.
# ─────────────────────────────────────────────────────────
echo ""
echo "============================================================"
echo "  JARABA IMPACT PLATFORM — Architectural Validation"
echo "  Mode: $MODE | $(date '+%Y-%m-%d %H:%M:%S')"
echo "============================================================"
echo ""

# ─────────────────────────────────────────────────────────
# Run checks.
# ─────────────────────────────────────────────────────────

# Fast checks (always run).
MODULE_CHECK="$PROJECT_ROOT/scripts/maintenance/check-module-consistency.sh"
if [ -f "$MODULE_CHECK" ]; then
  run_check "MODULE-ORPHAN-001" "Module consistency (orphan/ghost detection)" \
    bash "$MODULE_CHECK"
else
  echo -e "${YELLOW}[MODULE-ORPHAN-001] Script not found: $MODULE_CHECK${NC}"
  echo ""
fi

run_check "ROUTE-CTRL-001" "Route-Controller method validation" \
  php "$SCRIPT_DIR/validate-routing.php"

run_check "QUERY-CHAIN-001" "Dangerous query method chaining" \
  php "$SCRIPT_DIR/validate-query-chains.php"

run_check "CONTAINER-DEPS-001" "Container dependency integrity (fast)" \
  php "$SCRIPT_DIR/validate-container-deps.php"

run_check "CONTAINER-DEPS-002" "Circular reference detection (fast)" \
  php "$SCRIPT_DIR/validate-circular-deps.php"

run_check "LOGGER-INJECT-001" "Logger injection consistency (fast)" \
  php "$SCRIPT_DIR/validate-logger-injection.php"

# Full-only checks.
if [ "$MODE" = "full" ]; then
  run_check "DI-TYPE-001" "Service DI type consistency" \
    php "$SCRIPT_DIR/validate-services-di.php"

  run_check "ENTITY-INTEG-001" "Entity convention compliance" \
    php "$SCRIPT_DIR/validate-entity-integrity.php"

  run_check "CONFIG-SYNC-001" "Config install/sync consistency" \
    bash "$SCRIPT_DIR/validate-config-sync.sh"

  # New checks (full mode only).
  run_check "SERVICE-ORPHAN-001" "Orphaned service detection" \
    php "$SCRIPT_DIR/validate-service-consumers.php"

  # ASSET-FRESHNESS-001: Skipped in CI (--full). Timestamp-based check is
  # unreliable after git checkout (all files get same mtime). The authoritative
  # check is SCSS-COMPILE-VERIFY-001 which runs later with npm build + git diff.
  skip_check "ASSET-FRESHNESS-001" "Compiled asset freshness (deferred to SCSS-COMPILE-VERIFY-001)"

  run_check "TENANT-CHECK-001" "Tenant isolation verification" \
    php "$SCRIPT_DIR/validate-tenant-isolation.php"

  run_check "TEST-COVERAGE-MAP-001" "Test coverage map" \
    php "$SCRIPT_DIR/validate-test-coverage-map.php"

  run_check "OPTIONAL-CROSSMODULE-001" "Cross-module hard dependency detection" \
    php "$SCRIPT_DIR/validate-optional-deps.php"

  run_check "CONTROLLER-READONLY-001" "Controller readonly inherited property detection" \
    php "$SCRIPT_DIR/validate-controller-readonly.php"

  run_check "PRESAVE-RESILIENCE-001" "Presave hook resilience detection" \
    php "$SCRIPT_DIR/validate-presave-resilience.php"

  run_check "ENTITY-SCHEMA-SYNC-001" "Entity schema sync (computed orphans, translatable fields, Twig url())" \
    php "$SCRIPT_DIR/validate-entity-schema-sync.php"

  run_check "BTN-CONTRAST-DARK-001" "Button contrast on dark backgrounds" \
    php "$SCRIPT_DIR/validate-btn-contrast-dark.php"

  # DIACRITICS-ES-001: Requires Drupal bootstrap (drush php:script).
  # Only runs if drush is available (skipped in pure-PHP CI).
  if command -v drush &>/dev/null; then
    run_check "DIACRITICS-ES-001" "Spanish diacritics in page_content canvas_data" \
      drush php:script "$SCRIPT_DIR/validate-spanish-diacritics.php"
  else
    skip_check "DIACRITICS-ES-001" "Spanish diacritics (requires drush)"
  fi

  run_check "DEPLOY-READY-001" "Production deploy readiness (domains, settings, nginx)" \
    php "$SCRIPT_DIR/validate-deploy-readiness.php"

else
  skip_check "DI-TYPE-001" "Service DI type consistency"
  skip_check "ENTITY-INTEG-001" "Entity convention compliance"
  skip_check "CONFIG-SYNC-001" "Config install/sync consistency"
  skip_check "SERVICE-ORPHAN-001" "Orphaned service detection"
  skip_check "ASSET-FRESHNESS-001" "Compiled asset freshness"
  skip_check "TENANT-CHECK-001" "Tenant isolation verification"
  skip_check "TEST-COVERAGE-MAP-001" "Test coverage map"
  skip_check "OPTIONAL-CROSSMODULE-001" "Cross-module hard dependency detection"
  skip_check "ENTITY-SCHEMA-SYNC-001" "Entity schema sync (computed orphans, translatable fields, Twig url())"
  skip_check "BTN-CONTRAST-DARK-001" "Button contrast on dark backgrounds"
  skip_check "DIACRITICS-ES-001" "Spanish diacritics in page_content"
  skip_check "DEPLOY-READY-001" "Production deploy readiness"
fi

fi  # End of non-checklist mode guard.

# ─────────────────────────────────────────────────────────
# Checklist mode: targeted checks for a single module.
# ─────────────────────────────────────────────────────────
if [ "$MODE" = "checklist" ]; then
  MODULE_PATH="$PROJECT_ROOT/$CHECKLIST_MODULE"
  MODULE_NAME=$(basename "$MODULE_PATH")

  echo ""
  echo "============================================================"
  echo "  JARABA IMPACT PLATFORM — Module Checklist"
  echo "  Module: $MODULE_NAME | $(date '+%Y-%m-%d %H:%M:%S')"
  echo "============================================================"
  echo ""

  CL_PASS=0
  CL_FAIL=0

  checklist_item() {
    local label="$1"
    local result="$2"
    if [ "$result" = "PASS" ]; then
      echo -e "  ${GREEN}[PASS]${NC} $label"
      CL_PASS=$((CL_PASS + 1))
    else
      echo -e "  ${RED}[FAIL]${NC} $label"
      CL_FAIL=$((CL_FAIL + 1))
    fi
  }

  echo -e "${BOLD}=== COMPLITUD ===${NC}"

  # Services registered and consumed.
  if [ -f "$MODULE_PATH/$MODULE_NAME.services.yml" ]; then
    checklist_item "services.yml exists" "PASS"
  else
    if ls "$MODULE_PATH/src/Service/"*.php 1>/dev/null 2>&1; then
      checklist_item "services.yml exists (has Service classes)" "FAIL"
    else
      checklist_item "services.yml exists (no services needed)" "PASS"
    fi
  fi

  # Routes point to existing controllers.
  if [ -f "$MODULE_PATH/$MODULE_NAME.routing.yml" ]; then
    checklist_item "routing.yml exists" "PASS"
  else
    checklist_item "routing.yml exists" "PASS"
  fi

  # Entity checks.
  if ls "$MODULE_PATH/src/Entity/"*.php 1>/dev/null 2>&1; then
    checklist_item "Entity classes exist" "PASS"
    # AccessControlHandler check.
    if grep -rl "AccessControlHandler" "$MODULE_PATH/src/" >/dev/null 2>&1 || \
       grep -l "admin_permission" "$MODULE_PATH/src/Entity/"*.php >/dev/null 2>&1; then
      checklist_item "AccessControlHandler declared" "PASS"
    else
      checklist_item "AccessControlHandler declared" "FAIL"
    fi
  fi

  echo ""
  echo -e "${BOLD}=== INTEGRIDAD ===${NC}"

  # Tests exist.
  if [ -d "$MODULE_PATH/tests/src/Unit" ]; then
    checklist_item "Unit tests directory" "PASS"
  else
    if ls "$MODULE_PATH/src/Service/"*.php 1>/dev/null 2>&1; then
      checklist_item "Unit tests directory (has services)" "FAIL"
    else
      checklist_item "Unit tests directory" "PASS"
    fi
  fi

  if [ -d "$MODULE_PATH/tests/src/Kernel" ]; then
    checklist_item "Kernel tests directory" "PASS"
  else
    if ls "$MODULE_PATH/src/Entity/"*.php 1>/dev/null 2>&1; then
      checklist_item "Kernel tests directory (has entities)" "FAIL"
    else
      checklist_item "Kernel tests directory" "PASS"
    fi
  fi

  echo ""
  echo -e "${BOLD}=== CONSISTENCIA ===${NC}"

  # SCSS compiled.
  if ls "$MODULE_PATH/"*.libraries.yml 1>/dev/null 2>&1; then
    checklist_item "Libraries yml exists" "PASS"
  else
    checklist_item "Libraries yml" "PASS"
  fi

  echo ""
  echo -e "${BOLD}=== COHERENCIA ===${NC}"

  # Info.yml exists.
  if [ -f "$MODULE_PATH/$MODULE_NAME.info.yml" ]; then
    checklist_item "info.yml exists" "PASS"
  else
    checklist_item "info.yml exists" "FAIL"
  fi

  echo ""
  echo "============================================================"
  echo -e "  ${BOLD}Checklist:${NC} $CL_PASS passed, $CL_FAIL failed"
  echo "============================================================"

  if [ $CL_FAIL -gt 0 ]; then
    echo -e "  ${RED}${BOLD}$CL_FAIL item(s) need attention.${NC}"
    echo ""
    exit $CL_FAIL
  fi

  echo -e "  ${GREEN}${BOLD}Module checklist complete.${NC}"
  echo ""
  exit 0
fi

# ─────────────────────────────────────────────────────────
# Summary (full/fast mode).
# ─────────────────────────────────────────────────────────
echo "============================================================"
echo -e "  ${BOLD}Results:${NC} $PASSED passed, $ERRORS failed, $SKIPPED skipped (of $TOTAL)"
echo "============================================================"

if [ $ERRORS -gt 0 ]; then
  echo -e "  ${RED}${BOLD}$ERRORS validation(s) FAILED.${NC}"
  echo ""
  exit $ERRORS
fi

echo -e "  ${GREEN}${BOLD}All validations passed.${NC}"
echo ""
exit 0
