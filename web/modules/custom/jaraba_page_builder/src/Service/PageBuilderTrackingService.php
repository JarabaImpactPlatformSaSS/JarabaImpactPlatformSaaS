<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Servicio de Tracking integrado para Page Builder.
 *
 * ESPECIFICACIÓN: Doc 167 - Platform_Analytics_PageBuilder_v1
 *
 * Funcionalidades:
 * - Generar eventos GA4 por tipo de bloque
 * - DataLayer push automático
 * - Tracking de CTAs, forms, scroll depth
 * - Métricas de rendimiento por página
 *
 * @package Drupal\jaraba_page_builder\Service
 */
class PageBuilderTrackingService
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Route match.
     *
     * @var \Drupal\Core\Routing\RouteMatchInterface
     */
    protected RouteMatchInterface $routeMatch;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        RouteMatchInterface $route_match
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->routeMatch = $route_match;
    }

    /**
     * Genera configuración de tracking para una página.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return array
     *   Configuración de tracking.
     */
    public function getPageTrackingConfig(int $pageId): array
    {
        try {
            $page = $this->entityTypeManager->getStorage('page_content')->load($pageId);
            if (!$page) {
                return [];
            }

            $templateId = $page->get('template_id')->value ?? '';
            $contentData = json_decode($page->get('content_data')->value ?? '{}', TRUE);

            return [
                'page_id' => $pageId,
                'template_id' => $templateId,
                'page_type' => $this->mapTemplateToPageType($templateId),
                'blocks' => $this->extractBlocksForTracking($contentData),
                'events' => $this->generateEventConfig($templateId, $contentData),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Mapea template ID a tipo de página para GA4.
     *
     * @param string $templateId
     *   Template ID.
     *
     * @return string
     *   Tipo de página para analytics.
     */
    protected function mapTemplateToPageType(string $templateId): string
    {
        $mapping = [
            // Landings.
            'landing_main' => 'landing_page',
            'landing_vertical' => 'vertical_landing',
            'emp_landing_main' => 'employment_landing',
            'agro_landing_main' => 'agriculture_landing',
            'com_landing_main' => 'commerce_landing',
            'srv_landing_main' => 'services_landing',
            'ent_landing_main' => 'entrepreneurship_landing',
            // Productos/Servicios.
            'service_detail' => 'service_page',
            'product_detail' => 'product_page',
            // Empleos/Cursos.
            'job_detail' => 'job_listing',
            'course_detail' => 'course_page',
            // Blog.
            'blog_post' => 'article',
            // Informativas.
            'about' => 'about_page',
            'gen_about' => 'about_page',
            'contact' => 'contact_page',
            'gen_contact' => 'contact_page',
            'faq' => 'faq_page',
            'gen_faq' => 'faq_page',
        ];

        return $mapping[$templateId] ?? 'content_page';
    }

    /**
     * Extrae bloques para tracking.
     *
     * @param array $contentData
     *   Datos de contenido.
     *
     * @return array
     *   Lista de bloques con metadata de tracking.
     */
    protected function extractBlocksForTracking(array $contentData): array
    {
        $blocks = [];

        if (!isset($contentData['blocks']) || !is_array($contentData['blocks'])) {
            return $blocks;
        }

        foreach ($contentData['blocks'] as $index => $block) {
            $blockType = $block['type'] ?? 'unknown';
            $blocks[] = [
                'index' => $index,
                'type' => $blockType,
                'category' => $this->getBlockCategory($blockType),
                'track_visibility' => $this->shouldTrackVisibility($blockType),
                'track_interaction' => $this->shouldTrackInteraction($blockType),
            ];
        }

        return $blocks;
    }

    /**
     * Obtiene categoría de un bloque.
     *
     * @param string $blockType
     *   Tipo de bloque.
     *
     * @return string
     *   Categoría del bloque.
     */
    protected function getBlockCategory(string $blockType): string
    {
        $categories = [
            // Hero blocks.
            'hero_fullscreen' => 'hero',
            'hero_split' => 'hero',
            'video_hero' => 'hero',
            'job_search_hero' => 'hero',
            // Feature blocks.
            'features_grid' => 'features',
            'icon_cards' => 'features',
            'services_grid' => 'features',
            // CTA blocks.
            'cta_section' => 'cta',
            'alert_banner' => 'cta',
            'banner_strip' => 'cta',
            // Form blocks.
            'newsletter_signup' => 'form',
            'contact_form' => 'form',
            // Content blocks.
            'rich_text' => 'content',
            'blog_cards' => 'content',
            'faq_accordion' => 'content',
            // Social proof.
            'testimonials_slider' => 'social_proof',
            'testimonials_3d' => 'social_proof',
            'logo_grid' => 'social_proof',
            // Pricing.
            'pricing_table' => 'pricing',
        ];

        return $categories[$blockType] ?? 'content';
    }

    /**
     * Determina si un bloque debe trackear visibilidad.
     *
     * @param string $blockType
     *   Tipo de bloque.
     *
     * @return bool
     *   TRUE si debe trackear visibilidad.
     */
    protected function shouldTrackVisibility(string $blockType): bool
    {
        $trackableTypes = [
            'cta_section',
            'pricing_table',
            'newsletter_signup',
            'testimonials_slider',
            'testimonials_3d',
            'features_grid',
        ];

        return in_array($blockType, $trackableTypes, TRUE);
    }

    /**
     * Determina si un bloque debe trackear interacción.
     *
     * @param string $blockType
     *   Tipo de bloque.
     *
     * @return bool
     *   TRUE si debe trackear interacción.
     */
    protected function shouldTrackInteraction(string $blockType): bool
    {
        $interactiveTypes = [
            'cta_section',
            'alert_banner',
            'banner_strip',
            'newsletter_signup',
            'contact_form',
            'faq_accordion',
            'tabs_content',
            'accordion_content',
            'pricing_table',
        ];

        return in_array($blockType, $interactiveTypes, TRUE);
    }

    /**
     * Genera configuración de eventos.
     *
     * @param string $templateId
     *   Template ID.
     * @param array $contentData
     *   Datos de contenido.
     *
     * @return array
     *   Configuración de eventos.
     */
    protected function generateEventConfig(string $templateId, array $contentData): array
    {
        $events = [];

        // Evento de page_view siempre.
        $events['page_view'] = [
            'event' => 'page_view',
            'page_type' => $this->mapTemplateToPageType($templateId),
            'template_id' => $templateId,
        ];

        // Eventos específicos por tipo de plantilla.
        switch ($templateId) {
            case 'job_detail':
                $events['view_job'] = [
                    'event' => 'view_job_listing',
                    'job_title' => $contentData['title'] ?? '',
                    'job_company' => $contentData['company'] ?? '',
                    'job_location' => $contentData['location'] ?? '',
                ];
                break;

            case 'course_detail':
                $events['view_course'] = [
                    'event' => 'view_course',
                    'course_name' => $contentData['title'] ?? '',
                    'course_price' => $contentData['price'] ?? 0,
                ];
                break;

            case 'product_detail':
                $events['view_product'] = [
                    'event' => 'view_item',
                    'item_name' => $contentData['title'] ?? '',
                    'item_category' => $contentData['category'] ?? '',
                    'price' => $contentData['price'] ?? 0,
                ];
                break;

            case 'service_detail':
                $events['view_service'] = [
                    'event' => 'view_service',
                    'service_name' => $contentData['title'] ?? '',
                    'service_category' => $contentData['category'] ?? '',
                ];
                break;
        }

        // Evento de scroll depth.
        $events['scroll_tracking'] = [
            'thresholds' => [25, 50, 75, 90],
        ];

        return $events;
    }

    /**
     * Genera script de dataLayer para inyectar en la página.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return string
     *   JavaScript para inyectar.
     */
    public function generateDataLayerScript(int $pageId): string
    {
        $config = $this->getPageTrackingConfig($pageId);

        if (empty($config)) {
            return '';
        }

        $configJson = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<JS
<script>
  window.dataLayer = window.dataLayer || [];
  window.jarabaPageAnalytics = {$configJson};
  dataLayer.push({
    'event': 'page_builder_view',
    'page_type': '{$config['page_type']}',
    'template_id': '{$config['template_id']}',
    'page_id': {$pageId}
  });
</script>
JS;
    }

    /**
     * Genera atributos de tracking para bloques.
     *
     * @param string $blockType
     *   Tipo de bloque.
     * @param int $blockIndex
     *   Índice del bloque.
     *
     * @return array
     *   Array de atributos HTML.
     */
    public function getBlockTrackingAttributes(string $blockType, int $blockIndex): array
    {
        $category = $this->getBlockCategory($blockType);
        $trackVisibility = $this->shouldTrackVisibility($blockType);
        $trackInteraction = $this->shouldTrackInteraction($blockType);

        $attributes = [
            'data-analytics-block' => $blockType,
            'data-analytics-index' => (string) $blockIndex,
            'data-analytics-category' => $category,
        ];

        if ($trackVisibility) {
            $attributes['data-analytics-track-visibility'] = 'true';
        }

        if ($trackInteraction) {
            $attributes['data-analytics-track-interaction'] = 'true';
        }

        return $attributes;
    }

    /**
     * Registra un evento de Analytics.
     *
     * @param string $event
     *   Nombre del evento.
     * @param array $data
     *   Datos del evento.
     * @param int|null $pageId
     *   ID de la página (opcional).
     *
     * @return bool
     *   TRUE si se registró correctamente.
     */
    public function logEvent(string $event, array $data = [], ?int $pageId = NULL): bool
    {
        try {
            \Drupal::logger('jaraba_page_builder.analytics')->info('Analytics event: @event', [
                '@event' => $event,
                '@data' => json_encode($data),
                '@page_id' => $pageId,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

}
