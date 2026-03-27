#!/bin/bash
# PHPSTAN-BASELINE-REGEN-001: Regenera el baseline de PHPStan.
#
# El baseline actual (157K+ líneas) requiere >4GB RAM y >5 min.
# Ejecutar en un entorno con suficiente memoria (servidor IONOS, no WSL).
#
# Uso: bash scripts/maintenance/regenerate-phpstan-baseline.sh
#
# IMPORTANTE: Ejecutar DESPUÉS de resolver errores reales (no para silenciarlos).
# El baseline solo debe contener errores heredados de parent classes y
# tipos no resolubles sin refactoring mayor.

set -euo pipefail

echo "=== PHPSTAN BASELINE REGENERATION ==="
echo "Requires: ~4GB RAM, ~10 minutes"
echo ""

php -d memory_limit=4G vendor/bin/phpstan analyse \
  --generate-baseline phpstan-baseline.neon \
  --memory-limit=4G \
  2>&1

LINES=$(wc -l < phpstan-baseline.neon)
echo ""
echo "✓ Baseline regenerated: $LINES lines"
echo "  Commit with: git add phpstan-baseline.neon && git commit -m 'chore: regenerate PHPStan baseline'"
