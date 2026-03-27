#!/bin/bash
# SAFEGUARD-PHPSTAN-TIMEOUT-001: PHPStan wrapper with 5-minute timeout.
# Used by lint-staged to prevent PHPStan from hanging indefinitely
# on large changesets or memory-intensive analyses.
#
# Usage: bash scripts/lint/phpstan-staged.sh [files...]

set -euo pipefail

TIMEOUT_SECONDS=600

if command -v timeout &>/dev/null; then
  timeout "$TIMEOUT_SECONDS" php -d memory_limit=4G vendor/bin/phpstan analyse --no-progress --error-format=raw "$@"
else
  # macOS fallback (no coreutils timeout).
  php -d memory_limit=4G vendor/bin/phpstan analyse --no-progress --error-format=raw "$@"
fi
