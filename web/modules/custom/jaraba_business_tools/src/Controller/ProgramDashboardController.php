<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Program Dashboard (administrators view).
 */
class ProgramDashboardController extends ControllerBase
{

    /**
     * Renders the program dashboard for administrators.
     */
    public function dashboard(): array
    {
        // Get cohort statistics
        $cohortStats = $this->getCohortStatistics();

        // Get entrepreneur counts by stage
        $stageDistribution = $this->getStageDistribution();

        // Get aggregate progress
        $aggregateProgress = $this->getAggregateProgress();

        // Get impact metrics
        $impactMetrics = $this->getImpactMetrics();

        return [
            '#theme' => 'program_dashboard',
            '#cohort_stats' => $cohortStats,
            '#stage_distribution' => $stageDistribution,
            '#aggregate_progress' => $aggregateProgress,
            '#impact_metrics' => $impactMetrics,
            '#recent_activity' => $this->getRecentActivity(),
            '#attached' => [
                'library' => [
                    'jaraba_business_tools/program-dashboard',
                ],
            ],
        ];
    }

    /**
     * Gets cohort statistics.
     */
    protected function getCohortStatistics(): array
    {
        try {
            $diagnosticStorage = $this->entityTypeManager()->getStorage('business_diagnostic');

            $total = $diagnosticStorage->getQuery()
                ->accessCheck(TRUE)
                ->count()
                ->execute();

            $completed = $diagnosticStorage->getQuery()
                ->accessCheck(TRUE)
                ->condition('status', 'completed')
                ->count()
                ->execute();

            $activeCanvases = $this->entityTypeManager()->getStorage('business_model_canvas')
                ->getQuery()
                ->accessCheck(TRUE)
                ->count()
                ->execute();

            $activePaths = $this->entityTypeManager()->getStorage('path_enrollment')
                ->getQuery()
                ->accessCheck(TRUE)
                ->count()
                ->execute();

            return [
                'total_entrepreneurs' => $total,
                'completed_diagnostics' => $completed,
                'active_canvases' => $activeCanvases,
                'active_paths' => $activePaths,
            ];
        } catch (\Exception $e) {
            return [
                'total_entrepreneurs' => 0,
                'completed_diagnostics' => 0,
                'active_canvases' => 0,
                'active_paths' => 0,
            ];
        }
    }

    /**
     * Gets entrepreneur distribution by business stage.
     */
    protected function getStageDistribution(): array
    {
        // Return placeholder data - business_stage field may not exist
        return [
            'idea' => 0,
            'validation' => 0,
            'growth' => 0,
            'scaling' => 0,
        ];
    }

    /**
     * Gets aggregate progress metrics.
     */
    protected function getAggregateProgress(): array
    {
        try {
            $canvasStorage = $this->entityTypeManager()->getStorage('business_model_canvas');
            $pathStorage = $this->entityTypeManager()->getStorage('path_enrollment');

            // Count canvases
            $canvasCount = $canvasStorage->getQuery()
                ->accessCheck(TRUE)
                ->count()
                ->execute();

            // Count paths
            $pathCount = $pathStorage->getQuery()
                ->accessCheck(TRUE)
                ->count()
                ->execute();

            return [
                'avg_canvas_completeness' => 0,
                'avg_path_progress' => 0,
                'total_canvases' => $canvasCount,
                'total_active_paths' => $pathCount,
            ];
        } catch (\Exception $e) {
            return [
                'avg_canvas_completeness' => 0,
                'avg_path_progress' => 0,
                'total_canvases' => 0,
                'total_active_paths' => 0,
            ];
        }
    }

    /**
     * Gets impact metrics.
     */
    protected function getImpactMetrics(): array
    {
        // Placeholder - would connect to actual impact tracking
        return [
            'businesses_created' => 0,
            'jobs_created' => 0,
            'total_revenue_generated' => 0,
            'digital_maturity_improvement' => 0,
        ];
    }

    /**
     * Gets recent activity.
     */
    protected function getRecentActivity(): array
    {
        try {
            $activities = [];

            // Recent diagnostics
            $diagnosticStorage = $this->entityTypeManager()->getStorage('business_diagnostic');
            $diagnosticIds = $diagnosticStorage->getQuery()
                ->accessCheck(TRUE)
                ->sort('created', 'DESC')
                ->range(0, 5)
                ->execute();

            if (!empty($diagnosticIds)) {
                $recentDiagnostics = $diagnosticStorage->loadMultiple($diagnosticIds);
                foreach ($recentDiagnostics as $diagnostic) {
                    $activities[] = [
                        'type' => 'diagnostic',
                        'label' => $this->t('Nuevo diagnóstico completado'),
                        'date' => time(),
                    ];
                }
            }

            // Sort by date
            usort($activities, fn($a, $b) => $b['date'] <=> $a['date']);

            return array_slice($activities, 0, 10);
        } catch (\Exception $e) {
            return [];
        }
    }

}
