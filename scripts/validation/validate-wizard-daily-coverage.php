<?php

/**
 * @file
 * Validador: SETUP-WIZARD-DAILY-001 — Cobertura wizard/daily por vertical.
 *
 * Verifica que cada vertical con Setup Wizard steps tenga tambien
 * Daily Actions registradas, y viceversa. Detecta verticales sin
 * ninguno de los dos patrones.
 *
 * Uso:
 *   php scripts/validation/validate-wizard-daily-coverage.php
 *
 * Exit codes:
 *   0 = OK (todos los verticales cubiertos)
 *   1 = FAIL (verticales sin wizard o daily actions)
 */

declare(strict_types=1);

// Buscar servicios tagged en TODOS los services.yml de módulos custom.
$modulesDir = __DIR__ . '/../../web/modules/custom';
$servicesFiles = glob($modulesDir . '/*/*.services.yml');

if (empty($servicesFiles)) {
    echo "ERROR: No se encuentran ficheros services.yml en $modulesDir\n";
    exit(1);
}

$content = '';
foreach ($servicesFiles as $file) {
    $content .= file_get_contents($file) . "\n";
}

/**
 * Busca un fichero PHP de clase en todos los módulos custom.
 */
function findClassFile(string $modulesDir, string $subdir, string $className): ?string {
    $pattern = $modulesDir . "/*/src/{$subdir}/{$className}.php";
    $files = glob($pattern);
    return !empty($files) ? $files[0] : null;
}

// Extraer wizard IDs de los servicios tagged.
$wizardIds = [];
if (preg_match_all('/class:\s+.*\\\\SetupWizard\\\\(\w+)/m', $content, $matches)) {
    foreach ($matches[1] as $className) {
        // Leer el fichero PHP para obtener wizardId.
        $phpFile = findClassFile($modulesDir, 'SetupWizard', $className);
        if ($phpFile !== null && file_exists($phpFile)) {
            $phpContent = file_get_contents($phpFile);
            if (preg_match("/getWizardId\(\).*?return\s+'([^']+)'/s", $phpContent, $m)) {
                $wizardId = $m[1];
                if ($wizardId !== '__global__') {
                    $wizardIds[$wizardId] = ($wizardIds[$wizardId] ?? 0) + 1;
                }
            }
        }
    }
}

// Extraer dashboard IDs de los daily actions.
$dashboardIds = [];
if (preg_match_all('/class:\s+.*\\\\DailyActions\\\\(\w+)/m', $content, $matches)) {
    foreach ($matches[1] as $className) {
        $phpFile = findClassFile($modulesDir, 'DailyActions', $className);
        if ($phpFile !== null && file_exists($phpFile)) {
            $phpContent = file_get_contents($phpFile);
            if (preg_match("/getDashboardId\(\).*?return\s+'([^']+)'/s", $phpContent, $m)) {
                $dashboardId = $m[1];
                if ($dashboardId !== '__global__') {
                    $dashboardIds[$dashboardId] = ($dashboardIds[$dashboardId] ?? 0) + 1;
                }
            }
        }
    }
}

// Verticales canonicos (VERTICAL-CANONICAL-001) que DEBERIAN tener wizard+daily.
$canonicalVerticals = [
    'empleabilidad' => 'candidato_empleo',
    'emprendimiento' => 'emprendedor',
    'comercioconecta' => 'merchant_comercio',
    'agroconecta' => 'producer_agro',
    'jarabalex' => 'legal_professional',
    'serviciosconecta' => 'provider_servicios',
    'andalucia_ei' => 'coordinador_ei',
    'formacion' => 'instructor_lms',
    'demo' => 'demo_visitor',
];

$errors = [];
$warnings = [];

echo "\n=== SETUP-WIZARD-DAILY-001: Cobertura wizard/daily por vertical ===\n\n";

// Chequeo 1: wizard IDs que existen en los steps.
$allIds = array_unique(array_merge(array_keys($wizardIds), array_keys($dashboardIds)));
sort($allIds);

foreach ($allIds as $id) {
    $hasWizard = isset($wizardIds[$id]);
    $hasDashboard = isset($dashboardIds[$id]);
    $wizardCount = $wizardIds[$id] ?? 0;
    $dashboardCount = $dashboardIds[$id] ?? 0;

    $status = ($hasWizard && $hasDashboard) ? 'OK' : 'WARN';
    $details = [];
    if ($hasWizard) {
        $details[] = "{$wizardCount} wizard steps";
    } else {
        $details[] = "0 wizard steps";
        $warnings[] = "Vertical '{$id}' tiene daily actions pero NO wizard steps.";
    }
    if ($hasDashboard) {
        $details[] = "{$dashboardCount} daily actions";
    } else {
        $details[] = "0 daily actions";
        $warnings[] = "Vertical '{$id}' tiene wizard steps pero NO daily actions.";
    }

    $statusIcon = $status === 'OK' ? 'PASS' : 'WARN';
    echo "  [{$statusIcon}] {$id}: " . implode(', ', $details) . "\n";
}

// Chequeo 2: verticales canonicos sin coverage.
echo "\n--- Verticales canónicos ---\n";
foreach ($canonicalVerticals as $vertical => $expectedId) {
    $hasWizard = isset($wizardIds[$expectedId]);
    $hasDashboard = isset($dashboardIds[$expectedId]);

    if (!$hasWizard && !$hasDashboard) {
        $errors[] = "Vertical '{$vertical}' (ID: {$expectedId}) NO tiene wizard NI daily actions.";
        echo "  [FAIL] {$vertical} ({$expectedId}): sin cobertura\n";
    } elseif (!$hasWizard || !$hasDashboard) {
        $missing = !$hasWizard ? 'wizard' : 'daily actions';
        $warnings[] = "Vertical '{$vertical}' ({$expectedId}) sin {$missing}.";
        echo "  [WARN] {$vertical} ({$expectedId}): falta {$missing}\n";
    } else {
        echo "  [PASS] {$vertical} ({$expectedId})\n";
    }
}

// Resumen.
echo "\n=== Resumen ===\n";
echo "  Wizard IDs encontrados: " . count($wizardIds) . "\n";
echo "  Dashboard IDs encontrados: " . count($dashboardIds) . "\n";
echo "  Errores: " . count($errors) . "\n";
echo "  Advertencias: " . count($warnings) . "\n";

if (count($errors) > 0) {
    echo "\n--- ERRORES ---\n";
    foreach ($errors as $e) {
        echo "  [ERROR] {$e}\n";
    }
    exit(1);
}

if (count($warnings) > 0) {
    echo "\n--- ADVERTENCIAS ---\n";
    foreach ($warnings as $w) {
        echo "  [WARN] {$w}\n";
    }
}

echo "\n[OK] SETUP-WIZARD-DAILY-001: Validación completada.\n";
exit(0);
