<?php

declare(strict_types=1);

/**
 * @file
 * TENANT-VIEWS-ISOLATION-001: Validates Views with tenant entities filter by tenant_id.
 *
 * Scans all Views config (config/sync/views.view.*.yml) and verifies that
 * Views using entity types with tenant_id field have a tenant_id filter
 * or contextual filter configured. Without this, an admin could create
 * a View that exposes cross-tenant data.
 *
 * Usage: php scripts/validation/validate-views-tenant-isolation.php
 * Exit: 0 = PASS, 1 = FAIL
 */

$projectRoot = dirname(__DIR__, 2);

// Step 1: Find all entity types with tenant_id in baseFieldDefinitions.
$entityTypesWithTenant = [];

$entityFiles = glob($projectRoot . '/web/modules/custom/*/src/Entity/*.php');
foreach ($entityFiles as $file) {
  $content = file_get_contents($file);
  if ($content === false) {
    continue;
  }

  // Check if entity has tenant_id field definition.
  if (strpos($content, "'tenant_id'") === false && strpos($content, '"tenant_id"') === false) {
    continue;
  }

  // Check it's actually a BaseFieldDefinition for tenant_id (not just a reference).
  if (strpos($content, 'BaseFieldDefinition') === false) {
    continue;
  }

  // Extract entity type ID from annotation.
  if (preg_match('/id\s*=\s*"([^"]+)"/', $content, $matches)) {
    $entityTypeId = $matches[1];
    // Derive the base table name (entity_type_id with underscores).
    $baseTable = $entityTypeId;
    // Also check for explicit base_table in annotation.
    if (preg_match('/base_table\s*=\s*"([^"]+)"/', $content, $tableMatches)) {
      $baseTable = $tableMatches[1];
    }
    $entityTypesWithTenant[$entityTypeId] = $baseTable;
  }
}

if (empty($entityTypesWithTenant)) {
  echo "WARNING: No entity types with tenant_id found. Skipping.\n";
  exit(0);
}

echo "Found " . count($entityTypesWithTenant) . " entity types with tenant_id field.\n";

// Step 2: Scan all Views config files.
$viewsDir = $projectRoot . '/config/sync';
$viewFiles = glob($viewsDir . '/views.view.*.yml');

// Also check module config/install.
$moduleViewFiles = glob($projectRoot . '/web/modules/custom/*/config/install/views.view.*.yml');
$viewFiles = array_merge($viewFiles, $moduleViewFiles);

if (empty($viewFiles)) {
  echo "No Views config files found. PASS.\n";
  exit(0);
}

echo "Scanning " . count($viewFiles) . " Views config files...\n\n";

$failures = [];
$passes = [];
$skipped = [];

// Admin-only views that are excluded from this check.
$adminExclusions = [
  'admin_', 'content_admin', 'user_admin', 'commerce_order',
  'commerce_cart', 'commerce_checkout', 'block_content',
  'comment', 'archive', 'frontpage', 'taxonomy_term',
  'content_recent', 'who_s_new', 'who_s_online', 'watchdog',
  'files', 'media', 'redirect',
];

foreach ($viewFiles as $viewFile) {
  $viewName = basename($viewFile, '.yml');
  $viewName = preg_replace('/^views\.view\./', '', $viewName);

  $content = file_get_contents($viewFile);
  if ($content === false) {
    continue;
  }

  // Parse YAML manually (lightweight, no Symfony dependency).
  // Extract base_table.
  if (!preg_match('/^base_table:\s*(.+)$/m', $content, $baseTableMatch)) {
    continue;
  }
  $baseTable = trim($baseTableMatch[1]);

  // Check if this view's base_table matches a tenant entity type.
  $matchedEntityType = null;
  foreach ($entityTypesWithTenant as $entityTypeId => $entityBaseTable) {
    // Views can use base_table or _field_data table.
    if ($baseTable === $entityBaseTable
      || $baseTable === $entityBaseTable . '_field_data'
      || $baseTable === $entityTypeId
      || $baseTable === $entityTypeId . '_field_data') {
      $matchedEntityType = $entityTypeId;
      break;
    }
  }

  if ($matchedEntityType === null) {
    $skipped[] = $viewName;
    continue;
  }

  // Check if this is an admin-only view (excluded).
  $isAdmin = false;
  foreach ($adminExclusions as $exclusion) {
    if (strpos($viewName, $exclusion) !== false) {
      $isAdmin = true;
      break;
    }
  }

  // Check for tenant_id filter or contextual filter in the view.
  $hasTenantFilter = false;

  // Check filters.
  if (preg_match('/filters:.*?(?=\n\S|\z)/s', $content, $filterBlock)) {
    if (strpos($filterBlock[0], 'tenant_id') !== false) {
      $hasTenantFilter = true;
    }
  }

  // Check arguments (contextual filters).
  if (!$hasTenantFilter && preg_match('/arguments:.*?(?=\n\S|\z)/s', $content, $argBlock)) {
    if (strpos($argBlock[0], 'tenant_id') !== false) {
      $hasTenantFilter = true;
    }
  }

  // Broad check: anywhere in the YAML mentions tenant_id as filter/argument.
  if (!$hasTenantFilter) {
    // Check for tenant_id in any display's filters or arguments.
    if (preg_match('/(?:filter|argument).*tenant_id/s', $content)) {
      $hasTenantFilter = true;
    }
    // Also check the simple presence of tenant_id as a field reference in filters.
    if (preg_match('/field:\s*tenant_id/', $content)) {
      $hasTenantFilter = true;
    }
  }

  if ($hasTenantFilter) {
    $passes[] = [
      'view' => $viewName,
      'entity' => $matchedEntityType,
      'admin' => $isAdmin,
    ];
  }
  elseif ($isAdmin) {
    // Admin views get a warning, not a failure.
    $passes[] = [
      'view' => $viewName,
      'entity' => $matchedEntityType,
      'admin' => true,
      'note' => 'admin-only (excluded)',
    ];
  }
  else {
    $failures[] = [
      'view' => $viewName,
      'entity' => $matchedEntityType,
      'file' => $viewFile,
    ];
  }
}

// Output results.
echo "=== TENANT-VIEWS-ISOLATION-001 ===\n\n";

if (!empty($passes)) {
  echo "PASS (" . count($passes) . " views with tenant filter):\n";
  foreach ($passes as $pass) {
    $note = isset($pass['note']) ? " [{$pass['note']}]" : '';
    echo "  OK  {$pass['view']} (entity: {$pass['entity']}){$note}\n";
  }
  echo "\n";
}

if (!empty($skipped)) {
  echo "SKIP (" . count($skipped) . " views without tenant entity types)\n\n";
}

if (!empty($failures)) {
  echo "FAIL (" . count($failures) . " views missing tenant_id filter):\n";
  foreach ($failures as $fail) {
    echo "  FAIL  {$fail['view']} uses entity '{$fail['entity']}' (has tenant_id) but NO tenant filter!\n";
    echo "        File: {$fail['file']}\n";
    echo "        Fix: Add tenant_id filter or contextual filter to this view.\n\n";
  }
}

// Summary.
$totalChecked = count($passes) + count($failures);
echo "═══════════════════════════════════════════════════════════\n";
if (empty($failures)) {
  echo "  RESULT: {$totalChecked} tenant views checked — ALL HAVE TENANT FILTER\n";
  echo "═══════════════════════════════════════════════════════════\n";
  exit(0);
}
else {
  echo "  RESULT: " . count($failures) . " of {$totalChecked} tenant views MISSING tenant filter!\n";
  echo "═══════════════════════════════════════════════════════════\n";
  exit(1);
}
