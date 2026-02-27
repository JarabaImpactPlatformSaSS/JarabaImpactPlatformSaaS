<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Regulatory-grade audit trail for AI interactions.
 *
 * Creates immutable AiAuditEntry records for every AI agent interaction.
 * Designed for EU AI Act Art. 12 (Record-keeping) compliance:
 * - Append-only (no modifications after creation).
 * - Includes risk classification, decision, human oversight status.
 * - Hashes user input/output for privacy (no raw PII stored).
 * - Minimum 5-year retention.
 *
 * Non-blocking: audit failures MUST NOT affect agent response delivery.
 *
 * @see \Drupal\jaraba_ai_agents\Entity\AiAuditEntry
 * @see \Drupal\jaraba_ai_agents\Service\AiRiskClassificationService
 */
class AiAuditTrailService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AiRiskClassificationService $riskClassification,
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Records an AI interaction in the audit trail.
   *
   * @param array $data
   *   Audit data:
   *   - agent_id (string, required)
   *   - action (string, required)
   *   - user_input (string): Raw input — will be hashed, not stored raw.
   *   - agent_output (string): Raw output — will be hashed, not stored raw.
   *   - decision (string): delivered|blocked|escalated|modified.
   *   - human_oversight (bool): Whether human review was applied.
   *   - human_reviewer (string): UID of reviewer.
   *   - verification_id (int): Reference to VerificationResult.
   *   - model_id (string): AI model used.
   *   - provider_id (string): AI provider used.
   *   - tenant_id (string): Tenant context.
   *   - vertical (string): Vertical context.
   *   - metadata (array): Additional compliance metadata.
   *
   * @return int|null
   *   The AiAuditEntry entity ID, or NULL on error.
   */
  public function record(array $data): ?int {
    try {
      $agentId = $data['agent_id'] ?? '';
      $action = $data['action'] ?? '';

      if (empty($agentId) || empty($action)) {
        return NULL;
      }

      // Classify risk at interaction time.
      $classification = $this->riskClassification->classify($agentId, $action, [
        'vertical' => $data['vertical'] ?? '',
      ]);

      $storage = $this->entityTypeManager->getStorage('ai_audit_entry');
      $entity = $storage->create([
        'agent_id' => $agentId,
        'action' => $action,
        'risk_level' => $classification['risk_level'],
        'decision' => $data['decision'] ?? 'delivered',
        'human_oversight' => !empty($data['human_oversight']),
        'human_reviewer' => $data['human_reviewer'] ?? '',
        'user_input_hash' => !empty($data['user_input'])
          ? hash('sha256', $data['user_input'])
          : '',
        'output_hash' => !empty($data['agent_output'])
          ? hash('sha256', $data['agent_output'])
          : '',
        'verification_id' => $data['verification_id'] ?? NULL,
        'model_id' => $data['model_id'] ?? '',
        'provider_id' => $data['provider_id'] ?? '',
        'tenant_id' => $data['tenant_id'] ?? '',
        'compliance_metadata' => json_encode(
          array_merge(
            $data['metadata'] ?? [],
            [
              'risk_classification' => $classification,
              'eu_ai_act_version' => '2024/1689',
            ],
          ),
          JSON_THROW_ON_ERROR,
        ),
      ]);
      $entity->save();

      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      // Non-blocking: audit failure must not affect response delivery.
      $this->logger->error('Audit trail record failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets audit entries for a specific agent within a date range.
   *
   * @param string $agentId
   *   The agent ID.
   * @param string $from
   *   Start date (Y-m-d format).
   * @param string $to
   *   End date (Y-m-d format).
   * @param int $limit
   *   Maximum entries to return.
   *
   * @return array
   *   Array of AiAuditEntry entities.
   */
  public function getEntries(string $agentId, string $from = '', string $to = '', int $limit = 100): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_audit_entry');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('agent_id', $agentId)
        ->sort('created', 'DESC')
        ->range(0, $limit);

      if (!empty($from)) {
        $query->condition('created', strtotime($from), '>=');
      }
      if (!empty($to)) {
        $query->condition('created', strtotime($to . ' 23:59:59'), '<=');
      }

      $ids = $query->execute();
      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable $e) {
      $this->logger->error('Audit trail query failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Gets compliance statistics for a tenant.
   *
   * @param string $tenantId
   *   The tenant ID.
   * @param int $days
   *   Number of days to look back.
   *
   * @return array
   *   Statistics: total_interactions, by_risk_level, by_decision,
   *   human_oversight_rate, blocked_rate.
   */
  public function getComplianceStats(string $tenantId, int $days = 30): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_audit_entry');
      $since = \Drupal::time()->getRequestTime() - ($days * 86400);

      $baseQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('created', $since, '>=');

      $totalIds = (clone $baseQuery)->execute();
      $total = count($totalIds);

      if ($total === 0) {
        return [
          'total_interactions' => 0,
          'by_risk_level' => [],
          'by_decision' => [],
          'human_oversight_rate' => 0.0,
          'blocked_rate' => 0.0,
          'period_days' => $days,
        ];
      }

      $entities = $storage->loadMultiple($totalIds);

      $byRisk = [];
      $byDecision = [];
      $humanOversightCount = 0;
      $blockedCount = 0;

      foreach ($entities as $entity) {
        $risk = $entity->get('risk_level')->value ?? 'minimal';
        $decision = $entity->get('decision')->value ?? 'delivered';

        $byRisk[$risk] = ($byRisk[$risk] ?? 0) + 1;
        $byDecision[$decision] = ($byDecision[$decision] ?? 0) + 1;

        if ($entity->get('human_oversight')->value) {
          $humanOversightCount++;
        }
        if ($decision === 'blocked') {
          $blockedCount++;
        }
      }

      return [
        'total_interactions' => $total,
        'by_risk_level' => $byRisk,
        'by_decision' => $byDecision,
        'human_oversight_rate' => $total > 0 ? round($humanOversightCount / $total, 4) : 0.0,
        'blocked_rate' => $total > 0 ? round($blockedCount / $total, 4) : 0.0,
        'period_days' => $days,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Compliance stats query failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return ['total_interactions' => 0, 'error' => $e->getMessage()];
    }
  }

}
