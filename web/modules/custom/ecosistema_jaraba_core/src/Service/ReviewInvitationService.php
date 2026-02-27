<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * Envia invitaciones para dejar resenas tras transacciones completadas.
 *
 * Encola invitaciones con delay configurable y las procesa via cron.
 * Verifica que la transaccion siga completada y que el usuario no haya
 * dejado ya una resena antes de enviar.
 *
 * REV-PHASE3: Servicio 4 de 5 transversales.
 */
class ReviewInvitationService {

  /**
   * Nombre de la cola de invitaciones.
   */
  private const QUEUE_NAME = 'review_invitation_queue';

  /**
   * Mapeo de vertical a tipo de entidad de resena.
   */
  private const VERTICAL_REVIEW_MAP = [
    'comercioconecta' => 'comercio_review',
    'agroconecta' => 'review_agro',
    'serviciosconecta' => 'review_servicios',
    'formacion' => 'course_review',
    'mentoring' => 'session_review',
  ];

  /**
   * Mapeo de vertical a campo de target en la entidad de resena.
   */
  private const VERTICAL_TARGET_FIELD_MAP = [
    'comercioconecta' => 'entity_id_ref',
    'agroconecta' => 'target_entity_id',
    'serviciosconecta' => 'provider_id',
    'formacion' => 'course_id',
    'mentoring' => 'session_id',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly QueueFactory $queueFactory,
    protected readonly LoggerInterface $logger,
    protected readonly MailManagerInterface $mailManager,
  ) {}

  /**
   * Encola una invitacion para enviar despues de un delay.
   *
   * @param string $vertical
   *   Vertical canonica (e.g., 'comercioconecta').
   * @param int $transactionEntityId
   *   ID de la entidad de transaccion (pedido, reserva, sesion).
   * @param int $userId
   *   ID del usuario que recibira la invitacion.
   * @param int $delayHours
   *   Horas de espera antes de enviar la invitacion.
   */
  public function scheduleInvitation(string $vertical, int $transactionEntityId, int $userId, int $delayHours = 48): void {
    if (!isset(self::VERTICAL_REVIEW_MAP[$vertical])) {
      $this->logger->warning('Unknown vertical @vertical for review invitation.', [
        '@vertical' => $vertical,
      ]);
      return;
    }

    $queue = $this->queueFactory->get(self::QUEUE_NAME);
    $queue->createItem([
      'vertical' => $vertical,
      'transaction_entity_id' => $transactionEntityId,
      'user_id' => $userId,
      'send_after' => time() + ($delayHours * 3600),
      'review_entity_type' => self::VERTICAL_REVIEW_MAP[$vertical],
    ]);

    $this->logger->info('Scheduled review invitation for uid @uid, vertical @vertical, transaction @tid (delay: @hours h).', [
      '@uid' => $userId,
      '@vertical' => $vertical,
      '@tid' => $transactionEntityId,
      '@hours' => $delayHours,
    ]);
  }

  /**
   * Procesa una invitacion encolada.
   *
   * Verifica que la transaccion siga completada y que el usuario no haya
   * dejado ya una resena antes de enviar el email.
   *
   * @param array $data
   *   Datos de la invitacion encolada.
   */
  public function processInvitation(array $data): void {
    // Verificar que haya pasado suficiente tiempo.
    if (time() < ($data['send_after'] ?? 0)) {
      // Reencolar — aun no es hora.
      $queue = $this->queueFactory->get(self::QUEUE_NAME);
      $queue->createItem($data);
      return;
    }

    $userId = $data['user_id'] ?? 0;
    $reviewEntityType = $data['review_entity_type'] ?? '';
    $vertical = $data['vertical'] ?? '';
    $transactionEntityId = $data['transaction_entity_id'] ?? 0;

    // Verificar que el usuario existe.
    $user = $this->entityTypeManager->getStorage('user')->load($userId);
    if ($user === NULL || $user->isBlocked()) {
      return;
    }

    // Verificar que no haya dejado ya una resena.
    $targetField = self::VERTICAL_TARGET_FIELD_MAP[$vertical] ?? NULL;
    if ($targetField && $this->hasUserReviewed($reviewEntityType, $userId, $targetField, $transactionEntityId)) {
      $this->logger->info('User @uid already reviewed @type target @tid. Skipping invitation.', [
        '@uid' => $userId,
        '@type' => $reviewEntityType,
        '@tid' => $transactionEntityId,
      ]);
      return;
    }

    // Enviar email.
    $this->sendInvitationEmail($user, $vertical, $transactionEntityId);
  }

  /**
   * Verifica si un usuario ya ha dejado una resena para un target.
   *
   * @param string $reviewEntityTypeId
   *   Tipo de entidad de resena.
   * @param int $userId
   *   ID del usuario.
   * @param string $targetField
   *   Campo que referencia al target en la entidad de resena.
   * @param int $targetEntityId
   *   ID de la entidad target.
   *
   * @return bool
   *   TRUE si ya existe una resena.
   */
  public function hasUserReviewed(string $reviewEntityTypeId, int $userId, string $targetField, int $targetEntityId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage($reviewEntityTypeId);
      $count = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($targetField, $targetEntityId)
        ->condition('uid', $userId)
        ->count()
        ->execute();

      return ((int) $count) > 0;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * Envia el email de invitacion a dejar una resena.
   */
  protected function sendInvitationEmail(EntityInterface $user, string $vertical, int $transactionEntityId): void {
    $params = [
      'vertical' => $vertical,
      'transaction_entity_id' => $transactionEntityId,
      'user_display_name' => $user->getDisplayName(),
    ];

    $this->mailManager->mail(
      'ecosistema_jaraba_core',
      'review_invitation',
      $user->getEmail(),
      $user->getPreferredLangcode(),
      $params,
    );

    $this->logger->info('Sent review invitation email to uid @uid for @vertical transaction @tid.', [
      '@uid' => $user->id(),
      '@vertical' => $vertical,
      '@tid' => $transactionEntityId,
    ]);
  }

}
