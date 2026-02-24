<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class CanvasVersionSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'canvas_version_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Canvas Version.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
