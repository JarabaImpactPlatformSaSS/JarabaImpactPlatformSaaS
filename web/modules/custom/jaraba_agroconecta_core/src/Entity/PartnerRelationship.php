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
 * Define la entidad PartnerRelationship.
 *
 * Representa la relación comercial entre un productor y un partner
 * externo (distribuidor, exportador, HORECA, etc.) con niveles de
 * acceso diferenciados y autenticación por magic link.
 *
 * @ContentEntityType(
 *   id = "partner_relationship",
 *   label = @Translation("Relación Partner"),
 *   label_collection = @Translation("Relaciones Partner"),
 *   label_singular = @Translation("relación partner"),
 *   label_plural = @Translation("relaciones partner"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\PartnerRelationshipListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\PartnerRelationshipForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\PartnerRelationshipForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\PartnerRelationshipForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\PartnerRelationshipAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "partner_relationship",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.partner_relationship.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "partner_name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-partners/{partner_relationship}",
 *     "add-form" = "/admin/content/agro-partners/add",
 *     "edit-form" = "/admin/content/agro-partners/{partner_relationship}/edit",
 *     "delete-form" = "/admin/content/agro-partners/{partner_relationship}/delete",
 *     "collection" = "/admin/content/agro-partners",
 *   },
 * )
 */
class PartnerRelationship extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Productor'))
            ->setDescription(t('Perfil de productor que comparte documentos.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -10])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['partner_email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email del partner'))
            ->setDescription(t('Dirección de email del partner comercial.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'email_default', 'weight' => -9])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['partner_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre/empresa partner'))
            ->setDescription(t('Nombre comercial o empresa del partner.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -8])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['partner_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de partner'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'distribuidor' => t('Distribuidor'),
                'exportador' => t('Exportador'),
                'comercial' => t('Comercial'),
                'horeca' => t('HORECA'),
                'mayorista' => t('Mayorista'),
                'importador' => t('Importador'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -7])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['access_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel de acceso'))
            ->setRequired(TRUE)
            ->setDefaultValue('basico')
            ->setSetting('allowed_values', [
                'basico' => t('Básico'),
                'verificado' => t('Verificado'),
                'premium' => t('Premium'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -6])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['access_token'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Token de acceso'))
            ->setDescription(t('Token único para magic link de acceso al portal.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('view', TRUE);

        $fields['allowed_products'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Productos permitidos'))
            ->setDescription(t('JSON con IDs de productos accesibles. Null = todos.'))
            ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => -4])
            ->setDisplayConfigurable('form', TRUE);

        $fields['allowed_categories'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Categorías permitidas'))
            ->setDescription(t('JSON con IDs de categorías accesibles. Null = todas.'))
            ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => -3])
            ->setDisplayConfigurable('form', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setDefaultValue('pending')
            ->setSetting('allowed_values', [
                'pending' => t('Pendiente'),
                'active' => t('Activo'),
                'suspended' => t('Suspendido'),
                'revoked' => t('Revocado'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -2])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Notas internas'))
            ->setDescription(t('Notas privadas del productor sobre este partner.'))
            ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 0])
            ->setDisplayConfigurable('form', TRUE);

        $fields['last_access_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Último acceso'))
            ->setDescription(t('Fecha y hora del último acceso al portal.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene el email del partner.
     */
    public function getPartnerEmail(): string
    {
        return $this->get('partner_email')->value ?? '';
    }

    /**
     * Obtiene el nombre del partner.
     */
    public function getPartnerName(): string
    {
        return $this->get('partner_name')->value ?? '';
    }

    /**
     * Obtiene el tipo de partner.
     */
    public function getPartnerType(): string
    {
        return $this->get('partner_type')->value ?? '';
    }

    /**
     * Obtiene el nivel de acceso.
     */
    public function getAccessLevel(): string
    {
        return $this->get('access_level')->value ?? 'basico';
    }

    /**
     * Obtiene el token de acceso.
     */
    public function getAccessToken(): string
    {
        return $this->get('access_token')->value ?? '';
    }

    /**
     * Obtiene el estado de la relación.
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? 'pending';
    }

    /**
     * Obtiene el ID del productor.
     */
    public function getProducerId(): ?int
    {
        return $this->get('producer_id')->target_id ? (int) $this->get('producer_id')->target_id : NULL;
    }

    /**
     * Obtiene los productos permitidos como array.
     */
    public function getAllowedProducts(): ?array
    {
        $value = $this->get('allowed_products')->value;
        return $value ? json_decode($value, TRUE) : NULL;
    }

    /**
     * Obtiene las categorías permitidas como array.
     */
    public function getAllowedCategories(): ?array
    {
        $value = $this->get('allowed_categories')->value;
        return $value ? json_decode($value, TRUE) : NULL;
    }

}
