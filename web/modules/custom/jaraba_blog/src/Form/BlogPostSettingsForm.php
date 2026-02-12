<?php

declare(strict_types=1);

namespace Drupal\jaraba_blog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion para BlogPost.
 *
 * Proporciona la ruta base para Field UI.
 */
class BlogPostSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'blog_post_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Configuracion de entradas del blog. Usa las pestanas para gestionar campos y displays.') . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->messenger()->addStatus($this->t('Configuracion guardada.'));
  }

}
