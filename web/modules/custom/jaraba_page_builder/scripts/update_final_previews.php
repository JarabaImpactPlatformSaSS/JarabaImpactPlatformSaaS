<?php

/**
 * @file
 * Script Drush para actualizar preview_image de las últimas 7 plantillas.
 *
 * Ejecutar con: lando drush scr modules/custom/jaraba_page_builder/scripts/update_final_previews.php
 */

$config_factory = \Drupal::configFactory();

// Mapeo de las 7 plantillas finales.
$final_previews = [
    'split_screen' => '/modules/custom/jaraba_page_builder/images/previews/split-screen.png',
    'testimonials_carousel' => '/modules/custom/jaraba_page_builder/images/previews/testimonials-carousel.png',
    'text_gradient' => '/modules/custom/jaraba_page_builder/images/previews/text-gradient.png',
    'typewriter_text' => '/modules/custom/jaraba_page_builder/images/previews/typewriter-text.png',
    'footer_section' => '/modules/custom/jaraba_page_builder/images/previews/footer-section.png',
    'video_embed' => '/modules/custom/jaraba_page_builder/images/previews/video-embed.png',
    'social_proof' => '/modules/custom/jaraba_page_builder/images/previews/social-proof.png',
];

echo "=== Actualizando preview_image de 7 plantillas finales ===\n\n";

$updated = 0;
$skipped = 0;

foreach ($final_previews as $template_id => $preview_path) {
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
