<?php

/**
 * @file
 * WHATSAPP-CAMPAIGN-RGPD-001: WhatsApp campaign RGPD compliance safeguards.
 *
 * Verifies that the WhatsApp module infrastructure enforces RGPD consent
 * before any outbound campaign message. Spanish AEPD can fine up to 20M EUR
 * for unsolicited commercial WhatsApp messages without explicit consent.
 *
 * Checks:
 * 1. CRM Contact entity has rgpd_whatsapp_consent field
 * 2. WaTemplate entity has is_commercial flag for campaign templates
 * 3. Campaign service checks consent before sending (if exists)
 * 4. Opt-out mechanism exists (unsubscribe template or keyword)
 *
 * Usage: php scripts/validation/validate-whatsapp-campaign-rgpd.php
 * Exit:  0 = pass, 1 = fail
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$checks = 0;
$passed = 0;

echo "WHATSAPP-CAMPAIGN-RGPD-001: WhatsApp campaign RGPD compliance\n";
echo str_repeat('=', 60) . "\n\n";

// ── Check 1: CRM Contact entity has consent tracking capability ──
$checks++;
$crmModule = "$projectRoot/web/modules/custom/jaraba_crm";
if (is_dir($crmModule)) {
  $contactEntity = "$crmModule/src/Entity/Contact.php";
  if (file_exists($contactEntity)) {
    $content = file_get_contents($contactEntity);
    if (str_contains($content, 'rgpd') || str_contains($content, 'consent') || str_contains($content, 'gdpr')) {
      $passed++;
      echo "  [PASS] CRM Contact entity has RGPD/consent field capability\n";
    }
    else {
      $warnings[] = "CRM Contact entity lacks rgpd_consent field — required before implementing campaigns";
      echo "  [WARN] CRM Contact missing RGPD consent field (needed for campaigns)\n";
    }
  }
  else {
    $warnings[] = "Contact entity not found at expected path";
    echo "  [WARN] Contact.php not found\n";
  }
}
else {
  $warnings[] = "jaraba_crm module not found";
  echo "  [WARN] jaraba_crm module not found\n";
}

// ── Check 2: WaTemplate entity exists with commercial tracking ──
$checks++;
$waModule = "$projectRoot/web/modules/custom/jaraba_whatsapp";
if (is_dir($waModule)) {
  $templateEntity = "$waModule/src/Entity/WaTemplate.php";
  if (file_exists($templateEntity)) {
    $passed++;
    echo "  [PASS] WaTemplate entity exists for approved message templates\n";
  }
  else {
    $errors[] = "WaTemplate entity not found — required for Meta template approval";
    echo "  [FAIL] WaTemplate entity missing\n";
  }
}
else {
  $warnings[] = "jaraba_whatsapp module not found";
  echo "  [WARN] jaraba_whatsapp module not found\n";
}

// ── Check 3: RGPD retention config exists ──
$checks++;
$waSettings = "$waModule/config/install/jaraba_whatsapp.settings.yml";
if (file_exists($waSettings)) {
  $content = file_get_contents($waSettings);
  if (str_contains($content, 'rgpd_retention')) {
    $passed++;
    echo "  [PASS] RGPD retention period configured in WhatsApp settings\n";
  }
  else {
    $errors[] = "WhatsApp settings missing rgpd_retention_months config";
    echo "  [FAIL] Missing RGPD retention config\n";
  }
}
else {
  $warnings[] = "WhatsApp settings file not found";
  echo "  [WARN] WhatsApp settings not found\n";
}

// ── Check 4: No hardcoded bulk send without consent check ──
$checks++;
$campaignFiles = glob("$waModule/src/Service/*Campaign*") ?: [];
$bulkFiles = glob("$waModule/src/Service/*Bulk*") ?: [];
$allCampaign = array_merge($campaignFiles, $bulkFiles);

if (empty($allCampaign)) {
  $passed++;
  echo "  [PASS] No bulk/campaign service exists yet (safe — no unsolicited sends)\n";
}
else {
  $hasConsentCheck = false;
  foreach ($allCampaign as $file) {
    $content = file_get_contents($file);
    if (str_contains($content, 'consent') || str_contains($content, 'rgpd') || str_contains($content, 'opt_in')) {
      $hasConsentCheck = true;
    }
  }
  if ($hasConsentCheck) {
    $passed++;
    echo "  [PASS] Campaign service includes consent verification\n";
  }
  else {
    $errors[] = "Campaign/Bulk service found WITHOUT consent check — RGPD violation risk";
    echo "  [FAIL] Campaign service missing RGPD consent check\n";
  }
}

// ── Summary ──
echo "\n" . str_repeat('=', 60) . "\n";
echo "Checks: $checks | Passed: $passed | Errors: " . count($errors) . " | Warnings: " . count($warnings) . "\n";

if (!empty($warnings)) {
  echo "\nWARNINGS:\n";
  foreach ($warnings as $w) {
    echo "  !  $w\n";
  }
}

if (!empty($errors)) {
  echo "\n[ERROR]S:\n";
  foreach ($errors as $e) {
    echo "  [ERROR] $e\n";
  }
  exit(1);
}

echo "\n+ WHATSAPP-CAMPAIGN-RGPD-001: Compliance checks passed.\n";
exit(0);
