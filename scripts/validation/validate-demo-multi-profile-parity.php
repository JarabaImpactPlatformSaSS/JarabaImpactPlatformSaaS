<?php
/**
 * @file
 * DEMO-MULTI-PROFILE-PARITY-001: Verifica que verticales con 3+ perfiles
 * tienen botones de seleccion en los puntos de descubrimiento principales.
 *
 * Previene que se anadan perfiles al servicio DemoInteractiveService pero
 * no se expongan en los templates de showcase (demo landing y homepage).
 *
 * Checks:
 *   1. Identifica verticales con 3+ perfiles en DEMO_PROFILES
 *   2. Verifica que demo-landing.html.twig tiene showcase-profiles-grid para cada vertical multi-perfil
 *   3. Verifica que _product-demo.html.twig tiene usecase-profiles para cada vertical multi-perfil
 *   4. Verifica que TODOS los profile IDs del vertical aparecen en los templates
 *
 * Uso: php scripts/validation/validate-demo-multi-profile-parity.php
 * Exit: 0 = OK, 1 = perfiles no expuestos en discovery points
 *
 * @see docs/implementacion/2026-03-20_Plan_Implementacion_Demo_ComercioConecta_3_Perfiles_Comerciante_10_10_v1.md §7b.2
 */

$root = dirname(__DIR__, 2);
$serviceFile = $root . '/web/modules/custom/ecosistema_jaraba_core/src/Service/DemoInteractiveService.php';
$landingFile = $root . '/web/modules/custom/ecosistema_jaraba_core/templates/demo-landing.html.twig';
$homepageFile = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_product-demo.html.twig';

$errors = [];

// Step 1: Parse DEMO_PROFILES to find multi-profile verticals.
$serviceContent = file_get_contents($serviceFile);
$demoProfilesBlock = '';
if (preg_match('/public const DEMO_PROFILES\s*=\s*\[(.*?)^\s{4}\];/ms', $serviceContent, $m)) {
    $demoProfilesBlock = $m[1];
}

// Extract profile ID → vertical mapping.
$profileVerticals = [];
preg_match_all("/'id'\s*=>\s*'([a-z_]+)'.*?'vertical'\s*=>\s*'([a-z_]+)'/s", $demoProfilesBlock, $matches, PREG_SET_ORDER);
foreach ($matches as $match) {
    $profileVerticals[$match[1]] = $match[2];
}

// Group by vertical.
$verticalProfiles = [];
foreach ($profileVerticals as $profileId => $vertical) {
    $verticalProfiles[$vertical][] = $profileId;
}

// Filter to multi-profile verticals (3+).
$multiProfileVerticals = array_filter($verticalProfiles, function ($profiles) {
    return count($profiles) >= 3;
});

if (empty($multiProfileVerticals)) {
    echo "DEMO-MULTI-PROFILE-PARITY-001: Multi-Profile Parity\n";
    echo "============================================================\n";
    echo "No multi-profile verticals found (3+ profiles). Nothing to check.\n";
    echo "\033[0;32m✅ PASS\033[0m\n";
    exit(0);
}

// Step 2: Check demo-landing.html.twig.
$landingContent = file_exists($landingFile) ? file_get_contents($landingFile) : '';
foreach ($multiProfileVerticals as $vertical => $profileIds) {
    $hasProfileGrid = false;
    // Check for showcase-profiles-grid or showcase-profile-btn with profile IDs.
    foreach ($profileIds as $pid) {
        if (strpos($landingContent, "profileId: '$pid'") !== false
            || strpos($landingContent, "profileId: \"$pid\"") !== false) {
            $hasProfileGrid = true;
        } else {
            $errors[] = "LANDING-MISSING: Profile '$pid' (vertical '$vertical') not found in demo-landing.html.twig showcase";
        }
    }
}

// Step 3: Check _product-demo.html.twig.
$homepageContent = file_exists($homepageFile) ? file_get_contents($homepageFile) : '';
foreach ($multiProfileVerticals as $vertical => $profileIds) {
    foreach ($profileIds as $pid) {
        if (strpos($homepageContent, "profileId: '$pid'") !== false
            || strpos($homepageContent, "profileId: \"$pid\"") !== false) {
            // Present.
        } else {
            $errors[] = "HOMEPAGE-MISSING: Profile '$pid' (vertical '$vertical') not found in _product-demo.html.twig";
        }
    }
}

// === OUTPUT ===
echo "DEMO-MULTI-PROFILE-PARITY-001: Multi-Profile Parity Validation\n";
echo "============================================================\n";
$verticalCount = count($multiProfileVerticals);
$totalProfiles = array_sum(array_map('count', $multiProfileVerticals));
echo "Multi-profile verticals: $verticalCount (" . implode(', ', array_keys($multiProfileVerticals)) . ")\n";
echo "Total profiles to check: $totalProfiles\n";
echo "Discovery points: demo-landing.html.twig, _product-demo.html.twig\n\n";

if (empty($errors)) {
    echo "\033[0;32m✅ PASS — All $totalProfiles profiles in $verticalCount multi-profile verticals are discoverable in both landing and homepage.\033[0m\n";
    exit(0);
} else {
    echo "\033[0;31m❌ FAIL — " . count($errors) . " discovery gaps found:\033[0m\n\n";
    foreach ($errors as $error) {
        echo "  $error\n";
    }
    exit(1);
}
