<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para registrar ejecuciones de agentes.
 */
class AgentExecutionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'config' => [
        'label' => $this->t('Configuration'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Agent, trigger type, and trigger data.'),
        'fields' => ['tenant_id', 'agent_id', 'trigger_type', 'trigger_data'],
      ],
      'execution' => [
        'label' => $this->t('Execution'),
        'icon' => ['category' => 'actions', 'name' => 'calendar'],
        'description' => $this->t('Execution timing and status.'),
        'fields' => ['started_at', 'completed_at', 'status'],
      ],
      'results' => [
        'label' => $this->t('Results'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Actions, decisions, outcome, and feedback.'),
        'fields' => ['actions_taken', 'decisions_made', 'outcome', 'human_feedback', 'error_message'],
      ],
      'costs' => [
        'label' => $this->t('Costs'),
        'icon' => ['category' => 'commerce', 'name' => 'price'],
        'description' => $this->t('Token usage and cost estimate.'),
        'fields' => ['tokens_used', 'cost_estimate'],
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
