<?php

/**
 * Final verification of Pepe Jaraba meta-site.
 * Run: lando drush scr web/verify_pepejaraba_final.php
 */

$etm = \Drupal::entityTypeManager();

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║      META-SITIO PEPEJARABA.COM — VERIFICACIÓN FINAL        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// 1. SiteConfig
echo "─── 1. SITE CONFIG ─────────────────────────────────────────\n";
$config = $etm->getStorage('site_config')->load(1);
if ($config) {
  $fields = [
    'site_name', 'site_tagline', 'contact_email', 'contact_phone',
    'contact_address', 'meta_title_suffix',
    'header_type', 'header_sticky', 'header_cta_text', 'header_cta_url',
    'footer_type', 'footer_columns', 'footer_show_social', 'footer_show_newsletter',
  ];
  foreach ($fields as $f) {
    if ($config->hasField($f)) {
      $val = $config->get($f)->value ?? 'NULL';
      echo "  {$f}: {$val}\n";
    }
  }
  // Entity references
  $refs = ['homepage_id', 'blog_index_id', 'privacy_policy_id', 'terms_conditions_id', 'cookies_policy_id'];
  foreach ($refs as $ref) {
    if ($config->hasField($ref)) {
      $val = $config->get($ref)->target_id ?? 'NULL';
      echo "  {$ref}: {$val}\n";
    }
  }
  // Social links
  if ($config->hasField('social_links')) {
    $social = $config->get('social_links')->value;
    $decoded = json_decode($social, TRUE);
    if ($decoded) {
      echo "  social_links: " . implode(', ', array_keys($decoded)) . "\n";
    }
  }
}

// 2. Pages
echo "\n─── 2. PAGES (Tenant 5) ────────────────────────────────────\n";
$pages = $etm->getStorage('page_content')->loadByProperties(['tenant_id' => 5]);
echo "  Total pages: " . count($pages) . "\n\n";

$all_ok = TRUE;
foreach ($pages as $page) {
  $id = $page->id();
  $title = $page->get('title')->value;
  $path = $page->get('path_alias')->value ?? '-';
  $status = $page->get('status')->value;
  $mod = $page->hasField('moderation_state') ? $page->get('moderation_state')->value : '-';
  $meta_title = $page->get('meta_title')->value ?? '-';
  $layout = $page->get('layout_mode')->value ?? '-';

  // Check canvas_data
  $canvas = $page->get('canvas_data')->value ?? '';
  $canvas_data = json_decode($canvas, TRUE);
  $has_html = !empty($canvas_data['html']);
  $has_css = !empty($canvas_data['css']);
  $html_len = strlen($canvas_data['html'] ?? '');
  $css_len = strlen($canvas_data['css'] ?? '');

  // Check rendered_html
  $rendered = $page->get('rendered_html')->value ?? '';
  $rendered_len = strlen($rendered);

  $status_icon = ($status == 1 && $mod == 'published') ? '✓' : '✗';
  $content_icon = ($has_html && $has_css) ? '✓' : '✗';

  if ($status != 1 || !$has_html) $all_ok = FALSE;

  echo "  [{$status_icon}] ID {$id}: {$title}\n";
  echo "      Path: {$path} | Status: {$status} | Moderation: {$mod}\n";
  echo "      Meta: {$meta_title}\n";
  echo "      Layout: {$layout} | HTML: {$html_len}ch | CSS: {$css_len}ch | Rendered: {$rendered_len}ch\n\n";
}

// 3. Page Tree
echo "─── 3. NAVIGATION TREE ─────────────────────────────────────\n";
$tree_storage = $etm->getStorage('site_page_tree');
$tree_nodes = $tree_storage->loadByProperties(['tenant_id' => 5]);

// Sort by weight
$sorted = [];
foreach ($tree_nodes as $node) {
  $sorted[$node->get('weight')->value] = $node;
}
ksort($sorted);

foreach ($sorted as $node) {
  $nav = $node->get('nav_title')->value ?? '-';
  $page_id = $node->get('page_id')->target_id ?? '-';
  $show = $node->get('show_in_navigation')->value ? 'NAV' : 'FOOTER';
  $w = $node->get('weight')->value;
  echo "  [{$show}] w:{$w} — {$nav} (page:{$page_id})\n";
}

// 4. Summary
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  RESUMEN FINAL\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  Sitio: " . ($config ? $config->get('site_name')->value : 'ERROR') . "\n";
echo "  Páginas creadas: " . count($pages) . "/9\n";
echo "  Nodos de navegación: " . count($tree_nodes) . "/9\n";
echo "  Estado general: " . ($all_ok ? '✓ TODO OK' : '⚠ Revisar') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";

echo "\n  URLs de acceso:\n";
foreach ($pages as $page) {
  $id = $page->id();
  $title = $page->get('title')->value;
  echo "    {$title}: /page/{$id}\n";
}
echo "\n  Canvas Editor URLs:\n";
foreach ($pages as $page) {
  $id = $page->id();
  $title = $page->get('title')->value;
  echo "    {$title}: /page/{$id}/editor\n";
}
