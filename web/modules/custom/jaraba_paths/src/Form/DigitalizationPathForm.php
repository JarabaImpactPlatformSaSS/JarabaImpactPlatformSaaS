<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing digitalization paths.
 */
class DigitalizationPathForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic_info' => [
        'label' => $this->t('Basic Information'),
        'icon' => ['category' => 'business', 'name' => 'roadmap'],
        'description' => $this->t('Essential path data visible to users.'),
        'fields' => ['title', 'short_description', 'description', 'image'],
      ],
      'targeting' => [
        'label' => $this->t('Target Audience'),
        'icon' => ['category' => 'ui', 'name' => 'target'],
        'description' => $this->t('Define who this path is aimed at.'),
        'fields' => ['target_sector', 'target_maturity_level', 'target_business_size', 'difficulty_level'],
      ],
      'metrics' => [
        'label' => $this->t('Metrics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Time and return estimates.'),
        'fields' => ['estimated_weeks', 'expected_roi_percent', 'total_steps', 'total_quick_wins'],
      ],
      'publishing' => [
        'label' => $this->t('Publishing'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Visibility and assignment.'),
        'fields' => ['status', 'is_featured', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'business', 'name' => 'roadmap'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getCharacterLimits(): array {
    return [
      'short_description' => 160,
    ];
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
