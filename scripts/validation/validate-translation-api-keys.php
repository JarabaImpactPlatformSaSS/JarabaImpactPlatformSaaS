<?php

/**
 * @file
 * TRANSLATION-API-KEYS-001: Valida disponibilidad de API keys para traduccion IA.
 *
 * Checks:
 * 1. ANTHROPIC_API_KEY disponible via getenv().
 * 2. Key module resuelve anthropic_api con valor no vacio.
 * 3. settings.env.php legible por www-data (permisos).
 *
 * Uso:
 *   lando drush php:script scripts/validation/validate-translation-api-keys.php
 *   php scripts/validation/validate-translation-api-keys.php
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$checks = 0;

echo "TRANSLATION-API-KEYS-001: Validando API keys para traduccion IA\n";
echo str_repeat('=', 60) . "\n\n";

// ─────────────────────────────────────────────────────────
// Check 1: ANTHROPIC_API_KEY en entorno.
// ─────────────────────────────────────────────────────────
$checks++;
$apiKey = getenv('ANTHROPIC_API_KEY');
if ($apiKey !== FALSE && $apiKey !== '') {
  $len = strlen($apiKey);
  $prefix = substr($apiKey, 0, 10);
  echo "  [ANTHROPIC_API_KEY] Disponible ({$len} chars, prefix: {$prefix}...) — OK\n";

  // Verificar formato basico.
  $checks++;
  if (!str_starts_with($apiKey, 'sk-ant-')) {
    $warnings[] = "ANTHROPIC_API_KEY no tiene prefijo 'sk-ant-' esperado (tiene: {$prefix}).";
  }
  else {
    echo "  [ANTHROPIC_API_KEY formato] Prefijo sk-ant-* — OK\n";
  }
}
else {
  $errors[] = 'ANTHROPIC_API_KEY no disponible en getenv(). El worker de traduccion no puede funcionar.';
}

// ─────────────────────────────────────────────────────────
// Check 2: Key module resuelve la key.
// ─────────────────────────────────────────────────────────
$checks++;
if (\Drupal::hasService('key.repository')) {
  $keyEntity = \Drupal::service('key.repository')->getKey('anthropic_api');
  if ($keyEntity !== NULL) {
    $keyValue = $keyEntity->getKeyValue();
    if ($keyValue !== NULL && $keyValue !== '') {
      echo "  [Key module anthropic_api] Resuelve (" . strlen($keyValue) . " chars) — OK\n";
    }
    else {
      $errors[] = 'Key module: anthropic_api existe pero getKeyValue() devuelve vacio.';
    }
  }
  else {
    $warnings[] = 'Key module: entity anthropic_api no encontrada.';
  }
}
else {
  $warnings[] = 'Key module no disponible (key.repository service no existe).';
}

// ─────────────────────────────────────────────────────────
// Check 3: settings.env.php permisos (solo en produccion).
// ─────────────────────────────────────────────────────────
$checks++;
$settingsEnvPath = DRUPAL_ROOT . '/../config/deploy/settings.env.php';
if (file_exists($settingsEnvPath)) {
  $perms = fileperms($settingsEnvPath);
  $octal = substr(sprintf('%o', $perms), -3);
  $group = filegroup($settingsEnvPath);
  $groupInfo = function_exists('posix_getgrgid') ? posix_getgrgid($group) : NULL;
  $groupName = $groupInfo['name'] ?? (string) $group;

  // www-data debe poder leer (grupo con read permission).
  $groupReadable = ($perms & 0040) !== 0;

  if ($groupReadable) {
    echo "  [settings.env.php permisos] {$octal} (grupo: {$groupName}) — OK\n";
  }
  else {
    $errors[] = "settings.env.php permisos {$octal} — grupo {$groupName} no tiene lectura. El worker Supervisor (www-data) no podra leer las API keys.";
  }
}
else {
  // En local (Lando) no existe, es normal.
  echo "  [settings.env.php] No existe (entorno local) — skip\n";
}

// ─────────────────────────────────────────────────────────
// Resumen.
// ─────────────────────────────────────────────────────────
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
    echo "  x  {$e}\n";
  }
  exit(1);
}

echo "\n+ TRANSLATION-API-KEYS-001: Todas las validaciones pasaron.\n";
exit(0);
