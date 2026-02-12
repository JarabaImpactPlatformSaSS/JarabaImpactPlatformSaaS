<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Exception;

/**
 * Exception thrown when a multi-modal capability is not yet available.
 */
class MultiModalNotAvailableException extends \RuntimeException {

  public function __construct(string $capability, string $message = '') {
    $msg = $message ?: "Multi-modal capability '$capability' is not yet available. This is a placeholder for future implementation.";
    parent::__construct($msg);
  }

}
