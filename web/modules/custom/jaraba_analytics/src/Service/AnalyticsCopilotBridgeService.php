<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Analytics Copilot Bridge — contextualiza el copilot con datos de analitica.
 */
class AnalyticsCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   */
  public function getRelevantContext(int $userId): array {
    $context = [
      'vertical' => 'analytics',
      'has_analytics_data' => FALSE,
      'experiments_count' => 0,
      'active_experiments' => 0,
    ];

    try {
      // AB experiments.
      if ($this->entityTypeManager->hasDefinition('ab_experiment')) {
        $total = $this->entityTypeManager
          ->getStorage('ab_experiment')
          ->getQuery()
          ->accessCheck(TRUE)
          ->count()
          ->execute();
        $context['experiments_count'] = $total;

        $active = $this->entityTypeManager
          ->getStorage('ab_experiment')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'running')
          ->count()
          ->execute();
        $context['active_experiments'] = $active;
      }

      $context['has_analytics_data'] = $context['experiments_count'] > 0;

      $context['_system_prompt_addition'] = sprintf(
        "Datos de analitica: %d experimentos A/B (%d activos). "
        . "Ayuda a interpretar resultados de tests, sugerir hipotesis de optimizacion y analizar metricas de conversion. "
        . "NUNCA inventes datos estadisticos ni resultados de tests.",
        $context['experiments_count'],
        $context['active_experiments'],
      );

    }
    catch (\Exception $e) {
      $this->logger->warning('Analytics CopilotBridge error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>|null
   */
  public function getSoftSuggestion(int $userId): ?array {
    return NULL;
  }

}
