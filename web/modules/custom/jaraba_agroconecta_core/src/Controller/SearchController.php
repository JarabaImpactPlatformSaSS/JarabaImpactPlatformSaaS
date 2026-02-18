<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\AgroSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador frontend para páginas de búsqueda y descubrimiento.
 *
 * PROPÓSITO:
 * Renderiza las páginas frontend de búsqueda, categoría y colección
 * usando clean Twig templates (page--agro-search, page--agro-category, etc.).
 *
 * PATRÓN FRONTEND:
 * - Usa page--{route-name}.html.twig para layout full-width
 * - Los datos se inyectan via #attached drupalSettings
 * - La interactividad se maneja con JS vanilla (agro-search.js)
 * - Los datos adicionales se cargan via API REST asíncrona
 */
class SearchController extends ControllerBase implements ContainerInjectionInterface
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
     * Página de búsqueda de productos.
     *
     * Ruta: /agroconecta/search?q=aceite
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return array
     *   Render array para la página de búsqueda.
     */
    public function searchPage(Request $request): array
    {
        $query = trim($request->query->get('q', ''));
        $sort = $request->query->get('sort', 'relevance');

        // Obtener categorías para el sidebar de filtros.
        $categories = $this->searchService->getCategoryTree();

        // Colecciones destacadas (para mostrar si no hay búsqueda).
        $featuredCollections = $this->searchService->getFeaturedCollections();

        // Si hay query, hacer búsqueda inicial.
        $initialResults = NULL;
        if (!empty($query)) {
            $initialResults = $this->searchService->searchProducts($query, [], $sort, 24, 0);
        }

        return [
            '#theme' => 'agro_search_page',
            '#search_query' => $query,
            '#sort' => $sort,
            '#categories' => $categories,
            '#featured_collections' => $featuredCollections,
            '#initial_results' => $initialResults,
            '#attached' => [
                'library' => [
                    'jaraba_agroconecta_core/agroconecta.search',
                ],
                'drupalSettings' => [
                    'agroSearch' => [
                        'query' => $query,
                        'sort' => $sort,
                        'apiBase' => '/api/v1/agro',
                        'initialResults' => $initialResults,
                        'categories' => $categories,
                    ],
                ],
            ],
        ];
    }

    /**
     * Página de categoría con listado de productos.
     *
     * Ruta: /agroconecta/category/{slug}
     *
     * @param string $slug
     *   Slug de la categoría.
     *
     * @return array
     *   Render array para la página de categoría.
     */
    public function categoryPage(string $slug): array
    {
        $category = $this->searchService->findCategoryBySlug($slug);
        if (!$category) {
            throw new NotFoundHttpException();
        }

        $categoryData = [
            'id' => (int) $category->id(),
            'name' => $category->label(),
            'slug' => $category->getSlug(),
            'description' => $category->get('description')->value ?? '',
            'breadcrumb' => $category->getBreadcrumb(),
        ];

        $products = $this->searchService->getProductsByCategory((int) $category->id(), 'newest', 24, 0);

        return [
            '#theme' => 'agro_category_page',
            '#category' => $categoryData,
            '#products' => $products,
            '#attached' => [
                'library' => [
                    'jaraba_agroconecta_core/agroconecta.search',
                ],
                'drupalSettings' => [
                    'agroCategory' => [
                        'categoryId' => (int) $category->id(),
                        'category' => $categoryData,
                        'apiBase' => '/api/v1/agro',
                        'initialProducts' => $products,
                        'totalProducts' => $products['total'] ?? 0,
                        'initialCount' => count($products['results'] ?? []),
                    ],
                ],
            ],
        ];
    }

    /**
     * Página de colección con sus productos.
     *
     * Ruta: /agroconecta/collection/{slug}
     *
     * @param string $slug
     *   Slug de la colección.
     *
     * @return array
     *   Render array para la página de colección.
     */
    public function collectionPage(string $slug): array
    {
        $collection = $this->searchService->findCollectionBySlug($slug);
        if (!$collection) {
            throw new NotFoundHttpException();
        }

        $resolved = $this->searchService->resolveCollection((int) $collection->id(), 24, 0);

        return [
            '#theme' => 'agro_collection_page',
            '#collection' => $resolved['collection'],
            '#products' => $resolved['results'],
            '#total' => $resolved['total'],
            '#attached' => [
                'library' => [
                    'jaraba_agroconecta_core/agroconecta.search',
                ],
                'drupalSettings' => [
                    'agroCollection' => [
                        'collectionId' => (int) $collection->id(),
                        'collection' => $resolved['collection'],
                        'apiBase' => '/api/v1/agro',
                        'initialProducts' => $resolved['results'],
                        'totalProducts' => $resolved['total'],
                        'initialCount' => count($resolved['results']),
                    ],
                ],
            ],
        ];
    }

}
