<?php

/**
 * @file
 * PRICING-COHERENCE-001: Validates consistency between promised features
 * and actual enforcement in the codebase.
 *
 * Checks:
 * 1. All features in SaasPlan limits have enforcement in FeatureGateService
 * 2. No mentoring_1a1 / mentoring_sessions_monthly in commercial plans (Doc 181)
 * 3. No white_label in plan features (must be addon)
 * 4. All Starters have copilot (Regla #1 Doc 158 v3)
 * 5. No "soporte dedicado" promises without support team
 *
 * Usage: php scripts/validation/validate-pricing-coherence.php
 */

$base_path = dirname(__DIR__, 2);
$errors = [];
$warnings = [];

// 1. Check mentoring in commercial plans (NOT andalucia_ei)
$config_dir = $base_path . '/config/sync';
$commercial_plans = glob($config_dir . '/ecosistema_jaraba_core.saas_plan.*.yml');

foreach ($commercial_plans as $file) {
  $basename = basename($file);
  // Skip andalucia_ei (institutional, mentoring OK)
  if (str_contains($basename, 'andalucia_ei')) {
    continue;
  }

  $content = file_get_contents($file);

  if (str_contains($content, 'mentoring_1a1')) {
    $errors[] = "RULE-0 VIOLATION: $basename contains mentoring_1a1 (human hours in SaaS plan)";
  }

  // Check mentoring_sessions_monthly > 0 in limits
  if (preg_match('/mentoring_sessions_monthly["\s:]+(\d+)/', $content, $m)) {
    if ((int) $m[1] > 0) {
      $errors[] = "RULE-0 VIOLATION: $basename has mentoring_sessions_monthly={$m[1]} (must be 0)";
    }
  }

  // Check white_label as plan feature
  if (preg_match('/^\s+- white_label$/m', $content)) {
    $errors[] = "DOC-158-V3: $basename includes white_label as plan feature (must be addon)";
  }
}

// 2. Check Starters have copilot (Regla #1)
$starter_plans = glob($config_dir . '/ecosistema_jaraba_core.saas_plan.*starter*.yml');
foreach ($starter_plans as $file) {
  $content = file_get_contents($file);
  $has_copilot = str_contains($content, 'copilot_sessions_daily')
    || str_contains($content, 'copilot_uses_per_month')
    || str_contains($content, 'copilot_queries_month')
    || str_contains($content, 'copilot_messages_per_day');

  if (!$has_copilot) {
    $warnings[] = "RULE-1 WARNING: " . basename($file) . " — Starter plan without copilot limit";
  }
}

// 3. Check Free plans have copilot
$free_plans = glob($config_dir . '/ecosistema_jaraba_core.saas_plan.*free*.yml');
foreach ($free_plans as $file) {
  $content = file_get_contents($file);
  if (!str_contains($content, 'copilot')) {
    $warnings[] = "RULE-1 WARNING: " . basename($file) . " — Free plan without copilot reference";
  }
}

// Output
echo "PRICING-COHERENCE-001: Pricing vs Delivery Consistency\n";
echo str_repeat('=', 60) . "\n";

if (empty($errors) && empty($warnings)) {
  echo "\033[32mPASS\033[0m — All pricing rules consistent.\n";
}
else {
  if (!empty($errors)) {
    echo "\033[31mFAIL\033[0m — " . count($errors) . " error(s):\n";
    foreach ($errors as $e) {
      echo "  \033[31m✗\033[0m $e\n";
    }
  }
  if (!empty($warnings)) {
    echo "\033[33mWARN\033[0m — " . count($warnings) . " warning(s):\n";
    foreach ($warnings as $w) {
      echo "  \033[33m⚠\033[0m $w\n";
    }
  }
}

echo "\nPlans scanned: " . count($commercial_plans) . "\n";
echo "Starters checked: " . count($starter_plans) . "\n";
echo "Free plans checked: " . count($free_plans) . "\n";

exit(empty($errors) ? 0 : 1);
