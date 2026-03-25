<?php

/**
 * @file
 * Validator: DRUPAL-SETTINGS-CONSUMERS-001 — drupalSettings injection vs JS consumption.
 *
 * For each drupalSettings key injected in hook_page_attachments_alter or
 * hook_preprocess_* of any custom module, verifies that a JS file in the
 * same module (or theme) references that key.
 *
 * Usage: php scripts/validation/validate-drupal-settings-consumers.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$modulesDir = __DIR__ . '/../../web/modules/custom';

// Scan all .module files for drupalSettings injections.
$moduleFiles = glob($modulesDir . '/*/*.module');
$injections = []; // ['module' => ['key' => 'line context']].

foreach ($moduleFiles as $moduleFile) {
  $moduleName = basename(dirname($moduleFile));
  $content = file_get_contents($moduleFile);
  if (empty($content)) {
    continue;
  }

  // Pattern 1: $attachments['#attached']['drupalSettings']['KEY']
  // Pattern 2: $variables['#attached']['drupalSettings']['KEY']
  // Pattern 3: $page['#attached']['drupalSettings']['KEY']
  if (preg_match_all("/\['#attached'\]\['drupalSettings'\]\['([a-zA-Z_][a-zA-Z0-9_]*)'\]/", $content, $matches)) {
    foreach ($matches[1] as $key) {
      if (!isset($injections[$moduleName])) {
        $injections[$moduleName] = [];
      }
      $injections[$moduleName][$key] = TRUE;
    }
  }
}

if (empty($injections)) {
  echo "\n=== DRUPAL-SETTINGS-CONSUMERS-001 ===\n\n";
  echo "  ✅ No drupalSettings injections found in custom modules\n";
  echo "\n--- Score: 0/0 keys consumed ---\n\n";
  exit(0);
}

// For each module with injections, scan JS files for consumption.
$totalKeys = 0;
$consumedKeys = 0;

foreach ($injections as $moduleName => $keys) {
  $moduleDir = $modulesDir . '/' . $moduleName;

  // Collect all JS files in the module.
  $jsFiles = [];
  $jsDir = $moduleDir . '/js';
  if (is_dir($jsDir)) {
    $jsFiles = array_merge($jsFiles, glob($jsDir . '/*.js'));
    $jsFiles = array_merge($jsFiles, glob($jsDir . '/**/*.js'));
  }

  // Also check theme JS files — some drupalSettings are consumed by the theme.
  $themeJsDir = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/js';
  $themeJsFiles = [];
  if (is_dir($themeJsDir)) {
    $themeJsFiles = array_merge($themeJsFiles, glob($themeJsDir . '/*.js'));
    $themeJsFiles = array_merge($themeJsFiles, glob($themeJsDir . '/**/*.js'));
  }

  // Read all JS content for this module's files AND theme files.
  $moduleJsContent = '';
  foreach ($jsFiles as $jsFile) {
    $moduleJsContent .= file_get_contents($jsFile) . "\n";
  }
  $themeJsContent = '';
  foreach ($themeJsFiles as $jsFile) {
    $themeJsContent .= file_get_contents($jsFile) . "\n";
  }

  // Also check all other modules' JS (cross-module consumption).
  $allJsContent = $moduleJsContent . $themeJsContent;
  $allModuleJsDirs = glob($modulesDir . '/*/js');
  foreach ($allModuleJsDirs as $otherJsDir) {
    if ($otherJsDir === $jsDir) {
      continue; // Already included.
    }
    foreach (glob($otherJsDir . '/*.js') as $otherJs) {
      $allJsContent .= file_get_contents($otherJs) . "\n";
    }
  }

  foreach ($keys as $key => $_) {
    $totalKeys++;

    // Search for the key in JS: drupalSettings.KEY or drupalSettings['KEY'].
    $found = FALSE;
    $foundIn = '';

    // Check module JS first.
    if (strpos($moduleJsContent, "drupalSettings.$key") !== FALSE
      || strpos($moduleJsContent, "drupalSettings['$key']") !== FALSE
      || strpos($moduleJsContent, "drupalSettings[\"$key\"]") !== FALSE) {
      $found = TRUE;
      // Find the specific file.
      foreach ($jsFiles as $jsFile) {
        $fc = file_get_contents($jsFile);
        if (strpos($fc, "drupalSettings.$key") !== FALSE
          || strpos($fc, "drupalSettings['$key']") !== FALSE
          || strpos($fc, "drupalSettings[\"$key\"]") !== FALSE) {
          $foundIn = basename($jsFile);
          break;
        }
      }
    }

    // Check theme JS.
    if (!$found) {
      if (strpos($themeJsContent, "drupalSettings.$key") !== FALSE
        || strpos($themeJsContent, "drupalSettings['$key']") !== FALSE
        || strpos($themeJsContent, "drupalSettings[\"$key\"]") !== FALSE) {
        $found = TRUE;
        foreach ($themeJsFiles as $jsFile) {
          $fc = file_get_contents($jsFile);
          if (strpos($fc, "drupalSettings.$key") !== FALSE
            || strpos($fc, "drupalSettings['$key']") !== FALSE
            || strpos($fc, "drupalSettings[\"$key\"]") !== FALSE) {
            $foundIn = 'theme/' . basename($jsFile);
            break;
          }
        }
      }
    }

    // Check all other module JS.
    if (!$found) {
      if (strpos($allJsContent, "drupalSettings.$key") !== FALSE
        || strpos($allJsContent, "drupalSettings['$key']") !== FALSE
        || strpos($allJsContent, "drupalSettings[\"$key\"]") !== FALSE) {
        $found = TRUE;
        $foundIn = '(cross-module)';
      }
    }

    if ($found) {
      $consumedKeys++;
      $passes[] = "$moduleName → $key — consumed by $foundIn";
    }
    else {
      $errors[] = "$moduleName → $key — injected but NO JS consumer found";
    }
  }
}

// RESULTS.
echo "\n=== DRUPAL-SETTINGS-CONSUMERS-001 ===\n\n";
foreach ($passes as $msg) {
  echo "  ✅ $msg\n";
}
foreach ($errors as $msg) {
  echo "  ❌ $msg\n";
}
echo "\n--- Score: $consumedKeys/$totalKeys keys consumed ---\n\n";
exit(empty($errors) ? 0 : 1);
