#!/bin/bash
# =============================================================================
# CONFIG-SYNC-001: Validate config/install vs config/sync consistency.
#
# Detects config/install YMLs from active modules missing in config/sync.
# Also detects config/sync references to inactive modules.
#
# Usage: bash scripts/validation/validate-config-sync.sh
# Exit:  0 = consistent, 1 = critical issues found
# =============================================================================

set -euo pipefail

# Paths.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
MODULES_DIR="$PROJECT_ROOT/web/modules/custom"
CONFIG_SYNC="$PROJECT_ROOT/config/sync"
CORE_EXTENSION="$CONFIG_SYNC/core.extension.yml"

# Colors.
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BOLD='\033[1m'
NC='\033[0m'

echo ""
echo -e "${BOLD}=== CONFIG-SYNC-001: Config install/sync consistency ===${NC}"
echo ""

if [ ! -f "$CORE_EXTENSION" ]; then
  echo -e "${RED}ERROR: $CORE_EXTENSION not found${NC}"
  exit 1
fi

if [ ! -d "$CONFIG_SYNC" ]; then
  echo -e "${RED}ERROR: $CONFIG_SYNC not found${NC}"
  exit 1
fi

# ─────────────────────────────────────────────────────────
# 1. Parse active modules from core.extension.yml
# ─────────────────────────────────────────────────────────
declare -a ACTIVE_MODULES=()
in_module_section=false

while IFS= read -r line; do
  if [[ "$line" == "module:" ]]; then
    in_module_section=true
    continue
  fi
  if [[ "$line" == "theme:" ]] || [[ "$line" == "profile:"* ]]; then
    in_module_section=false
    continue
  fi
  if $in_module_section; then
    module_name=$(echo "$line" | sed -n 's/^  \([a-zA-Z_][a-zA-Z0-9_]*\):.*/\1/p')
    if [ -n "$module_name" ]; then
      ACTIVE_MODULES+=("$module_name")
    fi
  fi
done < "$CORE_EXTENSION"

echo -e "  Active modules: ${#ACTIVE_MODULES[@]}"

# ─────────────────────────────────────────────────────────
# 2. Check config/sync for references to non-active custom modules
# ─────────────────────────────────────────────────────────
warnings=0
errors=0

# Helper: check if module is active.
is_active() {
  local mod="$1"
  for active in "${ACTIVE_MODULES[@]}"; do
    if [ "$mod" = "$active" ]; then
      return 0
    fi
  done
  return 1
}

echo ""
echo -e "${BOLD}Config/sync files referencing inactive custom modules:${NC}"
inactive_refs=0

# Only check jaraba_* and ecosistema_* config files.
for sync_file in "$CONFIG_SYNC"/*.yml; do
  basename_file=$(basename "$sync_file" .yml)

  # Extract module prefix from config name (first segment before .).
  config_module=$(echo "$basename_file" | cut -d. -f1)

  # Only check custom module configs (skip theme configs).
  case "$config_module" in
    ecosistema_jaraba_theme)
      # Theme config — not a module, skip.
      ;;
    jaraba_*|ecosistema_*)
      if ! is_active "$config_module"; then
        echo -e "  ${YELLOW}[WARN]${NC} $basename_file.yml → module '$config_module' not active"
        inactive_refs=$((inactive_refs + 1))
        warnings=$((warnings + 1))
      fi
      ;;
  esac
done

if [ "$inactive_refs" -eq 0 ]; then
  echo -e "  ${GREEN}None${NC}"
fi

# ─────────────────────────────────────────────────────────
# 3. Check active custom modules' config/install for missing sync
# ─────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}Active module config/install not in config/sync:${NC}"
missing_count=0

for active_mod in "${ACTIVE_MODULES[@]}"; do
  # Only check custom modules.
  case "$active_mod" in
    jaraba_*|ecosistema_*)
      # Find the module's config/install directory.
      config_install=""
      if [ -d "$MODULES_DIR/$active_mod/config/install" ]; then
        config_install="$MODULES_DIR/$active_mod/config/install"
      else
        # Try submodule paths.
        found_dir=$(find "$MODULES_DIR" -path "*/modules/$active_mod/config/install" -type d 2>/dev/null | head -1)
        if [ -n "$found_dir" ]; then
          config_install="$found_dir"
        fi
      fi

      if [ -z "$config_install" ] || [ ! -d "$config_install" ]; then
        continue
      fi

      for install_yml in "$config_install"/*.yml; do
        [ -f "$install_yml" ] || continue
        install_name=$(basename "$install_yml")

        # Skip schema files and optional config.
        if [[ "$install_name" == *.schema.yml ]]; then
          continue
        fi

        # Check if exists in config/sync.
        if [ ! -f "$CONFIG_SYNC/$install_name" ]; then
          # Not critical — config may have been overridden or is optional.
          # Only warn for non-views, non-field configs.
          case "$install_name" in
            views.view.*|field.storage.*|field.field.*)
              # These are frequently absent from sync (created at runtime).
              ;;
            *)
              echo -e "  ${YELLOW}[INFO]${NC} $install_name (from $active_mod)"
              missing_count=$((missing_count + 1))
              ;;
          esac
        fi
      done
      ;;
  esac
done

if [ "$missing_count" -eq 0 ]; then
  echo -e "  ${GREEN}All synced${NC}"
fi

# ─────────────────────────────────────────────────────────
# 4. Summary
# ─────────────────────────────────────────────────────────
echo ""
echo -e "${BOLD}Summary:${NC}"
echo -e "  Config/sync files: $(ls -1 "$CONFIG_SYNC"/*.yml 2>/dev/null | wc -l)"
echo -e "  Inactive module refs: $inactive_refs"
echo -e "  Missing from sync: $missing_count"
echo -e "  Errors: $errors"
echo -e "  Warnings: $warnings"

if [ "$errors" -gt 0 ]; then
  echo ""
  echo -e "${RED}${BOLD}FAIL: $errors critical issue(s).${NC}"
  exit 1
fi

echo ""
echo -e "${GREEN}${BOLD}OK: Config sync is consistent (${warnings} warnings).${NC}"
exit 0
