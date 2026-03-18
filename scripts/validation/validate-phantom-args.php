<?php

/**
 * @file
 * PHANTOM-ARG-001: Detect services.yml arguments that don't match constructor params.
 *
 * Compares the number of arguments declared in services.yml with the number of
 * constructor parameters in the PHP class. Detects TWO failure modes:
 *
 * 1. PHANTOM args (YAML > constructor): "Too many arguments" TypeError.
 * 2. MISSING args (YAML < required params): "Too few arguments" TypeError.
 *    This is the more insidious bug because $container->has() returns TRUE
 *    but $container->get() throws, silently breaking transitive dependencies
 *    (e.g., a missing @current_user in LegalSearchService breaking the
 *    entire CopilotOrchestratorService chain — 2026-03-17 incident).
 *
 * Usage: php scripts/validation/validate-phantom-args.php
 * Exit:  0 = clean, 1 = mismatches found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

$errors = [];
$checked = 0;

// Find all services.yml files.
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
  if (!preg_match('/\.services\.yml$/', $file->getFilename())) {
    continue;
  }

  $yamlContent = file_get_contents($file->getPathname());
  if ($yamlContent === FALSE) {
    continue;
  }

  // Parse YAML manually — avoid symfony/yaml dependency for standalone usage.
  // Extract service definitions with class + arguments.
  $services = parseServicesYaml($yamlContent);

  foreach ($services as $serviceId => $serviceInfo) {
    $className = $serviceInfo['class'] ?? NULL;
    $argCount = $serviceInfo['arg_count'] ?? 0;

    if (!$className || $argCount === 0) {
      continue;
    }

    // Convert Drupal namespace to file path.
    $classFile = resolveClassFile($className, $modulesDir);
    if (!$classFile || !file_exists($classFile)) {
      continue;
    }

    $checked++;
    $paramInfo = countConstructorParams($classFile);

    if ($paramInfo === NULL) {
      // No constructor found — skip (might use parent).
      continue;
    }

    $totalParams = $paramInfo['total'];
    $requiredParams = $paramInfo['required'];

    // Case 1: Too MANY args in YAML (phantom args).
    if ($argCount > $totalParams) {
      $errors[] = sprintf(
        "  PHANTOM-ARG-001: %s\n    Service: %s\n    YAML args: %d, Constructor params: %d (%d phantom args)\n    File: %s",
        basename($file->getPathname()),
        $serviceId,
        $argCount,
        $totalParams,
        $argCount - $totalParams,
        $classFile
      );
    }
    // Case 2: Too FEW args in YAML (missing required args).
    elseif ($argCount < $requiredParams) {
      $errors[] = sprintf(
        "  PHANTOM-ARG-001: %s\n    Service: %s\n    YAML args: %d, Required constructor params: %d (%d missing args)\n    File: %s",
        basename($file->getPathname()),
        $serviceId,
        $argCount,
        $requiredParams,
        $requiredParams - $argCount,
        $classFile
      );
    }
  }
}

// Report results.
if (count($errors) > 0) {
  echo "PHANTOM-ARG-001 VIOLATIONS FOUND:\n\n";
  foreach ($errors as $error) {
    echo $error . "\n\n";
  }
  echo sprintf("Checked %d services, found %d violations.\n", $checked, count($errors));
  exit(1);
}

echo sprintf("PHANTOM-ARG-001: OK — %d services checked, 0 violations.\n", $checked);
exit(0);

// ============================================================================
// Helper functions.
// ============================================================================

/**
 * Parse services from YAML content (lightweight, no symfony/yaml).
 */
function parseServicesYaml(string $content): array {
  $services = [];
  $lines = explode("\n", $content);
  $inServices = FALSE;
  $currentService = NULL;
  $currentClass = NULL;
  $inArgs = FALSE;
  $argCount = 0;

  foreach ($lines as $line) {
    // Detect 'services:' top-level key.
    if (preg_match('/^services:\s*$/', $line)) {
      $inServices = TRUE;
      continue;
    }

    if (!$inServices) {
      continue;
    }

    // Top-level key outside services (like 'parameters:').
    if (preg_match('/^\S/', $line) && !preg_match('/^services:/', $line)) {
      // Save current service if any.
      if ($currentService && $currentClass) {
        $services[$currentService] = ['class' => $currentClass, 'arg_count' => $argCount];
      }
      $inServices = FALSE;
      continue;
    }

    // Service definition (2-space indent, not a comment).
    // Also matches FQCN keys like Drupal\module\Service\ClassName:
    if (preg_match('/^  ([a-zA-Z_][a-zA-Z0-9_.\\\\]+):\s*$/', $line, $m)) {
      // Save previous service.
      if ($currentService && $currentClass) {
        $services[$currentService] = ['class' => $currentClass, 'arg_count' => $argCount];
      }
      $currentService = $m[1];
      $currentClass = NULL;
      $inArgs = FALSE;
      $argCount = 0;
      continue;
    }

    // Class definition.
    if ($currentService && preg_match('/^\s{4}class:\s*(.+)$/', $line, $m)) {
      $currentClass = trim($m[1]);
      continue;
    }

    // Arguments start.
    if ($currentService && preg_match('/^\s{4}arguments:\s*$/', $line)) {
      $inArgs = TRUE;
      $argCount = 0;
      continue;
    }

    // Argument entry (6-space indent with dash).
    if ($inArgs && preg_match('/^\s{6}- /', $line)) {
      $argCount++;
      continue;
    }

    // End of arguments block (back to 4-space indent or less).
    if ($inArgs && preg_match('/^\s{4}\S/', $line)) {
      $inArgs = FALSE;
      continue;
    }
  }

  // Save last service.
  if ($currentService && $currentClass) {
    $services[$currentService] = ['class' => $currentClass, 'arg_count' => $argCount];
  }

  return $services;
}

/**
 * Resolve a Drupal class name to a file path.
 */
function resolveClassFile(string $className, string $modulesDir): ?string {
  // Convert Drupal\module_name\... to web/modules/custom/module_name/src/...
  if (!preg_match('/^Drupal\\\\([^\\\\]+)\\\\(.+)$/', $className, $m)) {
    return NULL;
  }

  $moduleName = $m[1];
  $classPath = str_replace('\\', '/', $m[2]);

  return $modulesDir . '/' . $moduleName . '/src/' . $classPath . '.php';
}

/**
 * Count constructor parameters in a PHP file.
 *
 * Returns both total params and required (non-optional) params.
 * A param is optional if it has a default value (= ...) or is nullable
 * with = NULL. This allows detecting both phantom (too many) and
 * missing (too few) arguments.
 *
 * @return array{total: int, required: int}|null
 *   NULL if no constructor found.
 */
function countConstructorParams(string $filePath): ?array {
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return NULL;
  }

  // Find __construct method and count parameters.
  // Handle multi-line constructors with promoted properties.
  if (!preg_match('/function\s+__construct\s*\(([^)]*(?:\([^)]*\)[^)]*)*)\)/s', $content, $m)) {
    return NULL;
  }

  $paramBlock = trim($m[1]);
  if ($paramBlock === '') {
    return ['total' => 0, 'required' => 0];
  }

  // Split parameters at top-level commas (not inside nested parens).
  $params = [];
  $depth = 0;
  $current = '';
  for ($i = 0; $i < strlen($paramBlock); $i++) {
    $char = $paramBlock[$i];
    if ($char === '(') {
      $depth++;
      $current .= $char;
    }
    elseif ($char === ')') {
      $depth--;
      $current .= $char;
    }
    elseif ($char === ',' && $depth === 0) {
      $params[] = trim($current);
      $current = '';
    }
    else {
      $current .= $char;
    }
  }
  $last = trim($current);
  if ($last !== '') {
    $params[] = $last;
  }

  $total = count($params);
  $required = 0;

  foreach ($params as $param) {
    // A param is optional if it has a default value assignment.
    // Patterns: `= NULL`, `= null`, `= 'value'`, `= []`, `= 0`, etc.
    if (!preg_match('/=\s*\S/', $param)) {
      $required++;
    }
  }

  return ['total' => $total, 'required' => $required];
}
