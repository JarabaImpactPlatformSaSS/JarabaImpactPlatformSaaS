<?php

declare(strict_types=1);

/**
 * @file
 * STATUS-REPORT-PROACTIVE-001: Validates Drupal status report programmatically.
 *
 * Executes `drush core:requirements` and fails on Errors, warns on new Warnings.
 * Filters expected dev-environment warnings via baseline.
 *
 * Usage:
 *   php scripts/validation/validate-status-report.php [--env=dev|prod]
 *
 * Exit codes:
 *   0 = PASS (no errors, no unexpected warnings)
 *   1 = FAIL (errors found)
 *   2 = WARN (unexpected warnings, non-blocking)
 */

$projectRoot = dirname(__DIR__, 2);

// Parse --env flag (default: dev).
$env = 'dev';
foreach ($argv as $arg) {
  if (str_starts_with($arg, '--env=')) {
    $env = substr($arg, 6);
  }
}

// ─────────────────────────────────────────────────────────────────────────
// Baseline: expected warnings per environment.
// Keys = requirement array keys from hook_requirements (not titles).
// ─────────────────────────────────────────────────────────────────────────
$expectedWarnings = [
  'dev' => [
    'ecosistema_jaraba_base_domain',  // Lando domain expected in dev.
    'experimental_modules',            // Custom modules intentionally experimental.
    'update_contrib',                  // Contrib update check (routine).
    'update_core',                     // Core update check (routine).
  ],
  'prod' => [
    'experimental_modules',            // Still intentionally experimental.
  ],
];

$baseline = $expectedWarnings[$env] ?? $expectedWarnings['dev'];

// ─────────────────────────────────────────────────────────────────────────
// Execute drush core:requirements.
// ─────────────────────────────────────────────────────────────────────────
$drushBin = 'drush';

// Detect Lando environment.
if (file_exists($projectRoot . '/.lando.yml') && !getenv('LANDO_INFO')) {
  $drushBin = 'lando drush';
}

$cmd = "$drushBin core:requirements --format=json 2>/dev/null";
$output = shell_exec($cmd);

if ($output === null || $output === '') {
  echo "[ERROR] Failed to execute: $cmd\n";
  echo "  Ensure Drupal is bootstrapped and drush is available.\n";
  exit(1);
}

$requirements = json_decode($output, true);
if (!is_array($requirements)) {
  echo "[ERROR] Failed to parse drush output as JSON.\n";
  exit(1);
}

// ─────────────────────────────────────────────────────────────────────────
// Classify results.
// ─────────────────────────────────────────────────────────────────────────
$errors = [];
$unexpectedWarnings = [];
$baselineWarnings = [];
$okCount = 0;

foreach ($requirements as $key => $item) {
  $severity = $item['severity'] ?? 'OK';
  $title = $item['title'] ?? $key;
  $value = trim($item['value'] ?? '');

  if ($severity === 'Error') {
    $errors[] = [
      'key' => $key,
      'title' => $title,
      'value' => $value,
      'description' => trim($item['description'] ?? ''),
    ];
  }
  elseif ($severity === 'Warning') {
    if (in_array($key, $baseline, true)) {
      $baselineWarnings[] = "$title";
    }
    else {
      $unexpectedWarnings[] = [
        'key' => $key,
        'title' => $title,
        'value' => $value,
      ];
    }
  }
  else {
    $okCount++;
  }
}

// ─────────────────────────────────────────────────────────────────────────
// Report.
// ─────────────────────────────────────────────────────────────────────────
$total = count($requirements);
echo "Drupal Status Report Check (env=$env):\n";
echo "  Total checks: $total\n";
echo "  OK/Info: $okCount\n";
echo "  Errors: " . count($errors) . "\n";
echo "  Warnings (unexpected): " . count($unexpectedWarnings) . "\n";
echo "  Warnings (baseline): " . count($baselineWarnings) . "\n";

if (!empty($errors)) {
  echo "\n[FAIL] Errors found:\n";
  foreach ($errors as $e) {
    echo "  - [{$e['key']}] {$e['title']}: {$e['value']}\n";
    if ($e['description']) {
      // First 200 chars of description.
      $desc = substr(preg_replace('/\s+/', ' ', $e['description']), 0, 200);
      echo "    {$desc}\n";
    }
  }
  exit(1);
}

if (!empty($unexpectedWarnings)) {
  echo "\n[WARN] Unexpected warnings:\n";
  foreach ($unexpectedWarnings as $w) {
    echo "  - [{$w['key']}] {$w['title']}: {$w['value']}\n";
  }
  echo "\n  To baseline a warning, add its key to \$expectedWarnings in this script.\n";
  exit(2);
}

if (!empty($baselineWarnings)) {
  echo "\n  Baselined warnings (expected): " . implode(', ', $baselineWarnings) . "\n";
}

echo "\n[PASS] No errors, no unexpected warnings.\n";
exit(0);
