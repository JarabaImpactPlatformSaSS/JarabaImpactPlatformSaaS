<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event dispatched when a conversation is created.
 */
class ConversationCreatedEvent extends Event {

  public function __construct(
    public readonly int $conversationId,
    public readonly string $conversationUuid,
    public readonly string $conversationType,
    public readonly int $initiatedBy,
    public readonly int $tenantId,
  ) {}

}
