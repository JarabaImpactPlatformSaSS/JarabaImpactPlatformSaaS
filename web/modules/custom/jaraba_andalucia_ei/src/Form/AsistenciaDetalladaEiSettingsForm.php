<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for AsistenciaDetalladaEi (Field UI base route target).
 */
class AsistenciaDetalladaEiSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'asistencia_detallada_ei_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de asistencia detallada. Utiliza las pestañas superiores para gestionar campos y modos de visualización.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
