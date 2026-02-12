<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para CrossVerticalRule.
 *
 * Permite el acceso a Field UI para la entidad.
 */
class CrossVerticalRuleSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cross_vertical_rule_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['cross_vertical_rule_settings'] = [
      '#markup' => $this->t('Utiliza las pestañas superiores para administrar los campos de la entidad Regla Cross-Vertical.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No hay configuración adicional que guardar.
  }

}
