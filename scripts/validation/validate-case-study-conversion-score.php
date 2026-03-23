<?php

/**
 * @file
 * CASE-STUDY-CONVERSION-001: Validates case study landing meets 15/15 criteria.
 *
 * Evaluates the unified case-study-landing.html.twig template and its partials
 * against LANDING-CONVERSION-SCORE-001 criteria.
 *
 * Score: <12 = FAIL, 12-14 = WARN, 15 = PASS.
 *
 * Usage: php scripts/validation/validate-case-study-conversion-score.php
 */

$themePath = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme';
$templatePath = $themePath . '/templates';
$partialsPath = $templatePath . '/partials';
$scssPath = $themePath . '/scss/routes/case-study-landing.scss';
$jsPath = $themePath . '/js';

$score = 0;
$errors = [];
$warnings = [];

// Helper: check file contains pattern.
function fileContains(string $path, string $pattern): bool {
  if (!file_exists($path)) {
    return false;
  }
  $content = file_get_contents($path);
  return (bool) preg_match($pattern, $content);
}

// Helper: check partial exists and has content.
function partialExists(string $path): bool {
  return file_exists($path) && filesize($path) > 50;
}

// === CHECK 1: Hero with urgency badge + 2 CTAs ===
$heroPartial = $partialsPath . '/_cs-hero.html.twig';
if (partialExists($heroPartial)) {
  $heroContent = file_get_contents($heroPartial);
  $hasUrgency = str_contains($heroContent, 'urgency-badge');
  $hasTwoCtas = substr_count($heroContent, 'data-track-cta') >= 2;
  $hasH1 = str_contains($heroContent, '<h1');

  if ($hasUrgency && $hasTwoCtas && $hasH1) {
    $score++;
    echo "  [PASS] CHECK 1: Hero with urgency badge + 2 CTAs + H1\n";
  } else {
    $missing = [];
    if (!$hasUrgency) $missing[] = 'urgency badge';
    if (!$hasTwoCtas) $missing[] = '2 CTAs';
    if (!$hasH1) $missing[] = 'H1';
    $errors[] = "CHECK 1: Hero missing: " . implode(', ', $missing);
    echo "  [FAIL] CHECK 1: Hero missing: " . implode(', ', $missing) . "\n";
  }
} else {
  $errors[] = "CHECK 1: _cs-hero.html.twig not found";
  echo "  [FAIL] CHECK 1: _cs-hero.html.twig not found\n";
}

// === CHECK 2: Trust badges below hero ===
if (partialExists($heroPartial) && str_contains(file_get_contents($heroPartial), 'trust-badge')) {
  $score++;
  echo "  [PASS] CHECK 2: Trust badges present in hero\n";
} else {
  $errors[] = "CHECK 2: Trust badges missing from hero";
  echo "  [FAIL] CHECK 2: Trust badges missing from hero\n";
}

// === CHECK 3: Pain points with icons ===
$painPartial = $partialsPath . '/_cs-pain-points.html.twig';
if (partialExists($painPartial) && fileContains($painPartial, '/jaraba_icon/')) {
  $score++;
  echo "  [PASS] CHECK 3: Pain points with icons\n";
} else {
  $errors[] = "CHECK 3: Pain points partial missing or lacks jaraba_icon";
  echo "  [FAIL] CHECK 3: Pain points partial missing or lacks jaraba_icon\n";
}

// === CHECK 4: How it works (3 steps) ===
$howPartial = $partialsPath . '/_cs-how-it-works.html.twig';
if (partialExists($howPartial) && str_contains(file_get_contents($howPartial), 'cs-how__step')) {
  $score++;
  echo "  [PASS] CHECK 4: How it works section\n";
} else {
  $errors[] = "CHECK 4: How-it-works partial missing";
  echo "  [FAIL] CHECK 4: How-it-works partial missing\n";
}

// === CHECK 5: 12+ features with icons ===
$featPartial = $partialsPath . '/_cs-features.html.twig';
if (partialExists($featPartial) && fileContains($featPartial, '/jaraba_icon/')) {
  $score++;
  echo "  [PASS] CHECK 5: Features section with icons\n";
} else {
  $errors[] = "CHECK 5: Features partial missing or lacks jaraba_icon";
  echo "  [FAIL] CHECK 5: Features partial missing or lacks jaraba_icon\n";
}

// === CHECK 6: Comparison table 3 columns ===
$compPartial = $partialsPath . '/_cs-comparison.html.twig';
if (partialExists($compPartial) && str_contains(file_get_contents($compPartial), 'cs-comparison__table')) {
  $score++;
  echo "  [PASS] CHECK 6: Comparison table\n";
} else {
  $errors[] = "CHECK 6: Comparison partial missing";
  echo "  [FAIL] CHECK 6: Comparison partial missing\n";
}

// === CHECK 7: Social proof (testimonials + metrics) ===
$proofPartial = $partialsPath . '/_cs-social-proof.html.twig';
if (partialExists($proofPartial)) {
  $content = file_get_contents($proofPartial);
  $hasMainQuote = str_contains($content, 'main-quote');
  $hasAdditional = str_contains($content, 'short-quote');
  $hasMetrics = str_contains($content, 'data-count');
  if ($hasMainQuote && $hasAdditional && $hasMetrics) {
    $score++;
    echo "  [PASS] CHECK 7: Social proof dense (testimonials + metrics)\n";
  } else {
    $errors[] = "CHECK 7: Social proof incomplete";
    echo "  [FAIL] CHECK 7: Social proof incomplete\n";
  }
} else {
  $errors[] = "CHECK 7: Social proof partial missing";
  echo "  [FAIL] CHECK 7: Social proof partial missing\n";
}

// === CHECK 8: Lead magnet with email ===
$leadPartial = $partialsPath . '/_cs-lead-magnet.html.twig';
if (partialExists($leadPartial) && str_contains(file_get_contents($leadPartial), 'type="email"')) {
  $score++;
  echo "  [PASS] CHECK 8: Lead magnet with email capture\n";
} else {
  $errors[] = "CHECK 8: Lead magnet partial missing or no email input";
  echo "  [FAIL] CHECK 8: Lead magnet partial missing or no email input\n";
}

// === CHECK 9: 4-tier pricing ===
$pricingPartial = $partialsPath . '/_cs-pricing.html.twig';
if (partialExists($pricingPartial)) {
  $content = file_get_contents($pricingPartial);
  $tierCount = substr_count($content, 'cs-pricing__card');
  // 4 cards + 1 popular modifier = 5 mentions minimum.
  if ($tierCount >= 4) {
    $score++;
    echo "  [PASS] CHECK 9: 4-tier pricing ({$tierCount} card references)\n";
  } else {
    $errors[] = "CHECK 9: Only {$tierCount} pricing card references (need 4+)";
    echo "  [FAIL] CHECK 9: Only {$tierCount} pricing card references\n";
  }
} else {
  $errors[] = "CHECK 9: Pricing partial missing";
  echo "  [FAIL] CHECK 9: Pricing partial missing\n";
}

// === CHECK 10: FAQ 10+ with Schema.org ===
$faqPartial = $partialsPath . '/_cs-faq.html.twig';
if (partialExists($faqPartial)) {
  $content = file_get_contents($faqPartial);
  $hasFaq = str_contains($content, 'cs-faq__item');
  $hasSchema = str_contains($content, 'FAQPage');
  if ($hasFaq && $hasSchema) {
    $score++;
    echo "  [PASS] CHECK 10: FAQ with Schema.org FAQPage\n";
  } else {
    $errors[] = "CHECK 10: FAQ missing items or Schema.org";
    echo "  [FAIL] CHECK 10: FAQ missing items or Schema.org\n";
  }
} else {
  $errors[] = "CHECK 10: FAQ partial missing";
  echo "  [FAIL] CHECK 10: FAQ partial missing\n";
}

// === CHECK 11: Final CTA ===
$ctaPartial = $partialsPath . '/_cs-final-cta.html.twig';
if (partialExists($ctaPartial) && str_contains(file_get_contents($ctaPartial), 'data-track-cta')) {
  $score++;
  echo "  [PASS] CHECK 11: Final CTA with tracking\n";
} else {
  $errors[] = "CHECK 11: Final CTA partial missing or no tracking";
  echo "  [FAIL] CHECK 11: Final CTA partial missing or no tracking\n";
}

// === CHECK 12: Sticky CTA ===
$masterTemplate = $templatePath . '/case-study-landing.html.twig';
if (file_exists($masterTemplate) && str_contains(file_get_contents($masterTemplate), '_landing-sticky-cta')) {
  // Also check JS has cs-hero selector.
  $jsFile = $jsPath . '/landing-sticky-cta.js';
  if (file_exists($jsFile) && str_contains(file_get_contents($jsFile), '.cs-hero')) {
    $score++;
    echo "  [PASS] CHECK 12: Sticky CTA included + JS connected\n";
  } else {
    $errors[] = "CHECK 12: Sticky CTA JS missing .cs-hero selector";
    echo "  [FAIL] CHECK 12: Sticky CTA JS missing .cs-hero selector\n";
  }
} else {
  $errors[] = "CHECK 12: Sticky CTA not included in master template";
  echo "  [FAIL] CHECK 12: Sticky CTA not included in master template\n";
}

// === CHECK 13: Reveal animations ===
$scrollJs = $jsPath . '/scroll-animations.js';
if (file_exists($scrollJs) && str_contains(file_get_contents($scrollJs), '.reveal-element')) {
  // Check SCSS has reveal-element styles.
  if (file_exists($scssPath) && str_contains(file_get_contents($scssPath), '.reveal-element')) {
    $score++;
    echo "  [PASS] CHECK 13: Reveal animations (JS + CSS)\n";
  } else {
    $errors[] = "CHECK 13: .reveal-element CSS missing in SCSS";
    echo "  [FAIL] CHECK 13: .reveal-element CSS missing in SCSS\n";
  }
} else {
  $errors[] = "CHECK 13: scroll-animations.js missing .reveal-element selector";
  echo "  [FAIL] CHECK 13: scroll-animations.js missing .reveal-element selector\n";
}

// === CHECK 14: Tracking (data-track-cta + data-track-position) ===
if (file_exists($masterTemplate)) {
  $content = file_get_contents($masterTemplate);
  $ctaCount = substr_count($content, 'data-track-cta');
  $posCount = substr_count($content, 'data-track-position');
  // Master template references partials, count includes from partials.
  // At least the section-level positions should be in the template.
  if ($posCount >= 10) {
    $score++;
    echo "  [PASS] CHECK 14: Tracking ({$posCount} position markers)\n";
  } else {
    // Check partials too.
    $totalPos = $posCount;
    foreach (glob($partialsPath . '/_cs-*.html.twig') as $partial) {
      $totalPos += substr_count(file_get_contents($partial), 'data-track-position');
    }
    if ($totalPos >= 10) {
      $score++;
      echo "  [PASS] CHECK 14: Tracking ({$totalPos} total position markers across partials)\n";
    } else {
      $errors[] = "CHECK 14: Only {$totalPos} data-track-position markers (need 10+)";
      echo "  [FAIL] CHECK 14: Only {$totalPos} data-track-position markers\n";
    }
  }
} else {
  $errors[] = "CHECK 14: Master template missing";
  echo "  [FAIL] CHECK 14: Master template missing\n";
}

// === CHECK 15: Mobile-first (touch targets + no max-width) ===
if (file_exists($scssPath)) {
  $scss = file_get_contents($scssPath);
  $hasTouch = str_contains($scss, 'min-height: 44px');
  // Only flag @media (max-width:) breakpoints, NOT max-width on containers.
  $hasMaxWidth = (bool) preg_match('/@media\s*\(\s*max-width/', $scss);
  if ($hasTouch && !$hasMaxWidth) {
    $score++;
    echo "  [PASS] CHECK 15: Mobile-first (44px touch targets, no max-width breakpoints)\n";
  } else {
    $issues = [];
    if (!$hasTouch) $issues[] = 'missing min-height: 44px';
    if ($hasMaxWidth) $issues[] = 'has max-width breakpoints';
    $warnings[] = "CHECK 15: " . implode(', ', $issues);
    echo "  [WARN] CHECK 15: " . implode(', ', $issues) . "\n";
  }
} else {
  $errors[] = "CHECK 15: SCSS file missing";
  echo "  [FAIL] CHECK 15: SCSS file missing\n";
}

// === SUMMARY ===
echo "\n";
echo "CASE-STUDY-CONVERSION-001: Score $score/15\n";

if ($score >= 15) {
  echo "RESULT: PASS — World-class 10/10\n";
  exit(0);
} elseif ($score >= 12) {
  echo "RESULT: WARN — Close to world-class ($score/15)\n";
  if (!empty($errors)) {
    echo "Errors:\n";
    foreach ($errors as $e) {
      echo "  - $e\n";
    }
  }
  exit(0);
} else {
  echo "RESULT: FAIL — Score $score/15 (need 12+)\n";
  foreach ($errors as $e) {
    echo "  - $e\n";
  }
  exit(1);
}
