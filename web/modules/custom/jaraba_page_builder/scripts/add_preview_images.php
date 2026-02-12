<?php

/**
 * Script para añadir preview_image a todos los templates que tengan PNG disponible.
 */

$templates = \Drupal::entityTypeManager()
    ->getStorage('page_template')
    ->loadMultiple();

$preview_dir = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/images/previews/';
$base_path = '/modules/custom/jaraba_page_builder/images/previews/';

$updated = 0;
$fixed_path = 0;
$not_found = [];

foreach ($templates as $template) {
    $id = $template->id();
    $current_preview = $template->getPreviewImage();

    // Convertir ID a nombre de archivo (underscores a hyphens)
    $filename = str_replace('_', '-', $id) . '.png';
    $full_path = $preview_dir . $filename;
    $relative_path = $base_path . $filename;

    // Verificar si el archivo existe
    if (file_exists($full_path)) {
        // Si no tiene preview_image o tiene path incorrecto
        if (empty($current_preview) || $current_preview !== $relative_path) {
            $template->set('preview_image', $relative_path);
            $template->save();

            if (empty($current_preview)) {
                echo "✅ Añadido: $id => $relative_path\n";
                $updated++;
            } else {
                echo "🔧 Corregido: $id => $relative_path (era: $current_preview)\n";
                $fixed_path++;
            }
        }
    } else {
        if (empty($current_preview)) {
            $not_found[] = $id;
        }
    }
}

echo "\n=== RESUMEN ===\n";
echo "Templates actualizados: $updated\n";
echo "Rutas corregidas: $fixed_path\n";
echo "Sin archivo PNG disponible: " . count($not_found) . "\n";

if ($not_found) {
    echo "\nTemplates sin PNG (necesitan generación manual):\n";
    foreach ($not_found as $nf) {
        echo "  - $nf\n";
    }
}
