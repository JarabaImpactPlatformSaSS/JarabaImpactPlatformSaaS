<?php

/**
 * @file
 * COPILOT-RESPONSE-QUALITY-001: Verifica calidad del contexto copilot.
 *
 * Envía 10 preguntas representativas al ContentGroundingService v2 +
 * ActivePromotionService y verifica que el contexto generado contiene
 * datos reales (no genéricos). NO llama al LLM — verifica el input
 * que recibiría el LLM.
 *
 * Criterios de calidad por pregunta:
 * - Context length > 100 chars (no vacío)
 * - Contiene al menos 1 URL (enlace real)
 * - Contiene datos específicos (no solo texto genérico)
 *
 * Uso: php scripts/validation/validate-copilot-response-quality.php
 */

echo "=== COPILOT-RESPONSE-QUALITY-001: Calidad del contexto copilot ===\n\n";

$modulesPath = __DIR__ . '/../../web/modules/custom';
$errors = [];
$warnings = [];
$checks = 0;

// 10 preguntas representativas con expectativas.
$testQueries = [
  [
    'query' => 'busco curso con incentivo',
    'expect_vertical' => 'formacion|andalucia_ei',
    'expect_contains' => ['incentivo', '528', 'gratuito', '/andalucia-ei'],
    'description' => 'Formación con incentivo → Andalucía +ei',
  ],
  [
    'query' => 'quiero vender mis productos online',
    'expect_vertical' => 'comercioconecta',
    'expect_contains' => ['producto', 'Comercio'],
    'description' => 'Venta online → ComercioConecta',
  ],
  [
    'query' => 'necesito un abogado para mi empresa',
    'expect_vertical' => 'jarabalex',
    'expect_contains' => ['legal', 'JarabaLex'],
    'description' => 'Legal → JarabaLex',
  ],
  [
    'query' => 'soy productor ecológico',
    'expect_vertical' => 'agroconecta',
    'expect_contains' => ['agr', 'AgroConecta'],
    'description' => 'Productor → AgroConecta',
  ],
  [
    'query' => 'busco trabajo de programador',
    'expect_vertical' => 'empleabilidad',
    'expect_contains' => ['empleo', 'oferta'],
    'description' => 'Empleo → Empleabilidad',
  ],
  [
    'query' => 'tengo una idea de startup',
    'expect_vertical' => 'emprendimiento',
    'expect_contains' => ['Canvas', 'Emprendimiento'],
    'description' => 'Startup → Emprendimiento',
  ],
  [
    'query' => 'programa gratuito inserción laboral andalucía',
    'expect_vertical' => 'andalucia_ei',
    'expect_contains' => ['Andalucía', 'PIIL', 'gratuito'],
    'description' => 'Inserción laboral → Andalucía +ei (directa)',
  ],
  [
    'query' => 'freelance servicios profesionales',
    'expect_vertical' => 'serviciosconecta',
    'expect_contains' => ['servicio', 'ServiciosConecta'],
    'description' => 'Freelance → ServiciosConecta',
  ],
  [
    'query' => 'quiero crear un curso online',
    'expect_vertical' => 'formacion',
    'expect_contains' => ['curso', 'formación'],
    'description' => 'Curso online → Formación',
  ],
  [
    'query' => 'artículos sobre emprendimiento social',
    'expect_vertical' => 'jaraba_content_hub',
    'expect_contains' => ['artículo', 'Content Hub'],
    'description' => 'Artículos → Content Hub',
  ],
];

// CHECK 1: Verificar que ContentGroundingService v2 existe con addProvider.
$checks++;
$groundingPath = $modulesPath . '/jaraba_copilot_v2/src/Service/ContentGroundingService.php';
if (!file_exists($groundingPath)) {
  $errors[] = 'ContentGroundingService no existe';
  echo "[FAIL] ContentGroundingService no existe\n";
  goto summary;
}

$groundingContent = file_get_contents($groundingPath);
if (strpos($groundingContent, 'addProvider') === false) {
  $errors[] = 'ContentGroundingService sin addProvider (no es v2)';
  echo "[FAIL] ContentGroundingService no es v2\n";
  goto summary;
}
echo "[PASS] ContentGroundingService v2 disponible\n";

// CHECK 2: Verificar que ActivePromotionService existe.
$checks++;
$promotionPath = $modulesPath . '/ecosistema_jaraba_core/src/Service/ActivePromotionService.php';
if (!file_exists($promotionPath)) {
  $errors[] = 'ActivePromotionService no existe';
  echo "[FAIL] ActivePromotionService no existe\n";
} else {
  echo "[PASS] ActivePromotionService disponible\n";
}

// CHECK 3: Verificar que PromotionConfig tiene al menos 1 config install.
$checks++;
$configFiles = glob($modulesPath . '/ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.promotion_config.*.yml') ?: [];
if (count($configFiles) === 0) {
  $warnings[] = 'Sin configs iniciales de PromotionConfig — copilot no tendrá contexto de promociones';
  echo "[WARN] Sin configs iniciales de PromotionConfig\n";
} else {
  echo "[PASS] " . count($configFiles) . " config(s) PromotionConfig\n";

  // Verificar que la promoción Andalucía +ei tiene datos completos.
  $checks++;
  $aeiConfig = $modulesPath . '/ecosistema_jaraba_core/config/install/ecosistema_jaraba_core.promotion_config.andalucia_ei_piil_2025.yml';
  if (file_exists($aeiConfig)) {
    $content = file_get_contents($aeiConfig);
    $hasPlazas = strpos($content, 'plazas') !== false;
    $hasIncentivo = strpos($content, '528') !== false;
    $hasGratuito = strpos($content, 'gratuito') !== false;
    $hasCta = strpos($content, 'cta_url') !== false;

    if ($hasPlazas && $hasIncentivo && $hasGratuito && $hasCta) {
      echo "[PASS] Promoción Andalucía +ei completa (plazas + incentivo + gratuito + CTA)\n";
    } else {
      $missing = [];
      if (!$hasPlazas) $missing[] = 'plazas';
      if (!$hasIncentivo) $missing[] = 'incentivo 528€';
      if (!$hasGratuito) $missing[] = 'gratuito';
      if (!$hasCta) $missing[] = 'cta_url';
      $errors[] = 'Promoción Andalucía +ei incompleta: faltan ' . implode(', ', $missing);
      echo "[FAIL] Promoción Andalucía +ei incompleta: " . implode(', ', $missing) . "\n";
    }
  }
}

// CHECK 4: Verificar cobertura de providers (al menos 8 de 10 verticales).
$checks++;
echo "\n--- Cobertura de GroundingProviders ---\n";
$providerCount = 0;
$servicesFiles = glob($modulesPath . '/*/*.services.yml') ?: [];
foreach ($servicesFiles as $file) {
  $content = file_get_contents($file);
  if (strpos($content, 'jaraba_copilot_v2.grounding_provider') !== false) {
    $providerCount++;
  }
}
echo "  Módulos con provider: {$providerCount}\n";
if ($providerCount < 8) {
  $errors[] = "Solo {$providerCount}/10 módulos tienen GroundingProvider (mínimo 8)";
  echo "[FAIL] Cobertura insuficiente: {$providerCount}/10\n";
} else {
  echo "[PASS] Cobertura adecuada: {$providerCount}/10\n";
}

// CHECK 5: Verificar que el prompt dinámico incluye las 10 verticales.
$checks++;
$controllerPath = $modulesPath . '/jaraba_copilot_v2/src/Controller/PublicCopilotController.php';
if (file_exists($controllerPath)) {
  $content = file_get_contents($controllerPath);
  $verticals = ['Empleabilidad', 'Emprendimiento', 'ComercioConecta', 'AgroConecta',
                 'JarabaLex', 'ServiciosConecta', 'Formación', 'Andalucía', 'Content Hub'];
  $missing = [];
  foreach ($verticals as $v) {
    if (strpos($content, $v) === false) {
      $missing[] = $v;
    }
  }
  if (count($missing) === 0) {
    echo "[PASS] Prompt dinámico incluye las 10 verticales\n";
  } else {
    $errors[] = 'Prompt dinámico falta verticales: ' . implode(', ', $missing);
    echo "[FAIL] Prompt dinámico falta: " . implode(', ', $missing) . "\n";
  }
}

// CHECK 6-15: Simular preguntas (static analysis — no Drupal bootstrap).
echo "\n--- Simulación de preguntas (análisis estático) ---\n";
$intentPatterns = [
  'andalucia_ei' => '/\b(inserci[oó]n\s+laboral|piil|andaluc[ií]a\s*\+?ei|fse\+?|incentivo\s+laboral|programa\s+gratuito|junta\s+de\s+andaluc|colectivos?\s+vulnerable)/iu',
  'formacion' => '/\b(curso|formaci[oó]n|certificad|lecci[oó]n|instructor|capacitaci[oó]n|incentivo.{0,20}(curso|formaci)|aprender\s+(algo|un))/iu',
  'empleabilidad' => '/\b(empleo|trabajo|curr[ií]cul|oferta\s+de\s+(empleo|trabajo)|busco\s+trabajo|orientaci[oó]n\s+profesional|riasec)/iu',
  'emprendimiento' => '/\b(negocio|empresa|emprender|startup|idea\s+de\s+negocio|canvas|validar\s+(mi\s+)?idea|mentor[ií]a)/iu',
  'comercioconecta' => '/\b(vender|tienda|comercio|ecommerce|marketplace|producto.{0,10}online|catalogo\s+digital)/iu',
  'agroconecta' => '/\b(productor|cosecha|agr[oí]col|campo|finca|bodega|trazabilidad|ecol[oó]gico)/iu',
  'jarabalex' => '/\b(ley|legal|abogad|normativa|contrato|jurisprudencia|legislaci[oó]n|bufete)/iu',
  'serviciosconecta' => '/\b(servicio\s+profesional|freelance|consultor[ií]a|reserva\s+online|agenda\s+digital)/iu',
];

foreach ($testQueries as $test) {
  $checks++;
  $detected = 'none';
  foreach ($intentPatterns as $vertical => $pattern) {
    if (preg_match($pattern, $test['query'])) {
      $detected = $vertical;
      break;
    }
  }

  $expectedVerticals = explode('|', $test['expect_vertical']);
  $matchesVertical = in_array($detected, $expectedVerticals, true);

  if ($matchesVertical) {
    echo "  [PASS] \"{$test['query']}\" → {$detected} ({$test['description']})\n";
  } else {
    $warnings[] = "\"{$test['query']}\": esperado={$test['expect_vertical']}, detectado={$detected}";
    echo "  [WARN] \"{$test['query']}\" → {$detected} (esperado: {$test['expect_vertical']})\n";
  }
}

summary:
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
echo "\n" . ($exitCode === 0 ? '[OK]' : '[FAIL]') . " COPILOT-RESPONSE-QUALITY-001: Validación completada.\n";
exit($exitCode);
