<?php

/**
 * @file
 * SAFEGUARD-CONTENT-E2E-001: Content creation pipeline E2E validator.
 *
 * Verifies PIPELINE-E2E-001 (L1-L4) for BOTH content modules:
 * - jaraba_page_builder (Page Builder)
 * - jaraba_content_hub (Content Hub)
 *
 * Usage: php scripts/validation/validate-content-pipeline-e2e.php
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$errors = [];
$warnings = [];
$passes = [];

echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║  SAFEGUARD-CONTENT-E2E-001                             ║\n";
echo "║  Content Pipeline E2E Validator                        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$modules = [
  'jaraba_page_builder' => [
    'controller' => 'src/Controller/PageBuilderDashboardController.php',
    'module_file' => 'jaraba_page_builder.module',
    'template' => 'templates/page-builder-dashboard.html.twig',
    'theme_hook' => 'page_builder_dashboard',
    'page_template' => 'page--page-builder',
  ],
  'jaraba_content_hub' => [
    'controller' => 'src/Controller/ContentHubDashboardController.php',
    'module_file' => 'jaraba_content_hub.module',
    'template' => 'templates/content-hub-dashboard-frontend.html.twig',
    'theme_hook' => 'content_hub_dashboard_frontend',
    'page_template' => 'page--content-hub',
  ],
];

foreach ($modules as $module_name => $config) {
  $module_path = $root . '/web/modules/custom/' . $module_name;
  echo "── Checking $module_name ──────────────────────────────\n";

  if (!is_dir($module_path)) {
    $warnings[] = "[$module_name] Module directory not found (may not be installed)";
    continue;
  }

  // L1: Service injection in controller.
  $controller_path = $module_path . '/' . $config['controller'];
  if (file_exists($controller_path)) {
    $controller = file_get_contents($controller_path);
    if (str_contains($controller, 'wizardRegistry') || str_contains($controller, 'setup_wizard_registry')) {
      $passes[] = "[$module_name] L1 PASS: Controller references SetupWizardRegistry";
    } else {
      $errors[] = "[$module_name] L1 FAIL: Controller missing SetupWizardRegistry injection";
    }
    if (str_contains($controller, 'dailyActionsRegistry') || str_contains($controller, 'daily_actions_registry')) {
      $passes[] = "[$module_name] L1 PASS: Controller references DailyActionsRegistry";
    } else {
      $errors[] = "[$module_name] L1 FAIL: Controller missing DailyActionsRegistry injection";
    }
  } else {
    $errors[] = "[$module_name] L1 FAIL: Controller file not found: " . $config['controller'];
  }

  // L2: Render array includes #setup_wizard and #daily_actions.
  if (isset($controller)) {
    if (str_contains($controller, 'setup_wizard') && str_contains($controller, 'daily_actions')) {
      $passes[] = "[$module_name] L2 PASS: Controller passes wizard + daily data to render array";
    } else {
      $errors[] = "[$module_name] L2 FAIL: Controller missing wizard/daily data in render array";
    }
  }

  // L3: hook_theme() declares variables.
  $module_file_path = $module_path . '/' . $config['module_file'];
  if (file_exists($module_file_path)) {
    $module_content = file_get_contents($module_file_path);

    $has_wizard_var = str_contains($module_content, "'setup_wizard'")
                   || str_contains($module_content, "'wizard'");
    $has_daily_var = str_contains($module_content, "'daily_actions'");

    if ($has_wizard_var) {
      $passes[] = "[$module_name] L3 PASS: hook_theme() declares wizard variable";
    } else {
      $errors[] = "[$module_name] L3 FAIL: hook_theme() missing wizard variable declaration";
    }
    if ($has_daily_var) {
      $passes[] = "[$module_name] L3 PASS: hook_theme() declares daily_actions variable";
    } else {
      $errors[] = "[$module_name] L3 FAIL: hook_theme() missing daily_actions variable declaration";
    }
  } else {
    $errors[] = "[$module_name] L3 FAIL: .module file not found";
  }

  // L4: Template includes partials with 'only'.
  $template_path = $module_path . '/' . $config['template'];
  if (file_exists($template_path)) {
    $template = file_get_contents($template_path);

    if (str_contains($template, '_setup-wizard.html.twig') || str_contains($template, 'setup_wizard')) {
      $passes[] = "[$module_name] L4 PASS: Template includes setup wizard partial";
    } else {
      $errors[] = "[$module_name] L4 FAIL: Template missing setup wizard include";
    }
    if (str_contains($template, '_daily-actions.html.twig') || str_contains($template, 'daily_actions')) {
      $passes[] = "[$module_name] L4 PASS: Template includes daily actions partial";
    } else {
      $errors[] = "[$module_name] L4 FAIL: Template missing daily actions include";
    }
  } else {
    $warnings[] = "[$module_name] L4 WARN: Dashboard template not found at expected path";
  }

  // Zero Region: Verify clean_content pattern.
  $theme_path = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates/' . $config['page_template'] . '.html.twig';
  if (file_exists($theme_path)) {
    $page_template = file_get_contents($theme_path);
    if (str_contains($page_template, 'clean_content')) {
      $passes[] = "[$module_name] ZERO-REGION PASS: Uses {{ clean_content }} pattern";
    } else {
      $warnings[] = "[$module_name] ZERO-REGION WARN: May use {{ page.content }} instead of {{ clean_content }}";
    }
  } else {
    $warnings[] = "[$module_name] ZERO-REGION WARN: Page template " . $config['page_template'] . ".html.twig not found";
  }

  echo "\n";
  unset($controller, $module_content);
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
