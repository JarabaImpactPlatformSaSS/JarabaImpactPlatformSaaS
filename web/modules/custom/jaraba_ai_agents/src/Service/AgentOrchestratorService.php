<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Orchestrator for Autonomous AI Agent workflows.
 *
 * This service takes a high-level goal, plans the necessary steps,
 * and executes tools from the ToolRegistry to achieve it.
 */
class AgentOrchestratorService {

  public function __construct(
    protected AiProviderPluginManager $aiProvider,
    protected ConfigFactoryInterface $configFactory,
    protected AgentToolRegistry $toolRegistry,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Plans and executes a workflow to achieve a goal.
   *
   * @param string $goal
   *   The user's goal (e.g., "Analyze my sales and create a promotion").
   * @param array $context
   *   Additional context (tenant_id, user_id).
   *
   * @return array
   *   Execution results and log of actions taken.
   */
  public function runWorkflow(string $goal, array $context): array {
    $tools = $this->toolRegistry->getTools();
    
    // 1. Planning: Ask LLM to break down the goal into tool calls.
    $plan = $this->generatePlan($goal, $tools, $context);
    
    $results = [];
    $executionLog = [];

    // 2. Execution Loop.
    foreach ($plan['steps'] as $step) {
      $toolName = $step['tool'];
      $params = $step['parameters'];
      
      // Add context to params if needed.
      $params['tenantId'] = $context['tenant_id'];

      try {
        $output = $this->toolRegistry->executeTool($toolName, $params);
        $results[$toolName] = $output;
        $executionLog[] = [
          'step' => $step['description'],
          'tool' => $toolName,
          'status' => 'success',
          'output_summary' => is_array($output) ? 'Data returned' : (string) $output,
        ];
      }
      catch (\Exception $e) {
        $this->logger->error('Agent execution failed at step "@step": @error', [
          '@step' => $step['description'],
          '@error' => $e->getMessage(),
        ]);
        $executionLog[] = [
          'step' => $step['description'],
          'tool' => $toolName,
          'status' => 'error',
          'error' => $e->getMessage(),
        ];
        break; // Stop on first error for safety.
      }
    }

    return [
      'success' => count($executionLog) === count($plan['steps']),
      'goal' => $goal,
      'log' => $executionLog,
      'final_output' => $this->summarizeResults($results, $goal),
    ];
  }

  /**
   * Generates a step-by-step plan using an LLM.
   */
  protected function generatePlan(string $goal, array $tools, array $context): array {
    // Note: In a real implementation, we'd use a robust prompt.
    // For the prototype, we return a structured array.
    $this->logger->info('Generating plan for goal: @goal', ['@goal' => $goal]);
    
    // Simulate LLM response for "Analyze sales and create newsletter"
    if (str_contains($goal, 'newsletter')) {
      return [
        'steps' => [
          [
            'description' => 'Calculate shipping impact',
            'tool' => 'agro_calculate_shipping',
            'parameters' => ['items' => [['product_id' => 1, 'quantity' => 1]], 'postalCode' => '28001'],
          ],
          [
            'description' => 'Create newsletter draft from recent article',
            'tool' => 'hub_create_newsletter',
            'parameters' => ['articleId' => 1],
          ]
        ]
      ];
    }

    return ['steps' => []];
  }

  /**
   * Summarizes the execution results.
   */
  protected function summarizeResults(array $results, string $goal): string {
    return "Goal achieved: $goal. Actions performed: " . implode(', ', array_keys($results));
  }

}
