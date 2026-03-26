<?php

declare(strict_types=1);

/**
 * @file
 * CLAUDE-MD-SIZE-001: Validates CLAUDE.md stays within performance budget.
 *
 * Claude Code degrades performance when CLAUDE.md exceeds 40k chars.
 * This validator enforces a 39k hard limit with a 36k warning threshold
 * to prevent drift.
 *
 * Usage: php scripts/validation/validate-claude-md-size.php
 * Exit codes: 0 = PASS, 1 = FAIL (>39k), 2 = WARN (>36k)
 */

$projectRoot = dirname(__DIR__, 2);
$claudeMdPath = $projectRoot . '/CLAUDE.md';

$hardLimit = 39000;
$warnLimit = 36000;

if (!file_exists($claudeMdPath)) {
  echo "[ERROR] CLAUDE.md not found at: $claudeMdPath\n";
  exit(1);
}

$content = file_get_contents($claudeMdPath);
$charCount = strlen($content);
$lineCount = substr_count($content, "\n") + 1;

// Count rule IDs (pattern: WORD-WORD-NNN or WORD-NNN).
preg_match_all('/[A-Z]+-[A-Z]*-?\d{3}/', $content, $ruleMatches);
$ruleCount = count(array_unique($ruleMatches[0]));

echo "CLAUDE.md size check:\n";
echo "  Characters: $charCount / $hardLimit max ($warnLimit warn)\n";
echo "  Lines: $lineCount\n";
echo "  Rule IDs: $ruleCount\n";

if ($charCount > $hardLimit) {
  $excess = $charCount - $hardLimit;
  echo "\n[FAIL] CLAUDE.md exceeds hard limit by $excess chars.\n";
  echo "  Action: Move implementation state to memory/ topic files.\n";
  echo "  Only MUST/NEVER/ALWAYS rules belong in CLAUDE.md.\n";
  exit(1);
}

if ($charCount > $warnLimit) {
  $remaining = $hardLimit - $charCount;
  echo "\n[WARN] CLAUDE.md approaching limit. $remaining chars remaining.\n";
  echo "  Consider proactive cleanup before next feature additions.\n";
  // Exit 0 (not 2) — warning is non-blocking for lint-staged/pre-commit.
  // Hard limit violation (above) exits with 1 and IS blocking.
  exit(0);
}

$remaining = $hardLimit - $charCount;
echo "\n[PASS] $remaining chars of budget remaining.\n";
exit(0);
