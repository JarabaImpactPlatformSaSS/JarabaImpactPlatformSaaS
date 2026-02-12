<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Service\CohortAnalysisService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para Cohort Analysis.
 *
 * PROPÓSITO:
 * Expone endpoints REST para listar cohortes, obtener curvas de retención
 * y crear nuevas definiciones de cohorte.
 *
 * LÓGICA:
 * - GET /api/v1/analytics/cohorts: lista todas las cohortes (filtrable por tenant).
 * - GET /api/v1/analytics/cohorts/{cohort_id}/retention: curva de retención.
 * - POST /api/v1/analytics/cohorts: crea una nueva definición de cohorte.
 */
class CohortApiController extends ControllerBase {

  /**
   * Servicio de análisis de cohortes.
   *
   * @var \Drupal\jaraba_analytics\Service\CohortAnalysisService
   */
  protected CohortAnalysisService $cohortAnalysisService;

  /**
   * Constructor.
   */
  public function __construct(
    CohortAnalysisService $cohort_analysis_service,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->cohortAnalysisService = $cohort_analysis_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_analytics.cohort_analysis'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * GET /api/v1/analytics/cohorts.
   *
   * Lists all cohort definitions, optionally filtered by tenant_id.
   */
  public function listCohorts(Request $request): JsonResponse {
    $tenantId = $request->query->get('tenant_id');

    $storage = $this->entityTypeManager->getStorage('cohort_definition');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('created', 'DESC');

    if ($tenantId) {
      $query->condition('tenant_id', (int) $tenantId);
    }

    $ids = $query->execute();
    $cohorts = $storage->loadMultiple($ids);

    $data = [];
    /** @var \Drupal\jaraba_analytics\Entity\CohortDefinition $cohort */
    foreach ($cohorts as $cohort) {
      $data[] = [
        'id' => (int) $cohort->id(),
        'name' => $cohort->getName(),
        'cohort_type' => $cohort->getCohortType(),
        'date_range_start' => $cohort->getDateRangeStart(),
        'date_range_end' => $cohort->getDateRangeEnd(),
        'filters' => $cohort->getFilters(),
        'tenant_id' => $cohort->getTenantId(),
        'created' => (int) $cohort->get('created')->value,
        'changed' => (int) $cohort->get('changed')->value,
      ];
    }

    return new JsonResponse([
      'cohorts' => $data,
      'total' => count($data),
    ]);
  }

  /**
   * GET /api/v1/analytics/cohorts/{cohort_id}/retention.
   *
   * Returns the week-by-week retention curve for a specific cohort.
   */
  public function getRetentionCurve(Request $request, string $cohort_id): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('cohort_definition');
    $cohort = $storage->load($cohort_id);

    if (!$cohort) {
      return new JsonResponse([
        'error' => 'Cohort not found.',
      ], 404);
    }

    /** @var \Drupal\jaraba_analytics\Entity\CohortDefinition $cohort */
    $weeks = (int) ($request->query->get('weeks', 12));
    if ($weeks < 1 || $weeks > 52) {
      $weeks = 12;
    }

    $retention = $this->cohortAnalysisService->buildRetentionCurve($cohort, $weeks);
    $members = $this->cohortAnalysisService->getCohortMembers($cohort);

    return new JsonResponse([
      'cohort_id' => (int) $cohort->id(),
      'cohort_name' => $cohort->getName(),
      'cohort_type' => $cohort->getCohortType(),
      'members_count' => count($members),
      'weeks' => $weeks,
      'retention' => $retention,
    ]);
  }

  /**
   * POST /api/v1/analytics/cohorts.
   *
   * Creates a new cohort definition from JSON payload.
   */
  public function createCohort(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (!$content || empty($content['name']) || empty($content['cohort_type'])) {
      return new JsonResponse([
        'error' => 'Missing required fields: name, cohort_type.',
      ], 400);
    }

    // Validate cohort type.
    $validTypes = [
      'registration_date',
      'first_purchase',
      'vertical',
      'custom',
    ];
    if (!in_array($content['cohort_type'], $validTypes, TRUE)) {
      return new JsonResponse([
        'error' => 'Invalid cohort_type. Valid values: ' . implode(', ', $validTypes),
      ], 400);
    }

    $storage = $this->entityTypeManager->getStorage('cohort_definition');

    $values = [
      'name' => $content['name'],
      'cohort_type' => $content['cohort_type'],
    ];

    if (!empty($content['tenant_id'])) {
      $values['tenant_id'] = (int) $content['tenant_id'];
    }
    if (!empty($content['date_range_start'])) {
      $values['date_range_start'] = $content['date_range_start'];
    }
    if (!empty($content['date_range_end'])) {
      $values['date_range_end'] = $content['date_range_end'];
    }
    if (!empty($content['filters']) && is_array($content['filters'])) {
      $values['filters'] = $content['filters'];
    }

    try {
      $cohort = $storage->create($values);
      $cohort->save();

      return new JsonResponse([
        'success' => TRUE,
        'cohort_id' => (int) $cohort->id(),
        'name' => $cohort->get('name')->value,
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Failed to create cohort: ' . $e->getMessage(),
      ], 500);
    }
  }

}
