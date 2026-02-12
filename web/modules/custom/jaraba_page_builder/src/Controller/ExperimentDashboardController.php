<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jaraba_page_builder\Service\ExperimentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para el dashboard visual de experimentos A/B.
 *
 * PROPÓSITO:
 * Proporciona la página frontend donde los tenants pueden ver y gestionar
 * sus experimentos A/B de manera visual, con gráficos de conversión y
 * controles para iniciar/detener experimentos.
 *
 * ESPECIFICACIÓN: Gap 2 - Plan Elevación Clase Mundial
 * ARQUITECTURA: Frontend limpio, sin sidebar de admin
 *
 * @package Drupal\jaraba_page_builder\Controller
 */
class ExperimentDashboardController extends ControllerBase implements ContainerInjectionInterface
{

    /**
     * Servicio de experimentos.
     *
     * @var \Drupal\jaraba_page_builder\Service\ExperimentService
     */
    protected ExperimentService $experimentService;

    /**
     * Servicio de renderizado para respuestas AJAX.
     *
     * @var \Drupal\Core\Render\RendererInterface
     */
    protected RendererInterface $renderer;

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_page_builder\Service\ExperimentService $experiment_service
     *   Servicio de gestión de experimentos.
     * @param \Drupal\Core\Render\RendererInterface $renderer
     *   Servicio de renderizado.
     */
    public function __construct(ExperimentService $experiment_service, RendererInterface $renderer)
    {
        $this->experimentService = $experiment_service;
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_page_builder.experiment'),
            $container->get('renderer')
        );
    }

    /**
     * Renderiza el dashboard de experimentos A/B.
     *
     * Este dashboard muestra:
     * - Lista de experimentos activos y finalizados
     * - Métricas de conversión (visits, conversions, rate)
     * - Gráficos de tendencia
     * - Acciones: crear, pausar, detener, ver resultados
     *
     * @return array
     *   Render array del dashboard.
     */
    public function dashboard(): array
    {
        // Obtener tenant ID del usuario actual.
        $user = $this->currentUser();
        $tenantId = $this->getTenantId($user);

        // Obtener experimentos del tenant.
        $experiments = $this->loadTenantExperiments($tenantId);

        // Calcular métricas agregadas.
        $metrics = $this->calculateDashboardMetrics($experiments);

        return [
            '#theme' => 'experiment_dashboard',
            '#experiments' => $experiments,
            '#metrics' => $metrics,
            '#attached' => [
                'library' => [
                    'ecosistema_jaraba_core/global',
                    'jaraba_page_builder/experiment-dashboard',
                ],
                'drupalSettings' => [
                    'experimentDashboard' => [
                        'apiBaseUrl' => '/api/v1/experiments',
                        'refreshInterval' => 60000, // 60 segundos
                    ],
                ],
            ],
            '#cache' => [
                'max-age' => 0,
                'contexts' => ['user', 'url.path'],
            ],
        ];
    }

    /**
     * Obtiene el tenant ID del usuario actual.
     *
     * @param mixed $user
     *   Objeto de cuenta del usuario.
     *
     * @return int
     *   ID del tenant o 0 si no tiene.
     */
    protected function getTenantId($user): int
    {
        // Si el usuario tiene campo tenant_id, usarlo.
        if ($user->isAuthenticated()) {
            $account = \Drupal::entityTypeManager()
                ->getStorage('user')
                ->load($user->id());

            if ($account && $account->hasField('field_tenant') && !$account->get('field_tenant')->isEmpty()) {
                return (int) $account->get('field_tenant')->target_id;
            }
        }

        return 0;
    }

    /**
     * Carga los experimentos del tenant.
     *
     * @param int $tenantId
     *   ID del tenant.
     *
     * @return array
     *   Lista de experimentos formateados para el dashboard.
     */
    protected function loadTenantExperiments(int $tenantId): array
    {
        $storage = \Drupal::entityTypeManager()->getStorage('page_experiment');

        // Query de experimentos por tenant.
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->sort('created', 'DESC')
            ->range(0, 50);

        // Si tiene tenant, filtrar.
        if ($tenantId > 0) {
            $query->condition('tenant_id', $tenantId);
        }

        $ids = $query->execute();
        $experiments = [];

        if (!empty($ids)) {
            $entities = $storage->loadMultiple($ids);

            foreach ($entities as $experiment) {
                $experiments[] = [
                    'id' => $experiment->id(),
                    'name' => $experiment->getName(),
                    'status' => $experiment->getStatus(),
                    'status_label' => $this->getStatusLabel($experiment->getStatus()),
                    'page_id' => $experiment->getPageId(),
                    'page_title' => $this->getPageTitle($experiment->getPageId()),
                    'variants_count' => $this->getVariantsCount($experiment->id()),
                    'total_visits' => 0, // Se calcula por variantes.
                    'total_conversions' => 0,
                    'conversion_rate' => 0.0,
                    'created' => $experiment->getCreatedTime(),
                    'started_at' => $experiment->getStartedAt(),
                    'ended_at' => $experiment->getEndedAt(),
                    'significance' => null, // Se calcula si hay datos suficientes.
                ];
            }

            // Calcular métricas por experimento.
            foreach ($experiments as &$exp) {
                $this->enrichExperimentMetrics($exp);
            }
        }

        return $experiments;
    }

    /**
     * Obtiene la etiqueta del estado en español.
     *
     * @param string $status
     *   Estado del experimento.
     *
     * @return string
     *   Etiqueta traducida.
     */
    protected function getStatusLabel(string $status): string
    {
        $labels = [
            'draft' => $this->t('Borrador'),
            'running' => $this->t('En ejecución'),
            'paused' => $this->t('Pausado'),
            'completed' => $this->t('Completado'),
            'archived' => $this->t('Archivado'),
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Obtiene el título de la página asociada.
     *
     * @param int|null $pageId
     *   ID de la página.
     *
     * @return string
     *   Título de la página o mensaje por defecto.
     */
    protected function getPageTitle(?int $pageId): string
    {
        if (!$pageId) {
            return $this->t('Sin página asociada')->render();
        }

        $page = \Drupal::entityTypeManager()
            ->getStorage('page_content')
            ->load($pageId);

        if ($page) {
            return $page->getTitle();
        }

        return $this->t('Página #@id', ['@id' => $pageId])->render();
    }

    /**
     * Obtiene el número de variantes de un experimento.
     *
     * @param int $experimentId
     *   ID del experimento.
     *
     * @return int
     *   Número de variantes.
     */
    protected function getVariantsCount(int $experimentId): int
    {
        $storage = \Drupal::entityTypeManager()->getStorage('experiment_variant');

        return (int) $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('experiment_id', $experimentId)
            ->count()
            ->execute();
    }

    /**
     * Enriquece el experimento con métricas calculadas.
     *
     * @param array $experiment
     *   Datos del experimento por referencia.
     */
    protected function enrichExperimentMetrics(array &$experiment): void
    {
        $variantStorage = \Drupal::entityTypeManager()->getStorage('experiment_variant');

        $variants = $variantStorage->loadByProperties([
            'experiment_id' => $experiment['id'],
        ]);

        $totalVisits = 0;
        $totalConversions = 0;

        foreach ($variants as $variant) {
            $totalVisits += $variant->getVisitors();
            $totalConversions += $variant->getConversions();
        }

        $experiment['total_visits'] = $totalVisits;
        $experiment['total_conversions'] = $totalConversions;
        $experiment['conversion_rate'] = $totalVisits > 0
            ? round(($totalConversions / $totalVisits) * 100, 2)
            : 0.0;

        // Calcular significancia si hay datos suficientes.
        if ($totalVisits >= 100 && count($variants) >= 2) {
            // Cargar la entidad experimento para el análisis.
            $experimentEntity = \Drupal::entityTypeManager()
                ->getStorage('page_experiment')
                ->load($experiment['id']);
            if ($experimentEntity) {
                $analysis = $this->experimentService->analyzeResults($experimentEntity);
                $experiment['significance'] = $analysis['confidence'] ?? null;
            }
        }
    }

    /**
     * Calcula métricas agregadas del dashboard.
     *
     * @param array $experiments
     *   Lista de experimentos.
     *
     * @return array
     *   Métricas agregadas.
     */
    protected function calculateDashboardMetrics(array $experiments): array
    {
        $active = 0;
        $completed = 0;
        $totalVisits = 0;
        $totalConversions = 0;

        foreach ($experiments as $exp) {
            if ($exp['status'] === 'running') {
                $active++;
            }
            if ($exp['status'] === 'completed') {
                $completed++;
            }
            $totalVisits += $exp['total_visits'];
            $totalConversions += $exp['total_conversions'];
        }

        return [
            'total_experiments' => count($experiments),
            'active_experiments' => $active,
            'completed_experiments' => $completed,
            'total_visits' => $totalVisits,
            'total_conversions' => $totalConversions,
            'overall_conversion_rate' => $totalVisits > 0
                ? round(($totalConversions / $totalVisits) * 100, 2)
                : 0.0,
        ];
    }

    /**
     * Renderiza el formulario de creación de experimento.
     *
     * Este método es llamado desde el slide-panel cuando el usuario
     * hace clic en "Nuevo Experimento" en el dashboard.
     *
     * Sigue el patrón AJAX de slide-panel:
     * - Si es AJAX, devuelve solo el HTML del formulario (Response).
     * - Si es request normal, devuelve render array (página completa).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request actual.
     *
     * @return array|\Symfony\Component\HttpFoundation\Response
     *   Render array o Response AJAX con HTML del formulario.
     *
     * @see .agent/workflows/slide-panel-modales.md
     */
    public function createForm(Request $request): array|Response
    {
        // Crear una nueva entidad de experimento.
        $experiment = $this->entityTypeManager()
            ->getStorage('page_experiment')
            ->create([]);

        // Obtener el formulario de la entidad.
        $form = $this->entityFormBuilder()->getForm($experiment, 'add');

        // Build completo con wrapper y estilos.
        $build = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['experiment-create-form', 'slide-panel-content'],
            ],
            'form' => $form,
            '#attached' => [
                'library' => [
                    'jaraba_page_builder/experiment-dashboard',
                ],
            ],
        ];

        // Patrón AJAX: Si viene de slide-panel, devolver solo HTML.
        // @see .agent/workflows/slide-panel-modales.md Sección "Controlador"
        if ($request->isXmlHttpRequest()) {
            $html = (string) $this->renderer->render($build);
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        // Request normal: devolver página completa.
        return $build;
    }

}

