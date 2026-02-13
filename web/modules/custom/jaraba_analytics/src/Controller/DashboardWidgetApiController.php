<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_analytics\Service\AnalyticsDataService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador de API REST para widgets de dashboard.
 *
 * PROPOSITO:
 * Expone endpoints REST para operaciones CRUD de widgets y
 * obtencion de datos para renderizado de widgets.
 *
 * LOGICA:
 * - GET /api/v1/analytics/dashboards/{id}/widgets: lista widgets.
 * - POST /api/v1/analytics/dashboards/{id}/widgets: crea widget.
 * - PATCH /api/v1/analytics/widgets/{id}: actualiza widget.
 * - DELETE /api/v1/analytics/widgets/{id}: elimina widget.
 * - GET /api/v1/analytics/widgets/{id}/data: datos del widget.
 */
class DashboardWidgetApiController extends ControllerBase {

  /**
   * Analytics data service.
   *
   * @var \Drupal\jaraba_analytics\Service\AnalyticsDataService
   */
  protected AnalyticsDataService $analyticsDataService;

  /**
   * Servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * Constructor.
   */
  public function __construct(
    AnalyticsDataService $analytics_data_service,
    EntityTypeManagerInterface $entity_type_manager,
    TenantContextService $tenant_context,
  ) {
    $this->analyticsDataService = $analytics_data_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_analytics.analytics_data'),
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context')
    );
  }

  /**
   * GET - Lists all widgets for a dashboard.
   *
   * @param int $dashboardId
   *   The dashboard entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with widget list.
   */
  public function listWidgets(int $dashboardId, Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('dashboard_widget');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('dashboard_id', $dashboardId)
        ->sort('position', 'ASC');

      $includeHidden = $request->query->get('include_hidden', FALSE);
      if (!$includeHidden) {
        $query->condition('widget_status', 'active');
      }

      $ids = $query->execute();
      $widgets = $storage->loadMultiple($ids);

      $data = [];
      /** @var \Drupal\jaraba_analytics\Entity\DashboardWidget $widget */
      foreach ($widgets as $widget) {
        $data[] = [
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

      return new JsonResponse([
        'widgets' => $data,
        'total' => count($data),
        'dashboard_id' => $dashboardId,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Failed to load widgets: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * POST - Creates a new widget for a dashboard.
   *
   * @param int $dashboardId
   *   The dashboard entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object with JSON body.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with created widget data.
   */
  public function createWidget(int $dashboardId, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (!$content || empty($content['name']) || empty($content['widget_type'])) {
      return new JsonResponse([
        'error' => 'Missing required fields: name, widget_type.',
      ], 400);
    }

    $validTypes = [
      'line_chart', 'bar_chart', 'pie_chart',
      'number_card', 'table', 'funnel', 'cohort_heatmap',
    ];

    if (!in_array($content['widget_type'], $validTypes, TRUE)) {
      return new JsonResponse([
        'error' => 'Invalid widget_type. Valid values: ' . implode(', ', $validTypes),
      ], 400);
    }

    try {
      $storage = $this->entityTypeManager->getStorage('dashboard_widget');

      $values = [
        'name' => $content['name'],
        'widget_type' => $content['widget_type'],
        'dashboard_id' => $dashboardId,
        'data_source' => $content['data_source'] ?? '',
        'position' => $content['position'] ?? '1:1:4:3',
        'widget_status' => $content['widget_status'] ?? 'active',
      ];

      if (!empty($content['query_config'])) {
        $values['query_config'] = is_array($content['query_config'])
          ? json_encode($content['query_config'])
          : $content['query_config'];
      }

      if (!empty($content['display_config'])) {
        $values['display_config'] = is_array($content['display_config'])
          ? json_encode($content['display_config'])
          : $content['display_config'];
      }

      $widget = $storage->create($values);
      $widget->save();

      return new JsonResponse([
        'success' => TRUE,
        'widget_id' => (int) $widget->id(),
        'name' => $widget->get('name')->value,
      ], 201);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Failed to create widget: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * PATCH - Updates an existing widget.
   *
   * @param int $widgetId
   *   The widget entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object with JSON body.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with update status.
   */
  public function updateWidget(int $widgetId, Request $request): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);

    if (!$content) {
      return new JsonResponse([
        'error' => 'Invalid JSON body.',
      ], 400);
    }

    try {
      $storage = $this->entityTypeManager->getStorage('dashboard_widget');
      /** @var \Drupal\jaraba_analytics\Entity\DashboardWidget|null $widget */
      $widget = $storage->load($widgetId);

      if (!$widget) {
        return new JsonResponse([
          'error' => 'Widget not found.',
        ], 404);
      }

      $allowedFields = [
        'name', 'widget_type', 'data_source', 'position',
        'widget_status', 'query_config', 'display_config',
      ];

      foreach ($allowedFields as $field) {
        if (isset($content[$field])) {
          $value = $content[$field];
          // Encode arrays to JSON for string_long fields.
          if (in_array($field, ['query_config', 'display_config'], TRUE) && is_array($value)) {
            $value = json_encode($value);
          }
          $widget->set($field, $value);
        }
      }

      $widget->save();

      return new JsonResponse([
        'success' => TRUE,
        'widget_id' => $widgetId,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Failed to update widget: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * DELETE - Deletes a widget.
   *
   * @param int $widgetId
   *   The widget entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with delete status.
   */
  public function deleteWidget(int $widgetId, Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('dashboard_widget');
      $widget = $storage->load($widgetId);

      if (!$widget) {
        return new JsonResponse([
          'error' => 'Widget not found.',
        ], 404);
      }

      $widget->delete();

      return new JsonResponse([
        'success' => TRUE,
        'deleted_widget_id' => $widgetId,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Failed to delete widget: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * GET - Gets rendered data for a widget.
   *
   * @param int $widgetId
   *   The widget entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with widget data.
   */
  public function getWidgetData(int $widgetId, Request $request): JsonResponse {
    try {
      $storage = $this->entityTypeManager->getStorage('dashboard_widget');
      /** @var \Drupal\jaraba_analytics\Entity\DashboardWidget|null $widget */
      $widget = $storage->load($widgetId);

      if (!$widget) {
        return new JsonResponse([
          'error' => 'Widget not found.',
        ], 404);
      }

      $queryConfig = $widget->getQueryConfig();
      $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');
      $tenantIdInt = $tenantId ? (int) $tenantId : NULL;

      $data = $this->analyticsDataService->executeQuery($queryConfig, $tenantIdInt);

      return new JsonResponse([
        'widget_id' => $widgetId,
        'widget_type' => $widget->getWidgetType(),
        'display_config' => $widget->getDisplayConfig(),
        'data' => $data,
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Failed to get widget data: ' . $e->getMessage(),
      ], 500);
    }
  }

}
