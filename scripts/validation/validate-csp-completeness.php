<?php

declare(strict_types=1);

/**
 * @file
 * CSP-DOMAIN-COMPLETENESS-001: Verify CSP includes ALL external domains referenced in code.
 *
 * Reads the Content Security Policy from SecurityHeadersSubscriber.php,
 * extracts domains per directive, then scans JS/Twig/PHP files for external
 * domain references. Reports domains found in code but missing from CSP.
 *
 * SCAN TARGETS:
 * - SecurityHeadersSubscriber.php: CSP directives (script-src, connect-src, etc.)
 * - JS files: fetch(), new URL(), src=, XMLHttpRequest.open()
 * - Twig files: src=, href= with external URLs
 * - PHP files: Guzzle requests, file_get_contents(), curl calls
 *
 * KNOWN REQUIRED DOMAINS (CSP-STRIPE-SCRIPT-001, CSP-POLICY-001):
 *   js.stripe.com, api.stripe.com, www.google.com, www.gstatic.com,
 *   generativelanguage.googleapis.com, fonts.googleapis.com, fonts.gstatic.com
 *
 * EXIT CODES:
 *   0 = Always (warnings only — CSP changes need manual review)
 *
 * Usage: php scripts/validation/validate-csp-completeness.php
 *
 * @see CSP-POLICY-001
 * @see CSP-STRIPE-SCRIPT-001
 */

$root = dirname(__DIR__, 2);
$cspFile = $root . '/web/modules/custom/ecosistema_jaraba_core/src/EventSubscriber/SecurityHeadersSubscriber.php';

// ═══════════════════════════════════════════════════════════════
// Banner
// ═══════════════════════════════════════════════════════════════
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║  CSP-DOMAIN-COMPLETENESS-001                                ║\n";
echo "║  Verify CSP covers all external domains referenced in code  ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════
// Configuration
// ═══════════════════════════════════════════════════════════════

/** Domains to ignore when scanning code references. */
$ignoreDomains = [
    'localhost',
    '127.0.0.1',
    '0.0.0.0',
    'example.com',
    'example.org',
    'www.example.com',
    'example.net',
    'schema.org',
    'www.w3.org',
    'www.drupal.org',
    'drupal.org',
    'github.com',
    'www.github.com',
    'packagist.org',
    'getcomposer.org',
    'wikipedia.org',
    'en.wikipedia.org',
    'creativecommons.org',
    'spdx.org',
    'www.php.net',
    'php.net',
    'symfony.com',
    'developer.mozilla.org',
    'httpd.apache.org',
    'nginx.org',
];

/** Domain patterns to ignore (regex). */
$ignorePatterns = [
    '/\.lndo\.site$/',          // Lando dev
    '/^localhost(:\d+)?$/',     // Localhost with port
    '/\.local$/',               // Local dev
    '/\.test$/',                // Local test
    '/\.example\./',            // Example domains
    '/\.internal$/',            // Internal
    '/plataformadeecosistemas\.es$/',  // Own domain
    '/jaraba-saas\./',          // Own dev domain
    '/ecosistemajaraba\./',     // Own domain variants
];

/** CSP directive to resource type mapping for contextual checks. */
$directiveContextMap = [
    'script-src'  => ['js', 'twig_script', 'php_script'],
    'style-src'   => ['twig_style', 'css_import'],
    'font-src'    => ['css_font', 'twig_font'],
    'img-src'     => ['twig_img', 'js_img', 'php_img'],
    'connect-src' => ['js_fetch', 'php_api'],
    'frame-src'   => ['twig_iframe', 'js_iframe'],
];

/** Known required domains and their expected CSP directives. */
$knownRequired = [
    'js.stripe.com' => ['script-src', 'connect-src', 'frame-src'],
    'api.stripe.com' => ['connect-src'],
    'www.google.com' => ['script-src', 'connect-src', 'frame-src'],
    'www.gstatic.com' => ['script-src', 'img-src'],
    'generativelanguage.googleapis.com' => ['connect-src'],
    'fonts.googleapis.com' => ['style-src'],
    'fonts.gstatic.com' => ['font-src'],
];

// ═══════════════════════════════════════════════════════════════
// Step 1: Parse CSP from SecurityHeadersSubscriber.php
// ═══════════════════════════════════════════════════════════════
echo "[1/4] Parsing CSP from SecurityHeadersSubscriber.php...\n";

if (!file_exists($cspFile)) {
    echo "  ERROR: CSP file not found: $cspFile\n";
    exit(0);
}

$cspContent = file_get_contents($cspFile);
if ($cspContent === false) {
    echo "  ERROR: Cannot read CSP file\n";
    exit(0);
}

/**
 * Extract CSP directives and their domains from the PHP source.
 *
 * @param string $source The PHP source code.
 * @return array<string, array<string>> Map of directive => domains.
 */
function extractCspDirectives(string $source): array
{
    $directives = [];

    // Match the CSP policy string array in implode('; ', [...])
    // Each line like: "script-src 'self' 'unsafe-inline' domain1 domain2",
    if (preg_match_all('/"((?:default|script|style|font|img|connect|frame|media|object|worker|child|form-action|base-uri)-src\s+[^"]+)"/m', $source, $matches)) {
        foreach ($matches[1] as $directive) {
            $parts = preg_split('/\s+/', trim($directive));
            $name = array_shift($parts);
            $domains = [];
            foreach ($parts as $part) {
                // Skip CSP keywords and special values
                if (in_array($part, ["'self'", "'unsafe-inline'", "'unsafe-eval'", "'none'", "'strict-dynamic'", "'nonce-*'", 'data:', 'blob:', 'https:', 'http:'], true)) {
                    continue;
                }
                $domains[] = $part;
            }
            $directives[$name] = $domains;
        }
    }

    return $directives;
}

$cspDirectives = extractCspDirectives($cspContent);

if (empty($cspDirectives)) {
    echo "  WARNING: No CSP directives found in source. Is the format expected?\n";
    exit(0);
}

$totalCspDomains = 0;
foreach ($cspDirectives as $directive => $domains) {
    $count = count($domains);
    $totalCspDomains += $count;
    echo "  $directive: " . implode(', ', $domains) . " ($count)\n";
}
echo "  Total CSP domains (across directives): $totalCspDomains\n\n";

// Build a flat set of all CSP domains for quick lookup.
$allCspDomains = [];
foreach ($cspDirectives as $domains) {
    foreach ($domains as $domain) {
        // Normalize wildcard domains: *.stripe.com covers sub.stripe.com
        $allCspDomains[$domain] = true;
    }
}

// ═══════════════════════════════════════════════════════════════
// Step 2: Scan codebase for external domain references
// ═══════════════════════════════════════════════════════════════
echo "[2/4] Scanning codebase for external domain references...\n";

$scanPaths = [
    'js'   => [
        $root . '/web/modules/custom',
        $root . '/web/themes/custom',
    ],
    'twig' => [
        $root . '/web/modules/custom',
        $root . '/web/themes/custom',
    ],
    'php'  => [
        $root . '/web/modules/custom',
    ],
];

/**
 * Recursively collect files matching extensions.
 *
 * @param string $dir Directory to scan.
 * @param array<string> $extensions File extensions (without dot).
 * @return array<string> File paths.
 */
function collectFiles(string $dir, array $extensions): array
{
    if (!is_dir($dir)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $ext = strtolower($file->getExtension());
        if (in_array($ext, $extensions, true)) {
            $path = $file->getPathname();
            // Skip vendor, node_modules, .min.js files from CDN vendors
            if (str_contains($path, '/node_modules/') || str_contains($path, '/vendor/')) {
                continue;
            }
            $files[] = $path;
        }
    }

    return $files;
}

/**
 * Extract external domains from a URL string.
 *
 * @param string $url The URL to parse.
 * @return string|null The domain or null if not external.
 */
function extractDomain(string $url): ?string
{
    // Must start with https:// or http://
    if (!preg_match('#^https?://#i', $url)) {
        return null;
    }

    $parsed = parse_url($url);
    if (!isset($parsed['host'])) {
        return null;
    }

    return strtolower($parsed['host']);
}

/**
 * Check if a domain should be ignored.
 *
 * @param string $domain The domain to check.
 * @param array<string> $ignoreDomains Exact domains to ignore.
 * @param array<string> $ignorePatterns Regex patterns to ignore.
 * @return bool True if domain should be ignored.
 */
function shouldIgnoreDomain(string $domain, array $ignoreDomains, array $ignorePatterns): bool
{
    if (in_array($domain, $ignoreDomains, true)) {
        return true;
    }

    foreach ($ignorePatterns as $pattern) {
        if (preg_match($pattern, $domain)) {
            return true;
        }
    }

    return false;
}

/**
 * Check if content is inside a comment (HTML, Twig, PHP, JS).
 *
 * Simple heuristic: check if the line containing the URL looks like a comment.
 *
 * @param string $line The line containing the URL.
 * @return bool True if likely inside a comment.
 */
function isLikelyComment(string $line): bool
{
    $trimmed = ltrim($line);
    // PHP/JS single-line comments
    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '#')) {
        return true;
    }
    // PHP/JS block comment line
    if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
        return true;
    }
    // Twig comment
    if (str_contains($trimmed, '{#')) {
        return true;
    }
    // HTML comment
    if (str_contains($trimmed, '<!--')) {
        return true;
    }
    // @see, @link in docblocks
    if (preg_match('/^\s*\*?\s*@(see|link|file|docs)\s/', $trimmed)) {
        return true;
    }

    return false;
}

/** @var array<string, array<array{file: string, line: int, context: string}>> Domain => references */
$foundDomains = [];
$scannedFiles = 0;

// --- Scan JS files ---
$jsFiles = [];
foreach ($scanPaths['js'] as $dir) {
    $jsFiles = array_merge($jsFiles, collectFiles($dir, ['js']));
}

foreach ($jsFiles as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $scannedFiles++;

    $lines = explode("\n", $content);
    foreach ($lines as $lineNum => $line) {
        if (isLikelyComment($line)) {
            continue;
        }

        // Match URLs in JS: fetch('https://...'), new URL('https://...'), src = "https://...", etc.
        if (preg_match_all('#https?://[a-zA-Z0-9._-]+(?:\.[a-zA-Z]{2,})(?:[/\w.?&=%-]*)#', $line, $urlMatches)) {
            foreach ($urlMatches[0] as $url) {
                $domain = extractDomain($url);
                if ($domain !== null && !shouldIgnoreDomain($domain, $ignoreDomains, $ignorePatterns)) {
                    $relPath = str_replace($root . '/', '', $file);
                    $foundDomains[$domain][] = [
                        'file' => $relPath,
                        'line' => $lineNum + 1,
                        'context' => 'js',
                    ];
                }
            }
        }
    }
}

// --- Scan Twig files ---
$twigFiles = [];
foreach ($scanPaths['twig'] as $dir) {
    $twigFiles = array_merge($twigFiles, collectFiles($dir, ['twig']));
}

foreach ($twigFiles as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $scannedFiles++;

    $lines = explode("\n", $content);
    foreach ($lines as $lineNum => $line) {
        if (isLikelyComment($line)) {
            continue;
        }

        // Match URLs in Twig: src="https://...", href="https://...", action="https://..."
        if (preg_match_all('#https?://[a-zA-Z0-9._-]+(?:\.[a-zA-Z]{2,})(?:[/\w.?&=%-]*)#', $line, $urlMatches)) {
            foreach ($urlMatches[0] as $url) {
                $domain = extractDomain($url);
                if ($domain !== null && !shouldIgnoreDomain($domain, $ignoreDomains, $ignorePatterns)) {
                    $relPath = str_replace($root . '/', '', $file);
                    $foundDomains[$domain][] = [
                        'file' => $relPath,
                        'line' => $lineNum + 1,
                        'context' => 'twig',
                    ];
                }
            }
        }
    }
}

// --- Scan PHP files ---
$phpFiles = [];
foreach ($scanPaths['php'] as $dir) {
    $phpFiles = array_merge($phpFiles, collectFiles($dir, ['php', 'module', 'theme', 'install']));
}

foreach ($phpFiles as $file) {
    // Skip the CSP file itself — we already parsed it
    if (realpath($file) === realpath($cspFile)) {
        continue;
    }

    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }
    $scannedFiles++;

    $lines = explode("\n", $content);
    foreach ($lines as $lineNum => $line) {
        if (isLikelyComment($line)) {
            continue;
        }

        // Match URLs in PHP strings: 'https://...', "https://..."
        if (preg_match_all('#https?://[a-zA-Z0-9._-]+(?:\.[a-zA-Z]{2,})(?:[/\w.?&=%-]*)#', $line, $urlMatches)) {
            foreach ($urlMatches[0] as $url) {
                $domain = extractDomain($url);
                if ($domain !== null && !shouldIgnoreDomain($domain, $ignoreDomains, $ignorePatterns)) {
                    $relPath = str_replace($root . '/', '', $file);
                    $foundDomains[$domain][] = [
                        'file' => $relPath,
                        'line' => $lineNum + 1,
                        'context' => 'php',
                    ];
                }
            }
        }
    }
}

echo "  Scanned $scannedFiles files\n";
echo "  Found " . count($foundDomains) . " unique external domains\n\n";

// ═══════════════════════════════════════════════════════════════
// Step 3: Cross-reference domains against CSP
// ═══════════════════════════════════════════════════════════════
echo "[3/4] Cross-referencing domains against CSP policy...\n";

/**
 * Check if a domain is covered by CSP (including wildcard matching).
 *
 * @param string $domain The domain to check.
 * @param array<string, bool> $cspDomains Map of CSP domains.
 * @return bool True if domain is covered.
 */
function isDomainInCsp(string $domain, array $cspDomains): bool
{
    // Exact match
    if (isset($cspDomains[$domain])) {
        return true;
    }

    // Wildcard match: *.stripe.com covers anything.stripe.com
    foreach (array_keys($cspDomains) as $cspDomain) {
        if (str_starts_with($cspDomain, '*.')) {
            $suffix = substr($cspDomain, 1); // .stripe.com
            if (str_ends_with($domain, $suffix)) {
                return true;
            }
        }
    }

    // Parent domain match: if CSP has fonts.gstatic.com, it covers fonts.gstatic.com
    // but NOT sub.fonts.gstatic.com. Already handled by exact match above.

    return false;
}

$coveredDomains = [];
$missingDomains = [];

foreach ($foundDomains as $domain => $references) {
    if (isDomainInCsp($domain, $allCspDomains)) {
        $coveredDomains[$domain] = $references;
    } else {
        $missingDomains[$domain] = $references;
    }
}

echo "  Covered by CSP: " . count($coveredDomains) . "\n";
echo "  Missing from CSP: " . count($missingDomains) . "\n\n";

// ═══════════════════════════════════════════════════════════════
// Step 4: Check known required domains
// ═══════════════════════════════════════════════════════════════
echo "[4/4] Checking known required domains...\n";

$knownWarnings = 0;
foreach ($knownRequired as $domain => $requiredDirectives) {
    foreach ($requiredDirectives as $directive) {
        $directiveDomains = $cspDirectives[$directive] ?? [];
        $found = false;
        foreach ($directiveDomains as $cspDomain) {
            if ($cspDomain === $domain) {
                $found = true;
                break;
            }
            // Wildcard check
            if (str_starts_with($cspDomain, '*.') && str_ends_with($domain, substr($cspDomain, 1))) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "  OK  $domain in $directive\n";
        } else {
            echo "  WARN  $domain MISSING from $directive\n";
            $knownWarnings++;
        }
    }
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// Report
// ═══════════════════════════════════════════════════════════════
echo "═══════════════════════════════════════════════════════════════\n";
echo "RESULTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($knownWarnings > 0) {
    echo "KNOWN REQUIRED DOMAINS — $knownWarnings MISSING:\n";
    foreach ($knownRequired as $domain => $requiredDirectives) {
        foreach ($requiredDirectives as $directive) {
            $directiveDomains = $cspDirectives[$directive] ?? [];
            $found = false;
            foreach ($directiveDomains as $cspDomain) {
                if ($cspDomain === $domain || (str_starts_with($cspDomain, '*.') && str_ends_with($domain, substr($cspDomain, 1)))) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                echo "  WARN  $domain not in $directive\n";
            }
        }
    }
    echo "\n";
}

if (!empty($missingDomains)) {
    echo "DOMAINS IN CODE BUT NOT IN CSP (" . count($missingDomains) . " domains):\n\n";

    // Sort by number of references (most referenced first)
    uasort($missingDomains, static fn(array $a, array $b): int => count($b) <=> count($a));

    foreach ($missingDomains as $domain => $references) {
        $refCount = count($references);
        $contexts = array_unique(array_column($references, 'context'));
        echo "  WARN  $domain ($refCount ref" . ($refCount > 1 ? 's' : '') . ", ctx: " . implode('+', $contexts) . ")\n";

        // Show up to 3 example locations
        $shown = 0;
        $uniqueFiles = [];
        foreach ($references as $ref) {
            $key = $ref['file'] . ':' . $ref['line'];
            if (isset($uniqueFiles[$key])) {
                continue;
            }
            $uniqueFiles[$key] = true;
            echo "         " . $ref['file'] . ':' . $ref['line'] . "\n";
            $shown++;
            if ($shown >= 3) {
                $remaining = count($references) - $shown;
                if ($remaining > 0) {
                    echo "         ... and $remaining more\n";
                }
                break;
            }
        }
    }
    echo "\n";
} else {
    echo "All external domains found in code are covered by CSP.\n\n";
}

if (!empty($coveredDomains)) {
    echo "COVERED DOMAINS (" . count($coveredDomains) . "):\n";
    foreach (array_keys($coveredDomains) as $domain) {
        echo "  OK    $domain\n";
    }
    echo "\n";
}

// Summary
$totalWarnings = $knownWarnings + count($missingDomains);
echo "───────────────────────────────────────────────────────────────\n";
echo "Summary: $scannedFiles files scanned, " . count($foundDomains) . " external domains found\n";
echo "         " . count($coveredDomains) . " covered, " . count($missingDomains) . " missing from CSP, $knownWarnings known-required missing\n";

if ($totalWarnings > 0) {
    echo "         $totalWarnings warning(s) — manual review recommended\n";
} else {
    echo "         CSP appears complete.\n";
}
echo "───────────────────────────────────────────────────────────────\n";

// Always exit 0 — CSP changes need manual review, not auto-blocking.
exit(0);
