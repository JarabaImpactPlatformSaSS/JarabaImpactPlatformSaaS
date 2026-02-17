<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\AgroConectaFeatureGateService;
use Drupal\jaraba_agroconecta_core\Entity\OrderAgro;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión del ciclo de vida de pedidos AgroConecta.
 *
 * PROPÓSITO:
 * Centraliza la creación, transición de estados y consulta de pedidos.
 * Crea automáticamente sub-pedidos (SuborderAgro) agrupados por productor.
 * Integra FeatureGateService para verificar limites de pedidos por plan.
 *
 * FLUJO:
 * 1. Cart items → createOrderFromCart() → OrderAgro + OrderItemAgro[] + SuborderAgro[]
 * 2. transitionState() → valida transiciones y actualiza estado
 * 3. calculateTotals() → recalcula subtotales, envío, impuestos y total
 */
class OrderService
{

    /**
     * Transiciones de estado válidas.
     */
    private const STATE_TRANSITIONS = [
        OrderAgro::STATE_DRAFT => [OrderAgro::STATE_PENDING, OrderAgro::STATE_CANCELLED],
        OrderAgro::STATE_PENDING => [OrderAgro::STATE_PAID, OrderAgro::STATE_CANCELLED],
        OrderAgro::STATE_PAID => [OrderAgro::STATE_PROCESSING],
        OrderAgro::STATE_PROCESSING => [OrderAgro::STATE_READY],
        OrderAgro::STATE_READY => [OrderAgro::STATE_SHIPPED, OrderAgro::STATE_PICKED_UP],
        OrderAgro::STATE_SHIPPED => [OrderAgro::STATE_DELIVERED, OrderAgro::STATE_RETURNED],
        OrderAgro::STATE_PICKED_UP => [OrderAgro::STATE_COMPLETED],
        OrderAgro::STATE_DELIVERED => [OrderAgro::STATE_COMPLETED, OrderAgro::STATE_RETURN_REQUESTED],
        OrderAgro::STATE_RETURN_REQUESTED => [OrderAgro::STATE_RETURNED, OrderAgro::STATE_COMPLETED],
    ];

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountInterface $currentUser,
        protected LoggerInterface $logger,
        protected AgroConectaFeatureGateService $featureGate,
    ) {
    }

    /**
     * Crea un pedido a partir de items del carrito.
     *
     * @param array $cartItems
     *   Array de items con: product_id, quantity, unit_price, producer_id, title, sku.
     * @param array $customerData
     *   Datos del cliente: email, phone, billing_address, shipping_address,
     *   delivery_method, delivery_notes, tenant_id.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\OrderAgro|null
     *   El pedido creado o NULL si falla.
     */
    public function createOrderFromCart(array $cartItems, array $customerData): ?OrderAgro
    {
        try {
            // Verificar limites de pedidos por productor (cada productor tiene su gate).
            $producerIds = array_unique(array_column($cartItems, 'producer_id'));
            foreach ($producerIds as $producerId) {
                $gateResult = $this->featureGate->checkAndFire((int) $producerId, 'orders_per_month');
                if (!$gateResult->isAllowed()) {
                    $this->logger->warning('Pedido denegado: productor @id alcanzo limite de pedidos mensuales.', [
                        '@id' => $producerId,
                    ]);
                    // Registramos pero no bloqueamos — el limite es del productor, no del comprador.
                    // El productor recibira notificacion de upgrade.
                }
            }

            $orderStorage = $this->entityTypeManager->getStorage('order_agro');
            $itemStorage = $this->entityTypeManager->getStorage('order_item_agro');
            $suborderStorage = $this->entityTypeManager->getStorage('suborder_agro');

            // Crear pedido maestro.
            /** @var \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $order */
            $order = $orderStorage->create([
                'order_number' => OrderAgro::generateOrderNumber(),
                'tenant_id' => $customerData['tenant_id'] ?? NULL,
                'customer_id' => $this->currentUser->id(),
                'email' => $customerData['email'] ?? '',
                'phone' => $customerData['phone'] ?? '',
                'state' => OrderAgro::STATE_DRAFT,
                'billing_address' => json_encode($customerData['billing_address'] ?? []),
                'shipping_address' => json_encode($customerData['shipping_address'] ?? []),
                'delivery_method' => $customerData['delivery_method'] ?? 'shipping',
                'delivery_notes' => $customerData['delivery_notes'] ?? '',
                'currency' => 'EUR',
                'payment_state' => 'pending',
            ]);
            $order->save();

            // Agrupar items por productor.
            $itemsByProducer = [];
            foreach ($cartItems as $item) {
                $producerId = $item['producer_id'];
                $itemsByProducer[$producerId][] = $item;
            }

            // Crear sub-pedidos y items.
            $subtotal = 0;
            $producerIndex = 1;

            foreach ($itemsByProducer as $producerId => $producerItems) {
                // Crear sub-pedido para este productor.
                $suborderSubtotal = 0;
                $suborder = $suborderStorage->create([
                    'suborder_number' => $order->get('order_number')->value . '-P' . $producerIndex,
                    'order_id' => $order->id(),
                    'producer_id' => $producerId,
                    'state' => OrderAgro::STATE_DRAFT,
                    'tenant_id' => $customerData['tenant_id'] ?? NULL,
                    'commission_rate' => 5.00,
                ]);
                $suborder->save();

                foreach ($producerItems as $item) {
                    $totalPrice = round(($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0), 2);
                    $taxAmount = round($totalPrice * 0.10, 2);

                    $orderItem = $itemStorage->create([
                        'order_id' => $order->id(),
                        'suborder_id' => $suborder->id(),
                        'product_id' => $item['product_id'],
                        'producer_id' => $producerId,
                        'title' => $item['title'] ?? '',
                        'sku' => $item['sku'] ?? '',
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'total_price' => $totalPrice,
                        'tax_rate' => 10.00,
                        'tax_amount' => $taxAmount,
                        'item_state' => 'pending',
                    ]);
                    $orderItem->save();

                    $suborderSubtotal += $totalPrice;
                }

                // Actualizar subtotal del sub-pedido y calcular comisión.
                // Calcular comision segun plan del productor via FeatureGate.
                $commissionRate = $this->featureGate->getCommissionRate((int) $producerId);
                $suborder->set('subtotal', $suborderSubtotal);
                $suborder->set('commission_rate', $commissionRate);
                $suborder->calculateCommission($commissionRate);
                $suborder->save();

                $subtotal += $suborderSubtotal;
                $producerIndex++;
            }

            // Actualizar totales del pedido maestro.
            $taxTotal = round($subtotal * 0.10, 2);
            $order->set('subtotal', $subtotal);
            $order->set('tax_total', $taxTotal);
            $order->set('total', $subtotal + $taxTotal);
            $order->save();

            $this->logger->info('Pedido @number creado con @items items de @producers productores.', [
                '@number' => $order->get('order_number')->value,
                '@items' => count($cartItems),
                '@producers' => count($itemsByProducer),
            ]);

            return $order;
        } catch (\Exception $e) {
            $this->logger->error('Error al crear pedido: @message', [
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Transiciona el estado de un pedido.
     *
     * @param int $orderId
     *   ID del pedido.
     * @param string $newState
     *   Nuevo estado.
     *
     * @return bool
     *   TRUE si la transición fue exitosa.
     */
    public function transitionState(int $orderId, string $newState): bool
    {
        try {
            $orderStorage = $this->entityTypeManager->getStorage('order_agro');
            /** @var \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $order */
            $order = $orderStorage->load($orderId);

            if (!$order) {
                return FALSE;
            }

            $currentState = $order->get('state')->value;
            $allowedTransitions = self::STATE_TRANSITIONS[$currentState] ?? [];

            if (!in_array($newState, $allowedTransitions)) {
                $this->logger->warning('Transición no permitida: @current → @new para pedido @id.', [
                    '@current' => $currentState,
                    '@new' => $newState,
                    '@id' => $orderId,
                ]);
                return FALSE;
            }

            $order->set('state', $newState);

            // Acciones especiales por transición.
            if ($newState === OrderAgro::STATE_PAID) {
                $order->set('placed_at', date('Y-m-d\TH:i:s'));
                $order->set('payment_state', 'paid');
            } elseif ($newState === OrderAgro::STATE_COMPLETED) {
                $order->set('completed_at', date('Y-m-d\TH:i:s'));
            } elseif ($newState === OrderAgro::STATE_CANCELLED) {
                $order->set('payment_state', 'refunded');
            }

            $order->save();

            $this->logger->info('Pedido @id transicionado: @from → @to.', [
                '@id' => $orderId,
                '@from' => $currentState,
                '@to' => $newState,
            ]);

            return TRUE;
        } catch (\Exception $e) {
            $this->logger->error('Error en transición de estado: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Obtiene los pedidos de un cliente con paginación.
     *
     * @param int $userId
     *   ID del usuario.
     * @param int $limit
     *   Límite de resultados.
     * @param int $offset
     *   Offset para paginación.
     *
     * @return array
     *   Array con 'data' (pedidos) y 'meta' (paginación).
     */
    public function getOrdersByCustomer(int $userId, int $limit = 20, int $offset = 0): array
    {
        $storage = $this->entityTypeManager->getStorage('order_agro');

        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('customer_id', $userId)
            ->condition('state', OrderAgro::STATE_DRAFT, '<>')
            ->sort('created', 'DESC');

        // Total para paginación.
        $countQuery = clone $query;
        $total = $countQuery->count()->execute();

        $ids = $query->range($offset, $limit)->execute();
        $orders = $ids ? $storage->loadMultiple($ids) : [];

        return [
            'data' => array_values($orders),
            'meta' => [
                'total' => (int) $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * Obtiene los sub-pedidos de un productor.
     *
     * @param int $producerId
     *   ID del perfil de productor.
     * @param int $limit
     *   Límite de resultados.
     * @param int $offset
     *   Offset para paginación.
     *
     * @return array
     *   Array con 'data' (sub-pedidos) y 'meta' (paginación).
     */
    public function getSubordersByProducer(int $producerId, int $limit = 20, int $offset = 0): array
    {
        $storage = $this->entityTypeManager->getStorage('suborder_agro');

        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('producer_id', $producerId)
            ->sort('created', 'DESC');

        $countQuery = clone $query;
        $total = $countQuery->count()->execute();

        $ids = $query->range($offset, $limit)->execute();
        $suborders = $ids ? $storage->loadMultiple($ids) : [];

        return [
            'data' => array_values($suborders),
            'meta' => [
                'total' => (int) $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * Serializa un pedido para respuesta API.
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $order
     *   El pedido a serializar.
     *
     * @return array
     *   Datos del pedido en formato array.
     */
    public function serializeOrder(OrderAgro $order): array
    {
        return [
            'id' => (int) $order->id(),
            'order_number' => $order->get('order_number')->value,
            'state' => $order->get('state')->value,
            'state_label' => $order->getStateLabel(),
            'total' => $order->getFormattedTotal(),
            'total_raw' => (float) ($order->get('total')->value ?? 0),
            'subtotal' => (float) ($order->get('subtotal')->value ?? 0),
            'shipping_total' => (float) ($order->get('shipping_total')->value ?? 0),
            'tax_total' => (float) ($order->get('tax_total')->value ?? 0),
            'currency' => $order->get('currency')->value ?? 'EUR',
            'payment_state' => $order->get('payment_state')->value,
            'payment_method' => $order->get('payment_method')->value,
            'delivery_method' => $order->get('delivery_method')->value,
            'placed_at' => $order->get('placed_at')->value,
            'created' => date('c', (int) $order->get('created')->value),
        ];
    }

}
