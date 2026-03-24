<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for NegocioProspectadoEi (Field UI base route target).
 *
 * FIELD-UI-SETTINGS-TAB-001.
 */
class NegocioProspectadoEiSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'negocio_prospectado_ei_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de negocios prospectados. Utiliza las pestañas superiores para gestionar campos y modos de visualización.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
