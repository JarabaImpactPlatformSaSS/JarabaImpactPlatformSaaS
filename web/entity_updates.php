<?php

/**
 * @file
 * Script para aplicar actualizaciones de definición de entidades.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Bootstrap Drupal.
$autoloader = require_once 'autoload.php';

$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();

// Get the entity definition update manager
$update_manager = \Drupal::entityDefinitionUpdateManager();

// Get list of changes
$change_list = $update_manager->getChangeList();

echo "=== Entity Definition Updates ===\n\n";

if (empty($change_list)) {
    echo "No pending entity definition updates.\n";
    exit(0);
}

foreach ($change_list as $entity_type_id => $changes) {
    echo "Entity Type: $entity_type_id\n";

    // Install new entity type
    if (!empty($changes['entity_type'])) {
        try {
            $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
            $update_manager->installEntityType($entity_type);
            echo "  ✓ Entity type installed\n";
        } catch (\Exception $e) {
            echo "  ✗ Error installing entity type: " . $e->getMessage() . "\n";
        }
    }

    // Install/update fields
    if (!empty($changes['field_storage_definitions'])) {
        foreach ($changes['field_storage_definitions'] as $field_name => $change) {
            try {
                $field_storage = \Drupal::service('entity_field.manager')
                    ->getFieldStorageDefinitions($entity_type_id)[$field_name] ?? NULL;

                if ($field_storage) {
                    if ($change === 1) { // Install
                        $update_manager->installFieldStorageDefinition(
                            $field_name,
                            $entity_type_id,
                            $entity_type_id,
                            $field_storage
                        );
                        echo "  ✓ Field '$field_name' installed\n";
                    } elseif ($change === 2) { // Update
                        $update_manager->updateFieldStorageDefinition($field_storage);
                        echo "  ✓ Field '$field_name' updated\n";
                    }
                } elseif ($change === 3) { // Uninstall
                    // Skip uninstall for now
                    echo "  - Field '$field_name' needs uninstall (skipped)\n";
                }
            } catch (\Exception $e) {
                echo "  ✗ Error with field '$field_name': " . $e->getMessage() . "\n";
            }
        }
    }

    echo "\n";
}

echo "=== Updates Complete ===\n";
