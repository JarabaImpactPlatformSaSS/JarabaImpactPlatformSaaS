<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Service\DeviceRegistryService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for mobile device management.
 *
 * Handles device registration, unregistration, token updates, and listing.
 * All responses use the standard JSON envelope {success, data, error} (CONS-N08).
 * Tenant validation on every request (CONS-N10).
 */
class DeviceApiController extends ControllerBase {

  /**
   * Constructs a DeviceApiController.
   *
   * @param \Drupal\jaraba_mobile\Service\DeviceRegistryService $deviceRegistry
   *   The device registry service.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   */
  public function __construct(
    protected readonly DeviceRegistryService $deviceRegistry,
    protected readonly TenantContextService $tenantContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_mobile.device_registry'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Registers a new device.
   *
   * POST /api/v1/mobile/devices/register
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with the registered device data.
   */
  public function register(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $data = json_decode((string) $request->getContent(), TRUE);

    if (empty($data['device_token']) || empty($data['platform'])) {
      // AUDIT-CONS-N08: Standardized JSON envelope.
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Fields device_token and platform are required.'],
      ], 400);
    }

    $platform = strtolower($data['platform']);
    if (!in_array($platform, ['ios', 'android'], TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Platform must be ios or android.'],
      ], 400);
    }

    try {
      $device = $this->deviceRegistry->register(
        $data['device_token'],
        $platform,
        [
          'os_version' => $data['os_version'] ?? '',
          'app_version' => $data['app_version'] ?? '',
          'device_model' => $data['device_model'] ?? '',
        ]
      );

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $device->id(),
          'device_token' => $data['device_token'],
          'platform' => $platform,
          'is_active' => TRUE,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Failed to register device.'],
      ], 500);
    }
  }

  /**
   * Unregisters a device.
   *
   * DELETE /api/v1/mobile/devices/unregister
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function unregister(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $data = json_decode((string) $request->getContent(), TRUE);

    if (empty($data['device_token'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Field device_token is required.'],
      ], 400);
    }

    try {
      $result = $this->deviceRegistry->unregister($data['device_token']);

      if (!$result) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['message' => 'Device not found.'],
        ], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['unregistered' => TRUE],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Failed to unregister device.'],
      ], 500);
    }
  }

  /**
   * Updates the push token for a device.
   *
   * PUT /api/v1/mobile/devices/token
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function updateToken(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $data = json_decode((string) $request->getContent(), TRUE);

    if (empty($data['old_token']) || empty($data['new_token'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Fields old_token and new_token are required.'],
      ], 400);
    }

    try {
      $result = $this->deviceRegistry->updateToken($data['old_token'], $data['new_token']);

      if (!$result) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['message' => 'Device with old_token not found.'],
        ], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => ['token_updated' => TRUE],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Failed to update token.'],
      ], 500);
    }
  }

  /**
   * Lists all active devices for the current user.
   *
   * GET /api/v1/mobile/devices
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with device list.
   */
  public function list(): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    try {
      $devices = $this->deviceRegistry->getDevices();
      $result = [];

      foreach ($devices as $device) {
        $result[] = [
          'id' => (int) $device->id(),
          'device_token' => $device->get('device_token')->value,
          'platform' => $device->get('platform')->value,
          'os_version' => $device->get('os_version')->value ?? '',
          'app_version' => $device->get('app_version')->value ?? '',
          'device_model' => $device->get('device_model')->value ?? '',
          'last_active' => (int) ($device->get('last_active')->value ?? 0),
          'is_active' => (bool) $device->get('is_active')->value,
        ];
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => $result,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Failed to retrieve devices.'],
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
