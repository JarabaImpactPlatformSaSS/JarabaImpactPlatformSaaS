<?php
/**
 * Drush script to install entity schemas.
 * Run with: drush scr install_entities.php
 */

$entity_type_manager = \Drupal::entityTypeManager();
$entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();

$entity_types = [
    'lms_course',
    'lms_enrollment',
    'job_posting',
    'job_application',
    'candidate_profile',
];

foreach ($entity_types as $entity_type_id) {
    try {
        $definition = $entity_type_manager->getDefinition($entity_type_id, FALSE);
        if ($definition) {
            $entity_definition_update_manager->installEntityType($definition);
            echo "Installed: $entity_type_id\n";
        } else {
            echo "Not found: $entity_type_id\n";
        }
    } catch (\Exception $e) {
        echo "Error $entity_type_id: " . $e->getMessage() . "\n";
    }
}

echo "Complete.\n";
