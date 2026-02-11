<?php

namespace Drupal\jaraba_ab_testing\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ab_testing\Service\ExperimentAggregatorService;
use Drupal\jaraba_ab_testing\Service\StatisticalEngineService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del dashboard de A/B testing.
 *
 * ESTRUCTURA:
 * Renderiza el dashboard centralizado de experimentos A/B con KPIs
 * agregados, lista de experimentos activos y vista de detalle individual
 * con análisis estadístico por variante.
 *
 * LÓGICA:
 * El dashboard muestra:
 * 1. KPI cards: experimentos activos, visitantes totales, tasa media, ganadores.
 * 2. Lista de experimentos con estado y métricas resumidas.
 * 3. Detalle individual con gráfico de conversión y análisis estadístico.
 *
 * RELACIONES:
 * - ABTestingDashboardController -> ExperimentAggregatorService
 * - ABTestingDashboardController -> StatisticalEngineService
 * - ABTestingDashboardController <- jaraba_ab_testing.routing.yml
 *
 * @package Drupal\jaraba_ab_testing\Controller
 */
class ABTestingDashboardController extends ControllerBase {

  /**
   * Servicio de agregación de experimentos.
   *
   * @var \Drupal\jaraba_ab_testing\Service\ExperimentAggregatorService|null
   */
  protected ?ExperimentAggregatorService $aggregator = NULL;

  /**
   * Motor estadístico.
   *
   * @var \Drupal\jaraba_ab_testing\Service\StatisticalEngineService|null
   */
  protected ?StatisticalEngineService $statisticalEngine = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);

    try {
      $instance->aggregator = $container->get('jaraba_ab_testing.experiment_aggregator');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    try {
      $instance->statisticalEngine = $container->get('jaraba_ab_testing.statistical_engine');
    }
    catch (\Exception $e) {
      // Service may not be available yet.
    }

    return $instance;
  }

  /**
   * Dashboard principal de A/B testing (/admin/ab-testing).
   *
   * LÓGICA:
   * Obtiene las métricas KPI del tenant actual y la lista de todos
   * los experimentos con sus datos resumidos. Renderiza la plantilla
   * del dashboard con los datos organizados.
   *
   * @return array
   *   Render array con #theme 'jaraba_ab_testing_dashboard'.
   */
  public function dashboard(): array {
    $tenant_id = 0;

    // Obtener métricas KPI
    $kpis = $this->aggregator
      ? $this->aggregator->getDashboardMetrics($tenant_id)
      : [
        'active_experiments' => 0,
        'completed_experiments' => 0,
        'total_visitors' => 0,
        'avg_conversion_rate' => 0.0,
        'experiments_with_winner' => 0,
        'avg_days_to_significance' => 0,
      ];

    // Obtener experimentos activos
    $active_experiments = $this->aggregator
      ? $this->aggregator->getTenantExperiments($tenant_id, 'running')
      : [];

    // Obtener experimentos completados (últimos 10)
    $completed_experiments = $this->aggregator
      ? $this->aggregator->getTenantExperiments($tenant_id, 'completed')
      : [];

    // Todos los experimentos
    $all_experiments = $this->aggregator
      ? $this->aggregator->getTenantExperiments($tenant_id)
      : [];

    return [
      '#theme' => 'jaraba_ab_testing_dashboard',
      '#kpis' => $kpis,
      '#active_experiments' => $active_experiments,
      '#completed_experiments' => array_slice($completed_experiments, 0, 10),
      '#all_experiments' => $all_experiments,
      '#attached' => [
        'library' => [
          'jaraba_ab_testing/dashboard',
        ],
      ],
      '#cache' => [
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Detalle de un experimento (/admin/ab-testing/{experiment_id}).
   *
   * LÓGICA:
   * Obtiene el detalle completo del experimento con análisis estadístico
   * por variante: Z-score, confianza, lift sobre control, y recomendación.
   *
   * @param int $experiment_id
   *   ID del experimento.
   *
   * @return array
   *   Render array con #theme 'jaraba_ab_testing_experiment_detail'.
   */
  public function experimentDetail(int $experiment_id): array {
    $detail = $this->aggregator
      ? $this->aggregator->getExperimentDetail($experiment_id)
      : [];

    if (empty($detail)) {
      return [
        '#markup' => $this->t('Experiment not found.'),
      ];
    }

    return [
      '#theme' => 'jaraba_ab_testing_experiment_detail',
      '#experiment' => $detail['experiment'] ?? [],
      '#variants' => $detail['variants'] ?? [],
      '#analysis' => $detail['analysis'] ?? [],
      '#funnel' => $detail['funnel'] ?? [],
      '#attached' => [
        'library' => [
          'jaraba_ab_testing/experiment-detail',
        ],
      ],
      '#cache' => [
        'max-age' => 60,
        'tags' => ['ab_experiment:' . $experiment_id],
      ],
    ];
  }

}
