<?php

/**
 * @file
 * TWIG-RAW-AUDIT-001: Audit all |raw usages in custom Twig templates.
 *
 * Scans templates in web/modules/custom/ and web/themes/custom/ for |raw.
 * Reports usages that are not in the known-safe allowlist.
 *
 * Safe patterns (allowlisted):
 * - |json_encode|raw (Schema.org JSON-LD)
 * - |striptags(...)|raw (filtered HTML embeds like maps)
 * - jaraba_icon()|raw (SVG icon system)
 * - Variables with AUDIT-SEC comment in the template
 *
 * @see AUDIT-SEC-003
 * @see docs/tecnicos/auditorias/20260326-Auditoria_Seguridad_Produccion_IONOS_v1_Claude.md
 */

declare(strict_types=1);

$rootDir = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$safeCount = 0;

// Directories to scan.
$scanDirs = [
  $rootDir . '/web/modules/custom',
  $rootDir . '/web/themes/custom',
];

// Safe patterns that do not require manual audit.
$safePatterns = [
  '|json_encode|raw',
  '|json_encode()|raw',
  '|striptags(',
  'jaraba_icon(',
  '<!-- AUDIT-SEC:',
  'AUDIT-SEC-003',
  'svg_content|raw',
  'icon_svg|raw',
];

foreach ($scanDirs as $scanDir) {
  if (!is_dir($scanDir)) {
    continue;
  }

  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scanDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
  );

  foreach ($iterator as $file) {
    if ($file->getExtension() !== 'twig') {
      continue;
    }

    $filePath = $file->getPathname();
    $relativePath = str_replace($rootDir . '/', '', $filePath);
    $lines = file($filePath, FILE_IGNORE_NEW_LINES);

    foreach ($lines as $lineNum => $line) {
      // Skip Twig comments.
      if (preg_match('/^\s*\{#/', $line)) {
        continue;
      }

      // Check for |raw usage.
      if (strpos($line, '|raw') === false) {
        continue;
      }

      // Check if this is a safe pattern.
      $isSafe = false;
      foreach ($safePatterns as $pattern) {
        if (strpos($line, $pattern) !== false) {
          $isSafe = true;
          break;
        }
      }

      // Check if previous line has AUDIT-SEC comment.
      if (!$isSafe && $lineNum > 0) {
        $prevLine = $lines[$lineNum - 1] ?? '';
        if (strpos($prevLine, 'AUDIT-SEC') !== false) {
          $isSafe = true;
        }
      }

      if ($isSafe) {
        $safeCount++;
        continue;
      }

      // Extract variable name from the |raw usage.
      $varName = '(unknown)';
      if (preg_match('/\{\{\s*([a-zA-Z_][a-zA-Z0-9_.]*)\s*\|/', $line, $matches)) {
        $varName = $matches[1];
      }

      $lineNumber = $lineNum + 1;
      $trimmedLine = trim($line);
      $warnings[] = sprintf(
        "  %s:%d — variable '%s': %s",
        $relativePath,
        $lineNumber,
        $varName,
        substr($trimmedLine, 0, 120)
      );
    }
  }
}

// Output results.
$totalRaw = count($warnings) + $safeCount;
echo "TWIG-RAW-AUDIT-001: Scanned for |raw in custom templates\n";
echo "  Total |raw usages: {$totalRaw}\n";
echo "  Safe (allowlisted): {$safeCount}\n";
echo "  Require review: " . count($warnings) . "\n";

if (!empty($warnings)) {
  echo "\n⚠ Usages requiring manual verification of sanitization chain:\n";
  foreach ($warnings as $warning) {
    echo $warning . "\n";
  }
  echo "\nTo mark a usage as audited, add '<!-- AUDIT-SEC: verified YYYY-MM-DD -->' above the line.\n";
  // Exit with warning (non-zero) to flag for review.
  exit(1);
}

echo "\n✅ All |raw usages are in the known-safe allowlist.\n";
exit(0);
