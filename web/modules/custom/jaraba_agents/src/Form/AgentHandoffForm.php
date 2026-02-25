<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para gestionar handoffs de agentes.
 */
class AgentHandoffForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'transfer' => [
        'label' => $this->t('Transfer'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Conversation and agents involved in the handoff.'),
        'fields' => ['conversation_id', 'from_agent_id', 'to_agent_id'],
      ],
      'details' => [
        'label' => $this->t('Details'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Reason, context, confidence, and timing.'),
        'fields' => ['reason', 'context_transferred', 'confidence', 'handoff_at'],
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
