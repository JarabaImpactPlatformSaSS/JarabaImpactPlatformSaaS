<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;

/**
 * Provides inline AI suggestions for entity form fields.
 *
 * GAP-AUD-009: Sparkle button suggestions powered by AI.
 * Uses the fast tier (low latency, low cost) for real-time suggestions.
 *
 * @see INLINE-AI-001
 * @see AI-IDENTITY-001
 * @see OPTIONAL-SERVICE-DI-001
 */
class InlineAiService
{

    /**
     * Maximum number of suggestions per request.
     */
    private const MAX_SUGGESTIONS = 3;

    /**
     * Constructs InlineAiService.
     *
     * @param \Psr\Log\LoggerInterface $logger
     *   The logger channel.
     * @param object|null $aiAgent
     *   Optional AI agent for generating suggestions.
     *   Injected as @?jaraba_ai_agents.smart_marketing_agent.
     */
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly ?object $aiAgent = NULL,
    ) {}

    /**
     * Generates AI suggestions for a form field.
     *
     * @param string $fieldName
     *   The machine name of the field.
     * @param string $currentValue
     *   The current field value.
     * @param string $entityType
     *   The entity type ID.
     * @param array $context
     *   Additional context (e.g., other field values).
     *
     * @return array
     *   Array with 'suggestions' key containing string suggestions.
     */
    public function suggest(string $fieldName, string $currentValue, string $entityType, array $context = []): array
    {
        if ($this->aiAgent === NULL) {
            return ['suggestions' => []];
        }

        try {
            $prompt = $this->buildSuggestionPrompt($fieldName, $currentValue, $entityType, $context);

            // Use the agent's execute method with fast tier.
            $result = $this->aiAgent->execute([
                'action' => 'inline_suggest',
                'prompt' => $prompt,
                'tier' => 'fast',
            ]);

            $suggestions = $this->parseSuggestions($result);

            return ['suggestions' => array_slice($suggestions, 0, self::MAX_SUGGESTIONS)];
        }
        catch (\Exception $e) {
            $this->logger->warning('Inline AI suggestion failed for @field: @error', [
                '@field' => $fieldName,
                '@error' => $e->getMessage(),
            ]);
            return ['suggestions' => []];
        }
    }

    /**
     * Builds the suggestion prompt for a specific field.
     *
     * @param string $fieldName
     *   The field machine name.
     * @param string $currentValue
     *   Current value of the field.
     * @param string $entityType
     *   The entity type.
     * @param array $context
     *   Additional context.
     *
     * @return string
     *   The composed prompt with identity rule.
     */
    protected function buildSuggestionPrompt(string $fieldName, string $currentValue, string $entityType, array $context = []): string
    {
        $maxLength = $this->getFieldMaxLength($fieldName);
        $lengthConstraint = $maxLength > 0
            ? "Each suggestion should be under {$maxLength} characters."
            : 'Keep suggestions concise and focused.';

        $basePrompt = 'You are an expert content writer for a SaaS platform. '
            . 'Generate 3 alternative suggestions for the "' . $fieldName . '" field of a "' . $entityType . '" entity. '
            . 'Current value: "' . $currentValue . '". '
            . 'Return ONLY a JSON array of 3 strings. No explanation. '
            . $lengthConstraint;

        // Use short identity rule for token efficiency (AI-IDENTITY-001).
        return AIIdentityRule::apply($basePrompt, TRUE);
    }

    /**
     * Parses AI response into suggestion strings.
     *
     * @param array $result
     *   The AI agent execution result.
     *
     * @return array
     *   Array of suggestion strings.
     */
    protected function parseSuggestions(array $result): array
    {
        $content = $result['content'] ?? $result['response'] ?? '';
        if (empty($content)) {
            return [];
        }

        // Try to parse as JSON array.
        if (is_string($content)) {
            // Extract JSON array from response (may contain surrounding text).
            if (preg_match('/\[.*\]/s', $content, $matches)) {
                $decoded = json_decode($matches[0], TRUE);
                if (is_array($decoded)) {
                    return array_filter($decoded, 'is_string');
                }
            }
        }

        if (is_array($content)) {
            return array_filter($content, 'is_string');
        }

        return [];
    }

    /**
     * Returns the max length for a known field.
     *
     * @param string $fieldName
     *   The field machine name.
     *
     * @return int
     *   Max character length, or 0 for unlimited.
     */
    protected function getFieldMaxLength(string $fieldName): int
    {
        $limits = [
            'title' => 255,
            'seo_title' => 70,
            'seo_description' => 160,
            'answer_capsule' => 200,
            'excerpt' => 300,
        ];

        return $limits[$fieldName] ?? 0;
    }

}
