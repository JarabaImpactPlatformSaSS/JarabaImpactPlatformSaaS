<?php

declare(strict_types=1);

namespace Drupal\jaraba_connector_sdk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_connector_sdk\Service\ConnectorCertifierService;
use Drupal\jaraba_connector_sdk\Service\MarketplaceService;
use Drupal\jaraba_connector_sdk\Service\RevenueShareService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for the Connector SDK module.
 *
 * Provides REST endpoints for:
 * - Marketplace listing, detail, rating, install/uninstall, configure, status
 * - Developer submission and certification pipeline
 * - Rendered marketplace page
 *
 * All JSON responses use the standard envelope: {success, data, error}.
 */
class ConnectorSdkApiController extends ControllerBase {

  /**
   * Constructs the ConnectorSdkApiController.
   *
   * @param \Drupal\jaraba_connector_sdk\Service\ConnectorCertifierService $certifier
   *   The connector certifier service.
   * @param \Drupal\jaraba_connector_sdk\Service\MarketplaceService $marketplace
   *   The marketplace service.
   * @param \Drupal\jaraba_connector_sdk\Service\RevenueShareService $revenueShare
   *   The revenue share service.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   */
  public function __construct(
    protected readonly ConnectorCertifierService $certifier,
    protected readonly MarketplaceService $marketplace,
    protected readonly RevenueShareService $revenueShare,
    protected readonly TenantContextService $tenantContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_connector_sdk.certifier'),
      $container->get('jaraba_connector_sdk.marketplace'),
      $container->get('jaraba_connector_sdk.revenue_share'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Renders the marketplace page.
   *
   * @return array
   *   Render array for the connector marketplace page.
   */
  public function marketplace(): array {
    $result = $this->marketplace->listCertified(0, 50);

    return [
      '#theme' => 'connector_marketplace',
      '#connectors' => $result['items'],
      '#total' => $result['total'],
      '#attached' => [
        'library' => ['jaraba_connector_sdk/marketplace'],
      ],
    ];
  }

  /**
   * Lists certified connectors (REST).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with connector list.
   */
  public function listCertified(Request $request): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    $page = max(0, (int) $request->query->get('page', '0'));
    $limit = min(100, max(1, (int) $request->query->get('limit', '20')));
    $category = $request->query->get('category');
    $search = $request->query->get('q');

    try {
      $result = $this->marketplace->listCertified($page, $limit, $category, $search);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to list connectors.', 500);
    }
  }

  /**
   * Returns detail for a single connector (REST).
   *
   * @param int $connector_id
   *   The connector entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with connector detail.
   */
  public function getDetail(int $connector_id): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    try {
      $detail = $this->marketplace->getDetail($connector_id);

      if ($detail === NULL) {
        return $this->errorResponse('Connector not found.', 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $detail,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to load connector detail.', 500);
    }
  }

  /**
   * Rates a connector (REST).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param int $connector_id
   *   The connector entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response confirming the rating.
   */
  public function rate(Request $request, int $connector_id): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $rating = (int) ($body['rating'] ?? 0);
    $review = $body['review'] ?? NULL;

    if ($rating < 1 || $rating > 5) {
      return $this->errorResponse('Rating must be between 1 and 5.', 400);
    }

    try {
      $userId = (int) $this->currentUser()->id();
      $success = $this->marketplace->rate($connector_id, $userId, $rating, $review);

      if (!$success) {
        return $this->errorResponse('Failed to record rating.', 400);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'connector_id' => $connector_id,
          'rating' => $rating,
          'message' => 'Rating recorded successfully.',
        ],
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to rate connector.', 500);
    }
  }

  /**
   * Installs a connector for the current tenant (REST).
   *
   * @param int $connector_id
   *   The connector entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with installation result.
   */
  public function installConnector(int $connector_id): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    try {
      $result = $this->marketplace->install($connector_id);

      if (!$result['success']) {
        return $this->errorResponse($result['error'] ?? 'Installation failed.', 409);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
        'error' => NULL,
      ], 201);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to install connector.', 500);
    }
  }

  /**
   * Uninstalls a connector for the current tenant (REST).
   *
   * @param int $connector_id
   *   The connector entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response confirming uninstallation.
   */
  public function uninstallConnector(int $connector_id): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    try {
      $success = $this->marketplace->uninstall($connector_id);

      if (!$success) {
        return $this->errorResponse('Connector not installed or uninstallation failed.', 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'connector_id' => $connector_id,
          'message' => 'Connector uninstalled successfully.',
        ],
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to uninstall connector.', 500);
    }
  }

  /**
   * Configures an installed connector (REST).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param int $connector_id
   *   The connector entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response confirming configuration.
   */
  public function configureConnector(Request $request, int $connector_id): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    $settings = json_decode($request->getContent(), TRUE) ?? [];
    if (empty($settings)) {
      return $this->errorResponse('Configuration settings are required.', 400);
    }

    try {
      $success = $this->marketplace->configure($connector_id, $settings);

      if (!$success) {
        return $this->errorResponse('Connector not installed or configuration failed.', 400);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'connector_id' => $connector_id,
          'message' => 'Connector configured successfully.',
        ],
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to configure connector.', 500);
    }
  }

  /**
   * Gets the status of a connector for the current tenant (REST).
   *
   * @param int $connector_id
   *   The connector entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with connector status.
   */
  public function getConnectorStatus(int $connector_id): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    try {
      $status = $this->marketplace->getStatus($connector_id);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $status,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to get connector status.', 500);
    }
  }

  /**
   * Submits a connector for certification (REST).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with certification submission result.
   */
  public function submitForCertification(Request $request): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $connectorId = (int) ($body['connector_id'] ?? 0);

    if ($connectorId <= 0) {
      return $this->errorResponse('Valid connector_id is required.', 400);
    }

    try {
      $developerId = (int) $this->currentUser()->id();
      $result = $this->certifier->submitForCertification($connectorId, $developerId);

      if (isset($result['error'])) {
        return $this->errorResponse($result['error'], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to submit for certification.', 500);
    }
  }

  /**
   * Certifies a connector (REST, admin only).
   *
   * @param int $connector_id
   *   The connector entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response confirming certification.
   */
  public function certifyConnector(int $connector_id): JsonResponse {
    $tenant = $this->resolveTenant();
    if ($tenant instanceof JsonResponse) {
      return $tenant;
    }

    try {
      $success = $this->certifier->certify($connector_id);

      if (!$success) {
        return $this->errorResponse('Certification failed. Automated tests did not pass.', 400);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'connector_id' => $connector_id,
          'status' => ConnectorCertifierService::STATUS_CERTIFIED,
          'message' => 'Connector certified successfully.',
        ],
        'error' => NULL,
      ]);
    }
    catch (\Exception $e) {
      return $this->errorResponse('Failed to certify connector.', 500);
    }
  }

  /**
   * Resolves and validates the current tenant context.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|true
   *   TRUE if tenant is resolved, or a JsonResponse error.
   */
  protected function resolveTenant(): JsonResponse|bool {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      if ($tenant) {
        return TRUE;
      }
    }
    catch (\Exception $e) {
      // Fall through to error.
    }

    return $this->errorResponse('Tenant context required.', 400);
  }

  /**
   * Creates a standardised error JsonResponse.
   *
   * @param string $message
   *   The error message.
   * @param int $statusCode
   *   The HTTP status code.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The error response.
   */
  protected function errorResponse(string $message, int $statusCode): JsonResponse {
    return new JsonResponse([
      'success' => FALSE,
      'data' => NULL,
      'error' => [
        'code' => 'ERROR',
        'message' => $message,
      ],
    ], $statusCode);
  }

}
