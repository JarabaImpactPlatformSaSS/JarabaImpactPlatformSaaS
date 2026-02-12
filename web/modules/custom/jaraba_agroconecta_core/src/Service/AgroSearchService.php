<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\jaraba_agroconecta_core\Entity\AgroCategory;
use Drupal\jaraba_agroconecta_core\Entity\AgroCollection;

/**
 * Servicio de búsqueda y descubrimiento de productos AgroConecta.
 *
 * PROPÓSITO:
 * Centraliza la búsqueda full-text, autocompletado, navegación por categorías,
 * resolución de colecciones (manuales y smart), cálculo de facets y paginación.
 *
 * FLUJO PRINCIPAL:
 * 1. searchProducts() → búsqueda full-text con filtros y facets
 * 2. autocomplete() → sugerencias rápidas para el input de búsqueda
 * 3. getProductsByCategory() → listado por categoría con paginación
 * 4. resolveCollection() → materializa una colección (manual o smart)
 * 5. getCategoryTree() → árbol jerárquico para navegación
 * 6. getFeaturedCollections() → colecciones destacadas para la home
 */
class AgroSearchService
{

    /**
     * Canal de logger del servicio.
     */
    protected LoggerChannelInterface $logger;

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected Connection $database,
        LoggerChannelFactoryInterface $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('jaraba_agroconecta.search');
    }

    /**
     * Busca productos con texto libre, filtros y facets.
     *
     * @param string $query
     *   Texto de búsqueda.
     * @param array $filters
     *   Filtros opcionales: category_id, min_price, max_price, organic, region.
     * @param string $sort
     *   Campo de ordenación: relevance, price_asc, price_desc, newest, best_rated.
     * @param int $limit
     *   Número máximo de resultados.
     * @param int $offset
     *   Desplazamiento para paginación.
     *
     * @return array
     *   Array con 'results', 'total', 'facets'.
     */
    public function searchProducts(string $query, array $filters = [], string $sort = 'relevance', int $limit = 24, int $offset = 0): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('product_agro');
            $entityQuery = $storage->getQuery()
                ->condition('status', 1)
                ->accessCheck(TRUE);

            // Búsqueda por texto.
            if (!empty($query)) {
                $group = $entityQuery->orConditionGroup()
                    ->condition('name', '%' . $query . '%', 'LIKE')
                    ->condition('description', '%' . $query . '%', 'LIKE');
                $entityQuery->condition($group);
            }

            // Filtro por categoría.
            if (!empty($filters['category_id'])) {
                $entityQuery->condition('category_id', (int) $filters['category_id']);
            }

            // Filtro por precio.
            if (!empty($filters['min_price'])) {
                $entityQuery->condition('price', (string) $filters['min_price'], '>=');
            }
            if (!empty($filters['max_price'])) {
                $entityQuery->condition('price', (string) $filters['max_price'], '<=');
            }

            // Filtro orgánico.
            if (isset($filters['organic']) && $filters['organic'] !== '') {
                $entityQuery->condition('organic', (bool) $filters['organic']);
            }

            // Contar total antes de paginar.
            $countQuery = clone $entityQuery;
            $total = (int) $countQuery->count()->execute();

            // Ordenación.
            switch ($sort) {
                case 'price_asc':
                    $entityQuery->sort('price', 'ASC');
                    break;
                case 'price_desc':
                    $entityQuery->sort('price', 'DESC');
                    break;
                case 'newest':
                    $entityQuery->sort('created', 'DESC');
                    break;
                case 'best_rated':
                    $entityQuery->sort('average_rating', 'DESC');
                    break;
                default:
                    // Por relevancia: más recientes primero por defecto.
                    $entityQuery->sort('created', 'DESC');
                    break;
            }

            // Paginación.
            $entityQuery->range($offset, $limit);

            $ids = $entityQuery->execute();
            $products = $ids ? $storage->loadMultiple($ids) : [];

            // Serializar resultados.
            $results = [];
            foreach ($products as $product) {
                $results[] = $this->serializeProduct($product);
            }

            // Calcular facets si hay búsqueda activa.
            $facets = $this->calculateFacets($query, $filters);

            return [
                'results' => $results,
                'total' => $total,
                'facets' => $facets,
                'meta' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'query' => $query,
                    'sort' => $sort,
                ],
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error en búsqueda: @message', ['@message' => $e->getMessage()]);
            return [
                'results' => [],
                'total' => 0,
                'facets' => [],
                'meta' => ['error' => TRUE],
            ];
        }
    }

    /**
     * Autocompletado de búsqueda.
     *
     * @param string $query
     *   Texto parcial de búsqueda.
     * @param int $limit
     *   Número máximo de sugerencias.
     *
     * @return array
     *   Array de sugerencias con 'label' y 'value'.
     */
    public function autocomplete(string $query, int $limit = 5): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $storage = $this->entityTypeManager->getStorage('product_agro');
            $ids = $storage->getQuery()
                ->condition('status', 1)
                ->condition('name', '%' . $query . '%', 'LIKE')
                ->range(0, $limit)
                ->sort('name', 'ASC')
                ->accessCheck(TRUE)
                ->execute();

            $suggestions = [];
            if ($ids) {
                $products = $storage->loadMultiple($ids);
                foreach ($products as $product) {
                    $suggestions[] = [
                        'label' => $product->label(),
                        'value' => $product->label(),
                        'id' => (int) $product->id(),
                    ];
                }
            }

            return $suggestions;
        } catch (\Exception $e) {
            $this->logger->error('Error en autocompletado: @message', ['@message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtiene productos por categoría con paginación.
     *
     * @param int $categoryId
     *   ID de la categoría.
     * @param string $sort
     *   Campo de ordenación.
     * @param int $limit
     *   Límite de resultados.
     * @param int $offset
     *   Desplazamiento.
     *
     * @return array
     *   Array con 'results', 'total', 'category'.
     */
    public function getProductsByCategory(int $categoryId, string $sort = 'newest', int $limit = 24, int $offset = 0): array
    {
        try {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCategory|null $category */
            $category = $this->entityTypeManager->getStorage('agro_category')->load($categoryId);
            if (!$category || !$category->isActive()) {
                return ['results' => [], 'total' => 0, 'category' => NULL];
            }

            $storage = $this->entityTypeManager->getStorage('product_agro');
            $entityQuery = $storage->getQuery()
                ->condition('status', 1)
                ->condition('category_id', $categoryId)
                ->accessCheck(TRUE);

            $total = (int) (clone $entityQuery)->count()->execute();

            switch ($sort) {
                case 'price_asc':
                    $entityQuery->sort('price', 'ASC');
                    break;
                case 'price_desc':
                    $entityQuery->sort('price', 'DESC');
                    break;
                case 'best_rated':
                    $entityQuery->sort('average_rating', 'DESC');
                    break;
                default:
                    $entityQuery->sort('created', 'DESC');
                    break;
            }

            $entityQuery->range($offset, $limit);
            $ids = $entityQuery->execute();
            $products = $ids ? $storage->loadMultiple($ids) : [];

            $results = [];
            foreach ($products as $product) {
                $results[] = $this->serializeProduct($product);
            }

            return [
                'results' => $results,
                'total' => $total,
                'category' => $this->serializeCategory($category),
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo productos por categoría: @message', ['@message' => $e->getMessage()]);
            return ['results' => [], 'total' => 0, 'category' => NULL];
        }
    }

    /**
     * Obtiene el árbol jerárquico de categorías.
     *
     * @param int|null $tenantId
     *   ID del tenant (opcional).
     *
     * @return array
     *   Árbol de categorías serializadas.
     */
    public function getCategoryTree(?int $tenantId = NULL): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('agro_category');
            $query = $storage->getQuery()
                ->condition('is_active', TRUE)
                ->sort('position', 'ASC')
                ->sort('name', 'ASC')
                ->accessCheck(TRUE);

            if ($tenantId) {
                $query->condition('tenant_id', $tenantId);
            }

            $ids = $query->execute();
            /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCategory[] $categories */
            $categories = $ids ? $storage->loadMultiple($ids) : [];

            // Construir árbol jerárquico.
            $tree = [];
            $indexed = [];

            foreach ($categories as $category) {
                $serialized = $this->serializeCategory($category);
                $serialized['children'] = [];
                $indexed[(int) $category->id()] = $serialized;
            }

            foreach ($indexed as $id => &$cat) {
                $parentId = $categories[$id]->get('parent_id')->target_id ?? NULL;
                if ($parentId && isset($indexed[$parentId])) {
                    $indexed[$parentId]['children'][] = &$cat;
                } else {
                    $tree[] = &$cat;
                }
            }

            return $tree;
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo árbol de categorías: @message', ['@message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtiene colecciones destacadas.
     *
     * @param int $limit
     *   Número máximo de colecciones.
     *
     * @return array
     *   Array de colecciones serializadas.
     */
    public function getFeaturedCollections(int $limit = 6): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('agro_collection');
            $ids = $storage->getQuery()
                ->condition('is_active', TRUE)
                ->condition('is_featured', TRUE)
                ->sort('position', 'ASC')
                ->range(0, $limit)
                ->accessCheck(TRUE)
                ->execute();

            /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCollection[] $collections */
            $collections = $ids ? $storage->loadMultiple($ids) : [];
            $results = [];
            foreach ($collections as $collection) {
                $results[] = $this->serializeCollection($collection);
            }

            return $results;
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo colecciones destacadas: @message', ['@message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtiene todas las colecciones activas.
     *
     * @return array
     *   Array de colecciones serializadas.
     */
    public function getActiveCollections(): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('agro_collection');
            $ids = $storage->getQuery()
                ->condition('is_active', TRUE)
                ->sort('position', 'ASC')
                ->accessCheck(TRUE)
                ->execute();

            /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCollection[] $collections */
            $collections = $ids ? $storage->loadMultiple($ids) : [];
            $results = [];
            foreach ($collections as $collection) {
                $results[] = $this->serializeCollection($collection);
            }

            return $results;
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo colecciones activas: @message', ['@message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Resuelve una colección y devuelve sus productos.
     *
     * @param int $collectionId
     *   ID de la colección.
     * @param int $limit
     *   Límite de resultados.
     * @param int $offset
     *   Desplazamiento.
     *
     * @return array
     *   Array con 'collection', 'results', 'total'.
     */
    public function resolveCollection(int $collectionId, int $limit = 24, int $offset = 0): array
    {
        try {
            /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCollection|null $collection */
            $collection = $this->entityTypeManager->getStorage('agro_collection')->load($collectionId);
            if (!$collection || !$collection->isActive()) {
                return ['collection' => NULL, 'results' => [], 'total' => 0];
            }

            if ($collection->isManual()) {
                return $this->resolveManualCollection($collection, $limit, $offset);
            } else {
                return $this->resolveSmartCollection($collection, $limit, $offset);
            }
        } catch (\Exception $e) {
            $this->logger->error('Error resolviendo colección: @message', ['@message' => $e->getMessage()]);
            return ['collection' => NULL, 'results' => [], 'total' => 0];
        }
    }

    /**
     * Busca categoría por slug.
     *
     * @param string $slug
     *   Slug de la categoría.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\AgroCategory|null
     *   La categoría o NULL si no se encuentra.
     */
    public function findCategoryBySlug(string $slug): ?AgroCategory
    {
        $storage = $this->entityTypeManager->getStorage('agro_category');
        $ids = $storage->getQuery()
            ->condition('slug', $slug)
            ->condition('is_active', TRUE)
            ->accessCheck(TRUE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        return $storage->load(reset($ids));
    }

    /**
     * Busca colección por slug.
     *
     * @param string $slug
     *   Slug de la colección.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\AgroCollection|null
     *   La colección o NULL si no se encuentra.
     */
    public function findCollectionBySlug(string $slug): ?AgroCollection
    {
        $storage = $this->entityTypeManager->getStorage('agro_collection');
        $ids = $storage->getQuery()
            ->condition('slug', $slug)
            ->condition('is_active', TRUE)
            ->accessCheck(TRUE)
            ->execute();

        if (empty($ids)) {
            return NULL;
        }

        return $storage->load(reset($ids));
    }

    /**
     * Resuelve una colección manual (IDs explícitos).
     */
    protected function resolveManualCollection(AgroCollection $collection, int $limit, int $offset): array
    {
        $productIds = $collection->getProductIds();
        $total = count($productIds);

        if (empty($productIds)) {
            return [
                'collection' => $this->serializeCollection($collection),
                'results' => [],
                'total' => 0,
            ];
        }

        // Paginar los IDs.
        $pagedIds = array_slice($productIds, $offset, $limit);

        $storage = $this->entityTypeManager->getStorage('product_agro');
        $products = $storage->loadMultiple($pagedIds);

        $results = [];
        foreach ($products as $product) {
            if ($product->get('status')->value) {
                $results[] = $this->serializeProduct($product);
            }
        }

        return [
            'collection' => $this->serializeCollection($collection),
            'results' => $results,
            'total' => $total,
        ];
    }

    /**
     * Resuelve una colección smart (reglas dinámicas).
     */
    protected function resolveSmartCollection(AgroCollection $collection, int $limit, int $offset): array
    {
        $rules = $collection->getRules();
        $storage = $this->entityTypeManager->getStorage('product_agro');
        $query = $storage->getQuery()
            ->condition('status', 1)
            ->accessCheck(TRUE);

        // Aplicar reglas.
        if (!empty($rules['category_id'])) {
            $query->condition('category_id', (int) $rules['category_id']);
        }
        if (!empty($rules['min_price'])) {
            $query->condition('price', (string) $rules['min_price'], '>=');
        }
        if (!empty($rules['max_price'])) {
            $query->condition('price', (string) $rules['max_price'], '<=');
        }
        if (!empty($rules['min_rating'])) {
            $query->condition('average_rating', (string) $rules['min_rating'], '>=');
        }

        $total = (int) (clone $query)->count()->execute();

        // Ordenación por regla.
        $sort = $rules['sort'] ?? 'newest';
        switch ($sort) {
            case 'price_asc':
                $query->sort('price', 'ASC');
                break;
            case 'price_desc':
                $query->sort('price', 'DESC');
                break;
            case 'best_rated':
                $query->sort('average_rating', 'DESC');
                break;
            default:
                $query->sort('created', 'DESC');
                break;
        }

        $query->range($offset, $limit);
        $ids = $query->execute();
        $products = $ids ? $storage->loadMultiple($ids) : [];

        $results = [];
        foreach ($products as $product) {
            $results[] = $this->serializeProduct($product);
        }

        return [
            'collection' => $this->serializeCollection($collection),
            'results' => $results,
            'total' => $total,
        ];
    }

    /**
     * Calcula facets disponibles para los filtros activos.
     */
    protected function calculateFacets(string $query, array $currentFilters): array
    {
        $facets = [];

        try {
            // Facet de categorías: contar productos por categoría.
            $categoryStorage = $this->entityTypeManager->getStorage('agro_category');
            $categoryIds = $categoryStorage->getQuery()
                ->condition('is_active', TRUE)
                ->sort('position', 'ASC')
                ->accessCheck(TRUE)
                ->execute();

            if ($categoryIds) {
                /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCategory[] $categories */
                $categories = $categoryStorage->loadMultiple($categoryIds);
                $categoryFacets = [];
                foreach ($categories as $category) {
                    $categoryFacets[] = [
                        'id' => (int) $category->id(),
                        'name' => $category->label(),
                        'slug' => $category->getSlug(),
                        'count' => $category->getProductCount(),
                    ];
                }
                $facets['categories'] = $categoryFacets;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Error calculando facets: @message', ['@message' => $e->getMessage()]);
        }

        return $facets;
    }

    /**
     * Serializa un producto para respuesta API.
     *
     * @param mixed $product
     *   El producto a serializar.
     *
     * @return array
     *   Datos del producto en formato array.
     */
    protected function serializeProduct($product): array
    {
        return [
            'id' => (int) $product->id(),
            'name' => $product->label(),
            'slug' => $product->get('slug')->value ?? '',
            'description' => $product->get('description')->value ?? '',
            'price' => (float) ($product->get('price')->value ?? 0),
            'unit' => $product->get('unit')->value ?? 'kg',
            'category_id' => (int) ($product->get('category_id')->target_id ?? 0),
            'producer_id' => (int) ($product->get('producer_id')->target_id ?? 0),
            'average_rating' => (float) ($product->get('average_rating')->value ?? 0),
            'review_count' => (int) ($product->get('review_count')->value ?? 0),
            'status' => (bool) $product->get('status')->value,
            'created' => $product->get('created')->value,
        ];
    }

    /**
     * Serializa una categoría para respuesta API.
     */
    protected function serializeCategory(AgroCategory $category): array
    {
        return [
            'id' => (int) $category->id(),
            'name' => $category->label(),
            'slug' => $category->getSlug(),
            'description' => $category->get('description')->value ?? '',
            'icon' => $category->get('icon')->value ?? '',
            'parent_id' => $category->get('parent_id')->target_id ? (int) $category->get('parent_id')->target_id : NULL,
            'product_count' => $category->getProductCount(),
            'is_featured' => $category->isFeatured(),
            'position' => (int) $category->get('position')->value,
        ];
    }

    /**
     * Serializa una colección para respuesta API.
     */
    protected function serializeCollection(AgroCollection $collection): array
    {
        return [
            'id' => (int) $collection->id(),
            'name' => $collection->label(),
            'slug' => $collection->getSlug(),
            'description' => $collection->get('description')->value ?? '',
            'type' => $collection->get('type')->value,
            'type_label' => $collection->getTypeLabel(),
            'is_featured' => $collection->isFeatured(),
            'position' => (int) $collection->get('position')->value,
        ];
    }

}
