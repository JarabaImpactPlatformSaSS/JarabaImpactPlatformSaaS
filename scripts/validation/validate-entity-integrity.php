<?php

/**
 * @file
 * ENTITY-INTEG-001: Validate entity convention compliance.
 *
 * Checks:
 * 1. ENTITY-001: EntityOwnerTrait usage implies EntityOwnerInterface
 * 2. AUDIT-CONS-001: ContentEntityType must have access handler
 * 3. ENTITY-PREPROCESS-001: Entities with view_builder need preprocess hook
 * 4. FIELD-UI-SETTINGS-TAB-001: field_ui_base_route requires settings route
 * 5. Views: views_data handler declared if entity has list builder
 *
 * Usage: php scripts/validation/validate-entity-integrity.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$deprecatedModules = ['jaraba_blog'];

$errors = [];
$warnings = [];
$checkedEntities = 0;

/**
 * Extract the module name from a file path.
 */
function getModuleName(string $filePath, string $modulesDir): string {
  $relative = str_replace($modulesDir . '/', '', $filePath);
  $parts = explode('/', $relative);

  // Check if it's a submodule: parent/modules/submodule/src/Entity/...
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
 * Check if a .module file contains a template_preprocess function.
 */
function hasPreprocessHook(string $moduleDir, string $moduleName, string $entityTypeId): bool {
  $moduleFile = "$moduleDir/$moduleName.module";
  if (!file_exists($moduleFile)) {
    return FALSE;
  }

  $content = file_get_contents($moduleFile);
  if ($content === FALSE) {
    return FALSE;
  }

  // Look for function template_preprocess_{entity_type_id}(
  // or function {module}_preprocess_{entity_type_id}(
  $patterns = [
    "function template_preprocess_{$entityTypeId}(",
    "function {$moduleName}_preprocess_{$entityTypeId}(",
  ];

  foreach ($patterns as $pattern) {
    if (str_contains($content, $pattern)) {
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * Check if a routing.yml contains a specific route.
 */
function routeExistsInModule(string $moduleDir, string $routeName): bool {
  $routingFiles = glob("$moduleDir/*.routing.yml");
  foreach ($routingFiles as $file) {
    $content = file_get_contents($file);
    if ($content !== FALSE && str_contains($content, "$routeName:")) {
      return TRUE;
    }
  }
  return FALSE;
}

// Find all entity PHP files.
$entityFiles = array_merge(
  glob("$modulesDir/*/src/Entity/*.php") ?: [],
  glob("$modulesDir/*/modules/*/src/Entity/*.php") ?: []
);

foreach ($entityFiles as $entityFile) {
  $content = file_get_contents($entityFile);
  if ($content === FALSE) {
    continue;
  }

  $moduleName = getModuleName($entityFile, $modulesDir);
  $moduleDir = getModuleDir($entityFile, $modulesDir);
  $relativePath = str_replace($projectRoot . '/', '', $entityFile);

  // Check deprecated module.
  if (in_array($moduleName, $deprecatedModules, TRUE)) {
    continue;
  }

  // Skip interfaces, traits, and base classes (typically no @ContentEntityType).
  $basename = basename($entityFile, '.php');
  if (str_ends_with($basename, 'Interface') || str_ends_with($basename, 'Trait') || str_ends_with($basename, 'Base')) {
    continue;
  }

  $isContentEntity = str_contains($content, '@ContentEntityType');
  $isConfigEntity = str_contains($content, '@ConfigEntityType');

  if (!$isContentEntity && !$isConfigEntity) {
    continue;
  }

  $checkedEntities++;

  // Extract entity type ID from annotation.
  $entityTypeId = NULL;
  if (preg_match('/\*\s+id\s*=\s*"([^"]+)"/', $content, $m)) {
    $entityTypeId = $m[1];
  }

  // ─────────────────────────────────────────────────────────
  // CHECK 1: ENTITY-001 — EntityOwnerTrait requires interfaces
  // ─────────────────────────────────────────────────────────
  if (str_contains($content, 'use EntityOwnerTrait')) {
    if (!str_contains($content, 'EntityOwnerInterface')) {
      $errors[] = [
        'rule' => 'ENTITY-001',
        'file' => $relativePath,
        'message' => "Uses EntityOwnerTrait but does not implement EntityOwnerInterface",
      ];
    }
    if (!str_contains($content, 'EntityChangedInterface')) {
      $warnings[] = [
        'rule' => 'ENTITY-001',
        'file' => $relativePath,
        'message' => "Uses EntityOwnerTrait but does not implement EntityChangedInterface",
      ];
    }
  }

  // ─────────────────────────────────────────────────────────
  // CHECK 2: AUDIT-CONS-001 — ContentEntity must have access handler
  // ─────────────────────────────────────────────────────────
  if ($isContentEntity) {
    // Check for access handler in annotation.
    $hasAccessHandler = (bool) preg_match('/["\']access["\']\s*=\s*"[^"]*AccessControlHandler/', $content);
    // Also check the compact form.
    if (!$hasAccessHandler) {
      $hasAccessHandler = str_contains($content, '"access" =');
    }
    // Also allow admin_permission as an alternative.
    $hasAdminPermission = str_contains($content, 'admin_permission');

    if (!$hasAccessHandler && !$hasAdminPermission) {
      $errors[] = [
        'rule' => 'AUDIT-CONS-001',
        'file' => $relativePath,
        'message' => "ContentEntity missing access handler AND admin_permission in annotation",
      ];
    }
  }

  // ─────────────────────────────────────────────────────────
  // CHECK 3: ENTITY-PREPROCESS-001 — view_builder requires preprocess
  // ─────────────────────────────────────────────────────────
  if ($isContentEntity && $entityTypeId !== NULL) {
    $hasViewBuilder = (bool) preg_match('/["\']view_builder["\']\s*=/', $content);
    if ($hasViewBuilder) {
      if (!hasPreprocessHook($moduleDir, $moduleName, $entityTypeId)) {
        $errors[] = [
          'rule' => 'ENTITY-PREPROCESS-001',
          'file' => $relativePath,
          'message' => "Has view_builder but no template_preprocess_{$entityTypeId}() in $moduleName.module",
        ];
      }
    }
  }

  // ─────────────────────────────────────────────────────────
  // CHECK 4: FIELD-UI-SETTINGS-TAB-001 — field_ui_base_route
  // ─────────────────────────────────────────────────────────
  if (preg_match('/field_ui_base_route\s*=\s*"([^"]+)"/', $content, $m)) {
    $fieldUiRoute = $m[1];
    if (!routeExistsInModule($moduleDir, $fieldUiRoute)) {
      // Check in core routing too (some entities use entity.X.edit_form).
      if (!str_starts_with($fieldUiRoute, 'entity.')) {
        $warnings[] = [
          'rule' => 'FIELD-UI-SETTINGS-TAB-001',
          'file' => $relativePath,
          'message' => "field_ui_base_route '$fieldUiRoute' not found in module routing.yml",
        ];
      }
    }
  }

  // ─────────────────────────────────────────────────────────
  // CHECK 5: Views data handler
  // ─────────────────────────────────────────────────────────
  if ($isContentEntity) {
    $hasListBuilder = (bool) preg_match('/["\']list_builder["\']\s*=/', $content);
    $hasViewsData = (bool) preg_match('/["\']views_data["\']\s*=/', $content);

    if ($hasListBuilder && !$hasViewsData) {
      $warnings[] = [
        'rule' => 'VIEWS-DATA-001',
        'file' => $relativePath,
        'message' => "Has list_builder but no views_data handler in annotation",
      ];
    }
  }
}

// ─────────────────────────────────────────────────────────────
// CHECK 6: ECA-EVENT-001 — @EcaEvent must have event_name
// ─────────────────────────────────────────────────────────────
$ecaPluginFiles = array_merge(
  glob("$modulesDir/*/src/Plugin/ECA/Event/*.php") ?: [],
  glob("$modulesDir/*/modules/*/src/Plugin/ECA/Event/*.php") ?: []
);

foreach ($ecaPluginFiles as $ecaFile) {
  $ecaContent = file_get_contents($ecaFile);
  if ($ecaContent === FALSE) {
    continue;
  }
  $ecaRelative = str_replace($projectRoot . '/', '', $ecaFile);

  // Check if it uses @EcaEvent annotation.
  if (str_contains($ecaContent, '@EcaEvent')) {
    if (!preg_match('/event_name\s*=\s*"/', $ecaContent)) {
      $errors[] = [
        'rule' => 'ECA-EVENT-001',
        'file' => $ecaRelative,
        'message' => "@EcaEvent annotation missing required 'event_name' property",
      ];
    }
  }
}

// ─────────────────────────────────────────────────────────────
// CHECK 7: UPDATE-HOOK-REQUIRED-001 — Entity types need update hook
// Every entity type (Content or Config) must have a corresponding
// installEntityType() call in a hook_update_N() in the module's .install.
// ─────────────────────────────────────────────────────────────
foreach ($entityFiles as $entityFile) {
  $entContent = file_get_contents($entityFile);
  if ($entContent === FALSE) {
    continue;
  }
  $entRelative = str_replace($projectRoot . '/', '', $entityFile);
  $entModuleName = getModuleName($entityFile, $modulesDir);

  // Skip deprecated modules.
  if (in_array($entModuleName, $deprecatedModules, TRUE)) {
    continue;
  }

  // Extract entity type id from annotation.
  $entityTypeId = '';
  if (preg_match('/@ContentEntityType\b.*?id\s*=\s*"([^"]+)"/s', $entContent, $m)) {
    $entityTypeId = $m[1];
  }
  elseif (preg_match('/@ConfigEntityType\b.*?id\s*=\s*"([^"]+)"/s', $entContent, $m)) {
    $entityTypeId = $m[1];
  }

  if ($entityTypeId === '') {
    continue;
  }

  // Check if .install file has installEntityType for this entity type.
  $entModuleDir = getModuleDir($entityFile, $modulesDir);
  $installFile = $entModuleDir . '/' . $entModuleName . '.install';

  if (!file_exists($installFile)) {
    $warnings[] = [
      'rule' => 'UPDATE-HOOK-REQUIRED-001',
      'file' => $entRelative,
      'message' => "Entity type '$entityTypeId' has no .install file in module '$entModuleName'. Ensure hook_install() or hook_update_N() registers it.",
    ];
    continue;
  }

  $installContent = file_get_contents($installFile);
  if ($installContent !== FALSE && !str_contains($installContent, "'$entityTypeId'") && !str_contains($installContent, "\"$entityTypeId\"")) {
    $warnings[] = [
      'rule' => 'UPDATE-HOOK-REQUIRED-001',
      'file' => $entRelative,
      'message' => "Entity type '$entityTypeId' not referenced in $entModuleName.install. Needs installEntityType() in hook_update_N() or hook_install().",
    ];
  }
}

// Output results.
echo "\n";
echo "=== ENTITY-INTEG-001: Entity convention compliance ===\n";
echo "  Checked: $checkedEntities entity definitions\n";
echo "\n";

if (!empty($errors)) {
  // Group by rule.
  $byRule = [];
  foreach ($errors as $e) {
    $byRule[$e['rule']][] = $e;
  }

  echo "  ERRORS:\n";
  foreach ($byRule as $rule => $items) {
    echo "  [$rule]\n";
    foreach ($items as $item) {
      echo "    {$item['file']}: {$item['message']}\n";
    }
  }
  echo "\n";
}

if (!empty($warnings)) {
  $byRule = [];
  foreach ($warnings as $w) {
    $byRule[$w['rule']][] = $w;
  }

  echo "  WARNINGS:\n";
  foreach ($byRule as $rule => $items) {
    echo "  [$rule]\n";
    foreach ($items as $item) {
      echo "    {$item['file']}: {$item['message']}\n";
    }
  }
  echo "\n";
}

if (empty($errors)) {
  echo "  OK: All entity conventions are satisfied.\n";
  echo "\n";
  exit(0);
}

echo "  " . count($errors) . " error(s), " . count($warnings) . " warning(s) found.\n";
echo "\n";
exit(1);
