<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de observabilidad y analíticas para IA.
 *
 * PROPÓSITO:
 * Proporciona tracking de uso, cálculo de costos y métricas
 * para todos los agentes de IA. Alimenta el Dashboard de
 * Observabilidad con datos en tiempo real.
 *
 * MÉTRICAS RASTREADAS:
 * - Ejecuciones totales por agente/acción/tier
 * - Tokens de entrada/salida
 * - Costos por ejecución y totales
 * - Duraciones de respuesta
 * - Tasas de éxito/error
 * - Puntuaciones de calidad (LLM-as-Judge)
 *
 * GAP-02: TRACING DISTRIBUIDO:
 * Soporta campos trace_id, span_id, parent_span_id y operation_name
 * para reconstruir cadenas de llamadas: Copilot -> Agent -> RAG -> Tool.
 * Los campos se propagan automaticamente via TraceContextService.
 *
 * CÁLCULOS DE AHORRO:
 * Compara el costo real (usando Model Routing) vs. el costo
 * equivalente si todas las llamadas usaran tier premium.
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
class AIObservabilityService {

  /**
   * El gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El usuario actual.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * El logger para registrar errores.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Servicio de contexto de tracing (GAP-02).
   *
   * @var \Drupal\jaraba_ai_agents\Service\TraceContextService|null
   */
  protected ?TraceContextService $traceContext;

  /**
   * Servicio de metering de tenant (GAP-AUD-002).
   *
   * @var object|null
   */
  protected ?object $tenantMetering;

  /**
   * Construye un AIObservabilityService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   El usuario actual.
   * @param \Psr\Log\LoggerInterface $logger
   *   El servicio de logging.
   * @param \Drupal\jaraba_ai_agents\Service\TraceContextService|null $traceContext
   *   Servicio de contexto de tracing (GAP-02). Opcional para
   *   backward compatibility.
   * @param object|null $tenantMetering
   *   Servicio de metering por tenant (GAP-AUD-002). Opcional.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    LoggerInterface $logger,
    ?TraceContextService $traceContext = NULL,
    ?object $tenantMetering = NULL,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->logger = $logger;
    $this->traceContext = $traceContext;
    $this->tenantMetering = $tenantMetering;
  }

  /**
   * Registra una ejecución de IA.
   *
   * Crea una entidad ai_usage_log con todos los datos de
   * la ejecución para análisis posterior.
   *
   * GAP-02: Incluye automaticamente trace_id/span_id del
   * TraceContextService si esta disponible. Los datos
   * explicitos en $data tienen prioridad sobre los del contexto.
   *
   * @param array $data
   *   Datos de la ejecución:
   *   - agent_id: string - ID del agente.
   *   - action: string - Acción ejecutada.
   *   - tier: string - Tier del modelo (fast/balanced/premium).
   *   - model_id: string - ID del modelo usado.
   *   - provider_id: string - ID del proveedor.
   *   - tenant_id: string - ID del tenant.
   *   - vertical: string - Vertical del negocio.
   *   - input_tokens: int - Tokens de entrada.
   *   - output_tokens: int - Tokens de salida.
   *   - cost: float - Costo de la ejecución.
   *   - duration_ms: int - Duración en milisegundos.
   *   - success: bool - Si fue exitosa.
   *   - error_message: string - Mensaje de error si falló.
   *   - quality_score: float - Puntuación de calidad (0-1).
   *   - trace_id: string - (GAP-02) UUID del trace.
   *   - span_id: string - (GAP-02) UUID del span.
   *   - parent_span_id: string - (GAP-02) UUID del span padre.
   *   - operation_name: string - (GAP-02) Nombre de la operacion.
   */
  public function log(array $data): void {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_usage_log');

      // GAP-02: Merge automatico de trace context si disponible.
      $traceData = [];
      if ($this->traceContext) {
        $traceData = $this->traceContext->getSpanContext();
      }

      $log = $storage->create([
        'agent_id' => $data['agent_id'] ?? '',
        'action' => $data['action'] ?? '',
        'tier' => $data['tier'] ?? '',
        'model_id' => $data['model_id'] ?? '',
        'provider_id' => $data['provider_id'] ?? '',
        'tenant_id' => $data['tenant_id'] ?? '',
        'vertical' => $data['vertical'] ?? '',
        'input_tokens' => $data['input_tokens'] ?? 0,
        'output_tokens' => $data['output_tokens'] ?? 0,
        'cost' => $data['cost'] ?? 0,
        'duration_ms' => $data['duration_ms'] ?? 0,
        'success' => $data['success'] ?? TRUE,
        'error_message' => $data['error_message'] ?? '',
        'quality_score' => $data['quality_score'] ?? NULL,
        'user_id' => $this->currentUser->id(),
        // GAP-02: Campos de tracing — datos explicitos > contexto automatico.
        'trace_id' => $data['trace_id'] ?? ($traceData['trace_id'] ?? NULL),
        'span_id' => $data['span_id'] ?? ($traceData['span_id'] ?? NULL),
        'parent_span_id' => $data['parent_span_id'] ?? ($traceData['parent_span_id'] ?? NULL),
        'operation_name' => $data['operation_name'] ?? ($traceData['operation_name'] ?? NULL),
      ]);

      $log->save();

      // GAP-AUD-002: Bridge to TenantMeteringService for billing.
      $tenantId = $data['tenant_id'] ?? '';
      if ($this->tenantMetering && !empty($tenantId)) {
        try {
          $totalTokens = (float) (($data['input_tokens'] ?? 0) + ($data['output_tokens'] ?? 0));
          if ($totalTokens > 0) {
            $this->tenantMetering->record($tenantId, 'ai_tokens', $totalTokens, [
              'agent_id' => $data['agent_id'] ?? '',
              'model_id' => $data['model_id'] ?? '',
              'tier' => $data['tier'] ?? '',
              'cost' => $data['cost'] ?? 0,
            ]);
          }
        }
        catch (\Exception $meteringError) {
          $this->logger->warning('AI metering bridge failed for tenant @tenant: @msg', [
            '@tenant' => $tenantId,
            '@msg' => $meteringError->getMessage(),
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error al registrar uso de IA: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Registra un log con span completo (GAP-02).
   *
   * Metodo de conveniencia que inicia un span, ejecuta el log, y cierra
   * el span. Util cuando se quiere loguear una operacion completa de golpe.
   *
   * @param string $operationName
   *   Nombre de la operacion.
   * @param array $data
   *   Datos del log (misma estructura que log()).
   *
   * @return int|null
   *   ID de la entidad log creada, o NULL si fallo.
   */
  public function logSpan(string $operationName, array $data): ?int {
    $spanId = NULL;

    if ($this->traceContext) {
      $spanId = $this->traceContext->startSpan($operationName);
      $data['span_id'] = $spanId;
      $data['operation_name'] = $operationName;
      $data['trace_id'] = $this->traceContext->getCurrentTraceId();
      $data['parent_span_id'] = $this->traceContext->getParentSpanId($spanId);
    }

    $this->log($data);

    if ($spanId && $this->traceContext) {
      $this->traceContext->endSpan($spanId);
    }

    return NULL;
  }

  /**
   * Obtiene todos los spans de un trace (GAP-02).
   *
   * @param string $traceId
   *   El UUID del trace.
   *
   * @return array
   *   Array de entidades AIUsageLog ordenadas por created ASC.
   */
  public function getTraceSpans(string $traceId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_usage_log');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('trace_id', $traceId)
        ->sort('created', 'ASC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      return $storage->loadMultiple($ids);
    }
    catch (\Exception $e) {
      $this->logger->error('GAP-02: Error obteniendo spans del trace @trace: @msg', [
        '@trace' => $traceId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

    /**
     * Obtiene estadísticas de uso para un período de tiempo.
     *
     * @param string $period
     *   Período: 'day', 'week', 'month', 'year'.
     * @param string|null $tenantId
     *   Filtro opcional por tenant.
     *
     * @return array
     *   Array de estadísticas:
     *   - total_executions: int
     *   - successful: int
     *   - failed: int
     *   - success_rate: float (porcentaje)
     *   - total_cost: float
     *   - total_tokens: int
     *   - avg_duration_ms: int
     *   - avg_quality_score: float|null
     */
    public function getStats(string $period = 'day', ?string $tenantId = NULL): array
    {
        $startTime = $this->getPeriodStart($period);

        $query = $this->entityTypeManager->getStorage('ai_usage_log')->getQuery()
            ->accessCheck(FALSE)
            ->condition('created', $startTime, '>=');

        if ($tenantId) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();

        if (empty($ids)) {
            return $this->getEmptyStats();
        }

        $logs = $this->entityTypeManager->getStorage('ai_usage_log')->loadMultiple($ids);

        return $this->calculateStats($logs);
    }

    /**
     * Obtiene desglose de costos por tier.
     *
     * @param string $period
     *   Período: 'day', 'week', 'month', 'year'.
     *
     * @return array
     *   Desglose de costos por tier:
     *   - fast: float
     *   - balanced: float
     *   - premium: float
     */
    public function getCostByTier(string $period = 'month'): array
    {
        $startTime = $this->getPeriodStart($period);

        $query = $this->entityTypeManager->getStorage('ai_usage_log')->getQuery()
            ->accessCheck(FALSE)
            ->condition('created', $startTime, '>=');

        $ids = $query->execute();
        $logs = $this->entityTypeManager->getStorage('ai_usage_log')->loadMultiple($ids);

        $byTier = ['fast' => 0, 'balanced' => 0, 'premium' => 0];

        foreach ($logs as $log) {
            $tier = $log->getTier() ?: 'balanced';
            if (isset($byTier[$tier])) {
                $byTier[$tier] += $log->getCost();
            }
        }

        return array_map(fn($cost) => round($cost, 4), $byTier);
    }

    /**
     * Obtiene uso por agente.
     *
     * @param string $period
     *   Período de tiempo.
     *
     * @return array
     *   Estadísticas por agente:
     *   - [agent_id] => ['count' => int, 'cost' => float, 'success_rate' => float]
     */
    public function getUsageByAgent(string $period = 'month'): array
    {
        $startTime = $this->getPeriodStart($period);

        $query = $this->entityTypeManager->getStorage('ai_usage_log')->getQuery()
            ->accessCheck(FALSE)
            ->condition('created', $startTime, '>=');

        $ids = $query->execute();
        $logs = $this->entityTypeManager->getStorage('ai_usage_log')->loadMultiple($ids);

        $byAgent = [];

        foreach ($logs as $log) {
            $agentId = $log->getAgentId();
            if (!isset($byAgent[$agentId])) {
                $byAgent[$agentId] = ['count' => 0, 'cost' => 0, 'success_rate' => 0, 'successes' => 0];
            }
            $byAgent[$agentId]['count']++;
            $byAgent[$agentId]['cost'] += $log->getCost();
            if ($log->isSuccessful()) {
                $byAgent[$agentId]['successes']++;
            }
        }

        // Calcular tasas de éxito.
        foreach ($byAgent as $agentId => &$data) {
            $data['success_rate'] = $data['count'] > 0
                ? round(($data['successes'] / $data['count']) * 100, 1)
                : 0;
            $data['cost'] = round($data['cost'], 4);
            unset($data['successes']);
        }

        return $byAgent;
    }

    /**
     * Calcula el ahorro por Model Routing.
     *
     * Compara el costo real con lo que costaría si todas
     * las llamadas usaran el tier premium.
     *
     * @param string $period
     *   Período de tiempo.
     *
     * @return array
     *   Cálculo de ahorros:
     *   - actual_cost: float - Costo real incurrido.
     *   - premium_equivalent: float - Costo si todo fuera premium.
     *   - savings: float - Ahorro absoluto.
     *   - savings_percent: float - Porcentaje de ahorro.
     */
    public function getSavings(string $period = 'month'): array
    {
        $startTime = $this->getPeriodStart($period);

        $query = $this->entityTypeManager->getStorage('ai_usage_log')->getQuery()
            ->accessCheck(FALSE)
            ->condition('created', $startTime, '>=');

        $ids = $query->execute();
        $logs = $this->entityTypeManager->getStorage('ai_usage_log')->loadMultiple($ids);

        $actualCost = 0;
        $premiumEquivalent = 0;

        // Costo del tier premium por 1K tokens (promedio).
        $premiumCostPer1K = 0.045;

        foreach ($logs as $log) {
            $actualCost += $log->getCost();
            $totalTokens = ($log->get('input_tokens')->value ?? 0) + ($log->get('output_tokens')->value ?? 0);
            $premiumEquivalent += ($totalTokens / 1000) * $premiumCostPer1K;
        }

        $savings = max(0, $premiumEquivalent - $actualCost);
        $savingsPercent = $premiumEquivalent > 0 ? ($savings / $premiumEquivalent) * 100 : 0;

        return [
            'actual_cost' => round($actualCost, 4),
            'premium_equivalent' => round($premiumEquivalent, 4),
            'savings' => round($savings, 4),
            'savings_percent' => round($savingsPercent, 1),
        ];
    }

    /**
     * Obtiene el timestamp de inicio para un período.
     *
     * @param string $period
     *   El período: day, week, month, year.
     *
     * @return int
     *   Timestamp de inicio del período.
     */
    protected function getPeriodStart(string $period): int
    {
        return match ($period) {
            'day' => strtotime('-1 day'),
            'week' => strtotime('-1 week'),
            'month' => strtotime('-1 month'),
            'year' => strtotime('-1 year'),
            default => strtotime('-1 day'),
        };
    }

    /**
     * Retorna estructura de estadísticas vacía.
     *
     * @return array
     *   Estadísticas vacías con valores por defecto.
     */
    protected function getEmptyStats(): array
    {
        return [
            'total_executions' => 0,
            'successful' => 0,
            'failed' => 0,
            'success_rate' => 0,
            'total_cost' => 0,
            'total_tokens' => 0,
            'avg_duration_ms' => 0,
            'avg_quality_score' => NULL,
        ];
    }

    /**
     * Calcula estadísticas a partir de entidades de log.
     *
     * @param array $logs
     *   Array de entidades AIUsageLog.
     *
     * @return array
     *   Estadísticas calculadas.
     */
    protected function calculateStats(array $logs): array
    {
        $total = count($logs);
        $successful = 0;
        $totalCost = 0;
        $totalTokens = 0;
        $totalDuration = 0;
        $qualityScores = [];

        foreach ($logs as $log) {
            if ($log->isSuccessful()) {
                $successful++;
            }
            $totalCost += $log->getCost();
            $totalTokens += ($log->get('input_tokens')->value ?? 0) + ($log->get('output_tokens')->value ?? 0);
            $totalDuration += $log->get('duration_ms')->value ?? 0;

            $quality = $log->get('quality_score')->value;
            if ($quality !== NULL) {
                $qualityScores[] = (float) $quality;
            }
        }

        return [
            'total_executions' => $total,
            'successful' => $successful,
            'failed' => $total - $successful,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
            'total_cost' => round($totalCost, 4),
            'total_tokens' => $totalTokens,
            'avg_duration_ms' => $total > 0 ? round($totalDuration / $total) : 0,
            'avg_quality_score' => !empty($qualityScores)
                ? round(array_sum($qualityScores) / count($qualityScores), 2)
                : NULL,
        ];
    }

}
