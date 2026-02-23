<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event dispatched when messages are marked as read.
 */
class MessageReadEvent extends Event {

  public function __construct(
    public readonly int $conversationId,
    public readonly int $userId,
    public readonly int $readCount,
  ) {}

}
