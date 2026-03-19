<?php

/**
 * @file
 * SCSS-VARIABLE-EXIST-001: Validates SCSS $ej-* variable existence.
 *
 * Ensures that all $ej-* prefixed variables used in SCSS files are defined
 * in _variables.scss. Undefined variables cause Sass compilation failure.
 *
 * Usage: php scripts/validation/validate-scss-variables.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  SCSS-VARIABLE-EXIST-001                                ║\033[0m\n";
echo "\033[36m║  SCSS Variable Existence Validator                       ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// ── STEP 1: Collect all variable definitions ──────────────────────────
$defined_vars = [];

$variable_files = [
  $root . '/web/themes/custom/ecosistema_jaraba_theme/scss/_variables.scss',
  $root . '/web/modules/custom/ecosistema_jaraba_core/scss/_variables.scss',
];

foreach ($variable_files as $var_file) {
  if (!file_exists($var_file)) {
    continue;
  }
  $content = file_get_contents($var_file);
  if ($content === false) {
    continue;
  }
  // Match variable definitions: $ej-something: value;
  if (preg_match_all('/(\$ej-[a-zA-Z0-9_-]+)\s*:/', $content, $matches)) {
    foreach ($matches[1] as $var) {
      $defined_vars[$var] = basename($var_file);
    }
  }
}

echo "Defined: " . count($defined_vars) . " \$ej-* variables in _variables.scss files\n";

// ── STEP 2: Scan all SCSS files for $ej-* usage ──────────────────────
$theme_scss_dir = $root . '/web/themes/custom/ecosistema_jaraba_theme/scss';
$module_scss_dir = $root . '/web/modules/custom/ecosistema_jaraba_core/scss';

$scss_files = [];

// Recursively find all .scss files.
$dirs_to_scan = [];
if (is_dir($theme_scss_dir)) {
  $dirs_to_scan[] = $theme_scss_dir;
}
if (is_dir($module_scss_dir)) {
  $dirs_to_scan[] = $module_scss_dir;
}

foreach ($dirs_to_scan as $dir) {
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
  );
  foreach ($iterator as $file) {
    if ($file->getExtension() === 'scss') {
      $scss_files[] = $file->getPathname();
    }
  }
}

echo "Scanned: " . count($scss_files) . " SCSS files\n\n";

$files_checked = 0;
$files_clean = 0;

foreach ($scss_files as $scss_file) {
  $content = file_get_contents($scss_file);
  if ($content === false) {
    continue;
  }

  $files_checked++;
  $relative = str_replace($root . '/', '', $scss_file);
  $basename = basename($scss_file);

  // Skip the _variables.scss files themselves.
  if ($basename === '_variables.scss') {
    $files_clean++;
    continue;
  }

  // Collect local definitions in this file (variables defined here).
  $local_vars = [];
  if (preg_match_all('/(\$ej-[a-zA-Z0-9_-]+)\s*:/', $content, $local_matches)) {
    foreach ($local_matches[1] as $var) {
      $local_vars[$var] = true;
    }
  }

  // Find all $ej-* usages (NOT in definition context — i.e., not followed by ':').
  $lines = explode("\n", $content);
  $file_errors = [];
  $in_block_comment = false;

  foreach ($lines as $line_num => $line) {
    // Track block comments.
    if (str_contains($line, '/*')) {
      $in_block_comment = true;
    }
    if ($in_block_comment) {
      if (str_contains($line, '*/')) {
        $in_block_comment = false;
      }
      continue;
    }
    // Skip single-line comments.
    $trimmed = ltrim($line);
    if (str_starts_with($trimmed, '//')) {
      continue;
    }

    // Find all $ej-* references in this line.
    if (preg_match_all('/(\$ej-[a-zA-Z0-9_-]+)/', $line, $usage_matches)) {
      foreach ($usage_matches[1] as $var) {
        // Skip if this is a definition line for this var.
        if (preg_match('/' . preg_quote($var, '/') . '\s*:/', $line)) {
          continue;
        }
        // Skip if defined in _variables.scss or locally in this file.
        if (isset($defined_vars[$var]) || isset($local_vars[$var])) {
          continue;
        }
        $file_errors[] = [
          'var' => $var,
          'line' => $line_num + 1,
        ];
      }
    }
  }

  if (empty($file_errors)) {
    $files_clean++;
  } else {
    // Deduplicate by variable name.
    $unique_errors = [];
    foreach ($file_errors as $err) {
      $key = $err['var'];
      if (!isset($unique_errors[$key])) {
        $unique_errors[$key] = $err;
      }
    }
    foreach ($unique_errors as $err) {
      $short_path = basename(dirname($relative)) . '/' . $basename;
      $errors[] = "$short_path:{$err['line']} uses {$err['var']} — NOT DEFINED in _variables.scss";
    }
  }
}

if ($files_clean === $files_checked) {
  $passes[] = "All $files_checked SCSS files have valid \$ej-* variable references";
} else {
  $passes[] = "$files_clean/$files_checked SCSS files have valid \$ej-* variable references";
}

// ── REPORT ────────────────────────────────────────────────────────────
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
