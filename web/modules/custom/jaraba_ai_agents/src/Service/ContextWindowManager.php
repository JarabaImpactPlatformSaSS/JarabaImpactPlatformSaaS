<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Psr\Log\LoggerInterface;

/**
 * Context Window Manager (FIX-033).
 *
 * Estimates tokens, verifies model limits, and progressively trims
 * (RAG context -> tool docs -> knowledge) if the prompt exceeds
 * the model's context window.
 */
class ContextWindowManager
{

    /**
     * Model token limits.
     *
     * @var array<string, int>
     */
    protected const MODEL_LIMITS = [
        // Claude models.
        'claude-3-5-sonnet-20241022' => 200000,
        'claude-sonnet-4-6' => 200000,
        'claude-opus-4-6' => 200000,
        'claude-3-5-haiku-20241022' => 200000,
        'claude-haiku-4-5-20251001' => 200000,
        // OpenAI models.
        'gpt-4o' => 128000,
        'gpt-4o-mini' => 128000,
        'gpt-4-turbo' => 128000,
        // Default.
        'default' => 128000,
    ];

    /**
     * Safety margin: reserve 20% for output.
     */
    protected const SAFETY_MARGIN = 0.8;

    /**
     * Constructor.
     */
    public function __construct(
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Estimates token count for text.
     *
     * Approximation: ~4 characters per token for mixed EN/ES content.
     *
     * @param string $text
     *   The text.
     *
     * @return int
     *   Estimated tokens.
     */
    public function estimateTokens(string $text): int
    {
        return (int) ceil(mb_strlen($text) / 4);
    }

    /**
     * Gets the token limit for a model.
     *
     * @param string $modelId
     *   The model ID.
     *
     * @return int
     *   Token limit.
     */
    public function getModelLimit(string $modelId): int
    {
        return self::MODEL_LIMITS[$modelId] ?? self::MODEL_LIMITS['default'];
    }

    /**
     * Fits the system prompt into the context window.
     *
     * Progressive trimming order:
     * 1. RAG context (between <rag_context> tags)
     * 2. Tool documentation (between <available_tools> tags)
     * 3. Knowledge context (between <knowledge> tags)
     * 4. Vertical context (between <vertical_context> tags)
     *
     * @param string $systemPrompt
     *   The full system prompt.
     * @param string $userPrompt
     *   The user prompt.
     * @param string $modelId
     *   The target model ID.
     *
     * @return string
     *   Trimmed system prompt that fits the window.
     */
    public function fitToWindow(string $systemPrompt, string $userPrompt, string $modelId): string
    {
        $maxTokens = (int) ($this->getModelLimit($modelId) * self::SAFETY_MARGIN);
        $userTokens = $this->estimateTokens($userPrompt);
        $availableForSystem = $maxTokens - $userTokens;

        $currentTokens = $this->estimateTokens($systemPrompt);

        if ($currentTokens <= $availableForSystem) {
            return $systemPrompt;
        }

        $this->logger->info('Context window exceeded: @current/@max tokens. Trimming.', [
            '@current' => $currentTokens + $userTokens,
            '@max' => $maxTokens,
        ]);

        // Progressive trimming.
        $trimSections = [
            'rag_context',
            'available_tools',
            'knowledge',
            'vertical_context',
        ];

        foreach ($trimSections as $section) {
            $pattern = "/<{$section}>[\s\S]*?<\/{$section}>/";
            $trimmed = preg_replace($pattern, "<{$section}>[Trimmed to fit context window]</{$section}>", $systemPrompt);
            if ($trimmed !== NULL) {
                $systemPrompt = $trimmed;
            }

            $currentTokens = $this->estimateTokens($systemPrompt);
            if ($currentTokens <= $availableForSystem) {
                break;
            }
        }

        // Last resort: hard truncate.
        if ($this->estimateTokens($systemPrompt) > $availableForSystem) {
            $maxChars = $availableForSystem * 4;
            $systemPrompt = mb_substr($systemPrompt, 0, $maxChars);
            $this->logger->warning('System prompt hard-truncated to @chars chars.', ['@chars' => $maxChars]);
        }

        return $systemPrompt;
    }

}
