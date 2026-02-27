<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * FIELD-UI-SETTINGS-TAB-001: Settings form for AutonomousSession entity.
 */
class AutonomousSessionSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'autonomous_session_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Autonomous Session entity settings. Use the dashboard at <a href=":url">Autonomous Agents</a> to manage sessions.', [
        ':url' => '/admin/config/ai/autonomous-agents',
      ]) . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
