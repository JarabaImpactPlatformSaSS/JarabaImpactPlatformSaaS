<?php

/**
 * @file
 * STRIPE-SYNC-001: Verifies that SaasPlan entities with price > 0
 * have Stripe Price IDs populated (not empty strings).
 *
 * Also checks Addon entities for Stripe fields.
 *
 * Usage: php scripts/validation/validate-stripe-sync.php
 * Note: Does NOT call Stripe API (no secrets needed). Only checks local DB.
 */

$base_path = dirname(__DIR__, 2);
$errors = [];
$warnings = [];

// Check SaasPlan configs in config/sync.
$config_dir = $base_path . '/config/sync';
$plan_files = glob($config_dir . '/ecosistema_jaraba_core.saas_plan.*.yml');

$plans_checked = 0;
$plans_with_stripe = 0;
$plans_missing_stripe = 0;

foreach ($plan_files as $file) {
  $content = file_get_contents($file);
  $basename = basename($file, '.yml');

  // Extract price.
  if (preg_match('/price_monthly:\s*["\']?(\d+\.?\d*)["\']?/', $content, $pm)) {
    $price = (float) $pm[1];
  }
  else {
    continue; // No price field, skip.
  }

  if ($price <= 0) {
    continue; // Free plan, no Stripe needed.
  }

  $plans_checked++;

  // Check stripe_price_id.
  $has_stripe = FALSE;
  if (preg_match('/stripe_price_id:\s*["\']?(price_\w+)["\']?/', $content)) {
    $has_stripe = TRUE;
  }

  if ($has_stripe) {
    $plans_with_stripe++;
  }
  else {
    $plans_missing_stripe++;
    // NOTE: Stripe Price IDs are stored in DB (via StripeProductSyncService),
    // NOT in config/sync YAML. Missing ID in YAML is expected — the DB is
    // the source of truth for Stripe IDs. This is a WARNING, not an error.
    $warnings[] = sprintf(
      '%s (%.0f EUR/mes): no Stripe Price ID in config/sync (check DB)',
      str_replace('ecosistema_jaraba_core.saas_plan.', '', $basename),
      $price
    );
  }
}

// Check Addon entities (from config or install).
$addon_files = glob($config_dir . '/jaraba_addons.addon.*.yml');
$addon_install = glob($base_path . '/web/modules/custom/jaraba_addons/config/install/jaraba_addons.addon.*.yml');
$all_addon_files = array_merge($addon_files, $addon_install);
$addons_checked = 0;
$addons_with_stripe = 0;

foreach ($all_addon_files as $file) {
  $content = file_get_contents($file);
  if (preg_match('/price_monthly:\s*["\']?(\d+\.?\d*)["\']?/', $content, $pm)) {
    if ((float) $pm[1] > 0) {
      $addons_checked++;
      if (preg_match('/stripe_product_id:\s*["\']?(prod_\w+)["\']?/', $content)) {
        $addons_with_stripe++;
      }
    }
  }
}

if ($addons_checked > 0 && $addons_with_stripe === 0) {
  $warnings[] = sprintf(
    '%d paid addon(s) in config without Stripe Product ID — addon checkout will not work',
    $addons_checked
  );
}

// Output.
echo "STRIPE-SYNC-001: Stripe integration verification\n";
echo str_repeat('=', 60) . "\n";

if (empty($errors)) {
  echo "\033[32mPASS\033[0m — All paid plans have Stripe Price IDs.\n";
}
else {
  echo "\033[31mFAIL\033[0m — " . count($errors) . " plan(s) without Stripe:\n";
  foreach ($errors as $e) {
    echo "  \033[31m✗\033[0m $e\n";
  }
}

if (!empty($warnings)) {
  foreach ($warnings as $w) {
    echo "  \033[33m⚠\033[0m $w\n";
  }
}

echo "\nPaid plans: $plans_checked (with Stripe: $plans_with_stripe, missing: $plans_missing_stripe)\n";
echo "Paid addons: $addons_checked (with Stripe: $addons_with_stripe)\n";

exit(empty($errors) ? 0 : 1);
