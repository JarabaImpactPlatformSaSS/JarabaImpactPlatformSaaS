<?php

/**
 * @file
 * SAFEGUARD-AUTO-COUNTER-001: Valida coherencia de conteos de validators.
 *
 * Recuenta automaticamente run_check/warn_check en validate-all.sh y compara
 * con los valores documentados en validators-reference.md y CLAUDE.md.
 * Detecta desincronizacion entre codigo y documentacion.
 *
 * Checks:
 * 1. Total scripts PHP en scripts/validation/ coincide con documentado.
 * 2. run_check count en validate-all.sh coincide con validators-reference.md.
 * 3. warn_check count en validate-all.sh coincide con validators-reference.md.
 * 4. CLAUDE.md conteos sincronizados con validate-all.sh.
 * 5. Cada validator en validate-all.sh tiene fichero PHP correspondiente.
 * 6. Cada fichero PHP validator esta registrado en validate-all.sh.
 *
 * Uso:
 *   php scripts/validation/validate-safeguard-counter.php
 *
 * NO requiere Drupal bootstrap (analisis estatico puro).
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$checks = 0;

$rootDir = dirname(__DIR__, 2);
$validateAllPath = $rootDir . '/scripts/validation/validate-all.sh';
$validatorsRefPath = $rootDir . '/docs/validators-reference.md';
$claudeMdPath = $rootDir . '/CLAUDE.md';
$validationDir = $rootDir . '/scripts/validation';

echo "SAFEGUARD-AUTO-COUNTER-001: Validando coherencia conteos de validators\n";
echo str_repeat('=', 60) . "\n\n";

// ─── Contar ficheros PHP de validacion ───
$checks++;
$phpFiles = glob($validationDir . '/validate-*.php');
$totalScripts = count($phpFiles);
echo "  [Scripts PHP] {$totalScripts} ficheros validate-*.php encontrados\n";

// ─── Contar invocaciones en validate-all.sh ───
$validateAllContent = file_get_contents($validateAllPath);
if ($validateAllContent === FALSE) {
  $errors[] = 'No se puede leer validate-all.sh';
}
else {
  preg_match_all('/^\s*run_check\s+/m', $validateAllContent, $runMatches);
  preg_match_all('/^\s*warn_check\s+/m', $validateAllContent, $warnMatches);
  preg_match_all('/^\s*skip_check\s+/m', $validateAllContent, $skipMatches);

  $runCount = count($runMatches[0]);
  $warnCount = count($warnMatches[0]);
  $skipCount = count($skipMatches[0]);
  $totalChecks = $runCount + $warnCount;

  echo "  [validate-all.sh] run_check: {$runCount} | warn_check: {$warnCount} | skip_check: {$skipCount}\n";
  echo "  [validate-all.sh] Total checks activos: {$totalChecks}\n";

  // ─── CHECK 1-3: Comparar con validators-reference.md ───
  $refContent = file_get_contents($validatorsRefPath);
  if ($refContent !== FALSE) {
    // Extraer numeros de la tabla de estadisticas.
    if (preg_match('/Total scripts PHP\s*\|\s*(\d+)/', $refContent, $m)) {
      $checks++;
      $refTotal = (int) $m[1];
      if ($refTotal !== $totalScripts) {
        $errors[] = "validators-reference.md dice {$refTotal} scripts, real: {$totalScripts}";
      }
      else {
        echo "  [validators-reference.md] Total scripts: OK ({$refTotal})\n";
      }
    }

    if (preg_match('/run_check.*?\|\s*(\d+)/', $refContent, $m)) {
      $checks++;
      $refRun = (int) $m[1];
      if ($refRun !== $runCount) {
        $errors[] = "validators-reference.md dice {$refRun} run_check, real: {$runCount}";
      }
      else {
        echo "  [validators-reference.md] run_check: OK ({$refRun})\n";
      }
    }

    if (preg_match('/warn_check.*?\|\s*(\d+)/', $refContent, $m)) {
      $checks++;
      $refWarn = (int) $m[1];
      if ($refWarn !== $warnCount) {
        $errors[] = "validators-reference.md dice {$refWarn} warn_check, real: {$warnCount}";
      }
      else {
        echo "  [validators-reference.md] warn_check: OK ({$refWarn})\n";
      }
    }
  }
  else {
    $warnings[] = 'No se puede leer validators-reference.md';
  }

  // ─── CHECK 4: Comparar con CLAUDE.md ───
  $checks++;
  $claudeContent = file_get_contents($claudeMdPath);
  if ($claudeContent !== FALSE) {
    // Buscar patron: "N scripts validacion (X run + Y warn = Z checks)"
    if (preg_match('/(\d+)\s+scripts\s+validacion\s*\((\d+)\s+run\s*\+\s*(\d+)\s+warn\s*=\s*(\d+)\s+checks\)/', $claudeContent, $m)) {
      $cmScripts = (int) $m[1];
      $cmRun = (int) $m[2];
      $cmWarn = (int) $m[3];
      $cmTotal = (int) $m[4];

      $claudeOk = TRUE;
      if ($cmScripts !== $totalScripts) {
        $errors[] = "CLAUDE.md dice {$cmScripts} scripts, real: {$totalScripts}";
        $claudeOk = FALSE;
      }
      if ($cmRun !== $runCount) {
        $errors[] = "CLAUDE.md dice {$cmRun} run_check, real: {$runCount}";
        $claudeOk = FALSE;
      }
      if ($cmWarn !== $warnCount) {
        $errors[] = "CLAUDE.md dice {$cmWarn} warn_check, real: {$warnCount}";
        $claudeOk = FALSE;
      }
      if ($claudeOk) {
        echo "  [CLAUDE.md] Conteos sincronizados: OK\n";
      }
    }
    else {
      $warnings[] = 'CLAUDE.md: no se encontro patron de conteo de validators';
    }
  }

  // ─── CHECK 5: Cada validator en validate-all.sh tiene fichero PHP ───
  $checks++;
  preg_match_all('/(?:run_check|warn_check)\s+"[^"]+"\s+"[^"]+"\s*\\\?\s*\n?\s*php\s+"\$SCRIPT_DIR\/([^"]+)"/', $validateAllContent, $fileRefs);
  $missingFiles = [];
  foreach ($fileRefs[1] as $ref) {
    if (!file_exists($validationDir . '/' . $ref)) {
      $missingFiles[] = $ref;
    }
  }
  if ($missingFiles !== []) {
    $errors[] = 'Validators referenciados en validate-all.sh sin fichero PHP: ' . implode(', ', $missingFiles);
  }
  else {
    echo "  [File refs] Todos los validators referenciados existen: OK\n";
  }

  // ─��─ CHECK 6: Cada fichero PHP esta registrado en validate-all.sh ───
  $checks++;
  $orphaned = [];
  foreach ($phpFiles as $phpFile) {
    $basename = basename($phpFile);
    // Excluir validate-all.sh helper y el propio counter.
    if (in_array($basename, ['validate-safeguard-counter.php'], TRUE)) {
      continue;
    }
    if (strpos($validateAllContent, $basename) === FALSE) {
      $orphaned[] = $basename;
    }
  }
  if ($orphaned !== []) {
    $warnings[] = 'Validators PHP no registrados en validate-all.sh (posible huerfano o solo pre-commit): ' . implode(', ', $orphaned);
  }
  else {
    echo "  [Orphaned] Sin validators huerfanos: OK\n";
  }
}

// ─── RESUMEN ───
echo "\n" . str_repeat('=', 60) . "\n";
echo "Checks: {$checks} | Errores: " . count($errors) . " | Avisos: " . count($warnings) . "\n";

if (!empty($warnings)) {
  echo "\nAVISOS:\n";
  foreach ($warnings as $w) {
    echo "  !  {$w}\n";
  }
}

if (!empty($errors)) {
  echo "\nERRORES:\n";
  foreach ($errors as $e) {
    echo "  [ERROR] {$e}\n";
  }
  exit(1);
}

echo "\n+ SAFEGUARD-AUTO-COUNTER-001: Conteos sincronizados.\n";
exit(0);
