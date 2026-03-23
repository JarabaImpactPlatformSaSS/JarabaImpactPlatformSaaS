<?php

/**
 * @file
 * ROUTE-SUBSCRIBER-PARITY-001: Validates CaseStudyRouteSubscriber covers all legacy routes.
 *
 * Ensures that every legacy case study route defined in vertical modules'
 * routing.yml files is covered by CaseStudyRouteSubscriber::LEGACY_ROUTES.
 * If a new vertical adds a case study route without updating the subscriber,
 * users will see the old hardcoded template instead of the unified one.
 *
 * Usage: php scripts/validation/validate-route-subscriber-parity.php
 */

$basePath = __DIR__ . '/../../web/modules/custom';
$subscriberPath = $basePath . '/jaraba_success_cases/src/Routing/CaseStudyRouteSubscriber.php';

$errors = [];
$checks = 0;

echo "ROUTE-SUBSCRIBER-PARITY-001: Validating route subscriber covers all legacy routes...\n\n";

// === Step 1: Extract route names from CaseStudyRouteSubscriber::LEGACY_ROUTES ===
if (!file_exists($subscriberPath)) {
  echo "  [FAIL] CaseStudyRouteSubscriber.php not found\n";
  exit(1);
}

$subscriberContent = file_get_contents($subscriberPath);
preg_match_all("/['\"]([a-z_]+\.case_study\.[a-z_]+)['\"]/", $subscriberContent, $subscriberMatches);
$coveredRoutes = array_unique($subscriberMatches[1]);

echo "  Subscriber covers " . count($coveredRoutes) . " routes:\n";
foreach ($coveredRoutes as $route) {
  echo "    - $route\n";
}
echo "\n";

// === Step 2: Find all .case_study. routes in vertical modules' routing.yml ===
$routingFiles = glob($basePath . '/*/jaraba_*.routing.yml') +
                glob($basePath . '/*/*.routing.yml');

$legacyRoutes = [];
foreach ($routingFiles as $file) {
  // Skip jaraba_success_cases own routing (has the unified route).
  if (str_contains($file, 'jaraba_success_cases')) {
    continue;
  }

  $content = file_get_contents($file);
  // Match route names containing .case_study.
  preg_match_all('/^([a-z_]+\.case_study\.[a-z_]+)\s*:/m', $content, $matches);
  foreach ($matches[1] as $routeName) {
    $module = basename(dirname($file));
    $legacyRoutes[$routeName] = $module;
  }

  // Also check for caso-de-exito paths without .case_study. naming.
  preg_match_all("/^([a-z_]+[a-z_.]+):\s*\n\s+path:\s*['\"]?\/[^\/]+\/caso-de-exito\//m", $content, $altMatches);
  foreach ($altMatches[1] as $routeName) {
    if (!str_contains($routeName, 'case_study')) {
      $module = basename(dirname($file));
      $legacyRoutes[$routeName] = $module . ' (path-based, no .case_study. naming)';
    }
  }
}

echo "  Legacy routes found in vertical modules: " . count($legacyRoutes) . "\n";

// === Step 3: Compare ===
$uncovered = [];
foreach ($legacyRoutes as $routeName => $module) {
  $checks++;
  if (in_array($routeName, $coveredRoutes)) {
    echo "  [PASS] $routeName ($module)\n";
  }
  else {
    $uncovered[] = "$routeName ($module)";
    $errors[] = "Route $routeName from $module not covered by CaseStudyRouteSubscriber";
    echo "  [FAIL] $routeName ($module) — NOT in subscriber\n";
  }
}

// Check for orphan entries in subscriber (route in subscriber but no longer in any module).
$orphans = [];
foreach ($coveredRoutes as $route) {
  if (!isset($legacyRoutes[$route])) {
    $orphans[] = $route;
  }
}
if (!empty($orphans)) {
  echo "\n  [WARN] Orphan routes in subscriber (no matching routing.yml):\n";
  foreach ($orphans as $o) {
    echo "    - $o\n";
  }
}

// === Summary ===
echo "\n";
if (empty($errors)) {
  echo "ROUTE-SUBSCRIBER-PARITY-001: PASS — All " . count($legacyRoutes) . " legacy routes covered\n";
  exit(0);
}
else {
  echo "ROUTE-SUBSCRIBER-PARITY-001: FAIL — " . count($errors) . " uncovered routes\n";
  echo "Fix: Add missing routes to CaseStudyRouteSubscriber::LEGACY_ROUTES\n";
  exit(1);
}
