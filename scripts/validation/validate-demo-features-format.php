<?php
/**
 * @file
 * DEMO-FEATURES-FORMAT-001: Verifica que TODAS las features de getVerticalContext()
 * usan formato rico (array con icon_category/icon_name/title/description),
 * NO plain strings.
 *
 * Previene la inconsistencia visual donde algunos perfiles muestran iconos
 * y descripciones estructuradas y otros solo texto plano sin iconos.
 *
 * Checks:
 *   1. Cada perfil en getVerticalContext() tiene al menos 3 features
 *   2. Cada feature es un array con keys: icon_category, icon_name, title, description
 *   3. No hay features que sean plain strings (string sin array wrapper)
 *
 * Uso: php scripts/validation/validate-demo-features-format.php
 * Exit: 0 = OK, 1 = features con formato incorrecto
 *
 * @see docs/implementacion/2026-03-20_Plan_Implementacion_Demo_ComercioConecta_3_Perfiles_Comerciante_10_10_v1.md §7b.1
 */

$root = dirname(__DIR__, 2);
$file = $root . '/web/modules/custom/ecosistema_jaraba_core/src/Service/DemoInteractiveService.php';
$content = file_get_contents($file);

$errors = [];

// Extract DEMO_PROFILES block to get profile IDs.
$demoProfilesBlock = '';
if (preg_match('/public const DEMO_PROFILES\s*=\s*\[(.*?)^\s{4}\];/ms', $content, $m)) {
    $demoProfilesBlock = $m[1];
}

// Extract profile IDs.
preg_match_all("/'id'\s*=>\s*'([a-z_]+)'/", $demoProfilesBlock, $matches);
$profiles = array_unique($matches[1] ?? []);

// Extract getVerticalContext block.
$ctxBlock = '';
if (preg_match('/function getVerticalContext.*?\$contexts\s*=\s*\[(.*?)\];\s*$/ms', $content, $m)) {
    $ctxBlock = $m[1];
}

if (empty($ctxBlock)) {
    echo "DEMO-FEATURES-FORMAT-001: Features Format Validation\n";
    echo "============================================================\n";
    echo "\033[0;31m❌ FAIL — Could not find getVerticalContext() in DemoInteractiveService.\033[0m\n";
    exit(1);
}

foreach ($profiles as $profile) {
    // Extract this profile's features block.
    // Pattern: 'profile' => [ ... 'features' => [ ... ], ],
    if (!preg_match("/'$profile'\s*=>\s*\[.*?'features'\s*=>\s*\[(.*?)\],\s*\]/s", $ctxBlock, $fm)) {
        $errors[] = "MISSING: Profile '$profile' has no 'features' array in getVerticalContext()";
        continue;
    }

    $featuresBlock = $fm[1];

    // CHECK 1: Count features (look for either array openings or string entries).
    $richCount = preg_match_all("/\[\s*'icon_category'/", $featuresBlock);
    $plainCount = preg_match_all("/^\s*\(string\)\s*\\\$this->t\(/m", $featuresBlock);

    $totalFeatures = $richCount + $plainCount;

    if ($totalFeatures < 3) {
        $errors[] = "FEW-FEATURES: Profile '$profile' has only $totalFeatures features (minimum 3)";
    }

    // CHECK 2: If there are plain string features, flag them.
    if ($plainCount > 0) {
        $errors[] = "PLAIN-STRING: Profile '$profile' has $plainCount plain string features (should be rich format with icon_category/icon_name/title/description)";
    }

    // CHECK 3: Rich features must have all 4 required keys.
    if ($richCount > 0) {
        $requiredKeys = ['icon_category', 'icon_name', 'title', 'description'];
        foreach ($requiredKeys as $key) {
            $keyCount = preg_match_all("/'$key'\s*=>/", $featuresBlock);
            if ($keyCount < $richCount) {
                $errors[] = "MISSING-KEY: Profile '$profile' has $richCount rich features but only $keyCount have '$key'";
            }
        }
    }
}

// === OUTPUT ===
echo "DEMO-FEATURES-FORMAT-001: Features Format Validation\n";
echo "============================================================\n";
echo "Checked: " . count($profiles) . " profiles\n\n";

if (empty($errors)) {
    echo "\033[0;32m✅ PASS — All " . count($profiles) . " profiles use rich format features (icon + title + description).\033[0m\n";
    exit(0);
} else {
    echo "\033[0;31m❌ FAIL — " . count($errors) . " format issues found:\033[0m\n\n";
    foreach ($errors as $error) {
        echo "  $error\n";
    }
    exit(1);
}
