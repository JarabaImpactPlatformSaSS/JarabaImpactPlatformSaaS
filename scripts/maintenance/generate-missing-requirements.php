#!/usr/bin/env php
<?php

/**
 * @file
 * Generates hook_requirements() for modules with entities but missing runtime checks.
 *
 * Usage:
 *   php scripts/maintenance/generate-missing-requirements.php
 *   php scripts/maintenance/generate-missing-requirements.php --dry-run
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv, TRUE);
$modulesDir = $projectRoot . '/web/modules/custom';

// Discover modules with entities but no hook_requirements().
$targets = [];

foreach (new DirectoryIterator($modulesDir) as $dir) {
  if (!$dir->isDir() || $dir->isDot()) {
    continue;
  }

  $moduleName = $dir->getFilename();
  $modulePath = $dir->getPathname();
  $entityDir = $modulePath . '/src/Entity';
  $installFile = $modulePath . '/' . $moduleName . '.install';

  if (!is_dir($entityDir)) {
    continue;
  }

  // Check if hook_requirements already exists.
  if (file_exists($installFile)) {
    $content = file_get_contents($installFile);
    if (str_contains($content, $moduleName . '_requirements')) {
      continue;
    }
  }

  // Extract entity type IDs.
  $entityIds = [];
  foreach (glob($entityDir . '/*.php') as $entityFile) {
    $src = file_get_contents($entityFile);
    if (!preg_match('/@(Content|Config)EntityType\s*\(/', $src)) {
      continue;
    }
    if (preg_match('/\*\s*id\s*=\s*"([^"]+)"/', $src, $m)) {
      $entityIds[] = $m[1];
    }
  }

  if (empty($entityIds)) {
    continue;
  }

  sort($entityIds);
  $targets[$moduleName] = [
    'path' => $modulePath,
    'install_file' => $installFile,
    'entities' => $entityIds,
  ];
}

ksort($targets);

echo "Missing hook_requirements() Generator\n";
echo "======================================\n";
echo "Modules affected: " . count($targets) . "\n";
echo "Mode: " . ($dryRun ? 'DRY RUN' : 'WRITE') . "\n\n";

foreach ($targets as $moduleName => $data) {
  $count = count($data['entities']);
  echo "[$moduleName] $count entities: " . implode(', ', $data['entities']) . "\n";

  if ($dryRun) {
    continue;
  }

  $label = str_replace('_', ' ', ucfirst($moduleName));
  $idsPhp = implode("', '", $data['entities']);

  $code = <<<PHP


/**
 * Implements hook_requirements().
 */
function {$moduleName}_requirements(string \$phase): array {
  \$requirements = [];

  if (\$phase !== 'runtime') {
    return \$requirements;
  }

  \$entityTypes = ['$idsPhp'];
  \$updateManager = \\Drupal::entityDefinitionUpdateManager();
  \$missing = [];

  foreach (\$entityTypes as \$typeId) {
    if (!\$updateManager->getEntityType(\$typeId)) {
      \$missing[] = \$typeId;
    }
  }

  if (!empty(\$missing)) {
    \$requirements['{$moduleName}_entities'] = [
      'title' => t('$label: Entity Types'),
      'value' => t('Missing: @types', ['@types' => implode(', ', \$missing)]),
      'description' => t('Run drush entity:updates to install missing entity types.'),
      'severity' => REQUIREMENT_ERROR,
    ];
  }
  else {
    \$requirements['{$moduleName}_entities'] = [
      'title' => t('$label: Entity Types'),
      'value' => t('All @count entity types installed', ['@count' => count(\$entityTypes)]),
      'severity' => REQUIREMENT_OK,
    ];
  }

  return \$requirements;
}
PHP;

  file_put_contents($data['install_file'], file_get_contents($data['install_file']) . $code);
  echo "  -> Appended hook_requirements() to {$data['install_file']}\n";
}

echo "\n======================================\n";
echo "Done.\n";
