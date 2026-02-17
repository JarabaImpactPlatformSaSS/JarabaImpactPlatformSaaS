<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * @ContentEntityType(
 *   id = "return_request",
 *   label = @Translation("Solicitud de Devolucion"),
 *   label_collection = @Translation("Solicitudes de Devolucion"),
 *   label_singular = @Translation("solicitud de devolucion"),
 *   label_plural = @Translation("solicitudes de devolucion"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\ReturnRequestListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\ReturnRequestForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\ReturnRequestForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\ReturnRequestForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\ReturnRequestAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "return_request",
 *   admin_permission = "manage comercio returns",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-return/{return_request}",
 *     "add-form" = "/admin/content/comercio-return/add",
 *     "edit-form" = "/admin/content/comercio-return/{return_request}/edit",
 *     "delete-form" = "/admin/content/comercio-return/{return_request}/delete",
 *     "collection" = "/admin/content/comercio-returns",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.return_request.settings",
 * )
 */
class ReturnRequest extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Pedido'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'order_retail')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['suborder_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sub-pedido'))
      ->setSetting('target_type', 'suborder_retail')
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE);

    $fields['reason'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Motivo'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'defective' => t('Producto defectuoso'),
        'wrong_item' => t('Producto incorrecto'),
        'not_as_described' => t('No coincide con la descripcion'),
        'changed_mind' => t('Cambio de opinion'),
        'other' => t('Otro'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripcion'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'requested' => t('Solicitada'),
        'approved' => t('Aprobada'),
        'rejected' => t('Rechazada'),
        'returned' => t('Devuelto'),
        'refunded' => t('Reembolsado'),
      ])
      ->setDefaultValue('requested')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['refund_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe de reembolso'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
