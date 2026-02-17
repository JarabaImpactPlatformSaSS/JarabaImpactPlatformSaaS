<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de recoleccion y agregacion de metricas de agentes.
 *
 * ESTRUCTURA:
 *   Registra y consulta metricas de rendimiento de ejecuciones
 *   de agentes autonomos, incluyendo tokens consumidos, costes,
 *   duracion y tasas de exito.
 *
 * LOGICA:
 *   Cada ejecucion registra metricas via record(). Los metodos de
 *   consulta agregan datos para dashboards, analisis por tenant
 *   (AUDIT-CONS-005: entity_reference a group) y tendencias
 *   temporales de rendimiento.
 */
class AgentMetricsCollectorService {

  /**
   * Construye el servicio de metricas de agentes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceso a almacenamiento.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_agents.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Registra metricas para una ejecucion de agente.
   *
   * Actualiza los campos tokens_used, cost y duration de la entidad
   * AgentExecution correspondiente.
   *
   * @param int $executionId
   *   ID de la ejecucion a actualizar.
   * @param array $metrics
   *   Array con claves opcionales:
   *   - 'tokens_used': int con tokens consumidos.
   *   - 'cost': float con coste en USD.
   *   - 'duration': int con duracion en segundos.
   *   - 'status': string con estado actual.
   */
  public function record(int $executionId, array $metrics): void {
    try {
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');
      $execution = $executionStorage->load($executionId);

      if (!$execution) {
        $this->logger->error('No se pueden registrar metricas: ejecucion @id no encontrada.', [
          '@id' => $executionId,
        ]);
        return;
      }

      if (isset($metrics['tokens_used'])) {
        $currentTokens = (int) ($execution->get('tokens_used')->value ?? 0);
        $execution->set('tokens_used', $currentTokens + (int) $metrics['tokens_used']);
      }

      if (isset($metrics['cost'])) {
        $currentCost = (float) ($execution->get('cost')->value ?? 0.0);
        $execution->set('cost', $currentCost + (float) $metrics['cost']);
      }

      if (isset($metrics['duration']) && $execution->hasField('duration')) {
        $execution->set('duration', (int) $metrics['duration']);
      }

      if (isset($metrics['status'])) {
        $execution->set('status', $metrics['status']);
      }

      $execution->save();

      $this->logger->info('Metricas registradas para ejecucion @id: tokens=@tokens, cost=@cost.', [
        '@id' => $executionId,
        '@tokens' => $metrics['tokens_used'] ?? 0,
        '@cost' => $metrics['cost'] ?? 0.0,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al registrar metricas para ejecucion @id: @message', [
        '@id' => $executionId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Obtiene estadisticas agregadas de un agente especifico.
   *
   * @param int $agentId
   *   ID del agente autonomo.
   *
   * @return array
   *   Array con claves:
   *   - 'total_executions': int total de ejecuciones.
   *   - 'successful': int ejecuciones completadas.
   *   - 'failed': int ejecuciones fallidas.
   *   - 'avg_tokens': float promedio de tokens por ejecucion.
   *   - 'avg_cost': float promedio de coste por ejecucion.
   *   - 'avg_duration': float promedio de duracion en segundos.
   */
  public function getStats(int $agentId): array {
    try {
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');

      // Obtener todas las ejecuciones del agente.
      $query = $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('agent_id', $agentId);
      $ids = $query->execute();

      if (empty($ids)) {
        return [
          'total_executions' => 0,
          'successful' => 0,
          'failed' => 0,
          'avg_tokens' => 0.0,
          'avg_cost' => 0.0,
          'avg_duration' => 0.0,
        ];
      }

      $executions = $executionStorage->loadMultiple($ids);

      $totalExecutions = count($executions);
      $successful = 0;
      $failed = 0;
      $totalTokens = 0;
      $totalCost = 0.0;
      $totalDuration = 0;

      foreach ($executions as $execution) {
        $status = $execution->get('status')->value;
        if ($status === 'completed') {
          $successful++;
        }
        elseif ($status === 'failed') {
          $failed++;
        }

        $totalTokens += (int) ($execution->get('tokens_used')->value ?? 0);
        $totalCost += (float) ($execution->get('cost')->value ?? 0.0);

        if ($execution->hasField('duration')) {
          $totalDuration += (int) ($execution->get('duration')->value ?? 0);
        }
      }

      return [
        'total_executions' => $totalExecutions,
        'successful' => $successful,
        'failed' => $failed,
        'avg_tokens' => $totalExecutions > 0 ? round($totalTokens / $totalExecutions, 2) : 0.0,
        'avg_cost' => $totalExecutions > 0 ? round($totalCost / $totalExecutions, 6) : 0.0,
        'avg_duration' => $totalExecutions > 0 ? round($totalDuration / $totalExecutions, 2) : 0.0,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener estadisticas del agente @id: @message', [
        '@id' => $agentId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'total_executions' => 0,
        'successful' => 0,
        'failed' => 0,
        'avg_tokens' => 0.0,
        'avg_cost' => 0.0,
        'avg_duration' => 0.0,
      ];
    }
  }

  /**
   * Obtiene el desglose de costes por tenant.
   *
   * AUDIT-CONS-005: tenant_id como entity_reference a group.
   *
   * @param int|null $tenantId
   *   ID del grupo/tenant para filtrar, o NULL para obtener global.
   *
   * @return array
   *   Array indexado por tenant_id con totales de coste, o global.
   */
  public function getCostByTenant(?int $tenantId = NULL): array {
    try {
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');
      $query = $executionStorage->getQuery()
        ->accessCheck(TRUE);

      // AUDIT-CONS-005: Filtrar por tenant_id (entity_reference a group).
      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $executions = $executionStorage->loadMultiple($ids);
      $costByTenant = [];

      foreach ($executions as $execution) {
        // AUDIT-CONS-005: tenant_id como entity_reference a group.
        $executionTenantId = $execution->get('tenant_id')->target_id ?? 'sin_tenant';
        $cost = (float) ($execution->get('cost')->value ?? 0.0);
        $tokens = (int) ($execution->get('tokens_used')->value ?? 0);

        if (!isset($costByTenant[$executionTenantId])) {
          $costByTenant[$executionTenantId] = [
            'tenant_id' => $executionTenantId,
            'total_cost' => 0.0,
            'total_tokens' => 0,
            'execution_count' => 0,
          ];
        }

        $costByTenant[$executionTenantId]['total_cost'] += $cost;
        $costByTenant[$executionTenantId]['total_tokens'] += $tokens;
        $costByTenant[$executionTenantId]['execution_count']++;
      }

      return array_values($costByTenant);
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener costes por tenant: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene el rendimiento de un agente en un periodo de tiempo.
   *
   * @param int $agentId
   *   ID del agente autonomo.
   * @param int $days
   *   Numero de dias hacia atras para analizar.
   *
   * @return array
   *   Array con claves:
   *   - 'daily_executions': array indexado por fecha con conteo diario.
   *   - 'success_rate_trend': array con tasa de exito diaria.
   *   - 'cost_trend': array con coste diario.
   */
  public function getPerformance(int $agentId, int $days = 30): array {
    try {
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');
      $startDate = date('Y-m-d\TH:i:s', strtotime("-{$days} days"));

      $query = $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('agent_id', $agentId)
        ->condition('started_at', $startDate, '>=')
        ->sort('started_at', 'ASC');
      $ids = $query->execute();

      $dailyExecutions = [];
      $successRateTrend = [];
      $costTrend = [];

      if (empty($ids)) {
        return [
          'daily_executions' => $dailyExecutions,
          'success_rate_trend' => $successRateTrend,
          'cost_trend' => $costTrend,
        ];
      }

      $executions = $executionStorage->loadMultiple($ids);

      // Agrupar por dia.
      $dailyData = [];
      foreach ($executions as $execution) {
        $startedAt = $execution->get('started_at')->value ?? '';
        $day = substr($startedAt, 0, 10);
        if (empty($day)) {
          continue;
        }

        if (!isset($dailyData[$day])) {
          $dailyData[$day] = [
            'total' => 0,
            'successful' => 0,
            'cost' => 0.0,
          ];
        }

        $dailyData[$day]['total']++;
        if ($execution->get('status')->value === 'completed') {
          $dailyData[$day]['successful']++;
        }
        $dailyData[$day]['cost'] += (float) ($execution->get('cost')->value ?? 0.0);
      }

      foreach ($dailyData as $day => $data) {
        $dailyExecutions[$day] = $data['total'];
        $successRateTrend[$day] = $data['total'] > 0
          ? round(($data['successful'] / $data['total']) * 100, 2)
          : 0.0;
        $costTrend[$day] = round($data['cost'], 6);
      }

      return [
        'daily_executions' => $dailyExecutions,
        'success_rate_trend' => $successRateTrend,
        'cost_trend' => $costTrend,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener rendimiento del agente @id: @message', [
        '@id' => $agentId,
        '@message' => $e->getMessage(),
      ]);
      return [
        'daily_executions' => [],
        'success_rate_trend' => [],
        'cost_trend' => [],
      ];
    }
  }

  /**
   * Obtiene estadisticas agregadas para el dashboard de agentes.
   *
   * @return array
   *   Array con claves:
   *   - 'total_agents': int total de agentes configurados.
   *   - 'active_agents': int agentes con estado activo.
   *   - 'total_executions_today': int ejecuciones del dia actual.
   *   - 'pending_approvals': int aprobaciones pendientes.
   *   - 'total_cost_month': float coste total del mes actual.
   */
  public function getDashboardStats(): array {
    try {
      $agentStorage = $this->entityTypeManager->getStorage('autonomous_agent');
      $executionStorage = $this->entityTypeManager->getStorage('agent_execution');

      // Total de agentes configurados.
      $totalAgentsQuery = $agentStorage->getQuery()
        ->accessCheck(TRUE)
        ->count();
      $totalAgents = (int) $totalAgentsQuery->execute();

      // Agentes activos.
      $activeAgentsQuery = $agentStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', TRUE)
        ->count();
      $activeAgents = (int) $activeAgentsQuery->execute();

      // Ejecuciones de hoy.
      $todayStart = date('Y-m-d\T00:00:00');
      $todayExecutionsQuery = $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('started_at', $todayStart, '>=')
        ->count();
      $totalExecutionsToday = (int) $todayExecutionsQuery->execute();

      // Aprobaciones pendientes.
      $pendingApprovals = 0;
      try {
        $approvalStorage = $this->entityTypeManager->getStorage('agent_approval');
        $pendingQuery = $approvalStorage->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'pending')
          ->count();
        $pendingApprovals = (int) $pendingQuery->execute();
      }
      catch (\Exception $e) {
        // El storage de aprobaciones puede no existir aun.
        $this->logger->notice('Storage de aprobaciones no disponible: @message', [
          '@message' => $e->getMessage(),
        ]);
      }

      // Coste total del mes.
      $monthStart = date('Y-m-01\T00:00:00');
      $monthCostQuery = $executionStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('started_at', $monthStart, '>=');
      $monthIds = $monthCostQuery->execute();

      $totalCostMonth = 0.0;
      if (!empty($monthIds)) {
        $monthExecutions = $executionStorage->loadMultiple($monthIds);
        foreach ($monthExecutions as $execution) {
          $totalCostMonth += (float) ($execution->get('cost')->value ?? 0.0);
        }
      }

      return [
        'total_agents' => $totalAgents,
        'active_agents' => $activeAgents,
        'total_executions_today' => $totalExecutionsToday,
        'pending_approvals' => $pendingApprovals,
        'total_cost_month' => round($totalCostMonth, 4),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener estadisticas del dashboard: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        'total_agents' => 0,
        'active_agents' => 0,
        'total_executions_today' => 0,
        'pending_approvals' => 0,
        'total_cost_month' => 0.0,
      ];
    }
  }

}
