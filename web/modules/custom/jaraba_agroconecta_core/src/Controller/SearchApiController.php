<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\AgroSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para búsqueda y descubrimiento de AgroConecta.
 *
 * PROPÓSITO:
 * Expone endpoints JSON para búsqueda, autocompletado, categorías y
 * colecciones desde frontend.
 *
 * ENDPOINTS:
 * - GET /api/v1/agro/search?q=&sort=&category_id=&limit=&offset=
 * - GET /api/v1/agro/search/autocomplete?q=
 * - GET /api/v1/agro/categories
 * - GET /api/v1/agro/categories/{category_id}/products
 * - GET /api/v1/agro/collections
 * - GET /api/v1/agro/collections/{collection_id}
 */
class SearchApiController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor del controlador.
     */
    public function __construct(
        protected AgroSearchService $searchService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta_core.search_service'), // AUDIT-CONS-N05: canonical prefix
        );
    }

    /**
     * Busca productos con texto libre y filtros.
     *
     * GET /api/v1/agro/search?q=aceite&sort=price_asc&category_id=5
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultados, total y facets.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $sort = $request->query->get('sort', 'relevance');
        $limit = min((int) $request->query->get('limit', 24), 100);
        $offset = max((int) $request->query->get('offset', 0), 0);

        $filters = [];
        if ($request->query->has('category_id')) {
            $filters['category_id'] = $request->query->get('category_id');
        }
        if ($request->query->has('min_price')) {
            $filters['min_price'] = $request->query->get('min_price');
        }
        if ($request->query->has('max_price')) {
            $filters['max_price'] = $request->query->get('max_price');
        }
        if ($request->query->has('organic')) {
            $filters['organic'] = $request->query->get('organic');
        }

        $results = $this->searchService->searchProducts($query, $filters, $sort, $limit, $offset);

        return new JsonResponse(['success' => TRUE, 'data' => $results, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Autocompletado de búsqueda.
     *
     * GET /api/v1/agro/search/autocomplete?q=ace
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con sugerencias.
     */
    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim($request->query->get('q', ''));
        $limit = min((int) $request->query->get('limit', 5), 10);

        $suggestions = $this->searchService->autocomplete($query, $limit);

        return new JsonResponse(['suggestions' => $suggestions]);
    }

    /**
     * Lista todas las categorías activas (árbol jerárquico).
     *
     * GET /api/v1/agro/categories
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con árbol de categorías.
     */
    public function categories(): JsonResponse
    {
        $tree = $this->searchService->getCategoryTree();

        return new JsonResponse(['categories' => $tree]);
    }

    /**
     * Lista productos de una categoría.
     *
     * GET /api/v1/agro/categories/{category_id}/products
     *
     * @param int $category_id
     *   ID de la categoría.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con productos de la categoría.
     */
    public function categoryProducts(int $category_id, Request $request): JsonResponse
    {
        $sort = $request->query->get('sort', 'newest');
        $limit = min((int) $request->query->get('limit', 24), 100);
        $offset = max((int) $request->query->get('offset', 0), 0);

        $results = $this->searchService->getProductsByCategory($category_id, $sort, $limit, $offset);

        return new JsonResponse(['success' => TRUE, 'data' => $results, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Lista todas las colecciones activas.
     *
     * GET /api/v1/agro/collections
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con colecciones.
     */
    public function collections(): JsonResponse
    {
        $collections = $this->searchService->getActiveCollections();

        return new JsonResponse(['collections' => $collections]);
    }

    /**
     * Detalle de una colección y sus productos.
     *
     * GET /api/v1/agro/collections/{collection_id}
     *
     * @param int $collection_id
     *   ID de la colección.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con colección y productos.
     */
    public function collectionDetail(int $collection_id, Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 24), 100);
        $offset = max((int) $request->query->get('offset', 0), 0);

        $results = $this->searchService->resolveCollection($collection_id, $limit, $offset);

        if (!$results['collection']) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Colección no encontrada.']], 404);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $results, 'meta' => ['timestamp' => time()]]);
    }

}
