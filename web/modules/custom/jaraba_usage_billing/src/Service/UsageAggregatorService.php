<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_usage_billing\Entity\UsageAggregate;
use Psr\Log\LoggerInterface;

/**
 * Pipeline de agregación de eventos de uso.
 *
 * Agrega eventos individuales (UsageEvent) en periodos temporales
 * (UsageAggregate) para consulta eficiente y facturación.
 */
class UsageAggregatorService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Agrega eventos de uso en periodos horarios.
   *
   * @param int|null $tenantId
   *   ID del tenant a agregar, o NULL para todos.
   *
   * @return int
   *   Número de agregados creados/actualizados.
   */
  public function aggregateHourly(?int $tenantId = NULL): int {
    return $this->aggregate(UsageAggregate::PERIOD_HOURLY, $tenantId);
  }

  /**
   * Agrega eventos de uso en periodos diarios.
   *
   * @param int|null $tenantId
   *   ID del tenant a agregar, o NULL para todos.
   *
   * @return int
   *   Número de agregados creados/actualizados.
   */
  public function aggregateDaily(?int $tenantId = NULL): int {
    return $this->aggregate(UsageAggregate::PERIOD_DAILY, $tenantId);
  }

  /**
   * Agrega eventos de uso en periodos mensuales.
   *
   * @param int|null $tenantId
   *   ID del tenant a agregar, o NULL para todos.
   *
   * @return int
   *   Número de agregados creados/actualizados.
   */
  public function aggregateMonthly(?int $tenantId = NULL): int {
    return $this->aggregate(UsageAggregate::PERIOD_MONTHLY, $tenantId);
  }

  /**
   * Ejecuta la agregación para un tipo de periodo.
   *
   * @param string $periodType
   *   Tipo de periodo (hourly, daily, monthly).
   * @param int|null $tenantId
   *   ID del tenant, o NULL para todos.
   *
   * @return int
   *   Número de agregados procesados.
   */
  protected function aggregate(string $periodType, ?int $tenantId): int {
    $count = 0;

    try {
      [$periodStart, $periodEnd] = $this->calculatePeriodBounds($periodType);

      // Consultar eventos agrupados por metric_name y tenant_id.
      $query = $this->database->select('usage_event', 'ue');
      $query->addField('ue', 'metric_name');
      $query->addField('ue', 'tenant_id__target_id', 'tenant_id');
      $query->addExpression('SUM(ue.quantity__number)', 'total_quantity');
      $query->addExpression('COUNT(ue.id)', 'event_count');
      $query->condition('ue.recorded_at', $periodStart, '>=');
      $query->condition('ue.recorded_at', $periodEnd, '<');
      $query->groupBy('ue.metric_name');
      $query->groupBy('ue.tenant_id__target_id');

      if ($tenantId !== NULL) {
        $query->condition('ue.tenant_id__target_id', $tenantId);
      }

      $results = $query->execute();

      if (!$results) {
        return 0;
      }

      $aggregateStorage = $this->entityTypeManager->getStorage('usage_aggregate');

      foreach ($results as $row) {
        // Buscar agregado existente para este periodo y métrica.
        $existing = $aggregateStorage->loadByProperties([
          'metric_name' => $row->metric_name,
          'period_type' => $periodType,
          'period_start' => $periodStart,
          'tenant_id' => $row->tenant_id,
        ]);

        if (!empty($existing)) {
          // Actualizar existente.
          $aggregate = reset($existing);
          $aggregate->set('total_quantity', (string) $row->total_quantity);
          $aggregate->set('event_count', (int) $row->event_count);
        }
        else {
          // Crear nuevo agregado.
          $aggregate = $aggregateStorage->create([
            'metric_name' => $row->metric_name,
            'period_type' => $periodType,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'total_quantity' => (string) $row->total_quantity,
            'event_count' => (int) $row->event_count,
            'tenant_id' => $row->tenant_id,
          ]);
        }

        $aggregate->save();
        $count++;
      }

      $this->logger->info('Agregación @period completada: @count agregados procesados.', [
        '@period' => $periodType,
        '@count' => $count,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error en agregación @period: @error', [
        '@period' => $periodType,
        '@error' => $e->getMessage(),
      ]);
    }

    return $count;
  }

  /**
   * Calcula los límites del periodo anterior según el tipo.
   *
   * @param string $periodType
   *   Tipo de periodo.
   *
   * @return array
   *   [period_start, period_end] como timestamps.
   */
  protected function calculatePeriodBounds(string $periodType): array {
    $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

    switch ($periodType) {
      case UsageAggregate::PERIOD_HOURLY:
        $periodEnd = $now->setTime((int) $now->format('H'), 0, 0);
        $periodStart = $periodEnd->modify('-1 hour');
        break;

      case UsageAggregate::PERIOD_DAILY:
        $periodEnd = $now->setTime(0, 0, 0);
        $periodStart = $periodEnd->modify('-1 day');
        break;

      case UsageAggregate::PERIOD_MONTHLY:
        $periodEnd = $now->setDate((int) $now->format('Y'), (int) $now->format('m'), 1)->setTime(0, 0, 0);
        $periodStart = $periodEnd->modify('-1 month');
        break;

      default:
        $periodEnd = $now->setTime(0, 0, 0);
        $periodStart = $periodEnd->modify('-1 day');
    }

    return [$periodStart->getTimestamp(), $periodEnd->getTimestamp()];
  }

  /**
   * Obtiene agregados para un tenant y periodo.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $periodType
   *   Tipo de periodo.
   * @param int|null $limit
   *   Número máximo de resultados.
   *
   * @return array
   *   Array de entidades UsageAggregate.
   */
  public function getAggregates(int $tenantId, string $periodType = UsageAggregate::PERIOD_DAILY, ?int $limit = 30): array {
    try {
      $storage = $this->entityTypeManager->getStorage('usage_aggregate');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('period_type', $periodType)
        ->sort('period_start', 'DESC');

      if ($limit !== NULL) {
        $query->range(0, $limit);
      }

      $ids = $query->execute();

      return !empty($ids) ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo agregados para tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
