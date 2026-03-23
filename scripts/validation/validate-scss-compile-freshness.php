<?php

/**
 * @file
 * SCSS-COMPILE-FRESHNESS-001: Validates that compiled CSS is fresher than SCSS sources.
 *
 * Goes beyond SCSS-COMPILE-VERIFY-001 (single file timestamp) by checking
 * that EACH route/bundle CSS is newer than ALL the SCSS partials it imports.
 *
 * Catches the case where a partial (_landing-sections.scss) is edited but
 * only main.scss is recompiled (which doesn't include that partial).
 *
 * Usage: php scripts/validation/validate-scss-compile-freshness.php
 */

$themePath = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme';
$errors = [];
$checks = 0;

echo "SCSS-COMPILE-FRESHNESS-001: Validating SCSS→CSS freshness...\n\n";

// Map: CSS file → SCSS entry point → all @use'd partials.
$routeMappings = [
  'css/routes/landing.css' => 'scss/routes/landing.scss',
  'css/routes/dashboard.css' => 'scss/routes/dashboard.scss',
  'css/routes/content-hub.css' => 'scss/routes/content-hub.scss',
  'css/routes/case-study-landing.css' => 'scss/routes/case-study-landing.scss',
  'css/routes/jarabalex-case-study.css' => 'scss/routes/jarabalex-case-study.scss',
  'css/ecosistema-jaraba-theme.css' => 'scss/main.scss',
];

foreach ($routeMappings as $cssFile => $scssEntry) {
  $cssPath = $themePath . '/' . $cssFile;
  $scssPath = $themePath . '/' . $scssEntry;

  if (!file_exists($cssPath) || !file_exists($scssPath)) {
    continue;
  }

  $checks++;
  $cssMtime = filemtime($cssPath);

  // Resolve all @use'd partials recursively.
  $allScssPaths = resolveScssImports($scssPath, $themePath . '/scss');
  $stalePartials = [];

  foreach ($allScssPaths as $partial) {
    if (file_exists($partial) && filemtime($partial) > $cssMtime) {
      $stalePartials[] = str_replace($themePath . '/', '', $partial);
    }
  }

  if (empty($stalePartials)) {
    echo "  [PASS] $cssFile is fresh\n";
  }
  else {
    $errors[] = "$cssFile is STALE — newer SCSS: " . implode(', ', $stalePartials);
    echo "  [FAIL] $cssFile is STALE — newer partials:\n";
    foreach ($stalePartials as $p) {
      echo "         - $p\n";
    }
  }
}

echo "\n";
if (empty($errors)) {
  echo "SCSS-COMPILE-FRESHNESS-001: PASS ($checks CSS files checked)\n";
  exit(0);
}
else {
  echo "SCSS-COMPILE-FRESHNESS-001: FAIL (" . count($errors) . " stale CSS files)\n";
  echo "Fix: Run 'npm run build' from the theme directory.\n";
  exit(1);
}

/**
 * Recursively resolves all @use'd SCSS partials from an entry point.
 */
function resolveScssImports(string $file, string $scssDir, array &$visited = []): array {
  $realpath = realpath($file);
  if (!$realpath || in_array($realpath, $visited)) {
    return [];
  }
  $visited[] = $realpath;
  $result = [$realpath];

  $content = file_get_contents($file);
  $dir = dirname($file);

  // Match @use 'path' and @use "path".
  preg_match_all("/@use\s+['\"]([^'\"]+)['\"]/", $content, $matches);

  foreach ($matches[1] as $import) {
    // Skip sass built-in modules.
    if (str_starts_with($import, 'sass:')) {
      continue;
    }

    // Resolve relative path.
    $candidates = [
      $dir . '/' . $import . '.scss',
      $dir . '/_' . $import . '.scss',
      $dir . '/' . $import . '/index.scss',
      $dir . '/' . $import . '/_index.scss',
      $scssDir . '/' . $import . '.scss',
      $scssDir . '/_' . $import . '.scss',
    ];

    foreach ($candidates as $candidate) {
      if (file_exists($candidate)) {
        $result = array_merge($result, resolveScssImports($candidate, $scssDir, $visited));
        break;
      }
    }
  }

  return $result;
}
