<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Psr\Log\LoggerInterface;

/**
 * AI-powered onboarding step recommendations.
 *
 * Consumes SmartBaseAgent (fast tier) to generate personalized
 * onboarding recommendations based on user profile, vertical,
 * and current progress.
 *
 * GAP-AUD-001: Onboarding Wizard AI
 */
class OnboardingAiRecommendationService {

  /**
   * The AI agent for generating recommendations (optional).
   *
   * @var object|null
   */
  protected ?object $aiAgent;

  /**
   * Constructs an OnboardingAiRecommendationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param object|null $aiAgent
   *   An AI agent (SmartBaseAgent) for generating recommendations. Optional.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    ?object $aiAgent = NULL,
  ) {
    $this->aiAgent = $aiAgent;
  }

  /**
   * Gets AI-powered recommendations for a user's onboarding journey.
   *
   * @param int $userId
   *   The user ID.
   * @param string $vertical
   *   The business vertical (e.g., 'agroconecta', 'empleabilidad').
   * @param array $progress
   *   Current onboarding progress data from OrchestratorService.
   *
   * @return array
   *   Array of recommendations, each with:
   *   - step_id: string — The recommended step identifier
   *   - reason: string — Why this step is recommended
   *   - priority: string — 'high', 'medium', or 'low'
   *   Returns empty array if AI is unavailable.
   */
  public function getRecommendations(int $userId, string $vertical, array $progress): array {
    if (!$this->aiAgent || !method_exists($this->aiAgent, 'callAiApi')) {
      return [];
    }

    try {
      $completedSteps = $progress['completed_steps'] ?? [];
      $percentage = $progress['progress_percentage'] ?? 0;

      $prompt = $this->buildRecommendationPrompt($vertical, $completedSteps, $percentage);

      // Apply identity rule.
      $prompt = AIIdentityRule::apply($prompt);

      // Call AI with fast tier for low latency.
      $response = $this->aiAgent->callAiApi($prompt, 'fast');

      if (empty($response)) {
        return [];
      }

      return $this->parseRecommendations($response);
    }
    catch (\Exception $e) {
      $this->logger->warning('AI recommendation failed for user @user: @error', [
        '@user' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Builds the prompt for generating recommendations.
   *
   * @param string $vertical
   *   The business vertical.
   * @param array $completedSteps
   *   Already completed step IDs.
   * @param int $percentage
   *   Current completion percentage.
   *
   * @return string
   *   The prompt text.
   */
  protected function buildRecommendationPrompt(string $vertical, array $completedSteps, int $percentage): string {
    $stepsJson = !empty($completedSteps) ? json_encode($completedSteps) : '[]';

    return <<<PROMPT
You are an onboarding assistant for {$vertical} vertical.

The user has completed {$percentage}% of onboarding.
Completed steps: {$stepsJson}

Based on their progress, recommend the top 3 next steps they should take.
Consider:
- What's most impactful for their vertical
- What builds on what they've already done
- Quick wins first to maintain momentum

Respond ONLY with a JSON array:
[
  {"step_id": "step_name", "reason": "Brief reason", "priority": "high|medium|low"},
  ...
]
PROMPT;
  }

  /**
   * Parses AI response into structured recommendations.
   *
   * @param string $response
   *   The raw AI response text.
   *
   * @return array
   *   Parsed recommendations array.
   */
  protected function parseRecommendations(string $response): array {
    // Extract JSON from response (may be wrapped in markdown code block).
    $json = $response;
    if (preg_match('/\[[\s\S]*\]/', $response, $matches)) {
      $json = $matches[0];
    }

    $parsed = json_decode($json, TRUE);

    if (!is_array($parsed)) {
      return [];
    }

    $recommendations = [];
    foreach ($parsed as $item) {
      if (!is_array($item) || empty($item['step_id'])) {
        continue;
      }
      $recommendations[] = [
        'step_id' => (string) $item['step_id'],
        'reason' => (string) ($item['reason'] ?? ''),
        'priority' => in_array($item['priority'] ?? '', ['high', 'medium', 'low'], TRUE)
          ? $item['priority']
          : 'medium',
      ];
    }

    return array_slice($recommendations, 0, 5);
  }

}
