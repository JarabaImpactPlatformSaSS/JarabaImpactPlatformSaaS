<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_whatsapp\Agent\WhatsAppConversationAgent;
use Drupal\jaraba_whatsapp\Entity\WaConversation;
use Drupal\jaraba_whatsapp\Entity\WaMessage;
use Drupal\jaraba_whatsapp\Service\WhatsAppApiService;
use Drupal\jaraba_whatsapp\Service\WhatsAppConversationService;
use Drupal\jaraba_whatsapp\Service\WhatsAppCrmBridgeService;
use Drupal\jaraba_whatsapp\Service\WhatsAppEscalationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes incoming WhatsApp messages asynchronously.
 *
 * SUPERVISOR-SLEEP-001: In production, run via Supervisor with sleep 30-60s.
 *
 * @QueueWorker(
 *   id = "whatsapp_process_message",
 *   title = @Translation("WhatsApp Process Message"),
 *   cron = {"time" = 60}
 * )
 */
class WhatsAppProcessMessageWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WhatsAppConversationService $conversationService,
    protected WhatsAppApiService $apiService,
    protected WhatsAppEscalationService $escalationService,
    protected WhatsAppCrmBridgeService $crmBridge,
    protected LoggerInterface $logger,
    protected ?WhatsAppConversationAgent $agent = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('jaraba_whatsapp.conversation_service'),
      $container->get('jaraba_whatsapp.api_service'),
      $container->get('jaraba_whatsapp.escalation_service'),
      $container->get('jaraba_whatsapp.crm_bridge_service'),
      $container->get('logger.channel.jaraba_whatsapp'),
      $container->has('jaraba_whatsapp.conversation_agent') ? $container->get('jaraba_whatsapp.conversation_agent') : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!is_array($data) || !isset($data['phone'])) {
      return;
    }

    $phone = (string) $data['phone'];
    $body = (string) ($data['body'] ?? '');
    $messageType = (string) ($data['type'] ?? 'text');
    $waMessageId = (string) ($data['message_id'] ?? '');

    // TODO: Resolve tenant from phone_number_id mapping.
    // For now, use default tenant (Andalucia +ei).
    $tenantId = 1;

    // Get or create conversation.
    $conversation = $this->conversationService->getActiveByPhone($phone, $tenantId);
    if ($conversation === NULL) {
      $conversation = $this->conversationService->createConversation($phone, $tenantId);
    }

    // Store incoming message.
    $this->conversationService->addMessage($conversation, [
      'direction' => WaMessage::DIRECTION_INBOUND,
      'sender_type' => WaMessage::SENDER_USER,
      'message_type' => $messageType,
      'body' => $body,
      'wa_message_id' => $waMessageId,
    ]);

    // Check if conversation is escalated (human handling).
    if ($conversation->getStatus() === WaConversation::STATUS_ESCALATED) {
      $this->logger->info('Message received in escalated conversation @id — skipping AI.', [
        '@id' => $conversation->id(),
      ]);
      return;
    }

    // Check auto-escalation rules.
    $autoEscalation = $this->escalationService->checkAutoEscalation($conversation, $body, $messageType);
    if ($autoEscalation['escalate']) {
      $this->escalationService->escalate($conversation, $autoEscalation['reason']);
      return;
    }

    // Agent not available — skip AI processing.
    if ($this->agent === NULL) {
      $this->logger->warning('WhatsApp agent not available — message stored but no AI response.');
      return;
    }

    // Classify on first message.
    if ($conversation->getLeadType() === WaConversation::LEAD_SIN_CLASIFICAR) {
      $classification = $this->agent->classify(['message' => $body]);
      if ($classification['success'] ?? false) {
        $conversation->set('lead_type', $classification['type']);
        $conversation->set('lead_confidence', $classification['confidence']);
        $conversation->save();

        // Link to CRM.
        $this->crmBridge->linkToCrm($conversation);
      }
    }

    // Generate AI response.
    $history = $this->conversationService->getHistory($conversation);
    $response = $this->agent->respond([
      'lead_type' => $conversation->getLeadType(),
      'history' => $history,
      'current_message' => $body,
    ]);

    if (!($response['success'] ?? false)) {
      $this->logger->error('AI response failed for conversation @id.', [
        '@id' => $conversation->id(),
      ]);
      return;
    }

    $responseText = $response['text'] ?? '';

    // Check if AI flagged escalation.
    if ($response['escalate'] ?? false) {
      $summary = $this->agent->summarize([
        'history' => $history,
        'reason' => $response['escalate_reason'] ?? '',
      ]);
      $this->escalationService->escalate(
        $conversation,
        $response['escalate_reason'] ?? 'AI escalation',
        $summary['summary'] ?? '',
      );
    }

    // Send response via WhatsApp.
    if ($responseText !== '') {
      $sendResult = $this->apiService->sendTextMessage($phone, $responseText);

      $this->conversationService->addMessage($conversation, [
        'direction' => WaMessage::DIRECTION_OUTBOUND,
        'sender_type' => WaMessage::SENDER_AGENT_IA,
        'body' => $responseText,
        'wa_message_id' => $sendResult['message_id'] ?? NULL,
        'delivery_status' => ($sendResult['success'] ?? false) ? 'sent' : 'failed',
      ]);
    }
  }

}
