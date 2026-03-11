<?php

/**
 * @file
 * SAFEGUARD: Backup and restore canvas_data for page_content entities.
 *
 * Usage (via drush):
 *   drush php:script scripts/maintenance/backup-canvas.php -- --action=backup
 *   drush php:script scripts/maintenance/backup-canvas.php -- --action=backup --pages=87
 *   drush php:script scripts/maintenance/backup-canvas.php -- --action=restore --pages=87 --snapshot=2026-03-10_143000
 *   drush php:script scripts/maintenance/backup-canvas.php -- --action=list
 *   drush php:script scripts/maintenance/backup-canvas.php -- --action=diff --pages=87 --snapshot=2026-03-10_143000
 *
 * Snapshots are stored in scripts/maintenance/canvas-snapshots/ as JSON files.
 * Each snapshot contains: entity ID, langcode, canvas_data hash, canvas_data length,
 * title, path_alias, and the full canvas_data.
 *
 * SAFEGUARD-CANVAS-001: Always run backup BEFORE any bulk canvas operation.
 */

declare(strict_types=1);

// Parse CLI arguments.
$args = [];
foreach ($extra ?? [] as $arg) {
  if (str_starts_with($arg, '--')) {
    $parts = explode('=', substr($arg, 2), 2);
    $args[$parts[0]] = $parts[1] ?? TRUE;
  }
}

$action = $args['action'] ?? 'backup';
$pageFilter = isset($args['pages']) ? array_map('intval', explode(',', $args['pages'])) : [];
$snapshotName = $args['snapshot'] ?? '';

$snapshotDir = dirname(__DIR__) . '/maintenance/canvas-snapshots';
if (!is_dir($snapshotDir)) {
  mkdir($snapshotDir, 0755, TRUE);
}

$storage = \Drupal::entityTypeManager()->getStorage('page_content');

switch ($action) {
  case 'backup':
    doBackup($storage, $snapshotDir, $pageFilter);
    break;

  case 'restore':
    doRestore($storage, $snapshotDir, $snapshotName, $pageFilter);
    break;

  case 'list':
    doList($snapshotDir);
    break;

  case 'diff':
    doDiff($storage, $snapshotDir, $snapshotName, $pageFilter);
    break;

  default:
    echo "ERROR: Unknown action '$action'. Use: backup, restore, list, diff\n";
}

/**
 * Backup all page_content canvas_data (all languages).
 */
function doBackup($storage, string $snapshotDir, array $pageFilter): void {
  $timestamp = date('Y-m-d_His');
  $snapshotFile = "$snapshotDir/canvas-snapshot-$timestamp.json";

  $query = $storage->getQuery()->accessCheck(FALSE);
  if (!empty($pageFilter)) {
    $query->condition('id', $pageFilter, 'IN');
  }
  $ids = $query->execute();

  $snapshot = [
    'timestamp' => $timestamp,
    'created_at' => date('c'),
    'entity_count' => 0,
    'translation_count' => 0,
    'entities' => [],
  ];

  foreach ($storage->loadMultiple($ids) as $entity) {
    $id = (int) $entity->id();
    $entityData = [];

    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $translation = $entity->getTranslation($langcode);
      $canvasData = $translation->get('canvas_data')->value ?? '';
      $title = $translation->get('title')->value ?? '';
      $pathAlias = $translation->get('path_alias')->value ?? '';

      $entityData[$langcode] = [
        'title' => $title,
        'path_alias' => $pathAlias,
        'canvas_length' => strlen($canvasData),
        'canvas_hash' => md5($canvasData),
        'canvas_data' => $canvasData,
      ];
      $snapshot['translation_count']++;
    }

    $snapshot['entities'][$id] = $entityData;
    $snapshot['entity_count']++;
  }

  file_put_contents($snapshotFile, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

  echo "SUCCESS: Backup created → $snapshotFile\n";
  echo "  Entities: {$snapshot['entity_count']}\n";
  echo "  Translations: {$snapshot['translation_count']}\n";
  echo "  File size: " . number_format(filesize($snapshotFile)) . " bytes\n";
}

/**
 * Restore canvas_data from a snapshot.
 */
function doRestore($storage, string $snapshotDir, string $snapshotName, array $pageFilter): void {
  if (empty($snapshotName)) {
    echo "ERROR: --snapshot=YYYY-MM-DD_HHMMSS required for restore\n";
    return;
  }

  $snapshotFile = "$snapshotDir/canvas-snapshot-$snapshotName.json";
  if (!file_exists($snapshotFile)) {
    echo "ERROR: Snapshot not found: $snapshotFile\n";
    return;
  }

  $snapshot = json_decode(file_get_contents($snapshotFile), TRUE);
  if (!$snapshot) {
    echo "ERROR: Invalid snapshot JSON\n";
    return;
  }

  echo "Restoring from snapshot: {$snapshot['timestamp']} ({$snapshot['created_at']})\n";
  echo "  Source: {$snapshot['entity_count']} entities, {$snapshot['translation_count']} translations\n\n";

  $restored = 0;
  $skipped = 0;

  foreach ($snapshot['entities'] as $id => $translations) {
    $id = (int) $id;
    if (!empty($pageFilter) && !in_array($id, $pageFilter, TRUE)) {
      continue;
    }

    $entity = $storage->load($id);
    if (!$entity) {
      echo "  SKIP: Entity $id not found (deleted?)\n";
      $skipped++;
      continue;
    }

    foreach ($translations as $langcode => $data) {
      if (!$entity->hasTranslation($langcode)) {
        echo "  SKIP: Entity $id/$langcode — translation doesn't exist\n";
        $skipped++;
        continue;
      }

      $translation = $entity->getTranslation($langcode);
      $currentHash = md5($translation->get('canvas_data')->value ?? '');

      if ($currentHash === $data['canvas_hash']) {
        echo "  UNCHANGED: Entity $id/$langcode (hash match)\n";
        $skipped++;
        continue;
      }

      $translation->set('canvas_data', $data['canvas_data']);
      $translation->set('title', $data['title']);
      $translation->setSyncing(TRUE);
      $translation->save();

      // Reload entity after save to avoid cross-language contamination.
      $storage->resetCache([$id]);
      $entity = $storage->load($id);

      echo "  RESTORED: Entity $id/$langcode — {$data['title']} ({$data['canvas_length']} chars)\n";
      $restored++;
    }
  }

  echo "\nDone: $restored restored, $skipped skipped\n";
}

/**
 * List available snapshots.
 */
function doList(string $snapshotDir): void {
  $files = glob("$snapshotDir/canvas-snapshot-*.json");
  if (empty($files)) {
    echo "No snapshots found in $snapshotDir\n";
    return;
  }

  echo "Available snapshots:\n\n";
  foreach ($files as $file) {
    $data = json_decode(file_get_contents($file), TRUE);
    $size = filesize($file);
    $name = basename($file, '.json');
    $timestamp = str_replace('canvas-snapshot-', '', $name);

    echo "  $timestamp — {$data['entity_count']} entities, {$data['translation_count']} translations, " .
      number_format($size) . " bytes\n";
  }
}

/**
 * Show diff between current state and a snapshot.
 */
function doDiff($storage, string $snapshotDir, string $snapshotName, array $pageFilter): void {
  if (empty($snapshotName)) {
    echo "ERROR: --snapshot=YYYY-MM-DD_HHMMSS required for diff\n";
    return;
  }

  $snapshotFile = "$snapshotDir/canvas-snapshot-$snapshotName.json";
  if (!file_exists($snapshotFile)) {
    echo "ERROR: Snapshot not found: $snapshotFile\n";
    return;
  }

  $snapshot = json_decode(file_get_contents($snapshotFile), TRUE);
  echo "Comparing current state with snapshot: {$snapshot['timestamp']}\n\n";

  $changed = 0;
  $unchanged = 0;

  foreach ($snapshot['entities'] as $id => $translations) {
    $id = (int) $id;
    if (!empty($pageFilter) && !in_array($id, $pageFilter, TRUE)) {
      continue;
    }

    $entity = $storage->load($id);
    if (!$entity) {
      echo "  DELETED: Entity $id\n";
      $changed++;
      continue;
    }

    foreach ($translations as $langcode => $data) {
      if (!$entity->hasTranslation($langcode)) {
        echo "  MISSING: Entity $id/$langcode translation removed\n";
        $changed++;
        continue;
      }

      $translation = $entity->getTranslation($langcode);
      $currentCanvas = $translation->get('canvas_data')->value ?? '';
      $currentHash = md5($currentCanvas);

      if ($currentHash === $data['canvas_hash']) {
        $unchanged++;
        continue;
      }

      $lenDiff = strlen($currentCanvas) - $data['canvas_length'];
      $pctDiff = $data['canvas_length'] > 0 ? round(($lenDiff / $data['canvas_length']) * 100, 1) : 0;
      $direction = $lenDiff >= 0 ? "+$lenDiff" : "$lenDiff";

      echo "  CHANGED: Entity $id/$langcode — \"{$data['title']}\"\n";
      echo "           Canvas: {$data['canvas_length']} → " . strlen($currentCanvas) . " ($direction chars, {$pctDiff}%)\n";
      $changed++;
    }
  }

  echo "\n$changed changed, $unchanged unchanged\n";
}
