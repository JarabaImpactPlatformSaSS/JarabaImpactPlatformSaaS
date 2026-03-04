<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion de Mentorias.
 *
 * Estructura: FormBase para la ruta jaraba_mentoring.settings.
 * Logica: Punto de entrada admin para configuracion de mentorias.
 */
class MentoringSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_mentoring_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Mentoring configuration. Use the tabs above to manage mentor profiles, packages, and session settings.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
