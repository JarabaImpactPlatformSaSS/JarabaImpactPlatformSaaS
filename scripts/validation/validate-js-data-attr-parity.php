<?php

/**
 * @file
 * JS-DATA-ATTR-PARITY-001: Validates Twig data-* attributes match JS selectors.
 *
 * Detects disconnections between templates and JS behaviors, e.g.:
 * - Template uses data-count but JS searches for data-counter-target
 * - Template uses .cs-hero but JS searches for .landing-hero only
 *
 * Checks known pairs of Twig data attributes → JS selectors.
 *
 * Usage: php scripts/validation/validate-js-data-attr-parity.php
 */

$themePath = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme';
$jsPath = $themePath . '/js';
$templatePath = $themePath . '/templates';

$errors = [];
$warnings = [];
$checks = 0;

echo "JS-DATA-ATTR-PARITY-001: Validating Twig data-* attributes match JS selectors...\n\n";

// === Known canonical pairs: data attribute → JS file + selector ===
$canonicalPairs = [
  [
    'description' => 'Animated counters',
    'twig_attr' => 'data-counter-target',
    'twig_wrong' => 'data-count',
    'js_file' => 'landing-counters.js',
    'js_selector' => 'data-counter-target',
    'twig_glob' => '_cs-*.html.twig',
  ],
  [
    'description' => 'Hero video autoplay',
    'twig_attr' => 'data-hero-video-el',
    'twig_wrong' => NULL,
    'js_file' => 'landing-hero-video.js',
    'js_selector' => 'data-hero-video-el',
    'twig_glob' => '_cs-hero.html.twig',
  ],
  [
    'description' => 'Sticky CTA hero selector',
    'twig_attr' => 'cs-hero',
    'twig_wrong' => NULL,
    'js_file' => 'landing-sticky-cta.js',
    'js_selector' => '.cs-hero',
    'twig_glob' => '_cs-hero.html.twig',
  ],
  [
    'description' => 'Reveal animations',
    'twig_attr' => 'reveal-element',
    'twig_wrong' => NULL,
    'js_file' => 'scroll-animations.js',
    'js_selector' => '.reveal-element',
    'twig_glob' => '_cs-*.html.twig',
  ],
  [
    'description' => 'Funnel tracking CTAs',
    'twig_attr' => 'data-track-cta',
    'twig_wrong' => NULL,
    'js_file' => NULL,
    'js_selector' => NULL,
    'twig_glob' => '_cs-*.html.twig',
  ],
];

foreach ($canonicalPairs as $pair) {
  $checks++;
  $desc = $pair['description'];

  // Check 1: Twig templates use the correct attribute.
  $twigFiles = glob($templatePath . '/partials/' . $pair['twig_glob']);
  $usesCorrect = FALSE;
  $usesWrong = FALSE;

  foreach ($twigFiles as $file) {
    $content = file_get_contents($file);
    if (str_contains($content, $pair['twig_attr'])) {
      $usesCorrect = TRUE;
    }
    if ($pair['twig_wrong'] && preg_match('/\b' . preg_quote($pair['twig_wrong'], '/') . '(?!=)["=\s]/', $content)) {
      // Match exact attribute name, not as substring of a longer attribute.
      // e.g. data-count=" but NOT data-counter-target.
      $usesWrong = TRUE;
    }
  }

  if ($usesWrong) {
    $errors[] = "$desc: Templates use wrong attribute '{$pair['twig_wrong']}' (should be '{$pair['twig_attr']}')";
    echo "  [FAIL] $desc: Templates use '{$pair['twig_wrong']}' instead of '{$pair['twig_attr']}'\n";
    continue;
  }

  if (!$usesCorrect && count($twigFiles) > 0) {
    $warnings[] = "$desc: No template uses '{$pair['twig_attr']}'";
    echo "  [WARN] $desc: No template uses '{$pair['twig_attr']}'\n";
    continue;
  }

  // Check 2: JS file contains the selector.
  if ($pair['js_file']) {
    $jsFilePath = $jsPath . '/' . $pair['js_file'];
    if (file_exists($jsFilePath)) {
      $jsContent = file_get_contents($jsFilePath);
      if (str_contains($jsContent, $pair['js_selector'])) {
        echo "  [PASS] $desc: Twig '{$pair['twig_attr']}' ↔ JS '{$pair['js_selector']}' in {$pair['js_file']}\n";
      }
      else {
        $errors[] = "$desc: JS '{$pair['js_file']}' missing selector '{$pair['js_selector']}'";
        echo "  [FAIL] $desc: JS '{$pair['js_file']}' does NOT contain '{$pair['js_selector']}'\n";
      }
    }
    else {
      $errors[] = "$desc: JS file '{$pair['js_file']}' not found";
      echo "  [FAIL] $desc: JS file '{$pair['js_file']}' not found\n";
    }
  }
  else {
    echo "  [PASS] $desc: Twig '{$pair['twig_attr']}' present (no JS selector check needed)\n";
  }
}

// === Additional check: detect orphan data-count usage in _cs- templates ===
$checks++;
$orphanCount = 0;
foreach (glob($templatePath . '/partials/_cs-*.html.twig') as $file) {
  $content = file_get_contents($file);
  // data-count that is NOT data-counter- (which is OK).
  if (preg_match('/data-count="[^"]*"/', $content) && !str_contains($content, 'data-counter-target')) {
    $orphanCount++;
    $errors[] = basename($file) . " uses legacy 'data-count' (should be 'data-counter-target')";
    echo "  [FAIL] " . basename($file) . " uses legacy 'data-count'\n";
  }
}
if ($orphanCount === 0) {
  echo "  [PASS] No legacy 'data-count' in _cs-* templates\n";
}

// === Summary ===
echo "\n";
if (empty($errors)) {
  $warnText = count($warnings) > 0 ? " (" . count($warnings) . " warnings)" : "";
  echo "JS-DATA-ATTR-PARITY-001: PASS — $checks checks$warnText\n";
  exit(0);
}
else {
  echo "JS-DATA-ATTR-PARITY-001: FAIL — " . count($errors) . " errors\n";
  foreach ($errors as $e) {
    echo "  ERROR: $e\n";
  }
  exit(1);
}
