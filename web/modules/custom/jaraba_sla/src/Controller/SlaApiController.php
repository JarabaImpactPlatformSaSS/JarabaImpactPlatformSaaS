<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sla\Service\PostmortemService;
use Drupal\jaraba_sla\Service\SlaCalculatorService;
use Drupal\jaraba_sla\Service\SlaCreditEngineService;
use Drupal\jaraba_sla\Service\UptimeMonitorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for SLA management.
 *
 * Structure: Extends ControllerBase with constructor DI for all SLA services.
 * Logic: All responses use {success, data, error} envelope pattern.
 *   Tenant validation on each method via TenantContextService.
 *   API-NAMING-001 convention: GET list/detail, POST store.
 */
class SlaApiController extends ControllerBase {

  /**
   * Constructs the SLA API controller.
   */
  public function __construct(
    protected readonly SlaCalculatorService $calculator,
    protected readonly UptimeMonitorService $uptimeMonitor,
    protected readonly SlaCreditEngineService $creditEngine,
    protected readonly PostmortemService $postmortem,
    protected readonly TenantContextService $tenantContext,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_sla.calculator'),
      $container->get('jaraba_sla.uptime_monitor'),
      $container->get('jaraba_sla.credit_engine'),
      $container->get('jaraba_sla.postmortem'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * GET /api/v1/sla/agreements — List tenant's SLA agreements.
   */
  public function listAgreements(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant context not found.'],
      ], 403);
    }

    try {
      $storage = $this->entityTypeManager->getStorage('sla_agreement');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC');

      $activeOnly = $request->query->get('active');
      if ($activeOnly !== NULL) {
        $query->condition('is_active', (bool) $activeOnly);
      }

      $limit = min((int) ($request->query->get('limit') ?? 25), 100);
      $offset = max((int) ($request->query->get('offset') ?? 0), 0);
      $query->range($offset, $limit);

      $ids = $query->execute();
      $items = [];

      if (!empty($ids)) {
        $agreements = $storage->loadMultiple($ids);
        foreach ($agreements as $agreement) {
          $items[] = $this->serializeAgreement($agreement);
        }
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $items,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_sla')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'],
      ], 500);
    }
  }

  /**
   * GET /api/v1/sla/agreements/{agreement_id} — Agreement detail.
   */
  public function agreementDetail(int $agreement_id): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant context not found.'],
      ], 403);
    }

    try {
      $agreement = $this->entityTypeManager->getStorage('sla_agreement')
        ->load($agreement_id);

      if (!$agreement) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'NOT_FOUND', 'message' => 'Agreement not found.'],
        ], 404);
      }

      // Verify tenant ownership.
      if ((int) $agreement->get('tenant_id')->target_id !== $tenantId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'FORBIDDEN', 'message' => 'Agreement does not belong to your tenant.'],
        ], 403);
      }

      $data = $this->serializeAgreement($agreement);

      // Include compliance check.
      $data['compliance'] = $this->calculator->checkSlaCompliance($agreement_id);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_sla')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'],
      ], 500);
    }
  }

  /**
   * GET /api/v1/sla/measurements — Uptime measurements for tenant.
   */
  public function listMeasurements(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant context not found.'],
      ], 403);
    }

    try {
      $history = $this->creditEngine->getCreditsHistory($tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $history,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_sla')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'],
      ], 500);
    }
  }

  /**
   * GET /api/v1/sla/status — Public status page data.
   */
  public function statusData(): JsonResponse {
    try {
      $data = $this->uptimeMonitor->getStatusPageData();

      return new JsonResponse([
        'success' => TRUE,
        'data' => $data,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => 'Failed to retrieve status data.'],
      ], 500);
    }
  }

  /**
   * POST /api/v1/sla/postmortem — Create postmortem.
   */
  public function createPostmortem(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant context not found.'],
      ], 403);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];

    if (empty($data['incident_id'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'Field required: incident_id.'],
      ], 422);
    }

    // Verify the incident belongs to this tenant.
    $incident = $this->entityTypeManager->getStorage('sla_incident')
      ->load((int) $data['incident_id']);

    if (!$incident) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Incident not found.'],
      ], 404);
    }

    if ((int) $incident->get('tenant_id')->target_id !== $tenantId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'FORBIDDEN', 'message' => 'Incident does not belong to your tenant.'],
      ], 403);
    }

    $result = $this->postmortem->create((int) $data['incident_id'], $data);

    if (!$result) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => 'Failed to create postmortem.'],
      ], 500);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => (int) $result->id(),
        'status' => $result->get('status')->value,
        'root_cause' => $result->get('root_cause')->value ?? '',
        'preventive_actions' => $result->get('preventive_actions')->value ?? '',
      ],
    ], 201);
  }

  /**
   * GET /api/v1/sla/postmortems — List postmortems.
   */
  public function listPostmortems(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant context not found.'],
      ], 403);
    }

    $page = max((int) ($request->query->get('page') ?? 0), 0);
    $limit = min((int) ($request->query->get('limit') ?? 20), 100);

    $result = $this->postmortem->list($tenantId, $page, $limit);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $result['items'],
      'meta' => [
        'total' => $result['total'],
        'page' => $result['page'],
        'limit' => $result['limit'],
      ],
    ]);
  }

  /**
   * GET /api/v1/sla/report/{period} — Monthly SLA report.
   */
  public function monthlyReport(string $period): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'TENANT_REQUIRED', 'message' => 'Tenant context not found.'],
      ], 403);
    }

    // Parse period (YYYY-MM).
    $parts = explode('-', $period);
    if (count($parts) !== 2) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'Period must be in YYYY-MM format.'],
      ], 422);
    }

    $year = (int) $parts[0];
    $month = (int) $parts[1];

    if ($year < 2020 || $year > 2100 || $month < 1 || $month > 12) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION', 'message' => 'Invalid period.'],
      ], 422);
    }

    try {
      $report = $this->calculator->getMonthlyReport($tenantId, $year, $month);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $report,
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_sla')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.'],
      ], 500);
    }
  }

  /**
   * Serializes an SLA agreement entity to an API-friendly array.
   *
   * @param object $agreement
   *   The SLA agreement entity.
   *
   * @return array
   *   Serialized agreement data.
   */
  protected function serializeAgreement(object $agreement): array {
    return [
      'id' => (int) $agreement->id(),
      'uuid' => $agreement->uuid(),
      'tenant_id' => (int) ($agreement->get('tenant_id')->target_id ?? 0),
      'sla_tier' => $agreement->get('sla_tier')->value ?? 'standard',
      'uptime_target' => (float) ($agreement->get('uptime_target')->value ?? 99.9),
      'credit_policy' => json_decode($agreement->get('credit_policy')->value ?? '[]', TRUE) ?? [],
      'custom_terms' => json_decode($agreement->get('custom_terms')->value ?? '{}', TRUE) ?? [],
      'effective_date' => $agreement->get('effective_date')->value ?? '',
      'expiry_date' => $agreement->get('expiry_date')->value,
      'is_active' => (bool) ($agreement->get('is_active')->value ?? FALSE),
      'created' => $agreement->get('created')->value ?? '',
      'changed' => $agreement->get('changed')->value ?? '',
    ];
  }

}
