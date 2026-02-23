<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * Servicio de retención RGPD para limpieza programada de mensajes.
 *
 * PROPÓSITO:
 * Purga mensajes y audit logs que exceden el período de retención
 * configurado. Se ejecuta vía cron (hook_cron) con flags de
 * idempotencia (CRON-FLAG-001).
 */
class RetentionService {

  public function __construct(
    protected Connection $database,
    protected MessageAuditServiceInterface $auditService,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Runs the scheduled cleanup of expired messages and audit logs.
   */
  public function runScheduledCleanup(): void {
    $config = $this->configFactory->get('jaraba_messaging.settings');

    // Purge expired messages.
    $messageRetentionDays = $config->get('retention.default_message_retention_days') ?? 730;
    $messageThreshold = time() - ($messageRetentionDays * 86400);
    $deletedMessages = $this->purgeMessages($messageThreshold);

    // Purge expired audit logs.
    $auditRetentionDays = $config->get('retention.audit_log_retention_days') ?? 2555;
    $auditThreshold = time() - ($auditRetentionDays * 86400);
    $deletedAudit = $this->purgeAuditLogs($auditThreshold);

    // Purge expired read receipts (same as messages).
    $deletedReceipts = $this->purgeReadReceipts($messageThreshold);

    if ($deletedMessages > 0 || $deletedAudit > 0 || $deletedReceipts > 0) {
      $this->logger->info('Retention cleanup: @messages messages, @audit audit entries, @receipts receipts purged.', [
        '@messages' => $deletedMessages,
        '@audit' => $deletedAudit,
        '@receipts' => $deletedReceipts,
      ]);
    }
  }

  /**
   * Purges messages older than the threshold.
   */
  protected function purgeMessages(int $threshold): int {
    return (int) $this->database->delete('secure_message')
      ->condition('created_at', $threshold, '<')
      ->execute();
  }

  /**
   * Purges audit log entries older than the threshold.
   */
  protected function purgeAuditLogs(int $threshold): int {
    return (int) $this->database->delete('message_audit_log')
      ->condition('created_at', $threshold, '<')
      ->execute();
  }

  /**
   * Purges read receipts for deleted messages.
   */
  protected function purgeReadReceipts(int $threshold): int {
    return (int) $this->database->delete('message_read_receipt')
      ->condition('read_at', $threshold, '<')
      ->execute();
  }

}
