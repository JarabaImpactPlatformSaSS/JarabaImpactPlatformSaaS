<?php

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para la entidad ProviderProfile.
 *
 * Estructura: FormBase simple que sirve como ruta base para Field UI.
 *
 * Lógica: Proporciona el field_ui_base_route necesario para
 *   que aparezcan las pestañas "Manage fields" y "Manage display".
 */
class ProviderProfileSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'provider_profile_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuración de la entidad Perfil Profesional. Use las pestañas superiores para gestionar campos y modos de visualización.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
