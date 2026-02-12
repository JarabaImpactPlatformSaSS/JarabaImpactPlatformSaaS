<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Entity\OrderAgro;
use Drupal\jaraba_agroconecta_core\Service\OrderService;
use Drupal\jaraba_agroconecta_core\Service\ProducerDashboardService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del Portal del Productor de AgroConecta.
 *
 * PROPÓSITO:
 * Renderiza las páginas frontend del area privada del productor:
 * dashboard, pedidos, productos, finanzas y configuración.
 *
 * RUTAS:
 * - GET /productor → Dashboard principal
 * - GET /productor/pedidos → Lista sub-pedidos
 * - GET /productor/pedidos/{id} → Detalle sub-pedido
 * - GET /productor/productos → Gestión productos
 * - GET /productor/finanzas → Payouts y comisiones
 * - GET /productor/configuracion → Configuración perfil
 */
class ProducerPortalController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor del controlador.
     */
    public function __construct(
        protected ProducerDashboardService $dashboardService,
        protected OrderService $orderService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta.producer_dashboard_service'),
            $container->get('jaraba_agroconecta.order_service'),
        );
    }

    /**
     * Dashboard principal del productor.
     *
     * @return array
     *   Render array con KPIs, alertas, pedidos pendientes, gráfico y top productos.
     */
    public function dashboard(): array
    {
        $producerId = $this->getProducerId();
        if (!$producerId) {
            return $this->accessDenied();
        }

        $kpis = $this->dashboardService->getKpis($producerId);
        $alerts = $this->dashboardService->getAlerts($producerId);
        $pendingOrders = $this->dashboardService->getPendingOrders($producerId);
        $topProducts = $this->dashboardService->getTopProducts($producerId);
        $salesChart = $this->dashboardService->getSalesChart($producerId);

        return [
            '#theme' => 'agro_producer_dashboard',
            '#kpis' => $kpis,
            '#alerts' => $alerts,
            '#pending_orders' => $pendingOrders,
            '#top_products' => $topProducts,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.producer'],
                'drupalSettings' => [
                    'agroconecta' => [
                        'salesChart' => $salesChart,
                    ],
                ],
            ],
        ];
    }

    /**
     * Lista de sub-pedidos del productor.
     *
     * @return array
     *   Render array con sub-pedidos filtrados.
     */
    public function orders(): array
    {
        $producerId = $this->getProducerId();
        if (!$producerId) {
            return $this->accessDenied();
        }

        $result = $this->orderService->getSubordersByProducer($producerId, 50, 0);

        $orders = [];
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');

        foreach ($result['data'] as $suborder) {
            $order = $orderStorage->load($suborder->get('order_id')->target_id);
            $orders[] = [
                'id' => (int) $suborder->id(),
                'suborder_number' => $suborder->get('suborder_number')->value,
                'state' => $suborder->get('state')->value,
                'state_label' => OrderAgro::getStateLabels()[$suborder->get('state')->value] ?? $suborder->get('state')->value,
                'subtotal' => number_format((float) ($suborder->get('subtotal')->value ?? 0), 2, ',', '.') . ' €',
                'commission' => number_format((float) ($suborder->get('commission_amount')->value ?? 0), 2, ',', '.') . ' €',
                'payout' => number_format((float) ($suborder->get('producer_payout')->value ?? 0), 2, ',', '.') . ' €',
                'payout_state' => $suborder->get('payout_state')->value ?? 'pending',
                'customer_email' => $order ? ($order->get('email')->value ?? '') : '',
                'tracking_number' => $suborder->get('tracking_number')->value ?? '',
                'created' => date('d/m/Y H:i', (int) $suborder->get('created')->value),
            ];
        }

        return [
            '#theme' => 'agro_producer_orders',
            '#orders' => $orders,
            '#total' => $result['meta']['total'],
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.producer'],
            ],
        ];
    }

    /**
     * Detalle de un sub-pedido.
     *
     * @param int $suborder_id
     *   ID del sub-pedido.
     *
     * @return array
     *   Render array con detalle del sub-pedido.
     */
    public function orderDetail(int $suborder_id): array
    {
        $producerId = $this->getProducerId();
        if (!$producerId) {
            return $this->accessDenied();
        }

        $suborderStorage = $this->entityTypeManager()->getStorage('suborder_agro');
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
        $itemStorage = $this->entityTypeManager()->getStorage('order_item_agro');

        $suborder = $suborderStorage->load($suborder_id);
        if (!$suborder || (int) $suborder->get('producer_id')->target_id !== $producerId) {
            return $this->accessDenied();
        }

        $order = $orderStorage->load($suborder->get('order_id')->target_id);

        // Cargar items de este sub-pedido.
        $itemIds = $itemStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('suborder_id', $suborder_id)
            ->execute();

        $items = [];
        foreach ($itemStorage->loadMultiple($itemIds) as $item) {
            $items[] = [
                'title' => $item->get('title')->value,
                'sku' => $item->get('sku')->value,
                'quantity' => (int) $item->get('quantity')->value,
                'unit_price' => number_format((float) ($item->get('unit_price')->value ?? 0), 2, ',', '.') . ' €',
                'total_price' => number_format((float) ($item->get('total_price')->value ?? 0), 2, ',', '.') . ' €',
                'item_state' => $item->get('item_state')->value,
            ];
        }

        $suborderData = [
            'id' => (int) $suborder->id(),
            'suborder_number' => $suborder->get('suborder_number')->value,
            'state' => $suborder->get('state')->value,
            'state_label' => OrderAgro::getStateLabels()[$suborder->get('state')->value] ?? $suborder->get('state')->value,
            'subtotal' => number_format((float) ($suborder->get('subtotal')->value ?? 0), 2, ',', '.') . ' €',
            'commission_rate' => (float) ($suborder->get('commission_rate')->value ?? 0),
            'commission_amount' => number_format((float) ($suborder->get('commission_amount')->value ?? 0), 2, ',', '.') . ' €',
            'producer_payout' => number_format((float) ($suborder->get('producer_payout')->value ?? 0), 2, ',', '.') . ' €',
            'payout_state' => $suborder->get('payout_state')->value ?? 'pending',
            'tracking_number' => $suborder->get('tracking_number')->value ?? '',
            'tracking_url' => $suborder->get('tracking_url')->value ?? '',
            'shipped_at' => $suborder->get('shipped_at')->value ?? '',
            'delivered_at' => $suborder->get('delivered_at')->value ?? '',
            'created' => date('d/m/Y H:i', (int) $suborder->get('created')->value),
        ];

        $orderData = [];
        if ($order) {
            $orderData = [
                'order_number' => $order->get('order_number')->value,
                'customer_email' => $order->get('email')->value ?? '',
                'shipping_address' => $order->get('shipping_address')->value ?? '',
                'delivery_method' => $order->get('delivery_method')->value ?? '',
                'delivery_notes' => $order->get('delivery_notes')->value ?? '',
            ];
        }

        return [
            '#theme' => 'agro_producer_order_detail',
            '#suborder' => $suborderData,
            '#order' => $orderData,
            '#items' => $items,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.producer'],
            ],
        ];
    }

    /**
     * Gestión de productos del productor.
     *
     * @return array
     *   Render array con lista de productos.
     */
    public function products(): array
    {
        $producerId = $this->getProducerId();
        if (!$producerId) {
            return $this->accessDenied();
        }

        $productStorage = $this->entityTypeManager()->getStorage('product_agro');
        $userId = $this->getProducerUserId($producerId);

        $productIds = $productStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('uid', $userId)
            ->sort('created', 'DESC')
            ->execute();

        $products = [];
        foreach ($productStorage->loadMultiple($productIds) as $product) {
            $products[] = [
                'id' => (int) $product->id(),
                'name' => $product->get('name')->value,
                'sku' => $product->get('sku')->value ?? '',
                'price' => number_format((float) ($product->get('price')->value ?? 0), 2, ',', '.') . ' €',
                'stock' => (int) ($product->get('stock_quantity')->value ?? 0),
                'status' => (bool) $product->get('status')->value,
                'category' => $product->get('category')->value ?? '',
            ];
        }

        return [
            '#theme' => 'agro_producer_products',
            '#products' => $products,
            '#total' => count($products),
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.producer'],
            ],
        ];
    }

    /**
     * Página de finanzas y payouts del productor.
     *
     * @return array
     *   Render array con payouts, comisiones e historial.
     */
    public function payouts(): array
    {
        $producerId = $this->getProducerId();
        if (!$producerId) {
            return $this->accessDenied();
        }

        $suborderStorage = $this->entityTypeManager()->getStorage('suborder_agro');

        // Obtener sub-pedidos completados con payouts.
        $paidIds = $suborderStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('producer_id', $producerId)
            ->condition('state', [OrderAgro::STATE_DELIVERED, OrderAgro::STATE_COMPLETED], 'IN')
            ->sort('created', 'DESC')
            ->range(0, 50)
            ->execute();

        $payoutHistory = [];
        $totalRevenue = 0;
        $totalCommissions = 0;
        $totalPayouts = 0;

        foreach ($suborderStorage->loadMultiple($paidIds) as $suborder) {
            $subtotal = (float) ($suborder->get('subtotal')->value ?? 0);
            $commission = (float) ($suborder->get('commission_amount')->value ?? 0);
            $payout = (float) ($suborder->get('producer_payout')->value ?? 0);

            $totalRevenue += $subtotal;
            $totalCommissions += $commission;
            $totalPayouts += $payout;

            $payoutHistory[] = [
                'suborder_number' => $suborder->get('suborder_number')->value,
                'subtotal' => number_format($subtotal, 2, ',', '.') . ' €',
                'commission' => number_format($commission, 2, ',', '.') . ' €',
                'payout' => number_format($payout, 2, ',', '.') . ' €',
                'payout_state' => $suborder->get('payout_state')->value ?? 'pending',
                'stripe_transfer_id' => $suborder->get('stripe_transfer_id')->value ?? '',
                'created' => date('d/m/Y', (int) $suborder->get('created')->value),
            ];
        }

        return [
            '#theme' => 'agro_producer_payouts',
            '#total_revenue' => number_format($totalRevenue, 2, ',', '.') . ' €',
            '#total_commissions' => number_format($totalCommissions, 2, ',', '.') . ' €',
            '#total_payouts' => number_format($totalPayouts, 2, ',', '.') . ' €',
            '#payout_history' => $payoutHistory,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.producer'],
            ],
        ];
    }

    /**
     * Página de configuración del productor.
     *
     * @return array
     *   Render array con formulario de configuración.
     */
    public function settings(): array
    {
        $producerId = $this->getProducerId();
        if (!$producerId) {
            return $this->accessDenied();
        }

        $producerStorage = $this->entityTypeManager()->getStorage('producer_profile');
        $producer = $producerStorage->load($producerId);

        $producerData = [];
        if ($producer) {
            $producerData = [
                'id' => (int) $producer->id(),
                'name' => $producer->get('name')->value ?? '',
                'email' => $producer->get('email')->value ?? '',
                'phone' => $producer->get('phone')->value ?? '',
                'stripe_account_id' => $producer->get('stripe_account_id')->value ?? '',
                'description' => $producer->get('description')->value ?? '',
            ];
        }

        return [
            '#theme' => 'agro_producer_settings',
            '#producer' => $producerData,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.producer'],
            ],
        ];
    }

    /**
     * Obtiene el ID del ProducerProfile del usuario actual.
     */
    protected function getProducerId(): ?int
    {
        $producerStorage = $this->entityTypeManager()->getStorage('producer_profile');
        $ids = $producerStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('uid', $this->currentUser()->id())
            ->execute();

        return !empty($ids) ? (int) reset($ids) : NULL;
    }

    /**
     * Obtiene el user ID del owner de un ProducerProfile.
     */
    protected function getProducerUserId(int $producerId): int
    {
        $producer = $this->entityTypeManager()->getStorage('producer_profile')->load($producerId);
        return $producer ? (int) $producer->getOwnerId() : 0;
    }

    /**
     * Render array de acceso denegado.
     */
    protected function accessDenied(): array
    {
        return [
            '#markup' => '<div class="agro-portal__access-denied"><h2>' . $this->t('Acceso denegado') . '</h2><p>' . $this->t('No tienes un perfil de productor asociado.') . '</p></div>',
        ];
    }

}
