<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class AgentFlowStepLogSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'agent_flow_step_log_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Agent Flow Step Log.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
