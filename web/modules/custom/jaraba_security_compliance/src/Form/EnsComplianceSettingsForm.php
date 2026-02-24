<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class EnsComplianceSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'ens_compliance_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for ENS Compliance Measure.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
