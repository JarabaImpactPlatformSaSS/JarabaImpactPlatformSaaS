#!/bin/bash
# =============================================================================
# STATUS-REPORT-PROACTIVE-001 — Layer 3: AI Agent Diagnostic Script
#
# Designed to be executed by a scheduled Claude Code agent or manually.
# Outputs structured JSON for AI analysis and GitHub Issue creation.
#
# Usage:
#   bash scripts/diagnostics/diagnose-status-report.sh [--json]
#
# Exit codes:
#   0 = Clean (no issues)
#   1 = Issues found (errors or unexpected warnings)
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$(dirname "$SCRIPT_DIR")")"
JSON_MODE=false

for arg in "$@"; do
  if [ "$arg" = "--json" ]; then
    JSON_MODE=true
  fi
done

cd "$PROJECT_ROOT"

# ─────────────────────────────────────────────────────────────────────────
# 1. Drupal Status Report
# ─────────────────────────────────────────────────────────────────────────
DRUSH_BIN="drush"
if [ -f ".lando.yml" ] && [ -z "${LANDO_INFO:-}" ]; then
  DRUSH_BIN="lando drush"
fi

STATUS_JSON=$($DRUSH_BIN core:requirements --format=json 2>/dev/null || echo '{}')

# Extract errors and warnings.
ERRORS=$(echo "$STATUS_JSON" | python3 -c "
import json, sys
data = json.load(sys.stdin)
baseline = ['ecosistema_jaraba_base_domain','experimental_modules','update_contrib','update_core']
issues = []
for key, item in data.items():
    sev = item.get('severity', 'OK')
    if sev == 'Error':
        issues.append({'key': key, 'severity': 'error', 'title': item.get('title',''), 'value': item.get('value','').strip(), 'description': item.get('description','').strip()[:300]})
    elif sev == 'Warning' and key not in baseline:
        issues.append({'key': key, 'severity': 'warning', 'title': item.get('title',''), 'value': item.get('value','').strip(), 'description': item.get('description','').strip()[:300]})
print(json.dumps(issues))
" 2>/dev/null)

# ─────────────────────────────────────────────────────────────────────────
# 2. Pending Updates
# ─────────────────────────────────────────────────────────────────────────
PENDING_UPDATES=$($DRUSH_BIN updatedb:status --format=json 2>/dev/null || echo '[]')

# ─────────────────────────────────────────────────────────────────────────
# 3. Config Status
# ─────────────────────────────────────────────────────────────────────────
CONFIG_STATUS=$($DRUSH_BIN config:status --format=json 2>/dev/null | python3 -c "
import json, sys
data = json.load(sys.stdin)
# Filter to only show differences.
diffs = [item for item in data if item.get('state', '') != 'Only in sync dir']
print(json.dumps(diffs[:20]))
" 2>/dev/null || echo '[]')

# ─────────────────────────────────────────────────────────────────────────
# 4. Compile Report
# ─────────────────────────────────────────────────────────────────────────
ISSUE_COUNT=$(echo "$ERRORS" | python3 -c "import json,sys; print(len(json.load(sys.stdin)))" 2>/dev/null || echo "0")
UPDATE_COUNT=$(echo "$PENDING_UPDATES" | python3 -c "import json,sys; d=json.load(sys.stdin); print(len(d) if isinstance(d,list) else 0)" 2>/dev/null || echo "0")

if $JSON_MODE; then
  python3 -c "
import json
report = {
    'timestamp': '$(date -u +%Y-%m-%dT%H:%M:%SZ)',
    'hostname': '$(hostname)',
    'issues': json.loads('''$ERRORS'''),
    'pending_updates': json.loads('''$PENDING_UPDATES''') if isinstance(json.loads('''$PENDING_UPDATES'''), list) else [],
    'config_diffs': json.loads('''$CONFIG_STATUS'''),
    'summary': {
        'issue_count': $ISSUE_COUNT,
        'pending_update_count': $UPDATE_COUNT,
        'status': 'clean' if $ISSUE_COUNT == 0 else 'issues_found'
    }
}
print(json.dumps(report, indent=2))
"
else
  echo "=== Drupal Platform Diagnostic Report ==="
  echo "Timestamp: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "Hostname: $(hostname)"
  echo ""
  echo "Status Report Issues: $ISSUE_COUNT"
  echo "Pending Updates: $UPDATE_COUNT"
  echo ""

  if [ "$ISSUE_COUNT" -gt 0 ]; then
    echo "--- Issues ---"
    echo "$ERRORS" | python3 -c "
import json, sys
for item in json.load(sys.stdin):
    print(f'  [{item[\"severity\"].upper()}] {item[\"title\"]}: {item[\"value\"]}')
    if item.get('description'):
        print(f'    {item[\"description\"][:200]}')
"
    echo ""
  fi
fi

# Exit code based on issues.
if [ "$ISSUE_COUNT" -gt 0 ]; then
  exit 1
fi
exit 0
