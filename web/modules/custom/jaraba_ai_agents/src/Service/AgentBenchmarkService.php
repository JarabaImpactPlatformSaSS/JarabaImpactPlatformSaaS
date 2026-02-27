<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for automated agent benchmarking with golden test cases (S5-02).
 *
 * Runs test suites against agents, compares versions, and stores results.
 * Integrates with QualityEvaluatorService for LLM-as-Judge scoring.
 */
class AgentBenchmarkService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected QualityEvaluatorService $qualityEvaluator,
    protected LoggerInterface $logger,
    protected ?AIObservabilityService $observability = NULL,
  ) {}

  /**
   * Runs a benchmark suite against an agent.
   *
   * @param string $agentId
   *   The agent service ID.
   * @param array $testCases
   *   Array of test cases: [['input' => '...', 'expected_output' => '...', 'criteria' => [...]]].
   * @param array $options
   *   Optional: version, description, max_concurrent.
   *
   * @return array
   *   BenchmarkResult with scores, pass/fail, and per-case details.
   */
  public function runBenchmark(string $agentId, array $testCases, array $options = []): array {
    $startTime = microtime(TRUE);
    $results = [];
    $totalScore = 0.0;
    $passCount = 0;
    $version = $options['version'] ?? '1.0.0';

    foreach ($testCases as $index => $testCase) {
      $input = $testCase['input'] ?? '';
      $expectedOutput = $testCase['expected_output'] ?? '';
      $criteria = $testCase['criteria'] ?? [];

      try {
        // Execute agent.
        $agentResult = $this->executeAgent($agentId, $input);
        $actualOutput = $agentResult['data']['text'] ?? '';

        // Evaluate using QualityEvaluatorService (LLM-as-Judge).
        $evaluation = $this->qualityEvaluator->evaluate(
          $input,
          $actualOutput,
          !empty($criteria) ? $criteria : NULL,
        );

        $score = $evaluation['overall_score'] ?? 0.0;
        $passed = $score >= ($testCase['threshold'] ?? 0.7);
        $totalScore += $score;
        if ($passed) {
          $passCount++;
        }

        $results[] = [
          'index' => $index,
          'input' => mb_substr($input, 0, 200),
          'expected_summary' => mb_substr($expectedOutput, 0, 200),
          'actual_summary' => mb_substr($actualOutput, 0, 200),
          'score' => $score,
          'passed' => $passed,
          'criteria_scores' => $evaluation['criteria_scores'] ?? [],
          'evaluation_reasoning' => $evaluation['reasoning'] ?? '',
        ];
      }
      catch (\Exception $e) {
        $results[] = [
          'index' => $index,
          'input' => mb_substr($input, 0, 200),
          'score' => 0.0,
          'passed' => FALSE,
          'error' => $e->getMessage(),
        ];
      }
    }

    $totalCases = count($testCases);
    $averageScore = $totalCases > 0 ? $totalScore / $totalCases : 0.0;
    $passRate = $totalCases > 0 ? $passCount / $totalCases : 0.0;
    $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

    $benchmarkResult = [
      'agent_id' => $agentId,
      'version' => $version,
      'total_cases' => $totalCases,
      'passed' => $passCount,
      'failed' => $totalCases - $passCount,
      'average_score' => round($averageScore, 4),
      'pass_rate' => round($passRate, 4),
      'duration_ms' => $durationMs,
      'results' => $results,
      'timestamp' => time(),
    ];

    // Store result as entity.
    $this->storeBenchmarkResult($benchmarkResult, $options);

    // Log to observability.
    if ($this->observability) {
      $this->observability->log([
        'agent_id' => $agentId,
        'action' => 'benchmark',
        'tier' => 'benchmark',
        'success' => $passRate >= 0.7,
        'duration_ms' => $durationMs,
        'input_tokens' => 0,
        'output_tokens' => 0,
      ]);
    }

    return $benchmarkResult;
  }

  /**
   * Compares benchmark results between two versions.
   *
   * @param string $agentId
   *   The agent ID.
   * @param string $versionA
   *   First version.
   * @param string $versionB
   *   Second version.
   *
   * @return array
   *   Comparison with score deltas and regression analysis.
   */
  public function compareVersions(string $agentId, string $versionA, string $versionB): array {
    $resultsA = $this->getLatestResult($agentId, $versionA);
    $resultsB = $this->getLatestResult($agentId, $versionB);

    if (!$resultsA || !$resultsB) {
      return [
        'error' => 'One or both versions have no benchmark results.',
        'has_results_a' => !empty($resultsA),
        'has_results_b' => !empty($resultsB),
      ];
    }

    $scoreA = $resultsA['average_score'] ?? 0;
    $scoreB = $resultsB['average_score'] ?? 0;
    $delta = round($scoreB - $scoreA, 4);
    $regression = $delta < -0.05;

    return [
      'agent_id' => $agentId,
      'version_a' => $versionA,
      'version_b' => $versionB,
      'score_a' => $scoreA,
      'score_b' => $scoreB,
      'delta' => $delta,
      'improvement' => $delta > 0,
      'regression' => $regression,
      'pass_rate_a' => $resultsA['pass_rate'] ?? 0,
      'pass_rate_b' => $resultsB['pass_rate'] ?? 0,
    ];
  }

  /**
   * Gets the latest benchmark result for an agent+version.
   */
  public function getLatestResult(string $agentId, ?string $version = NULL): ?array {
    try {
      $query = $this->entityTypeManager->getStorage('agent_benchmark_result')
        ->getQuery()
        ->condition('agent_id', $agentId)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->accessCheck(FALSE);

      if ($version) {
        $query->condition('version', $version);
      }

      $ids = $query->execute();
      if (empty($ids)) {
        return NULL;
      }

      $entity = $this->entityTypeManager->getStorage('agent_benchmark_result')
        ->load(reset($ids));
      if (!$entity) {
        return NULL;
      }

      $resultData = json_decode($entity->get('result_data')->value ?? '{}', TRUE);
      return $resultData ?: NULL;
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to load benchmark result: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Executes an agent by service ID.
   */
  protected function executeAgent(string $agentId, string $input): array {
    $serviceId = $this->resolveAgentServiceId($agentId);
    if (!\Drupal::hasService($serviceId)) {
      throw new \RuntimeException("Agent service not found: {$serviceId}");
    }

    $agent = \Drupal::service($serviceId);
    return $agent->execute('benchmark_test', ['message' => $input, 'query' => $input]);
  }

  /**
   * Resolves agent ID to Drupal service ID.
   */
  protected function resolveAgentServiceId(string $agentId): string {
    $map = [
      'smart_marketing' => 'jaraba_ai_agents.smart_marketing_agent',
      'smart_employability_copilot' => 'jaraba_candidate.agent.smart_employability_copilot',
      'smart_legal_copilot' => 'jaraba_legal_intelligence.smart_legal_copilot_agent',
      'smart_content_writer' => 'jaraba_content_hub.smart_content_writer_agent',
      'storytelling' => 'jaraba_ai_agents.storytelling_agent',
      'customer_experience' => 'jaraba_ai_agents.customer_experience_agent',
      'support' => 'jaraba_ai_agents.support_agent',
      'sales' => 'jaraba_ai_agents.sales_agent',
    ];
    return $map[$agentId] ?? "jaraba_ai_agents.{$agentId}_agent";
  }

  /**
   * Stores benchmark result as entity.
   */
  protected function storeBenchmarkResult(array $result, array $options = []): void {
    try {
      $storage = $this->entityTypeManager->getStorage('agent_benchmark_result');
      $entity = $storage->create([
        'agent_id' => $result['agent_id'],
        'version' => $result['version'],
        'average_score' => $result['average_score'],
        'pass_rate' => $result['pass_rate'],
        'total_cases' => $result['total_cases'],
        'passed_cases' => $result['passed'],
        'failed_cases' => $result['failed'],
        'duration_ms' => $result['duration_ms'],
        'result_data' => json_encode($result, JSON_UNESCAPED_UNICODE),
        'description' => $options['description'] ?? '',
      ]);
      $entity->save();
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to store benchmark result: @error', ['@error' => $e->getMessage()]);
    }
  }

}
