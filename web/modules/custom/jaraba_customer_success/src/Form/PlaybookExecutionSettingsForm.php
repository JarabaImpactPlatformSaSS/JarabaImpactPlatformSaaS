<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class PlaybookExecutionSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'playbook_execution_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Playbook Execution.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
