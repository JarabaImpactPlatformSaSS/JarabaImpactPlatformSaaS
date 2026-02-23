<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event dispatched when a message is sent.
 */
class MessageSentEvent extends Event {

  public function __construct(
    public readonly int $conversationId,
    public readonly string $conversationUuid,
    public readonly int $messageId,
    public readonly int $senderId,
    public readonly int $tenantId,
  ) {}

}
