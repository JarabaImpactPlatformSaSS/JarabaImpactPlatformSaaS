<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_market\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad DigitalTwin.
 *
 * @ContentEntityType(
 *   id = "digital_twin",
 *   label = @Translation("Gemelo Digital"),
 *   base_table = "digital_twin",
 *   admin_permission = "administer jaraba agent market",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   handlers = {
 *     "access" = "Drupal\jaraba_agent_market\Access\DigitalTwinAccessControlHandler",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   links = {
 *     "collection" = "/admin/content/digital-twins",
 *   },
 *   field_ui_base_route = "entity.digital_twin.settings",
 * )
 */
class DigitalTwin extends ContentEntityBase {

  use EntityOwnerTrait;

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Agente'))
      ->setRequired(TRUE);

    $fields['identity_wallet'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Identidad Soberana (DID)'))
      ->setSetting('target_type', 'identity_wallet')
      ->setRequired(TRUE);

    $fields['strategy'] = BaseFieldDefinition::create('string_long') // JSON
      ->setLabel(t('Estrategia de Negociación'))
      ->setDefaultValue('{"mode": "conservative", "max_rounds": 3}')
      ->setRequired(TRUE);

    $fields['budget_cap'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Límite de Gasto Autónomo'))
      ->setSetting('precision', 19)
      ->setSetting('scale', 4)
      ->setDefaultValue(0);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE);

    return $fields;
  }

}
