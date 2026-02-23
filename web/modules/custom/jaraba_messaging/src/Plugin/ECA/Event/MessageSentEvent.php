<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Plugin\ECA\Event;

use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * ECA event for when a message is sent.
 *
 * @EcaEvent(
 *   id = "jaraba_messaging_message_sent",
 *   label = @Translation("Message sent"),
 *   description = @Translation("Fires when a secure message is sent in a conversation."),
 *   eca_version_introduced = "2.0.0",
 * )
 */
#[Token(name: 'conversation_id', description: 'The conversation ID.')]
#[Token(name: 'message_id', description: 'The message ID.')]
#[Token(name: 'sender_id', description: 'The sender user ID.')]
#[Token(name: 'tenant_id', description: 'The tenant ID.')]
class MessageSentEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'jaraba_messaging_message_sent' => [
        'label' => 'Message sent',
        'event_name' => 'jaraba_messaging.message_sent',
        'event_class' => \Drupal\jaraba_messaging\Event\MessageSentEvent::class,
      ],
    ];
  }

}
