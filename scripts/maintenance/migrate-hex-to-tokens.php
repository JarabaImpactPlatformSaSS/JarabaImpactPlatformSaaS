#!/usr/bin/env php
<?php

/**
 * @file
 * Migrates hardcoded hex colors in SCSS to CSS custom property tokens.
 *
 * CSS-VAR-ALL-COLORS-001: Every color in SCSS MUST be var(--ej-*, fallback).
 *
 * Usage:
 *   php scripts/maintenance/migrate-hex-to-tokens.php --dry-run
 *   php scripts/maintenance/migrate-hex-to-tokens.php
 *
 * Safe operations:
 *   - Does NOT touch lines that already use var(--ej-*)
 *   - Does NOT touch SCSS variable declarations ($ej-*)
 *   - Does NOT touch comments
 *   - Does NOT touch SVG inline hex (canvas_data)
 *   - Preserves original hex as fallback value
 */

declare(strict_types=1);

$dryRun = in_array('--dry-run', $argv, TRUE);
$projectRoot = dirname(__DIR__, 2);
$scssDir = $projectRoot . '/web/themes/custom/ecosistema_jaraba_theme/scss';

// Also check module SCSS.
$moduleScssDirs = glob($projectRoot . '/web/modules/custom/*/scss');

$allDirs = array_merge([$scssDir], $moduleScssDirs);

// ─────────────────────────────────────────────────────────
// Token mapping: hex (lowercase) => CSS custom property name.
// ─────────────────────────────────────────────────────────
$tokenMap = [
  // Brand colors.
  '#233d63' => '--ej-color-corporate',
  '#ff8c42' => '--ej-color-impulse',
  '#00a9a5' => '--ej-color-innovation',
  '#556b2f' => '--ej-color-agro',

  // Semantic.
  '#10b981' => '--ej-color-success',
  '#059669' => '--ej-color-success-dark',
  '#f59e0b' => '--ej-color-warning',
  '#d97706' => '--ej-color-warning-dark',
  '#ef4444' => '--ej-color-danger',
  '#dc2626' => '--ej-color-danger-dark',
  '#64748b' => '--ej-color-neutral',

  // Backgrounds.
  '#f8fafc' => '--ej-bg-body',
  '#ffffff' => '--ej-bg-surface',
  '#fff'    => '--ej-bg-surface',
  '#1a1a2e' => '--ej-bg-dark',

  // Text.
  '#334155' => '--ej-color-body',
  '#252538' => '--ej-color-headings',

  // Borders.
  '#e5e7eb' => '--ej-border-color',

  // Grays (Tailwind-based scale used in project).
  '#f1f5f9' => '--ej-gray-100',
  '#e2e8f0' => '--ej-gray-200',
  '#cbd5e1' => '--ej-gray-300',
  '#94a3b8' => '--ej-gray-400',
  '#475569' => '--ej-gray-600',
  '#374151' => '--ej-gray-700',
  '#1e293b' => '--ej-gray-800',
  '#0f172a' => '--ej-gray-900',

  // Dark theme accents.
  '#0f1d30' => '--ej-dark-deeper',
  '#1a2d4a' => '--ej-dark-accent',

  // Status/badge colors.
  '#198754' => '--ej-color-success-alt',
  '#92400e' => '--ej-color-warning-deep',
  '#991b1b' => '--ej-color-danger-deep',
  '#a78bfa' => '--ej-color-purple',

  // Black.
  '#000'    => '--ej-color-black',
  '#000000' => '--ej-color-black',

  // Extended grays (Tailwind).
  '#f3f4f6' => '--ej-gray-50',
  '#d1d5db' => '--ej-gray-350',
  '#6b7280' => '--ej-gray-500',
  '#4b5563' => '--ej-gray-650',
  '#1f2937' => '--ej-gray-800',
  '#111827' => '--ej-gray-900-deep',
  '#9ca3af' => '--ej-gray-400-alt',

  // Blues.
  '#3b82f6' => '--ej-color-blue',
  '#2563eb' => '--ej-color-blue-dark',
  '#1e40af' => '--ej-color-blue-deep',
  '#1976d2' => '--ej-color-blue-md',
  '#dbeafe' => '--ej-color-blue-50',
  '#e3f2fd' => '--ej-color-blue-50-alt',
  '#bfdbfe' => '--ej-color-blue-100',
  '#60a5fa' => '--ej-color-blue-400',

  // Purples.
  '#8b5cf6' => '--ej-color-violet',
  '#6366f1' => '--ej-color-indigo',
  '#764ba2' => '--ej-color-purple-deep',
  '#7c3aed' => '--ej-color-violet-dark',

  // Greens (Material/extended).
  '#065f46' => '--ej-color-green-deep',
  '#2e7d32' => '--ej-color-green-md',
  '#d1fae5' => '--ej-color-green-50',
  '#a7f3d0' => '--ej-color-green-100',
  '#34d399' => '--ej-color-green-400',
  '#047857' => '--ej-color-green-700',
  '#166534' => '--ej-color-green-800',

  // Oranges.
  '#f97316' => '--ej-color-orange',
  '#e67935' => '--ej-color-orange-alt',
  '#e65100' => '--ej-color-orange-deep',
  '#fbbf24' => '--ej-color-amber',
  '#fef3c7' => '--ej-color-amber-50',
  '#fed7aa' => '--ej-color-orange-100',

  // Reds (Material/extended).
  '#c62828' => '--ej-color-red-deep',
  '#b91c1c' => '--ej-color-red-700',
  '#fecaca' => '--ej-color-red-100',
  '#fee2e2' => '--ej-color-red-50',
  '#7f1d1d' => '--ej-color-red-900',

  // Common grays (shorthand).
  '#ccc'    => '--ej-gray-border',
  '#cccccc' => '--ej-gray-border',
  '#666'    => '--ej-gray-text',
  '#666666' => '--ej-gray-text',
  '#333'    => '--ej-gray-dark',
  '#333333' => '--ej-gray-dark',
  '#e0e0e0' => '--ej-gray-divider',
  '#fafafa' => '--ej-gray-lightest',
  '#f5f5f5' => '--ej-gray-light',
  '#eee'    => '--ej-gray-lighter',
  '#eeeeee' => '--ej-gray-lighter',
  '#ddd'    => '--ej-gray-border-light',
  '#dddddd' => '--ej-gray-border-light',
  '#999'    => '--ej-gray-mid',
  '#999999' => '--ej-gray-mid',
  '#aaa'    => '--ej-gray-mid-light',
  '#aaaaaa' => '--ej-gray-mid-light',
  '#777'    => '--ej-gray-mid-dark',
  '#777777' => '--ej-gray-mid-dark',
  '#555'    => '--ej-gray-dark-alt',
  '#555555' => '--ej-gray-dark-alt',
  '#444'    => '--ej-gray-darker',
  '#444444' => '--ej-gray-darker',
  '#222'    => '--ej-gray-darkest',
  '#222222' => '--ej-gray-darkest',
  '#111'    => '--ej-gray-black',
  '#111827' => '--ej-gray-900-deep',

  // Extended grays.
  '#f9fafb' => '--ej-gray-25',
  '#f8f9fa' => '--ej-gray-25-alt',
  '#e9ecef' => '--ej-gray-150',
  '#ced4da' => '--ej-gray-250',
  '#9e9e9e' => '--ej-gray-450',
  '#757575' => '--ej-gray-550',
  '#616161' => '--ej-gray-600-alt',
  '#424242' => '--ej-gray-700-alt',
  '#212121' => '--ej-gray-850',
  '#495057' => '--ej-gray-bootstrap-dark',
  '#6c757d' => '--ej-gray-bootstrap-mid',

  // Material colors.
  '#4caf50' => '--ej-color-green-material',
  '#43a047' => '--ej-color-green-material-dark',
  '#1b5e20' => '--ej-color-green-material-900',
  '#e8f5e9' => '--ej-color-green-material-50',
  '#c8e6c9' => '--ej-color-green-material-100',
  '#a5d6a7' => '--ej-color-green-material-200',
  '#f44336' => '--ej-color-red-material',
  '#e53935' => '--ej-color-red-material-dark',
  '#b71c1c' => '--ej-color-red-material-900',
  '#d32f2f' => '--ej-color-red-material-700',
  '#ffebee' => '--ej-color-red-material-50',
  '#ffcdd2' => '--ej-color-red-material-100',
  '#2196f3' => '--ej-color-blue-material',
  '#1565c0' => '--ej-color-blue-material-dark',
  '#0d47a1' => '--ej-color-blue-material-900',
  '#e1f5fe' => '--ej-color-blue-material-50',
  '#90caf9' => '--ej-color-blue-material-200',
  '#ff9800' => '--ej-color-orange-material',
  '#f57c00' => '--ej-color-orange-material-dark',
  '#e65100' => '--ej-color-orange-material-900',
  '#fff3e0' => '--ej-color-orange-material-50',
  '#ffc107' => '--ej-color-amber-material',
  '#ffb300' => '--ej-color-amber-material-dark',
  '#fff8e1' => '--ej-color-amber-material-50',
  '#ffe082' => '--ej-color-amber-material-200',
  '#ffd54f' => '--ej-color-amber-material-300',
  '#9c27b0' => '--ej-color-purple-material',
  '#7b1fa2' => '--ej-color-purple-material-dark',

  // Status colors (Bootstrap-compatible).
  '#28a745' => '--ej-color-success-bootstrap',
  '#dc3545' => '--ej-color-danger-bootstrap',
  '#17a2b8' => '--ej-color-info-bootstrap',
  '#0891b2' => '--ej-color-cyan',
  '#06b6d4' => '--ej-color-cyan-light',
  '#22d3ee' => '--ej-color-cyan-300',
  '#ecfeff' => '--ej-color-cyan-50',
  '#eab308' => '--ej-color-yellow',
  '#ca8a04' => '--ej-color-yellow-dark',
  '#fde68a' => '--ej-color-yellow-200',
  '#fffbeb' => '--ej-color-yellow-50',

  // Brand colors (social/external).
  '#1877f2' => '--ej-brand-facebook',
  '#1da1f2' => '--ej-brand-twitter',
  '#0077b5' => '--ej-brand-linkedin',
  '#0a66c2' => '--ej-brand-linkedin-alt',
  '#25d366' => '--ej-brand-whatsapp',
  '#ea4335' => '--ej-brand-google-red',
  '#4285f4' => '--ej-brand-google-blue',
  '#fbbc04' => '--ej-brand-google-yellow',
  '#34a853' => '--ej-brand-google-green',

  // Misc.
  '#f0f0f0' => '--ej-color-bg-subtle',
  '#fefefe' => '--ej-color-bg-white-alt',
  '#f7fafc' => '--ej-color-bg-cool',
  '#ec4899' => '--ej-color-pink',
  '#db2777' => '--ej-color-pink-dark',
  '#f472b6' => '--ej-color-pink-300',
  '#faf5ff' => '--ej-color-purple-50',
  '#ede9fe' => '--ej-color-violet-50',
  '#e0e7ff' => '--ej-color-indigo-50',
  '#818cf8' => '--ej-color-indigo-400',
  '#22c55e' => '--ej-color-green-500',
  '#16a34a' => '--ej-color-green-600',
  '#4ade80' => '--ej-color-green-300',
  '#bbf7d0' => '--ej-color-green-200',
  '#dcfce7' => '--ej-color-green-100-alt',
  '#ecfdf5' => '--ej-color-green-50-alt',
  '#f0fdf4' => '--ej-color-green-25',
  '#0ea5e9' => '--ej-color-sky',
  '#eff6ff' => '--ej-color-blue-25',
  '#ea580c' => '--ej-color-orange-600',
  '#9a3412' => '--ej-color-orange-800',
  '#431407' => '--ej-color-orange-950',
  '#78350f' => '--ej-color-amber-900',
  '#fef2f2' => '--ej-color-red-25',
  '#e11d48' => '--ej-color-rose',
  '#ff0000' => '--ej-color-red-pure',
];

// ─────────────────────────────────────────────────────────
// Processing.
// ─────────────────────────────────────────────────────────

$totalFiles = 0;
$totalReplacements = 0;
$filesModified = 0;
$skippedLines = 0;

foreach ($allDirs as $dir) {
  if (!is_dir($dir)) {
    continue;
  }
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
  );

  foreach ($iterator as $file) {
    if ($file->getExtension() !== 'scss') {
      continue;
    }
    $totalFiles++;
    $filePath = $file->getPathname();
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $modified = FALSE;
    $fileReplacements = 0;

    foreach ($lines as $lineNum => &$line) {
      // Skip comment lines.
      $trimmed = ltrim($line);
      if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
        continue;
      }

      // Skip lines that already use var(--ej-).
      if (str_contains($line, 'var(--ej-')) {
        continue;
      }

      // Skip SCSS variable declarations ($ej-*: value).
      if (preg_match('/^\s*\$ej-/', $line)) {
        continue;
      }

      // Skip lines inside SVG data (ICON-CANVAS-INLINE-001).
      if (str_contains($line, 'svg') || str_contains($line, 'data:image') || str_contains($line, 'url(')) {
        continue;
      }

      // Skip sass:color function internals.
      if (str_contains($line, 'color.adjust') || str_contains($line, 'color.mix') || str_contains($line, 'color-mix')) {
        continue;
      }

      // Find hex colors in this line.
      // Match #RRGGBB, #RRGGBBAA, #RGB, but NOT inside var() or strings.
      if (preg_match_all('/#([0-9a-fA-F]{3,8})\b/', $line, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        // Process from right to left to preserve offsets.
        $matchesReversed = array_reverse($matches);
        foreach ($matchesReversed as $match) {
          $fullHex = $match[0][0]; // e.g., #FF8C42
          $offset = $match[0][1];
          $hexLower = strtolower($fullHex);

          // Normalize 3-char hex.
          $lookupKey = $hexLower;

          if (isset($tokenMap[$lookupKey])) {
            $token = $tokenMap[$lookupKey];
            $replacement = "var($token, $fullHex)";

            // Check if this hex is inside a function call like rgba(), color-mix(), etc.
            $before = substr($line, 0, $offset);
            // If inside rgba() or other color function, skip.
            if (preg_match('/(?:rgba?|hsla?|color-mix|color\.adjust|color\.scale)\s*\([^)]*$/', $before)) {
              $skippedLines++;
              continue;
            }

            // Replace.
            $line = substr($line, 0, $offset) . $replacement . substr($line, $offset + strlen($fullHex));
            $modified = TRUE;
            $fileReplacements++;
            $totalReplacements++;
          }
        }
      }
    }
    unset($line);

    if ($modified) {
      $filesModified++;
      $relativePath = str_replace($projectRoot . '/', '', $filePath);

      if ($dryRun) {
        echo "  [DRY] $relativePath: $fileReplacements replacements\n";
      }
      else {
        file_put_contents($filePath, implode("\n", $lines));
        echo "  [WRITE] $relativePath: $fileReplacements replacements\n";
      }
    }
  }
}

echo "\n==============================\n";
echo "SCSS files scanned: $totalFiles\n";
echo "Files modified: $filesModified\n";
echo "Total replacements: $totalReplacements\n";
echo "Skipped (inside functions): $skippedLines\n";
echo "Mode: " . ($dryRun ? "DRY RUN" : "WRITTEN") . "\n";

if ($totalReplacements === 0) {
  echo "\nNo hex colors found matching token map.\n";
}

exit(0);
