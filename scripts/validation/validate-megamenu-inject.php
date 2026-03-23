#!/usr/bin/env php
<?php

/**
 * @file
 * MEGAMENU-INJECT-001: Verifica que _header.html.twig resuelve mega_menu_columns
 * desde theme_settings._mega_menu_columns cuando no se pasa explícitamente.
 *
 * Detecta page templates que incluyen _header.html.twig con 'only' pero NO
 * pasan mega_menu_columns. Esto NO es un error bloqueante desde que
 * MEGAMENU-INJECT-001 inyecta el fallback, pero se reporta como WARNING
 * para mantener consistencia.
 *
 * También verifica que:
 * 1. ecosistema_jaraba_theme.theme inyecta _mega_menu_columns en theme_settings
 * 2. _header.html.twig usa resolved_mega_columns con fallback a ts._mega_menu_columns
 *
 * Exit codes: 0 = OK, 1 = safeguard broken
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$templateDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates';
$themeFile = $root . '/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';
$headerFile = $templateDir . '/partials/_header.html.twig';

$errors = 0;
$warnings = 0;

// Check 1: theme file inyecta _mega_menu_columns en theme_settings.
$themeContent = file_get_contents($themeFile);
if (strpos($themeContent, "'_mega_menu_columns'") === false) {
  echo "ERROR: ecosistema_jaraba_theme.theme no inyecta _mega_menu_columns en theme_settings\n";
  echo "  Requerido por MEGAMENU-INJECT-001\n";
  $errors++;
} else {
  echo "OK: theme_settings._mega_menu_columns inyectado en preprocess_page\n";
}

// Check 2: _header.html.twig usa fallback desde theme_settings.
$headerContent = file_get_contents($headerFile);
if (strpos($headerContent, '_mega_menu_columns') === false) {
  echo "ERROR: _header.html.twig no usa fallback ts._mega_menu_columns\n";
  echo "  Sin este fallback, templates con 'only' sin mega_menu_columns quedan vacíos\n";
  $errors++;
} else {
  echo "OK: _header.html.twig usa fallback ts._mega_menu_columns\n";
}

// Check 3: Auditar page templates que incluyen header con 'only'.
$pageTemplates = glob($templateDir . '/page--*.html.twig');
$missingExplicit = [];

foreach ($pageTemplates as $file) {
  $content = file_get_contents($file);
  $basename = basename($file);

  // Solo nos interesan templates que incluyen _header.html.twig.
  if (strpos($content, '_header.html.twig') === false) {
    continue;
  }

  // Buscar el bloque include...only que contiene _header.
  if (preg_match('/\{%\s*include.*_header\.html\.twig.*?%\}/s', $content, $match)) {
    $includeBlock = $match[0];
    $hasOnly = strpos($includeBlock, 'only') !== false;
    $hasMega = strpos($content, 'mega_menu_columns') !== false;

    if ($hasOnly && !$hasMega) {
      $missingExplicit[] = $basename;
    }
  }
}

if (!empty($missingExplicit)) {
  echo "\nWARN: " . count($missingExplicit) . " templates incluyen header con 'only' sin mega_menu_columns explícito\n";
  echo "  (Protegido por MEGAMENU-INJECT-001 fallback, pero recomendado pasar explícitamente)\n";
  foreach (array_slice($missingExplicit, 0, 10) as $t) {
    echo "  - $t\n";
  }
  if (count($missingExplicit) > 10) {
    echo "  ... y " . (count($missingExplicit) - 10) . " más\n";
  }
  $warnings += count($missingExplicit);
}

echo "\n";
if ($errors > 0) {
  echo "FAIL: $errors errores, $warnings warnings\n";
  exit(1);
}

echo "PASS: MEGAMENU-INJECT-001 safeguard OK ($warnings warnings — protegidos por fallback)\n";
exit(0);
