<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Service\DeepLinkResolverService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for deep link resolution.
 *
 * Translates deep link URIs into routes and universal (HTTPS) links.
 * All responses use the standard JSON envelope {success, data, error} (CONS-N08).
 * Tenant validation on every request (CONS-N10).
 */
class DeepLinkApiController extends ControllerBase {

  /**
   * Constructs a DeepLinkApiController.
   *
   * @param \Drupal\jaraba_mobile\Service\DeepLinkResolverService $deepLinkResolver
   *   The deep link resolver service.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   */
  public function __construct(
    protected readonly DeepLinkResolverService $deepLinkResolver,
    protected readonly TenantContextService $tenantContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_mobile.deep_link_resolver'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Resolves a deep link URI to a route and web URL.
   *
   * GET /api/v1/mobile/deeplink/resolve?deep_link=jaraba://jobs/123
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with resolved deep link data.
   */
  public function resolve(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $deepLink = $request->query->get('deep_link', '');

    if (empty($deepLink)) {
      // AUDIT-CONS-N08: Standardized JSON envelope.
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Query parameter deep_link is required.'],
      ], 400);
    }

    try {
      $resolved = $this->deepLinkResolver->resolve($deepLink);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $resolved,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Failed to resolve deep link.'],
      ], 500);
    }
  }

  /**
   * Validates tenant context for the current request (CONS-N10).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|null
   *   Error response if tenant is not resolved, NULL if valid.
   */
  protected function validateTenant(): ?JsonResponse {
    $tenantId = (int) $this->tenantContext->getCurrentTenantId();
    if ($tenantId <= 0) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Tenant not resolved.'],
      ], 403);
    }
    return NULL;
  }

}
