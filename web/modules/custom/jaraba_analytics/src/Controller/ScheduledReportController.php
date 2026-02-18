<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Service\ReportSchedulerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador para la gestion de informes programados.
 *
 * PROPOSITO:
 * Renderiza la pagina de lista de informes programados y
 * proporciona previsualizacion de informes.
 *
 * LOGICA:
 * - listReports: muestra la lista de informes con estado, proxima ejecucion.
 * - previewReport: genera y devuelve datos de preview para un informe.
 */
class ScheduledReportController extends ControllerBase {

  /**
   * Report scheduler service.
   *
   * @var \Drupal\jaraba_analytics\Service\ReportSchedulerService
   */
  protected ReportSchedulerService $reportScheduler;

  /**
   * Constructor.
   */
  public function __construct(
    ReportSchedulerService $report_scheduler,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->reportScheduler = $report_scheduler;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_analytics.report_scheduler'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Lists all scheduled reports.
   *
   * @return array
   *   Render array for the reports list page.
   */
  public function listReports(): array {
    try {
      $reports = $this->reportScheduler->getScheduledReports();
      $canManage = $this->currentUser()->hasPermission('manage scheduled reports');

      return [
        '#theme' => 'analytics_scheduled_reports',
        '#reports' => $reports,
        '#can_manage' => $canManage,
        '#attached' => [
          'library' => [
            'jaraba_analytics/scheduled-reports',
          ],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_analytics')->error('Failed to list reports: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        '#markup' => $this->t('Unable to load scheduled reports. Please try again later.'),
      ];
    }
  }

  /**
   * Previews a scheduled report.
   *
   * @param int $reportId
   *   The scheduled report entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with report preview data.
   */
  public function previewReport(int $reportId): JsonResponse {
    try {
      $reportData = $this->reportScheduler->generateReport($reportId);

      if (empty($reportData)) {
        // AUDIT-CONS-N08: Standardized JSON envelope.
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'NOT_FOUND', 'message' => 'Report not found or empty.'],
        ], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['preview' => TRUE, 'report' => $reportData],
        'meta' => ['timestamp' => time()],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Failed to preview report.'],
      ], 500);
    }
  }

}
