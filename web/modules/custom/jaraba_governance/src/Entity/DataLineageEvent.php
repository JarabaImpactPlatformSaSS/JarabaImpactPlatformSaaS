<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the DataLineageEvent entity (APPEND-ONLY).
 *
 * STRUCTURE:
 * Immutable audit trail that records every data lifecycle event.
 * Once created, events cannot be updated or deleted.
 *
 * BUSINESS LOGIC:
 * - Supports 7 event types: created, updated, read, exported, deleted,
 *   anonymized, transferred.
 * - Actor types distinguish human users, system processes, AI agents,
 *   and API clients.
 * - Metadata field stores arbitrary JSON context per event.
 *
 * @ContentEntityType(
 *   id = "data_lineage_event",
 *   label = @Translation("Data Lineage Event"),
 *   label_collection = @Translation("Data Lineage Events"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_governance\Entity\DataLineageEventAccessControlHandler",
 *   },
 *   base_table = "data_lineage_event",
 *   admin_permission = "administer data governance",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/data-lineage-events",
 *   },
 *   field_ui_base_route = "entity.data_lineage_event.settings",
 * )
 */
class DataLineageEvent extends ContentEntityBase implements DataLineageEventInterface {

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType(): string {
    return (string) $this->get('target_entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityId(): int {
    return (int) $this->get('target_entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventType(): string {
    return (string) $this->get('event_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getActorId(): ?int {
    $value = $this->get('actor_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getActorType(): string {
    return (string) $this->get('actor_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceSystem(): ?string {
    return $this->get('source_system')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDestinationSystem(): ?string {
    return $this->get('destination_system')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataArray(): array {
    $raw = $this->get('metadata')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return (int) $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
      ->setRequired(FALSE);

    $fields['target_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Target Entity Type'))
      ->setDescription(t('The entity type this lineage event applies to.'))
      ->setSettings(['max_length' => 128])
      ->setRequired(TRUE);

    $fields['target_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Target Entity ID'))
      ->setDescription(t('The entity ID this lineage event applies to.'))
      ->setRequired(TRUE);

    $fields['event_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Event Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'created' => t('Created'),
        'updated' => t('Updated'),
        'read' => t('Read'),
        'exported' => t('Exported'),
        'deleted' => t('Deleted'),
        'anonymized' => t('Anonymized'),
        'transferred' => t('Transferred'),
      ]);

    $fields['actor_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Actor'))
      ->setSetting('target_type', 'user');

    $fields['actor_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Actor Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'user' => t('User'),
        'system' => t('System'),
        'agent' => t('AI Agent'),
        'api_client' => t('API Client'),
      ])
      ->setDefaultValue('user');

    $fields['source_system'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source System'))
      ->setSettings(['max_length' => 128]);

    $fields['destination_system'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Destination System'))
      ->setSettings(['max_length' => 128]);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadata (JSON)'))
      ->setDescription(t('Arbitrary JSON metadata for this lineage event.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
