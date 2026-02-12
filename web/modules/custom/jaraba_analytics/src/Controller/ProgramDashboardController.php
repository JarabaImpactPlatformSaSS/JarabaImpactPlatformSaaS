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
   *
   * Consulta entidades cohort_definition si existen; de lo contrario
   * lee del State API donde las cohortes se almacenan como configuracion.
   */
  protected function getCohortStats(): array {
    $stats = [
      'total_cohorts' => 0,
      'active_cohorts' => 0,
      'total_students' => 0,
      'avg_retention' => 0,
    ];

    try {
      $state = \Drupal::state();

      // Intentar leer de entidad CohortDefinition si el servicio existe.
      if (\Drupal::hasService('jaraba_analytics.cohort_analysis')) {
        try {
          $storage = $this->entityTypeManager()->getStorage('cohort_definition');

          $totalIds = $storage->getQuery()
            ->accessCheck(FALSE)
            ->count()
            ->execute();
          $stats['total_cohorts'] = (int) $totalIds;

          // Cohortes activas: aquellas cuya fecha de fin es futura o nula.
          $activeIds = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 'active')
            ->count()
            ->execute();
          $stats['active_cohorts'] = (int) $activeIds;

          // Agregar estudiantes de todas las cohortes activas.
          $activeCohorts = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 'active')
            ->execute();

          $totalStudents = 0;
          $retentionSum = 0.0;
          $cohortCount = 0;

          foreach ($storage->loadMultiple($activeCohorts) as $cohort) {
            $memberCount = 0;
            if ($cohort->hasField('member_count')) {
              $memberCount = (int) $cohort->get('member_count')->value;
            }
            elseif ($cohort->hasField('initial_users')) {
              $memberCount = (int) $cohort->get('initial_users')->value;
            }
            $totalStudents += $memberCount;

            if ($cohort->hasField('retention_rate') && $cohort->get('retention_rate')->value) {
              $retentionSum += (float) $cohort->get('retention_rate')->value;
              $cohortCount++;
            }
          }

          $stats['total_students'] = $totalStudents;
          $stats['avg_retention'] = $cohortCount > 0
            ? (int) round($retentionSum / $cohortCount)
            : 0;

          return $stats;
        }
        catch (\Exception $e) {
          // Fall through to State API.
          $this->logger->info('Cohort entity query failed, using State API: @error', [
            '@error' => $e->getMessage(),
          ]);
        }
      }

      // Fallback: leer del State API para configuracion en staging/dev.
      $cohortData = $state->get('jaraba_analytics.programa_cohorts', []);
      if (!empty($cohortData)) {
        $stats['total_cohorts'] = (int) ($cohortData['total'] ?? count($cohortData));
        $stats['active_cohorts'] = (int) ($cohortData['active'] ?? $stats['total_cohorts']);
        $stats['total_students'] = (int) ($cohortData['students'] ?? 0);
        $stats['avg_retention'] = (int) ($cohortData['avg_retention'] ?? 0);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error getting cohort stats: @error', ['@error' => $e->getMessage()]);
    }

    return $stats;
  }

  /**
   * Obtiene KPIs del programa desde datos reales.
   *
   * Consulta las siguientes fuentes (en orden de prioridad):
   * 1. Entidades de usuarios del programa (entity query).
   * 2. State API (jaraba_analytics.programa_kpis) para configuracion manual.
   * 3. Valores por defecto (cero).
   */
  protected function getProgramKpis(): array {
    $state = \Drupal::state();

    // Intentar obtener KPIs reales de la base de datos.
    $participants = 0;
    $completionRate = 0;
    $insertionRate = 0;
    $satisfaction = 0.0;

    try {
      // Contar participantes del programa: usuarios con rol 'programa_participant' o 'student'.
      $userStorage = $this->entityTypeManager()->getStorage('user');
      $programRoles = ['programa_participant', 'student', 'alumno'];

      foreach ($programRoles as $role) {
        $count = (int) $userStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 1)
          ->condition('roles', $role)
          ->count()
          ->execute();
        $participants += $count;
      }

      // Tasa de finalizacion: usuarios marcados como 'completed' en su progreso.
      if ($participants > 0) {
        $completedCount = 0;

        // Intentar con field_program_status o State API.
        try {
          $completedCount = (int) $userStorage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->condition('roles', $programRoles, 'IN')
            ->condition('field_program_status', 'completed')
            ->count()
            ->execute();
        }
        catch (\Exception $e) {
          // Campo no existe, usar State API.
          $completedCount = (int) $state->get('jaraba_analytics.programa_completed', 0);
        }

        $completionRate = (int) round(($completedCount / $participants) * 100);
      }

      // Tasa de insercion laboral desde State API (dato externo, se carga manualmente).
      $insertionRate = (int) $state->get('jaraba_analytics.programa_insertion_rate', 0);

      // Satisfaccion media desde State API (encuestas externas).
      $satisfaction = (float) $state->get('jaraba_analytics.programa_satisfaction', 0);

    }
    catch (\Exception $e) {
      $this->logger->warning('Error calculating program KPIs: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    // Fallback: si no hay participantes de la query, intentar State API.
    if ($participants === 0) {
      $stateKpis = $state->get('jaraba_analytics.programa_kpis', []);
      if (!empty($stateKpis)) {
        $participants = (int) ($stateKpis['participants'] ?? 0);
        $completionRate = (int) ($stateKpis['completion_rate'] ?? 0);
        $insertionRate = (int) ($stateKpis['insertion_rate'] ?? 0);
        $satisfaction = (float) ($stateKpis['satisfaction'] ?? 0);
      }
    }

    return [
      'participants' => [
        'value' => $participants,
        'label' => $this->t('Participantes'),
        'icon' => 'users',
      ],
      'completion_rate' => [
        'value' => $completionRate,
        'label' => $this->t('Tasa Finalizacion'),
        'format' => 'percent',
        'icon' => 'check-circle',
      ],
      'insertion_rate' => [
        'value' => $insertionRate,
        'label' => $this->t('Insercion Laboral'),
        'format' => 'percent',
        'icon' => 'briefcase',
      ],
      'satisfaction' => [
        'value' => $satisfaction,
        'label' => $this->t('Satisfaccion'),
        'format' => 'score',
        'icon' => 'star',
      ],
    ];
  }

}
