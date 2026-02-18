<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CustomerProfileSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'customer_profile_retail_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuracion de la entidad Perfil de Cliente. Usa las pestanas de arriba para gestionar campos y visualizacion.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
