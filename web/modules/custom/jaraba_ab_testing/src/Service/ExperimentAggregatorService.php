<?php

namespace Drupal\jaraba_ab_testing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Servicio agregador de datos de experimentos A/B multi-fuente.
 *
 * ESTRUCTURA:
 * Servicio de orquestación que recopila métricas de los experimentos A/B
 * nativos (entidades ab_experiment/ab_variant), las combina con el motor
 * estadístico y genera datos para el dashboard centralizado. Opera con
 * aislamiento multi-tenant a través de TenantContextService.
 *
 * LÓGICA:
 * Todas las consultas de experimentos están filtradas por tenant_id para
 * garantizar aislamiento. Las métricas se calculan en tiempo real desde
 * las entidades, delegando el análisis estadístico a StatisticalEngineService.
 * El servicio no cachea resultados (fase 1); la capa de caché se añade
 * en la render array del controlador (max-age: 60s).
 *
 * Flujos principales:
 * - getTenantExperiments(): Lista con resumen por experimento.
 * - getExperimentDetail(): Detalle con análisis variante a variante.
 * - getDashboardMetrics(): KPIs agregados para las cards del dashboard.
 * - declareWinner(): Cierra experimento con ganador seleccionado.
 *
 * RELACIONES:
 * - ExperimentAggregatorService -> EntityTypeManagerInterface (dependencia)
 * - ExperimentAggregatorService -> TenantContextService (dependencia)
 * - ExperimentAggregatorService -> StatisticalEngineService (dependencia)
 * - ExperimentAggregatorService -> LoggerInterface (dependencia)
 * - ExperimentAggregatorService <- ABTestingDashboardController (consumido por)
 * - ExperimentAggregatorService <- ABTestingApiController (consumido por)
 *
 * @package Drupal\jaraba_ab_testing\Service
 */
class ExperimentAggregatorService {

  use StringTranslationTrait;

  /**
   * Gestor de tipos de entidad de Drupal.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Servicio de contexto de tenant para aislamiento multi-tenant.
   *
   * @var object
   */
  protected $tenantContext;

  /**
   * Motor estadístico para análisis de experimentos.
   *
   * @var \Drupal\jaraba_ab_testing\Service\StatisticalEngineService
   */
  protected StatisticalEngineService $statisticalEngine;

  /**
   * Canal de log dedicado para el módulo de A/B testing.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor del servicio agregador de experimentos.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad para acceder a storage de experimentos y variantes.
   * @param object $tenant_context
   *   Servicio de contexto de tenant para filtrado multi-tenant.
   * @param \Drupal\jaraba_ab_testing\Service\StatisticalEngineService $statistical_engine
   *   Motor estadístico para cálculos de Z-score, confianza, chi-cuadrado.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log dedicado para trazar operaciones y errores.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    $tenant_context,
    StatisticalEngineService $statistical_engine,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
    $this->statisticalEngine = $statistical_engine;
    $this->logger = $logger;
  }

  /**
   * Obtiene todos los experimentos de un tenant con su resumen analítico.
   *
   * LÓGICA:
   * 1. Consulta entidades ab_experiment filtradas por tenant_id.
   * 2. Si se proporciona status_filter, filtra por ese estado.
   * 3. Para cada experimento, carga sus variantes y calcula métricas.
   * 4. Retorna lista con resumen por experimento incluyendo:
   *    total de visitantes, conversiones, tasa, si tiene ganador, días en ejecución.
   *
   * @param int $tenant_id
   *   ID del tenant para filtrado.
   * @param string $status_filter
   *   Filtro de estado opcional: 'draft', 'running', 'paused', 'completed', ''.
   *   Cadena vacía retorna todos los estados.
   *
   * @return array
   *   Lista de arrays con resumen por experimento:
   *   - 'id' (int): ID del experimento.
   *   - 'name' (string): Nombre del experimento.
   *   - 'machine_name' (string): Machine name para URLs y APIs.
   *   - 'type' (string): Tipo de experimento.
   *   - 'status' (string): Estado actual.
   *   - 'total_visitors' (int): Visitantes totales sumados de todas las variantes.
   *   - 'total_conversions' (int): Conversiones totales.
   *   - 'overall_rate' (float): Tasa de conversión global (%).
   *   - 'has_winner' (bool): Si se ha detectado un ganador significativo.
   *   - 'days_running' (int): Días desde que se inició el experimento.
   *   - 'variant_count' (int): Número de variantes.
   *   - 'created' (string): Fecha de creación.
   */
  public function getTenantExperiments(int $tenant_id, string $status_filter = ''): array {
    try {
      $experiment_storage = $this->entityTypeManager->getStorage('ab_experiment');

      $query = $experiment_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->sort('created', 'DESC');

      if (!empty($status_filter)) {
        $query->condition('status', $status_filter);
      }

      $experiment_ids = $query->execute();

      if (empty($experiment_ids)) {
        return [];
      }

      $experiments = $experiment_storage->loadMultiple($experiment_ids);
      $variant_storage = $this->entityTypeManager->getStorage('ab_variant');
      $results = [];

      foreach ($experiments as $experiment) {
        $exp_id = (int) $experiment->id();

        // Cargar variantes del experimento.
        $variant_ids = $variant_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('experiment_id', $exp_id)
          ->execute();

        $variants = !empty($variant_ids) ? $variant_storage->loadMultiple($variant_ids) : [];

        // Calcular totales.
        $total_visitors = 0;
        $total_conversions = 0;
        $variants_data = [];

        foreach ($variants as $v) {
          $v_visitors = (int) ($v->get('visitors')->value ?? 0);
          $v_conversions = (int) ($v->get('conversions')->value ?? 0);
          $total_visitors += $v_visitors;
          $total_conversions += $v_conversions;

          $variants_data[] = [
            'id' => (int) $v->id(),
            'name' => $v->get('label')->value ?? '',
            'is_control' => (bool) ($v->get('is_control')->value ?? FALSE),
            'visitors' => $v_visitors,
            'conversions' => $v_conversions,
          ];
        }

        // Tasa de conversión global.
        $overall_rate = $total_visitors > 0
          ? round(($total_conversions / $total_visitors) * 100.0, 2)
          : 0.0;

        // Detectar ganador con análisis rápido.
        $has_winner = FALSE;
        if (count($variants_data) >= 2 && $total_visitors > 0) {
          $analysis = $this->statisticalEngine->analyzeExperiment($variants_data);
          $has_winner = $analysis['has_winner'] ?? FALSE;
        }

        // Calcular días en ejecución.
        $created_timestamp = $experiment->get('created')->value ?? 0;
        $started_timestamp = $experiment->get('start_date')->value
          ? strtotime($experiment->get('start_date')->value)
          : $created_timestamp;
        $days_running = 0;
        if ($started_timestamp > 0) {
          $days_running = (int) floor((time() - $started_timestamp) / 86400);
        }

        $results[] = [
          'id' => $exp_id,
          'name' => $experiment->get('label')->value ?? '',
          'machine_name' => $experiment->get('machine_name')->value ?? '',
          'type' => $experiment->get('experiment_type')->value ?? '',
          'status' => $experiment->get('status')->value ?? 'draft',
          'total_visitors' => $total_visitors,
          'total_conversions' => $total_conversions,
          'overall_rate' => $overall_rate,
          'has_winner' => $has_winner,
          'days_running' => max(0, $days_running),
          'variant_count' => count($variants),
          'created' => $created_timestamp ? date('Y-m-d', $created_timestamp) : '',
        ];
      }

      return $results;

    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo experimentos del tenant @tid: @error', [
        '@tid' => $tenant_id,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene el detalle completo de un experimento con análisis por variante.
   *
   * LÓGICA:
   * 1. Carga la entidad ab_experiment y todas sus variantes.
   * 2. Construye el array de datos por variante para el motor estadístico.
   * 3. Ejecuta StatisticalEngineService::analyzeExperiment() para obtener
   *    Z-score, confianza, lift y significancia por variante.
   * 4. Ejecuta chiSquaredTest() para test de independencia global.
   * 5. Calcula tamaño muestral necesario y estima días restantes.
   * 6. Retorna estructura completa para la plantilla de detalle.
   *
   * @param int $experiment_id
   *   ID del experimento a detallar.
   *
   * @return array
   *   Array completo con:
   *   - 'experiment' (array): Datos del experimento.
   *   - 'variants' (array): Lista de variantes con sus métricas.
   *   - 'analysis' (array): Resultado del análisis estadístico.
   *   - 'chi_squared' (array): Resultado del test chi-cuadrado.
   *   - 'sample_size' (array): Información de tamaño muestral.
   *   - 'funnel' (array): Funnel de conversión simplificado.
   *   Retorna array vacío si el experimento no existe.
   */
  public function getExperimentDetail(int $experiment_id): array {
    try {
      $experiment = $this->entityTypeManager->getStorage('ab_experiment')->load($experiment_id);

      if (!$experiment) {
        return [];
      }

      // Cargar variantes.
      $variant_storage = $this->entityTypeManager->getStorage('ab_variant');
      $variant_ids = $variant_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('experiment_id', $experiment_id)
        ->sort('is_control', 'DESC')
        ->execute();

      $variant_entities = !empty($variant_ids) ? $variant_storage->loadMultiple($variant_ids) : [];

      // Construir datos de variantes.
      $variants_data = [];
      $total_visitors = 0;
      $total_conversions = 0;
      $total_revenue = 0.0;

      foreach ($variant_entities as $v) {
        $v_visitors = (int) ($v->get('visitors')->value ?? 0);
        $v_conversions = (int) ($v->get('conversions')->value ?? 0);
        $v_revenue = (float) ($v->get('revenue')->value ?? 0.0);
        $v_rate = $v_visitors > 0 ? ($v_conversions / $v_visitors) * 100.0 : 0.0;

        $total_visitors += $v_visitors;
        $total_conversions += $v_conversions;
        $total_revenue += $v_revenue;

        $variants_data[] = [
          'id' => (int) $v->id(),
          'name' => $v->get('label')->value ?? '',
          'is_control' => (bool) ($v->get('is_control')->value ?? FALSE),
          'visitors' => $v_visitors,
          'conversions' => $v_conversions,
          'conversion_rate' => round($v_rate, 2),
          'revenue' => round($v_revenue, 2),
          'traffic_percentage' => (float) ($v->get('traffic_weight')->value ?? 0),
        ];
      }

      // Ejecutar análisis estadístico completo.
      $confidence_threshold = (float) ($experiment->get('confidence_threshold')->value ?? 0.95);
      $analysis = $this->statisticalEngine->analyzeExperiment($variants_data, $confidence_threshold);

      // Ejecutar test chi-cuadrado para test de independencia global.
      $chi_squared = $this->statisticalEngine->chiSquaredTest($variants_data);

      // Calcular tamaño muestral necesario.
      $baseline_rate = 0.0;
      foreach ($variants_data as $vd) {
        if ($vd['is_control'] && $vd['visitors'] > 0) {
          $baseline_rate = $vd['conversions'] / $vd['visitors'];
          break;
        }
      }

      // Si no hay tasa base, estimar con los datos globales.
      if ($baseline_rate <= 0.0 && $total_visitors > 0) {
        $baseline_rate = $total_conversions / $total_visitors;
      }

      // MDE not stored in entity; use 10% as reasonable default for sample size estimation.
      $mde = 0.10;
      $required_sample = 0;
      $days_remaining = -1;

      if ($baseline_rate > 0.0 && $baseline_rate < 1.0 && $mde > 0.0) {
        $required_sample = $this->statisticalEngine->calculateMinimumSampleSize(
          $baseline_rate,
          $mde,
          $confidence_threshold
        );

        // Estimar tasa diaria.
        $start_date_val = $experiment->get('start_date')->value;
        $started_timestamp = $start_date_val ? strtotime($start_date_val) : ($experiment->get('created')->value ?? 0);
        $days_elapsed = 0;
        if ($started_timestamp > 0) {
          $days_elapsed = max(1, (int) floor((time() - $started_timestamp) / 86400));
        }

        $daily_rate = $days_elapsed > 0 ? (int) ceil($total_visitors / $days_elapsed) : 0;

        $days_remaining = $this->statisticalEngine->estimateDaysToSignificance(
          $total_visitors,
          $daily_rate,
          $required_sample
        );
      }

      // Datos del experimento.
      $created_timestamp = $experiment->get('created')->value ?? 0;
      $start_date_val2 = $experiment->get('start_date')->value;
      $started_timestamp = $start_date_val2 ? strtotime($start_date_val2) : $created_timestamp;

      $experiment_data = [
        'id' => $experiment_id,
        'name' => $experiment->get('label')->value ?? '',
        'machine_name' => $experiment->get('machine_name')->value ?? '',
        'description' => $experiment->get('hypothesis')->value ?? '',
        'type' => $experiment->get('experiment_type')->value ?? '',
        'status' => $experiment->get('status')->value ?? 'draft',
        'confidence_threshold' => $confidence_threshold,
        'minimum_detectable_effect' => $mde,
        'total_visitors' => $total_visitors,
        'total_conversions' => $total_conversions,
        'total_revenue' => round($total_revenue, 2),
        'overall_rate' => $total_visitors > 0 ? round(($total_conversions / $total_visitors) * 100.0, 2) : 0.0,
        'created' => $created_timestamp ? date('Y-m-d H:i:s', $created_timestamp) : '',
        'started_at' => $started_timestamp ? date('Y-m-d H:i:s', $started_timestamp) : '',
        'days_running' => $started_timestamp > 0 ? max(0, (int) floor((time() - $started_timestamp) / 86400)) : 0,
        'winner_variant' => $experiment->get('winner_variant')->target_id ?? NULL,
      ];

      // Construir funnel simplificado.
      $funnel = [
        [
          'label' => (string) $this->t('Visitors'),
          'count' => $total_visitors,
          'rate' => 100.0,
        ],
        [
          'label' => (string) $this->t('Conversions'),
          'count' => $total_conversions,
          'rate' => $total_visitors > 0 ? round(($total_conversions / $total_visitors) * 100.0, 1) : 0.0,
        ],
      ];

      return [
        'experiment' => $experiment_data,
        'variants' => $variants_data,
        'analysis' => $analysis,
        'chi_squared' => $chi_squared,
        'sample_size' => [
          'required' => $required_sample,
          'current' => $total_visitors,
          'remaining' => max(0, $required_sample - $total_visitors),
          'days_remaining' => $days_remaining,
          'progress' => $required_sample > 0 ? round(min(100.0, ($total_visitors / $required_sample) * 100.0), 1) : 0.0,
        ],
        'funnel' => $funnel,
      ];

    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo detalle del experimento @id: @error', [
        '@id' => $experiment_id,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene métricas KPI agregadas para el dashboard de A/B testing.
   *
   * LÓGICA:
   * Consulta todos los experimentos del tenant y calcula métricas
   * globales para las cards del dashboard:
   * - Experimentos activos (status = running).
   * - Experimentos completados (status = completed).
   * - Total de visitantes sumados de todos los experimentos.
   * - Tasa de conversión media ponderada.
   * - Experimentos con ganador significativo.
   * - Media de días hasta alcanzar significancia.
   *
   * @param int $tenant_id
   *   ID del tenant para filtrado.
   *
   * @return array
   *   Array con métricas KPI:
   *   - 'active_experiments' (int): Experimentos en estado 'running'.
   *   - 'completed_experiments' (int): Experimentos en estado 'completed'.
   *   - 'total_visitors' (int): Visitantes totales en todos los experimentos.
   *   - 'avg_conversion_rate' (float): Tasa de conversión media (%).
   *   - 'experiments_with_winner' (int): Experimentos con ganador significativo.
   *   - 'avg_days_to_significance' (int): Media de días hasta significancia.
   */
  public function getDashboardMetrics(int $tenant_id): array {
    $metrics = [
      'active_experiments' => 0,
      'completed_experiments' => 0,
      'total_visitors' => 0,
      'avg_conversion_rate' => 0.0,
      'experiments_with_winner' => 0,
      'avg_days_to_significance' => 0,
    ];

    try {
      $experiment_storage = $this->entityTypeManager->getStorage('ab_experiment');

      // Contar experimentos activos.
      $active_count = $experiment_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->condition('status', 'running')
        ->count()
        ->execute();
      $metrics['active_experiments'] = (int) $active_count;

      // Contar experimentos completados.
      $completed_count = $experiment_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->condition('status', 'completed')
        ->count()
        ->execute();
      $metrics['completed_experiments'] = (int) $completed_count;

      // Obtener todos los experimentos para métricas globales.
      $all_experiment_ids = $experiment_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->execute();

      if (empty($all_experiment_ids)) {
        return $metrics;
      }

      $experiments = $experiment_storage->loadMultiple($all_experiment_ids);
      $variant_storage = $this->entityTypeManager->getStorage('ab_variant');

      $total_visitors = 0;
      $total_conversions = 0;
      $experiments_with_winner = 0;
      $days_to_significance = [];

      foreach ($experiments as $experiment) {
        $exp_id = (int) $experiment->id();

        // Cargar variantes del experimento.
        $variant_ids = $variant_storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('experiment_id', $exp_id)
          ->execute();

        $variants = !empty($variant_ids) ? $variant_storage->loadMultiple($variant_ids) : [];

        $exp_visitors = 0;
        $exp_conversions = 0;
        $variants_data = [];

        foreach ($variants as $v) {
          $v_visitors = (int) ($v->get('visitors')->value ?? 0);
          $v_conversions = (int) ($v->get('conversions')->value ?? 0);
          $exp_visitors += $v_visitors;
          $exp_conversions += $v_conversions;

          $variants_data[] = [
            'id' => (int) $v->id(),
            'name' => $v->get('label')->value ?? '',
            'is_control' => (bool) ($v->get('is_control')->value ?? FALSE),
            'visitors' => $v_visitors,
            'conversions' => $v_conversions,
          ];
        }

        $total_visitors += $exp_visitors;
        $total_conversions += $exp_conversions;

        // Verificar si tiene ganador.
        $status = $experiment->get('status')->value ?? 'draft';
        if ($status === 'completed' && $experiment->get('winner_variant')->target_id) {
          $experiments_with_winner++;
        }
        elseif (count($variants_data) >= 2 && $exp_visitors > 0) {
          $analysis = $this->statisticalEngine->analyzeExperiment($variants_data);
          if ($analysis['has_winner'] ?? FALSE) {
            $experiments_with_winner++;
          }
        }

        // Calcular días en ejecución para los completados.
        if ($status === 'completed') {
          $sd = $experiment->get('start_date')->value;
          $started = $sd ? strtotime($sd) : ($experiment->get('created')->value ?? 0);
          $ed = $experiment->get('end_date')->value;
          $completed = $ed ? strtotime($ed) : time();
          if ($started > 0) {
            $days = (int) floor(($completed - $started) / 86400);
            $days_to_significance[] = max(0, $days);
          }
        }
      }

      $metrics['total_visitors'] = $total_visitors;
      $metrics['avg_conversion_rate'] = $total_visitors > 0
        ? round(($total_conversions / $total_visitors) * 100.0, 2)
        : 0.0;
      $metrics['experiments_with_winner'] = $experiments_with_winner;
      $metrics['avg_days_to_significance'] = !empty($days_to_significance)
        ? (int) round(array_sum($days_to_significance) / count($days_to_significance))
        : 0;

    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando métricas del dashboard para tenant @tid: @error', [
        '@tid' => $tenant_id,
        '@error' => $e->getMessage(),
      ]);
    }

    return $metrics;
  }

  /**
   * Declara un ganador para un experimento y lo completa.
   *
   * LÓGICA:
   * 1. Carga el experimento y valida que existe y no está ya completado.
   * 2. Carga la variante ganadora y valida que pertenece al experimento.
   * 3. Establece el campo winner_variant con la referencia a la variante.
   * 4. Cambia el estado del experimento a 'completed'.
   * 5. Registra la fecha de finalización en completed_at.
   * 6. Guarda la entidad y registra en log.
   *
   * @param int $experiment_id
   *   ID del experimento a completar.
   * @param int $variant_id
   *   ID de la variante declarada como ganadora.
   *
   * @return bool
   *   TRUE si se declaró el ganador correctamente.
   *   FALSE si el experimento o la variante no existen, o si hay error.
   */
  public function declareWinner(int $experiment_id, int $variant_id): bool {
    try {
      // 1. Cargar experimento.
      $experiment = $this->entityTypeManager->getStorage('ab_experiment')->load($experiment_id);

      if (!$experiment) {
        $this->logger->warning('declareWinner: Experimento @id no encontrado.', [
          '@id' => $experiment_id,
        ]);
        return FALSE;
      }

      // Verificar que no está ya completado.
      $current_status = $experiment->get('status')->value ?? 'draft';
      if ($current_status === 'completed') {
        $this->logger->warning('declareWinner: Experimento @id ya está completado.', [
          '@id' => $experiment_id,
        ]);
        return FALSE;
      }

      // 2. Cargar variante y verificar que pertenece al experimento.
      $variant = $this->entityTypeManager->getStorage('ab_variant')->load($variant_id);

      if (!$variant) {
        $this->logger->warning('declareWinner: Variante @vid no encontrada.', [
          '@vid' => $variant_id,
        ]);
        return FALSE;
      }

      $variant_experiment_id = $variant->get('experiment_id')->target_id;
      if ((int) $variant_experiment_id !== $experiment_id) {
        $this->logger->warning('declareWinner: Variante @vid no pertenece al experimento @eid (pertenece a @actual).', [
          '@vid' => $variant_id,
          '@eid' => $experiment_id,
          '@actual' => $variant_experiment_id,
        ]);
        return FALSE;
      }

      // 3. Establecer ganador y completar.
      $experiment->set('winner_variant', $variant_id);
      $experiment->set('status', 'completed');
      $experiment->set('end_date', date('Y-m-d\TH:i:s'));
      $experiment->save();

      $this->logger->info('Ganador declarado: variante "@vname" (ID: @vid) para experimento "@ename" (ID: @eid). Experimento completado.', [
        '@vname' => $variant->get('label')->value ?? '',
        '@vid' => $variant_id,
        '@ename' => $experiment->get('label')->value ?? '',
        '@eid' => $experiment_id,
      ]);

      return TRUE;

    }
    catch (\Exception $e) {
      $this->logger->error('Error declarando ganador para experimento @id: @error', [
        '@id' => $experiment_id,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
