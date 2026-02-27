<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Manages self-improving prompt proposals with constitutional validation.
 *
 * All proposed modifications are validated against ConstitutionalGuardrailService
 * before being applied. Maintains full audit trail via PromptImprovement entities.
 * Supports rollback to the previous prompt version.
 *
 * @see \Drupal\jaraba_ai_agents\Service\ConstitutionalGuardrailService
 * @see \Drupal\jaraba_ai_agents\Entity\PromptImprovement
 * @see \Drupal\jaraba_ai_agents\Entity\PromptTemplate
 */
class SelfImprovingPromptManager {

  /**
   * Maximum pending improvements per agent.
   */
  private const MAX_PENDING_PER_AGENT = 10;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConstitutionalGuardrailService $constitutionalGuardrails,
    protected readonly LoggerChannelInterface $logger,
  ) {}

  /**
   * Creates a prompt improvement proposal.
   *
   * @param string $agentId
   *   The agent whose prompt should be improved.
   * @param string $action
   *   The action context.
   * @param array $reflection
   *   Reflection data: quality_score, suggestions, critical_issues, tenant_id.
   *
   * @return int|null
   *   The PromptImprovement entity ID, or NULL on failure.
   */
  public function proposeImprovement(string $agentId, string $action, array $reflection): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('prompt_improvement');

      // Check pending limit per agent.
      $pendingCount = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('agent_id', $agentId)
        ->condition('status', 'pending')
        ->count()
        ->execute();

      if ((int) $pendingCount >= self::MAX_PENDING_PER_AGENT) {
        $this->logger->info('Max pending improvements reached for agent @agent (@count).', [
          '@agent' => $agentId,
          '@count' => $pendingCount,
        ]);
        return NULL;
      }

      $entity = $storage->create([
        'agent_id' => $agentId,
        'action' => $action,
        'quality_score' => $reflection['quality_score'] ?? 0,
        'suggestions' => json_encode($reflection['suggestions'] ?? [], JSON_THROW_ON_ERROR),
        'critical_issues' => json_encode($reflection['critical_issues'] ?? [], JSON_THROW_ON_ERROR),
        'status' => 'pending',
        'tenant_id' => $reflection['tenant_id'] ?? '',
      ]);
      $entity->save();

      $this->logger->info('Prompt improvement proposed for agent @agent (score: @score).', [
        '@agent' => $agentId,
        '@score' => $reflection['quality_score'] ?? 0,
      ]);

      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to create prompt improvement for @agent: @error', [
        '@agent' => $agentId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Applies an approved prompt improvement.
   *
   * Validates the new prompt against ConstitutionalGuardrailService.
   * Stores previous prompt for rollback capability.
   *
   * @param int $improvementId
   *   The PromptImprovement entity ID.
   * @param string $modifiedPrompt
   *   The new system prompt text.
   *
   * @return array{success: bool, message: string}
   *   Result.
   */
  public function applyImprovement(int $improvementId, string $modifiedPrompt): array {
    try {
      $storage = $this->entityTypeManager->getStorage('prompt_improvement');
      $improvement = $storage->load($improvementId);

      if (!$improvement) {
        return ['success' => FALSE, 'message' => 'Improvement not found'];
      }

      if ($improvement->get('status')->value !== 'pending') {
        return ['success' => FALSE, 'message' => 'Improvement is not in pending state'];
      }

      // Resolve current prompt template for this agent.
      $templateStorage = $this->entityTypeManager->getStorage('prompt_template');
      $templates = $templateStorage->loadByProperties([
        'agent_id' => $improvement->get('agent_id')->value,
        'is_active' => TRUE,
      ]);
      $template = reset($templates);

      if (!$template) {
        return ['success' => FALSE, 'message' => 'No active prompt template found for agent'];
      }

      $originalPrompt = $template->get('system_prompt');

      // Constitutional validation — this is the safety gate.
      $validation = $this->constitutionalGuardrails->validatePromptModification(
        $originalPrompt,
        $modifiedPrompt,
      );

      if (!$validation['approved']) {
        $improvement->set('status', 'rejected_constitutional');
        $improvement->set('rejection_reason', $validation['reason']);
        $improvement->save();

        $this->logger->warning('Prompt improvement @id rejected: @reason', [
          '@id' => $improvementId,
          '@reason' => $validation['reason'],
        ]);

        return [
          'success' => FALSE,
          'message' => 'Constitutional violation: ' . $validation['reason'],
        ];
      }

      // Store previous version for rollback.
      $improvement->set('previous_prompt', $originalPrompt);
      $improvement->set('applied_prompt', $modifiedPrompt);
      $improvement->set('status', 'applied');
      $improvement->save();

      // Update the template.
      $template->set('system_prompt', $modifiedPrompt);
      $template->save();

      $this->logger->info('Prompt improvement @id applied to agent @agent.', [
        '@id' => $improvementId,
        '@agent' => $improvement->get('agent_id')->value,
      ]);

      return ['success' => TRUE, 'message' => 'Improvement applied successfully'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to apply improvement @id: @error', [
        '@id' => $improvementId,
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => $e->getMessage()];
    }
  }

  /**
   * Rolls back a previously applied improvement.
   *
   * Restores the previous prompt from the PromptImprovement record.
   *
   * @param int $improvementId
   *   The PromptImprovement entity ID.
   *
   * @return array{success: bool, message: string}
   *   Result.
   */
  public function rollback(int $improvementId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('prompt_improvement');
      $improvement = $storage->load($improvementId);

      if (!$improvement || $improvement->get('status')->value !== 'applied') {
        return ['success' => FALSE, 'message' => 'Cannot rollback: not in applied state'];
      }

      $previousPrompt = $improvement->get('previous_prompt')->value;
      if (empty($previousPrompt)) {
        return ['success' => FALSE, 'message' => 'No previous prompt stored for rollback'];
      }

      $templateStorage = $this->entityTypeManager->getStorage('prompt_template');
      $templates = $templateStorage->loadByProperties([
        'agent_id' => $improvement->get('agent_id')->value,
        'is_active' => TRUE,
      ]);
      $template = reset($templates);

      if ($template) {
        $template->set('system_prompt', $previousPrompt);
        $template->save();
      }

      $improvement->set('status', 'rolled_back');
      $improvement->save();

      $this->logger->info('Prompt improvement @id rolled back for agent @agent.', [
        '@id' => $improvementId,
        '@agent' => $improvement->get('agent_id')->value,
      ]);

      return ['success' => TRUE, 'message' => 'Rollback successful'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Rollback failed for improvement @id: @error', [
        '@id' => $improvementId,
        '@error' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => $e->getMessage()];
    }
  }

  /**
   * Gets pending improvements for an agent.
   *
   * @param string $agentId
   *   The agent ID.
   *
   * @return array
   *   Array of PromptImprovement entities.
   */
  public function getPending(string $agentId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('prompt_improvement');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('agent_id', $agentId)
        ->condition('status', 'pending')
        ->sort('created', 'DESC')
        ->execute();

      return $ids ? $storage->loadMultiple($ids) : [];
    }
    catch (\Throwable $e) {
      return [];
    }
  }

}
