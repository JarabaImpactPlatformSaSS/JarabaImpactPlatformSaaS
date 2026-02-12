<?php

declare(strict_types=1);

namespace Drupal\jaraba_matching\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Telemetry service for embedding and matching operations.
 *
 * Captura métricas de rendimiento y costos para observabilidad:
 * - Latencia de generación de embeddings
 * - Cache hit rates
 * - Costos estimados de API
 * - Errores y timeouts
 */
class EmbeddingTelemetryService
{

    /**
     * State storage.
     *
     * @var \Drupal\Core\State\StateInterface
     */
    protected StateInterface $state;

    /**
     * Logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Cost per 1K tokens for embedding models.
     */
    const COST_PER_1K_TOKENS = [
        'text-embedding-ada-002' => 0.0001,
        'text-embedding-3-small' => 0.00002,
        'text-embedding-3-large' => 0.00013,
    ];

    /**
     * Constructor.
     */
    public function __construct(StateInterface $state, LoggerInterface $logger)
    {
        $this->state = $state;
        $this->logger = $logger;
    }

    /**
     * Records an embedding generation event.
     *
     * @param string $model
     *   Model used.
     * @param int $tokens
     *   Number of tokens processed.
     * @param float $latencyMs
     *   Latency in milliseconds.
     * @param bool $fromCache
     *   Whether result was from cache.
     * @param bool $success
     *   Whether operation succeeded.
     */
    public function recordEmbeddingEvent(
        string $model,
        int $tokens,
        float $latencyMs,
        bool $fromCache = FALSE,
        bool $success = TRUE
    ): void {
        $metrics = $this->getMetrics();
        $today = date('Y-m-d');

        // Initialize daily metrics
        if (!isset($metrics['daily'][$today])) {
            $metrics['daily'][$today] = [
                'total_calls' => 0,
                'cache_hits' => 0,
                'total_tokens' => 0,
                'total_latency_ms' => 0,
                'errors' => 0,
                'estimated_cost' => 0,
            ];
        }

        $daily = &$metrics['daily'][$today];
        $daily['total_calls']++;

        if ($fromCache) {
            $daily['cache_hits']++;
        } else {
            $daily['total_tokens'] += $tokens;
            $costPer1k = self::COST_PER_1K_TOKENS[$model] ?? 0.0001;
            $daily['estimated_cost'] += ($tokens / 1000) * $costPer1k;
        }

        $daily['total_latency_ms'] += $latencyMs;

        if (!$success) {
            $daily['errors']++;
        }

        // Update totals
        $metrics['totals']['total_calls'] = ($metrics['totals']['total_calls'] ?? 0) + 1;
        $metrics['totals']['total_tokens'] = ($metrics['totals']['total_tokens'] ?? 0) + ($fromCache ? 0 : $tokens);
        $metrics['totals']['total_cost'] = ($metrics['totals']['total_cost'] ?? 0) + (($tokens / 1000) * ($costPer1k ?? 0.0001));

        // Keep only last 30 days
        $metrics['daily'] = array_slice($metrics['daily'], -30, 30, TRUE);

        $this->state->set('embedding_telemetry', $metrics);
    }

    /**
     * Records a matching operation.
     *
     * @param string $type
     *   Type of matching (rules, semantic, hybrid).
     * @param int $candidatesScored
     *   Number of candidates scored.
     * @param float $latencyMs
     *   Total latency.
     */
    public function recordMatchingEvent(string $type, int $candidatesScored, float $latencyMs): void
    {
        $metrics = $this->getMetrics();
        $today = date('Y-m-d');

        if (!isset($metrics['matching'][$today])) {
            $metrics['matching'][$today] = [
                'rules' => 0,
                'semantic' => 0,
                'hybrid' => 0,
                'total_candidates' => 0,
                'avg_latency_ms' => 0,
                'total_latency' => 0,
                'calls' => 0,
            ];
        }

        $daily = &$metrics['matching'][$today];
        $daily[$type] = ($daily[$type] ?? 0) + 1;
        $daily['total_candidates'] += $candidatesScored;
        $daily['total_latency'] += $latencyMs;
        $daily['calls']++;
        $daily['avg_latency_ms'] = $daily['total_latency'] / $daily['calls'];

        $this->state->set('embedding_telemetry', $metrics);
    }

    /**
     * Gets current telemetry metrics.
     *
     * @return array
     *   Metrics data.
     */
    public function getMetrics(): array
    {
        $metrics = $this->state->get('embedding_telemetry', []);

        if (empty($metrics)) {
            $metrics = [
                'daily' => [],
                'matching' => [],
                'totals' => [
                    'total_calls' => 0,
                    'total_tokens' => 0,
                    'total_cost' => 0,
                ],
            ];
        }

        return $metrics;
    }

    /**
     * Gets summary for dashboard display.
     *
     * @param int $days
     *   Number of days to summarize.
     *
     * @return array
     *   Summary metrics.
     */
    public function getSummary(int $days = 7): array
    {
        $metrics = $this->getMetrics();
        $cutoff = date('Y-m-d', strtotime("-{$days} days"));

        $summary = [
            'embedding' => [
                'total_calls' => 0,
                'cache_hits' => 0,
                'total_tokens' => 0,
                'estimated_cost' => 0,
                'avg_latency_ms' => 0,
                'errors' => 0,
            ],
            'matching' => [
                'rules' => 0,
                'semantic' => 0,
                'hybrid' => 0,
                'avg_latency_ms' => 0,
            ],
        ];

        $totalLatency = 0;
        $callCount = 0;

        foreach ($metrics['daily'] ?? [] as $date => $data) {
            if ($date >= $cutoff) {
                $summary['embedding']['total_calls'] += $data['total_calls'];
                $summary['embedding']['cache_hits'] += $data['cache_hits'];
                $summary['embedding']['total_tokens'] += $data['total_tokens'];
                $summary['embedding']['estimated_cost'] += $data['estimated_cost'];
                $summary['embedding']['errors'] += $data['errors'];
                $totalLatency += $data['total_latency_ms'];
                $callCount += $data['total_calls'];
            }
        }

        if ($callCount > 0) {
            $summary['embedding']['avg_latency_ms'] = round($totalLatency / $callCount, 2);
        }

        $summary['embedding']['cache_hit_rate'] = $summary['embedding']['total_calls'] > 0
            ? round(($summary['embedding']['cache_hits'] / $summary['embedding']['total_calls']) * 100, 1)
            : 0;

        $matchLatency = 0;
        $matchCalls = 0;

        foreach ($metrics['matching'] ?? [] as $date => $data) {
            if ($date >= $cutoff) {
                $summary['matching']['rules'] += $data['rules'] ?? 0;
                $summary['matching']['semantic'] += $data['semantic'] ?? 0;
                $summary['matching']['hybrid'] += $data['hybrid'] ?? 0;
                $matchLatency += $data['total_latency'] ?? 0;
                $matchCalls += $data['calls'] ?? 0;
            }
        }

        if ($matchCalls > 0) {
            $summary['matching']['avg_latency_ms'] = round($matchLatency / $matchCalls, 2);
        }

        $summary['period_days'] = $days;
        $summary['estimated_cost_formatted'] = '$' . number_format($summary['embedding']['estimated_cost'], 4);

        return $summary;
    }

    /**
     * Clears all telemetry data.
     */
    public function clear(): void
    {
        $this->state->delete('embedding_telemetry');
        $this->logger->info('Embedding telemetry data cleared');
    }

}
