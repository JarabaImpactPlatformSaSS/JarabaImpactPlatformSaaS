<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for a daily action card on a vertical dashboard.
 *
 * Daily actions represent recurring operational tasks that users perform
 * on a day-to-day basis. Unlike Setup Wizard steps (one-time configuration),
 * daily actions are always visible and contextual.
 *
 * Pattern: SETUP-WIZARD-DAILY-001 (transversal SaaS standard).
 * Identical collection pattern to SetupWizardStepInterface.
 *
 * Collected via tagged services: ecosistema_jaraba_core.daily_action
 * Organized by dashboardId (e.g., 'coordinador_ei', 'merchant_comercio').
 *
 * @see \Drupal\ecosistema_jaraba_core\DailyActions\DailyActionsRegistry
 */
interface DailyActionInterface {

  /**
   * Returns the unique action ID within its dashboard.
   *
   * Convention: '{dashboard_id}.{action_name}'
   * Example: 'coordinador_ei.solicitudes'.
   */
  public function getId(): string;

  /**
   * Returns the dashboard this action belongs to.
   *
   * Multiple actions with the same dashboardId are grouped together.
   * Convention: module_name + role (e.g., 'coordinador_ei', 'merchant_comercio').
   */
  public function getDashboardId(): string;

  /**
   * Human-readable action label.
   *
   * MUST use TranslatableMarkup for i18n.
   */
  public function getLabel(): TranslatableMarkup;

  /**
   * Short description of what this action does.
   *
   * Displayed below the action title in the card.
   * MUST use TranslatableMarkup.
   */
  public function getDescription(): TranslatableMarkup;

  /**
   * Icon configuration for jaraba_icon() Twig function.
   *
   * Returns: ['category' => string, 'name' => string, 'variant' => 'duotone']
   * ICON-DUOTONE-001: default variant MUST be 'duotone'.
   * ICON-COLOR-001: color MUST be from Jaraba palette.
   *
   * @return array{category: string, name: string, variant: string}
   */
  public function getIcon(): array;

  /**
   * Color token for the action card accent.
   *
   * Must be from the Jaraba palette: 'azul-corporativo', 'naranja-impulso',
   * 'verde-innovacion'.
   */
  public function getColor(): string;

  /**
   * Drupal route name for the action.
   *
   * ROUTE-LANGPREFIX-001: MUST be a Drupal route name, never a hardcoded path.
   */
  public function getRoute(): string;

  /**
   * Route parameters for the action.
   *
   * @return array<string, mixed>
   */
  public function getRouteParameters(): array;

  /**
   * Optional href override (e.g., for anchor navigation like #panel-solicitudes).
   *
   * When set, overrides the route-based URL. Useful for on-page navigation.
   */
  public function getHrefOverride(): ?string;

  /**
   * Whether to open the action in a slide-panel.
   *
   * SLIDE-PANEL-RENDER-001: target controller MUST support renderPlain().
   */
  public function useSlidePanel(): bool;

  /**
   * Slide-panel size when useSlidePanel() returns TRUE.
   *
   * Valid values: 'small', 'medium', 'large', 'full'.
   */
  public function getSlidePanelSize(): string;

  /**
   * Ordering weight. Lower values appear first.
   *
   * Convention: use multiples of 10 (10, 20, 30...) to allow insertion.
   */
  public function getWeight(): int;

  /**
   * Whether this is the primary action (displayed as a larger card).
   *
   * Only ONE action per dashboard should return TRUE.
   * The primary action gets grid-column: span 2 in the daily-actions grid.
   */
  public function isPrimary(): bool;

  /**
   * Dynamic context data computed at render time.
   *
   * Called on every dashboard load — MUST be fast (< 50ms).
   * Use count queries, NOT full entity loads.
   * TENANT-001: MUST filter by tenant_id internally.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array{badge: int|null, badge_type: string, visible: bool}
   *   - badge: Optional counter (e.g., pending items). NULL = no badge.
   *   - badge_type: Visual severity: 'info', 'warning', 'critical'.
   *   - visible: Whether to show this action. FALSE hides it entirely.
   */
  public function getContext(int $tenantId): array;

}
