<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad ContentCategory (Categoría de Contenido).
 *
 * PROPÓSITO:
 * Entidad para organizar artículos del Content Hub en categorías.
 * Soporta jerarquía (categorías padre/hijo) y personalización
 * visual con colores e iconos.
 *
 * CARACTERÍSTICAS:
 * - Nombres y descripciones traducibles
 * - Jerarquía mediante campo parent
 * - Personalización con color hex e icono
 * - Ordenación por peso
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 *
 * @ContentEntityType(
 *   id = "content_category",
 *   label = @Translation("Content Category"),
 *   label_collection = @Translation("Categories"),
 *   label_singular = @Translation("category"),
 *   label_plural = @Translation("categories"),
 *   label_count = @PluralTranslation(
 *     singular = "@count category",
 *     plural = "@count categories",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_content_hub\ContentCategoryListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\jaraba_content_hub\Form\ContentCategoryForm",
 *       "edit" = "Drupal\jaraba_content_hub\Form\ContentCategoryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "content_category",
 *   data_table = "content_category_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer content categories",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/blog/category/{content_category}",
 *     "add-form" = "/admin/structure/content-hub/categories/add",
 *     "edit-form" = "/admin/structure/content-hub/categories/{content_category}/edit",
 *     "delete-form" = "/admin/structure/content-hub/categories/{content_category}/delete",
 *     "collection" = "/admin/structure/content-hub/categories",
 *   },
 * )
 */
class ContentCategory extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Obtiene el nombre de la categoría.
     *
     * @return string
     *   El nombre de la categoría.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Obtiene el slug URL-friendly de la categoría.
     *
     * @return string
     *   El slug para la URL.
     */
    public function getSlug(): string
    {
        return $this->get('slug')->value ?? '';
    }

    /**
     * Obtiene el color de la categoría.
     *
     * @return string
     *   El código de color hexadecimal (ej: #233D63).
     */
    public function getColor(): string
    {
        return $this->get('color')->value ?? '#233D63';
    }

    /**
     * Obtiene el nombre del icono de la categoría.
     *
     * @return string
     *   El nombre del icono (ej: folder, document).
     */
    public function getIcon(): string
    {
        return $this->get('icon')->value ?? 'folder';
    }

    /**
     * {@inheritdoc}
     *
     * Define los campos base de la entidad ContentCategory.
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre de la categoría - requerido y traducible.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('El nombre de la categoría.'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
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

        // Slug para URLs amigables.
        $fields['slug'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL Slug'))
            ->setDescription(t('El slug amigable para la URL.'))
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Descripción de la categoría.
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Una descripción de la categoría.'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Categoría padre para jerarquía.
        $fields['parent'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Categoría Padre'))
            ->setDescription(t('La categoría padre para crear jerarquía.'))
            ->setSetting('target_type', 'content_category')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Color de la categoría (hexadecimal).
        $fields['color'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Color'))
            ->setDescription(t('El color de la categoría (código hex).'))
            ->setDefaultValue('#233D63')
            ->setSetting('max_length', 7)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Icono de la categoría.
        $fields['icon'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Icono'))
            ->setDescription(t('El nombre del icono de la categoría.'))
            ->setDefaultValue('folder')
            ->setSetting('max_length', 50)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Peso para ordenación.
        $fields['weight'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Peso'))
            ->setDescription(t('El peso para ordenación.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Timestamps automáticos.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('La fecha de creación de la categoría.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('La fecha de última modificación de la categoría.'));

        return $fields;
    }

}
