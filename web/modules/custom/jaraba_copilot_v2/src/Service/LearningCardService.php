<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Servicio para gestionar Learning Cards según Testing Business Ideas.
 *
 * Una Learning Card documenta los resultados de un experimento:
 * - Hipótesis probada
 * - Observaciones
 * - Aprendizajes
 * - Decisión (pivotar/perseverar)
 *
 * @see https://www.strategyzer.com/blog/test-cards-learning-cards
 */
class LearningCardService
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected AccountProxyInterface $currentUser;
    protected LoggerChannelInterface $logger;

    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountProxyInterface $current_user,
        LoggerChannelInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Crea una Learning Card a partir de un Test Card completado.
     *
     * @param array $testCard
     *   El Test Card original.
     * @param array $observations
     *   Datos observados durante el experimento.
     *
     * @return array
     *   Learning Card estructurada.
     */
    public function createLearningCard(array $testCard, array $observations): array
    {
        $result = $this->determineResult($testCard, $observations);

        $learningCard = [
            'id' => uniqid('lc_'),
            'test_card_id' => $testCard['id'] ?? NULL,
            'created' => date('Y-m-d H:i:s'),
            'user_id' => (int) $this->currentUser->id(),

            // 1. HIPÓTESIS PROBADA
            'hypothesis' => $testCard['hypothesis']['statement'] ?? '',

            // 2. OBSERVACIONES
            'observations' => [
                'data' => $observations['data'] ?? '',
                'sample_achieved' => $observations['sample_size'] ?? 0,
                'sample_target' => $testCard['test']['sample_size'] ?? 0,
                'duration_actual_days' => $observations['duration_days'] ?? 0,
                'unexpected_findings' => $observations['unexpected'] ?? '',
            ],

            // 3. APRENDIZAJES
            'learnings' => [
                'validated' => $result['validated'],
                'key_insight' => $observations['key_insight'] ?? '',
                'customer_quotes' => $observations['quotes'] ?? [],
                'patterns_found' => $observations['patterns'] ?? [],
            ],

            // 4. DECISIÓN
            'decision' => [
                'action' => $result['action'],
                'confidence' => $result['confidence'],
                'next_steps' => $this->suggestNextSteps($result, $testCard),
                'pivot_type' => $result['pivot_type'] ?? NULL,
            ],

            // Metadata
            'status' => 'completed',
            'bmc_block_affected' => $this->detectBmcBlock($testCard['hypothesis']['statement'] ?? ''),
        ];

        // Intentar persistir si existe la entidad
        $this->saveLearning($learningCard);

        return $learningCard;
    }

    /**
     * Determina el resultado del experimento.
     */
    protected function determineResult(array $testCard, array $observations): array
    {
        $metricValue = $observations['metric_value'] ?? 0;
        $successThreshold = $this->parseThreshold($testCard['criteria']['success_threshold'] ?? '');
        $failureThreshold = $this->parseThreshold($testCard['criteria']['failure_threshold'] ?? '');

        if ($metricValue >= $successThreshold) {
            return [
                'validated' => TRUE,
                'action' => 'persevere',
                'confidence' => 'high',
            ];
        } elseif ($metricValue <= $failureThreshold) {
            return [
                'validated' => FALSE,
                'action' => 'pivot',
                'confidence' => 'high',
                'pivot_type' => $this->suggestPivotType($testCard, $observations),
            ];
        } else {
            return [
                'validated' => NULL,
                'action' => 'iterate',
                'confidence' => 'low',
            ];
        }
    }

    /**
     * Parsea un umbral de texto a número.
     */
    protected function parseThreshold(string $threshold): float
    {
        // Extrae el primer número del string
        preg_match('/(\d+(?:\.\d+)?)/', $threshold, $matches);
        return (float) ($matches[1] ?? 0);
    }

    /**
     * Sugiere tipo de pivot basado en el experimento.
     */
    protected function suggestPivotType(array $testCard, array $observations): string
    {
        $hypothesis = mb_strtolower($testCard['hypothesis']['statement'] ?? '');

        if (str_contains($hypothesis, 'cliente') || str_contains($hypothesis, 'segmento')) {
            return 'customer_segment_pivot';
        }
        if (str_contains($hypothesis, 'precio') || str_contains($hypothesis, 'pagar')) {
            return 'value_capture_pivot';
        }
        if (str_contains($hypothesis, 'canal') || str_contains($hypothesis, 'vender')) {
            return 'channel_pivot';
        }
        if (str_contains($hypothesis, 'problema') || str_contains($hypothesis, 'necesidad')) {
            return 'customer_need_pivot';
        }

        return 'general_pivot';
    }

    /**
     * Sugiere próximos pasos.
     */
    protected function suggestNextSteps(array $result, array $testCard): array
    {
        $action = $result['action'];

        $suggestions = [
            'persevere' => [
                'Diseñar siguiente experimento para escalar',
                'Documentar el patrón validado en el BMC',
                'Identificar próxima hipótesis más riesgosa',
            ],
            'pivot' => [
                'Analizar datos para entender el porqué del fracaso',
                'Generar 3 alternativas de pivot',
                'Diseñar nuevo Test Card para la hipótesis pivotada',
            ],
            'iterate' => [
                'Aumentar tamaño de muestra',
                'Refinar el experimento',
                'Recoger más datos cualitativos',
            ],
        ];

        return $suggestions[$action] ?? $suggestions['iterate'];
    }

    /**
     * Detecta qué bloque del BMC afecta la hipótesis.
     *
     * FIX-013: Retorna código 2-letras (CS, VP, etc.) que es el formato
     * almacenado en las entidades Hypothesis y EntrepreneurLearning.
     * Usa BmcValidationService::KEY_TO_CODE para la conversión.
     *
     * @param string $hypothesis
     *   Texto de la hipótesis.
     *
     * @return string
     *   Código 2-letras del bloque BMC (ej: 'VP', 'CS').
     */
    protected function detectBmcBlock(string $hypothesis): string
    {
        $lower = mb_strtolower($hypothesis);

        $blockKeywords = [
            'customer_segments' => ['cliente', 'segmento', 'usuario', 'audiencia'],
            'value_propositions' => ['valor', 'propuesta', 'beneficio', 'problema'],
            'channels' => ['canal', 'distribución', 'vender', 'llegar'],
            'customer_relationships' => ['relación', 'retención', 'fidelización'],
            'revenue_streams' => ['precio', 'pagar', 'ingresos', 'monetizar'],
            'key_resources' => ['recurso', 'infraestructura', 'tecnología'],
            'key_activities' => ['actividad', 'operación', 'proceso'],
            'key_partnerships' => ['partner', 'socio', 'proveedor', 'alianza'],
            'cost_structure' => ['coste', 'gasto', 'margen'],
        ];

        foreach ($blockKeywords as $snakeKey => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lower, $keyword)) {
                    // FIX-013: Convertir snake_case a código 2-letras para la entidad.
                    return BmcValidationService::KEY_TO_CODE[$snakeKey] ?? 'VP';
                }
            }
        }

        return 'VP'; // Default: Value Propositions.
    }

    /**
     * Guarda el aprendizaje en la entidad si existe.
     */
    protected function saveLearning(array $learningCard): void
    {
        try {
            $storage = $this->entityTypeManager->getStorage('entrepreneur_learning');
            $entity = $storage->create([
                'user_id' => $learningCard['user_id'],
                'test_card_id' => $learningCard['test_card_id'],
                'hypothesis' => $learningCard['hypothesis'],
                'validated' => $learningCard['learnings']['validated'],
                'key_insight' => $learningCard['learnings']['key_insight'],
                'decision' => $learningCard['decision']['action'],
                'bmc_block' => $learningCard['bmc_block_affected'],
                'data' => json_encode($learningCard),
            ]);
            $entity->save();

            $this->logger->info('Learning card saved: @id', ['@id' => $entity->id()]);
        } catch (\Exception $e) {
            // Entity may not exist yet, log and continue
            $this->logger->debug('Could not save learning card: @error', [
                '@error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtiene los aprendizajes de un usuario.
     */
    public function getUserLearnings(int $userId, int $limit = 10): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('entrepreneur_learning');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $userId)
                ->sort('created', 'DESC')
                ->range(0, $limit)
                ->execute();

            return $storage->loadMultiple($ids);
        } catch (\Exception $e) {
            return [];
        }
    }

}
