<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agent_flows\Entity\AgentFlowExecution;
use Psr\Log\LoggerInterface;

/**
 * Servicio de metricas de flujos de agentes IA.
 *
 * PROPOSITO:
 * Proporciona metricas agregadas sobre flujos y ejecuciones para
 * dashboards, APIs y reportes. Calcula tasas de exito, duraciones
 * promedio y volumenes de ejecucion.
 *
 * USO:
 * @code
 * $metrics = $this->metricsService->getFlowMetrics(42);
 * $dashboard = $this->metricsService->getDashboardMetrics($tenantId);
 * @endcode
 */
class AgentFlowMetricsService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected CacheBackendInterface $cache,
  ) {
  }

  /**
   * Obtiene metricas detalladas de un flujo especifico.
   *
   * @param int $flowId
   *   ID del flujo.
   *
   * @return array
   *   Array con: total_executions, successful, failed, avg_duration_ms,
   *   success_rate, last_execution_at, executions_last_24h.
   */
  public function getFlowMetrics(int $flowId): array {
    try {
      $executionStorage = $this->entityTypeManager->getStorage('agent_flow_execution');

      // Total de ejecuciones.
      $totalExecutions = (int) $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('flow_id', $flowId)
        ->count()
        ->execute();

      // Ejecuciones exitosas.
      $successful = (int) $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('flow_id', $flowId)
        ->condition('execution_status', AgentFlowExecution::STATUS_COMPLETED)
        ->count()
        ->execute();

      // Ejecuciones fallidas.
      $failed = (int) $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('flow_id', $flowId)
        ->condition('execution_status', AgentFlowExecution::STATUS_FAILED)
        ->count()
        ->execute();

      // Ejecuciones en las ultimas 24 horas.
      $twentyFourHoursAgo = \Drupal::time()->getRequestTime() - 86400;
      $executionsLast24h = (int) $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('flow_id', $flowId)
        ->condition('created', $twentyFourHoursAgo, '>=')
        ->count()
        ->execute();

      // Calcular duracion promedio de ejecuciones completadas.
      $avgDuration = 0;
      $completedIds = $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('flow_id', $flowId)
        ->condition('execution_status', AgentFlowExecution::STATUS_COMPLETED)
        ->condition('duration_ms', 0, '>')
        ->execute();

      if (!empty($completedIds)) {
        $completedEntities = $executionStorage->loadMultiple($completedIds);
        $totalDuration = 0;
        foreach ($completedEntities as $entity) {
          $totalDuration += (int) ($entity->get('duration_ms')->value ?? 0);
        }
        $avgDuration = (int) round($totalDuration / count($completedEntities));
      }

      // Ultima ejecucion.
      $lastExecutionIds = $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('flow_id', $flowId)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      $lastExecutionAt = 0;
      if (!empty($lastExecutionIds)) {
        $lastExecution = $executionStorage->load(reset($lastExecutionIds));
        $lastExecutionAt = (int) ($lastExecution->get('started_at')->value ?? 0);
      }

      $successRate = $totalExecutions > 0
        ? round(($successful / $totalExecutions) * 100, 2)
        : 0.0;

      return [
        'total_executions' => $totalExecutions,
        'successful' => $successful,
        'failed' => $failed,
        'avg_duration_ms' => $avgDuration,
        'success_rate' => $successRate,
        'last_execution_at' => $lastExecutionAt,
        'executions_last_24h' => $executionsLast24h,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener metricas del flujo @flow: @message', [
        '@flow' => $flowId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'total_executions' => 0,
        'successful' => 0,
        'failed' => 0,
        'avg_duration_ms' => 0,
        'success_rate' => 0.0,
        'last_execution_at' => 0,
        'executions_last_24h' => 0,
      ];
    }
  }

  /**
   * Obtiene metricas agregadas para el dashboard.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para metricas globales.
   *
   * @return array
   *   Array con: total_flows, active_flows, total_executions,
   *   avg_duration, success_rate, executions_today, top_flows.
   */
  public function getDashboardMetrics(?int $tenantId = NULL): array {
    // AUDIT-PERF-N08: Cache 5 min — evita 6+ entity queries.
    $cacheKey = 'jaraba_agent_flows:dashboard:' . ($tenantId ?? 'global');
    $cached = $this->cache->get($cacheKey);
    if ($cached) {
      return $cached->data;
    }

    try {
      $flowStorage = $this->entityTypeManager->getStorage('agent_flow');
      $executionStorage = $this->entityTypeManager->getStorage('agent_flow_execution');

      // Consulta base para flujos.
      $flowQuery = $flowStorage->getQuery()->accessCheck(TRUE);
      if ($tenantId !== NULL) {
        $flowQuery->condition('tenant_id', $tenantId);
      }
      $totalFlows = (int) (clone $flowQuery)->count()->execute();

      // Flujos activos.
      $activeFlows = (int) (clone $flowQuery)
        ->condition('flow_status', 'active')
        ->count()
        ->execute();

      // Consulta base para ejecuciones.
      $execBaseQuery = $executionStorage->getQuery()->accessCheck(TRUE);
      if ($tenantId !== NULL) {
        $execBaseQuery->condition('tenant_id', $tenantId);
      }
      $totalExecutions = (int) (clone $execBaseQuery)->count()->execute();

      // Ejecuciones exitosas.
      $successfulExecutions = (int) (clone $execBaseQuery)
        ->condition('execution_status', AgentFlowExecution::STATUS_COMPLETED)
        ->count()
        ->execute();

      // Ejecuciones de hoy.
      $todayStart = strtotime('today midnight');
      $executionsToday = (int) (clone $execBaseQuery)
        ->condition('created', $todayStart, '>=')
        ->count()
        ->execute();

      // Duracion promedio global.
      $avgDuration = 0;
      $completedQuery = (clone $execBaseQuery)
        ->condition('execution_status', AgentFlowExecution::STATUS_COMPLETED)
        ->condition('duration_ms', 0, '>');
      $completedIds = $completedQuery->execute();

      if (!empty($completedIds)) {
        $completedEntities = $executionStorage->loadMultiple($completedIds);
        $totalDuration = 0;
        foreach ($completedEntities as $entity) {
          $totalDuration += (int) ($entity->get('duration_ms')->value ?? 0);
        }
        $avgDuration = (int) round($totalDuration / count($completedEntities));
      }

      $successRate = $totalExecutions > 0
        ? round(($successfulExecutions / $totalExecutions) * 100, 2)
        : 0.0;

      $metrics = [
        'total_flows' => $totalFlows,
        'active_flows' => $activeFlows,
        'total_executions' => $totalExecutions,
        'avg_duration' => $avgDuration,
        'success_rate' => $successRate,
        'executions_today' => $executionsToday,
      ];

      $this->cache->set($cacheKey, $metrics, time() + 300);

      return $metrics;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener metricas del dashboard: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        'total_flows' => 0,
        'active_flows' => 0,
        'total_executions' => 0,
        'avg_duration' => 0,
        'success_rate' => 0.0,
        'executions_today' => 0,
      ];
    }
  }

}
