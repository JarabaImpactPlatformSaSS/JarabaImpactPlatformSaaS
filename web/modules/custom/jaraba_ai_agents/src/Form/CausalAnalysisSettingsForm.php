<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * FIELD-UI-SETTINGS-TAB-001: Settings form for CausalAnalysis entity.
 */
class CausalAnalysisSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'causal_analysis_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Causal Analysis entity settings. Analyses are performed by the Causal Analytics Service using LLM premium tier.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
