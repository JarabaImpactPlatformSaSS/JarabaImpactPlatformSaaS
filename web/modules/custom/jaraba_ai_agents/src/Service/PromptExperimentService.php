<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Prompt A/B Testing — Integracion con jaraba_ab_testing (F11).
 *
 * Creates prompt experiments, assigns prompt variants to agent calls,
 * records quality scores as conversions, and integrates with
 * QualityEvaluatorService for automatic evaluation.
 */
class PromptExperimentService {

  use StringTranslationTrait;

  protected const EXPERIMENT_TYPE = 'prompt_variant';
  protected const QUALITY_THRESHOLD = 0.7;

  /**
   * Constructor.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected QualityEvaluatorService $qualityEvaluator,
    protected AIObservabilityService $observability,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Creates a new prompt A/B experiment.
   *
   * @param string $name
   *   Machine name for the experiment.
   * @param string $label
   *   Human-readable label.
   * @param array $variants
   *   Array of variant configs, each with:
   *   - label: string
   *   - system_prompt: string
   *   - temperature: float (0.0-1.0)
   *   - model_tier: string (fast|balanced|premium)
   *   - is_control: bool
   * @param int $tenantId
   *   Tenant ID.
   * @param array $options
   *   Optional: confidence_threshold, minimum_sample_size, traffic_percentage.
   *
   * @return array
   *   Result with experiment_id and variant_ids.
   */
  public function createExperiment(string $name, string $label, array $variants, int $tenantId, array $options = []): array {
    try {
      $experimentStorage = $this->entityTypeManager->getStorage('ab_experiment');
      $variantStorage = $this->entityTypeManager->getStorage('ab_variant');

      // Create experiment.
      $experiment = $experimentStorage->create([
        'label' => $label,
        'machine_name' => $name,
        'experiment_type' => self::EXPERIMENT_TYPE,
        'hypothesis' => $options['hypothesis'] ?? (string) $this->t('Probar variaciones de prompt para mejorar calidad de respuesta IA.'),
        'primary_metric' => 'custom',
        'status' => 'draft',
        'confidence_threshold' => $options['confidence_threshold'] ?? 0.95,
        'minimum_sample_size' => $options['minimum_sample_size'] ?? 50,
        'minimum_runtime_days' => $options['minimum_runtime_days'] ?? 7,
        'traffic_percentage' => $options['traffic_percentage'] ?? 100,
        'auto_complete' => TRUE,
        'tenant_id' => $tenantId,
      ]);
      $experiment->save();

      // Create variants.
      $variantIds = [];
      $totalWeight = 0;
      $weightPerVariant = (int) floor(100 / count($variants));

      foreach ($variants as $i => $config) {
        $isLast = ($i === count($variants) - 1);
        $weight = $isLast ? (100 - $totalWeight) : $weightPerVariant;
        $totalWeight += $weight;

        $variant = $variantStorage->create([
          'label' => $config['label'],
          'variant_key' => $name . '_v' . ($i + 1),
          'experiment_id' => $experiment->id(),
          'is_control' => $config['is_control'] ?? ($i === 0),
          'traffic_weight' => $weight,
          'variant_data' => json_encode([
            'system_prompt' => $config['system_prompt'],
            'temperature' => $config['temperature'] ?? 0.7,
            'model_tier' => $config['model_tier'] ?? 'balanced',
          ]),
          'tenant_id' => $tenantId,
        ]);
        $variant->save();
        $variantIds[] = (int) $variant->id();
      }

      $this->logger->info('Prompt experiment "@name" created with @count variants.', [
        '@name' => $name,
        '@count' => count($variants),
      ]);

      return [
        'success' => TRUE,
        'experiment_id' => (int) $experiment->id(),
        'variant_ids' => $variantIds,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating prompt experiment: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Gets the prompt variant to use for the current request.
   *
   * @param string $experimentName
   *   Machine name of the experiment.
   *
   * @return array|null
   *   Variant config (system_prompt, temperature, model_tier) or NULL if
   *   no active experiment or assignment service unavailable.
   */
  public function getActiveVariant(string $experimentName): ?array {
    try {
      if (!\Drupal::hasService('jaraba_ab_testing.variant_assignment')) {
        return NULL;
      }

      $assignmentService = \Drupal::service('jaraba_ab_testing.variant_assignment');
      $assignment = $assignmentService->assignVariant($experimentName);

      if (!$assignment) {
        return NULL;
      }

      // Load variant data.
      $variantStorage = $this->entityTypeManager->getStorage('ab_variant');
      $variant = $variantStorage->load($assignment['variant_id']);

      if (!$variant) {
        return NULL;
      }

      $data = json_decode($variant->get('variant_data')->value ?? '{}', TRUE);

      return [
        'experiment_id' => $assignment['experiment_id'],
        'variant_id' => $assignment['variant_id'],
        'variant_name' => $assignment['variant_name'] ?? '',
        'is_control' => $assignment['is_control'] ?? FALSE,
        'system_prompt' => $data['system_prompt'] ?? '',
        'temperature' => (float) ($data['temperature'] ?? 0.7),
        'model_tier' => $data['model_tier'] ?? 'balanced',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting active variant: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Records the result of a prompt experiment execution.
   *
   * Uses QualityEvaluatorService to automatically score the response
   * and records conversion if quality >= threshold.
   *
   * @param string $experimentName
   *   Machine name of the experiment.
   * @param string $prompt
   *   The user prompt that was sent.
   * @param string $response
   *   The AI response that was generated.
   * @param array $context
   *   Additional context for quality evaluation.
   *
   * @return array
   *   Result with quality_score, is_conversion, experiment details.
   */
  public function recordResult(string $experimentName, string $prompt, string $response, array $context = []): array {
    try {
      // Evaluate quality.
      $evaluation = $this->qualityEvaluator->evaluate($prompt, $response, [], $context);
      $qualityScore = (float) ($evaluation['overall_score'] ?? 0.0);
      $isConversion = $qualityScore >= self::QUALITY_THRESHOLD;

      // Record conversion in AB testing.
      if (\Drupal::hasService('jaraba_ab_testing.variant_assignment')) {
        $assignmentService = \Drupal::service('jaraba_ab_testing.variant_assignment');
        if ($isConversion) {
          $assignmentService->recordConversion($experimentName, $qualityScore);
        }
      }

      $this->logger->info('Prompt experiment result: @name score=@score conversion=@conv', [
        '@name' => $experimentName,
        '@score' => round($qualityScore, 3),
        '@conv' => $isConversion ? 'yes' : 'no',
      ]);

      return [
        'success' => TRUE,
        'quality_score' => round($qualityScore, 4),
        'is_conversion' => $isConversion,
        'threshold' => self::QUALITY_THRESHOLD,
        'evaluation' => $evaluation,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error recording prompt result: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Lists active prompt experiments for a tenant.
   *
   * @param int $tenantId
   *   Tenant ID.
   *
   * @return array
   *   Array of experiment summaries.
   */
  public function listExperiments(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ab_experiment');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('experiment_type', self::EXPERIMENT_TYPE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $experiments = $storage->loadMultiple($ids);
      $result = [];

      foreach ($experiments as $experiment) {
        $result[] = [
          'id' => (int) $experiment->id(),
          'label' => $experiment->get('label')->value,
          'machine_name' => $experiment->get('machine_name')->value,
          'status' => $experiment->get('status')->value,
          'total_visitors' => (int) ($experiment->get('total_visitors')->value ?? 0),
          'total_conversions' => (int) ($experiment->get('total_conversions')->value ?? 0),
        ];
      }

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing experiments: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets experiment results with statistical analysis.
   *
   * @param int $experimentId
   *   Experiment ID.
   *
   * @return array
   *   Statistical analysis results.
   */
  public function getExperimentResults(int $experimentId): array {
    try {
      if (!\Drupal::hasService('jaraba_ab_testing.statistical_engine')) {
        return ['success' => FALSE, 'error' => 'Statistical engine not available'];
      }

      $experimentStorage = $this->entityTypeManager->getStorage('ab_experiment');
      $variantStorage = $this->entityTypeManager->getStorage('ab_variant');
      $statisticalEngine = \Drupal::service('jaraba_ab_testing.statistical_engine');

      $experiment = $experimentStorage->load($experimentId);
      if (!$experiment) {
        return ['success' => FALSE, 'error' => 'Experiment not found'];
      }

      // Load variants.
      $variantIds = $variantStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('experiment_id', $experimentId)
        ->execute();

      $variantEntities = $variantStorage->loadMultiple($variantIds);
      $variants = [];

      foreach ($variantEntities as $variant) {
        $data = json_decode($variant->get('variant_data')->value ?? '{}', TRUE);
        $variants[] = [
          'id' => (int) $variant->id(),
          'label' => $variant->get('label')->value,
          'is_control' => (bool) $variant->get('is_control')->value,
          'visitors' => (int) ($variant->get('visitors')->value ?? 0),
          'conversions' => (int) ($variant->get('conversions')->value ?? 0),
          'model_tier' => $data['model_tier'] ?? 'balanced',
          'temperature' => $data['temperature'] ?? 0.7,
        ];
      }

      $confidence = (float) ($experiment->get('confidence_threshold')->value ?? 0.95);
      $analysis = $statisticalEngine->analyzeExperiment($variants, $confidence);

      return [
        'success' => TRUE,
        'experiment' => [
          'id' => $experimentId,
          'label' => $experiment->get('label')->value,
          'status' => $experiment->get('status')->value,
        ],
        'variants' => $variants,
        'analysis' => $analysis,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error getting experiment results: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

}
