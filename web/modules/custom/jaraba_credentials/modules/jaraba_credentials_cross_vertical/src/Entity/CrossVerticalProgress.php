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
 * Define la entidad CrossVerticalProgress.
 *
 * @ContentEntityType(
 *   id = "cross_vertical_progress",
 *   label = @Translation("Progreso Cross-Vertical"),
 *   label_collection = @Translation("Progresos Cross-Vertical"),
 *   label_singular = @Translation("progreso cross-vertical"),
 *   label_plural = @Translation("progresos cross-vertical"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_credentials_cross_vertical\CrossVerticalProgressListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_credentials_cross_vertical\Form\CrossVerticalProgressForm",
 *       "add" = "Drupal\jaraba_credentials_cross_vertical\Form\CrossVerticalProgressForm",
 *       "edit" = "Drupal\jaraba_credentials_cross_vertical\Form\CrossVerticalProgressForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_credentials_cross_vertical\CrossVerticalProgressAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cross_vertical_progress",
 *   admin_permission = "administer credentials",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.cross_vertical_progress.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/cross-vertical-progress/{cross_vertical_progress}",
 *     "collection" = "/admin/content/cross-vertical-progress",
 *   },
 * )
 */
class CrossVerticalProgress extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  public const STATUS_TRACKING = 'tracking';
  public const STATUS_COMPLETED = 'completed';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['rule_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Regla'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'cross_vertical_rule')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical_progress'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Progreso por Vertical'))
      ->setDescription(t('JSON con progreso por cada vertical.'))
      ->setDefaultValue('{}')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['overall_percent'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Progreso Total (%)'))
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_TRACKING => t('En seguimiento'),
        self::STATUS_COMPLETED => t('Completado'),
      ])
      ->setDefaultValue(self::STATUS_TRACKING)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Completado'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['result_credential_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Credencial Resultante'))
      ->setSetting('target_type', 'issued_credential')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene el progreso por vertical.
   */
  public function getVerticalProgress(): array {
    $json = $this->get('vertical_progress')->value ?? '{}';
    return json_decode($json, TRUE) ?: [];
  }

}
