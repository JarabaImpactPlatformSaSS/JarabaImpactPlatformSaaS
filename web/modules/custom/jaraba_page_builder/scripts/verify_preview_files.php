<?php

/**
 * @file
 * Script para verificar que los archivos de preview_image existen físicamente.
 */

$templates = \Drupal::entityTypeManager()
    ->getStorage('page_template')
    ->loadMultiple();

$module_path = \Drupal::service('extension.list.module')->getPath('jaraba_page_builder');
$drupal_root = DRUPAL_ROOT;

echo "=== Verificando existencia de archivos preview_image ===\n\n";

$existing = 0;
$missing = [];

foreach ($templates as $template) {
    $preview = $template->get('preview_image');
    if (!empty($preview)) {
        // Construir ruta completa desde la raíz del site.
        $full_path = $drupal_root . $preview;

        if (file_exists($full_path)) {
            $existing++;
        } else {
            $missing[$template->id()] = [
                'label' => $template->label(),
                'preview' => $preview,
                'path' => $full_path,
            ];
        }
    }
}

echo "✅ Existentes: {$existing}\n";
echo "❌ Faltantes: " . count($missing) . "\n\n";

if (!empty($missing)) {
    echo "=== Archivos faltantes ===\n";
    foreach ($missing as $id => $info) {
        echo "- {$info['label']} ({$id})\n";
        echo "  Ruta: {$info['preview']}\n\n";
    }
}
