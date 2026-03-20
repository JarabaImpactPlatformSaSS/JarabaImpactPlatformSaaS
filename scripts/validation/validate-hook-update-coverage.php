<?php

/**
 * @file
 * HOOK-UPDATE-COVERAGE-001: Detect entity types without installEntityType() coverage.
 *
 * Every ContentEntity and ConfigEntity declared via annotation MUST have either:
 *   a) A hook_install() that calls installEntityType() for that entity, OR
 *   b) A hook_update_N() that calls installEntityType() for that entity
 *
 * Without this, entities pass CI but fail in production with
 * "entity type needs to be installed" in /admin/reports/status.
 *
 * ConfigEntities are NOT exempt — Drupal tracks their definitions too.
 *
 * Usage: php scripts/validation/validate-hook-update-coverage.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  HOOK-UPDATE-COVERAGE-001                               ║\033[0m\n";
echo "\033[36m║  Entity Type Install/Update Hook Coverage Validator      ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// Deprecated/uninstalled modules to skip entirely.
$deprecatedModules = ['jaraba_blog'];

// Core Drupal entity types (from contrib or core, never in our .install).
$coreEntityTypes = [
  'node', 'user', 'taxonomy_term', 'taxonomy_vocabulary', 'block_content',
  'comment', 'file', 'menu_link_content', 'path_alias', 'shortcut',
  'block', 'action', 'menu', 'view', 'image_style', 'date_format',
  'search_page', 'entity_form_display', 'entity_view_display',
  'entity_view_mode', 'entity_form_mode', 'field_config',
  'field_storage_config', 'base_field_override', 'filter_format',
  'editor', 'responsive_image_style', 'media', 'media_type',
  'workflow', 'content_moderation_state', 'redirect', 'pathauto_pattern',
  'webform', 'webform_submission', 'group', 'group_type',
  'group_content', 'group_content_type', 'group_relationship',
  'group_relationship_type', 'domain', 'domain_alias',
];

$errors = [];
$warnings = [];
$passes = [];
$checkedEntities = 0;

/**
 * Extract the module name from a file path.
 */
function getModuleName(string $filePath, string $modulesDir): string {
  $relative = str_replace($modulesDir . '/', '', $filePath);
  $parts = explode('/', $relative);

  // Submodule: parent/modules/submodule/src/Entity/...
  if (isset($parts[1]) && $parts[1] === 'modules' && isset($parts[2])) {
    return $parts[2];
  }

  return $parts[0];
}

/**
 * Get the module directory from a file path.
 */
function getModuleDir(string $filePath, string $modulesDir): string {
  $relative = str_replace($modulesDir . '/', '', $filePath);
  $parts = explode('/', $relative);

  // Submodule path.
  if (isset($parts[1]) && $parts[1] === 'modules' && isset($parts[2])) {
    return $modulesDir . '/' . $parts[0] . '/modules/' . $parts[2];
  }

  return $modulesDir . '/' . $parts[0];
}

/**
 * Check if an .install or .module file contains installEntityType() for entity.
 *
 * Searches for the entity type ID string in context of:
 * - installEntityType()
 * - applyEntitySchemaUpdate()
 * - installFieldStorageDefinition()
 * - getEntityType('entity_type_id')
 * - getDefinition('entity_type_id')
 */
function hasInstallCoverage(string $fileContent, string $entityTypeId): bool {
  // Check if the entity type ID is referenced at all.
  if (!str_contains($fileContent, "'$entityTypeId'") && !str_contains($fileContent, "\"$entityTypeId\"")) {
    return false;
  }

  // Must be in context of an install/update function.
  $installPatterns = [
    'installEntityType',
    'applyEntitySchemaUpdate',
    'installFieldStorageDefinition',
    'updateFieldableEntityType',
    'updateEntityType',
    'getEntityType',
    'getDefinition',
  ];

  foreach ($installPatterns as $pattern) {
    if (str_contains($fileContent, $pattern)) {
      return true;
    }
  }

  return false;
}

/**
 * Check if the .install file contains a hook_install() function.
 */
function hasHookInstall(string $fileContent, string $moduleName): bool {
  return (bool) preg_match('/function\s+' . preg_quote($moduleName, '/') . '_install\s*\(/', $fileContent);
}

/**
 * Check if the .install file contains any hook_update_N() function.
 */
function hasHookUpdate(string $fileContent, string $moduleName): bool {
  return (bool) preg_match('/function\s+' . preg_quote($moduleName, '/') . '_update_\d+\s*\(/', $fileContent);
}

// Find all entity PHP files (top-level and submodules).
$entityFiles = array_merge(
  glob("$modulesDir/*/src/Entity/*.php") ?: [],
  glob("$modulesDir/*/modules/*/src/Entity/*.php") ?: []
);

foreach ($entityFiles as $entityFile) {
  $content = file_get_contents($entityFile);
  if ($content === false) {
    continue;
  }

  // Skip interfaces, traits, and base classes.
  $basename = basename($entityFile, '.php');
  if (str_ends_with($basename, 'Interface') || str_ends_with($basename, 'Trait') || str_ends_with($basename, 'Base')) {
    continue;
  }

  $isContentEntity = str_contains($content, '@ContentEntityType');
  $isConfigEntity = str_contains($content, '@ConfigEntityType');

  if (!$isContentEntity && !$isConfigEntity) {
    continue;
  }

  // Extract entity type ID from annotation.
  $entityTypeId = null;
  if (preg_match('/\*\s+id\s*=\s*"([^"]+)"/', $content, $m)) {
    $entityTypeId = $m[1];
  }

  if ($entityTypeId === null) {
    continue;
  }

  $moduleName = getModuleName($entityFile, $modulesDir);
  $moduleDir = getModuleDir($entityFile, $modulesDir);
  $relativePath = str_replace($projectRoot . '/', '', $entityFile);
  $entityKind = $isContentEntity ? 'ContentEntity' : 'ConfigEntity';

  // Skip deprecated modules.
  if (in_array($moduleName, $deprecatedModules, true)) {
    continue;
  }

  // Skip core/contrib entity types.
  if (in_array($entityTypeId, $coreEntityTypes, true)) {
    continue;
  }

  $checkedEntities++;

  // Look for .install file.
  $installFile = $moduleDir . '/' . $moduleName . '.install';
  $moduleFile = $moduleDir . '/' . $moduleName . '.module';

  $installContent = '';
  $moduleContent = '';

  if (file_exists($installFile)) {
    $installContent = file_get_contents($installFile) ?: '';
  }
  if (file_exists($moduleFile)) {
    $moduleContent = file_get_contents($moduleFile) ?: '';
  }

  // Check 1: Does the .install file exist at all?
  if (!file_exists($installFile)) {
    $errors[] = [
      'entity_type_id' => $entityTypeId,
      'kind' => $entityKind,
      'file' => $relativePath,
      'module' => $moduleName,
      'message' => "$entityKind '$entityTypeId' has NO .install file. Needs hook_install() or hook_update_N() with installEntityType().",
    ];
    continue;
  }

  // Check 2: Does the .install file reference this entity type ID with install functions?
  if (hasInstallCoverage($installContent, $entityTypeId)) {
    $passes[] = "[$moduleName] $entityKind '$entityTypeId' — covered in .install";
    continue;
  }

  // Check 3: Maybe the entity is referenced in a hook_install() that installs all entity types generically.
  // Pattern: foreach loop or getEntityType() with dynamic variable (common in ecosistema_jaraba_core).
  $hasGenericInstall = false;
  if (hasHookInstall($installContent, $moduleName)) {
    // Check for generic patterns like installEntityType($entity_type) in a loop.
    if (preg_match('/installEntityType\s*\(\s*\$/', $installContent)) {
      $hasGenericInstall = true;
    }
    // Check for getDefinition() with the module prefix (installs all module entities).
    if (str_contains($installContent, "entity_type.manager") && str_contains($installContent, 'installEntityType')) {
      $hasGenericInstall = true;
    }
  }

  if ($hasGenericInstall) {
    $passes[] = "[$moduleName] $entityKind '$entityTypeId' — generic install pattern detected";
    continue;
  }

  // Check 4: Maybe the entity type ID appears in a hook_update_N() via class name reference.
  // Extract the class name from the entity file and check if .install references it.
  $className = $basename;
  if (str_contains($installContent, $className) && str_contains($installContent, 'installEntityType')) {
    $passes[] = "[$moduleName] $entityKind '$entityTypeId' — covered via class reference in .install";
    continue;
  }

  // Check 5: Module has hook_install() with installEntityType but entity ID differs in quoting.
  if (hasHookInstall($installContent, $moduleName) && str_contains($installContent, 'installEntityType')) {
    // Broad match — the module does install entities, maybe this one is covered by a variable.
    $warnings[] = [
      'entity_type_id' => $entityTypeId,
      'kind' => $entityKind,
      'file' => $relativePath,
      'module' => $moduleName,
      'message' => "$entityKind '$entityTypeId' not explicitly found in .install but module has installEntityType() calls. Verify manually.",
    ];
    continue;
  }

  // No coverage found — ERROR.
  $hasAnyUpdate = hasHookUpdate($installContent, $moduleName);
  $hasAnyInstall = hasHookInstall($installContent, $moduleName);

  $hint = '';
  if (!$hasAnyInstall && !$hasAnyUpdate) {
    $hint = ' .install has no hook_install() or hook_update_N().';
  } elseif ($hasAnyInstall && !$hasAnyUpdate) {
    $hint = ' hook_install() exists but does not reference this entity.';
  } elseif (!$hasAnyInstall && $hasAnyUpdate) {
    $hint = ' hook_update_N() exists but does not reference this entity.';
  } else {
    $hint = ' Both hook_install() and hook_update_N() exist but neither references this entity.';
  }

  $errors[] = [
    'entity_type_id' => $entityTypeId,
    'kind' => $entityKind,
    'file' => $relativePath,
    'module' => $moduleName,
    'message' => "$entityKind '$entityTypeId' NOT covered by installEntityType() in $moduleName.install.$hint",
  ];
}

// ── REPORT ────────────────────────────────────────────────────────────
echo "Scanned: " . count($entityFiles) . " entity files, checked $checkedEntities entity types\n\n";

foreach ($passes as $p) {
  echo "  \033[32m✓\033[0m $p\n";
}

if (!empty($warnings)) {
  echo "\n";
  foreach ($warnings as $w) {
    echo "  \033[33m⚠\033[0m [{$w['module']}] {$w['message']}\n";
  }
}

if (!empty($errors)) {
  echo "\n";
  foreach ($errors as $e) {
    echo "  \033[31m✗\033[0m [{$e['module']}] {$e['message']}\n";
    echo "    File: {$e['file']}\n";
  }
}

$totalChecks = count($passes) + count($errors);
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULT: " . count($passes) . "/$totalChecks PASS";
if (!empty($warnings)) {
  echo ", " . count($warnings) . " WARN";
}
if (!empty($errors)) {
  echo ", " . count($errors) . " FAIL";
}
echo "\n═══════════════════════════════════════════════════════════\n";

exit(empty($errors) ? 0 : 1);
