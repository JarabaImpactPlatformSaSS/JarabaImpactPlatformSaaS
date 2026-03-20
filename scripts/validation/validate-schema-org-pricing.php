<?php

/**
 * @file
 * SCHEMA-PRICING-001: Validates Schema.org pricing is dynamic, not hardcoded.
 *
 * Checks that pricing templates use dynamic data from tiers/overview_tiers
 * variables instead of hardcoded EUR amounts in JSON-LD blocks.
 *
 * Usage: php scripts/validation/validate-schema-org-pricing.php
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$templateDir = $projectRoot . '/web/themes/custom/ecosistema_jaraba_theme/templates';
$errors = [];

$pricingTemplates = [
  'pricing-page.html.twig',
  'pricing-hub-page.html.twig',
];

foreach ($pricingTemplates as $tpl) {
  $file = "$templateDir/$tpl";
  if (!file_exists($file)) {
    echo "SKIP: $tpl not found\n";
    continue;
  }

  $content = file_get_contents($file);

  // Extract JSON-LD blocks.
  preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $content, $blocks);

  foreach ($blocks[1] as $i => $block) {
    // Check for hardcoded EUR prices in JSON-LD.
    if (preg_match('/"price":\s*"(\d+)"/', $block, $m)) {
      $price = (int) $m[1];
      // 0 is OK (free tier can be hardcoded). Check for actual hardcoded prices.
      if ($price > 0 && !str_contains($block, 'tier.price_monthly') && !str_contains($block, '{{')) {
        $errors[] = "$tpl block $i: Hardcoded price \"$price\" in Schema.org — should use {{ tier.price_monthly }}";
      }
    }

    if (preg_match('/"lowPrice":\s*"(\d+)"/', $block, $m)) {
      $price = (int) $m[1];
      if ($price > 0 && !str_contains($block, '{{')) {
        $errors[] = "$tpl block $i: Hardcoded lowPrice \"$price\" in Schema.org — should be dynamic";
      }
    }

    if (preg_match('/"highPrice":\s*"(\d+)"/', $block, $m)) {
      $price = (int) $m[1];
      if ($price > 0 && !str_contains($block, '{{')) {
        $errors[] = "$tpl block $i: Hardcoded highPrice \"$price\" in Schema.org — should be dynamic";
      }
    }
  }

  // Check that JSON-LD uses Twig variables (dynamic).
  $hasJsonLd = !empty($blocks[1]);
  $hasDynamicPricing = str_contains($content, 'tier.price_monthly') ||
                       str_contains($content, 'overview_tiers');

  if ($hasJsonLd && !$hasDynamicPricing) {
    $errors[] = "$tpl: Has JSON-LD but no dynamic pricing variables (tier.price_monthly or overview_tiers)";
  }

  echo "✓ $tpl: " . count($blocks[1]) . " JSON-LD blocks, dynamic=" . ($hasDynamicPricing ? 'YES' : 'NO') . "\n";
}

// Results.
echo "\n" . str_repeat('=', 60) . "\n";
if (empty($errors)) {
  echo "✅ SCHEMA-PRICING-001: ALL CHECKS PASSED\n";
  exit(0);
}

echo "❌ ERRORS (" . count($errors) . "):\n";
foreach ($errors as $e) {
  echo "  $e\n";
}
exit(1);
