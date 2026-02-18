<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_moderation_queue",
 *   label = @Translation("Cola de Moderacion"),
 *   label_collection = @Translation("Cola de Moderacion"),
 *   label_singular = @Translation("elemento de moderacion"),
 *   label_plural = @Translation("elementos de moderacion"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\ModerationQueueListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\ModerationQueueAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_moderation_queue",
 *   admin_permission = "manage comercio moderation",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-moderation-queue/{comercio_moderation_queue}",
 *     "collection" = "/admin/content/comercio-moderation-queue",
 *   },
 * )
 */
class ModerationQueue extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de entidad'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_id_ref'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID de entidad'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['moderation_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de moderacion'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'new_product' => t('Producto nuevo'),
        'edited_product' => t('Producto editado'),
        'new_review' => t('Resena nueva'),
        'flagged_review' => t('Resena reportada'),
        'new_merchant' => t('Comercio nuevo'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'approved' => t('Aprobado'),
        'rejected' => t('Rechazado'),
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Prioridad'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'low' => t('Baja'),
        'normal' => t('Normal'),
        'high' => t('Alta'),
        'urgent' => t('Urgente'),
      ])
      ->setDefaultValue('normal')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Asignado a'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notas'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
