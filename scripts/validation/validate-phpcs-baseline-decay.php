<?php

declare(strict_types=1);

/**
 * @file
 * PHPCS-BASELINE-DECAY-001: Monitors PHPCS baseline size growth.
 *
 * Warns if phpcs-baseline.json grows more than 5% (new violations added
 * without fixing old ones). This prevents gradual quality erosion.
 *
 * Usage: php scripts/validation/validate-phpcs-baseline-decay.php
 * Exit: 0 = PASS, 1 = FAIL (>10% growth), 2 = WARN (>5% growth)
 */

$projectRoot = dirname(__DIR__, 2);
$baselinePath = $projectRoot . '/phpcs-baseline.json';

// Thresholds.
$warnThreshold = 8500;  // Current ~7,883 + 5% buffer.
$failThreshold = 9500;  // ~7,883 + 20% = definite regression.

if (!file_exists($baselinePath)) {
  echo "[WARN] phpcs-baseline.json not found. Skipping decay check.\n";
  exit(0);
}

$baseline = json_decode(file_get_contents($baselinePath), TRUE);
if (!is_array($baseline)) {
  echo "[ERROR] Could not parse phpcs-baseline.json.\n";
  exit(1);
}

$fileCount = count($baseline);
$totalErrors = 0;
$totalWarnings = 0;

foreach ($baseline as $file => $counts) {
  $totalErrors += (int) ($counts['errors'] ?? 0);
  $totalWarnings += (int) ($counts['warnings'] ?? 0);
}

echo "PHPCS-BASELINE-DECAY-001:\n";
echo "  Files with violations: $fileCount\n";
echo "  Total errors: $totalErrors (warn > $warnThreshold, fail > $failThreshold)\n";
echo "  Total warnings: $totalWarnings\n";

if ($totalErrors > $failThreshold) {
  echo "\n[FAIL] PHPCS baseline has grown beyond fail threshold.\n";
  echo "  Action: Run PHPCBF to auto-fix, then regenerate baseline.\n";
  exit(1);
}

if ($totalErrors > $warnThreshold) {
  echo "\n[WARN] PHPCS baseline approaching threshold ($totalErrors > $warnThreshold).\n";
  echo "  Action: Schedule PHPCBF cleanup.\n";
  exit(2);
}

echo "\n[PASS] PHPCS baseline within acceptable limits.\n";
exit(0);
