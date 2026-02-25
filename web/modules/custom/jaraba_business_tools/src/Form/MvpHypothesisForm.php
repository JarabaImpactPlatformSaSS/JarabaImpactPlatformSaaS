<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for MVP Hypothesis entities.
 */
class MvpHypothesisForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'hypothesis' => [
        'label' => $this->t('Hypothesis'),
        'icon' => ['category' => 'analytics', 'name' => 'gauge'],
        'fields' => ['canvas_id', 'hypothesis', 'target_segment', 'experiment_type'],
      ],
      'criteria' => [
        'label' => $this->t('Success Criteria'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'fields' => ['success_criteria', 'min_success_threshold', 'sample_size'],
      ],
      'timeline' => [
        'label' => $this->t('Timeline'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'fields' => ['start_date', 'end_date'],
      ],
      'results' => [
        'label' => $this->t('Results'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['results_data', 'actual_result', 'result_status', 'learnings', 'pivot_decision'],
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
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
