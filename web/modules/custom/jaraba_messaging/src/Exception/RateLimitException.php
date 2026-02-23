<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Exception;

/**
 * Thrown when user exceeds messaging rate limit.
 */
class RateLimitException extends \RuntimeException {

  /**
   * The maximum number of messages allowed within the window.
   */
  public readonly int $limit;

  /**
   * The rate limit window duration in seconds.
   */
  public readonly int $windowSeconds;

  /**
   * The scope of the rate limit (e.g., 'conversation', 'global').
   */
  public readonly string $scope;

  /**
   * Constructs a new RateLimitException.
   *
   * @param int $limit
   *   The maximum number of messages allowed within the window.
   * @param int $windowSeconds
   *   The rate limit window duration in seconds.
   * @param string $scope
   *   The scope of the rate limit.
   * @param string $message
   *   The exception message.
   * @param int $code
   *   The exception code.
   * @param \Throwable|null $previous
   *   The previous exception.
   */
  public function __construct(
    int $limit,
    int $windowSeconds,
    string $scope,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = NULL,
  ) {
    $this->limit = $limit;
    $this->windowSeconds = $windowSeconds;
    $this->scope = $scope;

    if ($message === '') {
      $message = sprintf(
        'Rate limit exceeded: %d messages per %d seconds (scope: %s).',
        $limit,
        $windowSeconds,
        $scope,
      );
    }

    parent::__construct($message, $code, $previous);
  }

}
