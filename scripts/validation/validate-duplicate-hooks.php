<?php

/**
 * @file
 * DUPLICATE-HOOK-001: Detects duplicate function definitions in .module/.theme files.
 *
 * PHP 8.4 throws a fatal error if a function is defined more than once.
 * This validator scans all .module and .theme files for duplicate function names.
 *
 * Usage: php scripts/validation/validate-duplicate-hooks.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  DUPLICATE-HOOK-001                                     ║\033[0m\n";
echo "\033[36m║  Duplicate Function Detection Validator                  ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// Collect all files to scan.
$module_files = glob($root . '/web/modules/custom/*/*.module');
if ($module_files === false) {
  $module_files = [];
}

$theme_files = glob($root . '/web/themes/custom/*/*.theme');
if ($theme_files === false) {
  $theme_files = [];
}

// Also check .install files (they can have functions too).
$install_files = glob($root . '/web/modules/custom/*/*.install');
if ($install_files === false) {
  $install_files = [];
}

$all_files = array_merge($module_files, $theme_files, $install_files);
$total_files = count($all_files);
$total_duplicates = 0;
$clean_files = 0;

foreach ($all_files as $file) {
  $content = file_get_contents($file);
  if ($content === false) {
    continue;
  }

  $basename = basename($file);
  $lines = explode("\n", $content);

  // Find all function definitions with line numbers.
  $functions = [];
  foreach ($lines as $line_num => $line) {
    // Match function definitions: function name(
    if (preg_match('/^\s*function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $line, $match)) {
      $func_name = $match[1];
      if (!isset($functions[$func_name])) {
        $functions[$func_name] = [];
      }
      $functions[$func_name][] = $line_num + 1; // 1-based line numbers.
    }
  }

  // Check for duplicates.
  $file_has_duplicates = false;
  foreach ($functions as $func_name => $line_numbers) {
    if (count($line_numbers) > 1) {
      $file_has_duplicates = true;
      $total_duplicates++;
      $lines_str = implode(', ', $line_numbers);
      $errors[] = "$basename: function $func_name() defined " . count($line_numbers) . " times (lines $lines_str)";
    }
  }

  if (!$file_has_duplicates) {
    $clean_files++;
  }
}

// Summary.
if ($clean_files === $total_files) {
  $passes[] = "All $total_files files scanned — 0 duplicate functions found";
} else {
  $passes[] = "$clean_files/$total_files files are clean (no duplicates)";
}

// ── REPORT ────────────────────────────────────────────────────────────
echo "Scanned: " . count($module_files) . " module files + " . count($theme_files) . " theme files + " . count($install_files) . " install files\n\n";

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
  echo ", " . count($errors) . " FAIL ($total_duplicates duplicates)";
}
echo "\n═══════════════════════════════════════════════════════════\n";

exit(empty($errors) ? 0 : 1);
