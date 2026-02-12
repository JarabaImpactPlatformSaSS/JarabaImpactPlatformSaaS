<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de calculo de resultados para experimentos A/B.
 *
 * PROPOSITO:
 * Calcula estadisticas agregadas por variante, evalua la significancia
 * estadistica y determina cuando un experimento puede detenerse
 * automaticamente al alcanzar resultados conclusivos.
 *
 * LOGICA:
 * - calculateResults(): obtiene todas las exposiciones del experimento,
 *   agrupa por variante, calcula metricas estadisticas (media, desviacion
 *   estandar, p-value, intervalo de confianza) y guarda entidades
 *   ExperimentResult.
 * - checkAutoStop(): verifica si el experimento ha alcanzado significancia
 *   estadistica suficiente para detenerse automaticamente.
 * - declareWinner(): marca una variante como ganadora y completa el
 *   experimento.
 *
 * RELACIONES:
 * - Consume ExperimentExposure y ExperimentResult entities.
 * - Consume ABExperiment para configuracion estadistica.
 * - Consumido por cron, dashboard y controladores API.
 */
class ResultCalculationService {

  /**
   * Gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Canal de log.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio de calculo de resultados.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para A/B testing.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Calcula los resultados estadisticos de un experimento.
   *
   * Obtiene todas las exposiciones del experimento, las agrupa por
   * variante y calcula para cada una: tamano de muestra, tasa de
   * conversion (media), desviacion estandar, intervalo de confianza,
   * p-value y lift respecto al control.
   *
   * @param int $experimentId
   *   ID del experimento A/B.
   *
   * @return array
   *   Array de resultados por variante. Cada elemento contiene:
   *   - 'variant_id' (string): Clave de la variante.
   *   - 'metric_name' (string): Nombre de la metrica.
   *   - 'sample_size' (int): Numero de exposiciones.
   *   - 'mean' (float): Tasa de conversion media.
   *   - 'std_dev' (float): Desviacion estandar.
   *   - 'p_value' (float|null): Valor p del test.
   *   - 'is_significant' (bool): Si el resultado es significativo.
   *   - 'lift' (float): Mejora porcentual respecto al control.
   *   Array vacio si no hay datos suficientes.
   */
  public function calculateResults(int $experimentId): array {
    try {
      $exposureStorage = $this->entityTypeManager->getStorage('experiment_exposure');
      $resultStorage = $this->entityTypeManager->getStorage('experiment_result');

      // Obtener todas las exposiciones del experimento.
      $exposureIds = $exposureStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_id', $experimentId)
        ->execute();

      if (empty($exposureIds)) {
        $this->logger->info('No hay exposiciones para el experimento @experiment.', [
          '@experiment' => $experimentId,
        ]);
        return [];
      }

      $exposures = $exposureStorage->loadMultiple($exposureIds);

      // Agrupar exposiciones por variante.
      $variantData = [];
      foreach ($exposures as $exposure) {
        $variantId = $exposure->get('variant_id')->value;
        if (!isset($variantData[$variantId])) {
          $variantData[$variantId] = [
            'total' => 0,
            'conversions' => 0,
            'values' => [],
          ];
        }
        $variantData[$variantId]['total']++;
        $converted = (bool) $exposure->get('converted')->value;
        if ($converted) {
          $variantData[$variantId]['conversions']++;
          $conversionValue = (float) ($exposure->get('conversion_value')->value ?? 0);
          $variantData[$variantId]['values'][] = $conversionValue;
        }
      }

      // Cargar el experimento para obtener la configuracion estadistica.
      $experiment = $this->entityTypeManager
        ->getStorage('ab_experiment')
        ->load($experimentId);

      $confidenceThreshold = 0.95;
      if ($experiment && $experiment->hasField('confidence_threshold')) {
        $confidenceThreshold = (float) ($experiment->get('confidence_threshold')->value ?? 0.95);
      }

      // Identificar la variante control (la primera o la que tenga menos key).
      $variantKeys = array_keys($variantData);
      sort($variantKeys);
      $controlKey = $variantKeys[0] ?? NULL;

      $controlRate = 0.0;
      if ($controlKey && $variantData[$controlKey]['total'] > 0) {
        $controlRate = $variantData[$controlKey]['conversions'] / $variantData[$controlKey]['total'];
      }

      $results = [];
      $now = time();

      foreach ($variantData as $variantId => $data) {
        $sampleSize = $data['total'];
        $conversionRate = $sampleSize > 0 ? $data['conversions'] / $sampleSize : 0.0;

        // Calcular desviacion estandar para proporcion binomial.
        $stdDev = $sampleSize > 0
          ? sqrt($conversionRate * (1 - $conversionRate) / $sampleSize)
          : 0.0;

        // Calcular p-value simplificado usando z-test de dos proporciones.
        $pValue = NULL;
        $isSignificant = FALSE;

        if ($variantId !== $controlKey && $sampleSize > 0 && $controlRate > 0) {
          $controlSample = $variantData[$controlKey]['total'];
          $pooledRate = ($data['conversions'] + $variantData[$controlKey]['conversions'])
            / ($sampleSize + $controlSample);

          if ($pooledRate > 0 && $pooledRate < 1 && $controlSample > 0) {
            $pooledSe = sqrt($pooledRate * (1 - $pooledRate) * (1 / $sampleSize + 1 / $controlSample));
            if ($pooledSe > 0) {
              $zScore = ($conversionRate - $controlRate) / $pooledSe;
              // Aproximacion del p-value para un z-test bilateral.
              $pValue = 2 * (1 - $this->normalCdf(abs($zScore)));
              $isSignificant = $pValue < (1 - $confidenceThreshold);
            }
          }
        }

        // Calcular intervalo de confianza al 95%.
        $zCritical = 1.96;
        $marginOfError = $zCritical * $stdDev;
        $confidenceInterval = json_encode([
          'lower' => round($conversionRate - $marginOfError, 6),
          'upper' => round($conversionRate + $marginOfError, 6),
        ]);

        // Calcular lift respecto al control.
        $lift = 0.0;
        if ($controlRate > 0 && $variantId !== $controlKey) {
          $lift = (($conversionRate - $controlRate) / $controlRate) * 100;
        }

        // Crear o actualizar la entidad ExperimentResult.
        $existingIds = $resultStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('experiment_id', $experimentId)
          ->condition('variant_id', $variantId)
          ->condition('metric_name', 'conversion_rate')
          ->range(0, 1)
          ->execute();

        if (!empty($existingIds)) {
          $resultEntity = $resultStorage->load(reset($existingIds));
        }
        else {
          $resultEntity = $resultStorage->create([
            'experiment_id' => $experimentId,
            'variant_id' => $variantId,
            'metric_name' => 'conversion_rate',
          ]);
        }

        $resultEntity->set('sample_size', $sampleSize);
        $resultEntity->set('mean', round($conversionRate, 6));
        $resultEntity->set('std_dev', round($stdDev, 6));
        $resultEntity->set('confidence_interval', $confidenceInterval);
        $resultEntity->set('p_value', $pValue !== NULL ? round($pValue, 6) : NULL);
        $resultEntity->set('is_significant', $isSignificant);
        $resultEntity->set('lift', round($lift, 4));
        $resultEntity->set('calculated_at', $now);
        $resultEntity->save();

        $results[] = [
          'variant_id' => $variantId,
          'metric_name' => 'conversion_rate',
          'sample_size' => $sampleSize,
          'mean' => round($conversionRate, 6),
          'std_dev' => round($stdDev, 6),
          'p_value' => $pValue !== NULL ? round($pValue, 6) : NULL,
          'is_significant' => $isSignificant,
          'lift' => round($lift, 4),
        ];
      }

      $this->logger->info('Resultados calculados para el experimento @experiment: @count variantes.', [
        '@experiment' => $experimentId,
        '@count' => count($results),
      ]);

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando resultados del experimento @experiment: @error', [
        '@experiment' => $experimentId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Comprueba si un experimento puede detenerse automaticamente.
   *
   * Verifica que se hayan alcanzado las condiciones de parada:
   * tamano minimo de muestra, dias minimos de ejecucion y
   * significancia estadistica en al menos una variante.
   *
   * @param int $experimentId
   *   ID del experimento A/B.
   *
   * @return bool
   *   TRUE si el experimento ha alcanzado significancia y cumple
   *   las condiciones de parada, FALSE en caso contrario.
   */
  public function checkAutoStop(int $experimentId): bool {
    try {
      $resultStorage = $this->entityTypeManager->getStorage('experiment_result');

      // Buscar resultados significativos para el experimento.
      $significantIds = $resultStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_id', $experimentId)
        ->condition('is_significant', TRUE)
        ->execute();

      if (empty($significantIds)) {
        return FALSE;
      }

      // Cargar el experimento para verificar condiciones adicionales.
      $experiment = $this->entityTypeManager
        ->getStorage('ab_experiment')
        ->load($experimentId);

      if (!$experiment) {
        return FALSE;
      }

      // Verificar que el auto_complete este habilitado.
      if ($experiment->hasField('auto_complete')) {
        $autoComplete = (bool) ($experiment->get('auto_complete')->value ?? FALSE);
        if (!$autoComplete) {
          return FALSE;
        }
      }

      // Verificar tamano minimo de muestra.
      if ($experiment->hasField('minimum_sample_size')) {
        $minimumSample = (int) ($experiment->get('minimum_sample_size')->value ?? 100);
        $totalVisitors = (int) ($experiment->get('total_visitors')->value ?? 0);
        if ($totalVisitors < $minimumSample) {
          return FALSE;
        }
      }

      // Verificar dias minimos de ejecucion.
      if ($experiment->hasField('minimum_runtime_days') && $experiment->hasField('start_date')) {
        $minimumDays = (int) ($experiment->get('minimum_runtime_days')->value ?? 7);
        $startDate = $experiment->get('start_date')->value;
        if (!empty($startDate)) {
          $start = new \DateTimeImmutable($startDate);
          $now = new \DateTimeImmutable();
          $daysDiff = (int) $now->diff($start)->days;
          if ($daysDiff < $minimumDays) {
            return FALSE;
          }
        }
      }

      $this->logger->info('El experimento @experiment cumple las condiciones de auto-parada.', [
        '@experiment' => $experimentId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error verificando auto-parada del experimento @experiment: @error', [
        '@experiment' => $experimentId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Declara una variante como ganadora de un experimento.
   *
   * Actualiza el campo winner_variant del experimento y cambia su
   * estado a 'completed'.
   *
   * @param int $experimentId
   *   ID del experimento A/B.
   * @param string $variantId
   *   Clave de la variante ganadora.
   *
   * @return bool
   *   TRUE si se declaro el ganador correctamente, FALSE en caso contrario.
   */
  public function declareWinner(int $experimentId, string $variantId): bool {
    try {
      $experiment = $this->entityTypeManager
        ->getStorage('ab_experiment')
        ->load($experimentId);

      if (!$experiment) {
        $this->logger->warning('No se encontro el experimento @experiment para declarar ganador.', [
          '@experiment' => $experimentId,
        ]);
        return FALSE;
      }

      // Buscar la entidad ABVariant correspondiente a la clave de variante.
      $variantStorage = $this->entityTypeManager->getStorage('ab_variant');
      $variantIds = $variantStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_id', $experimentId)
        ->condition('variant_key', $variantId)
        ->range(0, 1)
        ->execute();

      if (!empty($variantIds)) {
        $winnerEntityId = reset($variantIds);
        $experiment->set('winner_variant', $winnerEntityId);
      }

      $experiment->set('status', 'completed');
      $experiment->save();

      $this->logger->info('Variante "@variant" declarada ganadora del experimento @experiment.', [
        '@variant' => $variantId,
        '@experiment' => $experimentId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error declarando ganador del experimento @experiment: @error', [
        '@experiment' => $experimentId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Aproximacion de la funcion de distribucion acumulada normal estandar.
   *
   * Usa la aproximacion de Abramowitz y Stegun para calcular
   * la probabilidad acumulada de una distribucion normal estandar.
   *
   * @param float $x
   *   Valor z para el que calcular la probabilidad acumulada.
   *
   * @return float
   *   Probabilidad acumulada P(Z <= x).
   */
  protected function normalCdf(float $x): float {
    // Constantes para la aproximacion de Abramowitz y Stegun.
    $a1 = 0.254829592;
    $a2 = -0.284496736;
    $a3 = 1.421413741;
    $a4 = -1.453152027;
    $a5 = 1.061405429;
    $p = 0.3275911;

    $sign = $x < 0 ? -1 : 1;
    $x = abs($x) / sqrt(2);

    $t = 1.0 / (1.0 + $p * $x);
    $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

    return 0.5 * (1.0 + $sign * $y);
  }

}
