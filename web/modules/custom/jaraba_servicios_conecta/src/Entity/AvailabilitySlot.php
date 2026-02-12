<?php

namespace Drupal\jaraba_servicios_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Slot de Disponibilidad.
 *
 * Estructura: Representa un bloque de disponibilidad recurrente
 *   de un profesional (ej: lunes de 9:00 a 14:00).
 *
 * Lógica: Cada profesional define sus slots semanales recurrentes.
 *   El motor de reservas consulta estos slots para calcular las
 *   horas disponibles, descontando las reservas existentes y
 *   las excepciones (vacaciones, imprevistos).
 *
 * @ContentEntityType(
 *   id = "availability_slot",
 *   label = @Translation("Slot de Disponibilidad"),
 *   label_collection = @Translation("Slots de Disponibilidad"),
 *   label_singular = @Translation("slot de disponibilidad"),
 *   label_plural = @Translation("slots de disponibilidad"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_servicios_conecta\ListBuilder\AvailabilitySlotListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_servicios_conecta\Form\AvailabilitySlotForm",
 *       "add" = "Drupal\jaraba_servicios_conecta\Form\AvailabilitySlotForm",
 *       "edit" = "Drupal\jaraba_servicios_conecta\Form\AvailabilitySlotForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_servicios_conecta\Access\AvailabilitySlotAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "availability_slot",
 *   admin_permission = "manage servicios providers",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/servicios-availability/{availability_slot}",
 *     "add-form" = "/admin/content/servicios-availability/add",
 *     "edit-form" = "/admin/content/servicios-availability/{availability_slot}/edit",
 *     "delete-form" = "/admin/content/servicios-availability/{availability_slot}/delete",
 *     "collection" = "/admin/content/servicios-availability",
 *   },
 *   field_ui_base_route = "jaraba_servicios_conecta.availability_slot.settings",
 * )
 */
class AvailabilitySlot extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Profesional'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'provider_profile')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['day_of_week'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Día de la Semana'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        1 => t('Lunes'),
        2 => t('Martes'),
        3 => t('Miércoles'),
        4 => t('Jueves'),
        5 => t('Viernes'),
        6 => t('Sábado'),
        7 => t('Domingo'),
      ])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_time'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hora de Inicio'))
      ->setDescription(t('Formato HH:MM (24h). Ej: 09:00'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 5)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_time'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hora de Fin'))
      ->setDescription(t('Formato HH:MM (24h). Ej: 14:00'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 5)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE);

    $fields['valid_from'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Válido Desde'))
      ->setDescription(t('Fecha desde la que aplica este slot (opcional).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE);

    $fields['valid_until'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Válido Hasta'))
      ->setDescription(t('Fecha hasta la que aplica este slot (opcional).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
