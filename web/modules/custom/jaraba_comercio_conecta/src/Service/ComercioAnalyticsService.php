<?php

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

class ComercioAnalyticsService {

  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('jaraba_comercio_conecta.analytics');
  }

  public function getMarketplaceKpis(string $period = '30d'): array {
    try {
      $start_timestamp = $this->getPeriodStartTimestamp($period);

      $order_storage = $this->entityTypeManager->getStorage('order_retail');

      $gmv_result = $order_storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('total', 'SUM')
        ->condition('payment_status', 'paid')
        ->condition('created', $start_timestamp, '>=')
        ->execute();
      $gmv = (float) ($gmv_result[0]['total_sum'] ?? 0);

      $total_orders = (int) $order_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $start_timestamp, '>=')
        ->count()
        ->execute();

      $paid_orders = (int) $order_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('payment_status', 'paid')
        ->condition('created', $start_timestamp, '>=')
        ->count()
        ->execute();

      $avg_order_value = $paid_orders > 0 ? round($gmv / $paid_orders, 2) : 0;

      $merchant_storage = $this->entityTypeManager->getStorage('merchant_profile');
      $active_merchants = (int) $merchant_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->count()
        ->execute();

      $new_customers = $this->getNewCustomerCount($start_timestamp);
      $conversion_rate = $this->estimateConversionRate($start_timestamp);

      return [
        'gmv' => $gmv,
        'total_orders' => $total_orders,
        'avg_order_value' => $avg_order_value,
        'active_merchants' => $active_merchants,
        'new_customers' => $new_customers,
        'conversion_rate' => $conversion_rate,
        'period' => $period,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo KPIs del marketplace: @e', ['@e' => $e->getMessage()]);
      return [
        'gmv' => 0,
        'total_orders' => 0,
        'avg_order_value' => 0,
        'active_merchants' => 0,
        'new_customers' => 0,
        'conversion_rate' => 0.0,
        'period' => $period,
      ];
    }
  }

  public function getRevenueChart(string $period = '30d'): array {
    try {
      $start_timestamp = $this->getPeriodStartTimestamp($period);

      $query = $this->database->select('order_retail', 'o');
      $query->addExpression("FROM_UNIXTIME(o.created, '%Y-%m-%d')", 'date');
      $query->addExpression('SUM(o.total)', 'revenue');
      $query->addExpression('COUNT(o.id)', 'order_count');
      $query->condition('o.payment_status', 'paid');
      $query->condition('o.created', $start_timestamp, '>=');
      $query->groupBy('date');
      $query->orderBy('date', 'ASC');

      $results = $query->execute()->fetchAll();

      $chart_data = [];
      foreach ($results as $row) {
        $chart_data[] = [
          'date' => $row->date,
          'revenue' => (float) $row->revenue,
          'order_count' => (int) $row->order_count,
        ];
      }

      return $chart_data;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo datos de grafico de ingresos: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  public function getTopProducts(int $limit = 10, string $period = '30d'): array {
    try {
      $start_timestamp = $this->getPeriodStartTimestamp($period);

      $query = $this->database->select('order_item_retail', 'oi');
      $query->join('order_retail', 'o', 'oi.order_id = o.id');
      $query->addField('oi', 'product_id');
      $query->addField('oi', 'product_name');
      $query->addExpression('SUM(oi.quantity)', 'total_quantity');
      $query->addExpression('SUM(oi.total)', 'total_revenue');
      $query->condition('o.payment_status', 'paid');
      $query->condition('o.created', $start_timestamp, '>=');
      $query->groupBy('oi.product_id');
      $query->groupBy('oi.product_name');
      $query->orderBy('total_revenue', 'DESC');
      $query->range(0, $limit);

      $results = $query->execute()->fetchAll();

      $products = [];
      foreach ($results as $row) {
        $products[] = [
          'product_id' => (int) $row->product_id,
          'product_name' => $row->product_name,
          'total_quantity' => (int) $row->total_quantity,
          'total_revenue' => (float) $row->total_revenue,
        ];
      }

      return $products;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo top productos: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  public function getTopCategories(int $limit = 10): array {
    try {
      $query = $this->database->select('order_item_retail', 'oi');
      $query->join('product_retail', 'p', 'oi.product_id = p.id');
      $query->join('order_retail', 'o', 'oi.order_id = o.id');
      $query->addField('p', 'category');
      $query->addExpression('SUM(oi.total)', 'total_revenue');
      $query->addExpression('COUNT(DISTINCT oi.order_id)', 'total_orders');
      $query->condition('o.payment_status', 'paid');
      $query->groupBy('p.category');
      $query->orderBy('total_revenue', 'DESC');
      $query->range(0, $limit);

      $results = $query->execute()->fetchAll();

      $categories = [];
      foreach ($results as $row) {
        $categories[] = [
          'category' => $row->category,
          'total_revenue' => (float) $row->total_revenue,
          'total_orders' => (int) $row->total_orders,
        ];
      }

      return $categories;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo top categorias: @e', ['@e' => $e->getMessage()]);
      return [];
    }
  }

  public function getCustomerRetentionRate(string $period = '30d'): float {
    try {
      $start_timestamp = $this->getPeriodStartTimestamp($period);

      $query = $this->database->select('order_retail', 'o');
      $query->addField('o', 'customer_uid');
      $query->condition('o.payment_status', 'paid');
      $query->condition('o.created', $start_timestamp, '>=');
      $query->groupBy('o.customer_uid');
      $query->having('COUNT(o.id) > 1');
      $repeat_count = (int) $query->countQuery()->execute()->fetchField();

      $total_query = $this->database->select('order_retail', 'o');
      $total_query->addField('o', 'customer_uid');
      $total_query->condition('o.payment_status', 'paid');
      $total_query->condition('o.created', $start_timestamp, '>=');
      $total_query->groupBy('o.customer_uid');
      $total_count = (int) $total_query->countQuery()->execute()->fetchField();

      if ($total_count === 0) {
        return 0.0;
      }

      return round(($repeat_count / $total_count) * 100, 2);
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando tasa de retencion: @e', ['@e' => $e->getMessage()]);
      return 0.0;
    }
  }

  protected function getPeriodStartTimestamp(string $period): int {
    $days = match ($period) {
      '7d' => 7,
      '14d' => 14,
      '30d' => 30,
      '90d' => 90,
      '365d' => 365,
      default => 30,
    };

    return \Drupal::time()->getRequestTime() - ($days * 86400);
  }

  protected function getNewCustomerCount(int $startTimestamp): int {
    try {
      $query = $this->database->select('order_retail', 'o');
      $query->addField('o', 'customer_uid');
      $query->condition('o.created', $startTimestamp, '>=');
      $query->groupBy('o.customer_uid');

      $subquery = $this->database->select('order_retail', 'o2');
      $subquery->addField('o2', 'customer_uid');
      $subquery->condition('o2.created', $startTimestamp, '<');
      $subquery->groupBy('o2.customer_uid');

      $query->condition('o.customer_uid', $subquery, 'NOT IN');

      return (int) $query->countQuery()->execute()->fetchField();
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  protected function estimateConversionRate(int $startTimestamp): float {
    try {
      $total_orders = (int) $this->entityTypeManager->getStorage('order_retail')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $startTimestamp, '>=')
        ->count()
        ->execute();

      $paid_orders = (int) $this->entityTypeManager->getStorage('order_retail')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('payment_status', 'paid')
        ->condition('created', $startTimestamp, '>=')
        ->count()
        ->execute();

      if ($total_orders === 0) {
        return 0.0;
      }

      return round(($paid_orders / $total_orders) * 100, 2);
    }
    catch (\Exception $e) {
      return 0.0;
    }
  }

}
