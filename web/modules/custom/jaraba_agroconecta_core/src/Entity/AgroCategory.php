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
 * Define la entidad AgroCategory.
 *
 * Representa una categoría jerárquica de productos en el marketplace
 * AgroConecta. Soporta anidación (parent), posición manual, imagen,
 * icono y conteo de productos para navegación, filtros y SEO.
 *
 * @ContentEntityType(
 *   id = "agro_category",
 *   label = @Translation("Categoría Agro"),
 *   label_collection = @Translation("Categorías Agro"),
 *   label_singular = @Translation("categoría agro"),
 *   label_plural = @Translation("categorías agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\AgroCategoryListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\AgroCategoryForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\AgroCategoryForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\AgroCategoryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AgroCategoryAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agro_category",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.agro_category.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-categories/{agro_category}",
 *     "add-form" = "/admin/content/agro-categories/add",
 *     "edit-form" = "/admin/content/agro-categories/{agro_category}/edit",
 *     "delete-form" = "/admin/content/agro-categories/{agro_category}/delete",
 *     "collection" = "/admin/content/agro-categories",
 *   },
 * )
 */
class AgroCategory extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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

        // Tenant ID para multi-tenancy.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Marketplace al que pertenece esta categoría.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Nombre de la categoría.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre visible de la categoría (ej: Aceites de Oliva, Vinos).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Slug para URLs limpias.
        $fields['slug'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Slug'))
            ->setDescription(t('Identificador URL-friendly (ej: aceites-de-oliva). Se genera automáticamente si se deja vacío.'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción de la categoría (SEO).
        $fields['description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción de la categoría para SEO y listados.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -8,
                'settings' => [
                    'rows' => 4,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Categoría padre (jerarquía).
        $fields['parent_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Categoría padre'))
            ->setDescription(t('Categoría padre para jerarquía. Dejar vacío para categoría raíz.'))
            ->setSetting('target_type', 'agro_category')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nombre del icono (ej: olive, wine, cheese).
        $fields['icon'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Icono'))
            ->setDescription(t('Nombre del icono SVG para la categoría (ej: olive, wine, grain).'))
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Imagen de la categoría.
        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Imagen'))
            ->setDescription(t('Imagen representativa de la categoría para listados y cabeceras.'))
            ->setSetting('file_extensions', 'png jpg jpeg webp')
            ->setSetting('alt_field', TRUE)
            ->setSetting('file_directory', 'agro/categories')
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Posición para ordenación manual.
        $fields['position'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Posición'))
            ->setDescription(t('Orden de aparición en listados. Menor número = primero.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ¿Categoría destacada?
        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacada'))
            ->setDescription(t('Mostrar esta categoría en la página principal y en posiciones destacadas.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Conteo de productos (cache, actualizado por cron/hook).
        $fields['product_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Productos'))
            ->setDescription(t('Número de productos activos en esta categoría. Se actualiza automáticamente.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', TRUE);

        // ¿Categoría activa?
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Solo las categorías activas son visibles en el marketplace.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Meta title para SEO.
        $fields['meta_title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Meta título'))
            ->setDescription(t('Título para SEO (tag <title>). Si vacío, se usa el nombre.'))
            ->setSetting('max_length', 120)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Meta description para SEO.
        $fields['meta_description'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Meta descripción'))
            ->setDescription(t('Descripción para SEO (meta description). Máx. 160 caracteres.'))
            ->setSetting('max_length', 160)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Campos de sistema.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene el slug de la categoría.
     *
     * @return string
     *   Slug URL-friendly de la categoría.
     */
    public function getSlug(): string
    {
        return $this->get('slug')->value ?? '';
    }

    /**
     * Indica si la categoría es raíz (sin padre).
     *
     * @return bool
     *   TRUE si no tiene categoría padre.
     */
    public function isRoot(): bool
    {
        return empty($this->get('parent_id')->target_id);
    }

    /**
     * Indica si la categoría está destacada.
     *
     * @return bool
     *   TRUE si es una categoría destacada.
     */
    public function isFeatured(): bool
    {
        return (bool) $this->get('is_featured')->value;
    }

    /**
     * Indica si la categoría está activa.
     *
     * @return bool
     *   TRUE si la categoría es visible en el marketplace.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

    /**
     * Obtiene el número de productos en esta categoría.
     *
     * @return int
     *   Conteo de productos activos.
     */
    public function getProductCount(): int
    {
        return (int) ($this->get('product_count')->value ?? 0);
    }

    /**
     * Obtiene la categoría padre, si existe.
     *
     * @return \Drupal\jaraba_agroconecta_core\Entity\AgroCategory|null
     *   Entidad padre o NULL si es raíz.
     */
    public function getParent(): ?AgroCategory
    {
        $parent_id = $this->get('parent_id')->target_id;
        if ($parent_id) {
            return $this->entityTypeManager()->getStorage('agro_category')->load($parent_id);
        }
        return NULL;
    }

    /**
     * Obtiene el breadcrumb jerárquico de la categoría.
     *
     * @return array
     *   Array de ['id' => int, 'name' => string, 'slug' => string].
     */
    public function getBreadcrumb(): array
    {
        $breadcrumb = [];
        $current = $this;
        $max_depth = 10; // Protección contra loops.

        while ($current && $max_depth > 0) {
            array_unshift($breadcrumb, [
                'id' => (int) $current->id(),
                'name' => $current->label(),
                'slug' => $current->getSlug(),
            ]);
            $current = $current->getParent();
            $max_depth--;
        }

        return $breadcrumb;
    }

}
