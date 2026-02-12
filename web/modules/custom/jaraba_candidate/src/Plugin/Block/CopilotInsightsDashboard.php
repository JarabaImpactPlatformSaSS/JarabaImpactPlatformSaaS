<?php

namespace Drupal\jaraba_candidate\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jaraba_candidate\Service\CopilotInsightsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a dashboard block for Copilot Insights.
 *
 * PROPÓSITO:
 * Muestra métricas y análisis de conversaciones de copilotos para
 * administradores. Permite ver qué preguntan los usuarios, detectar
 * gaps de conocimiento y medir la efectividad de los asistentes IA.
 *
 * UBICACIÓN RECOMENDADA:
 * - /admin/insights/copilot
 * - Dashboard de administrador de tenant
 *
 * @Block(
 *   id = "copilot_insights_dashboard",
 *   admin_label = @Translation("Copilot Insights Dashboard"),
 *   category = @Translation("Jaraba AI"),
 * )
 */
class CopilotInsightsDashboard extends BlockBase implements ContainerFactoryPluginInterface
{

    /**
     * Copilot insights service.
     *
     * @var \Drupal\jaraba_candidate\Service\CopilotInsightsService
     */
    protected CopilotInsightsService $insightsService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition)
    {
        $instance = new static($configuration, $plugin_id, $plugin_definition);
        $instance->insightsService = $container->get('jaraba_candidate.copilot_insights');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        // Obtener métricas de efectividad
        $metrics = $this->insightsService->getEffectivenessMetrics('week');

        // Obtener topics agregados
        $topics = $this->insightsService->aggregateTopics(NULL, 'week');

        // Obtener preguntas populares
        $popularQuestions = $this->insightsService->getPopularQuestions(NULL, 8);

        // Obtener queries sin resolver
        $unresolvedQueries = $this->insightsService->getUnresolvedQueries();

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
                'max-age' => 300, // 5 minutos
                'contexts' => ['user.permissions'],
                'tags' => ['copilot_insights'],
            ],
        ];
    }

}
