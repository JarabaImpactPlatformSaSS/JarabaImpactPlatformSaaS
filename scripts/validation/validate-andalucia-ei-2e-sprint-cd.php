<?php

/**
 * @file
 * Validator: Andalucía +ei 2ª Edición Sprint C+D integrity.
 *
 * Checks: ProspeccionPipelineService, CalculadoraPuntoEquilibrioService,
 * controllers, FormadorDashboard #theme, PortfolioEntregablesService field
 * names, CaptacionLeadsAction target, required templates.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-2e-sprint-cd.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';
$servicesFile = $moduleRoot . '/jaraba_andalucia_ei.services.yml';
$servicesContent = file_exists($servicesFile) ? file_get_contents($servicesFile) : '';

// CHECK 1: ProspeccionPipelineService registered in services.yml.
if (strpos($servicesContent, 'jaraba_andalucia_ei.prospeccion_pipeline') !== FALSE) {
  $passes[] = "CHECK 1 PASS: ProspeccionPipelineService registered in services.yml";
} else {
  $errors[] = "CHECK 1 FAIL: ProspeccionPipelineService not registered — expected 'jaraba_andalucia_ei.prospeccion_pipeline' in services.yml";
}

// CHECK 2: CalculadoraPuntoEquilibrioService registered in services.yml.
if (strpos($servicesContent, 'jaraba_andalucia_ei.calculadora_punto_equilibrio') !== FALSE) {
  $passes[] = "CHECK 2 PASS: CalculadoraPuntoEquilibrioService registered in services.yml";
} else {
  $errors[] = "CHECK 2 FAIL: CalculadoraPuntoEquilibrioService not registered — expected 'jaraba_andalucia_ei.calculadora_punto_equilibrio' in services.yml";
}

// CHECK 3: ProspeccionPipelineController exists.
$prospeccionController = $moduleRoot . '/src/Controller/ProspeccionPipelineController.php';
if (file_exists($prospeccionController)) {
  $passes[] = "CHECK 3 PASS: ProspeccionPipelineController exists";
} else {
  $errors[] = "CHECK 3 FAIL: ProspeccionPipelineController not found — expected at src/Controller/ProspeccionPipelineController.php";
}

// CHECK 4: PruebaGratuitaController exists.
$pruebaController = $moduleRoot . '/src/Controller/PruebaGratuitaController.php';
if (file_exists($pruebaController)) {
  $passes[] = "CHECK 4 PASS: PruebaGratuitaController exists";
} else {
  $errors[] = "CHECK 4 FAIL: PruebaGratuitaController not found — expected at src/Controller/PruebaGratuitaController.php";
}

// CHECK 5: FormadorDashboardController returns #theme (NOT empty markup).
$formadorController = $moduleRoot . '/src/Controller/FormadorDashboardController.php';
if (file_exists($formadorController)) {
  $formadorContent = file_get_contents($formadorController);
  if (strpos($formadorContent, "'#theme' => 'formador_dashboard'") !== FALSE
    || strpos($formadorContent, '"#theme" => "formador_dashboard"') !== FALSE) {
    $passes[] = "CHECK 5 PASS: FormadorDashboardController returns #theme => formador_dashboard";
  } else {
    $errors[] = "CHECK 5 FAIL: FormadorDashboardController does not return '#theme' => 'formador_dashboard' — returns empty markup?";
  }
} else {
  $errors[] = "CHECK 5 FAIL: FormadorDashboardController not found — cannot verify #theme";
}

// CHECK 6: PortfolioEntregablesService uses 'numero' field (NOT 'numero_entregable').
$portfolioService = $moduleRoot . '/src/Service/PortfolioEntregablesService.php';
if (file_exists($portfolioService)) {
  $portfolioContent = file_get_contents($portfolioService);
  if (strpos($portfolioContent, 'numero_entregable') !== FALSE) {
    $errors[] = "CHECK 6 FAIL: PortfolioEntregablesService uses 'numero_entregable' — should be 'numero'";
  } elseif (strpos($portfolioContent, 'numero') !== FALSE) {
    $passes[] = "CHECK 6 PASS: PortfolioEntregablesService uses correct field name 'numero'";
  } else {
    $errors[] = "CHECK 6 FAIL: PortfolioEntregablesService does not reference 'numero' field at all";
  }
} else {
  $errors[] = "CHECK 6 FAIL: PortfolioEntregablesService not found — cannot verify field names";
}

// CHECK 7: CaptacionLeadsAction points to prospeccion_pipeline (NOT leads_guia).
$captacionFiles = glob($moduleRoot . '/src/{Service,DailyActions,DailyAction,Action}/*Captacion*', GLOB_BRACE);
$captacionFile = NULL;
if (!empty($captacionFiles)) {
  $captacionFile = $captacionFiles[0];
} else {
  // Search more broadly.
  $allFiles = glob($moduleRoot . '/src/{Service,DailyActions,DailyAction,Action}/*Lead*', GLOB_BRACE);
  if (!empty($allFiles)) {
    $captacionFile = $allFiles[0];
  }
}
if ($captacionFile !== NULL && file_exists($captacionFile)) {
  $captacionContent = file_get_contents($captacionFile);
  if (strpos($captacionContent, 'leads_guia') !== FALSE) {
    $errors[] = "CHECK 7 FAIL: CaptacionLeadsAction points to 'leads_guia' — should reference 'prospeccion_pipeline'";
  } elseif (strpos($captacionContent, 'prospeccion_pipeline') !== FALSE) {
    $passes[] = "CHECK 7 PASS: CaptacionLeadsAction correctly points to prospeccion_pipeline";
  } else {
    $errors[] = "CHECK 7 FAIL: CaptacionLeadsAction does not reference 'prospeccion_pipeline'";
  }
} else {
  $errors[] = "CHECK 7 FAIL: CaptacionLeadsAction class not found — searched Service/DailyAction/Action directories";
}

// CHECK 8: 3 required templates exist.
$requiredTemplates = [
  'prospeccion-pipeline.html.twig',
  'prueba-gratuita-landing.html.twig',
  'portfolio-publico.html.twig',
];
$missingTemplates = [];
foreach ($requiredTemplates as $tpl) {
  $found = FALSE;
  // Check templates/ and templates/partials/.
  foreach (['templates/', 'templates/partials/'] as $dir) {
    if (file_exists($moduleRoot . '/' . $dir . $tpl)) {
      $found = TRUE;
      break;
    }
  }
  if (!$found) {
    $missingTemplates[] = $tpl;
  }
}
if (empty($missingTemplates)) {
  $passes[] = "CHECK 8 PASS: 3/3 Sprint C+D templates exist";
} else {
  $errors[] = "CHECK 8 FAIL: Missing templates: " . implode(', ', $missingTemplates);
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANDALUCÍA +EI 2ª EDICIÓN — SPRINT C+D ===\n\n";
foreach ($passes as $msg) echo "  ✅ $msg\n";
foreach ($errors as $msg) echo "  ❌ $msg\n";
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(empty($errors) ? 0 : 1);
