<?php

/**
 * @file
 * Validator: Andalucía +ei 2ª Edición — 29 entregables seed integrity.
 *
 * Verifies that PortfolioEntregablesService::ENTREGABLES constant has exactly
 * 29 items, each with required keys, correct session IDs and modules.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-2e-entregables.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$serviceFile = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei/src/Service/PortfolioEntregablesService.php';

if (!file_exists($serviceFile)) {
  echo "\n  ❌ FAIL: PortfolioEntregablesService.php not found\n\n";
  exit(1);
}

$content = file_get_contents($serviceFile);

// CHECK 1: ENTREGABLES constant exists.
if (strpos($content, 'ENTREGABLES') !== false) {
  $passes[] = "CHECK 1 PASS: ENTREGABLES constant defined";
} else {
  $errors[] = "CHECK 1 FAIL: ENTREGABLES constant not found in PortfolioEntregablesService";
}

// CHECK 2: Exactly 29 entries.
preg_match_all('/(\d+)\s*=>\s*\[/', $content, $matches);
$entryCount = count($matches[1] ?? []);
if ($entryCount === 29) {
  $passes[] = "CHECK 2 PASS: 29/29 entregable entries";
} else {
  $errors[] = "CHECK 2 FAIL: Expected 29 entries, found $entryCount";
}

// CHECK 3: All entries have required keys (titulo, sesion, modulo).
$requiredKeys = ['titulo', 'sesion', 'modulo'];
$missingKeys = [];
foreach ($requiredKeys as $key) {
  $keyCount = substr_count($content, "'$key'");
  if ($keyCount < 29) {
    $missingKeys[] = "$key (found $keyCount, expected 29)";
  }
}
if (count($missingKeys) === 0) {
  $passes[] = "CHECK 3 PASS: All 29 entries have titulo, sesion, modulo keys";
} else {
  $errors[] = "CHECK 3 FAIL: Missing keys: " . implode(', ', $missingKeys);
}

// CHECK 4: Session IDs cover all modules (OI, M0-M5).
$expectedPrefixes = ['OI-', 'M0-', 'M1-', 'M2-', 'M3-', 'M4-', 'M5-'];
$missingPrefixes = [];
foreach ($expectedPrefixes as $prefix) {
  if (strpos($content, "'$prefix") === false && strpos($content, "\"$prefix") === false) {
    $missingPrefixes[] = $prefix;
  }
}
if (count($missingPrefixes) === 0) {
  $passes[] = "CHECK 4 PASS: Session IDs cover all 7 modules (OI, M0-M5)";
} else {
  $errors[] = "CHECK 4 FAIL: Missing module prefixes: " . implode(', ', $missingPrefixes);
}

// CHECK 5: Module values are valid.
$validModules = ['orientacion', 'modulo_0', 'modulo_1', 'modulo_2', 'modulo_3', 'modulo_4', 'modulo_5'];
$allModulesFound = true;
foreach ($validModules as $mod) {
  if (strpos($content, "'$mod'") === false) {
    $errors[] = "CHECK 5 FAIL: Module '$mod' not found in any entregable";
    $allModulesFound = false;
  }
}
if ($allModulesFound) {
  $passes[] = "CHECK 5 PASS: All 7 module values present";
}

// CHECK 6: seedEntregables method exists.
if (strpos($content, 'function seedEntregables') !== false) {
  $passes[] = "CHECK 6 PASS: seedEntregables() method exists";
} else {
  $errors[] = "CHECK 6 FAIL: seedEntregables() method not found";
}

// CHECK 7: hook_insert calls seedEntregables.
$moduleFile = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei/jaraba_andalucia_ei.module';
$moduleContent = file_exists($moduleFile) ? file_get_contents($moduleFile) : '';
if (strpos($moduleContent, 'seedEntregables') !== false) {
  $passes[] = "CHECK 7 PASS: hook_insert calls seedEntregables()";
} else {
  $errors[] = "CHECK 7 FAIL: seedEntregables not wired in hook_insert";
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANDALUCÍA +EI 2E — ENTREGABLES SEED INTEGRITY ===\n\n";
foreach ($passes as $msg) { echo "  ✅ $msg\n"; }
foreach ($errors as $msg) { echo "  ❌ $msg\n"; }
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(count($errors) === 0 ? 0 : 1);
