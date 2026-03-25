<?php

declare(strict_types=1);

/**
 * @file
 * PHP-DECLARE-ORDER-001: declare(strict_types=1) MUST be before use statements.
 *
 * PHPCBF can reorder declare(strict_types=1) after @file docblocks, which is
 * valid PHP. BUT if a `use` statement appears between <?php and declare(),
 * PHP throws a fatal: "strict_types declaration must be the very first
 * statement in the script."
 *
 * This validator detects that specific broken ordering.
 *
 * Usage: php scripts/validation/validate-php-declare-order.php
 * Exit: 0 = PASS, 1 = FAIL
 */

$projectRoot = dirname(__DIR__, 2);
$customDir = $projectRoot . '/web/modules/custom';

$violations = [];
$checked = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($customDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
  $ext = $file->getExtension();
  if (!in_array($ext, ['php', 'install', 'module', 'inc'], TRUE)) {
    continue;
  }

  $path = $file->getPathname();

  // Skip test files and vendor.
  if (str_contains($path, '/vendor/') || str_contains($path, '/node_modules/')) {
    continue;
  }

  $content = file_get_contents($path);
  if ($content === FALSE) {
    continue;
  }

  // Only check files that HAVE declare(strict_types=1).
  if (!str_contains($content, 'declare(strict_types=1)')) {
    continue;
  }

  $checked++;
  $lines = explode("\n", $content);

  $declareLineNum = NULL;
  $firstUseLineNum = NULL;

  foreach ($lines as $i => $line) {
    $trimmed = trim($line);

    // Find first `use Xxx\Yyy;` statement (not use inside function body).
    if ($firstUseLineNum === NULL && preg_match('/^use\s+[A-Z\\\\]/', $trimmed)) {
      $firstUseLineNum = $i + 1;
    }

    // Find declare(strict_types=1).
    if ($declareLineNum === NULL && str_contains($trimmed, 'declare(strict_types=1)')) {
      $declareLineNum = $i + 1;
    }

    // Stop after finding both.
    if ($declareLineNum !== NULL && $firstUseLineNum !== NULL) {
      break;
    }
  }

  // Violation: use statement BEFORE declare(strict_types).
  if ($firstUseLineNum !== NULL && $declareLineNum !== NULL && $firstUseLineNum < $declareLineNum) {
    $relativePath = str_replace($projectRoot . '/', '', $path);
    $violations[] = "$relativePath: use on L$firstUseLineNum, declare on L$declareLineNum";
  }
}

echo "PHP-DECLARE-ORDER-001: Checked $checked files with declare(strict_types=1)\n";

if (!empty($violations)) {
  echo "\n[FAIL] " . count($violations) . " file(s) have use statements BEFORE declare(strict_types=1):\n";
  foreach ($violations as $v) {
    echo "  $v\n";
  }
  echo "\nFix: Move declare(strict_types=1) to line 3 (after <?php).\n";
  exit(1);
}

echo "[PASS] All files have correct declare(strict_types=1) ordering.\n";
exit(0);
