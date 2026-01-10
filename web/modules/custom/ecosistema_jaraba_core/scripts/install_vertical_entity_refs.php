<?php
/**
 * @file
 * Script para instalar las nuevas definiciones de campo entity_reference.
 *
 * Uso: lando drush php:script install_vertical_entity_refs.php
 */

$edm = \Drupal::entityDefinitionUpdateManager();

// Instalar nuevas definiciones (entity_reference)
$new_features_def = \Drupal\Core\Field\BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Features Habilitadas'))
    ->setDescription(t('Funcionalidades activas para esta vertical.'))
    ->setCardinality(\Drupal\Core\Field\BaseFieldDefinition::CARDINALITY_UNLIMITED)
    ->setSetting('target_type', 'feature')
    ->setSetting('handler', 'default');

$edm->installFieldStorageDefinition(
    'enabled_features',
    'vertical',
    'ecosistema_jaraba_core',
    $new_features_def
);
echo "enabled_features installed\n";

$new_agents_def = \Drupal\Core\Field\BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Agentes IA'))
    ->setDescription(t('Agentes de IA disponibles para esta vertical.'))
    ->setCardinality(\Drupal\Core\Field\BaseFieldDefinition::CARDINALITY_UNLIMITED)
    ->setSetting('target_type', 'ai_agent')
    ->setSetting('handler', 'default');

$edm->installFieldStorageDefinition(
    'ai_agents',
    'vertical',
    'ecosistema_jaraba_core',
    $new_agents_def
);
echo "ai_agents installed\n";

// Limpiar caché
\Drupal::cache('discovery')->deleteAll();
\Drupal::service('plugin.manager.field.field_type')->clearCachedDefinitions();

echo "Done! Fields installed.\n";
