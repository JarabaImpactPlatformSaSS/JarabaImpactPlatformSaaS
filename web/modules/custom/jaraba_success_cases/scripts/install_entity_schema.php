<?php

/**
 * @file
 * Creates the success_case entity table if missing.
 *
 * Run: drush scr web/modules/custom/jaraba_success_cases/scripts/install_entity_schema.php
 */

$entityDefinitionUpdateManager = \Drupal::entityDefinitionUpdateManager();
$entityTypeManager = \Drupal::entityTypeManager();

$entityType = $entityTypeManager->getDefinition('success_case');

try {
    $entityDefinitionUpdateManager->installEntityType($entityType);
    echo "SUCCESS: Entity type 'success_case' schema installed.\n";
} catch (\Exception $e) {
    echo "INFO: " . $e->getMessage() . "\n";
    echo "Table may already exist. Trying applyUpdates...\n";
    try {
        $entityDefinitionUpdateManager->applyUpdates();
        echo "SUCCESS: Entity updates applied.\n";
    } catch (\Exception $e2) {
        echo "ERROR: " . $e2->getMessage() . "\n";
    }
}
