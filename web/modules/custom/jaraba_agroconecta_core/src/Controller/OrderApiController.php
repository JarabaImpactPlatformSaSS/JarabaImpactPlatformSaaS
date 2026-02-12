<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Entity\OrderAgro;
use Drupal\jaraba_agroconecta_core\Entity\SuborderAgro;
use Drupal\jaraba_agroconecta_core\Service\OrderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para pedidos de AgroConecta.
 *
 * PROPÓSITO:
 * Expone endpoints JSON para gestión de pedidos desde frontend,
 * tanto para clientes como para productores.
 *
 * ENDPOINTS CLIENTE:
 * - GET /api/v1/agro/orders → Mis pedidos
 * - GET /api/v1/agro/orders/{number} → Detalle de pedido
 * - POST /api/v1/agro/orders/{number}/cancel → Cancelar pedido
 *
 * ENDPOINTS PRODUCTOR:
 * - GET /api/v1/agro/producer/orders → Sub-pedidos recibidos
 * - POST /api/v1/agro/producer/orders/{id}/confirm → Confirmar sub-pedido
 * - POST /api/v1/agro/producer/orders/{id}/ship → Marcar como enviado
 */
class OrderApiController extends ControllerBase implements ContainerInjectionInterface
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
     * Lista los pedidos del usuario autenticado.
     *
     * GET /api/v1/agro/orders
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con pedidos paginados.
     */
    public function listOrders(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 20), 50);
        $offset = max((int) $request->query->get('offset', 0), 0);

        $userId = (int) $this->currentUser()->id();
        $result = $this->orderService->getOrdersByCustomer($userId, $limit, $offset);

        $serialized = array_map(
            fn($order) => $this->orderService->serializeOrder($order),
            $result['data']
        );

        return new JsonResponse([
            'meta' => $result['meta'],
            'data' => $serialized,
        ]);
    }

    /**
     * Obtiene el detalle de un pedido por número.
     *
     * GET /api/v1/agro/orders/{order_number}
     *
     * @param string $order_number
     *   Número del pedido.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con detalle del pedido.
     */
    public function getOrder(string $order_number): JsonResponse
    {
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
        $orderIds = $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('order_number', $order_number)
            ->execute();

        if (empty($orderIds)) {
            return new JsonResponse(['error' => 'Pedido no encontrado.'], 404);
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $order */
        $order = $orderStorage->load(reset($orderIds));

        // Verificar que pertenece al usuario actual.
        if ((int) $order->get('customer_id')->target_id !== (int) $this->currentUser()->id()) {
            return new JsonResponse(['error' => 'Acceso denegado.'], 403);
        }

        // Cargar items del pedido.
        $itemStorage = $this->entityTypeManager()->getStorage('order_item_agro');
        $itemIds = $itemStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('order_id', $order->id())
            ->execute();

        $items = [];
        foreach ($itemStorage->loadMultiple($itemIds) as $item) {
            $items[] = [
                'title' => $item->get('title')->value,
                'sku' => $item->get('sku')->value,
                'quantity' => (int) $item->get('quantity')->value,
                'unit_price' => (float) $item->get('unit_price')->value,
                'total_price' => (float) $item->get('total_price')->value,
                'item_state' => $item->get('item_state')->value,
            ];
        }

        $orderData = $this->orderService->serializeOrder($order);
        $orderData['items'] = $items;

        return new JsonResponse(['data' => $orderData]);
    }

    /**
     * Solicita la cancelación de un pedido.
     *
     * POST /api/v1/agro/orders/{order_number}/cancel
     *
     * @param string $order_number
     *   Número del pedido.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado.
     */
    public function cancelOrder(string $order_number): JsonResponse
    {
        $orderStorage = $this->entityTypeManager()->getStorage('order_agro');
        $orderIds = $orderStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('order_number', $order_number)
            ->execute();

        if (empty($orderIds)) {
            return new JsonResponse(['error' => 'Pedido no encontrado.'], 404);
        }

        /** @var \Drupal\jaraba_agroconecta_core\Entity\OrderAgro $order */
        $order = $orderStorage->load(reset($orderIds));

        if ((int) $order->get('customer_id')->target_id !== (int) $this->currentUser()->id()) {
            return new JsonResponse(['error' => 'Acceso denegado.'], 403);
        }

        if (!$order->isCancellable()) {
            return new JsonResponse([
                'error' => 'El pedido no puede ser cancelado en su estado actual.',
            ], 422);
        }

        $success = $this->orderService->transitionState(
            (int) $order->id(),
            OrderAgro::STATE_CANCELLED
        );

        if (!$success) {
            return new JsonResponse(['error' => 'Error al cancelar el pedido.'], 500);
        }

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Pedido cancelado correctamente.',
        ]);
    }

    /**
     * Lista los sub-pedidos del productor autenticado.
     *
     * GET /api/v1/agro/producer/orders
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con sub-pedidos paginados.
     */
    public function listProducerOrders(Request $request): JsonResponse
    {
        $limit = min((int) $request->query->get('limit', 20), 50);
        $offset = max((int) $request->query->get('offset', 0), 0);

        // Buscar ProducerProfile del usuario actual.
        $producerStorage = $this->entityTypeManager()->getStorage('producer_profile');
        $producerIds = $producerStorage->getQuery()
            ->accessCheck(TRUE)
            ->condition('uid', $this->currentUser()->id())
            ->execute();

        if (empty($producerIds)) {
            return new JsonResponse([
                'meta' => ['total' => 0, 'limit' => $limit, 'offset' => $offset],
                'data' => [],
            ]);
        }

        $producerId = (int) reset($producerIds);
        $result = $this->orderService->getSubordersByProducer($producerId, $limit, $offset);

        $serialized = [];
        foreach ($result['data'] as $suborder) {
            $serialized[] = [
                'id' => (int) $suborder->id(),
                'suborder_number' => $suborder->get('suborder_number')->value,
                'state' => $suborder->get('state')->value,
                'subtotal' => (float) ($suborder->get('subtotal')->value ?? 0),
                'commission_amount' => (float) ($suborder->get('commission_amount')->value ?? 0),
                'producer_payout' => (float) ($suborder->get('producer_payout')->value ?? 0),
                'payout_state' => $suborder->get('payout_state')->value,
                'tracking_number' => $suborder->get('tracking_number')->value,
                'created' => date('c', (int) $suborder->get('created')->value),
            ];
        }

        return new JsonResponse([
            'meta' => $result['meta'],
            'data' => $serialized,
        ]);
    }

    /**
     * Confirma la recepción de un sub-pedido por el productor.
     *
     * POST /api/v1/agro/producer/orders/{suborder_agro}/confirm
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\SuborderAgro $suborder_agro
     *   El sub-pedido a confirmar.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado.
     */
    public function confirmSuborder(SuborderAgro $suborder_agro): JsonResponse
    {
        $suborder_agro->set('state', OrderAgro::STATE_PROCESSING);
        $suborder_agro->save();

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Sub-pedido confirmado.',
            'state' => OrderAgro::STATE_PROCESSING,
        ]);
    }

    /**
     * Marca un sub-pedido como enviado con datos de tracking.
     *
     * POST /api/v1/agro/producer/orders/{suborder_agro}/ship
     *
     * @param \Drupal\jaraba_agroconecta_core\Entity\SuborderAgro $suborder_agro
     *   El sub-pedido a marcar como enviado.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con tracking_number y tracking_url.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado.
     */
    public function shipSuborder(SuborderAgro $suborder_agro, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        $trackingNumber = $data['tracking_number'] ?? '';
        if (empty($trackingNumber)) {
            return new JsonResponse([
                'error' => 'Número de seguimiento requerido.',
            ], 400);
        }

        $suborder_agro->set('state', OrderAgro::STATE_SHIPPED);
        $suborder_agro->set('tracking_number', $trackingNumber);
        $suborder_agro->set('tracking_url', $data['tracking_url'] ?? '');
        $suborder_agro->set('shipped_at', date('Y-m-d\TH:i:s'));
        $suborder_agro->save();

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Sub-pedido marcado como enviado.',
            'tracking_number' => $trackingNumber,
        ]);
    }

}
