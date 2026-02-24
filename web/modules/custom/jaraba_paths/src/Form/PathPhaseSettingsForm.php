<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class PathPhaseSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'path_phase_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Fase del Itinerario.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
