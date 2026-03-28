<?php

/**
 * @file
 * Validator: PUBLIC-FILES-SEED-001 — Detects entity file references to missing files.
 *
 * Scans key content entities (SuccessCase, PageContent, ContentArticle) for
 * image/file field references where the File entity exists in the DB but
 * the physical file does NOT exist on disk. This catches the gap between
 * "entity references a file" and "the file is actually there".
 *
 * Root cause this prevents: files in public:// are gitignored. When entities
 * are seeded (content-seed pipeline) they may reference files that only exist
 * in the dev environment but not in production.
 *
 * Uso: php scripts/validation/validate-public-files-seed.php
 * Requiere: Drupal bootstrap (lando drush php:script)
 * CI: warn (requires DB + filesystem)
 *
 * @see IMG-SEED-001
 * @see CONTENT-SEED-PIPELINE-001
 */

declare(strict_types=1);

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Bootstrap Drupal.
if (!class_exists('Drupal') || !\Drupal::hasContainer()) {
  $autoloader = require_once 'autoload.php';
  $kernel = new DrupalKernel('prod', $autoloader);
  $request = Request::createFromGlobals();
  $kernel->boot();
  $kernel->preHandle($request);
}

$entityTypeManager = \Drupal::entityTypeManager();
$fileSystem = \Drupal::service('file_system');

// Entity types and their image/file fields to check.
$entityFieldMap = [
  'success_case' => ['hero_image', 'protagonist_image', 'before_after_image', 'discovery_image', 'dashboard_image'],
  'page_content' => ['hero_image', 'og_image'],
  'content_article' => ['hero_image'],
];

$totalChecked = 0;
$totalMissing = 0;
$totalOk = 0;
$violations = [];

echo "PUBLIC-FILES-SEED-001: Checking entity file references vs physical files...\n";

foreach ($entityFieldMap as $entityType => $fields) {
  // Check if entity type exists.
  if (!$entityTypeManager->hasDefinition($entityType)) {
    echo "  [SKIP] Entity type '$entityType' not installed.\n";
    continue;
  }

  $storage = $entityTypeManager->getStorage($entityType);
  $entities = $storage->loadMultiple();

  if (empty($entities)) {
    echo "  [SKIP] No '$entityType' entities found.\n";
    continue;
  }

  foreach ($entities as $entity) {
    $label = method_exists($entity, 'label') ? ($entity->label() ?? $entity->id()) : $entity->id();

    foreach ($fields as $field) {
      if (!$entity->hasField($field)) {
        continue;
      }
      if ($entity->get($field)->isEmpty()) {
        continue;
      }

      $totalChecked++;
      $fileEntity = $entity->get($field)->entity;

      if (!$fileEntity) {
        $violations[] = sprintf(
          '  [ORPHAN] %s #%s "%s" — field %s references non-existent File entity',
          $entityType, $entity->id(), $label, $field
        );
        $totalMissing++;
        continue;
      }

      $uri = $fileEntity->getFileUri();
      $realPath = $fileSystem->realpath($uri);

      if (!$realPath || !file_exists($realPath)) {
        $violations[] = sprintf(
          '  [MISSING] %s #%s "%s" — field %s → %s (file not on disk)',
          $entityType, $entity->id(), $label, $field, $uri
        );
        $totalMissing++;
      }
      else {
        $totalOk++;
      }
    }
  }
}

// Results.
echo sprintf("  Checked: %d file references | OK: %d | Missing: %d\n",
  $totalChecked, $totalOk, $totalMissing);

if (empty($violations)) {
  echo "  [PASS] All entity file references point to existing physical files.\n";
  exit(0);
}
else {
  echo sprintf("  [WARN] %d file(s) referenced but missing on disk:\n", $totalMissing);
  foreach ($violations as $v) {
    echo $v . "\n";
  }
  echo "\n  Fix: Run 'drush php:script scripts/migration/seed-success-case-images.php'\n";
  echo "  Or upload missing files via admin UI.\n";
  exit(0); // Warn only — not blocking.
}
