<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class LocalBusinessProfileSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'local_business_profile_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuracion de la entidad Perfil de Negocio Local. Usa las pestanas de arriba para gestionar campos y visualizacion.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
