<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para Pilot Program (Field UI base route).
 *
 * FIELD-UI-SETTINGS-TAB-001: Toda entity con field_ui_base_route
 * DEBE tener settings form.
 */
class PilotProgramSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'pilot_program_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Pilot Program entity settings. Use the tabs above to manage fields, form display, and view display.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
