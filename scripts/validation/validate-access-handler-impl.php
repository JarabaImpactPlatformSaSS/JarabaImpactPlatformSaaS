<?php

/**
 * @file
 * ACCESS-HANDLER-IMPL-001: Verify AccessControlHandlers implement tenant verification.
 *
 * For every ContentEntity that defines a tenant_id field in baseFieldDefinitions(),
 * the corresponding AccessControlHandler's checkAccess() method MUST contain
 * tenant verification logic (TENANT-ISOLATION-ACCESS-001).
 *
 * Usage: php scripts/validation/validate-access-handler-impl.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "[ERROR] $modulesDir not found\n");
  exit(1);
}

$errors = [];
$checked = 0;
$skipped = 0;

/**
 * Recursively find all PHP files in a directory.
 */
function findPhpFiles(string $dir): array {
  $files = [];
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY,
  );
  foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
      $files[] = $file->getPathname();
    }
  }
  return $files;
}

/**
 * Extract the access handler class from a ContentEntityType annotation.
 *
 * Parses the "access" = "Drupal\...\SomeAccessControlHandler" line.
 */
function extractAccessHandlerClass(string $content): ?string {
  // Match "access" = "Drupal\namespace\ClassName" in annotation.
  if (preg_match('/"access"\s*=\s*"(Drupal\\\\[^"]+)"/', $content, $matches)) {
    return $matches[1];
  }
  return NULL;
}

/**
 * Check if entity defines tenant_id in baseFieldDefinitions().
 */
function hasTenantIdField(string $content): bool {
  // Must have baseFieldDefinitions method.
  if (strpos($content, 'baseFieldDefinitions') === false) {
    return false;
  }

  // Look for tenant_id field definition patterns.
  // Pattern 1: $fields['tenant_id']
  if (preg_match('/\$fields\s*\[\s*[\'"]tenant_id[\'"]\s*\]/', $content)) {
    return true;
  }

  return false;
}

/**
 * Resolve a fully qualified class name to a file path.
 *
 * Converts Drupal\module\Namespace\Class to web/modules/custom/module/src/Namespace/Class.php
 */
function resolveClassToFile(string $fqcn, string $modulesDir): ?string {
  // Remove leading backslash.
  $fqcn = ltrim($fqcn, '\\');

  // Must start with Drupal\.
  if (strpos($fqcn, 'Drupal\\') !== 0) {
    return null;
  }

  $parts = explode('\\', $fqcn);
  // $parts[0] = 'Drupal', $parts[1] = module_name, rest = path.
  if (count($parts) < 3) {
    return null;
  }

  $moduleName = $parts[1];
  $classParts = array_slice($parts, 2);
  $relativePath = implode('/', $classParts) . '.php';

  // Try direct module path.
  $filePath = $modulesDir . '/' . $moduleName . '/src/' . $relativePath;
  if (file_exists($filePath)) {
    return $filePath;
  }

  // Try as submodule: scan parent modules for modules/$moduleName.
  $parentDirs = glob($modulesDir . '/*/modules/' . $moduleName);
  if ($parentDirs) {
    foreach ($parentDirs as $parentDir) {
      $filePath = $parentDir . '/src/' . $relativePath;
      if (file_exists($filePath)) {
        return $filePath;
      }
    }
  }

  return null;
}

/**
 * Check if an AccessControlHandler's checkAccess() has tenant verification.
 *
 * Looks for any of:
 * - 'tenant' string reference (variable, method, field access)
 * - getCurrentTenant / getTenantId call
 * - ->get('tenant_id') or ->tenant_id
 * - TenantContextService usage
 * - isSameTenant method call
 */
function hasCheckAccessTenantLogic(string $content): bool {
  // First, extract checkAccess method body.
  // We look for the method and scan until we find its closing brace.
  $checkAccessPos = strpos($content, 'function checkAccess(');
  if ($checkAccessPos === false) {
    // No checkAccess method — might delegate to parent. We'll flag this.
    return false;
  }

  // Extract from checkAccess to a reasonable depth (next method or class end).
  $methodBody = substr($content, $checkAccessPos);

  // Simple brace counting to extract method body.
  $braceCount = 0;
  $started = false;
  $methodEnd = strlen($methodBody);
  for ($i = 0, $len = strlen($methodBody); $i < $len; $i++) {
    $char = $methodBody[$i];
    if ($char === '{') {
      $braceCount++;
      $started = true;
    }
    elseif ($char === '}') {
      $braceCount--;
      if ($started && $braceCount === 0) {
        $methodEnd = $i + 1;
        break;
      }
    }
  }

  $methodBody = substr($methodBody, 0, $methodEnd);

  // Check for tenant verification patterns in the method body.
  $patterns = [
    '/tenant/i',
    '/getCurrentTenant/',
    '/getTenantId/',
    '/get\s*\(\s*[\'"]tenant_id[\'"]\s*\)/',
    '/->tenant_id/',
    '/TenantContext/',
    '/TenantBridge/',
    '/isSameTenant/',
    '/tenant_match/',
    '/tenantContext/',
    '/tenantBridge/',
  ];

  foreach ($patterns as $pattern) {
    if (preg_match($pattern, $methodBody)) {
      return true;
    }
  }

  return false;
}

/**
 * Get a short relative path for display.
 */
function shortPath(string $fullPath, string $projectRoot): string {
  return str_replace($projectRoot . '/', '', $fullPath);
}

// --- Main execution ---

// Step 1: Find all entity files that are ContentEntityType annotated.
$allPhpFiles = findPhpFiles($modulesDir);
$entityFiles = [];

foreach ($allPhpFiles as $file) {
  $content = file_get_contents($file);
  if ($content === false) {
    continue;
  }

  // Must be a ContentEntityType annotation.
  if (strpos($content, '@ContentEntityType') === false) {
    continue;
  }

  // Must have an access handler declared.
  $handlerClass = extractAccessHandlerClass($content);
  if ($handlerClass === null) {
    continue;
  }

  // Must have tenant_id field.
  if (!hasTenantIdField($content)) {
    continue;
  }

  $entityFiles[] = [
    'entity_file' => $file,
    'handler_class' => $handlerClass,
    'content' => $content,
  ];
}

// Step 2: For each tenant-scoped entity, check its handler.
foreach ($entityFiles as $entry) {
  $entityFile = $entry['entity_file'];
  $handlerClass = $entry['handler_class'];

  $handlerFile = resolveClassToFile($handlerClass, $modulesDir);

  if ($handlerFile === null) {
    $skipped++;
    fwrite(STDERR, "[WARN] Cannot resolve handler class $handlerClass for entity " . shortPath($entityFile, $projectRoot) . "\n");
    continue;
  }

  $handlerContent = file_get_contents($handlerFile);
  if ($handlerContent === false) {
    $skipped++;
    fwrite(STDERR, "[WARN] Cannot read handler file $handlerFile\n");
    continue;
  }

  $checked++;

  if (!hasCheckAccessTenantLogic($handlerContent)) {
    $errors[] = sprintf(
      "  %s\n    Handler: %s\n    Entity has tenant_id but checkAccess() lacks tenant verification (TENANT-ISOLATION-ACCESS-001)",
      shortPath($entityFile, $projectRoot),
      shortPath($handlerFile, $projectRoot),
    );
  }
}

// --- Output ---

if (count($errors) > 0) {
  fwrite(STDERR, "\n[ERROR] ACCESS-HANDLER-IMPL-001: " . count($errors) . " handler(s) missing tenant verification:\n\n");
  foreach ($errors as $error) {
    fwrite(STDERR, $error . "\n\n");
  }
  fwrite(STDERR, "Checked: $checked | Violations: " . count($errors) . " | Skipped: $skipped\n");
  exit(1);
}

echo "ACCESS-HANDLER-IMPL-001: OK — $checked handler(s) verified, all tenant-scoped entities have tenant logic in checkAccess(). Skipped: $skipped\n";
exit(0);
