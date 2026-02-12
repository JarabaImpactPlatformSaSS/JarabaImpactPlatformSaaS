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
 * Define la entidad ProductAgro.
 *
 * Representa un producto agroalimentario en el marketplace AgroConecta.
 * Incluye información de catálogo, precios, origen y certificaciones.
 *
 * @ContentEntityType(
 *   id = "product_agro",
 *   label = @Translation("Producto Agro"),
 *   label_collection = @Translation("Productos Agro"),
 *   label_singular = @Translation("producto agro"),
 *   label_plural = @Translation("productos agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\ProductAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\ProductAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\ProductAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\ProductAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\ProductAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "product_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.product_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-products/{product_agro}",
 *     "add-form" = "/admin/content/agro-products/add",
 *     "edit-form" = "/admin/content/agro-products/{product_agro}/edit",
 *     "delete-form" = "/admin/content/agro-products/{product_agro}/delete",
 *     "collection" = "/admin/content/agro-products",
 *   },
 * )
 */
class ProductAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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

        // Nombre del producto
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre del producto agroalimentario.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // SKU / Referencia
        $fields['sku'] = BaseFieldDefinition::create('string')
            ->setLabel(t('SKU'))
            ->setDescription(t('Referencia única del producto.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción corta
        $fields['description_short'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Descripción corta'))
            ->setDescription(t('Resumen del producto para listados.'))
            ->setSetting('max_length', 500)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción larga
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada del producto.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Precio
        $fields['price'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio'))
            ->setDescription(t('Precio del producto en euros.'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Moneda (por defecto EUR)
        $fields['currency_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Moneda'))
            ->setDescription(t('Código de moneda ISO 4217.'))
            ->setDefaultValue('EUR')
            ->setSetting('max_length', 3)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Stock
        $fields['stock'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Stock'))
            ->setDescription(t('Unidades disponibles.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Unidad de medida (kg, unidades, litros, etc.)
        $fields['unit'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Unidad de medida'))
            ->setDescription(t('Unidad de medida del producto (kg, uds, L, etc.).'))
            ->setDefaultValue('kg')
            ->setSetting('max_length', 20)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Categoría
        $fields['category'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Categoría del producto (frutas, verduras, lácteos, etc.).'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Origen / Denominación de origen
        $fields['origin'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Origen'))
            ->setDescription(t('Denominación de origen o zona geográfica.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al productor
        $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Productor'))
            ->setDescription(t('Perfil del productor propietario del producto.'))
            ->setSetting('target_type', 'producer_profile')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
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

        // Estado del producto
        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publicado'))
            ->setDescription(t('Si el producto está visible en el marketplace.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Imagen principal
        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Imagen'))
            ->setDescription(t('Imagen principal del producto.'))
            ->setSetting('file_extensions', 'png jpg jpeg webp')
            ->setSetting('file_directory', 'agro/products')
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => 5,
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
     * Obtiene el precio formateado.
     *
     * @return string
     *   El precio con formato (ej: "12,50 €").
     */
    public function getFormattedPrice(): string
    {
        $price = $this->get('price')->value ?? 0;
        $currency = $this->get('currency_code')->value ?? 'EUR';
        return number_format((float) $price, 2, ',', '.') . ' ' . ($currency === 'EUR' ? '€' : $currency);
    }

    /**
     * Verifica si el producto tiene stock disponible.
     *
     * @return bool
     *   TRUE si hay stock, FALSE en caso contrario.
     */
    public function hasStock(): bool
    {
        return ((int) $this->get('stock')->value) > 0;
    }

    /**
     * Verifica si el producto está publicado.
     *
     * @return bool
     *   TRUE si está publicado.
     */
    public function isPublished(): bool
    {
        return (bool) $this->get('status')->value;
    }

}
