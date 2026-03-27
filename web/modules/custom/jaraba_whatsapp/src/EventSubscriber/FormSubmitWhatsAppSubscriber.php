<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\EventSubscriber;

use Drupal\jaraba_whatsapp\Event\WhatsAppSendTemplateEvent;
use Drupal\jaraba_whatsapp\Service\WhatsAppConversationService;
use Drupal\jaraba_whatsapp\Service\WhatsAppTemplateService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens for form submission events to send WhatsApp templates.
 *
 * When a participant or business form is submitted, sends the
 * appropriate welcome template via WhatsApp and creates the conversation.
 */
class FormSubmitWhatsAppSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected WhatsAppTemplateService $templateService,
    protected WhatsAppConversationService $conversationService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      WhatsAppSendTemplateEvent::EVENT_NAME => ['onSendTemplate', 0],
    ];
  }

  /**
   * Handles the send template event.
   */
  public function onSendTemplate(WhatsAppSendTemplateEvent $event): void {
    $phone = $event->getPhone();
    $templateName = $event->getTemplateName();
    $vars = $event->getTemplateVars();

    if ($phone === '' || $templateName === '') {
      $this->logger->warning('WhatsApp template event with empty phone or template.');
      return;
    }

    // Send template.
    $result = $this->templateService->sendTemplate($templateName, $phone, $vars);

    if ($result['success'] ?? false) {
      // Create conversation in initiated state.
      $this->conversationService->createFromTemplate(
        $phone,
        $event->getTenantId(),
        $event->getUtmParams(),
      );

      $this->logger->info('WhatsApp template @tpl sent to @phone.', [
        '@tpl' => $templateName,
        '@phone' => $phone,
      ]);
    }
    else {
      $this->logger->error('Failed to send WhatsApp template @tpl to @phone: @err', [
        '@tpl' => $templateName,
        '@phone' => $phone,
        '@err' => $result['error'] ?? 'unknown',
      ]);
    }
  }

}
