<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_analytics\Service\GrantTrackingService;
use Drupal\jaraba_analytics\Service\InstitutionalReportService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * Program Dashboard for Elena avatar (entity admin).
 *
 * Dashboard institucional con:
 * - Grant Burn Rate tracker con visualizacion temporal.
 * - Gestion de cohortes activas.
 * - Generacion de informes para justificacion de fondos publicos.
 * - KPIs del programa (participantes, progreso, insercion).
 *
 * Ruta: /programa/dashboard
 * F7 — Doc 182.
 */
class ProgramDashboardController extends ControllerBase {

  public function __construct(
    protected GrantTrackingService $grantTracking,
    protected InstitutionalReportService $reportService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_analytics.grant_tracking'),
      $container->get('jaraba_analytics.institutional_report'),
      $container->get('logger.channel.jaraba_analytics'),
    );
  }

  /**
   * GET /programa/dashboard
   *
   * Dashboard principal del programa institucional.
   */
  public function dashboard(): array {
    $grantConfig = $this->getGrantConfig();
    $grantSummary = $this->grantTracking->getGrantSummary($grantConfig);
    $reportTypes = $this->reportService->getAvailableReportTypes();
    $cohortStats = $this->getCohortStats();
    $programKpis = $this->getProgramKpis();

    return [
      '#theme' => 'programa_dashboard',
      '#grant_summary' => $grantSummary,
      '#report_types' => $reportTypes,
      '#cohort_stats' => $cohortStats,
      '#program_kpis' => $programKpis,
      '#attached' => [
        'library' => [
          'jaraba_analytics/programa-dashboard',
        ],
        'drupalSettings' => [
          'programaDashboard' => [
            'grantSummary' => $grantSummary,
            'grantStatusUrl' => '/api/v1/programa/grant-status',
            'reportGenerateUrl' => '/api/v1/programa/reports/generate',
          ],
        ],
      ],
      '#cache' => [
        'max-age' => 300,
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * GET /api/v1/programa/grant-status
   *
   * Retorna el estado actual del grant en JSON.
   */
  public function grantStatus(): JsonResponse {
    try {
      $grantConfig = $this->getGrantConfig();
      $summary = $this->grantTracking->getGrantSummary($grantConfig);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $summary,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting grant status: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error obteniendo estado del grant.',
      ], 500);
    }
  }

  /**
   * POST /api/v1/programa/reports/generate
   *
   * Genera un informe institucional en PDF.
   *
   * Body: { "type": "monthly_tracking", "data": { ... } }
   */
  public function generateReport(Request $request): JsonResponse {
    try {
      $body = json_decode($request->getContent(), TRUE) ?? [];
      $type = $body['type'] ?? '';
      $data = $body['data'] ?? [];

      if (!$type) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Tipo de informe requerido.',
        ], 400);
      }

      $result = $this->reportService->generateReport($type, $data);

      return new JsonResponse($result, $result['success'] ? 200 : 400);
    }
    catch (\Exception $e) {
      $this->logger->error('Error generating report: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error generando informe.',
      ], 500);
    }
  }

  /**
   * Obtiene configuracion del grant del estado o config.
   *
   * En produccion, esto leeria de una entidad GrantProgram o config.
   * Por ahora retorna datos de ejemplo configurables.
   */
  protected function getGrantConfig(): array {
    $state = \Drupal::state();

    return [
      'total' => (int) $state->get('jaraba_analytics.grant_total', 500000),
      'spent' => (int) $state->get('jaraba_analytics.grant_spent', 0),
      'start_date' => $state->get('jaraba_analytics.grant_start', date('Y') . '-01-01'),
      'end_date' => $state->get('jaraba_analytics.grant_end', date('Y') . '-12-31'),
      'budget_lines' => $state->get('jaraba_analytics.grant_budget_lines', [
        ['name' => 'Personal docente', 'budget' => 200000, 'spent' => 0],
        ['name' => 'Material formativo', 'budget' => 50000, 'spent' => 0],
        ['name' => 'Infraestructura', 'budget' => 100000, 'spent' => 0],
        ['name' => 'Becas participantes', 'budget' => 80000, 'spent' => 0],
        ['name' => 'Gestion y administracion', 'budget' => 70000, 'spent' => 0],
      ]),
    ];
  }

  /**
   * Obtiene estadisticas de cohortes activas.
   */
  protected function getCohortStats(): array {
    $stats = [
      'total_cohorts' => 0,
      'active_cohorts' => 0,
      'total_students' => 0,
      'avg_retention' => 0,
    ];

    try {
      if (\Drupal::hasService('jaraba_analytics.cohort_analysis')) {
        $storage = $this->entityTypeManager()->getStorage('cohort_definition');
        $ids = $storage->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();
        $stats['total_cohorts'] = (int) $ids;
        $stats['active_cohorts'] = $stats['total_cohorts'];
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error getting cohort stats: @error', ['@error' => $e->getMessage()]);
    }

    return $stats;
  }

  /**
   * Obtiene KPIs del programa.
   */
  protected function getProgramKpis(): array {
    return [
      'participants' => [
        'value' => 0,
        'label' => $this->t('Participantes'),
        'icon' => 'users',
      ],
      'completion_rate' => [
        'value' => 0,
        'label' => $this->t('Tasa Finalizacion'),
        'format' => 'percent',
        'icon' => 'check-circle',
      ],
      'insertion_rate' => [
        'value' => 0,
        'label' => $this->t('Insercion Laboral'),
        'format' => 'percent',
        'icon' => 'briefcase',
      ],
      'satisfaction' => [
        'value' => 0,
        'label' => $this->t('Satisfaccion'),
        'format' => 'score',
        'icon' => 'star',
      ],
    ];
  }

}
