<?php

/**
 * @file
 * Fix: Generar rendered_html faltante desde canvas_data para páginas PED.
 *
 * Anomalía A3: 12/13 páginas de PED (tenant 7) tienen canvas_data pero
 * no rendered_html. Esto causa JSON decode en cada page load.
 *
 * Ejecutar: lando drush php:script scripts/content-seed/fix-rendered-html.php
 * Alcance: Todas las páginas canvas de todos los tenants sin rendered_html.
 */

declare(strict_types=1);

$storage = \Drupal::entityTypeManager()->getStorage('page_content');
$sanitizer = \Drupal::service('ecosistema_jaraba_core.canvas_sanitization');

echo "\n==========================================\n";
echo "Fix rendered_html faltante (A3)\n";
echo "==========================================\n\n";

// Buscar TODAS las páginas canvas sin rendered_html (no solo PED).
$allIds = $storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('layout_mode', 'canvas')
  ->execute();

$fixed = 0;
$skipped = 0;
$warnings = [];

foreach ($storage->loadMultiple($allIds) as $page) {
  $rendered = $page->get('rendered_html')->value ?? '';
  $canvasRaw = $page->get('canvas_data')->value ?? '';

  if (strlen($rendered) > 100) {
    $skipped++;
    continue;
  }

  if (empty($canvasRaw) || strlen($canvasRaw) < 100) {
    $warnings[] = "ID {$page->id()} '{$page->label()}': sin canvas_data suficiente";
    continue;
  }

  $canvasData = json_decode($canvasRaw, TRUE);
  if (!is_array($canvasData) || empty($canvasData['html'])) {
    $warnings[] = "ID {$page->id()} '{$page->label()}': canvas_data sin key 'html'";
    continue;
  }

  $html = $sanitizer->sanitizePageBuilderHtml($canvasData['html']);
  $page->set('rendered_html', $html);
  $page->save();
  $fixed++;
  $tenantId = $page->get('tenant_id')->target_id ?? 'NULL';
  echo "  ✓ ID {$page->id()} [tenant {$tenantId}] '{$page->label()}': rendered_html = " . strlen($html) . " bytes\n";
}

echo "\n";
foreach ($warnings as $w) {
  echo "  ⚠ {$w}\n";
}

echo "\nResultado: {$fixed} corregidas, {$skipped} ya OK, " . count($warnings) . " warnings\n\n";
