<?php

/**
 * @file
 * DB-EXPORT-FRESHNESS-001: Verifica que existe un dump local reciente de la BD.
 *
 * 3 checks:
 *  1. Existe al menos 1 dump .sql.gz en /tmp/ con nombre jaraba*
 *  2. El dump más reciente tiene < 7 días de antigüedad
 *  3. El dump pesa >= 1MB (no es un archivo vacío/corrupto)
 *
 * Motivación: lando destroy borra volúmenes Docker incluyendo la BD.
 * Sin un dump local reciente, se pierde todo el contenido L2 (PageContent,
 * Groups, Tenants) que NO está en config/sync ni en git.
 *
 * Usage:
 *   php scripts/validation/validate-db-export-freshness.php
 */

$errors = [];
$warnings = [];
$passed = 0;
$total = 3;

echo "\n=== DB-EXPORT-FRESHNESS-001: Local DB dump freshness ===\n\n";

function check(string $label, bool $condition, string $msg, array &$errors, array &$warnings, int &$passed, bool $warn = FALSE): void {
  if ($condition) {
    echo "  \033[32m[PASS]\033[0m $label\n";
    $passed++;
  }
  elseif ($warn) {
    echo "  \033[33m[WARN]\033[0m $label — $msg\n";
    $warnings[] = "$label: $msg";
    $passed++;
  }
  else {
    echo "  \033[31m[FAIL]\033[0m $label — $msg\n";
    $errors[] = "$label: $msg";
  }
}

// Search for dumps in common locations.
$search_paths = ['/tmp', getenv('HOME')];
$dumps = [];

foreach ($search_paths as $dir) {
  if (!$dir || !is_dir($dir)) {
    continue;
  }
  $files = glob($dir . '/jaraba*.sql.gz') ?: [];
  $files = array_merge($files, glob($dir . '/jaraba*.sql') ?: []);
  $dumps = array_merge($dumps, $files);
}

// Also check project root and standard backup location.
$base = dirname(__DIR__, 2);
$projectDumps = glob($base . '/*.sql.gz') ?: [];
$dumps = array_merge($dumps, $projectDumps);

// Windows/WSL F: drive backup location.
$winBackup = '/mnt/f/DATOS/PED S.L/Jaraba Impact Platform SaaS/Drupal/Desarrollo - Local/Copias';
if (is_dir($winBackup)) {
  $winDumps = glob($winBackup . '/*/*.sql.gz') ?: [];
  $winDumps = array_merge($winDumps, glob($winBackup . '/*/*/*.sql.gz') ?: []);
  $dumps = array_merge($dumps, $winDumps);
}

$dumps = array_unique($dumps);

// Check 1: At least 1 dump exists.
check(
  '1/3 At least 1 DB dump found',
  count($dumps) > 0,
  'No jaraba*.sql.gz dumps found in /tmp, $HOME, or project root. Run: lando db-export /tmp/jaraba-$(date +%Y%m%d).sql.gz',
  $errors, $warnings, $passed
);

// Find newest dump.
$newest = NULL;
$newestAge = PHP_INT_MAX;
$newestSize = 0;

foreach ($dumps as $dump) {
  $mtime = filemtime($dump);
  $age = time() - $mtime;
  if ($age < $newestAge) {
    $newestAge = $age;
    $newest = $dump;
    $newestSize = filesize($dump);
  }
}

// Check 2: Newest dump is < 7 days old.
$maxAgeDays = 7;
$ageDays = $newest ? round($newestAge / 86400, 1) : -1;
check(
  "2/3 Newest dump < {$maxAgeDays} days old",
  $newest && $ageDays < $maxAgeDays,
  $newest
    ? "Newest dump is {$ageDays} days old: " . basename($newest) . ". Run: lando db-export /tmp/jaraba-\$(date +%Y%m%d).sql.gz"
    : 'No dumps found',
  $errors, $warnings, $passed,
  $newest && $ageDays >= $maxAgeDays && $ageDays < 30 // warn if 7-30 days, fail if >30
);

// Check 3: Dump size >= 1MB.
$minSizeMB = 1;
$sizeMB = $newest ? round($newestSize / 1048576, 1) : 0;
check(
  "3/3 Dump size >= {$minSizeMB}MB (not empty/corrupt)",
  $newest && $sizeMB >= $minSizeMB,
  $newest
    ? "Newest dump is only {$sizeMB}MB — may be empty or corrupt: " . basename($newest)
    : 'No dumps found',
  $errors, $warnings, $passed
);

if ($newest) {
  echo "\n  Newest dump: " . basename($newest) . " ({$sizeMB}MB, {$ageDays} days old)\n";
}

// Summary.
echo "\n";
$ec = count($errors);
if ($ec === 0) {
  echo "\033[32m=== $passed/$total checks PASSED ===\033[0m\n\n";
  exit(0);
}
else {
  echo "\033[31m=== $passed/$total passed, $ec FAILED ===\033[0m\n";
  foreach ($errors as $err) {
    echo "  - $err\n";
  }
  echo "\n";
  exit(1);
}
