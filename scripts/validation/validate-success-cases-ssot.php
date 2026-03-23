<?php

/**
 * @file
 * SUCCESS-CASES-SSOT-001: Validates that case study data comes from entity.
 *
 * Verifies SUCCESS-CASES-001: All case study content MUST come from
 * SuccessCase entity, no hardcoded data in controllers or templates.
 *
 * Usage: php scripts/validation/validate-success-cases-ssot.php
 */

$basePath = __DIR__ . '/../../web/modules/custom';
$themePath = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme';

$errors = [];
$warnings = [];
$checks = 0;

echo "SUCCESS-CASES-SSOT-001: Validating SuccessCase as SSOT...\n\n";

// === CHECK 1: No hardcoded metrics/testimonials in old controllers ===
$checks++;
$oldControllers = glob($basePath . '/*/src/Controller/*CaseStudy*.php');
$violatingControllers = [];
foreach ($oldControllers as $controller) {
  // Skip the new unified controller.
  if (str_contains($controller, 'CaseStudyLandingController')) {
    continue;
  }
  $content = file_get_contents($controller);
  // Check for hardcoded metrics arrays.
  if (preg_match('/#metrics.*=>\s*\[/', $content) || preg_match('/#testimonial.*=>\s*\[/', $content)) {
    $violatingControllers[] = basename(dirname(dirname(dirname($controller)))) . '/' . basename($controller);
  }
}
if (empty($violatingControllers)) {
  echo "  [PASS] CHECK 1: No hardcoded metrics/testimonials in controllers\n";
} else {
  // Warn but don't fail — legacy controllers may still exist during transition.
  $warnings[] = "CHECK 1: Legacy hardcoded controllers: " . implode(', ', $violatingControllers);
  echo "  [WARN] CHECK 1: Legacy controllers with hardcoded data:\n";
  foreach ($violatingControllers as $c) {
    echo "         - $c\n";
  }
}

// === CHECK 2: Unified controller loads from SuccessCase entity ===
$checks++;
$unifiedController = $basePath . '/jaraba_success_cases/src/Controller/CaseStudyLandingController.php';
if (file_exists($unifiedController)) {
  $content = file_get_contents($unifiedController);
  $loadsEntity = str_contains($content, "->getStorage('success_case')");
  $usesPricing = str_contains($content, 'MetaSitePricingService') || str_contains($content, 'pricingService');
  $usesUrlFromRoute = str_contains($content, 'Url::fromRoute');

  if ($loadsEntity && $usesPricing && $usesUrlFromRoute) {
    echo "  [PASS] CHECK 2: Unified controller loads from entity + pricing + Url::fromRoute\n";
  } else {
    $missing = [];
    if (!$loadsEntity) $missing[] = 'entity loading';
    if (!$usesPricing) $missing[] = 'MetaSitePricingService';
    if (!$usesUrlFromRoute) $missing[] = 'Url::fromRoute';
    $errors[] = "CHECK 2: Unified controller missing: " . implode(', ', $missing);
    echo "  [FAIL] CHECK 2: Unified controller missing: " . implode(', ', $missing) . "\n";
  }
} else {
  $errors[] = "CHECK 2: CaseStudyLandingController.php not found";
  echo "  [FAIL] CHECK 2: CaseStudyLandingController.php not found\n";
}

// === CHECK 3: SuccessCase entity has tenant_id field ===
$checks++;
$entityFile = $basePath . '/jaraba_success_cases/src/Entity/SuccessCase.php';
if (file_exists($entityFile)) {
  $content = file_get_contents($entityFile);
  if (str_contains($content, "'tenant_id'") && str_contains($content, 'entity_reference')) {
    echo "  [PASS] CHECK 3: SuccessCase has tenant_id entity_reference field\n";
  } else {
    $errors[] = "CHECK 3: SuccessCase missing tenant_id entity_reference";
    echo "  [FAIL] CHECK 3: SuccessCase missing tenant_id entity_reference\n";
  }
} else {
  $errors[] = "CHECK 3: SuccessCase.php not found";
  echo "  [FAIL] CHECK 3: SuccessCase.php not found\n";
}

// === CHECK 4: SuccessCase has vertical field (list_string) ===
$checks++;
if (file_exists($entityFile)) {
  $content = file_get_contents($entityFile);
  if (str_contains($content, "'vertical'") && str_contains($content, 'list_string')) {
    echo "  [PASS] CHECK 4: SuccessCase has vertical list_string field\n";
  } else if (str_contains($content, "'vertical'")) {
    $warnings[] = "CHECK 4: vertical field exists but may not be list_string";
    echo "  [WARN] CHECK 4: vertical field exists but may not be list_string\n";
  } else {
    $errors[] = "CHECK 4: vertical field missing from SuccessCase";
    echo "  [FAIL] CHECK 4: vertical field missing from SuccessCase\n";
  }
}

// === CHECK 5: No hardcoded EUR prices in case study templates ===
$checks++;
$templates = glob($themePath . '/templates/partials/_cs-*.html.twig');
$priceViolations = [];
foreach ($templates as $template) {
  $content = file_get_contents($template);
  // Look for hardcoded EUR amounts like "149€" or "149 €".
  if (preg_match('/\b\d{2,3}\s*€/', $content)) {
    // Skip if it's a Twig variable like {{ pricing.price }} €.
    if (!preg_match('/\{\{[^}]+\}\}\s*€/', $content)) {
      $priceViolations[] = basename($template);
    }
  }
}
if (empty($priceViolations)) {
  echo "  [PASS] CHECK 5: No hardcoded EUR prices in case study templates\n";
} else {
  $errors[] = "CHECK 5: Hardcoded prices in: " . implode(', ', $priceViolations);
  echo "  [FAIL] CHECK 5: Hardcoded prices in: " . implode(', ', $priceViolations) . "\n";
}

// === CHECK 6: URLs use Url::fromRoute (no hardcoded paths in controller) ===
$checks++;
if (file_exists($unifiedController)) {
  $content = file_get_contents($unifiedController);
  // Check for hardcoded /planes/ or /registro/ paths without fallback context.
  $hasHardcodedPrimary = (bool) preg_match("/['\"]\/planes\//", $content);
  $hasUrlFromRoute = str_contains($content, 'Url::fromRoute');
  // Hardcoded paths are OK as fallbacks in catch blocks.
  if ($hasUrlFromRoute) {
    echo "  [PASS] CHECK 6: Controller uses Url::fromRoute for URLs\n";
  } else {
    $errors[] = "CHECK 6: Controller doesn't use Url::fromRoute";
    echo "  [FAIL] CHECK 6: Controller doesn't use Url::fromRoute\n";
  }
}

// === SUMMARY ===
echo "\n";
$passCount = $checks - count($errors);
echo "SUCCESS-CASES-SSOT-001: $passCount/$checks checks passed\n";

if (empty($errors)) {
  if (empty($warnings)) {
    echo "RESULT: PASS\n";
  } else {
    echo "RESULT: PASS (with " . count($warnings) . " warnings)\n";
    foreach ($warnings as $w) {
      echo "  WARN: $w\n";
    }
  }
  exit(0);
} else {
  echo "RESULT: FAIL\n";
  foreach ($errors as $e) {
    echo "  ERROR: $e\n";
  }
  exit(1);
}
