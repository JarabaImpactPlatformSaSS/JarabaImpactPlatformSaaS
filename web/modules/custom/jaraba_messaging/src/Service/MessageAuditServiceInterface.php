<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\jaraba_messaging\Model\IntegrityReport;

/**
 * Interface para el servicio de audit trail inmutable con hash chain.
 */
interface MessageAuditServiceInterface {

  /**
   * Logs an audit entry with hash chain integrity.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   * @param int $tenantId
   *   The tenant ID.
   * @param string $action
   *   The action type (e.g., 'conversation.created', 'message.sent').
   * @param int|null $targetId
   *   The ID of the affected object (message_id, participant_id, etc.).
   * @param array $details
   *   Additional details about the action.
   */
  public function log(int $conversationId, int $tenantId, string $action, ?int $targetId = NULL, array $details = []): void;

  /**
   * Verifies the hash chain integrity of a conversation's audit log.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   *
   * @return \Drupal\jaraba_messaging\Model\IntegrityReport
   *   The integrity verification report.
   */
  public function verifyIntegrity(int $conversationId): IntegrityReport;

  /**
   * Gets the audit log entries for a conversation.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   * @param int $limit
   *   Maximum number of entries to return.
   * @param int $offset
   *   Offset for pagination.
   *
   * @return array
   *   Array of audit log entries.
   */
  public function getLog(int $conversationId, int $limit = 50, int $offset = 0): array;

}
