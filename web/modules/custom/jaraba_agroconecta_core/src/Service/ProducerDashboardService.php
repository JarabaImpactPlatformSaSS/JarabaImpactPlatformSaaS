<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_agroconecta_core\Entity\OrderAgro;

/**
 * Servicio de agregación de datos para el dashboard del productor.
 *
 * PROPÓSITO:
 * Calcula KPIs, alertas, top productos y datos de gráficos
 * para el Portal del Productor de AgroConecta.
 *
 * MÉTRICAS:
 * - Ventas hoy/mes (subtotal de sub-pedidos)
 * - Pedidos pendientes (por estado)
 * - Alertas (stock bajo, pedidos sin confirmar >2h)
 * - Top productos (por cantidad vendida)
 * - Serie temporal de ventas (para Chart.js)
 */
class ProducerDashboardService
{

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountInterface $currentUser,
        protected Connection $database,
    ) {
    }

    /**
     * Obtiene los KPIs principales del dashboard productor.
     *
     * @param int $producerId
     *   ID del ProducerProfile.
     *
     * @return array
     *   Array con: sales_today, sales_month, pending_orders, avg_rating,
     *   orders_by_state, total_products.
     */
    public function getKpis(int $producerId): array
    {
        $suborderStorage = $this->entityTypeManager->getStorage('suborder_agro');
        $productStorage = $this->entityTypeManager->getStorage('product_agro');

        // Ventas hoy.
        $todayStart = strtotime('today midnight');
        $salesTodayIds = $suborderStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('producer_id', $producerId)
            ->condition('state', [OrderAgro::STATE_PAID, OrderAgro::STATE_PROCESSING, OrderAgro::STATE_READY, OrderAgro::STATE_SHIPPED, OrderAgro::STATE_DELIVERED, OrderAgro::STATE_COMPLETED], 'IN')
            ->condition('created', $todayStart, '>=')
            ->execute();

        $salesToday = 0;
        foreach ($suborderStorage->loadMultiple($salesTodayIds) as $sub) {
            $salesToday += (float) ($sub->get('subtotal')->value ?? 0);
        }

        // Ventas mes actual.
        $monthStart = strtotime('first day of this month midnight');
        $salesMonthIds = $suborderStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('producer_id', $producerId)
            ->condition('state', [OrderAgro::STATE_PAID, OrderAgro::STATE_PROCESSING, OrderAgro::STATE_READY, OrderAgro::STATE_SHIPPED, OrderAgro::STATE_DELIVERED, OrderAgro::STATE_COMPLETED], 'IN')
            ->condition('created', $monthStart, '>=')
            ->execute();

        $salesMonth = 0;
        foreach ($suborderStorage->loadMultiple($salesMonthIds) as $sub) {
            $salesMonth += (float) ($sub->get('subtotal')->value ?? 0);
        }

        // Pedidos por estado.
        $pendingStates = [OrderAgro::STATE_PENDING, OrderAgro::STATE_PAID, OrderAgro::STATE_PROCESSING, OrderAgro::STATE_READY];
        $ordersByState = [];
        foreach ($pendingStates as $state) {
            $count = $suborderStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('producer_id', $producerId)
                ->condition('state', $state)
                ->count()
                ->execute();
            $ordersByState[$state] = (int) $count;
        }

        $pendingOrders = array_sum($ordersByState);

        // Total productos activos.
        $totalProducts = (int) $productStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('uid', $this->getProducerUserId($producerId))
            ->condition('status', 1)
            ->count()
            ->execute();

        return [
            'sales_today' => round($salesToday, 2),
            'sales_month' => round($salesMonth, 2),
            'pending_orders' => $pendingOrders,
            'orders_by_state' => $ordersByState,
            'total_products' => $totalProducts,
            'avg_rating' => 0.0, // Se implementará con Doc 54 (Reviews)
        ];
    }

    /**
     * Obtiene alertas activas para el productor.
     *
     * @param int $producerId
     *   ID del ProducerProfile.
     *
     * @return array
     *   Array de alertas con: type, message, severity, action_url.
     */
    public function getAlerts(int $producerId): array
    {
        $alerts = [];
        $suborderStorage = $this->entityTypeManager->getStorage('suborder_agro');

        // Alerta: pedidos sin confirmar durante más de 2 horas.
        $twoHoursAgo = time() - 7200;
        $unconfirmedIds = $suborderStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('producer_id', $producerId)
            ->condition('state', OrderAgro::STATE_PAID)
            ->condition('created', $twoHoursAgo, '<')
            ->execute();

        if (!empty($unconfirmedIds)) {
            $alerts[] = [
                'type' => 'unconfirmed_orders',
                'message' => count($unconfirmedIds) . ' pedido(s) pendiente(s) de confirmar (> 2h)',
                'severity' => 'warning',
                'action_url' => '/productor/pedidos?state=paid',
                'count' => count($unconfirmedIds),
            ];
        }

        // Alerta: stock bajo (< 5 unidades).
        $productStorage = $this->entityTypeManager->getStorage('product_agro');
        $lowStockIds = $productStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('uid', $this->getProducerUserId($producerId))
            ->condition('status', 1)
            ->condition('stock_quantity', 5, '<')
            ->execute();

        if (!empty($lowStockIds)) {
            $lowStockProducts = $productStorage->loadMultiple($lowStockIds);
            $names = [];
            foreach (array_slice($lowStockProducts, 0, 3) as $product) {
                $names[] = $product->get('name')->value . ' (' . $product->get('stock_quantity')->value . ' uds)';
            }
            $alerts[] = [
                'type' => 'low_stock',
                'message' => 'Stock bajo: ' . implode(', ', $names),
                'severity' => 'warning',
                'action_url' => '/productor/productos',
                'count' => count($lowStockIds),
            ];
        }

        return $alerts;
    }

    /**
     * Obtiene los productos más vendidos del productor.
     *
     * @param int $producerId
     *   ID del ProducerProfile.
     * @param int $limit
     *   Número máximo de productos.
     *
     * @return array
     *   Array de productos con: title, quantity_sold, revenue.
     */
    public function getTopProducts(int $producerId, int $limit = 5): array
    {
        $itemStorage = $this->entityTypeManager->getStorage('order_item_agro');
        $productStorage = $this->entityTypeManager->getStorage('product_agro');

        // Obtener items de este productor en el último mes.
        $monthStart = strtotime('first day of this month midnight');
        $itemIds = $itemStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('producer_id', $producerId)
            ->condition('created', $monthStart, '>=')
            ->execute();

        // Agregar por producto.
        $productTotals = [];
        foreach ($itemStorage->loadMultiple($itemIds) as $item) {
            $productId = $item->get('product_id')->target_id;
            if (!isset($productTotals[$productId])) {
                $productTotals[$productId] = [
                    'title' => $item->get('title')->value,
                    'quantity_sold' => 0,
                    'revenue' => 0,
                ];
            }
            $productTotals[$productId]['quantity_sold'] += (int) $item->get('quantity')->value;
            $productTotals[$productId]['revenue'] += (float) $item->get('total_price')->value;
        }

        // Ordenar por cantidad vendida.
        uasort($productTotals, fn($a, $b) => $b['quantity_sold'] <=> $a['quantity_sold']);

        return array_slice(array_values($productTotals), 0, $limit);
    }

    /**
     * Obtiene datos de ventas diarias para el gráfico del dashboard.
     *
     * @param int $producerId
     *   ID del ProducerProfile.
     * @param int $days
     *   Número de días (default 7).
     *
     * @return array
     *   Array con: labels (fechas), data (importes).
     */
    public function getSalesChart(int $producerId, int $days = 7): array
    {
        $suborderStorage = $this->entityTypeManager->getStorage('suborder_agro');
        $labels = [];
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $dayStart = strtotime("-{$i} days midnight");
            $dayEnd = $dayStart + 86400;

            $labels[] = date('d/m', $dayStart);

            $dayIds = $suborderStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('producer_id', $producerId)
                ->condition('state', [OrderAgro::STATE_PAID, OrderAgro::STATE_PROCESSING, OrderAgro::STATE_READY, OrderAgro::STATE_SHIPPED, OrderAgro::STATE_DELIVERED, OrderAgro::STATE_COMPLETED], 'IN')
                ->condition('created', $dayStart, '>=')
                ->condition('created', $dayEnd, '<')
                ->execute();

            $dayTotal = 0;
            foreach ($suborderStorage->loadMultiple($dayIds) as $sub) {
                $dayTotal += (float) ($sub->get('subtotal')->value ?? 0);
            }
            $data[] = round($dayTotal, 2);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Obtiene los sub-pedidos pendientes del productor.
     *
     * @param int $producerId
     *   ID del ProducerProfile.
     * @param int $limit
     *   Límite de resultados.
     *
     * @return array
     *   Array de sub-pedidos serializados.
     */
    public function getPendingOrders(int $producerId, int $limit = 10): array
    {
        $suborderStorage = $this->entityTypeManager->getStorage('suborder_agro');
        $orderStorage = $this->entityTypeManager->getStorage('order_agro');

        $ids = $suborderStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('producer_id', $producerId)
            ->condition('state', [OrderAgro::STATE_PENDING, OrderAgro::STATE_PAID, OrderAgro::STATE_PROCESSING, OrderAgro::STATE_READY], 'IN')
            ->sort('created', 'DESC')
            ->range(0, $limit)
            ->execute();

        $result = [];
        foreach ($suborderStorage->loadMultiple($ids) as $suborder) {
            $order = $orderStorage->load($suborder->get('order_id')->target_id);
            $customerEmail = $order ? ($order->get('email')->value ?? '') : '';

            $result[] = [
                'id' => (int) $suborder->id(),
                'suborder_number' => $suborder->get('suborder_number')->value,
                'state' => $suborder->get('state')->value,
                'state_label' => OrderAgro::getStateLabels()[$suborder->get('state')->value] ?? $suborder->get('state')->value,
                'subtotal' => number_format((float) ($suborder->get('subtotal')->value ?? 0), 2, ',', '.') . ' €',
                'customer_email' => $customerEmail,
                'created' => date('d/m/Y H:i', (int) $suborder->get('created')->value),
            ];
        }

        return $result;
    }

    /**
     * Obtiene el user ID del owner de un ProducerProfile.
     */
    protected function getProducerUserId(int $producerId): int
    {
        $producer = $this->entityTypeManager->getStorage('producer_profile')->load($producerId);
        return $producer ? (int) $producer->getOwnerId() : 0;
    }

}
