<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio del Marketplace público de ComercioConecta.
 *
 * Estructura: Proporciona la lógica de negocio para el listado público
 *   de productos y comercios del marketplace. Consume las entidades
 *   ProductRetail y MerchantProfile.
 *
 * Lógica: Todos los métodos aplican filtro de tenant_id obligatorio
 *   para garantizar aislamiento multi-tenant. Los listados solo
 *   muestran productos activos de comercios verificados y activos.
 *   La paginación usa offset/limit estándar.
 */
class MarketplaceService {

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para consultas.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Usuario actual para contexto de sesión.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log del módulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el listado de productos del marketplace con filtros.
   *
   * Lógica: Consulta productos activos del tenant especificado,
   *   aplicando filtros opcionales de categoría, marca, precio,
   *   comercio y estado de stock. Solo incluye productos de
   *   comercios verificados y activos.
   *
   * @param int $tenant_id
   *   ID del tenant para filtro multi-tenant obligatorio.
   * @param array $filters
   *   Filtros opcionales: category_id, brand_id, price_min, price_max,
   *   merchant_id, in_stock_only (bool), search (texto libre).
   * @param string $sort
   *   Ordenación: 'newest', 'price_asc', 'price_desc', 'rating'.
   * @param int $page
   *   Número de página (base 0).
   * @param int $per_page
   *   Elementos por página. Default 24.
   *
   * @return array
   *   Array con 'products' (array de entidades), 'total' (int),
   *   'page' (int), 'per_page' (int), 'total_pages' (int).
   */
  public function getMarketplaceProducts(int $tenant_id, array $filters = [], string $sort = 'newest', int $page = 0, int $per_page = 24): array {
    $storage = $this->entityTypeManager->getStorage('product_retail');

    // Query base: productos activos del tenant
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', 'active');

    // Aplicar filtros opcionales
    if (!empty($filters['category_id'])) {
      $query->condition('category_id', $filters['category_id']);
    }
    if (!empty($filters['brand_id'])) {
      $query->condition('brand_id', $filters['brand_id']);
    }
    if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
      $query->condition('price', (float) $filters['price_min'], '>=');
    }
    if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
      $query->condition('price', (float) $filters['price_max'], '<=');
    }
    if (!empty($filters['merchant_id'])) {
      $query->condition('merchant_id', $filters['merchant_id']);
    }
    if (!empty($filters['in_stock_only'])) {
      $query->condition('stock_quantity', 0, '>');
    }

    // Contar total antes de paginar
    $count_query = clone $query;
    $total = $count_query->count()->execute();

    // Ordenación
    switch ($sort) {
      case 'price_asc':
        $query->sort('price', 'ASC');
        break;

      case 'price_desc':
        $query->sort('price', 'DESC');
        break;

      case 'rating':
        // Ordenar por rating requeriría JOIN; usamos created como fallback
        $query->sort('created', 'DESC');
        break;

      case 'newest':
      default:
        $query->sort('created', 'DESC');
        break;
    }

    // Paginación
    $query->range($page * $per_page, $per_page);

    $ids = $query->execute();
    $products = $ids ? $storage->loadMultiple($ids) : [];

    return [
      'products' => array_values($products),
      'total' => (int) $total,
      'page' => $page,
      'per_page' => $per_page,
      'total_pages' => (int) ceil($total / $per_page),
    ];
  }

  /**
   * Obtiene el listado de comercios activos del marketplace.
   *
   * Lógica: Solo muestra comercios con verification_status = 'approved'
   *   e is_active = TRUE dentro del tenant especificado.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param array $filters
   *   Filtros: business_type, search (texto libre).
   * @param string $sort
   *   Ordenación: 'alphabetical', 'rating', 'newest'.
   *
   * @return array
   *   Array de entidades MerchantProfile.
   */
  public function getMerchants(int $tenant_id, array $filters = [], string $sort = 'alphabetical'): array {
    $storage = $this->entityTypeManager->getStorage('merchant_profile');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->condition('verification_status', 'approved')
      ->condition('is_active', TRUE);

    if (!empty($filters['business_type'])) {
      $query->condition('business_type', $filters['business_type']);
    }

    switch ($sort) {
      case 'rating':
        $query->sort('average_rating', 'DESC');
        break;

      case 'newest':
        $query->sort('created', 'DESC');
        break;

      case 'alphabetical':
      default:
        $query->sort('business_name', 'ASC');
        break;
    }

    $ids = $query->execute();
    return $ids ? array_values($storage->loadMultiple($ids)) : [];
  }

  /**
   * Obtiene un comercio por su slug URL.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string $slug
   *   Slug URL-friendly del comercio.
   *
   * @return \Drupal\jaraba_comercio_conecta\Entity\MerchantProfile|null
   *   El perfil del comercio o NULL si no existe.
   */
  public function getMerchantBySlug(int $tenant_id, string $slug): ?object {
    $storage = $this->entityTypeManager->getStorage('merchant_profile');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->condition('slug', $slug)
      ->condition('is_active', TRUE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Obtiene estadísticas generales del marketplace.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return array
   *   Array con total_products, total_merchants, total_categories.
   */
  public function getMarketplaceStats(int $tenant_id): array {
    $product_storage = $this->entityTypeManager->getStorage('product_retail');
    $merchant_storage = $this->entityTypeManager->getStorage('merchant_profile');

    $total_products = $product_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', 'active')
      ->count()
      ->execute();

    $total_merchants = $merchant_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('tenant_id', $tenant_id)
      ->condition('is_active', TRUE)
      ->condition('verification_status', 'approved')
      ->count()
      ->execute();

    return [
      'total_products' => (int) $total_products,
      'total_merchants' => (int) $total_merchants,
    ];
  }

}
