<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Business Hours Schedule config entity.
 *
 * Configurable business hours calendars with timezone support
 * and holiday definitions. Used by SlaEngineService for accurate
 * business-hours-only SLA deadline calculations.
 *
 * Closes GAP-SUP-02.
 *
 * @ConfigEntityType(
 *   id = "business_hours_schedule",
 *   label = @Translation("Business Hours Schedule"),
 *   label_collection = @Translation("Business Hours Schedules"),
 *   label_singular = @Translation("business hours schedule"),
 *   label_plural = @Translation("business hours schedules"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_support\BusinessHoursScheduleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_support\Form\BusinessHoursScheduleForm",
 *       "edit" = "Drupal\jaraba_support\Form\BusinessHoursScheduleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "business_hours",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "timezone",
 *     "schedule",
 *     "holidays",
 *     "active",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/support/business-hours/add",
 *     "edit-form" = "/admin/config/support/business-hours/{business_hours_schedule}",
 *     "delete-form" = "/admin/config/support/business-hours/{business_hours_schedule}/delete",
 *     "collection" = "/admin/config/support/business-hours",
 *   },
 * )
 */
class BusinessHoursSchedule extends ConfigEntityBase {

  /**
   * @var string
   */
  protected $id;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $timezone = 'Europe/Madrid';

  /**
   * @var array
   */
  protected $schedule = [];

  /**
   * @var array
   */
  protected $holidays = [];

  /**
   * @var bool
   */
  protected $active = TRUE;

  /**
   * Gets the timezone string.
   */
  public function getTimezone(): string {
    return $this->timezone ?? 'Europe/Madrid';
  }

  /**
   * Gets the weekly schedule.
   *
   * @return array
   *   Keyed by day name: {monday: {start: "09:00", end: "18:00"}, ...}.
   */
  public function getSchedule(): array {
    return $this->schedule ?? [];
  }

  /**
   * Gets the holiday list.
   *
   * @return array
   *   Array of {date: "YYYY-MM-DD", name: "..."}.
   */
  public function getHolidays(): array {
    return $this->holidays ?? [];
  }

}
