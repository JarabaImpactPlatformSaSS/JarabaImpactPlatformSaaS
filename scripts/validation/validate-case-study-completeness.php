<?php

/**
 * @file validate-case-study-completeness.php
 * CASE-STUDY-COMPLETENESS-001: Validates unified case study landing architecture.
 *
 * Updated 2026-03-26 for SUCCESS-CASES-001 unified architecture:
 * - Single CaseStudyLandingController handles all 9 verticals
 * - Single parametrized template case-study-landing.html.twig (15 sections)
 * - CaseStudyRouteSubscriber for legacy route defense-in-depth
 * - SuccessCase entity as SSOT (no hardcoded data)
 *
 * Usage: php scripts/validation/validate-case-study-completeness.php
 */

$errors = [];
$warnings = [];

$themePath = 'web/themes/custom/ecosistema_jaraba_theme';
$modulesPath = 'web/modules/custom';
$successCasesModule = "$modulesPath/jaraba_success_cases";

// 9 commercial verticals (demo excluded = internal).
$commercialVerticals = [
  'jarabalex', 'agroconecta', 'comercioconecta', 'empleabilidad',
  'emprendimiento', 'formacion', 'serviciosconecta', 'andalucia_ei', 'content_hub',
];

// Vertical path slugs used in URLs (with hyphens for multi-word).
$verticalPathSlugs = [
  'jarabalex', 'agroconecta', 'comercioconecta', 'empleabilidad',
  'emprendimiento', 'formacion', 'serviciosconecta', 'andalucia-ei', 'content-hub',
];

echo "CASE-STUDY-COMPLETENESS-001: Validating unified case study architecture...\n\n";

// CHECK 1: Unified CaseStudyLandingController exists.
$controllerPath = "$successCasesModule/src/Controller/CaseStudyLandingController.php";
if (file_exists($controllerPath)) {
  $controllerContent = file_get_contents($controllerPath);
  // Verify it handles all verticals.
  $missingVerticals = [];
  foreach ($verticalPathSlugs as $slug) {
    if (strpos($controllerContent, "'$slug'") === false) {
      $missingVerticals[] = $slug;
    }
  }
  if (empty($missingVerticals)) {
    echo "  [PASS] CHECK 1: CaseStudyLandingController handles all 9 verticals\n";
  } else {
    $errors[] = "CaseStudyLandingController missing verticals: " . implode(', ', $missingVerticals);
    echo "  [FAIL] CHECK 1: Missing verticals in controller: " . implode(', ', $missingVerticals) . "\n";
  }
} else {
  $errors[] = "Missing unified CaseStudyLandingController";
  echo "  [FAIL] CHECK 1: CaseStudyLandingController not found\n";
}

// CHECK 2: CaseStudyRouteSubscriber exists (defense-in-depth).
$subscriberPath = "$successCasesModule/src/Routing/CaseStudyRouteSubscriber.php";
if (file_exists($subscriberPath)) {
  echo "  [PASS] CHECK 2: CaseStudyRouteSubscriber exists\n";
} else {
  $warnings[] = "Missing CaseStudyRouteSubscriber (defense-in-depth layer)";
  echo "  [WARN] CHECK 2: CaseStudyRouteSubscriber not found\n";
}

// CHECK 3: Parametrized route covers all verticals.
$routingPath = "$successCasesModule/jaraba_success_cases.routing.yml";
if (file_exists($routingPath)) {
  $routingContent = file_get_contents($routingPath);
  $allSlugsInRoute = true;
  foreach ($verticalPathSlugs as $slug) {
    if (strpos($routingContent, $slug) === false) {
      $allSlugsInRoute = false;
      $errors[] = "Vertical path '$slug' not in routing.yml requirements";
    }
  }
  if ($allSlugsInRoute) {
    echo "  [PASS] CHECK 3: Parametrized route covers all 9 vertical paths\n";
  } else {
    echo "  [FAIL] CHECK 3: Some vertical paths missing from routing.yml\n";
  }
} else {
  $errors[] = "Missing jaraba_success_cases.routing.yml";
  echo "  [FAIL] CHECK 3: routing.yml not found\n";
}

// CHECK 4: Unified template exists with 15 partial includes.
$templatePath = "$themePath/templates/case-study-landing.html.twig";
if (file_exists($templatePath)) {
  $templateContent = file_get_contents($templatePath);
  // Count _cs-*.html.twig includes (the 15 sections).
  $includeCount = preg_match_all('/_cs-[a-z-]+\.html\.twig/', $templateContent);
  if ($includeCount >= 10) {
    echo "  [PASS] CHECK 4: Template has $includeCount section includes (min 10)\n";
  } else {
    $warnings[] = "Template has only $includeCount section includes (expected 10+)";
    echo "  [WARN] CHECK 4: Template has only $includeCount section includes\n";
  }
} else {
  $errors[] = "Missing unified template: $templatePath";
  echo "  [FAIL] CHECK 4: case-study-landing.html.twig not found\n";
}

// CHECK 5: Library 'case-study-landing' exists.
$librariesPath = "$themePath/ecosistema_jaraba_theme.libraries.yml";
if (file_exists($librariesPath)) {
  $libContent = file_get_contents($librariesPath);
  if (strpos($libContent, 'case-study-landing:') !== false) {
    echo "  [PASS] CHECK 5: Library 'case-study-landing' registered\n";
  } else {
    $errors[] = "Missing library 'case-study-landing' in libraries.yml";
    echo "  [FAIL] CHECK 5: Library not found\n";
  }
} else {
  $errors[] = "Missing libraries.yml";
  echo "  [FAIL] CHECK 5: libraries.yml not found\n";
}

// CHECK 6: hook_theme registers 'case_study_landing'.
$moduleFile = "$successCasesModule/jaraba_success_cases.module";
if (file_exists($moduleFile)) {
  $moduleContent = file_get_contents($moduleFile);
  if (strpos($moduleContent, "'case_study_landing'") !== false) {
    echo "  [PASS] CHECK 6: hook_theme registers 'case_study_landing'\n";
  } else {
    $errors[] = "hook_theme missing 'case_study_landing' entry";
    echo "  [FAIL] CHECK 6: hook_theme entry not found\n";
  }
} else {
  $errors[] = "Missing module file: $moduleFile";
  echo "  [FAIL] CHECK 6: .module file not found\n";
}

// CHECK 7: No legacy CaseStudy controllers exist in vertical modules.
$legacyModules = [
  'jaraba_agroconecta_core', 'jaraba_andalucia_ei', 'jaraba_business_tools',
  'jaraba_candidate', 'jaraba_comercio_conecta', 'jaraba_content_hub',
  'jaraba_lms', 'jaraba_servicios_conecta',
];
$legacyControllers = [];
foreach ($legacyModules as $mod) {
  $pattern = "$modulesPath/$mod/src/Controller/*CaseStudyController.php";
  foreach (glob($pattern) as $file) {
    $legacyControllers[] = basename($file);
  }
}
if (empty($legacyControllers)) {
  echo "  [PASS] CHECK 7: No legacy CaseStudy controllers found\n";
} else {
  $errors[] = "Legacy controllers still exist: " . implode(', ', $legacyControllers);
  echo "  [FAIL] CHECK 7: Found legacy controllers: " . implode(', ', $legacyControllers) . "\n";
}

// CHECK 8: No routing.yml entries reference deleted controller classes.
$routeClassErrors = [];
foreach ($legacyModules as $mod) {
  $routingFile = "$modulesPath/$mod/$mod.routing.yml";
  if (file_exists($routingFile)) {
    $content = file_get_contents($routingFile);
    if (preg_match('/CaseStudyController::/', $content)) {
      $routeClassErrors[] = $mod;
    }
  }
}
if (empty($routeClassErrors)) {
  echo "  [PASS] CHECK 8: No routing.yml entries reference deleted controllers\n";
} else {
  $errors[] = "Routing.yml still references deleted controllers in: " . implode(', ', $routeClassErrors);
  echo "  [FAIL] CHECK 8: Stale routing entries in: " . implode(', ', $routeClassErrors) . "\n";
}

// CHECK 9: Seed script has at least 9 verticals covered.
$seedPath = 'scripts/migration/seed-success-cases.php';
if (file_exists($seedPath)) {
  $seedContent = file_get_contents($seedPath);
  $coveredVerticals = 0;
  foreach ($commercialVerticals as $v) {
    if (preg_match("/'vertical'\s*=>\s*'$v'/", $seedContent)) {
      $coveredVerticals++;
    }
  }
  if ($coveredVerticals >= 9) {
    echo "  [PASS] CHECK 9: Seed script covers all $coveredVerticals/9 commercial verticals\n";
  } else {
    $errors[] = "Seed script only covers $coveredVerticals/9 verticals";
    echo "  [FAIL] CHECK 9: Only $coveredVerticals/9 verticals in seed\n";
  }
} else {
  $warnings[] = "Seed script not found (non-blocking)";
  echo "  [WARN] CHECK 9: seed-success-cases.php not found\n";
}

// Summary.
echo "\n";
$totalChecks = 9;
$failCount = count($errors);
$warnCount = count($warnings);
$passCount = $totalChecks - $failCount - $warnCount;

if ($failCount === 0) {
  echo "CASE-STUDY-COMPLETENESS-001: PASS — $passCount/$totalChecks checks passed";
  if ($warnCount > 0) {
    echo " ($warnCount warnings)";
  }
  echo "\n";
  exit(0);
}

echo "CASE-STUDY-COMPLETENESS-001: FAIL — $failCount errors, $warnCount warnings\n";
exit(1);
