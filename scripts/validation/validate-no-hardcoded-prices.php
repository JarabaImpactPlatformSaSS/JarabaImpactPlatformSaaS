#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @file validate-no-hardcoded-prices.php
 *
 * NO-HARDCODE-PRICE-001: Detecta precios EUR hardcodeados en templates Twig.
 *
 * Los precios DEBEN venir del sistema de planes SaaS (MetaSitePricingService)
 * y NUNCA estar hardcodeados en templates. Los competidores (Aranzadi, vLex)
 * son excepcion: sus precios son referencia externa, no configurables.
 *
 * DIRECTRIZ: Todos los valores monetarios propios deben ser configurables
 * desde la interfaz (/admin/structure/saas-plan).
 *
 * USO:
 *   php scripts/validation/validate-no-hardcoded-prices.php [--fix]
 *
 * EXIT CODES:
 *   0 = PASS (sin precios hardcodeados)
 *   1 = FAIL (precios hardcodeados detectados)
 *
 * @see \Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService
 */

$project_root = dirname(__DIR__, 2);
$template_dirs = [
  $project_root . '/web/themes/custom/ecosistema_jaraba_theme/templates',
];

// Patrones que detectan precios EUR hardcodeados.
// Matchea: 29€, 59 EUR, 99€/mes, Desde 29EUR/mes, etc.
$price_patterns = [
  // Precio con simbolo euro: 29€, 59€/mes
  '/\b\d+[.,]?\d*\s*€/u',
  // Precio con EUR: 29 EUR, 59 EUR/mes
  '/\b\d+[.,]?\d*\s+EUR\b/u',
];

// Excepciones: lineas que son aceptables (competidores, comentarios, etc.)
$exception_patterns = [
  // Precios de competidores (referencia externa, no controlamos).
  '/Aranzadi/i',
  '/vLex/i',
  '/Wolters/i',
  // Comentarios Twig.
  '/^\s*\{#/',
  '/^\s*#\}/',
  // Comentarios HTML.
  '/^\s*<!--/',
  // Variables dinamicas (ya vienen del sistema).
  '/ped_pricing\.|tier\.price|from_price|price_monthly|price_yearly/',
  // number_format (procesamiento de variable dinamica).
  '/number_format/',
  // Ejemplos en documentacion de variables.
  '/\*\s+.*e\.g\.\s/',
  // Texto "0€/mes" es aceptable (gratis).
  '/\b0\s*€/',
  // Template pricing-page.html.twig ya usa datos dinamicos via controller.
  // Solo los archivos de documentacion/comentarios de variables.
  '/from_price.*string.*€/i',
];

// Archivos excluidos: contienen datos mock/placeholder, no precios de plan.
// Case studies contienen precios narrativos (datos de la historia del cliente),
// no precios configurables del SaaS.
$excluded_files = [
  'page--agent-dashboard.html.twig',
  'agroconecta-case-study.html.twig',
  'comercioconecta-case-study.html.twig',
  'emprendimiento-case-study.html.twig',
  'empleabilidad-case-study.html.twig',
  'formacion-case-study.html.twig',
];

$errors = [];
$checked = 0;

foreach ($template_dirs as $dir) {
  if (!is_dir($dir)) {
    continue;
  }

  $iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
  );

  foreach ($iterator as $file) {
    if ($file->getExtension() !== 'twig') {
      continue;
    }

    $filepath = $file->getPathname();
    $basename = $file->getBasename();
    $relative = str_replace($project_root . '/', '', $filepath);

    if (in_array($basename, $excluded_files, true)) {
      continue;
    }

    $lines = file($filepath);
    $checked++;

    foreach ($lines as $lineNum => $line) {
      $lineNumber = $lineNum + 1;

      // Saltar excepciones.
      $isException = false;
      foreach ($exception_patterns as $exception) {
        if (preg_match($exception, $line)) {
          $isException = true;
          break;
        }
      }
      if ($isException) {
        continue;
      }

      // Buscar precios hardcodeados.
      foreach ($price_patterns as $pattern) {
        if (preg_match($pattern, $line, $matches)) {
          $errors[] = [
            'file' => $relative,
            'line' => $lineNumber,
            'match' => trim($matches[0]),
            'context' => trim($line),
          ];
          break; // Una coincidencia por linea es suficiente.
        }
      }
    }
  }
}

// Reportar resultados.
echo "NO-HARDCODE-PRICE-001: Verificacion de precios hardcodeados en templates\n";
echo str_repeat('=', 70) . "\n";
echo "Templates verificados: {$checked}\n";

if (empty($errors)) {
  echo "\n\033[32mPASS\033[0m — Ningun precio EUR hardcodeado detectado.\n";
  echo "Todos los precios provienen del sistema SaaS Plans.\n";
  exit(0);
}

echo "\n\033[31mFAIL\033[0m — " . count($errors) . " precio(s) hardcodeado(s) detectado(s):\n\n";

foreach ($errors as $error) {
  echo "  \033[33m{$error['file']}:{$error['line']}\033[0m\n";
  echo "    Match: {$error['match']}\n";
  echo "    Linea: {$error['context']}\n\n";
}

echo "SOLUCION: Usar precios dinamicos desde MetaSitePricingService.\n";
echo "  - En preprocess: \$variables['ped_pricing'] via ecosistema_jaraba_core.metasite_pricing\n";
echo "  - En Twig: {{ ped_pricing.{vertical}.professional_price }} o {{ tier.price_monthly }}\n";
echo "  - Admin: /admin/structure/saas-plan\n";

exit(1);
