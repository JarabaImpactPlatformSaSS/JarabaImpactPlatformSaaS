<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for InteractiveContent entities.
 *
 * Defines the contract for interactive content entities used by
 * XApiEmitter and CompletionSubscriber services.
 */
interface InteractiveContentInterface extends ContentEntityInterface {

}
