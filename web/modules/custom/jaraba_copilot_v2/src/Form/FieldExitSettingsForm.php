<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form de configuracion para la entidad FieldExit.
 */
class FieldExitSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'field_exit_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => $this->t('<p>Configuracion de la entidad Field Exits (Customer Discovery). Usa las pestanas superiores para gestionar campos y formularios.</p>'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
