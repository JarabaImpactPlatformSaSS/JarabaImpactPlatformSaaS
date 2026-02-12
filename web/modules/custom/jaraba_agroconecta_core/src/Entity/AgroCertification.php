<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad AgroCertification.
 *
 * Representa una certificación de producto agroalimentario
 * (ecológica, DOP, IGP, Global GAP, etc.).
 *
 * @ContentEntityType(
 *   id = "agro_certification",
 *   label = @Translation("Certificación Agro"),
 *   label_collection = @Translation("Certificaciones Agro"),
 *   label_singular = @Translation("certificación agro"),
 *   label_plural = @Translation("certificaciones agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\AgroCertificationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\AgroCertificationForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\AgroCertificationForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\AgroCertificationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\AgroCertificationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agro_certification",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.agro_certification.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-certifications/{agro_certification}",
 *     "add-form" = "/admin/content/agro-certifications/add",
 *     "edit-form" = "/admin/content/agro-certifications/{agro_certification}/edit",
 *     "delete-form" = "/admin/content/agro-certifications/{agro_certification}/delete",
 *     "collection" = "/admin/content/agro-certifications",
 *   },
 * )
 */
class AgroCertification extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Nombre de la certificación
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre de la certificación (ej: Producción Ecológica, DOP, IGP).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de certificación
        $fields['certification_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo'))
            ->setDescription(t('Tipo de certificación: ecologica, dop, igp, global_gap, iso, otro.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Organismo certificador
        $fields['certifier'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Organismo certificador'))
            ->setDescription(t('Entidad que emite la certificación.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Número de certificado
        $fields['certificate_number'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nº Certificado'))
            ->setDescription(t('Número de identificación del certificado.'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de emisión
        $fields['issue_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de emisión'))
            ->setDescription(t('Fecha en que se emitió la certificación.'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de expiración
        $fields['expiry_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de expiración'))
            ->setDescription(t('Fecha en que expira la certificación.'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción y alcance de la certificación.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al productor
        $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Productor'))
            ->setDescription(t('Productor asociado a la certificación.'))
            ->setSetting('target_type', 'producer_profile')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Documento del certificado
        $fields['document'] = BaseFieldDefinition::create('file')
            ->setLabel(t('Documento'))
            ->setDescription(t('Archivo del certificado (PDF, imagen).'))
            ->setSetting('file_extensions', 'pdf png jpg jpeg')
            ->setSetting('file_directory', 'agro/certifications')
            ->setDisplayOptions('form', [
                'type' => 'file_generic',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tenant ID para multi-tenancy
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Organización propietaria.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Estado activo
        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Si la certificación está vigente.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Verifica si la certificación ha expirado.
     *
     * @return bool
     *   TRUE si está expirada.
     */
    public function isExpired(): bool
    {
        $expiry = $this->get('expiry_date')->value;
        if (empty($expiry)) {
            return FALSE;
        }
        return strtotime($expiry) < time();
    }

    /**
     * Verifica si la certificación está vigente.
     *
     * @return bool
     *   TRUE si está activa y no expirada.
     */
    public function isValid(): bool
    {
        return (bool) $this->get('status')->value && !$this->isExpired();
    }

}
