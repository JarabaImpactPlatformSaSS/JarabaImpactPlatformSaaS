<?php

/**
 * @file
 * Verificación de Setup Wizard compliance post-import.
 */

declare(strict_types=1);

echo "\n==========================================\n";
echo "Setup Wizard — Page Builder Compliance\n";
echo "==========================================\n\n";

$entityTypeManager = \Drupal::entityTypeManager();
$pageStorage = $entityTypeManager->getStorage('page_content');

$tenantMap = [5 => 'PepeJaraba', 6 => 'JarabaImpact', 7 => 'PED'];

foreach ($tenantMap as $groupId => $name) {
  echo "─── {$name} (group {$groupId}) ───\n";

  // CrearPrimeraPaginaStep: isComplete = count(page_content) > 0.
  $count = (int) $pageStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->count()
    ->execute();

  $published = (int) $pageStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->condition('status', 1)
    ->count()
    ->execute();

  // Simular isComplete() para los 4 page_builder steps.
  $steps = [
    'ElegirPlantillaStep' => TRUE, // Siempre TRUE (templates en config).
    'CrearPrimeraPaginaStep' => $count > 0,
    'PersonalizarContenidoStep' => $count > 0, // Hay contenido.
    'PublicarPaginaStep' => $published > 0,
  ];

  foreach ($steps as $step => $complete) {
    $status = $complete ? '✓ COMPLETE' : '✗ PENDING';
    echo "  {$status}: {$step}\n";
  }

  echo "  Total pages: {$count}, Published: {$published}\n\n";
}

echo "==========================================\n";
echo "Zeigarnik Preload (global steps):\n";
echo "  ✓ AutoCompleteAccountStep: siempre TRUE\n";
echo "  ✓ AutoCompleteVerticalStep: siempre TRUE\n";
echo "==========================================\n\n";
