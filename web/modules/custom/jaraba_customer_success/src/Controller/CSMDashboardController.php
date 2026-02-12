<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_customer_success\Service\HealthScoreCalculatorService;
use Drupal\jaraba_customer_success\Service\ChurnPredictionService;
use Drupal\jaraba_customer_success\Service\PlaybookExecutorService;
use Drupal\jaraba_customer_success\Service\NpsSurveyService;

/**
 * Controlador frontend para el dashboard CSM en /customer-success.
 *
 * PROPÓSITO:
 * Página principal del CSM con overview de health scores,
 * tenants en riesgo, ejecuciones de playbooks y pipeline
 * de expansión. Layout limpio sin regiones Drupal.
 *
 * DIRECTRICES:
 * - Template limpio con {% include %} parciales.
 * - Todos los textos traducibles con $this->t().
 * - Body class 'page-customer-success' vía hook_preprocess_html().
 */
class CSMDashboardController extends ControllerBase {

  public function __construct(
    protected HealthScoreCalculatorService $healthCalculator,
    protected ChurnPredictionService $churnPrediction,
    protected PlaybookExecutorService $playbookExecutor,
    protected NpsSurveyService $npsSurvey,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_customer_success.health_calculator'),
      $container->get('jaraba_customer_success.churn_prediction'),
      $container->get('jaraba_customer_success.playbook_executor'),
      $container->get('jaraba_customer_success.nps_survey'),
    );
  }

  /**
   * Dashboard principal del CSM (/customer-success).
   *
   * LÓGICA:
   * 1. Obtiene distribución de categorías de salud.
   * 2. Obtiene últimos health scores.
   * 3. Obtiene tenants en riesgo (churn prediction).
   * 4. Obtiene playbooks activos y ejecuciones recientes.
   * 5. Obtiene señales de expansión pendientes.
   */
  public function dashboard(): array {
    // Distribución de categorías.
    $category_counts = $this->healthCalculator->getByCategory();
    $total_tenants = array_sum($category_counts);

    // Estadísticas generales.
    $stats = [
      'total_tenants' => $total_tenants,
      'healthy_pct' => $total_tenants > 0 ? round(($category_counts['healthy'] / $total_tenants) * 100) : 0,
      'at_risk_pct' => $total_tenants > 0 ? round((($category_counts['at_risk'] + $category_counts['critical']) / $total_tenants) * 100) : 0,
      'critical_count' => $category_counts['critical'],
    ];

    // Health scores recientes (últimos 20).
    $recent_health = $this->getRecentHealthScores(20);

    // Tenants en riesgo.
    $at_risk_tenants = $this->churnPrediction->getAtRisk();

    // Playbooks activos.
    $active_playbooks = $this->playbookExecutor->getActivePlaybooks();

    // Señales de expansión nuevas.
    $expansion_signals = $this->getNewExpansionSignals();

    return [
      '#theme' => 'jaraba_cs_dashboard',
      '#stats' => $stats,
      '#category_counts' => $category_counts,
      '#recent_health' => $recent_health,
      '#at_risk_tenants' => $at_risk_tenants,
      '#active_playbooks' => $active_playbooks,
      '#expansion_signals' => $expansion_signals,
      '#nps_trend' => [],
      '#attached' => [
        'library' => ['jaraba_customer_success/dashboard'],
      ],
      '#cache' => [
        'tags' => ['customer_health_list', 'churn_prediction_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Detalle de un tenant específico (/customer-success/tenant/{group}).
   */
  public function tenantDetail(GroupInterface $group): array {
    $tenant_id = (string) $group->id();
    $health_history = $this->healthCalculator->getHistory($tenant_id, 30);
    $latest_health = !empty($health_history) ? reset($health_history) : NULL;
    $risk_factors = $this->churnPrediction->getRiskFactors($tenant_id);

    return [
      '#theme' => 'jaraba_cs_health_card',
      '#health' => $latest_health,
      '#tenant' => $group,
      '#attached' => [
        'library' => ['jaraba_customer_success/dashboard'],
      ],
    ];
  }

  /**
   * Title callback para detalle de tenant.
   */
  public function tenantTitle(GroupInterface $group): string {
    return (string) $this->t('Health Score: @name', ['@name' => $group->label()]);
  }

  /**
   * Panel de playbooks (/customer-success/playbooks).
   */
  public function playbooks(): array {
    $playbooks = $this->playbookExecutor->getActivePlaybooks();
    $executions = $this->getRecentExecutions(20);

    return [
      '#theme' => 'jaraba_cs_playbook_timeline',
      '#executions' => $executions,
      '#playbooks' => $playbooks,
      '#attached' => [
        'library' => ['jaraba_customer_success/dashboard'],
      ],
    ];
  }

  /**
   * Pipeline de expansión (/customer-success/expansion).
   */
  public function expansion(): array {
    $signals = $this->getNewExpansionSignals();

    if (empty($signals)) {
      return [
        '#theme' => 'jaraba_cs_empty_state',
        '#title' => $this->t('No expansion signals yet'),
        '#message' => $this->t('Expansion signals will appear here when tenants approach their plan limits or show growth patterns.'),
        '#action_url' => '/admin/config/services/customer-success',
        '#action_label' => $this->t('Configure thresholds'),
      ];
    }

    // Pipeline por estado.
    $pipeline = [
      'new' => [],
      'contacted' => [],
      'won' => [],
      'lost' => [],
      'deferred' => [],
    ];

    foreach ($signals as $signal) {
      $status = $signal->getStatus();
      $pipeline[$status][] = $signal;
    }

    return [
      '#type' => 'markup',
      '#markup' => '<div class="cs-expansion-pipeline">' . $this->t('Expansion pipeline: @new new, @contacted contacted, @won won.', [
        '@new' => count($pipeline['new']),
        '@contacted' => count($pipeline['contacted']),
        '@won' => count($pipeline['won']),
      ]) . '</div>',
      '#attached' => [
        'library' => ['jaraba_customer_success/dashboard'],
      ],
    ];
  }

  /**
   * Obtiene los health scores más recientes.
   */
  protected function getRecentHealthScores(int $limit): array {
    try {
      $storage = $this->entityTypeManager()->getStorage('customer_health');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('calculated_at', 'DESC')
        ->range(0, $limit)
        ->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Obtiene señales de expansión nuevas.
   */
  protected function getNewExpansionSignals(): array {
    try {
      $storage = $this->entityTypeManager()->getStorage('expansion_signal');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('detected_at', 'DESC')
        ->range(0, 50)
        ->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Obtiene ejecuciones de playbook recientes.
   */
  protected function getRecentExecutions(int $limit): array {
    try {
      $storage = $this->entityTypeManager()->getStorage('playbook_execution');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort('started_at', 'DESC')
        ->range(0, $limit)
        ->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Exception $e) {
      return [];
    }
  }

}
