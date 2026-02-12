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
 * Define la entidad AgroCollection.
 *
 * Representa una colección curada o automática de productos en el marketplace
 * AgroConecta. Soporta dos tipos: manual (IDs explícitos en JSON) y smart
 * (reglas en JSON para selección dinámica). Se usa para landing pages,
 * la home, campañas de marketing y secciones editoriales.
 *
 * @ContentEntityType(
 *   id = "agro_collection",
 *   label = @Translation("Colección Agro"),
 *   label_collection = @Translation("Colecciones Agro"),
 *   label_singular = @Translation("colección agro"),
 *   label_plural = @Translation("colecciones agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\AgroCollectionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\AgroCollectionForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\AgroCollectionForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\AgroCollectionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AgroCollectionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agro_collection",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.agro_collection.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-collections/{agro_collection}",
 *     "add-form" = "/admin/content/agro-collections/add",
 *     "edit-form" = "/admin/content/agro-collections/{agro_collection}/edit",
 *     "delete-form" = "/admin/content/agro-collections/{agro_collection}/delete",
 *     "collection" = "/admin/content/agro-collections",
 *   },
 * )
 */
class AgroCollection extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Tipos de colección.
     */
    const TYPE_MANUAL = 'manual';
    const TYPE_SMART = 'smart';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Tenant ID para multi-tenancy.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Marketplace al que pertenece esta colección.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Nombre de la colección.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre visible de la colección (ej: Aceites Premium, Ofertas de Temporada).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Slug para URL.
        $fields['slug'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Slug'))
            ->setDescription(t('Identificador URL-friendly.'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción.
        $fields['description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción de la colección para la página de detalle.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -8,
                'settings' => [
                    'rows' => 4,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Imagen de portada.
        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Imagen de portada'))
            ->setDescription(t('Imagen representativa de la colección.'))
            ->setSetting('file_extensions', 'png jpg jpeg webp')
            ->setSetting('alt_field', TRUE)
            ->setSetting('file_directory', 'agro/collections')
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo: manual o smart.
        $fields['type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo'))
            ->setDescription(t('Manual: productos seleccionados a mano. Smart: selección automática por reglas.'))
            ->setRequired(TRUE)
            ->setDefaultValue(self::TYPE_MANUAL)
            ->setSetting('max_length', 16)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // IDs de productos (para tipo manual).
        $fields['product_ids'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('IDs de productos'))
            ->setDescription(t('JSON array con IDs de productos (solo para tipo manual). Ej: [1, 5, 12, 23].'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -5,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Reglas smart (para tipo smart).
        $fields['rules'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Reglas smart'))
            ->setDescription(t('JSON con reglas de selección automática (solo para tipo smart). Ej: {"category_id": 5, "min_rating": 4, "sort": "best_selling"}.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -4,
                'settings' => [
                    'rows' => 5,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Posición para ordenación.
        $fields['position'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Posición'))
            ->setDescription(t('Orden de aparición en listados. Menor número = primero.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ¿Colección destacada?
        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacada'))
            ->setDescription(t('Mostrar esta colección en la página principal.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ¿Colección activa?
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Solo las colecciones activas son visibles en el marketplace.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene el slug de la colección.
     *
     * @return string
     *   Slug URL-friendly.
     */
    public function getSlug(): string
    {
        return $this->get('slug')->value ?? '';
    }

    /**
     * Indica si es una colección manual.
     *
     * @return bool
     *   TRUE si los productos se seleccionan manualmente.
     */
    public function isManual(): bool
    {
        return $this->get('type')->value === self::TYPE_MANUAL;
    }

    /**
     * Indica si es una colección smart (automática).
     *
     * @return bool
     *   TRUE si los productos se seleccionan por reglas.
     */
    public function isSmart(): bool
    {
        return $this->get('type')->value === self::TYPE_SMART;
    }

    /**
     * Obtiene los IDs de productos de una colección manual.
     *
     * @return array
     *   Array de IDs de producto, o vacío si es smart o no hay IDs.
     */
    public function getProductIds(): array
    {
        $json = $this->get('product_ids')->value;
        if (!$json) {
            return [];
        }
        $ids = json_decode($json, TRUE);
        return is_array($ids) ? array_map('intval', $ids) : [];
    }

    /**
     * Obtiene las reglas smart de la colección.
     *
     * @return array
     *   Array asociativo con las reglas, o vacío si es manual o no hay reglas.
     */
    public function getRules(): array
    {
        $json = $this->get('rules')->value;
        if (!$json) {
            return [];
        }
        $rules = json_decode($json, TRUE);
        return is_array($rules) ? $rules : [];
    }

    /**
     * Obtiene la etiqueta legible del tipo.
     *
     * @return string
     *   "Manual" o "Smart".
     */
    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_MANUAL => t('Manual'),
            self::TYPE_SMART => t('Smart'),
        ];
        return (string) ($labels[$this->get('type')->value] ?? $this->get('type')->value);
    }

    /**
     * Indica si la colección está activa.
     *
     * @return bool
     *   TRUE si es visible en el marketplace.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

    /**
     * Indica si la colección está destacada.
     *
     * @return bool
     *   TRUE si se muestra en la home.
     */
    public function isFeatured(): bool
    {
        return (bool) $this->get('is_featured')->value;
    }

}
