<?php

declare(strict_types=1);

namespace Drupal\jaraba_notifications\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio CRUD para notificaciones multi-tenant.
 *
 * Directivas:
 * - TENANT-001: Todas las queries filtran por tenant_id
 * - API-NAMING-001: No usa create() como nombre publico — usa send()
 * - KERNEL-OPTIONAL-AI-001: Sin dependencias AI directas
 */
class NotificationService {

  /**
   * Campos permitidos en operaciones de actualizacion (API-WHITELIST-001).
   */
  private const ALLOWED_UPDATE_FIELDS = ['read_status', 'dismissed'];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
    protected readonly ?object $tenantContext = NULL,
  ) {}

  /**
   * Envia una notificacion a un usuario.
   *
   * @param int $userId
   *   ID del usuario destinatario.
   * @param int $tenantId
   *   ID del tenant.
   * @param string $type
   *   Tipo: system|social|workflow|ai.
   * @param string $title
   *   Titulo corto.
   * @param string $message
   *   Mensaje descriptivo.
   * @param string $link
   *   URL de accion (opcional).
   *
   * @return \Drupal\jaraba_notifications\Entity\Notification|null
   *   La notificacion creada o NULL si falla.
   */
  public function send(int $userId, int $tenantId, string $type, string $title, string $message = '', string $link = ''): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('notification');
      $notification = $storage->create([
        'uid' => $userId,
        'tenant_id' => $tenantId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $link,
        'read_status' => FALSE,
        'dismissed' => FALSE,
      ]);
      $notification->save();

      return $notification;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al crear notificacion: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Lista notificaciones del usuario actual.
   *
   * @param string|null $type
   *   Filtrar por tipo (null = todos).
   * @param int $limit
   *   Numero maximo de resultados.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   Array de entidades Notification.
   */
  public function listForCurrentUser(?string $type = NULL, int $limit = 20, int $offset = 0): array {
    $userId = (int) $this->currentUser->id();
    if ($userId === 0) {
      return [];
    }

    $query = $this->entityTypeManager->getStorage('notification')->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $userId)
      ->condition('dismissed', FALSE)
      ->sort('created', 'DESC')
      ->range($offset, $limit);

    if ($type) {
      $query->condition('type', $type);
    }

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    return $this->entityTypeManager->getStorage('notification')->loadMultiple($ids);
  }

  /**
   * Cuenta notificaciones no leidas del usuario actual.
   */
  public function countUnreadForCurrentUser(): int {
    $userId = (int) $this->currentUser->id();
    if ($userId === 0) {
      return 0;
    }

    $count = $this->entityTypeManager->getStorage('notification')->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $userId)
      ->condition('read_status', FALSE)
      ->condition('dismissed', FALSE)
      ->count()
      ->execute();

    return (int) $count;
  }

  /**
   * Marca una notificacion como leida.
   */
  public function markRead(int $notificationId): bool {
    $notification = $this->entityTypeManager->getStorage('notification')->load($notificationId);
    if (!$notification || (int) $notification->getOwnerId() !== (int) $this->currentUser->id()) {
      return FALSE;
    }

    $notification->markRead();
    $notification->save();

    return TRUE;
  }

  /**
   * Marca todas las notificaciones del usuario actual como leidas.
   */
  public function markAllReadForCurrentUser(): int {
    $userId = (int) $this->currentUser->id();
    $query = $this->entityTypeManager->getStorage('notification')->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $userId)
      ->condition('read_status', FALSE)
      ->condition('dismissed', FALSE);

    $ids = $query->execute();
    if (empty($ids)) {
      return 0;
    }

    $notifications = $this->entityTypeManager->getStorage('notification')->loadMultiple($ids);
    $count = 0;
    foreach ($notifications as $notification) {
      $notification->markRead();
      $notification->save();
      $count++;
    }

    return $count;
  }

  /**
   * Descarta (soft delete) una notificacion.
   */
  public function dismissNotification(int $notificationId): bool {
    $notification = $this->entityTypeManager->getStorage('notification')->load($notificationId);
    if (!$notification || (int) $notification->getOwnerId() !== (int) $this->currentUser->id()) {
      return FALSE;
    }

    $notification->dismiss();
    $notification->save();

    return TRUE;
  }

}
