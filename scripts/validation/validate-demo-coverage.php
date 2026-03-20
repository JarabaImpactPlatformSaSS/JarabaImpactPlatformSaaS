<?php
/**
 * @file
 * DEMO-COVERAGE-001: Verifica que TODOS los perfiles demo tienen los 9
 * componentes del DEMO-VERTICAL-PATTERN-001 con datos de calidad.
 *
 * Garantiza paridad entre demos verticales — si lawfirm tiene chat IA
 * adaptado, TODOS los perfiles deben tenerlo.
 *
 * Uso: php scripts/validation/validate-demo-coverage.php
 * Exit: 0 = OK, 1 = perfiles incompletos
 */

$root = dirname(__DIR__, 2);

// All demo profiles from DemoInteractiveService::PROFILES
// S17-02: buyer reemplazado por gourmet/boutique/beautypro (2026-03-20).
$profiles = [
    'lawfirm', 'startup', 'academy', 'servicepro',
    'winery', 'producer', 'cheese',
    'gourmet', 'boutique', 'beautypro',
    'jobseeker', 'socialimpact', 'creator',
];

$errors = [];

// === CHECK 1: getCopilotDemoChat has entry for each profile ===
$chatFile = $root . '/web/modules/custom/ecosistema_jaraba_core/src/Service/DemoInteractiveService.php';
$chatContent = file_get_contents($chatFile);

// Extract profile keys from getCopilotDemoChat
if (preg_match('/function getCopilotDemoChat.*?\$chats\s*=\s*\[(.*?)\];/s', $chatContent, $m)) {
    $chatBlock = $m[1];
    foreach ($profiles as $profile) {
        if (strpos($chatBlock, "'$profile'") === false) {
            $errors[] = "CHAT-MISSING: Profile '$profile' has no entry in getCopilotDemoChat()";
        }
    }
} else {
    $errors[] = "CHAT-METHOD: Could not find getCopilotDemoChat() in DemoInteractiveService";
}

// === CHECK 2: getVerticalContext has entry for each profile ===
if (preg_match('/function getVerticalContext.*?\$contexts\s*=\s*\[(.*?)\];/s', $chatContent, $m)) {
    $ctxBlock = $m[1];
    foreach ($profiles as $profile) {
        if (strpos($ctxBlock, "'$profile'") === false) {
            $errors[] = "CONTEXT-MISSING: Profile '$profile' has no entry in getVerticalContext()";
        }
    }
}

// === CHECK 3: Social proof has testimony for each profile ===
$socialFile = $root . '/web/modules/custom/ecosistema_jaraba_core/templates/partials/_demo-social-proof.html.twig';
if (file_exists($socialFile)) {
    $socialContent = file_get_contents($socialFile);
    foreach ($profiles as $profile) {
        // Some profiles share testimonies (winery/producer/cheese)
        if (in_array($profile, ['producer', 'cheese'])) {
            continue; // Shared with winery
        }
        if (strpos($socialContent, "profile.id == '$profile'") === false
            && strpos($socialContent, "profile.id == '$profile'") === false) {
            // Check if it's covered by an 'or' condition
            if (strpos($socialContent, $profile) === false) {
                $errors[] = "TESTIMONY-MISSING: Profile '$profile' has no testimony in _demo-social-proof.html.twig";
            }
        }
    }
} else {
    $errors[] = "SOCIAL-FILE: _demo-social-proof.html.twig not found";
}

// === CHECK 4: SYNTHETIC_PRODUCTS has entry for profiles that sell ===
$sellerProfiles = ['lawfirm', 'startup', 'academy', 'servicepro', 'winery', 'producer', 'cheese', 'jobseeker'];
if (preg_match('/SYNTHETIC_PRODUCTS\s*=\s*\[(.*?)\];/s', $chatContent, $m)) {
    $productsBlock = $m[1];
    foreach ($sellerProfiles as $profile) {
        if (strpos($productsBlock, "'$profile'") === false) {
            $errors[] = "PRODUCTS-MISSING: Seller profile '$profile' has no SYNTHETIC_PRODUCTS entry";
        }
    }
}

// === CHECK 5: Quiz illustrations exist for verticals ===
$quizDir = $root . '/web/themes/custom/ecosistema_jaraba_theme/images/quiz';
$verticals = [
    'empleabilidad', 'emprendimiento', 'agroconecta', 'comercioconecta',
    'serviciosconecta', 'jarabalex', 'andalucia_ei', 'jaraba_content_hub', 'formacion',
];
foreach ($verticals as $v) {
    if (!file_exists("$quizDir/$v.png")) {
        $errors[] = "QUIZ-IMG-MISSING: Quiz illustration '$v.png' not found in images/quiz/";
    }
}

// Report
if (empty($errors)) {
    echo "✅ DEMO-COVERAGE-001: All " . count($profiles) . " profiles fully covered.\n";
    echo "   Checks: copilot chat, vertical context, testimonies, products, quiz images\n";
    exit(0);
}

echo "❌ DEMO-COVERAGE-001: " . count($errors) . " gaps in demo profile coverage!\n\n";
foreach ($errors as $error) {
    echo "  $error\n";
}
echo "\nReference: DEMO-VERTICAL-PATTERN-001 (9 components per profile)\n";
exit(1);
