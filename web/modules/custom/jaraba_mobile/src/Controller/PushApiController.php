<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Service\PushSenderService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller for push notification operations.
 *
 * Handles sending, batch sending, and viewing push history.
 * All responses use the standard JSON envelope {success, data, error} (CONS-N08).
 * Tenant validation on every request (CONS-N10).
 */
class PushApiController extends ControllerBase {

  /**
   * Constructs a PushApiController.
   *
   * @param \Drupal\jaraba_mobile\Service\PushSenderService $pushSender
   *   The push sender service.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   */
  public function __construct(
    protected readonly PushSenderService $pushSender,
    protected readonly TenantContextService $tenantContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_mobile.push_sender'),
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * Sends a push notification to a single user.
   *
   * POST /api/v1/mobile/push/send
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with notification data.
   */
  public function send(Request $request): JsonResponse {
    $tenantCheck = $this->validateTenant();
    if ($tenantCheck !== NULL) {
      return $tenantCheck;
    }

    $data = json_decode((string) $request->getContent(), TRUE);

    if (empty($data['recipient_id']) || empty($data['title']) || empty($data['body'])) {
      // AUDIT-CONS-N08: Standardized JSON envelope.
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
   * Sends batch push notifications.
   *
   * POST /api/v1/mobile/push/batch
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with batch results.
   */
  public function batch(Request $request): JsonResponse {
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
   * Returns push notification history.
   *
   * GET /api/v1/mobile/push/history
   *
   * Supports pagination (page, limit) and channel filter.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with paginated notification history.
   */
  public function history(Request $request): JsonResponse {
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
