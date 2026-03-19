<?php

declare(strict_types=1);

/**
 * @file
 * ICON-INTEGRITY-001: Validates that all jaraba_icon() calls reference existing SVG files.
 *
 * Scans Twig templates and PHP preprocess files for jaraba_icon() calls,
 * extracts category + name + variant, and verifies the corresponding SVG
 * file exists on disk. Catches the "chincheta fallback" problem before deploy.
 *
 * SCAN TARGETS:
 * - Twig: {{ jaraba_icon('category', 'name', { variant: 'duotone' }) }}
 * - PHP:  'icon_cat' => 'category', 'icon_name' => 'name' (preprocess arrays)
 *
 * EXIT CODES:
 *   0 = All icon references resolve to existing SVG files
 *   1 = Missing icons found (would fall back to emoji)
 *
 * @see ICON-CONVENTION-001
 * @see ICON-DUOTONE-001
 */

$root = dirname(__DIR__, 2);
$iconsBase = $root . '/web/modules/custom/ecosistema_jaraba_core/images/icons';

$scanDirs = [
    $root . '/web/themes/custom/ecosistema_jaraba_theme/templates',
    $root . '/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme',
    $root . '/web/modules/custom/ecosistema_jaraba_core/templates',
];

// Also scan PHP files that define icon_cat/icon_name arrays.
$phpScanDirs = [
    $root . '/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme',
    $root . '/web/modules/custom/ecosistema_jaraba_core/src',
];

$violations = [];
$checkedRefs = 0;
$checkedFiles = 0;

// Default variants used when none specified (ICON-DUOTONE-001 says default is duotone in templates).
$defaultVariants = ['outline', 'duotone'];

/**
 * Extract jaraba_icon() calls from Twig content.
 *
 * Matches: jaraba_icon('category', 'name', { variant: 'duotone', ... })
 * Also matches: jaraba_icon('category', 'name') (no options).
 */
function extractTwigIconCalls(string $content, string $file): array {
    $refs = [];

    // Strip Twig comments {# ... #} and PHP docblocks to avoid matching examples.
    $stripped = preg_replace('/\{#.*?#\}/s', '', $content) ?? $content;
    $stripped = preg_replace('/\/\*\*.*?\*\//s', '', $stripped) ?? $stripped;

    // Pattern: jaraba_icon('cat', 'name'  or  jaraba_icon('cat', 'name', {
    $pattern = '/jaraba_icon\s*\(\s*[\'"]([a-z_-]+)[\'"]\s*,\s*[\'"]([a-z_-]+)[\'"]\s*(?:,\s*\{([^}]*)\})?\s*\)/i';

    if (preg_match_all($pattern, $stripped, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($matches as $match) {
            $category = $match[1][0];
            $name = $match[2][0];
            $options = isset($match[3]) ? $match[3][0] : '';

            // Extract variant from options.
            $variant = 'outline'; // default
            if (preg_match("/variant\s*:\s*['\"]([a-z_-]+)['\"]/", $options, $vm)) {
                $variant = $vm[1];
            }

            // Calculate approximate line number (offset is on stripped content).
            $offset = $match[0][1];
            $lineNum = substr_count(substr($stripped, 0, $offset), "\n") + 1;

            $refs[] = [
                'category' => $category,
                'name' => $name,
                'variant' => $variant,
                'file' => $file,
                'line' => $lineNum,
            ];
        }
    }

    return $refs;
}

/**
 * Extract icon_cat/icon_name pairs from PHP preprocess arrays.
 *
 * Matches: 'icon_cat' => 'category' ... 'icon_name' => 'name'
 */
function extractPhpIconRefs(string $content, string $file): array {
    $refs = [];

    // Find icon_cat => 'value' patterns.
    $catPattern = "/'icon_cat'\s*=>\s*'([a-z_-]+)'/";
    $namePattern = "/'icon_name'\s*=>\s*'([a-z_-]+)'/";

    if (preg_match_all($catPattern, $content, $catMatches, PREG_OFFSET_CAPTURE)) {
        foreach ($catMatches[0] as $idx => $catMatch) {
            $category = $catMatches[1][$idx][0];
            $offset = $catMatch[1];

            // Look for the nearest icon_name after this icon_cat (within ~300 chars).
            $searchArea = substr($content, $offset, 300);
            if (preg_match($namePattern, $searchArea, $nameMatch)) {
                $name = $nameMatch[1];
                $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;

                // Default variant for PHP-defined icons used in templates is 'duotone'.
                $refs[] = [
                    'category' => $category,
                    'name' => $name,
                    'variant' => 'duotone',
                    'file' => $file,
                    'line' => $lineNum,
                ];
            }
        }
    }

    return $refs;
}

/**
 * Check if an icon SVG file exists for the given reference.
 */
function iconFileExists(string $iconsBase, string $category, string $name, string $variant): bool {
    if ($variant === 'outline') {
        $path = $iconsBase . '/' . $category . '/' . $name . '.svg';
    }
    else {
        $path = $iconsBase . '/' . $category . '/' . $name . '-' . $variant . '.svg';
    }

    return file_exists($path);
}

// --- Scan Twig templates ---

$twigFiles = [];
foreach ($scanDirs as $dir) {
    if (is_file($dir)) {
        continue; // PHP files handled separately.
    }
    if (!is_dir($dir)) {
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'twig') {
            $twigFiles[] = $file->getPathname();
        }
    }
}

foreach ($twigFiles as $twigFile) {
    $content = file_get_contents($twigFile);
    if ($content === false) {
        continue;
    }
    $refs = extractTwigIconCalls($content, $twigFile);
    $checkedFiles++;

    foreach ($refs as $ref) {
        $checkedRefs++;
        if (!iconFileExists($iconsBase, $ref['category'], $ref['name'], $ref['variant'])) {
            // Check if ANY variant exists (to give better error message).
            $anyExists = iconFileExists($iconsBase, $ref['category'], $ref['name'], 'outline')
                || iconFileExists($iconsBase, $ref['category'], $ref['name'], 'duotone')
                || iconFileExists($iconsBase, $ref['category'], $ref['name'], 'filled');

            $relFile = str_replace($GLOBALS['root'] . '/', '', $ref['file']);
            $expected = $ref['category'] . '/' . $ref['name'] . ($ref['variant'] !== 'outline' ? '-' . $ref['variant'] : '') . '.svg';

            $violations[] = [
                'file' => $relFile,
                'line' => $ref['line'],
                'category' => $ref['category'],
                'name' => $ref['name'],
                'variant' => $ref['variant'],
                'expected' => $expected,
                'hint' => $anyExists
                    ? 'Variant "' . $ref['variant'] . '" missing but other variants exist. Check category or variant.'
                    : 'Icon not found in ANY variant. Wrong category? Typo?',
            ];
        }
    }
}

// --- Scan PHP preprocess files ---

foreach ($phpScanDirs as $dir) {
    $phpFiles = [];
    if (is_file($dir)) {
        $phpFiles[] = $dir;
    }
    elseif (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
    }

    foreach ($phpFiles as $phpFile) {
        $content = file_get_contents($phpFile);
        if ($content === false) {
            continue;
        }
        $refs = extractPhpIconRefs($content, $phpFile);
        $checkedFiles++;

        foreach ($refs as $ref) {
            $checkedRefs++;
            if (!iconFileExists($iconsBase, $ref['category'], $ref['name'], $ref['variant'])) {
                $anyExists = iconFileExists($iconsBase, $ref['category'], $ref['name'], 'outline')
                    || iconFileExists($iconsBase, $ref['category'], $ref['name'], 'duotone')
                    || iconFileExists($iconsBase, $ref['category'], $ref['name'], 'filled');

                // Also check if the icon exists in a DIFFERENT category.
                $wrongCatHint = '';
                $allCategories = glob($iconsBase . '/*', GLOB_ONLYDIR);
                foreach ($allCategories as $catDir) {
                    $catName = basename($catDir);
                    if ($catName === $ref['category']) {
                        continue;
                    }
                    if (iconFileExists($iconsBase, $catName, $ref['name'], $ref['variant'])
                        || iconFileExists($iconsBase, $catName, $ref['name'], 'outline')) {
                        $wrongCatHint = ' Found in category "' . $catName . '" instead!';
                        break;
                    }
                }

                $relFile = str_replace($GLOBALS['root'] . '/', '', $ref['file']);
                $expected = $ref['category'] . '/' . $ref['name'] . '-' . $ref['variant'] . '.svg';

                $violations[] = [
                    'file' => $relFile,
                    'line' => $ref['line'],
                    'category' => $ref['category'],
                    'name' => $ref['name'],
                    'variant' => $ref['variant'],
                    'expected' => $expected,
                    'hint' => $anyExists
                        ? 'Variant "' . $ref['variant'] . '" missing but other variants exist.'
                        : 'Icon not found in ANY variant. Wrong category?' . $wrongCatHint,
                ];
            }
        }
    }
}

// --- Output results ---

echo "ICON-INTEGRITY-001: Icon Reference Validation\n";
echo str_repeat('=', 60) . "\n";
echo "Scanned: {$checkedFiles} files, {$checkedRefs} icon references\n";
echo "Icons base: " . str_replace($root . '/', '', $iconsBase) . "/\n\n";

if (empty($violations)) {
    echo "✅ PASS — All {$checkedRefs} icon references resolve to existing SVG files.\n";
    exit(0);
}

echo "❌ FAIL — " . count($violations) . " missing icon(s) found:\n\n";

foreach ($violations as $v) {
    echo "  {$v['file']}:{$v['line']}\n";
    echo "    jaraba_icon('{$v['category']}', '{$v['name']}', variant: '{$v['variant']}')\n";
    echo "    Expected: {$v['expected']}\n";
    echo "    → {$v['hint']}\n\n";
}

echo "Fix: Verify category and icon name match files in images/icons/{category}/{name}[-{variant}].svg\n";
exit(1);
