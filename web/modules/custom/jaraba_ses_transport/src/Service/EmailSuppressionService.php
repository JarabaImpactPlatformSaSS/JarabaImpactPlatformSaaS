<?php

declare(strict_types=1);

namespace Drupal\jaraba_ses_transport\Service;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Manages the email suppression list from SES bounces and complaints.
 *
 * EMAIL-BOUNCE-SYNC-001: Auto-suppresses addresses that hard-bounce or
 * generate complaints. Transient bounces get a 24h cooldown. Permanent
 * bounces and complaints are suppressed indefinitely until manual removal.
 */
class EmailSuppressionService {

  /**
   * Cooldown period for transient bounces (24 hours).
   */
  private const TRANSIENT_COOLDOWN = 86400;

  public function __construct(
    private readonly Connection $database,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Checks if an email address is suppressed.
   *
   * @param string $email
   *   The email address to check (already lowercase).
   *
   * @return bool
   *   TRUE if the email should not be sent to.
   */
  public function isSuppressed(string $email): bool {
    $record = $this->database->select('email_suppression', 'es')
      ->fields('es', ['reason', 'bounce_type', 'created'])
      ->condition('email', $email)
      ->execute()
      ->fetchAssoc();

    if (!$record) {
      return FALSE;
    }

    // Permanent bounces and complaints: always suppressed.
    if ($record['reason'] === 'complaint' || $record['bounce_type'] === 'Permanent') {
      return TRUE;
    }

    // Transient bounces: suppressed for 24h cooldown.
    if ($record['bounce_type'] === 'Transient') {
      return (\Drupal::time()->getRequestTime() - (int) $record['created']) < self::TRANSIENT_COOLDOWN;
    }

    // Unknown/manual: suppressed.
    return TRUE;
  }

  /**
   * Adds an email to the suppression list.
   *
   * @param string $email
   *   The email address to suppress.
   * @param string $reason
   *   The reason: 'bounce', 'complaint', or 'manual'.
   * @param string|null $bounceType
   *   SES bounce sub-type: 'Permanent', 'Transient', 'Undetermined'.
   * @param string|null $sesMessageId
   *   Original SES message ID.
   */
  public function suppress(string $email, string $reason = 'bounce', ?string $bounceType = NULL, ?string $sesMessageId = NULL): void {
    $email = strtolower(trim($email));

    try {
      $this->database->merge('email_suppression')
        ->keys(['email' => $email])
        ->fields([
          'reason' => $reason,
          'bounce_type' => $bounceType,
          'ses_message_id' => $sesMessageId,
          'created' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();

      $this->logger->warning('Email suppressed: @email (reason: @reason, type: @type)', [
        '@email' => $email,
        '@reason' => $reason,
        '@type' => $bounceType ?? 'N/A',
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to suppress email @email: @error', [
        '@email' => $email,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Removes an email from the suppression list (manual unsuppression).
   */
  public function unsuppress(string $email): bool {
    $email = strtolower(trim($email));
    $deleted = $this->database->delete('email_suppression')
      ->condition('email', $email)
      ->execute();

    if ($deleted > 0) {
      $this->logger->notice('Email unsuppressed: @email', ['@email' => $email]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Gets suppression stats for monitoring.
   *
   * @return array{total: int, bounces: int, complaints: int, manual: int}
   */
  public function getStats(): array {
    $query = $this->database->select('email_suppression', 'es');
    $query->addExpression('COUNT(*)', 'total');
    $total = (int) $query->execute()->fetchField();

    $query = $this->database->select('email_suppression', 'es');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('reason', 'bounce');
    $bounces = (int) $query->execute()->fetchField();

    $query = $this->database->select('email_suppression', 'es');
    $query->addExpression('COUNT(*)', 'count');
    $query->condition('reason', 'complaint');
    $complaints = (int) $query->execute()->fetchField();

    return [
      'total' => $total,
      'bounces' => $bounces,
      'complaints' => $complaints,
      'manual' => $total - $bounces - $complaints,
    ];
  }

}
