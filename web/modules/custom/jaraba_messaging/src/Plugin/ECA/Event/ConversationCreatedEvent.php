<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Plugin\ECA\Event;

use Drupal\eca\Attributes\Token;
use Drupal\eca\Plugin\ECA\Event\EventBase;

/**
 * ECA event for when a conversation is created.
 *
 * @EcaEvent(
 *   id = "jaraba_messaging_conversation_created",
 *   label = @Translation("Conversation created"),
 *   description = @Translation("Fires when a new secure conversation is created."),
 *   eca_version_introduced = "2.0.0",
 * )
 */
#[Token(name: 'conversation_id', description: 'The conversation ID.')]
#[Token(name: 'conversation_type', description: 'The conversation type (direct/group/support).')]
#[Token(name: 'initiated_by', description: 'The user ID who created the conversation.')]
#[Token(name: 'tenant_id', description: 'The tenant ID.')]
class ConversationCreatedEvent extends EventBase {

  /**
   * {@inheritdoc}
   */
  public static function definitions(): array {
    return [
      'jaraba_messaging_conversation_created' => [
        'label' => 'Conversation created',
        'event_name' => 'jaraba_messaging.conversation_created',
        'event_class' => \Drupal\jaraba_messaging\Event\ConversationCreatedEvent::class,
      ],
    ];
  }

}
