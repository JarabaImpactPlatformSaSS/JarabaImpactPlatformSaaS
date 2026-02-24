<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

final class AiSkillEmbeddingSettingsForm extends FormBase {

  public function getFormId(): string {
    return 'ai_skill_embedding_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Use the tabs above to manage fields and display settings for Embedding de Habilidad IA.') . '</p>',
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
