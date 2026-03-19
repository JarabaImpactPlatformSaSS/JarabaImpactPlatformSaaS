<?php

/**
 * @file
 * LIBRARY-ATTACHMENT-001: Validates bundle library completeness.
 *
 * Checks that CSS bundle libraries referenced in controllers are declared
 * in libraries.yml and have existing CSS files on disk.
 *
 * Usage: php scripts/validation/validate-library-attachments.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  LIBRARY-ATTACHMENT-001                                  ║\033[0m\n";
echo "\033[36m║  Bundle Library Completeness Validator                   ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// ── STEP 1: Load all libraries.yml files ──────────────────────────────
$libraries_map = [];

// Theme libraries.
$theme_lib_file = $root . '/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml';
if (file_exists($theme_lib_file)) {
  $theme_lib_content = file_get_contents($theme_lib_file);
  // Parse library names (top-level keys without indentation).
  if (preg_match_all('/^([a-z][a-z0-9_-]*)\s*:/m', $theme_lib_content, $lib_matches)) {
    foreach ($lib_matches[1] as $lib_name) {
      $libraries_map['ecosistema_jaraba_theme/' . $lib_name] = $theme_lib_file;
    }
  }

  // Also extract CSS file paths per library for existence check.
  $theme_lib_css = [];
  $current_lib = null;
  $lines = explode("\n", $theme_lib_content);
  foreach ($lines as $line) {
    // Top-level library key.
    if (preg_match('/^([a-z][a-z0-9_-]*)\s*:/', $line, $m)) {
      $current_lib = $m[1];
    }
    // CSS file reference (indented, ends with :).
    if ($current_lib && preg_match('/^\s+(css\/[^\s:]+\.css)\s*:/', $line, $m)) {
      $theme_lib_css['ecosistema_jaraba_theme/' . $current_lib][] = $m[1];
    }
  }
}

// Module libraries.
$module_lib_files = glob($root . '/web/modules/custom/*/*.libraries.yml');
if ($module_lib_files === false) {
  $module_lib_files = [];
}
$module_lib_css = [];

foreach ($module_lib_files as $lib_file) {
  $module_name = basename(dirname($lib_file));
  $content = file_get_contents($lib_file);
  if ($content === false) {
    continue;
  }
  if (preg_match_all('/^([a-z][a-z0-9_-]*)\s*:/m', $content, $lib_matches)) {
    foreach ($lib_matches[1] as $lib_name) {
      $libraries_map[$module_name . '/' . $lib_name] = $lib_file;
    }
  }
  // Extract CSS paths.
  $current_lib = null;
  $lines = explode("\n", $content);
  foreach ($lines as $line) {
    if (preg_match('/^([a-z][a-z0-9_-]*)\s*:/', $line, $m)) {
      $current_lib = $m[1];
    }
    if ($current_lib && preg_match('/^\s+(css\/[^\s:]+\.css)\s*:/', $line, $m)) {
      $module_lib_css[$module_name . '/' . $current_lib][] = $m[1];
    }
  }
}

// ── STEP 2: Scan controllers and .module files for library references ─
$controller_files = glob($root . '/web/modules/custom/*/src/Controller/*.php');
if ($controller_files === false) {
  $controller_files = [];
}
$module_files = glob($root . '/web/modules/custom/*/*.module');
if ($module_files === false) {
  $module_files = [];
}
$theme_file = $root . '/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';

$scan_files = array_merge($controller_files, $module_files);
if (file_exists($theme_file)) {
  $scan_files[] = $theme_file;
}

$referenced_libraries = [];
$scanned_count = 0;

foreach ($scan_files as $file) {
  $content = file_get_contents($file);
  if ($content === false) {
    continue;
  }
  $scanned_count++;

  // Match library references: 'module_or_theme/library-name' patterns.
  // Common patterns:
  //   '#attached' => ['library' => ['ecosistema_jaraba_theme/bundle-xyz']]
  //   'library' => 'ecosistema_jaraba_theme/route-xyz'
  if (preg_match_all("/['\"]([a-z_]+\/[a-z][a-z0-9_-]*)['\"]/" , $content, $lib_refs)) {
    foreach ($lib_refs[1] as $lib_ref) {
      // Filter to only actual library references (module/name format).
      if (preg_match('/^(ecosistema_jaraba_theme|jaraba_[a-z_]+|ecosistema_jaraba_core)\//', $lib_ref)) {
        $referenced_libraries[$lib_ref][] = basename($file);
      }
    }
  }
}

// ── STEP 3: Validate each referenced library ──────────────────────────
$checked = 0;
foreach ($referenced_libraries as $lib_ref => $sources) {
  $checked++;
  $source_str = implode(', ', array_unique($sources));

  // Check declaration.
  if (!isset($libraries_map[$lib_ref])) {
    $errors[] = "$lib_ref — NOT declared in any libraries.yml (referenced in: $source_str)";
    continue;
  }

  // Check CSS file existence.
  $css_paths = [];
  if (isset($theme_lib_css[$lib_ref])) {
    $css_paths = $theme_lib_css[$lib_ref];
  } elseif (isset($module_lib_css[$lib_ref])) {
    $css_paths = $module_lib_css[$lib_ref];
  }

  if (!empty($css_paths)) {
    $all_exist = true;
    $missing_css = [];
    foreach ($css_paths as $css_path) {
      // Resolve relative to the library file's directory.
      $lib_dir = dirname($libraries_map[$lib_ref]);
      $full_css = $lib_dir . '/' . $css_path;
      if (!file_exists($full_css)) {
        // For theme libraries, check from theme dir.
        $theme_dir = $root . '/web/themes/custom/ecosistema_jaraba_theme';
        $alt_path = $theme_dir . '/' . $css_path;
        if (!file_exists($alt_path)) {
          $all_exist = false;
          $missing_css[] = $css_path;
        }
      }
    }
    if ($all_exist) {
      $passes[] = "$lib_ref — declared + CSS exists";
    } else {
      $errors[] = "$lib_ref — declared but CSS file(s) missing: " . implode(', ', $missing_css);
    }
  } else {
    // Library declared but no CSS (might be JS-only, that's OK).
    $passes[] = "$lib_ref — declared (no CSS files to verify)";
  }
}

// ── REPORT ────────────────────────────────────────────────────────────
echo "Scanned: $scanned_count PHP files, found $checked unique library references\n";
echo "Libraries indexed: " . count($libraries_map) . " across theme + modules\n\n";

foreach ($passes as $p) {
  echo "  \033[32m✓\033[0m $p\n";
}
foreach ($warnings as $w) {
  echo "  \033[33m⚠\033[0m $w\n";
}
foreach ($errors as $e) {
  echo "  \033[31m✗\033[0m $e\n";
}

$total = count($passes) + count($errors);
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULT: " . count($passes) . "/$total PASS";
if (!empty($warnings)) {
  echo ", " . count($warnings) . " WARN";
}
if (!empty($errors)) {
  echo ", " . count($errors) . " FAIL";
}
echo "\n═══════════════════════════════════════════════════════════\n";

exit(empty($errors) ? 0 : 1);
