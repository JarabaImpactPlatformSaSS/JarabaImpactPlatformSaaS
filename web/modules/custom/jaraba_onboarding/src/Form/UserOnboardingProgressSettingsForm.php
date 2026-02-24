<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class UserOnboardingProgressSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'user_onboarding_progress_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Progreso de Onboarding.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
