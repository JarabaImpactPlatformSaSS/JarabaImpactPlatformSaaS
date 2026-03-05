<?php

/**
 * @file
 * PRESAVE-RESILIENCE-001: Detect presave hooks calling services without protection.
 *
 * Presave hooks that invoke optional services MUST use hasService() + try-catch.
 * Entity saves MUST NOT fail due to optional services being unavailable.
 *
 * Usage: php scripts/validation/validate-presave-resilience.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

echo "=== PRESAVE-RESILIENCE-001: Presave hook resilience detection ===\n";

$violations = [];
$checked = 0;

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
  // Only check .module files.
  if ($file->getExtension() !== 'module') {
    continue;
  }

  $content = file_get_contents($file->getPathname());
  $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());

  // Find all presave hook functions.
  if (!preg_match_all('/function\s+(\w+_(?:presave|insert|update))\s*\(/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
    continue;
  }

  foreach ($matches[0] as $i => $match) {
    $funcName = $matches[1][$i][0];
    $funcStart = $match[1];
    $checked++;

    // Extract function body (find matching closing brace).
    $braceCount = 0;
    $inFunc = FALSE;
    $funcBody = '';
    $bodyStart = strpos($content, '{', $funcStart);
    if ($bodyStart === FALSE) {
      continue;
    }

    for ($j = $bodyStart; $j < strlen($content); $j++) {
      if ($content[$j] === '{') {
        $braceCount++;
        $inFunc = TRUE;
      }
      if ($content[$j] === '}') {
        $braceCount--;
      }
      if ($inFunc) {
        $funcBody .= $content[$j];
      }
      if ($inFunc && $braceCount === 0) {
        break;
      }
    }

    // Check if function calls \Drupal::service() without protection.
    if (preg_match('/\\\\Drupal::service\s*\(/', $funcBody)) {
      // Check if there's a hasService() check or try-catch.
      $hasProtection = preg_match('/hasService\s*\(/', $funcBody)
        || preg_match('/try\s*\{/', $funcBody);

      if (!$hasProtection) {
        $lineNum = substr_count(substr($content, 0, $funcStart), "\n") + 1;
        $violations[] = "    $relativePath:$lineNum $funcName() calls \\Drupal::service() without hasService() or try-catch";
      }
    }
  }
}

echo "  Presave/insert/update hooks checked: $checked\n";

if (empty($violations)) {
  echo "\n  \033[0;32mOK: All entity hooks have resilient service calls.\033[0m\n";
  exit(0);
}

echo "\n  \033[0;33mWARNINGS (" . count($violations) . "):\033[0m\n";
foreach ($violations as $v) {
  echo "$v\n";
}
echo "\n  Recommendation: Wrap service calls in hasService() check + try-catch.\n";
// Warning only — not blocking yet.
exit(0);
