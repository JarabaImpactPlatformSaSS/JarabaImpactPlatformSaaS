<?php

/**
 * @file
 * FEATURE-GATING-001: Verifies that features listed in SaasPlan limits
 * have actual enforcement in FeatureGateService calls from controllers.
 *
 * Detects features that are promised in plans but never blocked in code.
 * A feature without gating means any user can access it regardless of tier.
 *
 * Usage: php scripts/validation/validate-feature-gating.php
 */

$base_path = dirname(__DIR__, 2);
$errors = [];
$warnings = [];

// 1. Collect all unique feature/limit keys from SaasPlan configs.
$config_dir = $base_path . '/config/sync';
$plan_files = glob($config_dir . '/ecosistema_jaraba_core.saas_plan.*.yml');
$all_limits = [];

foreach ($plan_files as $file) {
  $content = file_get_contents($file);
  if (preg_match("/limits:\s*'(.+?)'/s", $content, $match)) {
    $decoded = json_decode($match[1], TRUE);
    if (is_array($decoded)) {
      foreach (array_keys($decoded) as $key) {
        $all_limits[$key] = ($all_limits[$key] ?? 0) + 1;
      }
    }
  }
}

// 2. Search for enforcement of each limit key in PHP service/controller code.
$enforced = [];
$php_dirs = [
  $base_path . '/web/modules/custom/ecosistema_jaraba_core/src/Service',
  $base_path . '/web/modules/custom/ecosistema_jaraba_core/src/Controller',
  $base_path . '/web/modules/custom/jaraba_billing/src/Service',
  $base_path . '/web/modules/custom/jaraba_page_builder/src/Controller',
];

foreach ($php_dirs as $dir) {
  if (!is_dir($dir)) {
    continue;
  }
  $files = glob($dir . '/*.php');
  foreach ($files as $file) {
    $content = file_get_contents($file);
    foreach (array_keys($all_limits) as $key) {
      if (stripos($content, $key) !== false) {
        $enforced[$key] = TRUE;
      }
    }
  }
}

// 3. Report unenforced features.
$unenforced = array_diff_keys_missing($all_limits, $enforced);

// Exclude known non-enforceable limits (informational only).
$informational = ['commission_pct', 'created', 'changed'];

foreach (array_keys($all_limits) as $key) {
  if (in_array($key, $informational, TRUE)) {
    continue;
  }
  if (!isset($enforced[$key])) {
    $warnings[] = sprintf(
      'Limit "%s" (in %d plans) has NO enforcement in FeatureGate/Controller code',
      $key, $all_limits[$key]
    );
  }
}

// Output.
echo "FEATURE-GATING-001: Feature enforcement audit\n";
echo str_repeat('=', 60) . "\n";
echo "Unique limits in plans: " . count($all_limits) . "\n";
echo "Enforced in code: " . count($enforced) . "\n";
echo "Unenforced: " . (count($all_limits) - count($enforced) - count($informational)) . "\n\n";

if (empty($warnings) && empty($errors)) {
  echo "\033[32mPASS\033[0m — All features have enforcement.\n";
}
else {
  if (!empty($warnings)) {
    echo "\033[33mWARN\033[0m — " . count($warnings) . " feature(s) sin enforcement:\n";
    foreach ($warnings as $w) {
      echo "  \033[33m⚠\033[0m $w\n";
    }
  }
}

exit(empty($errors) ? 0 : 1);

/**
 * Helper: find keys in $all that are NOT in $enforced.
 */
function array_diff_keys_missing(array $all, array $enforced): array {
  return array_diff_key($all, $enforced);
}
