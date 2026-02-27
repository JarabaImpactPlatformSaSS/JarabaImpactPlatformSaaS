<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * AI-powered ticket resolution service.
 *
 * Attempts to resolve tickets automatically by combining KB search results,
 * similar resolved tickets, and LLM reasoning. When confidence is high enough,
 * drafts a resolution; otherwise routes to a human agent.
 */
final class TicketAiResolutionService {

  /**
   * Minimum confidence threshold for auto-resolution.
   */
  private const AUTO_RESOLVE_THRESHOLD = 0.85;

  /**
   * Minimum confidence threshold for suggesting a draft.
   */
  private const SUGGEST_DRAFT_THRESHOLD = 0.60;

  /**
   * System prompt for resolution attempt.
   */
  private const RESOLUTION_PROMPT = <<<'PROMPT'
You are a senior support agent for Jaraba Impact Platform. Your task is to analyze a support ticket and attempt to provide a resolution.

TICKET:
- Subject: {subject}
- Description: {description}
- Category: {category}
- Priority: {priority}
- Vertical: {vertical}

{kb_context}

{similar_context}

INSTRUCTIONS:
1. Analyze the ticket carefully.
2. If you can confidently resolve it based on the KB articles and similar tickets, provide a detailed solution.
3. If you're somewhat confident but not certain, provide a draft suggestion.
4. If the issue requires human investigation, say so.

Respond ONLY with a JSON object:
- "confidence": float 0.0-1.0 indicating your confidence in the solution
- "solution": string with the proposed resolution (in the same language as the ticket, typically Spanish)
- "sources": array of source references used (KB article titles or ticket numbers)
- "reasoning": brief explanation of your reasoning (internal, not shown to customer)
- "requires_human": boolean — true if a human agent should review regardless
PROMPT;

  public function __construct(
    protected ?AiProviderPluginManager $aiProvider,
    protected LoggerInterface $logger,
    protected ?QdrantDirectClient $qdrantClient,
    protected ?ModelRouterService $modelRouter,
    protected ?AIObservabilityService $observability,
    protected ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Attempts AI-powered resolution for a support ticket.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket to attempt resolution for.
   * @param array $kbResults
   *   Knowledge base search results relevant to the ticket.
   * @param array $similarTickets
   *   Previously resolved similar tickets for context.
   *
   * @return array
   *   Resolution attempt result with keys:
   *   - action: 'auto_resolve'|'suggest_draft'|'route_human'.
   *   - solution: Proposed solution text, or NULL.
   *   - confidence: Confidence score 0.0-1.0.
   *   - sources: References used for the solution.
   */
  public function attemptResolution(SupportTicketInterface $ticket, array $kbResults = [], array $similarTickets = []): array {
    if ($this->aiProvider === NULL) {
      return $this->routeToHuman('AI provider unavailable');
    }

    $config = $this->configFactory->get('jaraba_support.settings');
    if (!$config->get('ai_auto_resolve')) {
      return $this->routeToHuman('AI auto-resolve disabled');
    }

    try {
      $startTime = microtime(TRUE);

      // Build context from KB results.
      $kbContext = '';
      if (!empty($kbResults)) {
        $kbContext = "KNOWLEDGE BASE ARTICLES:\n";
        foreach (array_slice($kbResults, 0, 5) as $i => $article) {
          $kbContext .= sprintf(
            "Article %d: %s\n%s\n\n",
            $i + 1,
            $article['title'] ?? 'Untitled',
            mb_substr($article['content'] ?? $article['excerpt'] ?? '', 0, 800),
          );
        }
      }

      // Build context from similar tickets.
      $similarContext = '';
      if (!empty($similarTickets)) {
        $similarContext = "SIMILAR RESOLVED TICKETS:\n";
        foreach (array_slice($similarTickets, 0, 3) as $i => $similar) {
          $similarContext .= sprintf(
            "Ticket %d: %s\nResolution: %s\n\n",
            $i + 1,
            $similar['subject'] ?? '',
            mb_substr($similar['resolution'] ?? '', 0, 500),
          );
        }
      }

      $subject = $ticket->label() ?? '';
      $description = strip_tags($ticket->get('description')->value ?? '');
      $classification = $ticket->getAiClassification();

      $prompt = str_replace(
        ['{subject}', '{description}', '{category}', '{priority}', '{vertical}', '{kb_context}', '{similar_context}'],
        [
          $subject,
          mb_substr($description, 0, 3000),
          $classification['category'] ?? $ticket->get('category')->value ?? 'general',
          $ticket->getPriority(),
          $ticket->get('vertical')->value ?? 'platform',
          $kbContext ?: 'No relevant KB articles found.',
          $similarContext ?: 'No similar resolved tickets found.',
        ],
        self::RESOLUTION_PROMPT,
      );

      // Route to balanced tier (resolution requires more reasoning).
      $modelConfig = $this->modelRouter?->route('balanced') ?? [];
      $modelId = $modelConfig['model_id'] ?? NULL;

      if (!$modelId) {
        $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
        if (empty($defaults)) {
          return $this->routeToHuman('No AI provider configured');
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
        'temperature' => 0.3,
      ]);

      $text = trim($response->getNormalized()->getText());
      $result = $this->parseJsonResponse($text);

      $elapsed = microtime(TRUE) - $startTime;

      // Log observability.
      $this->observability?->log([
        'operation' => 'support_resolution',
        'ticket_id' => $ticket->id(),
        'model_id' => $modelId,
        'confidence' => $result['confidence'] ?? 0,
        'action' => $this->determineAction($result),
        'duration_ms' => (int) ($elapsed * 1000),
      ]);

      if (empty($result)) {
        return $this->routeToHuman('Invalid AI response');
      }

      $confidence = (float) ($result['confidence'] ?? 0);
      $action = $this->determineAction($result);

      $this->logger->info('AI resolution attempt for ticket @id: action=@action, confidence=@conf', [
        '@id' => $ticket->id() ?? 'new',
        '@action' => $action,
        '@conf' => $confidence,
      ]);

      return [
        'action' => $action,
        'solution' => $result['solution'] ?? NULL,
        'confidence' => $confidence,
        'sources' => $result['sources'] ?? [],
        'reasoning' => $result['reasoning'] ?? '',
      ];
    }
    catch (\Exception $e) {
      $this->logger->warning('AI resolution failed for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
      return $this->routeToHuman($e->getMessage());
    }
  }

  /**
   * Determines the action based on AI response confidence.
   */
  private function determineAction(array $result): string {
    if (!empty($result['requires_human'])) {
      return 'route_human';
    }

    $confidence = (float) ($result['confidence'] ?? 0);

    if ($confidence >= self::AUTO_RESOLVE_THRESHOLD) {
      return 'auto_resolve';
    }

    if ($confidence >= self::SUGGEST_DRAFT_THRESHOLD) {
      return 'suggest_draft';
    }

    return 'route_human';
  }

  /**
   * Returns a standard "route to human" response.
   */
  private function routeToHuman(string $reason = ''): array {
    if ($reason) {
      $this->logger->info('Routing to human agent: @reason', ['@reason' => $reason]);
    }
    return [
      'action' => 'route_human',
      'solution' => NULL,
      'confidence' => 0.0,
      'sources' => [],
    ];
  }

  /**
   * Parses a JSON response from the LLM.
   */
  private function parseJsonResponse(string $text): array {
    $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
    $text = preg_replace('/\s*```$/i', '', $text);
    $text = trim($text);

    $data = json_decode($text, TRUE);
    return is_array($data) ? $data : [];
  }

}
