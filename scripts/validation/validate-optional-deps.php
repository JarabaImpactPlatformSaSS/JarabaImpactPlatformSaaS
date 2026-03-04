<?php

/**
 * @file
 * OPTIONAL-CROSSMODULE-001: Detect cross-module hard dependencies in services.yml.
 *
 * Every service reference from module A to module B (where A != B) SHOULD use
 * optional injection (@?) instead of hard injection (@). Hard cross-module
 * dependencies create a fragile dependency chain where enabling/disabling one
 * module breaks another module's service container compilation.
 *
 * Exceptions:
 * - ecosistema_jaraba_core is a mandatory dependency for all modules
 * - Core/contrib services (entity_type.manager, database, etc.) are always available
 * - Submodules referencing their parent module
 *
 * Usage: php scripts/validation/validate-optional-deps.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

// ─────────────────────────────────────────────────────────────
// Step 1: Build module registry (service_id prefix => module_name).
// ─────────────────────────────────────────────────────────────
$moduleDirs = glob("$modulesDir/*/") ?: [];
$submoduleDirs = glob("$modulesDir/*/modules/*/") ?: [];
$allModuleDirs = array_merge($moduleDirs, $submoduleDirs);

$moduleNames = [];
foreach ($allModuleDirs as $dir) {
  $moduleNames[] = basename(rtrim($dir, '/'));
}

// Map service ID prefixes to owning module.
// Service IDs typically start with module_name. (e.g., jaraba_analytics.service_name).
$serviceToModule = [];
$serviceFiles = array_merge(
  glob("$modulesDir/*/*.services.yml") ?: [],
  glob("$modulesDir/*/modules/*/*.services.yml") ?: []
);

foreach ($serviceFiles as $file) {
  $moduleName = basename(dirname($file));
  $content = file_get_contents($file);
  if ($content === FALSE) {
    continue;
  }

  if (preg_match_all('/^  ([a-zA-Z_][a-zA-Z0-9_.]+):\s*$/m', $content, $matches)) {
    foreach ($matches[1] as $serviceId) {
      $serviceToModule[$serviceId] = $moduleName;
    }
  }
}

// ─────────────────────────────────────────────────────────────
// Step 2: Determine which core module is "universal" (always installed).
// ─────────────────────────────────────────────────────────────
$universalModules = [
  'ecosistema_jaraba_core',
];

// Submodule-to-parent mapping.
$parentModule = [];
foreach ($submoduleDirs as $dir) {
  $subName = basename(rtrim($dir, '/'));
  // Parent is 2 levels up: modules/custom/parent/modules/sub/
  $parentName = basename(dirname(dirname(rtrim($dir, '/'))));
  $parentModule[$subName] = $parentName;
}

// ─────────────────────────────────────────────────────────────
// Step 3: Scan services.yml for hard cross-module references.
// ─────────────────────────────────────────────────────────────
$violations = [];

foreach ($serviceFiles as $file) {
  $ownerModule = basename(dirname($file));
  $content = file_get_contents($file);
  if ($content === FALSE) {
    continue;
  }

  $lines = explode("\n", $content);
  $currentService = NULL;
  $inServices = FALSE;
  $lineNum = 0;

  foreach ($lines as $line) {
    $lineNum++;

    if (preg_match('/^services:\s*$/', $line)) {
      $inServices = TRUE;
      continue;
    }

    if (!$inServices) {
      continue;
    }

    if (preg_match('/^  ([a-zA-Z_][a-zA-Z0-9_.]+):\s*$/', $line, $m)) {
      $currentService = $m[1];
      if (str_starts_with($currentService, '_')) {
        $currentService = NULL;
      }
      continue;
    }

    if ($currentService === NULL) {
      continue;
    }

    // End of services section.
    if ($line !== '' && !str_starts_with($line, ' ') && !str_starts_with($line, '#')) {
      $inServices = FALSE;
      $currentService = NULL;
      continue;
    }

    // Find hard @ references (not @?).
    if (preg_match_all('/@([a-zA-Z_][a-zA-Z0-9_.]+)/', $line, $matches, PREG_OFFSET_CAPTURE)) {
      foreach ($matches[1] as [$ref, $offset]) {
        // Check if this is actually an optional reference (@?).
        $atPos = $offset - 1; // Position of the @ in the line.
        if ($atPos > 0 && $line[$atPos - 1] === '?') {
          // Actually it's @?ref, not @ref — skip.
          continue;
        }
        // More reliable check: look for '@?' prefix.
        if (str_contains($line, '@?' . $ref)) {
          continue;
        }

        // Determine which module owns the referenced service.
        $refModule = $serviceToModule[$ref] ?? NULL;
        if ($refModule === NULL) {
          // Not a custom service — it's core/contrib, skip.
          continue;
        }

        // Same module — OK.
        if ($refModule === $ownerModule) {
          continue;
        }

        // Universal modules — OK.
        if (in_array($refModule, $universalModules, TRUE)) {
          continue;
        }

        // Submodule referencing parent — OK.
        if (isset($parentModule[$ownerModule]) && $parentModule[$ownerModule] === $refModule) {
          continue;
        }

        // Parent referencing submodule — also OK (they're co-packaged).
        if (isset($parentModule[$refModule]) && $parentModule[$refModule] === $ownerModule) {
          continue;
        }

        $relFile = str_replace($projectRoot . '/', '', $file);
        $violations[] = [
          'file' => $relFile,
          'line' => $lineNum,
          'service' => $currentService,
          'reference' => $ref,
          'owner_module' => $ownerModule,
          'ref_module' => $refModule,
        ];
      }
    }
  }
}

// ─────────────────────────────────────────────────────────────
// Output.
// ─────────────────────────────────────────────────────────────
echo "\n";
echo "=== OPTIONAL-CROSSMODULE-001: Cross-module hard dependency detection ===\n";
echo "  Services files scanned: " . count($serviceFiles) . "\n";
echo "  Custom modules: " . count($moduleNames) . "\n";
echo "\n";

if (!empty($violations)) {
  echo "  [FAIL] Hard cross-module dependencies found:\n";
  foreach ($violations as $v) {
    echo "    {$v['file']}:{$v['line']}\n";
    echo "      Service: {$v['service']} (module: {$v['owner_module']})\n";
    echo "      References: @{$v['reference']} (module: {$v['ref_module']})\n";
    echo "      Fix: Change to @?{$v['reference']}\n";
    echo "\n";
  }
  echo "  " . count($violations) . " hard cross-module dependency(ies) found.\n";
  echo "  Cross-module services SHOULD use optional injection (@?) to avoid\n";
  echo "  container compilation failures when modules are not installed.\n";
  echo "\n";
  exit(1);
}

echo "  OK: All cross-module dependencies use optional injection (@?).\n";
echo "\n";
exit(0);
