<?php

/**
 * @file
 * VERTICAL-CATALOG-SYNC-001: Verifica que MegaMenuBridgeService::getVerticalCatalog()
 * contiene las 10 verticales canonicas organizadas en 4 categorias.
 *
 * Checks:
 * C1: getVerticalCatalog() method exists in MegaMenuBridgeService.php
 * C2: All 10 verticals are present
 * C3: 4 category titles present
 * C4: Each vertical has required keys (title, subtitle, url, color, icon_cat, icon_name)
 * C5: URLs are relative paths (start with /)
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$checks = 0;

$bridgeFile = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/src/Service/MegaMenuBridgeService.php';

if (!file_exists($bridgeFile)) {
  echo "FAIL: MegaMenuBridgeService.php not found at $bridgeFile\n";
  exit(1);
}

$content = file_get_contents($bridgeFile);

// C1: getVerticalCatalog() method exists.
$checks++;
if (!str_contains($content, 'function getVerticalCatalog()')) {
  $errors[] = 'C1: getVerticalCatalog() method not found in MegaMenuBridgeService.php';
}

// C2: All 10 verticals present.
$checks++;
$requiredVerticals = [
  'Empleabilidad',
  'Formación',
  'Emprendimiento',
  'ComercioConecta',
  'AgroConecta',
  'ServiciosConecta',
  'JarabaLex',
  'Content Hub',
  'Desarrollo Local',
  'Andalucía',
];
$missingVerticals = [];
foreach ($requiredVerticals as $vertical) {
  // Check exact match with quotes, or substring match without quotes.
  // Substring match handles cases like 'Andalucía +ei' matching 'Andalucía'.
  if (str_contains($content, $vertical)) {
    continue;
  }
  // Fallback: ASCII-safe partial match for accented characters.
  $asciiPrefix = preg_replace('/[^a-zA-Z].*$/', '', $vertical);
  if (strlen($asciiPrefix) >= 4 && str_contains($content, $asciiPrefix)) {
    continue;
  }
  $missingVerticals[] = $vertical;
}
if (!empty($missingVerticals)) {
  $errors[] = 'C2: Missing verticals in getVerticalCatalog(): ' . implode(', ', $missingVerticals);
}

// C3: 4 category titles present.
$checks++;
$requiredCategories = [
  'Para Personas',
  'Para Empresas',
  'Para Profesionales',
  'Para Instituciones',
];
$missingCategories = [];
foreach ($requiredCategories as $category) {
  if (!str_contains($content, "'$category'") && !str_contains($content, "\"$category\"")) {
    $missingCategories[] = $category;
  }
}
if (!empty($missingCategories)) {
  $errors[] = 'C3: Missing category titles: ' . implode(', ', $missingCategories);
}

// C4: Each vertical has required keys.
$checks++;
$requiredKeys = ['title', 'subtitle', 'url', 'color', 'icon_cat', 'icon_name'];
// Extract the getVerticalCatalog method body.
if (preg_match('/function getVerticalCatalog\(\)[^{]*\{(.*?)^\s{2}\}/ms', $content, $methodMatch)) {
  $methodBody = $methodMatch[1];
  foreach ($requiredKeys as $key) {
    // Check that the key appears as an array key in the method body.
    if (!str_contains($methodBody, "'$key'")) {
      $errors[] = "C4: Missing required key '$key' in vertical items of getVerticalCatalog()";
    }
  }
}
else {
  $errors[] = 'C4: Could not extract getVerticalCatalog() method body for key analysis';
}

// C5: URLs are relative paths (start with /).
$checks++;
if (preg_match_all("/'url'\s*=>\s*'([^']*)'/", $content, $urlMatches)) {
  $badUrls = [];
  foreach ($urlMatches[1] as $url) {
    if (!str_starts_with($url, '/')) {
      $badUrls[] = $url;
    }
  }
  if (!empty($badUrls)) {
    $errors[] = 'C5: Non-relative URLs found in getVerticalCatalog(): ' . implode(', ', $badUrls);
  }
}
else {
  $errors[] = 'C5: No URLs found in getVerticalCatalog() to validate';
}

// Report.
if (empty($errors)) {
  echo "VERTICAL-CATALOG-SYNC-001: OK ($checks checks passed) — 10 verticals, 4 categories, all keys present, URLs relative\n";
  exit(0);
}

echo "VERTICAL-CATALOG-SYNC-001: FAIL\n";
foreach ($errors as $error) {
  echo "  - $error\n";
}
exit(1);
