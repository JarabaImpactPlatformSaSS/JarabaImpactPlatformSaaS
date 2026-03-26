<?php

/**
 * @file
 * Validator: Andalucía +ei catálogo de servicios integrity.
 *
 * 10 checks for catalog-to-implementation integrity:
 * wizard files, daily action files, services.yml entries,
 * hook_theme vars, controller data, template includes,
 * cron calls, BonoProgramaExpiryService, copilot bridge.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-catalogo-servicios.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';
$servicesFile = $moduleRoot . '/jaraba_andalucia_ei.services.yml';
$servicesContent = file_exists($servicesFile) ? file_get_contents($servicesFile) : '';
$moduleFile = $moduleRoot . '/jaraba_andalucia_ei.module';
$moduleContent = file_exists($moduleFile) ? file_get_contents($moduleFile) : '';

// CHECK 1: Participante wizard files exist (>=6).
$wizardDir = $moduleRoot . '/src/SetupWizard';
$wizardFiles = glob($wizardDir . '/Participante*Step.php');
$wizardCount = is_array($wizardFiles) ? count($wizardFiles) : 0;
if ($wizardCount >= 6) {
  $passes[] = "CHECK 1 PASS: Participante wizard files found ($wizardCount >= 6)";
} else {
  $errors[] = "CHECK 1 FAIL: Only $wizardCount participante wizard files found in SetupWizard/ (expected >= 6)";
}

// CHECK 2: Participante daily action files exist (>=5).
$dailyDir = $moduleRoot . '/src/DailyActions';
$dailyFiles = glob($dailyDir . '/Participante*Action.php');
$dailyCount = is_array($dailyFiles) ? count($dailyFiles) : 0;
if ($dailyCount >= 5) {
  $passes[] = "CHECK 2 PASS: Participante daily action files found ($dailyCount >= 5)";
} else {
  $errors[] = "CHECK 2 FAIL: Only $dailyCount participante daily action files found in DailyActions/ (expected >= 5)";
}

// CHECK 3: Wizard services registered in services.yml with correct tags.
$wizardServiceCount = 0;
if (preg_match_all('/Participante\w+Step/', $servicesContent, $matches)) {
  $wizardServiceCount = count($matches[0]);
}
$wizardTagCount = substr_count($servicesContent, 'ecosistema_jaraba_core.setup_wizard_step');
if ($wizardServiceCount >= 6) {
  $passes[] = "CHECK 3 PASS: $wizardServiceCount participante wizard services registered in services.yml";
} else {
  $errors[] = "CHECK 3 FAIL: Only $wizardServiceCount participante wizard services in services.yml (expected >= 6). Total wizard tags: $wizardTagCount";
}

// CHECK 4: Daily action services registered in services.yml with correct tags.
$dailyServiceCount = 0;
if (preg_match_all('/Participante\w+Action/', $servicesContent, $matches)) {
  $dailyServiceCount = count($matches[0]);
}
if ($dailyServiceCount >= 5) {
  $passes[] = "CHECK 4 PASS: $dailyServiceCount participante daily action services registered in services.yml";
} else {
  $errors[] = "CHECK 4 FAIL: Only $dailyServiceCount participante daily action services in services.yml (expected >= 5)";
}

// CHECK 5: hook_theme declares participante dashboard variables.
if (strpos($moduleContent, 'participante_portal') !== false
  && strpos($moduleContent, "'setup_wizard' => NULL") !== false) {
  $passes[] = "CHECK 5 PASS: hook_theme references participante dashboard variables";
} else {
  $errors[] = "CHECK 5 FAIL: hook_theme does not reference participante dashboard — verify PIPELINE-E2E-001 L3";
}

// CHECK 6: Controller passes setup_wizard or daily_actions data.
$controllerFiles = glob($moduleRoot . '/src/Controller/*Dashboard*Controller.php');
$controllerPassesData = false;
if (is_array($controllerFiles)) {
  foreach ($controllerFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'setup_wizard') !== false || strpos($content, 'daily_actions') !== false) {
      $controllerPassesData = true;
      break;
    }
  }
}
// Also check the general AndaluciaEiController.
$mainController = $moduleRoot . '/src/Controller/AndaluciaEiController.php';
if (file_exists($mainController)) {
  $mainContent = file_get_contents($mainController);
  if (strpos($mainContent, 'setup_wizard') !== false || strpos($mainContent, 'daily_actions') !== false) {
    $controllerPassesData = true;
  }
}
if ($controllerPassesData) {
  $passes[] = "CHECK 6 PASS: Controller passes setup_wizard/daily_actions to render array";
} else {
  $errors[] = "CHECK 6 FAIL: No controller passes setup_wizard/daily_actions data — verify PIPELINE-E2E-001 L2";
}

// CHECK 7: Template includes _setup-wizard or _daily-actions partial.
$templateFiles = glob($moduleRoot . '/templates/*.html.twig');
$templateIncludesPartials = false;
if (is_array($templateFiles)) {
  foreach ($templateFiles as $tpl) {
    $content = file_get_contents($tpl);
    if (strpos($content, '_setup-wizard') !== false || strpos($content, '_daily-actions') !== false) {
      $templateIncludesPartials = true;
      break;
    }
  }
}
if ($templateIncludesPartials) {
  $passes[] = "CHECK 7 PASS: Template includes _setup-wizard/_daily-actions partials";
} else {
  $errors[] = "CHECK 7 FAIL: No template includes _setup-wizard/_daily-actions — verify PIPELINE-E2E-001 L4";
}

// CHECK 8: Cron hook calls BonoProgramaExpiryService or bono_expiry.
$cronCalls = false;
if (strpos($moduleContent, 'bono_programa_expiry') !== false
  || strpos($moduleContent, 'BonoProgramaExpiry') !== false
  || strpos($moduleContent, 'bono_expiry') !== false) {
  $cronCalls = true;
}
if ($cronCalls) {
  $passes[] = "CHECK 8 PASS: Cron references BonoProgramaExpiryService";
} else {
  $errors[] = "CHECK 8 FAIL: .module does not reference BonoProgramaExpiryService in cron — add hook_cron() call";
}

// CHECK 9: BonoProgramaExpiryService exists and has required constants.
$expiryService = $moduleRoot . '/src/Service/BonoProgramaExpiryService.php';
if (file_exists($expiryService)) {
  $expiryContent = file_get_contents($expiryService);
  $hasMeses = strpos($expiryContent, 'MESES_PROGRAMA') !== false;
  $hasAvisos = strpos($expiryContent, 'AVISOS_DIAS') !== false;
  if ($hasMeses && $hasAvisos) {
    $passes[] = "CHECK 9 PASS: BonoProgramaExpiryService exists with MESES_PROGRAMA + AVISOS_DIAS";
  } else {
    $missing = [];
    if (!$hasMeses) {
      $missing[] = 'MESES_PROGRAMA';
    }
    if (!$hasAvisos) {
      $missing[] = 'AVISOS_DIAS';
    }
    $errors[] = "CHECK 9 FAIL: BonoProgramaExpiryService missing constants: " . implode(', ', $missing);
  }
} else {
  $errors[] = "CHECK 9 FAIL: BonoProgramaExpiryService not found at src/Service/BonoProgramaExpiryService.php";
}

// CHECK 10: CopilotBridge has orientador + formador support.
$bridgeFiles = glob($moduleRoot . '/src/{Service,Bridge}/*CopilotBridge*', GLOB_BRACE);
$bridgeHasOrientador = false;
$bridgeHasFormador = false;
if (is_array($bridgeFiles) && count($bridgeFiles) > 0) {
  foreach ($bridgeFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'orientador') !== false) {
      $bridgeHasOrientador = true;
    }
    if (strpos($content, 'formador') !== false) {
      $bridgeHasFormador = true;
    }
  }
}
// Also check the grounding provider and other service files.
$serviceFiles = glob($moduleRoot . '/src/Service/*Copilot*');
if (is_array($serviceFiles)) {
  foreach ($serviceFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'orientador') !== false) {
      $bridgeHasOrientador = true;
    }
    if (strpos($content, 'formador') !== false) {
      $bridgeHasFormador = true;
    }
  }
}

if ($bridgeHasOrientador && $bridgeHasFormador) {
  $passes[] = "CHECK 10 PASS: CopilotBridge supports orientador + formador roles";
} else {
  $missing = [];
  if (!$bridgeHasOrientador) {
    $missing[] = 'orientador';
  }
  if (!$bridgeHasFormador) {
    $missing[] = 'formador';
  }
  $errors[] = "CHECK 10 FAIL: CopilotBridge missing role support: " . implode(', ', $missing);
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANDALUCIA +EI — CATALOGO SERVICIOS INTEGRITY ===\n\n";
foreach ($passes as $msg) {
  echo "  [PASS] $msg\n";
}
foreach ($errors as $msg) {
  echo "  [FAIL] $msg\n";
}
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(count($errors) === 0 ? 0 : 1);
