<?php

/**
 * @file
 * I18N-DRIFT-001: Detecta traducciones custom en BD no presentes en .po versionado.
 *
 * 4 checks:
 *  1. translations/es-custom.po existe y tiene >= 100 strings
 *  2. Conteo BD custom (es) vs conteo .po — drift < 5%
 *  3. translations/pt-br-custom.po existe (si hay custom pt-br en BD)
 *  4. Conteo BD custom (pt-br) vs conteo .po — drift < 5%
 *
 * Motivación: si un usuario traduce en la UI de Drupal y no exporta a .po,
 * la traducción se pierde en el siguiente deploy (--override=customized)
 * o si se reimporta la BD. Este validador detecta el drift.
 *
 * Usage:
 *   php scripts/validation/validate-translation-drift.php
 *   # En contexto Drupal (Lando):
 *   lando drush php:script scripts/validation/validate-translation-drift.php
 */

$errors = [];
$warnings = [];
$passed = 0;
$total = 4;

$base = dirname(__DIR__, 2);
$esPoFile = $base . '/translations/es-custom.po';
$ptPoFile = $base . '/translations/pt-br-custom.po';

echo "\n=== I18N-DRIFT-001: Translation drift detection ===\n\n";

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

/**
 * Count msgid entries in a .po file (excluding header).
 */
function countPoStrings(string $filePath): int {
  if (!file_exists($filePath)) {
    return 0;
  }
  $content = file_get_contents($filePath);
  // Count all msgid lines (including multiline), minus 1 for the PO header.
  preg_match_all('/^msgid\s+/m', $content, $matches);
  $count = count($matches[0]);
  // Subtract 1 for the PO header entry (msgid "").
  return max(0, $count - 1);
}

/**
 * Count custom translations in DB via direct SQL.
 * Returns -1 if DB not accessible.
 */
function countDbCustom(string $langcode): int {
  // Try Drupal DB first.
  if (class_exists('\Drupal') && method_exists('\Drupal', 'database')) {
    try {
      $count = \Drupal::database()
        ->select('locales_target', 'lt')
        ->condition('lt.customized', 1)
        ->condition('lt.language', $langcode)
        ->countQuery()
        ->execute()
        ->fetchField();
      return (int) $count;
    }
    catch (\Throwable) {
      // Fallback to direct PDO.
    }
  }

  // Direct PDO (outside Drupal context).
  try {
    $host = getenv('REDIS_HOST') ? 'database' : 'localhost';
    $dbName = getenv('LANDO') === 'ON' ? 'drupal_jaraba' : 'jaraba';
    $user = getenv('LANDO') === 'ON' ? 'drupal' : 'drupal';
    $pass = getenv('LANDO') === 'ON' ? 'drupal' : '';
    $pdo = new \PDO("mysql:host=$host;dbname=$dbName;port=3306", $user, $pass);
    return (int) $pdo->query("SELECT COUNT(*) FROM locales_target WHERE customized = 1 AND language = '$langcode'")->fetchColumn();
  }
  catch (\Throwable) {
    return -1;
  }
}

// ---------------------------------------------------------------------------
// Check 1: es-custom.po exists and has >= 100 strings.
// ---------------------------------------------------------------------------
$esPoCount = countPoStrings($esPoFile);
check(
  '1/4 es-custom.po exists (>= 100 strings)',
  file_exists($esPoFile) && $esPoCount >= 100,
  !file_exists($esPoFile)
    ? 'translations/es-custom.po not found. Run: lando ssh -c "bash /app/scripts/translations-export.sh"'
    : "es-custom.po has only $esPoCount strings (expected >= 100)",
  $errors, $warnings, $passed
);

// ---------------------------------------------------------------------------
// Check 2: DB custom (es) vs .po drift < 5%.
// ---------------------------------------------------------------------------
$esDbCount = countDbCustom('es');
if ($esDbCount === -1) {
  check(
    '2/4 es drift DB vs .po (< 5%)',
    TRUE,
    'DB not accessible — skipping drift check (static .po validation only)',
    $errors, $warnings, $passed,
    TRUE
  );
}
else {
  $esDrift = $esPoCount > 0 ? abs($esDbCount - $esPoCount) / $esPoCount * 100 : 100;
  $driftLabel = sprintf('%.1f%%', $esDrift);
  $driftDetail = "DB=$esDbCount, .po=$esPoCount, drift=$driftLabel";
  check(
    "2/4 es drift DB vs .po (< 5%)",
    $esDrift < 5,
    "$driftDetail. Run: lando ssh -c \"bash /app/scripts/translations-export.sh\" to re-export",
    $errors, $warnings, $passed,
    $esDrift >= 5 && $esDrift < 20  // warn 5-20%, fail >20%
  );
}

// ---------------------------------------------------------------------------
// Check 3: pt-br-custom.po exists if DB has custom pt-br.
// ---------------------------------------------------------------------------
$ptDbCount = countDbCustom('pt-br');
if ($ptDbCount <= 0) {
  check(
    '3/4 pt-br-custom.po exists (if DB has custom pt-br)',
    TRUE,
    '',
    $errors, $warnings, $passed
  );
}
else {
  check(
    '3/4 pt-br-custom.po exists (DB has custom pt-br)',
    file_exists($ptPoFile),
    "DB has $ptDbCount custom pt-br translations but translations/pt-br-custom.po is missing",
    $errors, $warnings, $passed,
    TRUE
  );
}

// ---------------------------------------------------------------------------
// Check 4: pt-br drift.
// ---------------------------------------------------------------------------
$ptPoCount = countPoStrings($ptPoFile);
if ($ptDbCount <= 0 || $ptDbCount === -1) {
  check(
    '4/4 pt-br drift DB vs .po',
    TRUE,
    '',
    $errors, $warnings, $passed
  );
}
else {
  $ptDrift = $ptPoCount > 0 ? abs($ptDbCount - $ptPoCount) / $ptPoCount * 100 : ($ptDbCount > 0 ? 100 : 0);
  $driftLabel = sprintf('%.1f%%', $ptDrift);
  check(
    "4/4 pt-br drift DB vs .po (< 5%)",
    $ptDrift < 5,
    "DB=$ptDbCount, .po=$ptPoCount, drift=$driftLabel",
    $errors, $warnings, $passed,
    $ptDrift >= 5 && $ptDrift < 50
  );
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
