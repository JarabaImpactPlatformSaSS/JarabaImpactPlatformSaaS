<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario premium para la entidad CourseReview.
 *
 * PREMIUM-FORMS-PATTERN-001: Patron A (Simple).
 */
class CourseReviewForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'review' => [
        'label' => $this->t('Evaluacion'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Valoracion del curso y sub-ratings.'),
        'fields' => [
          'title',
          'course_id',
          'rating',
          'difficulty_rating',
          'content_quality_rating',
          'instructor_rating',
          'body',
          'progress_at_review',
          'verified_enrollment',
        ],
      ],
      'moderation' => [
        'label' => $this->t('Moderacion'),
        'icon' => ['category' => 'ui', 'name' => 'shield-check'],
        'description' => $this->t('Estado de moderacion y tenant.'),
        'fields' => [
          'review_status',
          'tenant_id',
        ],
      ],
      'response' => [
        'label' => $this->t('Respuesta'),
        'icon' => ['category' => 'ui', 'name' => 'reply'],
        'description' => $this->t('Respuesta del instructor.'),
        'fields' => [
          'instructor_response',
          'instructor_response_date',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'star'];
  }

}
