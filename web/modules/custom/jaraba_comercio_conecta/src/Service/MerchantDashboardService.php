<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de dashboard para comerciantes de ComercioConecta.
 *
 * Estructura: Proporciona KPIs, alertas de stock, pedidos recientes
 *   y datos de gráficos para el panel del comerciante en /mi-comercio.
 *
 * Lógica: Todos los datos se filtran por merchant_id para que cada
 *   comerciante solo vea su propia información. Los KPIs se calculan
 *   en tiempo real contra las entidades de orden y producto.
 *   En el futuro (Fase 8) se usarán tablas de analíticas agregadas
 *   (AnalyticsDailyRetail) para rendimiento.
 */
class MerchantDashboardService {

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Usuario actual.
   * @param \Drupal\Core\Database\Connection $database
   *   Conexión a base de datos para consultas agregadas.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el perfil del comerciante del usuario actual.
   *
   * Lógica: Busca el MerchantProfile cuyo uid coincide con el
   *   usuario autenticado. Un usuario solo puede tener un perfil
   *   de comerciante activo.
   *
   * @return object|null
   *   Entidad MerchantProfile o NULL si el usuario no es comerciante.
   */
  public function getCurrentMerchantProfile(): ?object {
    $storage = $this->entityTypeManager->getStorage('merchant_profile');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $this->currentUser->id())
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Obtiene los KPIs del dashboard del comerciante.
   *
   * Lógica: Calcula indicadores clave de rendimiento:
   *   - total_products: productos activos del comerciante
   *   - stock_alerts: productos con stock bajo
   *   - average_rating: rating medio del comerciante
   *   - total_reviews: total de reseñas recibidas
   *   Los KPIs de ventas (ventas_hoy, ventas_mes, pedidos_pendientes)
   *   se añadirán en Fase 2 cuando existan las entidades de orden.
   *
   * @param int $merchant_id
   *   ID del perfil de comerciante.
   *
   * @return array
   *   Array asociativo con los KPIs.
   */
  public function getMerchantKpis(int $merchant_id): array {
    $product_storage = $this->entityTypeManager->getStorage('product_retail');

    // Total de productos activos
    $total_products = $product_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('merchant_id', $merchant_id)
      ->condition('status', ['active', 'paused'], 'IN')
      ->count()
      ->execute();

    // Productos con stock bajo
    $stock_alerts = $this->getStockAlerts($merchant_id);

    // Rating y reviews desde el perfil del comerciante
    $merchant = $this->entityTypeManager->getStorage('merchant_profile')->load($merchant_id);
    $average_rating = $merchant ? ($merchant->get('average_rating')->value ?: 0) : 0;
    $total_reviews = $merchant ? ($merchant->get('total_reviews')->value ?: 0) : 0;

    // KPIs de ventas: consultar entidades comercio_order con aggregate queries.
    $ventas_hoy = 0;
    $ventas_mes = 0;
    $pedidos_pendientes = 0;

    try {
      $order_storage = $this->entityTypeManager->getStorage('comercio_order');

      // Ventas de hoy: SUM(total_price) donde status=completed y created=hoy.
      $today_start = strtotime('today midnight');
      $result = $order_storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('total_price', 'SUM')
        ->condition('merchant_id', $merchant_id)
        ->condition('status', 'completed')
        ->condition('created', $today_start, '>=')
        ->execute();
      $ventas_hoy = (float) ($result[0]['total_price_sum'] ?? 0);

      // Ventas del mes: SUM(total_price) donde status=completed y created>=primer día del mes.
      $month_start = strtotime('first day of this month midnight');
      $result = $order_storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('total_price', 'SUM')
        ->condition('merchant_id', $merchant_id)
        ->condition('status', 'completed')
        ->condition('created', $month_start, '>=')
        ->execute();
      $ventas_mes = (float) ($result[0]['total_price_sum'] ?? 0);

      // Pedidos pendientes: COUNT donde status=pending.
      $pedidos_pendientes = (int) $order_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('merchant_id', $merchant_id)
        ->condition('status', 'pending')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando KPIs de ventas para merchant @id: @e', [
        '@id' => $merchant_id,
        '@e' => $e->getMessage(),
      ]);
    }

    return [
      'total_products' => (int) $total_products,
      'stock_alert_count' => count($stock_alerts),
      'average_rating' => round((float) $average_rating, 1),
      'total_reviews' => (int) $total_reviews,
      'ventas_hoy' => round($ventas_hoy, 2),
      'ventas_mes' => round($ventas_mes, 2),
      'pedidos_pendientes' => $pedidos_pendientes,
      'ingresos_mes' => round($ventas_mes, 2),
    ];
  }

  /**
   * Obtiene productos con stock bajo o agotado.
   *
   * Lógica: Busca productos cuyo stock_quantity es menor o igual
   *   al low_stock_threshold definido por el comerciante.
   *   Incluye también productos con stock = 0 (agotados).
   *
   * @param int $merchant_id
   *   ID del perfil de comerciante.
   *
   * @return array
   *   Array de entidades ProductRetail con stock bajo.
   */
  public function getStockAlerts(int $merchant_id): array {
    $storage = $this->entityTypeManager->getStorage('product_retail');

    // Productos agotados (stock = 0)
    $out_of_stock_ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('merchant_id', $merchant_id)
      ->condition('stock_quantity', 0)
      ->condition('status', 'archived', '<>')
      ->execute();

    // Productos con stock bajo (stock <= threshold, stock > 0)
    // Nota: comparar stock vs threshold requiere query directa
    // porque entity query no soporta comparación entre dos campos.
    // Por ahora usamos un threshold fijo de 5 unidades.
    $low_stock_ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('merchant_id', $merchant_id)
      ->condition('stock_quantity', 5, '<=')
      ->condition('stock_quantity', 0, '>')
      ->condition('status', 'archived', '<>')
      ->execute();

    $all_ids = array_unique(array_merge($out_of_stock_ids, $low_stock_ids));

    return $all_ids ? array_values($storage->loadMultiple($all_ids)) : [];
  }

  /**
   * Obtiene los productos del comerciante con filtros.
   *
   * @param int $merchant_id
   *   ID del perfil de comerciante.
   * @param array $filters
   *   Filtros: status, category_id, search.
   * @param int $page
   *   Página actual.
   * @param int $per_page
   *   Elementos por página.
   *
   * @return array
   *   Array con 'products', 'total', 'page', 'per_page'.
   */
  public function getMerchantProducts(int $merchant_id, array $filters = [], int $page = 0, int $per_page = 20): array {
    $storage = $this->entityTypeManager->getStorage('product_retail');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('merchant_id', $merchant_id);

    if (!empty($filters['status'])) {
      $query->condition('status', $filters['status']);
    }
    if (!empty($filters['category_id'])) {
      $query->condition('category_id', $filters['category_id']);
    }

    $count_query = clone $query;
    $total = $count_query->count()->execute();

    $query->sort('created', 'DESC')
      ->range($page * $per_page, $per_page);

    $ids = $query->execute();
    $products = $ids ? array_values($storage->loadMultiple($ids)) : [];

    return [
      'products' => $products,
      'total' => (int) $total,
      'page' => $page,
      'per_page' => $per_page,
    ];
  }

}
