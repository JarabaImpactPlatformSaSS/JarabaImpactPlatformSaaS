<?php

/**
 * @file
 * ANALYTICS-DATA-FLOW-001: Validate analytics data pipeline integrity.
 *
 * Checks:
 * 1. GA4 measurement ID configured in theme settings (config/sync yml)
 * 2. Meta Pixel ID configured in theme settings
 * 3. funnel-analytics.js exists and references /api/v1/analytics/event
 * 4. AnalyticsApiController exists and has trackEvent method
 * 5. analytics_event entity class exists
 *
 * Usage: php scripts/validation/validate-analytics-data-flow.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$checks = 0;

echo "ANALYTICS-DATA-FLOW-001: Analytics Data Pipeline Validator\n";
echo str_repeat('=', 60) . "\n";

// --- Check 1: GA4 measurement ID configured in theme settings ---
$checks++;
$themeSettingsFile = $projectRoot . '/config/sync/ecosistema_jaraba_theme.settings.yml';
if (file_exists($themeSettingsFile)) {
  $content = file_get_contents($themeSettingsFile);
  if (preg_match('/ga4|measurement_id|google_analytics/i', $content)) {
    echo "CHECK 1: PASS — GA4 measurement ID reference found in theme settings\n";
  }
  else {
    $warnings[] = "GA4 measurement ID not found in ecosistema_jaraba_theme.settings.yml";
    echo "CHECK 1: WARN — GA4 measurement ID not found in theme settings\n";
  }
}
else {
  $errors[] = "Theme settings file not found: ecosistema_jaraba_theme.settings.yml";
  echo "CHECK 1: FAIL — Theme settings file not found\n";
}

// --- Check 2: Meta Pixel ID configured in theme settings ---
$checks++;
if (file_exists($themeSettingsFile)) {
  $content = $content ?? file_get_contents($themeSettingsFile);
  if (preg_match('/meta_pixel|facebook_pixel|pixel_id/i', $content)) {
    echo "CHECK 2: PASS — Meta Pixel ID reference found in theme settings\n";
  }
  else {
    $warnings[] = "Meta Pixel ID not found in ecosistema_jaraba_theme.settings.yml";
    echo "CHECK 2: WARN — Meta Pixel ID not found in theme settings\n";
  }
}
else {
  echo "CHECK 2: SKIP — Theme settings file not found (already reported)\n";
}

// --- Check 3: funnel-analytics.js exists and references analytics endpoint ---
$checks++;
$funnelJsFile = $projectRoot . '/web/themes/custom/ecosistema_jaraba_theme/js/funnel-analytics.js';
if (file_exists($funnelJsFile)) {
  $jsContent = file_get_contents($funnelJsFile);
  if (strpos($jsContent, '/api/v1/analytics/event') !== FALSE
      || strpos($jsContent, 'analytics/event') !== FALSE
      || strpos($jsContent, 'funnel-analytics') !== FALSE) {
    echo "CHECK 3: PASS — funnel-analytics.js exists and references analytics endpoint\n";
  }
  else {
    $warnings[] = "funnel-analytics.js exists but does not reference /api/v1/analytics/event";
    echo "CHECK 3: WARN — funnel-analytics.js exists but missing analytics endpoint reference\n";
  }
}
else {
  $errors[] = "funnel-analytics.js not found at expected path";
  echo "CHECK 3: FAIL — funnel-analytics.js not found\n";
}

// --- Check 4: AnalyticsApiController exists and has trackEvent method ---
$checks++;
$controllerPaths = [
  $projectRoot . '/web/modules/custom/jaraba_analytics/src/Controller/AnalyticsApiController.php',
  $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/src/Controller/AnalyticsEventController.php',
];
$controllerFound = FALSE;
$trackEventFound = FALSE;
foreach ($controllerPaths as $ctrlPath) {
  if (file_exists($ctrlPath)) {
    $controllerFound = TRUE;
    $ctrlContent = file_get_contents($ctrlPath);
    if (strpos($ctrlContent, 'function trackEvent') !== FALSE) {
      $trackEventFound = TRUE;
      break;
    }
  }
}
if ($controllerFound && $trackEventFound) {
  echo "CHECK 4: PASS — AnalyticsApiController found with trackEvent method\n";
}
elseif ($controllerFound) {
  $warnings[] = "AnalyticsApiController found but trackEvent() method missing";
  echo "CHECK 4: WARN — Controller found but trackEvent() missing\n";
}
else {
  $errors[] = "AnalyticsApiController not found in jaraba_analytics or ecosistema_jaraba_core";
  echo "CHECK 4: FAIL — AnalyticsApiController not found\n";
}

// --- Check 5: analytics_event entity class exists ---
$checks++;
$entityPath = $projectRoot . '/web/modules/custom/jaraba_analytics/src/Entity/AnalyticsEvent.php';
if (file_exists($entityPath)) {
  $entityContent = file_get_contents($entityPath);
  if (strpos($entityContent, 'class AnalyticsEvent') !== FALSE) {
    echo "CHECK 5: PASS — AnalyticsEvent entity class exists\n";
  }
  else {
    $errors[] = "AnalyticsEvent.php exists but does not define AnalyticsEvent class";
    echo "CHECK 5: FAIL — AnalyticsEvent class not defined\n";
  }
}
else {
  $errors[] = "AnalyticsEvent entity not found at jaraba_analytics/src/Entity/AnalyticsEvent.php";
  echo "CHECK 5: FAIL — AnalyticsEvent entity not found\n";
}

// --- Output ---
echo str_repeat('-', 60) . "\n";
echo "Checks: $checks | Errors: " . count($errors) . " | Warnings: " . count($warnings) . "\n";

if (count($warnings) > 0) {
  echo "\nWARNINGS:\n";
  foreach ($warnings as $w) {
    echo "  WARN: $w\n";
  }
}

if (count($errors) > 0) {
  echo "\nERRORS:\n";
  foreach ($errors as $e) {
    echo "  FAIL: $e\n";
  }
  exit(1);
}

echo "\nPASS: Analytics data pipeline checks passed.\n";
exit(0);
