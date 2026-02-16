<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion del modulo de calendario.
 *
 * Proporciona field_ui_base_route para las entidades del modulo.
 */
class CalendarSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_legal_calendar_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuracion del modulo Agenda Juridica. Use las pestanas para gestionar campos de las entidades.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('Configuracion guardada.'));
  }

}
