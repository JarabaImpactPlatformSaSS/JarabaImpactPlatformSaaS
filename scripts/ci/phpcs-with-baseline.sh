#!/bin/bash
# =============================================================================
# PHPCS with Baseline — Only fails on NEW violations
# =============================================================================
# Uses phpcs-baseline.json to track known violations per file.
# CI passes if error count per file does not INCREASE vs baseline.
# New files with violations always fail.
#
# Usage: bash scripts/ci/phpcs-with-baseline.sh
# Exit: 0 = pass, 1 = new violations found
# =============================================================================

set -uo pipefail

BASELINE="phpcs-baseline.json"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$PROJECT_ROOT"

if [ ! -f "$BASELINE" ]; then
  echo "WARNING: No PHPCS baseline found. Running without baseline."
  vendor/bin/phpcs --standard=Drupal,DrupalPractice \
    --extensions=php,module,inc,install,theme \
    --ignore=*/vendor/*,*/node_modules/* \
    web/modules/custom
  exit $?
fi

# Run PHPCS and save JSON to temp file.
TMPFILE=$(mktemp)
trap "rm -f $TMPFILE" EXIT

# Detect Lando vs CI environment.
PHPCS_CMD="php -d memory_limit=512M vendor/bin/phpcs"
if [ -f ".lando.yml" ] && [ -z "${CI:-}" ]; then
  PHPCS_CMD="lando php -d memory_limit=2G vendor/bin/phpcs"
fi

$PHPCS_CMD \
  --standard=Drupal,DrupalPractice \
  --extensions=php,module,inc,install,theme \
  --ignore=*/vendor/*,*/node_modules/* \
  --report=json \
  web/modules/custom > "$TMPFILE" 2>/dev/null || true

# Compare against baseline.
python3 -c "
import json, sys

baseline = json.load(open('$BASELINE'))
current = json.load(open('$TMPFILE'))

new_violations = []
improved_count = 0

for filepath, info in current.get('files', {}).items():
    curr_errors = info.get('errors', 0)
    if curr_errors == 0:
        continue

    # Normalize path — strip leading ./ or absolute prefixes.
    path = filepath
    for prefix in ['/app/', '${PROJECT_ROOT}/']:
        if path.startswith(prefix):
            path = path[len(prefix):]

    if path in baseline:
        base_errors = baseline[path].get('errors', 0)
        if curr_errors > base_errors:
            new_violations.append(f'  {path}: {curr_errors} errors (was {base_errors}, +{curr_errors - base_errors} NEW)')
        elif curr_errors < base_errors:
            improved_count += 1
    else:
        new_violations.append(f'  {path}: {curr_errors} errors (NEW FILE not in baseline)')

if improved_count > 0:
    print(f'Improved: {improved_count} files have fewer violations than baseline')

if new_violations:
    print(f'\n[FAIL] {len(new_violations)} file(s) have NEW PHPCS violations:\n')
    for v in new_violations[:30]:
        print(v)
    if len(new_violations) > 30:
        print(f'  ... and {len(new_violations) - 30} more')
    sys.exit(1)
else:
    total = current.get('totals', {}).get('errors', 0)
    print(f'[PASS] No new PHPCS violations ({total} baselined errors in {len(baseline)} files)')
    sys.exit(0)
"
