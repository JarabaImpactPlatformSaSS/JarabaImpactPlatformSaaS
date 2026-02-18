<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_analytics\Service\ReportExecutionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para Informes Personalizados.
 *
 * PROPÓSITO:
 * Proporciona endpoints REST para listar y ejecutar informes
 * personalizados de analytics.
 *
 * ENDPOINTS:
 * - GET  /api/v1/analytics/reports                    — Listar informes.
 * - POST /api/v1/analytics/reports/{report_id}/execute — Ejecutar informe.
 */
class ReportApiController extends ControllerBase {

  /**
   * Servicio de ejecución de informes.
   *
   * @var \Drupal\jaraba_analytics\Service\ReportExecutionService
   */
  protected ReportExecutionService $reportExecutionService;

  /**
   * Servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructor del controlador.
   *
   * @param \Drupal\jaraba_analytics\Service\ReportExecutionService $report_execution_service
   *   Servicio de ejecución de informes.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Gestor de tipos de entidad.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenant_context
   *   Servicio de contexto de tenant.
   */
  public function __construct(
    ReportExecutionService $report_execution_service,
    EntityTypeManagerInterface $entity_type_manager,
    TenantContextService $tenant_context,
  ) {
    $this->reportExecutionService = $report_execution_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_analytics.report_execution'),
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * GET /api/v1/analytics/reports.
   *
   * Lista todos los informes personalizados, opcionalmente filtrados
   * por tenant_id.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto de petición HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de informes personalizados.
   */
  public function list(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

    $storage = $this->entityTypeManager->getStorage('custom_report');
    $query = $storage->getQuery()->accessCheck(TRUE);

    if ($tenantId) {
      $query->condition('tenant_id', (int) $tenantId);
    }

    $query->sort('created', 'DESC');
    $ids = $query->execute();

    if (empty($ids)) {
      return new JsonResponse([
        'reports' => [],
        'total' => 0,
      ]);
    }

    $entities = $storage->loadMultiple($ids);
    $reports = [];

    /** @var \Drupal\jaraba_analytics\Entity\CustomReport $entity */
    foreach ($entities as $entity) {
      $reports[] = [
        'id' => (int) $entity->id(),
        'name' => $entity->label(),
        'tenant_id' => $entity->get('tenant_id')->target_id ? (int) $entity->get('tenant_id')->target_id : NULL,
        'report_type' => $entity->get('report_type')->value,
        'date_range' => $entity->get('date_range')->value,
        'schedule' => $entity->get('schedule')->value,
        'last_executed' => $entity->get('last_executed')->value,
        'created' => (int) $entity->get('created')->value,
        'changed' => (int) $entity->get('changed')->value,
      ];
    }

    return new JsonResponse([
      'reports' => $reports,
      'total' => count($reports),
    ]);
  }

  /**
   * POST /api/v1/analytics/reports/{report_id}/execute.
   *
   * Ejecuta un informe personalizado y devuelve los resultados.
   *
   * @param int $report_id
   *   ID del informe a ejecutar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultados de la ejecución del informe.
   */
  public function execute(int $report_id): JsonResponse {
    $results = $this->reportExecutionService->executeReport($report_id);

    if (isset($results['error'])) {
      $statusCode = str_contains($results['error'], 'no encontrado') ? 404 : 500;
      return new JsonResponse($results, $statusCode);
    }

    return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => $results, 'meta' => ['timestamp' => time()]]);
  }

}
