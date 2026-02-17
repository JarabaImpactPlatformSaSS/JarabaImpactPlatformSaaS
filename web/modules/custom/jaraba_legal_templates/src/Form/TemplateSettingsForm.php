<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_templates\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para Field UI base routes de Templates.
 */
class TemplateSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'jaraba_legal_templates_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => $this->t('Configuracion de plantillas juridicas. Use las pestanas de arriba para gestionar campos.'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
