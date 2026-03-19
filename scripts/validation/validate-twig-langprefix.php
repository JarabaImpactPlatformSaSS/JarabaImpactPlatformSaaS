<?php

declare(strict_types=1);

/**
 * @file
 * TWIG-LANGPREFIX-001: Validates that Twig templates use language prefix on internal URLs.
 *
 * Detects hardcoded href="/path" patterns in Twig templates that should use
 * either {{ lp }}, {{ language_prefix }}, ped_urls, or path() to ensure
 * proper language prefix (e.g. /es/) on all internal URLs.
 *
 * ALLOWLIST:
 * - External URLs (https://, http://)
 * - Anchor links (#...)
 * - Admin paths (/admin/...)
 * - Drupal system paths (/sites/, /core/, /modules/, /themes/)
 * - API endpoints (/api/, /session/token)
 * - Already prefixed with {{ lp }}, ped_urls, path(), language_prefix
 *
 * EXIT CODES:
 *   0 = All checks pass
 *   1 = Hardcoded URLs found without language prefix
 *
 * @see ROUTE-LANGPREFIX-001
 */

$root = dirname(__DIR__, 2);
$templateDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates';

$violations = [];
$warnings = [];
$checkedFiles = 0;

// Paths that are safe to hardcode (system paths, not user-facing).
$allowedPrefixes = [
    '/admin',
    '/sites/',
    '/core/',
    '/modules/',
    '/themes/',
    '/api/',
    '/session/',
    '/favicon',
    '/robots.txt',
    '/sitemap.xml',
];

// Patterns that indicate the URL is already properly prefixed.
$safePatterns = [
    'lp }}',           // {{ lp }}/path
    'language_prefix', // {{ language_prefix }}/path
    'ped_urls',        // ped_urls.key
    'path(',           // {{ path('route.name') }}
    'url(',            // {{ url('route.name') }}
    'urls.',           // urls.empleabilidad
];

/**
 * Check if a line has a hardcoded internal URL without language prefix.
 */
function checkLine(string $line, int $lineNum, string $file, array $allowedPrefixes, array $safePatterns): ?array {
    // Find href="/something" patterns.
    if (!preg_match_all('/href="(\/[a-z][^"]*)"/', $line, $matches, PREG_SET_ORDER)) {
        return NULL;
    }

    $issues = [];
    foreach ($matches as $match) {
        $url = $match[1];

        // Check if URL starts with an allowed system prefix.
        $allowed = FALSE;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($url, $prefix)) {
                $allowed = TRUE;
                break;
            }
        }
        if ($allowed) {
            continue;
        }

        // Check if the URL is already using a safe pattern (look at surrounding context).
        $contextStart = max(0, strpos($line, $match[0]) - 80);
        $context = substr($line, $contextStart, strlen($match[0]) + 160);
        $safe = FALSE;
        foreach ($safePatterns as $pattern) {
            if (stripos($context, $pattern) !== FALSE) {
                $safe = TRUE;
                break;
            }
        }
        if ($safe) {
            continue;
        }

        // Check if it's already language-prefixed (e.g., /es/something).
        if (preg_match('#^/(es|en|pt-br)/#', $url)) {
            continue;
        }

        $issues[] = [
            'file' => str_replace($GLOBALS['root'] . '/', '', $file),
            'line' => $lineNum,
            'url' => $url,
            'context' => trim($line),
        ];
    }

    return $issues ?: NULL;
}

// Scan all Twig templates.
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($templateDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->getExtension() !== 'twig') {
        continue;
    }

    $filePath = $file->getPathname();
    $lines = file($filePath, FILE_IGNORE_NEW_LINES);
    $checkedFiles++;

    foreach ($lines as $i => $line) {
        $issues = checkLine($line, $i + 1, $filePath, $allowedPrefixes, $safePatterns);
        if ($issues) {
            $violations = array_merge($violations, $issues);
        }
    }
}

// Output results.
echo "TWIG-LANGPREFIX-001: Hardcoded URL Language Prefix Check\n";
echo str_repeat('=', 60) . "\n";
echo "Checked: {$checkedFiles} Twig files\n\n";

if (empty($violations)) {
    echo "\033[32m✓ PASS\033[0m — No hardcoded URLs without language prefix found.\n";
    exit(0);
}

echo "\033[31m✗ FAIL\033[0m — " . count($violations) . " hardcoded URL(s) without language prefix:\n\n";

foreach ($violations as $v) {
    echo "  \033[33m{$v['file']}:{$v['line']}\033[0m\n";
    echo "    URL: {$v['url']}\n";
    echo "    Context: " . substr($v['context'], 0, 120) . "\n\n";
}

echo "FIX: Use {{ lp }}/path, {{ path('route.name') }}, or ped_urls.key\n";
echo "     for all internal URLs in Twig templates.\n";
exit(1);
