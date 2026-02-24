<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class ConversationParticipantSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'conversation_participant_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Conversation Participant.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
