<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Plugin\ECA\Event;

use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * ECA event for when messages are marked as read.
 *
 * @EcaEvent(
 *   id = "jaraba_messaging_message_read",
 *   label = @Translation("Message read"),
 *   description = @Translation("Fires when messages in a conversation are marked as read."),
 *   eca_version_introduced = "2.0.0",
 * )
 */
#[Token(name: 'conversation_id', description: 'The conversation ID.')]
#[Token(name: 'user_id', description: 'The user who read the messages.')]
#[Token(name: 'read_count', description: 'Number of messages marked as read.')]
class MessageReadEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'jaraba_messaging_message_read' => [
        'label' => 'Message read',
        'event_name' => 'jaraba_messaging.message_read',
        'event_class' => \Drupal\jaraba_messaging\Event\MessageReadEvent::class,
      ],
    ];
  }

}
