<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\MerchantDashboardService;
use Drupal\jaraba_comercio_conecta\Service\MerchantPayoutService;
use Drupal\jaraba_comercio_conecta\Service\OrderRetailService;
use Drupal\jaraba_comercio_conecta\Service\ProductRetailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
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
   * @param \Drupal\jaraba_comercio_conecta\Service\OrderRetailService $orderService
   *   Servicio de pedidos retail.
   * @param \Drupal\jaraba_comercio_conecta\Service\MerchantPayoutService $payoutService
   *   Servicio de pagos y comisiones del comerciante.
   */
  public function __construct(
    protected MerchantDashboardService $dashboardService,
    protected ProductRetailService $productService,
    protected OrderRetailService $orderService,
    protected MerchantPayoutService $payoutService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.merchant_dashboard'),
      $container->get('jaraba_comercio_conecta.product_retail'),
      $container->get('jaraba_comercio_conecta.order_retail'),
      $container->get('jaraba_comercio_conecta.merchant_payout'),
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
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['merchant_profile:' . $merchant->id(), 'product_retail_list'],
        'max-age' => 60,
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

  /**
   * Listado de pedidos recibidos por el comerciante.
   *
   * Estructura: Muestra tabla de pedidos (suborders) asignados al
   *   comerciante con numero de pedido, cliente, total, estado,
   *   estado de pago y fecha. Incluye paginacion.
   *
   * Logica: Obtiene el perfil del comerciante actual, carga los
   *   suborders via OrderRetailService::getMerchantOrders() y
   *   serializa los datos para el template.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request con parametro page en query string.
   *
   * @return array
   *   Render array con #theme 'comercio_merchant_orders'.
   */
  public function merchantOrders(Request $request): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    $page = max(0, (int) $request->query->get('page', 0));
    $result = $this->orderService->getMerchantOrders((int) $merchant->id(), $page);

    $orders = [];
    foreach ($result['suborders'] as $suborder) {
      $orders[] = [
        'id' => (int) $suborder->id(),
        'order_number' => $suborder->get('order_number')->value,
        'customer_name' => $suborder->get('customer_name')->value ?? '',
        'total' => (float) $suborder->get('total')->value,
        'status' => $suborder->get('status')->value,
        'payment_status' => $suborder->get('payment_status')->value,
        'created' => $suborder->get('created')->value,
      ];
    }

    return [
      '#theme' => 'comercio_merchant_orders',
      '#merchant' => $merchant,
      '#orders' => $orders,
      '#total' => $result['total'],
      '#page' => $result['page'],
      '#total_pages' => $result['total_pages'],
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/merchant-portal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [
          'merchant_profile:' . $merchant->id(),
          'suborder_retail_list',
        ],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Historial de pagos y comisiones del comerciante.
   *
   * Estructura: Muestra tarjetas resumen (ingresos totales, comisiones,
   *   pagos recibidos, pendiente), tabla de pagos recientes y
   *   placeholder para grafico de ingresos mensuales.
   *
   * Logica: Obtiene el perfil del comerciante, carga resumen financiero
   *   y pagos recientes via MerchantPayoutService. Calcula totales
   *   acumulados de ingresos, comisiones y netos.
   *
   * @return array
   *   Render array con #theme 'comercio_merchant_payments'.
   */
  public function merchantPayments(): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    $summary = $this->payoutService->getPayoutSummary((int) $merchant->id());
    $recent_payouts = $this->payoutService->getRecentPayouts((int) $merchant->id());
    $monthly_revenue = $this->payoutService->getMonthlyRevenue((int) $merchant->id());

    return [
      '#theme' => 'comercio_merchant_payments',
      '#merchant' => $merchant,
      '#summary' => $summary,
      '#recent_payouts' => $recent_payouts,
      '#monthly_revenue' => $monthly_revenue,
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/merchant-portal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [
          'merchant_profile:' . $merchant->id(),
          'merchant_payout_list',
        ],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Pagina de configuracion del comercio.
   *
   * Estructura: Muestra secciones de configuracion del comercio:
   *   datos fiscales (NIF/CIF, razon social), horarios de apertura,
   *   zonas de envio y configuracion de Click & Collect.
   *
   * Logica: Carga el perfil del comerciante actual y renderiza
   *   el template con los datos de configuracion. Cada seccion
   *   es una tarjeta independiente con contenido editable.
   *
   * @return array
   *   Render array con #theme 'comercio_merchant_settings'.
   */
  public function merchantSettings(): array {
    $merchant = $this->dashboardService->getCurrentMerchantProfile();

    if (!$merchant) {
      throw new AccessDeniedHttpException($this->t('No tienes un perfil de comerciante asociado.'));
    }

    return [
      '#theme' => 'comercio_merchant_settings',
      '#merchant' => $merchant,
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/merchant-portal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['merchant_profile:' . $merchant->id()],
        'max-age' => 0,
      ],
    ];
  }

}
