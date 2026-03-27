<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_whatsapp\Entity\WaConversation;
use Drupal\jaraba_whatsapp\Entity\WaConversationInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages conversation escalation from AI agent to human.
 *
 * Escalation triggers:
 * - IA flag: [ESCALATE:reason] in agent response.
 * - Auto rules: message_count > 8, multimedia, keywords, spam.
 */
class WhatsAppEscalationService {

  /**
   * Escalation keywords that trigger immediate escalation.
   */
  private const ESCALATION_KEYWORDS = [
    'queja', 'denuncia', 'problema', 'enfadado', 'reclamar',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected WhatsAppConversationService $conversationService,
    protected WhatsAppApiService $apiService,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Checks if a conversation should be auto-escalated.
   *
   * @param \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation
   *   The conversation.
   * @param string $messageBody
   *   Latest message body.
   * @param string $messageType
   *   Message type (text, image, audio, etc.).
   *
   * @return array{escalate: bool, reason: string}
   */
  public function checkAutoEscalation(WaConversationInterface $conversation, string $messageBody, string $messageType): array {
    // Rule: multimedia messages (IA cannot process).
    if ($messageType !== 'text' && $messageType !== 'template' && $messageType !== 'interactive') {
      return ['escalate' => true, 'reason' => 'Mensaje multimedia recibido (' . $messageType . ')'];
    }

    // Rule: escalation keywords.
    $bodyLower = mb_strtolower($messageBody);
    foreach (self::ESCALATION_KEYWORDS as $keyword) {
      if (str_contains($bodyLower, $keyword)) {
        return ['escalate' => true, 'reason' => 'Palabra clave detectada: ' . $keyword];
      }
    }

    // Rule: conversation too long without conversion.
    $maxMessages = 8;
    if ($conversation->getMessageCount() > $maxMessages) {
      return ['escalate' => true, 'reason' => 'Conversacion larga sin conversion (>' . $maxMessages . ' mensajes)'];
    }

    return ['escalate' => false, 'reason' => ''];
  }

  /**
   * Escalates a conversation to human agents.
   *
   * @param \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation
   *   The conversation.
   * @param string $reason
   *   Escalation reason.
   * @param string $summary
   *   AI-generated summary.
   */
  public function escalate(WaConversationInterface $conversation, string $reason, string $summary = ''): void {
    $conversation->setStatus(WaConversation::STATUS_ESCALATED);
    $conversation->set('escalation_reason', $reason);
    if ($summary !== '') {
      $conversation->set('escalation_summary', $summary);
    }
    $conversation->save();

    // Notify via email.
    $this->notifyByEmail($conversation, $reason, $summary);

    // Notify via WhatsApp to supervisor.
    $this->notifyBySupervisorWhatsApp($conversation, $reason);

    $this->logger->warning('WaConversation @id escalated: @reason', [
      '@id' => $conversation->id(),
      '@reason' => $reason,
    ]);
  }

  /**
   * Returns a conversation from human back to AI agent.
   *
   * @param \Drupal\jaraba_whatsapp\Entity\WaConversationInterface $conversation
   *   The conversation.
   */
  public function returnToAgent(WaConversationInterface $conversation): void {
    $conversation->setStatus(WaConversation::STATUS_ACTIVE);
    $conversation->set('assigned_to', NULL);
    $conversation->save();

    $this->logger->info('WaConversation @id returned to AI agent.', [
      '@id' => $conversation->id(),
    ]);
  }

  /**
   * Sends escalation notification via email.
   */
  protected function notifyByEmail(WaConversationInterface $conversation, string $reason, string $summary): void {
    try {
      $config = \Drupal::config('jaraba_whatsapp.settings');
      $to = $config->get('escalation_email') ?? '';
      // CONTACT-SSOT-001: Fallback a system.site.mail si no configurado.
      if ($to === '') {
        $to = \Drupal::config('system.site')->get('mail') ?? '';
      }
      if ($to === '') {
        return;
      }

      $params = [
        'subject' => 'Escalacion WhatsApp #' . $conversation->id(),
        'body' => "Conversacion escalada.\n\nTelefono: " . $conversation->getWaPhone()
          . "\nTipo lead: " . $conversation->getLeadType()
          . "\nMotivo: " . $reason
          . "\n\nResumen:\n" . $summary,
      ];

      $this->mailManager->mail('jaraba_whatsapp', 'escalation', $to, 'es', $params);
    }
    catch (\Throwable $e) {
      $this->logger->error('Escalation email failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Sends escalation WhatsApp notification to supervisor.
   */
  protected function notifyBySupervisorWhatsApp(WaConversationInterface $conversation, string $reason): void {
    try {
      $config = \Drupal::config('jaraba_whatsapp.settings');
      $supervisorPhone = $config->get('escalation_whatsapp') ?? '';
      // CONTACT-SSOT-001: Fallback a whatsapp_number de theme_settings.
      if ($supervisorPhone === '') {
        $supervisorPhone = \Drupal::config('ecosistema_jaraba_theme.settings')->get('whatsapp_number') ?? '';
      }
      if ($supervisorPhone === '') {
        return;
      }

      $text = "Escalacion WhatsApp #" . $conversation->id()
        . "\nLead: " . $conversation->getLeadType()
        . "\nMotivo: " . $reason;

      $this->apiService->sendTextMessage($supervisorPhone, $text);
    }
    catch (\Throwable $e) {
      $this->logger->error('Escalation WhatsApp notify failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }

}
