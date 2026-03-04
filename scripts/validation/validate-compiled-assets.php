<?php

/**
 * @file
 * ASSET-FRESHNESS-001: Validate compiled CSS is up-to-date with SCSS sources.
 *
 * Checks all SCSS→CSS pairs in the theme to ensure that compiled CSS files
 * have a modification time >= their SCSS source files. Also checks that
 * partial SCSS files (_*.scss) are not newer than their compiled entry points.
 *
 * Usage: php scripts/validation/validate-compiled-assets.php
 * Exit:  0 = fresh, 1 = stale assets found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$themeDir = $projectRoot . '/web/themes/custom/ecosistema_jaraba_theme';

if (!is_dir($themeDir)) {
  fwrite(STDERR, "ERROR: Theme directory not found: $themeDir\n");
  exit(1);
}

$scssDir = "$themeDir/scss";
$cssDir = "$themeDir/css";

if (!is_dir($scssDir) || !is_dir($cssDir)) {
  fwrite(STDERR, "ERROR: SCSS or CSS directory not found in theme\n");
  exit(1);
}

$stale = [];
$checked = 0;

// ─────────────────────────────────────────────────────────────
// Define SCSS → CSS mappings.
// ─────────────────────────────────────────────────────────────
$mappings = [];

// Main entry point.
$mappings["$scssDir/main.scss"] = "$cssDir/ecosistema-jaraba-theme.css";

// Admin entry point.
if (file_exists("$scssDir/admin-settings.scss")) {
  $mappings["$scssDir/admin-settings.scss"] = "$cssDir/admin-settings.css";
}

// Route SCSS files.
$routeScss = glob("$scssDir/routes/*.scss") ?: [];
foreach ($routeScss as $scss) {
  $name = basename($scss, '.scss');
  // Skip partials.
  if (str_starts_with($name, '_')) {
    continue;
  }
  $css = "$cssDir/routes/$name.css";
  $mappings[$scss] = $css;
}

// Bundle SCSS files.
$bundleScss = glob("$scssDir/bundles/*.scss") ?: [];
foreach ($bundleScss as $scss) {
  $name = basename($scss, '.scss');
  if (str_starts_with($name, '_')) {
    continue;
  }
  $css = "$cssDir/bundles/$name.css";
  $mappings[$scss] = $css;
}

// ─────────────────────────────────────────────────────────────
// Check each SCSS → CSS pair.
// ─────────────────────────────────────────────────────────────
foreach ($mappings as $scssFile => $cssFile) {
  if (!file_exists($scssFile)) {
    continue;
  }

  $checked++;
  $relScss = str_replace($projectRoot . '/', '', $scssFile);
  $relCss = str_replace($projectRoot . '/', '', $cssFile);

  if (!file_exists($cssFile)) {
    $stale[] = [
      'scss' => $relScss,
      'css' => $relCss,
      'reason' => 'CSS file does not exist',
    ];
    continue;
  }

  $scssMtime = filemtime($scssFile);
  $cssMtime = filemtime($cssFile);

  if ($cssMtime < $scssMtime) {
    $stale[] = [
      'scss' => $relScss,
      'css' => $relCss,
      'reason' => sprintf(
        'CSS is older (CSS: %s, SCSS: %s)',
        date('Y-m-d H:i:s', $cssMtime),
        date('Y-m-d H:i:s', $scssMtime)
      ),
    ];
  }
}

// ─────────────────────────────────────────────────────────────
// Check partials: if any _partial.scss is newer than main.css,
// the main bundle needs recompilation.
// ─────────────────────────────────────────────────────────────
$mainCss = "$cssDir/ecosistema-jaraba-theme.css";

if (file_exists($mainCss)) {
  $mainCssMtime = filemtime($mainCss);

  // Recursively find all partials.
  $partialIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scssDir, FilesystemIterator::SKIP_DOTS)
  );

  foreach ($partialIterator as $fileInfo) {
    if ($fileInfo->getExtension() !== 'scss') {
      continue;
    }
    $filename = $fileInfo->getBasename();
    if (!str_starts_with($filename, '_')) {
      continue;
    }

    $partialMtime = $fileInfo->getMTime();
    if ($partialMtime > $mainCssMtime) {
      $relPartial = str_replace($projectRoot . '/', '', $fileInfo->getPathname());
      $stale[] = [
        'scss' => $relPartial,
        'css' => str_replace($projectRoot . '/', '', $mainCss),
        'reason' => sprintf(
          'Partial newer than compiled CSS (partial: %s, CSS: %s)',
          date('Y-m-d H:i:s', $partialMtime),
          date('Y-m-d H:i:s', $mainCssMtime)
        ),
      ];
    }
  }
}

// ─────────────────────────────────────────────────────────────
// Output.
// ─────────────────────────────────────────────────────────────
echo "\n";
echo "=== ASSET-FRESHNESS-001: Compiled asset freshness ===\n";
echo "  Checked: $checked SCSS→CSS pairs\n";
echo "\n";

if (!empty($stale)) {
  echo "  [STALE] The following CSS files are out of date:\n";
  foreach ($stale as $s) {
    echo "    {$s['scss']} → {$s['css']}\n";
    echo "      Reason: {$s['reason']}\n";
  }
  echo "\n";
  echo "  " . count($stale) . " stale asset(s) found.\n";
  echo "  Run 'npm run build' in the theme directory to recompile.\n";
  echo "\n";
  exit(1);
}

echo "  OK: All compiled CSS is up to date.\n";
echo "\n";
exit(0);
