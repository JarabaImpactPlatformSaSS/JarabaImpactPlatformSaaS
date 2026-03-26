<?php

/**
 * @file
 * Validator: A/B Testing infrastructure integrity (AB-TESTING-INTEGRITY-001).
 *
 * 6 checks for A/B testing pipeline:
 * ABExperiment entity, ABVariant entity, ExperimentService,
 * variant-tracker.js, ab-hero-cta.js, library registration.
 *
 * Usage: php scripts/validation/validate-ab-testing-active.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$customModules = __DIR__ . '/../../web/modules/custom';
$abTestingRoot = $customModules . '/jaraba_ab_testing';
$pbRoot = $customModules . '/jaraba_page_builder';
$eiRoot = $customModules . '/jaraba_andalucia_ei';

// CHECK 1: ABExperiment entity class exists.
$abExperimentPaths = [
  $abTestingRoot . '/src/Entity/ABExperiment.php',
  $abTestingRoot . '/src/Entity/AbExperiment.php',
  $pbRoot . '/src/Entity/ABExperiment.php',
  $pbRoot . '/src/Entity/AbExperiment.php',
];
$abExperimentFound = false;
foreach ($abExperimentPaths as $path) {
  if (file_exists($path)) {
    $abExperimentFound = true;
    break;
  }
}
// Broader search via glob.
if (!$abExperimentFound) {
  $globResults = glob($customModules . '/*/src/Entity/*Experiment*.php');
  if (is_array($globResults) && count($globResults) > 0) {
    $abExperimentFound = true;
  }
}
if ($abExperimentFound) {
  $passes[] = "CHECK 1 PASS: ABExperiment entity class exists";
} else {
  $errors[] = "CHECK 1 FAIL: ABExperiment entity class not found in any custom module";
}

// CHECK 2: ABVariant entity class exists.
$abVariantPaths = [
  $abTestingRoot . '/src/Entity/ABVariant.php',
  $abTestingRoot . '/src/Entity/AbVariant.php',
  $pbRoot . '/src/Entity/ABVariant.php',
  $pbRoot . '/src/Entity/AbVariant.php',
];
$abVariantFound = false;
foreach ($abVariantPaths as $path) {
  if (file_exists($path)) {
    $abVariantFound = true;
    break;
  }
}
if (!$abVariantFound) {
  $globResults = glob($customModules . '/*/src/Entity/*Variant*.php');
  if (is_array($globResults) && count($globResults) > 0) {
    $abVariantFound = true;
  }
}
if ($abVariantFound) {
  $passes[] = "CHECK 2 PASS: ABVariant entity class exists";
} else {
  $errors[] = "CHECK 2 FAIL: ABVariant entity class not found in any custom module";
}

// CHECK 3: ExperimentService exists in jaraba_page_builder or jaraba_ab_testing.
$experimentServicePaths = [
  $abTestingRoot . '/src/Service/ExperimentService.php',
  $pbRoot . '/src/Service/ExperimentService.php',
  $abTestingRoot . '/src/Service/ABExperimentService.php',
  $pbRoot . '/src/Service/ABExperimentService.php',
];
$experimentServiceFound = false;
foreach ($experimentServicePaths as $path) {
  if (file_exists($path)) {
    $experimentServiceFound = true;
    break;
  }
}
// Broader search.
if (!$experimentServiceFound) {
  $globResults = glob($customModules . '/{jaraba_ab_testing,jaraba_page_builder}/src/Service/*Experiment*', GLOB_BRACE);
  if (is_array($globResults) && count($globResults) > 0) {
    $experimentServiceFound = true;
  }
}
if ($experimentServiceFound) {
  $passes[] = "CHECK 3 PASS: ExperimentService exists in jaraba_page_builder or jaraba_ab_testing";
} else {
  $errors[] = "CHECK 3 FAIL: ExperimentService not found in jaraba_page_builder or jaraba_ab_testing";
}

// CHECK 4: variant-tracker.js exists in jaraba_ab_testing JS.
$variantTrackerPaths = [
  $abTestingRoot . '/js/variant-tracker.js',
  $abTestingRoot . '/js/ab/variant-tracker.js',
  $pbRoot . '/js/variant-tracker.js',
  $pbRoot . '/js/ab/variant-tracker.js',
];
$variantTrackerFound = false;
foreach ($variantTrackerPaths as $path) {
  if (file_exists($path)) {
    $variantTrackerFound = true;
    break;
  }
}
if (!$variantTrackerFound) {
  $globResults = glob($customModules . '/jaraba_ab_testing/js/*variant*tracker*');
  if (is_array($globResults) && count($globResults) > 0) {
    $variantTrackerFound = true;
  }
}
if ($variantTrackerFound) {
  $passes[] = "CHECK 4 PASS: variant-tracker.js found";
} else {
  $errors[] = "CHECK 4 FAIL: variant-tracker.js not found in jaraba_ab_testing JS";
}

// CHECK 5: ab-hero-cta.js exists in jaraba_andalucia_ei.
$abHeroCtaPaths = [
  $eiRoot . '/js/ab-hero-cta.js',
  $eiRoot . '/js/ab/ab-hero-cta.js',
];
$abHeroCtaFound = false;
foreach ($abHeroCtaPaths as $path) {
  if (file_exists($path)) {
    $abHeroCtaFound = true;
    break;
  }
}
if (!$abHeroCtaFound) {
  $globResults = glob($eiRoot . '/js/*ab-hero*');
  if (is_array($globResults) && count($globResults) > 0) {
    $abHeroCtaFound = true;
  }
}
if ($abHeroCtaFound) {
  $passes[] = "CHECK 5 PASS: ab-hero-cta.js found in jaraba_andalucia_ei";
} else {
  $errors[] = "CHECK 5 FAIL: ab-hero-cta.js not found in jaraba_andalucia_ei JS";
}

// CHECK 6: ab-hero-cta library registered in jaraba_andalucia_ei.libraries.yml.
$eiLibrariesFile = $eiRoot . '/jaraba_andalucia_ei.libraries.yml';
$eiLibrariesContent = file_exists($eiLibrariesFile) ? file_get_contents($eiLibrariesFile) : '';
if (strpos($eiLibrariesContent, 'ab-hero-cta') !== false) {
  $passes[] = "CHECK 6 PASS: ab-hero-cta library registered in jaraba_andalucia_ei.libraries.yml";
} else {
  $errors[] = "CHECK 6 FAIL: ab-hero-cta library not registered in jaraba_andalucia_ei.libraries.yml";
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== A/B TESTING INTEGRITY (AB-TESTING-INTEGRITY-001) ===\n\n";
foreach ($passes as $msg) {
  echo "  [PASS] $msg\n";
}
foreach ($errors as $msg) {
  echo "  [FAIL] $msg\n";
}
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(count($errors) === 0 ? 0 : 1);
