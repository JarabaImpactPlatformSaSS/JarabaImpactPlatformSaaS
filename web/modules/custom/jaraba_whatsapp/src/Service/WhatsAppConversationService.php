<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whatsapp\Entity\WaConversation;
use Drupal\jaraba_whatsapp\Entity\WaConversationInterface;
use Drupal\jaraba_whatsapp\Entity\WaMessage;
use Psr\Log\LoggerInterface;

/**
 * Manages WhatsApp conversations and messages.
 *
 * TENANT-001: All queries filter by tenant.
 */
class WhatsAppConversationService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WhatsAppApiService $apiService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Gets the active conversation for a phone number.
   *
   * @param string $phone
   *   Phone number.
   * @param int $tenantId
   *   Tenant ID.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaConversationInterface|null
   */
  public function getActiveByPhone(string $phone, int $tenantId): ?WaConversationInterface {
    $storage = $this->entityTypeManager->getStorage('wa_conversation');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('wa_phone', $phone)
      ->condition('tenant_id', $tenantId)
      ->condition('status', [WaConversation::STATUS_ACTIVE, WaConversation::STATUS_INITIATED], 'IN')
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if ($ids === []) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Creates a new conversation.
   *
   * @param string $phone
   *   Phone number.
   * @param int $tenantId
   *   Tenant ID.
   * @param string $status
   *   Initial status.
   * @param array $utmParams
   *   Optional UTM parameters.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaConversationInterface
   */
  public function createConversation(string $phone, int $tenantId, string $status = 'active', array $utmParams = []): WaConversationInterface {
    $storage = $this->entityTypeManager->getStorage('wa_conversation');

    $values = [
      'wa_phone' => $phone,
      'tenant_id' => $tenantId,
      'status' => $status,
      'lead_type' => WaConversation::LEAD_SIN_CLASIFICAR,
      'message_count' => 0,
    ];

    if (isset($utmParams['utm_source'])) {
      $values['utm_source'] = $utmParams['utm_source'];
    }
    if (isset($utmParams['utm_campaign'])) {
      $values['utm_campaign'] = $utmParams['utm_campaign'];
    }
    if (isset($utmParams['utm_content'])) {
      $values['utm_content'] = $utmParams['utm_content'];
    }

    /** @var \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation */
    $conversation = $storage->create($values);
    $conversation->save();

    $this->logger->info('WaConversation created for @phone (tenant: @tid).', [
      '@phone' => $phone,
      '@tid' => $tenantId,
    ]);

    return $conversation;
  }

  /**
   * Creates a conversation from a template send event.
   *
   * @param string $phone
   *   Phone number.
   * @param int $tenantId
   *   Tenant ID.
   * @param array $utmParams
   *   UTM parameters.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaConversationInterface
   */
  public function createFromTemplate(string $phone, int $tenantId, array $utmParams = []): WaConversationInterface {
    return $this->createConversation($phone, $tenantId, WaConversation::STATUS_INITIATED, $utmParams);
  }

  /**
   * Adds a message to a conversation.
   *
   * @param \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation
   *   The conversation.
   * @param array $data
   *   Message data: direction, sender_type, body, etc.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaMessage
   */
  public function addMessage(WaConversationInterface $conversation, array $data): WaMessage {
    $storage = $this->entityTypeManager->getStorage('wa_message');

    $values = [
      'tenant_id' => $conversation->getTenantId(),
      'conversation_id' => $conversation->id(),
      'direction' => $data['direction'] ?? WaMessage::DIRECTION_INBOUND,
      'sender_type' => $data['sender_type'] ?? WaMessage::SENDER_USER,
      'message_type' => $data['message_type'] ?? 'text',
      'body' => $data['body'] ?? '',
      'wa_message_id' => $data['wa_message_id'] ?? NULL,
      'template_name' => $data['template_name'] ?? NULL,
      'ai_model' => $data['ai_model'] ?? NULL,
      'ai_tokens_in' => $data['ai_tokens_in'] ?? NULL,
      'ai_tokens_out' => $data['ai_tokens_out'] ?? NULL,
      'ai_latency_ms' => $data['ai_latency_ms'] ?? NULL,
      'delivery_status' => $data['delivery_status'] ?? 'sent',
    ];

    /** @var \Drupal\jaraba_whatsapp\Entity\WaMessage $message */
    $message = $storage->create($values);
    $message->save();

    // Update conversation counters.
    $count = (int) $conversation->getMessageCount() + 1;
    $conversation->set('message_count', $count);
    $conversation->set('last_message_at', \Drupal::time()->getRequestTime());

    // Activate conversation if it was initiated by system.
    if ($conversation->getStatus() === WaConversation::STATUS_INITIATED
      && ($data['direction'] ?? '') === WaMessage::DIRECTION_INBOUND) {
      $conversation->setStatus(WaConversation::STATUS_ACTIVE);
    }

    $conversation->save();

    return $message;
  }

  /**
   * Gets messages for a conversation.
   *
   * @param \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation
   *   The conversation.
   * @param int $limit
   *   Maximum messages to return.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaMessage[]
   */
  public function getMessages(WaConversationInterface $conversation, int $limit = 50): array {
    $storage = $this->entityTypeManager->getStorage('wa_message');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('conversation_id', $conversation->id())
      ->sort('created', 'ASC')
      ->range(0, $limit)
      ->execute();

    if ($ids === []) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Gets conversation history as plain arrays (for AI context).
   *
   * @param \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation
   *   The conversation.
   *
   * @return array
   *   Array of ['direction' => string, 'body' => string].
   */
  public function getHistory(WaConversationInterface $conversation): array {
    $messages = $this->getMessages($conversation);
    $history = [];

    foreach ($messages as $msg) {
      $history[] = [
        'direction' => $msg->getDirection(),
        'body' => $msg->getBody(),
        'sender_type' => $msg->getSenderType(),
      ];
    }

    return $history;
  }

  /**
   * Gets active conversations for a tenant.
   *
   * @param int $tenantId
   *   Tenant ID.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaConversationInterface[]
   */
  public function getActiveForTenant(int $tenantId): array {
    return $this->getConversationsByStatus($tenantId, [WaConversation::STATUS_ACTIVE]);
  }

  /**
   * Gets escalated conversations for a tenant.
   *
   * @param int $tenantId
   *   Tenant ID.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaConversationInterface[]
   */
  public function getEscalatedForTenant(int $tenantId): array {
    return $this->getConversationsByStatus($tenantId, [WaConversation::STATUS_ESCALATED]);
  }

  /**
   * Gets conversations by status for a tenant.
   *
   * @param int $tenantId
   *   Tenant ID.
   * @param array $statuses
   *   Status values to filter.
   *
   * @return \Drupal\jaraba_whatsapp\Entity\WaConversationInterface[]
   */
  public function getConversationsByStatus(int $tenantId, array $statuses): array {
    $storage = $this->entityTypeManager->getStorage('wa_conversation');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('status', $statuses, 'IN')
      ->sort('last_message_at', 'DESC')
      ->execute();

    if ($ids === []) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Gets stats for the WhatsApp panel dashboard.
   *
   * @param int $tenantId
   *   Tenant ID.
   *
   * @return array{total: int, active: int, escalated: int, closed: int}
   */
  public function getStats(int $tenantId): array {
    $storage = $this->entityTypeManager->getStorage('wa_conversation');

    $total = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->count()
      ->execute();

    $active = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('status', WaConversation::STATUS_ACTIVE)
      ->count()
      ->execute();

    $escalated = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('status', WaConversation::STATUS_ESCALATED)
      ->count()
      ->execute();

    $closed = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('status', WaConversation::STATUS_CLOSED)
      ->count()
      ->execute();

    return [
      'total' => $total,
      'active' => $active,
      'escalated' => $escalated,
      'closed' => $closed,
    ];
  }

}
