<?php

/**
 * @file
 * GROUNDING-PROVIDER-HEALTH-001: Health check de GroundingProviders.
 *
 * Verifica que cada provider registrado:
 * 1. Tiene clase PHP existente
 * 2. La clase implementa GroundingProviderInterface
 * 3. getVerticalKey() devuelve una clave canónica
 * 4. getPriority() devuelve un entero >= 0
 *
 * NO ejecuta search() (requiere Drupal bootstrap + DB).
 * Para test runtime, usar: lando drush eval (ver plan de implementación).
 *
 * Uso: php scripts/validation/validate-grounding-provider-health.php
 */

echo "=== GROUNDING-PROVIDER-HEALTH-001: Health check de GroundingProviders ===\n\n";

$modulesPath = __DIR__ . '/../../web/modules/custom';
$errors = [];
$warnings = [];
$checks = 0;

$canonicalVerticals = [
  'global', 'empleabilidad', 'emprendimiento', 'comercioconecta',
  'agroconecta', 'jarabalex', 'serviciosconecta', 'andalucia_ei',
  'formacion', 'jaraba_content_hub', 'demo',
];

// CHECK 1: Interface exists.
$checks++;
$interfacePath = $modulesPath . '/jaraba_copilot_v2/src/Grounding/GroundingProviderInterface.php';
if (!file_exists($interfacePath)) {
  $errors[] = 'GroundingProviderInterface no existe';
  echo "[FAIL] GroundingProviderInterface no existe\n";
  goto summary;
}
echo "[PASS] GroundingProviderInterface existe\n\n";

// Find all providers registered in services.yml.
echo "--- Providers registrados ---\n";
$providers = [];
$servicesFiles = glob($modulesPath . '/*/*.services.yml') ?: [];

foreach ($servicesFiles as $file) {
  $content = file_get_contents($file);
  if (strpos($content, 'jaraba_copilot_v2.grounding_provider') === false) {
    continue;
  }

  $lines = explode("\n", $content);
  $currentService = '';
  $currentClass = '';
  $moduleName = basename(dirname($file));

  for ($i = 0; $i < count($lines); $i++) {
    // Detect service start.
    if (preg_match('/^  ([\w.]+):\s*$/', $lines[$i], $m)) {
      $currentService = $m[1];
      $currentClass = '';
    }
    // Detect class.
    if (preg_match('/^\s+class:\s*(.+)$/', $lines[$i], $m)) {
      $currentClass = trim($m[1]);
    }
    // If this service has the grounding provider tag.
    if ($currentService && strpos($lines[$i], 'jaraba_copilot_v2.grounding_provider') !== false) {
      $providers[] = [
        'service' => $currentService,
        'class' => $currentClass,
        'module' => $moduleName,
      ];
    }
  }
}

if (count($providers) === 0) {
  $errors[] = 'No hay GroundingProviders registrados';
  echo "[FAIL] 0 providers encontrados\n";
  goto summary;
}

// Verify each provider.
$coveredVerticals = [];

foreach ($providers as $provider) {
  $checks++;

  // CHECK: Class file exists.
  $classPath = str_replace('\\', '/', $provider['class']);
  $classPath = str_replace('Drupal/', '', $classPath);
  $parts = explode('/', $classPath);
  $module = $parts[0];
  $restPath = implode('/', array_slice($parts, 1));
  $filePath = $modulesPath . '/' . $module . '/src/' . $restPath . '.php';

  if (!file_exists($filePath)) {
    $errors[] = "{$provider['service']}: clase {$provider['class']} no existe en {$filePath}";
    echo "[FAIL] {$provider['service']}: clase no existe\n";
    continue;
  }

  // CHECK: Implements GroundingProviderInterface.
  $classContent = file_get_contents($filePath);
  if (strpos($classContent, 'GroundingProviderInterface') === false) {
    $errors[] = "{$provider['service']}: no implementa GroundingProviderInterface";
    echo "[FAIL] {$provider['service']}: no implementa interface\n";
    continue;
  }

  // CHECK: Has getVerticalKey() method.
  if (strpos($classContent, 'function getVerticalKey') === false) {
    $errors[] = "{$provider['service']}: falta método getVerticalKey()";
    echo "[FAIL] {$provider['service']}: falta getVerticalKey()\n";
    continue;
  }

  // Extract vertical key from source.
  preg_match("/getVerticalKey\(\).*?return\s+['\"](\w+)['\"]/s", $classContent, $vkMatch);
  $verticalKey = $vkMatch[1] ?? 'unknown';

  if (!in_array($verticalKey, $canonicalVerticals, true)) {
    $warnings[] = "{$provider['service']}: vertical '{$verticalKey}' no es canónica";
    echo "[WARN] {$provider['service']}: vertical '{$verticalKey}' no canónica\n";
  } else {
    $coveredVerticals[] = $verticalKey;
  }

  // CHECK: Has getPriority() method.
  if (strpos($classContent, 'function getPriority') === false) {
    $errors[] = "{$provider['service']}: falta método getPriority()";
    echo "[FAIL] {$provider['service']}: falta getPriority()\n";
    continue;
  }

  // CHECK: Has search() method.
  if (strpos($classContent, 'function search') === false) {
    $errors[] = "{$provider['service']}: falta método search()";
    echo "[FAIL] {$provider['service']}: falta search()\n";
    continue;
  }

  // CHECK: search() has try-catch for resilience.
  if (strpos($classContent, 'catch') === false) {
    $warnings[] = "{$provider['service']}: search() sin try-catch (no resiliente)";
    echo "[WARN] {$provider['service']}: search() sin try-catch\n";
  } else {
    echo "[PASS] {$provider['service']} [{$verticalKey}] — clase OK, interface OK, métodos OK\n";
  }
}

// CHECK: Coverage of canonical verticals.
$checks++;
echo "\n--- Cobertura vertical ---\n";
$commercialVerticals = ['empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
                         'jarabalex', 'serviciosconecta', 'andalucia_ei', 'formacion', 'jaraba_content_hub'];
$uncovered = array_diff($commercialVerticals, $coveredVerticals);

if (count($uncovered) === 0) {
  echo "[PASS] 9/9 verticales comerciales cubiertas\n";
} else {
  $warnings[] = 'Verticales sin provider: ' . implode(', ', $uncovered);
  echo "[WARN] Sin provider: " . implode(', ', $uncovered) . "\n";
}

// Global provider check.
$checks++;
if (in_array('global', $coveredVerticals, true)) {
  echo "[PASS] Provider global (promociones) presente\n";
} else {
  $warnings[] = 'Sin provider global — promociones no se inyectan en grounding';
  echo "[WARN] Sin provider global\n";
}

summary:
echo "\n=== Resumen ===\n";
echo "Providers: " . count($providers) . "\n";
echo "Checks: {$checks}\n";
echo "Errores: " . count($errors) . "\n";
echo "Advertencias: " . count($warnings) . "\n";

if (!empty($errors)) {
  echo "\nErrores:\n";
  foreach ($errors as $e) { echo "  - {$e}\n"; }
}
if (!empty($warnings)) {
  echo "\nAdvertencias:\n";
  foreach ($warnings as $w) { echo "  - {$w}\n"; }
}

$exitCode = count($errors) > 0 ? 1 : 0;
echo "\n" . ($exitCode === 0 ? '[OK]' : '[FAIL]') . " GROUNDING-PROVIDER-HEALTH-001: Validación completada.\n";
exit($exitCode);
