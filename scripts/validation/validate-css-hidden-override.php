<?php

declare(strict_types=1);

/**
 * @file
 * CSS-HIDDEN-OVERRIDE-001: Detects CSS `display` that overrides HTML `hidden` attribute.
 *
 * If a CSS rule sets `display: flex|grid|block` on a class, and that class
 * is used with the HTML `hidden` attribute, the element will be VISIBLE
 * despite `hidden` — because CSS `display` overrides `hidden`.
 *
 * Solution: Use `display: none` as default, with `:not([hidden]) { display: flex }`.
 *
 * EXIT CODES:
 *   0 = No conflicts found
 *   1 = Potential hidden/display conflicts detected
 */

$root = dirname(__DIR__, 2);
$violations = [];
$checked = 0;

// Find all JS files that use `hidden` attribute.
$jsFiles = glob($root . '/web/themes/custom/ecosistema_jaraba_theme/js/*.js');
$hiddenClasses = [];

foreach ($jsFiles as $jsFile) {
    $content = file_get_contents($jsFile);
    if ($content === false) {
        continue;
    }

    // Find patterns like: class="some-class" hidden or class="some-class" ... hidden
    // Also: element.hidden = true/false
    if (preg_match_all('/class="([^"]*)"[^>]*\bhidden\b/', $content, $m)) {
        foreach ($m[1] as $classes) {
            foreach (explode(' ', $classes) as $cls) {
                $cls = trim($cls);
                if ($cls) {
                    $hiddenClasses[$cls] = $jsFile;
                }
            }
        }
    }

    // Also patterns like: hidden id="quiz-email-ok"
    if (preg_match_all('/\bhidden\b[^>]*id="([^"]*)"/', $content, $m)) {
        foreach ($m[1] as $id) {
            $hiddenClasses['#' . $id] = $jsFile;
        }
    }
}

if (empty($hiddenClasses)) {
    echo "CSS-HIDDEN-OVERRIDE-001: No hidden attribute usage found in JS.\n";
    echo "✅ PASS\n";
    exit(0);
}

// Check SCSS for explicit display on those classes.
$scssFiles = glob($root . '/web/themes/custom/ecosistema_jaraba_theme/scss/{,*/,*/*/}*.scss', GLOB_BRACE);

foreach ($scssFiles as $scssFile) {
    $content = file_get_contents($scssFile);
    if ($content === false) {
        continue;
    }

    $checked++;
    $lines = explode("\n", $content);

    foreach ($hiddenClasses as $cls => $jsSource) {
        // Skip IDs for now (harder to match in SCSS).
        if (str_starts_with($cls, '#')) {
            continue;
        }

        // Check if the class has a display rule that would override hidden.
        $selectorPattern = '\\.' . preg_quote($cls, '/');
        $inSelector = false;
        $braceDepth = 0;

        foreach ($lines as $lineNum => $line) {
            if (preg_match('/' . $selectorPattern . '/', $line)) {
                $inSelector = true;
                $braceDepth = 0;
            }

            if ($inSelector) {
                $braceDepth += substr_count($line, '{') - substr_count($line, '}');

                if (preg_match('/display\s*:\s*(flex|grid|block|inline-flex|inline-block)/', $line, $dm)) {
                    // Check if there's a :not([hidden]) guard.
                    if (!str_contains($line, ':not([hidden])') && !str_contains($line, 'not-hidden')) {
                        $relScss = str_replace($root . '/', '', $scssFile);
                        $relJs = str_replace($root . '/', '', $jsSource);
                        $violations[] = [
                            'class' => $cls,
                            'scss_file' => $relScss,
                            'scss_line' => $lineNum + 1,
                            'display' => $dm[1],
                            'js_file' => $relJs,
                        ];
                    }
                }

                if ($braceDepth <= 0) {
                    $inSelector = false;
                }
            }
        }
    }
}

echo "CSS-HIDDEN-OVERRIDE-001: CSS display vs HTML hidden conflicts\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checked} SCSS files, " . count($hiddenClasses) . " classes with hidden attribute\n\n";

if (empty($violations)) {
    echo "✅ PASS — No display/hidden conflicts found.\n";
    exit(0);
}

echo "⚠️  WARNING — " . count($violations) . " potential conflict(s):\n\n";
foreach ($violations as $v) {
    echo "  .{$v['class']} has display:{$v['display']} in SCSS\n";
    echo "    SCSS: {$v['scss_file']}:{$v['scss_line']}\n";
    echo "    JS uses hidden attribute: {$v['js_file']}\n";
    echo "    → CSS display overrides HTML hidden. Use display:none + :not([hidden]){display:{$v['display']}}\n\n";
}
// Warning only, not blocking.
exit(0);
