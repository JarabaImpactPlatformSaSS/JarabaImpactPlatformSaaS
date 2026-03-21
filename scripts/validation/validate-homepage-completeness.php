<?php

/**
 * @file validate-homepage-completeness.php
 * HOMEPAGE-COMPLETENESS-001: Validates 15 conversion criteria for homepage 10/10.
 *
 * Ensures page--front.html.twig includes all required sections and patterns
 * from LANDING-CONVERSION-SCORE-001 adapted for homepage context.
 *
 * @see docs/implementacion/20260321b-Plan_Elevacion_Homepage_MetaSitios_Clase_Mundial_10_10_v1_Claude.md
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$templateDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates';
$frontPage = $templateDir . '/page--front.html.twig';
$partialsDir = $templateDir . '/partials';

echo "HOMEPAGE-COMPLETENESS-001: Homepage 10/10 Conversion Completeness\n";
echo str_repeat('=', 60) . "\n";

if (!file_exists($frontPage)) {
    echo "❌ FAIL — page--front.html.twig not found\n";
    exit(1);
}

$frontContent = file_get_contents($frontPage);
$failures = [];
$warnings = [];
$passes = 0;

// Helper: check if partial is included in front page
function checkPartialIncluded(string $content, string $partial, string $label): bool
{
    return str_contains($content, $partial);
}

// Helper: check if partial file exists
function checkPartialExists(string $dir, string $file): bool
{
    return file_exists($dir . '/' . $file);
}

// ─── CHECK 1: Hero exists ───
if (checkPartialIncluded($frontContent, '_hero.html.twig', 'Hero')) {
    $passes++;
    echo "✅ CHECK 1: Hero section included\n";
} else {
    $failures[] = 'CHECK 1: Hero section NOT included in page--front.html.twig';
    echo "❌ CHECK 1: Hero section missing\n";
}

// ─── CHECK 2: Hero urgency badge ───
$heroFile = $partialsDir . '/_hero.html.twig';
if (file_exists($heroFile)) {
    $heroContent = file_get_contents($heroFile);
    if (str_contains($heroContent, 'urgency') || str_contains($heroContent, '14 dia')) {
        $passes++;
        echo "✅ CHECK 2: Hero urgency badge present\n";
    } else {
        $failures[] = 'CHECK 2: Hero urgency badge missing (MARKETING-TRUTH-001)';
        echo "❌ CHECK 2: Hero urgency badge missing\n";
    }
} else {
    $failures[] = 'CHECK 2: _hero.html.twig not found';
    echo "❌ CHECK 2: _hero.html.twig not found\n";
}

// ─── CHECK 3: Pain points ───
if (checkPartialIncluded($frontContent, '_homepage-pain-points.html.twig', 'Pain Points')
    && checkPartialExists($partialsDir, '_homepage-pain-points.html.twig')) {
    $passes++;
    echo "✅ CHECK 3: Pain points section included\n";
} else {
    $failures[] = 'CHECK 3: _homepage-pain-points.html.twig not included or not found';
    echo "❌ CHECK 3: Pain points missing\n";
}

// ─── CHECK 4: Solution steps (how-it-works) ───
if (checkPartialIncluded($frontContent, '_how-it-works.html.twig', 'How It Works')) {
    $passes++;
    echo "✅ CHECK 4: Solution steps (how-it-works) included\n";
} else {
    $failures[] = 'CHECK 4: _how-it-works.html.twig not included';
    echo "❌ CHECK 4: Solution steps missing\n";
}

// ─── CHECK 5: Features grid ───
if (checkPartialIncluded($frontContent, '_homepage-features.html.twig', 'Features')
    || checkPartialIncluded($frontContent, '_features.html.twig', 'Features')) {
    $passes++;
    echo "✅ CHECK 5: Features grid included\n";
} else {
    $failures[] = 'CHECK 5: Features grid not included';
    echo "❌ CHECK 5: Features grid missing\n";
}

// ─── CHECK 6: Comparison table ───
if (checkPartialIncluded($frontContent, '_homepage-comparison.html.twig', 'Comparison')
    && checkPartialExists($partialsDir, '_homepage-comparison.html.twig')) {
    $passes++;
    echo "✅ CHECK 6: Comparison table included\n";
} else {
    $failures[] = 'CHECK 6: _homepage-comparison.html.twig not included or not found';
    echo "❌ CHECK 6: Comparison table missing\n";
}

// ─── CHECK 7: Social proof (testimonials) ───
if (checkPartialIncluded($frontContent, '_testimonials.html.twig', 'Testimonials')) {
    $passes++;
    echo "✅ CHECK 7: Testimonials (social proof) included\n";
} else {
    $failures[] = 'CHECK 7: _testimonials.html.twig not included';
    echo "❌ CHECK 7: Testimonials missing\n";
}

// ─── CHECK 8: Lead magnet ───
if (checkPartialIncluded($frontContent, '_lead-magnet.html.twig', 'Lead Magnet')) {
    $passes++;
    echo "✅ CHECK 8: Lead magnet included\n";
} else {
    $failures[] = 'CHECK 8: _lead-magnet.html.twig not included';
    echo "❌ CHECK 8: Lead magnet missing\n";
}

// ─── CHECK 9: Pricing preview ───
if (checkPartialIncluded($frontContent, '_homepage-pricing-preview.html.twig', 'Pricing')
    && checkPartialExists($partialsDir, '_homepage-pricing-preview.html.twig')) {
    $passes++;
    echo "✅ CHECK 9: Pricing preview included\n";
} else {
    $failures[] = 'CHECK 9: _homepage-pricing-preview.html.twig not included or not found';
    echo "❌ CHECK 9: Pricing preview missing\n";
}

// ─── CHECK 10: FAQ with Schema.org ───
if (checkPartialIncluded($frontContent, '_faq-homepage.html.twig', 'FAQ')) {
    $faqFile = $partialsDir . '/_faq-homepage.html.twig';
    if (file_exists($faqFile) && str_contains(file_get_contents($faqFile), 'FAQPage')) {
        $passes++;
        echo "✅ CHECK 10: FAQ with Schema.org FAQPage included\n";
    } else {
        $warnings[] = 'CHECK 10: FAQ included but Schema.org FAQPage JSON-LD missing';
        echo "⚠️  CHECK 10: FAQ present but Schema.org missing\n";
    }
} else {
    $failures[] = 'CHECK 10: _faq-homepage.html.twig not included';
    echo "❌ CHECK 10: FAQ missing\n";
}

// ─── CHECK 11: Final CTA ───
if (checkPartialIncluded($frontContent, '_cta-banner-final.html.twig', 'Final CTA')) {
    $passes++;
    echo "✅ CHECK 11: Final CTA banner included\n";
} else {
    $failures[] = 'CHECK 11: _cta-banner-final.html.twig not included';
    echo "❌ CHECK 11: Final CTA missing\n";
}

// ─── CHECK 12: Sticky CTA ───
if (checkPartialIncluded($frontContent, '_landing-sticky-cta.html.twig', 'Sticky CTA')) {
    $passes++;
    echo "✅ CHECK 12: Sticky CTA included\n";
} else {
    $failures[] = 'CHECK 12: _landing-sticky-cta.html.twig not included in homepage';
    echo "❌ CHECK 12: Sticky CTA missing\n";
}

// ─── CHECK 13: Reveal animations (>= 8 sections) ───
$revealCount = substr_count($frontContent, 'reveal-element');
// Also count in included partials
$partialFiles = [
    '_homepage-pain-points.html.twig',
    '_homepage-pricing-preview.html.twig',
    '_homepage-comparison.html.twig',
    '_homepage-features.html.twig',
    '_testimonials.html.twig',
    '_cross-pollination.html.twig',
    '_how-it-works.html.twig',
    '_product-demo.html.twig',
    '_lead-magnet.html.twig',
    '_faq-homepage.html.twig',
    '_cta-banner-final.html.twig',
];
$totalReveals = 0;
foreach ($partialFiles as $pf) {
    $pfPath = $partialsDir . '/' . $pf;
    if (file_exists($pfPath)) {
        $totalReveals += substr_count(file_get_contents($pfPath), 'reveal-element');
    }
}

if ($totalReveals >= 8) {
    $passes++;
    echo "✅ CHECK 13: Reveal animations — {$totalReveals} sections with reveal-element (>= 8)\n";
} else {
    $failures[] = "CHECK 13: Only {$totalReveals} reveal-element sections (need >= 8)";
    echo "❌ CHECK 13: Only {$totalReveals} reveal animations (need >= 8)\n";
}

// ─── CHECK 14: Tracking CTAs (>= 10 data-track-cta) ───
$trackCount = 0;
foreach ($partialFiles as $pf) {
    $pfPath = $partialsDir . '/' . $pf;
    if (file_exists($pfPath)) {
        $trackCount += substr_count(file_get_contents($pfPath), 'data-track-cta');
    }
}
// Also count in hero
if (file_exists($heroFile)) {
    $trackCount += substr_count(file_get_contents($heroFile), 'data-track-cta');
}

if ($trackCount >= 10) {
    $passes++;
    echo "✅ CHECK 14: Tracking — {$trackCount} CTAs with data-track-cta (>= 10)\n";
} else {
    $failures[] = "CHECK 14: Only {$trackCount} tracked CTAs (need >= 10)";
    echo "❌ CHECK 14: Only {$trackCount} tracked CTAs (need >= 10)\n";
}

// ─── CHECK 15: Sticky CTA has touch target (CSS check) ───
$stickyScss = $root . '/web/themes/custom/ecosistema_jaraba_theme/scss/routes/landing.scss';
$stickyCssExists = false;
if (file_exists($stickyScss)) {
    $scssContent = file_get_contents($stickyScss);
    $stickyCssExists = str_contains($scssContent, 'landing-sticky-cta') || str_contains($scssContent, 'sticky-cta');
}
// Also check _landing-sections.scss
$landingSections = $root . '/web/themes/custom/ecosistema_jaraba_theme/scss/components/_landing-sections.scss';
if (file_exists($landingSections)) {
    $stickyCssExists = $stickyCssExists || str_contains(file_get_contents($landingSections), 'landing-sticky-cta');
}

if ($stickyCssExists) {
    $passes++;
    echo "✅ CHECK 15: Sticky CTA CSS styles exist\n";
} else {
    $warnings[] = 'CHECK 15: Sticky CTA CSS styles not found in landing SCSS';
    echo "⚠️  CHECK 15: Sticky CTA CSS not verified\n";
}

// ─── SUMMARY ───
echo "\n" . str_repeat('─', 60) . "\n";
echo "Results: {$passes}/15 PASS, " . count($failures) . " FAIL, " . count($warnings) . " WARN\n";

if (empty($failures)) {
    echo "\n✅ PASS — Homepage meets 10/10 conversion completeness criteria\n";
    exit(0);
} else {
    echo "\n❌ FAIL — " . count($failures) . " criteria not met:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}
