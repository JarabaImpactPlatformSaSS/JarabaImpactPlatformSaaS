<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de notificaciones de resenas.
 *
 * Eventos: review_created, review_approved, review_responded,
 * invitation_reminder.
 *
 * B-11: Review Notification System.
 */
class ReviewNotificationService {

  /**
   * Campo de target por tipo.
   */
  private const TARGET_OWNER_MAP = [
    'comercio_review' => ['field' => 'entity_id_ref', 'owner_entity' => 'merchant_profile'],
    'review_agro' => ['field' => 'target_entity_id', 'owner_entity' => 'producer_profile'],
    'review_servicios' => ['field' => 'provider_id', 'owner_entity' => 'provider_profile'],
    'course_review' => ['field' => 'course_id', 'owner_entity' => 'lms_course'],
    'session_review' => ['field' => 'session_id', 'owner_entity' => 'mentoring_session'],
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly MailManagerInterface $mailManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Notifica que se creo una nueva resena.
   *
   * Notifica al propietario de la entidad target.
   */
  public function notifyReviewCreated(EntityInterface $reviewEntity): void {
    $entityType = $reviewEntity->getEntityTypeId();
    $targetInfo = self::TARGET_OWNER_MAP[$entityType] ?? NULL;
    if ($targetInfo === NULL) {
      return;
    }

    try {
      $targetId = $this->resolveTargetId($reviewEntity, $targetInfo['field']);
      if ($targetId === 0) {
        return;
      }

      $target = $this->entityTypeManager->getStorage($targetInfo['owner_entity'])->load($targetId);
      if ($target === NULL) {
        return;
      }

      $ownerUid = $target->hasField('uid') ? (int) ($target->get('uid')->target_id ?? 0) : 0;
      if ($ownerUid === 0) {
        return;
      }

      $owner = $this->entityTypeManager->getStorage('user')->load($ownerUid);
      if ($owner === NULL || !$owner->getEmail()) {
        return;
      }

      $rating = method_exists($reviewEntity, 'getReviewRating') ? $reviewEntity->getReviewRating() : 0;
      $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

      $this->sendMail($owner->getEmail(), 'review_created', [
        'owner_name' => $owner->getDisplayName(),
        'target_label' => $target->label() ?? '',
        'rating' => $rating,
        'stars' => $stars,
        'review_type' => $entityType,
        'review_id' => $reviewEntity->id(),
      ], $owner);
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to notify review_created for @type @id: @msg', [
        '@type' => $entityType,
        '@id' => $reviewEntity->id(),
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Notifica que una resena fue aprobada.
   *
   * Notifica al autor de la resena.
   */
  public function notifyReviewApproved(EntityInterface $reviewEntity): void {
    try {
      $uid = $reviewEntity->hasField('uid') ? (int) ($reviewEntity->get('uid')->target_id ?? 0) : 0;
      if ($uid === 0) {
        return;
      }

      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if ($user === NULL || !$user->getEmail()) {
        return;
      }

      $this->sendMail($user->getEmail(), 'review_approved', [
        'user_name' => $user->getDisplayName(),
        'review_type' => $reviewEntity->getEntityTypeId(),
        'review_id' => $reviewEntity->id(),
      ], $user);
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to notify review_approved: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Notifica que el propietario respondio a una resena.
   *
   * Notifica al autor de la resena.
   */
  public function notifyOwnerResponded(EntityInterface $reviewEntity, string $responseText): void {
    try {
      $uid = $reviewEntity->hasField('uid') ? (int) ($reviewEntity->get('uid')->target_id ?? 0) : 0;
      if ($uid === 0) {
        return;
      }

      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if ($user === NULL || !$user->getEmail()) {
        return;
      }

      $this->sendMail($user->getEmail(), 'review_responded', [
        'user_name' => $user->getDisplayName(),
        'response_preview' => mb_substr($responseText, 0, 200),
        'review_type' => $reviewEntity->getEntityTypeId(),
        'review_id' => $reviewEntity->id(),
      ], $user);
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to notify review_responded: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Envia un email de notificacion.
   *
   * @param string $to
   *   Direccion de email.
   * @param string $key
   *   Clave de mail (se prefija con 'review_notification_').
   * @param array $params
   *   Parametros del mail.
   * @param \Drupal\user\UserInterface|null $user
   *   El usuario destinatario (para resolver langcode).
   */
  protected function sendMail(string $to, string $key, array $params, ?UserInterface $user = NULL): void {
    $langcode = $user ? $user->getPreferredLangcode() : 'es';
    $this->mailManager->mail(
      'ecosistema_jaraba_core',
      'review_notification_' . $key,
      $to,
      $langcode,
      $params,
    );
  }

  /**
   * Resuelve el target ID de una resena.
   */
  protected function resolveTargetId(EntityInterface $entity, string $field): int {
    if (!$entity->hasField($field) || $entity->get($field)->isEmpty()) {
      return 0;
    }
    $targetId = (int) ($entity->get($field)->target_id ?? 0);
    if ($targetId === 0) {
      $targetId = (int) ($entity->get($field)->value ?? 0);
    }
    return $targetId;
  }

}
