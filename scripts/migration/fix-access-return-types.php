<?php

declare(strict_types=1);

/**
 * @file
 * ACCESS-RETURN-TYPE-BULK-001: Add : AccessResultInterface return type to checkAccess().
 *
 * Scans all AccessControlHandler files and adds the missing return type
 * declaration to checkAccess() methods. Also ensures the use statement exists.
 *
 * Usage:
 *   php scripts/migration/fix-access-return-types.php [--dry-run]
 *
 * Options:
 *   --dry-run   Show what would be changed without modifying files.
 */

$projectRoot = dirname(__DIR__, 2);
$dryRun = in_array('--dry-run', $argv ?? [], true);

if ($dryRun) {
  echo "=== DRY RUN MODE — No files will be modified ===\n\n";
}

$useStatement = 'use Drupal\Core\Access\AccessResultInterface;';

$files = [];
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($projectRoot . '/web/modules/custom')
);

foreach ($iterator as $file) {
  if ($file->isFile() && str_ends_with($file->getFilename(), 'AccessControlHandler.php')) {
    $files[] = $file->getPathname();
  }
}

echo "Found " . count($files) . " AccessControlHandler files.\n\n";

$fixed = 0;
$alreadyOk = 0;
$errors = [];

foreach ($files as $filePath) {
  $content = file_get_contents($filePath);
  if ($content === false) {
    $errors[] = "Cannot read: $filePath";
    continue;
  }

  $relativePath = str_replace($projectRoot . '/', '', $filePath);
  $modified = false;

  // Check if checkAccess already has return type.
  if (preg_match('/protected function checkAccess\([^)]*\)\s*:\s*AccessResultInterface/', $content)) {
    $alreadyOk++;
    continue;
  }

  // Check if checkAccess exists without return type.
  if (!preg_match('/protected function checkAccess\(/', $content)) {
    // No checkAccess method - skip.
    $alreadyOk++;
    continue;
  }

  // Step 1: Add use statement if missing.
  if (strpos($content, 'AccessResultInterface') === false
    || (strpos($content, 'use Drupal\Core\Access\AccessResultInterface;') === false
      && strpos($content, 'use Drupal\\Core\\Access\\AccessResultInterface;') === false)) {
    // Find the last use statement and add after it.
    if (preg_match('/^(use\s+[^;]+;)\s*$/m', $content, $lastUseMatch, PREG_OFFSET_CAPTURE)) {
      // Find ALL use statements and insert after the last one.
      preg_match_all('/^use\s+[^;]+;\s*$/m', $content, $allUses, PREG_OFFSET_CAPTURE);
      if (!empty($allUses[0])) {
        $lastUse = end($allUses[0]);
        $insertPos = $lastUse[1] + strlen($lastUse[0]);
        $content = substr($content, 0, $insertPos) . $useStatement . "\n" . substr($content, $insertPos);
        $modified = true;
      }
    }
  }

  // Step 2: Add return type to checkAccess.
  // Match: protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
  // Replace with: protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
  $pattern = '/(protected function checkAccess\([^)]*\))\s*(\{)/';
  if (preg_match($pattern, $content) && !preg_match('/checkAccess\([^)]*\)\s*:\s*AccessResultInterface/', $content)) {
    $content = preg_replace(
      $pattern,
      '$1: AccessResultInterface $2',
      $content
    );
    $modified = true;
  }

  if ($modified) {
    $fixed++;
    if ($dryRun) {
      echo "  WOULD FIX: $relativePath\n";
    }
    else {
      file_put_contents($filePath, $content);
      echo "  FIXED: $relativePath\n";
    }
  }
  else {
    $alreadyOk++;
  }
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULT: $fixed fixed, $alreadyOk already OK, " . count($errors) . " errors\n";
echo "═══════════════════════════════════════════════════════════\n";

if (!empty($errors)) {
  echo "\nErrors:\n";
  foreach ($errors as $error) {
    echo "  $error\n";
  }
}

exit(empty($errors) ? 0 : 1);
