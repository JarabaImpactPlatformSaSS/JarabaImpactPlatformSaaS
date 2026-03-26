<?php

/**
 * @file
 * Validator: Analytics & tracking integrity (ANALYTICS-INTEGRITY-001).
 *
 * 10 checks for analytics pipeline integrity:
 * GTM partial, html.html.twig include, theme settings for GA4/Pixel,
 * preprocess_html, settings.yml defaults, CSP headers, Consent Mode v2.
 *
 * Usage: php scripts/validation/validate-analytics-integrity.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$themeRoot = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme';
$partialsDir = $themeRoot . '/templates/partials';
$themeFile = $themeRoot . '/ecosistema_jaraba_theme.theme';
$themeContent = file_exists($themeFile) ? file_get_contents($themeFile) : '';
$settingsYml = __DIR__ . '/../../config/sync/ecosistema_jaraba_theme.settings.yml';
$settingsContent = file_exists($settingsYml) ? file_get_contents($settingsYml) : '';

$coreModuleRoot = __DIR__ . '/../../web/modules/custom/ecosistema_jaraba_core';
$subscriberFiles = glob($coreModuleRoot . '/src/EventSubscriber/*SecurityHeaders*');
$subscriberContent = '';
if (is_array($subscriberFiles)) {
  foreach ($subscriberFiles as $file) {
    $subscriberContent .= file_get_contents($file);
  }
}
// Also check jaraba_page_builder for SecurityHeadersSubscriber.
$pbModuleRoot = __DIR__ . '/../../web/modules/custom/jaraba_page_builder';
$pbSubscriberFiles = glob($pbModuleRoot . '/src/EventSubscriber/*SecurityHeaders*');
if (is_array($pbSubscriberFiles)) {
  foreach ($pbSubscriberFiles as $file) {
    $subscriberContent .= file_get_contents($file);
  }
}

// CHECK 1: _gtm-analytics.html.twig exists in theme partials.
$gtmPartial = $partialsDir . '/_gtm-analytics.html.twig';
if (file_exists($gtmPartial)) {
  $passes[] = "CHECK 1 PASS: _gtm-analytics.html.twig exists in theme partials";
} else {
  $errors[] = "CHECK 1 FAIL: _gtm-analytics.html.twig not found in templates/partials/";
}

// CHECK 2: _gtm-analytics.html.twig is included from html.html.twig.
$htmlTemplate = $themeRoot . '/templates/html.html.twig';
$htmlContent = file_exists($htmlTemplate) ? file_get_contents($htmlTemplate) : '';
if (strpos($htmlContent, '_gtm-analytics') !== false) {
  $passes[] = "CHECK 2 PASS: html.html.twig includes _gtm-analytics partial";
} else {
  $errors[] = "CHECK 2 FAIL: html.html.twig does not include _gtm-analytics partial";
}

// CHECK 3: ecosistema_jaraba_theme.theme has ga4_measurement_id in _get_base_settings.
if (strpos($themeContent, 'ga4_measurement_id') !== false
  && strpos($themeContent, '_get_base_settings') !== false) {
  $passes[] = "CHECK 3 PASS: theme file has ga4_measurement_id in _get_base_settings";
} else {
  $errors[] = "CHECK 3 FAIL: theme file missing ga4_measurement_id in _get_base_settings";
}

// CHECK 4: ecosistema_jaraba_theme.theme has meta_pixel_id in _get_base_settings.
if (strpos($themeContent, 'meta_pixel_id') !== false
  && strpos($themeContent, '_get_base_settings') !== false) {
  $passes[] = "CHECK 4 PASS: theme file has meta_pixel_id in _get_base_settings";
} else {
  $errors[] = "CHECK 4 FAIL: theme file missing meta_pixel_id in _get_base_settings";
}

// CHECK 5: ecosistema_jaraba_theme.theme has preprocess_html that sets theme_settings.
if (strpos($themeContent, 'preprocess_html') !== false
  && strpos($themeContent, 'theme_settings') !== false) {
  $passes[] = "CHECK 5 PASS: preprocess_html sets theme_settings variables";
} else {
  $errors[] = "CHECK 5 FAIL: preprocess_html missing or does not set theme_settings";
}

// CHECK 6: ecosistema_jaraba_theme.settings.yml has ga4_measurement_id (non-empty).
if (preg_match('/ga4_measurement_id:\s*\S+/', $settingsContent)) {
  $passes[] = "CHECK 6 PASS: settings.yml has ga4_measurement_id with non-empty value";
} else {
  // Also check if key exists but is empty vs missing entirely.
  if (strpos($settingsContent, 'ga4_measurement_id') !== false) {
    $errors[] = "CHECK 6 FAIL: settings.yml has ga4_measurement_id but value is empty";
  } else {
    $errors[] = "CHECK 6 FAIL: settings.yml does not have ga4_measurement_id key";
  }
}

// CHECK 7: SecurityHeadersSubscriber has googletagmanager.com in script-src.
if (strpos($subscriberContent, 'googletagmanager.com') !== false) {
  $passes[] = "CHECK 7 PASS: SecurityHeadersSubscriber has googletagmanager.com in CSP";
} else {
  $errors[] = "CHECK 7 FAIL: SecurityHeadersSubscriber missing googletagmanager.com in script-src";
}

// CHECK 8: SecurityHeadersSubscriber has connect.facebook.net in script-src.
if (strpos($subscriberContent, 'connect.facebook.net') !== false) {
  $passes[] = "CHECK 8 PASS: SecurityHeadersSubscriber has connect.facebook.net in CSP";
} else {
  $errors[] = "CHECK 8 FAIL: SecurityHeadersSubscriber missing connect.facebook.net in script-src";
}

// CHECK 9: _gtm-analytics.html.twig has Consent Mode v2 (fbq consent).
$gtmContent = file_exists($gtmPartial) ? file_get_contents($gtmPartial) : '';
if (strpos($gtmContent, 'consent') !== false && strpos($gtmContent, 'fbq') !== false) {
  $passes[] = "CHECK 9 PASS: _gtm-analytics.html.twig has Consent Mode v2 with fbq consent";
} else {
  $missing = [];
  if (strpos($gtmContent, 'consent') === false) {
    $missing[] = 'consent';
  }
  if (strpos($gtmContent, 'fbq') === false) {
    $missing[] = 'fbq';
  }
  $errors[] = "CHECK 9 FAIL: _gtm-analytics.html.twig missing: " . implode(', ', $missing);
}

// CHECK 10: _gtm-analytics.html.twig has meta_pixel_id variable.
if (strpos($gtmContent, 'meta_pixel_id') !== false) {
  $passes[] = "CHECK 10 PASS: _gtm-analytics.html.twig uses meta_pixel_id variable";
} else {
  $errors[] = "CHECK 10 FAIL: _gtm-analytics.html.twig does not use meta_pixel_id variable";
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANALYTICS INTEGRITY (ANALYTICS-INTEGRITY-001) ===\n\n";
foreach ($passes as $msg) {
  echo "  [PASS] $msg\n";
}
foreach ($errors as $msg) {
  echo "  [FAIL] $msg\n";
}
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(count($errors) === 0 ? 0 : 1);
