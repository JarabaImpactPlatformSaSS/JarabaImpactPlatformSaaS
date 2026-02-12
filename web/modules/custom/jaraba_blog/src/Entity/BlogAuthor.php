<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad BlogAuthor para perfiles de autor del blog per-tenant.
 *
 * Permite gestionar perfiles de autor independientes del usuario Drupal,
 * con biografia, avatar y redes sociales.
 *
 * @ContentEntityType(
 *   id = "blog_author",
 *   label = @Translation("Autor del Blog"),
 *   label_collection = @Translation("Autores del Blog"),
 *   label_singular = @Translation("autor del blog"),
 *   label_plural = @Translation("autores del blog"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_blog\BlogAuthorListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_blog\Form\BlogAuthorForm",
 *       "add" = "Drupal\jaraba_blog\Form\BlogAuthorForm",
 *       "edit" = "Drupal\jaraba_blog\Form\BlogAuthorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_blog\BlogAuthorAccessControlHandler",
 *   },
 *   base_table = "blog_author",
 *   admin_permission = "administer blog",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.blog_author.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "display_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/blog-authors/{blog_author}",
 *     "add-form" = "/admin/content/blog-authors/add",
 *     "edit-form" = "/admin/content/blog-authors/{blog_author}/edit",
 *     "delete-form" = "/admin/content/blog-authors/{blog_author}/delete",
 *     "collection" = "/admin/content/blog-authors",
 *   },
 * )
 */
class BlogAuthor extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant (Group).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant al que pertenece este autor.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -100,
      ]);

    // Usuario Drupal vinculado.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario Drupal'))
      ->setDescription(t('Usuario de Drupal vinculado a este perfil de autor.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Nombre para mostrar.
    $fields['display_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre para mostrar'))
      ->setDescription(t('Nombre publico del autor.'))
      ->setSettings(['max_length' => 100])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Slug del autor.
    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slug'))
      ->setDescription(t('URL amigable del autor.'))
      ->setSettings(['max_length' => 100])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Biografia.
    $fields['bio'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Biografia'))
      ->setDescription(t('Biografia del autor para mostrar en el blog.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -7,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Avatar.
    $fields['avatar'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Avatar'))
      ->setDescription(t('Foto de perfil del autor.'))
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

    // Redes sociales.
    $fields['social_twitter'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Twitter / X'))
      ->setDescription(t('URL del perfil de Twitter/X.'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['social_linkedin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('LinkedIn'))
      ->setDescription(t('URL del perfil de LinkedIn.'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['social_website'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sitio web'))
      ->setDescription(t('URL del sitio web personal.'))
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Activo.
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si el autor esta activo.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Cache de conteo de posts.
    $fields['posts_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entradas'))
      ->setDescription(t('Numero de entradas del autor (cache).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Fecha de creacion del perfil de autor.'))
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
   * Obtiene el nombre para mostrar.
   */
  public function getDisplayName(): string {
    return (string) $this->get('display_name')->value;
  }

  /**
   * Obtiene el slug.
   */
  public function getSlug(): string {
    return (string) $this->get('slug')->value;
  }

  /**
   * Obtiene la biografia.
   */
  public function getBio(): string {
    return (string) ($this->get('bio')->value ?? '');
  }

  /**
   * Comprueba si el autor esta activo.
   */
  public function isActive(): bool {
    return (bool) $this->get('is_active')->value;
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
   * Obtiene las URLs de redes sociales.
   */
  public function getSocialLinks(): array {
    $links = [];
    $twitter = $this->get('social_twitter')->value;
    if ($twitter) {
      $links['twitter'] = $twitter;
    }
    $linkedin = $this->get('social_linkedin')->value;
    if ($linkedin) {
      $links['linkedin'] = $linkedin;
    }
    $website = $this->get('social_website')->value;
    if ($website) {
      $links['website'] = $website;
    }
    return $links;
  }

}
