<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Actividad del CRM.
 *
 * Registra interacciones con contactos: llamadas, emails, reuniones, etc.
 * Los tipos de actividad se configuran desde YAML (Directriz #20).
 *
 * @ContentEntityType(
 *   id = "crm_activity",
 *   label = @Translation("Actividad"),
 *   label_collection = @Translation("Actividades"),
 *   label_singular = @Translation("actividad"),
 *   label_plural = @Translation("actividades"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_crm\ActivityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_crm\Form\ActivityForm",
 *       "add" = "Drupal\jaraba_crm\Form\ActivityForm",
 *       "edit" = "Drupal\jaraba_crm\Form\ActivityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_crm\ActivityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "crm_activity",
 *   admin_permission = "administer crm entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/activities/{crm_activity}",
 *     "add-form" = "/admin/content/activities/add",
 *     "edit-form" = "/admin/content/activities/{crm_activity}/edit",
 *     "delete-form" = "/admin/content/activities/{crm_activity}/delete",
 *     "collection" = "/admin/content/activities",
 *   },
 *   field_ui_base_route = "entity.crm_activity.settings",
 * )
 */
class Activity extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function preSave(EntityStorageInterface $storage): void
    {
        parent::preSave($storage);
        if (!$this->getOwnerId()) {
            $this->setOwnerId(\Drupal::currentUser()->id());
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['subject'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Asunto'))
            ->setDescription(t('Descripción breve de la actividad.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo'))
            ->setDescription(t('Tipo de actividad realizada.'))
            ->setRequired(TRUE)
            ->setDefaultValue('note')
            ->setSetting('allowed_values_function', 'jaraba_crm_get_activity_type_values')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['contact_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Contacto'))
            ->setDescription(t('Contacto relacionado con esta actividad.'))
            ->setSetting('target_type', 'crm_contact')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'entity_reference_label',
                'weight' => 1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['opportunity_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Oportunidad'))
            ->setDescription(t('Oportunidad relacionada (opcional).'))
            ->setSetting('target_type', 'crm_opportunity')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'entity_reference_label',
                'weight' => 2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['activity_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de actividad'))
            ->setDescription(t('Fecha y hora de la actividad.'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'datetime_default',
                'weight' => 3,
            ])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['duration'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duración'))
            ->setDescription(t('Duración en minutos (para llamadas/reuniones).'))
            ->setSetting('min', 0)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'number_integer',
                'weight' => 4,
                'settings' => [
                    'suffix' => ' min',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Notas'))
            ->setDescription(t('Detalles de la actividad.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_default',
                'weight' => 10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 10,
                'settings' => [
                    'rows' => 5,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Última modificación'));

        return $fields;
    }

}
