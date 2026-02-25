<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing onboarding templates.
 */
class OnboardingTemplateForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'template' => [
        'label' => $this->t('Template'),
        'icon' => ['category' => 'ui', 'name' => 'layout'],
        'description' => $this->t('Template name and description.'),
        'fields' => ['name', 'vertical', 'description'],
      ],
      'steps' => [
        'label' => $this->t('Steps'),
        'icon' => ['category' => 'business', 'name' => 'roadmap'],
        'description' => $this->t('Onboarding steps configuration.'),
        'fields' => ['steps_config'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Publication status.'),
        'fields' => ['status', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layout'];
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
