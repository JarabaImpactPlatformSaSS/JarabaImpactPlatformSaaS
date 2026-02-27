<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * AI-powered ticket classification service.
 *
 * Uses LLM (fast tier) to classify tickets by category, sentiment, urgency,
 * and suggested priority. Optionally leverages Qdrant for similar
 * ticket lookup to improve classification accuracy.
 */
final class TicketAiClassificationService {

  /**
   * System prompt for ticket classification.
   */
  private const CLASSIFICATION_PROMPT = <<<'PROMPT'
You are a support ticket classifier for Jaraba Impact Platform, a multi-vertical SaaS ecosystem serving employment, entrepreneurship, agriculture, commerce, services, and education sectors in Spain.

Classify the following support ticket and respond ONLY with a JSON object (no markdown, no explanation).

Required JSON keys:
- "category": one of "general", "billing", "technical", "account", "feature_request", "bug_report", "onboarding", "integration"
- "subcategory": a more specific sub-category string (e.g. "payment_failed", "login_issue", "api_error")
- "confidence": float 0.0-1.0 indicating classification confidence
- "sentiment": one of "positive", "neutral", "negative", "frustrated", "urgent"
- "urgency": one of "low", "medium", "high", "critical"
- "suggested_priority": one of "low", "medium", "high", "urgent"
- "tags": array of 1-5 relevant tags (strings)
- "language": detected language code (e.g. "es", "en")

Context:
- Subject: {subject}
- Description: {description}
- Current priority: {priority}
- Vertical: {vertical}
- Channel: {channel}
PROMPT;

  public function __construct(
    protected ?AiProviderPluginManager $aiProvider,
    protected LoggerInterface $logger,
    protected ?QdrantDirectClient $qdrantClient,
    protected ?ModelRouterService $modelRouter,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Classifies a support ticket using AI.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket to classify.
   *
   * @return array
   *   Classification result with category, confidence, sentiment, urgency,
   *   suggested_priority, tags, language. Empty array if AI unavailable.
   */
  public function classify(SupportTicketInterface $ticket): array {
    if ($this->aiProvider === NULL) {
      $this->logger->info('AI provider unavailable — skipping classification for ticket @id.', [
        '@id' => $ticket->id() ?? 'new',
      ]);
      return [];
    }

    $config = $this->configFactory->get('jaraba_support.settings');
    if (!$config->get('ai_auto_classify')) {
      return [];
    }

    try {
      $subject = $ticket->label() ?? '';
      $description = strip_tags($ticket->get('description')->value ?? '');
      $priority = $ticket->getPriority();
      $vertical = $ticket->get('vertical')->value ?? 'platform';
      $channel = $ticket->get('channel')->value ?? 'web';

      // Build prompt.
      $prompt = str_replace(
        ['{subject}', '{description}', '{priority}', '{vertical}', '{channel}'],
        [$subject, mb_substr($description, 0, 2000), $priority, $vertical, $channel],
        self::CLASSIFICATION_PROMPT,
      );

      // Route to fast tier (classification is a simple extraction task).
      $modelConfig = $this->modelRouter?->route('fast') ?? [];
      $modelId = $modelConfig['model_id'] ?? NULL;

      if (!$modelId) {
        // Fallback: use default provider.
        $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
        if (empty($defaults)) {
          return [];
        }
        $provider = $this->aiProvider->createInstance($defaults['provider_id']);
        $modelId = $defaults['model_id'];
      }
      else {
        $provider = $this->aiProvider->createInstance($modelConfig['provider_id'] ?? $modelConfig['provider'] ?? 'anthropic');
      }

      $chatInput = new ChatInput([
        new ChatMessage('user', $prompt),
      ]);

      $response = $provider->chat($chatInput, $modelId, [
        'temperature' => 0.1,
      ]);

      $text = trim($response->getNormalized()->getText());

      // Parse JSON response.
      $classification = $this->parseJsonResponse($text);

      if (empty($classification)) {
        $this->logger->warning('AI classification returned invalid JSON for ticket @id.', [
          '@id' => $ticket->id() ?? 'new',
        ]);
        return [];
      }

      $this->logger->info('AI classified ticket @id: category=@cat, confidence=@conf, sentiment=@sent', [
        '@id' => $ticket->id() ?? 'new',
        '@cat' => $classification['category'] ?? 'unknown',
        '@conf' => $classification['confidence'] ?? 0,
        '@sent' => $classification['sentiment'] ?? 'unknown',
      ]);

      return $classification;
    }
    catch (\Exception $e) {
      $this->logger->warning('AI classification failed for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parses a JSON response from the LLM, handling common formatting issues.
   */
  private function parseJsonResponse(string $text): array {
    // Strip markdown code fences if present.
    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/i', '', $text);
    $text = trim($text);

    $data = json_decode($text, TRUE);
    if (!is_array($data)) {
      return [];
    }

    // Validate required keys.
    $required = ['category', 'confidence', 'sentiment', 'urgency', 'suggested_priority'];
    foreach ($required as $key) {
      if (!isset($data[$key])) {
        return [];
      }
    }

    // Normalize confidence to float.
    $data['confidence'] = (float) $data['confidence'];

    return $data;
  }

}
