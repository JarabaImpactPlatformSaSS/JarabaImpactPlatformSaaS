<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for DR Test Result entities.
 */
class DrTestResultForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'test' => [
        'label' => $this->t('Test'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'fields' => ['test_name', 'test_type', 'description', 'executed_by'],
      ],
      'execution' => [
        'label' => $this->t('Execution'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'fields' => ['started_at', 'completed_at', 'duration_seconds'],
      ],
      'results' => [
        'label' => $this->t('Results'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'fields' => ['rto_achieved', 'rpo_achieved', 'results_data'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'fields' => ['status'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'shield'];
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
