<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para la entidad ContentComment.
 *
 * Proporciona la ruta base para Field UI.
 */
class ContentCommentSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'content_comment_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuracion de la entidad Comentario. Usa las pestanas para gestionar campos y visualizacion.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No-op.
  }

}
