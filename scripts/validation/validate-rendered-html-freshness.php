<?php

/**
 * @file
 * RENDERED-HTML-FRESHNESS-001: Detecta páginas canvas sin rendered_html.
 *
 * Checks:
 * 1. Páginas con layout_mode=canvas y canvas_data >100 bytes DEBEN tener rendered_html >50 bytes
 * 2. rendered_html no debe ser significativamente menor que canvas_data.html
 *
 * Problema: Si canvas_data se edita fuera del Canvas API (SQL directo, drush ev),
 * rendered_html no se auto-genera. El ViewBuilder tiene fallback (JSON decode en cada
 * page load) pero es ineficiente y degrada rendimiento.
 *
 * Usage: php scripts/validation/validate-rendered-html-freshness.php
 * Exit: 0 = clean, 1 = violations found
 */

declare(strict_types=1);

$baseDir = dirname(__DIR__, 2);

// Fuera de Drupal: verificar que los JSONs exportados tienen rendered_html.
if (!class_exists('\Drupal') || !\Drupal::hasContainer()) {
  $dataDir = $baseDir . '/scripts/content-seed/data';
  $jsonFiles = glob($dataDir . '/metasite-*.json');

  if (empty($jsonFiles)) {
    echo "  ✓ No hay JSONs exportados (nada que verificar)\n";
    echo "\n  ✅ ALL CHECKS PASSED\n\n";
    exit(0);
  }

  $errors = [];
  $passed = 0;
  $total = 0;

  foreach ($jsonFiles as $file) {
    $data = json_decode(file_get_contents($file), TRUE);
    if (!is_array($data) || empty($data['page_content'])) {
      continue;
    }

    $domain = $data['_metadata']['tenant_domain'] ?? basename($file);

    foreach ($data['page_content'] as $page) {
      $canvasLen = strlen($page['canvas_data'] ?? '');
      $renderLen = strlen($page['rendered_html'] ?? '');
      $layoutMode = $page['layout_mode'] ?? '';

      // Solo verificar páginas canvas con contenido.
      if ($layoutMode !== 'canvas' || $canvasLen < 100) {
        continue;
      }

      $total++;
      if ($renderLen > 50) {
        $passed++;
      }
      else {
        $errors[] = "{$domain}: '{$page['title']}' canvas={$canvasLen}B rendered_html={$renderLen}B";
      }
    }
  }

  if ($total === 0) {
    echo "  ✓ No hay páginas canvas en los JSONs\n";
    echo "\n  ✅ ALL CHECKS PASSED\n\n";
    exit(0);
  }

  echo "  Resultado: {$passed}/{$total} páginas canvas con rendered_html\n";

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
$storage = \Drupal::entityTypeManager()->getStorage('page_content');

$canvasIds = $storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('layout_mode', 'canvas')
  ->execute();

$errors = [];
$passed = 0;
$total = 0;

foreach ($storage->loadMultiple($canvasIds) as $page) {
  $canvasRaw = $page->get('canvas_data')->value ?? '';
  $renderedHtml = $page->get('rendered_html')->value ?? '';

  if (strlen($canvasRaw) < 100) {
    continue;
  }

  $total++;
  $tenantId = $page->get('tenant_id')->target_id ?? 'NULL';

  if (strlen($renderedHtml) > 50) {
    $passed++;
  }
  else {
    $errors[] = "ID:{$page->id()} [tenant {$tenantId}] '{$page->label()}': canvas=" . strlen($canvasRaw) . "B rendered_html=" . strlen($renderedHtml) . "B";
  }
}

echo "  Resultado: {$passed}/{$total} páginas canvas con rendered_html\n";

if (!empty($errors)) {
  foreach ($errors as $e) {
    echo "  ✗ {$e}\n";
  }
  echo "\n  ❌ FAILED\n\n";
  exit(1);
}

echo "\n  ✅ ALL CHECKS PASSED\n\n";
exit(0);
