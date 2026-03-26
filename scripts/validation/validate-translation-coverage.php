<?php

/**
 * @file
 * TRANSLATION-COVERAGE-001: Valida cobertura de traducciones automaticas.
 *
 * Checks:
 * 1. Entidades Tier 1+2 tienen >80% cobertura por idioma.
 * 2. Ningun entity type tiene 0% cobertura (pipeline roto).
 * 3. Queue routing a Redis configurado.
 * 4. Supervisor worker configurado.
 * 5. Constantes TEXT_ENTITY_FIELDS coinciden con entity types instalados.
 *
 * Uso:
 *   lando drush php:script scripts/validation/validate-translation-coverage.php
 *   php scripts/validation/validate-translation-coverage.php
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$checks = 0;

echo "TRANSLATION-COVERAGE-001: Validando cobertura de traducciones\n";
echo str_repeat('=', 60) . "\n\n";

// ─────────────────────────────────────────────────────────
// Check 1-2: Cobertura por entity type e idioma.
// ─────────────────────────────────────────────────────────

$canvasTypes = ['page_content', 'content_article'];
$textTypes = [
  'site_config', 'content_category', 'content_author',
  'homepage_content', 'feature_card', 'intention_card',
  'stat_item', 'tenant_faq', 'tenant_policy',
];
$allTypes = array_merge($canvasTypes, $textTypes);

$db = \Drupal::database();
$languageManager = \Drupal::languageManager();
$defaultLangcode = $languageManager->getDefaultLanguage()->getId();
$targetLangs = [];

foreach ($languageManager->getLanguages() as $langcode => $language) {
  if ($langcode !== $defaultLangcode && $langcode !== 'und' && $langcode !== 'zxx') {
    $targetLangs[$langcode] = $language->getName();
  }
}

if (empty($targetLangs)) {
  $warnings[] = 'No hay idiomas objetivo configurados (solo idioma por defecto).';
}

$entityTypeManager = \Drupal::entityTypeManager();
$brokenPipeline = [];

foreach ($allTypes as $entityTypeId) {
  $checks++;

  try {
    $definition = $entityTypeManager->getDefinition($entityTypeId);
  }
  catch (\Throwable) {
    $warnings[] = "Entity type '$entityTypeId' no instalado — modulo desactivado.";
    continue;
  }

  if (!$definition->isTranslatable()) {
    $warnings[] = "Entity type '$entityTypeId' no es translatable.";
    continue;
  }

  $dataTable = $definition->getDataTable();
  if (!$dataTable || !$db->schema()->tableExists($dataTable)) {
    $warnings[] = "Tabla de datos '$dataTable' no existe para '$entityTypeId'.";
    continue;
  }

  // Contar entidades en idioma por defecto.
  $totalDefault = (int) $db->select($dataTable, 't')
    ->condition('t.langcode', $defaultLangcode)
    ->countQuery()
    ->execute()
    ->fetchField();

  if ($totalDefault === 0) {
    echo "  [$entityTypeId] Sin entidades en '$defaultLangcode' — omitido.\n";
    continue;
  }

  foreach ($targetLangs as $langcode => $langName) {
    $checks++;
    $totalTranslated = (int) $db->select($dataTable, 't')
      ->condition('t.langcode', $langcode)
      ->countQuery()
      ->execute()
      ->fetchField();

    $pct = round(($totalTranslated / $totalDefault) * 100, 1);
    $status = $pct >= 80 ? 'OK' : ($pct > 0 ? 'WARN' : 'FAIL');

    echo "  [$entityTypeId → $langcode] $totalTranslated/$totalDefault ($pct%) — $status\n";

    if ($pct === 0.0 && $totalDefault > 0) {
      $brokenPipeline[] = "$entityTypeId ($langcode)";
    }
    elseif ($pct < 80) {
      $warnings[] = "$entityTypeId: cobertura $langcode = $pct% (objetivo: 80%).";
    }
  }
}

if (!empty($brokenPipeline)) {
  $errors[] = 'Pipeline de traduccion roto: 0% cobertura para: ' . implode(', ', $brokenPipeline);
}

// ─────────────────────────────────────────────────────────
// Check 3: Queue routing a Redis.
// ─────────────────────────────────────────────────────────
$checks++;
$settingsFile = dirname(__DIR__, 2) . '/config/deploy/settings.ai-queues.php';
if (file_exists($settingsFile)) {
  $content = file_get_contents($settingsFile);
  if (str_contains($content, 'jaraba_i18n_canvas_translation')) {
    echo "\n  [Redis routing] Configurado — OK\n";
  }
  else {
    $errors[] = 'Queue jaraba_i18n_canvas_translation no ruteada a Redis en settings.ai-queues.php.';
  }
}
else {
  $warnings[] = 'settings.ai-queues.php no encontrado en config/deploy/.';
}

// ─────────────────────────────────────────────────────────
// Check 4: Supervisor worker.
// ─────────────────────────────────────────────────────────
$checks++;
$supervisorFile = dirname(__DIR__, 2) . '/config/deploy/supervisor-ai-workers.conf';
if (file_exists($supervisorFile)) {
  $content = file_get_contents($supervisorFile);
  if (str_contains($content, 'jaraba_i18n_canvas_translation') || str_contains($content, 'jaraba-i18n-translation')) {
    echo "  [Supervisor worker] Configurado — OK\n";
  }
  else {
    $errors[] = 'Worker Supervisor para jaraba_i18n_canvas_translation no encontrado.';
  }
}
else {
  $warnings[] = 'supervisor-ai-workers.conf no encontrado en config/deploy/.';
}

// ─────────────────────────────────────────────────────────
// Check 5: Constantes vs entity types instalados.
// ─────────────────────────────────────────────────────────
$checks++;
$missingTypes = [];
foreach ($allTypes as $entityTypeId) {
  if (!$entityTypeManager->hasDefinition($entityTypeId)) {
    $missingTypes[] = $entityTypeId;
  }
}

if (!empty($missingTypes)) {
  $warnings[] = 'Entity types en constantes pero no instalados: ' . implode(', ', $missingTypes);
}
else {
  echo "  [Entity types] Todos instalados — OK\n";
}

// ─────────────────────────────────────────────────────────
// Resumen.
// ─────────────────────────────────────────────────────────
echo "\n" . str_repeat('=', 60) . "\n";
echo "Checks: $checks | Errores: " . count($errors) . " | Avisos: " . count($warnings) . "\n";

if (!empty($warnings)) {
  echo "\nAVISOS:\n";
  foreach ($warnings as $w) {
    echo "  ⚠  $w\n";
  }
}

if (!empty($errors)) {
  echo "\nERRORES:\n";
  foreach ($errors as $e) {
    echo "  ✗  $e\n";
  }
  exit(1);
}

echo "\n✓ TRANSLATION-COVERAGE-001: Todas las validaciones pasaron.\n";
exit(0);
