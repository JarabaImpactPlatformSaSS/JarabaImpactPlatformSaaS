<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Agent Saved View entity.
 *
 * Personalized filter presets for the agent dashboard.
 * Closes GAP-SUP-12.
 *
 * @ContentEntityType(
 *   id = "agent_saved_view",
 *   label = @Translation("Agent Saved View"),
 *   label_collection = @Translation("Agent Saved Views"),
 *   label_singular = @Translation("agent saved view"),
 *   label_plural = @Translation("agent saved views"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_support\Access\SupportTicketAccessControlHandler",
 *   },
 *   base_table = "agent_saved_view",
 *   admin_permission = "administer support system",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "owner_uid",
 *   },
 * )
 */
class AgentSavedView extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * Gets the filter configuration.
   *
   * @return array
   *   Decoded JSON filters.
   */
  public function getFilters(): array {
    $value = $this->get('filters')->value;
    return $value ? (json_decode($value, TRUE) ?: []) : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('View Name'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['filters'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Filters'))
      ->setDescription(t('JSON: {status: [...], priority: [...], assignee: "me"}.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sort_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sort Field'))
      ->setSettings(['max_length' => 64])
      ->setDisplayConfigurable('view', TRUE);

    $fields['sort_direction'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sort Direction'))
      ->setSetting('allowed_values', [
        'asc' => t('Ascending'),
        'desc' => t('Descending'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['scope'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Scope'))
      ->setRequired(TRUE)
      ->setDefaultValue('personal')
      ->setSetting('allowed_values', [
        'personal' => t('Personal'),
        'team' => t('Team'),
        'global' => t('Global'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_default'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default View'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
