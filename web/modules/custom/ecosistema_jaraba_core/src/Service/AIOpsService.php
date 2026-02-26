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
     * Obtiene métricas de recursos reales del sistema.
     */
    protected function getResourceMetrics(): array
    {
        return [
            'cpu' => $this->getCpuMetrics(),
            'memory' => $this->getMemoryMetrics(),
            'disk' => $this->getDiskMetrics(),
            'connections' => $this->getConnectionMetrics(),
        ];
    }

    /**
     * Obtiene métricas de CPU desde /proc/stat.
     */
    protected function getCpuMetrics(): array
    {
        $usagePercent = 0.0;
        $growthRate = 0.0;

        try {
            if (is_readable('/proc/stat')) {
                $stat = @file_get_contents('/proc/stat');
                if ($stat !== FALSE) {
                    // Leer la primera línea "cpu" que agrega todos los cores.
                    $lines = explode("\n", $stat);
                    foreach ($lines as $line) {
                        if (str_starts_with($line, 'cpu ')) {
                            $parts = preg_split('/\s+/', trim($line));
                            // cpu user nice system idle iowait irq softirq steal.
                            if (count($parts) >= 5) {
                                $user = (float) $parts[1];
                                $nice = (float) $parts[2];
                                $system = (float) $parts[3];
                                $idle = (float) $parts[4];
                                $total = $user + $nice + $system + $idle;
                                if ($total > 0) {
                                    $usagePercent = round((($total - $idle) / $total) * 100, 1);
                                }
                            }
                            break;
                        }
                    }
                }
            }

            // Fallback: usar sys_getloadavg si /proc/stat no es legible.
            if ($usagePercent === 0.0 && function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                if ($load !== FALSE && isset($load[0])) {
                    // Normalizar load average por número de CPUs (estimado).
                    $cpuCount = 1;
                    if (is_readable('/proc/cpuinfo')) {
                        $cpuinfo = @file_get_contents('/proc/cpuinfo');
                        if ($cpuinfo !== FALSE) {
                            $cpuCount = max(1, substr_count($cpuinfo, 'processor'));
                        }
                    }
                    $usagePercent = min(100, round(($load[0] / $cpuCount) * 100, 1));
                }
            }
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('ecosistema_jaraba_core')
                ->warning('Error reading CPU metrics: @error', ['@error' => $e->getMessage()]);
        }

        return ['usage_percent' => $usagePercent, 'growth_rate' => $growthRate];
    }

    /**
     * Obtiene métricas de memoria desde /proc/meminfo.
     */
    protected function getMemoryMetrics(): array
    {
        $usagePercent = 0.0;
        $growthRate = 0.0;

        try {
            if (is_readable('/proc/meminfo')) {
                $meminfo = @file_get_contents('/proc/meminfo');
                if ($meminfo !== FALSE) {
                    $values = [];
                    foreach (explode("\n", $meminfo) as $line) {
                        if (preg_match('/^(\w+):\s+(\d+)/', $line, $matches)) {
                            $values[$matches[1]] = (int) $matches[2];
                        }
                    }

                    $total = $values['MemTotal'] ?? 0;
                    $available = $values['MemAvailable'] ?? ($values['MemFree'] ?? 0);

                    if ($total > 0) {
                        $used = $total - $available;
                        $usagePercent = round(($used / $total) * 100, 1);
                    }
                }
            }

            // Fallback: PHP memory_get_usage (only covers this process).
            if ($usagePercent === 0.0) {
                $phpLimit = $this->getPhpMemoryLimitBytes();
                if ($phpLimit > 0) {
                    $usagePercent = round((memory_get_usage(TRUE) / $phpLimit) * 100, 1);
                }
            }
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('ecosistema_jaraba_core')
                ->warning('Error reading memory metrics: @error', ['@error' => $e->getMessage()]);
        }

        return ['usage_percent' => $usagePercent, 'growth_rate' => $growthRate];
    }

    /**
     * Convierte el memory_limit de PHP a bytes.
     */
    protected function getPhpMemoryLimitBytes(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return 0;
        }

        $value = (int) $limit;
        $unit = strtolower(substr(trim($limit), -1));

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Obtiene métricas de disco.
     */
    protected function getDiskMetrics(): array
    {
        $usagePercent = 0.0;
        $growthRate = 0.0;

        try {
            $docRoot = defined('DRUPAL_ROOT') ? DRUPAL_ROOT : getcwd();
            $total = @disk_total_space($docRoot);
            $free = @disk_free_space($docRoot);

            if ($total !== FALSE && $free !== FALSE && $total > 0) {
                $used = $total - $free;
                $usagePercent = round(($used / $total) * 100, 1);
            }
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('ecosistema_jaraba_core')
                ->warning('Error reading disk metrics: @error', ['@error' => $e->getMessage()]);
        }

        return ['usage_percent' => $usagePercent, 'growth_rate' => $growthRate];
    }

    /**
     * Obtiene métricas de conexiones de base de datos.
     */
    protected function getConnectionMetrics(): array
    {
        $usagePercent = 0.0;
        $growthRate = 0.0;

        try {
            // Obtener max_connections y threads_connected de MariaDB/MySQL.
            $maxResult = $this->database->query("SHOW VARIABLES LIKE 'max_connections'")->fetchAssoc();
            $currentResult = $this->database->query("SHOW STATUS LIKE 'Threads_connected'")->fetchAssoc();

            $maxConnections = (int) ($maxResult['Value'] ?? 0);
            $currentConnections = (int) ($currentResult['Value'] ?? 0);

            if ($maxConnections > 0) {
                $usagePercent = round(($currentConnections / $maxConnections) * 100, 1);
            }
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('ecosistema_jaraba_core')
                ->warning('Error reading DB connection metrics: @error', ['@error' => $e->getMessage()]);
        }

        return ['usage_percent' => $usagePercent, 'growth_rate' => $growthRate];
    }

    /**
     * Obtiene tendencia de latencia desde ai_telemetry.
     *
     * Compara la latencia promedio de las últimas 24h con las 24h anteriores
     * para calcular la tasa de crecimiento.
     */
    protected function getLatencyTrend(): array
    {
        $defaults = ['current_avg' => 0, 'p95' => 0, 'growth_rate' => 0];

        try {
            if (!$this->database->schema()->tableExists('ai_telemetry')) {
                return $defaults;
            }

            $now = time();
            $last24h = $now - 86400;
            $prev24h = $last24h - 86400;

            // Latencia promedio y P95 de las últimas 24h.
            $currentQuery = "
                SELECT
                    AVG(latency_ms) AS avg_latency,
                    MAX(latency_ms) AS p95_approx
                FROM {ai_telemetry}
                WHERE created >= :since AND success = 1";
            $currentResult = $this->database->query($currentQuery, [':since' => $last24h])->fetchAssoc();

            // Para P95 real, usamos un subquery con LIMIT/OFFSET.
            $p95 = 0.0;
            $countQuery = "SELECT COUNT(*) FROM {ai_telemetry} WHERE created >= :since AND success = 1";
            $totalRows = (int) $this->database->query($countQuery, [':since' => $last24h])->fetchField();
            if ($totalRows > 0) {
                $p95Offset = (int) floor($totalRows * 0.95);
                $p95Query = "
                    SELECT latency_ms FROM {ai_telemetry}
                    WHERE created >= :since AND success = 1
                    ORDER BY latency_ms ASC
                    LIMIT 1 OFFSET :offset";
                $p95Value = $this->database->query($p95Query, [
                    ':since' => $last24h,
                    ':offset' => max(0, $p95Offset - 1),
                ])->fetchField();
                $p95 = $p95Value !== FALSE ? round((float) $p95Value, 1) : 0.0;
            }

            $currentAvg = round((float) ($currentResult['avg_latency'] ?? 0), 1);

            // Latencia promedio de las 24h anteriores para calcular growth rate.
            $prevQuery = "
                SELECT AVG(latency_ms) AS avg_latency
                FROM {ai_telemetry}
                WHERE created >= :start AND created < :end AND success = 1";
            $prevResult = $this->database->query($prevQuery, [
                ':start' => $prev24h,
                ':end' => $last24h,
            ])->fetchAssoc();

            $prevAvg = (float) ($prevResult['avg_latency'] ?? 0);
            $growthRate = 0.0;
            if ($prevAvg > 0) {
                $growthRate = round((($currentAvg - $prevAvg) / $prevAvg) * 100, 1);
            }

            return [
                'current_avg' => $currentAvg,
                'p95' => $p95,
                'growth_rate' => $growthRate,
            ];
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('ecosistema_jaraba_core')
                ->warning('Error reading latency trend: @error', ['@error' => $e->getMessage()]);
            return $defaults;
        }
    }

    /**
     * Obtiene tendencia de errores desde la tabla watchdog.
     *
     * Compara los errores de las últimas 24h con las 24h anteriores.
     * Severidad <= 3 (Emergency, Alert, Critical, Error) en syslog RFC 5424.
     */
    protected function getErrorTrend(): array
    {
        $defaults = ['current_rate' => 0.0, 'growth_rate' => 0.0];

        try {
            if (!$this->database->schema()->tableExists('watchdog')) {
                return $defaults;
            }

            $now = time();
            $last24h = $now - 86400;
            $prev24h = $last24h - 86400;

            // Contar errores (severity <= 3) y total de entradas en las últimas 24h.
            $currentQuery = "
                SELECT
                    COUNT(*) AS total_entries,
                    SUM(CASE WHEN severity <= 3 THEN 1 ELSE 0 END) AS error_count
                FROM {watchdog}
                WHERE timestamp >= :since";
            $currentResult = $this->database->query($currentQuery, [':since' => $last24h])->fetchAssoc();

            $totalEntries = (int) ($currentResult['total_entries'] ?? 0);
            $errorCount = (int) ($currentResult['error_count'] ?? 0);

            $currentRate = 0.0;
            if ($totalEntries > 0) {
                $currentRate = round(($errorCount / $totalEntries) * 100, 1);
            }

            // Contar errores en las 24h anteriores para calcular growth rate.
            $prevQuery = "
                SELECT
                    COUNT(*) AS total_entries,
                    SUM(CASE WHEN severity <= 3 THEN 1 ELSE 0 END) AS error_count
                FROM {watchdog}
                WHERE timestamp >= :start AND timestamp < :end";
            $prevResult = $this->database->query($prevQuery, [
                ':start' => $prev24h,
                ':end' => $last24h,
            ])->fetchAssoc();

            $prevTotal = (int) ($prevResult['total_entries'] ?? 0);
            $prevErrors = (int) ($prevResult['error_count'] ?? 0);

            $prevRate = 0.0;
            if ($prevTotal > 0) {
                $prevRate = ($prevErrors / $prevTotal) * 100;
            }

            $growthRate = 0.0;
            if ($prevRate > 0) {
                $growthRate = round((($currentRate - $prevRate) / $prevRate) * 100, 1);
            }

            return [
                'current_rate' => $currentRate,
                'growth_rate' => $growthRate,
            ];
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('ecosistema_jaraba_core')
                ->warning('Error reading error trend from watchdog: @error', ['@error' => $e->getMessage()]);
            return $defaults;
        }
    }

    /**
     * Obtiene coste mensual actual desde ai_telemetry.
     *
     * Suma el cost_estimated de todas las invocaciones del mes actual.
     */
    protected function getCurrentMonthlyCost(): float
    {
        try {
            if (!$this->database->schema()->tableExists('ai_telemetry')) {
                return 0.0;
            }

            // Inicio del mes actual.
            $monthStart = (int) strtotime(date('Y-m-01 00:00:00'));

            $query = "
                SELECT COALESCE(SUM(cost_estimated), 0) AS total_cost
                FROM {ai_telemetry}
                WHERE created >= :month_start";
            $result = $this->database->query($query, [':month_start' => $monthStart])->fetchField();

            return round((float) $result, 2);
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('ecosistema_jaraba_core')
                ->warning('Error reading current monthly cost: @error', ['@error' => $e->getMessage()]);
            return 0.0;
        }
    }

    /**
     * Obtiene coste baseline como promedio de los 3 meses anteriores.
     */
    protected function getBaselineCost(): float
    {
        try {
            if (!$this->database->schema()->tableExists('ai_telemetry')) {
                return 0.0;
            }

            // Rango: desde hace 3 meses hasta el inicio del mes actual.
            $monthStart = (int) strtotime(date('Y-m-01 00:00:00'));
            $threeMonthsAgo = (int) strtotime('-3 months', $monthStart);

            $query = "
                SELECT COALESCE(SUM(cost_estimated), 0) AS total_cost
                FROM {ai_telemetry}
                WHERE created >= :start AND created < :end";
            $totalCost = (float) $this->database->query($query, [
                ':start' => $threeMonthsAgo,
                ':end' => $monthStart,
            ])->fetchField();

            // Dividir entre 3 para obtener promedio mensual.
            // Si no hay datos de los 3 meses completos, calculamos los meses
            // con datos para promediar correctamente.
            if ($totalCost <= 0) {
                return 0.0;
            }

            // Contar cuántos meses distintos tienen datos.
            $monthsQuery = "
                SELECT COUNT(DISTINCT DATE_FORMAT(FROM_UNIXTIME(created), '%Y-%m')) AS month_count
                FROM {ai_telemetry}
                WHERE created >= :start AND created < :end";
            $monthCount = (int) $this->database->query($monthsQuery, [
                ':start' => $threeMonthsAgo,
                ':end' => $monthStart,
            ])->fetchField();

            $divisor = max(1, $monthCount);

            return round($totalCost / $divisor, 2);
        }
        catch (\Exception $e) {
            $this->loggerFactory->get('ecosistema_jaraba_core')
                ->warning('Error reading baseline cost: @error', ['@error' => $e->getMessage()]);
            return 0.0;
        }
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
