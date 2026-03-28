<?php

/**
 * @file
 * CSS-VAR-ALL-COLORS-001: Validates --ej-* custom property usage in SCSS.
 *
 * Every color in SCSS MUST use var(--ej-*, fallback). Exceptions:
 * - $ej-color-* variable DEFINITIONS in _variables.scss (source of truth)
 * - Third-party brand colors (WhatsApp #25D366, social networks)
 * - Semantic colors (error red, success green) used as color-mix() inputs
 * - CSS-only contexts (SVG inline, email templates with inline styles)
 * - Sass functions that require static hex (color.scale, color.adjust)
 *
 * Usage: php scripts/validation/validate-css-var-colors.php
 * Exit code: 0 = pass (warnings only), 1 = critical violations.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;

$scssDir = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/scss';

// Files where raw hex is legitimate (definitions, not usage).
$exemptFiles = [
    '_variables.scss',       // Token definitions.
    '_email.scss',           // Email inline styles (no CSS custom props).
    '_print.scss',           // Print stylesheets.
];

// Third-party brand colors (legitimate raw hex).
$brandColors = [
    '#25D366', '#25d366',    // WhatsApp.
    '#1877F2', '#1877f2',    // Facebook.
    '#1DA1F2', '#1da1f2',    // Twitter.
    '#0A66C2', '#0a66c2',    // LinkedIn.
    '#0088cc',               // Telegram.
    '#FF0000', '#ff0000',    // YouTube.
    '#E4405F', '#e4405f',    // Instagram.
    '#f8f8f2',               // Code highlight (Monokai).
];

// Neutral/utility colors often used legitimately.
$utilityColors = [
    '#fff', '#FFF', '#ffffff', '#FFFFFF',
    '#000', '#000000',
    '#transparent',
];

// ─── CHECK 1: Scan SCSS for raw hex outside var() ───
$violations = [];
$filesScanned = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($scssDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'scss') {
        continue;
    }

    $basename = $file->getBasename();
    if (in_array($basename, $exemptFiles, true)) {
        continue;
    }

    $filesScanned++;
    $lines = file($file->getPathname(), FILE_IGNORE_NEW_LINES);

    foreach ($lines as $lineNum => $line) {
        $lineNo = $lineNum + 1;
        $trimmed = trim($line);

        // Skip comments.
        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
            continue;
        }

        // Skip SCSS variable definitions ($ej-*: #hex).
        if (preg_match('/^\$ej-/', $trimmed)) {
            continue;
        }

        // Skip sass:color function calls (need static hex).
        if (preg_match('/color\.(scale|adjust|change|mix)/', $trimmed)) {
            continue;
        }

        // Skip color-mix() inputs (hex as parameter to CSS function).
        if (str_contains($trimmed, 'color-mix(')) {
            continue;
        }

        // Find raw hex colors.
        if (preg_match_all('/#([0-9a-fA-F]{3,8})\b/', $trimmed, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $hex = '#' . $match[1];

                // Skip white, black, transparent.
                if (in_array($hex, $utilityColors, true)) {
                    continue;
                }

                // Skip third-party brand colors.
                if (in_array($hex, $brandColors, true)) {
                    continue;
                }

                // Skip if inside var().
                if (preg_match('/var\(--ej-[^,]+,\s*' . preg_quote($hex, '/') . '/', $trimmed)) {
                    continue;
                }

                // Skip if inside a SCSS variable reference.
                if (preg_match('/\$ej-.*' . preg_quote($hex, '/') . '/', $trimmed)) {
                    continue;
                }

                $relativePath = str_replace($scssDir . '/', '', $file->getPathname());
                $violations[] = "{$relativePath}:{$lineNo} — raw hex {$hex}";
            }
        }
    }
}

// CSS-VAR-ALL-COLORS-001 is a baseline rule — many pre-existing violations.
// Report as warnings, not errors, with a threshold.
$threshold = 200; // Known baseline of pre-existing hex usage.
if (count($violations) <= $threshold) {
    $passed++;
    if (!empty($violations)) {
        $warnings[] = "CHECK 1: " . count($violations) . " raw hex instances (baseline threshold: {$threshold})";
    }
} else {
    $errors[] = "CHECK 1: " . count($violations) . " raw hex instances EXCEED baseline threshold of {$threshold} (new violations introduced)";
}

// ─── CHECK 2: _variables.scss defines core tokens ───
$variablesFile = $scssDir . '/_variables.scss';
if (file_exists($variablesFile)) {
    $content = file_get_contents($variablesFile);
    $requiredTokens = ['$ej-color-corporate', '$ej-color-impulse', '$ej-color-innovation'];
    $missing = [];
    foreach ($requiredTokens as $token) {
        if (!str_contains($content, $token)) {
            $missing[] = $token;
        }
    }
    if (empty($missing)) {
        $passed++;
    } else {
        $errors[] = 'CHECK 2: _variables.scss missing core tokens: ' . implode(', ', $missing);
    }
} else {
    $errors[] = 'CHECK 2: _variables.scss not found';
}

// ─── CHECK 3: Main SCSS uses @use variables (not @import) ───
$mainFile = $scssDir . '/ecosistema-jaraba-theme.scss';
if (file_exists($mainFile)) {
    $content = file_get_contents($mainFile);
    if (str_contains($content, "@import 'variables'") || str_contains($content, '@import "variables"')) {
        $errors[] = 'CHECK 3: Main SCSS uses @import for variables (must use @use)';
    } else {
        $passed++;
    }
} else {
    $warnings[] = 'CHECK 3: Main SCSS entry point not found at expected path';
    $passed++;
}

// ─── RESULTS ───
$total = $passed + count($errors);
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " CSS-VAR-ALL-COLORS-001: SCSS Custom Property Usage\n";
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
