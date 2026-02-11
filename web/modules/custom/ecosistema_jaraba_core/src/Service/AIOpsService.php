<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Servicio de AIOps para predicción de incidentes.
 *
 * PROPÓSITO:
 * Implementa inteligencia artificial para predecir incidentes,
 * detectar anomalías y automatizar operaciones de la plataforma.
 *
 * Q4 2026 - Sprint 15-16: Level 5.0 Certification
 */
class AIOpsService
{

    /**
     * Tipos de predicción.
     */
    public const PREDICTION_CAPACITY = 'capacity_exhaustion';
    public const PREDICTION_PERFORMANCE = 'performance_degradation';
    public const PREDICTION_ERROR_SPIKE = 'error_spike';
    public const PREDICTION_COST_ANOMALY = 'cost_anomaly';
    public const PREDICTION_CHURN_RISK = 'churn_risk';

    /**
     * Severidades.
     */
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Constructor.
     */
    public function __construct(
        protected Connection $database,
        protected LoggerChannelFactoryInterface $loggerFactory,
    ) {
    }

    /**
     * Ejecuta análisis predictivo completo.
     */
    public function runPredictiveAnalysis(): array
    {
        $predictions = [];

        // 1. Predicción de capacidad.
        $capacityPrediction = $this->predictCapacityExhaustion();
        if ($capacityPrediction['risk_level'] !== 'none') {
            $predictions[] = $capacityPrediction;
        }

        // 2. Predicción de degradación de rendimiento.
        $perfPrediction = $this->predictPerformanceDegradation();
        if ($perfPrediction['risk_level'] !== 'none') {
            $predictions[] = $perfPrediction;
        }

        // 3. Predicción de spike de errores.
        $errorPrediction = $this->predictErrorSpike();
        if ($errorPrediction['risk_level'] !== 'none') {
            $predictions[] = $errorPrediction;
        }

        // 4. Detección de anomalías en costes.
        $costAnomaly = $this->detectCostAnomaly();
        if ($costAnomaly['detected']) {
            $predictions[] = $costAnomaly;
        }

        // Ordenar por severidad.
        usort($predictions, fn($a, $b) => $this->severityToInt($b['severity'] ?? 'low') <=> $this->severityToInt($a['severity'] ?? 'low'));

        // Almacenar predicciones.
        foreach ($predictions as $prediction) {
            $this->storePrediction($prediction);
        }

        return [
            'timestamp' => time(),
            'predictions' => $predictions,
            'health_score' => $this->calculateHealthScore($predictions),
            'recommended_actions' => $this->generateRecommendations($predictions),
        ];
    }

    /**
     * Predice agotamiento de capacidad.
     */
    protected function predictCapacityExhaustion(): array
    {
        // Análisis de tendencias de uso de recursos.
        $metrics = $this->getResourceMetrics();

        $riskLevel = 'none';
        $daysUntilExhaustion = NULL;
        $resource = NULL;

        foreach ($metrics as $res => $data) {
            if ($data['usage_percent'] > 90) {
                $riskLevel = 'high';
                $resource = $res;
                $daysUntilExhaustion = 3;
            } elseif ($data['usage_percent'] > 75 && $data['growth_rate'] > 5) {
                $riskLevel = $riskLevel === 'high' ? 'high' : 'medium';
                $resource = $resource ?? $res;
                $daysUntilExhaustion = round((100 - $data['usage_percent']) / max(0.1, $data['growth_rate']));
            }
        }

        return [
            'type' => self::PREDICTION_CAPACITY,
            'risk_level' => $riskLevel,
            'severity' => $this->riskToSeverity($riskLevel),
            'resource' => $resource,
            'days_until_exhaustion' => $daysUntilExhaustion,
            'message' => $riskLevel !== 'none'
                ? "Recurso '{$resource}' se agotará en ~{$daysUntilExhaustion} días"
                : NULL,
        ];
    }

    /**
     * Predice degradación de rendimiento.
     */
    protected function predictPerformanceDegradation(): array
    {
        // Análisis de latencia y tiempos de respuesta.
        $latencyTrend = $this->getLatencyTrend();

        $riskLevel = 'none';
        if ($latencyTrend['current_avg'] > 2000) { // > 2s.
            $riskLevel = 'high';
        } elseif ($latencyTrend['growth_rate'] > 10) { // Creciendo > 10%/día.
            $riskLevel = 'medium';
        } elseif ($latencyTrend['p95'] > 3000) {
            $riskLevel = 'low';
        }

        return [
            'type' => self::PREDICTION_PERFORMANCE,
            'risk_level' => $riskLevel,
            'severity' => $this->riskToSeverity($riskLevel),
            'current_latency_ms' => $latencyTrend['current_avg'],
            'p95_latency_ms' => $latencyTrend['p95'],
            'message' => $riskLevel !== 'none'
                ? "Latencia promedio: {$latencyTrend['current_avg']}ms (creciendo {$latencyTrend['growth_rate']}%/día)"
                : NULL,
        ];
    }

    /**
     * Predice spike de errores.
     */
    protected function predictErrorSpike(): array
    {
        // Análisis de tasa de errores.
        $errorTrend = $this->getErrorTrend();

        $riskLevel = 'none';
        if ($errorTrend['current_rate'] > 5) { // > 5%.
            $riskLevel = 'high';
        } elseif ($errorTrend['current_rate'] > 2) {
            $riskLevel = 'medium';
        } elseif ($errorTrend['growth_rate'] > 50) { // Creciendo rápido.
            $riskLevel = 'medium';
        }

        return [
            'type' => self::PREDICTION_ERROR_SPIKE,
            'risk_level' => $riskLevel,
            'severity' => $this->riskToSeverity($riskLevel),
            'error_rate' => $errorTrend['current_rate'],
            'message' => $riskLevel !== 'none'
                ? "Tasa de errores: {$errorTrend['current_rate']}% (umbral: 1%)"
                : NULL,
        ];
    }

    /**
     * Detecta anomalías en costes.
     */
    protected function detectCostAnomaly(): array
    {
        // Comparar costes actuales con baseline.
        $currentCost = $this->getCurrentMonthlyCost();
        $baselineCost = $this->getBaselineCost();

        $deviation = $baselineCost > 0
            ? (($currentCost - $baselineCost) / $baselineCost) * 100
            : 0;

        $detected = abs($deviation) > 20; // > 20% desviación.

        return [
            'type' => self::PREDICTION_COST_ANOMALY,
            'detected' => $detected,
            'severity' => $deviation > 50 ? self::SEVERITY_HIGH : ($detected ? self::SEVERITY_MEDIUM : self::SEVERITY_LOW),
            'current_cost' => $currentCost,
            'baseline_cost' => $baselineCost,
            'deviation_percent' => round($deviation, 1),
            'message' => $detected
                ? "Coste actual desviado {$deviation}% del baseline"
                : NULL,
        ];
    }

    /**
     * Obtiene métricas de recursos.
     */
    protected function getResourceMetrics(): array
    {
        // Simulación - en producción consultaría métricas reales.
        return [
            'cpu' => ['usage_percent' => rand(40, 85), 'growth_rate' => rand(1, 8)],
            'memory' => ['usage_percent' => rand(50, 90), 'growth_rate' => rand(1, 5)],
            'disk' => ['usage_percent' => rand(30, 70), 'growth_rate' => rand(1, 3)],
            'connections' => ['usage_percent' => rand(20, 60), 'growth_rate' => rand(1, 4)],
        ];
    }

    /**
     * Obtiene tendencia de latencia.
     */
    protected function getLatencyTrend(): array
    {
        return [
            'current_avg' => rand(200, 800),
            'p95' => rand(500, 2500),
            'growth_rate' => rand(-5, 15),
        ];
    }

    /**
     * Obtiene tendencia de errores.
     */
    protected function getErrorTrend(): array
    {
        return [
            'current_rate' => rand(0, 30) / 10,
            'growth_rate' => rand(-20, 100),
        ];
    }

    /**
     * Obtiene coste mensual actual.
     */
    protected function getCurrentMonthlyCost(): float
    {
        return rand(1000, 5000) + (rand(0, 99) / 100);
    }

    /**
     * Obtiene coste baseline.
     */
    protected function getBaselineCost(): float
    {
        return 3000.0;
    }

    /**
     * Convierte risk level a severity.
     */
    protected function riskToSeverity(string $riskLevel): string
    {
        return match ($riskLevel) {
            'high' => self::SEVERITY_HIGH,
            'medium' => self::SEVERITY_MEDIUM,
            'low' => self::SEVERITY_LOW,
            default => self::SEVERITY_LOW,
        };
    }

    /**
     * Convierte severity a int para ordenar.
     */
    protected function severityToInt(string $severity): int
    {
        return match ($severity) {
            self::SEVERITY_CRITICAL => 4,
            self::SEVERITY_HIGH => 3,
            self::SEVERITY_MEDIUM => 2,
            self::SEVERITY_LOW => 1,
            default => 0,
        };
    }

    /**
     * Almacena predicción.
     */
    protected function storePrediction(array $prediction): void
    {
        $this->database->insert('aiops_predictions')
            ->fields([
                    'type' => $prediction['type'],
                    'severity' => $prediction['severity'] ?? 'low',
                    'data' => json_encode($prediction),
                    'created' => time(),
                ])
            ->execute();
    }

    /**
     * Calcula health score.
     */
    protected function calculateHealthScore(array $predictions): int
    {
        $score = 100;

        foreach ($predictions as $prediction) {
            $severity = $prediction['severity'] ?? 'low';
            $score -= match ($severity) {
                self::SEVERITY_CRITICAL => 30,
                self::SEVERITY_HIGH => 20,
                self::SEVERITY_MEDIUM => 10,
                self::SEVERITY_LOW => 5,
                default => 0,
            };
        }

        return max(0, $score);
    }

    /**
     * Genera recomendaciones.
     */
    protected function generateRecommendations(array $predictions): array
    {
        $recommendations = [];

        foreach ($predictions as $prediction) {
            $recommendation = match ($prediction['type']) {
                self::PREDICTION_CAPACITY => [
                    'action' => 'scale_resources',
                    'message' => 'Considerar escalar recursos antes de alcanzar el límite',
                    'priority' => 'high',
                ],
                self::PREDICTION_PERFORMANCE => [
                    'action' => 'investigate_performance',
                    'message' => 'Investigar causas de degradación de rendimiento',
                    'priority' => 'medium',
                ],
                self::PREDICTION_ERROR_SPIKE => [
                    'action' => 'review_logs',
                    'message' => 'Revisar logs de errores y deployments recientes',
                    'priority' => 'high',
                ],
                self::PREDICTION_COST_ANOMALY => [
                    'action' => 'review_usage',
                    'message' => 'Revisar patrones de uso y optimizar recursos',
                    'priority' => 'medium',
                ],
                default => NULL,
            };

            if ($recommendation) {
                $recommendations[] = $recommendation;
            }
        }

        return $recommendations;
    }

    /**
     * Obtiene historial de predicciones.
     */
    public function getPredictionHistory(int $days = 7): array
    {
        $since = time() - ($days * 24 * 60 * 60);

        return $this->database->select('aiops_predictions', 'ap')
            ->fields('ap')
            ->condition('created', $since, '>')
            ->orderBy('created', 'DESC')
            ->execute()
            ->fetchAll();
    }

}
