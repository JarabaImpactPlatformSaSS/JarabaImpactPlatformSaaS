<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\jaraba_whatsapp\Entity\WaConversationInterface;

/**
 * Event dispatched when a WhatsApp message is received and processed.
 *
 * Allows other modules to react to incoming WhatsApp messages
 * (e.g., update CRM, trigger notifications, analytics).
 */
class WhatsAppMessageReceivedEvent extends Event {

  /**
   * Event name constant.
   */
  public const EVENT_NAME = 'jaraba_whatsapp.message_received';

  public function __construct(
    protected WaConversationInterface $conversation,
    protected string $messageBody,
    protected string $senderType,
  ) {}

  /**
   * Gets the conversation.
   */
  public function getConversation(): WaConversationInterface {
    return $this->conversation;
  }

  /**
   * Gets the message body.
   */
  public function getMessageBody(): string {
    return $this->messageBody;
  }

  /**
   * Gets the sender type.
   */
  public function getSenderType(): string {
    return $this->senderType;
  }

}
