<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

class MerchantPayoutService {

  protected LoggerInterface $logger;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('jaraba_comercio_conecta');
  }

  public function getMerchantSuborders(int $merchantId, string $status = NULL, int $limit = 50): array {
    $storage = $this->entityTypeManager->getStorage('suborder_retail');

    try {
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('merchant_id', $merchantId)
        ->sort('created', 'DESC')
        ->range(0, $limit);

      if ($status !== NULL) {
        $query->condition('payout_status', $status);
      }

      $ids = $query->execute();

      return $ids ? array_values($storage->loadMultiple($ids)) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo sub-pedidos del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getMerchantPayoutSummary(int $merchantId): array {
    $storage = $this->entityTypeManager->getStorage('suborder_retail');

    try {
      $revenueResult = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('subtotal', 'SUM')
        ->condition('merchant_id', $merchantId)
        ->execute();
      $totalRevenue = (float) ($revenueResult[0]['subtotal_sum'] ?? 0);

      $commissionResult = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('commission_amount', 'SUM')
        ->condition('merchant_id', $merchantId)
        ->execute();
      $totalCommissions = (float) ($commissionResult[0]['commission_amount_sum'] ?? 0);

      $paidResult = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('payout_amount', 'SUM')
        ->condition('merchant_id', $merchantId)
        ->condition('payout_status', 'paid')
        ->execute();
      $paidPayouts = (float) ($paidResult[0]['payout_amount_sum'] ?? 0);

      $pendingResult = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('payout_amount', 'SUM')
        ->condition('merchant_id', $merchantId)
        ->condition('payout_status', 'pending')
        ->execute();
      $pendingPayouts = (float) ($pendingResult[0]['payout_amount_sum'] ?? 0);

      $totalPayouts = $paidPayouts + $pendingPayouts;

      return [
        'total_revenue' => $totalRevenue,
        'total_commissions' => $totalCommissions,
        'total_payouts' => $totalPayouts,
        'pending_payouts' => $pendingPayouts,
        'paid_payouts' => $paidPayouts,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo resumen de pagos del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [
        'total_revenue' => 0.0,
        'total_commissions' => 0.0,
        'total_payouts' => 0.0,
        'pending_payouts' => 0.0,
        'paid_payouts' => 0.0,
      ];
    }
  }

  public function getMerchantRecentPayouts(int $merchantId, int $limit = 20): array {
    $storage = $this->entityTypeManager->getStorage('suborder_retail');

    try {
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('merchant_id', $merchantId)
        ->condition('payout_status', 'paid')
        ->sort('changed', 'DESC')
        ->range(0, $limit)
        ->execute();

      return $ids ? array_values($storage->loadMultiple($ids)) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo pagos recientes del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return [];
    }
  }

  public function getMonthlyRevenue(int $merchantId, int $months = 6): array {
    $storage = $this->entityTypeManager->getStorage('suborder_retail');
    $result = [];

    try {
      for ($i = $months - 1; $i >= 0; $i--) {
        $startDate = strtotime("first day of -$i months midnight");
        $endDate = strtotime("last day of -$i months 23:59:59");
        $monthKey = date('Y-m', $startDate);

        $revenueResult = $storage->getAggregateQuery()
          ->accessCheck(FALSE)
          ->aggregate('subtotal', 'SUM')
          ->condition('merchant_id', $merchantId)
          ->condition('created', $startDate, '>=')
          ->condition('created', $endDate, '<=')
          ->execute();

        $result[$monthKey] = (float) ($revenueResult[0]['subtotal_sum'] ?? 0);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo ingresos mensuales del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  public function getPendingPayoutAmount(int $merchantId): float {
    $storage = $this->entityTypeManager->getStorage('suborder_retail');

    try {
      $result = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('payout_amount', 'SUM')
        ->condition('merchant_id', $merchantId)
        ->condition('payout_status', 'pending')
        ->execute();

      return (float) ($result[0]['payout_amount_sum'] ?? 0);
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo monto pendiente del comerciante @id: @e', [
        '@id' => $merchantId,
        '@e' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

}
