<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para gestionar conversaciones de agentes.
 */
class AgentConversationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'conversation' => [
        'label' => $this->t('Conversation'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Tenant, user, current agent, and status.'),
        'fields' => ['tenant_id', 'user_id', 'current_agent_id', 'status'],
      ],
      'context' => [
        'label' => $this->t('Context'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Agent chain and shared context.'),
        'fields' => ['agent_chain', 'shared_context'],
      ],
      'metrics' => [
        'label' => $this->t('Metrics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Handoff count, satisfaction, tokens, and timestamps.'),
        'fields' => ['handoff_count', 'satisfaction_score', 'total_tokens', 'started_at', 'completed_at'],
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
