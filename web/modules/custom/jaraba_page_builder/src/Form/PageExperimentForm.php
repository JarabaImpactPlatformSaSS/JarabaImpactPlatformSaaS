<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating and editing PageExperiment entities.
 *
 * Spec: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Extends PremiumEntityFormBase for glassmorphism sections, pill
 * navigation, and premium UX in A/B testing experiment management.
 */
class PageExperimentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Basic info'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Experiment name and target page.'),
        'fields' => ['name', 'page_id'],
      ],
      'config' => [
        'label' => $this->t('Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Goal, traffic allocation, and confidence threshold.'),
        'fields' => ['status', 'goal_type', 'goal_target', 'traffic_allocation', 'confidence_threshold'],
      ],
      'results' => [
        'label' => $this->t('Results'),
        'icon' => ['category' => 'analytics', 'name' => 'chart-bar'],
        'description' => $this->t('Winner and timing (updated automatically).'),
        'fields' => ['winner_variant', 'started_at', 'ended_at'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart-bar'];
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
