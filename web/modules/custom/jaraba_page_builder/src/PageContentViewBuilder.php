<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder para entidades PageContent.
 *
 * PROPÓSITO:
 * Renderiza páginas según su layout_mode:
 * - legacy: usa template_id + content_data como antes
 * - multiblock: itera sobre sections[] y renderiza cada bloque
 *
 * INTEGRACIÓN:
 * Se registra en PageContent::baseFieldDefinitions() mediante
 * la anotación 'view_builder' en la clase de entidad.
 *
 * @package Drupal\jaraba_page_builder
 */
class PageContentViewBuilder extends EntityViewBuilder
{

    /**
     * {@inheritdoc}
     */
    public function build(array $build): array
    {
        $build = parent::build($build);

        return $build;
    }

    /**
     * {@inheritdoc}
     */
    public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL)
    {
        /** @var \Drupal\jaraba_page_builder\PageContentInterface $entity */
        $build = parent::view($entity, $view_mode, $langcode);

        // Prioridad 1: Contenido del Canvas Editor (GrapesJS)
        $canvasData = $entity->get('canvas_data')->value ?? '{}';
        $hasCanvasContent = !empty($canvasData) && $canvasData !== '{}';

        if ($hasCanvasContent) {
            $build = $this->buildCanvasView($entity, $build);
        } elseif ($entity->isMultiBlock()) {
            // Prioridad 2: Multi-block sections
            $build = $this->buildMultiBlockView($entity, $build);
        } else {
            // Prioridad 3: Legacy template
            $build = $this->buildLegacyView($entity, $build);
        }

        // Añadir librería CSS del Page Builder
        $build['#attached']['library'][] = 'jaraba_page_builder/global';

        return $build;
    }

    /**
     * Construye la vista para páginas con contenido del Canvas Editor (GrapesJS).
     *
     * Renderiza el HTML generado por el editor visual, incluyendo los estilos CSS
     * personalizados que el usuario haya definido en el canvas.
     *
     * @param \Drupal\jaraba_page_builder\PageContentInterface $entity
     *   La entidad de página.
     * @param array $build
     *   El array de renderizado base.
     *
     * @return array
     *   El array de renderizado modificado.
     */
    protected function buildCanvasView(PageContentInterface $entity, array $build): array
    {
        // Prioridad 1: HTML pre-renderizado (campo dedicado)
        $renderedHtml = $entity->get('rendered_html')->value ?? '';

        // Prioridad 2: Extraer HTML de canvas_data JSON
        $canvasDataRaw = $entity->get('canvas_data')->value ?? '{}';
        $canvasData = json_decode($canvasDataRaw, TRUE) ?: [];

        if (empty($renderedHtml)) {
            $renderedHtml = $canvasData['html'] ?? '';
        }

        // FIX C2: Sanitizar HTML con lista blanca ampliada para Page Builder.
        // Xss::filterAdmin() eliminaba svg, form, input, button, video, iframe, canvas, picture
        // que son necesarios para los bloques interactivos y premium.
        $renderedHtml = $this->sanitizePageBuilderHtml($renderedHtml);

        if (!empty($renderedHtml)) {
            // Renderizar el HTML del canvas
            $build['content']['canvas_html'] = [
                '#type' => 'inline_template',
                '#template' => '<div class="canvas-content">{{ content|raw }}</div>',
                '#context' => ['content' => $renderedHtml],
                '#weight' => 0,
            ];

            // Inyectar CSS personalizado del canvas si existe
            $css = $canvasData['css'] ?? '';
            if (!empty($css)) {
                // AUDIT-SEC-003: Sanitizar CSS para prevenir inyección.
                $css = $this->sanitizeCss($css);
                $build['#attached']['html_head'][] = [
                    [
                        '#tag' => 'style',
                        '#value' => $css,
                        '#attributes' => ['data-canvas-styles' => 'true'],
                    ],
                    'canvas-custom-styles',
                ];
            }
        } else {
            // Fallback: mostrar mensaje si no hay contenido
            $build['content']['empty_canvas'] = [
                '#markup' => '<div class="canvas-content canvas-content--empty"><p>' . $this->t('Esta página está vacía. Edítala en el Canvas Editor.') . '</p></div>',
                '#weight' => 0,
            ];
        }

        $build['#attributes']['class'][] = 'page-content';
        $build['#attributes']['class'][] = 'page-content--canvas';
        $build['#attributes']['data-page-id'] = $entity->id();

        // Añadir librería CSS específica del Page Builder
        $build['#attached']['library'][] = 'jaraba_page_builder/page-builder';

        return $build;
    }

    /**
     * Construye la vista para páginas legacy (un solo template).
     *
     * @param \Drupal\jaraba_page_builder\PageContentInterface $entity
     *   La entidad de página.
     * @param array $build
     *   El array de renderizado base.
     *
     * @return array
     *   El array de renderizado modificado.
     */
    protected function buildLegacyView(PageContentInterface $entity, array $build): array
    {
        $template_id = $entity->get('template_id')->value ?? '';
        $content_data_raw = $entity->get('content_data')->value ?? '{}';
        $content_data = json_decode($content_data_raw, TRUE) ?: [];

        // AUDIT-SEC-003: Sanitizar campos HTML antes de pasarlos a Twig |raw.
        $content_data = $this->sanitizeContentData($content_data);

        if (!empty($template_id)) {
            $build['content']['section_0'] = [
                '#theme' => 'page_builder_block__' . $template_id,
                '#content' => $content_data,
                '#template_id' => $template_id,
                '#page' => $entity,
                '#weight' => 0,
            ];
        }

        $build['#attributes']['class'][] = 'page-content';
        $build['#attributes']['class'][] = 'page-content--legacy';

        return $build;
    }

    /**
     * Construye la vista para páginas multi-block.
     *
     * @param \Drupal\jaraba_page_builder\PageContentInterface $entity
     *   La entidad de página.
     * @param array $build
     *   El array de renderizado base.
     *
     * @return array
     *   El array de renderizado modificado.
     */
    protected function buildMultiBlockView(PageContentInterface $entity, array $build): array
    {
        $sections = $entity->getSectionsSorted();

        $build['content']['sections'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['page-content__sections'],
            ],
        ];

        foreach ($sections as $index => $section) {
            // Saltar secciones ocultas
            if (!($section['visible'] ?? TRUE)) {
                continue;
            }

            $template_id = $section['template_id'] ?? '';
            $content = $section['content'] ?? [];
            $uuid = $section['uuid'] ?? '';

            // AUDIT-SEC-003: Sanitizar campos HTML antes de pasarlos a Twig |raw.
            $content = $this->sanitizeContentData($content);

            if (empty($template_id)) {
                continue;
            }

            $build['content']['sections'][$uuid] = [
                '#theme' => 'page_builder_block__' . $template_id,
                '#content' => $content,
                '#template_id' => $template_id,
                '#section_uuid' => $uuid,
                '#section_weight' => $section['weight'] ?? $index,
                '#page' => $entity,
                '#weight' => $section['weight'] ?? $index,
                '#wrapper_attributes' => [
                    'class' => ['page-section'],
                    'data-section-uuid' => $uuid,
                    'data-template-id' => $template_id,
                ],
            ];
        }

        $build['#attributes']['class'][] = 'page-content';
        $build['#attributes']['class'][] = 'page-content--multiblock';
        $build['#attributes']['data-page-id'] = $entity->id();
        $build['#attributes']['data-sections-count'] = count($sections);

        return $build;
    }

    /**
     * {@inheritdoc}
     */
    public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL)
    {
        $build = parent::viewMultiple($entities, $view_mode, $langcode);

        return $build;
    }

    /**
     * Sanitiza recursivamente campos HTML en arrays de contenido.
     *
     * AUDIT-SEC-003: Los templates Twig usan |raw para campos como
     * description, content, html, body. Estos campos DEBEN sanitizarse
     * server-side con Xss::filterAdmin() antes de llegar al template.
     *
     * @param array $data
     *   Array de contenido (content_data o section content).
     *
     * @return array
     *   Array con campos HTML sanitizados.
     */
    protected function sanitizeContentData(array $data): array
    {
        $htmlFields = ['description', 'content', 'html', 'body', 'text', 'summary', 'rendered'];

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeContentData($value);
            } elseif (is_string($value) && in_array($key, $htmlFields, TRUE)) {
                $sanitized[$key] = $this->sanitizePageBuilderHtml($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitiza HTML con lista blanca ampliada para el Page Builder.
     *
     * FIX C2: Xss::filterAdmin() eliminaba etiquetas necesarias para bloques
     * premium e interactivos (svg, form, input, button, video, iframe, canvas, picture).
     * Este método permite esas etiquetas pero elimina vectores XSS peligrosos:
     * scripts, event handlers (on*), y URLs javascript:.
     *
     * @param string $html
     *   HTML a sanitizar.
     *
     * @return string
     *   HTML sanitizado con etiquetas multimedia/interactivas preservadas.
     */
    protected function sanitizePageBuilderHtml(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Eliminar <script> tags y su contenido.
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

        // Eliminar event handlers on* (onclick, onerror, onload, etc.).
        $html = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // Eliminar atributos javascript: en href/src/action/data/formaction.
        $html = preg_replace('/\s+(href|src|action|data|formaction)\s*=\s*(?:"javascript:[^"]*"|\'javascript:[^\']*\')/i', '', $html);

        // Eliminar <object> y <embed> (vectores Flash/plugin).
        $html = preg_replace('/<object\b[^>]*>.*?<\/object>/is', '', $html);
        $html = preg_replace('/<embed\b[^>]*\/?>/i', '', $html);

        return $html;
    }

    /**
     * Sanitiza CSS para prevenir inyección de código.
     *
     * AUDIT-SEC-003: CSS personalizado del canvas puede contener vectores XSS
     * como javascript:, expression(), @import con URLs maliciosas, o behavior:.
     *
     * @param string $css
     *   CSS a sanitizar.
     *
     * @return string
     *   CSS sanitizado.
     */
    protected function sanitizeCss(string $css): string
    {
        $css = preg_replace('/javascript\s*:/i', '', $css);
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/@import\b/i', '', $css);
        $css = preg_replace('/behavior\s*:/i', '', $css);
        $css = preg_replace('/-moz-binding\s*:/i', '', $css);

        return $css;
    }

}
