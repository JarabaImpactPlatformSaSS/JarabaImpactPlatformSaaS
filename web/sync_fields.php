<?php

/**
 * @file
 * Drush script para sincronizar campos de candidate_profile.
 */

$update_manager = \Drupal::entityDefinitionUpdateManager();
$field_manager = \Drupal::service('entity_field.manager');
$defs = $field_manager->getFieldStorageDefinitions('candidate_profile');

echo "=== Candidate Profile Field Sync ===\n\n";

$changes = $update_manager->getChangeList();
if (!isset($changes['candidate_profile'])) {
    echo "No hay cambios pendientes.\n";
    return;
}

echo "Cambios detectados:\n";
print_r($changes['candidate_profile']);

$kv = \Drupal::keyValue('entity.definitions.installed');
$field_definitions = $kv->get('candidate_profile.field_storage_definitions');

foreach (['country', 'salary_expectation'] as $field_name) {
    if (isset($defs[$field_name]) && isset($field_definitions[$field_name])) {
        $stored = $update_manager->getFieldStorageDefinition($field_name, 'candidate_profile');
        $new_def = $defs[$field_name];

        echo "\n$field_name:\n";
        echo "  Almacenado: " . $stored->getType() . "\n";
        echo "  En código: " . $new_def->getType() . "\n";

        if ($stored->getType() === $new_def->getType()) {
            // Actualizar directamente en key_value
            $field_definitions[$field_name] = $new_def;
            echo "  -> Sincronizando...\n";
        }
    }
}

$kv->set('candidate_profile.field_storage_definitions', $field_definitions);

// Limpiar cache de definiciones
\Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();
\Drupal::cache('discovery')->deleteAll();

$changes = $update_manager->getChangeList();
if (isset($changes['candidate_profile'])) {
    echo "\nAún pendientes:\n";
    print_r($changes['candidate_profile']);
} else {
    echo "\n¡Sincronización completada!\n";
}
