<?php

/**
 * @file
 * Validator: NOWRAP-OVERFLOW-001 — white-space: nowrap sin overflow protection.
 *
 * Escanea archivos SCSS buscando declaraciones white-space: nowrap que no
 * tengan overflow: hidden o text-overflow: ellipsis en las 5 lineas siguientes.
 *
 * Uso: php scripts/validation/validate-nowrap-overflow.php
 * CI: Si, como run_check (warn)
 *
 * @see NOWRAP-OVERFLOW-001
 */

declare(strict_types=1);

$exitCode = 0;
$violations = [];
$checkedFiles = 0;
$totalNowrap = 0;
$protectedNowrap = 0;

$scanDirs = [
  __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/scss',
];

/**
 * Verifica proteccion overflow en nowrap declarations.
 */
function checkFile(string $filePath, array &$violations, int &$checkedFiles, int &$totalNowrap, int &$protectedNowrap): void {
  $checkedFiles++;
  $lines = file($filePath, FILE_IGNORE_NEW_LINES);
  if ($lines === FALSE) {
    return;
  }

  $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $filePath);
  $lineCount = count($lines);

  for ($i = 0; $i < $lineCount; $i++) {
    $trimmed = trim($lines[$i]);

    // Ignorar comentarios.
    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
      continue;
    }

    if (!preg_match('/white-space\s*:\s*nowrap/', $trimmed)) {
      continue;
    }

    $totalNowrap++;

    // Verificar excepciones.
    if (str_contains($trimmed, '// NOWRAP-SAFE:')) {
      $protectedNowrap++;
      continue;
    }

    // Verificar contexto de accesibilidad (.sr-only, visually-hidden).
    $contextRange = max(0, $i - 5);
    $contextBlock = implode("\n", array_slice($lines, $contextRange, 10));
    if (preg_match('/\.sr-only|visually-hidden|clip\s*:\s*rect/', $contextBlock)) {
      $protectedNowrap++;
      continue;
    }

    // Verificar si overflow: hidden o text-overflow: ellipsis existe en +5 lineas.
    $hasOverflow = FALSE;
    $hasTextOverflow = FALSE;
    $hasResponsiveWrap = FALSE;
    for ($j = $i + 1; $j <= min($i + 5, $lineCount - 1); $j++) {
      $nextTrimmed = trim($lines[$j]);
      if (preg_match('/overflow\s*:\s*hidden/', $nextTrimmed)) {
        $hasOverflow = TRUE;
      }
      if (preg_match('/text-overflow\s*:\s*ellipsis/', $nextTrimmed)) {
        $hasTextOverflow = TRUE;
      }
      if (preg_match('/white-space\s*:\s*normal/', $nextTrimmed)) {
        $hasResponsiveWrap = TRUE;
      }
    }

    // Verificar si padre tiene overflow-x: auto (5 lineas antes).
    for ($j = max(0, $i - 5); $j < $i; $j++) {
      $prevTrimmed = trim($lines[$j]);
      if (preg_match('/overflow-x\s*:\s*auto/', $prevTrimmed)) {
        $hasOverflow = TRUE;
      }
    }

    if ($hasOverflow || $hasTextOverflow || $hasResponsiveWrap) {
      $protectedNowrap++;
    }
    else {
      $lineNum = $i + 1;
      $violations[] = sprintf(
        '  %s:%d — %s',
        $relativePath,
        $lineNum,
        $trimmed
      );
    }
  }
}

// Recopilar y escanear archivos.
$filesToScan = [];
foreach ($scanDirs as $dir) {
  if (!is_dir($dir)) {
    continue;
  }
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
  );
  foreach ($iterator as $file) {
    if ($file->getExtension() === 'scss') {
      $filesToScan[] = $file->getPathname();
    }
  }
}

foreach ($filesToScan as $file) {
  checkFile($file, $violations, $checkedFiles, $totalNowrap, $protectedNowrap);
}

// Reporte.
echo "NOWRAP-OVERFLOW-001: Validando white-space: nowrap con overflow protection...\n";
echo sprintf("  Archivos escaneados: %d\n", $checkedFiles);
echo sprintf("  Total nowrap: %d | Protegidos: %d | Sin proteccion: %d\n",
  $totalNowrap, $protectedNowrap, count($violations));

if (empty($violations)) {
  echo "  [PASS] Todas las declaraciones nowrap tienen proteccion overflow.\n";
}
else {
  $pct = $totalNowrap > 0 ? round(($protectedNowrap / $totalNowrap) * 100, 1) : 0;
  echo sprintf("  [WARN] %d declaraciones sin proteccion (%.1f%% protegido):\n",
    count($violations), $pct);
  foreach (array_slice($violations, 0, 20) as $v) {
    echo $v . "\n";
  }
  if (count($violations) > 20) {
    echo sprintf("  ... y %d mas\n", count($violations) - 20);
  }
}

exit($exitCode);
