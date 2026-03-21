<?php

/**
 * @file
 * CRM-FUNNEL-ATTRIBUTION-001: Verifica coherencia pipeline CRM + funnel.
 *
 * Verifica que la infraestructura de attribution copilot→CRM está completa:
 * 1. CopilotLeadCaptureService existe y tiene dependencias CRM opcionales
 * 2. CopilotFunnelTrackingService existe con tabla copilot_funnel_event
 * 3. PublicCopilotController integra lead capture + funnel tracking
 * 4. Intent patterns cubren todas las verticales comerciales
 * 5. CRM services (jaraba_crm) referenciados como @? (optional)
 *
 * Uso: php scripts/validation/validate-crm-funnel-attribution.php
 */

echo "=== CRM-FUNNEL-ATTRIBUTION-001: Coherencia pipeline CRM + funnel ===\n\n";

$modulesPath = __DIR__ . '/../../web/modules/custom';
$errors = [];
$warnings = [];
$checks = 0;

// CHECK 1: CopilotLeadCaptureService exists.
$checks++;
$leadCapturePath = $modulesPath . '/jaraba_copilot_v2/src/Service/CopilotLeadCaptureService.php';
if (!file_exists($leadCapturePath)) {
  $errors[] = 'CopilotLeadCaptureService no existe';
  echo "[FAIL] CopilotLeadCaptureService\n";
} else {
  $content = file_get_contents($leadCapturePath);

  // CHECK: Has detectPurchaseIntent method.
  $checks++;
  if (strpos($content, 'function detectPurchaseIntent') === false) {
    $errors[] = 'CopilotLeadCaptureService sin detectPurchaseIntent()';
    echo "[FAIL] detectPurchaseIntent() ausente\n";
  } else {
    echo "[PASS] CopilotLeadCaptureService + detectPurchaseIntent()\n";
  }

  // CHECK: Has createCrmLead method.
  $checks++;
  if (strpos($content, 'function createCrmLead') === false) {
    $errors[] = 'CopilotLeadCaptureService sin createCrmLead()';
    echo "[FAIL] createCrmLead() ausente\n";
  } else {
    echo "[PASS] createCrmLead() presente\n";
  }

  // CHECK: Intent patterns cover commercial verticals.
  $checks++;
  $requiredVerticals = ['andalucia_ei', 'formacion', 'empleabilidad', 'emprendimiento',
                        'comercioconecta', 'agroconecta', 'jarabalex', 'serviciosconecta'];
  $missing = [];
  foreach ($requiredVerticals as $v) {
    if (strpos($content, "'{$v}'") === false) {
      $missing[] = $v;
    }
  }
  if (count($missing) === 0) {
    echo "[PASS] Intent patterns cubren 8/8 verticales comerciales\n";
  } else {
    $errors[] = 'Intent patterns faltan verticales: ' . implode(', ', $missing);
    echo "[FAIL] Faltan verticales: " . implode(', ', $missing) . "\n";
  }
}

// CHECK 2: CopilotFunnelTrackingService exists.
$checks++;
$funnelPath = $modulesPath . '/jaraba_copilot_v2/src/Service/CopilotFunnelTrackingService.php';
if (!file_exists($funnelPath)) {
  $errors[] = 'CopilotFunnelTrackingService no existe';
  echo "[FAIL] CopilotFunnelTrackingService\n";
} else {
  $funnelContent = file_get_contents($funnelPath);

  // CHECK: Has logEvent method.
  $checks++;
  if (strpos($funnelContent, 'function logEvent') === false) {
    $errors[] = 'CopilotFunnelTrackingService sin logEvent()';
    echo "[FAIL] logEvent() ausente\n";
  } else {
    echo "[PASS] CopilotFunnelTrackingService + logEvent()\n";
  }

  // CHECK: References copilot_funnel_event table.
  $checks++;
  if (strpos($funnelContent, 'copilot_funnel_event') === false) {
    $errors[] = 'CopilotFunnelTrackingService no referencia tabla copilot_funnel_event';
    echo "[FAIL] Tabla copilot_funnel_event no referenciada\n";
  } else {
    echo "[PASS] Tabla copilot_funnel_event referenciada\n";
  }
}

// CHECK 3: hook_update creates table.
$checks++;
$installPath = $modulesPath . '/jaraba_copilot_v2/jaraba_copilot_v2.install';
if (file_exists($installPath)) {
  $installContent = file_get_contents($installPath);
  if (strpos($installContent, 'copilot_funnel_event') !== false) {
    echo "[PASS] hook_update crea tabla copilot_funnel_event\n";
  } else {
    $errors[] = 'hook_update no crea tabla copilot_funnel_event';
    echo "[FAIL] hook_update sin copilot_funnel_event\n";
  }
}

// CHECK 4: PublicCopilotController integrates both services.
$checks++;
$controllerPath = $modulesPath . '/jaraba_copilot_v2/src/Controller/PublicCopilotController.php';
if (file_exists($controllerPath)) {
  $ctrlContent = file_get_contents($controllerPath);
  $hasLeadCapture = strpos($ctrlContent, 'leadCaptureService') !== false;
  $hasFunnelTracking = strpos($ctrlContent, 'funnelTracking') !== false;
  $hasIntentDetection = strpos($ctrlContent, 'detectPurchaseIntent') !== false;
  $hasEventLogging = strpos($ctrlContent, 'copilot_message_received') !== false;

  $controllerChecks = [
    'leadCaptureService' => $hasLeadCapture,
    'funnelTracking' => $hasFunnelTracking,
    'detectPurchaseIntent' => $hasIntentDetection,
    'event logging' => $hasEventLogging,
  ];

  foreach ($controllerChecks as $label => $ok) {
    $checks++;
    if ($ok) {
      echo "[PASS] PublicCopilotController integra: {$label}\n";
    } else {
      $errors[] = "PublicCopilotController NO integra: {$label}";
      echo "[FAIL] PublicCopilotController NO integra: {$label}\n";
    }
  }
}

// CHECK 5: CRM services referenced as optional.
$checks++;
$servicesPath = $modulesPath . '/jaraba_copilot_v2/jaraba_copilot_v2.services.yml';
if (file_exists($servicesPath)) {
  $svcContent = file_get_contents($servicesPath);
  $hasCrmOptional = strpos($svcContent, '@?jaraba_crm.contact') !== false
    || strpos($svcContent, '@?jaraba_crm') !== false;
  if ($hasCrmOptional) {
    echo "[PASS] CRM services referenciados como @? (OPTIONAL-CROSSMODULE-001)\n";
  } else {
    $warnings[] = 'CRM services no referenciados como opcionales';
    echo "[WARN] CRM services no referenciados como @?\n";
  }
}

echo "\n=== Resumen ===\n";
echo "Checks: {$checks}\n";
echo "Errores: " . count($errors) . "\n";
echo "Advertencias: " . count($warnings) . "\n";

if (!empty($errors)) {
  echo "\nErrores:\n";
  foreach ($errors as $e) { echo "  - {$e}\n"; }
}

$exitCode = count($errors) > 0 ? 1 : 0;
echo "\n" . ($exitCode === 0 ? '[OK]' : '[FAIL]') . " CRM-FUNNEL-ATTRIBUTION-001: Validación completada.\n";
exit($exitCode);
