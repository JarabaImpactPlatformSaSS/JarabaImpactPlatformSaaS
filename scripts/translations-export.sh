#!/usr/bin/env bash
# =============================================================================
# Jaraba Impact Platform — Export custom translations to .po files
# =============================================================================
# Exports customized interface translations from locale DB to versioned .po
# files. Run this after making translation changes in the Drupal UI.
#
# Usage:
#   lando ssh -c "bash /app/scripts/translations-export.sh"
#   # or from host:
#   bash scripts/translations-export.sh  (requires drush in PATH)
#
# Output: translations/es-custom.po, translations/pt-br-custom.po
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
TRANSLATIONS_DIR="$PROJECT_DIR/translations"

mkdir -p "$TRANSLATIONS_DIR"

echo "=== Exporting custom translations ==="

# Detect if running inside Lando or directly.
if command -v drush &> /dev/null; then
  DRUSH="drush"
elif [ -f "$PROJECT_DIR/vendor/bin/drush" ]; then
  DRUSH="$PROJECT_DIR/vendor/bin/drush"
else
  echo "ERROR: drush not found. Run inside Lando: lando ssh -c 'bash /app/scripts/translations-export.sh'"
  exit 1
fi

# Export Spanish (primary).
echo -n "  es: "
$DRUSH locale:export es --types=customized > "$TRANSLATIONS_DIR/es-custom.po" 2>/dev/null
ES_COUNT=$(grep -c '^msgid ' "$TRANSLATIONS_DIR/es-custom.po" 2>/dev/null || echo 0)
echo "$ES_COUNT strings exported"

# Export Portuguese (secondary).
echo -n "  pt-br: "
$DRUSH locale:export pt-br --types=customized > "$TRANSLATIONS_DIR/pt-br-custom.po" 2>/dev/null
PT_COUNT=$(grep -c '^msgid ' "$TRANSLATIONS_DIR/pt-br-custom.po" 2>/dev/null || echo 0)
echo "$PT_COUNT strings exported"

echo ""
echo "Files:"
ls -lah "$TRANSLATIONS_DIR/"*.po
echo ""
echo "Next: git add translations/ && git commit -m 'i18n: update custom translations'"
