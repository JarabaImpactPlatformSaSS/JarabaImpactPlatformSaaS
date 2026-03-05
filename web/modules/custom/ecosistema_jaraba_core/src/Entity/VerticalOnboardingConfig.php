<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the VerticalOnboardingConfig config entity.
 *
 * P1-01: Configuracion de onboarding por vertical.
 * Almacena beneficios, next_steps, titulo de onboarding y flags
 * como connect_required (Stripe Connect) por cada vertical.
 *
 * @ConfigEntityType(
 *   id = "vertical_onboarding_config",
 *   label = @Translation("Vertical Onboarding Config"),
 *   label_collection = @Translation("Vertical Onboarding Configs"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Config\Entity\ConfigEntityListBuilder",
 *   },
 *   config_prefix = "vertical_onboarding",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "vertical_id",
 *     "headline",
 *     "subheadline",
 *     "benefits",
 *     "next_steps",
 *     "connect_required",
 *     "post_onboarding_route",
 *   },
 * )
 */
class VerticalOnboardingConfig extends ConfigEntityBase {

  /**
   * The config ID (matches vertical machine name).
   */
  protected string $id = '';

  /**
   * The human-readable label.
   */
  protected string $label = '';

  /**
   * The vertical ID this config belongs to.
   */
  protected string $vertical_id = '';

  /**
   * Headline for the register page.
   */
  protected string $headline = '';

  /**
   * Subheadline for the register page.
   */
  protected string $subheadline = '';

  /**
   * Array of benefits to show on registration page.
   *
   * Each benefit: ['icon_category' => '', 'icon_name' => '', 'text' => ''].
   *
   * @var array
   */
  protected array $benefits = [];

  /**
   * Array of next steps to show on welcome page.
   *
   * Each step: ['title' => '', 'description' => '', 'route' => '', 'icon' => ''].
   *
   * @var array
   */
  protected array $next_steps = [];

  /**
   * Whether Stripe Connect is required after onboarding.
   */
  protected bool $connect_required = FALSE;

  /**
   * Route to redirect after onboarding completion.
   */
  protected string $post_onboarding_route = '';

  /**
   * Gets the headline.
   */
  public function getHeadline(): string {
    return $this->headline;
  }

  /**
   * Gets the subheadline.
   */
  public function getSubheadline(): string {
    return $this->subheadline;
  }

  /**
   * Gets the benefits array.
   */
  public function getBenefits(): array {
    return $this->benefits;
  }

  /**
   * Gets the next steps array.
   */
  public function getNextSteps(): array {
    return $this->next_steps;
  }

  /**
   * Whether Stripe Connect is required.
   */
  public function isConnectRequired(): bool {
    return $this->connect_required;
  }

  /**
   * Gets the post-onboarding route.
   */
  public function getPostOnboardingRoute(): string {
    return $this->post_onboarding_route;
  }

  /**
   * Gets the vertical ID.
   */
  public function getVerticalId(): string {
    return $this->vertical_id;
  }

}
