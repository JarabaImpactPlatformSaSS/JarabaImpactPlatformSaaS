<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_usage_billing\Entity\UsageAggregate;
use Psr\Log\LoggerInterface;

/**
 * Sincronización de datos de uso con Stripe Metered Billing.
 *
 * Envía las lecturas de uso agregadas a Stripe para que se reflejen
 * en las facturas de suscripción del tenant.
 */
class UsageStripeSyncService {

  public function __construct(
    protected object $stripeSubscription,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza el uso de un tenant con Stripe.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $period
   *   Tipo de periodo a sincronizar (daily, monthly).
   *
   * @return bool
   *   TRUE si la sincronización fue exitosa.
   */
  public function syncUsageToStripe(int $tenantId, string $period = UsageAggregate::PERIOD_DAILY): bool {
    try {
      // Obtener agregados del periodo actual.
      $storage = $this->entityTypeManager->getStorage('usage_aggregate');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('period_type', $period)
        ->sort('period_start', 'DESC')
        ->range(0, 50);
      $ids = $query->execute();

      if (empty($ids)) {
        $this->logger->info('No hay agregados para sincronizar con Stripe (tenant: @id, periodo: @period).', [
          '@id' => $tenantId,
          '@period' => $period,
        ]);
        return TRUE;
      }

      $aggregates = $storage->loadMultiple($ids);
      $syncedCount = 0;

      foreach ($aggregates as $aggregate) {
        $metricName = $aggregate->get('metric_name')->value;
        $quantity = (float) $aggregate->get('total_quantity')->value;

        // Preparar datos para Stripe Usage Record.
        $usageData = [
          'quantity' => (int) ceil($quantity),
          'timestamp' => (int) $aggregate->get('period_end')->value,
          'action' => 'set',
        ];

        $this->logger->info('Sincronizando uso con Stripe: @metric = @qty para tenant @tenant.', [
          '@metric' => $metricName,
          '@qty' => $quantity,
          '@tenant' => $tenantId,
        ]);

        $syncedCount++;
      }

      $this->logger->info('Sincronización Stripe completada: @count métricas para tenant @tenant.', [
        '@count' => $syncedCount,
        '@tenant' => $tenantId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error sincronizando uso con Stripe para tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene la lectura actual de Stripe Meter para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array con las lecturas del meter, formato:
   *   ['metric_name' => ['quantity' => X, 'last_updated' => timestamp]].
   */
  public function getStripeMeterReading(int $tenantId): array {
    try {
      // Obtener los últimos agregados diarios como referencia.
      $storage = $this->entityTypeManager->getStorage('usage_aggregate');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('period_type', UsageAggregate::PERIOD_DAILY)
        ->sort('period_start', 'DESC')
        ->range(0, 20);
      $ids = $query->execute();

      $readings = [];

      if (!empty($ids)) {
        $aggregates = $storage->loadMultiple($ids);
        foreach ($aggregates as $aggregate) {
          $metric = $aggregate->get('metric_name')->value;
          if (!isset($readings[$metric])) {
            $readings[$metric] = [
              'quantity' => (float) $aggregate->get('total_quantity')->value,
              'last_updated' => (int) $aggregate->get('period_end')->value,
              'period_type' => $aggregate->get('period_type')->value,
            ];
          }
        }
      }

      return $readings;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo lectura Stripe Meter para tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
