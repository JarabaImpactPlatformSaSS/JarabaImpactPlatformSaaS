<?php

/**
 * @file
 * ICON-DYNAMIC-001: Validates icons from getIcon() in SetupWizard + DailyActions.
 *
 * Static icon references in Twig/PHP are validated by validate-icon-references.php
 * (ICON-INTEGRITY-001). But wizard steps and daily actions define icons dynamically
 * via getIcon() arrays — those can't be caught statically.
 *
 * This script:
 * 1. Scans all PHP files in SetupWizard/ and DailyActions/ directories
 * 2. Extracts 'category' => '...', 'name' => '...' pairs from getIcon() methods
 * 3. Verifies both outline (.svg) and duotone (-duotone.svg) exist
 * 4. Reports missing icons that would cause pushpin fallback or broken images
 *
 * Usage: php scripts/validation/validate-dynamic-icon-refs.php
 * Exit: 0 = pass, 1 = failures found
 */

declare(strict_types=1);

$iconsBase = __DIR__ . '/../../web/modules/custom/ecosistema_jaraba_core/images/icons';
$modulesBase = __DIR__ . '/../../web/modules/custom';

$errors = [];
$checked = 0;

// Scan all SetupWizard and DailyActions PHP files.
$dirs = [
    'SetupWizard',
    'DailyActions',
    'UserProfile/Section',
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($modulesBase, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $inTargetDir = false;
    foreach ($dirs as $dir) {
        if (str_contains($path, "/{$dir}/")) {
            $inTargetDir = true;
            break;
        }
    }
    if (!$inTargetDir) {
        continue;
    }

    $content = file_get_contents($path);
    $basename = basename($path);

    // Skip interfaces, registries, compiler passes.
    if (str_contains($basename, 'Interface') || str_contains($basename, 'Registry') || str_contains($basename, 'CompilerPass')) {
        continue;
    }

    // Extract category/name pairs from getIcon() or icon arrays.
    if (preg_match_all("/['\"]category['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $content, $cats, PREG_OFFSET_CAPTURE)) {
        foreach ($cats[1] as $i => $catMatch) {
            $category = $catMatch[0];
            $offset = $catMatch[1];

            // Find the nearest 'name' => '...' after this category match.
            $rest = substr($content, $offset);
            if (preg_match("/['\"]name['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $rest, $nameMatch)) {
                $name = $nameMatch[1];
                $checked++;

                $outlinePath = "{$iconsBase}/{$category}/{$name}.svg";
                $duotonePath = "{$iconsBase}/{$category}/{$name}-duotone.svg";

                if (!file_exists($outlinePath)) {
                    $errors[] = "❌ MISSING outline: {$category}/{$name}.svg (from {$basename})";
                }
                if (!file_exists($duotonePath)) {
                    $errors[] = "⚠️  MISSING duotone: {$category}/{$name}-duotone.svg (from {$basename})";
                }
            }
        }
    }
}

// Output results.
echo "ICON-DYNAMIC-001: Dynamic Icon Reference Validation\n";
echo "============================================================\n";
echo "Scanned: SetupWizard + DailyActions + UserProfile/Section\n";
echo "Checked: {$checked} icon references\n";
echo "Icons base: web/modules/custom/ecosistema_jaraba_core/images/icons/\n\n";

if (empty($errors)) {
    echo "✅ PASS — All {$checked} dynamic icon references resolve to existing SVG files.\n";
    exit(0);
}

$outlineErrors = array_filter($errors, fn($e) => str_starts_with($e, '❌'));
$duotoneErrors = array_filter($errors, fn($e) => str_starts_with($e, '⚠'));

echo count($outlineErrors) . " outline errors (cause pushpin fallback):\n";
foreach ($outlineErrors as $err) {
    echo "  {$err}\n";
}

if (!empty($duotoneErrors)) {
    echo "\n" . count($duotoneErrors) . " duotone warnings (cause broken img or outline fallback):\n";
    foreach ($duotoneErrors as $err) {
        echo "  {$err}\n";
    }
}

echo "\n❌ FAIL — " . count($errors) . " missing icon files found.\n";
echo "Fix: Create the missing SVG files or update the icon references.\n";
echo "Hint: Run 'php scripts/validation/validate-icon-references.php' to also check static refs.\n";
exit(1);
