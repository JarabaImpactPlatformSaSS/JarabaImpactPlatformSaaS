<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Psr\Log\LoggerInterface;

/**
 * B-15: A/B testing for review display.
 *
 * Manages experiments for different review display layouts,
 * sort orders, and UX patterns. Tracks impressions and
 * conversions to determine optimal configuration.
 */
class ReviewAbTestService {

  /**
   * Default experiment definitions (used if no DB config exists).
   */
  private const DEFAULT_EXPERIMENTS = [
    'display_layout' => ['control' => 'list', 'variant' => 'card'],
    'sort_order' => ['control' => 'newest', 'variant' => 'helpful'],
    'show_sentiment' => ['control' => 'hidden', 'variant' => 'visible'],
    'photo_position' => ['control' => 'bottom', 'variant' => 'inline'],
    'summary_position' => ['control' => 'top', 'variant' => 'sidebar'],
  ];

  public function __construct(
    protected readonly Connection $database,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Get the assigned variant for a user/experiment.
   *
   * Uses consistent hashing based on uid + experiment_id to ensure
   * the same user always sees the same variant.
   *
   * @param string $experimentId
   *   Experiment identifier.
   * @param int $uid
   *   User ID (0 for anonymous).
   * @param string $sessionId
   *   Session ID for anonymous users.
   *
   * @return string
   *   'control' or 'variant'.
   */
  public function getVariant(string $experimentId, int $uid = 0, string $sessionId = ''): string {
    $experiment = $this->getExperimentConfig($experimentId);
    if ($experiment === NULL) {
      return 'control';
    }

    // Check if experiment is active.
    if (!$this->isExperimentActive($experimentId)) {
      return 'control';
    }

    // Consistent hash for assignment.
    $hash = crc32($experimentId . ':' . ($uid > 0 ? (string) $uid : $sessionId));
    $assigned = ($hash % 2 === 0) ? 'control' : 'variant';

    return $assigned;
  }

  /**
   * Get the actual value for a variant.
   *
   * @param string $experimentId
   *   Experiment ID.
   * @param string $variant
   *   'control' or 'variant'.
   *
   * @return string
   *   The configuration value.
   */
  public function getVariantValue(string $experimentId, string $variant): string {
    $experiment = $this->getExperimentConfig($experimentId);
    if ($experiment === NULL) {
      return '';
    }
    return $experiment[$variant] ?? $experiment['control'];
  }

  /**
   * Get experiment configuration from DB or defaults.
   */
  protected function getExperimentConfig(string $experimentId): ?array {
    // Check DB-stored experiments first.
    $this->ensureConfigTable();
    try {
      $row = $this->database->select('review_ab_config', 'c')
        ->fields('c', ['control_value', 'variant_value', 'active', 'traffic_percentage', 'start_date', 'end_date'])
        ->condition('experiment_id', $experimentId)
        ->execute()
        ->fetchObject();

      if ($row !== FALSE) {
        // Check date range.
        $now = time();
        if (!empty($row->start_date) && $now < (int) $row->start_date) {
          return NULL;
        }
        if (!empty($row->end_date) && $now > (int) $row->end_date) {
          return NULL;
        }
        if (!(bool) $row->active) {
          return NULL;
        }
        return [
          'control' => $row->control_value,
          'variant' => $row->variant_value,
          'traffic_percentage' => (int) $row->traffic_percentage,
        ];
      }
    }
    catch (\Exception) {}

    // Fall back to defaults.
    return self::DEFAULT_EXPERIMENTS[$experimentId] ?? NULL;
  }

  /**
   * Create or update an experiment configuration.
   */
  public function saveExperiment(string $experimentId, string $controlValue, string $variantValue, bool $active = TRUE, int $trafficPercentage = 50, int $startDate = 0, int $endDate = 0): void {
    $this->ensureConfigTable();

    $this->database->merge('review_ab_config')
      ->keys(['experiment_id' => $experimentId])
      ->fields([
        'experiment_id' => $experimentId,
        'control_value' => $controlValue,
        'variant_value' => $variantValue,
        'active' => $active ? 1 : 0,
        'traffic_percentage' => $trafficPercentage,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'updated' => time(),
      ])
      ->execute();
  }

  /**
   * List all experiment configurations.
   */
  public function listExperiments(): array {
    $this->ensureConfigTable();
    try {
      return $this->database->select('review_ab_config', 'c')
        ->fields('c')
        ->orderBy('experiment_id')
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * Record an impression.
   */
  public function recordImpression(string $experimentId, string $variant): void {
    $this->ensureTable();

    try {
      $this->database->merge('review_ab_experiments')
        ->keys([
          'experiment_id' => $experimentId,
          'variant' => $variant,
        ])
        ->fields([
          'experiment_id' => $experimentId,
          'variant' => $variant,
          'impressions' => 1,
          'conversions' => 0,
          'updated' => time(),
        ])
        ->expression('impressions', 'impressions + 1')
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->warning('AB impression record failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Record a conversion.
   */
  public function recordConversion(string $experimentId, string $variant): void {
    $this->ensureTable();

    try {
      $this->database->merge('review_ab_experiments')
        ->keys([
          'experiment_id' => $experimentId,
          'variant' => $variant,
        ])
        ->fields([
          'experiment_id' => $experimentId,
          'variant' => $variant,
          'impressions' => 0,
          'conversions' => 1,
          'updated' => time(),
        ])
        ->expression('conversions', 'conversions + 1')
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->warning('AB conversion record failed: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Get experiment results.
   *
   * @return array
   *   Results per experiment and variant.
   */
  public function getResults(?string $experimentId = NULL): array {
    $this->ensureTable();

    try {
      $query = $this->database->select('review_ab_experiments', 'e')
        ->fields('e', ['experiment_id', 'variant', 'impressions', 'conversions', 'updated']);

      if ($experimentId !== NULL) {
        $query->condition('experiment_id', $experimentId);
      }

      $results = $query->execute()->fetchAll();
      $grouped = [];

      foreach ($results as $row) {
        $convRate = $row->impressions > 0
          ? round(($row->conversions / $row->impressions) * 100, 2)
          : 0;

        $grouped[$row->experiment_id][$row->variant] = [
          'impressions' => (int) $row->impressions,
          'conversions' => (int) $row->conversions,
          'conversion_rate' => $convRate,
        ];
      }

      return $grouped;
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * Check if an experiment is active.
   */
  protected function isExperimentActive(string $experimentId): bool {
    return $this->getExperimentConfig($experimentId) !== NULL;
  }

  /**
   * Ensure config table exists.
   */
  protected function ensureConfigTable(): void {
    $schema = $this->database->schema();
    if (!$schema->tableExists('review_ab_config')) {
      $schema->createTable('review_ab_config', [
        'fields' => [
          'experiment_id' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE, 'default' => ''],
          'control_value' => ['type' => 'varchar', 'length' => 128, 'not null' => TRUE, 'default' => ''],
          'variant_value' => ['type' => 'varchar', 'length' => 128, 'not null' => TRUE, 'default' => ''],
          'active' => ['type' => 'int', 'size' => 'tiny', 'not null' => TRUE, 'default' => 1],
          'traffic_percentage' => ['type' => 'int', 'not null' => TRUE, 'default' => 50],
          'start_date' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'end_date' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'updated' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
        ],
        'primary key' => ['experiment_id'],
      ]);
    }
  }

  /**
   * Ensure tracking table exists.
   */
  protected function ensureTable(): void {
    $schema = $this->database->schema();

    if (!$schema->tableExists('review_ab_experiments')) {
      $schema->createTable('review_ab_experiments', [
        'fields' => [
          'experiment_id' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE, 'default' => ''],
          'variant' => ['type' => 'varchar', 'length' => 32, 'not null' => TRUE, 'default' => ''],
          'impressions' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'conversions' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'updated' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
        ],
        'primary key' => ['experiment_id', 'variant'],
      ]);
    }
  }

}
