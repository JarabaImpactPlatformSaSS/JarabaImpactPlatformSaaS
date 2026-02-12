<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad BlogCategory para categorias del blog per-tenant.
 *
 * Soporte para jerarquia padre/hijo, icono, color y SEO.
 *
 * @ContentEntityType(
 *   id = "blog_category",
 *   label = @Translation("Categoria del Blog"),
 *   label_collection = @Translation("Categorias del Blog"),
 *   label_singular = @Translation("categoria del blog"),
 *   label_plural = @Translation("categorias del blog"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_blog\BlogCategoryListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_blog\Form\BlogCategoryForm",
 *       "add" = "Drupal\jaraba_blog\Form\BlogCategoryForm",
 *       "edit" = "Drupal\jaraba_blog\Form\BlogCategoryForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_blog\BlogCategoryAccessControlHandler",
 *   },
 *   base_table = "blog_category",
 *   admin_permission = "administer blog",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.blog_category.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/blog-categories/{blog_category}",
 *     "add-form" = "/admin/content/blog-categories/add",
 *     "edit-form" = "/admin/content/blog-categories/{blog_category}/edit",
 *     "delete-form" = "/admin/content/blog-categories/{blog_category}/delete",
 *     "collection" = "/admin/content/blog-categories",
 *   },
 * )
 */
class BlogCategory extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant (Group).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant al que pertenece esta categoria.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -100,
      ]);

    // Nombre.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre de la categoria.'))
      ->setSettings(['max_length' => 100])
      ->setRequired(TRUE)
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

    // Slug.
    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slug'))
      ->setDescription(t('URL amigable de la categoria.'))
      ->setSettings(['max_length' => 100])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Descripcion.
    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripcion'))
      ->setDescription(t('Descripcion de la categoria.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => -8,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -8,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Padre (jerarquia).
    $fields['parent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Categoria padre'))
      ->setDescription(t('Categoria padre para jerarquia anidada.'))
      ->setSetting('target_type', 'blog_category')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Icono.
    $fields['icon'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Icono'))
      ->setDescription(t('Nombre del icono (ej: folder, tag, star).'))
      ->setSettings(['max_length' => 50])
      ->setDefaultValue('folder')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Color.
    $fields['color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color'))
      ->setDescription(t('Color hexadecimal (ej: #233D63).'))
      ->setSettings(['max_length' => 7])
      ->setDefaultValue('#233D63')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Orden.
    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Orden'))
      ->setDescription(t('Peso para ordenamiento.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Activa.
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDescription(t('Indica si la categoria esta activa y visible.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Cache de conteo de posts.
    $fields['posts_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entradas'))
      ->setDescription(t('Numero de entradas en esta categoria (cache).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // --- Campos SEO ---

    $fields['meta_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Meta Title'))
      ->setDescription(t('Titulo SEO de la categoria (max 70 caracteres).'))
      ->setSettings(['max_length' => 70])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['meta_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Meta Description'))
      ->setDescription(t('Descripcion SEO de la categoria (max 160 caracteres).'))
      ->setSettings(['max_length' => 160])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Fecha de creacion de la categoria.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Ultima actualizacion'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Obtiene el nombre.
   */
  public function getName(): string {
    return (string) $this->get('name')->value;
  }

  /**
   * Obtiene el slug.
   */
  public function getSlug(): string {
    return (string) $this->get('slug')->value;
  }

  /**
   * Comprueba si la categoria esta activa.
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
  }

  /**
   * Obtiene el color hex.
   */
  public function getColor(): string {
    return (string) ($this->get('color')->value ?? '#233D63');
  }

  /**
   * Obtiene el icono.
   */
  public function getIcon(): string {
    return (string) ($this->get('icon')->value ?? 'folder');
  }

  /**
   * Obtiene el conteo de posts cacheado.
   */
  public function getPostsCount(): int {
    return (int) ($this->get('posts_count')->value ?? 0);
  }

  /**
   * Obtiene el tenant ID.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * Obtiene el ID del padre.
   */
  public function getParentId(): ?int {
    $value = $this->get('parent_id')->target_id;
    return $value ? (int) $value : NULL;
  }

}
