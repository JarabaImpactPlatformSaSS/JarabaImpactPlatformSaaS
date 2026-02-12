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
 * Define la entidad Contacto del CRM.
 *
 * Representa una persona de contacto asociada a una empresa.
 * Incluye engagement scoring para priorización de seguimiento.
 *
 * @ContentEntityType(
 *   id = "crm_contact",
 *   label = @Translation("Contacto"),
 *   label_collection = @Translation("Contactos"),
 *   label_singular = @Translation("contacto"),
 *   label_plural = @Translation("contactos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_crm\ContactListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_crm\Form\ContactForm",
 *       "add" = "Drupal\jaraba_crm\Form\ContactForm",
 *       "edit" = "Drupal\jaraba_crm\Form\ContactForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_crm\ContactAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "crm_contact",
 *   admin_permission = "administer crm entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "full_name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/contacts/{crm_contact}",
 *     "add-form" = "/admin/content/contacts/add",
 *     "edit-form" = "/admin/content/contacts/{crm_contact}/edit",
 *     "delete-form" = "/admin/content/contacts/{crm_contact}/delete",
 *     "collection" = "/admin/content/contacts",
 *   },
 *   field_ui_base_route = "entity.crm_contact.settings",
 * )
 */
class Contact extends ContentEntityBase implements EntityOwnerInterface
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
        // Auto-generar full_name.
        $this->set('full_name', trim($this->get('first_name')->value . ' ' . $this->get('last_name')->value));
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['full_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre completo'))
            ->setDescription(t('Nombre completo del contacto (auto-generado).'))
            ->setComputed(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('view', TRUE);

        $fields['first_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre del contacto.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'string',
                'weight' => -10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['last_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Apellidos'))
            ->setDescription(t('Apellidos del contacto.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'string',
                'weight' => -9,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setDescription(t('Dirección de correo electrónico.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'email_mailto',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['phone'] = BaseFieldDefinition::create('telephone')
            ->setLabel(t('Teléfono'))
            ->setDescription(t('Número de teléfono de contacto.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'telephone_link',
                'weight' => 1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'telephone_default',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['job_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Cargo'))
            ->setDescription(t('Puesto o cargo en la empresa.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'string',
                'weight' => 2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['company_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Empresa'))
            ->setDescription(t('Empresa a la que pertenece el contacto.'))
            ->setSetting('target_type', 'crm_company')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'entity_reference_label',
                'weight' => 3,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 3,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'placeholder' => '',
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['source'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Fuente'))
            ->setDescription(t('Canal de captación del contacto.'))
            ->setSetting('allowed_values_function', 'jaraba_crm_get_contact_source_values')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 4,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['engagement_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación de engagement'))
            ->setDescription(t('Score calculado basado en interacciones (0-100).'))
            ->setDefaultValue(0)
            ->setSetting('min', 0)
            ->setSetting('max', 100)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'number_integer',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('view', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Notas'))
            ->setDescription(t('Notas internas sobre el contacto.'))
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
            ->setDescription(t('Tenant propietario de este contacto.'))
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
