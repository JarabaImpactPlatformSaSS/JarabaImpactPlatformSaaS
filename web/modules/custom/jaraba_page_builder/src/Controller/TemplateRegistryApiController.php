<?php

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_page_builder\Service\TemplateRegistryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para Template Registry.
 *
 * Expone endpoints REST para acceso a templates del Page Builder.
 */
class TemplateRegistryApiController extends ControllerBase
{

    /**
     * El servicio de registro de templates.
     *
     * @var \Drupal\jaraba_page_builder\Service\TemplateRegistryService
     */
    protected TemplateRegistryService $templateRegistry;

    /**
     * Constructor.
     */
    public function __construct(TemplateRegistryService $template_registry)
    {
        $this->templateRegistry = $template_registry;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_page_builder.template_registry')
        );
    }

    /**
     * GET /api/v1/page-builder/templates
     *
     * Obtiene lista de templates disponibles.
     *
     * Query params:
     * - category: Filtrar por categoría
     * - is_premium: Filtrar por estado premium (true/false)
     * - include_restricted: Incluir templates no accesibles al plan actual
     * - format: 'gallery' | 'blocks' | 'list' (default: list)
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Lista de templates.
     */
    public function list(Request $request): JsonResponse
    {
        $options = [];

        // Filtro por categoría.
        if ($category = $request->query->get('category')) {
            $options['category'] = $category;
        }

        // Filtro por premium.
        if ($request->query->has('is_premium')) {
            $options['is_premium'] = filter_var(
                $request->query->get('is_premium'),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        // Incluir restringidos.
        if ($request->query->get('include_restricted') === 'true') {
            $options['include_restricted'] = TRUE;
        }

        // Formato de respuesta.
        $format = $request->query->get('format', 'list');

        switch ($format) {
            case 'gallery':
                $data = $this->templateRegistry->getForGallery($options);
                break;

            case 'blocks':
                $data = $this->templateRegistry->getAsGrapesJSBlocks($options);
                break;

            case 'grouped':
                $data = $this->templateRegistry->getGroupedByCategory($options);
                break;

            default:
                $data = $this->templateRegistry->getAll($options);
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => $data,
            'meta' => [
                'format' => $format,
                'filters' => $options,
            ],
        ]);
    }

    /**
     * GET /api/v1/page-builder/templates/{id}
     *
     * Obtiene un template específico por ID.
     *
     * @param string $id
     *   El ID del template.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   El template o error 404.
     */
    public function get(string $id): JsonResponse
    {
        $template = $this->templateRegistry->get($id);

        if (!$template) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Template not found: @id', ['@id' => $id]),
            ], 404);
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => $template,
        ]);
    }

    /**
     * GET /api/v1/page-builder/templates/stats
     *
     * Obtiene estadísticas del registry.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Estadísticas.
     */
    public function stats(): JsonResponse
    {
        return new JsonResponse([
            'success' => TRUE,
            'data' => $this->templateRegistry->getStats(),
        ]);
    }

    /**
     * GET /api/v1/page-builder/templates/categories
     *
     * Obtiene lista de categorías disponibles.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Categorías.
     */
    public function categories(): JsonResponse
    {
        return new JsonResponse([
            'success' => TRUE,
            'data' => TemplateRegistryService::CATEGORIES,
        ]);
    }

    /**
     * POST /api/v1/page-builder/templates/cache/invalidate
     *
     * Invalida el caché de templates (admin only).
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Confirmación.
     */
    public function invalidateCache(): JsonResponse
    {
        $this->templateRegistry->invalidateCache();

        return new JsonResponse([
            'success' => TRUE,
            'message' => $this->t('Template cache invalidated.'),
        ]);
    }

}
