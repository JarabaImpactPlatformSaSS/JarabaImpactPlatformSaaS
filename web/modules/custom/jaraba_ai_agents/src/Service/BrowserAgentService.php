<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
 * Browser automation agent using headless browser (Playwright).
 *
 * Executes browser-based tasks: web scraping, form filling, screen capture.
 * Runs in an isolated Docker container with strict URL allowlist.
 *
 * Security constraints:
 * - URL allowlist per vertical (configurable).
 * - Memory/time limits per task.
 * - No access to internal network (Docker network isolation).
 * - All tasks logged in BrowserTask entity (append-only audit).
 *
 * Feature-flagged: requires `jaraba_ai_agents.browser.enabled = TRUE`.
 *
 * @see \Drupal\jaraba_ai_agents\Entity\BrowserTask
 */
class BrowserAgentService {

  /**
   * Default URL allowlist per vertical.
   */
  private const DEFAULT_ALLOWLISTS = [
    'empleabilidad' => [
      'linkedin.com',
      'indeed.com',
      'infojobs.net',
      'trabajar.com',
    ],
    'comercioconecta' => [
      'google.com/maps',
      'yelp.com',
      'tripadvisor.com',
    ],
    'agroconecta' => [
      'mapa.gob.es',
      'agroseguro.es',
    ],
  ];

  /**
   * Maximum task duration in seconds.
   */
  private const MAX_TASK_DURATION = 30;

  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerChannelInterface $logger,
    protected readonly ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Checks if browser agent is enabled.
   */
  public function isEnabled(): bool {
    $config = $this->configFactory->get('jaraba_ai_agents.browser');
    return (bool) ($config->get('enabled') ?? FALSE);
  }

  /**
   * Executes a browser task.
   *
   * @param string $taskType
   *   Task type: web_scraping, form_filling, screen_capture, data_extraction.
   * @param string $url
   *   Target URL.
   * @param array $params
   *   Task-specific parameters (selectors, form data, etc.).
   * @param array $context
   *   Context: agent_id, tenant_id, vertical.
   *
   * @return array{success: bool, data: array, task_id: int|null, error: string}
   *   Task execution result.
   */
  public function execute(string $taskType, string $url, array $params = [], array $context = []): array {
    if (!$this->isEnabled()) {
      return ['success' => FALSE, 'data' => [], 'task_id' => NULL, 'error' => 'Browser agent is not enabled.'];
    }

    $vertical = $context['vertical'] ?? '';

    // Validate URL against allowlist.
    if (!$this->isUrlAllowed($url, $vertical)) {
      $taskId = $this->recordTask($context['agent_id'] ?? '', $taskType, $url, 'blocked', [], 'URL not in allowlist', 0, $context['tenant_id'] ?? '');
      return ['success' => FALSE, 'data' => [], 'task_id' => $taskId, 'error' => 'URL not in allowlist for vertical: ' . $vertical];
    }

    $startTime = microtime(TRUE);

    try {
      $result = $this->executeBrowserTask($taskType, $url, $params);
      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      $taskId = $this->recordTask(
        $context['agent_id'] ?? '',
        $taskType,
        $url,
        'completed',
        $result,
        '',
        $durationMs,
        $context['tenant_id'] ?? '',
      );

      $this->observability?->log([
        'agent_id' => 'browser_agent',
        'action' => 'browser_' . $taskType,
        'tier' => 'fast',
        'tenant_id' => $context['tenant_id'] ?? '',
        'duration_ms' => $durationMs,
        'success' => TRUE,
      ]);

      return ['success' => TRUE, 'data' => $result, 'task_id' => $taskId, 'error' => ''];
    }
    catch (\Throwable $e) {
      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      $taskId = $this->recordTask(
        $context['agent_id'] ?? '',
        $taskType,
        $url,
        'failed',
        [],
        $e->getMessage(),
        $durationMs,
        $context['tenant_id'] ?? '',
      );

      $this->logger->warning('Browser task failed: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'data' => [], 'task_id' => $taskId, 'error' => $e->getMessage()];
    }
  }

  /**
   * Checks if a URL is in the allowlist for a vertical.
   */
  public function isUrlAllowed(string $url, string $vertical): bool {
    $config = $this->configFactory->get('jaraba_ai_agents.browser');
    $customAllowlist = $config->get('allowlist.' . $vertical);
    $allowlist = $customAllowlist ?: (self::DEFAULT_ALLOWLISTS[$vertical] ?? []);

    if (empty($allowlist)) {
      return FALSE;
    }

    $parsedUrl = parse_url($url);
    $host = $parsedUrl['host'] ?? '';
    $path = $parsedUrl['path'] ?? '';

    foreach ($allowlist as $allowed) {
      if (str_contains($allowed, '/')) {
        // Domain + path prefix.
        if (str_contains($host . $path, $allowed)) {
          return TRUE;
        }
      }
      else {
        // Domain only.
        if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Gets the allowlist for a vertical.
   */
  public function getAllowlist(string $vertical): array {
    $config = $this->configFactory->get('jaraba_ai_agents.browser');
    return $config->get('allowlist.' . $vertical) ?: (self::DEFAULT_ALLOWLISTS[$vertical] ?? []);
  }

  /**
   * Executes the actual browser task (Playwright).
   *
   * @throws \RuntimeException
   *   If Playwright is not available.
   */
  protected function executeBrowserTask(string $taskType, string $url, array $params): array {
    // Placeholder: actual Playwright integration requires Docker container.
    throw new \RuntimeException(
      'Playwright browser container not configured. '
      . 'Set jaraba_ai_agents.browser.playwright_endpoint in config.'
    );
  }

  /**
   * Records a browser task in the entity store.
   */
  protected function recordTask(
    string $agentId,
    string $taskType,
    string $url,
    string $status,
    array $resultData,
    string $errorMessage,
    int $durationMs,
    string $tenantId,
  ): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('browser_task');
      $entity = $storage->create([
        'agent_id' => $agentId,
        'task_type' => $taskType,
        'target_url' => $url,
        'status' => $status,
        'result_data' => json_encode($resultData, JSON_THROW_ON_ERROR),
        'error_message' => $errorMessage,
        'duration_ms' => $durationMs,
        'tenant_id' => $tenantId,
      ]);
      $entity->save();
      return (int) $entity->id();
    }
    catch (\Throwable $e) {
      $this->logger->error('Failed to record browser task: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

}
