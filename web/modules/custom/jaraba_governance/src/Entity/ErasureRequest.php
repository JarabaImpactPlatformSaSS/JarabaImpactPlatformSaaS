<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the ErasureRequest entity.
 *
 * STRUCTURE:
 * Tracks GDPR data subject requests through their lifecycle:
 * pending -> processing -> completed | rejected.
 *
 * BUSINESS LOGIC:
 * - Supports 4 request types: erasure, rectification, portability, access.
 * - Links requester (who asked) and subject (whose data).
 * - Stores JSON list of affected entity types + IDs after processing.
 *
 * @ContentEntityType(
 *   id = "erasure_request",
 *   label = @Translation("Erasure Request"),
 *   label_collection = @Translation("Erasure Requests"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_governance\Entity\ErasureRequestAccessControlHandler",
 *   },
 *   base_table = "erasure_request",
 *   admin_permission = "administer data governance",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/erasure-requests",
 *   },
 *   field_ui_base_route = "entity.erasure_request.settings",
 * )
 */
class ErasureRequest extends ContentEntityBase implements ErasureRequestInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getRequesterId(): int {
    return (int) $this->get('requester_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubjectUserId(): int {
    return (int) $this->get('subject_user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestType(): string {
    return (string) $this->get('request_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(string $status): self {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getReason(): ?string {
    return $this->get('reason')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitiesAffectedArray(): array {
    $raw = $this->get('entities_affected')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setEntitiesAffected(array $entities): self {
    $this->set('entities_affected', json_encode($entities, JSON_UNESCAPED_UNICODE));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessedAt(): ?string {
    return $this->get('processed_at')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessedById(): ?int {
    $value = $this->get('processed_by')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes(): ?string {
    return $this->get('notes')->value;
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

    $fields['requester_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Requester'))
      ->setDescription(t('The user who submitted this request.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['subject_user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Subject User'))
      ->setDescription(t('The user whose data is subject to this request.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['request_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Request Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'erasure' => t('Erasure (Art. 17)'),
        'rectification' => t('Rectification (Art. 16)'),
        'portability' => t('Portability (Art. 20)'),
        'access' => t('Access (Art. 15)'),
      ]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pending'),
        'processing' => t('Processing'),
        'completed' => t('Completed'),
        'rejected' => t('Rejected'),
      ])
      ->setDefaultValue('pending');

    $fields['reason'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Reason'))
      ->setDescription(t('Reason or justification for this request.'));

    $fields['entities_affected'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Entities Affected (JSON)'))
      ->setDescription(t('JSON list of entity types and IDs affected by processing.'));

    $fields['processed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Processed At'))
      ->setDescription(t('Timestamp when the request was processed.'));

    $fields['processed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Processed By'))
      ->setDescription(t('The user who processed this request.'))
      ->setSetting('target_type', 'user');

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Internal notes about processing this request.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
