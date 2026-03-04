<?php

/**
 * @file
 * QUERY-CHAIN-001: Detect dangerous query method chaining.
 *
 * Methods like addExpression(), join(), leftJoin(), innerJoin(), addField()
 * return a string alias, NOT $this. Chaining ->execute() after them causes
 * a fatal "Call to member function on string" error at runtime.
 *
 * Usage: php scripts/validation/validate-query-chains.php
 * Exit:  0 = clean, 1 = violations found
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$modulesDir = $projectRoot . '/web/modules/custom';

if (!is_dir($modulesDir)) {
  fwrite(STDERR, "ERROR: $modulesDir not found\n");
  exit(1);
}

// Methods that return string alias (not $this).
$dangerousMethods = [
  'addExpression',
  'join',
  'leftJoin',
  'innerJoin',
  'rightJoin',
  'addField',
];

$pattern = '/->(?:' . implode('|', $dangerousMethods) . ')\s*\([^)]*\)\s*->/';

$errors = [];

// Recursively scan all PHP files.
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
  if ($file->getExtension() !== 'php' && $file->getExtension() !== 'module' && $file->getExtension() !== 'install') {
    continue;
  }

  $content = file_get_contents($file->getPathname());
  if ($content === FALSE) {
    continue;
  }

  // Remove block comments to avoid false positives in documentation.
  $content = preg_replace('#/\*.*?\*/#s', '', $content);
  // Remove single-line comments.
  $content = preg_replace('#//.*$#m', '', $content);

  // Normalize multi-line statements: collapse whitespace for matching.
  // We work statement-by-statement (split on ;).
  $statements = explode(';', $content);

  $lines = explode("\n", file_get_contents($file->getPathname()));

  foreach ($statements as $statement) {
    if (preg_match($pattern, $statement, $matches)) {
      // Find the line number by searching for the matched fragment in original content.
      $matchFragment = trim(substr($matches[0], 0, 30));
      $lineNum = 0;
      foreach ($lines as $idx => $line) {
        if (str_contains($line, '->addExpression(') ||
            str_contains($line, '->join(') ||
            str_contains($line, '->leftJoin(') ||
            str_contains($line, '->innerJoin(') ||
            str_contains($line, '->rightJoin(') ||
            str_contains($line, '->addField(')) {
          // Check if this line's statement also has chaining.
          // Look forward a few lines for the chain.
          $chunk = '';
          for ($j = $idx; $j < min($idx + 5, count($lines)); $j++) {
            $chunk .= $lines[$j];
          }
          if (preg_match($pattern, $chunk)) {
            $lineNum = $idx + 1;
            break;
          }
        }
      }

      $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
      $errors[] = "$relativePath:$lineNum — chain after " . $matches[0];
    }
  }
}

// Output results.
echo "\n";
echo "=== QUERY-CHAIN-001: Dangerous query method chaining ===\n";
echo "\n";

if (empty($errors)) {
  echo "  OK: No dangerous query chains found.\n";
  echo "\n";
  exit(0);
}

echo "  VIOLATIONS:\n";
foreach ($errors as $error) {
  echo "  [ERROR] $error\n";
}
echo "\n";
echo "  " . count($errors) . " violation(s) found.\n";
echo "  Methods like addExpression()/join() return string alias, NOT \$this.\n";
echo "  Do NOT chain ->execute() after them.\n";
echo "\n";
exit(1);
