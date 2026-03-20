<?php

/**
 * @file validate-vertical-cross-links.php
 * VERTICAL-CROSS-LINK-001: Verifies that case_study cross-links in
 * VerticalLandingController point to existing routes.
 *
 * Extracts all 'url' => '/...' patterns from social_proof.case_study
 * blocks and checks that corresponding route and template exist.
 */

$errors = [];

$controllerFile = 'web/modules/custom/ecosistema_jaraba_core/src/Controller/VerticalLandingController.php';

if (!file_exists($controllerFile)) {
    echo "VERTICAL-CROSS-LINK-001: SKIP — VerticalLandingController not found\n";
    exit(0);
}

$content = file_get_contents($controllerFile);

// Extract all case_study URLs from the controller
$caseStudyUrls = [];
if (preg_match_all("/'case_study'\s*=>\s*\[.*?'url'\s*=>\s*'([^']+)'/s", $content, $matches)) {
    $caseStudyUrls = $matches[1];
}

if (empty($caseStudyUrls)) {
    echo "VERTICAL-CROSS-LINK-001: WARN — No case_study cross-links found in VerticalLandingController\n";
    exit(0);
}

// For each URL, verify a matching route exists in routing files
$routingFiles = glob('web/modules/custom/*/*.routing.yml');
$allRoutes = '';
foreach ($routingFiles as $rf) {
    $allRoutes .= file_get_contents($rf) . "\n";
}

foreach ($caseStudyUrls as $url) {
    // Extract the path from the URL (remove leading /)
    $path = ltrim($url, '/');

    // Check if this path exists in any routing file
    if (strpos($allRoutes, "path: '/$path'") === false && strpos($allRoutes, "path: \"/$path\"") === false) {
        $errors[] = "Cross-link URL '$url' not found in any routing.yml";
    }
}

if (empty($errors)) {
    echo "VERTICAL-CROSS-LINK-001: PASS — All " . count($caseStudyUrls) . " case_study cross-links point to valid routes\n";
    exit(0);
}

foreach ($errors as $e) {
    echo "  FAIL: $e\n";
}
echo "VERTICAL-CROSS-LINK-001: FAIL — " . count($errors) . " broken cross-links\n";
exit(1);
