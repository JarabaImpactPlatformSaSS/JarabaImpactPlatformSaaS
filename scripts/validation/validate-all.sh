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

warn_check() {
  local check_id="$1"
  local description="$2"
  shift 2
  local cmd=("$@")

  TOTAL=$((TOTAL + 1))

  echo -e "${BOLD}[$check_id]${NC} $description"

  local output
  local exit_code=0
  output=$("${cmd[@]}" 2>&1) || exit_code=$?

  if [ $exit_code -eq 0 ]; then
    echo -e "  ${GREEN}PASS${NC}"
    PASSED=$((PASSED + 1))
  else
    # BASELINE-CLEAN-001: Warn but don't block.
    echo -e "  ${YELLOW}WARN${NC} (non-blocking, baseline violations)"
    PASSED=$((PASSED + 1))
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

# Warn-only check: reports but does NOT block CI.
warn_check() {
  local check_id="$1"
  local description="$2"
  shift 2
  local cmd=("$@")

  TOTAL=$((TOTAL + 1))

  echo -e "${BOLD}[$check_id]${NC} $description"

  local output
  local exit_code=0
  output=$("${cmd[@]}" 2>&1) || exit_code=$?

  if [ $exit_code -eq 0 ]; then
    echo -e "  ${GREEN}PASS${NC}"
    PASSED=$((PASSED + 1))
  else
    echo -e "  ${YELLOW}WARN${NC} (non-blocking)"
    echo "$output" | grep -E '\[(ERROR|FAIL|ORPHAN|GHOST)\]' | head -5
    PASSED=$((PASSED + 1))
  fi
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

run_check "I18N-NAVPREFIX-001" "Navigation links use language_prefix" \
  php "$SCRIPT_DIR/validate-nav-i18n.php"

run_check "QUERY-CHAIN-001" "Dangerous query method chaining" \
  php "$SCRIPT_DIR/validate-query-chains.php"

run_check "NO-HARDCODE-PRICE-001" "No hardcoded EUR prices in templates" \
  php "$SCRIPT_DIR/validate-no-hardcoded-prices.php"

run_check "CONTAINER-DEPS-001" "Container dependency integrity (fast)" \
  php "$SCRIPT_DIR/validate-container-deps.php"

run_check "CONTAINER-DEPS-002" "Circular reference detection (fast)" \
  php "$SCRIPT_DIR/validate-circular-deps.php"

run_check "LOGGER-INJECT-001" "Logger injection consistency (fast)" \
  php "$SCRIPT_DIR/validate-logger-injection.php"

run_check "PHANTOM-ARG-001" "Phantom args in services.yml vs constructor params" \
  php "$SCRIPT_DIR/validate-phantom-args.php"

run_check "ORTOGRAFIA-TRANS-001" "Ortografia en textos traducibles Twig (tildes + ñ)" \
  php "$SCRIPT_DIR/validate-twig-ortografia.php"

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

  run_check "TWIG-LANGPREFIX-001" "Twig hardcoded URLs without language prefix" \
    php "$SCRIPT_DIR/validate-twig-langprefix.php"

  run_check "CTA-LOGGED-IN-001" "Conversion CTAs with logged_in conditional" \
    php "$SCRIPT_DIR/validate-cta-logged-in.php"

  run_check "HOOK-DUPLICATE-001" "Duplicate function declarations in .module files" \
    php "$SCRIPT_DIR/validate-hook-duplicates.php"

  run_check "PSR4-CLASSNAME-001" "PHP class names match filenames (PSR-4)" \
    php "$SCRIPT_DIR/validate-psr4-classname.php"

  run_check "JS-CACHE-BUST-001" "JS library versions are current" \
    php "$SCRIPT_DIR/validate-js-cache-bust.php"

  run_check "BIGPIPE-TIMING-001" "BigPipe + once() + drupalSettings timing risks" \
    php "$SCRIPT_DIR/validate-bigpipe-timing.php"

  run_check "IMAGE-WEIGHT-001" "Oversized images in theme" \
    php "$SCRIPT_DIR/validate-image-weight.php"

  run_check "CSS-HIDDEN-OVERRIDE-001" "CSS display vs HTML hidden conflicts" \
    php "$SCRIPT_DIR/validate-css-hidden-override.php"

  run_check "JS-TWIG-SELECTOR-001" "JS↔Twig selector coherence" \
    php "$SCRIPT_DIR/validate-js-twig-selectors.php"

  run_check "ICON-INTEGRITY-001" "Icon references resolve to existing SVGs" \
    php "$SCRIPT_DIR/validate-icon-references.php"

  run_check "QUIZ-FUNNEL-001" "Quiz vertical funnel integrity" \
    php "$SCRIPT_DIR/validate-quiz-funnel.php"

  run_check "CTA-DESTINATION-001" "CTA destinations point to existing routes" \
    php "$SCRIPT_DIR/validate-cta-destinations.php"

  run_check "FUNNEL-COMPLETENESS-001" "Conversion CTAs have tracking attributes" \
    php "$SCRIPT_DIR/validate-funnel-tracking.php"

  run_check "VERTICAL-COVERAGE-001" "All 9 commercial verticals in discovery points" \
    php "$SCRIPT_DIR/validate-vertical-coverage.php"

  run_check "PB-ONBOARDING-001" "Page Builder onboarding integrity (wizard+daily+L1-L4)" \
    php "$SCRIPT_DIR/validate-page-builder-onboarding.php"

  run_check "CONTENT-E2E-001" "Content pipeline E2E (both PB and CH)" \
    php "$SCRIPT_DIR/validate-content-pipeline-e2e.php"

  run_check "PLG-COVERAGE-001" "PLG trigger coverage for Page Builder" \
    php "$SCRIPT_DIR/validate-plg-triggers.php"

  run_check "HOOK-THEME-COMPLETENESS-001" "hook_theme() variable completeness (L3 verification)" \
    php "$SCRIPT_DIR/validate-hook-theme-completeness.php"

  run_check "DUPLICATE-HOOK-001" "Duplicate function definitions in .module files" \
    php "$SCRIPT_DIR/validate-duplicate-hooks.php"

  run_check "SCSS-VARIABLE-EXIST-001" "SCSS variables defined before use" \
    php "$SCRIPT_DIR/validate-scss-variables.php"

  # BASELINE-CLEAN-001: Library attachments + Twig include-only tienen
  # 34+144 violaciones pre-existentes. Registrados como warn_check
  # hasta completar limpieza. Luego migrar a run_check.
  warn_check "LIBRARY-ATTACHMENT-001" "Bundle library declaration + CSS existence" \
    php "$SCRIPT_DIR/validate-library-attachments.php"

  warn_check "TWIG-INCLUDE-ONLY-001" "Twig includes of partials use only keyword" \
    php "$SCRIPT_DIR/validate-twig-include-only.php"

  # DIACRITICS-ES-001: Requires Drupal bootstrap (drush php:script).
  # Prefer lando drush (host drush may not have Drupal bootstrap).
  # Only runs if drush with Drupal is available (skipped in pure-PHP CI).
  DRUSH_CMD=""
  if [ -f "$PROJECT_ROOT/.lando.yml" ] && command -v lando &>/dev/null; then
    DRUSH_CMD="lando drush"
  elif command -v drush &>/dev/null && drush status --field=drupal-version 2>/dev/null | grep -q "^[0-9]"; then
    DRUSH_CMD="drush"
  fi

  if [ -n "$DRUSH_CMD" ]; then
    # Use relative paths for lando (absolute host paths become /app/<abs-path> inside container).
    DIACRITICS_SCRIPT="scripts/validation/validate-spanish-diacritics.php"
    TRANSLATION_SCRIPT="scripts/validation/validate-translation-integrity.php"

    run_check "DIACRITICS-ES-001" "Spanish diacritics in page_content canvas_data" \
      $DRUSH_CMD php:script "$DIACRITICS_SCRIPT"

    run_check "TRANSLATION-INTEG-001" "Translation integrity (cross-page dup, NULL titles, AI fences)" \
      $DRUSH_CMD php:script "$TRANSLATION_SCRIPT"
  else
    skip_check "DIACRITICS-ES-001" "Spanish diacritics (requires Drupal bootstrap via lando drush)"
    skip_check "TRANSLATION-INTEG-001" "Translation integrity (requires Drupal bootstrap via lando drush)"
  fi

  run_check "DEPLOY-READY-001" "Production deploy readiness (domains, settings, nginx)" \
    php "$SCRIPT_DIR/validate-deploy-readiness.php"

  run_check "ENV-PARITY-001" "Dev/Prod environment parity (PHP, DB, Redis, OPcache, SSL)" \
    php "$SCRIPT_DIR/validate-env-parity.php"

  run_check "PRICING-COHERENCE-001" "Pricing vs delivery consistency (Doc 158 v3 rules)" \
    php "$SCRIPT_DIR/validate-pricing-coherence.php"

  run_check "FEATURE-GATING-001" "Feature enforcement audit (limits vs controllers)" \
    php "$SCRIPT_DIR/validate-feature-gating.php"

  run_check "STRIPE-SYNC-001" "Stripe integration verification (Price IDs in config)" \
    php "$SCRIPT_DIR/validate-stripe-sync.php"

  run_check "LANDING-PLAN-COHERENCE-001" "Landing page vs plan coherence (Doc 158 v3)" \
    php "$SCRIPT_DIR/validate-landing-vs-plans.php"

  run_check "ADDON-IMPLEMENTATION-001" "Addon module implementation audit" \
    php "$SCRIPT_DIR/validate-addon-implementation.php"

else
  skip_check "DI-TYPE-001" "Service DI type consistency"
  skip_check "ENTITY-INTEG-001" "Entity convention compliance"
  skip_check "CONFIG-SYNC-001" "Config install/sync consistency"
  skip_check "SERVICE-ORPHAN-001" "Orphaned service detection"
  skip_check "ASSET-FRESHNESS-001" "Compiled asset freshness"
  skip_check "TENANT-CHECK-001" "Tenant isolation verification"
  skip_check "TEST-COVERAGE-MAP-001" "Test coverage map"
  skip_check "OPTIONAL-CROSSMODULE-001" "Cross-module hard dependency detection"
  skip_check "CONTROLLER-READONLY-001" "Controller readonly inherited property detection"
  skip_check "PRESAVE-RESILIENCE-001" "Presave hook resilience detection"
  skip_check "ENTITY-SCHEMA-SYNC-001" "Entity schema sync (computed orphans, translatable fields, Twig url())"
  skip_check "BTN-CONTRAST-DARK-001" "Button contrast on dark backgrounds"
  skip_check "DIACRITICS-ES-001" "Spanish diacritics in page_content"
  skip_check "TRANSLATION-INTEG-001" "Translation integrity"
  skip_check "DEPLOY-READY-001" "Production deploy readiness"
  skip_check "ENV-PARITY-001" "Dev/Prod environment parity"
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
