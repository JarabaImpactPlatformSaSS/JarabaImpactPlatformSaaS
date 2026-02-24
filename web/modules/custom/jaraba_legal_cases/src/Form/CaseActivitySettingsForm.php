<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class CaseActivitySettingsForm extends FormBase {

  public function getFormId(): string {
    return 'case_activity_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Actividad de Expediente.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
