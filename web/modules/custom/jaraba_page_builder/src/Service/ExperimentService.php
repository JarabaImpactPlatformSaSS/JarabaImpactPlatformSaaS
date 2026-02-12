<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_page_builder\Entity\PageExperiment;
use Drupal\jaraba_page_builder\Entity\ExperimentVariant;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio para gestión de experimentos A/B Testing y Multivariate.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Funcionalidades:
 * - Asignar variante a visitante (cookie)
 * - Calcular conversión rate
 * - Calcular Z-score y confidence level
 * - Determinar ganador estadístico
 * - Análisis multivariate con matrix pairwise (P3-03)
 * - Integración GA4 para eventos de experimento (P3-03)
 *
 * @package Drupal\jaraba_page_builder\Service
 */
class ExperimentService
{

    /**
     * Nombre de la cookie para tracking.
     */
    const COOKIE_NAME = 'jaraba_ab_test';

    /**
     * Duración de la cookie (30 días).
     */
    const COOKIE_DURATION = 2592000;

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Request stack.
     *
     * @var \Symfony\Component\HttpFoundation\RequestStack
     */
    protected RequestStack $requestStack;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        RequestStack $request_stack
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->requestStack = $request_stack;
    }

    /**
     * Obtiene el experimento activo para una página.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return \Drupal\jaraba_page_builder\Entity\PageExperiment|null
     *   El experimento activo o null.
     */
    public function getActiveExperiment(int $pageId): ?PageExperiment
    {
        try {
            $storage = $this->entityTypeManager->getStorage('page_experiment');
            $query = $storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('page_id', $pageId)
                ->condition('status', PageExperiment::STATUS_RUNNING);

            $ids = $query->execute();
            if (empty($ids)) {
                return NULL;
            }

            $id = reset($ids);
            return $storage->load($id);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_page_builder')->error('Error getting experiment: @message', [
                '@message' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Asigna una variante al visitante actual.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageExperiment $experiment
     *   El experimento.
     *
     * @return \Drupal\jaraba_page_builder\Entity\ExperimentVariant|null
     *   La variante asignada o null.
     */
    public function assignVariant(PageExperiment $experiment): ?ExperimentVariant
    {
        $experimentId = $experiment->id();

        // Verificar si ya tiene una variante asignada (cookie).
        $assignedVariantId = $this->getAssignedVariantId($experimentId);
        if ($assignedVariantId) {
            try {
                $variant = $this->entityTypeManager->getStorage('experiment_variant')->load($assignedVariantId);
                if ($variant) {
                    return $variant;
                }
            } catch (\Exception $e) {
                // Continuar para asignar nueva variante.
            }
        }

        // Obtener variantes del experimento.
        $variants = $this->getExperimentVariants($experiment);
        if (empty($variants)) {
            return NULL;
        }

        // Seleccionar variante basada en pesos.
        $selectedVariant = $this->selectVariantByWeight($variants);
        if (!$selectedVariant) {
            return NULL;
        }

        // Guardar en cookie.
        $this->setAssignedVariant($experimentId, (int) $selectedVariant->id());

        // Incrementar contador de visitantes.
        $selectedVariant->incrementVisitors();
        $selectedVariant->save();

        return $selectedVariant;
    }

    /**
     * Obtiene las variantes de un experimento.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageExperiment $experiment
     *   El experimento.
     *
     * @return array
     *   Array de entidades ExperimentVariant.
     */
    public function getExperimentVariants(PageExperiment $experiment): array
    {
        try {
            $storage = $this->entityTypeManager->getStorage('experiment_variant');
            $query = $storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('experiment_id', $experiment->id());

            $ids = $query->execute();
            return $storage->loadMultiple($ids);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Selecciona una variante basada en pesos de tráfico.
     *
     * @param array $variants
     *   Array de variantes.
     *
     * @return \Drupal\jaraba_page_builder\Entity\ExperimentVariant|null
     *   Variante seleccionada.
     */
    protected function selectVariantByWeight(array $variants): ?ExperimentVariant
    {
        if (empty($variants)) {
            return NULL;
        }

        // Construir array de pesos acumulados.
        $totalWeight = 0;
        $weightedVariants = [];

        foreach ($variants as $variant) {
            $weight = $variant->getTrafficWeight();
            $totalWeight += $weight;
            $weightedVariants[] = [
                'variant' => $variant,
                'cumulative_weight' => $totalWeight,
            ];
        }

        if ($totalWeight === 0) {
            // Distribución uniforme si todos los pesos son 0.
            return $variants[array_rand($variants)];
        }

        // Número aleatorio entre 1 y totalWeight.
        $random = mt_rand(1, $totalWeight);

        // Seleccionar variante según peso.
        foreach ($weightedVariants as $item) {
            if ($random <= $item['cumulative_weight']) {
                return $item['variant'];
            }
        }

        return $variants[0];
    }

    /**
     * Obtiene el ID de variante asignada de la cookie.
     *
     * @param int $experimentId
     *   ID del experimento.
     *
     * @return int|null
     *   ID de variante o null.
     */
    protected function getAssignedVariantId(int $experimentId): ?int
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return NULL;
        }

        $cookie = $request->cookies->get(self::COOKIE_NAME);
        if (!$cookie) {
            return NULL;
        }

        $data = json_decode($cookie, TRUE);
        if (!is_array($data) || !isset($data[$experimentId])) {
            return NULL;
        }

        return (int) $data[$experimentId];
    }

    /**
     * Guarda la asignación de variante en cookie.
     *
     * @param int $experimentId
     *   ID del experimento.
     * @param int $variantId
     *   ID de la variante.
     */
    protected function setAssignedVariant(int $experimentId, int $variantId): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $existingCookie = $request->cookies->get(self::COOKIE_NAME);
        $data = $existingCookie ? (json_decode($existingCookie, TRUE) ?: []) : [];
        $data[$experimentId] = $variantId;

        $cookieValue = json_encode($data);
        $expires = time() + self::COOKIE_DURATION;

        // Usar setrawcookie para garantizar correcta codificación.
        setcookie(self::COOKIE_NAME, $cookieValue, [
            'expires' => $expires,
            'path' => '/',
            'secure' => TRUE,
            'httponly' => TRUE,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Registra una conversión para una variante.
     *
     * @param int $experimentId
     *   ID del experimento.
     * @param int $variantId
     *   ID de la variante.
     *
     * @return bool
     *   TRUE si se registró correctamente.
     */
    public function recordConversion(int $experimentId, int $variantId): bool
    {
        try {
            $variant = $this->entityTypeManager->getStorage('experiment_variant')->load($variantId);
            if (!$variant || $variant->getExperimentId() !== $experimentId) {
                return FALSE;
            }

            $variant->incrementConversions();
            $variant->save();

            return TRUE;
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_page_builder')->error('Error recording conversion: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Calcula el Z-score entre dos variantes.
     *
     * @param int $visitors1
     *   Visitantes de variante 1.
     * @param int $conversions1
     *   Conversiones de variante 1.
     * @param int $visitors2
     *   Visitantes de variante 2.
     * @param int $conversions2
     *   Conversiones de variante 2.
     *
     * @return float
     *   Z-score.
     */
    public function calculateZScore(int $visitors1, int $conversions1, int $visitors2, int $conversions2): float
    {
        if ($visitors1 === 0 || $visitors2 === 0) {
            return 0.0;
        }

        $p1 = $conversions1 / $visitors1;
        $p2 = $conversions2 / $visitors2;

        // Pooled probability.
        $pPooled = ($conversions1 + $conversions2) / ($visitors1 + $visitors2);

        if ($pPooled === 0.0 || $pPooled === 1.0) {
            return 0.0;
        }

        // Standard error.
        $se = sqrt($pPooled * (1 - $pPooled) * ((1 / $visitors1) + (1 / $visitors2)));

        if ($se === 0.0) {
            return 0.0;
        }

        return ($p1 - $p2) / $se;
    }

    /**
     * Convierte Z-score a nivel de confianza.
     *
     * @param float $zScore
     *   Z-score (absoluto).
     *
     * @return float
     *   Nivel de confianza (0-100).
     */
    public function zScoreToConfidence(float $zScore): float
    {
        // Aproximación usando función de distribución normal.
        // Para |z| > 3.5, retornar 99.9% para simplificar.
        $absZ = abs($zScore);

        if ($absZ < 0.1) {
            return 50.0;
        }

        // Lookup table simplificada.
        $table = [
            0.25 => 60,
            0.5 => 69,
            0.67 => 75,
            0.84 => 80,
            1.0 => 84,
            1.28 => 90,
            1.64 => 95,
            1.96 => 97.5,
            2.33 => 99,
            2.58 => 99.5,
            3.0 => 99.7,
            3.5 => 99.9,
        ];

        foreach ($table as $z => $confidence) {
            if ($absZ <= $z) {
                return $confidence;
            }
        }

        return 99.9;
    }

    /**
     * Analiza los resultados de un experimento.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageExperiment $experiment
     *   El experimento.
     *
     * @return array
     *   Resultados del análisis.
     */
    public function analyzeResults(PageExperiment $experiment): array
    {
        $variants = $this->getExperimentVariants($experiment);

        if (count($variants) < 2) {
            return [
                'status' => 'insufficient_variants',
                'message' => 'Se necesitan al menos 2 variantes para analizar.',
                'variants' => [],
                'winner' => NULL,
                'confidence' => 0.0,
            ];
        }

        // Encontrar control y mejor variante.
        $control = NULL;
        $bestVariant = NULL;
        $bestRate = 0.0;

        $variantResults = [];

        foreach ($variants as $variant) {
            $rate = $variant->getConversionRate();

            $variantResults[] = [
                'id' => $variant->id(),
                'name' => $variant->getName(),
                'is_control' => $variant->isControl(),
                'visitors' => $variant->getVisitors(),
                'conversions' => $variant->getConversions(),
                'conversion_rate' => round($rate, 2),
            ];

            if ($variant->isControl()) {
                $control = $variant;
            }

            if ($rate > $bestRate) {
                $bestRate = $rate;
                $bestVariant = $variant;
            }
        }

        if (!$control || !$bestVariant) {
            return [
                'status' => 'no_control',
                'message' => 'No se encontró variante de control.',
                'variants' => $variantResults,
                'winner' => NULL,
                'confidence' => 0.0,
            ];
        }

        // Calcular Z-score entre control y mejor variante.
        $zScore = $this->calculateZScore(
            $control->getVisitors(),
            $control->getConversions(),
            $bestVariant->getVisitors(),
            $bestVariant->getConversions()
        );

        $confidence = $this->zScoreToConfidence($zScore);
        $threshold = $experiment->getConfidenceThreshold();

        // Determinar ganador si hay suficiente confianza.
        $winner = NULL;
        $status = 'running';

        if ($confidence >= $threshold) {
            $winner = $bestVariant->id();
            $status = 'significant';
        } elseif ($control->getVisitors() + $bestVariant->getVisitors() < 100) {
            $status = 'insufficient_data';
        }

        return [
            'status' => $status,
            'message' => $this->getStatusMessage($status, $confidence, $threshold),
            'variants' => $variantResults,
            'winner' => $winner,
            'winner_name' => $winner ? $bestVariant->getName() : NULL,
            'confidence' => round($confidence, 1),
            'threshold' => $threshold,
            'z_score' => round($zScore, 3),
            'control_rate' => round($control->getConversionRate(), 2),
            'best_rate' => round($bestRate, 2),
            'improvement' => $control->getConversionRate() > 0 ?
                round((($bestRate - $control->getConversionRate()) / $control->getConversionRate()) * 100, 1) : 0,
        ];
    }

    /**
     * Obtiene mensaje de estado legible.
     *
     * @param string $status
     *   Código de estado.
     * @param float $confidence
     *   Nivel de confianza actual.
     * @param float $threshold
     *   Umbral requerido.
     *
     * @return string
     *   Mensaje legible.
     */
    protected function getStatusMessage(string $status, float $confidence, float $threshold): string
    {
        switch ($status) {
            case 'significant':
                return "Resultado estadísticamente significativo con {$confidence}% de confianza.";

            case 'insufficient_data':
                return "Datos insuficientes. Se necesitan más visitantes para determinar un ganador.";

            case 'running':
                $needed = $threshold - $confidence;
                return "En progreso. Necesita {$needed}% más de confianza para alcanzar el umbral de {$threshold}%.";

            default:
                return "Estado: {$status}";
        }
    }

    /**
     * Declara un ganador y completa el experimento.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageExperiment $experiment
     *   El experimento.
     * @param int $winnerVariantId
     *   ID de la variante ganadora.
     *
     * @return bool
     *   TRUE si se completó correctamente.
     */
    public function declareWinner(PageExperiment $experiment, int $winnerVariantId): bool
    {
        try {
            $experiment->complete($winnerVariantId);
            $experiment->save();
            return TRUE;
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_page_builder')->error('Error declaring winner: @message', [
                '@message' => $e->getMessage(),
            ]);
            return FALSE;
        }
    }

    /**
     * Analisis multivariate: matrix de comparacion pairwise.
     *
     * Compara cada par de variantes entre si y genera una matrix
     * de confianza estadistica para tests multivariantes.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageExperiment $experiment
     *   El experimento.
     *
     * @return array
     *   Resultados multivariate con pairwise matrix.
     */
    public function analyzeMultivariate(PageExperiment $experiment): array
    {
        $variants = $this->getExperimentVariants($experiment);

        if (count($variants) < 2) {
            return [
                'status' => 'insufficient_variants',
                'variants' => [],
                'pairwise_matrix' => [],
                'ranking' => [],
            ];
        }

        // Indexar variantes.
        $variantList = array_values($variants);
        $variantResults = [];
        foreach ($variantList as $variant) {
            $variantResults[] = [
                'id' => (int) $variant->id(),
                'name' => $variant->getName(),
                'is_control' => $variant->isControl(),
                'visitors' => $variant->getVisitors(),
                'conversions' => $variant->getConversions(),
                'conversion_rate' => round($variant->getConversionRate(), 4),
            ];
        }

        // Matrix pairwise: comparar cada par.
        $pairwiseMatrix = [];
        $count = count($variantList);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $vi = $variantList[$i];
                $vj = $variantList[$j];

                $zScore = $this->calculateZScore(
                    $vi->getVisitors(),
                    $vi->getConversions(),
                    $vj->getVisitors(),
                    $vj->getConversions()
                );
                $confidence = $this->zScoreToConfidence($zScore);

                $pairwiseMatrix[] = [
                    'variant_a_id' => (int) $vi->id(),
                    'variant_a_name' => $vi->getName(),
                    'variant_b_id' => (int) $vj->id(),
                    'variant_b_name' => $vj->getName(),
                    'z_score' => round($zScore, 3),
                    'confidence' => round($confidence, 1),
                    'significant' => $confidence >= $experiment->getConfidenceThreshold(),
                    'better' => $zScore > 0 ? $vi->getName() : $vj->getName(),
                    'improvement' => $this->calculateImprovement($vi, $vj),
                ];
            }
        }

        // Ranking por conversion rate.
        $ranking = $variantResults;
        usort($ranking, fn($a, $b) => $b['conversion_rate'] <=> $a['conversion_rate']);

        // Calcular wins count para cada variante.
        $wins = [];
        foreach ($variantList as $v) {
            $wins[(int) $v->id()] = 0;
        }
        foreach ($pairwiseMatrix as $pair) {
            if ($pair['significant']) {
                $winnerId = $pair['z_score'] > 0 ? $pair['variant_a_id'] : $pair['variant_b_id'];
                $wins[$winnerId] = ($wins[$winnerId] ?? 0) + 1;
            }
        }

        foreach ($ranking as &$item) {
            $item['significant_wins'] = $wins[$item['id']] ?? 0;
        }

        return [
            'status' => 'analyzed',
            'experiment_id' => (int) $experiment->id(),
            'experiment_name' => $experiment->getName(),
            'variant_count' => $count,
            'variants' => $variantResults,
            'pairwise_matrix' => $pairwiseMatrix,
            'ranking' => $ranking,
            'threshold' => $experiment->getConfidenceThreshold(),
            'total_visitors' => array_sum(array_column($variantResults, 'visitors')),
            'total_conversions' => array_sum(array_column($variantResults, 'conversions')),
        ];
    }

    /**
     * Calcula mejora relativa entre dos variantes.
     */
    protected function calculateImprovement(ExperimentVariant $a, ExperimentVariant $b): float
    {
        $rateA = $a->getConversionRate();
        $rateB = $b->getConversionRate();
        $baseRate = min($rateA, $rateB);

        if ($baseRate <= 0) {
            return 0.0;
        }

        return round((abs($rateA - $rateB) / $baseRate) * 100, 1);
    }

    /**
     * Despacha evento GA4 para tracking de experimento.
     *
     * Envia el evento a GA4 via ExternalAnalyticsService si esta disponible.
     *
     * @param string $eventName
     *   Nombre del evento GA4 (experiment_impression, experiment_conversion).
     * @param array $params
     *   Parametros del evento.
     */
    public function dispatchGA4Event(string $eventName, array $params): void
    {
        if (!\Drupal::hasService('jaraba_page_builder.external_analytics')) {
            return;
        }

        try {
            $analyticsService = \Drupal::service('jaraba_page_builder.external_analytics');
            if (!$analyticsService->isGA4Active()) {
                return;
            }

            $analyticsService->sendGA4Event($eventName, $params);
        } catch (\Exception $e) {
            // Silently fail — analytics should not break experiments.
        }
    }

}
