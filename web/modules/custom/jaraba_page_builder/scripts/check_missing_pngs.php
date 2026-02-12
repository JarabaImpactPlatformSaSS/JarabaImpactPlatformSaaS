<?php
/**
 * Verificar qué templates no tienen PNG.
 */

$templates = \Drupal::entityTypeManager()
    ->getStorage('page_template')
    ->loadMultiple();

$preview_dir = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/images/previews/';

echo "Total templates: " . count($templates) . "\n";
$missing = [];

foreach ($templates as $t) {
    $id = $t->id();
    $filename = str_replace('_', '-', $id) . '.png';
    if (!file_exists($preview_dir . $filename)) {
        $missing[] = $id;
    }
}

echo "Missing PNG: " . count($missing) . "\n";
foreach ($missing as $m) {
    echo "  - $m\n";
}
