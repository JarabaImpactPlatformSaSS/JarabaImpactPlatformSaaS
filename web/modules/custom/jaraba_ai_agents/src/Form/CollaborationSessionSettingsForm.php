<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Collaboration Session entity type settings.
 */
final class CollaborationSessionSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'collaboration_session_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Collaboration Session.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
