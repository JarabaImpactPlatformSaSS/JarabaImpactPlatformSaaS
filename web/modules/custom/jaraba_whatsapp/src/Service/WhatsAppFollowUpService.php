<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_whatsapp\Entity\WaConversation;
use Drupal\jaraba_whatsapp\Entity\WaMessage;
use Psr\Log\LoggerInterface;

/**
 * Manages automated follow-up messages and inactivity rules.
 */
class WhatsAppFollowUpService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WhatsAppConversationService $conversationService,
    protected WhatsAppTemplateService $templateService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Processes follow-ups for inactive conversations.
   *
   * Called via cron. Sends follow-up templates and closes stale conversations.
   *
   * @param int $tenantId
   *   Tenant ID.
   */
  public function processFollowUps(int $tenantId): void {
    $this->sendFollowUpMessages($tenantId);
    $this->closeInactiveConversations($tenantId);
  }

  /**
   * Sends follow-up templates to conversations inactive for 24h+.
   */
  protected function sendFollowUpMessages(int $tenantId): void {
    $storage = $this->entityTypeManager->getStorage('wa_conversation');
    $cutoff = \Drupal::time()->getRequestTime() - (24 * 3600);

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('status', WaConversation::STATUS_INITIATED)
      ->condition('last_message_at', $cutoff, '<')
      ->condition('message_count', 2, '<')
      ->range(0, 20)
      ->execute();

    foreach ($storage->loadMultiple($ids) as $conversation) {
      /** @var \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation */
      $templateName = match ($conversation->getLeadType()) {
        'negocio' => 'seguimiento_negocio',
        default => 'seguimiento_participante',
      };

      $result = $this->templateService->sendTemplate($templateName, $conversation->getWaPhone());

      if ($result['success'] ?? false) {
        $this->conversationService->addMessage($conversation, [
          'direction' => WaMessage::DIRECTION_OUTBOUND,
          'sender_type' => WaMessage::SENDER_SYSTEM,
          'message_type' => 'template',
          'body' => 'Follow-up: ' . $templateName,
          'template_name' => $templateName,
          'wa_message_id' => $result['message_id'] ?? NULL,
        ]);
      }
    }
  }

  /**
   * Closes conversations inactive for 48h+.
   */
  protected function closeInactiveConversations(int $tenantId): void {
    $storage = $this->entityTypeManager->getStorage('wa_conversation');
    $config = \Drupal::config('jaraba_whatsapp.settings');
    $hours = (int) ($config->get('inactivity_hours_close') ?? 48);
    $cutoff = \Drupal::time()->getRequestTime() - ($hours * 3600);

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('status', [WaConversation::STATUS_ACTIVE, WaConversation::STATUS_INITIATED], 'IN')
      ->condition('last_message_at', $cutoff, '<')
      ->execute();

    foreach ($storage->loadMultiple($ids) as $conversation) {
      /** @var \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation */
      $conversation->setStatus(WaConversation::STATUS_CLOSED);
      $conversation->save();

      $this->logger->info('WaConversation @id closed (inactive @h hours).', [
        '@id' => $conversation->id(),
        '@h' => $hours,
      ]);
    }
  }

}
