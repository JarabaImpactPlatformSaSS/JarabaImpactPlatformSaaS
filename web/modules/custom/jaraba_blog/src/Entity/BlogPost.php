<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad BlogPost para entradas del blog per-tenant.
 *
 * Almacena las entradas del blog con soporte para SEO, Schema.org,
 * publicacion programada, imagenes destacadas y conteo de visitas.
 *
 * @ContentEntityType(
 *   id = "blog_post",
 *   label = @Translation("Entrada del Blog"),
 *   label_collection = @Translation("Entradas del Blog"),
 *   label_singular = @Translation("entrada del blog"),
 *   label_plural = @Translation("entradas del blog"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_blog\BlogPostListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_blog\Form\BlogPostForm",
 *       "add" = "Drupal\jaraba_blog\Form\BlogPostForm",
 *       "edit" = "Drupal\jaraba_blog\Form\BlogPostForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_blog\BlogPostAccessControlHandler",
 *   },
 *   base_table = "blog_post",
 *   admin_permission = "administer blog",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.blog_post.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/blog-posts/{blog_post}",
 *     "add-form" = "/admin/content/blog-posts/add",
 *     "edit-form" = "/admin/content/blog-posts/{blog_post}/edit",
 *     "delete-form" = "/admin/content/blog-posts/{blog_post}/delete",
 *     "collection" = "/admin/content/blog-posts",
 *   },
 * )
 */
class BlogPost extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityOwnerTrait;
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Tenant (Group).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant al que pertenece esta entrada.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -100,
      ]);

    // Titulo.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Titulo de la entrada del blog.'))
      ->setSettings(['max_length' => 255])
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

    // Slug (URL amigable).
    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slug'))
      ->setDescription(t('URL amigable de la entrada.'))
      ->setSettings(['max_length' => 255])
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

    // Extracto.
    $fields['excerpt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Extracto'))
      ->setDescription(t('Resumen corto para listados y SEO.'))
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

    // Cuerpo (contenido completo).
    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Contenido'))
      ->setDescription(t('Contenido completo de la entrada.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -7,
        'settings' => ['rows' => 20],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Imagen destacada.
    $fields['featured_image'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Imagen destacada'))
      ->setDescription(t('Imagen principal de la entrada.'))
      ->setSetting('target_type', 'file')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_id',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Alt de imagen destacada.
    $fields['featured_image_alt'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alt imagen destacada'))
      ->setDescription(t('Texto alternativo accesible para la imagen destacada.'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Categoria.
    $fields['category_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Categoria'))
      ->setDescription(t('Categoria principal de la entrada.'))
      ->setSetting('target_type', 'blog_category')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tags (separados por coma).
    $fields['tags'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Etiquetas'))
      ->setDescription(t('Etiquetas separadas por coma.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'basic_string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -3,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Autor del blog.
    $fields['author_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Autor'))
      ->setDescription(t('Perfil de autor del blog.'))
      ->setSetting('target_type', 'blog_author')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado de publicacion.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado de publicacion de la entrada.'))
      ->setSettings([
        'allowed_values' => [
          'draft' => t('Borrador'),
          'published' => t('Publicado'),
          'scheduled' => t('Programado'),
          'archived' => t('Archivado'),
        ],
      ])
      ->setDefaultValue('draft')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Fecha de publicacion.
    $fields['published_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de publicacion'))
      ->setDescription(t('Fecha efectiva de publicacion.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Publicacion programada.
    $fields['scheduled_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Publicacion programada'))
      ->setDescription(t('Fecha y hora para publicacion automatica.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Destacado.
    $fields['is_featured'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Destacado'))
      ->setDescription(t('Marcar como entrada destacada en portada.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tiempo de lectura.
    $fields['reading_time'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tiempo de lectura'))
      ->setDescription(t('Tiempo estimado de lectura en minutos.'))
      ->setSettings(['min' => 0])
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Campos SEO ---

    $fields['meta_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Meta Title'))
      ->setDescription(t('Titulo para SEO (max 70 caracteres).'))
      ->setSettings(['max_length' => 70])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['meta_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Meta Description'))
      ->setDescription(t('Descripcion para SEO (max 160 caracteres).'))
      ->setSettings(['max_length' => 160])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Open Graph image.
    $fields['og_image'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Imagen Open Graph'))
      ->setDescription(t('Imagen para compartir en redes sociales.'))
      ->setSetting('target_type', 'file')
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Schema.org type.
    $fields['schema_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo Schema.org'))
      ->setDescription(t('Tipo de marcado estructurado para buscadores.'))
      ->setSettings([
        'allowed_values' => [
          'BlogPosting' => 'BlogPosting',
          'Article' => 'Article',
          'NewsArticle' => 'NewsArticle',
        ],
      ])
      ->setDefaultValue('BlogPosting')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Contador de visitas.
    $fields['views_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visitas'))
      ->setDescription(t('Contador de visitas de la entrada.'))
      ->setSettings(['min' => 0])
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Fecha de creacion de la entrada.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 30,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Ultima actualizacion'))
      ->setDescription(t('Fecha de ultima modificacion.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Obtiene el titulo.
   */
  public function getTitle(): string {
    return (string) $this->get('title')->value;
  }

  /**
   * Obtiene el slug.
   */
  public function getSlug(): string {
    return (string) $this->get('slug')->value;
  }

  /**
   * Obtiene el extracto.
   */
  public function getExcerpt(): string {
    return (string) ($this->get('excerpt')->value ?? '');
  }

  /**
   * Obtiene el cuerpo.
   */
  public function getBody(): string {
    return (string) ($this->get('body')->value ?? '');
  }

  /**
   * Obtiene el estado de publicacion.
   */
  public function getStatus(): string {
    return (string) ($this->get('status')->value ?? 'draft');
  }

  /**
   * Comprueba si la entrada esta publicada.
   */
  public function isPublished(): bool {
    return $this->getStatus() === 'published';
  }

  /**
   * Comprueba si la entrada esta destacada.
   */
  public function isFeatured(): bool {
    return (bool) $this->get('is_featured')->value;
  }

  /**
   * Obtiene el tiempo de lectura en minutos.
   */
  public function getReadingTime(): int {
    return (int) ($this->get('reading_time')->value ?? 0);
  }

  /**
   * Obtiene las etiquetas como array.
   */
  public function getTagsArray(): array {
    $raw = (string) ($this->get('tags')->value ?? '');
    if (empty($raw)) {
      return [];
    }
    return array_map('trim', explode(',', $raw));
  }

  /**
   * Obtiene el conteo de visitas.
   */
  public function getViewsCount(): int {
    return (int) ($this->get('views_count')->value ?? 0);
  }

  /**
   * Obtiene el tenant ID.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * Obtiene el ID de la categoria.
   */
  public function getCategoryId(): ?int {
    $value = $this->get('category_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * Obtiene el ID del autor.
   */
  public function getAuthorId(): ?int {
    $value = $this->get('author_id')->target_id;
    return $value ? (int) $value : NULL;
  }

}
