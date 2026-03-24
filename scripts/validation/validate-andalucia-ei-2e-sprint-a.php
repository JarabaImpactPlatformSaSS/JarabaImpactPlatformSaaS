<?php

/**
 * @file
 * Validator: Andalucía +ei 2ª Edición Sprint A integrity.
 *
 * Checks: new fields in ProgramaParticipanteEi, AsistenciaDetalladaEi entity,
 * CopilotPhaseConfigService, AsistenciaComplianceService, logos cofinanciación.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-2e-sprint-a.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';

// CHECK 1: 12 new fields in ProgramaParticipanteEi.
$entityFile = $moduleRoot . '/src/Entity/ProgramaParticipanteEi.php';
$entityContent = file_exists($entityFile) ? file_get_contents($entityFile) : '';
$newFields = ['ruta_programa', 'nivel_digital', 'pack_preseleccionado', 'pack_confirmado',
  'objetivos_smart', 'perfil_riasec', 'compromiso_firmado', 'compromiso_fecha',
  'estado_programa_2e', 'meses_ss_acumulados', 'negocio_piloto_id', 'pack_servicio_id'];
$missingFields = [];
foreach ($newFields as $f) {
  if (strpos($entityContent, "'$f'") === FALSE) {
    $missingFields[] = $f;
  }
}
if (empty($missingFields)) {
  $passes[] = "CHECK 1 PASS: 12/12 new 2E fields in ProgramaParticipanteEi";
} else {
  $errors[] = "CHECK 1 FAIL: Missing fields: " . implode(', ', $missingFields);
}

// CHECK 2: AsistenciaDetalladaEi entity exists.
$asistFile = $moduleRoot . '/src/Entity/AsistenciaDetalladaEi.php';
if (file_exists($asistFile) && strpos(file_get_contents($asistFile), 'asistencia_detallada_ei') !== FALSE) {
  $passes[] = "CHECK 2 PASS: AsistenciaDetalladaEi entity class exists";
} else {
  $errors[] = "CHECK 2 FAIL: AsistenciaDetalladaEi entity not found";
}

// CHECK 3: AsistenciaComplianceService exists.
$compFile = $moduleRoot . '/src/Service/AsistenciaComplianceService.php';
if (file_exists($compFile)) {
  $passes[] = "CHECK 3 PASS: AsistenciaComplianceService exists";
} else {
  $errors[] = "CHECK 3 FAIL: AsistenciaComplianceService not found";
}

// CHECK 4: CopilotPhaseConfigService exists.
$copilotFile = $moduleRoot . '/src/Service/CopilotPhaseConfigService.php';
if (file_exists($copilotFile)) {
  $passes[] = "CHECK 4 PASS: CopilotPhaseConfigService exists";
} else {
  $errors[] = "CHECK 4 FAIL: CopilotPhaseConfigService not found";
}

// CHECK 5: Copilot config YAML in config/install.
$configFile = $moduleRoot . '/config/install/jaraba_andalucia_ei.copilot_phase_prompts.yml';
if (file_exists($configFile)) {
  $passes[] = "CHECK 5 PASS: Copilot phase prompts config exists in config/install";
} else {
  $errors[] = "CHECK 5 FAIL: Copilot phase prompts config not found";
}

// CHECK 6: Services registered in services.yml.
$servicesContent = file_get_contents($moduleRoot . '/jaraba_andalucia_ei.services.yml');
$servicesNeeded = ['asistencia_compliance', 'copilot_phase_config'];
$servicesOk = TRUE;
foreach ($servicesNeeded as $s) {
  if (strpos($servicesContent, $s) === FALSE) {
    $errors[] = "CHECK 6 FAIL: Service '$s' not in services.yml";
    $servicesOk = FALSE;
  }
}
if ($servicesOk) {
  $passes[] = "CHECK 6 PASS: 2/2 Sprint A services registered";
}

// CHECK 7: Logos cofinanciación parcial exists.
$logosFile = $moduleRoot . '/templates/partials/_logos-cofinanciacion.html.twig';
if (file_exists($logosFile) && strpos(file_get_contents($logosFile), 'FSE+') !== FALSE) {
  $passes[] = "CHECK 7 PASS: Logos cofinanciación parcial exists with FSE+ text";
} else {
  $errors[] = "CHECK 7 FAIL: Logos cofinanciación parcial missing or incomplete";
}

// CHECK 8: hook_update for new fields and entity.
$installContent = file_get_contents($moduleRoot . '/jaraba_andalucia_ei.install');
if (strpos($installContent, 'update_10032') !== FALSE && strpos($installContent, 'update_10033') !== FALSE) {
  $passes[] = "CHECK 8 PASS: hook_update_10032 + 10033 exist for 2E fields + entity";
} else {
  $errors[] = "CHECK 8 FAIL: Missing hook_update for 2E components";
}

// CHECK 9: Asistencia routes in routing.yml.
$routingContent = file_get_contents($moduleRoot . '/jaraba_andalucia_ei.routing.yml');
if (strpos($routingContent, 'asistencia_detallada_ei.collection') !== FALSE) {
  $passes[] = "CHECK 9 PASS: Asistencia entity routes defined";
} else {
  $errors[] = "CHECK 9 FAIL: Asistencia entity routes missing";
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANDALUCÍA +EI 2ª EDICIÓN — SPRINT A ===\n\n";
foreach ($passes as $msg) echo "  ✅ $msg\n";
foreach ($errors as $msg) echo "  ❌ $msg\n";
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(empty($errors) ? 0 : 1);
