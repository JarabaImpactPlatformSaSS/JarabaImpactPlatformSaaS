<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing agent flows.
 *
 * Validates that flow_config and trigger_config contain valid JSON.
 */
class AgentFlowForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'flow' => [
        'label' => $this->t('Flow'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Flow name and description.'),
        'fields' => ['name', 'description'],
      ],
      'config' => [
        'label' => $this->t('Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Flow steps and trigger configuration.'),
        'fields' => ['flow_config', 'trigger_type', 'trigger_config'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Flow status and execution data.'),
        'fields' => ['flow_status', 'execution_count', 'last_execution', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ai', 'name' => 'brain'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate that flow_config is valid JSON.
    $flowConfig = $form_state->getValue(['flow_config', 0, 'value']);
    if (!empty($flowConfig)) {
      json_decode($flowConfig);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName(
          'flow_config',
          $this->t('The flow configuration must be valid JSON. Error: @error', [
            '@error' => json_last_error_msg(),
          ]),
        );
      }
    }

    // Validate that trigger_config is valid JSON if provided.
    $triggerConfig = $form_state->getValue(['trigger_config', 0, 'value']);
    if (!empty($triggerConfig)) {
      json_decode($triggerConfig);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $form_state->setErrorByName(
          'trigger_config',
          $this->t('The trigger configuration must be valid JSON. Error: @error', [
            '@error' => json_last_error_msg(),
          ]),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
