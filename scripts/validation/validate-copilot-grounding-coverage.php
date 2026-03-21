<?php

/**
 * @file
 * COPILOT-GROUNDING-COVERAGE-001: Valida que cada vertical tiene grounding.
 *
 * Verifica que ContentGroundingService tiene GroundingProviders registrados
 * para todas las verticales canonicas del SaaS.
 *
 * Uso: php scripts/validation/validate-copilot-grounding-coverage.php
 */

$modulesPath = __DIR__ . '/../../web/modules/custom';
$errors = [];
$warnings = [];
$checks = 0;

echo "=== COPILOT-GROUNDING-COVERAGE-001: Cobertura grounding por vertical ===\n\n";

// Verticales canonicas (VERTICAL-CANONICAL-001).
$canonicalVerticals = [
  'empleabilidad',
  'emprendimiento',
  'comercioconecta',
  'agroconecta',
  'jarabalex',
  'serviciosconecta',
  'andalucia_ei',
  'formacion',
  'jaraba_content_hub',
  'demo',
];

// CHECK 1: Buscar GroundingProviderInterface existe.
$checks++;
$interfacePath = $modulesPath . '/jaraba_copilot_v2/src/Grounding/GroundingProviderInterface.php';
if (!file_exists($interfacePath)) {
  $errors[] = 'GroundingProviderInterface no existe en ' . $interfacePath;
  echo "[FAIL] GroundingProviderInterface no existe\n";
} else {
  echo "[PASS] GroundingProviderInterface existe\n";
}

// CHECK 2: Buscar CompilerPass existe.
$checks++;
$compilerPassPath = $modulesPath . '/jaraba_copilot_v2/src/DependencyInjection/Compiler/GroundingProviderCompilerPass.php';
if (!file_exists($compilerPassPath)) {
  $errors[] = 'GroundingProviderCompilerPass no existe';
  echo "[FAIL] GroundingProviderCompilerPass no existe\n";
} else {
  echo "[PASS] GroundingProviderCompilerPass existe\n";
}

// CHECK 3: Buscar ServiceProvider registra el CompilerPass.
$checks++;
$serviceProviderPath = $modulesPath . '/jaraba_copilot_v2/src/JarabaCopilotV2ServiceProvider.php';
if (!file_exists($serviceProviderPath)) {
  $errors[] = 'JarabaCopilotV2ServiceProvider no existe — CompilerPass no se registrará';
  echo "[FAIL] JarabaCopilotV2ServiceProvider no existe\n";
} else {
  $content = file_get_contents($serviceProviderPath);
  if (strpos($content, 'GroundingProviderCompilerPass') === false) {
    $errors[] = 'ServiceProvider no registra GroundingProviderCompilerPass';
    echo "[FAIL] ServiceProvider no registra GroundingProviderCompilerPass\n";
  } else {
    echo "[PASS] ServiceProvider registra GroundingProviderCompilerPass\n";
  }
}

// CHECK 4: Buscar providers registrados en services.yml de todos los modulos.
$checks++;
echo "\n--- Providers registrados (tag: jaraba_copilot_v2.grounding_provider) ---\n";

$providerVerticals = [];
// Buscar en TODOS los services.yml del proyecto.
$servicesFiles = glob($modulesPath . '/*/*.services.yml') ?: [];

foreach ($servicesFiles as $file) {
  $content = file_get_contents($file);
  if (strpos($content, 'jaraba_copilot_v2.grounding_provider') === false) {
    continue;
  }

  // Buscar service IDs que tienen el tag. Patron: el service ID aparece
  // varias lineas antes del tag en el mismo bloque YAML.
  $lines = explode("\n", $content);
  $currentService = '';
  $moduleName = basename(dirname($file));

  for ($i = 0; $i < count($lines); $i++) {
    // Detectar inicio de service (2 espacios + nombre + :).
    if (preg_match('/^  ([\w.]+):\s*$/', $lines[$i], $m)) {
      $currentService = $m[1];
    }
    // Si dentro de un service encontramos el tag.
    if ($currentService && strpos($lines[$i], 'jaraba_copilot_v2.grounding_provider') !== false) {
      echo "  [FOUND] {$currentService} en {$moduleName}\n";
      $providerVerticals[] = $currentService;
    }
  }
}

if (empty($providerVerticals)) {
  $errors[] = 'No se encontraron GroundingProviders registrados en ningun services.yml';
  echo "  [FAIL] 0 providers encontrados\n";
} else {
  echo "  Total: " . count($providerVerticals) . " provider(s)\n";
}

// CHECK 5: PromotionConfig entity existe.
$checks++;
$promotionConfigPath = $modulesPath . '/ecosistema_jaraba_core/src/Entity/PromotionConfig.php';
if (!file_exists($promotionConfigPath)) {
  $errors[] = 'PromotionConfig entity no existe — ActivePromotionService no funcionará';
  echo "\n[FAIL] PromotionConfig entity no existe\n";
} else {
  echo "\n[PASS] PromotionConfig entity existe\n";
}

// CHECK 6: ActivePromotionService existe.
$checks++;
$activePromotionPath = $modulesPath . '/ecosistema_jaraba_core/src/Service/ActivePromotionService.php';
if (!file_exists($activePromotionPath)) {
  $errors[] = 'ActivePromotionService no existe';
  echo "[FAIL] ActivePromotionService no existe\n";
} else {
  echo "[PASS] ActivePromotionService existe\n";
}

// CHECK 7: ContentGroundingService acepta providers (tiene addProvider method).
$checks++;
$groundingServicePath = $modulesPath . '/jaraba_copilot_v2/src/Service/ContentGroundingService.php';
if (file_exists($groundingServicePath)) {
  $content = file_get_contents($groundingServicePath);
  if (strpos($content, 'addProvider') === false) {
    $errors[] = 'ContentGroundingService no tiene metodo addProvider — no es v2';
    echo "[FAIL] ContentGroundingService sin addProvider (no es v2)\n";
  } else {
    echo "[PASS] ContentGroundingService v2 con addProvider\n";
  }
} else {
  $errors[] = 'ContentGroundingService no existe';
}

// CHECK 8: PublicCopilotController usa prompt dinamico.
$checks++;
$publicControllerPath = $modulesPath . '/jaraba_copilot_v2/src/Controller/PublicCopilotController.php';
if (file_exists($publicControllerPath)) {
  $content = file_get_contents($publicControllerPath);
  if (strpos($content, 'buildDynamicPublicSystemPrompt') === false) {
    $warnings[] = 'PublicCopilotController no usa buildDynamicPublicSystemPrompt';
    echo "[WARN] PublicCopilotController sin prompt dinamico\n";
  } else {
    echo "[PASS] PublicCopilotController usa prompt dinamico\n";
  }
} else {
  $errors[] = 'PublicCopilotController no existe';
}

// CHECK 9: CopilotLeadCaptureService existe.
$checks++;
$leadCapturePath = $modulesPath . '/jaraba_copilot_v2/src/Service/CopilotLeadCaptureService.php';
if (!file_exists($leadCapturePath)) {
  $warnings[] = 'CopilotLeadCaptureService no existe — leads desde copilot no se capturan';
  echo "[WARN] CopilotLeadCaptureService no existe\n";
} else {
  echo "[PASS] CopilotLeadCaptureService existe\n";
}

// --- Resumen ---
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

if (!empty($warnings)) {
  echo "\nAdvertencias:\n";
  foreach ($warnings as $w) {
    echo "  - {$w}\n";
  }
}

$exitCode = count($errors) > 0 ? 1 : 0;
echo "\n" . ($exitCode === 0 ? '[OK]' : '[FAIL]') . " COPILOT-GROUNDING-COVERAGE-001: Validación completada.\n";
exit($exitCode);
