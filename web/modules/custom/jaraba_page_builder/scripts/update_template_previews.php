<?php

/**
 * @file
 * Script Drush para actualizar preview_image en las plantillas del Page Builder.
 *
 * Ejecutar con: lando drush scr modules/custom/jaraba_page_builder/scripts/update_template_previews.php
 */

use Drupal\Core\Config\ConfigFactoryInterface;

// Mapeo de template ID a archivo de imagen.
$template_previews = [
    'accordion_content' => 'modules/custom/jaraba_page_builder/images/previews/accordion-content.png',
    'agroconecta_content' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-content.png',
    'agroconecta_cta' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-cta.png',
    'agroconecta_faq' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-faq.png',
    'agroconecta_features' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-features.png',
    'agroconecta_gallery' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-gallery.png',
    'agroconecta_hero' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-hero.png',
    'agroconecta_map' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-map.png',
    'agroconecta_pricing' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-pricing.png',
    'agroconecta_social_proof' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-social-proof.png',
    'agroconecta_stats' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-stats.png',
    'agroconecta_testimonials' => 'modules/custom/jaraba_page_builder/images/previews/agroconecta-testimonials.png',
    'alert_banner' => 'modules/custom/jaraba_page_builder/images/previews/alert-banner.png',
    'animated_background' => 'modules/custom/jaraba_page_builder/images/previews/animated-background.png',
    'animated_beam' => 'modules/custom/jaraba_page_builder/images/previews/animated-beam.png',
    'animated_counter' => 'modules/custom/jaraba_page_builder/images/previews/animated-counter.png',
    'banner_strip' => 'modules/custom/jaraba_page_builder/images/previews/banner-strip.png',
    'bento_grid' => 'modules/custom/jaraba_page_builder/images/previews/bento-grid.png',
    'blockquote' => 'modules/custom/jaraba_page_builder/images/previews/blockquote.png',
    'blog_cards' => 'modules/custom/jaraba_page_builder/images/previews/blog-cards.png',
    'card_flip_3d' => 'modules/custom/jaraba_page_builder/images/previews/card-flip-3d.png',
    'cards_grid' => 'modules/custom/jaraba_page_builder/images/previews/cards-grid.png',
    'columns_layout' => 'modules/custom/jaraba_page_builder/images/previews/columns-layout.png',
    'comparison_slider' => 'modules/custom/jaraba_page_builder/images/previews/comparison-slider.png',
    'comparison_table' => 'modules/custom/jaraba_page_builder/images/previews/comparison-table.png',
    'contact_form' => 'modules/custom/jaraba_page_builder/images/previews/contact-form.png',
    'countdown_timer' => 'modules/custom/jaraba_page_builder/images/previews/countdown-timer.png',
    'course_catalog' => 'modules/custom/jaraba_page_builder/images/previews/course-catalog.png',
    'cta_section' => 'modules/custom/jaraba_page_builder/images/previews/cta-section.png',
    'divider_section' => 'modules/custom/jaraba_page_builder/images/previews/divider-section.png',
    'download_box' => 'modules/custom/jaraba_page_builder/images/previews/download-box.png',
    'event_calendar' => 'modules/custom/jaraba_page_builder/images/previews/event-calendar.png',
    'faq_accordion' => 'modules/custom/jaraba_page_builder/images/previews/faq-accordion.png',
    'feature_highlight' => 'modules/custom/jaraba_page_builder/images/previews/feature-highlight.png',
    'features_grid' => 'modules/custom/jaraba_page_builder/images/previews/features-grid.png',
    'floating_cards' => 'modules/custom/jaraba_page_builder/images/previews/floating-cards.png',
    'footer_section' => 'modules/custom/jaraba_page_builder/images/previews/footer-section.png',
    'glassmorphism_cards' => 'modules/custom/jaraba_page_builder/images/previews/glassmorphism-cards.png',
    'gradient_cards' => 'modules/custom/jaraba_page_builder/images/previews/gradient-cards.png',
    'hero_fullscreen' => 'modules/custom/jaraba_page_builder/images/previews/hero-fullscreen.png',
    'hover_glow_cards' => 'modules/custom/jaraba_page_builder/images/previews/hover-glow-cards.png',
    'how_it_works' => 'modules/custom/jaraba_page_builder/images/previews/how-it-works.png',
    'icon_cards' => 'modules/custom/jaraba_page_builder/images/previews/icon-cards.png',
    'image_gallery' => 'modules/custom/jaraba_page_builder/images/previews/image-gallery.png',
    'image_text_block' => 'modules/custom/jaraba_page_builder/images/previews/image-text-block.png',
    'job_search_hero' => 'modules/custom/jaraba_page_builder/images/previews/job-search-hero.png',
    'logo_grid' => 'modules/custom/jaraba_page_builder/images/previews/logo-grid.png',
    'map_locations' => 'modules/custom/jaraba_page_builder/images/previews/map-locations.png',
    'marquee_logos' => 'modules/custom/jaraba_page_builder/images/previews/marquee-logos.png',
    'newsletter_signup' => 'modules/custom/jaraba_page_builder/images/previews/newsletter-signup.png',
    'orbit_animation' => 'modules/custom/jaraba_page_builder/images/previews/orbit-animation.png',
    'parallax_hero' => 'modules/custom/jaraba_page_builder/images/previews/parallax-hero.png',
    'particle_hero' => 'modules/custom/jaraba_page_builder/images/previews/particle-hero.png',
    'partners_carousel' => 'modules/custom/jaraba_page_builder/images/previews/partners-carousel.png',
    'portfolio_gallery' => 'modules/custom/jaraba_page_builder/images/previews/portfolio-gallery.png',
    'pricing_table' => 'modules/custom/jaraba_page_builder/images/previews/pricing-table.png',
    'product_showcase' => 'modules/custom/jaraba_page_builder/images/previews/product-showcase.png',
    'profile_cards' => 'modules/custom/jaraba_page_builder/images/previews/profile-cards.png',
    'rich_text' => 'modules/custom/jaraba_page_builder/images/previews/rich-text.png',
    'scroll_reveal' => 'modules/custom/jaraba_page_builder/images/previews/scroll-reveal.png',
    'services_grid' => 'modules/custom/jaraba_page_builder/images/previews/services-grid.png',
    'social_feed' => 'modules/custom/jaraba_page_builder/images/previews/social-feed.png',
    'social_media' => 'modules/custom/jaraba_page_builder/images/previews/social-media.png',
    'split_hero' => 'modules/custom/jaraba_page_builder/images/previews/split-hero.png',
    'stats_counter' => 'modules/custom/jaraba_page_builder/images/previews/stats-counter.png',
    'steps_process' => 'modules/custom/jaraba_page_builder/images/previews/steps-process.png',
    'team_grid' => 'modules/custom/jaraba_page_builder/images/previews/team-grid.png',
    'testimonials_slider' => 'modules/custom/jaraba_page_builder/images/previews/testimonials-slider.png',
    'text_animation' => 'modules/custom/jaraba_page_builder/images/previews/text-animation.png',
    'text_section' => 'modules/custom/jaraba_page_builder/images/previews/text-section.png',
    'timeline' => 'modules/custom/jaraba_page_builder/images/previews/timeline.png',
    'two_columns' => 'modules/custom/jaraba_page_builder/images/previews/two-columns.png',
    'video_hero' => 'modules/custom/jaraba_page_builder/images/previews/video-hero.png',
    'video_section' => 'modules/custom/jaraba_page_builder/images/previews/video-section.png',
];

$config_factory = \Drupal::configFactory();
$updated_count = 0;
$skipped_count = 0;

foreach ($template_previews as $template_id => $preview_path) {
    $config_name = 'jaraba_page_builder.template.' . $template_id;
    $config = $config_factory->getEditable($config_name);

    if ($config->isNew()) {
        echo "SKIP: {$template_id} - Configuración no existe.\n";
        $skipped_count++;
        continue;
    }

    // Verificar si la imagen existe.
    $file_path = DRUPAL_ROOT . '/' . $preview_path;
    if (!file_exists($file_path)) {
        echo "WARN: {$template_id} - Imagen no existe: {$preview_path}\n";
    }

    // Actualizar preview_image.
    $config->set('preview_image', $preview_path);
    $config->save();

    echo "OK: {$template_id} -> {$preview_path}\n";
    $updated_count++;
}

echo "\n========================================\n";
echo "Actualizados: {$updated_count}\n";
echo "Omitidos: {$skipped_count}\n";
echo "========================================\n";
