<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad ScheduledReport para informes programados.
 *
 * PROPOSITO:
 * Almacena configuraciones de informes que se envian automaticamente
 * segun una programacion (diaria, semanal, mensual) a una lista
 * de destinatarios por email.
 *
 * LOGICA:
 * - report_config: JSON con query, filtros y formato de salida.
 * - schedule_type: frecuencia de envio (daily, weekly, monthly).
 * - schedule_config: JSON con detalles (day_of_week, time, timezone).
 * - recipients: JSON con lista de emails destinatarios.
 * - last_sent / next_send: timestamps de control de envio.
 * - report_status: active (programado) o paused (detenido).
 * - tenant_id: aislamiento multi-tenant mediante referencia a grupo.
 *
 * @ContentEntityType(
 *   id = "scheduled_report",
 *   label = @Translation("Scheduled Report"),
 *   label_collection = @Translation("Scheduled Reports"),
 *   label_singular = @Translation("scheduled report"),
 *   label_plural = @Translation("scheduled reports"),
 *   label_count = @PluralTranslation(
 *     singular = "@count scheduled report",
 *     plural = "@count scheduled reports",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_analytics\ListBuilder\ScheduledReportListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_analytics\Form\ScheduledReportForm",
 *       "add" = "Drupal\jaraba_analytics\Form\ScheduledReportForm",
 *       "edit" = "Drupal\jaraba_analytics\Form\ScheduledReportForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_analytics\Access\ScheduledReportAccessControlHandler",
 *   },
 *   base_table = "scheduled_report",
 *   admin_permission = "administer jaraba analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/content/scheduled-reports",
 *     "canonical" = "/admin/content/scheduled-reports/{scheduled_report}",
 *     "add-form" = "/admin/content/scheduled-reports/add",
 *     "edit-form" = "/admin/content/scheduled-reports/{scheduled_report}/edit",
 *     "delete-form" = "/admin/content/scheduled-reports/{scheduled_report}/delete",
 *   },
 *   field_ui_base_route = "entity.scheduled_report.settings",
 * )
 */
class ScheduledReport extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Schedule type constants.
   */
  public const SCHEDULE_DAILY = 'daily';
  public const SCHEDULE_WEEKLY = 'weekly';
  public const SCHEDULE_MONTHLY = 'monthly';

  /**
   * Report status constants.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_PAUSED = 'paused';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The scheduled report name.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['report_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Report Configuration'))
      ->setDescription(t('JSON report configuration (query, filters, format).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
        'settings' => [
          'rows' => 5,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['schedule_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Schedule Type'))
      ->setDescription(t('How often the report should be sent.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::SCHEDULE_DAILY => t('Daily'),
        self::SCHEDULE_WEEKLY => t('Weekly'),
        self::SCHEDULE_MONTHLY => t('Monthly'),
      ])
      ->setDefaultValue(self::SCHEDULE_WEEKLY)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schedule_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Schedule Configuration'))
      ->setDescription(t('JSON schedule details (day_of_week, time, timezone).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 15,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['recipients'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Recipients'))
      ->setDescription(t('JSON array of recipient email addresses.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 20,
        'settings' => [
          'rows' => 3,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['last_sent'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Sent'))
      ->setDescription(t('Timestamp of when the report was last sent.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['next_send'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Next Send'))
      ->setDescription(t('Timestamp of when the report will next be sent.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['report_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The report scheduling status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_ACTIVE => t('Active'),
        self::STATUS_PAUSED => t('Paused'),
      ])
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 25,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['owner_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Owner'))
      ->setDescription(t('The user who created this scheduled report.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (group) this report belongs to.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 35,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Gets the report name.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Gets the report configuration as an array.
   */
  public function getReportConfig(): array {
    $value = $this->get('report_config')->value;
    if ($value) {
      $decoded = json_decode($value, TRUE);
      return is_array($decoded) ? $decoded : [];
    }
    return [];
  }

  /**
   * Gets the schedule type.
   */
  public function getScheduleType(): string {
    return $this->get('schedule_type')->value ?? self::SCHEDULE_WEEKLY;
  }

  /**
   * Gets the schedule configuration as an array.
   */
  public function getScheduleConfig(): array {
    $value = $this->get('schedule_config')->value;
    if ($value) {
      $decoded = json_decode($value, TRUE);
      return is_array($decoded) ? $decoded : [];
    }
    return [];
  }

  /**
   * Gets the recipients list.
   */
  public function getRecipients(): array {
    $value = $this->get('recipients')->value;
    if ($value) {
      $decoded = json_decode($value, TRUE);
      return is_array($decoded) ? $decoded : [];
    }
    return [];
  }

  /**
   * Gets the last sent timestamp.
   */
  public function getLastSent(): ?int {
    $value = $this->get('last_sent')->value;
    return $value ? (int) $value : NULL;
  }

  /**
   * Gets the next send timestamp.
   */
  public function getNextSend(): ?int {
    $value = $this->get('next_send')->value;
    return $value ? (int) $value : NULL;
  }

  /**
   * Gets the report status.
   */
  public function getReportStatus(): string {
    return $this->get('report_status')->value ?? self::STATUS_ACTIVE;
  }

  /**
   * Gets the owner user ID.
   */
  public function getOwnerId(): ?int {
    $value = $this->get('owner_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * Gets the tenant ID.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value ? (int) $value : NULL;
  }

}
