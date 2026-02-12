<?php
/**
 * Script para auditar todos los templates del Page Builder.
 * 
 * Verifica:
 * - Existencia del archivo Twig
 * - Existencia del archivo PNG de miniatura
 * - Estado general del template
 */

$templates = \Drupal::entityTypeManager()
    ->getStorage('page_template')
    ->loadMultiple();

$preview_dir = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/images/previews/';
$templates_dir = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/templates/blocks/';

$results = [];

foreach ($templates as $template) {
    $id = $template->id();
    $label = $template->label();
    $twig_path = $template->getTwigTemplate();
    $category = $template->getCategory() ?? 'unknown';

    // Check PNG
    $png_filename = str_replace('_', '-', $id) . '.png';
    $png_exists = file_exists($preview_dir . $png_filename);

    // Check Twig - extract actual path from @jaraba_page_builder/...
    $twig_exists = false;
    if (!empty($twig_path)) {
        // Convert @jaraba_page_builder/blocks/xxx/yyy.html.twig to actual path
        $actual_twig = str_replace('@jaraba_page_builder/', '', $twig_path);
        $full_twig_path = $templates_dir . $actual_twig;
        // Fix blocks/ duplication if present
        $full_twig_path = str_replace('blocks/blocks/', 'blocks/', $full_twig_path);
        $full_twig_path = str_replace('/blocks/../', '/blocks/', $full_twig_path);
        $twig_exists = file_exists($full_twig_path);

        // Log actual path for debugging
        if (!$twig_exists) {
            // Try alternative paths
            $alt_path = DRUPAL_ROOT . '/modules/custom/jaraba_page_builder/templates/' . $actual_twig;
            $twig_exists = file_exists($alt_path);
        }
    }

    $results[] = [
        'id' => $id,
        'label' => $label,
        'category' => $category,
        'twig' => $twig_path,
        'twig_exists' => $twig_exists ? 'YES' : 'NO',
        'png_exists' => $png_exists ? 'YES' : 'NO',
    ];
}

// Sort by category, then by id
usort($results, function ($a, $b) {
    $cat_cmp = strcmp($a['category'], $b['category']);
    if ($cat_cmp !== 0)
        return $cat_cmp;
    return strcmp($a['id'], $b['id']);
});

// Output results
echo "=== TEMPLATE AUDIT REPORT ===" . PHP_EOL;
echo "Total templates: " . count($results) . PHP_EOL . PHP_EOL;

$missing_twig = [];
$missing_png = [];

echo "| ID | LABEL | CATEGORY | TWIG | PNG |" . PHP_EOL;
echo "|---|---|---|---|---|" . PHP_EOL;

foreach ($results as $r) {
    $status = ($r['twig_exists'] === 'NO' || $r['png_exists'] === 'NO') ? '⚠️' : '✅';
    echo "| {$r['id']} | {$r['label']} | {$r['category']} | {$r['twig_exists']} | {$r['png_exists']} |" . PHP_EOL;

    if ($r['twig_exists'] === 'NO') {
        $missing_twig[] = $r['id'] . ' => ' . $r['twig'];
    }
    if ($r['png_exists'] === 'NO') {
        $missing_png[] = $r['id'];
    }
}

echo PHP_EOL . "=== MISSING TWIG FILES (" . count($missing_twig) . ") ===" . PHP_EOL;
foreach ($missing_twig as $m) {
    echo "  - $m" . PHP_EOL;
}

echo PHP_EOL . "=== MISSING PNG FILES (" . count($missing_png) . ") ===" . PHP_EOL;
foreach ($missing_png as $m) {
    echo "  - $m" . PHP_EOL;
}
