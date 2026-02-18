<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_comercio_conecta\Service\MarketplaceService;
use Drupal\jaraba_comercio_conecta\Service\ProductRetailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller de las páginas frontend del marketplace público.
 *
 * Estructura: Gestiona las rutas /marketplace, /marketplace/{slug}
 *   y /marketplace/producto/{id}. Devuelve render arrays con
 *   #theme apuntando a los templates Twig del módulo.
 *
 * Lógica: El marketplace es público (accesible sin login).
 *   Cada página usa templates limpios sin regiones de Drupal.
 *   Los filtros se reciben como query parameters en la URL.
 *   El tenant_id se obtiene del contexto del sitio actual.
 */
class MarketplaceController extends ControllerBase {

  /**
   * Constructor del controller.
   *
   * @param \Drupal\jaraba_comercio_conecta\Service\MarketplaceService $marketplaceService
   *   Servicio del marketplace.
   * @param \Drupal\jaraba_comercio_conecta\Service\ProductRetailService $productRetailService
   *   Servicio de productos.
   */
  public function __construct(
    protected MarketplaceService $marketplaceService,
    protected ProductRetailService $productRetailService,
    protected TenantContextService $tenantContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.marketplace'),
      $container->get('jaraba_comercio_conecta.product_retail'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Página principal del marketplace con grid de productos.
   *
   * Lógica: Renderiza el listado de productos con filtros laterales,
   *   barra de búsqueda y paginación. Los filtros activos se muestran
   *   como chips removibles. El grid es responsive: 4 cols desktop,
   *   2 cols tablet, 1 col móvil.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP con query params para filtros.
   *
   * @return array
   *   Render array con #theme 'comercio_marketplace'.
   */
  public function marketplace(Request $request): array {
    $tenant_id = $this->getTenantId();

    // Recoger filtros de los query parameters
    $filters = [
      'category_id' => $request->query->get('category'),
      'brand_id' => $request->query->get('brand'),
      'price_min' => $request->query->get('price_min'),
      'price_max' => $request->query->get('price_max'),
      'merchant_id' => $request->query->get('merchant'),
      'in_stock_only' => $request->query->get('in_stock'),
    ];

    // Limpiar filtros vacíos
    $filters = array_filter($filters, fn($v) => $v !== NULL && $v !== '');

    $sort = $request->query->get('sort', 'newest');
    $page = max(0, (int) $request->query->get('page', 0));

    // Obtener productos del marketplace
    $result = $this->marketplaceService->getMarketplaceProducts(
      $tenant_id, $filters, $sort, $page
    );

    // Obtener comercios para el filtro lateral
    $merchants = $this->marketplaceService->getMerchants($tenant_id);

    // Obtener categorías para el filtro lateral
    $categories = $this->getCategories();

    return [
      '#theme' => 'comercio_marketplace',
      '#products' => $result['products'],
      '#categories' => $categories,
      '#brands' => [],
      '#merchants' => $merchants,
      '#current_filters' => $filters,
      '#total_results' => $result['total'],
      '#pager' => [
        'page' => $result['page'],
        'per_page' => $result['per_page'],
        'total_pages' => $result['total_pages'],
      ],
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/marketplace',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args', 'user.permissions'],
        'tags' => ['product_retail_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Página pública de un comercio con sus productos.
   *
   * Lógica: Muestra el perfil público del comercio (logo, descripción,
   *   horarios, ubicación en mapa) con un grid de sus productos activos.
   *
   * @param string $merchant_slug
   *   Slug URL del comercio.
   *
   * @return array
   *   Render array con el detalle del comercio.
   */
  public function merchantPage(string $merchant_slug): array {
    $tenant_id = $this->getTenantId();
    $merchant = $this->marketplaceService->getMerchantBySlug($tenant_id, $merchant_slug);

    if (!$merchant) {
      throw new NotFoundHttpException();
    }

    // Obtener productos del comercio
    $result = $this->marketplaceService->getMarketplaceProducts(
      $tenant_id,
      ['merchant_id' => $merchant->id()],
      'newest'
    );

    return [
      '#theme' => 'comercio_product_detail',
      '#product' => NULL,
      '#variations' => [],
      '#merchant' => $merchant,
      '#related_products' => $result['products'],
      '#reviews' => [],
      '#review_summary' => NULL,
      '#schema_json_ld' => '',
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/marketplace',
        ],
      ],
    ];
  }

  /**
   * Página de detalle de un producto.
   *
   * Lógica: Muestra el producto completo con galería de imágenes,
   *   selector de variaciones, precio, descripción, información
   *   del comercio, y Schema.org JSON-LD para SEO.
   *
   * @param int $product_retail
   *   ID del producto.
   *
   * @return array
   *   Render array con el detalle del producto.
   */
  public function productDetail(int $product_retail): array {
    $detail = $this->productRetailService->getProductDetail($product_retail);

    if (!$detail) {
      throw new NotFoundHttpException();
    }

    return [
      '#theme' => 'comercio_product_detail',
      '#product' => $detail['product'],
      '#variations' => $detail['variations'],
      '#merchant' => $detail['merchant'],
      '#related_products' => [],
      '#reviews' => [],
      '#review_summary' => NULL,
      '#schema_json_ld' => $detail['schema_json_ld'],
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/marketplace',
        ],
      ],
      '#cache' => [
        'tags' => ['product_retail:' . $product_retail],
      ],
    ];
  }

  /**
   * Obtiene el tenant_id del contexto actual.
   *
   * Lógica: Intenta obtener el tenant del servicio de contexto
   *   de ecosistema_jaraba_core. Si no está disponible, devuelve 1
   *   como fallback para desarrollo.
   *
   * @return int
   *   ID del tenant actual.
   */
  protected function getTenantId(): int {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    return $tenantId ?? 1;
  }

  /**
   * Obtiene el árbol de categorías de comercio.
   *
   * @return array
   *   Array jerárquico de términos de taxonomía.
   */
  protected function getCategories(): array {
    $term_storage = $this->entityTypeManager()->getStorage('taxonomy_term');

    $ids = $term_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('vid', 'comercio_category')
      ->condition('parent', 0)
      ->sort('weight', 'ASC')
      ->execute();

    return $ids ? array_values($term_storage->loadMultiple($ids)) : [];
  }

}
