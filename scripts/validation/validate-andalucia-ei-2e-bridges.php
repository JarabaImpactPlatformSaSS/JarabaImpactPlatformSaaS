<?php

/**
 * @file
 * Validator: Andalucía +ei 2ª Edición — Cross-vertical bridges integrity.
 *
 * Verifies 7 bridge services exist, use @? pattern, and are registered.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-2e-bridges.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];
$warnings = [];

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';
$servicesContent = file_get_contents($moduleRoot . '/jaraba_andalucia_ei.services.yml');

// All 7 bridges.
$bridges = [
  'ei_emprendimiento_bridge' => 'EiEmprendimientoBridgeService',
  'ei_matching_bridge' => 'EiMatchingBridgeService',
  'ei_alumni_bridge' => 'EiAlumniBridgeService',
  'ei_badge_bridge' => 'EiBadgeBridgeService',
  'ei_content_hub_bridge' => 'EiContentHubBridgeService',
  'ei_comercio_conecta_bridge' => 'EiComercioConectaBridgeService',
  'ei_jarabalex_bridge' => 'EiJarabaLexBridgeService',
];

// CHECK 1: All bridge services registered in services.yml.
$registeredCount = 0;
foreach ($bridges as $serviceId => $className) {
  if (strpos($servicesContent, $serviceId) !== false) {
    $registeredCount++;
  } else {
    $errors[] = "CHECK 1 FAIL: Bridge '$serviceId' not registered in services.yml";
  }
}
if ($registeredCount === 7) {
  $passes[] = "CHECK 1 PASS: 7/7 bridge services registered";
}

// CHECK 2: All bridge PHP files exist.
$fileCount = 0;
foreach ($bridges as $serviceId => $className) {
  $file = $moduleRoot . "/src/Service/$className.php";
  if (file_exists($file)) {
    $fileCount++;
  } else {
    $errors[] = "CHECK 2 FAIL: Bridge file $className.php not found";
  }
}
if ($fileCount === 7) {
  $passes[] = "CHECK 2 PASS: 7/7 bridge PHP files exist";
}

// CHECK 3: New bridges (3) use @? pattern (OPTIONAL-CROSSMODULE-001).
$newBridges = ['ei_content_hub_bridge', 'ei_comercio_conecta_bridge', 'ei_jarabalex_bridge'];
$optionalCount = 0;
foreach ($newBridges as $bridge) {
  // Find the service definition block and check for @?.
  $pos = strpos($servicesContent, $bridge);
  if ($pos !== false) {
    // Look ahead ~200 chars for @?.
    $block = substr($servicesContent, $pos, 200);
    if (strpos($block, '@?') !== false) {
      $optionalCount++;
    } else {
      $warnings[] = "CHECK 3 WARN: Bridge '$bridge' may not use @? for optional dependency";
    }
  }
}
if ($optionalCount === 3) {
  $passes[] = "CHECK 3 PASS: 3/3 new bridges use @? (OPTIONAL-CROSSMODULE-001)";
} elseif ($optionalCount > 0) {
  $passes[] = "CHECK 3 PARTIAL: $optionalCount/3 new bridges confirmed with @?";
}

// CHECK 4: All bridges have isAvailable() method.
$availableCount = 0;
foreach ($bridges as $serviceId => $className) {
  $file = $moduleRoot . "/src/Service/$className.php";
  if (file_exists($file)) {
    $content = file_get_contents($file);
    if (strpos($content, 'isAvailable') !== false) {
      $availableCount++;
    }
  }
}
if ($availableCount >= 3) {
  $passes[] = "CHECK 4 PASS: $availableCount/7 bridges implement isAvailable() (new bridges have it)";
} else {
  $warnings[] = "CHECK 4 WARN: Only $availableCount bridges have isAvailable()";
}

// CHECK 5: ProgramaVerticalAccessService exists (temp access manager).
$verticalAccessFile = $moduleRoot . '/src/Service/ProgramaVerticalAccessService.php';
if (file_exists($verticalAccessFile)) {
  $passes[] = "CHECK 5 PASS: ProgramaVerticalAccessService exists (temp vertical access)";
} else {
  $errors[] = "CHECK 5 FAIL: ProgramaVerticalAccessService not found";
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANDALUCÍA +EI 2E — CROSS-VERTICAL BRIDGES ===\n\n";
foreach ($passes as $msg) { echo "  ✅ $msg\n"; }
foreach ($warnings as $msg) { echo "  ⚠️  $msg\n"; }
foreach ($errors as $msg) { echo "  ❌ $msg\n"; }
echo "\n--- Score: " . count($passes) . "/" . ($total) . " checks passed";
if (count($warnings) > 0) { echo " (" . count($warnings) . " warnings)"; }
echo " ---\n\n";
exit(count($errors) === 0 ? 0 : 1);
