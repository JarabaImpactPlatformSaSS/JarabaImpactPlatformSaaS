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
 * Define la entidad ProducerProfile.
 *
 * Representa el perfil de un productor/agricultor en el marketplace AgroConecta.
 * Contiene datos de la finca, certificaciones, y preferencias de envío.
 *
 * @ContentEntityType(
 *   id = "producer_profile",
 *   label = @Translation("Perfil de Productor"),
 *   label_collection = @Translation("Productores"),
 *   label_singular = @Translation("productor"),
 *   label_plural = @Translation("productores"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\ProducerProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\ProducerProfileForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\ProducerProfileForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\ProducerProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\ProducerProfileAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "producer_profile",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.producer_profile.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "farm_name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-producers/{producer_profile}",
 *     "add-form" = "/admin/content/agro-producers/add",
 *     "edit-form" = "/admin/content/agro-producers/{producer_profile}/edit",
 *     "delete-form" = "/admin/content/agro-producers/{producer_profile}/delete",
 *     "collection" = "/admin/content/agro-producers",
 *   },
 * )
 */
class ProducerProfile extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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

        // Nombre de la finca / explotación
        $fields['farm_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre de la finca'))
            ->setDescription(t('Nombre comercial de la finca o explotación.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nombre del productor (persona)
        $fields['producer_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del productor'))
            ->setDescription(t('Nombre completo del productor.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción / Historia
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Historia y descripción de la finca y el productor.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Ubicación
        $fields['location'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Ubicación'))
            ->setDescription(t('Localidad, provincia y comunidad autónoma.'))
            ->setSetting('max_length', 500)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Coordenadas GPS (latitud)
        $fields['latitude'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Latitud'))
            ->setDescription(t('Coordenada de latitud GPS.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 7)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Coordenadas GPS (longitud)
        $fields['longitude'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Longitud'))
            ->setDescription(t('Coordenada de longitud GPS.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 7)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Superficie (hectáreas)
        $fields['area_hectares'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Superficie (ha)'))
            ->setDescription(t('Superficie de la finca en hectáreas.'))
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de producción (ecológica, convencional, integrada)
        $fields['production_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de producción'))
            ->setDescription(t('Tipo de producción: ecológica, convencional, integrada.'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Email de contacto
        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setDescription(t('Email de contacto del productor.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Teléfono
        $fields['phone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Teléfono'))
            ->setDescription(t('Teléfono de contacto del productor.'))
            ->setSetting('max_length', 20)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Stripe Connect Account ID
        $fields['stripe_account_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Account ID'))
            ->setDescription(t('ID de cuenta Stripe Connect para recibir pagos.'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Logo / Imagen del productor
        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Logo / Imagen'))
            ->setDescription(t('Logo o imagen del productor.'))
            ->setSetting('file_extensions', 'png jpg jpeg webp svg')
            ->setSetting('file_directory', 'agro/producers')
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => 3,
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
            ->setLabel(t('Activo'))
            ->setDescription(t('Si el perfil del productor está activo.'))
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
     * Verifica si el productor tiene Stripe Connect configurado.
     *
     * @return bool
     *   TRUE si tiene cuenta Stripe configurada.
     */
    public function hasStripeAccount(): bool
    {
        return !empty($this->get('stripe_account_id')->value);
    }

    /**
     * Verifica si el perfil del productor está activo.
     *
     * @return bool
     *   TRUE si está activo.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('status')->value;
    }

}
