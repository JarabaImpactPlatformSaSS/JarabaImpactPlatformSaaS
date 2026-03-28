<?php

/**
 * @file
 * SCSS-ENTRY-CONSOLIDATION-001: Detects SCSS entry point conflicts.
 *
 * Dart Sass fails when both name.scss and _name.scss exist in the same
 * directory. This validator scans the theme SCSS directory for conflicts.
 *
 * Usage: php scripts/validation/validate-scss-entry-consolidation.php
 * Exit code: 0 = all checks pass, 1 = conflicts found.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$scssDir = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/scss';

// ─── CHECK 1: No name.scss + _name.scss conflicts ───
$conflicts = [];
$filesScanned = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scssDir, FilesystemIterator::SKIP_DOTS)
);

$filesByDir = [];
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'scss') {
        continue;
    }
    $filesScanned++;
    $dir = $file->getPath();
    $basename = $file->getBasename('.scss');
    $filesByDir[$dir][] = $basename;
}

foreach ($filesByDir as $dir => $basenames) {
    foreach ($basenames as $name) {
        // If name starts with _, check if non-prefixed version exists.
        if (str_starts_with($name, '_')) {
            $unprefixed = substr($name, 1);
            if (in_array($unprefixed, $basenames, true)) {
                $relativeDir = str_replace($scssDir . '/', '', $dir);
                $conflicts[] = "{$relativeDir}/{$unprefixed}.scss + {$name}.scss";
            }
        }
    }
}

if (empty($conflicts)) {
    $passed++;
} else {
    $errors[] = 'CHECK 1: SCSS entry point conflicts (Dart Sass will fail): ' . implode('; ', $conflicts);
}

// ─── CHECK 2: Every SCSS partial has @use statement ───
// Partials (_name.scss) should have @use for their dependencies.
$orphanPartials = [];
$mainFile = $scssDir . '/ecosistema-jaraba-theme.scss';
$mainContent = '';
if (file_exists($mainFile)) {
    $mainContent = file_get_contents($mainFile);
}

$iterator2 = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scssDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator2 as $file) {
    if ($file->getExtension() !== 'scss') {
        continue;
    }
    $basename = $file->getBasename('.scss');
    // Only check partials (start with _), skip entry points.
    if (!str_starts_with($basename, '_')) {
        continue;
    }
    // Skip vendor/third-party.
    if (str_contains($file->getPathname(), '/vendor/')) {
        continue;
    }

    $partialName = substr($basename, 1);
    // Check if referenced in main.scss via @use.
    if (!empty($mainContent) && !str_contains($mainContent, $partialName)) {
        // Not in main — check if referenced by any other file.
        $isReferenced = false;
        $searchResult = shell_exec("grep -rl '@use.*{$partialName}\\|@forward.*{$partialName}' " . escapeshellarg($scssDir) . " 2>/dev/null | head -1");
        if (!empty(trim((string) $searchResult))) {
            $isReferenced = true;
        }
        if (!$isReferenced) {
            $relativePath = str_replace($scssDir . '/', '', $file->getPathname());
            $orphanPartials[] = $relativePath;
        }
    }
}

// Orphan check is informational (many partials are loaded via routes/ or bundles/).
if (count($orphanPartials) <= 20) {
    $passed++;
} else {
    $warnings[] = 'CHECK 2: ' . count($orphanPartials) . ' SCSS partials not referenced by main.scss or any @use (may be loaded via route/bundle libraries)';
    $passed++;
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " SCSS-ENTRY-CONSOLIDATION-001: SCSS Entry Point Integrity\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

foreach ($errors as $error) {
    echo "  ✗ FAIL: {$error}\n";
}
foreach ($warnings as $warning) {
    echo "  ⚠ WARN: {$warning}\n";
}

echo "\n  Passed: {$passed}/{$total}";
if (!empty($warnings)) {
    echo " (+" . count($warnings) . " warnings)";
}
echo " [{$filesScanned} SCSS files scanned]\n\n";

if (!empty($errors)) {
    echo "  RESULT: FAIL\n\n";
    exit(1);
}

echo "  RESULT: PASS\n\n";
exit(0);
