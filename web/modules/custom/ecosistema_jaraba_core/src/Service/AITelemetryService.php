<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de telemetría para agentes de IA.
 *
 * PROPÓSITO:
 * Monitorear el rendimiento de los agentes IA incluyendo:
 * - Latencia de respuesta por agente
 * - Tasa de éxito/error
 * - Costo estimado por invocación
 * - Tokens utilizados
 *
 * USO:
 * ```php
 * $telemetry = \Drupal::service('ecosistema_jaraba_core.ai_telemetry');
 * $invocation = $telemetry->startInvocation('marketing_agent', 'openai');
 * // ... llamada a la API ...
 * $telemetry->endInvocation($invocation, ['tokens_used' => 150, 'success' => true]);
 * ```
 */
class AITelemetryService
{

    /**
     * Logger para el servicio.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor del servicio.
     */
    public function __construct(
        protected Connection $database,
        LoggerChannelFactoryInterface $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get('ai_telemetry');
    }

    /**
     * Inicia una invocación de agente IA.
     *
     * @param string $agentId
     *   Identificador del agente (ej: 'marketing_agent', 'storytelling_agent').
     * @param string $provider
     *   Proveedor de IA (ej: 'openai', 'claude', 'gemini').
     * @param array $metadata
     *   Metadatos adicionales (ej: model, temperature, etc).
     *
     * @return array
     *   Contexto de invocación para pasar a endInvocation().
     */
    public function startInvocation(string $agentId, string $provider, array $metadata = []): array
    {
        return [
            'agent_id' => $agentId,
            'provider' => $provider,
            'start_time' => microtime(TRUE),
            'metadata' => $metadata,
            'request_id' => uniqid('ai_', TRUE),
        ];
    }

    /**
     * Finaliza una invocación y registra las métricas.
     *
     * @param array $invocation
     *   Contexto de invocación de startInvocation().
     * @param array $result
     *   Resultados: 'success', 'tokens_used', 'error_message', etc.
     *
     * @return array
     *   Métricas calculadas.
     */
    public function endInvocation(array $invocation, array $result = []): array
    {
        $endTime = microtime(TRUE);
        $latencyMs = ($endTime - $invocation['start_time']) * 1000;

        $metrics = [
            'request_id' => $invocation['request_id'],
            'agent_id' => $invocation['agent_id'],
            'provider' => $invocation['provider'],
            'latency_ms' => round($latencyMs, 2),
            'success' => $result['success'] ?? TRUE,
            'tokens_used' => $result['tokens_used'] ?? 0,
            'tokens_prompt' => $result['tokens_prompt'] ?? 0,
            'tokens_completion' => $result['tokens_completion'] ?? 0,
            'error_message' => $result['error_message'] ?? NULL,
            'model' => $invocation['metadata']['model'] ?? 'unknown',
            'timestamp' => time(),
            'cost_estimated' => $this->estimateCost(
                $invocation['provider'],
                $result['tokens_prompt'] ?? 0,
                $result['tokens_completion'] ?? 0
            ),
        ];

        // Log estructurado.
        $this->logMetrics($metrics);

        // Persistir en base de datos si existe la tabla.
        $this->persistMetrics($metrics);

        return $metrics;
    }

    /**
     * Estima el costo de una invocación basado en tokens.
     *
     * Precios aproximados por 1000 tokens (Enero 2026):
     * - OpenAI GPT-4o: $0.005 input, $0.015 output
     * - Claude 3.5 Sonnet: $0.003 input, $0.015 output
     * - Gemini 1.5 Pro: $0.00125 input, $0.005 output
     */
    protected function estimateCost(string $provider, int $tokensPrompt, int $tokensCompletion): float
    {
        $rates = [
            'openai' => ['input' => 0.005, 'output' => 0.015],
            'claude' => ['input' => 0.003, 'output' => 0.015],
            'gemini' => ['input' => 0.00125, 'output' => 0.005],
        ];

        $rate = $rates[$provider] ?? ['input' => 0.005, 'output' => 0.015];

        $inputCost = ($tokensPrompt / 1000) * $rate['input'];
        $outputCost = ($tokensCompletion / 1000) * $rate['output'];

        return round($inputCost + $outputCost, 6);
    }

    /**
     * Registra métricas en el log.
     */
    protected function logMetrics(array $metrics): void
    {
        $logLevel = $metrics['success'] ? 'info' : 'warning';

        $message = 'AI Invocation: @agent via @provider | @latency_ms ms | @tokens tokens | $@cost | @status';

        $this->logger->$logLevel($message, [
            '@agent' => $metrics['agent_id'],
            '@provider' => $metrics['provider'],
            '@latency_ms' => $metrics['latency_ms'],
            '@tokens' => $metrics['tokens_used'],
            '@cost' => number_format($metrics['cost_estimated'], 4),
            '@status' => $metrics['success'] ? 'SUCCESS' : 'FAILED: ' . $metrics['error_message'],
        ]);
    }

    /**
     * Persiste métricas en base de datos para análisis.
     */
    protected function persistMetrics(array $metrics): void
    {
        try {
            // Verificar si la tabla existe.
            if (!$this->database->schema()->tableExists('ai_telemetry')) {
                return;
            }

            $this->database->insert('ai_telemetry')
                ->fields([
                        'request_id' => $metrics['request_id'],
                        'agent_id' => $metrics['agent_id'],
                        'provider' => $metrics['provider'],
                        'latency_ms' => $metrics['latency_ms'],
                        'success' => $metrics['success'] ? 1 : 0,
                        'tokens_used' => $metrics['tokens_used'],
                        'tokens_prompt' => $metrics['tokens_prompt'],
                        'tokens_completion' => $metrics['tokens_completion'],
                        'cost_estimated' => $metrics['cost_estimated'],
                        'model' => $metrics['model'],
                        'error_message' => $metrics['error_message'],
                        'created' => $metrics['timestamp'],
                    ])
                ->execute();
        } catch (\Exception $e) {
            // Silenciar errores de persistencia para no afectar el flujo principal.
            $this->logger->debug('Failed to persist AI metrics: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtiene estadísticas agregadas de un agente.
     *
     * @param string $agentId
     *   ID del agente.
     * @param int $periodDays
     *   Período en días para el análisis.
     *
     * @return array
     *   Estadísticas agregadas.
     */
    public function getAgentStats(string $agentId, int $periodDays = 7): array
    {
        if (!$this->database->schema()->tableExists('ai_telemetry')) {
            return $this->getDefaultStats();
        }

        $since = time() - ($periodDays * 86400);

        try {
            $result = $this->database->query("
        SELECT 
          COUNT(*) as total_invocations,
          AVG(latency_ms) as avg_latency_ms,
          MAX(latency_ms) as max_latency_ms,
          MIN(latency_ms) as min_latency_ms,
          SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as success_count,
          SUM(tokens_used) as total_tokens,
          SUM(cost_estimated) as total_cost
        FROM {ai_telemetry}
        WHERE agent_id = :agent_id AND created >= :since
      ", [
                    ':agent_id' => $agentId,
                    ':since' => $since,
                ])->fetchAssoc();

            if ($result && $result['total_invocations'] > 0) {
                $result['success_rate'] = round(($result['success_count'] / $result['total_invocations']) * 100, 2);
                $result['avg_latency_ms'] = round($result['avg_latency_ms'], 2);
                $result['total_cost'] = round($result['total_cost'], 4);
            } else {
                return $this->getDefaultStats();
            }

            return $result;
        } catch (\Exception $e) {
            return $this->getDefaultStats();
        }
    }

    /**
     * Obtiene estadísticas por defecto.
     */
    protected function getDefaultStats(): array
    {
        return [
            'total_invocations' => 0,
            'avg_latency_ms' => 0,
            'max_latency_ms' => 0,
            'min_latency_ms' => 0,
            'success_rate' => 100,
            'total_tokens' => 0,
            'total_cost' => 0,
        ];
    }

    /**
     * Obtiene estadísticas de todos los agentes.
     *
     * @param int $periodDays
     *   Período en días.
     *
     * @return array
     *   Array de estadísticas por agente.
     */
    public function getAllAgentsStats(int $periodDays = 7): array
    {
        if (!$this->database->schema()->tableExists('ai_telemetry')) {
            return [];
        }

        $since = time() - ($periodDays * 86400);

        try {
            $results = $this->database->query("
        SELECT 
          agent_id,
          COUNT(*) as total_invocations,
          AVG(latency_ms) as avg_latency_ms,
          SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as success_rate,
          SUM(cost_estimated) as total_cost
        FROM {ai_telemetry}
        WHERE created >= :since
        GROUP BY agent_id
        ORDER BY total_invocations DESC
      ", [':since' => $since])->fetchAllAssoc('agent_id', \PDO::FETCH_ASSOC);

            return $results ?: [];
        } catch (\Exception $e) {
            return [];
        }
    }

}
