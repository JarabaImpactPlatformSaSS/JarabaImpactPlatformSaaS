<?php

declare(strict_types=1);

/**
 * @file
 * JS-TWIG-SELECTOR-001: Validates that JS selectors match Twig DOM elements.
 *
 * Detects mismatches between CSS class selectors or data-attribute selectors
 * used in JS files and the actual classes/attributes present in Twig templates.
 *
 * The lead-magnet bug (JS looked for .lead-magnet-form__success inside form,
 * but the element was a sibling with [data-lead-magnet-success]) is the
 * canonical example this safeguard prevents.
 *
 * SCOPE: JS files in the theme that use querySelector/querySelectorAll
 * paired with their corresponding Twig templates.
 *
 * EXIT CODES:
 *   0 = All checks pass
 *   1 = Potential selector mismatches found
 *   2 = Warnings only (possible but not confirmed mismatches)
 */

$root = dirname(__DIR__, 2);
$themeDir = $root . '/web/themes/custom/ecosistema_jaraba_theme';
$jsDir = $themeDir . '/js';
$tplDir = $themeDir . '/templates';

$violations = [];
$warnings = [];
$checkedPairs = 0;

// Map of JS files to their corresponding Twig template(s).
// Derived from @see comments in JS files and library definitions.
// IMPORTANT: Include ALL partials that are {% include %}'d in the page where
// the JS runs, because JS uses document.querySelector (global DOM scope).
$jsTwigMap = [
    'lead-magnet.js' => ['partials/_lead-magnet.html.twig', 'partials/_landing-lead-magnet.html.twig'],
    'product-demo.js' => ['partials/_product-demo.html.twig'],
    'progressive-profiling.js' => [
        'partials/_hero.html.twig',
        'partials/_intentions-grid.html.twig',
    ],
    'scroll-animations.js' => [
        'page--front.html.twig',
        // Partials included by page--front via {% include %}:
        'partials/_hero.html.twig',
        'partials/_header.html.twig',
        'partials/_header-classic.html.twig',
        'partials/_header-hero.html.twig',
        'partials/_header-minimal.html.twig',
        'partials/_header-split.html.twig',
        'partials/_header-centered.html.twig',
        'partials/_stats.html.twig',
        'partials/_features.html.twig',
        'partials/_copilot-fab.html.twig',
        'partials/_intentions-grid.html.twig',
    ],
    'setup-wizard.js' => ['partials/_setup-wizard.html.twig'],
    'slide-panel.js' => [],  // Generic, used everywhere.
    'consent-banner.js' => ['partials/_consent-banner.html.twig'],
    'notification-panel.js' => [
        'partials/_notification-panel.html.twig',
        // Bell badge is in the header/bottom-nav, not in the panel itself:
        'partials/_bottom-nav.html.twig',
    ],
];

// Selectors that are dynamically created via JS (document.createElement),
// or target legacy DOM structures no longer in current templates.
// These are legitimate and should be skipped.
$dynamicSelectors = [
    // progressive-profiling.js: badge injected via JS createElement.
    '.profile-badge',
    '.profile-badge__reset',
    // scroll-animations.js: overlay created via JS createElement.
    '.mobile-menu-overlay',
    // scroll-animations.js: [data-typed] is an optional attribute that content
    // editors may add via Page Builder. Not present in static Twig partials.
    '[data-typed]:not(.typed-attached)',
    // scroll-animations.js: landingCopilot behavior targets legacy agent-*
    // naming. Current _copilot-fab.html.twig uses copilot-* BEM classes.
    // The behavior guards with early return if container is null.
    '.agent-fab-container.landing-copilot',
    '.agent-fab-trigger',
    '.agent-panel',
    '.agent-close',
    '.agent-input',
    '.agent-send',
    '.agent-chat',
    // scroll-animations.js: .action-button is the legacy selector for copilot
    // action buttons. Current template uses .action-btn.
    '.action-button',
    // scroll-animations.js: rating buttons created dynamically via innerHTML.
    '.rating-btn',
];

/**
 * Extract querySelector/querySelectorAll selectors from JS content.
 */
function extractJsSelectors(string $content): array {
    $selectors = [];
    // Match querySelector('...') and querySelectorAll('...')
    if (preg_match_all('/querySelector(?:All)?\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
        foreach ($matches[1] as $selector) {
            $selectors[] = $selector;
        }
    }
    return array_unique($selectors);
}

/**
 * Check if a CSS selector could match content in a Twig file.
 *
 * For comma-separated selectors (e.g. ".foo, .bar, [data-baz]"),
 * returns TRUE if ANY alternative matches (OR logic).
 */
function selectorExistsInTwig(string $selector, string $twigContent): bool {
    // Handle comma-separated selectors: .foo, .bar → match if ANY part exists.
    if (str_contains($selector, ',')) {
        $alternatives = array_map('trim', explode(',', $selector));
        foreach ($alternatives as $alt) {
            if (selectorExistsInTwig($alt, $twigContent)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    // Handle class selectors: .foo-bar → class="...foo-bar..."
    if (preg_match('/^\.([a-zA-Z0-9_-]+)$/', $selector, $m)) {
        return stripos($twigContent, $m[1]) !== FALSE;
    }

    // Handle data-attribute selectors: [data-foo] or [data-foo="bar"]
    if (preg_match('/^\[([a-zA-Z0-9_-]+)(?:=["\']?([^"\'\]]*)["\']?)?\]$/', $selector, $m)) {
        return stripos($twigContent, $m[1]) !== FALSE;
    }

    // Handle combined selectors: .foo .bar, .foo[data-bar]
    // Split by space and check each part.
    $parts = preg_split('/\s+/', $selector);
    foreach ($parts as $part) {
        // Extract class or attribute from each part.
        if (preg_match('/\.([a-zA-Z0-9_-]+)/', $part, $m)) {
            if (stripos($twigContent, $m[1]) === FALSE) {
                return FALSE;
            }
        }
        if (preg_match('/\[([a-zA-Z0-9_-]+)/', $part, $m)) {
            if (stripos($twigContent, $m[1]) === FALSE) {
                return FALSE;
            }
        }
    }

    return TRUE;
}

// Check each JS/Twig pair.
foreach ($jsTwigMap as $jsFile => $twigFiles) {
    $jsPath = $jsDir . '/' . $jsFile;
    if (!file_exists($jsPath) || empty($twigFiles)) {
        continue;
    }

    $jsContent = file_get_contents($jsPath);
    $selectors = extractJsSelectors($jsContent);

    if (empty($selectors)) {
        continue;
    }

    // Combine all Twig files for this JS.
    $combinedTwig = '';
    foreach ($twigFiles as $twigFile) {
        $twigPath = $tplDir . '/' . $twigFile;
        if (file_exists($twigPath)) {
            $combinedTwig .= file_get_contents($twigPath) . "\n";
        }
    }

    if (empty($combinedTwig)) {
        continue;
    }

    $checkedPairs++;

    foreach ($selectors as $selector) {
        // Skip very generic selectors.
        if (in_array($selector, ['form', 'button', 'input', 'a', 'div', 'span', 'p', 'ul', 'li'])) {
            continue;
        }
        // Skip tag-based selectors.
        if (preg_match('/^[a-z]+(\[|$)/', $selector) && !str_contains($selector, '.') && !str_contains($selector, '#')) {
            continue;
        }

        // Skip selectors that are dynamically created via JS (not in Twig).
        if (in_array($selector, $dynamicSelectors)) {
            continue;
        }

        if (!selectorExistsInTwig($selector, $combinedTwig)) {
            // Determine severity: class selectors are more specific (violation),
            // complex selectors might be dynamic (warning).
            if (str_contains($selector, ' ') || str_contains($selector, ',')) {
                $warnings[] = [
                    'js' => $jsFile,
                    'twig' => implode(', ', $twigFiles),
                    'selector' => $selector,
                ];
            }
            else {
                $violations[] = [
                    'js' => $jsFile,
                    'twig' => implode(', ', $twigFiles),
                    'selector' => $selector,
                ];
            }
        }
    }
}

// Output results.
echo "JS-TWIG-SELECTOR-001: JS↔Twig Selector Coherence Check\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checkedPairs} JS↔Twig pairs\n\n";

if (empty($violations) && empty($warnings)) {
    echo "\033[32m✓ PASS\033[0m — All JS selectors match Twig DOM elements.\n";
    exit(0);
}

if (!empty($violations)) {
    echo "\033[31m✗ FAIL\033[0m — " . count($violations) . " selector mismatch(es):\n\n";
    foreach ($violations as $v) {
        echo "  \033[33m{$v['js']}\033[0m → {$v['twig']}\n";
        echo "    Selector: {$v['selector']}\n";
        echo "    Not found in any corresponding Twig template.\n\n";
    }
}

if (!empty($warnings)) {
    echo "\033[33m⚠ WARN\033[0m — " . count($warnings) . " possible mismatch(es):\n\n";
    foreach ($warnings as $w) {
        echo "  \033[33m{$w['js']}\033[0m → {$w['twig']}\n";
        echo "    Selector: {$w['selector']}\n\n";
    }
}

exit(!empty($violations) ? 1 : (!empty($warnings) ? 2 : 0));
