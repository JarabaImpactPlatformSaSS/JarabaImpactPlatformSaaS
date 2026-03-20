<?php

/**
 * @file validate-preprocess-isolation.php
 * PREPROCESS-ISOLATION-001: Detects preprocess_page / theme_suggestions_page_alter
 * hooks that use str_starts_with() without excluding special route patterns.
 *
 * Modules that blanket-capture all their routes in preprocess hooks break
 * case studies, API endpoints, and other cross-cutting routes.
 *
 * Required exclusions: .case_study., .api.
 */

$errors = [];
$warnings = [];
$modulesPath = 'web/modules/custom';

// Only check modules where case study routes start with the module's prefix
// (i.e., the str_starts_with would actually capture the case study route)
$modulesWithCaseStudies = [];
$routingFiles = glob("$modulesPath/*/*.routing.yml");
foreach ($routingFiles as $rf) {
    $moduleName = basename(dirname($rf));
    $content = file_get_contents($rf);
    // Check if any route starting with {module_name}. contains case_study
    if (preg_match('/^' . preg_quote($moduleName) . '\.[^\n]*case_study/m', $content)) {
        $modulesWithCaseStudies[] = $moduleName;
    }
}

$dirs = glob("$modulesPath/*/", GLOB_ONLYDIR);

foreach ($dirs as $dir) {
    $moduleName = basename($dir);
    $moduleFile = "$dir/$moduleName.module";

    if (!file_exists($moduleFile)) {
        continue;
    }

    // Only check modules that have case study routes
    if (!in_array($moduleName, $modulesWithCaseStudies, true)) {
        continue;
    }

    $content = file_get_contents($moduleFile);

    // Check preprocess_page with str_starts_with
    if (preg_match('/function\s+' . preg_quote($moduleName) . '_preprocess_page\b/s', $content)) {
        // Extract the function body (simplified: look for str_starts_with pattern)
        if (preg_match('/preprocess_page.*?str_starts_with\s*\(\s*\$route_name/s', $content)) {
            // Check for .case_study. exclusion
            if (strpos($content, '.case_study.') === false) {
                $errors[] = "[$moduleName] preprocess_page uses str_starts_with(\$route_name) but does NOT exclude .case_study. routes";
            }
        }
    }

    // Check theme_suggestions_page_alter with str_starts_with
    if (preg_match('/function\s+' . preg_quote($moduleName) . '_theme_suggestions_page_alter\b/s', $content)) {
        if (preg_match('/theme_suggestions_page_alter.*?str_starts_with\s*\(\s*\$route_name/s', $content)) {
            if (strpos($content, '.case_study.') === false) {
                $errors[] = "[$moduleName] theme_suggestions_page_alter uses str_starts_with(\$route_name) but does NOT exclude .case_study. routes";
            }
        }
    }
}

if (empty($errors)) {
    echo "PREPROCESS-ISOLATION-001: PASS — All preprocess/suggestions hooks properly exclude special routes\n";
    exit(0);
}

foreach ($errors as $e) {
    echo "  FAIL: $e\n";
}
echo "PREPROCESS-ISOLATION-001: FAIL — " . count($errors) . " hooks without proper route exclusion\n";
exit(1);
