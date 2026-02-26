<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Drupal\jaraba_ai_agents\Entity\A2ATask;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * GAP-AUD-012: Processes A2A tasks asynchronously.
 *
 * @QueueWorker(
 *   id = "a2a_task_worker",
 *   title = @Translation("A2A Task Worker"),
 *   cron = {"time" = 120}
 * )
 */
class A2ATaskWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ClientInterface $httpClient,
    protected ?object $aiAgent = NULL,
    protected ?object $guardrailsService = NULL,
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
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_ai_agents'),
      $container->get('http_client'),
      $container->has('jaraba_ai_agents.smart_marketing_agent')
        ? $container->get('jaraba_ai_agents.smart_marketing_agent')
        : NULL,
      $container->has('ecosistema_jaraba_core.ai_guardrails')
        ? $container->get('ecosistema_jaraba_core.ai_guardrails')
        : NULL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $taskId = (int) ($data['task_id'] ?? 0);
    if ($taskId <= 0) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('a2a_task');
    /** @var \Drupal\jaraba_ai_agents\Entity\A2ATask|null $task */
    $task = $storage->load($taskId);
    if (!$task) {
      $this->logger->warning('A2A task @id not found.', ['@id' => $taskId]);
      return;
    }

    // Skip if already completed or cancelled.
    if (in_array($task->getStatus(), [A2ATask::STATUS_COMPLETED, A2ATask::STATUS_CANCELLED, A2ATask::STATUS_FAILED], TRUE)) {
      return;
    }

    // Mark as working.
    $task->setStatus(A2ATask::STATUS_WORKING);
    $task->save();

    try {
      $output = $this->executeAction($task);

      // PII masking on output.
      if ($this->guardrailsService !== NULL && method_exists($this->guardrailsService, 'maskOutputPII')) {
        $output = $this->maskOutputRecursive($output);
      }

      $task->setOutput($output);
      $task->setStatus(A2ATask::STATUS_COMPLETED);
      $task->save();

      // Callback notification.
      $callbackUrl = $task->getCallbackUrl();
      if (!empty($callbackUrl)) {
        $this->notifyCallback($callbackUrl, $task);
      }

      $this->logger->info('A2A task @id completed: @action', [
        '@id' => $taskId,
        '@action' => $task->getAction(),
      ]);
    }
    catch (\Exception $e) {
      $task->setStatus(A2ATask::STATUS_FAILED);
      $task->set('error_message', mb_substr($e->getMessage(), 0, 1000));
      $task->save();

      $this->logger->error('A2A task @id failed: @error', [
        '@id' => $taskId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Executes the task action using AI agents.
   */
  protected function executeAction(A2ATask $task): array {
    if ($this->aiAgent === NULL) {
      return ['error' => 'AI agent not available.'];
    }

    $action = $task->getAction();
    $input = $task->getInput();

    $prompt = match ($action) {
      'generate_content' => $this->buildContentPrompt($input),
      'analyze_sentiment' => $this->buildSentimentPrompt($input),
      'seo_suggestions' => $this->buildSeoPrompt($input),
      'brand_voice_check' => $this->buildBrandVoicePrompt($input),
      'skill_inference' => $this->buildSkillPrompt($input),
      default => throw new \InvalidArgumentException('Unknown action: ' . $action),
    };

    $tier = match ($action) {
      'generate_content', 'brand_voice_check' => 'balanced',
      default => 'fast',
    };

    $result = $this->aiAgent->execute([
      'prompt' => AIIdentityRule::apply($prompt, TRUE),
      'tier' => $tier,
      'max_tokens' => 1024,
      'temperature' => 0.4,
    ]);

    $responseText = $result['response'] ?? $result['text'] ?? '';

    return [
      'action' => $action,
      'result' => $responseText,
      'model' => $result['model'] ?? '',
      'tokens_used' => ($result['input_tokens'] ?? 0) + ($result['output_tokens'] ?? 0),
    ];
  }

  /**
   * Builds prompt for content generation.
   */
  protected function buildContentPrompt(array $input): string {
    $prompt = $input['prompt'] ?? 'Generate marketing content.';
    $tone = $input['tone'] ?? 'professional';
    $vertical = $input['vertical'] ?? 'generic';
    $language = $input['language'] ?? 'es';

    return "Generate content in $language with a $tone tone for the $vertical vertical. Prompt: $prompt";
  }

  /**
   * Builds prompt for sentiment analysis.
   */
  protected function buildSentimentPrompt(array $input): string {
    $text = $input['text'] ?? '';
    $language = $input['language'] ?? 'es';

    return "Analyze the sentiment of this text in $language. Return JSON: {\"sentiment\": \"positive|negative|neutral|mixed\", \"score\": 0.0-1.0, \"aspects\": [{\"aspect\": \"...\", \"sentiment\": \"...\"}]}. Text: $text";
  }

  /**
   * Builds prompt for SEO suggestions.
   */
  protected function buildSeoPrompt(array $input): string {
    $content = mb_substr($input['content'] ?? '', 0, 2000);
    $keyword = $input['keyword'] ?? '';

    return "Analyze this content for SEO targeting keyword \"$keyword\". Return JSON: {\"score\": 0-100, \"suggestions\": [{\"type\": \"...\", \"priority\": \"high|medium|low\", \"suggestion\": \"...\"}]}. Content: $content";
  }

  /**
   * Builds prompt for brand voice check.
   */
  protected function buildBrandVoicePrompt(array $input): string {
    $text = mb_substr($input['text'] ?? '', 0, 2000);

    return "Evaluate this text for brand voice alignment. Return JSON: {\"alignment_score\": 0.0-1.0, \"suggestions\": [\"...\"]}. Text: $text";
  }

  /**
   * Builds prompt for skill inference.
   */
  protected function buildSkillPrompt(array $input): string {
    $text = mb_substr($input['text'] ?? '', 0, 3000);
    $language = $input['language'] ?? 'es';

    return "Extract professional skills from this text in $language. Return JSON: {\"skills\": {\"technical\": [{\"name\": \"...\", \"level\": 1-5}], \"soft\": [...], \"digital\": [...], \"languages\": [...]}, \"confidence\": 0.0-1.0}. Text: $text";
  }

  /**
   * Notifies callback URL of task completion.
   */
  protected function notifyCallback(string $callbackUrl, A2ATask $task): void {
    try {
      $this->httpClient->request('POST', $callbackUrl, [
        'json' => [
          'task_id' => (int) $task->id(),
          'status' => $task->getStatus(),
          'output' => $task->getOutput(),
        ],
        'timeout' => 10,
        'http_errors' => FALSE,
      ]);
    }
    catch (\Exception $e) {
      $this->logger->warning('A2A callback failed for task @id: @error', [
        '@id' => $task->id(),
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Recursively masks PII in output arrays.
   */
  protected function maskOutputRecursive(array $data): array {
    foreach ($data as $key => &$value) {
      if (is_string($value)) {
        $value = $this->guardrailsService->maskOutputPII($value);
      }
      elseif (is_array($value)) {
        $value = $this->maskOutputRecursive($value);
      }
    }
    return $data;
  }

}
