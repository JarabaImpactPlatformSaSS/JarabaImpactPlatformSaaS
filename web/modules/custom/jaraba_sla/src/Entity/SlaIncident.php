<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the SLA Incident entity.
 *
 * STRUCTURE:
 * Tracks incidents affecting platform components. Each incident has a lifecycle:
 * investigating -> identified -> monitoring -> resolved -> postmortem.
 *
 * LOGIC:
 * Incidents are created by UptimeMonitorService when a component goes down.
 * Duration is calculated on resolution. PostmortemService enriches resolved
 * incidents with root_cause, timeline, and preventive_actions.
 *
 * RELATIONS:
 * - SlaIncident -> Group (tenant_id): affected tenant.
 *
 * @ContentEntityType(
 *   id = "sla_incident",
 *   label = @Translation("SLA Incident"),
 *   label_collection = @Translation("SLA Incidents"),
 *   label_singular = @Translation("SLA incident"),
 *   label_plural = @Translation("SLA incidents"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\jaraba_sla\Access\SlaIncidentAccessControlHandler",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "sla_incident",
 *   admin_permission = "administer sla",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "collection" = "/admin/content/sla-incidents",
 *   },
 *   field_ui_base_route = "entity.sla_incident.settings",
 * )
 */
class SlaIncident extends ContentEntityBase implements SlaIncidentInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getComponent(): string {
    return (string) ($this->get('component')->value ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity(): string {
    return (string) ($this->get('severity')->value ?? 'sev4');
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) ($this->get('status')->value ?? 'investigating');
  }

  /**
   * {@inheritdoc}
   */
  public function getDurationMinutes(): ?float {
    $value = $this->get('duration_minutes')->value;
    return $value !== NULL ? (float) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    $tid = $this->get('tenant_id')->target_id;
    return $tid !== NULL ? (int) $tid : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOCK 1: MULTI-TENANT
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant affected by this incident.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 2: INCIDENT CLASSIFICATION
    // =========================================================================

    $fields['component'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Component'))
      ->setDescription(new TranslatableMarkup('The affected platform component.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'web_app' => new TranslatableMarkup('Web Application'),
        'api' => new TranslatableMarkup('API'),
        'database' => new TranslatableMarkup('Database'),
        'redis' => new TranslatableMarkup('Redis Cache'),
        'email' => new TranslatableMarkup('Email Service'),
        'ai_copilot' => new TranslatableMarkup('AI Copilot'),
        'payment' => new TranslatableMarkup('Payment Gateway'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Severity'))
      ->setDescription(new TranslatableMarkup('The incident severity level.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'sev1' => new TranslatableMarkup('SEV1 - Critical'),
        'sev2' => new TranslatableMarkup('SEV2 - Major'),
        'sev3' => new TranslatableMarkup('SEV3 - Minor'),
        'sev4' => new TranslatableMarkup('SEV4 - Low'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 3: DESCRIPTION
    // =========================================================================

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Title'))
      ->setDescription(new TranslatableMarkup('Brief description of the incident.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDescription(new TranslatableMarkup('Detailed description of the incident.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 4: TIMING
    // =========================================================================

    $fields['started_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Started At'))
      ->setDescription(new TranslatableMarkup('When the incident started.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolved_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Resolved At'))
      ->setDescription(new TranslatableMarkup('When the incident was resolved.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration_minutes'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Duration (minutes)'))
      ->setDescription(new TranslatableMarkup('Calculated duration of the incident in minutes.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 5: POSTMORTEM
    // =========================================================================

    $fields['root_cause'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Root Cause'))
      ->setDescription(new TranslatableMarkup('Root cause analysis of the incident.'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['preventive_actions'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Preventive Actions'))
      ->setDescription(new TranslatableMarkup('Actions taken to prevent recurrence.'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['timeline'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Timeline'))
      ->setDescription(new TranslatableMarkup('JSON array of timeline entries for the incident.'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 6: STATUS
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('Current status of the incident.'))
      ->setRequired(TRUE)
      ->setDefaultValue('investigating')
      ->setSetting('allowed_values', [
        'investigating' => new TranslatableMarkup('Investigating'),
        'identified' => new TranslatableMarkup('Identified'),
        'monitoring' => new TranslatableMarkup('Monitoring'),
        'resolved' => new TranslatableMarkup('Resolved'),
        'postmortem' => new TranslatableMarkup('Postmortem Complete'),
      ])
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOCK 7: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'));

    return $fields;
  }

}
