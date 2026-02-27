<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Feature Flag config entity (HAL-AI-11).
 *
 * Provides runtime feature flags that can be toggled without deploy.
 * Supports 5 scopes: global, vertical, tenant, plan, percentage.
 *
 * @ConfigEntityType(
 *   id = "feature_flag",
 *   label = @Translation("Feature Flag"),
 *   config_prefix = "flag",
 *   admin_permission = "administer feature flags",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "enabled",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "enabled",
 *     "scope",
 *     "conditions",
 *   },
 * )
 */
class FeatureFlag extends ConfigEntityBase {

  /**
   * The flag machine name.
   */
  protected string $id;

  /**
   * The flag human-readable name.
   */
  protected string $label;

  /**
   * Description of what this flag controls.
   */
  protected string $description = '';

  /**
   * Whether the flag is enabled.
   */
  protected bool $enabled = FALSE;

  /**
   * Scope: global, vertical, tenant, plan, percentage.
   */
  protected string $scope = 'global';

  /**
   * Conditions for non-global scopes (JSON-decoded).
   *
   * Examples:
   * - plan scope: {"plans": ["professional", "business", "enterprise"]}
   * - tenant scope: {"tenant_ids": [1, 2, 5]}
   * - vertical scope: {"verticals": ["empleabilidad", "formacion"]}
   * - percentage scope: {"percentage": 25}
   */
  protected array $conditions = [];

  /**
   * Gets the description.
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * Gets the scope.
   */
  public function getScope(): string {
    return $this->scope;
  }

  /**
   * Gets the conditions.
   */
  public function getConditions(): array {
    return $this->conditions;
  }

  /**
   * Checks if the flag is enabled.
   */
  public function isEnabled(): bool {
    return $this->enabled;
  }

}
