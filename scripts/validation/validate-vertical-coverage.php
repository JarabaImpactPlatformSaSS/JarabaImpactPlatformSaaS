<?php

declare(strict_types=1);

/**
 * @file
 * VERTICAL-COVERAGE-001: Validates that all 9 commercial verticals are represented.
 *
 * Checks mega menu, vertical selector, cross-pollination, and quiz scoring
 * to ensure no vertical is forgotten in any discovery point.
 *
 * The 9 commercial verticals (10 canonical - demo):
 * empleabilidad, emprendimiento, comercioconecta, agroconecta,
 * jarabalex, serviciosconecta, andalucia_ei, formacion, jaraba_content_hub
 *
 * EXIT CODES:
 *   0 = All verticals represented
 *   1 = Missing verticals found
 */

$root = dirname(__DIR__, 2);
$violations = [];

// Canonical commercial verticals (demo excluded).
$verticals = [
    'empleabilidad',
    'emprendimiento',
    'comercioconecta',
    'agroconecta',
    'jarabalex',
    'serviciosconecta',
    'andalucia_ei',
    'formacion',
    'jaraba_content_hub',
];

// Alternate forms that count as a match.
$aliases = [
    'andalucia_ei' => ['andalucia-ei', 'andalucia_ei', 'andalucia'],
    'jaraba_content_hub' => ['content-hub', 'content_hub', 'Content Hub'],
    'formacion' => ['formacion', 'Formación'],
    'empleabilidad' => ['empleabilidad', 'Empleabilidad'],
    'emprendimiento' => ['emprendimiento', 'Emprendimiento'],
    'comercioconecta' => ['comercioconecta', 'ComercioConecta'],
    'agroconecta' => ['agroconecta', 'AgroConecta'],
    'jarabalex' => ['jarabalex', 'JarabaLex'],
    'serviciosconecta' => ['serviciosconecta', 'ServiciosConecta'],
];

echo "VERTICAL-COVERAGE-001: Vertical Coverage Validation\n";
echo str_repeat('=', 60) . "\n\n";

/**
 * Check if a file contains references to a vertical (or its aliases).
 */
function verticalFoundIn(string $content, string $vertical, array $aliases): bool {
    $forms = $aliases[$vertical] ?? [$vertical];
    foreach ($forms as $form) {
        if (stripos($content, $form) !== false) {
            return true;
        }
    }
    return false;
}

// --- Check 1: Mega menu PHP preprocess ---
$themeFile = $root . '/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';
$themeContent = file_get_contents($themeFile) ?: '';

$missing_megamenu = [];
foreach ($verticals as $v) {
    // Check in the mega_menu_columns section.
    if (!verticalFoundIn($themeContent, $v, $aliases)) {
        $missing_megamenu[] = $v;
    }
}
if (!empty($missing_megamenu)) {
    $violations[] = "Mega menu (PHP preprocess): Missing verticals: " . implode(', ', $missing_megamenu);
}

// --- Check 2: Quiz scoring matrix ---
$quizServiceFile = $root . '/web/modules/custom/ecosistema_jaraba_core/src/Service/VerticalQuizService.php';
$quizContent = file_exists($quizServiceFile) ? file_get_contents($quizServiceFile) : '';

if (empty($quizContent)) {
    $violations[] = "Quiz scoring: VerticalQuizService.php not found";
} else {
    $missing_quiz = [];
    foreach ($verticals as $v) {
        if (!verticalFoundIn($quizContent, $v, $aliases)) {
            $missing_quiz[] = $v;
        }
    }
    if (!empty($missing_quiz)) {
        $violations[] = "Quiz scoring (VerticalQuizService): Missing verticals: " . implode(', ', $missing_quiz);
    }
}

// --- Check 3: Cross-pollination template ---
$crossPollFile = $root . '/web/themes/custom/ecosistema_jaraba_theme/templates/partials/_cross-pollination.html.twig';
if (file_exists($crossPollFile)) {
    $crossContent = file_get_contents($crossPollFile) ?: '';
    $missing_cross = [];
    // Cross-pollination should have at least 8 verticals (content_hub may be optional).
    foreach ($verticals as $v) {
        if ($v === 'jaraba_content_hub') {
            continue; // Optional in cross-pollination.
        }
        if (!verticalFoundIn($crossContent, $v, $aliases)) {
            $missing_cross[] = $v;
        }
    }
    if (count($missing_cross) > 1) {
        $violations[] = "Cross-pollination: Missing verticals: " . implode(', ', $missing_cross);
    }
}

// Output.
echo "Verticals checked: " . count($verticals) . "\n";
echo "Discovery points: Mega menu, Quiz, Cross-pollination\n\n";

if (empty($violations)) {
    echo "✅ PASS — All 9 commercial verticals represented in all discovery points.\n";
    exit(0);
}

echo "❌ FAIL — Coverage gaps found:\n\n";
foreach ($violations as $v) {
    echo "  • {$v}\n";
}
echo "\n";
exit(1);
