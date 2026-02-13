<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\jaraba_page_builder\Entity\PageTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador API para el Canvas Editor GrapesJS.
 *
 * Endpoints para persistencia del contenido del canvas visual:
 * - GET /api/v1/pages/{id}/canvas - Obtener contenido del canvas
 * - PATCH /api/v1/pages/{id}/canvas - Guardar contenido del canvas
 * - GET /api/v1/page-builder/blocks - Listar bloques disponibles
 *
 * @see docs/tecnicos/20260204b-Canvas_Editor_v3_Arquitectura_Maestra.md
 */
class CanvasApiController extends ControllerBase
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->entityTypeManager = $container->get('entity_type.manager');
        return $instance;
    }

    /**
     * GET /api/v1/pages/{page_content}/canvas
     *
     * Obtiene el contenido del canvas GrapesJS para una página.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $page_content
     *   La entidad de página.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con componentes y estilos del canvas.
     */
    public function getCanvas(ContentEntityInterface $page_content): JsonResponse
    {
        try {
            // Obtener datos del canvas almacenados.
            $canvasData = [];

            // El campo canvas_data almacena JSON con componentes y estilos.
            if ($page_content->hasField('canvas_data') && !$page_content->get('canvas_data')->isEmpty()) {
                $canvasData = json_decode($page_content->get('canvas_data')->value, TRUE) ?? [];
            }

            // Si no hay datos del canvas, generar desde secciones existentes.
            if (empty($canvasData)) {
                $canvasData = $this->generateCanvasFromSections($page_content);
            }

            return new JsonResponse([
                'components' => $canvasData['components'] ?? [],
                'styles' => $canvasData['styles'] ?? [],
                'html' => $canvasData['html'] ?? '',
                'css' => $canvasData['css'] ?? '',
            ]);

        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->error(
                'Error obteniendo canvas para página @id: @error',
                ['@id' => $page_content->id(), '@error' => $e->getMessage()]
            );

            return new JsonResponse(
                ['error' => 'Error al obtener el canvas'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * PATCH /api/v1/pages/{page_content}/canvas
     *
     * Guarda el contenido del canvas GrapesJS.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $page_content
     *   La entidad de página.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP con JSON body.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta con estado del guardado.
     */
    public function saveCanvas(ContentEntityInterface $page_content, Request $request): JsonResponse
    {
        try {
            // Parsear JSON del body.
            $data = json_decode($request->getContent(), TRUE);

            if (empty($data)) {
                return new JsonResponse(
                    ['error' => 'Datos inválidos'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validar estructura mínima.
            // AUDIT-SEC-003: Sanitizar HTML y CSS ANTES de almacenar.
            $canvasData = [
                'components' => $data['components'] ?? [],
                'styles' => $data['styles'] ?? [],
                'html' => Xss::filterAdmin($data['html'] ?? ''),
                'css' => $this->sanitizeCss($data['css'] ?? ''),
                'updated_at' => date('c'),
            ];

            // Almacenar en el campo canvas_data si existe.
            if ($page_content->hasField('canvas_data')) {
                $page_content->set('canvas_data', json_encode($canvasData, JSON_UNESCAPED_UNICODE));
            }

            // También actualizar el HTML renderizado para el frontend público.
            if ($page_content->hasField('rendered_html') && !empty($data['html'])) {
                $page_content->set('rendered_html', $this->sanitizeHtml($data['html']));
            }

            // Guardar con nueva revisión (si la entidad lo soporta).
            if (method_exists($page_content, 'setNewRevision')) {
                $page_content->setNewRevision(TRUE);
            }
            // Solo llamar a métodos de revisión si la entidad implementa RevisionLogInterface.
            if ($page_content instanceof \Drupal\Core\Entity\RevisionLogInterface) {
                $page_content->setRevisionLogMessage('Auto-guardado desde Canvas Editor v3');
                $page_content->setRevisionCreationTime(\Drupal::time()->getRequestTime());
            }
            $page_content->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => 'Canvas guardado correctamente',
                'revision_id' => $page_content->getRevisionId(),
            ]);

        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->error(
                'Error guardando canvas para página @id: @error',
                ['@id' => $page_content->id(), '@error' => $e->getMessage()]
            );

            return new JsonResponse(
                ['error' => 'Error al guardar el canvas: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET /api/v1/page-builder/blocks
     *
     * Lista todos los bloques disponibles para GrapesJS.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request HTTP con query params (tenant, vertical).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con array de bloques.
     */
    public function listBlocks(Request $request): JsonResponse
    {
        try {
            // PageTemplate es ConfigEntity global, no se filtra por tenant.
            // Todos los templates están disponibles para todos los tenants.
            $storage = $this->entityTypeManager->getStorage('page_template');
            $query = $storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('status', TRUE)
                ->sort('category', 'ASC')
                ->sort('weight', 'ASC');

            $ids = $query->execute();
            $templates = $storage->loadMultiple($ids);

            // Convertir a formato GrapesJS.
            $blocks = [];
            foreach ($templates as $template) {
                $blocks[] = $this->templateToGrapesJSBlock($template);
            }

            return new JsonResponse($blocks);

        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->error(
                'Error listando bloques: @error',
                ['@error' => $e->getMessage()]
            );

            return new JsonResponse(
                ['error' => 'Error al listar bloques'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Genera contenido de canvas inicial desde las secciones existentes.
     *
     * Si no hay secciones pero hay un template_id asignado, pre-carga
     * el HTML del template para que el usuario comience con contenido.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $page_content
     *   La entidad de página.
     *
     * @return array
     *   Datos del canvas (components, styles, html, css).
     */
    protected function generateCanvasFromSections(ContentEntityInterface $page_content): array
    {
        $components = [];
        $html = '';

        // Obtener secciones si el campo existe.
        if ($page_content->hasField('sections') && !$page_content->get('sections')->isEmpty()) {
            foreach ($page_content->get('sections') as $sectionItem) {
                $sectionData = json_decode($sectionItem->value, TRUE);
                if ($sectionData) {
                    // Convertir cada sección a componente GrapesJS.
                    $components[] = [
                        'type' => 'jaraba-section',
                        'attributes' => [
                            'data-block-id' => $sectionData['template_id'] ?? '',
                            'data-section-uuid' => $sectionData['uuid'] ?? '',
                        ],
                        'components' => $this->sectionToComponents($sectionData),
                    ];
                }
            }
        }

        // Si no hay secciones pero hay un template_id, pre-cargar HTML del template.
        if (empty($components) && $page_content->hasField('template_id')) {
            $templateId = $page_content->get('template_id')->value;
            if (!empty($templateId)) {
                $html = $this->generateHtmlFromTemplate($templateId);
            }
        }

        return [
            'components' => $components,
            'styles' => [],
            'html' => $html,
            'css' => '',
        ];
    }

    /**
     * Genera HTML renderizado desde un PageTemplate.
     *
     * @param string $templateId
     *   ID del template.
     *
     * @return string
     *   HTML renderizado del template con datos de ejemplo.
     */
    protected function generateHtmlFromTemplate(string $templateId): string
    {
        try {
            /** @var \Drupal\jaraba_page_builder\PageTemplateInterface|null $template */
            $template = $this->entityTypeManager
                ->getStorage('page_template')
                ->load($templateId);

            if (!$template) {
                return '';
            }

            // Obtener datos de ejemplo para el template.
            $previewData = [];
            if (method_exists($template, 'getPreviewData')) {
                $previewData = $template->getPreviewData() ?: [];
            }

            // Obtener ruta del template Twig.
            $twigPath = '';
            if (method_exists($template, 'getTwigTemplate')) {
                $twigPath = $template->getTwigTemplate();
            }

            if (empty($twigPath)) {
                // Fallback: generar placeholder con datos del template.
                return sprintf(
                    '<section class="jaraba-section jaraba-block" data-block-id="%s">
                        <div class="jaraba-block__container">
                            <h2 class="jaraba-block__title">%s</h2>
                            <p class="jaraba-block__subtitle">%s</p>
                        </div>
                    </section>',
                    htmlspecialchars($templateId),
                    htmlspecialchars($template->label()),
                    htmlspecialchars($template->get('description') ?: '')
                );
            }

            // Renderizar el template Twig con datos de ejemplo.
            /** @var \Twig\Environment $twig */
            $twig = \Drupal::service('twig');
            return $twig->render($twigPath, ['content' => $previewData]);

        } catch (\Exception $e) {
            $this->getLogger('jaraba_page_builder')->warning(
                'Error generando HTML desde template @id: @error',
                ['@id' => $templateId, '@error' => $e->getMessage()]
            );
            return '';
        }
    }

    /**
     * Convierte datos de sección a componentes GrapesJS.
     *
     * @param array $sectionData
     *   Datos de la sección.
     *
     * @return array
     *   Array de componentes GrapesJS.
     */
    protected function sectionToComponents(array $sectionData): array
    {
        // Por ahora retornar componente simple con el contenido.
        return [
            [
                'type' => 'text',
                'content' => $sectionData['title'] ?? 'Sección sin título',
            ],
        ];
    }

    /**
     * Convierte un PageTemplate a definición de bloque GrapesJS.
     *
     * @param mixed $template
     *   Entidad PageTemplate.
     *
     * @return array
     *   Definición de bloque GrapesJS.
     */
    protected function templateToGrapesJSBlock(PageTemplate $template): array
    {
        // PageTemplate es ConfigEntity, usar métodos de la entidad.
        $category = method_exists($template, 'getCategory') ? ($template->getCategory() ?: 'Bloques') : 'Bloques';

        // Obtener thumbnail/preview desde el método getPreviewImage.
        // GrapesJS espera HTML o SVG en el campo media, no una URL string.
        $mediaHtml = '';
        if (method_exists($template, 'getPreviewImage')) {
            $previewPath = $template->getPreviewImage();
            if ($previewPath) {
                $thumbnailUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($previewPath) ?: '';
                if ($thumbnailUrl) {
                    // Formato HTML para GrapesJS con estilos inline para sizing.
                    $mediaHtml = '<img src="' . htmlspecialchars($thumbnailUrl) . '" alt="' . htmlspecialchars($template->label()) . '" style="width: 100%; height: auto; border-radius: 4px; object-fit: cover;">';
                }
            }
        }

        // Fallback a icono SVG si no hay thumbnail.
        if (empty($mediaHtml)) {
            $mediaHtml = $this->getDefaultBlockIcon($category);
        }

        // Generar HTML real renderizado desde el template Twig.
        // Esto permite que GrapesJS muestre el diseño completo en el canvas.
        $templateId = $template->id();
        $content = $this->generateHtmlFromTemplate($templateId);

        // Si no se pudo generar el HTML (error de Twig o template sin path),
        // usar un placeholder informativo pero no con borde dashed.
        if (empty($content)) {
            $templateLabel = htmlspecialchars($template->label());
            $content = sprintf(
                '<section class="jaraba-section jaraba-block" data-block-id="%s">
                    <div class="jaraba-block__container" style="padding: 2rem; text-align: center;">
                        <h2 class="jaraba-block__title">%s</h2>
                        <p class="jaraba-block__subtitle" style="color: #64748b;">%s</p>
                    </div>
                </section>',
                $templateId,
                $templateLabel,
                htmlspecialchars($template->get('description') ?: 'Sección del Page Builder')
            );
        }

        return [
            'id' => 'jaraba-' . $templateId,
            'label' => $template->label(),
            'category' => $category,
            'media' => $mediaHtml,
            'content' => $content,
            'schema' => $this->getTemplateSchema($template),
        ];
    }


    /**
     * Obtiene el schema de campos para un template.
     *
     * @param mixed $template
     *   Entidad PageTemplate.
     *
     * @return array
     *   Schema de campos editables.
     */
    protected function getTemplateSchema(PageTemplate $template): array
    {
        $schema = [];

        // Obtener schema desde método de la entidad si existe.
        if (method_exists($template, 'getSchema')) {
            $schemaValue = $template->getSchema();
            if ($schemaValue) {
                $schema = json_decode($schemaValue, TRUE) ?? [];
            }
        }

        return $schema;
    }

    /**
     * Obtiene icono SVG por defecto según categoría.
     *
     * @param string $category
     *   Nombre de la categoría.
     *
     * @return string
     *   SVG del icono.
     */
    protected function getDefaultBlockIcon(string $category): string
    {
        $icons = [
            'Hero' => '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M21,3H3C1.89,3 1,3.89 1,5V19A2,2 0 0,0 3,21H21C22.11,21 23,20.11 23,19V5C23,3.89 22.11,3 21,3M21,19H3V5H21V19Z"/></svg>',
            'CTA' => '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2"/></svg>',
            'Features' => '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M3,11H11V3H3V11M5,5H9V9H5V5M13,21H21V13H13V21M15,15H19V19H15V15M3,21H11V13H3V21M5,15H9V19H5V15M13,3V11H21V3H13M19,9H15V5H19V9Z"/></svg>',
            'Testimonios' => '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M20,2H4A2,2 0 0,0 2,4V22L6,18H20A2,2 0 0,0 22,16V4A2,2 0 0,0 20,2Z"/></svg>',
        ];

        return $icons[$category] ?? '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M19,5V19H5V5H19M21,3H3V21H21V3Z"/></svg>';
    }

    /**
     * Sanitiza HTML para almacenamiento público.
     *
     * AUDIT-SEC-003: Usa Xss::filterAdmin() de Drupal que elimina script,
     * iframe, object, embed, event handlers (onclick, onerror, etc.) y
     * otros vectores XSS, pero permite tags HTML legítimos del page builder.
     * Además limpia atributos residuales del editor GrapesJS.
     *
     * @param string $html
     *   HTML a sanitizar.
     *
     * @return string
     *   HTML sanitizado.
     */
    protected function sanitizeHtml(string $html): string
    {
        // Paso 1: Sanitización XSS robusta via Drupal core.
        $html = Xss::filterAdmin($html);

        // Paso 2: Limpiar atributos residuales de GrapesJS editor.
        $html = preg_replace('/\s+data-gjs-[^=]+="[^"]*"/i', '', $html);
        $html = preg_replace('/\s+class="[^"]*gjs-[^"]*"/i', '', $html);

        return trim($html);
    }

    /**
     * Sanitiza CSS para prevenir inyección de código.
     *
     * AUDIT-SEC-003: CSS del canvas puede contener vectores XSS.
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
