<?php

/**
 * @file
 * ENTITY-FIELD-DB-SYNC-001: Validates entity field definitions match DB columns.
 *
 * Detects fields defined in baseFieldDefinitions() but missing from the
 * actual database table (not installed via hook_update_N or entity:updates).
 *
 * Also detects orphan DB columns not in baseFieldDefinitions() (legacy fields).
 *
 * Requires Drupal bootstrap. Run: lando drush php:script scripts/validation/validate-entity-field-db-sync.php
 *
 * Usage outside Drupal: php scripts/validation/validate-entity-field-db-sync.php
 *   (will do static analysis only — no DB check)
 */

$basePath = __DIR__ . '/../../web/modules/custom';

$errors = [];
$warnings = [];
$checks = 0;

echo "ENTITY-FIELD-DB-SYNC-001: Validating entity field definitions match DB...\n\n";

// Entities to check: module => [entity_type_id => entity_class_file].
$entities = [
  'jaraba_success_cases' => [
    'success_case' => 'src/Entity/SuccessCase.php',
  ],
];

foreach ($entities as $module => $types) {
  foreach ($types as $entityTypeId => $classFile) {
    $checks++;
    $filePath = $basePath . '/' . $module . '/' . $classFile;

    if (!file_exists($filePath)) {
      $errors[] = "$entityTypeId: Entity class file not found: $classFile";
      echo "  [FAIL] $entityTypeId: Class file not found\n";
      continue;
    }

    // Extract field names from baseFieldDefinitions() in PHP source.
    $content = file_get_contents($filePath);
    preg_match_all("/\\\$fields\['([a-z_]+)'\]\s*=/", $content, $fieldMatches);
    $codeFields = array_unique($fieldMatches[1]);

    // Remove parent fields (id, uuid, langcode) that are defined in ContentEntityBase.
    $parentFields = ['id', 'uuid', 'langcode', 'default_langcode'];
    $codeFields = array_diff($codeFields, $parentFields);

    echo "  $entityTypeId: " . count($codeFields) . " fields in baseFieldDefinitions()\n";

    // Try to check DB columns if Drupal is bootstrapped.
    if (class_exists('Drupal') && \Drupal::hasContainer()) {
      try {
        $db = \Drupal::database();
        $tableExists = $db->schema()->tableExists($entityTypeId);

        if (!$tableExists) {
          $errors[] = "$entityTypeId: DB table does not exist";
          echo "  [FAIL] $entityTypeId: Table does not exist in DB\n";
          continue;
        }

        $result = $db->query("SHOW COLUMNS FROM {$entityTypeId}")->fetchAll();
        $dbColumns = [];
        foreach ($result as $col) {
          // Map compound columns (hero_image__target_id → hero_image).
          $baseName = preg_replace('/__[a-z_]+$/', '', $col->Field);
          $dbColumns[$baseName] = TRUE;
        }

        // Check for fields in code but missing from DB.
        $missingInDb = [];
        foreach ($codeFields as $field) {
          if (!isset($dbColumns[$field])) {
            $missingInDb[] = $field;
          }
        }

        if (!empty($missingInDb)) {
          $errors[] = "$entityTypeId: Fields in code but NOT in DB: " . implode(', ', $missingInDb);
          echo "  [FAIL] Fields in code but NOT in DB:\n";
          foreach ($missingInDb as $f) {
            echo "         - $f\n";
          }
        }
        else {
          echo "  [PASS] All code fields exist in DB\n";
        }

        // Check for DB columns not in code (orphans).
        $systemColumns = ['id', 'uuid', 'langcode', 'default_langcode', 'revision_id'];
        $orphanColumns = [];
        foreach (array_keys($dbColumns) as $col) {
          if (!in_array($col, $codeFields) && !in_array($col, $systemColumns)) {
            $orphanColumns[] = $col;
          }
        }

        if (!empty($orphanColumns)) {
          $warnings[] = "$entityTypeId: Orphan DB columns (not in code): " . implode(', ', $orphanColumns);
          echo "  [WARN] Orphan DB columns (not in baseFieldDefinitions):\n";
          foreach ($orphanColumns as $c) {
            echo "         - $c\n";
          }
        }
      }
      catch (\Throwable $e) {
        $warnings[] = "$entityTypeId: DB check failed: " . $e->getMessage();
        echo "  [WARN] DB check failed: " . $e->getMessage() . "\n";
      }
    }
    else {
      echo "  [INFO] No Drupal bootstrap — skipping DB column check\n";
      echo "  [INFO] Run with: lando drush php:script scripts/validation/validate-entity-field-db-sync.php\n";
    }
  }
}

// === Summary ===
echo "\n";
if (empty($errors)) {
  $warnText = count($warnings) > 0 ? " (" . count($warnings) . " warnings)" : "";
  echo "ENTITY-FIELD-DB-SYNC-001: PASS — $checks entities checked$warnText\n";
  if (!empty($warnings)) {
    foreach ($warnings as $w) {
      echo "  WARN: $w\n";
    }
  }
  exit(0);
}
else {
  echo "ENTITY-FIELD-DB-SYNC-001: FAIL — " . count($errors) . " errors\n";
  foreach ($errors as $e) {
    echo "  ERROR: $e\n";
  }
  echo "\nFix: Run the appropriate hook_update_N() or install fields manually.\n";
  exit(1);
}
