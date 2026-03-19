<?php

/**
 * @file
 * TWIG-INCLUDE-ONLY-001: Validates Twig include uses 'only' keyword for partials.
 *
 * Without 'only', ALL parent template variables leak into partials, causing
 * collisions between render arrays and expected string variables.
 * Only flags includes that have 'with {' but NOT 'only'.
 *
 * Usage: php scripts/validation/validate-twig-include-only.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  TWIG-INCLUDE-ONLY-001                                  ║\033[0m\n";
echo "\033[36m║  Twig Include Only Verification Validator                ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// Collect all Twig files.
$twig_dirs = [
  $root . '/web/themes/custom/ecosistema_jaraba_theme/templates',
  $root . '/web/modules/custom/ecosistema_jaraba_core/templates',
];

// Add all module template dirs.
$module_template_dirs = glob($root . '/web/modules/custom/*/templates');
if ($module_template_dirs === false) {
  $module_template_dirs = [];
}
$twig_dirs = array_merge($twig_dirs, $module_template_dirs);
$twig_dirs = array_unique($twig_dirs);

$twig_files = [];
foreach ($twig_dirs as $dir) {
  if (!is_dir($dir)) {
    continue;
  }
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
  );
  foreach ($iterator as $file) {
    if (str_ends_with($file->getFilename(), '.html.twig')) {
      $twig_files[] = $file->getPathname();
    }
  }
}

$total_files = count($twig_files);
$total_includes = 0;
$with_only_count = 0;
$without_only_count = 0;
$no_with_count = 0;

foreach ($twig_files as $twig_file) {
  $content = file_get_contents($twig_file);
  if ($content === false) {
    continue;
  }

  $basename = basename($twig_file);
  $lines = explode("\n", $content);

  foreach ($lines as $line_num => $line) {
    // Match {% include ... %} statements.
    // Can span multiple lines, but most are single-line. Handle single-line first.
    if (!preg_match('/\{%[-~]?\s*include\s+/', $line)) {
      continue;
    }

    $total_includes++;

    // Extract the included template name.
    if (!preg_match("/include\s+['\"]([^'\"]+)['\"]/", $line, $name_match)) {
      // Dynamic include or variable — skip.
      continue;
    }

    $included_template = $name_match[1];

    // Check if it's a partial (starts with _ or in partials/ directory).
    $is_partial = false;
    if (str_starts_with(basename($included_template), '_')) {
      $is_partial = true;
    }
    if (str_contains($included_template, 'partials/')) {
      $is_partial = true;
    }

    if (!$is_partial) {
      continue;
    }

    // Check if include has 'with {' (passes variables).
    // We need to check the full include statement which may span lines.
    // First try single line.
    $has_with = false;
    $has_only = false;

    // Build the complete include statement (may span to closing %}).
    $include_stmt = $line;
    $check_line = $line_num;
    while (!str_contains($include_stmt, '%}') && $check_line < count($lines) - 1) {
      $check_line++;
      $include_stmt .= ' ' . $lines[$check_line];
    }

    $has_with = (bool) preg_match('/\bwith\s*\{/', $include_stmt);
    $has_only = (bool) preg_match('/\bonly\s*[-~]?%\}/', $include_stmt);

    if (!$has_with) {
      // No 'with' — simple include without passing variables. Not flagged.
      $no_with_count++;
      continue;
    }

    if ($has_only) {
      $with_only_count++;
      $passes[] = "$basename:" . ($line_num + 1) . " — includes $included_template with 'only'";
    } else {
      $without_only_count++;
      $errors[] = "$basename:" . ($line_num + 1) . " — includes $included_template with variables but WITHOUT 'only'";
    }
  }
}

// Add summary pass if all are correct.
if ($without_only_count === 0 && $with_only_count > 0) {
  // Replace individual passes with summary.
  $passes = ["All $with_only_count includes with variables correctly use 'only' keyword"];
}

// ── REPORT ────────────────────────────────────────────────────────────
echo "Scanned: $total_files Twig files, $total_includes include statements\n";
echo "  With 'only': $with_only_count | Missing 'only': $without_only_count | No 'with': $no_with_count\n\n";

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
