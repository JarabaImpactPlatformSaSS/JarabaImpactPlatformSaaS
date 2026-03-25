#!/usr/bin/env bash
# =============================================================================
# Jaraba Impact Platform — Import custom translations from .po files
# =============================================================================
# Imports versioned .po files into locale DB. Idempotent: safe to run N times.
# Only overwrites customized translations (--override=customized).
#
# Usage:
#   drush php:script scripts/translations-import.sh
#   # or inside Lando:
#   lando ssh -c "bash /app/scripts/translations-import.sh"
#   # Dry run (count only):
#   bash scripts/translations-import.sh --dry-run
#
# Called automatically by deploy.yml post-deploy step.
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
TRANSLATIONS_DIR="$PROJECT_DIR/translations"
DRY_RUN=false

for arg in "$@"; do
  case $arg in
    --dry-run) DRY_RUN=true ;;
  esac
done

echo "=== Importing custom translations ==="
$DRY_RUN && echo "  (DRY RUN — no changes)"

# Detect drush.
if command -v drush &> /dev/null; then
  DRUSH="drush"
elif [ -f "$PROJECT_DIR/vendor/bin/drush" ]; then
  DRUSH="$PROJECT_DIR/vendor/bin/drush"
else
  echo "ERROR: drush not found"
  exit 1
fi

ERRORS=0

for PO_FILE in "$TRANSLATIONS_DIR"/*-custom.po; do
  [ -f "$PO_FILE" ] || continue

  FILENAME=$(basename "$PO_FILE")
  # Extract langcode from filename: es-custom.po → es, pt-br-custom.po → pt-br
  LANGCODE="${FILENAME%-custom.po}"
  STRING_COUNT=$(grep -c '^msgid ' "$PO_FILE" 2>/dev/null || echo 0)

  echo -n "  $LANGCODE: $STRING_COUNT strings"

  if [ "$STRING_COUNT" -eq 0 ]; then
    echo " (empty, skipped)"
    continue
  fi

  if $DRY_RUN; then
    echo " (dry-run, would import)"
    continue
  fi

  # Import with --override=customized: only overwrites strings marked as custom.
  # --type=customized: marks imported strings as customized.
  if $DRUSH locale:import "$LANGCODE" "$PO_FILE" --type=customized --override=customized -y 2>/dev/null; then
    echo " → imported"
  else
    echo " → ERROR"
    ((ERRORS++))
  fi
done

if [ "$ERRORS" -gt 0 ]; then
  echo ""
  echo "ERROR: $ERRORS import(s) failed"
  exit 1
fi

echo ""
echo "=== Import complete ==="
