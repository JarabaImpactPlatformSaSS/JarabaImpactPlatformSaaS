<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\Crypt;

/**
 * Servicio de A/B testing para prompts de IA.
 *
 * PROPÓSITO:
 * Permite experimentar con diferentes versiones de prompts
 * para optimizar resultados de los agentes de IA.
 *
 * Q3 2026 - Sprint 9-10: AI Operations
 */
class AIPromptABTestingService
{

    /**
     * Estados de experimento.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
    ) {
    }

    /**
     * Crea un nuevo experimento A/B.
     */
    public function createExperiment(array $data): string
    {
        $experimentId = Crypt::randomBytesBase64(12);

        $this->database->insert('ai_ab_experiments')
            ->fields([
                    'id' => $experimentId,
                    'name' => $data['name'],
                    'description' => $data['description'] ?? '',
                    'agent_id' => $data['agent_id'],
                    'metric' => $data['metric'] ?? 'success_rate',
                    'traffic_split' => $data['traffic_split'] ?? 50,
                    'min_sample_size' => $data['min_sample_size'] ?? 100,
                    'status' => self::STATUS_DRAFT,
                    'created' => time(),
                    'updated' => time(),
                ])
            ->execute();

        // Crear variantes.
        foreach ($data['variants'] as $index => $variant) {
            $this->database->insert('ai_ab_variants')
                ->fields([
                        'id' => Crypt::randomBytesBase64(8),
                        'experiment_id' => $experimentId,
                        'name' => $variant['name'] ?? 'Variant ' . chr(65 + $index),
                        'prompt_template' => $variant['prompt'],
                        'is_control' => $index === 0 ? 1 : 0,
                        'impressions' => 0,
                        'conversions' => 0,
                    ])
                ->execute();
        }

        return $experimentId;
    }

    /**
     * Inicia un experimento.
     */
    public function startExperiment(string $experimentId): bool
    {
        return $this->database->update('ai_ab_experiments')
            ->fields([
                    'status' => self::STATUS_RUNNING,
                    'started_at' => time(),
                    'updated' => time(),
                ])
            ->condition('id', $experimentId)
            ->condition('status', self::STATUS_DRAFT)
            ->execute() > 0;
    }

    /**
     * Selecciona una variante para un usuario.
     */
    public function getVariant(string $experimentId, string $userId): ?array
    {
        $experiment = $this->getExperiment($experimentId);

        if (!$experiment || $experiment->status !== self::STATUS_RUNNING) {
            return NULL;
        }

        // Asignación determinística por usuario (consistente).
        $hash = crc32($experimentId . $userId);
        $bucket = $hash % 100;

        // Obtener variantes.
        $variants = $this->database->select('ai_ab_variants', 'v')
            ->fields('v')
            ->condition('experiment_id', $experimentId)
            ->execute()
            ->fetchAll();

        if (empty($variants)) {
            return NULL;
        }

        // Seleccionar variante basándose en traffic_split.
        $controlThreshold = $experiment->traffic_split;
        $selectedVariant = $bucket < $controlThreshold ? $variants[0] : ($variants[1] ?? $variants[0]);

        // Registrar impresión.
        $this->recordImpression($selectedVariant->id);

        return [
            'variant_id' => $selectedVariant->id,
            'variant_name' => $selectedVariant->name,
            'prompt_template' => $selectedVariant->prompt_template,
            'is_control' => (bool) $selectedVariant->is_control,
        ];
    }

    /**
     * Registra una impresión.
     */
    protected function recordImpression(string $variantId): void
    {
        $this->database->update('ai_ab_variants')
            ->expression('impressions', 'impressions + 1')
            ->condition('id', $variantId)
            ->execute();
    }

    /**
     * Registra una conversión (éxito).
     */
    public function recordConversion(string $variantId, array $metadata = []): void
    {
        $this->database->update('ai_ab_variants')
            ->expression('conversions', 'conversions + 1')
            ->condition('id', $variantId)
            ->execute();

        // Log detallado.
        $this->database->insert('ai_ab_conversion_logs')
            ->fields([
                    'variant_id' => $variantId,
                    'metadata' => json_encode($metadata),
                    'created' => time(),
                ])
            ->execute();
    }

    /**
     * Obtiene resultados de un experimento.
     */
    public function getResults(string $experimentId): array
    {
        $experiment = $this->getExperiment($experimentId);

        if (!$experiment) {
            return ['error' => 'Experiment not found'];
        }

        $variants = $this->database->select('ai_ab_variants', 'v')
            ->fields('v')
            ->condition('experiment_id', $experimentId)
            ->execute()
            ->fetchAll();

        $results = [
            'experiment' => [
                'id' => $experiment->id,
                'name' => $experiment->name,
                'status' => $experiment->status,
                'metric' => $experiment->metric,
            ],
            'variants' => [],
            'winner' => NULL,
            'statistical_significance' => NULL,
        ];

        $controlRate = NULL;

        foreach ($variants as $variant) {
            $impressions = (int) $variant->impressions;
            $conversions = (int) $variant->conversions;
            $conversionRate = $impressions > 0 ? round(($conversions / $impressions) * 100, 2) : 0;

            $variantResult = [
                'id' => $variant->id,
                'name' => $variant->name,
                'is_control' => (bool) $variant->is_control,
                'impressions' => $impressions,
                'conversions' => $conversions,
                'conversion_rate' => $conversionRate,
                'uplift' => NULL,
            ];

            if ($variant->is_control) {
                $controlRate = $conversionRate;
            } else {
                $variantResult['uplift'] = $controlRate !== NULL && $controlRate > 0
                    ? round((($conversionRate - $controlRate) / $controlRate) * 100, 2)
                    : NULL;
            }

            $results['variants'][] = $variantResult;
        }

        // Determinar ganador.
        $results['winner'] = $this->determineWinner($results['variants'], $experiment->min_sample_size);
        $results['statistical_significance'] = $this->calculateSignificance($results['variants']);

        return $results;
    }

    /**
     * Determina el ganador.
     */
    protected function determineWinner(array $variants, int $minSampleSize): ?array
    {
        $validVariants = array_filter($variants, fn($v) => $v['impressions'] >= $minSampleSize);

        if (count($validVariants) < 2) {
            return ['status' => 'insufficient_data', 'message' => 'Need more impressions'];
        }

        usort($validVariants, fn($a, $b) => $b['conversion_rate'] <=> $a['conversion_rate']);

        $best = $validVariants[0];
        $second = $validVariants[1] ?? NULL;

        // Requiere al menos 10% de diferencia para declarar ganador.
        if ($second && ($best['conversion_rate'] - $second['conversion_rate']) < 1) {
            return ['status' => 'inconclusive', 'message' => 'No clear winner yet'];
        }

        return [
            'status' => 'winner',
            'variant_id' => $best['id'],
            'variant_name' => $best['name'],
            'conversion_rate' => $best['conversion_rate'],
        ];
    }

    /**
     * Calcula significancia estadística (simplificado).
     */
    protected function calculateSignificance(array $variants): float
    {
        if (count($variants) < 2) {
            return 0;
        }

        // Z-test simplificado.
        $control = array_values(array_filter($variants, fn($v) => $v['is_control']))[0] ?? NULL;
        $treatment = array_values(array_filter($variants, fn($v) => !$v['is_control']))[0] ?? NULL;

        if (!$control || !$treatment) {
            return 0;
        }

        $n1 = max(1, $control['impressions']);
        $n2 = max(1, $treatment['impressions']);
        $p1 = $control['conversion_rate'] / 100;
        $p2 = $treatment['conversion_rate'] / 100;

        $pooledP = ($control['conversions'] + $treatment['conversions']) / ($n1 + $n2);
        $se = sqrt($pooledP * (1 - $pooledP) * (1 / $n1 + 1 / $n2));

        if ($se == 0) {
            return 0;
        }

        $z = abs($p1 - $p2) / $se;

        // Convertir Z a confidence.
        $confidence = min(99.9, $z * 38.3); // Aproximación simplificada

        return round($confidence, 1);
    }

    /**
     * Obtiene un experimento.
     */
    public function getExperiment(string $experimentId): ?object
    {
        return $this->database->select('ai_ab_experiments', 'e')
            ->fields('e')
            ->condition('id', $experimentId)
            ->execute()
            ->fetchObject() ?: NULL;
    }

    /**
     * Lista experimentos.
     */
    public function listExperiments(?string $status = NULL, int $limit = 20): array
    {
        $query = $this->database->select('ai_ab_experiments', 'e')
            ->fields('e')
            ->orderBy('created', 'DESC')
            ->range(0, $limit);

        if ($status) {
            $query->condition('status', $status);
        }

        return $query->execute()->fetchAll();
    }

    /**
     * Finaliza un experimento.
     */
    public function completeExperiment(string $experimentId): bool
    {
        return $this->database->update('ai_ab_experiments')
            ->fields([
                    'status' => self::STATUS_COMPLETED,
                    'completed_at' => time(),
                    'updated' => time(),
                ])
            ->condition('id', $experimentId)
            ->execute() > 0;
    }

}
