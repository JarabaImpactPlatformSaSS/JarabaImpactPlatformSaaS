<?php

/**
 * @file
 * SAFEGUARD-PLG-COVERAGE-001: PLG trigger coverage validator.
 *
 * Verifies that FreemiumVerticalLimit has rules for key features
 * that drive plan upgrades, specifically Page Builder limits.
 *
 * Usage: php scripts/validation/validate-plg-triggers.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  SAFEGUARD-PLG-COVERAGE-001                            ║\n";
echo "║  PLG Trigger Coverage Validator                        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ── CHECK 1: FreemiumVerticalLimit config files exist ────────────────
$config_dir = $root . '/config/install';
$config_sync_dir = $root . '/config/sync';
$module_config_dir = $root . '/web/modules/custom/ecosistema_jaraba_core/config/install';

// Check all possible locations.
$search_dirs = [];
if (is_dir($config_dir)) {
  $search_dirs[] = $config_dir;
}
if (is_dir($config_sync_dir)) {
  $search_dirs[] = $config_sync_dir;
}
if (is_dir($module_config_dir)) {
  $search_dirs[] = $module_config_dir;
}

$limit_files = [];
foreach ($search_dirs as $dir) {
  $files = glob($dir . '/ecosistema_jaraba_core.freemium_vertical_limit.*.yml');
  if (is_array($files)) {
    $limit_files = array_merge($limit_files, $files);
  }
}

$total_limits = count($limit_files);
echo "  Found $total_limits FreemiumVerticalLimit config files\n\n";

if ($total_limits === 0) {
  $warnings[] = "CHECK 1 WARN: No FreemiumVerticalLimit config files found";
} else {
  $passes[] = "CHECK 1 PASS: $total_limits FreemiumVerticalLimit config files found";
}

// ── CHECK 2: Page Builder related limits ─────────────────────────────
$pb_features = ['max_pages', 'premium_blocks', 'page_builder'];
$pb_limit_count = 0;

foreach ($limit_files as $file) {
  $content = file_get_contents($file);
  foreach ($pb_features as $feature) {
    if (str_contains($content, $feature)) {
      $pb_limit_count++;
      break;
    }
  }
}

if ($pb_limit_count >= 9) {
  $passes[] = "CHECK 2 PASS: $pb_limit_count Page Builder FreemiumVerticalLimit rules (>= 9)";
} elseif ($pb_limit_count > 0) {
  $warnings[] = "CHECK 2 WARN: Only $pb_limit_count Page Builder rules (recommended >= 9 for 9 commercial verticals)";
} else {
  $errors[] = "CHECK 2 FAIL: 0 Page Builder FreemiumVerticalLimit rules — PLG blind to Page Builder usage";
}

// ── CHECK 3: Verticals with limits ───────────────────────────────────
$verticals = [
  'empleabilidad', 'emprendimiento', 'comercioconecta',
  'agroconecta', 'jarabalex', 'serviciosconecta',
  'formacion', 'andalucia_ei', 'jaraba_content_hub',
];

$verticals_with_limits = [];
foreach ($limit_files as $file) {
  $basename = basename($file);
  foreach ($verticals as $vertical) {
    if (str_contains($basename, $vertical)) {
      $verticals_with_limits[$vertical] = TRUE;
    }
  }
}

$covered = count($verticals_with_limits);
$total_verticals = count($verticals);

if ($covered >= $total_verticals) {
  $passes[] = "CHECK 3 PASS: All $total_verticals verticals have FreemiumVerticalLimit rules";
} elseif ($covered > 0) {
  $missing = array_diff($verticals, array_keys($verticals_with_limits));
  $warnings[] = "CHECK 3 WARN: $covered/$total_verticals verticals covered. Missing: " . implode(', ', $missing);
} else {
  $warnings[] = "CHECK 3 WARN: No vertical-specific limits found";
}

// ── CHECK 4: Upgrade message in QuotaManagerService ──────────────────
$quota_file = $root . '/web/modules/custom/jaraba_page_builder/src/Service/QuotaManagerService.php';
if (file_exists($quota_file)) {
  $quota_content = file_get_contents($quota_file);

  if (str_contains($quota_content, 'upgrade') || str_contains($quota_content, 'upgrade_message')) {
    $passes[] = "CHECK 4 PASS: QuotaManagerService references upgrade data";
  } else {
    $warnings[] = "CHECK 4 WARN: QuotaManagerService may not return upgrade data when quota exceeded";
  }
} else {
  $errors[] = "CHECK 4 FAIL: QuotaManagerService.php not found";
}

// ── CHECK 5: Upgrade messages not empty ──────────────────────────────
$empty_messages = 0;
foreach ($limit_files as $file) {
  $content = file_get_contents($file);
  if (str_contains($content, "upgrade_message: ''") || str_contains($content, "upgrade_message: \"\"")) {
    $empty_messages++;
  }
}

if ($empty_messages > 0) {
  $warnings[] = "CHECK 5 WARN: $empty_messages FreemiumVerticalLimit rules have empty upgrade_message";
} else {
  $passes[] = "CHECK 5 PASS: All FreemiumVerticalLimit rules have non-empty upgrade_message";
}

// ── REPORT ───────────────────────────────────────────────────────────
echo "\n";
foreach ($passes as $p) {
  echo "  \033[32m✓\033[0m $p\n";
}
foreach ($warnings as $w) {
  echo "  \033[33m⚠\033[0m $w\n";
}
foreach ($errors as $e) {
  echo "  \033[31m✗\033[0m $e\n";
}

$total = count($passes) + count($errors);
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULT: " . count($passes) . "/$total PASS";
if (!empty($warnings)) {
  echo ", " . count($warnings) . " WARN";
}
if (!empty($errors)) {
  echo ", " . count($errors) . " FAIL";
}
echo "\n═══════════════════════════════════════════════════════════\n";

exit(empty($errors) ? 0 : 1);
