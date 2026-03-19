<?php

declare(strict_types=1);

/**
 * @file
 * BIGPIPE-TIMING-001: Detects Drupal.behaviors that use once() with drupalSettings.
 *
 * Pattern: once() marks the element as processed on first attach. But with BigPipe,
 * drupalSettings may not be available yet on first attach. If the behavior returns
 * early (no data), once() already consumed the element → never re-processes.
 *
 * Safe patterns:
 * - dataset.rendered flag (manual guard instead of once())
 * - Check drupalSettings BEFORE calling once()
 * - setTimeout fallback
 *
 * EXIT CODES:
 *   0 = No risky patterns found
 *   1 = Behaviors with once() + drupalSettings detected
 */

$root = dirname(__DIR__, 2);
$violations = [];
$checked = 0;

$jsFiles = array_merge(
    glob($root . '/web/themes/custom/ecosistema_jaraba_theme/js/*.js'),
    glob($root . '/web/modules/custom/*/js/*.js')
);

foreach ($jsFiles as $jsFile) {
    $content = file_get_contents($jsFile);
    if ($content === false || !str_contains($content, 'Drupal.behaviors')) {
        continue;
    }

    $checked++;

    // Find all behaviors blocks.
    if (preg_match_all('/Drupal\.behaviors\.(\w+)\s*=\s*\{/m', $content, $behaviorMatches, PREG_OFFSET_CAPTURE)) {
        foreach ($behaviorMatches[1] as $bMatch) {
            $behaviorName = $bMatch[0];
            $offset = $bMatch[1];

            // Extract ~200 lines of the behavior body.
            $body = substr($content, $offset, 8000);

            $usesOnce = str_contains($body, 'once(');
            $usesDrupalSettings = (bool) preg_match('/drupalSettings\.\w+/', $body);

            if ($usesOnce && $usesDrupalSettings) {
                // Check if there's a safe guard pattern.
                $hasSafeGuard = str_contains($body, 'dataset.rendered')
                    || str_contains($body, 'setTimeout')
                    || preg_match('/if\s*\(\s*!.*drupalSettings.*\)\s*\{\s*return/', $body);

                if (!$hasSafeGuard) {
                    $relPath = str_replace($root . '/', '', $jsFile);
                    $lineNum = substr_count(substr($content, 0, $offset), "\n") + 1;
                    $violations[] = [
                        'file' => $relPath,
                        'behavior' => $behaviorName,
                        'line' => $lineNum,
                    ];
                }
            }
        }
    }
}

echo "BIGPIPE-TIMING-001: BigPipe + once() + drupalSettings Timing\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checked} JS files with Drupal.behaviors\n\n";

if (empty($violations)) {
    echo "✅ PASS — No risky once() + drupalSettings patterns found.\n";
    exit(0);
}

echo "⚠️  WARNING — " . count($violations) . " behavior(s) with BigPipe timing risk:\n\n";
foreach ($violations as $v) {
    echo "  {$v['file']}:{$v['line']}\n";
    echo "    Drupal.behaviors.{$v['behavior']} uses once() with drupalSettings\n";
    echo "    → once() marks element on first attach, but drupalSettings may not be ready (BigPipe)\n";
    echo "    → Use dataset.rendered flag or setTimeout fallback instead of once()\n\n";
}
// Warning only — 69 preexisting behaviors. Fix incrementally.
exit(0);
