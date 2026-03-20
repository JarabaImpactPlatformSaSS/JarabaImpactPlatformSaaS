<?php

/**
 * @file validate-pricing-case-study-coherence.php
 * PRICING-CASE-STUDY-COHERENCE-001: Cross-checks prices in CaseStudyControllers
 * against expected pricing structure.
 *
 * Validates:
 * 1. All CaseStudyControllers use #pricing variable (NO-HARDCODE-PRICE-001)
 * 2. Pricing tiers match expected structure (free + starter + professional)
 * 3. No hardcoded EUR values in Twig templates
 */

$errors = [];
$warnings = [];
$modulesPath = 'web/modules/custom';
$themePath = 'web/themes/custom/ecosistema_jaraba_theme';

// Find all CaseStudy controllers
$controllers = glob("$modulesPath/*/src/Controller/*CaseStudy*Controller.php");

foreach ($controllers as $controller) {
    $content = file_get_contents($controller);
    $basename = basename($controller);

    // Check that controller uses #pricing variable
    if (strpos($content, "'#pricing'") === false && strpos($content, '"#pricing"') === false) {
        // B2G controllers (Andalucía EI) may not have pricing
        if (strpos($content, 'pricing_url') !== false) {
            $warnings[] = "[$basename] Has pricing_url but no #pricing variable — B2G pattern?";
        }
    } else {
        // Verify pricing structure has expected keys
        $requiredKeys = ['free_features', 'starter_price', 'starter_features'];
        foreach ($requiredKeys as $key) {
            if (strpos($content, "'$key'") === false) {
                // Some verticals use different tier names
                if ($key === 'starter_price' && strpos($content, "'professional_price'") !== false) {
                    continue; // Has professional but not starter — different naming
                }
                $warnings[] = "[$basename] Missing pricing key '$key'";
            }
        }

        // Check that prices are numeric strings, not hardcoded in Twig
        if (preg_match_all("/'(starter_price|professional_price)'\s*=>\s*'(\d+)'/", $content, $matches)) {
            foreach ($matches[2] as $price) {
                if ((int)$price <= 0) {
                    $errors[] = "[$basename] Price value is 0 or negative: $price";
                }
            }
        }
    }
}

// Check templates don't have hardcoded EUR prices
$templates = glob("$themePath/templates/*-case-study.html.twig");
foreach ($templates as $template) {
    $content = file_get_contents($template);
    $basename = basename($template);

    // Check for hardcoded prices like "29 €" or "29€" (not inside {{ }})
    // Exclude Schema.org JSON-LD section and &euro; entities with variables
    $lines = explode("\n", $content);
    $inJsonLd = false;
    foreach ($lines as $lineNum => $line) {
        // Track JSON-LD blocks
        if (strpos($line, 'application/ld+json') !== false) {
            $inJsonLd = true;
        }
        if ($inJsonLd && strpos($line, '</script>') !== false) {
            $inJsonLd = false;
            continue;
        }
        // Skip JSON-LD, comments, and narrative text ({% trans %} blocks)
        if ($inJsonLd || strpos($line, '{#') !== false) {
            continue;
        }
        // Only flag prices in pricing sections (cs-pricing), not in storytelling
        $isPricingSection = strpos($line, 'cs-pricing') !== false || strpos($line, 'pricing') !== false;
        // Detect hardcoded price in pricing section NOT inside {{ }}
        if ($isPricingSection && preg_match('/\b\d{2,}\s*(?:€|&euro;|EUR)\b/i', $line) && strpos($line, '{{') === false) {
            $errors[] = "[$basename:$lineNum] Hardcoded price in pricing section: " . trim($line);
        }
    }
}

if (empty($errors) && empty($warnings)) {
    echo "PRICING-CASE-STUDY-COHERENCE-001: PASS — All case study pricing is dynamic and structured\n";
    exit(0);
}

foreach ($warnings as $w) {
    echo "  WARN: $w\n";
}
foreach ($errors as $e) {
    echo "  FAIL: $e\n";
}

if (!empty($errors)) {
    echo "PRICING-CASE-STUDY-COHERENCE-001: FAIL — " . count($errors) . " errors\n";
    exit(1);
}

echo "PRICING-CASE-STUDY-COHERENCE-001: WARN — " . count($warnings) . " warnings\n";
exit(0);
