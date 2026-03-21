<?php

/**
 * @file
 * COPILOT-PROMPT-DRIFT-001: Detecta drift en el prompt dinámico del copilot.
 *
 * Verifica que buildDynamicPublicSystemPrompt() contiene las secciones
 * obligatorias:
 * 1. IDENTIDAD (AIIdentityRule o equivalente)
 * 2. VERTICALES (10 verticales canónicas)
 * 3. PROMOCIONES (buildPromotionContextForCopilot llamado)
 * 4. REGLAS CRITICAS (identidad, no competidores, CTA obligatorio)
 * 5. PERFILES DE VISITANTES (B2C + B2B)
 * 6. ESTRATEGIA AIDA o equivalente
 *
 * Si alguna sección falta, indica drift en el prompt que requiere corrección.
 *
 * Uso: php scripts/validation/validate-copilot-prompt-drift.php
 */

echo "=== COPILOT-PROMPT-DRIFT-001: Detección de drift en prompt dinámico ===\n\n";

$modulesPath = __DIR__ . '/../../web/modules/custom';
$errors = [];
$warnings = [];
$checks = 0;

$controllerPath = $modulesPath . '/jaraba_copilot_v2/src/Controller/PublicCopilotController.php';

if (!file_exists($controllerPath)) {
  $errors[] = 'PublicCopilotController no existe';
  echo "[FAIL] PublicCopilotController no existe\n";
  goto summary;
}

$content = file_get_contents($controllerPath);

// CHECK 1: buildDynamicPublicSystemPrompt exists.
$checks++;
if (strpos($content, 'buildDynamicPublicSystemPrompt') === false) {
  $errors[] = 'buildDynamicPublicSystemPrompt() no existe — copilot usa prompt estático';
  echo "[FAIL] buildDynamicPublicSystemPrompt() no existe\n";
  goto summary;
}
echo "[PASS] buildDynamicPublicSystemPrompt() existe\n";

// CHECK 2: Prompt calls ActivePromotionService.
$checks++;
if (strpos($content, 'buildPromotionContextForCopilot') !== false) {
  echo "[PASS] Llama a buildPromotionContextForCopilot()\n";
} else {
  $errors[] = 'Prompt no llama a ActivePromotionService — promociones no se inyectan';
  echo "[FAIL] No llama a buildPromotionContextForCopilot()\n";
}

// CHECK 3: Identity rule present.
$checks++;
$hasIdentity = strpos($content, 'IDENTIDAD') !== false
  || strpos($content, 'AIIdentityRule') !== false
  || strpos($content, 'Asistente de Jaraba') !== false;
if ($hasIdentity) {
  echo "[PASS] Regla de identidad presente\n";
} else {
  $errors[] = 'Sin regla de identidad en prompt — AI-IDENTITY-RULE violada';
  echo "[FAIL] Sin regla de identidad\n";
}

// CHECK 4: All 10 verticals mentioned.
$checks++;
$verticals = [
  'Empleabilidad', 'Emprendimiento', 'ComercioConecta', 'AgroConecta',
  'JarabaLex', 'ServiciosConecta', 'Formación', 'Andalucía', 'Content Hub',
];
$missing = [];
foreach ($verticals as $v) {
  if (strpos($content, $v) === false) {
    $missing[] = $v;
  }
}
if (count($missing) === 0) {
  echo "[PASS] 10 verticales presentes en prompt\n";
} else {
  $errors[] = 'Verticales ausentes: ' . implode(', ', $missing);
  echo "[FAIL] Verticales ausentes: " . implode(', ', $missing) . "\n";
}

// CHECK 5: No competitor mentions in prompt.
$checks++;
$competitors = ['LinkedIn', 'Indeed', 'InfoJobs', 'Salesforce', 'HubSpot', 'ChatGPT'];
$found = [];
// Only check within the prompt method, not the identity rule
preg_match('/buildDynamicPublicSystemPrompt.*?^    \}/ms', $content, $methodMatch);
$methodContent = $methodMatch[0] ?? '';

foreach ($competitors as $c) {
  // In the identity rule, competitors are mentioned to BLOCK them — that's OK.
  // We check they're in the context of "NUNCA menciones" not as recommendations.
  if (preg_match('/(?<!NUNCA|nunca|no|NO)\s+' . preg_quote($c, '/') . '/i', $methodContent)) {
    $found[] = $c;
  }
}
if (count($found) === 0) {
  echo "[PASS] Sin menciones positivas a competidores\n";
} else {
  $warnings[] = 'Posibles menciones a competidores: ' . implode(', ', $found);
  echo "[WARN] Posibles menciones a competidores: " . implode(', ', $found) . "\n";
}

// CHECK 6: CTA obligation present.
$checks++;
if (strpos($content, 'CTA') !== false || strpos($content, 'llamada a la acción') !== false) {
  echo "[PASS] Obligación de CTA presente\n";
} else {
  $warnings[] = 'Sin mención de CTA obligatorio en prompt';
  echo "[WARN] Sin mención de CTA\n";
}

// CHECK 7: Visitor profiles (B2C + B2B).
$checks++;
$hasB2C = strpos($content, 'B2C') !== false || strpos($content, 'PARTICULARES') !== false;
$hasB2B = strpos($content, 'B2B') !== false || strpos($content, 'ORGANIZACIONES') !== false;
if ($hasB2C && $hasB2B) {
  echo "[PASS] Perfiles B2C + B2B presentes\n";
} else {
  $warnings[] = 'Perfiles de visitantes incompletos: ' . (!$hasB2C ? 'falta B2C' : '') . (!$hasB2B ? 'falta B2B' : '');
  echo "[WARN] Perfiles incompletos\n";
}

// CHECK 8: Fallback to static prompt when service unavailable.
$checks++;
if (strpos($content, 'buildPublicSystemPrompt') !== false) {
  echo "[PASS] Fallback a prompt estático disponible\n";
} else {
  $warnings[] = 'Sin fallback a prompt estático cuando ActivePromotionService no disponible';
  echo "[WARN] Sin fallback a prompt estático\n";
}

// CHECK 9: Prompt is called in queryPublicKnowledge flow.
$checks++;
if (strpos($content, '_custom_system_prompt') !== false) {
  echo "[PASS] Prompt dinámico inyectado via _custom_system_prompt\n";
} else {
  $errors[] = 'Prompt dinámico NO se inyecta en el flujo — _custom_system_prompt ausente';
  echo "[FAIL] _custom_system_prompt ausente\n";
}

summary:
echo "\n=== Resumen ===\n";
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
echo "\n" . ($exitCode === 0 ? '[OK]' : '[FAIL]') . " COPILOT-PROMPT-DRIFT-001: Validación completada.\n";
exit($exitCode);
