<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de detección de anomalías en el uso de tenants.
 *
 * PROPÓSITO:
 * Detecta desviaciones anómalas en las métricas de uso de cada tenant
 * comparando el valor actual contra una media móvil de 30 días.
 * Utiliza desviación estándar para determinar umbrales dinámicos.
 *
 * ALGORITMO:
 * 1. Agrega valores diarios de los últimos 30 días por tenant+métrica.
 * 2. Calcula media (μ) y desviación estándar (σ).
 * 3. Si el valor de hoy supera μ ± (umbral × σ), se marca como anomalía.
 *
 * GAP: G111-3 - Detección de anomalías de uso
 *
 * PHASE 12: Observability & Alerting
 */
class UsageAnomalyDetectorService {

  /**
   * Número de días del período de análisis para la media móvil.
   */
  protected const ANALYSIS_WINDOW_DAYS = 30;

  /**
   * Umbral por defecto en desviaciones estándar.
   */
  protected const DEFAULT_THRESHOLD = 2.0;

  /**
   * Número mínimo de días con datos para considerar el análisis válido.
   */
  protected const MIN_DATA_POINTS = 5;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Conexión a la base de datos.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoría de configuración para leer umbrales.
   * @param \Drupal\ecosistema_jaraba_core\Service\AlertingService $alertingService
   *   Servicio de alertas (Slack/Teams).
   * @param \Drupal\ecosistema_jaraba_core\Service\AuditLogService $auditLogService
   *   Servicio de registro de auditoría.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log del módulo.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para consultar audit_log.
   */
  public function __construct(
    protected Connection $database,
    protected ConfigFactoryInterface $configFactory,
    protected AlertingService $alertingService,
    protected AuditLogService $auditLogService,
    protected LoggerInterface $logger,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
  }

  /**
   * Detecta anomalías en todas las combinaciones tenant+métrica activas.
   *
   * Recorre todas las combinaciones únicas de tenant_id y metric
   * registradas en la tabla tenant_metering y analiza cada una
   * en busca de desviaciones significativas.
   *
   * @return array
   *   Lista de anomalías detectadas. Cada elemento contiene:
   *   - tenant_id (string): ID del tenant.
   *   - metric (string): Nombre de la métrica.
   *   - current_value (float): Valor actual del día.
   *   - mean (float): Media de los últimos 30 días.
   *   - std_dev (float): Desviación estándar.
   *   - deviation (float): Número de desviaciones estándar.
   *   - threshold (float): Umbral configurado.
   *   - direction (string): 'above' o 'below'.
   *   - detected_at (int): Timestamp de detección.
   */
  public function detectAnomalies(): array {
    $anomalies = [];

    try {
      $combos = $this->getDistinctTenantMetricCombos();

      foreach ($combos as $combo) {
        $result = $this->analyzeMetric($combo['tenant_id'], $combo['metric']);

        if ($result !== NULL) {
          $anomalies[] = $result;
        }
      }

      if (!empty($anomalies)) {
        $this->logger->warning('Detectadas @count anomalías de uso en @combos combinaciones tenant+métrica.', [
          '@count' => count($anomalies),
          '@combos' => count($combos),
        ]);
      }
      else {
        $this->logger->info('Análisis de anomalías completado: 0 anomalías en @combos combinaciones.', [
          '@combos' => count($combos),
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error durante la detección de anomalías: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $anomalies;
  }

  /**
   * Analiza una métrica específica de un tenant en busca de anomalías.
   *
   * Obtiene los valores diarios agregados de los últimos 30 días,
   * calcula media y desviación estándar, y compara con el valor actual.
   *
   * @param string $tenantId
   *   ID del tenant a analizar.
   * @param string $metric
   *   Nombre de la métrica a analizar.
   *
   * @return array|null
   *   Datos de la anomalía detectada, o NULL si no hay anomalía.
   */
  public function analyzeMetric(string $tenantId, string $metric): ?array {
    try {
      $dailyValues = $this->getDailyAggregatedValues($tenantId, $metric);

      // Necesitamos un mínimo de datos para un análisis estadístico fiable.
      if (count($dailyValues) < self::MIN_DATA_POINTS) {
        return NULL;
      }

      $todayValue = $this->getTodayValue($tenantId, $metric);

      // Si no hay actividad hoy, no hay anomalía que reportar.
      if ($todayValue === NULL) {
        return NULL;
      }

      $mean = $this->calculateMean($dailyValues);
      $stdDev = $this->calculateStdDev($dailyValues, $mean);

      // Si la desviación estándar es 0 (valores constantes), no hay anomalía
      // posible basada en desviación. Solo reportamos si el valor es distinto.
      if ($stdDev == 0.0) {
        if ($todayValue != $mean) {
          // Valor diferente de una serie constante: anomalía evidente.
          return [
            'tenant_id' => $tenantId,
            'metric' => $metric,
            'current_value' => $todayValue,
            'mean' => $mean,
            'std_dev' => 0.0,
            'deviation' => PHP_FLOAT_MAX,
            'threshold' => $this->getAnomalyThreshold(),
            'direction' => $todayValue > $mean ? 'above' : 'below',
            'detected_at' => time(),
          ];
        }
        return NULL;
      }

      $threshold = $this->getAnomalyThreshold();
      $deviation = abs($todayValue - $mean) / $stdDev;

      if ($deviation > $threshold) {
        return [
          'tenant_id' => $tenantId,
          'metric' => $metric,
          'current_value' => $todayValue,
          'mean' => round($mean, 4),
          'std_dev' => round($stdDev, 4),
          'deviation' => round($deviation, 4),
          'threshold' => $threshold,
          'direction' => $todayValue > $mean ? 'above' : 'below',
          'detected_at' => time(),
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error analizando métrica @metric para tenant @tenant: @error', [
        '@metric' => $metric,
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Notifica las anomalías detectadas a los canales de alerta y auditoría.
   *
   * Para cada anomalía, envía una alerta vía AlertingService y
   * registra el evento en el AuditLogService.
   *
   * @param array $anomalies
   *   Lista de anomalías detectadas por detectAnomalies().
   */
  public function notifyAnomalies(array $anomalies): void {
    foreach ($anomalies as $anomaly) {
      $direction = $anomaly['direction'] === 'above' ? 'por encima' : 'por debajo';
      $title = "Anomalía de uso detectada: {$anomaly['metric']}";
      $message = sprintf(
        'Tenant %s: la métrica "%s" tiene un valor de %.2f, que está %.1fσ %s de la media (%.2f). Umbral configurado: %.1fσ.',
        $anomaly['tenant_id'],
        $anomaly['metric'],
        $anomaly['current_value'],
        $anomaly['deviation'] === PHP_FLOAT_MAX ? 0 : $anomaly['deviation'],
        $direction,
        $anomaly['mean'],
        $anomaly['threshold'],
      );

      // Enviar alerta a Slack/Teams.
      try {
        $this->alertingService->send(
          $title,
          $message,
          AlertingService::ALERT_WARNING,
          [
            ['title' => 'Tenant', 'value' => $anomaly['tenant_id']],
            ['title' => 'Métrica', 'value' => $anomaly['metric']],
            ['title' => 'Valor actual', 'value' => number_format($anomaly['current_value'], 2)],
            ['title' => 'Media (30d)', 'value' => number_format($anomaly['mean'], 2)],
            ['title' => 'Desviación', 'value' => ($anomaly['deviation'] === PHP_FLOAT_MAX ? 'N/A' : number_format($anomaly['deviation'], 2) . 'σ')],
            ['title' => 'Dirección', 'value' => $direction],
          ],
        );
      }
      catch (\Exception $e) {
        $this->logger->error('Error enviando alerta de anomalía para tenant @tenant: @error', [
          '@tenant' => $anomaly['tenant_id'],
          '@error' => $e->getMessage(),
        ]);
      }

      // Registrar en audit log.
      try {
        $this->auditLogService->log('usage_anomaly_detected', [
          'severity' => 'warning',
          'tenant_id' => (int) $anomaly['tenant_id'],
          'target_type' => 'tenant_metering',
          'details' => [
            'metric' => $anomaly['metric'],
            'current_value' => $anomaly['current_value'],
            'mean' => $anomaly['mean'],
            'std_dev' => $anomaly['std_dev'],
            'deviation' => $anomaly['deviation'] === PHP_FLOAT_MAX ? 'infinite' : $anomaly['deviation'],
            'threshold' => $anomaly['threshold'],
            'direction' => $anomaly['direction'],
            'detected_at' => $anomaly['detected_at'],
          ],
        ]);
      }
      catch (\Exception $e) {
        $this->logger->error('Error registrando anomalía en audit log para tenant @tenant: @error', [
          '@tenant' => $anomaly['tenant_id'],
          '@error' => $e->getMessage(),
        ]);
      }
    }

    if (!empty($anomalies)) {
      $this->logger->notice('Se notificaron @count anomalías de uso.', [
        '@count' => count($anomalies),
      ]);
    }
  }

  /**
   * Obtiene el umbral de anomalía configurado en desviaciones estándar.
   *
   * Lee el valor del fichero de configuración ecosistema_jaraba_core.finops
   * bajo la clave 'anomaly_threshold'. Si no está definido, devuelve
   * el valor por defecto de 2.0σ.
   *
   * @return float
   *   Umbral en número de desviaciones estándar.
   */
  public function getAnomalyThreshold(): float {
    $config = $this->configFactory->get('ecosistema_jaraba_core.finops');
    $threshold = $config->get('anomaly_threshold');

    if ($threshold !== NULL && is_numeric($threshold)) {
      return (float) $threshold;
    }

    return self::DEFAULT_THRESHOLD;
  }

  /**
   * Obtiene las anomalías recientes del audit log.
   *
   * Consulta las entidades audit_log con event_type 'usage_anomaly_detected'
   * para mostrarlas en dashboards o informes.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   * @param int $limit
   *   Número máximo de resultados a devolver.
   *
   * @return array
   *   Lista de anomalías recientes con sus detalles.
   */
  public function getRecentAnomalies(?int $tenantId = NULL, int $limit = 50): array {
    $anomalies = [];

    try {
      $storage = $this->entityTypeManager->getStorage('audit_log');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('event_type', 'usage_anomaly_detected')
        ->sort('created', 'DESC')
        ->range(0, $limit);

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();

      if (!empty($ids)) {
        $entities = $storage->loadMultiple($ids);

        foreach ($entities as $entity) {
          $details = $entity->getDetails();

          $anomalies[] = [
            'id' => (int) $entity->id(),
            'tenant_id' => $entity->get('tenant_id')->target_id,
            'metric' => $details['metric'] ?? '',
            'current_value' => $details['current_value'] ?? 0,
            'mean' => $details['mean'] ?? 0,
            'std_dev' => $details['std_dev'] ?? 0,
            'deviation' => $details['deviation'] ?? 0,
            'threshold' => $details['threshold'] ?? self::DEFAULT_THRESHOLD,
            'direction' => $details['direction'] ?? '',
            'severity' => $entity->getSeverity(),
            'created' => (int) ($entity->get('created')->value ?? 0),
          ];
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo anomalías recientes: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $anomalies;
  }

  /**
   * Obtiene todas las combinaciones únicas de tenant_id y metric.
   *
   * Consulta la tabla tenant_metering para encontrar todas las
   * combinaciones activas que deben analizarse.
   *
   * @return array
   *   Lista de arrays con claves 'tenant_id' y 'metric'.
   */
  protected function getDistinctTenantMetricCombos(): array {
    $query = $this->database->select('tenant_metering', 'tm')
      ->fields('tm', ['tenant_id', 'metric'])
      ->groupBy('tm.tenant_id')
      ->groupBy('tm.metric');

    $results = $query->execute()->fetchAll();

    $combos = [];
    foreach ($results as $row) {
      $combos[] = [
        'tenant_id' => $row->tenant_id,
        'metric' => $row->metric,
      ];
    }

    return $combos;
  }

  /**
   * Obtiene los valores diarios agregados de los últimos 30 días.
   *
   * Agrega los valores de tenant_metering por día (SUM) para el
   * tenant y métrica indicados, excluyendo el día actual.
   *
   * @param string $tenantId
   *   ID del tenant.
   * @param string $metric
   *   Nombre de la métrica.
   *
   * @return array
   *   Lista de valores diarios agregados (float).
   */
  protected function getDailyAggregatedValues(string $tenantId, string $metric): array {
    $startTimestamp = strtotime('-' . self::ANALYSIS_WINDOW_DAYS . ' days midnight');
    $todayStart = strtotime('today midnight');

    $query = $this->database->select('tenant_metering', 'tm');
    $query->addExpression("DATE(FROM_UNIXTIME(tm.created))", 'day');
    $query->addExpression('SUM(tm.value)', 'daily_total');
    $query->condition('tm.tenant_id', $tenantId)
      ->condition('tm.metric', $metric)
      ->condition('tm.created', $startTimestamp, '>=')
      ->condition('tm.created', $todayStart, '<')
      ->groupBy('day')
      ->orderBy('day', 'ASC');

    $results = $query->execute()->fetchAll();

    $values = [];
    foreach ($results as $row) {
      $values[] = (float) $row->daily_total;
    }

    return $values;
  }

  /**
   * Obtiene el valor agregado de hoy para un tenant y métrica.
   *
   * @param string $tenantId
   *   ID del tenant.
   * @param string $metric
   *   Nombre de la métrica.
   *
   * @return float|null
   *   Valor agregado de hoy, o NULL si no hay registros.
   */
  protected function getTodayValue(string $tenantId, string $metric): ?float {
    $todayStart = strtotime('today midnight');

    $query = $this->database->select('tenant_metering', 'tm');
    $query->addExpression('SUM(tm.value)', 'daily_total');
    $query->condition('tm.tenant_id', $tenantId)
      ->condition('tm.metric', $metric)
      ->condition('tm.created', $todayStart, '>=');

    $result = $query->execute()->fetchField();

    if ($result === FALSE || $result === NULL) {
      return NULL;
    }

    return (float) $result;
  }

  /**
   * Calcula la media aritmética de un conjunto de valores.
   *
   * @param array $values
   *   Lista de valores numéricos.
   *
   * @return float
   *   Media aritmética.
   */
  protected function calculateMean(array $values): float {
    if (empty($values)) {
      return 0.0;
    }

    return array_sum($values) / count($values);
  }

  /**
   * Calcula la desviación estándar poblacional de un conjunto de valores.
   *
   * Utiliza la fórmula de desviación estándar poblacional (σ):
   * σ = sqrt(Σ(xi - μ)² / N)
   *
   * @param array $values
   *   Lista de valores numéricos.
   * @param float|null $mean
   *   Media precalculada, o NULL para calcularla.
   *
   * @return float
   *   Desviación estándar poblacional.
   */
  protected function calculateStdDev(array $values, ?float $mean = NULL): float {
    $count = count($values);
    if ($count < 2) {
      return 0.0;
    }

    if ($mean === NULL) {
      $mean = $this->calculateMean($values);
    }

    $sumSquaredDiffs = 0.0;
    foreach ($values as $value) {
      $diff = $value - $mean;
      $sumSquaredDiffs += $diff * $diff;
    }

    return sqrt($sumSquaredDiffs / $count);
  }

}
