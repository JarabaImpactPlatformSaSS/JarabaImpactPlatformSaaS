<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_analytics\Service\DashboardManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para la interfaz del constructor de dashboards.
 *
 * PROPOSITO:
 * Renderiza las paginas del constructor de dashboards BI, incluyendo
 * la lista de dashboards, la vista individual y la pagina del builder.
 *
 * LOGICA:
 * - listDashboards: muestra tarjetas de dashboards disponibles.
 * - viewDashboard: renderiza un dashboard con sus widgets.
 * - builderPage: muestra la interfaz de edicion drag-and-drop.
 */
class DashboardBuilderController extends ControllerBase {

  /**
   * Dashboard manager service.
   *
   * @var \Drupal\jaraba_analytics\Service\DashboardManagerService
   */
  protected DashboardManagerService $dashboardManager;

  /**
   * Constructor.
   */
  public function __construct(
    DashboardManagerService $dashboard_manager,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->dashboardManager = $dashboard_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_analytics.dashboard_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Lists all available dashboards.
   *
   * @return array
   *   Render array for the dashboard list page.
   */
  public function listDashboards(): array {
    try {
      $currentUser = $this->currentUser();
      $dashboards = $this->dashboardManager->getUserDashboards(
        (int) $currentUser->id()
      );

      return [
        '#theme' => 'analytics_dashboard_list',
        '#dashboards' => $dashboards,
        '#can_create' => $currentUser->hasPermission('create dashboards') || $currentUser->hasPermission('manage analytics dashboards'),
        '#attached' => [
          'library' => [
            'jaraba_analytics/dashboard-builder',
          ],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_analytics')->error('Failed to list dashboards: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [
        '#markup' => $this->t('Unable to load dashboards. Please try again later.'),
      ];
    }
  }

  /**
   * Views a specific dashboard with its widgets.
   *
   * @param int $dashboardId
   *   The dashboard entity ID.
   *
   * @return array
   *   Render array for the dashboard view page.
   */
  public function viewDashboard(int $dashboardId): array {
    try {
      $dashboard = $this->dashboardManager->getDashboard($dashboardId);

      if (!$dashboard) {
        return [
          '#markup' => $this->t('Dashboard not found.'),
        ];
      }

      // Load widgets for this dashboard.
      $widgetStorage = $this->entityTypeManager->getStorage('dashboard_widget');
      $widgetIds = $widgetStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('dashboard_id', $dashboardId)
        ->condition('widget_status', 'active')
        ->sort('position', 'ASC')
        ->execute();

      $widgets = [];
      $widgetEntities = $widgetStorage->loadMultiple($widgetIds);

      /** @var \Drupal\jaraba_analytics\Entity\DashboardWidget $widget */
      foreach ($widgetEntities as $widget) {
        $widgets[] = [
          'id' => (int) $widget->id(),
          'name' => $widget->getName(),
          'widget_type' => $widget->getWidgetType(),
          'data_source' => $widget->getDataSource(),
          'query_config' => $widget->getQueryConfig(),
          'display_config' => $widget->getDisplayConfig(),
          'position' => $widget->getParsedPosition(),
        ];
      }

      $canEdit = $this->currentUser()->hasPermission('manage analytics dashboards');

      return [
        '#theme' => 'analytics_dashboard_view',
        '#dashboard' => $dashboard,
        '#widgets' => $widgets,
        '#can_edit' => $canEdit,
        '#attached' => [
          'library' => [
            'jaraba_analytics/widget-renderer',
            'jaraba_analytics/dashboard-view',
          ],
          'drupalSettings' => [
            'jarabaDashboard' => [
              'dashboardId' => $dashboardId,
              'widgets' => $widgets,
              'layout' => $dashboard['layout_config'],
            ],
          ],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_analytics')->error('Failed to view dashboard @id: @message', [
        '@id' => $dashboardId,
        '@message' => $e->getMessage(),
      ]);
      return [
        '#markup' => $this->t('Unable to load dashboard. Please try again later.'),
      ];
    }
  }

  /**
   * Renders the dashboard builder (drag-and-drop editor).
   *
   * @param int $dashboardId
   *   The dashboard entity ID.
   *
   * @return array
   *   Render array for the builder page.
   */
  public function builderPage(int $dashboardId): array {
    try {
      $dashboard = $this->dashboardManager->getDashboard($dashboardId);

      if (!$dashboard) {
        return [
          '#markup' => $this->t('Dashboard not found.'),
        ];
      }

      // Load existing widgets.
      $widgetStorage = $this->entityTypeManager->getStorage('dashboard_widget');
      $widgetIds = $widgetStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('dashboard_id', $dashboardId)
        ->sort('position', 'ASC')
        ->execute();

      $widgets = [];
      $widgetEntities = $widgetStorage->loadMultiple($widgetIds);

      /** @var \Drupal\jaraba_analytics\Entity\DashboardWidget $widget */
      foreach ($widgetEntities as $widget) {
        $widgets[] = [
          'id' => (int) $widget->id(),
          'name' => $widget->getName(),
          'widget_type' => $widget->getWidgetType(),
          'data_source' => $widget->getDataSource(),
          'query_config' => $widget->getQueryConfig(),
          'display_config' => $widget->getDisplayConfig(),
          'position' => $widget->getParsedPosition(),
          'widget_status' => $widget->getWidgetStatus(),
        ];
      }

      // Available widget types for the palette.
      $widgetTypes = [
        'line_chart' => $this->t('Line Chart'),
        'bar_chart' => $this->t('Bar Chart'),
        'pie_chart' => $this->t('Pie Chart'),
        'number_card' => $this->t('Number Card'),
        'table' => $this->t('Table'),
        'funnel' => $this->t('Funnel'),
        'cohort_heatmap' => $this->t('Cohort Heatmap'),
      ];

      return [
        '#theme' => 'analytics_dashboard_builder',
        '#dashboard' => $dashboard,
        '#widgets' => $widgets,
        '#widget_types' => $widgetTypes,
        '#attached' => [
          'library' => [
            'jaraba_analytics/dashboard-builder',
            'jaraba_analytics/widget-renderer',
          ],
          'drupalSettings' => [
            'jarabaDashboardBuilder' => [
              'dashboardId' => $dashboardId,
              'widgets' => $widgets,
              'layout' => $dashboard['layout_config'],
              'widgetTypes' => $widgetTypes,
              'apiBase' => '/api/v1/analytics/dashboards/' . $dashboardId,
            ],
          ],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_analytics')->error('Failed to load builder for dashboard @id: @message', [
        '@id' => $dashboardId,
        '@message' => $e->getMessage(),
      ]);
      return [
        '#markup' => $this->t('Unable to load dashboard builder. Please try again later.'),
      ];
    }
  }

}
