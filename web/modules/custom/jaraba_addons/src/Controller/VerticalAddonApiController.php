<?php

declare(strict_types=1);

namespace Drupal\jaraba_addons\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_addons\Service\TenantVerticalService;
use Drupal\jaraba_addons\Service\VerticalAddonBillingService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * API REST para verticales componibles.
 *
 * ESTRUCTURA:
 * Endpoints especializados para la gestion de verticales como add-ons.
 * Separado de AddonApiController para mantener responsabilidades claras.
 *
 * LOGICA:
 * - listActiveVerticals: GET /api/v1/addons/verticals/active
 * - listAvailableVerticals: GET /api/v1/addons/verticals/available
 * - activate: POST /api/v1/addons/verticals/{addon_id}/activate
 * - deactivate: POST /api/v1/addons/verticals/subscriptions/{subscription_id}/deactivate
 *
 * RELACIONES:
 * - VerticalAddonApiController -> TenantVerticalService
 * - VerticalAddonApiController -> VerticalAddonBillingService
 * - VerticalAddonApiController <- jaraba_addons.routing.yml
 */
class VerticalAddonApiController extends ControllerBase {

  protected $tenantContext;

  public function __construct(
    protected TenantVerticalService $tenantVerticalService,
    protected VerticalAddonBillingService $verticalBilling,
    protected RequestStack $requestStack,
    $tenantContext,
  ) {
    $this->tenantContext = $tenantContext;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $tenantContext = NULL;
    try {
      $tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    }
    catch (\Exception) {
    }

    return new static(
      $container->get('jaraba_addons.tenant_vertical'),
      $container->get('jaraba_addons.vertical_billing'),
      $container->get('request_stack'),
      $tenantContext,
    );
  }

  /**
   * GET /api/v1/addons/verticals/active — Verticales activos del tenant.
   */
  public function listActiveVerticals(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return $this->errorResponse('Contexto de tenant no encontrado.', 403);
    }

    try {
      $verticals = $this->tenantVerticalService->getActiveVerticals($tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => array_values($verticals),
        'meta' => [
          'total' => count($verticals),
          'tenant_id' => $tenantId,
        ],
        'errors' => [],
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error cargando verticales activos.', 500);
    }
  }

  /**
   * GET /api/v1/addons/verticals/available — Verticales disponibles.
   */
  public function listAvailableVerticals(): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return $this->errorResponse('Contexto de tenant no encontrado.', 403);
    }

    try {
      $available = $this->tenantVerticalService->getAvailableVerticals($tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => array_values($available),
        'meta' => [
          'total' => count($available),
          'tenant_id' => $tenantId,
        ],
        'errors' => [],
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error cargando verticales disponibles.', 500);
    }
  }

  /**
   * POST /api/v1/addons/verticals/{addon_id}/activate — Activa vertical.
   */
  public function activate(int $addon_id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return $this->errorResponse('Contexto de tenant no encontrado.', 403);
    }

    $request = $this->requestStack->getCurrentRequest();
    $body = [];
    if ($request) {
      $content = $request->getContent();
      if ($content) {
        $body = json_decode($content, TRUE) ?: [];
      }
    }
    $billingCycle = $body['billing_cycle'] ?? 'monthly';

    if (!in_array($billingCycle, ['monthly', 'yearly'])) {
      return $this->errorResponse('Ciclo de facturacion invalido. Use "monthly" o "yearly".', 422);
    }

    try {
      $result = $this->verticalBilling->activateVerticalAddon($addon_id, $tenantId, $billingCycle);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
        'meta' => ['message' => 'Vertical activado correctamente.'],
        'errors' => [],
      ], 201);
    }
    catch (\RuntimeException $e) {
      return $this->errorResponse($e->getMessage(), 422);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error activando el vertical.', 500);
    }
  }

  /**
   * POST /api/v1/addons/verticals/subscriptions/{subscription_id}/deactivate.
   */
  public function deactivate(int $subscription_id): JsonResponse {
    $tenantId = $this->getCurrentTenantId();
    if (!$tenantId) {
      return $this->errorResponse('Contexto de tenant no encontrado.', 403);
    }

    try {
      $result = $this->verticalBilling->deactivateVerticalAddon($subscription_id, $tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
        'meta' => ['message' => 'Vertical desactivado correctamente.'],
        'errors' => [],
      ]);
    }
    catch (\RuntimeException $e) {
      return $this->errorResponse($e->getMessage(), 422);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Error desactivando el vertical.', 500);
    }
  }

  /**
   *
   */
  protected function getCurrentTenantId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Exception) {
      return NULL;
    }
  }

  /**
   *
   */
  protected function errorResponse(string $message, int $statusCode): JsonResponse {
    return new JsonResponse([
      'data' => NULL,
      'meta' => [],
      'errors' => [['status' => $statusCode, 'message' => $message]],
    ], $statusCode);
  }

}
