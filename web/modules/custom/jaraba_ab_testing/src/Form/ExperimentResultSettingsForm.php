<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para la entidad ExperimentResult.
 *
 * Estructura: FormBase simple que sirve como ruta base para Field UI.
 *
 * Logica: Proporciona el field_ui_base_route necesario para
 *   que aparezcan las pestanas "Manage fields" y "Manage display".
 *
 * Sintaxis: Drupal 11 — return types estrictos, FormStateInterface.
 */
class ExperimentResultSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'experiment_result_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuracion de la entidad Resultado de Experimento. Usa las pestanas superiores para gestionar campos y modos de visualizacion.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
