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
 *   id = "comercio_incident_ticket",
 *   label = @Translation("Ticket de Incidencia"),
 *   label_collection = @Translation("Tickets de Incidencia"),
 *   label_singular = @Translation("ticket de incidencia"),
 *   label_plural = @Translation("tickets de incidencia"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\IncidentTicketListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\IncidentTicketForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\IncidentTicketForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\IncidentTicketForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\IncidentTicketAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_incident_ticket",
 *   admin_permission = "manage comercio incidents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-incident-ticket/{comercio_incident_ticket}",
 *     "add-form" = "/admin/content/comercio-incident-ticket/add",
 *     "edit-form" = "/admin/content/comercio-incident-ticket/{comercio_incident_ticket}/edit",
 *     "delete-form" = "/admin/content/comercio-incident-ticket/{comercio_incident_ticket}/delete",
 *     "collection" = "/admin/content/comercio-incident-tickets",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.comercio_incident_ticket.settings",
 * )
 */
class IncidentTicket extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Asunto'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripcion'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Pedido'))
      ->setSetting('target_type', 'order_retail')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setSetting('target_type', 'merchant_profile')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Categoria'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'order_issue' => t('Problema con pedido'),
        'payment_issue' => t('Problema de pago'),
        'product_quality' => t('Calidad del producto'),
        'delivery_issue' => t('Problema de envio'),
        'other' => t('Otro'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'open' => t('Abierto'),
        'in_progress' => t('En progreso'),
        'waiting_response' => t('Esperando respuesta'),
        'resolved' => t('Resuelto'),
        'closed' => t('Cerrado'),
      ])
      ->setDefaultValue('open')
      ->setDisplayOptions('form', ['weight' => 6])
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
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Asignado a'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['resolution_notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notas de resolucion'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
