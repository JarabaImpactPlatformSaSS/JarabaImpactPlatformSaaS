<?php

/**
 * Script para añadir preview_image a los archivos YAML de configuración.
 * 
 * Este script modifica los archivos de config/install para que preview_image
 * persista después de config imports.
 */

$config_dir = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/config/install/';
$preview_dir = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/images/previews/';
$base_path = '/modules/custom/jaraba_page_builder/images/previews/';

$updated = 0;
$already_has = 0;
$no_png = [];

// Obtener todos los archivos de template YAML
$yaml_files = glob($config_dir . 'jaraba_page_builder.template.*.yml');

foreach ($yaml_files as $yaml_file) {
    $filename = basename($yaml_file);

    // Extraer el ID del template (ej: jaraba_page_builder.template.accordion_content.yml -> accordion_content)
    preg_match('/jaraba_page_builder\.template\.(.+)\.yml/', $filename, $matches);
    if (empty($matches[1]))
        continue;

    $template_id = $matches[1];

    // Leer contenido YAML
    $content = file_get_contents($yaml_file);

    // Verificar si ya tiene preview_image
    if (strpos($content, 'preview_image:') !== false) {
        $already_has++;
        continue;
    }

    // Buscar archivo PNG correspondiente
    $png_filename = str_replace('_', '-', $template_id) . '.png';
    $png_path = $preview_dir . $png_filename;

    if (!file_exists($png_path)) {
        $no_png[] = $template_id;
        continue;
    }

    // Añadir preview_image al final del archivo (antes de la última línea vacía)
    $relative_path = $base_path . $png_filename;

    // Limpiar trailing whitespace y añadir preview_image
    $content = rtrim($content);
    $content .= "\npreview_image: '" . $relative_path . "'\n";

    file_put_contents($yaml_file, $content);
    echo "✅ $template_id => $relative_path\n";
    $updated++;
}

echo "\n=== RESUMEN ===\n";
echo "YAMLs actualizados: $updated\n";
echo "Ya tenían preview_image: $already_has\n";
echo "Sin PNG disponible: " . count($no_png) . "\n";

if ($no_png) {
    echo "\nTemplates sin PNG:\n";
    foreach ($no_png as $t) {
        echo "  - $t\n";
    }
}
