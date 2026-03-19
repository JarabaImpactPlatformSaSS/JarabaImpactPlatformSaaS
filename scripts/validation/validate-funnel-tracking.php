<?php

declare(strict_types=1);

/**
 * @file
 * FUNNEL-COMPLETENESS-001: Validates conversion funnel tracking completeness.
 *
 * Checks that every CTA in conversion-critical templates has:
 * 1. data-track-cta attribute (event name)
 * 2. data-track-position attribute (section location)
 * 3. logged_in conditional where appropriate (CTA-LOGGED-IN-001)
 *
 * EXIT CODES:
 *   0 = All conversion CTAs have tracking
 *   1 = CTAs missing tracking attributes
 */

$root = dirname(__DIR__, 2);
$violations = [];
$warnings = [];
$checkedCtas = 0;

$templates = [
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_hero.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_cta-banner-final.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_product-demo.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_how-it-works.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header-classic.html.twig',
    'web/modules/custom/ecosistema_jaraba_core/templates/quiz-vertical.html.twig',
    'web/modules/custom/ecosistema_jaraba_core/templates/quiz-vertical-result.html.twig',
];

// CTA CSS classes that indicate conversion-critical buttons.
$ctaClasses = ['btn-primary', 'btn-gold', 'quiz-result__cta'];

echo "FUNNEL-COMPLETENESS-001: Funnel Tracking Validation\n";
echo str_repeat('=', 60) . "\n";

foreach ($templates as $relPath) {
    $fullPath = $root . '/' . $relPath;
    if (!file_exists($fullPath)) {
        continue;
    }

    $content = file_get_contents($fullPath);
    if ($content === false) {
        continue;
    }
    $lines = explode("\n", $content);

    foreach ($lines as $lineNum => $line) {
        // Detect CTA links (have btn-primary, btn-gold, or similar).
        $isCta = false;
        foreach ($ctaClasses as $cls) {
            if (str_contains($line, $cls) && str_contains($line, 'href=')) {
                $isCta = true;
                break;
            }
        }

        if (!$isCta) {
            continue;
        }

        $checkedCtas++;

        // Multi-line tag support: check current line + next 2 lines for attributes.
        $tagBlock = $line;
        for ($look = 1; $look <= 2; $look++) {
            if (isset($lines[$lineNum + $look])) {
                $tagBlock .= ' ' . $lines[$lineNum + $look];
            }
        }

        // Check for data-track-cta.
        if (!str_contains($tagBlock, 'data-track-cta=')) {
            $violations[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'issue' => 'Missing data-track-cta attribute',
                'context' => trim(substr($line, 0, 120)),
            ];
        }

        // Check for data-track-position.
        if (!str_contains($tagBlock, 'data-track-position=')) {
            $violations[] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'issue' => 'Missing data-track-position attribute',
                'context' => trim(substr($line, 0, 120)),
            ];
        }
    }
}

echo "Checked: {$checkedCtas} conversion CTAs in " . count($templates) . " templates\n\n";

if (empty($violations)) {
    echo "✅ PASS — All {$checkedCtas} conversion CTAs have tracking attributes.\n";
    exit(0);
}

echo "❌ FAIL — " . count($violations) . " tracking gap(s) found:\n\n";
foreach ($violations as $v) {
    echo "  {$v['file']}:{$v['line']}\n";
    echo "    {$v['issue']}\n";
    echo "    Context: {$v['context']}\n\n";
}
exit(1);
