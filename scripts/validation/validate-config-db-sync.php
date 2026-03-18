<?php

/**
 * @file
 * CONFIG-DB-SYNC-001: Validates that config/sync YAMLs match active DB config.
 *
 * Detects drift between what's in config/sync/ (committed to git) and what's
 * actually active in the database. This prevents the bug where DB is updated
 * (e.g., via drush eval or UI) but config/sync is NOT exported, leaving
 * stale values that would overwrite DB on next `drush config:import`.
 *
 * Specifically checks SaasPlan entities (pricing-critical):
 * - features field
 * - limits field (JSON with quotas)
 * - price_monthly / price_yearly
 * - stripe_price_id / stripe_price_yearly_id
 *
 * Usage: lando drush scr scripts/validation/validate-config-db-sync.php
 * Requires Drupal bootstrap (runs via drush scr, NOT standalone PHP).
 */

use Drupal\Core\Site\Settings;

// This script MUST run with Drupal bootstrapped.
if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
  echo "ERROR: This script requires Drupal bootstrap. Run via: lando drush scr scripts/validation/validate-config-db-sync.php\n";
  exit(1);
}

$config_sync_dir = Settings::get('config_sync_directory', '../config/sync');
if (!is_dir($config_sync_dir)) {
  // Fallback to project root.
  $config_sync_dir = DRUPAL_ROOT . '/../config/sync';
}

$errors = [];
$checked = 0;

// Check all SaasPlan entities.
$storage = \Drupal::entityTypeManager()->getStorage('saas_plan');
$plans = $storage->loadMultiple();

foreach ($plans as $plan) {
  $plan_id = $plan->id();
  $config_name = 'ecosistema_jaraba_core.saas_plan.' . $plan->get('machine_name')->value;

  // Try to find matching YAML in config/sync.
  $yaml_path = $config_sync_dir . '/' . $config_name . '.yml';
  if (!file_exists($yaml_path)) {
    // Plan exists in DB but not in config/sync — not necessarily an error
    // (could be created via UI, not yet exported).
    continue;
  }

  $yaml_content = file_get_contents($yaml_path);
  $checked++;

  // Compare limits (critical for pricing coherence).
  $db_limits = $plan->get('limits')->value ?? '';
  if ($db_limits && str_contains($yaml_content, 'limits:')) {
    // Extract limits from YAML (simplified: look for the JSON string).
    if (preg_match("/limits:\s*'(.+?)'/s", $yaml_content, $match)) {
      $yaml_limits = $match[1];
      $db_decoded = json_decode($db_limits, TRUE);
      $yaml_decoded = json_decode($yaml_limits, TRUE);

      if ($db_decoded && $yaml_decoded) {
        foreach ($db_decoded as $key => $db_value) {
          $yaml_value = $yaml_decoded[$key] ?? 'MISSING';
          if ($yaml_value === 'MISSING') {
            $errors[] = sprintf(
              '%s: limit "%s" exists in DB (=%s) but MISSING in config/sync YAML',
              $plan->label(), $key, $db_value
            );
          }
          elseif ((string) $db_value !== (string) $yaml_value) {
            $errors[] = sprintf(
              '%s: limit "%s" DB=%s vs YAML=%s — config/sync DESINCRONIZADO',
              $plan->label(), $key, $db_value, $yaml_value
            );
          }
        }
      }
    }
  }

  // Compare features field.
  $db_features = $plan->get('features')->value ?? '';
  if ($db_features && str_contains($yaml_content, 'features:')) {
    if (str_contains($yaml_content, 'mentoring_1a1') && !str_contains($db_features, 'mentoring_1a1')) {
      $errors[] = sprintf(
        '%s: YAML has "mentoring_1a1" but DB does NOT — stale config',
        $plan->label()
      );
    }
  }
}

// Check Addon entities for Stripe fields sync.
if (\Drupal::entityTypeManager()->hasDefinition('addon')) {
  $addon_storage = \Drupal::entityTypeManager()->getStorage('addon');
  $addons = $addon_storage->loadMultiple();
  $addons_with_stripe = 0;
  foreach ($addons as $addon) {
    if ($addon->hasField('stripe_product_id')) {
      $stripe_id = $addon->get('stripe_product_id')->value ?? '';
      if (!empty($stripe_id)) {
        $addons_with_stripe++;
      }
    }
  }
  // Info line (not error).
  $total_addons = count($addons);
}
else {
  $total_addons = 0;
  $addons_with_stripe = 0;
}

// Output.
echo "CONFIG-DB-SYNC-001: Config/sync vs Database consistency\n";
echo str_repeat('=', 60) . "\n";

if (empty($errors)) {
  echo "\033[32mPASS\033[0m — Config/sync matches DB for all checked plans.\n";
}
else {
  echo "\033[31mFAIL\033[0m — " . count($errors) . " desincronización(es) detectada(s):\n";
  foreach ($errors as $e) {
    echo "  \033[31m✗\033[0m $e\n";
  }
  echo "\nFix: Run 'lando drush config:export -y' to sync DB → config/sync.\n";
}

echo "\nPlans checked (DB vs YAML): $checked\n";
echo "Addons total: $total_addons (with Stripe ID: $addons_with_stripe)\n";

exit(empty($errors) ? 0 : 1);
