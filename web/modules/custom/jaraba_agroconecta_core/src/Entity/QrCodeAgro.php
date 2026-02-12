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
 * Define la entidad QrCodeAgro.
 *
 * QR dinámico con tracking y analytics. Cada QR apunta a una landing
 * pública de trazabilidad, producto o productor, y registra todos
 * los escaneos con geolocalización y analytics.
 *
 * @ContentEntityType(
 *   id = "qr_code_agro",
 *   label = @Translation("Código QR Agro"),
 *   label_collection = @Translation("Códigos QR Agro"),
 *   label_singular = @Translation("código QR agro"),
 *   label_plural = @Translation("códigos QR agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\QrCodeAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\QrCodeAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\QrCodeAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\QrCodeAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\QrCodeAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "qr_code_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.qr_code_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-qr-codes/{qr_code_agro}",
 *     "add-form" = "/admin/content/agro-qr-codes/add",
 *     "edit-form" = "/admin/content/agro-qr-codes/{qr_code_agro}/edit",
 *     "delete-form" = "/admin/content/agro-qr-codes/{qr_code_agro}/delete",
 *     "collection" = "/admin/content/agro-qr-codes",
 *   },
 * )
 */
class QrCodeAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

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

        $fields['label'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Etiqueta'))
            ->setDescription(t('Nombre descriptivo del QR.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -10])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['qr_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de QR'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'batch' => t('Lote/Trazabilidad'),
                'product' => t('Producto'),
                'producer' => t('Productor'),
                'promo' => t('Promoción'),
                'custom' => t('URL personalizada'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -9])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['target_entity_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo entidad destino'))
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('form', TRUE);

        $fields['target_entity_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID entidad destino'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['short_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Código corto'))
            ->setDescription(t('Código único para la URL corta (ej: abc123XY).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 16)
            ->setDisplayConfigurable('view', TRUE);

        $fields['destination_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL de destino'))
            ->setDescription(t('URL completa a la que redirige el QR.'))
            ->setSetting('max_length', 512)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -7])
            ->setDisplayConfigurable('form', TRUE);

        $fields['qr_image_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL imagen QR'))
            ->setDescription(t('Ruta al archivo PNG/SVG generado.'))
            ->setSetting('max_length', 512)
            ->setDisplayConfigurable('view', TRUE);

        $fields['scan_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total escaneos'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['unique_scan_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Escaneos únicos'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['conversion_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Conversiones'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['utm_source'] = BaseFieldDefinition::create('string')
            ->setLabel(t('UTM Source'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -4])
            ->setDisplayConfigurable('form', TRUE);

        $fields['utm_medium'] = BaseFieldDefinition::create('string')
            ->setLabel(t('UTM Medium'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -3])
            ->setDisplayConfigurable('form', TRUE);

        $fields['utm_campaign'] = BaseFieldDefinition::create('string')
            ->setLabel(t('UTM Campaign'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -2])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 0])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getShortCode(): string
    {
        return $this->get('short_code')->value ?? '';
    }
    public function getQrType(): string
    {
        return $this->get('qr_type')->value ?? '';
    }
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }
    public function getScanCount(): int
    {
        return (int) ($this->get('scan_count')->value ?? 0);
    }
}
