#!/usr/bin/env php
<?php

/**
 * @file
 * HOOK-REQUIREMENTS-COVERAGE-001: Verifica porcentaje de modulos custom
 * con hook_requirements(). Target: 85%.
 *
 * Exit codes: 0 = OK, 1 = below threshold
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$modulesDir = $root . '/web/modules/custom';
$threshold = 85;

$total = 0;
$withRequirements = 0;
$missing = [];

$moduleInfoFiles = glob($modulesDir . '/*/*.info.yml');

foreach ($moduleInfoFiles as $infoFile) {
  $moduleDir = dirname($infoFile);
  $moduleName = basename($moduleDir);

  if (strpos($moduleName, '_test') !== false) {
    continue;
  }

  $hasModule = file_exists($moduleDir . '/' . $moduleName . '.module');
  $hasInstall = file_exists($moduleDir . '/' . $moduleName . '.install');
  $hasSrc = is_dir($moduleDir . '/src');

  if (!$hasModule && !$hasInstall && !$hasSrc) {
    continue;
  }

  if ($hasSrc) {
    $phpFiles = glob($moduleDir . '/src/{Service,Entity,Controller}/*.php', GLOB_BRACE);
    if (empty($phpFiles)) {
      continue;
    }
  }

  $total++;

  if ($hasInstall) {
    $installContent = file_get_contents($moduleDir . '/' . $moduleName . '.install');
    $hookName = $moduleName . '_requirements';
    if (strpos($installContent, "function $hookName") !== false) {
      $withRequirements++;
      continue;
    }
  }

  if ($hasModule) {
    $moduleContent = file_get_contents($moduleDir . '/' . $moduleName . '.module');
    $hookName = $moduleName . '_requirements';
    if (strpos($moduleContent, "function $hookName") !== false) {
      $withRequirements++;
      continue;
    }
  }

  $missing[] = $moduleName;
}

$percentage = $total > 0 ? round(($withRequirements / $total) * 100, 1) : 0;

echo "Modules with hook_requirements: $withRequirements / $total ($percentage%)\n";
echo "Threshold: $threshold%\n";

if (!empty($missing) && count($missing) <= 20) {
  echo "\nMissing hook_requirements:\n";
  foreach ($missing as $m) {
    echo "  - $m\n";
  }
}

echo "\n";
if ($percentage < $threshold) {
  echo "FAIL: Coverage $percentage% below threshold $threshold%\n";
  exit(1);
}

echo "PASS: HOOK-REQUIREMENTS-COVERAGE-001 — $percentage% coverage (threshold $threshold%)\n";
exit(0);
