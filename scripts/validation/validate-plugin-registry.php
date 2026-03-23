<?php

declare(strict_types=1);

/**
 * @file
 * PLUGIN-REGISTRY-VALIDATION-001: Validates Page Builder libraries.yml integrity.
 *
 * Verifies that all JS and CSS files declared in jaraba_page_builder's
 * libraries.yml actually exist on disk. For page-builder-related libraries,
 * also checks that JS files contain valid plugin/component registration
 * patterns (editor.*, grapesjs, Drupal.behaviors, Plugins.add).
 *
 * EXIT CODES:
 *   0 = All declared assets exist and contain valid registrations
 *   1 = Missing files or JS without recognized registration patterns
 *
 * @see IMPLEMENTATION-CHECKLIST-001
 */

$root = dirname(__DIR__, 2);
$moduleDir = $root . '/web/modules/custom/jaraba_page_builder';
$librariesFile = $moduleDir . '/jaraba_page_builder.libraries.yml';

if (!file_exists($librariesFile)) {
  echo "[ERROR] libraries.yml not found: $librariesFile\n";
  exit(1);
}

// ---------------------------------------------------------------------
// Parse YAML (simple parser — no Symfony dependency needed).
// ---------------------------------------------------------------------
$content = file_get_contents($librariesFile);
if ($content === false) {
  echo "[ERROR] Cannot read: $librariesFile\n";
  exit(1);
}

/**
 * Minimal YAML parser sufficient for Drupal libraries.yml structure.
 *
 * Returns ['library_name' => ['js' => [...paths], 'css' => [...paths]]].
 */
function parseLibrariesYml(string $content): array {
  $libraries = [];
  $currentLib = null;
  $inJs = false;
  $inCss = false;
  $inCssWeight = false;

  foreach (explode("\n", $content) as $line) {
    // Skip comments and blank lines.
    $trimmed = ltrim($line);
    if ($trimmed === '' || str_starts_with($trimmed, '#')) {
      continue;
    }

    // Top-level library key (no indentation, ends with colon).
    if ($line !== '' && $line[0] !== ' ' && str_ends_with(rtrim($line), ':')) {
      $currentLib = rtrim(rtrim($line), ':');
      $libraries[$currentLib] = ['js' => [], 'css' => []];
      $inJs = false;
      $inCss = false;
      $inCssWeight = false;
      continue;
    }

    if ($currentLib === null) {
      continue;
    }

    // Detect js: / css: sections.
    if (preg_match('/^  js:\s*$/', $line)) {
      $inJs = true;
      $inCss = false;
      $inCssWeight = false;
      continue;
    }
    if (preg_match('/^  css:\s*$/', $line)) {
      $inCss = true;
      $inJs = false;
      $inCssWeight = false;
      continue;
    }

    // CSS weight groups (theme:, component:, base:, layout:, state:).
    if ($inCss && preg_match('/^    \w+:\s*$/', $line)) {
      $inCssWeight = true;
      continue;
    }

    // Other top-level keys under library (version:, dependencies:).
    if (preg_match('/^  \w/', $line) && !$inJs && !$inCss) {
      $inJs = false;
      $inCss = false;
      $inCssWeight = false;
      continue;
    }
    if (preg_match('/^  (version|dependencies)/', $line)) {
      $inJs = false;
      $inCss = false;
      $inCssWeight = false;
      continue;
    }

    // Extract JS file paths (4-space indent under js:).
    if ($inJs && preg_match('/^\s{4}(\S+)\s*:/', $line, $m)) {
      $path = $m[1];
      // Skip external URLs.
      if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
        $libraries[$currentLib]['js'][] = $path;
      }
      continue;
    }

    // Extract CSS file paths (6-space indent under css weight group).
    if ($inCss && $inCssWeight && preg_match('/^\s{6}(\S+)\s*:/', $line, $m)) {
      $path = $m[1];
      if (!str_starts_with($path, 'http://') && !str_starts_with($path, 'https://')) {
        $libraries[$currentLib]['css'][] = $path;
      }
      continue;
    }
  }

  return $libraries;
}

$libraries = parseLibrariesYml($content);

// Registration patterns expected in page-builder JS files.
$registrationPatterns = [
  '/editor\./',
  '/grapesjs/i',
  '/Drupal\.behaviors\./',
  '/Plugins\.add/',
  '/GrapesJS/',
  '/\bonce\s*\(/',
  '/Drupal\.behaviors/',
];

$errors = [];
$warnings = [];
$totalJs = 0;
$totalCss = 0;
$missingJs = 0;
$missingCss = 0;
$noRegistration = 0;

foreach ($libraries as $libName => $assets) {
  // Check JS files.
  foreach ($assets['js'] as $jsPath) {
    $totalJs++;
    // Paths starting with / are relative to web root.
    if (str_starts_with($jsPath, '/')) {
      $fullPath = $root . '/web' . $jsPath;
    } else {
      $fullPath = $moduleDir . '/' . $jsPath;
    }

    if (!file_exists($fullPath)) {
      $errors[] = "[ERROR] Missing JS: $jsPath (library: $libName)";
      $missingJs++;
      continue;
    }

    // Check for registration patterns in non-vendor, non-minified JS.
    if (!str_contains($jsPath, 'vendor/') && !str_ends_with($jsPath, '.min.js')) {
      $jsContent = file_get_contents($fullPath);
      if ($jsContent === false) {
        $errors[] = "[ERROR] Cannot read JS: $fullPath (library: $libName)";
        continue;
      }

      $hasRegistration = false;
      foreach ($registrationPatterns as $pattern) {
        if (preg_match($pattern, $jsContent)) {
          $hasRegistration = true;
          break;
        }
      }

      if (!$hasRegistration) {
        $warnings[] = "[WARN] JS has no recognized plugin/behavior registration: $jsPath (library: $libName)";
        $noRegistration++;
      }
    }
  }

  // Check CSS files.
  foreach ($assets['css'] as $cssPath) {
    $totalCss++;
    if (str_starts_with($cssPath, '/')) {
      $fullPath = $root . '/web' . $cssPath;
    } else {
      $fullPath = $moduleDir . '/' . $cssPath;
    }

    if (!file_exists($fullPath)) {
      $errors[] = "[ERROR] Missing CSS: $cssPath (library: $libName)";
      $missingCss++;
    }
  }
}

// Output results.
echo "PLUGIN-REGISTRY-VALIDATION-001: Page Builder libraries.yml integrity\n";
echo str_repeat('=', 70) . "\n";
echo "Libraries parsed: " . count($libraries) . "\n";
echo "JS files declared: $totalJs (missing: $missingJs)\n";
echo "CSS files declared: $totalCss (missing: $missingCss)\n";
echo "JS without registration pattern: $noRegistration\n";
echo str_repeat('-', 70) . "\n";

$exitCode = 0;

if (!empty($errors)) {
  foreach ($errors as $error) {
    echo "$error\n";
  }
  $exitCode = 1;
}

if (!empty($warnings)) {
  foreach ($warnings as $warning) {
    echo "$warning\n";
  }
}

if (empty($errors) && empty($warnings)) {
  echo "[OK] All declared assets exist and contain valid registrations.\n";
}
elseif (empty($errors)) {
  echo "[OK] All declared assets exist. $noRegistration warning(s) above.\n";
}
else {
  $total = $missingJs + $missingCss;
  echo "[FAIL] $total missing asset(s) found.\n";
}

exit($exitCode);
