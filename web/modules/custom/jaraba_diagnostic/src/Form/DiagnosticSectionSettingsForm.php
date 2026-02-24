<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class DiagnosticSectionSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'diagnostic_section_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Seccion de Diagnostico.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
