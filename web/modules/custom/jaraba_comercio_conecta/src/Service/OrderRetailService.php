<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

class OrderRetailService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  public function getOrder(int $order_id): ?array {
    $storage = $this->entityTypeManager->getStorage('order_retail');
    $order = $storage->load($order_id);
    if (!$order) {
      return NULL;
    }

    $items = $this->getOrderItems($order_id);
    $suborders = $this->getSuborders($order_id);

    return [
      'order' => $order,
      'items' => $items,
      'suborders' => $suborders,
    ];
  }

  public function getOrderItems(int $order_id): array {
    $storage = $this->entityTypeManager->getStorage('order_item_retail');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('order_id', $order_id)
      ->sort('id', 'ASC')
      ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
  }

  public function getSuborders(int $order_id): array {
    $storage = $this->entityTypeManager->getStorage('suborder_retail');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('order_id', $order_id)
      ->sort('id', 'ASC')
      ->execute();

    return $ids ? array_values($storage->loadMultiple($ids)) : [];
  }

  public function getUserOrders(int $user_id, int $page = 0, int $per_page = 10): array {
    $storage = $this->entityTypeManager->getStorage('order_retail');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('customer_uid', $user_id)
      ->sort('created', 'DESC');

    $count_query = clone $query;
    $total = (int) $count_query->count()->execute();

    $query->range($page * $per_page, $per_page);
    $ids = $query->execute();
    $orders = $ids ? array_values($storage->loadMultiple($ids)) : [];

    return [
      'orders' => $orders,
      'total' => $total,
      'page' => $page,
      'per_page' => $per_page,
      'total_pages' => (int) ceil($total / $per_page),
    ];
  }

  public function getMerchantOrders(int $merchant_id, int $page = 0, int $per_page = 10): array {
    $storage = $this->entityTypeManager->getStorage('suborder_retail');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('merchant_id', $merchant_id)
      ->sort('created', 'DESC');

    $count_query = clone $query;
    $total = (int) $count_query->count()->execute();

    $query->range($page * $per_page, $per_page);
    $ids = $query->execute();
    $suborders = $ids ? array_values($storage->loadMultiple($ids)) : [];

    return [
      'suborders' => $suborders,
      'total' => $total,
      'page' => $page,
      'per_page' => $per_page,
      'total_pages' => (int) ceil($total / $per_page),
    ];
  }

  public function updateStatus(int $order_id, string $new_status): bool {
    $storage = $this->entityTypeManager->getStorage('order_retail');
    $order = $storage->load($order_id);
    if (!$order) {
      return FALSE;
    }

    $valid_transitions = [
      'draft' => ['pending', 'cancelled'],
      'pending' => ['confirmed', 'cancelled'],
      'confirmed' => ['processing', 'cancelled'],
      'processing' => ['shipped', 'cancelled'],
      'shipped' => ['delivered'],
      'delivered' => ['refunded'],
      'cancelled' => [],
      'refunded' => [],
    ];

    $current_status = $order->get('status')->value;
    if (!in_array($new_status, $valid_transitions[$current_status] ?? [])) {
      $this->logger->warning('Transicion de estado no valida: @current -> @new para pedido @id', [
        '@current' => $current_status,
        '@new' => $new_status,
        '@id' => $order_id,
      ]);
      return FALSE;
    }

    $order->set('status', $new_status);
    $order->save();

    $this->logger->info('Pedido @id actualizado de @old a @new', [
      '@id' => $order_id,
      '@old' => $current_status,
      '@new' => $new_status,
    ]);

    return TRUE;
  }

  public function getOrderStats(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('order_retail');

    try {
      $total_orders = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->count()
        ->execute();

      $pending_orders = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->condition('status', ['pending', 'confirmed', 'processing'], 'IN')
        ->count()
        ->execute();

      $result = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('total', 'SUM')
        ->condition('tenant_id', $tenant_id)
        ->condition('payment_status', 'paid')
        ->execute();
      $total_revenue = (float) ($result[0]['total_sum'] ?? 0);

      return [
        'total_orders' => $total_orders,
        'pending_orders' => $pending_orders,
        'total_revenue' => $total_revenue,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadisticas de pedidos: @e', ['@e' => $e->getMessage()]);
      return [
        'total_orders' => 0,
        'pending_orders' => 0,
        'total_revenue' => 0,
      ];
    }
  }

}
