<?php

declare(strict_types=1);

/**
 * @file
 * CTA-LOGGED-IN-001: Validates that conversion CTAs have logged_in conditional.
 *
 * Detects Twig patterns where registration/signup CTAs are rendered
 * without checking {% if logged_in %} first. A logged-in user should
 * never see "Empezar gratis" or "Crear cuenta" — they should see
 * "Ir al dashboard" instead.
 *
 * SCOPE: Only homepage and landing page templates (page--front, _hero,
 * _cta-banner-final, _product-demo, _how-it-works).
 *
 * EXIT CODES:
 *   0 = All checks pass
 *   1 = CTAs without logged_in conditional found
 *
 * @see IMPLEMENTATION-CHECKLIST-001
 */

$root = dirname(__DIR__, 2);
$templateDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates';

$violations = [];
$checkedFiles = 0;

// Files that MUST have logged_in conditionals on conversion CTAs.
$targetFiles = [
    'page--front.html.twig',
    'partials/_hero.html.twig',
    'partials/_cta-banner-final.html.twig',
    'partials/_product-demo.html.twig',
    'partials/_how-it-works.html.twig',
];

// CTA patterns that indicate registration/signup intent.
$registrationPatterns = [
    '/href="[^"]*user\/register[^"]*"/',
    '/href="[^"]*register[^"]*".*(?:Empezar|Registr|Crear cuenta|Sign up)/si',
    '/path\([\'"]user\.register[\'"]\)/',
];

foreach ($targetFiles as $relPath) {
    $filePath = $templateDir . '/' . $relPath;
    if (!file_exists($filePath)) {
        continue;
    }

    $content = file_get_contents($filePath);
    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    $checkedFiles++;

    // Find all registration CTA occurrences.
    foreach ($lines as $i => $line) {
        $hasRegistrationCta = FALSE;
        foreach ($registrationPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                $hasRegistrationCta = TRUE;
                break;
            }
        }

        if (!$hasRegistrationCta) {
            continue;
        }

        // Look backwards up to 10 lines for {% if logged_in %} or {% else %}.
        $hasConditional = FALSE;
        $searchStart = max(0, $i - 10);
        for ($j = $i; $j >= $searchStart; $j--) {
            if (preg_match('/\{%\s*(if|else)\s.*logged_in|\{%\s*else\s*%\}/', $lines[$j])) {
                $hasConditional = TRUE;
                break;
            }
        }

        if (!$hasConditional) {
            $violations[] = [
                'file' => $relPath,
                'line' => $i + 1,
                'context' => trim($line),
            ];
        }
    }
}

// Output results.
echo "CTA-LOGGED-IN-001: Conversion CTA logged_in Conditional Check\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checkedFiles} template files\n\n";

if (empty($violations)) {
    echo "\033[32m✓ PASS\033[0m — All conversion CTAs have logged_in conditional.\n";
    exit(0);
}

echo "\033[31m✗ FAIL\033[0m — " . count($violations) . " CTA(s) without logged_in check:\n\n";

foreach ($violations as $v) {
    echo "  \033[33m{$v['file']}:{$v['line']}\033[0m\n";
    echo "    " . substr($v['context'], 0, 120) . "\n\n";
}

echo "FIX: Wrap registration CTAs in {% if logged_in %}...dashboard...{% else %}...register...{% endif %}\n";
exit(1);
