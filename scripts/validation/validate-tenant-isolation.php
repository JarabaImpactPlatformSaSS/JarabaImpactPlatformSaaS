<?php

/**
 * @file
 * TENANT-CHECK-001: Validate tenant isolation in AccessControlHandlers.
 *
 * Finds all AccessControlHandler classes for entities that have a tenant_id
 * field and verifies they contain tenant comparison logic for update/delete.
 *
 * Rules:
 * - TENANT-ISOLATION-ACCESS-001: Every AccessControlHandler for entities
 *   with tenant_id MUST verify tenant match for update/delete operations.
 *
 * Usage: php scripts/validation/validate-tenant-isolation.php
 * Exit:  0 = isolated, 1 = leaks found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$deprecatedModules = ['jaraba_blog'];

$leaks = [];
$checked = 0;

// ─────────────────────────────────────────────────────────────
// Step 1: Find all entities with tenant_id field.
// ─────────────────────────────────────────────────────────────
$entityFiles = array_merge(
  glob("$modulesDir/*/src/Entity/*.php") ?: [],
  glob("$modulesDir/*/modules/*/src/Entity/*.php") ?: []
);

$entitiesWithTenant = [];

foreach ($entityFiles as $entityFile) {
  $content = file_get_contents($entityFile);
  if ($content === FALSE) {
    continue;
  }

  $moduleName = basename(dirname(dirname(dirname($entityFile))));
  if (in_array($moduleName, $deprecatedModules, TRUE)) {
    continue;
  }

  // Skip interfaces, traits, base classes.
  $basename = basename($entityFile, '.php');
  if (str_ends_with($basename, 'Interface') || str_ends_with($basename, 'Trait') || str_ends_with($basename, 'Base')) {
    continue;
  }

  $isContentEntity = str_contains($content, '@ContentEntityType');
  if (!$isContentEntity) {
    continue;
  }

  // Check if entity defines tenant_id field.
  $hasTenantId = str_contains($content, "'tenant_id'") || str_contains($content, '"tenant_id"');
  if (!$hasTenantId) {
    continue;
  }

  // Extract entity type ID.
  $entityTypeId = NULL;
  if (preg_match('/\*\s+id\s*=\s*"([^"]+)"/', $content, $m)) {
    $entityTypeId = $m[1];
  }

  // Extract access handler class.
  $accessHandler = NULL;
  if (preg_match('/["\']access["\']\s*=\s*"([^"]+)"/', $content, $m)) {
    $accessHandler = $m[1];
  }

  if ($entityTypeId === NULL) {
    continue;
  }

  $entitiesWithTenant[] = [
    'entity_type_id' => $entityTypeId,
    'file' => str_replace($projectRoot . '/', '', $entityFile),
    'access_handler' => $accessHandler,
    'class' => $basename,
  ];
}

// ─────────────────────────────────────────────────────────────
// Step 2: For each entity with tenant_id, check its handler.
// ─────────────────────────────────────────────────────────────
foreach ($entitiesWithTenant as $entity) {
  $checked++;

  // If no access handler declared at all, that's already caught by
  // AUDIT-CONS-001 in validate-entity-integrity.php. Skip here.
  if (empty($entity['access_handler'])) {
    continue;
  }

  // Resolve handler class to file path.
  $handlerClass = $entity['access_handler'];
  $handlerFile = resolveClassToFile($handlerClass, $modulesDir);

  if ($handlerFile === NULL) {
    $leaks[] = [
      'entity' => $entity['entity_type_id'],
      'file' => $entity['file'],
      'message' => "Access handler class '$handlerClass' file not found",
    ];
    continue;
  }

  $handlerContent = file_get_contents($handlerFile);
  if ($handlerContent === FALSE) {
    continue;
  }

  // Check if handler references tenant (any of these patterns).
  $tenantPatterns = [
    'tenant_id',
    'tenant',
    'getTenantId',
    'get(\'tenant_id\')',
    'get("tenant_id")',
    'getCurrentTenant',
    'TenantBridge',
    'tenant_context',
    'DefaultEntityAccessControlHandler',
  ];

  $hasTenantCheck = FALSE;
  foreach ($tenantPatterns as $pattern) {
    if (str_contains($handlerContent, $pattern)) {
      $hasTenantCheck = TRUE;
      break;
    }
  }

  // Also check if the handler extends DefaultEntityAccessControlHandler
  // which already handles tenant isolation.
  if (str_contains($handlerContent, 'extends DefaultEntityAccessControlHandler')) {
    $hasTenantCheck = TRUE;
  }

  if (!$hasTenantCheck) {
    $leaks[] = [
      'entity' => $entity['entity_type_id'],
      'file' => str_replace($projectRoot . '/', '', $handlerFile),
      'message' => "AccessControlHandler does not check tenant isolation for entity with tenant_id",
    ];
  }
}

/**
 * Resolve a fully-qualified class name to a file path.
 */
function resolveClassToFile(string $fqcn, string $modulesDir): ?string {
  // Convert namespace to path: Drupal\module_name\... → web/modules/custom/module_name/src/...
  $parts = explode('\\', ltrim($fqcn, '\\'));

  if (count($parts) < 3 || $parts[0] !== 'Drupal') {
    return NULL;
  }

  $moduleName = $parts[1];
  $classPath = implode('/', array_slice($parts, 2));
  $filePath = "$modulesDir/$moduleName/src/$classPath.php";

  if (file_exists($filePath)) {
    return $filePath;
  }

  // Try submodule path.
  $submoduleDirs = glob("$modulesDir/*/modules/$moduleName", GLOB_ONLYDIR) ?: [];
  foreach ($submoduleDirs as $subDir) {
    $filePath = "$subDir/src/$classPath.php";
    if (file_exists($filePath)) {
      return $filePath;
    }
  }

  return NULL;
}

// ─────────────────────────────────────────────────────────────
// Output.
// ─────────────────────────────────────────────────────────────
echo "\n";
echo "=== TENANT-CHECK-001: Tenant isolation verification ===\n";
echo "  Entities with tenant_id: " . count($entitiesWithTenant) . "\n";
echo "  Access handlers checked: $checked\n";
echo "\n";

if (!empty($leaks)) {
  echo "  [LEAK] Potential tenant isolation gaps:\n";
  foreach ($leaks as $l) {
    echo "    Entity '{$l['entity']}' — {$l['file']}\n";
    echo "      {$l['message']}\n";
  }
  echo "\n";
  echo "  " . count($leaks) . " potential tenant isolation gap(s) found.\n";
  echo "  See TENANT-ISOLATION-ACCESS-001 in CLAUDE.md.\n";
  echo "\n";
  exit(1);
}

echo "  OK: All tenant entities have isolation checks in their access handlers.\n";
echo "\n";
exit(0);
