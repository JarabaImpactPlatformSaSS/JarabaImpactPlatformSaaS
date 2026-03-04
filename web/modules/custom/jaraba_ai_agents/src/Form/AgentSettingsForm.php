<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion general de AI Agents.
 *
 * Estructura: FormBase para la ruta jaraba_ai_agents.settings.
 * Logica: Punto de entrada admin para configuracion de agentes IA.
 */
class AgentSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_ai_agents_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('AI Agents configuration. Use the tabs above to manage agent settings, model routing, and brand voice profiles.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
