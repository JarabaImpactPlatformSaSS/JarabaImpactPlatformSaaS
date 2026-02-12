<?php

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\MerchantDashboardService;
use Drupal\jaraba_comercio_conecta\Service\ProductRetailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller del portal privado del comerciante (/mi-comercio).
 *
 * Estructura: Gestiona las rutas /mi-comercio, /mi-comercio/productos,
 *   /mi-comercio/stock, /mi-comercio/analiticas y /mi-comercio/perfil.
 *   Devuelve render arrays con #theme apuntando a los templates del módulo.
 *
 * Lógica: Todas las rutas requieren autenticación y permiso
 *   'edit own merchant profile'. El comerciante solo ve datos de su
 *   propio perfil (verificado via MerchantDashboardService).
 */
class MerchantPortalController extends ControllerBase {

  /**
   * Constructor del controller.
   *
   * @param \Drupal\jaraba_comercio_conecta\Service\MerchantDashboardService $dashboardService
   *   Servicio del dashboard del comerciante.
   * @param \Drupal\jaraba_comercio_conecta\Service\ProductRetailService $productService
   *   Servicio de productos retail.
   */
  public function __construct(
    protected MerchantDashboardService $dashboardService,
    protected ProductRetailService $productService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.merchant_dashboard'),
      $container->get('jaraba_comercio_conecta.product_retail'),
    );
  }

  /**
   * Dashboard principal del comerciante con KPIs y alertas.
   *
   * Lógica: Muestra KPIs (ventas, pedidos, valoración, productos),
   *   alertas de stock bajo, pedidos recientes y banner de verificación
   *   si el comercio aún no está aprobado.
   *
   * @return array
   *   Render array con #theme 'comercio_merchant_dashboard'.
   */
  public function dashboard(): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    $kpis = $this->dashboardService->getMerchantKpis($merchant);
    $stock_alerts = $this->dashboardService->getStockAlerts($merchant);

    return [
      '#theme' => 'comercio_merchant_dashboard',
      '#merchant' => $merchant,
      '#kpis' => $kpis,
      '#stock_alerts' => $stock_alerts,
      '#recent_orders' => [],
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/merchant-portal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['merchant_profile:' . $merchant->id()],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Listado de productos del comerciante con filtros.
   *
   * Lógica: Muestra la tabla de productos del comerciante con
   *   pestañas de filtro por estado (todos, activos, borrador,
   *   agotados). Incluye botón para añadir producto nuevo.
   *
   * @return array
   *   Render array con #theme 'comercio_merchant_products'.
   */
  public function products(): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    $products = $this->dashboardService->getMerchantProducts($merchant);

    return [
      '#theme' => 'comercio_merchant_products',
      '#merchant' => $merchant,
      '#products' => $products,
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/merchant-portal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [
          'merchant_profile:' . $merchant->id(),
          'product_retail_list',
        ],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Formulario para añadir un nuevo producto.
   *
   * Lógica: Crea una nueva entidad product_retail con el merchant_id
   *   pre-rellenado y renderiza el formulario de edición.
   *
   * @return array
   *   Render array con el formulario de la entidad.
   */
  public function productAdd(): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    $product = $this->entityTypeManager()
      ->getStorage('product_retail')
      ->create([
        'merchant_id' => $merchant->id(),
        'tenant_id' => $merchant->get('tenant_id')->value,
      ]);

    return $this->entityFormBuilder()->getForm($product, 'add');
  }

  /**
   * Página de gestión de inventario/stock.
   *
   * Lógica: Muestra tabla de productos con stock bajo, formulario
   *   rápido de actualización de stock por ubicación. Las alertas
   *   de stock se calculan comparando stock_quantity con low_stock_threshold.
   *
   * @return array
   *   Render array con dashboard de stock.
   */
  public function stock(): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    $stock_alerts = $this->dashboardService->getStockAlerts($merchant);
    $products = $this->dashboardService->getMerchantProducts($merchant);

    return [
      '#theme' => 'comercio_merchant_dashboard',
      '#merchant' => $merchant,
      '#kpis' => [],
      '#stock_alerts' => $stock_alerts,
      '#recent_orders' => [],
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/merchant-portal',
        ],
      ],
    ];
  }

  /**
   * Página de analíticas del comerciante.
   *
   * Lógica: Muestra KPIs con selector de periodo (7d, 30d, 90d),
   *   tabla de productos más vendidos y gráficos de tendencia.
   *   Los datos se obtienen de MerchantDashboardService.
   *
   * @return array
   *   Render array con #theme 'comercio_merchant_analytics'.
   */
  public function analytics(): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    $kpis = $this->dashboardService->getMerchantKpis($merchant);

    return [
      '#theme' => 'comercio_merchant_analytics',
      '#merchant' => $merchant,
      '#kpis' => $kpis,
      '#top_products' => [],
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/merchant-portal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['merchant_profile:' . $merchant->id()],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Página de edición del perfil del comercio.
   *
   * Lógica: Renderiza el formulario de edición del MerchantProfile
   *   del usuario actual. El comerciante puede actualizar datos de
   *   contacto, dirección, horarios, logo y galería.
   *
   * @return array
   *   Render array con el formulario de la entidad.
   */
  public function profile(): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    return $this->entityFormBuilder()->getForm($merchant, 'edit');
  }

}
