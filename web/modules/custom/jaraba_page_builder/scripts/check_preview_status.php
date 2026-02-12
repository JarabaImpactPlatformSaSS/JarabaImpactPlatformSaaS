<?php

/**
 * Script para verificar preview_image de templates.
 */

$templates = \Drupal::entityTypeManager()
    ->getStorage('page_template')
    ->loadMultiple();

$no_preview = [];
$wrong_path = [];
$ok = [];

foreach ($templates as $t) {
    $img = $t->getPreviewImage();
    if (empty($img)) {
        $no_preview[] = $t->id();
    } elseif (!file_exists(DRUPAL_ROOT . $img)) {
        $wrong_path[$t->id()] = $img;
    } else {
        $ok[] = $t->id();
    }
}

echo "=== RESUMEN preview_image ===\n\n";
echo "✅ Con imagen OK: " . count($ok) . "\n";
echo "❌ Sin preview_image: " . count($no_preview) . "\n";
echo "⚠️  Path incorrecto/no existe: " . count($wrong_path) . "\n\n";

if ($no_preview) {
    echo "Templates sin preview_image:\n";
    echo implode(", ", $no_preview) . "\n\n";
}

if ($wrong_path) {
    echo "Templates con path incorrecto:\n";
    foreach ($wrong_path as $id => $path) {
        echo "  - $id => $path\n";
    }
}
