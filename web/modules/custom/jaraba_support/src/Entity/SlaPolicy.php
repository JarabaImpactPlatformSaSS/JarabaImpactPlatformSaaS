<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the SLA Policy config entity.
 *
 * Configurable SLA policies by plan tier and priority.
 * ID pattern: {plan_tier}_{priority} (e.g., professional_high).
 *
 * SPEC: 178 — Section 3.4 & 5.1
 *
 * @ConfigEntityType(
 *   id = "sla_policy",
 *   label = @Translation("SLA Policy"),
 *   label_collection = @Translation("SLA Policies"),
 *   label_singular = @Translation("SLA policy"),
 *   label_plural = @Translation("SLA policies"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_support\SlaPolicyListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_support\Form\SlaPolicyForm",
 *       "edit" = "Drupal\jaraba_support\Form\SlaPolicyForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "sla_policy",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plan_tier",
 *     "priority",
 *     "first_response_hours",
 *     "resolution_hours",
 *     "business_hours_only",
 *     "business_hours_schedule_id",
 *     "escalation_after_hours",
 *     "includes_phone",
 *     "includes_priority_queue",
 *     "idle_reminder_hours",
 *     "auto_close_hours",
 *     "pause_on_pending",
 *     "active",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/support/sla-policies/add",
 *     "edit-form" = "/admin/config/support/sla-policies/{sla_policy}",
 *     "delete-form" = "/admin/config/support/sla-policies/{sla_policy}/delete",
 *     "collection" = "/admin/config/support/sla-policies",
 *   },
 * )
 */
class SlaPolicy extends ConfigEntityBase implements SlaPolicyInterface {

  /**
   * The SLA policy ID ({plan_tier}_{priority}).
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $plan_tier = 'starter';

  /**
   * @var string
   */
  protected $priority = 'medium';

  /**
   * @var int
   */
  protected $first_response_hours = 24;

  /**
   * @var int
   */
  protected $resolution_hours = 120;

  /**
   * @var bool
   */
  protected $business_hours_only = TRUE;

  /**
   * @var string
   */
  protected $business_hours_schedule_id = 'spain_standard';

  /**
   * @var int
   */
  protected $escalation_after_hours = 0;

  /**
   * @var bool
   */
  protected $includes_phone = FALSE;

  /**
   * @var bool
   */
  protected $includes_priority_queue = FALSE;

  /**
   * @var int
   */
  protected $idle_reminder_hours = 48;

  /**
   * @var int
   */
  protected $auto_close_hours = 168;

  /**
   * @var bool
   */
  protected $pause_on_pending = TRUE;

  /**
   * @var bool
   */
  protected $active = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getPlanTier(): string {
    return $this->plan_tier ?? 'starter';
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): string {
    return $this->priority ?? 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstResponseHours(): int {
    return (int) ($this->first_response_hours ?? 24);
  }

  /**
   * {@inheritdoc}
   */
  public function getResolutionHours(): int {
    return (int) ($this->resolution_hours ?? 120);
  }

  /**
   * {@inheritdoc}
   */
  public function isBusinessHoursOnly(): bool {
    return (bool) ($this->business_hours_only ?? TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isPauseOnPending(): bool {
    return (bool) ($this->pause_on_pending ?? TRUE);
  }

}
