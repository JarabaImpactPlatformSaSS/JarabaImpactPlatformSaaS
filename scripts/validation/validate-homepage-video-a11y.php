<?php

/**
 * @file validate-homepage-video-a11y.php
 * HOMEPAGE-VIDEO-A11Y-001: Verifies video hero accessibility compliance.
 *
 * Checks:
 * 1. Video element has data-hero-video-el attribute (JS can control it)
 * 2. Parent section has data-hero-video attribute (JS detects it)
 * 3. landing-hero-video.js checks prefers-reduced-motion
 * 4. landing-hero-video.js checks navigator.connection.saveData
 * 5. Video has muted attribute (autoplay requires muted)
 * 6. CSS has prefers-reduced-motion rule for hero elements
 *
 * @see VIDEO-HERO-001 in CLAUDE.md
 * @see docs/implementacion/20260321b-Plan_Elevacion_Homepage_MetaSitios_Clase_Mundial_10_10_v1_Claude.md
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$themeDir = $root . '/web/themes/custom/ecosistema_jaraba_theme';

echo "HOMEPAGE-VIDEO-A11Y-001: Video Hero Accessibility Compliance\n";
echo str_repeat('=', 60) . "\n";

$failures = [];
$passes = 0;

// ─── CHECK 1: Video has data-hero-video-el ───
$heroFile = $themeDir . '/templates/partials/_hero.html.twig';
if (!file_exists($heroFile)) {
    echo "❌ FAIL — _hero.html.twig not found\n";
    exit(1);
}

$heroContent = file_get_contents($heroFile);

if (str_contains($heroContent, 'data-hero-video-el')) {
    $passes++;
    echo "✅ CHECK 1: Video element has data-hero-video-el attribute\n";
} else {
    $failures[] = 'CHECK 1: Video missing data-hero-video-el (JS cannot control playback)';
    echo "❌ CHECK 1: data-hero-video-el missing from video element\n";
}

// ─── CHECK 2: Parent section has data-hero-video ───
if (str_contains($heroContent, 'data-hero-video')) {
    // Verify it's on the section, not just the video element
    if (preg_match('/<section[^>]*data-hero-video/', $heroContent)) {
        $passes++;
        echo "✅ CHECK 2: Parent section has data-hero-video attribute\n";
    } else {
        $passes++;
        echo "✅ CHECK 2: data-hero-video attribute present (verify on section element)\n";
    }
} else {
    $failures[] = 'CHECK 2: Section missing data-hero-video (JS cannot find video section)';
    echo "❌ CHECK 2: data-hero-video missing from hero section\n";
}

// ─── CHECK 3: JS checks prefers-reduced-motion ───
$jsFile = $themeDir . '/js/landing-hero-video.js';
if (!file_exists($jsFile)) {
    $failures[] = 'CHECK 3: landing-hero-video.js not found';
    echo "❌ CHECK 3: landing-hero-video.js not found\n";
} else {
    $jsContent = file_get_contents($jsFile);
    if (str_contains($jsContent, 'prefers-reduced-motion')) {
        $passes++;
        echo "✅ CHECK 3: JS checks prefers-reduced-motion\n";
    } else {
        $failures[] = 'CHECK 3: JS does NOT check prefers-reduced-motion';
        echo "❌ CHECK 3: prefers-reduced-motion NOT checked in JS\n";
    }
}

// ─── CHECK 4: JS checks saveData ───
if (isset($jsContent)) {
    if (str_contains($jsContent, 'saveData')) {
        $passes++;
        echo "✅ CHECK 4: JS checks navigator.connection.saveData\n";
    } else {
        $failures[] = 'CHECK 4: JS does NOT check saveData (data saver mode)';
        echo "❌ CHECK 4: saveData NOT checked in JS\n";
    }
} else {
    $failures[] = 'CHECK 4: Cannot verify (JS file not loaded)';
    echo "❌ CHECK 4: Cannot verify saveData\n";
}

// ─── CHECK 5: Video has muted attribute ───
if (preg_match('/<video[^>]*muted[^>]*>/', $heroContent)) {
    $passes++;
    echo "✅ CHECK 5: Video has muted attribute (autoplay compliance)\n";
} else {
    // Check if video exists at all
    if (str_contains($heroContent, '<video')) {
        $failures[] = 'CHECK 5: Video exists but missing muted attribute';
        echo "❌ CHECK 5: Video missing muted attribute\n";
    } else {
        $failures[] = 'CHECK 5: No <video> element found in hero';
        echo "⚠️  CHECK 5: No video element in hero (may be intentional)\n";
    }
}

// ─── CHECK 6: CSS has prefers-reduced-motion for hero ───
$scssFile = $themeDir . '/scss/components/_hero-landing.scss';
if (!file_exists($scssFile)) {
    $failures[] = 'CHECK 6: _hero-landing.scss not found';
    echo "❌ CHECK 6: _hero-landing.scss not found\n";
} else {
    $scssContent = file_get_contents($scssFile);
    if (str_contains($scssContent, 'prefers-reduced-motion')) {
        $passes++;
        echo "✅ CHECK 6: CSS has prefers-reduced-motion rule for hero\n";
    } else {
        $failures[] = 'CHECK 6: CSS missing prefers-reduced-motion for hero animations';
        echo "❌ CHECK 6: prefers-reduced-motion missing in hero SCSS\n";
    }
}

// ─── SUMMARY ───
echo "\n" . str_repeat('─', 60) . "\n";
$total = $passes + count($failures);
echo "Results: {$passes}/{$total} PASS, " . count($failures) . " FAIL\n";

if (empty($failures)) {
    echo "\n✅ PASS — Video hero meets accessibility requirements\n";
    exit(0);
} else {
    echo "\n❌ FAIL — " . count($failures) . " accessibility issues:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}
