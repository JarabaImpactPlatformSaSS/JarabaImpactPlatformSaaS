<?php

/**
 * @file
 * MERGE-API-AUDIT-001: Detecta uso de métodos inexistentes de Drupal Database API.
 *
 * Grep estático que busca llamadas a métodos que NO existen en las clases
 * de Drupal Database API pero que se confunden con facilidad. Estos bugs son
 * latentes: solo explotan cuando el código se ejecuta (no en PHPStan nivel 6
 * porque las queries son dinámicas y $this->database->merge() retorna mixed).
 *
 * Métodos fantasma detectados:
 *   - Merge::expressions()  → usar ->expression() (singular) encadenado
 *   - Select::addFields()   → usar ->fields() o ->addField() (singular)
 *   - Delete::where()       → usar ->condition()
 *   - Insert::onDuplicate() → no existe, usar Merge
 *   - Update::join()        → solo Select tiene join()
 *   - Select::addExpression()->execute() → addExpression() retorna alias, no $this
 *
 * Usage:
 *   php scripts/validation/validate-db-api-phantom-methods.php
 */

$errors = [];
$passed = 0;
$total = 0;

$base = dirname(__DIR__, 2);
$modulesDir = $base . '/web/modules/custom';

echo "\n=== MERGE-API-AUDIT-001: Database API phantom method detection ===\n\n";

// Phantom methods: pattern => description.
$phantoms = [
  '/->expressions\s*\(/' => 'Merge::expressions() does not exist — use ->expression() (singular) chained',
  '/->addFields\s*\(/' => 'Select::addFields() does not exist — use ->fields() or ->addField()',
  '/->onDuplicate\s*\(/' => 'Insert::onDuplicate() does not exist — use $db->merge() for upserts',
  '/->addExpression\s*\([^)]*\)\s*->\s*execute\s*\(/' => 'addExpression() returns alias (string), not $this — cannot chain ->execute()',
];

// Scan all PHP files in custom modules.
$phpFiles = [];
$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
  if ($file->getExtension() === 'php') {
    $phpFiles[] = $file->getPathname();
  }
}

echo "  Scanning " . count($phpFiles) . " PHP files in web/modules/custom/...\n\n";

$findings = [];

foreach ($phpFiles as $phpFile) {
  $content = file_get_contents($phpFile);
  $lines = explode("\n", $content);
  $relativePath = str_replace($base . '/', '', $phpFile);

  foreach ($phantoms as $pattern => $description) {
    foreach ($lines as $lineNum => $line) {
      if (preg_match($pattern, $line)) {
        // Exclude comments.
        $trimmed = ltrim($line);
        if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '#')) {
          continue;
        }
        $findings[] = [
          'file' => $relativePath,
          'line' => $lineNum + 1,
          'method' => $description,
          'code' => trim($line),
        ];
      }
    }
  }
}

$total = 1;

if (empty($findings)) {
  echo "  \033[32m[PASS]\033[0m No phantom Database API methods found\n";
  $passed++;
}
else {
  echo "  \033[31m[FAIL]\033[0m " . count($findings) . " phantom method call(s) found:\n\n";
  foreach ($findings as $f) {
    echo "    \033[31m{$f['file']}:{$f['line']}\033[0m\n";
    echo "      {$f['method']}\n";
    echo "      Code: {$f['code']}\n\n";
  }
  $errors = $findings;
}

echo "\n";
if (empty($errors)) {
  echo "\033[32m=== $passed/$total checks PASSED ===\033[0m\n\n";
  exit(0);
}
else {
  echo "\033[31m=== 0/$total passed, " . count($errors) . " phantom method(s) found ===\033[0m\n";
  exit(1);
}
