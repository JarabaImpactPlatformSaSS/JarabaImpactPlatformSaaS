<?php

/**
 * @file
 * Script Drush para corregir rutas de preview_image (añadir / inicial).
 *
 * Ejecutar con: lando drush scr modules/custom/jaraba_page_builder/scripts/fix_preview_paths.php
 */

$config_factory = \Drupal::configFactory();

echo "=== Corrigiendo rutas de preview_image ===\n\n";

// Obtener lista de todas las configs de plantillas.
$config_names = $config_factory->listAll('jaraba_page_builder.template.');

$fixed = 0;
$skipped = 0;

foreach ($config_names as $config_name) {
    $config = $config_factory->getEditable($config_name);
    $preview_image = $config->get('preview_image');

    if (empty($preview_image)) {
        $skipped++;
        continue;
    }

    // Verificar si ya tiene / inicial.
    if (strpos($preview_image, '/') === 0) {
        echo "SKIP: {$config_name} - Ya tiene / inicial\n";
        $skipped++;
        continue;
    }

    // Añadir / inicial.
    $new_path = '/' . $preview_image;
    $config->set('preview_image', $new_path);
    $config->save();

    $id = $config->get('id');
    echo "FIX: {$id} -> {$new_path}\n";
    $fixed++;
}

echo "\n=== Resumen ===\n";
echo "Corregidas: {$fixed}\n";
echo "Omitidas: {$skipped}\n";
echo "========================================\n";
