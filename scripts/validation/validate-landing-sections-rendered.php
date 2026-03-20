<?php
/**
 * LANDING-SECTIONS-RENDERED-001
 * Validates that vertical landing pages render expected sections.
 *
 * Usage: php scripts/validation/validate-landing-sections-rendered.php
 * Exit: 0 = all pass, 1 = any fail
 */

$base_url = 'https://jaraba-saas.lndo.site';

$landings = [
    '/es/agroconecta',
    '/es/comercioconecta',
    '/es/serviciosconecta',
    '/es/empleabilidad',
    '/es/emprendimiento',
    '/es/jarabalex',
    '/es/instituciones',
    '/es/formacion',
    '/es/talento',
];

// Key classes that MUST be present in every landing.
$required_classes = [
    'landing-hero',
    'landing-pricing',
    'landing-social-proof',
    'landing-final-cta',
];

$min_sections = 8;

// Stream context: suppress SSL verification for local dev (Lando self-signed cert).
$context = stream_context_create([
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
    'http' => [
        'timeout'          => 15,
        'follow_location'  => true,
        'max_redirects'    => 5,
        'header'           => [
            'Accept: text/html,application/xhtml+xml',
            'User-Agent: JarabaValidator/1.0 LANDING-SECTIONS-RENDERED-001',
        ],
    ],
]);

$errors   = [];
$results  = [];

foreach ($landings as $path) {
    $url  = $base_url . $path;
    $html = @file_get_contents($url, false, $context);

    if ($html === false) {
        $errors[]  = $path;
        $results[] = [
            'path'    => $path,
            'ok'      => false,
            'reason'  => 'HTTP request failed (is lndo.site running?)',
            'count'   => 0,
            'found'   => [],
            'missing' => $required_classes,
        ];
        continue;
    }

    // Count opening tags of landing sections.
    $section_count = substr_count($html, '<section class="landing-');

    // Check required classes.
    $found   = [];
    $missing = [];
    foreach ($required_classes as $cls) {
        // Match both `class="landing-foo"` and `class="landing-foo ..."`.
        if (preg_match('/class="' . preg_quote($cls, '/') . '(?:\s|")/', $html)) {
            $found[] = $cls;
        }
        else {
            $missing[] = $cls;
        }
    }

    $ok = ($section_count >= $min_sections) && empty($missing);

    if (!$ok) {
        $errors[] = $path;
    }

    $results[] = [
        'path'    => $path,
        'ok'      => $ok,
        'reason'  => '',
        'count'   => $section_count,
        'found'   => $found,
        'missing' => $missing,
    ];
}

// ---- Output ----------------------------------------------------------------

$green = "\033[32m";
$red   = "\033[31m";
$reset = "\033[0m";

echo "LANDING-SECTIONS-RENDERED-001: Landing section completeness\n";

foreach ($results as $r) {
    if ($r['reason'] !== '') {
        // Fetch error.
        printf(
            "  %s✗%s %s — %s\n",
            $red,
            $reset,
            $r['path'],
            $r['reason']
        );
        continue;
    }

    // Build key-class summary.
    $short_found = array_map(
        static fn(string $cls): string => str_replace('landing-', '', $cls),
        $r['found']
    );

    if ($r['ok']) {
        printf(
            "  %s✓%s %s — %d sections (%s)\n",
            $green,
            $reset,
            $r['path'],
            $r['count'],
            implode(', ', $short_found)
        );
    }
    else {
        $issues = [];

        if ($r['count'] < $min_sections) {
            $issues[] = sprintf('%d sections (min %d)', $r['count'], $min_sections);
        }

        if (!empty($r['missing'])) {
            $short_missing = array_map(
                static fn(string $cls): string => str_replace('landing-', '', $cls),
                $r['missing']
            );
            $issues[] = 'MISSING: ' . implode(', ', $short_missing);
        }

        printf(
            "  %s✗%s %s — %s\n",
            $red,
            $reset,
            $r['path'],
            implode(' · ', $issues)
        );
    }
}

echo "\n";

if (empty($errors)) {
    printf(
        "%s✅ All %d landings pass section completeness (≥%d sections + 4 key classes).%s\n",
        $green,
        count($landings),
        $min_sections,
        $reset
    );
    exit(0);
}

printf(
    "%s❌ LANDING-SECTIONS-RENDERED-001: %d/%d landings FAILED section check.%s\n",
    $red,
    count($errors),
    count($landings),
    $reset
);
exit(1);
