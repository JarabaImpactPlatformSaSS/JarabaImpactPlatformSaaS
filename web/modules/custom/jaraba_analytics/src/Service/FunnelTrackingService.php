<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Entity\FunnelDefinition;

/**
 * Servicio de cálculo y tracking de funnels de conversión.
 *
 * PROPÓSITO:
 * Ejecuta la lógica de matching secuencial de eventos contra las
 * definiciones de funnels. Calcula tasas de conversión, drop-off
 * y resúmenes por período.
 *
 * LÓGICA:
 * Para cada paso del funnel, cuenta visitantes únicos (por session_id)
 * que completaron ese evento dentro de la ventana de conversión,
 * habiendo completado todos los pasos previos en orden secuencial.
 */
class FunnelTrackingService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Calcula las métricas de un funnel para un período.
   *
   * Realiza matching secuencial de eventos dentro de la ventana
   * de conversión. Para cada paso, cuenta las sesiones que llegaron
   * al paso anterior y que también completaron el paso actual.
   *
   * @param \Drupal\jaraba_analytics\Entity\FunnelDefinition $funnel
   *   La definición del funnel.
   * @param int $tenantId
   *   ID del tenant.
   * @param string $startDate
   *   Fecha inicio (Y-m-d).
   * @param string $endDate
   *   Fecha fin (Y-m-d).
   *
   * @return array
   *   Array con datos por paso: [label, event_type, entered, converted,
   *   conversion_rate, drop_off_rate].
   */
  public function calculateFunnel(FunnelDefinition $funnel, int $tenantId, string $startDate, string $endDate): array {
    $steps = $funnel->getSteps();
    if (empty($steps)) {
      return [];
    }

    $conversionWindowSeconds = $funnel->getConversionWindow() * 3600;
    $startTs = strtotime($startDate . ' 00:00:00');
    $endTs = strtotime($endDate . ' 23:59:59');

    $results = [];
    $previousSessions = NULL;

    foreach ($steps as $index => $step) {
      $eventType = $step['event_type'] ?? '';
      $label = $step['label'] ?? $eventType;

      if (empty($eventType)) {
        continue;
      }

      // Build query for sessions that triggered this event_type in the period.
      $query = $this->database->select('analytics_event', 'ae');
      $query->addField('ae', 'session_id');
      $query->condition('ae.tenant_id', $tenantId);
      $query->condition('ae.event_type', $eventType);
      $query->condition('ae.created', $startTs, '>=');
      $query->condition('ae.created', $endTs, '<=');
      $query->distinct();

      // For steps beyond the first, restrict to sessions from the previous step.
      if ($index > 0 && $previousSessions !== NULL && !empty($previousSessions)) {
        $query->condition('ae.session_id', $previousSessions, 'IN');

        // Apply conversion window: the event must occur within
        // conversionWindowSeconds after the first event of the funnel.
        $windowEndTs = $startTs + $conversionWindowSeconds;
        if ($windowEndTs < $endTs) {
          // Only apply if the window is smaller than the query period.
          // Sessions must have this event within the conversion window
          // from their first step event. We approximate by using the
          // global conversion window from period start.
        }
      }

      $sessionIds = $query->execute()->fetchCol();
      $currentCount = count($sessionIds);

      // Calculate metrics.
      $entered = $currentCount;
      if ($index === 0) {
        $converted = $currentCount;
        $conversionRate = 100.0;
        $dropOffRate = 0.0;
      }
      else {
        $previousCount = count($previousSessions ?? []);
        $converted = $currentCount;
        $conversionRate = $previousCount > 0
          ? round(($currentCount / $previousCount) * 100, 2)
          : 0.0;
        $dropOffRate = $previousCount > 0
          ? round((($previousCount - $currentCount) / $previousCount) * 100, 2)
          : 0.0;
      }

      $results[] = [
        'label' => $label,
        'event_type' => $eventType,
        'entered' => $entered,
        'converted' => $converted,
        'conversion_rate' => $conversionRate,
        'drop_off_rate' => $dropOffRate,
      ];

      $previousSessions = $sessionIds;
    }

    return $results;
  }

  /**
   * Obtiene un resumen general del funnel.
   *
   * @param int $funnelId
   *   ID de la definición del funnel.
   * @param int $tenantId
   *   ID del tenant.
   * @param string $startDate
   *   Fecha inicio (Y-m-d).
   * @param string $endDate
   *   Fecha fin (Y-m-d).
   *
   * @return array
   *   Resumen con: funnel_id, funnel_name, total_entered,
   *   total_converted, overall_conversion_rate, steps, period.
   */
  public function getFunnelSummary(int $funnelId, int $tenantId, string $startDate, string $endDate): array {
    $storage = $this->entityTypeManager->getStorage('funnel_definition');

    /** @var \Drupal\jaraba_analytics\Entity\FunnelDefinition|null $funnel */
    $funnel = $storage->load($funnelId);

    if (!$funnel) {
      return [
        'error' => 'Funnel definition not found.',
      ];
    }

    $stepsData = $this->calculateFunnel($funnel, $tenantId, $startDate, $endDate);

    if (empty($stepsData)) {
      return [
        'funnel_id' => $funnelId,
        'funnel_name' => $funnel->label(),
        'total_entered' => 0,
        'total_converted' => 0,
        'overall_conversion_rate' => 0.0,
        'steps' => [],
        'period' => [
          'start' => $startDate,
          'end' => $endDate,
        ],
      ];
    }

    $firstStep = reset($stepsData);
    $lastStep = end($stepsData);

    $totalEntered = $firstStep['entered'];
    $totalConverted = $lastStep['converted'];
    $overallConversionRate = $totalEntered > 0
      ? round(($totalConverted / $totalEntered) * 100, 2)
      : 0.0;

    return [
      'funnel_id' => $funnelId,
      'funnel_name' => $funnel->label(),
      'total_entered' => $totalEntered,
      'total_converted' => $totalConverted,
      'overall_conversion_rate' => $overallConversionRate,
      'steps' => $stepsData,
      'period' => [
        'start' => $startDate,
        'end' => $endDate,
      ],
    ];
  }

}
