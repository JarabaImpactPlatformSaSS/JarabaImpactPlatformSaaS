#!/bin/bash
# Apply entity definition updates

# Install ai_agent entity type
drush php-eval '$um = \Drupal::entityDefinitionUpdateManager(); $et = \Drupal::entityTypeManager()->getDefinition("ai_agent"); $um->installEntityType($et); echo "ai_agent installed\n";'

# Install feature entity type
drush php-eval '$um = \Drupal::entityDefinitionUpdateManager(); $et = \Drupal::entityTypeManager()->getDefinition("feature"); $um->installEntityType($et); echo "feature installed\n";'

# Install lms_lesson entity type
drush php-eval '$um = \Drupal::entityDefinitionUpdateManager(); $et = \Drupal::entityTypeManager()->getDefinition("lms_lesson"); $um->installEntityType($et); echo "lms_lesson installed\n";'

# Install tenant fields
drush php-eval '$um = \Drupal::entityDefinitionUpdateManager(); $defs = \Drupal::service("entity_field.manager")->getFieldStorageDefinitions("tenant"); foreach (["is_reverse_trial","downgrade_plan","trial_reminder_sent"] as $f) { if (isset($defs[$f])) { $um->installFieldStorageDefinition($f, "tenant", "ecosistema_jaraba_core", $defs[$f]); echo "$f installed\n"; }}'

# Handle candidate_profile fields  
drush php-eval '$um = \Drupal::entityDefinitionUpdateManager(); $defs = \Drupal::service("entity_field.manager")->getFieldStorageDefinitions("candidate_profile"); if (isset($defs["location"])) { $um->installFieldStorageDefinition("location", "candidate_profile", "jaraba_candidate", $defs["location"]); echo "location installed\n"; }'

echo "Entity updates complete!"
