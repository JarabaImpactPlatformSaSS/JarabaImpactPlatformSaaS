<?php

/**
 * @file
 * SAFEGUARD-DEPLOY-PERSISTENCE-001: Verifica que settings.env.php generation
 * en deploy.yml contiene TODAS las credenciales referenciadas en settings.secrets.php.
 *
 * Detecta credenciales huerfanas: presentes en settings.secrets.php (getenv())
 * pero NO generadas en deploy.yml (putenv()). Estas credenciales se pierden
 * en cada deploy porque git reset --hard sobreescribe settings.env.php.
 *
 * 3 checks:
 * 1. Todas las getenv() de settings.secrets.php tienen putenv() en deploy.yml
 * 2. deploy.yml tiene step SEO-DEPLOY-NOTIFY-001 con continue-on-error
 * 3. settings.secrets.php tiene bloque GSC OAuth
 */

$errors = [];
$warnings = [];
$passed = 0;
$total = 3;

$base = dirname(__DIR__, 2);
$secrets_file = $base . '/config/deploy/settings.secrets.php';
$deploy_file = $base . '/.github/workflows/deploy.yml';

if (!file_exists($secrets_file) || !file_exists($deploy_file)) {
    echo "\n  ✗ SKIP: settings.secrets.php or deploy.yml not found\n\n";
    exit(0);
}

$secrets_code = file_get_contents($secrets_file);
$deploy_code = file_get_contents($deploy_file);

echo "\n=== SAFEGUARD-DEPLOY-PERSISTENCE-001: Deploy Env Persistence ===\n\n";

// CHECK 1: Todas las getenv() de secrets tienen putenv() en deploy.
preg_match_all("/getenv\(['\"]([A-Z_]+)['\"]\)/", $secrets_code, $secretsVars);
$secretEnvVars = array_unique($secretsVars[1] ?? []);

preg_match_all("/putenv\(['\"]([A-Z_]+)=/", $deploy_code, $deployVars);
$deployEnvVars = array_unique($deployVars[1] ?? []);

// Tambien buscar en formato ${KEY_*} que luego se mapean a putenv.
preg_match_all('/putenv\([\'"]([A-Z_]+)=\$\{/', $deploy_code, $deployVarsTemplate);
$deployEnvVarsTemplate = array_unique($deployVarsTemplate[1] ?? []);
$allDeployVars = array_merge($deployEnvVars, $deployEnvVarsTemplate);

$orphaned = array_diff($secretEnvVars, $allDeployVars);

// Excluir variables que son intencionales sin deploy (ej: test/local only).
$allowedOrphans = ['OAUTH_CALLBACK_BASE_URL'];
$orphaned = array_diff($orphaned, $allowedOrphans);

if (count($orphaned) === 0) {
    $passed++;
    echo "  ✓ CHECK 1: Todas las " . count($secretEnvVars) . " env vars de secrets.php tienen generacion en deploy.yml\n";
} else {
    $errors[] = 'CHECK 1: ' . count($orphaned) . ' env var(s) en settings.secrets.php sin generacion en deploy.yml: ' . implode(', ', $orphaned);
}

// CHECK 2: deploy.yml tiene step SEO-DEPLOY-NOTIFY-001.
if (preg_match('/SEO-DEPLOY-NOTIFY-001/', $deploy_code) && preg_match('/continue-on-error:\s*true/', $deploy_code)) {
    $passed++;
    echo "  ✓ CHECK 2: deploy.yml tiene step SEO-DEPLOY-NOTIFY-001 con continue-on-error\n";
} else {
    $errors[] = 'CHECK 2: deploy.yml falta step SEO-DEPLOY-NOTIFY-001 o sin continue-on-error';
}

// CHECK 3: settings.secrets.php tiene bloque GSC OAuth.
if (preg_match('/GOOGLE_SEARCH_CONSOLE_CLIENT_ID/', $secrets_code) && preg_match('/jaraba_insights_hub\.settings.*search_console_client_id/', $secrets_code)) {
    $passed++;
    echo "  ✓ CHECK 3: settings.secrets.php tiene mapeo GSC OAuth → jaraba_insights_hub.settings\n";
} else {
    $errors[] = 'CHECK 3: settings.secrets.php falta mapeo GOOGLE_SEARCH_CONSOLE_CLIENT_ID → config';
}

// Resultado.
echo "\n";
if (!empty($errors)) {
    foreach ($errors as $e) {
        echo "  ✗ {$e}\n";
    }
}
if (!empty($warnings)) {
    foreach ($warnings as $w) {
        echo "  ⚠ {$w}\n";
    }
}

echo "\n  Resultado: {$passed}/{$total} checks OK\n";

if (!empty($errors)) {
    echo "\n  ❌ FAILED\n\n";
    exit(1);
}
echo "\n  ✅ ALL CHECKS PASSED\n\n";
exit(0);
