<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_notifications\Service\NotificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller REST para el centro de notificaciones.
 *
 * Directivas:
 * - CSRF-API-001: Rutas PATCH protegidas con _csrf_request_header_token
 * - INNERHTML-XSS-001: Output sanitizado con Html::escape()
 * - API-WHITELIST-001: Campos permitidos definidos en servicio
 * - TENANT-001: Filtrado por usuario (aislamiento implicito)
 */
class NotificationApiController extends ControllerBase {

  public function __construct(
    protected readonly NotificationService $notificationService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_notifications.notification'),
    );
  }

  /**
   * GET /api/v1/notifications — Lista notificaciones del usuario.
   */
  public function listNotifications(Request $request): JsonResponse {
    $type = $request->query->get('type');
    $limit = min((int) ($request->query->get('limit', 20)), 50);
    $offset = max((int) ($request->query->get('offset', 0)), 0);

    // Validar tipo permitido.
    $allowedTypes = ['system', 'social', 'workflow', 'ai'];
    if ($type && !in_array($type, $allowedTypes, TRUE)) {
      $type = NULL;
    }

    $notifications = $this->notificationService->listForCurrentUser($type, $limit, $offset);

    $items = [];
    foreach ($notifications as $notification) {
      $items[] = [
        'id' => (int) $notification->id(),
        'type' => Html::escape($notification->getNotificationType()),
        'title' => Html::escape($notification->getTitle()),
        'message' => Html::escape($notification->getMessage()),
        'link' => Html::escape($notification->getLink()),
        'read' => $notification->isRead(),
        'created' => $notification->get('created')->value,
      ];
    }

    return new JsonResponse([
      'data' => $items,
      'meta' => [
        'count' => count($items),
        'offset' => $offset,
        'limit' => $limit,
      ],
    ]);
  }

  /**
   * GET /api/v1/notifications/count — Conteo de no leidas.
   */
  public function countUnread(): JsonResponse {
    $count = $this->notificationService->countUnreadForCurrentUser();

    return new JsonResponse([
      'data' => ['unread_count' => $count],
    ]);
  }

  /**
   * PATCH /api/v1/notifications/{notification}/read — Marcar como leida.
   */
  public function markRead(int $notification): JsonResponse {
    $success = $this->notificationService->markRead($notification);

    if (!$success) {
      return new JsonResponse(['error' => 'Notificacion no encontrada'], 404);
    }

    return new JsonResponse(['data' => ['status' => 'read']]);
  }

  /**
   * PATCH /api/v1/notifications/read-all — Marcar todas como leidas.
   */
  public function markAllRead(): JsonResponse {
    $count = $this->notificationService->markAllReadForCurrentUser();

    return new JsonResponse([
      'data' => ['marked_read' => $count],
    ]);
  }

  /**
   * PATCH /api/v1/notifications/{notification}/dismiss — Descartar.
   */
  public function dismiss(int $notification): JsonResponse {
    $success = $this->notificationService->dismissNotification($notification);

    if (!$success) {
      return new JsonResponse(['error' => 'Notificacion no encontrada'], 404);
    }

    return new JsonResponse(['data' => ['status' => 'dismissed']]);
  }

}
