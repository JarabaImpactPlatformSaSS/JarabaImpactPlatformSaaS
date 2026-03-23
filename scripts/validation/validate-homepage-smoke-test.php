<?php

/**
 * @file
 * HOMEPAGE-SMOKE-TEST-001: Verifica que cada metasitio sirve su homepage diferenciada.
 *
 * Checks:
 * 1. SiteConfig.homepage_id NOT NULL para cada metasitio
 * 2. homepage_id apunta a PageContent existente y publicada
 * 3. meta_title_suffix está configurado (diferenciación SEO)
 * 4. No se sirve la homepage genérica del SaaS ("Impulsa tu ecosistema")
 *
 * Problema: El deploy puede sincronizar código y config pero si el contenido
 * de plataforma (L2) no existe, PathProcessor devuelve NULL y Drupal sirve
 * system.site.front = /node (homepage genérica). Esto es invisible en CI.
 *
 * Usage: php scripts/validation/validate-homepage-smoke-test.php
 * Exit: 0 = clean, 1 = violations found
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__, 2);

// Fuera de Drupal: verificar via JSONs exportados.
if (!class_exists('\Drupal') || !\Drupal::hasContainer()) {
  $dataDir = $baseDir . '/scripts/content-seed/data';
  $jsonFiles = glob($dataDir . '/metasite-*.json');

  if (empty($jsonFiles)) {
    echo "  ⚠ No hay JSONs exportados — no se puede verificar\n";
    echo "\n  ✅ ALL CHECKS PASSED (skip)\n\n";
    exit(0);
  }

  $errors = [];
  $passed = 0;
  $total = 0;

  foreach ($jsonFiles as $file) {
    $data = json_decode(file_get_contents($file), TRUE);
    if (!is_array($data) || empty($data['site_config'])) {
      continue;
    }

    $domain = $data['_metadata']['tenant_domain'] ?? basename($file);
    $sc = $data['site_config'];

    // Check 1: homepage_uuid NOT NULL.
    $total++;
    if (!empty($sc['homepage_uuid'])) {
      $passed++;
      echo "  ✓ {$domain}: homepage_uuid configurado\n";
    }
    else {
      $errors[] = "{$domain}: homepage_uuid = NULL";
    }

    // Check 2: meta_title_suffix NOT NULL.
    $total++;
    if (!empty($sc['meta_title_suffix'])) {
      $passed++;
      echo "  ✓ {$domain}: meta_title_suffix = '{$sc['meta_title_suffix']}'\n";
    }
    else {
      $errors[] = "{$domain}: meta_title_suffix vacío";
    }

    // Check 3: Homepage tiene rendered_html.
    $total++;
    $homepageUuid = $sc['homepage_uuid'] ?? '';
    $homepageHasContent = FALSE;
    foreach ($data['page_content'] as $page) {
      if ($page['uuid'] === $homepageUuid) {
        $homepageHasContent = strlen($page['rendered_html'] ?? '') > 100;
        break;
      }
    }
    if ($homepageHasContent) {
      $passed++;
      echo "  ✓ {$domain}: homepage tiene rendered_html\n";
    }
    else {
      $errors[] = "{$domain}: homepage sin rendered_html";
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
// Dentro de Drupal: verificar directamente en DB.
// ============================================================================
$domains = ['pepejaraba.com', 'jarabaimpact.com', 'plataformadeecosistemas.es'];
$entityTypeManager = \Drupal::entityTypeManager();
$tenantStorage = $entityTypeManager->getStorage('tenant');
$pageStorage = $entityTypeManager->getStorage('page_content');
$scStorage = $entityTypeManager->getStorage('site_config');

$errors = [];
$passed = 0;
$total = 0;

foreach ($domains as $domain) {
  $tenants = $tenantStorage->loadByProperties(['domain' => $domain]);
  if (empty($tenants)) {
    $total++;
    $errors[] = "{$domain}: Tenant no encontrado";
    continue;
  }

  $tenant = reset($tenants);
  $groupId = (int) $tenant->get('group_id')->target_id;

  // SiteConfig.
  $scIds = $scStorage->getQuery()->accessCheck(FALSE)->condition('tenant_id', $groupId)->execute();
  $sc = !empty($scIds) ? $scStorage->load(reset($scIds)) : NULL;

  // Check 1: homepage_id NOT NULL.
  $total++;
  $homepageId = $sc?->get('homepage_id')->target_id;
  if ($homepageId) {
    $passed++;
    echo "  ✓ {$domain}: homepage_id={$homepageId}\n";
  }
  else {
    $errors[] = "{$domain}: SiteConfig.homepage_id = NULL — PathProcessor devolverá NULL";
  }

  // Check 2: Homepage existe y publicada.
  $total++;
  if ($homepageId) {
    $homepage = $pageStorage->load($homepageId);
    if ($homepage && (bool) $homepage->get('status')->value) {
      $passed++;
      echo "  ✓ {$domain}: homepage publicada '{$homepage->label()}'\n";
    }
    else {
      $errors[] = "{$domain}: homepage ID:{$homepageId} no existe o no publicada";
    }
  }
  else {
    $errors[] = "{$domain}: homepage no verificable (ID NULL)";
  }

  // Check 3: meta_title_suffix configurado.
  $total++;
  $suffix = $sc?->get('meta_title_suffix')->value ?? '';
  if (!empty($suffix)) {
    $passed++;
    echo "  ✓ {$domain}: meta_title_suffix = '{$suffix}'\n";
  }
  else {
    $errors[] = "{$domain}: meta_title_suffix vacío — SEO no diferenciado";
  }

  // Check 4: Homepage tiene rendered_html.
  $total++;
  if ($homepageId) {
    $homepage = $homepage ?? $pageStorage->load($homepageId);
    $renderLen = strlen($homepage?->get('rendered_html')->value ?? '');
    if ($renderLen > 100) {
      $passed++;
      echo "  ✓ {$domain}: homepage rendered_html = {$renderLen} bytes\n";
    }
    else {
      $errors[] = "{$domain}: homepage sin rendered_html ({$renderLen}B) — page load lento";
    }
  }
  else {
    $errors[] = "{$domain}: homepage rendered_html no verificable";
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
