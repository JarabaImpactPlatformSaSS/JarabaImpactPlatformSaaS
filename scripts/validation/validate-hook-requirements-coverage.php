<?php

/**
 * @file
 * HOOK-REQUIREMENTS-COVERAGE-001: Detect modules without hook_requirements().
 *
 * Every custom module with entities, services, or config should implement
 * hook_requirements() for runtime self-checks in /admin/reports/status.
 *
 * Usage: php scripts/validation/validate-hook-requirements-coverage.php
 * Exit:  0 always (coverage metric, not blocking)
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

// Hardcoded whitelist — deprecated or special-case modules.
$whitelist = ['jaraba_blog'];

$covered = [];
$missing = [];
$skippedTrivial = 0;
$skippedHidden = 0;
$skippedSubmodule = 0;

// Collect top-level module directories only.
$topLevelDirs = [];
foreach (new DirectoryIterator($modulesDir) as $item) {
  if ($item->isDot() || !$item->isDir()) {
    continue;
  }
  $topLevelDirs[] = $item->getPathname();
}

// Also find submodule directories (to detect and skip them).
$submodulePaths = [];
foreach ($topLevelDirs as $dir) {
  $modulesSubdir = $dir . '/modules';
  if (!is_dir($modulesSubdir)) {
    continue;
  }
  foreach (new DirectoryIterator($modulesSubdir) as $sub) {
    if ($sub->isDot() || !$sub->isDir()) {
      continue;
    }
    $submodulePaths[] = $sub->getPathname();
  }
}

// Merge all module paths for scanning.
$allModulePaths = array_merge($topLevelDirs, $submodulePaths);

foreach ($allModulePaths as $modulePath) {
  $moduleName = basename($modulePath);

  // Skip whitelisted.
  if (in_array($moduleName, $whitelist, true)) {
    continue;
  }

  // Detect submodules (path contains /modules/ segment after custom/).
  $relative = str_replace($modulesDir . '/', '', $modulePath);
  if (str_contains($relative, '/modules/')) {
    $skippedSubmodule++;
    continue;
  }

  // Check hidden flag in .info.yml.
  $infoFile = $modulePath . '/' . $moduleName . '.info.yml';
  if (is_file($infoFile)) {
    $infoContent = file_get_contents($infoFile);
    if ($infoContent !== false && preg_match('/^hidden:\s*true$/mi', $infoContent)) {
      $skippedHidden++;
      continue;
    }
  }

  // Skip trivially small modules (no src/, no services.yml, no templates/).
  $hasSrc = is_dir($modulePath . '/src');
  $hasServices = is_file($modulePath . '/' . $moduleName . '.services.yml');
  $hasTemplates = is_dir($modulePath . '/templates');
  if (!$hasSrc && !$hasServices && !$hasTemplates) {
    $skippedTrivial++;
    continue;
  }

  // Check for hook_requirements() in .install file.
  $installFile = $modulePath . '/' . $moduleName . '.install';
  $hasRequirements = false;
  if (is_file($installFile)) {
    $installContent = file_get_contents($installFile);
    if ($installContent !== false) {
      $funcName = $moduleName . '_requirements';
      $hasRequirements = str_contains($installContent, "function $funcName(");
    }
  }

  if ($hasRequirements) {
    $covered[] = $moduleName;
  } else {
    $missing[] = $moduleName;
  }
}

// Output.
$total = count($covered) + count($missing);
$pct = $total > 0 ? round(count($covered) / $total * 100) : 0;

echo "=== HOOK-REQUIREMENTS-COVERAGE-001 ===\n";
echo "Custom modules with hook_requirements(): " . count($covered) . "/$total ($pct%)\n";
echo "Skipped: $skippedTrivial trivial, $skippedHidden hidden, $skippedSubmodule submodules\n\n";

if ($missing !== []) {
  sort($missing);
  echo "WARNING: Modules without hook_requirements():\n";
  foreach ($missing as $mod) {
    echo "  - $mod\n";
  }
  echo "\n";
}

if ($covered !== []) {
  sort($covered);
  echo "Covered modules:\n";
  foreach ($covered as $mod) {
    echo "  ✓ $mod\n";
  }
  echo "\n";
}

echo "Result: " . ($pct >= 95 ? "EXCELLENT" : ($pct >= 80 ? "GOOD" : "NEEDS IMPROVEMENT")) . " ($pct% coverage)\n";

exit(0);
