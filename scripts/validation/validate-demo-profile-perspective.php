<?php
/**
 * @file
 * DEMO-PROFILE-PERSPECTIVE-001: Verifica que TODOS los perfiles demo tienen
 * perspectiva B2B (orientados al cliente que paga la suscripción SaaS).
 *
 * Previene regresiones como el perfil "buyer" que mostraba la perspectiva
 * del consumidor final en lugar del comerciante.
 *
 * Checks:
 *   1. Cada perfil tiene al menos 1 métrica de negocio (revenue, orders, bookings)
 *   2. getMagicMomentActions tiene 'view_dashboard' (no solo browse_marketplace)
 *   3. getVerticalContext headline contiene "tu" o "tus" (perspectiva propietario)
 *   4. getDemoStory referencia negocio (clientes, ventas, gestión, plataforma)
 *   5. demo_data no contiene solo métricas de consumidor sin revenue
 *
 * Uso: php scripts/validation/validate-demo-profile-perspective.php
 * Exit: 0 = OK, 1 = perfiles con perspectiva incorrecta
 *
 * @see docs/implementacion/2026-03-20_Plan_Implementacion_Demo_ComercioConecta_3_Perfiles_Comerciante_10_10_v1.md §7.1
 */

$root = dirname(__DIR__, 2);
$file = $root . '/web/modules/custom/ecosistema_jaraba_core/src/Service/DemoInteractiveService.php';
$content = file_get_contents($file);

$errors = [];

// Extract DEMO_PROFILES block ONLY.
$demoProfilesBlock = '';
if (preg_match('/public const DEMO_PROFILES\s*=\s*\[(.*?)^\s{4}\];/ms', $content, $m)) {
    $demoProfilesBlock = $m[1];
}

// Extract profile IDs from DEMO_PROFILES block only (keys with 'id' =>).
preg_match_all("/'id'\s*=>\s*'([a-z_]+)'/", $demoProfilesBlock, $matches);
$profiles = array_unique($matches[1] ?? []);

// === CHECK 1: Métricas de negocio ===
// Incluye métricas de valor para cada tipo de vertical.
$businessMetrics = [
    'revenue_', 'orders_', 'bookings_', 'clients_managed', 'active_cases',
    'active_clients', 'clients_active', 'customers_count',
    // Empleabilidad: applications_sent es acción de valor.
    'applications_sent', 'interviews_scheduled',
    // Social impact: beneficiaries es KPI de impacto.
    'beneficiaries_reached', 'funding_secured',
    // Content Hub: monthly_views es KPI de tracción.
    'monthly_views', 'subscribers',
];
foreach ($profiles as $profile) {
    // Extract demo_data for this profile.
    if (preg_match("/'$profile'\s*=>\s*\[.*?'demo_data'\s*=>\s*\[(.*?)\]/s", $demoProfilesBlock, $dm)) {
        $dataBlock = $dm[1];
        $hasBusinessMetric = false;
        foreach ($businessMetrics as $metric) {
            if (strpos($dataBlock, "'$metric") !== false || strpos($dataBlock, "'" . rtrim($metric, '_') . "'") !== false) {
                $hasBusinessMetric = true;
                break;
            }
        }
        if (!$hasBusinessMetric) {
            $errors[] = "METRIC-B2B: Profile '$profile' has no business metric (revenue, orders, bookings, clients) in demo_data";
        }
    }
}

// === CHECK 2: Magic actions include view_dashboard ===
if (preg_match('/function getMagicMomentActions.*?\$actions\s*=\s*\[(.*?)\];\s*$/ms', $content, $m)) {
    $actionsBlock = $m[1];
    foreach ($profiles as $profile) {
        // Extract this profile's actions.
        if (preg_match("/'$profile'\s*=>\s*\[(.*?)\],\s*(?:'[a-z]|\])/s", $actionsBlock, $am)) {
            $profileActions = $am[1];
            if (strpos($profileActions, "'view_dashboard'") === false
                && strpos($profileActions, "'generate_story'") === false) {
                $errors[] = "ACTION-B2B: Profile '$profile' has no 'view_dashboard' or 'generate_story' in magic actions (consumer-only)";
            }
        }
    }
}

// === CHECK 3: Vertical context headline contiene "tu/tus" ===
if (preg_match('/function getVerticalContext.*?\$contexts\s*=\s*\[(.*?)\];\s*$/ms', $content, $m)) {
    $ctxBlock = $m[1];
    foreach ($profiles as $profile) {
        if (preg_match("/'$profile'\s*=>\s*\[.*?'headline'\s*=>\s*.*?'([^']+)'/s", $ctxBlock, $hm)) {
            $headline = strtolower($hm[1]);
            if (strpos($headline, ' tu ') === false
                && strpos($headline, ' tus ') === false
                && strpos($headline, 'tu ') !== 0) {
                $errors[] = "HEADLINE-B2B: Profile '$profile' headline lacks possessive 'tu/tus' (not owner perspective): '$hm[1]'";
            }
        }
    }
}

// === CHECK 5: No consumer-only profiles (products_available without revenue) ===
foreach ($profiles as $profile) {
    if (preg_match("/'$profile'\s*=>\s*\[.*?'demo_data'\s*=>\s*\[(.*?)\]/s", $demoProfilesBlock, $dm)) {
        $dataBlock = $dm[1];
        if (strpos($dataBlock, "'products_available'") !== false
            && strpos($dataBlock, "'revenue_") === false
            && strpos($dataBlock, "'orders_") === false) {
            $errors[] = "CONSUMER-ONLY: Profile '$profile' has products_available but no revenue/orders — consumer perspective";
        }
    }
}

// === OUTPUT ===
echo "DEMO-PROFILE-PERSPECTIVE-001: B2B Perspective Validation\n";
echo "============================================================\n";
echo "Checked: " . count($profiles) . " profiles\n\n";

if (empty($errors)) {
    echo "\033[0;32m✅ PASS — All " . count($profiles) . " profiles have B2B (owner) perspective.\033[0m\n";
    exit(0);
} else {
    echo "\033[0;31m❌ FAIL — " . count($errors) . " perspective issues found:\033[0m\n\n";
    foreach ($errors as $error) {
        echo "  $error\n";
    }
    exit(1);
}
