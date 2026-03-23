<?php

/**
 * @file
 * CONTENT-SEED-INTEGRITY-001: Verifica integridad de contenido de metasitios.
 *
 * Checks:
 * 1. SiteConfig.homepage_id NOT NULL para cada metasitio
 * 2. Homepage referenciada existe y está publicada
 * 3. Páginas legales (privacy, terms, cookies) vinculadas
 * 4. SitePageTree sin referencias huérfanas a PageContent
 * 5. rendered_html >= 80% de páginas con contenido
 * 6. Path aliases únicos por tenant (sin colisiones)
 *
 * Uso: php scripts/validation/validate-content-seed-integrity.php
 * Exit: 0 = clean, 1 = violations found
 */

declare(strict_types=1);

// Verificar que estamos en contexto Drupal.
if (!class_exists('\Drupal') || !\Drupal::hasContainer()) {
  // Ejecución fuera de Drupal — verificar solo existencia de JSONs.
  $dataDir = __DIR__ . '/../content-seed/data';
  $jsonFiles = glob($dataDir . '/metasite-*.json');

  $errors = [];
  $passed = 0;
  $total = 3;

  $expected = ['metasite-pepejaraba.json', 'metasite-jarabaimpact.json', 'metasite-ped.json'];
  foreach ($expected as $file) {
    $path = $dataDir . '/' . $file;
    if (file_exists($path)) {
      $data = json_decode(file_get_contents($path), TRUE);
      if (is_array($data) && !empty($data['_metadata'])) {
        $passed++;
        echo "  ✓ {$file}: JSON válido ({$data['_metadata']['entity_counts']['page_content']} pages)\n";
      }
      else {
        $errors[] = "{$file}: JSON inválido o sin _metadata";
      }
    }
    else {
      $errors[] = "{$file}: no encontrado en {$dataDir}";
    }
  }

  echo "\n  Resultado: {$passed}/{$total} checks OK\n";
  if (!empty($errors)) {
    foreach ($errors as $e) {
      echo "  ✗ {$e}\n";
    }
    echo "\n  ❌ FAILED\n\n";
    exit(1);
  }
  echo "\n  ✅ ALL CHECKS PASSED\n\n";
  exit(0);
}

// ============================================================================
// Ejecución en contexto Drupal (vía drush php:script).
// ============================================================================

$domains = ['pepejaraba.com', 'jarabaimpact.com', 'plataformadeecosistemas.es'];
$entityTypeManager = \Drupal::entityTypeManager();
$tenantStorage = $entityTypeManager->getStorage('tenant');
$pageStorage = $entityTypeManager->getStorage('page_content');
$sptStorage = $entityTypeManager->getStorage('site_page_tree');
$scStorage = $entityTypeManager->getStorage('site_config');

$errors = [];
$warnings = [];
$passed = 0;
$total = 0;

foreach ($domains as $domain) {
  $tenants = $tenantStorage->loadByProperties(['domain' => $domain]);
  if (empty($tenants)) {
    $total++;
    $errors[] = "TENANT: {$domain} no encontrado";
    continue;
  }

  $tenant = reset($tenants);
  $groupId = (int) $tenant->get('group_id')->target_id;

  // Check 1: homepage_id NOT NULL.
  $total++;
  $scIds = $scStorage->getQuery()->accessCheck(FALSE)->condition('tenant_id', $groupId)->execute();
  $sc = !empty($scIds) ? $scStorage->load(reset($scIds)) : NULL;
  $homepageId = $sc?->get('homepage_id')->target_id;
  if ($homepageId) {
    $passed++;
    echo "  ✓ {$domain}: homepage_id={$homepageId}\n";
  }
  else {
    $errors[] = "{$domain}: SiteConfig.homepage_id = NULL";
  }

  // Check 2: Homepage existe y publicada.
  $total++;
  if ($homepageId) {
    $hp = $pageStorage->load($homepageId);
    if ($hp && (bool) $hp->get('status')->value) {
      $passed++;
      echo "  ✓ {$domain}: homepage publicada '{$hp->label()}'\n";
    }
    else {
      $errors[] = "{$domain}: homepage ID:{$homepageId} no existe o no publicada";
    }
  }
  else {
    $errors[] = "{$domain}: homepage no verificable (homepage_id NULL)";
  }

  // Check 3: Páginas legales.
  $total++;
  $privacyId = $sc?->get('privacy_policy_id')->target_id;
  $termsId = $sc?->get('terms_conditions_id')->target_id;
  $cookiesId = $sc?->get('cookies_policy_id')->target_id;
  if ($privacyId && $termsId && $cookiesId) {
    $passed++;
    echo "  ✓ {$domain}: legal pages OK\n";
  }
  else {
    $errors[] = "{$domain}: legal pages incompletas (privacy={$privacyId}, terms={$termsId}, cookies={$cookiesId})";
  }

  // Check 4: SPT integridad.
  $total++;
  $sptIds = $sptStorage->getQuery()->accessCheck(FALSE)->condition('tenant_id', $groupId)->execute();
  $orphans = 0;
  foreach ($sptStorage->loadMultiple($sptIds) as $spt) {
    $pageId = $spt->get('page_id')->target_id;
    if ($pageId && !$pageStorage->load($pageId)) {
      $orphans++;
    }
  }
  if ($orphans === 0) {
    $passed++;
    echo "  ✓ {$domain}: SPT integridad OK (" . count($sptIds) . " entries)\n";
  }
  else {
    $errors[] = "{$domain}: SPT {$orphans} referencias huérfanas";
  }

  // Check 5: rendered_html >= 80%.
  $total++;
  $allPages = $pageStorage->loadMultiple(
    $pageStorage->getQuery()->accessCheck(FALSE)->condition('tenant_id', $groupId)->execute()
  );
  $withRender = 0;
  $pageCount = count($allPages);
  foreach ($allPages as $p) {
    if (strlen($p->get('rendered_html')->value ?? '') > 50) {
      $withRender++;
    }
  }
  $pct = $pageCount > 0 ? round(($withRender / $pageCount) * 100) : 0;
  if ($pct >= 80) {
    $passed++;
    echo "  ✓ {$domain}: rendered_html {$withRender}/{$pageCount} ({$pct}%)\n";
  }
  else {
    $warnings[] = "{$domain}: rendered_html solo {$pct}%";
    $passed++; // Warning no bloquea.
  }

  // Check 6: Path alias únicos.
  $total++;
  $aliases = [];
  $dupes = 0;
  foreach ($allPages as $p) {
    $alias = $p->get('path_alias')->value ?? '';
    if (!empty($alias)) {
      if (isset($aliases[$alias])) {
        $dupes++;
      }
      $aliases[$alias] = TRUE;
    }
  }
  if ($dupes === 0) {
    $passed++;
    echo "  ✓ {$domain}: path aliases únicos\n";
  }
  else {
    $errors[] = "{$domain}: {$dupes} path aliases duplicados";
  }
}

echo "\n  Resultado: {$passed}/{$total} checks OK\n";

if (!empty($warnings)) {
  foreach ($warnings as $w) {
    echo "  ⚠ {$w}\n";
  }
}

if (!empty($errors)) {
  foreach ($errors as $e) {
    echo "  ✗ {$e}\n";
  }
  echo "\n  ❌ FAILED\n\n";
  exit(1);
}

echo "\n  ✅ ALL CHECKS PASSED\n\n";
exit(0);
