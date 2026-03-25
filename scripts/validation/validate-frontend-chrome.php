<?php

/**
 * @file
 * Validator: FRONTEND-CHROME-001 — Page template coverage for frontend routes.
 *
 * For each custom module with frontend routes (NOT /admin/*, NOT /api/*),
 * checks that hook_theme_suggestions_page_alter covers those routes
 * with a page template suggestion in the theme.
 *
 * Usage: php scripts/validation/validate-frontend-chrome.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$modulesDir = __DIR__ . '/../../web/modules/custom';
$themeFile = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';
$templatesDir = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/templates';

// Read the theme file content (hook_theme_suggestions_page_alter).
if (!file_exists($themeFile)) {
  echo "\n=== FRONTEND-CHROME-001 ===\n\n";
  echo "  ❌ FATAL: ecosistema_jaraba_theme.theme not found\n";
  echo "\n--- Score: 0/1 checks passed ---\n\n";
  exit(1);
}
$themeContent = file_get_contents($themeFile);

// Extract the $ecosistema_prefixes array from hook_theme_suggestions_page_alter.
// These route prefixes get page__dashboard as a catch-all fallback.
$catchAllPrefixes = [];
if (preg_match('/\$ecosistema_prefixes\s*=\s*\[(.*?)\];/s', $themeContent, $m)) {
  preg_match_all("/['\"]([^'\"]+)['\"]/", $m[1], $prefixMatches);
  $catchAllPrefixes = $prefixMatches[1] ?? [];
}

// Also find str_starts_with patterns for specific suggestions.
$strStartsWithPrefixes = [];
if (preg_match_all('/str_starts_with\(\(string\)\s*\$route(?:_str)?,\s*[\'"]([^\'"]+)[\'"]\)/', $themeContent, $swMatches)) {
  $strStartsWithPrefixes = $swMatches[1] ?? [];
}

// Find explicit route lists (in_array patterns) — extract route names.
$explicitRoutes = [];
if (preg_match_all("/['\"]([a-z_]+\.[a-z_.]+)['\"]/", $themeContent, $routeMatches)) {
  $explicitRoutes = $routeMatches[1] ?? [];
}

// Collect all page--*.html.twig templates.
$pageTemplates = [];
foreach (glob($templatesDir . '/page--*.html.twig') as $tpl) {
  $pageTemplates[] = basename($tpl);
}

// Scan routing.yml files and group frontend routes by module.
$routingFiles = glob($modulesDir . '/*/jaraba_*.routing.yml');
// Also check ecosistema_jaraba_core.
$coreRouting = glob($modulesDir . '/ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml');
if ($coreRouting) {
  $routingFiles = array_merge($routingFiles, $coreRouting);
}
$routingFiles = array_unique($routingFiles);

$moduleResults = [];

foreach ($routingFiles as $routingFile) {
  $moduleName = basename(dirname($routingFile));
  $content = file_get_contents($routingFile);
  if (empty($content)) {
    continue;
  }

  // Simple YAML parsing: find route definitions and their paths.
  $frontendRoutes = [];
  $lines = explode("\n", $content);
  $currentRoute = NULL;
  $currentPath = NULL;

  foreach ($lines as $line) {
    // Route definition (not indented, ends with :).
    if (preg_match('/^([a-z_][a-z0-9_.]*)\s*:/', $line, $routeMatch)) {
      // Save previous route if it was frontend.
      if ($currentRoute !== NULL && $currentPath !== NULL) {
        if (!str_starts_with($currentPath, '/admin/') && !str_starts_with($currentPath, '/api/')) {
          $frontendRoutes[$currentRoute] = $currentPath;
        }
      }
      $currentRoute = $routeMatch[1];
      $currentPath = NULL;
    }
    // Path line.
    if (preg_match("/^\s+path:\s*['\"]?([^'\"#\n]+)/", $line, $pathMatch)) {
      $currentPath = trim($pathMatch[1]);
    }
  }
  // Don't forget last route.
  if ($currentRoute !== NULL && $currentPath !== NULL) {
    if (!str_starts_with($currentPath, '/admin/') && !str_starts_with($currentPath, '/api/')) {
      $frontendRoutes[$currentRoute] = $currentPath;
    }
  }

  if (empty($frontendRoutes)) {
    continue;
  }

  // Determine the route prefix for this module.
  // e.g., jaraba_andalucia_ei → 'jaraba_andalucia_ei.'
  $routePrefix = $moduleName . '.';

  // Check coverage: is the prefix in $ecosistema_prefixes OR str_starts_with patterns?
  $coveredByCatchAll = in_array($routePrefix, $catchAllPrefixes, TRUE);
  $coveredByStrStartsWith = FALSE;
  foreach ($strStartsWithPrefixes as $swPrefix) {
    if ($routePrefix === $swPrefix || str_starts_with($routePrefix, $swPrefix)) {
      $coveredByStrStartsWith = TRUE;
      break;
    }
  }

  // Check if individual routes are listed explicitly.
  $explicitCoveredCount = 0;
  foreach (array_keys($frontendRoutes) as $routeName) {
    if (in_array($routeName, $explicitRoutes, TRUE)) {
      $explicitCoveredCount++;
    }
  }

  $totalFrontend = count($frontendRoutes);
  $isCovered = $coveredByCatchAll || $coveredByStrStartsWith || ($explicitCoveredCount > 0);

  // Determine the suggestion name for reporting.
  $suggestionName = 'page__dashboard (catch-all)';
  if ($coveredByStrStartsWith) {
    // Find which suggestion it maps to.
    $suggestionName = 'page__* (str_starts_with match)';
  }
  if ($explicitCoveredCount > 0 && !$coveredByCatchAll && !$coveredByStrStartsWith) {
    $suggestionName = "explicit routes ($explicitCoveredCount/$totalFrontend)";
  }

  $moduleResults[$moduleName] = [
    'frontend_count' => $totalFrontend,
    'covered' => $isCovered,
    'suggestion' => $suggestionName,
  ];
}

// Generate results.
foreach ($moduleResults as $moduleName => $result) {
  $routePrefix = $moduleName . '.*';
  if ($result['covered']) {
    $passes[] = "$routePrefix ({$result['frontend_count']} frontend routes) — covered by {$result['suggestion']}";
  }
  else {
    $errors[] = "$routePrefix ({$result['frontend_count']} frontend routes) — NO page template suggestion found";
  }
}

// RESULTS.
$total = count($errors) + count($passes);
echo "\n=== FRONTEND-CHROME-001 ===\n\n";
foreach ($passes as $msg) {
  echo "  ✅ $msg\n";
}
foreach ($errors as $msg) {
  echo "  ❌ $msg\n";
}
echo "\n--- Score: " . count($passes) . "/$total modules covered ---\n\n";
exit(empty($errors) ? 0 : 1);
