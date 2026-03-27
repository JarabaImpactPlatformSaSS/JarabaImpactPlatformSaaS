<?php

/**
 * @file
 * TEST-COVERAGE-MAP-001: Validate that critical modules have test coverage.
 *
 * Checks that modules with routes, entities, or services have corresponding
 * test directories (Functional, Kernel, Unit) appropriate to their complexity.
 *
 * Rules:
 * - Modules with >10 routes MUST have tests/src/Functional/
 * - Modules with entities MUST have tests/src/Kernel/
 * - Modules with services MUST have tests/src/Unit/
 *
 * Usage: php scripts/validation/validate-test-coverage-map.php
 * Exit:  0 = covered, 1 = gaps found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$deprecatedModules = ['jaraba_blog'];
$gaps = [];
$checked = 0;

// ─────────────────────────────────────────────────────────────
// Scan each module.
// ─────────────────────────────────────────────────────────────
$modules = glob("$modulesDir/*", GLOB_ONLYDIR) ?: [];

foreach ($modules as $moduleDir) {
  $moduleName = basename($moduleDir);

  if (in_array($moduleName, $deprecatedModules, TRUE)) {
    continue;
  }

  $checked++;

  // Count routes.
  $routeCount = 0;
  $routingFiles = glob("$moduleDir/*.routing.yml") ?: [];
  foreach ($routingFiles as $rf) {
    $content = file_get_contents($rf);
    if ($content === FALSE) {
      continue;
    }
    // Count route definitions (lines matching "route.name:" at start).
    $routeCount += preg_match_all('/^[a-zA-Z_][a-zA-Z0-9_.]+:\s*$/m', $content);
  }

  // Check for entities.
  $entityFiles = glob("$moduleDir/src/Entity/*.php") ?: [];
  $hasEntities = FALSE;
  foreach ($entityFiles as $ef) {
    $content = file_get_contents($ef);
    if ($content !== FALSE && (str_contains($content, '@ContentEntityType') || str_contains($content, '@ConfigEntityType'))) {
      $hasEntities = TRUE;
      break;
    }
  }

  // Check for services.
  $serviceFiles = glob("$moduleDir/src/Service/*.php") ?: [];
  $hasServices = count($serviceFiles) > 0;

  // Check existing test directories.
  $hasFunctional = is_dir("$moduleDir/tests/src/Functional");
  $hasKernel = is_dir("$moduleDir/tests/src/Kernel");
  $hasUnit = is_dir("$moduleDir/tests/src/Unit");

  // Rule 1: Modules with >10 routes should have Functional tests.
  if ($routeCount > 10 && !$hasFunctional) {
    $gaps[] = [
      'module' => $moduleName,
      'rule' => 'FUNCTIONAL',
      'message' => "$routeCount routes but no tests/src/Functional/ directory",
    ];
  }

  // Rule 2: Modules with entities should have Kernel tests.
  if ($hasEntities && !$hasKernel) {
    $gaps[] = [
      'module' => $moduleName,
      'rule' => 'KERNEL',
      'message' => "Has entity definitions but no tests/src/Kernel/ directory",
    ];
  }

  // Rule 3: Modules with services should have Unit tests.
  if ($hasServices && !$hasUnit) {
    $gaps[] = [
      'module' => $moduleName,
      'rule' => 'UNIT',
      'message' => count($serviceFiles) . " service class(es) but no tests/src/Unit/ directory",
    ];
  }
}

// ─────────────────────────────────────────────────────────────
// Output.
// ─────────────────────────────────────────────────────────────
echo "\n";
echo "=== TEST-COVERAGE-MAP-001: Test coverage map ===\n";
echo "  Checked: $checked modules\n";
echo "\n";

if (!empty($gaps)) {
  // Group by module.
  $byModule = [];
  foreach ($gaps as $g) {
    $byModule[$g['module']][] = $g;
  }

  echo "  [FAIL] Modules missing test coverage:\n";
  foreach ($byModule as $module => $items) {
    foreach ($items as $item) {
      echo "  [FAIL] $module: [{$item['rule']}] {$item['message']}\n";
    }
  }
  echo "\n";
  echo "  " . count($gaps) . " test coverage gap(s) in " . count($byModule) . " module(s).\n";
  echo "\n";
  exit(1);
}

echo "  OK: All critical modules have test coverage.\n";
echo "\n";
exit(0);
