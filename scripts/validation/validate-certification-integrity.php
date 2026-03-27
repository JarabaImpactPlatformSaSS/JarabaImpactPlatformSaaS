<?php

/**
 * @file
 * Validador de integridad del sistema de certificación Método Jaraba.
 *
 * 8 checks: entity, access control, hook_update, tenant_id,
 * wizard steps, daily actions, CopilotBridge, GroundingProvider.
 *
 * Uso: php scripts/validation/validate-certification-integrity.php
 */

$basePath = dirname(__DIR__, 2);
$pass = 0;
$fail = 0;
$checks = 0;

function check(string $label, bool $result, string $detail = ''): void {
  global $pass, $fail, $checks;
  $checks++;
  if ($result) {
    $pass++;
    echo "  \033[32mPASS\033[0m $label\n";
  }
  else {
    $fail++;
    echo "  \033[31mFAIL\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
}

echo "\n\033[1m[CERTIFICATION-INTEGRITY-001]\033[0m Método Jaraba certification system integrity\n\n";

// 1. MethodPortfolioItem entity exists.
$entityFile = "$basePath/web/modules/custom/jaraba_training/src/Entity/MethodPortfolioItem.php";
check('MethodPortfolioItem entity exists', file_exists($entityFile));

// 2. AccessControlHandler exists.
$accessFile = "$basePath/web/modules/custom/jaraba_training/src/Access/MethodPortfolioItemAccessControlHandler.php";
check('AccessControlHandler for MethodPortfolioItem', file_exists($accessFile));

// 3. Entity has tenant_id field.
if (file_exists($entityFile)) {
  $content = file_get_contents($entityFile);
  check('MethodPortfolioItem has tenant_id field', str_contains($content, "'tenant_id'"));
}

// 4. hook_update exists for new fields.
$installFile = "$basePath/web/modules/custom/jaraba_training/jaraba_training.install";
if (file_exists($installFile)) {
  $content = file_get_contents($installFile);
  check('hook_update_10002 (Método fields)', str_contains($content, 'jaraba_training_update_10002'));
  check('hook_update_10003 (MethodPortfolioItem)', str_contains($content, 'jaraba_training_update_10003'));
}

// 5. Setup Wizard steps registered.
$servicesFile = "$basePath/web/modules/custom/jaraba_training/jaraba_training.services.yml";
if (file_exists($servicesFile)) {
  $content = file_get_contents($servicesFile);
  check('3 Setup Wizard steps registered', (
    str_contains($content, 'wizard.configurar_rubrica') &&
    str_contains($content, 'wizard.asignar_evaluador') &&
    str_contains($content, 'wizard.definir_precios')
  ));
  check('3 Daily Actions registered', (
    str_contains($content, 'daily.evaluaciones_pendientes') &&
    str_contains($content, 'daily.renovaciones_proximas') &&
    str_contains($content, 'daily.nuevas_solicitudes')
  ));
}

// 6. CopilotBridge + GroundingProvider.
if (file_exists($servicesFile)) {
  $content = file_get_contents($servicesFile);
  check('CopilotBridge registered (AI-COVERAGE-001)',
    str_contains($content, 'jaraba_copilot_v2.copilot_bridge'));
  check('GroundingProvider registered (AI-COVERAGE-001)',
    str_contains($content, 'jaraba_copilot_v2.grounding_provider'));
}

echo "\n============================================================\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed (of $checks)\n";
echo "============================================================\n";

exit($fail > 0 ? 1 : 0);
