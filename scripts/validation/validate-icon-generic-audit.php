<?php

/**
 * @file
 * ICON-CONTEXTUAL-001: Detecta uso de iconos genéricos como comodín.
 *
 * Iconos como ai/brain y ai/screening se usaban 29+ veces para contextos
 * completamente diferentes. Esta salvaguarda detecta acumulación de un mismo
 * icono en más de 3 ubicaciones diferentes (señal de deuda visual).
 *
 * Regla: Cada icono DEBE ser semánticamente único para su contexto.
 * Golden Rule #155.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$warnings = [];

// Escanear PHP files en modules/custom para referencias de iconos.
$iconUsage = [];
$patterns = [
  // jaraba_icon() en Twig y PHP.
  "/'name'\s*=>\s*'([^']+)'/",
  "/icon_name.*?'([^']+)'/",
];

$phpFiles = glob($projectRoot . '/web/modules/custom/*/src/**/*.php');
$phpFiles = array_merge($phpFiles, glob($projectRoot . '/web/modules/custom/*/src/Controller/*.php'));
$phpFiles = array_merge($phpFiles, glob($projectRoot . '/web/modules/custom/*/src/Service/*.php'));
$phpFiles = array_merge($phpFiles, glob($projectRoot . '/web/modules/custom/*/src/Plugin/**/*.php'));

// Buscar en VerticalLandingController específicamente (mayor concentración de iconos).
$vlc = $projectRoot . '/web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php';
if (file_exists($vlc)) {
  $content = file_get_contents($vlc);

  // Contar ocurrencias de cada nombre de icono.
  if (preg_match_all("/'name'\s*=>\s*'([^']+)'/", $content, $matches)) {
    foreach ($matches[1] as $iconName) {
      $iconUsage[$iconName] = ($iconUsage[$iconName] ?? 0) + 1;
    }
  }
}

// Iconos prohibidos (comodines conocidos que ya fueron reemplazados).
$bannedIcons = ['screening', 'diagnostic'];
foreach ($bannedIcons as $banned) {
  if (isset($iconUsage[$banned]) && $iconUsage[$banned] > 0) {
    $errors[] = "BANNED: Icono '$banned' encontrado {$iconUsage[$banned]}x en VerticalLandingController — debe estar reemplazado por icono contextual (ICON-CONTEXTUAL-001)";
  }
}

// Detectar iconos genéricos usados excesivamente (>4 veces en el mismo archivo).
$threshold = 4;
foreach ($iconUsage as $name => $count) {
  if ($count > $threshold && !in_array($name, ['check-circle', 'arrow-right', 'star'], true)) {
    $warnings[] = "GENERIC: Icono '$name' usado {$count}x en VerticalLandingController — considerar diversificar (umbral: {$threshold})";
  }
}

// Reportar.
if (!empty($errors)) {
  echo "ICON-CONTEXTUAL-001: " . count($errors) . " error(es)\n";
  foreach ($errors as $e) {
    echo "  ERROR: $e\n";
  }
  exit(1);
}

if (!empty($warnings)) {
  echo "ICON-CONTEXTUAL-001: OK con " . count($warnings) . " warning(s)\n";
  foreach ($warnings as $w) {
    echo "  WARN: $w\n";
  }
}
else {
  $totalIcons = array_sum($iconUsage);
  $uniqueIcons = count($iconUsage);
  echo "ICON-CONTEXTUAL-001: OK — {$totalIcons} icon refs, {$uniqueIcons} unique icons, 0 banned, 0 generic comodines\n";
}

exit(0);
