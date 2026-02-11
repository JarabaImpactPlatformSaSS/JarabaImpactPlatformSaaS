<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Availability Slot.
 *
 * Representa un slot de disponibilidad del mentor para sesiones.
 *
 * SPEC: 31_Emprendimiento_Mentoring_Core_v1
 *
 * @ContentEntityType(
 *   id = "availability_slot",
 *   label = @Translation("Slot de Disponibilidad"),
 *   label_collection = @Translation("Slots de Disponibilidad"),
 *   label_singular = @Translation("slot de disponibilidad"),
 *   label_plural = @Translation("slots de disponibilidad"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "availability_slot",
 *   admin_permission = "administer mentor profiles",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/availability-slots",
 *     "canonical" = "/admin/content/availability-slot/{availability_slot}",
 *     "delete-form" = "/admin/content/availability-slot/{availability_slot}/delete",
 *   },
 * )
 */
class AvailabilitySlot extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['mentor_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Mentor'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentor_profile')
            ->setDisplayConfigurable('form', TRUE);

        $fields['day_of_week'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Día de la Semana'))
            ->setDescription(t('0=Domingo, 1=Lunes, ..., 6=Sábado'))
            ->setSetting('min', 0)
            ->setSetting('max', 6)
            ->setDisplayConfigurable('form', TRUE);

        $fields['start_time'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hora de Inicio'))
            ->setDescription(t('Formato HH:MM (ej: 09:00)'))
            ->setSetting('max_length', 5)
            ->setDisplayConfigurable('form', TRUE);

        $fields['end_time'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hora de Fin'))
            ->setDescription(t('Formato HH:MM (ej: 18:00)'))
            ->setSetting('max_length', 5)
            ->setDisplayConfigurable('form', TRUE);

        $fields['timezone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Zona Horaria'))
            ->setDefaultValue('Europe/Madrid')
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_recurring'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Es Recurrente'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['specific_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Específica'))
            ->setDescription(t('Si no es recurrente, fecha específica.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_available'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Disponible'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        return $fields;
    }

}
