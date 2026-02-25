<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para gestionar aprobaciones de acciones de agentes.
 */
class AgentApprovalForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'request' => [
        'label' => $this->t('Request'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Execution, agent, action description, and reasoning.'),
        'fields' => ['tenant_id', 'execution_id', 'agent_id', 'action_description', 'reasoning'],
      ],
      'evaluation' => [
        'label' => $this->t('Evaluation'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Risk assessment, status, and review notes.'),
        'fields' => ['risk_assessment', 'status', 'review_notes'],
      ],
      'review' => [
        'label' => $this->t('Review'),
        'icon' => ['category' => 'users', 'name' => 'user'],
        'description' => $this->t('Reviewer, review date, and expiration.'),
        'fields' => ['reviewed_by', 'reviewed_at', 'expires_at'],
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
