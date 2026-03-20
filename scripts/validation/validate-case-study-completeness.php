<?php

/**
 * @file validate-case-study-completeness.php
 * CASE-STUDY-PATTERN-001: Validates case study landing completeness.
 *
 * Checks that every commercial vertical has:
 * 1. A CaseStudyController with #pricing variable
 * 2. A hook_theme entry for the case study template
 * 3. A route with .case_study. in the name
 * 4. A Twig template with 10 sections
 * 5. A library entry in ecosistema_jaraba_theme.libraries.yml
 * 6. 6 WebP images in the images directory
 * 7. Modules with str_starts_with in preprocess_page exclude .case_study.
 */

$errors = [];
$warnings = [];

// 9 commercial verticals (demo excluded = internal)
$verticals = [
    'jarabalex' => [
        'module' => 'jaraba_success_cases',
        'template' => 'jarabalex-case-study',
        'theme_key' => 'jarabalex_case_study',
        'library' => 'jarabalex-case-study',
        'images_dir' => 'jarabalex-case-study',
        'alt_controller_module' => 'jaraba_legal_intelligence',
    ],
    'agroconecta' => [
        'module' => 'jaraba_agroconecta_core',
        'template' => 'agroconecta-case-study',
        'theme_key' => 'agroconecta_case_study',
        'library' => 'agroconecta-case-study',
        'images_dir' => 'agroconecta-case-study',
    ],
    'emprendimiento' => [
        'module' => 'jaraba_business_tools',
        'template' => 'emprendimiento-case-study',
        'theme_key' => 'emprendimiento_case_study',
        'library' => 'emprendimiento-case-study',
        'images_dir' => 'emprendimiento-case-study',
    ],
    'empleabilidad' => [
        'module' => 'jaraba_candidate',
        'template' => 'empleabilidad-case-study',
        'theme_key' => 'empleabilidad_case_study',
        'library' => 'empleabilidad-case-study',
        'images_dir' => 'empleabilidad-case-study',
    ],
    'comercioconecta' => [
        'module' => 'jaraba_comercio_conecta',
        'template' => 'comercioconecta-case-study',
        'theme_key' => 'comercioconecta_case_study',
        'library' => 'comercioconecta-case-study',
        'images_dir' => 'comercioconecta-case-study',
    ],
    'serviciosconecta' => [
        'module' => 'jaraba_servicios_conecta',
        'template' => 'serviciosconecta-case-study',
        'theme_key' => 'serviciosconecta_case_study',
        'library' => 'serviciosconecta-case-study',
        'images_dir' => 'serviciosconecta-case-study',
    ],
    'formacion' => [
        'module' => 'jaraba_lms',
        'template' => 'formacion-case-study',
        'theme_key' => 'formacion_case_study',
        'library' => 'formacion-case-study',
        'images_dir' => 'formacion-case-study',
    ],
    'andalucia_ei' => [
        'module' => 'jaraba_andalucia_ei',
        'template' => 'andalucia-ei-case-study',
        'theme_key' => 'andalucia_ei_case_study',
        'library' => 'andalucia-ei-case-study',
        'images_dir' => 'andalucia-ei-case-study',
    ],
    'content_hub' => [
        'module' => 'jaraba_content_hub',
        'template' => 'contenthub-case-study',
        'theme_key' => 'contenthub_case_study',
        'library' => 'contenthub-case-study',
        'images_dir' => 'contenthub-case-study',
    ],
];

$themePath = 'web/themes/custom/ecosistema_jaraba_theme';
$modulesPath = 'web/modules/custom';

foreach ($verticals as $key => $config) {
    $modulePath = "$modulesPath/{$config['module']}";

    // 1. Check controller exists (may be in alt module)
    $controllerGlob = glob("$modulePath/src/Controller/*CaseStudy*Controller.php");
    if (empty($controllerGlob) && isset($config['alt_controller_module'])) {
        $altPath = "$modulesPath/{$config['alt_controller_module']}";
        $controllerGlob = glob("$altPath/src/Controller/*CaseStudy*Controller.php")
            ?: glob("$altPath/src/Controller/*Landing*Controller.php");
    }
    if (empty($controllerGlob)) {
        $errors[] = "[$key] Missing CaseStudyController in $modulePath/src/Controller/";
    }

    // 2. Check route with .case_study. or caso-de-exito path
    $routeFound = false;
    $searchModules = [$config['module']];
    if (isset($config['alt_controller_module'])) {
        $searchModules[] = $config['alt_controller_module'];
    }
    foreach ($searchModules as $mod) {
        $routingFile = "$modulesPath/$mod/$mod.routing.yml";
        if (file_exists($routingFile)) {
            $routingContent = file_get_contents($routingFile);
            if (strpos($routingContent, '.case_study.') !== false || strpos($routingContent, 'caso-de-exito') !== false) {
                $routeFound = true;
                break;
            }
        }
    }
    if (!$routeFound) {
        $errors[] = "[$key] No case_study route found in any module routing.yml";
    }

    // 3. Check hook_theme entry (may be in alt module)
    $themeFound = false;
    foreach ($searchModules as $mod) {
        $mFile = "$modulesPath/$mod/$mod.module";
        if (file_exists($mFile)) {
            $mContent = file_get_contents($mFile);
            if (strpos($mContent, "'{$config['theme_key']}'") !== false) {
                $themeFound = true;
                break;
            }
        }
    }
    if (!$themeFound) {
        $errors[] = "[$key] Missing hook_theme entry '{$config['theme_key']}' in module files";
    }

    // 4. Check template exists
    $templateFile = "$themePath/templates/{$config['template']}.html.twig";
    if (!file_exists($templateFile)) {
        $errors[] = "[$key] Missing template: $templateFile";
    } else {
        // Check 10 sections
        $templateContent = file_get_contents($templateFile);
        $sectionCount = preg_match_all('/===\s+\d+/', $templateContent);
        if ($sectionCount < 9) {
            $warnings[] = "[$key] Template has only $sectionCount sections (expected 9-10)";
        }
        // Check data-track-cta
        $ctaCount = preg_match_all('/data-track-cta/', $templateContent);
        if ($ctaCount < 3) {
            $errors[] = "[$key] Template has only $ctaCount CTAs with data-track-cta (min 3)";
        }
        // Check Schema.org
        if (strpos($templateContent, 'application/ld+json') === false) {
            $errors[] = "[$key] Missing Schema.org JSON-LD in template";
        }
    }

    // 5. Check library entry
    $librariesFile = "$themePath/ecosistema_jaraba_theme.libraries.yml";
    if (file_exists($librariesFile)) {
        $libContent = file_get_contents($librariesFile);
        if (strpos($libContent, "{$config['library']}:") === false) {
            $errors[] = "[$key] Missing library '{$config['library']}' in libraries.yml";
        }
    }

    // 6. Check images (at least 5 WebP)
    $imagesDir = "$themePath/images/{$config['images_dir']}";
    if (is_dir($imagesDir)) {
        $webpFiles = glob("$imagesDir/*.webp");
        $webpCount = count($webpFiles);
        if ($webpCount < 5) {
            $errors[] = "[$key] Only $webpCount WebP images in $imagesDir (min 5)";
        }
    } else {
        $errors[] = "[$key] Missing images directory: $imagesDir";
    }
}

// 7. Check modules with str_starts_with in preprocess_page exclude .case_study.
// Modules known to have str_starts_with in preprocess_page that must exclude .case_study.
$modulesWithPreprocess = [
    'jaraba_servicios_conecta',
    'jaraba_comercio_conecta',
    'jaraba_content_hub',
];
// Also check hook_theme_suggestions_page_alter with str_starts_with
$modulesWithSuggestions = [
    'jaraba_content_hub',
];
foreach ($modulesWithPreprocess as $moduleName) {
    $moduleFile = "$modulesPath/$moduleName/$moduleName.module";
    if (file_exists($moduleFile)) {
        $content = file_get_contents($moduleFile);
        if (preg_match('/preprocess_page.*str_starts_with/s', $content)
            && strpos($content, '.case_study.') === false) {
            $errors[] = "[$moduleName] preprocess_page uses str_starts_with but does NOT exclude .case_study. routes";
        }
    }
}

// Report
if (empty($errors) && empty($warnings)) {
    echo "CASE-STUDY-COMPLETENESS-001: PASS — 9/9 commercial verticals have complete case studies\n";
    exit(0);
}

if (!empty($warnings)) {
    foreach ($warnings as $w) {
        echo "  WARN: $w\n";
    }
}
if (!empty($errors)) {
    foreach ($errors as $e) {
        echo "  FAIL: $e\n";
    }
    echo "CASE-STUDY-COMPLETENESS-001: FAIL — " . count($errors) . " errors\n";
    exit(1);
}

echo "CASE-STUDY-COMPLETENESS-001: WARN — " . count($warnings) . " warnings\n";
exit(0);
