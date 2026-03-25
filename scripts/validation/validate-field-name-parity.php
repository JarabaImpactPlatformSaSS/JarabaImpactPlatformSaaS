<?php

/**
 * @file
 * Validator: FIELD-NAME-PARITY-001 — entity field names vs service usage.
 *
 * For each ContentEntity in jaraba_andalucia_ei, extracts field names from
 * baseFieldDefinitions(). Then scans Service/ for ->get('field') and
 * ->set('field', ...) calls, verifying referenced fields actually exist
 * in the target entity.
 *
 * Usage: php scripts/validation/validate-field-name-parity.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';
$entityDir = $moduleRoot . '/src/Entity';
$serviceDir = $moduleRoot . '/src/Service';

if (!is_dir($entityDir)) {
  echo "  ❌ FAIL: Entity directory not found: $entityDir\n";
  exit(1);
}

// Step 1: Parse all ContentEntity classes and extract fields + entity type ID.
$entities = []; // entity_type_id => ['fields' => [...], 'class' => ..., 'file' => ...]

$entityFiles = glob($entityDir . '/*.php');
foreach ($entityFiles as $entityFile) {
  $content = file_get_contents($entityFile);

  // Must be a ContentEntityType.
  if (strpos($content, '@ContentEntityType') === FALSE) {
    continue;
  }

  // Extract entity type ID from annotation.
  if (!preg_match('/\*\s+id\s*=\s*"(\w+)"/', $content, $idMatch)) {
    continue;
  }
  $entityTypeId = $idMatch[1];

  // Extract class name.
  if (!preg_match('/class\s+(\w+)\s+/', $content, $classMatch)) {
    continue;
  }
  $className = $classMatch[1];

  // Extract field names from baseFieldDefinitions().
  $fields = [];
  // Pattern: $fields['field_name'] =
  if (preg_match_all("/\\\$fields\['(\w+)'\]\s*=/", $content, $fieldMatches)) {
    $fields = array_unique($fieldMatches[1]);
  }

  // Also include common inherited fields from ContentEntityBase.
  $inheritedFields = ['id', 'uuid', 'langcode'];
  $fields = array_unique(array_merge($fields, $inheritedFields));

  $entities[$entityTypeId] = [
    'fields' => $fields,
    'class' => $className,
    'file' => basename($entityFile),
  ];
}

if (empty($entities)) {
  echo "  ❌ FAIL: No ContentEntity classes found in $entityDir\n";
  exit(1);
}

// Step 2: Scan Service/ files for ->get('field') and ->set('field', ...) calls.
// Try to associate them with entities via getStorage('entity_type_id') calls.

if (!is_dir($serviceDir)) {
  echo "  ❌ FAIL: Service directory not found: $serviceDir\n";
  exit(1);
}

$serviceFiles = glob($serviceDir . '/*.php');
$totalFieldRefs = 0;
$mismatches = [];

foreach ($serviceFiles as $serviceFile) {
  $content = file_get_contents($serviceFile);
  $lines = explode("\n", $content);
  $serviceBasename = basename($serviceFile);

  // Find which entity types this service works with.
  // Pattern: getStorage('entity_type_id') or ::load( or ->load(
  $referencedEntityTypes = [];
  if (preg_match_all("/getStorage\(\s*'(\w+)'\s*\)/", $content, $storageMatches)) {
    foreach ($storageMatches[1] as $eid) {
      if (isset($entities[$eid])) {
        $referencedEntityTypes[] = $eid;
      }
    }
  }

  // Also check for class name references (e.g., EntregableFormativoEi::load).
  foreach ($entities as $eid => $einfo) {
    if (strpos($content, $einfo['class'] . '::') !== FALSE) {
      $referencedEntityTypes[] = $eid;
    }
  }

  $referencedEntityTypes = array_unique($referencedEntityTypes);

  if (empty($referencedEntityTypes)) {
    continue;
  }

  // Collect all ->get('field') and ->set('field', ...) calls.
  $fieldRefs = [];
  foreach ($lines as $lineNum => $line) {
    // ->get('field_name') pattern.
    if (preg_match_all("/->get\(\s*'(\w+)'\s*\)/", $line, $getMatches)) {
      foreach ($getMatches[1] as $fieldName) {
        $fieldRefs[] = ['field' => $fieldName, 'line' => $lineNum + 1, 'op' => 'get'];
      }
    }
    // ->set('field_name', ...) pattern.
    if (preg_match_all("/->set\(\s*'(\w+)'\s*,/", $line, $setMatches)) {
      foreach ($setMatches[1] as $fieldName) {
        $fieldRefs[] = ['field' => $fieldName, 'line' => $lineNum + 1, 'op' => 'set'];
      }
    }
  }

  if (empty($fieldRefs)) {
    continue;
  }

  // Build a combined field set from all referenced entities.
  $allValidFields = [];
  foreach ($referencedEntityTypes as $eid) {
    foreach ($entities[$eid]['fields'] as $f) {
      $allValidFields[$f] = $eid;
    }
  }

  // Skip common non-field get/set patterns (Drupal internal).
  $skipFields = [
    'value', 'target_id', 'entity', 'format', 'uri', 'title',
    'alias', 'source', 'weight', 'status', 'name', 'type',
    'description', 'label', 'path', 'plugin', 'settings',
    'configuration', 'data', 'context', 'options', 'mode',
  ];

  foreach ($fieldRefs as $ref) {
    $totalFieldRefs++;

    // Skip Drupal internals.
    if (in_array($ref['field'], $skipFields, TRUE)) {
      continue;
    }

    // Check if the field exists in any referenced entity.
    if (!isset($allValidFields[$ref['field']])) {
      // Verify it's not a field in ANY entity (could be a different entity via variable).
      $foundAnywhere = FALSE;
      foreach ($entities as $eid => $einfo) {
        if (in_array($ref['field'], $einfo['fields'], TRUE)) {
          $foundAnywhere = TRUE;
          break;
        }
      }

      if (!$foundAnywhere) {
        $mismatches[] = [
          'service' => $serviceBasename,
          'line' => $ref['line'],
          'field' => $ref['field'],
          'op' => $ref['op'],
          'entities' => implode(', ', $referencedEntityTypes),
        ];
      }
    }
  }
}

// Step 3: Report results.
$entityCount = count($entities);
$serviceCount = count($serviceFiles);

if (empty($mismatches)) {
  $passes[] = "CHECK: {$entityCount} entities, {$serviceCount} services scanned — all field references match";
} else {
  foreach ($mismatches as $mm) {
    $errors[] = "FAIL: {$mm['service']}:{$mm['line']} — ->{$mm['op']}('{$mm['field']}') not found in entities [{$mm['entities']}]";
  }
}

// Also report entity coverage.
$passes[] = "CHECK: {$entityCount} ContentEntity classes parsed in jaraba_andalucia_ei";

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== FIELD-NAME-PARITY-001 (jaraba_andalucia_ei) ===\n\n";
foreach ($passes as $msg) {
  echo "  ✅ $msg\n";
}
foreach ($errors as $msg) {
  echo "  ❌ $msg\n";
}
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(empty($errors) ? 0 : 1);
