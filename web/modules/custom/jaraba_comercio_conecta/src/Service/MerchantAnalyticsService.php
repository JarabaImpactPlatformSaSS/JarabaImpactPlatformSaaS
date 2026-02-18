<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

class MerchantAnalyticsService {

  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('jaraba_comercio_conecta.merchant_analytics');
  }

  public function getMerchantKpis(int $merchantId, string $period = '30d'): array {
    try {
      $start_timestamp = $this->getPeriodStartTimestamp($period);

      $order_storage = $this->entityTypeManager->getStorage('suborder_retail');

      $revenue_result = $order_storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('total', 'SUM')
        ->condition('merchant_id', $merchantId)
        ->condition('payment_status', 'paid')
        ->condition('created', $start_timestamp, '>=')
        ->execute();
      $revenue = (float) ($revenue_result[0]['total_sum'] ?? 0);

      $total_orders = (int) $order_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('merchant_id', $merchantId)
        ->condition('created', $start_timestamp, '>=')
        ->count()
        ->execute();

      $avg_order = $total_orders > 0 ? round($revenue / $total_orders, 2) : 0;

      $top_product = $this->getTopProductForMerchant($merchantId, $start_timestamp);

      $avg_rating = $this->getMerchantAverageRating($merchantId);

      return [
        'revenue' => $revenue,
        'total_orders' => $total_orders,
        'avg_order_value' => $avg_order,
        'top_product' => $top_product,
        'avg_rating' => $avg_rating,
        'period' => $period,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo KPIs del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [
        'revenue' => 0,
        'total_orders' => 0,
        'avg_order_value' => 0,
        'top_product' => NULL,
        'avg_rating' => 0,
        'period' => $period,
      ];
    }
  }

  public function getMerchantRevenueChart(int $merchantId, string $period = '30d'): array {
    try {
      $start_timestamp = $this->getPeriodStartTimestamp($period);

      $query = $this->database->select('suborder_retail', 'so');
      $query->addExpression("FROM_UNIXTIME(so.created, '%Y-%m-%d')", 'date');
      $query->addExpression('SUM(so.total)', 'revenue');
      $query->addExpression('COUNT(so.id)', 'order_count');
      $query->condition('so.merchant_id', $merchantId);
      $query->condition('so.payment_status', 'paid');
      $query->condition('so.created', $start_timestamp, '>=');
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
      $this->logger->error('Error obteniendo grafico de ingresos del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getMerchantTopProducts(int $merchantId, int $limit = 10): array {
    try {
      $query = $this->database->select('order_item_retail', 'oi');
      $query->join('suborder_retail', 'so', 'oi.suborder_id = so.id');
      $query->addField('oi', 'product_id');
      $query->addField('oi', 'product_name');
      $query->addExpression('SUM(oi.quantity)', 'total_quantity');
      $query->addExpression('SUM(oi.total)', 'total_revenue');
      $query->condition('so.merchant_id', $merchantId);
      $query->condition('so.payment_status', 'paid');
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
      $this->logger->error('Error obteniendo top productos del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getMerchantPeakHours(int $merchantId): array {
    try {
      $query = $this->database->select('suborder_retail', 'so');
      $query->addExpression("HOUR(FROM_UNIXTIME(so.created))", 'hour');
      $query->addExpression('COUNT(so.id)', 'order_count');
      $query->condition('so.merchant_id', $merchantId);
      $query->condition('so.payment_status', 'paid');
      $query->groupBy('hour');
      $query->orderBy('hour', 'ASC');

      $results = $query->execute()->fetchAll();

      $hours = [];
      for ($h = 0; $h < 24; $h++) {
        $hours[$h] = [
          'hour' => $h,
          'label' => sprintf('%02d:00', $h),
          'order_count' => 0,
        ];
      }

      foreach ($results as $row) {
        $h = (int) $row->hour;
        $hours[$h]['order_count'] = (int) $row->order_count;
      }

      return array_values($hours);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo horas pico del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getMerchantCustomerInsights(int $merchantId): array {
    try {
      $query = $this->database->select('suborder_retail', 'so');
      $query->addField('so', 'customer_uid');
      $query->addExpression('COUNT(so.id)', 'order_count');
      $query->addExpression('SUM(so.total)', 'lifetime_value');
      $query->condition('so.merchant_id', $merchantId);
      $query->condition('so.payment_status', 'paid');
      $query->groupBy('so.customer_uid');

      $results = $query->execute()->fetchAll();

      $total_customers = count($results);
      $repeat_customers = 0;
      $total_ltv = 0.0;

      foreach ($results as $row) {
        $total_ltv += (float) $row->lifetime_value;
        if ((int) $row->order_count > 1) {
          $repeat_customers++;
        }
      }

      $repeat_rate = $total_customers > 0 ? round(($repeat_customers / $total_customers) * 100, 2) : 0;
      $avg_ltv = $total_customers > 0 ? round($total_ltv / $total_customers, 2) : 0;

      return [
        'total_customers' => $total_customers,
        'repeat_customers' => $repeat_customers,
        'repeat_rate' => $repeat_rate,
        'avg_lifetime_value' => $avg_ltv,
        'total_lifetime_value' => round($total_ltv, 2),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo insights de clientes del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [
        'total_customers' => 0,
        'repeat_customers' => 0,
        'repeat_rate' => 0,
        'avg_lifetime_value' => 0,
        'total_lifetime_value' => 0,
      ];
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

  protected function getTopProductForMerchant(int $merchantId, int $startTimestamp): ?array {
    try {
      $query = $this->database->select('order_item_retail', 'oi');
      $query->join('suborder_retail', 'so', 'oi.suborder_id = so.id');
      $query->addField('oi', 'product_id');
      $query->addField('oi', 'product_name');
      $query->addExpression('SUM(oi.total)', 'total_revenue');
      $query->condition('so.merchant_id', $merchantId);
      $query->condition('so.payment_status', 'paid');
      $query->condition('so.created', $startTimestamp, '>=');
      $query->groupBy('oi.product_id');
      $query->groupBy('oi.product_name');
      $query->orderBy('total_revenue', 'DESC');
      $query->range(0, 1);

      $result = $query->execute()->fetchObject();
      if (!$result) {
        return NULL;
      }

      return [
        'product_id' => (int) $result->product_id,
        'product_name' => $result->product_name,
        'total_revenue' => (float) $result->total_revenue,
      ];
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  protected function getMerchantAverageRating(int $merchantId): float {
    try {
      $storage = $this->entityTypeManager->getStorage('product_review');
      $result = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('rating', 'AVG')
        ->condition('merchant_id', $merchantId)
        ->condition('status', 'approved')
        ->execute();

      return round((float) ($result[0]['rating_avg'] ?? 0), 1);
    }
    catch (\Exception $e) {
      return 0.0;
    }
  }

}
