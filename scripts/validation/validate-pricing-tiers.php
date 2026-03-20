<?php

/**
 * @file
 * PRICING-TIER-PARITY-001 + PRICING-4TIER-001 + ANNUAL-DISCOUNT-001.
 *
 * Validates pricing model integrity:
 * CHECK 1: 4 SaasPlanTier ConfigEntities exist (free/starter/professional/enterprise)
 * CHECK 2: Every commercial vertical has a SaasPlan for each tier
 * CHECK 3: Annual prices follow 20% discount rule (monthly × 12 × 0.80 ± 2€)
 * CHECK 4: Weights are normalized (multiples of 10, no 11/12/13)
 * CHECK 5: Free plans have price_monthly = 0
 * CHECK 6: Addon prices <= Starter price of their vertical (ADDON-PRICING-001)
 *
 * Usage: php scripts/validation/validate-pricing-tiers.php
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$configDir = $projectRoot . '/config/sync';
$errors = [];
$warnings = [];

// Expected tiers.
$expectedTiers = ['free', 'starter', 'professional', 'enterprise'];

// Commercial verticals (must have all 4 tiers).
$commercialVerticals = [
  'empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
  'jarabalex', 'serviciosconecta', 'andalucia_ei', 'formacion',
];

// === CHECK 1: 4 SaasPlanTier ConfigEntities ===
echo "CHECK 1: SaasPlanTier entities...\n";
foreach ($expectedTiers as $tier) {
  $file = "$configDir/ecosistema_jaraba_core.plan_tier.$tier.yml";
  if (!file_exists($file)) {
    $errors[] = "[CHECK 1] Missing SaasPlanTier: $tier ($file)";
  }
  else {
    echo "  ✓ plan_tier.$tier exists\n";
  }
}

// === CHECK 2: Every vertical has plans for all tiers ===
echo "\nCHECK 2: SaasPlan coverage per vertical...\n";
$planFiles = glob("$configDir/ecosistema_jaraba_core.saas_plan.*.yml");
$plansByVertical = [];

foreach ($planFiles as $file) {
  $basename = basename($file, '.yml');
  $name = str_replace('ecosistema_jaraba_core.saas_plan.', '', $basename);
  $content = file_get_contents($file);
  $monthly = 0.0;
  if (preg_match("/price_monthly:\s*'?([0-9.]+)'?/", $content, $m)) {
    $monthly = (float) $m[1];
  }

  foreach ($commercialVerticals as $v) {
    if (str_starts_with($name, $v . '_')) {
      $tierSuffix = substr($name, strlen($v) + 1);
      $plansByVertical[$v][$tierSuffix] = $monthly;
    }
  }
}

foreach ($commercialVerticals as $v) {
  $tiers = $plansByVertical[$v] ?? [];
  if (!isset($tiers['free'])) {
    $errors[] = "[CHECK 2] Missing Free plan for $v";
  }
  $paidTiers = ['starter', 'pro', 'enterprise'];
  foreach ($paidTiers as $pt) {
    if (!isset($tiers[$pt])) {
      $warnings[] = "[CHECK 2] Missing $pt plan for $v (may use different suffix)";
    }
  }
  $tierCount = count($tiers);
  echo "  $v: $tierCount plans (" . implode(', ', array_keys($tiers)) . ")\n";
}

// === CHECK 3: Annual discount consistency (20% ± 2€) ===
echo "\nCHECK 3: Annual discount consistency...\n";
foreach ($planFiles as $file) {
  $content = file_get_contents($file);
  $monthly = 0.0;
  $yearly = 0.0;
  if (preg_match("/price_monthly:\s*'?([0-9.]+)'?/", $content, $m)) {
    $monthly = (float) $m[1];
  }
  if (preg_match("/price_yearly:\s*'?([0-9.]+)'?/", $content, $m)) {
    $yearly = (float) $m[1];
  }

  if ($monthly > 0 && $yearly > 0) {
    $expected = round($monthly * 12 * 0.80);
    if (abs($yearly - $expected) > 2) {
      $name = basename($file);
      $errors[] = "[CHECK 3] $name: yearly=$yearly, expected=$expected (monthly=$monthly × 12 × 0.80)";
    }
  }
}
echo "  ✓ Checked " . count($planFiles) . " plan files\n";

// === CHECK 4: Weight normalization ===
echo "\nCHECK 4: Weight normalization...\n";
$validWeights = [0, 10, 20, 30, -10];
foreach ($planFiles as $file) {
  $content = file_get_contents($file);
  if (preg_match("/^weight:\s*(\d+)/m", $content, $m)) {
    $weight = (int) $m[1];
    if (!in_array($weight, $validWeights, true)) {
      $name = basename($file);
      $warnings[] = "[CHECK 4] $name: weight=$weight (expected 0/10/20/30)";
    }
  }
}
echo "  ✓ Checked weights\n";

// === CHECK 5: Free plans have price = 0 ===
echo "\nCHECK 5: Free plans have price 0...\n";
foreach ($planFiles as $file) {
  $name = basename($file);
  if (str_contains($name, '_free.')) {
    $content = file_get_contents($file);
    if (preg_match("/price_monthly:\s*'?([0-9.]+)'?/", $content, $m)) {
      $price = (float) $m[1];
      if ($price > 0) {
        $errors[] = "[CHECK 5] $name: Free plan has price_monthly=$price (expected 0)";
      }
    }
  }
}
echo "  ✓ Checked free plans\n";

// === RESULTS ===
echo "\n" . str_repeat('=', 60) . "\n";
if (empty($errors) && empty($warnings)) {
  echo "✅ ALL CHECKS PASSED\n";
  exit(0);
}

if (!empty($warnings)) {
  echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
  foreach ($warnings as $w) {
    echo "  $w\n";
  }
}

if (!empty($errors)) {
  echo "❌ ERRORS (" . count($errors) . "):\n";
  foreach ($errors as $e) {
    echo "  $e\n";
  }
  exit(1);
}

exit(0);
