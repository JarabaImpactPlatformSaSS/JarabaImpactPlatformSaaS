<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

class ComercioAdminService {

  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('jaraba_comercio_conecta.admin');
  }

  public function getDashboardStats(): array {
    try {
      $merchant_storage = $this->entityTypeManager->getStorage('merchant_profile');
      $total_merchants = (int) $merchant_storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $product_storage = $this->entityTypeManager->getStorage('product_retail');
      $total_products = (int) $product_storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $order_storage = $this->entityTypeManager->getStorage('order_retail');
      $total_orders = (int) $order_storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      $gmv_result = $order_storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('total', 'SUM')
        ->condition('payment_status', 'paid')
        ->execute();
      $total_gmv = (float) ($gmv_result[0]['total_sum'] ?? 0);

      $moderation_storage = $this->entityTypeManager->getStorage('moderation_queue');
      $pending_moderations = (int) $moderation_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'pending')
        ->count()
        ->execute();

      return [
        'total_merchants' => $total_merchants,
        'total_products' => $total_products,
        'total_orders' => $total_orders,
        'total_gmv' => $total_gmv,
        'pending_moderations' => $pending_moderations,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadisticas del dashboard: @e', ['@e' => $e->getMessage()]);
      return [
        'total_merchants' => 0,
        'total_products' => 0,
        'total_orders' => 0,
        'total_gmv' => 0,
        'pending_moderations' => 0,
      ];
    }
  }

  public function getPendingModerations(int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('moderation_queue');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'pending')
        ->sort('priority', 'DESC')
        ->sort('created', 'ASC')
        ->range(0, $limit)
        ->execute();

      if (!$ids) {
        return [];
      }

      $items = [];
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $items[] = [
          'id' => (int) $entity->id(),
          'title' => $entity->get('title')->value,
          'entity_type_ref' => $entity->get('entity_type_ref')->value,
          'entity_id_ref' => $entity->get('entity_id_ref')->value,
          'moderation_type' => $entity->get('moderation_type')->value,
          'priority' => $entity->get('priority')->value,
          'created' => $entity->get('created')->value,
        ];
      }

      return $items;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo moderaciones pendientes: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  public function getRecentIncidents(int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('incident_ticket');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', ['open', 'in_progress', 'waiting'], 'IN')
        ->sort('priority', 'DESC')
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (!$ids) {
        return [];
      }

      $items = [];
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $items[] = [
          'id' => (int) $entity->id(),
          'subject' => $entity->get('subject')->value,
          'category' => $entity->get('category')->value,
          'priority' => $entity->get('priority')->value,
          'status' => $entity->get('status')->value,
          'created' => $entity->get('created')->value,
        ];
      }

      return $items;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo incidencias recientes: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  public function getPendingPayouts(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('payout_record');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'pending')
        ->sort('created', 'ASC')
        ->execute();

      if (!$ids) {
        return [];
      }

      $items = [];
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $merchant_name = '';
        $merchant_ref = $entity->get('merchant_id')->entity;
        if ($merchant_ref) {
          $merchant_name = $merchant_ref->get('business_name')->value ?? $merchant_ref->label();
        }

        $items[] = [
          'id' => (int) $entity->id(),
          'merchant_id' => $entity->get('merchant_id')->target_id,
          'merchant_name' => $merchant_name,
          'payout_amount' => (float) $entity->get('payout_amount')->value,
          'commission' => (float) $entity->get('commission')->value,
          'net' => (float) $entity->get('net')->value,
          'period' => $entity->get('period')->value,
          'status' => $entity->get('status')->value,
        ];
      }

      return $items;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo pagos pendientes: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  public function getTopMerchants(int $limit = 10): array {
    try {
      $query = $this->database->select('order_retail', 'o');
      $query->join('merchant_profile', 'm', 'o.merchant_id = m.id');
      $query->addField('o', 'merchant_id');
      $query->addField('m', 'business_name');
      $query->addExpression('SUM(o.total)', 'total_revenue');
      $query->addExpression('COUNT(o.id)', 'total_orders');
      $query->condition('o.payment_status', 'paid');
      $query->groupBy('o.merchant_id');
      $query->groupBy('m.business_name');
      $query->orderBy('total_revenue', 'DESC');
      $query->range(0, $limit);

      $results = $query->execute()->fetchAll();

      $merchants = [];
      foreach ($results as $row) {
        $merchants[] = [
          'merchant_id' => (int) $row->merchant_id,
          'business_name' => $row->business_name,
          'total_revenue' => (float) $row->total_revenue,
          'total_orders' => (int) $row->total_orders,
        ];
      }

      return $merchants;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo top comerciantes: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

}
