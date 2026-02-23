<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_messaging\Entity\SecureConversationInterface;
use Psr\Log\LoggerInterface;

/**
 * Puente de notificaciones hacia el módulo doc 98 (Notificaciones Multicanal).
 *
 * PROPÓSITO:
 * Cuando un mensaje es enviado y el destinatario está offline,
 * este servicio dispara una notificación vía el sistema de
 * notificaciones de la plataforma (email, push, SMS).
 */
class NotificationBridgeService {

  public function __construct(
    protected ConversationServiceInterface $conversationService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Notifies offline participants of a new message.
   *
   * @param \Drupal\jaraba_messaging\Entity\SecureConversationInterface $conversation
   *   The conversation entity.
   * @param int $senderId
   *   The sender user ID.
   * @param string $messagePreview
   *   Preview text of the message (plaintext, truncated).
   */
  public function notifyOfflineParticipants(SecureConversationInterface $conversation, int $senderId, string $messagePreview): void {
    $participants = $this->conversationService->getParticipants((int) $conversation->id());

    foreach ($participants as $participant) {
      if ($participant->getUserId() === $senderId) {
        continue;
      }

      if ($participant->get('is_muted')->value) {
        continue;
      }

      $notificationPref = $participant->get('notification_pref')->value ?? 'all';
      if ($notificationPref === 'none') {
        continue;
      }

      // Queue notification via doc 98 if available.
      if (\Drupal::hasService('jaraba_notifications.notification')) {
        try {
          $notificationService = \Drupal::service('jaraba_notifications.notification');
          $sender = $this->entityTypeManager->getStorage('user')->load($senderId);
          $senderName = $sender ? $sender->getDisplayName() : 'Unknown';

          $notificationService->send([
            'recipient_uid' => $participant->getUserId(),
            'type' => 'messaging_new_message',
            'title' => t('New message from @name', ['@name' => $senderName]),
            'body' => mb_substr($messagePreview, 0, 160),
            'url' => '/messaging',
            'data' => [
              'conversation_id' => $conversation->uuid(),
              'sender_id' => $senderId,
            ],
          ]);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Failed to send notification to user @uid: @error', [
            '@uid' => $participant->getUserId(),
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }
  }

}
