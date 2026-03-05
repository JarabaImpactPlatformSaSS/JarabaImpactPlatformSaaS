#!/usr/bin/env php
<?php

/**
 * @file
 * Generates hook_update_N() for entity types missing installEntityType().
 *
 * Usage:
 *   php scripts/maintenance/generate-install-hooks.php
 *   php scripts/maintenance/generate-install-hooks.php --dry-run
 *   php scripts/maintenance/generate-install-hooks.php --module=jaraba_legal
 *
 * Reads output from validate-entity-integrity.php and generates the required
 * hook_update_N() or hook_install() code for each affected module.
 *
 * Rules applied:
 *   - UPDATE-HOOK-REQUIRED-001: Every entity needs installEntityType()
 *   - UPDATE-HOOK-CATCH-001: try-catch uses \Throwable (not \Exception)
 *   - UPDATE-FIELD-DEF-001: setName() + setTargetEntityTypeId() on field defs
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);

// Parse args.
$dryRun = in_array('--dry-run', $argv, TRUE);
$filterModule = NULL;
foreach ($argv as $arg) {
  if (str_starts_with($arg, '--module=')) {
    $filterModule = substr($arg, strlen('--module='));
  }
}

// Discover entity gaps by scanning Entity directories.
$modulesDir = $projectRoot . '/web/modules/custom';
$gaps = []; // module_path => [entity_type_ids]

// Find all Entity PHP files.
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
  if ($file->getExtension() !== 'php') {
    continue;
  }

  $path = $file->getPathname();

  // Must be in src/Entity/ directory.
  if (!preg_match('#/src/Entity/[^/]+\.php$#', $path)) {
    continue;
  }

  // Extract entity type ID from @ContentEntityType or @ConfigEntityType annotation.
  $content = file_get_contents($path);
  if (!preg_match('/@(Content|Config)EntityType\s*\(/', $content)) {
    continue;
  }

  // Extract id.
  if (!preg_match('/\*\s*id\s*=\s*"([^"]+)"/', $content, $idMatch)) {
    continue;
  }
  $entityTypeId = $idMatch[1];
  $isConfig = (bool) preg_match('/@ConfigEntityType/', $content);

  // Resolve module path and name.
  // Find the closest .info.yml to determine module boundary.
  $dir = dirname($path);
  $modulePath = NULL;
  $moduleName = NULL;
  while ($dir !== $modulesDir && $dir !== '/') {
    $candidates = glob($dir . '/*.info.yml');
    if (!empty($candidates)) {
      $infoFile = basename($candidates[0], '.info.yml');
      $modulePath = $dir;
      $moduleName = $infoFile;
      break;
    }
    $dir = dirname($dir);
  }

  if (!$moduleName || !$modulePath) {
    continue;
  }

  if ($filterModule && $moduleName !== $filterModule) {
    continue;
  }

  // Check if this entity is already referenced in .install file.
  $installFile = $modulePath . '/' . $moduleName . '.install';
  $alreadyReferenced = FALSE;
  if (file_exists($installFile)) {
    $installContent = file_get_contents($installFile);
    // Check for entity type ID in installEntityType, getEntityType, or entity class reference.
    if (
      str_contains($installContent, "'" . $entityTypeId . "'") ||
      str_contains($installContent, '"' . $entityTypeId . '"')
    ) {
      $alreadyReferenced = TRUE;
    }
  }

  if (!$alreadyReferenced) {
    if (!isset($gaps[$moduleName])) {
      $gaps[$moduleName] = [
        'path' => $modulePath,
        'entities' => [],
      ];
    }
    $gaps[$moduleName]['entities'][] = [
      'id' => $entityTypeId,
      'is_config' => $isConfig,
      'file' => $path,
    ];
  }
}

// Sort by module name.
ksort($gaps);

$totalEntities = 0;
$totalModules = count($gaps);
foreach ($gaps as $data) {
  $totalEntities += count($data['entities']);
}

echo "Entity Install Hook Generator\n";
echo "==============================\n";
echo "Modules affected: $totalModules\n";
echo "Entity types needing install: $totalEntities\n";
echo "Mode: " . ($dryRun ? "DRY RUN" : "WRITE") . "\n\n";

$modulesWritten = 0;
$entitiesWritten = 0;

foreach ($gaps as $moduleName => $data) {
  $modulePath = $data['path'];
  $entities = $data['entities'];
  $installFile = $modulePath . '/' . $moduleName . '.install';

  // Sort entities by ID for consistent output.
  usort($entities, fn($a, $b) => strcmp($a['id'], $b['id']));

  $entityIds = array_map(fn($e) => $e['id'], $entities);
  $entityCount = count($entities);

  echo "[$moduleName] $entityCount entities: " . implode(', ', $entityIds) . "\n";

  if ($dryRun) {
    $entitiesWritten += $entityCount;
    $modulesWritten++;
    continue;
  }

  // Determine next update hook number.
  $nextUpdateNum = 10001;
  if (file_exists($installFile)) {
    $installContent = file_get_contents($installFile);
    // Find highest existing update hook number.
    if (preg_match_all('/function\s+' . preg_quote($moduleName) . '_update_(\d+)/', $installContent, $matches)) {
      $maxNum = max(array_map('intval', $matches[1]));
      $nextUpdateNum = $maxNum + 1;
    }
  }

  // Generate the hook code.
  $hookCode = generateUpdateHook($moduleName, $entities, $nextUpdateNum);

  // Also generate/update hook_requirements if not present.
  $requirementsCode = generateRequirements($moduleName, $entities);

  if (file_exists($installFile)) {
    // Append to existing file.
    $existingContent = file_get_contents($installFile);

    // Check if hook_requirements already exists.
    $hasRequirements = str_contains($existingContent, $moduleName . '_requirements');

    $appendContent = "\n" . $hookCode;
    if (!$hasRequirements) {
      $appendContent .= "\n" . $requirementsCode;
    }

    file_put_contents($installFile, $existingContent . $appendContent);
    echo "  -> Appended update_$nextUpdateNum to $installFile\n";
  }
  else {
    // Create new .install file.
    $newContent = "<?php\n\n";
    $newContent .= "/**\n * @file\n * Install, update, and uninstall functions for $moduleName module.\n */\n\n";
    $newContent .= "declare(strict_types=1);\n\n";
    $newContent .= $hookCode . "\n";
    $newContent .= $requirementsCode;

    file_put_contents($installFile, $newContent);
    echo "  -> Created $installFile\n";
  }

  $entitiesWritten += $entityCount;
  $modulesWritten++;
}

echo "\n==============================\n";
echo "Modules processed: $modulesWritten / $totalModules\n";
echo "Entity types processed: $entitiesWritten / $totalEntities\n";
echo "Done.\n";

exit(0);

// ─────────────────────────────────────────────────────────
// Generator functions.
// ─────────────────────────────────────────────────────────

/**
 * Generate hook_update_N() for a batch of entity types.
 */
function generateUpdateHook(string $moduleName, array $entities, int $hookNum): string {
  $entityIds = array_map(fn($e) => $e['id'], $entities);
  $idList = implode(', ', $entityIds);
  $entityCount = count($entities);

  $code = "/**\n";
  $code .= " * Install entity types: $idList.\n";
  $code .= " *\n";
  $code .= " * UPDATE-HOOK-REQUIRED-001: Ensure all entity type definitions are installed.\n";
  $code .= " */\n";
  $code .= "function {$moduleName}_update_{$hookNum}(): string {\n";
  $code .= "  \$messages = [];\n";
  $code .= "  \$updateManager = \\Drupal::entityDefinitionUpdateManager();\n";
  $code .= "  \$entityTypeManager = \\Drupal::entityTypeManager();\n\n";

  $code .= "  \$entityTypes = [\n";
  foreach ($entities as $entity) {
    $code .= "    '" . $entity['id'] . "',\n";
  }
  $code .= "  ];\n\n";

  $code .= "  foreach (\$entityTypes as \$entityTypeId) {\n";
  $code .= "    try {\n";
  $code .= "      if (!\$updateManager->getEntityType(\$entityTypeId)) {\n";
  $code .= "        \$entityType = \$entityTypeManager->getDefinition(\$entityTypeId, FALSE);\n";
  $code .= "        if (\$entityType) {\n";
  $code .= "          \$updateManager->installEntityType(\$entityType);\n";
  $code .= "          \$messages[] = \"Installed entity type: \$entityTypeId.\";\n";
  $code .= "        }\n";
  $code .= "        else {\n";
  $code .= "          \$messages[] = \"Definition not found: \$entityTypeId.\";\n";
  $code .= "        }\n";
  $code .= "      }\n";
  $code .= "      else {\n";
  $code .= "        \$messages[] = \"Already installed: \$entityTypeId.\";\n";
  $code .= "      }\n";
  $code .= "    }\n";
  $code .= "    catch (\\Throwable \$e) {\n";
  $code .= "      \$messages[] = \"Error installing \$entityTypeId: \" . \$e->getMessage();\n";
  $code .= "    }\n";
  $code .= "  }\n\n";

  $code .= "  return implode(' ', \$messages);\n";
  $code .= "}\n";

  return $code;
}

/**
 * Generate hook_requirements() for entity type health check.
 */
function generateRequirements(string $moduleName, array $entities): string {
  $entityIds = array_map(fn($e) => $e['id'], $entities);

  $code = "/**\n";
  $code .= " * Implements hook_requirements().\n";
  $code .= " */\n";
  $code .= "function {$moduleName}_requirements(string \$phase): array {\n";
  $code .= "  \$requirements = [];\n\n";
  $code .= "  if (\$phase !== 'runtime') {\n";
  $code .= "    return \$requirements;\n";
  $code .= "  }\n\n";

  $code .= "  \$entityTypes = [\n";
  foreach ($entityIds as $id) {
    $code .= "    '$id',\n";
  }
  $code .= "  ];\n";

  $code .= "  \$updateManager = \\Drupal::entityDefinitionUpdateManager();\n";
  $code .= "  \$missing = [];\n\n";

  $code .= "  foreach (\$entityTypes as \$typeId) {\n";
  $code .= "    if (!\$updateManager->getEntityType(\$typeId)) {\n";
  $code .= "      \$missing[] = \$typeId;\n";
  $code .= "    }\n";
  $code .= "  }\n\n";

  $label = str_replace('_', ' ', ucfirst($moduleName));
  $code .= "  if (!empty(\$missing)) {\n";
  $code .= "    \$requirements['{$moduleName}_entities'] = [\n";
  $code .= "      'title' => t('$label: Entity Types'),\n";
  $code .= "      'value' => t('Missing: @types', ['@types' => implode(', ', \$missing)]),\n";
  $code .= "      'description' => t('Run drush entity:updates to install missing entity types.'),\n";
  $code .= "      'severity' => REQUIREMENT_ERROR,\n";
  $code .= "    ];\n";
  $code .= "  }\n";
  $code .= "  else {\n";
  $code .= "    \$requirements['{$moduleName}_entities'] = [\n";
  $code .= "      'title' => t('$label: Entity Types'),\n";
  $code .= "      'value' => t('All @count entity types installed', ['@count' => count(\$entityTypes)]),\n";
  $code .= "      'severity' => REQUIREMENT_OK,\n";
  $code .= "    ];\n";
  $code .= "  }\n\n";

  $code .= "  return \$requirements;\n";
  $code .= "}\n";

  return $code;
}
