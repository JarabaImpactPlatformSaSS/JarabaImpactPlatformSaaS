<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar agentes autonomos.
 */
class AutonomousAgentForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Agent name, type, vertical, and objective.'),
        'fields' => ['name', 'agent_type', 'vertical', 'objective'],
      ],
      'capabilities' => [
        'label' => $this->t('Capabilities'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Agent capabilities, guardrails, and approval requirements.'),
        'fields' => ['capabilities', 'guardrails', 'requires_approval'],
      ],
      'llm_config' => [
        'label' => $this->t('LLM Configuration'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Autonomy level, model, temperature, and action limits.'),
        'fields' => ['autonomy_level', 'llm_model', 'temperature', 'max_actions_per_run'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Active status and performance metrics.'),
        'fields' => ['is_active', 'performance_metrics', 'tenant_id'],
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
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
