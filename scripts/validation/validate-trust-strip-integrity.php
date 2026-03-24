<?php

/**
 * @file
 * TRUST-STRIP-INTEGRITY-001: Validates trust strip partner catalog, assets,
 * Twig inclusion, variables contract, MARKETING-TRUTH-001 compliance,
 * CSS compilation, JS behavior, and vertical coverage.
 *
 * 10 checks for trust strip completeness and integrity.
 *
 * Usage: php scripts/validation/validate-trust-strip-integrity.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$theme = "$root/web/themes/custom/ecosistema_jaraba_theme";
$controller = "$root/web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php";

$passes = 0;
$failures = [];
$warnings = [];
$total = 10;

echo "=== TRUST-STRIP-INTEGRITY-001: Trust Strip Partner Validation ===\n\n";

// ─── CHECK 1: Partner catalog exists with 9 entries ───
$controllerContent = file_get_contents($controller);
$catalogCount = substr_count($controllerContent, "'type' => 'image', 'src' => 'logo-");
if ($catalogCount >= 9) {
  echo "✅ CHECK 1: Partner catalog has $catalogCount entries (≥9)\n";
  $passes++;
} else {
  $failures[] = "Partner catalog has only $catalogCount entries (expected ≥9)";
  echo "❌ CHECK 1: Partner catalog incomplete ($catalogCount, expected ≥9)\n";
}

// ─── CHECK 2: All 9 logo assets exist ───
$expectedLogos = [
  'logo-stripe.png', 'logo-google.png', 'logo-anthropic.png',
  'logo-drupal.png', 'logo-qdrant.png', 'logo-linkedin.png',
  'logo-whatsapp.png', 'logo-bizum.png', 'logo-firma-digital.png',
];
$missingLogos = [];
foreach ($expectedLogos as $logo) {
  if (!file_exists("$theme/images/$logo")) {
    $missingLogos[] = $logo;
  }
}
if (empty($missingLogos)) {
  echo "✅ CHECK 2: All 9 logo PNG assets exist\n";
  $passes++;
} else {
  $failures[] = "Missing logo assets: " . implode(', ', $missingLogos);
  echo "❌ CHECK 2: Missing logos: " . implode(', ', $missingLogos) . "\n";
}

// ─── CHECK 3: Institutional logos exist ───
$institutionalLogos = ['kit-digital-logo.svg', 'next-generation-eu.svg', 'gobierno-espana.svg'];
$missingInst = [];
foreach ($institutionalLogos as $logo) {
  if (!file_exists("$theme/images/$logo")) {
    $missingInst[] = $logo;
  }
}
if (empty($missingInst)) {
  echo "✅ CHECK 3: All 3 institutional logo assets exist\n";
  $passes++;
} else {
  $failures[] = "Missing institutional logos: " . implode(', ', $missingInst);
  echo "❌ CHECK 3: Missing institutional logos: " . implode(', ', $missingInst) . "\n";
}

// ─── CHECK 4: _trust-strip.html.twig exists ───
$trustStrip = "$theme/templates/partials/_trust-strip.html.twig";
if (file_exists($trustStrip)) {
  echo "✅ CHECK 4: _trust-strip.html.twig exists\n";
  $passes++;
} else {
  $failures[] = "_trust-strip.html.twig not found";
  echo "❌ CHECK 4: _trust-strip.html.twig NOT FOUND\n";
}

// ─── CHECK 5: Trust strip included in vertical-landing-content.html.twig ───
$orchestrator = "$theme/templates/partials/vertical-landing-content.html.twig";
if (file_exists($orchestrator) && str_contains(file_get_contents($orchestrator), '_trust-strip.html.twig')) {
  echo "✅ CHECK 5: Trust strip included in vertical landing orchestrator\n";
  $passes++;
} else {
  $failures[] = "_trust-strip.html.twig not included in vertical-landing-content.html.twig";
  echo "❌ CHECK 5: Trust strip NOT included in vertical landing orchestrator\n";
}

// ─── CHECK 6: Trust strip included in page--front.html.twig ───
$homepage = "$theme/templates/page--front.html.twig";
if (file_exists($homepage) && str_contains(file_get_contents($homepage), '_trust-strip.html.twig')) {
  echo "✅ CHECK 6: Trust strip included in homepage\n";
  $passes++;
} else {
  $failures[] = "_trust-strip.html.twig not included in page--front.html.twig";
  echo "❌ CHECK 6: Trust strip NOT included in homepage\n";
}

// ─── CHECK 7: MARKETING-TRUTH-001 — No MRW/SEUR in controller ───
if (!preg_match('/\bMRW\b|\bSEUR\b/i', $controllerContent)) {
  echo "✅ CHECK 7: MARKETING-TRUTH-001 — No MRW/SEUR in VerticalLandingController\n";
  $passes++;
} else {
  $failures[] = "MARKETING-TRUTH-001 violation: MRW/SEUR found in VerticalLandingController";
  echo "❌ CHECK 7: MARKETING-TRUTH-001 — MRW/SEUR still present in controller\n";
}

// ─── CHECK 8: SCSS compiled (trust-strip styles in landing.css) ───
$landingCss = "$theme/css/routes/landing.css";
if (file_exists($landingCss) && str_contains(file_get_contents($landingCss), 'trust-strip')) {
  echo "✅ CHECK 8: Trust strip SCSS compiled into landing.css\n";
  $passes++;
} else {
  $failures[] = "trust-strip styles not found in compiled landing.css";
  echo "❌ CHECK 8: Trust strip SCSS NOT compiled into landing.css\n";
}

// ─── CHECK 9: JS behavior file exists ───
$jsFile = "$theme/js/trust-strip-marquee.js";
if (file_exists($jsFile) && str_contains(file_get_contents($jsFile), 'trustStripMarquee')) {
  echo "✅ CHECK 9: trust-strip-marquee.js exists with behavior\n";
  $passes++;
} else {
  $failures[] = "trust-strip-marquee.js missing or missing behavior";
  echo "❌ CHECK 9: trust-strip-marquee.js missing or incomplete\n";
}

// ─── CHECK 10: Per-vertical partner selection method exists ───
if (str_contains($controllerContent, 'getPartnersForVertical') && str_contains($controllerContent, 'getInstitutionalPartners')) {
  echo "✅ CHECK 10: Per-vertical partner helper methods present\n";
  $passes++;
} else {
  $failures[] = "getPartnersForVertical/getInstitutionalPartners methods missing";
  echo "❌ CHECK 10: Per-vertical helper methods NOT found\n";
}

// ─── SUMMARY ───
echo "\n=== RESULT: $passes/$total checks passed ===\n";

if (!empty($warnings)) {
  echo "\n⚠️  Warnings:\n";
  foreach ($warnings as $w) {
    echo "  - $w\n";
  }
}

if (!empty($failures)) {
  echo "\n❌ Failures:\n";
  foreach ($failures as $f) {
    echo "  - $f\n";
  }
  exit(1);
}

echo "\n✅ TRUST-STRIP-INTEGRITY-001: All checks passed.\n";
exit(0);
