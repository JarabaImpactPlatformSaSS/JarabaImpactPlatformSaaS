<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para la entidad ClientCase.
 *
 * Estructura: FormBase simple que sirve como ruta base para Field UI.
 *
 * Logica: Proporciona el field_ui_base_route necesario para
 *   que aparezcan las pestanas "Manage fields" y "Manage display".
 */
class ClientCaseSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'client_case_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuracion de la entidad Expediente Juridico. Use las pestanas superiores para gestionar campos y modos de visualizacion.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
