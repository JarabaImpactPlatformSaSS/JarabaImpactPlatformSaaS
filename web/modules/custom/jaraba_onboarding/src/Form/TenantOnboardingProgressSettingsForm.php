<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class TenantOnboardingProgressSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'tenant_onboarding_progress_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Progreso de Onboarding de Tenant.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
