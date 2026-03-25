<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

/**
 * Collects and organizes DailyAction tagged services.
 *
 * Pattern: identical to SetupWizardRegistry (SETUP-WIZARD-DAILY-001).
 * Actions are collected via DailyActionsCompilerPass and grouped by dashboardId.
 *
 * Unlike SetupWizardRegistry, daily actions include dynamic context data
 * (badges, visibility) computed at render time via getContext().
 *
 * Usage in controllers:
 *   $actions = $this->dailyActionsRegistry->getActionsForDashboard('coordinador_ei', $tenantId);
 *
 * Usage in Twig (via preprocess):
 *   {% include '.../_daily-actions.html.twig' with { daily_actions: daily_actions } only %}
 *
 * @see \Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface
 * @see \Drupal\ecosistema_jaraba_core\DependencyInjection\Compiler\DailyActionsCompilerPass
 */
class DailyActionsRegistry {

  /**
   * Collected daily actions, grouped by dashboard ID.
   *
   * @var \Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface[][]
   */
  protected array $actions = [];

  /**
   * Registers an action into the registry.
   *
   * Called by DailyActionsCompilerPass for each tagged service.
   */
  public function addAction(DailyActionInterface $action): void {
    $this->actions[$action->getDashboardId()][] = $action;
  }

  /**
   * Quick check if a dashboard has any registered actions.
   */
  public function hasDashboard(string $dashboardId): bool {
    return isset($this->actions[$dashboardId]) && $this->actions[$dashboardId] !== []
        || isset($this->actions[self::GLOBAL_DASHBOARD_ID]) && $this->actions[self::GLOBAL_DASHBOARD_ID] !== [];
  }

  /**
   * Returns daily actions for a dashboard, sorted by weight, with context.
   *
   * Actions with visible=FALSE in their context are filtered out.
   * The result is ready for Twig rendering via _daily-actions.html.twig.
   *
   * @param string $dashboardId
   *   The dashboard identifier (e.g., 'coordinador_ei').
   * @param int $tenantId
   *   The tenant ID for computing getContext().
   *
   * @return array<int, array{
   *   id: string,
   *   label: \Drupal\Core\StringTranslation\TranslatableMarkup,
   *   description: \Drupal\Core\StringTranslation\TranslatableMarkup,
   *   icon: array{category: string, name: string, variant: string},
   *   color: string,
   *   route: string,
   *   route_params: array,
   *   href_override: ?string,
   *   use_slide_panel: bool,
   *   slide_panel_size: string,
   *   is_primary: bool,
   *   badge: ?int,
   *   badge_type: string,
   * }>
   */
  /**
   * Global dashboard ID for actions injected into ALL dashboards.
   *
   * Mirrors SetupWizardRegistry's __global__ pattern (ZEIGARNIK-PRELOAD-001).
   * Actions with this dashboard ID are appended to EVERY dashboard's actions.
   * Typical use: cross-vertical actions like "Create page" or "Create article"
   * that every tenant user should see regardless of their avatar.
   */
  public const GLOBAL_DASHBOARD_ID = '__global__';

  /**
   *
   */
  public function getActionsForDashboard(string $dashboardId, int $tenantId): array {
    $dashboardActions = $this->actions[$dashboardId] ?? [];

    // Merge global actions into every dashboard (like __global__ wizard steps).
    $globalActions = $this->actions[self::GLOBAL_DASHBOARD_ID] ?? [];
    if ($globalActions !== [] && $dashboardId !== self::GLOBAL_DASHBOARD_ID) {
      $dashboardActions = array_merge($dashboardActions, $globalActions);
    }

    if ($dashboardActions === []) {
      return [];
    }
    usort($dashboardActions, fn(DailyActionInterface $a, DailyActionInterface $b) => $a->getWeight() <=> $b->getWeight());

    $result = [];
    foreach ($dashboardActions as $action) {
      $context = $action->getContext($tenantId);

      // Skip actions that are not visible for this tenant.
      if (!($context['visible'] ?? TRUE)) {
        continue;
      }

      $result[] = [
        'id' => $action->getId(),
        'label' => $action->getLabel(),
        'description' => $action->getDescription(),
        'icon' => $action->getIcon(),
        'color' => $action->getColor(),
        'route' => $action->getRoute(),
        'route_params' => $action->getRouteParameters(),
        'href_override' => $action->getHrefOverride(),
        'use_slide_panel' => $action->useSlidePanel(),
        'slide_panel_size' => $action->getSlidePanelSize(),
        'is_primary' => $action->isPrimary(),
        'badge' => $context['badge'] ?? NULL,
        'badge_type' => $context['badge_type'] ?? 'info',
      ];
    }

    return $result;
  }

}
