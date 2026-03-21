<?php

/**
 * @file
 * PROMOTION-COPILOT-SYNC-001: Valida coherencia promociones-copilot.
 *
 * Verifica que:
 * 1. PromotionConfig entity type existe
 * 2. ActivePromotionService existe y está registrado
 * 3. Config inicial (andalucia_ei_piil_2025) existe en config/install
 * 4. Las verticales en configs son canónicas (VERTICAL-CANONICAL-001)
 * 5. Fechas coherentes (start < end)
 * 6. CTA URLs no están vacías
 *
 * Uso: php scripts/validation/validate-promotion-copilot-sync.php
 */

echo "=== PROMOTION-COPILOT-SYNC-001: Coherencia promociones-copilot ===\n\n";

$modulesPath = __DIR__ . '/../../web/modules/custom';
$errors = [];
$warnings = [];
$checks = 0;

$canonicalVerticals = [
  'global', 'empleabilidad', 'emprendimiento', 'comercioconecta',
  'agroconecta', 'jarabalex', 'serviciosconecta', 'andalucia_ei',
  'formacion', 'jaraba_content_hub', 'demo',
];

// CHECK 1: PromotionConfig entity exists.
$checks++;
$entityPath = $modulesPath . '/ecosistema_jaraba_core/src/Entity/PromotionConfig.php';
if (!file_exists($entityPath)) {
  $errors[] = 'PromotionConfig entity no existe';
  echo "[FAIL] PromotionConfig entity\n";
} else {
  echo "[PASS] PromotionConfig entity existe\n";
}

// CHECK 2: ActivePromotionService registered in services.yml.
$checks++;
$servicesPath = $modulesPath . '/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml';
$servicesContent = file_exists($servicesPath) ? file_get_contents($servicesPath) : '';
if (strpos($servicesContent, 'ecosistema_jaraba_core.active_promotion') === false) {
  $errors[] = 'ActivePromotionService no registrado en services.yml';
  echo "[FAIL] ActivePromotionService en services.yml\n";
} else {
  echo "[PASS] ActivePromotionService registrado\n";
}

// CHECK 3: Config install files exist and are valid.
$checks++;
$configPath = $modulesPath . '/ecosistema_jaraba_core/config/install';
$configFiles = glob($configPath . '/ecosistema_jaraba_core.promotion_config.*.yml') ?: [];
if (count($configFiles) === 0) {
  $warnings[] = 'No hay configs iniciales de PromotionConfig en config/install';
  echo "[WARN] Sin configs iniciales\n";
} else {
  echo "[PASS] " . count($configFiles) . " config(s) inicial(es) encontrada(s)\n";
}

// CHECK 4-6: Validar cada config file.
foreach ($configFiles as $file) {
  $filename = basename($file);
  $content = file_get_contents($file);
  // Parse YAML fields via regex (no yaml extension needed).
  preg_match('/^vertical:\s*[\'"]?(.+?)[\'"]?\s*$/m', $content, $verticalMatch);
  preg_match('/^cta_url:\s*(.+)$/m', $content, $ctaMatch);
  preg_match('/^date_start:\s*[\'"]?(.+?)[\'"]?\s*$/m', $content, $startMatch);
  preg_match('/^date_end:\s*[\'"]?(.+?)[\'"]?\s*$/m', $content, $endMatch);

  $vertical = trim($verticalMatch[1] ?? '');
  $ctaUrl = trim($ctaMatch[1] ?? '');
  $dateStart = trim($startMatch[1] ?? '');
  $dateEnd = trim($endMatch[1] ?? '');

  // CHECK: Vertical is canonical.
  $checks++;
  if (!in_array($vertical, $canonicalVerticals, true)) {
    $errors[] = "{$filename}: vertical '{$vertical}' no es canónica";
    echo "[FAIL] {$filename}: vertical '{$vertical}' no canónica\n";
  } else {
    echo "[PASS] {$filename}: vertical '{$vertical}' OK\n";
  }

  // CHECK: CTA URL not empty.
  $checks++;
  if ($ctaUrl === '' || $ctaUrl === "''") {
    $errors[] = "{$filename}: cta_url vacío";
    echo "[FAIL] {$filename}: cta_url vacío\n";
  } else {
    echo "[PASS] {$filename}: cta_url = {$ctaUrl}\n";
  }

  // CHECK: Dates coherent.
  $checks++;
  if ($dateStart !== '' && $dateEnd !== '' && $dateStart > $dateEnd) {
    $errors[] = "{$filename}: date_start ({$dateStart}) > date_end ({$dateEnd})";
    echo "[FAIL] {$filename}: fechas incoherentes\n";
  } else {
    echo "[PASS] {$filename}: fechas coherentes\n";
  }
}

// CHECK 7: Schema file exists.
$checks++;
$schemaPath = $modulesPath . '/ecosistema_jaraba_core/config/schema/ecosistema_jaraba_core.promotion_config.schema.yml';
if (!file_exists($schemaPath)) {
  $errors[] = 'Schema PromotionConfig no existe';
  echo "[FAIL] Schema file\n";
} else {
  echo "[PASS] Schema file existe\n";
}

// CHECK 8: hook_update exists for entity install.
$checks++;
$installPath = $modulesPath . '/ecosistema_jaraba_core/ecosistema_jaraba_core.install';
if (file_exists($installPath)) {
  $installContent = file_get_contents($installPath);
  if (strpos($installContent, 'promotion_config') !== false) {
    echo "[PASS] hook_update incluye promotion_config\n";
  } else {
    $errors[] = 'hook_update no incluye installEntityType para promotion_config';
    echo "[FAIL] hook_update sin promotion_config\n";
  }
} else {
  $errors[] = '.install file no existe';
}

echo "\n=== Resumen ===\n";
echo "Checks: {$checks}\n";
echo "Errores: " . count($errors) . "\n";
echo "Advertencias: " . count($warnings) . "\n";

if (!empty($errors)) {
  echo "\nErrores:\n";
  foreach ($errors as $e) {
    echo "  - {$e}\n";
  }
}

$exitCode = count($errors) > 0 ? 1 : 0;
echo "\n" . ($exitCode === 0 ? '[OK]' : '[FAIL]') . " PROMOTION-COPILOT-SYNC-001: Validación completada.\n";
exit($exitCode);
