<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Service\DeepLinkResolverService;
use Drupal\jaraba_mobile\Service\DeviceRegistryService;
use Drupal\jaraba_mobile\Service\PushSenderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for mobile app backend operations.
 *
 * Provides RESTful endpoints for device registration, push notifications,
 * and deep link resolution. All responses use the standard JSON envelope
 * {success, data, error} (CONS-N08). All paths include /api/v1/ (CONS-N07).
 * Tenant validation on every request (CONS-N10).
 */
class MobileApiController extends ControllerBase {

  /**
   * Constructs a MobileApiController.
   *
   * @param \Drupal\jaraba_mobile\Service\DeviceRegistryService $deviceRegistry
   *   The device registry service.
   * @param \Drupal\jaraba_mobile\Service\PushSenderService $pushSender
   *   The push sender service.
   * @param \Drupal\jaraba_mobile\Service\DeepLinkResolverService $deepLinkResolver
   *   The deep link resolver service.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy.
   */
  public function __construct(
    protected readonly DeviceRegistryService $deviceRegistry,
    protected readonly PushSenderService $pushSender,
    protected readonly DeepLinkResolverService $deepLinkResolver,
    protected readonly TenantContextService $tenantContext,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_mobile.device_registry'),
      $container->get('jaraba_mobile.push_sender'),
      $container->get('jaraba_mobile.deep_link_resolver'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('current_user'),
    );
  }

  /**
   * POST /api/v1/mobile/devices/register
   *
   * Registers a mobile device for push notifications.
   *
   * JSON body: device_token (required), platform (required),
   * os_version, app_version, device_model.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with device data.
   */
  public function registerDevice(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $data = json_decode((string) $request->getContent(), TRUE);

    if (empty($data['device_token']) || empty($data['platform'])) {
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
   * DELETE /api/v1/mobile/devices/unregister
   *
   * Unregisters (soft deletes) a mobile device.
   *
   * JSON body: device_token (required).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function unregisterDevice(Request $request): JsonResponse {
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
   * PUT /api/v1/mobile/devices/token
   *
   * Updates a device token (FCM token rotation).
   *
   * JSON body: old_token (required), new_token (required).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
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
   * GET /api/v1/mobile/devices
   *
   * Returns all active devices for the current user.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with device list.
   */
  public function listDevices(): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    try {
      $devices = $this->deviceRegistry->getDevices();
      $data = [];

      foreach ($devices as $device) {
        $data[] = [
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
        'data' => $data,
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
   * POST /api/v1/mobile/push/send
   *
   * Sends a push notification to a single recipient.
   *
   * JSON body: recipient_id (required), title (required), body (required),
   * channel, priority, deep_link, data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with notification data.
   */
  public function sendPush(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $data = json_decode((string) $request->getContent(), TRUE);

    if (empty($data['recipient_id']) || empty($data['title']) || empty($data['body'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Fields recipient_id, title, and body are required.'],
      ], 400);
    }

    try {
      $notification = $this->pushSender->send(
        (int) $data['recipient_id'],
        $data['title'],
        $data['body'],
        $data['channel'] ?? 'general',
        [
          'priority' => $data['priority'] ?? 'high',
          'deep_link' => $data['deep_link'] ?? '',
          'data' => $data['data'] ?? [],
        ]
      );

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'id' => (int) $notification->id(),
          'status' => $notification->get('status')->value,
          'channel' => $notification->get('channel')->value,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Failed to send notification.'],
      ], 500);
    }
  }

  /**
   * POST /api/v1/mobile/push/batch
   *
   * Sends a push notification to multiple recipients.
   *
   * JSON body: recipient_ids (array, required), title (required),
   * body (required), channel.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with notification results.
   */
  public function sendBatchPush(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $data = json_decode((string) $request->getContent(), TRUE);

    if (empty($data['recipient_ids']) || !is_array($data['recipient_ids'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Field recipient_ids must be a non-empty array.'],
      ], 400);
    }

    if (empty($data['title']) || empty($data['body'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Fields title and body are required.'],
      ], 400);
    }

    try {
      $notifications = $this->pushSender->sendBatch(
        $data['recipient_ids'],
        $data['title'],
        $data['body'],
        $data['channel'] ?? 'general',
        [
          'priority' => $data['priority'] ?? 'high',
          'deep_link' => $data['deep_link'] ?? '',
          'data' => $data['data'] ?? [],
        ]
      );

      $results = [];
      foreach ($notifications as $notification) {
        $results[] = [
          'id' => (int) $notification->id(),
          'recipient_id' => (int) $notification->get('recipient_id')->value,
          'status' => $notification->get('status')->value,
        ];
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'sent' => count($results),
          'notifications' => $results,
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Failed to send batch notifications.'],
      ], 500);
    }
  }

  /**
   * GET /api/v1/mobile/push/history
   *
   * Returns push notification history for the current tenant.
   * Supports pagination (page, limit query params) and channel filter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with paginated notification history.
   */
  public function pushHistory(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $page = max(1, (int) $request->query->get('page', '1'));
    $limit = min(100, max(1, (int) $request->query->get('limit', '20')));
    $channel = $request->query->get('channel', '');

    try {
      $tenantId = (int) $this->tenantContext->getCurrentTenantId();
      $storage = $this->entityTypeManager()->getStorage('push_notification');

      // Build count query.
      $countQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId);

      if (!empty($channel)) {
        $countQuery->condition('channel', $channel);
      }

      $total = (int) $countQuery->count()->execute();

      // Build paginated query.
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(($page - 1) * $limit, $limit);

      if (!empty($channel)) {
        $query->condition('channel', $channel);
      }

      $ids = $query->execute();
      $entities = !empty($ids) ? $storage->loadMultiple($ids) : [];

      $data = [];
      foreach ($entities as $notification) {
        $data[] = [
          'id' => (int) $notification->id(),
          'recipient_id' => (int) $notification->get('recipient_id')->value,
          'title' => $notification->get('title')->value,
          'body' => $notification->get('body')->value,
          'channel' => $notification->get('channel')->value,
          'status' => $notification->get('status')->value,
          'priority' => $notification->get('priority')->value ?? 'high',
          'deep_link' => $notification->get('deep_link')->value ?? '',
          'created' => (int) $notification->get('created')->value,
        ];
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'items' => $data,
          'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => (int) ceil($total / $limit),
          ],
        ],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['message' => 'Failed to retrieve push history.'],
      ], 500);
    }
  }

  /**
   * GET /api/v1/mobile/deeplink/resolve
   *
   * Resolves a deep link URI to a route and web URL.
   *
   * Query param: deep_link (required).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response with resolved link data.
   */
  public function resolveDeepLink(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $deepLink = $request->query->get('deep_link', '');

    if (empty($deepLink)) {
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
   * Validates tenant context for the current request.
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
