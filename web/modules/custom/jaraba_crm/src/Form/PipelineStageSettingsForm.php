<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para la entidad PipelineStage.
 *
 * Requerido por field_ui_base_route para habilitar
 * "Administrar campos" y "Administrar visualizacion".
 */
class PipelineStageSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'crm_pipeline_stage_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Usa las pestanas de arriba para administrar los campos y la visualizacion de las etapas del pipeline.') . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

}
