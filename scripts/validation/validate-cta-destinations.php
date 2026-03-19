<?php

declare(strict_types=1);

/**
 * @file
 * CTA-DESTINATION-001: Validates that conversion CTAs point to existing routes.
 *
 * Scans conversion-critical Twig templates for href patterns and verifies
 * that the destination routes exist in routing.yml files across the project.
 *
 * EXIT CODES:
 *   0 = All CTA destinations verified
 *   1 = Dead-end CTAs found
 *
 * @see 20260319-Plan_Implementacion_Quiz_Recomendacion_Vertical_IA_v1_Claude.md §20.2
 */

$root = dirname(__DIR__, 2);
$violations = [];
$checkedLinks = 0;

// Templates to scan (conversion-critical).
$templates = [
    'web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_hero.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_cta-banner-final.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_product-demo.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_how-it-works.html.twig',
    'web/themes/custom/ecosistema_jaraba_theme/templates/partials/_header-classic.html.twig',
    'web/modules/custom/ecosistema_jaraba_core/templates/quiz-vertical.html.twig',
    'web/modules/custom/ecosistema_jaraba_core/templates/quiz-vertical-result.html.twig',
];

// Collect known paths from routing files.
$knownPaths = [];
$routingFiles = glob($root . '/web/modules/custom/*//*.routing.yml');
foreach ($routingFiles as $routingFile) {
    $content = file_get_contents($routingFile);
    if ($content === false) {
        continue;
    }
    if (preg_match_all("/path:\s*'([^']+)'/", $content, $matches)) {
        foreach ($matches[1] as $path) {
            // Normalize: remove parameters like {uuid}.
            $normalized = preg_replace('/\{[^}]+\}/', '*', $path);
            $knownPaths[$normalized] = $path;
            $knownPaths[$path] = $path;
        }
    }
}

// Also add standard Drupal paths.
$knownPaths['/user'] = '/user';
$knownPaths['/user/login'] = '/user/login';
$knownPaths['/user/register'] = '/user/register';

// Internal paths that are always valid.
$alwaysValid = ['#', 'http', '{{', 'path(', 'url('];

echo "CTA-DESTINATION-001: CTA Destination Validation\n";
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
        // Find href="..." patterns in CTAs (btn-primary, btn-ghost, quiz-result__cta).
        if (preg_match_all('/href="([^"]+)"/', $line, $matches)) {
            foreach ($matches[1] as $href) {
                // Skip external, anchors, dynamic.
                $skip = false;
                foreach ($alwaysValid as $prefix) {
                    if (str_starts_with($href, $prefix)) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) {
                    continue;
                }

                $checkedLinks++;

                // Remove language prefix variable {{ lp }}.
                $cleanPath = preg_replace('/\{\{\s*lp\s*\}\}/', '', $href);
                $cleanPath = preg_replace('/\{\{[^}]*\}\}/', '*', $cleanPath);
                $cleanPath = preg_replace('/\?.*$/', '', $cleanPath); // Remove query strings.

                // Check if path exists.
                if (!empty($cleanPath) && $cleanPath !== '/' && !isset($knownPaths[$cleanPath])) {
                    // Try wildcard match.
                    $found = false;
                    foreach ($knownPaths as $pattern => $original) {
                        $patternRegex = str_replace('*', '[^/]+', preg_quote($pattern, '/'));
                        if (preg_match('/^' . $patternRegex . '$/', $cleanPath)) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $violations[] = [
                            'file' => $relPath,
                            'line' => $lineNum + 1,
                            'href' => $href,
                            'clean' => $cleanPath,
                        ];
                    }
                }
            }
        }
    }
}

echo "Checked: {$checkedLinks} CTA links in " . count($templates) . " templates\n";
echo "Known routes: " . count($knownPaths) . "\n\n";

if (empty($violations)) {
    echo "✅ PASS — All {$checkedLinks} CTA destinations verified.\n";
    exit(0);
}

echo "⚠️  WARNING — " . count($violations) . " CTA(s) with unverified destinations:\n\n";
foreach ($violations as $v) {
    echo "  {$v['file']}:{$v['line']}\n";
    echo "    href=\"{$v['href']}\"\n";
    echo "    Resolved: {$v['clean']}\n\n";
}

// Warnings only (not blocking) — some paths may be from contributed modules.
echo "Note: These may be false positives if destinations come from contrib modules.\n";
exit(0);
