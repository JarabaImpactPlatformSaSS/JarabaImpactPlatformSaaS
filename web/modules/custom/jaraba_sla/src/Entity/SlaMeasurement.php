<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the SLA Measurement entity (append-only).
 *
 * STRUCTURE:
 * Immutable measurement records capturing uptime data per period.
 * Once created, measurements cannot be updated or deleted (append-only audit log).
 *
 * LOGIC:
 * Each record represents a billing period (typically monthly) for a specific
 * SLA agreement. The SlaCalculatorService generates these from incident data.
 * The SlaCreditEngineService uses measurements to calculate credits.
 *
 * RELATIONS:
 * - SlaMeasurement -> Group (tenant_id): owning tenant.
 * - SlaMeasurement -> SlaAgreement (agreement_id): associated agreement.
 *
 * @ContentEntityType(
 *   id = "sla_measurement",
 *   label = @Translation("SLA Measurement"),
 *   label_collection = @Translation("SLA Measurements"),
 *   label_singular = @Translation("SLA measurement"),
 *   label_plural = @Translation("SLA measurements"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\jaraba_sla\Access\SlaMeasurementAccessControlHandler",
 *   },
 *   base_table = "sla_measurement",
 *   admin_permission = "administer sla",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/sla-measurements",
 *   },
 *   field_ui_base_route = "entity.sla_measurement.settings",
 * )
 */
class SlaMeasurement extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOCK 1: MULTI-TENANT + AGREEMENT REFERENCE
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant that owns this measurement.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['agreement_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('SLA Agreement'))
      ->setDescription(new TranslatableMarkup('The SLA agreement this measurement belongs to.'))
      ->setSetting('target_type', 'sla_agreement')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 2: PERIOD
    // =========================================================================

    $fields['period_start'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Period Start'))
      ->setDescription(new TranslatableMarkup('Start of the measurement period.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_end'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Period End'))
      ->setDescription(new TranslatableMarkup('End of the measurement period.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 3: METRICS
    // =========================================================================

    $fields['total_minutes'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Total Minutes'))
      ->setDescription(new TranslatableMarkup('Total minutes in the measurement period.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['downtime_minutes'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Downtime Minutes'))
      ->setDescription(new TranslatableMarkup('Total downtime minutes in the period.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayConfigurable('view', TRUE);

    $fields['uptime_pct'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Uptime Percentage'))
      ->setDescription(new TranslatableMarkup('Calculated uptime percentage for the period.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 3)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sla_met'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('SLA Met'))
      ->setDescription(new TranslatableMarkup('Whether the SLA target was met for this period.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 4: CREDITS
    // =========================================================================

    $fields['credit_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Credit Amount'))
      ->setDescription(new TranslatableMarkup('Credit percentage awarded for this period.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 5: DETAILS
    // =========================================================================

    $fields['incidents'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Incidents'))
      ->setDescription(new TranslatableMarkup('JSON array of incident references for this period.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['excluded_maintenance_minutes'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Excluded Maintenance Minutes'))
      ->setDescription(new TranslatableMarkup('Planned maintenance minutes excluded from SLA calculation.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 6: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'));

    return $fields;
  }

}
