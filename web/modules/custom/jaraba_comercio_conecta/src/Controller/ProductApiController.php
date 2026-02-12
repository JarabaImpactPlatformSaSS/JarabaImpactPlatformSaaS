<?php

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\MarketplaceService;
use Drupal\jaraba_comercio_conecta\Service\ProductRetailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller REST API para productos y comerciantes.
 *
 * Estructura: Expone endpoints JSON en /api/v1/comercio/ para
 *   operaciones CRUD de productos, variaciones, stock y comerciantes.
 *
 * Lógica: Todos los endpoints devuelven JsonResponse. Los endpoints de
 *   lectura son públicos (con permisos de 'view'). Los de escritura
 *   requieren permisos de 'create', 'edit own' o 'manage'.
 *   El tenant_id se obtiene del TenantContextService.
 */
class ProductApiController extends ControllerBase {

  /**
   * Constructor del controller.
   *
   * @param \Drupal\jaraba_comercio_conecta\Service\MarketplaceService $marketplaceService
   *   Servicio del marketplace.
   * @param \Drupal\jaraba_comercio_conecta\Service\ProductRetailService $productService
   *   Servicio de productos.
   */
  public function __construct(
    protected MarketplaceService $marketplaceService,
    protected ProductRetailService $productService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.marketplace'),
      $container->get('jaraba_comercio_conecta.product_retail'),
    );
  }

  /**
   * Lista productos con filtros y paginación.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP con query params.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista paginada de productos.
   */
  public function list(Request $request): JsonResponse {
    $tenant_id = $this->getTenantId();
    $filters = array_filter([
      'category_id' => $request->query->get('category'),
      'brand_id' => $request->query->get('brand'),
      'merchant_id' => $request->query->get('merchant'),
      'in_stock_only' => $request->query->get('in_stock'),
    ], fn($v) => $v !== NULL && $v !== '');

    $sort = $request->query->get('sort', 'newest');
    $page = max(0, (int) $request->query->get('page', 0));

    $result = $this->marketplaceService->getMarketplaceProducts(
      $tenant_id, $filters, $sort, $page
    );

    $data = [];
    foreach ($result['products'] as $product) {
      $data[] = $this->serializeProduct($product);
    }

    return new JsonResponse([
      'data' => $data,
      'meta' => [
        'total' => $result['total'],
        'page' => $result['page'],
        'per_page' => $result['per_page'],
        'total_pages' => $result['total_pages'],
      ],
    ]);
  }

  /**
   * Detalle de un producto.
   *
   * @param int $product_retail
   *   ID del producto.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Datos completos del producto.
   */
  public function detail(int $product_retail): JsonResponse {
    $detail = $this->productService->getProductDetail($product_retail);

    if (!$detail) {
      throw new NotFoundHttpException();
    }

    $data = $this->serializeProduct($detail['product']);
    $data['variations'] = [];
    foreach ($detail['variations'] as $variation) {
      $data['variations'][] = [
        'id' => (int) $variation->id(),
        'title' => $variation->get('title')->value,
        'sku' => $variation->get('sku')->value,
        'price' => (float) $variation->get('price')->value,
        'stock_quantity' => (int) $variation->get('stock_quantity')->value,
        'attributes' => $variation->get('attributes')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * Crea un nuevo producto (POST).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request con body JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Producto creado.
   */
  public function createProduct(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['title']) || empty($content['merchant_id'])) {
      return new JsonResponse([
        'error' => $this->t('Campos obligatorios: title, merchant_id'),
      ], 400);
    }

    $product = $this->entityTypeManager()
      ->getStorage('product_retail')
      ->create([
        'title' => $content['title'],
        'merchant_id' => $content['merchant_id'],
        'tenant_id' => $this->getTenantId(),
        'sku' => $content['sku'] ?? '',
        'description' => $content['description'] ?? '',
        'price' => $content['price'] ?? 0,
        'status' => $content['status'] ?? 'draft',
      ]);

    $product->save();

    return new JsonResponse([
      'data' => $this->serializeProduct($product),
    ], 201);
  }

  /**
   * Actualiza un producto (PATCH).
   *
   * @param int $product_retail
   *   ID del producto.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request con body JSON parcial.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Producto actualizado.
   */
  public function update(int $product_retail, Request $request): JsonResponse {
    $product = $this->entityTypeManager()
      ->getStorage('product_retail')
      ->load($product_retail);

    if (!$product) {
      throw new NotFoundHttpException();
    }

    $content = json_decode($request->getContent(), TRUE);
    $updatable = ['title', 'description', 'price', 'compare_at_price', 'status', 'stock_quantity'];

    foreach ($updatable as $field) {
      if (isset($content[$field])) {
        $product->set($field, $content[$field]);
      }
    }

    $product->save();

    return new JsonResponse(['data' => $this->serializeProduct($product)]);
  }

  /**
   * Obtiene variaciones de un producto.
   *
   * @param int $product_retail
   *   ID del producto padre.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de variaciones.
   */
  public function variations(int $product_retail): JsonResponse {
    $variations = $this->productService->getProductVariations($product_retail);

    $data = [];
    foreach ($variations as $variation) {
      $data[] = [
        'id' => (int) $variation->id(),
        'title' => $variation->get('title')->value,
        'sku' => $variation->get('sku')->value,
        'price' => (float) $variation->get('price')->value,
        'stock_quantity' => (int) $variation->get('stock_quantity')->value,
        'is_active' => (bool) $variation->get('is_active')->value,
        'attributes' => $variation->get('attributes')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * Actualiza stock de un producto o variación (POST).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request con body JSON: {product_id, quantity, location_id?}.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado de la actualización.
   */
  public function updateStock(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (empty($content['product_id']) || !isset($content['quantity'])) {
      return new JsonResponse([
        'error' => $this->t('Campos obligatorios: product_id, quantity'),
      ], 400);
    }

    $this->productService->updateStock(
      (int) $content['product_id'],
      (int) $content['quantity']
    );

    return new JsonResponse(['status' => 'ok']);
  }

  /**
   * Lista comerciantes del marketplace.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de comerciantes.
   */
  public function merchants(): JsonResponse {
    $tenant_id = $this->getTenantId();
    $merchants = $this->marketplaceService->getMerchants($tenant_id);

    $data = [];
    foreach ($merchants as $merchant) {
      $data[] = [
        'id' => (int) $merchant->id(),
        'business_name' => $merchant->get('business_name')->value,
        'slug' => $merchant->get('slug')->value,
        'business_type' => $merchant->get('business_type')->value,
        'address_city' => $merchant->get('address_city')->value,
        'is_active' => (bool) $merchant->get('is_active')->value,
      ];
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * Detalle de un comerciante.
   *
   * @param int $merchant_profile
   *   ID del comerciante.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Datos del comerciante.
   */
  public function merchantDetail(int $merchant_profile): JsonResponse {
    $merchant = $this->entityTypeManager()
      ->getStorage('merchant_profile')
      ->load($merchant_profile);

    if (!$merchant) {
      throw new NotFoundHttpException();
    }

    return new JsonResponse([
      'data' => [
        'id' => (int) $merchant->id(),
        'business_name' => $merchant->get('business_name')->value,
        'slug' => $merchant->get('slug')->value,
        'business_type' => $merchant->get('business_type')->value,
        'description' => $merchant->get('description')->value,
        'phone' => $merchant->get('phone')->value,
        'email' => $merchant->get('email')->value,
        'address_city' => $merchant->get('address_city')->value,
        'click_collect' => (bool) $merchant->get('click_collect')->value,
        'average_rating' => (float) $merchant->get('average_rating')->value,
        'total_reviews' => (int) $merchant->get('total_reviews')->value,
      ],
    ]);
  }

  /**
   * Busca comerciantes cercanos por geolocalización.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request con query params: lat, lng, radius (km).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de comerciantes cercanos ordenados por distancia.
   */
  public function merchantsNearby(Request $request): JsonResponse {
    $lat = (float) $request->query->get('lat', 0);
    $lng = (float) $request->query->get('lng', 0);
    $radius = (float) $request->query->get('radius', 10);

    if (!$lat || !$lng) {
      return new JsonResponse([
        'error' => $this->t('Parámetros requeridos: lat, lng'),
      ], 400);
    }

    // Búsqueda básica por rango de coordenadas (Fase 1)
    // En Fase 4 se optimizará con índice espacial
    $delta_lat = $radius / 111.0;
    $delta_lng = $radius / (111.0 * cos(deg2rad($lat)));

    $ids = $this->entityTypeManager()
      ->getStorage('merchant_profile')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('is_active', TRUE)
      ->condition('verification_status', 'approved')
      ->condition('latitude', $lat - $delta_lat, '>=')
      ->condition('latitude', $lat + $delta_lat, '<=')
      ->condition('longitude', $lng - $delta_lng, '>=')
      ->condition('longitude', $lng + $delta_lng, '<=')
      ->execute();

    $data = [];
    if ($ids) {
      $merchants = $this->entityTypeManager()
        ->getStorage('merchant_profile')
        ->loadMultiple($ids);

      foreach ($merchants as $merchant) {
        $m_lat = (float) $merchant->get('latitude')->value;
        $m_lng = (float) $merchant->get('longitude')->value;
        $distance = $this->haversineDistance($lat, $lng, $m_lat, $m_lng);

        if ($distance <= $radius) {
          $data[] = [
            'id' => (int) $merchant->id(),
            'business_name' => $merchant->get('business_name')->value,
            'slug' => $merchant->get('slug')->value,
            'address_city' => $merchant->get('address_city')->value,
            'latitude' => $m_lat,
            'longitude' => $m_lng,
            'distance_km' => round($distance, 2),
            'click_collect' => (bool) $merchant->get('click_collect')->value,
          ];
        }
      }

      // Ordenar por distancia
      usort($data, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);
    }

    return new JsonResponse(['data' => $data]);
  }

  /**
   * Serializa un producto para respuesta JSON.
   *
   * @param object $product
   *   Entidad ProductRetail.
   *
   * @return array
   *   Datos serializados.
   */
  protected function serializeProduct(object $product): array {
    return [
      'id' => (int) $product->id(),
      'title' => $product->get('title')->value,
      'sku' => $product->get('sku')->value,
      'price' => (float) $product->get('price')->value,
      'compare_at_price' => (float) ($product->get('compare_at_price')->value ?? 0),
      'stock_quantity' => (int) $product->get('stock_quantity')->value,
      'status' => $product->get('status')->value,
      'has_variations' => (bool) $product->get('has_variations')->value,
    ];
  }

  /**
   * Obtiene el tenant_id del contexto actual.
   *
   * @return int
   *   ID del tenant.
   */
  protected function getTenantId(): int {
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      $tenant_context = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $tenant = $tenant_context->getCurrentTenant();
      if ($tenant) {
        return (int) $tenant->id();
      }
    }
    return 1;
  }

  /**
   * Calcula distancia Haversine entre dos puntos geográficos.
   *
   * @param float $lat1
   *   Latitud origen.
   * @param float $lng1
   *   Longitud origen.
   * @param float $lat2
   *   Latitud destino.
   * @param float $lng2
   *   Longitud destino.
   *
   * @return float
   *   Distancia en kilómetros.
   */
  protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earth_radius = 6371;
    $d_lat = deg2rad($lat2 - $lat1);
    $d_lng = deg2rad($lng2 - $lng1);
    $a = sin($d_lat / 2) * sin($d_lat / 2)
      + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
      * sin($d_lng / 2) * sin($d_lng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
  }

}
