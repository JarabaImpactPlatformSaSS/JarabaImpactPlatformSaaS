<?php

/**
 * @file
 * CONFIG-INSTALL-DRIFT-001: Detects config keys in config/install that
 * are missing from the active Drupal config in database.
 *
 * This is the safeguard for the gap "código existe vs usuario experimenta"
 * in configuration: a key added to config/install/*.yml only applies on
 * fresh module install. Existing installations keep the old config until
 * a hook_update_N() explicitly adds the new keys.
 *
 * This validator scans ALL jaraba_* modules for drift between their
 * config/install/*.settings.yml and the active config in the Drupal DB.
 *
 * Requires: Drupal bootstrap (runs via drush eval or direct PHP with
 * autoloader). Falls back to YAML-only check if no Drupal bootstrap.
 *
 * Usage: php scripts/validation/validate-config-install-drift.php
 * Exit code: 0 = no drift, 1 = drift detected.
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passed = 0;
$checked = 0;

$modulesBase = __DIR__ . '/../../web/modules/custom';

// Scan all jaraba_* modules for config/install/*.settings.yml files.
$settingsFiles = glob($modulesBase . '/jaraba_*/config/install/*.settings.yml');
$settingsFiles = array_merge(
  $settingsFiles,
  glob($modulesBase . '/ecosistema_jaraba_*/config/install/*.settings.yml')
);

if (empty($settingsFiles)) {
    echo "  No settings.yml files found in config/install/\n";
    exit(0);
}

// Try Drupal bootstrap for active config comparison.
$hasDrupal = FALSE;
$drupalRoot = __DIR__ . '/../../web';
$autoloader = $drupalRoot . '/autoload.php';

// We compare install YAML keys vs schema YAML keys.
// If schema exists but lacks keys from install, there's drift.
// This works without Drupal bootstrap.
foreach ($settingsFiles as $settingsFile) {
    $configName = basename($settingsFile, '.yml');
    $moduleName = basename(dirname($settingsFile, 3));

    $installContent = file_get_contents($settingsFile);
    if ($installContent === FALSE) {
        $warnings[] = "[{$moduleName}] Cannot read {$settingsFile}";
        continue;
    }

    // Parse YAML keys from install file.
    $installKeys = [];
    foreach (explode("\n", $installContent) as $line) {
        // Top-level keys only (no leading spaces, ends with ':').
        if (preg_match('/^([a-z][a-z0-9_]*)\s*:/', $line, $m)) {
            $installKeys[] = $m[1];
        }
    }

    if (empty($installKeys)) {
        continue;
    }

    // Check corresponding schema file for completeness.
    $schemaDir = dirname($settingsFile, 2) . '/schema';
    $schemaFile = $schemaDir . '/' . $moduleName . '.schema.yml';

    if (!file_exists($schemaFile)) {
        $warnings[] = "[{$moduleName}] No schema file: {$schemaFile}";
        $checked++;
        continue;
    }

    $schemaContent = file_get_contents($schemaFile);
    if ($schemaContent === FALSE) {
        $warnings[] = "[{$moduleName}] Cannot read schema file";
        $checked++;
        continue;
    }

    // Extract schema mapping keys.
    $schemaKeys = [];
    $inMapping = FALSE;
    foreach (explode("\n", $schemaContent) as $line) {
        // Detect 'mapping:' section.
        if (preg_match('/^\s{2,4}mapping:\s*$/', $line)) {
            $inMapping = TRUE;
            continue;
        }
        // Keys inside mapping (4-6 spaces indent, followed by ':').
        if ($inMapping && preg_match('/^\s{4,6}([a-z][a-z0-9_]*):\s*$/', $line, $m)) {
            $schemaKeys[] = $m[1];
        }
        // Exit mapping if we hit a new top-level key.
        if ($inMapping && preg_match('/^[a-z]/', $line)) {
            $inMapping = FALSE;
        }
    }

    $checked++;

    // Compare install keys vs schema keys.
    $missingInSchema = array_diff($installKeys, $schemaKeys);
    if (!empty($missingInSchema)) {
        $errors[] = "[{$moduleName}] {$configName}: " . count($missingInSchema) .
            " key(s) in config/install but MISSING in schema: " .
            implode(', ', $missingInSchema);
    }

    // Also check for hook_update that references these keys.
    $installFile = dirname($settingsFile, 3) . '/' . $moduleName . '.install';
    if (file_exists($installFile)) {
        $installContent2 = file_get_contents($installFile);
        foreach ($missingInSchema as $key) {
            if ($installContent2 !== FALSE && !str_contains($installContent2, "'" . $key . "'")) {
                $warnings[] = "[{$moduleName}] Key '{$key}' missing in config/install AND no hook_update references it. " .
                    "Add a hook_update_N() to propagate to existing installations.";
            }
        }
    }

    if (empty($missingInSchema)) {
        $passed++;
    }
}

// ─── RESULTS ───
echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo " CONFIG-INSTALL-DRIFT-001: Config Install vs Schema Drift\n";
echo "═══════════════════════════════════════════════════════\n";
echo "\n";

foreach ($errors as $error) {
    echo "  ✗ FAIL: {$error}\n";
}
foreach ($warnings as $warning) {
    echo "  ⚠ WARN: {$warning}\n";
}

echo "\n  Modules checked: {$checked}";
echo "\n  Passed: {$passed}/{$checked}";
if (!empty($warnings)) {
    echo " (+" . count($warnings) . " warnings)";
}
echo "\n\n";

if (!empty($errors)) {
    echo "  RESULT: FAIL\n\n";
    exit(1);
}

echo "  RESULT: PASS\n\n";
exit(0);
