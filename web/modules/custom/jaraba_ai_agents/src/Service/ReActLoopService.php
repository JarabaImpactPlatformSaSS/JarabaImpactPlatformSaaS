<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Psr\Log\LoggerInterface;

/**
 * ReAct Loop Service — Plan-Execute-Reflect (FIX-038 + S3-02).
 *
 * Orchestrates multi-step reasoning cycles for agents:
 * PLAN (decompose objective) -> EXECUTE (run step with tools) ->
 * OBSERVE (collect results) -> REFLECT (adjust plan) -> FINISH.
 *
 * S3-02 enhancements:
 * - Structured THOUGHT/ACTION/OBSERVATION prompt format.
 * - Duplicate action detection (3 identical actions = forced FINISH).
 * - Enhanced regex + JSON hybrid parsing.
 * - Per-step observability logging with operation_name.
 */
class ReActLoopService
{

    /**
     * Maximum iterations to prevent infinite loops.
     */
    protected const MAX_DEFAULT_STEPS = 10;

    /**
     * Consecutive duplicate actions before forced finish.
     */
    protected const DUPLICATE_THRESHOLD = 3;

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
        $recentActionHashes = [];

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

            // S3-02: Duplicate action detection.
            if (!empty($parsed['action']) && $parsed['action'] !== 'none') {
                $actionHash = $parsed['action'] . '::' . md5(json_encode($parsed['action_input'] ?? []));
                $recentActionHashes[] = $actionHash;

                // Keep only last N hashes.
                if (count($recentActionHashes) > self::DUPLICATE_THRESHOLD) {
                    array_shift($recentActionHashes);
                }

                // Check if all recent actions are identical.
                if (count($recentActionHashes) === self::DUPLICATE_THRESHOLD
                    && count(array_unique($recentActionHashes)) === 1) {
                    $step['observation'] = 'Loop detected: same action repeated ' . self::DUPLICATE_THRESHOLD . ' times. Forcing completion.';
                    $steps[] = $step;

                    $this->logger->warning('ReAct loop duplicate detected: @action repeated @n times.', [
                        '@action' => $parsed['action'],
                        '@n' => self::DUPLICATE_THRESHOLD,
                    ]);

                    return [
                        'success' => TRUE,
                        'final_answer' => 'Could not make further progress. Last action "' . $parsed['action'] . '" was repeated ' . self::DUPLICATE_THRESHOLD . ' times without new results.',
                        'steps' => $steps,
                        'total_steps' => $stepNumber,
                        'tools_used' => array_unique($toolsUsed),
                        'loop_detected' => TRUE,
                    ];
                }
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
                'operation_name' => "react_step_{$stepNumber}",
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
     * Builds the prompt for each ReAct step (S3-02 enhanced).
     *
     * Uses structured THOUGHT/ACTION/OBSERVATION format with explicit
     * instructions for the LLM to follow.
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
                $prompt .= "  THOUGHT: {$step['thought']}\n";
                $prompt .= "  ACTION: {$step['action']}\n";
                if (!empty($step['action_input'])) {
                    $inputJson = is_string($step['action_input']) ? $step['action_input'] : json_encode($step['action_input'], JSON_UNESCAPED_UNICODE);
                    $prompt .= "  ACTION_INPUT: {$inputJson}\n";
                }
                if (!empty($step['observation'])) {
                    // Truncate long observations to avoid context overflow.
                    $obs = mb_substr($step['observation'], 0, 1000);
                    if (mb_strlen($step['observation']) > 1000) {
                        $obs .= '... [truncated]';
                    }
                    $prompt .= "  OBSERVATION: {$obs}\n";
                }
            }
            $prompt .= "</previous_steps>\n";
        }

        $prompt .= "\n<instructions>\n";
        $prompt .= "Responde SIEMPRE en formato JSON con estas claves:\n";
        $prompt .= '{"thought": "tu razonamiento sobre el paso actual", "action": "nombre_herramienta o FINISH", "action_input": {"param": "valor"}, "final_answer": "solo si action=FINISH"}';
        $prompt .= "\n\nReglas:\n";
        $prompt .= "- Si ya tienes la respuesta, usa action=FINISH y pon tu respuesta en final_answer.\n";
        $prompt .= "- Si necesitas mas informacion, usa una herramienta disponible.\n";
        $prompt .= "- NUNCA repitas la misma accion con los mismos parametros. Si un resultado no es util, prueba otro enfoque.\n";
        $prompt .= "- Responde SOLO el JSON, sin texto adicional.\n";
        $prompt .= "</instructions>\n";
        $prompt .= "</react_loop>";

        return $prompt;
    }

    /**
     * Parses a step response from the LLM (S3-02 enhanced).
     *
     * Uses hybrid approach: JSON first, then regex fallback for
     * THOUGHT/ACTION/ACTION_INPUT format.
     */
    protected function parseStepResponse(string $text): array
    {
        // 1. Try JSON parse (primary format).
        $cleaned = preg_replace('/```(?:json)?\s*/is', '', $text);
        $cleaned = preg_replace('/\s*```/is', '', $cleaned);

        if (preg_match('/(\{[\s\S]*\})/m', $cleaned, $matches)) {
            $decoded = json_decode($matches[1], TRUE);
            if ($decoded && isset($decoded['action'])) {
                // Normalize action to uppercase FINISH.
                if (strtoupper($decoded['action']) === 'FINISH') {
                    $decoded['action'] = 'FINISH';
                }
                return $decoded;
            }
        }

        // 2. Regex fallback for THOUGHT/ACTION/ACTION_INPUT format.
        $thought = '';
        $action = '';
        $actionInput = [];
        $finalAnswer = '';

        if (preg_match('/THOUGHT:\s*(.+?)(?=ACTION:|$)/si', $text, $m)) {
            $thought = trim($m[1]);
        }
        if (preg_match('/ACTION:\s*(.+?)(?=ACTION_INPUT:|$)/si', $text, $m)) {
            $action = trim($m[1]);
        }
        if (preg_match('/ACTION_INPUT:\s*(.+)/si', $text, $m)) {
            $jsonStr = trim($m[1]);
            $actionInput = json_decode($jsonStr, TRUE) ?? [];
            if (strtoupper($action) === 'FINISH' && isset($actionInput['answer'])) {
                $finalAnswer = $actionInput['answer'];
            }
        }

        if (!empty($action)) {
            if (strtoupper($action) === 'FINISH') {
                $action = 'FINISH';
            }
            return [
                'thought' => $thought,
                'action' => $action,
                'action_input' => $actionInput,
                'final_answer' => $finalAnswer ?: $thought,
            ];
        }

        // 3. Last resort: treat as final answer.
        return [
            'thought' => 'Could not parse structured response.',
            'action' => 'FINISH',
            'action_input' => [],
            'final_answer' => $text,
        ];
    }

}
