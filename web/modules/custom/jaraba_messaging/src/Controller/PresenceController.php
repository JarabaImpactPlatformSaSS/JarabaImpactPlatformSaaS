<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_messaging\Service\PresenceServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador REST API para presencia en tiempo real.
 *
 * Endpoints:
 * - GET  /api/v1/messaging/presence           - Estado online de usuarios del tenant.
 * - POST /api/v1/messaging/presence/heartbeat  - Actualiza presencia del usuario actual.
 *
 * Todas las respuestas usan envelope: {success, data, meta}.
 */
class PresenceController extends ControllerBase {

  public function __construct(
    protected PresenceServiceInterface $presenceService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_messaging.presence'),
    );
  }

  /**
   * GET /api/v1/messaging/presence - Returns online users for the current tenant.
   *
   * Optional query parameters:
   * - user_ids: comma-separated user IDs to check specific users.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with online user data.
   */
  public function getStatus(Request $request): JsonResponse {
    try {
      $tenantId = $this->getTenantId();

      // Check if specific user IDs were requested.
      $userIdsParam = $request->query->get('user_ids', '');
      if (!empty($userIdsParam)) {
        $requestedIds = array_map('intval', explode(',', $userIdsParam));
        $statuses = [];
        foreach ($requestedIds as $uid) {
          $statuses[] = [
            'user_id' => $uid,
            'is_online' => $this->presenceService->isOnline($uid),
          ];
        }

        return new JsonResponse([
          'success' => TRUE,
          'data' => $statuses,
          'meta' => [
            'tenant_id' => $tenantId,
            'timestamp' => time(),
          ],
        ]);
      }

      // Return all online users for the tenant.
      $onlineUserIds = $this->presenceService->getOnlineUsers($tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'online_users' => $onlineUserIds,
          'online_count' => count($onlineUserIds),
        ],
        'meta' => [
          'tenant_id' => $tenantId,
          'timestamp' => time(),
        ],
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

  /**
   * POST /api/v1/messaging/presence/heartbeat - Updates presence for current user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response confirming the heartbeat.
   */
  public function heartbeat(Request $request): JsonResponse {
    try {
      $userId = (int) $this->currentUser()->id();

      if ($userId === 0) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'UNAUTHORIZED', 'message' => 'Authentication required.'],
        ], 401);
      }

      $tenantId = $this->getTenantId();
      $this->presenceService->setOnline($userId, $tenantId);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'user_id' => $userId,
          'status' => 'online',
        ],
        'meta' => [
          'tenant_id' => $tenantId,
          'timestamp' => time(),
        ],
      ]);
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'ERROR', 'message' => $e->getMessage()],
      ], 500);
    }
  }

  /**
   * Gets the current tenant ID from the platform context.
   *
   * @return int
   *   The tenant ID, or 0 if not available.
   */
  protected function getTenantId(): int {
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
      $tenantId = $tenantContext->getCurrentTenantId();
      if ($tenantId) {
        return (int) $tenantId;
      }
    }
    return 0;
  }

}
