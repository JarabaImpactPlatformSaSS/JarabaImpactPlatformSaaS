<?php

declare(strict_types=1);

/**
 * @file
 * QUIZ-FUNNEL-001: Validates the Vertical Quiz funnel integrity.
 *
 * Checks that all components of the quiz exist and are properly wired:
 * routes, controller, service, entity, templates, JS, CSS.
 *
 * EXIT CODES:
 *   0 = All quiz components found
 *   1 = Missing components detected
 *
 * @see 20260319-Plan_Implementacion_Quiz_Recomendacion_Vertical_IA_v1_Claude.md
 */

$root = dirname(__DIR__, 2);
$violations = [];
$checks = 0;

// Helper: check file exists.
$checkFile = function (string $relPath, string $desc) use ($root, &$violations, &$checks): void {
    $checks++;
    if (!file_exists($root . '/' . $relPath)) {
        $violations[] = "MISSING: {$relPath} — {$desc}";
    }
};

// Helper: check string exists in file.
$checkContent = function (string $relPath, string $needle, string $desc) use ($root, &$violations, &$checks): void {
    $checks++;
    $path = $root . '/' . $relPath;
    if (!file_exists($path)) {
        $violations[] = "MISSING FILE: {$relPath} — needed for: {$desc}";
        return;
    }
    $content = file_get_contents($path);
    if ($content === false || strpos($content, $needle) === false) {
        $violations[] = "NOT FOUND: '{$needle}' in {$relPath} — {$desc}";
    }
};

echo "QUIZ-FUNNEL-001: Vertical Quiz Funnel Integrity\n";
echo str_repeat('=', 60) . "\n\n";

// 1. Entity.
$checkFile('web/modules/custom/ecosistema_jaraba_core/src/Entity/QuizResult.php', 'QuizResult entity');

// 2. Service.
$checkFile('web/modules/custom/ecosistema_jaraba_core/src/Service/VerticalQuizService.php', 'VerticalQuizService');

// 3. Controller.
$checkFile('web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalQuizController.php', 'VerticalQuizController');

// 4. Routes.
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml',
    'ecosistema_jaraba_core.quiz_vertical:',
    'Quiz page route'
);
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml',
    'ecosistema_jaraba_core.quiz_vertical.submit:',
    'Quiz submit API route'
);
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml',
    '_csrf_request_header_token',
    'CSRF protection on submit route'
);

// 5. Services.yml registration.
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml',
    'ecosistema_jaraba_core.vertical_quiz:',
    'Service registered in services.yml'
);
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml',
    '@?jaraba_crm.contact',
    'CRM dependency is optional (OPTIONAL-CROSSMODULE-001)'
);

// 6. hook_theme registration.
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module',
    "'quiz_vertical'",
    'hook_theme() registration for quiz template'
);

// 7. Templates.
$checkFile('web/modules/custom/ecosistema_jaraba_core/templates/quiz-vertical.html.twig', 'Quiz page template');
$checkFile('web/modules/custom/ecosistema_jaraba_core/templates/quiz-vertical-result.html.twig', 'Quiz result template');

// 8. Page template.
$checkFile('web/themes/custom/ecosistema_jaraba_theme/templates/page--test-vertical.html.twig', 'Clean page template');

// 9. SCSS + compiled CSS.
$checkFile('web/themes/custom/ecosistema_jaraba_theme/scss/routes/quiz.scss', 'Quiz SCSS source');
$checkFile('web/themes/custom/ecosistema_jaraba_theme/css/routes/quiz.css', 'Quiz compiled CSS');

// 10. JS.
$checkFile('web/themes/custom/ecosistema_jaraba_theme/js/vertical-quiz.js', 'Quiz JS');

// 11. Library.
$checkContent(
    'web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.libraries.yml',
    'route-quiz:',
    'Library registration'
);

// 12. Install hook.
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.install',
    'quiz_result',
    'hook_update_N() for QuizResult entity (UPDATE-HOOK-REQUIRED-001)'
);

// 13. CRM allowed value.
$checkContent(
    'web/modules/custom/jaraba_crm/jaraba_crm.allowed_values.yml',
    'quiz_vertical',
    'Contact source: quiz_vertical in CRM allowed values'
);

// 14. Setup Wizard step (CompletarQuizStep).
$checkFile('web/modules/custom/ecosistema_jaraba_core/src/SetupWizard/CompletarQuizStep.php', 'CompletarQuizStep wizard step');
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml',
    'ecosistema_jaraba_core.setup_wizard.completar_quiz:',
    'Wizard step registered in services.yml'
);

// 15. Daily Action (ExplorarQuizAction).
$checkFile('web/modules/custom/ecosistema_jaraba_core/src/DailyActions/ExplorarQuizAction.php', 'ExplorarQuizAction daily action');
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml',
    'ecosistema_jaraba_core.daily_action.explorar_quiz:',
    'Daily action registered in services.yml'
);

// 16. Post-registration linking (quiz_uuid → user).
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/src/Service/TenantOnboardingService.php',
    'quiz_uuid',
    'Post-registration quiz linking in TenantOnboardingService'
);

// 17. Frontend quiz_uuid capture in onboarding JS.
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/js/ecosistema-jaraba-onboarding.js',
    'quiz_uuid',
    'Quiz UUID captured in registration JS'
);

// 18. Drip email cron.
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module',
    'quiz_followup',
    'Quiz follow-up drip email in hook_mail'
);

// 19. MegaMenuBridgeService (Fase B).
$checkFile('web/modules/custom/ecosistema_jaraba_core/src/Service/MegaMenuBridgeService.php', 'MegaMenuBridgeService (Fase B)');
$checkContent(
    'web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.services.yml',
    'ecosistema_jaraba_core.mega_menu_bridge:',
    'MegaMenuBridge registered in services.yml'
);

// Output.
echo "Checked: {$checks} components\n\n";

if (empty($violations)) {
    echo "✅ PASS — All {$checks} quiz funnel components verified.\n";
    exit(0);
}

echo "❌ FAIL — " . count($violations) . " missing component(s):\n\n";
foreach ($violations as $v) {
    echo "  • {$v}\n";
}
echo "\n";
exit(1);
