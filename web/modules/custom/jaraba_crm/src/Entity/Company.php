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
 * Define la entidad Empresa (Company) del CRM.
 *
 * Esta entidad representa una organización/empresa en el sistema CRM.
 * Incluye datos de contacto, clasificación por industria y tamaño,
 * y está asociada a un tenant específico para aislamiento multi-tenant.
 *
 * @ContentEntityType(
 *   id = "crm_company",
 *   label = @Translation("Empresa"),
 *   label_collection = @Translation("Empresas"),
 *   label_singular = @Translation("empresa"),
 *   label_plural = @Translation("empresas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_crm\CompanyListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_crm\Form\CompanyForm",
 *       "add" = "Drupal\jaraba_crm\Form\CompanyForm",
 *       "edit" = "Drupal\jaraba_crm\Form\CompanyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_crm\CompanyAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "crm_company",
 *   admin_permission = "administer crm entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/companies/{crm_company}",
 *     "add-form" = "/admin/content/companies/add",
 *     "edit-form" = "/admin/content/companies/{crm_company}/edit",
 *     "delete-form" = "/admin/content/companies/{crm_company}/delete",
 *     "collection" = "/admin/content/companies",
 *   },
 *   field_ui_base_route = "entity.crm_company.settings",
 * )
 */
class Company extends ContentEntityBase implements EntityOwnerInterface
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

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre de la empresa'))
            ->setDescription(t('Razón social o nombre comercial de la empresa.'))
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

        $fields['industry'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Industria'))
            ->setDescription(t('Sector de actividad de la empresa.'))
            ->setSetting('allowed_values_function', 'jaraba_crm_get_industry_values')
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

        $fields['size'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tamaño'))
            ->setDescription(t('Clasificación por número de empleados.'))
            ->setSetting('allowed_values_function', 'jaraba_crm_get_size_values')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['website'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('Sitio web'))
            ->setDescription(t('URL del sitio web corporativo.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'uri_link',
                'weight' => 2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'uri',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['phone'] = BaseFieldDefinition::create('telephone')
            ->setLabel(t('Teléfono'))
            ->setDescription(t('Número de teléfono principal.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'telephone_link',
                'weight' => 3,
            ])
            ->setDisplayOptions('form', [
                'type' => 'telephone_default',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email corporativo'))
            ->setDescription(t('Dirección de email principal de contacto.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'email_mailto',
                'weight' => 4,
            ])
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['address'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Dirección'))
            ->setDescription(t('Dirección física de la empresa.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'basic_string',
                'weight' => 5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 5,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Notas'))
            ->setDescription(t('Notas internas sobre la empresa.'))
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

        // Multi-tenant field.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant propietario de esta empresa.'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creación'))
            ->setDescription(t('Fecha y hora de creación del registro.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Última modificación'))
            ->setDescription(t('Fecha y hora de última modificación.'));

        return $fields;
    }

}
