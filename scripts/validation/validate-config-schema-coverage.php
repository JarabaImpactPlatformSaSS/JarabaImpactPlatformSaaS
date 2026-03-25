<?php

/**
 * @file
 * CONFIG-SCHEMA-COVERAGE-001: Detecta config objects con schema incompleto.
 *
 * Busca mappings definidos como `type: mapping` sin keys internas (open
 * mappings). En Drupal 11, estos generan "missing schema" warnings en
 * drush cim. En Drupal 12, podrían bloquear la importación.
 *
 * Escanea todos los ficheros config/schema/*.schema.yml de módulos custom.
 *
 * Usage:
 *   php scripts/validation/validate-config-schema-coverage.php
 */

$errors = [];
$warnings = [];
$passed = 0;
$total = 0;

$base = dirname(__DIR__, 2);
$modulesDir = $base . '/web/modules/custom';

echo "\n=== CONFIG-SCHEMA-COVERAGE-001: Config schema completeness ===\n\n";

// Find all schema files in custom modules.
$schemaFiles = glob($modulesDir . '/*/config/schema/*.schema.yml');

if (empty($schemaFiles)) {
  echo "  No schema files found in custom modules\n";
  exit(0);
}

$openMappings = [];

foreach ($schemaFiles as $schemaFile) {
  $content = file_get_contents($schemaFile);
  $lines = explode("\n", $content);
  $relativePath = str_replace($base . '/', '', $schemaFile);

  $lineNum = 0;
  $prevLine = '';

  foreach ($lines as $line) {
    $lineNum++;

    // Detect pattern: a line with "type: mapping" followed by a line that
    // is NOT "mapping:" (i.e., no child keys defined).
    // This is the "open mapping" anti-pattern.
    if (preg_match('/^\s+type:\s*mapping\s*$/', $prevLine)) {
      $prevIndent = strlen($prevLine) - strlen(ltrim($prevLine));
      $currentIndent = strlen($line) - strlen(ltrim($line));
      $trimmedLine = trim($line);

      // If current line has same or less indent than the "type: mapping" line,
      // OR is empty, OR starts a new key at same level — the mapping has no
      // children defined.
      if ($trimmedLine === '' || $currentIndent <= $prevIndent) {
        // Check if the line before "type: mapping" was a key name (the mapping name).
        // Go back to find the mapping name.
        $nameLineNum = $lineNum - 2; // Line before "type: mapping"
        $nameLine = $nameLineNum >= 0 ? trim($lines[$nameLineNum - 1] ?? '') : '';

        // Get the key name from the line before "type: mapping".
        preg_match('/^(\s*)(\w[\w-]*):\s*$/', $lines[$lineNum - 3] ?? '', $nameMatch);
        $mappingName = $nameMatch[2] ?? '(unknown)';

        // Exclude known safe patterns: top-level config_object and config_entity.
        if ($mappingName !== 'type' && $mappingName !== 'config_object' && $mappingName !== 'config_entity') {
          $openMappings[] = [
            'file' => $relativePath,
            'line' => $lineNum - 1,
            'mapping' => $mappingName,
          ];
        }
      }
    }

    $prevLine = $line;
  }
}

$total = 1;
$openCount = count($openMappings);

if ($openCount === 0) {
  echo "  \033[32m[PASS]\033[0m No open mappings found (all mappings have defined keys)\n";
  $passed++;
}
else {
  echo "  \033[33m[WARN]\033[0m $openCount open mapping(s) found — may cause 'missing schema' warnings\n\n";
  foreach ($openMappings as $om) {
    echo "    {$om['file']}:{$om['line']} — mapping '{$om['mapping']}' has no child keys\n";
  }
  $warnings[] = "$openCount open mappings without child keys defined";
  $passed++;
}

echo "\n";
$ec = count($errors);
if ($ec === 0) {
  $wc = count($warnings);
  echo "\033[32m=== $passed/$total checks PASSED";
  if ($wc > 0) {
    echo " ($wc warnings)";
  }
  echo " ===\033[0m\n\n";
  exit(0);
}
else {
  echo "\033[31m=== $ec FAILED ===\033[0m\n";
  exit(1);
}
