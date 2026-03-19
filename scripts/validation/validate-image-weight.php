<?php

declare(strict_types=1);

/**
 * @file
 * IMAGE-WEIGHT-001: Detects oversized images in theme directories.
 *
 * Large images slow down page load and hurt Core Web Vitals (LCP).
 * This script flags images above thresholds and suggests WebP conversion.
 *
 * Thresholds:
 * - PNG: > 200KB → suggest WebP
 * - JPG: > 300KB → suggest optimization or WebP
 * - WebP: > 200KB → suggest further compression
 * - SVG: > 50KB → suggest SVGO optimization
 *
 * EXIT CODES:
 *   0 = All images within limits
 *   1 = Oversized images found
 */

$root = dirname(__DIR__, 2);
$violations = [];
$totalSize = 0;
$checked = 0;

$thresholds = [
    'png' => 200 * 1024,
    'jpg' => 300 * 1024,
    'jpeg' => 300 * 1024,
    'webp' => 200 * 1024,
    'svg' => 50 * 1024,
    'gif' => 500 * 1024,
];

$imageDirs = [
    $root . '/web/themes/custom/ecosistema_jaraba_theme/images',
    $root . '/web/modules/custom/ecosistema_jaraba_core/images',
];

foreach ($imageDirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        $ext = strtolower($file->getExtension());
        if (!isset($thresholds[$ext])) {
            continue;
        }

        $checked++;
        $size = $file->getSize();
        $totalSize += $size;
        $threshold = $thresholds[$ext];

        if ($size > $threshold) {
            $relPath = str_replace($root . '/', '', $file->getPathname());
            $sizeKb = round($size / 1024);
            $thresholdKb = round($threshold / 1024);
            $violations[] = [
                'file' => $relPath,
                'size_kb' => $sizeKb,
                'threshold_kb' => $thresholdKb,
                'ext' => $ext,
                'suggestion' => match ($ext) {
                    'png' => 'Convert to WebP: cwebp -q 85 input.png -o output.webp',
                    'jpg', 'jpeg' => 'Convert to WebP or compress: cwebp -q 80 input.jpg -o output.webp',
                    'svg' => 'Optimize with SVGO: npx svgo input.svg',
                    'webp' => 'Reduce quality: cwebp -q 70 input.webp -o output.webp',
                    default => 'Optimize or resize',
                },
            ];
        }
    }
}

$totalMb = round($totalSize / (1024 * 1024), 1);

echo "IMAGE-WEIGHT-001: Image Size Optimization\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checked} images ({$totalMb} MB total)\n\n";

if (empty($violations)) {
    echo "✅ PASS — All {$checked} images within size limits.\n";
    exit(0);
}

echo "⚠️  WARNING — " . count($violations) . " oversized image(s):\n\n";

// Sort by size descending.
usort($violations, function ($a, $b) {
    return $b['size_kb'] - $a['size_kb'];
});

$savingsKb = 0;
foreach ($violations as $v) {
    $excess = $v['size_kb'] - $v['threshold_kb'];
    $savingsKb += $excess;
    echo "  {$v['file']}\n";
    echo "    {$v['size_kb']}KB (threshold: {$v['threshold_kb']}KB, excess: {$excess}KB)\n";
    echo "    → {$v['suggestion']}\n\n";
}

$savingsMb = round($savingsKb / 1024, 1);
echo "Potential savings: ~{$savingsMb}MB (converting excess to optimized formats)\n";
// Warning only — not blocking CI.
exit(0);
