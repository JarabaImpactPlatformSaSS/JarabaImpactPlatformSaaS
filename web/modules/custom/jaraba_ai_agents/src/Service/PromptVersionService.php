<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for prompt version management (S5-03: HAL-AI-23).
 *
 * Manages versioned prompt templates with rollback support.
 * Integrates with SmartBaseAgent for active prompt loading.
 */
class PromptVersionService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Gets the active prompt template for an agent.
   *
   * @param string $agentId
   *   The agent ID.
   *
   * @return \Drupal\jaraba_ai_agents\Entity\PromptTemplate|null
   *   The active prompt template or NULL if none found.
   */
  public function getActivePrompt(string $agentId): ?object {
    try {
      $templates = $this->entityTypeManager->getStorage('prompt_template')
        ->loadByProperties([
          'agent_id' => $agentId,
          'is_active' => TRUE,
        ]);

      if (empty($templates)) {
        return NULL;
      }

      // Return the most recently updated active template.
      usort($templates, fn($a, $b) => ($b->get('updated') ?? 0) - ($a->get('updated') ?? 0));
      return reset($templates) ?: NULL;
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to load active prompt for agent @agent: @error', [
        '@agent' => $agentId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Creates a new version of a prompt template.
   *
   * @param string $agentId
   *   The agent ID.
   * @param array $data
   *   Data: system_prompt, temperature, model_tier, variables, version.
   *
   * @return object|null
   *   The created PromptTemplate entity or NULL on failure.
   */
  public function createVersion(string $agentId, array $data): ?object {
    try {
      $version = $data['version'] ?? $this->getNextVersion($agentId);
      $id = $agentId . '_v' . str_replace('.', '_', $version);

      // Deactivate previous active versions.
      $this->deactivateAllVersions($agentId);

      $storage = $this->entityTypeManager->getStorage('prompt_template');
      $template = $storage->create([
        'id' => $id,
        'label' => ($data['label'] ?? ucfirst(str_replace('_', ' ', $agentId))) . " v{$version}",
        'agent_id' => $agentId,
        'version' => $version,
        'system_prompt' => $data['system_prompt'] ?? '',
        'temperature' => $data['temperature'] ?? 0.7,
        'model_tier' => $data['model_tier'] ?? 'balanced',
        'variables' => $data['variables'] ?? [],
        'is_active' => TRUE,
        'created' => time(),
        'updated' => time(),
      ]);
      $template->save();

      $this->logger->info('Created prompt version @version for agent @agent', [
        '@version' => $version,
        '@agent' => $agentId,
      ]);

      return $template;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create prompt version: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Rolls back to a specific version.
   *
   * @param string $agentId
   *   The agent ID.
   * @param string $version
   *   The version to activate.
   */
  public function rollback(string $agentId, string $version): bool {
    try {
      $templates = $this->entityTypeManager->getStorage('prompt_template')
        ->loadByProperties([
          'agent_id' => $agentId,
          'version' => $version,
        ]);

      if (empty($templates)) {
        $this->logger->warning('No prompt template found for @agent version @version', [
          '@agent' => $agentId,
          '@version' => $version,
        ]);
        return FALSE;
      }

      $this->deactivateAllVersions($agentId);

      $target = reset($templates);
      $target->set('is_active', TRUE);
      $target->set('updated', time());
      $target->save();

      $this->logger->info('Rolled back prompt for agent @agent to version @version', [
        '@agent' => $agentId,
        '@version' => $version,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Rollback failed: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Gets version history for an agent.
   *
   * @param string $agentId
   *   The agent ID.
   *
   * @return array
   *   Array of version info sorted by creation date desc.
   */
  public function getHistory(string $agentId): array {
    try {
      $templates = $this->entityTypeManager->getStorage('prompt_template')
        ->loadByProperties(['agent_id' => $agentId]);

      $history = [];
      foreach ($templates as $template) {
        $history[] = [
          'id' => $template->id(),
          'version' => $template->get('version'),
          'is_active' => (bool) $template->get('is_active'),
          'model_tier' => $template->get('model_tier'),
          'temperature' => $template->get('temperature'),
          'created' => $template->get('created'),
          'updated' => $template->get('updated'),
        ];
      }

      usort($history, fn($a, $b) => ($b['created'] ?? 0) - ($a['created'] ?? 0));
      return $history;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Deactivates all versions for an agent.
   */
  protected function deactivateAllVersions(string $agentId): void {
    $templates = $this->entityTypeManager->getStorage('prompt_template')
      ->loadByProperties([
        'agent_id' => $agentId,
        'is_active' => TRUE,
      ]);

    foreach ($templates as $template) {
      $template->set('is_active', FALSE);
      $template->save();
    }
  }

  /**
   * Gets the next version number.
   */
  protected function getNextVersion(string $agentId): string {
    $history = $this->getHistory($agentId);
    if (empty($history)) {
      return '1.0.0';
    }

    $latest = $history[0]['version'] ?? '1.0.0';
    $parts = explode('.', $latest);
    $parts[2] = (int) ($parts[2] ?? 0) + 1;
    return implode('.', $parts);
  }

}
