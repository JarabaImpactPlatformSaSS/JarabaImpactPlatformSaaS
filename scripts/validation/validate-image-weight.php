<?php

/**
 * @file
 * IMAGE-WEIGHT-001: Detects oversized images in the theme directory.
 *
 * Thresholds by category:
 *   - Logos (logo-*): max 100KB
 *   - Case study/quiz/hero images: max 500KB
 *   - Other images: max 300KB
 *
 * Logo violations are failures (served on every page).
 * Other violations are warnings.
 *
 * Usage: php scripts/validation/validate-image-weight.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$imagesDir = "$root/web/themes/custom/ecosistema_jaraba_theme/images";

$failures = [];
$warnings = [];

echo "=== IMAGE-WEIGHT-001: Theme Image Size Audit ===\n\n";

if (!is_dir($imagesDir)) {
  echo "❌ Images directory not found: $imagesDir\n";
  exit(1);
}

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($imagesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$totalFiles = 0;
$oversized = [];
$totalSizeKB = 0;

foreach ($iterator as $file) {
  if (!$file->isFile()) {
    continue;
  }

  $ext = strtolower($file->getExtension());
  if (!in_array($ext, ['png', 'webp', 'jpg', 'jpeg'], TRUE)) {
    continue;
  }

  $totalFiles++;
  $sizeKB = (int) round($file->getSize() / 1024);
  $totalSizeKB += $sizeKB;
  $relativePath = str_replace("$imagesDir/", '', $file->getPathname());

  // Determine threshold based on file pattern.
  $threshold = 300;
  $category = 'general';

  if (str_starts_with($relativePath, 'logo-') || str_starts_with($relativePath, 'trust-logos/')) {
    $threshold = 100;
    $category = 'logo';
  } elseif (str_contains($relativePath, 'case-study') || str_contains($relativePath, 'cs-')) {
    $threshold = 500;
    $category = 'case-study';
  } elseif (str_contains($relativePath, 'quiz/') || str_contains($relativePath, 'pickers/')) {
    $threshold = 500;
    $category = 'quiz/picker';
  } elseif (str_contains($relativePath, 'hero') || str_contains($relativePath, 'fundador')) {
    $threshold = 500;
    $category = 'hero';
  }

  if ($sizeKB > $threshold) {
    $oversized[] = [
      'path' => $relativePath,
      'size' => $sizeKB,
      'threshold' => $threshold,
      'category' => $category,
      'excess' => $sizeKB - $threshold,
    ];
  }
}

$savingsKB = array_sum(array_column($oversized, 'excess'));
echo "Scanned: $totalFiles image files (" . round($totalSizeKB / 1024, 1) . " MB total)\n\n";

if (empty($oversized)) {
  echo "✅ All images within size thresholds.\n";
} else {
  echo "⚠️  " . count($oversized) . " oversized images (potential savings: ~" . round($savingsKB / 1024, 1) . " MB)\n\n";

  foreach ($oversized as $img) {
    $severity = $img['excess'] > 500 ? '🔴' : ($img['excess'] > 200 ? '🟡' : '🟢');
    echo "  $severity {$img['size']}KB > {$img['threshold']}KB [{$img['category']}] {$img['path']}\n";
  }

  echo "\n💡 Optimize: optipng -o5 <file> OR cwebp -q 85 <file> -o <file>.webp\n";

  $logoOversized = array_filter($oversized, fn($i) => $i['category'] === 'logo');
  if (!empty($logoOversized)) {
    foreach ($logoOversized as $img) {
      $failures[] = "Logo {$img['path']} is {$img['size']}KB (max {$img['threshold']}KB)";
    }
  }
  foreach ($oversized as $img) {
    if ($img['category'] !== 'logo') {
      $warnings[] = "{$img['path']} is {$img['size']}KB (max {$img['threshold']}KB)";
    }
  }
}

echo "\n=== RESULT ===\n";

if (!empty($failures)) {
  echo "❌ IMAGE-WEIGHT-001: " . count($failures) . " logo images exceed threshold.\n";
  foreach ($failures as $f) {
    echo "  - $f\n";
  }
  exit(1);
}

if (!empty($warnings)) {
  echo "⚠️  IMAGE-WEIGHT-001: Passed with " . count($warnings) . " warnings (non-logo images).\n";
  exit(0);
}

echo "✅ IMAGE-WEIGHT-001: All images within thresholds.\n";
exit(0);
