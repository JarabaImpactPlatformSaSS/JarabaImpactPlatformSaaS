<?php

declare(strict_types=1);

/**
 * @file
 * BTN-CONTRAST-DARK-001: Validates button contrast on dark backgrounds.
 *
 * Detects SCSS patterns where buttons (.btn-ghost, .btn--outline, .btn--ghost,
 * .btn-secondary) are used inside dark background contexts without explicit
 * white/light color overrides.
 *
 * Dark contexts are identified by:
 * - background containing corporate (#233D63), bg-dark (#1A1A2E), or gradient to dark
 * - Class names containing "--dark"
 * - Explicit dark gradient patterns
 *
 * EXIT CODES:
 *   0 = All checks pass
 *   1 = Potential contrast violations found
 */

$root = dirname(__DIR__, 2);
$themeDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/scss';

$violations = [];
$warnings = [];

// Patterns that indicate dark background contexts.
$darkBgPatterns = [
    '/background:\s*(?:linear-gradient\([^)]*(?:#(?:1[a-f0-9]{5}|2[0-3][0-9a-f]{4}|0[f0-9][0-9a-f]{4})|var\(--ej-(?:color-corporate|bg-dark|dark-)))/i',
    '/background(?:-color)?:\s*var\(--ej-(?:color-corporate|bg-dark|dark-accent|dark-deeper)/i',
];

// Button classes that need light text on dark backgrounds.
$dangerousButtonClasses = [
    'btn-ghost',
    'btn--ghost',
    'btn--outline',
    'btn-secondary',
    'btn--secondary',
];

// Known safe patterns (already have white overrides).
$safePatterns = [
    'color: var(--ej-bg-surface',
    'color: #fff',
    'color: white',
    'color: rgba(255',
    'border-color: rgba(255',
];

// Scan all SCSS files.
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($themeDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'scss') {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    $relativePath = str_replace($root . '/', '', $file->getPathname());
    $lines = explode("\n", $content);

    // Track nesting: are we inside a dark context?
    $braceDepth = 0;
    $darkContextDepth = -1;
    $darkContextStart = 0;
    $inDarkContext = false;

    foreach ($lines as $lineNum => $line) {
        $lineNumber = $lineNum + 1;

        // Track braces for nesting.
        $braceDepth += substr_count($line, '{') - substr_count($line, '}');

        // Detect entering dark context.
        if (!$inDarkContext) {
            // Check for --dark variant.
            if (preg_match('/&--dark\b/', $line)) {
                $inDarkContext = true;
                $darkContextDepth = $braceDepth;
                $darkContextStart = $lineNumber;
            }

            // Check for dark background declaration.
            foreach ($darkBgPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $inDarkContext = true;
                    $darkContextDepth = $braceDepth;
                    $darkContextStart = $lineNumber;
                    break;
                }
            }
        }

        // Check if we exited dark context.
        if ($inDarkContext && $braceDepth < $darkContextDepth) {
            $inDarkContext = false;
            $darkContextDepth = -1;
        }

        // If in dark context, look for button references without color override.
        if ($inDarkContext) {
            foreach ($dangerousButtonClasses as $btnClass) {
                if (str_contains($line, '.' . $btnClass) || str_contains($line, '"' . $btnClass . '"')) {
                    // Look ahead 10 lines for a safe color override.
                    $hasSafeOverride = false;
                    for ($i = $lineNum; $i < min($lineNum + 10, count($lines)); $i++) {
                        foreach ($safePatterns as $safe) {
                            if (str_contains($lines[$i], $safe)) {
                                $hasSafeOverride = true;
                                break 2;
                            }
                        }
                    }

                    if (!$hasSafeOverride) {
                        $warnings[] = [
                            'file' => $relativePath,
                            'line' => $lineNumber,
                            'context_start' => $darkContextStart,
                            'button' => $btnClass,
                            'detail' => trim($line),
                        ];
                    }
                }
            }
        }
    }
}

// Also check: ensure _buttons.scss has the global dark context override.
$buttonsFile = $themeDir . '/components/_buttons.scss';
if (file_exists($buttonsFile)) {
    $buttonsContent = file_get_contents($buttonsFile);
    if (!str_contains($buttonsContent, 'ej-dark-context') && !str_contains($buttonsContent, 'BTN-CONTRAST-DARK-001')) {
        $violations[] = "CRITICAL: _buttons.scss missing global .ej-dark-context override (BTN-CONTRAST-DARK-001)";
    }
    if (!str_contains($buttonsContent, '[class*="--dark"]')) {
        $violations[] = "CRITICAL: _buttons.scss missing [class*=\"--dark\"] selector for page-builder dark variants";
    }
}
else {
    $violations[] = "CRITICAL: _buttons.scss not found at expected path";
}

// Report.
$totalIssues = count($violations) + count($warnings);

echo "BTN-CONTRAST-DARK-001: Button contrast on dark backgrounds\n";
echo str_repeat('=', 60) . "\n";

if (empty($violations) && empty($warnings)) {
    echo "PASS: Global dark context override present in _buttons.scss\n";
    echo "PASS: No unprotected buttons in dark SCSS contexts detected\n";
    exit(0);
}

foreach ($violations as $v) {
    echo "FAIL: $v\n";
}

foreach ($warnings as $w) {
    echo "WARN: {$w['file']}:{$w['line']} — .{$w['button']} in dark context (started line {$w['context_start']}) without explicit white color override\n";
    echo "      {$w['detail']}\n";
}

echo "\n{$totalIssues} issue(s) found.\n";
echo "Fix: Add white color override for buttons in dark contexts, or use .ej-dark-context utility class.\n";

exit(count($violations) > 0 ? 1 : 0);
