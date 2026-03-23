#!/usr/bin/env php
<?php

/**
 * @file
 * TEMPLATE-CONTRACT-001: Verifica que page templates que incluyen parciales
 * criticos con 'only' pasen las variables minimas del contrato.
 *
 * Contratos verificados:
 * - _header.html.twig: theme_settings, site_name, logged_in
 * - _footer.html.twig: theme_settings, site_name
 *
 * Exit codes: 0 = OK, 1 = contract violations found
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$templateDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates';

$contracts = [
  '_header.html.twig' => [
    'required' => ['theme_settings', 'site_name', 'logged_in'],
    'label' => 'Header',
  ],
  '_footer.html.twig' => [
    'required' => ['theme_settings', 'site_name'],
    'label' => 'Footer',
  ],
];

$errors = 0;
$checked = 0;
$pageTemplates = glob($templateDir . '/page--*.html.twig');

foreach ($pageTemplates as $file) {
  $content = file_get_contents($file);
  $basename = basename($file);

  foreach ($contracts as $partial => $contract) {
    if (strpos($content, $partial) === false) {
      continue;
    }

    // Extract include blocks for this partial by finding the line and
    // expanding to the closing %} (may span multiple lines for with{}).
    $lines = explode("\n", $content);
    $includeBlock = '';
    $capturing = false;
    $braceDepth = 0;
    foreach ($lines as $line) {
      if (!$capturing && strpos($line, $partial) !== false && preg_match('/\{%\s*include/', $line)) {
        $capturing = true;
        $includeBlock = '';
      }
      if ($capturing) {
        $includeBlock .= $line . "\n";
        $braceDepth += substr_count($line, '{');
        $braceDepth -= substr_count($line, '}');
        if (strpos($line, '%}') !== false && $braceDepth <= 0) {
          break;
        }
      }
    }
    if (empty($includeBlock)) {
      continue;
    }

    // 'only' must appear at the end before %}, not as part of a variable name.
    $hasOnly = (bool) preg_match('/\bonly\s*%\}/', $includeBlock);

    if (!$hasOnly) {
      // Without 'only', all vars are inherited — no contract violation.
      continue;
    }

    $checked++;
    $missing = [];
    foreach ($contract['required'] as $var) {
      // Check if the variable is passed in the with {} block.
      if (strpos($includeBlock, $var) === false) {
        $missing[] = $var;
      }
    }

    if (!empty($missing)) {
      echo "WARN: $basename includes {$contract['label']} with 'only' missing: " . implode(', ', $missing) . "\n";
      // Only error if theme_settings is missing (critical).
      if (in_array('theme_settings', $missing, true)) {
        echo "  ERROR: theme_settings is critical — header/footer will have no config\n";
        $errors++;
      }
    }
  }
}

echo "\nChecked $checked include-with-only blocks in " . count($pageTemplates) . " page templates\n";

if ($errors > 0) {
  echo "FAIL: $errors critical contract violations (missing theme_settings)\n";
  exit(1);
}

echo "PASS: TEMPLATE-CONTRACT-001 — all critical contracts satisfied\n";
exit(0);
