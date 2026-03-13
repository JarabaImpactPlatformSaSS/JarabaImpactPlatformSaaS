<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Interface for a single step in a Setup Wizard.
 *
 * Each step represents a configuration task that must be completed
 * before the system is fully operational. Steps are collected via
 * tagged services (tag: ecosistema_jaraba_core.setup_wizard_step)
 * and organized by wizardId.
 *
 * Pattern: SETUP-WIZARD-DAILY-001 (transversal SaaS standard).
 * Identical collection pattern to TenantSettingsSectionInterface.
 *
 * @see \Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardRegistry
 */
interface SetupWizardStepInterface {

  /**
   * Returns the unique step ID within its wizard.
   *
   * Convention: '{wizard_id}.{step_name}'
   * Example: 'coordinador_ei.plan_formativo'.
   */
  public function getId(): string;

  /**
   * Returns the wizard this step belongs to.
   *
   * Multiple steps with the same wizardId are grouped together.
   * Convention: module_name + role (e.g., 'coordinador_ei', 'candidato_empleo').
   */
  public function getWizardId(): string;

  /**
   * Human-readable label for the step.
   *
   * MUST use TranslatableMarkup for i18n.
   */
  public function getLabel(): TranslatableMarkup;

  /**
   * Short description explaining what this step achieves.
   *
   * Displayed below the step title in the wizard UI.
   * MUST use TranslatableMarkup.
   */
  public function getDescription(): TranslatableMarkup;

  /**
   * Ordering weight. Lower values appear first.
   *
   * Steps within a wizard are sorted ascending by weight.
   * Convention: use multiples of 10 (10, 20, 30...) to allow insertion.
   */
  public function getWeight(): int;

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
   * Route name for the action button.
   *
   * ROUTE-LANGPREFIX-001: MUST be a Drupal route name, never a hardcoded path.
   * The wizard UI generates the URL via path() in Twig.
   */
  public function getRoute(): string;

  /**
   * Route parameters for the action button.
   *
   * @return array<string, mixed>
   */
  public function getRouteParameters(): array;

  /**
   * Whether this step should open in a slide-panel.
   *
   * If TRUE, the wizard button includes data-slide-panel attributes.
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
   * Determines whether this step is complete for the given tenant.
   *
   * Called on every dashboard load — MUST be fast.
   * Prefer pre-aggregated stats over individual entity queries.
   * TENANT-001: MUST filter by tenant_id internally.
   *
   * @param int $tenantId
   *   The tenant ID to check completeness for.
   */
  public function isComplete(int $tenantId): bool;

  /**
   * Returns completion data for display in the wizard UI.
   *
   * Example: ['count' => 3, 'label' => '3 acciones creadas', 'progress' => 60]
   * - 'count': numeric value for badges
   * - 'label': human-readable status (TranslatableMarkup)
   * - 'progress': 0-100 percentage (optional, for partial completion)
   * - 'warning': string message if there's a blocker (e.g., VoBo pending)
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return array<string, mixed>
   */
  public function getCompletionData(int $tenantId): array;

  /**
   * Whether this step is optional.
   *
   * Optional steps don't block the wizard from showing "complete" status
   * but are displayed with a different visual treatment (dashed border).
   */
  public function isOptional(): bool;

}
