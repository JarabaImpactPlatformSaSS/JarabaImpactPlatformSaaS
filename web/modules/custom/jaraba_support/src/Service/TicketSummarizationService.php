<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_support\Entity\SupportTicketInterface;
use Psr\Log\LoggerInterface;

/**
 * AI-powered ticket summarization service.
 *
 * Generates concise summaries of ticket conversations for agent
 * handoffs, escalations, and resolution notes. Uses the fast-tier
 * model for cost efficiency.
 */
final class TicketSummarizationService {

  /**
   * System prompt for summarization.
   */
  private const SUMMARIZE_PROMPT = <<<'PROMPT'
Summarize the following support ticket conversation concisely. The summary should cover:
1. The original issue reported
2. Key actions taken
3. Current status and any pending items
4. Customer sentiment

Write in the same language as the conversation (typically Spanish). Keep it under 200 words.

TICKET: {subject} (#{ticket_number})
Priority: {priority} | Status: {status}

CONVERSATION:
{messages}
PROMPT;

  public function __construct(
    protected ?AiProviderPluginManager $aiProvider,
    protected LoggerInterface $logger,
    protected TicketService $ticketService,
    protected ?ModelRouterService $modelRouter,
  ) {}

  /**
   * Generates an AI summary of a ticket's conversation.
   *
   * @param \Drupal\jaraba_support\Entity\SupportTicketInterface $ticket
   *   The ticket to summarize.
   *
   * @return string
   *   The generated summary text. Returns empty string if AI is unavailable.
   */
  public function summarize(SupportTicketInterface $ticket): string {
    if ($this->aiProvider === NULL) {
      return '';
    }

    try {
      $messages = $this->ticketService->getMessages($ticket, TRUE);

      if (empty($messages)) {
        return '';
      }

      // Build messages text.
      $messagesText = '';
      foreach ($messages as $message) {
        $authorType = $message->getAuthorType();
        $authorLabel = match ($authorType) {
          'customer' => 'Customer',
          'agent' => 'Agent',
          'ai' => 'AI',
          'system' => 'System',
          default => 'Unknown',
        };

        $body = strip_tags($message->get('body')->value ?? '');
        $isNote = $message->isInternalNote() ? ' [INTERNAL NOTE]' : '';

        $messagesText .= sprintf("[%s%s]: %s\n\n", $authorLabel, $isNote, mb_substr($body, 0, 500));
      }

      $prompt = str_replace(
        ['{subject}', '{ticket_number}', '{priority}', '{status}', '{messages}'],
        [
          $ticket->label() ?? '',
          $ticket->getTicketNumber(),
          $ticket->getPriority(),
          $ticket->getStatus(),
          $messagesText,
        ],
        self::SUMMARIZE_PROMPT,
      );

      // Fast tier — summarization is a simple extraction task.
      $modelConfig = $this->modelRouter?->route('fast') ?? [];
      $modelId = $modelConfig['model_id'] ?? NULL;

      if (!$modelId) {
        $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
        if (empty($defaults)) {
          return '';
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

      $summary = trim($response->getNormalized()->getText());

      $this->logger->info('AI summary generated for ticket @id (@words words).', [
        '@id' => $ticket->id(),
        '@words' => str_word_count($summary),
      ]);

      return $summary;
    }
    catch (\Exception $e) {
      $this->logger->warning('AI summarization failed for ticket @id: @msg', [
        '@id' => $ticket->id() ?? 'new',
        '@msg' => $e->getMessage(),
      ]);
      return '';
    }
  }

}
