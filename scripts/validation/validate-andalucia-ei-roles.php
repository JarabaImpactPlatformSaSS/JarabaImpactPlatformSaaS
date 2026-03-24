<?php

/**
 * @file
 * Validator: Andalucía +ei program role system integrity.
 *
 * 8 checks verifying roles exist, permissions assigned, dashboards per role,
 * wizard/daily actions coverage, detection coherence, and audit entity.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-roles.php
 * Orchestrator: validate-all.sh (run_check)
 */

declare(strict_types=1);

$errors = [];
$warnings = [];
$passes = [];

// Helper: Read YAML file.
function readYaml(string $path): ?array {
  if (!file_exists($path)) {
    return NULL;
  }
  $content = file_get_contents($path);
  if ($content === FALSE) {
    return NULL;
  }
  // Simple YAML parser for role files (flat structure).
  $data = [];
  foreach (explode("\n", $content) as $line) {
    if (preg_match('/^(\w[\w\s]*?):\s*(.*)$/', $line, $m)) {
      $data[trim($m[1])] = trim($m[2], " '\"");
    }
  }
  return $data;
}

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';
$configInstall = $moduleRoot . '/config/install';

// =====================================================
// CHECK 1: Drupal roles exist in config/install
// =====================================================
$rolesRequired = ['coordinador_ei', 'orientador_ei', 'formador_ei'];
$missingRoles = [];

foreach ($rolesRequired as $roleId) {
  $path = "$configInstall/user.role.$roleId.yml";
  if (!file_exists($path)) {
    $missingRoles[] = $roleId;
  }
}

if (empty($missingRoles)) {
  $passes[] = "CHECK 1 PASS: 3/3 role config files exist in config/install/";
}
else {
  $errors[] = "CHECK 1 FAIL: Missing role configs: " . implode(', ', $missingRoles);
}

// =====================================================
// CHECK 2: Key permissions assigned in each role file
// =====================================================
$rolPermChecks = [
  'coordinador_ei' => ['administer andalucia ei', 'assign andalucia ei roles'],
  'orientador_ei' => ['access andalucia ei orientador dashboard', 'register andalucia ei actuacion'],
  'formador_ei' => ['access andalucia ei formador dashboard', 'mark attendance sesion ei'],
];

$permissionsOk = TRUE;
foreach ($rolPermChecks as $roleId => $requiredPerms) {
  $path = "$configInstall/user.role.$roleId.yml";
  if (!file_exists($path)) {
    continue;
  }
  $content = file_get_contents($path);
  foreach ($requiredPerms as $perm) {
    if (strpos($content, $perm) === FALSE) {
      $errors[] = "CHECK 2 FAIL: Role $roleId missing permission '$perm'";
      $permissionsOk = FALSE;
    }
  }
}

if ($permissionsOk) {
  $passes[] = "CHECK 2 PASS: Key permissions assigned to all 3 roles";
}

// =====================================================
// CHECK 3: Dashboard routes per role
// =====================================================
$routingFile = $moduleRoot . '/jaraba_andalucia_ei.routing.yml';
$routingContent = file_exists($routingFile) ? file_get_contents($routingFile) : '';

$dashboardRoutes = [
  'coordinador' => 'jaraba_andalucia_ei.coordinador_dashboard',
  'orientador' => 'jaraba_andalucia_ei.orientador_dashboard',
  'formador' => 'jaraba_andalucia_ei.formador_dashboard',
];

$dashboardsOk = TRUE;
foreach ($dashboardRoutes as $role => $routeName) {
  // Check route name exists in routing file.
  if (strpos($routingContent, "$routeName:") === FALSE) {
    $errors[] = "CHECK 3 FAIL: Dashboard route '$routeName' missing for role '$role'";
    $dashboardsOk = FALSE;
  }
}

if ($dashboardsOk) {
  $passes[] = "CHECK 3 PASS: 3/3 dashboard routes defined in routing.yml";
}

// =====================================================
// CHECK 4: Setup Wizard steps per role
// =====================================================
$servicesFile = $moduleRoot . '/jaraba_andalucia_ei.services.yml';
$servicesContent = file_exists($servicesFile) ? file_get_contents($servicesFile) : '';

$wizardChecks = [
  'coordinador' => 'setup_wizard.plan_formativo',
  'orientador' => 'setup_wizard.orientador_perfil',
  'formador' => 'setup_wizard.formador_perfil',
];

$wizardOk = TRUE;
foreach ($wizardChecks as $role => $serviceFragment) {
  if (strpos($servicesContent, $serviceFragment) === FALSE) {
    $errors[] = "CHECK 4 FAIL: Setup Wizard step '$serviceFragment' missing for role '$role'";
    $wizardOk = FALSE;
  }
}

if ($wizardOk) {
  $passes[] = "CHECK 4 PASS: Setup Wizard steps registered for all 3 roles";
}

// =====================================================
// CHECK 5: Daily Actions per role
// =====================================================
$dailyChecks = [
  'coordinador' => 'daily_action.solicitudes',
  'orientador' => 'daily_action.orientador_sesiones_hoy',
  'formador' => 'daily_action.formador_sesiones_hoy',
];

$dailyOk = TRUE;
foreach ($dailyChecks as $role => $serviceFragment) {
  if (strpos($servicesContent, $serviceFragment) === FALSE) {
    $errors[] = "CHECK 5 FAIL: Daily Action '$serviceFragment' missing for role '$role'";
    $dailyOk = FALSE;
  }
}

if ($dailyOk) {
  $passes[] = "CHECK 5 PASS: Daily Actions registered for all 3 roles";
}

// =====================================================
// CHECK 6: Detection coherent (RolProgramaService used)
// =====================================================
$rolServiceFile = $moduleRoot . '/src/Service/RolProgramaService.php';
$accesoServiceFile = $moduleRoot . '/src/Service/AccesoProgramaService.php';

if (file_exists($rolServiceFile)) {
  $passes[] = "CHECK 6a PASS: RolProgramaService exists";
}
else {
  $errors[] = "CHECK 6a FAIL: RolProgramaService.php not found";
}

if (file_exists($accesoServiceFile)) {
  $accesoContent = file_get_contents($accesoServiceFile);
  if (strpos($accesoContent, 'rolProgramaService') !== FALSE) {
    $passes[] = "CHECK 6b PASS: AccesoProgramaService delegates to RolProgramaService";
  }
  else {
    $warnings[] = "CHECK 6b WARN: AccesoProgramaService does not reference RolProgramaService";
  }
}

// =====================================================
// CHECK 7: Log entity installed
// =====================================================
$logEntityFile = $moduleRoot . '/src/Entity/RolProgramaLog.php';
if (file_exists($logEntityFile)) {
  $passes[] = "CHECK 7 PASS: RolProgramaLog entity class exists";
}
else {
  $errors[] = "CHECK 7 FAIL: RolProgramaLog entity class not found";
}

// =====================================================
// CHECK 8: No wildcard permissions (warn)
// =====================================================
$accesoContent = file_exists($accesoServiceFile) ? file_get_contents($accesoServiceFile) : '';

// Count how many times 'view programa participante ei' appears as access gate.
$viewPermCount = substr_count($accesoContent, "'view programa participante ei'");
if ($viewPermCount > 2) {
  $warnings[] = "CHECK 8 WARN: Permission 'view programa participante ei' used as gate $viewPermCount times — may be ambiguous across roles";
}
else {
  $passes[] = "CHECK 8 PASS: No wildcard permission abuse detected";
}

// =====================================================
// RESULTS
// =====================================================
$total = count($errors) + count($warnings) + count($passes);
$passCount = count($passes);

echo "\n=== ANDALUCÍA +EI ROLE SYSTEM INTEGRITY ===\n\n";

foreach ($passes as $msg) {
  echo "  ✅ $msg\n";
}
foreach ($warnings as $msg) {
  echo "  ⚠️  $msg\n";
}
foreach ($errors as $msg) {
  echo "  ❌ $msg\n";
}

echo "\n--- Score: $passCount/" . ($passCount + count($errors)) . " checks passed";
if (!empty($warnings)) {
  echo " (" . count($warnings) . " warnings)";
}
echo " ---\n\n";

exit(empty($errors) ? 0 : 1);
