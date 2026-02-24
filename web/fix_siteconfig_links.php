<?php

/**
 * Fix SiteConfig page links for Pepe Jaraba.
 * Entity reference fields need target_id format.
 * Run: lando drush scr web/fix_siteconfig_links.php
 */

$etm = \Drupal::entityTypeManager();
$config_storage = $etm->getStorage('site_config');
$config = $config_storage->load(1);

if (!$config) {
  echo "ERROR: SiteConfig ID 1 not found\n";
  return;
}

// Page IDs (already known from creation)
$links = [
  'homepage_id' => 57,
  'blog_index_id' => 61,
  'privacy_policy_id' => 64,
  'terms_conditions_id' => 63,
  'cookies_policy_id' => 65,
];

echo "=== Fixing SiteConfig Entity Reference Links ===\n\n";

// Also fix tenant_id while we're at it
$config->set('tenant_id', ['target_id' => 5]);
echo "tenant_id → 5 (entity_reference)\n";

foreach ($links as $field => $page_id) {
  $config->set($field, ['target_id' => $page_id]);
  echo "{$field} → {$page_id} (entity_reference)\n";
}

$config->save();
echo "\nSaved!\n";

// Verify
$config2 = $config_storage->load(1);
echo "\nVerification:\n";
echo "  tenant_id: " . ($config2->get('tenant_id')->target_id ?? 'NULL') . "\n";
foreach (array_keys($links) as $field) {
  $val = $config2->get($field)->target_id ?? $config2->get($field)->value ?? 'NULL';
  echo "  {$field}: {$val}\n";
}
