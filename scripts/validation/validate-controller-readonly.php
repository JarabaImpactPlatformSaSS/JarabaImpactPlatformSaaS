<?php

/**
 * @file
 * CONTROLLER-READONLY-001: Detect controllers with readonly on inherited properties.
 *
 * ControllerBase::$entityTypeManager has no type/readonly declaration in Drupal 11.
 * Subclasses MUST NOT use constructor promotion with `readonly` for inherited
 * properties. This causes PHP 8.4 TypeError at runtime.
 *
 * Usage: php scripts/validation/validate-controller-readonly.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

echo "=== CONTROLLER-READONLY-001: Readonly inherited property detection ===\n";

$violations = [];
$checked = 0;

// Inherited properties from ControllerBase that MUST NOT be readonly.
$inheritedProperties = [
  'entityTypeManager',
  'entityFormBuilder',
  'formBuilder',
  'currentUser',
  'languageManager',
  'configFactory',
  'keyValue',
  'stateService',
  'moduleHandler',
];

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
  if ($file->getExtension() !== 'php') {
    continue;
  }

  $content = file_get_contents($file->getPathname());

  // Only check files that extend ControllerBase.
  if (!preg_match('/extends\s+ControllerBase\b/', $content)) {
    continue;
  }

  $checked++;
  $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());

  // Check constructor promotion for readonly on inherited properties.
  // Match: __construct( ... readonly TypeHint $entityTypeManager ... )
  if (preg_match('/__construct\s*\(([^)]*)\)/s', $content, $constructorMatch)) {
    $params = $constructorMatch[1];

    foreach ($inheritedProperties as $prop) {
      // Match readonly in any position before the property name.
      if (preg_match('/\breadonly\b[^,]*\$' . $prop . '\b/', $params)) {
        $violations[] = "    $relativePath: readonly \$$prop in constructor promotion (inherited from ControllerBase)";
      }
    }
  }
}

echo "  Controllers checked: $checked\n";

if (empty($violations)) {
  echo "\n  \033[0;32mOK: No controllers redeclare inherited properties as readonly.\033[0m\n";
  exit(0);
}

echo "\n  \033[0;31mVIOLATIONS (" . count($violations) . "):\033[0m\n";
foreach ($violations as $v) {
  echo "$v\n";
}
echo "\n  Fix: Remove 'readonly' from constructor promotion for inherited properties.\n";
echo "  Assign manually: \$this->entityTypeManager = \$entityTypeManager;\n";
exit(1);
