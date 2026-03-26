<?php

/**
 * @file
 * AB-EXPERIMENT-HEALTH-001: Validate A/B testing infrastructure integrity.
 *
 * Checks:
 * 1. ABExperiment entity class exists
 * 2. ABVariant entity class exists
 * 3. variant-tracker.js exists
 * 4. StatisticalEngineService exists
 *
 * Usage: php scripts/validation/validate-ab-experiment-health.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$abModule = $projectRoot . '/web/modules/custom/jaraba_ab_testing';
$errors = [];
$checks = 0;

echo "AB-EXPERIMENT-HEALTH-001: A/B Testing Infrastructure Validator\n";
echo str_repeat('=', 60) . "\n";

// --- Check 1: ABExperiment entity class exists ---
$checks++;
$experimentPath = $abModule . '/src/Entity/ABExperiment.php';
if (file_exists($experimentPath)) {
  $content = file_get_contents($experimentPath);
  if (strpos($content, 'class ABExperiment') !== FALSE) {
    echo "CHECK 1: PASS — ABExperiment entity class exists\n";
  }
  else {
    $errors[] = "ABExperiment.php exists but does not define ABExperiment class";
    echo "CHECK 1: FAIL — ABExperiment class not defined\n";
  }
}
else {
  $errors[] = "ABExperiment entity not found at jaraba_ab_testing/src/Entity/ABExperiment.php";
  echo "CHECK 1: FAIL — ABExperiment entity not found\n";
}

// --- Check 2: ABVariant entity class exists ---
$checks++;
$variantPath = $abModule . '/src/Entity/ABVariant.php';
if (file_exists($variantPath)) {
  $content = file_get_contents($variantPath);
  if (strpos($content, 'class ABVariant') !== FALSE) {
    echo "CHECK 2: PASS — ABVariant entity class exists\n";
  }
  else {
    $errors[] = "ABVariant.php exists but does not define ABVariant class";
    echo "CHECK 2: FAIL — ABVariant class not defined\n";
  }
}
else {
  $errors[] = "ABVariant entity not found at jaraba_ab_testing/src/Entity/ABVariant.php";
  echo "CHECK 2: FAIL — ABVariant entity not found\n";
}

// --- Check 3: variant-tracker.js exists ---
$checks++;
$trackerPath = $abModule . '/js/variant-tracker.js';
if (file_exists($trackerPath)) {
  echo "CHECK 3: PASS — variant-tracker.js exists\n";
}
else {
  $errors[] = "variant-tracker.js not found at jaraba_ab_testing/js/variant-tracker.js";
  echo "CHECK 3: FAIL — variant-tracker.js not found\n";
}

// --- Check 4: StatisticalEngineService exists ---
$checks++;
$enginePath = $abModule . '/src/Service/StatisticalEngineService.php';
if (file_exists($enginePath)) {
  $content = file_get_contents($enginePath);
  if (strpos($content, 'class StatisticalEngineService') !== FALSE) {
    echo "CHECK 4: PASS — StatisticalEngineService exists\n";
  }
  else {
    $errors[] = "StatisticalEngineService.php exists but does not define the class";
    echo "CHECK 4: FAIL — StatisticalEngineService class not defined\n";
  }
}
else {
  $errors[] = "StatisticalEngineService not found at jaraba_ab_testing/src/Service/StatisticalEngineService.php";
  echo "CHECK 4: FAIL — StatisticalEngineService not found\n";
}

// --- Output ---
echo str_repeat('-', 60) . "\n";
echo "Checks: $checks | Errors: " . count($errors) . "\n";

if (count($errors) > 0) {
  echo "\nERRORS:\n";
  foreach ($errors as $e) {
    echo "  FAIL: $e\n";
  }
  exit(1);
}

echo "\nPASS: All A/B testing infrastructure checks passed.\n";
exit(0);
