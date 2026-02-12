<?php
/**
 * Script to install Copilot entities.
 * Run with: drush scr web/install_copilot_entities.php
 */

$entityTypeManager = \Drupal::entityTypeManager();
$updateManager = \Drupal::entityDefinitionUpdateManager();

$entityTypes = ['copilot_conversation', 'copilot_message'];

foreach ($entityTypes as $id) {
    $definition = $entityTypeManager->getDefinition($id, FALSE);
    if ($definition) {
        try {
            $updateManager->installEntityType($definition);
            echo "✅ Installed: $id\n";
        } catch (\Exception $e) {
            // Check if already installed
            if (strpos($e->getMessage(), 'already exists') !== FALSE) {
                echo "ℹ️ Already exists: $id\n";
            } else {
                echo "❌ Error installing $id: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "⚠️ Definition not found: $id\n";
    }
}

echo "\nDone!\n";
