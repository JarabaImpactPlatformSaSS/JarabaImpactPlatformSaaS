<?php

/**
 * @file
 * Script para instalar las tablas de entidades de Self-Discovery.
 * 
 * Ejecutar: drush scr web/install_self_discovery_entities.php
 */

$entity_type_manager = \Drupal::entityTypeManager();
$entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

$entity_types = ['life_wheel_assessment'];

foreach ($entity_types as $entity_type_id) {
    $definition = $entity_type_manager->getDefinition($entity_type_id, FALSE);
    if ($definition) {
        try {
            $entity_definition_update_manager->installEntityType($definition);
            echo "✅ Instalada: $entity_type_id\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== FALSE) {
                echo "⏭️ Ya existe: $entity_type_id\n";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "❌ No encontrada: $entity_type_id\n";
    }
}

echo "\n✅ Proceso completado.\n";
