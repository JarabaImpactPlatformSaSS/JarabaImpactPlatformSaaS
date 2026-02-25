<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing secure conversations.
 */
class SecureConversationForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'conversation' => [
        'label' => $this->t('Conversation'),
        'icon' => ['category' => 'ui', 'name' => 'message'],
        'description' => $this->t('Subject and type.'),
        'fields' => ['title', 'conversation_type', 'context_type', 'context_id'],
      ],
      'participants' => [
        'label' => $this->t('Participants'),
        'icon' => ['category' => 'users', 'name' => 'group'],
        'description' => $this->t('Participant settings.'),
        'fields' => ['initiated_by', 'max_participants', 'participant_count', 'tenant_id'],
      ],
      'security' => [
        'label' => $this->t('Security'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('Encryption and confidentiality.'),
        'fields' => ['encryption_key_id', 'is_confidential', 'retention_days', 'auto_close_days'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Conversation status.'),
        'fields' => ['status', 'is_archived', 'is_muted_by_system', 'metadata'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'message'];
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
