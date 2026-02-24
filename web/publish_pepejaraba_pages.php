<?php

/**
 * Publish all Pepe Jaraba (Tenant 5) pages.
 * Run: lando drush scr web/publish_pepejaraba_pages.php
 */

$etm = \Drupal::entityTypeManager();
$page_storage = $etm->getStorage('page_content');

// Load all pages for tenant 5
$pages = $page_storage->loadByProperties(['tenant_id' => 5]);

if (empty($pages)) {
  echo "ERROR: No pages found for tenant 5\n";
  return;
}

echo "=== Publishing Pepe Jaraba Pages (Tenant 5) ===\n\n";

$count = 0;
foreach ($pages as $page) {
  $title = $page->get('title')->value;
  $id = $page->id();
  $current_status = $page->get('status')->value;

  // Set status to published (1)
  $page->set('status', 1);

  // Set moderation_state to 'published' if the field exists
  if ($page->hasField('moderation_state')) {
    $page->set('moderation_state', 'published');
  }

  $page->save();
  $count++;

  $new_status = $page->get('status')->value;
  echo "Published: {$title} (ID: {$id}) - status: {$current_status} → {$new_status}";
  if ($page->hasField('moderation_state')) {
    echo " | moderation: " . $page->get('moderation_state')->value;
  }
  echo "\n";
}

echo "\n=== {$count} pages published successfully ===\n";

// Also verify SiteConfig
$config_storage = $etm->getStorage('site_config');
$config = $config_storage->load(1);
if ($config) {
  echo "\nSiteConfig verification:\n";
  echo "  Site name: " . $config->get('site_name')->value . "\n";
  echo "  Homepage ID: " . $config->get('homepage_id')->value . "\n";
  echo "  Blog ID: " . ($config->get('blog_index_id')->value ?? 'not set') . "\n";
  echo "  Privacy ID: " . ($config->get('privacy_policy_id')->value ?? 'not set') . "\n";
  echo "  Terms ID: " . ($config->get('terms_conditions_id')->value ?? 'not set') . "\n";
  echo "  Cookies ID: " . ($config->get('cookies_policy_id')->value ?? 'not set') . "\n";
}
