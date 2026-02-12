<?php

/**
 * @file
 * Script Drush para actualizar preview_image de las plantillas recién generadas.
 *
 * Ejecutar con: lando drush scr modules/custom/jaraba_page_builder/scripts/update_new_previews.php
 */

$config_factory = \Drupal::configFactory();

// Mapeo de las nuevas plantillas a sus imágenes.
$new_previews = [
    'tabs_content' => '/modules/custom/jaraba_page_builder/images/previews/tabs-content.png',
    'floating_cards' => '/modules/custom/jaraba_page_builder/images/previews/floating-cards.png',
    'glassmorphism_cards' => '/modules/custom/jaraba_page_builder/images/previews/glassmorphism-cards.png',
    'gradient_cards' => '/modules/custom/jaraba_page_builder/images/previews/gradient-cards.png',
    'hover_glow_cards' => '/modules/custom/jaraba_page_builder/images/previews/hover-glow-cards.png',
    'how_it_works' => '/modules/custom/jaraba_page_builder/images/previews/how-it-works.png',
    'icon_cards' => '/modules/custom/jaraba_page_builder/images/previews/icon-cards.png',
    'feature_highlight' => '/modules/custom/jaraba_page_builder/images/previews/feature-highlight.png',
    'spotlight_grid' => '/modules/custom/jaraba_page_builder/images/previews/spotlight-grid.png',
    'spotlight_text' => '/modules/custom/jaraba_page_builder/images/previews/spotlight-text.png',
    'sticky_scroll' => '/modules/custom/jaraba_page_builder/images/previews/sticky-scroll.png',
    // También actualizar las existentes que no tenían el / inicial.
    'text_gradient' => '/modules/custom/jaraba_page_builder/images/previews/text-gradient.png',
    'typewriter_text' => '/modules/custom/jaraba_page_builder/images/previews/typewriter-text.png',
    'video_embed' => '/modules/custom/jaraba_page_builder/images/previews/video-embed.png',
    'split_screen' => '/modules/custom/jaraba_page_builder/images/previews/split-screen.png',
    'social_proof' => '/modules/custom/jaraba_page_builder/images/previews/social-proof.png',
    'testimonials_carousel' => '/modules/custom/jaraba_page_builder/images/previews/testimonials-carousel.png',
];

echo "=== Actualizando preview_image de nuevas plantillas ===\n\n";

$updated = 0;
$skipped = 0;

foreach ($new_previews as $template_id => $preview_path) {
    $config_name = 'jaraba_page_builder.template.' . $template_id;
    $config = $config_factory->getEditable($config_name);

    if ($config->isNew()) {
        echo "SKIP: {$template_id} - Config no existe\n";
        $skipped++;
        continue;
    }

    $config->set('preview_image', $preview_path);
    $config->save();

    echo "OK: {$template_id} -> {$preview_path}\n";
    $updated++;
}

echo "\n=== Resumen ===\n";
echo "Actualizadas: {$updated}\n";
echo "Omitidas: {$skipped}\n";
