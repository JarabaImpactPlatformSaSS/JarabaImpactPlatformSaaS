<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_analytics\Service\FunnelTrackingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para Funnels de conversión.
 *
 * PROPÓSITO:
 * Proporciona endpoints REST para listar, crear y calcular
 * funnels de conversión definidos por el usuario.
 *
 * ENDPOINTS:
 * - GET  /api/v1/analytics/funnels               — Listar funnels.
 * - GET  /api/v1/analytics/funnels/{id}/calculate — Calcular funnel.
 * - POST /api/v1/analytics/funnels               — Crear funnel.
 */
class FunnelApiController extends ControllerBase {

  /**
   * Funnel tracking service.
   *
   * @var \Drupal\jaraba_analytics\Service\FunnelTrackingService
   */
  protected FunnelTrackingService $funnelTrackingService;

  /**
   * Servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructor.
   *
   * @param \Drupal\jaraba_analytics\Service\FunnelTrackingService $funnel_tracking_service
   *   Funnel tracking service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenant_context
   *   Servicio de contexto de tenant.
   */
  public function __construct(
    FunnelTrackingService $funnel_tracking_service,
    EntityTypeManagerInterface $entity_type_manager,
    TenantContextService $tenant_context,
  ) {
    $this->funnelTrackingService = $funnel_tracking_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_analytics.funnel_tracking'),
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * GET /api/v1/analytics/funnels.
   *
   * Lista todas las definiciones de funnels, opcionalmente filtradas
   * por tenant_id.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de funnels.
   */
  public function list(Request $request): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

    $storage = $this->entityTypeManager->getStorage('funnel_definition');
    $query = $storage->getQuery()->accessCheck(TRUE);

    if ($tenantId) {
      $query->condition('tenant_id', (int) $tenantId);
    }

    $query->sort('created', 'DESC');
    $ids = $query->execute();

    if (empty($ids)) {
      return new JsonResponse([
        'funnels' => [],
        'total' => 0,
      ]);
    }

    $entities = $storage->loadMultiple($ids);
    $funnels = [];

    /** @var \Drupal\jaraba_analytics\Entity\FunnelDefinition $entity */
    foreach ($entities as $entity) {
      $steps = $entity->getSteps();
      $funnels[] = [
        'id' => (int) $entity->id(),
        'name' => $entity->label(),
        'tenant_id' => $entity->get('tenant_id')->target_id ? (int) $entity->get('tenant_id')->target_id : NULL,
        'steps_count' => count($steps),
        'steps' => $steps,
        'conversion_window_hours' => $entity->getConversionWindow(),
        'created' => (int) $entity->get('created')->value,
        'changed' => (int) $entity->get('changed')->value,
      ];
    }

    return new JsonResponse([
      'funnels' => $funnels,
      'total' => count($funnels),
    ]);
  }

  /**
   * GET /api/v1/analytics/funnels/{funnel_id}/calculate.
   *
   * Calcula las métricas de un funnel para un período y tenant.
   *
   * Query params:
   * - tenant_id (required): ID del tenant.
   * - start_date (optional): Fecha inicio (Y-m-d). Default: -30 days.
   * - end_date (optional): Fecha fin (Y-m-d). Default: today.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   * @param int $funnel_id
   *   ID del funnel.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del cálculo del funnel.
   */
  public function calculate(Request $request, int $funnel_id): JsonResponse {
    $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

    if (!$tenantId) {
      return new JsonResponse([
        'error' => 'Missing required parameter: tenant_id.',
      ], 400);
    }

    $startDate = $request->query->get('start_date', date('Y-m-d', strtotime('-30 days')));
    $endDate = $request->query->get('end_date', date('Y-m-d'));

    $summary = $this->funnelTrackingService->getFunnelSummary(
      $funnel_id,
      (int) $tenantId,
      $startDate,
      $endDate
    );

    if (isset($summary['error'])) {
      return new JsonResponse($summary, 404);
    }

    return new JsonResponse($summary);
  }

  /**
   * POST /api/v1/analytics/funnels.
   *
   * Crea una nueva definición de funnel.
   *
   * JSON body:
   * - name (required): Nombre del funnel.
   * - tenant_id (optional): ID del tenant.
   * - steps (required): Array de pasos [{event_type, label, filters}].
   * - conversion_window_hours (optional): Ventana de conversión. Default: 72.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Funnel creado.
   */
  public function createFunnel(Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (!$content) {
      return new JsonResponse([
        'error' => 'Invalid JSON payload.',
      ], 400);
    }

    if (empty($content['name'])) {
      return new JsonResponse([
        'error' => 'Missing required field: name.',
      ], 400);
    }

    if (empty($content['steps']) || !is_array($content['steps'])) {
      return new JsonResponse([
        'error' => 'Missing or invalid required field: steps (must be an array).',
      ], 400);
    }

    // Validate each step has event_type.
    foreach ($content['steps'] as $index => $step) {
      if (empty($step['event_type'])) {
        return new JsonResponse([
          'error' => sprintf('Step %d is missing required field: event_type.', $index + 1),
        ], 400);
      }
    }

    try {
      $storage = $this->entityTypeManager->getStorage('funnel_definition');

      // Normalize steps to ensure consistent structure.
      $steps = [];
      foreach ($content['steps'] as $step) {
        $steps[] = [
          'event_type' => $step['event_type'],
          'label' => $step['label'] ?? $step['event_type'],
          'filters' => $step['filters'] ?? [],
        ];
      }

      $entity = $storage->create([
        'name' => $content['name'],
        'tenant_id' => $this->tenantContext->getCurrentTenantId() ?? ($content['tenant_id'] ?? NULL),
        'steps' => [$steps],
        'conversion_window_hours' => $content['conversion_window_hours'] ?? 72,
      ]);

      $entity->save();

      return new JsonResponse([
        'success' => TRUE,
        'funnel' => [
          'id' => (int) $entity->id(),
          'name' => $entity->label(),
          'steps_count' => count($steps),
          'conversion_window_hours' => $entity->getConversionWindow(),
          'created' => (int) $entity->get('created')->value,
        ],
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Failed to create funnel: ' . $e->getMessage(),
      ], 500);
    }
  }

}
