<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Entity\OrderAgro;
use Drupal\jaraba_agroconecta_core\Service\OrderService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del Portal del Cliente de AgroConecta.
 *
 * PROPÓSITO:
 * Renderiza las páginas frontend del area privada del cliente:
 * dashboard, historial de pedidos, detalle, direcciones y favoritos.
 *
 * RUTAS:
 * - GET /mi-cuenta → Dashboard
 * - GET /mi-cuenta/pedidos → Historial pedidos
 * - GET /mi-cuenta/pedidos/{number} → Detalle pedido
 * - GET /mi-cuenta/direcciones → Gestión direcciones
 * - GET /mi-cuenta/favoritos → Productos favoritos
 */
class CustomerPortalController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Constructor del controlador.
     */
    public function __construct(
        protected OrderService $orderService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta.order_service'),
        );
    }

    /**
     * Dashboard principal del cliente.
     *
     * @return array
     *   Render array con resumen, pedido activo, comprar de nuevo.
     */
    public function dashboard(): array
    {
        $userId = (int) $this->currentUser()->id();
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');

        // Pedido activo (no completado ni cancelado).
        $activeOrderIds = $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('customer_id', $userId)
            ->condition('state', [OrderAgro::STATE_COMPLETED, OrderAgro::STATE_CANCELLED, OrderAgro::STATE_RETURNED], 'NOT IN')
            ->sort('created', 'DESC')
            ->range(0, 1)
            ->execute();

        $activeOrder = NULL;
        if (!empty($activeOrderIds)) {
            $order = $orderStorage->load(reset($activeOrderIds));
            if ($order) {
                $activeOrder = [
                    'order_number' => $order->get('order_number')->value,
                    'state' => $order->get('state')->value,
                    'state_label' => OrderAgro::getStateLabels()[$order->get('state')->value] ?? $order->get('state')->value,
                    'total' => $order->getFormattedTotal(),
                    'item_count' => $this->getItemCount((int) $order->id()),
                ];
            }
        }

        // Estadísticas rápidas.
        $totalOrders = (int) $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('customer_id', $userId)
            ->count()
            ->execute();

        // Productos comprados recientemente (para "comprar de nuevo").
        $recentProducts = $this->getRecentPurchases($userId, 4);

        return [
            '#theme' => 'agro_customer_dashboard',
            '#active_order' => $activeOrder,
            '#total_orders' => $totalOrders,
            '#recent_products' => $recentProducts,
            '#user_name' => $this->currentUser()->getDisplayName(),
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.customer'],
            ],
        ];
    }

    /**
     * Historial de pedidos del cliente.
     *
     * @return array
     *   Render array con lista de pedidos.
     */
    public function orders(): array
    {
        $userId = (int) $this->currentUser()->id();
        $result = $this->orderService->getOrdersByCustomer($userId, 20, 0);

        $orders = [];
        foreach ($result['data'] as $order) {
            $orders[] = [
                'order_number' => $order->get('order_number')->value,
                'state' => $order->get('state')->value,
                'state_label' => OrderAgro::getStateLabels()[$order->get('state')->value] ?? $order->get('state')->value,
                'total' => $order->getFormattedTotal(),
                'item_count' => $this->getItemCount((int) $order->id()),
                'payment_state' => $order->get('payment_state')->value ?? '',
                'created' => date('d/m/Y H:i', (int) $order->get('created')->value),
            ];
        }

        return [
            '#theme' => 'agro_customer_orders',
            '#orders' => $orders,
            '#total' => $result['meta']['total'],
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.customer'],
            ],
        ];
    }

    /**
     * Detalle de un pedido del cliente.
     *
     * @param string $order_number
     *   Número del pedido.
     *
     * @return array
     *   Render array con detalle del pedido.
     */
    public function orderDetail(string $order_number): array
    {
        $userId = (int) $this->currentUser()->id();
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
        $itemStorage = $this->entityTypeManager()->getStorage('order_item_agro');

        // Buscar pedido por número.
        $ids = $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('order_number', $order_number)
            ->condition('customer_id', $userId)
            ->execute();

        if (empty($ids)) {
            return [
                '#markup' => '<div class="customer-portal__not-found"><h2>' . $this->t('Pedido no encontrado') . '</h2></div>',
            ];
        }

        $order = $orderStorage->load(reset($ids));

        // Cargar items.
        $itemIds = $itemStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('order_id', $order->id())
            ->execute();

        $items = [];
        foreach ($itemStorage->loadMultiple($itemIds) as $item) {
            $items[] = [
                'title' => $item->get('title')->value,
                'quantity' => (int) $item->get('quantity')->value,
                'unit_price' => number_format((float) ($item->get('unit_price')->value ?? 0), 2, ',', '.') . ' €',
                'total_price' => number_format((float) ($item->get('total_price')->value ?? 0), 2, ',', '.') . ' €',
            ];
        }

        // Timeline de estados.
        $stateTimeline = $this->buildStateTimeline($order);

        $orderData = [
            'order_number' => $order->get('order_number')->value,
            'state' => $order->get('state')->value,
            'state_label' => OrderAgro::getStateLabels()[$order->get('state')->value] ?? $order->get('state')->value,
            'total' => $order->getFormattedTotal(),
            'subtotal' => number_format((float) ($order->get('subtotal')->value ?? 0), 2, ',', '.') . ' €',
            'shipping_total' => number_format((float) ($order->get('shipping_total')->value ?? 0), 2, ',', '.') . ' €',
            'delivery_method' => $order->get('delivery_method')->value ?? '',
            'shipping_address' => $order->get('shipping_address')->value ?? '',
            'payment_state' => $order->get('payment_state')->value ?? '',
            'created' => date('d/m/Y H:i', (int) $order->get('created')->value),
            'can_cancel' => in_array($order->get('state')->value, [OrderAgro::STATE_PENDING, OrderAgro::STATE_PAID]),
        ];

        return [
            '#theme' => 'agro_customer_order_detail',
            '#order' => $orderData,
            '#items' => $items,
            '#timeline' => $stateTimeline,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.customer'],
            ],
        ];
    }

    /**
     * Gestión de direcciones del cliente.
     *
     * @return array
     *   Render array con direcciones guardadas.
     */
    public function addresses(): array
    {
        // Las direcciones se almacenan en los pedidos anteriores.
        // Extraer direcciones únicas de pedidos completados.
        $userId = (int) $this->currentUser()->id();
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');

        $ids = $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('customer_id', $userId)
            ->sort('created', 'DESC')
            ->range(0, 20)
            ->execute();

        $uniqueAddresses = [];
        foreach ($orderStorage->loadMultiple($ids) as $order) {
            $address = $order->get('shipping_address')->value ?? '';
            if ($address && !in_array($address, array_column($uniqueAddresses, 'address'))) {
                $uniqueAddresses[] = [
                    'address' => $address,
                    'delivery_method' => $order->get('delivery_method')->value ?? '',
                    'used_in' => $order->get('order_number')->value,
                ];
            }
        }

        return [
            '#theme' => 'agro_customer_addresses',
            '#addresses' => $uniqueAddresses,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.customer'],
            ],
        ];
    }

    /**
     * Productos favoritos del cliente.
     *
     * @return array
     *   Render array con productos favoritos.
     */
    public function favorites(): array
    {
        // Por ahora se basa en productos de pedidos anteriores.
        // Se expandirá con Flag module en fase futura.
        $userId = (int) $this->currentUser()->id();
        $recentProducts = $this->getRecentPurchases($userId, 12);

        return [
            '#theme' => 'agro_customer_favorites',
            '#products' => $recentProducts,
            '#attached' => [
                'library' => ['jaraba_agroconecta_core/agroconecta.customer'],
            ],
        ];
    }

    /**
     * Obtiene productos de compras recientes del cliente.
     */
    protected function getRecentPurchases(int $userId, int $limit): array
    {
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
        $itemStorage = $this->entityTypeManager()->getStorage('order_item_agro');

        $orderIds = $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('customer_id', $userId)
            ->sort('created', 'DESC')
            ->range(0, 10)
            ->execute();

        $products = [];
        $seen = [];
        foreach ($orderIds as $orderId) {
            $itemIds = $itemStorage->getQuery()
                ->accessCheck(FALSE)
                ->condition('order_id', $orderId)
                ->execute();

            foreach ($itemStorage->loadMultiple($itemIds) as $item) {
                $productId = $item->get('product_id')->target_id;
                if (!in_array($productId, $seen) && count($products) < $limit) {
                    $seen[] = $productId;
                    $products[] = [
                        'product_id' => $productId,
                        'title' => $item->get('title')->value,
                        'price' => number_format((float) ($item->get('unit_price')->value ?? 0), 2, ',', '.') . ' €',
                    ];
                }
            }
        }

        return $products;
    }

    /**
     * Obtiene el número de items de un pedido.
     */
    protected function getItemCount(int $orderId): int
    {
        return (int) $this->entityTypeManager()->getStorage('order_item_agro')
            ->getQuery()
            ->accessCheck(FALSE)
            ->condition('order_id', $orderId)
            ->count()
            ->execute();
    }

    /**
     * Construye una timeline de estados para el pedido.
     */
    protected function buildStateTimeline($order): array
    {
        $currentState = $order->get('state')->value;
        $stateOrder = [
            OrderAgro::STATE_PENDING => ['label' => 'Pendiente', 'icon' => '🕐'],
            OrderAgro::STATE_PAID => ['label' => 'Pagado', 'icon' => '💳'],
            OrderAgro::STATE_PROCESSING => ['label' => 'En preparación', 'icon' => '📦'],
            OrderAgro::STATE_SHIPPED => ['label' => 'Enviado', 'icon' => '🚚'],
            OrderAgro::STATE_DELIVERED => ['label' => 'Entregado', 'icon' => '✅'],
        ];

        $timeline = [];
        $reached = FALSE;
        foreach ($stateOrder as $state => $info) {
            if ($state === $currentState) {
                $reached = TRUE;
                $timeline[] = [
                    'state' => $state,
                    'label' => $info['label'],
                    'icon' => $info['icon'],
                    'status' => 'current',
                ];
            } elseif (!$reached) {
                $timeline[] = [
                    'state' => $state,
                    'label' => $info['label'],
                    'icon' => $info['icon'],
                    'status' => 'completed',
                ];
            } else {
                $timeline[] = [
                    'state' => $state,
                    'label' => $info['label'],
                    'icon' => $info['icon'],
                    'status' => 'pending',
                ];
            }
        }

        return $timeline;
    }

}
