<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * CATEGORÍA DE BASE DE CONOCIMIENTO - KbCategory
 *
 * PROPÓSITO:
 * Organiza los artículos de la base de conocimiento en categorías
 * jerárquicas con soporte para anidamiento via parent_id.
 *
 * ESTRUCTURA:
 * - name: Nombre de la categoría
 * - slug: URL amigable
 * - description: Descripción larga
 * - icon: Icono representativo (clase CSS o nombre)
 * - sort_order: Orden de visualización
 * - parent_id: Referencia a categoría padre para anidamiento
 * - category_status: active / inactive
 *
 * MULTI-TENANCY:
 * Campo tenant_id obligatorio. Aislamiento completo por tenant.
 *
 * @ContentEntityType(
 *   id = "kb_category",
 *   label = @Translation("Categoría KB"),
 *   label_collection = @Translation("Categorías KB"),
 *   label_singular = @Translation("categoría KB"),
 *   label_plural = @Translation("categorías KB"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_tenant_knowledge\KbCategoryListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\KbCategoryForm",
 *       "add" = "Drupal\jaraba_tenant_knowledge\Form\KbCategoryForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\KbCategoryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\Access\KbCategoryAccessControlHandler",
 *   },
 *   base_table = "kb_category",
 *   admin_permission = "administer tenant knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/content/kb-categories",
 *     "add-form" = "/admin/content/kb-categories/add",
 *     "edit-form" = "/admin/content/kb-categories/{kb_category}/edit",
 *     "delete-form" = "/admin/content/kb-categories/{kb_category}/delete",
 *   },
 *   field_ui_base_route = "entity.kb_category.settings",
 * )
 */
class KbCategory extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al tenant propietario (OBLIGATORIO).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario de esta categoría.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === CONTENIDO PRINCIPAL ===

        // Nombre de la categoría.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre de la categoría.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ]);

        // Slug URL amigable.
        $fields['slug'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Slug'))
            ->setDescription(t('URL amigable de la categoría.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ]);

        // Descripción.
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción de la categoría.'))
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'text_default',
                'weight' => 2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
                'settings' => [
                    'rows' => 4,
                ],
            ]);

        // Icono.
        $fields['icon'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Icono'))
            ->setDescription(t('Nombre del icono representativo (ej: book, help-circle).'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 3,
            ]);

        // === ORGANIZACIÓN ===

        // Orden de visualización.
        $fields['sort_order'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Orden'))
            ->setDescription(t('Orden de visualización. Menor número = aparece primero.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 4,
            ]);

        // Categoría padre para anidamiento.
        $fields['parent_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Categoría Padre'))
            ->setDescription(t('Categoría padre para crear jerarquías.'))
            ->setSetting('target_type', 'kb_category')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 5,
            ]);

        // === ESTADO ===

        // Estado de la categoría.
        $fields['category_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado de la categoría.'))
            ->setRequired(TRUE)
            ->setSettings([
                'allowed_values' => [
                    'active' => 'Activa',
                    'inactive' => 'Inactiva',
                ],
            ])
            ->setDefaultValue('active')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 6,
            ]);

        // === TIMESTAMPS ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene el nombre de la categoría.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Obtiene el slug.
     */
    public function getSlug(): string
    {
        return $this->get('slug')->value ?? '';
    }

    /**
     * Obtiene la descripción.
     */
    public function getDescription(): string
    {
        return $this->get('description')->value ?? '';
    }

    /**
     * Obtiene el icono.
     */
    public function getIcon(): string
    {
        return $this->get('icon')->value ?? 'help-circle';
    }

    /**
     * Obtiene el estado de la categoría.
     */
    public function getCategoryStatus(): string
    {
        return $this->get('category_status')->value ?? 'active';
    }

    /**
     * Verifica si la categoría está activa.
     */
    public function isActive(): bool
    {
        return $this->getCategoryStatus() === 'active';
    }

    /**
     * Obtiene el tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
    }

    /**
     * Obtiene el ID de la categoría padre.
     */
    public function getParentId(): ?int
    {
        return $this->get('parent_id')->target_id ? (int) $this->get('parent_id')->target_id : NULL;
    }

}
