<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad IssuerProfile.
 *
 * Representa una organización emisora de credenciales con claves Ed25519.
 *
 * @ContentEntityType(
 *   id = "issuer_profile",
 *   label = @Translation("Perfil de Emisor"),
 *   label_collection = @Translation("Perfiles de Emisor"),
 *   label_singular = @Translation("perfil de emisor"),
 *   label_plural = @Translation("perfiles de emisor"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_credentials\IssuerProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_credentials\Form\IssuerProfileForm",
 *       "add" = "Drupal\jaraba_credentials\Form\IssuerProfileForm",
 *       "edit" = "Drupal\jaraba_credentials\Form\IssuerProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_credentials\IssuerProfileAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "issuer_profile",
 *   admin_permission = "manage issuer profiles",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.issuer_profile.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/issuer-profiles/{issuer_profile}",
 *     "add-form" = "/admin/content/issuer-profiles/add",
 *     "edit-form" = "/admin/content/issuer-profiles/{issuer_profile}/edit",
 *     "delete-form" = "/admin/content/issuer-profiles/{issuer_profile}/delete",
 *     "collection" = "/admin/content/issuer-profiles",
 *   },
 * )
 */
class IssuerProfile extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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

        // Nombre de la organización emisora
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre de la organización emisora.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // URL de la organización
        $fields['url'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('URL'))
            ->setDescription(t('URL del sitio web del emisor.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'uri',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Email de contacto
        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setDescription(t('Email de contacto del emisor.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción de la organización emisora.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Imagen/Logo del emisor
        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Logo'))
            ->setDescription(t('Logo de la organización emisora.'))
            ->setSetting('file_extensions', 'png svg jpg jpeg')
            ->setSetting('file_directory', 'credentials/issuers')
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Clave pública Ed25519 (Base64)
        $fields['public_key'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Clave Pública Ed25519'))
            ->setDescription(t('Clave pública en formato Base64 para verificación de firmas.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -5,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Clave privada Ed25519 (encriptada)
        // NOTA: Este campo se almacena encriptado y no se expone en la UI
        $fields['private_key_encrypted'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Clave Privada Encriptada'))
            ->setDescription(t('Clave privada encriptada (no editar manualmente).'))
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', FALSE);

        // URL del JSON del issuer (para OB3)
        $fields['issuer_json_url'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('URL JSON del Emisor'))
            ->setDescription(t('URL pública donde se puede verificar el perfil del emisor.'))
            ->setDisplayOptions('form', [
                'type' => 'uri',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Es el emisor por defecto
        $fields['is_default'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Emisor por defecto'))
            ->setDescription(t('Si está marcado, este emisor se usará como predeterminado.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -3,
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
     * Obtiene la clave pública como bytes.
     *
     * @return string|null
     *   La clave pública decodificada o NULL.
     */
    public function getPublicKeyBytes(): ?string
    {
        $encoded = $this->get('public_key')->value ?? '';
        if (empty($encoded)) {
            return NULL;
        }
        return base64_decode($encoded, TRUE) ?: NULL;
    }

    /**
     * Indica si este emisor tiene claves configuradas.
     *
     * @return bool
     *   TRUE si tiene claves, FALSE en caso contrario.
     */
    public function hasKeys(): bool
    {
        return !empty($this->get('public_key')->value);
    }

}
