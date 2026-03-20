<?php

declare(strict_types=1);

/**
 * @file
 * VALIDATOR-COVERAGE-001: Meta-safeguard that detects validation scripts
 * NOT registered in validate-all.sh or .lintstagedrc.json.
 *
 * Prevents orphaned validators from going unnoticed.
 *
 * Usage: php scripts/validation/validate-validator-coverage.php
 * Exit: 0 = all validators registered, 1 = orphaned validators found
 */

$root = dirname(__DIR__, 2);
$validationDir = $root . '/scripts/validation';
$validateAllPath = $validationDir . '/validate-all.sh';
$lintStagedPath = $root . '/.lintstagedrc.json';

echo "\033[36m╔══════════════════════════════════════════════════════════╗\033[0m\n";
echo "\033[36m║  VALIDATOR-COVERAGE-001                                 ║\033[0m\n";
echo "\033[36m║  Meta-safeguard: Orphaned Validator Detection           ║\033[0m\n";
echo "\033[36m╚══════════════════════════════════════════════════════════╝\033[0m\n\n";

// Whitelist: scripts that are NOT checks themselves.
$whitelist = [
  'validate-all.sh',
  'validate-validator-coverage.php',
];

// 1. Discover all validate-*.php files.
$validators = [];
foreach (glob($validationDir . '/validate-*.php') as $file) {
  $validators[] = basename($file);
}
sort($validators);

echo "Found " . count($validators) . " validation scripts in scripts/validation/\n\n";

// 2. Read validate-all.sh content.
if (!file_exists($validateAllPath)) {
  echo "\033[31m[ERROR] validate-all.sh not found at: $validateAllPath\033[0m\n";
  exit(1);
}
$validateAllContent = file_get_contents($validateAllPath);

// 3. Read .lintstagedrc.json content.
$lintStagedContent = '';
if (file_exists($lintStagedPath)) {
  $lintStagedContent = file_get_contents($lintStagedPath);
}

// 4. Check each validator is registered somewhere.
$orphans = [];
foreach ($validators as $validator) {
  if (in_array($validator, $whitelist, true)) {
    continue;
  }

  $inValidateAll = str_contains($validateAllContent, $validator);
  $inLintStaged = str_contains($lintStagedContent, $validator);

  if (!$inValidateAll && !$inLintStaged) {
    $orphans[] = $validator;
  }
}

// 5. Report results.
if (count($orphans) === 0) {
  echo "\033[32m✓ All " . count($validators) . " validators are registered.\033[0m\n";
  exit(0);
}

echo "\033[31m[FAIL] " . count($orphans) . " orphaned validator(s) not registered in validate-all.sh or .lintstagedrc.json:\033[0m\n\n";
foreach ($orphans as $orphan) {
  echo "  \033[31m[ORPHAN]\033[0m $orphan\n";
}
echo "\n";
echo "Fix: Add each orphaned script to validate-all.sh (run_check or warn_check) or .lintstagedrc.json.\n";
exit(1);
