<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Psr\Log\LoggerInterface;

/**
 * ReAct Loop Service — Plan-Execute-Reflect (FIX-038).
 *
 * Orchestrates multi-step reasoning cycles for agents:
 * PLAN (decompose objective) -> EXECUTE (run step with tools) ->
 * OBSERVE (collect results) -> REFLECT (adjust plan) -> FINISH.
 */
class ReActLoopService
{

    /**
     * Maximum iterations to prevent infinite loops.
     */
    protected const MAX_DEFAULT_STEPS = 10;

    /**
     * Constructor.
     */
    public function __construct(
        protected ToolRegistry $toolRegistry,
        protected AIObservabilityService $observability,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Runs a ReAct loop for an objective.
     *
     * @param callable $llmCallable
     *   Callable that takes (prompt, options) and returns AI result array.
     * @param string $objective
     *   The high-level objective to accomplish.
     * @param array $context
     *   Execution context (tenant_id, vertical, agent_id, etc.).
     * @param int $maxSteps
     *   Maximum steps before forcing finish.
     *
     * @return array
     *   Result with: success, final_answer, steps[], total_steps, tools_used.
     */
    public function run(callable $llmCallable, string $objective, array $context = [], int $maxSteps = self::MAX_DEFAULT_STEPS): array
    {
        $steps = [];
        $observations = [];
        $toolsUsed = [];

        // Build available tools documentation.
        $toolDocs = $this->toolRegistry->generateToolsDocumentation();

        for ($i = 0; $i < $maxSteps; $i++) {
            $stepNumber = $i + 1;

            // Build step prompt.
            $prompt = $this->buildStepPrompt($objective, $steps, $observations, $toolDocs, $stepNumber, $maxSteps);

            // Call LLM.
            $result = $llmCallable($prompt, ['temperature' => 0.3]);

            if (!($result['success'] ?? FALSE)) {
                $this->logger->error('ReAct step @step failed: @error', [
                    '@step' => $stepNumber,
                    '@error' => $result['error'] ?? 'unknown',
                ]);
                break;
            }

            $text = $result['data']['text'] ?? '';

            // Parse step response.
            $parsed = $this->parseStepResponse($text);

            $step = [
                'number' => $stepNumber,
                'thought' => $parsed['thought'] ?? '',
                'action' => $parsed['action'] ?? 'none',
                'action_input' => $parsed['action_input'] ?? [],
                'observation' => '',
            ];

            // Check if finished.
            if ($parsed['action'] === 'FINISH') {
                $step['observation'] = $parsed['final_answer'] ?? $text;
                $steps[] = $step;

                $this->logger->info('ReAct loop completed in @steps steps.', ['@steps' => $stepNumber]);

                return [
                    'success' => TRUE,
                    'final_answer' => $parsed['final_answer'] ?? $text,
                    'steps' => $steps,
                    'total_steps' => $stepNumber,
                    'tools_used' => array_unique($toolsUsed),
                ];
            }

            // Execute tool if requested.
            if (!empty($parsed['action']) && $parsed['action'] !== 'none') {
                $toolId = $parsed['action'];
                $toolParams = $parsed['action_input'] ?? [];

                $toolResult = $this->toolRegistry->execute($toolId, $toolParams, $context);
                $step['observation'] = json_encode($toolResult, JSON_UNESCAPED_UNICODE);
                $toolsUsed[] = $toolId;

                $observations[] = [
                    'step' => $stepNumber,
                    'tool' => $toolId,
                    'result' => $toolResult,
                ];

                // HAL-AI-18: If tool requires approval, stop the loop early
                // and inform the caller that human approval is needed.
                if (!empty($toolResult['pending_approval'])) {
                    $steps[] = $step;
                    return [
                        'success' => FALSE,
                        'pending_approval' => TRUE,
                        'approval_id' => $toolResult['approval_id'] ?? NULL,
                        'final_answer' => "Tool '{$toolId}' requires human approval before execution.",
                        'steps' => $steps,
                        'total_steps' => $stepNumber,
                        'tools_used' => array_unique($toolsUsed),
                    ];
                }
            }

            $steps[] = $step;

            // Log step for observability.
            $this->observability->log([
                'agent_id' => $context['agent_id'] ?? 'react_loop',
                'action' => "react_step_{$stepNumber}",
                'tier' => 'balanced',
                'model_id' => '',
                'tenant_id' => $context['tenant_id'] ?? NULL,
                'vertical' => $context['vertical'] ?? 'general',
                'input_tokens' => (int) ceil(mb_strlen($prompt) / 4),
                'output_tokens' => (int) ceil(mb_strlen($text) / 4),
                'duration_ms' => 0,
                'success' => TRUE,
            ]);
        }

        // Max steps reached without FINISH.
        $this->logger->warning('ReAct loop reached max steps (@max) without finishing.', ['@max' => $maxSteps]);

        $lastObservation = !empty($steps) ? end($steps)['observation'] : '';
        return [
            'success' => TRUE,
            'final_answer' => $lastObservation ?: 'Max steps reached.',
            'steps' => $steps,
            'total_steps' => count($steps),
            'tools_used' => array_unique($toolsUsed),
            'max_steps_reached' => TRUE,
        ];
    }

    /**
     * Builds the prompt for each ReAct step.
     */
    protected function buildStepPrompt(
        string $objective,
        array $previousSteps,
        array $observations,
        string $toolDocs,
        int $stepNumber,
        int $maxSteps,
    ): string {
        $prompt = "<react_loop>\n";
        $prompt .= "<objective>{$objective}</objective>\n";
        $prompt .= "<step>{$stepNumber}/{$maxSteps}</step>\n";

        if (!empty($toolDocs)) {
            $prompt .= "\n{$toolDocs}\n";
        }

        if (!empty($previousSteps)) {
            $prompt .= "\n<previous_steps>\n";
            foreach ($previousSteps as $step) {
                $prompt .= "Step {$step['number']}:\n";
                $prompt .= "  Thought: {$step['thought']}\n";
                $prompt .= "  Action: {$step['action']}\n";
                if (!empty($step['observation'])) {
                    $prompt .= "  Observation: {$step['observation']}\n";
                }
            }
            $prompt .= "</previous_steps>\n";
        }

        $prompt .= "\n<instructions>\n";
        $prompt .= "Respond in JSON format:\n";
        $prompt .= '{"thought": "your reasoning", "action": "tool_id or FINISH", "action_input": {"param": "value"}, "final_answer": "only if action=FINISH"}';
        $prompt .= "\n</instructions>\n";
        $prompt .= "</react_loop>";

        return $prompt;
    }

    /**
     * Parses a step response from the LLM.
     */
    protected function parseStepResponse(string $text): array
    {
        // Try JSON parse.
        $cleaned = preg_replace('/```(?:json)?\s*/is', '', $text);
        $cleaned = preg_replace('/\s*```/is', '', $cleaned);

        if (preg_match('/(\{[\s\S]*\})/m', $cleaned, $matches)) {
            $decoded = json_decode($matches[1], TRUE);
            if ($decoded) {
                return $decoded;
            }
        }

        // Fallback: treat as final answer.
        return [
            'thought' => 'Could not parse structured response.',
            'action' => 'FINISH',
            'action_input' => [],
            'final_answer' => $text,
        ];
    }

}
