<?php

/**
 * @file
 * OPTIONAL-PARAM-ORDER-001: Detect optional constructor parameters before required ones.
 *
 * PHP 8.4 deprecates optional parameters declared before required parameters
 * in function/method signatures. This causes runtime deprecation warnings that
 * appear in /admin/reports/status and pollute logs.
 *
 * Pattern detected:
 *   function __construct(
 *     ?SomeType $optional = NULL,   // optional
 *     LoggerInterface $logger,       // required AFTER optional = VIOLATION
 *   )
 *
 * Scans all PHP classes in web/modules/custom/ referenced by services.yml,
 * plus any PHP file with a constructor in the modules directory.
 *
 * Usage: php scripts/validation/validate-optional-param-order.php
 * Exit:  0 = clean, 1 = violations found
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

// Recursively find all PHP files.
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
  if ($file->getExtension() !== 'php') {
    continue;
  }

  $filePath = $file->getPathname();
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    continue;
  }

  // Find __construct method.
  if (!preg_match('/function\s+__construct\s*\(([^)]*(?:\([^)]*\)[^)]*)*)\)/s', $content, $m)) {
    continue;
  }

  $paramBlock = trim($m[1]);
  if ($paramBlock === '') {
    continue;
  }

  $checked++;

  // Strip inline comments.
  $paramBlock = preg_replace('#//[^\n]*#', '', $paramBlock);
  $paramBlock = trim($paramBlock);

  // Split parameters at top-level commas.
  $params = splitParams($paramBlock);

  // Filter to actual parameters (must contain $).
  $params = array_values(array_filter($params, function (string $p): bool {
    return str_contains($p, '$');
  }));

  if (count($params) < 2) {
    continue;
  }

  // Check ordering: once we see an optional param, all subsequent must be optional.
  $lastOptionalIndex = -1;
  $firstRequiredAfterOptional = NULL;
  $optionalParamName = NULL;

  foreach ($params as $i => $param) {
    $isOptional = (bool) preg_match('/=\s*\S/', $param);

    if ($isOptional && $lastOptionalIndex < $i) {
      $lastOptionalIndex = $i;
      // Extract param name for reporting.
      if (preg_match('/\$(\w+)/', $param, $nm)) {
        $optionalParamName = '$' . $nm[1];
      }
    }

    if (!$isOptional && $lastOptionalIndex >= 0 && $lastOptionalIndex < $i && $firstRequiredAfterOptional === NULL) {
      $requiredName = '';
      if (preg_match('/\$(\w+)/', $param, $nm)) {
        $requiredName = '$' . $nm[1];
      }
      $firstRequiredAfterOptional = [
        'required_param' => $requiredName,
        'required_index' => $i,
        'optional_param' => $optionalParamName,
        'optional_index' => $lastOptionalIndex,
      ];
    }
  }

  if ($firstRequiredAfterOptional !== NULL) {
    $relPath = str_replace($projectRoot . '/', '', $filePath);
    $errors[] = sprintf(
      "  OPTIONAL-PARAM-ORDER-001: %s\n    Required param %s (pos %d) after optional %s (pos %d)\n    Fix: move all required params before optional ones",
      $relPath,
      $firstRequiredAfterOptional['required_param'],
      $firstRequiredAfterOptional['required_index'],
      $firstRequiredAfterOptional['optional_param'],
      $firstRequiredAfterOptional['optional_index']
    );
  }
}

// Report results.
if (count($errors) > 0) {
  echo "OPTIONAL-PARAM-ORDER-001 VIOLATIONS FOUND:\n\n";
  foreach ($errors as $error) {
    echo $error . "\n\n";
  }
  echo sprintf("Checked %d constructors, found %d violations.\n", $checked, count($errors));
  exit(1);
}

echo sprintf("OPTIONAL-PARAM-ORDER-001: OK — %d constructors checked, 0 violations.\n", $checked);
exit(0);

// ============================================================================
// Helper functions.
// ============================================================================

/**
 * Split parameter string at top-level commas (respecting nested parens).
 *
 * @return string[]
 */
function splitParams(string $paramBlock): array {
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

  return $params;
}
