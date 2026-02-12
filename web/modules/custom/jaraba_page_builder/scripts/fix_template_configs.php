<?php

/**
 * @file
 * Script Drush para diagnosticar y limpiar configs de plantillas corruptas.
 *
 * Ejecutar con: lando drush scr modules/custom/jaraba_page_builder/scripts/fix_template_configs.php
 */

$config_factory = \Drupal::configFactory();
$entity_storage = \Drupal::entityTypeManager()->getStorage('page_template');

echo "=== Diagnóstico de Configs de Plantillas ===\n\n";

// Obtener lista de todas las configs de plantillas.
$config_names = $config_factory->listAll('jaraba_page_builder.template.');

echo "Total configs encontradas: " . count($config_names) . "\n\n";

$corrupted = [];
$valid = [];

foreach ($config_names as $config_name) {
    $config = $config_factory->get($config_name);
    $id = $config->get('id');
    $label = $config->get('label');

    if (empty($id)) {
        echo "CORRUPTA: {$config_name} - Sin ID\n";
        $corrupted[] = $config_name;
    } else {
        $valid[] = $config_name;
    }
}

echo "\n=== Resumen ===\n";
echo "Válidas: " . count($valid) . "\n";
echo "Corruptas: " . count($corrupted) . "\n";

if (!empty($corrupted)) {
    echo "\n=== Eliminando configs corruptas ===\n";
    foreach ($corrupted as $config_name) {
        $config_factory->getEditable($config_name)->delete();
        echo "ELIMINADA: {$config_name}\n";
    }
}

echo "\n=== Proceso completado ===\n";
