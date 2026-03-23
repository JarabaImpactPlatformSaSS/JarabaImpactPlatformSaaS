<?php

/**
 * @file
 * CONTENT-SEED-PIPELINE-001: Exportación de contenido de metasitios.
 *
 * Exporta PageContent, SitePageTree, SiteConfig y SiteMenu/SiteMenuItem
 * de los 3 metasitios a ficheros JSON individuales en scripts/content-seed/data/.
 *
 * El JSON usa UUID como ancla (no IDs auto-incrementales) para permitir
 * importación idempotente en cualquier entorno donde los IDs pueden diferir.
 *
 * Uso:
 *   lando drush php:script scripts/content-seed/export-metasite-content.php
 *   lando drush php:script scripts/content-seed/export-metasite-content.php -- --domain=pepejaraba.com
 *
 * Exit: 0 = exportación correcta, 1 = error
 */

declare(strict_types=1);

// ============================================================================
// Configuración: dominios de los 3 metasitios del Ecosistema Jaraba.
// ============================================================================
const METASITE_DOMAINS = [
  'pepejaraba.com',
  'jarabaimpact.com',
  'plataformadeecosistemas.es',
];

const OUTPUT_DIR = __DIR__ . '/data';

// ============================================================================
// Parseo de argumentos.
// ============================================================================
$domainFilter = NULL;
$extra = $extra ?? [];
foreach ($extra as $arg) {
  if (str_starts_with($arg, '--domain=')) {
    $domainFilter = substr($arg, 9);
  }
}

$domains = $domainFilter ? [$domainFilter] : METASITE_DOMAINS;

// ============================================================================
// Servicios.
// ============================================================================
$entityTypeManager = \Drupal::entityTypeManager();
$tenantStorage = $entityTypeManager->getStorage('tenant');
$pageStorage = $entityTypeManager->getStorage('page_content');
$sptStorage = $entityTypeManager->getStorage('site_page_tree');
$scStorage = $entityTypeManager->getStorage('site_config');

// SiteMenu y SiteMenuItem pueden no existir si no se usan.
$menuStorage = NULL;
$menuItemStorage = NULL;
try {
  $menuStorage = $entityTypeManager->getStorage('site_menu');
  $menuItemStorage = $entityTypeManager->getStorage('site_menu_item');
}
catch (\Throwable) {
  // Silenciar si las entidades no están registradas.
}

echo "\n==========================================\n";
echo "Content Seed Export — Metasitios\n";
echo "==========================================\n\n";

$totalErrors = 0;

foreach ($domains as $domain) {
  echo "─── Exportando: {$domain} ───\n\n";

  // ==========================================================================
  // 1. Resolver Tenant → Group ID.
  // ==========================================================================
  $tenants = $tenantStorage->loadByProperties(['domain' => $domain]);
  if (empty($tenants)) {
    echo "  ✗ ERROR: No se encontró Tenant con domain='{$domain}'\n\n";
    $totalErrors++;
    continue;
  }
  $tenant = reset($tenants);
  $tenantId = (int) $tenant->id();
  $groupId = (int) $tenant->get('group_id')->target_id;
  $tenantName = $tenant->label();

  if (!$groupId) {
    echo "  ✗ ERROR: Tenant '{$tenantName}' (ID:{$tenantId}) sin group_id\n\n";
    $totalErrors++;
    continue;
  }

  echo "  Tenant: {$tenantName} (ID:{$tenantId}, Group:{$groupId})\n";

  // ==========================================================================
  // 2. Exportar PageContent.
  // ==========================================================================
  $pageIds = $pageStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->sort('id', 'ASC')
    ->execute();

  $pages = [];
  $pageUuidMap = []; // local_id => uuid para resolución en SPT/SC.

  foreach ($pageStorage->loadMultiple($pageIds) as $page) {
    $uuid = $page->uuid();
    $localId = (int) $page->id();
    $pageUuidMap[$localId] = $uuid;

    $canvasRaw = $page->get('canvas_data')->value ?? '';
    $renderedHtml = $page->get('rendered_html')->value ?? '';
    $sectionsRaw = $page->get('sections')->value ?? '[]';

    // Si rendered_html está vacío pero canvas_data tiene html, extraer.
    if (empty($renderedHtml) && !empty($canvasRaw)) {
      $canvasData = json_decode($canvasRaw, TRUE);
      if (is_array($canvasData) && !empty($canvasData['html'])) {
        $renderedHtml = $canvasData['html'];
      }
    }

    $pages[] = [
      'uuid' => $uuid,
      'local_id' => $localId,
      'title' => $page->get('title')->value ?? '',
      'path_alias' => $page->get('path_alias')->value ?? '',
      'template_id' => $page->get('template_id')->value ?? '',
      'layout_mode' => $page->get('layout_mode')->value ?? 'legacy',
      'status' => (bool) ($page->get('status')->value ?? TRUE),
      'meta_title' => $page->get('meta_title')->value ?? '',
      'meta_description' => $page->get('meta_description')->value ?? '',
      'canvas_data' => $canvasRaw,
      'rendered_html' => $renderedHtml,
      'sections' => $sectionsRaw,
      'content_data' => $page->get('content_data')->value ?? '',
      'menu_link' => $page->get('menu_link')->value ?? '',
      'langcode' => $page->get('langcode')->value ?? 'es',
    ];
  }

  echo "  PageContent: " . count($pages) . " páginas\n";

  // ==========================================================================
  // 3. Exportar SitePageTree.
  // ==========================================================================
  $sptIds = $sptStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->sort('weight', 'ASC')
    ->execute();

  $sptEntries = [];
  $sptUuidMap = []; // local_id => uuid para resolución de parent_id.

  foreach ($sptStorage->loadMultiple($sptIds) as $spt) {
    $uuid = $spt->uuid();
    $localId = (int) $spt->id();
    $sptUuidMap[$localId] = $uuid;

    $pageId = $spt->get('page_id')->target_id;
    $parentId = $spt->get('parent_id')->target_id;

    $sptEntries[] = [
      'uuid' => $uuid,
      'local_id' => $localId,
      'page_uuid' => $pageId ? ($pageUuidMap[(int) $pageId] ?? NULL) : NULL,
      'page_local_id' => $pageId ? (int) $pageId : NULL,
      'parent_uuid' => NULL, // Se resuelve después.
      'parent_local_id' => $parentId ? (int) $parentId : NULL,
      'nav_title' => $spt->get('nav_title')->value ?? '',
      'weight' => (int) ($spt->get('weight')->value ?? 0),
      'depth' => (int) ($spt->get('depth')->value ?? 0),
      'path' => $spt->get('path')->value ?? '',
      'show_in_navigation' => (bool) ($spt->get('show_in_navigation')->value ?? TRUE),
      'show_in_footer' => (bool) ($spt->get('show_in_footer')->value ?? FALSE),
      'show_in_sitemap' => (bool) ($spt->get('show_in_sitemap')->value ?? TRUE),
      'show_in_breadcrumbs' => (bool) ($spt->get('show_in_breadcrumbs')->value ?? TRUE),
      'nav_icon' => $spt->get('nav_icon')->value ?? '',
      'nav_highlight' => (bool) ($spt->get('nav_highlight')->value ?? FALSE),
      'nav_external_url' => $spt->get('nav_external_url')->value ?? '',
      'status' => $spt->get('status')->value ?? 'published',
    ];
  }

  // Resolver parent_uuid (segunda pasada).
  foreach ($sptEntries as &$entry) {
    if ($entry['parent_local_id'] && isset($sptUuidMap[$entry['parent_local_id']])) {
      $entry['parent_uuid'] = $sptUuidMap[$entry['parent_local_id']];
    }
  }
  unset($entry);

  echo "  SitePageTree: " . count($sptEntries) . " entradas\n";

  // ==========================================================================
  // 4. Exportar SiteConfig.
  // ==========================================================================
  $scIds = $scStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->execute();

  $siteConfigData = NULL;
  if (!empty($scIds)) {
    $sc = $scStorage->load(reset($scIds));
    if ($sc) {
      // Resolver UUIDs de páginas referenciadas.
      $resolvePageUuid = function (?int $pageId) use ($pageUuidMap): ?string {
        return ($pageId && isset($pageUuidMap[$pageId])) ? $pageUuidMap[$pageId] : NULL;
      };

      $homepageId = $sc->get('homepage_id')->target_id;
      $blogId = $sc->get('blog_index_id')->target_id;
      $privacyId = $sc->get('privacy_policy_id')->target_id;
      $termsId = $sc->get('terms_conditions_id')->target_id;
      $cookiesId = $sc->get('cookies_policy_id')->target_id;

      $siteConfigData = [
        'uuid' => $sc->uuid(),
        'local_id' => (int) $sc->id(),
        'site_name' => $sc->get('site_name')->value ?? '',
        'site_tagline' => $sc->get('site_tagline')->value ?? '',
        'homepage_uuid' => $resolvePageUuid($homepageId ? (int) $homepageId : NULL),
        'homepage_local_id' => $homepageId ? (int) $homepageId : NULL,
        'blog_index_uuid' => $resolvePageUuid($blogId ? (int) $blogId : NULL),
        'blog_index_local_id' => $blogId ? (int) $blogId : NULL,
        'privacy_policy_uuid' => $resolvePageUuid($privacyId ? (int) $privacyId : NULL),
        'privacy_policy_local_id' => $privacyId ? (int) $privacyId : NULL,
        'terms_conditions_uuid' => $resolvePageUuid($termsId ? (int) $termsId : NULL),
        'terms_conditions_local_id' => $termsId ? (int) $termsId : NULL,
        'cookies_policy_uuid' => $resolvePageUuid($cookiesId ? (int) $cookiesId : NULL),
        'cookies_policy_local_id' => $cookiesId ? (int) $cookiesId : NULL,
        'meta_title_suffix' => $sc->get('meta_title_suffix')->value ?? '',
        'header_type' => $sc->get('header_type')->value ?? 'classic',
        'header_sticky' => (bool) ($sc->get('header_sticky')->value ?? TRUE),
        'header_transparent' => (bool) ($sc->get('header_transparent')->value ?? FALSE),
        'header_cta_text' => $sc->get('header_cta_text')->value ?? '',
        'header_cta_url' => $sc->get('header_cta_url')->value ?? '',
        'header_show_auth' => (bool) ($sc->get('header_show_auth')->value ?? TRUE),
        'footer_type' => $sc->get('footer_type')->value ?? 'standard',
        'footer_columns' => (int) ($sc->get('footer_columns')->value ?? 4),
        'footer_show_social' => (bool) ($sc->get('footer_show_social')->value ?? TRUE),
        'footer_show_newsletter' => (bool) ($sc->get('footer_show_newsletter')->value ?? FALSE),
        'footer_copyright' => $sc->get('footer_copyright')->value ?? '',
        'footer_col1_title' => $sc->get('footer_col1_title')->value ?? '',
        'footer_col2_title' => $sc->get('footer_col2_title')->value ?? '',
        'footer_col3_title' => $sc->get('footer_col3_title')->value ?? '',
        'contact_email' => $sc->get('contact_email')->value ?? '',
        'contact_phone' => $sc->get('contact_phone')->value ?? '',
        'contact_address' => $sc->get('contact_address')->value ?? '',
        'contact_coordinates' => $sc->get('contact_coordinates')->value ?? '',
        'social_links' => $sc->get('social_links')->value ?? '',
        'ecosystem_footer_enabled' => (bool) ($sc->get('ecosystem_footer_enabled')->value ?? FALSE),
        'ecosystem_footer_links' => $sc->get('ecosystem_footer_links')->value ?? '',
        'google_analytics_id' => $sc->get('google_analytics_id')->value ?? '',
        'google_tag_manager_id' => $sc->get('google_tag_manager_id')->value ?? '',
        'langcode' => $sc->get('langcode')->value ?? 'es',
      ];
    }
  }

  echo "  SiteConfig: " . ($siteConfigData ? 'exportado' : 'NO ENCONTRADO') . "\n";

  // ==========================================================================
  // 5. Exportar SiteMenu y SiteMenuItem (si existen).
  // ==========================================================================
  $menus = [];
  $menuItems = [];

  if ($menuStorage) {
    $menuIds = $menuStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $groupId)
      ->execute();

    foreach ($menuStorage->loadMultiple($menuIds) as $menu) {
      $menuUuid = $menu->uuid();
      $menus[] = [
        'uuid' => $menuUuid,
        'local_id' => (int) $menu->id(),
        'machine_name' => $menu->get('machine_name')->value ?? '',
        'label' => $menu->label() ?? '',
        'description' => $menu->get('description')->value ?? '',
      ];

      // Exportar items de este menú.
      if ($menuItemStorage) {
        $itemIds = $menuItemStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('menu_id', $menu->id())
          ->sort('weight', 'ASC')
          ->execute();

        foreach ($menuItemStorage->loadMultiple($itemIds) as $item) {
          $itemPageId = $item->get('page_id')->target_id;
          $itemParentId = $item->get('parent_id')->target_id;

          $menuItems[] = [
            'uuid' => $item->uuid(),
            'local_id' => (int) $item->id(),
            'menu_uuid' => $menuUuid,
            'title' => $item->get('title')->value ?? '',
            'url' => $item->get('url')->value ?? '',
            'page_uuid' => $itemPageId ? ($pageUuidMap[(int) $itemPageId] ?? NULL) : NULL,
            'page_local_id' => $itemPageId ? (int) $itemPageId : NULL,
            'parent_uuid' => NULL, // Simple: no self-referencing en export.
            'parent_local_id' => $itemParentId ? (int) $itemParentId : NULL,
            'item_type' => $item->get('item_type')->value ?? 'link',
            'icon' => $item->get('icon')->value ?? '',
            'badge_text' => $item->get('badge_text')->value ?? '',
            'badge_color' => $item->get('badge_color')->value ?? '',
            'highlight' => (bool) ($item->get('highlight')->value ?? FALSE),
            'mega_content' => $item->get('mega_content')->value ?? '',
            'open_in_new_tab' => (bool) ($item->get('open_in_new_tab')->value ?? FALSE),
            'is_enabled' => (bool) ($item->get('is_enabled')->value ?? TRUE),
            'weight' => (int) ($item->get('weight')->value ?? 0),
            'depth' => (int) ($item->get('depth')->value ?? 0),
          ];
        }
      }
    }
  }

  echo "  SiteMenu: " . count($menus) . " menús, " . count($menuItems) . " items\n";

  // ==========================================================================
  // 6. Validar integridad referencial antes de escribir.
  // ==========================================================================
  $integrityErrors = [];

  // SC homepage_id debe apuntar a una página exportada.
  if ($siteConfigData && $siteConfigData['homepage_uuid'] === NULL && $siteConfigData['homepage_local_id'] !== NULL) {
    $integrityErrors[] = "SiteConfig.homepage_id={$siteConfigData['homepage_local_id']} no resuelve a UUID (página no exportada)";
  }

  // SPT page_uuid debe resolverse (si page_local_id no es NULL).
  foreach ($sptEntries as $spt) {
    if ($spt['page_local_id'] !== NULL && $spt['page_uuid'] === NULL) {
      $integrityErrors[] = "SPT '{$spt['nav_title']}' page_id={$spt['page_local_id']} no resuelve a UUID";
    }
  }

  if (!empty($integrityErrors)) {
    echo "\n  ⚠ Warnings de integridad:\n";
    foreach ($integrityErrors as $err) {
      echo "    - {$err}\n";
    }
  }

  // ==========================================================================
  // 7. Escribir JSON.
  // ==========================================================================
  $slug = match ($domain) {
    'pepejaraba.com' => 'pepejaraba',
    'jarabaimpact.com' => 'jarabaimpact',
    'plataformadeecosistemas.es' => 'ped',
    default => preg_replace('/[^a-z0-9]/', '-', strtolower($domain)),
  };

  $output = [
    '_metadata' => [
      'format_version' => '1.0.0',
      'exported_at' => date('c'),
      'source_environment' => php_uname('n'),
      'tenant_domain' => $domain,
      'tenant_name' => $tenantName,
      'tenant_id_local' => $tenantId,
      'group_id_local' => $groupId,
      'entity_counts' => [
        'page_content' => count($pages),
        'site_page_tree' => count($sptEntries),
        'site_config' => $siteConfigData ? 1 : 0,
        'site_menu' => count($menus),
        'site_menu_item' => count($menuItems),
      ],
    ],
    'page_content' => $pages,
    'site_page_tree' => $sptEntries,
    'site_config' => $siteConfigData,
    'site_menu' => $menus,
    'site_menu_item' => $menuItems,
  ];

  $jsonPath = OUTPUT_DIR . "/metasite-{$slug}.json";
  $jsonBytes = file_put_contents(
    $jsonPath,
    json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
  );

  if ($jsonBytes === FALSE) {
    echo "  ✗ ERROR escribiendo {$jsonPath}\n\n";
    $totalErrors++;
  }
  else {
    $sizeKb = round($jsonBytes / 1024, 1);
    echo "\n  ✓ Exportado: {$jsonPath} ({$sizeKb} KB)\n\n";
  }
}

// ============================================================================
// Resumen final.
// ============================================================================
echo "==========================================\n";
if ($totalErrors > 0) {
  echo "❌ Exportación completada con {$totalErrors} errores\n";
  exit(1);
}
echo "✅ Exportación completada sin errores\n";
echo "==========================================\n\n";
exit(0);
