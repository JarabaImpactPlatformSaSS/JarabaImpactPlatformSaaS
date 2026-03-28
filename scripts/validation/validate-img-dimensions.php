<?php

/**
 * @file
 * Validator: IMG-DIMENSIONS-001 — <img> sin atributos width/height.
 *
 * Escanea templates Twig del tema y modulos custom buscando etiquetas <img>
 * que no tengan atributos width y height, lo que causa CLS.
 *
 * Uso: php scripts/validation/validate-img-dimensions.php
 * CI: Si, como warn
 *
 * @see IMG-DIMENSIONS-001
 */

declare(strict_types=1);

$exitCode = 0;
$violations = [];
$checkedFiles = 0;

$scanDirs = [
  __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/templates',
  __DIR__ . '/../../web/modules/custom/jaraba_page_builder/templates',
];

/**
 * Verifica imagenes con dimensiones en templates Twig.
 */
function checkFile(string $filePath, array &$violations, int &$checkedFiles): void {
  $checkedFiles++;
  $content = file_get_contents($filePath);
  if ($content === FALSE) {
    return;
  }

  $relativePath = str_replace(dirname(__DIR__, 2) . '/', '', $filePath);
  $lines = explode("\n", $content);

  for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];

    // Buscar <img que NO sean SVG inline ni Drupal image styles.
    if (!preg_match('/<img\b/i', $line)) {
      continue;
    }

    // Ignorar SVG referenciadas con jaraba_icon().
    if (str_contains($line, 'jaraba_icon') || str_contains($line, '<svg')) {
      continue;
    }

    // Ignorar lineas comentadas en Twig.
    if (preg_match('/\{#.*<img.*#\}/', $line)) {
      continue;
    }

    // Verificar que la etiqueta img tiene width Y height.
    // Primero extraer el tag completo (puede ser multilinea).
    $tagContent = $line;
    $j = $i + 1;
    while ($j < count($lines) && !str_contains($tagContent, '>')) {
      $tagContent .= ' ' . $lines[$j];
      $j++;
    }

    $hasWidth = preg_match('/\bwidth\s*=/', $tagContent) || preg_match('/\bwidth\s*\}/', $tagContent);
    $hasHeight = preg_match('/\bheight\s*=/', $tagContent) || preg_match('/\bheight\s*\}/', $tagContent);

    // Excepciones: imagenes con aspect-ratio class o responsive-img.
    if (preg_match('/class="[^"]*responsive-img/', $tagContent) ||
        preg_match('/aspect-ratio/', $tagContent)) {
      continue;
    }

    // Excepciones: Drupal render arrays ({{ content.field_image }}).
    if (preg_match('/\{\{.*content\./', $tagContent)) {
      continue;
    }

    if (!$hasWidth || !$hasHeight) {
      $lineNum = $i + 1;
      $missing = [];
      if (!$hasWidth) {
        $missing[] = 'width';
      }
      if (!$hasHeight) {
        $missing[] = 'height';
      }
      $violations[] = sprintf(
        '  %s:%d — <img> sin %s',
        $relativePath,
        $lineNum,
        implode(', ', $missing)
      );
    }
  }
}

// Recopilar archivos.
$filesToScan = [];
foreach ($scanDirs as $dir) {
  if (!is_dir($dir)) {
    continue;
  }
  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
  );
  foreach ($iterator as $file) {
    if (in_array($file->getExtension(), ['twig', 'html'])) {
      $filesToScan[] = $file->getPathname();
    }
  }
}

foreach ($filesToScan as $file) {
  checkFile($file, $violations, $checkedFiles);
}

// Reporte.
echo "IMG-DIMENSIONS-001: Validando <img> con width/height...\n";
echo sprintf("  Templates escaneados: %d\n", $checkedFiles);

if (empty($violations)) {
  echo "  [PASS] Todas las imagenes tienen dimensiones.\n";
}
else {
  echo sprintf("  [WARN] %d imagenes sin dimensiones completas:\n", count($violations));
  foreach (array_slice($violations, 0, 30) as $v) {
    echo $v . "\n";
  }
  if (count($violations) > 30) {
    echo sprintf("  ... y %d mas\n", count($violations) - 30);
  }
}

exit($exitCode);
