<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_messaging\Entity\SecureConversationInterface;
use Psr\Log\LoggerInterface;

/**
 * Orquestador central del sistema de mensajería.
 *
 * PROPÓSITO:
 * Coordina ConversationService, MessageService, EncryptionService,
 * AuditService y NotificationBridgeService para flujos completos
 * de envío/recepción de mensajes.
 */
class MessagingService implements MessagingServiceInterface {

  public function __construct(
    protected ConversationServiceInterface $conversationService,
    protected MessageServiceInterface $messageService,
    protected MessageEncryptionServiceInterface $encryptionService,
    protected MessageAuditServiceInterface $auditService,
    protected NotificationBridgeService $notificationBridge,
    protected ConfigFactoryInterface $configFactory,
    protected AccountProxyInterface $currentUser,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function sendMessage(string $conversationUuid, string $body, string $messageType = 'text', ?int $replyToId = NULL, array $attachmentIds = []): array {
    $conversation = $this->conversationService->getByUuid($conversationUuid);
    if (!$conversation) {
      throw new \RuntimeException('Conversation not found.');
    }

    $senderId = (int) $this->currentUser->id();
    $tenantId = $conversation->getTenantId();

    $message = $this->messageService->send(
      (int) $conversation->id(),
      $senderId,
      $tenantId,
      $body,
      $messageType,
      $replyToId,
      $attachmentIds,
    );

    // Update conversation metadata.
    $conversation->set('last_message_at', $message->createdAt);
    $conversation->set('last_message_preview', mb_substr($body, 0, 100));
    $conversation->set('last_message_sender_id', $senderId);
    $conversation->set('message_count', $conversation->getMessageCount() + 1);
    $conversation->save();

    // Increment unread counts for other participants.
    $this->incrementUnreadCounts((int) $conversation->id(), $senderId);

    // Trigger offline notifications.
    try {
      $this->notificationBridge->notifyOfflineParticipants(
        $conversation,
        $senderId,
        $body,
      );
    }
    catch (\Throwable $e) {
      $this->logger->warning('Failed to send offline notifications: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $message->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function createConversation(array $participantIds, string $title, string $conversationType = 'direct', string $contextType = 'general', ?string $contextId = NULL): array {
    $initiatedBy = (int) $this->currentUser->id();

    // Ensure initiator is in participants.
    if (!in_array($initiatedBy, $participantIds)) {
      $participantIds[] = $initiatedBy;
    }

    // Get tenant ID from current context.
    $tenantId = $this->getTenantId();

    $conversation = $this->conversationService->create(
      $tenantId,
      $initiatedBy,
      $participantIds,
      $title,
      $conversationType,
      $contextType,
      $contextId,
    );

    return $this->serializeConversation($conversation);
  }

  /**
   * {@inheritdoc}
   */
  public function getConversations(string $status = 'active', int $limit = 50, int $offset = 0): array {
    $userId = (int) $this->currentUser->id();
    $tenantId = $this->getTenantId();

    $conversations = $this->conversationService->listForUser(
      $userId,
      $tenantId,
      $status,
      $limit,
      $offset,
    );

    return array_map([$this, 'serializeConversation'], $conversations);
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(string $conversationUuid, int $limit = 25, ?int $beforeId = NULL): array {
    $conversation = $this->conversationService->getByUuid($conversationUuid);
    if (!$conversation) {
      throw new \RuntimeException('Conversation not found.');
    }

    $messages = $this->messageService->getMessages(
      (int) $conversation->id(),
      $conversation->getTenantId(),
      $limit,
      $beforeId,
    );

    return array_map(fn($msg) => $msg->toArray(), $messages);
  }

  /**
   * {@inheritdoc}
   */
  public function markConversationRead(string $conversationUuid): int {
    $conversation = $this->conversationService->getByUuid($conversationUuid);
    if (!$conversation) {
      return 0;
    }

    $userId = (int) $this->currentUser->id();
    return $this->messageService->markRead((int) $conversation->id(), $userId);
  }

  /**
   * Serializes a conversation entity to array.
   */
  protected function serializeConversation(SecureConversationInterface $conversation): array {
    return [
      'id' => (int) $conversation->id(),
      'uuid' => $conversation->uuid(),
      'title' => $conversation->getTitle(),
      'conversation_type' => $conversation->getConversationType(),
      'context_type' => $conversation->get('context_type')->value ?? 'general',
      'context_id' => $conversation->get('context_id')->value,
      'status' => $conversation->getStatus(),
      'is_confidential' => $conversation->isConfidential(),
      'message_count' => $conversation->getMessageCount(),
      'participant_count' => $conversation->getParticipantCount(),
      'last_message_at' => $conversation->getLastMessageAt(),
      'last_message_preview' => $conversation->get('last_message_preview')->value,
      'created' => $conversation->get('created')->value,
    ];
  }

  /**
   * Increments unread counts for all participants except the sender.
   */
  protected function incrementUnreadCounts(int $conversationId, int $excludeUserId): void {
    $participants = $this->conversationService->getParticipants($conversationId);
    foreach ($participants as $participant) {
      if ($participant->getUserId() !== $excludeUserId) {
        $current = $participant->getUnreadCount();
        $participant->set('unread_count', $current + 1);
        $participant->save();
      }
    }
  }

  /**
   * Gets the current tenant ID.
   */
  protected function getTenantId(): int {
    // Try ecosistema_jaraba_core tenant context if available.
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
