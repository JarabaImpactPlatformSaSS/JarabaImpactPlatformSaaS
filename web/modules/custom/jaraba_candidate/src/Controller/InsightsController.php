<?php

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the Copilot Insights Dashboard page.
 */
class InsightsController extends ControllerBase
{

    /**
     * Builds the Copilot Insights dashboard page.
     *
     * @return array
     *   Render array for the dashboard.
     */
    public function dashboard(): array
    {
        /** @var \Drupal\jaraba_candidate\Service\CopilotInsightsService $insightsService */
        $insightsService = \Drupal::service('jaraba_candidate.copilot_insights');

        // Obtener métricas
        $metrics = $insightsService->getEffectivenessMetrics('week');
        $topics = $insightsService->aggregateTopics(NULL, 'week');
        $popularQuestions = $insightsService->getPopularQuestions(NULL, 8);
        $unresolvedQueries = $insightsService->getUnresolvedQueries();

        return [
            '#theme' => 'copilot_insights_dashboard',
            '#metrics' => $metrics,
            '#topics' => $topics,
            '#popular_questions' => $popularQuestions,
            '#unresolved_queries' => $unresolvedQueries,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/premium-components',
                ],
            ],
            '#cache' => [
                'max-age' => 300,
                'contexts' => ['user.permissions'],
            ],
        ];
    }

}
