<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario premium para la entidad SessionReview.
 *
 * PREMIUM-FORMS-PATTERN-001: Extiende PremiumEntityFormBase con secciones
 * glassmorphism. Patron A (Simple) — sin DI extra ni logica custom.
 */
class SessionReviewForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'review' => [
        'label' => $this->t('Evaluacion'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Valoraciones de la sesion de mentoring.'),
        'fields' => [
          'title',
          'session_id',
          'reviewer_id',
          'reviewee_id',
          'review_type',
          'overall_rating',
          'punctuality_rating',
          'preparation_rating',
          'communication_rating',
          'comment',
          'would_recommend',
        ],
      ],
      'moderation' => [
        'label' => $this->t('Moderacion'),
        'icon' => ['category' => 'ui', 'name' => 'shield-check'],
        'description' => $this->t('Estado de moderacion y feedback interno.'),
        'fields' => [
          'review_status',
          'private_feedback',
          'tenant_id',
        ],
      ],
      'engagement' => [
        'label' => $this->t('Engagement'),
        'icon' => ['category' => 'ui', 'name' => 'thumbs-up'],
        'description' => $this->t('Metricas de interaccion y contenido IA.'),
        'fields' => [
          'helpful_count',
          'photos',
          'ai_summary',
          'ai_summary_generated_at',
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
