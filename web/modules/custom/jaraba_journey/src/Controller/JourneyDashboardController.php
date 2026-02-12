<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_journey\Service\JourneyEngineService;
use Drupal\jaraba_journey\Service\JourneyDefinitionLoader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para el dashboard administrativo del Journey Engine.
 */
class JourneyDashboardController extends ControllerBase
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * Journey engine service.
     *
     * @var \Drupal\jaraba_journey\Service\JourneyEngineService
     */
    protected $journeyEngine;

    /**
     * Journey definition loader.
     *
     * @var \Drupal\jaraba_journey\Service\JourneyDefinitionLoader
     */
    protected $definitionLoader;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        JourneyEngineService $journey_engine,
        JourneyDefinitionLoader $definition_loader
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->journeyEngine = $journey_engine;
        $this->definitionLoader = $definition_loader;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('jaraba_journey.engine'),
            $container->get('jaraba_journey.definition_loader')
        );
    }

    /**
     * Dashboard principal del Journey Engine.
     */
    public function dashboard()
    {
        $build = [];

        // Header.
        $build['header'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['journey-dashboard-header']],
            'title' => [
                '#markup' => '<h1>' . $this->t('Journey Engine Dashboard') . '</h1>',
            ],
            'subtitle' => [
                '#markup' => '<p class="subtitle">' . $this->t('Navegación inteligente para 19 avatares en 7 verticales') . '</p>',
            ],
        ];

        // Métricas globales.
        $build['metrics'] = $this->buildGlobalMetrics();

        // Estado por vertical.
        $build['verticals'] = $this->buildVerticalsSummary();

        // Usuarios en riesgo.
        $build['at_risk'] = $this->buildAtRiskUsers();

        // Actividad reciente.
        $build['activity'] = $this->buildRecentActivity();

        // Adjuntar librería CSS.
        $build['#attached']['library'][] = 'jaraba_journey/dashboard';

        return $build;
    }

    /**
     * Construye las métricas globales.
     */
    protected function buildGlobalMetrics(): array
    {
        $storage = $this->entityTypeManager->getStorage('journey_state');

        // Contar usuarios por estado.
        $states = ['discovery', 'activation', 'engagement', 'conversion', 'retention', 'expansion', 'advocacy', 'at_risk'];
        $state_counts = [];

        foreach ($states as $state) {
            $count = $storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('journey_state', $state)
                ->count()
                ->execute();
            $state_counts[$state] = $count;
        }

        $total_users = array_sum($state_counts);

        // Calcular funnel conversion.
        $funnel_stages = ['discovery', 'activation', 'engagement', 'conversion'];
        $funnel_data = [];
        foreach ($funnel_stages as $stage) {
            $funnel_data[$stage] = $state_counts[$stage] ?? 0;
        }

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['journey-metrics-grid']],
            'total' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['metric-card', 'metric-primary']],
                '#markup' => '<div class="metric-value">' . $total_users . '</div>' .
                    '<div class="metric-label">' . $this->t('Usuarios en Journey') . '</div>',
            ],
            'at_risk' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['metric-card', 'metric-danger']],
                '#markup' => '<div class="metric-value">' . ($state_counts['at_risk'] ?? 0) . '</div>' .
                    '<div class="metric-label">' . $this->t('En Riesgo') . '</div>',
            ],
            'advocacy' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['metric-card', 'metric-success']],
                '#markup' => '<div class="metric-value">' . ($state_counts['advocacy'] ?? 0) . '</div>' .
                    '<div class="metric-label">' . $this->t('Advocates') . '</div>',
            ],
            'conversion_rate' => [
                '#type' => 'container',
                '#attributes' => ['class' => ['metric-card', 'metric-info']],
                '#markup' => '<div class="metric-value">' .
                    ($total_users > 0 ? round(($state_counts['conversion'] ?? 0) / $total_users * 100, 1) : 0) . '%</div>' .
                    '<div class="metric-label">' . $this->t('Tasa Conversión') . '</div>',
            ],
        ];
    }

    /**
     * Construye el resumen por vertical.
     */
    protected function buildVerticalsSummary(): array
    {
        $verticals = $this->definitionLoader->getAvailableVerticals();
        $storage = $this->entityTypeManager->getStorage('journey_state');

        $rows = [];
        foreach ($verticals as $vertical) {
            $avatars = $this->definitionLoader->getAvatarsForVertical($vertical);
            $avatar_count = count($avatars);

            // Contar usuarios en esta vertical.
            $user_count = $storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('vertical', $vertical)
                ->count()
                ->execute();

            // Contar usuarios en riesgo.
            $at_risk_count = $storage->getQuery()
                ->accessCheck(TRUE)
                ->condition('vertical', $vertical)
                ->condition('journey_state', 'at_risk')
                ->count()
                ->execute();

            $rows[] = [
                'vertical' => $this->formatVerticalName($vertical),
                'avatars' => $avatar_count,
                'users' => $user_count,
                'at_risk' => $at_risk_count,
                'health' => $this->calculateHealthScore($user_count, $at_risk_count),
            ];
        }

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['journey-verticals-section']],
            'title' => [
                '#markup' => '<h2>' . $this->t('Estado por Vertical') . '</h2>',
            ],
            'table' => [
                '#type' => 'table',
                '#header' => [
                    $this->t('Vertical'),
                    $this->t('Avatares'),
                    $this->t('Usuarios'),
                    $this->t('En Riesgo'),
                    $this->t('Salud'),
                ],
                '#rows' => array_map(function ($row) {
                    return [
                        $row['vertical'],
                        $row['avatars'],
                        $row['users'],
                        ['data' => $row['at_risk'], 'class' => $row['at_risk'] > 0 ? ['text-danger'] : []],
                        ['data' => $row['health'], 'class' => ['health-badge', 'health-' . strtolower($row['health'])]],
                    ];
                }, $rows),
                '#attributes' => ['class' => ['journey-table']],
                '#empty' => $this->t('No hay datos disponibles.'),
            ],
        ];
    }

    /**
     * Construye la lista de usuarios en riesgo.
     */
    protected function buildAtRiskUsers(): array
    {
        $storage = $this->entityTypeManager->getStorage('journey_state');

        $at_risk_states = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('journey_state', 'at_risk')
            ->range(0, 10)
            ->sort('changed', 'DESC')
            ->execute();

        if (empty($at_risk_states)) {
            return [
                '#type' => 'container',
                '#attributes' => ['class' => ['journey-at-risk-section']],
                'title' => [
                    '#markup' => '<h2>' . $this->t('Usuarios en Riesgo') . '</h2>',
                ],
                'empty' => [
                    '#markup' => '<p class="empty-state">' . $this->t('No hay usuarios en riesgo actualmente.') . '</p>',
                ],
            ];
        }

        $states = $storage->loadMultiple($at_risk_states);
        $rows = [];

        foreach ($states as $state) {
            $user = $state->get('user_id')->entity;
            $rows[] = [
                'user' => $user ? $user->getDisplayName() : $this->t('Usuario #@id', ['@id' => $state->get('user_id')->target_id]),
                'avatar' => $state->get('avatar_type')->value,
                'vertical' => $this->formatVerticalName($state->get('vertical')->value),
                'since' => \Drupal::service('date.formatter')->formatTimeDiffSince($state->get('changed')->value),
            ];
        }

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['journey-at-risk-section']],
            'title' => [
                '#markup' => '<h2>' . $this->t('Usuarios en Riesgo') . '</h2>',
            ],
            'table' => [
                '#type' => 'table',
                '#header' => [
                    $this->t('Usuario'),
                    $this->t('Avatar'),
                    $this->t('Vertical'),
                    $this->t('Desde'),
                ],
                '#rows' => array_map(function ($row) {
                    return [$row['user'], $row['avatar'], $row['vertical'], $row['since']];
                }, $rows),
                '#attributes' => ['class' => ['journey-table', 'table-danger']],
            ],
        ];
    }

    /**
     * Construye la actividad reciente.
     */
    protected function buildRecentActivity(): array
    {
        $storage = $this->entityTypeManager->getStorage('journey_state');

        $recent_states = $storage->getQuery()
            ->accessCheck(TRUE)
            ->sort('changed', 'DESC')
            ->range(0, 5)
            ->execute();

        if (empty($recent_states)) {
            return [];
        }

        $states = $storage->loadMultiple($recent_states);
        $items = [];

        foreach ($states as $state) {
            $user = $state->get('user_id')->entity;
            $items[] = [
                '#markup' => '<div class="activity-item">' .
                    '<span class="activity-user">' . ($user ? $user->getDisplayName() : 'Usuario') . '</span> ' .
                    '<span class="activity-action">' . $this->t('está en') . '</span> ' .
                    '<span class="activity-state badge-' . $state->get('journey_state')->value . '">' .
                    $state->get('journey_state')->value . '</span> ' .
                    '<span class="activity-time">' .
                    \Drupal::service('date.formatter')->formatTimeDiffSince($state->get('changed')->value) . '</span>' .
                    '</div>',
            ];
        }

        return [
            '#type' => 'container',
            '#attributes' => ['class' => ['journey-activity-section']],
            'title' => [
                '#markup' => '<h2>' . $this->t('Actividad Reciente') . '</h2>',
            ],
            'items' => $items,
        ];
    }

    /**
     * Formatea el nombre del vertical.
     */
    protected function formatVerticalName(string $vertical): string
    {
        $names = [
            'agroconecta' => 'AgroConecta',
            'comercioconecta' => 'ComercioConecta',
            'serviciosconecta' => 'ServiciosConecta',
            'empleabilidad' => 'Empleabilidad',
            'emprendimiento' => 'Emprendimiento',
            'andalucia_ei' => 'Andalucía +ei',
            'certificacion' => 'Certificación',
        ];

        return $names[$vertical] ?? ucfirst($vertical);
    }

    /**
     * Calcula el score de salud.
     *
     * @return string
     *   Health score traducible.
     */
    protected function calculateHealthScore(int $total, int $at_risk): string
    {
        if ($total === 0) {
            return (string) $this->t('N/A');
        }

        $risk_percentage = ($at_risk / $total) * 100;

        if ($risk_percentage <= 5) {
            return (string) $this->t('Excelente');
        } elseif ($risk_percentage <= 15) {
            return (string) $this->t('Bueno');
        } elseif ($risk_percentage <= 30) {
            return (string) $this->t('Regular');
        } else {
            return (string) $this->t('Crítico');
        }
    }

}
