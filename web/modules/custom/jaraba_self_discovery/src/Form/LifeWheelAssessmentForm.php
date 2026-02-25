<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing Life Wheel assessments.
 */
class LifeWheelAssessmentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'assessment' => [
        'label' => $this->t('Assessment'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'description' => $this->t('User and assessment info.'),
        'fields' => ['user_id'],
      ],
      'dimensions' => [
        'label' => $this->t('Dimensions'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Rate each life area from 1 (very dissatisfied) to 10 (very satisfied).'),
        'fields' => ['score_career', 'score_finance', 'score_health', 'score_family', 'score_social', 'score_growth', 'score_leisure', 'score_environment'],
      ],
      'notes' => [
        'label' => $this->t('Notes'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Additional observations.'),
        'fields' => ['notes'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'gauge'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();

    // Auto-assign current user for new assessments.
    if ($entity->isNew()) {
      $entity->set('user_id', $this->currentUser()->id());
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirect('entity.life_wheel_assessment.collection');
    return $result;
  }

}
