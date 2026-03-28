<?php

/**
 * @file
 * Validator: VIEWPORT-DVH-001 — Detecta 100vh sin dvh fallback.
 *
 * Escanea archivos SCSS del tema y modulos custom buscando declaraciones
 * de 100vh que no tengan la linea dvh correspondiente inmediatamente despues.
 *
 * Uso: php scripts/validation/validate-viewport-dvh.php [--files file1 file2 ...]
 * Pre-commit: Si, para archivos .scss modificados
 * CI: Si, como run_check
 *
 * @see VIEWPORT-DVH-001 en CLAUDE.md
 */

declare(strict_types=1);

$exitCode = 0;
$violations = [];
$checkedFiles = 0;

// Directorios a escanear.
$scanDirs = [
  __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/scss',
];

// Si se pasan --files, solo escanear esos archivos.
$filesFromArgs = [];
if (in_array('--files', $argv, TRUE)) {
  $idx = array_search('--files', $argv);
  $filesFromArgs = array_slice($argv, $idx + 1);
}

/**
 * Verifica si una linea con 100vh tiene dvh fallback en la linea siguiente.
 */
function checkFile(string $filePath, array &$violations, int &$checkedFiles): void {
  $checkedFiles++;
  $lines = file($filePath, FILE_IGNORE_NEW_LINES);
  if ($lines === FALSE) {
    return;
  }

  $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $filePath);

  for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    $trimmed = trim($line);

    // Ignorar comentarios.
    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '*')) {
      continue;
    }

    // Buscar 100vh (pero no 100dvh, 100svh, 100lvh).
    if (preg_match('/\b100vh\b/', $trimmed) && !preg_match('/\b100[dsl]vh\b/', $trimmed)) {
      // Verificar excepciones en la misma linea.
      if (str_contains($trimmed, '// VH-SAFE:') || str_contains($trimmed, '/* VH-SAFE')) {
        continue;
      }

      // Verificar que max-height (no necesita dvh).
      if (preg_match('/max-height\s*:/', $trimmed)) {
        continue;
      }

      // Verificar que la linea siguiente contiene dvh.
      $nextLine = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';
      if (!preg_match('/\b100dvh\b/', $nextLine)) {
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
}

// Recopilar archivos a escanear.
$filesToScan = [];

if (!empty($filesFromArgs)) {
  foreach ($filesFromArgs as $file) {
    $fullPath = realpath($file);
    if ($fullPath && str_ends_with($fullPath, '.scss') && file_exists($fullPath)) {
      $filesToScan[] = $fullPath;
    }
  }
}
else {
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
}

// Escanear.
foreach ($filesToScan as $file) {
  checkFile($file, $violations, $checkedFiles);
}

// Reporte.
echo "VIEWPORT-DVH-001: Validando 100vh con dvh fallback...\n";
echo sprintf("  Archivos escaneados: %d\n", $checkedFiles);

if (empty($violations)) {
  echo "  [PASS] Todas las declaraciones 100vh tienen fallback dvh.\n";
}
else {
  echo sprintf("  [FAIL] %d violaciones encontradas:\n", count($violations));
  foreach ($violations as $v) {
    echo $v . "\n";
  }
  $exitCode = 1;
}

exit($exitCode);
