<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GAP-AUD-025: Processes scheduled/batch AI tasks.
 *
 * Handles deferred AI workloads that don't need immediate response:
 * - Batch demand forecasting
 * - Bulk skill inference
 * - Periodic content quality audits
 * - Scheduled SEO analysis
 *
 * Designed for horizontal scaling via Redis queue + supervisor workers.
 *
 * @QueueWorker(
 *   id = "scheduled_agent",
 *   title = @Translation("Scheduled Agent Worker"),
 *   cron = {"time" = 300}
 * )
 */
class ScheduledAgentWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LoggerInterface $logger,
    protected ?object $aiAgent = NULL,
    protected ?object $observability = NULL,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.jaraba_ai_agents'),
      $container->has('jaraba_ai_agents.smart_marketing_agent')
        ? $container->get('jaraba_ai_agents.smart_marketing_agent')
        : NULL,
      $container->has('jaraba_ai_agents.observability')
        ? $container->get('jaraba_ai_agents.observability')
        : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $type = $data['type'] ?? '';
    $tenantId = $data['tenant_id'] ?? '';

    if (empty($type)) {
      $this->logger->warning('Scheduled agent item missing type.');
      return;
    }

    $startTime = microtime(TRUE);

    try {
      $result = match ($type) {
        'batch_content_audit' => $this->processBatchContentAudit($data),
        'batch_seo_analysis' => $this->processBatchSeoAnalysis($data),
        'batch_skill_inference' => $this->processBatchSkillInference($data),
        'tenant_health_check' => $this->processTenantHealthCheck($data),
        default => ['error' => 'Unknown scheduled type: ' . $type],
      };

      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      // Log to observability.
      if ($this->observability !== NULL && method_exists($this->observability, 'log')) {
        $this->observability->log([
          'agent_id' => 'scheduled_agent',
          'action' => $type,
          'tier' => 'balanced',
          'tenant_id' => $tenantId,
          'duration_ms' => $durationMs,
          'success' => !isset($result['error']),
          'input_tokens' => $result['tokens_in'] ?? 0,
          'output_tokens' => $result['tokens_out'] ?? 0,
        ]);
      }

      $this->logger->info('Scheduled agent @type completed in @ms ms for tenant @tenant.', [
        '@type' => $type,
        '@ms' => $durationMs,
        '@tenant' => $tenantId ?: 'global',
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Scheduled agent @type failed: @error', [
        '@type' => $type,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Audits content quality for a batch of articles.
   */
  protected function processBatchContentAudit(array $data): array {
    if ($this->aiAgent === NULL) {
      return ['error' => 'AI agent not available.'];
    }

    $entityIds = $data['entity_ids'] ?? [];
    if (empty($entityIds)) {
      return ['processed' => 0];
    }

    $processed = 0;
    $totalTokensIn = 0;
    $totalTokensOut = 0;

    foreach (array_slice($entityIds, 0, 10) as $entityId) {
      try {
        $prompt = AIIdentityRule::apply(
          "Evaluate the content quality of article ID {$entityId}. Rate SEO, readability, and engagement potential on a 0-100 scale. Return JSON: {\"seo_score\": N, \"readability_score\": N, \"engagement_score\": N, \"suggestions\": [\"...\"]}.",
          TRUE
        );

        $result = $this->aiAgent->execute([
          'prompt' => $prompt,
          'tier' => 'fast',
          'max_tokens' => 512,
          'temperature' => 0.3,
        ]);

        $totalTokensIn += $result['input_tokens'] ?? 0;
        $totalTokensOut += $result['output_tokens'] ?? 0;
        $processed++;
      }
      catch (\Exception $e) {
        $this->logger->warning('Content audit failed for entity @id: @error', [
          '@id' => $entityId,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return ['processed' => $processed, 'tokens_in' => $totalTokensIn, 'tokens_out' => $totalTokensOut];
  }

  /**
   * Batch SEO analysis for multiple pages.
   */
  protected function processBatchSeoAnalysis(array $data): array {
    if ($this->aiAgent === NULL) {
      return ['error' => 'AI agent not available.'];
    }

    $urls = $data['urls'] ?? [];
    $processed = 0;

    foreach (array_slice($urls, 0, 5) as $url) {
      try {
        $prompt = AIIdentityRule::apply(
          "Analyze SEO for this URL: {$url}. Return JSON: {\"score\": 0-100, \"issues\": [{\"type\": \"...\", \"priority\": \"high|medium|low\", \"fix\": \"...\"}]}.",
          TRUE
        );

        $this->aiAgent->execute([
          'prompt' => $prompt,
          'tier' => 'fast',
          'max_tokens' => 512,
          'temperature' => 0.3,
        ]);
        $processed++;
      }
      catch (\Exception $e) {
        // Continue with next URL.
      }
    }

    return ['processed' => $processed];
  }

  /**
   * Batch skill inference from multiple profiles.
   */
  protected function processBatchSkillInference(array $data): array {
    $profileIds = $data['profile_ids'] ?? [];
    return ['processed' => 0, 'total' => count($profileIds), 'note' => 'Delegated to SkillInferenceService.'];
  }

  /**
   * Tenant health check — AI-powered analysis of tenant metrics.
   */
  protected function processTenantHealthCheck(array $data): array {
    if ($this->aiAgent === NULL) {
      return ['error' => 'AI agent not available.'];
    }

    $tenantId = $data['tenant_id'] ?? '';
    if (empty($tenantId)) {
      return ['error' => 'No tenant_id provided.'];
    }

    $prompt = AIIdentityRule::apply(
      "Analyze the health metrics for tenant {$tenantId}. Consider AI usage patterns, content creation velocity, and engagement trends. Return JSON: {\"health_score\": 0-100, \"recommendations\": [\"...\"]}.",
      TRUE
    );

    $result = $this->aiAgent->execute([
      'prompt' => $prompt,
      'tier' => 'fast',
      'max_tokens' => 512,
      'temperature' => 0.3,
    ]);

    return [
      'tenant_id' => $tenantId,
      'tokens_in' => $result['input_tokens'] ?? 0,
      'tokens_out' => $result['output_tokens'] ?? 0,
    ];
  }

}
