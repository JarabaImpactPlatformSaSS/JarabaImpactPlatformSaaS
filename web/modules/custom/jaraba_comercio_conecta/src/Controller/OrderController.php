<?php

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\OrderRetailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends ControllerBase {

  public function __construct(
    protected OrderRetailService $orderService,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.order_retail'),
    );
  }

  public function myOrders(Request $request): array {
    $uid = (int) $this->currentUser()->id();
    if ($uid <= 0) {
      throw new AccessDeniedHttpException($this->t('Debes iniciar sesion para ver tus pedidos.'));
    }

    $page = max(0, (int) $request->query->get('page', 0));
    $result = $this->orderService->getUserOrders($uid, $page);

    $orders = [];
    foreach ($result['orders'] as $order) {
      $orders[] = [
        'id' => (int) $order->id(),
        'order_number' => $order->get('order_number')->value,
        'status' => $order->get('status')->value,
        'payment_status' => $order->get('payment_status')->value,
        'total' => (float) $order->get('total')->value,
        'created' => $order->get('created')->value,
      ];
    }

    return [
      '#theme' => 'comercio_my_orders',
      '#orders' => $orders,
      '#total' => $result['total'],
      '#page' => $result['page'],
      '#total_pages' => $result['total_pages'],
      '#attached' => [
        'library' => ['jaraba_comercio_conecta/orders'],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['order_retail_list'],
        'max-age' => 60,
      ],
    ];
  }

  public function orderDetail(int $order_id): array {
    $result = $this->orderService->getOrder($order_id);
    if (!$result) {
      throw new NotFoundHttpException();
    }

    $order = $result['order'];
    $customer_uid = (int) $order->get('customer_uid')->target_id;
    if ($customer_uid !== (int) $this->currentUser()->id() &&
        !$this->currentUser()->hasPermission('manage comercio orders')) {
      throw new AccessDeniedHttpException($this->t('No tienes acceso a este pedido.'));
    }

    $items = [];
    foreach ($result['items'] as $item) {
      $items[] = [
        'product_title' => $item->get('product_title')->value,
        'product_sku' => $item->get('product_sku')->value,
        'quantity' => (int) $item->get('quantity')->value,
        'unit_price' => (float) $item->get('unit_price')->value,
        'total_price' => (float) $item->get('total_price')->value,
      ];
    }

    return [
      '#theme' => 'comercio_order_detail',
      '#order' => $order,
      '#items' => $items,
      '#order_number' => $order->get('order_number')->value,
      '#status' => $order->get('status')->value,
      '#payment_status' => $order->get('payment_status')->value,
      '#subtotal' => (float) $order->get('subtotal')->value,
      '#tax_amount' => (float) $order->get('tax_amount')->value,
      '#shipping_cost' => (float) $order->get('shipping_cost')->value,
      '#discount_amount' => (float) $order->get('discount_amount')->value,
      '#total' => (float) $order->get('total')->value,
      '#tracking_number' => $order->get('tracking_number')->value,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['order_retail:' . $order->id()],
        'max-age' => 60,
      ],
    ];
  }

  public function apiListOrders(Request $request): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $page = max(0, (int) $request->query->get('page', 0));
    $per_page = min(50, max(1, (int) $request->query->get('per_page', 10)));

    $result = $this->orderService->getUserOrders($uid, $page, $per_page);

    $data = [];
    foreach ($result['orders'] as $order) {
      $data[] = $this->serializeOrder($order);
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

  public function apiGetOrder(int $order_id): JsonResponse {
    $result = $this->orderService->getOrder($order_id);
    if (!$result) {
      return new JsonResponse(['error' => $this->t('Pedido no encontrado.')], 404);
    }

    $order = $result['order'];
    $customer_uid = (int) $order->get('customer_uid')->target_id;
    if ($customer_uid !== (int) $this->currentUser()->id() &&
        !$this->currentUser()->hasPermission('manage comercio orders')) {
      return new JsonResponse(['error' => $this->t('Acceso denegado.')], 403);
    }

    $order_data = $this->serializeOrder($order);
    $order_data['items'] = [];
    foreach ($result['items'] as $item) {
      $order_data['items'][] = [
        'id' => (int) $item->id(),
        'product_title' => $item->get('product_title')->value,
        'product_sku' => $item->get('product_sku')->value,
        'quantity' => (int) $item->get('quantity')->value,
        'unit_price' => (float) $item->get('unit_price')->value,
        'total_price' => (float) $item->get('total_price')->value,
      ];
    }

    return new JsonResponse(['data' => $order_data]);
  }

  public function apiUpdateStatus(int $order_id, Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE) ?? [];
    $new_status = $data['status'] ?? '';

    if (!$new_status) {
      return new JsonResponse(['error' => $this->t('Campo status requerido.')], 400);
    }

    $success = $this->orderService->updateStatus($order_id, $new_status);
    if (!$success) {
      return new JsonResponse(['error' => $this->t('Transicion de estado no valida.')], 400);
    }

    return new JsonResponse(['data' => ['status' => $new_status, 'order_id' => $order_id]]);
  }

  protected function serializeOrder(object $order): array {
    return [
      'id' => (int) $order->id(),
      'order_number' => $order->get('order_number')->value,
      'status' => $order->get('status')->value,
      'payment_status' => $order->get('payment_status')->value,
      'subtotal' => (float) $order->get('subtotal')->value,
      'tax_amount' => (float) $order->get('tax_amount')->value,
      'shipping_cost' => (float) $order->get('shipping_cost')->value,
      'discount_amount' => (float) $order->get('discount_amount')->value,
      'total' => (float) $order->get('total')->value,
      'shipping_method' => $order->get('shipping_method')->value,
      'tracking_number' => $order->get('tracking_number')->value,
      'created' => $order->get('created')->value,
    ];
  }

}
