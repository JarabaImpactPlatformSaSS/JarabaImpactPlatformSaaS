<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\QualityEvaluatorService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for AI Dashboard page.
 */
class DashboardController extends ControllerBase
{

    /**
     * The observability service.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AIObservabilityService
     */
    protected AIObservabilityService $observability;

    /**
     * The quality evaluator.
     *
     * @var \Drupal\jaraba_ai_agents\Service\QualityEvaluatorService
     */
    protected QualityEvaluatorService $qualityEvaluator;

    /**
     * Constructs a DashboardController.
     */
    public function __construct(
        AIObservabilityService $observability,
        QualityEvaluatorService $qualityEvaluator,
    ) {
        $this->observability = $observability;
        $this->qualityEvaluator = $qualityEvaluator;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_ai_agents.observability'),
            $container->get('jaraba_ai_agents.quality_evaluator'),
        );
    }

    /**
     * Renders the AI Dashboard page.
     */
    public function dashboard(): array
    {
        $period = \Drupal::request()->query->get('period', 'month');

        // Get all metrics.
        $stats = $this->observability->getStats($period);
        $costByTier = $this->observability->getCostByTier($period);
        $usageByAgent = $this->observability->getUsageByAgent($period);
        $savings = $this->observability->getSavings($period);
        $qualityStats = $this->qualityEvaluator->getQualityStats($period);

        return [
            '#theme' => 'jaraba_ai_dashboard',
            '#period' => $period,
            '#stats' => $stats,
            '#cost_by_tier' => $costByTier,
            '#usage_by_agent' => $usageByAgent,
            '#savings' => $savings,
            '#quality_stats' => $qualityStats,
            '#attached' => [
                'library' => ['jaraba_ai_agents/dashboard'],
            ],
            '#cache' => [
                'max-age' => 300, // 5 minutes cache.
            ],
        ];
    }

}
