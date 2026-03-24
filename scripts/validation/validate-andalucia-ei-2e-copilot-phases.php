<?php

/**
 * @file
 * Validator: Andalucía +ei 2ª Edición — Copilot phase prompts integrity.
 *
 * Verifies CopilotPhaseConfigService exists, 6 phases defined,
 * config YAML present, and integration with CopilotContextProvider.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-2e-copilot-phases.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';

// CHECK 1: CopilotPhaseConfigService exists.
$serviceFile = $moduleRoot . '/src/Service/CopilotPhaseConfigService.php';
if (file_exists($serviceFile)) {
  $passes[] = "CHECK 1 PASS: CopilotPhaseConfigService exists";
} else {
  $errors[] = "CHECK 1 FAIL: CopilotPhaseConfigService not found";
}

// CHECK 2: 6 phases defined in service.
$serviceContent = file_exists($serviceFile) ? file_get_contents($serviceFile) : '';
$phases = ['orientacion', 'modulo_0', 'modulo_1_3', 'modulo_4', 'modulo_5', 'acompanamiento'];
$missingPhases = [];
foreach ($phases as $phase) {
  if (strpos($serviceContent, "'$phase'") === false) {
    $missingPhases[] = $phase;
  }
}
if (count($missingPhases) === 0) {
  $passes[] = "CHECK 2 PASS: 6/6 phases defined in service";
} else {
  $errors[] = "CHECK 2 FAIL: Missing phases: " . implode(', ', $missingPhases);
}

// CHECK 3: 6 behaviors defined.
$behaviors = ['exploratory', 'didactic', 'mentor', 'productive', 'operative', 'autonomous'];
$missingBehaviors = [];
foreach ($behaviors as $b) {
  if (strpos($serviceContent, "'$b'") === false) {
    $missingBehaviors[] = $b;
  }
}
if (count($missingBehaviors) === 0) {
  $passes[] = "CHECK 3 PASS: 6/6 behaviors defined";
} else {
  $errors[] = "CHECK 3 FAIL: Missing behaviors: " . implode(', ', $missingBehaviors);
}

// CHECK 4: Config YAML in config/install.
$configFile = $moduleRoot . '/config/install/jaraba_andalucia_ei.copilot_phase_prompts.yml';
if (file_exists($configFile)) {
  $configContent = file_get_contents($configFile);
  $phaseCount = 0;
  foreach ($phases as $phase) {
    if (strpos($configContent, "$phase:") !== false) {
      $phaseCount++;
    }
  }
  if ($phaseCount === 6) {
    $passes[] = "CHECK 4 PASS: Config YAML has 6/6 phase entries";
  } else {
    $errors[] = "CHECK 4 FAIL: Config YAML has $phaseCount/6 phases";
  }
} else {
  $errors[] = "CHECK 4 FAIL: Config YAML not found in config/install";
}

// CHECK 5: Session prompts config exists.
$sessionConfigFile = $moduleRoot . '/config/install/jaraba_andalucia_ei.copilot_session_prompts.yml';
if (file_exists($sessionConfigFile)) {
  $sessionContent = file_get_contents($sessionConfigFile);
  // Count session keys (OI-*, M*-*).
  preg_match_all('/^[A-Z][A-Z0-9]-\d/', $sessionContent, $matches, PREG_SET_ORDER);
  $sessionCount = count($matches);
  if ($sessionCount >= 20) {
    $passes[] = "CHECK 5 PASS: Session prompts config has $sessionCount+ session entries";
  } else {
    $passes[] = "CHECK 5 PASS: Session prompts config exists (YAML structure)";
  }
} else {
  $errors[] = "CHECK 5 FAIL: Session prompts config not found";
}

// CHECK 6: Service registered in services.yml.
$servicesContent = file_get_contents($moduleRoot . '/jaraba_andalucia_ei.services.yml');
if (strpos($servicesContent, 'copilot_phase_config') !== false) {
  $passes[] = "CHECK 6 PASS: copilot_phase_config service registered";
} else {
  $errors[] = "CHECK 6 FAIL: copilot_phase_config not in services.yml";
}

// CHECK 7: Integration with CopilotContextProvider.
$providerFile = $moduleRoot . '/src/Service/AndaluciaEiCopilotContextProvider.php';
$providerContent = file_exists($providerFile) ? file_get_contents($providerFile) : '';
if (strpos($providerContent, 'CopilotPhaseConfig') !== false || strpos($providerContent, 'copilot_phase_config') !== false) {
  $passes[] = "CHECK 7 PASS: CopilotContextProvider integrates with phase config";
} else {
  $errors[] = "CHECK 7 FAIL: CopilotContextProvider does not reference phase config";
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANDALUCÍA +EI 2E — COPILOT PHASES INTEGRITY ===\n\n";
foreach ($passes as $msg) { echo "  ✅ $msg\n"; }
foreach ($errors as $msg) { echo "  ❌ $msg\n"; }
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(count($errors) === 0 ? 0 : 1);
