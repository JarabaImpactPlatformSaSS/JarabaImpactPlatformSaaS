<?php

/**
 * @file
 * SAFEGUARD-PB-ONBOARDING-001: Validates Page Builder onboarding integrity.
 *
 * Checks:
 * 1. SetupWizard step classes exist and implement interface
 * 2. DailyAction classes exist and implement interface
 * 3. services.yml has correct tags for each step/action
 * 4. hook_theme() declares setup_wizard and daily_actions variables
 * 5. Template includes _setup-wizard.html.twig and _daily-actions.html.twig
 * 6. Controller consumes SetupWizardRegistry and DailyActionsRegistry
 *
 * Usage: php scripts/validation/validate-page-builder-onboarding.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$module_path = $root . '/web/modules/custom/jaraba_page_builder';
$errors = [];
$warnings = [];
$passes = [];

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  SAFEGUARD-PB-ONBOARDING-001                           ║\n";
echo "║  Page Builder Onboarding Integrity Validator            ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// ── CHECK 1: SetupWizard step classes exist ──────────────────────────
$wizard_dir = $module_path . '/src/SetupWizard';
$required_steps = [
  'CrearPrimeraPaginaStep.php',
  'ElegirPlantillaStep.php',
  'PersonalizarContenidoStep.php',
  'PublicarPaginaStep.php',
];

if (!is_dir($wizard_dir)) {
  $errors[] = 'CHECK 1 FAIL: SetupWizard directory does not exist: ' . $wizard_dir;
} else {
  $found = 0;
  foreach ($required_steps as $step_file) {
    $path = $wizard_dir . '/' . $step_file;
    if (file_exists($path)) {
      $content = file_get_contents($path);
      if (str_contains($content, 'SetupWizardStepInterface')) {
        $found++;
      } else {
        $errors[] = "CHECK 1 FAIL: $step_file does not implement SetupWizardStepInterface";
      }
    } else {
      $errors[] = "CHECK 1 FAIL: Missing wizard step: $step_file";
    }
  }
  if ($found === count($required_steps)) {
    $passes[] = "CHECK 1 PASS: All " . count($required_steps) . " wizard step classes exist and implement interface";
  }
}

// ── CHECK 2: DailyAction classes exist ───────────────────────────────
$actions_dir = $module_path . '/src/DailyActions';
$required_actions = [
  'NuevaPaginaAction.php',
  'EditarPaginaPrincipalAction.php',
  'ExplorarPlantillasAction.php',
  'RendimientoPaginasAction.php',
];

if (!is_dir($actions_dir)) {
  $errors[] = 'CHECK 2 FAIL: DailyActions directory does not exist: ' . $actions_dir;
} else {
  $found = 0;
  foreach ($required_actions as $action_file) {
    $path = $actions_dir . '/' . $action_file;
    if (file_exists($path)) {
      $content = file_get_contents($path);
      if (str_contains($content, 'DailyActionInterface')) {
        $found++;
      } else {
        $errors[] = "CHECK 2 FAIL: $action_file does not implement DailyActionInterface";
      }
    } else {
      $errors[] = "CHECK 2 FAIL: Missing daily action: $action_file";
    }
  }
  if ($found === count($required_actions)) {
    $passes[] = "CHECK 2 PASS: All " . count($required_actions) . " daily action classes exist and implement interface";
  }
}

// ── CHECK 3: services.yml tags ───────────────────────────────────────
$services_file = $module_path . '/jaraba_page_builder.services.yml';
if (!file_exists($services_file)) {
  $errors[] = 'CHECK 3 FAIL: services.yml not found';
} else {
  $services_content = file_get_contents($services_file);

  $wizard_tag_count = substr_count($services_content, 'ecosistema_jaraba_core.setup_wizard_step');
  $action_tag_count = substr_count($services_content, 'ecosistema_jaraba_core.daily_action');

  if ($wizard_tag_count >= 4) {
    $passes[] = "CHECK 3a PASS: services.yml has $wizard_tag_count setup_wizard_step tags (>= 4)";
  } else {
    $errors[] = "CHECK 3a FAIL: services.yml has only $wizard_tag_count setup_wizard_step tags (expected >= 4)";
  }

  if ($action_tag_count >= 4) {
    $passes[] = "CHECK 3b PASS: services.yml has $action_tag_count daily_action tags (>= 4)";
  } else {
    $errors[] = "CHECK 3b FAIL: services.yml has only $action_tag_count daily_action tags (expected >= 4)";
  }

  // Verify @? pattern (OPTIONAL-CROSSMODULE-001).
  if (preg_match_all('/@ecosistema_jaraba_core\./', $services_content, $hard_matches)) {
    foreach ($hard_matches[0] as $match) {
      // Count hard refs vs optional refs.
    }
  }
  $hard_refs = preg_match_all('/[^?]@ecosistema_jaraba_core\./', $services_content);
  $optional_refs = substr_count($services_content, '@?ecosistema_jaraba_core.');
  if ($hard_refs > 0) {
    $warnings[] = "CHECK 3c WARN: $hard_refs hard @ecosistema_jaraba_core refs found (should use @?)";
  } else {
    $passes[] = "CHECK 3c PASS: All cross-module deps use @? (OPTIONAL-CROSSMODULE-001)";
  }
}

// ── CHECK 4: hook_theme() declares variables ─────────────────────────
$module_file = $module_path . '/jaraba_page_builder.module';
if (!file_exists($module_file)) {
  $errors[] = 'CHECK 4 FAIL: .module file not found';
} else {
  $module_content = file_get_contents($module_file);

  if (str_contains($module_content, "'setup_wizard' => NULL")) {
    $passes[] = "CHECK 4a PASS: hook_theme() declares 'setup_wizard' variable";
  } else {
    $errors[] = "CHECK 4a FAIL: hook_theme() missing 'setup_wizard' => NULL in page_builder_dashboard";
  }

  if (str_contains($module_content, "'daily_actions' => []")) {
    $passes[] = "CHECK 4b PASS: hook_theme() declares 'daily_actions' variable";
  } else {
    $errors[] = "CHECK 4b FAIL: hook_theme() missing 'daily_actions' => [] in page_builder_dashboard";
  }
}

// ── CHECK 5: Template includes partials ──────────────────────────────
$template_file = $module_path . '/templates/page-builder-dashboard.html.twig';
if (!file_exists($template_file)) {
  $errors[] = 'CHECK 5 FAIL: page-builder-dashboard.html.twig not found';
} else {
  $template_content = file_get_contents($template_file);

  if (str_contains($template_content, '_setup-wizard.html.twig')) {
    $passes[] = "CHECK 5a PASS: Template includes _setup-wizard.html.twig";
  } else {
    $errors[] = "CHECK 5a FAIL: Template missing {% include '_setup-wizard.html.twig' %}";
  }

  if (str_contains($template_content, '_daily-actions.html.twig')) {
    $passes[] = "CHECK 5b PASS: Template includes _daily-actions.html.twig";
  } else {
    $errors[] = "CHECK 5b FAIL: Template missing {% include '_daily-actions.html.twig' %}";
  }

  if (str_contains($template_content, 'only %}')) {
    $passes[] = "CHECK 5c PASS: Template uses 'only' keyword (TWIG-INCLUDE-ONLY-001)";
  } else {
    $warnings[] = "CHECK 5c WARN: Template may not use 'only' keyword in includes";
  }
}

// ── CHECK 6: Controller consumes registries ──────────────────────────
$controller_file = $module_path . '/src/Controller/PageBuilderDashboardController.php';
if (!file_exists($controller_file)) {
  $errors[] = 'CHECK 6 FAIL: PageBuilderDashboardController.php not found';
} else {
  $controller_content = file_get_contents($controller_file);

  if (str_contains($controller_content, 'wizardRegistry') || str_contains($controller_content, 'setup_wizard_registry')) {
    $passes[] = "CHECK 6a PASS: Controller references SetupWizardRegistry";
  } else {
    $errors[] = "CHECK 6a FAIL: Controller does not reference SetupWizardRegistry";
  }

  if (str_contains($controller_content, 'dailyActionsRegistry') || str_contains($controller_content, 'daily_actions_registry')) {
    $passes[] = "CHECK 6b PASS: Controller references DailyActionsRegistry";
  } else {
    $errors[] = "CHECK 6b FAIL: Controller does not reference DailyActionsRegistry";
  }

  if (str_contains($controller_content, '#setup_wizard')) {
    $passes[] = "CHECK 6c PASS: Controller passes #setup_wizard to render array (L2)";
  } else {
    $errors[] = "CHECK 6c FAIL: Controller missing #setup_wizard in render array";
  }

  if (str_contains($controller_content, '#daily_actions')) {
    $passes[] = "CHECK 6d PASS: Controller passes #daily_actions to render array (L2)";
  } else {
    $errors[] = "CHECK 6d FAIL: Controller missing #daily_actions in render array";
  }
}

// ── CHECK 7: Zero Region hooks exist ─────────────────────────────────
if (isset($module_content)) {
  if (str_contains($module_content, 'jaraba_page_builder_preprocess_html')) {
    $passes[] = "CHECK 7a PASS: hook_preprocess_html() exists";
  } else {
    $errors[] = "CHECK 7a FAIL: Missing hook_preprocess_html() for body classes";
  }

  if (str_contains($module_content, 'jaraba_page_builder_preprocess_page')) {
    $passes[] = "CHECK 7b PASS: hook_preprocess_page() exists";
  } else {
    $errors[] = "CHECK 7b FAIL: Missing hook_preprocess_page() for clean_content";
  }

  if (str_contains($module_content, "page__page_builder")) {
    $passes[] = "CHECK 7c PASS: hook_theme_suggestions_page_alter() adds page__page_builder";
  } else {
    $errors[] = "CHECK 7c FAIL: Missing page__page_builder template suggestion";
  }
}

// ── REPORT ───────────────────────────────────────────────────────────
echo "\n";
foreach ($passes as $p) {
  echo "  \033[32m✓\033[0m $p\n";
}
foreach ($warnings as $w) {
  echo "  \033[33m⚠\033[0m $w\n";
}
foreach ($errors as $e) {
  echo "  \033[31m✗\033[0m $e\n";
}

$total = count($passes) + count($errors);
echo "\n═══════════════════════════════════════════════════════════\n";
echo "  RESULT: " . count($passes) . "/$total PASS";
if (!empty($warnings)) {
  echo ", " . count($warnings) . " WARN";
}
if (!empty($errors)) {
  echo ", " . count($errors) . " FAIL";
}
echo "\n═══════════════════════════════════════════════════════════\n";

exit(empty($errors) ? 0 : 1);
