<?php

declare(strict_types=1);

namespace Drupal\jaraba_sla\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class SlaMeasurementSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'sla_measurement_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for SLA Measurement.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
