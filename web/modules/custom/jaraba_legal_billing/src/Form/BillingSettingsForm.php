<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para Field UI base routes.
 *
 * Estructura: FormBase minimo requerido por field_ui_base_route.
 * Logica: Placeholder para que Field UI pueda adjuntar tabs de campos.
 */
class BillingSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_legal_billing_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => $this->t('Configuracion de facturacion legal. Use las pestanas de arriba para gestionar campos.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
