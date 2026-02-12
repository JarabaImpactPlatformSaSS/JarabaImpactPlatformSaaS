<?php

/**
 * @file
 * Script para identificar plantillas sin preview_image.
 */

$templates = \Drupal::entityTypeManager()
    ->getStorage('page_template')
    ->loadMultiple();

echo "=== Plantillas sin preview_image o con rutas incorrectas ===\n\n";

$issues = [];
foreach ($templates as $template) {
    $preview = $template->get('preview_image');
    if (empty($preview) || strpos($preview, '/') !== 0) {
        $issues[$template->id()] = $preview ?: 'VACÍO';
    }
}

if (empty($issues)) {
    echo "✅ Todas las " . count($templates) . " plantillas tienen preview_image configurado correctamente.\n";
} else {
    echo "❌ " . count($issues) . " plantillas tienen problemas:\n\n";
    foreach ($issues as $id => $value) {
        echo "  - {$id}: {$value}\n";
    }
}

echo "\n=== Total plantillas: " . count($templates) . " ===\n";
