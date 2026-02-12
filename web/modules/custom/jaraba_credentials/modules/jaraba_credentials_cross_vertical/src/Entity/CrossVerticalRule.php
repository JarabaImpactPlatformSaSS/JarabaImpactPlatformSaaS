<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad CrossVerticalRule.
 *
 * Regla que define credenciales cross-vertical basadas en logros
 * en múltiples verticales de la plataforma.
 *
 * @ContentEntityType(
 *   id = "cross_vertical_rule",
 *   label = @Translation("Regla Cross-Vertical"),
 *   label_collection = @Translation("Reglas Cross-Vertical"),
 *   label_singular = @Translation("regla cross-vertical"),
 *   label_plural = @Translation("reglas cross-vertical"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_credentials_cross_vertical\CrossVerticalRuleListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_credentials_cross_vertical\Form\CrossVerticalRuleForm",
 *       "add" = "Drupal\jaraba_credentials_cross_vertical\Form\CrossVerticalRuleForm",
 *       "edit" = "Drupal\jaraba_credentials_cross_vertical\Form\CrossVerticalRuleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_credentials_cross_vertical\CrossVerticalRuleAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cross_vertical_rule",
 *   admin_permission = "administer credentials",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.cross_vertical_rule.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/cross-vertical-rules/{cross_vertical_rule}",
 *     "add-form" = "/admin/content/cross-vertical-rules/add",
 *     "edit-form" = "/admin/content/cross-vertical-rules/{cross_vertical_rule}/edit",
 *     "delete-form" = "/admin/content/cross-vertical-rules/{cross_vertical_rule}/delete",
 *     "collection" = "/admin/content/cross-vertical-rules",
 *   },
 * )
 */
class CrossVerticalRule extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  public const RARITY_COMMON = 'common';
  public const RARITY_RARE = 'rare';
  public const RARITY_EPIC = 'epic';
  public const RARITY_LEGENDARY = 'legendary';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre Máquina'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['result_template_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Template Resultante'))
      ->setSetting('target_type', 'credential_template')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['verticals_required'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Verticales Requeridas'))
      ->setDescription(t('JSON array de verticales requeridas, ej: ["empleabilidad","emprendimiento"]'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -6,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conditions'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Condiciones'))
      ->setDescription(t('JSON con reglas por vertical: credentials_count, milestones_achieved, etc.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -5,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bonus_credits'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Créditos Bonus'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bonus_xp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('XP Bonus'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rarity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Rareza'))
      ->setSetting('allowed_values', [
        self::RARITY_COMMON => t('Común'),
        self::RARITY_RARE => t('Raro'),
        self::RARITY_EPIC => t('Épico'),
        self::RARITY_LEGENDARY => t('Legendario'),
      ])
      ->setDefaultValue(self::RARITY_COMMON)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene las verticales requeridas.
   */
  public function getVerticalsRequired(): array {
    $json = $this->get('verticals_required')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

  /**
   * Obtiene las condiciones por vertical.
   */
  public function getConditions(): array {
    $json = $this->get('conditions')->value ?? '{}';
    return json_decode($json, TRUE) ?: [];
  }

  /**
   * Obtiene la rareza.
   */
  public function getRarity(): string {
    return $this->get('rarity')->value ?? self::RARITY_COMMON;
  }

}
