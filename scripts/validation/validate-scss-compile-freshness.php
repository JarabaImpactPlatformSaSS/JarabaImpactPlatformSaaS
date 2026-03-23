<?php

/**
 * @file
 * SCSS-COMPILE-FRESHNESS-001: Verifica que CSS compilados sean mas recientes
 * que los SCSS parciales que los generan.
 *
 * Usa git log timestamps (no filesystem) para portabilidad en CI.
 */

$base = dirname(__DIR__, 2);
$themeDir = $base . '/web/themes/custom/ecosistema_jaraba_theme';
$themeRel = 'web/themes/custom/ecosistema_jaraba_theme';

$cssFiles = [
  'css/ecosistema-jaraba-theme.css',
  'css/routes/landing.css',
  'css/routes/dashboard.css',
  'css/routes/content-hub.css',
  'css/routes/case-study-landing.css',
  'css/routes/jarabalex-case-study.css',
];

echo "\n=== SCSS-COMPILE-FRESHNESS-001: CSS freshness vs SCSS ===\n\n";

function gitTs(string $path): int {
  $out = trim((string) shell_exec(sprintf('git log -1 --format=%%ct -- %s 2>/dev/null', escapeshellarg($path))));
  return $out ? (int) $out : 0;
}

// Newest SCSS commit across ALL partials.
$scssAll = array_merge(
  glob($themeDir . '/scss/*.scss') ?: [],
  glob($themeDir . '/scss/**/*.scss') ?: [],
  glob($themeDir . '/scss/**/**/*.scss') ?: []
);
$newestScss = 0;
$newestScssName = '';
foreach ($scssAll as $f) {
  $rel = str_replace($base . '/', '', $f);
  $ts = gitTs($rel);
  if ($ts > $newestScss) {
    $newestScss = $ts;
    $newestScssName = basename($rel);
  }
}

$errors = [];
$passed = 0;
$total = count($cssFiles);

foreach ($cssFiles as $css) {
  $fullPath = $themeDir . '/' . $css;
  if (!file_exists($fullPath)) {
    $errors[] = "{$css} does not exist";
    continue;
  }
  $cssTs = gitTs($themeRel . '/' . $css);
  if ($cssTs >= $newestScss) {
    $passed++;
    echo "  ✓ {$css} FRESH\n";
  } else {
    $errors[] = "{$css} is STALE — newer partials: {$newestScssName}";
  }
}

echo "\n  Resultado: {$passed}/{$total}\n";
if (!empty($errors)) {
  foreach ($errors as $e) { echo "  [FAIL] {$e}\n"; }
  echo "\n  Fix: cd web/themes/custom/ecosistema_jaraba_theme && npm run build\n";
  exit(1);
}
echo "\n  ✅ PASSED\n\n";
exit(0);
