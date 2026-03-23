<?php

/**
 * @file
 * CONTENT-SEED-PIPELINE-001: Importación de contenido de metasitios.
 *
 * Importa PageContent, SitePageTree, SiteMenu/SiteMenuItem y actualiza
 * SiteConfig desde los ficheros JSON generados por export-metasite-content.php.
 *
 * Estrategia:
 *   - UUID-Anchored: busca entidades existentes por UUID. Si existe, actualiza.
 *     Si no existe, crea con UUID forzado.
 *   - Idempotente: ejecutar N veces produce el mismo resultado.
 *   - Orden de dependencias: PageContent → SitePageTree → SiteMenu/MenuItem → SiteConfig.
 *   - Resolución de tenant: busca Tenant por domain → obtiene group_id en destino.
 *
 * Uso:
 *   lando drush php:script scripts/content-seed/import-metasite-content.php
 *   lando drush php:script scripts/content-seed/import-metasite-content.php -- --dry-run
 *   lando drush php:script scripts/content-seed/import-metasite-content.php -- --domain=pepejaraba.com
 *
 * Exit: 0 = importación correcta, 1 = error
 */

declare(strict_types=1);

const DATA_DIR = __DIR__ . '/data';

// ============================================================================
// Parseo de argumentos.
// ============================================================================
$dryRun = FALSE;
$domainFilter = NULL;
$extra = $extra ?? [];
foreach ($extra as $arg) {
  if ($arg === '--dry-run') {
    $dryRun = TRUE;
  }
  if (str_starts_with($arg, '--domain=')) {
    $domainFilter = substr($arg, 9);
  }
}

// ============================================================================
// Descubrir JSONs disponibles.
// ============================================================================
$jsonFiles = glob(DATA_DIR . '/metasite-*.json');
if (empty($jsonFiles)) {
  echo "✗ No se encontraron JSONs en " . DATA_DIR . "\n";
  echo "  Ejecuta primero: drush php:script scripts/content-seed/export-metasite-content.php\n";
  exit(1);
}

echo "\n==========================================\n";
echo "Content Seed Import — Metasitios\n";
echo $dryRun ? "  ⚡ MODO DRY-RUN (sin escritura en DB)\n" : '';
echo "==========================================\n\n";

// ============================================================================
// Servicios.
// ============================================================================
$entityTypeManager = \Drupal::entityTypeManager();
$tenantStorage = $entityTypeManager->getStorage('tenant');
$pageStorage = $entityTypeManager->getStorage('page_content');
$sptStorage = $entityTypeManager->getStorage('site_page_tree');
$scStorage = $entityTypeManager->getStorage('site_config');

$menuStorage = NULL;
$menuItemStorage = NULL;
try {
  $menuStorage = $entityTypeManager->getStorage('site_menu');
  $menuItemStorage = $entityTypeManager->getStorage('site_menu_item');
}
catch (\Throwable) {
  // Silenciar.
}

$totalCreated = 0;
$totalUpdated = 0;
$totalErrors = 0;

foreach ($jsonFiles as $jsonFile) {
  $rawJson = file_get_contents($jsonFile);
  $data = json_decode($rawJson, TRUE);

  if (!is_array($data) || empty($data['_metadata'])) {
    echo "  ✗ ERROR: JSON inválido en {$jsonFile}\n";
    $totalErrors++;
    continue;
  }

  $meta = $data['_metadata'];
  $domain = $meta['tenant_domain'];

  // Filtrar por dominio si se especificó.
  if ($domainFilter && $domain !== $domainFilter) {
    continue;
  }

  echo "─── Importando: {$domain} ({$meta['tenant_name']}) ───\n\n";

  // ==========================================================================
  // Fase A: Resolver tenant_id en destino.
  // ==========================================================================
  $tenants = $tenantStorage->loadByProperties(['domain' => $domain]);
  if (empty($tenants)) {
    echo "  ✗ ERROR: No se encontró Tenant con domain='{$domain}' en este entorno\n";
    echo "  El Tenant debe existir antes de importar contenido.\n\n";
    $totalErrors++;
    continue;
  }
  $tenant = reset($tenants);
  $targetGroupId = (int) $tenant->get('group_id')->target_id;

  if (!$targetGroupId) {
    echo "  ✗ ERROR: Tenant '{$domain}' sin group_id\n\n";
    $totalErrors++;
    continue;
  }

  echo "  Tenant resuelto: group_id={$targetGroupId}\n";

  // ==========================================================================
  // Fase B: Crear/actualizar PageContent.
  // ==========================================================================
  echo "\n  [B] PageContent ({$meta['entity_counts']['page_content']} en JSON)...\n";

  $pageUuidToIdMap = []; // uuid → ID en destino.

  foreach ($data['page_content'] as $pageData) {
    $uuid = $pageData['uuid'];
    $existing = $pageStorage->loadByProperties(['uuid' => $uuid]);

    if (!empty($existing)) {
      // Actualizar entidad existente.
      $page = reset($existing);
      $action = 'actualizar';

      // Solo sobreescribir campos con contenido no vacío.
      $page->set('title', $pageData['title']);
      $page->set('path_alias', $pageData['path_alias']);
      $page->set('template_id', $pageData['template_id']);
      $page->set('layout_mode', $pageData['layout_mode']);
      $page->set('status', $pageData['status']);
      $page->set('tenant_id', $targetGroupId);

      if (!empty($pageData['meta_title'])) {
        $page->set('meta_title', $pageData['meta_title']);
      }
      if (!empty($pageData['meta_description'])) {
        $page->set('meta_description', $pageData['meta_description']);
      }
      if (!empty($pageData['canvas_data']) && strlen($pageData['canvas_data']) > 10) {
        $page->set('canvas_data', $pageData['canvas_data']);
      }
      if (!empty($pageData['rendered_html']) && strlen($pageData['rendered_html']) > 10) {
        $page->set('rendered_html', $pageData['rendered_html']);
      }
      if (!empty($pageData['sections']) && $pageData['sections'] !== '[]') {
        $page->set('sections', $pageData['sections']);
      }
      if (!empty($pageData['content_data'])) {
        $page->set('content_data', $pageData['content_data']);
      }
    }
    else {
      // Crear nueva entidad con UUID forzado.
      $action = 'crear';

      $createData = [
        'uuid' => $uuid,
        'title' => $pageData['title'],
        'path_alias' => $pageData['path_alias'],
        'template_id' => $pageData['template_id'] ?: 'multiblock',
        'layout_mode' => $pageData['layout_mode'] ?: 'legacy',
        'status' => $pageData['status'],
        'tenant_id' => $targetGroupId,
        'uid' => 1,
        'langcode' => $pageData['langcode'] ?? 'es',
        'meta_title' => $pageData['meta_title'] ?? '',
        'meta_description' => $pageData['meta_description'] ?? '',
        'canvas_data' => $pageData['canvas_data'] ?? '',
        'rendered_html' => $pageData['rendered_html'] ?? '',
        'sections' => $pageData['sections'] ?? '[]',
        'content_data' => $pageData['content_data'] ?? '',
        'menu_link' => $pageData['menu_link'] ?? '',
      ];

      $page = $pageStorage->create($createData);
    }

    if (!$dryRun) {
      try {
        $page->save();
        $pageUuidToIdMap[$uuid] = (int) $page->id();

        if ($action === 'crear') {
          $totalCreated++;
        }
        else {
          $totalUpdated++;
        }
        echo "    ✓ {$action}: '{$pageData['title']}' (ID:{$page->id()})\n";
      }
      catch (\Throwable $e) {
        echo "    ✗ ERROR {$action} '{$pageData['title']}': {$e->getMessage()}\n";
        $totalErrors++;
      }
    }
    else {
      echo "    [dry-run] {$action}: '{$pageData['title']}' (uuid:{$uuid})\n";
      // En dry-run, usar local_id como referencia temporal.
      $pageUuidToIdMap[$uuid] = $pageData['local_id'];
    }
  }

  // ==========================================================================
  // Fase C: Crear/actualizar SitePageTree.
  // ==========================================================================
  echo "\n  [C] SitePageTree ({$meta['entity_counts']['site_page_tree']} en JSON)...\n";

  $sptUuidToIdMap = []; // uuid → ID en destino.

  // Primera pasada: crear/actualizar sin parent_id (evitar dependencia circular).
  foreach ($data['site_page_tree'] as $sptData) {
    $uuid = $sptData['uuid'];
    $existing = $sptStorage->loadByProperties(['uuid' => $uuid]);

    // Resolver page_id desde UUID.
    $resolvedPageId = NULL;
    if (!empty($sptData['page_uuid']) && isset($pageUuidToIdMap[$sptData['page_uuid']])) {
      $resolvedPageId = $pageUuidToIdMap[$sptData['page_uuid']];
    }

    if (!empty($existing)) {
      $spt = reset($existing);
      $action = 'actualizar';

      $spt->set('tenant_id', $targetGroupId);
      $spt->set('page_id', $resolvedPageId);
      $spt->set('nav_title', $sptData['nav_title']);
      $spt->set('weight', $sptData['weight']);
      $spt->set('depth', $sptData['depth']);
      $spt->set('show_in_navigation', $sptData['show_in_navigation']);
      $spt->set('show_in_footer', $sptData['show_in_footer']);
      $spt->set('show_in_sitemap', $sptData['show_in_sitemap']);
      $spt->set('show_in_breadcrumbs', $sptData['show_in_breadcrumbs']);
      $spt->set('nav_icon', $sptData['nav_icon']);
      $spt->set('nav_highlight', $sptData['nav_highlight']);
      $spt->set('nav_external_url', $sptData['nav_external_url']);
      $spt->set('status', $sptData['status']);
    }
    else {
      $action = 'crear';

      $spt = $sptStorage->create([
        'uuid' => $uuid,
        'tenant_id' => $targetGroupId,
        'page_id' => $resolvedPageId,
        'parent_id' => NULL, // Se resuelve en segunda pasada.
        'nav_title' => $sptData['nav_title'],
        'weight' => $sptData['weight'],
        'depth' => $sptData['depth'],
        'path' => $sptData['path'] ?? '',
        'show_in_navigation' => $sptData['show_in_navigation'],
        'show_in_footer' => $sptData['show_in_footer'],
        'show_in_sitemap' => $sptData['show_in_sitemap'],
        'show_in_breadcrumbs' => $sptData['show_in_breadcrumbs'],
        'nav_icon' => $sptData['nav_icon'],
        'nav_highlight' => $sptData['nav_highlight'],
        'nav_external_url' => $sptData['nav_external_url'],
        'status' => $sptData['status'],
      ]);
    }

    if (!$dryRun) {
      try {
        $spt->save();
        $sptUuidToIdMap[$uuid] = (int) $spt->id();

        if ($action === 'crear') {
          $totalCreated++;
        }
        else {
          $totalUpdated++;
        }
        echo "    ✓ {$action}: '{$sptData['nav_title']}' (ID:{$spt->id()})\n";
      }
      catch (\Throwable $e) {
        echo "    ✗ ERROR {$action} '{$sptData['nav_title']}': {$e->getMessage()}\n";
        $totalErrors++;
      }
    }
    else {
      echo "    [dry-run] {$action}: '{$sptData['nav_title']}'\n";
      $sptUuidToIdMap[$uuid] = $sptData['local_id'];
    }
  }

  // Segunda pasada: resolver parent_id.
  foreach ($data['site_page_tree'] as $sptData) {
    if (empty($sptData['parent_uuid'])) {
      continue;
    }
    if (!isset($sptUuidToIdMap[$sptData['uuid']]) || !isset($sptUuidToIdMap[$sptData['parent_uuid']])) {
      continue;
    }

    $sptId = $sptUuidToIdMap[$sptData['uuid']];
    $parentId = $sptUuidToIdMap[$sptData['parent_uuid']];

    if (!$dryRun) {
      $spt = $sptStorage->load($sptId);
      if ($spt) {
        $spt->set('parent_id', $parentId);
        $spt->save();
        echo "    ↳ parent_id resuelto: '{$sptData['nav_title']}' → parent ID:{$parentId}\n";
      }
    }
  }

  // ==========================================================================
  // Fase C2: Crear/actualizar SiteMenu y SiteMenuItem.
  // ==========================================================================
  if (!empty($data['site_menu']) && $menuStorage) {
    echo "\n  [C2] SiteMenu (" . count($data['site_menu']) . " menús)...\n";

    $menuUuidToIdMap = [];
    foreach ($data['site_menu'] as $menuData) {
      $uuid = $menuData['uuid'];
      $existing = $menuStorage->loadByProperties(['uuid' => $uuid]);

      if (!empty($existing)) {
        $menu = reset($existing);
        $menu->set('tenant_id', $targetGroupId);
        $menu->set('label', $menuData['label']);
        $action = 'actualizar';
      }
      else {
        $menu = $menuStorage->create([
          'uuid' => $uuid,
          'tenant_id' => $targetGroupId,
          'machine_name' => $menuData['machine_name'],
          'label' => $menuData['label'],
          'description' => $menuData['description'],
        ]);
        $action = 'crear';
      }

      if (!$dryRun) {
        try {
          $menu->save();
          $menuUuidToIdMap[$uuid] = (int) $menu->id();
          echo "    ✓ {$action}: '{$menuData['label']}' (ID:{$menu->id()})\n";
          $action === 'crear' ? $totalCreated++ : $totalUpdated++;
        }
        catch (\Throwable $e) {
          echo "    ✗ ERROR: {$e->getMessage()}\n";
          $totalErrors++;
        }
      }
      else {
        echo "    [dry-run] {$action}: '{$menuData['label']}'\n";
        $menuUuidToIdMap[$uuid] = $menuData['local_id'];
      }
    }

    // Importar SiteMenuItems.
    if (!empty($data['site_menu_item']) && $menuItemStorage) {
      echo "\n  [C3] SiteMenuItem (" . count($data['site_menu_item']) . " items)...\n";

      foreach ($data['site_menu_item'] as $itemData) {
        $uuid = $itemData['uuid'];
        $existing = $menuItemStorage->loadByProperties(['uuid' => $uuid]);

        $resolvedMenuId = $itemData['menu_uuid'] ? ($menuUuidToIdMap[$itemData['menu_uuid']] ?? NULL) : NULL;
        $resolvedPageId = $itemData['page_uuid'] ? ($pageUuidToIdMap[$itemData['page_uuid']] ?? NULL) : NULL;

        if (!empty($existing)) {
          $item = reset($existing);
          $action = 'actualizar';
          $item->set('title', $itemData['title']);
          $item->set('url', $itemData['url']);
          $item->set('page_id', $resolvedPageId);
          $item->set('item_type', $itemData['item_type']);
          $item->set('weight', $itemData['weight']);
          $item->set('is_enabled', $itemData['is_enabled']);
          if ($resolvedMenuId) {
            $item->set('menu_id', $resolvedMenuId);
          }
        }
        else {
          $action = 'crear';
          $item = $menuItemStorage->create([
            'uuid' => $uuid,
            'title' => $itemData['title'],
            'menu_id' => $resolvedMenuId,
            'url' => $itemData['url'],
            'page_id' => $resolvedPageId,
            'item_type' => $itemData['item_type'],
            'icon' => $itemData['icon'],
            'badge_text' => $itemData['badge_text'],
            'badge_color' => $itemData['badge_color'],
            'highlight' => $itemData['highlight'],
            'mega_content' => $itemData['mega_content'],
            'open_in_new_tab' => $itemData['open_in_new_tab'],
            'is_enabled' => $itemData['is_enabled'],
            'weight' => $itemData['weight'],
            'depth' => $itemData['depth'],
          ]);
        }

        if (!$dryRun) {
          try {
            $item->save();
            echo "    ✓ {$action}: '{$itemData['title']}'\n";
            $action === 'crear' ? $totalCreated++ : $totalUpdated++;
          }
          catch (\Throwable $e) {
            echo "    ✗ ERROR: {$e->getMessage()}\n";
            $totalErrors++;
          }
        }
        else {
          echo "    [dry-run] {$action}: '{$itemData['title']}'\n";
        }
      }
    }
  }

  // ==========================================================================
  // Fase D: Actualizar SiteConfig (solo campos de referencia y configuración).
  // ==========================================================================
  if ($data['site_config']) {
    echo "\n  [D] SiteConfig (actualizar referencias)...\n";

    $scData = $data['site_config'];

    // Buscar SiteConfig existente por tenant_id (NO crear nuevo).
    $existingScIds = $scStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $targetGroupId)
      ->execute();

    if (empty($existingScIds)) {
      echo "    ⚠ SiteConfig no existe para tenant group_id={$targetGroupId}. Creando nuevo.\n";
      $sc = $scStorage->create([
        'uuid' => $scData['uuid'],
        'tenant_id' => $targetGroupId,
        'langcode' => $scData['langcode'] ?? 'es',
      ]);
    }
    else {
      $sc = $scStorage->load(reset($existingScIds));
    }

    if ($sc) {
      // Resolver UUIDs de páginas a IDs en destino.
      $resolveUuid = function (?string $uuid) use ($pageUuidToIdMap): ?int {
        return ($uuid && isset($pageUuidToIdMap[$uuid])) ? $pageUuidToIdMap[$uuid] : NULL;
      };

      $sc->set('site_name', $scData['site_name']);
      $sc->set('site_tagline', $scData['site_tagline']);
      $sc->set('homepage_id', $resolveUuid($scData['homepage_uuid']));
      $sc->set('blog_index_id', $resolveUuid($scData['blog_index_uuid']));
      $sc->set('privacy_policy_id', $resolveUuid($scData['privacy_policy_uuid']));
      $sc->set('terms_conditions_id', $resolveUuid($scData['terms_conditions_uuid']));
      $sc->set('cookies_policy_id', $resolveUuid($scData['cookies_policy_uuid']));
      $sc->set('meta_title_suffix', $scData['meta_title_suffix']);
      $sc->set('header_type', $scData['header_type']);
      $sc->set('header_sticky', $scData['header_sticky']);
      $sc->set('header_transparent', $scData['header_transparent']);
      $sc->set('header_cta_text', $scData['header_cta_text']);
      $sc->set('header_cta_url', $scData['header_cta_url']);
      $sc->set('header_show_auth', $scData['header_show_auth']);
      $sc->set('footer_type', $scData['footer_type']);
      $sc->set('footer_columns', $scData['footer_columns']);
      $sc->set('footer_show_social', $scData['footer_show_social']);
      $sc->set('footer_show_newsletter', $scData['footer_show_newsletter']);
      $sc->set('footer_copyright', $scData['footer_copyright']);
      $sc->set('footer_col1_title', $scData['footer_col1_title']);
      $sc->set('footer_col2_title', $scData['footer_col2_title']);
      $sc->set('footer_col3_title', $scData['footer_col3_title']);
      $sc->set('contact_email', $scData['contact_email']);
      $sc->set('contact_phone', $scData['contact_phone']);
      $sc->set('contact_address', $scData['contact_address']);
      $sc->set('contact_coordinates', $scData['contact_coordinates']);
      $sc->set('social_links', $scData['social_links']);
      $sc->set('ecosystem_footer_enabled', $scData['ecosystem_footer_enabled']);
      $sc->set('ecosystem_footer_links', $scData['ecosystem_footer_links']);

      // Preservar google_analytics_id y google_tag_manager_id solo si el JSON los trae.
      if (!empty($scData['google_analytics_id'])) {
        $sc->set('google_analytics_id', $scData['google_analytics_id']);
      }
      if (!empty($scData['google_tag_manager_id'])) {
        $sc->set('google_tag_manager_id', $scData['google_tag_manager_id']);
      }

      if (!$dryRun) {
        try {
          $sc->save();
          $homepageId = $resolveUuid($scData['homepage_uuid']);
          echo "    ✓ SiteConfig actualizado (homepage_id={$homepageId})\n";
          $totalUpdated++;
        }
        catch (\Throwable $e) {
          echo "    ✗ ERROR actualizando SiteConfig: {$e->getMessage()}\n";
          $totalErrors++;
        }
      }
      else {
        echo "    [dry-run] Actualizaría SiteConfig con homepage_uuid={$scData['homepage_uuid']}\n";
      }
    }
  }

  echo "\n";
}

// ============================================================================
// Resumen final.
// ============================================================================
echo "==========================================\n";
echo "Resumen: {$totalCreated} creados, {$totalUpdated} actualizados, {$totalErrors} errores\n";

if (!$dryRun) {
  echo "\n💡 Ejecuta ahora: drush cr && drush php:script scripts/content-seed/validate-content-sync.php\n";
}

if ($totalErrors > 0) {
  echo "❌ Importación con errores\n";
  exit(1);
}
echo "✅ Importación completada\n";
echo "==========================================\n\n";
exit(0);
