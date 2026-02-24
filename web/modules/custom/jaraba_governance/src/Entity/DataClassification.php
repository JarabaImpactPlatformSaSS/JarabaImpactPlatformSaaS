<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the DataClassification entity.
 *
 * STRUCTURE:
 * Stores classification metadata per entity type (or per field).
 * Classification levels: C1_PUBLIC, C2_INTERNAL, C3_CONFIDENTIAL, C4_RESTRICTED.
 *
 * BUSINESS LOGIC:
 * - Drives retention, masking, encryption and cross-border rules.
 * - PII and sensitive flags enable GDPR-specific processing.
 *
 * @ContentEntityType(
 *   id = "data_classification",
 *   label = @Translation("Data Classification"),
 *   label_collection = @Translation("Data Classifications"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_governance\Entity\DataClassificationAccessControlHandler",
 *   },
 *   base_table = "data_classification",
 *   admin_permission = "administer data governance",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "entity_type_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/data-classifications",
 *   },
 *   field_ui_base_route = "entity.data_classification.settings",
 * )
 */
class DataClassification extends ContentEntityBase implements DataClassificationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeClassified(): string {
    return (string) $this->get('entity_type_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(): ?string {
    return $this->get('field_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getClassificationLevel(): string {
    return (string) $this->get('classification_level')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPii(): bool {
    return (bool) $this->get('is_pii')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isSensitive(): bool {
    return (bool) $this->get('is_sensitive')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRetentionDays(): ?int {
    $value = $this->get('retention_days')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isEncryptionRequired(): bool {
    return (bool) $this->get('encryption_required')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isMaskingRequired(): bool {
    return (bool) $this->get('masking_required')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isCrossBorderAllowed(): bool {
    return (bool) $this->get('cross_border_allowed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLegalBasis(): ?string {
    return $this->get('legal_basis')->value;
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

    $fields['entity_type_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The Drupal entity type ID being classified.'))
      ->setSettings(['max_length' => 128])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -10])
      ->setDisplayOptions('form', ['weight' => -10]);

    $fields['field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Field Name'))
      ->setDescription(t('Specific field name, or NULL for entire entity.'))
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -9])
      ->setDisplayOptions('form', ['weight' => -9]);

    $fields['classification_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Classification Level'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'C1_PUBLIC' => t('C1 - Public'),
        'C2_INTERNAL' => t('C2 - Internal'),
        'C3_CONFIDENTIAL' => t('C3 - Confidential'),
        'C4_RESTRICTED' => t('C4 - Restricted'),
      ])
      ->setDefaultValue('C1_PUBLIC')
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -8])
      ->setDisplayOptions('form', ['weight' => -8]);

    $fields['is_pii'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is PII'))
      ->setDescription(t('Whether this data qualifies as Personally Identifiable Information.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -7])
      ->setDisplayOptions('form', ['weight' => -7]);

    $fields['is_sensitive'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is Sensitive (GDPR Art. 9)'))
      ->setDescription(t('Special categories of personal data: race, health, political opinion, etc.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -6])
      ->setDisplayOptions('form', ['weight' => -6]);

    $fields['retention_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retention Days'))
      ->setDescription(t('Number of days to retain data before applying retention action.'))
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -5])
      ->setDisplayOptions('form', ['weight' => -5]);

    $fields['encryption_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Encryption Required'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -4])
      ->setDisplayOptions('form', ['weight' => -4]);

    $fields['masking_required'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Masking Required'))
      ->setDescription(t('Whether PII must be masked when copying to dev/staging.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -3])
      ->setDisplayOptions('form', ['weight' => -3]);

    $fields['cross_border_allowed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Cross-Border Allowed'))
      ->setDescription(t('Whether this data can be transferred outside EU/EEA.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -2])
      ->setDisplayOptions('form', ['weight' => -2]);

    $fields['legal_basis'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Legal Basis'))
      ->setDescription(t('GDPR legal basis (consent, contract, legitimate interest, etc.).'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('view', ['label' => 'above', 'weight' => -1])
      ->setDisplayOptions('form', ['weight' => -1]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
