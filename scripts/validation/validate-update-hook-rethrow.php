<?php

declare(strict_types=1);

/**
 * @file
 * UPDATE-HOOK-CATCH-RETHROW-001: catch in hook_update_N() MUST re-throw.
 *
 * Drupal treats any return string from hook_update_N() as success.
 * If a catch block returns an error message instead of throwing,
 * Drupal registers the schema as updated even though the update failed.
 *
 * This validator detects the anti-pattern:
 *   catch (\Throwable $e) { return 'Error: ' . $e->getMessage(); }
 *
 * The correct pattern is:
 *   catch (\Throwable $e) { throw new \RuntimeException(..., 0, $e); }
 *
 * Usage: php scripts/validation/validate-update-hook-rethrow.php
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
  if ($file->getExtension() !== 'install') {
    continue;
  }

  $path = $file->getPathname();
  $content = file_get_contents($path);
  if ($content === FALSE) {
    continue;
  }

  // Only check files with hook_update_N functions.
  if (!preg_match('/function\s+\w+_update_\d+/', $content)) {
    continue;
  }

  $checked++;
  $lines = explode("\n", $content);
  $inUpdateHook = FALSE;
  $inCatch = FALSE;
  $braceDepth = 0;
  $catchStartLine = 0;

  foreach ($lines as $i => $line) {
    $trimmed = trim($line);
    $lineNum = $i + 1;

    // Detect hook_update_N function start.
    if (preg_match('/function\s+\w+_update_\d+/', $trimmed)) {
      $inUpdateHook = TRUE;
    }

    if (!$inUpdateHook) {
      continue;
    }

    // Track brace depth for function scope.
    $braceDepth += substr_count($line, '{') - substr_count($line, '}');
    if ($braceDepth <= 0 && $inUpdateHook) {
      $inUpdateHook = FALSE;
      $inCatch = FALSE;
      $braceDepth = 0;
    }

    // Detect catch block.
    if (preg_match('/\bcatch\s*\(/', $trimmed)) {
      $inCatch = TRUE;
      $catchStartLine = $lineNum;
    }

    // In catch block, detect return with error message (anti-pattern).
    if ($inCatch && preg_match('/return\s+[\'"].*(?:error|fail|exception)/i', $trimmed)) {
      $relativePath = str_replace($projectRoot . '/', '', $path);
      $violations[] = "$relativePath:$lineNum — return in catch (should throw). Catch started at L$catchStartLine";
      $inCatch = FALSE;
    }

    // Detect return with $e->getMessage() in catch.
    if ($inCatch && str_contains($trimmed, 'return') && str_contains($trimmed, 'getMessage()')) {
      $relativePath = str_replace($projectRoot . '/', '', $path);
      $violations[] = "$relativePath:$lineNum — return \$e->getMessage() in catch (should throw)";
      $inCatch = FALSE;
    }

    // Reset catch flag on closing brace.
    if ($inCatch && $trimmed === '}') {
      $inCatch = FALSE;
    }
  }
}

echo "UPDATE-HOOK-CATCH-RETHROW-001: Checked $checked .install files\n";

if (!empty($violations)) {
  echo "\n[FAIL] " . count($violations) . " hook_update catch block(s) return instead of throw:\n";
  foreach ($violations as $v) {
    echo "  $v\n";
  }
  echo "\nFix: Replace 'return \$errorMsg' with 'throw new \\RuntimeException(\$msg, 0, \$e)'\n";
  exit(1);
}

echo "[PASS] All hook_update_N() catch blocks re-throw properly.\n";
exit(0);
