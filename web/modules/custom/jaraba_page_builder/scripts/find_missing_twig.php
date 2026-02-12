<?php
/**
 * Script simplificado para detectar templates sin Twig.
 */

$templates = \Drupal::entityTypeManager()
    ->getStorage('page_template')
    ->loadMultiple();

$templates_base = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/templates/';

$missing_twig = [];
$missing_preview = [];

foreach ($templates as $t) {
    $id = $t->id();
    $twig = $t->getTwigTemplate();

    if (!empty($twig)) {
        // @jaraba_page_builder/blocks/xxx/yyy.html.twig => templates/blocks/xxx/yyy.html.twig
        $relative = str_replace('@jaraba_page_builder/', '', $twig);
        $full_path = $templates_base . $relative;

        if (!file_exists($full_path)) {
            $missing_twig[$id] = [
                'twig' => $twig,
                'expected_path' => $full_path,
            ];
        }
    }
}

echo "=== TEMPLATES SIN ARCHIVO TWIG (" . count($missing_twig) . ") ===" . PHP_EOL;
foreach ($missing_twig as $id => $info) {
    echo "- $id" . PHP_EOL;
    echo "  Template: " . $info['twig'] . PHP_EOL;
    echo "  Expected: " . $info['expected_path'] . PHP_EOL;
}
