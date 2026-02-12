<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para la entidad ScheduledPublish.
 *
 * P1-05: Requerido por field_ui_base_route para habilitar Field UI.
 * Permite gestionar la configuracion de campos via /admin/structure.
 */
class ScheduledPublishSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'scheduled_publish_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuracion de la entidad Publicacion Programada. Utiliza las pestanas para gestionar los campos y la visualizacion.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No action needed - Field UI handles its own config.
  }

}
