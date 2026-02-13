<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Course entity.
 *
 * Un curso representa un programa formativo completo dentro del LMS.
 * Contiene múltiples lecciones y puede pertenecer a uno o más learning paths.
 *
 * @ContentEntityType(
 *   id = "lms_course",
 *   label = @Translation("Course"),
 *   label_collection = @Translation("Courses"),
 *   label_singular = @Translation("course"),
 *   label_plural = @Translation("courses"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_lms\CourseListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_lms\Form\CourseForm",
 *       "add" = "Drupal\jaraba_lms\Form\CourseForm",
 *       "edit" = "Drupal\jaraba_lms\Form\CourseForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_lms\CourseAccessControlHandler",
 *   },
 *   base_table = "lms_course",
 *   admin_permission = "administer lms courses",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "author_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/course/{lms_course}",
 *     "add-form" = "/admin/content/courses/add",
 *     "edit-form" = "/admin/content/course/{lms_course}/edit",
 *     "delete-form" = "/admin/content/course/{lms_course}/delete",
 *     "collection" = "/admin/content/courses",
 *   },
 *   field_ui_base_route = "entity.lms_course.settings",
 * )
 */
class Course extends ContentEntityBase implements CourseInterface
{

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string
  {
    return $this->get('title')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle(string $title): CourseInterface
  {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName(): string
  {
    return $this->get('machine_name')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): ?string
  {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(): string
  {
    return $this->get('summary')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDurationMinutes(): int
  {
    return (int) ($this->get('duration_minutes')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getDifficultyLevel(): string
  {
    return $this->get('difficulty_level')->value ?? 'beginner';
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished(): bool
  {
    return (bool) $this->get('is_published')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished(bool $published): CourseInterface
  {
    $this->set('is_published', $published);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPremium(): bool
  {
    return (bool) $this->get('is_premium')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice(): ?float
  {
    $price = $this->get('price')->value;
    return $price !== NULL ? (float) $price : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionCredits(): int
  {
    return (int) ($this->get('completion_credits')->value ?? 100);
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int
  {
    $value = $this->get('tenant_id')->value;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrerequisites(): array
  {
    $json = $this->get('prerequisites')->value;
    return $json ? json_decode($json, TRUE) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTags(): array
  {
    $json = $this->get('tags')->value;
    return $json ? json_decode($json, TRUE) : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
  {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The course title.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('URL-friendly identifier.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ]);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Full course description.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
      ])
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['summary'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Summary'))
      ->setDescription(t('Short summary for cards and SEO.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ]);

    $fields['duration_minutes'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration (minutes)'))
      ->setDescription(t('Estimated total duration.'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setDefaultValue(60)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -6,
      ]);

    $fields['difficulty_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Difficulty level'))
      ->setDescription(t('Course difficulty.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'beginner' => t('Beginner'),
        'intermediate' => t('Intermediate'),
        'advanced' => t('Advanced'),
      ])
      ->setDefaultValue('beginner')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ]);

    $fields['vertical_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Vertical'))
      ->setDescription(t('Associated vertical.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => ['vertical'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ]);

    $fields['field_category'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Category'))
      ->setDescription(t('Course category for grouping and filtering.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => ['course_category'],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // AUDIT-CONS-005: tenant_id como entity_reference al entity type 'tenant'.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant owner (NULL = global).'))
      ->setSetting('target_type', 'tenant');

    $fields['is_published'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDescription(t('Whether the course is visible.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['is_premium'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Premium'))
      ->setDescription(t('Requires subscription or payment.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -2,
      ]);

    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Price'))
      ->setDescription(t('Price if paid course.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -1,
      ]);

    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency'))
      ->setDescription(t('ISO 4217 currency code.'))
      ->setSetting('max_length', 3)
      ->setDefaultValue('EUR');

    $fields['completion_credits'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Completion credits'))
      ->setDescription(t('Impact credits awarded on completion.'))
      ->setDefaultValue(100)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ]);

    $fields['prerequisites'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Prerequisites'))
      ->setDescription(t('JSON array of prerequisite course IDs.'));

    $fields['tags'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Tags'))
      ->setDescription(t('JSON array of taxonomy term IDs.'));

    $fields['thumbnail'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Thumbnail'))
      ->setDescription(t('Course thumbnail image.'))
      ->setSetting('target_type', 'file')
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 1,
      ]);

    $fields['certificate_template_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Certificate template'))
      ->setDescription(t('Template ID for completion certificate.'));

    $fields['author_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('Course author.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Creation timestamp.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Last modification timestamp.'));

    return $fields;
  }

}
