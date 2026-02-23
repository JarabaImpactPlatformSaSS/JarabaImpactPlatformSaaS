<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_messaging\Entity\ConversationParticipantInterface;
use Drupal\jaraba_messaging\Entity\SecureConversationInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio CRUD para conversaciones seguras.
 *
 * PROPÓSITO:
 * Gestiona el ciclo de vida completo de SecureConversation y
 * ConversationParticipant entities. Incluye detección de duplicados
 * para conversaciones directas (1:1).
 */
class ConversationService implements ConversationServiceInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected Connection $database,
    protected MessageAuditServiceInterface $auditService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function create(int $tenantId, int $initiatedBy, array $participantIds, string $title, string $conversationType = 'direct', string $contextType = 'general', ?string $contextId = NULL): SecureConversationInterface {
    // For direct conversations, check for existing.
    if ($conversationType === 'direct' && count($participantIds) === 2) {
      $existing = $this->findExistingDirect($tenantId, $participantIds[0], $participantIds[1]);
      if ($existing) {
        return $existing;
      }
    }

    $storage = $this->entityTypeManager->getStorage('secure_conversation');

    /** @var \Drupal\jaraba_messaging\Entity\SecureConversationInterface $conversation */
    $conversation = $storage->create([
      'tenant_id' => $tenantId,
      'title' => $title,
      'conversation_type' => $conversationType,
      'context_type' => $contextType,
      'context_id' => $contextId,
      'initiated_by' => $initiatedBy,
      'encryption_key_id' => 'tenant_' . $tenantId . '_v1',
      'participant_count' => count($participantIds),
      'status' => SecureConversationInterface::STATUS_ACTIVE,
    ]);
    $conversation->save();

    // Add participants.
    $participantStorage = $this->entityTypeManager->getStorage('conversation_participant');
    foreach ($participantIds as $uid) {
      $role = ($uid === $initiatedBy) ? ConversationParticipantInterface::ROLE_OWNER : ConversationParticipantInterface::ROLE_PARTICIPANT;
      $participant = $participantStorage->create([
        'conversation_id' => $conversation->id(),
        'user_id' => $uid,
        'role' => $role,
        'status' => ConversationParticipantInterface::STATUS_ACTIVE,
      ]);
      $participant->save();
    }

    // Audit log.
    $this->auditService->log(
      (int) $conversation->id(),
      $tenantId,
      'conversation.created',
      NULL,
      ['participants' => $participantIds, 'type' => $conversationType],
    );

    $this->logger->info('Conversation @id created by user @user for tenant @tenant.', [
      '@id' => $conversation->id(),
      '@user' => $initiatedBy,
      '@tenant' => $tenantId,
    ]);

    return $conversation;
  }

  /**
   * {@inheritdoc}
   */
  public function getById(int $id): ?SecureConversationInterface {
    $entity = $this->entityTypeManager->getStorage('secure_conversation')->load($id);
    return $entity instanceof SecureConversationInterface ? $entity : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getByUuid(string $uuid): ?SecureConversationInterface {
    $entities = $this->entityTypeManager->getStorage('secure_conversation')
      ->loadByProperties(['uuid' => $uuid]);
    $entity = reset($entities);
    return $entity instanceof SecureConversationInterface ? $entity : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function listForUser(int $userId, int $tenantId, string $status = 'active', int $limit = 50, int $offset = 0): array {
    // Get conversation IDs where user is active participant.
    $participantIds = $this->entityTypeManager->getStorage('conversation_participant')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userId)
      ->condition('status', ConversationParticipantInterface::STATUS_ACTIVE)
      ->execute();

    if (empty($participantIds)) {
      return [];
    }

    $participants = $this->entityTypeManager->getStorage('conversation_participant')
      ->loadMultiple($participantIds);

    $conversationIds = [];
    foreach ($participants as $participant) {
      $conversationIds[] = $participant->getConversationId();
    }
    $conversationIds = array_unique($conversationIds);

    if (empty($conversationIds)) {
      return [];
    }

    // Load conversations with filters.
    $query = $this->entityTypeManager->getStorage('secure_conversation')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('id', $conversationIds, 'IN')
      ->condition('tenant_id', $tenantId)
      ->condition('status', $status)
      ->sort('last_message_at', 'DESC')
      ->range($offset, $limit);

    $ids = $query->execute();
    return $ids ? array_values($this->entityTypeManager->getStorage('secure_conversation')->loadMultiple($ids)) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function close(int $conversationId): void {
    $conversation = $this->getById($conversationId);
    if (!$conversation) {
      return;
    }

    $conversation->set('status', SecureConversationInterface::STATUS_CLOSED);
    $conversation->save();

    $this->auditService->log(
      $conversationId,
      $conversation->getTenantId() ?? 0,
      'conversation.closed',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function archive(int $conversationId, int $userId): void {
    $conversation = $this->getById($conversationId);
    if (!$conversation) {
      return;
    }

    $conversation->set('status', SecureConversationInterface::STATUS_ARCHIVED);
    $conversation->save();

    $this->auditService->log(
      $conversationId,
      $conversation->getTenantId() ?? 0,
      'conversation.archived',
      NULL,
      ['archived_by' => $userId],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getParticipants(int $conversationId): array {
    $ids = $this->entityTypeManager->getStorage('conversation_participant')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('conversation_id', $conversationId)
      ->condition('status', ConversationParticipantInterface::STATUS_ACTIVE)
      ->execute();

    return $ids ? array_values($this->entityTypeManager->getStorage('conversation_participant')->loadMultiple($ids)) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function addParticipant(int $conversationId, int $userId, string $role = 'participant'): void {
    $storage = $this->entityTypeManager->getStorage('conversation_participant');
    $participant = $storage->create([
      'conversation_id' => $conversationId,
      'user_id' => $userId,
      'role' => $role,
      'status' => ConversationParticipantInterface::STATUS_ACTIVE,
    ]);
    $participant->save();

    // Update participant count on conversation.
    $conversation = $this->getById($conversationId);
    if ($conversation) {
      $count = $conversation->getParticipantCount() + 1;
      $conversation->set('participant_count', $count);
      $conversation->save();

      $this->auditService->log(
        $conversationId,
        $conversation->getTenantId() ?? 0,
        'participant.added',
        $userId,
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function removeParticipant(int $conversationId, int $userId, int $removedBy): void {
    $ids = $this->entityTypeManager->getStorage('conversation_participant')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('conversation_id', $conversationId)
      ->condition('user_id', $userId)
      ->condition('status', ConversationParticipantInterface::STATUS_ACTIVE)
      ->execute();

    if (empty($ids)) {
      return;
    }

    $participant = $this->entityTypeManager->getStorage('conversation_participant')->load(reset($ids));
    if ($participant) {
      $participant->set('status', ConversationParticipantInterface::STATUS_REMOVED);
      $participant->set('removed_by', $removedBy);
      $participant->set('left_at', time());
      $participant->save();

      $conversation = $this->getById($conversationId);
      if ($conversation) {
        $count = max(0, $conversation->getParticipantCount() - 1);
        $conversation->set('participant_count', $count);
        $conversation->save();

        $this->auditService->log(
          $conversationId,
          $conversation->getTenantId() ?? 0,
          'participant.removed',
          $userId,
          ['removed_by' => $removedBy],
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isParticipant(int $conversationId, int $userId): bool {
    $count = $this->entityTypeManager->getStorage('conversation_participant')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('conversation_id', $conversationId)
      ->condition('user_id', $userId)
      ->condition('status', ConversationParticipantInterface::STATUS_ACTIVE)
      ->count()
      ->execute();

    return $count > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function autoCloseInactive(int $days): int {
    $threshold = time() - ($days * 86400);

    $ids = $this->entityTypeManager->getStorage('secure_conversation')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', SecureConversationInterface::STATUS_ACTIVE)
      ->condition('last_message_at', $threshold, '<')
      ->execute();

    $count = 0;
    foreach ($ids as $id) {
      $this->close((int) $id);
      $count++;
    }

    if ($count > 0) {
      $this->logger->info('Auto-closed @count inactive conversations (threshold: @days days).', [
        '@count' => $count,
        '@days' => $days,
      ]);
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function findExistingDirect(int $tenantId, int $userA, int $userB): ?SecureConversationInterface {
    // Find conversations where both users are active participants.
    $queryA = $this->entityTypeManager->getStorage('conversation_participant')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $userA)
      ->condition('status', ConversationParticipantInterface::STATUS_ACTIVE)
      ->execute();

    if (empty($queryA)) {
      return NULL;
    }

    $participantsA = $this->entityTypeManager->getStorage('conversation_participant')->loadMultiple($queryA);
    $convIdsA = [];
    foreach ($participantsA as $p) {
      $convIdsA[] = $p->getConversationId();
    }

    $queryB = $this->entityTypeManager->getStorage('conversation_participant')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('conversation_id', $convIdsA, 'IN')
      ->condition('user_id', $userB)
      ->condition('status', ConversationParticipantInterface::STATUS_ACTIVE)
      ->execute();

    if (empty($queryB)) {
      return NULL;
    }

    $participantsB = $this->entityTypeManager->getStorage('conversation_participant')->loadMultiple($queryB);
    foreach ($participantsB as $p) {
      $conversation = $this->getById($p->getConversationId());
      if ($conversation
        && $conversation->getTenantId() === $tenantId
        && $conversation->getConversationType() === 'direct'
        && $conversation->isActive()) {
        return $conversation;
      }
    }

    return NULL;
  }

}
