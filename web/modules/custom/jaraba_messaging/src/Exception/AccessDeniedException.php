<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Exception;

use Drupal\Core\Access\AccessException;

/**
 * Thrown when user lacks permission for a messaging action.
 */
class AccessDeniedException extends AccessException {

}
