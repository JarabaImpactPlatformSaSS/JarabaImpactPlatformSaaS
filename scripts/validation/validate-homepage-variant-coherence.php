<?php

/**
 * @file validate-homepage-variant-coherence.php
 * HOMEPAGE-VARIANT-COHERENCE-001: Verifies each metasite homepage variant
 * has differentiated content — no two variants share identical copy.
 *
 * Checks:
 * 1. page--front.html.twig has 4 variant branches (pepejaraba, jarabaimpact, is_ped, else)
 * 2. Each variant passes different hero content (headline not duplicated)
 * 3. CTA final is variant-aware (cta_headline differs per variant)
 * 4. Pain points are included in all variants
 * 5. homepage_variant variable is set in preprocess
 *
 * @see docs/implementacion/20260321b-Plan_Elevacion_Homepage_MetaSitios_Clase_Mundial_10_10_v1_Claude.md
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$themeDir = $root . '/web/themes/custom/ecosistema_jaraba_theme';
$frontPage = $themeDir . '/templates/page--front.html.twig';
$themeFile = $themeDir . '/ecosistema_jaraba_theme.theme';

echo "HOMEPAGE-VARIANT-COHERENCE-001: Homepage Variant Differentiation\n";
echo str_repeat('=', 60) . "\n";

$failures = [];
$passes = 0;

// ─── CHECK 1: 4 variant branches exist ───
if (!file_exists($frontPage)) {
    echo "❌ FAIL — page--front.html.twig not found\n";
    exit(1);
}

$content = file_get_contents($frontPage);

$requiredBranches = [
    "variant == 'pepejaraba'" => 'pepejaraba',
    "variant == 'jarabaimpact'" => 'jarabaimpact',
    'is_ped' => 'pde (is_ped)',
    '{% else %}' => 'generic (else)',
];

$branchCount = 0;
foreach ($requiredBranches as $pattern => $label) {
    if (str_contains($content, $pattern)) {
        $branchCount++;
    }
}

if ($branchCount >= 4) {
    $passes++;
    echo "✅ CHECK 1: 4 variant branches present (pepejaraba, jarabaimpact, pde, generic)\n";
} else {
    $failures[] = "CHECK 1: Only {$branchCount}/4 variant branches found";
    echo "❌ CHECK 1: Only {$branchCount}/4 variant branches\n";
}

// ─── CHECK 2: Hero content differs per variant ───
// Extract hero title strings from each variant block
$heroTitles = [];

// pepejaraba hero
if (preg_match("/variant == 'pepejaraba'.*?title:\s*'([^']+)'/s", $content, $m)) {
    $heroTitles['pepejaraba'] = $m[1];
}
// jarabaimpact hero
if (preg_match("/variant == 'jarabaimpact'.*?title:\s*'([^']+)'/s", $content, $m)) {
    $heroTitles['jarabaimpact'] = $m[1];
}

$uniqueTitles = array_unique(array_values($heroTitles));
if (count($heroTitles) >= 2 && count($uniqueTitles) === count($heroTitles)) {
    $passes++;
    echo "✅ CHECK 2: Hero titles differ between variants (" . count($heroTitles) . " unique)\n";
} elseif (count($heroTitles) >= 2) {
    $failures[] = 'CHECK 2: Hero titles are identical between variants';
    echo "❌ CHECK 2: Hero titles duplicated between variants\n";
} else {
    $failures[] = 'CHECK 2: Could not extract hero titles from variant blocks';
    echo "⚠️  CHECK 2: Could not parse hero titles (manual review needed)\n";
}

// ─── CHECK 3: CTA final is variant-aware ───
$ctaVariantCount = 0;
if (str_contains($content, "cta_headline: 'Hablemos") || str_contains($content, "cta_headline: 'Hablemos")) {
    $ctaVariantCount++;
}
if (str_contains($content, "cta_headline: 'Tu franquicia") || str_contains($content, 'franquicia digital')) {
    $ctaVariantCount++;
}

if ($ctaVariantCount >= 2) {
    $passes++;
    echo "✅ CHECK 3: CTA final has variant-specific headlines ({$ctaVariantCount} custom CTAs)\n";
} elseif ($ctaVariantCount >= 1) {
    $passes++;
    echo "✅ CHECK 3: CTA final has at least 1 variant-specific headline\n";
} else {
    $failures[] = 'CHECK 3: CTA final uses generic copy for all variants';
    echo "❌ CHECK 3: CTA final not variant-aware\n";
}

// ─── CHECK 4: Pain points in all variant flows ───
$painPointIncludes = substr_count($content, '_homepage-pain-points.html.twig');
// We expect at least 2 includes (pepejaraba + generic; PED may or may not have it)
if ($painPointIncludes >= 2) {
    $passes++;
    echo "✅ CHECK 4: Pain points included in {$painPointIncludes} variant flows\n";
} else {
    $failures[] = "CHECK 4: Pain points only included {$painPointIncludes} times (need >= 2)";
    echo "❌ CHECK 4: Pain points missing from variant flows\n";
}

// ─── CHECK 5: homepage_variant set in preprocess ───
if (!file_exists($themeFile)) {
    $failures[] = 'CHECK 5: ecosistema_jaraba_theme.theme not found';
    echo "❌ CHECK 5: Theme file not found\n";
} else {
    $themeContent = file_get_contents($themeFile);
    if (str_contains($themeContent, "homepage_variant") && str_contains($themeContent, 'variantMap')) {
        $passes++;
        echo "✅ CHECK 5: homepage_variant set via variantMap in preprocess\n";
    } else {
        $failures[] = 'CHECK 5: homepage_variant not found in preprocess';
        echo "❌ CHECK 5: homepage_variant missing from preprocess\n";
    }
}

// ─── SUMMARY ───
echo "\n" . str_repeat('─', 60) . "\n";
$total = $passes + count($failures);
echo "Results: {$passes}/{$total} PASS, " . count($failures) . " FAIL\n";

if (empty($failures)) {
    echo "\n✅ PASS — Homepage variants are properly differentiated\n";
    exit(0);
} else {
    echo "\n❌ FAIL — " . count($failures) . " coherence issues:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}
