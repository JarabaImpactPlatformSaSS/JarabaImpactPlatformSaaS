<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Conflicto POS entity type settings.
 */
final class PosConflictSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'comercio_pos_conflict_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Conflicto POS.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
