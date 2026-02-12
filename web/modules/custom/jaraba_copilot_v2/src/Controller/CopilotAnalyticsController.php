<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_copilot_v2\Service\CopilotOrchestratorService;
use Drupal\jaraba_copilot_v2\Service\CopilotQueryLoggerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para el Dashboard de Analytics del Copiloto.
 *
 * Proporciona una vista administrativa para analizar:
 * - Estadísticas de uso del copiloto
 * - Preguntas frecuentes
 * - Queries problemáticas (sin feedback positivo)
 * - Tendencias de uso por fuente
 */
class CopilotAnalyticsController extends ControllerBase
{

    /**
     * Constructor del controlador.
     */
    public function __construct(
        protected CopilotQueryLoggerService $queryLogger,
        protected CopilotOrchestratorService $orchestrator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_copilot_v2.query_logger'),
            $container->get('jaraba_copilot_v2.copilot_orchestrator'),
        );
    }

    /**
     * Dashboard principal de analytics.
     *
     * @return array
     *   Render array con el dashboard.
     */
    public function dashboard(): array
    {
        // Verificar si la tabla está lista
        if (!$this->queryLogger->isTableReady()) {
            return [
                '#theme' => 'copilot_analytics_dashboard',
                '#attached' => [
                    'library' => ['jaraba_copilot_v2/copilot-analytics'],
                ],
                '#table_ready' => FALSE,
                '#message' => $this->t('La tabla de analytics no está configurada. Por favor, ejecute: drush updatedb'),
            ];
        }

        // Obtener estadísticas generales
        $stats = $this->queryLogger->getStats('all', 30);

        // Obtener estadísticas por fuente
        $statsBySource = [
            'public' => $this->queryLogger->getStats('public', 30),
            'emprendimiento' => $this->queryLogger->getStats('emprendimiento', 30),
            'empleabilidad' => $this->queryLogger->getStats('empleabilidad', 30),
        ];

        // Obtener queries recientes
        $recentQueries = $this->queryLogger->getRecentQueries('all', 25);

        // Obtener queries problemáticas
        $problematicQueries = $this->queryLogger->getProblematicQueries(15);

        // Obtener preguntas frecuentes
        $frequentQuestions = $this->queryLogger->getFrequentQuestions(30, 10);

        // Obtener metricas de rendimiento avanzadas.
        $performanceMetrics = $this->orchestrator->getMetricsSummary();

        return [
            '#theme' => 'copilot_analytics_dashboard',
            '#attached' => [
                'library' => ['jaraba_copilot_v2/copilot-analytics'],
            ],
            '#table_ready' => TRUE,
            '#stats' => $stats,
            '#stats_by_source' => $statsBySource,
            '#recent_queries' => $recentQueries,
            '#problematic_queries' => $problematicQueries,
            '#frequent_questions' => $frequentQuestions,
            '#performance_metrics' => $performanceMetrics,
        ];
    }

}
