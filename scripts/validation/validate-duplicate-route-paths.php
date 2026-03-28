<?php

/**
 * @file
 * ROUTE-DUP-CROSSMODULE-001: Detect duplicate HTTP paths across modules.
 *
 * Scans all jaraba_*.routing.yml files and detects when two different modules
 * register the same HTTP path. This catches issues like two modules both
 * defining /api/v1/whatsapp/webhook.
 *
 * Same-module duplicates are ignored (Drupal handles those via route names).
 *
 * Usage: php scripts/validation/validate-duplicate-route-paths.php
 * Exit:  0 = clean, 1 = duplicates found
 */

declare(strict_types=1);

$basePath = dirname(__DIR__, 2);
$pass = 0;
$fail = 0;
$warn = 0;
$checks = 0;

function check(string $label, bool $result, string $detail = '', bool $isWarn = false): void {
  global $pass, $fail, $warn, $checks;
  $checks++;
  if ($result) {
    $pass++;
    echo "  \033[32mPASS\033[0m $label\n";
  }
  elseif ($isWarn) {
    $warn++;
    echo "  \033[33mWARN\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
  else {
    $fail++;
    echo "  \033[31mFAIL\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
}

echo "\n\033[1m[ROUTE-DUP-CROSSMODULE-001]\033[0m Duplicate route path detection across modules\n\n";

// Collect all routing.yml files from custom modules.
$routingFiles = glob("$basePath/web/modules/custom/*/jaraba_*.routing.yml");
if ($routingFiles === false) {
  $routingFiles = [];
}

// Also pick up routing files in subdirectories (submodules).
$submoduleRouting = glob("$basePath/web/modules/custom/*/modules/*/jaraba_*.routing.yml");
if ($submoduleRouting) {
  $routingFiles = array_merge($routingFiles, $submoduleRouting);
}

// Also check ecosistema_jaraba_core routing files.
$coreRouting = glob("$basePath/web/modules/custom/*/ecosistema_jaraba_*.routing.yml");
if ($coreRouting) {
  $routingFiles = array_merge($routingFiles, $coreRouting);
}

$routingFiles = array_unique($routingFiles);

// Map: path => [ module_name => [route_names] ].
$pathMap = [];
$totalRoutes = 0;
$modulesScanned = 0;

foreach ($routingFiles as $file) {
  $filename = basename($file);
  // Extract module name from filename (e.g., jaraba_foo.routing.yml -> jaraba_foo).
  $moduleName = preg_replace('/\.routing\.yml$/', '', $filename);

  $content = file_get_contents($file);
  if ($content === false) {
    continue;
  }

  $modulesScanned++;

  // Parse route definitions: top-level keys (route names) and their path values.
  $lines = explode("\n", $content);
  $currentRoute = null;

  foreach ($lines as $line) {
    // Route name: top-level key (no leading whitespace, ends with colon).
    if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_.]+):\s*$/', $line, $m)) {
      $currentRoute = $m[1];
      continue;
    }

    // Path value within a route definition.
    if ($currentRoute && preg_match('/^\s+path:\s*[\'"]?([^\'"#\n]+)/m', $line, $m)) {
      $path = trim($m[1]);
      // Normalize: remove trailing slash if present (except for root /).
      if ($path !== '/' && str_ends_with($path, '/')) {
        $path = rtrim($path, '/');
      }

      if (!isset($pathMap[$path])) {
        $pathMap[$path] = [];
      }
      if (!isset($pathMap[$path][$moduleName])) {
        $pathMap[$path][$moduleName] = [];
      }
      $pathMap[$path][$moduleName][] = $currentRoute;
      $totalRoutes++;
      $currentRoute = null;
      continue;
    }
  }
}

echo "  Modules scanned: $modulesScanned\n";
echo "  Routes scanned: $totalRoutes\n\n";

// Detect cross-module duplicates.
$duplicates = 0;
foreach ($pathMap as $path => $modules) {
  if (count($modules) < 2) {
    continue;
  }

  // Multiple modules define the same path.
  $moduleList = [];
  foreach ($modules as $mod => $routes) {
    $routeNames = implode(', ', $routes);
    $moduleList[] = "$mod ($routeNames)";
  }
  $detail = implode(' vs ', $moduleList);

  check(
    "Path $path is unique across modules",
    false,
    "Defined in: $detail"
  );
  $duplicates++;
}

if ($duplicates === 0) {
  check('No cross-module duplicate paths detected', true);
}

echo "\n============================================================\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed, $warn warnings (of $checks)\n";
echo "============================================================\n";

exit($fail > 0 ? 1 : 0);
