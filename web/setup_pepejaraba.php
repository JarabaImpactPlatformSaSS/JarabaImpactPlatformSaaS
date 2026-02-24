<?php

/**
 * Setup Pepe Jaraba meta-site config + create all pages.
 * Run: lando drush scr web/setup_pepejaraba.php
 */

$etm = \Drupal::entityTypeManager();

// ─── 1. UPDATE SITE CONFIG (already exists as ID 1, tenant 5) ─────────
$storage = $etm->getStorage('site_config');
$config = $storage->load(1);

if (!$config) {
  echo "ERROR: SiteConfig ID 1 not found\n";
  return;
}

echo "=== Updating SiteConfig for Pepe Jaraba (Tenant 5) ===\n";

$config->set('site_name', 'Pepe Jaraba');
$config->set('site_tagline', 'Transformación digital para todos, sin rodeos');
$config->set('contact_email', 'info@pepejaraba.com');
$config->set('contact_phone', '+34 623 174 304');
$config->set('contact_address', 'Calle Héroe de Sostoa 12, 29002 Málaga, España');
$config->set('contact_coordinates', '{"lat": 36.7213, "lng": -4.4214}');
$config->set('meta_title_suffix', '| Pepe Jaraba');
$config->set('social_links', json_encode([
  'linkedin' => 'https://www.linkedin.com/in/pepejaraba/',
  'facebook' => 'https://www.facebook.com/PepeJaraba',
  'instagram' => 'https://www.instagram.com/pepejaraba_/',
  'youtube' => 'https://www.youtube.com/@PepeJaraba',
  'whatsapp' => 'https://wa.me/34623174304',
]));

// Header config
$config->set('header_type', 'minimal');
$config->set('header_sticky', TRUE);
$config->set('header_transparent', FALSE);
$config->set('header_cta_text', 'Acceder al Ecosistema');
$config->set('header_cta_url', 'https://plataformadeecosistemas.com');

// Footer config
$config->set('footer_type', 'standard');
$config->set('footer_columns', 3);
$config->set('footer_show_social', TRUE);
$config->set('footer_show_newsletter', TRUE);
$config->set('footer_copyright', '© {year} Pepe Jaraba. Plataforma de Ecosistemas Digitales S.L. Todos los derechos reservados.');

$config->save();
echo "SiteConfig updated successfully.\n\n";

// ─── 2. CREATE PAGES FOR PEPE JARABA ──────────────────────────────────
$page_storage = $etm->getStorage('page_content');

// Delete existing pages for tenant 5 first (clean slate)
$existing = $page_storage->loadByProperties(['tenant_id' => 5]);
if (!empty($existing)) {
  echo "Deleting " . count($existing) . " existing pages for tenant 5...\n";
  $page_storage->delete($existing);
}

// Page definitions
$pages = [
  [
    'title' => 'Inicio',
    'path_alias' => '/inicio',
    'meta_title' => 'Transformación Digital Empresas | Pepe Jaraba',
    'meta_description' => 'Soy Pepe Jaraba. Consultor en Transformación Digital. Te ayudo a conseguir resultados reales de transformación digital sin tecnicismos. Sin humo, solo resultados.',
    'status' => 1,
    'is_homepage' => TRUE,
  ],
  [
    'title' => 'Manifiesto',
    'path_alias' => '/manifiesto',
    'meta_title' => 'Mi Historia: Vi un Puente Roto y Decidí Construirlo | Pepe Jaraba',
    'meta_description' => 'Más de 30 años gestionando fondos europeos me enseñaron que la transformación digital necesita puentes, no muros. Descubre por qué creé el Ecosistema Sin Humo.',
    'status' => 1,
  ],
  [
    'title' => 'Método Jaraba',
    'path_alias' => '/metodo',
    'meta_title' => 'El Método Jaraba: Ciclo de Impacto Digital en 90 Días | Pepe Jaraba',
    'meta_description' => 'Un sistema de 3 fases para conseguir resultados reales de transformación digital. Diagnóstico, implementación y optimización. Sin humo, sin atajos.',
    'status' => 1,
  ],
  [
    'title' => 'Casos de Éxito',
    'path_alias' => '/casos-de-exito',
    'meta_title' => 'Resultados Reales, Historias Reales | Pepe Jaraba',
    'meta_description' => 'Descubre cómo pymes, emprendedores y profesionales han transformado su negocio digital con el Método Jaraba. Casos reales de impacto medible.',
    'status' => 1,
  ],
  [
    'title' => 'Blog',
    'path_alias' => '/blog',
    'meta_title' => 'Blog: Estrategias y Recursos Sin Humo | Pepe Jaraba',
    'meta_description' => 'Ideas prácticas de transformación digital para impulsar tu proyecto. Sin tecnicismos, sin humo. Solo estrategias que funcionan.',
    'status' => 1,
  ],
  [
    'title' => 'Contacto',
    'path_alias' => '/contacto',
    'meta_title' => 'Hablemos. Sin Humo | Pepe Jaraba',
    'meta_description' => 'Contacta con Pepe Jaraba para impulsar tu transformación digital. Consulta general, proyecto específico o conferencias. Respuesta en 48h.',
    'status' => 1,
  ],
  [
    'title' => 'Aviso Legal',
    'path_alias' => '/aviso-legal',
    'meta_title' => 'Aviso Legal | Pepe Jaraba',
    'meta_description' => 'Aviso legal de pepejaraba.com. Plataforma de Ecosistemas Digitales S.L. NIF B93750271.',
    'status' => 1,
  ],
  [
    'title' => 'Política de Privacidad',
    'path_alias' => '/privacidad',
    'meta_title' => 'Política de Privacidad | Pepe Jaraba',
    'meta_description' => 'Política de privacidad y protección de datos de pepejaraba.com conforme al RGPD.',
    'status' => 1,
  ],
  [
    'title' => 'Política de Cookies',
    'path_alias' => '/cookies',
    'meta_title' => 'Política de Cookies | Pepe Jaraba',
    'meta_description' => 'Política de cookies de pepejaraba.com. Información sobre los tipos de cookies utilizadas.',
    'status' => 1,
  ],
];

$created_pages = [];
foreach ($pages as $page_data) {
  $is_homepage = $page_data['is_homepage'] ?? FALSE;
  unset($page_data['is_homepage']);

  $page = $page_storage->create([
    'tenant_id' => 5,
    'title' => $page_data['title'],
    'path_alias' => $page_data['path_alias'],
    'meta_title' => $page_data['meta_title'],
    'meta_description' => $page_data['meta_description'],
    'status' => $page_data['status'],
    'layout_mode' => 'canvas',
    'canvas_data' => json_encode(['html' => '', 'css' => '', 'components' => [], 'styles' => []]),
    'rendered_html' => '',
  ]);
  $page->save();

  $created_pages[$page_data['title']] = $page->id();
  echo "Created page: {$page_data['title']} (ID: {$page->id()}) - {$page_data['path_alias']}\n";

  // Set as homepage
  if ($is_homepage) {
    $config->set('homepage_id', $page->id());
    $config->save();
    echo "  → Set as homepage\n";
  }
}

// Set legal pages
if (isset($created_pages['Aviso Legal'])) {
  // terms_conditions_id for Aviso Legal
}
if (isset($created_pages['Política de Privacidad'])) {
  $config->set('privacy_policy_id', $created_pages['Política de Privacidad']);
}
if (isset($created_pages['Política de Cookies'])) {
  $config->set('cookies_policy_id', $created_pages['Política de Cookies']);
}
if (isset($created_pages['Aviso Legal'])) {
  $config->set('terms_conditions_id', $created_pages['Aviso Legal']);
}
if (isset($created_pages['Blog'])) {
  $config->set('blog_index_id', $created_pages['Blog']);
}
$config->save();
echo "\nLinked legal pages and blog to SiteConfig.\n";

// ─── 3. CREATE SITE PAGE TREE ─────────────────────────────────────────
$tree_storage = $etm->getStorage('site_page_tree');

// Delete existing tree for tenant 5
$existing_tree = $tree_storage->loadByProperties(['tenant_id' => 5]);
if (!empty($existing_tree)) {
  $tree_storage->delete($existing_tree);
  echo "Cleared existing page tree.\n";
}

// Tree structure: main pages at root, legal pages nested
$tree_items = [
  ['page_id' => $created_pages['Inicio'], 'weight' => 0, 'depth' => 0, 'nav_title' => 'Inicio', 'show_in_navigation' => TRUE],
  ['page_id' => $created_pages['Manifiesto'], 'weight' => 1, 'depth' => 0, 'nav_title' => 'Manifiesto', 'show_in_navigation' => TRUE],
  ['page_id' => $created_pages['Método Jaraba'], 'weight' => 2, 'depth' => 0, 'nav_title' => 'Método', 'show_in_navigation' => TRUE],
  ['page_id' => $created_pages['Casos de Éxito'], 'weight' => 3, 'depth' => 0, 'nav_title' => 'Casos de éxito', 'show_in_navigation' => TRUE],
  ['page_id' => $created_pages['Blog'], 'weight' => 4, 'depth' => 0, 'nav_title' => 'Blog', 'show_in_navigation' => TRUE],
  ['page_id' => $created_pages['Contacto'], 'weight' => 5, 'depth' => 0, 'nav_title' => 'Contacto', 'show_in_navigation' => TRUE],
  ['page_id' => $created_pages['Aviso Legal'], 'weight' => 10, 'depth' => 0, 'nav_title' => 'Aviso Legal', 'show_in_navigation' => FALSE],
  ['page_id' => $created_pages['Política de Privacidad'], 'weight' => 11, 'depth' => 0, 'nav_title' => 'Privacidad', 'show_in_navigation' => FALSE],
  ['page_id' => $created_pages['Política de Cookies'], 'weight' => 12, 'depth' => 0, 'nav_title' => 'Cookies', 'show_in_navigation' => FALSE],
];

foreach ($tree_items as $item) {
  $tree_node = $tree_storage->create([
    'tenant_id' => 5,
    'page_id' => $item['page_id'],
    'weight' => $item['weight'],
    'depth' => $item['depth'],
    'path' => '/' . $item['weight'],
    'nav_title' => $item['nav_title'],
    'show_in_navigation' => $item['show_in_navigation'],
    'status' => 1,
  ]);
  $tree_node->save();
  $nav = $item['show_in_navigation'] ? '✓ nav' : '✗ footer-only';
  echo "Tree: {$item['nav_title']} (weight: {$item['weight']}) [{$nav}]\n";
}

echo "\n=== PEPE JARABA META-SITE SETUP COMPLETE ===\n";
echo "Pages created: " . count($created_pages) . "\n";
echo "Tree nodes created: " . count($tree_items) . "\n";
echo "\nPage IDs for Canvas editing:\n";
foreach ($created_pages as $title => $id) {
  echo "  {$title}: /page/{$id}/editor\n";
}
