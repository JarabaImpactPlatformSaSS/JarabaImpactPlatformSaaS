#!/usr/bin/env php
<?php
/**
 * Extract all ES text nodes from meta-site pages for translation.
 * Outputs a PHP array that can be used as a dictionary.
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');

$tenants = [
  5 => ['name' => 'pepejaraba', 'langs' => ['en']],
  6 => ['name' => 'jarabaimpact', 'langs' => ['en']],
  7 => ['name' => 'PED', 'langs' => ['en']],
];

foreach ($tenants as $tid => $config) {
  echo "\n// " . str_repeat('=', 70) . "\n";
  echo "// TENANT $tid — {$config['name']}\n";
  echo "// " . str_repeat('=', 70) . "\n";

  $pages = $storage->loadByProperties(['tenant_id' => $tid]);
  foreach ($pages as $page) {
    $id = $page->id();
    $title = $page->get('title')->value ?? '';
    $es = $page->getUntranslated();
    $html = $es->get('rendered_html')->value ?? '';

    preg_match_all('/>((?:(?!<).){3,})</us', $html, $m);
    $texts = array_values(array_filter(array_map('trim', $m[1])));

    if (empty($texts)) continue;

    echo "\n// --- Page #$id: $title ---\n";
    foreach ($texts as $t) {
      $escaped = str_replace("'", "\\'", $t);
      echo "  '$escaped' => '',\n";
    }
  }
}
