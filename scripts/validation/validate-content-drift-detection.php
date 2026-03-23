<?php

/**
 * @file
 * CONTENT-DRIFT-DETECTION-001: Detecta divergencia entre JSONs exportados y DB.
 *
 * Checks:
 * 1. JSONs exportados existen en scripts/content-seed/data/
 * 2. El conteo de PageContent por metasitio en JSON coincide con DB
 * 3. Los UUIDs del JSON existen en DB
 * 4. Si DB tiene más entities que JSON, señala drift (contenido creado en producción)
 *
 * Problema: Post-lanzamiento, contenido se edita en producción directamente.
 * Los JSONs quedan desactualizados. Si alguien ejecuta import sin re-exportar,
 * sobreescribiría cambios de producción.
 *
 * Usage: php scripts/validation/validate-content-drift-detection.php
 * Exit: 0 = clean, 1 = violations found (hard drift), warn for soft drift
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__, 2);
$dataDir = $baseDir . '/scripts/content-seed/data';

// ============================================================================
// Modo sin Drupal: solo verificar existencia y estructura de JSONs.
// ============================================================================
if (!class_exists('\Drupal') || !\Drupal::hasContainer()) {
  $expected = ['metasite-pepejaraba.json', 'metasite-jarabaimpact.json', 'metasite-ped.json'];
  $errors = [];
  $warnings = [];
  $passed = 0;
  $total = 0;

  foreach ($expected as $file) {
    $total++;
    $path = $dataDir . '/' . $file;
    if (!file_exists($path)) {
      $errors[] = "{$file}: no encontrado";
      continue;
    }

    $data = json_decode(file_get_contents($path), TRUE);
    if (!is_array($data) || empty($data['_metadata'])) {
      $errors[] = "{$file}: JSON inválido";
      continue;
    }

    $meta = $data['_metadata'];
    $exportedAt = $meta['exported_at'] ?? 'unknown';
    $pageCount = $meta['entity_counts']['page_content'] ?? 0;

    // Check: exported_at no demasiado antiguo (>30 días = warning).
    $exportTs = strtotime($exportedAt);
    $daysSinceExport = $exportTs ? (int) ((time() - $exportTs) / 86400) : 999;

    if ($daysSinceExport > 30) {
      $warnings[] = "{$file}: exportado hace {$daysSinceExport} días ({$exportedAt}) — posible drift";
    }

    $passed++;
    echo "  ✓ {$file}: {$pageCount} pages, exportado {$exportedAt}\n";
  }

  echo "\n  Resultado: {$passed}/{$total} JSONs OK\n";

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
}

// ============================================================================
// Modo Drupal: comparar JSONs contra DB.
// ============================================================================
$entityTypeManager = \Drupal::entityTypeManager();
$tenantStorage = $entityTypeManager->getStorage('tenant');
$pageStorage = $entityTypeManager->getStorage('page_content');

$jsonFiles = glob($dataDir . '/metasite-*.json');
if (empty($jsonFiles)) {
  echo "  ⚠ No hay JSONs exportados\n";
  echo "\n  ✅ ALL CHECKS PASSED (skip)\n\n";
  exit(0);
}

$errors = [];
$warnings = [];
$passed = 0;
$total = 0;

foreach ($jsonFiles as $file) {
  $data = json_decode(file_get_contents($file), TRUE);
  if (!is_array($data) || empty($data['_metadata'])) {
    continue;
  }

  $domain = $data['_metadata']['tenant_domain'];
  $jsonPageCount = $data['_metadata']['entity_counts']['page_content'];
  $exportedAt = $data['_metadata']['exported_at'] ?? 'unknown';

  // Resolver tenant en DB.
  $tenants = $tenantStorage->loadByProperties(['domain' => $domain]);
  if (empty($tenants)) {
    $total++;
    $errors[] = "{$domain}: Tenant no encontrado en DB";
    continue;
  }

  $tenant = reset($tenants);
  $groupId = (int) $tenant->get('group_id')->target_id;

  // Check 1: Conteo coincide.
  $total++;
  $dbPageCount = (int) $pageStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->count()
    ->execute();

  if ($dbPageCount === $jsonPageCount) {
    $passed++;
    echo "  ✓ {$domain}: {$dbPageCount} pages (JSON={$jsonPageCount}, DB={$dbPageCount})\n";
  }
  elseif ($dbPageCount > $jsonPageCount) {
    $passed++; // Soft drift, no bloquea.
    $diff = $dbPageCount - $jsonPageCount;
    $warnings[] = "{$domain}: DB tiene {$diff} página(s) más que JSON ({$dbPageCount} vs {$jsonPageCount}) — contenido creado post-export";
    echo "  ⚠ {$domain}: +{$diff} páginas en DB vs JSON (drift detectado)\n";
  }
  else {
    $diff = $jsonPageCount - $dbPageCount;
    $errors[] = "{$domain}: JSON tiene {$diff} página(s) más que DB ({$jsonPageCount} vs {$dbPageCount}) — posible eliminación en DB";
  }

  // Check 2: UUIDs del JSON existen en DB.
  $total++;
  $missingUuids = 0;
  foreach ($data['page_content'] as $page) {
    $exists = $pageStorage->loadByProperties(['uuid' => $page['uuid']]);
    if (empty($exists)) {
      $missingUuids++;
    }
  }

  if ($missingUuids === 0) {
    $passed++;
    echo "  ✓ {$domain}: todos los UUIDs del JSON existen en DB\n";
  }
  else {
    $warnings[] = "{$domain}: {$missingUuids} UUID(s) del JSON no existen en DB";
    echo "  ⚠ {$domain}: {$missingUuids} UUID(s) no encontrados\n";
  }

  // Check 3: Antigüedad del export.
  $total++;
  $exportTs = strtotime($exportedAt);
  $daysSinceExport = $exportTs ? (int) ((time() - $exportTs) / 86400) : 999;

  if ($daysSinceExport <= 30) {
    $passed++;
    echo "  ✓ {$domain}: export reciente ({$daysSinceExport} días)\n";
  }
  else {
    $warnings[] = "{$domain}: export antiguo ({$daysSinceExport} días) — re-exportar recomendado";
    $passed++; // Warning, no bloquea.
    echo "  ⚠ {$domain}: export hace {$daysSinceExport} días\n";
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
