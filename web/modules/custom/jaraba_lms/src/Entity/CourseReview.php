<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\ecosistema_jaraba_core\Entity\ReviewableEntityTrait;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad CourseReview.
 *
 * Resena de un curso del LMS. Incluye sub-ratings (dificultad, calidad,
 * instructor), progreso al momento de la resena, y matricula verificada.
 * Implementa ReviewableEntityTrait para moderacion, IA y social proof.
 *
 * @ContentEntityType(
 *   id = "course_review",
 *   label = @Translation("Resena de Curso"),
 *   label_collection = @Translation("Resenas de Cursos"),
 *   label_singular = @Translation("resena de curso"),
 *   label_plural = @Translation("resenas de cursos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_lms\Form\CourseReviewForm",
 *       "add" = "Drupal\jaraba_lms\Form\CourseReviewForm",
 *       "edit" = "Drupal\jaraba_lms\Form\CourseReviewForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_lms\Access\CourseReviewAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "course_review",
 *   admin_permission = "administer lms",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/course-review/{course_review}",
 *     "add-form" = "/admin/content/course-review/add",
 *     "edit-form" = "/admin/content/course-review/{course_review}/edit",
 *     "delete-form" = "/admin/content/course-review/{course_review}/delete",
 *     "collection" = "/admin/content/course-reviews",
 *   },
 *   field_ui_base_route = "entity.course_review.settings",
 * )
 */
class CourseReview extends ContentEntityBase implements CourseReviewInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;
  use ReviewableEntityTrait;

  /**
   * {@inheritdoc}
   */
  public function getCourseId(): ?int {
    if ($this->hasField('course_id') && !$this->get('course_id')->isEmpty()) {
      return (int) $this->get('course_id')->target_id;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isVerifiedEnrollment(): bool {
    return (bool) ($this->get('verified_enrollment')->value ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getProgressAtReview(): int {
    return (int) ($this->get('progress_at_review')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // TENANT-BRIDGE-001.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE);

    $fields['course_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Curso'))
      ->setDescription(t('Curso evaluado.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'lms_course')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Valoracion general'))
      ->setDescription(t('Puntuacion de 1 a 5 estrellas.'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['difficulty_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Dificultad percibida'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE);

    $fields['content_quality_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Calidad del contenido'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE);

    $fields['instructor_rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Calidad del instructor'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Texto de la resena'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['progress_at_review'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Progreso al momento de la resena'))
      ->setDescription(t('Porcentaje completado (0-100) al escribir la resena.'))
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayConfigurable('view', TRUE);

    $fields['verified_enrollment'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Matricula verificada'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['instructor_response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Respuesta del instructor'))
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['instructor_response_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de respuesta del instructor'))
      ->setDisplayConfigurable('view', TRUE);

    // Campos del trait.
    $fields += static::reviewableBaseFieldDefinitions();

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
