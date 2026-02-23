<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Exception;

/**
 * Thrown when user tries to edit a message outside the edit window.
 */
class EditWindowExpiredException extends \RuntimeException {

  /**
   * The edit window duration in minutes.
   */
  public readonly int $windowMinutes;

  /**
   * Constructs a new EditWindowExpiredException.
   *
   * @param int $windowMinutes
   *   The edit window duration in minutes.
   * @param string $message
   *   The exception message.
   * @param int $code
   *   The exception code.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(
    int $windowMinutes,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    $this->windowMinutes = $windowMinutes;

    if ($message === '') {
      $message = sprintf(
        'Edit window of %d minutes has expired.',
        $windowMinutes,
      );
    }

    parent::__construct($message, $code, $previous);
  }

}
