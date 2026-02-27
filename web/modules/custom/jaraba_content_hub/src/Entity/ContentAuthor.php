<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad ContentAuthor (Autor Editorial del Content Hub).
 *
 * PROPOSITO:
 * Perfiles de autor editoriales desacoplados de las cuentas de usuario
 * Drupal. Permite gestionar autores con biografia, avatar y redes
 * sociales de forma independiente por tenant.
 *
 * Backported de jaraba_blog/BlogAuthor y elevado a clase mundial:
 * - tenant_id como entity_reference a group (TENANT-BRIDGE-001)
 * - Soporte multi-idioma (translatable)
 * - PremiumEntityFormBase para forms (PREMIUM-FORMS-PATTERN-001)
 * - Tenant isolation en access handler (TENANT-ISOLATION-ACCESS-001)
 *
 * ESPECIFICACION: Plan Consolidacion Content Hub + Blog v1 - Seccion 7.4
 *
 * @ContentEntityType(
 *   id = "content_author",
 *   label = @Translation("Content Author"),
 *   label_collection = @Translation("Content Authors"),
 *   label_singular = @Translation("content author"),
 *   label_plural = @Translation("content authors"),
 *   label_count = @PluralTranslation(
 *     singular = "@count content author",
 *     plural = "@count content authors",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_content_hub\ContentAuthorListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_content_hub\ContentAuthorAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\jaraba_content_hub\Form\ContentAuthorForm",
 *       "edit" = "Drupal\jaraba_content_hub\Form\ContentAuthorForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "content_author",
 *   data_table = "content_author_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer content authors",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "display_name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/content-authors/{content_author}",
 *     "add-form" = "/admin/content/content-authors/add",
 *     "edit-form" = "/admin/content/content-authors/{content_author}/edit",
 *     "delete-form" = "/admin/content/content-authors/{content_author}/delete",
 *     "collection" = "/admin/content/content-authors",
 *   },
 *   field_ui_base_route = "entity.content_author.settings",
 * )
 */
class ContentAuthor extends ContentEntityBase implements ContentAuthorInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getDisplayName(): string {
    return $this->get('display_name')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSlug(): string {
    return $this->get('slug')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setSlug(string $slug): static {
    $this->set('slug', $slug);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBio(): string {
    return $this->get('bio')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return (bool) ($this->get('is_active')->value ?? TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function getPostsCount(): int {
    return (int) ($this->get('posts_count')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Tenant (Group) — TENANT-BRIDGE-001.
    // entity_reference a group para aislamiento multi-tenant real.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant (grupo) al que pertenece este autor.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -100,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Usuario Drupal vinculado (opcional).
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario Drupal'))
      ->setDescription(t('Usuario de Drupal vinculado a este perfil de autor (opcional).'))
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

    // Nombre para mostrar — requerido y traducible.
    $fields['display_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre para mostrar'))
      ->setDescription(t('Nombre publico del autor.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 100)
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

    // Slug para URLs amigables.
    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Slug'))
      ->setDescription(t('URL amigable del autor (ej: jose-garcia).'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Biografia — traducible.
    $fields['bio'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Biografia'))
      ->setDescription(t('Biografia del autor para mostrar en el blog.'))
      ->setTranslatable(TRUE)
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

    // Avatar (foto de perfil).
    $fields['avatar'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Avatar'))
      ->setDescription(t('Foto de perfil del autor.'))
      ->setSetting('target_type', 'file')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'image',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Redes sociales.
    $fields['social_twitter'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Twitter / X'))
      ->setDescription(t('URL del perfil de Twitter/X.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['social_linkedin'] = BaseFieldDefinition::create('string')
      ->setLabel(t('LinkedIn'))
      ->setDescription(t('URL del perfil de LinkedIn.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['social_website'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sitio web'))
      ->setDescription(t('URL del sitio web personal.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Autor activo/inactivo.
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

    // Cache de conteo de articulos publicados.
    $fields['posts_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Articulos'))
      ->setDescription(t('Numero de articulos publicados del autor (cache).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creacion del perfil de autor.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'))
      ->setDescription(t('Fecha de ultima modificacion.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
